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
        .reports-page { max-width: 1480px; margin: 0 auto; padding: 18px; display: grid; gap: 16px; }
        .reports-hero,
        .reports-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        }
        .reports-hero { padding: 22px; display: flex; align-items: center; justify-content: space-between; gap: 18px; }
        .reports-hero-main { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .reports-hero-icon {
            width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center;
            background: #eef2ff; color: #1e3a8a; font-size: 22px; flex: 0 0 auto;
        }
        .reports-title { margin: 0; color: var(--ink); font-family: var(--font-heading); font-size: 25px; font-weight: 700; letter-spacing: 0; }
        .reports-subtitle { margin-top: 4px; color: var(--ink-muted); font-size: 13px; }
        .reports-role-badge {
            display: inline-flex; align-items: center; gap: 8px; border: 1px solid #dbe4ef; background: #f8fafc;
            color: #334155; border-radius: 999px; padding: 7px 12px; font-size: 12px; font-weight: 700; white-space: nowrap;
        }
        .reports-card-head { padding: 16px 18px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .reports-card-title { margin: 0; font-family: var(--font-heading); color: var(--ink); font-size: 18px; font-weight: 700; }
        .reports-card-copy { margin-top: 3px; color: var(--ink-muted); font-size: 12px; }
        .reports-config-form { padding: 18px; display: grid; gap: 16px; }
        .reports-config-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .reports-config-toolbar > .reports-field { width: min(100%, 360px); gap: 0; }
        .reports-config-toolbar > .reports-field .reports-label,
        .reports-config-toolbar > .reports-field .reports-help { display: none; }
        .reports-config-toolbar .reports-analytics-filter { flex: 1 1 680px; min-width: 0; }
        .reports-filter-grid {
            display: flex; flex-wrap: wrap; gap: 10px; align-items: center; flex: 1 1 720px; min-width: 0;
        }
        .reports-filter-grid .reports-field { position: relative; width: auto; min-width: 154px; max-width: 240px; flex: 0 1 auto; gap: 0; }
        .reports-filter-grid .reports-field.reports-field-wide { min-width: 210px; max-width: 300px; }
        .reports-filter-grid .reports-label {
            position: absolute; top: -7px; left: 10px; z-index: 1; background: var(--card); padding: 0 5px;
            font-size: 9px; line-height: 1; color: #64748b;
        }
        .reports-field { display: grid; gap: 6px; min-width: 0; }
        .reports-label { color: var(--ink-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .reports-help { color: var(--ink-muted); font-size: 11px; line-height: 1.35; }
        .reports-input {
            width: 100%; min-height: 42px; border: 1px solid #dbe4ef; border-radius: 10px; background: var(--card);
            color: var(--ink); padding: 9px 11px; font-size: 13px; outline: none; transition: border-color .16s ease, box-shadow .16s ease;
        }
        .reports-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .14); }
        .reports-actions {
            display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;
            border-top: 1px solid var(--border); padding-top: 16px;
        }
        .reports-action-chips { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; min-width: 0; }
        .reports-action-chips .reports-chip { display: inline-flex; align-items: center; gap: 6px; }
        .reports-action-buttons { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; margin-left: auto; }
        .reports-btn {
            min-height: 40px; border-radius: 10px; padding: 0 14px; display: inline-flex; align-items: center; justify-content: center;
            gap: 8px; font-size: 13px; font-weight: 800; border: 1px solid transparent; transition: opacity .16s ease, transform .16s ease, background .16s ease;
        }
        .reports-btn:hover:not(:disabled) { transform: translateY(-1px); }
        .reports-btn:disabled { opacity: .48; cursor: not-allowed; }
        .reports-btn-primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .reports-btn-secondary { background: #fff; border-color: #cbd5e1; color: #334155; }
        .reports-btn-neutral { background: #f8fafc; border-color: #dbe4ef; color: #475569; }
        .reports-spin { width: 14px; height: 14px; border-radius: 999px; border: 2px solid rgba(255,255,255,.45); border-top-color: #fff; animation: reportsSpin .75s linear infinite; }
        @keyframes reportsSpin { to { transform: rotate(360deg); } }
        .reports-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; padding: 16px; }
        .reports-metric {
            border: 1px solid var(--border); border-radius: 12px; background: #fff; padding: 14px;
            display: flex; gap: 12px; align-items: flex-start; min-width: 0;
        }
        .reports-metric-icon { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: #f1f5f9; color: #334155; flex: 0 0 auto; }
        .reports-metric-label { color: var(--ink-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .reports-metric-value { margin-top: 5px; color: var(--ink); font-family: var(--font-heading); font-size: 21px; font-weight: 700; line-height: 1.05; overflow-wrap: anywhere; }
        .reports-preview-head { padding: 16px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .reports-preview-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .reports-chip { border: 1px solid #dbe4ef; background: #f8fafc; color: #475569; border-radius: 999px; padding: 4px 9px; font-size: 11px; font-weight: 700; }
        .reports-scope-pill { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; color: #0f766e; font-size: 11px; font-weight: 800; }
        .reports-alert { margin: 16px; padding: 12px 14px; border-radius: 12px; display: flex; gap: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 13px; }
        .reports-state { min-height: 260px; display: grid; place-items: center; padding: 24px; text-align: center; }
        .reports-state-icon { width: 54px; height: 54px; border-radius: 16px; display: grid; place-items: center; margin: 0 auto 12px; background: #f1f5f9; color: #475569; font-size: 24px; }
        .reports-state-title { color: var(--ink); font-family: var(--font-heading); font-size: 18px; font-weight: 700; }
        .reports-state-copy { margin-top: 5px; color: var(--ink-muted); font-size: 13px; }
        .reports-loading-dot { width: 32px; height: 32px; border-radius: 999px; border: 3px solid #dbeafe; border-top-color: #2563eb; animation: reportsSpin .75s linear infinite; margin: 0 auto 12px; }
        .reports-table-wrap { overflow: auto; }
        .reports-table { min-width: 1040px; width: 100%; border-collapse: separate; border-spacing: 0; }
        .reports-table th {
            position: sticky; top: 0; z-index: 1; background: #f8fafc; color: #475569; border-bottom: 1px solid var(--border);
            padding: 12px 13px; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; text-align: left;
        }
        .reports-table td { border-bottom: 1px solid #edf2f7; padding: 12px 13px; font-size: 13px; color: var(--ink); vertical-align: top; }
        .reports-table tbody tr:hover td { background: #f8fafc; }
        .reports-cell-number { text-align: right !important; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .reports-status-badge { display: inline-flex; align-items: center; min-height: 24px; border-radius: 999px; padding: 3px 9px; font-size: 11px; font-weight: 800; }
        .reports-status-paid, .reports-status-completed, .reports-status-verified, .reports-status-success { background:#dcfce7; color:#166534; }
        .reports-status-partial, .reports-status-active, .reports-status-pending { background:#fef3c7; color:#92400e; }
        .reports-status-unpaid, .reports-status-draft, .reports-status-disputed { background:#fee2e2; color:#991b1b; }
        .reports-status-neutral { background:#e2e8f0; color:#334155; }
        .reports-analytics-filter { display: grid; gap: 12px; }
        .reports-analytics-bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .reports-analytics-branch {
            min-height: 42px; min-width: 160px; display: inline-flex; align-items: center; gap: 8px; position: relative;
            border: 1px solid #dbe4ef; border-radius: 10px; background: #fff; color: #334155; padding: 0 34px 0 12px;
        }
        .reports-analytics-select {
            appearance: none; border: 0; background: transparent; color: inherit; outline: none; min-height: 40px; width: 100%;
            font-size: 13px; font-weight: 800; cursor: pointer;
        }
        .reports-analytics-select-chev { position: absolute; right: 12px; pointer-events: none; color: #64748b; font-size: 12px; }
        .reports-analytics-seg { display: inline-flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .reports-analytics-seg-item,
        .reports-analytics-more {
            min-height: 42px; border: 1px solid #dbe4ef; border-radius: 10px; background: #fff; color: #334155;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 13px;
            font-size: 13px; font-weight: 800; transition: background .16s ease, border-color .16s ease, color .16s ease, transform .16s ease;
        }
        .reports-analytics-seg-item:hover,
        .reports-analytics-more:hover { transform: translateY(-1px); border-color: #94a3b8; }
        .reports-analytics-seg-item.active,
        .reports-analytics-more.active { background: #0f172a; border-color: #0f172a; color: #fff; }
        .reports-analytics-custom { position: relative; }
        .reports-analytics-date-chev { font-size: 11px; }
        .reports-analytics-popover {
            position: absolute; top: calc(100% + 8px); right: 0; z-index: 20; width: min(300px, calc(100vw - 32px));
            background: #fff; border: 1px solid #dbe4ef; border-radius: 12px; box-shadow: 0 18px 48px rgba(15, 23, 42, .16);
            padding: 12px;
        }
        .reports-analytics-pop-label { color: #475569; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 10px; }
        .reports-analytics-pop-fields { display: grid; gap: 10px; }
        .reports-analytics-pop-field { display: grid; gap: 5px; }
        .reports-analytics-pop-field label { color: #64748b; font-size: 11px; font-weight: 800; }
        .reports-analytics-pop-input {
            min-height: 38px; width: 100%; border: 1px solid #dbe4ef; border-radius: 9px; padding: 7px 9px;
            color: #0f172a; font-size: 13px; outline: none;
        }
        .reports-analytics-pop-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .14); }
        .reports-analytics-pop-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
        .reports-analytics-pop-apply,
        .reports-analytics-pop-reset {
            min-height: 34px; border-radius: 9px; padding: 0 12px; font-size: 12px; font-weight: 900; border: 1px solid transparent;
        }
        .reports-analytics-pop-apply { background: #0f172a; color: #fff; }
        .reports-analytics-pop-reset { background: #f8fafc; border-color: #dbe4ef; color: #475569; }
        .reports-analytics-advanced {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; padding-top: 4px;
        }
        @media (max-width: 1180px) { .reports-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 720px) {
            .reports-page { padding: 12px; }
            .reports-hero { align-items: flex-start; flex-direction: column; }
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
            .reports-actions { justify-content: stretch; }
            .reports-action-chips,
            .reports-action-buttons { width: 100%; margin-left: 0; }
            .reports-btn { width: 100%; }
        }
    </style>

    <header class="reports-hero">
        <div class="reports-hero-main">
            <div class="reports-hero-icon"><i class="bi bi-file-earmark-bar-graph"></i></div>
            <div>
                <h1 class="reports-title">Reports</h1>
                <div class="reports-subtitle">Generate, preview, and export filtered operational reports.</div>
            </div>
        </div>
        <div class="reports-role-badge">
            <i class="bi bi-shield-check"></i>
            <span x-text="userRole === 'owner' ? 'Owner View' : 'Administrator View'"></span>
        </div>
    </header>

    <section class="reports-card">
        <div class="reports-card-head">
            <div>
                <h2 class="reports-card-title">Report Configuration</h2>
                <div class="reports-card-copy">Choose a report type and apply server-side filters before previewing.</div>
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
                    <div class="reports-field reports-field-wide">
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
                    <div class="reports-field reports-field-wide">
                        <label class="reports-label" for="service_type">Service Type</label>
                        <input id="service_type" type="text" x-model="filters.service_type" class="reports-input" placeholder="Burial, cremation, transfer">
                    </div>
                </template>

                <template x-if="shows('encoded_by')">
                    <div class="reports-field reports-field-wide">
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
                    <div class="reports-field reports-field-wide">
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
                <article class="reports-metric">
                    <div class="reports-metric-icon"><i :class="`bi ${card.icon}`"></i></div>
                    <div>
                        <div class="reports-metric-label" x-text="card.label"></div>
                        <div class="reports-metric-value" x-text="card.value"></div>
                    </div>
                </article>
            </template>
        </div>
    </section>

    <section class="reports-card">
        <div class="reports-preview-head">
            <div>
                <h2 class="reports-card-title">Report Preview</h2>
                <div class="reports-card-copy" x-text="reportTypes[reportType] || 'Select a report type'"></div>
                <div class="reports-preview-meta" x-show="hasPreview && filterChips().length" x-cloak>
                    <template x-for="chip in filterChips()" :key="chip">
                        <span class="reports-chip" x-text="chip"></span>
                    </template>
                </div>
            </div>
            <div class="reports-chip" x-show="hasPreview" x-text="`${rows.length} row${rows.length === 1 ? '' : 's'}`"></div>
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

        <template x-if="!loading && rows.length > 0">
            <div class="reports-table-wrap">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <template x-for="column in columns()" :key="column.key">
                                <th :class="isNumericColumn(column.key) ? 'reports-cell-number' : ''" x-text="column.label"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="index">
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
        params() {
            this.enforceAssignedBranch();
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
            const query = new URLSearchParams(this.params()).toString();
            window.open(`${config.printUrl}?${query}`, '_blank', 'noopener');
        },
        openCsv() {
            if (!this.rows.length) return;
            const query = new URLSearchParams(this.params()).toString();
            window.location.href = `${config.csvUrl}?${query}`;
        },
        resetFilters() {
            const reportType = this.filters.report_type || config.defaultReportType;
            Object.keys(this.filters).forEach((key) => this.filters[key] = '');
            this.filters.report_type = reportType;
            this.enforceAssignedBranch();
            this.reportType = reportType;
            this.datePreset = '';
            this.customRangeOpen = false;
            this.advancedFiltersOpen = false;
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
            this.reportType = reportType;
            this.datePreset = '';
            this.customRangeOpen = false;
            this.advancedFiltersOpen = false;
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
                return [{ label: 'Total Records', value: this.number(this.summary.total_records || 0), icon: 'bi-list-check' }];
            }
            if (this.reportType === 'owner_branch_analytics') {
                return [
                    { label: 'Total Cases', value: this.number(this.summary.total_cases || 0), icon: 'bi-folder2-open' },
                    { label: 'Paid Cases', value: this.number(this.summary.paid_cases || 0), icon: 'bi-check-circle' },
                    { label: 'Partial Cases', value: this.number(this.summary.partial_cases || 0), icon: 'bi-hourglass-split' },
                    { label: 'Unpaid Cases', value: this.number(this.summary.unpaid_cases || 0), icon: 'bi-exclamation-circle' },
                    { label: 'Gross Amount', value: money(this.summary.gross_amount), icon: 'bi-cash-stack' },
                    { label: 'Collected Amount', value: money(this.summary.collected_amount), icon: 'bi-wallet2' },
                    { label: 'Remaining Balance', value: money(this.summary.remaining_balance), icon: 'bi-receipt' },
                ];
            }
            return [
                { label: 'Total Records', value: this.number(this.summary.total_records || 0), icon: 'bi-list-check' },
                { label: 'Gross Amount', value: money(this.summary.gross_amount), icon: 'bi-cash-stack' },
                { label: 'Collected Amount', value: money(this.summary.collected_amount), icon: 'bi-wallet2' },
                { label: 'Remaining Balance', value: money(this.summary.remaining_balance), icon: 'bi-receipt' },
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
            return ['total_amount', 'total_paid', 'balance', 'gross_amount', 'collected_amount', 'remaining_balance'];
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
    };
}
</script>
@endsection
