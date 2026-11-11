<style>/* Ledger owns its layout independently of legacy modal/table rules. */html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal {    --pm-ledger-tint:color-mix(in srgb,var(--brand) 12%,var(--card));    --pm-ledger-row:color-mix(in srgb,var(--brand) 4%,var(--card));    --pm-transaction-heading:color-mix(in srgb,var(--brand) 8%,transparent);    --pm-transaction-divider:color-mix(in srgb,var(--border) 55%,transparent);    --pm-ledger-paid-ink:#087443;    --pm-ledger-due-ink:#a64b00;    width:min(760px,100vw) !important;    max-width:100vw !important;}
html[data-theme='dark'] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal {    --pm-ledger-paid-ink:#6ee7b7;    --pm-ledger-due-ink:#fcd34d;}
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-modal-body { display:block; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-context { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px 24px; margin:0 0 24px; padding:18px; border:0; border-radius:10px; background:var(--pm-ledger-tint); }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-context > div { display:flex; gap:10px; align-items:flex-start; min-width:0; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-context > div > i { display:grid; place-items:center; flex:0 0 30px; height:30px; border:1px solid var(--border); border-radius:7px; color:var(--ink-muted); background:var(--card); }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-context dt { margin-bottom:3px; color:var(--ink-muted); font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-context dd { margin:0; color:var(--ink); font-size:14px; line-height:1.5; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-context dd span + span::before { content:' | '; color:var(--ink-muted); }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-section-heading { color:var(--ink-muted) !important; font-size:13px !important; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin:0; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-card { display:grid; align-content:start; gap:4px; padding:14px 12px; border:0; border-radius:10px; background:var(--pm-ledger-row); min-width:0; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-card > span { color:var(--ink-muted); font-size:12px; line-height:1.45; white-space:nowrap; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-card strong { color:var(--ink); font-size:20px; font-weight:700; line-height:1.4; font-variant-numeric:tabular-nums; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-card strong small { color:inherit; font-size:11px; font-weight:600; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-paid { background:color-mix(in srgb,#10b981 6%,var(--card)); }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-due { background:color-mix(in srgb,#f59e0b 6%,var(--card)); }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-paid strong { color:var(--pm-ledger-paid-ink); }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-due strong { color:var(--pm-ledger-due-ink); }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-section-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:24px 0 12px; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-ledger-section-head .pm-section-heading { margin:0; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-count { flex:none; padding:3px 8px; border-radius:20px; color:var(--ink-muted); background:var(--pm-transaction-heading); font-size:12px; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-cards { display:grid; gap:10px; width:100%; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-payment-card {    position:relative; display:grid; gap:12px; min-width:0; padding:14px;    border:1px solid var(--pm-transaction-divider); border-radius:12px;    background:color-mix(in srgb,var(--brand) 4%,var(--card)); color:var(--ink);    transition:background-color .16s ease,border-color .16s ease;}
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-payment-card:hover { background:color-mix(in srgb,var(--brand) 8%,var(--card)); }
.pm-payment-card__primary { display:grid; grid-template-columns:minmax(0,1fr) auto auto; align-items:center; gap:12px; min-width:0; }
.pm-payment-card__date,.pm-payment-card__amount,.pm-payment-card__badges { min-width:0; }
.pm-payment-card__date { display:grid; gap:3px; }
.pm-payment-card__date .pm-history-date { color:var(--ink); font-size:14px; font-weight:650; white-space:nowrap; }
.pm-payment-card .pm-history-time { display:block; margin-top:2px; color:var(--ink-muted); font-size:12px; overflow-wrap:anywhere; }
.pm-payment-card__amount { display:flex; align-items:baseline; gap:5px; color:var(--ink); font-variant-numeric:tabular-nums; white-space:nowrap; }
.pm-payment-card__amount small { color:var(--ink-muted); font-size:11px; font-weight:600; }
.pm-payment-card__amount strong { font-size:17px; font-weight:750; }
.pm-payment-card__badges { display:flex; justify-content:flex-end; }
.pm-payment-posted { color:var(--ink-muted); font-size:12px; }
.pm-payment-card__details { display:grid; grid-template-columns:minmax(0,1.5fr) minmax(0,1fr) minmax(0,1fr) auto; align-items:center; gap:12px; min-width:0; padding-top:10px; border-top:1px solid var(--pm-transaction-divider,var(--border)); }
.pm-payment-card__reference,.pm-payment-card__method { display:grid; gap:3px; min-width:0; }
.pm-payment-card__record-label { color:var(--ink-muted); font-size:10px; font-weight:700; letter-spacing:.045em; text-transform:uppercase; }
.pm-payment-card .pm-ledger-record,.pm-payment-card .pm-ledger-method { color:var(--ink); font-size:13px; font-weight:650; overflow-wrap:anywhere; }
.pm-payment-card .pm-ledger-method { font-weight:550; }
.pm-payment-card .pm-history-actions { display:flex; align-items:center; justify-content:flex-end; gap:4px; }
.pm-payment-card .pm-modal-icon { width:34px; height:34px; flex:none; border:0; border-radius:8px; background:transparent; color:var(--ink-muted); }
.pm-payment-card .pm-modal-icon:is(:hover,:focus-visible) { background:color-mix(in srgb,var(--brand) 18%,transparent); color:var(--ink); outline:none; box-shadow:none; }
.pm-payment-card .pm-modal-icon:focus-visible { background:color-mix(in srgb,var(--brand) 28%,transparent); }
.pm-payment-card__correction { padding-top:9px; border-top:1px solid var(--pm-transaction-divider,var(--border)); }
.pm-payment-card__correction-actions { display:flex; align-items:center; justify-content:space-between; gap:10px 14px; flex-wrap:wrap; }
.pm-payment-card__correction-link { position:relative; width:max-content; max-width:100%; }
.pm-correction-history-trigger { display:inline-flex; min-height:34px; align-items:center; justify-content:center; gap:7px; padding:7px 10px; border:0; border-radius:8px; background:color-mix(in srgb,var(--brand) 9%,var(--card)); color:var(--ink); font:inherit; font-size:12px; font-weight:650; line-height:1.25; cursor:pointer; transition:background-color .16s ease,color .16s ease; }
.pm-correction-history-trigger > span { display:inline-grid; min-width:20px; min-height:20px; place-items:center; padding:0 5px; border-radius:999px; background:color-mix(in srgb,var(--brand) 16%,var(--card)); color:var(--ink); font-size:11px; font-weight:750; }
.pm-correction-history-trigger:hover { background:color-mix(in srgb,var(--brand) 16%,var(--card)); }
.pm-correction-history-trigger:focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
.pm-correction-history-content { width:100%; padding:0 !important; border:0 !important; background:transparent !important; }
.pm-correction-history-overview { display:grid; gap:12px; margin-bottom:16px; padding:14px; border-radius:10px; background:color-mix(in srgb,var(--brand) 8%,var(--card)); }
.pm-correction-history-overview__title { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
.pm-correction-history-overview__title > div { display:grid; gap:4px; }
.pm-correction-history-overview__title small,.pm-correction-history-overview__payment small { color:var(--ink-muted); font-size:11px; }
.pm-correction-history-overview__title strong { color:var(--ink); font-size:16px; }
.pm-correction-history-overview__title > span { color:var(--ink-muted); font-size:12px; }
.pm-correction-history-overview__payment { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; padding-top:10px; border-top:1px solid var(--pm-transaction-divider,var(--border)); }
.pm-correction-history-overview__payment > span { display:grid; gap:3px; min-width:0; }
.pm-correction-history-overview__payment strong { color:var(--ink); font-size:12px; font-weight:650; overflow-wrap:anywhere; }
.pm-correction-history-content .pw-correction-summary-counts { margin:0; }
.pm-correction-history-content .pm-correction-history-list { margin:14px 0 0; }
.pm-correction-history-content .pm-correction-meta { margin-bottom:10px; }
.pm-correction-history-content .pm-correction-entry-footer { justify-content:flex-start; }
.pm-ledger-review-tooltip { position:absolute; z-index:8; left:0; bottom:calc(100% + 8px); width:min(290px,calc(100vw - 64px)); padding:10px 12px; border-radius:9px; background:#fff5f4; color:#7f1d1d; font-size:12px; line-height:1.45; box-shadow:0 5px 18px rgb(30 41 35 / 12%); opacity:0; visibility:hidden; transform:translateY(4px); pointer-events:none; transition:opacity .14s ease,transform .14s ease,visibility .14s ease; }
.pm-payment-card__correction-link:is(:hover,:focus-within) .pm-ledger-review-tooltip { opacity:1; visibility:visible; transform:translateY(0); }
html[data-theme="dark"] .pm-ledger-review-tooltip { background:#3b2425; color:#fecaca; box-shadow:0 5px 18px rgb(0 0 0 / 28%); }
.pm-payment-card.is-pending { border:2px solid #f3d48f; background:color-mix(in srgb,#f59e0b 6%,var(--card)); }
.pm-payment-card.is-approved { border-color:color-mix(in srgb,#047857 28%,var(--pm-transaction-divider)); }
.pm-payment-card.is-rejected { border-color:color-mix(in srgb,#b91c1c 28%,var(--pm-transaction-divider)); }
.pm-payment-card.is-voided { background:color-mix(in srgb,#b91c1c 4%,var(--card)); }
.pm-payment-card.is-approved .pm-payment-card__amount strong { color:#047857; }
.pm-payment-card.is-rejected .pm-payment-card__amount strong { color:#b91c1c; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal [data-pm-history-footer] { display:flex; width:100%; flex:1 1 100%; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-print { box-sizing:border-box; display:flex; flex:1 1 100%; width:100%; min-width:100%; align-items:center; justify-content:center; gap:8px; padding:12px; border-radius:9px; background:var(--brand); color:var(--card); font-size:13px; font-weight:600; text-decoration:none; }
html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-print:is(:hover,:focus-visible) { background:color-mix(in srgb,var(--brand) 78%,var(--ink)); outline:none; box-shadow:none; }
@media(max-width:560px) {    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-summary { grid-template-columns:1fr; }
    .pm-payment-card__details { grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto; gap:10px; }
    .pm-payment-card__reference { grid-column:1 / -1; }
}
@media(max-width:480px) {    html[data-theme] body.panel-shell-body .pm-transactions-backdrop .pm-modal.pm-transactions-modal .pm-ledger-context { grid-template-columns:1fr; gap:14px; }
    .pm-ledger-section-head { flex-wrap:wrap; }
    .pm-payment-card { padding:12px; }
    .pm-payment-card__primary { grid-template-columns:minmax(0,1fr) auto; gap:8px; }
    .pm-payment-card__badges { grid-column:1 / -1; justify-content:flex-start; }
    .pm-payment-card__amount strong { font-size:16px; }
    .pm-correction-history-overview__payment { grid-template-columns:1fr; gap:9px; }
}
.pm-ledger-correction-meta { display:flex; flex-wrap:wrap; align-items:center; gap:6px 12px; max-width:calc(100cqw - 20px); }
.pm-ledger-status { display:inline-block; padding:3px 7px; border-radius:6px; font-size:12px; line-height:1.4; }
.pm-ledger-status[data-state="voided"] { color:#842d27; background:#fbe4e1; }
.pm-ledger-status[data-state="pending"] { color:#704606; background:#ffefcf; }
.pm-ledger-status[data-state="approved"] { color:var(--pm-ledger-paid-ink); background:color-mix(in srgb,var(--pm-ledger-paid-ink) 12%,transparent); }
.pm-ledger-status[data-state="rejected"] { color:#842d27; background:#fbe4e1; }
.pm-ledger-status[data-state="withdrawn"] { color:var(--ink-muted); background:var(--pm-transaction-heading); }
html[data-theme="dark"] .pm-ledger-status[data-state="voided"] { color:#ffd0cb; background:#512e30; }
html[data-theme="dark"] .pm-ledger-status[data-state="pending"] { color:#ffe0a3; background:#4c3b21; }
html[data-theme="dark"] .pm-ledger-status[data-state="rejected"] { color:#ffd0cb; background:#512e30; }
.pm-ledger-request-link { display:inline-flex; align-items:center; gap:6px; font-weight:700; text-decoration:none; }
.pm-ledger-request-link:is(:hover,:focus-visible) { outline:none; text-decoration:underline; text-underline-offset:2px; }
.pm-ledger-related { color:var(--ink-muted); font-size:12px; overflow-wrap:anywhere; }
.pm-ledger-related strong { color:var(--ink); font-weight:600; white-space:nowrap; }
</style>
