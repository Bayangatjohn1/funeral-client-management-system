@php
    $dashboardShell = 'html[data-theme] body.panel-shell-body .page-content';
    $dashboard = $dashboardShell.' :is(.staff-dashboard-v2,.admin-dashboard-shell,.sa,.oe[data-owner-executive])';
    $staffDashboardEntry = "html[data-theme]:not([data-sidebar-nav-entry='true']) body.panel-shell-body .page-content > .staff-dashboard-v2";
    $dashboardDetails = $dashboard.' :is(.branch-case-drawer,.sa-dialog,.oe-summary-dialog)';
    $dashboardControls = ':is(.dashboard-header-theme,.topbar-notification,.staff-tab-btn,.branch-panel__link,.branch-panel__overflow,.sa-select,.sa-link,.sa-eye,.oe-button,.oe-icon-button,.oe-shared-select,.oe-segment)';
    $dashboardRows = ':is(.ref-activity-row,.ref-schedule-card,.ref-attention-card,.ref-balance-card,.branch-record-card,.branch-balance-item,.sa-branch,.sa-activity,.oe-branch-row)';
@endphp
<style>
@media (prefers-reduced-motion:no-preference) {
    {!! $dashboardShell !!}:has(> .staff-dashboard-v2) { animation:none; }
    {!! $staffDashboardEntry !!} > :is(.staff-header-card,.staff-summary-grid,.staff-grid,.staff-subgrid-two) {
        animation:staff-dashboard-enter 200ms ease-out backwards;
    }
    {!! $staffDashboardEntry !!} > .staff-summary-grid { animation-delay:20ms; }
    {!! $staffDashboardEntry !!} > .staff-grid { animation-delay:40ms; }
    {!! $staffDashboardEntry !!} > .staff-subgrid-two { animation-delay:60ms; }
}
@keyframes staff-dashboard-enter {
    from { opacity:0; transform:translateY(10px); }
    to { opacity:1; transform:none; }
}
{!! $dashboard !!} {
    --dash-kpi:#EDF3EF;
    --dash-kpi-hover:#E2ECE5;
    --dash-panel:#F7FAF8;
    --dash-control:#EAF2ED;
    --dash-hover:#DCEBE1;
    --dash-divider:#B8CEC0;
    --dash-focus:#1B4332;
    --dash-positive:#216342;
    --dash-warning:#854D16;
    --dash-danger:#A02C36;
    --dash-info:#315A77;
    --dash-plan:#594B79;
    --dash-neutral:#3C5045;
    --dashboard-section-gap:16px;
    --staff-layout-gap:var(--dashboard-section-gap);
    --dashboard-gap:var(--dashboard-section-gap);
    gap:var(--dashboard-section-gap);
}
{!! str_replace('html[data-theme]', "html[data-theme='dark']", $dashboard) !!} {
    --dash-kpi:#182B21;
    --dash-kpi-hover:#21382B;
    --dash-panel:#15251C;
    --dash-control:#22392C;
    --dash-hover:#2D4838;
    --dash-divider:#355241;
    --dash-focus:#A9C9B5;
    --dash-positive:#8AD6A6;
    --dash-warning:#EBC381;
    --dash-danger:#F5A6AB;
    --dash-info:#A2C9E2;
    --dash-plan:#C5B8E1;
    --dash-neutral:#C2D3C9;
}

/* Shared dashboard notification control and panel. */
{!! $dashboard !!} .topbar-notification {
    width:44px;
    height:44px;
    border:1px solid var(--dash-divider);
    border-radius:12px;
    background:var(--dash-control) !important;
    color:var(--dash-positive);
    box-shadow:none !important;
}
{!! $dashboard !!} .topbar-notification:is(:hover,[aria-expanded='true'],.is-active) {
    border-color:var(--dash-focus);
    background:var(--dash-hover) !important;
    color:var(--dash-positive);
}
{!! $dashboard !!} .topbar-notification.tone-danger { --notification-bell-tone:var(--dash-danger); color:var(--dash-danger) !important; }
{!! $dashboard !!} .topbar-notification.tone-warning { --notification-bell-tone:var(--dash-warning); color:var(--dash-warning) !important; }
{!! $dashboard !!} .topbar-notification.tone-info { --notification-bell-tone:var(--dash-info); color:var(--dash-info) !important; }
{!! $dashboard !!} .topbar-notification.tone-plan { --notification-bell-tone:var(--dash-plan); color:var(--dash-plan) !important; }
{!! $dashboard !!} .topbar-notification.tone-success { --notification-bell-tone:var(--dash-positive); color:var(--dash-positive) !important; }
{!! $dashboard !!} .topbar-notification.tone-neutral { --notification-bell-tone:var(--dash-neutral); color:var(--dash-neutral) !important; }
{!! $dashboard !!} .topbar-notification__count { background:var(--notification-bell-tone,var(--dash-danger)) !important; }
{!! $dashboard !!} .topbar-notification i { font-size:18px; }
{!! $dashboard !!} .topbar-notification__count {
    top:-5px;
    right:-5px;
    min-width:18px;
    height:18px;
    line-height:16px;
    border-color:var(--panel-title-background,var(--dash-panel));
    background:var(--dash-danger);
}
{!! $dashboard !!} .topbar-notification-menu,
{!! $dashboard !!} .topbar-notification-menu.is-compact {
    width:min(420px,calc(100vw - 24px));
    border:1px solid var(--dash-divider) !important;
    border-radius:14px;
    background:var(--dash-panel) !important;
    box-shadow:none !important;
    color:inherit;
}
{!! $dashboard !!} .topbar-notification-menu__head {
    padding:14px 16px;
    border-bottom:1px solid var(--dash-divider);
    background:var(--dash-control) !important;
}
{!! $dashboard !!} .topbar-notification-menu__identity { display:flex; align-items:center; gap:10px; min-width:0; }
{!! $dashboard !!} .topbar-notification-menu__icon {
    display:grid;
    place-items:center;
    flex:0 0 36px;
    width:36px;
    height:36px;
    border:1px solid var(--dash-divider);
    border-radius:10px;
    background:var(--dash-panel);
    color:var(--dash-positive);
}
{!! $dashboard !!} .topbar-notification-menu__head strong { color:inherit; font-size:14px; font-weight:650; }
{!! $dashboard !!} .topbar-notification-menu__head small { color:var(--dash-neutral); font-size:12px; }
{!! $dashboard !!} .topbar-notification-menu__list,
{!! $dashboard !!} .topbar-notification-menu.is-compact .topbar-notification-menu__list {
    max-height:min(420px,58vh);
    background:var(--dash-panel) !important;
}
{!! $dashboard !!} .topbar-workflow-notices {
    padding:12px 14px;
    border-bottom:1px solid var(--dash-divider);
    background:var(--dash-panel);
}
{!! $dashboard !!} .topbar-workflow-notices__head {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:8px;
}
{!! $dashboard !!} .topbar-workflow-notices__head strong { font-size:12px; font-weight:650; }
{!! $dashboard !!} .topbar-workflow-notices__head small { color:var(--dash-neutral); font-size:11px; }
{!! $dashboard !!} .pw-notice-list { display:grid; gap:4px; color:var(--dash-neutral); font-size:12px; }
{!! $dashboard !!} .pw-notice-list a {
    display:grid;
    gap:3px;
    padding:8px 9px;
    border-radius:8px;
    color:inherit;
    text-decoration:none;
}
{!! $dashboard !!} .topbar-notification-entry,
{!! $dashboard !!} .pw-notice-entry { position:relative; }
{!! $dashboard !!} :is(.topbar-notification-entry,.pw-notice-entry).tone-danger { --notification-tone:var(--dash-danger); --notification-tone-soft:color-mix(in srgb,var(--dash-danger) 13%,transparent); }
{!! $dashboard !!} :is(.topbar-notification-entry,.pw-notice-entry).tone-warning { --notification-tone:var(--dash-warning); --notification-tone-soft:color-mix(in srgb,var(--dash-warning) 13%,transparent); }
{!! $dashboard !!} :is(.topbar-notification-entry,.pw-notice-entry).tone-info { --notification-tone:var(--dash-info); --notification-tone-soft:color-mix(in srgb,var(--dash-info) 13%,transparent); }
{!! $dashboard !!} :is(.topbar-notification-entry,.pw-notice-entry).tone-plan { --notification-tone:var(--dash-plan); --notification-tone-soft:color-mix(in srgb,var(--dash-plan) 13%,transparent); }
{!! $dashboard !!} :is(.topbar-notification-entry,.pw-notice-entry).tone-success { --notification-tone:var(--dash-positive); --notification-tone-soft:color-mix(in srgb,var(--dash-positive) 13%,transparent); }
{!! $dashboard !!} :is(.topbar-notification-entry,.pw-notice-entry).tone-neutral { --notification-tone:var(--dash-neutral); --notification-tone-soft:color-mix(in srgb,var(--dash-neutral) 10%,transparent); }
{!! $dashboard !!} .topbar-notification-dismiss {
    border-color:color-mix(in srgb,var(--notification-tone) 28%,var(--dash-divider));
    background:var(--dash-panel);
    color:var(--notification-tone);
    box-shadow:none;
}
{!! $dashboard !!} .topbar-notification-dismiss:is(:hover,:focus-visible) {
    border-color:var(--notification-tone);
    background:var(--notification-tone-soft);
    color:var(--notification-tone);
}
{!! $dashboard !!} :is(.topbar-notification-entry,.pw-notice-entry):not(.is-read) > :is(a,.topbar-notification-card) {
    background:color-mix(in srgb,var(--notification-tone) 11%,var(--dash-panel)) !important;
}
{!! $dashboard !!} :is(.topbar-notification-entry,.pw-notice-entry):not(.is-read):is(:hover,:focus-within) > :is(a,.topbar-notification-card) {
    background:color-mix(in srgb,var(--notification-tone) 17%,var(--dash-panel)) !important;
}
{!! $dashboard !!} :is(.topbar-notification-entry,.pw-notice-entry).is-read > :is(a,.topbar-notification-card) {
    opacity:1;
    background:color-mix(in srgb,var(--dash-neutral) 9%,var(--dash-panel)) !important;
}
{!! $dashboard !!} :is(.topbar-notification-entry,.pw-notice-entry).is-read:is(:hover,:focus-within) > :is(a,.topbar-notification-card) {
    background:color-mix(in srgb,var(--dash-neutral) 14%,var(--dash-panel)) !important;
}
{!! $dashboard !!} .pw-notice-list a:hover { background:var(--dash-hover); color:inherit; }
{!! $dashboard !!} .pw-notice-list a[data-unread='true'] { color:inherit; font-weight:600; }
{!! $dashboard !!} .pw-notice-entry > a > span:first-child { color:inherit !important; }
{!! $dashboard !!} .pw-notice-list a small { color:var(--dash-neutral); font-weight:400; }
{!! $dashboard !!} .topbar-notification-card,
{!! $dashboard !!} .topbar-notification-menu.is-compact .topbar-notification-card {
    padding:12px 14px;
    border-bottom-color:var(--dash-divider) !important;
    background:var(--dash-panel) !important;
}
{!! $dashboard !!} .topbar-notification-card:hover,
{!! $dashboard !!} .topbar-notification-menu.is-compact .topbar-notification-card:hover { background:var(--dash-hover) !important; }
{!! $dashboard !!} .topbar-notification-card__title { color:inherit; font-size:13px; font-weight:650; }
{!! $dashboard !!} :is(.topbar-notification-card__text,.topbar-notification-card__meta) { color:var(--dash-neutral); }
{!! $dashboard !!} .topbar-notification-empty,
{!! $dashboard !!} .topbar-notification-menu.is-compact .topbar-notification-empty {
    margin:12px;
    border-color:var(--dash-divider);
    background:var(--dash-control) !important;
    color:var(--dash-neutral);
}
{!! $dashboard !!} .topbar-notification-menu__footer,
{!! $dashboard !!} .topbar-notification-menu.is-compact .topbar-notification-menu__footer {
    justify-content:flex-end;
    padding:12px 14px;
    border-top-color:var(--dash-divider) !important;
    background:var(--dash-control) !important;
}
{!! $dashboard !!} .topbar-notification-footer-btn.is-primary {
    min-height:38px;
    border-color:var(--dash-focus);
    background:var(--dash-focus);
    color:var(--dash-panel);
    font-weight:600;
}
{!! $dashboard !!} .topbar-notification-menu :is(a,button):focus-visible { outline:2px solid var(--dash-focus); outline-offset:-2px; }
@media(max-width:640px) {
    {!! $dashboard !!} .topbar-notification-menu,
    {!! $dashboard !!} .topbar-notification-menu.is-compact { width:auto; }
}

/* Keep the existing dashboard layouts and share the Audit title surface. */
{!! $dashboard !!} :is(.staff-header-card,.admin-dashboard-greeting,.sa-header,.oe-executive-banner) {
    background:var(--panel-title-background,var(--dash-panel)) !important;
    border-color:transparent !important;
    box-shadow:none !important;
}
/* Let layout gaps, rather than a mix of gaps and child margins, separate sections. */
{!! $dashboardShell !!} :is(.staff-dashboard-v2,.admin-dashboard-shell.branch-admin-view) {
    row-gap:12px;
    padding-inline:clamp(.75rem,1.2vw,1.15rem);
}
@media (min-width:1024px) {
    {!! $dashboardShell !!} :is(.staff-dashboard-v2,.admin-dashboard-shell.branch-admin-view) {
        padding-inline:clamp(.65rem,1vw,1rem);
    }
}
{!! $dashboard !!} > :is(section,.staff-subgrid-two,.branch-board,.sa-columns,.oe-kpis) { margin-block:0 !important; }
{!! $dashboard !!} :is(.staff-summary-grid,.admin-kpi-grid,.sa-kpis,.oe-kpis,.staff-grid,.staff-subgrid-two,.branch-board,.branch-board__column,.sa-columns) { gap:var(--dashboard-section-gap); }
{!! $dashboard !!} > .staff-header-card > .staff-header-main {
    grid-template-columns:minmax(0,1fr) minmax(280px,390px) auto;
    grid-template-areas:"title search controls";
    align-items:center;
    gap:12px 16px;
}
{!! $dashboard !!} .staff-header-main > .staff-header-title-group { grid-area:title; }
{!! $dashboard !!} .staff-header-main > .staff-payment-search-region { grid-area:search; min-width:0; }
{!! $dashboard !!} .staff-header-main > .dashboard-header-controls {
    grid-area:controls;
    align-self:end;
    justify-content:flex-end;
    flex-wrap:nowrap;
}
@media (max-width:1100px) {
    {!! $dashboard !!} > .staff-header-card > .staff-header-main {
        grid-template-columns:minmax(0,1fr) auto;
        grid-template-areas:"title controls" "search search";
    }
    {!! $dashboard !!} .staff-header-main > .dashboard-header-controls { align-self:center; }
}
{!! $dashboard !!} .staff-payment-search {
    position:relative;
    z-index:2;
    width:min(100%,560px);
    margin-inline-start:auto;
    font-size:.875rem;
}
{!! $dashboard !!} .staff-payment-search label {
    display:block;
    margin-bottom:6px;
    font-weight:500;
}
{!! $dashboard !!} .staff-payment-search-field {
    display:flex;
    background:var(--dash-control);
    border-radius:14px;
}
{!! $dashboard !!} .staff-payment-search-input {
    position:relative;
    display:flex;
    flex:1;
    min-width:0;
    align-items:center;
}
{!! $dashboard !!} .staff-payment-search-input > i { position:absolute; inset-inline-start:14px; }
{!! $dashboard !!} .staff-payment-search-input input {
    width:100%;
    min-height:44px;
    padding-inline:40px;
    background:transparent;
    border:0;
    border-radius:14px 0 0 14px;
    box-shadow:none;
    color:inherit;
    font-size:inherit;
}
{!! $dashboard !!} .staff-payment-search-input input::placeholder { color:var(--dash-neutral); }
{!! $dashboard !!} .staff-payment-search-field > button {
    flex:0 0 84px;
    border-radius:0 14px 14px 0;
    background:var(--reference-action-bg);
    color:var(--reference-action-text);
    font-weight:600;
}
{!! $dashboard !!} .staff-payment-search-field > button:hover { background:var(--reference-action-hover); }
{!! $dashboard !!} .staff-payment-search-popup {
    position:absolute;
    inset-inline:0;
    top:calc(100% + 6px);
    max-height:280px;
    overflow:auto;
    padding:6px;
    border-radius:10px;
    background:var(--dash-kpi-hover);
}
{!! $dashboard !!} .staff-payment-search-popup [role='option'] {
    display:block;
    padding:10px;
    border-radius:6px;
    color:inherit;
    overflow-wrap:anywhere;
}
{!! $dashboard !!} .staff-payment-search-popup [role='option']:is(:hover,[aria-selected='true']) { background:var(--dash-hover); }
{!! $dashboard !!} .staff-payment-search-popup [role='status'] { margin:0; padding:8px; }
{!! $dashboard !!} .staff-payment-search-popup [role='status']:empty { display:none; }
{!! $dashboard !!} :is([data-outstanding-card],[data-attention-card]) {
    display:flex;
    flex-direction:column;
    block-size:28rem;
    align-self:start;
}
{!! $dashboard !!} :is([data-outstanding-card],[data-attention-card]) > .staff-card-head {
    flex:none;
    padding:12px 16px;
}
{!! $dashboard !!} :is(.staff-balances-heading,.staff-urgent-heading) { gap:8px; margin-bottom:4px; }
{!! $dashboard !!} :is(.staff-balances-heading,.staff-urgent-heading) > h3 {
    display:inline-flex;
    align-items:center;
    gap:8px;
}
{!! $dashboard !!} .staff-balances-heading i { color:var(--dash-positive); }
{!! $dashboard !!} .staff-urgent-heading i { color:var(--dash-warning); }
{!! $dashboard !!} .staff-urgent-count {
    color:var(--dash-warning);
    font-size:.75rem;
    font-weight:600;
    font-variant-numeric:tabular-nums;
}
{!! $dashboard !!} [data-attention-card] > .staff-urgent-list {
    display:grid;
    align-content:start;
    gap:8px;
    min-height:0;
    max-height:none;
    flex:1;
    overflow-y:auto;
    overscroll-behavior-y:contain;
    padding:12px 16px;
    margin:0;
}
{!! $dashboard !!} :is(.staff-urgent-list,.staff-balances-scroll):focus-visible { outline:2px solid var(--dash-focus); outline-offset:-3px; }
{!! $dashboard !!} .staff-urgent-case {
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    align-items:center;
    gap:8px 12px;
    padding:8px 12px;
    border-radius:8px;
    background:var(--dash-control);
    font-size:.8125rem;
}
{!! $dashboard !!} .staff-urgent-case-name { display:flex; gap:2px 6px; align-items:baseline; flex-wrap:wrap; overflow-wrap:anywhere; }
{!! $dashboard !!} .staff-urgent-case-name i { color:var(--dash-danger); font-size:.5rem; }
{!! $dashboard !!} .staff-urgent-case-name .ref-mono,
{!! $dashboard !!} .staff-urgent-case .staff-attention-date { color:var(--dash-neutral); }
{!! $dashboard !!} .staff-urgent-case .staff-attention-date { margin-top:3px; }
{!! $dashboard !!} .staff-urgent-case-actions { display:grid; justify-items:end; gap:2px; }
{!! $dashboard !!} .staff-urgent-case-actions .ref-balance-amount { white-space:nowrap; }
{!! $dashboard !!} .staff-urgent-case-actions button { min-height:32px; font-size:.75rem; }
{!! $dashboard !!} [data-balances-results],
{!! $dashboard !!} .staff-balances-panel {
    display:flex;
    flex-direction:column;
    flex:1;
    min-height:0;
}
{!! $dashboard !!} .staff-balances-panel {
    margin:12px 16px;
    overflow:hidden;
    border-radius:12px;
    background:var(--dash-panel);
}
{!! $dashboard !!} .staff-balances-scroll { flex:1; min-height:0; overflow:auto; }
{!! $dashboard !!} .staff-balances-scroll > .panel-skeleton {
    --card:var(--dash-panel);
    --panel-skeleton-exit-duration:150ms;
}
{!! $dashboard !!} .staff-balances-table { width:100%; min-width:26rem; font-size:.8125rem; }
{!! $dashboard !!} .staff-balances-table :is(th,td) { padding:8px 12px; text-align:start; vertical-align:middle; }
{!! $dashboard !!} .staff-balances-table th { font-weight:600; }
/* The legacy panel table layer paints headers and hovered cells with important fills. */
{!! $dashboard !!} .staff-balances-table,
{!! $dashboard !!} .staff-balances-table :is(thead,thead tr,thead th,tbody th,tbody td) {
    background-color:transparent !important;
}
{!! $dashboard !!} .staff-balances-table thead th {
    position:sticky;
    inset-block-start:0;
    z-index:26;
    height:36px;
    background-color:var(--dash-panel) !important;
    border-bottom:1px solid var(--dash-divider) !important;
}
{!! $dashboard !!} .staff-balances-table tbody tr { height:52px; }
{!! $dashboard !!} .staff-balances-table tbody tr + tr { border-top:1px solid var(--dash-divider); }
{!! $dashboard !!} .staff-balances-table tbody tr:hover > :is(th,td) { background-color:var(--record-row-hover) !important; }
@media (prefers-reduced-motion:no-preference) {
    {!! $dashboard !!} .staff-balances-table tbody tr > :is(th,td) { transition:background-color 240ms ease; }
}
{!! $dashboard !!} .staff-balances-table :is(.staff-balance-value,.staff-balance-action) { text-align:end; white-space:nowrap; }
{!! $dashboard !!} .staff-balances-table .staff-balance-code { color:var(--dash-neutral); overflow-wrap:anywhere; }
{!! $dashboard !!} .staff-balance-status[data-case-status='ACTIVE'] { color:var(--record-status-active); }
{!! $dashboard !!} .staff-balance-status[data-case-status='COMPLETED'] { color:var(--record-status-completed); }
{!! $dashboard !!} .staff-balance-status[data-case-status='DRAFT'] { color:var(--record-status-draft); }
{!! $dashboard !!} .staff-balance-record { display:inline-flex; align-items:center; min-height:36px; }
{!! $dashboard !!} .staff-balances-pagination {
    display:grid;
    flex:none;
    grid-template-columns:minmax(0,1fr) auto;
    align-items:center;
    gap:6px;
    padding:5px 10px;
    border-top:1px solid var(--dash-divider);
    font-size:.75rem;
}
{!! $dashboard !!} .staff-balances-pagination > div { display:flex; gap:4px; }
{!! $dashboard !!} .staff-balances-pagination :is(a,button) {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:28px;
    min-width:36px;
    padding-inline:6px;
    border-radius:6px;
}
{!! $dashboard !!} .staff-balances-pagination a:hover { background:var(--dash-hover); }
{!! $dashboard !!} .staff-balances-pagination button:disabled { opacity:.55; }
{!! $dashboard !!} :is(.staff-card,.branch-panel,.admin-action-group,.sa-panel,.oe-card):not(.sa-summary,.oe-kpi,.oe-executive-banner) {
    background:var(--dash-panel) !important;
    border-color:transparent !important;
    box-shadow:none !important;
}
{!! $dashboard !!} :is(.staff-summary-card,.admin-kpi-card,.sa-summary,.oe-kpi) {
    min-width:0;
    padding:20px !important;
    background:var(--dash-kpi) !important;
    border-color:transparent !important;
    box-shadow:none !important;
    outline:none;
}
{!! $dashboard !!} :is(.admin-kpi-card:hover,.sa-summary:has(.sa-card-link:hover),.oe-kpi:hover) {
    background:var(--dash-kpi-hover) !important;
    outline:none;
    transform:none !important;
}
{!! $dashboard !!} :is(.staff-summary-card > strong,.admin-kpi-card__value,.sa-summary strong,.oe-kpi-value) {
    font-variant-numeric:tabular-nums;
    overflow-wrap:anywhere;
}
{!! $dashboard !!} :is([data-dashboard-kpi-tone='positive'],.admin-kpi-card.is-positive .admin-kpi-card__value,.oe-kpi[data-kpi='collections'] .oe-kpi-value) { color:var(--dash-positive) !important; }
{!! $dashboard !!} :is([data-dashboard-kpi-tone='warning'],.admin-kpi-card.is-alert .admin-kpi-card__value,.oe-kpi[data-kpi='outstanding']:not([data-zero-outstanding]) .oe-kpi-value) { color:var(--dash-warning) !important; }
{!! $dashboard !!} :is([data-dashboard-kpi-tone='info'],.admin-kpi-card.is-neutral .admin-kpi-card__value,.oe-kpi[data-kpi='cases'] .oe-kpi-value) { color:var(--dash-info) !important; }
{!! $dashboard !!} :is([data-dashboard-kpi-tone='plan'],.oe-kpi[data-kpi='service'] .oe-kpi-value) { color:var(--dash-plan) !important; }
{!! $dashboard !!} :is([data-dashboard-kpi-tone='neutral'],.oe-kpi[data-zero-outstanding] .oe-kpi-value) { color:var(--dash-neutral) !important; }
{!! $dashboard !!} :is(.staff-summary-label > i,.admin-kpi-card__icon,.sa-summary-icon,.oe-kpi-top > i) {
    width:40px;
    height:40px;
    flex:0 0 40px;
    border-color:transparent !important;
    box-shadow:none !important;
}

{!! $dashboard !!} :is(.staff-card-head,.branch-panel__head,.sa-head,.oe-section-head,.oe-header) {
    border-bottom:1px solid var(--dash-divider) !important;
    box-shadow:none !important;
}
{!! $dashboard !!} :is(.staff-card-head,.branch-panel__head) { background:transparent !important; }
{!! $dashboard !!} :is(.staff-card-head,.branch-panel__head,.sa-head,.oe-section-head) :is(h2,h3) { line-height:1.35; }
{!! $dashboard !!} {!! $dashboardRows !!} {
    background:transparent !important;
    border:0 !important;
    border-radius:0 !important;
    box-shadow:none !important;
}
{!! $dashboard !!} {!! $dashboardRows !!} + {!! $dashboardRows !!} { border-top:1px solid var(--dash-divider) !important; }
{!! $dashboard !!} {!! $dashboardRows !!}:hover { background:var(--dash-control) !important; outline:none; }
{!! $dashboard !!} :is(.branch-record-card__field,.branch-schedule-item,.oe-trend-comparison,.oe-ranking-context) { border-color:transparent !important; box-shadow:none !important; }
{!! $dashboard !!} :is(.branch-record-card__field,.branch-schedule-item,.oe-ranking-context) { background:transparent !important; }
{!! $dashboard !!} .oe-filter-card { background:transparent !important; border-color:transparent !important; box-shadow:none !important; }
{!! $dashboard !!} :is(.admin-kpi-card__arrow,.branch-panel__icon,.oe-title-icon,.sa-badge,.staff-context-badge,.branch-dashboard-context-badge) { border-color:transparent !important; box-shadow:none !important; }

/* Pointer states use the fill. Keyboard focus has one visible ring. */
{!! $dashboard !!} {!! $dashboardControls !!} {
    background:var(--dash-control) !important;
    border-color:transparent !important;
    box-shadow:none !important;
}
{!! $dashboard !!} :is(.branch-panel__link,.branch-panel__overflow) { transform:none !important; }
{!! $dashboard !!} {!! $dashboardControls !!}:is(:hover,.is-open) { background:var(--dash-hover) !important; border-color:transparent !important; }
{!! $dashboard !!} .oe-shared-select:focus-within { outline:none; }
{!! str_replace('html[data-theme]', "html[data-theme]:not([data-filter-input='pointer'])", $dashboard) !!} .oe-shared-select:has(select:focus-visible) { outline:2px solid var(--dash-focus); outline-offset:2px; }
{!! $dashboard !!} .oe-shared-select select:focus-visible { outline:none !important; }
{!! $dashboard !!} :is(.staff-tab-btn.is-active,.sa-primary,.oe-primary,.oe-segment button[aria-pressed='true']) {
    background:#A9C9B5 !important;
    color:#123524 !important;
    border-color:transparent !important;
    box-shadow:none !important;
}
{!! $dashboard !!} :is(.staff-tab-btn.is-active,.sa-primary,.oe-primary,.oe-segment button[aria-pressed='true']):hover { background:#92B89F !important; }
{!! $dashboard !!} .oe-kpi:focus-within { outline:none; }
{!! $dashboard !!} :is(a,button,input,select,summary,[tabindex]):focus-visible {
    outline:2px solid var(--dash-focus) !important;
    outline-offset:2px;
}
{!! $dashboard !!} :is(.sa-card-link,.oe-kpi-action):focus-visible { outline-offset:-3px; }
{!! $dashboard !!} :is(.admin-kpi-card,.branch-panel__link,.branch-panel__overflow,.branch-balance-item):hover:not(:focus-visible) { outline:none !important; }

{!! $dashboardDetails !!} { border:0 !important; box-shadow:none !important; background:var(--dash-panel) !important; }
{!! $dashboardDetails !!}:is(.branch-case-drawer,.oe-summary-dialog) { width:min(560px,100vw); border-radius:0 !important; }
{!! $dashboardDetails !!} > :is(header,.sa-dialog-head) { background:var(--panel-title-background,var(--dash-panel)) !important; border-bottom:1px solid var(--dash-divider) !important; padding:20px; }
{!! $dashboardDetails !!} > :is(footer,.sa-dialog-footer) { border-top:1px solid var(--dash-divider) !important; background:var(--dash-panel) !important; padding:16px 20px; }
{!! $dashboardDetails !!} :is(.branch-case-drawer__body,.oe-summary-body,.sa-dialog-fields) { padding:20px; min-height:0; }
{!! $dashboardDetails !!} .oe-drawer-scope { padding-bottom:16px; margin-bottom:20px; border-bottom:1px solid var(--dash-divider); }
{!! $dashboardDetails !!} .oe-summary-stat { padding:0; background:transparent; border:0; border-radius:0; }
{!! $dashboardDetails !!} .oe-summary-body { flex:1 1 auto; }
{!! $dashboardDetails !!} footer:has([data-summary-destination][hidden]) { display:none; }
{!! $dashboardDetails !!} footer { justify-content:flex-start; }
{!! $dashboardDetails !!} .sa-dialog-footer { justify-content:flex-start; }
{!! $dashboardDetails !!} footer :is([data-branch-summary-destination],[data-summary-destination]) { background:#A9C9B5 !important; color:#123524 !important; width:fit-content; }

@media(max-width:600px) {
    {!! $dashboard !!} { --dashboard-section-gap:12px; }
    {!! $dashboard !!} :is(.staff-summary-card,.admin-kpi-card,.sa-summary,.oe-kpi) { padding:16px !important; }
    {!! $dashboard !!} .staff-summary-label { font-size:12px; }
    {!! $dashboard !!} :is(.staff-summary-card > strong,.admin-kpi-card__value,.sa-summary strong,.oe-kpi-value) { font-size:clamp(1.15rem,5vw,1.65rem); }
    {!! $dashboardDetails !!} .branch-summary-fields { grid-template-columns:minmax(0,1fr); }
}
</style>
