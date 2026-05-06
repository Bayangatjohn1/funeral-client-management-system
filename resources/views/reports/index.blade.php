@extends('layouts.panel')

@section('page_title', 'Reports')
@section('page_desc', 'Generate, preview, and export filtered operational reports.')

@section('content')
<div
    class="reports-page"
    x-data="reportsModule({
        defaultReportType: @js($defaultReportType),
        reportTypes: @js($reportTypes),
        previewUrl: @js(route('reports.preview')),
        printUrl: @js(route('reports.print')),
        csvUrl: @js(route('reports.exportCsv')),
        drilldownUrl: @js(route('reports.ownerDrilldown')),
        branches: @js($branches),
        packages: @js($packages),
        users: @js($users),
        auditOptions: @js($auditOptions),
        userRole: @js($userRole),
        isBranchAdmin: @js($isBranchAdmin),
        assignedBranchId: @js($assignedBranchId),
        assignedBranchLabel: @js($assignedBranchLabel),
        analyticsDates: @js([
            'today' => now()->toDateString(),
            'monthStart' => now()->startOfMonth()->toDateString(),
            'yearStart' => now()->startOfYear()->toDateString(),
        ]),
    })"
    x-init="init()"
>
    <style>
        [x-cloak] { display: none !important; }
        .reports-page { max-width: none; margin: 0; padding: 12px var(--panel-content-inline, 20px) 20px; display: grid; gap: 14px; }
        .reports-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        }
        .reports-role-badge {
            display: inline-flex; align-items: center; gap: 8px; border: 1px solid #dbe4ef; background: #FAFAF7;
            color: #333333; border-radius: 999px; padding: 7px 12px; font-size: 12px; font-weight: 700; white-space: nowrap;
        }
        .reports-card-head { padding: 16px 18px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .reports-card-title { margin: 0; font-family: var(--font-heading); color: var(--ink); font-size: 18px; font-weight: 700; }
        .reports-card-copy { margin-top: 3px; color: var(--ink-muted); font-size: 12px; }
        .reports-config-form { padding: 14px; display: grid; gap: 12px; }
        .reports-config-toolbar { display: flex; flex-wrap: nowrap; align-items: center; gap: 8px; min-width: 0; }
        .reports-config-toolbar > .reports-field { flex: 0 0 260px; width: 260px; gap: 0; position: relative; }
        .reports-config-toolbar > .reports-field .reports-label,
        .reports-config-toolbar > .reports-field .reports-help { display: none; }
        .reports-config-toolbar .reports-analytics-filter { flex: 1 1 auto; min-width: 0; }
        .reports-filter-grid {
            display: flex; flex-wrap: nowrap; gap: 8px; align-items: center; flex: 1 1 auto; min-width: 0;
        }
        .reports-filter-grid [x-cloak] { display: none !important; }
        .reports-advanced-filter-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            width: 100%;
            margin-top: -2px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
        }
        .reports-filter-grid .reports-field { position: relative; width: auto; min-width: 0; max-width: none; flex: 1 1 122px; gap: 0; }
        .reports-filter-grid .reports-field.reports-field-wide { flex: 1.35 1 170px; min-width: 0; max-width: none; }
        .reports-filter-grid .reports-label {
            position: absolute; top: -7px; left: 10px; z-index: 1; background: var(--card); padding: 0 5px;
            font-size: 9px; line-height: 1; color: #5F685F;
        }
        .reports-field { display: grid; gap: 6px; min-width: 0; }
        .reports-label { color: var(--ink-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .reports-help { color: var(--ink-muted); font-size: 11px; line-height: 1.35; }
        .reports-input {
            width: 100%; min-height: 40px; border: 1px solid #dbe4ef; border-radius: 10px; background: var(--card);
            color: var(--ink); padding: 8px 10px; font-size: 12.5px; outline: none; transition: border-color .16s ease, box-shadow .16s ease;
        }
        .reports-input:focus { border-color: #3E4A3D; box-shadow: 0 0 0 3px rgba(37, 99, 235, .14); }
        .reports-actions {
            display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center;
            border-top: 1px solid var(--border); padding-top: 12px;
        }
        .reports-action-chips { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; min-width: 0; }
        .reports-action-chips .reports-chip { display: inline-flex; align-items: center; gap: 6px; }
        .reports-action-buttons { display: flex; flex-wrap: nowrap; gap: 8px; justify-content: flex-end; margin-left: auto; }
        .reports-btn {
            min-height: 38px; border-radius: 10px; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center;
            gap: 7px; font-size: 12.5px; font-weight: 800; border: 1px solid transparent; white-space: nowrap; transition: opacity .16s ease, transform .16s ease, background .16s ease;
        }
        .reports-btn:hover:not(:disabled) { transform: translateY(-1px); }
        .reports-btn:disabled { opacity: .48; cursor: not-allowed; }
        .reports-btn-primary { background: #3E4A3D; border-color: #3E4A3D; color: #fff; }
        .reports-btn-secondary { background: #fff; border-color: #C9C5BB; color: #333333; }
        .reports-btn-neutral { background: #FAFAF7; border-color: #dbe4ef; color: #5F685F; }
        .reports-spin { width: 14px; height: 14px; border-radius: 999px; border: 2px solid rgba(255,255,255,.45); border-top-color: #fff; animation: reportsSpin .75s linear infinite; }
        @keyframes reportsSpin { to { transform: rotate(360deg); } }
        .reports-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; padding: 16px; }
        .reports-metric {
            border: 1px solid var(--border); border-radius: 12px; background: #fff; padding: 14px;
            display: flex; gap: 12px; align-items: flex-start; min-width: 0;
        }
        .reports-metric-icon { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: #f1f5f9; color: #333333; flex: 0 0 auto; }
        .reports-metric-label { color: var(--ink-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .reports-metric-value { margin-top: 5px; color: var(--ink); font-family: var(--font-heading); font-size: 21px; font-weight: 700; line-height: 1.05; overflow-wrap: anywhere; }
        .reports-preview-head { padding: 16px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .reports-preview-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .reports-chip { border: 1px solid #dbe4ef; background: #FAFAF7; color: #5F685F; border-radius: 999px; padding: 4px 9px; font-size: 11px; font-weight: 700; }
        .reports-scope-pill { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; color: #0f766e; font-size: 11px; font-weight: 800; }
        .reports-alert { margin: 16px; padding: 12px 14px; border-radius: 12px; display: flex; gap: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #7F3A32; font-size: 13px; }
        .reports-state { min-height: 260px; display: grid; place-items: center; padding: 24px; text-align: center; }
        .reports-state-icon { width: 54px; height: 54px; border-radius: 16px; display: grid; place-items: center; margin: 0 auto 12px; background: #f1f5f9; color: #5F685F; font-size: 24px; }
        .reports-state-title { color: var(--ink); font-family: var(--font-heading); font-size: 18px; font-weight: 700; }
        .reports-state-copy { margin-top: 5px; color: var(--ink-muted); font-size: 13px; }
        .reports-loading-dot { width: 32px; height: 32px; border-radius: 999px; border: 3px solid #dbeafe; border-top-color: #3E4A3D; animation: reportsSpin .75s linear infinite; margin: 0 auto 12px; }
        .reports-table-wrap { overflow: auto; }
        .reports-table { min-width: 1040px; width: 100%; border-collapse: separate; border-spacing: 0; }
        .reports-table th {
            position: sticky; top: 0; z-index: 1; background: #FAFAF7; color: #5F685F; border-bottom: 1px solid var(--border);
            padding: 12px 13px; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; text-align: left;
        }
        .reports-table td { border-bottom: 1px solid #edf2f7; padding: 12px 13px; font-size: 13px; color: var(--ink); vertical-align: top; }
        .reports-table tbody tr:hover td { background: #FAFAF7; }
        .reports-cell-number { text-align: right !important; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .reports-status-badge { display: inline-flex; align-items: center; min-height: 24px; border-radius: 999px; padding: 3px 9px; font-size: 11px; font-weight: 800; }
        .reports-status-paid, .reports-status-completed, .reports-status-verified, .reports-status-success { background:#dcfce7; color:#166534; }
        .reports-status-partial, .reports-status-active, .reports-status-pending { background:#fef3c7; color:#92400e; }
        .reports-status-unpaid, .reports-status-draft, .reports-status-disputed { background:#fee2e2; color:#7F3A32; }
        .reports-status-neutral { background:#C9C5BB; color:#333333; }
        .reports-analytics-filter { display: grid; gap: 12px; }
        .reports-analytics-bar { display: flex; flex-wrap: nowrap; gap: 8px; align-items: center; min-width: 0; }
        .reports-analytics-branch {
            min-height: 40px; min-width: 150px; display: inline-flex; align-items: center; gap: 8px; position: relative;
            border: 1px solid #dbe4ef; border-radius: 10px; background: #fff; color: #333333; padding: 0 34px 0 12px;
        }
        .reports-analytics-select {
            appearance: none; border: 0; background: transparent; color: inherit; outline: none; min-height: 40px; width: 100%;
            font-size: 13px; font-weight: 800; cursor: pointer;
        }
        .reports-analytics-select-chev { position: absolute; right: 12px; pointer-events: none; color: #5F685F; font-size: 12px; }
        .reports-analytics-seg { display: inline-flex; flex-wrap: nowrap; gap: 6px; align-items: center; min-width: 0; }
        .reports-analytics-seg-item,
        .reports-analytics-more {
            min-height: 40px; border: 1px solid #dbe4ef; border-radius: 10px; background: #fff; color: #333333;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 13px;
            font-size: 12.5px; font-weight: 800; white-space: nowrap; transition: background .16s ease, border-color .16s ease, color .16s ease, transform .16s ease;
        }
        .reports-analytics-seg-item:hover,
        .reports-analytics-more:hover { transform: translateY(-1px); border-color: #7A8076; }
        .reports-analytics-seg-item.active,
        .reports-analytics-more.active { background: #3E4A3D; border-color: #3E4A3D; color: #fff; }
        .reports-more-filters {
            min-height: 40px; border: 1px solid #dbe4ef; border-radius: 10px; background: #fff; color: #333333;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 12px;
            font-size: 12.5px; font-weight: 800; white-space: nowrap; transition: background .16s ease, border-color .16s ease, color .16s ease, transform .16s ease;
        }
        .reports-more-filters:hover { transform: translateY(-1px); border-color: #7A8076; }
        .reports-more-filters.active { background: #3E4A3D; border-color: #3E4A3D; color: #fff; }
        .reports-analytics-custom { position: relative; }
        .reports-analytics-date-chev { font-size: 11px; }
        .reports-analytics-popover {
            position: absolute; top: calc(100% + 8px); right: 0; z-index: 20; width: min(300px, calc(100vw - 32px));
            background: #fff; border: 1px solid #dbe4ef; border-radius: 12px; box-shadow: 0 18px 48px rgba(15, 23, 42, .16);
            padding: 12px;
        }
        .reports-analytics-pop-label { color: #5F685F; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 10px; }
        .reports-analytics-pop-fields { display: grid; gap: 10px; }
        .reports-analytics-pop-field { display: grid; gap: 5px; }
        .reports-analytics-pop-field label { color: #5F685F; font-size: 11px; font-weight: 800; }
        .reports-analytics-pop-input {
            min-height: 38px; width: 100%; border: 1px solid #dbe4ef; border-radius: 9px; padding: 7px 9px;
            color: #3E4A3D; font-size: 13px; outline: none;
        }
        .reports-analytics-pop-input:focus { border-color: #3E4A3D; box-shadow: 0 0 0 3px rgba(37, 99, 235, .14); }
        .reports-analytics-pop-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
        .reports-analytics-pop-apply,
        .reports-analytics-pop-reset {
            min-height: 34px; border-radius: 9px; padding: 0 12px; font-size: 12px; font-weight: 900; border: 1px solid transparent;
        }
        .reports-analytics-pop-apply { background: #3E4A3D; color: #fff; }
        .reports-analytics-pop-reset { background: #FAFAF7; border-color: #dbe4ef; color: #5F685F; }
        .reports-analytics-advanced {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; padding-top: 4px;
        }
        @media (max-width: 1180px) {
            .reports-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .reports-config-toolbar,
            .reports-filter-grid,
            .reports-analytics-bar,
            .reports-analytics-seg,
            .reports-action-buttons { flex-wrap: wrap; }
            .reports-config-toolbar > .reports-field { flex: 1 1 240px; width: auto; }
            .reports-filter-grid .reports-field { flex: 1 1 150px; }
            .reports-advanced-filter-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        /* ── Branch summary strip (shown above drill-down records) ── */
        .reports-branch-strip {
            margin: 10px 16px 0;
            border: 1px solid #C9C5BB;
            border-radius: 10px;
            overflow: hidden;
        }
        .reports-branch-strip-head {
            display: flex; align-items: center; gap: 6px;
            padding: 7px 12px; background: #FAFAF7; border-bottom: 1px solid #C9C5BB;
            font-size: 10.5px; font-weight: 800; color: #5F685F; text-transform: uppercase; letter-spacing: .04em;
        }
        .reports-branch-strip-table { width: 100%; border-collapse: collapse; }
        .reports-branch-strip-table th {
            padding: 6px 11px; background: #FAFAF7; border-bottom: 1px solid #edf2f7;
            font-size: 10px; font-weight: 800; color: #5F685F; text-transform: uppercase;
            letter-spacing: .04em; text-align: left; white-space: nowrap;
        }
        .reports-branch-strip-table td {
            padding: 7px 11px; border-bottom: 1px solid #edf2f7; font-size: 12px; color: #333333;
        }
        .reports-branch-strip-table tbody tr:last-child td { border-bottom: none; }
        .reports-branch-strip-table .reports-cell-number { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        /* ── Metric card drill-down ── */
        .reports-metric {
            cursor: pointer;
            transition: border-color .16s ease, background .16s ease, box-shadow .16s ease;
            position: relative;
            overflow: hidden;
        }
        .reports-metric:hover:not(.is-selected) {
            border-color: #7A8076;
            background: rgba(139, 154, 139, 0.06);
        }
        .reports-metric.is-selected {
            border-color: #3E4A3D !important;
            background: rgba(139, 154, 139, 0.15) !important;
            box-shadow: 0 0 0 2px rgba(62, 74, 61, 0.12);
        }
        .reports-metric-hint {
            position: absolute;
            bottom: 7px;
            right: 10px;
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: .03em;
            color: #5F685F;
            opacity: 0;
            transition: opacity .16s ease;
            pointer-events: none;
            text-transform: uppercase;
        }
        .reports-metric:hover .reports-metric-hint { opacity: 1; }
        .reports-metric.is-selected .reports-metric-hint { opacity: 0; }
        /* ── Drill-down active-filter banner ── */
        .reports-drill-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 12px 16px 0;
            padding: 10px 14px;
            border-radius: 10px;
            background: rgba(139, 154, 139, 0.10);
            border: 1px solid #C9C5BB;
            color: #3E4A3D;
            font-size: 12.5px;
        }
        .reports-drill-banner-icon { color: #3E4A3D; font-size: 14px; flex: 0 0 auto; }
        .reports-drill-banner-text { flex: 1 1 auto; min-width: 0; font-weight: 700; }
        .reports-drill-banner-hint { font-weight: 400; color: #5F685F; }
        .reports-drill-clear {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            font-weight: 800;
            color: #5F685F;
            background: #fff;
            border: 1px solid #C9C5BB;
            border-radius: 8px;
            padding: 5px 11px;
            cursor: pointer;
            white-space: nowrap;
            transition: border-color .16s ease, color .16s ease;
            flex: 0 0 auto;
        }
        .reports-drill-clear:hover { border-color: #3E4A3D; color: #3E4A3D; }
        @media (max-width: 720px) {
            .reports-page { padding: 10px var(--panel-content-inline, 16px) 16px; }
            .reports-config-toolbar > .reports-field,
            .reports-config-toolbar .reports-analytics-filter,
            .reports-filter-grid,
            .reports-filter-grid .reports-field { flex-basis: 100%; width: 100%; max-width: none; }
            .reports-summary-grid { grid-template-columns: 1fr; }
            .reports-analytics-bar,
            .reports-analytics-seg,
            .reports-analytics-branch,
            .reports-analytics-seg-item,
            .reports-analytics-more { width: 100%; }
            .reports-analytics-advanced { grid-template-columns: 1fr; }
            .reports-analytics-popover { left: 0; right: auto; }
            .reports-advanced-filter-row { grid-template-columns: 1fr; }
            .reports-actions { justify-content: stretch; }
            .reports-action-chips,
            .reports-action-buttons { width: 100%; margin-left: 0; }
            .reports-btn { width: 100%; }
        }
    </style>

    <section class="reports-card">
        <div class="reports-card-head">
            <div>
                <h2 class="reports-card-title">Report Configuration</h2>
                <div class="reports-card-copy">Choose a report type and apply server-side filters before previewing.</div>
            </div>
            <div class="reports-role-badge">
                <i class="bi bi-shield-check"></i>
                <span x-text="userRole === 'owner' ? 'Owner View' : 'Administrator View'"></span>
            </div>
        </div>

        <form class="reports-config-form" @submit.prevent="loadPreview">
            <div class="reports-config-toolbar" :class="{ 'is-owner-analytics': isOwnerAnalytics() }">
                <div class="reports-field">
                    <label class="reports-label" for="report_type">Report Type</label>
                    <select id="report_type" x-model="filters.report_type" class="reports-input" @change="resetReportSpecificFilters()">
                        <template x-for="[value, label] in Object.entries(reportTypes)" :key="value">
                            <option :value="value" x-text="label"></option>
                        </template>
                    </select>
                    <div class="reports-help">Preview and print use the same query.</div>
                </div>

                @include('reports.partials.analytics-filter-bar')

                <div class="reports-filter-grid" x-show="!isOwnerAnalytics()" x-cloak>
                <template x-if="shows('branch_id')">
                    <div class="reports-field reports-field-wide">
                        <label class="reports-label" for="branch_id">Branch</label>
                        <select id="branch_id" x-model="filters.branch_id" class="reports-input" :disabled="isBranchAdmin">
                            <template x-if="!isBranchAdmin">
                                <option value="">All Branches</option>
                            </template>
                            <template x-for="branch in branches" :key="branch.id">
                                <option :value="branch.id" x-text="`${branch.branch_code} - ${branch.branch_name}`"></option>
                            </template>
                        </select>
                        <div class="reports-scope-pill" x-show="isBranchAdmin" x-cloak>
                            <i class="bi bi-lock-fill"></i>
                            <span>Assigned Branch Only</span>
                        </div>
                    </div>
                </template>

                <template x-if="shows('date_range')">
                    <div class="reports-field">
                        <label class="reports-label" for="date_from">Date From</label>
                        <input id="date_from" type="date" x-model="filters.date_from" class="reports-input">
                    </div>
                </template>

                <template x-if="shows('date_range')">
                    <div class="reports-field">
                        <label class="reports-label" for="date_to">Date To</label>
                        <input id="date_to" type="date" x-model="filters.date_to" class="reports-input">
                    </div>
                </template>

                <template x-if="shows('payment_status')">
                    <div class="reports-field">
                        <label class="reports-label" for="payment_status">Payment Status</label>
                        <select id="payment_status" x-model="filters.payment_status" class="reports-input">
                            <option value="">All Payment Statuses</option>
                            <option value="PAID">Paid</option>
                            <option value="PARTIAL">Partial</option>
                            <option value="UNPAID">Unpaid</option>
                        </select>
                    </div>
                </template>

                <template x-if="shows('audit_user') && auditOptions.supports_user">
                    <div class="reports-field reports-field-wide">
                        <label class="reports-label" for="user_id">Audit User</label>
                        <select id="user_id" x-model="filters.user_id" class="reports-input">
                            <option value="">All Users</option>
                            <template x-for="user in users" :key="user.id">
                                <option :value="user.id" x-text="user.name"></option>
                            </template>
                        </select>
                    </div>
                </template>
                <button
                    type="button"
                    class="reports-more-filters"
                    :class="{ 'active': advancedFiltersOpen }"
                    x-show="hasAdvancedFilters()"
                    x-cloak
                    @click="advancedFiltersOpen = !advancedFiltersOpen"
                >
                    <i class="bi bi-sliders"></i>
                    <span>More Filters</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                </div>
            </div>
                <div class="reports-advanced-filter-row" x-show="advancedFiltersOpen && hasAdvancedFilters()" x-cloak>
                    <template x-if="shows('case_status')">
                        <div class="reports-field">
                            <label class="reports-label" for="case_status">Case Status</label>
                            <select id="case_status" x-model="filters.case_status" class="reports-input">
                                <option value="">All Case Statuses</option>
                                <option value="DRAFT">Draft</option>
                                <option value="ACTIVE">Active</option>
                                <option value="COMPLETED">Completed</option>
                            </select>
                        </div>
                    </template>

                    <template x-if="shows('verification_status')">
                        <div class="reports-field">
                            <label class="reports-label" for="verification_status">Verification Status</label>
                            <select id="verification_status" x-model="filters.verification_status" class="reports-input">
                                <option value="">All Verification</option>
                                <option value="PENDING">Pending</option>
                                <option value="VERIFIED">Verified</option>
                                <option value="DISPUTED">Disputed</option>
                            </select>
                        </div>
                    </template>

                    <template x-if="shows('package_id')">
                        <div class="reports-field">
                            <label class="reports-label" for="package_id">Package</label>
                            <select id="package_id" x-model="filters.package_id" class="reports-input">
                                <option value="">All Packages</option>
                                <template x-for="pkg in packages" :key="pkg.id">
                                    <option :value="pkg.id" x-text="pkg.name"></option>
                                </template>
                            </select>
                        </div>
                    </template>

                    <template x-if="shows('service_type')">
                        <div class="reports-field">
                            <label class="reports-label" for="service_type">Service Type</label>
                            <input id="service_type" type="text" x-model="filters.service_type" class="reports-input" placeholder="Burial">
                        </div>
                    </template>

                    <template x-if="shows('encoded_by')">
                        <div class="reports-field">
                            <label class="reports-label" for="encoded_by">Encoded By</label>
                            <select id="encoded_by" x-model="filters.encoded_by" class="reports-input">
                                <option value="">All Users</option>
                                <template x-for="user in users" :key="user.id">
                                    <option :value="user.id" x-text="user.name"></option>
                                </template>
                            </select>
                        </div>
                    </template>

                    <template x-if="shows('interment_range')">
                        <div class="reports-field">
                            <label class="reports-label" for="interment_from">Interment From</label>
                            <input id="interment_from" type="date" x-model="filters.interment_from" class="reports-input">
                        </div>
                    </template>

                    <template x-if="shows('interment_range')">
                        <div class="reports-field">
                            <label class="reports-label" for="interment_to">Interment To</label>
                            <input id="interment_to" type="date" x-model="filters.interment_to" class="reports-input">
                        </div>
                    </template>

                    <template x-if="shows('audit_action') && auditOptions.supports_action">
                        <div class="reports-field">
                            <label class="reports-label" for="action">Action</label>
                            <input id="action" type="text" x-model="filters.action" list="audit-actions" class="reports-input" placeholder="Search action">
                            <datalist id="audit-actions">
                                <template x-for="action in auditOptions.actions" :key="action">
                                    <option :value="action"></option>
                                </template>
                            </datalist>
                        </div>
                    </template>

                    <template x-if="shows('audit_module') && auditOptions.supports_module">
                        <div class="reports-field">
                            <label class="reports-label" for="module">Module</label>
                            <input id="module" type="text" x-model="filters.module" list="audit-modules" class="reports-input" placeholder="Module or entity">
                            <datalist id="audit-modules">
                                <template x-for="module in auditOptions.modules" :key="module">
                                    <option :value="module"></option>
                                </template>
                            </datalist>
                        </div>
                    </template>
                </div>

            <div class="reports-actions">
                <div class="reports-action-chips" x-show="activeToolbarChips().length" x-cloak>
                    <template x-for="chip in activeToolbarChips()" :key="chip.label">
                        <span class="reports-chip">
                            <i :class="`bi ${chip.icon}`"></i>
                            <span x-text="chip.label"></span>
                        </span>
                    </template>
                </div>

                <div class="reports-action-buttons">
                    <button type="submit" class="reports-btn reports-btn-primary" :disabled="loading">
                        <span class="reports-spin" x-show="loading" aria-hidden="true"></span>
                        <i class="bi bi-eye" x-show="!loading"></i>
                        <span x-text="loading ? 'Generating...' : 'Preview Report'"></span>
                    </button>
                    <button type="button" class="reports-btn reports-btn-secondary" @click="openPrint" :disabled="loading || rows.length === 0">
                        <i class="bi bi-printer"></i>
                        <span>Print / Save as PDF</span>
                    </button>
                    <button type="button" class="reports-btn reports-btn-secondary" @click="openCsv" :disabled="loading || rows.length === 0">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                        <span>Export CSV</span>
                    </button>
                    <button type="button" class="reports-btn reports-btn-neutral" @click="resetFilters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset Filters</span>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="reports-card" x-show="hasPreview || loading" x-cloak>
        <div class="reports-card-head">
            <div>
                <h2 class="reports-card-title">Summary Metrics</h2>
                <div class="reports-card-copy">Snapshot of the generated preview.</div>
            </div>
        </div>
        <div class="reports-summary-grid">
            <template x-for="card in summaryCards()" :key="card.label">
                <article
                    class="reports-metric"
                    :class="{ 'is-selected': hasPreview && selectedMetric === card.key }"
                    @click="selectMetric(card.key)"
                    :title="hasPreview ? 'Click to drill down into ' + card.label : ''"
                >
                    <div class="reports-metric-icon"><i :class="`bi ${card.icon}`"></i></div>
                    <div>
                        <div class="reports-metric-label" x-text="card.label"></div>
                        <div class="reports-metric-value" x-text="card.value"></div>
                    </div>
                    <span class="reports-metric-hint" x-show="hasPreview">View details</span>
                </article>
            </template>
        </div>
    </section>

    <section class="reports-card">
        <div class="reports-preview-head">
            <div>
                <h2 class="reports-card-title" x-text="selectedMetric ? 'Report Preview — ' + metricDrillLabel() : 'Report Preview'"></h2>
                <div class="reports-card-copy" x-text="selectedMetric ? metricDrillHint() : (reportTypes[reportType] || 'Select a report type')"></div>
                <div class="reports-preview-meta" x-show="hasPreview && filterChips().length" x-cloak>
                    <template x-for="chip in filterChips()" :key="chip">
                        <span class="reports-chip" x-text="chip"></span>
                    </template>
                </div>
            </div>
            <div
                class="reports-chip"
                x-show="hasPreview"
                x-text="(selectedMetric && reportType === 'owner_branch_analytics')
                    ? (drilldownLoading ? 'Loading...' : `${drillDownRows().length} record${drillDownRows().length === 1 ? '' : 's'}`)
                    : (selectedMetric
                        ? `${drillDownRows().length} of ${rows.length} row${rows.length === 1 ? '' : 's'}`
                        : `${rows.length} row${rows.length === 1 ? '' : 's'}`)"
            ></div>
        </div>

        {{-- Active metric drill-down banner --}}
        <div class="reports-drill-banner" x-show="hasPreview && selectedMetric" x-cloak>
            <i class="bi bi-funnel-fill reports-drill-banner-icon"></i>
            <div class="reports-drill-banner-text">
                <span x-text="metricDrillLabel()"></span>
                <span class="reports-drill-banner-hint" x-text="' — ' + metricDrillHint()"></span>
            </div>
            <button type="button" class="reports-drill-clear" @click="clearMetric()">
                <i class="bi bi-x-lg"></i>
                Clear Selection
            </button>
        </div>

        <template x-if="error">
            <div class="reports-alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <strong>Unable to generate report preview.</strong>
                    <div x-text="error"></div>
                </div>
            </div>
        </template>

        <template x-if="loading">
            <div class="reports-state">
                <div>
                    <div class="reports-loading-dot"></div>
                    <div class="reports-state-title">Generating preview...</div>
                    <div class="reports-state-copy">Applying filters and preparing report rows.</div>
                </div>
            </div>
        </template>

        <template x-if="!loading && !hasPreview">
            <div class="reports-state">
                <div>
                    <div class="reports-state-icon"><i class="bi bi-bar-chart-line"></i></div>
                    <div class="reports-state-title">No report generated yet.</div>
                    <div class="reports-state-copy">Select filters and click Preview Report to view results.</div>
                </div>
            </div>
        </template>

        <template x-if="!loading && hasPreview && rows.length === 0">
            <div class="reports-state">
                <div>
                    <div class="reports-state-icon"><i class="bi bi-search"></i></div>
                    <div class="reports-state-title">No records found.</div>
                    <div class="reports-state-copy">Try adjusting the selected filters.</div>
                </div>
            </div>
        </template>

        {{-- Drilldown fetch in progress --}}
        <template x-if="drilldownLoading && selectedMetric">
            <div class="reports-state">
                <div>
                    <div class="reports-loading-dot"></div>
                    <div class="reports-state-title" x-text="'Loading ' + metricDrillLabel() + ' records...'"></div>
                    <div class="reports-state-copy">Fetching records from the server.</div>
                </div>
            </div>
        </template>

        {{-- Empty state when metric drill-down returns nothing --}}
        <template x-if="!loading && !drilldownLoading && hasPreview && rows.length > 0 && drillDownRows().length === 0 && selectedMetric">
            <div class="reports-state">
                <div>
                    <div class="reports-state-icon"><i class="bi bi-funnel"></i></div>
                    <div class="reports-state-title">No matching records.</div>
                    <div class="reports-state-copy">No matching records found for this metric under the selected filters.</div>
                </div>
            </div>
        </template>

        {{-- Branch summary strip + drill-down records table --}}
        <template x-if="!loading && !drilldownLoading && drillDownRows().length > 0">
            <div>
                {{-- Compact branch summary strip (only for owner analytics drilldown) --}}
                <template x-if="reportType === 'owner_branch_analytics' && selectedMetric && branchSummaryRows().length > 0">
                    <div class="reports-branch-strip">
                        <div class="reports-branch-strip-head">
                            <i class="bi bi-building"></i>
                            Branch Summary
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="reports-branch-strip-table">
                                <thead>
                                    <tr>
                                        <th>Branch</th>
                                        <th class="reports-cell-number">Total Cases</th>
                                        <th class="reports-cell-number">Paid</th>
                                        <th class="reports-cell-number">Partial</th>
                                        <th class="reports-cell-number">Unpaid</th>
                                        <th class="reports-cell-number">Gross Amount</th>
                                        <th class="reports-cell-number">Collected</th>
                                        <th class="reports-cell-number">Remaining Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(br, i) in branchSummaryRows()" :key="i">
                                        <tr>
                                            <td x-text="br.branch"></td>
                                            <td class="reports-cell-number" x-text="number(br.total_cases)"></td>
                                            <td class="reports-cell-number" x-text="number(br.paid_cases)"></td>
                                            <td class="reports-cell-number" x-text="number(br.partial_cases)"></td>
                                            <td class="reports-cell-number" x-text="number(br.unpaid_cases)"></td>
                                            <td class="reports-cell-number" x-text="money(br.gross_amount)"></td>
                                            <td class="reports-cell-number" x-text="money(br.collected_amount)"></td>
                                            <td class="reports-cell-number" x-text="money(br.remaining_balance)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                {{-- Main drill-down / normal records table --}}
                <div class="reports-table-wrap" :style="selectedMetric && reportType === 'owner_branch_analytics' ? 'margin-top: 10px;' : ''">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <template x-for="column in columns()" :key="column.key">
                                    <th :class="isNumericColumn(column.key) ? 'reports-cell-number' : ''" x-text="column.label"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in drillDownRows()" :key="index">
                                <tr>
                                    <template x-for="column in columns()" :key="column.key">
                                        <td :class="isNumericColumn(column.key) ? 'reports-cell-number' : ''">
                                            <template x-if="isStatusColumn(column.key)">
                                                <span :class="statusClass(row[column.key])" x-text="formatStatus(row[column.key])"></span>
                                            </template>
                                            <template x-if="!isStatusColumn(column.key)">
                                                <span x-text="displayCell(row, column)"></span>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </section>
</div>

<script>
function reportsModule(config) {
    return {
        reportTypes: config.reportTypes,
        branches: config.branches,
        packages: config.packages,
        users: config.users,
        auditOptions: config.auditOptions,
        userRole: config.userRole,
        isBranchAdmin: Boolean(config.isBranchAdmin),
        assignedBranchId: config.assignedBranchId ? String(config.assignedBranchId) : '',
        assignedBranchLabel: config.assignedBranchLabel || '',
        filters: {
            report_type: config.defaultReportType,
            branch_id: '',
            date_from: '',
            date_to: '',
            payment_status: '',
            case_status: '',
            verification_status: '',
            package_id: '',
            service_type: '',
            encoded_by: '',
            interment_from: '',
            interment_to: '',
            user_id: '',
            action: '',
            module: '',
        },
        reportType: config.defaultReportType,
        rows: [],
        summary: {},
        selectedFilters: {},
        loading: false,
        error: '',
        hasPreview: false,
        selectedMetric: null,
        drilldownRows: [],
        drilldownLoading: false,
        drilldownMode: 'cases',
        datePreset: '',
        customRangeOpen: false,
        advancedFiltersOpen: false,
        previewTimer: null,
        init() {
            const params = new URLSearchParams(window.location.search);
            Object.keys(this.filters).forEach((key) => {
                if (params.has(key)) {
                    this.filters[key] = params.get(key) || '';
                }
            });
            if (!this.reportTypes[this.filters.report_type]) {
                this.filters.report_type = config.defaultReportType;
            }
            this.enforceAssignedBranch();
            this.applyReportDefaults();
            this.reportType = this.filters.report_type;
            if (this.isOwnerAnalytics()) {
                this.syncAnalyticsPresetFromDates();
                this.queueOwnerAnalyticsPreview();
            }
        },
        enforceAssignedBranch() {
            if (this.isBranchAdmin && this.assignedBranchId) {
                this.filters.branch_id = this.assignedBranchId;
            }
        },
        isOwnerAnalytics() {
            return this.filters.report_type === 'owner_branch_analytics';
        },
        shows(field) {
            const map = {
                sales: ['branch_id', 'date_range', 'payment_status', 'case_status', 'package_id', 'service_type'],
                master_cases: ['branch_id', 'date_range', 'payment_status', 'case_status', 'verification_status', 'package_id', 'service_type', 'encoded_by', 'interment_range'],
                audit_logs: ['date_range', 'audit_user', 'audit_action', 'audit_module'],
                owner_branch_analytics: [],
            };
            return (map[this.filters.report_type] || []).includes(field);
        },
        isAdvancedField(field) {
            const map = {
                sales: ['case_status', 'package_id', 'service_type'],
                master_cases: ['case_status', 'verification_status', 'package_id', 'service_type', 'encoded_by', 'interment_range'],
                audit_logs: ['audit_action', 'audit_module'],
                owner_branch_analytics: [],
            };
            return (map[this.filters.report_type] || []).includes(field);
        },
        hasAdvancedFilters() {
            const map = {
                sales: ['case_status', 'package_id', 'service_type'],
                master_cases: ['case_status', 'verification_status', 'package_id', 'service_type', 'encoded_by', 'interment_range'],
                audit_logs: ['audit_action', 'audit_module'],
                owner_branch_analytics: [],
            };
            return (map[this.filters.report_type] || []).some((field) => {
                if (field === 'audit_action') return Boolean(this.auditOptions.supports_action);
                if (field === 'audit_module') return Boolean(this.auditOptions.supports_module);
                return this.shows(field);
            });
        },
        applyReportDefaults() {
            if (this.shows('service_type') && !this.filters.service_type) {
                this.filters.service_type = 'Burial';
            }
        },
        params() {
            this.enforceAssignedBranch();
            this.applyReportDefaults();
            return Object.fromEntries(Object.entries(this.filters).filter(([, value]) => value !== '' && value !== null));
        },
        async loadPreview() {
            if (this.previewTimer) {
                clearTimeout(this.previewTimer);
                this.previewTimer = null;
            }
            this.loading = true;
            this.error = '';
            this.hasPreview = true;
            this.selectedMetric = null;
            this.drilldownRows = [];
            this.drilldownLoading = false;
            this.drilldownMode = 'cases';
            try {
                const response = await window.axios.get(config.previewUrl, { params: this.params() });
                this.reportType = response.data.report_type;
                this.rows = response.data.rows || [];
                this.summary = response.data.summary || {};
                this.selectedFilters = response.data.filters || {};
            } catch (error) {
                this.rows = [];
                this.summary = {};
                this.selectedFilters = {};
                const validation = error.response?.data?.errors;
                this.error = validation
                    ? Object.values(validation).flat().join(' ')
                    : (error.response?.data?.message || 'Unable to generate report preview.');
            } finally {
                this.loading = false;
            }
        },
        openPrint() {
            if (!this.rows.length) return;
            const params = { ...this.params() };
            if (this.selectedMetric && this.reportType === 'owner_branch_analytics') {
                params.metric = this.selectedMetric;
            }
            window.open(`${config.printUrl}?${new URLSearchParams(params)}`, '_blank', 'noopener');
        },
        openCsv() {
            if (!this.rows.length) return;
            const params = { ...this.params() };
            if (this.selectedMetric && this.reportType === 'owner_branch_analytics') {
                params.metric = this.selectedMetric;
            }
            window.location.href = `${config.csvUrl}?${new URLSearchParams(params)}`;
        },
        resetFilters() {
            const reportType = this.filters.report_type || config.defaultReportType;
            Object.keys(this.filters).forEach((key) => this.filters[key] = '');
            this.filters.report_type = reportType;
            this.enforceAssignedBranch();
            this.applyReportDefaults();
            this.reportType = reportType;
            this.datePreset = '';
            this.customRangeOpen = false;
            this.advancedFiltersOpen = false;
            this.selectedMetric = null;
            this.drilldownRows = [];
            this.drilldownLoading = false;
            this.drilldownMode = 'cases';
            if (this.isOwnerAnalytics()) {
                this.selectDatePreset('TODAY');
            } else {
                this.rows = [];
                this.summary = {};
                this.selectedFilters = {};
                this.error = '';
                this.hasPreview = false;
                return;
            }
            this.rows = [];
            this.summary = {};
            this.selectedFilters = {};
            this.error = '';
            this.hasPreview = false;
        },
        resetReportSpecificFilters() {
            const reportType = this.filters.report_type;
            Object.keys(this.filters).forEach((key) => this.filters[key] = '');
            this.filters.report_type = reportType;
            this.enforceAssignedBranch();
            this.applyReportDefaults();
            this.reportType = reportType;
            this.datePreset = '';
            this.customRangeOpen = false;
            this.advancedFiltersOpen = false;
            this.selectedMetric = null;
            this.drilldownRows = [];
            this.drilldownLoading = false;
            this.drilldownMode = 'cases';
            if (this.isOwnerAnalytics()) {
                this.selectDatePreset('TODAY');
                return;
            }
            this.rows = [];
            this.summary = {};
            this.selectedFilters = {};
            this.error = '';
            this.hasPreview = false;
        },
        selectDatePreset(preset) {
            this.datePreset = preset;
            this.customRangeOpen = false;
            if (preset === 'TODAY') {
                this.filters.date_from = config.analyticsDates.today;
                this.filters.date_to = config.analyticsDates.today;
            } else if (preset === 'THIS_MONTH') {
                this.filters.date_from = config.analyticsDates.monthStart;
                this.filters.date_to = config.analyticsDates.today;
            } else if (preset === 'THIS_YEAR') {
                this.filters.date_from = config.analyticsDates.yearStart;
                this.filters.date_to = config.analyticsDates.today;
            }
            this.queueOwnerAnalyticsPreview();
        },
        applyCustomRange() {
            this.datePreset = 'CUSTOM';
            this.customRangeOpen = false;
            this.queueOwnerAnalyticsPreview();
        },
        syncAnalyticsPresetFromDates() {
            const from = this.filters.date_from;
            const to = this.filters.date_to;
            if (!from && !to) {
                this.datePreset = 'TODAY';
                this.filters.date_from = config.analyticsDates.today;
                this.filters.date_to = config.analyticsDates.today;
                return;
            }
            if (from === config.analyticsDates.today && to === config.analyticsDates.today) {
                this.datePreset = 'TODAY';
                return;
            }
            if (from === config.analyticsDates.monthStart && to === config.analyticsDates.today) {
                this.datePreset = 'THIS_MONTH';
                return;
            }
            if (from === config.analyticsDates.yearStart && to === config.analyticsDates.today) {
                this.datePreset = 'THIS_YEAR';
                return;
            }
            this.datePreset = 'CUSTOM';
        },
        queueOwnerAnalyticsPreview() {
            if (!this.isOwnerAnalytics()) return;
            if (this.previewTimer) clearTimeout(this.previewTimer);
            this.previewTimer = setTimeout(() => this.loadPreview(), 180);
        },
        analyticsBranchLabel() {
            if (this.isBranchAdmin) return this.assignedBranchLabel || 'Assigned Branch';
            const branch = this.branches.find((item) => String(item.id) === String(this.filters.branch_id));
            return branch ? `${branch.branch_code} - ${branch.branch_name}` : 'All Branches';
        },
        analyticsPresetLabel() {
            const labels = {
                TODAY: 'Today',
                THIS_MONTH: 'This Month',
                THIS_YEAR: 'This Year',
                CUSTOM: 'Custom Range',
            };
            return labels[this.datePreset] || 'No Date Filter';
        },
        analyticsDateLabel() {
            if (!this.filters.date_from && !this.filters.date_to) return 'No date filter';
            const from = this.filters.date_from || 'Start';
            const to = this.filters.date_to || 'Today';
            return `${from} - ${to}`;
        },
        activeToolbarChips() {
            if (this.isOwnerAnalytics()) {
                return [
                    { icon: 'bi-calendar3', label: this.analyticsDateLabel() },
                    { icon: 'bi-building', label: this.analyticsBranchLabel() },
                    { icon: 'bi-funnel', label: this.analyticsPresetLabel() },
                ];
            }

            const chips = [];
            const branch = this.branches.find((item) => String(item.id) === String(this.filters.branch_id));
            const pkg = this.packages.find((item) => String(item.id) === String(this.filters.package_id));
            const encodedBy = this.users.find((item) => String(item.id) === String(this.filters.encoded_by));
            const auditUser = this.users.find((item) => String(item.id) === String(this.filters.user_id));

            if (this.filters.branch_id || this.isBranchAdmin) chips.push({ icon: 'bi-building', label: this.isBranchAdmin ? (this.assignedBranchLabel || 'Assigned Branch Only') : (branch ? `${branch.branch_code} - ${branch.branch_name}` : `Branch #${this.filters.branch_id}`) });
            if (this.filters.date_from || this.filters.date_to) chips.push({ icon: 'bi-calendar3', label: `${this.filters.date_from || 'Start'} - ${this.filters.date_to || 'Today'}` });
            if (this.filters.payment_status) chips.push({ icon: 'bi-wallet2', label: `Payment: ${this.formatStatus(this.filters.payment_status)}` });
            if (this.filters.case_status) chips.push({ icon: 'bi-folder2-open', label: `Case: ${this.formatStatus(this.filters.case_status)}` });
            if (this.filters.verification_status) chips.push({ icon: 'bi-shield-check', label: `Verification: ${this.formatStatus(this.filters.verification_status)}` });
            if (this.filters.package_id) chips.push({ icon: 'bi-box-seam', label: pkg ? `Package: ${pkg.name}` : `Package #${this.filters.package_id}` });
            if (this.filters.service_type) chips.push({ icon: 'bi-briefcase', label: `Service: ${this.filters.service_type}` });
            if (this.filters.encoded_by) chips.push({ icon: 'bi-person-check', label: encodedBy ? `Encoded by: ${encodedBy.name}` : `Encoded by #${this.filters.encoded_by}` });
            if (this.filters.interment_from || this.filters.interment_to) chips.push({ icon: 'bi-calendar-event', label: `Interment: ${this.filters.interment_from || 'Start'} - ${this.filters.interment_to || 'Today'}` });
            if (this.filters.user_id) chips.push({ icon: 'bi-person', label: auditUser ? `User: ${auditUser.name}` : `User #${this.filters.user_id}` });
            if (this.filters.action) chips.push({ icon: 'bi-activity', label: `Action: ${this.filters.action}` });
            if (this.filters.module) chips.push({ icon: 'bi-grid', label: `Module: ${this.filters.module}` });

            return chips;
        },
        columns() {
            // In owner analytics drilldown mode return the drill-down-specific columns
            if (this.reportType === 'owner_branch_analytics' && this.selectedMetric) {
                return this.drilldownColumns();
            }
            const allColumns = {
                sales: [
                    ['case_no', 'Case No.'], ['client', 'Client'], ['deceased', 'Deceased'], ['branch', 'Branch'],
                    ['package', 'Package'], ['service_type', 'Service Type'], ['total_amount', 'Total Amount'],
                    ['total_paid', 'Total Paid'], ['balance', 'Balance'], ['payment_status', 'Payment Status'],
                    ['case_status', 'Case Status'], ['date', 'Date Created or Paid Date'],
                ],
                master_cases: [
                    ['case_no', 'Case No.'], ['case_code', 'Case Code'], ['client', 'Client'], ['deceased', 'Deceased'],
                    ['branch', 'Branch'], ['service_type', 'Service Type'], ['package', 'Package'], ['interment_date', 'Interment Date'],
                    ['payment_status', 'Payment Status'], ['case_status', 'Case Status'], ['verification_status', 'Verification Status'],
                    ['encoded_by', 'Encoded By'], ['date_created', 'Date Created'],
                ],
                audit_logs: [
                    ['date', 'Date'], ['user', 'User'], ['role', 'Role'], ['action', 'Action'], ['action_type', 'Action Type'],
                    ['module', 'Module'], ['record_id', 'Record ID'], ['branch', 'Branch'], ['status', 'Status'], ['remarks', 'Remarks'],
                ],
                owner_branch_analytics: [
                    ['branch', 'Branch'], ['total_cases', 'Total Cases'], ['paid_cases', 'Paid'], ['partial_cases', 'Partial'],
                    ['unpaid_cases', 'Unpaid'], ['gross_amount', 'Gross Amount'], ['collected_amount', 'Collected'],
                    ['remaining_balance', 'Remaining Balance'],
                ],
            };
            return (allColumns[this.reportType] || allColumns.sales).map(([key, label]) => ({ key, label }));
        },
        summaryCards() {
            const money = (value) => this.money(value || 0);
            if (this.reportType === 'audit_logs') {
                return [{ label: 'Total Records', value: this.number(this.summary.total_records || 0), icon: 'bi-list-check', key: 'total_records' }];
            }
            if (this.reportType === 'owner_branch_analytics') {
                return [
                    { label: 'Total Cases',       value: this.number(this.summary.total_cases || 0),       icon: 'bi-folder2-open',      key: 'total_cases' },
                    { label: 'Paid Cases',         value: this.number(this.summary.paid_cases || 0),        icon: 'bi-check-circle',       key: 'paid_cases' },
                    { label: 'Partial Cases',      value: this.number(this.summary.partial_cases || 0),     icon: 'bi-hourglass-split',    key: 'partial_cases' },
                    { label: 'Unpaid Cases',       value: this.number(this.summary.unpaid_cases || 0),      icon: 'bi-exclamation-circle', key: 'unpaid_cases' },
                    { label: 'Gross Amount',       value: money(this.summary.gross_amount),                 icon: 'bi-cash-stack',         key: 'gross_amount' },
                    { label: 'Collected Amount',   value: money(this.summary.collected_amount),             icon: 'bi-wallet2',            key: 'collected_amount' },
                    { label: 'Remaining Balance',  value: money(this.summary.remaining_balance),            icon: 'bi-receipt',            key: 'remaining_balance' },
                ];
            }
            return [
                { label: 'Total Records',      value: this.number(this.summary.total_records || 0), icon: 'bi-list-check',  key: 'total_records' },
                { label: 'Gross Amount',       value: money(this.summary.gross_amount),             icon: 'bi-cash-stack',  key: 'gross_amount' },
                { label: 'Collected Amount',   value: money(this.summary.collected_amount),         icon: 'bi-wallet2',     key: 'collected_amount' },
                { label: 'Remaining Balance',  value: money(this.summary.remaining_balance),        icon: 'bi-receipt',     key: 'remaining_balance' },
            ];
        },
        filterChips() {
            return Object.entries(this.selectedFilters).map(([key, value]) => `${this.headline(key)}: ${value}`);
        },
        displayCell(row, column) {
            if (this.moneyColumns().includes(column.key)) return this.money(row[column.key] || 0);
            if (this.countColumns().includes(column.key)) return this.number(row[column.key] || 0);
            return row[column.key] ?? '-';
        },
        isNumericColumn(key) {
            return this.moneyColumns().includes(key) || this.countColumns().includes(key);
        },
        isStatusColumn(key) {
            return ['payment_status', 'case_status', 'verification_status', 'status'].includes(key);
        },
        moneyColumns() {
            return ['total_amount', 'total_paid', 'balance', 'gross_amount', 'collected_amount', 'remaining_balance', 'amount_paid'];
        },
        countColumns() {
            return ['total_cases', 'paid_cases', 'partial_cases', 'unpaid_cases'];
        },
        statusClass(value) {
            const normalized = String(value || '').toLowerCase().replaceAll('_', '-');
            const known = ['paid', 'completed', 'verified', 'success', 'partial', 'active', 'pending', 'unpaid', 'draft', 'disputed'];
            const suffix = known.includes(normalized) ? normalized : 'neutral';
            return `reports-status-badge reports-status-${suffix}`;
        },
        formatStatus(value) {
            return this.headline(String(value || '-').toLowerCase().replaceAll('_', ' '));
        },
        headline(value) {
            return String(value || '').replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
        },
        money(value) {
            return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0));
        },
        number(value) {
            return new Intl.NumberFormat('en-PH').format(Number(value || 0));
        },

        // ── Metric drill-down ─────────────────────────────────────────────
        async selectMetric(key) {
            if (!this.hasPreview) return;
            // Toggle off if same card is clicked again
            if (this.selectedMetric === key) {
                this.clearMetric();
                return;
            }
            this.selectedMetric = key;
            this.drilldownRows = [];
            this.drilldownMode = 'cases';
            // Owner analytics: fetch real case/payment records from backend
            if (this.reportType === 'owner_branch_analytics') {
                await this.fetchDrilldown(key);
            }
        },
        async fetchDrilldown(metric) {
            this.drilldownLoading = true;
            try {
                const response = await window.axios.get(config.drilldownUrl, {
                    params: { ...this.params(), metric },
                });
                this.drilldownRows = response.data.rows || [];
                this.drilldownMode = response.data.mode || 'cases';
            } catch (_err) {
                this.drilldownRows = [];
                this.drilldownMode = 'cases';
            } finally {
                this.drilldownLoading = false;
            }
        },
        clearMetric() {
            this.selectedMetric = null;
            this.drilldownRows = [];
            this.drilldownLoading = false;
            this.drilldownMode = 'cases';
        },
        drillDownRows() {
            if (!this.selectedMetric) return this.rows;

            // Owner analytics: use server-fetched case/payment records
            if (this.reportType === 'owner_branch_analytics') {
                return this.drilldownRows;
            }

            // Other report types: client-side filter on the already-loaded rows
            const key = this.selectedMetric;
            if (key === 'total_cases' || key === 'total_records') return this.rows;
            if (key === 'paid_cases')    return this.rows.filter(r => String(r.payment_status || '').toUpperCase() === 'PAID');
            if (key === 'partial_cases') return this.rows.filter(r => String(r.payment_status || '').toUpperCase() === 'PARTIAL');
            if (key === 'unpaid_cases')  return this.rows.filter(r => String(r.payment_status || '').toUpperCase() === 'UNPAID');
            if (key === 'gross_amount')      return this.rows.filter(r => Number(r.total_amount || 0) > 0);
            if (key === 'collected_amount')  return this.rows.filter(r => Number(r.total_paid   || 0) > 0);
            if (key === 'remaining_balance') return this.rows.filter(r => Number(r.balance       || 0) > 0);
            return this.rows;
        },
        // Filtered branch summary rows for the compact strip shown above drill-down records
        branchSummaryRows() {
            if (!this.selectedMetric || this.reportType !== 'owner_branch_analytics') return [];
            const key = this.selectedMetric;
            if (key === 'total_cases') return this.rows;
            const colMap = {
                paid_cases: 'paid_cases', partial_cases: 'partial_cases', unpaid_cases: 'unpaid_cases',
                gross_amount: 'gross_amount', collected_amount: 'collected_amount', remaining_balance: 'remaining_balance',
            };
            const col = colMap[key];
            return col ? this.rows.filter(r => Number(r[col] || 0) > 0) : this.rows;
        },
        // Columns for the drill-down table (differs from normal branch analytics columns)
        drilldownColumns() {
            if (this.drilldownMode === 'payments') {
                return [
                    { key: 'payment_record_no', label: 'Payment Record No.' },
                    { key: 'case_no',           label: 'Case No.' },
                    { key: 'branch',            label: 'Branch' },
                    { key: 'client_deceased',   label: 'Client / Deceased' },
                    { key: 'payment_method',    label: 'Payment Method' },
                    { key: 'amount_paid',       label: 'Amount Paid' },
                    { key: 'payment_date',      label: 'Payment Date' },
                ];
            }
            return [
                { key: 'case_no',           label: 'Case No.' },
                { key: 'branch',            label: 'Branch' },
                { key: 'client',            label: 'Client' },
                { key: 'deceased',          label: 'Deceased' },
                { key: 'service',           label: 'Service' },
                { key: 'payment_status',    label: 'Payment Status' },
                { key: 'gross_amount',      label: 'Gross Amount' },
                { key: 'collected_amount',  label: 'Collected Amount' },
                { key: 'remaining_balance', label: 'Remaining Balance' },
                { key: 'last_payment_date', label: 'Last Payment Date' },
            ];
        },
        metricDrillLabel() {
            const map = {
                total_cases:       'Total Cases',
                total_records:     'Total Records',
                paid_cases:        'Paid Cases',
                partial_cases:     'Partial Cases',
                unpaid_cases:      'Unpaid Cases',
                gross_amount:      'Gross Amount',
                collected_amount:  'Collected Amount',
                remaining_balance: 'Remaining Balance',
            };
            return map[this.selectedMetric] || '';
        },
        metricDrillHint() {
            const map = {
                total_cases:       'Showing all case records for the selected filters.',
                total_records:     'Showing all records for the selected filters.',
                paid_cases:        'Showing paid case records for the selected filters.',
                partial_cases:     'Showing partial case records for the selected filters.',
                unpaid_cases:      'Showing unpaid case records for the selected filters.',
                gross_amount:      'Showing case records contributing to gross amount.',
                collected_amount:  'Showing payment records contributing to collected amount.',
                remaining_balance: 'Showing case records with remaining unpaid balances.',
            };
            return map[this.selectedMetric] || '';
        },
    };
}
</script>
@endsection
