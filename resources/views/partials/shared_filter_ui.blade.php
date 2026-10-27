<style>
.case-date-dialog { width:min(480px,calc(100vw - 32px)); max-height:calc(100dvh - 48px); margin:auto; padding:0; overflow:auto; border:1px solid var(--filter-ui-border); border-radius:12px; background:var(--filter-ui-menu); color:var(--filter-ui-text); font-family:'Plus Jakarta Sans','Segoe UI',sans-serif; }
.case-date-dialog:not([open]) { display:none; }
.case-date-dialog::backdrop { background:var(--modal-scrim,rgba(15,23,42,.72)); }
.case-date-head { display:flex; align-items:center; justify-content:space-between; padding:20px; gap:16px; border-bottom:1px solid var(--filter-ui-border); }
.case-date-head h2 { margin:0; font-size:20px; font-weight:700; }
.case-date-head button { width:44px; height:44px; border:1px solid var(--filter-ui-border); border-radius:8px; background:var(--filter-ui-surface); }
.case-date-dialog > .case-compact-popover { position:static !important; width:auto !important; padding:20px; border:0; border-radius:0; box-shadow:none; background:transparent; }
.case-date-dialog .case-compact-pop-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.case-date-dialog label { display:block; font-size:12px; color:var(--filter-ui-muted); margin-bottom:8px; }
.case-date-dialog input[type="date"] { width:100%; min-width:0; height:44px; padding:8px; border:1px solid var(--filter-ui-border); border-radius:8px; background:var(--filter-ui-surface) !important; color:var(--filter-ui-text) !important; font-size:14px; color-scheme:light; }
html[data-theme] body.panel-shell-body .case-date-dialog input[type="date"] { background:var(--filter-ui-surface) !important; color:var(--filter-ui-text) !important; }
html[data-theme='dark'] .case-date-dialog input[type="date"] { color-scheme:dark; }
.case-date-dialog .case-compact-pop-actions { display:flex; justify-content:flex-end; gap:12px; margin-top:20px; }
.case-date-dialog .case-compact-pop-actions button { min-height:44px; padding:0 16px; border-radius:8px; border:1px solid var(--filter-ui-border); background:var(--filter-ui-surface); color:var(--filter-ui-text); font-size:14px; }
.case-date-dialog .case-compact-pop-actions .case-compact-pop-apply { background:var(--filter-ui-focus); color:var(--filter-ui-menu); }
.case-date-dialog :is(input,button):focus-visible { outline:2px solid var(--filter-ui-focus); outline-offset:2px; }
body.case-date-open .page-content { overflow:hidden !important; }
body.case-date-open .panel-scroll-top { display:none !important; }
@media(max-width:420px) { .case-date-dialog .case-compact-pop-fields { grid-template-columns:minmax(0,1fr); } }
.filter-select-menu__item { display:flex !important; align-items:center; justify-content:space-between; gap:12px; }
.filter-select-menu__check { flex-shrink:0; }
.filter-select-menu__item:not([aria-selected='true']) .filter-select-menu__check { visibility:hidden; }
/* Presentation only: existing filter controls keep their original events and values. */
body.panel-shell-body {
    --filter-ui-surface:#D0E2D6; --filter-ui-menu:#F4F8F5; --filter-ui-border:#AEC7B7;
    --filter-ui-text:#081D13; --filter-ui-muted:#1B3E2E; --filter-ui-focus:#1B4332;
}
html[data-theme='dark'] body.panel-shell-body {
    --filter-ui-surface:#14261D; --filter-ui-menu:#112218; --filter-ui-border:#29523D;
    --filter-ui-text:#F8FAFC; --filter-ui-muted:#94A3B8; --filter-ui-focus:#34D399;
}
/* Shared, non-destructive action for clearing an applied filter set. */
html[data-theme] body.panel-shell-body .page-content .filter-clear-action {
    display:inline-flex; align-items:center; justify-content:center; gap:7px;
    min-height:44px; padding:0 13px; border:1px solid #C79B55 !important;
    border-radius:8px; background:#F6E8CC !important; color:#704514 !important;
    font-family:'Plus Jakarta Sans','Segoe UI',sans-serif; font-size:14px; font-weight:600;
    line-height:1; text-decoration:none; white-space:nowrap; box-shadow:none;
}
html[data-theme] body.panel-shell-body .page-content .filter-clear-action:hover {
    border-color:#A9762E !important; background:#EFDDBB !important; color:#56330C !important;
}
html[data-theme] body.panel-shell-body .page-content .filter-clear-action:focus-visible {
    outline:2px solid #9A6B27 !important; outline-offset:2px;
}
html[data-theme='dark'] body.panel-shell-body .page-content .filter-clear-action {
    border-color:#9B7844 !important; background:#382C1D !important; color:#F4D9A4 !important;
}
html[data-theme='dark'] body.panel-shell-body .page-content .filter-clear-action:hover {
    border-color:#C79B55 !important; background:#493923 !important; color:#FFF0CF !important;
}
/* These two modules have highly specific legacy button rules; keep the shared
   filter-clear semantics at the component boundary without altering other buttons. */
html[data-theme] body.panel-shell-body .page-content .payment-monitoring-page .pm-actions > .pm-btn.filter-clear-action[data-pm-clear-filters],
html[data-theme] body.panel-shell-body .page-content .service-reference .service-filter-actions .table-toolbar-reset-wrap > .btn.filter-clear-action[data-service-reset] {
    border-color:#C79B55 !important; background:#F6E8CC !important; color:#704514 !important;
}
html[data-theme] body.panel-shell-body .page-content .payment-monitoring-page .pm-actions > .pm-btn.filter-clear-action[data-pm-clear-filters]:hover,
html[data-theme] body.panel-shell-body .page-content .service-reference .service-filter-actions .table-toolbar-reset-wrap > .btn.filter-clear-action[data-service-reset]:hover {
    border-color:#A9762E !important; background:#EFDDBB !important; color:#56330C !important;
}
html[data-theme='dark'] body.panel-shell-body .page-content .payment-monitoring-page .pm-actions > .pm-btn.filter-clear-action[data-pm-clear-filters],
html[data-theme='dark'] body.panel-shell-body .page-content .service-reference .service-filter-actions .table-toolbar-reset-wrap > .btn.filter-clear-action[data-service-reset] {
    border-color:#9B7844 !important; background:#382C1D !important; color:#F4D9A4 !important;
}
html[data-theme='dark'] body.panel-shell-body .page-content .payment-monitoring-page .pm-actions > .pm-btn.filter-clear-action[data-pm-clear-filters]:hover,
html[data-theme='dark'] body.panel-shell-body .page-content .service-reference .service-filter-actions .table-toolbar-reset-wrap > .btn.filter-clear-action[data-service-reset]:hover {
    border-color:#C79B55 !important; background:#493923 !important; color:#FFF0CF !important;
}
html[data-theme] body.panel-shell-body .page-content .filter-clear-action[hidden] { display:none !important; }
html[data-theme] body.panel-shell-body .page-content :is(form[method='get' i],[data-table-toolbar],.case-record-filters,.reports-module-controls,.reports-filter-card,.ba-workspace-filters,.owner-history-filter-panel,.rp-header-filters) {
    --uniform-filter-bg:var(--filter-ui-surface); --uniform-filter-border:var(--filter-ui-border); --uniform-filter-text:var(--filter-ui-text);
    font-family:'Plus Jakarta Sans','Segoe UI',sans-serif;
}
html[data-theme] body.panel-shell-body .page-content :is(form[method='get' i],[data-table-toolbar],.case-record-filters,.reports-module-controls,.reports-filter-card,.ba-workspace-filters,.owner-history-filter-panel,.rp-header-filters) :is(input:not([type='hidden']):not([type='checkbox']):not([type='radio']):not([type='submit']),select) {
    box-sizing:border-box !important; min-width:0; height:44px !important; min-height:44px !important; max-height:44px !important;
    background:var(--filter-control-background,var(--filter-ui-surface)) !important; color:var(--filter-ui-text) !important;
    border:var(--filter-control-border,1px solid var(--filter-ui-border)) !important; border-radius:8px !important; box-shadow:none !important;
    font-family:'Plus Jakarta Sans','Segoe UI',sans-serif !important; font-size:14px !important; font-weight:600;
    text-overflow:clip !important;
}
/* Wrapped search/select surfaces have one border; inner fields cannot obscure its bottom edge. */
body.panel-shell-body .case-record-filters .case-compact-seg > select {
    --filter-control-background:transparent;
    --filter-control-border:0;
}
html[data-theme] body.panel-shell-body .page-content :is(form[method='get' i],[data-table-toolbar],.case-record-filters,.reports-module-controls,.reports-filter-card,.ba-workspace-filters,.owner-history-filter-panel) :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap,.audit-filter-control,.reports-field-control,.ba-branch-select-wrap,.ba-period-select-wrap,.case-compact-search-control,.case-compact-branch,.case-compact-seg) {
    box-sizing:border-box !important; height:44px !important; min-height:44px !important;
    background:var(--filter-ui-surface) !important; border:1px solid var(--filter-ui-border) !important; border-radius:8px !important; box-shadow:none !important;
}
html[data-theme] body.panel-shell-body .page-content :is(form[method='get' i],[data-table-toolbar],.case-record-filters,.reports-module-controls,.reports-filter-card,.ba-workspace-filters,.owner-history-filter-panel) :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap,.audit-filter-control,.reports-field-control,.ba-branch-select-wrap,.ba-period-select-wrap,.case-compact-search-control,.case-compact-branch,.case-compact-seg) > :is(input,select) {
    background:transparent !important; border:0 !important; border-radius:inherit !important; height:100% !important; min-height:0 !important; max-height:none !important;
}
html[data-theme] body.panel-shell-body .page-content :is(.case-record-filters,.owner-history-filter-panel,.audit-compact-toolbar) :is(.case-compact-date-trigger,.case-compact-sort-trigger,.case-compact-advanced-trigger,.case-compact-more,.audit-filter-menu > summary) {
    min-height:44px !important; border:1px solid var(--filter-ui-border) !important; border-radius:8px !important; background:var(--filter-ui-surface) !important; color:var(--filter-ui-text) !important; font-family:'Plus Jakarta Sans','Segoe UI',sans-serif; font-size:14px;
}
html[data-theme] body.panel-shell-body .page-content :is(.filter-select-menu,.case-compact-pop,.case-compact-advanced-panel,.audit-filter-popover) {
    background:var(--filter-ui-menu) !important; border-color:var(--filter-ui-border) !important; color:var(--filter-ui-text) !important; border-radius:12px !important; font-family:'Plus Jakarta Sans','Segoe UI',sans-serif; font-size:14px;
}
html[data-theme] body.panel-shell-body .page-content :is(.filter-select-menu,.case-compact-pop,.audit-filter-popover) :is(button,[role='option']) { min-height:40px; color:var(--filter-ui-text); border-radius:8px; }
html[data-theme] body.panel-shell-body .page-content :is(.filter-select-menu,.case-compact-pop,.audit-filter-popover) :is(button,[role='option']):hover { background:var(--filter-ui-surface); }
html[data-theme] body.panel-shell-body .page-content :is(.filter-select-menu,.case-compact-pop,.audit-filter-popover) :is(.is-active,.is-selected,[aria-selected='true'],[aria-pressed='true']) { background:var(--filter-ui-surface) !important; color:var(--filter-ui-text) !important; font-weight:600; }
html[data-theme] body.panel-shell-body .page-content :is(form[method='get' i],[data-table-toolbar],.case-record-filters,.reports-module-controls,.reports-filter-card,.ba-workspace-filters,.owner-history-filter-panel) :is(input,select,button,summary):focus-visible { outline:2px solid var(--filter-ui-focus) !important; outline-offset:2px; }
/* Observe existing open states; do not add a replacement dropdown implementation. */
html[data-theme] body.panel-shell-body .page-content :is(.table-toolbar-select-wrap,.pm-field,.case-compact-branch) :is(.table-toolbar-select-icon,.pm-sel-chev,.case-compact-select-chev) { transition:rotate .18s ease; }
html[data-theme] body.panel-shell-body .page-content :is(.table-toolbar-select-wrap,.pm-field,.case-compact-branch):has(> select:open) :is(.table-toolbar-select-icon,.pm-sel-chev,.case-compact-select-chev) { rotate:180deg; }
@media(prefers-reduced-motion:reduce) { html[data-theme] body.panel-shell-body .page-content :is(.table-toolbar-select-icon,.pm-sel-chev,.case-compact-select-chev) { transition:none; } }
</style>
