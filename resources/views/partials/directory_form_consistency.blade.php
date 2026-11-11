@once
<style>
/* Existing directory forms, including their AJAX/modal consumers. */
html[data-theme] body.panel-shell-body :is(#branchCreateForm,#branchCreateStandaloneForm,#branchEditForm,#userCreateForm,#userEditForm) .label-section { display:block; margin-bottom:8px; color:var(--ink); font-size:14px; font-weight:500; letter-spacing:0; text-transform:none; }
html[data-theme] body.panel-shell-body :is(#branchCreateForm,#branchCreateStandaloneForm,#branchEditForm,#userCreateForm,#userEditForm) :is(.form-input,.form-select) { box-sizing:border-box; width:100%; min-height:44px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--card); color:var(--ink); font-size:14px; font-weight:400; line-height:1.5; }
html[data-theme] body.panel-shell-body :is(#branchCreateForm,#branchCreateStandaloneForm,#branchEditForm,#userCreateForm,#userEditForm) :is(.form-input,.form-select):focus-visible { outline:2px solid var(--focus-ring,var(--accent)); outline-offset:2px; }
html[data-theme] body.panel-shell-body :is(#branchCreateForm,#branchCreateStandaloneForm,#branchEditForm,#userCreateForm,#userEditForm) :is(.grid,.branch-edit-fields) { gap:20px; }
html[data-theme] body.panel-shell-body :is(#branchCreateForm,#branchCreateStandaloneForm,#branchEditForm,#userCreateForm,#userEditForm) :is(.form-hint,.branch-edit-help) { margin-top:6px; color:var(--ink-muted); font-size:13px; line-height:1.5; }
html[data-theme] body.panel-shell-body :is(#userCreateForm,#userEditForm) :is(.user-create-section,.rounded-xl.border) { padding:20px; border-radius:10px; background:transparent; }
@media(max-width:639px) { html[data-theme] body.panel-shell-body :is(#userCreateForm,#userEditForm) :is(.user-create-section,.rounded-xl.border) { padding:16px; } }
</style>
@endonce
