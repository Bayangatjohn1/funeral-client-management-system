<style>
html[data-theme] body:is(.record-surface-ui,.service-catalog-modal-frame) {
    --drawer-width:560px; --drawer-surface:#F7FAF8; --drawer-header:#D5E4D9; --drawer-soft:#EAF2ED;
    --drawer-ink:#123524; --drawer-muted:#617568; --drawer-line:#B8CEC0; --drawer-paid:#166534; --drawer-balance:#92400E; --drawer-hover:#E1EEE6;
}
html[data-theme=dark] body:is(.record-surface-ui,.service-catalog-modal-frame) {
    --drawer-surface:#15251C; --drawer-header:#1C3025; --drawer-soft:#22392C;
    --drawer-ink:#E7F0E9; --drawer-muted:#A7BCAE; --drawer-line:#355241; --drawer-paid:#4ADE80; --drawer-balance:#FBBF24; --drawer-hover:#2D4838;
}
html[data-theme] body.record-surface-ui .record-drawer,
html[data-theme] body.record-surface-ui .record-drawer.user-information-dialog:not(.directory-feedback-dialog) {
    position:fixed !important; inset:0 0 0 auto !important; margin:0 !important;
    box-sizing:border-box; width:min(var(--drawer-width),100vw) !important; max-width:100vw !important;
    height:100dvh !important; min-height:0 !important; max-height:100dvh !important; padding:0 !important;
    border:0 !important; border-radius:0 !important; background:var(--drawer-surface) !important;
    color:var(--drawer-ink) !important; box-shadow:none !important; overflow:hidden !important;
}
html[data-theme] body.record-surface-ui .record-drawer[open] { display:flex !important; flex-direction:column; }
html[data-theme] body.record-surface-ui .record-drawer::backdrop { background:var(--modal-scrim,rgba(15,23,42,.72)) !important; }
body.record-drawer-open { overflow:hidden !important; }
html[data-theme] body.record-surface-ui .record-drawer-body { flex:1 1 auto; min-height:0; overflow:auto; overscroll-behavior:contain; }
html[data-theme] body.record-surface-ui .case-summary-drawer .record-drawer-body { min-width:0; overflow-x:hidden; scrollbar-gutter:stable; }
html[data-theme] body.record-surface-ui :is(.record-drawer-head,.record-drawer-footer,.record-drawer .user-information-head,.record-drawer .user-information-footer) { flex:0 0 auto; }
html[data-theme] body.record-surface-ui .record-drawer-head {
    display:flex; align-items:center; gap:12px; padding:20px; border-bottom:1px solid var(--drawer-line);
    background:var(--drawer-header); color:var(--drawer-ink);
}
.record-drawer-head > div { min-width:0; flex:1; }
.record-drawer-head h2 { margin:2px 0 0; font-size:20px; line-height:1.3; font-weight:650; }
.record-drawer-eyebrow { color:var(--drawer-muted); font-size:12px; }
.record-drawer-icon { display:grid; place-items:center; width:40px; height:40px; flex:0 0 40px; border-radius:10px; background:var(--drawer-soft); }
.record-drawer-close { display:grid; place-items:center; flex:0 0 44px; width:44px; height:44px; border:0; border-radius:8px; background:var(--drawer-soft); color:var(--drawer-ink); }
.record-drawer-footer { display:flex; align-items:center; justify-content:flex-end; flex-wrap:wrap; gap:12px; padding:16px 20px; border-top:1px solid var(--drawer-line); background:var(--drawer-surface); }
.case-summary-drawer .record-drawer-footer { justify-content:flex-start; }
.record-drawer-primary,.record-drawer-secondary { display:inline-flex; justify-content:center; align-items:center; gap:8px; min-height:44px; padding:0 16px; border:0; border-radius:8px; font-size:14px; font-weight:600; text-decoration:none; cursor:pointer; }
html[data-theme] body.record-surface-ui .record-drawer-primary { background:#A9C9B5 !important; color:#123524 !important; }
html[data-theme] body.record-surface-ui .record-drawer-primary:hover { background:#92B89F !important; }
html[data-theme] body.record-surface-ui .record-drawer-secondary { background:var(--drawer-soft) !important; color:var(--drawer-ink) !important; }
html[data-theme] body.record-surface-ui :is(.record-drawer-close,.record-drawer-secondary):hover { background:var(--drawer-hover) !important; }
.record-drawer :is(a,button):focus-visible { outline:2px solid var(--filter-ui-focus,#1B4332); outline-offset:2px; }
.record-summary { padding:20px; display:grid; gap:20px; }
.record-summary-identity { display:grid; gap:12px; padding:0 0 20px; border:0; border-bottom:1px solid var(--drawer-line); border-radius:0; background:transparent; }
.record-summary-code { font:600 12px 'JetBrains Mono',monospace; color:var(--drawer-muted); }
.record-summary-identity h3 { font-size:22px; font-weight:650; line-height:1.35; margin:0; overflow-wrap:anywhere; }
.record-summary-identity p { display:flex; align-items:baseline; gap:8px; margin:0; color:var(--drawer-muted); font-size:13px; overflow-wrap:anywhere; }
.record-summary-identity p > i { flex:none; }
.record-summary-identity p > span { min-width:0; }
.record-summary-section + .record-summary-section { padding-top:20px; border-top:1px solid var(--drawer-line); }
.record-summary-section h3 { display:flex; align-items:center; gap:8px; margin:0 0 16px; font-size:14px; font-weight:650; }
.record-summary-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; margin:0; }
.record-summary-fields .is-wide { grid-column:1/-1; }
.record-summary-fields dt,.record-summary-money dt { margin-bottom:5px; color:var(--drawer-muted); font-size:12px; }
.record-summary-fields dd { margin:0; font-size:14px; font-weight:500; overflow-wrap:anywhere; }
.record-summary-money { display:grid; gap:12px; padding:16px; border-radius:10px; background:var(--drawer-soft); margin:0; }
.record-summary-money > div { display:flex; justify-content:space-between; gap:16px; align-items:baseline; }
.record-summary-money dd { margin:0; font-variant-numeric:tabular-nums; font-weight:650; }
.record-summary-money .is-paid dd { color:var(--drawer-paid); }
.record-summary-money .is-balance dd { color:var(--drawer-balance); }
.record-drawer-message { padding:32px 20px; text-align:center; color:var(--drawer-muted); }
.record-drawer-message > i { font-size:24px; }
/* Branch keeps its native dialog and record actions; only overview mode is a drawer. */
html[data-theme] body.record-surface-ui #branchModalOverlay.is-record-drawer {
    inset:0 !important; margin:0 !important; padding:0 !important; width:100vw !important; max-width:100vw !important;
    height:100dvh !important; max-height:100dvh !important; align-items:stretch !important; justify-content:flex-end !important;
    border:0 !important; border-radius:0 !important; overflow:hidden !important; backdrop-filter:none !important;
}
html[data-theme] body.record-surface-ui #branchModalOverlay.is-record-drawer #branchModalSheet {
    width:min(var(--drawer-width),100vw) !important; max-width:100vw !important; height:100dvh !important; max-height:100dvh !important;
    border:0 !important; border-radius:0 !important; box-shadow:none !important; flex:none;
}
html[data-theme] body.record-surface-ui #branchModalOverlay.is-record-drawer #branchModalContent { display:flex; flex-direction:column; height:100%; max-height:100% !important; overflow:hidden !important; }
html[data-theme] body.record-surface-ui #branchModalOverlay.is-record-drawer :is(.branch-detail-header,.branch-detail-actions) { flex:0 0 auto; }
html[data-theme] body.record-surface-ui #branchModalOverlay.is-record-drawer .branch-detail-body { flex:1; min-height:0; overflow:auto; }
html[data-theme] body.record-surface-ui #branchModalOverlay.is-record-drawer .branch-overview-grid { grid-template-columns:minmax(0,1fr); }
/* Service details keep the iframe/loader and edit-return flow. */
html[data-theme] body.record-surface-ui .service-modal.is-detail { padding:0 !important; align-items:stretch; justify-content:flex-end; }
html[data-theme] body.record-surface-ui .service-modal.is-detail .service-modal__panel { width:min(var(--drawer-width),100vw) !important; height:100dvh !important; max-height:100dvh !important; border-radius:0 !important; border:0 !important; animation:none !important; }
html[data-theme] body.record-surface-ui .service-modal.is-detail .service-modal__frame { flex:1 1 0; min-height:0; height:100% !important; max-height:none; background:var(--drawer-surface); }
html[data-theme] body.service-catalog-modal-frame .svc-detail .svc-detail-card { border-radius:0 !important; }
html[data-theme] body.service-catalog-modal-frame .svc-detail .svc-detail-head { position:sticky; top:0; z-index:5; padding-right:68px !important; }
html[data-theme] body.service-catalog-modal-frame .svc-detail-actions { position:sticky; bottom:0; z-index:5; }
html[data-theme] body.service-catalog-modal-frame:not(.service-catalog-form-frame):has(.record-service-drawer-card),
html[data-theme] body.service-catalog-modal-frame:not(.service-catalog-form-frame):has(.record-service-drawer-card) .page-content { height:100dvh !important; overflow:hidden !important; }
html[data-theme] body.service-catalog-modal-frame .svc-detail:has(.record-service-drawer-card) { height:100dvh; max-width:none !important; padding:0 !important; margin:0 !important; }
html[data-theme] body.service-catalog-modal-frame .svc-detail .record-service-drawer-card { display:flex; flex-direction:column; height:100dvh; min-height:0; max-height:100dvh; }
html[data-theme] body.service-catalog-modal-frame .svc-detail .svc-detail-card.record-service-drawer-card > :is(.svc-detail-head,.svc-detail-actions) { flex:0 0 auto; position:static; margin:0; }
html[data-theme] body.service-catalog-modal-frame .record-service-drawer-body { flex:1; min-height:0; overflow:auto; overscroll-behavior:contain; }
@media(max-width:480px) { .record-summary-fields { grid-template-columns:minmax(0,1fr); } .record-drawer-footer > * { flex:1; } }
@media(prefers-reduced-motion:reduce) { .record-drawer { scroll-behavior:auto; } }
</style>
