@extends('layouts.panel')

@section('hide_layout_topbar', '1')
@section('page_title', 'Payment Monitoring')
@section('page_desc', '')

@section('content')
@php
    $user = auth()->user();
    $isBranchOnly = ($branches ?? collect())->count() === 1;
    $isBranchAdmin = $user?->isBranchAdmin();
    $isStaff = $user?->isStaff();
    $isMainAdmin = $user?->isMainBranchAdmin();
    $isOwner = $user?->isOwner();
    $activeTab = 'summary';
    $defaultPaymentBranchId = $defaultPaymentBranchId ?? null;
    $monitoringRoute = request()->routeIs('admin.payments.index') || request()->routeIs('admin.payment-monitoring')
        ? 'admin.payments.index'
        : 'payments.history';
    $paymentStatus = $paymentStatus ?? $statusAfterPayment ?? null;
    $dateRange = request('date_preset') ?: ((request()->filled('paid_from') || request()->filled('paid_to')) ? 'custom' : 'all');
    $branchFilterIsActive = !$isStaff
        && request()->has('branch_id')
        && filled(request('branch_id'))
        && (
            $defaultPaymentBranchId
                ? (string) request('branch_id') !== (string) $defaultPaymentBranchId
                : request('branch_id') !== 'all'
        );
    $hasPaymentFilters = filled($q ?? null)
        || filled($paymentStatus)
        || filled($caseStatus ?? null)
        || filled($paymentMethod ?? null)
        || ($dateRange !== 'all' && $dateRange !== 'any')
        || filled($paidFrom ?? null)
        || filled($paidTo ?? null)
        || $branchFilterIsActive;
    $emptyMessage = $isBranchAdmin
        ? 'No payment records found for your assigned branch.'
        : 'No payment records found for the selected filters.';

    $tabQuery = fn (string $tab) => array_filter(array_merge(request()->except(['tab', 'page', 'transactions_page', 'open_case']), ['tab' => $tab]), fn ($v) => $v !== null && $v !== '');
    $statusClass = fn ($status) => match (strtoupper((string) $status)) {
        'PAID' => 'is-paid',
        'PARTIAL' => 'is-partial',
        default => 'is-unpaid',
    };
    $caseRoute = function ($case) use ($isOwner) {
        if (!$case) {
            return '#';
        }

        return $isOwner
            ? route('owner.cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()])
            : route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]);
    };
@endphp

<style>
    .pm-page { color: var(--ink); padding: 12px var(--panel-content-inline, 20px) 20px; }
    .pm-kpis { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:.75rem; margin-bottom:1rem; }
    .pm-kpi {
        background:#D3DEC9; border:1.5px solid var(--border); border-radius:.625rem;
        display:flex; flex-direction:column;
        text-decoration:none; color:var(--ink);
        box-shadow:none;
    }
    .pm-kpi-inner {
        display:flex; align-items:center; gap:.75rem;
        padding:.7rem 1rem; flex:1;
    }
    .pm-kpi-icon {
        width:2rem; height:2rem; border-radius:7px; flex-shrink:0;
        display:inline-flex; align-items:center; justify-content:center;
        font-size:.82rem; background:transparent; color:var(--brand);
    }
    .pm-kpi-body {
        flex:1; min-width:0; display:flex; flex-direction:column; gap:.08rem;
    }
    .pm-kpi-label {
        display:block; font-size:.65rem; text-transform:uppercase; letter-spacing:.06em;
        color:var(--ink-muted); font-weight:650; line-height:1.3; white-space:nowrap;
    }
    .pm-kpi-value {
        display:block; font-size:1.15rem; line-height:1.15;
        font-weight:750; font-variant-numeric:tabular-nums; color:var(--ink);
    }
    .pm-kpi-value.good { color:#6F8A6D; }
    .pm-kpi-value.warn { color:#B87956; }
    .pm-kpi-desc {
        display:block; font-size:.63rem; color:var(--ink-muted); font-weight:500;
        line-height:1.3; margin-top:.1rem; white-space:nowrap;
        overflow:hidden; text-overflow:ellipsis;
    }
    .pm-kpi-action {
        font-size:.62rem; font-weight:700; color:var(--ink-muted);
        text-transform:uppercase; letter-spacing:.07em;
        white-space:nowrap; flex-shrink:0;
        opacity:0; transition:opacity .15s ease;
    }
    .pm-kpi.is-link { cursor:pointer; transition:background .13s ease, border-color .13s ease, color .13s ease; }
    .pm-kpi.is-link:hover { background:#C7D5BE; border-color:#8EA083; box-shadow:none; }
    .pm-kpi.is-link:hover .pm-kpi-action { opacity:1; }

    .pm-toolbar-shell { background:#D3DEC9; border:1px solid var(--border); border-radius:.75rem; padding:.7rem; margin-bottom:1rem; overflow:visible; box-shadow:none; }
    .pm-page.is-loading .pm-kpis,
    .pm-page.is-loading .pm-panel { opacity:.62; pointer-events:none; transition:opacity .16s ease; }
    .pm-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:.55rem; }
    .pm-field { flex:1 1 8rem; min-width:0; }
    .pm-field.branch { flex:1.5 1 11rem; }
    .pm-field.branch-readonly { flex:0 1 18rem; min-width:14rem; max-width:18rem; }
    .pm-field.search { flex:2 1 15rem; min-width:12rem; }
    .pm-field.has-icon { position:relative; }
    .pm-field.has-icon > i { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:var(--ink-muted); pointer-events:none; z-index:1; }
    .pm-field.has-icon .pm-control { padding-left:2.35rem; }
    .pm-field.has-icon select.pm-control { -webkit-appearance:none; appearance:none; padding-right:2.2rem; }
    .pm-sel-chev { position:absolute; right:.72rem; top:50%; transform:translateY(-50%); color:var(--ink-muted); pointer-events:none; font-size:.72rem; z-index:1; }
    .pm-control {
        width:100%; height:2.65rem; border:1px solid var(--border); border-radius:.75rem;
        background:#E1E7D9; color:var(--ink); font-size:.82rem; padding:0 .72rem;
        box-shadow:none; cursor:pointer; transition:background-color .16s ease, border-color .16s ease;
    }
    .pm-control:hover { background:#C7D5BE; border-color:#8EA083; }
    .pm-control:focus { outline:none; box-shadow:none; border-color:var(--accent); background:#FBFCF7; }
    .pm-control:disabled { background:var(--surface-muted); color:var(--ink-muted); opacity:1; }
    .pm-search-clear {
        position:absolute; right:.68rem; top:50%; transform:translateY(-50%);
        width:1.65rem; height:1.65rem; border:0; border-radius:999px;
        display:inline-flex; align-items:center; justify-content:center;
        background:transparent; color:var(--ink-muted); cursor:pointer;
        transition:background-color .16s ease, color .16s ease;
    }
    .pm-search-clear:hover { background:#C7D5BE; color:var(--ink); }
    .pm-field.search .pm-control { padding-right:2.45rem; cursor:text; }
    .pm-search-suggestions {
        position:absolute; left:0; right:0; top:calc(100% + .35rem); z-index:40;
        background:#E1E7D9; border:1px solid var(--border); border-radius:.75rem;
        padding:.35rem; display:grid; gap:.25rem; max-height:16rem; overflow:auto;
    }
    .pm-search-suggestions[hidden] { display:none !important; }
    .pm-search-option {
        width:100%; border:0; border-radius:.55rem; background:transparent;
        color:var(--ink); display:grid; gap:.08rem; text-align:left;
        padding:.55rem .65rem; cursor:pointer;
        transition:background-color .16s ease, color .16s ease;
    }
    .pm-search-option:hover,
    .pm-search-option:focus-visible { outline:none; background:#C7D5BE; }
    .pm-search-title { font-size:.83rem; font-weight:700; }
    .pm-search-meta { font-size:.72rem; color:var(--ink-muted); font-weight:600; }
    .pm-readonly-control {
        display:flex; align-items:center; min-height:2.75rem; height:2.75rem;
        background:var(--surface-muted); color:var(--ink-muted); cursor:default;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .pm-actions { display:flex; gap:.55rem; flex:0 0 auto; align-items:center; margin-left:auto; }
    .pm-btn {
        height:2.65rem; border-radius:.75rem; border:1px solid var(--border); background:#E1E7D9;
        color:var(--ink); padding:0 .78rem; display:inline-flex; align-items:center; gap:.4rem;
        font-weight:700; font-size:.82rem; text-decoration:none; white-space:nowrap;
        cursor:pointer; box-shadow:none; transition:background-color .16s ease, border-color .16s ease, color .16s ease;
    }
    .pm-btn:hover { background:#C7D5BE; border-color:#8EA083; color:var(--ink); }
    .pm-btn.primary { background:var(--accent); border-color:var(--accent); color:#fff; }
    .pm-btn.primary:hover { background:#2F3A2E; border-color:#2F3A2E; color:#fff; }
    .pm-hidden-date-fields { display:none; }
    .pm-modal-backdrop { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(15,23,42,.45); z-index:60; padding:1rem; }
    .pm-modal-backdrop.open { display:flex; }
    .pm-modal { width:min(100%,28rem); background:#D3DEC9; border:1px solid var(--border); border-radius:1rem; box-shadow:none; overflow:hidden; }
    .pm-modal.pm-transactions-modal { width:min(100%,58rem); max-height:min(86vh,48rem); display:flex; flex-direction:column; }
    .pm-modal-hd { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.1rem; border-bottom:1px solid var(--border); }
    .pm-modal-title { font-weight:700; }
    .pm-modal-body { display:grid; gap:.8rem; padding:1rem 1.1rem; }
    .pm-transactions-modal .pm-modal-body { overflow:auto; }
    .pm-modal-ft { display:flex; justify-content:flex-end; gap:.65rem; padding:1rem 1.1rem; border-top:1px solid var(--border); }

    .pm-tabs { display:none; gap:.35rem; border-bottom:1px solid var(--border); margin:1rem 0 .75rem; }
    .pm-tab {
        display:inline-flex; align-items:center; gap:.45rem; padding:.75rem .95rem; color:var(--ink-muted);
        border-bottom:2px solid transparent; text-decoration:none; font-weight:650; font-size:.9rem;
        cursor:pointer; transition:background-color .16s ease, color .16s ease;
    }
    .pm-tab:hover { background:#C7D5BE; color:var(--ink); }
    .pm-tab.active { color:var(--accent); border-color:var(--accent); background:#C7D5BE; }

    .pm-panel { background:#D3DEC9; border:1px solid var(--border); border-radius:.75rem; overflow:hidden; display:flex; flex-direction:column; box-shadow:none; }
    .pm-records-body { flex:1 1 auto; min-height:0; overflow-y:auto; max-height:calc(100vh - 320px); }
    .pm-money { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; font-weight:700; }
    .pm-row-list { display:flex; flex-direction:column; background:#D3DEC9; }
    .pm-case-item { background:#D3DEC9; border-bottom:1px solid var(--border); }
    .pm-case-item:last-child { border-bottom:0; }
    .pm-case-row { display:grid; grid-template-columns:minmax(6rem,.5fr) minmax(0,1.5fr) minmax(12rem,.75fr) minmax(18rem,auto); gap:1rem; align-items:center; width:100%; padding:1rem; border:0; background:transparent; color:inherit; text-align:left; }
    .pm-case-row.is-toggle { cursor:pointer; }
    .pm-case-row.is-toggle:hover { background:#C7D5BE; }
    .pm-case-row.is-toggle:focus-visible { outline:none; background:#C7D5BE; box-shadow:none; }
    .pm-case-row[aria-expanded="true"] { background:#C7D5BE; }
    .pm-case-row[aria-expanded="true"] .pm-chev { transform:rotate(180deg); }
    .pm-row-main { min-width:0; }
    .pm-row-title { font-weight:850; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pm-row-meta { margin-top:.22rem; color:var(--ink-muted); font-size:.82rem; display:flex; gap:.4rem; flex-wrap:wrap; align-items:center; }
    .pm-row-date { color:var(--ink-muted); font-size:.86rem; white-space:nowrap; }
    .pm-row-actions { display:flex; align-items:center; justify-content:flex-end; gap:.55rem; min-width:max-content; flex-wrap:nowrap; }
    .pm-row-link { display:inline-flex; align-items:center; gap:.4rem; color:var(--accent); font-weight:850; font-size:.84rem; text-decoration:none; background:transparent; border:0; padding:0; }
    .pm-light-link { display:inline-flex; align-items:center; justify-content:center; min-height:2.25rem; gap:.4rem; color:var(--ink); font-weight:700; font-size:.82rem; text-decoration:none; background:#E1E7D9; border:1px solid var(--border); border-radius:.65rem; padding:0 .75rem; transition:background-color .16s ease, border-color .16s ease, color .16s ease; }
    .pm-light-link:hover { background:#D3DEC9; border-color:#8EA083; color:var(--ink); }
    button.pm-light-link { cursor:pointer; }
    .pm-icon-toggle { display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; border:1px solid var(--border); border-radius:.6rem; background:#E1E7D9; color:var(--ink-muted); }
    .pm-summary-detail { display:none; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; padding:1rem; background:#C7D5BE; border-top:1px solid var(--border); }
    .pm-summary-detail.open { display:grid; }
    .pm-summary-stat { background:#DCE6D6; border:1px solid var(--border); border-radius:.75rem; padding:.75rem; min-width:0; }
    .pm-summary-stat span { display:block; color:var(--ink-muted); font-size:.68rem; font-weight:650; text-transform:uppercase; letter-spacing:.05em; }
    .pm-summary-stat strong { display:block; margin-top:.25rem; color:var(--ink); font-size:.95rem; font-weight:750; font-variant-numeric:tabular-nums; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .pm-muted { color:var(--ink-muted); }
    .pm-case { font-weight:800; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; }
    .pm-name { font-weight:800; }
    .pm-sub { margin-top:.15rem; font-size:.78rem; color:var(--ink-muted); }
    .pm-status { display:inline-flex; align-items:center; border-radius:999px; padding:.22rem .58rem; font-size:.72rem; font-weight:800; }
    .pm-status.is-paid { background:#dcfce7; color:#166534; }
    .pm-status.is-partial { background:#fef3c7; color:#92400e; }
    .pm-status.is-unpaid { background:#fee2e2; color:#7F3A32; }
    .pm-link { color:var(--accent); font-weight:800; text-decoration:none; white-space:nowrap; }
    .pm-empty { padding:3rem 1rem; text-align:center; color:var(--ink-muted); font-weight:700; }
    .pm-foot {
        flex-shrink:0;
        position:relative;
        z-index:2;
        padding:.7rem .85rem;
        border-top:1px solid var(--border);
        background:#D3DEC9;
        border-bottom-left-radius:.75rem;
        border-bottom-right-radius:.75rem;
        box-shadow:none;
    }
    .pm-foot .table-paginator {
        width:100%;
        margin:0;
        padding:0;
        border:0;
        background:transparent;
        box-shadow:none;
    }
    .pm-foot .table-paginator-meta {
        color:var(--ink-muted);
        font-weight:800;
    }

    .pm-trans-list { background:#D3DEC9; }
    .pm-trans-item { background:#D3DEC9; border-bottom:1px solid var(--border); overflow:hidden; }
    .pm-trans-row { width:100%; display:grid; grid-template-columns:minmax(7rem,.65fr) minmax(0,1.8fr) minmax(12rem,.9fr) auto; align-items:center; gap:1rem; padding:1rem; border:0; background:transparent; color:inherit; text-align:left; cursor:pointer; }
    .pm-trans-main { min-width:0; }
    .pm-trans-title { font-weight:850; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pm-trans-meta { margin-top:.25rem; color:var(--ink-muted); font-size:.82rem; display:flex; gap:.45rem; flex-wrap:wrap; }
    .pm-trans-money { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; min-width:0; }
    .pm-trans-stat span { display:block; color:var(--ink-muted); font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .pm-trans-stat strong { display:block; margin-top:.2rem; font-size:.9rem; font-weight:750; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .pm-trans-side { display:flex; align-items:center; justify-content:flex-end; gap:.75rem; }
    .pm-trans-count { color:var(--ink-muted); font-size:.78rem; font-weight:800; white-space:nowrap; }
    .pm-expand-label { color:var(--accent); font-size:.82rem; font-weight:700; white-space:nowrap; }
    .pm-chev { color:var(--ink-muted); transition:transform .16s ease; }
    .pm-trans-row[aria-expanded="true"] .pm-chev { transform:rotate(180deg); }
    .pm-detail { display:none; background:#C7D5BE; padding:0 0 .75rem; }
    .pm-detail.open { display:block; }
    .pm-case-overview { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; padding:.85rem 1rem; background:#C7D5BE; border-bottom:1px solid var(--border); }
    .pm-detail-actions { display:flex; gap:.5rem; padding:.75rem 1rem 0; flex-wrap:wrap; }

    .pm-txn-list { display:flex; flex-direction:column; gap:.5rem; padding:.75rem 1rem 0; }
    .pm-txn-card { background:#DCE6D6; border:1px solid var(--border); border-radius:.6rem; overflow:hidden; box-shadow:none; }
    .pm-txn-hd { display:flex; justify-content:space-between; align-items:flex-start; padding:.85rem 1rem; gap:1rem; }
    .pm-txn-info { min-width:0; flex:1 1 0; }
    .pm-txn-rec { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-weight:750; font-size:.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:24rem; color:var(--ink); }
    .pm-txn-rec-label { display:block; margin-bottom:.16rem; color:var(--ink-muted); font-size:.65rem; font-family:inherit; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .pm-txn-sub { display:flex; flex-wrap:wrap; gap:.3rem; font-size:.78rem; color:var(--ink-muted); margin-top:.2rem; align-items:center; }
    .pm-dot { color:var(--ink-muted); }
    .pm-txn-right { display:flex; flex-direction:column; align-items:flex-end; gap:.2rem; flex-shrink:0; }
    .pm-txn-amt { font-size:1rem; font-weight:750; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .pm-txn-bal { display:flex; flex-direction:column; align-items:flex-end; margin-top:.15rem; }
    .pm-txn-bal-lbl { font-size:.65rem; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-muted); font-weight:700; }
    .pm-txn-bal-val { font-size:.8rem; font-weight:700; font-variant-numeric:tabular-nums; color:var(--ink-muted); white-space:nowrap; }
    .pm-txn-foot { padding:.1rem 1rem .55rem; display:flex; flex-wrap:wrap; align-items:center; gap:.35rem; font-size:.78rem; color:var(--ink-muted); }
    .pm-txn-foot strong { color:var(--ink); font-weight:700; }
    .pm-txn-tog {
        display:inline-flex; align-items:center; gap:.3rem;
        margin:0 1rem .55rem; padding:.28rem .6rem;
        background:transparent; border:1px solid var(--border); border-radius:.4rem;
        color:var(--ink-muted); font-size:.75rem; font-weight:700; cursor:pointer;
    }
    .pm-txn-tog:hover { background:#C7D5BE; color:var(--ink); }
    .pm-txn-tog[aria-expanded="true"] .pm-chev { transform:rotate(180deg); }
    .pm-txn-det { border-top:1px solid var(--border); padding:.65rem 1rem .85rem; background:#C7D5BE; }
    .pm-txn-det-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(11rem,1fr)); gap:.5rem; }
    .pm-txn-det-cell span { display:block; font-size:.65rem; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-muted); font-weight:700; margin-bottom:.2rem; }
    .pm-txn-det-cell strong { font-size:.8rem; word-break:break-word; }
    .pm-txn-det-full { grid-column:1/-1; }

    html[data-theme='dark'] .pm-control,
    html[data-theme='dark'] .pm-btn { background:#1e334f; color:#e2ecf9; border-color:#3a5069; }

    /* ── Dark mode: KPI cards ─────────────────────────────────────── */
    html[data-theme='dark'] .pm-kpi {
        background: #182334;
        border-color: #3a495f;
        color: var(--ink);
    }
    html[data-theme='dark'] .pm-kpi-icon {
        background: rgba(59,130,246,0.12);
        color: #93c5fd;
    }
    html[data-theme='dark'] .pm-kpi-label { color: #9fb1c8; }
    html[data-theme='dark'] .pm-kpi-value { color: #e5edf6; }
    html[data-theme='dark'] .pm-kpi-value.good { color: #86efac; }
    html[data-theme='dark'] .pm-kpi-value.warn { color: #fcd34d; }
    html[data-theme='dark'] .pm-kpi-desc  { color: #8fa2ba; }
    html[data-theme='dark'] .pm-kpi-action { color: #9fb1c8; }
    html[data-theme='dark'] .pm-kpi.is-link:hover {
        background: #1f2f45;
        border-color: var(--brand);
        box-shadow: none;
    }

    /* ── Dark mode: status pills ──────────────────────────────────── */
    html[data-theme='dark'] .pm-status.is-paid    { background:rgba(34,197,94,0.14);  color:#86efac; }
    html[data-theme='dark'] .pm-status.is-partial { background:rgba(245,158,11,0.14); color:#fcd34d; }
    html[data-theme='dark'] .pm-status.is-unpaid  { background:rgba(239,68,68,0.14);  color:#fca5a5; }

    /* ── Dark mode: row hover & icon toggle ───────────────────────── */
    html[data-theme='dark'] .pm-case-row.is-toggle:hover,
    html[data-theme='dark'] .pm-case-row[aria-expanded="true"],
    html[data-theme='dark'] .pm-trans-row:hover { background: #202d3f; }
    html[data-theme='dark'] .pm-light-link {
        background: #273243;
        border-color: #526177;
        color: #e2ecf9;
    }
    html[data-theme='dark'] .pm-light-link:hover {
        background: #202d3f;
        border-color: #6b7c91;
        color: #e2ecf9;
    }
    html[data-theme='dark'] .pm-icon-toggle {
        background: #273243;
        border-color: #526177;
        color: #9fb1c8;
    }

    /* ── Dark mode: summary / transaction detail areas ────────────── */
    html[data-theme='dark'] .pm-summary-detail,
    html[data-theme='dark'] .pm-case-overview,
    html[data-theme='dark'] .pm-txn-det { background: #1a2738; border-color: #354e69; }
    html[data-theme='dark'] .pm-summary-stat,
    html[data-theme='dark'] .pm-txn-card { background: #1e2e44; border-color: #3a5069; }
    html[data-theme='dark'] .pm-txn-hd  { background: transparent; }
    html[data-theme='dark'] .pm-txn-rec { color: #e2ecf9; }

    @media (max-width: 1100px) {
        .pm-field, .pm-field.search, .pm-field.branch { flex:1 1 11rem; min-width:10rem; }
        .pm-actions { margin-left:0; }
    }
    @media (max-width: 900px) {
        .pm-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .pm-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .pm-records-body { max-height:calc(100vh - 360px); }
    }
    @media (max-width: 640px) {
        .pm-kpis { grid-template-columns:1fr; }
        .pm-toolbar { flex-wrap:wrap; min-width:0; }
        .pm-field, .pm-field.search, .pm-field.branch, .pm-field.branch-readonly, .pm-actions, .pm-actions .pm-btn { width:100%; flex-basis:100%; min-width:0; max-width:none; }
        .pm-records-body { max-height:calc(100vh - 420px); }
        .pm-foot .table-paginator { align-items:flex-start; gap:.7rem; }
        .pm-summary-top { flex-direction:column; }
        .pm-summary-grid { grid-template-columns:1fr; }
        .pm-summary-actions, .pm-summary-actions .pm-btn { width:100%; justify-content:center; }
        .pm-case-row, .pm-trans-row { grid-template-columns:minmax(0,1fr); }
        .pm-summary-detail, .pm-case-overview, .pm-trans-money { grid-template-columns:1fr 1fr; }
        .pm-trans-side, .pm-row-actions { justify-content:flex-start; flex-wrap:wrap; min-width:0; }
        .pm-txn-hd { flex-direction:column; gap:.5rem; }
        .pm-txn-right { align-items:flex-start; }
    }
</style>

<div class="pm-page ops-page payment-monitoring-page">
    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    <div class="flash-info" data-flash-icon="bi-clock-history">You are on the Payment Monitoring page.</div>
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}</div>
    @endif

    <header class="ops-page-header" aria-labelledby="paymentMonitoringTitle">
        <div class="ops-page-header__copy">
            <div class="ops-page-kicker">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                <span>Payment Operations</span>
            </div>
            <h1 id="paymentMonitoringTitle" class="ops-page-title">Payment Monitoring</h1>
            <p class="ops-page-desc">Review payment progress, transaction history, branch scope, and outstanding balances.</p>
        </div>
    </header>

    @if(!$isStaff)
    <div class="pm-kpis ops-stat-grid">
        {{-- Total Cases With Payments — links to Case Payment Summary tab --}}
        <div class="pm-kpi ops-stat-card">
            <div class="pm-kpi-inner ops-stat-card__inner">
                <span class="pm-kpi-icon ops-stat-card__icon"><i class="bi bi-folder-check" aria-hidden="true"></i></span>
                <div class="pm-kpi-body ops-stat-card__body">
                    <span class="pm-kpi-label ops-stat-card__label">Cases with Payments</span>
                    <strong class="pm-kpi-value ops-stat-card__value">{{ number_format($totalCasesWithPayments ?? 0) }}</strong>
                    <span class="pm-kpi-desc ops-stat-card__desc">Cases with at least one payment</span>
                </div>
            </div>
        </div>
        {{-- Total Payment Transactions — links to Transaction History tab --}}
        <div class="pm-kpi ops-stat-card">
            <div class="pm-kpi-inner ops-stat-card__inner">
                <span class="pm-kpi-icon ops-stat-card__icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                <div class="pm-kpi-body ops-stat-card__body">
                    <span class="pm-kpi-label ops-stat-card__label">Payment Transactions</span>
                    <strong class="pm-kpi-value ops-stat-card__value">{{ number_format($paymentRecordsCount ?? 0) }}</strong>
                    <span class="pm-kpi-desc ops-stat-card__desc">All recorded payment entries</span>
                </div>
            </div>
        </div>
        {{-- Total Collected — links to Transaction History tab --}}
        <div class="pm-kpi ops-stat-card">
            <div class="pm-kpi-inner ops-stat-card__inner">
                <span class="pm-kpi-icon ops-stat-card__icon" style="color:#6F8A6D;"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
                <div class="pm-kpi-body ops-stat-card__body">
                    <span class="pm-kpi-label ops-stat-card__label">Total Collected</span>
                    <strong class="pm-kpi-value ops-stat-card__value good">&#8369;{{ number_format((float) ($totalCollected ?? 0), 2) }}</strong>
                    <span class="pm-kpi-desc ops-stat-card__desc">Actual money received</span>
                </div>
            </div>
        </div>
        {{-- Outstanding Balance — non-clickable; no combined UNPAID+PARTIAL filter exists --}}
        <div class="pm-kpi ops-stat-card">
            <div class="pm-kpi-inner ops-stat-card__inner">
                <span class="pm-kpi-icon ops-stat-card__icon" style="color:#B87956;"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span>
                <div class="pm-kpi-body ops-stat-card__body">
                    <span class="pm-kpi-label ops-stat-card__label">Outstanding Balance</span>
                    <strong class="pm-kpi-value ops-stat-card__value warn">&#8369;{{ number_format((float) ($totalOutstanding ?? 0), 2) }}</strong>
                    <span class="pm-kpi-desc ops-stat-card__desc">Remaining unpaid balance</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="pm-toolbar-shell ops-toolbar-shell">
        <form id="pmFilterForm" method="GET" action="{{ route($monitoringRoute) }}" class="pm-toolbar ops-toolbar" data-pm-default-branch="{{ $defaultPaymentBranchId ?? '' }}">
            <input type="hidden" name="tab" value="summary">

            <div class="pm-field search has-icon ops-field ops-field--search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input class="pm-control ops-control" name="q" value="{{ $q ?? '' }}" placeholder="Search client, deceased, case no., payment record, accounting ref, transaction ref..." autocomplete="off" data-pm-search-input>
                <button type="button" class="pm-search-clear" data-pm-search-clear @if(blank($q ?? null)) hidden @endif aria-label="Clear search">
                    <i class="bi bi-x" aria-hidden="true"></i>
                </button>
                <div class="pm-search-suggestions" data-pm-search-suggestions hidden></div>
            </div>

            @if(!$isStaff && ($isMainAdmin || !$isBranchOnly))
                <div class="pm-field branch has-icon ops-field">
                    <i class="bi bi-building" aria-hidden="true"></i>
                    <select name="branch_id" class="pm-control ops-control" title="Branch">
                        <option value="all" @selected(($selectedBranchId ?? null) === 'all')>All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) ($selectedBranchId ?? '') === (string) $branch->id)>
                                {{ $branch->branch_code }} - {{ $branch->branch_name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
                </div>
            @elseif(!$isStaff && $assignedBranch)
                <div class="pm-field branch branch-readonly has-icon ops-field">
                    <i class="bi bi-building" aria-hidden="true"></i>
                    <div
                        class="pm-control ops-control pm-readonly-control"
                        role="status"
                        title="{{ trim(($assignedBranch->branch_code ?? 'Assigned Branch') . ' - ' . ($assignedBranch->branch_name ?? '')) }}"
                        aria-label="Assigned Branch: {{ $assignedBranch->branch_code ?? 'Assigned Branch' }}"
                    >
                        Assigned Branch: {{ $assignedBranch->branch_code ?? 'Assigned Branch' }}{{ $assignedBranch->branch_name ? ' - ' . $assignedBranch->branch_name : '' }}
                    </div>
                </div>
            @endif

            <div class="pm-field has-icon ops-field">
                <i class="bi bi-credit-card" aria-hidden="true"></i>
                <select name="payment_status" class="pm-control ops-control" title="Payment Status">
                    <option value="">All Status</option>
                    <option value="UNPAID" @selected($paymentStatus === 'UNPAID')>Unpaid</option>
                    <option value="PARTIAL" @selected($paymentStatus === 'PARTIAL')>Partial</option>
                    <option value="PAID" @selected($paymentStatus === 'PAID')>Paid</option>
                </select>
                <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
            </div>

            <div class="pm-field has-icon ops-field">
                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                <select name="case_status" class="pm-control ops-control" title="Case Status">
                    <option value="">All Cases</option>
                    <option value="DRAFT" @selected(($caseStatus ?? '') === 'DRAFT')>Draft</option>
                    <option value="ACTIVE" @selected(($caseStatus ?? '') === 'ACTIVE')>Active</option>
                    <option value="COMPLETED" @selected(($caseStatus ?? '') === 'COMPLETED')>Completed</option>
                </select>
                <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
            </div>

            <div class="pm-field has-icon ops-field">
                <i class="bi bi-wallet2" aria-hidden="true"></i>
                <select name="payment_method" class="pm-control ops-control" title="Payment Method">
                    <option value="">All Methods</option>
                    <option value="cash" @selected(($paymentMethod ?? '') === 'cash')>Cash</option>
                    <option value="cashless" @selected(($paymentMethod ?? '') === 'cashless')>Cashless</option>
                </select>
                <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
            </div>

            <div class="pm-field has-icon ops-field">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <select id="pmDateRange" name="date_preset" class="pm-control ops-control" title="Date Range">
                    <option value="all" @selected($dateRange === 'all' || $dateRange === 'any')>All Dates</option>
                    <option value="today" @selected($dateRange === 'today')>Today</option>
                    <option value="week" @selected($dateRange === 'week')>This Week</option>
                    <option value="month" @selected($dateRange === 'month')>This Month</option>
                    <option value="year" @selected($dateRange === 'year')>This Year</option>
                    <option value="custom" @selected($dateRange === 'custom')>Custom</option>
                </select>
                <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
            </div>

            <div class="pm-hidden-date-fields">
                <input id="pmPaidFrom" type="date" name="paid_from" value="{{ $paidFrom ?? '' }}">
                <input id="pmPaidTo" type="date" name="paid_to" value="{{ $paidTo ?? '' }}">
            </div>

            <div class="pm-actions">
                <a href="{{ route($monitoringRoute) }}" class="pm-btn ops-btn-outline" data-pm-clear-filters @if(!$hasPaymentFilters) hidden @endif><i class="bi bi-x-circle" aria-hidden="true"></i><span>Clear</span></a>
            </div>
        </form>
    </div>

    @if($activeTab === 'summary')
        <div class="pm-panel ops-list-panel">
            <div class="pm-records-body ops-list-body">
            <div class="pm-row-list">
                @forelse($paymentCases as $case)
                    @php
                        $casePayments = $case->payments ?? collect();
                        $latestPaymentAt = $case->payments_max_paid_at ? \Illuminate\Support\Carbon::parse($case->payments_max_paid_at) : null;
                        $summaryId = 'summary-case-' . $case->id;
                        $transactionsModalId = 'transactions-modal-case-' . $case->id;
                    @endphp
                    <article
                        class="pm-case-item ops-list-row"
                        data-pm-search-row
                        data-pm-search-title="{{ trim(($case->case_code ?? '') . ' ' . ($case->client?->full_name ?? '') . ' ' . ($case->deceased?->full_name ?? '')) }}"
                        data-pm-search-meta="{{ trim(($case->branch?->branch_code ?? '') . ' ' . ($case->branch?->branch_name ?? '') . ' ' . \Illuminate\Support\Str::headline($case->payment_status ?? 'UNPAID')) }}"
                        data-pm-search-text="{{ trim(($case->case_code ?? '') . ' ' . ($case->client?->full_name ?? '') . ' ' . ($case->deceased?->full_name ?? '') . ' ' . ($case->branch?->branch_code ?? '') . ' ' . ($case->branch?->branch_name ?? '') . ' ' . \Illuminate\Support\Str::headline($case->payment_status ?? 'UNPAID')) }}"
                    >
                    <div class="pm-case-row is-toggle ops-clickable" data-pm-summary-toggle="{{ $summaryId }}" aria-expanded="false" role="button" tabindex="0">
                        <div class="pm-case">{{ $case->case_code ?? '-' }}</div>
                        <div class="pm-row-main">
                            <div class="pm-row-title">{{ $case->client?->full_name ?? '-' }} &ndash; {{ $case->deceased?->full_name ?? '-' }}</div>
                            <div class="pm-row-meta">
                                <span>{{ $case->branch?->branch_code ?? '-' }}{{ $case->branch?->branch_name ? ' · ' . $case->branch->branch_name : '' }}</span>
                            </div>
                        </div>
                        <div class="pm-row-date">Last payment: {{ $latestPaymentAt?->format('M d, Y h:i A') ?? '-' }}</div>
                        <div class="pm-row-actions">
                            <span class="pm-status {{ $statusClass($case->payment_status) }}">{{ \Illuminate\Support\Str::headline($case->payment_status ?? 'UNPAID') }}</span>
                            <button type="button" class="pm-light-link ops-btn-outline" data-pm-stop-row-toggle data-pm-open-transactions-modal="{{ $transactionsModalId }}">
                                <i class="bi bi-list-ul" aria-hidden="true"></i><span>View Transactions</span>
                            </button>
                            <span class="pm-icon-toggle" aria-hidden="true"><i class="bi bi-chevron-down pm-chev" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    <div id="{{ $summaryId }}" class="pm-summary-detail">
                        <div class="pm-summary-stat"><span>Service Amount</span><strong>PHP {{ number_format((float) $case->total_amount, 2) }}</strong></div>
                        <div class="pm-summary-stat"><span>Total Paid</span><strong>PHP {{ number_format((float) $case->total_paid, 2) }}</strong></div>
                        <div class="pm-summary-stat"><span>Remaining Balance</span><strong>PHP {{ number_format((float) $case->balance_amount, 2) }}</strong></div>
                        <div class="pm-summary-stat"><span>Transactions</span><strong>{{ number_format($case->payments_count ?? 0) }}</strong></div>
                    </div>
                    <div id="{{ $transactionsModalId }}" class="pm-modal-backdrop ops-modal-backdrop pm-transactions-backdrop" aria-hidden="true">
                        <div class="pm-modal ops-modal pm-transactions-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $transactionsModalId }}-title">
                            <div class="pm-modal-hd ops-modal__head">
                                <div>
                                    <div class="pm-modal-title" id="{{ $transactionsModalId }}-title">Transactions for {{ $case->case_code ?? 'Case' }}</div>
                                    <div class="pm-sub">{{ $case->client?->full_name ?? '-' }} &ndash; {{ $case->deceased?->full_name ?? '-' }}</div>
                                </div>
                                <button type="button" class="pm-btn compact ops-btn-outline" data-pm-close-transactions-modal aria-label="Close transactions"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                            </div>
                            <div class="pm-modal-body ops-modal__body">
                                <div class="pm-case-overview">
                                    <div class="pm-summary-stat"><span>Service Amount</span><strong>PHP {{ number_format((float) $case->total_amount, 2) }}</strong></div>
                                    <div class="pm-summary-stat"><span>Total Paid</span><strong>PHP {{ number_format((float) $case->total_paid, 2) }}</strong></div>
                                    <div class="pm-summary-stat"><span>Remaining Balance</span><strong>PHP {{ number_format((float) $case->balance_amount, 2) }}</strong></div>
                                    <div class="pm-summary-stat"><span>Transactions</span><strong>{{ number_format($case->payments_count ?? $casePayments->count()) }}</strong></div>
                                </div>

                                <div class="pm-txn-list">
                                    @forelse($casePayments as $payment)
                                        @php
                                            $method = $payment->payment_method ?: $payment->payment_mode ?: 'cash';
                                            $cashlessType = $payment->cashless_type ?: ($method === 'bank_transfer' || $payment->payment_mode === 'bank_transfer' ? 'bank_transfer' : null);
                                            $isCashless = $method === 'cashless' || $cashlessType;
                                            $methodLabel = \App\Support\Payments\PaymentDetails::label($payment);
                                            $paidAt = $payment->paid_at ?? $payment->paid_date;
                                            $hasBalSnap = $payment->balance_after_payment !== null;
                                            $balanceLabel = $hasBalSnap ? 'Balance After Payment' : 'Current Balance';
                                            $balanceValue = $hasBalSnap ? $payment->balance_after_payment : $case->balance_amount;
                                            $txnDetId = 'summary-txnd-' . $payment->id;
                                            $txnRef = $payment->reference_number ?: $payment->transaction_reference_no ?: null;
                                            $refLabel = \App\Support\Payments\PaymentDetails::referenceLabel($payment);
                                            $remarks = $payment->remarks ?: null;
                                            $encodedBy = $payment->encodedBy?->name ?? $payment->recordedBy?->name ?? null;
                                            $senderName = $payment->sender_name ?: null;
                                            $statusAfter = $payment->payment_status_after_payment ?? null;
                                            $recordNo = $payment->display_payment_record_no ?? null;
                                        @endphp
                                        <div class="pm-txn-card">
                                            <div class="pm-txn-hd">
                                                <div class="pm-txn-info">
                                                    <span class="pm-txn-rec-label">Payment Record No.</span>
                                                    <div class="pm-txn-rec">{{ $recordNo ?? 'Not provided' }}</div>
                                                    <div class="pm-txn-sub">
                                                        <span>{{ $methodLabel }}</span>
                                                        @if($refLabel)
                                                            <span class="pm-dot">&middot;</span>
                                                            <span>{{ $refLabel }}</span>
                                                        @endif
                                                        <span class="pm-dot">&middot;</span>
                                                        <span>{{ $paidAt?->format('M d, Y h:i A') ?? 'Not provided' }}</span>
                                                    </div>
                                                </div>
                                                <div class="pm-txn-right">
                                                    <div class="pm-txn-amt">PHP {{ number_format((float) $payment->amount, 2) }}</div>
                                                    @if($statusAfter)
                                                        <span class="pm-status {{ $statusClass($statusAfter) }}">{{ \Illuminate\Support\Str::headline($statusAfter) }}</span>
                                                    @endif
                                                    <div class="pm-txn-bal">
                                                        <span class="pm-txn-bal-lbl">{{ $balanceLabel }}</span>
                                                        <span class="pm-txn-bal-val">PHP {{ number_format((float) $balanceValue, 2) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            @if($encodedBy)
                                                <div class="pm-txn-foot">
                                                    <span>Encoded by <strong>{{ $encodedBy }}</strong></span>
                                                </div>
                                            @endif
                                            <button type="button" class="pm-txn-tog ops-btn-outline" data-pm-txn-det="{{ $txnDetId }}" aria-expanded="false">
                                                <i class="bi bi-info-circle" aria-hidden="true"></i> Details <i class="bi bi-chevron-down pm-chev" aria-hidden="true"></i>
                                            </button>
                                            <div id="{{ $txnDetId }}" class="pm-txn-det" hidden>
                                                <div class="pm-txn-det-grid">
                                                    <div class="pm-txn-det-cell"><span>Payment Record No.</span><strong>{{ $recordNo ?? 'Not provided' }}</strong></div>
                                                    <div class="pm-txn-det-cell"><span>Payment Method</span><strong>{{ $methodLabel }}</strong></div>
                                                    <div class="pm-txn-det-cell"><span>Payment Amount</span><strong>PHP {{ number_format((float) $payment->amount, 2) }}</strong></div>
                                                    <div class="pm-txn-det-cell"><span>Payment Date &amp; Time</span><strong>{{ $paidAt?->format('M d, Y h:i A') ?? 'Not provided' }}</strong></div>
                                                    <div class="pm-txn-det-cell"><span>{{ $balanceLabel }}</span><strong>PHP {{ number_format((float) $balanceValue, 2) }}</strong></div>
                                                    <div class="pm-txn-det-cell"><span>Encoded By</span><strong>{{ $encodedBy ?: 'Not provided' }}</strong></div>
                                                    @if($isCashless)
                                                        <div class="pm-txn-det-cell"><span>Cashless Type</span><strong>{{ $cashlessType ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $cashlessType)) : 'Not provided' }}</strong></div>
                                                        <div class="pm-txn-det-cell"><span>{{ $payment->approval_code ? 'Approval Code' : 'Reference No.' }}</span><strong>{{ $payment->approval_code ?: ($txnRef ?: 'Not provided') }}</strong></div>
                                                    @endif
                                                    @if($senderName)
                                                        <div class="pm-txn-det-cell"><span>Sender / Account Name</span><strong>{{ $senderName }}</strong></div>
                                                    @endif
                                                    @if($remarks)
                                                        <div class="pm-txn-det-cell pm-txn-det-full"><span>Remarks</span><strong>{{ $remarks }}</strong></div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="pm-empty ops-empty">No payment transactions found for this case.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="pm-modal-ft ops-modal__foot">
                                @if($case)
                                    <a class="pm-btn primary ops-btn-primary" href="{{ $caseRoute($case) }}"><i class="bi bi-eye" aria-hidden="true"></i><span>View Case</span></a>
                                @endif
                                <button type="button" class="pm-btn ops-btn-outline" data-pm-close-transactions-modal>Close</button>
                            </div>
                        </div>
                    </div>
                    </article>
                @empty
                    <div class="pm-empty ops-empty">{{ $emptyMessage }}</div>
                @endforelse
            </div>
            </div>{{-- /.pm-records-body --}}
            @if($paymentCases->hasPages())
                <div class="pm-foot">
                    {{ $paymentCases->onEachSide(1)->links('components.pagination.table') }}
                </div>
            @endif
        </div>
    @else
        <div class="pm-panel ops-list-panel">
            <div class="pm-records-body ops-list-body">
            <div class="pm-trans-list">
                @forelse($transactionCases as $case)
                    @php
                        $casePayments = $case->payments ?? collect();
                        $latestPayment = $casePayments->first();
                        $latestPaymentAt = $latestPayment?->paid_at
                            ?? ($case->payments_max_paid_at ? \Illuminate\Support\Carbon::parse($case->payments_max_paid_at) : null);
                        $latestAmount = $latestPayment?->amount;
                        $detailId = 'case-transactions-' . $case->id;
                    @endphp
                    <div class="pm-trans-item ops-list-row">
                        <button type="button" class="pm-trans-row ops-clickable" data-pm-transaction-toggle="{{ $detailId }}" data-case-code="{{ $case->case_code }}" aria-expanded="false">
                            <div class="pm-case">{{ $case->case_code ?? '-' }}</div>
                            <div class="pm-trans-main">
                                <div class="pm-trans-title">{{ $case->client?->full_name ?? '-' }} &ndash; {{ $case->deceased?->full_name ?? '-' }}</div>
                                <div class="pm-trans-meta">
                                    <span>{{ $case->branch?->branch_code ?? '-' }}{{ $case->branch?->branch_name ? ' · ' . $case->branch->branch_name : '' }}</span>
                                </div>
                            </div>
                            <div class="pm-row-date">Last payment: {{ $latestPaymentAt?->format('M d, Y h:i A') ?? '-' }}</div>
                            <div class="pm-trans-side">
                                <span class="pm-status {{ $statusClass($case->payment_status) }}">{{ \Illuminate\Support\Str::headline($case->payment_status ?? 'UNPAID') }}</span>
                                <span class="pm-expand-label">View Full Transactions</span>
                                <i class="bi bi-chevron-down pm-chev" aria-hidden="true"></i>
                            </div>
                        </button>

                        <div id="{{ $detailId }}" class="pm-detail">
                            <div class="pm-case-overview">
                                <div class="pm-summary-stat"><span>Service Amount</span><strong>PHP {{ number_format((float) $case->total_amount, 2) }}</strong></div>
                                <div class="pm-summary-stat"><span>Total Paid</span><strong>PHP {{ number_format((float) $case->total_paid, 2) }}</strong></div>
                                <div class="pm-summary-stat"><span>Remaining Balance</span><strong>PHP {{ number_format((float) $case->balance_amount, 2) }}</strong></div>
                                <div class="pm-summary-stat"><span>Transactions</span><strong>{{ number_format($case->payments_count ?? $casePayments->count()) }}</strong></div>
                            </div>
                            <div class="pm-txn-list">
                                @forelse($casePayments as $payment)
                                    @php
                                        $method = $payment->payment_method ?: $payment->payment_mode ?: 'cash';
                                        $cashlessType = $payment->cashless_type ?: ($method === 'bank_transfer' || $payment->payment_mode === 'bank_transfer' ? 'bank_transfer' : null);
                                        $isCashless = $method === 'cashless' || $cashlessType;
                                        $methodLabel = \App\Support\Payments\PaymentDetails::label($payment);
                                        $channel = $payment->bank_or_channel === 'Other'
                                            ? ($payment->other_bank_or_channel ?: 'Other')
                                            : ($payment->bank_or_channel ?: null);
                                        $paidAt = $payment->paid_at ?? $payment->paid_date;
                                        $hasBalSnap = $payment->balance_after_payment !== null;
                                        $balanceLabel = $hasBalSnap ? 'Balance After Payment' : 'Current Balance';
                                        $balanceValue = $hasBalSnap ? $payment->balance_after_payment : $case->balance_amount;
                                        $txnDetId = 'txnd-' . $payment->id;
                                        $txnRef = $payment->reference_number ?: $payment->transaction_reference_no ?: null;
                                        $refLabel = \App\Support\Payments\PaymentDetails::referenceLabel($payment);
                                        $remarks = $payment->remarks ?: null;
                                        $encodedBy = $payment->encodedBy?->name ?? $payment->recordedBy?->name ?? null;
                                        $senderName = $payment->sender_name ?: null;
                                        $statusAfter = $payment->payment_status_after_payment ?? null;
                                        $recordNo = $payment->display_payment_record_no ?? null;
                                    @endphp
                                    <div class="pm-txn-card">
                                        <div class="pm-txn-hd">
                                            <div class="pm-txn-info">
                                                <span class="pm-txn-rec-label">Payment Record No.</span>
                                                <div class="pm-txn-rec">{{ $recordNo ?? '—' }}</div>
                                                <div class="pm-txn-sub">
                                                    <span>{{ $methodLabel }}</span>
                                                    @if($refLabel)
                                                        <span class="pm-dot">&middot;</span>
                                                        <span>{{ $refLabel }}</span>
                                                    @endif
                                                    <span class="pm-dot">&middot;</span>
                                                    <span>{{ $paidAt?->format('M d, Y h:i A') ?? 'Not provided' }}</span>
                                                </div>
                                            </div>
                                            <div class="pm-txn-right">
                                                <div class="pm-txn-amt">PHP {{ number_format((float) $payment->amount, 2) }}</div>
                                                @if($statusAfter)
                                                    <span class="pm-status {{ $statusClass($statusAfter) }}">{{ \Illuminate\Support\Str::headline($statusAfter) }}</span>
                                                @endif
                                                <div class="pm-txn-bal">
                                                    <span class="pm-txn-bal-lbl">{{ $balanceLabel }}</span>
                                                    <span class="pm-txn-bal-val">PHP {{ number_format((float) $balanceValue, 2) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        @if($encodedBy)
                                            <div class="pm-txn-foot">
                                                <span>Encoded by <strong>{{ $encodedBy }}</strong></span>
                                            </div>
                                        @endif
                                        <button type="button" class="pm-txn-tog ops-btn-outline" data-pm-txn-det="{{ $txnDetId }}" aria-expanded="false">
                                            <i class="bi bi-info-circle" aria-hidden="true"></i> Details <i class="bi bi-chevron-down pm-chev" aria-hidden="true"></i>
                                        </button>
                                        <div id="{{ $txnDetId }}" class="pm-txn-det" hidden>
                                            <div class="pm-txn-det-grid">
                                                <div class="pm-txn-det-cell">
                                                    <span>Payment Record No.</span>
                                                    <strong>{{ $recordNo ?? 'Not provided' }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Payment Method</span>
                                                    <strong>{{ $methodLabel }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Payment Amount</span>
                                                    <strong>PHP {{ number_format((float) $payment->amount, 2) }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Payment Date &amp; Time</span>
                                                    <strong>{{ $paidAt?->format('M d, Y h:i A') ?? 'Not provided' }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>{{ $balanceLabel }}</span>
                                                    <strong>PHP {{ number_format((float) $balanceValue, 2) }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Encoded By</span>
                                                    <strong>{{ $encodedBy ?: 'Not provided' }}</strong>
                                                </div>
                                                @if($isCashless)
                                                    <div class="pm-txn-det-cell">
                                                        <span>Cashless Type</span>
                                                        <strong>{{ $cashlessType ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $cashlessType)) : 'Not provided' }}</strong>
                                                    </div>
                                                    <div class="pm-txn-det-cell">
                                                        <span>{{ $payment->approval_code ? 'Approval Code' : 'Reference No.' }}</span>
                                                        <strong>{{ $payment->approval_code ?: ($txnRef ?: 'Not provided') }}</strong>
                                                    </div>
                                                @endif
                                                @if($senderName)
                                                    <div class="pm-txn-det-cell">
                                                        <span>Sender / Account Name</span>
                                                        <strong>{{ $senderName }}</strong>
                                                    </div>
                                                @endif
                                                @if($remarks)
                                                    <div class="pm-txn-det-cell pm-txn-det-full">
                                                        <span>Remarks</span>
                                                        <strong>{{ $remarks }}</strong>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="pm-empty ops-empty">No payment transactions match the selected filters for this case.</div>
                                @endforelse
                            </div>
                            <div class="pm-detail-actions">
                                @if($case)
                                    <a class="pm-btn primary ops-btn-primary" href="{{ $caseRoute($case) }}"><i class="bi bi-eye" aria-hidden="true"></i><span>View Case</span></a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="pm-empty ops-empty">{{ $emptyMessage }}</div>
                @endforelse
            </div>
            </div>{{-- /.pm-records-body --}}
            @if($transactionCases->hasPages())
                <div class="pm-foot">
                    {{ $transactionCases->onEachSide(1)->links('components.pagination.table') }}
                </div>
            @endif
        </div>
    @endif
</div>

<div id="pmDateModal" class="pm-modal-backdrop ops-modal-backdrop" aria-hidden="true">
    <div class="pm-modal ops-modal" role="dialog" aria-modal="true" aria-labelledby="pmDateModalTitle">
        <div class="pm-modal-hd ops-modal__head">
            <div class="pm-modal-title ops-modal__title" id="pmDateModalTitle">Custom Payment Date Range</div>
            <button type="button" class="pm-btn compact ops-btn-outline" data-pm-date-cancel aria-label="Close date range modal"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <div class="pm-modal-body ops-modal__body">
            <label>
                <span class="pm-txn-rec-label ops-label">Date Range From</span>
                <input id="pmModalPaidFrom" class="pm-control ops-control" type="date" value="{{ $paidFrom ?? '' }}">
            </label>
            <label>
                <span class="pm-txn-rec-label ops-label">Date Range To</span>
                <input id="pmModalPaidTo" class="pm-control ops-control" type="date" value="{{ $paidTo ?? '' }}">
            </label>
        </div>
        <div class="pm-modal-ft ops-modal__foot">
            <button type="button" class="pm-btn ops-btn-outline" data-pm-date-cancel>Cancel</button>
            <button type="button" class="pm-btn primary ops-btn-primary" id="pmApplyCustomDates">Apply Date Range</button>
        </div>
    </div>
</div>

<script>
window.initPaymentMonitoring = () => {
    window.pmMonitoringAbort?.abort();
    window.pmMonitoringAbort = new AbortController();
    const listenerSignal = window.pmMonitoringAbort.signal;
    const form = document.getElementById('pmFilterForm');
    const range = document.getElementById('pmDateRange');
    const paidFrom = document.getElementById('pmPaidFrom');
    const paidTo = document.getElementById('pmPaidTo');
    const modal = document.getElementById('pmDateModal');
    const modalFrom = document.getElementById('pmModalPaidFrom');
    const modalTo = document.getElementById('pmModalPaidTo');
    const applyCustomDates = document.getElementById('pmApplyCustomDates');
    const openCase = new URLSearchParams(window.location.search).get('open_case');
    const searchInput = form?.querySelector('[data-pm-search-input]');
    const searchClear = form?.querySelector('[data-pm-search-clear]');
    const searchSuggestions = form?.querySelector('[data-pm-search-suggestions]');
    const clearFilters = form?.querySelector('[data-pm-clear-filters]');
    let isFetching = false;

    const toDateValue = (date) => date.toISOString().slice(0, 10);
    const submitFilters = () => form?.requestSubmit();
    const hasActiveFilters = () => {
        if (!form) return false;
        return Array.from(new FormData(form).entries()).some(([key, value]) => {
            if (key === 'tab') return false;
            if (key === 'date_preset') return value && value !== 'all' && value !== 'any';
            if (key === 'branch_id') {
                const defaultBranch = form.dataset.pmDefaultBranch || '';
                return defaultBranch ? String(value || '') !== defaultBranch : (value && value !== 'all');
            }
            return String(value || '').trim() !== '';
        });
    };
    const updateClearVisibility = () => {
        if (clearFilters) clearFilters.hidden = !hasActiveFilters();
    };
    const replacePaymentMonitoringContent = (html, url, push = true) => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextPage = doc.querySelector('.pm-page');
        const currentPage = document.querySelector('.pm-page');
        const nextDateModal = doc.getElementById('pmDateModal');
        const currentDateModal = document.getElementById('pmDateModal');

        if (!nextPage || !currentPage) {
            window.location.href = url;
            return;
        }

        nextPage.querySelectorAll('.flash-success, .flash-error, .flash-info, .flash-warning').forEach(el => el.remove());
        currentPage.replaceWith(nextPage);
        if (nextDateModal && currentDateModal) {
            currentDateModal.replaceWith(nextDateModal);
        }
        if (push) window.history.pushState({}, '', url);
        window.initPaymentMonitoring();
    };
    const fetchFilters = async (url, push = true) => {
        if (isFetching) return;
        isFetching = true;
        const page = document.querySelector('.pm-page');
        page?.classList.add('is-loading');

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });

            if (!response.ok) throw new Error('Payment monitoring filter failed.');
            replacePaymentMonitoringContent(await response.text(), url, push);
        } catch (error) {
            window.location.href = url;
        } finally {
            isFetching = false;
            page?.classList.remove('is-loading');
        }
    };
    const submitFiltersAsync = (push = true) => {
        if (!form) return;
        const url = new URL(form.action, window.location.origin);
        new FormData(form).forEach((value, key) => {
            if (value !== null && String(value).trim() !== '') {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
        });
        url.searchParams.delete('page');
        url.searchParams.delete('transactions_page');
        fetchFilters(url.toString(), push);
    };
    const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));
    const closeSearchSuggestions = () => {
        if (!searchSuggestions) return;
        searchSuggestions.hidden = true;
        searchSuggestions.innerHTML = '';
    };
    const commitSearch = (value = searchInput?.value || '') => {
        if (!searchInput) return;
        searchInput.value = value.trim();
        closeSearchSuggestions();
        submitFiltersAsync();
    };
    const renderSearchSuggestions = () => {
        if (!searchInput || !searchClear || !searchSuggestions) return;

        const term = searchInput.value.trim().toLowerCase();
        searchClear.hidden = term.length === 0;

        if (term.length < 2) {
            closeSearchSuggestions();
            return;
        }

        const matches = [];
        const seen = new Set();
        document.querySelectorAll('[data-pm-search-row]').forEach(row => {
            const haystack = (row.dataset.pmSearchText || '').toLowerCase();
            if (!haystack.includes(term)) return;

            const title = row.dataset.pmSearchTitle || 'Payment record';
            if (seen.has(title)) return;
            seen.add(title);
            matches.push({
                title,
                meta: row.dataset.pmSearchMeta || 'Payment monitoring result',
            });
        });

        if (!matches.length) {
            searchSuggestions.innerHTML = '<div class="pm-search-option" role="status"><span class="pm-search-title">No match found</span><span class="pm-search-meta">Press Enter to search all records.</span></div>';
            searchSuggestions.hidden = false;
            return;
        }

        searchSuggestions.innerHTML = matches.slice(0, 6).map(match => `
            <button type="button" class="pm-search-option" data-pm-search-value="${escapeHtml(match.title)}">
                <span class="pm-search-title">${escapeHtml(match.title)}</span>
                <span class="pm-search-meta">${escapeHtml(match.meta)}</span>
            </button>
        `).join('');
        searchSuggestions.hidden = false;
    };

    const setDateRange = (preset) => {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        let from = '';
        let to = '';

        if (preset === 'today') {
            from = to = toDateValue(today);
        } else if (preset === 'week') {
            const day = today.getDay();
            const diff = day === 0 ? -6 : 1 - day;
            const start = new Date(today);
            start.setDate(today.getDate() + diff);
            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            from = toDateValue(start);
            to = toDateValue(end);
        } else if (preset === 'month') {
            from = toDateValue(new Date(today.getFullYear(), today.getMonth(), 1));
            to = toDateValue(new Date(today.getFullYear(), today.getMonth() + 1, 0));
        } else if (preset === 'year') {
            from = toDateValue(new Date(today.getFullYear(), 0, 1));
            to = toDateValue(new Date(today.getFullYear(), 11, 31));
        }

        if (paidFrom) paidFrom.value = from;
        if (paidTo) paidTo.value = to;
    };

    const openDateModal = () => {
        if (modalFrom && paidFrom) modalFrom.value = paidFrom.value;
        if (modalTo && paidTo) modalTo.value = paidTo.value;
        modal?.classList.add('open');
        modal?.setAttribute('aria-hidden', 'false');
        modalFrom?.focus();
    };

    const closeDateModal = () => {
        modal?.classList.remove('open');
        modal?.setAttribute('aria-hidden', 'true');
    };

    form?.addEventListener('submit', event => {
        event.preventDefault();
        closeSearchSuggestions();
        updateClearVisibility();
        submitFiltersAsync();
    }, { signal: listenerSignal });

    clearFilters?.addEventListener('click', event => {
        event.preventDefault();
        fetchFilters(clearFilters.href);
    }, { signal: listenerSignal });

    document.querySelectorAll('.pm-foot a[href]').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            fetchFilters(link.href);
        }, { signal: listenerSignal });
    });

    range?.addEventListener('change', () => {
        if (range.value === 'custom') {
            openDateModal();
            return;
        }

        setDateRange(range.value);
        submitFilters();
    }, { signal: listenerSignal });

    applyCustomDates?.addEventListener('click', () => {
        if (paidFrom && modalFrom) paidFrom.value = modalFrom.value;
        if (paidTo && modalTo) paidTo.value = modalTo.value;
        closeDateModal();
        submitFilters();
    }, { signal: listenerSignal });

    document.querySelectorAll('[data-pm-date-cancel]').forEach(button => {
        button.addEventListener('click', () => {
            closeDateModal();
            if (range && (!paidFrom?.value && !paidTo?.value)) range.value = 'all';
        }, { signal: listenerSignal });
    });

    modal?.addEventListener('click', event => {
        if (event.target === modal) closeDateModal();
    }, { signal: listenerSignal });

    document.querySelectorAll('[data-pm-stop-row-toggle]').forEach(link => {
        link.addEventListener('click', event => event.stopPropagation(), { signal: listenerSignal });
    });

    const setTransactionsModalOpen = (modal, open) => {
        if (!modal) return;
        modal.classList.toggle('open', open);
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.documentElement.classList.toggle('overflow-hidden', open);
        document.body.classList.toggle('overflow-hidden', open);
    };

    document.querySelectorAll('[data-pm-open-transactions-modal]').forEach(button => {
        button.addEventListener('click', event => {
            event.stopPropagation();
            const modal = document.getElementById(button.dataset.pmOpenTransactionsModal);
            setTransactionsModalOpen(modal, true);
        }, { signal: listenerSignal });
    });

    document.querySelectorAll('.pm-transactions-backdrop').forEach(modal => {
        modal.addEventListener('click', event => {
            if (event.target === modal) setTransactionsModalOpen(modal, false);
        }, { signal: listenerSignal });
        modal.querySelectorAll('[data-pm-close-transactions-modal]').forEach(button => {
            button.addEventListener('click', () => setTransactionsModalOpen(modal, false), { signal: listenerSignal });
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.pm-transactions-backdrop.open').forEach(modal => {
            setTransactionsModalOpen(modal, false);
        });
    }, { signal: listenerSignal });

    document.querySelectorAll('[data-pm-summary-toggle]').forEach(button => {
        const toggleSummary = () => {
            const target = document.getElementById(button.dataset.pmSummaryToggle);
            if (!target) return;
            const nextOpen = !target.classList.contains('open');
            target.classList.toggle('open', nextOpen);
            button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
        };

        button.addEventListener('click', toggleSummary, { signal: listenerSignal });
        button.addEventListener('keydown', event => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            toggleSummary();
        }, { signal: listenerSignal });
    });

    document.querySelectorAll('[data-pm-transaction-toggle]').forEach(button => {
        const target = document.getElementById(button.dataset.pmTransactionToggle);
        const toggle = (forceOpen = null) => {
            if (!target) return;
            const nextOpen = forceOpen === null ? !target.classList.contains('open') : forceOpen;
            target.classList.toggle('open', nextOpen);
            button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
        };

        button.addEventListener('click', () => toggle(), { signal: listenerSignal });

        if (openCase && button.dataset.caseCode === openCase) {
            toggle(true);
        }
    });

    document.querySelectorAll('[data-pm-txn-det]').forEach(button => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.pmTxnDet);
            if (!target) return;
            const nextOpen = target.hidden;
            target.hidden = !nextOpen;
            button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
        }, { signal: listenerSignal });
    });

    form?.querySelectorAll('select[name="branch_id"], select[name="payment_status"], select[name="case_status"], select[name="payment_method"]').forEach(control => {
        control.addEventListener('change', () => {
            updateClearVisibility();
            submitFilters();
        }, { signal: listenerSignal });
    });

    searchInput?.addEventListener('input', () => {
        renderSearchSuggestions();
        updateClearVisibility();
    }, { signal: listenerSignal });
    searchInput?.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            commitSearch();
        }

        if (event.key === 'Escape') {
            closeSearchSuggestions();
        }
    }, { signal: listenerSignal });
    searchClear?.addEventListener('click', () => {
        if (!searchInput) return;
        searchInput.value = '';
        searchClear.hidden = true;
        closeSearchSuggestions();
        updateClearVisibility();
        submitFiltersAsync();
    }, { signal: listenerSignal });
    searchSuggestions?.addEventListener('click', event => {
        const option = event.target.closest('[data-pm-search-value]');
        if (!option) return;
        commitSearch(option.dataset.pmSearchValue || '');
    }, { signal: listenerSignal });
    document.addEventListener('click', event => {
        if (!form?.contains(event.target)) {
            closeSearchSuggestions();
        }
    }, { signal: listenerSignal });

    updateClearVisibility();
};
window.addEventListener('popstate', () => {
    fetch(window.location.href, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html',
        },
    })
        .then(response => response.ok ? response.text() : Promise.reject())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextPage = doc.querySelector('.pm-page');
            const currentPage = document.querySelector('.pm-page');
            const nextDateModal = doc.getElementById('pmDateModal');
            const currentDateModal = document.getElementById('pmDateModal');

            if (!nextPage || !currentPage) {
                window.location.reload();
                return;
            }

            nextPage.querySelectorAll('.flash-success, .flash-error, .flash-info, .flash-warning').forEach(el => el.remove());
            currentPage.replaceWith(nextPage);
            if (nextDateModal && currentDateModal) currentDateModal.replaceWith(nextDateModal);
            window.initPaymentMonitoring();
        })
        .catch(() => window.location.reload());
});
window.initPaymentMonitoring();
</script>
@endsection
