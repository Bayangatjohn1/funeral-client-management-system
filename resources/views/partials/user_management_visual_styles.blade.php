<style>
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui):has(:is(.directory-page,.user-create-page,.user-edit-page)) .user-page-title-icon { display:grid; place-items:center; flex:none; width:3rem; height:3rem; border-radius:.8rem; background:transparent; color:var(--ink-muted); font-size:1.35rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui):has(:is(.directory-page,.user-create-page,.user-edit-page)) .panel-page-header__leading { display:flex; align-items:center; gap:.85rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-head {
    background:transparent !important; border:0 !important; padding:0 !important; margin:0 0 16px; min-height:0;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .admin-table-head-row { padding:0; min-height:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-toolbar {
    display:grid !important;
    gap:10px !important; align-items:center !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-filters-system {
    grid-template-columns:minmax(280px,2.2fr) minmax(190px,1.2fr) minmax(145px,.9fr) auto !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-filters-system:has(.user-filter-clear) { grid-template-columns:minmax(280px,2.2fr) minmax(190px,1.2fr) minmax(145px,.9fr) auto auto !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-toolbar > .table-toolbar-field { min-width:0 !important; width:100%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-filters-branch {
    grid-template-columns:minmax(240px,2fr) repeat(2,minmax(170px,1fr)) !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-filters-branch:has(.user-filter-clear) { grid-template-columns:minmax(240px,2fr) repeat(2,minmax(170px,1fr)) auto !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-filter-clear { display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:44px; padding:0 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); color:var(--ink); font-size:14px; font-weight:600; text-decoration:none; white-space:nowrap; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-filter-clear:hover { background:var(--surface-hover,var(--surface-muted)); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters { position:relative; min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters > summary { display:flex; align-items:center; justify-content:center; gap:10px; min-height:44px; padding:0 14px; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); color:var(--ink); font-size:14px; font-weight:600; cursor:pointer; list-style:none; white-space:nowrap; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters > summary::-webkit-details-marker { display:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters[data-has-active-filters] > summary { border-color:var(--brand); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters-count { display:grid; place-items:center; min-width:20px; height:20px; padding:0 4px; border-radius:999px; background:var(--brand); color:#fff; font-size:11px; font-weight:700; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters[open] > summary .bi-chevron-down { transform:rotate(180deg); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters-panel { position:absolute; top:calc(100% + 8px); right:0; z-index:50; width:min(360px,calc(100vw - 32px)); display:grid; gap:12px; padding:16px; border:1px solid var(--border); border-radius:12px; background:var(--card); box-shadow:0 12px 28px rgb(8 29 19 / 14%); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters:not([open]) .user-more-filters-panel { display:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters-panel .table-toolbar-label { display:block; margin-bottom:6px; font-size:12px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters-panel .table-toolbar-field { min-width:0; width:100%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.table-toolbar-input-wrap, .table-toolbar-select-wrap) { border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.table-toolbar-search, .table-toolbar-select, .table-toolbar-sort) { border:0 !important; background:transparent !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .row-action-trigger {
    position:relative; display:inline-flex; justify-content:center; align-items:center; width:44px !important; min-width:44px !important; height:44px; padding:0 !important; border-radius:8px !important; font-size:16px;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .row-action-trigger::after {
    content:'User actions'; position:absolute; right:calc(100% + 8px); top:50%; transform:translateY(-50%); width:max-content; padding:6px 10px; background:var(--ink); color:var(--card); border-radius:6px; font-size:12px; opacity:0; visibility:hidden; pointer-events:none; z-index:40;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .row-action-trigger:is(:hover,:focus-visible)::after { opacity:1; visibility:visible; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-card-actions { opacity:1 !important; visibility:visible !important; padding:12px 16px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-card-view > .grid { gap:16px; align-items:stretch; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-item-card { overflow:visible !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-item-card { cursor:default; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-card-head { padding:16px; border-radius:12px 12px 0 0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-card-head .user-view-button { flex:none; align-self:flex-start; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-table-view { padding:0 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-table :is(th,td) { padding:14px 16px; }
@media(max-width:1150px) { html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-filters-system { grid-template-columns:repeat(3,minmax(0,1fr)) !important; } }
@media(max-width:1000px) { html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-toolbar { grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important; } }
@media(max-width:600px) { html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-toolbar { grid-template-columns:minmax(0,1fr) !important; } }
@media(max-width:600px) { html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters-panel { right:auto; left:0; } }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.directory-page, .user-create-page, .user-edit-page) { background:transparent !important; color:var(--ink); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui):has(:is(.directory-page, .user-create-page, .user-edit-page)) .panel-page-header { background:transparent !important; border:0 !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.table-system-card, .admin-table-card) { background:transparent !important; border:0 !important; box-shadow:none !important; overflow:visible !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.table-system-toolbar, .directory-item-card, .table-system-wrap, .directory-user-card-dropdown) { background:var(--card) !important; border:1px solid var(--border) !important; border-radius:12px !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page { padding-top:8px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .admin-table-card { margin-top:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar { position:sticky; top:16px; z-index:30; margin-block:0 16px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.directory-card-view, .table-system-list) { background:transparent !important; border:0 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.directory-user-card-head, .directory-user-role-strip) { background:var(--surface-muted) !important; border-color:var(--border) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.table-toolbar-search, .table-toolbar-select, .table-toolbar-sort) { background:var(--surface-muted) !important; border-color:var(--border) !important; border-radius:8px !important; min-height:44px; color:var(--ink) !important; font-size:14px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-wrap { overflow-x:auto !important; isolation:isolate; position:relative; z-index:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-pagination { background:transparent !important; border:0 !important; margin-top:16px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-page, .user-edit-page) :is(.modal-shell-card, [class*='-section'], .card-custom) { background:var(--card) !important; border-color:var(--border) !important; border-radius:12px; box-shadow:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-page, .user-edit-page) :is(input, select, textarea) { background:var(--card) !important; color:var(--ink) !important; border-color:var(--border) !important; border-radius:8px; min-height:44px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.directory-page, .user-create-page, .user-edit-page) :is(a, button, input, select, textarea):focus-visible { outline:2px solid var(--brand) !important; outline-offset:2px; }
</style>
<style>
/* This toolbar owns its control surfaces, including the shared uniform overrides. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar .table-toolbar :is(.table-toolbar-input-wrap, .table-toolbar-select-wrap) { background:var(--surface-muted) !important; border:1px solid var(--border) !important; border-radius:8px !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar .table-toolbar :is(.table-toolbar-input-wrap, .table-toolbar-select-wrap) { height:44px; box-sizing:border-box; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar .table-toolbar :is(.table-toolbar-input-wrap, .table-toolbar-select-wrap) > :is(input,select) { background:transparent !important; border:0 !important; border-radius:inherit !important; height:100% !important; min-height:0 !important; box-sizing:border-box; }
.user-view-button { position:relative; display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border:1px solid var(--border); background:var(--surface-muted); color:var(--brand); border-radius:8px; cursor:pointer; }
.user-view-tooltip { position:absolute; right:calc(100% + 8px); top:50%; transform:translateY(-50%); background:var(--ink); color:var(--card); padding:6px 10px; border-radius:6px; font-size:12px; white-space:nowrap; pointer-events:none; visibility:hidden; z-index:40; }
.user-view-button:is(:hover,:focus-visible) .user-view-tooltip { visibility:visible; }
.user-information-dialog { width:min(560px, calc(100vw - 32px)); max-height:calc(100dvh - 48px); margin:auto; padding:0; background:var(--card); color:var(--ink); border:1px solid var(--border); border-radius:12px; overflow:auto; }
.user-information-dialog::backdrop { background:var(--modal-scrim, rgba(15,23,42,.72)); }
.user-information-dialog[open] + .user-status-dialog::backdrop { background:transparent; }
.user-information-head { display:flex; align-items:start; justify-content:space-between; padding:20px; background:var(--surface-muted); border-bottom:1px solid var(--border); gap:16px; }
.user-information-heading { display:flex; align-items:flex-start; gap:12px; min-width:0; }
.user-information-heading > div { min-width:0; }
.user-information-heading-icon { display:grid; place-items:center; flex:none; width:40px; height:40px; border-radius:10px; background:#1b4332; color:#fff; font-size:18px; }
.user-information-head h2 { font-size:20px; font-weight:700; margin:0; }
.user-information-head p { color:var(--ink-muted); margin-top:4px; }
.user-information-head button { width:44px; height:44px; border:1px solid var(--border); background:var(--surface-muted); border-radius:8px; flex-shrink:0; }
.user-information-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); column-gap:20px; padding:12px 20px 20px; }
.user-information-fields > div { min-width:0; padding:12px 0; border-bottom:1px solid var(--border); }
.user-information-fields dt { font-size:12px; color:var(--ink-muted); margin-bottom:4px; }
.user-information-fields dd { font-size:14px; font-weight:600; overflow-wrap:anywhere; }
.user-information-footer { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:12px; padding:16px 20px; border-top:1px solid var(--border); background:var(--card); }
.user-information-footer :is(a,button) { display:inline-flex; align-items:center; gap:8px; min-height:44px; }
.user-view-button:focus-visible, .user-information-dialog :is(a,button):focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
.directory-page .directory-user-role-strip .user-card-status { margin-left:auto; border-radius:999px; padding:3px 9px; font-size:11px !important; letter-spacing:0 !important; text-transform:none; }
.directory-page .directory-user-role-strip .user-card-status.is-active { background:#e0f4e9 !important; border-color:#a3d5bb; color:#13583c !important; }
.directory-page .directory-user-role-strip .user-card-status.is-inactive { background:#f6e8e6 !important; border-color:#dfc0bc; color:#973d38 !important; }
.directory-page .directory-user-card-actions, .directory-page .user-table-actions { display:flex; align-items:center; gap:8px; }
.directory-page .directory-user-card-actions { margin-top:auto; border-top:1px solid var(--border); }
.directory-page .user-view-button--card { flex:1; width:auto; min-width:0; gap:8px; font-size:14px; font-weight:600; color:var(--ink); }
.directory-page .user-view-button--card .user-view-tooltip { display:none; }
.directory-page .user-directory-action { display:inline-grid; place-items:center; flex:none; width:40px; height:40px; padding:0; border:0; border-radius:8px; background:transparent; color:var(--brand); text-decoration:none; cursor:pointer; font-size:17px; }
.directory-page .user-directory-action:hover { background:var(--surface-muted); }
.directory-page .user-directory-action.is-danger { color:#b44648; }
.directory-page .user-directory-action.is-enable { color:#167b54; }
.directory-page .user-table-actions { justify-content:flex-end; }
.directory-page .user-table-actions .user-view-button { width:40px; height:40px; border:0; background:transparent; }
.directory-page .user-table-actions .user-view-button:hover { background:var(--surface-muted); }
.directory-page .table-col-actions { min-width:142px; }
.user-status-body { padding:22px 20px; color:var(--ink); line-height:1.55; }
.user-status-confirm-danger { padding:0 16px; border:1px solid #ad4341; border-radius:8px; background:#ad4341; color:#fff; font-weight:650; cursor:pointer; }
.user-status-confirm-danger:hover { background:#8f302f; }
.directory-page .user-directory-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
.directory-page .user-directory-stats + .admin-table-card { margin-top:18px; }
.directory-page .user-directory-stat { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; min-width:0; padding:18px; background:var(--card); border:1px solid var(--border); border-radius:12px; }
.directory-page .user-directory-stat > div { display:grid; gap:5px; min-width:0; }
.directory-page .user-directory-stat > div > span { color:var(--ink-muted); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
.directory-page .user-directory-stat strong { color:var(--ink); font-size:28px; line-height:1.1; font-weight:700; }
.directory-page .user-directory-stat small { color:var(--ink-muted); font-size:12px; font-weight:400; }
.directory-page .user-directory-stat > i { display:grid; place-items:center; flex:none; width:38px; height:38px; border-radius:10px; background:var(--surface-muted); color:var(--brand); font-size:18px; }
.directory-page .user-directory-role-counts { display:flex; align-items:baseline; gap:14px; white-space:nowrap; }
.directory-page .user-directory-role-counts > span + span { padding-left:14px; border-left:1px solid var(--border); }
.directory-page .user-directory-role-counts small { font-size:12px; }
.directory-page .user-directory-avatar { display:grid; place-items:center; flex:none; width:42px; height:42px; border-radius:10px; font-size:15px; font-weight:700; }
.directory-page .user-directory-avatar.is-system { background:#efe2fa; color:#6f31a4; }
.directory-page .user-directory-avatar.is-branch { background:#dfebfb; color:#215ca5; }
.directory-page .user-directory-avatar.is-staff { background:#d8f2e5; color:#146847; }
.directory-page .directory-user-card-head { align-items:center; }
.directory-page .directory-user-card-head .directory-user-role-strip { justify-content:flex-end; margin:0 0 4px; }
.directory-page .directory-user-card-head .user-card-status { margin:0; }
.directory-page .user-directory-card-meta { display:grid; gap:8px; padding:16px; border-top:1px solid var(--border); font-size:13px; }
.directory-page .user-directory-card-meta > div { display:flex; align-items:baseline; justify-content:space-between; gap:10px; min-width:0; }
.directory-page .user-directory-card-meta span { color:var(--ink-muted); white-space:nowrap; }
.directory-page .user-directory-card-meta strong { color:var(--ink); font-weight:550; text-align:right; overflow-wrap:anywhere; }
.directory-page .user-directory-table-identity { display:inline-flex; align-items:center; gap:10px; }
.directory-page .user-directory-table-identity .user-directory-avatar { width:34px; height:34px; border-radius:999px; font-size:12px; }
.user-information-access { display:flex; justify-content:space-between; gap:18px; margin:20px 20px 0; padding:14px 16px; border:1px solid var(--border); border-radius:10px; background:var(--surface-muted); }
.user-information-access > div { display:grid; gap:5px; }
.user-information-access > div:last-child { text-align:right; }
.user-information-access span { color:var(--ink-muted); font-size:12px; }
.user-information-access strong { color:var(--ink); font-size:14px; }
.user-information-access .user-modal-status.is-active { color:#146847; }
.user-information-access .user-modal-status.is-inactive { color:#a33a3e; }
.user-information-close-action { margin-right:auto; }
@media(max-width:1100px) { .directory-page .user-directory-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:600px) { .directory-page .user-directory-stats { grid-template-columns:minmax(0,1fr); } .user-information-footer .user-information-close-action { margin-right:0; } }
html[data-theme="dark"] .directory-page .user-directory-avatar.is-system { background:#34213e; color:#e5bbff; }
html[data-theme="dark"] .directory-page .user-directory-avatar.is-branch { background:#152c49; color:#a7ceff; }
html[data-theme="dark"] .directory-page .user-directory-avatar.is-staff { background:#103528; color:#a2e8c3; }
html[data-theme="dark"] .user-information-access .user-modal-status.is-active { color:#a2e8c3; }
html[data-theme="dark"] .user-information-access .user-modal-status.is-inactive { color:#f5a6a9; }
/* User directory: follow the supplied card/table reference without copying its sample data. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui):has(.directory-page) .panel-page-header__copy h1 { display:flex; align-items:center; flex-wrap:wrap; gap:10px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui):has(.directory-page) .panel-page-header__badge { display:inline-flex; align-items:center; padding:4px 9px; border:1px solid var(--border); border-radius:6px; background:var(--card); color:var(--ink-muted); font-size:12px; font-weight:600; letter-spacing:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-stats { margin-bottom:24px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-stats + .admin-table-card { margin-top:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-stat { min-height:116px; padding:16px 18px; background:#fff; border-color:#dae5df; border-radius:16px; box-shadow:0 1px 2px rgb(8 29 19 / 4%); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-stat > div > span { color:#546a7f; font-size:12px; font-weight:600; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-stat strong { font-size:25px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-stat > i { width:36px; height:36px; background:#eef3ff; color:#385aaf; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-stat:nth-child(2) > i { background:#e8f7ef; color:#087f55; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-stat:nth-child(3) > i { background:#fff0f2; color:#c94358; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar { display:flex !important; align-items:center; gap:12px; padding:16px !important; background:#fff !important; border-color:#dae5df !important; border-radius:16px !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar .table-toolbar { flex:1; min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar .table-toolbar :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap) { background:#f8fafc !important; border-color:#dbe4ed !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters > summary { background:#f8fafc; border-color:#dbe4ed; }
html:not([data-theme="dark"]) body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar .table-toolbar :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap):not(.is-filtered) { background:#fff !important; border-color:#dbe4ed !important; box-shadow:none !important; }
html:not([data-theme="dark"]) body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters:not([data-has-active-filters]) > summary { background:#fff !important; border-color:#dbe4ed !important; box-shadow:none !important; }
html:not([data-theme="dark"]) body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar .table-toolbar :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap).is-filtered,
html:not([data-theme="dark"]) body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters[data-has-active-filters] > summary { background:#edf6f0 !important; border-color:#86b39a !important; }
html:not([data-theme="dark"]) body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar .table-toolbar :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap):focus-within,
html:not([data-theme="dark"]) body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters > summary:focus-visible { border-color:var(--brand) !important; box-shadow:0 0 0 3px rgb(27 67 50 / 16%) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-view-toggle { display:flex; align-items:center; flex:none; padding:3px; border:1px solid #dbe4ed; border-radius:10px; background:#f1f5f9; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-view-toggle button { position:relative; display:grid; place-items:center; width:44px; height:44px; border:0; border-radius:7px; background:transparent; color:var(--ink-muted); cursor:pointer; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-view-toggle button::after { content:attr(aria-label); position:absolute; top:calc(100% + .5rem); right:0; z-index:30; width:max-content; padding:.35rem .55rem; border-radius:.4rem; background:var(--ink); color:var(--card); font-size:.75rem; line-height:1.4; pointer-events:none; opacity:0; visibility:hidden; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-view-toggle button:is(:hover,:focus-visible)::after { opacity:1; visibility:visible; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-view-toggle button:focus-visible { outline:2px solid var(--brand); outline-offset:-3px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-view-toggle button.is-active { background:#fff; color:var(--ink); box-shadow:0 1px 3px rgb(8 29 19 / 12%); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.directory-card-view,.table-system-list) { padding:0 !important; background:transparent !important; border:0 !important; border-radius:0 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-card-view > .grid { grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-item-card { background:#fff !important; border-color:#dae5df !important; border-radius:16px !important; overflow:hidden !important; box-shadow:0 1px 2px rgb(8 29 19 / 4%) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-item-card:hover { background:#fff !important; border-color:#a9c7b5 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-card-head { position:relative; min-height:82px; padding:18px 20px; margin:0; background:#fff !important; border:0 !important; border-radius:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-card-head .directory-user-role-strip { position:absolute; top:16px; right:18px; margin:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-card-head h3 { padding-right:66px; font-size:15px; font-weight:650; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-card-meta { margin:0 20px; padding:16px 0; border-top:1px solid #e8edf1; gap:8px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-card-meta span { color:#7590aa; font-size:12px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-card-meta strong { font-size:12px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-role-badge { display:inline-flex; align-items:center; padding:3px 9px; border:1px solid #cbd7e7; border-radius:999px; background:#f1f5f9; color:#314860; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-role-badge.is-system { background:#f6edff; border-color:#e2c9ff; color:#6f31a4; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-role-badge.is-branch { background:#edf5ff; border-color:#bdd7ff; color:#215ca5; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-role-strip .user-card-status { display:inline-flex; align-items:center; gap:6px; padding:0 !important; border:0 !important; border-radius:0 !important; background:transparent !important; box-shadow:none !important; font-size:12px !important; font-weight:600; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-role-strip .user-card-status::before { content:""; width:6px; height:6px; flex:none; border-radius:50%; background:currentColor; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-role-strip .user-card-status.is-active { color:#176347 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-role-strip .user-card-status.is-inactive { color:#a33a3e !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-card-actions { justify-content:space-between; flex-wrap:wrap; margin:0 20px; padding:12px 0 16px; border-top:1px solid #e8edf1; gap:6px; opacity:1; transform:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-card-action-group { display:inline-flex; align-items:center; gap:6px; margin-left:auto; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-view-button--card { flex:none; width:auto; height:40px; padding:0 4px; border:0; background:transparent; color:#314fbd; font-size:12px; font-weight:650; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-action--card { display:inline-flex; align-items:center; width:auto; min-width:0; height:36px; padding:0 9px; gap:5px; border:1px solid #dbe4ed; background:#fff; color:#314860; font-size:12px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-action--card.is-danger { border-color:#ffc3ce; color:#c23852; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-action--card.is-enable { border-color:#abdcc6; color:#087f55; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-wrap { background:#fff !important; border-color:#dae5df !important; overflow-x:auto !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-table thead { background:#f8fafc !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-table tbody tr { background:#fff !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-table tbody tr:hover { background:#f8fafc !important; }
@media(max-width:1200px) { html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-card-view > .grid { grid-template-columns:repeat(2,minmax(0,1fr)); } html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar { flex-wrap:wrap; } }
@media(max-width:700px) { html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-card-view > .grid { grid-template-columns:minmax(0,1fr); } }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.user-directory-stat,.table-system-toolbar,.directory-item-card,.table-system-wrap) { background:#112218 !important; border-color:#29523d !important; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-stat,
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar,
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-item-card,
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-wrap { background:#112218 !important; border-color:#29523d !important; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.directory-user-card-head,.table-system-table tbody tr) { background:#112218 !important; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-item-card:hover { background:#152b20 !important; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-card-view .grid > .directory-item-card.bg-white { background-color:#112218 !important; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-stat > div > span,
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-directory-card-meta span { color:#a8c3b2; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap,.user-more-filters > summary,.user-directory-view-toggle) { background:#172d21 !important; border-color:#3a5d46 !important; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar .table-toolbar :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap):not(.is-filtered),
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters:not([data-has-active-filters]) > summary { background:#112218 !important; border-color:#3a5d46 !important; box-shadow:none !important; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar .table-toolbar :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap).is-filtered,
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-more-filters[data-has-active-filters] > summary { background:#193b29 !important; border-color:#6ba583 !important; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-role-strip .user-card-status.is-active { color:#9bdfb8 !important; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .directory-user-role-strip .user-card-status.is-inactive { color:#f2a6a8 !important; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page :is(.user-directory-view-toggle button.is-active,.user-directory-action--card) { background:#1b3527; color:#e8f5ed; border-color:#3a5d46; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .user-view-button--card { color:#a8cfff; }
html[data-theme="dark"] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-table thead { background:#172d21 !important; }
body.user-information-open .page-content { overflow:hidden !important; }
body.user-information-open .panel-scroll-top { display:none !important; }
@media(max-width:520px) { .user-information-fields { grid-template-columns:minmax(0,1fr); } }
/* Add/Edit user forms: reference header, quiet field groups, consistent footer. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-page,.user-edit-page) { min-height:0; padding-top:8px !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-page,.user-edit-page) > div { max-width:880px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-shell,.user-edit-page .modal-shell-card) { max-width:880px; overflow:hidden; background:var(--card) !important; border:1px solid var(--border) !important; border-radius:16px !important; box-shadow:0 12px 30px rgb(8 29 19 / 8%) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-head,.user-edit-page .modal-shell-card > .border-b) { background:var(--card) !important; border-color:var(--border) !important; color:var(--ink) !important; padding:20px 24px !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .user-edit-page .modal-shell-card:first-of-type > .border-b { background:var(--card) !important; border-color:var(--border) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-head,.user-edit-page .modal-shell-card > .border-b) :is(h2,p) { color:var(--ink) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-head,.user-edit-page .modal-shell-card > .border-b) p { color:var(--ink-muted) !important; font-size:13px; font-weight:400; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .user-form-heading { display:flex; align-items:center; gap:12px; min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .user-form-heading > div { min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .user-form-heading-icon { display:grid; place-items:center; width:40px; height:40px; flex:none; border-radius:10px; background:transparent; color:var(--ink-muted); font-size:19px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-badge,.user-edit-badge) { background:var(--surface-muted); color:var(--ink-muted); border-color:var(--border); border-radius:8px; font-size:11px; white-space:nowrap; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-shell,.user-edit-page .modal-shell-card) > :is(.p-5,.border-b) { min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-section,.user-edit-page .modal-shell-card .rounded-xl.border) { padding:0 0 20px !important; border:0 !important; border-bottom:1px solid var(--border) !important; border-radius:0 !important; background:transparent !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-section,.user-edit-page .modal-shell-card .rounded-xl.border):last-child { padding-bottom:0 !important; border-bottom:0 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .user-create-section [aria-label="Assigned account access"] > div { background:var(--surface-muted) !important; border-color:var(--border) !important; border-radius:10px !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-section,.user-edit-page .modal-shell-card .rounded-xl.border) h3 { color:var(--ink) !important; font-size:13px !important; font-weight:700 !important; letter-spacing:.04em !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-page,.user-edit-page) :is(.label-section,.form-hint) { color:var(--ink-muted) !important; font-weight:550 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-page,.user-edit-page) :is(.form-input,.form-select) { min-height:44px !important; background:var(--surface-muted) !important; border:1px solid var(--border) !important; border-radius:10px !important; color:var(--ink) !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-page,.user-edit-page) :is(.form-input,.form-select):focus { border-color:var(--brand) !important; outline:2px solid rgb(27 67 50 / 20%) !important; outline-offset:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-footer,.user-edit-page .modal-shell-card .border-t) { background:var(--card) !important; border-color:var(--border) !important; padding:16px 24px !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-footer,.user-edit-page .modal-shell-card .border-t) :is(.btn-outline,.btn-primary-custom) { min-height:44px; border-radius:10px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .user-create-page .password-field-action { min-width:44px; min-height:44px; border-color:var(--border); border-radius:10px; background:var(--surface-muted); color:var(--ink); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-page,.user-edit-page) :is(select:disabled,input:disabled) { opacity:.65; cursor:not-allowed; }
@media(max-width:640px) { html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-head,.user-edit-page .modal-shell-card > .border-b) { padding:16px !important; } html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-footer,.user-edit-page .modal-shell-card .border-t) { padding:16px !important; } html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) :is(.user-create-badge,.user-edit-badge) { display:none; } }
</style>
