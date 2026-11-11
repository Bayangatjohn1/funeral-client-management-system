@php
    $systemAdmin = auth()->user()->isSystemAdmin();
    $filters = [
        'correction_status' => ['Request status', 'All statuses', 'funnel', [
            'PENDING' => 'Pending', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected', 'WITHDRAWN' => 'Withdrawn',
        ]],
        'correction_type' => ['Request type', 'All request types', 'arrow-repeat', [
            'CORRECT' => 'Correct payment', 'VOID_ONLY' => 'Void payment',
        ]],
        'correction_period' => ['Date submitted', 'Any date', 'calendar3', [
            'week' => 'Last 7 days', 'last_30_days' => 'Last 30 days', 'month_to_date' => 'This month',
        ]],
    ];
    if (auth()->user()->isBranchAdmin()) {
        $filters['correction_requester_id'] = ['Requested by', 'All requesters', 'person', $correctionRequesters->pluck('name', 'id')->all()];
    }
    if ($systemAdmin) {
        $filters['correction_branch_id'] = ['Branch', 'All branches', 'building', $branches->mapWithKeys(
            fn ($branch) => [$branch->id => $branch->branch_code.' · '.$branch->branch_name]
        )->all()];
        $hours = \App\Models\WorkflowNotice::CORRECTION_FOLLOW_UP_HOURS;
        $filters['correction_urgency'] = ['Review urgency', 'Any urgency', 'clock', [
            'overdue' => "Pending for {$hours}+ hours",
        ]];
    }

    $correctionChips = [];
    if (request()->filled('correction_q')) {
        $correctionChips['correction_q'] = 'Search: '.request('correction_q');
    }
    foreach ($filters as $key => [$label, , , $options]) {
        if (request()->filled($key)) {
            $correctionChips[$key] = $label.': '.($options[request($key)] ?? 'Unavailable');
        }
    }
    $filterCount = count($correctionChips) - (int) isset($correctionChips['correction_q']);
@endphp
<section class="pw-request-list" aria-labelledby="paymentCorrectionQueueTitle" data-pm-correction-card>
<div class="pc-requests__header pc-requests__header--queue">
    <a href="{{ route($monitoringRoute) }}" class="pm-header-action" aria-label="Back to Payment Records" data-pm-section-action>
        <i class="bi bi-arrow-left" aria-hidden="true"></i><span>Back</span>
    </a>
    <h2 id="paymentCorrectionQueueTitle">{{ $isStaff ? 'My Correction Requests' : 'Correction Requests' }}</h2>
</div>
<div class="pm-toolbar-shell ops-toolbar-shell pw-request-toolbar-shell">
<form id="pmFilterForm" method="GET" action="{{ route($monitoringRoute) }}" class="pm-toolbar ops-toolbar uniform-record-filters" data-correction-toolbar>
    <input type="hidden" name="tab" value="corrections">
    <div class="pm-field search has-icon ops-field ops-field--search shared-record-search">
        <i class="bi bi-search shared-record-search-icon" aria-hidden="true"></i>
        <input class="pm-control ops-control shared-record-search-input" name="correction_q" value="{{ request('correction_q') }}" maxlength="100" placeholder="e.g., #12, PAY-2026-000013, OR-203456 or TXN-9876" aria-label="Search correction requests by request, case, payment, OR or transaction reference" autocomplete="off" aria-controls="correctionSearchMatches" aria-expanded="false" data-correction-search>
        <button class="pm-search-clear shared-record-search-clear" type="button" @if(!request()->filled('correction_q')) hidden @endif aria-label="Clear search" data-correction-clear><i class="bi bi-x" aria-hidden="true"></i></button>
        <button class="pm-search-button" type="submit">Search</button>
        <div id="correctionSearchMatches" class="pm-search-matches" data-correction-matches hidden></div>
    </div>
    <details class="pm-more-filters">
        <summary aria-controls="correctionFilterPanel"><i class="bi bi-funnel" aria-hidden="true"></i><span>Filter</span>@if($filterCount)<span class="pm-filter-count">{{ $filterCount }}</span>@endif<i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
        <div id="correctionFilterPanel" class="pm-more-panel" role="group" aria-label="Correction request filters">
            <div class="pm-filter-head"><strong>Filter by</strong><button type="button" data-correction-filter-close aria-label="Close filters"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
            <div class="pm-filter-fields">
                @foreach($filters as $key => [$label, $default, $icon, $options])
                    <label class="pm-filter-label" for="{{ $key }}">{{ $label }}</label>
                    <div class="pm-field has-icon ops-field"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
                        <select class="pm-control ops-control" id="{{ $key }}" name="{{ $key }}" data-correction-filter>
                            <option value="">{{ $default }}</option>
                            @foreach($options as $value => $name)
                                <option value="{{ $value }}" @selected((string) $value === (string) request($key))>{{ $name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
                    </div>
                @endforeach
            </div>
            <div class="pm-filter-apply"><button type="submit" class="pm-btn primary" data-correction-apply>Apply filters</button></div>
        </div>
    </details>
    <div class="pm-field has-icon pm-sort-control shared-record-filter" data-filter-select-wrap>
        <i class="bi bi-sort-down" aria-hidden="true"></i>
        <select name="correction_sort" class="pm-control ops-control" aria-label="Sort requests" data-correction-submit><option value="newest" data-menu-label="Newest first">Sort</option><option value="oldest" @selected(request('correction_sort') === 'oldest')>Oldest first</option></select>
        <i class="bi bi-chevron-down pm-sel-chev shared-record-chevron" aria-hidden="true"></i>
    </div>
    <label class="pm-page-size" for="correctionPerPage">Requests per page
        <span class="pm-page-size-control global-filter-select-wrap shared-record-filter shared-record-page-size" data-filter-select-wrap>
            <select id="correctionPerPage" name="correction_per_page" class="pm-control ops-control" data-plain-options data-correction-submit>@foreach([10,20,50] as $size)<option @selected((int) request('correction_per_page',20) === $size)>{{ $size }}</option>@endforeach</select>
            <i class="bi bi-chevron-down table-toolbar-select-icon shared-record-chevron" aria-hidden="true" data-filter-select-icon></i>
        </span>
    </label>
    <div class="pm-applied-filters" data-correction-applied @if(!$correctionChips) hidden @endif>
        <div class="pm-filter-chips" aria-label="Applied correction filters">
            @foreach($correctionChips as $key => $label)
                <span class="catalog-filter-chip" data-tone="neutral">{{ $label }}<button type="button" data-correction-remove="{{ $key }}" aria-label="Remove {{ $label }}"><i class="bi bi-x" aria-hidden="true"></i></button></span>
            @endforeach
        </div>
        <div class="pm-actions">
            <a href="{{ route($monitoringRoute, request()->except([...array_keys($filters), 'open_correction', 'page'])) }}" class="pm-btn ops-btn-outline filter-clear-action" data-correction-clear-filters @if(!$filterCount) hidden @endif><span>Clear filters</span></a>
        </div>
    </div>
</form>
</div>
<div data-correction-results>
<div class="pw-request-table" role="region" aria-label="Correction requests table" tabindex="0">
<table><thead><tr><th>Request / Payment</th><th>Case</th>@if($systemAdmin)<th>Branch</th>@endif<th>Type</th><th>Requested By</th><th>Requested At</th><th>Status</th><th><span class="sr-only">View</span></th></tr></thead><tbody>
@forelse($requests as $item)
<tr data-workflow-row="{{ route('payments.corrections.show', $item) }}" data-request-number="#{{ $item->id }}" data-payment-number="{{ $item->payment?->display_payment_record_no }}" data-replacement-payment-number="{{ $item->replacementPayment?->display_payment_record_no }}" data-case-code="{{ $item->payment?->funeralCase?->case_code }}" data-receipt-number="{{ $item->payment?->receipt_or_no ?: $item->payment?->receipt_number }}" data-transaction-reference="{{ $item->payment?->transaction_reference_no ?: $item->payment?->reference_number }}" data-approval-code="{{ $item->payment?->approval_code }}" tabindex="0">
    <td>#{{ $item->id }}<br>{{ $item->payment?->display_payment_record_no }}</td><td>{{ $item->payment?->funeralCase?->case_code }}</td>
    @if($systemAdmin)<td>{{ $item->branch?->branch_name }}<br><small>{{ $item->branch?->branch_code }}</small></td>@endif
    <td>{{ $item->correction_type === 'VOID_ONLY' ? 'Void payment' : 'Correct payment' }}</td><td>{{ $item->requester?->name }}</td><td>{{ $item->created_at->format('M d, Y g:i A') }}</td><td><span class="pw-status" data-status="{{ $item->status }}">{{ ucfirst(strtolower($item->status)) }}</span></td><td><a href="{{ route('payments.corrections.show', $item) }}" data-payment-workflow data-workflow-payment-history aria-label="View correction history for {{ $item->payment?->display_payment_record_no ?? 'request '.$item->id }}" title="View correction history"><i class="bi bi-eye" aria-hidden="true"></i></a></td>
</tr>
@empty
<tr><td colspan="{{ $systemAdmin ? 8 : 7 }}"><p>{{ $correctionChips ? 'No matching requests.' : 'No correction requests yet.' }}</p><p>Submitted requests and their review status will appear here.</p></td></tr>
@endforelse
</tbody></table>
</div>
</div>
<p class="sr-only" data-correction-search-status role="status"></p>
</section>
<footer class="pm-foot" data-correction-pagination>{{ $requests->withQueryString()->links('components.pagination.table') }}</footer>
