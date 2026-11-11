@extends('layouts.panel')
@section('page_title', 'Request Payment Correction')
@section('content')
@include('staff.payments._workflow_styles')
<section id="workflowContent" class="pw-content">
<header class="pw-head"><div><small>{{ $payment->display_payment_record_no }}</small><h2>Request Payment Correction</h2></div><button type="button" data-workflow-close aria-label="Close request"><i class="bi bi-x-lg"></i></button></header>
<form class="pw-form" method="POST" action="{{ route('payments.corrections.store', $payment) }}" enctype="multipart/form-data" data-correction-form data-payment-details="{{ json_encode(\App\Support\Payments\PaymentDetails::normalize($payment->getAttributes())) }}" data-original-amount="{{ $payment->amount }}" data-current-balance="{{ $payment->funeralCase->balance_amount }}" data-case-total="{{ $payment->funeralCase->total_amount }}" data-total-paid="{{ $payment->funeralCase->total_paid }}" data-original-case="{{ $payment->funeral_case_id }}">
@csrf
<div class="pw-body">
    <section class="pw-context" aria-label="Original payment summary">
        <div class="pw-context-title"><strong>{{ $payment->funeralCase->case_code }} · {{ $payment->funeralCase->deceased?->full_name }}</strong></div>
        <dl class="pw-meta-grid">
            <div><dt>Client</dt><dd>{{ $payment->funeralCase->client?->full_name }}</dd></div>
            <div><dt>Recorded payment</dt><dd>₱{{ number_format($payment->amount, 2) }} · {{ \App\Support\Payments\PaymentDetails::label($payment) }}<small>{{ ($payment->paid_at ?? $payment->paid_date)?->format('M d, Y g:i A') }}</small></dd></div>
            <div><dt>Receipt / OR No.</dt><dd>{{ $payment->receipt_or_no ?: 'Not recorded' }}</dd></div>
            @if($payment->reference_number || $payment->approval_code || $payment->transaction_reference_no)<div><dt>{{ $payment->approval_code ? 'Approval Code' : 'Transaction Reference' }}</dt><dd>{{ $payment->approval_code ?: ($payment->reference_number ?: $payment->transaction_reference_no) }}</dd></div>@endif
        </dl>
        <div class="pw-context-actions">@include('staff.payments._proof', ['proofPayment' => $payment])</div>
    </section>
    <fieldset class="pw-request-choices">
        <legend>Request type</legend>
        <label>
            <input type="radio" name="correction_type" value="CORRECT" checked required>
            <span><strong>A payment detail is wrong</strong><small>Correct the amount, date, method, or case.</small></span>
        </label>
        <label>
            <input type="radio" name="correction_type" value="VOID_ONLY" required>
            <span><strong>The same payment was entered twice</strong><small>Void the duplicate and keep the valid entry.</small></span>
        </label>
    </fieldset>
    <div data-correction-only>
        <fieldset class="pw-options"><legend>Choose what to correct</legend>
        @foreach(['amount' => 'Amount', 'paid_date' => 'Payment Date', 'receipt_or_no' => 'Receipt / OR No.', 'payment_details' => 'Payment Method / Transaction Reference', 'funeral_case_id' => 'Wrong Case'] as $key => $label)
        <label><input type="checkbox" name="changes[]" value="{{ $key }}"> {{ $label }}</label>
        @endforeach
        </fieldset>
        <div class="pw-grid">
            <label data-change-field="amount" hidden>Correct Amount *<input name="amount" type="number" min="0.01" step="0.01" value="{{ $payment->amount }}" placeholder="e.g., 5000.00" disabled required></label>
            <label data-change-field="paid_date" hidden>Actual Payment Date *<input name="paid_date" type="date" max="{{ today()->toDateString() }}" value="{{ ($payment->paid_at ?? $payment->paid_date)?->format('Y-m-d') }}" disabled required></label>
            <label data-change-field="receipt_or_no" hidden>Correct Receipt / OR No. *<small>Recorded · {{ $payment->receipt_or_no ?: 'Not recorded' }}</small><input name="receipt_or_no" value="{{ $payment->receipt_or_no }}" placeholder="e.g., OR-001234" minlength="3" maxlength="50" pattern="[A-Za-z0-9 \/\-]+" disabled required></label>
        </div>
        <div data-change-field="funeral_case_id" hidden>
            <label>Correct Case *<input type="search" data-correction-case-search data-url="{{ route('payments.corrections.cases') }}" placeholder="Search Case ID, client or deceased" disabled autocomplete="off"></label>
            <input type="hidden" name="funeral_case_id" disabled><div data-correction-case-results role="listbox"></div><p data-correction-selected-case></p>
        </div>
        <div data-change-field="payment_details" hidden>
            <div class="pw-grid"><label>Payment Method *<select name="payment_method" disabled required><option value="cash">Cash</option><option value="cashless">Cashless</option></select></label>
            <label data-cashless>Cashless Type *<select name="cashless_type" disabled><option value="">Select type</option>@foreach(\App\Support\Payments\PaymentDetails::CASHLESS_TYPES as $type)<option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>@endforeach</select></label></div>
            <div class="pw-grid" data-cashless>
                <label data-types="bank_transfer">Bank *<select name="bank_name" disabled><option value="">Select bank</option>@foreach(\App\Support\Payments\PaymentDetails::BANKS as $bank)<option>{{ $bank }}</option>@endforeach</select></label>
                <label data-other-bank>Bank Name *<input name="other_bank_name" disabled placeholder="e.g., EastWest Bank" maxlength="80"></label>
                <label data-types="bank_transfer gcash maya other">Account Name <small>(Optional)</small><input name="account_name" disabled placeholder="e.g., Maria Santos" maxlength="100"></label>
                <label data-types="gcash maya other">Mobile Number <small>(Optional)</small><input name="mobile_number" disabled placeholder="e.g., 09171234567" maxlength="14"></label>
                <label>Reference Number <span data-reference-required>*</span><input name="reference_number" disabled placeholder="e.g., TXN-12345678" minlength="4" maxlength="60"></label>
                <label data-types="card">Card Type <small>(Optional)</small><select name="card_type" disabled><option value="">Select type</option><option value="debit">Debit</option><option value="credit">Credit</option></select></label>
                <label data-types="card">Approval Code <small>(Optional if reference provided)</small><input name="approval_code" disabled placeholder="e.g., AP1234" maxlength="40"></label>
                <label data-types="card">Terminal Provider <small>(Optional)</small><input name="terminal_provider" disabled placeholder="e.g., BDO POS" maxlength="80"></label>
                <label data-types="other">Payment Channel *<input name="payment_channel" disabled placeholder="e.g., Dragonpay" maxlength="80"></label>
                <label>Payment Notes <small>(Optional)</small><input name="payment_notes" disabled placeholder="e.g., Family representative paid" maxlength="255"></label>
            </div>
        </div>
    </div>
    <div data-duplicate-reference hidden>
        <p class="pw-inline-note">Payment to void <strong data-void-record>{{ $payment->display_payment_record_no ?? 'Payment #'.$payment->id }}</strong></p>
        <label>Payment Record No. to keep *
            <select name="evidence_reference" disabled>
                <option value="">Select the valid payment to keep</option>
                @foreach($retainedCandidates as $candidate)
                    <option value="{{ $candidate->display_payment_record_no }}">{{ $candidate->display_payment_record_no }} · ₱{{ number_format((float) $candidate->amount, 2) }} · {{ ($candidate->paid_at ?? $candidate->paid_date)?->format('M d, Y g:i A') ?? 'Date not recorded' }} · {{ \App\Support\Payments\PaymentDetails::label($candidate) }}</option>
                @endforeach
            </select>
        </label>
        @if($retainedCandidates->isEmpty())
            <p class="pw-inline-note">No other valid payment on this case matches the recorded amount and method. A duplicate void needs a matching payment to keep.</p>
        @endif
    </div>
    <label>Explain what happened *<textarea name="reason" required minlength="10" maxlength="1000" rows="3" placeholder="e.g., The receipt shows ₱5,000, but I entered ₱15,000."></textarea></label>
    <details><summary>Add Additional Evidence (Optional)</summary>
        <label>Additional Evidence <small>(Optional · JPG, PNG or PDF · max 5 MB)</small><input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf"></label>
        <button type="button" data-proof-clear hidden>Remove attachment</button>
    </details>
    <p class="pw-error" data-workflow-error role="alert" hidden></p>
</div>
<footer class="pw-footer pw-correction-form-footer">
    <section class="pw-impact" aria-live="polite">
        <p data-void-explanation hidden></p>
        <h3>Estimated balance if approved</h3>
        <p data-correction-impact>Select the details you need to correct.</p>
    </section>
    <small class="pw-footer-note">Changes apply after Branch Admin approval.</small>
    <div class="pw-correction-form-actions"><button type="button" data-workflow-close>Cancel</button><button type="submit" class="pw-primary" data-correction-request-submit>Send for Approval</button></div>
</footer>
</form>
</section>
@endsection
