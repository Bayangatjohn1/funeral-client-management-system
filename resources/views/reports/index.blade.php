@extends('layouts.panel')

@section('page_title', 'Reports & Analytics')
@section('page_desc', 'Analytics charts, branch performance, and operational reports.')
@section('hide_layout_topbar', '1')

@section('content')
<div
    class="reports-page"
    data-reports-module-shell
    x-data="reportsModule({
        defaultReportType: @js($defaultReportType),
        reportTypes: @js($reportTypes),
        previewUrl: @js(route('reports.preview')),
        indexUrl: @js(route('reports.index')),
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
    <div class="reports-sr-only" aria-label="Available report users">
        @foreach($users as $reportUser)
            <span>{{ $reportUser->name }}</span>
        @endforeach
    </div>

    @include('reports.partials.module-tabs', [
        'activeModule' => 'reports',
        'reportTypes' => $reportTypes,
        'currentReportType' => $defaultReportType,
        'users' => $users,
        'auditOptions' => $auditOptions,
    ])

    <style>
        [x-cloak] { display: none !important; }
        .reports-page { max-width: none; margin: 0; padding: 12px var(--panel-content-inline, 20px) 20px; display: grid; gap: 14px; }
        .reports-card {
            background: var(--records-card, var(--card));
            border: 1.25px solid var(--records-border, var(--border));
            border-radius: 12px;
            box-shadow: none;
        }
        .reports-filter-card {
            min-height: 76px;
            display: flex;
            align-items: center;
            padding: 0;
        }
        .reports-sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
        .reports-role-badge {
            display: inline-flex; align-items: center; gap: 8px; border: 1px solid #dbe4ef; background: #FAFAF7;
            color: #333333; border-radius: 999px; padding: 7px 12px; font-size: 12px; font-weight: 700; white-space: nowrap;
        }
        .reports-card-head { padding: 16px 18px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .reports-card-title { margin: 0; font-family: var(--font-heading); color: var(--ink); font-size: 18px; font-weight: 700; }
        .reports-card-copy { margin-top: 3px; color: var(--ink-muted); font-size: 12px; }
        .reports-config-form { width: 100%; padding: 12px 14px; display: grid; gap: 12px; }
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
        .reports-filter-grid .reports-field.reports-field-status {
            flex: 0 0 260px;
            width: 260px;
            max-width: 260px;
        }
        .reports-filter-grid .reports-label {
            position: absolute; top: -7px; left: 10px; z-index: 1; background: var(--card); padding: 0 5px;
            font-size: 9px; line-height: 1; color: #5F685F;
        }
        .reports-field { display: grid; gap: 6px; min-width: 0; }
        .reports-label { color: var(--ink-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .reports-help { color: var(--ink-muted); font-size: 11px; line-height: 1.35; }
        .reports-input {
            width: 100%; min-height: 40px; border: 1.25px solid var(--records-border, var(--border)); border-radius: 10px; background: var(--surface-muted);
            color: var(--records-text, var(--ink)); padding: 8px 10px; font-size: 12.5px; font-weight: 600; outline: none; transition: border-color .16s ease, background-color .16s ease, color .16s ease;
        }
        select.reports-input {
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 2.35rem;
            background-color: var(--surface-muted);
            background-image: none;
        }
        select.reports-input option {
            background-color: #f7f8f1 !important;
            color: #1f2d20 !important;
            font-weight: 650;
        }
        select.reports-input option:checked {
            background-color: #2f5131 !important;
            background: #2f5131 !important;
            color: #f7f8f1 !important;
            -webkit-text-fill-color: #f7f8f1 !important;
            font-weight: 800;
        }
        .reports-input:hover { background-color: var(--records-hover, #C5D3BC); border-color: #8EA083; }
        .reports-input:focus { border-color: #8EA083; background-color: var(--records-card-alt, #DCE6D6); box-shadow: none; }
        .reports-actions {
            display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center;
            border-top: 1px solid var(--border); padding-top: 12px;
        }
        .reports-toolbar-trailing {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-left: auto;
            min-width: 0;
        }
        .reports-action-chips { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; min-width: 0; }
        .reports-action-chips .reports-chip { display: inline-flex; align-items: center; gap: 6px; }
        .reports-action-buttons { display: flex; flex-wrap: nowrap; gap: 8px; justify-content: flex-end; margin-left: auto; }
        .reports-filter-reset { flex: 0 0 auto; }
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
        .reports-card-head-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex: 0 0 auto;
        }
        .reports-metric {
            border: 1.25px solid var(--records-border, var(--border)); border-radius: 12px; background: var(--records-card-alt, #fff); padding: 14px;
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
        .reports-preview-body {
            position: relative;
            min-height: 260px;
        }
        .reports-preview-body.is-updating {
            cursor: progress;
        }
        .reports-preview-loading-overlay {
            position: absolute;
            inset: 0;
            z-index: 6;
            display: grid;
            place-items: start end;
            padding: 14px;
            background: rgba(234, 241, 226, 0.76);
            pointer-events: none;
        }
        .reports-preview-loading-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid var(--records-border, var(--border));
            background: var(--records-card-alt, #fff);
            color: var(--ink, #263126);
            font-size: 12px;
            font-weight: 800;
            box-shadow: none;
        }
        .reports-preview-loading-pill .reports-spin {
            border-color: rgba(62, 74, 61, 0.22);
            border-top-color: #3E4A3D;
        }
        .reports-preview-body.is-switching {
            animation: reportsPreviewSoftHold 0.18s ease-out both;
        }
        @keyframes reportsPreviewSoftHold {
            from { opacity: 0.82; }
            to { opacity: 1; }
        }
        .reports-state { min-height: 260px; display: grid; place-items: center; padding: 24px; text-align: center; }
        .reports-state-icon { width: 54px; height: 54px; border-radius: 16px; display: grid; place-items: center; margin: 0 auto 12px; background: #f1f5f9; color: #5F685F; font-size: 24px; }
        .reports-state-title { color: var(--ink); font-family: var(--font-heading); font-size: 18px; font-weight: 700; }
        .reports-state-copy { margin-top: 5px; color: var(--ink-muted); font-size: 13px; }
        .reports-loading-dot { width: 32px; height: 32px; border-radius: 999px; border: 3px solid #dbeafe; border-top-color: #3E4A3D; animation: reportsSpin .75s linear infinite; margin: 0 auto 12px; }
        .reports-table-wrap { overflow: auto; }
        .reports-table { min-width: 1040px; width: 100%; border-collapse: separate; border-spacing: 0; }
        .reports-table th {
            position: sticky; top: 0; z-index: 1; background: var(--records-card-strong, #FAFAF7); color: #5F685F; border-bottom: 1.25px solid var(--records-border, var(--border));
            padding: 12px 13px; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; text-align: left;
        }
        .reports-table td { border-bottom: 1.25px solid var(--records-border, #edf2f7); padding: 12px 13px; font-size: 13px; color: var(--ink); vertical-align: top; }
        .reports-table tbody tr:hover td { background: var(--records-hover, #FAFAF7); }
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
            border: 1.25px solid var(--records-border, #dbe4ef); border-radius: 10px; background: var(--surface-muted); color: #333333; padding: 0 34px 0 12px;
        }
        .reports-analytics-select {
            appearance: none; border: 0; background: transparent; color: inherit; outline: none; min-height: 40px; width: 100%;
            font-size: 13px; font-weight: 800; cursor: pointer;
        }
        .reports-analytics-select-chev { position: absolute; right: 12px; pointer-events: none; color: #5F685F; font-size: 12px; }
        .reports-analytics-seg { display: inline-flex; flex-wrap: nowrap; gap: 6px; align-items: center; min-width: 0; }
        .reports-analytics-seg-item,
        .reports-analytics-more {
            min-height: 40px; border: 1.25px solid var(--records-border, #dbe4ef); border-radius: 10px; background: var(--surface-muted); color: #333333;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 13px;
            font-size: 12.5px; font-weight: 650; white-space: nowrap; transition: background .16s ease, border-color .16s ease, color .16s ease;
        }
        .reports-analytics-seg-item:hover,
        .reports-analytics-more:hover { background: var(--records-hover, #C5D3BC); border-color: #8EA083; }
        .reports-analytics-seg-item.active,
        .reports-analytics-more.active { background: #3E4A3D; border-color: #3E4A3D; color: #fff; }
        .reports-more-filters {
            min-height: 40px; border: 1.25px solid var(--records-border, #dbe4ef); border-radius: 10px; background: var(--surface-muted); color: #333333;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 12px;
            font-size: 12.5px; font-weight: 650; white-space: nowrap; transition: background .16s ease, border-color .16s ease, color .16s ease;
        }
        .reports-more-filters:hover { background: var(--records-hover, #C5D3BC); border-color: #8EA083; }
        .reports-more-filters.active { background: #3E4A3D; border-color: #3E4A3D; color: #fff; }
        .reports-analytics-custom { position: relative; }
        .reports-analytics-date-chev { font-size: 11px; }
        .reports-analytics-popover {
            position: absolute; top: calc(100% + 8px); right: 0; z-index: 20; width: min(300px, calc(100vw - 32px));
            background: var(--records-card-alt, #fff); border: 1.25px solid var(--records-border, #dbe4ef); border-radius: 12px; box-shadow: none;
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
        .reports-analytics-pop-input:focus { border-color: #3E4A3D; box-shadow: none; }
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
            box-shadow: none;
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
            .reports-filter-grid .reports-field,
            .reports-filter-grid .reports-field.reports-field-status,
            .reports-filter-grid .reports-field.reports-field-audit-user {
                flex-basis: 100% !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: none !important;
            }
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
            .reports-toolbar-trailing {
                width: 100%;
                margin-left: 0;
                flex-direction: column;
                align-items: stretch;
            }
            .reports-action-chips,
            .reports-action-buttons { width: 100%; margin-left: 0; }
            .reports-card-head-actions { width: 100%; justify-content: stretch; }
            .reports-card-head-actions .reports-btn { width: 100%; }
            .reports-filter-reset { width: 100%; margin-left: 0 !important; }
            .reports-btn { width: 100%; }
        }

        /* ── Dark mode overrides ──────────────────────────────────────────── */
        html[data-theme='dark'] .reports-role-badge {
            background: #1a2f46;
            border-color: #2e4560;
            color: #cfe0f5;
        }
        html[data-theme='dark'] .reports-input {
            border-color: #2e4560;
        }
        html[data-theme='dark'] .reports-filter-grid .reports-label {
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-metric {
            background: #17283b;
            border-color: #2e4560 !important;
        }
        html[data-theme='dark'] .reports-metric:hover:not(.is-selected) {
            background: #1e3349;
            border-color: #3d5a7a !important;
        }
        html[data-theme='dark'] .reports-metric.is-selected {
            background: #16365f !important;
            border-color: #4a7cb5 !important;
            box-shadow: 0 0 0 2px rgba(74, 124, 181, 0.22) !important;
        }
        html[data-theme='dark'] .reports-metric-icon {
            background: #1f344d;
            color: #cfe0f5;
        }
        html[data-theme='dark'] .reports-metric-hint {
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-chip {
            background: #1a2f46;
            border-color: #2e4560;
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-alert {
            background: #2d1515;
            border-color: #7f2020;
            color: #fca5a5;
        }
        html[data-theme='dark'] .reports-state-icon {
            background: #1f344d;
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-loading-dot {
            border-color: #2e4560;
            border-top-color: #60a5fa;
        }
        html[data-theme='dark'] .reports-preview-loading-overlay {
            background: rgba(20, 28, 36, 0.58);
        }
        html[data-theme='dark'] .reports-table th {
            background: #1f344d;
            color: #8ca6c4;
            border-bottom-color: #2e4560;
        }
        html[data-theme='dark'] .reports-table td {
            border-bottom-color: #243447;
        }
        html[data-theme='dark'] .reports-table tbody tr:hover td {
            background: #1e3349;
        }
        html[data-theme='dark'] .reports-status-paid,
        html[data-theme='dark'] .reports-status-completed,
        html[data-theme='dark'] .reports-status-verified,
        html[data-theme='dark'] .reports-status-success {
            background: #0b3b2b;
            color: #6ee7b7;
        }
        html[data-theme='dark'] .reports-status-partial,
        html[data-theme='dark'] .reports-status-active,
        html[data-theme='dark'] .reports-status-pending {
            background: #432a11;
            color: #fdba74;
        }
        html[data-theme='dark'] .reports-status-unpaid,
        html[data-theme='dark'] .reports-status-draft,
        html[data-theme='dark'] .reports-status-disputed {
            background: #4a1515;
            color: #fca5a5;
        }
        html[data-theme='dark'] .reports-status-neutral {
            background: #2e3a4b;
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-analytics-branch {
            background: #1a2f46;
            border-color: #2e4560;
            color: #cfe0f5;
        }
        html[data-theme='dark'] .reports-analytics-seg-item,
        html[data-theme='dark'] .reports-analytics-more,
        html[data-theme='dark'] .reports-more-filters {
            background: #1a2f46;
            border-color: #2e4560;
            color: #cfe0f5;
        }
        html[data-theme='dark'] .reports-analytics-seg-item:hover,
        html[data-theme='dark'] .reports-analytics-more:hover,
        html[data-theme='dark'] .reports-more-filters:hover {
            background: #1e3349;
            border-color: #3d5a7a;
        }
        html[data-theme='dark'] .reports-analytics-seg-item.active,
        html[data-theme='dark'] .reports-analytics-more.active,
        html[data-theme='dark'] .reports-more-filters.active {
            background: #e2ecf9;
            border-color: #e2ecf9;
            color: #10253a;
        }
        html[data-theme='dark'] .reports-analytics-popover {
            background: #17283b;
            border-color: #2e4560;
            box-shadow: none;
        }
        html[data-theme='dark'] .reports-analytics-pop-label,
        html[data-theme='dark'] .reports-analytics-pop-field label {
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-analytics-pop-input {
            background: #1a2f46;
            border-color: #2e4560;
            color: #cfe0f5;
        }
        html[data-theme='dark'] .reports-analytics-pop-reset {
            background: #1a2f46;
            border-color: #2e4560;
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-btn-secondary {
            background: #1a2f46;
            border-color: #2e4560;
            color: #cfe0f5;
        }
        html[data-theme='dark'] .reports-btn-secondary:hover:not(:disabled) {
            background: #1e3349;
            border-color: #3d5a7a;
        }
        html[data-theme='dark'] .reports-btn-neutral {
            background: #17283b;
            border-color: #2e4560;
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-btn-neutral:hover:not(:disabled) {
            background: #1e3349;
            border-color: #3d5a7a;
        }
        html[data-theme='dark'] .reports-branch-strip {
            border-color: #2e4560;
        }
        html[data-theme='dark'] .reports-branch-strip-head {
            background: #1f344d;
            border-bottom-color: #2e4560;
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-branch-strip-table th {
            background: #1f344d;
            border-bottom-color: #243447;
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-branch-strip-table td {
            border-bottom-color: #243447;
            color: #cfe0f5;
        }
        html[data-theme='dark'] .reports-drill-banner {
            background: rgba(59, 130, 246, 0.08);
            border-color: #2e4560;
            color: #93c5fd;
        }
        html[data-theme='dark'] .reports-drill-banner-icon {
            color: #93c5fd;
        }
        html[data-theme='dark'] .reports-drill-banner-hint {
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-drill-clear {
            background: #1a2f46;
            border-color: #2e4560;
            color: #8ca6c4;
        }
        html[data-theme='dark'] .reports-drill-clear:hover {
            border-color: #60a5fa;
            color: #93c5fd;
        }
        html[data-theme='dark'] .reports-scope-pill {
            color: #34d399;
        }

        .reports-page {
            min-height:calc(100vh - var(--topbar-h, 62px));
            padding:18px !important;
            display:flex !important;
            flex-direction:column;
            align-items:stretch;
            gap:14px;
            background:#C4D2BE;
        }

        .reports-toast {
            position:fixed;
            top:1rem;
            right:1rem;
            z-index:1200;
            display:flex;
            align-items:center;
            gap:.55rem;
            max-width:calc(100vw - 2rem);
            border:1px solid #8EA083;
            border-radius:.75rem;
            background:#2F3A2E;
            color:#F7FAF3;
            padding:.72rem .9rem;
            font-size:.88rem;
            font-weight:650;
            line-height:1.35;
            box-shadow:none !important;
            pointer-events:none;
            animation:reportsToastIn .18s ease-out, reportsToastOut .22s ease-in 3.8s forwards;
        }

        @keyframes reportsToastIn {
            from { opacity:0; transform:translateY(-.35rem); }
            to { opacity:1; transform:translateY(0); }
        }

        @keyframes reportsToastOut {
            to { opacity:0; transform:translateY(-.35rem); visibility:hidden; }
        }

        @media (max-width: 720px) {
            .reports-toast {
                top:auto;
                right:1rem;
                bottom:1rem;
                left:1rem;
                justify-content:center;
            }
        }

        .reports-card {
            background:#D3DEC9 !important;
            border:1px solid #AEBFA6 !important;
            border-radius:.75rem !important;
            box-shadow:none !important;
            overflow:visible;
            flex:0 0 auto;
        }

        .reports-card:last-of-type {
            display:flex;
            flex:1 1 auto;
            min-height:24rem;
            flex-direction:column;
            overflow:hidden;
        }

        .reports-card-head,
        .reports-preview-head {
            border-bottom:1px solid #AEBFA6 !important;
            padding:.95rem 1rem !important;
            background:#D3DEC9 !important;
        }

        .reports-card:first-of-type .reports-card-head {
            display:none;
        }

        .reports-card-title {
            color:#293229 !important;
            font-size:1.05rem !important;
            font-weight:720 !important;
        }

        .reports-card-copy {
            color:#566653 !important;
            font-size:.86rem !important;
            font-weight:560 !important;
        }

        .reports-config-form {
            padding:.65rem .75rem !important;
            gap:.6rem !important;
            align-content:start;
        }

        .reports-config-toolbar {
            align-items:center !important;
            gap:.55rem !important;
            flex-wrap:wrap !important;
        }

        .reports-filter-grid {
            gap:.55rem !important;
            flex-wrap:wrap !important;
        }

        .reports-field {
            position:relative;
        }

        .reports-field-control {
            position:relative;
            display:block;
        }

        .reports-field-control > .bi {
            position:absolute;
            left:.8rem;
            top:50%;
            z-index:2;
            transform:translateY(-50%);
            color:#566653;
            font-size:.86rem;
            line-height:1;
            pointer-events:none;
        }

        .reports-field-control:has(select.reports-input)::after {
            content:"";
            position:absolute;
            right:.85rem;
            top:50%;
            width:.44rem;
            height:.44rem;
            border-right:2px solid #566653;
            border-bottom:2px solid #566653;
            pointer-events:none;
            transform:translateY(-62%) rotate(45deg);
            transition:transform .16s ease, border-color .16s ease;
            z-index:2;
        }

        .reports-field-control.is-open:has(select.reports-input)::after {
            border-color:#293229;
            transform:translateY(-38%) rotate(225deg);
        }

        .reports-config-toolbar > .reports-field,
        .reports-filter-grid .reports-field,
        .reports-filter-grid .reports-field.reports-field-wide {
            flex:1 1 13.5rem !important;
            min-width:12.5rem !important;
            max-width:none !important;
        }

        .reports-filter-grid .reports-field.reports-field-status {
            flex:0 0 16.25rem !important;
            width:16.25rem !important;
            min-width:16.25rem !important;
            max-width:16.25rem !important;
        }

        .reports-filter-grid .reports-field.reports-field-audit-user {
            flex:0 0 17.5rem !important;
            width:17.5rem !important;
            min-width:17.5rem !important;
            max-width:17.5rem !important;
        }

        .reports-config-toolbar > .reports-field:first-child {
            flex:0 1 16rem !important;
        }

        .reports-label {
            position:absolute !important;
            top:-.45rem !important;
            left:.85rem !important;
            z-index:2 !important;
            background:#D3DEC9 !important;
            color:#566653 !important;
            padding:0 .35rem !important;
            font-size:.68rem !important;
            font-weight:680 !important;
            line-height:1 !important;
            letter-spacing:.04em !important;
        }

        .reports-config-toolbar > .reports-field .reports-label,
        .reports-filter-grid .reports-label,
        .reports-analytics-bar .reports-label {
            display:none !important;
        }

        .reports-advanced-filter-row .reports-label,
        .reports-analytics-advanced .reports-label {
            display:block !important;
            position:static !important;
            background:transparent !important;
            padding:0 !important;
            color:#566653 !important;
            font-size:.7rem !important;
            font-weight:680 !important;
            line-height:1.2 !important;
            letter-spacing:.04em !important;
        }

        .reports-help {
            display:none !important;
        }

        .reports-input,
        .reports-analytics-branch,
        .reports-analytics-seg-item,
        .reports-analytics-more,
        .reports-more-filters,
        .reports-analytics-pop-input {
            min-height:2.5rem !important;
            border:1.25px solid #AEBFA6 !important;
            border-radius:.625rem !important;
            background:#F7F9F3 !important;
            color:#293229 !important;
            font-size:.84rem !important;
            font-weight:650 !important;
            box-shadow:none !important;
            cursor:pointer;
        }

        .reports-input {
            padding:.42rem 2.25rem .42rem .85rem !important;
        }

        .reports-field-control .reports-input {
            padding-left:2.2rem !important;
        }

        select.reports-input {
            appearance:none !important;
            -webkit-appearance:none !important;
            -moz-appearance:none !important;
            background-color:#F7F9F3 !important;
            background-image:none !important;
        }

        .reports-input:hover,
        .reports-analytics-branch:hover,
        .reports-analytics-seg-item:hover,
        .reports-analytics-more:hover,
        .reports-more-filters:hover,
        .reports-btn-secondary:hover:not(:disabled),
        .reports-btn-neutral:hover:not(:disabled),
        .reports-metric:hover:not(.is-selected) {
            background-color:#DDE8D6 !important;
            border-color:#8EA083 !important;
            transform:none !important;
        }

        .reports-input:focus,
        .reports-analytics-pop-input:focus {
            border-color:#8EA083 !important;
            background-color:#F4F8EF !important;
            box-shadow:none !important;
        }

        .reports-more-filters.active,
        .reports-analytics-seg-item.active,
        .reports-analytics-more.active {
            background:#344333 !important;
            border-color:#344333 !important;
            color:#fff !important;
        }

        .reports-advanced-filter-row {
            border-top:1px solid #AEBFA6 !important;
            padding-top:.75rem !important;
            gap:.65rem !important;
        }

        .reports-actions {
            border-top:1px solid #AEBFA6 !important;
            padding-top:.6rem !important;
            gap:.55rem !important;
            flex:0 0 auto;
        }

        .reports-action-buttons {
            gap:.5rem !important;
        }

        .reports-filter-reset {
            flex:0 0 auto !important;
            margin-left:auto !important;
        }

        .reports-btn {
            min-height:2.5rem !important;
            border-radius:.625rem !important;
            padding:0 .9rem !important;
            font-size:.84rem !important;
            font-weight:650 !important;
            box-shadow:none !important;
            cursor:pointer;
            transform:none !important;
        }

        .reports-btn-primary {
            background:#344333 !important;
            border-color:#344333 !important;
            color:#fff !important;
        }

        .reports-btn-primary:hover:not(:disabled) {
            background:#2F3A2E !important;
        }

        .reports-btn-secondary,
        .reports-btn-neutral {
            background:#E9F0E4 !important;
            border:1px solid #AEBFA6 !important;
            color:#3E4A3D !important;
        }

        .reports-export-menu {
            position:relative;
        }

        .reports-export-menu[aria-disabled="true"] {
            opacity:.48;
            pointer-events:none;
        }

        .reports-export-menu summary {
            list-style:none;
        }

        .reports-export-menu summary::-webkit-details-marker {
            display:none;
        }

        .reports-export-options {
            position:absolute;
            right:0;
            top:calc(100% + .45rem);
            z-index:40;
            min-width:12rem;
            border:1px solid #AEBFA6;
            border-radius:.65rem;
            background:#E9F0E4;
            overflow:hidden;
        }

        @media (max-width: 820px) {
            .reports-export-options {
                left: 0 !important;
                right: auto !important;
                width: min(12rem, calc(100vw - 1.5rem)) !important;
                min-width: 0 !important;
                max-width: calc(100vw - 1.5rem) !important;
            }
        }

        .reports-export-options button {
            width:100%;
            min-height:2.5rem;
            border:0;
            background:transparent;
            color:#293229;
            display:flex;
            align-items:center;
            gap:.5rem;
            padding:0 .85rem;
            font-size:.86rem;
            font-weight:650;
            cursor:pointer;
            text-align:left;
        }

        .reports-export-options button:hover {
            background:#DDE8D6;
        }

        .reports-chip,
        .reports-role-badge {
            border:1px solid #AEBFA6 !important;
            background:#E1E7D9 !important;
            color:#566653 !important;
            font-weight:650 !important;
            box-shadow:none !important;
        }

        .reports-summary-grid {
            grid-template-columns:repeat(4,minmax(0,1fr)) !important;
            gap:.65rem !important;
            padding:.75rem !important;
        }

        .reports-metric {
            background:#E1E7D9 !important;
            border:1px solid #AEBFA6 !important;
            border-radius:.65rem !important;
            box-shadow:none !important;
        }

        .reports-metric-icon {
            background:transparent !important;
            color:#566653 !important;
            width:2rem !important;
            height:2rem !important;
        }

        .reports-metric-label {
            color:#566653 !important;
            font-size:.74rem !important;
            font-weight:680 !important;
        }

        .reports-metric-value {
            color:#293229 !important;
            font-size:1.3rem !important;
            font-weight:720 !important;
        }

        .reports-table-wrap {
            background:#D3DEC9 !important;
            padding:.75rem;
            flex:1 1 auto;
            min-height:0;
        }

        .reports-table {
            min-width:64rem;
            background:#F7F9F3;
            border:1px solid #AEBFA6;
            border-radius:.75rem;
            overflow:hidden;
        }

        .reports-table th {
            background:#C7D5BE !important;
            color:#566653 !important;
            border-bottom:1px solid #AEBFA6 !important;
            font-weight:720 !important;
        }

        .reports-table td {
            border-bottom:1px solid #D4DEC9 !important;
            color:#293229 !important;
            font-weight:540;
        }

        .reports-table tbody tr:hover td {
            background:#E9F0E4 !important;
        }

        .reports-table-wrap {
            border-radius:.875rem;
            border:1px solid #AEBFA6;
            background:#C7D5BE !important;
            overflow:auto;
        }

        .reports-table {
            min-width:76rem;
            border:0;
            border-radius:.75rem;
            overflow:hidden;
            background:#F7F9F3;
        }

        .reports-table th {
            position:sticky;
            top:0;
            z-index:2;
            padding:.78rem 1rem !important;
            background:#C4D2BC !important;
            color:#4E5D4D !important;
            border-bottom:1px solid #9FAF98 !important;
            font-size:.72rem !important;
            font-weight:760 !important;
            letter-spacing:.045em !important;
            line-height:1.15;
            vertical-align:middle;
        }

        .reports-table td {
            padding:.86rem 1rem !important;
            border-bottom:1px solid #D7E0D0 !important;
            background:#F8FAF4;
            color:#253026 !important;
            font-size:.84rem !important;
            font-weight:570;
            line-height:1.45;
            vertical-align:middle;
        }

        .reports-table tbody tr:nth-child(even) td {
            background:#F1F5EC;
        }

        .reports-table tbody tr:last-child td {
            border-bottom:0 !important;
        }

        .reports-table tbody tr:hover td {
            background:#E6EFDE !important;
        }

        .reports-table td:first-child,
        .reports-table th:first-child {
            padding-left:1.1rem !important;
        }

        .reports-table td:last-child,
        .reports-table th:last-child {
            padding-right:1.1rem !important;
        }

        .reports-cell-number {
            text-align:right !important;
            white-space:nowrap;
            font-variant-numeric:tabular-nums;
        }

        .reports-table td.reports-cell-number {
            font-weight:680;
        }

        .reports-table .reports-col-case_no,
        .reports-table .reports-col-case_code,
        .reports-table .reports-col-payment_record_no,
        .reports-table .reports-col-record_id {
            width:7.25rem;
            min-width:7.25rem;
            white-space:nowrap;
            font-weight:760;
        }

        .reports-table .reports-col-branch {
            min-width:13.5rem;
        }

        .reports-table .reports-col-client,
        .reports-table .reports-col-deceased,
        .reports-table .reports-col-client_deceased,
        .reports-table .reports-col-user,
        .reports-table .reports-col-encoded_by {
            min-width:12rem;
            max-width:18rem;
            overflow-wrap:anywhere;
        }

        .reports-table .reports-col-service,
        .reports-table .reports-col-service_type,
        .reports-table .reports-col-package,
        .reports-table .reports-col-payment_method,
        .reports-table .reports-col-role,
        .reports-table .reports-col-module,
        .reports-table .reports-col-action_type {
            min-width:9.5rem;
            white-space:nowrap;
        }

        .reports-table .reports-col-payment_status,
        .reports-table .reports-col-case_status,
        .reports-table .reports-col-verification_status,
        .reports-table .reports-col-status {
            width:9rem;
            min-width:9rem;
            text-align:center;
        }

        .reports-table .reports-col-payment_status .reports-status-badge,
        .reports-table .reports-col-case_status .reports-status-badge,
        .reports-table .reports-col-verification_status .reports-status-badge,
        .reports-table .reports-col-status .reports-status-badge {
            justify-content:center;
            min-width:5.75rem;
        }

        .reports-table .reports-col-date,
        .reports-table .reports-col-date_created,
        .reports-table .reports-col-payment_date,
        .reports-table .reports-col-last_payment_date,
        .reports-table .reports-col-interment_date {
            width:10.25rem;
            min-width:10.25rem;
            white-space:nowrap;
            color:#4E5D4D !important;
            font-variant-numeric:tabular-nums;
        }

        .reports-table .reports-col-total_amount,
        .reports-table .reports-col-total_paid,
        .reports-table .reports-col-balance,
        .reports-table .reports-col-gross_amount,
        .reports-table .reports-col-collected_amount,
        .reports-table .reports-col-remaining_balance,
        .reports-table .reports-col-amount_paid {
            min-width:10.75rem;
        }

        .reports-table .reports-col-action,
        .reports-table .reports-col-remarks {
            min-width:16rem;
            max-width:26rem;
            overflow-wrap:anywhere;
        }

        .reports-branch-strip {
            margin:.75rem 1rem 0;
            border:1px solid #AEBFA6;
            border-radius:.875rem;
            overflow:hidden;
            background:#F7F9F3;
        }

        .reports-branch-strip-head {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.75rem;
            padding:.68rem .9rem;
            background:#E9F0E4;
            border-bottom:1px solid #AEBFA6;
            color:#4E5D4D;
            font-size:.72rem;
            font-weight:780;
            letter-spacing:.045em;
            text-transform:uppercase;
        }

        .reports-branch-strip-title {
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            min-width:0;
        }

        .reports-branch-strip-note {
            color:#667362;
            font-size:.72rem;
            font-weight:650;
            letter-spacing:0;
            text-transform:none;
            white-space:nowrap;
        }

        .reports-branch-strip-table {
            width:100%;
            min-width:56rem;
            border-collapse:separate;
            border-spacing:0;
            background:#F7F9F3;
        }

        .reports-branch-strip-table th {
            padding:.62rem .85rem;
            background:#F4F7EF;
            border-bottom:1px solid #D4DEC9;
            color:#5C6959;
            font-size:.68rem;
            font-weight:760;
            letter-spacing:.045em;
            line-height:1.15;
            text-align:left;
            text-transform:uppercase;
            white-space:nowrap;
        }

        .reports-branch-strip-table td {
            padding:.7rem .85rem;
            border-bottom:1px solid #DCE4D6;
            background:#F9FBF6;
            color:#293229;
            font-size:.82rem;
            font-weight:560;
        }

        .reports-branch-strip-table tbody tr:nth-child(even) td {
            background:#F2F6EE;
        }

        .reports-branch-strip-table tbody tr:hover td {
            background:#E8F0E1;
        }

        .reports-branch-strip-table tbody tr:last-child td {
            border-bottom:0;
        }

        .reports-branch-strip-table .reports-col-branch {
            min-width:15rem;
            font-weight:720;
        }

        .reports-branch-strip-table td.reports-cell-number {
            font-weight:680;
        }

        @media (max-width: 720px) {
            .reports-branch-strip {
                margin-inline:0;
            }

            .reports-branch-strip-head {
                align-items:flex-start;
                flex-direction:column;
                gap:.35rem;
            }

            .reports-branch-strip-note {
                white-space:normal;
            }
        }

        .reports-state {
            background:#D3DEC9 !important;
            flex:1 1 auto;
            min-height:20rem !important;
        }

        .reports-state-icon {
            background:#E1E7D9 !important;
            color:#566653 !important;
        }

        .reports-alert,
        .reports-branch-strip,
        .reports-drill-banner {
            box-shadow:none !important;
        }

        .reports-analytics-popover {
            background:#E9F0E4 !important;
            border:1px solid #AEBFA6 !important;
            box-shadow:none !important;
        }

        /* Shared Reports & Analytics component layer */
        .reports-page {
            --module-surface: #D3DEC9;
            --module-surface-soft: #DCE6D6;
            --module-surface-strong: #C7D5BE;
            --module-canvas: #F8FAF4;
            --module-canvas-soft: #F1F5EC;
            --module-warm: #E4DFCB;
            --module-border: #AEBFA6;
            --module-border-strong: #9FAF98;
            --module-text: #263126;
            --module-muted: #5F6D59;
            --module-brand: #2F5233;
        }

        .reports-card {
            background: var(--module-surface) !important;
            border-color: var(--module-border) !important;
            border-radius: 8px !important;
        }

        .reports-card-head,
        .reports-preview-head {
            min-height: 58px;
            padding: 0.9rem 1rem !important;
            background: transparent !important;
            border-bottom-color: var(--module-border) !important;
        }

        .reports-card-title {
            color: var(--module-text) !important;
            font-size: 1.08rem !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
        }

        .reports-card-copy {
            color: var(--module-muted) !important;
            font-size: 0.84rem !important;
            font-weight: 650 !important;
        }

        .reports-summary-grid {
            gap: 0.7rem !important;
            padding: 0.75rem !important;
        }

        .reports-metric {
            min-height: 82px !important;
            align-items: center !important;
            gap: 0.85rem !important;
            padding: 0.85rem 0.95rem !important;
            background: var(--module-surface-soft) !important;
            border-color: var(--module-border) !important;
            border-radius: 7px !important;
        }

        .reports-metric:nth-child(even) {
            background: var(--module-warm) !important;
        }

        .reports-metric-icon {
            width: 2.2rem !important;
            height: 2.2rem !important;
            border-radius: 8px !important;
            background: rgba(250, 251, 247, 0.58) !important;
            color: var(--module-muted) !important;
            font-size: 0.98rem !important;
        }

        .reports-metric-label {
            color: var(--module-muted) !important;
            font-size: 0.76rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.08em !important;
        }

        .reports-metric-value {
            color: var(--module-text) !important;
            font-size: 1.22rem !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
        }

        .reports-chip,
        .reports-role-badge,
        .reports-status-badge {
            border-color: var(--module-border) !important;
            box-shadow: none !important;
        }

        .reports-btn,
        .reports-input,
        .reports-filter-reset {
            border-radius: 7px !important;
        }

        .reports-table-wrap,
        .reports-branch-strip {
            border-color: var(--module-border) !important;
            border-radius: 8px !important;
            background: var(--module-surface-strong) !important;
        }

        .reports-table,
        .reports-branch-strip-table {
            background: var(--module-canvas) !important;
        }

        .reports-table th,
        .reports-branch-strip-table th {
            background: var(--module-surface-strong) !important;
            border-bottom-color: var(--module-border-strong) !important;
            color: var(--module-muted) !important;
            font-size: 0.72rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.055em !important;
        }

        .reports-table td,
        .reports-branch-strip-table td {
            background: var(--module-canvas) !important;
            border-bottom-color: #D7E0D0 !important;
            color: var(--module-text) !important;
            font-size: 0.84rem !important;
        }

        .reports-table tbody tr:nth-child(even) td,
        .reports-branch-strip-table tbody tr:nth-child(even) td {
            background: var(--module-canvas-soft) !important;
        }

        .reports-table tbody tr:hover td,
        .reports-branch-strip-table tbody tr:hover td {
            background: #E6EFDE !important;
        }

        .reports-preview-head {
            align-items: flex-start !important;
            padding: 1rem 1.1rem !important;
        }

        .reports-preview-meta {
            gap: 0.45rem !important;
            margin-top: 0.7rem !important;
        }

        .reports-preview-meta .reports-chip,
        .reports-preview-head > .reports-chip {
            background: #EAF1E3 !important;
            border-color: #B8C8AE !important;
            color: #566653 !important;
            font-size: 0.72rem !important;
            font-weight: 760 !important;
        }

        .reports-drill-banner {
            margin: 0.75rem 1rem 0 !important;
            min-height: 3rem !important;
            padding: 0.65rem 0.75rem !important;
            border: 1px solid #B8C8AE !important;
            border-radius: 8px !important;
            background: #E5ECDE !important;
            box-shadow: none !important;
        }

        .reports-drill-banner-icon {
            width: 1.85rem !important;
            height: 1.85rem !important;
            display: inline-grid !important;
            place-items: center !important;
            border-radius: 7px !important;
            background: rgba(248, 250, 244, 0.72) !important;
            color: #3E4A3D !important;
            font-size: 0.86rem !important;
        }

        .reports-drill-banner-text {
            display: flex !important;
            align-items: baseline !important;
            gap: 0.45rem !important;
            color: #2B352A !important;
            font-size: 0.86rem !important;
            font-weight: 780 !important;
            line-height: 1.25 !important;
        }

        .reports-drill-banner-hint {
            color: #667362 !important;
            font-size: 0.82rem !important;
            font-weight: 620 !important;
        }

        .reports-drill-clear {
            min-height: 2.2rem !important;
            padding: 0 0.75rem !important;
            border-radius: 7px !important;
            border: 1px solid #B8C8AE !important;
            background: #F8FAF4 !important;
            color: #4E5D4D !important;
            font-size: 0.78rem !important;
            font-weight: 760 !important;
            box-shadow: none !important;
        }

        .reports-branch-strip {
            margin: 0.7rem 1rem 0 !important;
            border-radius: 8px !important;
            background: #F7FAF3 !important;
        }

        .reports-branch-strip-head {
            min-height: 2.7rem !important;
            padding: 0.65rem 0.85rem !important;
            background: #EAF1E3 !important;
            border-bottom-color: #B8C8AE !important;
            color: #566653 !important;
        }

        .reports-branch-strip-table {
            min-width: 0 !important;
            table-layout: fixed !important;
        }

        .reports-branch-strip-table th,
        .reports-branch-strip-table td {
            padding: 0.65rem 0.8rem !important;
            font-size: 0.78rem !important;
        }

        .reports-table-wrap.is-drilldown {
            margin: 0.7rem 1rem 1rem !important;
            padding: 0 !important;
            border-radius: 8px !important;
            background: #F7FAF3 !important;
            border: 1px solid #AEBFA6 !important;
            scrollbar-color: #879782 #E6EFDE;
        }

        .reports-table.is-drilldown-table {
            min-width: 68rem !important;
            border-radius: 0 !important;
        }

        .reports-table.is-drilldown-table th {
            padding: 0.72rem 0.85rem !important;
            font-size: 0.7rem !important;
        }

        .reports-table.is-drilldown-table td {
            padding: 0.78rem 0.85rem !important;
            font-size: 0.82rem !important;
        }

        .reports-table.is-drilldown-table .reports-col-branch {
            min-width: 11rem !important;
        }

        .reports-table.is-drilldown-table .reports-col-client,
        .reports-table.is-drilldown-table .reports-col-deceased,
        .reports-table.is-drilldown-table .reports-col-client_deceased {
            min-width: 10.5rem !important;
            max-width: 14rem !important;
        }

        .reports-table.is-drilldown-table .reports-col-gross_amount,
        .reports-table.is-drilldown-table .reports-col-collected_amount,
        .reports-table.is-drilldown-table .reports-col-remaining_balance,
        .reports-table.is-drilldown-table .reports-col-amount_paid {
            min-width: 9.25rem !important;
        }

        .reports-table.is-drilldown-table .reports-col-last_payment_date {
            min-width: 7.5rem !important;
            width: 7.5rem !important;
        }

        @media (max-width: 720px) {
            .reports-page,
            .reports-page *,
            .reports-page *::before,
            .reports-page *::after {
                box-sizing: border-box;
            }

            .reports-page {
                max-width: 100vw;
                overflow-x: clip;
                padding-inline: clamp(.5rem, 3vw, .75rem) !important;
            }

            .reports-card,
            .reports-filter-card,
            .reports-config-form,
            .reports-config-toolbar,
            .reports-filter-grid,
            .reports-card-head,
            .reports-preview-head,
            .reports-card-head-actions,
            .reports-toolbar-trailing,
            .reports-action-buttons {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }

            .reports-card-head,
            .reports-preview-head {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: .75rem !important;
            }

            .reports-card-head > *,
            .reports-preview-head > * {
                min-width: 0;
                max-width: 100%;
            }

            .reports-card-head-actions,
            .reports-action-buttons {
                display: grid !important;
                grid-template-columns: 1fr;
                justify-content: stretch !important;
            }

            .reports-btn,
            .reports-filter-reset {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                white-space: normal;
            }

            .reports-summary-grid {
                grid-template-columns:1fr !important;
                gap:.65rem !important;
                padding:.75rem !important;
            }

            .reports-metric {
                display:grid !important;
                grid-template-columns:2.4rem minmax(0, 1fr) !important;
                align-items:center !important;
                min-height:4.6rem !important;
                gap:.75rem !important;
                padding:.8rem .9rem !important;
            }

            .reports-metric-icon {
                width:2.25rem !important;
                height:2.25rem !important;
            }

            .reports-metric-label,
            .reports-metric-value {
                min-width:0;
                overflow-wrap:anywhere;
                text-align:left;
            }
        }

        @media (max-width: 900px) {
            .reports-drill-banner,
            .reports-preview-head {
                align-items: stretch !important;
                flex-direction: column !important;
            }

            .reports-drill-banner-text {
                align-items: flex-start !important;
                flex-direction: column !important;
                gap: 0.2rem !important;
            }

            .reports-drill-clear {
                align-self: flex-start !important;
            }
        }
    </style>

    <div class="reports-toast no-print" role="status" aria-live="polite" data-page-context-toast>
        <i class="bi bi-clipboard-data"></i>
        <span>You are viewing the reports page.</span>
    </div>

    <div class="reports-module-layout">
        @include('reports.partials.module-rail', [
            'activeModule' => 'reports',
            'reportTypes' => $reportTypes,
            'currentReportType' => $defaultReportType,
            'quickStats' => [
                ['label' => 'Branches', 'value' => number_format($branches->count())],
                ['label' => 'Period', 'value' => 'This Year'],
                ['label' => 'Mode', 'value' => $userRole === 'owner' ? 'Owner' : 'Admin'],
            ],
        ])

        <div class="reports-module-main">
    <section class="reports-card reports-filter-card" aria-label="Report filters">
        <form id="reportsConfigForm" class="reports-config-form" @submit.prevent="loadPreview">
            <h2 class="reports-sr-only">Report Configuration</h2>
            <div class="reports-config-toolbar" :class="{ 'is-owner-analytics': isOwnerAnalytics() }">
                @include('reports.partials.analytics-filter-bar')

                <div class="reports-filter-grid" x-show="!isOwnerAnalytics()" x-cloak>
                <div class="reports-toolbar-trailing">
                    <div class="reports-action-chips" x-show="activeToolbarChips().length" x-cloak>
                        <template x-for="chip in activeToolbarChips()" :key="chip.label">
                            <span class="reports-chip">
                                <i :class="`bi ${chip.icon}`"></i>
                                <span x-text="chip.label"></span>
                            </span>
                        </template>
                    </div>

                    <button type="button" class="reports-btn reports-btn-neutral reports-filter-reset" @click="resetFilters(); loadPreview()">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset Filters</span>
                    </button>
                </div>
                </div>
            </div>
                <div class="reports-advanced-filter-row" x-show="advancedFiltersOpen && hasAdvancedFilters()" x-cloak>
                    <template x-if="shows('date_range') && datePreset === 'CUSTOM'">
                        <div class="reports-field">
                            <label class="reports-label" for="date_from">Date From</label>
                            <span class="reports-field-control">
                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                <input id="date_from" type="date" x-model="filters.date_from" class="reports-input">
                            </span>
                        </div>
                    </template>

                    <template x-if="shows('date_range') && datePreset === 'CUSTOM'">
                        <div class="reports-field">
                            <label class="reports-label" for="date_to">Date To</label>
                            <span class="reports-field-control">
                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                <input id="date_to" type="date" x-model="filters.date_to" class="reports-input">
                            </span>
                        </div>
                    </template>

                    <template x-if="shows('case_status')">
                        <div class="reports-field">
                            <label class="reports-label" for="case_status">Case Status</label>
                            <span class="reports-field-control">
                                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                                <select id="case_status" x-model="filters.case_status" class="reports-input">
                                    <option value="">All Case Statuses</option>
                                    <option value="DRAFT">Draft</option>
                                    <option value="ACTIVE">Active</option>
                                    <option value="COMPLETED">Completed</option>
                                </select>
                            </span>
                        </div>
                    </template>

                    <template x-if="shows('verification_status')">
                        <div class="reports-field">
                            <label class="reports-label" for="verification_status">Verification Status</label>
                            <span class="reports-field-control">
                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                <select id="verification_status" x-model="filters.verification_status" class="reports-input">
                                    <option value="">All Verification</option>
                                    <option value="PENDING">Pending</option>
                                    <option value="VERIFIED">Verified</option>
                                    <option value="DISPUTED">Disputed</option>
                                </select>
                            </span>
                        </div>
                    </template>

                    <template x-if="shows('package_id')">
                        <div class="reports-field">
                            <label class="reports-label" for="package_id">Package</label>
                            <span class="reports-field-control">
                                <i class="bi bi-box-seam" aria-hidden="true"></i>
                                <select id="package_id" x-model="filters.package_id" class="reports-input">
                                    <option value="">All Packages</option>
                                    <template x-for="pkg in packages" :key="pkg.id">
                                        <option :value="pkg.id" x-text="pkg.name"></option>
                                    </template>
                                </select>
                            </span>
                        </div>
                    </template>

                    <template x-if="shows('service_type')">
                        <div class="reports-field">
                            <label class="reports-label" for="service_type">Service Type</label>
                            <span class="reports-field-control">
                                <i class="bi bi-briefcase" aria-hidden="true"></i>
                                <input id="service_type" type="text" x-model="filters.service_type" class="reports-input" placeholder="Burial">
                            </span>
                        </div>
                    </template>

                    <template x-if="shows('encoded_by')">
                        <div class="reports-field">
                            <label class="reports-label" for="encoded_by">Encoded By</label>
                            <span class="reports-field-control">
                                <i class="bi bi-person-check" aria-hidden="true"></i>
                                <select id="encoded_by" x-model="filters.encoded_by" class="reports-input">
                                    <option value="">All Users</option>
                                    <template x-for="user in users" :key="user.id">
                                        <option :value="user.id" x-text="user.name"></option>
                                    </template>
                                </select>
                            </span>
                        </div>
                    </template>

                    <template x-if="shows('interment_range')">
                        <div class="reports-field">
                            <label class="reports-label" for="interment_from">Interment From</label>
                            <span class="reports-field-control">
                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                <input id="interment_from" type="date" x-model="filters.interment_from" class="reports-input">
                            </span>
                        </div>
                    </template>

                    <template x-if="shows('interment_range')">
                        <div class="reports-field">
                            <label class="reports-label" for="interment_to">Interment To</label>
                            <span class="reports-field-control">
                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                <input id="interment_to" type="date" x-model="filters.interment_to" class="reports-input">
                            </span>
                        </div>
                    </template>

                    <template x-if="shows('audit_action') && auditOptions.supports_action">
                        <div class="reports-field">
                            <label class="reports-label" for="action">Action</label>
                            <span class="reports-field-control">
                                <i class="bi bi-activity" aria-hidden="true"></i>
                                <input id="action" type="text" x-model="filters.action" list="audit-actions" class="reports-input" placeholder="Search action">
                            </span>
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
                            <span class="reports-field-control">
                                <i class="bi bi-grid" aria-hidden="true"></i>
                                <input id="module" type="text" x-model="filters.module" list="audit-modules" class="reports-input" placeholder="Module or entity">
                            </span>
                            <datalist id="audit-modules">
                                <template x-for="module in auditOptions.modules" :key="module">
                                    <option :value="module"></option>
                                </template>
                            </datalist>
                        </div>
                    </template>
                </div>

        </form>
    </section>

    <section class="reports-card">
        <div class="reports-card-head">
            <div>
                <h2 class="reports-card-title">Summary Metrics</h2>
                <div class="reports-card-copy">Snapshot of the generated preview.</div>
            </div>
            <div class="reports-card-head-actions">
                <button type="submit" form="reportsConfigForm" class="reports-btn reports-btn-primary" :disabled="loading">
                    <span class="reports-spin" x-show="loading" aria-hidden="true"></span>
                    <i class="bi bi-eye" x-show="!loading"></i>
                    <span x-text="loading ? 'Generating...' : 'Preview Report'"></span>
                </button>
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
                <h2 class="reports-card-title" x-text="selectedMetric ? metricDrillLabel() + ' Preview' : 'Report Preview'"></h2>
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
                <span class="reports-drill-banner-hint" x-text="metricDrillHint()"></span>
            </div>
            <button type="button" class="reports-drill-clear" @click="clearMetric()">
                <i class="bi bi-x-lg"></i>
                Clear Selection
            </button>
        </div>

        {{-- Branch scoped preview banner --}}
        <div class="reports-drill-banner" x-show="hasPreview && isBranchFilteredPreview()" x-cloak>
            <i class="bi bi-building reports-drill-banner-icon"></i>
            <div class="reports-drill-banner-text">
                <span>Branch filtered</span>
                <span class="reports-drill-banner-hint" x-text="' — Showing ' + analyticsBranchLabel() + ' only.'"></span>
            </div>
            <button type="button" class="reports-drill-clear" @click="returnToBranchSummary()">
                <i class="bi bi-arrow-left"></i>
                Back to Branch Summary
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

        <div class="reports-preview-body" :class="{ 'is-updating': loading, 'is-switching': tabTransitioning }" :aria-busy="loading ? 'true' : 'false'">
        <div class="reports-preview-loading-overlay" x-show="loading && (previewLoaded || tabTransitioning)" x-cloak>
            <div class="reports-preview-loading-pill">
                <span class="reports-spin" aria-hidden="true"></span>
                <span x-text="tabTransitioning ? 'Switching report...' : 'Updating preview...'"></span>
            </div>
        </div>

        <template x-if="loading && !previewLoaded && !tabTransitioning">
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
                    <div class="reports-state-title">Data table is ready to generate.</div>
                    <div class="reports-state-copy">Choose filters, then click Preview Report to load the table and summary.</div>
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
                            <span class="reports-branch-strip-title">
                                <i class="bi bi-building" aria-hidden="true"></i>
                                <span>Branch Summary</span>
                            </span>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="reports-branch-strip-table">
                                <thead>
                                    <tr>
                                        <th class="reports-col-branch">Branch</th>
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
                                            <td class="reports-col-branch">
                                                <span x-text="br.branch"></span>
                                            </td>
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
                <div class="reports-table-wrap" :class="{ 'is-drilldown': selectedMetric && reportType === 'owner_branch_analytics' }">
                    <table class="reports-table" :class="{ 'is-drilldown-table': selectedMetric && reportType === 'owner_branch_analytics' }">
                        <thead>
                            <tr>
                                <template x-for="column in columns()" :key="column.key">
                                    <th :class="[columnClass(column.key), isNumericColumn(column.key) ? 'reports-cell-number' : '']" x-text="column.label"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in drillDownRows()" :key="index">
                                <tr>
                                    <template x-for="column in columns()" :key="column.key">
                                        <td :class="[columnClass(column.key), isNumericColumn(column.key) ? 'reports-cell-number' : '']">
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
        </div>
    </section>
        </div>
    </div>
</div>

<script data-reports-module-page-script>
window.reportsModule = function reportsModule(config) {
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
        previewLoaded: false,
        tabTransitioning: false,
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
            } else {
                this.syncReportPresetFromDates();
            }
            window.addEventListener('popstate', () => {
                const restoredParams = new URLSearchParams(window.location.search);
                Object.keys(this.filters).forEach((key) => {
                    this.filters[key] = restoredParams.get(key) || '';
                });
                if (!this.reportTypes[this.filters.report_type]) {
                    this.filters.report_type = config.defaultReportType;
                }
                this.enforceAssignedBranch();
                this.applyReportDefaults();
                this.reportType = this.filters.report_type;
                this.syncReportPresetFromDates();
                this.$nextTick(() => this.loadPreview());
            });
            this.$nextTick(() => this.loadPreview());
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
            if (this.shows('date_range') && this.datePreset === 'CUSTOM') {
                return true;
            }
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
        syncAddressBar(mode = 'replace') {
            if (!config.indexUrl) return;

            const url = new URL(config.indexUrl, window.location.origin);
            Object.entries(this.params()).forEach(([key, value]) => {
                if (value !== '' && value !== null) {
                    url.searchParams.set(key, value);
                }
            });

            const method = mode === 'push' ? 'pushState' : 'replaceState';
            window.history[method]({ reportsModule: true }, '', url.toString());
        },
        selectReportType(type) {
            if (!this.reportTypes[type] || this.filters.report_type === type) return;

            this.tabTransitioning = true;
            this.filters.report_type = type;
            this.reportType = type;
            this.selectedMetric = '';
            this.resetReportSpecificFilters();
            this.syncAddressBar('push');
            window.setTimeout(() => {
                this.tabTransitioning = false;
            }, 180);
        },
        async loadPreview() {
            if (this.previewTimer) {
                clearTimeout(this.previewTimer);
                this.previewTimer = null;
            }
            this.closeExportMenu();
            this.loading = true;
            this.error = '';
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
                this.hasPreview = true;
                this.previewLoaded = true;
                this.syncAddressBar();
                if (this.exportDisabled()) {
                    this.closeExportMenu();
                }
            } catch (error) {
                this.rows = [];
                this.summary = {};
                this.selectedFilters = {};
                this.closeExportMenu();
                const validation = error.response?.data?.errors;
                this.error = validation
                    ? Object.values(validation).flat().join(' ')
                    : (error.response?.data?.message || 'Unable to generate report preview.');
                this.hasPreview = true;
                this.previewLoaded = true;
            } finally {
                this.loading = false;
                this.tabTransitioning = false;
                if (this.exportDisabled()) {
                    this.closeExportMenu();
                }
            }
        },
        exportDisabled() {
            return this.loading || this.rows.length === 0;
        },
        closeExportMenu() {
            this.$refs.exportMenu?.removeAttribute('open');
            this.$refs.headerExportMenu?.removeAttribute('open');
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
            }
            this.rows = [];
            this.summary = {};
            this.selectedFilters = {};
            this.error = '';
            this.hasPreview = false;
            this.previewLoaded = false;
        },
        resetReportSpecificFilters(options = {}) {
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
            this.syncReportPresetFromDates();
            this.rows = [];
            this.summary = {};
            this.selectedFilters = {};
            this.error = '';
            this.hasPreview = false;
            this.previewLoaded = Boolean(options.preservePreviewLoaded);
        },
        selectReportDatePreset(preset) {
            if (this.isOwnerAnalytics()) {
                this.selectDatePreset(preset || 'TODAY');
                return;
            }
            if (!preset) {
                this.filters.date_from = '';
                this.filters.date_to = '';
                return;
            }
            if (preset === 'TODAY') {
                this.filters.date_from = config.analyticsDates.today;
                this.filters.date_to = config.analyticsDates.today;
            } else if (preset === 'THIS_MONTH') {
                this.filters.date_from = config.analyticsDates.monthStart;
                this.filters.date_to = config.analyticsDates.today;
            } else if (preset === 'THIS_YEAR') {
                this.filters.date_from = config.analyticsDates.yearStart;
                this.filters.date_to = config.analyticsDates.today;
            } else if (preset === 'CUSTOM') {
                this.advancedFiltersOpen = true;
            }
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
        },
        applyCustomRange() {
            this.datePreset = 'CUSTOM';
            this.customRangeOpen = false;
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
        syncReportPresetFromDates() {
            if (this.isOwnerAnalytics()) return;
            const from = this.filters.date_from;
            const to = this.filters.date_to;
            if (!from && !to) {
                this.datePreset = '';
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
            this.advancedFiltersOpen = true;
        },
        queueOwnerAnalyticsPreview() {},
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
            if (this.filters.date_from || this.filters.date_to) chips.push({ icon: 'bi-calendar3', label: this.datePreset === 'CUSTOM' ? `${this.filters.date_from || 'Start'} - ${this.filters.date_to || 'Today'}` : this.analyticsPresetLabel() });
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
                    ['case_code', 'Case Code'], ['client', 'Client'], ['deceased', 'Deceased'], ['branch', 'Branch'],
                    ['package', 'Package'], ['service_type', 'Service Type'], ['total_amount', 'Total Amount'],
                    ['total_paid', 'Total Paid'], ['balance', 'Balance'], ['payment_status', 'Payment Status'],
                    ['case_status', 'Case Status'], ['date', 'Date Created or Paid Date'],
                ],
                master_cases: [
                    ['case_code', 'Case Code'], ['client', 'Client'], ['deceased', 'Deceased'],
                    ['branch', 'Branch'], ['service_type', 'Service Type'], ['package', 'Package'], ['interment_date', 'Interment Date'],
                    ['payment_status', 'Payment Status'], ['case_status', 'Case Status'],
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
        columnClass(key) {
            return `reports-col-${String(key || '')}`;
        },
        isBranchFilteredPreview() {
            return this.reportType === 'owner_branch_analytics'
                && !this.isBranchAdmin
                && Boolean(this.filters.branch_id);
        },
        async returnToBranchSummary() {
            if (this.reportType !== 'owner_branch_analytics' || this.isBranchAdmin) return;

            this.filters.branch_id = '';
            this.selectedMetric = null;
            this.drilldownRows = [];
            this.drilldownLoading = false;
            this.drilldownMode = 'cases';
            this.syncAnalyticsPresetFromDates();
            await this.loadPreview();
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
                    { key: 'case_code',         label: 'Case Code' },
                    { key: 'branch',            label: 'Branch' },
                    { key: 'client_deceased',   label: 'Client / Deceased' },
                    { key: 'payment_method',    label: 'Payment Method' },
                    { key: 'amount_paid',       label: 'Amount Paid' },
                    { key: 'payment_date',      label: 'Payment Date' },
                ];
            }
            return [
                { key: 'case_code',         label: 'Case Code' },
                { key: 'branch',            label: 'Branch' },
                { key: 'client',            label: 'Client' },
                { key: 'deceased',          label: 'Deceased' },
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
};
</script>
@endsection
