@once
<style>
.pw-content,.pw-confirm { --pw-surface:#f5f9f6;--pw-soft:#e4eee7;--pw-focus:#d3e5d9;--pw-ink:#183c2a;--pw-muted:#63786b;--pw-line:#bdd0c3;color:var(--pw-ink);background:var(--pw-surface);font-size:14px;font-weight:400; }
html[data-theme=dark] :is(.pw-content,.pw-confirm) { --pw-surface:#18291f;--pw-soft:#243c2e;--pw-focus:#30503b;--pw-ink:#e2eee5;--pw-muted:#afc3b6;--pw-line:#365743; }
.pw-dialog { position:fixed;inset:0 0 0 auto;margin:0;padding:0;border:0;border-radius:0;width:min(600px,100vw);max-width:100vw;height:100dvh;max-height:100dvh;overflow:hidden;background:var(--surface,#f5f9f6); }
.pw-dialog::backdrop,.pw-confirm::backdrop { background:rgba(17,30,23,.55); }
.pw-dialog:is(.pw-review-with-history,.pw-queue-with-request) { box-sizing:border-box; width:100vw; max-width:100vw; padding-left:16px; display:flex; flex-direction:row-reverse; justify-content:flex-start; align-items:center; gap:16px; background:transparent; }
.pw-dialog.pw-review-with-history > #workflowContent { flex:0 0 600px; width:600px; min-width:0; max-width:600px; margin:0; height:100dvh; }
.pw-dialog.pw-review-with-history > .pw-review-history-panel { flex:0 1 680px; min-width:0; margin:0; height:auto; max-height:min(760px,calc(100dvh - 32px)); border-radius:14px; overflow:hidden; }
.pw-dialog > .pw-queue-history { max-width:none; }
.pw-dialog.pw-queue-with-request > .pw-queue-history { flex:0 0 600px; width:600px; margin:0; height:100dvh; }
.pw-dialog.pw-queue-with-request > #workflowContent { flex:0 1 560px; min-width:0; margin:0; height:min(760px,calc(100dvh - 32px)); border-radius:14px; overflow:hidden; }
.pw-history-standalone { width:min(760px,calc(100vw - 32px)); height:min(800px,calc(100dvh - 32px)); padding:0; border:0; border-radius:14px; overflow:hidden; }
.pw-history-standalone::backdrop { background:rgba(17,30,23,.55); }
.pw-review-history-panel .pw-payment-history { border:0; padding:0; }
.pw-review-history-panel .pw-history-scroll { max-height:none; }
.pw-content.pw-review-history-panel .pw-history-scroll table thead th { background:transparent!important; color:var(--pw-ink); text-transform:none; letter-spacing:normal; font-size:12px; font-weight:600; }
.pw-content.pw-review-history-panel .pw-history-scroll table :is(thead,thead tr) { background:transparent!important; }
.pw-history-launcher button { display:inline-flex; align-items:center; gap:8px; }
.pw-content .pw-history-launcher button[aria-expanded=true] { background:var(--pw-focus)!important; }
@media(max-width:1000px) {
    .pw-dialog:is(.pw-review-with-history,.pw-queue-with-request) { flex-direction:column; gap:8px; padding:8px; }
    .pw-dialog.pw-review-with-history > #workflowContent { flex:1 1 45%; width:100%; max-width:100%; height:auto; min-height:0; }
    .pw-dialog.pw-review-with-history > .pw-review-history-panel { flex:1 1 55%; width:100%; height:auto; min-height:0; }
    .pw-dialog.pw-queue-with-request > .pw-queue-history { flex:1 1 45%; width:min(100%,600px); height:auto; min-height:0; }
    .pw-dialog.pw-queue-with-request > #workflowContent { flex:1 1 55%; width:min(100%,600px); height:auto; min-height:0; }
}
.pw-confirm.pw-confirm-shared-scrim::backdrop { background:transparent; }
html[data-theme] body.panel-shell-body.pm-transactions-modal-open .pm-transactions-backdrop:is(.pw-with-request,.pw-with-picker,.pm-with-detail).open {
    flex-direction:row-reverse; justify-content:flex-start!important; gap:16px; padding:0!important;
}
html[data-theme] body.panel-shell-body .pm-transactions-backdrop:is(.pw-with-request,.pw-with-picker,.pm-with-detail) > .pm-modal.pm-transactions-modal {
    position:relative!important; inset:auto!important; flex:0 0 auto; min-width:0; min-height:0!important;
}
.pw-companion { flex:0 1 560px; align-self:center; min-width:0; height:min(740px,calc(100dvh - 40px)); border-radius:16px; overflow:hidden; }
.pm-transactions-backdrop.pw-review-with-history > .pw-review-history-panel {
    flex:0 1 440px; align-self:center; min-width:0; margin:0;
    height:min(740px,calc(100dvh - 40px)); border-radius:16px; overflow:hidden;
}
.pw-companion .pw-content { max-width:none; }
.pm-payment-companion[hidden],.pw-with-request > .pm-payment-companion { display:none!important; }
.pm-payment-companion [data-pm-detail-host] { display:block; }
.pm-payment-companion [data-pm-detail-host] > .pm-txn-det { padding:0; border:0; background:transparent; }
.pm-payment-companion [data-pm-detail-footer] { align-items:center; }
.pm-payment-companion [data-pm-detail-request]:empty { display:none; }
.pm-payment-companion [data-pm-detail-print] { margin-inline-start:auto; }
.pm-payment-companion [data-pm-detail-request] .pm-correction-action {
    display:inline-flex; align-items:center; gap:6px; min-height:40px; padding:9px 12px;
    border-radius:8px; background:var(--pw-soft); color:var(--pw-ink); font-size:13px; font-weight:600;
}
.pm-payment-companion [data-pm-detail-request] .pm-correction-action:hover { background:var(--pw-focus); }
.pm-correction-pending-state { display:grid; justify-items:start; gap:8px; }
.pm-correction-pending-state > span { color:var(--pw-muted); font-size:12px; line-height:1.45; }
.pm-correction-pending-state .pm-correction-disabled { min-height:40px; padding:9px 12px; border:0; border-radius:8px; background:color-mix(in srgb,#f59e0b 14%,var(--pw-soft)); color:#704606; font-size:13px; font-weight:650; cursor:not-allowed; opacity:.8; }
@media (prefers-reduced-motion:no-preference) {
    .pm-payment-companion:not([hidden]) { animation:pm-payment-companion-in 180ms ease-out; }
    @keyframes pm-payment-companion-in { from { opacity:0; transform:translateX(8px); } }
}
.pw-companion.pm-correction-picker { height:auto; max-height:calc(100dvh - 24px); }
.pm-correction-picker .pw-content { height:auto; max-height:inherit; }
.pm-correction-search-title { margin:0 0 12px; font-size:15px; font-weight:700; }
.pm-correction-search { position:relative; margin-top:6px; border-radius:14px; }
html[data-theme] body.panel-shell-body .pm-correction-search > .shared-record-search-input {
    width:100%; min-width:0; padding-right:128px !important; border-radius:14px !important;
}
.pm-correction-search > .pm-search-button { position:absolute; inset:0 0 0 auto; z-index:1; width:84px; border-radius:0 14px 14px 0; }
html[data-theme] body.panel-shell-body .pm-correction-search > .shared-record-search-clear {
    position:absolute; inset:0 88px 0 auto; z-index:2; width:32px; min-height:0; padding:0; background:transparent;
}
.pm-correction-search > [hidden] { display:none !important; }
.pm-correction-search-status { margin:12px 0 6px; color:var(--pw-muted); font-size:12px; }
.pm-correction-search-results { list-style:none; display:grid; gap:4px; max-height:280px; overflow:auto; padding:6px; margin:0; border-radius:10px; background:var(--pw-soft); }
.pm-correction-search-results[hidden] { display:none; }
.pm-correction-search-results [data-pm-correction-result] { display:grid; width:100%; gap:3px; padding:8px 10px; border-radius:7px; background:transparent; color:var(--pw-ink); text-align:start; text-decoration:none; overflow-wrap:anywhere; }
.pm-correction-search-results [data-pm-correction-result]:is(:hover,:focus-visible) { background:var(--pw-focus); outline:none; }
.pm-correction-search-results [data-pm-correction-result]:focus-visible strong { text-decoration:underline; }
.pm-correction-search-results mark { background:transparent; color:inherit; text-decoration:underline; text-underline-offset:3px; }
.pm-correction-search-results small { color:var(--pw-muted); font-size:12px; }
.pm-transactions-backdrop [data-pm-correction-picker-toggle][hidden] { display:none!important; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal [data-pm-history-footer]:has([data-pm-correction-picker-toggle]:not([hidden])) .pm-ledger-print {
    flex:1 1 auto; width:auto; min-width:0;
}
@media (max-width:1200px) {
    html[data-theme] body.panel-shell-body.pm-transactions-modal-open .pm-transactions-backdrop:is(.pw-with-request,.pw-with-picker,.pm-with-detail).open {
        flex-direction:column; align-items:flex-end!important; gap:8px;
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop:is(.pw-with-request,.pw-with-picker,.pm-with-detail) > .pm-modal.pm-transactions-modal {
        flex:1 1 35%; height:auto!important;
    }
    .pm-transactions-backdrop > .pw-companion { flex:1 1 65%; align-self:flex-end; width:min(100%,760px); min-height:0; }
    .pw-companion.pm-correction-picker { flex:0 1 auto; max-height:55dvh; }
}
@media (max-width:1400px) {
    html[data-theme] body.panel-shell-body.pm-transactions-modal-open .pm-transactions-backdrop.pw-with-request.pw-review-with-history.open {
        flex-direction:column; align-items:flex-end!important; gap:8px;
    }
    html[data-theme] body.panel-shell-body .pm-transactions-backdrop.pw-with-request.pw-review-with-history > .pm-transactions-modal {
        flex:1 1 25%; height:auto!important;
    }
    .pm-transactions-backdrop.pw-review-with-history > .pw-companion {
        flex:1 1 45%; align-self:flex-end; width:min(100%,760px); min-height:0;
    }
    .pm-transactions-backdrop.pw-review-with-history > .pw-review-history-panel {
        flex:1 1 30%; align-self:flex-end; width:min(100%,760px); min-height:0; max-width:100%;
    }
}
.pw-content { display:flex;flex-direction:column;height:100%;min-height:0;max-width:900px;margin:auto; }
.pw-content[hidden] { display:none!important; }
.page-content > .pw-content { height:calc(100dvh - 150px); }
.pw-dialog > .pw-content { height:100%; }
.pw-content input[type=checkbox] { appearance:auto!important;width:18px!important;height:18px!important;min-height:18px!important;padding:0!important;flex:none;accent-color:#4c8060; }
.pw-content .pw-void-confirmation { display:flex; align-items:flex-start; margin-top:12px; }
.pw-content [data-correction-only],.pw-content [data-change-field=payment_details] { display:grid;gap:18px; }
.pw-content [data-change-field=payment_details] > .pw-grid { padding-bottom:16px;border-bottom:1px solid var(--pw-line); }
.page-content > .pw-content { height:calc(100dvh - 150px); }
.pw-dialog > .pw-content { height:100%; }
.pw-content input[type=checkbox] { appearance:auto!important;width:18px!important;height:18px!important;min-height:18px!important;padding:0!important;flex:none;accent-color:#4c8060; }
.pw-content [data-correction-only],.pw-content [data-change-field=payment_details] { display:grid;gap:18px; }
.pw-content [data-change-field=payment_details] > .pw-grid { padding-bottom:16px;border-bottom:1px solid var(--pw-line); }
.pw-head { display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 24px;background:var(--pw-soft);flex:none; }
.pw-head h2 { font-size:20px;font-weight:600;margin:4px 0 0; }.pw-content small { color:var(--pw-muted);font-size:12px;font-weight:400; }
.pw-request-source { display:block; margin-top:6px; overflow-wrap:anywhere; }
.pw-request-source strong,.pw-audit-source strong { color:var(--pw-ink); font-weight:600; }
.pw-request-switching [data-correction-viewing] { display:none; }
.pw-content [data-correction-opening] { color:var(--pw-ink); font-weight:650; }
.pw-request-switching > .pw-body { opacity:.7; }
@media (prefers-reduced-motion:no-preference) {
    .pw-request-switching > .pw-body { transition:opacity 160ms ease; }
    .pw-request-switched > .pw-body { animation:pw-request-reveal 220ms ease-out; }
    @keyframes pw-request-reveal { from { opacity:.55; transform:translateY(4px); } }
}
.pw-head button { width:40px;height:40px;border:0;border-radius:8px;background:var(--pw-surface);color:var(--pw-ink); }
.pw-form { display:flex;flex-direction:column;flex:1;min-height:0; }.pw-body { padding:22px 24px;overflow:auto;min-height:0;flex:1;display:grid;gap:18px;align-content:start; }
.pw-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px; }.pw-content label { display:grid;gap:7px;font-size:13px;align-content:start; }
.pw-content input:not([type=checkbox]):not([type=hidden]),.pw-content select,.pw-content textarea { width:100%;min-width:0;min-height:44px;border:0!important;outline:0!important;box-shadow:none!important;border-radius:8px;padding:10px 12px;background:var(--pw-soft);color:var(--pw-ink);font:inherit;transition:background 160ms; }
.pw-content :is(input,select,textarea):hover,.pw-content :is(input,select,textarea):focus { background:var(--pw-focus)!important; }
.pw-content [hidden] { display:none!important; }.pw-summary { display:grid;gap:5px; }.pw-summary span { color:var(--pw-muted);font-size:13px; }
.pw-notice { padding:12px 14px;background:var(--pw-soft);border-radius:8px;font-size:13px;line-height:1.6; }
.pw-request-choices { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; border:0; margin:0; padding:0; min-width:0; }
.pw-request-choices legend { margin-bottom:8px; font-weight:600; }
.pw-request-choices label { display:flex; align-items:flex-start; padding:12px; border-radius:8px; background:var(--pw-soft); cursor:pointer; }
.pw-request-choices label:has(:checked), .pw-request-choices label:hover { background:var(--pw-focus); }
.pw-request-choices label span { display:grid; gap:4px; }
.pw-content .pw-request-choices input[type=radio] { appearance:auto; width:16px; height:16px; min-height:16px; padding:0; flex:none; accent-color:#4c8060; }
.pw-footer-note { margin-inline-end:auto; align-self:center; }
@media(max-width:480px) {
    .pw-request-choices { grid-template-columns:1fr; }
    .pw-content .pw-footer { flex-wrap:wrap; }
    .pw-footer-note { flex-basis:100%; }
}
.pw-options { border:0;margin:0 0 16px;padding:0;display:flex;gap:12px;flex-wrap:wrap; }.pw-options legend { margin-bottom:10px;font-size:13px; }.pw-options label { display:flex;align-items:center;gap:7px;padding:8px;background:var(--pw-soft);border-radius:6px; }
.pw-content h3 { font-weight:600;font-size:14px;margin:0 0 8px; }.pw-impact,.pw-section { border-top:1px solid var(--pw-line);padding-top:16px; }.pw-impact p { line-height:1.6; }
.pw-context { display:grid; gap:14px; min-width:0; }
.pw-context-title { display:flex; align-items:baseline; justify-content:space-between; gap:12px; min-width:0; }
.pw-context-title > strong { font-size:14px; font-weight:600; overflow-wrap:anywhere; }
.pw-context-title .pw-status { flex:none; }
.pw-meta-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px 20px; margin:0; min-width:0; }
.pw-meta-grid > div { min-width:0; }
.pw-meta-grid dt { font-size:11px; color:var(--pw-muted); margin-bottom:4px; font-weight:400; }
.pw-meta-grid dd { margin:0; font-size:13px; line-height:1.5; color:var(--pw-ink); overflow-wrap:anywhere; }
.pw-meta-grid dd small { display:block; margin-top:2px; }
.pw-context-actions { display:flex; flex-wrap:wrap; align-items:center; gap:8px 12px; }
.pw-context-actions .payment-original-proof { display:contents; }
.pw-content .pw-context-actions :is(a,button) { display:inline-flex; align-items:center; gap:7px; width:auto; min-height:36px; padding:8px 10px; background:var(--pw-soft); color:var(--pw-ink); border-radius:7px; font-size:12px; font-weight:400; text-decoration:none; }
.pw-content .pw-context-actions :is(a,button):hover,.pw-content .pw-context-actions button[aria-expanded=true] { background:var(--pw-focus); }
.pw-context-actions p { margin:0; font-size:12px; color:var(--pw-muted); }
.pw-inline-note { margin:0; color:var(--pw-muted); font-size:12px; line-height:1.6; }
.pw-explanation { display:grid; gap:12px; }
.pw-explanation h3 { margin:0; }
.pw-explanation-text { margin:0; font-size:13px; line-height:1.6; overflow-wrap:anywhere; white-space:pre-line; }
.pw-decision-note { display:grid; gap:5px; margin-top:12px; padding:10px 12px; background:var(--pw-soft); border-radius:7px; }
.pw-decision-note p { margin:0; font-size:13px; line-height:1.5; overflow-wrap:anywhere; white-space:pre-line; }
.pw-decision-note[data-status=REJECTED] { color:var(--pw-error-ink); background:var(--pw-error-surface); }
.pw-decision-note[data-status=REJECTED] small { color:inherit; }
.pw-audit-source { margin:8px 0; color:var(--pw-muted); font-size:12px; overflow-wrap:anywhere; }
.pw-correction-history-panel .pw-head {
    margin:16px 16px 0; padding:8px 12px; border-radius:8px;
    background:color-mix(in srgb,var(--pw-line) 75%,var(--pw-surface));
}
.pw-correction-history-panel .pw-head h2 { margin:0; font-size:17px; }
.pw-correction-history-panel .pw-head button { flex:none; padding:0; min-height:32px; height:32px; width:32px; background:transparent; }
.pw-correction-history-panel .pw-audit-trail { padding:12px 16px 16px; gap:10px; }
.pw-correction-history-context { display:grid; gap:6px; padding:12px 14px; border-radius:8px; background:var(--pw-soft); }
.pw-correction-summary-head { display:flex; align-items:baseline; flex-wrap:wrap; justify-content:space-between; gap:4px 12px; }
.pw-correction-summary-head h3 { margin:0; font-size:14px; font-weight:650; }
.pw-correction-summary-head strong { font-size:13px; }
.pw-correction-summary-counts { display:flex; flex-wrap:wrap; gap:6px; list-style:none; margin:4px 0 0; padding:0; }
.pw-correction-summary-counts .pm-correction-status { text-transform:none; letter-spacing:normal; }
.pw-audit-trail .pw-audit-source,.pw-audit-trail .pm-correction-history-list { margin:0; }
.pm-correction-history-list { display:grid; gap:12px; margin:12px 0 0; padding:0; list-style:none; }
.pm-correction-entry {
    min-width:0; padding:14px; border-radius:10px;
    background:color-mix(in srgb,var(--pw-line,var(--border)) 60%,var(--pw-surface,var(--card)));
}
.pm-correction-entry[aria-current="true"] { background:var(--pw-focus); }
.pm-correction-entry-head,.pm-correction-entry-footer { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px 10px; }
.pm-correction-entry-head h3 { margin:0; font-size:14px; font-weight:700; }
.pm-correction-entry-footer .pw-text-action { color:var(--pw-ink,var(--ink)); }
.pm-correction-status {
    padding:3px 9px; border-radius:999px; background:var(--pw-surface,var(--card)); color:var(--pw-ink,var(--ink));
    font-size:12px; font-weight:650; letter-spacing:.075em; text-transform:uppercase;
}
.pm-correction-status[data-status="REJECTED"] { background:#fbe4e1; color:#842d27; }
.pm-correction-status[data-status="APPROVED"] { background:#dff3e7; color:#185836; }
.pm-correction-status[data-status="PENDING"] { background:#ffefcf; color:#704606; }
html[data-theme="dark"] .pm-correction-status[data-status="REJECTED"] { background:#512e30; color:#ffd0cb; }
html[data-theme="dark"] .pm-correction-status[data-status="APPROVED"] { background:#244737; color:#b9f1cc; }
html[data-theme="dark"] .pm-correction-status[data-status="PENDING"] { background:#4c3b21; color:#ffe0a3; }
.pm-correction-meta { display:grid; gap:6px; margin:12px 0; font-size:13px; line-height:1.5; }
.pm-correction-meta > div { display:grid; grid-template-columns:minmax(78px,105px) minmax(0,1fr); gap:8px; }
.pm-correction-meta dt { color:var(--pw-muted,var(--ink-muted)); }
.pm-correction-meta dd { margin:0; overflow-wrap:anywhere; }
.pw-audit-current { font-size:13px; font-weight:650; }
.pm-correction-entry[aria-current="true"] [data-workflow-history-link],
.pm-correction-entry:not([aria-current="true"]) .pw-audit-current { display:none; }
.pw-review-history-panel .pw-history-heading { margin:0 0 12px; padding-bottom:10px; border-bottom:1px solid var(--pw-line); }
.pw-review-history-panel .pw-history-scroll + .pw-history-heading { margin-top:16px; }
@media(max-width:380px) { .pw-meta-grid { grid-template-columns:1fr; } }
.pw-content details > summary { padding:8px 0; margin-bottom:8px; cursor:pointer; color:var(--pw-ink); font-size:13px; }
.pw-payment-history { min-width:0; max-width:100%; }
.pw-history-heading { display:grid; gap:4px; margin:12px 0; }
.pw-history-scroll { max-width:100%; max-height:360px; overflow:auto; border-bottom:1px solid var(--pw-line); }
.pw-history-scroll table { width:100%; min-width:480px; border-collapse:collapse; table-layout:fixed; font-size:12px; }
.pw-history-scroll :is(th,td) { padding:10px 8px; text-align:left; vertical-align:top; overflow-wrap:anywhere; border-bottom:1px solid var(--pw-line); }
.pw-history-scroll th { font-weight:600; background:transparent; }
.pw-history-scroll th:first-child { width:34%; }.pw-history-scroll th:nth-child(2) { width:28%; }.pw-history-scroll th:nth-child(3) { width:22%; }
.pw-history-scroll td > :is(strong,span,small) { display:block; margin-bottom:5px; }
.pw-history-scroll strong { font-weight:600; }
.pw-history-scroll .pw-history-current { background:var(--pw-soft); }
.pw-history-marker { color:#216b43; font-weight:600; }.pw-history-status { color:var(--pw-muted); }.pw-history-status[data-posted=true] { color:#216b43; }
html[data-theme=dark] :is(.pw-history-marker,.pw-history-status[data-posted=true]) { color:#a1dcb5; }
.pw-history-scroll .pw-text-action { font-size:12px; }
.pw-footer { display:flex;justify-content:flex-end;gap:10px;border-top:1px solid var(--pw-line);padding:14px 24px;background:var(--pw-surface);flex:none; }
.pw-correction-form-footer { display:grid; gap:8px; justify-content:stretch; max-height:45dvh; overflow:auto; }
.pw-correction-form-footer .pw-impact { margin:0; }
.pw-correction-form-actions { display:flex; flex-wrap:wrap; justify-content:space-between; gap:8px; }
.pw-void-pair > div { min-width:0; overflow-wrap:anywhere; }
.pw-void-pair .payment-original-proof { margin-top:6px; }
.pw-content button,.pw-content a.pw-button,.pw-confirm button { cursor:pointer;border:0;border-radius:8px;min-height:40px;padding:9px 14px;background:var(--pw-soft);color:var(--pw-ink);font:inherit;font-size:13px; }
.pw-content button:hover,.pw-confirm button:hover { filter:brightness(.96); }.pw-content button:focus-visible,.pw-content a:focus-visible,.pw-confirm button:focus-visible { outline:2px solid var(--pw-muted);outline-offset:3px; }
.pw-content .pw-primary,.pw-confirm .pw-primary { background:#b1d0bc;color:#173e29; }.pw-content .pw-danger,.pw-confirm .pw-danger { background:#f5ddda;color:#9b3930; }.pw-content .pw-warning,.pw-confirm .pw-warning { background:#f3e4c5;color:#825319; }
.pw-content .pw-primary,.pw-confirm .pw-primary { background:#b1d0bc!important;color:#173e29!important; }
.pw-content .pw-danger,.pw-confirm .pw-danger { background:#f5ddda!important;color:#9b3930!important; }
.pw-content .pw-warning,.pw-confirm .pw-warning { background:#f3e4c5!important;color:#825319!important; }
.pw-content button:disabled { opacity:.55;cursor:wait; }
.pw-content { --pw-error-ink:#8d2925; --pw-error-surface:#fbe8e6; --pw-error-focus:#f7d8d4; }
html[data-theme=dark] .pw-content { --pw-error-ink:#ffc2bc; --pw-error-surface:#412725; --pw-error-focus:#552e2a; }
.pw-content :is(.pw-error,.pw-field-error) {
    display:flex; align-items:flex-start; gap:8px; min-width:0; margin:0; padding:8px 10px;
    border-radius:6px; background:var(--pw-error-surface); color:var(--pw-error-ink);
    font-size:13px; font-weight:600; line-height:1.45; overflow-wrap:anywhere;
}
.pw-content :is(.pw-error,.pw-field-error)::before {
    content:'!' / ''; display:grid; place-items:center; flex:0 0 16px; height:16px; margin-top:1px;
    border:1px solid currentColor; border-radius:50%; font-size:11px; line-height:1;
}
.pw-content :is(input,select,textarea)[aria-invalid=true],
.pw-content label:has(input:is([type=checkbox],[type=radio])[aria-invalid=true]) {
    background:var(--pw-error-surface)!important;
}
.pw-content :is(input,select,textarea)[aria-invalid=true]:is(:hover,:focus) { background:var(--pw-error-focus)!important; }
.pw-content input:is([type=checkbox],[type=radio])[aria-invalid=true] { accent-color:var(--pw-error-ink); }
.pw-status { font-size:12px;color:#7a561d; }.pw-status[data-status=APPROVED] { color:#247246; }.pw-status[data-status=REJECTED] { color:#b14c42; }.pw-status[data-status=WITHDRAWN] { color:#68776e; }
.pw-confirm { border:0;border-radius:16px;padding:28px;width:min(440px,calc(100vw - 32px));text-align:center; }.pw-confirm h2 { font-size:19px;font-weight:600;margin:14px 0 10px; }.pw-confirm p { font-size:14px;line-height:1.6;color:var(--pw-muted); }.pw-confirm footer { display:flex;justify-content:center;gap:10px;margin-top:22px; }.pw-confirm > i { display:block;text-align:center;font-size:25px;color:#a56b22; }
.pw-compare { display:grid;font-size:13px; }.pw-compare > div { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;padding:11px 0;border-bottom:1px solid var(--pw-line);overflow-wrap:anywhere; }.pw-compare .pw-changed { color:#247246;font-weight:600; }
html[data-theme=dark] .pw-compare .pw-changed { color:#9cdbb0; }
.pw-text-action { color:var(--ink,#244b36);font-size:13px;text-decoration:underline;text-underline-offset:3px; }.pw-request-list { background:var(--surface,#f5f9f6);border-radius:12px;overflow:hidden; }.pw-request-list table { width:100%;font-size:13px; }.pw-request-list th,.pw-request-list td { text-align:left;padding:15px;border-bottom:1px solid var(--border,#bdd0c3); }.pw-request-list tbody tr:hover { background:var(--surface-hover,#e4eee7); }.pw-request-list footer { padding:12px; }.pw-request-toolbar { display:flex;flex-wrap:wrap;gap:12px;padding:16px;border-bottom:1px solid var(--border,#bdd0c3); }.pw-request-toolbar input,.pw-request-toolbar select { border:0;border-radius:8px;background:var(--surface-soft,#e4eee7);padding:10px; }
.pw-active-row { background:var(--surface-hover,#e4eee7); }.pw-notice-list { display:grid;gap:6px;max-height:300px;overflow:auto; }.pw-notice-list a { display:grid;padding:10px;text-decoration:none;background:var(--surface-soft,#e4eee7);border-radius:8px;color:var(--ink,#183c2a);font-size:12px; }.pw-notice-list a[data-unread=true] { font-weight:600; }.pw-notice-list small { font-weight:400;opacity:.8; }
#chapel-schedule-context { padding:12px;background:var(--intake-hover,#e4eee7);border-radius:8px;font-size:13px; }#chapel-schedule-context[hidden],#chapel-schedule-context [hidden] { display:none!important; }#chapel-schedule-context details { margin-top:10px; }#chapel-schedule-context summary { cursor:pointer;color:#87571c; }#chapel-schedule-context details p { margin:10px 0; }#chapel-schedule-context label { display:flex;gap:8px;align-items:start; }
@media(max-width:640px){.pw-grid { grid-template-columns:1fr; }.pw-body { padding:18px 16px; }.pw-head,.pw-footer { padding:16px; }.pw-compare > div { gap:8px; }.pw-request-list { overflow-x:auto; }}
@media(prefers-reduced-motion:reduce){.pw-content * { transition:none!important;animation:none!important; }}
</style>
<style>
.payment-monitoring-page .pw-request-list {
    --queue-surface:#f4f8f5; --queue-control:#e7f0ea; --queue-hover:#e4efe8;
    --queue-active:#d8e9de; --queue-line:#bdcfc2; --queue-muted:#526c5d;
    background:var(--queue-surface);
    overflow:visible;
}
html[data-theme=dark] .payment-monitoring-page .pw-request-list {
    --queue-surface:#18291f; --queue-control:#243c2e; --queue-hover:#20372a;
    --queue-active:#2b4736; --queue-line:#395747; --queue-muted:#b1c8b9;
}
.payment-monitoring-page .pw-request-table { border-radius:0 0 12px 12px; }
.payment-monitoring-page .pw-request-list { overflow-x:visible; }
.payment-monitoring-page .pw-request-table table { min-width:720px; border-collapse:collapse; }
.payment-monitoring-page .pw-request-table :is(th,td) {
    background:transparent; border-color:var(--queue-line); padding:13px 16px;
}
.payment-monitoring-page .pw-request-table th { color:var(--queue-muted); font-weight:600; }
.payment-monitoring-page .pw-request-table tbody tr[data-workflow-row] { cursor:pointer; transition:background-color 160ms ease; }
.payment-monitoring-page .pw-request-table tr[data-workflow-row]:hover { background:var(--queue-hover); }
.payment-monitoring-page .pw-request-table tr.pw-active-row { background:var(--queue-active); }
.payment-monitoring-page .pw-request-table td:last-child { width:64px; text-align:center; }
.payment-monitoring-page .pw-request-table a[data-payment-workflow] {
    display:inline-grid; place-items:center; width:36px; height:36px; color:inherit;
}
.payment-monitoring-page .pw-request-table a[data-payment-workflow] i { transition:color 160ms ease, transform 160ms ease; }
.payment-monitoring-page .pw-request-table tr:is(:hover,.pw-active-row) a[data-payment-workflow] i {
    color:var(--filter-ui-focus,#194a32); transform:scale(.9);
}
.payment-monitoring-page .pw-request-list > footer { padding:8px 16px; }
html[data-theme=dark] .pw-request-list .pw-status { color:#f2cc8e; }
html[data-theme=dark] .pw-request-list .pw-status[data-status=APPROVED] { color:#9cdbb0; }
html[data-theme=dark] .pw-request-list .pw-status[data-status=REJECTED] { color:#f3aba4; }
html[data-theme=dark] .pw-request-list .pw-status[data-status=WITHDRAWN] { color:#b5c6bb; }
@media(prefers-reduced-motion:reduce) {
    .payment-monitoring-page .pw-request-table :is(tr,i) { transition:none!important; }
}
</style>
@endonce
