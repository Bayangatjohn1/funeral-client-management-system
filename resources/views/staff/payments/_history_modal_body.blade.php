<div data-pm-history-pane>
<h3 class="pm-section-heading">Payment Summary</h3>
<div class="pm-ledger-summary">
    <div class="pm-ledger-card"><strong><small>PHP</small> {{ number_format((float) $case->total_amount, 2) }}</strong><span>Total Contract Amount</span></div>
    <div class="pm-ledger-card pm-ledger-paid"><strong><small>PHP</small> {{ number_format((float) $case->total_paid, 2) }}</strong><span>Total Paid to Date</span></div>
    <div class="pm-ledger-card {{ (float) $case->balance_amount > 0 ? 'pm-ledger-due' : 'pm-ledger-paid' }}"><strong><small>PHP</small> {{ number_format((float) $case->balance_amount, 2) }}</strong><span>Remaining Balance</span></div>
</div>
@php
    $chronologicalPayments = $casePayments->sortBy(fn ($payment) => [
        ($payment->paid_at ?? $payment->paid_date)?->timestamp ?? PHP_INT_MIN,
        $payment->id,
    ]);
    [$validPayments, $voidedPayments] = $chronologicalPayments->partition(fn ($payment) => !$payment->isVoided());
    $ledgerViewer = auth()->user();
    $runningBalance = (float) $case->total_amount;
    $balancesByPayment = [];
    foreach ($validPayments as $validPayment) {
        $balanceAfter = max(0, $runningBalance - (float) $validPayment->amount);
        $balancesByPayment[$validPayment->id] = [$runningBalance, $balanceAfter];
        $runningBalance = $balanceAfter;
    }
@endphp
<div class="pm-ledger-section-head">
    <h3 class="pm-section-heading">Payment transactions</h3>
    <span class="pm-ledger-count">{{ number_format($validPayments->count()) }} valid · {{ number_format($voidedPayments->count()) }} voided</span>
</div>
<div class="pm-ledger-cards" role="list" aria-label="Payment transactions">
@forelse($validPayments->reverse()->concat($voidedPayments->reverse()) as $payment)
                                        @php
                                            $methodLabel = \App\Support\Payments\PaymentDetails::label($payment);
                                            $paidAt = $payment->paid_at ?? $payment->paid_date;
                                            $txnDetId = 'summary-txnd-' . $payment->id;
                                            $txnRef = $payment->reference_number ?: $payment->transaction_reference_no ?: null;
                                            $remarks = $payment->remarks ?: null;
                                            $recordedBy = $payment->recordedBy?->name ?? $payment->encodedBy?->name ?? null;
                                            $senderName = $payment->sender_name ?: null;
                                            $statusAfter = $payment->payment_status_after_payment ?? null;
                                            $recordNo = $payment->display_payment_record_no ?? null;
                                            $ledgerCorrection = $payment->ledgerCorrection();
                                            $visibleCorrectionHistory = $payment->correctionRequests->sortByDesc('id')
                                                ->filter(fn ($request) => $request->isVisibleTo($ledgerViewer))->values();
                                            $visibleCorrection = $visibleCorrectionHistory->first();
                                            $correctionHistoryCounts = $visibleCorrectionHistory->countBy('status');
                                            $correctionHistoryId = 'summary-correction-history-' . $payment->id;
                                            $correctionState = strtolower((string) $visibleCorrection?->status);
                                            $correctionStateLabel = match ($correctionState) {
                                                'pending' => 'Correction pending',
                                                'approved' => 'Correction approved',
                                                'rejected' => 'Correction declined',
                                                'withdrawn' => 'Correction withdrawn',
                                                default => null,
                                            };
                                        @endphp
<article role="listitem" @class(['pm-payment-card', 'pm-ledger-transaction', 'is-voided' => $payment->isVoided(), 'is-pending' => $correctionState === 'pending', 'is-approved' => $correctionState === 'approved', 'is-rejected' => $correctionState === 'rejected'])
    @if($ledgerViewer->isStaff())
        data-correction-status="{{ $payment->ledgerStatusLabel($payment->isVoided() ? $ledgerCorrection : $visibleCorrection) }}"
        data-correction-references="{{ json_encode(array_values(array_unique(array_filter([
            $payment->receipt_or_no, $payment->accounting_reference_no, $payment->reference_number,
            $payment->transaction_reference_no, $payment->approval_code, $recordNo,
        ])))) }}"
    @endif>
    <div class="pm-payment-card__primary">
        <div class="pm-payment-card__date" data-label="Payment date">
            <strong class="pm-history-date">{{ $paidAt?->format('M d, Y') ?? 'Date not provided' }}</strong>
            @if($paidAt)<span class="pm-history-time">{{ $paidAt->format('h:i A') }}</span>@endif
        </div>
        <div class="pm-payment-card__amount" data-label="Amount"><small>PHP</small><strong>{{ number_format((float) $payment->amount, 2) }}</strong></div>
        <div class="pm-payment-card__badges">
            @if($payment->isVoided())
                <span class="pm-ledger-status" data-state="voided">{{ $payment->ledgerStatusLabel($ledgerCorrection) }}</span>
            @elseif($correctionStateLabel)
                <span class="pm-ledger-status" data-state="{{ $correctionState }}">{{ $correctionStateLabel }}</span>
            @else
                <span class="pm-payment-posted">Posted</span>
            @endif
        </div>
    </div>
    <div class="pm-payment-card__details">
        <div class="pm-payment-card__reference" data-label="OR number / Record">
            <span class="pm-payment-card__record-label">Payment Record</span>
            <strong class="pm-ledger-record">{{ $recordNo ?? 'Not provided' }}</strong>
            <span class="pm-history-time">OR: {{ $payment->receipt_or_no ?: 'Not recorded' }}</span>
        </div>
        <div class="pm-payment-card__method" data-label="Method"><span class="pm-payment-card__record-label">Method</span><strong class="pm-ledger-method">{{ $methodLabel }}</strong></div>
        @if($txnRef && $txnRef !== $payment->receipt_or_no)
            <div class="pm-payment-card__method"><span class="pm-payment-card__record-label">Transaction reference</span><strong class="pm-ledger-method">{{ $txnRef }}</strong></div>
        @endif
        <div class="pm-history-actions" data-label="Actions">
            <button type="button" class="pm-modal-icon" data-pm-history-detail="{{ $txnDetId }}"
                    data-payment-record="{{ $recordNo ?? '' }}" data-print-url="{{ route('payments.summary', $payment) }}"
                    data-print-label="{{ $payment->isVoided() ? 'Print Void Record' : 'Print Payment Record' }}"
                    aria-label="View payment details" aria-controls="{{ $transactionsModalId }}-payment" aria-expanded="false">
                <i class="bi bi-eye" aria-hidden="true"></i><span class="pm-action-tooltip" aria-hidden="true">View payment details</span>
            </button>
            @unless($payment->isVoided())
                <a class="pm-modal-icon" data-pm-transaction-print href="{{ route('payments.summary', $payment) }}" target="_blank" rel="noopener" aria-label="Print payment record"><i class="bi bi-printer" aria-hidden="true"></i><span class="pm-action-tooltip" aria-hidden="true">Print payment record</span></a>
            @endunless
        </div>
    </div>
    @if($visibleCorrection)
        <div class="pm-payment-card__correction">
            <div class="pm-payment-card__correction-actions">
                <div class="pm-payment-card__correction-link">
                    <span class="pm-ledger-status" data-state="{{ $correctionState }}"
                          @if($correctionState === 'rejected' && filled($visibleCorrection->review_reason)) tabindex="0" aria-describedby="pm-review-reason-{{ $visibleCorrection->id }}" @endif>
                        {{ $correctionStateLabel }}
                    </span>
                    @if($correctionState === 'rejected' && filled($visibleCorrection->review_reason))
                        <span class="pm-ledger-review-tooltip" id="pm-review-reason-{{ $visibleCorrection->id }}" role="tooltip">{{ $visibleCorrection->review_reason }}</span>
                    @endif
                </div>
                <button type="button" class="pm-correction-history-trigger" data-pm-correction-history-open="{{ $correctionHistoryId }}"
                        aria-controls="{{ $correctionHistoryId }}" aria-expanded="false">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                    Correction history <span>{{ $visibleCorrectionHistory->count() }}</span>
                </button>
            </div>
        </div>
    @endif
    @if($payment->isVoided() && $ledgerCorrection)
        <div class="pm-payment-card__correction pm-payment-card__related">@include('staff.payments._ledger_correction_links', ['linkRelatedPayments' => false])</div>
    @endif
    @if($visibleCorrectionHistory->isNotEmpty())
        <div class="pm-txn-det pm-correction-history-content" id="{{ $correctionHistoryId }}" hidden>
            <section class="pm-correction-history-overview" aria-label="Payment correction history summary">
                <div class="pm-correction-history-overview__title">
                    <div><small>Payment Record · {{ $recordNo ?? 'Not provided' }}</small><strong>{{ $visibleCorrectionHistory->count() }} {{ \Illuminate\Support\Str::plural('request', $visibleCorrectionHistory->count()) }}</strong></div>
                    <span>Case {{ $case->case_code ?? 'Not provided' }}</span>
                </div>
                <div class="pm-correction-history-overview__payment">
                    <span><small>Amount</small><strong>PHP {{ number_format((float) $payment->amount, 2) }}</strong></span>
                    <span><small>Method</small><strong>{{ $methodLabel }}</strong></span>
                    <span><small>Paid at</small><strong>{{ $paidAt?->format('M d, Y h:i A') ?? 'Date not provided' }}</strong></span>
                    <span><small>Receipt / OR No.</small><strong>{{ $payment->receipt_or_no ?: ($payment->accounting_reference_no ?: 'Not recorded') }}</strong></span>
                </div>
                <ul class="pw-correction-summary-counts" aria-label="Requests by status">
                    @foreach(['PENDING', 'APPROVED', 'REJECTED', 'WITHDRAWN'] as $historyStatus)
                        @if($correctionHistoryCounts->has($historyStatus))
                            <li class="pm-correction-status" data-status="{{ $historyStatus }}">{{ \Illuminate\Support\Str::headline($historyStatus) }} {{ $correctionHistoryCounts[$historyStatus] }}</li>
                        @endif
                    @endforeach
                </ul>
            </section>
            <ol class="pm-correction-history-list" aria-label="Correction requests for payment {{ $recordNo ?? 'without record number' }}">
                @foreach($visibleCorrectionHistory as $historyItem)
                    <li class="pm-correction-entry">
                        <div class="pm-correction-entry-head">
                            <h3>Request #{{ $historyItem->id }} · {{ $historyItem->correction_type === 'VOID_ONLY' ? 'Void payment' : 'Correct payment' }}</h3>
                            <span class="pm-correction-status" data-status="{{ $historyItem->status }}">{{ \Illuminate\Support\Str::headline($historyItem->status) }}</span>
                        </div>
                        <dl class="pm-correction-meta">
                            <div><dt>Requested by</dt><dd>{{ $historyItem->requester?->name ?? 'Unknown user' }} · {{ $historyItem->created_at?->format('M d, Y g:i A') ?? 'Date unavailable' }}</dd></div>
                            @if($historyItem->reason_category || $historyItem->reason)
                                <div><dt>Reason</dt><dd>{{ $historyItem->reason_category }}{{ $historyItem->reason ? ' · '.$historyItem->reason : '' }}</dd></div>
                            @endif
                            @if($historyItem->reviewed_at)
                                <div><dt>Reviewed by</dt><dd>{{ $historyItem->reviewer?->name ?? 'Unknown user' }} · {{ $historyItem->reviewed_at->format('M d, Y g:i A') }}</dd></div>
                            @endif
                            @if($historyItem->review_reason)
                                <div><dt>Review note</dt><dd>{{ $historyItem->review_reason }}</dd></div>
                            @endif
                        </dl>
                        <div class="pm-correction-entry-footer">
                            <a class="pw-text-action" href="{{ route('payments.corrections.show', $historyItem) }}" data-payment-workflow>View request details <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
    <div class="pm-txn-det" id="{{ $txnDetId }}" hidden>
<h4 class="pm-detail-group-title">Payment information</h4>
<div class="pm-txn-det-grid">
<div class="pm-txn-det-cell"><span>Payment Record No.</span><strong>{{ $recordNo ?? 'Not provided' }}</strong></div>
<div class="pm-txn-det-cell"><span>Payment Date</span><strong>{{ $paidAt?->format('M d, Y h:i A') ?? 'Not provided' }}</strong></div>
<div class="pm-txn-det-cell"><span>Payment Amount</span><strong><small>PHP</small>{{ number_format((float) $payment->amount, 2) }}</strong></div>
<div class="pm-txn-det-cell"><span>Payment Method</span><strong>{{ $methodLabel }}</strong></div>
<div class="pm-txn-det-cell"><span>Transaction Status</span><div><strong>{{ $payment->ledgerStatusLabel($payment->isVoided() ? $ledgerCorrection : $visibleCorrection) }}</strong>
    @include('staff.payments._ledger_correction_links', ['linkRelatedPayments' => false])
</div></div>
</div>
@unless($payment->isVoided())
@php [$previousBalance, $balanceAfter] = $balancesByPayment[$payment->id]; @endphp
<h4 class="pm-detail-group-title">Balance information</h4>
<div class="pm-txn-det-grid">
<div class="pm-txn-det-cell"><span>Previous Balance</span><strong><small>PHP</small>{{ number_format($previousBalance, 2) }}</strong></div>
<div class="pm-txn-det-cell"><span>Balance After Payment</span><strong><small>PHP</small>{{ number_format($balanceAfter, 2) }}</strong></div>
@if($statusAfter)<div class="pm-txn-det-cell"><span>Status After Payment</span><strong class="pm-status {{ $statusClass($statusAfter) }}">{{ \Illuminate\Support\Str::headline($statusAfter) }}</strong></div>@endif
</div>
@endunless
<h4 class="pm-detail-group-title">References and recording</h4>
<div class="pm-txn-det-grid">
<div class="pm-txn-det-cell">
    <span>Receipt / Reference No.</span>
    <div class="pm-reference-value">
        <strong>{{ $payment->receipt_or_no ?: ($txnRef ?: 'Not recorded') }}</strong>
        @if($payment->proof_path && $payment->proof_mime)
            <a href="{{ route('payment-proof.payment', $payment) }}" data-proof-viewer data-proof-mime="{{ $payment->proof_mime }}"><i class="bi bi-file-earmark-image" aria-hidden="true"></i> View Proof</a>
        @else
            <small>No proof on file</small>
        @endif
    </div>
</div>
@if($payment->approval_code || ($payment->receipt_or_no && $txnRef))
<div class="pm-txn-det-cell"><span>{{ $payment->approval_code ? 'Approval Code' : 'Transaction Reference' }}</span><strong>{{ $payment->approval_code ?: ($txnRef ?: 'Not provided') }}</strong></div>
@endif
@if($senderName)<div class="pm-txn-det-cell"><span>Sender / Account Name</span><strong>{{ $senderName }}</strong></div>@endif
<div class="pm-txn-det-cell"><span>Recorded By</span><strong>{{ $recordedBy ?: 'Not provided' }}</strong></div>
<div class="pm-txn-det-cell"><span>Recorded On</span><strong>{{ $payment->created_at?->format('M d, Y h:i A') ?? 'Not provided' }}</strong></div>
@if($remarks)<div class="pm-txn-det-cell"><span>Remarks</span><strong>{{ $remarks }}</strong></div>@endif
</div>
                                                <div class="pm-detail-actions">
                                                    @include('staff.payments._correction_request', ['payment' => $payment])
</div>
</div>
</article>
@empty
<div class="pm-empty">No payment transactions found for this case.</div>
@endforelse
</div>
</div>








