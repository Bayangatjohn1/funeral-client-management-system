<style>
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-toolbar { display: flex !important; flex-wrap: wrap !important; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-toolbar .pm-field {
        flex: 1 0 160px;
        min-width: 160px !important;
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-toolbar .pm-field:has(select[name='payment_status']) {
        flex-basis: 210px;
        min-width: 210px !important;
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-toolbar .pm-field.branch-readonly {
        flex: 1 0 300px;
        min-width: 300px !important;
        max-width: none;
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-toolbar .pm-field.search {
        flex: 2 1 240px;
        min-width: 240px !important;
    }
    @media (max-width: 600px) {
        html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-toolbar .pm-field {
            flex: 1 0 100% !important;
            min-width: 0 !important;
            max-width: 100%;
        }
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-kpi-inner {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) 40px;
        gap: 8px 12px !important;
        align-items: start !important;
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-kpi-body { display: contents; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-kpi-icon {
        grid-area: 1 / 2 / 3 / 3;
        width: 40px;
        height: 40px;
        justify-self: end;
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-kpi-label {
        grid-area: 1 / 1;
        margin: 0;
        line-height: 1.5;
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-kpi-value {
        grid-area: 2 / 1;
        margin: 0;
        font-size: clamp(20px, 1.6vw, 26px) !important;
        line-height: 1.25;
        overflow-wrap: anywhere;
        white-space: normal;
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-kpi-desc {
        grid-area: 3 / 1 / 4 / 3;
        margin: 0;
        line-height: 1.5;
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-toolbar-shell {
        position: sticky;
        top: 16px;
        z-index: 30;
        box-shadow: none;
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-panel {
        position: relative;
        z-index: 0;
        isolation: isolate;
    }
    body:is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page) {
        --staff-canvas: var(--surface);
        --staff-card: var(--card);
        --staff-soft: var(--surface-muted);
        --staff-border: var(--border);
        --staff-ink: var(--ink);
        --staff-muted: var(--ink-muted);
        --staff-action: var(--brand);
        --staff-accent: var(--brand);
    }
    /*
     * Staff records visual system
     * Presentation-only overrides shared by Case Records, Payment Monitoring,
     * and Client Directory. Existing content, fields, routes, and behavior stay intact.
     */
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) {
        --staff-records-canvas: var(--staff-canvas, #FBFBF9);
        --staff-records-surface: var(--staff-card, #FFFFFF);
        --staff-records-soft: var(--staff-soft, #F4F7F5);
        --staff-records-soft-strong: #EBF3ED;
        --staff-records-border: var(--staff-border, #E2E8E4);
        --staff-records-border-strong: #C6D4CB;
        --staff-records-ink: var(--staff-ink, #0F241C);
        --staff-records-muted: var(--staff-muted, #526A5F);
        --staff-records-action: var(--staff-action, #1B4332);
        --staff-records-action-hover: #143527;
        --staff-records-radius-panel: 12px;
        --staff-records-radius-control: 8px;
        --staff-records-focus: 0 0 0 3px rgb(27 67 50 / 18%);
    }

    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) {
        --staff-records-canvas: #07130E;
        --staff-records-surface: #112218;
        --staff-records-soft: #14261D;
        --staff-records-soft-strong: #183124;
        --staff-records-border: #264333;
        --staff-records-border-strong: #41634F;
        --staff-records-ink: var(--staff-ink);
        --staff-records-muted: var(--staff-muted);
        --staff-records-action: var(--staff-action);
        --staff-records-action-hover: #B1E8CA;
        --staff-records-focus: 0 0 0 3px rgb(143 215 178 / 22%);
    }

    /* Keep page titles on the workspace canvas, matching the approved Staff screens. */
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .panel-page-header {
        color: var(--staff-records-ink) !important;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        overflow: visible !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .panel-page-header::after {
        display: none !important;
        content: none !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .panel-page-header__copy h1 {
        color: var(--staff-records-ink) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .panel-page-header__copy p {
        color: var(--staff-records-muted) !important;
    }

    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .panel-page-header__copy p {
        color: var(--staff-accent) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page) {
        color: var(--staff-records-ink) !important;
        background: transparent !important;
        font-family: var(--font-body) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page {
        --records-card: var(--staff-records-surface);
        --records-card-alt: var(--staff-records-soft);
        --records-card-strong: var(--staff-records-soft-strong);
        --records-hover: var(--staff-records-soft);
        --records-active: var(--staff-records-soft-strong);
        --records-border: var(--staff-records-border);
        --records-border-strong: var(--staff-records-border-strong);
        --records-text: var(--staff-records-ink);
        --records-muted: var(--staff-records-muted);
        padding-top: 1rem !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page)
    :is(.table-system-card, .table-system-list, .case-records-top-wrapper, .pm-toolbar-shell, .pm-panel, .pm-kpi, .pm-modal) {
        background: var(--staff-records-surface) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: var(--staff-records-radius-panel) !important;
        box-shadow: 0 1px 2px rgb(15 36 28 / 4%) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page :is(.table-system-toolbar, .table-system-list-header, .table-system-pagination),
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page :is(.pm-modal-hd, .pm-modal-ft) {
        background: var(--staff-records-surface) !important;
        border-color: var(--staff-records-border) !important;
    }
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-foot { background:transparent !important; border:0 !important; }
    html[data-theme] body.panel-shell-body .pm-page .pm-foot .table-paginator { background:transparent !important; border:0 !important; }
    html[data-theme] body.panel-shell-body .pm-page .pm-foot .table-paginator-meta { color:var(--filter-ui-muted) !important; }
    html[data-theme] body.panel-shell-body .pm-page .pm-foot .table-paginator-btn { background:var(--filter-ui-menu) !important; color:var(--filter-ui-text) !important; border-color:var(--filter-ui-border) !important; }
    html[data-theme] body.panel-shell-body .pm-page .pm-foot .table-paginator-btn.is-active { background:var(--filter-ui-focus) !important; color:var(--filter-ui-menu) !important; }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .case-records-top-wrapper {
        padding: .875rem !important;
        gap: .75rem !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page)
    :is(input, select, textarea, .form-input, .form-select, .case-compact-input, .case-compact-select,
        .case-compact-date-select, .case-compact-sort-select, .case-compact-branch, .pm-control) {
        min-height: 44px !important;
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-soft) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: var(--staff-records-radius-control) !important;
        box-shadow: none !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page)
    :is(input, select, textarea, .form-input, .form-select, .case-compact-input, .case-compact-select,
        .case-compact-date-select, .case-compact-sort-select, .pm-control):hover {
        background: var(--staff-records-surface) !important;
        border-color: var(--staff-records-border-strong) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page)
    :is(input, select, textarea, button, a[href], [role='button']):focus-visible {
        outline: 2px solid var(--staff-records-action) !important;
        outline-offset: 2px !important;
        box-shadow: var(--staff-records-focus) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page)
    :is(.btn-secondary, .case-compact-apply, .case-compact-pop-apply, .pm-btn.primary, .ops-btn-primary) {
        color: #FFFFFF !important;
        background: #1B4332 !important;
        border-color: #1B4332 !important;
        border-radius: var(--staff-records-radius-control) !important;
        box-shadow: none !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page)
    :is(.btn-secondary, .case-compact-apply, .case-compact-pop-apply, .pm-btn.primary, .ops-btn-primary):hover {
        color: #FFFFFF !important;
        background: #143527 !important;
        border-color: #143527 !important;
        transform: none !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page)
    :is(.btn-outline, .btn-filter-reset, .case-compact-reset, .case-compact-more, .case-compact-pop-reset,
        .case-compact-advanced-clear, .pm-btn, .pm-light-link, .row-action-trigger) {
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-surface) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: var(--staff-records-radius-control) !important;
        box-shadow: none !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page)
    :is(.btn-outline, .btn-filter-reset, .case-compact-reset, .case-compact-more, .case-compact-pop-reset,
        .case-compact-advanced-clear, .pm-btn, .pm-light-link, .row-action-trigger):hover {
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-soft) !important;
        border-color: var(--staff-records-border-strong) !important;
        transform: none !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page :is(.table-quick-tabs, .case-records-tabs) {
        padding: .25rem !important;
        gap: .25rem !important;
        background: var(--staff-records-soft) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: 10px !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-quick-tab {
        min-height: 40px !important;
        padding: .55rem .85rem !important;
        color: var(--staff-records-muted) !important;
        background: transparent !important;
        border: 1px solid transparent !important;
        border-radius: 7px !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-quick-tab-active {
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-surface) !important;
        border-color: var(--staff-records-border) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-list-header {
        padding: 1rem 1.25rem !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-list-title {
        color: var(--staff-records-ink) !important;
        font-size: 1rem !important;
        font-weight: 700 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-list-copy,
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-secondary,
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page :is(.pm-kpi-desc, .pm-row-meta, .pm-row-date, .pm-sub, .pm-muted) {
        color: var(--staff-records-muted) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-wrap {
        background: var(--staff-records-surface) !important;
        border: 0 !important;
        border-radius: 0 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table {
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-surface) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table thead th {
        padding: .8rem 1rem !important;
        color: var(--staff-records-muted) !important;
        background: var(--staff-records-soft) !important;
        border-color: var(--staff-records-border) !important;
        font-size: .72rem !important;
        letter-spacing: .035em !important;
        text-transform: uppercase !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table tbody td {
        padding: .9rem 1rem !important;
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-surface) !important;
        border-color: var(--staff-records-border) !important;
        vertical-align: middle !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table tbody tr:hover td {
        background: var(--staff-records-soft) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page :is(.table-primary, .records-case-code) {
        color: var(--staff-records-ink) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page :is(.records-case-code, .table-financial-total, .table-financial-breakdown),
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-records-mono,
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page [data-label="Schedule"],
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.pm-page, .pm-transactions-modal) :is(.pm-case, .pm-kpi-value, .pm-summary-stat strong, .pm-txn-rec, .pm-txn-amt, .pm-txn-bal-val, .pm-row-date) {
        font-family: 'JetBrains Mono', Consolas, monospace !important;
        font-variant-numeric: tabular-nums !important;
    }

    /* Case list typography follows the Dashboard. */
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page {
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page :is(.table-system-list, .table-system-wrap, .table-wrapper) {
        flex: 0 0 auto !important;
        height: auto !important;
        max-height: none !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page .records-col-actions {
        width: 84px;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page .records-view-case {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 44px;
        width: 44px;
        height: 44px;
        border: 1px solid var(--staff-records-border);
        border-radius: 8px;
        color: var(--staff-records-action);
        background: var(--staff-records-soft);
        text-decoration: none;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page .records-view-tooltip {
        position: absolute;
        top: 50%;
        right: calc(100% + 8px);
        transform: translateY(-50%);
        z-index: 30;
        width: max-content;
        padding: 5px 8px;
        border-radius: 6px;
        color: var(--staff-records-surface);
        background: var(--staff-records-ink);
        font: 500 12px/1.4 var(--font-body);
        pointer-events: none;
        visibility: hidden;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page .records-view-case:is(:hover, :focus-visible) .records-view-tooltip {
        visibility: visible;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page .records-person-names {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page .table-primary {
        font-family: var(--font-body);
        font-size: 14px;
        font-weight: 600;
        line-height: 1.5;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page .table-secondary {
        margin-top: 4px;
        font-size: 12px;
        line-height: 1.5;
        white-space: normal !important;
        overflow-wrap: anywhere;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page [data-label='Schedule'] .table-primary {
        font-family: 'JetBrains Mono', Consolas, monospace;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page .table-system-table tbody td {
        vertical-align: top !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page .table-system-table tr[data-clickable-row]:focus-visible {
        outline: 2px solid var(--staff-records-action);
        outline-offset: -2px;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-case-records-page :is(.case-compact-input, .case-compact-select, .case-compact-date-select, .case-compact-sort-select, .table-quick-tab) {
        font-family: var(--font-body) !important;
        font-size: 14px !important;
        letter-spacing: normal !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .row-action-dropdown {
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-surface) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: 10px !important;
        box-shadow: 0 12px 28px rgb(15 36 28 / 12%) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .row-action-item:hover {
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-soft) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-clients-page #clientModalSheet,
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-clients-page #clientModalContent,
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-clients-page #clientModalClose {
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-surface) !important;
        border-color: var(--staff-records-border) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .staff-clients-page #clientModalSheet {
        border-radius: var(--staff-records-radius-panel) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-kpis {
        gap: 1rem !important;
        margin-bottom: 1rem !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-kpi-inner {
        align-items: flex-start !important;
        padding: 1.1rem !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-kpi-icon {
        display: grid !important;
        place-items: center !important;
        width: 36px !important;
        height: 36px !important;
        color: var(--staff-records-action) !important;
        background: var(--staff-records-soft-strong) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: 8px !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-kpi-label {
        color: var(--staff-records-muted) !important;
        letter-spacing: .035em !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-kpi-value {
        margin-top: .25rem !important;
        color: var(--staff-records-ink) !important;
        font-size: 1.35rem !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-kpi-value.good { color: #166534 !important; }
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-kpi-value.warn { color: #9A5B08 !important; }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-kpi-value.good { color: #86EFAC !important; }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-kpi-value.warn { color: #FBBF24 !important; }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-toolbar-shell {
        padding: .875rem !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-panel {
        overflow: hidden !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-records-body {
        max-height: none !important;
        overflow: visible !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-row-title {
        font-weight: 500;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-case-recorder {
        margin-top: 4px;
        color: var(--staff-records-muted);
        font-family: var(--font-body);
        font-size: 12px;
        font-weight: 400;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-case-icon-action {
        position: relative;
        width: 44px;
        height: 44px;
        min-height: 44px;
        flex: 0 0 44px;
        padding: 0;
        cursor: pointer;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-case-icon-action:focus-visible {
        outline: 2px solid var(--staff-records-action);
        outline-offset: 3px;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-action-tooltip {
        position: absolute;
        top: 50%;
        right: calc(100% + 8px);
        bottom: auto;
        left: auto;
        transform: translateY(-50%);
        z-index: 5;
        width: max-content;
        padding: 5px 8px;
        border-radius: 6px;
        background: var(--staff-records-ink);
        color: var(--staff-records-surface);
        font-family: var(--font-body);
        font-size: 12px;
        font-weight: 500;
        line-height: 1.4;
        white-space: nowrap;
        pointer-events: none;
        visibility: hidden;
        opacity: 0;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-case-icon-action:is(:hover, :focus-visible) .pm-action-tooltip {
        visibility: visible;
        opacity: 1;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-panel { overflow: visible; }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-icon-toggle[aria-expanded='true'] .pm-chev {
        transform: rotate(180deg);
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-row-date {
        min-width: 0;
        white-space: normal;
        overflow-wrap: anywhere;
        line-height: 1.5;
    }

    @media (min-width: 1101px) {
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-case-row {
            grid-template-columns: 10rem minmax(0, 1fr) minmax(12rem, 19rem) max-content;
            gap: 1.25rem;
        }
    }

    @media (min-width: 641px) and (max-width: 1100px) {
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-case-row {
            grid-template-columns: 6rem minmax(0, 1fr);
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-row-actions {
            min-width: 0;
            flex-wrap: wrap;
            justify-content: flex-start;
        }
    }

    /* Transaction dialogs must cover and temporarily deactivate the app chrome. */
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-backdrop {
        z-index: 1400 !important;
        padding: 20px !important;
        background: var(--modal-scrim) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)).pm-transactions-modal-open .sidebar {
        z-index: 0 !important;
        pointer-events: none !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)).pm-transactions-modal-open .pm-transactions-backdrop.open {
        pointer-events: auto !important;
    }

    /* Reuse the Staff Dashboard dialog proportions and typography. */
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-section-heading {
        margin: 0 0 12px;
        color: var(--staff-records-ink);
        font-family: var(--font-heading);
        font-size: 14px;
        font-weight: 700;
        line-height: 1.5;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-facts {
        display: grid;
        gap: 6px;
        margin-top: 12px;
        line-height: 1.5;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-facts strong {
        color: var(--staff-records-ink);
        font-weight: 500;
        overflow-wrap: anywhere;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal {
        width: min(620px, calc(100vw - 32px)) !important;
        max-height: calc(100dvh - 40px) !important;
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-surface) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: 16px !important;
        box-shadow: 0 24px 80px rgb(9 37 25 / 25%) !important;
        font-family: var(--font-body) !important;
        letter-spacing: 0 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-modal-hd {
        flex: 0 0 auto !important;
        padding: 16px 20px !important;
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-surface) !important;
        border-bottom: 1px solid var(--staff-records-border) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-modal-title {
        margin: 0 0 4px !important;
        color: var(--staff-records-ink) !important;
        font-family: var(--font-heading) !important;
        font-size: 20px !important;
        font-weight: 700 !important;
        line-height: 1.25 !important;
        letter-spacing: -.02em !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-modal-hd .pm-sub {
        margin-top: 3px !important;
        color: var(--staff-records-muted) !important;
        font-size: 13px !important;
        line-height: 1.4 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-modal-hd [data-pm-close-transactions-modal] {
        flex: 0 0 44px !important;
        width: 44px !important;
        height: 44px !important;
        min-height: 44px !important;
        padding: 0 !important;
        justify-content: center !important;
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-soft) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: 10px !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-modal-body {
        min-height: 0 !important;
        gap: 14px !important;
        padding: 16px 20px !important;
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-surface) !important;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-case-overview {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 8px !important;
        padding: 0 !important;
        background: transparent !important;
        border: 0 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-summary-stat {
        padding: 12px 14px !important;
        background: var(--staff-records-soft) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: 10px !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-summary-stat span {
        color: var(--staff-records-muted) !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        line-height: 1.35 !important;
        letter-spacing: .035em !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-summary-stat strong {
        margin-top: 4px !important;
        color: var(--staff-records-ink) !important;
        font-size: 13px !important;
        line-height: 1.4 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-list {
        gap: 10px !important;
        padding: 0 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-card {
        overflow: hidden !important;
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-soft) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: 10px !important;
        box-shadow: none !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-hd {
        gap: 12px !important;
        padding: 12px 14px !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-rec-label,
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-det-cell span {
        color: var(--staff-records-muted) !important;
        font-size: 11px !important;
        letter-spacing: .035em !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-rec {
        color: var(--staff-records-ink) !important;
        font-size: 13px !important;
        line-height: 1.4 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-sub {
        color: var(--staff-records-muted) !important;
        font-size: 12px !important;
        line-height: 1.45 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-amt {
        color: var(--staff-records-ink) !important;
        font-size: 18px !important;
        line-height: 1.4 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-tog {
        min-height: 40px !important;
        margin: 8px 0 0 !important;
        padding: 8px 12px !important;
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-surface) !important;
        border: 1px solid var(--staff-records-border) !important;
        border-radius: 8px !important;
        font-size: 12px !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-det {
        padding: 14px !important;
        background: var(--staff-records-surface) !important;
        border-top: 1px solid var(--staff-records-border) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-det-grid {
        gap: 12px !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-det-cell strong {
        color: var(--staff-records-ink) !important;
        font-size: 12px !important;
        line-height: 1.45 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-detail-actions {
        padding: 14px 0 0 !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-modal-ft {
        position: sticky;
        bottom: 0;
        flex: 0 0 auto !important;
        padding: 12px 20px !important;
        gap: 10px !important;
        background: var(--staff-records-surface) !important;
        border-top: 1px solid var(--staff-records-border) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-modal-ft .pm-btn {
        min-height: 40px !important;
        padding: 8px 12px !important;
        border-radius: 8px !important;
        font-size: 13px !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page :is(.pm-row-list, .pm-case-item, .pm-trans-list, .pm-trans-item) {
        background: var(--staff-records-surface) !important;
        border-color: var(--staff-records-border) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page :is(.pm-case-row.is-toggle:hover, .pm-case-row[aria-expanded='true'], .pm-trans-row:hover) {
        background: var(--staff-records-soft) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page :is(.pm-summary-detail, .pm-case-overview, .pm-detail, .pm-txn-det) {
        background: var(--staff-records-soft) !important;
        border-color: var(--staff-records-border) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page :is(.pm-summary-stat, .pm-txn-card) {
        background: var(--staff-records-surface) !important;
        border-color: var(--staff-records-border) !important;
        border-radius: 8px !important;
    }

    /* Connect expanded summaries to their case, distinct from adjacent rows. */
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-summary-detail.open {
        background: color-mix(in srgb, var(--staff-records-surface) 65%, var(--staff-records-soft) 35%) !important;
        border-inline-start: 4px solid var(--staff-records-action) !important;
    }
    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-case-row:has([data-pm-summary-toggle][aria-expanded='true']) {
        background: color-mix(in srgb, var(--staff-records-surface) 65%, var(--staff-records-soft) 35%) !important;
        box-shadow: inset 4px 0 var(--staff-records-action);
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-icon-toggle {
        color: var(--staff-records-muted) !important;
        background: var(--staff-records-soft) !important;
        border-color: var(--staff-records-border) !important;
        border-radius: 8px !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-payment-summary-ready {
        color: var(--staff-records-ink) !important;
        background: var(--staff-records-soft) !important;
        border-color: var(--staff-records-border-strong) !important;
        border-radius: var(--staff-records-radius-panel) !important;
    }

    html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-payment-summary-ready span {
        color: var(--staff-records-muted) !important;
    }

    @media (max-width: 767px) {
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page) {
            padding-inline: .75rem !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page)
        :is(button, a[href], [role='button'], .row-action-trigger, .pm-btn, .pm-light-link) {
            min-height: 44px;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table,
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table tbody,
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table tr,
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table td {
            display: block !important;
            width: 100% !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table thead {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip-path: inset(50%) !important;
            white-space: nowrap !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table tbody {
            display: grid !important;
            gap: .75rem !important;
            padding: .75rem !important;
            background: var(--staff-records-soft) !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table tbody tr {
            overflow: visible !important;
            padding: .35rem 0 !important;
            background: var(--staff-records-surface) !important;
            border: 1px solid var(--staff-records-border) !important;
            border-radius: 10px !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table tbody td {
            display: grid !important;
            grid-template-columns: minmax(7.5rem, 38%) minmax(0, 1fr) !important;
            gap: .75rem !important;
            align-items: start !important;
            padding: .65rem .85rem !important;
            background: transparent !important;
            border: 0 !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table tbody td::before {
            content: attr(data-label);
            color: var(--staff-records-muted);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .035em;
            text-transform: uppercase;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-system-table tbody td:not([data-label])::before {
            content: none;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page .table-col-actions,
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .records-page td.table-col-actions {
            justify-content: end !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-page .pm-records-body {
            max-height: none !important;
            overflow: visible !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-backdrop {
            align-items: flex-end !important;
            padding: 12px !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal {
            width: 100% !important;
            max-height: calc(100dvh - 24px) !important;
            border-radius: 14px !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal :is(.pm-modal-hd, .pm-modal-body, .pm-modal-ft) {
            padding-inline: 16px !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-case-overview,
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-det-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-hd {
            flex-direction: column !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-txn-right {
            width: 100% !important;
            align-items: flex-start !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-modal-ft {
            flex-direction: column-reverse !important;
        }

        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) .pm-transactions-modal .pm-modal-ft .pm-btn {
            width: 100% !important;
            min-height: 44px !important;
            justify-content: center !important;
        }
    }

    /* Record Payment follows the same night-shift surfaces as the transaction dialog. */
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal {
        --pf-surface: var(--staff-records-surface);
        --pf-soft: var(--staff-records-soft);
        --pf-raised: var(--staff-records-soft-strong);
        --pf-border: var(--staff-records-border);
        --pf-ink: var(--staff-records-ink);
        --pf-muted: var(--staff-records-muted);
        color: var(--pf-ink);
    }

    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.payment-modal-sheet, .payment-modal-head, .payment-modal-sheet > .p-6) {
        background: var(--pf-surface) !important;
        border-color: var(--pf-border) !important;
        color: var(--pf-ink) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.payment-modal-title, .pf-accordion-title, .pf-section-title, .pf-guide-title, .pf-guide-step, .pf-review-card h4, .pf-review-row strong, .pf-record-card span:last-child, .pf-snapshot-value, .pf-summary-value) {
        color: var(--pf-ink) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.payment-modal-copy, .pf-accordion-copy, .pf-accordion-summary, .pf-section-sub, .pf-label, .pf-help, .pf-guide-copy, .pf-review-row, .pf-record-card span:first-child, .pf-snapshot-label, .pf-summary-label, .pf-progress-meta, .pf-note) {
        color: var(--pf-muted) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-section, .pf-accordion-step, .pf-guide, .pf-guide-step, .pf-review-card, .pf-snapshot, .pf-summary-card, .pf-bank-box, .pf-note, .pf-record-card) {
        background: var(--pf-soft) !important;
        border-color: var(--pf-border) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-accordion-trigger, .pf-section-head) {
        background: var(--pf-raised) !important;
        border-color: var(--pf-border) !important;
        color: var(--pf-ink) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-section-index, .pf-accordion-number, .pf-section-icon, .payment-modal-icon) {
        background: #1B4332 !important;
        color: #F8FAFC !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-input, .pf-amount-wrap, .pf-method-option, .pf-method-type, .pf-quick-amount) {
        background: var(--pf-surface) !important;
        border-color: var(--pf-border) !important;
        color: var(--pf-ink) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-input, .pf-amount-input, .pf-select) {
        color-scheme: dark;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal .pf-select option {
        background: var(--pf-surface) !important;
        color: var(--pf-ink) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-input, .pf-amount-input)::placeholder {
        color: var(--pf-muted) !important;
        opacity: 1;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-input, .pf-amount-wrap):focus-within {
        border-color: #8FD7B2 !important;
        box-shadow: var(--staff-records-focus) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal .pf-input[readonly] {
        background: var(--pf-raised) !important;
        color: var(--pf-muted) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal .pf-amount-prefix {
        background: var(--pf-raised) !important;
        border-color: var(--pf-border) !important;
        color: var(--pf-ink) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal .pf-amount-input {
        background: transparent !important;
        color: var(--pf-ink) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-control-icon, .pf-method-option-copy) {
        color: var(--pf-muted) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-method-option, .pf-method-type, .pf-quick-amount):hover {
        background: var(--pf-raised) !important;
        border-color: var(--staff-records-border-strong) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-method-option, .pf-method-type).is-active {
        background: #1B4332 !important;
        border-color: #8FD7B2 !important;
        color: #F8FAFC !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal .pf-method-option.is-active .pf-method-option-copy {
        color: #D6F1E0 !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.pf-actions, .payment-modal-close, .pf-btn.secondary) {
        background: var(--pf-surface) !important;
        border-color: var(--pf-border) !important;
        color: var(--pf-ink) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal :is(.payment-modal-close, .pf-btn.secondary):hover {
        background: var(--pf-raised) !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal .pf-btn.primary {
        background: #1B4332 !important;
        border-color: #41634F !important;
        color: #F8FAFC !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal .pf-btn.primary:hover {
        background: #245C42 !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal .pf-progress-track {
        background: #41634F !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal .pf-progress-bar {
        background: #8FD7B2 !important;
    }
    html[data-theme='dark'] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) #paymentFormModal #pf_amount_formatted {
        color: var(--pf-ink) !important;
    }

    @media (prefers-reduced-motion: reduce) {
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page) *,
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page) *::before,
        html[data-theme] body.panel-shell-body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.payment-monitoring-page)) :is(.records-page, .pm-page) *::after {
            scroll-behavior: auto !important;
            transition-duration: .01ms !important;
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
        }
    }
    /* Payment record values should read like the lighter case-records worklist. */
    html[data-theme] body.panel-shell-body:is(.staff-ui,.branch-admin-ui,.system-admin-ui) :is(.payment-monitoring-page, .pm-transactions-modal) :is(
        .pm-case-row .pm-case,
        .pm-case-row .pm-row-title,
        .pm-case-row .pm-row-date,
        .pm-status,
        .pm-summary-stat strong,
        .pm-trans-row .pm-trans-title,
        .pm-trans-stat strong,
        .pm-trans-count,
        .pm-txn-rec,
        .pm-txn-amt,
        .pm-txn-bal-val,
        .pm-txn-foot strong,
        .pm-txn-det-cell strong
    ) {
        font-weight: 500 !important;
    }
</style>
