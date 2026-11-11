<style>
html[data-theme] body .page-content :is(.cc-form,.aoc-form,.fbc-form) { max-width:720px; padding:0; color:var(--color-text-primary); }
html[data-theme] body .page-content :is(.cc-card,.aoc-card,.fbc-card) { border:1px solid var(--color-border); border-radius:14px; background:var(--color-bg-surface); }
html[data-theme] body .page-content :is(.cc-head,.aoc-head,.fbc-head) { padding:20px; background:var(--color-bg-muted); border-bottom:1px solid var(--color-border); }
html[data-theme] body .page-content :is(.cc-title,.aoc-title,.fbc-title) { font-size:20px; font-weight:700; color:var(--color-text-primary); }
html[data-theme] body .page-content :is(.cc-title,.aoc-title,.fbc-title) > i { display:grid; flex:0 0 36px; place-items:center; width:36px; height:36px; border:1px solid var(--border); border-radius:10px; background:var(--surface-muted); color:var(--brand); font-size:17px; }
html[data-theme] body .page-content :is(.cc-copy,.aoc-copy,.fbc-copy,.cc-help) { font-weight:400; font-size:13px; color:var(--color-text-secondary); line-height:1.6; }
html[data-theme] body .page-content :is(.cc-body,.aoc-body,.fbc-body) { padding:20px; gap:18px; }
html[data-theme] body .page-content :is(.cc-label,.aoc-label,.fbc-label) { text-transform:none; letter-spacing:0; font-size:13px; font-weight:600; color:var(--color-text-primary); }
html[data-theme] body .page-content :is(.cc-form,.aoc-form,.fbc-form) :is(.form-input,.form-select,.cc-money,.aoc-money) { border-radius:8px; background:var(--color-bg-surface); color:var(--color-text-primary); border-color:var(--color-border); min-height:44px; font-weight:400; }
html[data-theme] body .page-content :is(.cc-grid,.aoc-grid,.fbc-grid) { grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
html[data-theme] body .page-content :is(.cc-actions,.aoc-actions,.fbc-actions) { display:flex; align-items:center; justify-content:flex-end; gap:10px; position:sticky; bottom:0; padding:16px 20px; border-top:1px solid var(--color-border); background:var(--color-bg-surface); }
html[data-theme] body .page-content :is(.cc-primary,.aoc-primary,.fbc-primary) { min-height:44px; padding:10px 18px; border-radius:8px; background:var(--color-primary); color:#fff; }
html[data-theme] body .page-content :is(.cc-secondary,.aoc-secondary,.fbc-secondary) { min-height:44px; padding:10px 18px; border-radius:8px; border:1px solid var(--color-border); color:var(--color-text-primary); background:var(--color-bg-surface); }
@media(max-width:480px) { html[data-theme] body .page-content :is(.cc-grid,.aoc-grid,.fbc-grid) { grid-template-columns:minmax(0,1fr); } }

/* Modal-frame presentation only. The existing fields and server validation stay intact. */
html[data-theme] body.service-catalog-form-frame .page-content { min-height:0; background:var(--card) !important; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-form,.aoc-form,.fbc-form) { max-width:none; margin:0; padding:0; font:400 14px/1.5 var(--font-body); }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-card,.aoc-card,.fbc-card) { border:0 !important; border-radius:0 !important; background:transparent !important; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-body,.aoc-body,.fbc-body) { gap:14px; margin:0; padding:24px; border:0; border-radius:0; background:transparent; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-grid,.aoc-grid,.fbc-grid) { grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-label,.aoc-label,.fbc-label) { margin-bottom:5px; color:var(--ink) !important; font-size:13px; font-weight:600; text-transform:none; letter-spacing:0; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-form,.aoc-form,.fbc-form) :is(.form-input,.form-select,textarea) { min-height:44px; padding:10px 13px; border:1px solid var(--border) !important; border-radius:8px; background:#fff !important; color:var(--ink) !important; font-size:14px; font-weight:400; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-form,.aoc-form,.fbc-form) textarea { min-height:80px; line-height:1.5; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-money,.aoc-money) { min-height:44px; border:1px solid var(--border) !important; border-radius:8px; background:#fff !important; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-money,.aoc-money) .form-input { border:0 !important; background:transparent !important; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-form,.aoc-form,.fbc-form) :is(.form-input,.form-select,textarea):focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-money,.aoc-money):focus-within { outline:2px solid var(--brand); outline-offset:2px; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-help,.aoc-note,.fbc-note) { color:var(--ink-muted) !important; font-size:12px; font-weight:400; line-height:1.55; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-label,.aoc-label,.fbc-label) [aria-hidden="true"] { color:#B42318; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.aoc-note,.fbc-note) { margin-top:8px; padding:9px 12px; border:1px solid var(--border) !important; border-radius:8px; background:var(--surface-muted) !important; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-actions,.aoc-actions,.fbc-actions) { position:sticky; bottom:0; z-index:2; display:flex; justify-content:flex-end; align-items:center; gap:10px; padding:16px 24px; border-top:1px solid rgb(174 199 183 / 60%); background:rgb(255 255 255 / 40%) !important; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-primary,.aoc-primary,.fbc-primary,.cc-secondary,.aoc-secondary,.fbc-secondary) { min-height:44px; padding:9px 16px; border-radius:8px; font-size:13px; font-weight:600 !important; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-primary,.aoc-primary,.fbc-primary) { border-color:#2D6A4F !important; background:#2D6A4F !important; color:#fff !important; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-secondary,.aoc-secondary,.fbc-secondary) { border-color:#AEC7B7 !important; background:rgb(255 255 255 / 75%) !important; color:var(--ink) !important; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-primary,.aoc-primary,.fbc-primary,.cc-secondary,.aoc-secondary,.fbc-secondary):focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
html[data-theme='dark'] body.service-catalog-form-frame .page-content :is(.cc-form,.aoc-form,.fbc-form) :is(.form-input,.form-select,textarea) { background:#07130E !important; border-color:#41634F !important; color:#F8FAFC !important; }
html[data-theme='dark'] body.service-catalog-form-frame .page-content { background:var(--card) !important; }
html[data-theme='dark'] body.service-catalog-form-frame .page-content :is(.cc-actions,.aoc-actions,.fbc-actions) { background:rgb(0 0 0 / 10%) !important; border-color:#1B4332; }
html[data-theme='dark'] body.service-catalog-form-frame .page-content :is(.cc-money,.aoc-money) { background:#07130E !important; border-color:#41634F !important; }
html[data-theme='dark'] body.service-catalog-form-frame .page-content :is(.cc-label,.aoc-label,.fbc-label) [aria-hidden="true"] { color:#FDA29B; }
html[data-theme='dark'] body.service-catalog-form-frame .page-content :is(.cc-secondary,.aoc-secondary,.fbc-secondary) { background:#142B20 !important; color:#F8FAFC !important; border-color:#41634F !important; }
html[data-theme='dark'] body.service-catalog-form-frame .page-content :is(.cc-primary,.aoc-primary,.fbc-primary) { background:#1B4332 !important; border-color:#41634F !important; }
@media(max-width:480px) {
    html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-body,.aoc-body,.fbc-body) { padding:16px; }
    html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-grid,.aoc-grid,.fbc-grid) { grid-template-columns:minmax(0,1fr); }
    html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-actions,.aoc-actions,.fbc-actions) { flex-wrap:wrap; padding:14px 18px; }
}
/* Prototype arrangement: name first, paired configuration fields, then notes. */
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-body,.aoc-body) { grid-template-columns:repeat(2,minmax(0,1fr)); }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-grid,.aoc-grid) { display:contents; }
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-grid,.aoc-grid):first-child > div:first-child { grid-column:1 / -1; }
html[data-theme] body.service-catalog-form-frame .page-content .cc-body > div:not(.cc-grid) { grid-column:1 / -1; }
html[data-theme] body.service-catalog-form-frame .page-content .cc-body > div:has(#casket-material) { grid-column:auto; }
html[data-theme] body.service-catalog-form-frame .page-content .aoc-grid > div:has(#addon-unit),
html[data-theme] body.service-catalog-form-frame .page-content .aoc-body > div:has(#addon-description) { grid-column:1 / -1; }
html[data-theme] body.service-catalog-form-frame .page-content .fbc-grid { grid-template-columns:minmax(0,1fr); }
@media(max-width:480px) {
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-body,.aoc-body) { grid-template-columns:minmax(0,1fr); }
}
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-form,.aoc-form,.fbc-form) :is(input,select,textarea)[aria-invalid="true"],
html[data-theme] body.service-catalog-form-frame .page-content :is(.cc-money,.aoc-money):has([aria-invalid="true"]) { border-color:#B42318 !important; }
html[data-theme='dark'] body.service-catalog-form-frame .page-content :is(.cc-form,.aoc-form,.fbc-form) :is(input,select,textarea)[aria-invalid="true"],
html[data-theme='dark'] body.service-catalog-form-frame .page-content :is(.cc-money,.aoc-money):has([aria-invalid="true"]) { border-color:#FDA29B !important; }
html[data-theme] body.service-catalog-form-frame .page-content .cc-form input[type="checkbox"][name="is_available"] { appearance:none; position:relative; flex:0 0 44px; width:44px; height:24px; min-height:24px; padding:0; border:1px solid var(--border); border-radius:99px; background:var(--surface-muted) !important; background-image:none !important; cursor:pointer; }
html[data-theme] body.service-catalog-form-frame .page-content .cc-form input[type="checkbox"][name="is_available"]::before { content:""; position:absolute; top:3px; left:3px; width:16px; height:16px; border-radius:50%; background:var(--ink-muted); }
html[data-theme] body.service-catalog-form-frame .page-content .cc-form input[type="checkbox"][name="is_available"]:checked { background:#1B4332 !important; }
html[data-theme] body.service-catalog-form-frame .page-content .cc-form input[type="checkbox"][name="is_available"]:checked::before { transform:translateX(20px); background:#fff; }
html[data-theme] body.service-catalog-form-frame .page-content .cc-availability-label { width:100%; justify-content:space-between; flex-direction:row-reverse; }
html[data-theme] body.service-catalog-form-frame .page-content .cc-availability-label + .cc-help { margin-right:60px; }
</style>
