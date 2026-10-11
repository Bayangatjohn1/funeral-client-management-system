<style>
    @media(min-width:1201px) {
        html[data-theme] body.panel-shell-body .records-page .table-system-toolbar .case-record-filters { display:grid !important; grid-template-columns:minmax(0,1.1fr) minmax(180px,1fr) minmax(160px,1fr) auto auto !important; }
        html[data-theme] body.panel-shell-body .records-page .table-system-toolbar .case-record-filters:has(.case-compact-actions) { grid-template-columns:minmax(0,1.1fr) minmax(180px,1fr) minmax(160px,1fr) auto auto auto !important; }
        html[data-theme] body.panel-shell-body .records-page .table-system-toolbar .case-record-filters :is(.case-compact-search-row,.case-compact-filter-bar) { display:contents !important; }
        html[data-theme] body.panel-shell-body .records-page .table-system-toolbar .case-compact-search-field { grid-area:1 / 1 !important; min-width:0 !important; width:100% !important; }
        html[data-theme] body.panel-shell-body .records-page .table-system-toolbar .case-compact-date-filter { grid-area:1 / 2 !important; width:100% !important; }
        html[data-theme] body.panel-shell-body .records-page .table-system-toolbar .case-compact-sort-filter { grid-area:1 / 3 !important; width:100% !important; }
        html[data-theme] body.panel-shell-body .records-page .table-system-toolbar .case-compact-more { grid-area:1 / 4 !important; }
        html[data-theme] body.panel-shell-body .records-page .table-system-toolbar .case-rows-per-page { grid-area:1 / 5 !important; }
        html[data-theme] body.panel-shell-body .records-page .table-system-toolbar .case-compact-actions { grid-area:1 / 6 !important; }
    }
    .case-record-filters .case-compact-filter-bar > .case-compact-actions { flex:0 0 auto; margin:0; }
    @media(min-width:1201px) {
        html[data-theme] body.panel-shell-body.branch-admin-ui .master-records-page .admin-table-toolbar .case-compact-filter:has(.case-compact-actions) { grid-template-columns:minmax(0,1.1fr) minmax(0,1fr) minmax(0,1fr) auto auto auto !important; }
    }
    @media(max-width:767px) { .case-record-filters .case-compact-filter-bar > .case-compact-actions { order:11; justify-content:flex-end; } }
    /* Keep the table's scroll wrapper, without nested card surfaces around it. */
    html body :is(.records-page, .master-records-page) :is(.table-system-card, .table-system-list) {
        background:transparent !important;
        border:0 !important;
        border-radius:0 !important;
        box-shadow:none !important;
        padding:0 !important;
    }
    html body :is(.records-page, .master-records-page) .table-system-list > .table-system-wrap {
        margin:0 !important;
        border:1px solid var(--records-border, var(--border)) !important;
        border-radius:8px !important;
        box-shadow:none !important;
    }
    html body :is(.records-page, .master-records-page) .case-records-tabs-row {
        width:100% !important;
        box-sizing:border-box;
        margin:0 0 12px !important;
        justify-content:flex-start !important;
    }
    html body :is(.records-page, .master-records-page) .case-records-tabs {
        margin:0 !important;
        justify-content:flex-start !important;
        gap:8px !important;
    }
    html body :is(.records-page, .master-records-page) .case-records-tabs-row + .table-system-list {
        margin-inline:0 !important;
        padding-inline:0 !important;
        width:100% !important;
        box-sizing:border-box;
    }
    html body :is(.records-page, .master-records-page) .case-records-tabs-row + .table-system-list > .table-system-wrap {
        margin-inline:0 !important;
        width:100% !important;
        box-sizing:border-box;
    }
    html body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab {
        background:transparent !important;
        border:0 !important;
        border-bottom:2px solid transparent !important;
        border-radius:0 !important;
        box-shadow:none !important;
        transform:none !important;
    }
    html body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab-active {
        border-bottom-color:var(--records-text, var(--brand, #24513e)) !important;
        color:var(--records-text, var(--ink)) !important;
    }
    html body :is(.records-page, .master-records-page) :is(.case-records-tabs-row, .case-records-tabs) {
        background:transparent !important;
        border:0 !important;
        border-radius:0 !important;
        box-shadow:none !important;
        padding:0 !important;
    }
    .records-page .case-records-top-wrapper {
        display:block !important;
    }
    :is(.records-page, .master-records-page) .case-record-filters .case-compact-advanced[popover] {
        position:fixed !important; inset:0 !important; width:100vw !important; height:100dvh !important;
        max-width:none !important; max-height:none !important; margin:0 !important; padding:0 !important;
        border:0 !important; overflow:visible !important; color:inherit;
    }
    .case-record-filters .case-compact-advanced::backdrop { background:transparent; }
    .case-record-filters :is(select, input[type='date'], [data-case-more-toggle], [data-case-more-dismiss], [data-case-more-reset]) { cursor:pointer !important; }
    .case-record-filters input[type='date']::-webkit-calendar-picker-indicator { cursor:pointer; }
    .case-record-filters {
        --record-control-height:44px;
        --record-control-bg:var(--records-card-alt, var(--card));
    }
    /* One surface and one height for every toolbar control, including search. */
    :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters :is(
        .case-compact-search-control > input,
        .case-compact-filter-bar > .case-compact-branch,
        .case-compact-filter-bar > .case-compact-seg,
        .case-compact-filter-bar > .case-compact-more,
        .case-rows-per-page > select
    ) {
        box-sizing:border-box !important;
        height:var(--record-control-height) !important;
        min-height:var(--record-control-height) !important;
        max-height:var(--record-control-height) !important;
        background-color:var(--record-control-bg) !important;
        border:1px solid var(--records-border, var(--border)) !important;
        border-radius:8px !important;
        color:var(--records-text, var(--ink)) !important;
        box-shadow:none !important;
        margin-top:0 !important;
        margin-bottom:0 !important;
    }
    :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters :is(
        .case-compact-search-control, .case-compact-search-field
    ) {
        height:var(--record-control-height) !important;
        min-height:var(--record-control-height) !important;
        align-self:center !important;
    }
    :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-filter-bar > :is(.case-compact-branch, .case-compact-seg) > select {
        box-sizing:border-box !important;
        height:100% !important;
        min-height:0 !important;
        max-height:100% !important;
        background-color:transparent !important;
        border:0 !important;
        box-shadow:none !important;
    }
    :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-more.active {
        border-color:var(--brand, #24513e) !important;
    }
    :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-filter-bar {
        display:flex !important; flex-wrap:wrap !important; align-items:center !important; gap:10px !important;
    }
    .case-record-filters .case-rows-per-page { display:flex; align-items:center; gap:10px; flex:0 0 auto; }
    .case-record-filters .case-rows-per-page label { white-space:nowrap; font-size:12px; font-weight:600; }
    .case-record-filters .case-rows-per-page select { width:76px; min-height:44px; }
    .case-page-size-control { position:relative; display:inline-flex; flex-shrink:0; }
    .case-record-filters .case-page-size-control select { appearance:none; padding-right:30px !important; }
    .case-page-size-chevron { position:absolute; right:12px; top:50%; transform:translateY(-50%); pointer-events:none; font-size:12px; color:var(--filter-ui-muted); transition:transform .18s ease; }
    .case-page-size-control:has(select:open) .case-page-size-chevron { transform:translateY(-50%) rotate(180deg); }
    .case-record-filters .case-applied-filters { display:flex; flex-wrap:wrap; gap:8px; grid-column:1/-1; width:100%; margin-top:10px; }
    :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-drawer {
        position:absolute; top:var(--filter-top, 100px); left:var(--filter-left, 16px);
        width:min(480px, calc(100vw - 32px)); height:auto; max-height:var(--filter-height, 80dvh);
        border:1px solid var(--records-border, var(--border)); border-radius:16px;
        background:var(--records-card, var(--card)); box-shadow:0 18px 60px #0003; overflow:hidden;
    }
    .case-record-filters .case-compact-drawer-backdrop { background:rgba(20,35,25,.12); }
    :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-advanced-head {
        padding:18px 20px; flex-shrink:0; background:var(--records-card, var(--card));
    }
    :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-advanced-grid {
        display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; padding:20px; min-height:0; overflow-y:auto;
    }
    .case-record-filters .case-compact-field { min-width:0; }
    .case-record-filters .case-interment-range { grid-column:1/-1; border:0; padding:0; margin:0; min-width:0; }
    .case-record-filters .case-interment-range legend { font-size:12px; font-weight:700; margin-bottom:10px; }
    .case-record-filters .case-interment-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .case-record-filters .case-interment-fields input { width:100%; min-width:0; }
    :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-advanced-actions {
        display:flex; flex-direction:row; justify-content:flex-end; gap:10px; margin:0; padding:14px 20px;
        flex-shrink:0; border-top:1px solid var(--records-border, var(--border)); background:var(--records-card, var(--card));
    }
    .case-record-filters .case-compact-advanced-clear { min-height:44px; padding:0 18px; border-radius:8px; border:1px solid var(--border); }
    .case-record-filters :is(input,select,button):focus-visible { outline:2px solid var(--brand, #24513e) !important; outline-offset:3px; }
    @media(min-width:768px) {
        /* Sidebar changes shrink columns instead of moving controls to another row. */
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters {
            display:grid !important;
            grid-template-columns:minmax(160px, 1fr) minmax(0, 2.8fr) !important;
            align-items:center !important;
            gap:10px !important;
            width:100% !important;
            min-width:0 !important;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-search-row {
            display:flex !important;
            grid-column:1 !important; grid-row:1 !important;
            align-items:center !important;
            width:100% !important; min-width:0 !important; gap:8px !important;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-search-field {
            flex:1 1 0 !important; width:100% !important; min-width:0 !important; max-width:none !important;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-filter-bar {
            display:flex !important; flex-wrap:nowrap !important;
            grid-column:2 !important; grid-row:1 !important;
            width:100% !important; min-width:0 !important;
            align-items:center !important; gap:8px !important;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-filter-bar > :is(.case-compact-branch, .case-compact-seg) {
            flex:1 1 0 !important; width:0 !important; min-width:0 !important; max-width:none !important;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-filter-bar select {
            text-overflow:ellipsis; white-space:nowrap;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-more {
            flex:0 0 auto !important; width:auto !important; min-width:0 !important;
            padding:0 10px !important; white-space:nowrap;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-rows-per-page {
            flex:0 0 auto !important; width:auto !important; gap:6px;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-rows-per-page select {
            width:88px !important; min-width:88px !important;
            padding-left:12px !important; padding-right:32px !important;
        }
        .case-record-filters .case-applied-filters { grid-row:2; }
    }
    @media(max-width:767px) {
        .records-page .case-records-top-wrapper .case-record-filters .case-compact-search-row,
        .records-page .case-records-top-wrapper .case-record-filters .case-compact-filter-bar {
            flex:0 0 auto !important; width:100% !important;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters { display:flex !important; flex-direction:column !important; gap:10px; width:100% !important; min-width:0; }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters :is(.case-compact-search-row,.case-compact-filter-bar) {
            display:flex !important; flex-direction:column !important; align-items:stretch !important; width:100% !important; gap:10px !important;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-filter-bar > *,
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-search-field {
            flex:0 0 auto !important; width:100% !important; min-width:0 !important; max-width:none !important; margin:0 !important;
        }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-rows-per-page { justify-content:space-between; order:10; }
        .case-record-filters .case-rows-per-page select { width:88px; }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-drawer {
            top:auto; bottom:0; left:0; width:100%; max-height:85dvh; border-radius:18px 18px 0 0;
        }
        .case-record-filters .case-compact-drawer-backdrop { background:rgba(20,35,25,.25); }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-advanced-grid { grid-template-columns:minmax(0,1fr); padding:16px; gap:14px; }
        :is(.records-page, .master-records-page) .table-system-toolbar .case-record-filters .case-compact-advanced-actions { padding:12px 16px max(12px,env(safe-area-inset-bottom)); }
        .case-record-filters .case-compact-advanced-actions > * { flex:1; justify-content:center; }
        :is(.records-page, .master-records-page) .case-records-tabs { display:flex !important; flex-wrap:nowrap !important; overflow-x:auto; width:100%; }
        :is(.records-page, .master-records-page) .case-records-tabs a { flex:1 0 auto !important; white-space:nowrap !important; padding:10px !important; word-break:normal !important; }
    }
    /* Only the Branch select paints a box; the wrapper positions its icons/menu. */
    html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-branch,
    html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-branch:is(:hover,:focus-within) {
        position:relative !important; padding:0 !important;
        background:transparent !important; background-color:transparent !important;
        border:0 !important; box-shadow:none !important;
    }
    html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-branch > select.case-compact-select,
    html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-branch > select.case-compact-select:is(:hover,:focus) {
        position:absolute !important; inset:0 !important;
        width:100% !important; min-width:0 !important; height:100% !important;
        padding:0 2.25rem 0 2.5rem !important;
        background:var(--record-control-bg) !important;
        background-color:var(--record-control-bg) !important;
        border:1px solid var(--records-border,var(--border)) !important;
        border-radius:8px !important; box-shadow:none !important;
    }
    html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-branch > .bi-building {
        position:absolute !important; left:14px !important; top:50% !important;
        transform:translateY(-50%) !important; z-index:1 !important; pointer-events:none !important;
    }
    html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-branch > .case-compact-select-chev {
        position:absolute !important; right:12px !important; top:50% !important;
        transform:translateY(-50%) !important; z-index:1 !important; pointer-events:none !important;
    }
    /* System Admin has six primary controls. Keep their order explicit. */
    @media(min-width:1201px) {
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar form.case-record-filters {
            display:grid !important;
            grid-template-columns:minmax(0,2fr) minmax(0,1.6fr) minmax(0,1.2fr) minmax(0,1.2fr) auto auto !important;
            grid-template-areas:none !important;
            align-items:center !important;
            width:100% !important;
        }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar form.case-record-filters:has(.case-compact-actions) {
            grid-template-columns:minmax(0,2fr) minmax(0,1.6fr) minmax(0,1.2fr) minmax(0,1.2fr) auto auto auto !important;
        }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-record-filters :is(.case-compact-search-row,.case-compact-filter-bar) { display:contents !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-search-field { grid-area:1 / 1 !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-branch { grid-area:1 / 2 !important; width:100% !important; min-width:0 !important; max-width:none !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-date-filter { grid-area:1 / 3 !important; width:100% !important; min-width:0 !important; max-width:none !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-sort-filter { grid-area:1 / 4 !important; width:100% !important; min-width:0 !important; max-width:none !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-more { grid-area:1 / 5 !important; width:auto !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-rows-per-page { grid-area:1 / 6 !important; width:auto !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-applied-filters { grid-area:2 / 1 / 3 / -1 !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-actions { grid-area:1 / 7 !important; margin-left:0 !important; }
    }
    @media(min-width:768px) and (max-width:1200px) {
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar form.case-record-filters {
            display:grid !important; grid-template-columns:repeat(3,minmax(0,1fr)) !important; grid-template-areas:none !important;
        }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-record-filters :is(.case-compact-search-row,.case-compact-filter-bar) { display:contents !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-search-field { grid-area:1 / 1 / 2 / 3 !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-branch { grid-area:1 / 3 !important; width:100% !important; min-width:0 !important; max-width:none !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-date-filter { grid-area:2 / 1 !important; width:100% !important; min-width:0 !important; max-width:none !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-sort-filter { grid-area:2 / 2 !important; width:100% !important; min-width:0 !important; max-width:none !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-more { grid-area:2 / 3 !important; width:100% !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-rows-per-page { grid-area:3 / 1 / 4 / 3 !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-applied-filters { grid-area:4 / 1 / 5 / -1 !important; }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-compact-actions { grid-area:3 / 3 !important; margin-left:0 !important; }
    }
    @media(max-width:767px) {
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar form.case-record-filters,
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-record-filters :is(.case-compact-search-row,.case-compact-filter-bar) {
            display:flex !important; flex-direction:column !important; align-items:stretch !important; width:100% !important;
        }
        html[data-theme] body.panel-shell-body.system-admin-ui .master-records-page .admin-table-toolbar .case-record-filters :is(.case-compact-search-field,.case-compact-branch,.case-compact-date-filter,.case-compact-sort-filter,.case-compact-more,.case-rows-per-page,.case-applied-filters,.case-compact-actions) {
            grid-area:auto !important; width:100% !important; min-width:0 !important; max-width:none !important;
        }
    }
</style>
