<style>
/* Shared presentation only. Chart canvases/configuration and report data remain owned by existing views. */
html[data-theme] body .reports-page[data-reports-module-shell] { background:transparent !important; }
html[data-theme] body .reports-page .reports-card.reports-filter-card { display:grid !important; grid-template-columns:minmax(0,1fr) !important; align-items:start !important; gap:16px !important; }
html[data-theme] body .reports-page .reports-card.reports-filter-card > .reports-module-subtabs { display:grid !important; width:100% !important; grid-template-columns:repeat(3,minmax(0,1fr)) !important; }
html[data-theme] body .reports-page .reports-card.reports-filter-card > .reports-card-title { margin:0 !important; }
html[data-theme] body .reports-page .reports-config-form { width:100% !important; }
@media(max-width:600px) { html[data-theme] body .reports-page .reports-card.reports-filter-card > .reports-module-subtabs { grid-template-columns:minmax(0,1fr) !important; } }

html[data-theme='dark'] body:has([data-reports-module-shell]) .page-content { background:#07140E !important; }
html[data-theme]:not([data-theme='dark']) body:has([data-reports-module-shell]) .page-content { background:#DCE9E0 !important; }
html[data-theme] body:has([data-reports-module-shell]) .panel-page-header__copy :is(h1,p) { color:var(--filter-ui-text) !important; }
html body .ba-shell .ba-global-filterbar[hidden] { display:none !important; }
html body .ba-shell .ba-global-filterbar { background:var(--filter-ui-menu) !important; border:1px solid var(--filter-ui-border) !important; border-radius:12px !important; padding:12px !important; }
html body .ba-shell .ba-global-clear { background:var(--filter-ui-surface) !important; color:var(--filter-ui-text) !important; }
html body :is(.ba-shell,.reports-page) .reports-module-main { padding:0 !important; display:grid !important; gap:24px !important; }
html body .ba-shell .ba-card.ba-workspace { display:flex !important; flex-direction:column !important; gap:20px !important; }
html body .ba-shell .ba-workspace > .reports-module-subtabs { display:flex !important; flex-wrap:nowrap !important; align-self:stretch !important; justify-content:flex-start !important; gap:8px !important; padding:0 0 8px !important; border-bottom:1px solid var(--filter-ui-border) !important; }
html body .reports-page .reports-filter-card > .reports-module-subtabs { display:grid !important; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px !important; padding:16px 0 !important; }
html body .reports-page .reports-filter-card > .reports-module-subtabs button { min-height:56px !important; white-space:normal !important; text-align:left !important; border:1px solid var(--filter-ui-border) !important; border-radius:12px !important; }
html[data-theme='dark'] body .ba-shell :is(.ba-performance-card .ba-chart-frame,.ba-panel.active > .ba-chart-frame) { background:#16291F !important; border:1px solid var(--ra-border) !important; }
html body .ba-shell .ba-drilldown { margin-top:0 !important; }
@media(max-width:600px) { html body .reports-page .reports-filter-card > .reports-module-subtabs { grid-template-columns:minmax(0,1fr); } }
html[data-theme] body:has([data-reports-module-shell]) :is(.panel-page-header,.panel-page-header h1,.panel-page-header p) { background:transparent !important; color:var(--filter-ui-text) !important; }
html body :is(.ba-shell,.reports-page) { background:transparent !important; }
html body :is(.ba-shell,.reports-page) .reports-module-layout { background:transparent !important; border:0 !important; margin-inline:0 !important; }
html body :is(.ba-shell,.reports-page) .reports-module-main { background:transparent !important; }
html body :is(.ba-shell,.reports-page) :is(.reports-module-tab,.ba-tab-btn) { background:var(--filter-ui-menu) !important; color:var(--filter-ui-text) !important; border-color:var(--filter-ui-border) !important; }
html body :is(.ba-shell,.reports-page) :is(.reports-module-tab.active,.ba-tab-btn.active) { background:var(--filter-ui-surface) !important; color:var(--filter-ui-text) !important; }
html body .ba-shell :is(.ba-global-filter-bar,.ba-global-filter-chips) { background:transparent !important; }
html body .ba-shell { --module-surface:var(--filter-ui-menu); --module-surface-soft:var(--filter-ui-menu); --module-warm:var(--filter-ui-menu); --module-text:var(--filter-ui-text); --module-muted:var(--filter-ui-muted); --module-border:var(--filter-ui-border); }
html body :is(.ba-shell,.reports-page) .reports-module-header { background:transparent !important; border:0 !important; }
html body .ba-shell .ba-panel.active { background:var(--filter-ui-menu) !important; border-color:var(--filter-ui-border) !important; border-radius:16px !important; padding:20px !important; }
html body .ba-shell .ba-card.ba-workspace { background:transparent !important; border:0 !important; padding:0 !important; }
html body :is(.ba-shell,.reports-page) { --ra-surface:var(--filter-ui-menu) !important; --ra-muted:var(--filter-ui-surface) !important; --ra-border:var(--filter-ui-border) !important; --ra-text:var(--filter-ui-text) !important; --ra-secondary:var(--filter-ui-muted) !important; font-family:'Plus Jakarta Sans','Segoe UI',sans-serif !important; }
html body .ba-shell .ba-workspace { background:transparent !important; border:0 !important; box-shadow:none !important; padding:0 !important; }
html body .ba-shell :is(.ba-performance-card,.ba-compare-card,.ba-drilldown) { padding:20px !important; border:1px solid var(--ra-border) !important; border-radius:16px !important; background:var(--ra-surface) !important; box-shadow:none !important; }
html body .ba-shell :is(.ba-panel-head,.ba-compare-title) { gap:12px !important; margin-bottom:16px !important; }
html body .ba-shell :is(.ba-panel-title,.ba-compare-title) { font-size:16px !important; color:var(--ra-text) !important; }
html body .ba-shell :is(.ba-panel-subtitle,.ba-panel-note) { font-size:13px !important; color:var(--ra-secondary) !important; }
html body .ba-shell .ba-compare-table-wrap { overflow-x:auto !important; border:1px solid var(--ra-border) !important; border-radius:12px !important; }
html body .ba-shell .ba-compare-table :is(th,td) { padding:12px 16px !important; font-size:14px !important; border-color:var(--ra-border) !important; }
html body .ba-shell .ba-compare-table th { background:var(--ra-muted) !important; color:var(--ra-secondary) !important; }
html body .ba-shell .ba-compare-table td { background:var(--ra-surface) !important; color:var(--ra-text) !important; }
html body .ba-shell .reports-module-subtabs { justify-self:start !important; max-width:100% !important; overflow-x:auto !important; background:transparent !important; }
html body .ba-shell .ba-tab-btn { min-height:44px !important; padding:10px 16px !important; font-size:14px !important; border-radius:8px !important; white-space:nowrap !important; }
html body .ba-chart-modal .ba-chart-modal__dialog { border-radius:16px !important; font-family:'Plus Jakarta Sans','Segoe UI',sans-serif !important; }
html body .ba-chart-modal .ba-chart-modal__close { min-width:44px !important; min-height:44px !important; }
html body .reports-page .reports-card { padding:20px !important; border:1px solid var(--ra-border) !important; border-radius:16px !important; background:var(--ra-surface) !important; box-shadow:none !important; }
html body .reports-page .reports-card-title { font-size:16px !important; color:var(--ra-text) !important; }
html body .reports-page .reports-card-copy { font-size:13px !important; line-height:1.5 !important; color:var(--ra-secondary) !important; }
html body .reports-page .reports-summary-grid { gap:16px !important; }
html body .reports-page .reports-metric { position:relative !important; padding:20px !important; padding-right:64px !important; border:1px solid var(--ra-border) !important; border-radius:12px !important; background:var(--ra-muted) !important; }
html body .reports-page .reports-metric-icon { position:absolute !important; right:16px !important; top:16px !important; }
html body .reports-page .reports-metric-label { font-size:12px !important; color:var(--ra-secondary) !important; }
html body .reports-page .reports-metric-value { margin-top:8px !important; font-size:24px !important; line-height:1.3 !important; font-variant-numeric:tabular-nums !important; overflow-wrap:anywhere !important; color:var(--ra-text) !important; }


/* Reference composition: separate scope toolbar, KPI row, section tabs and chart/breakdown card. */
html body :is(.ba-shell,.reports-page) .reports-module-header {
    display:grid !important;
    grid-template-columns:minmax(0,1fr) !important;
    gap:16px !important;
    padding:0 0 24px !important;
}
html body :is(.ba-shell,.reports-page) .reports-module-primary-tabs {
    grid-column:1 !important;
    justify-self:end !important;
    padding:4px !important;
    border:1px solid var(--ra-border) !important;
    border-radius:12px !important;
    background:var(--ra-muted) !important;
}
html body :is(.ba-shell,.reports-page) .reports-module-primary-tabs .is-active {
    background:#1B4435 !important;
    color:#fff !important;
}
html body :is(.ba-shell,.reports-page) .reports-module-controls {
    grid-column:1 !important;
    grid-row:auto !important;
    justify-content:flex-start !important;
    padding:16px !important;
    border:1px solid var(--ra-border) !important;
    border-radius:16px !important;
    background:var(--ra-surface) !important;
    gap:12px !important;
}
html body .ba-shell .ba-workspace > .reports-module-subtabs .ba-tab-btn {
    background:transparent !important;
    border:0 !important;
    border-radius:12px 12px 0 0 !important;
}
html body .ba-shell .ba-workspace > .reports-module-subtabs .ba-tab-btn.active {
    background:var(--ra-surface) !important;
}
html body .ba-shell .ba-panel.active > :is(.ba-performance-card,.ba-compare-card) {
    background:transparent !important;
    border:0 !important;
    padding:0 !important;
    box-shadow:none !important;
}
html body .ba-shell .ba-panel.active > .ba-compare-card { margin-top:24px !important; }
@media(max-width:600px) {
    html body :is(.ba-shell,.reports-page) .reports-module-controls > :is(form,label) { width:100% !important; min-width:0 !important; }
    html body :is(.ba-shell,.reports-page) .reports-module-controls select { width:100% !important; max-width:none !important; min-width:0 !important; }
    html body .ba-shell .ba-panel-head { flex-wrap:wrap !important; }
    html body .ba-shell .ba-compare-table { min-width:640px !important; }
}
/* The workspace heading and controls follow the supplied reference composition. */
html[data-theme] body :is(.ba-shell,.reports-page) { --ra-surface:#F4F8F5 !important; --ra-muted:#D0E2D6 !important; --ra-border:#AEC7B7 !important; --ra-text:#081D13 !important; --ra-secondary:#1B3E2E !important; }
html[data-theme='dark'] body :is(.ba-shell,.reports-page) { --ra-surface:#112219 !important; --ra-muted:#0A1A12 !important; --ra-border:#254638 !important; --ra-text:#F1F6F3 !important; --ra-secondary:#B8CFC3 !important; }
html[data-theme] body .reports-page .reports-card { background:var(--ra-surface) !important; }
html body :is(.ba-shell,.reports-page) .reports-reference-heading {
    display:flex; align-items:center; justify-content:space-between; gap:20px;
    padding:24px; border:1px solid var(--ra-border); border-radius:20px;
    background:var(--ra-surface);
}
html body :is(.ba-shell,.reports-page) .reports-module-title-block { display:block; min-width:0; }
html body :is(.ba-shell,.reports-page) .reports-module-title { font-size:clamp(24px,2vw,32px) !important; line-height:1.2; color:var(--ra-text) !important; }
html body :is(.ba-shell,.reports-page) .reports-module-subtitle { font-size:14px; line-height:1.5; color:var(--ra-secondary) !important; }
html body :is(.ba-shell,.reports-page) .reports-reference-scope { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:8px; }
html body :is(.ba-shell,.reports-page) :is(.reports-reference-branch,.reports-reference-lock) { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; font-size:12px; line-height:1.4; }
html body :is(.ba-shell,.reports-page) .reports-reference-branch { background:#1B4332; color:white; border-radius:20px; font-weight:700; }
html body :is(.ba-shell,.reports-page) .reports-reference-lock { background:var(--ra-muted); color:var(--ra-text); border:1px solid var(--ra-border); border-radius:6px; }
html body .ba-shell .reports-reference-period { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
html body .ba-shell .reports-reference-date-label { display:inline-flex; align-items:center; gap:8px; font-size:14px; font-weight:700; color:var(--ra-text); }
html body .ba-shell .reports-reference-native-period { position:absolute; width:1px; height:1px; padding:0; overflow:hidden; clip-path:inset(50%); pointer-events:none; }
html body .ba-shell .reports-reference-date-pills { display:flex; gap:4px; padding:4px; border:1px solid var(--ra-border); border-radius:10px; background:var(--ra-muted); }
html body .ba-shell .reports-reference-date-pills button { min-height:36px; padding:6px 12px; border:0; border-radius:7px; background:transparent !important; font:inherit; font-size:14px; font-weight:700; color:var(--ra-text) !important; cursor:pointer; }
html body .ba-shell .reports-reference-date-pills button.is-active { background:#1B4332 !important; color:white !important; }
html body .ba-shell .reports-module-controls { justify-content:space-between !important; }
html body .reports-page .reports-filter-card > .reports-module-controls { padding:0 !important; border:0 !important; background:transparent !important; }
html body .reports-page .reports-reference-config-actions { display:flex; align-items:center; justify-content:flex-end; flex-wrap:wrap; gap:12px; padding-top:16px; border-top:1px solid var(--ra-border); }
html body .reports-page .reports-filter-card > .reports-card-copy { margin:0; }
html body .reports-page .reports-reference-config-actions .reports-btn { min-height:44px; }
html body .reports-page .reports-filter-card > .reports-module-subtabs button { justify-content:flex-start !important; padding:16px !important; min-height:72px !important; }
html body .ba-shell .ba-workspace > .reports-module-subtabs { gap:0 !important; padding-bottom:0 !important; }
html body .ba-shell .ba-workspace > .reports-module-subtabs .ba-tab-btn { flex:1; justify-content:flex-start; padding:14px 16px !important; }
html body .ba-shell .ba-drilldown { margin-top:24px !important; }
html body .ba-shell .ba-chart-frame { border-radius:14px !important; }
html body .ba-shell .ba-compare-title { padding-top:8px; font-weight:700; }
html body :is(.ba-shell,.reports-page) button:focus-visible { outline:2px solid var(--ra-text); outline-offset:3px; }
@media(max-width:767px) {
    html body :is(.ba-shell,.reports-page) .reports-reference-heading { align-items:flex-start; flex-direction:column; padding:20px; }
    html body :is(.ba-shell,.reports-page) .reports-reference-heading .reports-module-primary-tabs { align-self:flex-start; }
    html body .ba-shell .reports-reference-date-pills button { padding:6px 10px; }
    html body .ba-shell .reports-reference-period { width:100%; }
    html body .ba-shell .ba-workspace > .reports-module-subtabs .ba-tab-btn { flex:0 0 auto; }
}
html body .ba-shell .ba-workspace > .reports-module-subtabs .ba-tab-btn { min-height:44px !important; font-size:13px !important; padding:10px 14px !important; color:var(--ra-secondary) !important; }
html body .ba-shell .ba-workspace > .reports-module-subtabs .ba-tab-btn.active { color:var(--ra-text) !important; font-weight:700 !important; }
html body .ba-shell .ba-workspace > .reports-module-subtabs .ba-tab-btn.active::after { background:#24866D !important; height:3px !important; }
html body .ba-shell .reports-reference-applied-dates { font-size:12px; color:var(--ra-secondary); }
html body .ba-shell .reports-custom-date-dialog { width:min(460px,calc(100vw - 32px)); padding:0; color:var(--ra-text); background:var(--ra-surface); border:1px solid var(--ra-border); border-radius:16px; }
html body .ba-shell .reports-custom-date-dialog::backdrop { background:rgba(15,23,42,.72); }
html body .ba-shell .reports-custom-date-dialog :is(header,footer) { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 20px; }
html body .ba-shell .reports-custom-date-dialog header { border-bottom:1px solid var(--ra-border); }
html body .ba-shell .reports-custom-date-dialog h2 { margin:0; font-size:18px; color:var(--ra-text); }
html body .ba-shell .reports-custom-date-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; padding:20px; }
html body .ba-shell .reports-custom-date-fields label { display:grid; gap:8px; font-size:13px; font-weight:600; }
html body .ba-shell .reports-custom-date-fields input { width:100%; min-width:0; min-height:44px; padding:8px; color:var(--ra-text); background:var(--ra-muted); border:1px solid var(--ra-border); border-radius:8px; color-scheme:light; }
html[data-theme='dark'] body .ba-shell .reports-custom-date-fields input { color-scheme:dark; }
html body .ba-shell .reports-custom-date-dialog button { min-height:44px; padding:8px 14px; border:1px solid var(--ra-border); border-radius:8px; color:var(--ra-text); background:var(--ra-muted); cursor:pointer; }
html body .ba-shell .reports-custom-date-dialog button[type='submit'] { background:#1B4332; color:white; }
html body .ba-shell .reports-custom-date-dialog footer { justify-content:flex-end; border-top:1px solid var(--ra-border); }
html body:has(.reports-custom-date-dialog[open]),html body:has(.reports-custom-date-dialog[open]) .page-content { overflow:hidden !important; }
@media(max-width:600px) {
    html body .ba-shell .reports-reference-date-pills { flex-wrap:wrap; }
    html body .ba-shell .reports-custom-date-fields { grid-template-columns:minmax(0,1fr); }
}
</style>
<style>
/* Supplied reference: payment split layout and full-width underline navigation. */
html body .ba-shell .ba-payment-layout { display:grid; grid-template-columns:minmax(0,5fr) minmax(0,7fr); gap:28px; align-items:center; }
html body .ba-shell .reports-reference-scope-badge { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid var(--ra-border); border-radius:8px; background:var(--ra-surface); font-size:12px; color:var(--ra-text); }
html body .ba-shell .ba-payment-expand { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; min-height:38px; border:1px solid var(--ra-border); border-radius:10px; color:var(--ra-text); background:var(--ra-surface); font:inherit; font-size:12px; cursor:pointer; }
html body .ba-shell .ba-payment-chart-column { min-width:0; border:1px solid var(--ra-border); border-radius:14px; background:var(--ra-surface); padding:20px; }
html body .ba-shell .ba-payment-chart-column .ba-chart-frame { position:relative; height:300px !important; min-height:0 !important; width:100% !important; max-width:none !important; border:0 !important; padding:0 !important; background:transparent !important; }
html body .ba-shell .ba-payment-center { position:absolute; inset:0; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:4px; pointer-events:none; color:var(--ra-text); }
html body .ba-shell .ba-payment-center > span { font-size:10px; text-transform:uppercase; color:var(--ra-secondary); }
html body .ba-shell .ba-payment-center strong { font-size:28px; }
html body .ba-shell .ba-payment-center small,.ba-payment-chart-hint { font-size:11px; color:var(--ra-secondary); text-align:center; }
html body .ba-shell .ba-payment-status-cards { display:grid; gap:14px; min-width:0; }
html body .ba-shell .ba-payment-status-card { width:100%; display:flex; align-items:center; gap:14px; padding:18px; border:1px solid var(--status-border); background:var(--status-bg); color:var(--status-text); border-radius:14px; text-align:left; font:inherit; cursor:pointer; }
html body .ba-shell .ba-payment-status-card.tone-green { --status-bg:#ECFDF5; --status-border:#6EE7B7; --status-text:#064E3B; --status-dot:#1B4332; }
html body .ba-shell .ba-payment-status-card.tone-amber { --status-bg:#FFFBEB; --status-border:#FCD34D; --status-text:#92400E; --status-dot:#D97706; }
html body .ba-shell .ba-payment-status-card.tone-red { --status-bg:#FFF1F2; --status-border:#FDA4AF; --status-text:#991B1B; --status-dot:#DC2626; }
html[data-theme='dark'] body .ba-shell .ba-payment-status-card { --status-bg:#152A20; --status-text:#D5EDE1; }
html[data-theme='dark'] body .ba-shell .ba-payment-status-card.tone-amber { --status-bg:#2B2415; --status-text:#FDE1A7; }
html[data-theme='dark'] body .ba-shell .ba-payment-status-card.tone-red { --status-bg:#2E1C20; --status-text:#FFC9CF; }
html body .ba-shell .ba-payment-status-card:hover { filter:brightness(.97); }
html body .ba-shell .ba-payment-status-card.is-selected { outline:2px solid var(--status-border); outline-offset:2px; }
html body .ba-shell .ba-payment-status-dot { width:18px; height:18px; flex:0 0 18px; border-radius:50%; background:var(--status-dot); }
html body .ba-shell .ba-payment-status-copy { display:grid; gap:5px; flex:1; }
html body .ba-shell .ba-payment-status-copy strong { font-size:12px; text-transform:uppercase; }
html body .ba-shell .ba-payment-status-copy :is(span,small) { font-size:11px; }
html body .ba-shell .ba-payment-status-count { display:grid; gap:4px; text-align:right; }
html body .ba-shell .ba-payment-status-count strong { font-size:22px; font-variant-numeric:tabular-nums; white-space:nowrap; }
html body .ba-shell .ba-payment-status-count small { font-size:11px; text-decoration:underline; }
html body .ba-shell .ba-payment-settlement { margin-top:24px; }
html body .ba-shell .ba-payment-settlement > div:first-child { display:flex; justify-content:space-between; gap:12px; font-size:12px; margin-bottom:8px; }
html body .ba-shell .ba-payment-settlement-track { display:flex; height:14px; border:1px solid var(--ra-border); border-radius:20px; background:var(--ra-muted); overflow:hidden; }
.ba-payment-settlement-track .is-paid { background:#1B4332; }.ba-payment-settlement-track .is-partial { background:#D97706; }.ba-payment-settlement-track .is-unpaid { background:#DC2626; }
html body .ba-shell .ba-workspace > .reports-module-subtabs { width:100%; border:0 !important; border-bottom:1px solid var(--ra-border) !important; border-radius:0 !important; background:transparent !important; overflow-x:auto; }
html body .ba-shell .ba-workspace > .reports-module-subtabs .ba-tab-btn { border:0 !important; border-radius:14px 14px 0 0 !important; background:transparent !important; min-width:max-content; }
html body .ba-shell .ba-workspace > .reports-module-subtabs .ba-tab-btn.active { background:var(--ra-surface) !important; }
html body .ba-shell .ba-workspace > .reports-module-subtabs .ba-tab-btn.active::after { left:0 !important; right:0 !important; bottom:0 !important; height:2px !important; border-radius:0 !important; background:#1B4332 !important; }
@media(max-width:900px) { html body .ba-shell .ba-payment-layout { grid-template-columns:minmax(0,1fr); } }
@media(max-width:480px) { html body .ba-shell .ba-payment-status-card { flex-wrap:wrap; padding:14px; }.ba-payment-status-count { margin-left:32px; } }
/* Reference hover card lives outside chart overflow containers and never intercepts clicks. */
.analytics-hover-tooltip { position:fixed; z-index:10050; pointer-events:none; width:max-content; max-width:min(320px,calc(100vw - 24px)); padding:12px; background:#0F172A; color:#F8FAFC; border:1px solid #475569; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,.22); font-family:'Plus Jakarta Sans','Segoe UI',sans-serif; font-size:12px; line-height:1.5; }
.analytics-hover-tooltip[hidden] { display:none; }
.analytics-hover-tooltip__title { color:#A7F3D0; font-weight:700; border-bottom:1px solid #334155; padding-bottom:5px; margin-bottom:5px; overflow-wrap:anywhere; }
.analytics-hover-tooltip__subtitle { color:#CBD5E1; font-size:11px; margin-bottom:5px; overflow-wrap:anywhere; }
.analytics-hover-tooltip__row { display:flex; align-items:baseline; gap:8px; padding-top:3px; }
.analytics-hover-tooltip__marker { flex:0 0 8px; width:8px; height:8px; background:#94A3B8; border-radius:2px; }
.analytics-hover-tooltip__label { color:#CBD5E1; flex:1; overflow-wrap:anywhere; }
.analytics-hover-tooltip__value { color:#F8FAFC; font-variant-numeric:tabular-nums; text-align:right; overflow-wrap:anywhere; }
</style>
