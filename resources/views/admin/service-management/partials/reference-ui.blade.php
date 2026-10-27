<dialog class="service-confirm-dialog" data-service-confirm aria-labelledby="service-confirm-title">
    <h2 id="service-confirm-title">Confirm catalog action</h2>
    <p data-confirm-copy></p>
    <footer><button type="button" class="btn btn-outline" data-confirm-cancel>Cancel</button><button type="button" class="btn btn-primary-custom" data-confirm-submit>Confirm</button></footer>
</dialog>
<style>
html[data-theme] body.panel-shell-body .service-reference .service-reference-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:20px; padding:24px; border:1px solid var(--color-border); border-radius:16px; background:var(--color-bg-surface); }
.service-reference-heading { display:flex; align-items:center; gap:14px; }
.service-reference-heading-icon { display:grid; place-items:center; flex:0 0 48px; width:48px; height:48px; border-radius:13px; background:var(--card); color:var(--ink-muted); font-size:24px; }
.service-reference-title { display:flex; align-items:center; flex-wrap:wrap; gap:12px; }
.service-reference-title h1 { margin:0; font-size:clamp(24px, 3vw, 30px); font-weight:700; line-height:1.25; }
.service-reference-title > span { padding:4px 10px; border:1px solid var(--color-border); border-radius:20px; font-size:11px; font-weight:600; }
.service-reference-heading p { margin:6px 0 0; color:var(--color-text-secondary); font-size:14px; }
.service-reference-readonly { flex-basis:100%; padding:12px; margin:0; border:1px solid var(--color-border); border-radius:10px; font-size:13px; color:var(--color-text-secondary); }
html[data-theme] body.panel-shell-body .service-reference .service-hub-nav { padding:6px; border:1px solid var(--color-border); border-radius:14px; background:var(--color-bg-surface); margin-bottom:16px; }
html[data-theme] body.panel-shell-body .service-reference .service-hub-tabs { display:flex; gap:6px; width:100%; }
html[data-theme] body.panel-shell-body .service-reference .service-hub-tab { flex:1; justify-content:center; min-height:44px; border-radius:10px; border:0; padding:10px 14px; }
html[data-theme] body.panel-shell-body .service-reference .service-hub-tab.is-active { color:var(--color-text-on-primary, #fff); background:var(--color-primary); }
html[data-theme] body.panel-shell-body .service-reference .service-package-card { border-radius:16px; border:1px solid var(--color-border); box-shadow:0 4px 14px rgb(0 0 0 / .04); }
html[data-theme] body.panel-shell-body .service-reference .service-package-card__head { padding-bottom:12px; border-bottom:1px solid var(--color-border); }
html[data-theme] body.panel-shell-body .service-reference .service-package-card__price { margin:14px 0; padding:12px; border-radius:10px; background:var(--color-bg-muted); }
html[data-theme] body.panel-shell-body .service-reference .service-package-card__foot { border-top:1px solid var(--color-border); padding-top:12px; }
html[data-theme] body.panel-shell-body .service-reference .service-hub-toolbar { border:1px solid var(--color-border); border-radius:14px; background:var(--color-bg-surface); padding:16px; }
/* Page-level reference: one tab bar, one toolbar, calm catalog cards. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-reference-header:not(.panel-title-surface) { background:var(--surface-muted); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-reference-title > span { background:#1B4332; border-color:#1B4332; color:#fff; text-transform:uppercase; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-nav { padding:6px !important; margin-bottom:20px; background:var(--card) !important; border:1px solid var(--border) !important; border-radius:16px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tabs { gap:6px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab { min-height:48px; background:transparent !important; border:0 !important; box-shadow:none !important; color:var(--ink) !important; font-weight:700; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab:hover { background:#E5F0E8 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab:focus-visible { background:#E5F0E8 !important; outline:2px solid var(--brand) !important; outline-offset:-2px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab.is-active { background:#D0E2D6 !important; color:var(--ink) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab.is-active:hover { background:#C2DAC9 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab.is-active::after { display:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab b { border:0 !important; background:var(--surface-muted) !important; color:var(--ink) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab.is-active b { background:var(--card) !important; color:var(--ink) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-toolbar { display:flex; align-items:center; gap:12px; padding:20px !important; margin-bottom:28px; background:var(--surface-muted) !important; border-radius:16px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-filter { flex:1; min-width:0; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap) { background:var(--card) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-viewbar { flex:0 0 auto; padding:0; justify-content:flex-end; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-view-toggle { min-height:44px; padding:3px; overflow:visible; border-radius:10px; background:var(--surface-muted) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-view-option { position:relative; display:grid; place-items:center; width:44px; min-width:44px; height:44px; min-height:44px; padding:0; border-radius:7px; color:var(--ink-muted); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-view-option.is-active { background:var(--card) !important; color:var(--ink) !important; box-shadow:0 1px 3px rgb(8 29 19 / 12%); }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-view-option::after { content:attr(aria-label); position:absolute; top:calc(100% + 8px); right:0; z-index:40; width:max-content; padding:5px 9px; border-radius:6px; background:var(--ink); color:var(--card); font-size:12px; line-height:1.4; pointer-events:none; opacity:0; visibility:hidden; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-view-option:is(:hover,:focus-visible)::after { opacity:1; visibility:visible; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-view-option:focus-visible { outline:2px solid var(--brand) !important; outline-offset:-3px !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card { background:var(--card) !important; border-radius:16px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__head { border-bottom:0 !important; padding-bottom:4px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__head h3 { font-size:1.06rem; line-height:1.4; font-weight:650; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__head p { font-size:.84rem; line-height:1.5; font-weight:500; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__price { margin:0; padding-top:8px; padding-bottom:8px; border:0 !important; background:transparent !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__price span { font-size:.72rem; font-weight:600; letter-spacing:.03em; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__price strong { font-size:1.35rem; line-height:1.35; font-weight:700; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__price small { font-size:.8rem; line-height:1.5; font-weight:500; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__lists { margin:8px 16px 16px; padding:12px !important; border:1px solid var(--border) !important; border-radius:12px; background:var(--surface-muted) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__lists h4 { font-size:.72rem; font-weight:650; letter-spacing:.02em; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__lists :is(p,li) { font-size:.84rem; line-height:1.55; font-weight:450; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__lists :is(b,em) { font-size:.82rem; line-height:1.5; font-style:normal; font-weight:500; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card .service-status { font-weight:600; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__foot { justify-content:flex-end; margin:0 16px; padding:12px 0 16px !important; border-top:1px solid var(--border) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__foot [data-service-modal-title^="View"] { width:auto; min-width:0; min-height:40px; padding:0 14px; border:1px solid #AEC7B7; border-radius:10px; background:#D0E2D6 !important; color:#1B4332 !important; font-size:13px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__foot [data-service-modal-title^="View"]:hover { background:#C2DAC9 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__foot [data-service-modal-title^="View"]:focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__foot [data-service-modal-title^="View"] > span { display:inline; margin-left:6px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .row-hover-actions { opacity:1; pointer-events:auto; transform:none; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .row-hover-action { position:relative; width:40px; min-width:40px; min-height:40px; height:40px; padding:0; border-radius:9px; font-size:17px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .row-hover-action i { font-size:17px; }
html[data-theme] .service-action-tooltip { position:fixed; z-index:1100; max-width:min(240px,calc(100vw - 24px)); padding:6px 10px; border:1px solid var(--border); border-radius:7px; background:var(--ink); color:var(--card); box-shadow:0 6px 18px rgb(8 29 19 / 16%); font:500 12px/1.35 'Plus Jakarta Sans',sans-serif; text-align:center; pointer-events:none; visibility:hidden; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-reference-title > span { background:#1B4332 !important; color:#fff !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__foot [data-service-modal-title^="View"] { background:#1B3527 !important; border-color:#41634F; color:#E8F5ED !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__foot [data-service-modal-title^="View"]:hover { background:#254632 !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab.is-active { background:#1B3527 !important; color:#E8F5ED !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab:hover { background:#173024 !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab.is-active:hover { background:#254632 !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-tab:focus-visible { background:#173024 !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-reference-heading-icon { background:#1B3527; color:#B5D6C4; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-view-option.is-active { background:#1B3527 !important; color:#E8F5ED !important; }
html[data-theme='dark'] body.panel-shell-body:has(.service-reference) .page-content { background:#07130E !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference :is(.service-reference-header,.service-hub-nav,.service-hub-toolbar,.service-package-card) { background:#112218 !important; border-color:#29523D !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-reference-header:not(.panel-title-surface) { background:#14261D !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference :is(.service-hub-tab:not(.is-active),.service-view-option:not(.is-active),.service-reference-heading p,.service-package-card__head p,.service-package-card__price span,.service-package-card__price small,.service-package-card__lists li,.service-package-card__lists em,.service-package-card__foot > span) { color:#CBD5D1 !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference :is(.service-hub-tab b,.service-package-card__lists,.service-view-toggle) { background:#14261D !important; border-color:#41634F !important; color:#F8FAFC !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap,.table-toolbar-reset-wrap .btn) { background:#14261D !important; border-color:#41634F !important; color:#F8FAFC !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap) > :is(input,select) { color:#F8FAFC !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference input::placeholder { color:#CBD5D1 !important; opacity:1; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference :is(.table-toolbar-leading-icon,.table-toolbar-select-icon,.service-view-option:not(.is-active) i) { color:#CBD5D1 !important; }
html[data-theme='dark'] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-reference-header > button { background:#1B4332 !important; color:#fff !important; }
.service-confirm-dialog { width:min(440px, calc(100vw - 32px)); padding:24px; border:1px solid var(--color-border); border-radius:16px; background:var(--color-bg-surface); color:var(--color-text-primary); }
/* Source-reference catalog card: clear title, price, description, inset details, then one action. */
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-cards { gap:22px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card { display:flex; flex-direction:column; min-height:300px; padding:20px !important; background:var(--card) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__head { gap:16px; padding:0 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__head h3 { font-size:1.08rem; font-weight:700; line-height:1.4; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__head p { margin-top:3px; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__price { padding:14px 0 8px !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__price strong { font-size:1.5rem; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__description { margin:4px 0 14px; color:var(--ink-muted); font-size:.86rem; line-height:1.55; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__lists { margin:8px 0 0 !important; padding:14px !important; background:var(--surface-muted) !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__foot { margin:auto 0 0 !important; padding:14px 0 0 !important; }
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-package-card__foot [data-service-modal-title^="View"] { min-width:104px; }
.service-confirm-dialog::backdrop { background:rgb(0 0 0 / .5); }
.service-confirm-dialog h2 { font-size:22px; margin:0 0 12px; font-weight:700; }
.service-confirm-dialog p { font-size:14px; line-height:1.6; color:var(--color-text-secondary); }
.service-confirm-dialog footer { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__panel { width:min(896px,calc(100vw - 32px)); height:auto; max-height:calc(100dvh - 32px); border-radius:24px; border:1px solid #AEC7B7; background:#E8F1EC; box-shadow:0 24px 70px rgb(8 29 19 / 22%); }
html[data-theme] body.panel-shell-body .service-modal.is-detail .service-modal__panel { width:min(672px,calc(100vw - 32px)); height:auto; }
html[data-theme] body.panel-shell-body .service-modal.is-detail .service-modal__head { position:absolute; width:1px; height:1px; padding:0; overflow:hidden; clip-path:inset(50%); white-space:nowrap; border:0; }
html[data-theme] body.panel-shell-body .service-modal.is-detail .service-modal__floating-close { top:20px; right:20px; background:rgb(255 255 255 / 65%); border-color:transparent; color:#1B3E2E; }
html[data-theme] body.panel-shell-body .service-modal.is-form:not(.is-package-form) .service-modal__panel { width:min(576px,calc(100vw - 32px)); height:auto; }
html[data-theme] body.panel-shell-body .service-modal.is-package-form .service-modal__panel { height:auto; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__head { justify-content:flex-start; padding:20px 76px 20px 24px; background:rgb(255 255 255 / 40%); border-bottom:1px solid rgb(174 199 183 / 60%); }
html[data-theme] body.panel-shell-body .service-modal .service-modal__icon { display:grid; place-items:center; flex:0 0 40px; width:40px; height:40px; border-radius:14px; background:#2D6A4F; border:0; color:#fff; font-size:20px; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__floating-close { width:44px; height:44px; border-radius:12px; box-shadow:none; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__head h2 { font-size:20px; font-weight:800; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__head p { font-size:12px; font-weight:500; color:#1B3E2E; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__frame { flex:0 1 auto; height:var(--service-frame-height,240px); min-height:0; max-height:calc(100dvh - 32px - var(--service-header-height,0px)); background:#E8F1EC; }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__panel { background:#0F241C; border-color:#1B4332; }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__head { background:rgb(0 0 0 / 10%); border-color:rgb(27 67 50 / 60%); }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__icon { background:#2D6A4F; color:#fff; }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__head p { color:#94A3B8; }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__floating-close { color:#E2E8F0; background:rgb(255 255 255 / 5%); }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__frame { background:#0F241C; }
@media(max-width:600px) {
    html[data-theme] body.panel-shell-body .service-reference .service-reference-header { padding:18px; }
    html[data-theme] body.panel-shell-body .service-reference .service-hub-tabs { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); }
    .service-reference-header > button { width:100%; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-toolbar { align-items:stretch; flex-direction:column; padding:14px !important; }
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-viewbar { justify-content:flex-end; }
}
/* Keep the catalog toolbar consistent with the other admin worklists. */
html:not([data-theme='dark']) body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-toolbar {
    background:#fff !important;
    border-color:#dae5df !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-filter {
    display:grid !important;
    grid-template-columns:minmax(280px,34rem) minmax(0,1fr) !important;
    column-gap:clamp(28px,4vw,64px) !important;
    row-gap:10px !important;
    width:100%;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui):has(.service-reference) .page-content {
    min-width:0;
    scrollbar-gutter:stable;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-filter-actions {
    display:grid !important;
    grid-template-columns:minmax(145px,1fr) minmax(155px,1fr) auto !important;
    gap:10px !important;
    align-items:center;
}
html:not([data-theme='dark']) body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference :is(.table-toolbar-input-wrap,.table-toolbar-select-wrap,.table-toolbar-reset-wrap .btn) {
    background:var(--surface-muted) !important;
    border-color:var(--border) !important;
    color:var(--ink) !important;
}
html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .table-toolbar-reset-wrap .btn {
    min-height:44px;
    padding-inline:14px;
    border:1px solid var(--border);
    border-radius:8px;
    box-shadow:none !important;
}
@media(max-width:1100px) {
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-reference .service-hub-filter {
        grid-template-columns:minmax(0,1fr) !important;
        column-gap:0 !important;
    }
}
</style>
<script>
(() => {
    if (window.serviceActionTooltipReady) return;
    window.serviceActionTooltipReady = true;
    const selector = '.service-reference .row-hover-action[aria-label], .service-reference .service-package-card__foot button[aria-label]';
    const tooltip = document.createElement('div');
    tooltip.className = 'service-action-tooltip';
    tooltip.setAttribute('aria-hidden', 'true');
    document.body.appendChild(tooltip);
    let active = null;
    const hide = () => {
        active = null;
        tooltip.style.visibility = 'hidden';
    };
    const show = target => {
        if (!target || !target.isConnected) return;
        active = target;
        tooltip.textContent = target.getAttribute('aria-label') || '';
        tooltip.style.visibility = 'visible';
        const rect = target.getBoundingClientRect();
        const width = tooltip.offsetWidth;
        const height = tooltip.offsetHeight;
        tooltip.style.left = `${Math.max(12, Math.min(innerWidth - width - 12, rect.left + rect.width / 2 - width / 2))}px`;
        tooltip.style.top = `${rect.top >= height + 12 ? rect.top - height - 8 : rect.bottom + 8}px`;
    };
    document.addEventListener('pointerover', event => {
        const target = event.target.closest?.(selector);
        if (target && target !== active) show(target);
    });
    document.addEventListener('pointerout', event => {
        if (active && event.target.closest?.(selector) === active && !active.contains(event.relatedTarget)) hide();
    });
    document.addEventListener('focusin', event => {
        const target = event.target.closest?.(selector);
        if (target) show(target);
    });
    document.addEventListener('focusout', event => { if (active === event.target) hide(); });
    document.addEventListener('pointerdown', hide);
    document.addEventListener('scroll', hide, true);
    window.addEventListener('resize', hide);
})();
</script>
