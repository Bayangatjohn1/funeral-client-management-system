<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentCorrectionRequest;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentCorrectionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isBranchAdmin(), 403);
        return redirect()->route('admin.payments.index', ['tab' => 'corrections']);
    }

    public function store(Request $request, Payment $payment)
    {
        return DB::transaction(function () use ($request, $payment) {
        $payment = Payment::lockForUpdate()->findOrFail($payment->id);
        abort_unless($request->user()->role === 'staff' && (int) $request->user()->branch_id === (int) $payment->branch_id, 403);
        abort_if($payment->funeralCase?->entry_source === 'OTHER_BRANCH', 422, 'Administrative other-branch reports are locked for payment updates.');
        abort_if(in_array($payment->status, ['VOID', 'VOIDED'], true), 422, 'A voided payment cannot be corrected.');
        abort_if($payment->correctionRequests()->where('status', 'PENDING')->exists(), 422, 'This payment already has a pending correction request.');

        $validated = $request->validate([
            'correction_type' => ['required', Rule::in(['CORRECT', 'VOID_ONLY'])],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'amount' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            'paid_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'payment_method' => ['nullable', Rule::in(['cash', 'cashless', 'bank_transfer'])],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'funeral_case_id' => ['nullable', 'integer', Rule::exists('funeral_cases', 'id')->where('branch_id', $payment->branch_id)],
        ]);

        $original = $payment->only(['funeral_case_id', 'amount', 'paid_date', 'paid_at', 'payment_method', 'payment_mode', 'reference_number']);
        $proposed = $validated['correction_type'] === 'CORRECT'
            ? array_filter(collect($validated)->only(['funeral_case_id', 'amount', 'paid_date', 'payment_method', 'reference_number'])->all(), fn ($value) => $value !== null && $value !== '')
            : null;

        if ($proposed !== null) {
            if (isset($proposed['paid_date'])) {
                // Reports use paid_at; keep the original payment time for date-only corrections.
                $proposed['paid_at'] = $proposed['paid_date'].' '.($payment->paid_at?->format('H:i:s') ?? '00:00:00');
            }
            $changed = collect($proposed)->except('paid_at')->contains(function ($value, $field) use ($original) {
                if ($field === 'amount') return round((float) $value, 2) !== round((float) ($original[$field] ?? 0), 2);
                if ($field === 'paid_date') return $value !== ($original['paid_at'] ?? $original['paid_date'])?->format('Y-m-d');
                return (string) $value !== (string) ($original[$field] ?? '');
            });
            if (!$changed) {
                throw \Illuminate\Validation\ValidationException::withMessages(['correction_type' => 'Change at least one payment detail before requesting a correction.']);
            }
        }

        $correction = PaymentCorrectionRequest::create([
            'payment_id' => $payment->id,
            'branch_id' => $payment->branch_id,
            'requested_by' => $request->user()->id,
            'correction_type' => $validated['correction_type'],
            'original_values' => $original,
            'proposed_values' => $proposed,
            'reason' => $validated['reason'],
        ]);
        AuditLogger::log('PAYMENT_CORRECTION_REQUESTED', 'CREATE', 'payment_correction_request', $correction->id, $this->auditDetails($correction), $payment->branch_id);
        return back()->with('success', 'Payment correction request submitted for Branch Admin review.');
        }, 3);
    }

    public function reject(Request $request, PaymentCorrectionRequest $correction)
    {
        $this->authorizeReviewer($request, $correction);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        DB::transaction(function () use ($request, $correction, $validated) {
            $locked = PaymentCorrectionRequest::lockForUpdate()->findOrFail($correction->id);
            abort_unless($locked->status === 'PENDING', 409, 'This request has already been resolved.');
            $locked->update(['status' => 'REJECTED', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_reason' => $validated['reason']]);
            AuditLogger::log('PAYMENT_CORRECTION_REJECTED', 'UPDATE', 'payment_correction_request', $locked->id, $this->auditDetails($locked), $locked->branch_id);
        });
        return back()->with('success', 'Correction request rejected; the original payment remains posted.');
    }

    public function approve(Request $request, PaymentCorrectionRequest $correction)
    {
        $this->authorizeReviewer($request, $correction);
        DB::transaction(function () use ($request, $correction) {
            $locked = PaymentCorrectionRequest::lockForUpdate()->findOrFail($correction->id);
            abort_unless($locked->status === 'PENDING', 409, 'This request has already been resolved.');
            abort_if((int) $locked->requested_by === (int) $request->user()->id, 403, 'You cannot approve your own request.');

            $payment = Payment::withoutGlobalScope('branch_scope')->lockForUpdate()->findOrFail($locked->payment_id);
            abort_if(in_array($payment->status, ['VOID', 'VOIDED'], true), 409, 'The original payment is already voided.');
            $oldCase = $payment->funeralCase()->lockForUpdate()->firstOrFail();
            abort_if($oldCase->entry_source === 'OTHER_BRANCH', 422, 'Administrative other-branch reports are locked for payment updates.');
            $payment->update(['status' => 'VOIDED', 'void_reason' => $locked->reason]);
            $replacement = null;

            if ($locked->correction_type === 'CORRECT') {
                $values = $locked->proposed_values ?? [];
                $newCaseId = (int) ($values['funeral_case_id'] ?? $payment->funeral_case_id);
                $newCase = \App\Models\FuneralCase::withoutGlobalScope('branch_scope')->lockForUpdate()->findOrFail($newCaseId);
                abort_unless((int) $newCase->branch_id === (int) $locked->branch_id, 422, 'The corrected case must belong to the same branch.');
                abort_if($newCase->entry_source === 'OTHER_BRANCH', 422, 'Administrative other-branch reports are locked for payment updates.');
                $posted = $newCase->payments()->where(function ($query) {
                    $query->whereNull('status')->orWhereNotIn('status', ['VOID', 'VOIDED']);
                })->sum('amount');
                $replacementCents = (int) round((float) ($values['amount'] ?? $payment->amount) * 100);
                $remainingCents = (int) round((float) $newCase->total_amount * 100) - (int) round((float) $posted * 100);
                abort_if($replacementCents <= 0 || $replacementCents > $remainingCents, 422,
                    'The corrected payment exceeds the case remaining balance. Review the correction.');
                $replacement = $payment->replicate(['receipt_number', 'payment_record_no', 'status', 'void_reason', 'submission_id', 'submission_hash']);
                $replacement->fill($values);
                $replacement->funeral_case_id = $newCase->id;
                $replacement->branch_id = $newCase->branch_id;
                $replacement->replaces_payment_id = $payment->id;
                $replacement->status = 'POSTED';
                $replacement->recorded_by = $payment->recorded_by;
                $totals = $newCase->paymentTotalsFor((float) $posted + $replacementCents / 100);
                $replacement->balance_after_payment = $totals['balance_amount'];
                $replacement->payment_status_after_payment = $totals['payment_status'];
                $replacement->payment_record_no = Payment::nextPaymentRecordNumber(now());
                $replacement->save();
                $newCase->recalculatePaymentTotals();
            }
            $oldCase->recalculatePaymentTotals();
            $locked->update(['status' => 'APPROVED', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'replacement_payment_id' => $replacement?->id]);
            AuditLogger::log('PAYMENT_CORRECTION_APPROVED', 'UPDATE', 'payment_correction_request', $locked->id, $this->auditDetails($locked), $locked->branch_id);
        }, 3);
        return back()->with('success', 'Payment correction approved and financial totals recalculated.');
    }

    private function auditDetails(PaymentCorrectionRequest $correction): array
    {
        $payment = $correction->payment;
        $replacement = $correction->replacementPayment;
        $format = function ($field, $value) {
            if ($value === null || $value === '') return 'Not provided';
            if ($field === 'amount') return 'PHP '.number_format((float) $value, 2);
            if (in_array($field, ['paid_date', 'paid_at'], true)) {
                return \Carbon\Carbon::parse($value)->timezone(config('app.timezone'))->format($field === 'paid_date' ? 'M d, Y' : 'M d, Y g:i A');
            }
            return (string) $value;
        };
        $changes = [];
        foreach ($correction->proposed_values ?? [] as $field => $value) {
            if ($field === 'paid_at') continue; // Payment date is displayed once.
            $before = $format($field, $correction->original_values[$field] ?? null);
            $after = $format($field, $value);
            if ($before !== $after) $changes[] = ['field' => $field, 'before' => $before, 'after' => $after];
        }
        return [
            'payment_id' => $correction->payment_id,
            'payment_record_no' => $payment?->display_payment_record_no,
            'case_code' => $payment?->funeralCase?->case_code,
            'correction_type' => $correction->correction_type === 'VOID_ONLY' ? 'Void without replacement' : 'Correct payment',
            'request_status' => $correction->status,
            'requested_by' => $correction->requester?->name,
            'reason' => $correction->reason,
            'reviewed_by' => $correction->reviewer?->name,
            'review_reason' => $correction->review_reason,
            'replacement_payment_id' => $correction->replacement_payment_id,
            'replacement_payment_record_no' => $replacement?->display_payment_record_no,
            'changes' => $changes,
        ];
    }

    private function authorizeReviewer(Request $request, PaymentCorrectionRequest $correction): void
    {
        abort_unless($request->user()->isBranchAdmin() && (int) $request->user()->branch_id === (int) $correction->branch_id, 403);
    }
}
