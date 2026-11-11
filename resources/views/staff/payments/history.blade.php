@extends('layouts.panel')

@section('hide_layout_topbar', '1')
@section('page_title', 'Payment Monitoring')
@section('page_desc', 'Review payment progress, transaction history, branch scope, and outstanding balances.')
@php
    $user = auth()->user();
    $isBranchAdmin = $user?->isBranchAdmin();
    $isStaff = $user?->isStaff();
    $isMainAdmin = $user?->isMainBranchAdmin();
    $canViewCorrections = $isBranchAdmin || $isStaff || $isMainAdmin;
    $showCorrections = $canViewCorrections && request('tab') === 'corrections';
    $monitoringRoute = request()->routeIs('admin.payments.index') || request()->routeIs('admin.payment-monitoring')
        ? 'admin.payments.index'
        : 'payments.history';
@endphp
@section('topbar_actions')
    @if($canViewCorrections)
        <div data-pm-header-slot @if($showCorrections) hidden @endif>
            @unless($showCorrections)
                <a href="{{ route($monitoringRoute, ['tab' => 'corrections']) }}" class="pm-header-action pm-header-action--corrections" data-pm-section-action>
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                    <span>{{ $isStaff ? 'My Correction Requests' : 'Correction Requests' }}</span>
                    <span class="correction-request-count" data-workflow-pending data-attention="{{ $pendingCorrectionCount > 0 ? 'true' : 'false' }}" aria-label="{{ $pendingCorrectionCount }} pending requests">{{ $pendingCorrectionCount }}</span>
                </a>
            @endunless
        </div>
    @endif
@endsection
@section('record_toolbar_action')
    @if(auth()->user()?->isStaff())
        <button type="button" id="openPaymentForm" class="reference-page-action ops-btn-primary">
            <i class="bi bi-cash-stack" aria-hidden="true"></i>
            <span>Record Payment</span>
        </button>
    @endif
    @if(auth()->user()?->isAdmin() && request('tab') !== 'corrections')
    <a class="reference-page-action ops-btn-outline" data-pm-print-preview href="{{ route('payments.monitoring.print', request()->except(['page', 'transactions_page', 'tab'])) }}" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i><span>Print Preview</span></a>
    @endif
@endsection

@section('content')
@if($canRecordPayment ?? false)
    @include('staff.payments._record_modal')
@endif
@php
    $isBranchOnly = ($branches ?? collect())->count() === 1;
    $isOwner = $user?->isOwner();
    $activeTab = request('tab') === 'transactions' ? 'transactions' : 'summary';
    $defaultPaymentBranchId = $defaultPaymentBranchId ?? null;
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
    $isAllBranchScope = ($selectedBranchId ?? null) === 'all'
        || (($branches ?? collect())->count() > 1 && blank($selectedBranchId ?? null));
    $collectiblesLabel = $isAllBranchScope ? 'Total Collectibles' : 'Branch Collectibles';
    $hasPeriodFilter = filled($paidFrom ?? null)
        || filled($paidTo ?? null)
        || !in_array($dateRange, ['all', 'any'], true);
@endphp

@push('worklist-styles')
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
    .pm-page.is-loading .pm-records-body { cursor:progress; }
    .pm-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:.55rem; }
    .pm-field { flex:1 1 8rem; min-width:0; }
    .pm-field.branch { flex:1.5 1 11rem; }
    .pm-field.branch-readonly { flex:0 1 18rem; min-width:14rem; max-width:18rem; }
    .pm-field.search { flex:2 1 15rem; min-width:12rem; }
    .pm-field.has-icon { position:relative; }
    .pm-field.has-icon > i:first-child { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:var(--ink-muted); pointer-events:none; z-index:1; }
    .pm-field.has-icon .pm-control { padding-left:2.35rem; }
    .pm-field.has-icon select.pm-control { -webkit-appearance:none; appearance:none; padding-right:2.2rem; }
    .pm-sel-chev { position:absolute; left:auto; right:.72rem; top:50%; transform:translateY(-50%); color:var(--ink-muted); pointer-events:none; font-size:.72rem; z-index:1; transition:transform .16s ease,color .16s ease,opacity .16s ease; transform-origin:center; }
    .pm-field.has-icon.is-open > .pm-sel-chev { color:var(--ink); transform:translateY(-50%) rotate(180deg); }
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
    .pm-modal-backdrop { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:var(--modal-scrim); z-index:60; padding:1rem; }
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
    .pm-records-body { flex:1 1 auto; min-height:0; overflow:visible; max-height:none; }
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
    .pm-date-subline { display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; margin-top:.22rem; font-size:.8rem; line-height:1.45; }
    .pm-date-reference { color:var(--ink-muted); overflow-wrap:anywhere; }
    .pm-date-method { color:var(--ink); font-weight:650; }
    .pm-status-stack { display:flex; flex-direction:column; align-items:flex-start; gap:.22rem; min-width:0; }
    .pm-last-payment-amount { color:var(--ink); font-size:.95rem; font-weight:750; font-variant-numeric:tabular-nums; white-space:nowrap; }
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
        margin-top:16px;
        padding:0;
        border:0;
        background:transparent;
        border-bottom-left-radius:.75rem;
        border-bottom-right-radius:.75rem;
        box-shadow:none;
    }
    .pm-page-size { display:flex; align-items:center; gap:10px; font-size:12px; white-space:nowrap; }
    .pm-page-size-control { position:relative; display:inline-flex; }
    .pm-page-size-control select { width:80px !important; padding:0 30px 0 12px !important; appearance:none; }
    .pm-page-size-control > i { position:absolute; right:12px; top:50%; transform:translateY(-50%); pointer-events:none; font-size:12px; }
    .pm-page-size-control:has(select:open) > i { transform:translateY(-50%) rotate(180deg); }
    .pm-more-filters { position:relative; flex:0 0 auto; }
    .pm-more-filters summary { display:flex; align-items:center; gap:10px; height:44px; padding:0 12px; list-style:none; cursor:pointer; border:1px solid var(--filter-ui-border); border-radius:8px; background:var(--filter-ui-surface); color:var(--filter-ui-text); font-size:14px; font-weight:600; }
    .pm-more-filters summary::-webkit-details-marker { display:none; }
    .pm-more-filters[open] summary > i:last-child { transform:rotate(180deg); }
    .pm-more-panel { position:absolute; top:calc(100% + 8px); right:0; width:300px; max-width:calc(100vw - 48px); padding:16px; display:grid; gap:12px; border:1px solid var(--filter-ui-border); border-radius:12px; background:var(--filter-ui-menu); box-shadow:0 8px 24px rgb(8 29 19 / 16%); z-index:45; }
    .pm-more-panel .pm-field { width:100%; min-width:0; }
    .pm-actions [hidden] { display:none !important; }
    @media(max-width:540px) { .pm-more-filters { width:100%; } .pm-more-panel { left:0; right:auto; width:100%; } }
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
    .pm-txn-hd { display:flex; justify-content:space-between; align-items:flex-start; padding:.65rem .85rem; gap:.75rem; }
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
    .pm-txn-tog.inline { margin:.4rem 0 0; }
    .pm-txn-det { border-top:1px solid var(--border); padding:.65rem 1rem .85rem; background:#C7D5BE; }
    .pm-txn-det-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(11rem,1fr)); gap:.5rem; }
    .pm-txn-det-cell span { display:block; font-size:.65rem; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-muted); font-weight:700; margin-bottom:.2rem; }
    .pm-txn-det-cell strong { font-size:.8rem; word-break:break-word; }
    .pm-txn-det-full { grid-column:1/-1; }

    html[data-theme='dark'] .pm-control,
    html[data-theme='dark'] .pm-btn { background:#1e334f; color:#e2ecf9; border-color:#3a5069; }

    /* â”€â”€ Dark mode: KPI cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    /* â”€â”€ Dark mode: status pills â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    html[data-theme='dark'] .pm-status.is-paid    { background:rgba(34,197,94,0.14);  color:#86efac; }
    html[data-theme='dark'] .pm-status.is-partial { background:rgba(245,158,11,0.14); color:#fcd34d; }
    html[data-theme='dark'] .pm-status.is-unpaid  { background:rgba(239,68,68,0.14);  color:#fca5a5; }

    /* â”€â”€ Dark mode: row hover & icon toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    /* â”€â”€ Dark mode: summary / transaction detail areas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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
    }
    @media (max-width: 640px) {
        .pm-kpis { grid-template-columns:1fr; }
        .pm-toolbar { flex-wrap:wrap; min-width:0; }
        .pm-field, .pm-field.search, .pm-field.branch, .pm-field.branch-readonly, .pm-actions, .pm-actions .pm-btn { width:100%; flex-basis:100%; min-width:0; max-width:none; }
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
    @media print {
        .sidebar, .panel-sidebar, .pm-toolbar-shell, .pm-tabs, .pm-btn, .pm-light-link,
        .pm-icon-toggle, .pm-modal-backdrop, [data-topbar-notifications] { display:none !important; }
        .pm-page { padding:0 !important; }
        .pm-panel, .pm-kpi { break-inside:avoid; box-shadow:none !important; }
        .pm-records-body { max-height:none !important; overflow:visible !important; }
        .pm-summary-detail { display:grid !important; }
    }

    @media (min-width:701px) {
        html[data-theme] body.panel-shell-body .panel-page-header:has(.pm-header-action--corrections) {
            grid-template-columns:minmax(0,1fr) minmax(0,760px) minmax(max-content,1fr);
        }
    }
    .panel-page-header__actions:has([data-pm-header-slot][hidden]) { display:none; }
    .pm-header-action {
        display:inline-flex; align-items:center; justify-content:center; gap:8px;
        min-height:44px; padding:8px 12px; border:0; border-radius:8px;
        background:var(--rs-control,var(--surface-muted)); color:var(--rs-text,var(--ink));
        font:600 13px/1.4 var(--font-body); text-decoration:none;
    }
    .pm-header-action:hover { background:var(--rs-hover,var(--surface-muted)); }
    .panel-page-header .pm-header-action--corrections {
        padding-inline:16px;
        background:var(--brand); color:var(--card);
        font-weight:700;
    }
    .panel-page-header .pm-header-action--corrections:hover { background:var(--brand-hover); }
    .panel-page-header .pm-header-action--corrections .correction-request-count[data-attention="true"] { color:#f9d78e; }
    html[data-theme="dark"] .panel-page-header .pm-header-action--corrections .correction-request-count[data-attention="true"] { color:#472300; }
    .pm-header-action:focus-visible { outline:2px solid var(--filter-ui-focus,var(--brand)); outline-offset:2px; }

    html.pm-section-switching :is(.pm-header-action--corrections, [data-pm-correction-card]) { view-transition-name:pm-correction-card; }
    html.pm-section-switching [data-pm-view="records"] { view-transition-name:pm-records; }
    html.pm-section-switching::view-transition-old(root) { display:none; }
    html.pm-section-switching::view-transition-new(root) { animation:none; }
    html.pm-section-switching::view-transition-group(pm-records) { animation-duration:180ms; }
    html.pm-section-switching::view-transition-group(pm-correction-card) {
        z-index:1; animation-duration:340ms; animation-timing-function:cubic-bezier(.2,.65,.25,1);
    }
    html.pm-section-switching::view-transition-image-pair(pm-correction-card) { overflow:hidden; border-radius:12px; }
    html.pm-section-switching::view-transition-old(pm-correction-card) {
        height:100%; mix-blend-mode:normal; animation:pm-section-fade-out 150ms ease both;
    }
    html.pm-section-switching::view-transition-new(pm-correction-card) {
        height:100%; mix-blend-mode:normal; animation:pm-section-fade-in 230ms ease 90ms both;
    }
    @keyframes pm-section-fade-out { to { opacity:0; } }
    @keyframes pm-section-fade-in { from { opacity:0; } }

    .payment-monitoring-page .pc-requests { background:var(--card,var(--surface)); border:1px solid var(--border); border-radius:12px; overflow:hidden; }
    .payment-monitoring-page .pc-requests__header { padding:20px 24px; border-bottom:1px solid var(--border); }
    .payment-monitoring-page .pc-requests__header h2 { margin:0 0 6px; font:600 18px/1.4 var(--font-body); color:var(--ink); }
    .payment-monitoring-page .pc-requests__header--queue { display:flex; align-items:center; gap:12px; padding:12px 16px; }
    .payment-monitoring-page .pc-requests__header--queue h2 { margin:0; }
    .pc-requests__header--queue .pm-header-action { flex:none; }
    .payment-monitoring-page .pc-requests__header p,.payment-monitoring-page .pc-empty p { margin:0; font-size:14px; color:var(--ink-muted); }
    .payment-monitoring-page .pc-empty { padding:48px 24px; text-align:center; }
    .payment-monitoring-page .pc-empty__icon { display:inline-flex; align-items:center; justify-content:center; width:48px; height:48px; background:var(--surface-muted); border-radius:12px; font-size:22px; color:var(--ink-muted); }
    .payment-monitoring-page .pc-empty h3 { margin:16px 0 8px; font:500 16px/1.4 var(--font-body); color:var(--ink); }
    .payment-monitoring-page .pc-list { padding:16px; display:grid; gap:12px; }
    .payment-monitoring-page .pc-request { border:1px solid var(--border); border-radius:10px; overflow:hidden; }
    .payment-monitoring-page .pc-request__summary { display:grid; grid-template-columns:1fr 1.5fr auto; align-items:center; gap:16px; padding:18px; }
    .payment-monitoring-page .pc-request__summary strong { font-weight:500; }
    .payment-monitoring-page .pc-muted { display:block; color:var(--ink-muted); font-size:13px; margin-top:5px; }
    .payment-monitoring-page .pc-status { padding:5px 10px; border-radius:6px; background:var(--surface-muted); font-size:12px; }
    .payment-monitoring-page .pc-status[data-status='PENDING'] { color:#92400e; background:#fef3c7; }
    .payment-monitoring-page .pc-review > summary { display:flex; gap:8px; align-items:center; padding:12px 18px; cursor:pointer; border-top:1px solid var(--border); font-size:14px; }
    .payment-monitoring-page .pc-review > summary .bi-chevron-down { margin-left:auto; transition:transform .15s; }
    .payment-monitoring-page .pc-review[open] > summary .bi-chevron-down { transform:rotate(180deg); }
    .payment-monitoring-page .pc-review__body { padding:20px; border-top:1px solid var(--border); }
    .payment-monitoring-page .pc-review__body h3 { font-size:14px; font-weight:500; margin:0 0 8px; }
    .payment-monitoring-page .pc-reason { white-space:pre-wrap; overflow-wrap:anywhere; margin:0 0 20px; }
    .payment-monitoring-page .pc-meta-label { display:block; font-size:12px; color:var(--ink-muted); margin-bottom:6px; }
    .payment-monitoring-page .pc-comparison { border:1px solid var(--border); border-radius:8px; overflow:hidden; margin:12px 0 20px; }
    .payment-monitoring-page .pc-compare-row { display:grid; grid-template-columns:1fr 1.3fr 1.3fr; background:var(--card); }
    .payment-monitoring-page .pc-compare-row + .pc-compare-row { border-top:1px solid var(--border); }
    .payment-monitoring-page .pc-compare-row > span { padding:14px 16px; font-size:14px; color:var(--ink); overflow-wrap:anywhere; }
    .payment-monitoring-page .pc-compare-head { background:var(--surface-muted); }
    .payment-monitoring-page .pc-compare-head > span { font-size:12px; font-weight:500; color:var(--ink-muted); }
    .payment-monitoring-page .pc-compare-row .pc-changed { border-left:3px solid #64748b; font-weight:500; }
    .payment-monitoring-page .pc-compare-row small { display:block; font-size:12px; color:var(--ink-muted); margin-top:4px; font-weight:400; }
    @media(max-width:600px) {
        .payment-monitoring-page .pc-compare-head { display:none; }
        .payment-monitoring-page .pc-compare-row { grid-template-columns:1fr 1fr; }
        .payment-monitoring-page .pc-compare-row > [role='rowheader'] { grid-column:1/-1; padding-bottom:4px; font-weight:500; }
        .payment-monitoring-page .pc-compare-row > [role='cell']::before { content:attr(data-label); display:block; color:var(--ink-muted); font-size:12px; margin-bottom:6px; }
    }
    .payment-monitoring-page .pc-decisions { display:flex; flex-wrap:wrap; align-items:flex-start; gap:12px; margin-top:20px; padding-top:18px; border-top:1px solid var(--border); }
    .payment-monitoring-page .pc-reject > summary { cursor:pointer; padding:11px 16px; font-size:14px; border:1px solid var(--border); border-radius:8px; min-height:44px; list-style:none; color:var(--ink); }
    .payment-monitoring-page .pc-reject form { display:grid; gap:10px; padding:12px 0; }
    .payment-monitoring-page .pc-reject textarea { width:100%; background:var(--surface); color:var(--ink); border:1px solid var(--border); border-radius:8px; }
    .payment-monitoring-page .pc-pagination { padding:16px; border-top:1px solid var(--border); }
    @media(max-width:600px) { .payment-monitoring-page .pc-request__summary { grid-template-columns:1fr auto; } .payment-monitoring-page .pc-request__summary > div:nth-child(2) { grid-row:2; grid-column:1/-1; } .payment-monitoring-page .pc-requests__header { padding:18px; } }

    .pc-approval-dialog { width:min(480px,calc(100vw - 32px)); max-height:calc(100dvh - 32px); padding:0; margin:auto; border:1px solid var(--border); border-radius:14px; background:var(--card,var(--surface)); color:var(--ink); font:400 14px/1.6 var(--font-body); box-shadow:0 24px 64px rgba(0,0,0,.2); }
    .pc-approval-dialog::backdrop { background:var(--modal-scrim,rgba(15,23,42,.72)); }
    .pc-approval-dialog header { display:flex; align-items:center; justify-content:space-between; padding:18px 20px; border-bottom:1px solid var(--border); gap:16px; }
    .pc-approval-dialog h2 { margin:0; font:600 18px/1.4 var(--font-body); color:var(--ink); }
    .pc-approval-dialog .pc-modal-close { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border:0; border-radius:6px; background:transparent; color:var(--ink); cursor:pointer; }
    .pc-approval-dialog .pc-modal-close:hover { background:var(--surface-muted); }
    .pc-approval-dialog .pc-modal-body { padding:20px; }
    .pc-approval-dialog p { margin:0 0 14px; }
    .pc-approval-dialog p:last-child { margin-bottom:0; color:var(--ink-muted); font-size:13px; }
    .pc-approval-dialog .pc-modal-record { padding:12px; border:1px solid var(--border); border-radius:8px; }
    .pc-approval-dialog .pc-modal-record strong { display:block; font-weight:500; }
    .pc-approval-dialog footer { display:flex; justify-content:flex-end; gap:10px; padding:16px 20px; border-top:1px solid var(--border); background:transparent; }
    .pc-approval-dialog footer button { min-height:44px; padding:10px 16px; border:1px solid var(--border); border-radius:8px; font:500 14px var(--font-body); cursor:pointer; }
    .pc-approval-dialog .pc-modal-cancel { background:transparent; color:var(--ink); }
    .pc-approval-dialog .pc-modal-confirm { background:#374151; color:#fff; border-color:#374151; }
    .pc-approval-dialog button:focus-visible { outline:2px solid #64748b; outline-offset:3px; }
    .pc-approval-dialog button:disabled { opacity:.6; cursor:wait; }
</style>
@include('staff.partials.remaining-pages-visual-system')
@include('partials.case_records_layout_styles')
@include('staff.payments._consistency_styles')
@endpush

<div class="pm-page ops-page payment-monitoring-page pm-consistent-ui" data-pm-view="{{ $showCorrections ? 'corrections' : 'records' }}">
    @if(session('success') && !session('payment_summary_id') && !session('payment_success.id'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if(session('payment_success.id'))
        @include('staff.intake._payment-success', [
            'paymentSuccess' => session('payment_success'),
            'paymentSuccessContext' => 'payment',
        ])
    @elseif(session('payment_summary_id'))
        @include('partials.centered-success-toast', ['toastId' => 'payment-recorded-toast', 'title' => 'Payment recorded successfully', 'message' => 'The payment has been added to the case.'])
    @endif
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}</div>
    @endif

    @if($showCorrections)
        @if($openCorrectionId ?? null)
            <span hidden data-open-correction-url="{{ route('payments.corrections.show', $openCorrectionId) }}"></span>
        @endif
        @include('staff.payments._correction_requests', ['requests' => $correctionRequests])
    @else
    @if(!$isStaff)
    <div class="pm-kpis ops-stat-grid">
        {{-- Cases included by the active payment filter --}}
        <div class="pm-kpi ops-stat-card">
            <div class="pm-kpi-inner ops-stat-card__inner">
                <span class="pm-kpi-icon ops-stat-card__icon"><i class="bi bi-folder-check" aria-hidden="true"></i></span>
                <div class="pm-kpi-body ops-stat-card__body">
                    <span class="pm-kpi-label ops-stat-card__label">{{ $paymentStatus === 'WITH_BALANCE' ? 'Cases With Balance' : 'Cases Shown' }}</span>
                    <strong class="pm-kpi-value ops-stat-card__value">{{ number_format($totalCasesWithPayments ?? 0) }}</strong>
                    <span class="pm-kpi-desc ops-stat-card__desc">{{ $paymentStatus === 'WITH_BALANCE' ? 'Active or completed cases with a remaining balance' : 'Cases matching the selected filters' }}</span>
                </div>
            </div>
        </div>
        {{-- Total Payment Transactions â€” links to Transaction History tab --}}
        <div class="pm-kpi ops-stat-card">
            <div class="pm-kpi-inner ops-stat-card__inner">
                <span class="pm-kpi-icon ops-stat-card__icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                <div class="pm-kpi-body ops-stat-card__body">
                    <span class="pm-kpi-label ops-stat-card__label">Payment Transactions</span>
                    <strong class="pm-kpi-value ops-stat-card__value">{{ number_format($paymentRecordsCount ?? 0) }}</strong>
                    <span class="pm-kpi-desc ops-stat-card__desc">{{ $hasPeriodFilter ? 'Valid entries in the selected period' : 'All valid recorded payment entries' }}</span>
                </div>
            </div>
        </div>
        {{-- Total Collected â€” links to Transaction History tab --}}
        <div class="pm-kpi ops-stat-card">
            <div class="pm-kpi-inner ops-stat-card__inner">
                <span class="pm-kpi-icon ops-stat-card__icon pm-kpi-icon--success"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
                <div class="pm-kpi-body ops-stat-card__body">
                    <span class="pm-kpi-label ops-stat-card__label">Total Collected</span>
                    <strong class="pm-kpi-value ops-stat-card__value good">&#8369;{{ number_format((float) ($totalCollected ?? 0), 2) }}</strong>
                    <span class="pm-kpi-desc ops-stat-card__desc">{{ $hasPeriodFilter ? 'Amount received in the selected period' : 'All valid payments received' }}</span>
                </div>
            </div>
        </div>
        {{-- Outstanding balance for the active filters --}}
        <div class="pm-kpi ops-stat-card">
            <div class="pm-kpi-inner ops-stat-card__inner">
                <span class="pm-kpi-icon ops-stat-card__icon pm-kpi-icon--warning"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span>
                <div class="pm-kpi-body ops-stat-card__body">
                    <span class="pm-kpi-label ops-stat-card__label">{{ $collectiblesLabel }}</span>
                    <strong class="pm-kpi-value ops-stat-card__value warn">&#8369;{{ number_format((float) ($totalOutstanding ?? 0), 2) }}</strong>
                    <span class="pm-kpi-desc ops-stat-card__desc">Current remaining balance of cases shown</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('staff.payments._toolbar')

    @if($activeTab === 'summary')
        <div class="pm-panel ops-list-panel">
            <div class="pm-records-body ops-list-body">
            @include('staff.payments._record_headers', ['showActions' => false])
            <div class="pm-row-list">
                @forelse($paymentCases as $case)
                    @php
                        $casePayments = $case->payments ?? collect();
                        $latestCasePayment = $casePayments->last(fn ($payment) => !$payment->isVoided());
                        $latestPaymentMethodValue = strtolower((string) ($latestCasePayment?->payment_method ?: $latestCasePayment?->payment_mode ?: 'cash'));
                        $latestPaymentIsCashless = $latestPaymentMethodValue === 'cashless' || $latestPaymentMethodValue === 'bank_transfer';
                        $latestPaymentReference = $latestPaymentIsCashless
                            ? ($latestCasePayment?->transaction_reference_no ?: $latestCasePayment?->reference_number)
                            : $latestCasePayment?->receipt_or_no;
                        if ($latestPaymentReference && $latestCasePayment?->payment_record_no
                            && strcasecmp(trim((string) $latestPaymentReference), trim((string) $latestCasePayment->payment_record_no)) === 0) {
                            $latestPaymentReference = null;
                        }
                        $latestPaymentReferenceLabel = $latestPaymentIsCashless ? 'Reference No.' : 'OR No.';
                        $latestPaymentMethodLabel = $latestCasePayment ? \App\Support\Payments\PaymentDetails::label($latestCasePayment) : null;
                        $searchPaymentReferences = $casePayments
                            ->reject(fn ($payment) => $payment->isVoided())
                            ->flatMap(fn ($payment) => [
                                $payment->display_payment_record_no,
                                $payment->receipt_number,
                                $payment->receipt_or_no,
                                $payment->accounting_reference_no,
                                $payment->transaction_reference_no,
                                $payment->reference_number,
                                $payment->approval_code,
                            ])
                            ->filter()
                            ->unique()
                            ->implode(' · ');
                        $latestPaymentRecorder = $latestCasePayment?->recordedBy?->name ?? $latestCasePayment?->encodedBy?->name;
                        $latestPaymentAt = $case->payments_max_paid_at ? \Illuminate\Support\Carbon::parse($case->payments_max_paid_at) : null;
                        $transactionsModalId = 'transactions-modal-case-' . $case->id;
                    @endphp
                    <article
                        class="pm-case-item ops-list-row"
                        data-pm-match-case="{{ $case->case_code ?? '' }}"
                        data-pm-match-client="{{ $case->client?->full_name ?? 'Not provided' }}"
                        data-pm-match-deceased="{{ $case->deceased?->full_name ?? 'Not provided' }}"
                        data-pm-match-payment="{{ $searchPaymentReferences }}"
                    >
                    <div class="pm-case-row pm-history-row" role="button" tabindex="0" aria-label="Open payment history for case {{ $case->case_code }}" aria-controls="{{ $transactionsModalId }}" aria-expanded="false" data-pm-open-transactions-modal="{{ $transactionsModalId }}">
                        <div class="pm-case-identity">
                            <div class="pm-case">{{ $case->case_code ?? '-' }}</div>
                            @if($latestCasePayment)
                                <div class="pm-case-recorder">Recorded by: {{ $latestPaymentRecorder ?: 'Not recorded' }}</div>
                            @endif
                        </div>
                        <div class="pm-row-main">
                            <div class="pm-row-title">{{ $case->client?->full_name ?? '-' }} &ndash; {{ $case->deceased?->full_name ?? '-' }}</div>
                            <div class="pm-row-meta">
                                <span>{{ $case->branch?->branch_code ?? '-' }}{{ $case->branch?->branch_name ? ' Â· ' . $case->branch->branch_name : '' }}</span>
                            </div>
                        </div>
                        <div class="pm-row-date"><span class="pm-date-label">Last payment</span><span class="pm-date-value">{{ $latestPaymentAt?->format('M d, Y h:i A') ?? 'No payment yet' }}</span>
                            @if($latestCasePayment)
                                <span class="pm-date-subline">
                                    @if($latestPaymentMethodLabel)<span class="pm-date-method">{{ $latestPaymentMethodLabel }}</span>@endif
                                    <span class="pm-date-reference">{{ $latestPaymentReferenceLabel }} {{ $latestPaymentReference ?: 'Not recorded' }}</span>
                                </span>
                            @endif
                        </div>
                        <div class="pm-history-view-cell">
                            <span class="pm-status-stack">
                                @if($latestCasePayment)<strong class="pm-last-payment-amount">&#8369;{{ number_format((float) $latestCasePayment->amount, 2) }}</strong>@endif
                                <span class="pm-status {{ $statusClass($case->payment_status) }}">{{ \Illuminate\Support\Str::headline($case->payment_status ?? 'UNPAID') }}</span>
                            </span>
                            <button type="button" class="primary-view-action record-action-control record-row-view" data-pm-open-transactions-modal="{{ $transactionsModalId }}" data-action-tooltip="Open payment history" data-action-tooltip-placement="row-end" aria-label="Open payment history for case {{ $case->case_code }}" aria-controls="{{ $transactionsModalId }}" aria-expanded="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div id="{{ $transactionsModalId }}" class="pm-modal-backdrop ops-modal-backdrop pm-transactions-backdrop" role="dialog" aria-modal="true" aria-labelledby="{{ $transactionsModalId }}-title" aria-hidden="true">
                        <div class="pm-modal ops-modal pm-transactions-modal">
                            <div class="pm-modal-hd ops-modal__head">
                                <div class="pm-drawer-heading">
                                    <div class="pm-drawer-title-row">
                                        <span class="pm-drawer-title-icon" aria-hidden="true"><i class="bi bi-receipt-cutoff"></i></span>
                                        <div>
                                            <div class="pm-modal-title" data-pm-history-title id="{{ $transactionsModalId }}-title">Payment History</div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="pm-btn compact ops-btn-outline pm-drawer-close" data-pm-close-transactions-modal aria-label="Close payment history"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                            </div>
                            <div class="pm-modal-body ops-modal__body">
                                <dl class="pm-ledger-context">
                                    <div><i class="bi bi-folder" aria-hidden="true"></i><div><dt>Case ID</dt><dd>{{ $case->case_code ?? 'Not provided' }}</dd></div></div>
                                    <div><i class="bi bi-building" aria-hidden="true"></i><div><dt>Branch</dt><dd><span class="pm-context-code">{{ $case->branch?->branch_code ?? 'Not provided' }}</span><span>{{ $case->branch?->branch_name ?? 'Not provided' }}</span></dd></div></div>
                                    <div><i class="bi bi-person" aria-hidden="true"></i><div><dt>Client</dt><dd>{{ $case->client?->full_name ?? 'Not provided' }}</dd></div></div>
                                    <div><i class="bi bi-heart" aria-hidden="true"></i><div><dt>Deceased</dt><dd>{{ $case->deceased?->full_name ?? 'Not provided' }}</dd></div></div>
                                </dl>
@include('staff.payments._history_modal_body')
                            </div>
                            <div class="pm-modal-ft ops-modal__foot">
                                <div class="pm-drawer-footer-actions" data-pm-history-footer>
                                    <a class="pm-ledger-print" aria-label="Print Full Payment History" title="Print Full Payment History" href="{{ route('payments.history.print', $case) }}" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i><span>Print Full Payment History</span></a>
                                    @if(auth()->user()?->isStaff())
                                        <button type="button" class="pm-btn" data-pm-correction-picker-toggle aria-expanded="false" aria-controls="{{ $transactionsModalId }}-correction" hidden><i class="bi bi-pencil-square" aria-hidden="true"></i> Request Correction</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <section id="{{ $transactionsModalId }}-payment" class="pw-companion pm-payment-companion" data-pm-payment-pane aria-labelledby="{{ $transactionsModalId }}-payment-title" hidden>
                            <div class="pw-content">
                                <header class="pw-head">
                                    <h2 id="{{ $transactionsModalId }}-payment-title" data-pm-detail-title>Payment Details</h2>
                                    <button type="button" data-pm-history-back aria-label="Close payment details"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                                </header>
                                <div class="pw-body" data-pm-detail-host></div>
                                <footer class="pw-footer" data-pm-detail-footer>
                                    <div data-pm-detail-request></div>
                                    <a class="pw-button pw-primary" data-pm-detail-print target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i> <span>Print Payment Record</span></a>
                                </footer>
                            </div>
                        </section>
                        @if(auth()->user()?->isStaff())
                            <section id="{{ $transactionsModalId }}-correction" class="pw-companion pm-correction-picker" data-pm-correction-picker data-client-name="{{ $case->client?->full_name ?? 'Client not recorded' }}" aria-labelledby="{{ $transactionsModalId }}-correction-title" hidden>
                                <div class="pw-content">
                                    <header class="pw-head"><h2 id="{{ $transactionsModalId }}-correction-title">Request Payment Correction</h2></header>
                                    <div class="pw-body">
                                        <h3 class="pm-correction-search-title">Transaction Selection</h3>
                                        <form role="search" data-pm-correction-search-form>
                                            <label for="{{ $transactionsModalId }}-correction-search">Search Payment Transaction</label>
                                            <div class="pm-field search shared-record-search pm-correction-search">
                                                <i class="bi bi-search shared-record-search-icon" aria-hidden="true"></i>
                                                <input id="{{ $transactionsModalId }}-correction-search" class="shared-record-search-input" type="search"
                                                       data-pm-correction-search maxlength="100" autocomplete="off" placeholder="Receipt / OR No. or transaction reference"
                                                       role="combobox" aria-autocomplete="list" aria-expanded="false"
                                                       aria-controls="{{ $transactionsModalId }}-correction-results">
                                                <button type="button" class="shared-record-search-clear" data-pm-correction-search-clear aria-label="Clear search" hidden><i class="bi bi-x" aria-hidden="true"></i></button>
                                                <button type="submit" class="pm-search-button">Search</button>
                                            </div>
                                        </form>
                                        <p class="pm-correction-search-status" role="status" data-pm-correction-search-status></p>
                                        <ul id="{{ $transactionsModalId }}-correction-results" class="pm-correction-search-results" role="listbox" aria-label="Matching payment transactions" data-pm-correction-results hidden></ul>
                                    </div>
                                    <footer class="pw-footer">
                                        <button type="button" data-pm-correction-cancel>Cancel</button>
                                    </footer>
                                </div>
                            </section>
                        @endif
                    </div>
                    </article>
                @empty
                    <div class="pm-empty ops-empty records-empty-state">{{ $emptyMessage }}</div>
                @endforelse
            </div>
            </div>{{-- /.pm-records-body --}}</div>
<div class="pm-foot">
                    {{ $paymentCases->onEachSide(1)->links('components.pagination.table') }}
                </div>
    @else
        <div class="pm-panel ops-list-panel">
            <div class="pm-records-body ops-list-body">
            @include('staff.payments._record_headers')
            <div class="pm-trans-list">
                @forelse($transactionCases as $case)
                    @php
                        $casePayments = $case->payments ?? collect();
                        $searchPaymentReferences = $casePayments
                            ->reject(fn ($payment) => $payment->isVoided())
                            ->flatMap(fn ($payment) => [
                                $payment->display_payment_record_no,
                                $payment->receipt_number,
                                $payment->receipt_or_no,
                                $payment->accounting_reference_no,
                                $payment->transaction_reference_no,
                                $payment->reference_number,
                                $payment->approval_code,
                            ])
                            ->filter()
                            ->unique()
                            ->implode(' · ');
                        $latestPayment = $casePayments->first();
                        $latestPaymentMethodValue = strtolower((string) ($latestPayment?->payment_method ?: $latestPayment?->payment_mode ?: 'cash'));
                        $latestPaymentIsCashless = $latestPaymentMethodValue === 'cashless' || $latestPaymentMethodValue === 'bank_transfer';
                        $latestPaymentReference = $latestPaymentIsCashless
                            ? ($latestPayment?->transaction_reference_no ?: $latestPayment?->reference_number)
                            : $latestPayment?->receipt_or_no;
                        if ($latestPaymentReference && $latestPayment?->payment_record_no
                            && strcasecmp(trim((string) $latestPaymentReference), trim((string) $latestPayment->payment_record_no)) === 0) {
                            $latestPaymentReference = null;
                        }
                        $latestPaymentReferenceLabel = $latestPaymentIsCashless ? 'Reference No.' : 'OR No.';
                        $latestPaymentMethodLabel = $latestPayment ? \App\Support\Payments\PaymentDetails::label($latestPayment) : null;
                        $latestPaymentAt = $latestPayment?->paid_at
                            ?? ($case->payments_max_paid_at ? \Illuminate\Support\Carbon::parse($case->payments_max_paid_at) : null);
                        $latestAmount = $latestPayment?->amount;
                        $detailId = 'case-transactions-' . $case->id;
                    @endphp
                    <div class="pm-trans-item ops-list-row"
                        data-pm-match-case="{{ $case->case_code ?? '' }}"
                        data-pm-match-client="{{ $case->client?->full_name ?? 'Not provided' }}"
                        data-pm-match-deceased="{{ $case->deceased?->full_name ?? 'Not provided' }}"
                        data-pm-match-payment="{{ $searchPaymentReferences }}"
>
                        <button type="button" class="pm-trans-row ops-clickable" data-pm-transaction-toggle="{{ $detailId }}" data-case-code="{{ $case->case_code }}" aria-expanded="false">
                            <div class="pm-case">{{ $case->case_code ?? '-' }}</div>
                            <div class="pm-trans-main">
                                <div class="pm-trans-title">{{ $case->client?->full_name ?? '-' }} &ndash; {{ $case->deceased?->full_name ?? '-' }}</div>
                                <div class="pm-trans-meta">
                                    <span>{{ $case->branch?->branch_code ?? '-' }}{{ $case->branch?->branch_name ? ' Â· ' . $case->branch->branch_name : '' }}</span>
                                </div>
                            </div>
                            <div class="pm-row-date"><span class="pm-date-label">Last payment</span><span class="pm-date-value">{{ $latestPaymentAt?->format('M d, Y h:i A') ?? 'No payment yet' }}</span>
                                @if($latestPayment)
                                    <span class="pm-date-subline">
                                        @if($latestPaymentMethodLabel)<span class="pm-date-method">{{ $latestPaymentMethodLabel }}</span>@endif
                                        <span class="pm-date-reference">{{ $latestPaymentReferenceLabel }} {{ $latestPaymentReference ?: 'Not recorded' }}</span>
                                    </span>
                                @endif
                            </div>
                            <span class="pm-status-stack">
                                @if($latestPayment)<strong class="pm-last-payment-amount">&#8369;{{ number_format((float) $latestPayment->amount, 2) }}</strong>@endif
                                <span class="pm-status {{ $statusClass($case->payment_status) }}">{{ \Illuminate\Support\Str::headline($case->payment_status ?? 'UNPAID') }}</span>
                            </span>
                            <div class="pm-trans-side">
                                <span class="pm-expand-label">View Full Transactions</span>
                                <i class="bi bi-chevron-down pm-chev" aria-hidden="true"></i>
                            </div>
                        </button>

                        <div id="{{ $detailId }}" class="pm-detail">
                            <div class="pm-case-overview">
                                <div class="pm-summary-stat"><span>Remaining Balance</span><strong>PHP {{ number_format((float) $case->balance_amount, 2) }}</strong></div>
                                <div class="pm-summary-stat"><span>Total Paid</span><strong>PHP {{ number_format((float) $case->total_paid, 2) }}</strong></div>
                                <div class="pm-summary-stat"><span>Total Service Amount</span><strong>PHP {{ number_format((float) $case->total_amount, 2) }}</strong></div>
                                <div class="pm-summary-stat"><span>Payment Count</span><strong>{{ number_format($case->payments_count ?? $casePayments->count()) }}</strong></div>
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
                                        $recordedBy = $payment->recordedBy?->name ?? $payment->encodedBy?->name ?? null;
                                        $senderName = $payment->sender_name ?: null;
                                        $statusAfter = $payment->payment_status_after_payment ?? null;
                                        $recordNo = $payment->display_payment_record_no ?? null;
                                    @endphp
                                    <div class="pm-txn-card">
                                        <div class="pm-txn-hd">
                                            <div class="pm-txn-info">
                                                <span class="pm-txn-rec-label">Payment Record No.</span>
                                                <div class="pm-txn-rec">{{ $recordNo ?? 'â€”' }}</div>
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
                                        @if($recordedBy)
                                            <div class="pm-txn-foot">
                                                <span>Recorded by <strong>{{ $recordedBy }}</strong></span>
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
                                                    <span>Date &amp; Time Received</span>
                                                    <strong>{{ $paidAt?->format('M d, Y h:i A') ?? 'Not provided' }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Recorded On</span>
                                                    <strong>{{ $payment->created_at?->format('M d, Y h:i A') ?? 'Not provided' }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>{{ $balanceLabel }}</span>
                                                    <strong>PHP {{ number_format((float) $balanceValue, 2) }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Recorded By</span>
                                                    <strong>{{ $recordedBy ?: 'Not provided' }}</strong>
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
                                            <div class="pm-detail-actions">
                                                @include('staff.payments._correction_request', ['payment' => $payment])
                                                <a class="pm-case-icon-action pm-print-action" aria-label="Print Payment Record" title="Print Payment Record" href="{{ route('payments.summary', $payment) }}" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i><span>Print Payment Record</span></a>
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
                    <div class="pm-empty ops-empty records-empty-state">{{ $emptyMessage }}</div>
                @endforelse
            </div>
            </div>{{-- /.pm-records-body --}}</div>
<div class="pm-foot">
                    {{ $transactionCases->onEachSide(1)->links('components.pagination.table') }}
                </div>
    @endif
    @endif
</div>

<div id="pmDateModal" class="pm-date-popover" popover="manual" role="group" aria-labelledby="pmDateModalTitle">
    <div class="pm-modal ops-modal">
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
            <button type="button" class="pm-btn primary ops-btn-primary" id="pmApplyCustomDates">Use date range</button>
        </div>
    </div>
</div>

<script>
let paymentSectionTransition;
window.initPaymentMonitoring = () => {
    const approvalDialog = document.getElementById('pcApprovalDialog');
    if (approvalDialog && !approvalDialog.dataset.initialized) {
        approvalDialog.dataset.initialized = 'true';
        let approvalForm = null;
        let approvalTrigger = null;
        let scrollContainer = null;
        let previousOverflow = '';
        document.querySelectorAll('[data-pc-approve-form]').forEach(form => {
            form.addEventListener('submit', event => {
                event.preventDefault();
                approvalForm = form;
                approvalTrigger = event.submitter;
                const voidOnly = form.dataset.correctionType === 'VOID_ONLY';
                approvalDialog.querySelector('#pcApprovalTitle').textContent = voidOnly ? 'Approve payment void?' : 'Approve correction?';
                approvalDialog.querySelector('[data-pc-record]').textContent = form.dataset.paymentRecord || 'Payment';
                approvalDialog.querySelector('#pcApprovalDescription').textContent = voidOnly
                    ? 'The original payment will be voided without a replacement. The case paid amount and remaining balance will be recalculated.'
                    : 'The original payment will be voided and a corrected replacement will be created. The case paid amount and remaining balance will be recalculated.';
                approvalDialog.querySelector('[data-pc-confirm]').textContent = voidOnly ? 'Approve Void' : 'Approve Correction';
                scrollContainer = document.querySelector('.page-content');
                if (scrollContainer) { previousOverflow = scrollContainer.style.overflow; scrollContainer.style.overflow = 'hidden'; }
                approvalDialog.showModal();
            });
        });
        approvalDialog.querySelectorAll('[data-pc-cancel]').forEach(button => button.addEventListener('click', () => approvalDialog.close()));
        approvalDialog.addEventListener('click', event => {
            const rect = approvalDialog.getBoundingClientRect();
            if (event.target === approvalDialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) approvalDialog.close();
        });
        approvalDialog.addEventListener('close', () => {
            if (scrollContainer) scrollContainer.style.overflow = previousOverflow;
            approvalTrigger?.focus();
            approvalForm = null;
        });
        approvalDialog.querySelector('[data-pc-confirm]').addEventListener('click', event => {
            if (!approvalForm) return;
            event.currentTarget.disabled = true;
            event.currentTarget.textContent = 'Approving?';
            approvalDialog.querySelectorAll('[data-pc-cancel]').forEach(button => button.disabled = true);
            HTMLFormElement.prototype.submit.call(approvalForm);
        });
    }

    window.pmMonitoringAbort?.abort();
    window.pmMonitoringAbort = new AbortController();
    const listenerSignal = window.pmMonitoringAbort.signal;
    const form = document.querySelector('#pmFilterForm:not([data-correction-toolbar])');
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
    const clearFilters = form?.querySelector('[data-pm-clear-filters]');
    const pendingFilterKeys = ['branch_id','payment_status','case_status','payment_method','date_preset','paid_from','paid_to'];
    const appliedFilterValues = new Map(form ? new FormData(form).entries() : []);
    let isFetching = false;
    let searchRequest = null;
    let requestNumber = 0;

    const toDateValue = (date) => date.toISOString().slice(0, 10);
    const submitFilters = () => form?.requestSubmit();
    const hasActiveFilters = () => {
        if (!form) return false;
        return Array.from(appliedFilterValues.entries()).some(([key, value]) => {
            if (key === 'q' || key === 'tab' || key === 'case_scope' || key === 'per_page' || key === 'sort') return false;
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
    const replacePaymentMonitoringContent = async (html, url, push, isCurrentRequest) => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextPage = doc.querySelector('.pm-page');
        const currentPage = document.querySelector('.pm-page');
        const nextDateModal = doc.getElementById('pmDateModal');
        const currentDateModal = document.getElementById('pmDateModal');
        const nextHeaderSlot = doc.querySelector('[data-pm-header-slot]');
        const currentHeaderSlot = document.querySelector('[data-pm-header-slot]');

        if (!nextPage || !currentPage) {
            throw new Error('Payment monitoring content is missing.');
        }

        const switchingSections = currentPage.dataset.pmView !== nextPage.dataset.pmView;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const commit = () => {
            if (!isCurrentRequest()) return;
            const restoreFocus = document.activeElement?.matches('[data-pm-section-action]');
            nextPage.querySelectorAll('.flash-success, .flash-error, .flash-info, .flash-warning').forEach(el => el.remove());
            currentPage.replaceWith(nextPage);
            if (nextHeaderSlot && currentHeaderSlot) currentHeaderSlot.replaceWith(nextHeaderSlot);
            if (nextDateModal && currentDateModal) currentDateModal.replaceWith(nextDateModal);
            if (switchingSections) {
                const scroller = nextPage.closest('.page-content');
                if (scroller) scroller.scrollTop = 0;
            }
            if (switchingSections || restoreFocus) {
                document.querySelector('[data-pm-section-action]')?.focus({ preventScroll: true });
            }
            if (push) window.history.pushState({}, '', url);
            document.querySelectorAll('[data-pm-print-preview]').forEach(link => {
                const printUrl = new URL(link.href);
                printUrl.search = new URL(url, window.location.origin).search;
                ['page','transactions_page','tab'].forEach(key => printUrl.searchParams.delete(key));
                link.href = printUrl.toString();
            });
            window.initPaymentMonitoring();
            document.dispatchEvent(new CustomEvent('panel-ui:reset'));
        };

        if (switchingSections && !reduceMotion && typeof document.startViewTransition === 'function') {
            const root = document.documentElement;
            root.classList.add('pm-section-switching');
            const transition = document.startViewTransition(commit);
            paymentSectionTransition = transition;
            const clearTransition = () => {
                if (paymentSectionTransition !== transition) return;
                paymentSectionTransition = null;
                root.classList.remove('pm-section-switching');
            };
            transition.finished.then(clearTransition, clearTransition);
            await transition.updateCallbackDone;
            return;
        }

        commit();
        if (switchingSections && !reduceMotion && nextPage.isConnected) {
            const card = nextPage.querySelector('[data-pm-correction-card]');
            (card || nextPage).animate?.(
                card ? [{ opacity:0, clipPath:'inset(0 0 80% 70% round 12px)' }, { opacity:1, clipPath:'inset(0 round 12px)' }]
                    : [{ opacity:0 }, { opacity:1 }],
                { duration:260, easing:'ease-out' },
            );
        }
    };
    const fetchFilters = async (url, push = true) => {
        searchRequest?.abort();
        searchRequest = new AbortController();
        const requestId = ++requestNumber;
        isFetching = true;
        const page = document.querySelector('.pm-page');
        page?.querySelector('[data-pm-load-error]')?.remove();
        page?.classList.add('is-loading');

        try {
            const response = await fetch(url, {
                signal:searchRequest.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });

            if (!response.ok) throw new Error('Payment monitoring filter failed.');
            const html = await response.text();
            if (requestId !== requestNumber) return;
            await replacePaymentMonitoringContent(html, url, push, () => requestId === requestNumber);
        } catch (error) {
            if (error.name === 'AbortError' || requestId !== requestNumber) return;
            const message = document.createElement('p');
            message.dataset.pmLoadError = '';
            message.className = 'flash-error';
            message.setAttribute('role', 'alert');
            message.textContent = 'Unable to update payment monitoring. Please try again.';
            page?.prepend(message);
        } finally {
            if (requestId !== requestNumber) return;
            isFetching = false;
            page?.classList.remove('is-loading');
        }
    };
    window.loadPaymentMonitoring = fetchFilters;
    const buildFilterUrl = (applyPending = false) => {
        if (!form) return;
        const url = new URL(form.action, window.location.origin);
        const values = new FormData(form);
        if (!applyPending) pendingFilterKeys.forEach(key => {
            if (appliedFilterValues.has(key)) values.set(key,appliedFilterValues.get(key)); else values.delete(key);
        });
        values.forEach((value, key) => {
            if (value !== null && String(value).trim() !== '') {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
        });
        url.searchParams.delete('page');
        url.searchParams.delete('transactions_page');
        return url;
    };
    const submitFiltersAsync = (push = true, applyPending = false) => {
        if (form) fetchFilters(buildFilterUrl(applyPending).toString(),push);
    };
    const matchPanel = form?.querySelector('[data-pm-search-matches]');
    let matchTimer, matchRequest, matchVersion = 0;
    const closeMatches = () => {
        clearTimeout(matchTimer);
        matchRequest?.abort();
        matchVersion++;
        if (matchPanel) { matchPanel.hidden = true; matchPanel.replaceChildren(); }
        searchInput?.setAttribute('aria-expanded','false');
    };
    const readMatches = root => [...root.querySelectorAll('[data-pm-match-case]')].map(row=>({code:row.dataset.pmMatchCase,client:row.dataset.pmMatchClient,deceased:row.dataset.pmMatchDeceased,payment:row.dataset.pmMatchPayment}));
    const underlineMatch = (element, text, query = searchInput?.value.trim().toLowerCase()) => {
        const value=String(text || '');
        if (!query) { element.textContent=value; return; }
        let cursor=0, index;
        while ((index=value.toLowerCase().indexOf(query,cursor))!==-1) {
            element.append(document.createTextNode(value.slice(cursor,index)));
            const mark=document.createElement('mark'); mark.textContent=value.slice(index,index+query.length); element.append(mark);
            cursor=index+query.length;
        }
        element.append(document.createTextNode(value.slice(cursor)));
    };
    const drawMatches = (rows, emptyMessage = 'No matching cases found.') => {
        if (!matchPanel) return;
        matchPanel.replaceChildren();
        const seen = new Set();
        rows.filter(row=>row.code&&!seen.has(row.code)&&seen.add(row.code)).slice(0,5).forEach(row=>{
            const button = document.createElement('button');
            button.type='button'; button.className='pm-search-match'; button.dataset.pmMatchValue=row.code;
            button.setAttribute('aria-label',row.code+', Client: '+row.client+', Deceased: '+row.deceased);
            button.title=row.code+' Â· '+row.client+' Â· '+row.deceased;
            for (const [text,kind] of [[row.code,'pm-match-code'],[row.client,'pm-match-name'],[row.deceased,'pm-match-name'],[row.payment,'pm-match-reference']].filter(([text])=>text)) {
                const line=document.createElement('span'); line.className=kind;
                if(kind==='pm-match-name') { const name=document.createElement('span'); underlineMatch(name,text); line.append(name); } else underlineMatch(line,text);
                button.append(line);
            }
            matchPanel.append(button);
        });
        if (!matchPanel.children.length && emptyMessage) {
            const status = document.createElement('p');
            status.className = 'pm-search-match pm-search-match--status';
            status.setAttribute('role', 'status');
            status.textContent = emptyMessage;
            matchPanel.append(status);
        }
        matchPanel.hidden = false;
        searchInput?.setAttribute('aria-expanded',String(!matchPanel.hidden));
    };
    const suggestMatches = () => {
        closeMatches();
        const query=searchInput?.value.trim();
        if (!query || query.length<2) return;
        const localMatches = readMatches(document.querySelector('.pm-page')).filter(row=>[row.code,row.client,row.deceased,row.payment].some(value=>value?.toLowerCase().includes(query.toLowerCase())));
        if (localMatches.length) drawMatches(localMatches, '');
        else drawMatches([], 'Searching for matching cases…');
        const version=matchVersion;
        matchTimer=setTimeout(async()=>{
            matchRequest=new AbortController();
            try {
                const url=buildFilterUrl(); url.searchParams.set('q',query);
                const response=await fetch(url.toString(),{signal:matchRequest.signal,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                if(!response.ok) throw new Error('Search unavailable');
                const doc=new DOMParser().parseFromString(await response.text(),'text/html');
                if(version!==matchVersion || !doc.querySelector('.pm-page')) return;
                drawMatches(readMatches(doc));
            } catch(error) {
                if (error.name !== 'AbortError' && version === matchVersion && !localMatches.length) {
                    drawMatches([], 'Search unavailable. Press Search to try again.');
                }
            }
        },300);
    };
    const commitSearch = () => {
        if (!searchInput) return;
        closeMatches();
        searchInput.value = searchInput.value.trim();
        submitFiltersAsync();
    };
    matchPanel?.addEventListener('click',event=>{
        const option=event.target.closest('[data-pm-match-value]');
        if(!option) return;
        searchInput.value=option.dataset.pmMatchValue;
        commitSearch();
    },{signal:listenerSignal});
    matchPanel?.addEventListener('keydown',event=>{
        if(event.key==='Escape') { closeMatches(); searchInput?.focus(); return; }
        if(!['ArrowDown','ArrowUp'].includes(event.key)) return;
        event.preventDefault();
        const items=[...matchPanel.querySelectorAll('button')], index=items.indexOf(document.activeElement)+(event.key==='ArrowDown'?1:-1);
        if(index<0) searchInput?.focus(); else items[Math.min(index,items.length-1)]?.focus();
    },{signal:listenerSignal});
    document.addEventListener('click',event=>{if(!form?.querySelector('.pm-field.search')?.contains(event.target)) closeMatches();},{signal:listenerSignal});
    listenerSignal.addEventListener('abort',closeMatches,{once:true});
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
        if (!modal) return;
        modal.showPopover();
        const panel = form?.querySelector('.pm-more-panel')?.getBoundingClientRect();
        const anchor = range?.getBoundingClientRect();
        const width = Math.min(352,innerWidth-32), box = modal.getBoundingClientRect();
        const right = (panel?.right ?? anchor?.right ?? 16)+8;
        const left = right+width<=innerWidth-16 ? right : (panel?.left ?? 16)-width-8>=16 ? panel.left-width-8 : Math.max(16,innerWidth-width-16);
        Object.assign(modal.style,{left:`${left}px`,top:`${Math.max(16,Math.min(anchor?.top ?? 80,innerHeight-box.height-16))}px`,width:`${width}px`});
        modalFrom?.focus();
    };

    const closeDateModal = () => {
        if (modal?.matches(':popover-open')) modal.hidePopover();
    };

    form?.addEventListener('submit', event => {
        event.preventDefault();
        submitFiltersAsync(true,!!event.submitter?.matches('[data-pm-apply-filters]'));
    }, { signal: listenerSignal });

    clearFilters?.addEventListener('click', event => {
        event.preventDefault();
        const url = new URL(clearFilters.href,location.href);
        const query = searchInput?.value.trim();
        if (query) url.searchParams.set('q',query); else url.searchParams.delete('q');
        fetchFilters(url.toString());
    }, { signal: listenerSignal });

    document.querySelectorAll('.pm-foot:not([data-correction-pagination]) a[href]').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            fetchFilters(link.href);
        }, { signal: listenerSignal });
    });
    document.querySelectorAll('[data-pm-section-action]').forEach(link => {
        link.addEventListener('click', event => {
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            if (link.href !== window.location.href) fetchFilters(link.href);
        }, { signal: listenerSignal });
    });

    range?.addEventListener('change', () => {
        if (range.value === 'custom') {
            openDateModal();
            return;
        }

        setDateRange(range.value);
    }, { signal: listenerSignal });

    applyCustomDates?.addEventListener('click', () => {
        if (paidFrom && modalFrom) paidFrom.value = modalFrom.value;
        if (paidTo && modalTo) paidTo.value = modalTo.value;
        closeDateModal();
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

    // Native top-layer tooltips escape every records scroller and rounded clip.
    document.querySelectorAll('.pm-case-icon-action .pm-action-tooltip, .pm-modal-icon .pm-action-tooltip').forEach(tooltip => {
        const trigger = tooltip.parentElement;
        let suppressed = false;
        tooltip.setAttribute('popover', 'manual');
        tooltip.style.setProperty('display','none','important');
        const hide = () => {
            if (tooltip.matches(':popover-open')) tooltip.hidePopover();
            tooltip.style.setProperty('display','none','important');
        };
        const show = () => {
            tooltip.style.setProperty('display','block','important');
            tooltip.showPopover();
            const rect = trigger.getBoundingClientRect();
            const box = tooltip.getBoundingClientRect();
            const left = Math.max(8,Math.min(rect.left+(rect.width-box.width)/2,innerWidth-box.width-8));
            const top = rect.top-box.height-6 >= 8 ? rect.top-box.height-6 : rect.bottom+6;
            const styles = {position:'fixed',inset:'auto',margin:'0',left:`${left}px`,top:`${top}px`,right:'auto',bottom:'auto',transform:'none',visibility:'visible',opacity:'1',border:'0'};
            Object.entries(styles).forEach(([key,value])=>tooltip.style.setProperty(key,value,'important'));
        };
        document.addEventListener('pm-dismiss-tooltips',hide,{signal:listenerSignal});
        trigger.addEventListener('pointerenter',()=>{ suppressed=false; document.dispatchEvent(new Event('pm-dismiss-tooltips')); show(); },{signal:listenerSignal});
        trigger.addEventListener('pointerleave',hide,{signal:listenerSignal});
        trigger.addEventListener('focus',()=>{ if(!suppressed && trigger.matches(trigger.matches('[data-pm-history-detail]') ? ':hover' : ':focus-visible')) show(); },{signal:listenerSignal});
        trigger.addEventListener('blur',hide,{signal:listenerSignal});
        const dismiss = () => { suppressed=true; document.dispatchEvent(new Event('pm-dismiss-tooltips')); };
        trigger.addEventListener('pointerdown',dismiss,{signal:listenerSignal});
        trigger.addEventListener('click',dismiss,{signal:listenerSignal});
        document.addEventListener('keydown',event=>{if(event.key==='Tab') suppressed=false;},{signal:listenerSignal});
        window.addEventListener('resize',hide,{signal:listenerSignal});
        document.addEventListener('scroll',hide,{capture:true,signal:listenerSignal});
        document.addEventListener('keydown',event=>{if(event.key==='Escape') hide();},{signal:listenerSignal});
        listenerSignal.addEventListener('abort',hide,{once:true});
    });
    let activeTransactionsTrigger = null;
    const moreFilters = form?.querySelector('.pm-more-filters');
    moreFilters?.addEventListener('toggle', () => {
        moreFilters.querySelector('summary')?.setAttribute('aria-expanded',String(moreFilters.open));
        if (moreFilters.open) return;
        moreFilters.querySelectorAll('.filter-select-menu').forEach(menu => {
            if (menu.matches(':popover-open')) menu.hidePopover();
            menu.hidden = true;
            const wrap = menu.closest('[data-filter-select-wrap]');
            wrap?.classList.remove('is-open');
            wrap?.querySelector('select')?.setAttribute('aria-expanded','false');
        });
    }, {signal:listenerSignal});
    form?.querySelector('[data-pm-filter-close]')?.addEventListener('click', () => { moreFilters.open=false; moreFilters.querySelector('summary').focus(); }, {signal:listenerSignal});
    form?.querySelectorAll('[data-pm-remove-filter]').forEach(button => button.addEventListener('click', () => {
        const url = new URL(location.href);
        const key = button.dataset.pmRemoveFilter;
        (key === 'date_preset' ? ['date_preset','paid_from','paid_to'] : [key]).forEach(name => url.searchParams.delete(name));
        url.searchParams.delete('page'); url.searchParams.delete('transactions_page');
        fetchFilters(url.href);
    }, {signal:listenerSignal}));
    document.addEventListener('click', event => {
        if (moreFilters?.open && !moreFilters.contains(event.target) && !modal?.contains(event.target) && !event.target.closest('.filter-select-menu')) moreFilters.open = false;
        if (modal?.matches(':popover-open') && !modal.contains(event.target) && !moreFilters?.contains(event.target) && !event.target.closest('.filter-select-menu')) closeDateModal();
    }, { signal: listenerSignal });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal?.matches(':popover-open')) { closeDateModal(); range?.focus(); return; }
        if (event.key === 'Escape' && moreFilters?.open) { moreFilters.open = false; moreFilters.querySelector('summary')?.focus(); }
    }, { signal: listenerSignal });
    const transactionsModalAnchors = new WeakMap();
    const transactionsModalCloseTimers = new WeakMap();

    const getTransactionsModalFocusable = modal => Array.from(modal?.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    ) || []).filter(element => !element.hidden && element.getClientRects().length > 0);

    const setHistoryRowActive = (trigger, active) => {
        if (!(trigger instanceof HTMLElement)) return;
        const row = trigger.closest('.pm-history-row');
        if (!row) return;
        row.classList.toggle('is-active', active);
        row.setAttribute('aria-expanded', String(active));
        row.querySelectorAll('[data-pm-open-transactions-modal]').forEach(button => button.setAttribute('aria-expanded', String(active)));
    };

    const setTransactionsModalOpen = (modal, open, trigger = null) => {
        if (!modal) return;
        const existingTimer = transactionsModalCloseTimers.get(modal);
        if (existingTimer) {
            window.clearTimeout(existingTimer);
            transactionsModalCloseTimers.delete(modal);
        }
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const finishClose = () => {
            modal.classList.remove('is-closing', 'is-opening');
            const anchor = transactionsModalAnchors.get(modal);
            if (anchor) {
                anchor.replaceWith(modal);
                transactionsModalAnchors.delete(modal);
            }
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden', 'pm-transactions-modal-open');
            if (activeTransactionsTrigger instanceof HTMLElement) activeTransactionsTrigger.focus();
            activeTransactionsTrigger = null;
        };
        if (open) {
            modal.dispatchEvent(new Event('pm-history-reset'));
            if (activeTransactionsTrigger !== trigger) setHistoryRowActive(activeTransactionsTrigger, false);
            activeTransactionsTrigger = trigger || document.activeElement;
            setHistoryRowActive(activeTransactionsTrigger, true);
            // Escape the transformed page container so the scrim covers the viewport.
            if (modal.parentElement !== document.body) {
                const anchor = document.createComment('transactions-modal-position');
                modal.before(anchor);
                transactionsModalAnchors.set(modal, anchor);
                document.body.appendChild(modal);
            }
            modal.classList.remove('is-closing', 'open');
            modal.classList.add('is-opening');
            modal.setAttribute('aria-hidden', 'false');
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden', 'pm-transactions-modal-open');
            document.body.removeAttribute('data-sidebar-hovered');
            const modalBody = modal.querySelector('.pm-modal-body');
            if (modalBody) modalBody.scrollTop = 0;
            modal.querySelectorAll('[data-pm-txn-det]').forEach(button => {
                const detail = document.getElementById(button.dataset.pmTxnDet);
                if (detail) detail.hidden = true;
                button.setAttribute('aria-expanded', 'false');
            });
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    modal.classList.add('open');
                    modal.classList.remove('is-opening');
                    if (modalBody) modalBody.scrollTop = 0;
                    getTransactionsModalFocusable(modal)[0]?.focus();
                });
            });
        } else {
            modal.dispatchEvent(new Event('pm-history-reset'));
            setHistoryRowActive(activeTransactionsTrigger, false);
            modal.setAttribute('aria-hidden', 'true');
            modal.classList.remove('is-opening', 'open');
            modal.classList.add('is-closing');
            if (reducedMotion) {
                finishClose();
            } else {
                const timer = window.setTimeout(() => {
                    transactionsModalCloseTimers.delete(modal);
                    finishClose();
                }, 280);
                transactionsModalCloseTimers.set(modal, timer);
            }
        }
    };

    const closeTopPaymentPanel = modal => {
        if (modal.classList.contains('pw-with-request')) {
            modal.dispatchEvent(new CustomEvent('pm-close-correction-panel', {bubbles:true, detail:{}}));
            return;
        }
        const closeChild = modal.querySelector('[data-pm-correction-picker]:not([hidden]) [data-pm-correction-cancel], [data-pm-payment-pane]:not([hidden]) [data-pm-history-back]');
        if (closeChild) closeChild.click();
        else setTransactionsModalOpen(modal, false);
    };

    document.querySelectorAll('[data-pm-open-transactions-modal]').forEach(button => {
        button.addEventListener('click', event => {
            event.stopPropagation();
            const modal = document.getElementById(button.dataset.pmOpenTransactionsModal);
            setTransactionsModalOpen(modal, true, button);
        }, { signal: listenerSignal });
        button.addEventListener('keydown', event => {
            if (event.target !== button || (event.key !== 'Enter' && event.key !== ' ')) return;
            event.preventDefault();
            const modal = document.getElementById(button.dataset.pmOpenTransactionsModal);
            setTransactionsModalOpen(modal, true, button);
        }, { signal: listenerSignal });
    });

    document.querySelectorAll('.pm-transactions-backdrop').forEach(modal => {
        const historyPane = modal.querySelector('[data-pm-history-pane]');
        const detailPane = modal.querySelector('[data-pm-payment-pane]');
        const host = modal.querySelector('[data-pm-detail-host]');
        const requestSlot = detailPane.querySelector('[data-pm-detail-request]');
        const detailPaneTitle = detailPane.querySelector('[data-pm-detail-title]');
        const detailPaneFooter = detailPane.querySelector('[data-pm-detail-footer]');
        const detailPaneClose = detailPane.querySelector('[data-pm-history-back]');
        const picker = modal.querySelector('[data-pm-correction-picker]');
        const pickerToggle = modal.querySelector('[data-pm-correction-picker-toggle]');
        const transactionSearch = picker?.querySelector('[data-pm-correction-search]');
        let pickerSearchTimer;
        const showPicker = (open) => {
            if (!picker) return;
            if (!open) {
                clearTimeout(pickerSearchTimer);
                picker.querySelector('[data-pm-correction-results]').hidden = true;
                transactionSearch?.setAttribute('aria-expanded', 'false');
                transactionSearch?.removeAttribute('aria-activedescendant');
            }
            picker.hidden = !open;
            modal.classList.toggle('pw-with-picker', open);
            pickerToggle.setAttribute('aria-expanded', String(open));
            if (open) transactionSearch.focus();
        };
        if (picker) {
            const results = picker.querySelector('[data-pm-correction-results]');
            const status = picker.querySelector('[data-pm-correction-search-status]');
            const clear = picker.querySelector('[data-pm-correction-search-clear]');
            const normalize = text => text.trim().toLowerCase();
            const compactReference = text => normalize(text).replace(/[\s/.-]+/g, '');
            let activeIndex = -1;
            const candidates = [...historyPane.querySelectorAll('[data-correction-references]')].map(row => {
                const viewDetails = row.querySelector('[data-pm-history-detail]');
                const dateCell = row.querySelector('[data-label="Payment date"]');
                const date = [...dateCell.children].map(part => part.textContent.trim()).join(' ') || dateCell.textContent.trim();
                const amount = row.querySelector('[data-label="Amount"]').textContent.trim();
                const references = JSON.parse(row.dataset.correctionReferences).map(String);
                return {row, viewDetails, record:viewDetails.dataset.paymentRecord, date, amount, references};
            });
            const renderResults = () => {
                clearTimeout(pickerSearchTimer);
                activeIndex = -1;
                transactionSearch.removeAttribute('aria-activedescendant');
                const query = normalize(transactionSearch.value);
                const compactQuery = compactReference(query);
                const matches = ref => normalize(ref).includes(query) || (compactQuery && compactReference(ref).includes(compactQuery));
                const isExact = item => item.references.some(ref => compactReference(ref) === compactQuery);
                const matching = candidates.filter(item => !query || item.references.some(matches));
                matching.sort((a, b) => Number(isExact(b)) - Number(isExact(a)));
                const visible = query ? matching.slice(0, 5) : matching;
                clear.hidden = !transactionSearch.value;
                results.replaceChildren(...visible.map(item => {
                    const row = document.createElement('li');
                    const requestLink = item.row.querySelector('[data-payment-correction-create]');
                    const option = document.createElement(requestLink ? 'a' : 'button');
                    option.id = `${results.id}-${results.children.length}`;
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');
                    option.tabIndex = -1;
                    option.setAttribute('data-pm-correction-result', '');
                    if (requestLink) {
                        option.href = requestLink.href;
                        option.setAttribute('data-payment-workflow', '');
                        option.setAttribute('data-payment-correction-create', '');
                    } else {
                        option.type = 'button';
                        option.addEventListener('click', () => {
                            showPicker(false);
                            item.viewDetails.click();
                        });
                    }
                    const reference = item.references.find(ref => compactReference(ref) === compactQuery)
                        ?? item.references.find(matches);
                    const title = document.createElement('strong');
                    underlineMatch(title, [...new Set([reference, item.record].filter(Boolean))].join(' · ') || 'Payment without reference', query);
                    const client = document.createElement('span');
                    client.textContent = picker.dataset.clientName;
                    const detail = document.createElement('small');
                    detail.textContent = [item.amount, item.date, item.row.dataset.correctionStatus].join(' · ');
                    option.append(title, client, detail);
                    if (!requestLink) {
                        const hint = document.createElement('small');
                        hint.textContent = 'View Payment Details';
                        option.append(hint);
                    }
                    row.append(option);
                    return row;
                }));
                results.hidden = !visible.length;
                transactionSearch.setAttribute('aria-expanded', String(visible.length > 0));
                status.textContent = query
                    ? (matching.length ? `Showing ${visible.length} of ${matching.length} matches` : 'No matching payments for this case.')
                    : (visible.length ? 'Payments for this case' : 'No payment transactions available for correction.');
            };
            const queueSearch = delay => {
                clearTimeout(pickerSearchTimer);
                results.hidden = true;
                results.replaceChildren();
                status.textContent = '';
                transactionSearch.setAttribute('aria-expanded', 'false');
                transactionSearch.removeAttribute('aria-activedescendant');
                activeIndex = -1;
                const term = transactionSearch.value.trim();
                if ([...term].length < 2) {
                    if (delay === 0) status.textContent = 'Enter at least two characters.';
                    return;
                }
                pickerSearchTimer = setTimeout(renderResults, delay);
            };
            pickerToggle.hidden = candidates.length === 0;
            pickerToggle.addEventListener('click', () => {
                const open = () => {
                    renderResults();
                    showPicker(true);
                };
                if (modal.classList.contains('pw-with-request')) {
                    modal.dispatchEvent(new CustomEvent('pm-close-correction-panel', {
                        bubbles:true, detail:{afterClose:open},
                    }));
                } else if (picker.hidden) open();
                else showPicker(false);
            }, {signal:listenerSignal});
            transactionSearch.addEventListener('input', event => {
                if (!event.isComposing) queueSearch(300);
            }, {signal:listenerSignal});
            transactionSearch.addEventListener('compositionend', () => queueSearch(300), {signal:listenerSignal});
            picker.querySelector('[data-pm-correction-search-form]').addEventListener('submit', event => {
                event.preventDefault();
                transactionSearch.focus();
                queueSearch(0);
            }, {signal:listenerSignal});
            clear.addEventListener('click', () => {
                transactionSearch.value = '';
                transactionSearch.dispatchEvent(new Event('input', {bubbles:true}));
                transactionSearch.focus();
            }, {signal:listenerSignal});
            modal.addEventListener('pointerdown', event => {
                if (!picker.hidden && !picker.contains(event.target)) {
                    results.hidden = true;
                    transactionSearch.setAttribute('aria-expanded', 'false');
                    transactionSearch.removeAttribute('aria-activedescendant');
                    activeIndex = -1;
                }
            }, {signal:listenerSignal});
            picker.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !results.hidden) {
                    event.preventDefault();
                    results.hidden = true;
                    transactionSearch.setAttribute('aria-expanded', 'false');
                    transactionSearch.removeAttribute('aria-activedescendant');
                    activeIndex = -1;
                    transactionSearch.focus();
                    return;
                }
                const options = results.querySelectorAll('[data-pm-correction-result]');
                if (results.hidden || !options.length || !['ArrowUp', 'ArrowDown', 'Enter'].includes(event.key)) return;
                if (event.key === 'Enter' && activeIndex >= 0) {
                    event.preventDefault();
                    options[activeIndex].click();
                    return;
                }
                if (!['ArrowUp', 'ArrowDown'].includes(event.key) || event.target !== transactionSearch) return;
                event.preventDefault();
                options[activeIndex]?.setAttribute('aria-selected', 'false');
                activeIndex = event.key === 'ArrowDown'
                    ? (activeIndex + 1) % options.length
                    : (activeIndex <= 0 ? options.length - 1 : activeIndex - 1);
                const active = options[activeIndex];
                active.setAttribute('aria-selected', 'true');
                transactionSearch.setAttribute('aria-activedescendant', active.id);
                active.scrollIntoView({block:'nearest'});
            }, {signal:listenerSignal});
            results.addEventListener('click', event => {
                if (event.target.closest('a') && event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
                    showPicker(false);
                }
            }, {signal:listenerSignal});
            picker.querySelector('[data-pm-correction-cancel]').addEventListener('click', () => {
                showPicker(false);
                transactionSearch.value = '';
                const focus = pickerToggle.getClientRects().length ? pickerToggle : modal.querySelector('[data-pm-close-transactions-modal]');
                focus?.focus({preventScroll:true});
            }, {signal:listenerSignal});
        }

        let restoreAnchor, activeDetail, detailTrigger;
        const closeDetail = (restoreFocus = true) => {
            if (activeDetail && restoreAnchor) {
                const requestAction = requestSlot.firstElementChild;
                const originalActionSlot = activeDetail.querySelector('.pm-detail-actions');
                if (requestAction && originalActionSlot) originalActionSlot.prepend(requestAction);
                activeDetail.hidden = true;
                restoreAnchor.replaceWith(activeDetail);
            }
            activeDetail = null;
            restoreAnchor = null;
            detailPane.hidden = true;
            modal.classList.remove('pm-with-detail');
            detailPaneFooter.hidden = false;
            detailPaneTitle.textContent = 'Payment Details';
            detailPaneClose.setAttribute('aria-label', 'Close payment details');
            detailTrigger?.setAttribute('aria-expanded', 'false');
            if (restoreFocus) detailTrigger?.focus({preventScroll:true});
            detailTrigger = null;
        };
        const openCompanion = (button, contentId, paneType) => {
            const open = () => {
                if (detailTrigger !== button) {
                    const detail = document.getElementById(contentId);
                    if (!detail || !host) return;
                    closeDetail(false);
                    detailTrigger = button;
                    button.setAttribute('aria-expanded', 'true');
                    if (paneType === 'history') {
                        detailPaneTitle.textContent = 'Correction History';
                        detailPaneClose.setAttribute('aria-label', 'Close correction history');
                        detailPaneFooter.hidden = true;
                    } else {
                        detailPaneTitle.textContent = 'Payment Details';
                        detailPaneClose.setAttribute('aria-label', 'Close payment details');
                        detailPaneFooter.hidden = false;
                        const printLink = detailPane.querySelector('[data-pm-detail-print]');
                        printLink.href = button.dataset.printUrl;
                        printLink.querySelector('span').textContent = button.dataset.printLabel || 'Print Payment Record';
                    }
                    restoreAnchor = document.createComment('payment-detail-position');
                    detail.before(restoreAnchor);
                    host.append(detail);
                    if (paneType === 'payment') {
                        const requestAction = detail.querySelector('[data-payment-correction-create]');
                        if (requestAction) requestSlot.append(requestAction);
                    }
                    activeDetail = detail;
                    detail.hidden = false;
                    detailPane.hidden = false;
                    modal.classList.add('pm-with-detail');
                    host.scrollTop = 0;
                }
                detailPaneClose.focus({preventScroll:true});
            };
            if (modal.classList.contains('pw-with-request')) {
                modal.dispatchEvent(new CustomEvent('pm-close-correction-panel', {
                    bubbles:true, detail:{afterClose:open},
                }));
            } else open();
        };
        modal.querySelectorAll('[data-pm-history-detail]').forEach(button => button.addEventListener('click', () => {
            openCompanion(button, button.dataset.pmHistoryDetail, 'payment');
        }, {signal:listenerSignal}));
        modal.querySelectorAll('[data-pm-correction-history-open]').forEach(button => button.addEventListener('click', () => {
            openCompanion(button, button.dataset.pmCorrectionHistoryOpen, 'history');
        }, {signal:listenerSignal}));
        modal.addEventListener('pm-history-reset', () => {
            closeDetail(false);
            if (!picker) return;
            showPicker(false);
            transactionSearch.value = '';
        }, {signal:listenerSignal});
        modal.querySelector('[data-pm-history-back]')?.addEventListener('click', () => closeDetail(), {signal:listenerSignal});
        modal.addEventListener('click', event => {
            if (event.target === modal) closeTopPaymentPanel(modal);
            const print = event.target.closest('[data-pm-detail-print], [data-pm-transaction-print]');
            if (print && event.detail > 0) print.blur();
        }, { signal: listenerSignal });
        modal.querySelectorAll('[data-pm-close-transactions-modal]').forEach(button => {
            button.addEventListener('click', () => closeTopPaymentPanel(modal), { signal: listenerSignal });
        });
    });

    document.addEventListener('keydown', event => {
        const modal = document.querySelector('.pm-transactions-backdrop.open');
        if (!modal || event.defaultPrevented || event.target.closest('dialog[open]')) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeTopPaymentPanel(modal);
            return;
        }

        if (event.key !== 'Tab') return;
        const focusable = getTransactionsModalFocusable(modal);
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (!modal.contains(document.activeElement)) {
            event.preventDefault();
            (event.shiftKey ? last : first).focus();
        } else if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }, { signal: listenerSignal });

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

    form?.querySelectorAll('select[name="per_page"], select[name="sort"]').forEach(control => {
        control.addEventListener('change', () => {
            updateClearVisibility();
            submitFilters();
        }, { signal: listenerSignal });
    });

    searchInput?.addEventListener('input', () => {
        if (searchClear) searchClear.hidden = !searchInput.value.trim();
        suggestMatches();
        updateClearVisibility();
    }, { signal: listenerSignal });
    searchInput?.addEventListener('keydown', event => {
        if(event.key==='ArrowDown' && !matchPanel?.hidden) { event.preventDefault(); matchPanel.querySelector('button')?.focus(); }
        if(event.key==='Escape') closeMatches();
        if (event.key === 'Enter') {
            event.preventDefault();
            commitSearch();
        }
    }, { signal: listenerSignal });
    searchClear?.addEventListener('click', () => {
        if (!searchInput) return;
        closeMatches();
        searchInput.value = '';
        searchClear.hidden = true;
        submitFiltersAsync();
    }, { signal: listenerSignal });
    form?.querySelector('[data-pm-search-submit]')?.addEventListener('click',commitSearch,{signal:listenerSignal});

    updateClearVisibility();
};
window.addEventListener('popstate', () => window.loadPaymentMonitoring?.(window.location.href, false));
window.initPaymentMonitoring();
</script>
@endsection
