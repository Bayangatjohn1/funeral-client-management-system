<section class="pc-requests" aria-labelledby="pcRequestsTitle">
    <header class="pc-requests__header">
        <h2 id="pcRequestsTitle">Correction Requests</h2>
        <p>Review changes requested by staff before updating a payment record.</p>
    </header>
    @if($requests->isEmpty())
        <div class="pc-empty">
            <span class="pc-empty__icon"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></span>
            <h3>No correction requests yet</h3>
            <p>Requests from staff in your branch will appear here for review.</p>
        </div>
    @else
        <div class="pc-list">
        @foreach($requests as $item)
            <article class="pc-request">
                <div class="pc-request__summary">
                    <div><span class="pc-meta-label">Payment record</span><strong>{{ $item->payment?->display_payment_record_no ?? 'Payment record unavailable' }}</strong><span class="pc-muted">{{ $item->payment?->funeralCase?->case_code }}</span></div>
                    <div><span class="pc-meta-label">Request type</span><span>{{ $item->correction_type === 'VOID_ONLY' ? 'Void payment' : 'Correct payment' }}</span><span class="pc-muted">{{ $item->requester?->name }} &middot; {{ $item->created_at->format('M d, Y g:i A') }}</span></div>
                    <span class="pc-status" data-status="{{ $item->status }}">{{ ucfirst(strtolower($item->status)) }}</span>
                </div>
                <details class="pc-review">
                    <summary><i class="bi bi-eye" aria-hidden="true"></i> Review request <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                    <div class="pc-review__body">
                        <h3>Reason for correction</h3><p class="pc-reason">{{ $item->reason }}</p>
                        @if($item->correction_type === 'VOID_ONLY')
                            <p class="pc-notice">This request will void the original payment without creating a replacement.</p>
                        @else
                            <h3>Payment changes</h3>
                            <div class="pc-comparison" role="table" aria-label="Original and requested payment details">
                                <div class="pc-compare-row pc-compare-head" role="row"><span role="columnheader">Detail</span><span role="columnheader">Original payment</span><span role="columnheader">Requested correction</span></div>
                            @foreach(['amount' => 'Amount', 'paid_date' => 'Payment date', 'payment_method' => 'Payment method', 'reference_number' => 'Reference number', 'funeral_case_id' => 'Case ID'] as $field => $label)
                                @if(array_key_exists($field, $item->proposed_values ?? []))
                                    @php
                                        $originalValue = $item->original_values[$field] ?? null;
                                        $requestedValue = $item->proposed_values[$field];
                                        $formatValue = function ($value) use ($field) {
                                            if ($value === null || $value === '') return 'Not provided';
                                            if ($field === 'amount') return 'PHP '.number_format((float) $value, 2);
                                            if ($field === 'paid_date') {
                                                try { return \Carbon\Carbon::parse($value)->timezone(config('app.timezone'))->format('M d, Y'); }
                                                catch (\Throwable $e) { return 'Date unavailable'; }
                                            }
                                            if ($field === 'payment_method') return ucfirst(str_replace('_', ' ', $value));
                                            return $value;
                                        };
                                        $originalDisplay = $formatValue($originalValue);
                                        $requestedDisplay = $formatValue($requestedValue);
                                    @endphp
                                    <div class="pc-compare-row" role="row">
                                        <span role="rowheader">{{ $label }}</span>
                                        <span role="cell" data-label="Original">{{ $originalDisplay }}</span>
                                        <span role="cell" data-label="Requested" class="{{ $originalDisplay !== $requestedDisplay ? 'pc-changed' : '' }}">{{ $requestedDisplay }}@if($originalDisplay === $requestedDisplay)<small>Unchanged</small>@endif</span>
                                    </div>
                                @endif
                            @endforeach
                            </div>
                        @endif
                        @if($item->status === 'PENDING')
                            <p class="pc-muted">Approval voids the original entry{{ $item->correction_type === 'CORRECT' ? ', creates a corrected payment,' : '' }} and recalculates the case payment totals.</p>
                            <div class="pc-decisions">
                                <form method="POST" action="{{ route('admin.payment-corrections.approve', $item) }}" data-pc-approve-form data-payment-record="{{ $item->payment?->display_payment_record_no }}" data-correction-type="{{ $item->correction_type }}">
                                    @csrf<button class="pm-btn primary" type="submit">{{ $item->correction_type === 'VOID_ONLY' ? 'Approve Void' : 'Approve Correction' }}</button>
                                </form>
                                <details class="pc-reject"><summary>Reject request</summary><form method="POST" action="{{ route('admin.payment-corrections.reject', $item) }}">@csrf<label for="rejectReason{{ $item->id }}">Reason for rejection</label><textarea id="rejectReason{{ $item->id }}" name="reason" required minlength="5" maxlength="1000" rows="3"></textarea><button class="pm-btn" type="submit">Confirm Rejection</button></form></details>
                            </div>
                        @else
                            <p class="pc-muted">Reviewed by {{ $item->reviewer?->name ?? 'Branch Admin' }} &middot; {{ $item->reviewed_at?->format('M d, Y g:i A') }}</p>
                            @if($item->review_reason)<p class="pc-reason">{{ $item->review_reason }}</p>@endif
                            @if($item->replacementPayment)<p>Replacement payment: {{ $item->replacementPayment->display_payment_record_no }}</p>@endif
                        @endif
                    </div>
                </details>
            </article>
        @endforeach
        </div>
        @if($requests->hasPages())<div class="pc-pagination">{{ $requests->withQueryString()->links() }}</div>@endif
    @endif
</section>

<dialog id="pcApprovalDialog" class="pc-approval-dialog" aria-labelledby="pcApprovalTitle" aria-describedby="pcApprovalDescription">
    <header><h2 id="pcApprovalTitle">Approve correction?</h2><button type="button" class="pc-modal-close" data-pc-cancel aria-label="Close confirmation"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
    <div class="pc-modal-body">
        <p class="pc-modal-record">Payment record <strong data-pc-record></strong></p>
        <p id="pcApprovalDescription"></p>
        <p>The original entry remains in the payment history for reference.</p>
    </div>
    <footer><button type="button" class="pc-modal-cancel" data-pc-cancel autofocus>Cancel</button><button type="button" class="pc-modal-confirm" data-pc-confirm>Approve Correction</button></footer>
</dialog>
