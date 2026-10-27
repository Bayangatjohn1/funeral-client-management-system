@extends('layouts.panel')

@section('page_title', 'Master Case Records')
@section('page_desc', match(request('collection_state')) {
    'WITH_PAYMENTS' => 'Cases with recorded payments, including partial payments.',
    'WITH_BALANCE' => 'Cases with a current unpaid balance.',
    'AFTER_INTERMENT' => 'Cases with a balance remaining after interment. Review with the responsible branch.',
    default => 'Review case records across all branches.',
})
@section('hide_layout_topbar', '1')

@if(request('dashboard_range'))
@section('hide_page_title_icon', '1')
@section('page_back')
<a class="btn-outline owner-records-back" href="{{ route('owner.dashboard',['branch_id'=>request('dashboard_branch'),'range'=>request('dashboard_range'),'date_from'=>request('dashboard_from'),'date_to'=>request('dashboard_to')]) }}" aria-label="Back to Dashboard"><i class="bi bi-arrow-left" aria-hidden="true"></i><span class="owner-records-back-tooltip" role="tooltip">Back to Dashboard</span></a>
@endsection
@endif

@section('content')
<div class="owner-page-shell admin-table-page master-records-page owner-master-records-page">

@if($errors->any())
    <div class="flash-error">
        {{ $errors->first() }}
    </div>
@endif

<section class="table-system-card admin-table-card">
<div class="case-records-tabs-row">
    <nav class="table-quick-tabs case-records-tabs" aria-label="Master case record statuses">
        @foreach(['' => 'All', 'DRAFT' => 'Draft', 'ACTIVE' => 'Active', 'COMPLETED' => 'Completed'] as $statusKey => $statusLabel)
            <a class="table-quick-tab {{ ($caseStatus ?? '') === $statusKey ? 'table-quick-tab-active' : '' }}" href="{{ route('owner.history', array_filter(array_merge(request()->except(['case_status','page']), ['case_status' => $statusKey]), fn($value) => filled($value))) }}" @if(($caseStatus ?? '') === $statusKey) aria-current="page" @endif>{{ $statusLabel }}</a>
        @endforeach
    </nav>
</div>

<div class="table-system-toolbar admin-table-toolbar">
    @include('partials.case_filter_toolbar', [
        'showPerPage' => true,
        'showCaseStatus' => false,
        'showServiceType' => false,
        'showSort' => true,
        'sortOptions' => ['newest' => 'Newest', 'oldest' => 'Oldest'],
        'sort' => request('sort', 'newest'),
        'action' => route('owner.history'),
        'resetUrl' => route('owner.history',request()->only(['dashboard_branch','dashboard_range','dashboard_from','dashboard_to'])),
        'branchMode' => 'all',
        'branchId' => $branchId,
        'branches' => $branches,
        'datePreset' => $datePreset ?? '',
        'dateFrom' => $dateFrom ?? null,
        'dateTo' => $dateTo ?? null,
        'intermentFrom' => $intermentFrom ?? null,
        'intermentTo' => $intermentTo ?? null,
        'serviceTypes' => $serviceTypes ?? collect(),
        'packages' => $packages ?? collect(),
        'showVerificationStatus' => false,
        'showPackage' => true,
        'showEncodedBy' => false,
        'showInlineChips' => false,
        'useDateDisplayLabel' => false,
        'hiddenInputs' => request()->only(['case_status','collection_state','dashboard_branch','dashboard_range','dashboard_from','dashboard_to']),
    ])
    @if(request('collection_state'))
        @php
            $collectionLabel = match(request('collection_state')) { 'WITH_PAYMENTS'=>'With payments', 'WITH_BALANCE'=>'With balance', default=>'Balance after interment' };
            $overviewLabel = match(request('collection_state')) { 'WITH_PAYMENTS'=>'Cases With Payments', 'WITH_BALANCE'=>'Current Outstanding', default=>'Balances Past Interment Date' };
        @endphp
        <div class="owner-records-scope" aria-label="Active collection filter">
            <a class="owner-records-scope-chip" href="{{ request()->fullUrlWithoutQuery(['collection_state','page']) }}" aria-label="Remove {{ strtolower($collectionLabel) }} filter"><i class="bi bi-funnel" aria-hidden="true"></i>{{ $collectionLabel }}<i class="bi bi-x" aria-hidden="true"></i></a>
            <span>{{ $overviewLabel }} · {{ filled($dateFrom ?? null) || filled($dateTo ?? null) ? 'Selected recording dates' : 'All recording dates' }} · {{ number_format($cases->total()) }} matching {{ $cases->total() === 1 ? 'case' : 'cases' }}</span>
        </div>
    @endif
</div>


<div class="table-system-list">
    <div class="table-wrapper table-system-wrap" tabindex="0" role="region" aria-label="Case records table, scroll horizontally to view all columns">
        <table class="table-base table-system-table admin-master-table records-worklist-table">
            <colgroup><col style="width:14%"><col style="width:9%"><col style="width:17%"><col style="width:9%"><col style="width:11%"><col style="width:16%"><col style="width:8%"><col style="width:10%"><col style="width:6%"></colgroup>
            <thead>
                <tr>
                    <th class="text-left">Case</th>
                    <th class="text-left">Branch</th>
                    <th class="text-left">Deceased / Client</th>
                    <th class="text-left">Service</th>
                    <th class="text-left">Schedule</th>
                    <th class="table-col-number">Package Availed</th>
                    <th class="table-status-col text-left">Case Status</th>
                    <th class="table-status-col table-payment-status-col text-left">Payment Status</th>
                    <th class="table-col-actions"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                @php
                    $intermentDate = $case->deceased?->interment_at ?? $case->interment_at ?? $case->deceased?->interment;
                @endphp
                <tr class="{{ (float) $case->balance_amount > 0 ? 'row-needs-attention' : '' }}">
                    <td>
                        <div class="table-primary whitespace-nowrap records-case-code">{{ $case->case_code }}</div>
                        <div class="table-secondary">Recorded by: {{ $case->encodedBy?->name ?: 'Not recorded' }}</div>
                        <div class="table-secondary">@if($case->created_at)<span class="whitespace-nowrap">{{ $case->created_at->format('M d, Y') }}</span> <span class="whitespace-nowrap">{{ $case->created_at->format('h:i A') }}</span>@else Not recorded @endif</div>
                    </td>
                    <td>
                        <div class="table-primary whitespace-nowrap">{{ $case->branch?->branch_code ?? '-' }}</div>
                        <div class="table-secondary">{{ $case->branch?->branch_name ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="table-primary records-person-names">Deceased: {{ $case->deceased?->full_name ?? '-' }}</div>
                        <div class="table-secondary records-person-names">Client: {{ $case->client?->full_name ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="table-primary">{{ $case->service_type ?? '-' }}</div>
                        <div class="table-secondary">{{ $case->package?->name ?? $case->service_package ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="table-primary whitespace-nowrap">{{ $intermentDate ? $intermentDate->format('M d, Y') : '-' }}</div>
                        <div class="table-secondary">{{ $intermentDate && $intermentDate->format('H:i') !== '00:00' ? $intermentDate->format('h:i A') : 'Service date' }}</div>
                    </td>
                    <td class="table-col-number">
                        <div class="table-primary table-financial-total whitespace-nowrap">{{ number_format((float) $case->total_amount, 2) }}</div>
                        <div class="table-secondary table-financial-breakdown whitespace-nowrap">Paid {{ number_format((float) $case->total_paid, 2) }} &middot; Bal {{ number_format((float) $case->balance_amount, 2) }}</div>
                    </td>
                    <td class="table-status-cell">
                        <x-status-badge :status="$case->case_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->case_status))" />
                    </td>
                    <td class="table-status-cell table-payment-status-cell">
                        <x-status-badge class="table-payment-status-badge" :status="$case->payment_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->payment_status))" />
                    </td>
                    <td class="table-col-actions">
                        <a class="records-view-case" href="{{ route('owner.cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}" aria-label="View Full Information for {{ $case->case_code }}" title="View Full Information"><i class="bi bi-eye" aria-hidden="true"></i></a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="table-system-empty">No records found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="table-system-pagination">
    @include('partials.case_records_pagination', ['showSinglePageControls' => true])
</div>
</section>

</div>
@include('partials.master_records_visual_styles')
@include('partials.case_records_layout_styles')
<style>
html[data-theme] body.panel-shell-body.owner-ui:has(.owner-master-records-page) .panel-page-header { overflow:visible !important; position:relative; z-index:40; }
html[data-theme] body.panel-shell-body.owner-ui:has(.owner-master-records-page) .owner-records-back { background:var(--surface-muted) !important; color:var(--ink) !important; border:1px solid var(--border) !important; position:relative; width:44px; height:44px; padding:0 !important; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
.owner-records-back-tooltip { position:absolute; top:calc(100% + 8px); left:0; z-index:40; padding:7px 10px; border-radius:8px; border:1px solid var(--border); background:var(--card); color:var(--ink); font-size:12px; white-space:nowrap; visibility:hidden; opacity:0; pointer-events:none; }
.owner-records-back:is(:hover,:focus-visible) .owner-records-back-tooltip { visibility:visible; opacity:1; }
.owner-master-records-page .owner-records-scope { display:flex; align-items:center; flex-wrap:wrap; gap:10px 16px; margin-top:12px; font-size:12px; line-height:1.6; color:var(--ink); }
.owner-master-records-page .owner-records-scope-chip { display:inline-flex; align-items:center; gap:8px; min-height:36px; padding:6px 10px; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); color:var(--ink); text-decoration:none; }
.owner-master-records-page .owner-records-scope-chip:is(:hover,:focus-visible) { background:var(--owner-hover); }
.owner-master-records-page .owner-records-scope-chip:focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
/* Shared Master Records owns toolbar, table density, and responsive behavior. */
.owner-master-records-page .table-system-wrap { overflow-x:auto !important; }
html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .table-system-table { border-radius:0 !important; }
html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .table-system-table th:first-child,
html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .table-system-table td:first-child { padding-left:16px !important; }
html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .table-system-table .table-primary { white-space:normal !important; overflow:visible !important; text-overflow:clip !important; overflow-wrap:anywhere; }
.owner-master-records-page .records-worklist-table { width:100%; min-width:0 !important; table-layout:fixed; }
html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .records-view-case { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; min-width:0; padding:0 !important; background:transparent !important; border:0 !important; box-shadow:none !important; border-radius:6px; color:var(--ink) !important; }
html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .records-view-case:hover { background:rgba(100,116,139,.12) !important; }
html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .records-view-case:focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
@media(min-width:1100px) {
 html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page table.table-system-table.records-worklist-table { width:100% !important; min-width:0 !important; }
 html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .table-system-table :is(th,td) { padding:12px 8px !important; min-width:0 !important; }
 html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .table-system-table th { white-space:normal !important; overflow-wrap:anywhere; }
 html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .table-system-table .table-col-actions { padding-inline:10px !important; text-align:center; }
 html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .table-financial-breakdown { white-space:normal !important; }
}
@media(max-width:1099px) { .owner-master-records-page .records-worklist-table { min-width:1100px !important; } }
html[data-theme] body.panel-shell-body.owner-ui .owner-master-records-page .table-system-table tbody .table-secondary { white-space:normal !important; overflow:visible !important; text-overflow:clip !important; overflow-wrap:anywhere; }
.owner-master-records-page .admin-table-toolbar { width:100%; max-width:none; }
.owner-master-records-page .records-person-names { white-space:normal !important; overflow-wrap:anywhere; }
</style>
@endsection
