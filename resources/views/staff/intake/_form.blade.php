@php
    $intakeDraft = $intakeDraft ?? null;
    $intakeDraftPayload = $intakeDraftPayload ?? null;
    $draftFields = $intakeDraftPayload['fields'] ?? [];
    $intakeValue = static fn ($key, $default = null) => old($key, data_get($draftFields, $key, $default));
    $addOnReview = session('add_on_review');
    $basicDefaults = \App\Models\CustomPackageDefault::find(1);
    $customDefaultCoverage = $basicDefaults?->coverage() ?? [];
    $customDefaultRates = $basicDefaults?->rates() ?? [];
    if ($intakeDraft && !$errors->any()) {
        $initialStep = max(1, min(5, (int) $intakeDraft->current_step));
    }
    if (is_array($addOnReview)) {
        $initialStep = 4;
    }
    if ($intakeValue('viewing_type') === 'chapel' && $errors->hasAny(['chapel_coverage', 'chapel_add_on_id', 'chapel_address', 'package_id'])) {
        $initialStep = 3;
    }

    $showIntakePageHeading = $showIntakePageHeading ?? false;
    $intakeModal = $intakeModal ?? false;
    $isOtherEntryMode = !empty($entryMode) && $entryMode === 'other';
    $otherBranchCutoffHour = max(min((int) config('funeral.other_branch_report_cutoff_hour', 18), 23), 0);
    $todayStart = now()->copy()->startOfDay();
    $otherBranchCutoffAt = $todayStart->copy()->addHours($otherBranchCutoffHour);
    $otherBranchWindowClosed = $isOtherEntryMode && now()->gt($otherBranchCutoffAt);
    $otherBranchReportedDefault = $intakeValue('reported_at', $isOtherEntryMode
        ? now()->copy()->min($otherBranchCutoffAt)->format('Y-m-d\TH:i')
        : null);

    $initialSelectedBranchId = $intakeValue('branch_id', $defaultBranchId ?? auth()->user()->branch_id);
    $oldSelectedAddOns = array_map('strval', (array) $intakeValue('selected_add_ons', []));
    $oldAddOnQuantities = (array) $intakeValue('add_on_quantities', []);
    $addOnQuantityErrors = collect($errors->getMessages())->mapWithKeys(function ($messages, $key) {
        return preg_match('/^add_on_quantities\.(\d+)$/', (string) $key, $match)
            ? [$match[1] => $messages[0] ?? 'Enter a valid quantity.']
            : [];
    })->all();
@endphp

<div class="intake-root {{ ($showIntakePageHeading || $intakeModal) && !$isOtherEntryMode ? 'intake-reference' : '' }} w-full p-0 m-0 text-slate-800 font-sans border-0 shadow-none rounded-none">
    <style>
    /* ═══════════════════════════════════════════════════════════
       INTAKE FORM  —  Premium SaaS Design System
       Palette: Deep Navy · Slate Gray · Soft Cream
       ═══════════════════════════════════════════════════════════ */

    /* ── Scroll utility ── */
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* ── Panel shell overrides (keep form full-width inside existing layout) ── */
    html, body { margin: 0 !important; padding: 0 !important; }
    .app-shell  { width: 100% !important; }
    .main-area  { padding: 0 !important; }
    .topbar     { padding: 0 18px !important; margin: 0 !important; }
    @media (max-width: 1023px) { .topbar { padding: 0 16px !important; } }
    /* Make page-content a flex column so intake-section-shell's flex:1 is bounded */
    .page-content { padding: 0 !important; margin: 0 !important; width: 100% !important; background: transparent !important; display: flex !important; flex-direction: column !important; overflow: hidden !important; }
    .page-content > * { padding: 0 !important; margin: 0 !important; width: 100% !important; }
    .intake-root { width: 100% !important; padding: 0 !important; margin: 0 !important; background: #f3f2ef !important; font-family: inherit; flex-shrink: 0 !important; }
    html { scrollbar-gutter: auto; }

    /* ── Top header bar ── */
    .intake-top-shell {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 0 32px;
        height: 60px;
        min-height: 60px;
        background: #ffffff;
        border-bottom: 1px solid #e4e8ef;
        flex-shrink: 0;
    }

    /* ── Thin progress bar (sits between header and body) ── */
    .intake-progress-rail {
        height: 3px;
        background: #e9ecf2;
        flex-shrink: 0;
        position: relative;
        overflow: hidden;
    }
    .intake-progress-fill {
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 20%;              /* JS updates this */
        background: linear-gradient(90deg, #1b3358 0%, #2c5f9e 100%);
        border-radius: 0 2px 2px 0;
        transition: width 0.45s cubic-bezier(.4,0,.2,1);
    }

    /* ── Header internals ── */
    .intake-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .intake-brand-logo {
        width: 32px; height: 32px;
        background: #1b3358;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .intake-brand-name {
        font-size: 14.5px;
        font-weight: 700;
        color: #0d1f38;
        letter-spacing: -0.25px;
    }
    .intake-page-heading-copy {
        min-width: 0;
    }
    .intake-page-heading-copy h1 {
        margin: 0;
        color: #0d1f38;
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.25;
    }
    .intake-page-heading-copy p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 0.78rem;
        line-height: 1.45;
    }
    .intake-mode-badge {
        display: inline-flex; align-items: center;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 9.5px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.11em;
    }
    .intake-meta-shell {
        display: flex; flex-wrap: wrap; align-items: center;
        justify-content: center; gap: 0.15rem 1.1rem;
        flex: 1;
    }
    .intake-meta-divider {
        display: flex; flex-wrap: wrap;
        align-items: center; gap: 1.1rem;
    }
    .intake-meta-item {
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center; gap: 1px; min-width: 72px;
    }
    .intake-meta-label {
        font-size: 0.6rem; text-transform: uppercase;
        letter-spacing: 0.1em; font-weight: 800; color: #9baec8;
    }
    .intake-meta-value {
        font-size: 0.76rem; font-weight: 700; color: #2d3f56;
    }
    .branch-toggle {
        padding: 0; border: none; background: transparent;
        font-size: 0.76rem; font-weight: 700; color: #2d3f56;
        cursor: pointer; transition: color .15s;
    }
    .branch-toggle:hover { color: #1b3358; }
    .intake-header-right {
        display: flex; align-items: center; gap: 10px; flex-shrink: 0;
    }
    .intake-draft-label {
        font-size: 11.5px; color: #9baec8; font-weight: 500; white-space: nowrap;
    }
    .intake-exit-btn {
        font-size: 12.5px; font-weight: 600; color: #3d5268;
        border: 1.5px solid #dde3ec; padding: 6px 16px;
        border-radius: 8px; text-decoration: none;
        background: #fff; transition: all .15s; white-space: nowrap;
        cursor: pointer;
    }
    .intake-exit-btn:hover { border-color: #c0ccd9; background: #f4f7fb; color: #1b3358; }

    /* ── Two-column shell: sidebar + content ── */
    .intake-section-shell {
        display: flex; flex-direction: row;
        flex: 1; min-height: 0; overflow: hidden;
        background: transparent; border: none; border-radius: 0; box-shadow: none;
    }

    /* ── Left sidebar (step wizard) ── */
    .wizard-steps-shell {
        width: 178px; flex-shrink: 0;
        order: 2;
        display: flex; flex-direction: column; gap: 0;
        padding: 18px 0 12px;
        background: #ffffff;
        border-left: 1px solid #e4e8ef;
        overflow-y: auto; overflow-x: hidden;
    }
    .wizard-steps-group-label {
        font-size: 9px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.13em; color: #9baec8;
        padding: 0 14px 10px; border-bottom: 1px solid #f0f2f6;
        margin-bottom: 6px;
    }

    /* ── Individual step tab ── */
    .wizard-tab {
        --wsz: 28px;
        position: relative;
        display: flex; flex-direction: row; align-items: stretch;
        width: 100%; padding: 0;
        background: transparent; border: none;
        text-align: left; cursor: pointer;
    }
    .wizard-tab.is-locked { cursor: default; }
    .wizard-tab.is-locked .wz-body {
        visibility: hidden;
        pointer-events: none;
    }
    /* track column: circle + vertical line */
    .wizard-tab .wz-track {
        order: 2;
        display: flex; flex-direction: column; align-items: center;
        padding: 18px 14px 0 0; flex-shrink: 0; width: 46px;
    }
    .wizard-tab .wizard-step-number {
        width: var(--wsz); height: var(--wsz);
        border-radius: 50%;
        border: 1.5px solid #d0d9e8;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; position: relative; z-index: 2;
        font-weight: 700; font-size: 11px; color: #9baec8;
        background: #ffffff;
        transition: all .22s ease;
    }
    .wizard-tab .wz-line {
        width: 1.5px; flex: 1; min-height: 18px;
        background: #e4e8ef; margin-top: 4px;
        transition: background .3s ease;
    }
    .wizard-tab:last-child .wz-line { visibility: hidden; }
    /* text column */
    .wizard-tab .wz-body {
        order: 1;
        flex: 1; padding: 14px 10px 14px 4px;
        border-radius: 8px; margin: 6px 0 6px 6px;
        display: flex; flex-direction: column; align-items: flex-end; gap: 4px;
        text-align: right;
        transition: background .15s ease;
        min-height: 64px;
    }
    .wizard-tab:hover .wz-body { background: #f4f6fb; }
    .wizard-tab .wizard-step-label {
        font-size: 0.78rem; font-weight: 600; color: #6b7e96;
        line-height: 1.25; display: block;
    }
    .wizard-tab .wz-sub {
        font-size: 0.65rem; color: #9baec8; font-weight: 500;
        line-height: 1.3; display: block;
    }

    /* horizontal connector hidden (was for old horizontal tabs) */
    .wizard-tab::after        { display: none; }
    .wizard-tab:last-child::after { display: none; }
    .wizard-tab.completed-step::after { display: none; }

    /* Active */
    .wizard-tab.active-step .wizard-step-number {
        background: #1b3358; border-color: #1b3358; color: #ffffff;
        box-shadow: 0 4px 14px rgba(27,51,88,.32);
    }
    .wizard-tab.active-step .wz-body { background: #edf1f9; }
    .wizard-tab.active-step .wizard-step-label { color: #1b3358; font-weight: 700; }
    .wizard-tab.active-step .wz-sub { color: #5070a0; }

    /* Completed */
    .wizard-tab.completed-step .wizard-step-number {
        background: #1e3a5f; border-color: #1e3a5f; color: #ffffff; font-size: 0;
    }
    .wizard-tab.completed-step .wizard-step-number::after {
        content: "✓"; font-size: 11px; font-weight: 900;
    }
    .wizard-tab.completed-step .wz-line { background: #1e3a5f; }
    .wizard-tab.completed-step .wizard-step-label { color: #4d6480; }

    /* ── Form content wrapper ── */
    .intake-form-wrapper {
        flex: 1; min-width: 0; overflow: hidden;
        order: 1;
        display: flex; flex-direction: column;
        position: relative; background: #f3f2ef;
    }

    /* Form must be flex-column so #intakeFormContent fills and footer is anchored */
    form#intakeWizardForm {
        display: flex; flex-direction: column;
        flex: 1; min-height: 0;
    }

    /* ── Each wizard step lives in a card ── */
    .wizard-panel {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e4e8ef;
        padding: 24px 28px 22px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 4px 16px rgba(15,23,42,.03);
    }

    #intakeFormContent {
        padding: 20px 28px !important;
        flex: 1; min-height: 0; overflow-y: auto;
        display: flex; flex-direction: column; gap: 0;
    }
    #intakeFormContent > .wizard-panel:not(.hidden) {
        flex: 1 0 auto;
    }
    #intakeFormContent > .wizard-panel[data-step="1"]:not(.hidden) {
        display: flex;
        flex-direction: column;
    }
    #intakeFormContent > .wizard-panel[data-step="1"]:not(.hidden) > .space-y-4 {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    #intakeFormContent > .wizard-panel[data-step="1"]:not(.hidden) > .space-y-4 > .intake-field-section:last-child {
        flex: 1;
    }

    /* ── Section title block ── */
    .section-title-block {
        display: flex; align-items: flex-start;
        gap: 14px; margin-bottom: 18px;
    }
    .section-heading-icon {
        width: 44px; height: 44px; border-radius: 12px;
        background: #1b3358; color: #ffffff;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0;
        box-shadow: 0 4px 14px rgba(27,51,88,.26);
    }
    .section-title-text h3 {
        margin: 0; font-size: 1.2rem; font-weight: 700;
        color: #0d1f38; letter-spacing: -.02em; line-height: 1.25;
    }
    .section-title-text p {
        margin: 5px 0 0; font-size: 0.83rem;
        color: #6b7e96; font-weight: 400; line-height: 1.5;
    }

    /* ── Field labels ── */
    .field-label {
        display: block; font-size: 0.67rem; font-weight: 800;
        color: #4d6480; text-transform: uppercase;
        letter-spacing: 0.09em; margin-bottom: 0.5rem;
    }

    /* ── Inputs ── */
    .form-input, .form-textarea {
        width: 100%; border-radius: 10px;
        border: 1.5px solid #dde4ee;
        background: #ffffff;
        padding: 0.75rem 1rem;
        font-size: 0.88rem; font-weight: 400; color: #0d1f38;
        box-shadow: 0 1px 3px rgba(15,23,42,.05);
        transition: border-color .18s, box-shadow .18s, background .18s;
        font-family: inherit; box-sizing: border-box;
    }
    .form-textarea { resize: vertical; min-height: 100px; }
    .form-input:focus, .form-textarea:focus {
        border-color: #1b3358;
        box-shadow: 0 0 0 4px rgba(27,51,88,.09);
        outline: none; background: #fafcff;
    }
    .form-input.field-error,
    .form-textarea.field-error,
    .form-select.field-error {
        border-color: #e11d48 !important;
        background: #fff7f8 !important;
        color: #9f1239 !important;
        box-shadow: 0 0 0 4px rgba(225,29,72,.08) !important;
    }
    #intakeWizardForm .schedule-time-display[aria-invalid="true"] {
        border-color:#d61c42 !important;
        background:#fff1f3 !important;
        color:#831638 !important;
    }
    html[data-theme="dark"] #intakeWizardForm .schedule-time-display[aria-invalid="true"] {
        border-color:#fb7185 !important;
        background:#411f2b !important;
        color:#ffe4e9 !important;
    }
    html[data-theme] body.panel-shell-body #intakeWizardForm #intake_autosave_recovery {
        --recovery-surface:#f5eddf;--recovery-text:#493a24;--recovery-icon:#8a520c;--recovery-icon-surface:#f5dfb7;
        --recovery-primary:#b9d8c6;--recovery-primary-hover:#a5ccb5;--recovery-primary-text:#183d2d;
        --recovery-secondary:#ebe1d1;--recovery-secondary-hover:#e1d3bd;
        display:flex;align-items:flex-start;gap:12px;padding:16px;margin-bottom:18px;
        border:0 !important;border-radius:12px;background:var(--recovery-surface) !important;
    }
    #intake_autosave_recovery .recovery-icon { display:grid;place-items:center;flex:none;width:36px;height:36px;border-radius:9px;background:var(--recovery-icon-surface);color:var(--recovery-icon);font-size:18px; }
    #intake_autosave_recovery .recovery-content { flex:1;min-width:0; }
    html[data-theme] body.panel-shell-body #intake_autosave_recovery h4 { margin:0;color:var(--recovery-text) !important;font-size:16px;font-weight:700; }
    html[data-theme] body.panel-shell-body #intake_autosave_recovery p { margin:4px 0 12px;color:var(--recovery-text) !important;font-size:13px;line-height:1.5; }
    #intake_autosave_recovery .recovery-actions { display:flex;flex-wrap:wrap;gap:10px; }
    html[data-theme] body.panel-shell-body #intake_autosave_recovery button { min-height:44px;padding:8px 14px;border:0 !important;border-radius:8px;font-size:13px;font-weight:650;color:var(--recovery-text) !important;background:var(--recovery-secondary) !important; }
    html[data-theme] body.panel-shell-body #intake_autosave_recovery button:hover { background:var(--recovery-secondary-hover) !important; }
    html[data-theme] body.panel-shell-body #intake_autosave_recovery #restore_intake_autosave { background:var(--recovery-primary) !important;color:var(--recovery-primary-text) !important; }
    html[data-theme] body.panel-shell-body #intake_autosave_recovery #restore_intake_autosave:hover { background:var(--recovery-primary-hover) !important; }
    #intake_autosave_recovery button:focus-visible { outline:2px solid var(--recovery-icon);outline-offset:3px; }
    html[data-theme='dark'] body.panel-shell-body #intakeWizardForm #intake_autosave_recovery {
        --recovery-surface:#332b20;--recovery-text:#f4e5cb;--recovery-icon:#ffda96;--recovery-icon-surface:#514029;
        --recovery-primary:#365b49;--recovery-primary-hover:#42705a;--recovery-primary-text:#edfff4;
        --recovery-secondary:#45392a;--recovery-secondary-hover:#534432;
    }
    #intakeWizardForm .payment-field-note { margin-inline-start:4px;font-size:.85em;font-weight:400;opacity:.8; }
    @media(max-width:480px) { #intake_autosave_recovery .recovery-actions { flex-direction:column; } #intake_autosave_recovery button { width:100%; } }
    #intakeWizardForm .custom-coverage { margin-top:8px; }
    #intakeWizardForm .custom-coverage[hidden] { display:none !important; }
    #intakeWizardForm :is(.custom-package-service-card,#viewing_location_wrap,#chapel_charge_panel,#chapel_charge_controls,#chapel_charge_notice)[hidden] { display:none !important; }
    #intakeWizardForm .viewing-fields { display:grid;grid-column:1/-1;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 14px;align-items:start; }
    #intakeWizardForm .viewing-fields[data-chapel-viewing] { grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr)); }
    #intakeWizardForm .viewing-fields:not([data-chapel-viewing]) .viewing-cemetery { grid-column:1/-1; }
    #intakeWizardForm .viewing-type-select { position:relative; }
    #intakeWizardForm .viewing-type-select select { appearance:none;padding-inline-end:38px !important; }
    #intakeWizardForm .viewing-type-select > i { position:absolute;inset-inline-end:14px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--intake-muted); }
    #intakeWizardForm #chapel_charge_panel { grid-column:1/-1;display:grid;gap:12px; }
    #intakeWizardForm .chapel-coverage-options { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:6px; }
    #intakeWizardForm .chapel-coverage-option {
        position:relative;display:grid;grid-template-columns:18px minmax(0,1fr);align-items:start;
        gap:10px;padding:12px 14px;border-radius:8px;
        background:color-mix(in srgb,var(--intake-card-alt) 60%,var(--intake-card));color:var(--intake-text);cursor:pointer;
    }
    #intakeWizardForm .chapel-coverage-option:hover { background:var(--intake-card-alt); }
    #intakeWizardForm .chapel-coverage-option:has(input:checked) { background:var(--intake-hover); }
    #intakeWizardForm .chapel-coverage-option:has(input:focus-visible) { box-shadow:0 0 0 2px var(--intake-text); }
    #intakeWizardForm .chapel-coverage-option input[type="radio"] {
        position:absolute;inline-size:1px !important;block-size:1px !important;min-height:0 !important;
        margin:0;padding:0 !important;clip-path:inset(50%);opacity:0;
    }
    #intakeWizardForm .chapel-coverage-indicator {
        display:grid;place-items:center;inline-size:18px;block-size:18px;margin-top:2px;
        border:1px solid var(--intake-muted);border-radius:50%;
    }
    #intakeWizardForm .chapel-coverage-option input:checked + .chapel-coverage-indicator { border-color:currentColor; }
    #intakeWizardForm .chapel-coverage-option input:checked + .chapel-coverage-indicator::after {
        content:"";inline-size:8px;block-size:8px;border-radius:50%;background:currentColor;
    }
    #intakeWizardForm .chapel-coverage-option strong { font-weight:650; }
    #intakeWizardForm .chapel-coverage-option small { display:block;margin-top:3px;font-size:13px;line-height:1.4;color:var(--intake-muted); }
    #intakeWizardForm #chapel_coverage_error:is([hidden],:empty) { display:none !important; }
    #intakeWizardForm #chapel_package_hint { margin:6px 0 0;font-size:13px;color:var(--intake-muted); }
    #intakeWizardForm [data-coverage-hidden] { display:none !important; }
    #intakeWizardForm #chapel_preview_panel { margin:12px 0;padding:14px 16px;border-radius:10px;background:var(--intake-card-alt); }
    #intakeWizardForm #chapel_preview_panel h5 { margin:0 0 6px;font-size:14px;font-weight:650; }
    #intakeWizardForm #chapel_rate_readout { margin:0 0 2px;font-weight:650;color:var(--intake-text); }
    #intakeWizardForm #chapel_address_readout { margin:0 0 8px;color:var(--intake-muted); }
    #intakeWizardForm #chapel_charge_preview { margin:0; }
    #intakeWizardForm #chapel-schedule-context {
        margin-top:12px;padding:10px 0 0;border-top:1px solid var(--intake-border);border-radius:0;background:transparent;
    }
    #intakeWizardForm #chapel-schedule-context:is([data-state="conflict"],[data-state="error"]) {
        --chapel-advisory-surface:#fff1d6;--chapel-advisory-ink:#784305;--chapel-advisory-row:#fffaef;
        border:0;border-radius:8px;padding:12px 14px;background:var(--chapel-advisory-surface);color:var(--chapel-advisory-ink);
    }
    html[data-theme="dark"] #intakeWizardForm #chapel-schedule-context:is([data-state="conflict"],[data-state="error"]) {
        --chapel-advisory-surface:#3c2e1b;--chapel-advisory-ink:#ffdc9e;--chapel-advisory-row:#302418;
    }
    #intakeWizardForm .chapel-advisory-heading, #intakeWizardForm .chapel-conflict-actions { display:flex;align-items:center;flex-wrap:wrap;gap:8px 12px; }
    #intakeWizardForm .chapel-advisory-heading > i { display:none;font-size:16px; }
    #intakeWizardForm #chapel-schedule-context:is([data-state="conflict"],[data-state="error"]) .chapel-advisory-heading > i { display:block; }
    #intakeWizardForm [data-chapel-check-status] { margin:0;font-weight:650;flex:1 1 220px; }
    #intakeWizardForm [data-chapel-events] { display:grid;gap:5px;margin:9px 0 12px;padding:0;list-style:none; }
    #intakeWizardForm [data-chapel-events] li {
        display:flex;flex-wrap:wrap;justify-content:space-between;gap:4px 16px;
        padding:8px 10px;border-radius:6px;background:var(--chapel-advisory-row);
    }
    #intakeWizardForm .chapel-conflict-actions button { min-height:36px; }
    #intakeWizardForm [data-chapel-edit-wake] { background:#315d45;color:#fff; }
    #intakeWizardForm [data-chapel-edit-wake]:hover { background:#244a36; }
    #intakeWizardForm [data-chapel-check-retry] { padding:4px 0; }
    #intakeWizardForm .chapel-alternatives { margin-block:10px; }
    #intakeWizardForm .chapel-alternatives summary { cursor:pointer;color:inherit;font-weight:650; }
    #intakeWizardForm .chapel-alternatives p { margin:4px 0 8px; }
    #intakeWizardForm .chapel-alternative-options { display:flex;flex-wrap:wrap;gap:6px;margin-top:6px; }
    #intakeWizardForm .chapel-alternatives [hidden] { display:none !important; }
    #intakeWizardForm #chapel_charge_preview, #intakeWizardForm #chapel_package_feedback { font-size:13px;color:var(--intake-muted); }
    #intakeWizardForm #chapel_package_feedback { margin:0 0 10px; }
    #intakeWizardForm #chapel_charge_notice { display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:10px;background:#fff3da;color:#865315;line-height:1.5;font-size:13px; }
    #intakeWizardForm #chapel_charge_notice > i { flex:none;margin-top:2px;font-size:16px; }
    #intakeWizardForm #chapel_charge_notice strong { display:block;font-weight:600;margin-bottom:3px; }
    html[data-theme=dark] #intakeWizardForm #chapel_charge_notice { background:#3b3222;color:#f0ce91; }
    #intakeWizardForm :is(#casket-inclusion-status,#hearse-inclusion-status)[data-unavailable=true] { color:#a34e2c; }
    html[data-theme=dark] #intakeWizardForm :is(#casket-inclusion-status,#hearse-inclusion-status)[data-unavailable=true] { color:#f1c19b; }
    #intakeWizardForm #home_viewing_notice { grid-column:1/-1; }
    #intakeWizardForm #home_viewing_notice[hidden] { display:none !important; }
    #intakeWizardForm #home_viewing_notice { grid-column:1/-1; }
    #intakeWizardForm #home_viewing_notice[hidden] { display:none !important; }
    @media(max-width:640px) { #intakeWizardForm .viewing-fields, #intakeWizardForm .viewing-fields[data-chapel-viewing] { grid-template-columns:minmax(0,1fr); } }
    @media(max-width:640px) { #intakeWizardForm .chapel-coverage-options { grid-template-columns:minmax(0,1fr); } }
    #intakeWizardForm .custom-package-service-card .custom-coverage:only-of-type { align-self:start; }
    #intakeWizardForm .custom-package-service-card:has(#custom-hearse-slot) .custom-coverage { grid-column:1/-1; }
    #intakeWizardForm .custom-coverage-heading { display:flex;align-items:center;justify-content:space-between;gap:10px; }
    #intakeWizardForm .intake-package-select:has(.service-field-chip) > [data-filter-select-icon] { display:none; }
    #intakeWizardForm .intake-package-select > .service-field-chip { width:min(100%,420px); min-height:44px; padding:10px 14px; }
    #intakeWizardForm [data-viewing-context][data-extra=true] { color:#8a510d; }
    html[data-theme=dark] #intakeWizardForm [data-viewing-context][data-extra=true] { color:#f8d38a; }
    #intakeWizardForm .inline-upgrade-options:popover-open { box-shadow:var(--service-popover-shadow) !important; }
    #intakeWizardForm .inline-upgrade-options .service-popover-done { width:auto; min-height:30px; padding:4px 12px; font-size:12px; }
    #intakeWizardForm :is([data-custom-asset-trigger],[data-upgrade-trigger]).service-field-chip { padding:6px 11px; border-radius:8px; background:#dcebe2; color:#234a36; text-decoration:none; }
    #intakeWizardForm :is([data-custom-asset-trigger],[data-upgrade-trigger]).service-field-chip:is(:hover,[aria-expanded=true]) { background:#c9e0d2; }
    html[data-theme=dark] #intakeWizardForm :is([data-custom-asset-trigger],[data-upgrade-trigger]).service-field-chip { background:#294538; color:#e3f5eb; }
    html[data-theme=dark] #intakeWizardForm :is([data-custom-asset-trigger],[data-upgrade-trigger]).service-field-chip:is(:hover,[aria-expanded=true]) { background:#375c49; }
    #intakeWizardForm .custom-coverage-fields { display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px; }
    #intakeWizardForm .custom-coverage-fields label { display:flex;flex-direction:column;justify-content:space-between;gap:6px;min-width:0; }
    #intakeWizardForm .custom-coverage-fields > span { grid-column:1/-1; }
    #intakeWizardForm .custom-coverage-charge { color:#8a510d;font-size:13px; }
    html[data-theme='dark'] #intakeWizardForm .custom-coverage-charge { color:#f8d38a; }
    @media(max-width:480px) { #intakeWizardForm .custom-coverage-fields { grid-template-columns:1fr; } }
    #intakeWizardForm [data-schedule-advisory] { margin-top:10px;font-size:13px;line-height:1.45;overflow-wrap:anywhere; }
    #intakeWizardForm [data-schedule-advisory] h4 { margin:0 0 6px;font-size:13px;font-weight:550; }
    #intakeWizardForm [data-schedule-advisory] ul { display:grid;gap:4px;max-height:9rem;overflow-y:auto; }
    #intakeWizardForm [data-schedule-advisory] li,
    #intakeWizardForm .schedule-advisory-summary { padding:6px 10px;border-radius:6px; }
    #intakeWizardForm .schedule-advisory-summary { order:-1;font-size:13px;line-height:1.4; }
    #intakeWizardForm .footer-action-bar:has(.schedule-advisory-summary:not([hidden])) { flex-wrap:wrap; }
    #intakeWizardForm [data-schedule-advisory] [data-tone='match'],
    #intakeWizardForm .schedule-advisory-summary[data-tone='match'] { background:#fff3dc;color:#854900; }
    #intakeWizardForm .schedule-advisory-summary[data-tone='info'] { background:#e7f0eb;color:#365548; }
    #intakeWizardForm [data-schedule-advisory] [data-tone='same'] { background:#edf0f2;color:#465464; }
    #intakeWizardForm [data-schedule-advisory] p { margin:6px 0 0; }
    #intakeWizardForm [data-schedule-advisory] li strong { display:block;margin-top:3px; }
    #intakeWizardForm .schedule-advisory-launch { margin-top:10px; }
    #intakeWizardForm .schedule-advisory-launch > button { display:inline-flex;align-items:center;gap:7px;padding:6px 10px;border-radius:7px;background:#e7f0eb;color:#365548;text-align:left;font-size:13px;font-weight:500; }
    #intakeWizardForm .schedule-advisory-launch > button:is(:hover,[aria-expanded=true]) { background:#d9e9df; }
    #intakeWizardForm .schedule-advisory-launch > button[data-tone='match'] { background:#fff3dc;color:#854900; }
    #intakeWizardForm .schedule-advisory-launch > button[data-tone='match']:is(:hover,[aria-expanded=true]) { background:#f7e6c6; }
    #intakeWizardForm .schedule-advisory-panel { width:min(500px,calc(100vw - 24px));background:#f5f8f6;color:#334c40;overscroll-behavior:contain; }
    #intakeWizardForm .schedule-advisory-panel ul { overscroll-behavior:contain; }
    #intakeWizardForm .schedule-advisory-panel > [data-schedule-advisory]:first-child,
    #intakeWizardForm .schedule-advisory-panel > [data-schedule-advisory][hidden] + [data-schedule-advisory] { margin-top:0; }
    #intakeWizardForm .schedule-advisory-panel > p { margin:8px 0 0;font-size:13px;font-weight:400; }
    #intakeWizardForm .schedule-advisory-panel [data-tone='same'] { background:#eaf1ed;color:#4b6055; }
    html[data-theme='dark'] #intakeWizardForm .schedule-advisory-panel { background:#1d3026;color:#cfdfd5; }
    html[data-theme='dark'] #intakeWizardForm .schedule-advisory-panel [data-tone='same'] { background:#263b30;color:#bbcfc1; }
    html[data-theme='dark'] #intakeWizardForm .schedule-advisory-launch > button { background:#263b30;color:#c6dace; }
    html[data-theme='dark'] #intakeWizardForm .schedule-advisory-launch > button:is(:hover,[aria-expanded=true]) { background:#304a3c; }
    html[data-theme='dark'] #intakeWizardForm .schedule-advisory-launch > button[data-tone='match'] { background:#342718;color:#f8d38a; }
    html[data-theme='dark'] #intakeWizardForm .schedule-advisory-launch > button[data-tone='match']:is(:hover,[aria-expanded=true]) { background:#4c3923; }
    html[data-theme='dark'] #intakeWizardForm [data-schedule-advisory] [data-tone='match'],
    html[data-theme='dark'] #intakeWizardForm .schedule-advisory-summary[data-tone='match'] { background:#342718;color:#f8d38a; }
    html[data-theme='dark'] #intakeWizardForm .schedule-advisory-summary[data-tone='info'] { background:#263b30;color:#c6dace; }
    html[data-theme='dark'] #intakeWizardForm [data-schedule-advisory] [data-tone='same'] { background:#273039;color:#ccd6df; }
    @media (min-width:768px) { #intakeWizardForm .schedule-advisory-summary { order:0;margin-inline:auto; } }
    @media (max-width:767px) { #intakeWizardForm .schedule-advisory-summary { width:100%; } }
    .intake-field-error {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        margin-top: 7px;
        color: #9f1239 !important;
        font-size: 0.82rem;
        font-weight: 750;
        line-height: 1.35;
    }
    .intake-field-error::before {
        content: "\F33A";
        flex: 0 0 auto;
        color: #e11d48;
        font-family: "bootstrap-icons";
        font-size: 0.86rem;
        line-height: 1.25;
    }
    .intake-field-error.hidden {
        display: none !important;
    }
    .form-input::placeholder, .form-textarea::placeholder { color: #b0beca; font-weight: 400; }
    .intake-root input::placeholder, .intake-root textarea::placeholder { color: #b0beca !important; font-weight: 400 !important; opacity: 1; }
    .form-input[readonly], .form-input.cursor-not-allowed { background: #f4f6fb; color: #7a8fa8; cursor: not-allowed; }
    .schedule-subsection {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 6px;
        padding: 2px 0 4px;
        color: #1b3358;
        font-size: 0.74rem;
        font-weight: 900;
        letter-spacing: 0.11em;
        text-transform: uppercase;
    }
    .schedule-subsection::after {
        content: "";
        height: 1px;
        flex: 1;
        background: #e4e8ef;
    }
    .readonly-date-display {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 45px;
    }
    .readonly-date-display i { color: #6b7e96; }
    .time-input-wrap {
        position: relative;
    }
    .schedule-time-display {
        display: block;
        width: 100%;
        min-height: 45px;
        border: 1px solid #C9C5BB;
        border-radius: 10px;
        background: #FAFAF7;
        color: #333333;
        font-size: 0.92rem;
        font-weight: 800;
        padding: 0 2.6rem 0 0.95rem;
        outline: none;
        text-align: left;
        transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
    }
    .schedule-time-display::placeholder {
        color: #7A8076;
        font-weight: 700;
    }
    .schedule-time-toggle {
        position: absolute;
        right: 8px;
        top: 50%;
        z-index: 2;
        display: inline-flex;
        width: 32px;
        height: 32px;
        align-items: center;
        justify-content: center;
        transform: translateY(-50%);
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #5F685F;
        cursor: pointer;
    }
    .schedule-time-toggle:hover {
        background: #E3ECD9;
        color: #232821;
    }
    .schedule-time-periods {
        display: grid;
        grid-column: 1 / -1;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    .schedule-time-periods button {
        padding: 8px;
        border-radius:4px;
        font-size: .875rem;
        line-height:1.3;
        font-weight:600;
    }
    .schedule-time-periods button:is(:hover,[aria-pressed="true"]) { background:var(--staff-soft,var(--intake-hover)); }
    .schedule-time-display:hover { background: #F3F0E8; }
    .schedule-time-display:focus {
        background: #FAFAF7;
        border-color: #3E4A3D;
        box-shadow: 0 0 0 4px rgba(62, 74, 61, 0.18);
    }
    .time-input-wrap.is-open .schedule-time-display {
        border-color: #3E4A3D;
        box-shadow: 0 0 0 4px rgba(62, 74, 61, 0.18);
    }
    .schedule-time-popover {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        z-index: 60;
        display: none;
        overflow-y: auto;
        overscroll-behavior: contain;
        width: min(270px, calc(100vw - 32px));
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        padding: 10px;
        border: 1px solid #C9C5BB;
        border-radius: 14px;
        background: #FAFAF7;
        box-shadow: 0 18px 38px rgba(37, 43, 35, 0.18);
    }
    .time-input-wrap.is-open .schedule-time-popover { display: grid; }
    .intake-time-presets { display:grid; grid-column:1/-1; max-height:12rem; overflow-y:auto; overscroll-behavior:contain; }
    .intake-time-presets[hidden] { display: none; }
    .intake-time-presets button { padding:6px 10px; text-align:start; border-radius:6px; }
    .intake-time-presets button[hidden] { display:none !important; }
    .intake-time-presets button:is(:hover,[aria-pressed="true"]) { background:var(--staff-soft); }
    .time-column {
        min-width: 0;
        overflow: hidden;
        border: 1px solid rgba(201, 197, 187, 0.72);
        border-radius: 10px;
        background: rgba(255,255,255,0.42);
    }
    .time-column-label {
        position: sticky;
        top: 0;
        z-index: 1;
        display: block;
        padding: 8px 6px 6px;
        background: #FAFAF7;
        color: #7A8076;
        font-size: 0.64rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-align: center;
        text-transform: uppercase;
    }
    .time-column-options {
        display: flex;
        flex-direction: column;
        gap: 3px;
        max-height: 178px;
        overflow-y: auto;
        padding: 4px;
    }
    .time-option {
        min-height: 34px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #333333;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 800;
        text-align: center;
    }
    .time-option:hover, .time-option:focus {
        background: #F3F0E8;
        outline: none;
    }
    .time-option.is-selected {
        background: #3E4A3D;
        color: #FFFFFF;
    }


    /* -- UI-only field organization helpers for Client/Deceased steps -- */
    .intake-field-section {
        border: 1px solid #e4e8ef;
        border-radius: 14px;
        background: #ffffff;
        padding: 18px;
        box-shadow: 0 1px 3px rgba(15,23,42,.035);
    }
    .intake-field-section + .intake-field-section { margin-top: 16px; }
    .intake-section-kicker {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #5F685F;
    }
    .intake-section-kicker i { color: #1b3358; font-size: 13px; }
    .intake-name-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 14px;
    }
    .intake-info-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 14px;
    }
    .intake-full-field { grid-column: 1 / -1; }
    #senior_proof_wrap .payment-proof-widget { padding-block:0; border-block:0; }
    .intake-field-help {
        margin-top: 6px;
        font-size: 11px;
        line-height: 1.4;
        color: #7a8fa8;
        font-weight: 500;
    }
    .form-select-wrap { position: relative; }
    .form-select-wrap .form-input {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 2.4rem;
    }
    .form-select-wrap .form-input.field-error {
        color: #2f3a2e !important;
        padding-right: 2.75rem;
    }
    .form-select-wrap::after {
        content: "";
        position: absolute;
        right: 14px;
        top: 50%;
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 6px solid #7A8076;
        transform: translateY(-35%);
        transition: transform .14s ease, border-top-color .14s ease, opacity .14s ease;
        pointer-events: none;
    }
    .form-select-wrap:focus-within::after {
        border-top-color: #3E4A3D;
        transform: translateY(-10%);
        opacity: .95;
    }
    .form-select-wrap.is-error::after,
    .form-select-wrap:has(.form-input.field-error)::after {
        right: 16px;
        border-left-width: 5px;
        border-right-width: 5px;
        border-top-color: #6f786d;
        opacity: .72;
    }
    @media (min-width: 640px) {
        .intake-name-grid { grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.2fr); }
        .intake-info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .intake-name-grid { grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr) minmax(0, 1.1fr) 150px; }
        .intake-info-grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .intake-info-grid.deceased-meta { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 120px; }
    }
    html[data-theme='dark'] .intake-field-section {
        background: #152035 !important;
        border-color: #203050 !important;
        box-shadow: none;
    }
    html[data-theme='dark'] .intake-section-kicker { color: #7a9ec8; }
    html[data-theme='dark'] .intake-section-kicker i { color: #7ec0ff; }
    html[data-theme='dark'] .intake-field-help { color: #7a9ec8; }
    /* ── Subsection soft ── */
    .subsection-soft {
        border: 1px solid #e4e8ef; border-radius: 12px;
        background: linear-gradient(180deg,#FAFAF7 0%,#f1f5f9 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
    }

    /* ── Package cards ── */
    .package-card-item { position: relative; }
    #packageCardList .package-card-item:not(:has(.package-radio:checked)) { display: none; }
    #packageCardList .package-card { cursor: default; transform: none; box-shadow: none !important; }
    .inline-package-body { display: grid; gap: 16px; }
    .inline-package-header {
        display: flex;
        flex-wrap: wrap;
        align-items: start;
        justify-content: space-between;
        gap: 12px 24px;
    }
    .inline-package-header .pkg-price-label { margin-top: 0; }
    .inline-package-price { white-space: nowrap; }
    .inline-package-details { display: grid; gap: 12px; }
    .inline-package-items {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr));
        gap: 6px 16px;
        overflow-wrap: anywhere;
    }
    .inline-package-section {
        padding-top: 12px;
        border-top: 1px solid var(--intake-border);
    }
    .inline-package-section h5 {
        margin: 0 0 6px;
        color: var(--intake-text);
        font-weight: 650;
    }
    .inline-package-detail { color: var(--intake-muted); }
    .package-options-below {
        display: grid;
        gap: 12px;
        margin-top: 16px;
    }
    .package-upgrade-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 19rem), 1fr));
        gap: 12px;
    }
    .package-options-below:not(:has(section:not([hidden]))),
    .package-upgrade-grid:not(:has(section:not([hidden]))) {
        display: none;
    }
    .inline-inclusion-card {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 8px 16px;
        padding: 10px 12px;
        border-radius: 8px;
        background: var(--intake-card-alt);
        color: var(--intake-text);
    }
    .inline-inclusion-card.hidden { display: none; }
    .inline-inclusion-card[data-included-addon] { background: #E0ECE4; }
    .inline-inclusion-card[data-service-control="casket"],
    #regular-hearse-inclusion { background: #D5E6DB; }
    html[data-theme='dark'] .inline-inclusion-card[data-included-addon] { background: #1C3428; }
    html[data-theme='dark'] .inline-inclusion-card[data-service-control="casket"],
    html[data-theme='dark'] #regular-hearse-inclusion { background: #223C2E; }
    .included-quantity-grid {
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 14rem), 1fr));
        gap: 10px;
    }
    .included-quantity-card {
        display: grid;
        grid-template-rows: 1fr auto auto;
        align-items: start;
        gap: 6px;
        border-radius: 6px;
    }
    .included-quantity-card .inline-quantity { margin-top: 0; }
    .included-quantity-card .inline-inclusion-note { margin: 0; }
    .inline-inclusion-main { min-width: 0; }
    .inline-inclusion-name { font-weight: 650; overflow-wrap: anywhere; }
    .inline-inclusion-status {
        color: var(--feedback-success-fg, #166534);
        font-size: .8rem;
        font-weight: 650;
    }
    .inline-inclusion-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; font-size: .8rem; }
    .inline-inclusion-note { flex-basis: 100%; font-size: .8rem; }
    .inline-upgrade-button {
        padding: 7px 12px;
        border-radius: 8px;
        background: var(--intake-card-strong);
        color: var(--intake-text);
        font-weight: 650;
    }
    .inline-upgrade-button:hover { background: var(--intake-hover); }
    .inline-upgrade-options {
        padding: 6px;
        overflow: auto;
        overscroll-behavior: contain;
        border: 1px solid var(--intake-border);
        border-radius: 8px;
        background: var(--intake-card);
        color: var(--intake-text);
    }
    .inline-upgrade-options button {
        display: block;
        width: 100%;
        padding: 8px;
        border-radius: 6px;
        text-align: start;
        font-size: .875rem;
    }
    .inline-upgrade-options button:is(:hover, :focus-visible, [aria-pressed="true"]) { background: var(--intake-hover); }
    .inline-quantity {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
    }
    .inline-quantity.hidden { display: none; }
    .inline-quantity .form-input { width: 5.5rem; }
    .inline-quantity button {
        min-width: 40px;
        min-height: 40px;
        border-radius: 8px;
        background: var(--intake-card-alt);
        color: var(--intake-text);
    }
    .inline-quantity button:disabled { opacity: .45; }
    .inline-package-promotion {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 4px 16px;
        color: var(--intake-muted);
        font-size: .875rem;
    }
    .inline-package-promotion strong { color: var(--intake-text); }
    .package-card-item .package-radio {
        position: absolute; width: 1px; height: 1px;
        opacity: 0; pointer-events: none;
    }
    .package-details-btn { position: relative; z-index: 10; }
    .package-card {
        border: 1.5px solid #e4e8ef; border-radius: 14px;
        background: #ffffff; padding: 20px; cursor: pointer;
        transition: all .2s cubic-bezier(.4,0,.2,1);
        position: relative; display: flex; flex-direction: column;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        will-change: transform, box-shadow;
    }
    .package-card:hover { border-color: #1b3358; box-shadow: 0 8px 24px rgba(27,51,88,.12); transform: translateY(-2px); }
    .package-card .check-dot { opacity: 0; transform: scale(.5); border-color: #d0d9e8; transition: all .2s ease; }
    .package-card.selected { border-color: #1b3358; background: #eef3fc; box-shadow: 0 8px 28px rgba(27,51,88,.15); }
    .package-card.selected .check-dot { opacity: 1; transform: scale(1); border-color: #059669; background: #059669; }
    .package-card, .payment-type-card { will-change: transform, box-shadow; }
    .package-card:hover, .payment-type-card:hover { transform: translateY(-2px); }

    /* ── Premium Package Card redesign ── */
    .pkg-grid-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 24px; }
    .pkg-section-title { font-size: 15px; font-weight: 800; color: #0d1f38; margin: 0 0 3px; }
    .pkg-section-sub { font-size: 12px; color: #6b7e96; margin: 0; }
    .package-mode-chooser { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 14px; margin-bottom: 20px; }
    .package-mode-option { min-height: 92px; display: grid; grid-template-columns: auto minmax(0,1fr) auto; align-items: center; gap: 14px; width: 100%; padding: 16px; text-align: left; border: 1px solid #c7d8cd; border-radius: 16px; background: #faf8f5; color: #163526; transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease; }
    .package-mode-option:hover,.package-mode-option:focus-visible { border-color: #2d6a4f; background: #f1f6f3; box-shadow: 0 8px 22px rgba(27,67,50,.09); outline: none; }
    .package-mode-option[aria-checked="true"] { border-color: #2d6a4f; background: #e8f1ec; box-shadow: inset 0 0 0 1px #2d6a4f; }
    .package-mode-option__icon { display: inline-flex; width: 42px; height: 42px; align-items: center; justify-content: center; border-radius: 12px; background: #dce8e0; color: #1b4332; font-size: 18px; }
    .package-mode-option__icon.is-warm { background: #f2eadf; color: #7a4f2b; }
    .package-mode-option strong { display:block; font-size: 14px; margin-bottom: 3px; }
    .package-mode-option small { display:block; color:#596b61; font-size: 11px; line-height:1.45; }
    .package-mode-option__arrow { color:#587063; }
    .package-selection-area { margin-top: 4px; }
    .package-selection-area__heading { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid #d7e2da; }
    .package-selection-area__heading h4 { margin:0; font-size:15px; font-weight:850; color:#153827; }
    .package-selection-area__heading p { margin:3px 0 0; color:#607067; font-size:12px; }
    .package-mode-change { flex:none; border:0; background:transparent; color:#2d6a4f; font-size:11px; font-weight:800; text-decoration:underline; text-underline-offset:3px; }
    .custom-arrangement-guidance { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px 18px; padding:14px; border:1px solid #e4d8c8; border-radius:14px; background:#faf8f5; color:#55483d; font-size:12px; }
    .custom-arrangement-guidance span { display:flex; align-items:flex-start; gap:7px; }
    .custom-arrangement-guidance i { color:#2d6a4f; margin-top:1px; }

    .pkg-premium-card {
        border: 1px solid #d0e2d6 !important;
        border-radius: 16px !important;
        background: #faf8f5 !important;
        display: flex !important; flex-direction: column !important;
        padding: 0 !important;
        box-shadow: 0 1px 2px rgba(27,67,50,.04) !important;
        overflow: hidden;
        min-height: 330px;
        transition: all .22s cubic-bezier(.4,0,.2,1) !important;
    }
    .pkg-premium-card:hover { border-color: #2d6a4f !important; box-shadow: 0 10px 26px rgba(27,67,50,.10) !important; transform: translateY(-2px) !important; }
    .pkg-card-featured { border-color: #1b3358 !important; box-shadow: 0 6px 24px rgba(27,51,88,.18) !important; }
    .pkg-card-featured:hover { box-shadow: 0 12px 36px rgba(27,51,88,.22) !important; }

    .pkg-featured-item { position: relative; padding-top: 14px; }
    .pkg-badge-recommended {
        position: absolute; top: 0; left: 50%; transform: translateX(-50%);
        background: #1b3358; color: #fff;
        font-size: 9px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase;
        padding: 4px 14px; border-radius: 0 0 10px 10px; z-index: 6; white-space: nowrap;
    }

    .pkg-card-body { flex: 1; padding: 22px 22px 16px; }
    .pkg-card-title-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
    .pkg-radio-indicator { width:18px; height:18px; flex:none; border:2px solid #9aaca1; border-radius:999px; background:#fff; box-shadow:inset 0 0 0 4px #fff; }
    .pkg-premium-card:has(.package-radio:checked) .pkg-radio-indicator { border-color:#2d6a4f; background:#2d6a4f; }
    .pkg-tier-label { font-size: 10px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: #3d5a4c; margin-bottom: 5px; }
    .pkg-name { font-size: 19px; font-weight: 800; color: #163526; margin-bottom: 6px; line-height: 1.2; }
    .pkg-price-label { margin-top:12px; font-size:10px; font-weight:850; letter-spacing:.08em; text-transform:uppercase; color:#66776d; }
    .pkg-price { font: 850 30px/1 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; color: #1b4332; margin-bottom: 5px; font-variant-numeric: tabular-nums; }
    .pkg-price sub { font-size: 14px; font-weight: 700; vertical-align: super; }
    .pkg-price-note { font-size: 11px; color: #9baec8; margin-bottom: 14px; }
    .pkg-promo-badge { display: inline-block; font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; background: #d1fae5; color: #065f46; padding: 3px 8px; border-radius: 999px; margin-bottom: 10px; }
    .pkg-promo-status-note { display:grid; gap:2px; margin-top:10px; padding:8px 10px; border:1px solid #d8d2c7; border-left:3px solid #9b7653; border-radius:7px; background:#f5f1e9; color:#4f554b; font-size:11px; line-height:1.4; }
    .pkg-promo-status-note strong { color:#4f554b; font-size:10px; text-transform:uppercase; letter-spacing:.045em; }
    .pkg-promo-status-note.is-active { border-left-color:#347458; background:#edf4ee; }
    .pkg-promo-status-note.is-active strong { color:#245a3f; }
    .promotion-review-banner { margin:18px 0 22px; padding:16px; border:1px solid #d4b477; border-left:4px solid #aa7730; border-radius:10px; background:#fbf4e6; color:#433a2c; }
    .promotion-review-banner__heading { display:flex; align-items:flex-start; gap:12px; }
    .promotion-review-banner__heading > i { margin-top:2px; color:#90631f; font-size:1.2rem; }
    .promotion-review-banner__heading h4 { margin:0; font-size:1rem; font-weight:750; }
    .promotion-review-banner__heading p { margin:4px 0 0; color:#625844; font-size:.86rem; line-height:1.5; }
    .promotion-review-banner__comparison { display:grid; grid-template-columns:minmax(0,1fr) auto minmax(0,1fr); align-items:center; gap:12px; margin:14px 0; }
    .promotion-review-banner__comparison > div:not(.promotion-review-banner__arrow) { display:grid; gap:3px; padding:11px 12px; border:1px solid #e1d2b4; border-radius:8px; background:rgba(255,255,255,.55); }
    .promotion-review-banner__comparison span,.promotion-review-banner__comparison small { color:#655b49; font-size:.76rem; }
    .promotion-review-banner__comparison strong { color:#302b22; font-size:1.1rem; font-variant-numeric:tabular-nums; }
    .promotion-review-banner__arrow { color:#806b47; }
    .promotion-review-banner__acknowledgement { display:flex; align-items:flex-start; gap:9px; color:#37342d; font-size:.85rem; font-weight:650; line-height:1.45; cursor:pointer; }
    .promotion-review-banner__acknowledgement input { margin-top:3px; accent-color:#347458; }
    html[data-theme='dark'] .pkg-promo-status-note { background:#1e2b29; border-color:#455950; color:#d5e2d8; }
    html[data-theme='dark'] .pkg-promo-status-note strong { color:#e2ece4; }
    html[data-theme='dark'] .pkg-promo-status-note.is-active { background:#1c332a; border-left-color:#5caa7b; }
    html[data-theme='dark'] .pkg-promo-status-note.is-active strong { color:#9bd5ac; }
    html[data-theme='dark'] .promotion-review-banner { background:#30291e; border-color:#715b35; border-left-color:#d2a54f; color:#f0e8d6; }
    html[data-theme='dark'] .promotion-review-banner__heading > i { color:#e2bd73; }
    html[data-theme='dark'] .promotion-review-banner__heading p,
    html[data-theme='dark'] .promotion-review-banner__comparison span,
    html[data-theme='dark'] .promotion-review-banner__comparison small { color:#d0c5ad; }
    html[data-theme='dark'] .promotion-review-banner__comparison > div:not(.promotion-review-banner__arrow) { background:#382f22; border-color:#665536; }
    html[data-theme='dark'] .promotion-review-banner__comparison strong,
    html[data-theme='dark'] .promotion-review-banner__acknowledgement { color:#f3ecdc; }
    @media(max-width:560px) { .promotion-review-banner__comparison { grid-template-columns:1fr; } .promotion-review-banner__arrow { transform:rotate(90deg); justify-self:center; } }
    .pkg-card-description { margin:12px 0 0; color:#56675e; font-size:12px; line-height:1.5; }
    .pkg-inclusion-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px 14px; margin-top:16px; padding-top:14px; border-top:1px solid #dfe8e2; }
    .pkg-inclusion-list span { display:inline-flex; align-items:flex-start; gap:6px; padding:0; border-radius:0; background:transparent; color:#274c38; font-size:11px; font-weight:700; line-height:1.4; }
    .pkg-inclusion-list span i { color:#2d6a4f; margin-top:1px; }
    .pkg-inclusion-list span.is-more { color:#7a4f2b; }
    .pkg-compact-meta { display:flex; align-items:flex-start; gap:6px; margin:11px 0 0; color:#4f6257; font-size:11px; line-height:1.45; }
    .pkg-compact-meta i { color:#2d6a4f; }

    .pkg-features-list { list-style: none; padding: 0; margin: 14px 0 0; border-top: 1px solid #f0f3f8; padding-top: 14px; display: flex; flex-direction: column; gap: 7px; }
    .pkg-feature-item { display: flex; align-items: flex-start; gap: 8px; font-size: 12px; font-weight: 500; color: #333333; line-height: 1.45; }
    .pkg-feature-check { color: #059669; font-weight: 900; flex-shrink: 0; margin-top: 1px; }
    .pkg-feature-plus { color: #B87956; font-weight: 900; flex-shrink: 0; font-size: 13px; line-height: 1; }

    .pkg-card-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; padding: 14px 22px 18px; border-top:1px solid #dfe8e2; }
    .pkg-card-choice { display:inline-flex; align-items:center; gap:6px; color:#52665a; font-size:11px; font-weight:800; }
    .pkg-card-choice .is-selected { display:none; color:#1b4332; }
    .pkg-premium-card:has(.package-radio:checked) .pkg-card-choice .is-default { display:none; }
    .pkg-premium-card:has(.package-radio:checked) .pkg-card-choice .is-selected { display:inline-flex; align-items:center; gap:6px; }
    .pkg-select-btn {
        width: 100%; padding: 10px 16px; border-radius: 10px;
        font-size: 13px; font-weight: 700; text-align: center;
        border: 1.5px solid #d0d9e8; background: #fff; color: #333333;
        transition: all .18s ease; display: flex; align-items: center; justify-content: center; gap: 6px;
        pointer-events: none; user-select: none;
    }
    .pkg-card-featured .pkg-select-btn { border-color: #1b3358; color: #1b3358; background: #f4f7fd; }
    .pkg-card-custom .pkg-select-btn { border-color: #fcd34d; color: #92400e; background: #fffbeb; }

    .pkg-btn-selected { display: none; }

    /* Selected state */
    .pkg-premium-card:has(.package-radio:checked) { border-color: #2d6a4f !important; background: #edf5f0 !important; box-shadow:inset 0 0 0 1px #2d6a4f !important; }
    .pkg-premium-card:has(.package-radio:checked) .pkg-select-btn { background: #1b3358 !important; color: #fff !important; border-color: #1b3358 !important; }
    .pkg-card-custom:has(.package-radio:checked) .pkg-select-btn { background: #92400e !important; color: #fff !important; border-color: #92400e !important; }
    .pkg-premium-card:has(.package-radio:checked) .pkg-btn-unselected { display: none; }
    .pkg-premium-card:has(.package-radio:checked) .pkg-btn-selected { display: flex; align-items: center; gap: 5px; }

    /* Dark mode */
    html[data-theme='dark'] .pkg-premium-card { background: #0f241c !important; border-color: #315542 !important; }
    html[data-theme='dark'] .pkg-name, html[data-theme='dark'] .pkg-price { color: #eef8f1 !important; }
    html[data-theme='dark'] .pkg-tier-label, html[data-theme='dark'] .pkg-price-note { color: #b7cbbd !important; }
    html[data-theme='dark'] .pkg-features-list, html[data-theme='dark'] .pkg-inclusion-list, html[data-theme='dark'] .pkg-card-footer { border-color: #315542 !important; }
    html[data-theme='dark'] .pkg-feature-item { color: #d9eee0 !important; }
    html[data-theme='dark'] .pkg-card-choice { color:#b7cbbd; }
    html[data-theme='dark'] .pkg-card-choice .is-selected { color:#d9eee0; }
    html[data-theme='dark'] .pkg-select-btn { background: #10241a !important; border-color: #315542 !important; color: #d9eee0 !important; }
    html[data-theme='dark'] .pkg-premium-card:has(.package-radio:checked) { background: #173326 !important; border-color: #69a77f !important; box-shadow:inset 0 0 0 1px #69a77f !important; }
    html[data-theme='dark'] .package-mode-option, html[data-theme='dark'] .custom-arrangement-guidance { background:#10241a; border-color:#315542; color:#eef8f1; }
    html[data-theme='dark'] .package-mode-option:hover, html[data-theme='dark'] .package-mode-option:focus-visible, html[data-theme='dark'] .package-mode-option[aria-checked="true"] { background:#173326; border-color:#69a77f; }
    html[data-theme='dark'] .package-mode-option small, html[data-theme='dark'] .package-selection-area__heading p { color:#b7cbbd; }
    html[data-theme='dark'] .package-selection-area__heading { border-color:#315542; }
    html[data-theme='dark'] .package-selection-area__heading h4 { color:#f1faf4; }
    html[data-theme='dark'] .pkg-card-description, html[data-theme='dark'] .pkg-compact-meta, html[data-theme='dark'] .pkg-price-label { color:#c0d2c5; }
    html[data-theme='dark'] .pkg-inclusion-list span { background:#173326; color:#d9eee0; }
    .package-preview-drawer { border-radius:22px 0 0 22px; animation:packageDrawerIn .2s ease-out; }
    .package-preview-section { padding:16px; border:1px solid #d0e2d6; border-radius:16px; background:#fff; }
    .package-preview-section__title { display:flex; align-items:center; gap:7px; margin:0 0 12px; color:#1b4332; font-size:11px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
    .package-preview-section__title i { color:#2d6a4f; font-size:14px; }
    .package-preview-policy { padding:13px; border:1px solid #aec7b7; border-radius:12px; background:#e8f1ec; color:#183f2c; font-size:11px; line-height:1.55; }
    .package-preview-policy strong { display:block; margin-bottom:3px; }
    @keyframes packageDrawerIn { from { transform:translateX(24px); opacity:.72; } to { transform:translateX(0); opacity:1; } }
    html[data-theme='dark'] .package-preview-drawer { background:#0a1a12 !important; border-color:#315542 !important; }
    html[data-theme='dark'] .package-preview-section { background:#07130e; border-color:#315542; }
    html[data-theme='dark'] .package-preview-section__title { color:#d9eee0; }
    html[data-theme='dark'] .package-preview-policy { background:#10241a; border-color:#315542; color:#d9eee0; }
    html[data-theme='dark'] .package-preview-section .text-\[\#163526\] { color:#eef8f1 !important; }
    html[data-theme='dark'] .package-preview-section .text-\[\#596b61\] { color:#b7cbbd !important; }
    html[data-theme='dark'] .package-preview-section .bg-\[\#faf8f5\] { background:#0a1a12 !important; }
    html[data-theme='dark'] .package-preview-section .border-\[\#d0e2d6\] { border-color:#315542 !important; }
    @media (prefers-reduced-motion: reduce) { .package-preview-drawer { animation:none; } }
    @media (max-width: 639px) { .package-preview-drawer { max-width:100%; border-radius:18px 18px 0 0; margin-top:48px; height:calc(100% - 48px); } }
    @media (max-width: 639px) { .package-mode-chooser,.custom-arrangement-guidance { grid-template-columns:1fr; } .package-selection-area__heading { align-items:center; } }

    /* ── Payment type cards ── */
    .payment-type-card {
        border: 1.5px solid #e4e8ef; border-radius: 12px;
        background: #ffffff; transition: all .18s ease; cursor: pointer;
    }
    .payment-type-card:hover { border-color: #1b3358; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(27,51,88,.1); }

    /* ── Billing summary card (legacy, kept for reference) ── */
    .sticky-review-card {
        background: linear-gradient(140deg,#0d1f38 0%,#1b3358 100%);
        border-radius: 18px;
        box-shadow: 0 20px 50px rgba(13,31,56,.38);
        border: 1px solid rgba(255,255,255,.06);
    }

    /* ── Billing & Payment step — tab group ── */
    .bpay-tab-group { border: 1.5px solid #e4e8ef; border-radius: 12px; overflow: hidden; display: flex; }
    .bpay-tab-group .payment-type-card,
    .bpay-tab-group .payment-method-card { border: 0 !important; border-radius: 0 !important; box-shadow: none !important; transform: none !important; flex: 1; display: flex; align-items: center; justify-content: center; padding: 11px 0; }
    .bpay-tab-group .payment-type-card:hover,
    .bpay-tab-group .payment-method-card:hover { transform: none !important; box-shadow: none !important; background: #FAFAF7; border: 0 !important; }
    .bpay-tab-group .payment-type-card + .payment-type-card,
    .bpay-tab-group .payment-method-card + .payment-method-card { border-left: 1.5px solid #e4e8ef !important; }
    .bpay-tab-group .payment-type-card.bg-slate-50,
    .bpay-tab-group .payment-type-card.border-slate-800 { background: #f1f5f9 !important; border: 0 !important; }
    .bpay-tab-group .payment-type-card.ring-2 { box-shadow: none !important; }
    .bpay-tab-group .payment-method-card.active-tab,
    .bpay-tab-group .payment-type-card.active-tab { background: #1b3358 !important; color: #ffffff !important; border: 0 !important; }
    .bpay-tab-group .payment-method-card.active-tab span,
    .bpay-tab-group .payment-type-card.active-tab span { color: #ffffff !important; }
    .bpay-tab-group .payment-method-card.active-tab i,
    .bpay-tab-group .payment-type-card.active-tab i { color: #ffffff !important; }

    /* Toggle pill peer states (CSS fallback for Tailwind peer utilities) */
    #mark_as_paid_toggle input:checked ~ div:first-of-type { background: #1b3358 !important; border-color: #1b3358 !important; }
    #mark_as_paid_toggle input:checked ~ div:last-of-type { transform: translateX(20px); }

    /* ── Lock overlay ── */
    .intake-locked { opacity: .5; pointer-events: none; user-select: none; filter: grayscale(.1); }
    .lock-overlay {
        position: absolute; inset: 0; z-index: 20;
        background: rgba(243,242,239,.88); backdrop-filter: blur(3px);
        display: flex; align-items: flex-start; justify-content: center;
        padding-top: 3.5rem; border-radius: 0;
    }
    .lock-overlay.hidden { display: none; }

    /* ── Toasts ── */
    .toast-pop {
        left: 50% !important;
        top: calc(var(--topbar-h,72px) + 10px) !important;
        transform: translate(-50%,-8px) scale(.96);
        min-width: 280px; text-align: center; pointer-events: none;
        filter: none; border: 1px solid rgba(148,163,184,.25);
        transition: opacity .25s ease, transform .25s ease; z-index: 40 !important;
    }
    .toast-pop.toast-visible { opacity: 1 !important; transform: translate(-50%,0) scale(1); }
    .intake-toast-branch {
        left: calc(var(--sidebar-current-w, var(--sidebar-w, 86px)) + 1.25rem) !important;
        right: auto !important;
        top: calc(var(--topbar-h,72px) + 12px) !important;
        width: min(30rem, calc(100vw - 2rem)) !important;
        min-width: 0 !important;
        max-width: calc(100vw - 2rem) !important;
        text-align: left !important;
        transform: translateY(-8px) scale(.98) !important;
        background: #D3DEC9 !important;
        border: 1px solid #AEBBA8 !important;
        color: #232821 !important;
        box-shadow: none !important;
        z-index: 1600 !important;
    }
    .intake-toast-branch.toast-visible {
        transform: translateY(0) scale(1) !important;
    }
    .intake-toast-package { top: calc(var(--topbar-h,72px) + 58px) !important; }

    /* ── Footer action bar ── */
    .footer-action-bar {
        padding: 14px 32px !important;
        border-top: 1px solid #e4e8ef;
        background: #ffffff;
        display: flex; align-items: center;
        justify-content: space-between; gap: 12px;
        flex-shrink: 0; backdrop-filter: none;
    }
    #wizardPrev {
        padding: 10px 24px !important; border-radius: 10px !important;
        border: 1.5px solid #dde4ee !important; background: #ffffff !important;
        font-size: 13px !important; font-weight: 600 !important; color: #4d6480 !important;
        transition: all .15s ease !important; min-width: 100px;
    }
    #wizardPrev:hover:not(:disabled) { border-color: #c0ccd9 !important; background: #f4f7fb !important; color: #1b3358 !important; }
    #wizardPrev:disabled { opacity: .32; }
    #wizardNext {
        padding: 10px 28px !important; border-radius: 10px !important;
        background: linear-gradient(135deg,#1b3358 0%,#1e3a5f 100%) !important;
        font-size: 13px !important; font-weight: 700 !important; color: #ffffff !important;
        border: none !important;
        box-shadow: 0 4px 14px rgba(27,51,88,.3) !important;
        transition: all .18s ease !important; min-width: 160px !important;
    }
    #wizardNext:hover:not(:disabled) { background: linear-gradient(135deg,#142846 0%,#19305a 100%) !important; box-shadow: 0 6px 20px rgba(27,51,88,.42) !important; transform: translateY(-1px); }
    #saveIntakeRecord {
        padding: 10px 28px !important; border-radius: 10px !important;
        background: linear-gradient(135deg,#4F6F4D 0%,#059669 100%) !important;
        font-size: 13px !important; font-weight: 700 !important; color: #ffffff !important;
        border: none !important;
        box-shadow: 0 4px 14px rgba(5,150,105,.3) !important;
        transition: all .18s ease !important; min-width: 160px !important;
    }
    #saveIntakeRecord:hover:not(:disabled) { background: linear-gradient(135deg,#036348 0%,#4F6F4D 100%) !important; box-shadow: 0 6px 20px rgba(5,150,105,.42) !important; transform: translateY(-1px); }
    #saveIntakeRecord:disabled { opacity: .5; transform: none !important; }

    /* ── Cancel button (Botanical Suite) ── */
    #intakeCancelBtn {
        padding: 10px 20px !important; border-radius: 10px !important;
        background: #FAFAF7 !important; color: #5F685F !important;
        border: 1px solid #C9C5BB !important;
        font-size: 13px !important; font-weight: 600 !important;
        transition: all .15s ease !important; cursor: pointer;
        white-space: nowrap; flex-shrink: 0;
    }
    #intakeCancelBtn:hover { background: #F3F0E8 !important; border-color: #B8B4AA !important; }
    #intakeCancelBtn:active { background: #E8E4DC !important; }

    /* ── Cancel confirmation modal ── */
    #intakeCancelModal { background: transparent; }
    #intakeCancelModal .cancel-modal-box {
        background: #FAFAF7; border: 1px solid #C9C5BB;
        border-radius: 18px; padding: 28px;
        box-shadow: 0 20px 50px rgba(0,0,0,.18);
        max-width: 430px; width: 100%; position: relative;
    }
    #intakeCancelModal .cancel-modal-title {
        font-size: 15px; font-weight: 700; color: #3E4A3D; margin: 0 0 8px;
    }
    #intakeCancelModal .cancel-modal-msg {
        font-size: 13px; color: #5F685F; margin: 0 0 24px; line-height: 1.55;
    }
    #intakeCancelModal .cancel-modal-actions {
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    }
    #intakeCancelModalKeep,
    #intakeCancelModalDiscard,
    #intakeCancelModalSaveDraft {
        min-height: 48px; padding: 10px 16px; border-radius: 10px;
        font-size: 13px; font-weight: 600; cursor: pointer;
        transition: background .15s, border-color .15s, color .15s;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        text-align: center; white-space: nowrap;
    }
    #intakeCancelModalKeep,
    #intakeCancelModalDiscard {
        background: #FAFAF7; color: #5F685F; border: 1px solid #C9C5BB;
    }
    #intakeCancelModalKeep:hover { background: #F3F0E8; }
    #intakeCancelModalDiscard:hover { background: #FDF2F2; border-color: #D8A6A6; color: #9B3D3D; }
    #intakeCancelModalSaveDraft {
        background: #3E4A3D; color: #ffffff; border: 1px solid #3E4A3D;
        font-weight: 700; grid-column: 1 / -1;
    }
    #intakeCancelModalSaveDraft:hover { background: #344033; }
    #intakeCancelModalSaveDraft:active { background: #2F3A2E; }

    /* Botanical Suite theme overrides for cancel button */
    #intakeCancelBtn {
        background: var(--color-bg-surface, #FAFAF7) !important;
        color: var(--color-text-secondary, #5F685F) !important;
        border-color: var(--color-border, #C9C5BB) !important;
    }
    #intakeCancelBtn:hover {
        background: var(--color-bg-muted, #F3F0E8) !important;
    }

    /* ── Flatpickr ── */
    .flatpickr-calendar { margin-top: 10px !important; border-radius: 18px !important; border: 1px solid #e4e8ef !important; box-shadow: 0 16px 40px rgba(15,23,42,.15) !important; padding: 12px 12px 10px !important; width: 340px !important; max-height: calc(100vh - 24px) !important; background: #ffffff !important; overflow: auto !important; z-index: 2400 !important; }
    .flatpickr-calendar.arrowTop::before,.flatpickr-calendar.arrowTop::after,.flatpickr-calendar.arrowBottom::before,.flatpickr-calendar.arrowBottom::after { display: none !important; }
    .flatpickr-months { position: relative !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 6px 34px 12px !important; margin-bottom: 2px !important; border-bottom: 1px solid #f0f4f8 !important; }
    .flatpickr-month { height: auto !important; display: flex !important; justify-content: center !important; align-items: center !important; }
    .flatpickr-current-month { position: static !important; left: auto !important; width: auto !important; height: auto !important; padding: 0 !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; line-height: 1.2 !important; color: #0d1f38 !important; font-weight: 800 !important; white-space: nowrap !important; }
    .flatpickr-current-month .flatpickr-monthDropdown-months { appearance: none !important; -webkit-appearance: none !important; border: 1px solid #e4e8ef !important; background: #f4f7fb !important; border-radius: 10px !important; padding: 4px 28px 4px 10px !important; margin: 0 !important; font-size: 15px !important; font-weight: 800 !important; color: #0d1f38 !important; line-height: 1.2 !important; cursor: pointer !important; box-shadow: none !important; min-width: 110px !important; }
    .flatpickr-current-month input.cur-year { border: 1px solid #e4e8ef !important; background: #f4f7fb !important; border-radius: 10px !important; box-shadow: none !important; padding: 4px 8px !important; margin: 0 !important; font-size: 15px !important; font-weight: 800 !important; color: #0d1f38 !important; width: 74px !important; min-width: 74px !important; text-align: center !important; }
    .flatpickr-current-month .intake-year-dropdown { appearance: none !important; -webkit-appearance: none !important; border: 1px solid #e4e8ef !important; background: #f4f7fb !important; border-radius: 10px !important; padding: 4px 28px 4px 10px !important; margin: 0 !important; font-size: 15px !important; font-weight: 800 !important; color: #0d1f38 !important; line-height: 1.2 !important; cursor: pointer !important; box-shadow: none !important; min-width: 92px !important; }
    .flatpickr-current-month .flatpickr-monthDropdown-months:focus,.flatpickr-current-month input.cur-year:focus { outline: none !important; border-color: #1b3358 !important; box-shadow: 0 0 0 3px rgba(27,51,88,.1) !important; }
    .flatpickr-current-month .intake-year-dropdown:focus { outline: none !important; border-color: #1b3358 !important; box-shadow: 0 0 0 3px rgba(27,51,88,.1) !important; }
    .numInputWrapper { width: auto !important; min-width: 74px !important; }
    .numInputWrapper span { display: none !important; }
    .flatpickr-prev-month,.flatpickr-next-month { top: 12px !important; width: 30px !important; height: 30px !important; padding: 0 !important; display: flex !important; align-items: center !important; justify-content: center !important; color: #6b7e96 !important; border-radius: 999px !important; background: #ffffff !important; transition: all .15s ease !important; }
    .flatpickr-prev-month:hover,.flatpickr-next-month:hover { color: #0d1f38 !important; background: #f0f4f8 !important; }
    .flatpickr-prev-month svg,.flatpickr-next-month svg { display: none !important; }
    .flatpickr-prev-month::before { content: "‹"; font-size: 22px; font-weight: 700; line-height: 1; }
    .flatpickr-next-month::before { content: "›"; font-size: 22px; font-weight: 700; line-height: 1; }
    .flatpickr-weekdays { margin: 8px 0 10px !important; }
    .flatpickr-weekday { font-size: 12px !important; font-weight: 800 !important; color: #6b7e96 !important; }
    .intake-schedule-calendar {
        --schedule-event-ink:#fff;
        --schedule-today:#e2e5e4;
        --schedule-multiple:#d9e8f2;
        --schedule-dot-start:#087448;
        --schedule-dot-end:#9b4d00;
        --schedule-dot-ceremony:#2555bd;
        --schedule-dot-interment:#7a32ac;
        --schedule-range:#cce3d0;
        --schedule-ink:#152d27;
    }
    html[data-theme="dark"] .intake-schedule-calendar {
        --schedule-event-ink:#10221b;
        --schedule-today:#414846;
        --schedule-multiple:#304856;
        --schedule-dot-start:#5de5a5;
        --schedule-dot-end:#ffc36a;
        --schedule-dot-ceremony:#91baff;
        --schedule-dot-interment:#dda4ff;
        --schedule-range:#345447;
        --schedule-ink:#f3faf6;
    }
    html[data-theme] body.panel-shell-body:has(.intake-root) .flatpickr-calendar.intake-schedule-calendar .flatpickr-day[data-schedule-fill] {
        background:var(--schedule-fill) !important;
        color:var(--schedule-cell-ink, var(--schedule-ink)) !important;
        border-color:transparent !important;
        font-weight:700 !important;
    }
    html[data-theme] body.panel-shell-body:has(.intake-root) .flatpickr-calendar.intake-schedule-calendar .flatpickr-day.selected[data-schedule-fill] {
        box-shadow:0 2px 6px rgb(0 0 0 / .24);
    }
    :is(.intake-schedule-legend, .schedule-summary-events) i { display:inline-block; width:12px; height:12px; border-radius:3px; background:var(--schedule-swatch); }
    .intake-schedule-calendar [data-schedule-color="start"] { --schedule-swatch:var(--schedule-dot-start); }
    .intake-schedule-calendar [data-schedule-color="end"] { --schedule-swatch:var(--schedule-dot-end); }
    .intake-schedule-calendar [data-schedule-color="ceremony"] { --schedule-swatch:var(--schedule-dot-ceremony); }
    .intake-schedule-calendar [data-schedule-color="interment"] { --schedule-swatch:var(--schedule-dot-interment); }
    .intake-schedule-legend [data-schedule-color="today"] { --schedule-swatch:var(--schedule-today); }
    .intake-schedule-legend [data-schedule-color="range"] { --schedule-swatch:var(--schedule-range); }
    .intake-schedule-calendar .flatpickr-day { position:relative; }
    .intake-schedule-markers {
        position:absolute; inset:auto 0 3px; display:flex; justify-content:center; gap:3px; pointer-events:none;
    }
    .intake-schedule-markers i {
        width:8px; height:8px; border-radius:50%; background:var(--schedule-marker);
        box-shadow:0 1px 2px rgb(0 0 0 / .25);
    }
    .intake-schedule-legend {
        position:fixed; z-index:2401; box-sizing:border-box; display:grid; gap:6px;
        padding:14px; overflow:auto; overscroll-behavior:contain;
        border:1px solid var(--intake-border, var(--border)); border-radius:14px;
        background:var(--intake-card-alt, var(--card)); color:var(--intake-text, var(--ink));
        box-shadow:0 12px 30px rgb(0 0 0 / .14); font-size:12px; line-height:1.4;
    }
    .intake-schedule-legend[hidden] { display:none; }
    .intake-schedule-legend h4 { margin:0 0 4px; font-size:14px; font-weight:700; }
    .intake-schedule-legend > div { display:grid; grid-template-columns:12px minmax(0,1fr) auto; align-items:center; gap:8px; }
    .intake-schedule-legend [data-schedule-date] { color:var(--ink-muted); text-align:right; }
    .intake-schedule-legend [data-schedule-date] span { display:block; }
    .intake-schedule-legend .intake-schedule-key { display:flex; gap:8px; flex-wrap:wrap; }
    .intake-schedule-key span { display:inline-flex; align-items:center; gap:5px; margin-right:8px; }
    .intake-schedule-legend [data-wake-duration] { margin:3px 0 0; font-weight:650; }
    .flatpickr-day { border-radius: 10px !important; max-width: 42px !important; height: 42px !important; line-height: 42px !important; font-size: 14px !important; font-weight: 600 !important; color: #0d1f38 !important; }
    .flatpickr-day:hover { background: #f0f4f8 !important; border-color: #f0f4f8 !important; }
    .flatpickr-day.today { border: 2px solid #1b3358 !important; color: #1b3358 !important; font-weight: 800 !important; }
    .flatpickr-day.selected,.flatpickr-day.startRange,.flatpickr-day.endRange { background: #1b3358 !important; border-color: #1b3358 !important; color: #ffffff !important; border-radius: 10px !important; }
    .flatpickr-day.disabled,.flatpickr-day.prevMonthDay,.flatpickr-day.nextMonthDay { color: #d0d9e8 !important; }
    .flatpickr-time { border-top: 1px solid #e4e8ef !important; margin-top: 8px !important; padding-top: 10px !important; }
    .flatpickr-time input,.flatpickr-time .flatpickr-am-pm { border-radius: 8px !important; font-weight: 700 !important; }
    .flatpickr-calendar.open { margin-top: 12px !important; }

    /* Botanical Suite light-theme coverage */
    .intake-root,
    .intake-form-wrapper {
        background: var(--color-bg-page) !important;
        color: var(--color-text-primary);
    }

    .intake-top-shell,
    .wizard-steps-shell,
    .wizard-panel,
    .footer-action-bar,
    .intake-field-section,
    .package-card,
    .pkg-premium-card,
    .payment-type-card,
    .flatpickr-calendar {
        background: var(--color-bg-surface) !important;
        border-color: var(--color-border) !important;
    }

    .intake-progress-rail,
    .subsection-soft,
    .wizard-tab:hover .wz-body,
    .wizard-tab.active-step .wz-body,
    .form-input[readonly],
    .form-input.cursor-not-allowed,
    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month input.cur-year,
    .flatpickr-prev-month:hover,
    .flatpickr-next-month:hover,
    .flatpickr-day:hover {
        background: var(--color-bg-muted) !important;
        border-color: var(--color-border) !important;
    }

    .intake-progress-fill {
        background: linear-gradient(90deg, var(--color-primary) 0%, var(--color-accent) 100%) !important;
    }

    .intake-brand-logo,
    .section-heading-icon,
    .wizard-tab.active-step .wizard-step-number,
    .wizard-tab.completed-step .wizard-step-number,
    .pkg-badge-recommended,
    .bpay-tab-group .payment-method-card.active-tab,
    .bpay-tab-group .payment-type-card.active-tab {
        background: var(--color-primary) !important;
        border-color: var(--color-primary) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(62, 74, 61, .24) !important;
    }

    .intake-brand-name,
    .section-title-text h3,
    .pkg-section-title,
    .pkg-name,
    .pkg-price,
    .flatpickr-current-month,
    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month input.cur-year,
    .flatpickr-day {
        color: var(--color-text-primary) !important;
    }

    .intake-meta-label,
    .intake-draft-label,
    .wizard-steps-group-label,
    .wizard-tab .wz-sub,
    .section-title-text p,
    .field-label,
    .pkg-section-sub,
    .pkg-tier-label,
    .pkg-price-note,
    .intake-field-help,
    .flatpickr-weekday,
    .flatpickr-prev-month,
    .flatpickr-next-month {
        color: var(--color-text-secondary) !important;
    }

    .intake-meta-value,
    .branch-toggle,
    .wizard-tab .wizard-step-label,
    .intake-section-kicker,
    .pkg-feature-item {
        color: var(--color-text-primary) !important;
    }

    .branch-toggle:hover,
    .intake-section-kicker i,
    .wizard-tab.active-step .wizard-step-label,
    .wizard-tab.active-step .wz-sub,
    .pkg-card-featured .pkg-select-btn,
    .flatpickr-day.today {
        color: var(--color-primary) !important;
    }

    .wizard-tab .wizard-step-number,
    .pkg-features-list,
    .bpay-tab-group,
    .bpay-tab-group .payment-type-card + .payment-type-card,
    .bpay-tab-group .payment-method-card + .payment-method-card,
    .flatpickr-months,
    .flatpickr-time {
        border-color: var(--color-border) !important;
    }

    .wizard-tab .wizard-step-number {
        background: var(--color-bg-surface) !important;
        color: var(--color-text-muted) !important;
    }

    .wizard-tab .wz-line,
    .wizard-tab.completed-step .wz-line {
        background: var(--color-border) !important;
    }

    .form-input,
    .form-textarea {
        background: var(--color-bg-surface) !important;
        border-color: var(--color-border) !important;
        color: var(--color-text-primary) !important;
        box-shadow: 0 1px 3px rgba(62, 74, 61, .05) !important;
    }

    .form-input:focus,
    .form-textarea:focus,
    .flatpickr-current-month .flatpickr-monthDropdown-months:focus,
    .flatpickr-current-month input.cur-year:focus {
        border-color: var(--color-primary) !important;
        background: var(--color-bg-surface) !important;
        box-shadow: 0 0 0 4px rgba(62, 74, 61, .14) !important;
    }

    .form-input::placeholder,
    .form-textarea::placeholder,
    .intake-root input::placeholder,
    .intake-root textarea::placeholder {
        color: var(--color-text-muted) !important;
    }

    .package-card:hover,
    .pkg-premium-card:hover,
    .pkg-card-featured,
    .payment-type-card:hover,
    .pkg-premium-card:has(.package-radio:checked) {
        border-color: var(--color-primary) !important;
        box-shadow: 0 8px 24px rgba(62, 74, 61, .13) !important;
    }

    .package-card.selected,
    .pkg-premium-card:has(.package-radio:checked) {
        background: rgba(139, 154, 139, .14) !important;
    }

    .package-card.selected .check-dot,
    .pkg-premium-card:has(.package-radio:checked) .pkg-select-btn,
    #mark_as_paid_toggle input:checked ~ div:first-of-type,
    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        background: var(--color-primary) !important;
        border-color: var(--color-primary) !important;
        color: #ffffff !important;
    }

    .pkg-select-btn {
        background: var(--color-bg-surface) !important;
        border-color: var(--color-border) !important;
        color: var(--color-text-primary) !important;
    }

    .pkg-card-custom .pkg-select-btn,
    .pkg-card-custom:has(.package-radio:checked) .pkg-select-btn {
        border-color: var(--color-warning) !important;
        color: #ffffff !important;
        background: var(--color-warning) !important;
    }

    .pkg-promo-badge {
        background: rgba(111, 138, 109, .16) !important;
        color: #4F6F4D !important;
    }

    .pkg-feature-check {
        color: var(--color-success) !important;
    }

    .pkg-feature-plus {
        color: var(--color-warning) !important;
    }

    .bpay-tab-group .payment-type-card:hover,
    .bpay-tab-group .payment-method-card:hover,
    .bpay-tab-group .payment-type-card.bg-slate-50,
    .bpay-tab-group .payment-type-card.border-slate-800 {
        background: var(--color-bg-muted) !important;
    }

    .lock-overlay {
        background: rgba(232, 228, 220, .88) !important;
    }

    #intake_lock_overlay .bg-white {
        background: var(--color-bg-surface) !important;
        border-color: var(--color-border) !important;
        color: var(--color-text-primary) !important;
    }

    #wizardPrev {
        background: var(--color-bg-surface) !important;
        border-color: var(--color-border) !important;
        color: var(--color-primary) !important;
    }

    #wizardPrev:hover:not(:disabled) {
        background: var(--color-bg-muted) !important;
        border-color: var(--color-border-strong) !important;
        color: var(--color-primary-hover) !important;
    }

    #wizardNext,
    #saveIntakeRecord {
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-active) 100%) !important;
        box-shadow: 0 4px 14px rgba(62, 74, 61, .26) !important;
    }

    #wizardNext:hover:not(:disabled),
    #saveIntakeRecord:hover:not(:disabled) {
        background: linear-gradient(135deg, var(--color-primary-hover) 0%, var(--color-primary-active) 100%) !important;
        box-shadow: 0 6px 20px rgba(62, 74, 61, .34) !important;
    }

    /* Staff dashboard-aligned intake polish */
    .intake-root {
        --intake-page: #CAD8C2;
        --intake-page-deep: #BFCEB7;
        --intake-card: #D3DEC9;
        --intake-card-alt: #DCE6D6;
        --intake-card-strong: #C7D5BE;
        --intake-hover: #C5D3BC;
        --intake-active: #B8C9AF;
        --intake-border: #AEBBA8;
        --intake-border-strong: #8EA083;
        --intake-text: #232821;
        --intake-muted: #3F4C3E;
        --intake-dark: #3E4A3D;
        --intake-dark-hover: #2F3A2E;
        --intake-danger: #A84E45;
        --intake-warning: #B87956;
        display: flex !important;
        flex-direction: column !important;
        height: calc(100vh - var(--topbar-h, 0px)) !important;
        min-height: 0 !important;
        max-height: calc(100vh - var(--topbar-h, 0px)) !important;
        padding: 18px 22px !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
        background-color: var(--intake-page) !important;
        background-image:
            linear-gradient(rgba(62, 74, 61, .035) 1px, transparent 1px),
            linear-gradient(90deg, rgba(62, 74, 61, .035) 1px, transparent 1px),
            repeating-linear-gradient(135deg, rgba(62, 74, 61, .028) 0 1px, transparent 1px 18px) !important;
        background-size: 28px 28px, 28px 28px, 18px 18px !important;
        color: var(--intake-text) !important;
        font-family: var(--font-body), "DM Sans", system-ui, sans-serif !important;
    }

    .intake-root *,
    .intake-root *::before,
    .intake-root *::after {
        letter-spacing: 0 !important;
    }

    .intake-root .font-sans { font-family: var(--font-body), "DM Sans", system-ui, sans-serif !important; }
    .intake-brand-name,
    .section-title-text h3,
    .pkg-section-title,
    .pkg-name,
    .pkg-price,
    #summary_total,
    .wizard-step-label {
        font-family: var(--font-heading), "Syne", var(--font-body), sans-serif !important;
    }

    .intake-root,
    .intake-form-wrapper,
    .wizard-steps-shell,
    .wizard-panel,
    .intake-field-section,
    .subsection-soft,
    .package-card,
    .pkg-premium-card,
    .payment-type-card,
    .flatpickr-calendar,
    .footer-action-bar,
    .schedule-time-popover,
    #intakeCancelModal .cancel-modal-box,
    .intake-root .shadow-sm,
    .intake-root .shadow-md,
    .intake-root .shadow-lg,
    .intake-root .shadow-xl,
    .intake-root .shadow-2xl,
    .intake-root [class*="shadow-"] {
        box-shadow: none !important;
        filter: none !important;
        backdrop-filter: none !important;
    }

    .intake-top-shell {
        min-height: 76px !important;
        height: auto !important;
        padding: 18px 22px !important;
        background: var(--intake-card-alt) !important;
        border: 1px solid var(--intake-border) !important;
        border-radius: 8px 8px 0 0 !important;
        color: var(--intake-text) !important;
    }

    .intake-progress-rail {
        height: 4px !important;
        background: var(--intake-card-strong) !important;
        border-left: 1px solid var(--intake-border) !important;
        border-right: 1px solid var(--intake-border) !important;
    }

    .intake-progress-fill {
        background: var(--intake-dark) !important;
        border-radius: 0 !important;
    }

    .intake-brand-logo {
        width: 40px !important;
        height: 40px !important;
        border-radius: 8px !important;
        background: var(--intake-dark) !important;
        color: #fff !important;
    }

    .intake-brand-name {
        color: var(--intake-text) !important;
        font-size: 1rem !important;
        font-weight: 650 !important;
    }

    .intake-page-heading-copy h1 {
        color: var(--intake-text) !important;
        font-family: var(--font-heading), "Syne", var(--font-body), sans-serif !important;
    }

    .intake-page-heading-copy p {
        color: var(--intake-muted) !important;
    }

    .intake-mode-badge,
    .intake-meta-value,
    .branch-toggle {
        color: var(--intake-text) !important;
        font-weight: 650 !important;
    }

    .intake-meta-label,
    .intake-draft-label,
    .wizard-steps-group-label,
    .wizard-tab .wz-sub,
    .pkg-section-sub,
    .pkg-tier-label,
    .pkg-price-note,
    .intake-field-help {
        color: var(--intake-muted) !important;
        font-weight: 500 !important;
    }

    .intake-exit-btn,
    #intakeCancelBtn,
    #wizardPrev,
    #wizardNext,
    #saveIntakeRecord,
    .package-card,
    .package-details-btn,
    .payment-type-card,
    .payment-method-card,
    .time-option,
    .schedule-time-display,
    .flatpickr-day,
    .flatpickr-prev-month,
    .flatpickr-next-month,
    .branch-toggle,
    .review-edit,
    #add_additional_service_item,
    #choose_add_ons_btn,
    #cancel_add_ons_btn,
    #apply_add_ons_btn,
    [data-package-details-close],
    .wizard-tab:not(.is-locked) {
        cursor: pointer !important;
    }

    .intake-section-shell {
        flex: 1 1 0 !important;
        min-height: 0 !important;
        background: transparent !important;
        border: 1px solid var(--intake-border) !important;
        border-top: 0 !important;
        border-radius: 0 0 8px 8px !important;
        overflow: hidden !important;
    }

    .wizard-steps-shell {
        width: 220px !important;
        padding: 18px 10px !important;
        background: var(--intake-card-strong) !important;
        border-left: 1px solid var(--intake-border) !important;
    }

    .wizard-steps-group-label {
        padding: 0 10px 12px !important;
        border-bottom: 1px solid rgba(62, 74, 61, .16) !important;
        font-size: .68rem !important;
        text-transform: none !important;
    }

    .wizard-tab .wz-body {
        min-height: 58px !important;
        border-radius: 8px !important;
        margin: 5px 0 !important;
        padding: 12px 10px !important;
        align-items: flex-end !important;
        color: var(--intake-text) !important;
        transition: background-color .18s ease, color .18s ease !important;
    }

    .wizard-tab.is-locked .wz-body {
        visibility: visible !important;
        pointer-events: none !important;
        opacity: .82 !important;
    }

    .wizard-tab:hover .wz-body,
    .wizard-tab:focus-visible .wz-body {
        background: var(--intake-hover) !important;
    }

    .wizard-tab.active-step .wz-body,
    .wizard-tab.completed-step:hover .wz-body {
        background: var(--intake-active) !important;
    }

    .wizard-tab .wizard-step-label {
        color: var(--intake-text) !important;
        font-size: .84rem !important;
        font-weight: 650 !important;
    }

    .wizard-tab.active-step .wizard-step-label {
        color: var(--intake-text) !important;
        font-weight: 700 !important;
    }

    .wizard-tab .wizard-step-number {
        width: 30px !important;
        height: 30px !important;
        border-color: var(--intake-border-strong) !important;
        background: var(--intake-card-alt) !important;
        color: var(--intake-muted) !important;
        font-weight: 650 !important;
    }

    .wizard-tab.active-step .wizard-step-number,
    .wizard-tab.completed-step .wizard-step-number {
        background: var(--intake-dark) !important;
        border-color: var(--intake-dark) !important;
        color: #fff !important;
    }

    .wizard-tab .wz-line,
    .wizard-tab.completed-step .wz-line {
        background: var(--intake-border-strong) !important;
    }

    .intake-form-wrapper,
    #intakeFormContent,
    .footer-action-bar {
        background: transparent !important;
    }

    .intake-form-wrapper,
    form#intakeWizardForm {
        min-height: 0 !important;
        height: 100% !important;
    }

    #intakeFormContent {
        padding: 22px !important;
        min-height: 0 !important;
        overflow-y: auto !important;
    }

    .wizard-panel {
        border-radius: 8px !important;
        border: 1px solid var(--intake-border) !important;
        background: var(--intake-card) !important;
        padding: 24px !important;
    }

    .section-title-block {
        align-items: center !important;
        gap: 12px !important;
        margin-bottom: 20px !important;
        padding-bottom: 14px !important;
        border-bottom: 1px solid rgba(62, 74, 61, .15) !important;
    }

    .section-heading-icon {
        width: 38px !important;
        height: 38px !important;
        border-radius: 8px !important;
        background: transparent !important;
        border: 1px solid var(--intake-border-strong) !important;
        color: var(--intake-dark) !important;
        font-size: 18px !important;
    }

    .section-title-text h3,
    .pkg-section-title {
        color: var(--intake-text) !important;
        font-size: 1.15rem !important;
        font-weight: 650 !important;
    }

    .section-title-text p,
    .pkg-section-sub {
        color: var(--intake-muted) !important;
        font-size: .9rem !important;
        font-weight: 500 !important;
    }

    .intake-field-section,
    .subsection-soft,
    #optional_add_ons_section,
    #cashless_details_wrap,
    .intake-root .rounded-2xl.border,
    .intake-root .rounded-xl.border,
    .intake-root .rounded-lg.border {
        background-color: var(--intake-card-alt) !important;
        border-color: var(--intake-border) !important;
    }

    .intake-field-section {
        border-radius: 8px !important;
        padding: 18px !important;
    }

    .intake-section-kicker,
    .field-label {
        color: var(--intake-muted) !important;
        font-weight: 650 !important;
        text-transform: none !important;
        font-size: .82rem !important;
    }

    .field-label {
        margin-bottom: .45rem !important;
    }

    .intake-section-kicker i {
        color: var(--intake-dark) !important;
        font-size: 1rem !important;
    }

    .form-input,
    .form-textarea,
    .schedule-time-display,
    .readonly-date-display {
        min-height: 46px !important;
        border-radius: 8px !important;
        border: 1px solid var(--intake-border) !important;
        background: #F2F6EF !important;
        color: var(--intake-text) !important;
        font-size: .94rem !important;
        font-weight: 500 !important;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease !important;
    }

    .form-input:hover,
    .form-textarea:hover,
    .schedule-time-display:hover {
        background: #E8EFE3 !important;
        border-color: var(--intake-border-strong) !important;
    }

    .form-input:focus,
    .form-textarea:focus,
    .schedule-time-display:focus,
    .time-input-wrap.is-open .schedule-time-display {
        outline: none !important;
        background: #EEF4EA !important;
        border-color: var(--intake-dark) !important;
        box-shadow: none !important;
    }

    .form-input::placeholder,
    .form-textarea::placeholder,
    .intake-root input::placeholder,
    .intake-root textarea::placeholder {
        color: #667260 !important;
        opacity: 1 !important;
    }

    .form-input[readonly],
    .form-input.cursor-not-allowed,
    .intake-root .pointer-events-none.form-input {
        background: var(--intake-card-strong) !important;
        color: var(--intake-muted) !important;
    }

    .pkg-grid-header {
        margin-bottom: 18px !important;
        padding-bottom: 14px !important;
        border-bottom: 1px solid rgba(62, 74, 61, .15) !important;
    }

    .package-card,
    .pkg-premium-card,
    .payment-type-card,
    .payment-method-card,
    .package-detail-tab,
    .optional-add-on-row {
        border-radius: 8px !important;
        background: var(--intake-card-alt) !important;
        border-color: var(--intake-border) !important;
        color: var(--intake-text) !important;
        transform: none !important;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease !important;
    }

    .package-card:hover,
    .pkg-premium-card:hover,
    .payment-type-card:hover,
    .payment-method-card:hover,
    .package-detail-tab:hover,
    .optional-add-on-row:hover {
        background: var(--intake-hover) !important;
        border-color: var(--intake-border-strong) !important;
        transform: none !important;
    }

    .package-card.selected,
    .pkg-premium-card:has(.package-radio:checked),
    .bpay-tab-group .payment-method-card.active-tab,
    .bpay-tab-group .payment-type-card.active-tab {
        background: var(--intake-active) !important;
        border-color: var(--intake-dark) !important;
        color: var(--intake-text) !important;
    }

    .pkg-card-body,
    .pkg-card-footer {
        background: transparent !important;
    }

    .pkg-name,
    .pkg-price,
    .pkg-feature-item,
    .intake-root .text-slate-900,
    .intake-root .text-slate-800,
    .intake-root .text-slate-700,
    .intake-root .text-[#333333] {
        color: var(--intake-text) !important;
    }

    .intake-root .text-slate-600,
    .intake-root .text-slate-500,
    .intake-root .text-slate-400,
    .intake-root .text-[#5F685F] {
        color: var(--intake-muted) !important;
    }

    .pkg-price,
    #summary_total,
    #payment_status_preview,
    #payment_paid_preview,
    #payment_balance_preview {
        font-weight: 700 !important;
    }

    .pkg-select-btn,
    .package-details-btn,
    .intake-exit-btn,
    #intakeCancelBtn,
    #wizardPrev,
    #wizardNext,
    #saveIntakeRecord,
    #add_additional_service_item,
    #choose_add_ons_btn,
    #cancel_add_ons_btn,
    #apply_add_ons_btn,
    [data-package-details-close] {
        border-radius: 8px !important;
        font-weight: 650 !important;
        box-shadow: none !important;
        transform: none !important;
    }

    .package-details-btn,
    #wizardPrev,
    #intakeCancelBtn,
    #add_additional_service_item,
    #cancel_add_ons_btn,
    .intake-exit-btn {
        background: #EEF4EA !important;
        border: 1px solid var(--intake-border) !important;
        color: var(--intake-text) !important;
    }

    .package-details-btn:hover,
    #wizardPrev:hover:not(:disabled),
    #intakeCancelBtn:hover,
    #add_additional_service_item:hover,
    #cancel_add_ons_btn:hover,
    .intake-exit-btn:hover {
        background: var(--intake-hover) !important;
        border-color: var(--intake-border-strong) !important;
        color: var(--intake-text) !important;
    }

    #wizardNext,
    #saveIntakeRecord,
    #choose_add_ons_btn,
    #apply_add_ons_btn,
    #intakeCancelModalSaveDraft,
    .pkg-premium-card:has(.package-radio:checked) .pkg-select-btn {
        background: var(--intake-dark) !important;
        border: 1px solid var(--intake-dark) !important;
        color: #fff !important;
    }

    #wizardNext:hover:not(:disabled),
    #saveIntakeRecord:hover:not(:disabled),
    #choose_add_ons_btn:hover,
    #apply_add_ons_btn:hover,
    #intakeCancelModalSaveDraft:hover {
        background: var(--intake-dark-hover) !important;
        border-color: var(--intake-dark-hover) !important;
        color: #fff !important;
        transform: none !important;
    }

    .footer-action-bar {
        padding: 14px 22px !important;
        border-top: 1px solid var(--intake-border) !important;
        flex: 0 0 auto !important;
        position: relative !important;
        z-index: 35 !important;
    }

    .bpay-tab-group {
        border-color: var(--intake-border) !important;
        border-radius: 8px !important;
        background: var(--intake-card-strong) !important;
    }

    .schedule-subsection {
        color: var(--intake-text) !important;
        font-weight: 650 !important;
        text-transform: none !important;
    }

    .schedule-subsection::after {
        background: rgba(62, 74, 61, .2) !important;
    }

    .schedule-time-display::after,
    .readonly-date-display i,
    .flatpickr-prev-month,
    .flatpickr-next-month {
        color: var(--intake-muted) !important;
    }

    .schedule-time-popover,
    .flatpickr-calendar {
        background: #EEF4EA !important;
        border-color: var(--intake-border) !important;
    }

    .time-column,
    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month input.cur-year,
    .flatpickr-current-month .intake-year-dropdown {
        background: var(--intake-card-alt) !important;
        border-color: var(--intake-border) !important;
    }

    .time-option:hover,
    .time-option:focus,
    .flatpickr-day:hover {
        background: var(--intake-hover) !important;
    }

    .time-option.is-selected,
    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        background: var(--intake-dark) !important;
        border-color: var(--intake-dark) !important;
        color: #fff !important;
    }

    #package_details_modal .relative,
    #intakeCancelModal .cancel-modal-box,
    #intake_lock_overlay .bg-white {
        background: var(--intake-card-alt) !important;
        border-color: var(--intake-border) !important;
        color: var(--intake-text) !important;
    }

    #intakeCancelModal {
        background: rgba(35, 40, 33, .38) !important;
    }

    #intakeCancelModal[style] {
        backdrop-filter: none !important;
    }

    .lock-overlay {
        background: rgba(202, 216, 194, .88) !important;
        backdrop-filter: none !important;
    }

    .intake-root .ring-1,
    .intake-root .ring-2,
    .intake-root [class*="ring-"] {
        box-shadow: none !important;
    }

    .intake-root .font-black {
        font-weight: 700 !important;
    }

    .intake-root .font-bold {
        font-weight: 650 !important;
    }

    .intake-root .opacity-50,
    .intake-root .opacity-60,
    .intake-root .opacity-70 {
        opacity: 1 !important;
    }

    .intake-root input,
    .intake-root select,
    .intake-root textarea,
    .intake-root button,
    .intake-root label,
    .intake-root .form-input,
    .intake-root .form-textarea,
    .intake-root .wizard-step-number,
    .intake-root .section-heading-icon,
    .intake-root .package-card,
    .intake-root .pkg-premium-card,
    .intake-root .payment-type-card,
    .intake-root .payment-method-card,
    .intake-root .schedule-time-display,
    .intake-root .flatpickr-calendar,
    .intake-root .cancel-modal-box {
        box-shadow: none !important;
        --tw-shadow: 0 0 #0000 !important;
        --tw-shadow-colored: 0 0 #0000 !important;
        --tw-ring-offset-shadow: 0 0 #0000 !important;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    .intake-root .wizard-tab.active-step .wizard-step-number,
    .intake-root .wizard-tab.completed-step .wizard-step-number,
    .intake-root .bpay-tab-group .payment-method-card.active-tab,
    .intake-root .bpay-tab-group .payment-type-card.active-tab,
    .intake-root .form-input:focus,
    .intake-root .form-textarea:focus,
    .intake-root #wizardNext,
    .intake-root #saveIntakeRecord,
    .intake-root #wizardNext:hover:not(:disabled),
    .intake-root #saveIntakeRecord:hover:not(:disabled) {
        box-shadow: none !important;
    }

    .service-selection-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
        margin-top: 18px;
    }

    .service-tool-card:not(.hidden) {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: start;
        gap: 16px;
        border: 1px solid var(--intake-border);
        border-radius: 8px;
        background: var(--intake-card-alt);
        padding: 18px;
        color: var(--intake-text);
    }

    .service-tool-card:hover {
        background: var(--intake-hover);
        border-color: var(--intake-border-strong);
    }

    .service-tool-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: var(--font-heading), "Syne", var(--font-body), sans-serif;
        font-size: .98rem;
        font-weight: 650;
        color: var(--intake-text);
    }

    .service-tool-help {
        margin-top: 3px;
        font-size: .82rem;
        font-weight: 500;
        color: var(--intake-muted);
    }

    .service-tool-value {
        margin-top: 8px;
        font-size: .88rem;
        font-weight: 650;
        color: var(--intake-text);
    }

    #optional_add_ons_section:not(.hidden) {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
        padding: 16px;
        border: 0;
        border-radius: 10px;
        background: var(--staff-card, var(--intake-card));
        color: var(--intake-text);
    }

    .intake-add-ons-header,
    .intake-add-ons-totals {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 8px 16px;
    }
    .intake-add-ons-totals {
        padding-top: 12px;
        border-top: 1px solid var(--intake-border);
        font-size: .875rem;
    }
    .intake-add-ons-count, .intake-add-on-rate { color: var(--intake-muted); }
    #optional_add_ons_section #choose_add_ons_btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 40px;
        padding: 8px 12px;
        border: 0 !important;
        border-radius: 8px;
        background: #AECDBA !important;
        color: #123524 !important;
        font-size: .875rem;
        font-weight: 650;
    }
    #optional_add_ons_section #choose_add_ons_btn:hover { background: #9BBAA7 !important; color: #123524 !important; }
    html[data-theme='dark'] #optional_add_ons_section #choose_add_ons_btn { background: #294A39 !important; color: #E5F4EA !important; }
    html[data-theme='dark'] #optional_add_ons_section #choose_add_ons_btn:hover { background: #355C46 !important; }
    .intake-applied-add-on {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 8px 16px;
        padding: 10px 12px;
        border-radius: 8px;
        background: #E0ECE4;
    }
    html[data-theme='dark'] .intake-applied-add-on { background: #1C3428; }
    .intake-applied-add-on-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 14px; }
    .intake-applied-add-on-actions label { display: flex; align-items: center; gap: 6px; }
    .intake-applied-add-on-actions .form-input { width: 4.5rem; min-height: 36px !important; }
    .intake-applied-add-on-actions button {
        padding: 6px 8px;
        border-radius: 6px;
        color: var(--feedback-error-fg, #8A3537);
    }
    .intake-applied-add-on-actions button:hover {
        background: var(--feedback-error-bg);
    }

    #optional_add_ons_panel > div:first-child {
        flex: 0 0 auto;
        background: var(--intake-card-strong) !important;
        border-color: var(--intake-border) !important;
    }

    #optional_add_ons_panel > div:first-child {
        padding: 16px !important;
    }

    #optional_add_ons_panel > div:first-child > div {
        align-items: center !important;
    }

    #optional_add_ons_panel > div:first-child > div {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        gap: 12px !important;
    }

    .optional-add-ons-searchbar {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .optional-add-ons-searchbar .form-input {
        padding-left: 42px !important;
    }

    #optional_add_ons_list {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none !important;
        overflow-y: auto !important;
    }

    #optional_add_ons_list {
        padding: 14px 16px !important;
        background: var(--intake-card) !important;
    }

    #optional_add_ons_list .optional-add-on-row {
        min-height: 58px;
        align-items: center !important;
        background: var(--intake-card-alt) !important;
        border-color: var(--intake-border) !important;
    }

    #optional_add_ons_list .optional-add-on-row:hover {
        background: var(--intake-hover) !important;
    }

    #optional_add_ons_list .optional-add-on-row input[type="checkbox"],
    #billing_additional_services_section input[type="checkbox"] {
        cursor: pointer !important;
    }

    #billing_additional_services_section {
        margin: 0 16px 14px !important;
        background: var(--intake-card-alt) !important;
        border-color: var(--intake-border) !important;
        border-radius: 8px !important;
    }

    #billing_additional_services_section > div:first-child {
        align-items: center !important;
    }

    #billing_additional_services_section h5 {
        color: var(--intake-text) !important;
        font-family: var(--font-heading), "Syne", var(--font-body), sans-serif !important;
        font-size: .92rem !important;
        font-weight: 650 !important;
        text-transform: none !important;
    }

    #additional_service_items_list [data-additional-service-row] {
        background: #F2F6EF !important;
        border-color: var(--intake-border) !important;
        border-radius: 8px !important;
    }

    #additional_service_items_list input {
        background: #F7FAF4 !important;
    }

    .service-details-area {
        margin-bottom: 28px;
    }

    .service-details-grid {
        display: grid;
        gap: 16px;
        align-items: start;
    }

    .schedule-stack {
        display: grid;
        gap: 14px;
    }

    .service-details-heading {
        grid-column: 1 / -1;
        margin: 0;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--intake-border);
        color: var(--intake-text);
        font-size: 1rem;
        font-weight: 650;
    }

    .schedule-field-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 8px;
        align-items: start;
        padding: 14px;
        border: 1px solid var(--intake-border);
        border-radius: 8px;
        background: #DCE6D6;
    }

    .intake-root .intake-location-input > input { padding-inline-end:44px !important; }
    .intake-location-input > i {
        position:absolute;
        inset-inline-end:16px;
        top:50%;
        transform:translateY(-50%);
        color:var(--intake-muted);
        pointer-events:none;
    }

    .schedule-field-row:hover {
        background: var(--intake-hover);
    }
    html[data-theme] body.panel-shell-body .intake-root .schedule-field-row {
        background:transparent !important;
        border:0 !important;
        padding:0 !important;
    }
    .schedule-date-time {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .schedule-date-time > .relative {
        flex: 2 1 13rem;
        min-width: 0;
    }
    .schedule-date-time > .time-input-wrap {
        flex: 1 1 8.5rem;
        min-width: 0;
    }
    .intake-root .schedule-date-time [data-intake-date-alternate] {
        padding-inline-end: 2.5rem !important;
    }
    .wake-duration-badge > [role="status"] {
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:5px 10px;
        border-radius:8px;
        background:#DCEDE2;
        color:#166534;
        font-weight:600;
    }
    html[data-theme='dark'] .wake-duration-badge > [role="status"] {
        background:#223D30;
        color:#A7E3BA;
    }

    .schedule-field-label {
        padding-top: 2px;
    }

    .schedule-field-label .field-label {
        margin-bottom: 3px !important;
    }

    .schedule-field-label p {
        font-size: .78rem;
        line-height: 1.35;
        color: var(--intake-muted);
    }

    .schedule-pair,
    .service-info-stack {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 22rem), 1fr));
        gap: 14px;
        align-items: start;
    }

    .schedule-group-heading {
        display:flex;
        align-items:center;
        justify-content:space-between;
        flex-wrap:wrap;
        gap:8px 16px;
    }

    .schedule-summary-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    #intake_schedule_summary .schedule-summary-events { display:grid; gap:10px; margin:10px 0; }
    #intake_schedule_summary .schedule-summary-events > div { display:flex; justify-content:space-between; align-items:baseline; gap:8px; flex-wrap:wrap; }
    #intake_schedule_summary dt { display:flex; align-items:center; gap:8px; font-weight:600; }
    #intake_schedule_summary dd { margin:0; color:var(--ink-muted); }
    #intake_schedule_summary h5 { margin:0; font-size:14px; font-weight:700; }
    #intake_schedule_summary [data-wake-duration] { margin:0; font-weight:600; }
    #intake_schedule_summary_trigger[hidden], #copy_client_surnames[hidden] { display:none !important; }
    #copy_client_surnames { margin-top:12px; }

    .schedule-group-title {
        grid-column:1 / -1;
        margin:0;
        font-size:14px;
        font-weight:650;
        color:var(--intake-text);
    }

    .service-readout {
        display: flex;
        min-height: 46px;
        align-items: center;
        gap: 8px;
        border: 1px solid var(--intake-border);
        border-radius: 8px;
        background: #F2F6EF;
        padding: 0 14px;
        color: var(--intake-text);
        font-weight: 650;
    }

    #optional_add_ons_panel > div:last-child {
        flex: 0 0 auto;
        background: var(--intake-card-strong) !important;
        border-color: var(--intake-border) !important;
    }

    #optional_add_ons_panel > div:last-child {
        padding: 14px 16px !important;
    }

    .intake-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 250;
        background: var(--modal-scrim);
    }

    .intake-modal-backdrop.hidden {
        display: none;
    }

    .billing-method-choice {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .billing-payment-stack {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .billing-payment-stack > * {
        margin-top: 0 !important;
    }

    #payment_form_fields {
        order: 1;
    }

    #discount_help_wrap {
        order: 2;
    }

    .billing-method-choice .payment-method-card {
        min-height: 82px;
        justify-content: flex-start !important;
        padding: 16px !important;
        border: 1px solid var(--intake-border) !important;
        border-radius: 8px !important;
        background: var(--intake-card-alt) !important;
    }

    .billing-method-choice .payment-method-card:hover {
        background: var(--intake-hover) !important;
        border-color: var(--intake-border-strong) !important;
    }

    .billing-method-choice .payment-method-card.active-tab {
        background: var(--intake-active) !important;
        border-color: var(--intake-dark) !important;
    }

    .payment-details-shell {
        border: 1px solid var(--intake-border);
        border-radius: 8px;
        background: var(--intake-card-alt);
        padding: 16px;
    }

    .payment-details-shell.hidden {
        display: none;
    }

    #cashless_details_wrap select,
    #cashless_type {
        cursor: pointer !important;
    }

    #cashless_details_wrap select:hover,
    #cashless_type:hover {
        background: var(--intake-hover) !important;
        border-color: var(--intake-border-strong) !important;
    }

    @media (max-width: 900px) {
        .service-selection-actions {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .intake-root {
            padding: 12px !important;
        }

        .intake-top-shell {
            border-radius: 8px 8px 0 0 !important;
            align-items: flex-start !important;
        }

        .wizard-steps-shell {
            width: 100% !important;
            background: var(--intake-card-strong) !important;
            border-left: 0 !important;
            border-bottom: 1px solid var(--intake-border) !important;
        }

        #intakeFormContent {
            padding: 14px !important;
        }

        .wizard-panel {
            padding: 18px !important;
        }

        .billing-method-choice {
            grid-template-columns: 1fr;
        }
    }

    /* ── Dark mode ── */
    html[data-theme='dark'] .panel-shell-body,
    html[data-theme='dark'] .main-area,
    html[data-theme='dark'] .page-content,
    html[data-theme='dark'] .intake-root,
    html[data-theme='dark'] .intake-form-wrapper { background: #0f1a2e !important; color: #e0eaf8; }
    html[data-theme='dark'] .intake-top-shell { background: #111e33 !important; border-color: #203050 !important; }
    html[data-theme='dark'] .intake-page-heading-copy h1 { color: #e8f2ff !important; }
    html[data-theme='dark'] .intake-page-heading-copy p { color: #9fb0c6 !important; }
    html[data-theme='dark'] .wizard-steps-shell { background: #111e33 !important; border-color: #203050 !important; }
    html[data-theme='dark'] .wizard-panel { background: #152035 !important; border-color: #203050 !important; }
    html[data-theme='dark'] .footer-action-bar { background: #111e33 !important; border-color: #203050 !important; }
    html[data-theme='dark'] .section-title-text h3 { color: #e8f2ff; }
    html[data-theme='dark'] .section-title-text p,
    html[data-theme='dark'] .field-label,
    html[data-theme='dark'] .intake-meta-label,
    html[data-theme='dark'] .intake-meta-value { color: #7a9ec8; }
    html[data-theme='dark'] .form-input,
    html[data-theme='dark'] .form-textarea { background: #1a2b41 !important; border-color: #2a3f5f !important; color: #d8ecff !important; }
    html[data-theme='dark'] .form-input:focus,
    html[data-theme='dark'] .form-textarea:focus { border-color: #4a82c0 !important; box-shadow: 0 0 0 4px rgba(74,130,192,.15) !important; background: #1e3050 !important; }
    html[data-theme='dark'] .wizard-tab.active-step .wz-body { background: #1e3050 !important; }
    html[data-theme='dark'] .wizard-tab.active-step .wizard-step-label { color: #7ec0ff !important; }
    html[data-theme='dark'] .lock-overlay { background: rgba(15,26,46,.9); }
    html[data-theme='dark'] #intake_lock_overlay .bg-white { background: #152035 !important; border-color: #2a3f5f !important; color: #d8ecff !important; }
    html[data-theme='dark'] .package-card { background: #152035 !important; border-color: #2a3f5f !important; }
    html[data-theme='dark'] .package-card.selected { background: #1a2f50 !important; border-color: #4a82c0 !important; }
    html[data-theme='dark'] .subsection-soft { background: #1a2b41 !important; border-color: #2a3f5f !important; }
    html[data-theme='dark'] .intake-toast-branch { background: #1a2b41 !important; color: #e8f0fc !important; border-color: #3f5b7f !important; }
    html[data-theme='dark'] .intake-toast-package { background: #1f5d46 !important; color: #e8fff4 !important; border-color: #2e8666 !important; }

    /* ── Mobile responsive ── */
    @media (min-width: 640px) {
        #meta_request_wrap { display: flex !important; }
    }
    @media (min-width: 1024px) {
        #meta_encoder_wrap { display: flex !important; }
    }

    @media (max-width: 768px) {
        .intake-section-shell { flex-direction: column; }
        .wizard-steps-shell {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .35rem;
            order: 1;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            border-left: none; border-bottom: 1px solid #e4e8ef;
            overflow: visible;
            padding: .55rem !important; height: auto;
        }
        .intake-form-wrapper { order: 2; }
        .wizard-steps-group-label { display: none; }
        .wizard-tab {
            flex: 1 1 auto;
            flex-direction: column;
            align-items: center;
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
        }
        .wizard-tab .wz-track { order: 1; flex-direction: row; justify-content: center; padding: 6px 0 0 0; width: 100%; }
        .wizard-tab .wz-body {
            order: 2;
            width: 100%;
            min-width: 0;
            margin: 0;
        }
        .wizard-tab .wz-line { display: none; }
        .wizard-tab .wz-body { padding: 4px 4px 7px; min-height: auto; align-items: center; text-align: center; }
        .wizard-tab .wz-sub { display: none; }
        .wizard-tab .wizard-step-label {
            max-width: 100%;
            font-size: 0.62rem;
            line-height: 1.12;
            overflow-wrap: anywhere;
            text-align: center;
            white-space: normal;
        }
        #intakeFormContent { padding: 14px 16px !important; }
        .footer-action-bar { padding: 12px 16px !important; }
        .wizard-panel { padding: 18px 16px; border-radius: 14px; }
    }
    @media (max-width: 380px) {
        .wizard-steps-shell {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 640px) {
        .footer-action-bar { flex-direction: column; align-items: stretch; gap: 8px; }
        .footer-left-group,
        .footer-right-group { flex-direction: row; }
        #intakeCancelBtn, #saveIntakeDraft, #wizardPrev, #wizardNext, #saveIntakeRecord { text-align: center; width: 100%; }
        #intakeCancelBtn, #saveIntakeDraft, #wizardPrev, #wizardNext, #saveIntakeRecord { flex: 1; }
        #intakeCancelModal .cancel-modal-box { padding: 24px; max-width: 360px; }
        #intakeCancelModal .cancel-modal-actions { grid-template-columns: 1fr; }
        #intakeCancelModalSaveDraft { grid-column: auto; }
    }
    html[data-theme='dark'] #structured_pricing_section {
        background: #111827 !important;
        border-color: #2a3f5f !important;
    }
    html[data-theme='dark'] #structured_pricing_section .bg-slate-50,
    html[data-theme='dark'] #structured_pricing_section .bg-white {
        background: #152035 !important;
        border-color: #2a3f5f !important;
    }
    html[data-theme='dark'] #structured_pricing_section .text-slate-900,
    html[data-theme='dark'] #structured_pricing_section .text-slate-800 {
        color: #e5edf7 !important;
    }
    html[data-theme='dark'] #structured_pricing_section .text-slate-600,
    html[data-theme='dark'] #structured_pricing_section .text-slate-500,
    html[data-theme='dark'] #structured_pricing_section .text-slate-400 {
        color: #9fb0c6 !important;
    }
    html[data-theme='dark'] #structured_pricing_section .ring-slate-200,
    html[data-theme='dark'] #structured_pricing_section .border-slate-200 {
        --tw-ring-color: #2a3f5f !important;
        border-color: #2a3f5f !important;
    }
    html[data-theme='dark'] #package_details_modal .relative {
        background: #111827 !important;
        border-color: #2a3f5f !important;
    }
    html[data-theme='dark'] #package_details_modal .bg-white,
    html[data-theme='dark'] #package_details_modal .bg-slate-50 {
        background: #152035 !important;
        border-color: #2a3f5f !important;
    }
    html[data-theme='dark'] #package_details_modal .text-slate-900,
    html[data-theme='dark'] #package_details_modal .text-slate-700 {
        color: #e5edf7 !important;
    }
    html[data-theme='dark'] #package_details_modal .text-slate-500 {
        color: #9fb0c6 !important;
    }
    .optional-add-ons-panel:not(.hidden) {
        animation: intakeAddOnsExpand .18s ease-out;
    }
    @keyframes intakeAddOnsExpand {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .optional-add-ons-panel:not(.hidden) {
            animation: none;
        }
    }
    /* Step 3 reference treatment: keep legacy intake rules from recoloring package UI. */
    .intake-root .pkg-premium-card {
        border-radius:16px !important;
        background:#faf8f5 !important;
        border-color:#d0e2d6 !important;
    }
    .intake-root .pkg-premium-card:hover {
        background:#f6faf7 !important;
        border-color:#2d6a4f !important;
    }
    .intake-root .pkg-premium-card:has(.package-radio:checked) {
        background:#edf5f0 !important;
        border-color:#2d6a4f !important;
    }
    html[data-theme='dark'] .intake-root .pkg-premium-card {
        background:#0f241c !important;
        border-color:#315542 !important;
    }
    html[data-theme='dark'] .intake-root .pkg-premium-card:hover {
        background:#132d22 !important;
        border-color:#69a77f !important;
    }
    html[data-theme='dark'] .intake-root .pkg-premium-card:has(.package-radio:checked) {
        background:#173326 !important;
        border-color:#69a77f !important;
    }
    html[data-theme='dark'] #package_details_modal .package-preview-drawer,
    html[data-theme='dark'] #package_details_modal .package-preview-section {
        background:#0f241c !important;
        border-color:#315542 !important;
    }
    html[data-theme='dark'] #package_details_modal .package-preview-section .bg-white,
    html[data-theme='dark'] #package_details_modal .package-preview-section .bg-slate-50 {
        background:#10241a !important;
        border-color:#315542 !important;
    }
    @media (max-width:639px) {
        .pkg-inclusion-list { grid-template-columns:1fr; }
        .pkg-card-body { padding:18px 18px 14px; }
        .pkg-card-footer { padding:12px 18px 16px; }
    }
    </style>
    @include('staff.intake._interaction-styles')
    @include('staff.intake._wizard-surface-styles')

    <div id="branch_toast" role="status" aria-live="polite" class="hidden fixed left-1/2 -translate-x-1/2 px-5 py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold transition-all duration-300 opacity-0 translate-y-[-8px] toast-pop intake-toast-branch">
        <i class="bi bi-building intake-notice-icon" aria-hidden="true"></i>
        <span class="intake-notice-text">You're now recording for this branch.</span>
    </div>

    <div id="package_toast" role="status" aria-live="polite" class="hidden fixed left-1/2 -translate-x-1/2 px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-bold transition-all duration-200 opacity-0 translate-y-[-8px] flex items-center gap-2 toast-pop intake-toast-package">
        <i class="bi bi-check2-circle text-lg intake-notice-icon" aria-hidden="true"></i>
        <span class="package-toast-text">You selected a package.</span>
    </div>

@if($showIntakePageHeading)
    <header class="intake-top-shell panel-title-surface centered-page-header" aria-labelledby="intake-page-title">
        <div class="intake-brand">
            <div class="intake-page-heading-copy">
                <h1 id="intake-page-title">{{ $isOtherEntryMode ? 'Case Intake - Other Branch' : 'Case Intake - Main Branch' }}</h1>
                <p>{{ $isOtherEntryMode ? 'Record a completed case reported by another branch.' : 'Record client, deceased, service, and payment details for a new case.' }}</p>
            </div>
        </div>
    </header>
@endif

{{-- Progress rail (updated by JS) --}}
<div class="intake-progress-rail"><div class="intake-progress-fill" id="intakeProgressFill"></div></div>

    @if($isOtherEntryMode)
        <div class="mb-6 rounded-2xl border px-5 py-4 text-sm {{ $otherBranchWindowClosed ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-amber-200 bg-amber-50 text-amber-900' }} shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
            <div class="font-bold flex items-center gap-2">
                <i class="bi {{ $otherBranchWindowClosed ? 'bi-x-circle-fill text-rose-500' : 'bi-exclamation-triangle-fill text-amber-500' }}"></i>
                <span>External Branch Report</span>
                @if($otherBranchWindowClosed)
                    <span class="text-xs font-black uppercase tracking-widest opacity-80">Intake Window Closed</span>
                @endif
            </div>
            <div class="mt-2 text-[10px] font-black uppercase tracking-widest">Other-Branch Intake Rules</div>
            <div class="mt-1 text-xs font-medium opacity-90">
                Reports must be completed, fully paid, and submitted within today only from 00:00 to {{ $otherBranchCutoffAt->format('H:i') }}.
            </div>
        </div>
    @endif

    @if($historicalMode ?? false)
        <div class="mx-4 mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-950 md:mx-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><strong>Historical / Backlog Entry</strong><p class="mt-1">Past service dates are allowed. This entry stays out of official records and reports until a different authorized administrator approves it.</p></div>
                <a class="shrink-0 font-semibold underline underline-offset-2" href="{{ route('historical-cases.index') }}" @if($intakeModal) target="_parent" @endif>View submissions</a>
            </div>
        </div>
    @endif

    <div class="intake-section-shell">
        @if($intakeModal)
            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" id="intakeWizardForm" novalidate>
                <div class="intake-steps-panel">
        @endif
        <div class="wizard-steps-shell hide-scrollbar" id="wizardSteps" role="group" aria-label="Case intake progress">
    @php
        $steps = [
            1 => 'Client Info',
            2 => 'Deceased',
            3 => 'Service',
            4 => 'Billing & Payment',
            5 => 'Review',
        ];
        $stepSubs = [
            1 => 'Primary contact',
            2 => 'Deceased details',
            3 => 'Package & dates',
            4 => 'Charges & payment',
            5 => 'Final review',
        ];
    @endphp

    <div class="wizard-steps-group-label">Case Progress</div>

    @foreach($steps as $num => $label)
        <button
            type="button"
            data-step="{{ $num }}"
            aria-controls="intake-step-{{ $num }}"
            class="wizard-tab {{ $num === 1 ? 'active-step' : '' }}"
        >
            <div class="wz-track">
                <span class="wizard-step-number">{{ $num }}</span>
                <span class="wz-line"></span>
            </div>
            <div class="wz-body">
                <span class="wizard-step-label">{{ $label }}</span>
                <span class="wz-sub">{{ $stepSubs[$num] }}</span>
            </div>
        </button>
    @endforeach
</div>
        @if($intakeModal)
                </div>
        @endif

        @unless($intakeModal)
        <div class="intake-form-wrapper">

            <div id="intake_lock_overlay" class="lock-overlay hidden">
                <div class="max-w-md w-full mx-4 rounded-2xl border border-slate-200 bg-white shadow-[0_20px_45px_rgba(13,31,56,.18)] px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-diagram-3 text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Select a Branch to Begin</h4>
                            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">To begin recording, choose which branch this case belongs to.</p>
                        </div>
                    </div>
                </div>
            </div>

        <form method="POST" action="{{ $formAction ?? route('intake.main.store') }}" enctype="multipart/form-data" id="intakeWizardForm" novalidate>
        @endunless
            @csrf
            <input type="hidden" name="submission_id" value="{{ old('submission_id', (string) \Illuminate\Support\Str::uuid()) }}">

            <input type="hidden" name="branch_id" id="branch_id" value="{{ $initialSelectedBranchId }}">
            <input type="hidden" name="intake_draft_id" id="intake_draft_id" value="{{ $intakeDraft?->id }}">
            <input type="hidden" name="entry_mode" value="{{ $isOtherEntryMode ? 'other' : 'main' }}">
            <input type="hidden" name="historical_mode" value="{{ ($historicalMode ?? false) ? 1 : 0 }}">
            @if(session('historical_parent_submission_id'))<input type="hidden" name="historical_parent_submission_id" value="{{ session('historical_parent_submission_id') }}">@endif
            @if($historicalMode ?? false)<input type="hidden" name="historical_primary_parent_payment_index" value="{{ old('historical_primary_parent_payment_index', '') }}">@endif
            <input type="hidden" name="promotion_context_token" value="{{ old('promotion_context_token', '') }}">
            <input type="hidden" name="promotion_review_token" value="{{ old('promotion_review_token') ?: session('promotion_review.review_token', '') }}">
            <input type="hidden" name="add_on_context_token" value="{{ session('add_on_review.context_token') ?: old('add_on_context_token', $addOnContextToken ?? '') }}">
            <input type="hidden" name="package_allowance_context" id="package_allowance_context">
            <input type="hidden" name="package_configuration_context" id="package_configuration_context">
            <input type="hidden" name="chapel_draft_pricing" id="chapel_draft_pricing">
            <input type="hidden" name="custom_defaults_context" value="{{ \Illuminate\Support\Facades\Crypt::encryptString(json_encode(['coverage' => $customDefaultCoverage, 'rates' => $customDefaultRates], JSON_THROW_ON_ERROR)) }}">
            <input type="hidden" name="add_on_review_token" value="{{ session('add_on_review.review_token') ?: old('add_on_review_token', '') }}">
            <input type="hidden" name="current_step" id="current_step" value="{{ $initialStep ?? 1 }}">
            <input type="hidden" name="return_to" value="{{ $returnTo ?? '' }}">
            <input type="hidden" id="branch_code_main_default" value="{{ optional($branches->first())->branch_code ?? 'BR001' }}">

            <div id="intakeFormContent">
                @if(($historicalMode ?? false) && ($canEncodeAnyBranch ?? false))
                    <div class="mx-4 mb-5 rounded-xl bg-amber-50/80 p-4 md:mx-6">
                        <label class="field-label font-semibold text-slate-900" for="historical_branch_selector">Branch where this service took place <span class="text-rose-600">*</span></label>
                        <select id="historical_branch_selector" class="form-input mt-1 max-w-xl">
                            @foreach($branches as $branchOption)
                                <option value="{{ $branchOption->id }}" @selected((string) $initialSelectedBranchId === (string) $branchOption->id)>{{ $branchOption->branch_code }} · {{ $branchOption->branch_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-600">The selected branch controls catalog eligibility, review access, and the official case record.</p>
                        @error('branch_id')<p class="intake-field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                @endif
                <section class="wizard-panel" data-step="1" id="intake-step-1">
                    @if(!$isOtherEntryMode && auth()->user()?->isStaff())
                        @include('staff.intake._existing-client')
                    @endif
                    <div class="section-title-block">
                        <div class="section-heading-icon">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="section-title-text">
                            <h3>Client Information</h3>
                            <p>Enter the client’s basic details before continuing.</p>
                        </div>
                    </div>

                    @if($isOtherEntryMode)
                        <div id="external_reporter_box" class="subsection-soft mb-8 p-5 md:p-6 border border-amber-200 bg-gradient-to-br from-amber-50 to-white grid grid-cols-1 sm:grid-cols-3 gap-5">
                            <div class="sm:col-span-3 text-[10px] font-black uppercase tracking-widest text-amber-800">External Reporter Details</div>

                            <div>
                                <label class="field-label" for="reporter_name">Reporter Name</label>
                                <input type="text" name="reporter_name" id="reporter_name" value="{{ $intakeValue('reporter_name') }}" data-validate="letters-spaces" data-label="reporter name" class="form-input">
                            </div>

                            <div>
                                <label class="field-label" for="reporter_contact">Contact Number</label>
                                <input type="text" name="reporter_contact" id="reporter_contact" value="{{ $intakeValue('reporter_contact') }}" data-validate="digits" data-label="reporter contact number" class="form-input">
                            </div>

                            <div>
                                <label class="field-label" for="reported_at">Encoded Date</label>
                                <input
                                    type="datetime-local"
                                    name="reported_at"
                                    id="reported_at"
                                    value="{{ $otherBranchReportedDefault }}"
                                    min="{{ $isOtherEntryMode ? $todayStart->format('Y-m-d\\TH:i') : '' }}"
                                    max="{{ $isOtherEntryMode ? $otherBranchCutoffAt->min(now())->format('Y-m-d\\TH:i') : '' }}"
                                    data-label="reported date and time"
                                    class="form-input"
                                >
                            </div>
                        </div>
                    @endif

                    <div class="space-y-4">
                        <div class="intake-field-section">
                            <div class="intake-section-kicker"><i class="bi bi-person-lines-fill"></i><span>Client name</span></div>
                            <div class="intake-name-grid">
                                <div>
                                    <label class="field-label" for="client_first_name">First Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="client_first_name" id="client_first_name" value="{{ $intakeValue('client_first_name') }}" data-label="client first name" class="form-input" placeholder="First name" required>
                                    @error('client_first_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label" for="client_middle_name">Middle Name <span class="text-slate-400 normal-case tracking-normal font-normal">(Optional)</span></label>
                                    <input type="text" name="client_middle_name" id="client_middle_name" value="{{ $intakeValue('client_middle_name') }}" data-label="client middle name" class="form-input" placeholder="Middle name">
                                    @error('client_middle_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label" for="client_last_name">Last Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="client_last_name" id="client_last_name" value="{{ $intakeValue('client_last_name') }}" data-label="client last name" class="form-input" placeholder="Last name" required>
                                    @error('client_last_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label" for="client_suffix">Suffix</label>
                                    <div class="form-select-wrap">
                                        <select name="client_suffix" id="client_suffix" data-label="client suffix" class="form-input">
                                            <option value="" {{ $intakeValue('client_suffix') === null || $intakeValue('client_suffix') === '' ? 'selected' : '' }}>None</option>
                                            @foreach(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $suffixOption)
                                                <option value="{{ $suffixOption }}" {{ $intakeValue('client_suffix') === $suffixOption ? 'selected' : '' }}>{{ $suffixOption }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('client_suffix') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="intake-field-section">
                            <div class="intake-section-kicker"><i class="bi bi-telephone-fill"></i><span>Contact and relationship</span></div>
                            <div class="intake-info-grid">
                                <div>
                                    <label class="field-label" for="client_relationship">Relationship to Deceased <span class="text-rose-500">*</span></label>
                                    <div class="form-select-wrap">
                                        <select name="client_relationship" id="client_relationship" data-label="relationship to the deceased" class="form-input" required>
                                            <option value="">Select relationship</option>
                                            @foreach(['Father', 'Mother', 'Spouse', 'Child', 'Sibling', 'Relative', 'Guardian', 'Other'] as $relationship)
                                                <option value="{{ $relationship }}" {{ $intakeValue('client_relationship') === $relationship ? 'selected' : '' }}>
                                                    {{ $relationship }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="field-label" for="client_contact_number">Mobile Number <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        name="client_contact_number" id="client_contact_number"
                                        value="{{ $intakeValue('client_contact_number') }}"
                                        data-validate="philippine-mobile"
                                        data-label="contact number"
                                        inputmode="tel"
                                        maxlength="15"
                                        title="Philippine mobile number"
                                        class="form-input"
                                        placeholder="09XXXXXXXXX"
                                        required
                                    >
                                </div>

                                <div class="intake-full-field">
                                    <label class="field-label" for="client_address">Complete Address <span class="text-rose-500">*</span></label>
                                    <div class="relative intake-location-input">
                                        <input type="text" name="client_address" id="client_address" value="{{ $intakeValue('client_address') }}" data-label="client address" class="form-input" placeholder="House no., street, barangay, city/municipality, province" required>
                                        <i class="bi bi-house-door" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="2" id="intake-step-2">
                    <div class="section-title-block">
                        <div class="section-heading-icon">
                            <i class="bi bi-person-vcard-fill"></i>
                        </div>
                        <div class="section-title-text">
                            <h3>Deceased Information</h3>
                            <p>Record the deceased details and supporting verification.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="intake-field-section">
                            <div class="intake-section-kicker"><i class="bi bi-person-vcard"></i><span>Deceased name</span></div>
                            <div class="intake-name-grid">
                                <div>
                                    <label class="field-label" for="deceased_first_name">First Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="deceased_first_name" id="deceased_first_name" value="{{ $intakeValue('deceased_first_name') }}" data-label="deceased first name" class="form-input" placeholder="First name" required>
                                    @error('deceased_first_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label" for="deceased_middle_name">Middle Name</label>
                                    <input type="text" name="deceased_middle_name" id="deceased_middle_name" value="{{ $intakeValue('deceased_middle_name') }}" data-label="deceased middle name" class="form-input" placeholder="Middle name">
                                    @error('deceased_middle_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label" for="deceased_last_name">Last Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="deceased_last_name" id="deceased_last_name" value="{{ $intakeValue('deceased_last_name') }}" data-label="deceased last name" class="form-input" placeholder="Last name" required>
                                    @error('deceased_last_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label" for="deceased_suffix">Suffix</label>
                                    <div class="form-select-wrap">
                                        <select name="deceased_suffix" id="deceased_suffix" data-label="deceased suffix" class="form-input">
                                            <option value="" {{ $intakeValue('deceased_suffix') === null || $intakeValue('deceased_suffix') === '' ? 'selected' : '' }}>None</option>
                                            @foreach(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $suffixOption)
                                                <option value="{{ $suffixOption }}" {{ $intakeValue('deceased_suffix') === $suffixOption ? 'selected' : '' }}>{{ $suffixOption }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('deceased_suffix') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <button type="button" id="copy_client_surnames" class="service-field-chip" hidden>
                                <i class="bi bi-copy" aria-hidden="true"></i> Copy matching names from client
                            </button>
                        </div>

                        <div class="intake-field-section">
                            <div class="intake-section-kicker"><i class="bi bi-calendar-heart"></i><span>Life dates and address</span></div>
                            <div class="intake-info-grid deceased-meta">
                                <div>
                                    <label class="field-label">Date of Birth <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" name="born" id="born" value="{{ $intakeValue('born') }}" data-label="birthdate" class="form-input pr-10 cursor-pointer" placeholder="Select birth date" autocomplete="off" required>
                                        <button type="button" id="born_picker_trigger" aria-label="Open birth date calendar" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                            <i class="bi bi-calendar-event text-lg"></i>
                                        </button>
                                    </div>
                                    @error('born')
                                        <p class="intake-field-error">{{ $message }}</p>
                                    @enderror
                                    <p id="born_error" class="intake-field-error hidden"></p>
                                </div>

                                <div>
                                    <label class="field-label">Date of Death <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" name="died" id="died" value="{{ $intakeValue('died') }}" data-label="date of death" class="form-input pr-10 cursor-pointer" placeholder="Select date of death" autocomplete="off" required>
                                        <button type="button" id="died_picker_trigger" aria-label="Open date of death calendar" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                            <i class="bi bi-calendar-event text-lg"></i>
                                        </button>
                                    </div>
                                    @error('died')
                                        <p class="intake-field-error">{{ $message }}</p>
                                    @enderror
                                    <p id="died_error" class="intake-field-error hidden"></p>
                                </div>

                                <div>
                                    <label class="field-label" for="age">Age</label>
                                    <input type="number" name="age" id="age" value="{{ $intakeValue('age') }}" class="form-input bg-slate-50 text-slate-500 cursor-not-allowed" readonly>
                                </div>

                                <div class="intake-full-field">
                                    <label class="field-label" for="deceased_address">Complete Address <span class="text-rose-500">*</span></label>
                                    <div class="relative intake-location-input">
                                        <input type="text" name="deceased_address" id="deceased_address" value="{{ $intakeValue('deceased_address', $intakeValue('client_address')) }}" data-label="deceased address" class="form-input" placeholder="House no., street, barangay, city/municipality, province" required>
                                        <i class="bi bi-house-door" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="intake-field-section">
                            <input type="file" name="deceased_photo" id="deceased_photo" hidden>
                            <x-payment-proof-upload context="deceased" target="deceased_photo" label="Deceased Photo" accept="image/jpeg,image/png,image/webp" :limit="3" :restore-old="false" />
                        </div>

                        <div id="senior_verification_section" class="intake-field-section hidden">
                            <input type="hidden" name="senior_citizen_status" id="senior_citizen_status" value="0">
                            <div class="intake-info-grid">
                                <p class="senior-eligible-label intake-full-field"><i class="bi bi-patch-check" aria-hidden="true"></i> Senior citizen eligible</p>
                                <div id="senior_id_wrap" class="hidden">
                                    <label class="field-label" for="senior_citizen_id_number">Senior Citizen ID Number <span class="text-slate-400 normal-case tracking-normal font-normal">(Optional)</span></label>
                                    <input type="text" name="senior_citizen_id_number" id="senior_citizen_id_number" value="{{ $intakeValue('senior_citizen_id_number') }}" data-label="Senior Citizen ID number" class="form-input" placeholder="Enter Senior Citizen ID number">
                                </div>

                                <div id="senior_proof_wrap" class="min-w-0 hidden">
                                    <input type="file" name="senior_proof" id="senior_proof" hidden>
                                    <x-payment-proof-upload context="senior" target="senior_proof" label="Senior ID / Certificate" accept="image/jpeg,image/png,image/webp,application/pdf" :restore-old="false" />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="3" id="intake-step-3">
                    <div class="pkg-grid-header">
                        <div>
                            <h3 class="pkg-section-title">Service Details</h3>
                            <p class="pkg-section-sub">Set the schedule and choose a package.</p>
                        </div>
                    </div>
                    <div class="service-details-area">
                    <input type="hidden" name="service_requested_at" id="service_requested_at" value="{{ now()->toDateString() }}">
                    <input type="hidden" name="service_type" id="service_type" value="Burial">
                    @error('service_requested_at')
                        <p class="intake-field-error mb-3">{{ $message }}</p>
                    @enderror

                    <div class="service-details-grid">
                        <section class="schedule-stack" aria-labelledby="intake-schedule-heading">
                        <div class="schedule-group-heading">
                            <h4 class="schedule-group-title" id="intake-schedule-heading">Schedule</h4>
                            <input type="hidden" name="wake_days" id="wake_days" value="{{ $intakeValue('wake_days') }}" data-label="wake days">
                            <div class="schedule-summary-actions">
                                <div class="wake-duration-badge" id="wake_duration_badge" hidden>
                                    <div role="status">
                                        <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                        <span id="wake_duration_display"></span>
                                    </div>
                                </div>
                                <button type="button" class="service-field-chip" id="intake_schedule_summary_trigger" aria-controls="intake_schedule_summary" aria-expanded="false" hidden>
                                    <i class="bi bi-calendar-week" aria-hidden="true"></i> View Schedule Summary
                                </button>
                            </div>
                        </div>
                        <div class="service-field-popover intake-schedule-calendar" id="intake_schedule_summary" aria-labelledby="intake_schedule_summary_title" popover hidden>
                            <h5 id="intake_schedule_summary_title">Schedule Summary</h5>
                            <dl class="schedule-summary-events">
                                @foreach(['start' => 'Wake Start', 'end' => 'Wake End', 'ceremony' => 'Funeral Ceremony', 'interment' => 'Interment'] as $eventColor => $eventLabel)
                                    <div><dt><i data-schedule-color="{{ $eventColor }}" aria-hidden="true"></i>{{ $eventLabel }}</dt><dd data-summary-date></dd></div>
                                @endforeach
                            </dl>
                            <p data-wake-duration></p>
                        </div>
                        <div class="schedule-pair">
                        <div class="schedule-field-row">
                            <div class="schedule-field-label">
                                <label class="field-label" for="wake_start_date">Wake Start Date &amp; Time <span class="text-rose-500">*</span></label>
                            </div>
                            <div>
                            <div class="schedule-date-time">
                                <div class="relative">
                                    <input type="text" name="wake_start_date" id="wake_start_date" value="{{ $intakeValue('wake_start_date') }}" data-label="wake start date" class="form-input pr-10 cursor-pointer" placeholder="Wake date" autocomplete="off" required>
                                    <button type="button" id="wake_start_picker_trigger" aria-label="Open wake start date calendar" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                        <i class="bi bi-calendar-event text-lg"></i>
                                    </button>
                                </div>
                                <div class="time-input-wrap" data-time-picker="wake_start_time">
                                    <input type="hidden" name="wake_start_time" id="wake_start_time" value="{{ $intakeValue('wake_start_time') ? substr($intakeValue('wake_start_time'), 0, 5) : '' }}" data-label="wake start time" class="schedule-time-value" required>
                                    <input type="text" class="schedule-time-display" data-time-display-for="wake_start_time" data-skip-autocap aria-label="Wake start time" placeholder="e.g. 8:30 AM" inputmode="text" autocomplete="off" aria-expanded="false">
                                    <button type="button" class="schedule-time-toggle" data-time-toggle-for="wake_start_time" aria-label="Open wake start time picker">
                                        <i class="bi bi-clock"></i>
                                    </button>
                                    <div class="schedule-time-popover" data-time-popover role="dialog" aria-label="Wake start time picker">
                                        @include('staff.intake._time-controls')
                                    </div>
                                </div>
                            </div>
                            @error('wake_start_date')
                                <p class="intake-field-error" data-schedule-server-error="wake_start_date">{{ $message }}</p>
                            @enderror
                            @error('wake_start_time')
                                <p class="intake-field-error" data-schedule-server-error="wake_start_time">{{ $message }}</p>
                            @enderror
                            <p id="wake_start_error" class="intake-field-error hidden"></p>
                            </div>
                        </div>
                        <div class="schedule-field-row">
                            <div class="schedule-field-label">
                                <label class="field-label" for="wake_end_date">Wake End Date &amp; Time <span class="text-rose-500">*</span></label>
                            </div>
                            <div>
                            <div class="schedule-date-time">
                                <div class="relative">
                                    <input type="text" name="wake_end_date" id="wake_end_date" value="{{ $intakeValue('wake_end_date') }}" data-label="wake end date" class="form-input pr-10 cursor-pointer" placeholder="Wake end date" autocomplete="off" required>
                                    <button type="button" id="wake_end_picker_trigger" aria-label="Open wake end date calendar" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                        <i class="bi bi-calendar-event text-lg"></i>
                                    </button>
                                </div>
                                <div class="time-input-wrap" data-time-picker="wake_end_time">
                                    <input type="hidden" name="wake_end_time" id="wake_end_time" value="{{ $intakeValue('wake_end_time') ? substr($intakeValue('wake_end_time'), 0, 5) : '' }}" data-label="wake end time" class="schedule-time-value" required>
                                    <input type="text" class="schedule-time-display" data-time-display-for="wake_end_time" data-skip-autocap aria-label="Wake end time" placeholder="e.g. 7:00 AM" inputmode="text" autocomplete="off" aria-expanded="false">
                                    <button type="button" class="schedule-time-toggle" data-time-toggle-for="wake_end_time" aria-label="Open wake end time picker"><i class="bi bi-clock"></i></button>
                                    <div class="schedule-time-popover" data-time-popover role="dialog" aria-label="Wake end time picker">
                                        @include('staff.intake._time-controls')
                                    </div>
                                </div>
                            </div>
                            @error('wake_end_date')<p class="intake-field-error" data-schedule-server-error="wake_end_date">{{ $message }}</p>@enderror
                            @error('wake_end_time')<p class="intake-field-error" data-schedule-server-error="wake_end_time">{{ $message }}</p>@enderror
                            <p id="wake_end_error" class="intake-field-error hidden"></p>
                            </div>
                        </div>
                        </div>
                        <div class="schedule-pair">
                        <div class="schedule-field-row">
                            <div class="schedule-field-label">
                                <label class="field-label" for="funeral_service_at">Funeral Ceremony Date &amp; Time <span class="text-rose-500">*</span></label>
                            </div>
                            <div>
                            <div class="schedule-date-time">
                                <div class="relative">
                                    <input type="text" name="funeral_service_at" id="funeral_service_at" value="{{ $intakeValue('funeral_service_at') }}" data-label="funeral ceremony date" class="form-input pr-10 cursor-pointer" placeholder="Ceremony date" autocomplete="off" required>
                                    <button type="button" id="wake_picker_trigger" aria-label="Open funeral ceremony date calendar" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                        <i class="bi bi-calendar-event text-lg"></i>
                                    </button>
                                </div>
                                <div class="time-input-wrap" data-time-picker="funeral_service_time">
                                    <input type="hidden" name="funeral_service_time" id="funeral_service_time" value="{{ $intakeValue('funeral_service_time') ? substr($intakeValue('funeral_service_time'), 0, 5) : '' }}" data-label="funeral ceremony time" class="schedule-time-value" required>
                                    <input type="text" class="schedule-time-display" data-time-display-for="funeral_service_time" data-skip-autocap aria-label="Funeral ceremony time" placeholder="e.g. 9:00 AM" inputmode="text" autocomplete="off" aria-expanded="false">
                                    <button type="button" class="schedule-time-toggle" data-time-toggle-for="funeral_service_time" aria-label="Open funeral ceremony time picker">
                                        <i class="bi bi-clock"></i>
                                    </button>
                                    <div class="schedule-time-popover" data-time-popover role="dialog" aria-label="Funeral ceremony time picker">
                                        @include('staff.intake._time-controls')
                                    </div>
                                </div>
                            </div>
                            @error('funeral_service_at')
                                <p class="intake-field-error" data-schedule-server-error="funeral_service_at">{{ $message }}</p>
                            @enderror
                            @error('funeral_service_time')
                                <p class="intake-field-error" data-schedule-server-error="funeral_service_time">{{ $message }}</p>
                            @enderror
                            <p id="funeral_service_at_error" class="intake-field-error hidden"></p>
                            @include('staff.intake._schedule-advisory', ['field' => 'funeral', 'label' => 'Funeral Ceremony'])
                            </div>
                        </div><div class="schedule-field-row">
                            <div class="schedule-field-label">
                                <label class="field-label" for="interment_at">Interment Date &amp; Time <span class="text-rose-500">*</span></label>
                            </div>
                            <div>
                            <div class="schedule-date-time">
                                <div class="relative">
                                    <input type="text" name="interment_at" id="interment_at" value="{{ $intakeValue('interment_at') }}" data-label="interment date" class="form-input pr-10 cursor-pointer" placeholder="Interment date" autocomplete="off" required>
                                    <button type="button" id="inter_picker_trigger" aria-label="Open interment date calendar" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                        <i class="bi bi-calendar-event text-lg"></i>
                                    </button>
                                </div>
                                <div class="time-input-wrap" data-time-picker="interment_time">
                                    <input type="hidden" name="interment_time" id="interment_time" value="{{ $intakeValue('interment_time') ? substr($intakeValue('interment_time'), 0, 5) : '' }}" data-label="interment time" class="schedule-time-value" required>
                                    <input type="text" class="schedule-time-display" data-time-display-for="interment_time" data-skip-autocap aria-label="Interment time" placeholder="e.g. 1:00 PM" inputmode="text" autocomplete="off" aria-expanded="false">
                                    <button type="button" class="schedule-time-toggle" data-time-toggle-for="interment_time" aria-label="Open interment time picker">
                                        <i class="bi bi-clock"></i>
                                    </button>
                                    <div class="schedule-time-popover" data-time-popover role="dialog" aria-label="Interment time picker">
                                        @include('staff.intake._time-controls')
                                    </div>
                                </div>
                            </div>
                            @error('interment_at')
                                <p class="intake-field-error" data-schedule-server-error="interment_at">{{ $message }}</p>
                            @enderror
                            @error('interment_time')
                                <p class="intake-field-error" data-schedule-server-error="interment_time">{{ $message }}</p>
                            @enderror
                            <div id="interment_at_error" class="intake-field-error hidden">Interment Date cannot be earlier than the Funeral Ceremony Date.</div>
                            @include('staff.intake._schedule-advisory', ['field' => 'interment', 'label' => 'Interment'])
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">Follows the ceremony date. You can change it.</p>
                            <p id="interment_schedule_warning" class="hidden text-xs font-bold text-amber-600 mt-1">Funeral Ceremony and interment are usually on the same day. Please confirm if interment is scheduled on a different date.</p>
                            </div>
                        </div>

                        </div>
                        </section>

                        <section class="service-info-stack" aria-labelledby="intake-service-info-heading">
                        <h4 class="schedule-group-title" id="intake-service-info-heading">Viewing &amp; Interment Location</h4>
                        <div class="viewing-fields">
                        <div>
                            <label class="field-label" for="viewing_type">Viewing Type <span class="text-rose-500">*</span></label>
                            <div class="viewing-type-select">
                            <select name="viewing_type" id="viewing_type" class="form-input" data-label="viewing type" required>
                                <option value="">Select viewing type</option>
                                <option value="home" @selected($intakeValue('viewing_type') === 'home')>Home Viewing</option>
                                <option value="chapel" @selected($intakeValue('viewing_type') === 'chapel')>Funeral Home Chapel Viewing</option>
                            </select>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </div>
                            @error('viewing_type') <p id="viewing_type_inline_error" class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div id="chapel_charge_panel" hidden>
                            <input type="hidden" name="chapel_coverage_flow" value="1">
                            <fieldset>
                                <legend class="field-label">How will you choose the chapel? <span class="text-rose-500">*</span></legend>
                                <div class="chapel-coverage-options">
                                    <label class="chapel-coverage-option">
                                        <input type="radio" name="chapel_coverage" value="included" @checked($intakeValue('chapel_coverage') === 'included') aria-describedby="chapel_coverage_error">
                                        <span class="chapel-coverage-indicator" aria-hidden="true"></span>
                                        <span><strong>Choose a Package with a Chapel</strong><small>Pick a package to see its chapel and included days.</small></span>
                                    </label>
                                    <label class="chapel-coverage-option">
                                        <input type="radio" name="chapel_coverage" value="separate" @checked($intakeValue('chapel_coverage') === 'separate') aria-describedby="chapel_coverage_error">
                                        <span class="chapel-coverage-indicator" aria-hidden="true"></span>
                                        <span><strong>Choose a Chapel from This Branch</strong><small>Pick a chapel separately from the package. The daily rate applies to all wake days.</small></span>
                                    </label>
                                </div>
                                <p class="intake-field-error" id="chapel_coverage_error" @if(!$errors->has('chapel_coverage')) hidden @endif>{{ $errors->first('chapel_coverage') }}</p>
                            </fieldset>
                            <div id="chapel_charge_notice" role="status" aria-live="polite" aria-atomic="true">
                                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                <div><strong id="chapel_notice_title"></strong><span id="chapel_notice_detail"></span>
                                    <button type="button" id="chapel_save_draft" class="service-field-adjust" hidden>Save as Draft</button>
                                </div>
                            </div>
                            <div id="chapel_charge_controls">
                                <div>
                                    <label class="field-label" for="chapel_add_on_id">Select Chapel <span class="text-rose-500">*</span></label>
                                    <div class="viewing-type-select">
                                        <select name="chapel_add_on_id" id="chapel_add_on_id" class="form-input" aria-describedby="chapel_package_hint" disabled>
                                            <option value="">Select a branch chapel</option>
                                            @foreach(($activeAddOns ?? collect())->filter(fn ($rate) => $rate->chapel_ready && (float) $rate->price >= 0) as $rate)
                                                <option value="{{ $rate->id }}" data-branch="{{ $rate->branch_id }}" @selected((string) $intakeValue('chapel_add_on_id') === (string) $rate->id)>{{ $rate->name }} · ₱{{ number_format((float) $rate->price, 2) }} / day</option>
                                            @endforeach
                                        </select>
                                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                    </div>
                                    <p id="chapel_package_hint" hidden>Next, choose a compatible service package below.</p>
                                </div>
                            </div>
                            @error('chapel_add_on_id') <p class="intake-field-error" id="chapel_add_on_id_inline_error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div id="viewing_location_wrap">
                            <label class="field-label" for="wake_location"><span id="viewing_location_label">Viewing Location</span> <span id="viewing_location_required" class="text-rose-500" hidden>*</span></label>
                            <div class="relative intake-location-input">
                                <input type="text" name="wake_location" id="wake_location" value="{{ $intakeValue('wake_location') }}" data-label="viewing location" class="form-input" placeholder="Choose a viewing type first" required>
                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            </div>
                            @error('wake_location') <p id="wake_location_inline_error" class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div class="viewing-cemetery">
                            <label class="field-label" for="place_of_cemetery">Place of Interment / Cemetery <span class="text-rose-500">*</span></label>
                            <div class="relative intake-location-input">
                                <input type="text" name="place_of_cemetery" id="place_of_cemetery" value="{{ $intakeValue('place_of_cemetery') }}" data-label="place of interment or cemetery" class="form-input" placeholder="e.g., Forest Lake Memorial Park" required>
                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            </div>
                        </div>
                        <p id="home_viewing_notice" class="text-sm" role="status" hidden></p>
                        <div class="col-span-full">
                            @include('partials.ceremony-location-fields', [
                                'ceremonyLocationMode' => $intakeValue('funeral_ceremony_location_mode', 'custom'),
                                'ceremonyLocationValue' => $intakeValue('funeral_ceremony_location'),
                            ])
                        </div>
                        </div>

                        <input type="hidden" name="case_status" id="case_status" value="{{ $isOtherEntryMode ? 'COMPLETED' : 'ACTIVE' }}">
                        </section>
                    </div>

                    </div>{{-- /service-details-wrapper --}}

                    <div class="pkg-grid-header">
                        <div>
                            <p class="pkg-section-title">Select Service Package</p>
                            <p class="pkg-section-sub">Choose a regular package or create a custom arrangement based on the client's requirements.</p>
                        </div>
                        <div id="package_error" class="intake-field-error hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2" role="alert">
                            Select a service package or choose Custom Arrangement before continuing.
                        </div>
                    </div>

                    @error('package_id')
                        <p class="intake-field-error" role="alert">{{ $message }}</p>
                    @enderror
                    <p id="chapel_package_feedback" role="status" hidden></p>
                    <div class="package-mode-chooser" role="radiogroup" aria-label="Package arrangement type">
                        <button type="button" class="package-mode-option" data-package-mode="regular" role="radio" aria-checked="false">
                            <span class="package-mode-option__icon"><i class="bi bi-collection" aria-hidden="true"></i></span>
                            <span><strong>Regular Package</strong><small>Choose from eligible packages configured by the System Administrator.</small></span>
                            <i class="bi bi-chevron-right package-mode-option__arrow" aria-hidden="true"></i>
                        </button>
                        @if(empty($entryMode) || $entryMode === 'main')
                            <button type="button" class="package-mode-option" data-package-mode="custom" role="radio" aria-checked="false">
                                <span class="package-mode-option__icon is-warm"><i class="bi bi-sliders2" aria-hidden="true"></i></span>
                                <span><strong>Custom Arrangement</strong><small>Create a case-specific arrangement using selected services and an agreed price.</small></span>
                                <i class="bi bi-chevron-right package-mode-option__arrow" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>

                    <section id="regular_package_area" class="package-selection-area hidden" aria-labelledby="regular_package_heading">
                        <div class="package-selection-area__heading">
                            <div><h4 id="regular_package_heading">Regular Packages</h4><p id="regular_package_help">Predefined, eligible packages configured by the System Administrator.</p></div>
                            <button type="button" class="package-mode-change" data-change-package-mode>Change type</button>
                        </div>
                    <div class="mb-4">
                        <label class="field-label" for="regular_package_choice">Select Package</label>
                        <div class="global-filter-select-wrap intake-package-select">
                        <select class="form-input" id="regular_package_choice" data-plain-options @disabled($packages->isEmpty())>
                            <option value="" disabled>{{ $packages->isEmpty() ? 'No packages available' : 'Choose a package' }}</option>
                            @foreach($packages as $pkg)
                                <option value="{{ $pkg->id }}" @selected((string) $intakeValue('package_id') === (string) $pkg->id)>{{ $pkg->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down" data-filter-select-icon aria-hidden="true"></i>
                        </div>
                    </div>
                    <div id="chapel_setup_alternatives">
                        @include('staff.intake._chapel-alternatives')
                    </div>
                    <section id="chapel_preview_panel" aria-labelledby="chapel_preview_heading" hidden>
                        <h5 id="chapel_preview_heading">Chapel Charge Preview</h5>
                        <p id="chapel_rate_readout"></p>
                        <input type="hidden" name="chapel_address" id="chapel_address" value="{{ $intakeValue('chapel_address') }}" disabled>
                        <p id="chapel_address_readout"></p>
                        @error('chapel_address') <p class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                        <p id="chapel_charge_preview" aria-live="polite"></p>
                        <div id="chapel-schedule-context" data-state="idle" hidden data-url="{{ route('intake.chapel-advisory') }}">
                            <div class="chapel-advisory-heading">
                                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                <p data-chapel-check-status role="status" tabindex="-1"></p>
                                <button type="button" class="service-field-adjust" data-chapel-check-retry hidden>Recheck Availability</button>
                            </div>
                            <div data-chapel-overlaps hidden>
                                <ul data-chapel-events></ul>
                                @include('staff.intake._chapel-alternatives')
                                <div class="chapel-conflict-actions">
                                    <button type="button" class="service-field-chip" data-chapel-edit-wake>Edit Wake Schedule</button>
                                    <button type="button" class="service-field-chip" data-chapel-change-viewing>Change Viewing Type</button>
                                    <button type="button" class="service-field-chip" data-chapel-conflict-draft>Save as Draft</button>
                                </div>
                            </div>
                        </div>
                    </section>
                    <div id="packageCardList">
                        @foreach($packages as $pkg)
                            @php
                                $promoNow = $pkg->is_promo_effective;
                                $promotion = $pkg->promotion;
                                $promotionName = $promotion?->name ?? $pkg->promo_label;
                                $promotionType = $promotion?->discount_type ?? $pkg->promo_value_type;
                                $promotionValue = $promotion?->discount_value ?? $pkg->promo_value;
                                $promotionStart = $promotion?->starts_at?->format('M d, Y') ?? $pkg->promo_starts_at?->format('M d, Y');
                                $promotionEnd = $promotion?->ends_at?->format('M d, Y') ?? $pkg->promo_ends_at?->format('M d, Y');
                                $promotionConfigured = $promotion !== null || $pkg->hasConfiguredPromotion();

                                $inclusionItems = array_values(array_filter(
                                    $pkg->inclusionNames(),
                                    fn ($name) => ! \App\Models\Package::isLegacyPackageDescription($name),
                                ));
                                $freebieItems = $pkg->freebieNames();
                                $structuredInclusions = $pkg->packageInclusions->map(fn ($inclusion) => [
                                    'service_type' => \App\Models\Package::normalizeServiceType($inclusion->service_type),
                                    'name' => $inclusion->inclusion_name,
                                    'is_legacy_description' => \App\Models\Package::isLegacyPackageDescription($inclusion->inclusion_name),
                                    'add_on_id' => $inclusion->add_on_catalog_id,
                                    'included_quantity' => $inclusion->included_quantity,
                                    'add_on_unit' => $inclusion->addOnCatalog?->unit ?: 'item',
                                    'add_on_category' => $inclusion->addOnCatalog?->category,
                                    'add_on_branch_id' => $inclusion->addOnCatalog?->branch_id,
                                    'add_on_rate' => (float) $inclusion->addOnCatalog?->price,
                                    'add_on_available' => $inclusion->addOnCatalog?->is_active && $inclusion->addOnCatalog?->is_available,
                                    'included_kilometers' => $inclusion->included_kilometers,
                                    'price_per_excess_kilometer' => (float) $inclusion->price_per_excess_kilometer,
                                    'included_days' => $inclusion->included_days,
                                    'price_per_extended_day' => $inclusion->price_per_extended_day === null ? null : (float) $inclusion->price_per_extended_day,
                                    'casket_type' => $inclusion->casket_type,
                                    'casket_catalog_id' => $inclusion->casket_catalog_id,
                                    'hearse' => $inclusion->hearseCatalog ? ['id' => $inclusion->hearseCatalog->id, 'name' => $inclusion->hearseCatalog->name, 'reference_value' => $inclusion->hearseCatalog->reference_value, 'active' => (bool) $inclusion->hearseCatalog->is_active, 'available' => (bool) ($inclusion->hearseCatalog->is_active && $inclusion->hearseCatalog->is_available)] : null,
                                    'casket' => $inclusion->casketCatalog ? [
                                        'id' => $inclusion->casketCatalog->id,
                                        'name' => $inclusion->casketCatalog->name,
                                        'material' => $inclusion->casketCatalog->type_or_material,
                                        'reference_value' => (float) $inclusion->casketCatalog->standard_price,
                                        'available' => (bool) ($inclusion->casketCatalog->is_active && $inclusion->casketCatalog->is_available),
                                        'active' => (bool) $inclusion->casketCatalog->is_active,
                                    ] : null,
                                ])->values();
                                $structuredFreebies = $pkg->packageFreebies->map(fn ($freebie) => [
                                    'name' => $freebie->freebie_name,
                                    'quantity' => $freebie->quantity,
                                    'unit' => $freebie->unit,
                                ])->values();
                                $includedCasket = $structuredInclusions->firstWhere('service_type', \App\Models\Package::SERVICE_CASKET);
                            @endphp

                            <div class="package-card-item">
                                <div class="package-card pkg-premium-card w-full">
                                    <input
                                        type="radio"
                                        name="package_id"
                                        value="{{ $pkg->id }}"
                                        class="package-radio"
                                        tabindex="-1"
                                        aria-hidden="true"
                                        data-name="{{ $pkg->name }}"
                                        data-allowance-context="{{ $pkg->addOnAllowanceFingerprint() }}"
                                        data-configuration-context="{{ $pkg->intakeConfigurationFingerprint() }}"
                                        data-price="{{ $pkg->price }}"
                                        data-promo-now="{{ $promoNow ? '1' : '0' }}"
                                        data-promo-configured="{{ $promotionConfigured ? '1' : '0' }}"
                                        data-promo-type="{{ $promotionType }}"
                                        data-promo-value="{{ $promotionValue }}"
                                        data-promo-label="{{ $promotionName }}"
                                        data-promo-status="{{ $pkg->promo_status }}"
                                        data-promo-context="{{ $promotionContextTokens[$pkg->id] ?? '' }}"
                                        data-promo-start="{{ $promotionStart }}"
                                        data-promo-end="{{ $promotionEnd }}"
                                        data-description="{{ $pkg->short_description }}"
                                        data-inclusions="{{ implode("\n", $inclusionItems) }}"
                                        data-freebies="{{ implode("\n", $freebieItems) }}"
                                        data-structured-inclusions="{{ $structuredInclusions->toJson() }}"
                                        data-structured-freebies="{{ $structuredFreebies->toJson() }}"
                                        data-included-casket="{{ json_encode($includedCasket['casket'] ?? ['name' => $includedCasket['casket_type'] ?? $pkg->coffin_type, 'reference_value' => 0]) }}"
                                        {{ (string) $intakeValue('package_id') === (string) $pkg->id ? 'checked' : '' }}
                                        required
                                    >

                                    <div class="pkg-card-body inline-package-body">
                                        <div class="inline-package-header">
                                            <div class="min-w-0">
                                                <h4 class="pkg-name">{{ $pkg->name }}</h4>
                                                @if(filled(trim((string) $pkg->short_description)))
                                                    <p class="pkg-card-description">{{ $pkg->short_description }}</p>
                                                @endif
                                            </div>
                                            <div class="inline-package-price">
                                                <div class="pkg-price-label">Base Package Price</div>
                                                <div class="pkg-price"><sub>&#8369;</sub>{{ number_format($pkg->price, 2) }}</div>
                                            </div>
                                        </div>
                                        <div class="inline-package-details" data-inline-package-details></div>
                                        <section class="inline-package-section">
                                            <h5>Promotion</h5>
                                            @if($pkg->promo_status === 'No Promotion')
                                                <div class="inline-package-promotion-empty">
                                                    <i class="bi bi-tag" aria-hidden="true"></i>
                                                    <div>
                                                        <strong>No promotion</strong>
                                                        <p>No promotion is assigned to this package.</p>
                                                    </div>
                                                </div>
                                            @else
                                            <div class="inline-package-promotion">
                                                <strong>{{ $pkg->promo_status }}</strong>
                                                @if($pkg->promo_status === 'Active' && filled($promotionName))
                                                    <span>{{ $promotionName }} applies to new cases.</span>
                                                @elseif($pkg->promo_status === 'Incomplete Setup')
                                                    <span>This legacy promotion is unavailable until a System Admin corrects its configuration.</span>
                                                @elseif($pkg->promo_status === 'Scheduled')
                                                    <span>Promotion is scheduled for a future date.</span>
                                                @elseif($pkg->promo_status === 'Expired')
                                                    <span>Promotion has ended and does not affect this case.</span>
                                                @elseif($pkg->promo_status === 'Turned Off')
                                                    <span>Promotion is turned off and does not affect this case.</span>
                                                @else
                                                    <span>No promotion is assigned to this package.</span>
                                                @endif
                                                @if($promotionConfigured && filled($promotionType) && (float) $promotionValue > 0)
                                                    <span>{{ in_array(strtoupper($promotionType), ['PERCENT', 'PERCENTAGE'], true) ? rtrim(rtrim(number_format((float) $promotionValue, 2, '.', ''), '0'), '.') . '%' : '₱' . number_format((float) $promotionValue, 2) }} off the base price</span>
                                                @endif
                                                @if($promotionConfigured && ($promotionStart || $promotionEnd))
                                                    <span>{{ $promotionStart ? 'Starts ' . $promotionStart : '' }}{{ $promotionStart && $promotionEnd ? ' · ' : '' }}{{ $promotionEnd ? 'Ends ' . $promotionEnd : '' }}</span>
                                                @endif
                                            </div>
                                            @endif
                                        </section>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                    <div class="package-options-below">
                        <div class="package-upgrade-grid">
                            <section class="inline-package-section" id="package-casket-options" hidden>
                                <h5>Casket Upgrade</h5>
                                <div data-option-body></div>
                            </section>
                            <section class="inline-package-section" id="package-hearse-options" hidden>
                                <h5>Hearse Upgrade</h5>
                                <div data-option-body></div>
                            </section>
                        </div>
                        <section class="inline-package-section" id="package-inclusion-options" hidden>
                            <h5>Other Inclusions</h5>
                            <div data-option-body></div>
                        </section>
                    </div>
                    </section>

                    @if(empty($entryMode) || $entryMode === 'main')
                        <section id="custom_package_area" class="package-selection-area hidden" aria-labelledby="custom_arrangement_heading">
                            <div class="package-selection-area__heading"><div><h4 id="custom_arrangement_heading">Custom Arrangement</h4><p>Create a case-specific arrangement using the client's selected services and agreed price.</p></div><button type="button" class="package-mode-change" data-change-package-mode>Change type</button></div>
                            <input type="radio" name="package_id" value="custom" id="custom_package_radio" class="package-radio sr-only" data-name="Custom Arrangement" data-price="0" data-inclusions="" data-freebies="" data-structured-inclusions="[]" data-structured-freebies="[]" data-included-casket="{}" @checked($intakeValue('package_id') === 'custom')>
                        <div id="custom_package_fields" class="custom-package-builder">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,1fr)_minmax(12rem,0.45fr)]">
                                <div>
                                    <label for="client_pref_package" class="field-label">Package name <span aria-hidden="true">*</span></label>
                                    <input type="text" name="custom_package_name" id="client_pref_package" maxlength="150" value="{{ $intakeValue('custom_package_name') }}" class="form-input" placeholder="e.g. Family arrangement">
                                    @error('custom_package_name') <p class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="client_pref_price" class="field-label">All-in package price (PHP) <span aria-hidden="true">*</span></label>
                                    <input type="number" step="0.01" min="0.01" inputmode="decimal" name="custom_package_price" id="client_pref_price" value="{{ $intakeValue('custom_package_price') }}" class="form-input" placeholder="0.00">
                                    @error('custom_package_price') <p class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                </div>
                                <div class="custom-package-section md:col-span-2">
                                    <p class="field-label">Included services <span aria-hidden="true">*</span></p>
                                    <p class="mb-3 text-xs text-slate-600">Included coverage starts from Basic Funeral Inclusions. Use Adjust to change the coverage or enter the requested quantity.</p>
                                    @if($customDefaultCoverage === [])
                                        <p class="custom-defaults-notice" role="status">Ask the System Administrator to configure custom package included days and retrieval coverage.</p>
                                    @endif
                                    <div class="custom-package-service-grid" id="custom_service_choices" role="group" aria-label="Included services">
                                        @foreach([\App\Models\Package::SERVICE_BODY_RETRIEVAL, \App\Models\Package::SERVICE_EMBALMING, \App\Models\Package::SERVICE_CASKET, \App\Models\Package::SERVICE_HOME_VIEWING, \App\Models\Package::SERVICE_HEARSE] as $serviceType)
                                            @php
                                                $selectedCustomTypes = (array) $intakeValue('custom_service_types', array_keys(\App\Models\Package::serviceTypeOptions()));
                                                $coverageUnit = in_array($serviceType, [\App\Models\Package::SERVICE_BODY_RETRIEVAL, \App\Models\Package::SERVICE_HEARSE], true) ? 'km' : 'days';
                                            @endphp
                                            <div class="custom-package-service-card rounded-lg">
                                                <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-900">
                                                    <input type="checkbox" name="custom_service_types[]" value="{{ $serviceType }}" class="custom-service-check h-4 w-4 accent-[#1A4935]" @checked(in_array($serviceType, $selectedCustomTypes, true))>
                                                    {{ \App\Models\Package::serviceTypeOptions()[$serviceType] }}
                                                </label>
                                                @if($serviceType === \App\Models\Package::SERVICE_HEARSE)
                                                    <div class="custom-service-dependent mt-2">
                                                        <div id="custom-hearse-slot"></div>
                                                    </div>
                                                @elseif($serviceType === \App\Models\Package::SERVICE_CASKET)
                                                    <div class="custom-service-dependent mt-2">
                                                        <div class="custom-inclusion-editor custom-inclusion-inline">
                                                        <button type="button" class="custom-asset-trigger" data-custom-asset-trigger="casket" aria-controls="custom-casket-options" aria-expanded="false">Choose a casket</button>
                                                        <div id="custom-casket-options" class="inline-upgrade-options" popover aria-label="Choose a casket"></div>
                                                        <select name="custom_casket_catalog_id" id="custom_casket_catalog_id" data-label="included casket" class="form-input" hidden>
                                                            <option value="">Choose an available casket</option>
                                                            @foreach($activeCaskets->where('is_available', true) as $casket)
                                                                <option value="{{ $casket->id }}" @selected((string) $intakeValue('custom_casket_catalog_id') === (string) $casket->id)>{{ $casket->display_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error('custom_casket_catalog_id') <p class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                                        </div>
                                                    </div>
                                                @endif
                                                @if(isset(\App\Models\CustomPackageDefault::SERVICE_FIELDS[$serviceType]))
                                                    @php
                                                        $defaultCoverage = $customDefaultCoverage[$serviceType] ?? null;
                                                        $savedCoverage = $intakeValue('custom_included.'.$serviceType);
                                                        $coverage = filled($savedCoverage) ? $savedCoverage : $defaultCoverage;
                                                        $rate = $customDefaultRates[$serviceType] ?? null;
                                                        $stepSize = $coverageUnit === 'days' ? '1' : '0.01';
                                                    @endphp
                                                    <div class="custom-coverage" data-custom-coverage="{{ $serviceType }}" data-unit="{{ $coverageUnit }}" data-rate="{{ $rate }}">
                                                        <div class="custom-coverage-heading"><span class="custom-inclusion-chip" data-coverage-label>{{ $coverage === null ? 'Coverage not configured' : ($coverageUnit === 'days' ? $coverage.' day(s) included' : number_format((float) $coverage, 2).' km included') }}</span><button type="button" class="service-field-adjust" aria-controls="custom-config-{{ $serviceType }}" data-coverage-adjust>Adjust</button></div>
                                                        <div id="custom-config-{{ $serviceType }}" class="service-field-popover is-configuration" data-coverage-panel aria-label="{{ \App\Models\Package::serviceTypeOptions()[$serviceType] }} configuration" hidden>
                                                        <div class="custom-coverage-fields">
                                                            <label>Included {{ $coverageUnit }}
                                                                <input class="form-input" type="number" data-coverage-included data-default-coverage="{{ $defaultCoverage }}" name="custom_included[{{ $serviceType }}]" min="{{ $coverageUnit === 'days' ? 1 : 0 }}" max="100000" step="{{ $stepSize }}" value="{{ $coverage }}" placeholder="Not configured">
                                                            </label>
                                                            <label>Requested {{ $coverageUnit }} (Optional)
                                                                <input class="form-input" type="number" data-coverage-requested name="custom_requested[{{ $serviceType }}]" min="0" max="100000" step="{{ $stepSize }}" value="{{ $intakeValue('custom_requested.'.$serviceType) }}" placeholder="{{ $coverage ?? '0' }}">
                                                            </label>
                                                            <span>{{ $rate === null ? 'Rate not configured' : ((float) $rate === 0.0 ? 'No extra charge' : '₱'.number_format((float) $rate, 2).' / '.($coverageUnit === 'days' ? 'day' : 'km')) }}</span>
                                                        </div>
                                                        @if($serviceType === \App\Models\Package::SERVICE_HOME_VIEWING)
                                                            <input type="hidden" name="custom_home_viewing_manual" value="{{ $intakeValue('custom_home_viewing_manual', filled($intakeValue('custom_requested.home_viewing')) ? '1' : '0') }}" data-viewing-manual>
                                                            <button type="button" class="custom-asset-trigger" data-use-wake-duration>Use wake duration</button>
                                                        @endif
                                                        </div>
                                                        <p data-coverage-charge aria-live="polite" class="custom-coverage-charge"></p>
                                                        @foreach(['custom_service_coverage', 'custom_included', 'custom_requested'] as $errorKey)
                                                            @error($errorKey.'.'.$serviceType) <p class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('custom_service_types') <p class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                </div>
                                <div class="custom-package-section md:col-span-2">
                                    <p class="field-label">Catalog freebies <span class="font-normal text-slate-500">(optional)</span></p>
                                    <div id="custom_freebie_choices" class="custom-package-freebie-grid">
                                        @forelse($activeFreebies as $freebie)
                                            <label class="custom-package-freebie">
                                                <input type="checkbox" name="custom_freebie_catalog_ids[]" value="{{ $freebie->id }}" data-freebie-name="{{ $freebie->name }}" class="custom-freebie-check h-4 w-4 accent-[#1A4935]" @checked(in_array((string) $freebie->id, array_map('strval', (array) $intakeValue('custom_freebie_catalog_ids', [])), true))>
                                                {{ $freebie->name }}
                                            </label>
                                        @empty
                                            <p class="text-xs text-slate-600">No active freebies are available.</p>
                                        @endforelse
                                    </div>
                                    @error('custom_freebie_catalog_ids') <p class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                </div>
                                <textarea id="client_pref_inclusions" class="hidden" tabindex="-1" aria-hidden="true"></textarea>
                                <textarea id="client_pref_freebies" class="hidden" tabindex="-1" aria-hidden="true"></textarea>
                            </div>
                            @if(filled($intakeValue('custom_other_inclusions')))
                                <div class="mt-3">
                                    <p class="field-label">Previously agreed inclusions</p>
                                    <input type="hidden" name="custom_other_inclusions" id="custom_other_inclusions" value="{{ $intakeValue('custom_other_inclusions') }}">
                                    <p class="text-sm whitespace-pre-line">{{ $intakeValue('custom_other_inclusions') }}</p>
                                </div>
                            @endif
                            <p class="custom-package-note">Your agreed price covers the selected inclusions. Optional catalog add-ons below and Manual Charges are extra and increase the total.</p>
                        </div></section>
                    @endif

                    <div id="hearse-assignment-home">
                    <div id="hearse_assignment_fields" class="hidden">
                        <div id="regular-hearse-inclusion" class="inline-inclusion-card hidden">
                            <div class="inline-inclusion-main">
                                <p id="inherited_hearse_summary" class="inline-inclusion-name"></p>
                                <span class="inline-inclusion-status" id="hearse-inclusion-status">Included</span>
                            </div>
                            <div class="inline-inclusion-actions">
                                    <span data-upgrade-charge="hearse" hidden>Additional charge <strong>₱<span id="hearse-inclusion-charge">0.00</span></strong></span>
                                <button type="button" class="inline-upgrade-button" data-upgrade-trigger="hearse" aria-expanded="false" aria-controls="hearse-upgrade-options">Upgrade</button>
                                <button type="button" class="inline-upgrade-button hidden" data-upgrade-reset="hearse">Use included</button>
                                <div id="hearse-upgrade-options" class="inline-upgrade-options" popover aria-label="Hearse options"></div>
                            </div>
                        </div>
                        <div id="custom-hearse-picker" hidden>
                            <button type="button" class="custom-asset-trigger" data-custom-asset-trigger="hearse" aria-controls="custom-hearse-options" aria-expanded="false">Choose a hearse</button>
                            <div id="custom-hearse-options" class="inline-upgrade-options" popover aria-label="Choose a hearse"></div>
                            <select name="hearse_catalog_id" id="hearse_catalog_id" data-label="assigned hearse" class="form-input" hidden disabled>
                                <option value="">Choose an active hearse</option>
                                @foreach($activeHearses as $hearse)
                                    <option value="{{ $hearse->id }}" data-name="{{ $hearse->name }}" data-reference-value="{{ $hearse->reference_value }}" @selected((string) $intakeValue('hearse_catalog_id') === (string) $hearse->id)>{{ $hearse->name }}{{ $hearse->reference_value !== null ? ' - PHP ' . number_format((float) $hearse->reference_value, 2) : ' - Value not configured' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <p id="hearse_upgrade_preview" class="mt-2 text-sm" aria-live="polite"></p>
                        @if($activeHearses->isEmpty())
                            <p class="intake-field-error mt-2" role="alert">No active hearse is available. Ask the System Administrator to configure the Hearse Catalog.</p>
                        @endif
                        @error('hearse_catalog_id') <p class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                    </div>

                    <ul id="selected_package_inclusions" class="hidden"></ul>
                    <ul id="selected_package_freebies" class="hidden"></ul>

                    <div id="intake_service_modal_backdrop" class="intake-modal-backdrop hidden"></div>

                    <div class="service-selection-actions">
                    @error('included_add_on_requested')
                        <p class="intake-field-error" role="alert">{{ $message }}</p>
                    @enderror
                    <div id="optional_add_ons_section" class="hidden service-tool-card">
                        <div class="intake-add-ons-header">
                            <div>
                                <h5 class="service-tool-title">
                                    <i class="bi bi-plus-square"></i> Optional Add-ons
                                </h5>
                                <p class="service-tool-help">Paid extras outside the selected package.</p>
                            </div>
                            <button type="button" id="choose_add_ons_btn" popovertarget="optional_add_ons_panel" aria-expanded="false" aria-controls="optional_add_ons_panel">
                                <i class="bi bi-plus-circle" aria-hidden="true"></i> Choose Add-ons
                            </button>
                        </div>

                        <div id="selected_add_ons_lines" class="hidden space-y-2"></div>
                        <div class="intake-add-ons-totals">
                            <span id="selected_add_ons_count" class="intake-add-ons-count">No add-ons selected</span>
                            <strong>Selected total <span class="whitespace-nowrap">&#8369;<span id="selected_add_ons_total">0.00</span></span></strong>
                        </div>

                        <div id="optional_add_ons_panel" popover class="optional-add-ons-panel hidden overflow-hidden rounded-xl" role="dialog" aria-label="Available optional add-ons">
                            <div class="border-b border-[#E5E0D5] bg-[#FBFAF7] p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <div class="service-tool-title"><i class="bi bi-plus-square"></i> Optional Add-ons <span id="draft_add_ons_header_count" aria-live="polite">0 selected</span></div>
                                        <div class="service-tool-help">Click a card to select or unselect it. Set the quantity for each selected add-on.</div>
                                    </div>
                                    <button type="button" id="close_add_ons_btn" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[#C9C5BB] bg-[#F2F6EF] text-[#3E4A3D] hover:bg-[#DDE8D6]" aria-label="Close optional add-ons">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <div class="optional-add-ons-searchbar">
                                        <div class="intake-addon-search relative min-w-0 flex-1">
                                            <i class="bi bi-search" aria-hidden="true"></i>
                                            <input type="search" id="optional_add_ons_search" class="form-input" placeholder="Add-on name, category, unit or description" aria-label="Search optional add-ons" autocomplete="off" aria-autocomplete="list" aria-controls="addon_search_matches" aria-expanded="false">
                                            <button type="button" id="addon_search_clear" hidden aria-label="Clear add-on search"><i class="bi bi-x" aria-hidden="true"></i></button>
                                            <button type="button" id="addon_search_submit">Search</button>
                                            <div id="addon_search_matches" role="listbox" aria-label="Matching add-ons" hidden></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="optional_add_ons_list" class="max-h-[420px] overflow-y-auto p-4 space-y-2"></div>
                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E5E0D5] bg-[#FBFAF7] p-4">
                                <div class="add-ons-draft-summary"><span id="draft_add_ons_count">No add-ons selected</span><strong>Draft total: &#8369; <span id="draft_add_ons_total">0.00</span></strong></div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" id="cancel_add_ons_btn" class="rounded-lg border border-[#C9C5BB] bg-white px-4 py-2 text-xs font-bold text-[#333333] hover:bg-[#F3F0E8] focus:outline-none focus:ring-2 focus:ring-[#3E4A3D] focus:ring-offset-2">Cancel</button>
                                    <button type="button" id="apply_add_ons_btn" class="inline-flex items-center gap-2 rounded-lg bg-[#3E4A3D] px-4 py-2 text-xs font-bold text-white hover:bg-[#2f382e] focus:outline-none focus:ring-2 focus:ring-[#3E4A3D] focus:ring-offset-2">
                                        <i class="bi bi-check2-circle"></i> Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                        @error('selected_add_ons') <div class="intake-field-error">{{ $message }}</div> @enderror
                    </div>
                    </div>

                    <div id="package-inclusion-controls" hidden>
                            <div id="included-addon-adjustments" class="inline-package-items included-quantity-grid">
                                @foreach($packages->flatMap(fn ($package) => $package->packageInclusions)->whereNotNull('add_on_catalog_id')->unique('add_on_catalog_id') as $inclusion)
                                    <div class="inline-inclusion-card included-quantity-card hidden" data-included-addon="{{ $inclusion->add_on_catalog_id }}">
                                        <div class="inline-inclusion-main">
                                            <label class="inline-inclusion-name" for="requested-addon-{{ $inclusion->add_on_catalog_id }}">{{ $inclusion->inclusion_name }}</label>
                                            <p class="service-tool-help" data-allowance></p>
                                        </div>
                                        <div class="inline-quantity">
                                            <button type="button" data-inclusion-quantity-step="-1" aria-label="Decrease {{ $inclusion->inclusion_name }} quantity">−</button>
                                            <input type="number" min="1" max="9999" step="1" class="form-input" id="requested-addon-{{ $inclusion->add_on_catalog_id }}"
                                                name="included_add_on_requested[{{ $inclusion->add_on_catalog_id }}]" value="{{ $intakeValue('included_add_on_requested.'.$inclusion->add_on_catalog_id) }}" aria-label="Total requested for {{ $inclusion->inclusion_name }}" disabled>
                                            <button type="button" data-inclusion-quantity-step="1" aria-label="Increase {{ $inclusion->inclusion_name }} quantity">+</button>
                                        </div>
                                        <p class="inline-inclusion-note" data-extra-charge aria-live="polite"></p>
                                        @error('included_add_on_requested.'.$inclusion->add_on_catalog_id)
                                            <p class="intake-field-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                            <div class="inline-inclusion-card" data-service-control="casket">
                                <div class="inline-inclusion-main">
                                    <p class="inline-inclusion-name" id="included_casket_summary"></p>
                                    <span class="inline-inclusion-status" id="casket-inclusion-status">Included</span>
                                </div>
                                <div class="inline-inclusion-actions">
                                    <span data-upgrade-charge="casket" hidden>Additional charge <strong>₱<span id="casket_adjustment_amount">0.00</span></strong></span>
                                    <button type="button" class="inline-upgrade-button" data-upgrade-trigger="casket" aria-expanded="false" aria-controls="casket-upgrade-options">Upgrade</button>
                                    <button type="button" class="inline-upgrade-button hidden" data-upgrade-reset="casket">Use included</button>
                                    <div id="casket-upgrade-options" class="inline-upgrade-options" popover aria-label="Casket options"></div>
                                </div>
                                <input type="hidden" name="casket_selection_mode" value="included">
                                <input type="checkbox" name="casket_selection_mode" value="replacement" hidden {{ $intakeValue('replacement_casket_catalog_id') ? 'checked' : '' }}>
                                <div hidden>
                                    <select name="replacement_casket_catalog_id" id="replacement_casket_catalog_id" class="form-input" data-label="replacement casket">
                                                <option value="">Select replacement or upgrade casket</option>
                                                @foreach(($activeCaskets ?? collect()) as $casket)
                                                    <option
                                                        value="{{ $casket->id }}"
                                                        data-name="{{ $casket->name }}"
                                                        data-material="{{ $casket->type_or_material }}"
                                                        data-price="{{ $casket->standard_price }}"
                                                        @disabled(! $casket->is_available)
                                                        {{ (string) $intakeValue('replacement_casket_catalog_id') === (string) $casket->id ? 'selected' : '' }}
                                                    >
                                                        {{ $casket->name }}{{ $casket->type_or_material ? ' - '.$casket->type_or_material : '' }} (&#8369;{{ number_format((float) $casket->standard_price, 2) }}){{ $casket->is_available ? '' : ' — Unavailable' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                </div>
                                <p id="casket_upgrade_preview" class="inline-inclusion-note" aria-live="polite" hidden></p>
                                @error('replacement_casket_catalog_id') <div class="intake-field-error inline-inclusion-note">{{ $message }}</div> @enderror
                            </div>

                            <div class="inline-service-control" data-service-control="embalming">
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h6 class="text-sm font-black text-slate-900">Embalming Days</h6>
                                                <p class="mt-1 text-xs font-medium text-slate-500">Uses total wake days from Wake Start Date through Interment Date.</p>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-black text-slate-800">
                                                Charge: &#8369; <span id="embalming_charge_amount">0.00</span>
                                            </div>
                                        </div>
                                        <div id="embalming_config_warning" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">
                                            <i class="bi bi-exclamation-triangle mr-1"></i> Ask Admin to configure included days and extended-day rate for Embalming.
                                        </div>
                                        <div class="inline-service-metrics">
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Included</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="embalming_included_days">0</span> day(s)</span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Actual Wake Days</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="embalming_actual_days">0 Wake Days</span></span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Extended</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="embalming_extended_days">0</span> day(s)</span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Rate</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800">&#8369; <span id="embalming_rate">0.00</span></span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Source</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800">Schedule</span>
                                            </div>
                                        </div>
                                        <p id="embalming_charge_preview" class="mt-2 text-xs text-slate-500">Charged only when wake days exceed included embalming days.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="inline-service-control" data-service-control="home_viewing">
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h6 class="text-sm font-black text-slate-900">Home Viewing Days</h6>
                                                <p class="mt-1 text-xs font-medium text-slate-500">Uses the same actual wake days from the service schedule.</p>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-black text-slate-800">
                                                Charge: &#8369; <span id="viewing_charge_amount">0.00</span>
                                            </div>
                                        </div>
                                        <div id="viewing_config_warning" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">
                                            <i class="bi bi-exclamation-triangle mr-1"></i> Ask Admin to configure included days and extended-day rate for Home Viewing.
                                        </div>
                                        <div class="inline-service-metrics">
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Included</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="viewing_included_days">0</span> day(s)</span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Actual Wake Days</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="viewing_actual_days">0 Wake Days</span></span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Extended</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="viewing_extended_days">0</span> day(s)</span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Rate</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800">&#8369; <span id="viewing_rate">0.00</span></span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Source</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800">Schedule</span>
                                            </div>
                                        </div>
                                        <p id="viewing_charge_preview" class="mt-2 text-xs text-slate-500">Charged only when wake days exceed included home viewing days.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="inline-service-control" data-service-control="body_retrieval">
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h6 class="text-sm font-black text-slate-900">Body Retrieval Distance</h6>
                                                <p class="mt-1 text-xs font-medium text-slate-500">Distance used to retrieve the deceased from the pickup location.</p>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-black text-slate-800">
                                                Charge: &#8369; <span id="retrieval_charge_amount">0.00</span>
                                            </div>
                                        </div>
                                        <div id="retrieval_config_warning" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">
                                            <i class="bi bi-exclamation-triangle mr-1"></i> Ask Admin to configure included kilometers and excess kilometer rate for Body Retrieval.
                                        </div>
                                        <div class="inline-service-metrics">
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Included Coverage</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="retrieval_included_km">0.00</span> km</span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Excess Entered</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="retrieval_excess_km">0.00</span> km</span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Rate</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800">&#8369; <span id="retrieval_rate">0.00</span></span>
                                            </div>
                                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-white p-3 hover:border-slate-300">
                                                <input type="hidden" name="apply_retrieval_excess" value="0">
                                                <input type="checkbox" name="apply_retrieval_excess" id="apply_retrieval_excess" value="1" class="mt-1 h-4 w-4 text-slate-900 focus:ring-slate-900" {{ $intakeValue('apply_retrieval_excess') ? 'checked' : '' }}>
                                                <span>
                                                    <span class="block text-sm font-bold text-slate-800">Add excess retrieval distance</span>
                                                    <span class="block text-xs text-slate-500">Leave off when within package coverage.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div id="retrieval_excess_wrap" class="mt-3 hidden">
                                            <label class="field-label" for="retrieval_excess_kilometers">Excess KM</label>
                                            <input type="number" step="0.01" min="0" name="retrieval_excess_kilometers" id="retrieval_excess_kilometers" value="{{ $intakeValue('retrieval_excess_kilometers') }}" class="form-input" placeholder="0" data-label="retrieval excess kilometers">
                                            @error('retrieval_excess_kilometers') <div class="intake-field-error">{{ $message }}</div> @enderror
                                        </div>
                                        <p id="retrieval_charge_preview" class="mt-2 text-xs text-slate-500">Within included coverage unless excess kilometers are added.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="inline-service-control" data-service-control="hearse">
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h6 class="text-sm font-black text-slate-900">Hearse Service Distance</h6>
                                                <p class="mt-1 text-xs font-medium text-slate-500">Distance from the wake or Funeral Ceremony location to the cemetery/interment location.</p>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-black text-slate-800">
                                                Charge: &#8369; <span id="hearse_charge_amount">0.00</span>
                                            </div>
                                        </div>
                                        <div id="hearse_config_warning" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">
                                            <i class="bi bi-exclamation-triangle mr-1"></i> Ask Admin to configure included kilometers and excess kilometer rate for Hearse Service.
                                        </div>
                                        <div class="inline-service-metrics">
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Included Coverage</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="hearse_included_km">0.00</span> km</span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Excess Entered</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="hearse_excess_km">0.00</span> km</span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Rate</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800">&#8369; <span id="hearse_rate">0.00</span></span>
                                            </div>
                                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-white p-3 hover:border-slate-300">
                                                <input type="hidden" name="apply_hearse_excess" value="0">
                                                <input type="checkbox" name="apply_hearse_excess" id="apply_hearse_excess" value="1" class="mt-1 h-4 w-4 text-slate-900 focus:ring-slate-900" {{ $intakeValue('apply_hearse_excess') ? 'checked' : '' }}>
                                                <span>
                                                    <span class="block text-sm font-bold text-slate-800">Add excess hearse distance</span>
                                                    <span class="block text-xs text-slate-500">Leave off when within package coverage.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div id="hearse_excess_wrap" class="mt-3 hidden">
                                            <label class="field-label" for="hearse_excess_kilometers">Excess KM</label>
                                            <input type="number" step="0.01" min="0" name="hearse_excess_kilometers" id="hearse_excess_kilometers" value="{{ $intakeValue('hearse_excess_kilometers') }}" class="form-input" placeholder="0" data-label="hearse excess kilometers">
                                            @error('hearse_excess_kilometers') <div class="intake-field-error">{{ $message }}</div> @enderror
                                        </div>
                                        <p id="hearse_charge_preview" class="mt-2 text-xs text-slate-500">Within included coverage unless excess kilometers are added.</p>
                                    </div>
                                </div>
                            </div>
                    </div>

                </section>

                <section class="wizard-panel hidden" data-step="4" id="intake-step-4">
                    <div class="section-title-block">
                        <div class="section-heading-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="section-title-text">
                            <h3>Billing &amp; Payment</h3>
                            <p>Review the summary and record the initial deposit or full payment.</p>
                        </div>
                    </div>

                    @php
                        $promotionReview = session('promotion_review');
                    @endphp
                    @if(is_array($promotionReview) && (string) old('package_id') === (string) ($promotionReview['package_id'] ?? ''))
                        <div class="promotion-review-banner" role="alert" aria-labelledby="promotion-review-title">
                            <div class="promotion-review-banner__heading">
                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                <div>
                                    <h4 id="promotion-review-title">Promotion changed since package selection</h4>
                                    <p>{{ $promotionReview['package_name'] ?? 'Selected package' }} was repriced using the current promotion status. Review the updated amount before saving.</p>
                                </div>
                            </div>
                            <div class="promotion-review-banner__comparison">
                                <div><span>Previously shown total</span><strong>₱{{ number_format((float) ($promotionReview['previous_package_amount'] ?? 0), 2) }}</strong><small>{{ $promotionReview['previous_status'] ?? 'Unknown' }}</small></div>
                                <div aria-hidden="true" class="promotion-review-banner__arrow"><i class="bi bi-arrow-right"></i></div>
                                <div><span>Current calculated total</span><strong>₱{{ number_format((float) ($promotionReview['current_package_amount'] ?? 0), 2) }}</strong><small>{{ $promotionReview['current_status'] ?? 'Unknown' }}</small></div>
                            </div>
                            <label class="promotion-review-banner__acknowledgement">
                                <input type="checkbox" name="promotion_acknowledged" value="1" required @checked(old('promotion_acknowledged'))>
                                <span>I reviewed and accept the current server-calculated amount for this case.</span>
                            </label>
                        </div>
                    @endif

                    @if(is_array($addOnReview) && count($addOnReview['items'] ?? []))
                        <div class="promotion-review-banner" role="alert" aria-labelledby="add-on-review-title">
                            <div class="promotion-review-banner__heading">
                                <i class="bi bi-tag" aria-hidden="true"></i>
                                <div>
                                    <h4 id="add-on-review-title">Add-on prices changed</h4>
                                    <p>Catalog prices or billing units changed while this intake was open. Review the current price for each selected Add-on before saving.</p>
                                </div>
                            </div>
                            <div class="mt-3 space-y-2">
                                @foreach($addOnReview['items'] as $item)
                                    <div class="grid grid-cols-1 gap-1 rounded-lg border border-[#e1d2b4] bg-white/60 px-3 py-2 text-sm sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center">
                                        <strong class="min-w-0 break-words">{{ $item['name'] }}</strong>
                                        <span class="text-xs text-[#625844]">Before: ₱{{ number_format((float) ($item['previous_price'] ?? 0), 2) }} / {{ $item['previous_unit'] ?: 'unit' }}</span>
                                        <span class="text-xs font-bold text-[#302b22]">Now: ₱{{ number_format((float) ($item['current_price'] ?? 0), 2) }} / {{ $item['current_unit'] ?: 'unit' }} · Qty {{ (int) ($item['quantity'] ?? 1) }} · Line total ₱{{ number_format((float) ($item['current_line_total'] ?? 0), 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <label class="promotion-review-banner__acknowledgement mt-3">
                                <input type="checkbox" name="add_on_acknowledged" value="1" required @checked(old('add_on_acknowledged'))>
                                <span>I reviewed and accept the current Add-on prices and line totals.</span>
                            </label>
                            @error('add_on_acknowledged') <div class="intake-field-error mt-2">{{ $message }}</div> @enderror
                        </div>
                    @endif

                            <div id="billing_additional_services_section" class="wizard-divider-section">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-600">Manual Charges</h5>
                                        <p class="mt-1 text-xs font-medium text-slate-500">Use this only for requested charges that are not listed in the add-ons catalog.</p>
                                    </div>
                                    <button type="button" id="add_additional_service_item" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100">
                                        <i class="bi bi-plus-circle"></i> Add Line
                                    </button>
                                </div>
                                <div id="additional_service_items_list" class="mt-4 space-y-2" role="region" aria-label="Manual charge lines"></div>
                                <input type="hidden" name="additional_service_amount" id="additional_service_amount" value="{{ $intakeValue('additional_service_amount') }}">
                                <input type="hidden" name="additional_services" id="additional_services" value="{{ $intakeValue('additional_services') }}">
                            </div>

                    <div class="billing-workspace grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-6 mt-6">

                        {{-- LEFT: Record Payment card --}}
                        <div class="billing-payment-section">
                            <div class="billing-section-heading flex items-center gap-3">
                                <div class="billing-heading-icon w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-credit-card text-xs" aria-hidden="true"></i>
                                </div>
                                <span class="font-bold text-slate-800 text-sm">Record Payment</span>
                            </div>
                            <div class="p-5 billing-payment-stack">

                                {{-- Hidden billing inputs still used by JS --}}
                                <input type="hidden" name="package_amount" id="package_amount" value="{{ $intakeValue('package_amount') }}">
                                <input type="hidden" name="tax_rate" id="tax_rate" value="0">
                                <input type="hidden" id="auto_discount_type" value="None">
                                <input type="hidden" id="auto_discount_amount" value="PHP 0.00">

                                {{-- Discount info note --}}
                                <div id="discount_help_wrap" class="text-[11px] font-medium text-slate-500 flex gap-2 items-start">
                                    <i class="bi bi-info-circle text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span id="discount_help_text_secondary">Discount is tied to Senior Citizen status.</span>
                                </div>

                                <div class="billing-draft-note text-xs">
                                    <div class="flex items-start gap-2">
                                        <i class="bi bi-save2 mt-0.5 text-[#3E4A3D]"></i>
                                        <p>
                                            Payment details are autosaved only as a local draft. An official payment record is created only after final submission.
                                        </p>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100"></div>

                                @if($isOtherEntryMode)
                                    <input type="hidden" name="mark_as_paid" id="mark_as_paid" value="1">
                                    <input type="hidden" name="payment_type" id="payment_type" value="FULL">
                                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-900 flex gap-3">
                                        <i class="bi bi-shield-check text-amber-600 text-lg flex-shrink-0"></i>
                                        <div>
                                            <strong class="block text-xs uppercase tracking-wider mb-0.5">Payment Confirmation</strong>
                                            Other-branch completed reports must already be fully paid before they can be encoded.
                                            This report will be saved as a completed, fully paid branch report and routed automatically.
                                        </div>
                                    </div>
                                @endif

                                @if(!$isOtherEntryMode)
                                    <label id="mark_as_paid_label" class="hidden group flex items-center justify-between gap-4 p-4 rounded-xl border-2 {{ $intakeValue('mark_as_paid') ? 'border-slate-800 bg-slate-50' : 'border-slate-200 bg-white' }} cursor-pointer transition-all duration-200 hover:border-slate-400">
                                        <div class="flex items-center gap-3">
                                            <div id="mark_as_paid_icon" class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-200 {{ $intakeValue('mark_as_paid') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-500' }}">
                                                <i class="bi bi-cash-stack text-base"></i>
                                            </div>
                                            <div>
                                                <span class="block text-sm font-bold text-slate-800">Record Payment Now</span>
                                                <span class="block text-xs text-slate-500 mt-0.5">Click to log a deposit or full settlement.</span>
                                            </div>
                                        </div>
                                        <div id="mark_as_paid_toggle" class="relative w-11 h-6 flex-shrink-0">
                                            <input type="checkbox" name="mark_as_paid" id="mark_as_paid" value="1" {{ $intakeValue('mark_as_paid') ? 'checked' : '' }} class="sr-only peer">
                                            <div class="w-11 h-6 rounded-full border-2 transition-all duration-200 peer-checked:bg-slate-900 peer-checked:border-slate-900 border-slate-300 bg-slate-100"></div>
                                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-all duration-200 peer-checked:translate-x-5 peer-checked:shadow-md"></div>
                                        </div>
                                    </label>
                                @endif

                                <div id="payment_form_fields" class="space-y-5">
                                    {{-- Payment Method --}}
                                    <div>
                                        <div class="payment-method-heading">
                                            <span class="field-label text-xs">Payment Method</span>
                                            <button type="button" id="change_intake_payment_method" class="inline-upgrade-button hidden" aria-controls="payment_method_group">Change payment method</button>
                                        </div>
                                        <div class="billing-method-choice" id="payment_method_group" role="radiogroup" aria-label="Payment method">
                                            <label class="payment-method-card cursor-pointer">
                                                <input type="radio" name="payment_method" value="cash" class="payment-method-radio sr-only" {{ in_array($intakeValue('payment_method'), ['cash', 'CASH'], true) ? 'checked' : '' }}>
                                                <span class="flex flex-col gap-1 text-sm font-bold text-slate-700">
                                                    <span class="payment-choice-title"><span class="payment-choice-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span> Cash</span>
                                                    <span class="text-xs font-medium text-slate-500">Record an over-the-counter payment.</span>
                                                </span>
                                            </label>
                                            <label class="payment-method-card cursor-pointer">
                                                <input type="radio" name="payment_method" value="cashless" class="payment-method-radio sr-only" {{ in_array($intakeValue('payment_method'), ['cashless', 'bank_transfer', 'BANK_TRANSFER'], true) ? 'checked' : '' }}>
                                                <span class="flex flex-col gap-1 text-sm font-bold text-slate-700">
                                                    <span class="payment-choice-title"><span class="payment-choice-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span> Cashless</span>
                                                    <span class="text-xs font-medium text-slate-500">Bank, wallet, card, or other channel.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <p id="payment_method_error" class="intake-field-error hidden" role="alert">Select Cash or Cashless to record a payment.</p>
                                    </div>

                                    <div id="payment_details_section" class="payment-details-shell hidden space-y-5">
                                    <div id="cash_reference_wrap">
                                        <label class="field-label" for="cash_reference_number">Receipt / OR No. <span class="text-rose-500">*</span></label>
                                        <input type="text" name="receipt_or_no" id="cash_reference_number" value="{{ $intakeValue('receipt_or_no') }}" data-label="Receipt / OR number" class="form-input" minlength="3" maxlength="50" placeholder="e.g., OR-001234">
                                        @error('receipt_or_no') <p id="cash_reference_number_inline_error" class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                    </div>

                                    <div id="cashless_details_wrap" class="hidden space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <div>
                                            <label class="field-label" for="cashless_type">Cashless Type <span class="text-rose-500">*</span></label>
                                            <div class="payment-select-wrap"><i class="bi bi-credit-card payment-select-icon" aria-hidden="true"></i>
                                            <select name="cashless_type" id="cashless_type" class="form-input" data-native-select>
                                                <option value="">Select cashless type</option>
                                                <option value="bank_transfer" {{ $intakeValue('cashless_type', in_array($intakeValue('payment_method'), ['bank_transfer', 'BANK_TRANSFER'], true) ? 'bank_transfer' : null) === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                                <option value="gcash" {{ $intakeValue('cashless_type') === 'gcash' ? 'selected' : '' }}>GCash</option>
                                                <option value="maya" {{ $intakeValue('cashless_type') === 'maya' ? 'selected' : '' }}>Maya</option>
                                                <option value="card" {{ $intakeValue('cashless_type') === 'card' ? 'selected' : '' }}>Card</option>
                                                <option value="other" {{ $intakeValue('cashless_type') === 'other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                            <i class="bi bi-chevron-down payment-select-chevron" aria-hidden="true"></i></div>
                                            @error('cashless_type') <p id="cashless_type_inline_error" class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                        </div>

                                        <div data-intake-cashless-panel="bank_transfer" class="intake-cashless-panel hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="field-label" for="bank_name">Bank Name <span class="text-rose-500">*</span></label>
                                                <div class="payment-select-wrap"><i class="bi bi-bank payment-select-icon" aria-hidden="true"></i>
                                                <select name="bank_name" id="bank_name" class="form-input">
                                                    <option value="">Select bank</option>
                                                    @foreach(['BDO', 'BPI', 'Metrobank', 'Landbank', 'Security Bank', 'UnionBank', 'RCBC', 'PNB', 'China Bank', 'Other Bank'] as $bank)
                                                        <option value="{{ $bank }}" {{ $intakeValue('bank_name') === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                                                    @endforeach
                                                </select>
                                                <i class="bi bi-chevron-down payment-select-chevron" aria-hidden="true"></i></div>
                                                @error('bank_name') <p id="bank_name_inline_error" class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="field-label" for="account_name">Account Name <span class="payment-field-note">(Optional)</span></label>
                                                <input type="text" name="account_name" id="account_name" value="{{ $intakeValue('account_name') }}" class="form-input" maxlength="120" placeholder="e.g. Maria Dela Cruz">
                                            </div>
                                            <div id="other_bank_name_wrap" class="hidden md:col-span-2">
                                                <label class="field-label" for="other_bank_name">Other Bank Name <span class="text-rose-500">*</span></label>
                                                <input type="text" name="other_bank_name" id="other_bank_name" value="{{ $intakeValue('other_bank_name') }}" class="form-input" minlength="2" maxlength="80" placeholder="e.g. EastWest Bank">
                                                @error('other_bank_name') <p id="other_bank_name_inline_error" class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                            </div>
                                        </div>

                                        <div data-intake-cashless-panel="gcash" class="intake-cashless-panel hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="field-label" for="gcash_account_name">GCash Account Name <span class="payment-field-note">(Optional)</span></label>
                                                <input type="text" id="gcash_account_name" data-intake-account-name-field value="{{ $intakeValue('account_name') }}" class="form-input" maxlength="120" placeholder="e.g. Maria Dela Cruz">
                                            </div>
                                            <div>
                                                <label class="field-label" for="gcash_mobile_number">GCash Mobile Number <span class="payment-field-note">(Optional)</span></label>
                                                <input type="text" id="gcash_mobile_number" data-intake-mobile-number-field value="{{ $intakeValue('mobile_number') }}" class="form-input" maxlength="30" inputmode="tel" placeholder="e.g. 09171234567">
                                            </div>
                                        </div>

                                        <div data-intake-cashless-panel="maya" class="intake-cashless-panel hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="field-label" for="maya_account_name">Maya Account Name <span class="payment-field-note">(Optional)</span></label>
                                                <input type="text" id="maya_account_name" data-intake-account-name-field value="{{ $intakeValue('account_name') }}" class="form-input" maxlength="120" placeholder="e.g. Maria Dela Cruz">
                                            </div>
                                            <div>
                                                <label class="field-label" for="maya_mobile_number">Maya Mobile Number <span class="payment-field-note">(Optional)</span></label>
                                                <input type="text" id="maya_mobile_number" data-intake-mobile-number-field value="{{ $intakeValue('mobile_number') }}" class="form-input" maxlength="30" inputmode="tel" placeholder="e.g. 09171234567">
                                            </div>
                                        </div>

                                        <div data-intake-cashless-panel="card" class="intake-cashless-panel hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="field-label" for="card_type">Card Type <span class="payment-field-note">(Optional)</span></label>
                                                <select name="card_type" id="card_type" class="form-input">
                                                    <option value="">Select card type</option>
                                                    <option value="debit" {{ $intakeValue('card_type') === 'debit' ? 'selected' : '' }}>Debit</option>
                                                    <option value="credit" {{ $intakeValue('card_type') === 'credit' ? 'selected' : '' }}>Credit</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="field-label" for="terminal_provider">Terminal / Provider <span class="payment-field-note">(Optional)</span></label>
                                                <input type="text" name="terminal_provider" id="terminal_provider" value="{{ $intakeValue('terminal_provider') }}" class="form-input" maxlength="80" placeholder="e.g. BDO POS">
                                            </div>
                                            <p id="card_payment_hint" class="payment-field-note md:col-span-2">Enter at least one: Approval Code or Transaction Reference No.</p>
                                            <div>
                                                <label class="field-label" for="approval_code">Approval Code</label>
                                                <input type="text" name="approval_code" id="approval_code" value="{{ $intakeValue('approval_code') }}" class="form-input" maxlength="40" placeholder="e.g. A12345" aria-describedby="card_payment_hint">
                                            </div>
                                            <div id="intake_card_reference_slot"></div>
                                            <p id="approval_code_inline_error" class="intake-field-error md:col-span-2 @unless($errors->has('approval_code')) hidden @endunless" role="alert">{{ $errors->first('approval_code') }}</p>
                                        </div>

                                        <div data-intake-cashless-panel="other" class="intake-cashless-panel hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="field-label" for="intake_payment_channel">Payment Channel <span class="text-rose-500">*</span></label>
                                                <input type="text" id="intake_payment_channel" data-intake-payment-channel-field value="{{ $intakeValue('payment_channel') }}" class="form-input" minlength="2" maxlength="80" placeholder="e.g. PalawanPay">
                                                @error('payment_channel') <p id="intake_payment_channel_inline_error" class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="field-label" for="payment_notes">Notes <span class="payment-field-note">(Optional)</span></label>
                                                <input type="text" name="payment_notes" id="payment_notes" value="{{ $intakeValue('payment_notes') }}" class="form-input" maxlength="255" placeholder="e.g. Paid at the branch counter">
                                                @error('payment_notes') <p id="payment_notes_inline_error" class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                            </div>
                                        </div>

                                        <div id="intake_reference_slot">
                                            <div id="intake_reference_field">
                                                <label class="field-label" for="reference_number">Transaction Reference No. <span id="cashless_reference_required" class="text-rose-500">*</span></label>
                                                <input type="text" name="reference_number" id="reference_number" value="{{ $intakeValue('reference_number', $intakeValue('bank_reference')) }}" data-label="reference number" class="form-input" placeholder="e.g. TXN-20240101-001">
                                                @if($intakeValue('payment_method') !== 'cash')
                                                    @error('reference_number') <p id="reference_number_inline_error" class="intake-field-error" role="alert">{{ $message }}</p> @enderror
                                                @endif
                                            </div>
                                        </div>
                                        <input type="hidden" name="wallet_provider" id="wallet_provider" value="{{ $intakeValue('wallet_provider') }}">
                                        <input type="hidden" name="mobile_number" id="mobile_number" value="{{ $intakeValue('mobile_number') }}">
                                        <input type="hidden" name="payment_channel" id="payment_channel" value="{{ $intakeValue('payment_channel') }}">
                                        <input type="hidden" name="bank_reference" id="bank_reference" value="{{ $intakeValue('bank_reference') }}">
                                    </div>
                                    <x-payment-proof-upload context="intake" :proof="$intakeValue('payment_proof_id')" :form-id="$intakeValue('payment_proof_form_id')" />
                                    @if($historicalMode ?? false)
                                        @php
                                            $retainedProof = $intakeValue('historical_primary_existing_proof');
                                        @endphp
                                        @if(is_array($retainedProof) && !empty($retainedProof['path']))
                                            @if(session('historical_parent_submission_id') !== null && $intakeValue('historical_primary_parent_payment_index') !== '')<p class="mt-2 text-sm text-emerald-900">Previously attached proof retained: <a class="font-semibold underline" target="_blank" rel="noopener" href="{{ route('historical-cases.payment-proof', [session('historical_parent_submission_id'), $intakeValue('historical_primary_parent_payment_index')]) }}">{{ $retainedProof['name'] ?? 'View proof' }}</a></p>@endif
                                        @endif
                                        <div class="mt-3">
                                            <label class="field-label" for="payment_proof_missing_reason">If the old payment proof is unavailable, explain why</label>
                                            <textarea name="payment_proof_missing_reason" id="payment_proof_missing_reason" class="form-input min-h-20" maxlength="500" placeholder="Example: Original receipt was not retained; amount and date were transcribed from the branch ledger.">{{ $intakeValue('payment_proof_missing_reason') }}</textarea>
                                            <p class="mt-1 text-xs text-slate-500">A reviewer will see this explanation. Do not claim the payment is verified when no proof is attached.</p>
                                        </div>
                                    @endif
                                    </div>

                                    @if(!$isOtherEntryMode)
                                        <div id="payment_type_section" class="hidden">
                                            <label class="field-label text-xs">Payment Type</label>
                                            <div class="bpay-tab-group" id="payment_type_group" role="radiogroup" aria-label="Payment type">
                                                <label class="payment-type-card cursor-pointer transition-colors">
                                                    <input type="radio" name="payment_type" value="FULL" class="payment-type-radio sr-only" {{ $intakeValue('payment_type') === 'FULL' ? 'checked' : '' }}>
                                                    <span class="payment-type-copy"><span class="payment-choice-title"><i class="bi bi-check-circle" aria-hidden="true"></i> Full Payment</span></span>
                                                </label>
                                                <label class="payment-type-card cursor-pointer transition-colors">
                                                    <input type="radio" name="payment_type" value="PARTIAL" class="payment-type-radio sr-only" {{ $intakeValue('payment_type') === 'PARTIAL' ? 'checked' : '' }}>
                                                    <span class="payment-type-copy"><span class="payment-choice-title"><i class="bi bi-pie-chart" aria-hidden="true"></i> Partial Payment</span></span>
                                                </label>
                                            </div>
                                            <div id="payment_type_error" class="intake-field-error hidden">Please select a type.</div>
                                        </div>
                                    @endif

                                    <div id="payment_amount_section" class="hidden space-y-5">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="field-label" for="amount_paid">Amount Received</label>
                                            <div class="flex items-center rounded-lg border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-slate-900">
                                                <span class="pl-3 pr-2 text-slate-500 font-bold text-lg">&#8369;</span>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0.01"
                                                    name="amount_paid"
                                                    id="amount_paid"
                                                    value="{{ $intakeValue('amount_paid') }}"
                                                    data-label="amount paid"
                                                    class="w-full border-0 focus:outline-none focus:ring-0 font-black p-3 text-lg"
                                                    placeholder="0.00"
                                                >
                                            </div>
                                            <p class="text-[11px] font-medium text-blue-500 mt-1.5" id="payment_amount_hint">
                                                {{ $isOtherEntryMode ? 'Must equal full amount' : 'Enter amount received' }}
                                            </p>
                                            <p class="text-[11px] font-bold text-slate-600 mt-1" id="payment_amount_formatted">₱0.00</p>
                                        </div>
                                        <div>
                                            <label class="field-label" for="paid_at">Payment Received Date &amp; Time</label>
                                            <input type="datetime-local" name="paid_at" id="paid_at" value="{{ $intakeValue('paid_at', '') }}" data-label="payment received date/time" class="form-input">
                                        </div>
                                    </div>

                                    <div class="billing-payment-metrics grid grid-cols-3 gap-3">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Status</div>
                                            <div id="payment_status_preview" class="text-base font-black text-slate-800">UNPAID</div>
                                        </div>
                                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                            <div class="payment-summary-label text-[9px] font-black uppercase tracking-widest text-emerald-600 mb-1">Paid Amount</div>
                                            <div class="text-base font-black text-emerald-800">&#8369; <span id="payment_paid_preview">0.00</span></div>
                                        </div>
                                        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 text-white">
                                            <div class="payment-summary-label text-[9px] font-black uppercase tracking-widest mb-1">Remaining Balance</div>
                                            <div class="text-base font-black">&#8369; <span id="payment_balance_preview">0.00</span></div>
                                        </div>
                                    </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- RIGHT: Live Summary card --}}
                        <div class="billing-summary self-start sticky top-6">
                            <div class="billing-summary-panel">
                                <div class="billing-section-heading flex items-center gap-3">
                                    <div class="billing-heading-icon w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i class="bi bi-receipt text-xs" aria-hidden="true"></i>
                                    </div>
                                    <span class="font-bold text-slate-800 text-sm">Live Summary</span>
                                </div>
                                <div class="p-5 space-y-3 text-sm">

                                    <div class="flex justify-between items-center gap-2">
                                        <span class="text-slate-500">Package</span>
                                        <span class="font-bold text-slate-900 whitespace-nowrap">&#8369;&nbsp;<span id="summary_package_price">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between items-center gap-2">
                                        <span class="text-slate-500">Extras</span>
                                        <span class="font-bold text-slate-900 whitespace-nowrap">&#8369;&nbsp;<span id="summary_additional">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between items-center gap-2">
                                        <span class="text-slate-500">Selected Add-ons</span>
                                        <span class="font-bold text-slate-900 whitespace-nowrap">&#8369;&nbsp;<span id="summary_add_ons">0.00</span></span>
                                    </div>
                                    <div id="summary_package_adjustments" class="space-y-2"></div>

                                    <div class="flex justify-between items-center gap-2">
                                        <span class="text-emerald-600 text-xs">Discount (<span id="summary_discount_source">None</span>)</span>
                                        <span class="font-bold text-emerald-600 whitespace-nowrap">&#8722;&nbsp;&#8369;&nbsp;<span id="summary_discount">0.00</span></span>
                                    </div>

                                    <div class="border-t border-slate-100 pt-3 space-y-2">
                                        <div class="flex justify-between items-center gap-2">
                                            <span class="text-slate-400 text-xs">Subtotal</span>
                                            <span class="font-semibold text-slate-700 text-xs whitespace-nowrap">&#8369;&nbsp;<span id="summary_subtotal">0.00</span></span>
                                        </div>
                                        <span id="summary_tax" class="hidden">0.00</span>
                                    </div>

                                    <div class="border-t border-slate-200 pt-4 flex justify-between items-end gap-2">
                                        <span class="font-bold text-slate-800 text-base">Total Amount</span>
                                        <span class="font-black text-slate-900 text-2xl whitespace-nowrap leading-none">&#8369;<span id="summary_total">0.00</span></span>
                                    </div>

                                    <div class="border-t border-slate-100 pt-3 space-y-2 text-xs">
                                        <div class="flex justify-between items-center gap-2 text-slate-500">
                                            <span>Payment Status</span>
                                            <span class="font-bold text-slate-700 whitespace-nowrap"><span id="summary_payment_status_dummy" class="hidden"></span><span id="summary_payment_status" class="font-bold">UNPAID</span></span>
                                        </div>
                                        <div class="flex justify-between items-start gap-3 text-slate-500">
                                            <span>Payment Method</span>
                                            <span id="summary_payment_method" class="font-bold text-slate-800 text-right">Not recorded</span>
                                        </div>
                                        <div id="summary_bank_row" class="hidden flex justify-between items-start gap-3 text-slate-500">
                                            <span>Bank</span>
                                            <span id="summary_payment_bank" class="font-bold text-slate-800 text-right">-</span>
                                        </div>
                                        <div id="summary_account_row" class="hidden flex justify-between items-start gap-3 text-slate-500">
                                            <span>Account Name</span>
                                            <span id="summary_payment_account" class="font-bold text-slate-800 text-right break-words">-</span>
                                        </div>
                                        <div id="summary_mobile_row" class="hidden flex justify-between items-start gap-3 text-slate-500">
                                            <span>Mobile No.</span>
                                            <span id="summary_payment_mobile" class="font-bold text-slate-800 text-right">-</span>
                                        </div>
                                        <div id="summary_channel_row" class="hidden flex justify-between items-start gap-3 text-slate-500">
                                            <span id="summary_channel_label">Payment Channel</span>
                                            <span id="summary_payment_channel" class="font-bold text-slate-800 text-right break-words">-</span>
                                        </div>
                                        <div id="summary_reference_row" class="hidden flex justify-between items-start gap-3 text-slate-500">
                                            <span id="summary_reference_label">Reference No.</span>
                                            <span id="summary_payment_reference" class="font-bold text-slate-800 text-right break-all">-</span>
                                        </div>
                                        <div data-proof-review-wrap class="flex justify-between items-start gap-3 text-slate-500" hidden>
                                            <span>Payment proof</span><a data-proof-review target="_blank" rel="noopener" hidden>View Attached Proof</a>
                                        </div>
                                        <div id="summary_notes_row" class="hidden flex justify-between items-start gap-3 text-slate-500">
                                            <span>Notes</span>
                                            <span id="summary_payment_notes" class="font-bold text-slate-800 text-right break-words">-</span>
                                        </div>
                                        <div class="flex justify-between items-start gap-3 text-slate-500">
                                            <span>Paid Amount</span>
                                            <span class="font-bold text-slate-800 whitespace-nowrap">&#8369;&nbsp;<span id="summary_paid_amount">0.00</span></span>
                                        </div>
                                        <div class="flex justify-between items-center gap-2 text-slate-500">
                                            <span>Remaining Balance</span>
                                            <span class="font-bold text-slate-800 whitespace-nowrap">&#8369;&nbsp;<span id="summary_balance">0.00</span></span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    @if($historicalMode ?? false)
                        <section class="mt-6 rounded-2xl bg-amber-50/70 p-4 md:p-5" aria-labelledby="historical-payments-heading">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h4 id="historical-payments-heading" class="text-base font-bold text-slate-900">Other Past Payments</h4>
                                    <p class="mt-1 text-sm text-slate-600">Add each old payment with its actual date. These stay pending and are not official until approval.</p>
                                </div>
                                <button type="button" id="add_historical_payment" class="inline-flex items-center gap-2 rounded-xl bg-emerald-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-900"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add payment</button>
                            </div>
                            @error('historical_payments')<p class="mt-3 rounded-lg bg-rose-100 px-3 py-2 text-sm font-semibold text-rose-900" role="alert">{{ $message }}</p>@enderror
                            <div id="historical_payment_rows" class="mt-4 space-y-4">
                                @foreach(old('historical_payments', []) as $index => $row)
                                    @include('staff.intake._historical_payment_row', ['index' => $index, 'row' => $row])
                                @endforeach
                            </div>
                            <template id="historical_payment_template">
                                @include('staff.intake._historical_payment_row', ['index' => '__INDEX__', 'row' => [], 'isTemplate' => true])
                            </template>
                            <p class="mt-3 text-xs text-slate-600">Payment proof is strongly preferred. If the original proof is unavailable, explain why so the reviewer can assess the ledger evidence.</p>
                        </section>
                        <script>
                            (() => {
                                const list = document.getElementById('historical_payment_rows');
                                const template = document.getElementById('historical_payment_template');
                                const add = document.getElementById('add_historical_payment');
                                if (!list || !template || !add) return;
                                let next = Math.max(-1, ...[...list.querySelectorAll('[data-historical-payment-index]')].map((el) => Number(el.dataset.historicalPaymentIndex) || 0)) + 1;
                                const bind = (card) => {
                                    card.querySelector('[data-remove-historical-payment]')?.addEventListener('click', () => card.remove());
                                    const method = card.querySelector('[data-historical-method]');
                                    const cashless = card.querySelector('[data-historical-cashless-fields]');
                                    const update = () => cashless?.classList.toggle('hidden', method?.value !== 'cashless');
                                    method?.addEventListener('change', update); update();
                                };
                                list.querySelectorAll('[data-historical-payment-index]').forEach(bind);
                                add.addEventListener('click', () => {
                                    const html = template.innerHTML.replaceAll('__INDEX__', String(next)).replaceAll('__FORM_ID__', (crypto.randomUUID?.() || `hist-${Date.now()}-${next}`));
                                    const holder = document.createElement('div'); holder.innerHTML = html.trim();
                                    const card = holder.firstElementChild; list.append(card); bind(card); next += 1;
                                });
                            })();
                        </script>
                    @endif
                </section>

                <section class="wizard-panel hidden" data-step="5" id="intake-step-5">
                    <div class="section-title-block pb-4 border-b border-slate-100">
                        <div class="section-heading-icon">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <div class="section-title-text">
                            <h3>Final Review</h3>
                            <p>Check each section carefully before saving the record.</p>
                        </div>
                    </div>

                    @php
                        $reviewCards = [
                            'client'   => ['title' => 'Client',   'step' => 1],
                            'deceased' => ['title' => 'Deceased',  'step' => 2],
                            'package'  => ['title' => 'Package',   'step' => 3],
                            'service'  => ['title' => 'Service',   'step' => 3],
                            'billing'  => ['title' => 'Billing',   'step' => 4],
                            'payment'  => ['title' => 'Payment',   'step' => 4],
                        ];
                    @endphp
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        @foreach($reviewCards as $id => $card)
                            <div class="wizard-review-section group relative">
                                <button
                                    type="button"
                                    class="review-edit absolute top-4 right-4 text-[10px] font-bold uppercase tracking-wider text-slate-600 hover:text-slate-900 flex items-center gap-1 min-h-8 px-2"
                                    data-jump-step="{{ $card['step'] }}"
                                    aria-label="Edit {{ $card['title'] }} details"
                                >
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Edit
                                </button>

                                <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">{{ $card['title'] }}</h4>
                                <dl class="space-y-2 text-xs text-slate-700" id="review_{{ $id }}"></dl>
                            </div>
                        @endforeach
                    </div>

                    @if($historicalMode ?? false)
                        <div class="mt-6 rounded-xl bg-amber-50/70 p-4 md:p-5">
                            <label class="field-label text-slate-900" for="historical_entry_reason">Why is this completed case being recorded now? <span class="text-rose-600">*</span></label>
                            <textarea name="historical_entry_reason" id="historical_entry_reason" class="form-input min-h-24" minlength="10" maxlength="1000" required placeholder="Explain why the case was not entered when the service took place.">{{ $intakeValue('historical_entry_reason') }}</textarea>
                            <p class="mt-1 text-xs text-slate-600">This explanation and the submitter will be visible in the approval history.</p>
                            @error('historical_entry_reason')<p class="intake-field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div class="review-confirmation-wrap mt-8">
                        <label for="confirm_review" class="review-confirmation rounded-xl border p-5 flex items-start gap-4 cursor-pointer" aria-describedby="confirm_review_error">
                            <input type="checkbox" name="confirm_review" id="confirm_review" value="1" class="review-confirmation-input" required>
                            <span class="review-confirmation-mark" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                            <span class="review-confirmation-copy text-base font-semibold leading-snug">
                                I verify that I have reviewed this data and it is accurate to the best of my knowledge.
                            </span>
                        </label>
                        <p id="confirm_review_error" class="intake-field-error hidden" role="alert"></p>
                    </div>
                </section>
            </div>

            <div class="footer-action-bar p-3 sm:p-5 flex items-center gap-3 sm:justify-between bg-transparent border-t border-slate-200">
                {{-- Left group: Cancel --}}
                <div class="footer-left-group flex items-center gap-2 shrink-0">
                    <button type="button" id="intakeCancelBtn">
                        Cancel
                    </button>
                </div>

                <p id="schedule_advisory_summary" class="schedule-advisory-summary" role="status" hidden></p>
                {{-- Right group: Back + Continue / Save --}}
                <div class="footer-right-group flex flex-1 sm:flex-none sm:ml-auto items-center gap-2">
                    <button
                        type="submit"
                        id="saveIntakeDraft"
                        formaction="{{ route('intake.drafts.save', $intakeModal ? ['intake_modal' => 1] : []) }}"
                        formmethod="POST"
                        formnovalidate
                        class="hidden"
                        aria-hidden="true"
                        tabindex="-1"
                    >
                        <i class="bi bi-save2" aria-hidden="true"></i>
                        Save Draft
                    </button>
                    <button type="button" id="wizardPrev" class="w-auto shrink-0 px-6 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all disabled:opacity-30 disabled:hover:bg-white">
                        <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>
                        Back
                    </button>
                    <button type="button" id="wizardNext" class="w-full sm:w-auto sm:min-w-[190px] px-8 py-3 rounded-xl bg-slate-900 text-white text-sm font-bold transition-all hover:bg-[#3E4A3D] hover:-translate-y-0.5">
                        Continue
                    </button>

                    <button type="submit" id="saveIntakeRecord" class="hidden w-full sm:w-auto sm:min-w-[190px] px-8 py-3 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 transition-all disabled:opacity-50">
                        {{ ($historicalMode ?? false) ? 'Submit for Approval' : 'Save Record' }}
                    </button>
                </div>
            </div>
        </form>
        @unless($intakeModal)
        </div>{{-- /intake-form-wrapper --}}
        @endunless
    </div>{{-- /intake-section-shell --}}

    {{-- Cancel Confirmation Modal --}}
    <dialog id="intakeCancelModal" role="dialog" aria-modal="true" aria-labelledby="intakeCancelTitle" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4" style="backdrop-filter:blur(3px);">
        <div class="absolute inset-0" id="intakeCancelModalBackdrop" style="background:var(--modal-scrim);"></div>
        <div class="cancel-modal-box">
            <p id="intakeCancelTitle" class="cancel-modal-title">Save this intake before leaving?</p>
            <p class="cancel-modal-msg">{{ $intakeModal
                ? 'You have unsaved changes. Save them as a draft to continue later, or leave without saving.'
                : 'You have unsaved intake details. Save them as a draft so staff can continue later, or leave without saving.' }}</p>
            <div class="cancel-modal-actions">
                <button type="button" id="intakeCancelModalSaveDraft">
                    <i class="bi bi-save2" aria-hidden="true"></i>
                    Save as Draft
                </button>
                <button type="button" id="intakeCancelModalKeep">Keep Editing</button>
                <button type="button" id="intakeCancelModalDiscard">Leave without saving</button>
            </div>
        </div>
    </dialog>

</div>{{-- /intake-root --}}

<link rel="stylesheet" href="{{ asset('vendor/offline/flatpickr.min.css') }}">
<script src="{{ asset('vendor/offline/flatpickr.min.js') }}"></script>
@include('partials.service-field-popover')
<script>
(() => {
    const f = document.getElementById('intakeWizardForm');
    if (!f) return;

    const panels = [...document.querySelectorAll('.wizard-panel')];
    const tabs = [...document.querySelectorAll('.wizard-tab')];
    const prev = document.getElementById('wizardPrev');
    const next = document.getElementById('wizardNext');
    const save = document.getElementById('saveIntakeRecord');
    const currentStepField = document.getElementById('current_step');

    const formContent = document.getElementById('intakeFormContent');
    const wizardSteps = document.getElementById('wizardSteps');
    const lockOverlay = document.getElementById('intake_lock_overlay');

    const branch = document.getElementById('branch_id');
    const viewingType = document.getElementById('viewing_type');
    const viewingLocation = document.getElementById('wake_location');
    const viewingLocationWrap = document.getElementById('viewing_location_wrap');
    const viewingLocationLabel = document.getElementById('viewing_location_label');
    const viewingLocationRequired = document.getElementById('viewing_location_required');
    const chapelAddress = document.getElementById('chapel_address');
    const chapelChargePanel = document.getElementById('chapel_charge_panel');
    const viewingFields = viewingType.closest('.viewing-fields');
    const viewingCemetery = viewingFields.querySelector('.viewing-cemetery');
    const chapelChargeControls = document.getElementById('chapel_charge_controls');
    const chapelRate = document.getElementById('chapel_add_on_id');
    const chapelRateReadout = document.getElementById('chapel_rate_readout');
    const chapelAddressReadout = document.getElementById('chapel_address_readout');
    const chapelPreviewPanel = document.getElementById('chapel_preview_panel');
    const chapelScheduleBox = document.getElementById('chapel-schedule-context');
    const chapelChargePreview = document.getElementById('chapel_charge_preview');
    const chapelCoverageChoices = [...f.querySelectorAll('[name="chapel_coverage"]')];
    const chapelCoverageError = document.getElementById('chapel_coverage_error');
    const chapelPackageFeedback = document.getElementById('chapel_package_feedback');
    const chapelSaveDraft = document.getElementById('chapel_save_draft');
    const homeViewingNotice = document.getElementById('home_viewing_notice');
    const chapelCategory = @json(\App\Models\AddOnCatalog::CHAPEL_VIEWING);
    const isChapelViewing = () => viewingType.value === 'chapel';
    const chapelCoverage = () => chapelCoverageChoices.find(choice => choice.checked)?.value || '';
    let homeViewingAddress = isChapelViewing() ? '' : viewingLocation.value;
    const syncViewingLocation = (animate = false) => {
        const chapel = isChapelViewing();
        const selected = !!viewingType.value;
        viewingFields.toggleAttribute('data-chapel-viewing', chapel);
        if (chapel && viewingCemetery.nextElementSibling !== chapelChargePanel) {
            chapelChargePanel.before(viewingCemetery);
        } else if (!chapel && viewingCemetery.previousElementSibling !== viewingLocationWrap) {
            viewingLocationWrap.after(viewingCemetery);
        }
        viewingLocation.disabled = !selected && !viewingLocation.value;
        viewingLocation.required = !chapel;
        viewingLocationWrap.hidden = chapel;
        viewingLocationRequired.hidden = !selected;
        viewingLocationLabel.textContent = chapel ? 'Chapel Name' : selected ? 'Wake Address' : 'Viewing Location';
        viewingLocation.dataset.label = viewingLocationLabel.textContent.toLowerCase();
        viewingLocation.placeholder = chapel ? 'e.g., Main Branch Chapel'
            : selected ? 'e.g., 24 Mabini St., Brgy. San Jose, Quezon City' : 'Choose a viewing type first';
        chapelAddress.disabled = !chapel;
        if (animate && selected && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
            viewingLocationWrap.animate?.([{ opacity:.65 }, { opacity:1 }], { duration:160, easing:'ease-out' });
        }
    };
    const branchError = document.getElementById('branch_error');
    const branchBtns = [...document.querySelectorAll('.branch-toggle')];
    const nextCode = document.getElementById('next_case_code');
    const nextMap = @json($nextCodeMap ?? []);
    const defCode = @json($nextCode ?? 'FC0001');
    const mainBranchCodeDefault = document.getElementById('branch_code_main_default')?.value || 'BR001';

    const requestDate = document.getElementById('service_requested_at');
    const requestDateDisplay = document.getElementById('service_requested_display');
    const historicalIntake = document.querySelector('input[name="historical_mode"]')?.value === '1';

    const reporterBox = document.getElementById('external_reporter_box');
    const reporterName = document.getElementById('reporter_name');
    const reportedAt = document.getElementById('reported_at');

    const born = document.getElementById('born');
    const died = document.getElementById('died');
    let bornPicker = null;
    let diedPicker = null;
    const age = document.getElementById('age');

    const clientAddr = document.getElementById('client_address');
    const deceasedAddr = document.getElementById('deceased_address');
    const clientRelationship = document.getElementById('client_relationship');
    const copyClientSurnames = document.getElementById('copy_client_surnames');
    const surnameFields = ['middle_name', 'last_name'].map(name => ({
        source: f.elements.namedItem(`client_${name}`),
        destination: f.elements.namedItem(`deceased_${name}`),
    }));
    const availableSurnameCopies = () => surnameFields.filter(({ source, destination }) =>
        source?.value.trim() && destination && !destination.value.trim());
    const syncSurnameCopy = () => {
        const family = ['Father', 'Mother', 'Spouse', 'Child', 'Sibling', 'Relative'];
        copyClientSurnames.hidden = !family.includes(clientRelationship?.value) || !availableSurnameCopies().length;
    };

    const wakeDays = document.getElementById('wake_days');
    const wakeDurationDisplay = document.getElementById('wake_duration_display');
    const wakeStart = document.getElementById('wake_start_date');
    const wakeStartTime = document.getElementById('wake_start_time');
    const wakeEnd = document.getElementById('wake_end_date');
    const wakeEndTime = document.getElementById('wake_end_time');
    const funeral = document.getElementById('funeral_service_at');
    const funeralTime = document.getElementById('funeral_service_time');
    const interment = document.getElementById('interment_at');
    const intermentTime = document.getElementById('interment_time');
    const intermentErr = document.getElementById('interment_at_error');
    const intermentWarning = document.getElementById('interment_schedule_warning');
    const bornErr = document.getElementById('born_error');
    const diedErr = document.getElementById('died_error');
    const wakeStartErr = document.getElementById('wake_start_error');
    const wakeEndErr = document.getElementById('wake_end_error');
    const wakeErr = document.getElementById('funeral_service_at_error');
    let wakePicker = null;
    let wakeStartPicker = null;
    let wakeEndPicker = null;
    let interPicker = null;
    let isAutoSyncingIntermentDate = false;
    if (interment) interment.dataset.autoFilled = interment.value ? '0' : '';

    const senior = document.getElementById('senior_citizen_status');
    const seniorIdWrap = document.getElementById('senior_id_wrap');
    const seniorId = document.getElementById('senior_citizen_id_number');
    const proofWrap = document.getElementById('senior_proof_wrap');
    const proofInput = document.getElementById('senior_proof');

    const regularPackageChoice = document.getElementById('regular_package_choice');
    const regularPackagePicker = window.bindServiceFieldSelection(regularPackageChoice, 'Choose a package');
    if (regularPackagePicker) {
        const wrapper = regularPackageChoice.closest('.intake-package-select');
        wrapper.classList.remove('global-filter-select-wrap');
        wrapper.querySelector('[data-filter-select-icon]')?.remove();
    }
    const pkgRadios = [...document.querySelectorAll('.package-radio')];
    const pkgCards = [...document.querySelectorAll('.package-card')];
    const pkgAmount = document.getElementById('package_amount');
    const packageError = document.getElementById('package_error');
    const packageModeChooser = document.querySelector('.package-mode-chooser');
    const packageModeOptions = [...document.querySelectorAll('[data-package-mode]')];
    const regularPackageArea = document.getElementById('regular_package_area');
    const customPackageArea = document.getElementById('custom_package_area');
    let lastRegularPackageValue = pkgRadios.find((radio) => radio.checked && radio.value !== 'custom')?.value || '';

    const customPkgRadio = document.getElementById('custom_package_radio');
    const prefPkg = document.getElementById('client_pref_package');
    const prefPrice = document.getElementById('client_pref_price');
    const prefIncl = document.getElementById('client_pref_inclusions');
    const prefFree = document.getElementById('client_pref_freebies');
    const customPkgFields = document.getElementById('custom_package_fields');
    const customPkgPriceDisplay = document.getElementById('custom_package_price_display');

    const showPackageMode = (mode, { restoreSelection = true } = {}) => {
        const isRegular = mode === 'regular';
        const isCustom = mode === 'custom';
        packageModeChooser?.classList.toggle('hidden', isRegular || isCustom);
        regularPackageArea?.classList.toggle('hidden', !isRegular);
        customPackageArea?.classList.toggle('hidden', !isCustom);
        packageModeOptions.forEach((option) => option.setAttribute('aria-checked', option.dataset.packageMode === mode ? 'true' : 'false'));
        if (isCustom && customPkgRadio) {
            const currentRegular = pkgRadios.find((radio) => radio.checked && radio.value !== 'custom');
            if (currentRegular) lastRegularPackageValue = currentRegular.value;
            customPkgRadio.checked = true;
        } else if (isRegular) {
            const previous = restoreSelection && lastRegularPackageValue
                ? pkgRadios.find((radio) => String(radio.value) === String(lastRegularPackageValue))
                : null;
            if (previous) previous.checked = true;
            else if (customPkgRadio) customPkgRadio.checked = false;
        }
        renderPkg(false);
        syncControls();
        render();
    };

    const syncPackageModeFromSelection = () => {
        const selected = pkgRadios.find(radio => radio.checked);
        if (selected) {
            showPackageMode(selected === customPkgRadio ? 'custom' : 'regular', { restoreSelection: false });
            return;
        }
        lastRegularPackageValue = '';
        packageModeChooser?.classList.remove('hidden');
        regularPackageArea?.classList.add('hidden');
        customPackageArea?.classList.add('hidden');
        packageModeOptions.forEach(option => option.setAttribute('aria-checked', 'false'));
    };

    const inclusions = document.getElementById('selected_package_inclusions');
    const freebies = document.getElementById('selected_package_freebies');
    const hearseAssignmentFields = document.getElementById('hearse_assignment_fields');
    const hearseCatalogSelect = document.getElementById('hearse_catalog_id');
    const optionalAddOnsSection = document.getElementById('optional_add_ons_section');
    const optionalAddOnsList = document.getElementById('optional_add_ons_list');
    const optionalAddOnsPanel = document.getElementById('optional_add_ons_panel');
    const optionalAddOnsSearch = document.getElementById('optional_add_ons_search');
    const chooseAddOnsBtn = document.getElementById('choose_add_ons_btn');
    const applyAddOnsBtn = document.getElementById('apply_add_ons_btn');
    const cancelAddOnsBtn = document.getElementById('cancel_add_ons_btn');
    const closeAddOnsBtn = document.getElementById('close_add_ons_btn');
    const selectedAddOnsCount = document.getElementById('selected_add_ons_count');
    const selectedAddOnsLines = document.getElementById('selected_add_ons_lines');
    const draftAddOnsTotal = document.getElementById('draft_add_ons_total');
    const selectedAddOnsTotal = document.getElementById('selected_add_ons_total');
    const serviceModalBackdrop = document.getElementById('intake_service_modal_backdrop');
    const oldSelectedAddOns = new Set(@json($oldSelectedAddOns));
    const addOnQuantities = new Map(Object.entries(@json($oldAddOnQuantities)).map(([id, quantity]) => {
        const parsed = Number.parseInt(quantity, 10);
        return [String(id), Number.isInteger(parsed) ? parsed : 1];
    }));
    const addOnQuantityErrors = {!! \Illuminate\Support\Js::from($addOnQuantityErrors) !!};
    let draftSelectedAddOns = new Set(oldSelectedAddOns);
    let draftAddOnQuantities = new Map(addOnQuantities);
    const globalAddOns = {!! \Illuminate\Support\Js::from(($activeAddOns ?? collect())->map(fn ($addOn) => [
        'id' => $addOn->id,
        'name' => $addOn->name,
        'category' => $addOn->category,
        'description' => $addOn->description,
        'price' => (float) $addOn->price,
        'unit' => $addOn->unit,
        'branch_id' => $addOn->branch_id,
        'chapel_address' => $addOn->chapel_address,
    ])->values()) !!};
    const serviceTypeLabels = {
        body_retrieval: 'Body Retrieval',
        embalming: 'Embalming',
        casket: 'Casket',
        home_viewing: 'Home Viewing',
        hearse: 'Hearse',
        custom: 'Other Inclusions',
    };
    const replacementCasket = document.getElementById('replacement_casket_catalog_id');
    const casketModeRadios = [...document.querySelectorAll('[name="casket_selection_mode"]')];
    const includedCasketSummary = document.getElementById('included_casket_summary');
    const casketAdjustmentAmount = document.getElementById('casket_adjustment_amount');
    const casketUpgradePreview = document.getElementById('casket_upgrade_preview');
    const applyRetrievalExcess = document.getElementById('apply_retrieval_excess');
    const applyHearseExcess = document.getElementById('apply_hearse_excess');
    const retrievalExcessInput = document.getElementById('retrieval_excess_kilometers');
    const hearseExcessInput = document.getElementById('hearse_excess_kilometers');
    const retrievalExcessWrap = document.getElementById('retrieval_excess_wrap');
    const hearseExcessWrap = document.getElementById('hearse_excess_wrap');
    const retrievalChargePreview = document.getElementById('retrieval_charge_preview');
    const hearseChargePreview = document.getElementById('hearse_charge_preview');
    const retrievalConfigWarning = document.getElementById('retrieval_config_warning');
    const hearseConfigWarning = document.getElementById('hearse_config_warning');
    const retrievalIncludedKm = document.getElementById('retrieval_included_km');
    const retrievalExcessKm = document.getElementById('retrieval_excess_km');
    const retrievalRate = document.getElementById('retrieval_rate');
    const retrievalChargeAmount = document.getElementById('retrieval_charge_amount');
    const hearseIncludedKm = document.getElementById('hearse_included_km');
    const hearseExcessKm = document.getElementById('hearse_excess_km');
    const hearseRate = document.getElementById('hearse_rate');
    const hearseChargeAmount = document.getElementById('hearse_charge_amount');
    const embalmingConfigWarning = document.getElementById('embalming_config_warning');
    const embalmingIncludedDays = document.getElementById('embalming_included_days');
    const embalmingActualDays = document.getElementById('embalming_actual_days');
    const embalmingExtendedDays = document.getElementById('embalming_extended_days');
    const embalmingRate = document.getElementById('embalming_rate');
    const embalmingChargeAmount = document.getElementById('embalming_charge_amount');
    const embalmingChargePreview = document.getElementById('embalming_charge_preview');
    const viewingConfigWarning = document.getElementById('viewing_config_warning');
    const viewingIncludedDays = document.getElementById('viewing_included_days');
    const viewingActualDays = document.getElementById('viewing_actual_days');
    const viewingExtendedDays = document.getElementById('viewing_extended_days');
    const viewingRate = document.getElementById('viewing_rate');
    const viewingChargeAmount = document.getElementById('viewing_charge_amount');
    const viewingChargePreview = document.getElementById('viewing_charge_preview');

    const addAmt = document.getElementById('additional_service_amount');
    const additionalItemsList = document.getElementById('additional_service_items_list');
    const addAdditionalItem = document.getElementById('add_additional_service_item');
    const taxRate = document.getElementById('tax_rate');
    const taxAmountDisplay = document.getElementById('tax_amount_display');
    const additionalServices = document.getElementById('additional_services');
    const autoDiscountType = document.getElementById('auto_discount_type');
    const autoDiscountAmount = document.getElementById('auto_discount_amount');
    const discountHelpSecondary = document.getElementById('discount_help_text_secondary');

    const mark = document.getElementById('mark_as_paid');
    const payWrap = document.getElementById('payment_form_fields');
    const payTypeRadios = [...document.querySelectorAll('.payment-type-radio')];
    const payTypeCards = [...document.querySelectorAll('.payment-type-card')];
    const paymentTypeError = document.getElementById('payment_type_error');
    const amountPaid = document.getElementById('amount_paid');
    const paidAt = document.getElementById('paid_at');
    const payHint = document.getElementById('payment_amount_hint');

    const summaryPackage = document.getElementById('summary_package_price');
    const summaryAdd = document.getElementById('summary_additional');
    const summaryAddOns = document.getElementById('summary_add_ons');
    const summaryPackageAdjustments = document.getElementById('summary_package_adjustments');
    const summarySubtotal = document.getElementById('summary_subtotal');
    const summaryDiscountSource = document.getElementById('summary_discount_source');
    const summaryDiscount = document.getElementById('summary_discount');
    const summaryTax = document.getElementById('summary_tax');
    const summaryTotal = document.getElementById('summary_total');
    const summaryStatus = document.getElementById('summary_payment_status');
    const summaryPaymentMethod = document.getElementById('summary_payment_method');
    const summaryBankRow = document.getElementById('summary_bank_row');
    const summaryPaymentBank = document.getElementById('summary_payment_bank');
    const summaryAccountRow = document.getElementById('summary_account_row');
    const summaryPaymentAccount = document.getElementById('summary_payment_account');
    const summaryMobileRow = document.getElementById('summary_mobile_row');
    const summaryPaymentMobile = document.getElementById('summary_payment_mobile');
    const summaryChannelRow = document.getElementById('summary_channel_row');
    const summaryChannelLabel = document.getElementById('summary_channel_label');
    const summaryPaymentChannel = document.getElementById('summary_payment_channel');
    const summaryReferenceRow = document.getElementById('summary_reference_row');
    const summaryReferenceLabel = document.getElementById('summary_reference_label');
    const summaryPaymentReference = document.getElementById('summary_payment_reference');
    const summaryNotesRow = document.getElementById('summary_notes_row');
    const summaryPaymentNotes = document.getElementById('summary_payment_notes');
    const summaryPaidAmount = document.getElementById('summary_paid_amount');
    const summaryBalance = document.getElementById('summary_balance');

    const paymentStatusPreview = document.getElementById('payment_status_preview');
    const paymentPaidPreview = document.getElementById('payment_paid_preview');
    const paymentBalancePreview = document.getElementById('payment_balance_preview');
    const paymentAmountFormatted = document.getElementById('payment_amount_formatted');

    const reviewClient = document.getElementById('review_client');
    const reviewDeceased = document.getElementById('review_deceased');
    const reviewService = document.getElementById('review_service');
    const reviewPackage = document.getElementById('review_package');
    const reviewBilling = document.getElementById('review_billing');
    const reviewPayment = document.getElementById('review_payment');
    const reviewEditButtons = [...document.querySelectorAll('.review-edit')];

    const branchToast = document.getElementById('branch_toast');
    const pkgToast = document.getElementById('package_toast');

    const seniorPct = {{ (float) ($seniorDiscountPercent ?? config('funeral.senior_discount_percent', 20)) }};
    const isOtherEntryMode = @json($isOtherEntryMode);
    const isIntakeModal = @json($intakeModal);
    const otherBranchWindowClosed = @json($otherBranchWindowClosed);
    const intakeAutosaveKey = [
        'funeral-system',
        'intake-draft',
        @json(auth()->id()),
        @json($isOtherEntryMode ? 'other' : 'main'),
        @json((string) $initialSelectedBranchId),
        @json($intakeDraft ? 'saved-' . $intakeDraft->id : ($intakeModal ? 'modal-new' : 'new')),
    ].join(':');
    const totalSteps = panels.length;
    const initialStep = Math.max(1, Math.min(totalSteps, Number(@json($initialStep ?? 1))));
    const serverInitialBranchId = @json((string) $initialSelectedBranchId);
    const serverOldBranchExists = @json($intakeValue('branch_id') !== null);
    const serverDraftPayload = @json($intakeDraftPayload);

    let step = 1;
    let maxUnlockedStep = initialStep;
    let branchToastTimer = null;
    let packageToastTimer = null;
    const today = new Date().toLocaleDateString('en-CA');

    const num = (value) => Number.parseFloat(value || 0) || 0;
    const fmt = (value) => num(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const wakeDurationLabel = (value) => {
        const days = Math.max(Math.round(num(value)), 0);
        return days > 0 ? `${days} ${days === 1 ? 'Wake Day' : 'Wake Days'}` : '-';
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const cardRadio = (card) => card?.querySelector?.('.package-radio') || null;
    const pkg = () => pkgRadios.find((radio) => radio.checked);
    const payNow = () => !mark ? false : (mark.type === 'checkbox' ? mark.checked : String(mark.value) === '1');
    const payType = () => payTypeRadios.find((radio) => radio.checked)?.value || document.getElementById('payment_type')?.value || '';
    const list = (value) => String(value || '').split(/\r?\n|,|;/).map((item) => item.trim()).filter(Boolean);
    const textOrDash = (value) => String(value || '').trim() || '-';
    const parseAddOns = (radio = pkg()) => {
        if (!radio) return [];
        const includedIds = new Set(structuredInclusionsFor(radio).map(item => item.add_on_id).filter(Boolean).map(String));
        return globalAddOns.filter(item => item.category !== chapelCategory && !includedIds.has(String(item.id)));
    };
    const parseJsonDataset = (radio, key, fallback = []) => {
        try {
            const parsed = JSON.parse(radio?.dataset?.[key] || JSON.stringify(fallback));
            return parsed ?? fallback;
        } catch (error) {
            return fallback;
        }
    };
    const structuredInclusionsFor = (radio = pkg()) => {
        const parsed = parseJsonDataset(radio, 'structuredInclusions', []);
        return Array.isArray(parsed) ? parsed : [];
    };
    const structuredFreebiesFor = (radio = pkg()) => {
        const parsed = parseJsonDataset(radio, 'structuredFreebies', []);
        return Array.isArray(parsed) ? parsed : [];
    };
    const includedCasketFor = (radio = pkg()) => {
        const parsed = parseJsonDataset(radio, 'includedCasket', {});
        return parsed && typeof parsed === 'object' ? parsed : {};
    };
    const packageDetailRow = (title, details = '', icon = 'bi-check2', viewing = false) => `
        <div class="package-service-summary flex items-start gap-2">
            <i class="bi ${icon} mt-0.5 shrink-0" aria-hidden="true"></i>
            <div class="min-w-0">
                <div class="text-sm font-semibold">${escapeHtml(title)}</div>
                ${details || viewing ? `<div class="inline-package-detail text-xs leading-relaxed" ${viewing ? 'data-viewing-context' : ''}>${escapeHtml(details)}</div>` : ''}
            </div>
        </div>
    `;
    const includedAssetStatus = (asset) => !asset ? 'Not configured' : asset.active === false ? 'Archived' : asset.available === false ? 'Unavailable' : 'Included';
    const detailRowsForPackage = (radio = pkg()) => {
        const serviceOrder = ['body_retrieval', 'embalming', 'casket', 'home_viewing', 'hearse'];
        const includedRows = structuredInclusionsFor(radio).filter(item => !item.is_legacy_description);
        const otherRows = includedRows.filter(item => item.service_type === 'custom');
        const structuredRows = includedRows.filter(item => item.service_type !== 'custom').sort((left, right) => {
            const leftIndex = serviceOrder.indexOf(left.service_type);
            const rightIndex = serviceOrder.indexOf(right.service_type);
            return (leftIndex < 0 ? serviceOrder.length : leftIndex) - (rightIndex < 0 ? serviceOrder.length : rightIndex);
        });
        const structuredFreebieRows = structuredFreebiesFor(radio);
        const legacyInclusions = list(radio?.dataset.inclusions);
        const legacyFreebies = list(radio?.dataset.freebies);

        const structuredServiceLabels = new Set([
            ...includedRows.map(item => item.name?.trim().toLowerCase()),
            ...structuredRows.map(item => serviceTypeLabels[item.service_type]?.toLowerCase()),
        ].filter(Boolean));
        const structuredFreebieLabels = new Set(structuredFreebieRows.map((item) => String(item.name || '').trim().toLowerCase()).filter(Boolean));
        const legacyServiceRows = legacyInclusions
            .filter((item) => !structuredServiceLabels.has(item.toLowerCase()))
            .map((item) => packageDetailRow(item, '', 'bi-check2-circle'));
        const legacyFreebieRows = legacyFreebies
            .filter((item) => !structuredFreebieLabels.has(item.toLowerCase()))
            .map((item) => packageDetailRow(item, '', 'bi-gift'));

        const serviceRows = structuredRows.map(item => {
            const asset = ['casket', 'hearse'].includes(item.service_type);
            const coverage = [
                item.included_kilometers != null ? `${fmt(item.included_kilometers)} km included` : '',
                num(item.price_per_excess_kilometer) > 0 ? `₱${fmt(item.price_per_excess_kilometer)} per excess km` : '',
                item.included_days != null ? `${item.included_days} day(s) included` : '',
                num(item.price_per_extended_day) > 0 ? `₱${fmt(item.price_per_extended_day)} per extra day` : '',
            ].filter(Boolean).join(' · ');
            if (asset) return `<div class="package-asset-slot" data-package-asset-slot="${item.service_type}"><p class="inline-package-detail">${escapeHtml(coverage)}</p></div>`;
            return packageDetailRow(serviceTypeLabels[item.service_type] || item.name || 'Included service', coverage, 'bi-check2', item.service_type === 'home_viewing');
        }).concat(legacyServiceRows);

        const freebieRows = structuredFreebieRows
            .map((item) => packageDetailRow(item.name || 'Freebie', `${item.quantity || 1} ${item.unit || 'item'}`, 'bi-gift'))
            .concat(legacyFreebieRows);

        const legacyCasket = includedCasketFor(radio);
        const hasStructuredCasket = structuredRows.some((item) => item.service_type === 'casket');
        if (!hasStructuredCasket && legacyCasket.name) {
            serviceRows.push('<div class="package-asset-slot" data-package-asset-slot="casket"></div>');
        }

        return {
            services: serviceRows.join('') || '<div class="text-sm font-semibold text-slate-500">No included services configured.</div>',
            otherInclusions: otherRows.filter(item => !item.add_on_id && item.name?.trim())
                .map(item => packageDetailRow(item.name.trim(), item.add_on_id ? `${item.included_quantity} ${item.add_on_unit} included` : '')).join(''),
            hasCatalogAddOns: otherRows.some(item => item.add_on_id),
            freebies: freebieRows.join('') || '<div class="text-sm font-semibold text-slate-500">No freebies configured.</div>',
            hasCasket: hasStructuredCasket || Boolean(legacyCasket.name),
        };
    };
    const intakeDialogStates = new Map();
    const setIntakeDialog = (modal, open, firstFocus = null) => {
        if (!modal) return;
        const state = intakeDialogStates.get(modal);
        if (open && !state) {
            const previousFocus = document.activeElement;
            const scrollPositions = [];
            for (let parent = modal.parentElement; parent; parent = parent.parentElement) {
                scrollPositions.push({ element: parent, top: parent.scrollTop, left: parent.scrollLeft });
            }
            const restoreScroll = () => scrollPositions.forEach(({ element, top, left }) => {
                element.scrollTop = top;
                element.scrollLeft = left;
            });
            intakeDialogStates.set(modal, { previousFocus, restoreScroll });
            document.body.dataset.intakeModalOpen = 'true';
            modal.classList.remove('hidden');
            modal.showModal();
            modal.tabIndex = -1;
            (firstFocus || modal.querySelector('button, input, select, textarea, a[href]') || modal).focus({ preventScroll: true });
            restoreScroll();
            requestAnimationFrame(restoreScroll);
        } else if (!open && state) {
            if (state.closing) return;
            state.closing = true;
            const finish = () => {
                if (intakeDialogStates.get(modal) !== state) return;
                clearTimeout(state.closeTimer);
                intakeDialogStates.delete(modal);
                modal.close();
                modal.classList.add('hidden');
                state.closeAnimation?.cancel();
                if (!intakeDialogStates.size) delete document.body.dataset.intakeModalOpen;
                if (state.previousFocus?.isConnected && state.previousFocus.getClientRects().length) state.previousFocus.focus({ preventScroll: true });
                state.restoreScroll();
            };
            finish();
        }
    };
    document.addEventListener('keydown', event => {
        if (event.key !== 'Tab' || !intakeDialogStates.size) return;
        const modal = [...intakeDialogStates.keys()].at(-1);
        const controls = [...modal.querySelectorAll('button, a[href], input, select, textarea, [tabindex]')]
            .filter(element => !element.disabled && !element.inert && element.tabIndex >= 0 && element.getClientRects().length);
        const first = controls[0], last = controls.at(-1);
        if (!first) { event.preventDefault(); modal.focus(); return; }
        if (event.shiftKey && (document.activeElement === first || !modal.contains(document.activeElement))) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || !modal.contains(document.activeElement))) {
            event.preventDefault(); first.focus();
        }
    }, true);
    const checkedAddOns = () => [...document.querySelectorAll('.optional-add-on-checkbox:checked')]
        .map((checkbox) => {
            const rawQuantity = checkbox.closest('.optional-add-on-row')?.querySelector('[data-draft-add-on-quantity]')?.value;
            const quantity = Number(rawQuantity ?? 1);
            return ({
            id: checkbox.value,
            name: checkbox.dataset.name || '',
            description: checkbox.dataset.description || '',
            price: num(checkbox.dataset.price),
            unit: checkbox.dataset.unit || '',
            quantity: Number.isInteger(quantity) ? quantity : 0,
        });
    });
    const appliedAddOns = () => parseAddOns().filter((addOn) => oldSelectedAddOns.has(String(addOn.id))).map((addOn) => {
        const rawQuantity = addOnQuantities.has(String(addOn.id)) ? addOnQuantities.get(String(addOn.id)) : 1;
        const parsedQuantity = Number(rawQuantity);
        const quantity = Number.isInteger(parsedQuantity) && parsedQuantity >= 1 ? parsedQuantity : 0;
        return { ...addOn, quantity, lineTotal: Math.round(num(addOn.price) * quantity * 100) / 100 };
    });
    const addOnsTotal = () => appliedAddOns().reduce((sum, addOn) => sum + addOn.lineTotal, 0);
    [document.getElementById('intakeCancelModal')].filter(Boolean).forEach(modal => {
        modal.addEventListener('cancel', event => event.preventDefault());
        if (modal.id === 'intakeCancelModal') {
            modal.addEventListener('click', event => {
                if (event.target !== modal) return;
                document.getElementById('intakeCancelModalKeep')?.click();
            });
        }
    });
    const syncServiceBackdrop = () => {
        serviceModalBackdrop?.classList.add('hidden');
        // Scroll locking is owned by the shared dialog lifecycle.
    };
    const setAddOnsPanelOpen = (open) => {
        if (!optionalAddOnsPanel) return;
        if (open) {
            if (optionalAddOnsPanel.matches(':popover-open')) return;
            optionalAddOnsPanel.classList.remove('hidden');
            optionalAddOnsPanel.showPopover();
            positionIntakePicker(optionalAddOnsPanel, chooseAddOnsBtn, 640);
            optionalAddOnsSearch?.focus({ preventScroll: true });
        } else if (optionalAddOnsPanel.matches(':popover-open')) {
            optionalAddOnsPanel.hidePopover();
        }
        chooseAddOnsBtn?.setAttribute('aria-expanded', open ? 'true' : 'false');
        syncServiceBackdrop();
    };
    const syncDraftAddOnsTotal = () => {
        const selected = checkedAddOns();
        if (draftAddOnsTotal) draftAddOnsTotal.textContent = fmt(selected.reduce((sum, addOn) => sum + num(addOn.price) * addOn.quantity, 0));
        const count = document.getElementById('draft_add_ons_count');
        if (count) count.textContent = selected.length ? `${selected.length} add-on${selected.length === 1 ? '' : 's'} selected` : 'No add-ons selected';
        const headerCount = document.getElementById('draft_add_ons_header_count');
        if (headerCount) headerCount.textContent = `${selected.length} selected`;
    };
    const restoreAppliedAddOns = () => {
        draftSelectedAddOns = new Set(oldSelectedAddOns);
        draftAddOnQuantities = new Map(addOnQuantities);
        document.querySelectorAll('.optional-add-on-checkbox').forEach((checkbox) => {
            const id = String(checkbox.value);
            checkbox.checked = draftSelectedAddOns.has(id);
            const quantity = checkbox.closest('.optional-add-on-row')?.querySelector('[data-draft-add-on-quantity]');
            if (quantity) {
                quantity.value = String(draftAddOnQuantities.has(id) && draftAddOnQuantities.get(id) > 0 ? draftAddOnQuantities.get(id) : 1);
                quantity.disabled = !checkbox.checked;
                const total = checkbox.closest('.optional-add-on-row')?.querySelector('[data-draft-add-on-line-total]');
                if (total) total.textContent = fmt(num(checkbox.dataset.price) * Number(quantity.value));
            }
        });
        syncDraftAddOnsTotal();
    };
    const applyDraftAddOns = () => {
        const invalidQuantity = [...document.querySelectorAll('.optional-add-on-checkbox:checked')]
            .map((checkbox) => checkbox.closest('.optional-add-on-row')?.querySelector('[data-draft-add-on-quantity]'))
            .find((field) => field && !field.checkValidity());
        if (invalidQuantity) {
            invalidQuantity.reportValidity();
            invalidQuantity.focus();
            return;
        }
        oldSelectedAddOns.clear();
        addOnQuantities.clear();
        checkedAddOns().forEach((addOn) => {
            const id = String(addOn.id);
            oldSelectedAddOns.add(id);
            addOnQuantities.set(id, addOn.quantity);
        });
        draftSelectedAddOns = new Set(oldSelectedAddOns);
        draftAddOnQuantities = new Map(addOnQuantities);
        setAddOnsPanelOpen(false);
        renderPkg(false);
        render();
    };
    const selectedAppliedAddOns = () => {
        return appliedAddOns();
    };
    let additionalItemIndex = 0;
    const oldAdditionalItems = @json($intakeValue('additional_service_items', []));
    let restoredAutosaveAdditionalRows = false;
    const additionalServiceRows = () => [...document.querySelectorAll('[data-additional-service-row]')]
        .map((row) => ({
            description: row.querySelector('[data-additional-description]')?.value?.trim() || '',
            amount: num(row.querySelector('[data-additional-amount]')?.value),
        }))
        .filter((item) => item.description || item.amount > 0);
    const syncAdditionalServiceHiddenFields = () => {
        const rows = additionalServiceRows();
        if (addAmt) addAmt.value = rows.reduce((sum, item) => sum + item.amount, 0).toFixed(2);
        if (additionalServices) additionalServices.value = rows.map((item) => item.description).filter(Boolean).join('; ');
    };
    const addAdditionalServiceRow = (item = {}) => {
        if (!additionalItemsList) return;
        const index = additionalItemIndex++;
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 sm:grid-cols-[1fr_150px_38px] gap-2 rounded-lg border border-slate-200 bg-white p-3';
        row.dataset.additionalServiceRow = '1';
        row.innerHTML = `
            <input type="text" name="additional_service_items[${index}][description]" value="${escapeHtml(item.description || '')}" class="form-input" placeholder="Service description" aria-label="Manual charge description" data-label="additional service description" data-additional-description>
            <div class="flex items-center rounded-lg border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-slate-900">
                <span class="pl-3 pr-2 text-slate-500 font-bold">&#8369;</span>
                <input type="number" step="0.01" min="0" name="additional_service_items[${index}][amount]" value="${escapeHtml(item.amount || '')}" class="w-full border-0 focus:outline-none focus:ring-0 font-bold p-3" placeholder="0.00" aria-label="Manual charge amount in PHP" data-label="additional service amount" data-additional-amount>
            </div>
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600" aria-label="Remove additional service line" data-remove-additional-service>
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        additionalItemsList.appendChild(row);
        row.querySelectorAll('input').forEach((input) => input.addEventListener('input', () => {
            syncAdditionalServiceHiddenFields();
            render();
        }));
        row.querySelector('[data-remove-additional-service]')?.addEventListener('click', () => {
            const nextRow = row.nextElementSibling || row.previousElementSibling;
            row.remove();
            syncAdditionalServiceHiddenFields();
            render();
            (nextRow?.querySelector('[data-remove-additional-service]') || addAdditionalItem)?.focus({ preventScroll: true });
        });
        syncAdditionalServiceHiddenFields();
        return row;
    };
    const selectedReplacementCasket = () => {
        if (casketModeRadios.length && casketModeRadios.find((radio) => radio.checked)?.value !== 'replacement') return null;
        const option = replacementCasket?.selectedOptions?.[0];
        if (!option || !option.value) return null;
        return {
            id: option.value,
            name: option.dataset.name || option.textContent.trim(),
            material: option.dataset.material || '',
            referenceValue: num(option.dataset.price),
        };
    };
    const isCustomSelected = () => pkg()?.value === 'custom';
    const inclusiveWakeDays = () => num(wakeDays?.value);
    const casketUpgradeAmount = () => {
        if (isCustomSelected()) return 0;
        const included = includedCasketFor();
        const selected = selectedReplacementCasket();
        if (!selected) return 0;
        const includedValue = num(included.reference_value);
        if (includedValue <= 0 || selected.referenceValue <= 0) return 0;
        return Math.max(selected.referenceValue - includedValue, 0);
    };
    const chargeForKm = (type, applyField, excessField) => {
        const applied = !!applyField?.checked;
        if (isCustomSelected()) return { amount: 0, excess: 0, included: 0, rate: 0, configured: false, applied: false };
        const row = structuredInclusionsFor().find((item) => item.service_type === type);
        if (!row || row.included_kilometers === null || row.included_kilometers === undefined || num(row.price_per_excess_kilometer) <= 0) {
            return { amount: 0, excess: 0, included: num(row?.included_kilometers), rate: num(row?.price_per_excess_kilometer), configured: false, applied };
        }
        const included = num(row.included_kilometers);
        const rate = num(row.price_per_excess_kilometer);
        const excess = applied ? num(excessField?.value) : 0;
        return { amount: excess * rate, excess, included, rate, configured: true, applied };
    };
    const chargeForDays = (type) => {
        if (isCustomSelected() || (type === 'home_viewing' && isChapelViewing())) return { amount: 0, excess: 0, included: 0, rate: 0, actual: 0, configured: false };
        const row = structuredInclusionsFor().find((item) => item.service_type === type);
        if (!row || row.included_days === null || row.included_days === undefined || num(row.price_per_extended_day) <= 0) {
            return { amount: 0, excess: 0, included: num(row?.included_days), rate: num(row?.price_per_extended_day), actual: inclusiveWakeDays(), configured: false };
        }
        const included = num(row.included_days);
        const rate = num(row.price_per_extended_day);
        const actual = inclusiveWakeDays();
        const excess = Math.max(actual - included, 0);
        return { amount: excess * rate, excess, included, rate, actual, configured: true };
    };
    const hearseUpgradeAmount = () => {
        if (isCustomSelected() || !hearseCatalogSelect?.value) return 0;
        const included = structuredInclusionsFor().find(row => row.service_type === 'hearse')?.hearse;
        const selected = hearseCatalogSelect.selectedOptions[0];
        if (!included || included.reference_value === null || selected?.dataset.referenceValue === '') return 0;
        return Math.max(0, num(selected?.dataset.referenceValue) - num(included.reference_value));
    };
    const includedAddOnAdjustments = document.getElementById('included-addon-adjustments');
    const includedAddOnRows = [...includedAddOnAdjustments.querySelectorAll('[data-included-addon]')];
    const packageAllowanceContext = document.getElementById('package_allowance_context');
    const packageConfigurationContext = document.getElementById('package_configuration_context');
    const inclusionControlsPool = document.getElementById('package-inclusion-controls');
    const casketInclusionCard = inclusionControlsPool.querySelector('[data-service-control="casket"]');
    const hearseAssignmentHome = document.getElementById('hearse-assignment-home');
    const packageCasketOptions = document.getElementById('package-casket-options');
    const packageHearseOptions = document.getElementById('package-hearse-options');
    const packageInclusionOptions = document.getElementById('package-inclusion-options');
    packageCasketOptions.querySelector('[data-option-body]').append(casketInclusionCard);
    packageInclusionOptions.querySelector('[data-option-body]').append(includedAddOnAdjustments);

    const mountPackageInclusionCards = (selected, custom, regularIncludesHearse, customIncludesHearse) => {
        const selectedCard = selected && !custom ? selected.closest('.package-card') : null;
        const casketSlot = selectedCard?.querySelector('[data-package-asset-slot="casket"]');
        const hearseSlot = selectedCard?.querySelector('[data-package-asset-slot="hearse"]');
        const otherSlot = selectedCard?.querySelector('[data-package-other-slot]');
        const casketDestination = casketSlot || packageCasketOptions.querySelector('[data-option-body]');
        if (casketInclusionCard.parentElement !== casketDestination) casketDestination.prepend(casketInclusionCard);
        const otherDestination = otherSlot || packageInclusionOptions.querySelector('[data-option-body]');
        if (includedAddOnAdjustments.parentElement !== otherDestination) otherDestination.append(includedAddOnAdjustments);
        packageCasketOptions.hidden = true; // The fallback owns parked controls only.
        packageHearseOptions.hidden = !regularIncludesHearse || Boolean(hearseSlot);
        if (otherSlot) packageInclusionOptions.hidden = true;
        const hearseDestination = regularIncludesHearse ? (hearseSlot || packageHearseOptions.querySelector('[data-option-body]'))
            : customIncludesHearse ? document.getElementById('custom-hearse-slot') : hearseAssignmentHome;
        if (hearseAssignmentFields.parentElement !== hearseDestination) hearseDestination.prepend(hearseAssignmentFields);
    };

    const syncIncludedAddOnAdjustments = (radio) => {
        packageAllowanceContext.value = radio?.dataset.allowanceContext || '';
        packageConfigurationContext.value = radio?.dataset.configurationContext || '';
        const allowances = new Map(structuredInclusionsFor(radio).filter(item => item.add_on_id).map(item => [String(item.add_on_id), item]));
        packageInclusionOptions.hidden = ![...allowances.values()].some(item => item.add_on_category !== chapelCategory);
        includedAddOnRows.forEach(row => {
            const item = allowances.get(row.dataset.includedAddon);
            const input = row.querySelector('input');
            const available = !!item && item.add_on_category !== chapelCategory;
            row.classList.toggle('hidden', !available);
            input.disabled = !available;
            if (!available) return;
            input.placeholder = item.included_quantity;
            if (!input.dataset.initialized) {
                if (input.value === '') input.value = item.included_quantity;
                input.dataset.initialized = 'true';
            }
            row.querySelector('[data-allowance]').textContent = `${item.included_quantity} ${item.add_on_unit} included · Additional charge ₱0.00 within allowance`;
            row.querySelector('.inline-inclusion-name').textContent = item.name;
        });
    };

    const includedAddOnCharges = () => structuredInclusionsFor().filter(item => item.add_on_id && item.add_on_category !== chapelCategory).map(item => {
        const row = includedAddOnRows.find(row => row.dataset.includedAddon === String(item.add_on_id));
        const input = row.querySelector('input');
        const requested = input.valueAsNumber;
        const displayedQuantity = Number.isInteger(requested) ? requested : item.included_quantity;
        const extra = Number.isInteger(requested) ? Math.max(0, requested - item.included_quantity) : 0;
        const unavailable = extra > 0 && (!item.add_on_available || item.add_on_rate <= 0);
        const amount = unavailable ? 0 : Math.round(extra * item.add_on_rate * 100) / 100;
        input.setCustomValidity(unavailable ? 'Extra units require an available add-on with a configured rate.' : '');
        row.querySelector('[data-inclusion-quantity-step="-1"]').disabled = displayedQuantity <= 1;
        row.querySelector('[data-inclusion-quantity-step="1"]').disabled = displayedQuantity >= 9999
            || (displayedQuantity >= item.included_quantity && (!item.add_on_available || item.add_on_rate <= 0));
        row.querySelector('[data-extra-charge]').textContent = unavailable ? input.validationMessage
            : extra > 0 ? `${extra} extra ${item.add_on_unit} × ₱${fmt(item.add_on_rate)} = ₱${fmt(amount)} additional`
            : 'No additional charge. The package price stays the same.';
        return { label: `Extra ${item.name}`, amount };
    });

    const customCoverageRows = [...document.querySelectorAll('[data-custom-coverage]')];
    const customViewingRow = customCoverageRows.find(row => row.dataset.customCoverage === 'home_viewing');
    const viewingRequested = customViewingRow?.querySelector('[data-coverage-requested]');
    const viewingManual = customViewingRow?.querySelector('[data-viewing-manual]');
    const customCoverageCharges = () => {
        if (viewingRequested && viewingManual.value !== '1') viewingRequested.value = wakeDays?.value || '';
        return customCoverageRows.flatMap(row => {
            if (row.hidden) return [];
            const includedField = row.querySelector('[data-coverage-included]');
            const included = includedField.value || includedField.dataset.defaultCoverage;
            const requested = row.querySelector('[data-coverage-requested]').value;
            const rate = row.dataset.rate;
            const days = row.dataset.unit === 'days';
            const unit = days ? 'days' : 'km';
            const extra = included === '' || requested === '' ? 0 : Math.max(0, Number(requested) - Number(included));
            const amount = Math.round((Number(extra.toFixed(2)) * Number(rate) + Number.EPSILON) * 100) / 100;
            row.querySelector('[data-coverage-label]').textContent = included === '' ? 'Coverage not configured'
                : days ? `${included} day(s) included` : `${Number(included).toFixed(2)} km included`;
            row.querySelector('[data-coverage-charge]').textContent = included === '' && requested !== '' ? 'Coverage not configured'
                : extra <= 0 ? ''
                : rate === '' ? `${Number(extra.toFixed(2))} extra ${unit} · Rate not configured`
                : `${Number(extra.toFixed(2))} extra ${unit} × ₱${fmt(Number(rate))} = ₱${fmt(amount)}`;
            const label = row.closest('.custom-package-service-card').querySelector('label').textContent.trim();
            return amount > 0 ? [{ label: label + (days ? ' extension' : ' excess distance'), amount }] : [];
        });
    };
    let restoredChapelPricing = null;
    const defaultRestoreMessage = 'Autosave restored. Review the selected package, chapel coverage and charges before continuing.';
    let restoredServiceMessage = defaultRestoreMessage;
    let previousIncludedChapel = '';
    let chapelScheduleKey = '';
    let chapelScheduleTimer;
    let chapelScheduleRequest;
    const renderChapelAlternatives = (host, availableIds) => {
        const choices = host.querySelector('[data-chapel-alternatives]');
        const ids = new Set(availableIds.map(String));
        const addChoice = (list, label, type, value) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'service-field-chip';
            button.dataset.alternativeType = type;
            button.dataset.alternativeValue = value;
            button.textContent = label;
            list.append(button);
        };
        const packages = choices.querySelector('[data-alternative-packages]');
        const packageOptions = packages.querySelector('[data-alternative-package-options]');
        packageOptions.replaceChildren();
        if (chapelCoverage() === 'included') {
            for (const option of regularPackageChoice.options) {
                const radio = pkgRadios.find(item => item.value === option.value);
                if (radio && !option.disabled && ids.has(String(chapelAllowanceFor(radio)?.add_on_id))) {
                    addChoice(packageOptions, option.textContent, 'package', option.value);
                }
            }
        }
        packages.hidden = !packageOptions.childElementCount;
        const chapels = choices.querySelector('[data-alternative-chapels]');
        const chapelOptions = chapels.querySelector('[data-alternative-chapel-options]');
        chapelOptions.replaceChildren();
        for (const option of chapelRate.options) {
            if (!option.disabled && ids.has(option.value)) addChoice(chapelOptions, option.textContent, 'chapel', option.value);
        }
        chapels.hidden = !chapelOptions.childElementCount;
        choices.hidden = packages.hidden && chapels.hidden;
        packages.open = false;
        chapels.open = false;
    };
    const checkChapelSchedule = (rate, staleIncluded = false) => {
        const box = document.getElementById('chapel-schedule-context');
        if (!box) return;
        const setup = document.getElementById('chapel_setup_alternatives');
        const dateKey = (field, picker) => {
            const date = getDateValue(field, picker);
            return date ? `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}` : '';
        };
        const fromDate = dateKey(wakeStart, wakeStartPicker), toDate = dateKey(wakeEnd, wakeEndPicker);
        const from = `${fromDate} ${wakeStartTime.value}`;
        const to = `${toDate} ${wakeEndTime.value}`;
        const ready = isChapelViewing() && (rate || (staleIncluded && branch.value))
            && fromDate && toDate && wakeStartTime.value && wakeEndTime.value && from < to;
        const key = ready ? `${chapelCoverage()}|${rate ? 'chapel:' + rate.id : 'branch:' + branch.value}|${from}|${to}` : '';
        if (key === chapelScheduleKey) return;
        chapelScheduleKey = key;
        clearTimeout(chapelScheduleTimer);
        chapelScheduleRequest?.abort();
        box.hidden = !ready || !rate;
        box.dataset.state = ready && rate ? 'checking' : 'idle';
        box.querySelector('[data-chapel-overlaps]').hidden = true;
        box.querySelector('[data-chapel-alternatives]').hidden = true;
        setup.querySelector('[data-chapel-alternatives]').hidden = true;
        if (!ready) return;
        const status = box.querySelector('[data-chapel-check-status]');
        const retry = box.querySelector('[data-chapel-check-retry]');
        status.textContent = 'Checking chapel schedule…';
        retry.hidden = true;
        chapelScheduleTimer = setTimeout(async () => {
            const request = new AbortController();
            chapelScheduleRequest = request;
            try {
                const url = new URL(box.dataset.url, location.origin);
                url.search = new URLSearchParams({ ...(rate ? { chapel_id: rate.id } : { branch_id: branch.value }), start: from, end: to });
                const response = await fetch(url, { signal: request.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Schedule unavailable');
                const data = await response.json();
                if (request.signal.aborted || key !== chapelScheduleKey) return;
                if (!rate) {
                    renderChapelAlternatives(setup, data.available_chapel_ids);
                    return;
                }
                const hasConflict = data.events.length > 0;
                box.dataset.state = hasConflict ? 'conflict' : 'available';
                const events = box.querySelector('[data-chapel-events]');
                events.replaceChildren();
                data.events.forEach(event => {
                    const row = document.createElement('li');
                    const reference = document.createElement('strong');
                    reference.textContent = `Case ${event.case_code}`;
                    const wakeEnd = document.createElement('span');
                    wakeEnd.textContent = `Scheduled Wake End · ${formatScheduleDateTime(...event.end.split(' '))}`;
                    row.append(reference, wakeEnd);
                    events.append(row);
                });
                status.textContent = hasConflict ? 'Chapel schedule conflict' : 'No overlap in the recorded chapel schedule.';
                box.querySelector('[data-chapel-overlaps]').hidden = !hasConflict;
                if (hasConflict) renderChapelAlternatives(box, data.available_chapel_ids);
                retry.hidden = !hasConflict;
            } catch (error) {
                if (error.name === 'AbortError' || key !== chapelScheduleKey || !rate) return;
                box.dataset.state = 'error';
                status.textContent = 'Chapel availability could not be checked. Recheck availability before continuing.';
                retry.hidden = false;
            }
        }, 300);
        retry.onclick = () => { chapelScheduleKey = ''; checkChapelSchedule(rate); };
    };
    const chapelAllowanceFor = radio => {
        const rows = structuredInclusionsFor(radio).filter(item => item.add_on_category === chapelCategory);
        return rows.find(item => String(item.add_on_branch_id) === String(branch.value))
            || rows.find(item => !item.add_on_branch_id);
    };
    const configuredChapelFor = id => {
        if (![...chapelRate.options].some(option => option.value && option.value === String(id)
            && String(option.dataset.branch) === String(branch.value))) return null;
        return globalAddOns.find(item => String(item.id) === String(id)) || null;
    };
    const chapelPackageIsEligible = (radio, coverage = chapelCoverage()) => {
        if (!isChapelViewing()) return true;
        const allowance = chapelAllowanceFor(radio);
        if (coverage === 'included') return Boolean(allowance && configuredChapelFor(allowance.add_on_id));
        return coverage === 'separate' && !allowance;
    };
    const restoreLegacyChapelCoverage = fields => {
        if (!isChapelViewing() || fields.chapel_coverage_flow || chapelCoverage()) return;
        const selected = pkg();
        if (!selected && !chapelRate.value && !restoredChapelPricing) return;
        const coverage = chapelAllowanceFor(selected) || restoredChapelPricing?.days > 0 ? 'included' : 'separate';
        chapelCoverageChoices.find(choice => choice.value === coverage).checked = true;
    };
    const syncChapelPackageChoices = () => {
        const chapel = isChapelViewing();
        const coverage = chapelCoverage();
        const included = chapel && coverage === 'included';
        const selected = pkg();
        const awaitingChapel = chapel && (!coverage || (coverage === 'separate' && !configuredChapelFor(chapelRate.value)));
        packageModeChooser.toggleAttribute('data-coverage-hidden', awaitingChapel || included);
        regularPackageArea.toggleAttribute('data-coverage-hidden', awaitingChapel);
        customPackageArea?.toggleAttribute('data-coverage-hidden', chapel && (awaitingChapel || included));
        if (included) regularPackageArea.classList.remove('hidden');
        regularPackageArea.querySelector('[data-change-package-mode]').hidden = included;
        const packageHeading = document.getElementById('regular_package_heading');
        const packageHelp = document.getElementById('regular_package_help');
        const heading = included ? 'Packages with Included Chapel' : 'Regular Packages';
        const help = included ? 'Each option shows the chapel and included days for this branch.'
            : 'Predefined, eligible packages configured by the System Administrator.';
        if (packageHeading.textContent !== heading) packageHeading.textContent = heading;
        if (packageHelp.textContent !== help) packageHelp.textContent = help;

        let hasOptions = false;
        [...regularPackageChoice.options].forEach(option => {
            if (!option.value) return;
            const radio = pkgRadios.find(item => item.value === option.value);
            const eligible = chapelPackageIsEligible(radio);
            const allowance = included && eligible ? chapelAllowanceFor(radio) : null;
            const rate = allowance ? configuredChapelFor(allowance.add_on_id) : null;
            option.disabled = !eligible;
            option.hidden = !eligible;
            const label = rate ? [radio.dataset.name, rate.name, allowance.included_quantity + ' days included'].join(' · ') : radio.dataset.name;
            if (option.textContent !== label) option.textContent = label;
            hasOptions ||= eligible;
        });
        regularPackageChoice.disabled = !hasOptions;
        const message = !chapel || !coverage ? ''
            : selected && !chapelPackageIsEligible(selected)
                ? selected.dataset.name + ' is unavailable for this chapel coverage at this branch. Choose an eligible package or change coverage.'
                : !hasOptions && included ? 'No package with usable chapel coverage is available at this branch.'
                : !hasOptions ? 'No regular package without chapel coverage is available at this branch.'
                : coverage === 'separate' ? 'Showing packages with no chapel coverage at this branch.' : '';
        chapelPackageFeedback.hidden = !message || awaitingChapel;
        if (chapelPackageFeedback.textContent !== message) chapelPackageFeedback.textContent = message;
        const anchor = selected && selected === customPkgRadio && coverage === 'separate'
            ? customPackageArea.querySelector('.package-selection-area__heading')
            : regularPackageChoice.closest('.mb-4');
        if (chapelPreviewPanel.previousElementSibling !== anchor) anchor.after(chapelPreviewPanel);
    };
    const chapelViewingCharge = () => {
        const visible = isChapelViewing();
        const coverage = chapelCoverage();
        const selected = pkg();
        const eligiblePackage = selected && chapelPackageIsEligible(selected);
        const included = visible && coverage === 'included' ? chapelAllowanceFor(selected) : null;
        const includedId = included ? String(included.add_on_id) : '';
        const separate = visible && coverage === 'separate';
        chapelChargePanel.hidden = !visible;
        chapelCoverageChoices.forEach(choice => { choice.disabled = !visible; });
        if (previousIncludedChapel && !includedId) chapelRate.value = '';
        previousIncludedChapel = includedId;
        [...chapelRate.options].forEach(option => {
            const wrongBranch = Boolean(option.value && String(option.dataset.branch) !== String(branch.value));
            option.hidden = wrongBranch;
            option.disabled = wrongBranch;
        });
        const hasRates = [...chapelRate.options].some(option => option.value && !option.disabled);
        if (includedId) chapelRate.value = includedId;
        const rate = included ? configuredChapelFor(includedId) : separate ? configuredChapelFor(chapelRate.value) : null;
        const configured = Boolean(visible && eligiblePackage && rate);
        const draftPricing = document.getElementById('chapel_draft_pricing');
        const includedDays = Number(included?.included_quantity || 0);
        const current = configured ? { id: String(rate.id), rate: Number(rate.price), days: includedDays } : null;
        draftPricing.value = current ? JSON.stringify(current) : '';
        restoredServiceMessage = defaultRestoreMessage;
        if (restoredChapelPricing && visible) {
            const prior = restoredChapelPricing;
            const describe = item => '₱' + fmt(item.rate) + '/day · ' + item.days + ' included day(s)';
            if (!current) restoredServiceMessage = 'Saved chapel pricing: ' + describe(prior) + '. Review chapel coverage before continuing.';
            else if (JSON.stringify(prior) !== JSON.stringify(current)) {
                restoredServiceMessage = 'Chapel pricing changed. Saved: ' + describe(prior) + '. Current: ' + describe(current) + '. Review the updated charge before continuing.';
            }
        }

        viewingLocation.readOnly = visible;
        chapelAddress.disabled = !visible;
        if (visible) {
            viewingLocation.value = rate?.name || '';
            chapelAddress.value = rate?.chapel_address || '';
        }
        chapelChargeControls.hidden = !separate;
        chapelRate.disabled = !separate;
        chapelRate.required = separate;
        document.getElementById('chapel_package_hint').hidden = !(separate && rate && !eligiblePackage
            && (!regularPackageChoice.disabled || customPkgRadio));
        const staleIncluded = visible && coverage === 'included' && (selected ? !eligiblePackage : regularPackageChoice.disabled);
        const noRates = separate && !hasRates;
        const notice = document.getElementById('chapel_charge_notice');
        const noticeAnchor = staleIncluded ? document.getElementById('chapel_setup_alternatives') : chapelChargeControls;
        if (notice.nextElementSibling !== noticeAnchor) noticeAnchor.before(notice);
        notice.hidden = !(staleIncluded || noRates);
        document.getElementById('chapel_notice_title').textContent = staleIncluded
            ? 'No usable included chapel for this selection.'
            : 'No chapel with a daily rate is available at this branch.';
        document.getElementById('chapel_notice_detail').textContent = staleIncluded
            ? 'Choose an eligible package, adjust the wake schedule, or save as draft.'
            : 'Choose a package with a chapel or save as draft.';
        chapelSaveDraft.hidden = notice.hidden;
        chapelPreviewPanel.hidden = !configured;
        const advisoryContainer = separate ? chapelChargeControls : chapelPreviewPanel;
        if (chapelScheduleBox.parentElement !== advisoryContainer) advisoryContainer.append(chapelScheduleBox);
        checkChapelSchedule(rate, staleIncluded);

        const days = inclusiveWakeDays();
        const billableDays = Math.max(0, days - includedDays);
        const amount = configured ? Math.round(billableDays * rate.price * 100) / 100 : 0;
        if (configured) {
            chapelRateReadout.textContent = rate.name + ' · ₱' + fmt(rate.price) + ' / day';
            chapelAddressReadout.textContent = rate.chapel_address;
            const coverageText = included ? 'Included in package · ' + includedDays + ' covered day(s)' : 'Separate chapel rate';
            const chargePreview = !days
                ? coverageText + ' · Set the wake schedule to calculate the charge.'
                : [coverageText, wakeDurationLabel(days), billableDays + ' billable day(s)', '₱' + fmt(amount) + ' chapel charge'].join(' · ');
            if (chapelChargePreview.textContent !== chargePreview) chapelChargePreview.textContent = chargePreview;
        }
        return { label: included ? 'Chapel Viewing Extension' : 'Chapel Viewing', amount, configured, blocked: visible && !configured };
    };
    const serviceCharges = () => {
        const retrieval = chargeForKm('body_retrieval', applyRetrievalExcess, retrievalExcessInput);
        const hearse = chargeForKm('hearse', applyHearseExcess, hearseExcessInput);
        const embalming = chargeForDays('embalming');
        const viewing = chargeForDays('home_viewing');
        const casket = casketUpgradeAmount();
        const hearseUpgrade = hearseUpgradeAmount();
        const includedAddOns = includedAddOnCharges();
        const customCharges = isCustomSelected() ? customCoverageCharges() : [];
        const chapel = chapelViewingCharge();

        return {
            retrieval,
            hearse,
            embalming,
            viewing,
            casket,
            hearseUpgrade,
            includedAddOns,
            customCharges,
            chapel,
            total: retrieval.amount + hearse.amount + embalming.amount + viewing.amount + casket + hearseUpgrade
                + chapel.amount + [...includedAddOns, ...customCharges].reduce((sum, item) => sum + item.amount, 0),
        };
    };

    const formatDateOnly = (value) => {
        if (!value) return '-';
        const parsed = new Date(`${value}T00:00:00`);
        return Number.isNaN(parsed.getTime()) ? '-' : parsed.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatDateTime = (value) => {
        if (!value) return '-';
        const parsed = new Date(value);
        return Number.isNaN(parsed.getTime()) ? '-' : parsed.toLocaleString(undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    };

    const formatScheduleDateTime = (dateValue, timeValue) => {
        if (!dateValue && !timeValue) return '-';
        const dateLabel = formatDateOnly(dateValue);
        if (!timeValue) return `${dateLabel} Time not set`;
        const parsed = new Date(`${dateValue}T${timeValue}`);
        if (Number.isNaN(parsed.getTime())) return dateLabel;

        return parsed.toLocaleString(undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    };

    const detailRow = (label, value) => `
        <div class="flex items-start justify-between gap-4 py-1.5">
            <dt class="text-slate-500">${escapeHtml(label)}</dt>
            <dd class="text-right font-bold text-slate-800 break-words max-w-[50%]">${escapeHtml(value)}</dd>
        </div>
    `;

    const setHidden = (wrap, input, hidden) => {
        if (!wrap) return;
        wrap.classList.toggle('hidden', hidden);
        if (input) {
            input.disabled = hidden;
            if (hidden) input.setCustomValidity('');
        }
    };

    const clearFieldMessage = (field) => {
        if (field?.setCustomValidity) field.setCustomValidity('');
        field?.classList?.remove('field-error');
        field?.removeAttribute?.('aria-invalid');
        field?.closest?.('.form-select-wrap')?.classList.remove('is-error');

        const errorEl = fieldErrorElement(field, false);
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }
    };

    const labelFor = (field) => field?.dataset?.label || (field?.id && f.querySelector(`label[for="${field.id}"]`)?.textContent?.replace(/\*/g, '').trim()) || field?.getAttribute('placeholder') || 'this field';

    const fieldByName = (name) => f.querySelector(`[name="${name}"]`);

    const scheduleTimeDisplay = field => field?.classList.contains('schedule-time-value')
        ? f.querySelector(`[data-time-display-for="${field.id}"]`)
        : null;

    const fieldErrorElement = (field, create = true) => {
        if (!field) return null;
        const fieldName = field.id === 'cash_reference_number' ? field.id : (field.getAttribute('name') || field.id);
        if (!fieldName) return null;

        const safeName = fieldName.replace(/[^A-Za-z0-9_-]/g, '_');
        const dateErrorIds = { born: 'born_error', died: 'died_error', wake_start_date: 'wake_start_error', wake_end_date: 'wake_end_error', funeral_service_at: 'funeral_service_at_error', interment_at: 'interment_at_error', wake_start_time: 'wake_start_error', wake_end_time: 'wake_end_error', funeral_service_time: 'funeral_service_at_error', interment_time: 'interment_at_error', confirm_review: 'confirm_review_error' };
        const existing = document.getElementById(dateErrorIds[field.id]);
        if (existing) return existing;
        let errorEl = document.getElementById(`${safeName}_inline_error`);
        if (!errorEl && create) {
            errorEl = document.createElement('p');
            errorEl.id = `${safeName}_inline_error`;
            errorEl.className = 'intake-field-error hidden';

            const selectWrap = field.matches('select') ? field.closest('.form-select-wrap, .payment-select-wrap') : null;
            const amountWrap = field.id === 'amount_paid' ? field.parentElement : null;
            const locationWrap = field.closest('.intake-location-input');
            (selectWrap || amountWrap || locationWrap || field).insertAdjacentElement('afterend', errorEl);
        }

        return errorEl;
    };

    let collectingValidation = false;
    let restoringPickerState = false;
    const showFieldError = (field, message) => {
        if (!field) return false;
        const inclusionDialog = field.closest('.custom-inclusion-dialog');
        if (inclusionDialog && !collectingValidation) setIntakeDialog(inclusionDialog, true, field);
        const errorEl = fieldErrorElement(field);
        field.setCustomValidity(message);
        setFieldError(field, errorEl, message, scheduleTimeDisplay(field) || field._flatpickr?.altInput);
        if (!collectingValidation) field.focus();
        return false;
    };

    const normalizeText = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();
    const normalizeComparable = (value) => normalizeText(value).toLocaleLowerCase();
    const hasLetter = (value) => /\p{L}/u.test(String(value ?? ''));
    const validPlacePattern = /^[\p{L}\p{M}\p{N} .,\u2019'#()/&-]+$/u;
    const placeMessage = (field, value) => {
        const label = field.name === 'wake_location' ? (isChapelViewing() ? 'Chapel name / location' : 'Wake address')
            : field.name === 'chapel_address' ? 'Chapel address'
            : field.name === 'place_of_cemetery' ? 'Place of interment / cemetery' : 'Complete address';
        if (!value) return `${label} is required.`;
        if (value.length > 255) return `${label} must not exceed 255 characters.`;
        if (!hasLetter(value)) return `${label} must include a place name.`;
        if (!validPlacePattern.test(value)) return `${label} contains unsupported symbols. Use letters, numbers, spaces, and address punctuation (., apostrophe, #, (), /, &, -).`;
        return '';
    };
    const isValidName = (value) => /^[\p{L}\p{M}\s.'-]+$/u.test(normalizeText(value));
    const isValidSuffix = (value) => {
        const normalized = normalizeText(value);
        return normalized === '' || ['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'].includes(normalized);
    };
    const isValidPhilippineMobile = (value) => /^(09\d{9}|\+639\d{9}|639\d{9})$/.test(normalizeText(value));

    const validateDuplicateNameParts = (first, middle, last) => {
        const normalizedFirst = normalizeComparable(first);
        const normalizedMiddle = normalizeComparable(middle);
        const normalizedLast = normalizeComparable(last);

        if (normalizedFirst && normalizedLast && normalizedFirst === normalizedLast) {
            return { field: 'last', message: 'First name and last name cannot be the same.' };
        }

        if (normalizedMiddle && (normalizedMiddle === normalizedFirst || normalizedMiddle === normalizedLast)) {
            return { field: 'middle', message: 'Middle name should not be the same as first name or last name.' };
        }

        return null;
    };

    const fullNameForPrefix = (prefix) => ['first_name', 'middle_name', 'last_name', 'suffix']
        .map((field) => normalizeText(fieldByName(`${prefix}_${field}`)?.value))
        .filter(Boolean)
        .join(' ');

    const validateAddressField = (field) => {
        const value = normalizeText(field?.value);
        if (field) field.value = value;

        const message = placeMessage(field, value);
        if (message) return showFieldError(field, message);

        clearFieldMessage(field);
        return true;
    };

    const validateNameGroup = (prefix, labels) => {
        let valid = true;
        const fields = {
            first: fieldByName(`${prefix}_first_name`),
            middle: fieldByName(`${prefix}_middle_name`),
            last: fieldByName(`${prefix}_last_name`),
            suffix: fieldByName(`${prefix}_suffix`),
        };

        Object.values(fields).forEach(clearFieldMessage);

        const first = normalizeText(fields.first?.value);
        const middle = normalizeText(fields.middle?.value);
        const last = normalizeText(fields.last?.value);
        const suffix = normalizeText(fields.suffix?.value);

        if (fields.first) fields.first.value = first;
        if (fields.middle) fields.middle.value = middle;
        if (fields.last) fields.last.value = last;
        if (fields.suffix) fields.suffix.value = suffix;

        if (!first) valid = showFieldError(fields.first, `${labels.first} is required.`) && valid;
        if (first && !isValidName(first)) valid = showFieldError(fields.first, `${labels.first} must contain letters only.`) && valid;
        if (middle && !isValidName(middle)) valid = showFieldError(fields.middle, `${labels.middle} must contain letters only.`) && valid;
        if (!last) valid = showFieldError(fields.last, `${labels.last} is required.`) && valid;
        if (last && !isValidName(last)) valid = showFieldError(fields.last, `${labels.last} must contain letters only.`) && valid;
        if (!isValidSuffix(suffix)) valid = showFieldError(fields.suffix, 'Please select a valid suffix.') && valid;

        const duplicate = validateDuplicateNameParts(first, middle, last);
        if (duplicate) valid = showFieldError(fields[duplicate.field], duplicate.message) && valid;

        return valid;
    };

    const validateClientInformationStep = () => {
        const usingExistingClient = !!f.elements.existing_client_id?.value;
        let valid = usingExistingClient || validateNameGroup('client', { first: 'First name', middle: 'Middle name', last: 'Last name' });

        const relationship = fieldByName('client_relationship');
        if (!normalizeText(relationship?.value)) {
            valid = showFieldError(relationship, 'Relationship is required.') && valid;
        }
        if (normalizeText(relationship?.value)) clearFieldMessage(relationship);
        if (usingExistingClient) return valid;

        const contact = fieldByName('client_contact_number');
        if (!normalizeText(contact?.value)) {
            valid = showFieldError(contact, 'Contact number is required.') && valid;
        }
        if (normalizeText(contact?.value) && !isValidPhilippineMobile(contact.value)) {
            valid = showFieldError(contact, 'Please enter a valid Philippine mobile number.') && valid;
        }
        if (isValidPhilippineMobile(contact?.value)) clearFieldMessage(contact);

        return validateAddressField(fieldByName('client_address')) && valid;
    };

    const validateDeceasedInformationStep = () => {
        let valid = validateNameGroup('deceased', { first: 'First name', middle: 'Middle name', last: 'Last name' });
        valid = validateAddressField(fieldByName('deceased_address')) && valid;

        if (fullNameForPrefix('deceased') && normalizeComparable(fullNameForPrefix('client')) === normalizeComparable(fullNameForPrefix('deceased'))) {
            valid = showFieldError(fieldByName('deceased_first_name'), 'Client and deceased names cannot be exactly the same. Please verify the entered information.') && valid;
        }

        return valid;
    };

    const branchCode = () => {
        return branchBtns.find((button) => String(button.dataset.branchId) === String(branch.value))?.dataset.branchCode || '-';
    };

    const isBranchSelectedForOtherMode = () => {
        if (!isOtherEntryMode) return true;
        return branchCode() !== '-';
    };

    const shouldLockOtherBranchIntake = () => isOtherEntryMode && !isBranchSelectedForOtherMode();

    const alignToastToMainCenter = (element) => {
        if (!element) return;
        const contentArea = document.querySelector('.page-content') || document.querySelector('.main-area');
        if (!contentArea) return;
        const rect = contentArea.getBoundingClientRect();
        element.style.left = `${rect.left + (rect.width / 2)}px`;
    };

    const alignBranchToastToMainStart = () => {
        if (!branchToast) return;
        const contentArea = document.querySelector('.page-content') || document.querySelector('.main-area');
        if (!contentArea) return;
        const rect = contentArea.getBoundingClientRect();
        branchToast.style.left = `${Math.max(16, rect.left + 28)}px`;
    };

    const realignVisibleToasts = () => {
        if (branchToast && !branchToast.classList.contains('hidden')) alignBranchToastToMainStart();
        if (pkgToast && !pkgToast.classList.contains('hidden')) alignToastToMainCenter(pkgToast);
    };

    const scheduleToastRealign = () => {
        [0, 80, 160, 260, 380, 520].forEach((delay) => {
            setTimeout(realignVisibleToasts, delay);
        });
    };

    window.addEventListener('resize', scheduleToastRealign);
    document.addEventListener('click', (event) => {
        if (event.target.closest('#desktopSidebarToggle') || event.target.closest('#mobileSidebarToggle')) {
            scheduleToastRealign();
        }
    });

    const sidebar = document.getElementById('appSidebar');
    if (sidebar) {
        sidebar.addEventListener('transitionend', scheduleToastRealign);
    }

    const bodyObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'attributes' && (mutation.attributeName === 'data-sidebar-collapsed' || mutation.attributeName === 'data-sidebar-open')) {
                scheduleToastRealign();
                break;
            }
        }
    });
    bodyObserver.observe(document.body, { attributes: true });

    const resizeAnchor = document.querySelector('.page-content') || document.querySelector('.main-area');
    if (resizeAnchor && typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(() => scheduleToastRealign());
        observer.observe(resizeAnchor);
    }

    const showToast = (element, message = null, duration = 2400, type = 'branch') => {
        if (!element) return;

        if (type === 'branch' && branchToastTimer) clearTimeout(branchToastTimer);
        if (type === 'package' && packageToastTimer) clearTimeout(packageToastTimer);
        if (type === 'branch') {
            alignBranchToastToMainStart();
        } else {
            alignToastToMainCenter(element);
            scheduleToastRealign();
        }

        if (message) {
            const textNode = element.querySelector('.package-toast-text, .intake-notice-text');
            if (textNode) textNode.textContent = message;
            else element.textContent = message;
            if (type === 'branch') {
                const icon = element.querySelector('.intake-notice-icon');
                if (icon) icon.className = `bi ${/^(Resuming |.*draft restored)/i.test(message) ? 'bi-file-earmark-check' : 'bi-building'} intake-notice-icon`;
            }
        }

        element.classList.remove('hidden', 'opacity-0');
        element.classList.add('opacity-100', 'toast-visible');

        const timer = setTimeout(() => {
            element.classList.remove('opacity-100', 'toast-visible');
            element.classList.add('opacity-0');
            setTimeout(() => element.classList.add('hidden'), 260);
        }, duration);

        if (type === 'branch') branchToastTimer = timer;
        if (type === 'package') packageToastTimer = timer;
    };

    const showBranchToast = (message, duration = 2600) => showToast(branchToast, message, duration, 'branch');
    const showPackageToast = (message) => showToast(pkgToast, message, 2300, 'package');

    const branchPromptMessage = () => {
        const code = branchCode() !== '-' ? branchCode() : mainBranchCodeDefault;

        if (!isOtherEntryMode) {
            return `You're now recording for the Main Branch (${code}). You can start entering the case details.`;
        }

        if (!isBranchSelectedForOtherMode()) {
            return "You're recording for another branch. Please choose which branch to continue.";
        }

        return `Great, you're now recording for Branch ${code}. You can continue with the case details.`;
    };

    const syncBranchUiLock = () => {
        const locked = shouldLockOtherBranchIntake();

        formContent?.classList.toggle('intake-locked', locked);
        lockOverlay?.classList.toggle('hidden', !locked);

        if (locked) {
            next?.setAttribute('disabled', 'disabled');
            save?.setAttribute('disabled', 'disabled');
        } else {
            next?.removeAttribute('disabled');
            if (!otherBranchWindowClosed) save?.removeAttribute('disabled');
        }

        if (otherBranchWindowClosed) {
            save?.setAttribute('disabled', 'disabled');
        }
    };

    const computedAge = () => {
        const years = Number.parseInt(age?.value ?? '', 10);
        return Number.isFinite(years) ? years : null;
    };

    const hasSeniorDiscount = () => senior?.value === '1' && (computedAge() ?? -1) >= 60;

    const autoDiscountMeta = () => {
        if (hasSeniorDiscount()) {
            return {
                type: 'Senior Citizen Discount',
                source: `Senior (${seniorPct}%)`,
                amount: num(pkgAmount?.value) * (seniorPct / 100),
                message: `Senior Citizen discount is applied automatically at ${seniorPct}% of the package price.`,
            };
        }

        if (senior?.value === '1' && (computedAge() ?? -1) < 60) {
            return {
                type: 'Senior Citizen Discount',
                source: 'Age Below 60',
                amount: 0,
                message: 'Senior Citizen discount applies only when computed age is at least 60.',
            };
        }

        const selected = pkg();
        if (selected && selected.value !== 'custom' && selected.dataset.promoNow === '1') {
            const promoType = String(selected.dataset.promoType || '').toUpperCase();
            const promoValue = num(selected.dataset.promoValue);
            const amount = ['PERCENT', 'PERCENTAGE'].includes(promoType)
                ? num(pkgAmount?.value) * (promoValue / 100)
                : promoValue;
            const label = selected.dataset.promoLabel || 'Package Promo';

            return {
                type: 'Package Promo',
                source: label,
                amount: Math.max(amount, 0),
                message: `${label} is applied to the package price when no Senior/PWD discount is selected.`,
            };
        }

        return {
            type: 'None',
            source: 'None',
            amount: 0,
            message: 'No automatic discount applies.',
        };
    };

    const discount = () => {
        const meta = autoDiscountMeta();
        return { amount: meta.amount, source: meta.source, type: meta.type, message: meta.message };
    };

    const totals = () => {
        const packagePrice = num(pkgAmount?.value);
        const selectedAddOns = addOnsTotal();
        const structured = serviceCharges();
        const additional = num(addAmt?.value);
        const structuredTotal = structured.total;
        const subtotal = packagePrice + structuredTotal + selectedAddOns + additional;
        const disc = discount();
        const taxableBase = Math.max(subtotal - Math.min(disc.amount, subtotal), 0);
        const rate = Math.max(0, Math.min(num(taxRate?.value), 100));
        const tax = Math.max(taxableBase * (rate / 100), 0);
        const total = Math.max(taxableBase + tax, 0);

        if (payNow() && payType() === 'FULL' && amountPaid) {
            amountPaid.value = total > 0 ? total.toFixed(2) : '';
        }

        const paid = payNow() ? num(amountPaid?.value) : 0;
        const balance = Math.max(total - paid, 0);
        const status = !payNow() || paid <= 0 ? 'UNPAID' : paid < total ? 'PARTIAL' : 'PAID';

        return { packagePrice, structured, structuredTotal, selectedAddOns, additional, subtotal, disc, tax, total, paid, balance, status, rate };
    };

    const paymentMethodSummary = () => {
        const clean = (value) => normalizeText(value || '');
        const refPattern = /^[A-Za-z0-9 _/-]+$/;
        const accountPattern = /^(?=.*[\p{L}])[\p{L}\p{M} .'-]+$/u;
        const refState = (value, required = false) => {
            const ref = clean(value);
            if (!ref) return required ? { value: 'Not provided', invalid: false } : { value: '', invalid: false };
            if (ref.length < 4 || ref.length > 60 || !refPattern.test(ref)) {
                return { value: 'Invalid reference number', invalid: true };
            }
            return { value: ref, invalid: false };
        };
        const accountState = (field) => {
            const value = clean(field?.value);
            if (!value) return '';
            if (value.length < 2 || value.length > 100 || !accountPattern.test(value)) return '';
            return value;
        };

        if (!payNow()) return { method: 'Not recorded', bank: '', account: '', mobile: '', channelLabel: 'Payment Channel', channel: '', referenceLabel: 'Reference No.', reference: '', referenceInvalid: false, notes: '' };
        const selected = payMethodRadios.find(r => r.checked)?.value || 'cash';
        if (selected !== 'cashless') {
            const receipt = clean(cashReferenceNumber?.value);
            const validReceipt = /^[A-Za-z0-9 /-]{3,50}$/.test(receipt);
            return {
                method: 'Cash',
                bank: '',
                account: '',
                mobile: '',
                channelLabel: 'Payment Channel',
                channel: '',
                referenceLabel: 'Receipt / OR No.',
                reference: validReceipt ? receipt : 'Not provided',
                referenceInvalid: !validReceipt,
                notes: '',
            };
        }

        const type = cashlessType?.value || '';
        const ref = refState(referenceNumber?.value, ['bank_transfer', 'gcash', 'maya', 'other'].includes(type));
        const approval = document.getElementById('approval_code')?.value?.trim() || '';
        const cardType = document.getElementById('card_type')?.value || '';
        const terminal = clean(document.getElementById('terminal_provider')?.value);
        const activeAccount = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-account-name-field]`);
        const activeMobile = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-mobile-number-field]`);
        const activeChannel = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-payment-channel-field]`);
        const paymentNotes = clean(document.querySelector(`[data-intake-cashless-panel="${type}"] [name="payment_notes"]`)?.value);

        if (type === 'bank_transfer') {
            const bank = bankName?.value === 'Other Bank' ? (clean(otherBankName?.value) || 'Not selected') : (bankName?.value || 'Not selected');
            return { method: 'Bank Transfer', bank, account: accountState(accountName), mobile: '', channelLabel: 'Payment Channel', channel: '', referenceLabel: 'Reference No.', reference: ref.value, referenceInvalid: ref.invalid, notes: '' };
        }
        if (type === 'gcash') return { method: 'GCash', bank: '', account: accountState(activeAccount), mobile: clean(activeMobile?.value), channelLabel: 'Payment Channel', channel: '', referenceLabel: 'Reference No.', reference: ref.value, referenceInvalid: ref.invalid, notes: '' };
        if (type === 'maya') return { method: 'Maya', bank: '', account: accountState(activeAccount), mobile: clean(activeMobile?.value), channelLabel: 'Payment Channel', channel: '', referenceLabel: 'Reference No.', reference: ref.value, referenceInvalid: ref.invalid, notes: '' };
        if (type === 'card') {
            const approvalState = refState(approval, false);
            const referenceValue = approval ? approvalState.value : ref.value;
            return { method: 'Card', bank: '', account: '', mobile: '', channelLabel: cardType ? 'Card Type' : 'Terminal / Provider', channel: cardType ? cardType.charAt(0).toUpperCase() + cardType.slice(1) : terminal, referenceLabel: approval ? 'Approval Code' : 'Reference No.', reference: referenceValue, referenceInvalid: approval ? approvalState.invalid : ref.invalid, notes: terminal && cardType ? `Terminal / Provider: ${terminal}` : '' };
        }
        if (type === 'other') return { method: 'Other', bank: '', account: '', mobile: '', channelLabel: 'Payment Channel', channel: clean(activeChannel?.value) || 'Not selected', referenceLabel: 'Reference No.', reference: ref.value, referenceInvalid: ref.invalid, notes: paymentNotes };
        return { method: 'Cashless', bank: '', account: '', mobile: '', channelLabel: 'Payment Channel', channel: '', referenceLabel: 'Reference No.', reference: '', referenceInvalid: false, notes: '' };
    };

    const syncRequestDate = () => {
        if (!requestDateDisplay || !requestDate?.value) return;
        requestDateDisplay.textContent = formatDateOnly(requestDate.value);
    };

    const getRawDateString = (input, picker) => {
        const typed = (input?.dataset?.lastTypedValue || '').trim();
        const visible = (picker?.altInput?.value || '').trim();
        const actual = (input?.value || '').trim();
        return typed || visible || actual;
    };

    const getDateValue = (input, picker) => {
        if (input?.dataset.invalidTypedValue) return null;
        const picked = picker?.selectedDates?.[0];
        if (picked instanceof Date && !Number.isNaN(picked.getTime())) return picked;

        const raw = getRawDateString(input, picker);
        if (!raw) return null;

        if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
            const [year, month, day] = raw.split('-').map(Number);
            const date = new Date(year, month - 1, day);
            return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day ? date : null;
        }

        const parsed = Date.parse(raw);
        return Number.isNaN(parsed) ? null : new Date(parsed);
    };

    const dateOnly = (date) => {
        if (!(date instanceof Date) || Number.isNaN(date.getTime())) return null;
        return new Date(date.getFullYear(), date.getMonth(), date.getDate());
    };

    const sameDate = (first, second) => {
        const firstOnly = dateOnly(first);
        const secondOnly = dateOnly(second);
        return !!firstOnly && !!secondOnly && firstOnly.getTime() === secondOnly.getTime();
    };

    const markIntermentDateManual = () => {
        if (!interment || isAutoSyncingIntermentDate) return;
        interment.dataset.autoFilled = '0';
    };

    const syncIntermentDateFromFuneral = (ceremonyChanged = false) => {
        if (!funeral || !interment || isAutoSyncingIntermentDate) return;

        const serviceRaw = (funeral.value || '').trim();
        const intermentRaw = (interment.value || '').trim();
        const shouldAutoFill = (ceremonyChanged && Boolean(serviceRaw))
            || interment.dataset.autoFilled === '1' || !intermentRaw;
        if (!shouldAutoFill || serviceRaw === intermentRaw) return;

        isAutoSyncingIntermentDate = true;
        interment.dataset.autoFilled = '1';
        interment.dataset.userTyped = '0';
        interment.dataset.lastTypedValue = '';
        interment.dataset.invalidTypedValue = '';

        if (interPicker) {
            interPicker.set('minDate', serviceRaw || wakeStart?.value || (historicalIntake ? null : requestDate?.value || 'today'));
            interPicker.setDate(serviceRaw || null, false, 'Y-m-d');
        } else {
            interment.value = serviceRaw;
        }

        interment.setCustomValidity('');
        setFieldError(interment, intermentErr, '', interPicker?.altInput);
        isAutoSyncingIntermentDate = false;
    };

    const syncAge = () => {
        if (restoringPickerState) return;
        const birth = getDateValue(born, bornPicker);
        const death = getDateValue(died, diedPicker);
        const prevSeniorValue = senior?.value;

        if (!birth || !death || death < birth) {
            if (age) age.value = '';
            if (senior) senior.value = '0';
            if (senior && senior.value !== prevSeniorValue) syncControls();
            return;
        }

        let years = death.getFullYear() - birth.getFullYear();
        if (
            death.getMonth() < birth.getMonth() ||
            (death.getMonth() === birth.getMonth() && death.getDate() < birth.getDate())
        ) {
            years -= 1;
        }

        age.value = String(years);

        // Auto-mark senior citizen based on computed age.
        if (senior) senior.value = years >= 60 ? '1' : '0';

        if (senior && senior.value !== prevSeniorValue) {
            syncControls();
        }
    };

    const enforceSeniorEligibility = (notify = false) => {
        if (!senior) return true;

        const years = Number.parseInt(age?.value ?? '', 10);
        const eligible = Number.isFinite(years) && years >= 60;

        if (!eligible && senior.value === '1') {
            senior.value = '0';
            senior.setCustomValidity('Senior Citizen can only be set to Yes when computed age is at least 60.');
            if (notify) senior.reportValidity();
            return false;
        }

        senior.setCustomValidity('');
        return true;
    };

    const syncDateConstraints = () => {
        if (restoringPickerState) return;
        if (died) died.max = today;

        if (wakeStart) {
            wakeStart.min = historicalIntake ? '' : (requestDate?.value || '');
            if (wakeStartPicker) wakeStartPicker.set('minDate', historicalIntake ? null : (requestDate?.value || null));
        }

        if (wakePicker) {
            wakePicker.set('minDate', wakeStart?.value || (historicalIntake ? null : requestDate?.value || null));
        }

        if (interPicker) {
            interPicker.set('minDate', funeral?.value || wakeStart?.value || (historicalIntake ? null : requestDate?.value || 'today'));
        }

        if (paidAt && died?.value) {
            paidAt.removeAttribute('min');
        }
    };

    const syncPreferredPackage = () => {
        if (!customPkgRadio) return;

        const serviceRows = [...document.querySelectorAll('.custom-service-check:checked:not(:disabled)')].map((input) => {
            const label = input.closest('label')?.textContent?.trim() || input.value;
            const dependent = input.closest('.rounded-lg')?.querySelector('.custom-service-dependent input, .custom-service-dependent select');
            const detail = dependent?.tagName === 'SELECT'
                ? dependent.selectedOptions?.[0]?.textContent?.trim()
                : (dependent?.value ? `${dependent.value} ${['body_retrieval', 'hearse'].includes(input.value) ? 'km' : 'days'}` : '');
            return detail && !detail.startsWith('Choose') ? `${label} - ${detail}` : label;
        });
        document.querySelectorAll('.custom-inclusion-editor').forEach((editor) => {
            const field = editor.querySelector('input,select');
            const chip = editor.querySelector('[data-custom-inclusion-chip]');
            const value = field?.tagName === 'SELECT'
                ? (field.value ? field.selectedOptions[0].textContent.trim() : '')
                : (Number(field?.value) > 0 ? `${field.value} days` : '');
            const action = editor.querySelector('.custom-inclusion-edit-label');
            const serviceName = editor.closest('.rounded-lg')?.querySelector('label')?.textContent.trim() || '';
            const isHearse = editor.dataset.inclusionKind === 'hearse';
            const isCasket = field?.tagName === 'SELECT' && !isHearse;
            if (chip) {
                chip.textContent = value || '';
                chip.hidden = !value;
            }
            if (action) {
                action.textContent = value ? (isCasket || isHearse ? 'Change' : 'Edit') : (isHearse ? 'Choose a hearse' : isCasket ? 'Choose a casket' : 'Set agreed days');
                action.setAttribute('aria-label', value ? (isHearse ? 'Change hearse' : isCasket ? 'Change included casket' : `Edit ${serviceName} days`) : (isHearse ? 'Choose a hearse' : isCasket ? 'Choose a casket' : `Set ${serviceName} agreed days`));
            }
        });
        const otherRows = (document.getElementById('custom_other_inclusions')?.value || '').split(/\r?\n/u).map((value) => value.trim()).filter(Boolean);
        if (prefIncl) prefIncl.value = [...serviceRows, ...otherRows].join('\n');
        if (prefFree) prefFree.value = [...document.querySelectorAll('.custom-freebie-check:checked')].map((input) => input.dataset.freebieName).join('\n');

        if (prefPkg) customPkgRadio.dataset.name = prefPkg.value || 'Client Preference';
        if (prefPrice) customPkgRadio.dataset.price = prefPrice.value || '0';
        if (prefIncl) customPkgRadio.dataset.inclusions = prefIncl.value || '';
        if (prefFree) customPkgRadio.dataset.freebies = prefFree.value || '';

        if (customPkgPriceDisplay) customPkgPriceDisplay.textContent = fmt(customPkgRadio.dataset.price || 0);
        if (pkgAmount && customPkgRadio.checked) pkgAmount.value = customPkgRadio.dataset.price || '';
    };

    const renderPkg = (shouldToast = false) => {
        syncChapelPackageChoices();
        const selected = pkg();
        syncIncludedAddOnAdjustments(selected);
        if (regularPackageChoice) {
            regularPackageChoice.value = selected && selected !== customPkgRadio && chapelPackageIsEligible(selected) ? selected.value : '';
            regularPackagePicker?.sync();
        }

        pkgCards.forEach((card) => {
            const radio = cardRadio(card);
            const eligible = chapelPackageIsEligible(radio);
            card.toggleAttribute('data-coverage-hidden', !eligible);
            const active = !!radio?.checked && eligible;
            const details = active && card.querySelector('[data-inline-package-details]');
            if (details && !details.firstElementChild) {
                const rows = detailRowsForPackage(radio);
                details.innerHTML = `
                    <section class="inline-package-section">
                        <h5>Basic Funeral Inclusions</h5>
                        <div class="inline-package-items">${rows.services}</div>
                        ${rows.hasCasket ? '<p class="inline-package-detail mt-2 text-xs">The package price covers the included casket. A replacement or upgrade may add a charge.</p>' : ''}
                    </section>
                    ${rows.otherInclusions || rows.hasCatalogAddOns ? `
                        <section class="inline-package-section">
                            <h5>Other Inclusions</h5>
                            <div class="inline-package-items">${rows.otherInclusions}</div>
                            <div data-package-other-slot></div>
                        </section>
                    ` : ''}
                    <section class="inline-package-section">
                        <h5>Included Freebies</h5>
                        <div class="inline-package-items">${rows.freebies}</div>
                    </section>
                `;
            }
            card.classList.toggle('border-slate-800', active);
            card.classList.toggle('bg-slate-50', active);
            card.classList.toggle('ring-2', active);
            card.classList.toggle('selected', active);
        });

        if (pkgAmount) pkgAmount.value = selected?.dataset.price || '';
        if (packageError) packageError.classList.add('hidden');

        if (inclusions) inclusions.innerHTML = list(selected?.dataset.inclusions).map((item) => `<li>${escapeHtml(item)}</li>`).join('');
        if (freebies) freebies.innerHTML = list(selected?.dataset.freebies).map((item) => `<li>${escapeHtml(item)}</li>`).join('');

        const addOns = parseAddOns(selected);
        if (optionalAddOnsSection) optionalAddOnsSection.classList.toggle('hidden', !selected);
        setAddOnsPanelOpen(false);
        if (optionalAddOnsList) {
            if (!selected) {
                optionalAddOnsList.innerHTML = '';
            } else if (addOns.length === 0) {
                optionalAddOnsList.innerHTML = '<div class="rounded-lg border border-[#C9C5BB] bg-white px-3 py-3 text-sm font-semibold text-[#5F685F]">No add-ons are currently available.</div>';
            } else {
                optionalAddOnsList.innerHTML = addOns.map((addOn) => {
                    const checked = oldSelectedAddOns.has(String(addOn.id)) ? 'checked' : '';
                    const rawQuantity = addOnQuantities.has(String(addOn.id)) ? addOnQuantities.get(String(addOn.id)) : 1;
                    const quantity = Number(rawQuantity);
                    const searchable = [addOn.name, addOn.category, addOn.description, addOn.unit].filter(Boolean).join(' ').toLowerCase();
                    return `
                        <div class="optional-add-on-row inline-inclusion-card" tabindex="0" role="group" aria-label="${escapeHtml(addOn.name)}" data-add-on-search="${escapeHtml(searchable)}">
                            <input type="checkbox" hidden name="selected_add_ons[]" value="${escapeHtml(addOn.id)}" ${checked} class="optional-add-on-checkbox" data-name="${escapeHtml(addOn.name)}" data-description="${escapeHtml(addOn.description || '')}" data-price="${escapeHtml(addOn.price)}" data-unit="${escapeHtml(addOn.unit || '')}" data-category="${escapeHtml(addOn.category || '')}">
                            <span class="addon-card-copy min-w-0">
                                <span class="addon-card-heading">
                                    <span class="font-bold">${escapeHtml(addOn.name)}</span>
                                    <span class="font-black">&#8369; ${fmt(addOn.price)}${addOn.unit ? ` / ${escapeHtml(addOn.unit)}` : ''}</span>
                                </span>
                                <span class="mt-1 flex flex-wrap items-center gap-2">
                                    ${addOn.category ? `<span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500">${escapeHtml(addOn.category)}</span>` : ''}
                                </span>
                                ${addOn.description ? `<span class="mt-1 block text-xs font-medium text-[#5F685F]">${escapeHtml(addOn.description)}</span>` : ''}
                            </span>
                            <button type="button" data-optional-add-on-toggle aria-pressed="${Boolean(checked)}" aria-label="Select or unselect ${escapeHtml(addOn.name)}">${checked ? '<i class="bi bi-check2" aria-hidden="true"></i> Selected' : '<i class="bi bi-plus" aria-hidden="true"></i> Select'}</button>
                                <span class="add-on-quantity-area ${checked ? '' : 'hidden'}" data-add-on-quantity-note>
                                    <label for="draft_add_on_${escapeHtml(addOn.id)}">Quantity <span>(${escapeHtml(addOn.unit || 'unit')})</span></label>
                                    <span class="inline-quantity">
                                    <button type="button" data-optional-quantity-step="-1" aria-label="Decrease ${escapeHtml(addOn.name)} quantity">−</button>
                                    <input id="draft_add_on_${escapeHtml(addOn.id)}" type="number" min="1" step="1" required inputmode="numeric" value="${Number.isInteger(quantity) ? quantity : ''}" ${checked ? '' : 'disabled'} class="w-20 rounded-md border border-[#C9C5BB] bg-white px-2 py-1 text-sm text-[#333333] disabled:opacity-50" data-draft-add-on-quantity aria-label="Quantity for ${escapeHtml(addOn.name)}">
                                    <button type="button" data-optional-quantity-step="1" aria-label="Increase ${escapeHtml(addOn.name)} quantity">+</button>
                                    </span>
                                    <span class="add-on-line-total">Line total <strong>&#8369;<span data-draft-add-on-line-total>${fmt(num(addOn.price) * quantity)}</span></strong></span>
                                </span>
                        </div>`;
                }).join('');
            }
        }
        if (optionalAddOnsSearch) optionalAddOnsSearch.value = '';
        const applied = appliedAddOns();
        if (chooseAddOnsBtn) {
            const unavailable = !selected || addOns.length === 0;
            chooseAddOnsBtn.disabled = unavailable;
            chooseAddOnsBtn.hidden = addOns.length === 0;
            chooseAddOnsBtn.classList.toggle('opacity-50', unavailable);
            chooseAddOnsBtn.classList.toggle('cursor-not-allowed', unavailable);
            chooseAddOnsBtn.innerHTML = addOns.length === 0
                ? '<i class="bi bi-slash-circle"></i> No Add-ons Available'
                : (applied.length ? '<i class="bi bi-pencil-square" aria-hidden="true"></i> Change Add-ons' : '<i class="bi bi-plus-circle" aria-hidden="true"></i> Choose Add-ons');
        }
        if (selectedAddOnsTotal) selectedAddOnsTotal.textContent = fmt(addOnsTotal());
        if (draftAddOnsTotal) draftAddOnsTotal.textContent = fmt(addOnsTotal());
        if (selectedAddOnsLines) {
            selectedAddOnsLines.classList.toggle('hidden', applied.length === 0);
            selectedAddOnsLines.innerHTML = applied.map((addOn) => `
                <div class="intake-applied-add-on" data-applied-add-on-row="${escapeHtml(addOn.id)}">
                    <span class="min-w-0">
                        <span class="block font-bold">${escapeHtml(addOn.name)}</span>
                        <span class="intake-add-on-rate block text-sm">&#8369;${fmt(addOn.price)} per ${escapeHtml(addOn.unit || 'unit')}</span>
                    </span>
                    <div class="intake-applied-add-on-actions text-sm">
                        <label>Qty (${escapeHtml(addOn.unit || 'unit')})
                            <input type="number" name="add_on_quantities[${escapeHtml(addOn.id)}]" min="1" step="1" required inputmode="numeric" value="${addOn.quantity || ''}" class="form-input" data-applied-add-on-quantity="${escapeHtml(addOn.id)}" aria-label="Quantity for ${escapeHtml(addOn.name)}" aria-invalid="${addOnQuantityErrors[String(addOn.id)] ? 'true' : 'false'}">
                        </label>
                        <strong class="whitespace-nowrap">&#8369;<span data-applied-add-on-line-total="${escapeHtml(addOn.id)}">${fmt(addOn.lineTotal)}</span></strong>
                        <button type="button" data-remove-applied-add-on="${escapeHtml(addOn.id)}" aria-label="Remove ${escapeHtml(addOn.name)}">Remove</button>
                    </div>
                    ${addOnQuantityErrors[String(addOn.id)] ? `<span class="text-xs font-semibold text-red-700" role="alert">${escapeHtml(addOnQuantityErrors[String(addOn.id)])}</span>` : ''}
                </div>
            `).join('');
        }
        const appliedCount = applied.length;
        if (selectedAddOnsCount) {
            const excludedReasons = [];
            if (structuredInclusionsFor(selected).some(item => item.add_on_id && item.add_on_category !== chapelCategory)) {
                excludedReasons.push('Package add-ons are listed under Other Inclusions.');
            }
            if (globalAddOns.some(item => item.category === chapelCategory)) {
                excludedReasons.push('Chapel rates are shown under Chapel Viewing Charge.');
            }
            selectedAddOnsCount.textContent = appliedCount
                ? `${appliedCount} add-on${appliedCount === 1 ? '' : 's'} selected`
                : (addOns.length ? 'No add-ons selected' : ['No optional add-ons available.', ...excludedReasons].join(' '));
        }


        syncPreferredPackage();

        const isCustomSelected = selected === customPkgRadio;
        if (customPkgFields) {
            customPkgFields.classList.toggle('hidden', !isCustomSelected);
        }

        [prefPkg, prefPrice].forEach((field) => {
            if (!field) return;
            field.disabled = !isCustomSelected;
            if (!isCustomSelected) field.setCustomValidity('');
        });
        [prefPkg, prefPrice].forEach((field) => {
            if (field) field.required = isCustomSelected;
        });
        customPkgFields?.querySelectorAll('.custom-service-check, .custom-freebie-check, #custom_other_inclusions').forEach((field) => {
            field.disabled = !isCustomSelected;
        });
        customPkgFields?.querySelectorAll('.custom-service-check').forEach((checkbox) => {
            const excluded = checkbox.value === 'home_viewing' && isChapelViewing();
            checkbox.disabled = !isCustomSelected || excluded;
            checkbox.closest('.custom-package-service-card').hidden = excluded;
            const coverageRow = checkbox.closest('.custom-package-service-card')?.querySelector('[data-custom-coverage]');
            if (coverageRow) {
                coverageRow.hidden = !checkbox.checked || checkbox.disabled;
                coverageRow.querySelectorAll('input').forEach(field => { field.disabled = coverageRow.hidden; });
                coverageRow.querySelector('[data-coverage-included]').required = !coverageRow.hidden && coverageRow.dataset.unit === 'days';
            }
            const dependent = checkbox.closest('.rounded-lg')?.querySelector('.custom-service-dependent');
            if (!dependent) return;
            dependent.classList.toggle('hidden', !checkbox.checked || !isCustomSelected);
            dependent.querySelectorAll('input, select').forEach((field) => {
                field.disabled = !checkbox.checked || !isCustomSelected;
                field.required = checkbox.checked && isCustomSelected;
            });
        });

        const regularIncludesHearse = selected && selected !== customPkgRadio
            && (structuredInclusionsFor(selected).some((row) => row.service_type === 'hearse')
                || list(selected.dataset.inclusions).some((name) => /\bhearse\b/i.test(name)));
        const customIncludesHearse = isCustomSelected
            && !!document.querySelector('.custom-service-check[value="hearse"]:checked');
        const requiresHearse = !!(regularIncludesHearse || customIncludesHearse);
        mountPackageInclusionCards(selected, isCustomSelected, regularIncludesHearse, customIncludesHearse);
        document.getElementById('regular-hearse-inclusion').classList.toggle('hidden', !regularIncludesHearse);
        document.getElementById('custom-hearse-picker').hidden = !customIncludesHearse;
        hearseAssignmentFields?.classList.toggle('hidden', !requiresHearse);
        if (hearseCatalogSelect) {
            hearseCatalogSelect.options[0].textContent = isCustomSelected ? 'Choose an available hearse' : 'Use included hearse';
            const includedHearse = structuredInclusionsFor(selected).find(row => row.service_type === 'hearse')?.hearse;
            [...hearseCatalogSelect.options].slice(1).forEach(option => {
                const reference = option.dataset.referenceValue;
                const extra = includedHearse?.reference_value != null && reference !== ''
                    ? ` · Extra charge ₱${fmt(Math.max(0, num(reference) - num(includedHearse.reference_value)))}`
                    : ' · Upgrade rate unavailable';
                option.textContent = option.dataset.name + (isCustomSelected ? '' : extra);
            });
            hearseCatalogSelect.disabled = !requiresHearse;
            hearseCatalogSelect.required = requiresHearse && isCustomSelected;
            if (!requiresHearse) {
                hearseCatalogSelect.value = '';
                hearseCatalogSelect.setCustomValidity('');
            }
        }
        if (replacementCasket) {
            const includedValue = num(includedCasketFor(selected).reference_value);
            [...replacementCasket.options].slice(1).forEach(option => {
                const label = [option.dataset.name, option.dataset.material].filter(Boolean).join(' - ');
                const selectedValue = num(option.dataset.price);
                const extra = includedValue > 0 && selectedValue > 0
                    ? ` · Extra charge ₱${fmt(Math.max(0, selectedValue - includedValue))}`
                    : ' · Upgrade rate unavailable';
                option.textContent = label + extra + (option.disabled ? ' · Unavailable' : '');
            });
        }

        if (selected && shouldToast) {
            const name = selected.dataset.name || 'this package';
            showPackageToast(`You selected the ${name} package.`);
        }
    };

    const syncControls = () => {
        if (restoringPickerState) return;
        const typeSelected = !!document.querySelector('.payment-type-radio:checked') || !!document.getElementById('payment_type')?.value;
        document.getElementById('payment_amount_section')?.classList.toggle('hidden', !payNow() || !typeSelected);
        const hasPaymentMethod = !!document.querySelector('.payment-method-radio:checked:not([value="none"])');
        payTypeRadios.forEach((radio) => { radio.disabled = !hasPaymentMethod; });
        document.getElementById('payment_type_section')?.classList.toggle('hidden', !hasPaymentMethod);
        payTypeCards.forEach((card) => {
            card.classList.toggle('opacity-50', !hasPaymentMethod);
            card.classList.toggle('pointer-events-none', !hasPaymentMethod);
        });
        enforceSeniorEligibility(false);

        if (deceasedAddr && clientAddr && deceasedAddr.dataset.manual !== '1') deceasedAddr.value = clientAddr.value;

        document.getElementById('senior_verification_section')?.classList.toggle('hidden', senior?.value !== '1');
        setHidden(seniorIdWrap, seniorId, senior?.value !== '1');
        if (seniorId) seniorId.required = false;

        setHidden(proofWrap, proofInput, senior?.value !== '1');
        if (proofInput) proofInput.required = false;

        if (payWrap) payWrap.classList.remove('hidden');

        if (amountPaid) {
            amountPaid.disabled = !payNow();
            amountPaid.readOnly = payType() === 'FULL';
        }

        if (paidAt) {
            paidAt.disabled = !payNow() || !typeSelected;
            paidAt.required = payNow() && typeSelected;
            if (!payNow() || !typeSelected) paidAt.setCustomValidity('');
        }

        payTypeCards.forEach((card) => {
            const active = !!card.querySelector('.payment-type-radio')?.checked;
            card.classList.toggle('active-tab', active);
            card.classList.toggle('border-slate-800', active);
            card.classList.toggle('bg-slate-50', active);
            card.classList.toggle('ring-2', active);
        });

        if (discountHelpSecondary) discountHelpSecondary.textContent = autoDiscountMeta().message;
        syncBranchUiLock();
    };

    const advisorySummary = document.getElementById('schedule_advisory_summary');
    const scheduleSummaryTrigger = document.getElementById('intake_schedule_summary_trigger');
    const scheduleSummaryPanel = document.getElementById('intake_schedule_summary');
    const scheduleSummaryDates = [...scheduleSummaryPanel.querySelectorAll('[data-summary-date]')];
    const supportsScheduleSummaryPopover = typeof scheduleSummaryPanel.showPopover === 'function';
    const closeScheduleSummary = () => {
        if (supportsScheduleSummaryPopover) {
            if (scheduleSummaryPanel.matches(':popover-open')) scheduleSummaryPanel.hidePopover();
        } else {
            scheduleSummaryPanel.hidden = true;
            scheduleSummaryTrigger.setAttribute('aria-expanded', 'false');
        }
    };
    if (supportsScheduleSummaryPopover) {
        scheduleSummaryPanel.hidden = false;
        window.bindServiceFieldPopover(scheduleSummaryTrigger, scheduleSummaryPanel, null, formContent);
    } else {
        scheduleSummaryPanel.removeAttribute('popover');
        scheduleSummaryTrigger.addEventListener('click', () => {
            scheduleSummaryPanel.hidden = !scheduleSummaryPanel.hidden;
            scheduleSummaryTrigger.setAttribute('aria-expanded', String(!scheduleSummaryPanel.hidden));
        });
    }
    const advisoryFields = [...f.querySelectorAll('[data-advisory-field]')].map(launch => {
        const trigger = launch.querySelector('button');
        const panel = launch.querySelector('.schedule-advisory-panel');
        const supportsPopover = typeof panel.showPopover === 'function';
        if (supportsPopover) {
            panel.hidden = false;
            window.bindServiceFieldPopover(trigger, panel, null, formContent);
        } else {
            trigger.setAttribute('aria-expanded', 'false');
            trigger.addEventListener('click', () => {
                panel.hidden = !panel.hidden;
                trigger.setAttribute('aria-expanded', String(!panel.hidden));
            });
        }
        return { launch, trigger, panel, supportsPopover };
    });
    const advisoryUnavailable = 'Schedule advisory unavailable. Check the branch schedule.';
    const scheduleMatchesStart = (entry, event) => entry.date === event.date_key && entry.time === event.start_time;
    let advisoryKey = '';
    let advisoryTimer;
    let advisoryController;
    let advisoryCache;

    const advisoryHearseId = () => {
        const selected = pkg();
        if (!selected) return '';
        if (!hearseCatalogSelect?.disabled && hearseCatalogSelect?.value) return hearseCatalogSelect.value;
        if (selected === customPkgRadio) return '';
        return structuredInclusionsFor(selected).find(row => row.service_type === 'hearse')?.hearse?.id || '';
    };

    const drawScheduleAdvisory = (entries, data, unavailable = false) => {
        const events = (data?.events ?? []).filter(event =>
            entries.some(entry => entry.field === event.event_type && entry.date === event.date_key));
        const sameStart = events.filter(event => entries.some(entry =>
            entry.field === event.event_type && scheduleMatchesStart(entry, event)));
        const otherEvents = events.filter(event => !sameStart.includes(event));
        const hearseId = advisoryHearseId();
        const usesSameHearse = event => hearseId && event.assigned_hearse_id
            && String(hearseId) === String(event.assigned_hearse_id);
        const branchName = data?.branch_name;
        advisoryFields.forEach(({ launch, trigger, panel, supportsPopover }) => {
            const entry = entries.find(item => item.field === launch.dataset.advisoryField);
            const onDate = entry ? events.filter(event => event.event_type === entry.field && event.date_key === entry.date) : [];
            const matches = onDate.filter(event => scheduleMatchesStart(entry, event));
            const others = onDate.filter(event => !matches.includes(event));
            launch.hidden = step !== 3 || !entry || (!onDate.length && !unavailable);
            if (launch.hidden) {
                if (supportsPopover && panel.matches(':popover-open')) panel.hidePopover();
                if (!supportsPopover) {
                    panel.hidden = true;
                    trigger.setAttribute('aria-expanded', 'false');
                }
                return;
            }
            trigger.dataset.tone = matches.length ? 'match' : 'info';
            trigger.querySelector('i').className = matches.length ? 'bi bi-exclamation-triangle' : 'bi bi-info-circle';
            trigger.querySelector('[data-advisory-trigger-label]').textContent = matches.length
                ? `${matches.length} ${matches.length === 1 ? 'event starts' : 'events start'} at this time · View advisory`
                : others.length ? `${others.length} other ${others.length === 1 ? 'event' : 'events'} · View advisory` : 'View schedule advisory';
            panel.querySelector('[data-advisory-guidance]').hidden = !matches.length;
            panel.querySelectorAll('[data-schedule-advisory]').forEach(card => {
                const matched = card.dataset.scheduleAdvisory === 'match';
                const items = matched ? matches : others;
                card.replaceChildren();
                card.hidden = !items.length;
                if (!matched && unavailable) {
                    card.textContent = advisoryUnavailable;
                    card.hidden = false;
                    return;
                }
                if (!items.length) return;
                const title = document.createElement('h4');
                title.textContent = matched ? 'Same start time' : `Other events on this date${branchName ? ` (${branchName})` : ''}`;
                const list = document.createElement('ul');
                items.forEach(event => {
                    const item = document.createElement('li');
                    item.dataset.tone = matched ? 'match' : 'same';
                    const dateTime = event.start_time ? `${event.date} at ${event.time}` : `${event.date} (Time not recorded)`;
                    item.textContent = `${dateTime} · ${event.event} · Case ${event.case_code}`;
                    if (matched && usesSameHearse(event)) {
                        const asset = document.createElement('strong');
                        asset.textContent = `Same hearse on both cases${event.assigned_hearse ? ` · ${event.assigned_hearse}` : ''}. Confirm vehicle availability with your branch.`;
                        item.append(asset);
                    }
                    list.append(item);
                });
                card.append(title, list);
            });
        });

        const same = sameStart.length;
        const other = otherEvents.length;
        advisorySummary.dataset.tone = same ? 'match' : 'info';
        const sameHearseCases = new Set(sameStart.filter(usesSameHearse).map(event => event.case_code)).size;
        const dateLabel = new Set(entries.map(entry => entry.date)).size === 1 ? 'this date' : 'the selected dates';
        advisorySummary.hidden = step !== totalSteps || (!events.length && !unavailable);
        advisorySummary.textContent = unavailable ? advisoryUnavailable : [
            same ? `${same} ${same === 1 ? 'event starts' : 'events start'} at the same time` : '',
            sameHearseCases ? `Same hearse on ${sameHearseCases} ${sameHearseCases === 1 ? 'case' : 'cases'}` : '',
            other ? `${other} other ${other === 1 ? 'event' : 'events'} on ${dateLabel}` : '',
        ].filter(Boolean).join(' · ');
    };

    const updateScheduleAdvisory = () => {
        const entries = [
            { field: 'funeral', date: funeral.value, time: funeralTime.value },
            { field: 'interment', date: interment.value, time: intermentTime.value },
        ].filter(entry => /^\d{4}-\d{2}-\d{2}$/.test(entry.date) && /^\d{2}:\d{2}$/.test(entry.time));
        const active = (step === 3 || step === totalSteps) && branch.value && entries.length;
        const key = active ? JSON.stringify([step, branch.value, entries, advisoryHearseId()]) : '';
        if (key === advisoryKey) return;
        advisoryKey = key;
        clearTimeout(advisoryTimer);
        advisoryController?.abort();
        const params = new URLSearchParams({ branch_id: branch.value });
        entries.forEach(entry => params.set(entry.field === 'funeral' ? 'funeral_service_at' : 'interment_at', entry.date));
        const query = params.toString();
        if (active && advisoryCache?.query === query && step !== totalSteps) {
            drawScheduleAdvisory(entries, advisoryCache.data);
            return;
        }
        drawScheduleAdvisory(entries, null);
        if (!active) return;
        const controller = new AbortController();
        advisoryController = controller;
        advisoryTimer = setTimeout(async () => {
            try {
                const response = await fetch(@json(route('intake.schedule-advisory')) + '?' + params, {
                    headers: { Accept: 'application/json' }, cache: 'no-store', signal: controller.signal,
                });
                if (!response.ok) throw new Error('Schedule check failed');
                const data = await response.json();
                if (!controller.signal.aborted) {
                    advisoryCache = { query, data };
                    drawScheduleAdvisory(entries, data);
                }
            } catch (error) {
                if (!controller.signal.aborted) drawScheduleAdvisory(entries, null, true);
            }
        }, 200);
    };
    const renderReview = () => {
        updateScheduleAdvisory();
        const selected = pkg();
        const t = totals();
        const homeViewingIncluded = isCustomSelected()
            ? !!document.querySelector('.custom-service-check[value="home_viewing"]:checked:not(:disabled)')
            : structuredInclusionsFor().some(item => item.service_type === 'home_viewing');
        homeViewingNotice.hidden = viewingType.value !== 'home' || !pkg() || homeViewingIncluded;
        homeViewingNotice.textContent = 'Home Viewing not included. No automatic Home Viewing charge applies.';

        if (reviewClient) {
            reviewClient.innerHTML = [
                detailRow('Branch', branchCode() !== '-' ? branchCode() : (!isOtherEntryMode ? mainBranchCodeDefault : '-')),
                detailRow('Client Name', textOrDash([f.elements.client_first_name?.value, f.elements.client_middle_name?.value, f.elements.client_last_name?.value, f.elements.client_suffix?.value].filter(Boolean).join(' '))),
                detailRow('Relationship', textOrDash(f.elements.client_relationship?.value)),
                detailRow('Contact Number', textOrDash(f.elements.client_contact_number?.value)),
                detailRow('Address', textOrDash(f.elements.client_address?.value)),
                ...(isOtherEntryMode ? [
                    detailRow('Reporter Name', textOrDash(f.elements.reporter_name?.value)),
                    detailRow('Reporter Contact', textOrDash(f.elements.reporter_contact?.value)),
                    detailRow('Reported Date', formatDateTime(f.elements.reported_at?.value)),
                ] : []),
            ].join('');
        }

        if (reviewDeceased) {
            reviewDeceased.innerHTML = [
                detailRow('Deceased Name', textOrDash([f.elements.deceased_first_name?.value, f.elements.deceased_middle_name?.value, f.elements.deceased_last_name?.value, f.elements.deceased_suffix?.value].filter(Boolean).join(' '))),
                detailRow('Address', textOrDash(f.elements.deceased_address?.value)),
                detailRow('Birthdate', formatDateOnly(f.elements.born?.value)),
                detailRow('Age', textOrDash(f.elements.age?.value)),
                detailRow('Date of Death', formatDateOnly(f.elements.died?.value)),
                detailRow('Senior Citizen', senior?.value === '1' ? `Yes${seniorId?.value ? ` - ${seniorId.value}` : ''}` : 'No'),
            ].join('');
        }

        if (reviewService) {
            reviewService.innerHTML = [
                detailRow('Wake Start Date & Time', formatScheduleDateTime(f.elements.wake_start_date?.value, f.elements.wake_start_time?.value)),
                detailRow('Wake End Date & Time', formatScheduleDateTime(f.elements.wake_end_date?.value, f.elements.wake_end_time?.value)),
                detailRow('Funeral Ceremony Date & Time', formatScheduleDateTime(f.elements.funeral_service_at?.value, f.elements.funeral_service_time?.value)),
                detailRow('Funeral Ceremony Location', textOrDash(f.elements.funeral_ceremony_location_mode.value === 'wake'
                    ? viewingLocation.value : f.elements.funeral_ceremony_location.value)),
                detailRow('Interment Date & Time', formatScheduleDateTime(f.elements.interment_at?.value, f.elements.interment_time?.value)),
                detailRow('Wake Duration', wakeDurationLabel(f.elements.wake_days?.value)),
                detailRow('Retrieval Excess KM', applyRetrievalExcess?.checked ? textOrDash(retrievalExcessInput?.value) : 'Within coverage'),
                detailRow('Hearse Excess KM', applyHearseExcess?.checked ? textOrDash(hearseExcessInput?.value) : 'Within coverage'),
                detailRow('Viewing Type', textOrDash(viewingType.value ? viewingType.selectedOptions[0]?.textContent : '')),
                detailRow(viewingLocationLabel.textContent, textOrDash(viewingLocation.value)),
                ...(isChapelViewing() ? [detailRow('Chapel Address', textOrDash(chapelAddress.value))] : []),
                ...(isChapelViewing() ? [
                    detailRow('Chapel Selection', chapelCoverage() === 'included' ? 'Included in Package' : 'Branch Chapel at a Daily Rate'),
                    detailRow('Chapel Charge', chapelChargePreview.textContent),
                ] : []),
                detailRow('Place of Interment', textOrDash(f.elements.place_of_cemetery?.value)),
            ].join('');
        }

        if (reviewPackage) {
            const selectedAddOnRows = appliedAddOns();
            reviewPackage.innerHTML = [
                detailRow('Selected Package', selected?.dataset?.name || '-'),
                detailRow('Assigned Hearse', hearseCatalogSelect?.value ? hearseCatalogSelect.selectedOptions[0]?.textContent : (structuredInclusionsFor().find(row => row.service_type === 'hearse')?.hearse?.name || 'None')),
                detailRow('Package Price', `PHP ${fmt(t.packagePrice)}`),
                detailRow('Included services and items', list(selected?.dataset.inclusions).join(', ') || '-'),
                detailRow('Freebies', list(selected?.dataset.freebies).join(', ') || 'None'),
                ...(isCustomSelected() && document.querySelector('.custom-service-check[value="casket"]')?.checked
                    ? [detailRow('Included Casket', document.getElementById('custom_casket_catalog_id')?.selectedOptions?.[0]?.textContent?.trim() || '-')]
                    : []),
                ...(!isCustomSelected() ? [detailRow('Replacement Casket', selectedReplacementCasket()?.name || 'Use included casket')] : []),
                detailRow('Optional Add-ons', selectedAddOnRows.length
                    ? selectedAddOnRows.map((addOn) => `${addOn.name} × ${addOn.quantity} ${addOn.unit || 'unit'} - PHP ${fmt(addOn.lineTotal)}`).join(', ')
                    : 'No optional add-ons selected.'),
            ].join('');
        }

        if (reviewBilling) {
            reviewBilling.innerHTML = [
                detailRow('Package Price', `PHP ${fmt(t.packagePrice)}`),
                detailRow('Automatic Charges', `PHP ${fmt(t.structuredTotal)}`),
                ...t.structured.includedAddOns.filter(item => item.amount > 0).map(item => detailRow(item.label, `PHP ${fmt(item.amount)}`)),
                detailRow('Casket Upgrade', `PHP ${fmt(t.structured.casket)}`),
                detailRow('Hearse Upgrade', `PHP ${fmt(t.structured.hearseUpgrade || 0)}`),
                detailRow('Hearse Excess Distance', `PHP ${fmt(t.structured.hearse.amount)}`),
                detailRow('Add-ons Total', `PHP ${fmt(t.selectedAddOns)}`),
                detailRow('Additional Services', textOrDash(additionalServices?.value)),
                detailRow('Additional Charges', `PHP ${fmt(t.additional)}`),
                detailRow('Discount Type', t.disc.type),
                detailRow('Discount Amount', `PHP ${fmt(t.disc.amount)}`),
                detailRow('Tax Amount', `PHP ${fmt(t.tax)}`),
                detailRow('Total Amount', `PHP ${fmt(t.total)}`),
            ].join('');
        }

        if (reviewPayment) {
            reviewPayment.innerHTML = [
                detailRow('Payment Recorded', payNow() ? 'Yes' : 'No'),
                detailRow('Payment Type', isOtherEntryMode ? 'FULL (Required)' : (payType() || '-')),
                detailRow('Payment Method', paymentMethodSummary().method),
                detailRow('Amount Paid', `PHP ${fmt(t.paid)}`),
                detailRow('Remaining Balance', `PHP ${fmt(t.balance)}`),
                detailRow('Payment Date', formatDateTime(paidAt?.value)),
                detailRow('Payment Status', t.status),
            ].join('');
        }
    };

    const viewingDurationLabel = days => {
        const nights = Math.max(0, days - 1);
        return `${days} ${days === 1 ? 'day' : 'days'} / ${nights} ${nights === 1 ? 'night' : 'nights'}`;
    };
    const computeWakeDays = () => {
        if (!wakeDays) return;

        const wakeDate = getDateValue(wakeStart, wakeStartPicker);
        const serviceDate = getDateValue(wakeEnd, wakeEndPicker);
        const badge = document.getElementById('wake_duration_badge');

        if (!wakeDate || !serviceDate || serviceDate < wakeDate) {
            if (badge) badge.hidden = true;
            wakeDays.value = '';
            if (wakeDurationDisplay) wakeDurationDisplay.textContent = 'Auto-calculated';
            return;
        }

        const wakeOnly = new Date(wakeDate.getFullYear(), wakeDate.getMonth(), wakeDate.getDate());
        const serviceOnly = new Date(serviceDate.getFullYear(), serviceDate.getMonth(), serviceDate.getDate());
        const days = Math.min(365, Math.floor((serviceOnly - wakeOnly) / 86400000) + 1);
        wakeDays.value = String(days);
        if (wakeDurationDisplay) wakeDurationDisplay.textContent = `Wake Duration: ${viewingDurationLabel(days)}`;
        if (badge) badge.hidden = false;

    };

    const combineDateAndTime = (date, timeValue) => {
        if (!date || !timeValue) return null;
        const [hours, minutes] = String(timeValue).split(':').map(Number);
        if (!/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(String(timeValue))) return null;

        const combined = new Date(date.getFullYear(), date.getMonth(), date.getDate(), hours, minutes, 0, 0);
        return Number.isNaN(combined.getTime()) ? null : combined;
    };

    const positionIntakePicker = (panel, anchor, preferredWidth) => {
        const gap = 8;
        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = document.documentElement.clientHeight;
        const rect = anchor.getBoundingClientRect();
        const width = Math.min(preferredWidth, viewportWidth - gap * 2);
        panel.style.setProperty('position', 'fixed', 'important');
        panel.style.setProperty('width', `${width}px`, 'important');
        panel.style.setProperty('margin', '0', 'important');
        panel.style.setProperty('inset', 'auto', 'important');
        panel.style.setProperty('max-height', `${viewportHeight - gap * 2}px`, 'important');
        const below = viewportHeight - rect.bottom - gap * 2;
        const above = rect.top - gap * 2;
        const openAbove = panel.offsetHeight > below && above > below;
        panel.style.setProperty('max-height', `${Math.max(0, openAbove ? above : below)}px`, 'important');
        const top = openAbove ? rect.top - gap - panel.offsetHeight : rect.bottom + gap;
        panel.style.setProperty('top', `${top}px`, 'important');
        panel.style.setProperty('left', `${Math.max(gap, Math.min(rect.left, viewportWidth - width - gap))}px`, 'important');
    };

    const closeIntakePickerOnScroll = (anchor, panel, close) => {
        window.addEventListener('scroll', (event) => {
            const target = event.target;
            if (target instanceof Element && (panel.contains(target) || !target.contains(anchor))) return;
            close();
        }, { capture: true, passive: true });
        window.addEventListener('resize', close);
    };

    advisoryFields.forEach(({ trigger, panel, supportsPopover }) => {
        closeIntakePickerOnScroll(trigger, panel, () => {
            if (supportsPopover) {
                if (panel.matches(':popover-open')) panel.hidePopover();
            } else if (!panel.hidden) {
                panel.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    });

    const initScheduleTimePicker = (hiddenInput) => {
        if (!hiddenInput) return;

        const picker = document.querySelector(`[data-time-picker="${hiddenInput.id}"]`);
        const displayInput = document.querySelector(`[data-time-display-for="${hiddenInput.id}"]`);
        const toggleButton = document.querySelector(`[data-time-toggle-for="${hiddenInput.id}"]`);
        const popover = picker?.querySelector('[data-time-popover]');
        if (!picker || !displayInput || !toggleButton || !popover) return;

        const state = { hour: '', minute: '', period: '' };
        const pad = (value) => String(value).padStart(2, '0');
        const floating = typeof popover.showPopover === 'function';
        const periods = document.createElement('div');
        periods.className = 'schedule-time-periods';
        periods.setAttribute('role', 'group');
        periods.setAttribute('aria-label', `${displayInput.getAttribute('aria-label')} period`);
        const periodButtons = ['AM', 'PM'].map(period => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = period;
            button.setAttribute('aria-pressed', 'false');
            button.addEventListener('click', () => showPeriod(period));
            periods.append(button);
            return button;
        });
        toggleButton.setAttribute('aria-haspopup', 'dialog');
        toggleButton.setAttribute('aria-expanded', 'false');
        let presets;
        displayInput.readOnly = true;
        displayInput.placeholder = 'Select time';
        if (floating) popover.setAttribute('popover', 'manual');
        {
            presets = document.createElement('div');
            presets.className = 'intake-time-presets';
            presets.setAttribute('role', 'group');
            presets.setAttribute('aria-label', 'Half-hour time options');
            for (let hour = 0; hour < 24; hour++) {
                for (const minute of ['00', '30']) {
                    const value = `${pad(hour)}:${minute}`;
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.timePreset = value;
                    button.textContent = `${pad(hour % 12 || 12)}:${minute} ${hour < 12 ? 'AM' : 'PM'}`;
                    button.addEventListener('click', () => {
                        hiddenInput.value = value;
                        applyHiddenValueToPicker();
                        syncHiddenValue();
                        closePicker();
                        displayInput.focus({ preventScroll:true });
                    });
                    presets.append(button);
                }
            }
            presets.hidden = true;
            popover.replaceChildren(periods, presets);
        }

        const formatDisplay = () => {
            if (!state.hour || !state.minute || !state.period) return '';
            return `${state.hour}:${state.minute} ${state.period}`;
        };

        const closePicker = () => {
            if (floating && popover.matches(':popover-open')) popover.hidePopover();
            picker.classList.remove('is-open');
            displayInput.setAttribute('aria-expanded', 'false');
            toggleButton.setAttribute('aria-expanded', 'false');
            presets.hidden = true;
            periodButtons.forEach(button => button.setAttribute('aria-pressed', 'false'));
        };

        const positionPicker = () => {
            if (floating) {
                if (!popover.matches(':popover-open')) popover.showPopover();
                positionIntakePicker(popover, picker, 280);
            }
        };

        const openPicker = () => {
            if (picker.classList.contains('is-open')) return;
            document.querySelectorAll('.time-input-wrap.is-open').forEach((openWrap) => {
                openWrap.dispatchEvent(new Event('intake:close-time'));
            });
            picker.classList.add('is-open');
            displayInput.setAttribute('aria-expanded', 'true');
            toggleButton.setAttribute('aria-expanded', 'true');
            positionPicker();
            periodButtons[0].focus({ preventScroll: true });
        };

        const showPeriod = (period) => {
            presets.hidden = false;
            periodButtons.forEach(button => button.setAttribute('aria-pressed', String(button.textContent === period)));
            [...presets.children].forEach(button => {
                button.hidden = !button.textContent.endsWith(period);
                button.setAttribute('aria-pressed', String(button.dataset.timePreset === hiddenInput.value));
            });
            positionPicker();
            const selectedPreset = presets.querySelector('[aria-pressed="true"]:not([hidden])');
            presets.scrollTop = selectedPreset ? selectedPreset.offsetTop - presets.offsetTop : 0;
        };

        const syncHiddenValue = () => {
            if (!state.hour || !state.minute || !state.period) {
                hiddenInput.value = '';
                displayInput.value = '';
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }

            let hour24 = Number(state.hour) % 12;
            if (state.period === 'PM') hour24 += 12;

            hiddenInput.value = `${pad(hour24)}:${state.minute}`;
            displayInput.value = formatDisplay();
            hiddenInput.setCustomValidity('');
            displayInput.setCustomValidity('');
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const applyHiddenValueToPicker = () => {
            const [hourRaw, minuteRaw] = String(hiddenInput.value || '').slice(0, 5).split(':');
            const hour24 = Number(hourRaw);
            const minute = Number(minuteRaw);

            if (!Number.isFinite(hour24) || !Number.isFinite(minute)) {
                state.hour = '';
                state.minute = '';
                state.period = '';
                displayInput.value = '';
                return;
            }

            state.hour = pad(hour24 % 12 || 12);
            state.minute = pad(minute);
            state.period = hour24 >= 12 ? 'PM' : 'AM';
            displayInput.value = formatDisplay();
        };

        toggleButton.addEventListener('click', () => {
            if (picker.classList.contains('is-open')) closePicker();
            else openPicker();
        });
        displayInput.addEventListener('focus', () => {
            displayInput.setAttribute('aria-haspopup', 'dialog');
        });
        displayInput.addEventListener('click', () => openPicker());

        displayInput.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                openPicker();
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                openPicker();
            }
        });

        picker.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && picker.classList.contains('is-open')) {
                event.preventDefault();
                closePicker();
                displayInput.focus();
            }
        });
        picker.addEventListener('intake:close-time', closePicker);
        closeIntakePickerOnScroll(picker, popover, () => {
            if (picker.classList.contains('is-open')) closePicker();
        });

        applyHiddenValueToPicker();
        hiddenInput.addEventListener('change', () => {
            if (!picker.classList.contains('is-open')) applyHiddenValueToPicker();
        });
    };

    [wakeStartTime, wakeEndTime, funeralTime, intermentTime].forEach(initScheduleTimePicker);

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.time-input-wrap.is-open').forEach((picker) => {
            if (!picker.contains(event.target)) {
                picker.dispatchEvent(new Event('intake:close-time'));
            }
        });
    });

    const setFieldError = (el, errEl, message, altEl = null) => {
        if (!el || !errEl) return;

        const targets = [el];
        if (altEl) targets.push(altEl);

        if (message) {
            targets.forEach(t => t && t.classList.add('field-error'));
            targets.forEach(t => t?.closest?.('.form-select-wrap')?.classList.add('is-error'));
            targets.forEach(t => t && t.setAttribute('aria-invalid', 'true'));
            if (errEl.id) targets.forEach(target => {
                const descriptions = new Set((target.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
                descriptions.add(errEl.id);
                target.setAttribute('aria-describedby', [...descriptions].join(' '));
            });
            errEl.textContent = message;
            errEl.classList.remove('hidden');
        } else {
            targets.forEach(t => t && t.classList.remove('field-error'));
            targets.forEach(t => t?.closest?.('.form-select-wrap')?.classList.remove('is-error'));
            targets.forEach(t => t && t.removeAttribute('aria-invalid'));
            if (errEl.id) targets.forEach(target => {
                const descriptions = (target.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id && id !== errEl.id);
                if (descriptions.length) target.setAttribute('aria-describedby', descriptions.join(' '));
                else target.removeAttribute('aria-describedby');
            });
            errEl.textContent = '';
            errEl.classList.add('hidden');
        }
    };

    const validateDobDod = (mode = 'full') => {
        const todayEnd = new Date();
        todayEnd.setHours(23, 59, 59, 999);

        const birthRaw = getRawDateString(born, bornPicker);
        const deathRaw = getRawDateString(died, diedPicker);
        const birthDate = getDateValue(born, bornPicker);
        const deathDate = getDateValue(died, diedPicker);

        born?.setCustomValidity('');
        died?.setCustomValidity('');

        if (mode === 'full') {
            setFieldError(born, bornErr, '', bornPicker?.altInput);
            setFieldError(died, diedErr, '', diedPicker?.altInput);
        }

        if (born) {
            if (!birthRaw) {
                if (mode === 'full') born.setCustomValidity('Date of birth is required.');
            } else if (!birthDate) {
                born.setCustomValidity('Please enter a valid date of birth.');
            } else if (birthDate > todayEnd) {
                born.setCustomValidity('Date of birth cannot be in the future.');
            }
        }

        if (died) {
            if (!deathRaw) {
                if (mode === 'full') died.setCustomValidity('Date of death is required.');
            } else if (!deathDate) {
                died.setCustomValidity('Please enter a valid date of death.');
            } else if (deathDate > todayEnd) {
                died.setCustomValidity('Date of death cannot be in the future.');
            }
        }

        if (birthDate && deathDate && deathDate < birthDate) {
            died.setCustomValidity('Date of death cannot be earlier than date of birth.');
            if (!born.validationMessage) {
                born.setCustomValidity('Date of birth cannot be later than date of death.');
            }
        }

        if (birthRaw || mode === 'full') {
            setFieldError(born, bornErr, born?.validationMessage || '', bornPicker?.altInput);
        }
        if (deathRaw || mode === 'full') {
            setFieldError(died, diedErr, died?.validationMessage || '', diedPicker?.altInput);
        }
    };

    const validateWakeInterment = (mode = 'light') => {
        const fields = [
            [wakeStart, wakeStartTime, wakeStartPicker, wakeStartErr, 'Wake Start'],
            [wakeEnd, wakeEndTime, wakeEndPicker, wakeEndErr, 'Wake End'],
            [funeral, funeralTime, wakePicker, wakeErr, 'Funeral Ceremony'],
            [interment, intermentTime, interPicker, intermentErr, 'Interment'],
        ];
        const errors = new Map();
        const dates = fields.map(([field, , picker]) => getDateValue(field, picker));
        const times = fields.map(([, time], index) => combineDateAndTime(dates[index], time?.value));
        const [startDate, , ceremonyDate, intermentDate] = dates;
        const [start, end, ceremony, burial] = times;
        const deathDate = dateOnly(getDateValue(died, diedPicker));
        const requestDay = dateOnly(getDateValue(requestDate));
        const todayDate = dateOnly(new Date());

        fields.forEach(([field, time, picker, , label], index) => {
            const raw = getRawDateString(field, picker);
            const dateBeingEdited = document.activeElement === (picker?.altInput || field);
            if (!dates[index] && (mode === 'full' || (raw && !dateBeingEdited))) errors.set(field, `Select a valid ${label} date.`);
            if (!time?.value && mode === 'full') errors.set(time, `Select a ${label} time.`);
            else if (time?.value && !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(time.value)) errors.set(time, `Select a valid ${label} time.`);
            if (historicalIntake && dates[index] && todayDate && dateOnly(dates[index]) > todayDate) {
                errors.set(field, `${label} date cannot be in the future for a historical entry.`);
            }
            if (index !== 1 && dates[index] && deathDate && dateOnly(dates[index]) < deathDate) {
                errors.set(field, `${label} date must be on or after the date of death.`);
            }
        });
        if (!historicalIntake && startDate && requestDay && dateOnly(startDate) < requestDay) {
            errors.set(wakeStart, 'Wake start date cannot be before the request/recorded date.');
        }

        const checkOrder = (previousIndex, currentIndex, invalid, message) => {
            if (!invalid) return;
            const [field, time] = fields[currentIndex];
            const sameDay = sameDate(dates[previousIndex], dates[currentIndex]);
            errors.set(sameDay ? time : field, message);
        };
        checkOrder(0, 1, start && end && end <= start, sameDate(dates[0], dates[1])
            ? 'Wake End time must be later than Wake Start time.'
            : 'Wake End date cannot be before Wake Start date.');
        checkOrder(0, 2, start && ceremony && ceremony < start, sameDate(dates[0], dates[2])
            ? 'Funeral Ceremony time cannot be before Wake Start time.'
            : 'Funeral Ceremony date cannot be before Wake Start date.');
        checkOrder(1, 2, end && ceremony && ceremony < end, sameDate(dates[1], dates[2])
            ? 'Funeral Ceremony time cannot be before Wake End time.'
            : 'Funeral Ceremony date cannot be before Wake End date.');
        if (intermentDate && ceremonyDate && dateOnly(intermentDate) < dateOnly(ceremonyDate)) {
            errors.set(interment, 'Interment date cannot be before the Funeral Ceremony date.');
        } else {
            checkOrder(2, 3, ceremony && burial && burial <= ceremony, 'Interment time must be later than the Funeral Ceremony time.');
        }
        intermentWarning?.classList.toggle('hidden', !(intermentDate && ceremonyDate && dateOnly(intermentDate) > dateOnly(ceremonyDate)));

        fields.forEach(([field, time, picker, error], index) => {
            const dateMessage = errors.get(field) || '';
            const timeMessage = errors.get(time) || '';
            const timeDisplay = scheduleTimeDisplay(time);
            const serverDateError = field?.name
                ? f.querySelector(`[data-schedule-server-error="${field.name}"]`)
                : null;
            const serverTimeError = time?.name
                ? f.querySelector(`[data-schedule-server-error="${time.name}"]`)
                : null;
            if (dateMessage || dates[index] || mode === 'full') serverDateError?.remove();
            if (timeMessage || time?.value || mode === 'full') serverTimeError?.remove();
            field?.setCustomValidity(dateMessage);
            time?.setCustomValidity(timeMessage);
            if (time) time.dataset.scheduleError = timeMessage;
            setFieldError(field, error, dateMessage, picker?.altInput);
            setFieldError(time, error, timeMessage, timeDisplay);
            if (error) {
                error.textContent = dateMessage || timeMessage;
                error.classList.toggle('hidden', !error.textContent);
            }
        });
        const summaryReady = step === 3 && errors.size === 0 && times.every(Boolean);
        scheduleSummaryTrigger.hidden = !summaryReady;
        if (summaryReady) {
            scheduleSummaryDates.forEach((date, index) => { date.textContent = formatDateTime(times[index]); });
            scheduleSummaryPanel.querySelector('[data-wake-duration]').textContent = `Wake duration · ${viewingDurationLabel(Number(wakeDays.value))}`;
        } else {
            closeScheduleSummary();
        }
        return errors.size === 0;
    };

    const render = () => {
        if (restoringPickerState) return;
        computeWakeDays();
        validateDobDod('light');
        validateWakeInterment('light');
        syncAge();

        const t = totals();

        if (!isCustomSelected()) {
            const viewing = structuredInclusionsFor().find(item => item.service_type === 'home_viewing');
            const context = pkg()?.closest('.package-card')?.querySelector('[data-viewing-context]');
            if (viewing && context && isChapelViewing()) {
                context.textContent = 'Home Viewing coverage does not apply to Chapel Viewing.';
                context.dataset.extra = 'false';
            } else if (viewing && context) {
                const included = viewing.included_days;
                const actual = inclusiveWakeDays();
                const extra = included == null ? 0 : Math.max(actual - included, 0);
                const parts = [included == null ? 'Included days not configured' : `${included} ${Number(included) === 1 ? 'day' : 'days'} included`];
                if (actual) parts.push(`${actual} wake ${actual === 1 ? 'day' : 'days'}`);
                if (extra) {
                    parts.push(`${extra} extra ${extra === 1 ? 'day' : 'days'}`);
                    parts.push(viewing.price_per_extended_day == null ? 'Rate not configured'
                        : t.structured.viewing.amount > 0 ? `+₱${fmt(t.structured.viewing.amount)}` : 'No extra charge');
                }
                context.textContent = parts.join(' · ');
                context.dataset.extra = String(extra > 0);
            }
        }
        if (summaryPackage) summaryPackage.textContent = fmt(t.packagePrice);
        if (summaryAddOns) summaryAddOns.textContent = fmt(t.selectedAddOns);
        if (summaryPackageAdjustments) {
            summaryPackageAdjustments.innerHTML = [
                ...t.structured.includedAddOns,
                ...t.structured.customCharges,
                t.structured.chapel,
                { label: 'Home Viewing Extension', amount: t.structured.viewing.amount },
                { label: 'Casket Upgrade', amount: t.structured.casket },
                { label: 'Hearse Upgrade', amount: t.structured.hearseUpgrade },
                { label: 'Hearse Excess Distance', amount: t.structured.hearse.amount },
            ].filter(item => item.amount > 0).map(item => `
                <div class="flex items-center justify-between gap-2">
                    <span class="text-slate-500">${escapeHtml(item.label)}</span>
                    <span class="whitespace-nowrap font-bold text-slate-900">₱${fmt(item.amount)}</span>
                </div>
            `).join('');
        }
        if (selectedAddOnsTotal) selectedAddOnsTotal.textContent = fmt(t.selectedAddOns);
        if (summaryAdd) summaryAdd.textContent = fmt(t.additional);
        if (summarySubtotal) summarySubtotal.textContent = fmt(t.subtotal);
        if (summaryDiscountSource) summaryDiscountSource.textContent = t.disc.source;
        if (summaryDiscount) summaryDiscount.textContent = fmt(t.disc.amount);
        if (summaryTax) summaryTax.textContent = fmt(t.tax);
        if (summaryTotal) summaryTotal.textContent = fmt(t.total);
        if (summaryStatus) summaryStatus.textContent = t.status;
        if (summaryPaidAmount) summaryPaidAmount.textContent = fmt(t.paid);
        const paymentSummary = paymentMethodSummary();
        if (summaryPaymentMethod) summaryPaymentMethod.textContent = paymentSummary.method;
        if (summaryBankRow) summaryBankRow.classList.toggle('hidden', !paymentSummary.bank);
        if (summaryPaymentBank) summaryPaymentBank.textContent = paymentSummary.bank || '-';
        if (summaryAccountRow) summaryAccountRow.classList.toggle('hidden', !paymentSummary.account);
        if (summaryPaymentAccount) summaryPaymentAccount.textContent = paymentSummary.account || '-';
        if (summaryMobileRow) summaryMobileRow.classList.toggle('hidden', !paymentSummary.mobile);
        if (summaryPaymentMobile) summaryPaymentMobile.textContent = paymentSummary.mobile || '-';
        if (summaryChannelRow) summaryChannelRow.classList.toggle('hidden', !paymentSummary.channel);
        if (summaryChannelLabel) summaryChannelLabel.textContent = paymentSummary.channelLabel || 'Payment Channel';
        if (summaryPaymentChannel) summaryPaymentChannel.textContent = paymentSummary.channel || '-';
        if (summaryReferenceRow) summaryReferenceRow.classList.toggle('hidden', !paymentSummary.reference);
        if (summaryReferenceLabel) summaryReferenceLabel.textContent = paymentSummary.referenceLabel;
        if (summaryPaymentReference) {
            summaryPaymentReference.textContent = paymentSummary.reference || '-';
            summaryPaymentReference.classList.toggle('text-amber-600', !!paymentSummary.referenceInvalid);
            summaryPaymentReference.classList.toggle('text-slate-800', !paymentSummary.referenceInvalid);
        }
        if (summaryNotesRow) summaryNotesRow.classList.toggle('hidden', !paymentSummary.notes);
        if (summaryPaymentNotes) summaryPaymentNotes.textContent = paymentSummary.notes || '-';
        if (summaryBalance) summaryBalance.textContent = fmt(t.balance);

        if (taxAmountDisplay) taxAmountDisplay.value = `PHP ${fmt(t.tax)}`;
        if (taxRate) taxRate.value = t.rate.toFixed(2);
        if (autoDiscountType) autoDiscountType.value = t.disc.type;
        if (autoDiscountAmount) autoDiscountAmount.value = `PHP ${fmt(t.disc.amount)}`;
        if (discountHelpSecondary) discountHelpSecondary.textContent = t.disc.message;

        const requiresReplacement = !isCustomSelected() && includedCasketFor()?.available === false;
        const replacementToggle = casketModeRadios.find(radio => radio.value === 'replacement');
        if (replacementToggle) { if (requiresReplacement) replacementToggle.checked = true; replacementToggle.disabled = requiresReplacement; }
        if (replacementCasket) replacementCasket.required = requiresReplacement;
        const replacementMode = requiresReplacement || casketModeRadios.find((radio) => radio.checked)?.value === 'replacement';
        if (replacementCasket && !replacementMode && replacementCasket.value) {
            replacementCasket.value = '';
        }
        const includedCasket = includedCasketFor();
        if (includedCasketSummary) {
            const selected = selectedReplacementCasket();
            const casket = selected || includedCasket;
            includedCasketSummary.textContent = [casket?.name || 'No included casket configured', casket?.material].filter(Boolean).join(' - ');
            const status = document.getElementById('casket-inclusion-status');
            status.textContent = selected ? (t.structured.casket > 0 ? 'Upgrade top-up' : 'Replacement included') : includedAssetStatus(includedCasket);
            status.dataset.unavailable = String(!selected && includedAssetStatus(includedCasket) !== 'Included');
            document.querySelector('[data-upgrade-trigger="casket"]').textContent = selected ? 'Change' : requiresReplacement ? 'Choose replacement' : 'Upgrade';
            document.querySelector('[data-upgrade-charge="casket"]').hidden = !selected;
            document.querySelector('[data-upgrade-reset="casket"]').classList.toggle('hidden', !selected || requiresReplacement);
        }
        if (casketAdjustmentAmount) casketAdjustmentAmount.textContent = fmt(t.structured.casket);
        const hearseUpgradePreview = document.getElementById('hearse_upgrade_preview');
        if (hearseUpgradePreview) {
            const included = structuredInclusionsFor().find(row => row.service_type === 'hearse')?.hearse;
            const selected = hearseCatalogSelect?.selectedOptions[0];
            const changing = hearseCatalogSelect?.value && String(included?.id) !== hearseCatalogSelect.value;
            const missingValue = !isCustomSelected() && changing && (included?.reference_value == null || selected?.dataset.referenceValue === '');
            hearseCatalogSelect?.setCustomValidity(missingValue ? 'Configure reference values for both hearses before selecting an upgrade.' : '');
            document.getElementById('inherited_hearse_summary').textContent = changing ? selected.dataset.name : included?.name || 'No included hearse configured';
            const status = document.getElementById('hearse-inclusion-status');
            status.textContent = changing ? (t.structured.hearseUpgrade > 0 ? 'Upgrade top-up' : 'Replacement included') : includedAssetStatus(included);
            const unavailable = !!included && includedAssetStatus(included) !== 'Included';
            status.dataset.unavailable = String(!changing && includedAssetStatus(included) !== 'Included');
            document.querySelector('[data-upgrade-trigger="hearse"]').textContent = changing ? 'Change' : unavailable ? 'Choose replacement' : 'Upgrade';
            document.querySelector('[data-upgrade-charge="hearse"]').hidden = !changing;
            document.querySelector('[data-upgrade-reset="hearse"]').classList.toggle('hidden', !changing || !included);
            document.getElementById('hearse-inclusion-charge').textContent = fmt(t.structured.hearseUpgrade);
            hearseUpgradePreview.hidden = isCustomSelected() || (!missingValue && (!unavailable || changing));
            hearseUpgradePreview.textContent = isCustomSelected() ? 'Covered by the agreed all-in price.' : unavailable && !changing
                ? `Included hearse is ${includedAssetStatus(included).toLowerCase()}. Choose an available replacement or ask the System Admin to update the package.`
                : missingValue ? 'Reference value not configured. Ask the System Admin to configure both hearses.' : '';
        }


        if (casketUpgradePreview) {
            const included = includedCasketFor();
            const selected = selectedReplacementCasket();
            casketUpgradePreview.hidden = !requiresReplacement && (!selected || (num(included.reference_value) > 0 && selected.referenceValue > 0));
            if (isCustomSelected()) {
                casketUpgradePreview.textContent = 'Custom packages do not calculate casket upgrades yet.';
            } else if (requiresReplacement && !selected) {
                casketUpgradePreview.textContent = included?.active === false
                    ? 'Included casket is archived. Ask the System Admin to update the package, choose another package, or save as Draft.'
                    : 'Included casket is unavailable. Choose an available replacement before saving.';
            } else if (!selected) {
                casketUpgradePreview.textContent = included?.name ? `${included.name} is included in the package price.` : 'Included casket will be used.';
            } else if (num(included.reference_value) <= 0) {
                casketUpgradePreview.textContent = 'Included casket reference value is not configured.';
            } else if (selected.referenceValue <= 0) {
                casketUpgradePreview.textContent = 'Selected casket reference value is not configured.';
            } else {
                casketUpgradePreview.textContent = `Upgrade charge: PHP ${fmt(t.structured.casket)}.`;
            }
        }

        const syncDistanceCard = (kind, charge, toggle, input, wrap, warning, includedEl, excessEl, rateEl, amountEl, previewEl) => {
            if (includedEl) includedEl.textContent = fmt(charge.included);
            if (excessEl) excessEl.textContent = fmt(charge.excess);
            if (rateEl) rateEl.textContent = fmt(charge.rate);
            if (amountEl) amountEl.textContent = fmt(charge.amount);

            const disabled = isCustomSelected() || !charge.configured;
            if (toggle) {
                toggle.disabled = disabled;
                toggle.classList.toggle('cursor-not-allowed', disabled);
                if (disabled) toggle.checked = false;
            }
            wrap?.classList.toggle('hidden', disabled || !toggle?.checked);
            if (input) {
                input.disabled = disabled || !toggle?.checked;
                input.classList.toggle('cursor-not-allowed', disabled);
                input.classList.toggle('bg-slate-100', disabled);
                if (disabled || !toggle?.checked) input.setCustomValidity('');
            }
            warning?.classList.toggle('hidden', !(!isCustomSelected() && !charge.configured));

            if (!previewEl) return;
            if (isCustomSelected()) {
                previewEl.textContent = 'Custom packages do not use automatic kilometer limits yet.';
            } else if (!charge.configured) {
                previewEl.textContent = `${kind} excess charging is unavailable until Admin configures the package coverage and rate.`;
            } else if (!toggle?.checked) {
                previewEl.textContent = `${kind} is within included package coverage. No additional charge.`;
            } else {
                previewEl.textContent = `Excess ${fmt(charge.excess)} km x PHP ${fmt(charge.rate)} = PHP ${fmt(charge.amount)}.`;
            }
        };

        syncDistanceCard('Body Retrieval', t.structured.retrieval, applyRetrievalExcess, retrievalExcessInput, retrievalExcessWrap, retrievalConfigWarning, retrievalIncludedKm, retrievalExcessKm, retrievalRate, retrievalChargeAmount, retrievalChargePreview);
        syncDistanceCard('Hearse Service', t.structured.hearse, applyHearseExcess, hearseExcessInput, hearseExcessWrap, hearseConfigWarning, hearseIncludedKm, hearseExcessKm, hearseRate, hearseChargeAmount, hearseChargePreview);

        const syncDayCard = (kind, charge, warning, includedEl, actualEl, extendedEl, rateEl, amountEl, previewEl) => {
            if (includedEl) includedEl.textContent = String(Math.round(num(charge.included)));
            if (actualEl) actualEl.textContent = wakeDurationLabel(charge.actual);
            if (extendedEl) extendedEl.textContent = String(Math.round(num(charge.excess)));
            if (rateEl) rateEl.textContent = fmt(charge.rate);
            if (amountEl) amountEl.textContent = fmt(charge.amount);

            warning?.classList.toggle('hidden', !(!isCustomSelected() && !charge.configured));

            if (!previewEl) return;
            if (isCustomSelected()) {
                previewEl.textContent = 'Custom packages do not use automatic extended-day charges yet.';
            } else if (!charge.configured) {
                previewEl.textContent = `${kind} day charging is unavailable until Admin configures the package included days and rate.`;
            } else {
                previewEl.textContent = `Extended ${Math.round(num(charge.excess))} day(s) x PHP ${fmt(charge.rate)} = PHP ${fmt(charge.amount)}.`;
            }
        };

        syncDayCard('Embalming', t.structured.embalming, embalmingConfigWarning, embalmingIncludedDays, embalmingActualDays, embalmingExtendedDays, embalmingRate, embalmingChargeAmount, embalmingChargePreview);
        syncDayCard('Home Viewing', t.structured.viewing, viewingConfigWarning, viewingIncludedDays, viewingActualDays, viewingExtendedDays, viewingRate, viewingChargeAmount, viewingChargePreview);

        if (paymentStatusPreview) paymentStatusPreview.textContent = t.status;
        if (paymentPaidPreview) paymentPaidPreview.textContent = fmt(t.paid);
        if (paymentBalancePreview) paymentBalancePreview.textContent = fmt(t.balance);
        if (paymentAmountFormatted) paymentAmountFormatted.textContent = `₱${fmt(num(amountPaid?.value))}`;

        if (payHint) {
            payHint.textContent = payType() === 'FULL'
                ? 'Full payment must equal the total amount due.'
                : payType() === 'PARTIAL'
                    ? 'Partial payment must be less than the total amount due.'
                    : (mark?.type === 'checkbox' && !payNow()
                        ? 'Leave payment unchecked if the client has not paid yet.'
                        : 'Select full or partial payment and enter the amount received.');
        }

        renderReview();
    };

    const clearMessages = () => {
        branchError?.classList.add('hidden');
        packageError?.classList.add('hidden');
        paymentTypeError?.classList.add('hidden');
        intermentErr?.classList.add('hidden');
        [...f.querySelectorAll('input, select, textarea')].forEach(clearFieldMessage);
    };

    const validatePanelFields = (panel) => {
        let valid = true;
        const flatpickrDateFields = ['born', 'died', 'wake_start_date', 'funeral_service_at', 'interment_at'];

        for (const field of [...panel.querySelectorAll('input, select, textarea')].filter((element) =>
            element.type !== 'hidden' && !element.disabled && !element.dataset.intakeDateAlternate
            && !element.closest('.schedule-stack, [data-coverage-hidden]'))) {
            const isFlatpickrDate = flatpickrDateFields.includes(field.id);

            // Do NOT clear date custom validity here
            if (!isFlatpickrDate) {
                clearFieldMessage(field);
            }

            const value = typeof field.value === 'string' ? field.value.trim() : field.value;

            if (field.required && !value && field.type !== 'checkbox' && field.type !== 'radio') {
                if (isFlatpickrDate) {
                    continue;
                }

                const message = field.tagName === 'SELECT' || field.type === 'date' || field.type === 'datetime-local'
                    ? `Please select ${labelFor(field).toLowerCase()}.`
                    : `Please enter ${labelFor(field).toLowerCase()}.`;

                field.setCustomValidity(message);
                showFieldError(field, message);
                valid = false;
                continue;
            }

            if (isFlatpickrDate) {
                continue;
            }

            if (!field.checkValidity()) {
                showFieldError(field, field.validationMessage);
                valid = false;
            }
        }

        return valid;
    };

    const validateStep = (targetStep, scheduleMode) => {
        clearMessages();
        let valid = true;

        if (shouldLockOtherBranchIntake()) {
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Before you continue, please select a branch so we can record the case correctly.';
            if (!collectingValidation) showBranchToast('Before you continue, please select a branch so we can record the case correctly.');
            valid = false;
        }

        const panel = panels.find((element) => Number(element.dataset.step) === targetStep);
        if (!panel) return true;

        valid = validatePanelFields(panel) && valid;
        if (targetStep === 3 && customPkgRadio?.checked && prefIncl && chapelPackageIsEligible(customPkgRadio)) {
            syncPreferredPackage();
            const includedItems = prefIncl.value.split(/\r\n|\r|\n/u).map((item) => item.trim()).filter(Boolean);
            if (includedItems.length === 0) {
                const message = 'Select at least one included service.';
                const other = document.querySelector('.custom-service-check');
                showFieldError(other, message);
                valid = false;
            }
        }
        if (targetStep === 1) valid = validateClientInformationStep() && valid;
        if (targetStep === 2) valid = validateDeceasedInformationStep() && valid;
        if (targetStep === 2) validateDobDod('full');
        if (targetStep === 4) {
            for (const row of document.querySelectorAll('[data-additional-service-row]')) {
                const description = row.querySelector('[data-additional-description]');
                const amount = row.querySelector('[data-additional-amount]');
                const hasDescription = !!description?.value?.trim();
                const hasAmount = num(amount?.value) > 0;

                if (hasAmount && !hasDescription) {
                    valid = showFieldError(description, 'Description is required for this additional charge.') && valid;
                }

                if (hasDescription && !hasAmount) {
                    valid = showFieldError(amount, 'Enter an amount greater than zero for this line.') && valid;
                }
            }
        }

        if (targetStep === 1 && (!branch.value || (isOtherEntryMode && !isBranchSelectedForOtherMode()))) {
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Before you continue, please select a branch so we can record the case correctly.';
            valid = false;
        }

        if (targetStep === 2) {
            const birthRaw = getRawDateString(born, bornPicker);
            const deathRaw = getRawDateString(died, diedPicker);
            const birthDate = getDateValue(born, bornPicker);
            const deathDate = getDateValue(died, diedPicker);
            const todayEnd = new Date();
            todayEnd.setHours(23, 59, 59, 999);

            // Clear old inline messages first
            setFieldError(born, bornErr, '', bornPicker?.altInput);
            setFieldError(died, diedErr, '', diedPicker?.altInput);
            born?.setCustomValidity('');
            died?.setCustomValidity('');

            if (!birthRaw) {
                born.setCustomValidity('Date of birth is required.');
                setFieldError(born, bornErr, 'Date of birth is required.', bornPicker?.altInput);

                valid = false;
            }

            if (!deathRaw) {
                died.setCustomValidity('Date of death is required.');
                setFieldError(died, diedErr, 'Date of death is required.', diedPicker?.altInput);

                valid = false;
            }

            if (birthRaw && !birthDate) {
                born.setCustomValidity('Please enter a valid date of birth.');
                setFieldError(born, bornErr, 'Please enter a valid date of birth.', bornPicker?.altInput);

                valid = false;
            }

            if (deathRaw && !deathDate) {
                died.setCustomValidity('Please enter a valid date of death.');
                setFieldError(died, diedErr, 'Please enter a valid date of death.', diedPicker?.altInput);

                valid = false;
            }

            if (birthDate > todayEnd) {
                born.setCustomValidity('Date of birth cannot be in the future.');
                setFieldError(born, bornErr, 'Date of birth cannot be in the future.', bornPicker?.altInput);

                valid = false;
            }

            if (deathDate > todayEnd) {
                died.setCustomValidity('Date of death cannot be in the future.');
                setFieldError(died, diedErr, 'Date of death cannot be in the future.', diedPicker?.altInput);

                valid = false;
            }

            if (birthDate && deathDate && deathDate < birthDate) {
                died.setCustomValidity('Date of death cannot be earlier than date of birth.');
                setFieldError(died, diedErr, 'Date of death cannot be earlier than date of birth.', diedPicker?.altInput);

                valid = false;
            }

            const years = Number.parseInt(age?.value ?? '', 10);
            if (senior?.value === '1' && Number.isFinite(years) && years < 60) {
                senior.setCustomValidity('Senior Citizen can only be set to Yes when computed age is at least 60.');
                showFieldError(senior, senior.validationMessage);
                valid = false;
            }
        }
        const packageSelectionReady = !isChapelViewing() || chapelCoverage() === 'included'
            || (chapelCoverage() === 'separate' && configuredChapelFor(chapelRate.value));
        if (targetStep === 3 && packageSelectionReady && (!pkg() || !chapelPackageIsEligible(pkg()))) {
            packageError.textContent = isChapelViewing()
                ? (chapelCoverage() === 'included' ? 'Choose a package with chapel coverage at this branch.' : 'Choose a package without chapel coverage at this branch.')
                : 'Select a service package or choose Custom Arrangement before continuing.';
            packageError.classList.remove('hidden');
            valid = false;
        }

        if (targetStep === 3) {
            const invalidAddon = includedAddOnRows.map(row => row.querySelector('input'))
                .find(input => !input.disabled && !input.validity.valid);
            if (invalidAddon) {
                showFieldError(invalidAddon, invalidAddon.validationMessage);
                valid = false;
            }
            if (!viewingType.value) valid = showFieldError(viewingType, 'Select a viewing type.') && valid;
            if (!isChapelViewing() && !viewingLocation.disabled) valid = validateAddressField(viewingLocation) && valid;
            if (isChapelViewing()) {
                const chapel = chapelViewingCharge();
                if (!chapelCoverage()) {
                    chapelCoverageError.textContent = 'Choose a package with a chapel or select a branch chapel separately.';
                    chapelCoverageError.hidden = false;
                    chapelCoverageChoices[0].setAttribute('aria-invalid', 'true');
                    valid = false;
                } else if (chapelCoverage() === 'separate' && !configuredChapelFor(chapelRate.value)) {
                    valid = showFieldError(chapelRate, 'Select a chapel for this branch.') && valid;
                } else if (chapel.blocked || chapelScheduleBox.dataset.state !== 'available') {
                    valid = false;
                }
            }
            valid = validateAddressField(fieldByName('place_of_cemetery')) && valid;
            valid = validateWakeInterment(scheduleMode) && valid;
        }

        if (targetStep === 4 && isOtherEntryMode && !payMethodRadios.some((radio) => radio.checked)) {
            document.getElementById('payment_method_error')?.classList.remove('hidden');


            valid = false;
        }

        if (targetStep === 4 && payNow()) {
            const t = totals();

            if (!payType()) {
                paymentTypeError?.classList.remove('hidden');
                valid = false;
            }

            if (!amountPaid?.value) {
                amountPaid.setCustomValidity('Please enter amount paid.');
                showFieldError(amountPaid, amountPaid.validationMessage);
                valid = false;
            }

            if (!paidAt?.value) {
                paidAt.setCustomValidity('Please select the actual payment received date and time.');
                showFieldError(paidAt, paidAt.validationMessage);
                valid = false;
            }

            if (paidAt?.value) {
                const currentTime = new Date();
                paidAt.max = new Date(currentTime.getTime() - currentTime.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
                paidAt.setCustomValidity(new Date(paidAt.value) > currentTime ? 'Payment received date/time cannot be in the future.' : '');
                if (!paidAt.checkValidity()) { showFieldError(paidAt, paidAt.validationMessage); valid = false; }
            }

            if (!validatePaymentMethodDetails()) {
                valid = false;
            }

            if (amountPaid?.value && t.paid > 0 && payType() === 'FULL' && t.paid < t.total) {
                amountPaid.setCustomValidity('Full payment must match the total amount due.');
                showFieldError(amountPaid, amountPaid.validationMessage);
                valid = false;
            }

            if (amountPaid?.value && t.paid > 0 && payType() === 'PARTIAL' && t.paid >= t.total) {
                amountPaid.setCustomValidity('Partial payment must be less than the total amount due.');
                showFieldError(amountPaid, amountPaid.validationMessage);
                valid = false;
            }
        }

        if (targetStep === 5) {
            const confirmBox = document.getElementById('confirm_review');
            if (confirmBox && !confirmBox.checked) {
                confirmBox.setCustomValidity('Please confirm that the information is correct.');
                showFieldError(confirmBox, confirmBox.validationMessage);
                valid = false;
            }
        }

        return valid;
    };

    const attemptedSteps = new Set();
    const validationTarget = field => {
        if (!field) return null;
        if (field === regularPackageChoice && regularPackagePicker) return regularPackagePicker.trigger;
        if (chapelCoverageChoices.includes(field)) return field;
        if (field.id === 'custom_casket_catalog_id') return document.querySelector('[data-custom-asset-trigger="casket"]');
        if (field === hearseCatalogSelect && isCustomSelected()) return document.querySelector('[data-custom-asset-trigger="hearse"]');
        if (field === replacementCasket) return document.querySelector('[data-upgrade-trigger="casket"]');
        if (field === hearseCatalogSelect && !isCustomSelected()) return document.querySelector('[data-upgrade-trigger="hearse"]');
        const inclusionDialog = field.closest('.custom-inclusion-dialog');
        if (inclusionDialog && !inclusionDialog.open) return field.closest('.custom-inclusion-editor').querySelector('.custom-inclusion-edit-label');
        const input = scheduleTimeDisplay(field) || field._flatpickr?.altInput || field;
        if (getComputedStyle(input).display !== 'none' && !['hidden', 'radio', 'checkbox'].includes(input.type)) return input;
        const label = field.closest('label') || (field.id && f.querySelector(`label[for="${field.id}"]`));
        const target = label || (field === branch ? branchBtns[0] : input);
        if (target) target.tabIndex = -1;
        return target;
    };
    const collectValidationErrors = (panel) => {
        const errors = [];
        panel.querySelectorAll('input,select,textarea').forEach(field => {
            if (field.disabled || field.dataset.intakeDateAlternate || field.closest('[data-coverage-hidden]')
                || (field.type === 'hidden' && !field._flatpickr && !scheduleTimeDisplay(field))) return;
            const inlineError = fieldErrorElement(field, false);
            if (['wake_start_error', 'wake_end_error', 'funeral_service_at_error', 'interment_at_error'].includes(inlineError?.id)
                && field.getAttribute('aria-invalid') !== 'true') return;
            const message = (field.validity?.customError && scheduleTimeDisplay(field) ? field.dataset.scheduleError : field.validationMessage) || (inlineError && !inlineError.classList.contains('hidden') ? inlineError.textContent.trim() : '');
            if (!message) return;
            if (field.type === 'radio' && errors.some(error => error.name === field.name)) return;
            if (field.name === 'package_id' && !packageError?.classList.contains('hidden')) return;
            const target = validationTarget(field);
            if (errors.some(error => error.message === message && error.target === target)) return;
            showFieldError(field, message);
            errors.push({ target, message, name: field.name });
        });
        ['chapel_coverage_error', 'payment_method_error', 'payment_type_error', 'package_error', 'branch_error'].forEach(id => {
            const error = panel.querySelector('#' + id);
            if (!error || error.hidden || error.classList.contains('hidden')) return;
            const target = id === 'chapel_coverage_error' ? chapelCoverageChoices[0]
                : id === 'payment_method_error' ? payMethodRadios[0]
                : id === 'payment_type_error' ? payTypeRadios[0]
                : id === 'package_error' ? (regularPackageArea?.classList.contains('hidden') ? packageModeOptions[0]
                    : regularPackageChoice.disabled ? chapelCoverageChoices.find(choice => choice.checked) : regularPackageChoice) : branch;
            if (target) errors.push({ target: validationTarget(target), message: error.textContent.trim() });
        });
        if (panel.contains(chapelScheduleBox) && !chapelScheduleBox.hidden && chapelScheduleBox.dataset.state !== 'available') {
            const status = chapelScheduleBox.querySelector('[data-chapel-check-status]');
            errors.push({ target: status, message: status.textContent });
        }
        return errors;
    };
    const validate = (targetStep, focus = true) => {
        attemptedSteps.add(targetStep);
        collectingValidation = true;
        let valid;
        let errors;
        try {
            valid = validateStep(targetStep, focus ? 'full' : 'light');
            const panel = panels.find(element => Number(element.dataset.step) === targetStep);
            errors = panel ? collectValidationErrors(panel) : [];
        } finally {
            collectingValidation = false;
        }
        if (!valid && focus && errors.length) {
            errors[0].target.scrollIntoView({ block: 'center', behavior: 'smooth' });
            errors[0].target.focus({ preventScroll: true });
        }
        return valid && errors.length === 0;
    };
    f.addEventListener('focusout', event => {
        if (event.relatedTarget?.closest('button,a')) return;
        const field = event.target;
        const panel = field.closest('.wizard-panel');
        if (!panel || !field.matches('input,select,textarea') || field.disabled || field.readOnly || field.closest('[data-existing-client-picker]')) return;
        if (field.closest('.schedule-stack')) return;
        const stepNumber = Number(panel.dataset.step);
        if (attemptedSteps.has(stepNumber)) { validate(stepNumber, false); return; }
        if (!String(field.value || '').trim()) return;
        clearFieldMessage(field);
        let message = '';
        if (field.name?.match(/_(first|middle|last)_name$/) && !isValidName(field.value)) message = 'Name must contain letters only.';
        else if (field.name === 'client_contact_number' && !isValidPhilippineMobile(field.value)) message = 'Please enter a valid Philippine mobile number.';
        else if (field.name?.endsWith('_address') || ['wake_location', 'place_of_cemetery'].includes(field.name)) message = placeMessage(field, normalizeText(field.value));
        else if (!field.checkValidity()) message = field.validationMessage;
        if (message) {
            collectingValidation = true;
            showFieldError(field, message);
            collectingValidation = false;
        }
    });
    let validationRefresh;
    const refreshAttemptedValidation = event => {
        if (event.target.closest('.schedule-stack')) return;
        const panel = event.target.closest('.wizard-panel');
        if (!panel || !attemptedSteps.has(Number(panel.dataset.step))) return;
        clearTimeout(validationRefresh);
        validationRefresh = setTimeout(() => validate(Number(panel.dataset.step), false), 250);
    };
    f.addEventListener('input', refreshAttemptedValidation);
    f.addEventListener('change', refreshAttemptedValidation);

    const scrollToStepTop = (smooth = false) => {
        if (isIntakeModal) {
            formContent?.scrollTo({ top: 0, behavior: smooth && !window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'smooth' : 'auto' });
            return;
        }
        const anchor = wizardSteps || formContent;
        if (!anchor) return;

        const scroller = [anchor.closest('.page-content'), anchor.closest('.main-area')]
            .find(element => element && /(auto|scroll)/.test(getComputedStyle(element).overflowY));
        if (scroller) {
            const scrollerRect = scroller.getBoundingClientRect();
            const anchorRect = anchor.getBoundingClientRect();
            const scrollTop = Math.max(scroller.scrollTop + (anchorRect.top - scrollerRect.top) - 8, 0);

            scroller.scrollTo({
                top: scrollTop,
                behavior: smooth && !window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'smooth' : 'auto'
            });
            return;
        }

        const topbarHeight = document.querySelector('.topbar')?.getBoundingClientRect?.().height || 0;
        const anchorTop = anchor.getBoundingClientRect().top + window.scrollY;
        const scrollTop = Math.max(anchorTop - topbarHeight - 10, 0);
        window.scrollTo({ top: scrollTop, behavior: smooth && !window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'smooth' : 'auto' });
    };

    const syncProgressVisibility = () => {
        tabs.forEach((tab) => {
            const tabStep = Number(tab.dataset.step);
            const locked = tabStep > maxUnlockedStep;
            tab.classList.toggle('is-locked', locked);
            tab.setAttribute('aria-disabled', String(locked));
        });
    };

    const go = (targetStep, options = {}) => {
        const { scroll = true, smooth = true } = options;
        const previousStep = step;

        if (shouldLockOtherBranchIntake() && targetStep > 1) {
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Please select a branch first before encoding.';
            showBranchToast('Select Branch 2 or Branch 3 before entering case details.');
            maxUnlockedStep = 1;
            targetStep = 1;
        }

        targetStep = Math.min(targetStep, maxUnlockedStep);
        if (targetStep > 3 && chapelViewingCharge().blocked) targetStep = 3;
        step = Math.max(1, Math.min(totalSteps, targetStep));

        panels.forEach((panel) => panel.classList.toggle('hidden', Number(panel.dataset.step) !== step));
        if (step === 2) syncSurnameCopy();
        if (step === 3) validateWakeInterment('light');
        else closeScheduleSummary();

        tabs.forEach((tab) => {
            const tabStep = Number(tab.dataset.step);
            const active = tabStep === step;
            const completed = tabStep < step;

            tab.classList.toggle('active-step', active);
            tab.classList.toggle('completed-step', completed);
            if (active) tab.setAttribute('aria-current', 'step');
            else tab.removeAttribute('aria-current');
        });
        syncProgressVisibility();

        // Keep the current step visible inside the horizontal mobile progress rail.
        const activeTab = tabs.find(tab => Number(tab.dataset.step) === step);
        if (wizardSteps && activeTab) {
            const railRect = wizardSteps.getBoundingClientRect();
            const tabRect = activeTab.getBoundingClientRect();
            if (tabRect.left < railRect.left || tabRect.right > railRect.right) {
                wizardSteps.scrollTo({ left: wizardSteps.scrollLeft + tabRect.left - railRect.left - 16, behavior: 'auto' });
            }
        }

        if (prev) prev.disabled = step === 1;
        next?.classList.toggle('hidden', step === totalSteps);
        save?.classList.toggle('hidden', step !== totalSteps);

        if (next) {
            next.innerHTML = step === totalSteps - 1
                ? 'Review <i class="bi bi-chevron-right text-[10px] ml-1"></i>'
                : 'Continue <i class="bi bi-chevron-right text-[10px] ml-1"></i>';
        }

        if (step === totalSteps) renderReview();
        else updateScheduleAdvisory();
        chapelViewingCharge();
        if (scroll && step !== previousStep) scrollToStepTop(smooth);

        // Update progress rail
        const progressFill = document.getElementById('intakeProgressFill');
        if (progressFill) {
            progressFill.style.width = `${(step / totalSteps) * 100}%`;
        }
    };

    const syncBranch = (showNotice = false) => {
        syncViewingLocation();
        branchBtns.forEach((button) => {
            const active = String(button.dataset.branchId) === String(branch.value);

            button.classList.toggle('bg-slate-900', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('shadow-sm', active);

            button.classList.toggle('bg-white', !active);
            button.classList.toggle('text-slate-600', !active);
            button.classList.toggle('border', !active);
            button.classList.toggle('border-slate-200', !active);
        });

        if (nextCode) nextCode.textContent = nextMap[String(branch.value)] || defCode;

        reporterBox?.classList.toggle('hidden', !isOtherEntryMode);
        if (reporterName) reporterName.required = isOtherEntryMode;
        if (reportedAt) reportedAt.required = isOtherEntryMode;

        syncBranchUiLock();

        if (showNotice) {
            showBranchToast(branchPromptMessage());
        }
    };

    const historicalBranchSelector = document.getElementById('historical_branch_selector');
    historicalBranchSelector?.addEventListener('change', () => {
        branch.value = historicalBranchSelector.value;
        document.querySelector('[data-existing-client-clear]')?.click();
        chapelRate.value = '';
        chapelCoverageChoices.forEach(choice => { choice.checked = false; });
        syncBranch(true);
        syncPackageModeFromSelection();
        syncControls();
        updateScheduleAdvisory();
    });

    viewingType.addEventListener('change', event => {
        chapelRate.setCustomValidity('');
        setFieldError(chapelRate, fieldErrorElement(chapelRate, false), '');
        viewingType.setCustomValidity('');
        setFieldError(viewingType, fieldErrorElement(viewingType, false), '');
        viewingLocation.setCustomValidity('');
        setFieldError(viewingLocation, fieldErrorElement(viewingLocation, false), '');
        if (!isChapelViewing()) {
            if (event.isTrusted) viewingLocation.value = homeViewingAddress;
            else homeViewingAddress = viewingLocation.value;
        }
        chapelCoverageError.hidden = true;
        syncViewingLocation(event.isTrusted);
        renderPkg(false);
        syncPreferredPackage();
        render();
    });
    viewingLocation.addEventListener('input', () => { if (!isChapelViewing()) homeViewingAddress = viewingLocation.value; });
    chapelCoverageChoices.forEach(choice => choice.addEventListener('change', () => {
        chapelCoverageError.hidden = true;
        chapelCoverageChoices[0].removeAttribute('aria-invalid');
        chapelRate.setCustomValidity('');
        setFieldError(chapelRate, fieldErrorElement(chapelRate, false), '');
        if (previousIncludedChapel && chapelCoverage() !== 'included') {
            previousIncludedChapel = '';
            chapelRate.value = '';
        }
        syncPackageModeFromSelection();
        renderPkg(false);
        render();
    }));
    chapelSaveDraft.addEventListener('click', () => f.requestSubmit(document.getElementById('saveIntakeDraft')));
    const focusChapelField = field => {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        field.scrollIntoView({ block: 'center', behavior: reduceMotion ? 'instant' : 'smooth' });
        field.focus({ preventScroll: true });
    };
    chapelScheduleBox.querySelector('[data-chapel-edit-wake]').addEventListener('click', () => {
        focusChapelField(validationTarget(wakeStart));
    });
    f.addEventListener('click', event => {
        const option = event.target.closest('[data-alternative-type]');
        if (!option) return;
        const value = option.dataset.alternativeValue;
        if (option.dataset.alternativeType === 'package') {
            regularPackageChoice.value = value;
            regularPackageChoice.dispatchEvent(new Event('change', { bubbles: true }));
            focusChapelField(validationTarget(regularPackageChoice));
            return;
        }
        if (!configuredChapelFor(value)) return;
        const separate = chapelCoverageChoices.find(choice => choice.value === 'separate');
        if (!separate.checked) {
            separate.checked = true;
            separate.dispatchEvent(new Event('change', { bubbles: true }));
        }
        chapelRate.value = value;
        chapelRate.dispatchEvent(new Event('change', { bubbles: true }));
        focusChapelField(chapelRate);
    });
    chapelScheduleBox.querySelector('[data-chapel-change-viewing]').addEventListener('click', () => focusChapelField(viewingType));
    chapelScheduleBox.querySelector('[data-chapel-conflict-draft]').addEventListener('click', () => {
        f.requestSubmit(document.getElementById('saveIntakeDraft'));
    });
    chapelRate.addEventListener('change', () => {
        chapelRate.setCustomValidity('');
        setFieldError(chapelRate, fieldErrorElement(chapelRate, false), '');
        renderPkg(false);
        render();
    });

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const targetStep = Number(tab.dataset.step);
            if (targetStep > maxUnlockedStep) return;

            if (targetStep > step) {
                for (let index = step; index < targetStep; index += 1) {
                    if (!validate(index)) return;
                    maxUnlockedStep = Math.max(maxUnlockedStep, index + 1);
                }
            }

            go(targetStep);
        });
    });

    reviewEditButtons.forEach((button) => {
        button.addEventListener('click', () => go(Number(button.dataset.jumpStep)));
    });

    prev?.addEventListener('click', () => go(step - 1));

    next?.addEventListener('click', () => {
        if (!validate(step)) return;
        maxUnlockedStep = Math.max(maxUnlockedStep, step + 1);
        go(step + 1);
    });

    branchBtns.forEach((button) => {
        button.addEventListener('click', () => {
            branch.value = button.dataset.branchId;
            if (chapelCoverage() === 'separate' && !configuredChapelFor(chapelRate.value)) chapelRate.value = '';
            syncBranch(true);
            renderPkg(false);
            render();
        });
    });

    if (deceasedAddr && clientAddr && String(deceasedAddr.value || '').trim() !== '' && deceasedAddr.value !== clientAddr.value) {
        deceasedAddr.dataset.manual = '1';
    }

    clientAddr?.addEventListener('input', syncControls);

    clientRelationship?.addEventListener('change', syncSurnameCopy);
    surnameFields.forEach(({ source, destination }) => {
        source?.addEventListener('input', syncSurnameCopy);
        destination?.addEventListener('input', syncSurnameCopy);
    });
    copyClientSurnames.addEventListener('click', () => {
        const copies = availableSurnameCopies();
        copies.forEach(({ source, destination }) => {
            destination.value = source.value.trim();
            destination.dispatchEvent(new Event('input', { bubbles: true }));
        });
        copies[0]?.destination.focus({ preventScroll: true });
    });

    deceasedAddr?.addEventListener('input', () => {
        deceasedAddr.dataset.manual = deceasedAddr.value ? '1' : '';
    });

    born?.addEventListener('change', () => {
        validateDobDod('full');
        syncAge();
        syncDateConstraints();
        render();
    });

    died?.addEventListener('change', () => {
        validateDobDod('full');
        validateWakeInterment('light');
        syncAge();
        syncDateConstraints();
        render();
    });

    [wakeStart, wakeStartTime, wakeEnd, wakeEndTime, funeral, funeralTime, interment, intermentTime, requestDate].forEach((element) => {
        element?.addEventListener('change', event => {
            if (element === funeral) syncIntermentDateFromFuneral(event.isTrusted);
            if (element === interment) markIntermentDateManual();
            computeWakeDays();
            validateWakeInterment('light');
            syncDateConstraints();
            render();
        });
    });

    funeral?.addEventListener('change', () => {
        syncIntermentDateFromFuneral();
        computeWakeDays();
        validateWakeInterment('light');
        render();
    });

    interment?.addEventListener('change', () => {
        markIntermentDateManual();
        computeWakeDays();
        validateWakeInterment('light');
        render();
    });

    born?.addEventListener('input', () => {
        const raw = getRawDateString(born, bornPicker);
        if (!raw) {
            born.setCustomValidity('');
            setFieldError(born, bornErr, '', bornPicker?.altInput);
            return;
        }
        validateDobDod('light');
    });

    died?.addEventListener('input', () => {
        const raw = getRawDateString(died, diedPicker);
        if (!raw) {
            died.setCustomValidity('');
            setFieldError(died, diedErr, '', diedPicker?.altInput);
            return;
        }
        validateDobDod('light');
    });

    funeral?.addEventListener('input', () => {
        const raw = getRawDateString(funeral, wakePicker);
        if (!raw) {
            funeral.setCustomValidity('');
            setFieldError(funeral, wakeErr, '', wakePicker?.altInput);
            return;
        }
        computeWakeDays();
        validateWakeInterment('light');
        render();
    });

    interment?.addEventListener('input', () => {
        markIntermentDateManual();
        const raw = getRawDateString(interment, interPicker);
        if (!raw) {
            interment.setCustomValidity('');
            setFieldError(interment, intermentErr, '', interPicker?.altInput);
            return;
        }
        computeWakeDays();
        validateWakeInterment('light');
        render();
    });

    born?.addEventListener('blur', () => {
        const raw = getRawDateString(born, bornPicker);
        if (!raw) return;
        validateDobDod('full');
    });

    died?.addEventListener('blur', () => {
        const raw = getRawDateString(died, diedPicker);
        if (!raw) return;
        validateDobDod('full');
    });

    funeral?.addEventListener('blur', () => {
        const raw = getRawDateString(funeral, wakePicker);
        if (!raw) return;
        computeWakeDays();
        validateWakeInterment('light');
        render();
    });

    interment?.addEventListener('blur', () => {
        const raw = getRawDateString(interment, interPicker);
        if (!raw) return;
        computeWakeDays();
        validateWakeInterment('light');
        render();
    });

    if (typeof flatpickr !== 'undefined') {
        const dateYear = (value) => {
            if (!value) return null;
            const date = value instanceof Date ? value : new Date(value);
            return Number.isNaN(date.getTime()) ? null : date.getFullYear();
        };

        const enhanceYearDropdown = (instance) => {
            const currentYear = new Date().getFullYear();
            const calendar = instance?.calendarContainer;
            const currentMonth = calendar?.querySelector('.flatpickr-current-month');
            const yearInput = currentMonth?.querySelector('input.cur-year');
            const yearWrapper = yearInput?.closest('.numInputWrapper');
            if (!currentMonth || !yearInput || !yearWrapper) return;

            let select = currentMonth.querySelector('.intake-year-dropdown');
            if (!select) {
                select = document.createElement('select');
                select.className = 'intake-year-dropdown';
                select.setAttribute('aria-label', 'Select year');
                yearWrapper.style.display = 'none';
                yearWrapper.insertAdjacentElement('afterend', select);
                select.addEventListener('change', () => {
                    const year = Number(select.value);
                    if (Number.isFinite(year)) instance.changeYear(year);
                });
            }

            const maxYear = historicalIntake
                ? currentYear
                : dateYear(instance.config.maxDate) ?? (instance.input?.id === 'born' || instance.input?.id === 'died' ? currentYear : currentYear + 1);
            const minYear = historicalIntake
                ? 1000
                : dateYear(instance.config.minDate) ?? (instance.input?.id === 'born' || instance.input?.id === 'died' ? currentYear - 125 : currentYear - 1);
            const startYear = Math.min(minYear, maxYear);
            const endYear = Math.max(minYear, maxYear);
            const selectedYear = instance.currentYear || dateYear(instance.selectedDates?.[0]) || endYear;
            const signature = `${startYear}:${endYear}`;

            if (select.dataset.range !== signature) {
                select.replaceChildren();
                for (let year = endYear; year >= startYear; year -= 1) {
                    const option = document.createElement('option');
                    option.value = String(year);
                    option.textContent = String(year);
                    select.appendChild(option);
                }
                select.dataset.range = signature;
            }

            select.value = String(Math.min(Math.max(selectedYear, startYear), endYear));
        };

        const parseMaskedDate = (value) => {
            const match = /^(\d{2})\s*\/\s*(\d{2})\s*\/\s*(\d{4})$/.exec(value);
            if (!match) return undefined;
            const [, month, day, year] = match.map(Number);
            const date = new Date(0);
            date.setFullYear(year, month - 1, day);
            date.setHours(0, 0, 0, 0);
            return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day ? date : undefined;
        };

        const pickerBaseOpts = {
            altFormat: 'M d, Y (l)',
            parseDate: (value, format) => value.includes('/') ? parseMaskedDate(value) : flatpickr.parseDate(value, format),
            position: (instance) => {
                positionIntakePicker(instance.calendarContainer, instance.altInput || instance.input, 340);
            },
            appendTo: document.body,
            animate: !isIntakeModal,
            monthSelectorType: 'dropdown',
            prevArrow: '<span>&lsaquo;</span>',
            nextArrow: '<span>&rsaquo;</span>',
            onReady: (selectedDates, dateStr, instance) => {
                enhanceYearDropdown(instance);
                if (instance.calendarContainer) {
                    closeIntakePickerOnScroll(instance.altInput || instance.input, instance.calendarContainer, () => {
                        if (instance.isOpen) instance.close();
                    });
                }
            },
            onOpen: (selectedDates, dateStr, instance) => {
                enhanceYearDropdown(instance);
            },
            onMonthChange: (selectedDates, dateStr, instance) => {
                enhanceYearDropdown(instance);
            },
            onYearChange: (selectedDates, dateStr, instance) => {
                enhanceYearDropdown(instance);
            }
        };

        const rememberTypedValue = (picker, input, errorEl, type) => {
            const visible = picker?.altInput;
            if (!visible) return;
            visible.dataset.intakeDateAlternate = 'true';
            visible.setAttribute('aria-label', input.dataset.label || input.name);
            visible.inputMode = 'numeric';
            visible.placeholder = 'MM / DD / YYYY';
            visible.setAttribute('title', 'Enter MM / DD / YYYY or choose a date from the calendar.');

            input.dataset.userTyped = input.dataset.userTyped || '0';
            input.dataset.lastTypedValue = input.dataset.lastTypedValue || '';
            input.dataset.invalidTypedValue = input.dataset.invalidTypedValue || '';
            picker.config.onChange.unshift((selectedDates) => {
                if (selectedDates.length) input.dataset.invalidTypedValue = '';
            });

            visible.addEventListener('focus', () => {
                const selected = picker.selectedDates[0];
                if (selected && !input.dataset.invalidTypedValue) {
                    visible.value = flatpickr.formatDate(selected, 'm / d / Y');
                }
                visible.select();
            });

            const invalidMessage = () => {
                if (type === 'born') return 'Please enter a valid birthdate.';
                if (type === 'died') return 'Please enter a valid date of death.';
                if (type === 'wake') return 'Please enter a valid funeral ceremony date.';
                if (type === 'wake_start') return 'Please enter a valid wake start date.';
                if (type === 'wake_end') return 'Please enter a valid wake end date.';
                return 'Please enter a valid interment date.';
            };

            visible.addEventListener('input', (event) => {
                const digits = visible.value.replace(/\D/g, '').slice(0, 8);
                const parts = [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4)].filter(Boolean);
                const atSeparator = digits.length === 2 || digits.length === 4;
                visible.value = parts.join(' / ') + (atSeparator && !event.inputType?.startsWith('delete') ? ' / ' : '');
                const raw = visible.value.trim();
                if (type === 'interment') markIntermentDateManual();
                input.dataset.userTyped = '1';
                input.dataset.lastTypedValue = raw;

                // Keep the raw value for validation after the schedule date is committed.
                if (!raw) {
                    input.dataset.invalidTypedValue = '';
                    input.setCustomValidity('');
                    setFieldError(input, errorEl, '', visible);
                    return;
                }

                if (!parseMaskedDate(raw)) {
                    input.dataset.invalidTypedValue = raw;
                    const message = type === 'born' || type === 'died' ? invalidMessage() : '';
                    input.setCustomValidity(message);
                    setFieldError(input, errorEl, message, visible);
                    return;
                }

                input.dataset.invalidTypedValue = '';
                input.setCustomValidity('');
                setFieldError(input, errorEl, '', visible);
            });

            const normalizeShortDate = () => {
                if (input.dataset.userTyped !== '1' || !/^[\d\s/]+$/.test(visible.value)) return;
                const digits = visible.value.replace(/\D/g, '');
                if (![5, 6, 7].includes(digits.length)) return;

                const yearLength = digits.length === 7 ? 4 : 2;
                const month = digits.slice(0, 2);
                const day = digits.slice(2, -yearLength).padStart(2, '0');
                const year = digits.slice(-yearLength).padStart(4, '20');
                visible.value = `${month} / ${day} / ${year}`;
                visible.dispatchEvent(new Event('input', { bubbles:true }));
            };
            // Normalize before Flatpickr commits the alternate input.
            visible.addEventListener('blur', normalizeShortDate, true);
            visible.addEventListener('keydown', event => {
                if (event.key === 'Enter') normalizeShortDate();
            }, true);

            visible.addEventListener('blur', () => {
                const raw = (input.dataset.lastTypedValue || visible.value || '').trim();
                const invalidTyped = (input.dataset.invalidTypedValue || '').trim();
                const hasSelectedDate = !!picker.selectedDates.length;

                // untouched empty
                if (!raw && !invalidTyped && input.dataset.userTyped !== '1') {
                    input.setCustomValidity('');
                    setFieldError(input, errorEl, '', visible);
                    return;
                }

                // empty after typing
                if (!raw && !invalidTyped) {
                    input.setCustomValidity('');
                    setFieldError(input, errorEl, '', visible);
                    return;
                }

                // invalid typed text should stay as error even if flatpickr clears the field
                if (invalidTyped || (raw && !hasSelectedDate && Number.isNaN(Date.parse(raw)))) {
                    input.dataset.invalidTypedValue = raw || invalidTyped;
                    input.setCustomValidity(invalidMessage());
                    setFieldError(input, errorEl, invalidMessage(), visible);
                    return;
                }

                input.dataset.invalidTypedValue = '';
                input.setCustomValidity('');
                setFieldError(input, errorEl, '', visible);

                if (type === 'born' || type === 'died') {
                    validateDobDod('full');
                    setFieldError(input, errorEl, input.validationMessage, visible);
                } else {
                    validateWakeInterment('light');
                    setFieldError(input, errorEl, input.validationMessage, visible);
                }
            });
        };

        const scheduleEvents = [
            { field: wakeStart, time: wakeStartTime, key: 'start', label: 'Wake Start' },
            { field: wakeEnd, time: wakeEndTime, key: 'end', label: 'Wake End' },
            { field: funeral, time: funeralTime, key: 'ceremony', label: 'Funeral Ceremony' },
            { field: interment, time: intermentTime, key: 'interment', label: 'Interment' },
        ];
        const dateKey = date => flatpickr.formatDate(date, 'Y-m-d');
        const markScheduleDay = day => {
            const key = dateKey(day.dateObj);
            const events = scheduleEvents.filter(event => event.field?.value === key);
            const start = wakeStart?.value;
            const end = wakeEnd?.value;
            const inWake = Boolean(start && end && key >= start && key <= end);
            const isToday = day.classList.contains('today');
            const singleEvent = !isToday && events.length === 1;
            const fill = isToday ? 'today'
                : events.length > 1 ? 'multiple'
                : singleEvent ? `dot-${events[0].key}`
                : inWake ? 'range' : null;
            day.toggleAttribute('data-schedule-fill', Boolean(fill));
            if (singleEvent) day.style.setProperty('--schedule-cell-ink', 'var(--schedule-event-ink)');
            else day.style.removeProperty('--schedule-cell-ink');
            if (fill) {
                day.style.setProperty('--schedule-fill', `var(--schedule-${fill})`);
            } else {
                day.style.removeProperty('--schedule-fill');
            }
            day.querySelector('.intake-schedule-markers')?.remove();
            if (events.length > 1 || (isToday && events.length === 1)) {
                const markers = document.createElement('span');
                markers.className = 'intake-schedule-markers';
                markers.setAttribute('aria-hidden', 'true');
                events.forEach(event => {
                    const dot = document.createElement('i');
                    dot.style.setProperty('--schedule-marker', `var(--schedule-dot-${event.key})`);
                    markers.append(dot);
                });
                day.append(markers);
            }

            const dateLabel = day.dataset.scheduleDateLabel ?? day.getAttribute('aria-label') ?? key;
            day.dataset.scheduleDateLabel = dateLabel;
            const labels = events.map(event => event.label);
            if (isToday) labels.unshift('Today');
            if (inWake && !events.length) labels.push('Wake period');
            day.setAttribute('aria-label', [dateLabel, ...labels].join(', '));
            day.title = labels.join(' · ');
        };
        const scheduleLegends = new WeakMap();
        const refreshScheduleCalendar = instance => {
            const calendar = instance.calendarContainer;
            calendar.querySelectorAll('.flatpickr-day').forEach(markScheduleDay);
            const legend = scheduleLegends.get(instance);
            if (!legend) return;
            legend.querySelectorAll('[data-schedule-date]').forEach((label, index) => {
                const event = scheduleEvents[index];
                const key = event.field?.value;
                const date = key && flatpickr.parseDate(key, 'Y-m-d');
                label.textContent = date ? flatpickr.formatDate(date, 'M j, Y') : 'Not set';
                if (date) {
                    const dateTime = combineDateAndTime(date, event.time?.value);
                    const time = document.createElement('span');
                    time.textContent = dateTime ? flatpickr.formatDate(dateTime, 'h:i K') : 'Time not set';
                    label.append(time);
                }
            });
            const start = Date.parse(wakeStart?.value);
            const end = Date.parse(wakeEnd?.value);
            const days = Number.isFinite(start) && end >= start
                ? Math.min(365, Math.round((end - start) / 86400000) + 1)
                : 0;
            legend.querySelector('[data-wake-duration]').textContent = days
                ? `${days} wake ${days === 1 ? 'day' : 'days'} · includes start and end`
                : 'Set Wake Start and Wake End to see the duration.';
        };
        const positionScheduleCalendar = instance => {
            pickerBaseOpts.position(instance);
            const calendar = instance.calendarContainer;
            const legend = scheduleLegends.get(instance);
            if (!legend) return;

            const gap = 8;
            const viewportWidth = document.documentElement.clientWidth;
            const viewportHeight = document.documentElement.clientHeight;
            const rect = calendar.getBoundingClientRect();
            const width = Math.min(270, viewportWidth - gap * 2);
            legend.style.width = `${width}px`;
            legend.style.maxHeight = `${viewportHeight - gap * 2}px`;
            let left = rect.right + gap;
            let top = rect.top;
            if (left + width > viewportWidth - gap) left = rect.left - width - gap;
            if (left < gap) {
                left = Math.max(gap, Math.min(rect.left, viewportWidth - width - gap));
                legend.style.maxHeight = `${Math.floor(viewportHeight * .4)}px`;
                const maxCalendarHeight = Math.max(0, viewportHeight - legend.offsetHeight - gap * 3);
                calendar.style.setProperty('max-height', `${maxCalendarHeight}px`, 'important');
                const calendarTop = Math.max(gap, Math.min(rect.top, viewportHeight - calendar.offsetHeight - legend.offsetHeight - gap * 2));
                calendar.style.setProperty('top', `${calendarTop}px`, 'important');
                top = calendarTop + calendar.offsetHeight + gap;
            }
            legend.style.left = `${left}px`;
            legend.style.top = `${Math.max(gap, Math.min(top, viewportHeight - legend.offsetHeight - gap))}px`;
        };
        const schedulePickerOpts = {
            disableMobile: true,
            position: positionScheduleCalendar,
            ...(historicalIntake ? { maxDate: 'today' } : {}),
            onDayCreate: (_, __, ___, day) => markScheduleDay(day),
            onReady: (dates, value, instance) => {
                pickerBaseOpts.onReady(dates, value, instance);
                const legend = document.createElement('div');
                legend.className = 'intake-schedule-legend intake-schedule-calendar';
                legend.hidden = true;
                const title = document.createElement('h4');
                title.textContent = 'Schedule summary';
                legend.append(title);
                scheduleEvents.forEach(event => {
                    const row = document.createElement('div');
                    const dot = document.createElement('i');
                    dot.dataset.scheduleColor = event.key;
                    dot.setAttribute('aria-hidden', 'true');
                    const date = document.createElement('span');
                    date.dataset.scheduleDate = '';
                    row.append(dot, document.createTextNode(event.label), date);
                    legend.append(row);
                });
                const duration = document.createElement('p');
                duration.dataset.wakeDuration = '';
                const key = document.createElement('div');
                key.className = 'intake-schedule-key';
                for (const [color, label] of [['today', 'Today'], ['range', 'Wake period']]) {
                    const item = document.createElement('span');
                    const swatch = document.createElement('i');
                    swatch.dataset.scheduleColor = color;
                    swatch.setAttribute('aria-hidden', 'true');
                    item.append(swatch, document.createTextNode(label));
                    key.append(item);
                }
                legend.append(key, duration);
                instance.calendarContainer.classList.add('intake-schedule-calendar');
                document.body.append(legend);
                scheduleLegends.set(instance, legend);
                instance.config.ignoredFocusElements = [...instance.config.ignoredFocusElements, legend];
                instance.config.onClose.unshift(() => { legend.hidden = true; });
                instance.config.onDestroy.push(() => legend.remove());
                instance.config.onValueUpdate.push(() => refreshScheduleCalendar(instance));
                refreshScheduleCalendar(instance);
            },
            onOpen: (dates, value, instance) => {
                pickerBaseOpts.onOpen(dates, value, instance);
                const selectedDate = dates[0];
                if (selectedDate) instance.jumpToDate(selectedDate);
                refreshScheduleCalendar(instance);
                scheduleLegends.get(instance).hidden = false;
                if (selectedDate) requestAnimationFrame(() => {
                    if (instance.isOpen) instance.calendarContainer
                        .querySelector('.flatpickr-day.selected:not(.prevMonthDay):not(.nextMonthDay)')
                        ?.focus({ preventScroll: true });
                });
            },
        };

        wakeStartPicker = flatpickr(wakeStart, {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
            clickOpens: false,
            defaultDate: wakeStart?.value || null,
            ...pickerBaseOpts,
            ...schedulePickerOpts,
            altFormat: 'M d, Y (D)',
            onChange: (selectedDates) => {
                wakeStart.dataset.userTyped = '0';
                wakeStart.dataset.lastTypedValue = '';
                wakeStart.setCustomValidity('');
                setFieldError(wakeStart, wakeStartErr, '', wakeStartPicker?.altInput);

                if (wakeEndPicker && selectedDates[0]) {
                    wakeEndPicker.set('minDate', selectedDates[0]);
                }

                computeWakeDays();
                validateWakeInterment('light');
                render();
            },
            onClose: (selectedDates, dateStr, instance) => {
                const raw = (wakeStart.dataset.lastTypedValue || instance.altInput?.value || '').trim();
                const invalidTyped = (wakeStart.dataset.invalidTypedValue || '').trim();

                if (invalidTyped || (raw && !selectedDates.length && Number.isNaN(Date.parse(raw)))) {
                    wakeStart.setCustomValidity('Select a valid Wake Start date.');
                    setFieldError(wakeStart, wakeStartErr, 'Select a valid Wake Start date.', instance.altInput);
                    return;
                }

                validateWakeInterment('light');

            },
            onValueUpdate: () => {
                computeWakeDays();
                validateWakeInterment('light');

                render();
            }
        });

        wakeEndPicker = flatpickr(wakeEnd, {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
            clickOpens: false,
            defaultDate: wakeEnd?.value || null,
            minDate: wakeStart?.value || (historicalIntake ? null : requestDate?.value || 'today'),
            ...pickerBaseOpts,
            ...schedulePickerOpts,
            altFormat: 'M d, Y (D)',
            onChange: (selectedDates) => {
                wakeEnd.dataset.userTyped = '0';
                wakeEnd.dataset.lastTypedValue = '';
                wakeEnd.setCustomValidity('');
                setFieldError(wakeEnd, wakeEndErr, '', wakeEndPicker?.altInput);
                if (wakePicker && selectedDates[0]) wakePicker.set('minDate', selectedDates[0]);
                computeWakeDays();
                validateWakeInterment('light');
                render();
            },
            onClose: () => {
                validateWakeInterment('light');

            },
            onValueUpdate: () => {
                computeWakeDays();
                validateWakeInterment('light');
                render();
            }
        });

        wakePicker = flatpickr(funeral, {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
            clickOpens: false,
            defaultDate: funeral?.value || null,
            ...pickerBaseOpts,
            ...schedulePickerOpts,
            altFormat: 'M d, Y (D)',
            onChange: (selectedDates) => {
                funeral.dataset.userTyped = '0';
                funeral.dataset.lastTypedValue = '';
                funeral.setCustomValidity('');
                setFieldError(funeral, wakeErr, '', wakePicker?.altInput);

                if (interPicker && selectedDates[0]) {
                    interPicker.set('minDate', selectedDates[0]);
                }

                syncIntermentDateFromFuneral(true);
                computeWakeDays();
                validateWakeInterment('light');
                render();
            },
            onClose: (selectedDates, dateStr, instance) => {
                const raw = (funeral.dataset.lastTypedValue || instance.altInput?.value || '').trim();
                const invalidTyped = (funeral.dataset.invalidTypedValue || '').trim();

                if (invalidTyped || (raw && !selectedDates.length && Number.isNaN(Date.parse(raw)))) {
                    funeral.setCustomValidity('Please enter a valid Funeral Ceremony date.');
                    setFieldError(funeral, wakeErr, 'Please enter a valid Funeral Ceremony date.', instance.altInput);
                    return;
                }

                if (!raw) {
                    funeral.setCustomValidity('');
                    setFieldError(funeral, wakeErr, '', instance.altInput);
                    return;
                }

                validateWakeInterment('light');

            },
            onValueUpdate: () => {
                const raw = (wakePicker?.altInput?.value || '').trim();

                if (!raw) {
                    funeral.setCustomValidity('');
                    setFieldError(funeral, wakeErr, '', wakePicker?.altInput);
                    computeWakeDays();
                    render();
                    return;
                }

                computeWakeDays();
                validateWakeInterment('light');

                render();
            }
        });

        interPicker = flatpickr(interment, {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
            clickOpens: false,
            defaultDate: interment?.value || null,
            minDate: funeral?.value || wakeStart?.value || (historicalIntake ? null : requestDate?.value || 'today'),
            ...pickerBaseOpts,
            ...schedulePickerOpts,
            altFormat: 'M d, Y (D)',
            onChange: () => {
                markIntermentDateManual();
                interment.dataset.userTyped = '0';
                interment.dataset.lastTypedValue = '';
                interment.setCustomValidity('');
                setFieldError(interment, intermentErr, '', interPicker?.altInput);

                computeWakeDays();
                validateWakeInterment('light');
                render();
            },
            onClose: (selectedDates, dateStr, instance) => {
            const raw = (interment.dataset.lastTypedValue || instance.altInput?.value || '').trim();
            const invalidTyped = (interment.dataset.invalidTypedValue || '').trim();

            if (invalidTyped || (raw && !selectedDates.length && Number.isNaN(Date.parse(raw)))) {
                interment.setCustomValidity('Select a valid Interment date.');
                setFieldError(interment, intermentErr, 'Select a valid Interment date.', instance.altInput);
                return;
            }

            if (!raw) {
                interment.setCustomValidity('');
                setFieldError(interment, intermentErr, '', instance.altInput);
                return;
            }

            validateWakeInterment('light');

        },
            onValueUpdate: () => {
                const raw = (interPicker?.altInput?.value || '').trim();

                if (!raw) {
                    interment.setCustomValidity('');
                    setFieldError(interment, intermentErr, '', interPicker?.altInput);
                    computeWakeDays();
                    render();
                    return;
                }

                computeWakeDays();
                validateWakeInterment('light');

                render();
            }
        });

        const wakeTrigger = document.getElementById('wake_picker_trigger');
        if (wakeTrigger) wakeTrigger.addEventListener('click', () => wakePicker && wakePicker.open());

        const wakeStartTrigger = document.getElementById('wake_start_picker_trigger');
        if (wakeStartTrigger) wakeStartTrigger.addEventListener('click', () => wakeStartPicker && wakeStartPicker.open());

        const wakeEndTrigger = document.getElementById('wake_end_picker_trigger');
        if (wakeEndTrigger) wakeEndTrigger.addEventListener('click', () => wakeEndPicker && wakeEndPicker.open());

        const interTrigger = document.getElementById('inter_picker_trigger');
        if (interTrigger) interTrigger.addEventListener('click', () => interPicker && interPicker.open());

        bornPicker = flatpickr(born, {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
            clickOpens: false,
            maxDate: 'today',
            defaultDate: born?.value || null,
            ...pickerBaseOpts,
            onClose: (selectedDates, dateStr, instance) => {
                const raw = (born.dataset.lastTypedValue || instance.altInput?.value || '').trim();
                const invalidTyped = (born.dataset.invalidTypedValue || '').trim();
                const parsed = raw ? Date.parse(raw) : NaN;
                const future = !Number.isNaN(parsed) && new Date(parsed) > (() => {
                    const d = new Date();
                    d.setHours(23, 59, 59, 999);
                    return d;
                })();

                if (invalidTyped || (raw && !selectedDates.length && Number.isNaN(parsed))) {
                    born.setCustomValidity('Please enter a valid date of birth.');
                    setFieldError(born, bornErr, 'Please enter a valid date of birth.', instance.altInput);
                    return;
                }

                if (future) {
                    born.setCustomValidity('Date of birth cannot be in the future.');
                    setFieldError(born, bornErr, 'Date of birth cannot be in the future.', instance.altInput);
                    return;
                }

                if (!raw) {
                    born.setCustomValidity('');
                    setFieldError(born, bornErr, '', instance.altInput);
                    return;
                }

                validateDobDod('full');
                setFieldError(born, bornErr, born.validationMessage || '', instance.altInput);
            },
            onChange: () => {
                born.dataset.userTyped = '0';
                born.dataset.lastTypedValue = '';
                born.setCustomValidity('');
                setFieldError(born, bornErr, '', bornPicker?.altInput);

                syncAge();
                validateDobDod('full');
                render();
            },
            onValueUpdate: () => {
                const raw = (bornPicker?.altInput?.value || '').trim();

                if (!raw) {
                    born.setCustomValidity('');
                    setFieldError(born, bornErr, '', bornPicker?.altInput);
                    return;
                }

                validateDobDod('light');
                setFieldError(born, bornErr, born.validationMessage, bornPicker?.altInput);
            }
        });

        const bornTrigger = document.getElementById('born_picker_trigger');
        if (bornTrigger) bornTrigger.addEventListener('click', () => bornPicker && bornPicker.open());

        diedPicker = flatpickr(died, {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
            clickOpens: false,
            maxDate: 'today',
            defaultDate: died?.value || null,
            ...pickerBaseOpts,
            onClose: (selectedDates, dateStr, instance) => {
            const raw = (died.dataset.lastTypedValue || instance.altInput?.value || '').trim();
            const invalidTyped = (died.dataset.invalidTypedValue || '').trim();
            const parsed = raw ? Date.parse(raw) : NaN;
            const future = !Number.isNaN(parsed) && new Date(parsed) > (() => {
                const d = new Date();
                d.setHours(23, 59, 59, 999);
                return d;
            })();

            if (invalidTyped || (raw && !selectedDates.length && Number.isNaN(parsed))) {
                died.setCustomValidity('Please enter a valid date of death.');
                setFieldError(died, diedErr, 'Please enter a valid date of death.', instance.altInput);
                return;
            }

            if (future) {
                died.setCustomValidity('Date of death cannot be in the future.');
                setFieldError(died, diedErr, 'Date of death cannot be in the future.', instance.altInput);
                return;
            }

            if (!raw) {
                died.setCustomValidity('');
                setFieldError(died, diedErr, '', instance.altInput);
                return;
            }

            validateDobDod('full');
            setFieldError(died, diedErr, died.validationMessage || '', instance.altInput);
        },
            onChange: () => {
                died.dataset.userTyped = '0';
                died.dataset.lastTypedValue = '';
                died.setCustomValidity('');
                setFieldError(died, diedErr, '', diedPicker?.altInput);

                if (paidAt && died?.value) {
                    paidAt.removeAttribute('min');
                }

                syncAge();
                validateDobDod('full');
                validateWakeInterment('light');
                syncDateConstraints();
                render();
            },
            onValueUpdate: () => {
                const raw = (diedPicker?.altInput?.value || '').trim();

                if (!raw) {
                    died.setCustomValidity('');
                    setFieldError(died, diedErr, '', diedPicker?.altInput);
                    return;
                }

                validateDobDod('light');
                setFieldError(died, diedErr, died.validationMessage, diedPicker?.altInput);
            }
        });

        const diedTrigger = document.getElementById('died_picker_trigger');
        if (diedTrigger) diedTrigger.addEventListener('click', () => diedPicker && diedPicker.open());

        rememberTypedValue(bornPicker, born, bornErr, 'born');
        rememberTypedValue(diedPicker, died, diedErr, 'died');
        rememberTypedValue(wakeStartPicker, wakeStart, wakeStartErr, 'wake_start');
        rememberTypedValue(wakeEndPicker, wakeEnd, wakeEndErr, 'wake_end');
        rememberTypedValue(wakePicker, funeral, wakeErr, 'wake');
        rememberTypedValue(interPicker, interment, intermentErr, 'interment');
        syncIntermentDateFromFuneral();
    }

    computeWakeDays();

    wakeDays?.addEventListener('input', () => {
        wakeDays.dataset.manual = wakeDays.value ? '1' : '';
    });

    [seniorId, addAmt, taxRate, amountPaid, paidAt].forEach((element) => {
        element?.addEventListener('input', () => {
            clearFieldMessage(element);
            syncControls();
            render();
        });

        element?.addEventListener('change', () => {
            clearFieldMessage(element);
            syncControls();
            render();
        });
    });

    proofInput?.addEventListener('change', () => {
        syncControls();
        render();
    });

    const syncMarkLabel = () => {
        if (!mark || mark.type !== 'checkbox') return;
        const label = document.getElementById('mark_as_paid_label');
        const icon  = document.getElementById('mark_as_paid_icon');
        const on = mark.checked;
        if (label) {
            label.classList.toggle('border-slate-800', on);
            label.classList.toggle('bg-slate-50', on);
            label.classList.toggle('border-slate-200', !on);
            label.classList.toggle('bg-white', !on);
        }
        if (icon) {
            icon.classList.toggle('bg-slate-900', on);
            icon.classList.toggle('text-white', on);
            icon.classList.toggle('bg-slate-100', !on);
            icon.classList.toggle('text-slate-500', !on);
        }
    };

    if (mark?.type === 'checkbox') {
        mark.addEventListener('change', () => {
            syncMarkLabel();
            syncControls();
            render();
        });
        syncMarkLabel();
    }

    payTypeRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            clearFieldMessage(amountPaid);
            syncControls();
            render();
        });
    });

    const payMethodRadios = [...document.querySelectorAll('.payment-method-radio')];
    const payMethodCards  = [...document.querySelectorAll('.payment-method-card')];
    const paymentDetailsSection = document.getElementById('payment_details_section');
    const cashReferenceWrap = document.getElementById('cash_reference_wrap');
    const cashReferenceNumber = document.getElementById('cash_reference_number');
    const cashlessWrap    = document.getElementById('cashless_details_wrap');
    const cashlessType    = document.getElementById('cashless_type');
    const bankName        = document.getElementById('bank_name');
    const otherBankWrap   = document.getElementById('other_bank_name_wrap');
    const otherBankName   = document.getElementById('other_bank_name');
    const referenceNumber = document.getElementById('reference_number');
    const bankReference   = document.getElementById('bank_reference');
    const walletProvider  = document.getElementById('wallet_provider');
    const accountName     = document.getElementById('account_name');
    const mobileNumber    = document.getElementById('mobile_number');
    const paymentChannel  = document.getElementById('payment_channel');

    function syncPayMethod() {
        const selected = payMethodRadios.find(r => r.checked)?.value;
        const hasMethod = selected === 'cash' || selected === 'cashless';
        document.getElementById('change_intake_payment_method')?.classList.toggle('hidden', !hasMethod);
        document.getElementById('payment_type_section')?.classList.toggle('hidden', !hasMethod);
        if (selected) document.getElementById('payment_method_error')?.classList.add('hidden');
        if (mark?.type === 'checkbox') {
            mark.checked = hasMethod;
            syncMarkLabel();
        }
        payMethodCards.forEach(card => {
            card.classList.toggle('active-tab', !!card.querySelector('.payment-method-radio')?.checked);
        });
        const isCashless = selected === 'cashless';
        const type = cashlessType?.value || '';
        if (paymentDetailsSection) paymentDetailsSection.classList.toggle('hidden', !hasMethod);
        const typeSelected = !!document.querySelector('.payment-type-radio:checked') || !!document.getElementById('payment_type')?.value;
        document.getElementById('payment_amount_section')?.classList.toggle('hidden', !hasMethod || !typeSelected);
        if (cashReferenceWrap) cashReferenceWrap.classList.toggle('hidden', !hasMethod || isCashless);
        if (cashReferenceNumber) {
            cashReferenceNumber.disabled = !hasMethod || isCashless;
            cashReferenceNumber.required = !cashReferenceNumber.disabled;
        }
        if (cashlessWrap) cashlessWrap.classList.toggle('hidden', !hasMethod || !isCashless);
        if (cashlessType) cashlessType.required = hasMethod && isCashless;
        if (!hasMethod && amountPaid) {
            amountPaid.value = '';
        }
        if (!hasMethod) {
            payTypeRadios.forEach((radio) => { radio.checked = false; });
            if (paidAt) { paidAt.value = ''; paidAt.setCustomValidity(''); }
        }
        if (!isCashless) {
            if (cashlessType) cashlessType.value = '';
            if (bankName) bankName.value = '';
            if (otherBankName) otherBankName.value = '';
            if (referenceNumber) referenceNumber.value = '';
            if (walletProvider) walletProvider.value = '';
            if (accountName) accountName.value = '';
            if (mobileNumber) mobileNumber.value = '';
            if (paymentChannel) paymentChannel.value = '';
            if (bankReference) bankReference.value = '';
        }
        document.querySelectorAll('.intake-cashless-panel').forEach(panel => {
            panel.classList.toggle('hidden', !isCashless || panel.dataset.intakeCashlessPanel !== type);
        });
        if (bankName) {
            bankName.required = isCashless && type === 'bank_transfer';
            bankName.disabled = !bankName.required;
        }
        const isOtherBank = isCashless && type === 'bank_transfer' && bankName?.value === 'Other Bank';
        if (otherBankWrap) otherBankWrap.classList.toggle('hidden', !isOtherBank);
        if (otherBankName) {
            otherBankName.required = isOtherBank;
            otherBankName.disabled = !isOtherBank;
        }
        cashlessWrap?.querySelectorAll('[data-intake-cashless-panel="card"] input, [data-intake-cashless-panel="card"] select').forEach(field => {
            field.disabled = !isCashless || type !== 'card';
        });
        const channelField = document.getElementById('intake_payment_channel');
        if (channelField) {
            channelField.disabled = !isCashless || type !== 'other';
            channelField.required = !channelField.disabled;
        }
        const notes = document.getElementById('payment_notes');
        if (notes) notes.disabled = !isCashless || type !== 'other';
        if (referenceNumber) {
            referenceNumber.disabled = !isCashless;
            referenceNumber.required = isCashless && ['bank_transfer', 'gcash', 'maya', 'other'].includes(type);
        }
        document.getElementById('cashless_reference_required').hidden = !referenceNumber?.required;
        const isCard = isCashless && type === 'card';
        const referenceSlot = document.getElementById(isCard ? 'intake_card_reference_slot' : 'intake_reference_slot');
        const referenceField = document.getElementById('intake_reference_field');
        if (referenceSlot && referenceField && referenceField.parentElement !== referenceSlot) referenceSlot.append(referenceField);
        document.getElementById('intake_reference_slot').hidden = isCard;
        if (referenceNumber) {
            if (isCard) referenceNumber.setAttribute('aria-describedby', 'card_payment_hint');
            else referenceNumber.removeAttribute('aria-describedby');
        }
        if (bankReference && referenceNumber) bankReference.value = referenceNumber.value;

        const activeAccount = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-account-name-field]`);
        const activeMobile = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-mobile-number-field]`);
        const activeChannel = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-payment-channel-field]`);
        if (accountName && activeAccount) accountName.value = activeAccount.value;
        if (mobileNumber) mobileNumber.value = activeMobile ? activeMobile.value : '';
        if (paymentChannel) paymentChannel.value = activeChannel ? activeChannel.value : '';
        if (walletProvider) walletProvider.value = type === 'gcash' ? 'GCash' : (type === 'maya' ? 'Maya' : '');
        syncControls();
    }

    const clearPaymentMethodValidity = () => {
        [
            cashlessType,
            bankName,
            otherBankName,
            referenceNumber,
            cashReferenceNumber,
            document.getElementById('payment_notes'),
            document.getElementById('terminal_provider'),
            document.getElementById('approval_code'),
            ...document.querySelectorAll('[data-intake-account-name-field], [data-intake-mobile-number-field], [data-intake-payment-channel-field]'),
        ].forEach(clearFieldMessage);
    };

    const validReferencePattern = /^[A-Za-z0-9 _/-]+$/;
    const validAccountNamePattern = /^(?=.*[\p{L}])[\p{L}\p{M} .'-]+$/u;
    const validateReferenceValue = (field, required = false) => {
        const value = normalizeText(field?.value);
        if (required && !value) return showFieldError(field, 'Reference number is required.');
        if (!value) return true;
        if (value.length < 4 || value.length > 60) return showFieldError(field, 'Reference number must be 4 to 60 characters.');
        if (!validReferencePattern.test(value)) return showFieldError(field, 'Reference number contains invalid characters.');
        clearFieldMessage(field);
        return true;
    };
    const validateAccountNameValue = (field) => {
        const value = normalizeText(field?.value);
        if (!value) return true;
        if (value.length < 2 || value.length > 100) return showFieldError(field, 'Account name must be between 2 and 100 characters.');
        if (!validAccountNamePattern.test(value)) return showFieldError(field, 'Account name should contain letters only and must not include numbers or special characters.');
        clearFieldMessage(field);
        return true;
    };

    const paymentIdentifierCheckUrl = @json(route('payments.identifier-check'));
    const identifierCheckState = new WeakMap();
    const intakeIdentifierProvider = () => {
        const type = cashlessType?.value || '';
        if (type === 'bank_transfer') return bankName?.value === 'Other Bank' ? otherBankName?.value?.trim() : bankName?.value?.trim();
        if (type === 'gcash') return 'GCash';
        if (type === 'maya') return 'Maya';
        if (type === 'card') return document.getElementById('terminal_provider')?.value?.trim() || '';
        if (type === 'other') return document.getElementById('intake_payment_channel')?.value?.trim() || '';
        return '';
    };
    const showIdentifierDuplicate = (field, message) => {
        if (!field) return;
        field.dataset.identifierDuplicate = '1';
        field.setCustomValidity(message);
        const errorEl = fieldErrorElement(field);
        setFieldError(field, errorEl, message);
    };
    const checkIntakeIdentifier = async (field, identifierType) => {
        if (!field || field.disabled || !field.value.trim()) {
            if (field?.dataset.identifierDuplicate === '1') clearFieldMessage(field);
            if (field) delete field.dataset.identifierDuplicate;
            return false;
        }
        const value = field.value.trim();
        const paymentMethod = payMethodRadios.find(radio => radio.checked)?.value || '';
        const payload = {
            branch_id: document.getElementById('branch_id')?.value || '',
            payment_method: paymentMethod,
            identifier_type: identifierType,
            identifier: value,
            cashless_type: cashlessType?.value || '',
            provider: intakeIdentifierProvider(),
        };
        if (!payload.branch_id || (identifierType !== 'receipt_or_no' && (paymentMethod !== 'cashless' || !payload.cashless_type))) return false;
        const state = identifierCheckState.get(field) || { sequence: 0, timer: null };
        const sequence = ++state.sequence;
        if (state.timer) clearTimeout(state.timer);
        identifierCheckState.set(field, state);
        try {
            const response = await fetch(paymentIdentifierCheckUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': f.querySelector('[name="_token"]')?.value || '',
                },
                body: JSON.stringify(payload),
                credentials: 'same-origin',
            });
            if (!response.ok) return false;
            const result = await response.json();
            if (identifierCheckState.get(field)?.sequence !== sequence || field.value.trim() !== value) return false;
            if (result.duplicate) {
                showIdentifierDuplicate(field, result.message || 'This number is already used by another payment in this branch.');
                return true;
            }
            if (field.dataset.identifierDuplicate === '1') clearFieldMessage(field);
            delete field.dataset.identifierDuplicate;
            return false;
        } catch (error) {
            // The final intake save still performs an authoritative server-side duplicate check.
            return false;
        }
    };
    const scheduleIntakeIdentifierCheck = (field, identifierType) => {
        if (!field) return;
        const state = identifierCheckState.get(field) || { sequence: 0, timer: null };
        if (state.timer) clearTimeout(state.timer);
        state.sequence += 1;
        identifierCheckState.set(field, state);
        if (field.dataset.identifierDuplicate === '1') clearFieldMessage(field);
        delete field.dataset.identifierDuplicate;
        if (!field.value.trim() || field.disabled) return;
        state.timer = setTimeout(() => checkIntakeIdentifier(field, identifierType), 450);
    };
    const checkIntakePaymentIdentifiers = async () => {
        if (!payNow()) return false;
        const selected = payMethodRadios.find(radio => radio.checked)?.value;
        const checks = [];
        if (selected === 'cash' && cashReferenceNumber?.value.trim()) checks.push([cashReferenceNumber, 'receipt_or_no']);
        if (selected === 'cashless') {
            if (cashReferenceNumber?.value.trim()) checks.push([cashReferenceNumber, 'receipt_or_no']);
            if (referenceNumber && !referenceNumber.disabled && referenceNumber.value.trim()) checks.push([referenceNumber, 'reference_number']);
            const approvalField = document.getElementById('approval_code');
            if (cashlessType?.value === 'card' && approvalField?.value.trim()) checks.push([approvalField, 'approval_code']);
        }
        const results = await Promise.all(checks.map(([field, type]) => checkIntakeIdentifier(field, type)));
        const duplicateIndex = results.findIndex(Boolean);
        if (duplicateIndex >= 0) checks[duplicateIndex][0]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return duplicateIndex >= 0;
    };

    const validatePaymentMethodDetails = () => {
        let valid = true;
        syncPayMethod();
        clearPaymentMethodValidity();

        const selected = payMethodRadios.find(r => r.checked)?.value;
        if (!selected && isOtherEntryMode) {
            document.getElementById('payment_method_error')?.classList.remove('hidden');
            return false;
        }
        if (selected === 'cash') {
            const receipt = (cashReferenceNumber.value || '').trim();
            if (!receipt) return showFieldError(cashReferenceNumber, 'Enter the Receipt / OR number for this cash payment.');
            if (receipt.length < 3 || receipt.length > 50) return showFieldError(cashReferenceNumber, 'Receipt / OR number must be 3 to 50 characters.');
            if (!/^[A-Za-z0-9 /-]+$/.test(receipt)) return showFieldError(cashReferenceNumber, 'Use letters, numbers, spaces, hyphens, or slashes only.');
            return window.validatePaymentProof?.(f) ?? false;
        }
        if (selected !== 'cashless') return true;

        const type = cashlessType?.value || '';
        const ref = (referenceNumber?.value || '').trim();
        const approval = (document.getElementById('approval_code')?.value || '').trim();
        const activeChannel = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-payment-channel-field]`);
        const activeAccount = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-account-name-field]`);

        if (!type) valid = showFieldError(cashlessType, 'Cashless type is required.') && valid;
        if (type === 'bank_transfer') {
            if (!(bankName?.value || '').trim()) valid = showFieldError(bankName, 'Please select the bank name.') && valid;
            if (bankName.value === 'Other Bank') {
                const name = otherBankName.value.trim();
                if (!name) valid = showFieldError(otherBankName, 'Please enter the bank name.') && valid;
                else if (name.length < 2 || name.length > 80) valid = showFieldError(otherBankName, 'Bank name must be between 2 and 80 characters.') && valid;
                else if (!/^[A-Za-z0-9 .&()-]+$/.test(name)) valid = showFieldError(otherBankName, 'Bank name contains invalid characters.') && valid;
            }
            if (!validateAccountNameValue(accountName)) valid = false;
            if (!validateReferenceValue(referenceNumber, true)) valid = false;
        }
        if ((type === 'gcash' || type === 'maya')) {
            if (!validateAccountNameValue(activeAccount)) valid = false;
            if (!validateReferenceValue(referenceNumber, true)) valid = false;
            const mobile = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-mobile-number-field]`);
            if (mobile?.value.trim() && !/^(09\d{9}|\+?639\d{9})$/.test(mobile.value.trim())) {
                valid = showFieldError(mobile, 'Please enter a valid Philippine mobile number.') && valid;
            }
        }
        if (type === 'card' && !approval && !ref) {
            valid = showFieldError(document.getElementById('approval_code'), 'Enter an approval code or a transaction reference number.') && valid;
        }
        if (type === 'card' && ref && !validateReferenceValue(referenceNumber, false)) valid = false;
        if (type === 'card') {
            const approvalField = document.getElementById('approval_code');
            const terminal = document.getElementById('terminal_provider');
            if (approval && !/^[A-Za-z0-9_/-]{4,40}$/.test(approval)) valid = showFieldError(approvalField, 'Approval code contains invalid characters or is not 4 to 40 characters long.') && valid;
            if (terminal?.value.trim() && !/^[A-Za-z0-9 .()-]{1,80}$/.test(terminal.value.trim())) valid = showFieldError(terminal, 'Enter a valid terminal or provider name up to 80 characters.') && valid;
        }
        if (type === 'other') {
            const channel = activeChannel?.value.trim() || '';
            if (!channel) valid = showFieldError(activeChannel, 'Payment channel is required.') && valid;
            else if (channel.length < 2 || channel.length > 80) valid = showFieldError(activeChannel, 'Payment channel must be between 2 and 80 characters.') && valid;
            else if (!/^[A-Za-z0-9 ._&()-]+$/.test(channel)) valid = showFieldError(activeChannel, 'Payment channel contains invalid characters.') && valid;
            const notes = document.getElementById('payment_notes');
            if (notes?.value.trim().length > 255) valid = showFieldError(notes, 'Notes must not exceed 255 characters.') && valid;
            else if (/[<>]/.test(notes?.value || '')) valid = showFieldError(notes, 'Notes cannot contain angle brackets.') && valid;
            if (!validateReferenceValue(referenceNumber, true)) valid = false;
        }

        return valid && (window.validatePaymentProof?.(f) ?? false);
    };

    const syncPaymentAndRender = (event) => {
        if (event?.type === 'change') clearPaymentMethodValidity();
        else if (event?.target) clearFieldMessage(event.target);
        syncPayMethod();
        scheduleIntakeIdentifierCheck(cashReferenceNumber, 'receipt_or_no');
        scheduleIntakeIdentifierCheck(referenceNumber, 'reference_number');
        scheduleIntakeIdentifierCheck(document.getElementById('approval_code'), 'approval_code');
        render();
    };

    payMethodRadios.forEach(r => r.addEventListener('change', syncPaymentAndRender));
    document.getElementById('change_intake_payment_method')?.addEventListener('click', () => {
        payMethodRadios.forEach(radio => { radio.checked = false; });
        clearPaymentMethodValidity();
        syncPaymentAndRender();
        payMethodRadios[0]?.focus({ preventScroll: true });
    });
    cashlessType?.addEventListener('change', syncPaymentAndRender);
    bankName?.addEventListener('change', syncPaymentAndRender);
    otherBankName?.addEventListener('input', syncPaymentAndRender);
    cashReferenceNumber?.addEventListener('input', syncPaymentAndRender);
    referenceNumber?.addEventListener('input', () => {
        syncPayMethod();
        clearFieldMessage(referenceNumber);
        scheduleIntakeIdentifierCheck(referenceNumber, 'reference_number');
        render();
    });
    referenceNumber?.addEventListener('blur', () => {
        if (validateReferenceValue(referenceNumber, false)) checkIntakeIdentifier(referenceNumber, 'reference_number');
        render();
    });
    document.querySelectorAll('[data-intake-account-name-field], [data-intake-mobile-number-field], [data-intake-payment-channel-field]').forEach(field => {
        field.addEventListener('input', syncPaymentAndRender);
    });
    document.querySelectorAll('[data-intake-account-name-field]').forEach(field => {
        field.addEventListener('blur', () => {
            validateAccountNameValue(field);
            render();
        });
    });
    document.getElementById('approval_code')?.addEventListener('input', () => {
        clearPaymentMethodValidity();
        scheduleIntakeIdentifierCheck(document.getElementById('approval_code'), 'approval_code');
        render();
    });
    document.getElementById('approval_code')?.addEventListener('blur', () => checkIntakeIdentifier(document.getElementById('approval_code'), 'approval_code'));
    cashReferenceNumber?.addEventListener('blur', () => checkIntakeIdentifier(cashReferenceNumber, 'receipt_or_no'));
    cashlessType?.addEventListener('change', () => {
        scheduleIntakeIdentifierCheck(referenceNumber, 'reference_number');
        scheduleIntakeIdentifierCheck(document.getElementById('approval_code'), 'approval_code');
    });
    bankName?.addEventListener('change', () => scheduleIntakeIdentifierCheck(referenceNumber, 'reference_number'));
    otherBankName?.addEventListener('input', () => scheduleIntakeIdentifierCheck(referenceNumber, 'reference_number'));
    document.getElementById('terminal_provider')?.addEventListener('input', () => scheduleIntakeIdentifierCheck(document.getElementById('approval_code'), 'approval_code'));
    document.getElementById('intake_payment_channel')?.addEventListener('input', () => scheduleIntakeIdentifierCheck(referenceNumber, 'reference_number'));
    document.getElementById('card_type')?.addEventListener('change', render);
    document.getElementById('terminal_provider')?.addEventListener('input', render);
    document.querySelectorAll('[name="payment_notes"]').forEach(field => {
        field.addEventListener('input', syncPaymentAndRender);
    });
    syncPayMethod();

    pkgRadios.forEach((radio) => {
        radio.addEventListener('change', (event) => {
            if (radio.value !== 'custom' && radio.checked) lastRegularPackageValue = radio.value;
            const promotionContextField = document.querySelector('[name="promotion_context_token"]');
            const promotionReviewField = document.querySelector('[name="promotion_review_token"]');
            const promotionAcknowledgement = document.querySelector('[name="promotion_acknowledged"]');
            const promotionReviewBanner = document.querySelector('.promotion-review-banner');
            if (promotionContextField) promotionContextField.value = radio.checked && radio.value !== 'custom' ? (radio.dataset.promoContext || '') : '';
            if (promotionReviewField) promotionReviewField.value = '';
            if (promotionAcknowledgement) promotionAcknowledgement.checked = false;
            if (promotionReviewBanner) promotionReviewBanner.hidden = true;
            renderPkg(event.isTrusted);
            syncControls();
            render();
        });
    });
    const initialSelectedPackage = pkgRadios.find((radio) => radio.checked && radio.value !== 'custom');
    const promotionContextField = document.querySelector('[name="promotion_context_token"]');
    if (initialSelectedPackage && promotionContextField && !promotionContextField.value) {
        promotionContextField.value = initialSelectedPackage.dataset.promoContext || '';
    }

    const selectPackageRadio = (radio) => {
        if (!radio || radio.checked) return;
        radio.checked = true;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
    };

    packageModeOptions.forEach((option) => {
        option.addEventListener('click', () => showPackageMode(option.dataset.packageMode));
        option.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
            event.preventDefault();
            const index = packageModeOptions.indexOf(option);
            const delta = ['ArrowRight', 'ArrowDown'].includes(event.key) ? 1 : -1;
            const target = packageModeOptions[(index + delta + packageModeOptions.length) % packageModeOptions.length];
            target.focus();
        });
    });
    document.querySelectorAll('[data-change-package-mode]').forEach((button) => {
        button.addEventListener('click', () => {
            packageModeChooser?.classList.remove('hidden');
            pkgRadios.forEach(radio => { radio.checked = false; });
            regularPackageArea?.classList.add('hidden');
            customPackageArea?.classList.add('hidden');
            renderPkg(false);
            syncControls();
            render();
            packageModeOptions.forEach((option) => option.setAttribute('aria-checked', 'false'));
            packageModeOptions[0]?.focus();
        });
    });

    regularPackageChoice?.addEventListener('change', () => {
        selectPackageRadio(pkgRadios.find((radio) => radio.value === regularPackageChoice.value));
    });

    optionalAddOnsList?.addEventListener('change', (event) => {
        const row = event.target.closest('.optional-add-on-row');
        if (!row) return;
        const checkbox = row.querySelector('.optional-add-on-checkbox');
        const quantityField = row.querySelector('[data-draft-add-on-quantity]');
        const id = String(checkbox?.value || '');
        if (event.target.matches('.optional-add-on-checkbox')) {
            row.querySelector('[data-add-on-quantity-note]').classList.toggle('hidden', !checkbox.checked);
            row.querySelector('[data-optional-add-on-toggle]').innerHTML = checkbox.checked ? '<i class="bi bi-check2" aria-hidden="true"></i> Selected' : '<i class="bi bi-plus" aria-hidden="true"></i> Select';
            row.querySelector('[data-optional-add-on-toggle]').setAttribute('aria-pressed', String(checkbox.checked));
            if (checkbox.checked) {
                draftSelectedAddOns.add(id);
                if (!draftAddOnQuantities.has(id)) draftAddOnQuantities.set(id, 1);
            } else {
                draftSelectedAddOns.delete(id);
                draftAddOnQuantities.delete(id);
            }
            if (quantityField) {
                quantityField.disabled = !checkbox.checked;
                quantityField.value = String(draftAddOnQuantities.get(id) || 1);
            }
        } else if (event.target.matches('[data-draft-add-on-quantity]')) {
            const quantity = Number(quantityField.value);
            draftAddOnQuantities.set(id, Number.isInteger(quantity) && quantity > 0 ? quantity : 0);
        } else {
            return;
        }
        const lineTotal = row.querySelector('[data-draft-add-on-line-total]');
        const quantity = Number(quantityField?.value);
        if (lineTotal) lineTotal.textContent = fmt(num(checkbox?.dataset.price) * (Number.isInteger(quantity) && quantity > 0 ? quantity : 0));
        syncDraftAddOnsTotal();
    });

    optionalAddOnsList?.addEventListener('click', event => {
        const button = event.target.closest('[data-optional-add-on-toggle], [data-optional-quantity-step]');
        if (!button) {
            if (event.target.closest('button, input, label, select, a, [data-add-on-quantity-note]')) return;
            event.target.closest('.optional-add-on-row')?.querySelector('.optional-add-on-checkbox')?.click();
            return;
        }
        const row = button.closest('.optional-add-on-row');
        if (button.hasAttribute('data-optional-add-on-toggle')) {
            row.querySelector('.optional-add-on-checkbox').click();
            return;
        }
        const input = row.querySelector('[data-draft-add-on-quantity]');
        input.value = Math.max(1, (input.valueAsNumber || 1) + Number(button.dataset.optionalQuantityStep));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
    optionalAddOnsList?.addEventListener('keydown', event => {
        if (!event.target.matches('.optional-add-on-row') || !['Enter', ' '].includes(event.key)) return;
        event.preventDefault();
        event.target.querySelector('.optional-add-on-checkbox')?.click();
    });

    optionalAddOnsList?.addEventListener('input', (event) => {
        if (!event.target.matches('[data-draft-add-on-quantity]')) return;
        const row = event.target.closest('.optional-add-on-row');
        const checkbox = row?.querySelector('.optional-add-on-checkbox');
        const quantity = Number(event.target.value);
        if (checkbox?.checked) draftAddOnQuantities.set(String(checkbox.value), Number.isInteger(quantity) && quantity > 0 ? quantity : 0);
        const lineTotal = row?.querySelector('[data-draft-add-on-line-total]');
        if (lineTotal) lineTotal.textContent = fmt(num(checkbox?.dataset.price) * (Number.isInteger(quantity) ? quantity : 0));
        syncDraftAddOnsTotal();
    });

    selectedAddOnsLines?.addEventListener('input', (event) => {
        const field = event.target.closest('[data-applied-add-on-quantity]');
        if (!field) return;
        const id = String(field.dataset.appliedAddOnQuantity);
        const quantity = Number(field.value);
        addOnQuantities.set(id, Number.isInteger(quantity) && quantity > 0 ? quantity : 0);
        const addOn = parseAddOns().find((item) => String(item.id) === id);
        const lineTotal = selectedAddOnsLines.querySelector(`[data-applied-add-on-line-total="${CSS.escape(id)}"]`);
        if (lineTotal) lineTotal.textContent = fmt(num(addOn?.price) * (Number.isInteger(quantity) ? quantity : 0));
        const total = addOnsTotal();
        if (selectedAddOnsTotal) selectedAddOnsTotal.textContent = fmt(total);
        if (draftAddOnsTotal) draftAddOnsTotal.textContent = fmt(total);
        render();
    });

    selectedAddOnsLines?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-applied-add-on]');
        if (!button) return;
        const row = button.closest('[data-applied-add-on-row]');
        const adjacentRow = row.nextElementSibling || row.previousElementSibling;
        const adjacentId = adjacentRow?.dataset.appliedAddOnRow;
        const id = String(button.dataset.removeAppliedAddOn);
        oldSelectedAddOns.delete(id);
        addOnQuantities.delete(id);
        const checkbox = optionalAddOnsList?.querySelector(`.optional-add-on-checkbox[value="${CSS.escape(id)}"]`);
        if (checkbox) checkbox.checked = false;
        draftSelectedAddOns.delete(id);
        draftAddOnQuantities.delete(id);
        renderPkg(false);
        render();
        const nextAction = adjacentId && selectedAddOnsLines.querySelector(`[data-remove-applied-add-on="${CSS.escape(adjacentId)}"]`);
        (nextAction || chooseAddOnsBtn)?.focus({ preventScroll: true });
    });

    chooseAddOnsBtn?.addEventListener('click', event => {
        event.preventDefault();
        if (chooseAddOnsBtn.disabled) return;
        if (optionalAddOnsPanel.matches(':popover-open')) { setAddOnsPanelOpen(false); return; }
        restoreAppliedAddOns();
        optionalAddOnsList?.querySelectorAll('[data-add-on-quantity-note]').forEach((note) => {
            const row = note.closest('.optional-add-on-row');
            const checkbox = row.querySelector('.optional-add-on-checkbox');
            note.classList.toggle('hidden', !checkbox?.checked);
            row.querySelector('[data-optional-add-on-toggle]').innerHTML = checkbox.checked ? '<i class="bi bi-check2" aria-hidden="true"></i> Selected' : '<i class="bi bi-plus" aria-hidden="true"></i> Select';
            row.querySelector('[data-optional-add-on-toggle]').setAttribute('aria-pressed', String(checkbox.checked));
        });
        if (optionalAddOnsSearch) optionalAddOnsSearch.value = '';
        optionalAddOnsList?.querySelectorAll('.optional-add-on-row').forEach((row) => row.classList.remove('hidden'));
        resetAddonSearch();
        setAddOnsPanelOpen(true);
    });

    applyAddOnsBtn?.addEventListener('click', applyDraftAddOns);

    cancelAddOnsBtn?.addEventListener('click', () => {
        restoreAppliedAddOns();
        setAddOnsPanelOpen(false);
    });

    closeAddOnsBtn?.addEventListener('click', () => {
        restoreAppliedAddOns();
        setAddOnsPanelOpen(false);
        chooseAddOnsBtn?.focus({ preventScroll: true });
    });

    // This dialog already holds the complete authorized catalog: search stays local.
    const addonSearchMatches = document.getElementById('addon_search_matches');
    const addonSearchClear = document.getElementById('addon_search_clear');
    const hideAddonMatches = () => {
        addonSearchMatches.hidden = true;
        addonSearchMatches.replaceChildren();
        optionalAddOnsSearch.setAttribute('aria-expanded', 'false');
        optionalAddOnsSearch.removeAttribute('aria-activedescendant');
    };
    const filterAddonCards = () => {
        const term = optionalAddOnsSearch.value.trim().toLowerCase();
        let visible = 0;
        optionalAddOnsList?.querySelectorAll('.optional-add-on-row').forEach(row => {
            const match = !term || row.dataset.addOnSearch.includes(term);
            row.classList.toggle('hidden', !match);
            if (match) visible++;
        });
        let empty = document.getElementById('addon_search_empty');
        if (!empty) {
            empty = document.createElement('p');
            empty.id = 'addon_search_empty';
            empty.setAttribute('role', 'status');
            empty.textContent = 'No matching add-ons.';
            optionalAddOnsList.append(empty);
        }
        empty.hidden = visible > 0;
        optionalAddOnsList.scrollTop = 0;
        hideAddonMatches();
        addonSearchClear.hidden = !optionalAddOnsSearch.value;
        optionalAddOnsSearch.focus({ preventScroll: true });
    };
    const resetAddonSearch = () => {
        optionalAddOnsSearch.value = '';
        addonSearchClear.hidden = true;
        hideAddonMatches();
        document.getElementById('addon_search_empty')?.setAttribute('hidden', '');
    };
    const underlineSearchMatch = (target, text, query) => {
        const folded = text.toLowerCase();
        let offset = 0, index;
        while ((index = folded.indexOf(query, offset)) !== -1) {
            target.append(document.createTextNode(text.slice(offset, index)));
            const mark = document.createElement('mark');
            mark.textContent = text.slice(index, index + query.length);
            target.append(mark);
            offset = index + query.length;
        }
        target.append(document.createTextNode(text.slice(offset)));
    };
    const drawAddonMatches = () => {
        hideAddonMatches();
        addonSearchClear.hidden = !optionalAddOnsSearch.value;
        const query = optionalAddOnsSearch.value.trim().toLowerCase();
        if (query.length < 2) return;
        [...optionalAddOnsList.querySelectorAll('.optional-add-on-row')]
            .filter(row => row.dataset.addOnSearch.includes(query)).slice(0, 5).forEach((row, index) => {
                const record = row.querySelector('.optional-add-on-checkbox');
                const option = document.createElement('button');
                option.type = 'button';
                option.id = `addon_match_${index}`;
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.title = [record.dataset.name, record.dataset.unit].filter(Boolean).join(' · ');
                const name = document.createElement('span');
                underlineSearchMatch(name, record.dataset.name, query);
                option.append(name);
                const detail = [record.dataset.category, record.dataset.unit, record.dataset.description].filter(Boolean).find(value => value.toLowerCase().includes(query)) || record.dataset.unit;
                if (detail) {
                    const context = document.createElement('span');
                    underlineSearchMatch(context, detail, query);
                    option.append(context);
                }
                option.addEventListener('click', () => {
                    optionalAddOnsSearch.value = record.dataset.name;
                    filterAddonCards();
                });
                addonSearchMatches.append(option);
            });
        addonSearchMatches.hidden = !addonSearchMatches.children.length;
        optionalAddOnsSearch.setAttribute('aria-expanded', String(!addonSearchMatches.hidden));
    };
    optionalAddOnsSearch?.addEventListener('input', drawAddonMatches);
    document.getElementById('addon_search_submit')?.addEventListener('click', filterAddonCards);
    addonSearchClear?.addEventListener('click', () => {
        resetAddonSearch();
        filterAddonCards();
    });
    optionalAddOnsSearch?.addEventListener('keydown', event => {
        const options = [...addonSearchMatches.children];
        const current = options.findIndex(option => option.getAttribute('aria-selected') === 'true');
        if (['ArrowDown', 'ArrowUp'].includes(event.key) && options.length) {
            event.preventDefault();
            const index = (current + (event.key === 'ArrowDown' ? 1 : -1) + options.length) % options.length;
            options.forEach((option, i) => option.setAttribute('aria-selected', String(i === index)));
            optionalAddOnsSearch.setAttribute('aria-activedescendant', options[index].id);
            options[index].scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (current >= 0 && !addonSearchMatches.hidden) options[current].click();
            else filterAddonCards();
        } else if (event.key === 'Escape' && !addonSearchMatches.hidden) {
            event.preventDefault();
            event.stopPropagation();
            hideAddonMatches();
        }
    });
    optionalAddOnsPanel?.addEventListener('click', event => {
        if (!event.target.closest('.intake-addon-search')) hideAddonMatches();
    });
    optionalAddOnsSearch?.closest('.intake-addon-search')?.addEventListener('focusout', event => {
        if (!event.currentTarget.contains(event.relatedTarget)) hideAddonMatches();
    });

    optionalAddOnsPanel?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            restoreAppliedAddOns();
            setAddOnsPanelOpen(false);
            chooseAddOnsBtn?.focus({ preventScroll: true });
        }
    });
    optionalAddOnsPanel?.addEventListener('toggle', event => {
        const open = event.newState === 'open';
        chooseAddOnsBtn?.setAttribute('aria-expanded', String(open));
        if (!open) {
            restoreAppliedAddOns();
        }
    });
    closeIntakePickerOnScroll(chooseAddOnsBtn, optionalAddOnsPanel, () => setAddOnsPanelOpen(false));

    serviceModalBackdrop?.addEventListener('click', () => {
        if (optionalAddOnsPanel?.matches(':popover-open')) {
            restoreAppliedAddOns();
            setAddOnsPanelOpen(false);
        }
    });
    includedAddOnAdjustments.addEventListener('click', event => {
        const button = event.target.closest('[data-inclusion-quantity-step]');
        if (!button) return;
        const row = button.closest('[data-included-addon]');
        const item = structuredInclusionsFor().find(item => String(item.add_on_id) === row.dataset.includedAddon);
        const input = row.querySelector('input');
        const current = Number.isInteger(input.valueAsNumber) ? input.valueAsNumber : item.included_quantity;
        input.value = Math.max(1, Math.min(9999, current + Number(button.dataset.inclusionQuantityStep)));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    document.querySelectorAll('[data-upgrade-trigger], [data-custom-asset-trigger]').forEach(trigger => {
        trigger.classList.add('service-field-chip');
        trigger.dataset.serviceTooltip = (trigger.dataset.upgradeTrigger || trigger.dataset.customAssetTrigger) === 'casket' ? 'Choose casket' : 'Choose hearse';
        const custom = trigger.hasAttribute('data-custom-asset-trigger');
        const isCasket = (trigger.dataset.upgradeTrigger || trigger.dataset.customAssetTrigger) === 'casket';
        const select = isCasket ? (custom ? document.getElementById('custom_casket_catalog_id') : replacementCasket) : hearseCatalogSelect;
        const panel = document.getElementById(trigger.getAttribute('aria-controls'));
        const reset = document.querySelector(`[data-upgrade-reset="${trigger.dataset.upgradeTrigger}"]`);
        const setUpgrade = value => {
            select.value = value;
            if (isCasket && !custom) casketModeRadios.find(field => field.type === 'checkbox').checked = Boolean(value);
            select.dispatchEvent(new Event('change', { bubbles: true }));
            syncPreferredPackage();
            render();
        };
        const syncLabel = () => {
            if (!custom) return;
            const option = select.selectedOptions[0];
            trigger.textContent = select.value ? (option?.dataset.name || option?.textContent || '') : (isCasket ? 'Choose a casket' : 'Choose a hearse');
        };
        select.addEventListener('change', syncLabel);
        syncLabel();
        const close = () => {
            if (panel.matches(':popover-open')) panel.hidePopover();
        };
        closeIntakePickerOnScroll(trigger, panel, close);
        panel.addEventListener('toggle', () => {
            trigger.setAttribute('aria-expanded', String(panel.matches(':popover-open')));
        });
        reset?.addEventListener('click', () => {
            setUpgrade('');
            trigger.focus({ preventScroll: true });
        });
        trigger.addEventListener('click', () => {
            if (panel.matches(':popover-open')) {
                close();
                return;
            }
            const includedId = custom ? null : isCasket
                ? includedCasketFor()?.id
                : structuredInclusionsFor().find(item => item.service_type === 'hearse')?.hearse?.id;
            const options = [...select.options].filter(option => option.value && !option.disabled && option.value !== String(includedId));
            const searchFrame = document.createElement('div');
            searchFrame.className = 'intake-asset-search';
            const search = document.createElement('input');
            search.type = 'search';
            search.placeholder = isCasket ? 'Casket name or material' : 'Hearse name';
            search.setAttribute('aria-label', isCasket ? 'Search caskets' : 'Search hearses');
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.textContent = '×';
            clear.hidden = true;
            clear.setAttribute('aria-label', 'Clear search');
            const submit = document.createElement('button');
            submit.type = 'button';
            submit.textContent = 'Search';
            searchFrame.append(search, clear, submit);
            const list = document.createElement('div');
            list.className = 'intake-asset-options';
            list.replaceChildren(...options.map(option => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = option.textContent;
                button.setAttribute('aria-pressed', String(option.value === select.value));
                button.addEventListener('click', () => {
                    setUpgrade(option.value);
                    close();
                    trigger.focus({ preventScroll: true });
                });
                return button;
            }));
            const empty = document.createElement('p');
            empty.textContent = 'No available options.';
            empty.hidden = Boolean(options.length);
            const filter = () => {
                const query = search.value.trim().toLowerCase();
                let visible = 0;
                [...list.children].forEach(button => {
                    button.hidden = !button.textContent.toLowerCase().includes(query);
                    if (!button.hidden) visible++;
                });
                empty.hidden = visible > 0;
                clear.hidden = !search.value;
            };
            search.addEventListener('input', filter);
            search.addEventListener('keydown', event => {
                if (event.key === 'Enter') { event.preventDefault(); filter(); }
                if (event.key === 'ArrowDown') { event.preventDefault(); list.querySelector('button:not([hidden])')?.focus({ preventScroll: true }); }
            });
            submit.addEventListener('click', filter);
            clear.addEventListener('click', () => { search.value = ''; filter(); search.focus({ preventScroll: true }); });
            panel.replaceChildren(searchFrame, list, empty);
            window.addServicePopoverDone(panel, trigger);
            panel.showPopover();
            positionIntakePicker(panel, trigger, 380);
            search.focus({ preventScroll: true });
        });
    });

    addAdditionalItem?.addEventListener('click', () => {
        const row = addAdditionalServiceRow();
        render();
        additionalItemsList.scrollTop = additionalItemsList.scrollHeight;
        row?.querySelector('[data-additional-description]')?.focus({ preventScroll: true });
    });

    [hearseCatalogSelect, replacementCasket, retrievalExcessInput, hearseExcessInput, applyRetrievalExcess, applyHearseExcess].forEach((field) => {
        field?.addEventListener('input', render);
        field?.addEventListener('change', render);
    });

    casketModeRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            if (radio.value === 'included' && radio.checked && replacementCasket) {
                replacementCasket.value = '';
            }
            render();
        });
    });

    customCoverageRows.forEach(row => {
        row.addEventListener('input', render);
        const trigger = row.querySelector('[data-coverage-adjust]');
        trigger.dataset.serviceTooltip = 'Adjust';
        const panel = row.querySelector('[data-coverage-panel]');
        if (typeof panel.showPopover === 'function') {
            panel.setAttribute('popover', 'auto');
            panel.hidden = false;
            const open = window.bindServiceFieldPopover(trigger, panel);
            row.addEventListener('invalid', () => open(), true);
        } else {
            trigger.addEventListener('click', () => { panel.hidden = !panel.hidden; });
            row.addEventListener('invalid', () => { panel.hidden = false; }, true);
        }
        row.querySelector('[data-use-wake-duration]')?.addEventListener('click', () => {
            viewingManual.value = '0';
            render();
            viewingManual.dispatchEvent(new Event('change', { bubbles:true }));
        });
    });
    viewingRequested?.addEventListener('input', () => { viewingManual.value = viewingRequested.value === '' ? '0' : '1'; });
    prefPkg?.addEventListener('input', () => {
        syncPreferredPackage();
        render();
    });

    prefPrice?.addEventListener('input', () => {
        syncPreferredPackage();
        render();
    });

    prefIncl?.addEventListener('input', () => {
        syncPreferredPackage();
        renderPkg(false);
        render();
    });

    prefFree?.addEventListener('input', () => {
        syncPreferredPackage();
        renderPkg(false);
        render();
    });

    customPkgFields?.querySelectorAll('.custom-service-check, .custom-freebie-check, .custom-service-dependent input, .custom-service-dependent select, #custom_other_inclusions').forEach((field) => {
        field.addEventListener(field.matches('input[type="checkbox"], select') ? 'change' : 'input', () => {
            syncPreferredPackage();
            renderPkg(false);
            render();
        });
    });

    document.querySelectorAll('[data-validate]').forEach((element) => {
        element.addEventListener('input', () => {
            clearFieldMessage(element);

            if (element.dataset.validate === 'letters-spaces') {
                element.value = element.value.replace(/[^A-Za-zÀ-ÿ\s.'-]/g, '');
            }

            if (element.dataset.validate === 'digits') {
                element.value = element.value.replace(/\D/g, '');
            }

            if (element.dataset.validate === 'philippine-mobile') {
                element.value = element.value.replace(/[^\d+]/g, '').replace(/(?!^)\+/g, '');
            }
        });
    });

    const autoCapitalizeFirst = (field) => {
        if (!field || typeof field.value !== 'string' || field.value.length === 0) return;
        const first = field.value.charAt(0);
        if (first >= 'a' && first <= 'z') {
            const newValue = first.toUpperCase() + field.value.slice(1);
            const start = field.selectionStart;
            const end = field.selectionEnd;
            field.value = newValue;
            if (start !== null && end !== null) {
                field.setSelectionRange(start, end);
            }
        }
    };

    const normalizeMixedCaps = (value) => {
        if (typeof value !== 'string' || value.length === 0) return value;
        const lower = value.toLowerCase();
        return lower.replace(/(^|[\s.'-])([a-zà-ÿ])/g, (match, prefix, letter) => `${prefix}${letter.toUpperCase()}`);
    };

    const setupAutoCapitalize = () => {
        const fields = document.querySelectorAll('input[type="text"]:not([data-skip-autocap]), textarea:not([data-skip-autocap])');
        fields.forEach((field) => {
            field.addEventListener('input', () => autoCapitalizeFirst(field));
            field.addEventListener('blur', () => {
                const normalized = normalizeMixedCaps(field.value);
                if (typeof normalized === 'string' && normalized !== field.value) {
                    field.value = normalized;
                }
            });
        });
    };

    setupAutoCapitalize();

    f.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;

        if (target.matches('input, textarea, select')) {
            clearFieldMessage(target);
            if (target.id === 'cash_reference_number') scheduleIntakeIdentifierCheck(target, 'receipt_or_no');
            if (target.id === 'reference_number') scheduleIntakeIdentifierCheck(target, 'reference_number');
            if (target.id === 'approval_code') scheduleIntakeIdentifierCheck(target, 'approval_code');
            if (includedAddOnAdjustments.contains(target)) render();
        }
    });

    let intakeIdentifierSubmitBypass = false;
    f.addEventListener('invalid', () => { intakeIdentifierSubmitBypass = false; }, true);
    f.addEventListener('submit', async (event) => {
        if (event.submitter?.id === 'saveIntakeDraft' || intakeIdentifierSubmitBypass || !payNow()) {
            if (intakeIdentifierSubmitBypass) intakeIdentifierSubmitBypass = false;
            return;
        }
        event.preventDefault();
        if (await checkIntakePaymentIdentifiers()) {
            go(4);
            return;
        }
        intakeIdentifierSubmitBypass = true;
        f.requestSubmit(event.submitter || document.getElementById('saveIntakeRecord'));
    }, true);

    f.addEventListener('submit', (event) => {
        if (currentStepField) currentStepField.value = String(step);
        restoreAppliedAddOns();
        setAddOnsPanelOpen(false);

        if (event.submitter?.id === 'saveIntakeDraft') {
            try {
                localStorage.removeItem(intakeAutosaveKey);
            } catch (error) {
                // Autosave cleanup is best-effort only.
            }
            return;
        }

        if (isOtherEntryMode && otherBranchWindowClosed) {
            event.preventDefault();
            return;
        }

        if (shouldLockOtherBranchIntake()) {
            event.preventDefault();
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Please select a branch first before encoding.';
            showBranchToast('Select Branch 2 or Branch 3 before entering case details.');
            return;
        }

        for (let index = 1; index <= totalSteps; index += 1) {
            if (!validate(index)) {
                event.preventDefault();
                if (index === 3 && !chapelScheduleBox.hidden && chapelScheduleBox.dataset.state !== 'available') {
                    go(index, { scroll: false });
                    focusChapelField(chapelScheduleBox.querySelector('[data-chapel-check-status]'));
                } else {
                    go(index);
                }
                if (index === 3) validateWakeInterment('full');
                return;
            }
        }

        try {
            localStorage.removeItem(intakeAutosaveKey);
        } catch (error) {
            // Autosave is best-effort only.
        }
    });

    const clientPicker = f.querySelector('[data-existing-client-picker]');
    const existingClientField = f.elements.existing_client_id;
    const savedClientSelection = @json($existingClientSelection ?? null);
    const clientProfileFields = ['first_name', 'middle_name', 'last_name', 'suffix', 'contact_number', 'address'];
    const syncExistingClient = (client = null, clear = false) => {
        if (!clientPicker) return;
        const selected = Boolean(existingClientField.value);
        clientProfileFields.forEach(key => {
            const field = f.elements['client_' + key];
            if (client || clear) field.value = client?.[key] ?? '';
            if (field.tagName === 'SELECT') field.disabled = selected;
            else field.readOnly = selected;
            if (selected || clear) clearFieldMessage(field);
        });
        clientPicker.querySelector('[data-existing-client-search]').hidden = selected;
        clientPicker.querySelector('[data-existing-client-selected]').hidden = !selected;
        const clientName = client?.full_name
            || ['first_name', 'middle_name', 'last_name', 'suffix'].map(key => f.elements['client_' + key].value).filter(Boolean).join(' ')
            || 'Selected client';
        clientPicker.querySelector('[data-existing-client-name]').textContent = [clientName, client?.directory_id].filter(Boolean).join(' · ');
    };

    if (clientPicker) {
        const search = clientPicker.querySelector('input[type="search"]');
        const control = search.closest('.worklist-search-control');
        const clear = clientPicker.querySelector('[data-existing-client-clear]');
        const status = clientPicker.querySelector('[role="status"]');
        const results = clientPicker.querySelector('[data-existing-client-results]');
        const idleStatus = status.textContent;
        let searchTimer;
        let activeSearch;
        const resetResults = () => {
            clearTimeout(searchTimer);
            searchTimer = null;
            activeSearch?.abort();
            activeSearch = null;
            results.replaceChildren();
            results.hidden = true;
            search.setAttribute('aria-expanded', 'false');
            search.removeAttribute('aria-activedescendant');
            status.textContent = idleStatus;
        };
        const changeSelection = client => {
            existingClientField.value = client?.id ?? '';
            clientRelationship.value = '';
            clearFieldMessage(clientRelationship);
            syncExistingClient(client, !client);
            syncControls();
            render();
            clientPicker.querySelector('[data-existing-client-error]')?.remove();
            existingClientField.dispatchEvent(new Event('change', { bubbles: true }));
        };
        const findClients = async (explicit = false) => {
            resetResults();
            const query = search.value.trim();
            if (explicit) {
                search.value = query;
                clear.hidden = !query;
                search.focus({ preventScroll: true });
            }
            if (query.length < 2) {
                if (explicit) status.textContent = 'Enter at least two characters to search.';
                return;
            }
            const controller = new AbortController();
            activeSearch = controller;
            status.textContent = 'Searching…';
            try {
                const url = new URL(clientPicker.dataset.url, location.href);
                url.searchParams.set('q', query);
                if (historicalIntake && branch?.value) url.searchParams.set('branch_id', branch.value);
                const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Client lookup failed');
                const clients = await response.json();
                if (controller.signal.aborted || !clientPicker.isConnected) return;
                status.textContent = clients.length ? idleStatus : 'No matching clients in your branch.';
                const match = query.toLowerCase();
                const digits = query.replace(/\D/g, '');
                const visibleClients = explicit ? clients : clients.slice(0, 5);
                for (const client of visibleClients) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'worklist-match';
                    button.id = 'intake-client-match-' + client.id;
                    button.tabIndex = -1;
                    button.setAttribute('role', 'option');
                    button.setAttribute('aria-selected', 'false');
                    const details = [
                        client.matched_case_code ? 'Case ' + client.matched_case_code : null,
                        client.contact_number,
                        client.address,
                    ].filter(Boolean);
                    const parts = [client.full_name, client.directory_id, ...details].filter(Boolean);
                    button.title = parts.join(' · ');
                    button.setAttribute('aria-label', button.title);
                    const name = document.createElement('span');
                    underlineSearchMatch(name, client.full_name, match);
                    const code = document.createElement('span');
                    code.className = 'existing-client-match-code';
                    underlineSearchMatch(code, client.directory_id, match);
                    button.append(name, code);
                    const detail = document.createElement('span');
                    detail.className = 'existing-client-match-detail';
                    const highlight = /^[+\d\s().-]+$/.test(query) && digits.length >= 2 ? digits : match;
                    underlineSearchMatch(detail, details.join(' · '), highlight);
                    if (detail.textContent) button.append(detail);
                    button.addEventListener('click', () => {
                        search.value = '';
                        clear.hidden = true;
                        resetResults();
                        changeSelection(client);
                        clientRelationship.focus();
                    });
                    results.append(button);
                }
                results.hidden = !results.children.length;
                search.setAttribute('aria-expanded', String(!results.hidden));
            } catch (error) {
                if (!controller.signal.aborted) status.textContent = 'Client search is unavailable. Try again.';
            } finally {
                if (activeSearch === controller) activeSearch = null;
            }
        };
        clientPicker.querySelector('[data-existing-client-find]').addEventListener('click', () => findClients(true));
        clear.addEventListener('click', () => {
            search.value = '';
            clear.hidden = true;
            resetResults();
            search.focus({ preventScroll: true });
        });
        search.addEventListener('input', () => {
            resetResults();
            clear.hidden = !search.value;
            if (search.value.trim().length >= 2) searchTimer = setTimeout(findClients, 300);
        });
        search.addEventListener('keydown', event => {
            if (event.isComposing) return;
            const options = [...results.children];
            const current = options.findIndex(option => option.id === search.getAttribute('aria-activedescendant'));
            if (event.key === 'Enter') {
                event.preventDefault();
                if (current >= 0) options[current].click();
                else findClients(true);
            } else if (event.key === 'Escape' && (!results.hidden || activeSearch || searchTimer)) {
                event.preventDefault();
                resetResults();
            } else if (options.length && ['ArrowDown', 'ArrowUp'].includes(event.key)) {
                event.preventDefault();
                const next = event.key === 'ArrowDown' ? current + 1 : (current < 0 ? options.length - 1 : current - 1);
                const index = (next + options.length) % options.length;
                options.forEach((option, i) => option.setAttribute('aria-selected', String(i === index)));
                search.setAttribute('aria-activedescendant', options[index].id);
                options[index].scrollIntoView({ block: 'nearest' });
            }
        });
        results.addEventListener('mousedown', event => {
            if (event.target.closest('button')) event.preventDefault();
        });
        control.addEventListener('focusout', event => {
            if (!control.contains(event.relatedTarget)) resetResults();
        });
        f.addEventListener('click', event => {
            if (!control.contains(event.target)) resetResults();
        });
        clientPicker.querySelector('[data-existing-client-change]').addEventListener('click', () => {
            changeSelection(null);
            search.focus();
        });
        syncExistingClient(savedClientSelection);
    }

    const getAutosaveFields = () => [...f.querySelectorAll('input, select, textarea')]
        .filter((field) => {
            if (!field.name || field.type === 'file') return false;
            if (['_token', 'package_allowance_context', 'package_configuration_context', 'custom_defaults_context',
                'promotion_context_token', 'promotion_review_token', 'promotion_acknowledged',
                'add_on_context_token', 'add_on_review_token', 'add_on_acknowledged'].includes(field.name)) return false;
            return true;
        });

    const saveLocalDraft = () => {
        try {
            const fields = {};
            getAutosaveFields().forEach((field) => {
                if (field.type === 'radio') {
                    if (!(field.name in fields)) fields[field.name] = '';
                    if (field.checked) fields[field.name] = field.value;
                    return;
                }

                if (field.type === 'checkbox') {
                    if (!Array.isArray(fields[field.name])) fields[field.name] = [];
                    if (field.checked) fields[field.name].push(field.value);
                    return;
                }

                if (field.hasAttribute('data-coverage-included')
                    && (field.value === '' || Number(field.value) === Number(field.dataset.defaultCoverage) && field.dataset.defaultCoverage !== '')) return;
                fields[field.name] = field.value;
            });

            localStorage.setItem(intakeAutosaveKey, JSON.stringify({
                // Only coverage values that differ from the admin defaults are saved.
                version: 2,
                savedAt: new Date().toISOString(),
                step,
                selectedAddOns: [...oldSelectedAddOns],
                fields,
            }));
        } catch (error) {
            // Browser storage can be disabled/full; final form submit still works.
        }
    };

    const restoreDraftPayload = (draft, { localAutosave = false } = {}) => {
        if (!draft?.fields || typeof draft.fields !== 'object') return false;
        previousIncludedChapel = '';
        chapelCoverageChoices.forEach(choice => { choice.checked = false; });
        try {
            restoredChapelPricing = JSON.parse(draft.fields.chapel_draft_pricing || 'null');
        } catch {
            restoredChapelPricing = null;
        }
        Object.entries(draft.fields).forEach(([name, value]) => {
            const match = name.match(/^add_on_quantities\[(\d+)\]$/);
            if (!match) return;
            const quantity = Number(value);
            addOnQuantities.set(match[1], Number.isInteger(quantity) && quantity > 0 ? quantity : 0);
        });

        const grouped = new Map();
        getAutosaveFields().forEach((field) => {
            if (!grouped.has(field.name)) grouped.set(field.name, []);
            grouped.get(field.name).push(field);
        });

        const restoredChanges = [];
        Object.entries(draft.fields).forEach(([name, value]) => {
            const fields = grouped.get(name) || [];
            const restoredElements = [];
            fields.forEach((field) => {
                if (field.type === 'radio') {
                    field.checked = String(field.value) === String(value);
                    restoredElements.push(field);
                    return;
                }

                if (field.type === 'checkbox') {
                    const values = Array.isArray(value) ? value.map(String) : [String(value)];
                    field.checked = values.includes(String(field.value));
                    restoredElements.push(field);
                    return;
                }

                // Older browser autosaves copied every prefilled value. Use the
                // current settings when we cannot tell a default from an override.
                const useCurrentCoverage = field.hasAttribute('data-coverage-included')
                    && (value === '' || value == null || (localAutosave && draft.version !== 2));
                field.value = useCurrentCoverage
                    ? field.dataset.defaultCoverage
                    : (value ?? '');
                restoredElements.push(field);
            });
            restoredChanges.push(...restoredElements);
        });
        if (existingClientField) {
            existingClientField.value = draft.fields.existing_client_id ?? '';
            syncExistingClient(String(savedClientSelection?.id) === existingClientField.value ? savedClientSelection : null);
        }
        if (viewingManual && !Object.hasOwn(draft.fields, 'custom_home_viewing_manual') && viewingRequested.value !== '') {
            viewingManual.value = '1';
        }

        // Preserve user-entered overrides before rendering dependent controls.
        if (deceasedAddr) deceasedAddr.dataset.manual = draft.fields.deceased_address && draft.fields.deceased_address !== draft.fields.client_address ? '1' : '';
        if (interment && draft.fields.interment_at) interment.dataset.autoFilled = '0';
        // Date pickers own a separate selected-date/display state. Restore all of it
        // before any handler recalculates ages or applies date constraints.
        restoringPickerState = true;
        try { restoredChanges.forEach(field => {
            if (!field._flatpickr) return;
            field.dataset.userTyped = '0';
            field.dataset.lastTypedValue = '';
            field.dataset.invalidTypedValue = '';
            field._flatpickr.setDate(field.value || null, false, 'Y-m-d');
        }); } finally { restoringPickerState = false; }

        // Cashless editors mirror named hidden fields; restore both before change handlers run.
        if (referenceNumber) referenceNumber.value = draft.fields.reference_number || draft.fields.bank_reference || '';
        const restoredCashlessType = draft.fields.cashless_type || '';
        document.querySelectorAll('[data-intake-cashless-panel]').forEach((panel) => {
            if (panel.dataset.intakeCashlessPanel !== restoredCashlessType) return;
            const mirrors = {
                '[data-intake-account-name-field]': 'account_name',
                '[data-intake-mobile-number-field]': 'mobile_number',
                '[data-intake-payment-channel-field]': 'payment_channel',
            };
            Object.entries(mirrors).forEach(([selector, name]) => {
                const editor = panel.querySelector(selector);
                if (editor) editor.value = draft.fields[name] || '';
            });
        });

        // Restore every value before handlers read dependent fields.
        restoreLegacyChapelCoverage(draft.fields);
        restoredChanges.filter((field) => !['radio', 'checkbox'].includes(field.type) || field.checked)
            .forEach((field) => field.dispatchEvent(new Event('change', { bubbles: true })));

        if (Array.isArray(draft.selectedAddOns)) {
            oldSelectedAddOns.clear();
            draft.selectedAddOns.forEach((id) => oldSelectedAddOns.add(String(id)));
        }

        const additionalRows = Object.entries(draft.fields)
            .filter(([name]) => /^additional_service_items\[\d+\]\[(description|amount)\]$/.test(name))
            .reduce((rows, [name, value]) => {
                const match = name.match(/^additional_service_items\[(\d+)\]\[(description|amount)\]$/);
                if (!match) return rows;
                const index = Number(match[1]);
                rows[index] = rows[index] || {};
                rows[index][match[2]] = value;
                return rows;
            }, []);

        if (additionalRows.some((row) => row && (row.description || row.amount))) {
            additionalItemsList?.replaceChildren();
            additionalItemIndex = 0;
            additionalRows.filter(Boolean).forEach((row) => addAdditionalServiceRow(row));
            restoredAutosaveAdditionalRows = true;
        }

        if (Number.isFinite(Number(draft.step))) {
            maxUnlockedStep = Math.max(maxUnlockedStep, Math.min(totalSteps, Number(draft.step)));
        }

        return true;
    };

    const initializeBranchState = () => {
        if (isOtherEntryMode) {
            if (serverOldBranchExists) {
                branch.value = serverInitialBranchId;
            } else {
                branch.value = '';
            }
        } else {
            branch.value = serverInitialBranchId || branch.value;
        }
    };

    initializeBranchState();
    const restoredServerDraft = !@json(session()->hasOldInput()) && restoreDraftPayload(serverDraftPayload);
    restoreLegacyChapelCoverage({ chapel_coverage_flow: @json((bool) $intakeValue('chapel_coverage_flow')) });
    syncPayMethod();
    syncBranch(false);
    syncRequestDate();
    syncPackageModeFromSelection();
    syncControls();
    syncAge();
    syncDateConstraints();
    if (restoredAutosaveAdditionalRows) {
        // Autosaved manual charge rows were restored already.
    } else if (Array.isArray(oldAdditionalItems) && oldAdditionalItems.length) {
        oldAdditionalItems.forEach((item) => addAdditionalServiceRow(item || {}));
    } else if (num(addAmt?.value) > 0 || (additionalServices?.value || '').trim()) {
        addAdditionalServiceRow({ description: additionalServices?.value || '', amount: addAmt?.value || '' });
    }
    syncPreferredPackage();
    renderPkg(false);
    render();
    go(initialStep >= 5 ? 4 : initialStep, { scroll: false, smooth: false });
    try {
        const recovery = JSON.parse(localStorage.getItem(intakeAutosaveKey) || 'null');
        const hasRecoverableEntries = Object.entries(recovery?.fields || {}).some(([name, value]) => {
            const userFields = /^(client_(first_name|middle_name|last_name|suffix|contact_number|address)|deceased_(first_name|middle_name|last_name|suffix|address)|born|died|package_id|custom_package_(name|price)|custom_other_inclusions|senior_citizen_id_number|viewing_type|chapel_address|wake_(location|start_date|end_date|start_time|end_time)|funeral_service_at|interment_at|place_of_cemetery|reporter_(name|contact))$/;
            if (['custom_service_types[]', 'custom_freebie_catalog_ids[]'].includes(name)) return Array.isArray(value) && value.length > 0;
            return (userFields.test(name) || /^additional_service_items\[\d+\]\[description\]$/.test(name)) && String(value ?? '').trim() !== '';
        });
        if (recovery?.fields && hasRecoverableEntries && !@json(session()->hasOldInput())) {
            const notice = document.createElement('div');
            notice.id = 'intake_autosave_recovery';
            notice.setAttribute('role', 'region');
            notice.setAttribute('aria-labelledby', 'intake_recovery_title');
            const icon = document.createElement('span');
            icon.className = 'recovery-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = '<i class="bi bi-clock-history"></i>';
            const content = document.createElement('div');
            content.className = 'recovery-content';
            const title = document.createElement('h4');
            title.id = 'intake_recovery_title';
            title.textContent = 'Restore your unsaved intake?';
            const copy = document.createElement('p');
            copy.textContent = 'This browser saved a copy of your entries. Restore it to pick up where you left off.';
            const actions = document.createElement('div');
            actions.className = 'recovery-actions';
            const restore = document.createElement('button');
            restore.type = 'button';
            restore.id = 'restore_intake_autosave';
            restore.textContent = 'Restore autosave';
            restore.addEventListener('click', () => {
                restoreDraftPayload(recovery, { localAutosave: true });
                syncPayMethod();
                syncPackageModeFromSelection();
                render();
                go(Math.max(1, Math.min(4, Number(recovery.step) || 1)), { scroll: false, smooth: false });
                notice.remove();
                showBranchToast(restoredServiceMessage, 6000);
            });
            const dismiss = document.createElement('button');
            dismiss.type = 'button';
            dismiss.id = 'dismiss_intake_autosave';
            dismiss.textContent = 'Dismiss autosave';
            dismiss.addEventListener('click', () => {
                localStorage.removeItem(intakeAutosaveKey);
                notice.remove();
            });
            actions.append(restore, dismiss);
            content.append(title, copy, actions);
            notice.append(icon, content);
            document.getElementById('intakeFormContent')?.prepend(notice);
        }
    } catch (error) {
        // Recovery is optional when storage is unavailable.
    }
    if (restoredServerDraft) {
        showBranchToast(@json($intakeDraft ? 'Resuming '.$intakeDraft->draft_number : 'Draft restored.'), 5000);
    } else if (isOtherEntryMode) {
        showBranchToast(branchPromptMessage());
    }

    // ── Cancel button & dirty-state tracking ─────────────────────────────
    const cancelBtn      = document.getElementById('intakeCancelBtn');
    const cancelModal    = document.getElementById('intakeCancelModal');
    const cancelKeep     = document.getElementById('intakeCancelModalKeep');
    const cancelDiscard  = document.getElementById('intakeCancelModalDiscard');
    const cancelSaveDraft = document.getElementById('intakeCancelModalSaveDraft');
    const cancelBackdrop = document.getElementById('intakeCancelModalBackdrop');
    const saveDraftBtn   = document.getElementById('saveIntakeDraft');
    const cancelUrl      = @json($cancelUrl ?? '/');

    let formIsDirty = isIntakeModal && @json($errors->any());
    let modalSubmitting = false;
    let autosaveTimer = null;
    const queueLocalDraftSave = () => {
        window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(saveLocalDraft, 350);
    };

    // Mark dirty on any user interaction with the form
    const autosaveOnEdit = event => {
        if (event.target.id === 'intake-client-search') return;
        document.getElementById('intake_autosave_recovery')?.remove();
        formIsDirty = true;
        queueLocalDraftSave();
    };
    f.addEventListener('input', autosaveOnEdit, { passive: true });
    f.addEventListener('change', autosaveOnEdit, { passive: true });

    let cancelReturnFocus;
    const openCancelModal  = () => {
        cancelReturnFocus = document.activeElement;
        if (isIntakeModal && window.parent.showStaffIntakeConfirmation?.(window)) return;
        setIntakeDialog(cancelModal, true, cancelKeep);
    };
    const closeCancelModal = () => {
        setIntakeDialog(cancelModal, false);
    };
    const doCancel = () => {
        if (isIntakeModal && window.parent !== window) {
            window.clearTimeout(autosaveTimer);
            if (formIsDirty) {
                try { localStorage.removeItem(intakeAutosaveKey); } catch {}
            }
            window.parent.closeStaffIntake?.(window);
            return;
        }
        if (!window.SabanganNavigate?.(cancelUrl)) window.location.href = cancelUrl;
    };
    const saveDraftFromCancel = () => {
        closeCancelModal();
        if (currentStepField) currentStepField.value = String(step);

        if (saveDraftBtn && typeof f.requestSubmit === 'function') {
            f.requestSubmit(saveDraftBtn);
            return;
        }

        saveDraftBtn?.click();
    };

    const requestClose = () => {
        if (modalSubmitting || cancelModal?.open) return;
        formIsDirty ? openCancelModal() : doCancel();
    };
    cancelBtn?.addEventListener('click', requestClose);

    if (isIntakeModal) {
        window.requestStaffIntakeClose = requestClose;
        window.resolveStaffIntakeConfirmation = decision => {
            if (decision === 'keep') cancelReturnFocus?.focus({ preventScroll: true });
            if (decision === 'draft') saveDraftFromCancel();
            if (decision === 'discard') doCancel();
        };
        f.addEventListener('submit', event => {
            if (event.defaultPrevented) return;
            if (modalSubmitting) {
                event.preventDefault();
                return;
            }
            modalSubmitting = true;
            window.clearTimeout(autosaveTimer);
        });
    }

    cancelKeep?.addEventListener('click',     closeCancelModal);
    cancelBackdrop?.addEventListener('click', closeCancelModal);
    cancelDiscard?.addEventListener('click',  doCancel);
    cancelSaveDraft?.addEventListener('click', saveDraftFromCancel);

    const serverErrors = @json($errors->messages());
    if (Object.keys(serverErrors).length) {
        collectingValidation = true;
        Object.entries(serverErrors).forEach(([name, messages]) => {
            const field = fieldByName(name);
            if (field) showFieldError(field, messages.join(' '));
        });
        panels.forEach(panel => {
            if (panel.querySelector('[aria-invalid="true"]')) {
                attemptedSteps.add(Number(panel.dataset.step));
                collectValidationErrors(panel);
            }
        });
        collectingValidation = false;
        const firstPanel = panels.find(panel => panel.querySelector('[aria-invalid="true"]'));
        if (firstPanel) {
            maxUnlockedStep = Math.max(maxUnlockedStep, Number(firstPanel.dataset.step));
            go(Number(firstPanel.dataset.step));
        }
    }

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || e.defaultPrevented) return;
        if (cancelModal && !cancelModal.classList.contains('hidden')) {
            closeCancelModal();
        } else if (isIntakeModal && !document.querySelector('dialog[open]')) {
            e.preventDefault();
            requestClose();
        }
    });
    // ─────────────────────────────────────────────────────────────────────
})();
</script>
