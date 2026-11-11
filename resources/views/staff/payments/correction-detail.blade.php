@extends('layouts.panel')
@section('page_title', 'Payment Correction Request')
@section('content')
@include('staff.payments._workflow_styles')
@php
    $reviewer = auth()->user()->isBranchAdmin();
    $systemAdmin = auth()->user()->isSystemAdmin();
    $canWithdraw = auth()->user()->isStaff() && (int) $item->requested_by === (int) auth()->id();
    $payment = $item->payment;
    $originalRecordNo = $payment?->display_payment_record_no;
    $case = $payment?->funeralCase;
    $target = isset($item->proposed_values['funeral_case_id']) ? \App\Models\FuneralCase::where('branch_id', $item->branch_id)->find($item->proposed_values['funeral_case_id']) : $case;
    $proposedAmount = $item->correction_type === 'VOID_ONLY' ? 0 : (float) ($item->proposed_values['amount'] ?? $payment?->amount ?? 0);
    $originalAmount = (float) ($item->original_values['amount'] ?? $payment?->amount ?? 0);
    $approved = $item->status === 'APPROVED';
    $originalReferences = collect(['receipt_or_no' => 'Receipt / OR No.', 'reference_number' => 'Transaction Reference No.', 'transaction_reference_no' => 'Transaction Reference No.', 'approval_code' => 'Approval Code'])
        ->map(fn ($label, $field) => ['label' => $label, 'value' => array_key_exists($field, $item->original_values ?? []) ? $item->original_values[$field] : $payment?->{$field}])
        ->filter(fn ($reference) => filled($reference['value']))
        ->unique(fn ($reference) => $reference['label'].'|'.$reference['value']);
    $comparisonLabel = $approved ? 'Approved Changes' : 'Requested Changes';
    $duplicateVoid = $item->correction_type === 'VOID_ONLY' && $item->reason_category === 'Duplicate entry';
    $sameCase = $target?->id === $case?->id;
    $stale = $item->status === 'PENDING' && $payment && $item->original_values != $item->snapshot($payment);
    $format = function ($field, $value) use ($item) {
        if ($value === null || $value === '') return 'Not provided';
        if ($field === 'amount') return '₱'.number_format((float) $value, 2);
        if ($field === 'paid_date') return \Carbon\Carbon::parse($value)->format('M d, Y');
        if ($field === 'funeral_case_id') return \App\Models\FuneralCase::where('branch_id', $item->branch_id)->find($value)?->case_code ?? 'Case unavailable';
        if (in_array($field, ['payment_method', 'cashless_type', 'card_type'], true)) return ucwords(str_replace('_', ' ', (string) $value));
        return (string) $value;
    };
@endphp
<section id="workflowContent" class="pw-content" @if($systemAdmin) data-workflow-back-url="{{ route('admin.payments.index', ['tab' => 'corrections']) }}" @endif>
<header class="pw-head">
    <div>
        <small data-correction-viewing>Viewing request #{{ $item->id }}</small>
        <small data-correction-opening hidden></small>
        <h2>{{ $item->correction_type === 'VOID_ONLY' ? 'Void Payment Request' : 'Payment Correction Request' }}</h2>
        <small class="pw-request-source">Original payment <strong>{{ $originalRecordNo ?? 'Record number unavailable' }}</strong></small>
    </div>
    <button type="button" data-workflow-close aria-label="Close request"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
</header>
<div class="pw-body">
    <section class="pw-context" aria-label="Request summary">
        <div class="pw-context-title"><strong>{{ $case?->case_code }} · {{ $case?->deceased?->full_name }}</strong><span class="pw-status" data-status="{{ $item->status }}">{{ ucfirst(strtolower($item->status)) }}</span></div>
        <dl class="pw-meta-grid">
            @if($systemAdmin)<div><dt>Branch</dt><dd>{{ $item->branch?->branch_name }}<small>{{ $item->branch?->branch_code }}</small></dd></div>@endif
            @if($systemAdmin && $item->status === 'PENDING')
                <div><dt>Pending since</dt><dd>{{ $item->created_at->diffForHumans() }}<small>{{ $item->created_at->format('M d, Y g:i A') }}</small></dd></div>
            @endif
            <div><dt>Client</dt><dd>{{ $case?->client?->full_name ?: 'Not recorded' }}</dd></div>
            <div><dt>Recorded payment</dt><dd>₱{{ number_format((float) $payment?->amount, 2) }} · {{ $payment ? \App\Support\Payments\PaymentDetails::label($payment) : 'Unavailable' }}<small>Received · {{ ($payment?->paid_at ?? $payment?->paid_date)?->format('M d, Y g:i A') ?: 'Not recorded' }}</small></dd></div>
            <div><dt>Requested by</dt><dd>{{ $item->requester?->name }}<small>{{ $item->created_at->format('M d, Y g:i A') }}</small></dd></div>
            <div><dt>Recorded by</dt><dd>{{ $payment?->recordedBy?->name ?: 'Not recorded' }}</dd></div>
            @foreach($originalReferences as $reference)
                <div><dt>{{ $reference['label'] }}</dt><dd>{{ $reference['value'] }}</dd></div>
            @endforeach
        </dl>
        <div class="pw-context-actions">
            @unless($duplicateVoid && $reviewer)
                @include('staff.payments._proof', ['proofPayment' => $payment])
            @endunless
            @if($reviewer)<button type="button" data-review-history-open aria-expanded="false"><i class="bi bi-clock-history" aria-hidden="true"></i> View Payment History</button>@endif
        </div>
    </section>
    @if($duplicateVoid && $reviewer)
        <section class="pw-section" aria-label="Duplicate payment comparison">
            <h3>Compare the two payment records</h3>
            <div class="pw-grid pw-void-pair">
                @foreach([['Payment to void', $payment, $originalRecordNo], ['Payment to keep', $retainedPayment, $item->evidence_reference]] as [$label, $record, $number])
                    <div class="pw-notice">
                        <strong>{{ $label }}</strong>
                        <div>{{ $number ?: 'Record number unavailable' }}</div>
                        @if($record)
                            <div>₱{{ number_format((float) $record->amount, 2) }} · {{ \App\Support\Payments\PaymentDetails::label($record) }}</div>
                            <small>{{ ($record->paid_at ?? $record->paid_date)?->format('M d, Y g:i A') ?? 'Date not recorded' }}</small>
                            @include('staff.payments._proof', ['proofPayment' => $record, 'proofLabel' => 'View Payment Proof'])
                        @elseif($item->status === 'PENDING')
                            <p>This payment is no longer eligible. Review the ledger before deciding.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
    @if($stale)<p class="pw-notice" role="alert">The original payment changed after this request. Approval is unavailable. @if($reviewer)Reject this request and ask the staff to submit a fresh correction.@endif</p>@endif
    @if($item->status === 'PENDING' && !$reviewer)<p class="pw-inline-note">Waiting for Branch Admin approval. The payment stays as recorded while pending.</p>@endif
    <section class="pw-section"><h3>Payment Changes</h3>
    @if($item->correction_type === 'VOID_ONLY')
        <div class="pw-compare">
            <div><strong>Detail</strong><strong>Original Details</strong><strong>{{ $comparisonLabel }}</strong></div>
            <div><span>Payment record</span><span>₱{{ number_format($originalAmount, 2) }}</span><span>{{ $approved ? 'Voided without replacement' : 'Void without replacement' }}</span></div>
        </div>
        @if($duplicateVoid)<p class="pw-inline-note">Entry to keep · {{ $item->evidence_reference }}. No refund is issued.</p>@endif
    @else
        <div class="pw-compare"><div><strong>Detail</strong><strong>Original Details</strong><strong>{{ $comparisonLabel }}</strong></div>
        @foreach($item->proposed_values ?? [] as $field => $value)
            @if(!in_array($field, ['paid_at','accounting_reference_no','payment_mode','method','bank_or_channel','other_bank_or_channel','transaction_reference_no','sender_name']) && (string) ($item->original_values[$field] ?? '') !== (string) $value)
            <div><span>{{ match ($field) { 'funeral_case_id' => 'Case ID', 'receipt_or_no' => 'Receipt / OR No.', default => ucwords(str_replace('_', ' ', $field)) } }}</span><span>{{ $format($field, $item->original_values[$field] ?? null) }}</span><span class="pw-changed">{{ $format($field, $value) }}</span></div>
            @endif
        @endforeach
        </div>
    @endif
    </section>
    @if($reviewer)
        <template data-review-history-template>
            <section class="pw-content pw-review-history-panel" aria-label="Payment history comparison">
                <header class="pw-head"><div><small>Compare transactions</small><h2>Payment History</h2></div><button type="button" data-review-history-close aria-label="Close payment history"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
                <div class="pw-body">@include('staff.payments._correction_payment_history')</div>
            </section>
        </template>
    @endif
    @if($item->status === 'PENDING')
    <section class="pw-impact"><h3>Financial impact if approved</h3>
        @if($case && $target && !$stale)
            @if($sameCase && $proposedAmount === $originalAmount)
                <p class="pw-inline-note">No change to the payment amount. Case balance remains ₱{{ number_format((float) $case->balance_amount, 2) }}.</p>
            @else
            <div class="pw-compare">
                <div><strong>Case balance</strong><strong>Current</strong><strong>If approved</strong></div>
                <div><span>{{ $case->case_code }}</span><span>₱{{ number_format((float) $case->balance_amount, 2) }}</span><span>₱{{ number_format($case->paymentTotalsFor((float) $case->total_paid - $originalAmount + ($sameCase ? $proposedAmount : 0))['balance_amount'], 2) }}</span></div>
                @unless($sameCase)
                    <div><span>{{ $target->case_code }}</span><span>₱{{ number_format((float) $target->balance_amount, 2) }}</span><span>₱{{ number_format($target->paymentTotalsFor((float) $target->total_paid + $proposedAmount)['balance_amount'], 2) }}</span></div>
                @endunless
            </div>
            @endif
        @else
            <p>Balance preview unavailable for this request.</p>
        @endif
        <small>Preview uses current balances. Totals are validated again at approval.</small>
    </section>
    @endif
    <section class="pw-section pw-explanation"><h3>Staff explanation</h3>
        <p class="pw-explanation-text">{{ $item->reason }}</p>
        <dl class="pw-meta-grid">
            <div><dt>Correction type</dt><dd>{{ $item->reason_category }}</dd></div>
        </dl>
        @if($item->evidence_path)<a class="pw-text-action" href="{{ route('payments.corrections.evidence', $item) }}">Additional Evidence · {{ $item->evidence_name }}</a>@endif
    </section>
    @if($item->status !== 'PENDING')
    <section class="pw-section">
        <h3>Decision · {{ ucfirst(strtolower($item->status)) }}</h3>
        <dl class="pw-meta-grid">
            <div><dt>{{ $item->status === 'WITHDRAWN' ? 'Withdrawn by' : 'Reviewed by' }}</dt><dd>{{ $item->status === 'WITHDRAWN' ? $item->requester?->name : ($item->reviewer?->name ?? 'Branch Admin') }}</dd></div>
            <div><dt>Date and time</dt><dd>{{ ($item->reviewed_at ?? $item->withdrawn_at)?->format('M d, Y g:i A') }}</dd></div>
        </dl>
        @if($item->review_reason)
            <div class="pw-decision-note" data-status="{{ $item->status }}"><small>{{ $item->status === 'REJECTED' ? 'Rejection reason' : 'Review note' }}</small><p>{{ $item->review_reason }}</p></div>
        @endif
        @if($item->replacementPayment)
            <a class="pw-text-action" href="{{ route('payments.summary', $item->replacementPayment) }}" target="_blank" rel="noopener">View corrected payment {{ $item->replacementPayment->display_payment_record_no }} <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
        @endif
    </section>
    @endif
    @if($item->status === 'PENDING' && $reviewer)
    <section class="pw-section"><h3>Before you approve</h3>
        <p class="pw-inline-note">{{ $duplicateVoid ? 'Match both entries to one receipt or collection-log entry before approving the duplicate void.' : 'Check the requested changes against the proof and payment history.' }}</p>
    <form id="pwDecisionForm" action="{{ route('admin.payment-corrections.approve', $item) }}" method="POST" data-workflow-decision>
        <input type="hidden" name="preview_token" value="{{ $case && $target ? $item->approvalPreviewToken($case, $target) : '' }}">
        <a href="{{ route('payments.corrections.show', $item) }}" data-workflow-refresh hidden>Refresh financial preview</a>
        @csrf<label>What did you verify? *<textarea name="review_reason" required minlength="5" maxlength="1000" rows="3" placeholder="e.g., Checked receipt OR-1023 against the collection log."></textarea></label>
        @if($item->correction_type === 'VOID_ONLY')
            <label class="pw-void-confirmation"><input type="checkbox" name="duplicate_reconciled" value="1"><span>I checked the receipt or collection log. These two records represent one payment.</span></label>
        @endif
        <div data-rejection-fields hidden><label>Reason for Rejection *<textarea name="reason" minlength="5" maxlength="1000" rows="3" placeholder="Explain what needs to be corrected or supported."></textarea></label></div>
        <p data-workflow-error class="pw-error" role="alert" hidden></p>
    </form>
    </section>
    @endif
    @if($correctionHistory->isNotEmpty())
        <template data-correction-history-template>
            <section class="pw-content pw-review-history-panel pw-correction-history-panel" aria-label="Correction Request History">
                <header class="pw-head">
                    <h2>Correction Request History</h2>
                    <button type="button" data-review-history-close aria-label="Close correction history"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </header>
                <div class="pw-body pw-audit-trail">
                    <section class="pw-correction-history-context" aria-label="Correction history summary">
                        <div class="pw-correction-summary-head">
                            <h3>Summary</h3>
                            <strong>{{ $correctionHistory->count() }} {{ \Illuminate\Support\Str::plural('request', $correctionHistory->count()) }}</strong>
                        </div>
                        <p class="pw-audit-source">Original payment <strong>{{ $originalRecordNo ?? 'Record number unavailable' }}</strong>@if($case?->case_code) · Case {{ $case->case_code }}@endif</p>
                        @if($payment)
                            <p class="pw-audit-source"><strong>₱{{ number_format((float) $payment->amount, 2) }}</strong> · {{ \App\Support\Payments\PaymentDetails::label($payment) }}</p>
                        @endif
                        @php $historyCounts = $correctionHistory->countBy('status'); @endphp
                        <ul class="pw-correction-summary-counts" aria-label="Requests by status">
                            @foreach(['PENDING', 'APPROVED', 'REJECTED', 'WITHDRAWN'] as $status)
                                @if($historyCounts->has($status))
                                    <li class="pm-correction-status" data-status="{{ $status }}">{{ ucfirst(strtolower($status)) }} {{ $historyCounts[$status] }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </section>
                    <p class="pw-audit-source" data-correction-history-status role="status" hidden></p>
                    <ol class="pm-correction-history-list">
                        @foreach($correctionHistory as $history)
                            @include('staff.payments._correction_history_entry', ['entry' => $history, 'currentRequest' => $item])
                        @endforeach
                    </ol>
                </div>
            </section>
        </template>
    @endif
</div>
@if($item->status === 'PENDING' && ($reviewer || $canWithdraw || $systemAdmin))
<footer class="pw-footer">
@if($item->status === 'PENDING' && $reviewer)
    <button type="button" class="pw-danger" data-workflow-reject="{{ route('admin.payment-corrections.reject', $item) }}">Reject Request</button>
    <button type="submit" form="pwDecisionForm" @disabled($stale || ($duplicateVoid && !$retainedPayment)) class="{{ $item->correction_type === 'VOID_ONLY' ? 'pw-warning' : 'pw-primary' }}" data-confirm-title="{{ $item->correction_type === 'VOID_ONLY' ? 'Approve payment void?' : 'Approve payment correction?' }}" data-confirm-copy="{{ $duplicateVoid ? ($originalRecordNo ?? 'The selected payment').' will be voided. '.$item->evidence_reference.' will stay as the valid payment. No refund will be issued.' : 'The corrected payment will be posted and the case balance updated.' }}">{{ $item->correction_type === 'VOID_ONLY' ? 'Approve Void' : 'Approve Correction' }}</button>
@elseif($canWithdraw)
    <form action="{{ route('payments.corrections.withdraw', $item) }}" method="POST" data-workflow-decision>@csrf<button class="pw-warning" data-confirm-title="Withdraw this request?">Withdraw Request</button><p data-workflow-error hidden></p></form>
@elseif($systemAdmin)
    <div class="pw-footer-note">
        <small data-reminder-last>Last manual reminder · {{ $lastReminderAt?->format('M d, Y g:i A') ?? 'None sent' }}</small>
        <p data-reminder-feedback role="status"></p>
    </div>
    <form action="{{ route('admin.payment-corrections.remind', $item) }}" method="POST" data-workflow-reminder>
        @csrf
        <button type="submit" class="pw-warning"><i class="bi bi-bell" aria-hidden="true"></i> Remind Branch Admin</button>
    </form>
@endif
</footer>
@endif
</section>
@endsection
