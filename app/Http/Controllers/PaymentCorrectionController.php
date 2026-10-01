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
        $requests = PaymentCorrectionRequest::with(['payment.funeralCase', 'requester', 'reviewer', 'replacementPayment'])
            ->where('branch_id', $request->user()->branch_id)->latest()->paginate(20);
        return view('admin.payment-corrections.index', compact('requests'));
    }

    public function store(Request $request, Payment $payment)
    {
        abort_unless($request->user()->role === 'staff' && (int) $request->user()->branch_id === (int) $payment->branch_id, 403);
        abort_if(in_array($payment->status, ['VOID', 'VOIDED'], true), 422, 'A voided payment cannot be corrected.');
        abort_if($payment->correctionRequests()->where('status', 'PENDING')->exists(), 422, 'This payment already has a pending correction request.');

        $validated = $request->validate([
            'correction_type' => ['required', Rule::in(['CORRECT', 'VOID_ONLY'])],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'paid_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'funeral_case_id' => ['nullable', 'integer', 'exists:funeral_cases,id'],
        ]);

        $original = $payment->only(['funeral_case_id', 'amount', 'paid_date', 'paid_at', 'payment_method', 'payment_mode', 'reference_number']);
        $proposed = $validated['correction_type'] === 'CORRECT'
            ? array_filter(collect($validated)->only(['funeral_case_id', 'amount', 'paid_date', 'payment_method', 'reference_number'])->all(), fn ($value) => $value !== null && $value !== '')
            : null;

        $correction = PaymentCorrectionRequest::create([
            'payment_id' => $payment->id,
            'branch_id' => $payment->branch_id,
            'requested_by' => $request->user()->id,
            'correction_type' => $validated['correction_type'],
            'original_values' => $original,
            'proposed_values' => $proposed,
            'reason' => $validated['reason'],
        ]);
        AuditLogger::log('PAYMENT_CORRECTION_REQUESTED', 'CREATE', 'payment_correction_request', $correction->id, ['payment_id' => $payment->id], $payment->branch_id);
        return back()->with('success', 'Payment correction request submitted for Branch Admin review.');
    }

    public function reject(Request $request, PaymentCorrectionRequest $correction)
    {
        $this->authorizeReviewer($request, $correction);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        DB::transaction(function () use ($request, $correction, $validated) {
            $locked = PaymentCorrectionRequest::lockForUpdate()->findOrFail($correction->id);
            abort_unless($locked->status === 'PENDING', 409, 'This request has already been resolved.');
            $locked->update(['status' => 'REJECTED', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_reason' => $validated['reason']]);
            AuditLogger::log('PAYMENT_CORRECTION_REJECTED', 'UPDATE', 'payment_correction_request', $locked->id, ['payment_id' => $locked->payment_id], $locked->branch_id);
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
            $payment->update(['status' => 'VOIDED', 'void_reason' => $locked->reason]);
            $replacement = null;

            if ($locked->correction_type === 'CORRECT') {
                $values = $locked->proposed_values ?? [];
                $newCaseId = (int) ($values['funeral_case_id'] ?? $payment->funeral_case_id);
                $newCase = \App\Models\FuneralCase::withoutGlobalScope('branch_scope')->lockForUpdate()->findOrFail($newCaseId);
                abort_unless((int) $newCase->branch_id === (int) $locked->branch_id, 422, 'The corrected case must belong to the same branch.');
                $replacement = $payment->replicate(['receipt_number', 'payment_record_no', 'status', 'void_reason']);
                $replacement->fill($values);
                $replacement->funeral_case_id = $newCase->id;
                $replacement->branch_id = $newCase->branch_id;
                $replacement->replaces_payment_id = $payment->id;
                $replacement->status = 'POSTED';
                $replacement->recorded_by = $payment->recorded_by;
                $replacement->payment_record_no = Payment::nextPaymentRecordNumber(now());
                $replacement->save();
                $newCase->recalculatePaymentTotals();
            }
            $oldCase->recalculatePaymentTotals();
            $locked->update(['status' => 'APPROVED', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'replacement_payment_id' => $replacement?->id]);
            AuditLogger::log('PAYMENT_CORRECTION_APPROVED', 'UPDATE', 'payment_correction_request', $locked->id, ['payment_id' => $payment->id, 'replacement_payment_id' => $replacement?->id], $locked->branch_id);
        }, 3);
        return back()->with('success', 'Payment correction approved and financial totals recalculated.');
    }

    private function authorizeReviewer(Request $request, PaymentCorrectionRequest $correction): void
    {
        abort_unless($request->user()->isBranchAdmin() && (int) $request->user()->branch_id === (int) $correction->branch_id, 403);
    }
}
