@php
    $sectionUser = 'html[data-theme] body.record-surface-ui .user-information-dialog.record-drawer:not(.directory-feedback-dialog)';
    $sectionBranch = 'html[data-theme] body.record-surface-ui #branchModalOverlay.is-record-drawer #branchModalSheet #branchModalContent';
    $sectionService = 'html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail .record-service-drawer-body';
    $sectionPayment = 'html[data-theme] body.panel-shell-body.record-surface-ui .pm-transactions-backdrop .pm-modal.pm-transactions-modal';
@endphp
<style>
/* Detail sections share content insets and dividers; no nested card surfaces. */
{!! $sectionUser !!} .record-drawer-body { padding:0 20px; }
{!! $sectionUser !!} .record-detail-section { padding:20px 0; }
{!! $sectionUser !!} .record-detail-section + .record-detail-section { border-top:1px solid var(--detail-divider); }
{!! $sectionUser !!} .record-detail-section h3 { margin:0 0 16px; color:var(--rs-text); font-size:14px; font-weight:650; line-height:1.5; }
{!! $sectionUser !!} .user-information-access { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); margin:0 0 16px !important; padding:0 !important; gap:16px; border:0 !important; border-radius:0; background:transparent !important; }
{!! $sectionUser !!} .user-information-access > div:last-child { text-align:left; }
{!! $sectionUser !!} .user-information-fields { padding:0 !important; margin:0 !important; gap:16px 20px; }
{!! $sectionUser !!} .user-information-fields > div { padding:0 !important; border:0 !important; }
{!! $sectionBranch !!} .branch-detail-body { padding:0 20px !important; }
{!! $sectionBranch !!} .branch-overview-grid { display:block; }
{!! $sectionBranch !!} .branch-overview-panel,
{!! $sectionBranch !!} .branch-overview-cases { margin:0 !important; padding:20px 0 !important; border:0 !important; border-radius:0 !important; background:transparent !important; }
{!! $sectionBranch !!} .branch-overview-panel + .branch-overview-panel,
{!! $sectionBranch !!} .branch-overview-cases { border-top:1px solid var(--detail-divider) !important; }
{!! $sectionBranch !!} :is(.branch-overview-panel,.branch-overview-cases) h3 { margin:0 0 16px; font-size:14px; line-height:1.5; font-weight:650; text-transform:none; letter-spacing:0; }
{!! $sectionBranch !!} .branch-overview-metrics { grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px 20px; }
{!! $sectionBranch !!} .branch-overview-metrics > div { align-items:flex-start; padding:0; border:0; border-radius:0; background:transparent; gap:5px; }
{!! $sectionBranch !!} .branch-overview-metrics span { text-transform:none; font-size:12px; }
{!! $sectionBranch !!} .branch-overview-metrics strong { font-size:18px; }
{!! $sectionBranch !!} .branch-detail-status { padding:0; border:0; border-radius:0; background:transparent; font-size:14px; }
{!! $sectionService !!} { padding:0 20px; }
{!! $sectionService !!} .svc-detail-identity { margin:0 !important; padding:20px 0 !important; border:0 !important; border-bottom:1px solid var(--detail-divider) !important; background:transparent !important; }
{!! $sectionService !!} .svc-detail-overview,
{!! $sectionService !!} .svc-detail-stat-grid,
{!! $sectionService !!} .svc-detail-section { margin:0 !important; padding:20px 0 !important; border:0 !important; border-bottom:1px solid var(--detail-divider) !important; border-radius:0 !important; background:transparent !important; }
{!! $sectionService !!} .svc-detail-section:last-child { border-bottom:0 !important; }
{!! $sectionService !!} :is(.svc-detail-stat,.svc-detail-list-grid > *,.svc-detail-description .svc-detail-note,.svc-detail-note,.svc-detail-empty) { padding:0 !important; border:0 !important; border-radius:0 !important; background:transparent !important; box-shadow:none !important; }
{!! $sectionService !!} .svc-detail-stat-grid { gap:16px 20px !important; }
{!! $sectionService !!} .svc-detail-list-grid { gap:20px !important; }
{!! $sectionService !!} .svc-detail-section h3 { margin:0 0 12px; font-size:14px; font-weight:650; line-height:1.5; text-transform:none; letter-spacing:0; }
{!! $sectionService !!} .svc-detail-list h3 { margin:0 0 12px; }
{!! $sectionService !!} .svc-detail-note { margin:0; line-height:1.65; }
{!! $sectionPayment !!} { width:min(560px,100vw) !important; max-width:100vw !important; border:0 !important; }
{!! $sectionPayment !!} :is(.pm-modal-hd,.pm-modal-ft) { padding:20px !important; background:var(--rs-header) !important; border:0 !important; }
{!! $sectionPayment !!} .pm-modal-hd { border-bottom:1px solid var(--detail-divider) !important; }
{!! $sectionPayment !!} .pm-modal-ft { padding:16px 20px !important; border-top:1px solid var(--detail-divider) !important; background:var(--rs-surface) !important; }
{!! $sectionPayment !!} .pm-modal-ft { display:flex; align-items:center !important; gap:12px; }
{!! $sectionPayment !!} .pm-modal-ft .pm-drawer-footer-actions { width:auto !important; min-width:0; flex-direction:row; flex-wrap:wrap; align-items:center; justify-content:flex-start; gap:8px; }
{!! $sectionPayment !!} .pm-modal-ft .pm-drawer-footer-actions[hidden] { display:none !important; }
{!! $sectionPayment !!} .pm-modal-ft .pm-drawer-footer-actions > :is(a,button) { width:auto !important; max-width:100%; flex:0 1 auto; white-space:normal; text-align:left; }
{!! $sectionPayment !!} .pm-modal-body { padding:20px !important; background:var(--rs-surface) !important; }
{!! $sectionPayment !!} .pm-modal-context { display:grid !important; grid-template-columns:minmax(0,1fr) !important; margin:0 0 20px !important; padding:0 0 20px !important; gap:12px !important; border:0 !important; border-bottom:1px solid var(--detail-divider) !important; border-radius:0 !important; background:transparent !important; }
{!! $sectionPayment !!} .pm-modal-context > div { display:grid; grid-template-columns:88px minmax(0,1fr) !important; align-items:baseline; gap:12px !important; min-width:0; padding:0; }
{!! $sectionPayment !!} .pm-modal-context dt { margin:0; font-size:12px; line-height:1.5; color:var(--rs-muted); }
{!! $sectionPayment !!} .pm-modal-context dd { display:flex; flex-wrap:wrap; align-items:baseline; gap:4px 8px; min-width:0; margin:0; font-size:13px; line-height:1.5; font-weight:500; overflow-wrap:anywhere; }
{!! $sectionPayment !!} .pm-modal-context .pm-context-code { flex:0 0 auto; }
{!! $sectionPayment !!} .pm-section-heading { margin:0 0 16px; font-size:14px; font-weight:650; }
{!! $sectionPayment !!} .pm-case-overview { display:grid; grid-template-columns:minmax(0,1fr) !important; gap:0 !important; margin:0 0 20px; padding:0 0 20px; border-bottom:1px solid var(--detail-divider); }
{!! $sectionPayment !!} .pm-summary-stat { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:baseline; gap:12px; min-width:0; padding:8px 0 !important; border:0 !important; border-radius:0 !important; background:transparent !important; box-shadow:none !important; }
{!! $sectionPayment !!} .pm-summary-stat > span { margin:0; font-size:12px !important; line-height:1.5; letter-spacing:0; text-transform:none; color:var(--rs-muted); }
{!! $sectionPayment !!} .pm-summary-stat strong { min-width:0; margin:0 !important; font-size:14px !important; line-height:1.5; font-weight:600; text-align:right; font-variant-numeric:tabular-nums; overflow-wrap:anywhere; }
{!! $sectionPayment !!} .pm-history-scroll { max-width:100%; overflow-x:auto; border:0; border-radius:0; background:transparent; box-shadow:none; }
{!! $sectionPayment !!} .pm-history-table { width:100% !important; min-width:480px !important; table-layout:fixed; }
{!! $sectionPayment !!} .pm-history-table :is(th,td) { padding:10px 5px !important; vertical-align:top; font-size:12px !important; line-height:1.5; overflow-wrap:anywhere; }
{!! $sectionPayment !!} .pm-history-table th { background:transparent !important; font-size:11px !important; font-weight:600; letter-spacing:0 !important; text-transform:none; }
{!! $sectionPayment !!} .pm-history-table th:nth-child(1) { width:22%; }
{!! $sectionPayment !!} .pm-history-table th:nth-child(2) { width:22%; }
{!! $sectionPayment !!} .pm-history-table th:nth-child(3) { width:15%; }
{!! $sectionPayment !!} .pm-history-table th:nth-child(4) { width:25%; }
{!! $sectionPayment !!} .pm-history-table th:nth-child(5) { width:16%; }
{!! $sectionPayment !!} .pm-history-table .pm-history-date { display:block; white-space:nowrap; }
{!! $sectionPayment !!} .pm-history-table .pm-history-time { display:block; color:var(--rs-muted); font-size:11px; white-space:nowrap; }
{!! $sectionPayment !!} .pm-history-table .pm-money { white-space:normal; font-variant-numeric:tabular-nums; }
{!! $sectionPayment !!} .pm-history-actions { display:flex; justify-content:flex-end; align-items:center; gap:4px !important; flex-wrap:nowrap; }
{!! $sectionPayment !!} .pm-history-table :is(th:last-child,td[data-label=Actions]) { text-align:right; }
{!! $sectionPayment !!} .pm-history-actions .pm-modal-icon { display:inline-flex; align-items:center; justify-content:center; flex:0 0 32px; width:32px; height:32px; min-width:32px; min-height:32px; padding:0 !important; }
{!! $sectionPayment !!} [data-pm-detail-host] :is(.pm-txn-det,.pm-txn-det-grid,.pm-txn-det-cell) { border-radius:0 !important; background:transparent !important; box-shadow:none !important; }
{!! $sectionPayment !!} [data-pm-detail-host] .pm-txn-det { padding:0; border:0; }
{!! $sectionPayment !!} [data-pm-detail-host] .pm-detail-group-title { margin:20px 0 12px; font-size:14px; font-weight:650; }
{!! $sectionPayment !!} [data-pm-detail-host] .pm-detail-group-title:first-child { margin-top:0; }
{!! $sectionPayment !!} [data-pm-detail-host] .pm-txn-det-grid { margin:0; padding:0 0 20px; border:0; border-bottom:1px solid var(--detail-divider); }
{!! $sectionPayment !!} [data-pm-detail-host] .pm-txn-det-cell { padding:8px 0; border:0; }
@media(max-width:480px) {
    {!! $sectionService !!} .svc-detail-list-grid { grid-template-columns:minmax(0,1fr) !important; }
    {!! $sectionPayment !!} [data-pm-detail-host] .pm-txn-det-cell { grid-template-columns:minmax(0,1fr); gap:5px; }
}
</style>
