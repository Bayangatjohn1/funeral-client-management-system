<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Models\PaymentCorrectionRequest;
use App\Models\User;
use App\Models\WorkflowNotice;
use App\Support\AuditLogger;
use App\Support\Payments\PaymentDetails;
use App\Support\Payments\PaymentIdentifier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function checkIdentifier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'payment_method' => ['required', Rule::in(['cash', 'cashless'])],
            'identifier_type' => ['required', Rule::in(['receipt_or_no', 'reference_number', 'approval_code'])],
            'identifier' => ['required', 'string', 'max:100'],
            'cashless_type' => ['nullable', Rule::in(PaymentDetails::CASHLESS_TYPES)],
            'provider' => ['nullable', 'string', 'max:100'],
        ]);

        $branchId = (int) $validated['branch_id'];
        if (! in_array($branchId, $request->user()->branchScopeIds(), true)) {
            abort(403, 'Branch is outside your payment scope.');
        }

        $identifierType = $validated['identifier_type'];
        $paymentMethod = $validated['payment_method'];
        $cashlessType = $validated['cashless_type'] ?? null;
        $identifier = trim($validated['identifier']);

        if ($identifierType === 'receipt_or_no' && (! preg_match('~^[A-Za-z0-9 /-]{3,50}$~', $identifier))) {
            return response()->json(['duplicate' => false]);
        }
        if ($identifierType === 'reference_number' && ($paymentMethod !== 'cashless' || ! preg_match('/^[A-Za-z0-9 _\/-]{4,60}$/', $identifier))) {
            return response()->json(['duplicate' => false]);
        }
        if ($identifierType === 'approval_code' && ($paymentMethod !== 'cashless' || $cashlessType !== 'card' || ! preg_match('/^[A-Za-z0-9_\/-]{4,40}$/', $identifier))) {
            return response()->json(['duplicate' => false]);
        }
        if ($paymentMethod === 'cashless' && ! $cashlessType) {
            return response()->json(['duplicate' => false]);
        }

        $duplicate = PaymentIdentifier::duplicate(
            branchId: $branchId,
            identifierType: $identifierType,
            value: $identifier,
            cashlessType: $cashlessType,
            provider: $validated['provider'] ?? null,
        );

        return response()->json([
            'duplicate' => (bool) $duplicate,
            'case_code' => $duplicate?->funeralCase?->case_code,
            'message' => $duplicate ? PaymentIdentifier::duplicateMessage($identifierType, $duplicate) : null,
        ]);
    }

    public function recordableCaseSuggestions(Request $request): JsonResponse
    {
        $this->authorize('create', Payment::class);
        $validated = $request->validate(['q' => ['required', 'string', 'max:100']]);
        $term = trim($validated['q']);

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $caseId = ctype_digit($term) && strlen($term) <= 18 ? (int) $term : null;
        $pattern = '%'.strtr(mb_strtolower($term), ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
        $matches = $this->recordableCases($this->paymentWriteBranchIds($request->user()))
            ->with(['client:id,full_name', 'deceased:id,full_name'])
            ->where(function (Builder $query) use ($caseId, $pattern) {
                $query->whereRaw("LOWER(case_code) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereHas('client', fn (Builder $name) => $name->whereRaw("LOWER(full_name) LIKE ? ESCAPE '!'", [$pattern]))
                    ->orWhereHas('deceased', fn (Builder $name) => $name->whereRaw("LOWER(full_name) LIKE ? ESCAPE '!'", [$pattern]));

                if ($caseId !== null) {
                    $query->orWhere('id', $caseId);
                }
            })
            ->orderByRaw('CASE WHEN id = ? OR LOWER(case_code) = ? THEN 0 ELSE 1 END', [$caseId, mb_strtolower($term)])
            ->latest('id')
            ->limit(5)
            ->get(['id', 'case_code', 'client_id', 'deceased_id'])
            ->map(fn (FuneralCase $case) => [
                'caseCode' => $case->case_code,
                'client' => $case->client?->full_name,
                'deceased' => $case->deceased?->full_name,
                'url' => route('payments.history', ['case_id' => $case->id, 'record_payment' => 1, 'dashboard_search' => 1]),
            ]);

        return response()->json($matches);
    }

    public function index(Request $request)
    {
        if ($request->user()?->isOwner()) {
            if (Route::has('owner.analytics')) {
                return redirect()->route('owner.analytics');
            }

            abort(403);
        }

        $this->authorize('viewAny', Payment::class);

        // The former Record Payment page is retained only as a compatibility
        // endpoint. Recording now happens in the Payment Monitoring modal.
        $paymentSaved = $request->session()->has('success');
        $request->session()->reflash();

        return redirect()->route('payments.history', array_filter([
            'record_payment' => $paymentSaved ? null : 1,
            'case_id' => $request->integer('case_id') ?: null,
        ]));
    }

    public function void(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($payment, $validated) {
                $funeralCase = $payment->funeralCase()->lockForUpdate()->first();
                if (!$funeralCase) {
                    throw new \RuntimeException('Related case not found.');
                }

                if (in_array($payment->status, ['VOID', 'VOIDED'], true)) {
                    throw new \RuntimeException('Payment is already voided.');
                }

                $reason = AuditLogger::requireReason($validated, 'reason');

                $beforeTotalPaid = round((float) $funeralCase->total_paid, 2);
                $beforeBalance = round((float) $funeralCase->balance_amount, 2);
                $beforeStatus = $funeralCase->payment_status;

                $payment->update([
                    'status' => 'VOIDED',
                    'void_reason' => $reason,
                ]);

                // Re-derive aggregates from the payments table so the case totals are
                // authoritative even if multiple payments exist.
                $funeralCase->recalculatePaymentTotals();
                $funeralCase->refresh();

                AuditLogger::log(
                    'payment.voided',
                    'delete',
                    'payment',
                    $payment->id,
                    [
                        'case_id' => $funeralCase->id,
                        'amount' => $payment->amount,
                        'payment_record_no' => $payment->display_payment_record_no,
                        'reason' => $reason,
                        'changes' => [
                            ['field' => 'payment_status', 'before' => $beforeStatus, 'after' => $funeralCase->payment_status],
                            ['field' => 'total_paid', 'before' => $beforeTotalPaid, 'after' => $funeralCase->total_paid],
                            ['field' => 'balance_amount', 'before' => $beforeBalance, 'after' => $funeralCase->balance_amount],
                        ],
                    ],
                    (int) $funeralCase->branch_id,
                    null,
                    'success',
                    $reason,
                    'Payment voided'
                );
            });
        } catch (\RuntimeException $e) {
            Log::error('payment.void_failed', [
                'payment_id' => $payment->id,
                'case_id'    => $payment->funeral_case_id,
                'error'      => $e->getMessage(),
            ]);
            AuditLogger::log(
                'payment.void_failed',
                'delete',
                'payment',
                $payment->id,
                [
                    'case_id' => $payment->funeral_case_id,
                    'amount' => $payment->amount,
                    'error' => $e->getMessage(),
                ],
                (int) ($payment->branch_id ?? 0),
                null,
                'failed',
                $e->getMessage(),
                'Payment void failed'
            );
            return back()->withErrors(['payment' => $e->getMessage()])->withInput();
        }

        return redirect()->route('payments.history')->with('success', 'Payment voided successfully.');
    }

    public function history(Request $request)
    {
        if ($request->user()?->isOwner()) {
            if (Route::has('owner.analytics')) {
                return redirect()->route('owner.analytics');
            }

            abort(403);
        }

        $this->authorize('viewAny', Payment::class);

        $user = auth()->user();
        $viewBranchScopeIds = $this->paymentViewBranchIds($user);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100', "regex:/^[\\pL\\pN\\pM\\s.\\/_#'-]+$/u"],
            'date_preset' => ['nullable', 'in:all,today,week,month,year,custom'],
            'paid_from' => ['nullable', 'date'],
            'paid_to' => ['nullable', 'date', 'after_or_equal:paid_from'],
            'branch_id' => ['nullable', 'string', 'max:20'],
            'payment_status' => ['nullable', 'in:PAID,PARTIAL,UNPAID,WITH_BALANCE,HAS_PAYMENT'],
            'case_scope' => ['nullable', 'in:active_completed'],
            'case_status' => ['nullable', 'in:DRAFT,ACTIVE,COMPLETED'],
            'payment_method' => ['nullable', 'in:cash,cashless,bank_transfer'],
            'status_after_payment' => ['nullable', 'in:PAID,PARTIAL,UNPAID'],
            'sort' => ['nullable', 'in:asc,desc,balance_high,balance_low'],
            'tab' => ['nullable', 'in:summary,transactions,corrections'],
            'correction_q' => 'nullable|string|max:100',
            'correction_status' => 'nullable|in:PENDING,APPROVED,REJECTED,WITHDRAWN',
            'correction_type' => 'nullable|in:CORRECT,VOID_ONLY',
            'correction_period' => 'nullable|in:week,month_to_date,last_30_days',
            'correction_branch_id' => 'nullable|integer',
            'correction_requester_id' => 'nullable|integer|min:1',
            'correction_urgency' => 'nullable|in:overdue',
            'correction_sort' => 'nullable|in:newest,oldest',
            'correction_per_page' => 'nullable|in:10,20,50',
            'open_correction' => 'nullable|integer|min:1',
            'record_payment' => ['nullable', 'boolean'],
            'dashboard_search' => ['nullable', 'boolean'],
            'case_id' => ['nullable', 'integer', 'exists:funeral_cases,id'],
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, slashes, underscores, #, and hyphens only.',
        ]);

        $requestedBranchId = $validated['branch_id'] ?? null;
        $defaultBranchId = (
            !$request->has('branch_id')
            && $user?->role === 'staff'
            && $user->branch_id
            && in_array((int) $user->branch_id, $viewBranchScopeIds, true)
        ) ? (int) $user->branch_id : null;
        $effectiveBranchId = $requestedBranchId === 'all' ? null : ($requestedBranchId ?? $defaultBranchId);
        $branchScopeIds = $this->selectedPaymentBranchIds($viewBranchScopeIds, $effectiveBranchId);
        $mainBranchId = $branchScopeIds[0] ?? null;
        $selectedBranchId = $requestedBranchId === 'all'
            ? 'all'
            : (count($branchScopeIds) === 1 ? $branchScopeIds[0] : $effectiveBranchId);
        $branches = Branch::query()
            ->whereIn('id', $viewBranchScopeIds)
            ->orderBy('branch_code')
            ->get(['id', 'branch_code', 'branch_name']);
        $assignedBranch = count($viewBranchScopeIds) === 1 ? $branches->first() : null;

        $q = $validated['q'] ?? null;
        $datePreset = $validated['date_preset'] ?? 'all';
        $paidFrom = $validated['paid_from'] ?? null;
        $paidTo = $validated['paid_to'] ?? null;
        $currentPaymentStatus = $validated['payment_status'] ?? ($validated['status_after_payment'] ?? null);
        $caseScopePreset = $validated['case_scope'] ?? null;
        $caseStatus = $validated['case_status'] ?? null;
        $paymentMethod = $validated['payment_method'] ?? null;
        $sort = $validated['sort'] ?? 'desc';
        $applyMonitoringSort = function ($query) use ($sort) {
            if (in_array($sort, ['balance_high','balance_low'], true)) {
                $direction = $sort === 'balance_high' ? 'desc' : 'asc';
                $query->orderBy('balance_amount', $direction)->orderBy('id', $direction);
            } else {
                $query->orderByRaw('payments_max_paid_at IS NULL ASC')
                    ->orderBy('payments_max_paid_at', $sort)->orderBy('id', $sort);
            }
        };
        $activeTab = $validated['tab'] ?? 'summary';
        $canRecordPayment = $user?->isStaff() && $user->can('create', Payment::class);
        $fromDashboardSearch = $canRecordPayment && $request->boolean('dashboard_search');

        $openCases = collect();
        $preselectCase = null;
        if ($canRecordPayment) {
            $openCases = $this->recordableCases($viewBranchScopeIds)
                ->select(['id', 'branch_id', 'client_id', 'deceased_id', 'package_id', 'case_code', 'package_name_snapshot', 'custom_package_name', 'service_package', 'total_amount', 'total_paid', 'balance_amount'])
                ->with([
                    'client:id,full_name',
                    'deceased:id,full_name',
                    'package:id,name',
                ])
                ->latest()
                ->get();

            if (!empty($validated['case_id'])) {
                $preselectCase = $openCases->firstWhere('id', (int) $validated['case_id']);
            }
        }

        if ($fromDashboardSearch && $preselectCase === null) {
            return redirect()->route('payments.history')->with('warning', 'This case is no longer available for payment recording.');
        }

        if (!$paidFrom && !$paidTo) {
            $today = Carbon::today();

            [$paidFrom, $paidTo] = match ($datePreset) {
                'today' => [$today->toDateString(), $today->toDateString()],
                'week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
                'month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
                'year' => [$today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
                default => [null, null],
            };
        }

        $paidFromDate = $paidFrom ? Carbon::parse($paidFrom)->startOfDay() : null;
        $paidToDate = $paidTo ? Carbon::parse($paidTo)->endOfDay() : null;

        $applyValidPayment = function ($query) {
            $query->where(function ($statusQuery) {
                $statusQuery->whereNull('status')->orWhereNotIn('status', ['VOID', 'VOIDED']);
            });
        };

        $applyPaymentMethod = function ($query) use ($paymentMethod) {
            if (!$paymentMethod) {
                return;
            }

            $query->where(function ($methodQuery) use ($paymentMethod) {
                if ($paymentMethod === 'cashless') {
                    $methodQuery->where('payment_method', 'cashless')
                        ->orWhere('payment_method', 'bank_transfer')
                        ->orWhere('payment_mode', 'bank_transfer');
                } elseif ($paymentMethod === 'cash') {
                    $methodQuery->where('payment_method', 'cash')
                        ->orWhere(function ($legacy) {
                            $legacy->whereNull('payment_method')->where('payment_mode', 'cash');
                        });
                } else {
                    $methodQuery->where('payment_method', $paymentMethod)
                        ->orWhere('payment_mode', $paymentMethod);
                }
            });
        };

        $applyPaymentFilters = function ($query) use ($applyValidPayment, $applyPaymentMethod, $paidFromDate, $paidToDate) {
            $applyValidPayment($query);
            $applyPaymentMethod($query);
            $query
                ->when($paidFromDate, fn ($paymentQuery) => $paymentQuery->where('paid_at', '>=', $paidFromDate))
                ->when($paidToDate, fn ($paymentQuery) => $paymentQuery->where('paid_at', '<=', $paidToDate));
        };

        $caseScope = function ($query) use ($branchScopeIds) {
            $query->whereIn('branch_id', $branchScopeIds)
                ->where(function ($scopeQuery) {
                    $scopeQuery->where('entry_source', 'MAIN')
                        ->orWhereNull('entry_source');
                });
        };

        $fuzzyNamePatterns = [];
        $nameTokens = array_slice(preg_split('/\\s+/u', mb_strtolower((string) $q, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 2);
        foreach ($nameTokens as $token) {
            if (!preg_match('/\\A[a-z]{5,15}\\z/', $token)) {
                continue;
            }
            $length = strlen($token);
            for ($index = 0; $index < $length; $index++) {
                $fuzzyNamePatterns[] = '%'.substr($token, 0, $index).substr($token, $index + 1).'%';
                $fuzzyNamePatterns[] = '%'.substr($token, 0, $index).'_'.substr($token, $index + 1).'%';
                $fuzzyNamePatterns[] = '%'.substr($token, 0, $index).'_'.substr($token, $index).'%';
                if ($index + 1 < $length) {
                    $swapped = $token;
                    $swapped[$index] = $token[$index + 1];
                    $swapped[$index + 1] = $token[$index];
                    $fuzzyNamePatterns[] = '%'.$swapped.'%';
                }
            }
            $fuzzyNamePatterns[] = '%'.$token.'_%';
        }
        $fuzzyNamePatterns = array_values(array_unique($fuzzyNamePatterns));
        $searchName = function ($nameQuery) use ($q, $fuzzyNamePatterns) {
            $nameQuery->where('full_name', 'like', "%{$q}%");
            if ($fuzzyNamePatterns) {
                $nameQuery->orWhere(function ($fuzzyQuery) use ($fuzzyNamePatterns) {
                    foreach ($fuzzyNamePatterns as $pattern) {
                        $fuzzyQuery->orWhereRaw('LOWER(full_name) LIKE ?', [$pattern]);
                    }
                });
            }
        };

        $normalizedSearch = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $q));
        $normalizedReferenceSearch = strlen($normalizedSearch) >= 5 && preg_match('/\d/', $normalizedSearch)
            ? "%{$normalizedSearch}%"
            : null;
        $applyCaseSearch = function ($query) use ($q, $searchName, $normalizedReferenceSearch) {
            $query->where(function ($sub) use ($q, $searchName, $normalizedReferenceSearch) {
                $sub->where('case_code', 'like', "%{$q}%")
                    ->when($normalizedReferenceSearch, fn ($caseQuery) => $caseQuery->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(case_code), '-', ''), '/', ''), '_', ''), ' ', ''), '.', '') LIKE ?",
                        [$normalizedReferenceSearch]
                    ))
                    ->when(ctype_digit((string) $q), fn ($caseQuery) => $caseQuery->orWhere('id', (int) $q))
                    ->orWhereHas('client', $searchName)
                    ->orWhereHas('deceased', $searchName)
                    // Search historical references too, including voided entries, so staff can
                    // still locate the case that owns a corrected/replaced payment record.
                    ->orWhereHas('payments', function ($paymentQuery) use ($q, $normalizedReferenceSearch) {
                        $paymentQuery->where(function ($referenceQuery) use ($q, $normalizedReferenceSearch) {
                            foreach (['payment_record_no', 'receipt_number', 'receipt_or_no', 'accounting_reference_no', 'transaction_reference_no', 'reference_number', 'approval_code'] as $index => $column) {
                                $method = $index === 0 ? 'where' : 'orWhere';
                                $referenceQuery->{$method}($column, 'like', "%{$q}%");
                                if ($normalizedReferenceSearch) {
                                    $referenceQuery->orWhereRaw(
                                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(UPPER({$column}), '-', ''), '/', ''), '_', ''), ' ', ''), '.', '') LIKE ?",
                                        [$normalizedReferenceSearch]
                                    );
                                }
                            }
                        });
                    });
            });
        };

        $applyPaymentDateRange = function ($query) use ($paidFromDate, $paidToDate, $applyValidPayment) {
            if (!$paidFromDate && !$paidToDate) {
                return;
            }

            $query->whereHas('payments', function ($paymentQuery) use ($paidFromDate, $paidToDate, $applyValidPayment) {
                $applyValidPayment($paymentQuery);
                $paymentQuery
                    ->when($paidFromDate, fn ($q) => $q->where('paid_at', '>=', $paidFromDate))
                    ->when($paidToDate, fn ($q) => $q->where('paid_at', '<=', $paidToDate));
            });
        };

        $caseFilter = FuneralCase::query()
            ->where($caseScope)
            ->when($q, $applyCaseSearch)
            ->when($paidFromDate || $paidToDate, $applyPaymentDateRange)
            ->when($caseScopePreset === 'active_completed', fn ($query) => $query
                ->whereIn('case_status', ['ACTIVE', 'COMPLETED']))
            ->when($currentPaymentStatus === 'WITH_BALANCE', fn ($query) => $query
                ->whereIn('case_status', ['ACTIVE', 'COMPLETED'])
                ->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
                ->where('balance_amount', '>', 0))
            ->when($currentPaymentStatus === 'HAS_PAYMENT', fn ($query) => $query->whereHas('payments', $applyPaymentFilters))
            ->when(
                $currentPaymentStatus && !in_array($currentPaymentStatus, ['WITH_BALANCE', 'HAS_PAYMENT'], true),
                function ($query) use ($currentPaymentStatus) {
                    $query->where('payment_status', $currentPaymentStatus);

                    if ($currentPaymentStatus === 'PAID') {
                        $query->where('balance_amount', '<=', 0);
                    } elseif ($currentPaymentStatus === 'PARTIAL') {
                        $query->where('total_paid', '>', 0)->where('balance_amount', '>', 0);
                    } elseif ($currentPaymentStatus === 'UNPAID') {
                        $query->where('balance_amount', '>', 0);
                    }
                }
            )
            ->when($caseStatus, fn ($query) => $query->where('case_status', $caseStatus))
            ->when($paymentMethod, fn ($query) => $query->whereHas('payments', $applyPaymentFilters));

        $filteredPaymentQuery = Payment::query()
            ->whereIn('funeral_case_id', (clone $caseFilter)->select('id'))
            ->tap($applyPaymentFilters);

        $paymentRecordsCount = (clone $filteredPaymentQuery)->count();

        $caseKpi = (clone $caseFilter)
            ->selectRaw('COUNT(*) as cases_count')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as outstanding_balance')
            ->first();

        $totalCasesWithPayments = (int) ($caseKpi->cases_count ?? 0);
        $totalCollected = (float) (clone $filteredPaymentQuery)->sum('amount');
        $totalOutstanding = (float) ($caseKpi->outstanding_balance ?? 0);
        $totalAmountAvailed = (float) (clone $caseFilter)->sum('total_amount');

        $paymentCasesQuery = (clone $caseFilter)
            ->select([
                'id',
                'branch_id',
                'client_id',
                'deceased_id',
                'case_code',
                'total_amount',
                'total_paid',
                'balance_amount',
                'payment_status',
                'case_status',
            ])
            ->with([
                'branch:id,branch_code,branch_name',
                'client:id,full_name',
                'deceased:id,full_name',
                'payments' => function ($query) use ($applyValidPayment, $request) {
                    if ($request->routeIs('payments.monitoring.print')) $applyValidPayment($query);
                    $query->select([
                            'id',
                            'funeral_case_id',
                            'branch_id',
                            'receipt_number',
                            'payment_record_no',
                            'accounting_reference_no',
                            'receipt_or_no',
                            'proof_path',
                            'proof_mime',
                            'proof_upload_id',
                            'status',
                            'payment_method',
                            'cashless_type',
                            'bank_name',
                            'other_bank_name',
                            'wallet_provider',
                            'account_name',
                            'mobile_number',
                            'payment_mode',
                            'reference_number',
                            'approval_code',
                            'card_type',
                            'terminal_provider',
                            'payment_channel',
                            'payment_notes',
                            'bank_or_channel',
                            'other_bank_or_channel',
                            'transaction_reference_no',
                            'sender_name',
                            'transfer_datetime',
                            'amount',
                            'balance_after_payment',
                            'payment_status_after_payment',
                            'paid_date',
                            'paid_at',
                            'created_at',
                            'received_by',
                            'encoded_by',
                            'recorded_by',
                            'remarks',
                        ])
                        ->with(['recordedBy:id,name', 'encodedBy:id,name', 'proofUpload.user:id,name', 'correctionRequests.requester:id,name', 'correctionRequests.reviewer:id,name', 'correctionRequests.replacementPayment:id,branch_id,payment_record_no,receipt_number'])
                        ->orderBy('paid_at')
                        ->orderBy('id');
                },
            ])
            ->withCount(['payments' => $applyValidPayment])
            ->withMax(['payments' => $applyValidPayment], 'paid_at')
            ->tap($applyMonitoringSort);

        if ($request->routeIs('payments.monitoring.print')) {
            return view('staff.payments.monitoring_print', [
                'paymentCases' => $paymentCasesQuery->get(),
                'branches' => $branches,
                'selectedBranchId' => $selectedBranchId,
                'assignedBranch' => $assignedBranch,
                'q' => $q,
                'paidFrom' => $paidFrom,
                'paidTo' => $paidTo,
                'paymentStatus' => $currentPaymentStatus,
                'caseStatus' => $caseStatus,
                'paymentMethod' => $paymentMethod,
                'totalAmountAvailed' => $totalAmountAvailed,
                'totalCollected' => $totalCollected,
                'totalOutstanding' => $totalOutstanding,
            ]);
        }

        $requestedPageSize = (int) $request->input('per_page', 20);
        $pageSize = in_array($requestedPageSize, [10, 20, 50, 100], true) ? $requestedPageSize : 20;
        $paymentCases = $paymentCasesQuery
            ->paginate($pageSize)
            ->withQueryString();

        $filteredPayments = function ($query) use ($applyPaymentFilters) {
            $applyPaymentFilters($query);
            $query->select([
                    'id',
                    'funeral_case_id',
                    'branch_id',
                    'receipt_number',
                    'payment_record_no',
                    'accounting_reference_no',
                    'receipt_or_no',
                    'proof_path',
                    'proof_mime',
                    'proof_upload_id',
                    'status',
                    'payment_method',
                    'cashless_type',
                    'bank_name',
                    'other_bank_name',
                    'wallet_provider',
                    'account_name',
                    'mobile_number',
                    'payment_mode',
                    'approval_code',
                    'card_type',
                    'terminal_provider',
                    'payment_channel',
                    'payment_notes',
                    'bank_or_channel',
                    'other_bank_or_channel',
                    'transaction_reference_no',
                    'reference_number',
                    'sender_name',
                    'transfer_datetime',
                    'amount',
                    'balance_after_payment',
                    'payment_status_after_payment',
                    'paid_date',
                    'paid_at',
                    'created_at',
                    'received_by',
                    'encoded_by',
                    'recorded_by',
                    'remarks',
                ])
                ->with(['recordedBy:id,name', 'encodedBy:id,name', 'proofUpload.user:id,name', 'correctionRequests.requester:id,name', 'correctionRequests.reviewer:id,name'])
                ->orderByDesc('paid_at')
                ->orderByDesc('id');
        };

        $transactionCases = (clone $caseFilter)
            ->whereHas('payments', $applyPaymentFilters)
            ->select([
                'id',
                'branch_id',
                'client_id',
                'deceased_id',
                'case_code',
                'total_amount',
                'total_paid',
                'balance_amount',
                'payment_status',
                'case_status',
            ])
            ->with([
                'branch:id,branch_code,branch_name',
                'client:id,full_name',
                'deceased:id,full_name',
                'payments' => $filteredPayments,
            ])
            ->withCount(['payments' => $applyPaymentFilters])
            ->withMax(['payments' => $applyPaymentFilters], 'paid_at')
            ->tap($applyMonitoringSort)
            ->paginate($pageSize, ['*'], 'transactions_page')
            ->withQueryString();

        $systemAdmin = $user->isSystemAdmin();
        $correctionQuery = ($systemAdmin || $user->isBranchAdmin() || $user->isStaff())
            ? PaymentCorrectionRequest::forMonitoring($user)
            : null;
        $correctionRequesters = $user->isBranchAdmin() && $activeTab === 'corrections'
            ? User::whereIn('id', (clone $correctionQuery)->select('requested_by'))->orderBy('name')->get(['id', 'name'])
            : collect();
        $correctionNow = now();
        $correctionFrom = match ($validated['correction_period'] ?? null) {
            'week' => $correctionNow->copy()->subDays(6)->startOfDay(),
            'last_30_days' => $correctionNow->copy()->subDays(29)->startOfDay(),
            'month_to_date' => $correctionNow->copy()->startOfMonth(),
            default => null,
        };
        $correctionResults = $correctionQuery ? clone $correctionQuery : null;
        $correctionResults?->when($request->filled('correction_status'), fn ($q) => $q->where('status', $request->input('correction_status')))
            ->when($request->filled('correction_type'), fn ($q) => $q->where('correction_type', $validated['correction_type']))
            ->when($correctionFrom, fn ($q) => $q->whereBetween('created_at', [$correctionFrom, $correctionNow]))
            ->when($user->isBranchAdmin() && $request->filled('correction_requester_id'), fn ($q) => $q->where('requested_by', $validated['correction_requester_id']))
            ->when($systemAdmin && $request->filled('correction_branch_id'), fn ($q) => $q->where('branch_id', $validated['correction_branch_id']))
            ->when($systemAdmin && ($validated['correction_urgency'] ?? null) === 'overdue', fn ($q) => $q->where('status', 'PENDING')
                ->where('created_at', '<=', $correctionNow->copy()->subHours(WorkflowNotice::CORRECTION_FOLLOW_UP_HOURS)))
            ->when($request->filled('correction_q'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = trim($request->input('correction_q'));
                if (preg_match('/\A#([0-9]+)\z/', $term, $requestNumber)) {
                    $q->where('id', $requestNumber[1]);
                    return;
                }
                $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $term));
                $normalizedPattern = strlen($normalized) >= 5 && preg_match('/\d/', $normalized) ? '%'.$normalized.'%' : null;
                $isFullPaymentRecord = preg_match('/\APAY-[A-Z0-9]+-\d{4}-\d+\z/i', $term) === 1;
                $columns = ['payment_record_no', 'receipt_number', 'receipt_or_no', 'accounting_reference_no', 'transaction_reference_no', 'reference_number', 'approval_code'];
                $matchPaymentReferences = function ($paymentQuery) use ($term, $normalized, $normalizedPattern, $isFullPaymentRecord, $columns) {
                    $paymentQuery->where(function ($referenceQuery) use ($term, $normalized, $normalizedPattern, $isFullPaymentRecord, $columns) {
                        foreach ($columns as $index => $column) {
                            $method = $index === 0 ? 'where' : 'orWhere';
                            $exactRecordColumn = $isFullPaymentRecord && $column === 'payment_record_no';
                            $referenceQuery->{$method}($column, $exactRecordColumn ? '=' : 'like', $exactRecordColumn ? $term : '%'.$term.'%');
                            if ($normalizedPattern) {
                                $referenceQuery->orWhereRaw(
                                    "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(UPPER({$column}), '-', ''), '/', ''), '_', ''), ' ', ''), '.', '') LIKE ?",
                                    [$exactRecordColumn ? $normalized : $normalizedPattern]
                                );
                            }
                        }
                    });
                };
                $q->whereHas('payment', $matchPaymentReferences)
                    ->orWhereHas('replacementPayment', $matchPaymentReferences)
                    ->orWhereHas('payment.funeralCase', fn ($caseQuery) => $caseQuery
                        ->where('case_code', 'like', '%'.$term.'%')
                        ->when($normalizedPattern, fn ($caseQuery) => $caseQuery->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(case_code), '-', ''), '/', ''), '_', ''), ' ', ''), '.', '') LIKE ?",
                            [$normalizedPattern]
                        ))
                        ->orWhereHas('client', fn ($nameQuery) => $nameQuery->where('full_name', 'like', '%'.$term.'%'))
                        ->orWhereHas('deceased', fn ($nameQuery) => $nameQuery->where('full_name', 'like', '%'.$term.'%')));
            }));

        return view('staff.payments.history', [
            'correctionRequesters' => $correctionRequesters,
            'pendingCorrectionCount' => $correctionQuery ? (clone $correctionQuery)->where('status', 'PENDING')->count() : 0,
            'correctionRequests' => $correctionQuery && $activeTab === 'corrections'
                ? $correctionResults->with(['payment.funeralCase', 'requester', 'reviewer', 'replacementPayment', 'branch'])
                    ->orderBy('id', $request->input('correction_sort') === 'oldest' ? 'asc' : 'desc')
                    ->paginate((int) $request->input('correction_per_page', 20))
                    ->appends($request->except('open_correction')) : null,
            'openCorrectionId' => $systemAdmin && $activeTab === 'corrections' ? ($validated['open_correction'] ?? null) : null,
            'paymentCases' => $paymentCases,
            'transactionCases' => $transactionCases,
            'q' => $q,
            'paidFrom' => $paidFrom,
            'paidTo' => $paidTo,
            'statusAfterPayment' => $currentPaymentStatus,
            'paymentStatus' => $currentPaymentStatus,
            'caseScopePreset' => $caseScopePreset,
            'caseStatus' => $caseStatus,
            'paymentMethod' => $paymentMethod,
            'sort' => $sort,
            'activeTab' => $activeTab,
            'mainBranchId' => $mainBranchId,
            'defaultPaymentBranchId' => $defaultBranchId,
            'branches' => $branches,
            'assignedBranch' => $assignedBranch,
            'selectedBranchId' => $selectedBranchId,
            'totalCasesWithPayments' => $totalCasesWithPayments,
            'paymentRecordsCount' => $paymentRecordsCount,
            'totalCollected' => $totalCollected,
            'totalOutstanding' => $totalOutstanding,
            'totalAmountAvailed' => $totalAmountAvailed,
            'canRecordPayment' => $canRecordPayment,
            'openCases' => $openCases,
            'preselectCase' => $preselectCase,
            'fromDashboardSearch' => $fromDashboardSearch,
            'autoOpenPayment' => $canRecordPayment && ($request->boolean('record_payment') || $preselectCase !== null),
        ]);
    }

    public function store(Request $request)
    {
        if (! $request->filled('receipt_or_no') && $request->filled('accounting_reference_no')) {
            $request->merge(['receipt_or_no' => $request->input('accounting_reference_no')]);
        }
        $this->authorize('create', Payment::class);

        $validated = $request->validate(array_merge([
            'submission_id' => ['nullable', 'uuid'],
            'payment_proof_id' => ['required', 'uuid'],
            'payment_proof_form_id' => ['required', 'uuid'],
            'funeral_case_id' => ['required', 'exists:funeral_cases,id'],
            'paid_at' => PaymentDetails::dateRules(),
            'amount_paid' => PaymentDetails::amountRules(),
            'receipt_or_no' => ['required_if:payment_method,cash', 'nullable', 'string', 'min:3', 'max:50', 'regex:~^[A-Za-z0-9 /-]+$~'],
            'accounting_reference_no' => ['nullable', 'string', 'max:100', 'not_regex:/<[^>]*>|[<>]/'],
            'received_by' => ['nullable', 'string', 'max:120', 'not_regex:/<[^>]*>|[<>]/'],
            'remarks' => ['nullable', 'string', 'max:255', 'not_regex:/<[^>]*>|[<>]/'],
            'return_to_case' => ['nullable', 'boolean'],
            'return_to' => ['nullable', 'string', 'max:2048'],
            'void' => ['nullable', 'boolean'],
            'void_reason' => ['nullable', 'string', 'max:255'],
        ], PaymentDetails::rules()), PaymentDetails::messages());

        $receiptOrNo = trim((string) ($validated['receipt_or_no'] ?? $validated['accounting_reference_no'] ?? ''));
        $receiptOrNo = $receiptOrNo === '' ? null : $receiptOrNo;

        $paymentDetails = PaymentDetails::normalize($request);
        $submissionHash = hash('sha256', json_encode([
            'case' => (int) $validated['funeral_case_id'],
            'cents' => (int) round((float) $validated['amount_paid'] * 100),
            'paid_at' => Carbon::parse($validated['paid_at'])->toDateTimeString(),
            'details' => $paymentDetails, 'receipt' => $receiptOrNo, 'proof' => $validated['payment_proof_id'],
            'received_by' => $validated['received_by'] ?? null, 'remarks' => $validated['remarks'] ?? null,
        ], JSON_THROW_ON_ERROR));
        $paymentDetailErrors = PaymentDetails::validateNormalized($paymentDetails);
        if ($paymentDetailErrors !== []) {
            return back()->withErrors($paymentDetailErrors)->withInput();
        }

        $precheckCase = FuneralCase::whereKey($validated['funeral_case_id'])
            ->whereIn('branch_id', $this->paymentWriteBranchIds($request->user()))
            ->first();

        if ($precheckCase) {
            if ($this->replayedPayment($validated['submission_id'] ?? null, $submissionHash, $precheckCase)) {
                return redirect()->route('funeral-cases.show', $precheckCase)->with('success', 'This payment submission was already processed.');
            }
            $amountPaid = round((float) $validated['amount_paid'], 2);
            $remainingBalance = round((float) $precheckCase->balance_amount, 2);
            $paidAt = Carbon::parse($validated['paid_at']);

            if ($amountPaid > $remainingBalance) {
                $message = 'Amount cannot exceed the remaining balance of ₱' . number_format($remainingBalance, 2) . '.';
                return back()->withErrors([
                    'amount_paid' => $message,
                    'payment' => $message,
                ])->withInput();
            }

            $identifierChecks = [];
            if ($receiptOrNo !== null) {
                $identifierChecks[] = ['receipt_or_no', $receiptOrNo];
            }
            foreach (['reference_number', 'approval_code'] as $identifierType) {
                $value = trim((string) ($paymentDetails[$identifierType] ?? ''));
                if ($value !== '') $identifierChecks[] = [$identifierType, $value];
            }
            foreach ($identifierChecks as [$identifierType, $value]) {
                $duplicate = PaymentIdentifier::duplicate(
                    (int) $precheckCase->branch_id,
                    $identifierType,
                    $value,
                    $paymentDetails['cashless_type'] ?? null,
                    PaymentIdentifier::providerFor($paymentDetails),
                );
                if ($duplicate) {
                    return back()->withErrors([
                        $identifierType => PaymentIdentifier::duplicateMessage($identifierType, $duplicate),
                    ])->withInput();
                }
            }
        }

        $saved = false;
        $savedPaymentId = null;
        $attempt = 0;
        $maxRetries = 3;

        do {
            $attempt++;

            try {
                DB::transaction(function () use ($validated, $paymentDetails, $receiptOrNo, $submissionHash, &$savedPaymentId) {
                $user = auth()->user();
                $branchScopeIds = $this->paymentWriteBranchIds($user);
                $branchId = FuneralCase::query()
                    ->whereKey($validated['funeral_case_id'])
                    ->whereIn('branch_id', $branchScopeIds)
                    ->value('branch_id');
                if (! $branchId) abort(404);
                Branch::whereKey((int) $branchId)->lockForUpdate()->firstOrFail();

                $funeralCase = FuneralCase::whereKey($validated['funeral_case_id'])
                    ->whereIn('branch_id', $branchScopeIds)
                    ->where(function ($scopeQuery) {
                        $scopeQuery->where('entry_source', 'MAIN')
                            ->orWhereNull('entry_source');
                    })
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($existing = $this->replayedPayment($validated['submission_id'] ?? null, $submissionHash, $funeralCase)) {
                    $savedPaymentId = $existing->id;
                    return;
                }

                $identifierChecks = [];
                if ($receiptOrNo !== null) {
                    $identifierChecks[] = ['receipt_or_no', $receiptOrNo];
                }
                foreach (['reference_number', 'approval_code'] as $identifierType) {
                    $value = trim((string) ($paymentDetails[$identifierType] ?? ''));
                    if ($value !== '') $identifierChecks[] = [$identifierType, $value];
                }
                foreach ($identifierChecks as [$identifierType, $value]) {
                    $duplicate = PaymentIdentifier::duplicate(
                        (int) $funeralCase->branch_id,
                        $identifierType,
                        $value,
                        $paymentDetails['cashless_type'] ?? null,
                        PaymentIdentifier::providerFor($paymentDetails),
                    );
                    if ($duplicate) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            $identifierType => PaymentIdentifier::duplicateMessage($identifierType, $duplicate),
                        ]);
                    }
                }

                if (($funeralCase->entry_source ?? 'MAIN') === 'OTHER_BRANCH') {
                    throw new \RuntimeException('Other-branch cases must already be fully paid before encoding. Payment updates are blocked.');
                }

                if ((float) $funeralCase->total_amount <= 0) {
                    throw new \RuntimeException('Total amount must be greater than zero before recording payment.');
                }

                $amountPaid = round((float) $validated['amount_paid'], 2);
                $totalDue = round((float) $funeralCase->total_amount, 2);
                $currentPaid = round((float) $funeralCase->total_paid, 2);
                $currentBalance = round((float) $funeralCase->balance_amount, 2);
                $currentStatus = $funeralCase->payment_status;
                $newPaid = round($currentPaid + $amountPaid, 2);

                if ($currentStatus === 'PAID' || $currentBalance <= 0) {
                    throw new \RuntimeException('This case is already fully paid. Additional payments are blocked.');
                }

                if ($amountPaid > $currentBalance || $newPaid > $totalDue) {
                    $allowed = round(max($currentBalance, 0), 2);
                    Log::warning('payment.overpayment_attempted', [
                        'case_id'   => $funeralCase->id,
                        'attempted' => $amountPaid,
                        'remaining' => $allowed,
                        'user_id'   => $user->id,
                    ]);
                    throw new \RuntimeException("Payment exceeds balance. Remaining allowed amount is {$allowed}.");
                }

                $totals = $funeralCase->paymentTotalsFor($newPaid);
                $balance = $totals['balance_amount'];
                $status = $totals['payment_status'];
                $paidAt = Carbon::parse($validated['paid_at']);

                $isCashless = $paymentDetails['payment_method'] === 'cashless';
                $transferDateTime = $isCashless && !empty($validated['transfer_datetime'])
                    ? Carbon::parse($validated['transfer_datetime'])
                    : null;

                $payment = Payment::create([
                    'submission_id' => $validated['submission_id'] ?? null,
                    'submission_hash' => isset($validated['submission_id']) ? $submissionHash : null,
                    'funeral_case_id'  => $funeralCase->id,
                    'branch_id'        => $funeralCase->branch_id,
                    'payment_record_no' => Payment::nextPaymentRecordNumber($paidAt),
                    'accounting_reference_no' => $receiptOrNo,
                    'receipt_or_no'     => $receiptOrNo,
                    'method'           => $paymentDetails['legacy_method'],
                    'payment_mode'     => $paymentDetails['legacy_payment_mode'],
                    'payment_method'   => $paymentDetails['payment_method'],
                    'cashless_type'    => $paymentDetails['cashless_type'],
                    'bank_name'        => $paymentDetails['bank_name'],
                    'other_bank_name'  => $paymentDetails['other_bank_name'],
                    'wallet_provider'  => $paymentDetails['wallet_provider'],
                    'account_name'     => $paymentDetails['account_name'],
                    'mobile_number'    => $paymentDetails['mobile_number'],
                    'reference_number' => $paymentDetails['reference_number'],
                    'approval_code'    => $paymentDetails['approval_code'],
                    'card_type'        => $paymentDetails['card_type'],
                    'terminal_provider' => $paymentDetails['terminal_provider'],
                    'payment_channel'  => $paymentDetails['payment_channel'],
                    'payment_notes'    => $paymentDetails['payment_notes'],
                    'bank_or_channel'  => $paymentDetails['legacy_bank_or_channel'],
                    'other_bank_or_channel' => $paymentDetails['legacy_other_bank_or_channel'],
                    'transaction_reference_no' => $paymentDetails['legacy_transaction_reference_no'],
                    'sender_name'      => $paymentDetails['legacy_sender_name'],
                    'transfer_datetime' => $transferDateTime,
                    'amount'           => $amountPaid,
                    'balance_after_payment'        => $balance,
                    'payment_status_after_payment' => $status,
                    'paid_date'    => $paidAt->toDateString(),
                    'paid_at'      => $paidAt,
                    'received_by'  => $validated['received_by'] ?? null,
                    'encoded_by'   => $user->id,
                    'recorded_by'  => $user->id,
                    'remarks'      => $validated['remarks'] ?? null,
                    'status'       => 'POSTED',
                ]);

                $payment->update([
                    'receipt_number' => $payment->payment_record_no,
                ]);
                $savedPaymentId = $payment->id;

                \App\Support\Payments\PaymentProof::claim(request(), $payment, 'payment');
                $funeralCase->update($totals);

                AuditLogger::log(
                    'payment.created',
                    'create',
                    'payment',
                    $payment->id,
                    [
                        'case_id' => $funeralCase->id,
                        'amount' => $amountPaid,
                        'payment_record_no' => $payment->payment_record_no,
                        'receipt_or_no' => $payment->receipt_or_no,
                        'payment_method' => $payment->payment_method,
                        'cashless_type' => $payment->cashless_type,
                        'bank_name' => $payment->bank_name,
                        'wallet_provider' => $payment->wallet_provider,
                        'reference_number' => $payment->reference_number,
                        'approval_code' => $payment->approval_code,
                        'payment_channel' => $payment->payment_channel,
                        'payment_status_after' => $status,
                        'entry_source' => $funeralCase->entry_source,
                        'changes' => [
                            ['field' => 'total_paid', 'before' => $currentPaid, 'after' => $newPaid],
                            ['field' => 'balance_amount', 'before' => $currentBalance, 'after' => $balance],
                            ['field' => 'payment_status', 'before' => $currentStatus, 'after' => $status],
                        ],
                    ],
                    (int) $funeralCase->branch_id,
                    null,
                    'success',
                    null,
                    'Payment recorded'
                );
                });

                $saved = true;
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt < $maxRetries && $this->isDuplicatePaymentRecordNoException($e)) {
                    Log::warning('payment.payment_record_no_collision', [
                        'case_id' => $validated['funeral_case_id'] ?? null,
                        'attempt' => $attempt,
                    ]);
                    continue;
                }

                $caseId = $validated['funeral_case_id'] ?? null;
                Log::error('payment.store_failed', [
                    'case_id' => $caseId,
                    'amount'  => $validated['amount_paid'] ?? null,
                    'error'   => $e->getMessage(),
                ]);
                AuditLogger::log(
                    'payment.create_failed',
                    'create',
                    'payment',
                    null,
                    [
                        'case_id' => $caseId,
                        'amount' => $validated['amount_paid'] ?? null,
                        'error' => $e->getMessage(),
                    ],
                    null,
                    null,
                    'failed',
                    $e->getMessage(),
                    'Payment record failed'
                );

                return back()->withErrors(['payment' => 'Failed to save payment record. Please try again.'])->withInput();
            } catch (\RuntimeException $e) {
            $caseId = $validated['funeral_case_id'] ?? null;
            Log::error('payment.store_failed', [
                'case_id' => $caseId,
                'amount'  => $validated['amount_paid'] ?? null,
                'error'   => $e->getMessage(),
            ]);
            AuditLogger::log(
                'payment.create_failed',
                'create',
                'payment',
                null,
                [
                    'case_id' => $caseId,
                    'amount' => $validated['amount_paid'] ?? null,
                    'error' => $e->getMessage(),
                ],
                null,
                null,
                'failed',
                $e->getMessage(),
                'Payment record failed'
            );
            return back()->withErrors(['payment' => $e->getMessage()])->withInput();
            }
        } while ($attempt < $maxRetries);

        if (! $saved) {
            return back()->withErrors(['payment' => 'Failed to save payment record. Please try again.'])->withInput();
        }

        if ($request->boolean('return_to_case')) {
            $routeParams = ['funeral_case' => $validated['funeral_case_id']];
            if (!empty($validated['return_to'])) {
                $routeParams['return_to'] = $validated['return_to'];
            }

            return redirect()
                ->route('funeral-cases.show', $routeParams)
                ->with('success', 'Payment recorded successfully.');
        }

        $savedPayment = Payment::with([
            'funeralCase.client',
            'funeralCase.deceased',
            'funeralCase.package',
        ])->findOrFail($savedPaymentId);
        $savedCase = $savedPayment->funeralCase;

        return redirect()->route('payments.index')->with([
            'success' => 'Payment recorded successfully. You can print the Payment Record for the client’s reference.',
            'payment_summary_id' => $savedPaymentId,
            'payment_success' => [
                'id' => $savedPayment->id,
                'record_no' => $savedPayment->display_payment_record_no,
                'case_code' => $savedCase->case_code,
                'client' => $savedCase->client?->full_name,
                'deceased' => $savedCase->deceased?->full_name,
                'amount' => (float) $savedPayment->amount,
                'balance' => (float) $savedPayment->balance_after_payment,
                'method' => PaymentDetails::label($savedPayment),
                'received_at' => $savedPayment->paid_at->format('M d, Y h:i A'),
                'package' => $savedCase->package_name_snapshot
                    ?: $savedCase->custom_package_name
                    ?: $savedCase->service_package
                    ?: $savedCase->package?->name
                    ?: '—',
                'total' => (float) $savedCase->total_amount,
                'payment_status' => $savedPayment->payment_status_after_payment,
            ],
        ]);
    }

    private function replayedPayment(?string $submissionId, string $hash, FuneralCase $case): ?Payment
    {
        if (! $submissionId) {
            return null; // Legacy callers without an identity cannot be inferred to be duplicate payments.
        }
        $payment = Payment::withTrashed()->where('branch_id', $case->branch_id)->where('submission_id', $submissionId)->first();
        if ($payment && ((int) $payment->funeral_case_id !== (int) $case->id || ! hash_equals((string) $payment->submission_hash, $hash))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['submission_id' => 'This submission was already used for different payment details. Reload the payment form.']);
        }
        return $payment;
    }

    public function updateReceipt(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        $validated = $request->validate([
            'receipt_or_no' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9 \/\-]+$/'],
        ], [
            'receipt_or_no.required' => 'Receipt / OR number is required.',
            'receipt_or_no.min' => 'Receipt / OR number must be 3 to 50 characters.',
            'receipt_or_no.max' => 'Receipt / OR number must be 3 to 50 characters.',
            'receipt_or_no.regex' => 'Receipt / OR number may contain letters, numbers, spaces, hyphens, and slashes only.',
        ]);

        $receiptOrNo = trim($validated['receipt_or_no']);
        $before = DB::transaction(function () use ($payment, $receiptOrNo) {
            Branch::whereKey((int) $payment->branch_id)->lockForUpdate()->firstOrFail();
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $duplicate = PaymentIdentifier::duplicate(
                (int) $lockedPayment->branch_id,
                'receipt_or_no',
                $receiptOrNo,
                exceptPaymentId: (int) $lockedPayment->id,
            );

            if ($duplicate) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'receipt_or_no' => PaymentIdentifier::duplicateMessage('receipt_or_no', $duplicate),
                ]);
            }

            $previousReceipt = $lockedPayment->receipt_or_no;
            $lockedPayment->update([
                'receipt_or_no' => $receiptOrNo,
                'accounting_reference_no' => $receiptOrNo,
            ]);

            return $previousReceipt;
        });

        AuditLogger::log(
            'payment.receipt_updated',
            'update',
            'payment',
            $payment->id,
            [
                'case_id' => $payment->funeral_case_id,
                'payment_record_no' => $payment->display_payment_record_no,
                'changes' => [
                    ['field' => 'receipt_or_no', 'before' => $before, 'after' => $receiptOrNo],
                ],
            ],
            (int) $payment->branch_id,
            null,
            'success',
            null,
            'Payment receipt updated'
        );

        return back()->with('success', 'Receipt / OR number updated successfully.');
    }

    public function summary(Payment $payment)
    {
        abort_if(auth()->user()?->isOwner(), 403);
        $this->authorize('view', $payment);

        $payment->load([
            'funeralCase.client:id,full_name',
            'funeralCase.deceased:id,full_name',
            'funeralCase.branch:id,branch_code,branch_name,address',
            'funeralCase.package:id,name',
            'recordedBy:id,name',
            'encodedBy:id,name',
        ]);

        return view('staff.payments.summary', compact('payment'));
    }

    public function printHistory(Request $request, FuneralCase $funeralCase)
    {
        abort_if($request->user()?->isOwner(), 403);
        $branchIds = $this->paymentViewBranchIds($request->user());
        abort_unless(in_array((int) $funeralCase->branch_id, $branchIds, true), 403);

        $funeralCase->load([
            'client:id,full_name',
            'deceased:id,full_name',
            'branch:id,branch_code,branch_name,address',
            'payments' => fn ($query) => $query
                ->where(fn ($statusQuery) => $statusQuery
                    ->whereNull('status')
                    ->orWhereNotIn('status', ['VOID', 'VOIDED']))
                ->with(['recordedBy:id,name', 'encodedBy:id,name'])
                ->orderBy('paid_at')
                ->orderBy('id'),
        ]);

        return view('staff.payments.history_print', compact('funeralCase'));
    }

    private function isDuplicatePaymentRecordNoException(\Illuminate\Database\QueryException $e): bool
    {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        if ($driverCode !== 1062) {
            return false;
        }

        return str_contains($e->getMessage(), 'payments_payment_record_no_unique')
            || str_contains($e->getMessage(), 'payment_record_no');
    }

    private function recordableCases(array $branchIds): Builder
    {
        return FuneralCase::query()
            ->whereIn('branch_id', $branchIds)
            ->where(fn (Builder $query) => $query->where('entry_source', 'MAIN')->orWhereNull('entry_source'))
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
            ->where('balance_amount', '>', 0);
    }

    private function paymentViewBranchIds($user): array
    {
        if (!$user) {
            abort(403);
        }

        if (($user->role ?? null) === 'owner' || (method_exists($user, 'isMainAdmin') && $user->isMainAdmin())) {
            $ids = Branch::query()
                ->pluck('id')
                ->all();
        } else {
            $ids = array_filter([(int) ($user->branch_id ?? 0)]);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            abort(403);
        }

        return $ids;
    }

    private function paymentWriteBranchIds($user): array
    {
        if (!$user || ($user->role ?? null) !== 'staff' || !$user->branch_id) {
            abort(403);
        }

        return [(int) $user->branch_id];
    }

    private function selectedPaymentBranchIds(array $viewBranchScopeIds, mixed $selectedBranchId): array
    {
        if (count($viewBranchScopeIds) === 1) {
            return array_values($viewBranchScopeIds);
        }

        if (!$selectedBranchId) {
            return $viewBranchScopeIds;
        }

        $selected = (int) $selectedBranchId;
        if (!in_array($selected, $viewBranchScopeIds, true)) {
            abort(403, 'Branch is outside your payment monitoring scope.');
        }

        return [$selected];
    }
}
