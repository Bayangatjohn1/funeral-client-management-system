@extends('layouts.panel')

@section('page_title', 'Case Records')
@section('page_desc', 'Manage ongoing and completed case records.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    $activeTab = $currentTab ?? 'all';
    $isAllTab = $activeTab === 'all';
    $isActiveTab = $activeTab === 'active';
    $isDraftTab = $activeTab === 'draft';
    $isCompletedTab = $activeTab === 'completed';
    $recordScope = $recordScope ?? 'main';
    $quickFilter = $quickFilter ?? 'all';
    $sort = $sort ?? 'newest';
    $selectedDateRange = request('date_range', 'any');
    $usesCustomDate = $selectedDateRange === 'custom'
        || (!request()->filled('date_range') && (request()->filled('request_date_from') || request()->filled('request_date_to')));
    if ($usesCustomDate) {
        $selectedDateRange = 'custom';
    }
    $openWizard = request()->boolean('open_wizard') && $isActiveTab;
    $resetUrl = route('funeral-cases.index', ['tab' => $activeTab, 'record_scope' => $recordScope]);
    $caseRecordsBranchLabel = $operationalBranch
        ? trim(($operationalBranch->branch_code ?? '') . ' - ' . ($operationalBranch->branch_name ?? ''))
        : 'Assigned Branch';
    $caseRecordsChips = collect();
    if (filled(request('q'))) {
        $caseRecordsChips->push(['icon' => 'bi-search', 'label' => 'Search: ' . request('q')]);
    }
    if (filled(request('payment_status'))) {
        $caseRecordsChips->push(['icon' => 'bi-wallet2', 'label' => 'Payment: ' . \Illuminate\Support\Str::headline(strtolower(request('payment_status')))]);
    }
    if (filled(request('case_status'))) {
        $caseRecordsChips->push(['icon' => 'bi-clipboard-check', 'label' => 'Case: ' . \Illuminate\Support\Str::headline(strtolower(request('case_status')))]);
    }
    if (filled(request('service_type'))) {
        $caseRecordsChips->push(['icon' => 'bi-tag', 'label' => 'Service: ' . request('service_type')]);
    }
    if (filled(request('package_id'))) {
        $selectedPackage = ($packages ?? collect())->firstWhere('id', (int) request('package_id'));
        $caseRecordsChips->push(['icon' => 'bi-box', 'label' => 'Package: ' . ($selectedPackage?->name ?? 'Selected Package')]);
    }
    if (!empty($selectedClient)) {
        $caseRecordsChips->push(['icon' => 'bi-person-lines-fill', 'label' => 'Representative: ' . $selectedClient->full_name]);
    }
    if (filled($datePreset ?? null)) {
        $caseRecordsChips->push(['icon' => 'bi-calendar3', 'label' => 'Encoded: ' . \Illuminate\Support\Str::headline(strtolower((string) $datePreset))]);
    }
    if (filled($intermentFrom ?? null) || filled($intermentTo ?? null)) {
        $caseRecordsChips->push(['icon' => 'bi-calendar-event', 'label' => 'Interment: ' . (($intermentFrom ?? null) ?: 'Start') . ' - ' . (($intermentTo ?? null) ?: 'Today')]);
    }

    $caseRecordsTabParams = [
        'record_scope' => $recordScope,
        'q' => request('q'),
        'case_status' => request('case_status'),
        'payment_status' => request('payment_status'),
        'service_type' => request('service_type'),
        'package_id' => request('package_id'),
        'client_id' => request('client_id'),
        'date_preset' => request('date_preset'),
        'date_from' => request('date_from'),
        'date_to' => request('date_to'),
        'date_range' => request('date_range'),
        'request_date_from' => request('request_date_from'),
        'request_date_to' => request('request_date_to'),
        'interment_from' => request('interment_from'),
        'interment_to' => request('interment_to'),
        'sort' => 'newest',
    ];

    $allTabUrl = route('funeral-cases.index', array_filter(array_merge($caseRecordsTabParams, [
        'tab' => 'all',
    ]), fn ($value) => !is_null($value) && $value !== ''));

    $activeTabUrl = route('funeral-cases.index', array_filter(array_merge($caseRecordsTabParams, [
        'tab' => 'active',
    ]), fn ($value) => !is_null($value) && $value !== ''));

    $draftTabUrl = route('funeral-cases.index', array_filter(array_merge($caseRecordsTabParams, [
        'tab' => 'draft',
    ]), fn ($value) => !is_null($value) && $value !== ''));

    $completedTabUrl = route('funeral-cases.index', array_filter(array_merge($caseRecordsTabParams, [
        'tab' => 'completed',
    ]), fn ($value) => !is_null($value) && $value !== ''));
@endphp

@if($caseRecordsChips->isNotEmpty())
    <div class="sr-only">
        @foreach($caseRecordsChips as $chip)
            <span>{{ $chip['label'] }}</span>
        @endforeach
    </div>
@endif

<style>
    .records-page {
        --records-card: #D3DEC9;
        --records-card-alt: #DCE6D6;
        --records-card-strong: #C7D5BE;
        --records-hover: #C5D3BC;
        --records-active: #B8C9AF;
        --records-border: #AEBBA8;
        --records-border-strong: #8EA083;
        --records-text: #232821;
        --records-muted: #3F4C3E;
        box-sizing: border-box;
        min-height: 100%;
        padding: .9rem var(--panel-content-inline) 20px;
        color: var(--records-text);
        font-family: var(--font-body);
        background:
            linear-gradient(90deg, rgba(73, 87, 69, 0.04) 0 1px, transparent 1px),
            linear-gradient(180deg, rgba(73, 87, 69, 0.034) 0 1px, transparent 1px),
            repeating-linear-gradient(135deg, rgba(73, 87, 69, 0.02) 0 1px, transparent 1px 12px);
        background-size: 44px 44px, 44px 44px, 16px 16px;
        transition: opacity .16s ease, transform .16s ease;
    }

    .records-page.is-updating {
        opacity: .72;
        transform: translateY(2px);
        pointer-events: none;
    }

    .records-page,
    .records-page *,
    .records-page *::before,
    .records-page *::after {
        box-shadow: none !important;
        filter: none !important;
        backdrop-filter: none !important;
    }

    .records-page h1,
    .records-page h2,
    .records-page h3,
    .records-page h4,
    .records-page .table-system-list-title,
    .records-page .table-primary {
        font-family: var(--font-heading);
        letter-spacing: 0;
        color: var(--records-text) !important;
    }

    .records-page p,
    .records-page small,
    .records-page label,
    .records-page .table-secondary,
    .records-page .table-system-list-copy,
    .records-page .case-compact-advanced-note {
        color: var(--records-muted) !important;
        opacity: 1 !important;
    }

    .records-page label,
    .records-page th,
    .records-page .case-compact-chip,
    .records-page .table-quick-tab,
    .records-page .status-badge {
        font-weight: 650 !important;
        letter-spacing: 0 !important;
    }

    .records-page a[href],
    .records-page button,
    .records-page select,
    .records-page input,
    .records-page [role="button"],
    .records-page [data-clickable-row] {
        transition: background-color .14s ease, border-color .14s ease, color .14s ease;
    }

    .records-page a[href],
    .records-page button,
    .records-page select,
    .records-page [role="button"],
    .records-page [data-clickable-row] {
        cursor: pointer;
    }

    .records-page a[href]:focus,
    .records-page button:focus,
    .records-page select:focus,
    .records-page input:focus,
    .records-page [role="button"]:focus,
    .records-page [data-clickable-row]:focus,
    .records-page a[href]:focus-visible,
    .records-page button:focus-visible,
    .records-page select:focus-visible,
    .records-page input:focus-visible,
    .records-page [role="button"]:focus-visible,
    .records-page [data-clickable-row]:focus-visible {
        outline: none !important;
        outline-offset: 0 !important;
    }

    .records-page .flash-success,
    .records-page .flash-info,
    .records-page .list-card,
    .records-page .case-records-top-wrapper,
    .records-page .table-system-toolbar,
    .records-page .table-system-list,
    .records-page .table-system-list-header,
    .records-page .table-system-wrap,
    .records-page .table-system-pagination,
    .records-page .case-compact-filter,
    .records-page .case-compact-search-row,
    .records-page .case-compact-filter-bar,
    .records-page .case-compact-advanced,
    .records-page .case-compact-popover,
    .records-page .case-records-tabs-row,
    .records-page .case-records-quick-row {
        background: var(--records-card) !important;
        border-color: var(--records-border) !important;
        border-radius: 8px !important;
        color: var(--records-text) !important;
    }

    .records-page .case-records-top-wrapper,
    .records-page .table-system-toolbar,
    .records-page .case-records-tabs-row,
    .records-page .case-records-quick-row {
        overflow: visible;
    }

    .records-page .table-system-list-header,
    .records-page .case-compact-search-row,
    .records-page .case-compact-filter-bar,
    .records-page .case-records-tabs-row,
    .records-page .case-records-quick-row,
    .records-page .table-system-pagination {
        background: var(--records-card-alt) !important;
    }

    .records-page .case-records-top-wrapper {
        display: flex;
        flex-direction: column;
        gap: .75rem;
        padding: .85rem;
        margin-bottom: 1rem;
    }

    .records-page .table-system-toolbar,
    .records-page .case-records-controls {
        padding: 0 !important;
        background: transparent !important;
        border: 0 !important;
    }

    .records-page .case-compact-filter {
        display: flex;
        flex-direction: column;
        gap: .75rem;
        background: transparent !important;
        border: 0 !important;
    }

    .records-page .case-compact-search-row,
    .records-page .case-compact-filter-bar,
    .records-page .case-records-tabs-row,
    .records-page .case-records-quick-row {
        padding: .75rem;
        border: 1px solid var(--records-border);
    }

    .records-page .case-records-tabs-row,
    .records-page .case-records-quick-row {
        background: transparent !important;
        border-color: transparent !important;
        padding: 0;
    }

    .records-page .case-records-tabs,
    .records-page .table-system-quick-tabs {
        background: var(--records-card-alt) !important;
        border: 1px solid var(--records-border) !important;
        border-radius: 8px !important;
        padding: .35rem;
        gap: .35rem;
    }

    .records-page .case-compact-search-row,
    .records-page .case-compact-filter-bar {
        gap: .65rem;
    }

    .records-page .case-compact-field label,
    .records-page .case-compact-pop-field label,
    .records-page .table-toolbar-label {
        font-size: .74rem;
        color: var(--records-muted) !important;
        text-transform: none;
    }

    .records-page .case-compact-input,
    .records-page .case-compact-select,
    .records-page .case-compact-date-select,
    .records-page .case-compact-sort-select,
    .records-page .case-compact-branch,
    .records-page .case-compact-seg,
    .records-page .case-compact-seg-item,
    .records-page .case-compact-more,
    .records-page .case-compact-chip,
    .records-page .case-compact-reset,
    .records-page .case-compact-apply,
    .records-page .case-compact-pop-input,
    .records-page .case-compact-pop-apply,
    .records-page .case-compact-pop-reset,
    .records-page .case-compact-advanced-clear,
    .records-page .table-quick-tab,
    .records-page .btn-secondary,
    .records-page .btn-outline,
    .records-page .btn-filter-reset {
        min-height: 40px;
        background: var(--records-card-alt) !important;
        border: 1px solid var(--records-border) !important;
        border-radius: 8px !important;
        color: var(--records-text) !important;
    }

    .records-page .case-compact-date-filter {
        position: relative;
        display: inline-flex;
        align-items: center;
        min-width: 12.25rem;
        padding: 0 !important;
    }

    .records-page .case-compact-sort-filter {
        position: relative;
        display: inline-flex;
        align-items: center;
        min-width: 12.25rem;
        padding: 0 !important;
    }

    .records-page .case-compact-date-select,
    .records-page .case-compact-sort-select {
        width: 100%;
        min-height: 40px;
        padding: 0 2.3rem 0 2.35rem;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        font-weight: 650;
    }

    .records-page .case-compact-date-icon,
    .records-page .case-compact-sort-icon,
    .records-page .case-compact-date-filter > .case-compact-date-chev,
    .records-page .case-compact-sort-filter > .case-compact-sort-chev {
        position: absolute;
        top: 50%;
        z-index: 2;
        color: #3E4A3D !important;
        pointer-events: none;
        transform: translateY(-50%);
    }

    .records-page .case-compact-date-icon {
        left: .85rem;
    }

    .records-page .case-compact-sort-icon {
        left: .85rem;
    }

    .records-page .case-compact-date-filter > .case-compact-date-chev,
    .records-page .case-compact-sort-filter > .case-compact-sort-chev {
        right: .85rem;
        font-size: .82rem;
    }

    .records-page .case-compact-date-filter > .case-compact-date-chev,
    .records-page .case-compact-sort-filter > .case-compact-sort-chev,
    .records-page .case-compact-branch > .case-compact-select-chev {
        transition: transform .16s ease, color .16s ease, opacity .16s ease;
        transform-origin: center;
    }

    .records-page .case-compact-date-filter.is-open > .case-compact-date-chev,
    .records-page .case-compact-sort-filter.is-open > .case-compact-sort-chev,
    .records-page .case-compact-branch.is-open > .case-compact-select-chev {
        color: var(--records-text) !important;
        transform: translateY(-50%) rotate(180deg);
    }

    .records-page .case-compact-date-filter .case-compact-custom {
        position: absolute;
        left: 0;
        top: calc(100% + .45rem);
        z-index: 40;
    }

    .records-page .case-compact-input::placeholder {
        color: var(--records-muted) !important;
        opacity: 1;
    }

    .records-page .case-compact-apply,
    .records-page .case-compact-pop-apply,
    .records-page .btn-secondary {
        background: #3E4A3D !important;
        border-color: #3E4A3D !important;
        color: #FFFDF7 !important;
    }

    .records-page .case-compact-input:hover,
    .records-page .case-compact-select:hover,
    .records-page .case-compact-date-select:hover,
    .records-page .case-compact-sort-select:hover,
    .records-page .case-compact-branch:hover,
    .records-page .case-compact-seg-item:hover,
    .records-page .case-compact-more:hover,
    .records-page .case-compact-chip:hover,
    .records-page .case-compact-reset:hover,
    .records-page .case-compact-pop-reset:hover,
    .records-page .case-compact-advanced-clear:hover,
    .records-page .table-quick-tab:hover,
    .records-page .btn-outline:hover,
    .records-page .btn-filter-reset:hover {
        background: var(--records-hover) !important;
        border-color: var(--records-border-strong) !important;
        color: var(--records-text) !important;
        transform: none !important;
    }

    .records-page .case-compact-apply:hover,
    .records-page .case-compact-pop-apply:hover,
    .records-page .btn-secondary:hover {
        background: #2F3A2E !important;
        border-color: #2F3A2E !important;
        color: #FFFDF7 !important;
        transform: none !important;
    }

    .records-page .case-compact-seg-item.active,
    .records-page .case-compact-date-filter:has(.case-compact-date-select[value="CUSTOM"]),
    .records-page .case-compact-more.active,
    .records-page .table-quick-tab-active,
    .records-page .table-quick-tab[aria-selected="true"],
    .records-page .case-compact-chip-locked {
        background: var(--records-active) !important;
        border-color: #3E4A3D !important;
        color: var(--records-text) !important;
    }

    .records-page .case-compact-seg-item.active,
    .records-page .case-compact-more.active,
    .records-page .table-quick-tab-active {
        font-weight: 700 !important;
    }

    .records-page .case-compact-branch > i,
    .records-page .case-compact-select-chev,
    .records-page .case-compact-date-chev,
    .records-page .case-compact-sort-chev,
    .records-page .case-compact-chip i,
    .records-page .table-quick-tab i,
    .records-page .case-compact-reset i,
    .records-page .case-compact-apply i {
        color: #3E4A3D !important;
        opacity: 1 !important;
    }

    .records-page .case-compact-popover {
        min-width: 25rem;
        padding: .85rem;
        background: var(--records-card) !important;
        border: 1px solid var(--records-border) !important;
    }

    .records-page .case-compact-apply i,
    .records-page .case-compact-pop-apply i,
    .records-page .btn-secondary i {
        color: #FFFDF7 !important;
    }

    .records-page .table-system-list {
        overflow: hidden;
        border: 1px solid var(--records-border);
    }

    .records-page .table-system-list-header {
        padding: .9rem 1rem;
        border-bottom: 1px solid var(--records-border);
    }

    .records-page .table-system-list-title {
        font-size: 1.05rem;
        font-weight: 700;
    }

    .records-page .table-system-wrap {
        border: 0 !important;
        border-radius: 0 !important;
        background: var(--records-card) !important;
    }

    .records-page .table-system-table {
        background: transparent !important;
        color: var(--records-text);
    }

    .records-page .table-system-table thead tr,
    .records-page .table-system-table thead th {
        background: var(--records-card-strong) !important;
        color: var(--records-muted) !important;
        border-color: var(--records-border) !important;
    }

    .records-page .table-system-table tbody td {
        background: var(--records-card) !important;
        border-color: var(--records-border) !important;
        color: var(--records-text) !important;
    }

    .records-page .table-system-table tbody tr:nth-child(even) td {
        background: var(--records-card-alt) !important;
    }

    .records-page .table-system-table tbody tr:hover td,
    .records-page .table-system-table tr[data-clickable-row]:focus-visible td {
        background: var(--records-hover) !important;
        color: var(--records-text) !important;
    }

    .records-page .table-system-table .row-needs-attention td:first-child {
        box-shadow: inset 4px 0 0 #9E4B3F !important;
    }

    .records-page .status-badge,
    .records-page .table-payment-status-badge {
        background: transparent !important;
        border: 1.5px solid var(--records-border-strong) !important;
        border-radius: 8px !important;
        color: var(--records-text) !important;
    }

    .records-page .table-system-empty {
        background: var(--records-card-alt) !important;
        color: var(--records-muted) !important;
    }

    .records-page .table-wrapper,
    .records-page table,
    .records-page tbody,
    .records-page tr,
    .records-page td,
    .records-page .table-base,
    .records-page .table-system-table,
    .records-page .table-system-table tbody,
    .records-page .records-worklist-table {
        background-color: var(--records-card) !important;
    }

    .records-page .table-system-list,
    .records-page .table-system-wrap {
        background: var(--records-card) !important;
    }

    .records-page .table-system-table tbody tr:nth-child(even),
    .records-page .table-system-table tbody tr:nth-child(even) td {
        background-color: var(--records-card-alt) !important;
    }

    .records-page .table-system-table tbody tr:hover,
    .records-page .table-system-table tbody tr:hover td,
    .records-page .table-system-table tr[data-clickable-row]:focus-visible,
    .records-page .table-system-table tr[data-clickable-row]:focus-visible td {
        background-color: var(--records-hover) !important;
    }

    .records-page #caseEditOverlay {
        backdrop-filter: none !important;
    }

    .records-page #caseEditSheet,
    .records-page #caseEditContent,
    .records-page #caseEditClose {
        background: var(--records-card) !important;
        border-color: var(--records-border) !important;
        box-shadow: none !important;
    }

    @media (max-width: 767px) {
        .records-page {
            padding-inline: .75rem;
        }

        .records-page .case-records-top-wrapper {
            padding: .65rem;
        }

        .records-page .case-compact-search-row,
        .records-page .case-compact-filter-bar,
        .records-page .case-records-tabs-row,
        .records-page .case-records-quick-row {
            padding: .65rem;
        }
    }

    html:not([data-theme='dark']) .records-page {
        padding-top: 1.15rem;
        background:
            linear-gradient(90deg, rgba(73, 87, 69, 0.04) 0 1px, transparent 1px),
            linear-gradient(180deg, rgba(73, 87, 69, 0.034) 0 1px, transparent 1px),
            repeating-linear-gradient(135deg, rgba(73, 87, 69, 0.02) 0 1px, transparent 1px 12px);
        background-size: 44px 44px, 44px 44px, 16px 16px;
    }

    html:not([data-theme='dark']) .records-page .case-records-top-wrapper,
    html:not([data-theme='dark']) .records-page .table-system-list,
    html:not([data-theme='dark']) .records-page .table-system-list-header,
    html:not([data-theme='dark']) .records-page .table-system-wrap,
    html:not([data-theme='dark']) .records-page .table-system-table,
    html:not([data-theme='dark']) .records-page .table-system-table tbody,
    html:not([data-theme='dark']) .records-page .table-wrapper {
        background: var(--records-card) !important;
        border-color: var(--records-border) !important;
        box-shadow: none !important;
    }

    html:not([data-theme='dark']) .records-page .case-compact-search-row,
    html:not([data-theme='dark']) .records-page .case-compact-filter-bar,
    html:not([data-theme='dark']) .records-page .case-records-tabs,
    html:not([data-theme='dark']) .records-page .table-system-quick-tabs,
    html:not([data-theme='dark']) .records-page .table-system-list-header,
    html:not([data-theme='dark']) .records-page .table-system-table thead tr,
    html:not([data-theme='dark']) .records-page .table-system-table thead th {
        background: var(--records-card-strong) !important;
        border-color: var(--records-border) !important;
    }

    html:not([data-theme='dark']) .records-page .case-compact-input,
    html:not([data-theme='dark']) .records-page .case-compact-select,
    html:not([data-theme='dark']) .records-page .case-compact-date-select,
    html:not([data-theme='dark']) .records-page .case-compact-sort-select,
    html:not([data-theme='dark']) .records-page .case-compact-branch,
    html:not([data-theme='dark']) .records-page .case-compact-more,
    html:not([data-theme='dark']) .records-page .case-compact-reset,
    html:not([data-theme='dark']) .records-page .case-compact-pop-input,
    html:not([data-theme='dark']) .records-page .case-compact-pop-reset,
    html:not([data-theme='dark']) .records-page .case-compact-advanced-clear,
    html:not([data-theme='dark']) .records-page .table-quick-tab,
    html:not([data-theme='dark']) .records-page .case-compact-chip {
        background: var(--records-card-alt) !important;
        border-color: var(--records-border) !important;
        color: var(--records-text) !important;
    }

    html:not([data-theme='dark']) .records-page .case-compact-date-filter,
    html:not([data-theme='dark']) .records-page .case-compact-sort-filter {
        min-width: 12.25rem;
        background: transparent !important;
        border: 0 !important;
    }

    html:not([data-theme='dark']) .records-page .case-compact-sort-filter {
        min-width: 12.25rem;
    }

    html:not([data-theme='dark']) .records-page .case-compact-date-select,
    html:not([data-theme='dark']) .records-page .case-compact-sort-select {
        background-image: none !important;
        padding-left: 2.25rem !important;
        padding-right: 2.25rem !important;
    }

    html:not([data-theme='dark']) .records-page .case-compact-date-filter > .case-compact-date-chev,
    html:not([data-theme='dark']) .records-page .case-compact-sort-filter > .case-compact-sort-chev {
        right: .78rem;
    }

    html:not([data-theme='dark']) .records-page .case-compact-date-filter::after,
    html:not([data-theme='dark']) .records-page .case-compact-date-filter .case-compact-custom::before,
    html:not([data-theme='dark']) .records-page .case-compact-date-filter .case-compact-custom::after {
        content: none !important;
        display: none !important;
    }

    html:not([data-theme='dark']) .records-page .case-compact-date-filter .case-compact-custom {
        left: auto;
        right: 0;
        top: calc(100% + .5rem);
        width: min(22rem, calc(100vw - 2rem));
    }

    html:not([data-theme='dark']) .records-page .case-compact-popover {
        min-width: 0;
        width: 100%;
        padding: .85rem;
        background: var(--records-card) !important;
        border-color: var(--records-border) !important;
    }

    html:not([data-theme='dark']) .records-page .case-compact-pop-fields {
        grid-template-columns: 1fr 1fr;
        gap: .65rem;
    }

    html:not([data-theme='dark']) .records-page .table-system-table tbody tr,
    html:not([data-theme='dark']) .records-page .table-system-table tbody tr td {
        background: var(--records-card) !important;
    }

    html:not([data-theme='dark']) .records-page .table-system-table tbody tr:nth-child(even),
    html:not([data-theme='dark']) .records-page .table-system-table tbody tr:nth-child(even) td {
        background: var(--records-card-alt) !important;
    }

    html:not([data-theme='dark']) .records-page .case-compact-input:hover,
    html:not([data-theme='dark']) .records-page .case-compact-select:hover,
    html:not([data-theme='dark']) .records-page .case-compact-date-select:hover,
    html:not([data-theme='dark']) .records-page .case-compact-sort-select:hover,
    html:not([data-theme='dark']) .records-page .case-compact-branch:hover,
    html:not([data-theme='dark']) .records-page .case-compact-more:hover,
    html:not([data-theme='dark']) .records-page .case-compact-reset:hover,
    html:not([data-theme='dark']) .records-page .case-compact-pop-reset:hover,
    html:not([data-theme='dark']) .records-page .case-compact-advanced-clear:hover,
    html:not([data-theme='dark']) .records-page .table-quick-tab:hover,
    html:not([data-theme='dark']) .records-page .case-compact-chip:hover,
    html:not([data-theme='dark']) .records-page .table-system-table tbody tr:hover,
    html:not([data-theme='dark']) .records-page .table-system-table tbody tr:hover td {
        background: var(--records-hover) !important;
        border-color: var(--records-border-strong) !important;
        color: var(--records-text) !important;
    }

    html:not([data-theme='dark']) .records-page .case-compact-date-select:focus,
    html:not([data-theme='dark']) .records-page .case-compact-sort-select:focus,
    html:not([data-theme='dark']) .records-page .case-compact-input:focus,
    html:not([data-theme='dark']) .records-page .case-compact-pop-input:focus {
        background: var(--records-card-alt) !important;
        border-color: var(--records-border-strong) !important;
        color: var(--records-text) !important;
    }

    html:not([data-theme='dark']) .records-page .case-compact-seg-item.active,
    html:not([data-theme='dark']) .records-page .case-compact-more.active,
    html:not([data-theme='dark']) .records-page .table-quick-tab-active,
    html:not([data-theme='dark']) .records-page .case-compact-chip-locked {
        background: var(--records-active) !important;
        border-color: #3E4A3D !important;
        color: var(--records-text) !important;
    }

    .records-page .case-records-top-wrapper {
        display: grid !important;
        grid-template-columns: minmax(24rem, 46rem) minmax(2rem, 1fr) auto auto;
        grid-template-areas:
            "search spacer filters actions"
            "advanced advanced advanced advanced";
        align-items: center;
        gap: .65rem;
        height: auto !important;
        min-height: 0 !important;
        padding: .85rem !important;
        margin: 0 0 .6rem !important;
        overflow: visible !important;
    }

    .records-page .case-records-controls,
    .records-page .case-records-controls .case-compact-filter,
    .records-page .case-records-controls .case-compact-search-row,
    .records-page .case-records-quick-row {
        display: contents !important;
        height: auto !important;
        min-height: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    .records-page .case-records-controls .case-compact-search-field {
        grid-area: search;
        min-width: 0;
        max-width: 46rem;
    }

    .records-page .case-records-controls .case-compact-search-field label {
        display: none !important;
    }

    .records-page .case-records-controls .case-compact-filter-bar {
        grid-area: filters;
        display: inline-flex !important;
        flex-wrap: nowrap !important;
        gap: .55rem !important;
        justify-content: flex-end;
        justify-self: end;
        width: auto !important;
        height: auto !important;
        min-height: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    html:not([data-theme='dark']) .records-page .case-records-controls .case-compact-filter-bar {
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
    }

    .records-page .case-records-controls .case-compact-actions {
        grid-area: actions;
        display: inline-flex !important;
        align-items: center;
        justify-content: flex-end;
        justify-self: end;
        width: auto !important;
        margin-left: 0 !important;
    }

    .records-page .case-records-controls .case-compact-advanced {
        grid-area: advanced;
        position: fixed !important;
        inset: 0 !important;
        z-index: 1200 !important;
        display: flex !important;
        justify-content: flex-end !important;
        width: 100%;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    .records-page .case-records-controls .case-compact-advanced[hidden] {
        display: none !important;
    }

    .records-page .case-records-tabs-row {
        grid-area: tabs;
        display: flex !important;
        align-items: center;
        height: auto !important;
        min-height: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    .records-page .table-system-quick-tabs {
        grid-area: quick;
        justify-self: start;
    }

    .records-page .case-records-quick-chips {
        grid-area: branch;
        justify-self: end;
        align-self: end;
        width: auto !important;
        padding: 0 !important;
    }

    .records-page .case-compact-search-field,
    .records-page .case-compact-search-field .case-compact-input {
        height: 44px;
    }

    .records-page .case-compact-input,
    .records-page .case-compact-date-select,
    .records-page .case-compact-more,
    .records-page .case-compact-reset,
    .records-page .case-compact-apply,
    .records-page .table-quick-tab,
    .records-page .case-compact-chip {
        min-height: 40px !important;
    }

    .records-page .case-compact-search-field .case-compact-input {
        display: block;
        padding-left: 2.55rem !important;
        padding-right: 2.65rem !important;
    }

    .records-page .case-compact-date-filter {
        position: relative !important;
        overflow: visible !important;
        min-width: 12.75rem !important;
    }

    .records-page .case-compact-date-select {
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        background-image: none !important;
    }

    .records-page .case-compact-sort-select {
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        background-image: none !important;
    }

    .records-page .case-compact-sort-select::-ms-expand {
        display: none;
    }

    .records-page .case-compact-date-select::-ms-expand {
        display: none;
    }

    .records-page .case-compact-date-filter > .case-compact-date-chev,
    .records-page .case-compact-date-filter > .case-compact-date-icon,
    .records-page .case-compact-sort-filter > .case-compact-sort-chev,
    .records-page .case-compact-sort-filter > .case-compact-sort-icon {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
    }

    .records-page .case-compact-date-filter .case-compact-custom {
        position: absolute !important;
        top: calc(100% + .5rem) !important;
        right: 0 !important;
        left: auto !important;
        z-index: 80 !important;
        width: min(22rem, calc(100vw - 2rem)) !important;
    }

    .records-page .case-compact-popover {
        position: relative !important;
        top: auto !important;
        right: auto !important;
        width: 100% !important;
        min-width: 0 !important;
    }

    .records-page .table-system-list,
    .records-page .table-system-wrap,
    .records-page .table-wrapper {
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
    }

    .records-page .table-system-wrap,
    .records-page .table-wrapper {
        overflow-x: auto !important;
        overflow-y: visible !important;
    }

    @media (max-width: 1120px) {
        .records-page .case-records-top-wrapper {
            grid-template-columns: 1fr auto;
            grid-template-areas:
                "search search"
                "filters actions"
                "advanced advanced";
        }
    }

    @media (max-width: 760px) {
        .records-page .case-records-top-wrapper {
            grid-template-columns: 1fr;
            grid-template-areas:
                "search"
                "filters"
                "actions"
                "advanced";
        }

        .records-page .case-records-controls .case-compact-filter-bar,
        .records-page .case-records-controls .case-compact-actions,
        .records-page .case-records-tabs,
        .records-page .table-system-quick-tabs,
        .records-page .case-records-quick-chips {
            width: 100% !important;
            justify-content: flex-start;
            justify-self: stretch;
        }
    }

    .records-page > .case-records-tabs-row {
        display: flex !important;
        align-items: center;
        margin: 0 0 .65rem !important;
        padding: 0 !important;
        background: transparent !important;
        border: 0 !important;
    }

    .records-page > .case-records-tabs-row + .table-system-list {
        margin-top: 0 !important;
    }
</style>

<div class="records-page">
    <div class="panel-page-header sr-only" aria-hidden="true">Case Records</div>
    @if(session('success'))
        <div class="flash-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('summary') && $isActiveTab)
        <div class="list-card p-5 text-sm text-slate-700">
            <div class="mb-3 text-base font-semibold text-slate-900">Last Saved Summary</div>
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                <div><span class="font-semibold text-slate-900">Package:</span> {{ session('summary.package') }}</div>
                <div><span class="font-semibold text-slate-900">Subtotal:</span> {{ number_format(session('summary.subtotal'), 2) }}</div>
                <div><span class="font-semibold text-slate-900">Discount:</span> {{ number_format(session('summary.discount'), 2) }}</div>
                <div><span class="font-semibold text-slate-900">Discount Rule:</span> {{ session('summary.discount_source', 'NONE') }}</div>
                <div><span class="font-semibold text-slate-900">Total:</span> {{ number_format(session('summary.total'), 2) }}</div>
                <div><span class="font-semibold text-slate-900">Payment Status:</span> {{ session('summary.payment_status') }}</div>
            </div>
        </div>
    @endif

    @if(!empty($canEncodeAnyBranch) && $canEncodeAnyBranch)
        <div class="flash-info">
            Other-branch records remain completed-only and are managed under <strong>Branch Reports</strong>.
        </div>
    @endif

    @if($openWizard)
            <div class="p-4 md:p-5">
                @php
                    $showCancelButton = false;
                    $cancelUrl = route('funeral-cases.index', ['tab' => 'active', 'record_scope' => $recordScope]);
                    $formAction = route('intake.main.store');
                    $entryMode = 'main';
                @endphp
                @include('staff.intake._form')
            </div>
        @else
            <div class="case-records-top-wrapper">
            <div class="table-system-toolbar case-records-controls">
                @include('partials.case_filter_toolbar', [
                    'action' => route('funeral-cases.index'),
                    'resetUrl' => $resetUrl,
                    'branchMode' => 'locked',
                    'assignedBranch' => $operationalBranch ?? null,
                    'branchId' => $operationalBranch?->id ?? auth()->user()?->branch_id,
                    'branches' => $branches,
                    'datePreset' => $datePreset ?? '',
                    'dateFrom' => $dateFrom ?? null,
                    'dateTo' => $dateTo ?? null,
                    'intermentFrom' => $intermentFrom ?? null,
                    'intermentTo' => $intermentTo ?? null,
                    'serviceTypes' => $serviceTypes ?? collect(),
                    'packages' => $packages ?? collect(),
                    'hiddenInputs' => ['tab' => $activeTab, 'record_scope' => $recordScope, 'client_id' => request('client_id')],
                    'showVerificationStatus' => false,
                    'showPackage' => true,
                    'showEncodedBy' => false,
                    'showBranchChip' => false,
                    'showBranchField' => false,
                    'showInlineChips' => false,
                    'showMoreFilters' => false,
                    'showSort' => true,
                    'sortOptions' => $sortOptions ?? [],
                    'sort' => $sort,
                ])

                <form id="caseRecordsFilterForm" method="GET" action="{{ route('funeral-cases.index') }}" class="table-toolbar hidden" data-table-toolbar data-search-debounce="400">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input type="hidden" name="record_scope" value="{{ $recordScope }}">
                    @if(request('client_id'))
                        <input type="hidden" name="client_id" value="{{ request('client_id') }}">
                    @endif
                    <input type="hidden" name="branch_id" value="{{ $operationalBranch?->id ?? auth()->user()?->branch_id }}">

                    <div class="table-toolbar-field">
                        <label for="case-record-search" class="table-toolbar-label">Search</label>
                        <input
                            id="case-record-search"
                            name="q"
                            value="{{ request('q') }}"
                            class="form-input table-toolbar-search"
                            data-table-search
                            placeholder="Search case, client, or deceased..."
                            pattern="[A-Za-zÀ-öø-ÿĀ-žḀ-ỿ0-9.'\- ]+"
                            title="Letters (including accented like Ñ, É), numbers, spaces, apostrophes, dots, and hyphens only"
                        >
                    </div>

                    <div class="table-toolbar-field">
                        <label for="case-record-payment-status" class="table-toolbar-label">Payment Status</label>
                        <select id="case-record-payment-status" name="payment_status" class="form-select table-toolbar-select">
                            <option value="">All Payment Status</option>
                            <option value="UNPAID" @selected(request('payment_status') === 'UNPAID')>Unpaid</option>
                            <option value="PARTIAL" @selected(request('payment_status') === 'PARTIAL')>Partial</option>
                            <option value="PAID" @selected(request('payment_status') === 'PAID')>Paid</option>
                        </select>
                    </div>

                    <div class="table-toolbar-field">
                        <label for="case-record-date-range" class="table-toolbar-label">Date</label>
                        <select id="case-record-date-range" name="date_range" class="form-select table-toolbar-select">
                            <option value="any" @selected($selectedDateRange === 'any')>All Dates</option>
                            <option value="today" @selected($selectedDateRange === 'today')>Today</option>
                            <option value="7d" @selected($selectedDateRange === '7d')>Last 7 Days</option>
                            <option value="30d" @selected($selectedDateRange === '30d')>Last 30 Days</option>
                            <option value="this_month" @selected($selectedDateRange === 'this_month')>This Month</option>
                            <option value="custom" @selected($selectedDateRange === 'custom')>Custom Range</option>
                        </select>
                    </div>

                    <div class="table-toolbar-field" data-custom-date-field @if(!$usesCustomDate) hidden @endif>
                        <label for="case-record-date-from" class="table-toolbar-label">Date From</label>
                        <input
                            id="case-record-date-from"
                            type="date"
                            name="request_date_from"
                            value="{{ request('request_date_from') }}"
                            class="form-input table-toolbar-select"
                            data-custom-date-input
                            @if(!$usesCustomDate) disabled @endif
                        >
                    </div>

                    <div class="table-toolbar-field" data-custom-date-field @if(!$usesCustomDate) hidden @endif>
                        <label for="case-record-date-to" class="table-toolbar-label">To Date</label>
                        <input
                            id="case-record-date-to"
                            type="date"
                            name="request_date_to"
                            value="{{ request('request_date_to') }}"
                            class="form-input table-toolbar-select"
                            data-custom-date-input
                            @if(!$usesCustomDate) disabled @endif
                        >
                    </div>

                    <div class="table-toolbar-field">
                        <label for="case-record-sort" class="table-toolbar-label">Sort</label>
                        <select id="case-record-sort" name="sort" class="form-select table-toolbar-sort" data-table-sort>
                            @foreach(($sortOptions ?? []) as $sortKey => $sortLabel)
                                <option value="{{ $sortKey }}" @selected($sort === $sortKey)>{{ $sortLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-toolbar-reset-wrap">
                        <span class="table-toolbar-label opacity-0 select-none">Actions</span>
                        <div class="filter-actions">
                            <a href="{{ route('funeral-cases.index', ['tab' => $activeTab, 'record_scope' => $recordScope]) }}" class="btn-outline btn-filter-reset">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Reset</span>
                            </a>
                            <button type="submit" class="btn-secondary">
                                <i class="bi bi-funnel"></i>
                                <span>Apply</span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>
            </div>{{-- /.case-records-top-wrapper --}}

            <div class="case-records-tabs-row">
                <div class="table-quick-tabs case-records-tabs" role="tablist" aria-label="Case record tabs">
                    <a
                        href="{{ $allTabUrl }}"
                        role="tab"
                        aria-selected="{{ $isAllTab ? 'true' : 'false' }}"
                        class="table-quick-tab {{ $isAllTab ? 'table-quick-tab-active' : '' }}"
                    >
                        All
                    </a>
                    <a
                        href="{{ $draftTabUrl }}"
                        role="tab"
                        aria-selected="{{ $isDraftTab ? 'true' : 'false' }}"
                        class="table-quick-tab {{ $isDraftTab ? 'table-quick-tab-active' : '' }}"
                    >
                        Draft
                    </a>
                    <a
                        href="{{ $activeTabUrl }}"
                        role="tab"
                        aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                        class="table-quick-tab {{ $isActiveTab ? 'table-quick-tab-active' : '' }}"
                    >
                        Active
                    </a>
                    <a
                        href="{{ $completedTabUrl }}"
                        role="tab"
                        aria-selected="{{ $isCompletedTab ? 'true' : 'false' }}"
                        class="table-quick-tab {{ $isCompletedTab ? 'table-quick-tab-active' : '' }}"
                    >
                        Completed
                    </a>
                </div>
            </div>

            <div class="table-system-list">
                <div class="table-system-list-header">
                    <div>
                        <div class="table-system-list-title">
                            {{ !empty($selectedClient)
                                ? 'Cases for ' . $selectedClient->full_name
                                : ($isAllTab ? 'All Case Records' : ($isActiveTab ? 'Active Case Records' : ($isDraftTab ? 'Draft Case Records' : 'Completed Case Records'))) }}
                        </div>
                        <div class="table-system-list-copy">
                            {{ !empty($selectedClient)
                                ? 'Showing case records linked to this family representative.'
                                : ($isAllTab
                                ? 'Review all branch case records in one simplified list.'
                                : ($isActiveTab
                                ? 'Track ongoing case activity, balances, and workflow status.'
                                : ($isDraftTab
                                ? 'Review saved draft records before they move into active work.'
                                : 'Review completed records, payment standing, and follow-up actions.'))) }}
                        </div>
                    </div>
                </div>

                @if($isDraftTab)
                    <div class="border-t border-[var(--records-border)] bg-[var(--records-card-alt)] px-3 py-3">
                        <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-[var(--records-text)]">Incomplete Intake Drafts</div>
                                <div class="text-xs font-semibold text-[var(--records-muted)]">Saved intake work that has not created official client, deceased, case, or payment records yet.</div>
                            </div>
                            <a href="{{ route('intake.main.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-[var(--records-border)] bg-white px-3 py-2 text-xs font-bold text-[var(--records-text)] hover:bg-[var(--records-hover)]">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                New Intake
                            </a>
                        </div>

                        <div class="table-wrapper table-system-wrap">
                            <table class="table-base table-system-table">
                                <thead>
                                    <tr>
                                        <th class="text-left">Draft</th>
                                        <th class="text-left">Family / Client</th>
                                        <th class="text-left">Deceased</th>
                                        <th class="text-left">Step</th>
                                        <th class="text-left">Last Saved</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($intakeDrafts ?? collect()) as $draft)
                                        @php
                                            $fields = $draft->payload['fields'] ?? [];
                                            $clientDraftName = trim(implode(' ', array_filter([
                                                $fields['client_first_name'] ?? null,
                                                $fields['client_middle_name'] ?? null,
                                                $fields['client_last_name'] ?? null,
                                                $fields['client_suffix'] ?? null,
                                            ])));
                                            $deceasedDraftName = trim(implode(' ', array_filter([
                                                $fields['deceased_first_name'] ?? null,
                                                $fields['deceased_middle_name'] ?? null,
                                                $fields['deceased_last_name'] ?? null,
                                                $fields['deceased_suffix'] ?? null,
                                            ])));
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="table-primary whitespace-nowrap">{{ $draft->draft_number }}</div>
                                                <div class="table-secondary">{{ $draft->branch?->branch_code ?? 'Assigned Branch' }}</div>
                                            </td>
                                            <td>
                                                <div class="table-primary">{{ \Illuminate\Support\Str::limit($clientDraftName !== '' ? $clientDraftName : '-', 30) }}</div>
                                                <div class="table-secondary">{{ $fields['client_contact_number'] ?? 'Contact pending' }}</div>
                                            </td>
                                            <td>
                                                <div class="table-primary">{{ \Illuminate\Support\Str::limit($deceasedDraftName !== '' ? $deceasedDraftName : '-', 30) }}</div>
                                                <div class="table-secondary">Incomplete intake</div>
                                            </td>
                                            <td>Step {{ $draft->current_step }}</td>
                                            <td>{{ optional($draft->last_saved_at ?? $draft->updated_at)->format('M d, Y h:i A') }}</td>
                                            <td>
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('intake.drafts.edit', ['draft' => $draft, 'return_to' => request()->fullUrl()]) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-[var(--records-border)] bg-white px-3 py-2 text-xs font-bold text-[var(--records-text)] hover:bg-[var(--records-hover)]">
                                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                        Resume
                                                    </a>
                                                    <form method="POST" action="{{ route('intake.drafts.destroy', $draft) }}" onsubmit="return confirm('Discard this intake draft?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#CFA9A2] bg-white px-3 py-2 text-xs font-bold text-[#7F3A32] hover:bg-[#F8E7E3]">
                                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                                            Discard
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="table-system-empty">
                                                No incomplete intake drafts.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="table-wrapper table-system-wrap">
                    <table class="table-base table-system-table case-records-table records-worklist-table">
                        <colgroup>
                            <col class="records-col-case">
                            <col class="records-col-family">
                            <col class="records-col-service">
                            <col class="records-col-schedule">
                            <col class="records-col-financials">
                            <col class="records-col-case-status">
                            <col class="records-col-payment-status">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-left">Case</th>
                                <th class="text-left">Family / Client</th>
                                <th class="text-left">Service</th>
                                <th class="text-left">Schedule</th>
                                <th class="table-col-number">Financials</th>
                                <th class="table-status-col">Case Status</th>
                                <th class="table-status-col table-payment-status-col">Payment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cases as $case)
                                @php
                                    $intermentAt = $case->interment_at
                                        ?? $case->serviceDetail?->internment_date;
                                    if (is_string($intermentAt)) {
                                        $intermentAt = \Carbon\Carbon::parse($intermentAt);
                                    }
                                    $needsAttention = $case->payment_status === 'UNPAID'
                                        || (float) $case->balance_amount > 0;
                                @endphp
                                <tr
                                    class="{{ $needsAttention ? 'row-needs-attention' : '' }}"
                                    data-clickable-row
                                    data-row-href="{{ route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}"
                                    tabindex="0"
                                    role="link"
                                    aria-label="Open case details for {{ $case->case_code }}"
                                >
                                    <td>
                                        <div class="table-primary whitespace-nowrap records-case-code">{{ $case->case_code }}</div>
                                        <div class="table-secondary">{{ $case->branch?->branch_code ?? 'Assigned Branch' }} &middot; Encoded {{ $case->created_at?->format('M d, Y') }}</div>
                                    </td>
                                    <td>
                                        <div class="table-primary">{{ \Illuminate\Support\Str::limit($case->deceased?->full_name ?? '-', 30) }}</div>
                                        <div class="table-secondary">
                                            {{ \Illuminate\Support\Str::limit($case->client?->full_name ?? '-', 28) }}
                                            @if($isActiveTab && $case->client?->contact_number)
                                                &middot; {{ $case->client->contact_number }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-primary">{{ $case->service_type ?? '-' }}</div>
                                        <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->package?->name ?? $case->service_package ?? '-', 30) }}</div>
                                    </td>
                                    <td>
                                        <div class="table-primary whitespace-nowrap">{{ $intermentAt ? $intermentAt->format('M d, Y') : '-' }}</div>
                                        <div class="table-secondary">{{ $intermentAt ? $intermentAt->format('h:i A') : 'Interment time' }}</div>
                                    </td>
                                    <td class="table-col-number">
                                        <div class="table-primary table-financial-total whitespace-nowrap">{{ number_format((float) $case->total_amount, 2) }}</div>
                                        <div class="table-secondary table-financial-breakdown whitespace-nowrap">Paid {{ number_format((float) $case->total_paid, 2) }} &middot; Bal {{ number_format((float) $case->balance_amount, 2) }}</div>
                                    </td>
                                    <td class="table-status-cell">
                                        <x-status-badge :status="$case->case_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->case_status))" />
                                    </td>
                                    <td class="table-status-cell table-payment-status-cell">
                                        <x-status-badge :status="$case->payment_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->payment_status))" class="table-payment-status-badge" />
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="table-system-empty">
                                        {{ $isDraftTab ? 'No official draft case records found.' : 'No case records found.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-system-pagination">
                @if($cases->hasPages()){{ $cases->links() }}@endif
            </div>
        @endif

    <div id="caseEditOverlay" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-200 panel-overlay-content">
        <div id="caseEditSheet" class="relative w-[90vw] max-w-4xl max-h-[94vh] rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0" style="background:var(--card);border:1px solid var(--border)">
            <button id="caseEditClose" type="button" class="absolute top-4 right-4 z-10 inline-flex items-center justify-center w-9 h-9 rounded-xl transition-colors focus:outline-none shadow-sm" style="background:var(--card);border:1px solid var(--border);color:var(--ink-muted)">
                <i class="bi bi-x-lg" style="font-size:.8rem"></i>
            </button>
            <div id="caseEditContent" class="overflow-y-auto max-h-[84vh]" style="padding:16px;background:var(--card);">
                <div class="flex flex-col items-center justify-center py-16 gap-3">
                    <div class="w-7 h-7 rounded-full animate-spin" style="border:2px solid var(--border);border-top-color:var(--ink-muted)"></div>
                    <span class="text-sm" style="color:var(--ink-muted)">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const filterForm = document.getElementById('caseRecordsFilterForm');
        if (filterForm) {
            const dateRangeSelect = filterForm.querySelector('select[name="date_range"]');
            const customDateFields = filterForm.querySelectorAll('[data-custom-date-field]');
            const customDateInputs = filterForm.querySelectorAll('[data-custom-date-input]');

            const toggleCustomDate = () => {
                const isCustom = dateRangeSelect && dateRangeSelect.value === 'custom';
                customDateFields.forEach((field) => {
                    if (isCustom) {
                        field.removeAttribute('hidden');
                    } else {
                        field.setAttribute('hidden', '');
                    }
                });
                customDateInputs.forEach((input) => {
                    input.disabled = !isCustom;
                    if (!isCustom) {
                        input.value = '';
                    }
                });
            };

            if (dateRangeSelect) {
                dateRangeSelect.addEventListener('change', toggleCustomDate);
            }
            toggleCustomDate();
        }

        const overlay = document.getElementById('caseEditOverlay');
        const sheet = document.getElementById('caseEditSheet');
        const content = document.getElementById('caseEditContent');
        const closeBtn = document.getElementById('caseEditClose');
        const openLinks = [...document.querySelectorAll('.open-edit-modal, .open-view-modal')];
        const transitionMs = 180;
        let hideTimer = null;
        let activeRequestId = 0;

        const loadingMarkup = `
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full animate-spin" style="border:2px solid var(--border);border-top-color:var(--ink-muted)"></div>
                <span class="text-sm" style="color:var(--ink-muted)">Loading...</span>
            </div>`;

        const dispatchUiReset = () => {
            document.dispatchEvent(new CustomEvent('panel-ui:reset'));
        };

        const syncPageScrollLock = (isOpen) => {
            document.documentElement.classList.toggle('overflow-hidden', !!isOpen);
            document.body.classList.toggle('overflow-hidden', !!isOpen);
        };

        const resetContent = () => {
            if (content) {
                content.innerHTML = loadingMarkup;
            }
        };

        const showShell = () => {
            if (!overlay || !sheet) return;
            window.clearTimeout(hideTimer);
            dispatchUiReset();
            overlay.classList.remove('hidden');
            syncPageScrollLock(true);
            requestAnimationFrame(() => {
                sheet.classList.remove('scale-95', 'opacity-0');
                sheet.classList.add('scale-100', 'opacity-100');
                overlay.classList.add('opacity-100');
            });
        };

        const hideShell = () => {
            if (!overlay || !sheet || !content) return;
            activeRequestId += 1;
            window.clearTimeout(hideTimer);
            sheet.classList.add('scale-95', 'opacity-0');
            sheet.classList.remove('scale-100', 'opacity-100');
            overlay.classList.remove('opacity-100');
            syncPageScrollLock(false);
            dispatchUiReset();
            hideTimer = window.setTimeout(() => {
                overlay.classList.add('hidden');
                syncPageScrollLock(false);
                resetContent();
            }, transitionMs);
        };

        const loadContent = async (url) => {
            if (!content) return;
            const requestId = ++activeRequestId;
            resetContent();
            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const html = await res.text();
                if (requestId !== activeRequestId || overlay.classList.contains('hidden')) return;
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const form = doc.querySelector('#caseEditForm');
                const view = doc.querySelector('#caseViewContent');
                const payload = form || view;
                if (payload) {
                    content.innerHTML = payload.outerHTML;
                    const scripts = [...doc.querySelectorAll('script')];
                    scripts.forEach((oldScript) => {
                        const script = document.createElement('script');
                        if (oldScript.src) {
                            script.src = oldScript.src;
                        } else {
                            script.textContent = oldScript.textContent;
                        }
                        content.appendChild(script);
                    });
                } else {
                    content.innerHTML = html;
                }
            } catch (err) {
                if (requestId !== activeRequestId || overlay.classList.contains('hidden')) return;
                content.innerHTML = `<div class="p-6 text-sm text-rose-600">Unable to load. Please try again.</div>`;
            }
        };

        const openModal = (url) => {
            showShell();
            loadContent(url);
        };

        openLinks.forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const url = link.dataset.url || link.href;
                openModal(url);
            });
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', hideShell);
        }

        if (overlay) {
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) hideShell();
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && overlay && !overlay.classList.contains('hidden')) {
                hideShell();
            }
        });
    })();

    // Case records tab switcher — instant visual feedback on click
    (function () {
        const tabList = document.querySelector('.case-records-tabs[role="tablist"]');
        if (!tabList) return;

        tabList.addEventListener('click', function (e) {
            const clicked = e.target.closest('.table-quick-tab');
            if (!clicked || clicked.classList.contains('table-quick-tab-active')) return;

            // Swap active class immediately so transition fires before navigation
            tabList.querySelectorAll('.table-quick-tab').forEach(function (tab) {
                tab.classList.remove('table-quick-tab-active');
                tab.setAttribute('aria-selected', 'false');
            });
            clicked.classList.add('table-quick-tab-active');
            clicked.setAttribute('aria-selected', 'true');
        });
    })();

    (function () {
        const recordsSelector = '.records-page';
        const replaceSelectors = [
            '.case-records-top-wrapper',
            '.table-system-list',
            '.table-system-pagination',
        ];

        const getRecordsPage = () => document.querySelector(recordsSelector);

        const setUpdating = (isUpdating) => {
            const page = getRecordsPage();
            if (!page) return;
            page.classList.toggle('is-updating', isUpdating);
        };

        const syncActiveLink = (link) => {
            if (!link) return;
            const group = link.closest('.case-records-tabs, .table-system-quick-tabs');
            if (!group) return;
            group.querySelectorAll('.table-quick-tab').forEach((tab) => {
                tab.classList.remove('table-quick-tab-active');
                tab.setAttribute('aria-selected', 'false');
            });
            link.classList.add('table-quick-tab-active');
            link.setAttribute('aria-selected', 'true');
        };

        const replaceFromDocument = (doc) => {
            replaceSelectors.forEach((selector) => {
                const current = document.querySelector(`${recordsSelector} ${selector}`);
                const next = doc.querySelector(`${recordsSelector} ${selector}`);
                if (current && next) {
                    current.replaceWith(next);
                }
            });
        };

        const loadRecords = async (url, pushState = true) => {
            const page = getRecordsPage();
            if (!page) {
                window.location.href = url.toString();
                return;
            }

            setUpdating(true);

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) throw new Error(`Case records request failed: ${response.status}`);

                const html = await response.text();
                const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                if (!nextDocument.querySelector(recordsSelector)) throw new Error('Case records page not found.');

                await new Promise((resolve) => window.setTimeout(resolve, 120));
                replaceFromDocument(nextDocument);

                if (pushState) {
                    window.history.pushState({}, '', url.toString());
                }

                document.dispatchEvent(new CustomEvent('panel-ui:reset'));
            } catch (error) {
                window.location.href = url.toString();
            } finally {
                requestAnimationFrame(() => setUpdating(false));
            }
        };

        const submitFilterForm = (form, submitter = null) => {
            const url = new URL(form.action, window.location.origin);
            const data = submitter ? new FormData(form, submitter) : new FormData(form);
            const selectedPreset = String(data.get('date_preset') || '');

            Array.from(url.searchParams.keys()).forEach((key) => url.searchParams.delete(key));
            data.forEach((value, key) => {
                if (selectedPreset !== 'CUSTOM' && (key === 'date_from' || key === 'date_to')) return;
                if (value !== null && String(value) !== '') {
                    url.searchParams.append(key, value);
                }
            });

            loadRecords(url, true);
        };

        const setCustomOpen = (form, open) => {
            const toggle = form.querySelector('[data-case-custom-toggle]');
            const select = form.querySelector('[data-case-date-preset-select]');
            const panel = form.querySelector('[data-case-custom-panel]');
            if (!panel) return;
            panel.hidden = !open;
            if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (select) select.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        const setMoreOpen = (form, open) => {
            const toggle = form.querySelector('[data-case-more-toggle]');
            const panel = form.querySelector('[data-case-more-panel]');
            const icon = form.querySelector('[data-case-more-icon]');
            const text = form.querySelector('[data-case-more-text]');
            if (!toggle || !panel) return;
            const hasFilters = toggle.classList.contains('active');

            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.classList.toggle('active', open || hasFilters);
            if (text) text.textContent = open ? 'Hide Filters' : 'More Filters';
            if (icon) {
                icon.classList.toggle('bi-chevron-down', !open);
                icon.classList.toggle('bi-chevron-up', open);
            }
        };

        document.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target : null;
            if (!target) return;

            const customToggle = target.closest(`${recordsSelector} [data-case-custom-toggle]`);
            if (customToggle) {
                const form = customToggle.closest('[data-case-filter]');
                if (form) {
                    event.preventDefault();
                    event.stopPropagation();
                    setCustomOpen(form, customToggle.getAttribute('aria-expanded') !== 'true');
                }
                return;
            }

            const moreToggle = target.closest(`${recordsSelector} [data-case-more-toggle]`);
            if (moreToggle) {
                const form = moreToggle.closest('[data-case-filter]');
                if (form) {
                    event.preventDefault();
                    event.stopPropagation();
                    setMoreOpen(form, moreToggle.getAttribute('aria-expanded') !== 'true');
                }
                return;
            }

            const ajaxLink = target.closest(`${recordsSelector} .case-records-tabs a[href], ${recordsSelector} .table-system-quick-tabs a[href], ${recordsSelector} .case-compact-reset[href], ${recordsSelector} .case-compact-pop-reset[href], ${recordsSelector} .case-compact-advanced-clear[href]`);
            if (!ajaxLink || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            const url = new URL(ajaxLink.href, window.location.href);
            if (url.origin !== window.location.origin) return;

            event.preventDefault();
            event.stopPropagation();
            syncActiveLink(ajaxLink);
            loadRecords(url, true);
        }, true);

        document.addEventListener('change', (event) => {
            const select = event.target instanceof Element ? event.target.closest(`${recordsSelector} [data-case-date-preset-select]`) : null;
            if (!select) return;

            const form = select.closest('[data-case-filter]');
            if (!form) return;

            if (select.value === 'CUSTOM') {
                setCustomOpen(form, true);
                return;
            }

            setCustomOpen(form, false);
            submitFilterForm(form);
        }, true);

        document.addEventListener('change', (event) => {
            const select = event.target instanceof Element ? event.target.closest(`${recordsSelector} [data-case-sort-select]`) : null;
            if (!select) return;

            const form = select.closest('[data-case-filter]');
            if (!form) return;

            submitFilterForm(form);
        }, true);

        document.addEventListener('submit', (event) => {
            const form = event.target instanceof Element ? event.target.closest(`${recordsSelector} [data-case-filter]`) : null;
            if (!form) return;

            event.preventDefault();
            submitFilterForm(form, event.submitter || null);
        }, true);

        document.addEventListener('click', (event) => {
            const page = getRecordsPage();
            if (!page || !(event.target instanceof Element)) return;
            if (event.target.closest('[data-case-filter]')) return;
            page.querySelectorAll('[data-case-filter]').forEach((filterForm) => setCustomOpen(filterForm, false));
        });

        window.addEventListener('popstate', () => {
            loadRecords(new URL(window.location.href), false);
        });
    })();
</script>
@endsection
