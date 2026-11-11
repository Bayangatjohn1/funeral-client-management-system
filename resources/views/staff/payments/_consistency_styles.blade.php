<style>
    .payment-monitoring-page .record-toolbar-action { display:flex; gap:8px; flex-wrap:wrap; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page {
        --pm-control-surface:var(--filter-ui-menu,var(--card));
        --pm-soft-outline:#AEC7B7; --pm-view-color:#1B4332; --pm-info-color:#175CD3;
        --pm-clear-color:#B42318; --pm-search-surface:#EAF2ED;
    }
    html[data-theme='dark'] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page {
        --pm-soft-outline:#41634F; --pm-view-color:#5CE0A0; --pm-info-color:#84B8FF;
        --pm-clear-color:#FDA29B; --pm-search-surface:#182D22;
    }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page [data-pm-print-preview] { background:#E7F0FF !important; border-color:#B8CEF3 !important; color:#175CD3 !important; box-shadow:none !important; transform:none !important; transition:background-color .16s ease,border-color .16s ease,color .16s ease !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page [data-pm-print-preview]:is(:hover,:focus-visible) { background:#D6E6FF !important; border-color:#8EB1E8 !important; color:#1249AA !important; }
    html[data-theme='dark'] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page [data-pm-print-preview] { background:#183153 !important; border-color:#315C8F !important; color:#84B8FF !important; }
    html[data-theme='dark'] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page [data-pm-print-preview]:is(:hover,:focus-visible) { background:#203F69 !important; border-color:#4F7DB3 !important; color:#B8D5FF !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar-shell { padding:12px !important; border-radius:12px !important; overflow:visible !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar {
        --filter-ui-surface:var(--pm-control-surface); --uniform-filter-bg:var(--pm-control-surface); --staff-records-soft:var(--pm-control-surface);
        display:grid !important; grid-template-columns:minmax(200px,1fr) 160px 160px auto; gap:14px !important; align-items:center;
    }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar .pm-field.search.shared-record-search {
        --filter-ui-surface:var(--pm-search-surface);
        min-width:0 !important; max-width:none !important; margin-right:0 !important; width:100%; border:1px solid var(--border); border-radius:14px !important; background:var(--pm-search-surface) !important;
    }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-field.search input {
        height:44px !important; border:0 !important; background:transparent !important; border-radius:14px !important; padding-right:38px !important; font-size:14px !important; font-weight:400 !important;
    }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-control {
        --filter-control-background:var(--pm-control-surface);
        height:44px !important; font-size:14px !important; font-weight:500; color:var(--ink) !important;
        transition:border-color .16s ease,color .16s ease !important;
        border-radius:8px !important; border:1px solid var(--border) !important; background:var(--pm-control-surface) !important; box-shadow:none !important;
    }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar .search input.pm-control { --filter-control-background:transparent; --filter-control-border:0; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-control:hover { transform:none !important; background:var(--pm-control-surface) !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-more-filters { min-width:0; width:160px; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-more-filters > summary {
        width:100%; height:44px; justify-content:flex-start; gap:10px; padding:0 14px;
        border:1px solid var(--border); border-radius:8px; background:var(--pm-control-surface) !important;
        color:var(--ink); font-size:14px; font-weight:500; transform:none !important; box-shadow:none !important;
    }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-more-filters > summary > i:last-child { margin-left:auto; transform:none !important; rotate:0deg; transition:rotate .16s ease; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-more-filters[open] > summary > i:last-child { rotate:180deg; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-filter-count { display:grid; place-items:center; min-width:21px; height:21px; padding:0 5px; border-radius:999px; background:var(--brand); color:var(--card); font-size:12px; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-more-panel {
        left:0; right:auto; top:calc(100% + 8px); width:min(320px,calc(100vw - 32px)); max-width:calc(100vw - 32px); max-height:min(65vh,512px); box-sizing:border-box;
        display:flex; flex-direction:column; gap:0; padding:0; overflow:hidden;
        background:var(--pm-control-surface); border:1px solid var(--border); border-radius:10px; box-shadow:none;
    }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-more-panel .pm-field { flex:0 0 44px !important; width:100% !important; min-width:0 !important; max-width:none !important; height:44px !important; min-height:44px !important; max-height:44px !important; margin:0 !important; position:relative; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar .shared-record-filter-icon { position:absolute !important; left:14px !important; top:50% !important; transform:none !important; translate:0 -50%; width:16px; pointer-events:none; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar .shared-record-chevron { position:absolute !important; right:12px !important; top:50% !important; left:auto !important; transform:none !important; translate:0 -50%; rotate:0deg; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar .is-open > .shared-record-chevron { rotate:180deg; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-filter-head { display:flex; align-items:center; justify-content:space-between; padding-bottom:8px; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-filter-head button { display:grid; place-items:center; width:36px; height:36px; border:1px solid var(--border); border-radius:8px; background:transparent; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-filter-label { margin-top:8px; font-size:12px; font-weight:600; letter-spacing:.04em; color:var(--ink-muted); }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .filter-select-menu { background:var(--pm-control-surface) !important; color:var(--ink); border:1px solid var(--border); border-radius:10px; padding:6px; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .filter-select-menu__item { min-height:40px; font-size:14px; font-weight:500; white-space:normal; text-align:left; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-sort-control { width:160px; flex:none; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-page-size { font-size:14px; font-weight:400; gap:10px; margin:0; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-page-size-control { background:var(--pm-control-surface) !important; border:1px solid var(--border); border-radius:8px; width:88px; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-page-size-control select { --filter-control-background:transparent; --filter-control-border:0; width:100% !important; height:42px !important; background:transparent !important; border:0 !important; padding-left:12px !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-page-size-control > i:not([data-filter-select-icon]) { display:none; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-sel-chev { transform:none !important; translate:0 -50%; rotate:0deg; transition:rotate .16s ease; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .is-open > .pm-sel-chev { rotate:180deg; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-applied-filters { grid-column:1/-1; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-applied-filters[hidden] { display:none !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-filter-chips { display:flex; flex-wrap:wrap; gap:8px; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .catalog-filter-chip { display:inline-flex; align-items:center; gap:8px; max-width:100%; padding:6px 10px; font-size:13px; border:1px solid var(--border); border-radius:999px; background:var(--surface-muted); overflow-wrap:anywhere; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .catalog-filter-chip button { display:grid; place-items:center; flex:none; width:24px; height:24px; border:0; border-radius:50%; background:transparent; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-actions [data-pm-clear-filters] { height:36px; padding:0 10px; border:1px solid transparent !important; border-radius:6px; color:var(--pm-clear-color) !important; background:transparent !important; font-size:14px; font-weight:400 !important; box-shadow:none !important; transform:none !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-actions [data-pm-clear-filters]:is(:hover,:focus-visible) { border-color:color-mix(in srgb,var(--pm-clear-color) 45%,transparent) !important; outline:none; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-case-row,.pm-trans-row) { min-height:92px; padding:20px 24px; column-gap:24px; row-gap:14px; background:transparent !important; transform:none !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-case-row,.pm-trans-row):hover { background:color-mix(in srgb,var(--brand) 8%,transparent) !important; box-shadow:none !important; outline:none !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-history-row { position:relative; cursor:pointer; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-history-row::before { content:""; position:absolute; inset-block:12px; inset-inline-start:0; width:3px; border-radius:0 3px 3px 0; background:transparent; pointer-events:none; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-history-row.is-active { background:color-mix(in srgb,var(--brand) 8%,transparent) !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-history-row.is-active::before { background:var(--brand); }
    html[data-theme='dark'] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-history-row.is-active { background:color-mix(in srgb,var(--brand) 13%,transparent) !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-case-row,.pm-trans-row):focus-visible { background:color-mix(in srgb,var(--brand) 20%,transparent) !important; outline:none !important; box-shadow:none !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-row-title,.pm-trans-title,.pm-case) { font-size:15px !important; font-weight:400 !important; color:var(--ink) !important; overflow-wrap:anywhere; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-row-meta,.pm-trans-meta,.pm-case-recorder,.pm-row-date) { font-size:13px !important; font-weight:400 !important; color:var(--ink-muted) !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-txn-rec-label,.pm-summary-stat > span,.pc-compare-head) { background:transparent !important; font-size:13px !important; font-weight:600 !important; letter-spacing:.055em; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-row-actions { gap:4px; flex-wrap:wrap; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-row-actions > .pm-case-icon-action { width:40px !important; min-width:40px !important; max-width:40px !important; height:40px !important; min-height:40px !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-row-actions > .pm-case-icon-action > i { font-size:18px !important; line-height:1; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-case-icon-action { display:inline-flex; align-items:center; justify-content:center; position:relative; padding:0 !important; width:44px; height:44px; min-width:44px; transition:color .16s ease !important; border:0 !important; background:transparent !important; box-shadow:none !important; color:var(--ink) !important; --pm-action-color:var(--pm-view-color); transform:none !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-case-icon-action:is([data-pm-summary-toggle],.pm-print-action,.pm-case-history-print) { --pm-action-color:var(--pm-info-color); }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-case-icon-action:is(:hover,:focus-visible) > i { color:var(--pm-action-color) !important; filter:none !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-case-icon-action:focus-visible { outline:2px solid currentColor; outline-offset:2px; }
    html[data-theme] body.panel-shell-body .pm-print-action.pm-case-icon-action { display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; padding:0; border:0; background:transparent !important; color:var(--ink) !important; --pm-action-color:#175CD3; border-radius:6px; text-decoration:none; box-shadow:none !important; }
    html[data-theme='dark'] body.panel-shell-body .pm-print-action.pm-case-icon-action { --pm-action-color:#84B8FF; }
    html[data-theme] body.panel-shell-body .pm-print-action.pm-case-icon-action:is(:hover,:focus-visible) > i { color:var(--pm-action-color) !important; filter:none !important; }
    html[data-theme] body.panel-shell-body .pm-print-action.pm-case-icon-action:focus-visible { outline:2px solid currentColor; outline-offset:2px; }
    html[data-theme] .pm-date-popover { position:fixed; inset:auto; margin:0; width:min(352px,calc(100vw - 32px)); max-height:calc(100dvh - 32px); padding:0; border:1px solid var(--border); border-radius:10px; background:var(--filter-ui-menu,var(--card)); color:var(--ink); overflow:auto; }
    html[data-theme] .pm-date-popover .pm-modal { width:100%; max-height:none; border:0; border-radius:0; box-shadow:none; background:var(--filter-ui-menu,var(--card)); }
    html[data-theme] .pm-date-popover .pm-control { height:44px; border:1px solid var(--border); border-radius:8px; background:var(--filter-ui-menu,var(--card)); color:var(--ink); font-size:14px; }
    html[data-theme] .pm-date-popover::backdrop { background:transparent; pointer-events:none; }
    @media(max-width:1100px) {
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar { grid-template-columns:160px 160px minmax(170px,1fr); }
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-field.search { grid-column:1/-1; }
    }
    @media(max-width:600px) {
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar { grid-template-columns:minmax(0,1fr) minmax(0,1fr); }
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-more-filters,.pm-sort-control) { width:100%; }
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-page-size { grid-column:1/-1; justify-content:space-between; }
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-more-filters > summary { width:100%; }
    }
    @media(prefers-reduced-motion:reduce) {
        html[data-theme] body.panel-shell-body .pm-consistent-ui .pm-sel-chev,
        html[data-theme] body.panel-shell-body .pm-consistent-ui .pm-more-filters summary > i { transition:none; }
    }
    @media print { .pm-date-popover { display:none !important; } }
    html[data-theme] body.panel-shell-body .pm-consistent-ui .pm-sort-control .filter-select-menu { width:280px !important; min-width:280px !important; max-width:calc(100vw - 32px); right:0; left:auto; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui .pm-sort-control .filter-select-menu__item { grid-template-columns:20px minmax(0,1fr) 18px; white-space:nowrap; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-case-icon-action .pm-action-tooltip { display:block !important; top:auto !important; right:auto !important; left:50% !important; bottom:calc(100% + 6px) !important; transform:translateX(-50%) !important; z-index:30; filter:none !important; font-weight:400; }
body:has(.pm-consistent-ui) .panel-page-footer { display:none; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-field.search.shared-record-search { border-radius:14px !important; background-clip:padding-box; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page > .pm-foot { position:static; margin-top:12px; min-height:64px; padding:12px 16px; border:1px solid var(--border) !important; border-radius:6px; background:var(--card) !important; }
    html[data-theme] body.panel-shell-body .pm-print-action.pm-case-icon-action { width:auto; min-width:44px; padding:0 12px !important; gap:8px; font-size:14px; font-weight:400; }
    html[data-theme] body.panel-shell-body .page-content .pm-consistent-ui.payment-monitoring-page.pm-page #pmFilterForm .pm-field.search,
    html[data-theme] body.panel-shell-body .page-content .pm-consistent-ui.payment-monitoring-page.pm-page #pmFilterForm .pm-field.search > input { border-radius:14px !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-case-item,.pm-case-row,.pm-row-actions,.pm-row-list,.pm-records-body) { overflow:visible !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-status,
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-status { background:transparent !important; border:0 !important; border-radius:0 !important; padding:0 !important; box-shadow:none !important; }
    html[data-theme] body.panel-shell-body .pm-print-action.pm-case-icon-action:is(:hover,:focus-visible) > span { color:var(--pm-action-color) !important; }
    .pm-transactions-modal [hidden] { display:none !important; }
    .pm-transactions-modal .pm-modal-body { min-height:0; overflow:auto; }
    .pm-transactions-modal .pm-modal-hd,.pm-transactions-modal .pm-modal-ft { flex-shrink:0; }
    .pm-history-scroll { width:100%; min-width:0; overflow-x:hidden; }
    .pm-history-table { width:100%; min-width:0; table-layout:fixed; border-collapse:collapse; font-size:14px; }
    .pm-history-table :is(th,td) { padding:14px 16px; vertical-align:middle; text-align:left; border-bottom:1px solid var(--border); }
    .pm-history-table th { font-size:13px; font-weight:600; letter-spacing:.055em; background:transparent; }
    .pm-history-table .pm-money { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .pm-transactions-modal .pm-case-overview { gap:10px; margin-bottom:20px; }
    .pm-transactions-modal .pm-summary-stat { padding:10px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal { width:min(960px,calc(100vw - 32px)) !important; max-width:960px !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal-body { width:100%; box-sizing:border-box; min-width:0; overflow-x:hidden; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-case-overview { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); width:100%; min-width:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-summary-stat { min-width:0 !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table :is(thead,tr,th) { background:transparent !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal-ft { justify-content:space-between; }
    @media(max-width:700px) { html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-case-overview { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal-body { padding:24px !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-section-heading { margin:0 0 16px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-case-overview { gap:16px; margin-bottom:28px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-summary-stat { padding:16px !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-summary-stat strong { margin-top:10px; }
    /* Payment history is a full-height detail drawer, not a centered modal or
       a second row-level summary expansion. */
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop {
        align-items:stretch !important;
        justify-content:flex-end !important;
        padding:0 !important;
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop:is(.is-opening,.is-closing) { display:flex !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-transactions-modal {
        --pm-drawer-gutter:24px;
        width:min(600px,100vw) !important;
        max-width:min(600px,100vw) !important;
        height:100dvh !important;
        max-height:100dvh !important;
        margin:0 !important;
        border-width:0 0 0 1px !important;
        border-radius:0 !important;
        box-shadow:none !important;
        transform:translateX(100%);
        transition:transform .26s cubic-bezier(.22,1,.36,1);
        will-change:transform;
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop.open .pm-transactions-modal {
        transform:translateX(0);
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal-hd {
        padding:20px var(--pm-drawer-gutter) 18px !important;
        align-items:flex-start !important;
        gap:18px;
        background:linear-gradient(135deg,color-mix(in srgb,var(--brand) 11%,var(--card)) 0%,var(--card) 72%) !important;
        border-bottom:1px solid color-mix(in srgb,var(--brand) 24%,var(--border)) !important;
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-drawer-heading { min-width:0; flex:1 1 auto; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-drawer-title-row { display:flex; align-items:center; gap:12px; min-width:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-drawer-title-icon { display:inline-flex; align-items:center; justify-content:center; flex:0 0 42px; width:42px; height:42px; border:1px solid color-mix(in srgb,var(--brand) 32%,var(--border)); border-radius:12px; background:color-mix(in srgb,var(--brand) 13%,var(--card)); color:var(--brand); font-size:19px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal-title {
        font-size:20px !important;
        font-weight:650 !important;
        letter-spacing:-.015em;
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-drawer-subtitle { margin:3px 0 0; color:var(--ink-muted); font-size:13px; line-height:1.5; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-drawer-close { flex:0 0 40px; width:40px; height:40px; padding:0 !important; border-radius:10px !important; background:color-mix(in srgb,var(--card) 78%,transparent) !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal-context {
        margin-top:16px !important;
        padding:14px 16px;
        border:1px solid var(--border);
        border-radius:12px;
        background:color-mix(in srgb,var(--card) 82%,transparent);
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal-body {
        padding:24px var(--pm-drawer-gutter) !important;
        background:color-mix(in srgb,var(--surface-muted) 28%,var(--card)) !important;
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-summary-grid {
        gap:12px !important;
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-summary-stat {
        border-radius:12px !important;
        background:var(--surface-muted) !important;
        box-shadow:none !important;
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal-ft {
        padding:14px 24px !important;
        border-top:1px solid color-mix(in srgb,var(--brand) 22%,var(--border)) !important;
        background:linear-gradient(180deg,var(--card),color-mix(in srgb,var(--brand) 7%,var(--card))) !important;
        box-shadow:none !important;
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-drawer-footer-actions { display:flex; align-items:center; justify-content:space-between; gap:12px; width:100%; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table th:nth-child(1) { width:22%; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table th:nth-child(2) { width:22%; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table th:nth-child(3) { width:15%; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table th:nth-child(4) { width:23%; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table th:nth-child(5) { width:18%; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table :is(th,td) { min-width:0; padding:12px 8px; overflow-wrap:anywhere; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table .pm-money { white-space:normal; }
    @media (max-width:767px) {
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-transactions-modal {
            --pm-drawer-gutter:18px;
            width:100vw !important;
            max-width:100vw !important;
            border-left:0 !important;
            border-radius:0 !important;
        }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop :is(.pm-modal-hd,.pm-modal-body,.pm-modal-ft) { padding-inline:var(--pm-drawer-gutter) !important; }
    }
    @media (max-width:520px) {
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table thead { display:none; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table :is(tbody,tr) { display:block; width:100%; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table tr { margin-bottom:12px; padding:10px 12px; border:1px solid var(--border); border-radius:12px; background:var(--card); }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table td:not([hidden]) { display:grid; grid-template-columns:minmax(100px,38%) minmax(0,1fr); gap:12px; width:100%; padding:8px 0; border-bottom:1px solid color-mix(in srgb,var(--border) 65%,transparent); text-align:left; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table td[data-label='Actions'] { border-bottom:0; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table td:not([hidden])::before { content:attr(data-label); color:var(--ink-muted); font-size:12px; font-weight:600; letter-spacing:.035em; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table td.pm-empty { display:block; padding:18px 4px; border:0; text-align:center; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-table td.pm-empty::before { display:none; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-history-actions { justify-content:flex-start; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-drawer-footer-actions { flex-direction:column-reverse; align-items:stretch; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-drawer-footer-actions > * { width:100% !important; justify-content:center; }
    }
    @media (prefers-reduced-motion:reduce) {
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-transactions-modal { transition:none !important; transform:none !important; }
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-txn-det-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:24px 32px; margin-bottom:24px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-txn-det-cell { display:flex; flex-direction:column; align-items:flex-start; gap:8px; min-width:0; padding:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-txn-det-cell strong { font-size:14px; line-height:1.6; font-weight:400; white-space:normal; overflow-wrap:anywhere; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-txn-det-cell > span { font-size:12px; line-height:1.5; color:var(--ink-muted); }
    .pm-transactions-modal :is([data-pm-history-footer],[data-pm-detail-footer]) { display:flex; align-items:center; justify-content:space-between; width:100%; gap:16px; flex-wrap:wrap; }
    .pm-history-actions { display:flex; align-items:center; gap:8px; }
    .pm-modal-icon { width:40px; height:40px; display:inline-flex; justify-content:center; align-items:center; border:0; border-radius:6px; background:transparent; color:var(--ink); text-decoration:none; }
    .pm-modal-icon:is(:hover,:focus-visible) { color:#175CD3; background:transparent; }
    html[data-theme='dark'] .pm-modal-icon:is(:hover,:focus-visible) { color:#84B8FF; }
    .pm-modal-icon:focus-visible { outline:2px solid currentColor; outline-offset:2px; }
    @media(max-width:600px) { html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-txn-det-grid { grid-template-columns:minmax(0,1fr); gap:20px; } }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-detail-group-title { margin:24px 0 8px; font-size:14px; font-weight:600; color:var(--ink); }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-detail-group-title:first-child { margin-top:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-txn-det-grid { grid-template-columns:minmax(0,1fr); gap:0; margin:0 0 24px; padding-bottom:16px; border-bottom:1px solid var(--border); }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-txn-det-cell { display:grid; grid-template-columns:220px minmax(0,1fr); align-items:baseline; gap:20px; padding:8px 0; border:0; background:transparent; border-radius:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-txn-det-cell > span { position:relative; padding-right:16px; font-size:14px; font-weight:400; text-transform:none; letter-spacing:normal; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-txn-det-cell > span::after { content:':'; position:absolute; right:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-txn-det-cell > strong { margin:0; font-size:15px; line-height:1.5; }
    @media(max-width:600px) { html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-txn-det-cell { grid-template-columns:minmax(0,42%) minmax(0,1fr); gap:12px; } }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-txn-det-grid:last-of-type { border-bottom:0; padding-bottom:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-txn-det-cell > .pm-status { display:block !important; justify-self:start; text-align:left !important; justify-content:flex-start !important; width:auto !important; min-width:0 !important; min-height:0; margin:0 !important; letter-spacing:normal; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal-hd > div { flex:1; min-width:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal-hd { align-items:flex-start; gap:20px; padding:20px var(--pm-drawer-gutter); }
    .pm-modal-context { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:14px 28px; margin:16px 0 0; }
    .pm-modal-context > div { display:grid; grid-template-columns:64px minmax(0,1fr); gap:10px; align-items:baseline; }
    .pm-modal-context dt { color:var(--ink-muted); font-size:12px; font-weight:400; line-height:1.6; }
    .pm-modal-context dd { display:flex; flex-wrap:wrap; gap:4px 10px; margin:0; color:var(--ink); font-size:14px; font-weight:400; line-height:1.6; overflow-wrap:anywhere; }
    .pm-modal-context .pm-context-code { font-weight:600; }
    @media(max-width:600px) { .pm-modal-context { grid-template-columns:minmax(0,1fr); gap:10px; } }
    .pm-modal-icon .pm-action-tooltip { display:none; position:fixed; padding:6px 10px; border:0; border-radius:6px; background:var(--ink); color:var(--card); font-size:12px; font-weight:400; line-height:1.4; white-space:nowrap; pointer-events:none; box-shadow:none; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop [data-pm-detail-host] .pm-detail-group-title { font-size:15px; line-height:1.5; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-txn-det-cell > :is(span,strong) { font-size:16px !important; line-height:1.5 !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-detail-group-title { font-size:17px !important; line-height:1.5; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-title { font-size:22px !important; line-height:1.4; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-context :is(dt,dd) { font-size:15px !important; }
.pm-transactions-modal [data-pm-history-title][hidden] + .pm-modal-context { margin-top:0; }
    .pm-modal-context > div { grid-template-columns:96px minmax(0,1fr); gap:14px; }
    .pm-modal-context dt { position:relative; padding-right:16px; }
    .pm-modal-context dt::after { content:':'; position:absolute; right:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal { box-sizing:border-box !important; position:fixed !important; inset:0 0 0 auto !important; z-index:1; width:min(600px,100vw) !important; min-width:0 !important; max-width:min(600px,100vw) !important; height:100dvh !important; max-height:100dvh !important; min-height:100dvh !important; margin:0 !important; border-radius:0 !important; box-shadow:none !important; display:flex; flex-direction:column; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal :is(.pm-modal-hd,.pm-modal-body,.pm-modal-ft,.pm-modal-context,.pm-summary-stat,.pm-txn-card,.pm-history-table tr) { box-shadow:none !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-body { flex:1 1 0; min-height:0; overflow-y:auto; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-ft { box-sizing:border-box !important; flex:0 0 auto; min-height:76px; padding-top:16px !important; padding-right:max(var(--pm-drawer-gutter),env(safe-area-inset-right)) !important; padding-bottom:max(16px,env(safe-area-inset-bottom)) !important; padding-left:var(--pm-drawer-gutter) !important; align-items:center !important; overflow:visible !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-drawer-footer-actions { box-sizing:border-box; min-width:0; width:100%; padding:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-drawer-footer-actions > :is(a,button) { flex:0 0 auto; min-height:42px; margin:0 !important; }
    /* Final drawer rhythm owner: keep context, summary and ledger aligned and
       readable inside the fixed 600px surface. */
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-context { grid-template-columns:repeat(2,minmax(0,1fr)); column-gap:20px; row-gap:12px; margin-top:14px !important; padding:14px 16px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-context > div { grid-template-columns:78px minmax(0,1fr); gap:10px; align-items:baseline; min-width:0; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-context :is(dt,dd) { min-width:0; font-size:14px !important; line-height:1.5; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-context dd { gap:3px 8px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-section-heading { margin:0 0 12px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-case-overview { gap:10px; margin-bottom:18px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-summary-stat { padding:14px !important; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-summary-stat strong { margin-top:6px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-history-scroll { margin-top:2px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-history-table :is(th,td) { padding:12px 7px; line-height:1.5; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-history-table th { font-size:12px; letter-spacing:.04em; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-history-actions { gap:6px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-detail-group-title { margin:20px 0 8px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-txn-det-grid { margin-bottom:18px; padding-bottom:14px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-txn-det-cell { grid-template-columns:160px minmax(0,1fr); gap:16px; padding:7px 0; }
    @media(max-width:600px) { .pm-modal-context > div { grid-template-columns:88px minmax(0,1fr); gap:12px; } }
    @media(max-width:600px) {
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-context { grid-template-columns:minmax(0,1fr); gap:10px; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-context > div { grid-template-columns:82px minmax(0,1fr); gap:10px; }
        html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-txn-det-cell { grid-template-columns:minmax(0,40%) minmax(0,1fr); gap:12px; }
    }
    .pm-modal-icon[data-pm-history-detail]:is(:hover,:focus-visible) { color:#1B4332; }
    html[data-theme='dark'] .pm-modal-icon[data-pm-history-detail]:is(:hover,:focus-visible) { color:#5CE0A0; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-page-size { display:flex; align-items:center; gap:12px; white-space:nowrap; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-page-size-control { position:relative; flex:0 0 104px; width:104px; min-width:104px; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-page-size-control select { padding:0 36px 0 14px !important; box-sizing:border-box; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-page-size-control .filter-select-menu { left:auto; right:0; min-width:160px !important; width:160px; padding:6px; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-page-size-control .filter-select-menu__item { display:flex; align-items:center; justify-content:space-between; gap:16px; min-height:40px; padding:10px 12px; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-row-actions,.pm-trans-side) { gap:12px; min-width:0; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-row-date { white-space:normal; line-height:1.6; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-status { margin:0; white-space:nowrap; letter-spacing:.03em; }
    html[data-theme='dark'] body.panel-shell-body :is(.pm-consistent-ui.payment-monitoring-page.pm-page,.pm-transactions-backdrop) .pm-status.is-paid { color:#86EFAC !important; }
    html[data-theme='dark'] body.panel-shell-body :is(.pm-consistent-ui.payment-monitoring-page.pm-page,.pm-transactions-backdrop) .pm-status.is-partial { color:#FCD34D !important; }
    html[data-theme='dark'] body.panel-shell-body :is(.pm-consistent-ui.payment-monitoring-page.pm-page,.pm-transactions-backdrop) .pm-status.is-unpaid { color:#FCA5A5 !important; }
    @media(min-width:1301px) {
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-history-row { grid-template-columns:minmax(130px,.75fr) minmax(260px,1.5fr) minmax(220px,1fr) minmax(160px,.6fr) !important; column-gap:20px !important; }
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-trans-row { grid-template-columns:140px minmax(220px,1.1fr) minmax(210px,.9fr) 96px 156px !important; }
    }
    @media(min-width:641px) and (max-width:1300px) {
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-case-row,.pm-trans-row) { grid-template-columns:120px minmax(0,1fr) !important; }
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-row-date,.pm-row-actions,.pm-trans-side) { grid-column:2; justify-content:flex-start; }
    }
    @media(max-width:640px) {
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-case-row,.pm-trans-row) { grid-template-columns:minmax(0,1fr) !important; padding:18px 16px; row-gap:12px; }
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-row-actions,.pm-trans-side) { justify-content:flex-start; flex-wrap:wrap; }
    }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-row-date { min-width:0; font-family:inherit !important; }
    .pm-row-date .pm-date-label { display:block; margin-bottom:4px; font-size:12px; line-height:1.5; }
    .pm-row-date .pm-date-value { display:block; white-space:nowrap; line-height:1.6; color:var(--ink); }
    .pm-record-head { display:none; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-case-row,.pm-trans-row) > :is(.pm-status,.pm-status-stack,.pm-history-view-cell) { justify-self:start; }
    @media(min-width:1301px) {
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-record-head { display:grid; grid-template-columns:140px minmax(220px,1.1fr) minmax(210px,.9fr) 96px 156px; column-gap:24px; padding:16px 24px; border-bottom:1px solid var(--border); background:transparent; color:var(--ink); font-size:13px; font-weight:600; letter-spacing:.055em; }
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-record-head.pm-record-head--no-actions { grid-template-columns:minmax(130px,.75fr) minmax(260px,1.5fr) minmax(220px,1fr) minmax(160px,.6fr); column-gap:20px; }
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-date-label { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; }
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-row-actions,.pm-trans-side) { justify-content:flex-start; }
        .pm-trans-side .pm-expand-label { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; }
    }
    @media(min-width:641px) and (max-width:1300px) {
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-case-row,.pm-trans-row) > :is(.pm-status,.pm-status-stack,.pm-history-view-cell) { grid-column:2; }
    }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page :is(.pm-case-row,.pm-trans-row) { border:0 !important; box-sizing:border-box; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-record-head { box-sizing:border-box; border:1px solid transparent !important; border-bottom-color:var(--border) !important; background:var(--card) !important; }
    .pm-filter-apply { flex:none; position:relative; bottom:auto; padding:12px; margin:0; border-top:1px solid var(--border); background:var(--pm-control-surface); display:flex; justify-content:flex-end; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page #pmFilterForm .pm-field.search > input { padding-right:128px !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page #pmFilterForm .pm-search-clear { right:92px !important; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page #pmFilterForm .pm-search-button { position:absolute; top:0; bottom:0; right:0; width:84px; border:0; border-left:1px solid var(--border); border-radius:0 13px 13px 0; background:var(--pm-control-surface); color:var(--ink); font-size:14px; font-weight:500; cursor:pointer; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page #pmFilterForm .pm-search-button:hover { background:var(--surface-muted); }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page #pmFilterForm .pm-search-button:focus-visible { outline:2px solid var(--brand); outline-offset:-3px; }
    html[data-theme] body.panel-shell-body .pm-consistent-ui .pm-search-matches { position:absolute; top:calc(100% + 6px); left:0; width:100%; box-sizing:border-box; z-index:50; display:grid; max-height:280px; overflow:auto; padding:6px; border:1px solid var(--border); border-radius:10px; background:var(--pm-control-surface); }
    html[data-theme] body.panel-shell-body .pm-consistent-ui .pm-search-matches[hidden] { display:none !important; }
    .pm-search-match { display:flex; flex-direction:row; align-items:center; gap:10px; width:100%; padding:10px 12px; border:0; border-radius:6px; background:transparent; color:var(--ink); text-align:left; line-height:1.5; cursor:pointer; }
    .pm-search-match--status { margin:0; color:var(--muted); cursor:default; }
    .pm-search-match:hover { background:var(--surface-muted); }
    .pm-search-match:focus-visible { outline:2px solid var(--brand); outline-offset:-2px; }
    .pm-match-code { flex:none; font-size:14px; font-weight:600; }
    .pm-match-name { display:flex; align-items:center; gap:10px; flex:0 1 auto; min-width:0; font-size:13px; font-weight:400; white-space:nowrap; overflow:hidden; }
.pm-match-name::before { content:"·"; flex:none; color:var(--ink-muted); }
.pm-match-name > span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pm-match-reference { display:block; flex:1 1 16rem; min-width:0; overflow:hidden; color:var(--ink-muted); font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
.pm-search-match mark { background:transparent; color:inherit; font-weight:inherit; text-decoration:underline; text-underline-offset:3px; text-decoration-thickness:1px; }
    @media(min-width:1201px) {
        html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page #pmFilterForm .pm-field.search.shared-record-search { width:min(560px,calc(100% - 24px)) !important; max-width:560px !important; justify-self:start; }
    }
    html[data-theme] body.panel-shell-body .pm-consistent-ui.payment-monitoring-page.pm-page .pm-filter-head { flex:none; padding:12px; border-bottom:1px solid var(--border); }
    .pm-filter-fields { display:flex; flex-direction:column; gap:8px; min-height:0; overflow:auto; padding:12px; }
</style>

<style>
html[data-theme] body.panel-shell-body .pm-modal-icon[data-pm-history-detail] { color:var(--record-view-ink,var(--ink)); }
</style>

<style>
/* Keep the outer hover stroke inside the panel's rounded clipping boundary. */
html[data-theme] body.panel-shell-body .payment-monitoring-page .pm-records-body { padding:var(--record-row-outline-width) !important; box-sizing:border-box; }

</style>
@include('staff.payments._ledger_styles')
<style>
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-detail-group-title { padding:8px 12px; border-radius:7px; background:color-mix(in srgb,var(--brand) 12%,var(--card)); }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-txn-det-cell { display:grid; grid-template-columns:minmax(0, 38%) minmax(0, 1fr); align-items:baseline; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-txn-det-cell > span { white-space:normal; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-reference-value { display:grid; grid-template-columns:minmax(0, 1fr) auto; align-items:baseline; gap:8px; min-width:0; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-reference-value > strong { margin:0; font-size:14px !important; overflow-wrap:anywhere; min-width:0; }
.pm-reference-value > a { display:inline-flex; align-items:center; gap:6px; white-space:nowrap; font-size:13px; font-weight:400; color:var(--ink); text-underline-offset:3px; }
.pm-reference-value > a:hover { color:var(--brand); }
.pm-reference-value > small { font-size:12px; font-weight:400; color:var(--ink-muted); }
@media(max-width:600px) {
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-txn-det-cell { grid-template-columns:minmax(0, 35%) minmax(0, 1fr); gap:10px; }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-payment-companion [data-pm-detail-host] .pm-txn-det-cell > span { font-size:14px !important; }
}
</style>

<style>
.pm-detail-actions .pm-correction-action {
    display:inline-flex; align-items:center; gap:8px; padding:9px 12px;
    background:var(--surface-muted); color:var(--ink); border-radius:8px; font-size:13px; font-weight:650;
}
.pm-detail-actions .pm-correction-action:hover { text-decoration:underline; }
.pm-correction-history { flex:0 0 100%; min-width:0; margin-top:12px; }
.pm-correction-proof {
    display:flex; align-items:baseline; gap:8px; margin:12px 0;
    color:var(--ink-muted); font-size:13px; line-height:1.5;
}
.pm-correction-proof span { min-width:0; overflow-wrap:anywhere; }
</style>
