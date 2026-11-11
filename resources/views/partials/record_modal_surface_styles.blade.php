@php
    $modalScope = 'html[data-theme] body:is(.record-surface-ui,.service-catalog-modal-frame,.service-catalog-form-frame)';
    $modalPanel = ':is(.user-information-dialog,.directory-feedback-dialog,.service-feedback-dialog,.svc-status-dialog,.service-modal__panel,.pm-modal,.payment-modal-sheet,#branchCreateModalSheet,#branchModalSheet,#adminCaseViewSheet,#caseEditSheet,.case-view-sheet,.svc-detail,.form-card,.cc-card,.package-form-card)';
    $feedbackScope = $modalScope.' :is(.directory-feedback-dialog,.service-feedback-dialog,.svc-status-dialog,#branchModalSheet.branch-feedback-mode)';
    $feedbackConfirm = ':is([data-confirm-submit],[data-promotion-confirm-submit],.directory-feedback-dialog__confirm,.branch-detail-danger,.branch-detail-primary,.svc-detail-primary)';
@endphp
<style>
{!! $modalScope !!} {
    --rs-surface:#F7FAF8; --rs-control:#EAF2ED; --rs-header:#D5E4D9; --rs-text:#123524;
    --rs-muted:#617568; --reference-action-bg:#A9C9B5; --reference-action-hover:#92B89F; --reference-action-text:#123524;
}
html[data-theme=dark] body:is(.record-surface-ui,.service-catalog-modal-frame,.service-catalog-form-frame) {
    --rs-surface:#15251C; --rs-control:#22392C; --rs-header:#1C3025; --rs-text:#E7F0E9; --rs-muted:#A7BCAE;
}
{!! $modalScope !!} {!! $modalPanel !!} {
    background-color:var(--rs-surface) !important; color:var(--rs-text) !important;
    border-color:transparent !important; box-shadow:none !important; text-shadow:none !important;
    transition-property:opacity,transform !important;
}
{!! $modalScope !!} {!! $modalPanel !!} * { box-shadow:none !important; text-shadow:none !important; }
{!! $modalScope !!} :is(.user-information-head,.service-modal__head,.pm-modal-hd,.payment-modal-head,.branch-detail-header,.branch-create-header,.svc-detail-head) {
    background-color:var(--rs-header) !important; border-color:transparent !important; color:var(--rs-text) !important;
}
{!! $modalScope !!} :is(.user-information-footer,.service-modal__body,.pm-modal-body,.payment-modal-body,.pm-modal-ft,.payment-modal-footer,.branch-detail-actions,.branch-detail-footer,.branch-create-footer,#branchModalContent,.case-view-content,.svc-detail-foot,.svc-detail-footer,.svc-detail-actions) {
    background-color:var(--rs-surface) !important; border-color:transparent !important;
}
{!! $modalScope !!} {!! $modalPanel !!} :is(.btn-outline,.btn-secondary,.user-information-close-action) {
    background-color:var(--rs-control) !important; color:var(--rs-text) !important; border-color:transparent !important;
}
{!! $modalScope !!} .svc-detail .svc-detail-actions > :is(button,a) {
    background-color:var(--rs-control) !important; border-color:transparent !important; box-shadow:none !important;
}
{!! $modalScope !!} :is(.user-information-access,.branch-overview-panel,.pm-summary-stat,.pm-modal-context,.svc-detail-card) {
    background-color:var(--rs-control) !important; border-color:transparent !important;
}
{!! $modalScope !!} {!! $modalPanel !!} :is(input:not([type=checkbox]):not([type=radio]):not([type=hidden]),select,textarea) {
    background-color:var(--rs-control) !important; border-color:transparent !important;
    color:var(--rs-text) !important; box-shadow:none !important;
}
{!! $modalScope !!} {!! $modalPanel !!} :is(input,select,textarea,button,a):focus-visible {
    outline:2px solid var(--filter-ui-focus,#1B4332) !important; outline-offset:2px;
}
{!! $modalScope !!} {!! $modalPanel !!} :is(.btn-primary-custom,.ops-btn-primary,.pm-btn.primary) {
    background-color:var(--reference-action-bg) !important; color:var(--reference-action-text) !important;
    border-color:transparent !important;
}
{!! $modalScope !!} {!! $modalPanel !!} :is(.btn-primary-custom,.ops-btn-primary,.pm-btn.primary):hover {
    background-color:var(--reference-action-hover) !important;
}
/* Feedback decisions keep their semantic colors above the ordinary sage action. */
{!! $feedbackScope !!} { --feedback-action-bg:#A9C9B5; --feedback-action-hover:#92B89F; --feedback-action-fg:#123524; }
{!! $feedbackScope !!}:is([data-confirm-tone=warning],[data-feedback-kind=warning]) { --feedback-action-bg:#A15C00; --feedback-action-hover:#854C00; --feedback-action-fg:#FFFFFF; }
{!! $feedbackScope !!}:is([data-confirm-tone=success],[data-feedback-kind=success]) { --feedback-action-bg:#167044; --feedback-action-hover:#105A36; --feedback-action-fg:#FFFFFF; }
{!! $feedbackScope !!}:is([data-confirm-tone=error],[data-feedback-kind=error]) { --feedback-action-bg:#B42318; --feedback-action-hover:#912018; --feedback-action-fg:#FFFFFF; }
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $feedbackScope) !!}:is([data-confirm-tone=warning],[data-feedback-kind=warning]) { --feedback-action-bg:#FDC76A; --feedback-action-hover:#E7AD45; --feedback-action-fg:#2B1B00; }
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $feedbackScope) !!}:is([data-confirm-tone=success],[data-feedback-kind=success]) { --feedback-action-bg:#52E6B0; --feedback-action-hover:#38C996; --feedback-action-fg:#071B12; }
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $feedbackScope) !!}:is([data-confirm-tone=error],[data-feedback-kind=error]) { --feedback-action-bg:#FDA29B; --feedback-action-hover:#F97066; --feedback-action-fg:#260603; }
{!! $feedbackScope !!} {!! $feedbackConfirm !!} {
    background-color:var(--feedback-action-bg) !important; color:var(--feedback-action-fg) !important;
    border-color:transparent !important; box-shadow:none !important;
}
{!! $feedbackScope !!} {!! $feedbackConfirm !!}:is(:hover,:focus-visible) { background-color:var(--feedback-action-hover) !important; }
{!! $feedbackScope !!} :is([data-confirm-cancel],[data-status-cancel],[data-branch-close],.user-information-close-action) {
    background-color:var(--rs-control) !important; color:var(--rs-text) !important; border-color:transparent !important;
}
{!! $feedbackScope !!} [data-keep] { background-color:var(--rs-control) !important; color:var(--rs-text) !important; border-color:transparent !important; }
{!! $feedbackScope !!} [data-discard] { background-color:#B42318 !important; color:#FFFFFF !important; border-color:transparent !important; }
{!! $feedbackScope !!} [data-discard]:hover { background-color:#912018 !important; }
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $feedbackScope) !!} [data-discard] { background-color:#FDA29B !important; color:#260603 !important; }
/* Branch uses the same compact, flat feedback composition as User/Service. */
{!! $modalScope !!} #branchModalSheet.branch-feedback-mode { box-sizing:border-box; padding:24px !important; width:min(440px,calc(100vw - 32px)); max-height:calc(100dvh - 32px); overflow-y:auto; }
{!! $modalScope !!} #branchModalSheet.branch-feedback-mode #branchModalContent { padding:0 !important; }
{!! $modalScope !!} #branchModalSheet.branch-feedback-mode .branch-detail-header { padding:0 !important; background-color:transparent !important; }
{!! $modalScope !!} #branchModalSheet.branch-feedback-mode .branch-detail-body { padding:0 !important; margin-top:14px; }
{!! $modalScope !!} #branchModalSheet.branch-feedback-mode .branch-detail-actions { padding:0 !important; margin-top:20px; gap:10px; }
{!! $modalScope !!} #branchModalSheet.branch-feedback-mode .branch-detail-notice { border:0 !important; background-color:var(--rs-control) !important; }
{!! $modalScope !!} #branchModalSheet.branch-feedback-mode .branch-detail-actions a { background-color:#175CD3 !important; color:#FFFFFF !important; border-color:transparent !important; }
{!! $modalScope !!} #branchModalSheet.branch-feedback-mode .branch-detail-actions a:hover { background-color:#124BAE !important; }
{!! str_replace('html[data-theme]', 'html[data-theme=dark]', $modalScope) !!} #branchModalSheet.branch-feedback-mode .branch-detail-actions a { background-color:#84B8FF !important; color:#0B1B30 !important; }
@media(max-width:480px) {
    {!! $modalScope !!} #branchModalSheet.branch-feedback-mode { padding:20px 18px !important; }
    {!! $modalScope !!} #branchModalSheet.branch-feedback-mode .branch-detail-actions { flex-direction:column-reverse; }
    {!! $modalScope !!} #branchModalSheet.branch-feedback-mode .branch-detail-actions-group,
    {!! $modalScope !!} #branchModalSheet.branch-feedback-mode .branch-detail-actions :is(form,button,a) { width:100%; }
}
</style>
@include('partials.record_detail_modal_styles')
@include('partials.record_drawer_styles')
@include('partials.record_detail_sections')
@include('partials.service_branch_form_styles')
