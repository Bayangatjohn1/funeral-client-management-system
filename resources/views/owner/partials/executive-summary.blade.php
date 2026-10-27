<div data-records-url="{{ $recordsUrl }}">
    <p class="oe-summary-label">{{ match($kind) {
        'cases' => 'Cases Recorded',
        'service' => 'Total Service Value',
        'collections' => 'Collections Received',
        default => 'Current Balance',
    } }}</p>
    <strong class="oe-summary-total {{ $kind === 'cases' ? '' : 'oe-amount' }}">{{ $kind === 'cases' ? number_format($recordCount) : '₱'.number_format($scopeAmount, 2) }}</strong>
    <div class="oe-summary-secondary">
        @if($kind === 'cases')
            <span>Total Service Value</span><span class="oe-amount">₱{{ number_format($scopeAmount, 2) }}</span>
        @else
            <span>{{ $isPayments ? 'Payment transactions' : ($kind === 'risks' ? 'Cases past interment' : ($kind === 'outstanding' ? 'Cases with balance' : 'Verified cases')) }}</span>
            <span>{{ number_format($recordCount) }}</span>
        @endif
    </div>
    @if(!$recordCount)<p class="oe-empty">No records for this scope.</p>@endif
</div>
