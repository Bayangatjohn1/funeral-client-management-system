<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Support\AuditLogger;
use App\Support\Payments\PaymentDetails;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
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
            'q' => ['nullable', 'string', 'max:100', "regex:/^[A-Za-z0-9\\s.'-]+$/"],
            'date_preset' => ['nullable', 'in:all,today,week,month,year,custom'],
            'paid_from' => ['nullable', 'date'],
            'paid_to' => ['nullable', 'date', 'after_or_equal:paid_from'],
            'branch_id' => ['nullable', 'string', 'max:20'],
            'payment_status' => ['nullable', 'in:PAID,PARTIAL,UNPAID,WITH_BALANCE,HAS_PAYMENT'],
            'case_scope' => ['nullable', 'in:active_completed'],
            'case_status' => ['nullable', 'in:DRAFT,ACTIVE,COMPLETED'],
            'payment_method' => ['nullable', 'in:cash,cashless,bank_transfer'],
            'status_after_payment' => ['nullable', 'in:PAID,PARTIAL,UNPAID'],
            'sort' => ['nullable', 'in:asc,desc'],
            'tab' => ['nullable', 'in:summary,transactions'],
            'record_payment' => ['nullable', 'boolean'],
            'case_id' => ['nullable', 'integer', 'exists:funeral_cases,id'],
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, and hyphens only.',
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
        $activeTab = $validated['tab'] ?? 'summary';
        $canRecordPayment = $user?->isStaff() && $user->can('create', Payment::class);

        $openCases = collect();
        $preselectCase = null;
        if ($canRecordPayment) {
            $openCases = FuneralCase::query()
                ->select(['id', 'branch_id', 'client_id', 'deceased_id', 'package_id', 'case_code', 'package_name_snapshot', 'custom_package_name', 'service_package', 'total_amount', 'total_paid', 'balance_amount'])
                ->with([
                    'client:id,full_name',
                    'deceased:id,full_name',
                    'package:id,name',
                ])
                ->whereIn('branch_id', $viewBranchScopeIds)
                ->where(function ($scopeQuery) {
                    $scopeQuery->where('entry_source', 'MAIN')->orWhereNull('entry_source');
                })
                ->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
                ->where('balance_amount', '>', 0)
                ->latest()
                ->get();

            if (!empty($validated['case_id'])) {
                $preselectCase = $openCases->firstWhere('id', (int) $validated['case_id']);
            }
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

        $applyCaseSearch = function ($query) use ($q, $applyValidPayment) {
            $query->where(function ($sub) use ($q, $applyValidPayment) {
                $sub->where('case_code', 'like', "%{$q}%")
                    ->orWhereHas('client', fn ($c) => $c->where('full_name', 'like', "%{$q}%"))
                    ->orWhereHas('deceased', fn ($c) => $c->where('full_name', 'like', "%{$q}%"))
                    ->orWhereHas('payments', function ($paymentQuery) use ($q, $applyValidPayment) {
                        $applyValidPayment($paymentQuery);
                        $paymentQuery->where(function ($referenceQuery) use ($q) {
                            $referenceQuery->where('payment_record_no', 'like', "%{$q}%")
                                ->orWhere('receipt_number', 'like', "%{$q}%")
                                ->orWhere('receipt_or_no', 'like', "%{$q}%")
                                ->orWhere('accounting_reference_no', 'like', "%{$q}%")
                                ->orWhere('transaction_reference_no', 'like', "%{$q}%")
                                ->orWhere('reference_number', 'like', "%{$q}%");
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
                'payments' => function ($query) use ($applyValidPayment) {
                    $applyValidPayment($query);
                    $query->select([
                            'id',
                            'funeral_case_id',
                            'branch_id',
                            'receipt_number',
                            'payment_record_no',
                            'accounting_reference_no',
                            'receipt_or_no',
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
                        ->with(['recordedBy:id,name', 'encodedBy:id,name'])
                        ->orderBy('paid_at')
                        ->orderBy('id');
                },
            ])
            ->withCount(['payments' => $applyValidPayment])
            ->withMax(['payments' => $applyValidPayment], 'paid_at')
            ->orderBy('payments_max_paid_at', $sort)
            ->orderBy('id', $sort);

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

        $paymentCases = $paymentCasesQuery
            ->paginate(20)
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
                ->with(['recordedBy:id,name', 'encodedBy:id,name'])
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
            ->orderBy('payments_max_paid_at', $sort)
            ->orderBy('id', $sort)
            ->paginate(20, ['*'], 'transactions_page')
            ->withQueryString();

        return view('staff.payments.history', [
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
            'autoOpenPayment' => $canRecordPayment && ($request->boolean('record_payment') || $preselectCase !== null),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Payment::class);

        $validated = $request->validate(array_merge([
            'funeral_case_id' => ['required', 'exists:funeral_cases,id'],
            'paid_at' => PaymentDetails::dateRules(),
            'amount_paid' => PaymentDetails::amountRules(),
            'receipt_or_no' => ['nullable', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9 \\/\\-]+$/'],
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
        $paymentDetailErrors = PaymentDetails::validateNormalized($paymentDetails);
        if ($paymentDetailErrors !== []) {
            return back()->withErrors($paymentDetailErrors)->withInput();
        }

        $precheckCase = FuneralCase::whereKey($validated['funeral_case_id'])
            ->whereIn('branch_id', $this->paymentWriteBranchIds($request->user()))
            ->first();

        if ($precheckCase) {
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

            if ($precheckCase->created_at && $paidAt->lt($precheckCase->created_at)) {
                return back()->withErrors([
                    'paid_at' => 'Date received cannot be before the case creation date.',
                ])->withInput();
            }

            if ($receiptOrNo && Payment::query()
                ->where('branch_id', $precheckCase->branch_id)
                ->where('receipt_or_no', $receiptOrNo)
                ->where(function ($statusQuery) {
                    $statusQuery->whereNull('status')->orWhereNotIn('status', ['VOID', 'VOIDED']);
                })
                ->exists()) {
                return back()->withErrors([
                    'receipt_or_no' => 'This Receipt / OR number is already assigned to another payment in this branch.',
                ])->withInput();
            }
        }

        $saved = false;
        $savedPaymentId = null;
        $attempt = 0;
        $maxRetries = 3;

        do {
            $attempt++;

            try {
                DB::transaction(function () use ($validated, $paymentDetails, $receiptOrNo, &$savedPaymentId) {
                $user = auth()->user();
                $branchScopeIds = $this->paymentWriteBranchIds($user);

                $funeralCase = FuneralCase::whereKey($validated['funeral_case_id'])
                    ->whereIn('branch_id', $branchScopeIds)
                    ->where(function ($scopeQuery) {
                        $scopeQuery->where('entry_source', 'MAIN')
                            ->orWhereNull('entry_source');
                    })
                    ->lockForUpdate()
                    ->firstOrFail();

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

                $balance = round(max($totalDue - $newPaid, 0), 2);
                $status = $newPaid <= 0 ? 'UNPAID' : ($balance > 0 ? 'PARTIAL' : 'PAID');
                $paidAt = Carbon::parse($validated['paid_at']);

                $isCashless = $paymentDetails['payment_method'] === 'cashless';
                $transferDateTime = $isCashless && !empty($validated['transfer_datetime'])
                    ? Carbon::parse($validated['transfer_datetime'])
                    : null;

                $payment = Payment::create([
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

                $funeralCase->update([
                    'payment_status' => $status,
                    'total_paid' => $newPaid,
                    'balance_amount' => $balance,
                ]);

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

        return redirect()->route('payments.index')->with([
            'success' => 'Payment recorded successfully. You can print the Payment Summary for the client’s reference.',
            'payment_summary_id' => $savedPaymentId,
        ]);
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
        $duplicate = Payment::query()
            ->where('branch_id', $payment->branch_id)
            ->where('receipt_or_no', $receiptOrNo)
            ->where('id', '!=', $payment->id)
            ->where(function ($statusQuery) {
                $statusQuery->whereNull('status')->orWhereNotIn('status', ['VOID', 'VOIDED']);
            })
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'receipt_or_no' => 'This Receipt / OR number is already assigned to another payment in this branch.',
            ]);
        }

        $before = $payment->receipt_or_no;
        $payment->update([
            'receipt_or_no' => $receiptOrNo,
            'accounting_reference_no' => $receiptOrNo,
        ]);

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

    private function paymentViewBranchIds($user): array
    {
        if (!$user) {
            abort(403);
        }

        if (($user->role ?? null) === 'owner' || (method_exists($user, 'isMainAdmin') && $user->isMainAdmin())) {
            $ids = Branch::query()
                ->where('is_active', true)
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
