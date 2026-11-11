<style>
/* Capture the reference palette independently of page-local role tokens. */
body.panel-shell-body { --record-choice-surface:#D0E2D6; --record-choice-check:#1B4332; }
html[data-theme='dark'] body.panel-shell-body { --record-choice-surface:#14261D; --record-choice-check:#B5D6C4; }
/* Service Management's rendered contract: stable trigger surfaces, with open
   indicated by the existing chevron. Wrapper/select owners remain unchanged. */
html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar,.service-management-hub.service-reference .catalog-filter-sort-actions) :is(.case-compact-more,.case-compact-sort-filter,.pm-more-filters > summary,.pm-sort-control > select,.catalog-control > summary) {
    background:var(--filter-ui-menu) !important;
    border:1px solid var(--filter-ui-border) !important;
    color:var(--filter-ui-text) !important;
    box-shadow:none !important; transition:none !important;
    transform:none !important;
    translate:none !important;
}
html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar,.service-management-hub.service-reference .catalog-filter-sort-actions) :is(.case-compact-more:hover,.case-compact-sort-filter:hover,.pm-more-filters > summary:hover,.pm-sort-control > select:hover,.catalog-control > summary:hover) {
    background:var(--record-control-hover) !important;
    border-color:var(--record-search-frame-hover) !important;
}
html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar,.service-management-hub.service-reference .catalog-filter-sort-actions) :is(.case-compact-more:focus-visible,.case-compact-sort-filter:has(> select:focus-visible),.pm-more-filters > summary:focus-visible,.pm-sort-control > select:focus-visible,.catalog-control > summary:focus-visible) {
    outline:2px solid var(--filter-ui-focus) !important;
    outline-offset:2px;
}
/* Case/Payment fallback. The record-surface owner below the page content
   applies this same rotate-only contract to all six record worklists. */
html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar) :is(.case-compact-sort-chev,.pm-sort-control > .shared-record-chevron) {
    transform:none !important;
    rotate:0deg !important;
    transform-origin:center;
    transition:rotate .16s ease !important;
}
html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar) :is(.case-compact-sort-filter.is-open > .case-compact-sort-chev,.pm-sort-control.is-open > .shared-record-chevron) {
    transform:none !important;
    rotate:180deg !important;
}
/* The record-surface owner uses Payment's independent rotate property for
   every main Filter/Sort, including Service. Generic catalog pages retain
   their own catalog-filter-sort.css behavior. */
@media(prefers-reduced-motion:reduce) {
    html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page .pm-toolbar) :is(.case-compact-sort-chev,.pm-sort-control > .shared-record-chevron) { transition:none !important; }
}
/* Match catalog options without replacing selection events or query behavior. */
html[data-theme] body.panel-shell-body .page-content .service-management-hub.service-reference .catalog-control-choices {
    gap:4px !important;
}
html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page,.service-management-hub.service-reference) .filter-select-menu__item + .filter-select-menu__item {
    margin-top:4px !important;
}
html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page,.service-management-hub.service-reference) :is(.filter-select-menu,.catalog-control-panel) :is(.filter-select-menu__item,.catalog-control-option) {
    border:1px solid transparent !important;
    background:transparent !important;
    color:var(--filter-ui-text) !important;
    font-weight:400 !important;
    box-shadow:none !important; transition:none !important;
    transform:none !important;
}
html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page,.service-management-hub.service-reference) :is(.filter-select-menu,.catalog-control-panel) :is(.filter-select-menu__item,.catalog-control-option):is(:hover,:focus-visible) {
    background:var(--record-choice-surface) !important;
    border-color:var(--filter-ui-border) !important;
}
html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page,.service-management-hub.service-reference) :is(.filter-select-menu,.catalog-control-panel) :is(.filter-select-menu__item,.catalog-control-option):is([aria-selected='true'],[aria-pressed='true']) {
    background:var(--record-choice-surface) !important;
    font-weight:650 !important;
}
html[data-theme] body.panel-shell-body .page-content :is(.shared-record-filter-toolbar.case-record-filters,.pm-consistent-ui.payment-monitoring-page.pm-page,.service-management-hub.service-reference) :is(.filter-select-menu,.catalog-control-panel) :is(.filter-select-menu__check,.catalog-control-option-check) {
    color:var(--record-choice-check) !important;
}
</style>


