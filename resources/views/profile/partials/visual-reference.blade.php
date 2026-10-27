<style>
html[data-theme] body.panel-shell-body .prof-shell { color:var(--ink); font-family:var(--font-body); }
html[data-theme] body.panel-shell-body :is(.prof-section-card,.prof-hero-card,.prof-modal-card,.prof-sheet) { background:var(--card) !important; border-color:var(--border) !important; }
html[data-theme] body:is(.system-admin-ui,.owner-ui) :is(.prof-section-card,.prof-hero-card,.prof-modal-card,.prof-sheet) { border-radius:12px; box-shadow:none !important; }
html[data-theme] body.panel-shell-body :is(.prof-section-head,.prof-modal-head,.prof-modal-foot) { background:var(--surface-muted) !important; border-color:var(--border) !important; }
html[data-theme] body.panel-shell-body :is(.prof-value,.prof-name,.prof-section-title,.prof-modal-title) { color:var(--ink) !important; }
html[data-theme] body:is(.system-admin-ui,.owner-ui) :is(.prof-value,.prof-name,.prof-section-title,.prof-modal-title) { font-family:var(--font-heading); }
html[data-theme] body.panel-shell-body :is(.prof-label,.prof-email,.prof-modal-desc,.prof-lock-note) { color:var(--ink-muted) !important; }
html[data-theme] body.panel-shell-body :is(.prof-field,.prof-security-row) { background:var(--card) !important; border-color:var(--border) !important; }
html[data-theme] body.panel-shell-body .prof-lock-note { background:var(--surface-muted) !important; border-color:var(--border) !important; }
html[data-theme] body.panel-shell-body .prof-modal-close { background:var(--surface-muted); color:var(--ink); border-color:var(--border); }
html[data-theme] body:is(.system-admin-ui,.owner-ui) .prof-modal-close { width:44px; height:44px; }
html[data-theme] body.panel-shell-body .prof-overlay { background:var(--modal-scrim,rgba(15,23,42,.72)); }
body:is(.system-admin-ui,.owner-ui):has(.prof-overlay.is-open) .page-content { overflow:hidden !important; }
body:is(.system-admin-ui,.owner-ui):has(.prof-overlay.is-open) .panel-scroll-top { display:none !important; }
/* Modal actions share the form surface instead of a second colored panel. */
html[data-theme] body.panel-shell-body .prof-overlay .prof-modal-foot { background:transparent !important; margin-top:20px; padding:16px 0 0; border-top:1px solid var(--border) !important; gap:10px; }
html[data-theme] body.panel-shell-body .prof-overlay .prof-modal-foot .btn { min-height:44px; margin:0; padding:10px 18px; display:inline-flex; align-items:center; justify-content:center; gap:8px; }
html[data-theme] body.panel-shell-body .prof-overlay .prof-modal-foot .btn-outline { background:transparent !important; border:1px solid var(--border) !important; color:var(--ink) !important; }
html[data-theme] body.panel-shell-body .prof-overlay .prof-modal-foot .btn-outline:hover { background:var(--surface-muted) !important; }
@media(max-width:480px) { html[data-theme] body.panel-shell-body .prof-overlay .prof-modal-foot .btn { flex:1; padding-inline:12px; } }
/* Reference layout uses existing profile data and form hooks across roles. */
html[data-theme] body.panel-shell-body .prof-shell { max-width:1120px; width:auto; margin:20px auto; padding:0 20px; gap:20px; }
html[data-theme] body.panel-shell-body .prof-hero-card { position:static; padding:24px; border-radius:16px; }
html[data-theme] body.panel-shell-body .prof-hero-body { display:flex; align-items:center; gap:20px; padding:0; margin:0; min-height:0; }
html[data-theme] body.panel-shell-body .prof-avatar { width:80px; height:80px; margin:0; border-radius:16px; flex-shrink:0; font-size:28px; box-shadow:none; }
html[data-theme] body.panel-shell-body .prof-identity { padding:0; min-width:0; }
html[data-theme] body.panel-shell-body .prof-name { font-size:24px; margin:0 0 10px; overflow-wrap:anywhere; }
html[data-theme] body.panel-shell-body .prof-detail-grid { display:grid; grid-template-columns:minmax(0,1.4fr) minmax(0,1fr); gap:20px; align-items:start; }
html[data-theme] body.panel-shell-body .prof-side-stack { display:flex; flex-direction:column; gap:20px; min-width:0; }
html[data-theme] body.panel-shell-body .prof-section-card { min-width:0; overflow:hidden; border-radius:16px; }
html[data-theme] body.panel-shell-body .prof-section-head { display:flex; align-items:center; gap:10px; padding:18px 20px; }
html[data-theme] body.panel-shell-body .prof-head-action { margin-left:auto; flex-shrink:0; min-height:40px; }
html[data-theme] body.panel-shell-body .prof-fields { margin:0; }
html[data-theme] body.panel-shell-body .prof-value { margin:6px 0 0; overflow-wrap:anywhere; font-weight:400; }
html[data-theme] body.panel-shell-body .prof-label { font-size:12px; font-weight:500; letter-spacing:0; text-transform:none; }
html[data-theme] body.panel-shell-body .prof-readonly { margin-left:auto; font-size:12px; color:var(--ink-muted); white-space:nowrap; }
html[data-theme] body.panel-shell-body .prof-lock-note { margin:0; padding:16px 20px; font-size:12px; line-height:1.5; }
html[data-theme] body.panel-shell-body .prof-security-copy { margin:4px 0 0; font-size:13px; color:var(--ink-muted); }
html[data-theme] body.panel-shell-body .prof-security-card .prof-section-head { border-bottom:0; flex-wrap:wrap; background:var(--card) !important; }
html[data-theme] body.panel-shell-body .prof-security-card .prof-head-action { flex-basis:100%; margin-left:0; justify-content:center; }
html[data-theme] body.panel-shell-body .prof-shell .prof-section-head { background:var(--card) !important; }
html[data-theme] body.panel-shell-body .prof-shell .prof-lock-note { background:var(--card) !important; }
html[data-theme] body.panel-shell-body .prof-avatar { border:0; background:var(--surface-muted); color:var(--ink); }
html[data-theme] body.panel-shell-body .prof-field { padding:18px 20px; }
html[data-theme] body.panel-shell-body .prof-section-title { font-size:15px; font-weight:600; }
html[data-theme] body.panel-shell-body .prof-overlay { padding:16px; background:var(--modal-scrim,rgba(15,23,42,.72)); }
html[data-theme] body.panel-shell-body .prof-sheet { width:min(520px,100%); max-height:calc(100dvh - 32px); }
html[data-theme] body.panel-shell-body .prof-modal-scroll { max-height:calc(100dvh - 32px); }
@media(max-width:760px) {
 html[data-theme] body.panel-shell-body .prof-shell { margin:16px 0; padding:0 16px; gap:16px; }
 html[data-theme] body.panel-shell-body .prof-detail-grid { grid-template-columns:minmax(0,1fr); gap:16px; }
 html[data-theme] body.panel-shell-body .prof-hero-card { padding:20px; }
 html[data-theme] body.panel-shell-body .prof-section-head { flex-wrap:wrap; padding:16px; }
 html[data-theme] body.panel-shell-body .prof-security-card .prof-head-action { margin-left:0; }
}
</style>
