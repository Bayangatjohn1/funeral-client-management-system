@php
    $detailScope = 'html[data-theme] body.record-surface-ui';
    $userDetail = $detailScope.' .user-information-dialog:not(.directory-feedback-dialog)';
    $branchDetail = $detailScope.' #branchModalSheet:not(.branch-edit-mode):not(.branch-feedback-mode)';
    $serviceDetail = 'html[data-theme] body.service-catalog-modal-frame .svc-detail';
@endphp
<style>
{!! $detailScope !!}, html[data-theme] body.service-catalog-modal-frame {
    --detail-divider:#B8CEC0; --detail-edit:#175CD3; --detail-edit-hover:#124BAE; --detail-edit-text:#FFFFFF;
}
html[data-theme=dark] body:is(.record-surface-ui,.service-catalog-modal-frame) {
    --detail-divider:#355241; --detail-edit:#84B8FF; --detail-edit-hover:#B6D2FF; --detail-edit-text:#0B1B30;
}
{!! $userDetail !!}, {!! $branchDetail !!}, {!! $serviceDetail !!} .svc-detail-card {
    border:0 !important; border-radius:16px !important; box-shadow:none !important; background-color:var(--rs-surface) !important;
}
{!! $detailScope !!} .service-modal.is-detail .service-modal__panel { border:0 !important; border-radius:16px !important; box-shadow:none !important; }
{!! $detailScope !!} .service-modal.is-detail .service-modal__floating-close { background-color:var(--rs-control) !important; color:var(--rs-text) !important; border:0 !important; box-shadow:none !important; }
{!! $userDetail !!} .user-information-head,
{!! $branchDetail !!} .branch-detail-header,
{!! $serviceDetail !!} .svc-detail-head {
    box-sizing:border-box; padding:20px !important; gap:12px !important;
    background-color:var(--rs-header) !important; border:0 !important;
    border-bottom:1px solid var(--detail-divider) !important;
}
{!! $userDetail !!} .user-information-heading-icon,
{!! $branchDetail !!} .branch-detail-header__icon,
{!! $serviceDetail !!} .svc-detail-heading-icon {
    display:grid; place-items:center; flex:0 0 40px; width:40px; height:40px; border:0 !important;
    border-radius:10px; background-color:var(--rs-control) !important; color:var(--rs-text) !important; font-size:18px;
}
{!! $userDetail !!} .user-information-head h2,
{!! $branchDetail !!} .branch-detail-header h2,
{!! $serviceDetail !!} .svc-detail-modal-title { margin:0 !important; font-size:20px !important; line-height:1.35; font-weight:650; color:var(--rs-text) !important; }
{!! $userDetail !!} .user-information-head p,
{!! $branchDetail !!} .branch-detail-header p,
{!! $serviceDetail !!} .svc-detail-record { margin:4px 0 0 !important; color:var(--rs-muted) !important; font-size:13px; overflow-wrap:anywhere; }
{!! $userDetail !!} .user-information-access { margin:20px 20px 0 !important; padding:16px !important; gap:16px; border:0 !important; }
{!! $userDetail !!} .user-information-fields { margin:0 !important; padding:4px 20px 20px !important; column-gap:20px; }
{!! $userDetail !!} .user-information-fields > div { padding:16px 0 !important; border:0 !important; border-bottom:1px solid var(--detail-divider) !important; }
{!! $userDetail !!} .user-information-fields dd { margin:0; color:var(--rs-text); }
{!! $branchDetail !!} #branchModalContent { padding:0 !important; }
{!! $branchDetail !!} .branch-detail-body { padding:20px !important; }
{!! $branchDetail !!} .branch-overview-grid { gap:16px !important; }
{!! $branchDetail !!} .branch-overview-panel { padding:16px !important; border:0 !important; border-radius:10px; }
{!! $branchDetail !!} .branch-overview-panel h3 { margin:0 0 8px; color:var(--rs-text); }
{!! $serviceDetail !!} .svc-detail-card { background-color:var(--rs-surface) !important; }
{!! $serviceDetail !!} .svc-detail-identity { padding:20px !important; background-color:var(--rs-surface) !important; border:0 !important; gap:16px; }
{!! $serviceDetail !!} .svc-detail-section { margin:0 20px !important; padding:16px 0 !important; background-color:var(--rs-surface) !important; border-color:var(--detail-divider) !important; }
{!! $serviceDetail !!} .svc-detail-stat-grid { padding:0 20px 16px !important; gap:16px !important; background-color:var(--rs-surface) !important; border:0 !important; }
{!! $serviceDetail !!} :is(.svc-detail-stat,.svc-detail-list-grid > *) { padding:16px !important; background-color:var(--rs-control) !important; border:0 !important; border-radius:10px; }
{!! $serviceDetail !!} table { background-color:transparent !important; box-shadow:none !important; }
{!! $serviceDetail !!} table :is(th,td) { border-color:var(--detail-divider) !important; box-shadow:none !important; }
{!! $serviceDetail !!} table th { background-color:var(--rs-control) !important; color:var(--rs-text) !important; }
{!! $userDetail !!} .user-information-footer,
{!! $branchDetail !!} .branch-detail-actions,
{!! $serviceDetail !!} .svc-detail-actions {
    box-sizing:border-box; padding:16px 20px !important; gap:12px !important;
    border:0 !important; border-top:1px solid var(--detail-divider) !important; background-color:var(--rs-surface) !important;
}
{!! $userDetail !!} :is([data-user-dialog-close],.user-information-close-action),
{!! $branchDetail !!} [data-branch-close],
{!! $serviceDetail !!} [data-parent-modal-close] {
    min-height:44px; border:0 !important; border-radius:8px; background-color:var(--rs-control) !important; color:var(--rs-text) !important;
}
html[data-theme] body.service-catalog-modal-frame .page-content { padding:0 !important; background-color:var(--rs-surface) !important; }
@media(max-width:600px) {
    {!! $userDetail !!} .user-information-fields { grid-template-columns:minmax(0,1fr); }
    {!! $branchDetail !!} .branch-overview-grid { grid-template-columns:minmax(0,1fr); }
}
</style>
