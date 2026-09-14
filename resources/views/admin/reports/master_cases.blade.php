@extends('layouts.panel')

@section('page_title', 'Master Case Records')
@section('page_desc', 'Monitor branch activity, case status, and payment health from one admin worklist.')
@section('hide_layout_topbar', '1')

@section('content')
<div class="admin-table-page master-records-page">
    @php
        $isBranchAdmin = auth()->user()?->isBranchAdmin() ?? false;
        $resolvedDatePreset = $datePreset ?? 'ANY';
        $isCustomDate = $resolvedDatePreset === 'CUSTOM';
        $sort = $sort ?? 'newest';
        $secondaryFiltersActive = filled($paymentStatus ?? null)
            || $sort !== 'newest'
            || $resolvedDatePreset !== 'ANY'
            || filled($intermentFrom ?? null)
            || filled($intermentTo ?? null);
        $activeFilterCount = collect([
            filled($q ?? null),
            filled($branchId ?? null),
            filled($caseStatus ?? null),
            filled($paymentStatus ?? null),
            $sort !== 'newest',
            $resolvedDatePreset !== 'ANY',
            filled($intermentFrom ?? null),
            filled($intermentTo ?? null),
        ])->filter()->count();
        $presetLabel = match ($resolvedDatePreset) {
            'TODAY' => 'Today',
            'LAST_7_DAYS' => 'Last 7 Days',
            'LAST_30_DAYS' => 'Last 30 Days',
            'THIS_MONTH' => 'This Month',
            'CUSTOM' => 'Custom Range',
            default => 'All Dates',
        };
        $adminMasterChips = collect();
        if ($isBranchAdmin && $branchId) {
            $adminBranch = $branches->firstWhere('id', (int) $branchId) ?? $branches->first();
            $adminMasterChips->push([
                'icon' => 'bi-lock-fill',
                'label' => 'Branch: ' . ($adminBranch ? trim(($adminBranch->branch_code ?? '') . ' - ' . ($adminBranch->branch_name ?? '')) : 'Assigned Branch'),
                'locked' => true,
            ]);
        } elseif (filled($branchId)) {
            $adminBranch = $branches->firstWhere('id', (int) $branchId);
            $adminMasterChips->push([
                'icon' => 'bi-building',
                'label' => 'Branch: ' . ($adminBranch ? trim(($adminBranch->branch_code ?? '') . ' - ' . ($adminBranch->branch_name ?? '')) : 'Selected Branch'),
            ]);
        }
        if (filled($q ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-search', 'label' => 'Search: ' . $q]);
        }
        if (filled($caseStatus ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-clipboard-check', 'label' => 'Case: ' . \Illuminate\Support\Str::headline(strtolower($caseStatus))]);
        }
        if (filled($paymentStatus ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-wallet2', 'label' => 'Payment: ' . \Illuminate\Support\Str::headline(strtolower($paymentStatus))]);
        }
        if (filled(request('service_type'))) {
            $adminMasterChips->push(['icon' => 'bi-tag', 'label' => 'Service: ' . request('service_type')]);
        }
        if (filled(request('package_id'))) {
            $selectedPackage = ($packages ?? collect())->firstWhere('id', (int) request('package_id'));
            $adminMasterChips->push(['icon' => 'bi-box', 'label' => 'Package: ' . ($selectedPackage?->name ?? 'Selected Package')]);
        }
        if ($resolvedDatePreset !== 'ANY') {
            $adminMasterChips->push(['icon' => 'bi-calendar3', 'label' => 'Date: ' . $presetLabel]);
        }
        if (filled($intermentFrom ?? null) || filled($intermentTo ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-calendar-event', 'label' => 'Interment: ' . (($intermentFrom ?? null) ?: 'Start') . ' - ' . (($intermentTo ?? null) ?: 'Today')]);
        }
        $masterTabParams = request()->except(['case_status', 'page']);
        $masterAllUrl = route('admin.cases.index', array_filter($masterTabParams, fn ($value) => filled($value)));
        $masterDraftUrl = route('admin.cases.index', array_filter(array_merge($masterTabParams, ['case_status' => 'DRAFT']), fn ($value) => filled($value)));
        $masterActiveUrl = route('admin.cases.index', array_filter(array_merge($masterTabParams, ['case_status' => 'ACTIVE']), fn ($value) => filled($value)));
        $masterCompletedUrl = route('admin.cases.index', array_filter(array_merge($masterTabParams, ['case_status' => 'COMPLETED']), fn ($value) => filled($value)));
    @endphp
    <style>
        .master-records-page {
            --records-card: #D3DEC9;
            --records-card-alt: #DCE6D6;
            --records-card-strong: #C7D5BE;
            --records-hover: #C5D3BC;
            --records-active: #B8C9AF;
            --records-border: #AEBBA8;
            --records-border-strong: #8EA083;
            --records-text: #232821;
            --records-muted: #3F4C3E;
            min-height:calc(100vh - 1rem);
            padding:.9rem var(--panel-content-inline) 20px;
            color:var(--records-text);
            font-family:var(--font-body);
            background:
                linear-gradient(90deg, rgba(73,87,69,0.04) 0 1px, transparent 1px),
                linear-gradient(180deg, rgba(73,87,69,0.034) 0 1px, transparent 1px),
                repeating-linear-gradient(135deg, rgba(73,87,69,0.02) 0 1px, transparent 1px 12px);
            background-size:44px 44px,44px 44px,16px 16px;
        }

        .master-records-page,
        .master-records-page * {
            box-shadow:none !important;
            filter:none !important;
            backdrop-filter:none !important;
        }

        .master-records-toast {
            position:fixed;
            top:1rem;
            right:1rem;
            z-index:1200;
            display:flex;
            align-items:center;
            gap:.55rem;
            max-width:calc(100vw - 2rem);
            border:1px solid var(--records-border-strong);
            border-radius:8px;
            background:#2F3A2E;
            color:#F7FAF3;
            padding:.72rem .9rem;
            font-size:.88rem;
            font-weight:650;
            line-height:1.35;
            pointer-events:none;
            animation:masterRecordsToastIn .18s ease-out, masterRecordsToastOut .22s ease-in 3.8s forwards;
        }

        .master-records-toast i {
            color:#DCE6D6;
        }

        @keyframes masterRecordsToastIn {
            from { opacity:0; transform:translateY(-.35rem); }
            to { opacity:1; transform:translateY(0); }
        }

        @keyframes masterRecordsToastOut {
            to { opacity:0; transform:translateY(-.35rem); visibility:hidden; }
        }

        .master-records-page a[href],
        .master-records-page button,
        .master-records-page select,
        .master-records-page [role="button"],
        .master-records-page [data-clickable-row] {
            cursor:pointer;
            transition:background-color .14s ease,border-color .14s ease,color .14s ease;
        }

        .master-records-page input {
            transition:background-color .14s ease,border-color .14s ease,color .14s ease;
        }

        .master-records-page a:focus,
        .master-records-page button:focus,
        .master-records-page select:focus,
        .master-records-page input:focus,
        .master-records-page [data-clickable-row]:focus {
            outline:none !important;
        }

        .master-records-page .table-system-card,
        .master-records-page .admin-table-card {
            background:transparent !important;
            border:0 !important;
            border-radius:8px !important;
            overflow:visible;
        }

        .master-records-page .table-system-toolbar,
        .master-records-page .case-records-master-chip-row,
        .master-records-page .table-system-list,
        .master-records-page .table-system-wrap,
        .master-records-page .table-system-pagination,
        .master-records-page .case-records-tabs {
            background:var(--records-card) !important;
            border:1px solid var(--records-border) !important;
            border-radius:8px !important;
            color:var(--records-text) !important;
        }

        .master-records-page .table-system-toolbar {
            padding:.9rem !important;
            margin-bottom:.75rem;
            overflow:visible;
        }

        .master-records-page .admin-table-toolbar .case-compact-filter,
        .master-records-page .admin-table-toolbar .case-compact-search-row,
        .master-records-page .admin-table-toolbar .case-compact-filter-bar,
        .master-records-page .admin-table-toolbar .case-compact-advanced {
            background:transparent !important;
            border:0 !important;
            padding:0 !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-filter {
            display:flex !important;
            flex-wrap:wrap;
            align-items:center;
            gap:.7rem;
        }

        .master-records-page .admin-table-toolbar .case-compact-search-row {
            display:contents !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-search-field {
            order:1 !important;
            flex:1 1 320px !important;
            display:block !important;
            width:auto !important;
            min-width:0 !important;
            max-width:none !important;
            height:auto !important;
            min-height:0 !important;
            align-self:end;
            background:transparent !important;
            border:0 !important;
            border-radius:0 !important;
            padding:0 !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-filter-bar {
            order:2 !important;
            display:flex !important;
            align-items:center;
            justify-content:flex-start;
            flex-wrap:wrap;
            gap:.6rem;
            width:auto !important;
            min-height:2.85rem;
            min-width:0 !important;
            background:transparent !important;
            border:0 !important;
            border-radius:0 !important;
            padding:0 !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-filter-bar > * {
            flex-shrink:0 !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-actions {
            order:3 !important;
            display:flex !important;
            align-items:center;
            justify-content:flex-end;
            gap:.55rem;
            width:auto !important;
            margin-left:auto !important;
            flex:0 0 auto !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-advanced {
            order:3 !important;
            position:fixed !important;
            inset:0 !important;
            z-index:1200 !important;
            display:flex !important;
            justify-content:flex-end !important;
            width:100% !important;
            flex:none !important;
            background:transparent !important;
            border:0 !important;
            border-radius:0 !important;
            padding:0 !important;
            overflow:visible;
        }

        .master-records-page .admin-table-toolbar .case-compact-advanced[hidden] {
            display:none !important;
        }

        .master-records-page .case-compact-advanced-grid {
            display:grid;
            grid-template-columns:1fr;
            gap:.7rem;
            align-items:end;
        }

        .master-records-page .case-compact-advanced-actions {
            display:flex;
            justify-content:flex-end;
            gap:.55rem;
            margin:.1rem -.85rem -.85rem;
            padding:.75rem .85rem;
            border-top:1px solid var(--records-border);
            background:var(--records-card);
        }

        .master-records-page .case-compact-search-field {
            position:relative;
        }

        .master-records-page .case-compact-search-field label {
            display:none !important;
        }

        .master-records-page .case-compact-search-field::before {
            display:none !important;
            content:none !important;
        }

        .master-records-page .case-compact-search-field .case-compact-input {
            padding-left:2.65rem !important;
            width:100% !important;
            min-height:2.85rem !important;
            height:2.85rem !important;
            display:block !important;
            background:#E1E7D9 !important;
            border:1px solid var(--records-border) !important;
            border-radius:8px !important;
        }

        .master-records-page .case-compact-field label,
        .master-records-page .case-compact-pop-field label,
        .master-records-page .table-system-table thead th {
            color:var(--records-muted) !important;
            font-size:.72rem !important;
            font-weight:650 !important;
            letter-spacing:.04em !important;
            text-transform:uppercase;
            opacity:1 !important;
        }

        .master-records-page .case-compact-input,
        .master-records-page .case-compact-select,
        .master-records-page .case-compact-date-select,
        .master-records-page .case-compact-sort-select,
        .master-records-page .case-compact-branch,
        .master-records-page .case-compact-seg,
        .master-records-page .case-compact-more,
        .master-records-page .case-compact-reset,
        .master-records-page .case-compact-pop-input,
        .master-records-page .case-compact-pop-reset,
        .master-records-page .case-compact-advanced-clear,
        .master-records-page .case-compact-chip {
            background:#E1E7D9 !important;
            border:1px solid var(--records-border) !important;
            color:var(--records-text) !important;
            border-radius:8px !important;
            font-weight:650 !important;
        }

        .master-records-page .case-compact-branch,
        .master-records-page .case-compact-seg,
        .master-records-page .case-compact-more,
        .master-records-page .case-compact-reset,
        .master-records-page .case-compact-apply {
            min-height:2.85rem !important;
            height:2.85rem !important;
        }

        .master-records-page .case-compact-branch {
            display:flex !important;
            align-items:center;
            flex:0 0 16.5rem !important;
            width:16.5rem !important;
            min-width:16.5rem !important;
            max-width:16.5rem !important;
            padding:0 2.35rem 0 .9rem !important;
        }

        .master-records-page .case-compact-seg {
            position:relative;
            display:inline-flex !important;
            align-items:center;
            flex:0 0 12rem !important;
            width:12rem !important;
            min-width:12rem !important;
            max-width:12rem !important;
            padding:0 !important;
        }

        .master-records-page .case-compact-more,
        .master-records-page .case-compact-reset,
        .master-records-page .case-compact-apply {
            flex:0 0 auto !important;
        }

        .master-records-page .case-compact-date-select,
        .master-records-page .case-compact-sort-select {
            width:100% !important;
            height:100% !important;
            min-height:0 !important;
            padding:0 2.25rem 0 2.45rem !important;
            appearance:none;
        }

        .master-records-page .case-compact-select,
        .master-records-page .case-compact-date-select,
        .master-records-page .case-compact-sort-select {
            background:transparent !important;
            border:0 !important;
            box-shadow:none !important;
        }

        .master-records-page .case-compact-select {
            width:100% !important;
            min-height:0 !important;
            height:100% !important;
            padding:0 !important;
            appearance:none;
        }

        .master-records-page .case-compact-date-icon,
        .master-records-page .case-compact-sort-icon,
        .master-records-page .case-compact-date-chev,
        .master-records-page .case-compact-sort-chev,
        .master-records-page .case-compact-select-chev {
            pointer-events:none;
            color:var(--records-muted);
        }

        .master-records-page .case-compact-date-icon,
        .master-records-page .case-compact-sort-icon {
            position:absolute;
            left:.9rem;
            z-index:1;
        }

        .master-records-page .case-compact-date-chev,
        .master-records-page .case-compact-sort-chev {
            position:absolute;
            top:50%;
            right:.85rem;
            transform:translateY(-50%);
            z-index:1;
        }

        .master-records-page .case-compact-apply,
        .master-records-page .case-compact-pop-apply {
            background:#344333 !important;
            border:1px solid #344333 !important;
            color:#fff !important;
            border-radius:8px !important;
            font-weight:700 !important;
        }

        .master-records-page .case-compact-input:hover,
        .master-records-page .case-compact-select:hover,
        .master-records-page .case-compact-date-select:hover,
        .master-records-page .case-compact-sort-select:hover,
        .master-records-page .case-compact-seg:hover,
        .master-records-page .case-compact-branch:hover,
        .master-records-page .case-compact-more:hover,
        .master-records-page .case-compact-reset:hover,
        .master-records-page .case-compact-pop-reset:hover,
        .master-records-page .case-compact-advanced-clear:hover,
        .master-records-page .case-compact-chip:hover {
            background:var(--records-card-strong) !important;
            border-color:var(--records-border-strong) !important;
            color:var(--records-text) !important;
        }

        .master-records-page .case-compact-apply:hover,
        .master-records-page .case-compact-pop-apply:hover {
            background:#2F3A2E !important;
            border-color:#2F3A2E !important;
            color:#fff !important;
        }

        .master-records-page .case-compact-popover {
            background:var(--records-card-alt) !important;
            border:1px solid var(--records-border) !important;
            border-radius:8px !important;
        }

        .master-records-page .case-records-tabs-row {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.75rem;
            flex-wrap:wrap;
            margin:0 0 .75rem;
            padding:0;
            background:transparent;
            border:0;
        }

        .master-records-page .case-records-tabs {
            display:inline-flex;
            gap:.35rem;
            padding:.35rem;
            background:var(--records-card-alt) !important;
        }

        .master-records-page .table-quick-tab {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:2.45rem;
            padding:0 .95rem;
            border:1px solid transparent;
            border-radius:8px;
            color:var(--records-muted);
            font-size:.85rem;
            font-weight:650;
            text-decoration:none;
        }

        .master-records-page .table-quick-tab:hover {
            background:var(--records-card-strong);
            color:var(--records-text);
        }

        .master-records-page .table-quick-tab-active {
            background:var(--records-active);
            border-color:var(--records-border-strong);
            color:var(--records-text);
        }

        .master-records-page .case-records-master-chip-row {
            display:flex;
            justify-content:flex-start;
            padding:0;
            margin:0;
            background:transparent !important;
            border:0 !important;
            flex:1 1 320px;
        }

        .master-records-page .case-compact-inline-chips {
            display:flex;
            flex-wrap:wrap;
            justify-content:flex-end;
            gap:.45rem;
            width:100%;
        }

        .master-records-page .case-records-master-chip-row .case-compact-chip {
            max-width:min(340px, 100%);
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        .master-records-page .table-system-list {
            padding:.75rem;
            overflow:hidden;
        }

        @media (max-width: 1000px) {
            .master-records-page .admin-table-toolbar .case-compact-filter {
                display:flex !important;
            }

            .master-records-page .admin-table-toolbar .case-compact-search-row,
            .master-records-page .admin-table-toolbar .case-compact-actions {
                width:100% !important;
                justify-content:flex-start;
            }

            .master-records-page .case-compact-branch,
            .master-records-page .case-compact-seg,
            .master-records-page .case-compact-more {
                flex-basis:100% !important;
                width:100% !important;
                max-width:none !important;
            }

            .master-records-page .case-records-tabs-row {
                align-items:flex-start;
            }

            .master-records-page .case-records-master-chip-row,
            .master-records-page .case-compact-inline-chips {
                justify-content:flex-start;
            }

            .master-records-page .case-compact-advanced-grid {
                grid-template-columns:1fr;
            }

            .master-records-page .case-compact-advanced-actions {
                flex-direction:column;
                align-items:stretch;
            }
        }

        .master-records-page .table-system-wrap,
        .master-records-page .table-system-table {
            background:var(--records-card-alt) !important;
        }

        .master-records-page .table-system-wrap {
            overflow-x:hidden !important;
            overflow-y:visible !important;
        }

        .master-records-page .table-system-table {
            width:100%;
            min-width:0 !important;
            border-collapse:separate;
            border-spacing:0;
            table-layout:fixed;
        }

        .master-records-page .records-worklist-table .records-col-case { width:12%; }
        .master-records-page .records-worklist-table .records-col-branch { width:11%; }
        .master-records-page .records-worklist-table .records-col-family { width:20%; }
        .master-records-page .records-worklist-table .records-col-service { width:14%; }
        .master-records-page .records-worklist-table .records-col-schedule { width:12%; }
        .master-records-page .records-worklist-table .records-col-financials { width:16%; }
        .master-records-page .records-worklist-table .records-col-case-status { width:8%; }
        .master-records-page .records-worklist-table .records-col-payment-status { width:9%; }

        .master-records-page .table-system-table .table-primary,
        .master-records-page .table-system-table .table-secondary {
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        .master-records-page .table-system-table thead th {
            background:var(--records-card-strong) !important;
            padding:.95rem 1rem !important;
        }

        .master-records-page .table-system-table tbody tr {
            background:#EEF3E8;
        }

        .master-records-page .table-system-table tbody tr:nth-child(even) {
            background:#E5EDDF;
        }

        .master-records-page .table-system-table tbody tr:hover {
            background:var(--records-hover) !important;
        }

        .master-records-page .table-system-table td {
            padding:1rem !important;
            border-color:var(--records-border) !important;
            color:var(--records-text);
        }

        .master-records-page .table-primary {
            color:var(--records-text) !important;
            font-weight:680 !important;
            letter-spacing:0 !important;
        }

        .master-records-page .table-secondary {
            color:var(--records-muted) !important;
            font-weight:600 !important;
            opacity:1 !important;
        }

        .master-records-page .records-case-code {
            font-size:.98rem;
        }

        .master-records-page .row-needs-attention {
            border-left:4px solid #A85248;
        }

        .master-records-page .table-system-empty {
            background:#E1E7D9 !important;
            color:var(--records-muted) !important;
            font-weight:650;
            text-align:center;
            padding:2rem !important;
        }

        .master-records-page #adminCaseViewOverlay {
            background:rgba(35,40,33,.48) !important;
            backdrop-filter:none !important;
            -webkit-backdrop-filter:none !important;
        }

        .master-records-page #adminCaseViewSheet {
            background:var(--records-card) !important;
            border:1px solid var(--records-border) !important;
            border-radius:8px !important;
        }

        .master-records-page #adminCaseViewContent {
            background:var(--records-card) !important;
        }

        .admin-master-toolbar-row-primary {
            grid-template-columns: minmax(280px, 2.2fr) repeat(2, minmax(180px, 1fr)) auto;
        }

        .admin-master-primary-actions {
            justify-content: flex-end;
            align-items: flex-end;
            gap: 8px;
        }

        .admin-master-more-btn {
            min-height: 40px;
            white-space: nowrap;
            min-width: 132px;
        }

        .admin-master-more-btn i {
            font-size: 13px;
        }

        .admin-master-more-filters {
            margin-top: 8px;
        }

        .admin-master-more-filters .admin-master-toolbar-row-bottom {
            grid-template-columns: repeat(4, minmax(150px, 1fr)) repeat(2, minmax(150px, 180px));
        }

        .admin-master-active-summary {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            color: #5F685F;
        }

        .admin-master-active-summary strong {
            color: #333333;
            font-weight: 700;
        }

        .admin-master-active-summary a {
            color: #333333;
            font-weight: 600;
        }

        .admin-master-active-summary a:hover {
            color: #3E4A3D;
            text-decoration: underline;
        }

        @media (max-width: 1200px) {
            .admin-master-toolbar-row-primary {
                grid-template-columns: repeat(2, minmax(220px, 1fr));
            }

            .admin-master-primary-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 680px) {
            .admin-master-toolbar-row-primary {
                grid-template-columns: 1fr;
            }

            .admin-master-primary-actions {
                width: 100%;
                flex-wrap: wrap;
            }
        }
    </style>

    @if($errors->any())
        <div class="flash-error">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="master-records-toast no-print" role="status" aria-live="polite" data-page-context-toast>
        <i class="bi bi-folder2-open"></i>
        <span>You are viewing Master Case Records.</span>
    </div>

    <section class="table-system-card admin-table-card">
        <div class="table-system-toolbar admin-table-toolbar">
            @include('partials.case_filter_toolbar', [
                'action' => route('admin.cases.index'),
                'resetUrl' => route('admin.cases.index'),
                'branchMode' => $isBranchAdmin ? 'locked' : 'all',
                'assignedBranch' => $isBranchAdmin ? $branches->first() : null,
                'branchId' => $branchId,
                'branches' => $branches,
                'dateFrom' => $dateFrom ?? null,
                'dateTo' => $dateTo ?? null,
                'intermentFrom' => $intermentFrom ?? null,
                'intermentTo' => $intermentTo ?? null,
                'serviceTypes' => $serviceTypes ?? collect(),
                'packages' => $packages ?? collect(),
                'encoders' => $encoders ?? collect(),
                'showVerificationStatus' => false,
                'showPackage' => true,
                'showEncodedBy' => true,
                'showSort' => true,
                'sortOptions' => [
                    'newest' => 'Newest',
                    'oldest' => 'Oldest',
                ],
                'sort' => $sort,
                'showInlineChips' => false,
                'datePreset' => $resolvedDatePreset === 'ANY' ? '' : $resolvedDatePreset,
            ])

            <form method="GET" action="{{ route('admin.cases.index') }}" class="admin-master-toolbar hidden" data-table-toolbar data-search-debounce="400">
                <div class="admin-master-toolbar-row admin-master-toolbar-row-top admin-master-toolbar-row-primary">
                    <div class="table-toolbar-field">
                        <input
                            name="q"
                            value="{{ $q }}"
                            class="table-toolbar-search"
                            placeholder="Search case, client, or deceased..."
                            data-table-search
                        >
                    </div>

                    <div class="table-toolbar-field">
                        @if($isBranchAdmin && $branchId)
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        @endif
                        <select name="branch_id" class="table-toolbar-select" data-table-auto-submit @if($isBranchAdmin) disabled @endif>
                            @unless($isBranchAdmin)
                                <option value="">All Branches</option>
                            @endunless
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->branch_code }} - {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-toolbar-field">
                        <select name="case_status" class="table-toolbar-select" data-table-auto-submit>
                            <option value="">All Case Status</option>
                            <option value="DRAFT" {{ $caseStatus === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                            <option value="ACTIVE" {{ $caseStatus === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                            <option value="COMPLETED" {{ $caseStatus === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>

                    <div class="table-toolbar-reset-wrap admin-master-toolbar-actions admin-master-primary-actions">
                        <button
                            type="button"
                            class="btn btn-secondary admin-master-more-btn"
                            data-more-filters-toggle
                            data-more-filters-label
                            aria-controls="admin-master-more-filters"
                            aria-expanded="{{ $secondaryFiltersActive ? 'true' : 'false' }}"
                        >
                            <i class="bi bi-sliders"></i>
                            <span>{{ $secondaryFiltersActive ? 'Hide Filters' : 'More Filters' }}</span>
                        </button>
                        <a href="{{ route('admin.cases.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>

                <div id="admin-master-more-filters" class="admin-master-more-filters @if(!$secondaryFiltersActive) hidden @endif" data-more-filters-panel>
                    <div class="admin-master-toolbar-row admin-master-toolbar-row-bottom">
                        <div class="table-toolbar-field">
                            <select name="payment_status" class="table-toolbar-select" data-table-auto-submit>
                                <option value="">All Payment Status</option>
                                <option value="PAID" {{ $paymentStatus === 'PAID' ? 'selected' : '' }}>Paid</option>
                                <option value="PARTIAL" {{ $paymentStatus === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                                <option value="UNPAID" {{ $paymentStatus === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                            </select>
                        </div>

                        <div class="table-toolbar-field">
                            <select name="sort" class="table-toolbar-select" data-table-sort>
                                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                            </select>
                        </div>

                        <div class="table-toolbar-field">
                            <select name="date_preset" class="table-toolbar-select">
                                <option value="ANY" {{ $resolvedDatePreset === 'ANY' ? 'selected' : '' }}>All Dates</option>
                                <option value="TODAY" {{ $resolvedDatePreset === 'TODAY' ? 'selected' : '' }}>Today</option>
                                <option value="LAST_7_DAYS" {{ $resolvedDatePreset === 'LAST_7_DAYS' ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="LAST_30_DAYS" {{ $resolvedDatePreset === 'LAST_30_DAYS' ? 'selected' : '' }}>Last 30 Days</option>
                                <option value="THIS_MONTH" {{ $resolvedDatePreset === 'THIS_MONTH' ? 'selected' : '' }}>This Month</option>
                                <option value="CUSTOM" {{ $resolvedDatePreset === 'CUSTOM' ? 'selected' : '' }}>Custom Range</option>
                            </select>
                        </div>

                        <div class="table-toolbar-field" data-custom-date-field @if(!$isCustomDate) hidden @endif>
                            <input
                                type="date"
                                name="interment_from"
                                value="{{ $intermentFrom ?? '' }}"
                                class="table-toolbar-select"
                                data-custom-date-input
                                title="Date from"
                                @if(!$isCustomDate) disabled @endif
                            >
                        </div>

                        <div class="table-toolbar-field" data-custom-date-field @if(!$isCustomDate) hidden @endif>
                            <input
                                type="date"
                                name="interment_to"
                                value="{{ $intermentTo ?? '' }}"
                                class="table-toolbar-select"
                                data-custom-date-input
                                title="Date to"
                                @if(!$isCustomDate) disabled @endif
                            >
                        </div>
                    </div>
                </div>

                <div class="admin-master-active-summary">
                    <span>Active filters: <strong>{{ $activeFilterCount }}</strong></span>
                    @if($activeFilterCount > 0)
                        <a href="{{ route('admin.cases.index') }}">Clear all filters</a>
                    @endif
                    <span>Date Range: <strong>{{ $presetLabel }}</strong></span>
                    @if($isBranchAdmin)
                        <span><strong>Assigned Branch Only</strong></span>
                    @endif
                </div>
            </form>
        </div>

        <div class="case-records-tabs-row">
            <div class="table-quick-tabs case-records-tabs" role="tablist" aria-label="Master case record tabs">
                <a
                    href="{{ $masterAllUrl }}"
                    role="tab"
                    aria-selected="{{ blank($caseStatus ?? null) ? 'true' : 'false' }}"
                    class="table-quick-tab {{ blank($caseStatus ?? null) ? 'table-quick-tab-active' : '' }}"
                >
                    All
                </a>
                <a
                    href="{{ $masterDraftUrl }}"
                    role="tab"
                    aria-selected="{{ ($caseStatus ?? null) === 'DRAFT' ? 'true' : 'false' }}"
                    class="table-quick-tab {{ ($caseStatus ?? null) === 'DRAFT' ? 'table-quick-tab-active' : '' }}"
                >
                    Draft
                </a>
                <a
                    href="{{ $masterActiveUrl }}"
                    role="tab"
                    aria-selected="{{ ($caseStatus ?? null) === 'ACTIVE' ? 'true' : 'false' }}"
                    class="table-quick-tab {{ ($caseStatus ?? null) === 'ACTIVE' ? 'table-quick-tab-active' : '' }}"
                >
                    Active
                </a>
                <a
                    href="{{ $masterCompletedUrl }}"
                    role="tab"
                    aria-selected="{{ ($caseStatus ?? null) === 'COMPLETED' ? 'true' : 'false' }}"
                    class="table-quick-tab {{ ($caseStatus ?? null) === 'COMPLETED' ? 'table-quick-tab-active' : '' }}"
                >
                    Completed
                </a>
            </div>

            <div class="case-records-master-chip-row">
                <div class="case-compact-inline-chips case-records-quick-chips" aria-label="Applied branch and filters">
                    @forelse($adminMasterChips as $chip)
                        <span class="case-compact-chip {{ !empty($chip['locked']) ? 'case-compact-chip-locked' : '' }}">
                            <i class="bi {{ $chip['icon'] }}"></i>{{ $chip['label'] }}
                        </span>
                    @empty
                        <span class="case-compact-chip">
                            <i class="bi bi-funnel"></i>All records
                        </span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table admin-master-table records-worklist-table">
                    <colgroup>
                        <col class="records-col-case">
                        <col class="records-col-branch">
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
                            <th class="text-left">Branch</th>
                            <th class="text-left">Family / Client</th>
                            <th class="text-left">Service</th>
                            <th class="text-left">Interment</th>
                            <th class="table-col-number">Financials</th>
                            <th class="table-status-col">Case Status</th>
                            <th class="table-status-col table-payment-status-col">Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($cases as $case)
                        @php
                            $isOtherBranch = $case->entry_source === 'OTHER_BRANCH';
                            $needsAttention = $case->payment_status === 'UNPAID'
                                || (float) $case->balance_amount > 0;
                            $intermentDate = $case->interment_at
                                ?? $case->deceased?->interment_at
                                ?? $case->deceased?->interment;
                        @endphp

                        <tr
                            class="{{ $needsAttention ? 'row-needs-attention' : '' }}"
                            data-clickable-row
                            data-row-href="{{ route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}"
                            tabindex="0"
                            role="link"
                            aria-label="Open full case details for {{ $case->case_code }}"
                        >
                            <td>
                                <div class="table-primary whitespace-nowrap records-case-code">{{ $case->case_code }}</div>
                                <div class="table-secondary">Encoded {{ $case->created_at?->format('M d, Y') }}</div>
                            </td>
                            <td>
                                <div class="table-primary whitespace-nowrap">{{ $case->branch?->branch_code ?? '-' }}</div>
                                <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->branch?->branch_name ?? '-', 24) }}</div>
                            </td>
                            <td>
                                <div class="table-primary">{{ \Illuminate\Support\Str::limit($case->deceased?->full_name ?? '-', 30) }}</div>
                                <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->client?->full_name ?? '-', 28) }}</div>
                            </td>
                            <td>
                                <div class="table-primary">{{ $case->service_type ?? '-' }}</div>
                                <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->package?->name ?? $case->service_package ?? '-', 30) }}</div>
                            </td>
                            <td>
                                <div class="table-primary whitespace-nowrap">{{ $intermentDate ? $intermentDate->format('M d, Y') : '-' }}</div>
                                <div class="table-secondary">{{ $intermentDate && $intermentDate->format('H:i') !== '00:00' ? $intermentDate->format('h:i A') : 'Scheduled date' }}</div>
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
                            <td colspan="8" class="table-system-empty">No case records found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-system-pagination">
                @if($cases->hasPages()){{ $cases->links() }}@endif
            </div>
        </div>
    </section>
</div>

{{-- Case view modal --}}
<div id="adminCaseViewOverlay" style="display:none; position:fixed; inset:0; z-index:400; background:rgba(0,0,0,0.55); backdrop-filter:blur(3px); -webkit-backdrop-filter:blur(3px); align-items:center; justify-content:center;">
    <div id="adminCaseViewSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border"
         style="background:var(--card);border-color:var(--border);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface-panel);flex-shrink:0;">
            <span style="font-size:13px;font-weight:700;color:var(--ink);">Case Details</span>
            <button id="adminCaseViewClose" type="button" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--ink-muted);cursor:pointer;" aria-label="Close">
                <i class="bi bi-x-lg" style="font-size:.75rem;"></i>
            </button>
        </div>
        <div id="adminCaseViewContent" class="overflow-y-auto" style="max-height:calc(92vh - 54px);padding:16px;">
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 0;gap:10px;">
                <div style="width:28px;height:28px;border-radius:50%;border:2px solid var(--border);border-top-color:var(--brand);animation:spin 1s linear infinite;"></div>
                <span style="font-size:13px;color:var(--ink-muted);">Loading…</span>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const page = document.querySelector('.master-records-page');
        if (!page) return;

        const submitCompactForm = (form) => {
            if (!form) return;
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }
            form.submit();
        };

        const setCompactCustomOpen = (form, open) => {
            const select = form?.querySelector('[data-case-date-preset-select]');
            const panel = form?.querySelector('[data-case-custom-panel]');
            if (!select || !panel) return;
            panel.hidden = !open;
            select.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        page.addEventListener('change', (event) => {
            const select = event.target instanceof Element
                ? event.target.closest('[data-case-date-preset-select]')
                : null;
            if (!select) return;

            const form = select.closest('[data-case-filter]');
            if (!form) return;
            if (form.dataset.caseFilterReady === '1') return;

            if (select.value === 'CUSTOM') {
                setCompactCustomOpen(form, true);
                return;
            }

            setCompactCustomOpen(form, false);
            submitCompactForm(form);
        });

        page.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            if (event.target.closest('[data-case-custom-panel]') || event.target.closest('[data-case-date-preset-select]')) {
                return;
            }

            page.querySelectorAll('[data-case-filter]').forEach((form) => {
                setCompactCustomOpen(form, false);
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            page.querySelectorAll('[data-case-filter]').forEach((form) => {
                setCompactCustomOpen(form, false);
            });
        });
    })();

    (() => {
        const toggle = document.querySelector('[data-more-filters-toggle]');
        const panel = document.querySelector('[data-more-filters-panel]');
        const labelWrap = document.querySelector('[data-more-filters-label] span');
        if (!toggle || !panel || !labelWrap) return;

        const setOpen = (open) => {
            panel.classList.toggle('hidden', !open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            labelWrap.textContent = open ? 'Hide Filters' : 'More Filters';
        };

        setOpen(toggle.getAttribute('aria-expanded') === 'true');
        toggle.addEventListener('click', () => {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });
    })();

    (() => {
        const form = document.querySelector('form.admin-master-toolbar');
        const preset = document.querySelector('select[name="date_preset"]');
        const from = document.querySelector('input[name="interment_from"]');
        const to = document.querySelector('input[name="interment_to"]');
        const customInputs = document.querySelectorAll('[data-custom-date-input]');
        const customFields = document.querySelectorAll('[data-custom-date-field]');
        if (!form || !preset || !from || !to || !customFields.length) return;
        const customDateDebounce = 800;
        let customDateTimer = null;

        const syncCustomDateVisibility = () => {
            const isCustom = preset.value === 'CUSTOM';
            customFields.forEach((field) => {
                field.hidden = !isCustom;
            });
            from.disabled = !isCustom;
            to.disabled = !isCustom;
        };

        const isCompleteDate = (value) => value === '' || /^\d{4}-\d{2}-\d{2}$/.test(value);

        const canSubmitCustomRange = () => {
            if (preset.value !== 'CUSTOM') {
                return false;
            }

            const fromValue = (from.value || '').trim();
            const toValue = (to.value || '').trim();

            if (!isCompleteDate(fromValue) || !isCompleteDate(toValue)) {
                return false;
            }

            if (fromValue === '' && toValue === '') {
                return false;
            }

            return true;
        };

        const submitForm = () => {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }
            form.submit();
        };

        const forceCustom = (event) => {
            if (event?.target && !isCompleteDate(event.target.value || '')) {
                return;
            }

            if (from.value || to.value) {
                preset.value = 'CUSTOM';
                syncCustomDateVisibility();
            }
        };

        const queueCustomDateSubmit = () => {
            if (customDateTimer) {
                clearTimeout(customDateTimer);
            }

            customDateTimer = setTimeout(() => {
                if (canSubmitCustomRange()) {
                    submitForm();
                }
            }, customDateDebounce);
        };

        preset.addEventListener('change', () => {
            syncCustomDateVisibility();
            if (preset.value !== 'CUSTOM') {
                submitForm();
            }
        });

        from.addEventListener('change', forceCustom);
        to.addEventListener('change', forceCustom);
        customInputs.forEach((input) => {
            input.addEventListener('change', queueCustomDateSubmit);
        });

        form.addEventListener('submit', () => {
            if (customDateTimer) {
                clearTimeout(customDateTimer);
            }
        });

        syncCustomDateVisibility();
    })();

    (() => {
        const overlay  = document.getElementById('adminCaseViewOverlay');
        const sheet    = document.getElementById('adminCaseViewSheet');
        const content  = document.getElementById('adminCaseViewContent');
        const closeBtn = document.getElementById('adminCaseViewClose');
        if (!overlay || !sheet || !content || !closeBtn) return;

        const loadingHtml = `
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 0;gap:10px;">
                <div style="width:28px;height:28px;border-radius:50%;border:2px solid var(--border);border-top-color:var(--brand);animation:spin 1s linear infinite;"></div>
                <span style="font-size:13px;color:var(--ink-muted);">Loading…</span>
            </div>`;

        const show = () => {
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                sheet.classList.remove('scale-95', 'opacity-0');
                sheet.classList.add('scale-100', 'opacity-100');
            });
        };

        const hide = () => {
            sheet.classList.add('scale-95', 'opacity-0');
            sheet.classList.remove('scale-100', 'opacity-100');
            setTimeout(() => {
                overlay.style.display = 'none';
                document.body.style.overflow = '';
                content.innerHTML = loadingHtml;
            }, 180);
        };

        const load = async (url) => {
            content.innerHTML = loadingHtml;
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const payload = doc.querySelector('#caseViewContent');
                if (payload) {
                    content.innerHTML = payload.innerHTML;
                    doc.querySelectorAll('script').forEach(oldScript => {
                        const script = document.createElement('script');
                        script.textContent = oldScript.textContent;
                        content.appendChild(script);
                    });
                } else {
                    content.innerHTML = `<div style="padding:20px;font-size:13px;color:#9E4B3F;">Unable to load case details.</div>`;
                }
            } catch {
                content.innerHTML = `<div style="padding:20px;font-size:13px;color:#9E4B3F;">Network error. Please try again.</div>`;
            }
        };

        document.querySelectorAll('.open-case-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                show();
                load(btn.dataset.url);
            });
        });

        closeBtn.addEventListener('click', hide);
        overlay.addEventListener('click', e => { if (e.target === overlay) hide(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.style.display !== 'none') hide(); });
    })();
</script>
@endsection
