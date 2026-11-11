@php
    $branchReferenceForm = 'html[data-theme] body.service-catalog-form-frame:is(.branch-admin-ui,.system-admin-ui) .page-content :is(.cc-form,.aoc-form,.fbc-form,.promotion-form)';
    $branchReferenceShell = 'html[data-theme] body.panel-shell-body.record-surface-ui .service-modal.is-form:not(.is-package-form)';
@endphp
<style>
/* Branch Add/Edit is the source reference. Only non-package Service modals adopt it. */
{!! $branchReferenceShell !!} .service-modal__panel { width:min(640px,calc(100vw - 32px)) !important; max-height:calc(100dvh - 32px) !important; border:0 !important; border-radius:16px !important; box-shadow:none !important; }
{!! $branchReferenceShell !!} .service-modal__head { align-items:flex-start; gap:12px; padding:20px 76px 20px 24px !important; background:var(--rs-header) !important; border-bottom:1px solid var(--detail-divider) !important; }
{!! $branchReferenceShell !!} .service-modal__head h2 { font-size:1.15rem !important; font-weight:700; line-height:1.3; }
{!! $branchReferenceShell !!} .service-modal__head p { margin:4px 0 0; font-size:12.8px; font-weight:400; line-height:1.45; color:var(--rs-muted) !important; }
{!! $branchReferenceShell !!} .service-modal__icon { width:40px; height:40px; flex:0 0 40px; border-radius:11px; background:#1B4332 !important; color:#FFFFFF !important; font-size:18px; }
{!! $branchReferenceShell !!} .service-modal__floating-close { top:20px; right:24px; width:40px; height:40px; border-radius:10px; }
{!! $branchReferenceForm !!} { margin:0; padding:0; max-width:none; color:var(--rs-text); font:400 14px/1.5 var(--font-body); }
{!! $branchReferenceForm !!} :is(.cc-card,.aoc-card,.fbc-card,.promotion-form__card) { border:0 !important; border-radius:0 !important; background:var(--rs-surface) !important; box-shadow:none !important; }
{!! $branchReferenceForm !!} :is(.cc-body,.aoc-body,.fbc-body,.promotion-form__body) { gap:16px !important; padding:24px !important; background:var(--rs-surface) !important; }
{!! $branchReferenceForm !!} :is(.cc-grid,.aoc-grid,.fbc-grid) { gap:16px !important; }
{!! $branchReferenceForm !!} :is(.cc-label,.aoc-label,.fbc-label,label:not(.cc-availability-label)) { margin-bottom:7px; color:var(--rs-text) !important; font-size:12px !important; font-weight:680 !important; line-height:1.5; text-transform:none; letter-spacing:0; }
{!! $branchReferenceForm !!} :is(.form-input,.form-select,textarea,input:not([type=checkbox]):not([type=radio]):not([type=hidden]),select) { min-height:45.6px; padding:10.4px 13.6px; border:1px solid transparent !important; border-radius:10.4px; background:var(--rs-control) !important; color:var(--rs-text) !important; font-size:14px; line-height:1.5; font-weight:500; box-shadow:none !important; }
{!! $branchReferenceForm !!} :is(textarea) { min-height:80px; }
{!! $branchReferenceForm !!} :is(.cc-money,.aoc-money,.promotion-form__value-wrap) { min-height:45.6px; border:0 !important; border-radius:10.4px; background:var(--rs-control) !important; box-shadow:none !important; }
{!! $branchReferenceForm !!} :is(.cc-money,.aoc-money,.promotion-form__value-wrap) input { border:0 !important; background:transparent !important; }
{!! $branchReferenceForm !!} :is(.cc-help,.aoc-note,.fbc-note) { font-size:11.5px; font-weight:400; line-height:1.45; color:var(--rs-muted) !important; }
{!! $branchReferenceForm !!} :is(.cc-actions,.aoc-actions,.fbc-actions,.promotion-form__foot) { display:flex; align-items:center; justify-content:flex-end; flex-wrap:wrap; gap:10px; padding:16px 24px !important; border:0 !important; border-top:1px solid var(--detail-divider) !important; background:var(--rs-surface) !important; }
{!! $branchReferenceForm !!} :is(.cc-primary,.aoc-primary,.fbc-primary,.cc-secondary,.aoc-secondary,.fbc-secondary,.promotion-form__foot a,.promotion-form__foot button) { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-width:104px; min-height:44px; padding:10px 16px; border:0 !important; border-radius:8.8px; font-size:13px; font-weight:600 !important; box-shadow:none !important; }
{!! $branchReferenceForm !!} :is(.cc-primary,.aoc-primary,.fbc-primary,.promotion-form__foot button) { background:var(--reference-action-bg,#A9C9B5) !important; color:var(--reference-action-text,#123524) !important; }
{!! $branchReferenceForm !!} :is(.cc-primary,.aoc-primary,.fbc-primary,.promotion-form__foot button):hover { background:var(--reference-action-hover,#92B89F) !important; }
{!! $branchReferenceForm !!} :is(.cc-secondary,.aoc-secondary,.fbc-secondary,.promotion-form__foot a) { background:var(--rs-control) !important; color:var(--rs-text) !important; }
{!! $branchReferenceForm !!} :is(.cc-secondary,.aoc-secondary,.fbc-secondary,.promotion-form__foot a):hover { background:var(--rs-item-hover,#E1EEE6) !important; }
{!! $branchReferenceForm !!} :is(input,select,textarea,button,a):focus-visible { outline:2px solid var(--filter-ui-focus,var(--brand)) !important; outline-offset:2px; }
{!! $branchReferenceForm !!} :is(input,select,textarea)[aria-invalid=true],
{!! $branchReferenceForm !!} :is(.cc-money,.aoc-money,.promotion-form__value-wrap):has([aria-invalid=true]) { border:1px solid #B42318 !important; }
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $branchReferenceForm) !!} :is(input,select,textarea)[aria-invalid=true],
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $branchReferenceForm) !!} :is(.cc-money,.aoc-money,.promotion-form__value-wrap):has([aria-invalid=true]) { border-color:#FDA29B !important; }
@media(max-width:600px) {
    {!! $branchReferenceForm !!} :is(.cc-grid,.aoc-grid,.fbc-grid,.promotion-form__body) { grid-template-columns:minmax(0,1fr); }
    {!! $branchReferenceForm !!} :is(.cc-body,.aoc-body,.fbc-body,.promotion-form__body,.cc-actions,.aoc-actions,.fbc-actions,.promotion-form__foot) { padding-inline:18px !important; }
}
</style>
