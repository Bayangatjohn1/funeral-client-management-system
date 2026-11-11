{{-- Shared flat surfaces and connected toolbar/table edges for approved worklists. --}}
@php
    $surfaceScope = 'html[data-theme] body.panel-shell-body.record-surface-ui:is(.staff-ui,.branch-admin-ui,.system-admin-ui,.owner-ui)';
    $pageScope = $surfaceScope.' .page-content';
    $caseStatusScope = $surfaceScope.' :is(.records-worklist-table,.case-summary-drawer)';
    $headerScope = $surfaceScope.' .page-content :is(.panel-page-header.panel-title-surface,.service-management-hub.service-reference .service-reference-header)';
    $controlScope = $surfaceScope.' :is(#pmFilterForm,#historicalCasesFilterForm,[data-case-filter],[data-worklist-form],[data-service-filter-form])';
    $rowScope = $surfaceScope.' :is(.page-content .records-page,.page-content .master-records-page,.page-content .historical-cases-page,.page-content .directory-page,.page-content .service-management-hub,.page-content .payment-monitoring-page,.pm-transactions-backdrop)';
    $joinedHeaderScope = $pageScope.':has(:is(.branch-kpi-strip,.user-directory-stats,.pm-kpis,.records-page,.master-records-page)) > .panel-page-header.panel-title-surface';
    $chevronScope = $controlScope.' :is(.case-compact-more > [data-case-more-icon],.case-compact-sort-filter > .case-compact-sort-chev,.case-compact-date-filter > .case-compact-date-chev,.worklist-filter > summary > .bi-chevron-down,.worklist-sort-control > .table-toolbar-select-icon,.pm-more-filters > summary > i:last-child,.pm-sort-control > .pm-sel-chev,.catalog-control > summary > .catalog-control-chevron)';
    $openChevronScope = $controlScope.' :is(.case-compact-more[aria-expanded="true"] > [data-case-more-icon],.case-compact-sort-filter.is-open > .case-compact-sort-chev,.case-compact-date-filter.is-open > .case-compact-date-chev,.worklist-filter[open] > summary > .bi-chevron-down,.worklist-sort-control.is-open > .table-toolbar-select-icon,.pm-more-filters[open] > summary > i:last-child,.pm-sort-control.is-open > .pm-sel-chev,.catalog-control[open] > summary > .catalog-control-chevron)';
@endphp
<style>
{!! $surfaceScope !!} {
    --rs-page:#E6EEE8; --rs-header:#D5E4D9; --rs-surface:#F7FAF8;
    --rs-panel:#D5E4D9; --rs-menu:#F0F6F2; --rs-grid-card:#EAF2ED; --rs-divider:#B8CEC0;
    --rs-kpi-surface:#E4EFE7;
    --rs-item-hover:var(--record-row-hover); --rs-success:var(--record-status-active); --rs-warning:var(--record-status-draft);
    --rs-filter-padding:.9rem; --rs-filter-field-gap:.7rem; --rs-filter-label-gap:.55rem; --rs-filter-option-gap:4px; --rs-filter-heading-gap:12px;
    --rs-header-muted:#4D6555; --rs-control:#EAF2ED; --rs-hover:#D8E7DD; --rs-text:#123524; --rs-muted:#617568;
    --filter-ui-menu:var(--rs-surface); --filter-ui-surface:var(--rs-control);
    --filter-ui-border:transparent; --filter-ui-text:var(--rs-text); --filter-ui-muted:var(--rs-muted);
    --record-control-hover:var(--rs-hover); --record-choice-surface:var(--rs-hover);
    background-color:var(--rs-page) !important;
}
{!! str_replace('html[data-theme]', "html[data-theme='dark']", $surfaceScope) !!} {
    --rs-page:#0D1712; --rs-header:#1C3025; --rs-surface:#15251C;
    --rs-panel:#1C3025; --rs-menu:#304C3B; --rs-grid-card:#101E16; --rs-divider:#355241;
    --rs-kpi-surface:#1C3025;
    --rs-header-muted:#A7BCAE; --rs-control:#22392C; --rs-hover:#2D4938; --rs-text:#E7F0E9; --rs-muted:#A7BCAE;
}
{!! $pageScope !!} {
    background-color:var(--rs-page) !important; background-image:none !important; box-shadow:none !important; transition:none !important;
}

{!! $pageScope !!} > .admin-table-page,
{!! $pageScope !!} > .records-page,
{!! $pageScope !!} > .master-records-page,
{!! $pageScope !!} > .historical-cases-page,
{!! $pageScope !!} > .owner-page-shell,
{!! $pageScope !!} > .pm-page { background-color:var(--rs-page) !important; background-image:none !important; box-shadow:none !important; transition:none !important; }
{!! $pageScope !!} {
    --ink:var(--rs-text); --ink-muted:var(--rs-muted);
    --directory-card:var(--rs-surface); --catalog-search-surface:var(--rs-control);
    --worklist-search:var(--rs-control); --pm-search-surface:var(--rs-control);
    --pm-control-surface:var(--rs-control); --case-search-bg:var(--rs-control);
}
{!! $surfaceScope !!} .page-content::before,
{!! $surfaceScope !!} .page-content::after,
{!! $headerScope !!}::before,
{!! $headerScope !!}::after { content:none !important; }
{!! $headerScope !!} {
    background-color:var(--panel-title-background,var(--rs-header)) !important; background-image:none !important;
    padding:var(--panel-title-block-padding,16px) 16px !important;
    border-radius:var(--panel-title-radius,20px) !important;
    border-color:transparent !important; box-shadow:none !important; transition:none !important; text-shadow:none !important;
}
{!! $headerScope !!} :is(h1,h2) { color:var(--rs-text) !important; text-shadow:none !important; transition:none !important; }
{!! $headerScope !!} p { color:var(--rs-header-muted,var(--rs-muted)) !important; text-shadow:none !important; transition:none !important; }
{!! $headerScope !!} .owner-records-back {
    background-color:var(--rs-control) !important; border-color:transparent !important; box-shadow:none !important; transition:none !important;
}
{!! $pageScope !!} .branch-kpi-card,
{!! $pageScope !!} .user-directory-stat,
{!! $pageScope !!} .pm-kpi {
    --directory-card:var(--rs-kpi-surface);
    background-color:var(--rs-kpi-surface) !important; background-image:none !important;
    border-color:transparent !important; box-shadow:none !important; transition:none !important; text-shadow:none !important;
}
{!! $pageScope !!} :is(.user-directory-stat > i,.branch-kpi-card__icon,.pm-kpi-icon) {
    background-color:var(--rs-control) !important; border-color:transparent !important;
    box-shadow:none !important; transition:none !important; text-shadow:none !important;
}
{!! $pageScope !!} :is(.user-directory-stat strong,.branch-kpi-card__value,.pm-kpi-value:not(.bad)) { color:var(--rs-kpi-color,var(--rs-text)) !important; }
{!! $pageScope !!} :is([data-kpi-tone="success"],.pm-kpi-value.good) { --rs-kpi-color:var(--rs-success); }
{!! $pageScope !!} :is([data-kpi-tone="warning"],.pm-kpi-value.warn) { --rs-kpi-color:var(--rs-warning); }
{!! $pageScope !!} .branch-kpi-card[data-kpi-tone] .branch-kpi-card__icon { color:var(--rs-kpi-color) !important; }
{!! $pageScope !!} :is(.user-directory-stat small,.branch-kpi-card__desc,.pm-kpi-desc) { color:var(--rs-muted) !important; }
{!! $pageScope !!} :is(.table-system-toolbar,.case-records-top-wrapper,.case-records-controls,.hc-toolbar-shell,.pm-toolbar-shell,.service-hub-toolbar) {
    background-color:var(--rs-surface) !important; background-image:none !important;
    border-color:transparent !important; box-shadow:none !important; transition:none !important;
}
{!! $pageScope !!} :is(.pm-toolbar,.worklist-toolbar,.hc-toolbar,.service-hub-filter,.case-record-filters,.service-viewbar) {
    background-color:transparent !important; border-color:transparent !important; box-shadow:none !important; transition:none !important;
}
{!! $pageScope !!} :is(.worklist-control,.worklist-filter > summary,.catalog-control > summary,.case-compact-more,.case-compact-sort-filter,.pm-more-filters > summary,.pm-sort-control,.case-page-size-control,.directory-view-toggle,.service-view-toggle) {
    --border:transparent; --filter-ui-menu:var(--rs-control);
    --filter-control-background:transparent; --filter-control-border:0;
    background-color:var(--rs-control) !important; border-color:transparent !important;
    box-shadow:none !important; transition:none !important; text-shadow:none !important;
}
{!! $pageScope !!} :is(.shared-record-search,.service-record-search,.case-compact-search-control,.worklist-search-control) {
    --border:transparent; --filter-ui-menu:var(--rs-control);
    --catalog-search-surface:var(--rs-control); --worklist-search:var(--rs-control);
    --pm-search-surface:var(--rs-control); --case-search-bg:var(--rs-control);
    background-color:var(--rs-control) !important; border-color:transparent !important; box-shadow:none !important; transition:none !important;
}
{!! $pageScope !!} :is(.worklist-control,.worklist-filter > summary,.catalog-control > summary,.case-compact-more,.case-compact-sort-filter,.pm-more-filters > summary,.pm-sort-control):is(:hover,.is-open),
{!! $pageScope !!} :is(.worklist-filter,.pm-more-filters,.catalog-control)[open] > summary {
    background-color:var(--rs-hover) !important; border-color:transparent !important; box-shadow:none !important; transition:none !important;
}
{!! $pageScope !!} :is(.filter-select-menu,.worklist-filter-panel,.case-compact-drawer,.pm-more-panel,.catalog-control-panel) {
    background-color:var(--rs-surface) !important; border-color:transparent !important;
    box-shadow:none !important; transition:none !important; text-shadow:none !important;
}
{!! $pageScope !!} :is(.uniform-record-filters,.case-record-filters,.pm-filters,.service-hub-filter) :is(input,select) {
    border-color:transparent !important; box-shadow:none !important; transition:none !important;
}
{!! $pageScope !!} :is(.uniform-record-filters,.case-record-filters,.pm-filters,.service-hub-filter) :is(input,select,button,summary):focus-visible {
    outline:2px solid var(--filter-ui-focus) !important; outline-offset:2px !important;
    box-shadow:none !important; transition:none !important;
}
{!! $pageScope !!} :is(.case-records-top-wrapper,.table-system-toolbar,.pm-toolbar-shell,.service-hub-toolbar,.user-directory-stat,.branch-kpi-card,.pm-kpi) *,
{!! $headerScope !!} * { box-shadow:none !important; text-shadow:none !important; }
{!! $pageScope !!} .case-records-controls { background-color:transparent !important; }
{!! $pageScope !!} :is(.directory-item-card,.service-package-card,.table-system-card,.table-system-wrap,.row-action-dropdown) {
    box-shadow:none !important; text-shadow:none !important;
}
{!! $surfaceScope !!} .shared-action-tooltip { box-shadow:none !important; transition:none !important; text-shadow:none !important; }
/* Controls own their fill; native selects must not repaint the old inner surface. */
{!! $controlScope !!} :is([data-filter-select-wrap],.pm-page-size-control,.case-page-size-control,.case-compact-branch,.case-compact-date-filter) {
    --filter-ui-menu:var(--rs-control); --filter-control-background:transparent; --filter-control-border:0;
    background-color:var(--rs-control) !important; border-color:transparent !important; box-shadow:none !important;
    border-radius:8px;
}
{!! $controlScope !!} :is(select,input[type=date],input[name=per_page]) {
    background-color:var(--rs-control) !important; color:var(--rs-text) !important;
    border-color:transparent !important; box-shadow:none !important; transition:none !important;
    border-radius:inherit;
}
{!! $controlScope !!} :is(.filter-select-menu,.case-page-size-menu,.catalog-control-panel,.worklist-filter-panel,.case-compact-drawer,.pm-more-panel),
{!! $surfaceScope !!} :is(.case-date-dialog,[data-case-custom-panel],.pm-date-popover) {
    background-color:var(--rs-panel) !important; color:var(--rs-text) !important;
    border-color:transparent !important; box-shadow:none !important;
}
{!! $controlScope !!} :is(.case-compact-advanced-head,.case-compact-advanced-actions,.pm-filter-head,.pm-filter-apply,.worklist-filter-panel > header,.worklist-filter-panel > footer) {
    background-color:var(--rs-panel) !important; border-color:transparent !important; box-shadow:none !important;
}
{!! $controlScope !!} :is(.filter-select-menu,.case-page-size-menu) {
    background-color:var(--rs-menu) !important;
}
{!! $controlScope !!} :is(.case-compact-pop-apply,.worklist-apply,[data-pm-apply-filters]) {
    background-color:var(--reference-action-bg) !important; color:var(--reference-action-text) !important;
    border-color:transparent !important; box-shadow:none !important;
}
{!! $controlScope !!} :is(.case-compact-pop-apply,.worklist-apply,[data-pm-apply-filters]):hover {
    background-color:var(--reference-action-hover) !important;
}
{!! $controlScope !!} :is(.filter-select-menu__item,.catalog-control-option):hover {
    background-color:var(--rs-item-hover) !important; border-color:transparent !important; box-shadow:none !important;
}
{!! $controlScope !!} [data-filter-select-wrap].is-open > select { background-color:var(--rs-hover) !important; }
/* Record containers, grid cards and pagination share the same flat surface. */
{!! $pageScope !!} :is(.table-system-wrap,.pm-panel,.table-system-pagination,.pm-foot,.service-pagination,.directory-item-card,.service-package-card,.table-paginator) {
    background-color:var(--rs-surface) !important; border-color:transparent !important;
    box-shadow:none !important; text-shadow:none !important; transition:none !important;
}
{!! $pageScope !!} :is(.pm-row-list,.pm-case-item,.pm-records-body,.table-system-table,.service-package-table) {
    background-color:var(--rs-surface) !important; border-color:transparent !important; box-shadow:none !important;
}
{!! $pageScope !!} :is(.table-system-table,.pm-history-table) :is(thead,thead tr,th) {
    background-color:var(--rs-control) !important; border-color:transparent !important; box-shadow:none !important;
}
{!! $pageScope !!} .table-system-table tbody td { background-color:transparent !important; }
{!! $pageScope !!} :is(.table-paginator-btn,.table-paginator-page) {
    background-color:var(--rs-control) !important; border-color:transparent !important; box-shadow:none !important;
}
{!! $pageScope !!} :is(.table-paginator-btn,.table-paginator-page):hover { background-color:var(--rs-hover) !important; }
{!! $pageScope !!} :is(.table-paginator-btn,.table-paginator-page):is(.is-active,[aria-current=page]) {
    background-color:var(--reference-action-bg) !important; color:var(--reference-action-text) !important;
}
{!! $pageScope !!} :is(.branch-card-metric > div,.service-package-price,.package-price-block) {
    background-color:var(--rs-control) !important; border-color:transparent !important; box-shadow:none !important;
}
/* Worklist column headers inherit the table surface, without a separate band. */
{!! $pageScope !!} :is(.records-page,.master-records-page,.historical-cases-page,.directory-page,.service-management-hub) .table-system-table :is(thead,thead tr,th) {
    background-color:transparent !important;
}
/* Match the toolbar divider beneath the table's column labels. */
{!! $pageScope !!} .table-system-table thead tr:last-child > th,
{!! $pageScope !!} .payment-monitoring-page .pm-record-head {
    border-bottom:1px solid var(--rs-divider) !important;
}
/* Join the toolbar and table without clipping sticky controls or their menus. */
{!! $pageScope !!} .records-page { row-gap:0 !important; }
{!! $pageScope !!} .records-page > .case-records-top-wrapper { margin-top:.55rem !important; }
{!! $pageScope !!} .records-page > .case-records-top-wrapper,
{!! $pageScope !!} .master-records-page .admin-table-toolbar,
{!! $pageScope !!} .historical-cases-page .hc-toolbar-shell,
{!! $pageScope !!} .payment-monitoring-page .pm-toolbar-shell,
{!! $pageScope !!} .directory-page .table-system-card:has([aria-label="Table view"][aria-pressed="true"]) > .table-system-toolbar,
{!! $pageScope !!} .service-hub-card:has([data-catalog-view="table"][aria-pressed="true"]) > .service-hub-toolbar {
    margin-bottom:0 !important; border-bottom:1px solid var(--rs-divider) !important;
    border-radius:12px 12px 0 0 !important;
}
{!! $pageScope !!} :is(.records-page,.master-records-page) .table-system-list,
{!! $pageScope !!} .directory-page .table-system-card:has([aria-label="Table view"][aria-pressed="true"]) :is([data-record-results="table"],.table-system-list) {
    margin-top:0 !important; padding:0 !important; border-top-width:0 !important; border-radius:0 !important;
}
{!! $pageScope !!} :is(.records-page,.master-records-page) .table-system-wrap,
{!! $pageScope !!} .historical-cases-page .hc-results,
{!! $pageScope !!} .payment-monitoring-page .pm-panel,
{!! $pageScope !!} .directory-page .table-system-card:has([aria-label="Table view"][aria-pressed="true"]) .table-system-wrap,
{!! $pageScope !!} .service-hub-card:has([data-catalog-view="table"][aria-pressed="true"]) .service-hub-panels .table-system-wrap {
    margin-top:0 !important; border-top-width:0 !important; border-radius:0 0 12px 12px !important;
}
{!! $pageScope !!} .service-hub-card:has([data-catalog-view="table"][aria-pressed="true"]) { row-gap:0 !important; }
{!! $pageScope !!} .service-hub-card:has([data-catalog-view="table"][aria-pressed="true"]) > .service-section-intro { margin-bottom:24px; }
{!! $pageScope !!} .service-hub-card:has([data-catalog-view="table"][aria-pressed="true"]) .service-hub-panels { margin-top:0 !important; }
/* Grid and toolbar retain the original surface while sharing connected edges. */
{!! $pageScope !!} :is(.directory-item-card,.service-package-card) {
    --directory-card:var(--rs-grid-card); --bm-card:var(--rs-grid-card);
    background-color:var(--rs-grid-card) !important;
}
{!! $pageScope !!} .directory-page .table-system-card:has([aria-label="Grid view"][aria-pressed="true"]) > .table-system-toolbar,
{!! $pageScope !!} .service-hub-card:has([data-catalog-view="cards"][aria-pressed="true"]) > .service-hub-toolbar {
    background-color:var(--rs-surface) !important; margin-bottom:0 !important;
    border-radius:12px 12px 0 0 !important; border-bottom:1px solid var(--rs-divider) !important;
}
{!! $pageScope !!} .directory-page .table-system-card:has([aria-label="Grid view"][aria-pressed="true"]) > [data-record-results="cards"],
{!! $pageScope !!} .service-hub-card:has([data-catalog-view="cards"][aria-pressed="true"]) > .service-hub-panels {
    background-color:var(--rs-surface) !important; background-image:none !important;
    margin-top:0 !important; padding:16px !important; border:0 !important;
    border-radius:0 0 12px 12px !important; box-shadow:none !important;
}
{!! $pageScope !!} .service-hub-card:has([data-catalog-view="cards"][aria-pressed="true"]) { row-gap:0 !important; }
{!! $pageScope !!} .service-hub-card:has([data-catalog-view="cards"][aria-pressed="true"]) > .service-section-intro { margin-bottom:24px; }
/* Main sections use the Master/Payment rhythm; connected surfaces have no gap. */
{!! $pageScope !!} > .panel-page-header.panel-title-surface { margin-bottom:16px !important; }
{!! $pageScope !!} > :is(.records-page,.master-records-page,.payment-monitoring-page,.directory-page),
{!! $pageScope !!} .directory-page > .mx-auto { margin-top:0 !important; padding-top:0 !important; }
{!! $pageScope !!} .directory-page .space-y-4 { display:flex; flex-direction:column; gap:16px; }
{!! $pageScope !!} .directory-page .space-y-4 > * { margin-block:0 !important; }
{!! $pageScope !!} .service-management-hub > .mx-auto { gap:16px !important; }
{!! $pageScope !!} > .service-management-hub { padding-top:12px !important; }
{!! $pageScope !!} .service-management-hub .service-reference-header { border-width:0 !important; }
{!! $pageScope !!} .service-management-hub .service-reference-heading {
    padding-bottom:16px !important; border-bottom:1px solid var(--rs-divider) !important;
}
{!! $pageScope !!} .service-management-hub .service-hub-nav { padding-top:16px !important; }
/* Existing page actions sit last in their worklist toolbar. */
{!! $pageScope !!} .directory-page form.worklist-toolbar:has(.record-toolbar-action) {
    grid-template-columns:minmax(200px,1fr) 160px 160px auto auto !important;
    grid-template-areas:"search filter sort view action" !important; gap:12px !important;
}
{!! $pageScope !!} .payment-monitoring-page .pm-toolbar:has(.record-toolbar-action) {
    grid-template-columns:minmax(200px,1fr) 160px 160px auto auto !important;
    grid-template-areas:"search filter sort size action" !important; gap:12px !important;
}
{!! $pageScope !!} :is(.directory-page .worklist-search-track,.payment-monitoring-page .pm-toolbar > .search) { grid-area:search !important; min-width:0; }
{!! $pageScope !!} :is(.directory-page .worklist-filter,.payment-monitoring-page .pm-more-filters) { grid-area:filter !important; }
{!! $pageScope !!} :is(.directory-page .worklist-sort-control,.payment-monitoring-page .pm-sort-control) { grid-area:sort !important; }
{!! $pageScope !!} .directory-page .directory-view-toggle { grid-area:view !important; justify-self:end; }
{!! $pageScope !!} .payment-monitoring-page .pm-page-size { grid-area:size !important; justify-self:end; }
{!! $pageScope !!} :is(.directory-page .worklist-filter,.directory-page .worklist-sort-control,.payment-monitoring-page .pm-more-filters,.payment-monitoring-page .pm-sort-control) {
    width:100% !important; min-width:0 !important; max-width:none !important; height:44px !important; box-sizing:border-box;
}
{!! $pageScope !!} .payment-monitoring-page .pm-sort-control > select { height:100% !important; box-sizing:border-box; }
{!! $pageScope !!} .directory-page .worklist-sort-control { padding-right:36px !important; }
{!! $pageScope !!} :is(.worklist-applied,.pm-applied-filters) { grid-column:1 / -1 !important; grid-row:auto !important; }
{!! $pageScope !!} .record-toolbar-action { display:flex; align-items:center; justify-content:flex-end; justify-self:end; min-width:0; grid-area:action !important; }
{!! $pageScope !!} .record-toolbar-action > .reference-page-action {
    display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:44px;
    padding:0 14px !important; border-radius:10px !important; white-space:nowrap;
    background-color:var(--reference-action-bg) !important; color:var(--reference-action-text) !important;
    border-color:transparent !important; box-shadow:none !important;
}
{!! $pageScope !!} .record-toolbar-action > .reference-page-action:is(:hover,:active,:focus-visible) {
    background-color:var(--reference-action-hover) !important; color:var(--reference-action-text) !important;
    border-color:transparent !important; box-shadow:none !important;
}
{!! $pageScope !!} .record-toolbar-action > .reference-page-action:focus-visible {
    outline:2px solid var(--filter-ui-focus) !important; outline-offset:2px;
}
@media(max-width:1280px) {
    {!! $pageScope !!} .directory-page form.worklist-toolbar:has(.record-toolbar-action) {
        grid-template-columns:160px 160px minmax(0,1fr) auto !important;
        grid-template-areas:"search search search search" "filter sort view action" !important;
    }
    {!! $pageScope !!} .payment-monitoring-page .pm-toolbar:has(.record-toolbar-action) {
        grid-template-columns:160px 160px minmax(210px,1fr) auto !important;
        grid-template-areas:"search search search search" "filter sort size action" !important;
    }
}
@media(max-width:900px) {
    {!! $pageScope !!} .directory-page form.worklist-toolbar:has(.record-toolbar-action) {
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        grid-template-areas:"search search" "filter sort" "view action" !important;
    }
    {!! $pageScope !!} .payment-monitoring-page .pm-toolbar:has(.record-toolbar-action) {
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        grid-template-areas:"search search" "filter sort" "size action" !important;
    }
    {!! $pageScope !!} .directory-page .directory-view-toggle,
    {!! $pageScope !!} .payment-monitoring-page .pm-page-size { justify-self:start; }
}
@media(max-width:600px) {
    {!! $pageScope !!} .directory-page form.worklist-toolbar:has(.record-toolbar-action) { grid-template-areas:"search search" "filter sort" "view view" "action action" !important; }
    {!! $pageScope !!} .payment-monitoring-page .pm-toolbar:has(.record-toolbar-action) { grid-template-areas:"search search" "filter sort" "size size" "action action" !important; }
    {!! $pageScope !!} .payment-monitoring-page .pm-page-size { justify-self:stretch; }
}
{!! $pageScope !!} .service-hub-card > .service-section-intro { margin-bottom:16px !important; }
/* Correction Requests has the same four controls without the record action column. */
{!! $pageScope !!} .payment-monitoring-page .pm-toolbar[data-correction-toolbar] {
    grid-template-columns:minmax(0,1fr) 160px 160px auto !important;
    grid-template-areas:"search filter sort size" !important;
    gap:12px !important;
}
@media(max-width:1100px) {
    {!! $pageScope !!} .payment-monitoring-page .pm-toolbar[data-correction-toolbar] {
        grid-template-columns:160px 160px minmax(0,1fr) !important;
        grid-template-areas:"search search search" "filter sort size" !important;
    }
}
@media(max-width:700px) {
    {!! $pageScope !!} .payment-monitoring-page .pm-toolbar[data-correction-toolbar] {
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        grid-template-areas:"search search" "filter sort" "size size" !important;
    }
}
{!! $pageScope !!} .payment-monitoring-page .pw-request-list { background:var(--rs-surface) !important; }
{!! $pageScope !!} .payment-monitoring-page .pw-request-toolbar-shell { margin:0 !important; border-bottom:1px solid var(--rs-divider) !important; }
{!! $pageScope !!} .payment-monitoring-page .pw-request-table :is(table,thead,tbody,tr,th,td) { background-color:transparent !important; }
{!! $pageScope !!} .payment-monitoring-page .pw-request-table tbody tr[data-workflow-row]:hover { background-color:var(--queue-hover) !important; }
{!! $pageScope !!} .payment-monitoring-page .pw-request-table tbody tr.pw-active-row { background-color:var(--queue-active) !important; }
{!! $pageScope !!} :is(.table-system-pagination,.service-pagination),
{!! $pageScope !!} .payment-monitoring-page > .pm-foot { margin-top:12px !important; }
/* The existing header and KPI grid paint one summary block without moving data. */
{!! $pageScope !!}:has(:is(.branch-kpi-strip,.user-directory-stats,.pm-kpis)) > .panel-page-header.panel-title-surface {
    margin-bottom:0 !important; border-bottom-left-radius:0 !important; border-bottom-right-radius:0 !important;
}
{!! $pageScope !!} :is(.branch-kpi-strip,.user-directory-stats,.pm-kpis) {
    background-color:var(--panel-title-background,var(--rs-header)) !important; border:0 !important; box-shadow:none !important;
    border-radius:0 0 20px 20px !important; padding:16px !important;
    margin-top:0 !important; margin-bottom:16px !important; gap:16px !important;
}
/* Case status navigation completes the header; records begin after a section gap. */
{!! $pageScope !!}:has(:is(.records-page,.master-records-page) .case-records-tabs-row) > .panel-page-header.panel-title-surface {
    margin-bottom:0 !important; border-bottom-left-radius:0 !important; border-bottom-right-radius:0 !important;
}
{!! $joinedHeaderScope !!} {
    position:relative; border-bottom-width:0 !important; border-inline-width:0 !important;
}
{!! $joinedHeaderScope !!}::after {
    content:"" !important; position:absolute !important; top:auto !important; bottom:0 !important;
    left:16px !important; right:16px !important; width:auto !important; height:1px !important;
    background-color:var(--rs-divider) !important; background-image:none !important;
    border:0 !important; border-radius:0 !important; box-shadow:none !important;
    opacity:1 !important; transform:none !important; pointer-events:none !important;
}
{!! $pageScope !!} :is(.records-page,.master-records-page) .case-records-tabs-row {
    background-color:var(--panel-title-background,var(--rs-header)) !important; border:0 !important; box-shadow:none !important;
    border-radius:0 0 20px 20px !important; padding:16px !important;
    margin:0 !important; width:100%; box-sizing:border-box;
}
{!! $pageScope !!}:has(.staff-case-records-page) > .panel-page-header.panel-title-surface::after {
    content:none !important;
}
{!! $pageScope !!} .staff-case-records-page .case-records-tabs-row {
    position:relative;
}
{!! $pageScope !!} .staff-case-records-page .case-records-tabs-row::before {
    content:"";
    position:absolute;
    inset:0 16px auto;
    border-top:1px solid var(--rs-divider);
    pointer-events:none;
}
{!! $pageScope !!} :is(.records-page,.master-records-page) .case-records-tabs {
    background-color:transparent !important; border:0 !important; box-shadow:none !important; margin:0 !important;
}
{!! $pageScope !!} .records-page > .case-records-top-wrapper,
{!! $pageScope !!} .master-records-page .admin-table-toolbar {
    margin-top:16px !important; border-top-left-radius:12px !important; border-top-right-radius:12px !important;
}
{!! $pageScope !!} .master-records-page > .table-system-card { gap:0 !important; }
/* KPI dimensions match while allowing longer content to grow without clipping. */
{!! $pageScope !!} :is(.branch-kpi-card,.user-directory-stat,.pm-kpi) {
    box-sizing:border-box; min-height:140px !important; padding:20px !important;
}
{!! $pageScope !!} :is(.branch-kpi-card__inner,.pm-kpi-inner) { padding:0 !important; }
{!! $pageScope !!} :is(.branch-kpi-card__icon,.user-directory-stat > i,.pm-kpi-icon) {
    box-sizing:border-box; width:40px !important; height:40px !important; min-width:40px; flex-shrink:0;
}
{!! $pageScope !!} .payment-monitoring-page :is(.pm-case-item,.pm-trans-item):not(:last-child) {
    border-bottom:1px solid var(--rs-divider) !important;
}
@media(max-width:767px) {
    {!! $pageScope !!} > :is(.payment-monitoring-page,.records-page,.master-records-page) { padding-inline:16px !important; }
}
/* One painted Sort surface and one full-size hit target, including its icons. */
{!! $controlScope !!} .case-compact-sort-filter { position:relative; border:0 !important; border-color:transparent !important; }
{!! $controlScope !!} .case-compact-sort-filter > select {
    position:absolute !important; inset:0 !important; width:100% !important; height:100% !important;
    min-width:0 !important; padding:0 36px 0 40px !important;
    background-color:transparent !important; border:0 !important; border-radius:inherit !important;
}
{!! $controlScope !!} .case-compact-sort-filter > i { pointer-events:none; }
/* Payment reference: one rotation owner, with positioning kept separate. */
{!! $chevronScope !!} {
    display:inline-grid !important; place-items:center; width:16px; height:16px; line-height:1;
    flex:0 0 16px; transform:none !important; transform-origin:center !important;
    rotate:0deg !important; transition:rotate 160ms ease !important; pointer-events:none;
}
{!! $openChevronScope !!} { rotate:180deg !important; }
{!! $controlScope !!} :is(.case-compact-more > [data-case-more-icon],.case-compact-sort-filter > .case-compact-sort-chev,.worklist-filter > summary > .bi-chevron-down,.pm-more-filters > summary > i:last-child,.catalog-control > summary > .catalog-control-chevron) { translate:none !important; }
{!! $controlScope !!} :is(.worklist-sort-control > .table-toolbar-select-icon,.pm-sort-control > .pm-sel-chev) { top:50% !important; translate:0 -50% !important; }
/* Date field icons stay above the transparent select without taking clicks. */
{!! $controlScope !!} .case-compact-date-filter > .case-compact-date-select { background-color:transparent !important; z-index:1 !important; padding-left:40px !important; }
{!! $controlScope !!} .case-compact-date-filter > .case-compact-date-icon {
    position:absolute !important; left:14px !important; top:50% !important; z-index:2 !important;
    display:inline-grid !important; place-items:center; width:16px; height:16px; line-height:1;
    color:var(--rs-text) !important; opacity:1 !important; visibility:visible !important;
    transform:none !important; translate:0 -50% !important; pointer-events:none;
}
{!! $controlScope !!} .case-compact-date-filter > .case-compact-date-chev {
    position:absolute !important; right:12px !important; top:50% !important; z-index:2 !important;
    translate:0 -50% !important;
}
@media(prefers-reduced-motion:reduce) { {!! $chevronScope !!} { transition:none !important; } }
{!! $controlScope !!} :is([data-filter-select-wrap],.case-page-size-control,.case-compact-more,.pm-more-filters > summary,.worklist-filter > summary,.catalog-control > summary):is(:hover,.is-open),
{!! $controlScope !!} :is(.pm-more-filters,.worklist-filter,.catalog-control)[open] > summary {
    background-color:var(--rs-hover) !important; border-color:transparent !important; box-shadow:none !important;
}
{!! $controlScope !!} :is([data-filter-select-wrap],.case-page-size-control):is(:hover,.is-open) > :is(select,input[name=per_page]) { background-color:transparent !important; }
{!! $controlScope !!} :is(select,input,button,summary,[data-filter-select-wrap],.case-page-size-control):hover:not(:focus-visible) {
    outline:none !important; border-color:transparent !important; box-shadow:none !important;
}
{!! $controlScope !!} :is(.case-compact-advanced-grid,.pm-filter-fields,.worklist-filter-fields,.case-filter-group) {
    background-color:transparent !important; border-color:transparent !important; box-shadow:none !important;
}
/* Service catalog spacing, adapted to existing manual-Apply filter panels. */
{!! $controlScope !!} :is(.case-compact-advanced-head,.case-compact-advanced-actions,.worklist-filter-panel > header,.worklist-filter-panel > footer,.pm-filter-head,.pm-filter-apply) {
    padding:var(--rs-filter-padding) !important;
}
{!! $controlScope !!} :is(.case-compact-advanced-grid,.worklist-filter-fields,.pm-filter-fields) {
    padding:var(--rs-filter-padding) !important; gap:var(--rs-filter-field-gap) !important; align-content:start !important;
}
/* One explicit gap from the main panel heading row to its first filter. */
{!! $controlScope !!} :is(.case-compact-advanced-head,.worklist-filter-panel > header,.pm-filter-head) { padding-bottom:0 !important; }
{!! $controlScope !!} :is(.case-compact-advanced-grid,.worklist-filter-fields,.pm-filter-fields) { padding-top:var(--rs-filter-heading-gap) !important; }
{!! $controlScope !!} :is(.case-filter-group,.case-interment-fields) { gap:var(--rs-filter-field-gap) !important; margin:0 !important; }
{!! $controlScope !!} :is(.case-compact-advanced-grid .case-compact-field,.worklist-filter-fields > label) {
    display:grid !important; gap:var(--rs-filter-label-gap) !important; margin:0 !important;
}
{!! $controlScope !!} .case-compact-advanced-grid .case-compact-field > label { margin:0 !important; }
/* Payment labels and controls are siblings, so each gap has one owner. */
{!! $controlScope !!} .pm-filter-fields { display:grid !important; grid-template-columns:minmax(0,1fr); gap:0 !important; }
{!! $controlScope !!} .pm-filter-fields > .pm-filter-label {
    margin:var(--rs-filter-field-gap) 0 var(--rs-filter-label-gap) !important;
}
{!! $controlScope !!} .pm-filter-fields > .pm-filter-label:first-child { margin-top:0 !important; }
{!! $controlScope !!} .pm-filter-fields > .pm-field { margin:0 !important; }
{!! $controlScope !!} :is(.catalog-control-panel,.filter-select-menu) { padding:var(--rs-filter-padding) !important; }
{!! $controlScope !!} .catalog-control-panel > h3 { margin:0 0 var(--rs-filter-field-gap) !important; }
{!! $controlScope !!} .catalog-control-choices { gap:var(--rs-filter-option-gap) !important; }
{!! $controlScope !!} :is(.catalog-control-option,.filter-select-menu__item) { padding:.4rem .5rem !important; gap:var(--rs-filter-label-gap) !important; }
{!! $controlScope !!} .filter-select-menu__item + .filter-select-menu__item { margin-top:var(--rs-filter-option-gap) !important; }
{!! $controlScope !!} .filter-select-menu-heading {
    margin:0 0 var(--rs-filter-heading-gap) !important; padding:0 !important; border:0 !important;
    color:var(--rs-text) !important; font-size:.95rem !important; font-weight:650 !important; line-height:1.3 !important;
}
{!! $controlScope !!} :is(.case-compact-sort-filter,.pm-sort-control,.worklist-sort-control) > .filter-select-menu,
{!! $controlScope !!} .catalog-control--sort > .catalog-control-panel {
    box-sizing:border-box; width:min(280px,calc(100vw - 32px)) !important; min-width:0 !important;
    max-width:calc(100vw - 32px) !important; max-height:min(320px,65vh);
    top:calc(100% + 8px) !important; right:0 !important; left:auto !important;
}
{!! $controlScope !!} .catalog-control--sort > .catalog-control-panel > h3 { margin-bottom:var(--rs-filter-heading-gap) !important; }
{!! $controlScope !!} .worklist-sort-control > select {
    width:100% !important; height:100% !important; min-height:0 !important; z-index:1;
}
{!! $controlScope !!} :is(.case-compact-sort-filter,.pm-sort-control,.worklist-sort-control) .filter-select-menu__item > i {
    flex:0 0 16px; width:16px; text-align:center;
}
{!! $controlScope !!} :is(.case-compact-sort-filter,.pm-sort-control,.worklist-sort-control) .filter-select-menu__item[aria-selected="true"] {
    background-color:var(--record-choice-surface) !important; color:var(--rs-text) !important; font-weight:650 !important;
}
{!! $controlScope !!} :is(.case-compact-sort-filter,.pm-sort-control,.worklist-sort-control) .filter-select-menu__check {
    color:var(--record-choice-check) !important;
}
{!! $controlScope !!} :is(.case-compact-drawer,.pm-more-panel,.worklist-filter-panel) [data-filter-select-wrap]:hover {
    background-color:var(--rs-item-hover) !important;
}
/* Reuse individual-filter hover on the record rows; no outer stroke on hover. */
{!! $rowScope !!} :is(.table-system-table,.pm-history-table) tbody tr { outline:none !important; }
{!! $rowScope !!} :is(.table-system-table,.pm-history-table) tbody tr,
{!! $rowScope !!} :is(.table-system-table,.pm-history-table) tbody tr > td,
{!! $rowScope !!} :is(.pm-case-row,.pm-trans-row) {
    transition:background-color 240ms ease !important;
}
{!! $rowScope !!} :is(.table-system-table,.pm-history-table) tbody tr:hover,
{!! $rowScope !!} :is(.pm-case-row,.pm-trans-row):hover {
    background-color:var(--rs-item-hover) !important; outline:none !important; box-shadow:none !important;
}
{!! $rowScope !!} :is(.table-system-table,.pm-history-table) tbody tr:hover > td {
    background-color:var(--rs-item-hover) !important; box-shadow:none !important;
}
/* Reuse Payment Monitoring's subtle preview tint and inset leading accent. */
{!! $pageScope !!} .records-worklist-table.table-system-table tbody tr.is-viewing-record,
{!! $pageScope !!} .records-worklist-table.table-system-table tbody tr.is-viewing-record:hover {
    background-color:color-mix(in srgb,var(--brand) 8%,transparent) !important; box-shadow:none !important;
}
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $pageScope) !!} .records-worklist-table.table-system-table tbody tr.is-viewing-record,
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $pageScope) !!} .records-worklist-table.table-system-table tbody tr.is-viewing-record:hover {
    background-color:color-mix(in srgb,var(--brand) 13%,transparent) !important;
}
{!! $pageScope !!} .records-worklist-table.table-system-table tbody tr.is-viewing-record > td,
{!! $pageScope !!} .records-worklist-table.table-system-table tbody tr.is-viewing-record:hover > td {
    background-color:transparent !important; box-shadow:none !important;
}
{!! $pageScope !!} .records-worklist-table tbody tr.is-viewing-record > td:first-child { position:relative; }
{!! $pageScope !!} .records-worklist-table tbody tr.is-viewing-record > td:first-child::before {
    content:""; position:absolute; inset-block:12px; inset-inline-start:0; width:3px;
    border-radius:0 3px 3px 0; background:var(--brand); pointer-events:none;
}
{!! $caseStatusScope !!} .case-status-text {
    background:transparent !important; border:0 !important; border-radius:0 !important;
    padding:0 !important; min-height:0 !important; min-width:0 !important; box-shadow:none !important;
}
{!! $caseStatusScope !!} .case-status-text.status-badge-success { color:var(--rs-success) !important; }
{!! $caseStatusScope !!} .case-status-text.status-badge-warning { color:var(--rs-warning) !important; }
{!! $caseStatusScope !!} .case-status-text.status-badge-info { color:#175CD3 !important; }
{!! $caseStatusScope !!} .case-status-text.status-badge-danger { color:#B42318 !important; }
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $caseStatusScope) !!} .case-status-text.status-badge-info { color:#84B8FF !important; }
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $caseStatusScope) !!} .case-status-text.status-badge-danger { color:#FDA29B !important; }
{!! $caseStatusScope !!} .case-status-text[data-case-status=ACTIVE] { color:var(--rs-success) !important; }
{!! $caseStatusScope !!} .case-status-text[data-case-status=COMPLETED] { color:var(--record-status-completed) !important; }
{!! $rowScope !!} tr[data-clickable-row]:focus-visible {
    outline:2px solid var(--filter-ui-focus) !important; outline-offset:0;
}
{!! $pageScope !!} .branch-management-page .branch-directory-card-view > .grid { align-items:start !important; }
{!! $pageScope !!} .branch-management-page .directory-item-card { min-height:0 !important; height:auto; }
{!! $pageScope !!} .branch-management-page .branch-directory-card-view :is(.branch-code-pill,.branch-main-pill) {
    padding:0 !important; border:0 !important; border-radius:0 !important;
    background:transparent !important; box-shadow:none !important; line-height:1.5;
}
{!! $pageScope !!} .branch-management-page .branch-reference-actions { padding-block:10px; }
@media(prefers-reduced-motion:reduce) {
    {!! $rowScope !!} :is(.table-system-table,.pm-history-table) tbody tr,
    {!! $rowScope !!} :is(.table-system-table,.pm-history-table) tbody tr > td,
    {!! $rowScope !!} :is(.pm-case-row,.pm-trans-row) { transition:none !important; }
}
</style>
@include('partials.record_modal_surface_styles')
