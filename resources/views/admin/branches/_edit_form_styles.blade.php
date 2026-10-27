<style>
/* Loaded on both the standalone edit route and the AJAX branch modal. */
:is(#branchModalContent .branch-edit-form, .branch-edit-form) {
    width:100%; max-width:640px; margin-inline:auto; color:var(--ink);
    font-family:var(--font-body,'Plus Jakarta Sans','Segoe UI',sans-serif);
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-panel {
    border:1px solid var(--border) !important; border-radius:1rem !important;
    background:var(--card) !important; overflow:hidden; box-shadow:none !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-header {
    display:flex; align-items:flex-start; justify-content:space-between; gap:1rem;
    padding:1.25rem 1.5rem !important; border-bottom:1px solid var(--border) !important;
    background:var(--surface-muted) !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-heading { display:flex; align-items:flex-start; gap:.75rem; min-width:0; }
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-heading-icon {
    display:grid; place-items:center; flex:none; width:2.5rem; height:2.5rem;
    border-radius:.7rem; background:#1b4332; color:#fff; font-size:1.1rem;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-heading h2 {
    margin:0; color:var(--ink) !important; font-size:1.15rem !important; line-height:1.3; font-weight:700 !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-heading p {
    margin:.2rem 0 0; color:var(--ink-muted) !important; font-size:.8rem; line-height:1.45; font-weight:400;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-modal-close {
    display:grid; place-items:center; flex:none; width:2.5rem; height:2.5rem;
    border:1px solid var(--border) !important; border-radius:.65rem !important;
    background:var(--card) !important; color:var(--ink) !important; box-shadow:none !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-body {
    display:grid; gap:1rem; padding:1.5rem !important; background:var(--card) !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-fields {
    display:grid; grid-template-columns:minmax(0,1fr) minmax(0,2fr); gap:1rem !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-field { min-width:0; }
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-field--wide { grid-column:1/-1; }
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-field .label-section {
    display:block; margin:0 0 .45rem; color:var(--ink) !important; font-size:.75rem !important; font-weight:680 !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-field .form-input {
    width:100%; min-height:2.85rem; padding:.65rem .85rem; border:1px solid var(--border) !important;
    border-radius:.65rem; background:var(--card) !important; color:var(--ink) !important;
    font-weight:500; box-shadow:none !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-field .form-input:focus {
    border-color:#1b4332 !important; outline:2px solid rgb(27 67 50 / 18%); outline-offset:1px;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-code {
    background:var(--surface-muted) !important; font-family:'JetBrains Mono',Consolas,monospace;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-help {
    margin:.4rem 0 0; color:var(--ink-muted) !important; font-size:.72rem; line-height:1.45; font-weight:400;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-status {
    display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.1rem;
    border:1px solid var(--border) !important; border-radius:.75rem; background:var(--surface-muted) !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-status-title { color:var(--ink); font-size:.85rem; font-weight:700; }
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-status p {
    margin:.25rem 0 0; color:var(--ink-muted) !important; font-size:.75rem; line-height:1.45; font-weight:400;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-status .status-badge { flex:none; }
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-footer {
    display:flex; align-items:center; justify-content:flex-end; gap:.6rem; padding:1rem 1.5rem;
    border-top:1px solid var(--border) !important; background:var(--card) !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-footer :is(a,button) {
    display:inline-flex; align-items:center; justify-content:center; min-width:6.5rem; min-height:2.7rem;
    border-radius:.55rem; box-shadow:none !important; text-decoration:none;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-footer .btn-outline {
    background:var(--surface-muted) !important; border:1px solid var(--border) !important; color:var(--ink) !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-footer .btn-primary-custom {
    background:#1b4332 !important; border:1px solid #1b4332 !important; color:#fff !important;
}
:is(#branchModalContent .branch-edit-form, .branch-edit-form) :is(button,a,input):focus-visible {
    outline:2px solid #1b4332; outline-offset:2px;
}
html[data-theme='dark'] :is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-panel,
html[data-theme='dark'] :is(#branchModalContent .branch-edit-form, .branch-edit-form) :is(.branch-edit-body,.branch-edit-footer) {
    background:#112218 !important; border-color:#29523d !important;
}
html[data-theme='dark'] :is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-header,
html[data-theme='dark'] :is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-status {
    background:#1b3527 !important; border-color:#29523d !important;
}
html[data-theme='dark'] :is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-field .form-input {
    background:#14261d !important; color:#f8fafc !important; border-color:#41634f !important;
}
html[data-theme='dark'] :is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-code { background:#1b3527 !important; }
@media(max-width:560px) {
    :is(#branchModalContent .branch-edit-form, .branch-edit-form) :is(.branch-edit-header,.branch-edit-body,.branch-edit-footer) { padding:1rem !important; }
    :is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-fields { grid-template-columns:minmax(0,1fr); }
    :is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-status { align-items:flex-start; flex-direction:column; }
    :is(#branchModalContent .branch-edit-form, .branch-edit-form) .branch-edit-footer :is(a,button) { flex:1; }
}
</style>
