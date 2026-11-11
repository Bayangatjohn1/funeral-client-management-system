{{-- Shared layout ownership for the two case worklists, independent of role palette. --}}
<style>
    /* Case worklist table: quiet headers, readable regular text and a compact
       View + More-actions hierarchy. All columns stay in the scroller. */
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-wrap {
        border-radius: 6px !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table {
        min-width: 1280px !important;
        border-collapse: separate;
        border-spacing: 0;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table :is(thead, thead tr, thead th) {
        background: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table thead th {
        padding: 16px 14px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        letter-spacing: .055em !important;
        line-height: 1.5;
        color: var(--records-text, var(--ink)) !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody td {
        padding: 16px 14px !important;
        vertical-align: middle;
        font-size: 15px !important;
        font-weight: 400 !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody :is(.table-primary, .table-financial-total, .records-case-code, .status-badge) {
        font-size: 15px !important;
        font-weight: 400 !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody .records-person-names {
        white-space: normal !important;
        overflow-wrap: anywhere;
        line-height: 1.5;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table :is(.records-col-actions, .table-col-actions) {
        width: 112px !important;
        min-width: 112px;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody tr {
        background: transparent !important;
        border-left: 0 !important;
        box-shadow: none !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody tr:is(:hover,:focus-within) {
        background: transparent !important;
        outline: var(--record-row-outline-width) solid var(--record-row-outline) !important;
        outline-offset: 0 !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody tr:is(:hover,:focus-within) > td {
        background: transparent !important;
        box-shadow: none !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
        white-space: nowrap;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-icon-action {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        flex: 0 0 40px;
        width: 40px;
        height: 40px;
        border: 1px solid transparent !important;
        border-radius: 6px;
        background: transparent !important;
        color: var(--records-text, var(--ink)) !important;
        box-shadow: none !important;
        transform: none !important;
        text-decoration: none;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-icon-action:is(:hover,:focus-visible) {
        border-color: transparent !important;
        outline: none !important;
        background: transparent !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-icon-action > i {
        font-size: 18px !important;
        line-height: 1;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-icon-action:is(:hover,:focus-visible) > i {
        color: var(--case-action-color) !important;
        filter: none !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-action-view { --case-action-color:#1B4332; }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-action-edit { --case-action-color:#A15C00; }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-action-print { --case-action-color:#175CD3; }
    html[data-theme='dark'] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-action-view { --case-action-color:#5CE0A0; }
    html[data-theme='dark'] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-action-edit { --case-action-color:#FDC76A; }
    html[data-theme='dark'] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-action-print { --case-action-color:#84B8FF; }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .compact-action-item.case-record-action-edit { color:#A15C00 !important; }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .compact-action-item.case-record-action-print { color:#175CD3 !important; }
    html[data-theme='dark'] body.panel-shell-body :is(.records-page, .master-records-page) .compact-action-item.case-record-action-edit { color:#FDC76A !important; }
    html[data-theme='dark'] body.panel-shell-body :is(.records-page, .master-records-page) .compact-action-item.case-record-action-print { color:#84B8FF !important; }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-icon-action:focus-visible {
        outline: 2px solid var(--case-action-color) !important;
        outline-offset: -4px;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) {
        --case-tab-active-bg: #1B4332;
        --case-tab-active-text: #FFFFFF;
        --case-row-hover-border: #AEC7B7;
        --case-search-bg: #EAF2ED;
    }
    html[data-theme='dark'] body.panel-shell-body :is(.records-page, .master-records-page) {
        --case-tab-active-bg: #B5D6C4;
        --case-tab-active-text: #081D13;
        --case-row-hover-border: #41634F;
        --case-search-bg: #182D22;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) :is(.case-records-tabs-row, .case-records-tabs) {
        display: flex !important;
        justify-content: flex-start !important;
        flex-wrap: wrap;
        gap: 6px !important;
        padding: 0 !important;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: .875rem;
        justify-content: center;
        flex: 0 0 auto !important;
        width: auto !important;
        max-width: none !important;
        white-space: nowrap;
        min-height: 44px !important;
        padding: 10px 14px !important;
        border: 0 !important;
        border-radius: 12px !important;
        background: transparent !important;
        color: var(--reference-tab-muted) !important;
        font-weight: 600 !important;
        transform: none !important;
        box-shadow: none !important;
        transition: none !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab:not(.table-quick-tab-active):hover {
        background: var(--filter-ui-surface) !important;
        border-color: var(--filter-ui-border, var(--border)) !important;
        color: var(--filter-ui-text) !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab:hover:not(:focus-visible) {
        outline: none !important;
        box-shadow: none !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab-active,
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab-active:is(:hover,:focus-visible,:active) {
        background: var(--reference-action-bg) !important;
        border-color: var(--reference-action-bg) !important;
        color: var(--reference-action-text) !important;
        font-weight: 600 !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab:focus-visible {
        outline: 2px solid var(--filter-ui-focus, var(--brand)) !important;
        outline-offset: 3px;
    }
    @media (prefers-reduced-motion: reduce) {
        html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab { transition:none !important; }
    }
    /* Keep record values easy to scan without making every row bold. */
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody :is(.table-primary, .table-financial-total, .records-case-code) {
        font-weight: 400 !important;
        line-height: 1.45;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody .table-secondary {
        font-size: 13px !important;
        font-weight: 400 !important;
        line-height: 1.5;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody .status-badge {
        font-weight: 400 !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) {
        overflow: visible !important;
    }
    /* Sticky filters must use the page scroller, not a clipped card ancestor. */
    html[data-theme] body.panel-shell-body .master-records-page > .table-system-card {
        overflow: visible !important;
    }
    /* Focus belongs to the composite field, not a second box around its select. */
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-filters .case-compact-seg :is(.case-compact-date-select, .case-compact-sort-select):is(:focus, :focus-visible) {
        outline: none !important;
        box-shadow: none !important;
        border: 0 !important;
        background: transparent !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-compact-seg:has(select:focus-visible) {
        outline: 2px solid var(--brand) !important;
        outline-offset: 2px !important;
    }
    html[data-theme] body.panel-shell-body .records-page > .case-records-top-wrapper.is-stuck,
    html[data-theme] body.panel-shell-body .master-records-page .admin-table-toolbar.is-stuck,
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-toolbar-shell.is-stuck,
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar.is-stuck,
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-toolbar.is-stuck,
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .admin-table-toolbar.is-stuck {
        box-shadow: 0 8px 24px rgba(8, 29, 19, .22), 0 0 0 1px var(--border-strong, var(--border)) !important;
        border-radius: 12px;
    }
    html[data-theme] body.panel-shell-body .records-page > .case-records-top-wrapper,
    html[data-theme] body.panel-shell-body .master-records-page .admin-table-toolbar {
        display: block !important;
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        padding: 12px !important;
        margin-inline: 0 !important;
        align-self: stretch !important;
        position: sticky !important;
        top: 16px !important;
        z-index: 30;
        overflow: visible !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-list {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        overflow: visible !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-wrap {
        /* Contain the global sticky header's z-index below the filter toolbar. */
        position: relative;
        z-index: 0;
        isolation: isolate;
        border: 1px solid var(--records-border, var(--border)) !important;
        border-radius: 6px !important;
        overflow-x: auto !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-pagination {
        position: static !important;
        margin-top: 16px !important;
        padding: 0 !important;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-paginator {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }

    /* Keep case-record controls in the same order as Service Management:
       Search | Filter | Sort | Rows per page. Applied chips stay below. */
    html[data-theme] body.panel-shell-body.staff-ui .records-page > .case-records-top-wrapper {
        display: block !important;
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: none !important;
        padding: 12px !important;
        margin: 0 0 10px !important;
        background: var(--records-card, var(--card)) !important;
        border: 1px solid var(--records-border, var(--border)) !important;
        border-radius: 12px !important;
    }
    html[data-theme] body.panel-shell-body.staff-ui .records-page .case-records-controls {
        display: block !important;
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
        border: 0 !important;
    }
    @media print {
        html[data-theme] body.panel-shell-body .records-page > .case-records-top-wrapper,
        html[data-theme] body.panel-shell-body .master-records-page .admin-table-toolbar { position: static !important; }
    }
    /* One shared toolbar contract: Service Management and case worklists use
       the same surfaces, control rhythm, icon spacing, and applied-filter row. */
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar {
        --shared-filter-surface: var(--filter-ui-surface, var(--surface-panel, #F4F8F5));
        --shared-filter-menu: var(--filter-ui-menu, var(--surface-panel, #F4F8F5));
        --shared-filter-border: var(--filter-ui-border, var(--border, #AEC7B7));
        --shared-filter-text: var(--filter-ui-text, var(--ink, #081D13));
        --shared-filter-height: 44px;
        box-sizing: border-box;
        width: 100%;
        max-width: none;
        min-width: 0;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar :is(.table-toolbar-input-wrap, .case-compact-search-control) {
        box-sizing: border-box !important;
        height: var(--shared-filter-height) !important;
        min-height: var(--shared-filter-height) !important;
        border: 1px solid var(--shared-filter-border) !important;
        border-radius: 8px !important;
        background: var(--shared-filter-surface) !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar :is(.table-toolbar-search, .case-compact-input) {
        box-sizing: border-box !important;
        min-width: 0 !important;
        color: var(--shared-filter-text) !important;
        font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-search-control {
        --filter-control-background: transparent;
        --filter-control-border: 0;
        background: var(--case-search-bg) !important;
        border-radius: 14px !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-search-control > input {
        background: transparent !important;
        border: 0 !important;
        border-radius: inherit !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar :is(.catalog-control > summary, .case-compact-more, .case-compact-sort-filter) {
        box-sizing: border-box !important;
        min-height: var(--shared-filter-height) !important;
        border: 1px solid var(--shared-filter-border) !important;
        border-radius: 8px !important;
        background: var(--shared-filter-menu) !important;
        color: var(--shared-filter-text) !important;
        font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif !important;
        font-size: 14px !important;
        font-weight: 600 !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar :is(.catalog-control > summary, .case-compact-more) {
        display: inline-flex !important;
        align-items: center !important;
        gap: 9px !important;
        padding-inline: 13px !important;
        white-space: nowrap;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar :is(.catalog-control > summary > .bi, .case-compact-more > .bi, .case-compact-sort-filter > .bi) {
        flex: 0 0 auto;
        color: var(--shared-filter-text) !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar .case-compact-sort-filter {
        display: inline-flex !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        gap: 9px !important;
        padding-inline: 13px !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .filter-select-menu {
        box-sizing: border-box;
        width: min(240px, calc(100vw - 32px)) !important;
        min-width: 0 !important;
        max-height: 320px;
        padding: 6px !important;
        margin: 0 !important;
        overflow: auto;
        background: var(--shared-filter-menu) !important;
        border: 1px solid var(--shared-filter-border) !important;
        border-radius: 10px !important;
        box-shadow: 0 12px 30px rgb(18 35 27 / .18);
        z-index: 1200 !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-sort-filter > .filter-select-menu {
        position: absolute !important;
        top: calc(100% + 8px) !important;
        right: 0 !important;
        left: auto !important;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .filter-select-menu-heading {
        padding: 8px 10px 10px;
        margin-bottom: 4px;
        border-bottom: 1px solid var(--shared-filter-border);
        color: var(--filter-ui-muted);
        font-size: 12px;
        font-weight: 600;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .filter-select-menu__item {
        display: flex !important;
        align-items: center;
        gap: 10px !important;
        min-height: 40px;
        padding: 10px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        line-height: 1.4;
        text-align: left;
        white-space: normal;
        overflow-wrap: anywhere;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .filter-select-menu__item > span { flex: 1; }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .filter-select-menu__item > i { flex: 0 0 16px; }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-more {
        justify-content: flex-start !important;
        flex: 0 0 160px !important;
        white-space: nowrap !important;
        font-weight: 600 !important;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters :is(.case-compact-advanced, .case-compact-drawer, .case-compact-more, .filter-select-menu) {
        transition: none !important;
        animation: none !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar .case-compact-sort-filter > select {
        color: var(--shared-filter-text) !important;
        font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif !important;
        font-size: 14px !important;
        font-weight: 600 !important;
    }
    html[data-theme='dark'] body.panel-shell-body .page-content .shared-record-filter-toolbar {
        --shared-filter-surface: var(--filter-ui-surface, #14261D);
        --shared-filter-menu: var(--filter-ui-menu, #112218);
        --shared-filter-border: var(--filter-ui-border, #29523D);
        --shared-filter-text: var(--filter-ui-text, #F8FAFC);
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar :is(.case-applied-filters, .catalog-active-filter-chips) {
        box-sizing: border-box !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap !important;
        gap: 8px 12px !important;
        width: 100% !important;
        min-width: 0 !important;
        margin-top: 8px !important;
    }
    html[data-theme] body.panel-shell-body .page-content .service-hub-toolbar + .catalog-active-filter-chips {
        box-sizing: border-box !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap !important;
        gap: 8px 12px !important;
        width: 100% !important;
        min-width: 0 !important;
        margin-top: 8px !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar .case-applied-filter-chips {
        display: flex !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        gap: 7px !important;
        min-width: 0;
    }
    html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar .catalog-filter-chip, .service-hub-toolbar + .catalog-active-filter-chips .catalog-filter-chip) {
        flex: 0 0 auto;
        font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-rows-per-page {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-page-size-control {
        /* The uniform input rule has higher specificity and reads these
           tokens. Keep its input transparent so this wrapper owns the color. */
        --filter-control-background: transparent;
        --filter-control-border: 0;
        position: relative;
        display: inline-flex;
        flex: 0 0 88px;
        width: 88px;
        box-sizing: border-box;
        background: var(--shared-filter-menu) !important;
        border: 1px solid var(--shared-filter-border) !important;
        border-radius: 8px;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-page-size-control :is(select,input) {
        width: 100% !important;
        height: 44px;
        padding: 0 32px 0 12px !important;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-page-size-chevron {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: auto;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        cursor: pointer;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-page-size-control > input {
        background: transparent !important;
        border: 0 !important;
        color: var(--shared-filter-text) !important;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-page-size-chevron[aria-expanded='true'] > i { display:block; rotate:180deg; }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-custom-date-side-panel {
        box-sizing:border-box;
        padding:16px !important;
        border:1px solid var(--shared-filter-border) !important;
        border-radius:10px !important;
        background:var(--shared-filter-menu) !important;
        color:var(--shared-filter-text);
        max-height:calc(100dvh - 24px);
        overflow:auto;
        box-shadow:0 12px 30px rgb(18 35 27 / .18);
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-compact-more:is(:hover,:active,:focus) {
        transform: none !important;
    }
    /* Center with translate independently of rotation so the offset does not
       rotate along with the arrow when a dropdown opens. */
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters :is(.shared-record-chevron, .case-compact-sort-chev, [data-case-more-icon]) {
        display: inline-grid !important;
        place-items: center;
        width: 16px;
        height: 16px;
        line-height: 1;
        transform: none !important;
        translate: none !important;
        rotate: 0deg !important;
        transform-origin: center;
        transition: rotate .16s ease !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .shared-record-filter:not(.case-compact-sort-filter) > .shared-record-chevron {
        position: absolute !important;
        top: 50% !important;
        right: 12px !important;
        translate: 0 -50% !important;
        transform: none !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .shared-record-filter.is-open > .shared-record-chevron,
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-more[aria-expanded='true'] > [data-case-more-icon] {
        transform: none !important;
        rotate: 180deg !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters :is(.case-compact-sort-chev, [data-case-more-icon]) { margin-left:auto; }
    @media (prefers-reduced-motion: reduce) {
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters :is(.shared-record-chevron, [data-case-more-icon]) { transition:none !important; }
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-page-size-control input::-webkit-inner-spin-button,
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-page-size-control input::-webkit-calendar-picker-indicator { display:none; }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters [data-case-clear-filters] {
        color: #B42318 !important;
        border: 1px solid transparent !important;
        background: transparent !important;
        box-shadow: none !important;
        outline: none !important;
        font-weight: 400 !important;
        transform: none !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters [data-case-clear-filters]:hover {
        color: #912018 !important;
        background: transparent !important;
        border-color: #D88B80 !important;
    }
    html[data-theme='dark'] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters [data-case-clear-filters] {
        color: #FDA29B !important;
        background: transparent !important;
        border-color: transparent !important;
    }
    html[data-theme='dark'] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters [data-case-clear-filters]:hover {
        color: #FECDCA !important;
        background: transparent !important;
        border-color: #B4655B !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters [data-case-clear-filters]:focus-visible {
        outline: none !important;
        box-shadow: none !important;
        border-color: currentColor !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters [data-case-clear-filters] > span { font-weight:400 !important; }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-compact-sort-filter > select {
        flex: 1 1 0;
        width: 0 !important;
        min-width: 0 !important;
        padding: 0 !important;
    }
    /* Icons participate in the flex row; legacy absolute positioning placed
       the leading icon on top of the selected label. */
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-compact-sort-filter > :is(.case-compact-sort-icon, .case-compact-sort-chev) {
        position: static !important;
        transform: none !important;
        flex: 0 0 auto;
    }
    /* Catalog summaries use the menu surface. Use that same surface for every
       case selector, and let composite wrappers own their single border. */
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters :is(.case-compact-more, .case-compact-sort-filter, .case-compact-drawer .case-compact-branch, .case-compact-drawer .case-compact-date-filter, .case-compact-drawer .global-filter-select-wrap, .case-compact-drawer input[type='date']) {
        background: var(--shared-filter-menu) !important;
        border: 1px solid var(--shared-filter-border) !important;
        color: var(--shared-filter-text) !important;
        border-radius: 8px !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters :is(.case-compact-branch, .case-compact-date-filter, .case-compact-sort-filter, .global-filter-select-wrap) > select {
        --filter-control-background: transparent;
        --filter-control-border: 0;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-drawer .case-compact-branch {
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        height: 44px !important;
        padding: 0 38px 0 14px !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-branch > select {
        flex: 1 1 0 !important;
        width: 0 !important;
        padding: 0 !important;
        min-width: 0 !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-branch > :is(.bi-building, .shared-record-filter-icon) {
        position: static !important;
        transform: none !important;
        flex: 0 0 16px !important;
        width: 16px !important;
        margin: 0 !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .filter-select-menu__item:hover {
        background: var(--surface-muted) !important;
    }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .filter-select-menu__item[aria-selected='true'] {
        background: var(--brand-soft) !important;
    }
    /* Case-specific layout uses the shared control components and order:
       Search | Filter | Sort | Rows per page, with chips on a full-width row. */
    @media (min-width:1201px) {
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto auto auto !important;
            grid-template-rows: auto auto !important;
            align-items: center !important;
            justify-content: stretch !important;
            column-gap: 14px !important;
            row-gap: 0 !important;
            width: 100% !important;
            max-width: none !important;
        }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters > .case-compact-search-row { display:flex !important; grid-column:1 !important; grid-row:1 !important; width:100% !important; min-width:0 !important; margin:0 !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters :is(.case-compact-search-field,.case-compact-search-control) { box-sizing:border-box !important; width:100% !important; min-width:0 !important; max-width:none !important; margin:0 !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters > .case-compact-filter-bar { display:contents !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-more { grid-column:2 !important; grid-row:1 !important; width:160px !important; min-width:160px !important; max-width:160px !important; margin:0 !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-sort-filter { grid-column:3 !important; grid-row:1 !important; width:160px !important; min-width:160px !important; max-width:160px !important; margin:0 !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-rows-per-page { grid-column:4 !important; grid-row:1 !important; width:auto !important; min-width:max-content !important; margin:0 !important; white-space:nowrap !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters > .case-applied-filters { grid-column:1 / -1 !important; grid-row:2 !important; margin-top:8px !important; }
    }
    @media (min-width:768px) and (max-width:1200px) {
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters { display:flex !important; flex-flow:row wrap !important; align-items:center !important; gap:10px 12px !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters > .case-compact-search-row { display:flex !important; flex:1 1 100% !important; order:0 !important; width:100% !important; min-width:0 !important; margin:0 !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters > .case-compact-filter-bar { display:flex !important; flex:1 1 100% !important; order:1 !important; justify-content:flex-end !important; gap:10px !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters > .case-applied-filters { order:2 !important; flex-basis:100% !important; }
    }
    @media (max-width:767px) {
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters > .case-compact-search-row { display:flex !important; flex:1 1 100% !important; order:0 !important; width:100% !important; margin:0 !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters > .case-compact-filter-bar { display:flex !important; flex:1 1 100% !important; order:1 !important; flex-wrap:wrap !important; gap:8px !important; }
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters > .case-applied-filters { display:flex !important; flex:1 1 100% !important; order:2 !important; flex-wrap:wrap !important; align-items:flex-start !important; gap:8px !important; }
    }
    /* Hidden panels must stay hidden even when role styles set display:flex. */
    html[data-theme] form.case-record-filters .case-compact-advanced[hidden] { display:none !important; }
    /* Single owner for the filter drawer on both case worklists. */
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-advanced:not([hidden]) {
        position:fixed !important;
        inset:0 !important;
        display:flex !important;
        align-items:flex-start !important;
        justify-content:flex-start !important;
        width:100vw !important;
        height:100dvh !important;
        margin:0 !important;
        padding:0 !important;
        border:0 !important;
        border-radius:0 !important;
        overflow:hidden !important;
        background:transparent !important;
        pointer-events:none;
        z-index:2147483000 !important;
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-advanced:popover-open {
        position:fixed !important;
        inset:0 !important;
        width:100vw !important;
        height:100dvh !important;
    }
    /* The page shell uses a transform for its entrance animation. Remove that
       containing block only while this viewport-positioned filter is open. */
    html[data-theme] body.panel-shell-body .page-content:has(.case-record-filters) {
        transform:none !important;
        animation:none !important;
        will-change:auto !important;
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-drawer {
        box-sizing:border-box !important;
        position:absolute !important;
        top:var(--filter-top,16px) !important;
        left:var(--filter-left,16px) !important;
        display:flex !important;
        flex:0 1 auto !important;
        flex-direction:column !important;
        width:min(var(--filter-width,440px),calc(100vw - 32px)) !important;
        min-width:0 !important;
        max-width:calc(100vw - 32px) !important;
        height:auto !important;
        min-height:0 !important;
        max-height:min(78dvh,var(--filter-max-height,78dvh)) !important;
        overflow:hidden !important;
        pointer-events:auto;
        border:1px solid var(--records-border,var(--filter-ui-border,var(--border))) !important;
        border-radius:14px !important;
        background:var(--card) !important;
        box-shadow:0 12px 30px rgb(18 35 27 / .18);
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-advanced-head {
        flex:0 0 auto !important;
        padding:12px 16px !important;
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-advanced-grid {
        box-sizing:border-box !important;
        display:grid !important;
        grid-template-columns:minmax(0,1fr) !important;
        gap:12px !important;
        padding:14px 16px !important;
        flex:1 1 auto !important;
        width:100% !important;
        min-height:0 !important;
        min-width:0 !important;
        max-width:100% !important;
        overflow-x:clip !important;
        overflow-y:auto !important;
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-advanced-actions { flex:0 0 auto !important; }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-compact-drawer :is(.case-compact-advanced-head, .case-compact-advanced-grid, .case-compact-advanced-actions) {
        background: var(--card) !important;
        margin: 0 !important;
        padding: 12px 14px !important;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-filter-group-title {
        margin: 0 !important;
        font-size: 13px !important;
        font-weight: 650 !important;
        line-height: 1.4 !important;
    }
    html[data-theme] body.panel-shell-body .page-content .case-record-filters .case-compact-advanced-note { display:none; }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-filter-group {
        display:grid !important;
        grid-template-columns:minmax(0,1fr) !important;
        align-items:start !important;
        width:100% !important;
        min-width:0 !important;
        max-width:100% !important;
        gap:10px 14px !important;
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-filter-group-title { grid-column:1 / -1 !important; }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-filter-group-title,
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-filter-group > .case-interment-range { grid-column:1 / -1 !important; }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters :is(.case-filter-group > *, .case-compact-field, .case-interment-fields, .case-interment-fields > *) {
        box-sizing:border-box !important;
        min-width:0 !important;
        max-width:100% !important;
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-interment-fields {
        display:grid !important;
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        gap:10px 14px !important;
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-drawer :is(.case-compact-branch, .case-compact-seg, .case-compact-input, .case-interment-fields input) {
        box-sizing:border-box !important;
        flex:1 1 auto !important;
        width:100% !important;
        min-width:0 !important;
        max-width:100% !important;
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-advanced-date > .case-compact-date-filter {
        position:relative !important;
        display:flex !important;
        flex:1 1 auto !important;
        height:44px !important;
        min-height:44px !important;
        max-height:44px !important;
        overflow:visible !important;
        z-index:2;
        align-self:stretch !important;
        background:var(--filter-ui-surface,var(--records-card,var(--card))) !important;
        border:1px solid var(--records-border,var(--filter-ui-border,var(--border))) !important;
        border-radius:8px !important;
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-advanced-date > .case-compact-date-filter > .case-compact-date-select {
        box-sizing:border-box !important;
        position:absolute !important;
        inset:0 !important;
        width:100% !important;
        min-width:0 !important;
        height:44px !important;
        min-height:44px !important;
        max-height:44px !important;
        padding:0 38px 0 40px !important;
        border:0 !important;
        border-radius:inherit !important;
        background:transparent !important;
        text-align:left !important;
    }
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-date-filter > .filter-select-menu,
    html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-compact-branch > .filter-select-menu { z-index:1200 !important; }
    @media (max-width:767px) {
        html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-filter-group { grid-template-columns:minmax(0,1fr) !important; }
        html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-filter-group-title,
        html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-filter-group > .case-interment-range { grid-column:1 !important; }
        html[data-theme] :is(.records-page,.master-records-page,.owner-master-records-page) form.case-record-filters .case-interment-fields { grid-template-columns:minmax(0,1fr) !important; }
    }
    /* Shorten only the visible search field; keep the grid track and controls fixed. */
    @media(min-width:1201px) {
        html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters > .case-compact-search-row > .case-compact-search-field {
            flex:0 1 auto !important;
            width:min(560px,calc(100% - 24px)) !important;
            max-width:560px !important;
            min-width:0 !important;
        }
    }
    /* Full search component parity with Payment Monitoring: action, clear and matches. */
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-search-control > input { padding-right:128px !important; font-size:14px !important; font-weight:400 !important; }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-search-clear { right:92px !important; }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-search-button { position:absolute; top:0; bottom:0; right:0; width:84px; border:0; border-left:1px solid var(--border); border-radius:0 13px 13px 0; background:var(--shared-filter-menu,var(--card)); color:var(--ink); font-size:14px; font-weight:500; cursor:pointer; }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-search-button:hover { background:var(--record-search-action-hover); }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-search-button:focus-visible { outline:2px solid var(--brand); outline-offset:-3px; }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-search-suggestions { position:absolute; top:calc(100% + 6px); left:0; right:auto; width:100%; box-sizing:border-box; max-height:280px; overflow:auto; z-index:50; display:grid; padding:6px; border:1px solid var(--border); border-radius:10px; background:var(--shared-filter-menu,var(--card)); }
    html[data-theme] body.panel-shell-body .page-content .shared-record-filter-toolbar.case-record-filters .case-compact-search-suggestions[hidden] { display:none !important; }
    .case-search-match { display:flex; align-items:center; gap:10px; width:100%; padding:10px 12px; border:0; border-radius:6px; background:transparent; color:var(--ink); text-align:left; line-height:1.5; cursor:pointer; }
    .case-search-match:hover { background:var(--surface-muted); }
    .case-search-match:focus-visible { outline:2px solid var(--brand); outline-offset:-2px; }
    .case-match-code { flex:none; font-size:14px; font-weight:600; }
    .case-match-name { display:flex; align-items:center; gap:10px; flex:0 1 auto; min-width:0; font-size:13px; font-weight:400; white-space:nowrap; overflow:hidden; }
    .case-match-name::before { content:'·'; flex:none; color:var(--ink-muted); }
    .case-match-name > span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .case-search-match mark { background:transparent; color:inherit; font-weight:inherit; text-decoration:underline; text-underline-offset:3px; text-decoration-thickness:1px; }
</style>
@include('partials.records_results_layout_styles')
<script>
    function initStickyWorklistToolbar() {
        const scrollRoot = document.querySelector('.page-content');
        if (!scrollRoot) return;
        let scheduled = false;
        const update = () => {
            scheduled = false;
            const toolbar = document.querySelector('.records-page > .case-records-top-wrapper, .master-records-page .admin-table-toolbar, .payment-monitoring-page .pm-toolbar-shell, .directory-page .table-system-toolbar, .service-management-hub .service-hub-toolbar, .audit-ref-page .admin-table-toolbar');
            if (!toolbar) return;
            const top = toolbar.getBoundingClientRect().top - scrollRoot.getBoundingClientRect().top;
            toolbar.classList.toggle('is-stuck', scrollRoot.scrollTop > 0 && top <= 17);
        };
        const schedule = () => {
            if (scheduled) return;
            scheduled = true;
            requestAnimationFrame(update);
        };
        scrollRoot.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule, { passive: true });
        // Staff filters replace their toolbar through AJAX; update the replacement too.
        new MutationObserver(schedule).observe(scrollRoot, { childList: true, subtree: true });
        update();
    }
    if (document.querySelector('.page-content')) initStickyWorklistToolbar();
    else document.addEventListener('DOMContentLoaded', initStickyWorklistToolbar, { once:true });
</script>

@include('partials.record_filter_sort_states')

<style>
html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page) .case-record-icon-action.case-record-action-view { color:var(--record-view-ink,var(--records-text,var(--ink))) !important; }
/* Current compact worklist: secondary record details live in the summary drawer. */
html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page) .records-worklist-table .records-col-case { width:12%; }
html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page) .records-worklist-table .records-col-deceased { width:26%; }
html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page) .records-worklist-table .records-col-interment { width:16%; }
html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page) .records-worklist-table .records-col-case-status { width:10%; }
html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page) .records-worklist-table .records-col-payment-status { width:12%; }
html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page) .records-worklist-table .records-col-balance { width:18%; }
html[data-theme] body.panel-shell-body .owner-master-records-page .records-worklist-table .records-col-case { width:11%; }
html[data-theme] body.panel-shell-body .owner-master-records-page .records-worklist-table .records-col-branch { width:14%; }
html[data-theme] body.panel-shell-body .owner-master-records-page .records-worklist-table .records-col-deceased { width:21%; }
html[data-theme] body.panel-shell-body .owner-master-records-page .records-worklist-table .records-col-interment { width:14%; }
html[data-theme] body.panel-shell-body .owner-master-records-page .records-worklist-table .records-col-payment-status { width:11%; }
html[data-theme] body.panel-shell-body .owner-master-records-page .records-worklist-table .records-col-balance { width:14%; }
</style>
