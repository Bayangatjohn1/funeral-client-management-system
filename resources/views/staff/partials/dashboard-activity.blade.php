<div data-activity-panel="cases">
    <div class="ref-activity-list">
        @if($recentCases->isNotEmpty())
            <div class="ref-activity-heading ref-case-row" aria-hidden="true"><span>Case ID</span><span>Deceased Name</span><span>Client Name</span><span>Package Selected</span><span>Status</span></div>
        @endif
        @forelse($recentCases as $case)
            @php
                $statusClass = match($case->case_status) { 'ACTIVE' => 'active', 'COMPLETED' => 'completed', default => 'draft' };
            @endphp
            <div class="ref-activity-row ref-case-row">
                <div><span class="ref-field-label">Case ID</span><span class="ref-code">{{ $case->case_code }}</span><small class="ref-meta">Created by {{ $case->encodedBy?->name ?? 'Unknown user' }}<br>{{ $case->created_at?->format('M d, Y · h:i A') }}</small></div>
                <div><span class="ref-field-label">Deceased Name</span><strong>{{ $case->deceased?->full_name ?? 'Unknown' }}</strong></div>
                <div><span class="ref-field-label">Client Name</span><span>{{ $case->client?->full_name ?? 'No client record' }}</span></div>
                <div><span class="ref-field-label">Package Selected</span><span class="ref-package">{{ $case->package_name_snapshot ?: $case->custom_package_name ?: $case->package?->name ?: $case->service_package ?: 'No package selected' }}</span></div>
                <div><span class="ref-field-label">Status</span><span class="status-pill {{ $statusClass }}">{{ \Illuminate\Support\Str::title(strtolower($case->case_status)) }}</span><a class="dashboard-view-icon" href="{{ route('funeral-cases.index', ['q' => $case->case_code]) }}" aria-label="View Case Record" data-tip="View Case Record"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></a></div>
            </div>
        @empty
            <div class="ref-empty"><div class="ref-empty-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></div><p>No new cases created today.</p></div>
        @endforelse
    </div>
    @if($recentCases->hasPages())
        <nav class="dashboard-activity-pagination" aria-label="Recent cases pages">
            <span>{{ $recentCases->firstItem() }}–{{ $recentCases->lastItem() }} of {{ $recentCases->total() }} cases</span>
            <div>
                @if($recentCases->onFirstPage())<button disabled aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>@else<a href="{{ $recentCases->previousPageUrl() }}" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>@endif
                <span>Page {{ $recentCases->currentPage() }} of {{ $recentCases->lastPage() }}</span>
                @if($recentCases->hasMorePages())<a href="{{ $recentCases->nextPageUrl() }}" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>@else<button disabled aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>@endif
            </div>
        </nav>
    @endif
</div>
<div data-activity-panel="payments" hidden>
    <div class="ref-activity-list">
        @if($recentPayments->isNotEmpty())
            <div class="ref-activity-heading ref-payment-row" aria-hidden="true"><span>Case ID</span><span>Deceased Name</span><span>Client Name</span><span>Payment Method</span><span>Date &amp; Time</span><span>Payment Status</span></div>
        @endif
        @forelse($recentPayments->take(3) as $payment)
            @php
                $paidAt = $payment->paid_at ?? $payment->created_at;
                $rawStatus = strtoupper((string) ($payment->payment_status_after_payment ?: $payment->funeralCase?->payment_status ?: 'UNPAID'));
                $statusLabel = match($rawStatus) { 'PAID' => 'Fully Paid', 'PARTIAL' => 'Partial', default => 'Unpaid' };
                $statusClass = match($rawStatus) { 'PAID' => 'completed', 'PARTIAL' => 'draft', default => 'unpaid' };
            @endphp
            <div class="ref-activity-row ref-payment-row">
                <div><span class="ref-field-label">Case ID</span><span class="ref-code">{{ $payment->funeralCase?->case_code ?? 'N/A' }}</span></div>
                <div><span class="ref-field-label">Deceased Name</span><strong>{{ $payment->funeralCase?->deceased?->full_name ?? 'Unknown' }}</strong></div>
                <div><span class="ref-field-label">Client Name</span><span>{{ $payment->funeralCase?->client?->full_name ?? 'No client' }}</span></div>
                <div><span class="ref-field-label">Payment Method</span><span class="ref-code">{{ $payment->method ? \Illuminate\Support\Str::title(strtolower((string) $payment->method)) : 'Payment' }}</span></div>
                <div><span class="ref-field-label">Date &amp; Time</span><span class="ref-meta">{{ optional($paidAt)->format('M d, Y · h:i A') }}</span></div>
                <div><span class="ref-field-label">Payment Status</span><span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span><a class="dashboard-view-icon" href="{{ route('payments.history', ['q' => $payment->funeralCase?->case_code, 'tab' => 'summary']) }}" aria-label="View Payment Monitoring" data-tip="View Payment Monitoring"><i class="bi bi-wallet2" aria-hidden="true"></i></a></div>
            </div>
        @empty
            <div class="ref-empty"><div class="ref-empty-icon"><i class="bi bi-receipt" aria-hidden="true"></i></div><p>No payments recorded today.</p></div>
        @endforelse
    </div>
</div>
