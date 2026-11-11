<style>
.service-modal { width:100vw; max-width:none; height:100dvh; max-height:none; margin:0; border:0; background:transparent; }
.service-modal::backdrop { background:var(--modal-scrim,rgba(15,23,42,.72)); }
html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .service-modal__panel { background:var(--card); border:1px solid var(--border); border-radius:12px; box-shadow:none; width:min(860px,calc(100vw - 32px)); }
html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .service-modal__head { display:flex !important; padding:20px; padding-right:76px; background:var(--card); border-color:var(--border); }
html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .service-modal__head h2 { color:var(--ink); font-size:20px; font-weight:700; }
html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .service-modal__head p { color:var(--ink-muted); font-weight:400; }
html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .service-modal__floating-close { display:grid; width:44px; height:44px; border-radius:8px; background:var(--surface-muted); border-color:var(--border); color:var(--ink); box-shadow:none; }

/* Service catalog presentation. Typography inherits the existing role foundation. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .page-content > .service-management-hub {
    --catalog-surface:var(--card); --catalog-soft:var(--surface-muted); --catalog-line:var(--border);
    --catalog-selected:var(--card); --catalog-accent:var(--brand); --catalog-success:#167044; --catalog-warning:#946000; color:var(--ink); padding-top:32px !important; min-width:0; display:block;
}
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .page-content > .service-management-hub {
    --catalog-surface:#121D17; --catalog-soft:#0E1511; --catalog-line:#163629; --catalog-selected:#1F3528; --catalog-accent:#52E6B0; --catalog-success:#52E6B0; --catalog-warning:#FBBF24;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub > .mx-auto { max-width:1440px; display:grid; grid-template-columns:minmax(0,1fr); align-content:start; gap:24px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub > .mx-auto > * { margin-top:0; min-width:0; align-self:start; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-reference-header { display:block; padding:24px; border:1px solid var(--catalog-line); border-radius:20px; background:var(--catalog-surface); text-align:left; box-shadow:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-reference-heading { display:block; padding:0 0 20px; border-bottom:1px solid var(--catalog-line); text-align:center; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-reference-heading h1 { margin:0; font-family:var(--font-heading); font-size:1.5rem; font-weight:700; line-height:1.3; color:var(--ink); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-reference-heading p { margin:6px 0 0; color:var(--ink-muted); font-size:.875rem; line-height:1.6; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub :is(.service-hub-card,.service-hub-nav,.service-hub-tabs,.service-hub-panels,.service-viewbar) { background:transparent !important; border:0 !important; box-shadow:none !important; overflow:visible; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-nav { padding:16px 0 0; min-height:0; display:block; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-tabs { display:flex; align-items:center; gap:6px; width:auto; flex-wrap:wrap; padding:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-tab { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; min-height:44px; width:auto; border:0; border-radius:12px; background:transparent !important; color:var(--reference-tab-muted) !important; font-size:.875rem; font-weight:600; text-decoration:none; white-space:nowrap; transition:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-tab.is-active { background:var(--reference-action-bg) !important; color:var(--reference-action-text) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-tab::after { display:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-tab b { display:grid; place-items:center; min-width:22px; height:22px; padding:0 5px; border-radius:99px; background:var(--catalog-soft); color:var(--ink-muted); font-size:.75rem; font-weight:600; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-tab.is-active b { background:var(--reference-action-hover); color:var(--reference-action-text); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-tab:hover { background:var(--filter-ui-surface) !important; color:var(--filter-ui-text) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-card { display:grid; grid-template-columns:minmax(0,1fr); align-content:start; gap:24px; border-radius:0; padding:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-section-intro { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 0 20px; border-bottom:1px solid var(--catalog-line); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-section-intro h2 { margin:0; color:var(--ink); font-size:1.5rem; font-weight:700; line-height:1.3; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-section-intro p { margin:6px 0 0; color:var(--ink-muted); font-size:.875rem; line-height:1.6; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-section-intro .btn-primary-custom { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:44px; padding:10px 16px; background:var(--reference-action-bg) !important; color:var(--reference-action-text) !important; border:0; border-radius:10px; font-size:.875rem; font-weight:600; flex-shrink:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-readonly { color:var(--ink-muted); font-size:.75rem; white-space:nowrap; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-toolbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; position:static; margin:0; padding:12px; background:var(--catalog-surface) !important; border:1px solid var(--catalog-line) !important; border-radius:14px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-filter { display:flex; align-items:center !important; justify-content:space-between; flex:1; gap:16px; flex-wrap:wrap; margin:0; min-width:0; width:auto; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-search-field { flex:1 1 240px; width:auto; min-width:0; max-width:384px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .table-toolbar-label { display:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .table-toolbar-search { padding:0 36px 0 36px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .table-toolbar-select { padding:0 32px 0 34px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .table-toolbar-leading-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--ink-muted); pointer-events:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .table-toolbar-select-icon { position:absolute; right:10px; top:50%; transform:translateY(-50%); pointer-events:none; font-size:.75rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .live-search-clear { position:absolute; right:6px; top:5px; width:32px; height:32px; border:0; background:transparent; color:var(--ink-muted); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-filter-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-left:auto; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub :is(.service-status-toggle,.service-view-toggle) { display:flex; align-items:center; gap:2px; padding:4px; border:1px solid var(--catalog-line); border-radius:9px; background:var(--surface-muted); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub :is(.service-status-toggle button,.service-view-option) { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-height:36px; padding:7px 11px; border:0; border-radius:6px; background:transparent; color:var(--ink-muted); font-family:inherit; font-size:.8125rem; font-weight:500; white-space:nowrap; cursor:pointer; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub :is(.service-status-toggle button.is-active,.service-view-option.is-active) { background:var(--catalog-selected); color:var(--catalog-accent); font-weight:600; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-viewbar { display:flex; padding:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-panel { display:none; min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-panel.is-active { display:block; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-cards { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); align-items:stretch; gap:16px; padding:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-promotion-cards { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; padding:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-cards[hidden] { display:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card { display:flex; min-width:0; flex-direction:column; gap:14px; padding:18px; border:1px solid var(--catalog-line); border-radius:14px; background:var(--catalog-surface); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__identity { display:flex; align-items:center; gap:10px; min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__icon { display:grid; place-items:center; flex:0 0 36px; width:36px; height:36px; border:1px solid var(--catalog-line); border-radius:10px; background:var(--catalog-soft); color:var(--catalog-accent); font-size:1rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__head h3 { margin:0; color:var(--ink); font-size:1rem; font-weight:650; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__review { margin:.35rem 0 0; color:var(--ink-muted); font-size:.8rem; line-height:1.4; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__details { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__details > div { min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__details dt { color:var(--ink-muted); font-size:.76rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__details dd { margin:3px 0 0; color:var(--ink); font-size:.9rem; font-weight:550; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__actions { display:flex; justify-content:flex-end; align-items:center; gap:8px; margin-top:auto; padding-top:10px; border-top:1px solid var(--catalog-line); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-promotion-card__actions form { display:inline-flex; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card { display:flex; flex-direction:column; gap:14px; min-width:0; padding:18px; border:1px solid var(--catalog-line); border-radius:14px; background:var(--catalog-surface); box-shadow:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; padding:0; border:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__head > div { min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__identity { display:flex; align-items:center; gap:10px; min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__identity > div { min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__icon { display:grid; flex:0 0 36px; place-items:center; width:36px; height:36px; border:1px solid var(--catalog-line); border-radius:10px; background:var(--catalog-soft); color:var(--catalog-accent); font-size:1rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__head h3 { margin:0; color:var(--ink); font-size:1rem; font-weight:700; line-height:1.5; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__head p { margin:3px 0 0; color:var(--ink-muted); font-size:.75rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__price { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; padding:14px 12px; border:1px solid var(--catalog-line); border-radius:9px; background:transparent; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__price > span { color:var(--ink-muted); font-size:.75rem; text-transform:uppercase; font-weight:600; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__price strong { color:var(--catalog-accent); font-size:1.25rem; font-weight:700; font-variant-numeric:tabular-nums; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__price small { flex-basis:100%; color:var(--ink-muted); font-size:.75rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub :is(.service-package-card__description,.service-package-summary) { margin:0; color:var(--ink-muted); font-size:.8125rem; line-height:1.65; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-summary { display:flex; align-items:baseline; gap:8px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-summary i { color:var(--catalog-accent); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__lists { display:grid; gap:14px; padding:0; border:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__lists h4 { margin:0 0 8px; color:var(--ink-muted); font-size:.75rem; font-weight:500; text-transform:uppercase; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__lists ul { display:flex; flex-wrap:wrap; gap:6px; margin:0; padding:0; list-style:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__lists li { padding:3px 8px; background:var(--catalog-soft); border:1px solid var(--catalog-line); border-radius:4px; color:var(--catalog-accent); font-size:.75rem; line-height:1.4; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__lists :is(p,em) { margin:0; color:var(--ink-muted); font-size:.8125rem; line-height:1.6; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-card__foot { display:block; margin-top:auto; padding:12px 0 0; border-top:1px solid var(--catalog-line); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub :is(.service-catalog-actions,.service-catalog-write-actions,.row-hover-actions) { display:flex; align-items:center; gap:6px; opacity:1; visibility:visible; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions { justify-content:space-between; width:100%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions form { margin:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub :is(.row-hover-action,[data-service-modal-title^="View"]) { display:inline-flex; align-items:center; justify-content:center; gap:8px; width:40px; min-width:40px; height:40px; padding:0; border:0; border-radius:8px; background:transparent !important; color:var(--ink-muted) !important; opacity:1 !important; visibility:visible !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions .service-card-view { width:auto; min-width:0; justify-content:flex-start; color:var(--ink) !important; font-size:.875rem; font-weight:600; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions .service-card-view span { display:inline; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-promotion-card__actions .service-card-view { width:auto; min-width:0; min-height:36px; padding:0 6px; gap:7px; color:var(--ink) !important; font-size:.8125rem; font-weight:600; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-promotion-card__actions .service-card-view span { display:inline; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .row-hover-action:hover { background:var(--surface-muted) !important; color:var(--catalog-accent) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table { min-width:0; max-width:100%; margin:0; border:1px solid var(--catalog-line); border-radius:14px; overflow-x:auto; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table table { width:100%; min-width:800px; border-collapse:collapse; background:var(--catalog-surface); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table :is(th,td) { padding:18px 20px; border-bottom:1px solid var(--catalog-line); font-family:inherit; font-size:.875rem; color:var(--ink) !important; background:var(--catalog-surface) !important; vertical-align:middle; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table th { background:var(--catalog-soft) !important; font-size:.75rem; font-weight:600; text-transform:uppercase; text-align:left; color:var(--ink-muted) !important; white-space:normal; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table tr:last-child td { border-bottom:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .table-col-number { text-align:right; font-variant-numeric:tabular-nums; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .table-col-actions { width:180px; min-width:180px; text-align:right; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-row-note { margin-top:4px; max-width:400px; color:var(--ink-muted); font-size:.75rem; line-height:1.6; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-status { display:inline-flex; align-items:center; gap:6px; border:0; background:transparent; color:var(--ink-muted); padding:0; font-size:.75rem; font-weight:500; white-space:nowrap; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-status::before { content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-status.is-active { color:var(--catalog-success); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-status.is-warning { color:var(--catalog-warning); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-status.is-archived { color:var(--catalog-warning); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-identity { display:flex; align-items:center; gap:12px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-identity > i { display:grid; place-items:center; flex:0 0 36px; height:36px; border-radius:8px; background:var(--catalog-soft); color:var(--catalog-accent); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-intake-warning { margin:0; color:#B77716; font-size:.75rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-pagination { padding:20px 0 0; background:transparent; border:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-empty { display:grid; gap:6px; justify-items:center; padding:40px 20px; grid-column:1 / -1; border:1px dashed var(--catalog-line); border-radius:12px; color:var(--ink-muted); background:var(--catalog-surface); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub :is([hidden],.service-package-table[hidden]) { display:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub :is(a,button,input,select):focus-visible { outline:2px solid var(--catalog-accent); outline-offset:3px; }
@media(max-width:1200px) {
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-filter { flex-basis:100%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-viewbar { margin-left:auto; }
}
@media(max-width:1000px) {
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-cards { grid-template-columns:repeat(2,minmax(0,1fr)); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-promotion-cards { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media(max-width:640px) {
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .page-content > .service-management-hub { padding:64px 16px 16px; padding-top:64px !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-reference-header { padding:18px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-tabs { flex-wrap:nowrap; overflow-x:auto; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-cards { grid-template-columns:minmax(0,1fr); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-promotion-cards { grid-template-columns:minmax(0,1fr); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-section-intro { align-items:flex-start; flex-direction:column; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-search-field { max-width:none; flex-basis:100%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-filter-actions { margin-left:0; width:100%; }
}


html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui):has(.service-management-hub) :is(.main-area,.page-content) { background:#0E1511 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-extra-filter { width:auto; flex:0 0 auto; max-width:220px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-extra-filter select { width:auto; padding-left:12px; max-width:220px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-complimentary { display:inline-block; padding:4px 8px; border-radius:4px; color:var(--catalog-accent); background:var(--catalog-soft); font-size:.75rem; white-space:nowrap; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions button.row-hover-action { background:transparent !important; border:0 !important; box-shadow:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-tab.is-active:is(:hover,:focus-visible) { background:var(--reference-action-bg) !important; color:var(--reference-action-text) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table th:is(.table-col-number,.table-col-actions) { text-align:right; }

/* Package table hierarchy and icon actions follow the supplied catalog reference. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions button.row-hover-action[type="submit"] { color:var(--catalog-action-color, var(--ink-muted)) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions button.row-hover-action:is(:hover,:focus-visible) { background:var(--surface-muted) !important; color:var(--catalog-accent) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions button.row-hover-action i { color:inherit; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table table { table-layout:fixed; min-width:1280px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .package-name-col { width:16%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .package-price-col { width:9%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .package-services-col { width:22%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .package-freebies-col { width:17%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .package-promo-col { width:16%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .package-status-col { width:8%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .package-actions-col { width:12%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table :is(th,td) { padding:20px; text-align:left; overflow-wrap:anywhere; border-left:0 !important; border-right:0 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table tbody td { padding:12px 14px; vertical-align:middle; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .table-col-number { text-align:right; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-centered { text-align:center; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebies-cell { text-align:left; vertical-align:top; overflow-wrap:normal; word-break:normal; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .table-col-actions { width:auto; min-width:0; text-align:right; padding-left:8px; padding-right:14px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .service-catalog-actions { justify-content:flex-end; gap:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .service-catalog-write-actions { gap:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .service-catalog-actions button.row-hover-action { flex:0 0 36px; width:36px; min-width:36px; height:40px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .catalog-primary-cell { font-weight:600; font-size:.875rem; line-height:1.6; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-code { margin:3px 0 0; color:var(--ink-muted); font-size:.75rem; line-height:1.5; font-variant-numeric:tabular-nums; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-base-price { font-weight:700; color:var(--catalog-accent) !important; white-space:nowrap; overflow-wrap:normal; font-variant-numeric:tabular-nums; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-inclusion-chips { display:flex; flex-wrap:wrap; gap:0 8px; padding:0; margin:0; list-style:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-inclusion-chips li.is-catalog-affected { order:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-inclusion-chips li.is-catalog-item { order:1; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-inclusion-chips li.is-service-item { order:2; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-inclusion-chips li { display:inline-block; max-width:100%; padding:0; border:0; border-radius:0; background:transparent; color:var(--ink); font-size:.8rem; line-height:1.5; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-inclusion-chips li.is-empty { background:transparent; color:var(--ink-muted); padding:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebie-preview { display:flex; flex-wrap:wrap; align-items:flex-start; gap:0 8px; max-width:100%; margin:0; padding:0; list-style:none; font-size:.8rem; line-height:1.5; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebie-preview li { min-width:0; max-width:100%; margin:0; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebie-more-trigger { display:inline-flex; align-items:center; margin-top:5px; padding:2px 0; border:0; background:transparent; color:var(--catalog-accent); font-size:.75rem; font-weight:700; line-height:1.4; text-decoration:underline; text-underline-offset:2px; cursor:pointer; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebie-more-trigger:hover { color:var(--catalog-warning); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebie-more-trigger:focus-visible { outline:2px solid var(--catalog-accent); outline-offset:3px; border-radius:3px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebie-more-popover { position:fixed; inset:auto; z-index:1000; box-sizing:border-box; width:min(300px, calc(100vw - 24px)); max-height:min(280px, calc(100dvh - 24px)); overflow:auto; margin:0; padding:12px 14px; border:1px solid var(--catalog-line); border-radius:10px; background:var(--catalog-surface); color:var(--ink); box-shadow:0 12px 32px rgb(17 38 30 / 20%); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebie-more-popover::backdrop { background:transparent; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebie-more-popover > strong { display:block; margin-bottom:6px; color:var(--ink); font-size:.8rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebie-more-popover ul { display:grid; gap:5px; margin:0; padding:0; list-style:none; color:var(--ink-muted); font-size:.8rem; line-height:1.4; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-list-table .package-freebie-more-popover li { overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .package-catalog-warning-icon { margin-right:3px; font-size:.8em; vertical-align:baseline; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-count-badge { display:inline-block; padding:3px 8px; border-radius:4px; background:var(--surface-muted); color:var(--ink); font-size:.75rem; font-weight:600; white-space:nowrap; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .package-freebie-items { display:flex; flex-wrap:wrap; justify-content:flex-start; gap:4px 9px; margin:6px 0 0; padding:0; list-style:none; font-size:.8rem; line-height:1.45; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .package-freebie-items li { min-width:0; max-width:100%; margin:0; overflow-wrap:break-word; word-break:normal; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-promotion-badge { display:inline-flex; align-items:center; justify-content:center; gap:8px; max-width:100%; padding:6px 10px; border-radius:99px; color:var(--catalog-accent); background:var(--surface-muted); font-size:.75rem; font-weight:600; line-height:1.4; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-promotion-badge.is-muted { color:var(--ink-muted); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-promotion-empty { color:var(--ink-muted); }

/* Archive is cautionary and reversible; restore and active status use success green. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions .is-archive-action { --catalog-action-color:var(--catalog-warning); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions .is-restore-action { --catalog-action-color:var(--catalog-success); }

/* Keep catalog table action slots aligned, including archived rows. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub td.table-col-actions .service-catalog-actions { justify-content:flex-end; gap:4px; width:auto; white-space:nowrap; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub td.table-col-actions .service-catalog-write-actions { display:contents; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub td.table-col-actions .service-catalog-actions > small { display:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub td.table-col-actions :is(.row-hover-action,.service-action-placeholder) { flex:0 0 36px; width:36px; min-width:36px; height:40px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub th.table-col-actions { text-align:right; }

html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .table-toolbar-input-wrap { position:relative; display:flex; align-items:center; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .table-toolbar-leading-icon { position:absolute !important; left:14px !important; top:50% !important; transform:translateY(-50%); margin:0; pointer-events:none; z-index:1; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .live-search-clear { top:50%; transform:translateY(-50%); display:inline-flex; align-items:center; justify-content:center; border:0; box-shadow:none; background:transparent; font-size:20px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .live-search-clear[hidden] { display:none !important; }
.service-management-hub input[type=search]::-webkit-search-cancel-button { -webkit-appearance:none; }

.service-management-hub .table-toolbar-select-wrap .table-toolbar-select-icon { transition:transform .18s ease; }
.service-management-hub .table-toolbar-select-wrap.is-open .table-toolbar-select-icon { transform:translateY(-50%) rotate(180deg) !important; }
.service-management-hub .filter-select-menu__item > span { flex:1; }

/* One shared hover treatment for every catalog action, including submit buttons. */
html[data-theme] .service-management-hub { --action-hover-bg:#d9e9e0; --action-warning-hover-bg:#f6e6c4; --action-warning-hover-ink:#805000; }
html[data-theme='dark'] .service-management-hub { --action-hover-bg:#254b3a; --action-warning-hover-bg:#514021; --action-warning-hover-ink:#ffd477; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions button.row-hover-action { transition:background-color .15s ease,color .15s ease; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions button.row-hover-action:is(:hover,:focus-visible) { background:var(--action-hover-bg) !important; color:var(--catalog-accent) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions button.row-hover-action:is(.is-archive-action,[aria-label^="Mark unavailable"]):is(:hover,:focus-visible) { background:var(--action-warning-hover-bg) !important; color:var(--action-warning-hover-ink) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions button.row-hover-action:focus-visible { outline:2px solid currentColor; outline-offset:2px; }

/* Pointer interaction stays quiet; keyboard focus retains its visible indicator. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub[data-pointer-filters] .service-hub-filter :is(input,select,button,.table-toolbar-input-wrap,.table-toolbar-select-wrap):is(:focus,:focus-visible,:focus-within,.is-open) { outline:none !important; box-shadow:none !important; }

/* Match the catalog toolbar reference: flexible search, adjacent controls, view switch at the edge. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-toolbar { display:flex; flex-wrap:wrap; align-items:center; width:100%; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-toolbar > .service-hub-filter { flex:1 1 auto; min-width:0; justify-content:flex-start; flex-wrap:nowrap; gap:12px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-filter .catalog-search-field { flex:1 1 0; width:auto; min-width:12rem; max-width:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-filter .table-toolbar-search { background:var(--surface-panel) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-filter .service-filter-actions { display:flex; flex:0 0 auto; min-width:0; width:auto; flex-wrap:nowrap; margin-left:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-filter-actions .catalog-filter-sort-actions { width:auto; flex:0 0 auto; justify-content:flex-start; margin-left:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-toolbar > .service-viewbar { flex:0 0 auto; margin-left:auto; padding-left:12px; border-left:1px solid var(--catalog-line) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-card > .catalog-active-filter-chips {
    box-sizing:border-box; width:100%; min-height:36px; margin-block:-12px !important;
    align-items:center; gap:8px 12px; font-family:'Plus Jakarta Sans','Segoe UI',sans-serif;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-card > .catalog-active-filter-chips .catalog-filter-chip { flex:0 0 auto; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-card > .catalog-active-filter-chips .catalog-clear-all {
    min-height:36px; margin-left:auto; padding:0 10px; border:1px solid transparent !important;
    border-radius:6px; background:transparent !important; color:#B42318 !important;
    font-size:14px; font-weight:400; line-height:1; box-shadow:none !important; transform:none !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-card > .catalog-active-filter-chips .catalog-clear-all:is(:hover,:focus-visible) {
    border-color:color-mix(in srgb,#B42318 35%,transparent) !important;
    background:transparent !important; color:#B42318 !important; outline:none;
}
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-card > .catalog-active-filter-chips .catalog-clear-all { color:#FDA29B !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-card > .catalog-active-filter-chips .catalog-clear-all:is(:hover,:focus-visible) {
    border-color:color-mix(in srgb,#FDA29B 35%,transparent) !important; color:#FDA29B !important;
}
@media(max-width:900px) {
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-toolbar > .service-hub-filter { flex:1 1 100%; flex-wrap:wrap; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-filter .catalog-search-field { flex:1 1 20rem; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-filter .service-filter-actions { flex:0 0 auto; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-toolbar > .service-viewbar { margin-left:auto; }
}
@media(max-width:640px) {
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-filter .catalog-search-field { flex:1 1 100%; min-width:0; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-filter .service-filter-actions { width:100%; flex-wrap:wrap; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-filter-actions .catalog-filter-sort-actions { width:100%; flex-wrap:wrap; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-filter-actions .catalog-control { flex:1 1 8rem; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-viewbar { margin-left:auto; }
}
/* Catalog actions follow the current payment-action treatment: neutral at
   rest, semantic ink on hover, with no filled/blurred button surface. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference {
    --catalog-action-view:#1B4332; --catalog-action-warning:#A15C00;
}
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference {
    --catalog-action-view:#5CE0A0; --catalog-action-warning:#FDC76A;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-catalog-actions button.row-hover-action {
    --catalog-action-hover:var(--catalog-action-view);
    background:transparent !important; border:0 !important; box-shadow:none !important;
    color:var(--ink) !important; transform:none !important; filter:none !important;
    text-shadow:none !important; transition:none !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-catalog-actions button.row-hover-action:is(.is-edit-action,.is-archive-action,[aria-label^='Mark unavailable']) { --catalog-action-hover:var(--catalog-action-warning); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-catalog-actions button.row-hover-action:is(:hover,:focus-visible) {
    background:transparent !important; color:var(--catalog-action-hover) !important;
    box-shadow:none !important; filter:none !important; transform:none !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-catalog-actions button.row-hover-action > i { color:inherit !important; filter:none !important; text-shadow:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-catalog-actions button.row-hover-action:focus-visible { outline:2px solid var(--catalog-action-hover) !important; outline-offset:2px; }
/* Primary View stays visible; write actions live in the labelled overflow menu. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions .service-primary-view:not(.primary-view-action--card) { display:inline-flex; align-items:center; justify-content:center; flex:0 0 40px !important; width:40px !important; min-width:40px !important; height:40px; padding:0 !important; color:var(--ink) !important; font-size:1rem; font-weight:600; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions .service-primary-view.primary-view-action--card { display:inline-flex; align-items:center; justify-content:flex-start; flex:0 0 auto !important; width:auto !important; min-width:0 !important; height:40px; gap:7px; padding:0 6px !important; background:transparent !important; color:var(--ink) !important; font-size:.8125rem; font-weight:600; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-catalog-actions .service-primary-view.primary-view-action--card :is(i,span) { color:inherit !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-actions-menu .service-catalog-write-actions { display:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-actions-menu.is-open .service-catalog-write-actions { display:block; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub td.table-col-actions .service-actions-menu .service-catalog-write-actions { display:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub td.table-col-actions .service-actions-menu.is-open .service-catalog-write-actions { display:block; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-actions-menu .compact-action-item { transition:background-color .15s ease,color .15s ease; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub td.table-col-actions .service-catalog-actions { min-width:126px; }

/* Service catalog tables: compact, readable, and stable across every catalog. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table {
    max-width:100%; overflow-x:auto; overflow-y:visible; border:1px solid var(--catalog-line);
    border-radius:8px; background:var(--catalog-surface); box-shadow:none; scrollbar-gutter:stable;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table table {
    width:100%; border-collapse:separate; border-spacing:0; table-layout:auto; background:transparent !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub [data-service-panel='packages'] .service-package-table table { min-width:1180px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub [data-service-panel='caskets'] .service-package-table table { min-width:880px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub [data-service-panel='addons'] .service-package-table table { min-width:960px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub [data-service-panel='freebies'] .service-package-table table { min-width:900px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub [data-service-panel='hearses'] .service-package-table table { min-width:960px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub [data-service-panel='promotions'] .service-package-table table { min-width:1080px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table thead,
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table thead tr,
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table thead th {
    background:transparent !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table thead th {
    padding:14px 16px; border:0; border-bottom:1px solid var(--catalog-line); color:var(--ink) !important;
    font-size:13px; font-weight:600; line-height:1.35; letter-spacing:.045em; text-transform:none; vertical-align:middle;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table tbody td {
    padding:15px 16px; border:0; border-bottom:1px solid var(--catalog-line); background:transparent !important;
    color:var(--ink) !important; font-size:14px; font-weight:400; line-height:1.5; vertical-align:middle;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table tbody tr:last-child td { border-bottom:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table tbody tr {
    position:relative; background:transparent !important; box-shadow:inset 0 0 0 1px transparent; transition:box-shadow .15s ease;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table tbody tr:is(:hover,:focus-within) {
    background:transparent !important; box-shadow:inset 0 0 0 1px #AEC7B7;
}
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table tbody tr:is(:hover,:focus-within) { box-shadow:inset 0 0 0 1px #41634F; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table tbody td:first-child :is(.catalog-primary-cell,.font-semibold),
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table tbody td:first-child.font-semibold,
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table tbody td:first-child > strong { font-size:15px; font-weight:600; line-height:1.4; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table :is(.service-row-note,.service-package-code,small) { color:var(--ink-muted) !important; font-size:12.5px; font-weight:400; line-height:1.45; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table :is(th,td).table-col-number { text-align:right; font-variant-numeric:tabular-nums; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table :is(th,td).table-col-actions { width:112px; min-width:112px; padding-left:10px; padding-right:10px; text-align:right; white-space:nowrap; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table td.table-col-actions .service-catalog-actions { min-width:0; justify-content:flex-end; gap:4px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table .table-system-empty { min-height:240px; padding:48px 20px !important; color:var(--ink-muted) !important; font-weight:400; text-align:center; }
@media(max-width:640px) {
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table thead th { padding:12px 14px; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-package-table tbody td { padding:14px; }
}
/* Search keeps its flexible track so Filter/Sort and the view switch do not
   move when only the visible search field is capped. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference {
    --catalog-search-surface:#EAF2ED;
}
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference { --catalog-search-surface:#182D22; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-hub-filter .service-record-search.table-toolbar-input-wrap {
    position:relative; box-sizing:border-box !important; width:100%; height:44px !important;
    border:1px solid var(--filter-ui-border) !important; border-radius:14px !important;
    background:var(--catalog-search-surface) !important; box-shadow:none !important; overflow:visible; transition:none !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-hub-filter .service-record-search > input {
    --filter-control-background:transparent; --filter-control-border:0;
    width:100% !important; height:42px !important; min-height:0 !important; max-height:none !important;
    padding:0 128px 0 40px !important; border:0 !important; border-radius:inherit !important;
    background:transparent !important; font-size:14px !important; font-weight:400 !important; color:var(--filter-ui-text) !important; transition:none !important;
}
html[data-theme] body.panel-shell-body .service-record-search > input::-webkit-search-cancel-button { display:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-hub-filter .service-record-search > .live-search-clear {
    position:absolute !important; top:50% !important; right:92px !important; left:auto !important;
    width:28px !important; height:28px !important; min-height:0 !important; padding:0 !important;
    transform:translateY(-50%) !important; background:transparent !important; border:0 !important; box-shadow:none !important; color:var(--filter-ui-text) !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-record-search > .service-search-submit {
    position:absolute; inset:0 0 0 auto; width:84px; min-height:0 !important; padding:0 !important;
    border:0 !important; border-left:1px solid var(--filter-ui-border) !important; border-radius:0 13px 13px 0 !important;
    background:var(--record-search-action) !important; color:var(--record-search-action-text) !important; font-size:14px !important; font-weight:500 !important;
    box-shadow:none !important; transform:none !important; cursor:pointer; transition:none !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-record-search > .service-search-submit:hover { background:var(--record-search-action-hover) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-record-search .service-search-matches {
    position:absolute; top:calc(100% + 6px); left:0; right:0; z-index:90; max-height:280px; overflow:auto; box-sizing:border-box;
    padding:6px; border:1px solid var(--filter-ui-border); border-radius:10px; background:var(--filter-ui-menu); color:var(--filter-ui-text);
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-record-search [hidden] { display:none !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-record-search .service-search-match {
    display:flex; flex-wrap:nowrap; align-items:center; gap:10px; width:100%; min-height:40px; padding:8px 10px;
    border:0 !important; border-radius:6px; background:transparent !important; color:var(--filter-ui-text) !important;
    text-align:left; white-space:nowrap; box-shadow:none !important; transform:none !important; filter:none !important; transition:none !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-record-search .service-search-match:is(:hover,:focus-visible) { background:var(--record-choice-surface) !important; }
.service-search-match :is(.service-match-name,.service-match-detail) { min-width:0; overflow:hidden; text-overflow:ellipsis; }
.service-search-match .service-match-name { flex:0 1 auto; max-width:65%; font-size:14px; font-weight:600; }
.service-search-match .service-match-name:only-child { max-width:100%; }
.service-search-match .service-match-detail { display:flex; align-items:center; gap:10px; flex:1 1 0; font-size:13px; font-weight:400; }
.service-search-match .service-match-detail::before { content:'·'; flex:none; }
.service-search-match .service-match-detail > span { min-width:0; overflow:hidden; text-overflow:ellipsis; }
.service-search-match mark { background:transparent; color:inherit; font:inherit; text-decoration:underline; text-decoration-thickness:1px; text-underline-offset:3px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-record-search :is(button,input):focus-visible { outline:2px solid var(--filter-ui-focus) !important; outline-offset:2px; }
@media(min-width:1201px) {
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-hub-filter .service-record-search.table-toolbar-input-wrap { width:min(560px,calc(100% - 24px)); }
}
</style>

@once
<script>
document.addEventListener('pointerdown', event => {
    const hub = event.target.closest('.service-management-hub');
    if (hub && event.target.closest('.service-hub-filter')) hub.setAttribute('data-pointer-filters', '');
}, true);
document.addEventListener('keydown', event => {
    if (event.key === 'Tab' || event.key.startsWith('Arrow')) document.querySelectorAll('[data-pointer-filters]').forEach(hub => hub.removeAttribute('data-pointer-filters'));
}, true);
</script>
@endonce

<style>
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub.service-reference .service-catalog-actions button.service-primary-view { color:var(--record-view-ink,var(--ink)) !important; }
</style>
