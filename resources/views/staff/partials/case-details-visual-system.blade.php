<style>
@media screen {
    .case-detail-shell .cv-service-grid > :is(.cv-pkg-pair, .cv-addon-list) { grid-column: 1 / -1; width: 100%; padding: 8px 0; }
    /* Branch Admin uses the same visual tokens without inheriting staff permissions. */
    body:is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell) {
        --staff-ink: var(--ink);
        --staff-muted: var(--ink-muted);
        --staff-border: var(--border);
        --staff-card: var(--card);
        --staff-soft: var(--surface-muted);
        --staff-hover: var(--records-hover);
        --staff-action: var(--brand);
        --staff-accent: var(--brand);
    }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell) .panel-page-header {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }
    body:is(.branch-admin-ui,.system-admin-ui) .case-detail-shell .cv-hero-kicker { display: block !important; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap { --ink: var(--staff-ink); --ink-muted: var(--staff-muted); --border: var(--staff-border); font-family: var(--font-body); color: var(--staff-ink); }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap :is(.ec-hero, .ec-card, .ec-hero__top, .ec-card__head, .ec-footer) { background: var(--staff-card) !important; border-color: var(--staff-border) !important; color: var(--staff-ink); box-shadow: none; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap :is(.ec-meta__item, .ec-readonly, .ec-fin-card, .ec-code-pill, .ec-back, .ec-cancel-btn) { background: var(--staff-soft) !important; border-color: var(--staff-border) !important; color: var(--staff-ink) !important; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap :is(.form-input, select, textarea) { min-height: 44px; font-family: var(--font-body); color: var(--staff-ink) !important; background: var(--staff-card) !important; border-color: var(--staff-border) !important; border-radius: 8px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap :is(.ec-label, .ec-hint, .ec-meta__label, .ec-hero__sub, .ec-fin-label, .ec-fin-sub) { color: var(--staff-muted); letter-spacing: normal; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap :is(.ec-card__title, .ec-hero__title, .ec-meta__value) { font-family: var(--font-heading); color: var(--staff-ink); letter-spacing: normal; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap .ec-save-btn { background: #1B4332; border-color: #1B4332; color: white; min-height: 44px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap :is(input, select, textarea, a, button):focus-visible { outline: 2px solid var(--staff-action); outline-offset: 2px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap :is(.ec-fin-value, .ec-code-pill) { font-family: 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums; }
    html[data-theme='dark'] body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap :is(.ec-hint-warn, .ec-fin-card.is-discount .ec-fin-value) { color: #FBBF24; }
    html[data-theme='dark'] body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .ec-wrap :is(.ec-err, .ec-req) { color: #FCA5A5; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell :is(.cv-subsection-head, .cv-schedule-fields__head, .cv-schedule-fields, .cv-fin-item, .cv-addon-row, .cv-txn, .cv-doc-row, .cv-tarp-preview) { background: var(--staff-soft) !important; border-color: var(--staff-border) !important; color: var(--staff-ink); }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell :is(.cv-fin-label, .cv-txn-lbl, .cv-tarp-note) { color: var(--staff-muted) !important; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell :is(.cv-fin-value, .cv-txn-val) { color: var(--staff-ink) !important; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .panel-page-header { background: transparent !important; border: 0 !important; box-shadow: none !important; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .panel-page-header::after { display: none !important; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .panel-page-header__copy h1 { color: var(--staff-ink) !important; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .panel-page-header__copy p { color: var(--staff-muted) !important; }
    html[data-theme='dark'] body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .panel-page-header__copy p { color: var(--staff-accent) !important; }
    .case-back-icon { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; flex: 0 0 44px; color: var(--staff-ink); border: 1px solid var(--staff-border); border-radius: 8px; text-decoration: none; }
    .case-back-icon span { position: absolute; left: 0; top: calc(100% + 6px); z-index: 100; width: max-content; padding: 6px 10px; border-radius: 6px; background: var(--staff-ink); color: var(--staff-card); font-size: 12px; visibility: hidden; pointer-events: none; }
    .case-back-icon:is(:hover, :focus-visible) span { visibility: visible; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell {
        --ink: var(--staff-ink);
        --ink-muted: var(--staff-muted);
        --border: var(--staff-border);
        --brand: var(--staff-action);
        font-family: var(--font-body);
        color: var(--staff-ink);
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell #caseViewContent {
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-section-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 14px;
        padding: 6px;
        border: 1px solid var(--staff-border);
        border-radius: 12px;
        background: var(--staff-card);
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-section-nav a,
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-record-toolbar .btn-outline,
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-modify-btn {
        display: inline-flex;
        align-items: center;
        min-height: 44px;
        padding: 10px 14px;
        border: 1px solid var(--staff-border);
        border-radius: 8px;
        background: var(--staff-card);
        color: var(--staff-ink);
        font: 600 14px/1.5 var(--font-body);
        text-decoration: none;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-section-nav a:hover { background: var(--staff-hover); }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-section-nav a {
        border-color: transparent;
        background: transparent;
        white-space: nowrap;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) :is(.case-section-nav, .case-detail-shell, .case-record-toolbar) :is(a, button):focus-visible {
        outline: 2px solid var(--staff-action);
        outline-offset: 3px;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-print-btn {
        min-height: 44px;
        border-radius: 8px;
        background: #1B4332;
        border-color: #1B4332;
        color: #FFFFFF;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-record-toolbar { margin-bottom: 16px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-shell { gap: 16px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell [id^='case-'] { scroll-margin-top: 24px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-card {
        background: var(--staff-card) !important;
        border: 1px solid var(--staff-border) !important;
        border-radius: 12px;
        box-shadow: none;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-card-head {
        padding: 12px 16px;
        background: var(--staff-card) !important;
        border-color: var(--staff-border) !important;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-card-title {
        color: var(--staff-ink) !important;
        font: 700 16px/1.5 var(--font-heading);
        text-transform: none;
        letter-spacing: normal;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell :is(.cv-field, .cv-meta-item) {
        padding: 8px 0;
        background: transparent !important;
        border: 0 !important;
        border-radius: 0;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-fields { padding: 12px 16px; gap: 4px 20px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-section-people .cv-fields { align-content: start; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell :is(.cv-field-label, .cv-meta-label, .cv-stat-lbl) {
        color: var(--staff-muted) !important;
        font-size: 12px;
        line-height: 1.5;
        letter-spacing: normal;
        text-transform: none;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell :is(.cv-field-value, .cv-meta-value) {
        color: var(--staff-ink) !important;
        font-size: 14px;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell :is(.cv-stat, .cv-interment-badge, .cv-pkg-box) {
        background: var(--staff-soft) !important;
        border-color: var(--staff-border) !important;
        color: var(--staff-ink) !important;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell :is(.cv-stat-val, .cv-hero-case) {
        color: var(--staff-ink) !important;
        font-family: 'JetBrains Mono', Consolas, monospace;
        font-variant-numeric: tabular-nums;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-section-people { grid-template-columns: minmax(0, 1fr); gap: 16px; align-items: start; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-service-grid { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items: start; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-service-grid > .cv-field-package { grid-column: auto; grid-row: auto; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-service-grid > .cv-field-tarpaulin { grid-column: 1 / -1; grid-row: auto; align-self: start; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-hero-kicker { display: none; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-hero-body { padding: 16px 20px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-hero-top { margin-bottom: 12px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-hero-meta {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        padding-top: 10px;
        gap: 8px 20px;
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-meta-item { gap: 0; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-stat-row { gap: 10px; padding: 12px 16px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-stat { padding: 12px 14px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-stat-val { font-size: 16px; line-height: 1.35; overflow-wrap: anywhere; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-fin-grid { grid-template-columns: minmax(0, 1fr); gap: 0; padding: 0 16px 12px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-fin-item { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 8px 24px; padding: 10px 0; border: 0; border-bottom: 1px solid var(--staff-border); border-radius: 0; background: transparent; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-fin-item:nth-child(odd) { padding-right: 0; border-right: 0; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-fin-item:nth-child(even) { padding-left: 0; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-fin-item:last-child { border-bottom: 1px solid var(--staff-border); }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-tarp-grid { padding: 12px 16px; gap: 12px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-schedule-fields__grid { gap: 4px 20px; padding: 12px 16px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-tarp-preview { min-height: 100px; max-height: 120px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-field-tarpaulin {
        min-height: 0;
        margin: 0 16px 16px;
        border: 1px solid var(--staff-border) !important;
        border-radius: 10px;
        background: var(--staff-card) !important;
    }
    html.cv-tarp-modal-open, body.cv-tarp-modal-open { overflow: hidden !important; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal { z-index: 1100; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal[data-mode='details'] .cv-tarp-dialog { width: min(100%, 560px); max-height: min(80vh, 680px); }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal[data-mode='details'] .cv-tarp-modal-body { display: grid; gap: 16px; padding: 16px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal[data-mode='details'] [data-tarpaulin-details-body] { display: grid; gap: 16px; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal[data-mode='details'] .cv-tarp-modal-body > img { display: none; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal[data-mode='photo'] [data-tarpaulin-details-body] { display: none; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal[data-mode='details'] .cv-tarp-meta { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal :is(.cv-tarp-meta, .cv-tarp-form, .cv-tarp-file) {
        background: var(--staff-soft) !important;
        border-color: var(--staff-border) !important;
        color: var(--staff-ink);
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal :is(.cv-tarp-dialog, .cv-tarp-modal-head, .cv-tarp-modal-foot) {
        background: var(--staff-card) !important;
        border-color: var(--staff-border) !important;
        color: var(--staff-ink);
    }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal .cv-tarp-modal-body { background: var(--staff-soft) !important; overflow-y: auto; }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-section-people > .cv-card:first-child .cv-fields,
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-section-people > .cv-card:nth-child(2) .cv-fields { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-section-people > .cv-card:first-child .cv-field:last-child,
    body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-section-people > .cv-card:nth-child(2) .cv-field-full { grid-column: auto; }
    @media (min-width: 1000px) {
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-schedule-fields__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-schedule-fields__grid > .cv-field-full { grid-column: auto; }
    }
    @media (max-width: 760px) {
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-section-nav { flex-wrap: nowrap; overflow-x: auto; overscroll-behavior-x: contain; }
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-hero-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-section-people > .cv-card:first-child .cv-fields,
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-section-people > .cv-card:nth-child(2) .cv-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-fields-2 { grid-template-columns: 1fr; }
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-fields-2 .cv-field-full { grid-column: auto; }
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-interment-badge { white-space: normal; flex-wrap: wrap; }
    }
    @media (max-width: 520px) {
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .cv-tarp-modal[data-mode='details'] .cv-tarp-meta { grid-template-columns: 1fr; }
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-section-people > .cv-card:first-child .cv-fields,
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-section-people > .cv-card:nth-child(2) .cv-fields { grid-template-columns: 1fr; }
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-hero-meta,
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-stat-row,
        body:is(.staff-ui, :is(.branch-admin-ui,.system-admin-ui):has(.case-detail-shell), .system-admin-ui:has(.ec-wrap)) .case-detail-shell .cv-fin-grid { grid-template-columns: 1fr; }
    }
}
</style>
