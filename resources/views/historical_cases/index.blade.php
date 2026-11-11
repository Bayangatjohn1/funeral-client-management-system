@extends('layouts.panel')
@section('page_title', 'Historical Cases')
@section('page_desc', 'Review completed cases waiting to be added to official records')
@section('page_back')
    <a href="{{ auth()->user()->isStaff() ? route('funeral-cases.index') : route('admin.cases.index') }}" class="case-back-icon hc-back-link historical-cases-nav-link" data-historical-transition aria-label="Back to cases"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Back to cases</span></a>
@endsection
@section('header_actions')
    <div class="flex flex-wrap items-center justify-end gap-2">
        @if(auth()->user()->isStaff())
            <a href="{{ route('historical-intake.create') }}" data-intake-trigger="{{ route('historical-intake.create', ['intake_modal' => 1]) }}" class="hc-new-entry"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>New historical entry</span></a>
        @endif
    </div>
@endsection

@push('worklist-styles')
<style data-historical-route-style="historical-cases">
    body.panel-shell-body .page-content.historical-cases-shell {
        background:var(--rs-page,var(--surface)) !important;
        background-image:none !important;
    }
    .historical-cases-page { display:grid; gap:12px; --record-search-action:var(--reference-action-bg); --record-search-action-hover:var(--reference-action-hover); --record-search-action-active:var(--reference-action-hover); --record-search-action-text:var(--reference-action-text); }
    .historical-cases-page .hc-worklist-shell { display:grid; min-width:0; gap:0; }
    .panel-page-header :is(.hc-back-link,.hc-new-entry) { display:inline-flex; min-height:40px; align-items:center; justify-content:center; gap:8px; padding:0 14px; border:0; border-radius:10px; font-size:13px; font-weight:650; text-decoration:none; transition:background-color 160ms ease,color 160ms ease; white-space:nowrap; }
    .panel-page-header .hc-back-link { background:var(--records-card-alt); color:var(--records-text); }
    html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__leading > .hc-back-link { left:24px !important; }
    .panel-page-header .hc-back-link:hover { background:var(--records-card-strong); }
    .panel-page-header .hc-new-entry { min-height:40px; padding-inline:16px; background:var(--brand,#244F3B); color:#fff; }
    .panel-page-header .hc-new-entry:hover { background:var(--brand-hover,#1C4232); }
    .panel-page-header :is(.hc-back-link,.hc-new-entry):focus-visible { outline:2px solid var(--filter-ui-focus,#24513e); outline-offset:2px; }
    .historical-cases-page .hc-toolbar-shell { position:relative; z-index:4; display:grid; gap:10px; padding:12px; background:var(--records-card,var(--card)); border:1px solid var(--records-border,var(--border)); border-radius:12px; }
    .historical-cases-page .hc-toolbar { display:grid; grid-template-columns:minmax(260px,560px) minmax(0,1fr); align-items:center; gap:14px; }
    .historical-cases-page .hc-search { position:relative; display:flex; align-items:center; width:min(560px,100%); min-width:0; height:44px; padding:0; border:1px solid var(--filter-ui-border,transparent); border-radius:14px; background:var(--pm-search-surface,var(--filter-ui-surface,#EAF2ED)); }
    .historical-cases-page .hc-search > .bi-search { position:absolute; left:14px; color:var(--filter-ui-muted,#617568); pointer-events:none; }
    .historical-cases-page .hc-search input { width:100%; min-width:0; height:42px; padding:0 128px 0 40px !important; border:0; outline:0; border-radius:14px; background:transparent; color:var(--filter-ui-text,#123524); box-shadow:none; }
    .historical-cases-page .hc-search input:focus { outline:0; box-shadow:none; }
    .historical-cases-page .hc-search input::-webkit-search-cancel-button { display:none; }
    .historical-cases-page .hc-search-clear { position:absolute !important; right:92px !important; top:50% !important; translate:0 -50%; transform:none !important; display:grid; width:32px; height:32px; place-items:center; border:0; border-radius:8px; background:transparent; color:var(--filter-ui-muted,#617568); font-size:18px; }
    .historical-cases-page .hc-search-clear[hidden] { display:none; }
    html[data-theme] body.panel-shell-body .page-content .historical-cases-page #historicalCasesFilterForm .hc-search > button[type="submit"].hc-search-submit { position:absolute; inset:0 0 0 auto; width:84px; border:0; border-left:1px solid var(--filter-ui-border,transparent); border-radius:0 13px 13px 0; background:var(--record-search-action,var(--reference-action-bg,#A9C9B5)) !important; color:var(--record-search-action-text,var(--reference-action-text,#123524)) !important; font-weight:600; }
    html[data-theme] body.panel-shell-body .page-content .historical-cases-page #historicalCasesFilterForm .hc-search > button[type="submit"].hc-search-submit:hover { background:var(--record-search-action-hover,var(--reference-action-hover,#92B89F)) !important; }
    .historical-cases-page .hc-toolbar-actions { display:flex; align-items:center; justify-content:flex-end; gap:10px; flex-wrap:wrap; }
    .historical-cases-page .hc-filter { position:relative; }
    .historical-cases-page .hc-filter > summary { display:flex; align-items:center; gap:9px; min-width:156px; height:44px; padding:0 14px; border:0; border-radius:10px; background:var(--filter-ui-surface,#EAF2ED); color:var(--filter-ui-text,#123524); cursor:pointer; list-style:none; }
    .historical-cases-page .hc-filter > summary::-webkit-details-marker { display:none; }
    .historical-cases-page .hc-filter > summary .bi-chevron-down { margin-left:auto; transition:transform 160ms ease; }
    .historical-cases-page .hc-filter[open] > summary .bi-chevron-down { transform:rotate(180deg); }
    .historical-cases-page .hc-filter-count { display:inline-grid; min-width:20px; height:20px; place-items:center; padding:0 5px; border-radius:999px; background:var(--reference-action-bg,#1B4332); color:var(--reference-action-text,#fff); font-size:11px; font-weight:700; }
    .historical-cases-page .hc-filter-panel { position:absolute; z-index:10; top:calc(100% + 8px); right:0; display:grid; width:min(392px,calc(100vw - 32px)); gap:12px; padding:16px; border-radius:12px; background:var(--filter-ui-menu,#F7FAF8); box-shadow:0 12px 32px rgb(18 53 36 / 16%); }
    .historical-cases-page .hc-filter-range { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .historical-cases-page .hc-filter-panel input[type="date"] { width:100%; min-width:0; height:42px; padding:0 10px; border:0; border-radius:8px; background:var(--filter-ui-surface,#EAF2ED); color:var(--filter-ui-text,#123524); }
    .historical-cases-page .hc-filter-heading { padding-bottom:10px; border-bottom:1px solid var(--records-border,var(--border)); }
    .historical-cases-page .hc-filter-heading strong { display:block; color:var(--records-text,var(--ink)); font-size:14px; }
    .historical-cases-page .hc-filter-heading span { display:block; margin-top:3px; color:var(--records-muted,var(--ink-muted)); font-size:12px; }
    .historical-cases-page .hc-filter-panel label { display:grid; gap:6px; color:var(--filter-ui-text,#123524); font-size:13px; font-weight:600; }
    .historical-cases-page .hc-filter-panel select { width:100%; min-width:0; height:42px; padding:0 12px; border:0; border-radius:8px; background:var(--filter-ui-surface,#EAF2ED); color:var(--filter-ui-text,#123524); }
    .historical-cases-page .hc-apply { justify-self:end; min-height:40px; padding:0 14px; border:0; border-radius:9px; background:var(--reference-action-bg,#1B4332); color:var(--reference-action-text,#fff); font-weight:600; }
    .historical-cases-page .hc-sort { position:relative; }
    .historical-cases-page .hc-sort > summary { display:flex; align-items:center; gap:9px; min-width:174px; height:44px; padding:0 14px; border:0; border-radius:10px; background:var(--filter-ui-surface,#EAF2ED); color:var(--filter-ui-text,#123524); cursor:pointer; list-style:none; }
    .historical-cases-page .hc-sort > summary::-webkit-details-marker { display:none; }
    .historical-cases-page .hc-sort > summary .bi-chevron-down { margin-left:auto; transition:transform 160ms ease; }
    .historical-cases-page .hc-sort[open] > summary .bi-chevron-down { transform:rotate(180deg); }
    .historical-cases-page .hc-sort-menu { position:absolute; z-index:10; top:calc(100% + 8px); right:0; display:grid; width:220px; gap:4px; padding:6px; border-radius:10px; background:var(--filter-ui-menu,#F7FAF8); box-shadow:0 12px 32px rgb(18 53 36 / 16%); }
    .historical-cases-page .hc-sort-menu a { display:flex; align-items:center; min-height:40px; padding:0 10px; border-radius:7px; color:var(--filter-ui-text,#123524); font-size:14px; text-decoration:none; }
    .historical-cases-page .hc-sort-menu a:hover,.historical-cases-page .hc-sort-menu a[aria-current="true"] { background:var(--filter-ui-surface,#EAF2ED); }
    .historical-cases-page .hc-page-size { display:flex; align-items:center; gap:8px; color:var(--filter-ui-text,#123524); white-space:nowrap; }
    .historical-cases-page .hc-page-size label { font-size:12px; font-weight:600; }
    .historical-cases-page .hc-page-size select { width:88px; height:44px; padding:0 32px 0 12px; border:0; border-radius:10px; appearance:auto; background:transparent; color:var(--filter-ui-text,#123524); }
    .historical-cases-page :is(.hc-apply,.hc-search-submit,.hc-filter > summary,.hc-sort > summary,.hc-search-clear,.hc-view):hover { filter:brightness(.97); }
    .historical-cases-page :is(.hc-apply,.hc-search-submit,.hc-filter > summary,.hc-sort > summary,.hc-search-clear,.hc-view):focus-visible { outline:2px solid var(--filter-ui-focus,#24513e); outline-offset:2px; }
    .historical-cases-page .hc-applied { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; padding:2px 2px 0; }
    .historical-cases-page .hc-applied[hidden] { display:none; }
    .historical-cases-page .hc-chips { display:flex; flex-wrap:wrap; gap:7px; }
    .historical-cases-page .hc-chip { display:inline-flex; align-items:center; gap:6px; min-height:28px; padding:3px 8px 3px 10px; border-radius:999px; background:var(--filter-ui-surface,#EAF2ED); color:var(--filter-ui-text,#123524); font-size:12px; }
    .historical-cases-page .hc-chip button { display:grid; width:20px; height:20px; place-items:center; border:0; border-radius:50%; background:transparent; color:inherit; }
    .historical-cases-page .hc-clear-filters { padding:5px 2px; border:0; background:transparent; color:#A43A32; font-size:13px; font-weight:600; text-decoration:none; }
    .historical-cases-page .hc-results { height:clamp(420px,calc(100dvh - 365px),700px); min-height:420px; overflow:hidden; background:var(--records-card,var(--card)); border:1px solid var(--records-border,var(--border)); border-radius:8px; }
    .historical-cases-page .hc-table-scroll { height:100%; overflow:auto; overscroll-behavior:contain; }
    .historical-cases-page table { width:100%; min-width:1280px; height:100%; border-collapse:separate; border-spacing:0; text-align:left; font-size:15px; color:var(--records-text,var(--ink)); }
    .historical-cases-page .table-system-table thead { background:var(--records-card-strong); }
    .historical-cases-page .table-system-table thead th { padding:16px 14px; border-bottom:1px solid var(--records-border,var(--border)); background:var(--records-card-strong); color:var(--records-muted,var(--ink-muted)); font-size:13px; font-weight:600; letter-spacing:.055em; text-transform:uppercase; white-space:nowrap; }
    .historical-cases-page .table-system-table tbody td { padding:16px 14px; border-bottom:1px solid var(--records-border,var(--border)); background:var(--records-card,var(--card)); color:var(--records-text,var(--ink)); vertical-align:middle; }
    .historical-cases-page .table-system-table tbody tr:nth-child(even) td { background:var(--records-card-alt); }
    .historical-cases-page .table-system-table tbody tr:last-child td { border-bottom:0; }
    .historical-cases-page .table-system-table tbody tr { transition:background-color 150ms ease; }
    .historical-cases-page .table-system-table tbody tr:hover td { background:var(--records-hover,rgba(36,79,59,.045)); }
    .historical-cases-page .hc-primary { font-weight:650; }
    .historical-cases-page .hc-secondary { display:block; margin-top:3px; color:var(--filter-ui-muted,#617568); font-size:12px; }
    .historical-cases-page .hc-status { display:inline-flex; align-items:center; min-height:26px; padding:3px 9px; border-radius:999px; font-size:12px; font-weight:650; white-space:nowrap; }
    .historical-cases-page .hc-view { display:inline-flex; align-items:center; justify-content:center; min-height:36px; padding:0 12px; border:0; border-radius:8px; background:var(--filter-ui-surface,#EAF2ED); color:var(--filter-ui-text,#123524); font-size:13px; font-weight:600; text-decoration:none; white-space:nowrap; }
    .historical-cases-page .hc-empty { display:grid; min-height:360px; height:100%; align-content:center; justify-items:center; gap:6px; padding:44px 20px; background:transparent; color:var(--records-muted,var(--ink-muted)); text-align:center; }
    .historical-cases-page .hc-empty > i { display:grid; width:44px; height:44px; margin-bottom:4px; place-items:center; border-radius:12px; background:var(--rs-control,var(--filter-ui-surface)); color:var(--rs-text,var(--records-text)); font-size:20px; }
    .historical-cases-page .hc-empty > strong { color:var(--rs-text,var(--records-text)); font-size:14px; }
    .historical-cases-page .hc-empty > p { margin:0; color:var(--rs-muted,var(--records-muted)); font-size:13px; }
    .historical-cases-page .hc-page-alert { display:flex; align-items:center; gap:9px; padding:11px 14px; border:1px solid color-mix(in srgb,#16A34A 30%,transparent); border-radius:10px; background:color-mix(in srgb,#16A34A 10%,var(--rs-surface,var(--records-card))); color:#166534; font-size:14px; font-weight:500; }
    html[data-theme='dark'] .historical-cases-page .hc-page-alert { border-color:color-mix(in srgb,#4ADE80 34%,transparent); color:#86EFAC; }
    .historical-cases-page .hc-pagination { position:sticky; bottom:0; z-index:5; display:flex; min-height:58px; align-items:center; justify-content:stretch; margin-top:0; padding:8px 14px; background:var(--records-card,var(--card)); border:1px solid var(--records-border,var(--border)); border-radius:10px; }
    html[data-theme] body.panel-shell-body .historical-cases-page .hc-pagination .table-paginator { display:flex; width:100%; min-height:40px; align-items:center; justify-content:space-between; gap:10px; margin:0; padding:0; border:0 !important; border-radius:0; background:transparent !important; background-color:transparent !important; box-shadow:none !important; }
    html[data-theme] body.panel-shell-body.record-surface-ui:is(.staff-ui,.branch-admin-ui,.system-admin-ui,.owner-ui) .page-content .historical-cases-page .hc-pagination .table-paginator { background:transparent !important; background-color:transparent !important; }
    .historical-cases-page .hc-pagination .table-paginator-meta { color:var(--records-muted,var(--ink-muted)); }
    .historical-cases-page .hc-pagination .table-paginator-nav { display:flex; justify-content:flex-end; gap:6px; margin:0 0 0 auto; }
    html[data-theme] body.panel-shell-body.record-surface-ui:is(.staff-ui,.branch-admin-ui,.system-admin-ui,.owner-ui) .page-content .historical-cases-page .hc-pagination .table-paginator-btn { min-width:30px; height:30px; padding:0 8px; border:1px solid var(--records-border,var(--border)) !important; border-radius:4px; background:var(--records-card,var(--card)) !important; color:var(--records-muted,var(--ink-muted)) !important; font-size:12px; }
    html[data-theme] body.panel-shell-body.record-surface-ui:is(.staff-ui,.branch-admin-ui,.system-admin-ui,.owner-ui) .page-content .historical-cases-page .hc-pagination .table-paginator-btn:hover { background:var(--records-card-alt,var(--surface-muted)) !important; border-color:var(--records-border-strong,var(--border-strong)) !important; color:var(--records-text,var(--ink)) !important; }
    html[data-theme] body.panel-shell-body.record-surface-ui:is(.staff-ui,.branch-admin-ui,.system-admin-ui,.owner-ui) .page-content .historical-cases-page .hc-pagination .table-paginator-btn.is-active { background:var(--brand,#244F3B) !important; border-color:var(--brand,#244F3B) !important; color:#fff !important; }
    html[data-theme] body.panel-shell-body.record-surface-ui:is(.staff-ui,.branch-admin-ui,.system-admin-ui,.owner-ui) .page-content .historical-cases-page .hc-pagination .table-paginator-btn.is-disabled { background:var(--records-card-alt,var(--surface-muted)) !important; border-color:var(--records-border,var(--border)) !important; color:var(--records-muted,var(--ink-muted)) !important; opacity:.58; }
    @media(max-width:850px) {
        .historical-cases-page .hc-toolbar { grid-template-columns:minmax(0,1fr); }
        .historical-cases-page .hc-toolbar-actions { display:grid; grid-template-columns:1fr 1fr; justify-content:stretch; }
        .historical-cases-page .hc-filter, .historical-cases-page .hc-filter > summary { width:100%; }
        .historical-cases-page .hc-sort, .historical-cases-page .hc-sort > summary { width:100%; }
        .historical-cases-page .hc-filter-panel, .historical-cases-page .hc-sort-menu { left:0; right:auto; }
        .historical-cases-page .hc-applied { align-items:flex-start; }
        html[data-theme] body.panel-shell-body .historical-cases-page .hc-pagination .table-paginator { flex-direction:row; justify-content:space-between; gap:8px; }
        .historical-cases-page .hc-pagination .table-paginator-meta { grid-column:auto; }
        .historical-cases-page .hc-pagination .table-paginator-nav { grid-column:auto; margin-left:auto; }
        .historical-cases-page .hc-page-size { justify-content:space-between; grid-column:1 / -1; }
    }
    @media(max-width:520px) {
        .historical-cases-page .hc-results { height:calc(100dvh - 390px); min-height:360px; }
        .historical-cases-page .hc-pagination .table-paginator { flex-wrap:wrap; }
        .historical-cases-page .hc-filter-range { grid-template-columns:1fr; }
        html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__leading > .hc-back-link { left:16px !important; }
        .panel-page-header .hc-back-link { width:40px; padding:0; }
        .panel-page-header .hc-back-link span { display:none; }
    }

    /* Current record-worklist presentation shared by every authorized role. */
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page {
        color:var(--rs-text);
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-toolbar-shell {
        background:var(--rs-surface);
        border-color:transparent;
        border-radius:12px;
        box-shadow:none;
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-search .worklist-search-matches {
        width:100%;
        background:var(--rs-menu);
        border:0;
        box-shadow:none;
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-search .worklist-match {
        display:grid;
        grid-template-columns:auto minmax(0,1fr) minmax(0,1fr);
        gap:10px;
        min-height:40px;
        width:100%;
    }
    .historical-cases-page .hc-search .worklist-match > :is(strong,span) { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .historical-cases-page .hc-search .worklist-match > strong { font-size:14px; font-weight:600; }
    .historical-cases-page .hc-search .worklist-match > span { font-size:13px; font-weight:400; }
    .historical-cases-page .hc-search .worklist-match mark { padding:0; background:transparent; color:inherit; text-decoration:underline; text-decoration-thickness:1px; text-underline-offset:3px; }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-search .worklist-match:is(:hover,[aria-selected="true"]) { background:var(--rs-item-hover) !important; }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page :is(.hc-search,.hc-filter > summary,.hc-sort > summary,.hc-page-size select) {
        background:var(--rs-control);
        border-color:transparent;
        box-shadow:none;
        color:var(--rs-text);
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page :is(.hc-filter > summary,.hc-sort > summary):hover,
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page :is(.hc-filter,.hc-sort)[open] > summary {
        background:var(--rs-hover);
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-filter-panel {
        background:var(--rs-panel);
        border:0;
        box-shadow:none;
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-sort-menu {
        width:280px;
        background:var(--rs-menu);
        border:0;
        box-shadow:none;
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-filter-panel :is(input,select) {
        background:var(--rs-control);
        color:var(--rs-text);
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-sort-menu a:hover,
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-sort-menu a[aria-current="true"] {
        background:var(--rs-item-hover);
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-apply {
        min-height:44px;
        background:var(--reference-action-bg);
        color:var(--reference-action-text);
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-chip {
        background:var(--rs-control);
        color:var(--rs-text);
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-clear-filters {
        min-height:32px;
        padding:5px 8px;
        border:1px solid transparent;
        border-radius:8px;
        color:#B42318;
        font-size:14px;
        font-weight:400;
    }
    html[data-theme='dark'] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-clear-filters { color:#FDA29B; }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-clear-filters:is(:hover,:focus-visible) {
        border-color:currentColor;
        background:transparent;
        filter:none;
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-results {
        background:var(--rs-surface);
        border:0;
        border-radius:12px;
        box-shadow:none;
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .table-system-table thead,
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .table-system-table thead th {
        background:transparent;
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .table-system-table thead th {
        padding:15px 16px;
        border-bottom:1px solid var(--rs-divider);
        color:var(--rs-text);
        font-size:13px;
        font-weight:650;
        letter-spacing:.045em;
        text-transform:none;
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .table-system-table tbody td,
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .table-system-table tbody tr:nth-child(even) td {
        padding:15px 16px;
        border-bottom:1px solid var(--rs-divider);
        background:transparent;
        color:var(--rs-text);
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .table-system-table tbody tr:hover td {
        background:var(--rs-item-hover);
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-status {
        min-height:0;
        padding:0;
        border:0;
        border-radius:0;
        background:transparent !important;
        font-size:13px;
        font-weight:600;
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-status[data-status="APPROVED"] { color:#166534 !important; }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-status[data-status="REJECTED"] { color:#B42318 !important; }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-status[data-status="NEEDS_CHANGES"] { color:#92400E !important; }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-status[data-status="PENDING"] { color:#175CD3 !important; }
    html[data-theme='dark'] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-status[data-status="APPROVED"] { color:#4ADE80 !important; }
    html[data-theme='dark'] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-status[data-status="REJECTED"] { color:#FDA29B !important; }
    html[data-theme='dark'] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-status[data-status="NEEDS_CHANGES"] { color:#FBBF24 !important; }
    html[data-theme='dark'] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-status[data-status="PENDING"] { color:#84B8FF !important; }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-view {
        width:40px;
        min-width:40px;
        height:40px;
        min-height:40px;
        padding:0;
        border-radius:8px;
        background:transparent;
        color:var(--rs-text);
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-view:is(:hover,:focus-visible) {
        background:transparent;
        color:#1B4332;
        filter:none;
    }
    html[data-theme='dark'] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-view:is(:hover,:focus-visible) { color:#5CE0A0; }
    html[data-theme] body.panel-shell-body.record-surface-ui .historical-cases-page .hc-pagination {
        position:static;
        margin-top:12px;
        background:var(--rs-surface);
        border:0;
        border-radius:12px;
        box-shadow:none;
    }
</style>
@endpush

@section('content')
@php
    $statusOptions = [
        \App\Models\HistoricalCaseSubmission::PENDING => 'Pending approval',
        \App\Models\HistoricalCaseSubmission::NEEDS_CHANGES => 'Needs changes',
        \App\Models\HistoricalCaseSubmission::REJECTED => 'Rejected',
        \App\Models\HistoricalCaseSubmission::APPROVED => 'Approved',
    ];
    $selectedBranch = auth()->user()->isSystemAdmin()
        ? $branches->firstWhere('id', $branchId)
        : null;
    $formatFilterDate = static fn ($value) => filled($value) ? \Illuminate\Support\Carbon::parse($value)->format('M j, Y') : null;
    $filterChips = [];
    if (filled($status)) $filterChips['status'] = 'Status: '.($statusOptions[$status] ?? $status);
    if (filled($branchId) && auth()->user()->isSystemAdmin()) {
        $filterChips['branch_id'] = 'Branch: '.($selectedBranch ? $selectedBranch->branch_code.' · '.$selectedBranch->branch_name : 'Selected branch');
    }
    if (filled($submittedFrom) || filled($submittedTo)) {
        $filterChips['submitted_range'] = 'Submitted: '.($formatFilterDate($submittedFrom) ?? 'Any date').' – '.($formatFilterDate($submittedTo) ?? 'Any date');
    }
    if (filled($serviceFrom) || filled($serviceTo)) {
        $filterChips['service_range'] = 'Service date: '.($formatFilterDate($serviceFrom) ?? 'Any date').' – '.($formatFilterDate($serviceTo) ?? 'Any date');
    }
    $activeFilterCount = count($filterChips);
@endphp
<main class="historical-cases-page w-full px-4 py-5 text-slate-800 md:px-6" data-worklist-consistent>
    @if(session('success'))
        <div class="hc-page-alert" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
    @endif

    <div class="hc-worklist-shell">
    <form id="historicalCasesFilterForm" method="GET" action="{{ route('historical-cases.index') }}" class="hc-toolbar-shell table-system-toolbar uniform-record-filters case-record-filters">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <div class="hc-toolbar">
                <div class="hc-search worklist-search-control shared-record-search">
                    <i class="bi bi-search shared-record-search-icon" aria-hidden="true"></i>
                    <label class="sr-only" for="historicalCaseSearch">Search historical submissions</label>
                    <input id="historicalCaseSearch" class="shared-record-search-input" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Submission ID, client/deceased, branch, encoder, status or date" aria-label="Search submission ID, client or deceased name, branch, encoder, status, or date" aria-controls="historicalSearchMatches" aria-expanded="false" aria-autocomplete="list" autocomplete="off">
                    <button type="button" class="hc-search-clear shared-record-search-clear" data-hc-clear-search aria-label="Clear search" @if(!filled($search)) hidden @endif><i class="bi bi-x" aria-hidden="true"></i></button>
                    <button type="submit" class="hc-search-submit worklist-search-submit">Search</button>
                    <div id="historicalSearchMatches" class="worklist-search-matches" data-hc-search-matches role="listbox" aria-label="Matching historical submissions" hidden></div>
                </div>
                <div class="hc-toolbar-actions">
                    <details class="hc-filter worklist-filter">
                        <summary><i class="bi bi-funnel" aria-hidden="true"></i><span>Filter</span>@if($activeFilterCount)<span class="hc-filter-count">{{ $activeFilterCount }}</span>@endif<i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                        <div class="hc-filter-panel worklist-filter-panel" role="group" aria-label="Historical case filters">
                            <div class="hc-filter-heading"><strong>Filter by</strong><span>Choose the submission details to narrow the list.</span></div>
                            @if(auth()->user()->isSystemAdmin())
                                <label for="historicalBranch">Branch
                                    <select id="historicalBranch" name="branch_id">
                                        <option value="">All branches</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->branch_code }} · {{ $branch->branch_name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
                            <label for="historicalStatus">Review status
                                <select id="historicalStatus" name="status">
                                    <option value="">All statuses</option>
                                    @foreach($statusOptions as $key => $label)
                                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="hc-filter-range" role="group" aria-label="Submission date range">
                                <label for="submittedFrom">Submitted from
                                    <input id="submittedFrom" type="date" name="submitted_from" value="{{ $submittedFrom }}">
                                </label>
                                <label for="submittedTo">Submitted to
                                    <input id="submittedTo" type="date" name="submitted_to" value="{{ $submittedTo }}">
                                </label>
                            </div>
                            <div class="hc-filter-range" role="group" aria-label="Service date range">
                                <label for="serviceFrom">Service date from
                                    <input id="serviceFrom" type="date" name="service_from" value="{{ $serviceFrom }}">
                                </label>
                                <label for="serviceTo">Service date to
                                    <input id="serviceTo" type="date" name="service_to" value="{{ $serviceTo }}">
                                </label>
                            </div>
                            <button type="submit" class="hc-apply">Apply filters</button>
                        </div>
                    </details>
                    <details class="hc-sort catalog-control">
                        <summary><i class="bi bi-sort-down" aria-hidden="true"></i><span>{{ $sort === 'oldest' ? 'Oldest first' : 'Newest first' }}</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                        <div class="hc-sort-menu catalog-control-panel" aria-label="Sort historical cases">
                            <a href="{{ route('historical-cases.index', array_merge(request()->except('page'), ['sort' => 'newest'])) }}" @if($sort === 'newest') aria-current="true" @endif>Newest first</a>
                            <a href="{{ route('historical-cases.index', array_merge(request()->except('page'), ['sort' => 'oldest'])) }}" @if($sort === 'oldest') aria-current="true" @endif>Oldest first</a>
                        </div>
                    </details>
                    <div class="hc-page-size worklist-control">
                        <label for="historicalPerPage">Cases per page</label>
                        <select id="historicalPerPage" name="per_page" data-hc-page-size aria-label="Historical cases per page">
                            @foreach([10, 20, 25, 50, 100] as $pageSize)
                                <option value="{{ $pageSize }}" @selected($perPage === $pageSize)>{{ $pageSize }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
        </div>
        <div class="hc-applied" @if(!$filterChips) hidden @endif>
                    <div class="hc-chips" aria-label="Applied historical case filters">
                        @foreach($filterChips as $key => $label)
                            @php($removeNames = match($key) { 'submitted_range' => 'submitted_from,submitted_to', 'service_range' => 'service_from,service_to', default => $key })
                            <span class="hc-chip">{{ $label }}<button type="button" data-hc-remove="{{ $removeNames }}" aria-label="Remove {{ $label }}"><i class="bi bi-x" aria-hidden="true"></i></button></span>
                        @endforeach
                    </div>
                    @if($activeFilterCount)
                        <a class="hc-clear-filters" href="{{ route('historical-cases.index', request()->except(['status', 'branch_id', 'submitted_from', 'submitted_to', 'service_from', 'service_to', 'page'])) }}">Clear filters</a>
                    @endif
        </div>
    </form>

    <section class="hc-results table-system-wrap" aria-label="Historical case submissions">
        <div class="hc-table-scroll" role="region" aria-label="Historical case submissions table" tabindex="0">
                <table class="table-system-table">
                    <thead><tr><th scope="col">Submission</th><th scope="col">Client / Deceased</th><th scope="col">Service dates</th><th scope="col">Branch</th><th scope="col">Submitted by / Date</th><th scope="col">Status</th><th scope="col" class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @forelse($submissions as $submission)
                            @php($data = $submission->submission_data)
                            @php($v = $data['validated'] ?? [])
                            <tr data-clickable-row tabindex="0" data-hc-search-result data-hc-code="{{ $submission->submission_code }}" data-hc-client="{{ trim(($v['client_first_name'] ?? '').' '.($v['client_last_name'] ?? '')) }}" data-hc-deceased="{{ trim(($v['deceased_first_name'] ?? '').' '.($v['deceased_last_name'] ?? '')) }}">
                                <td><span class="hc-primary">{{ $submission->submission_code }}</span>@if($submission->official_case_id)<span class="hc-secondary">Case {{ $submission->officialCase?->case_code }}</span>@endif</td>
                                <td><span class="hc-primary">{{ trim(($v['client_first_name'] ?? '').' '.($v['client_last_name'] ?? '')) }}</span><span class="hc-secondary">{{ trim(($v['deceased_first_name'] ?? '').' '.($v['deceased_last_name'] ?? '')) }}</span></td>
                                <td>{{ $v['wake_start_date'] ?? 'Date unavailable' }}<span class="hc-secondary">through {{ $v['interment_at'] ?? 'Date unavailable' }}</span></td>
                                <td>{{ $submission->branch?->branch_code }} · {{ $submission->branch?->branch_name }}</td>
                                <td>{{ $submission->encoder?->name }}<span class="hc-secondary">{{ $submission->created_at?->format('M d, Y h:i A') }}</span></td>
                                <td><span class="hc-status" data-status="{{ $submission->status }}">{{ $statusOptions[$submission->status] ?? \Illuminate\Support\Str::headline($submission->status) }}</span></td>
                                <td class="text-right"><a class="hc-view primary-view-action record-action-control record-row-view" href="{{ route('historical-cases.show', $submission) }}" data-historical-transition data-row-view-trigger aria-label="View historical submission {{ $submission->submission_code }}" data-action-tooltip="View submission"><i class="bi bi-eye" aria-hidden="true"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="hc-empty records-empty-state"><i class="bi bi-clock-history" aria-hidden="true"></i><strong>No historical submissions found.</strong><p>Try changing your search or filters.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
        </div>
    </section>
    </div>
    <footer class="hc-pagination table-system-pagination">{{ $submissions->withQueryString()->links('components.pagination.table') }}</footer>
</main>
<script>
    (() => {
        const form = document.getElementById('historicalCasesFilterForm');
        if (!form || form.dataset.hcReady) return;
        form.dataset.hcReady = '1';
        const search = form.querySelector('[name="q"]');
        const matches = form.querySelector('[data-hc-search-matches]');
        const clearSearch = form.querySelector('[data-hc-clear-search]');
        let activeMatch = -1;
        let searchTimer = 0;
        let searchRequest = null;
        let searchVersion = 0;

        const readMatches = (root = document) => [...root.querySelectorAll('[data-hc-search-result]')].map((row) => ({
            code: row.dataset.hcCode || '',
            client: row.dataset.hcClient || '',
            deceased: row.dataset.hcDeceased || '',
        })).filter((item) => item.code);
        const loadedMatches = readMatches();
        const appendHighlighted = (target, value, query) => {
            const source = String(value || '');
            const needle = query.toLocaleLowerCase();
            if (!needle) { target.append(document.createTextNode(source)); return; }
            let start = 0;
            let index = source.toLocaleLowerCase().indexOf(needle);
            while (index !== -1) {
                target.append(document.createTextNode(source.slice(start, index)));
                const mark = document.createElement('mark');
                mark.textContent = source.slice(index, index + query.length);
                target.append(mark);
                start = index + query.length;
                index = source.toLocaleLowerCase().indexOf(needle, start);
            }
            target.append(document.createTextNode(source.slice(start)));
        };
        const closeMatches = () => {
            matches.hidden = true;
            matches.replaceChildren();
            activeMatch = -1;
            search.setAttribute('aria-expanded', 'false');
            search.removeAttribute('aria-activedescendant');
        };
        const renderMatches = (items, query) => {
            const unique = [...new Map(items.map((item) => [item.code, item])).values()].slice(0, 5);
            matches.replaceChildren();
            activeMatch = -1;
            unique.forEach((item, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.id = `historicalMatch${index}`;
                button.className = 'worklist-match';
                button.setAttribute('role', 'option');
                button.dataset.hcSearchCode = item.code;
                button.setAttribute('aria-label', `${item.code}, ${item.client || 'Client unavailable'}, ${item.deceased || 'Deceased unavailable'}`);
                const code = document.createElement('strong');
                const client = document.createElement('span');
                const deceased = document.createElement('span');
                appendHighlighted(code, item.code, query);
                appendHighlighted(client, item.client || 'Client unavailable', query);
                appendHighlighted(deceased, item.deceased || 'Deceased unavailable', query);
                button.append(code, client, deceased);
                matches.append(button);
            });
            matches.hidden = unique.length === 0;
            search.setAttribute('aria-expanded', unique.length ? 'true' : 'false');
        };
        const matchingLoaded = (query) => {
            const needle = query.toLocaleLowerCase();
            return loadedMatches.filter((item) => [item.code, item.client, item.deceased].some((value) => value.toLocaleLowerCase().includes(needle)));
        };
        const requestMatches = (query) => {
            const version = ++searchVersion;
            searchRequest?.abort();
            searchRequest = new AbortController();
            const url = new URL(form.action, window.location.href);
            new FormData(form).forEach((value, key) => { if (String(value).trim() !== '') url.searchParams.set(key, value); });
            url.searchParams.set('q', query);
            url.searchParams.set('per_page', '10');
            url.searchParams.delete('page');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: searchRequest.signal })
                .then((response) => response.ok ? response.text() : Promise.reject(new Error('Search unavailable')))
                .then((html) => {
                    if (version !== searchVersion || search.value.trim() !== query) return;
                    const parsed = new DOMParser().parseFromString(html, 'text/html');
                    renderMatches([...matchingLoaded(query), ...readMatches(parsed)], query);
                })
                .catch((error) => { if (error.name !== 'AbortError') renderMatches(matchingLoaded(query), query); });
        };
        search?.addEventListener('input', () => {
            const query = search.value.trim();
            if (clearSearch) clearSearch.hidden = search.value.length === 0;
            window.clearTimeout(searchTimer);
            searchRequest?.abort();
            if (query.length < 2) { closeMatches(); return; }
            renderMatches(matchingLoaded(query), query);
            searchTimer = window.setTimeout(() => requestMatches(query), 300);
        });
        search?.addEventListener('keydown', (event) => {
            const options = [...matches.querySelectorAll('[role="option"]')];
            if (event.key === 'Escape') { closeMatches(); return; }
            if (!options.length || !['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) return;
            if (event.key === 'Enter' && activeMatch < 0) return;
            event.preventDefault();
            if (event.key === 'ArrowDown') activeMatch = (activeMatch + 1) % options.length;
            if (event.key === 'ArrowUp') activeMatch = (activeMatch - 1 + options.length) % options.length;
            if (event.key === 'Enter') { options[activeMatch].click(); return; }
            options.forEach((option, index) => option.setAttribute('aria-selected', index === activeMatch ? 'true' : 'false'));
            search.setAttribute('aria-activedescendant', options[activeMatch].id);
        });
        matches?.addEventListener('click', (event) => {
            const option = event.target.closest('[data-hc-search-code]');
            if (!option) return;
            search.value = option.dataset.hcSearchCode;
            closeMatches();
            form.requestSubmit();
        });
        form.querySelector('[data-hc-page-size]')?.addEventListener('change', event => {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', event.currentTarget.value);
            url.searchParams.delete('page');
            window.location.assign(url.href);
        });
        clearSearch?.addEventListener('click', () => { search.value = ''; clearSearch.hidden = true; closeMatches(); form.requestSubmit(); });
        form.querySelectorAll('[data-hc-remove]').forEach((button) => button.addEventListener('click', () => {
            button.dataset.hcRemove.split(',').forEach(key => {
                const field = form.querySelector(`[name="${CSS.escape(key)}"]`);
                if (field) field.value = '';
            });
            form.requestSubmit();
        }));
        document.addEventListener('pointerdown', (event) => {
            if (!form.querySelector('.hc-search')?.contains(event.target)) closeMatches();
            form.querySelectorAll('.hc-filter, .hc-sort').forEach((popover) => {
                if (popover.open && !popover.contains(event.target)) popover.open = false;
            });
        });
        form.querySelectorAll('.hc-filter, .hc-sort').forEach((popover) => popover.addEventListener('toggle', () => {
            if (!popover.open) return;
            form.querySelectorAll('.hc-filter, .hc-sort').forEach((other) => { if (other !== popover) other.open = false; });
        }));
        form.addEventListener('submit', () => { search.value = search.value.trim(); closeMatches(); });
    })();
</script>
@endsection
