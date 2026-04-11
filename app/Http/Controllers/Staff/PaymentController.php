<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $user = auth()->user();
        $mainBranchId = $this->mainBranchIdForPayment($user);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100', "regex:/^[A-Za-z0-9\\s.'-]+$/"],
            'payment_status' => ['nullable', 'in:PARTIAL,UNPAID'],
            'case_status' => ['nullable', 'in:DRAFT,ACTIVE,COMPLETED'],
            'request_date_from' => ['nullable', 'date'],
            'request_date_to' => ['nullable', 'date', 'after_or_equal:request_date_from'],
            'case_id' => ['nullable', 'integer', 'exists:funeral_cases,id'],
            'open_payment' => ['nullable', 'boolean'],
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $preselectCase = null;
        if ($request->filled('case_id')) {
            $preselectCase = FuneralCase::query()
                ->select(['id', 'branch_id', 'client_id', 'case_code'])
                ->with(['client:id,full_name'])
                ->where('branch_id', $mainBranchId)
                ->find($request->integer('case_id'));
        }

        $openCasesQuery = FuneralCase::query()
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
                'created_at',
            ])
            ->with([
                'branch:id,branch_code',
                'client:id,full_name',
                'deceased:id,full_name',
            ])
            ->where('branch_id', $mainBranchId)
            ->where('payment_status', '!=', 'PAID')
            ->where(function ($scopeQuery) {
                $scopeQuery->where('entry_source', 'MAIN')
                    ->orWhereNull('entry_source');
            })
            ->latest();

        // Ensure preselected case appears in the picker even if already paid/filtered out.
        if ($preselectCase) {
            $openCasesQuery->orWhere(function ($q) use ($preselectCase) {
                $q->whereKey($preselectCase->id);
            });
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $openCasesQuery->where(function ($sub) use ($q) {
                $sub->where('case_code', 'like', "%{$q}%")
                    ->orWhereHas('client', function ($q2) use ($q) {
                        $q2->where('full_name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('deceased', function ($q3) use ($q) {
                        $q3->where('full_name', 'like', "%{$q}%");
                    });
            });
        }
        if ($request->filled('payment_status')) {
            $openCasesQuery->where('payment_status', $request->string('payment_status')->toString());
        }
        if ($request->filled('case_status')) {
            $openCasesQuery->where('case_status', $request->string('case_status')->toString());
        }
        if ($request->filled('request_date_from')) {
            $openCasesQuery->whereDate('service_requested_at', '>=', $request->string('request_date_from')->toString());
        }
        if ($request->filled('request_date_to')) {
            $openCasesQuery->whereDate('service_requested_at', '<=', $request->string('request_date_to')->toString());
        }

        $openCases = $openCasesQuery->paginate(20)->withQueryString();

        return view('staff.payments.index', [
            'openCases' => $openCases,
            'mainBranchId' => $mainBranchId,
            'preselectCase' => $preselectCase,
            'autoOpenPayment' => (bool) $request->boolean('open_payment'),
        ]);
    }

    public function void(Request $request, Payment $payment)
    {
        $this->authorize('update', Payment::class);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($payment, $validated) {
                $funeralCase = $payment->funeralCase()->lockForUpdate()->first();
                if (!$funeralCase) {
                    throw new \RuntimeException('Related case not found.');
                }

                if ($payment->status === 'VOID') {
                    throw new \RuntimeException('Payment is already voided.');
                }

                $reason = AuditLogger::requireReason($validated, 'reason');

                $beforeTotalPaid = round((float) $funeralCase->total_paid, 2);
                $beforeBalance = round((float) $funeralCase->balance_amount, 2);
                $beforeStatus = $funeralCase->payment_status;

                // revert payment effect
                $newPaid = round($beforeTotalPaid - (float) $payment->amount, 2);
                $newPaid = max($newPaid, 0);
                $balance = round(max((float) $funeralCase->total_amount - $newPaid, 0), 2);
                $status = $newPaid <= 0 ? 'UNPAID' : ($balance > 0 ? 'PARTIAL' : 'PAID');

                $payment->update([
                    'status' => 'VOID',
                    'void_reason' => $reason,
                ]);

                $funeralCase->update([
                    'total_paid' => $newPaid,
                    'balance_amount' => $balance,
                    'payment_status' => $status,
                ]);

                AuditLogger::log(
                    'payment.voided',
                    'delete',
                    'payment',
                    $payment->id,
                    [
                        'case_id' => $funeralCase->id,
                        'amount' => $payment->amount,
                        'reference_no' => $payment->receipt_number,
                        'reason' => $reason,
                        'changes' => [
                            ['field' => 'payment_status', 'before' => $beforeStatus, 'after' => $status],
                            ['field' => 'total_paid', 'before' => $beforeTotalPaid, 'after' => $newPaid],
                            ['field' => 'balance_amount', 'before' => $beforeBalance, 'after' => $balance],
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
        $this->authorize('viewAny', Payment::class);

        $user = auth()->user();
        $mainBranchId = $this->mainBranchIdForPayment($user);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100', "regex:/^[A-Za-z0-9\\s.'-]+$/"],
            'paid_from' => ['nullable', 'date'],
            'paid_to' => ['nullable', 'date', 'after_or_equal:paid_from'],
            'status_after_payment' => ['nullable', 'in:PAID,PARTIAL,UNPAID'],
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $q = $validated['q'] ?? null;
        $paidFrom = $validated['paid_from'] ?? null;
        $paidTo = $validated['paid_to'] ?? null;
        $statusAfterPayment = $validated['status_after_payment'] ?? null;

        $payments = Payment::query()
            ->select([
                'id',
                'funeral_case_id',
                'branch_id',
                'receipt_number',
                'amount',
                'balance_after_payment',
                'payment_status_after_payment',
                'paid_date',
                'paid_at',
                'recorded_by',
            ])
            ->with([
                'funeralCase:id,branch_id,client_id,deceased_id,case_code',
                'funeralCase.branch:id,branch_code',
                'funeralCase.client:id,full_name',
                'funeralCase.deceased:id,full_name',
                'recordedBy:id,name',
            ])
            ->where('branch_id', $mainBranchId)
            ->whereHas('funeralCase', function ($query) use ($mainBranchId) {
                $query->where('branch_id', $mainBranchId)
                    ->where(function ($scopeQuery) {
                        $scopeQuery->where('entry_source', 'MAIN')
                            ->orWhereNull('entry_source');
                    });
            })
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->whereHas('funeralCase', function ($caseQuery) use ($q) {
                        $caseQuery->where('case_code', 'like', "%{$q}%");
                    })->orWhereHas('funeralCase.client', function ($clientQuery) use ($q) {
                        $clientQuery->where('full_name', 'like', "%{$q}%");
                    })->orWhereHas('funeralCase.deceased', function ($deceasedQuery) use ($q) {
                        $deceasedQuery->where('full_name', 'like', "%{$q}%");
                    });
                });
            })
            ->when($paidFrom, fn ($query) => $query->whereDate('paid_at', '>=', $paidFrom))
            ->when($paidTo, fn ($query) => $query->whereDate('paid_at', '<=', $paidTo))
            ->when($statusAfterPayment, fn ($query) => $query->where('payment_status_after_payment', $statusAfterPayment))
            ->latest('paid_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('staff.payments.history', [
            'payments' => $payments,
            'q' => $q,
            'paidFrom' => $paidFrom,
            'paidTo' => $paidTo,
            'statusAfterPayment' => $statusAfterPayment,
            'mainBranchId' => $mainBranchId,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Payment::class);

        $validated = $request->validate([
            'funeral_case_id' => 'required|exists:funeral_cases,id',
            'paid_at' => 'required|date',
            'amount_paid' => 'required|numeric|min:0.01',
            'return_to_case' => 'nullable|boolean',
            'void' => 'nullable|boolean',
            'void_reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $user = auth()->user();
                $mainBranchId = $this->mainBranchIdForPayment($user);

                $funeralCase = FuneralCase::whereKey($validated['funeral_case_id'])
                    ->where('branch_id', $mainBranchId)
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

                if ($newPaid > $totalDue) {
                    $allowed = round(max($totalDue - $currentPaid, 0), 2);
                    throw new \RuntimeException("Payment exceeds balance. Remaining allowed amount is {$allowed}.");
                }

                $balance = round(max($totalDue - $newPaid, 0), 2);
                $status = $newPaid <= 0 ? 'UNPAID' : ($balance > 0 ? 'PARTIAL' : 'PAID');
                $paidAt = Carbon::parse($validated['paid_at']);

                $payment = Payment::create([
                    'funeral_case_id' => $funeralCase->id,
                    'branch_id' => $funeralCase->branch_id,
                    'method' => 'CASH',
                    'amount' => $amountPaid,
                    'balance_after_payment' => $balance,
                    'payment_status_after_payment' => $status,
                    'paid_date' => $paidAt->toDateString(),
                    'paid_at' => $paidAt,
                    'recorded_by' => $user->id,
                    'status' => 'VALID',
                ]);

                $payment->update([
                    'receipt_number' => Payment::buildReceiptNumber($payment->id, $paidAt),
                ]);

                $funeralCase->update([
                    'payment_status' => $status,
                    'paid_at' => $paidAt,
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
                        'receipt_number' => $payment->receipt_number,
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
        } catch (\RuntimeException $e) {
            $caseId = $validated['funeral_case_id'] ?? null;
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

        if ($request->boolean('return_to_case')) {
            return redirect()
                ->route('funeral-cases.show', $validated['funeral_case_id'])
                ->with('success', 'Payment recorded successfully.');
        }

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }

    private function mainBranchIdForPayment($user): int
    {
        if (!$user || !$user->branch_id) {
            abort(403);
        }

        return (int) $user->branch_id;
    }
}
