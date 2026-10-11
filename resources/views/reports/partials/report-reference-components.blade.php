<style>
html[data-theme] body .reports-page .reports-metric-help { margin:10px 0 0 !important; font-size:12px !important; font-weight:400 !important; line-height:1.5 !important; color:var(--ra-secondary) !important; text-transform:none !important; letter-spacing:0 !important; }
/* Amount paid is not a fully-paid status: use text emphasis instead of a filled column. */
html[data-theme] body .reports-page .reports-table tbody td.reports-col-total_paid { background:var(--ra-surface) !important; color:#237448 !important; }
html[data-theme='dark'] body .reports-page .reports-table tbody td.reports-col-total_paid { color:#81DBAA !important; }
html[data-theme] body .reports-page .reports-table tbody tr:hover td.reports-col-total_paid { background:var(--ra-muted) !important; }
html[data-theme] body .reports-page .reports-reference-config-actions { display:flex !important; align-items:center !important; flex-wrap:wrap !important; }
html[data-theme] body .reports-page .reports-reference-config-actions > .reports-module-controls { display:flex !important; flex:1 1 480px !important; flex-wrap:wrap !important; gap:12px !important; padding:0 !important; border:0 !important; background:transparent !important; margin-right:auto !important; position:relative; z-index:10; overflow:visible !important; }
html[data-theme] body .reports-page .reports-reference-config-actions .reports-module-control-wrap { flex:1 1 200px !important; min-width:190px !important; max-width:300px; }
.reports-date-dialog { width:min(480px,calc(100vw - 32px)); margin:auto; padding:0; border:1px solid var(--ra-border); border-radius:16px; background:var(--ra-surface); color:var(--ra-text); font-family:inherit; }
.reports-date-dialog::backdrop { background:var(--modal-scrim,rgba(15,23,42,.72)); }
.reports-date-dialog :is(header,footer) { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 20px; }
.reports-date-dialog header { border-bottom:1px solid var(--ra-border); }.reports-date-dialog h2 { font-size:18px; margin:0; }
.reports-date-dialog-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; padding:20px; }
.reports-date-dialog-fields label { display:grid; gap:8px; font-size:13px; font-weight:600; }
.reports-date-dialog-fields input { min-width:0; width:100%; min-height:44px; padding:8px; border:1px solid var(--ra-border); border-radius:8px; color:var(--ra-text); background:var(--ra-muted); color-scheme:light; }
html[data-theme='dark'] .reports-date-dialog-fields input { color-scheme:dark; }
.reports-date-dialog button { min-height:44px; padding:8px 14px; border:1px solid var(--ra-border); border-radius:8px; background:var(--ra-muted); color:var(--ra-text); cursor:pointer; font:inherit; font-size:13px; }
.reports-date-dialog button[type='submit'] { background:#1B4332; color:white; }
.reports-date-dialog footer { justify-content:flex-end; border-top:1px solid var(--ra-border); }
body:has(.reports-date-dialog[open]),body:has(.reports-date-dialog[open]) .page-content { overflow:hidden !important; }
@media(max-width:600px) { .reports-date-dialog-fields { grid-template-columns:minmax(0,1fr); } }
/* Preview is a compact report surface; the page owns vertical scrolling. */
html[data-theme] body .reports-page .reports-preview-card { padding:20px !important; min-height:0 !important; height:auto !important; background:var(--ra-surface) !important; }
html[data-theme] body .reports-page .reports-preview-head { padding:0 0 16px !important; margin:0 !important; align-items:flex-start !important; border-bottom:1px solid var(--ra-border) !important; }
html[data-theme] body .reports-page .reports-preview-head .reports-card-title { margin:0 0 4px !important; font-size:18px !important; }
html[data-theme] body .reports-page .reports-preview-body { padding:16px 0 0 !important; min-height:0 !important; height:auto !important; }
html[data-theme] body .reports-page .reports-table-wrap { padding:0 !important; margin:0 !important; background:transparent !important; border:1px solid var(--ra-border) !important; border-radius:12px !important; max-height:none !important; overflow-x:auto !important; }
html[data-theme] body .reports-page .reports-table { width:100%; border-collapse:collapse !important; margin:0 !important; border:0 !important; }
html[data-theme] body .reports-page .reports-table th { position:static !important; padding:12px 14px !important; font-size:11px !important; font-weight:700 !important; background:var(--ra-muted) !important; border:0 !important; border-bottom:1px solid var(--ra-border) !important; }
html[data-theme] body .reports-page .reports-table td { padding:12px 14px !important; font-size:13px !important; background:var(--ra-surface) !important; border:0 !important; border-bottom:1px solid var(--ra-border) !important; }
html[data-theme] body .reports-page .reports-table tbody tr:last-child td { border-bottom:0 !important; }
html[data-theme] body .reports-page .reports-table td.reports-cell-number { font-variant-numeric:tabular-nums; }
html[data-theme] body .reports-page .reports-table tbody tr:hover td { background:var(--ra-muted) !important; }
/* Dropdown menus must paint above the following chips and preview cards. */
html[data-theme] body .reports-page .reports-card.reports-filter-card { position:relative !important; overflow:visible !important; z-index:2; }
html[data-theme] body .reports-page .reports-filter-card > .reports-module-controls { position:relative !important; z-index:10 !important; overflow:visible !important; }
html[data-theme] body .reports-page .reports-filter-card .reports-module-control-wrap { position:relative; overflow:visible !important; }
html[data-theme] body .reports-page .reports-filter-card .global-filter-select-wrap:has(select[aria-expanded='true']) { z-index:30 !important; }
html[data-theme] body .reports-page .reports-filter-card .filter-select-menu { z-index:40 !important; }
/* Report configuration owns its spacing and selected state independently of analytics tabs. */
html[data-theme] body .reports-page .reports-card.reports-filter-card { padding:24px !important; border-radius:20px !important; background:var(--ra-surface) !important; gap:14px !important; }
html[data-theme] body .reports-page .reports-filter-card > .reports-card-title { font-size:18px !important; margin:0 !important; }
html[data-theme] body .reports-page .reports-filter-card > .reports-card-copy { margin:0 !important; font-size:13px !important; font-weight:400 !important; }
html[data-theme] body .reports-page .reports-card.reports-filter-card > .reports-module-subtabs { margin:4px 0 !important; padding:0 !important; gap:12px !important; border:0 !important; }
html[data-theme] body .reports-page .reports-card.reports-filter-card > .reports-module-subtabs > button { position:relative; min-height:64px !important; padding:14px 36px 14px 16px !important; border:1px solid var(--ra-border) !important; border-radius:12px !important; background:transparent !important; color:var(--ra-secondary) !important; font-size:13px !important; font-weight:600 !important; box-shadow:none !important; }
html[data-theme] body .reports-page .reports-card.reports-filter-card > .reports-module-subtabs > button::after { display:none !important; }
html[data-theme] body .reports-page .reports-card.reports-filter-card > .reports-module-subtabs > button:hover { background:var(--ra-muted) !important; }
html[data-theme] body .reports-page .reports-card.reports-filter-card > .reports-module-subtabs > button.is-active { background:#1B4332 !important; border-color:#1B4332 !important; color:white !important; }
html[data-theme] body .reports-page .reports-card.reports-filter-card > .reports-module-subtabs > button.is-active::after { display:block !important; content:'✓'; position:absolute; top:50%; right:14px; left:auto !important; bottom:auto !important; transform:translateY(-50%); width:auto !important; height:auto !important; background:transparent !important; color:white; font-size:16px; }
html[data-theme] body .reports-page .reports-filter-card > .reports-module-controls { display:flex !important; flex-wrap:wrap !important; align-items:center !important; justify-content:flex-start !important; gap:12px !important; margin:0 !important; }
html[data-theme] body .reports-page .reports-filter-card .reports-module-control-wrap { flex:1 1 240px !important; min-width:220px !important; max-width:360px; }
html[data-theme] body .reports-page .reports-filter-card .reports-module-control-wrap select { width:100% !important; min-width:0 !important; max-width:none !important; height:44px !important; font-size:13px !important; text-overflow:clip !important; }
html[data-theme] body .reports-page .reports-filter-card .reports-config-toolbar { display:block !important; margin:0 !important; padding:0 !important; min-height:0 !important; }
html[data-theme] body .reports-page .reports-filter-card .reports-filter-grid { margin:0 !important; gap:0 !important; }
html[data-theme] body .reports-page .reports-filter-card .reports-toolbar-trailing { display:flex !important; align-items:center !important; justify-content:space-between !important; flex-wrap:wrap !important; gap:12px !important; margin:0 !important; padding:0 !important; }
html[data-theme] body .reports-page .reports-reference-config-actions { margin:0 !important; padding-top:16px !important; gap:10px !important; }
html[data-theme] body .reports-page .reports-filter-reset { margin-left:auto; }
html[data-theme] body .reports-page .reports-reference-config-actions .reports-btn { min-height:42px !important; font-size:13px !important; }
@media(max-width:600px) {
    html[data-theme] body .reports-page .reports-card.reports-filter-card { padding:16px !important; }
    html[data-theme] body .reports-page .reports-filter-card .reports-module-control-wrap { flex-basis:100% !important; min-width:0 !important; max-width:none; }
    html[data-theme] body .reports-page .reports-reference-config-actions > :is(button,details) { flex:1; }
    html[data-theme] body .reports-page .reports-reference-config-actions summary { justify-content:center; }
}
</style>
