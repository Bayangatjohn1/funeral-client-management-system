@once
<style>
html[data-theme] body.panel-shell-body { --worklist-hover:#D0E2D6; --worklist-search:#EAF2ED; --worklist-view:#087443; --worklist-edit:#A15C00; --worklist-danger:#B42318; }
html[data-theme='dark'] body.panel-shell-body { --worklist-hover:#14261D; --worklist-search:#182D22; --worklist-view:#5CE0A0; --worklist-edit:#FDC76A; --worklist-danger:#FDA29B; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent][data-record-results-page] > .table-system-card,
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent][data-record-results-page] .admin-table-card { background:transparent !important; border:0 !important; box-shadow:none !important; overflow:visible !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-toolbar { overflow:visible !important; padding:12px !important; gap:12px; border:1px solid var(--filter-ui-border) !important; border-radius:14px !important; background:var(--filter-ui-menu) !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] form.worklist-toolbar { display:grid !important; grid-template-columns:minmax(160px,1fr) 220px 220px auto !important; align-items:center; gap:14px !important; width:100%; flex:1 1 auto; min-width:0; margin:0 !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] :is(.worklist-control,.worklist-filter > summary) { box-sizing:border-box !important; display:flex; align-items:center; gap:10px; min-width:0; height:44px !important; padding:0 14px; border:1px solid var(--filter-ui-border) !important; border-radius:8px !important; background:var(--filter-ui-menu) !important; color:var(--filter-ui-text) !important; box-shadow:none !important; transform:none !important; transition:none !important; font-size:14px; font-weight:500; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] :is(.worklist-sort-control,.worklist-filter > summary):hover { background:var(--record-control-hover) !important; border-color:var(--record-search-frame-hover) !important; }
.worklist-filter { position:relative; min-width:0; }
.worklist-filter > summary { list-style:none; cursor:pointer; }
.worklist-filter > summary::-webkit-details-marker { display:none; }
.worklist-filter > summary > .bi-chevron-down { margin-left:auto; rotate:0deg; transition:rotate .16s ease; }
.worklist-filter[open] > summary > .bi-chevron-down { rotate:180deg; }
[data-worklist-count] { display:inline-grid; place-items:center; width:21px; height:21px; border-radius:50%; background:var(--filter-ui-focus); color:var(--filter-ui-menu); font-size:12px; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-filter-panel { position:absolute; top:calc(100% + 8px); left:0; z-index:70; width:min(320px,calc(100vw - 32px)); max-height:min(65vh,512px); display:flex; flex-direction:column; padding:0; overflow:hidden; border:1px solid var(--filter-ui-border); border-radius:10px; background:var(--filter-ui-menu); color:var(--filter-ui-text); }
.worklist-filter:not([open]) > .worklist-filter-panel { display:none !important; }
.worklist-filter-panel > :is(header,footer) { display:flex; align-items:center; gap:12px; padding:12px; flex-shrink:0; }
.worklist-filter-panel > header { justify-content:space-between; border-bottom:1px solid var(--filter-ui-border); }
.worklist-filter-panel > footer { justify-content:flex-end; border-top:1px solid var(--filter-ui-border); }
.worklist-filter-panel > header button { display:grid; place-items:center; width:36px; height:36px; padding:0; background:transparent; color:inherit; border:1px solid var(--filter-ui-border); border-radius:8px; }
.worklist-filter-fields { display:grid; gap:14px; padding:14px; overflow:auto; min-height:0; }
.worklist-filter-fields > label { display:grid; gap:8px; margin:0; font-size:12px; font-weight:600; color:var(--filter-ui-text); }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-filter-fields .worklist-control { position:relative; width:100%; padding:0; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-control > select { --filter-control-background:transparent; --filter-control-border:0; width:100% !important; height:42px !important; min-height:0 !important; background:transparent !important; border:0 !important; border-radius:inherit !important; color:var(--filter-ui-text) !important; padding:0 36px 0 40px !important; font-size:14px !important; font-weight:500; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-sort-control { position:relative; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-sort-label { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:14px; font-weight:500; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-sort-control > select { position:absolute; inset:0; opacity:0; cursor:pointer; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-toolbar .worklist-control { background:var(--filter-ui-menu) !important; }
.worklist-sort-control > .table-toolbar-select-icon { position:absolute; right:12px; top:50%; translate:0 -50%; transform:none !important; rotate:0deg !important; transition:rotate .16s ease !important; pointer-events:none; }
.worklist-sort-control.is-open > .table-toolbar-select-icon { transform:none !important; rotate:180deg !important; }
.worklist-sort-control > .table-toolbar-leading-icon { position:static !important; translate:none !important; transform:none !important; }
.worklist-filter-fields .table-toolbar-leading-icon { position:absolute; left:14px; top:50%; translate:0 -50%; transform:none !important; pointer-events:none; }
.worklist-filter-fields .table-toolbar-select-icon { position:absolute; right:12px; top:50%; translate:0 -50%; transform:none !important; rotate:0deg; pointer-events:none; }
.worklist-filter-fields .is-open > .table-toolbar-select-icon { rotate:180deg; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .filter-select-menu { background:var(--filter-ui-menu) !important; color:var(--filter-ui-text); border:1px solid var(--filter-ui-border); padding:6px; border-radius:10px; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .filter-select-menu__item + .filter-select-menu__item { margin-top:4px !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .filter-select-menu__item { display:flex !important; align-items:center; gap:10px; min-height:40px; padding:8px 10px; border:1px solid transparent !important; background:transparent !important; color:var(--filter-ui-text) !important; font-size:14px; font-weight:400; box-shadow:none !important; transform:none !important; transition:none !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .filter-select-menu__item:is(:hover,:focus-visible,[aria-selected='true']) { background:var(--worklist-hover) !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .filter-select-menu__item[aria-selected='true'] { font-weight:650; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-apply { min-height:44px; padding:0 16px; border:0; border-radius:8px; background:var(--filter-ui-focus) !important; color:var(--filter-ui-menu) !important; font-size:14px; font-weight:600; }
.worklist-search-track { min-width:0; }
html[data-theme] body.panel-shell-body :is(.page-content,.panel-overlay-content:where(#paymentFormModal)) [data-worklist-consistent] .worklist-search-control { position:relative; box-sizing:border-box; width:100%; height:44px; border:1px solid var(--filter-ui-border); border-radius:14px; background:var(--worklist-search); transition:none !important; }
.worklist-search-control > .bi-search { position:absolute; left:14px; top:50%; translate:0 -50%; pointer-events:none; }
html[data-theme] body.panel-shell-body :is(.page-content,.panel-overlay-content:where(#paymentFormModal)) [data-worklist-consistent] .worklist-search-control > input { --filter-control-background:transparent; --filter-control-border:0; box-sizing:border-box; width:100%; height:42px !important; padding:0 128px 0 40px !important; border:0 !important; border-radius:inherit !important; background:transparent !important; color:var(--filter-ui-text) !important; font-size:14px !important; font-weight:400 !important; transition:none !important; }
.worklist-search-control > input::-webkit-search-cancel-button { display:none; }
html[data-theme] body.panel-shell-body :is(.page-content,.panel-overlay-content:where(#paymentFormModal)) [data-worklist-consistent] .worklist-search-clear { position:absolute; right:92px; top:50%; translate:0 -50%; width:28px; height:28px; padding:0; border:0 !important; background:transparent !important; color:var(--filter-ui-text) !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body :is(.page-content,.panel-overlay-content:where(#paymentFormModal)) [data-worklist-consistent] .worklist-search-submit { position:absolute; inset:0 0 0 auto; width:84px; padding:0; border:0; border-left:1px solid var(--filter-ui-border); border-radius:0 13px 13px 0; background:var(--record-search-action) !important; color:var(--record-search-action-text) !important; font-size:14px; font-weight:500; box-shadow:none !important; transform:none !important; }
.worklist-search-matches { position:absolute; left:0; right:0; top:calc(100% + 6px); z-index:90; max-height:280px; overflow:auto; padding:6px; border:1px solid var(--filter-ui-border); border-radius:10px; background:var(--filter-ui-menu); }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-match { display:flex; align-items:center; gap:10px; width:100%; min-height:40px; padding:8px 10px; border:0 !important; border-radius:6px; white-space:nowrap; text-align:left; background:transparent !important; color:var(--filter-ui-text) !important; font-size:14px; font-weight:600; box-shadow:none !important; transform:none !important; }
.worklist-match > span { min-width:0; overflow:hidden; text-overflow:ellipsis; }
.worklist-match > span:first-child { max-width:65%; }
.worklist-match > span:last-child:not(:first-child) { display:flex; gap:10px; align-items:center; font-size:13px; font-weight:400; }
.worklist-match > span:last-child:not(:first-child)::before { content:'·'; }
.worklist-match mark { background:transparent; color:inherit; font:inherit; text-decoration:underline; text-decoration-thickness:1px; text-underline-offset:3px; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-match:is(:hover,:focus-visible) { background:var(--worklist-hover) !important; }
.directory-view-toggle { align-self:center; justify-self:end; white-space:nowrap; }
.worklist-applied { grid-column:1/-1; display:flex; align-items:center; flex-wrap:wrap; gap:8px; min-height:36px; }
.worklist-applied:empty,.worklist-applied:not(:has(.worklist-chip)):has(.worklist-clear[hidden]) { display:none; }
.worklist-chip { flex:0 0 auto; font-family:'Plus Jakarta Sans','Segoe UI',sans-serif; }
.worklist-chip-remove { display:inline-grid; flex:none; width:1.35rem; height:1.35rem; place-items:center; border:0; border-radius:50%; background:transparent; color:var(--chip-ink); text-decoration:none; }
.worklist-chip-remove:is(:hover,:focus-visible) { background:rgb(0 0 0 / .08); color:var(--chip-ink); outline:2px solid currentColor; outline-offset:1px; }
html[data-theme='dark'] .worklist-chip-remove:is(:hover,:focus-visible) { background:rgb(255 255 255 / .12); }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-clear { margin-left:auto; min-height:36px; padding:0 10px; border:1px solid transparent !important; background:transparent !important; color:var(--worklist-danger) !important; font-weight:400 !important; border-radius:6px; box-shadow:none !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-clear:is(:hover,:focus-visible) { border-color:color-mix(in srgb,var(--worklist-danger) 35%,transparent) !important; background:transparent !important; color:var(--worklist-danger) !important; outline:none; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-footer { order:99 !important; position:static !important; display:flex !important; min-height:64px; margin-top:12px !important; padding:12px 16px !important; border:1px solid var(--filter-ui-border) !important; border-radius:6px !important; background:var(--filter-ui-menu) !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-footer.is-pagination-hidden { display:none !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-footer .table-paginator { width:100%; padding:0 !important; border:0 !important; background:transparent !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-list { padding:0 !important; border-radius:6px !important; overflow:visible !important; background:var(--filter-ui-menu) !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-wrap { min-height:clamp(20rem,40vh,30rem); border-radius:6px !important; overflow-x:auto !important; background:var(--filter-ui-menu) !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-list .table-system-table { background:transparent !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-list .table-system-table :is(thead,thead tr,th) { background:transparent !important; color:var(--ink) !important; font-size:13px !important; font-weight:600 !important; letter-spacing:.055em; text-transform:none; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-table td { padding:18px 16px !important; vertical-align:middle; font-size:14px; font-weight:400; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-table tbody tr:is(:hover,:focus-within) { background:transparent !important; box-shadow:inset 0 0 0 1px var(--filter-ui-border) !important; transform:none !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-table tbody tr[data-clickable-row]:is(:hover,:focus-within) { box-shadow:none !important; outline:var(--record-row-outline-width) solid var(--record-row-outline) !important; outline-offset:0 !important; }
html[data-theme] body.panel-shell-body .directory-page { --worklist-view:#1B4332; }
html[data-theme='dark'] body.panel-shell-body .directory-page { --worklist-view:#5CE0A0; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] :is(.user-directory-action,.user-view-button,.branch-reference-icon,.open-branch-view,.audit-view-details,.audit-detail-trigger) { --worklist-action:var(--worklist-view); background:transparent !important; color:var(--ink) !important; border:0 !important; box-shadow:none !important; transform:none !important; filter:none !important; transition:none !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] :is(.user-directory-action[href],.branch-reference-icon[data-branch-card-action='edit']) { --worklist-action:var(--worklist-edit); }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] :is(.user-directory-action,.branch-reference-icon).is-danger { --worklist-action:var(--worklist-danger); }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] :is(.user-directory-action,.user-view-button,.branch-reference-icon,.open-branch-view,.audit-view-details,.audit-detail-trigger):is(:hover,:focus-visible) > i { color:var(--worklist-action) !important; filter:none !important; text-shadow:none !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] :is(button,input,summary):focus-visible { outline:2px solid var(--filter-ui-focus); outline-offset:2px; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-control:has(>select:focus-visible) { outline:2px solid var(--filter-ui-focus) !important; outline-offset:2px; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-control > select:focus-visible { outline:none !important; }
[data-worklist-consistent] [hidden] { display:none !important; }
@media(min-width:1201px){.worklist-search-control{max-width:min(560px,calc(100% - 24px));}}
@media(max-width:1100px){html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] form.worklist-toolbar{grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important;}.worklist-search-track{grid-column:1/-1;}.directory-view-toggle{grid-column:2;}}
@media(max-width:600px){html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] form.worklist-toolbar{grid-template-columns:minmax(0,1fr) !important;}.directory-view-toggle{grid-column:1;justify-self:start;}}
@media(max-width:767px){html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-wrap{min-height:18rem;}.worklist-filter-panel{left:auto!important;right:0;}}
@media(prefers-reduced-motion:reduce){.worklist-filter>summary>.bi-chevron-down{transition:none;}}
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-toolbar.worklist-audit-toolbar { display:flex !important; flex-wrap:wrap; }
.worklist-audit-toolbar > .worklist-filter { width:160px; }
.worklist-audit-toolbar > .worklist-search-track { flex:1 1 280px; }
.worklist-audit-toolbar > .worklist-applied { flex-basis:100%; }
@media(max-width:600px) { .worklist-audit-toolbar > .worklist-search-track { flex-basis:100%; } }
.worklist-page-size { display:flex; align-items:center; gap:12px; margin:0; font-size:14px; font-weight:400; }
.worklist-page-size > .worklist-control { position:relative; width:88px; padding:0 !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-page-size select { padding-left:12px !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] [data-record-results='cards'] { min-height:clamp(20rem,40vh,30rem); }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-table tbody tr { background:var(--filter-ui-menu) !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .table-system-table tbody tr > td { background:transparent !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent][data-record-results-page='audit'] .table-system-empty { height:calc(clamp(20rem,40vh,30rem) - 64px); border:0 !important; border-radius:0 !important; text-align:center; vertical-align:middle; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .status-badge { background:transparent !important; border:0 !important; padding:0 !important; border-radius:0 !important; box-shadow:none !important; font-weight:500; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .status-badge-success { color:var(--worklist-view) !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .status-badge-danger { color:var(--worklist-danger) !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .audit-details-button { display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border:0 !important; background:transparent !important; color:var(--ink) !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .audit-details-button:is(:hover,:focus-visible)>i { color:var(--worklist-view) !important; filter:none !important; }
html[data-theme] body.panel-shell-body .worklist-date-panel,
html[data-theme] body.panel-shell-body dialog[data-report-date-panel]:popover-open { position:fixed; inset:auto; margin:0; padding:16px; max-width:calc(100vw - 24px); max-height:calc(100dvh - 24px); overflow:auto; border:1px solid var(--filter-ui-border); border-radius:10px; background:var(--filter-ui-menu); color:var(--filter-ui-text); box-shadow:none; }
dialog[data-report-date-panel]:not(:popover-open) { display:none; }
dialog[data-report-date-panel]:popover-open { display:block !important; }
html[data-theme] body.panel-shell-body dialog[data-report-date-panel]::backdrop { background:transparent !important; backdrop-filter:none !important; }
.worklist-date-panel { display:none; }
.worklist-date-panel:popover-open { display:grid; gap:14px; }
.worklist-date-panel label { display:grid; gap:8px; font-size:13px; }
.worklist-date-panel input { min-width:0; width:100%; height:44px; border:1px solid var(--filter-ui-border); border-radius:8px; background:var(--filter-ui-menu); color:var(--filter-ui-text); padding:8px; }
.worklist-date-panel > div { display:flex; justify-content:flex-end; gap:10px; }
.worklist-date-panel button { min-height:40px; padding:0 12px; border:1px solid var(--filter-ui-border); border-radius:8px; background:var(--filter-ui-menu); color:var(--filter-ui-text); }
html[data-theme] body.panel-shell-body :is(.reports-page,.ba-shell) [data-report-filter-controls] { display:flex !important; flex-wrap:wrap; align-items:center; gap:12px !important; justify-content:flex-start !important; overflow:visible; }
html[data-theme] body.panel-shell-body :is(.reports-page,.ba-shell) [data-report-filter-controls] > .reports-module-control-wrap { flex:0 1 15rem; min-width:min(100%, 11.5rem); max-width:100%; margin:0; }
html[data-theme] body.panel-shell-body :is(.reports-page,.ba-shell) [data-report-filter-controls] .reports-module-control-wrap { position:relative; display:flex; align-items:center; height:44px; padding:0; border:1px solid var(--filter-ui-border) !important; border-radius:8px !important; background:var(--filter-ui-menu) !important; }
html[data-theme] body.panel-shell-body :is(.reports-page,.ba-shell) [data-report-filter-controls] select { --filter-control-background:transparent; --filter-control-border:0; display:block !important; position:static !important; clip-path:none !important; width:100% !important; min-width:0 !important; max-width:none !important; height:42px !important; padding:0 36px 0 40px !important; border:0 !important; border-radius:8px !important; background:transparent !important; color:var(--filter-ui-text) !important; font-size:14px !important; font-weight:500; pointer-events:auto !important; }
html[data-theme] body.panel-shell-body :is(.reports-page,.ba-shell) [data-report-filter-controls] .reports-module-control-wrap > i:first-child { position:absolute; inset-inline-start:14px; top:50%; translate:0 -50%; transform:none; font-size:16px; line-height:1; color:var(--filter-ui-text); pointer-events:none; }
html[data-theme] body.panel-shell-body :is(.reports-page,.ba-shell) [data-report-filter-controls] .reports-module-control-wrap::after { border-color:var(--filter-ui-text); }
html[data-theme] body.panel-shell-body :is(.reports-page,.ba-shell) [data-report-filter-controls] .reports-module-control-wrap:is(:hover,.is-open) { background:var(--filter-ui-bg-hover,var(--filter-ui-menu)) !important; border-color:var(--filter-ui-border-strong,var(--filter-ui-border)) !important; }
html[data-theme] body.panel-shell-body :is(.reports-page,.ba-shell) [data-report-filter-controls] .reports-module-control-wrap:has(select:focus-visible) { outline:2px solid var(--filter-ui-text); outline-offset:2px; }
html[data-theme] body.panel-shell-body :is(.reports-page,.ba-shell) [data-report-filter-controls] .reports-module-control-wrap.is-open { z-index:20; }
@media(max-width:600px) {
    html[data-theme] body.panel-shell-body :is(.reports-page,.ba-shell) [data-report-filter-controls] > .reports-module-control-wrap { flex:1 1 100%; min-width:0; }
}
@media(max-width:767px){html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] [data-record-results='cards']{min-height:18rem;}}
</style>
@endonce

<style>
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] .worklist-search-submit:hover { background:var(--record-search-action-hover) !important; }
</style>

<style>
html[data-theme] body.panel-shell-body .page-content [data-worklist-consistent] :is(.user-view-button,.open-branch-view).record-row-view { color:var(--record-view-ink,var(--ink)) !important; }
</style>
