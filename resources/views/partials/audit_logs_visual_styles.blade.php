<style>
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-filter-menu > summary { height:44px; box-sizing:border-box; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); font-size:14px; font-weight:650; padding:0 16px; }
.audit-date-dialog { width:min(480px,calc(100vw - 32px)); max-height:calc(100dvh - 48px); margin:auto; padding:0; overflow:auto; border:1px solid var(--border); border-radius:12px; background:var(--card); color:var(--ink); font-family:var(--font-body); }
.audit-date-dialog::backdrop { background:var(--modal-scrim,rgba(15,23,42,.72)); }
.audit-date-dialog:not([open]) { display:none !important; }
.audit-date-dialog-head { display:flex; justify-content:space-between; gap:16px; padding:20px; border-bottom:1px solid var(--border); }
.audit-date-dialog-head h2 { font-size:20px; font-weight:700; margin:0; }
.audit-date-dialog-head p { font-size:14px; color:var(--ink-muted); margin-top:4px; }
.audit-date-dialog button { min-height:44px; padding:0 16px; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); color:var(--ink); }
.audit-date-dialog-head button { width:44px; flex-shrink:0; padding:0; }
.audit-date-dialog-body { padding:20px; }
.audit-date-dialog .audit-date-fields { grid-template-columns:repeat(2,minmax(0,1fr)); }
.audit-date-dialog label { display:grid; gap:8px; min-width:0; font-size:12px; color:var(--ink-muted); }
.audit-date-dialog input { width:100%; min-width:0; height:44px; padding:8px 10px; background:var(--surface-muted); border:1px solid var(--border); border-radius:8px; color:var(--ink); font-size:14px; }
.audit-date-dialog-body p { margin-top:12px; color:var(--ink-muted); font-size:12px; line-height:1.5; }
.audit-date-dialog-footer { display:flex; justify-content:flex-end; padding:16px 20px; border-top:1px solid var(--border); }
.audit-date-dialog :is(input,button):focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
body.audit-date-open .page-content { overflow:hidden !important; }
body.audit-date-open .panel-scroll-top { display:none !important; }
@media(max-width:420px) { .audit-date-dialog .audit-date-fields { grid-template-columns:minmax(0,1fr); } }
.audit-date-heading { padding:8px 12px; font-size:12px; font-weight:600; color:var(--ink-muted); }
.audit-filter-popover > button[data-audit-range],.audit-filter-popover > button[data-audit-custom] { display:flex; justify-content:space-between; align-items:center; gap:12px; }
.audit-filter-popover > button[aria-pressed="true"] { background:var(--surface-muted); font-weight:600; }
.audit-filter-popover > button[aria-pressed="false"] > i { visibility:hidden; }
.audit-date-fields { display:grid; gap:12px; }
.audit-custom-dates p { font-size:12px; line-height:1.5; color:var(--ink-muted); margin:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page { display:block !important; height:auto !important; overflow:visible !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page :is(.admin-table-card,.table-system-list,.table-wrapper) { flex:none !important; display:block !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page :is(.table-system-list,.table-wrapper,.table-system-wrap) { max-height:none !important; height:auto !important; overflow-y:visible !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-details-panel { color:var(--ink) !important; background:var(--card) !important; border-color:var(--border) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-details-panel :is(.audit-detail-line,strong,p,span) { color:var(--ink) !important; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-detail-section { border-color:var(--border) !important; }
.audit-compact-toolbar { display:grid; grid-template-columns:minmax(160px,1fr) minmax(160px,1fr) minmax(160px,1fr) auto auto; align-items:center; gap:10px; }
.audit-filter-menu { position:relative; min-width:0; }
.audit-filter-menu > summary { display:flex; align-items:center; gap:8px; min-height:44px; padding:0 12px; background:var(--surface-muted); border:1px solid var(--border); border-radius:8px; color:var(--ink); list-style:none; cursor:pointer; font-size:14px; white-space:nowrap; }
.audit-filter-menu > summary::-webkit-details-marker { display:none; }
.audit-filter-menu > summary > i:last-child { margin-left:auto; }
.audit-filter-menu > summary > i:last-child { transition:transform .18s ease; }
.audit-filter-menu[open] > summary > i:last-child { transform:rotate(180deg); }
@media(prefers-reduced-motion:reduce) { .audit-filter-menu > summary > i:last-child { transition:none; } }
.audit-filter-popover { position:absolute; top:calc(100% + 8px); right:0; width:280px; max-width:calc(100vw - 48px); background:var(--card); border:1px solid var(--border); border-radius:12px; padding:8px; z-index:40; box-shadow:0 8px 24px rgb(8 29 19 / 16%); }
.audit-filter-popover > button { display:block; width:100%; min-height:40px; padding:8px 12px; text-align:left; border-radius:8px; color:var(--ink); }
.audit-filter-popover > button:hover { background:var(--surface-muted); }
.audit-filter-popover label { display:grid; gap:6px; font-size:12px; color:var(--ink-muted); }
.audit-filter-popover :is(input,select) { width:100%; min-width:0; height:44px; padding:8px; background:var(--surface-muted); border:1px solid var(--border); border-radius:8px; color:var(--ink); font-size:14px; }
.audit-custom-dates { display:grid; gap:12px; padding:12px; border-top:1px solid var(--border); margin-top:8px; }
.audit-custom-dates[hidden] { display:none; }
.audit-secondary-filters { padding:16px; }
.audit-secondary-filters p { margin-top:12px; font-size:12px; color:var(--ink-muted); }
.audit-compact-actions { display:flex; gap:8px; }
.audit-page-size { display:flex; align-items:center; gap:10px; white-space:nowrap; font-size:12px; color:var(--ink-muted); }
.audit-page-size .audit-filter-control { width:80px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-page-size .audit-select { padding-left:12px; }
.audit-compact-actions .audit-action { padding:0 12px; }
@media(max-width:1100px) { .audit-compact-toolbar { grid-template-columns:repeat(2,minmax(0,1fr)); } .audit-compact-actions { grid-column:1 / -1; justify-content:flex-end; } }
@media(max-width:540px) { .audit-compact-toolbar { grid-template-columns:minmax(0,1fr); } .audit-filter-popover { left:0; right:auto; } }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui):has(.audit-ref-page) .panel-page-header { background:transparent !important; border:0 !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page { padding:8px var(--panel-content-inline,20px) 20px; background:transparent !important; color:var(--ink); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page :is(.table-system-card,.table-system-list) { background:transparent !important; border:0 !important; box-shadow:none !important; overflow:visible; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .admin-table-card { margin-top:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .admin-table-toolbar { position:sticky; top:16px; z-index:30; padding:12px; margin-bottom:16px; background:var(--card) !important; border:1px solid var(--border) !important; border-radius:12px; overflow:visible; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-toolbar { grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-filter-control { height:44px; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-select { height:100%; min-height:0; border:0; border-radius:inherit; background:transparent !important; color:var(--ink); font-size:14px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page :is(.audit-filter-control > i,.audit-filter-control::after) { color:var(--ink-muted); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page :is(.audit-scope-chip,.audit-action,.audit-details-panel) { background:var(--surface-muted); border-color:var(--border); color:var(--ink); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-action { min-height:44px; border-radius:8px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-action-primary { background:var(--brand); color:var(--card); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .table-system-wrap { background:var(--card) !important; border:1px solid var(--border); border-radius:12px; overflow-x:auto; max-height:none !important; height:auto !important; position:relative; isolation:isolate; z-index:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .table-system-table { min-width:1000px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .table-system-table th { width:auto; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .table-system-table .audit-col-data { width:80px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .table-system-table thead th { background:var(--surface-muted) !important; color:var(--ink); border-color:var(--border); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .table-system-table :is(td,tbody tr) { background:var(--card); color:var(--ink); border-color:var(--border); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-primary { color:var(--ink); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page :is(.audit-secondary,.audit-detail-title) { color:var(--ink-muted); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .table-system-pagination { margin-top:16px; background:transparent !important; border:0 !important; padding:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-details-button { position:relative; display:inline-flex; width:44px; height:44px; justify-content:center; align-items:center; padding:0; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); color:var(--brand); list-style:none; }
.audit-details-button::-webkit-details-marker { display:none; }
.audit-eye-tooltip { position:absolute; top:50%; right:calc(100% + 8px); transform:translateY(-50%); width:max-content; background:var(--ink); color:var(--card); padding:6px 10px; border-radius:6px; font-size:12px; visibility:hidden; pointer-events:none; z-index:40; }
.audit-details-button:is(:hover,:focus-visible) .audit-eye-tooltip { visibility:visible; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page :is(a,button,input,select,summary):focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
@media(max-width:900px) { html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-toolbar { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:540px) { html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .audit-toolbar { grid-template-columns:minmax(0,1fr); } }
.audit-log-dialog { width:min(680px,calc(100vw - 32px)); max-height:calc(100dvh - 48px); margin:auto; padding:0; border:1px solid var(--filter-ui-border); border-radius:12px; background:var(--filter-ui-menu); color:var(--filter-ui-text); font-family:'Plus Jakarta Sans','Segoe UI',sans-serif; overflow:auto; }
.audit-log-dialog:not([open]) { display:none; }
.audit-log-dialog::backdrop { background:rgba(15,23,42,.72); }
.audit-log-dialog header { position:sticky; top:0; z-index:1; background:var(--filter-ui-menu); display:flex; justify-content:space-between; align-items:center; gap:16px; padding:20px; border-bottom:1px solid var(--filter-ui-border); }
.audit-log-dialog h2 { margin:0; font-size:20px; font-weight:700; }
.audit-log-dialog header button { width:44px; height:44px; flex-shrink:0; border:1px solid var(--filter-ui-border); border-radius:8px; background:var(--filter-ui-surface); color:var(--filter-ui-text); }
.audit-log-context { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; padding:20px; margin:0; background:var(--filter-ui-surface); }
.audit-log-context dt,.audit-log-dialog .audit-detail-title { color:var(--filter-ui-muted); font-size:12px; font-weight:700; margin-bottom:6px; }
.audit-log-context dd { margin:0; white-space:pre-line; }
.audit-log-dialog .audit-details-panel { position:static; width:auto; min-width:0; max-height:none; padding:20px; margin:0; border:0; box-shadow:none; background:transparent; color:var(--filter-ui-text); }
.audit-log-dialog :is(dd,.audit-detail-line,strong,.audit-secondary) { color:var(--filter-ui-text); font-size:14px; overflow-wrap:anywhere; }
.audit-log-dialog .audit-detail-section { border-color:var(--filter-ui-border); }
.audit-log-dialog button:focus-visible { outline:2px solid var(--filter-ui-focus); outline-offset:2px; }
body.audit-log-modal-open .page-content { overflow:hidden !important; }
@media(max-width:540px) { .audit-log-context { grid-template-columns:minmax(0,1fr); } .audit-log-dialog { max-height:calc(100dvh - 24px); } .audit-log-dialog header,.audit-log-context,.audit-log-dialog .audit-details-panel { padding:16px; } }
</style>
