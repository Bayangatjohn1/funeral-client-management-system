<dialog class="service-confirm-dialog service-feedback-dialog" data-service-confirm aria-labelledby="service-confirm-title" aria-describedby="service-confirm-copy">
    <span class="service-confirm-dialog__icon" aria-hidden="true"><i class="bi bi-exclamation-circle"></i></span>
    <h2 id="service-confirm-title">Confirm catalog action</h2>
    <p id="service-confirm-copy" data-confirm-copy></p>
    <footer><button type="button" class="btn btn-outline" data-confirm-cancel>Cancel</button><button type="button" class="btn btn-primary-custom" data-confirm-submit>Confirm</button></footer>
</dialog>
<style>
/* Modal and action feedback; hub layout is owned by services_visual_styles. */
html[data-theme] .service-action-tooltip { position:fixed; z-index:1100; max-width:min(240px,calc(100vw - 24px)); padding:6px 10px; border:1px solid var(--border); border-radius:7px; background:var(--ink); color:var(--card); box-shadow:0 6px 18px rgb(8 29 19 / 16%); font:500 12px/1.35 'Plus Jakarta Sans',sans-serif; text-align:center; pointer-events:none; visibility:hidden; }
.service-confirm-dialog { width:min(400px, calc(100vw - 32px)); padding:18px; border:1px solid var(--color-border); border-radius:14px; background:var(--color-bg-surface); color:var(--color-text-primary); text-align:center; }
.service-confirm-dialog::backdrop { background:rgb(0 0 0 / .5); }
.service-confirm-dialog__icon { display:grid; place-items:center; width:40px; height:40px; margin:0 auto 8px; border-radius:11px; background:var(--surface-muted); color:var(--ink); font-size:17px; }
.service-confirm-dialog[data-feedback-kind='warning'] .service-confirm-dialog__icon { background:var(--feedback-warning-bg); color:var(--feedback-warning-fg); }
.service-confirm-dialog[data-feedback-kind='success'] .service-confirm-dialog__icon { background:var(--feedback-success-bg); color:var(--feedback-success-fg); }
.service-confirm-dialog[data-feedback-kind='error'] .service-confirm-dialog__icon { background:var(--feedback-error-bg); color:var(--feedback-error-fg); }
.service-confirm-dialog h2 { font-size:19px; margin:0 0 5px; font-weight:700; }
.service-confirm-dialog p { margin:0; font-size:13px; line-height:1.5; color:var(--color-text-secondary); }
.service-confirm-dialog footer { display:flex; flex-wrap:wrap; justify-content:center; gap:8px; margin-top:14px; }
@media(max-width:480px) { .service-confirm-dialog footer { flex-direction:column-reverse; } .service-confirm-dialog footer button { width:100%; } }
html[data-theme] body.panel-shell-body .service-modal .service-modal__panel { width:min(896px,calc(100vw - 32px)); height:auto; max-height:calc(100dvh - 32px); border-radius:12px; border:1px solid #AEC7B7; background:#E8F1EC; box-shadow:0 24px 70px rgb(8 29 19 / 22%); }
/* Detail geometry is owned by partials/record_drawer_styles; forms keep the centered shell. */
html[data-theme] body.panel-shell-body .service-modal.is-detail .service-modal__head { position:absolute; width:1px; height:1px; padding:0; overflow:hidden; clip-path:inset(50%); white-space:nowrap; border:0; }
html[data-theme] body.panel-shell-body .service-modal.is-detail .service-modal__floating-close { top:20px; right:20px; background:rgb(255 255 255 / 65%); border-color:transparent; color:#1B3E2E; }
html[data-theme] body.panel-shell-body .service-modal.is-form:not(.is-package-form) .service-modal__panel { width:min(var(--service-modal-width,576px),calc(100vw - 32px)); height:auto; }
html[data-theme] body.panel-shell-body .service-modal.is-package-form .service-modal__panel { height:auto; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__head { justify-content:flex-start; padding:20px 76px 20px 24px; background:rgb(255 255 255 / 40%); border-bottom:1px solid rgb(174 199 183 / 60%); }
html[data-theme] body.panel-shell-body .service-modal .service-modal__icon { display:grid; place-items:center; flex:0 0 40px; width:40px; height:40px; border-radius:14px; background:#2D6A4F; border:0; color:#fff; font-size:20px; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__floating-close { width:44px; height:44px; border-radius:12px; box-shadow:none; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__head h2 { font-size:20px; font-weight:700; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__head p { font-size:12px; font-weight:500; color:#1B3E2E; }
html[data-theme] body.panel-shell-body .service-modal .service-modal__frame { flex:0 1 auto; height:var(--service-frame-height,240px); min-height:0; max-height:calc(100dvh - 32px - var(--service-header-height,0px)); background:#E8F1EC; }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__panel { background:#0F241C; border-color:#1B4332; }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__head { background:rgb(0 0 0 / 10%); border-color:rgb(27 67 50 / 60%); }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__icon { background:#2D6A4F; color:#fff; }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__head p { color:#94A3B8; }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__floating-close { color:#E2E8F0; background:rgb(255 255 255 / 5%); }
html[data-theme='dark'] body.panel-shell-body .service-modal .service-modal__frame { background:#0F241C; }
html[data-theme] body.panel-shell-body .service-modal.is-detail .service-modal__floating-close { background:transparent; border:0; box-shadow:none; color:var(--ink-muted); }
</style>


<style>
html[data-theme] body.panel-shell-body .service-modal.is-package-form .service-modal__panel { width:min(760px,calc(100vw - 32px)); max-height:min(680px,calc(100dvh - 32px)); }
html[data-theme] body.panel-shell-body .service-modal.is-detail:has(iframe[src*="/packages/"]) .service-modal__panel { max-height:min(680px,calc(100dvh - 32px)); }
</style>
<style>[data-confirm-affected]:not(:empty) { margin-top:14px; padding-top:12px; border-top:1px solid var(--border); }[data-confirm-affected] p { font-weight:600; margin:0 0 6px; }[data-confirm-affected] ul { margin:0; padding-left:18px; font-size:13px; line-height:1.7; }</style>
<style>
html[data-theme] body.panel-shell-body .service-modal :is(.service-modal__floating-close,.service-modal__close) { width:44px; height:44px; display:grid; place-items:center; border:1px solid var(--border); border-radius:10px; background:var(--surface-muted); color:var(--ink); box-shadow:none; font-size:16px; }
html[data-theme] body.panel-shell-body .service-modal.is-detail .service-modal__floating-close { background:var(--surface-muted); border:1px solid var(--border); color:var(--ink); }
html[data-theme] body.panel-shell-body .service-modal :is(.service-modal__floating-close,.service-modal__close):hover { background:var(--card); }
</style>
