<style>
/* Owner presentation foundation; keeps role data and permissions independent. */
html[data-theme] body.owner-ui {
    --font-body:'Plus Jakarta Sans','Segoe UI',sans-serif; --font-heading:var(--font-body);
    --surface:#DCE8E0; --card:#F4F8F5; --surface-muted:#D0E2D6; --border:#AEC7B7;
    --ink:#081D13; --ink-muted:#1B3E2E; --brand:#1B4332; --owner-hover:#C2DAC9;
    --color-text-primary:var(--ink); --color-text-secondary:var(--ink-muted);
    font-family:var(--font-body); color:var(--ink);
}
html[data-theme='dark'] body.owner-ui {
    --surface:#07130E; --card:#112218; --surface-muted:#14261D; --border:#29523D;
    --ink:#F8FAFC; --ink-muted:#B5C7BC; --brand:#B5D6C4; --owner-hover:#1B3527;
}
@media screen {
    html[data-theme] body.panel-shell-body.owner-ui .owner-package-analysis :is(h2,td) { color:var(--ink) !important; }
    html[data-theme] body.panel-shell-body.owner-ui .owner-package-analysis :is(p,th) { color:var(--ink-muted) !important; }
    html[data-theme] body.panel-shell-body.owner-ui .owner-package-analysis tbody tr { background:var(--card) !important; }
    html[data-theme] body.panel-shell-body.owner-ui .owner-package-analysis td { background:transparent !important; }
    html[data-theme] body.panel-shell-body.owner-ui .owner-package-analysis th { background:var(--surface-muted) !important; }
    html[data-theme] body.owner-ui :is(.page-content,.eb-shell,.owner-page-shell) { background:var(--surface) !important; color:var(--ink); font-family:var(--font-body) !important; }
    html[data-theme] body.owner-ui .eb-shell {
        --eb-text:var(--ink); --eb-text-muted:var(--ink-muted); --eb-text-faint:var(--ink-muted);
        --eb-card:var(--card); --eb-card-alt:var(--surface-muted); --eb-card-warm:var(--card);
        --eb-readable-muted:var(--ink-muted); --eb-accent:var(--brand); --eb-hover:var(--owner-hover);
    }
    html[data-theme] body.owner-ui .eb-shell :is(.eb-card,.eb-overview-card,.eb-branch-card,.eb-trend-card,.eb-attention-card,.eb-branch-bar-item,.eb-status-row,.eb-pkg-row,.eb-quick-item) {
        background:var(--card) !important; border-color:var(--border) !important; color:var(--ink) !important; box-shadow:none !important; border-radius:12px;
    }
    html[data-theme] body.owner-ui .eb-shell :is(.eb-filter-bar,.eb-branch-pie-wrap,.eb-branch-bar-chart) { background:var(--card) !important; border:1px solid var(--border) !important; border-radius:12px; }
    html[data-theme] body.owner-ui .eb-shell :is(.eb-overview-value,.eb-branch-name,.eb-branch-bar-value,.eb-status-value,.eb-pkg-name,.eb-attention-name,.eb-attention-amount,h2,h3,h4) { color:var(--ink) !important; font-family:var(--font-body) !important; font-weight:600; }
    html[data-theme] body.owner-ui .eb-shell :is(.eb-overview-label,.eb-overview-desc,.eb-section-sub,.eb-branch-code,.eb-status-label,.eb-pkg-meta,.eb-attention-code,.eb-empty,.eb-status-name,.eb-status-count,.eb-status-pct) { color:var(--ink-muted) !important; font-weight:400; }
    html[data-theme] body.owner-ui .eb-shell :is(.eb-overview-icon,.eb-section-icon,.eb-quick-icon) { color:var(--ink-muted) !important; }
    html[data-theme] body.owner-ui .eb-shell :is(.eb-branch-select-wrap,.eb-period-select-wrap,.eb-quick-button,.eb-attention-link,.eb-pop-reset) { background:var(--surface-muted) !important; border:1px solid var(--border) !important; color:var(--ink) !important; border-radius:8px; font-weight:500; }
    html[data-theme] body.owner-ui .eb-shell :is(.eb-branch-select,.eb-period-select) { background:transparent !important; color:var(--ink) !important; border:0 !important; font-family:var(--font-body); font-weight:400; }
    html[data-theme] body.owner-ui .eb-shell :is(a.eb-overview-card,.eb-quick-button,.eb-branch-bar-item):is(:hover,:focus-visible) { background:var(--owner-hover) !important; }
    html[data-theme] body.owner-ui .eb-shell .eb-branch-bar-item.is-selected { background:var(--surface-muted) !important; border-color:var(--brand) !important; }
    html[data-theme] body.owner-ui .owner-page-shell { height:auto; min-height:0; overflow:visible; padding-top:0; }
    html[data-theme] body.owner-ui .owner-page-shell :is(.filter-panel,.list-card,.cv-card,.cv-stat,.cv-pkg-box,.cv-txn,.cv-tarp-empty) { background:var(--card) !important; border-color:var(--border) !important; color:var(--ink); }
    html[data-theme] body.owner-ui .owner-page-shell :is(.cv-card-head,.cv-interment-badge) { background:var(--surface-muted) !important; border-color:var(--border); }
    html[data-theme] body.owner-ui .owner-page-shell :is(.table-primary,.table-secondary,.cv-field-value,.cv-meta-value,.cv-txn-val) { font-weight:400; color:var(--ink); }
    html[data-theme] body.owner-ui .owner-history-table :is(th,td) { padding:14px 16px; }
    html[data-theme] body.owner-ui .owner-history-table th { font-weight:500; }
    html[data-theme] body.owner-ui .owner-page-shell .table-wrapper { overflow-x:auto; overflow-y:visible; max-height:none; }
    html[data-theme] body.owner-ui .owner-page-shell .list-card { overflow:visible; }
    html[data-theme] body.owner-ui #ebDatePopover {
        position:fixed; inset:0; width:min(420px,calc(100vw - 32px)); max-height:calc(100dvh - 48px);
        margin:auto; padding:24px; background:var(--card) !important; color:var(--ink); border:1px solid var(--border); border-radius:12px; z-index:auto;
    }
    #ebDatePopover::backdrop { background:var(--modal-scrim,rgba(15,23,42,.72)); }
    body.owner-date-open { overflow:hidden; }
    html[data-theme] body.owner-ui #ebDatePopover .eb-pop-input { background:var(--surface-muted); color:var(--ink); border-color:var(--border); font:400 14px var(--font-body); }
    html[data-theme] body.owner-ui #ebDatePopover .eb-pop-field-label { color:#1B3E2E !important; }
    html[data-theme='dark'] body.owner-ui #ebDatePopover .eb-pop-field-label { color:#B5C7BC !important; }
    html[data-theme] body.owner-ui #ebDatePopover h2 { margin:0 32px 20px 0; font:600 20px var(--font-body); color:var(--ink); }
    html[data-theme] body.owner-ui #ebDatePopover form { display:grid !important; grid-template-columns:minmax(0,1fr) !important; gap:20px; width:100%; }
    html[data-theme] body.owner-ui #ebDatePopover .eb-pop-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    html[data-theme] body.owner-ui #ebDatePopover .eb-pop-field { min-width:0; }
    html[data-theme] body.owner-ui #ebDatePopover .eb-pop-field-label { display:block !important; margin-bottom:6px; font-size:13px; text-transform:none; }
    html[data-theme] body.owner-ui #ebDatePopover .eb-pop-input { width:100%; min-width:0; height:44px; padding:8px; box-sizing:border-box; }
    html[data-theme] body.owner-ui #ebDatePopover .eb-pop-actions { display:flex; justify-content:flex-end; gap:8px; }
    html[data-theme='dark'] body.owner-ui .ba-shell .ba-chart-frame { background:var(--card) !important; border-color:var(--border) !important; }
    @media(max-width:540px) {
        html[data-theme] body.owner-ui #ebDatePopover .eb-pop-fields { grid-template-columns:minmax(0,1fr); }
        html[data-theme] body.owner-ui .eb-greeting:not(.oe-header) h1 { padding-left:40px; }
    }
}
</style>
