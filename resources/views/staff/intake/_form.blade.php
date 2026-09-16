@php
    $intakeDraft = $intakeDraft ?? null;
    $intakeDraftPayload = $intakeDraftPayload ?? null;
    $isOtherEntryMode = !empty($entryMode) && $entryMode === 'other';
    $otherBranchCutoffHour = max(min((int) config('funeral.other_branch_report_cutoff_hour', 18), 23), 0);
    $todayStart = now()->copy()->startOfDay();
    $otherBranchCutoffAt = $todayStart->copy()->addHours($otherBranchCutoffHour);
    $otherBranchWindowClosed = $isOtherEntryMode && now()->gt($otherBranchCutoffAt);
    $otherBranchReportedDefault = old('reported_at', $isOtherEntryMode
        ? now()->copy()->min($otherBranchCutoffAt)->format('Y-m-d\TH:i')
        : null);

    $initialSelectedBranchId = old('branch_id', $defaultBranchId ?? auth()->user()->branch_id);
    $oldSelectedAddOns = array_map('strval', (array) old('selected_add_ons', []));
@endphp

<div class="intake-root w-full p-0 m-0 text-slate-800 font-sans border-0 shadow-none rounded-none">
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
    .form-select.field-error,
    .schedule-time-display.field-error {
        border-color: #e11d48 !important;
        background: #fff7f8 !important;
        color: #9f1239 !important;
        box-shadow: 0 0 0 4px rgba(225,29,72,.08) !important;
    }
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
    .time-input-wrap::before {
        content: "\F293";
        position: absolute;
        left: 0.95rem;
        top: 50%;
        z-index: 1;
        transform: translateY(-50%);
        color: #7A8076;
        font-family: "bootstrap-icons";
        font-size: 0.95rem;
        pointer-events: none;
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
        padding: 0 2.6rem 0 2.5rem;
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

    .pkg-premium-card {
        border: 1.5px solid #e4e8ef !important;
        border-radius: 16px !important;
        background: #ffffff !important;
        display: flex !important; flex-direction: column !important;
        padding: 0 !important;
        box-shadow: 0 1px 4px rgba(15,23,42,.05), 0 4px 14px rgba(15,23,42,.04) !important;
        overflow: hidden;
        min-height: 380px;
        transition: all .22s cubic-bezier(.4,0,.2,1) !important;
    }
    .pkg-premium-card:hover { border-color: #b0bfd8 !important; box-shadow: 0 8px 28px rgba(27,51,88,.13) !important; transform: translateY(-3px) !important; }
    .pkg-card-featured { border-color: #1b3358 !important; box-shadow: 0 6px 24px rgba(27,51,88,.18) !important; }
    .pkg-card-featured:hover { box-shadow: 0 12px 36px rgba(27,51,88,.22) !important; }

    .pkg-featured-item { position: relative; padding-top: 14px; }
    .pkg-badge-recommended {
        position: absolute; top: 0; left: 50%; transform: translateX(-50%);
        background: #1b3358; color: #fff;
        font-size: 9px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase;
        padding: 4px 14px; border-radius: 0 0 10px 10px; z-index: 6; white-space: nowrap;
    }

    .pkg-card-body { flex: 1; padding: 22px 22px 14px; }
    .pkg-tier-label { font-size: 10px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: #6b7e96; margin-bottom: 5px; }
    .pkg-name { font-size: 18px; font-weight: 800; color: #0d1f38; margin-bottom: 6px; line-height: 1.2; }
    .pkg-price { font-size: 30px; font-weight: 900; color: #0d1f38; line-height: 1; margin-bottom: 5px; }
    .pkg-price sub { font-size: 14px; font-weight: 700; vertical-align: super; }
    .pkg-price-note { font-size: 11px; color: #9baec8; margin-bottom: 14px; }
    .pkg-promo-badge { display: inline-block; font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; background: #d1fae5; color: #065f46; padding: 3px 8px; border-radius: 999px; margin-bottom: 10px; }

    .pkg-features-list { list-style: none; padding: 0; margin: 14px 0 0; border-top: 1px solid #f0f3f8; padding-top: 14px; display: flex; flex-direction: column; gap: 7px; }
    .pkg-feature-item { display: flex; align-items: flex-start; gap: 8px; font-size: 12px; font-weight: 500; color: #333333; line-height: 1.45; }
    .pkg-feature-check { color: #059669; font-weight: 900; flex-shrink: 0; margin-top: 1px; }
    .pkg-feature-plus { color: #B87956; font-weight: 900; flex-shrink: 0; font-size: 13px; line-height: 1; }

    .pkg-card-footer { padding: 0 22px 20px; }
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
    .pkg-premium-card:has(.package-radio:checked) { border-color: #1b3358 !important; background: #f6f9ff !important; }
    .pkg-premium-card:has(.package-radio:checked) .pkg-select-btn { background: #1b3358 !important; color: #fff !important; border-color: #1b3358 !important; }
    .pkg-card-custom:has(.package-radio:checked) .pkg-select-btn { background: #92400e !important; color: #fff !important; border-color: #92400e !important; }
    .pkg-premium-card:has(.package-radio:checked) .pkg-btn-unselected { display: none; }
    .pkg-premium-card:has(.package-radio:checked) .pkg-btn-selected { display: flex; align-items: center; gap: 5px; }

    /* Dark mode */
    html[data-theme='dark'] .pkg-premium-card { background: #152035 !important; border-color: #2a3f5f !important; }
    html[data-theme='dark'] .pkg-name, html[data-theme='dark'] .pkg-price { color: #d8ecff !important; }
    html[data-theme='dark'] .pkg-tier-label, html[data-theme='dark'] .pkg-price-note { color: #7a9cc0 !important; }
    html[data-theme='dark'] .pkg-features-list { border-color: #1e3050 !important; }
    html[data-theme='dark'] .pkg-feature-item { color: #c8ddf2 !important; }
    html[data-theme='dark'] .pkg-select-btn { background: #1c2f4a !important; border-color: #2a4060 !important; color: #c8ddf2 !important; }
    html[data-theme='dark'] .pkg-premium-card:has(.package-radio:checked) { background: #1a2f50 !important; border-color: #4a82c0 !important; }
    html[data-theme='dark'] .pkg-premium-card:has(.package-radio:checked) .pkg-select-btn { background: #1b3358 !important; }

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
    #intakeCancelModal { background: rgba(0,0,0,.45); }
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
    #structured_pricing_section,
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

    .time-input-wrap::before,
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

    .service-tool-card {
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

    #selected_add_ons_summary {
        grid-column: 1 / -1;
        min-width: 0 !important;
        width: 100% !important;
        border-color: var(--intake-border) !important;
        background: #C7D5BE !important;
        padding: 14px !important;
    }

    #selected_add_ons_summary > div {
        align-items: center !important;
    }

    #structured_pricing_section > button {
        align-self: center;
        white-space: nowrap;
    }

    #optional_add_ons_panel:not(.hidden),
    #package_adjustments_panel:not(.hidden) {
        position: fixed !important;
        inset: auto auto auto auto !important;
        left: 50% !important;
        top: 50% !important;
        z-index: 260 !important;
        display: flex !important;
        flex-direction: column !important;
        width: min(920px, calc(100vw - 32px)) !important;
        max-height: min(700px, calc(100vh - 40px)) !important;
        transform: translate(-50%, -50%) !important;
        margin: 0 !important;
        overflow: hidden !important;
        border: 1px solid var(--intake-border-strong) !important;
        border-radius: 8px !important;
        background: var(--intake-card) !important;
        color: var(--intake-text) !important;
    }

    #optional_add_ons_panel:not(.hidden) {
        width: min(820px, calc(100vw - 32px)) !important;
    }

    #optional_add_ons_panel > div:first-child,
    #package_adjustments_panel > .package-adjustments-head {
        flex: 0 0 auto;
        background: var(--intake-card-strong) !important;
        border-color: var(--intake-border) !important;
    }

    #optional_add_ons_panel > div:first-child {
        padding: 16px !important;
    }

    #optional_add_ons_panel > div:first-child > div,
    #package_adjustments_panel > .package-adjustments-head {
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

    #optional_add_ons_list,
    #package_adjustments_panel .package-adjustments-body {
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
    #billing_additional_services_section input[type="checkbox"],
    #package_adjustments_panel input[type="checkbox"] {
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
        margin-top: 28px;
        padding-top: 24px;
        border-top: 1px solid rgba(62, 74, 61, .18);
    }

    .service-details-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(300px, .65fr);
        gap: 16px;
        align-items: start;
    }

    .service-detail-panel {
        border: 1px solid var(--intake-border);
        border-radius: 8px;
        background: var(--intake-card-alt);
        padding: 18px;
    }

    .service-detail-panel-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(62, 74, 61, .16);
    }

    .service-detail-panel-header i {
        display: inline-flex;
        width: 34px;
        height: 34px;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--intake-border-strong);
        border-radius: 8px;
        color: var(--intake-dark);
    }

    .service-detail-panel-header h4 {
        margin: 0;
        font-family: var(--font-heading), "Syne", var(--font-body), sans-serif;
        font-size: 1rem;
        font-weight: 650;
        color: var(--intake-text);
    }

    .service-detail-panel-header p {
        margin-top: 2px;
        font-size: .82rem;
        font-weight: 500;
        color: var(--intake-muted);
    }

    .schedule-stack {
        display: grid;
        gap: 14px;
    }

    .schedule-field-row {
        display: grid;
        grid-template-columns: 170px minmax(0, 1fr);
        gap: 14px;
        align-items: start;
        padding: 14px;
        border: 1px solid var(--intake-border);
        border-radius: 8px;
        background: #DCE6D6;
    }

    .schedule-field-row:hover {
        background: var(--intake-hover);
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

    .service-info-stack {
        display: grid;
        gap: 14px;
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

    .service-photo-upload {
        margin-top: 16px;
        border: 1px dashed var(--intake-border-strong);
        border-radius: 8px;
        background: var(--intake-card-alt);
        padding: 16px;
    }

    @media (max-width: 1100px) {
        .service-details-grid {
            grid-template-columns: 1fr;
        }

        .schedule-field-row {
            grid-template-columns: 1fr;
        }
    }

    #package_adjustments_panel .package-adjustments-head {
        padding: 16px !important;
    }

    #package_adjustments_panel .package-adjustments-body {
        padding: 16px !important;
        background: var(--intake-card) !important;
    }

    #package_adjustments_panel .package-adjustments-body > .rounded-xl {
        border-radius: 8px !important;
        background: var(--intake-card-alt) !important;
        border-color: var(--intake-border) !important;
        padding: 16px !important;
    }

    #package_adjustments_panel .package-adjustments-body > .rounded-xl:hover {
        background: var(--intake-hover) !important;
    }

    #package_adjustments_panel .package-adjustments-body .rounded-lg {
        border-radius: 8px !important;
    }

    #package_adjustments_panel .shadow-sm {
        box-shadow: none !important;
    }

    #package_adjustments_panel .package-adjustments-body .grid .rounded-lg.bg-white {
        background: #F2F6EF !important;
        border: 1px solid var(--intake-border) !important;
    }

    #package_adjustments_panel .package-adjustments-body h6 {
        font-family: var(--font-heading), "Syne", var(--font-body), sans-serif !important;
        font-size: .92rem !important;
        font-weight: 650 !important;
        color: var(--intake-text) !important;
    }

    #package_adjustments_panel .package-adjustments-body .text-slate-500,
    #package_adjustments_panel .package-adjustments-body .text-slate-400 {
        color: var(--intake-muted) !important;
    }

    #optional_add_ons_panel > div:last-child,
    #package_adjustments_panel .package-adjustments-foot {
        flex: 0 0 auto;
        background: var(--intake-card-strong) !important;
        border-color: var(--intake-border) !important;
    }

    #optional_add_ons_panel > div:last-child,
    #package_adjustments_panel .package-adjustments-foot {
        padding: 14px 16px !important;
    }

    .intake-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 250;
        background: rgba(35, 40, 33, .38);
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
    </style>

    <div id="branch_toast" class="hidden fixed left-1/2 -translate-x-1/2 px-5 py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold transition-all duration-300 opacity-0 translate-y-[-8px] toast-pop intake-toast-branch">
        You're now recording for this branch.
    </div>

    <div id="package_toast" class="hidden fixed left-1/2 -translate-x-1/2 px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-bold transition-all duration-200 opacity-0 translate-y-[-8px] flex items-center gap-2 toast-pop intake-toast-package">
        <i class="bi bi-check2-circle text-lg"></i>
        <span class="package-toast-text">You selected a package.</span>
    </div>

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

    <div class="intake-section-shell">

        <div class="wizard-steps-shell hide-scrollbar" id="wizardSteps">
    @php
        $steps = [
            1 => 'Client Info',
            2 => 'Deceased',
            3 => 'Service Selection',
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

        <form method="POST" action="{{ $formAction ?? route('intake.main.store') }}" enctype="multipart/form-data" id="intakeWizardForm">
            @csrf

            <input type="hidden" name="branch_id" id="branch_id" value="{{ $initialSelectedBranchId }}">
            <input type="hidden" name="intake_draft_id" id="intake_draft_id" value="{{ $intakeDraft?->id }}">
            <input type="hidden" name="entry_mode" value="{{ $isOtherEntryMode ? 'other' : 'main' }}">
            <input type="hidden" name="current_step" id="current_step" value="{{ $initialStep ?? 1 }}">
            <input type="hidden" name="return_to" value="{{ $returnTo ?? '' }}">
            <input type="hidden" id="branch_code_main_default" value="{{ optional($branches->first())->branch_code ?? 'BR001' }}">

            @if($intakeDraft)
                <div class="mx-4 mb-3 rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-surface)] px-4 py-3 text-sm text-[var(--color-text-secondary)] flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="font-semibold text-[var(--color-text-primary)]">Resuming {{ $intakeDraft->draft_number }}</div>
                        <div class="text-xs">Last saved {{ optional($intakeDraft->last_saved_at)->format('Y-m-d H:i') ?? 'recently' }}</div>
                    </div>
                    <a href="{{ route('funeral-cases.index', ['tab' => 'draft', 'record_scope' => 'main']) }}" class="ops-btn-outline inline-flex items-center justify-center gap-2">
                        <i class="bi bi-list-check" aria-hidden="true"></i>
                        Intake Drafts
                    </a>
                </div>
            @endif

            <div id="intakeFormContent">
                <section class="wizard-panel" data-step="1">
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
                                <label class="field-label">Reporter Name</label>
                                <input type="text" name="reporter_name" id="reporter_name" value="{{ old('reporter_name') }}" data-validate="letters-spaces" data-label="reporter name" class="form-input">
                            </div>

                            <div>
                                <label class="field-label">Contact Number</label>
                                <input type="text" name="reporter_contact" value="{{ old('reporter_contact') }}" data-validate="digits" data-label="reporter contact number" class="form-input">
                            </div>

                            <div>
                                <label class="field-label">Encoded Date</label>
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
                                    <label class="field-label">First Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="client_first_name" value="{{ old('client_first_name') }}" data-label="client first name" class="form-input" placeholder="First name" required>
                                    @error('client_first_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Middle Name</label>
                                    <input type="text" name="client_middle_name" value="{{ old('client_middle_name') }}" data-label="client middle name" class="form-input" placeholder="Middle name">
                                    @error('client_middle_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Last Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="client_last_name" value="{{ old('client_last_name') }}" data-label="client last name" class="form-input" placeholder="Last name" required>
                                    @error('client_last_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Suffix</label>
                                    <div class="form-select-wrap">
                                        <select name="client_suffix" data-label="client suffix" class="form-input">
                                            <option value="" {{ old('client_suffix') === null || old('client_suffix') === '' ? 'selected' : '' }}>None</option>
                                            @foreach(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $suffixOption)
                                                <option value="{{ $suffixOption }}" {{ old('client_suffix') === $suffixOption ? 'selected' : '' }}>{{ $suffixOption }}</option>
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
                                    <label class="field-label">Relationship to Deceased <span class="text-rose-500">*</span></label>
                                    <div class="form-select-wrap">
                                        <select name="client_relationship" data-label="relationship to the deceased" class="form-input" required>
                                            <option value="">Select relationship</option>
                                            @foreach(['Father', 'Mother', 'Spouse', 'Child', 'Sibling', 'Relative', 'Guardian', 'Other'] as $relationship)
                                                <option value="{{ $relationship }}" {{ old('client_relationship') === $relationship ? 'selected' : '' }}>
                                                    {{ $relationship }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="field-label">Mobile Number <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        name="client_contact_number"
                                        value="{{ old('client_contact_number') }}"
                                        data-validate="philippine-mobile"
                                        data-label="contact number"
                                        inputmode="tel"
                                        maxlength="15"
                                        title="Philippine mobile number"
                                        class="form-input"
                                        placeholder="09XXXXXXXXX"
                                        required
                                    >
                                    <p class="intake-field-help">Use an active Philippine mobile number for updates and coordination.</p>
                                </div>

                                <div class="intake-full-field">
                                    <label class="field-label">Complete Address <span class="text-rose-500">*</span></label>
                                    <input type="text" name="client_address" id="client_address" value="{{ old('client_address') }}" data-label="client address" class="form-input" placeholder="House no., street, barangay, city/municipality, province" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="2">
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
                                    <label class="field-label">First Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="deceased_first_name" value="{{ old('deceased_first_name') }}" data-label="deceased first name" class="form-input" placeholder="First name" required>
                                    @error('deceased_first_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Middle Name</label>
                                    <input type="text" name="deceased_middle_name" value="{{ old('deceased_middle_name') }}" data-label="deceased middle name" class="form-input" placeholder="Middle name">
                                    @error('deceased_middle_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Last Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="deceased_last_name" value="{{ old('deceased_last_name') }}" data-label="deceased last name" class="form-input" placeholder="Last name" required>
                                    @error('deceased_last_name') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Suffix</label>
                                    <div class="form-select-wrap">
                                        <select name="deceased_suffix" data-label="deceased suffix" class="form-input">
                                            <option value="" {{ old('deceased_suffix') === null || old('deceased_suffix') === '' ? 'selected' : '' }}>None</option>
                                            @foreach(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $suffixOption)
                                                <option value="{{ $suffixOption }}" {{ old('deceased_suffix') === $suffixOption ? 'selected' : '' }}>{{ $suffixOption }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('deceased_suffix') <p class="intake-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="intake-field-section">
                            <div class="intake-section-kicker"><i class="bi bi-calendar-heart"></i><span>Life dates and address</span></div>
                            <div class="intake-info-grid deceased-meta">
                                <div>
                                    <label class="field-label">Date of Birth <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" name="born" id="born" value="{{ old('born') }}" data-label="birthdate" class="form-input pr-10 cursor-pointer" placeholder="Select birth date" autocomplete="off" required>
                                        <span id="born_picker_trigger" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                            <i class="bi bi-calendar-event text-lg"></i>
                                        </span>
                                    </div>
                                    @error('born')
                                        <p class="intake-field-error">{{ $message }}</p>
                                    @enderror
                                    <p id="born_error" class="intake-field-error hidden"></p>
                                </div>

                                <div>
                                    <label class="field-label">Date of Death <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" name="died" id="died" value="{{ old('died') }}" data-label="date of death" class="form-input pr-10 cursor-pointer" placeholder="Select date of death" autocomplete="off" required>
                                        <span id="died_picker_trigger" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                            <i class="bi bi-calendar-event text-lg"></i>
                                        </span>
                                    </div>
                                    @error('died')
                                        <p class="intake-field-error">{{ $message }}</p>
                                    @enderror
                                    <p id="died_error" class="intake-field-error hidden"></p>
                                </div>

                                <div>
                                    <label class="field-label">Age</label>
                                    <input type="number" name="age" id="age" value="{{ old('age') }}" class="form-input bg-slate-50 text-slate-500 cursor-not-allowed" readonly>
                                    <p class="intake-field-help">Auto-computed.</p>
                                </div>

                                <div class="intake-full-field">
                                    <label class="field-label">Complete Address <span class="text-rose-500">*</span></label>
                                    <input type="text" name="deceased_address" id="deceased_address" value="{{ old('deceased_address', old('client_address')) }}" data-label="deceased address" class="form-input" placeholder="House no., street, barangay, city/municipality, province" required>
                                </div>
                            </div>
                        </div>

                        <div class="intake-field-section">
                            <div class="intake-section-kicker"><i class="bi bi-patch-check"></i><span>Senior citizen verification</span></div>
                            <div class="intake-info-grid">
                                <div>
                                    <label class="field-label">Senior Citizen Status</label>
                                    <div class="form-select-wrap">
                                        <select name="senior_citizen_status" id="senior_citizen_status" data-label="senior citizen status" class="form-input w-full">
                                            <option value="0" {{ old('senior_citizen_status', '0') === '0' ? 'selected' : '' }}>No / Standard</option>
                                            <option value="1" {{ old('senior_citizen_status') === '1' ? 'selected' : '' }}>Yes / Eligible</option>
                                        </select>
                                    </div>
                                    <p class="intake-field-help">Discount applies automatically when eligible.</p>
                                </div>

                                <div id="senior_id_wrap" class="hidden">
                                    <label class="field-label">Senior Citizen ID Number <span class="text-slate-400 normal-case tracking-normal font-normal">(Optional)</span></label>
                                    <input type="text" name="senior_citizen_id_number" id="senior_citizen_id_number" value="{{ old('senior_citizen_id_number') }}" data-label="Senior Citizen ID number" class="form-input" placeholder="Enter Senior Citizen ID number">
                                </div>

                                <div id="senior_proof_wrap" class="intake-full-field hidden">
                                    <label class="field-label">Upload Senior ID / Certificate <span class="text-slate-400 normal-case tracking-normal font-normal">(Optional)</span></label>
                                    <input type="file" name="senior_proof" id="senior_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" class="form-input">
                                    <p class="intake-field-help">Accepted: JPG, PNG, WEBP, PDF. Max 5MB.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="3">
                    <div class="pkg-grid-header">
                        <div>
                            <p class="pkg-section-title">Service Packages</p>
                            <p class="pkg-section-sub">Select the appropriate service package for this case.</p>
                        </div>
                        <div id="package_error" class="intake-field-error hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2">
                            <i class="bi bi-exclamation-circle-fill"></i> Selection required
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" id="packageCardList">
                        @foreach($packages as $pkg)
                            @php
                                $promoNow = $pkg->promo_is_active
                                    && (!$pkg->promo_starts_at || $pkg->promo_starts_at->lte(now()))
                                    && (!$pkg->promo_ends_at || $pkg->promo_ends_at->gte(now()));

                                $inclusionItems = $pkg->inclusionNames();
                                $freebieItems = $pkg->freebieNames();
                                $structuredInclusions = $pkg->packageInclusions->map(fn ($inclusion) => [
                                    'service_type' => \App\Models\Package::normalizeServiceType($inclusion->service_type),
                                    'name' => $inclusion->inclusion_name,
                                    'included_kilometers' => $inclusion->included_kilometers,
                                    'price_per_excess_kilometer' => (float) $inclusion->price_per_excess_kilometer,
                                    'included_days' => $inclusion->included_days,
                                    'price_per_extended_day' => (float) $inclusion->price_per_extended_day,
                                    'casket_type' => $inclusion->casket_type,
                                    'casket_catalog_id' => $inclusion->casket_catalog_id,
                                    'casket' => $inclusion->casketCatalog ? [
                                        'id' => $inclusion->casketCatalog->id,
                                        'name' => $inclusion->casketCatalog->name,
                                        'material' => $inclusion->casketCatalog->type_or_material,
                                        'reference_value' => (float) $inclusion->casketCatalog->standard_price,
                                    ] : null,
                                ])->values();
                                $structuredFreebies = $pkg->packageFreebies->map(fn ($freebie) => [
                                    'name' => $freebie->freebie_name,
                                    'quantity' => $freebie->quantity,
                                    'unit' => $freebie->unit,
                                ])->values();
                                $includedCasket = $structuredInclusions->firstWhere('service_type', \App\Models\Package::SERVICE_CASKET);
                                $inclusionCount = $structuredInclusions->isNotEmpty() ? $structuredInclusions->count() : count($inclusionItems);
                                $freebieCount = $structuredFreebies->isNotEmpty() ? $structuredFreebies->count() : count($freebieItems);
                                $includedCasketName = $includedCasket['casket']['name'] ?? $includedCasket['casket_type'] ?? $pkg->coffin_type ?? 'Not configured';
                            @endphp

                            <div class="package-card-item">
                                <div class="package-card pkg-premium-card w-full h-full cursor-pointer" role="radio" tabindex="0" aria-label="{{ $pkg->name }} package">
                                    <input
                                        type="radio"
                                        name="package_id"
                                        value="{{ $pkg->id }}"
                                        class="package-radio"
                                        data-name="{{ $pkg->name }}"
                                        data-price="{{ $pkg->price }}"
                                        data-promo-now="{{ $promoNow ? '1' : '0' }}"
                                        data-promo-type="{{ $pkg->promo_value_type }}"
                                        data-promo-value="{{ $pkg->promo_value }}"
                                        data-promo-label="{{ $pkg->promo_label }}"
                                        data-inclusions="{{ implode("\n", $inclusionItems) }}"
                                        data-freebies="{{ implode("\n", $freebieItems) }}"
                                        data-structured-inclusions="{{ $structuredInclusions->toJson() }}"
                                        data-structured-freebies="{{ $structuredFreebies->toJson() }}"
                                        data-included-casket="{{ json_encode($includedCasket['casket'] ?? ['name' => $includedCasket['casket_type'] ?? $pkg->coffin_type, 'reference_value' => 0]) }}"
                                        {{ (string) old('package_id') === (string) $pkg->id ? 'checked' : '' }}
                                        required
                                    >

                                    <div class="pkg-card-body flex h-full flex-col">
                                        <div class="min-h-[152px]">
                                            <div class="pkg-name">{{ $pkg->name }}</div>
                                            <div class="pkg-price"><sub>&#8369;</sub>{{ number_format($pkg->price, 0) }}</div>
                                            @if($promoNow)
                                                <div class="pkg-promo-badge">{{ $pkg->promo_label }}</div>
                                            @else
                                                <p class="pkg-price-note">Base package price</p>
                                            @endif

                                            <div class="mt-4 rounded-lg border border-slate-200 bg-white/70 px-3 py-2 text-[11px] font-semibold text-slate-600">
                                                <div class="text-[10px] font-black uppercase tracking-widest text-[#5F685F]">Included Casket</div>
                                                <div class="mt-1 truncate">{{ $includedCasketName }}</div>
                                            </div>

                                            <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                                                <div class="rounded-lg bg-slate-50 px-2 py-2 ring-1 ring-slate-200">
                                                    <div class="text-base font-black text-slate-900">{{ $inclusionCount }}</div>
                                                    <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Inclusions</div>
                                                </div>
                                                <div class="rounded-lg bg-slate-50 px-2 py-2 ring-1 ring-slate-200">
                                                    <div class="text-base font-black text-slate-900">{{ $freebieCount }}</div>
                                                    <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Freebies</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pkg-card-footer grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <button type="button" class="package-details-btn rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900" data-package-value="{{ $pkg->id }}">
                                            <i class="bi bi-eye mr-1"></i> View Details
                                        </button>
                                        <div class="pkg-select-btn justify-center">
                                            <span class="pkg-btn-unselected">Select Package</span>
                                            <span class="pkg-btn-selected"><i class="bi bi-check-circle-fill"></i> Selected</span>
                                        </div>
                                        <div class="check-dot hidden"></div>{{-- JS compat --}}
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if(empty($entryMode) || $entryMode === 'main')
                            <div class="package-card-item">
                                <div class="package-card pkg-premium-card pkg-card-custom w-full h-full cursor-pointer" role="radio" tabindex="0" aria-label="Custom package">
                                    <input
                                        type="radio"
                                        name="package_id"
                                        value="custom"
                                        id="custom_package_radio"
                                        class="package-radio"
                                        data-name="Client Preference"
                                        data-price="0"
                                        data-inclusions=""
                                        data-freebies=""
                                        data-structured-inclusions="[]"
                                        data-structured-freebies="[]"
                                        data-included-casket="{}"
                                    >

                                    <div class="pkg-card-body">
                                        <div class="pkg-tier-label" style="color:#b45309;">Client Preference</div>
                                        <div class="pkg-name" style="color:#78350f;">Custom</div>
                                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-sm font-black text-amber-900">
                                            Price set during package setup.
                                        </div>
                                        <p class="pkg-price-note">Manual package details are entered below.</p>
                                    </div>

                                    <div class="pkg-card-footer grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <button type="button" class="package-details-btn rounded-lg border border-amber-200 bg-white px-3 py-2 text-xs font-bold text-amber-800 hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-700" data-package-value="custom">
                                            <i class="bi bi-eye mr-1"></i> View Details
                                        </button>
                                        <div class="pkg-select-btn justify-center">
                                            <span class="pkg-btn-unselected">Build Package</span>
                                            <span class="pkg-btn-selected"><i class="bi bi-check-circle-fill"></i> Selected</span>
                                        </div>
                                        <div class="check-dot hidden"></div>{{-- JS compat --}}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if(empty($entryMode) || $entryMode === 'main')
                        <div id="custom_package_fields" class="hidden mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 rounded-xl border border-amber-100 bg-amber-50/70 px-4 py-4">
                            <div class="md:col-span-2">
                                <label class="field-label">Client Preference Package Name</label>
                                <input type="text" name="custom_package_name" id="client_pref_package" value="{{ old('custom_package_name') }}" class="form-input" placeholder="Custom package name">
                            </div>

                            <div>
                                <label class="field-label">Inclusions (comma or line separated)</label>
                                <textarea name="custom_package_inclusions" id="client_pref_inclusions" class="form-textarea" rows="3" placeholder="Hearse, viewing chapel, etc.">{{ old('custom_package_inclusions') }}</textarea>
                            </div>

                            <div>
                                <label class="field-label">Freebies / Notes</label>
                                <textarea name="custom_package_freebies" id="client_pref_freebies" class="form-textarea" rows="3" placeholder="Flower standee, mass card, etc.">{{ old('custom_package_freebies') }}</textarea>
                            </div>

                            <div>
                                <label class="field-label">Preferred Price</label>
                                <input type="number" step="0.01" min="0" name="custom_package_price" id="client_pref_price" value="{{ old('custom_package_price') }}" class="form-input" placeholder="0.00">
                            </div>
                        </div>
                    @endif

                    <ul id="selected_package_inclusions" class="hidden"></ul>
                    <ul id="selected_package_freebies" class="hidden"></ul>

                    <div id="package_details_modal" class="hidden fixed inset-0 z-[210] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="package_details_title">
                        <div class="absolute inset-0 bg-slate-950/50" data-package-details-close></div>
                        <div class="relative max-h-[88vh] w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                                <div class="min-w-0">
                                    <h4 id="package_details_title" class="text-lg font-black text-slate-900">Package Details</h4>
                                    <p id="package_details_subtitle" class="mt-1 text-xs font-semibold text-slate-500">Review included limits and possible extra charges before selecting.</p>
                                </div>
                                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900" aria-label="Close package details" data-package-details-close>
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="border-b border-slate-200 px-5 py-3">
                                <div class="flex flex-wrap gap-2" role="tablist" aria-label="Package detail sections">
                                    <button type="button" class="package-detail-tab rounded-lg px-3 py-2 text-xs font-black uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-slate-900" data-package-detail-tab="services" role="tab">Included Services</button>
                                    <button type="button" class="package-detail-tab rounded-lg px-3 py-2 text-xs font-black uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-slate-900" data-package-detail-tab="freebies" role="tab">Freebies</button>
                                    <button type="button" class="package-detail-tab rounded-lg px-3 py-2 text-xs font-black uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-slate-900" data-package-detail-tab="coverage" role="tab">Included Limits & Extra Charges</button>
                                </div>
                            </div>
                            <div class="max-h-[58vh] overflow-y-auto px-5 py-4">
                                <div id="package_detail_services" class="package-detail-panel space-y-2" role="tabpanel"></div>
                                <div id="package_detail_freebies" class="package-detail-panel hidden space-y-2" role="tabpanel"></div>
                                <div id="package_detail_coverage" class="package-detail-panel hidden space-y-2" role="tabpanel"></div>
                            </div>
                            <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-4">
                                <button type="button" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2" data-package-details-close>
                                    Done
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="intake_service_modal_backdrop" class="intake-modal-backdrop hidden"></div>

                    <div class="service-selection-actions">
                    <div id="structured_pricing_section" class="hidden service-tool-card">
                        <div class="min-w-0">
                            <div>
                                <h5 class="service-tool-title">
                                    <i class="bi bi-sliders2"></i> Package Adjustments
                                </h5>
                                <p class="service-tool-help">Review package upgrades, covered items, and excess usage before adding optional services.</p>
                            </div>
                            <div class="service-tool-value">
                                Total adjustments: &#8369; <span id="structured_charges_total">0.00</span>
                            </div>
                        </div>
                        <button type="button" id="open_package_adjustments_btn" class="inline-flex items-center gap-2 rounded-lg bg-[#3E4A3D] px-4 py-2 text-xs font-bold text-white hover:bg-[#2f382e]">
                            <i class="bi bi-sliders"></i> Open Adjustments
                        </button>
                    </div>

                    <div id="optional_add_ons_section" class="hidden service-tool-card">
                        <div class="min-w-0">
                            <div>
                                <h5 class="service-tool-title">
                                    <i class="bi bi-plus-square"></i> Optional Add-ons
                                </h5>
                                <p class="service-tool-help">Select paid catalog services only when requested.</p>
                            </div>
                            <div class="service-tool-value">
                                Selected total: &#8369; <span id="selected_add_ons_total">0.00</span>
                            </div>
                        </div>

                        <div id="selected_add_ons_summary" class="min-w-[220px] rounded-lg border border-[#C9C5BB] bg-white px-4 py-3 text-sm text-[#333333]">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-bold">No add-ons selected.</div>
                                    <div class="mt-0.5 text-xs font-medium text-[#5F685F]">Optional add-ons are not included in the base package price.</div>
                                </div>
                                <button type="button" id="choose_add_ons_btn" class="inline-flex items-center gap-2 rounded-lg bg-[#3E4A3D] px-4 py-2 text-xs font-bold text-white hover:bg-[#2f382e] focus:outline-none focus:ring-2 focus:ring-[#3E4A3D] focus:ring-offset-2" aria-expanded="false" aria-controls="optional_add_ons_panel">
                                    <i class="bi bi-plus-circle"></i> Choose Add-ons
                                </button>
                            </div>
                            <div id="selected_add_ons_lines" class="mt-3 hidden space-y-2"></div>
                        </div>

                        <div id="optional_add_ons_panel" class="optional-add-ons-panel mt-4 hidden overflow-hidden rounded-xl border border-[#C9C5BB] bg-white" role="region" aria-label="Available optional add-ons">
                            <div class="border-b border-[#E5E0D5] bg-[#FBFAF7] p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <div class="service-tool-title"><i class="bi bi-plus-square"></i> Optional Add-ons</div>
                                        <div class="service-tool-help">Choose catalog add-ons first. Use manual charges only when the request is not listed.</div>
                                    </div>
                                    <button type="button" id="close_add_ons_btn" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[#C9C5BB] bg-[#F2F6EF] text-[#3E4A3D] hover:bg-[#DDE8D6]" aria-label="Close optional add-ons">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <div class="optional-add-ons-searchbar">
                                        <div class="relative min-w-0 flex-1">
                                            <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-[#687466]"></i>
                                            <input type="search" id="optional_add_ons_search" class="form-input" placeholder="Search add-ons..." aria-label="Search optional add-ons">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="optional_add_ons_list" class="max-h-[420px] overflow-y-auto p-4 space-y-2"></div>
                            <div id="billing_additional_services_section" class="mx-4 mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-600">Manual Charges</h5>
                                        <p class="mt-1 text-xs font-medium text-slate-500">Use this only for requested charges that are not listed in the add-ons catalog.</p>
                                    </div>
                                    <button type="button" id="add_additional_service_item" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100">
                                        <i class="bi bi-plus-circle"></i> Add Line
                                    </button>
                                </div>
                                <div id="additional_service_items_list" class="mt-4 space-y-2"></div>
                                <input type="hidden" name="additional_service_amount" id="additional_service_amount" value="{{ old('additional_service_amount') }}">
                                <input type="hidden" name="additional_services" id="additional_services" value="{{ old('additional_services') }}">
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E5E0D5] bg-[#FBFAF7] p-4">
                                <div class="text-xs font-bold text-[#5F685F]">Draft total: &#8369; <span id="draft_add_ons_total">0.00</span></div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" id="cancel_add_ons_btn" class="rounded-lg border border-[#C9C5BB] bg-white px-4 py-2 text-xs font-bold text-[#333333] hover:bg-[#F3F0E8] focus:outline-none focus:ring-2 focus:ring-[#3E4A3D] focus:ring-offset-2">Cancel</button>
                                    <button type="button" id="apply_add_ons_btn" class="inline-flex items-center gap-2 rounded-lg bg-[#3E4A3D] px-4 py-2 text-xs font-bold text-white hover:bg-[#2f382e] focus:outline-none focus:ring-2 focus:ring-[#3E4A3D] focus:ring-offset-2">
                                        <i class="bi bi-check2-circle"></i> Apply Selected Add-ons
                                    </button>
                                </div>
                            </div>
                        </div>
                        @error('selected_add_ons') <div class="intake-field-error">{{ $message }}</div> @enderror
                    </div>
                    </div>

                    <div id="package_adjustments_panel" class="package-adjustments-modal hidden" role="dialog" aria-modal="true" aria-labelledby="package_adjustments_title">
                        <div class="package-adjustments-head flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 p-4">
                            <div>
                                <h5 id="package_adjustments_title" class="service-tool-title">
                                    <i class="bi bi-sliders2"></i> Package Adjustments
                                </h5>
                                <p class="service-tool-help">Enter only actual excess usage or selected upgrades.</p>
                            </div>
                            <button type="button" id="close_package_adjustments_btn" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50" aria-label="Close package adjustments">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="package-adjustments-body p-4 space-y-4">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h6 class="text-sm font-black text-slate-900">Casket Selection</h6>
                                                <p id="included_casket_summary" class="mt-1 text-xs font-semibold text-slate-500">Included casket will be used.</p>
                                            </div>
                                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700">
                                                + &#8369; <span id="casket_adjustment_amount">0.00</span>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <input type="hidden" name="casket_selection_mode" value="included">
                                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-white px-3 py-3 hover:border-slate-300">
                                                <input type="checkbox" name="casket_selection_mode" value="replacement" class="mt-1 h-4 w-4 text-slate-900 focus:ring-slate-900" {{ old('replacement_casket_catalog_id') ? 'checked' : '' }}>
                                                <span>
                                                    <span class="block text-sm font-bold text-slate-800">Choose replacement/upgrade</span>
                                                    <span class="block text-xs text-slate-500">Off means the included casket is used with no additional charge.</span>
                                                </span>
                                            </label>
                                        </div>

                                        <div id="replacement_casket_wrap" class="mt-4 hidden">
                                            <label class="field-label">Alternative Casket</label>
                                            <select name="replacement_casket_catalog_id" id="replacement_casket_catalog_id" class="form-input" data-label="replacement casket">
                                                <option value="">Select replacement or upgrade casket</option>
                                                @foreach(($activeCaskets ?? collect()) as $casket)
                                                    <option
                                                        value="{{ $casket->id }}"
                                                        data-name="{{ $casket->name }}"
                                                        data-material="{{ $casket->type_or_material }}"
                                                        data-price="{{ $casket->standard_price }}"
                                                        {{ (string) old('replacement_casket_catalog_id') === (string) $casket->id ? 'selected' : '' }}
                                                    >
                                                        {{ $casket->name }}{{ $casket->type_or_material ? ' - '.$casket->type_or_material : '' }} (&#8369;{{ number_format((float) $casket->standard_price, 2) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p id="casket_upgrade_preview" class="mt-1 text-xs text-slate-500">Select a replacement casket to calculate the upgrade difference.</p>
                                            @error('replacement_casket_catalog_id') <div class="intake-field-error">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                                        <i class="bi bi-droplet"></i>
                                    </div>
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
                                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Included</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="embalming_included_days">0</span> day(s)</span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Actual Wake Days</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="embalming_actual_days">0D/0N</span></span>
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

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                                        <i class="bi bi-house-heart"></i>
                                    </div>
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
                                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Included</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="viewing_included_days">0</span> day(s)</span>
                                            </div>
                                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Actual Wake Days</span>
                                                <span class="mt-1 block text-sm font-black text-slate-800"><span id="viewing_actual_days">0D/0N</span></span>
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

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                                        <i class="bi bi-truck"></i>
                                    </div>
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
                                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
                                                <input type="checkbox" name="apply_retrieval_excess" id="apply_retrieval_excess" value="1" class="mt-1 h-4 w-4 text-slate-900 focus:ring-slate-900" {{ old('apply_retrieval_excess') ? 'checked' : '' }}>
                                                <span>
                                                    <span class="block text-sm font-bold text-slate-800">Add excess retrieval distance</span>
                                                    <span class="block text-xs text-slate-500">Leave off when within package coverage.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div id="retrieval_excess_wrap" class="mt-3 hidden">
                                            <label class="field-label">Excess KM</label>
                                            <input type="number" step="0.01" min="0" name="retrieval_excess_kilometers" id="retrieval_excess_kilometers" value="{{ old('retrieval_excess_kilometers') }}" class="form-input" placeholder="0" data-label="retrieval excess kilometers">
                                            @error('retrieval_excess_kilometers') <div class="intake-field-error">{{ $message }}</div> @enderror
                                        </div>
                                        <p id="retrieval_charge_preview" class="mt-2 text-xs text-slate-500">Within included coverage unless excess kilometers are added.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                                        <i class="bi bi-signpost-split"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h6 class="text-sm font-black text-slate-900">Hearse Service Distance</h6>
                                                <p class="mt-1 text-xs font-medium text-slate-500">Distance from the wake or funeral service location to the cemetery/interment location.</p>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-black text-slate-800">
                                                Charge: &#8369; <span id="hearse_charge_amount">0.00</span>
                                            </div>
                                        </div>
                                        <div id="hearse_config_warning" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">
                                            <i class="bi bi-exclamation-triangle mr-1"></i> Ask Admin to configure included kilometers and excess kilometer rate for Hearse Service.
                                        </div>
                                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
                                                <input type="checkbox" name="apply_hearse_excess" id="apply_hearse_excess" value="1" class="mt-1 h-4 w-4 text-slate-900 focus:ring-slate-900" {{ old('apply_hearse_excess') ? 'checked' : '' }}>
                                                <span>
                                                    <span class="block text-sm font-bold text-slate-800">Add excess hearse distance</span>
                                                    <span class="block text-xs text-slate-500">Leave off when within package coverage.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div id="hearse_excess_wrap" class="mt-3 hidden">
                                            <label class="field-label">Excess KM</label>
                                            <input type="number" step="0.01" min="0" name="hearse_excess_kilometers" id="hearse_excess_kilometers" value="{{ old('hearse_excess_kilometers') }}" class="form-input" placeholder="0" data-label="hearse excess kilometers">
                                            @error('hearse_excess_kilometers') <div class="intake-field-error">{{ $message }}</div> @enderror
                                        </div>
                                        <p id="hearse_charge_preview" class="mt-2 text-xs text-slate-500">Within included coverage unless excess kilometers are added.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="package-adjustments-foot flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 p-4">
                            <div class="text-xs font-bold text-[#5F685F]">Current total: &#8369; <span id="structured_charges_total_modal">0.00</span></div>
                            <button type="button" id="done_package_adjustments_btn" class="inline-flex items-center gap-2 rounded-lg bg-[#3E4A3D] px-4 py-2 text-xs font-bold text-white hover:bg-[#2f382e]">
                                <i class="bi bi-check2-circle"></i> Done
                            </button>
                        </div>
                    </div>

                    <div class="service-details-area">
                        <div class="section-title-block mb-6">
                            <div class="section-heading-icon">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div class="section-title-text">
                                <h3>Service Details</h3>
                                <p>Set the service schedule, locations, and case progress.</p>
                            </div>
                        </div>

                    <input type="hidden" name="service_requested_at" id="service_requested_at" value="{{ now()->toDateString() }}">
                    <input type="hidden" name="service_type" id="service_type" value="Burial">
                    @error('service_requested_at')
                        <p class="intake-field-error mb-3">{{ $message }}</p>
                    @enderror

                    <div class="service-details-grid">
                        <div class="service-detail-panel">
                            <div class="service-detail-panel-header">
                                <i class="bi bi-calendar2-week"></i>
                                <div>
                                    <h4>Schedule</h4>
                                    <p>Arrange the wake, funeral service, and interment timeline.</p>
                                </div>
                            </div>

                            <div class="schedule-stack">

                        <div class="schedule-field-row">
                            <div class="schedule-field-label">
                                <label class="field-label">Wake Start <span class="text-rose-500">*</span></label>
                                <p>First day and time of the wake or viewing period.</p>
                            </div>
                            <div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="relative">
                                    <input type="text" name="wake_start_date" id="wake_start_date" value="{{ old('wake_start_date') }}" data-label="wake start date" class="form-input pr-10 cursor-pointer" placeholder="Wake date" autocomplete="off" required>
                                    <span id="wake_start_picker_trigger" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                        <i class="bi bi-calendar-event text-lg"></i>
                                    </span>
                                </div>
                                <div class="time-input-wrap" data-time-picker="wake_start_time">
                                    <input type="hidden" name="wake_start_time" id="wake_start_time" value="{{ old('wake_start_time') ? substr(old('wake_start_time'), 0, 5) : '' }}" data-label="wake start time" class="schedule-time-value" required>
                                    <input type="text" class="schedule-time-display" data-time-display-for="wake_start_time" data-skip-autocap aria-label="Wake start time" placeholder="e.g. 8:30 AM" inputmode="text" autocomplete="off" aria-expanded="false">
                                    <button type="button" class="schedule-time-toggle" data-time-toggle-for="wake_start_time" aria-label="Open wake start time picker">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <div class="schedule-time-popover" data-time-popover role="dialog" aria-label="Wake start time picker">
                                        <div class="time-column">
                                            <span class="time-column-label">Hour</span>
                                            <div class="time-column-options">
                                                @for ($hour = 1; $hour <= 12; $hour++)
                                                    <button type="button" class="time-option" data-time-part="hour" data-time-value="{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}</button>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="time-column">
                                            <span class="time-column-label">Minute</span>
                                            <div class="time-column-options">
                                                @for ($minute = 0; $minute < 60; $minute++)
                                                    <button type="button" class="time-option" data-time-part="minute" data-time-value="{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}</button>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="time-column">
                                            <span class="time-column-label">AM/PM</span>
                                            <div class="time-column-options">
                                                <button type="button" class="time-option" data-time-part="period" data-time-value="AM">AM</button>
                                                <button type="button" class="time-option" data-time-part="period" data-time-value="PM">PM</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('wake_start_date')
                                <p class="intake-field-error">{{ $message }}</p>
                            @enderror
                            @error('wake_start_time')
                                <p class="intake-field-error">{{ $message }}</p>
                            @enderror
                            <p id="wake_start_error" class="intake-field-error hidden"></p>
                            </div>
                        </div>

                        <div class="schedule-field-row">
                            <div class="schedule-field-label">
                                <label class="field-label">Funeral Service <span class="text-rose-500">*</span></label>
                                <p>Date and time of the final service or ceremony.</p>
                            </div>
                            <div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="relative">
                                    <input type="text" name="funeral_service_at" id="funeral_service_at" value="{{ old('funeral_service_at') }}" data-label="funeral service date" class="form-input pr-10 cursor-pointer" placeholder="Service date" autocomplete="off" required>
                                    <span id="wake_picker_trigger" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                        <i class="bi bi-calendar-event text-lg"></i>
                                    </span>
                                </div>
                                <div class="time-input-wrap" data-time-picker="funeral_service_time">
                                    <input type="hidden" name="funeral_service_time" id="funeral_service_time" value="{{ old('funeral_service_time') ? substr(old('funeral_service_time'), 0, 5) : '' }}" data-label="funeral service time" class="schedule-time-value" required>
                                    <input type="text" class="schedule-time-display" data-time-display-for="funeral_service_time" data-skip-autocap aria-label="Funeral service time" placeholder="e.g. 9:00 AM" inputmode="text" autocomplete="off" aria-expanded="false">
                                    <button type="button" class="schedule-time-toggle" data-time-toggle-for="funeral_service_time" aria-label="Open funeral service time picker">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <div class="schedule-time-popover" data-time-popover role="dialog" aria-label="Funeral service time picker">
                                        <div class="time-column">
                                            <span class="time-column-label">Hour</span>
                                            <div class="time-column-options">
                                                @for ($hour = 1; $hour <= 12; $hour++)
                                                    <button type="button" class="time-option" data-time-part="hour" data-time-value="{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}</button>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="time-column">
                                            <span class="time-column-label">Minute</span>
                                            <div class="time-column-options">
                                                @for ($minute = 0; $minute < 60; $minute++)
                                                    <button type="button" class="time-option" data-time-part="minute" data-time-value="{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}</button>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="time-column">
                                            <span class="time-column-label">AM/PM</span>
                                            <div class="time-column-options">
                                                <button type="button" class="time-option" data-time-part="period" data-time-value="AM">AM</button>
                                                <button type="button" class="time-option" data-time-part="period" data-time-value="PM">PM</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('funeral_service_at')
                                <p class="intake-field-error">{{ $message }}</p>
                            @enderror
                            @error('funeral_service_time')
                                <p class="intake-field-error">{{ $message }}</p>
                            @enderror
                            <p id="funeral_service_at_error" class="intake-field-error hidden"></p>
                            </div>
                        </div>

                        <div class="schedule-field-row">
                            <div class="schedule-field-label">
                                <label class="field-label">Interment <span class="text-rose-500">*</span></label>
                                <p>Date and time of burial or interment.</p>
                            </div>
                            <div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="relative">
                                    <input type="text" name="interment_at" id="interment_at" value="{{ old('interment_at') }}" data-label="interment date" class="form-input pr-10 cursor-pointer" placeholder="Interment date" autocomplete="off" required>
                                    <span id="inter_picker_trigger" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                        <i class="bi bi-calendar-event text-lg"></i>
                                    </span>
                                </div>
                                <div class="time-input-wrap" data-time-picker="interment_time">
                                    <input type="hidden" name="interment_time" id="interment_time" value="{{ old('interment_time') ? substr(old('interment_time'), 0, 5) : '' }}" data-label="interment time" class="schedule-time-value" required>
                                    <input type="text" class="schedule-time-display" data-time-display-for="interment_time" data-skip-autocap aria-label="Interment time" placeholder="e.g. 1:00 PM" inputmode="text" autocomplete="off" aria-expanded="false">
                                    <button type="button" class="schedule-time-toggle" data-time-toggle-for="interment_time" aria-label="Open interment time picker">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <div class="schedule-time-popover" data-time-popover role="dialog" aria-label="Interment time picker">
                                        <div class="time-column">
                                            <span class="time-column-label">Hour</span>
                                            <div class="time-column-options">
                                                @for ($hour = 1; $hour <= 12; $hour++)
                                                    <button type="button" class="time-option" data-time-part="hour" data-time-value="{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}</button>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="time-column">
                                            <span class="time-column-label">Minute</span>
                                            <div class="time-column-options">
                                                @for ($minute = 0; $minute < 60; $minute++)
                                                    <button type="button" class="time-option" data-time-part="minute" data-time-value="{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}</button>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="time-column">
                                            <span class="time-column-label">AM/PM</span>
                                            <div class="time-column-options">
                                                <button type="button" class="time-option" data-time-part="period" data-time-value="AM">AM</button>
                                                <button type="button" class="time-option" data-time-part="period" data-time-value="PM">PM</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('interment_at')
                                <p class="intake-field-error">{{ $message }}</p>
                            @enderror
                            @error('interment_time')
                                <p class="intake-field-error">{{ $message }}</p>
                            @enderror
                            <div id="interment_at_error" class="intake-field-error hidden">Interment date cannot be before the funeral service date.</div>
                            <p id="interment_schedule_warning" class="hidden text-xs font-bold text-amber-600 mt-1">Funeral service and interment are usually on the same day. Please confirm if interment is scheduled on a different date.</p>
                            </div>
                        </div>

                        <div class="schedule-field-row">
                            <div class="schedule-field-label">
                                <label class="field-label">Wake Duration</label>
                                <p>Auto-computed from wake start through interment.</p>
                            </div>
                            <div>
                            <input type="hidden" name="wake_days" id="wake_days" value="{{ old('wake_days') }}" data-label="wake days">
                            <div class="service-readout pointer-events-none">
                                <i class="bi bi-moon-stars text-slate-500"></i>
                                <span id="wake_duration_display">Auto-calculated</span>
                            </div>
                            <p id="wake_days_helper" class="text-xs text-slate-500 mt-1">Calculated from Wake Start Date through Interment Date, inclusive.</p>
                            </div>
                        </div>

                            </div>
                        </div>

                        <div class="service-detail-panel">
                            <div class="service-detail-panel-header">
                                <i class="bi bi-geo-alt"></i>
                                <div>
                                    <h4>Service Information</h4>
                                    <p>Burial service details and current case progress.</p>
                                </div>
                            </div>

                            <div class="service-info-stack">

                        <div class="md:col-span-1">
                            <label class="field-label">Wake Location <span class="text-rose-500">*</span></label>
                            <input type="text" name="wake_location" id="wake_location" value="{{ old('wake_location') }}" data-label="wake location" class="form-input" placeholder="Chapel or House Address" required>
                        </div>

                        <div>
                            <label class="field-label">Place of Interment / Cemetery <span class="text-rose-500">*</span></label>
                            <input type="text" name="place_of_cemetery" value="{{ old('place_of_cemetery') }}" data-label="place of interment or cemetery" class="form-input" placeholder="Cemetery Name" required>
                        </div>

                        <div>
                            <label class="field-label">Case Status <span class="text-rose-500">*</span></label>

                            @if($isOtherEntryMode)
                                <input type="hidden" name="case_status" id="case_status" value="COMPLETED">
                                <div class="form-input bg-emerald-50 border-emerald-200 text-emerald-800 font-bold flex items-center gap-2 pointer-events-none">
                                    <i class="bi bi-check-circle-fill"></i> Completed
                                </div>
                            @else
                                <select name="case_status" id="case_status" data-label="case status" class="form-input" required>
                                    <option value="ACTIVE" selected>Ongoing</option>
                                </select>
                            @endif
                        </div>
                            </div>
                        </div>
                    </div>

                    <div class="service-photo-upload">
                        <label class="field-label">Upload Deceased Photo (Optional)</label>
                        <input type="file" name="deceased_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300">
                    </div>
                    </div>{{-- /service-details-wrapper --}}
                </section>

                <section class="wizard-panel hidden" data-step="4">
                    <div class="section-title-block">
                        <div class="section-heading-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="section-title-text">
                            <h3>Billing &amp; Payment</h3>
                            <p>Review the summary and record the initial deposit or full payment.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-6 mt-6">

                        {{-- LEFT: Record Payment card --}}
                        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50">
                                <div class="w-7 h-7 rounded-lg bg-slate-800 text-white flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-credit-card-fill text-xs"></i>
                                </div>
                                <span class="font-bold text-slate-800 text-sm">Record Payment</span>
                            </div>
                            <div class="p-5 billing-payment-stack">

                                {{-- Hidden billing inputs still used by JS --}}
                                <input type="hidden" name="package_amount" id="package_amount" value="{{ old('package_amount') }}">
                                <input type="hidden" name="tax_rate" id="tax_rate" value="0">
                                <input type="hidden" id="auto_discount_type" value="None">
                                <input type="hidden" id="auto_discount_amount" value="PHP 0.00">

                                {{-- Discount info note --}}
                                <div id="discount_help_wrap" class="text-[11px] font-medium text-slate-500 flex gap-2 items-start">
                                    <i class="bi bi-info-circle text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span id="discount_help_text_secondary">Discount is tied to Senior Citizen status.</span>
                                </div>

                                <div class="rounded-lg border border-[#AEBBA8] bg-[#DCE6D6] px-4 py-3 text-xs font-medium text-[#3F4C3E]">
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
                                    <label id="mark_as_paid_label" class="hidden group flex items-center justify-between gap-4 p-4 rounded-xl border-2 {{ old('mark_as_paid') ? 'border-slate-800 bg-slate-50' : 'border-slate-200 bg-white' }} cursor-pointer transition-all duration-200 hover:border-slate-400">
                                        <div class="flex items-center gap-3">
                                            <div id="mark_as_paid_icon" class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-200 {{ old('mark_as_paid') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-500' }}">
                                                <i class="bi bi-cash-stack text-base"></i>
                                            </div>
                                            <div>
                                                <span class="block text-sm font-bold text-slate-800">Record Payment Now</span>
                                                <span class="block text-xs text-slate-500 mt-0.5">Click to log a deposit or full settlement.</span>
                                            </div>
                                        </div>
                                        <div id="mark_as_paid_toggle" class="relative w-11 h-6 flex-shrink-0">
                                            <input type="checkbox" name="mark_as_paid" id="mark_as_paid" value="1" {{ old('mark_as_paid') ? 'checked' : '' }} class="sr-only peer">
                                            <div class="w-11 h-6 rounded-full border-2 transition-all duration-200 peer-checked:bg-slate-900 peer-checked:border-slate-900 border-slate-300 bg-slate-100"></div>
                                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-all duration-200 peer-checked:translate-x-5 peer-checked:shadow-md"></div>
                                        </div>
                                    </label>
                                @endif

                                <div id="payment_form_fields" class="space-y-5">
                                    {{-- Payment Method --}}
                                    <div>
                                        <label class="field-label text-xs">Payment Method</label>
                                        <div class="billing-method-choice" id="payment_method_group">
                                            <label class="payment-method-card cursor-pointer">
                                                <input type="radio" name="payment_method" value="cash" class="payment-method-radio sr-only" {{ $isOtherEntryMode || old('payment_method') === 'cash' || old('payment_method') === 'CASH' ? 'checked' : '' }}>
                                                <span class="flex flex-col gap-1 text-sm font-bold text-slate-700">
                                                    <span class="flex items-center gap-2"><i class="bi bi-cash-coin"></i> Cash</span>
                                                    <span class="text-xs font-medium text-slate-500">Record an over-the-counter payment.</span>
                                                </span>
                                            </label>
                                            <label class="payment-method-card cursor-pointer">
                                                <input type="radio" name="payment_method" value="cashless" class="payment-method-radio sr-only" {{ in_array(old('payment_method'), ['cashless', 'bank_transfer', 'BANK_TRANSFER'], true) ? 'checked' : '' }}>
                                                <span class="flex flex-col gap-1 text-sm font-bold text-slate-700">
                                                    <span class="flex items-center gap-2"><i class="bi bi-wallet2"></i> Cashless</span>
                                                    <span class="text-xs font-medium text-slate-500">Bank, wallet, card, or other channel.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <p id="payment_method_hint" class="mt-2 text-xs font-medium text-slate-500">Leave unselected if no payment is being recorded yet.</p>
                                    </div>

                                    <div id="payment_details_section" class="payment-details-shell hidden space-y-5">
                                    <div id="cash_reference_wrap">
                                        <label class="field-label">Receipt / Reference No. <span class="text-slate-400 font-medium">(optional)</span></label>
                                        <input type="text" name="reference_number" id="cash_reference_number" value="{{ old('reference_number') }}" data-label="receipt reference" class="form-input" placeholder="Optional receipt or reference number">
                                    </div>

                                    <div id="cashless_details_wrap" class="hidden space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <div>
                                            <label class="field-label">Cashless Type</label>
                                            <select name="cashless_type" id="cashless_type" class="form-input">
                                                <option value="">Select cashless type</option>
                                                <option value="bank_transfer" {{ old('cashless_type', in_array(old('payment_method'), ['bank_transfer', 'BANK_TRANSFER'], true) ? 'bank_transfer' : null) === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                                <option value="gcash" {{ old('cashless_type') === 'gcash' ? 'selected' : '' }}>GCash</option>
                                                <option value="maya" {{ old('cashless_type') === 'maya' ? 'selected' : '' }}>Maya</option>
                                                <option value="card" {{ old('cashless_type') === 'card' ? 'selected' : '' }}>Card</option>
                                                <option value="other" {{ old('cashless_type') === 'other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                        </div>

                                        <div data-intake-cashless-panel="bank_transfer" class="intake-cashless-panel hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="field-label">Bank Name</label>
                                                <select name="bank_name" id="bank_name" class="form-input">
                                                    <option value="">Select bank</option>
                                                    @foreach(['BDO', 'BPI', 'Metrobank', 'Landbank', 'Security Bank', 'UnionBank', 'RCBC', 'PNB', 'China Bank', 'Other Bank'] as $bank)
                                                        <option value="{{ $bank }}" {{ old('bank_name') === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="field-label">Account Name</label>
                                                <input type="text" name="account_name" id="account_name" value="{{ old('account_name') }}" class="form-input" maxlength="120" placeholder="Optional">
                                            </div>
                                            <div id="other_bank_name_wrap" class="hidden md:col-span-2">
                                                <label class="field-label">Other Bank Name</label>
                                                <input type="text" name="other_bank_name" id="other_bank_name" value="{{ old('other_bank_name') }}" class="form-input" maxlength="100">
                                            </div>
                                        </div>

                                        <div data-intake-cashless-panel="gcash" class="intake-cashless-panel hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="field-label">GCash Account Name</label>
                                                <input type="text" data-intake-account-name-field value="{{ old('account_name') }}" class="form-input" maxlength="120" placeholder="Optional">
                                            </div>
                                            <div>
                                                <label class="field-label">GCash Mobile Number</label>
                                                <input type="text" data-intake-mobile-number-field value="{{ old('mobile_number') }}" class="form-input" maxlength="30" placeholder="Optional">
                                            </div>
                                        </div>

                                        <div data-intake-cashless-panel="maya" class="intake-cashless-panel hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="field-label">Maya Account Name</label>
                                                <input type="text" data-intake-account-name-field value="{{ old('account_name') }}" class="form-input" maxlength="120" placeholder="Optional">
                                            </div>
                                            <div>
                                                <label class="field-label">Maya Mobile Number</label>
                                                <input type="text" data-intake-mobile-number-field value="{{ old('mobile_number') }}" class="form-input" maxlength="30" placeholder="Optional">
                                            </div>
                                        </div>

                                        <div data-intake-cashless-panel="card" class="intake-cashless-panel hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="field-label">Card Type</label>
                                                <select name="card_type" id="card_type" class="form-input">
                                                    <option value="">Select card type</option>
                                                    <option value="debit" {{ old('card_type') === 'debit' ? 'selected' : '' }}>Debit</option>
                                                    <option value="credit" {{ old('card_type') === 'credit' ? 'selected' : '' }}>Credit</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="field-label">Terminal / Provider</label>
                                                <input type="text" name="terminal_provider" id="terminal_provider" value="{{ old('terminal_provider') }}" class="form-input" maxlength="80" placeholder="Optional">
                                            </div>
                                            <div>
                                                <label class="field-label">Approval Code</label>
                                                <input type="text" name="approval_code" id="approval_code" value="{{ old('approval_code') }}" class="form-input" maxlength="40">
                                            </div>
                                        </div>

                                        <div data-intake-cashless-panel="other" class="intake-cashless-panel hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="field-label">Payment Channel</label>
                                                <input type="text" data-intake-payment-channel-field value="{{ old('payment_channel') }}" class="form-input" maxlength="100" placeholder="e.g. PalawanPay">
                                            </div>
                                            <div>
                                                <label class="field-label">Notes</label>
                                                <input type="text" name="payment_notes" value="{{ old('payment_notes') }}" class="form-input" maxlength="255" placeholder="Optional">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="field-label">Reference No.</label>
                                            <input type="text" id="reference_number" value="{{ old('reference_number', old('bank_reference')) }}" data-label="reference number" class="form-input" placeholder="e.g. TXN-20240101-001">
                                        </div>
                                        <input type="hidden" name="wallet_provider" id="wallet_provider" value="{{ old('wallet_provider') }}">
                                        <input type="hidden" name="mobile_number" id="mobile_number" value="{{ old('mobile_number') }}">
                                        <input type="hidden" name="payment_channel" id="payment_channel" value="{{ old('payment_channel') }}">
                                        <input type="hidden" name="bank_reference" id="bank_reference" value="{{ old('bank_reference') }}">
                                    </div>

                                    @if(!$isOtherEntryMode)
                                        <div>
                                            <label class="field-label text-xs">Payment Type</label>
                                            <div class="bpay-tab-group" id="payment_type_group">
                                                <label class="payment-type-card cursor-pointer transition-colors">
                                                    <input type="radio" name="payment_type" value="FULL" class="payment-type-radio sr-only" {{ old('payment_type') === 'FULL' ? 'checked' : '' }}>
                                                    <span class="text-sm font-bold text-slate-700">Full Payment</span>
                                                </label>
                                                <label class="payment-type-card cursor-pointer transition-colors">
                                                    <input type="radio" name="payment_type" value="PARTIAL" class="payment-type-radio sr-only" {{ old('payment_type') === 'PARTIAL' ? 'checked' : '' }}>
                                                    <span class="text-sm font-bold text-slate-700">Partial Payment</span>
                                                </label>
                                            </div>
                                            <div id="payment_type_error" class="intake-field-error hidden">Please select a type.</div>
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="field-label">Amount Received</label>
                                            <div class="flex items-center rounded-lg border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-slate-900">
                                                <span class="pl-3 pr-2 text-slate-500 font-bold text-lg">&#8369;</span>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0.01"
                                                    name="amount_paid"
                                                    id="amount_paid"
                                                    value="{{ old('amount_paid') }}"
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
                                            <label class="field-label">Date Received</label>
                                            <input type="datetime-local" name="paid_at" id="paid_at" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}" data-label="payment date" class="form-input">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Status</div>
                                            <div id="payment_status_preview" class="text-base font-black text-slate-800">UNPAID</div>
                                        </div>
                                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                            <div class="text-[9px] font-black uppercase tracking-widest text-emerald-600 mb-1">Paid Amount</div>
                                            <div class="text-base font-black text-emerald-800">&#8369; <span id="payment_paid_preview">0.00</span></div>
                                        </div>
                                        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 text-white">
                                            <div class="text-[9px] font-black uppercase tracking-widest opacity-50 mb-1">Remaining Balance</div>
                                            <div class="text-base font-black">&#8369; <span id="payment_balance_preview">0.00</span></div>
                                        </div>
                                    </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- RIGHT: Live Summary card --}}
                        <div class="self-start sticky top-6">
                            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-lg bg-slate-800 text-white flex items-center justify-center flex-shrink-0">
                                        <i class="bi bi-receipt text-xs"></i>
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
                                            <span class="font-bold text-slate-700 whitespace-nowrap">&#8369;&nbsp;<span id="summary_payment_status_dummy" class="hidden"></span><span id="summary_payment_status" class="font-bold">UNPAID</span></span>
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
                </section>

                <section class="wizard-panel hidden" data-step="5">
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
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 group relative shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
                                <button
                                    type="button"
                                    class="review-edit absolute top-4 right-4 text-[10px] font-bold uppercase tracking-wider text-slate-400 hover:text-slate-900 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                    data-jump-step="{{ $card['step'] }}"
                                >
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>

                                <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">{{ $card['title'] }}</h4>
                                <dl class="space-y-2 text-xs text-slate-700" id="review_{{ $id }}"></dl>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8 rounded-xl border border-amber-300 bg-amber-100 p-5 text-slate-900 flex items-start sm:items-center gap-4 shadow-md ring-1 ring-amber-200/70">
                        <input type="checkbox" name="confirm_review" id="confirm_review" value="1" class="w-5 h-5 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300" required>
                        <label for="confirm_review" class="text-base font-semibold leading-snug">
                            I verify that I have reviewed this data and it is accurate to the best of my knowledge.
                        </label>
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

                {{-- Right group: Back + Continue / Save --}}
                <div class="footer-right-group flex flex-1 sm:flex-none sm:ml-auto items-center gap-2">
                    <button
                        type="submit"
                        id="saveIntakeDraft"
                        formaction="{{ route('intake.drafts.save') }}"
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
                        Back
                    </button>
                    <button type="button" id="wizardNext" class="w-full sm:w-auto sm:min-w-[190px] px-8 py-3 rounded-xl bg-slate-900 text-white text-sm font-bold transition-all hover:bg-[#3E4A3D] hover:-translate-y-0.5">
                        Continue
                    </button>

                    <button type="submit" id="saveIntakeRecord" class="hidden w-full sm:w-auto sm:min-w-[190px] px-8 py-3 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 transition-all disabled:opacity-50">
                        Save Record
                    </button>
                </div>
            </div>
        </form>
        </div>{{-- /intake-form-wrapper --}}
    </div>{{-- /intake-section-shell --}}

    {{-- Cancel Confirmation Modal --}}
    <div id="intakeCancelModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4" style="backdrop-filter:blur(3px);">
        <div class="absolute inset-0" id="intakeCancelModalBackdrop" style="background:rgba(0,0,0,.45);"></div>
        <div class="cancel-modal-box">
            <p class="cancel-modal-title">Save this intake before leaving?</p>
            <p class="cancel-modal-msg">You have unsaved intake details. Save them as a draft so staff can continue later, or leave without saving.</p>
            <div class="cancel-modal-actions">
                <button type="button" id="intakeCancelModalKeep">Keep Editing</button>
                <button type="button" id="intakeCancelModalDiscard">Leave without saving</button>
                <button type="button" id="intakeCancelModalSaveDraft">
                    <i class="bi bi-save2" aria-hidden="true"></i>
                    Save as Draft
                </button>
            </div>
        </div>
    </div>

</div>{{-- /intake-root --}}

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
    const branchError = document.getElementById('branch_error');
    const branchBtns = [...document.querySelectorAll('.branch-toggle')];
    const nextCode = document.getElementById('next_case_code');
    const nextMap = @json($nextCodeMap ?? []);
    const defCode = @json($nextCode ?? 'FC0001');
    const mainBranchCodeDefault = document.getElementById('branch_code_main_default')?.value || 'BR001';

    const requestDate = document.getElementById('service_requested_at');
    const requestDateDisplay = document.getElementById('service_requested_display');

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

    const wakeDays = document.getElementById('wake_days');
    const wakeDurationDisplay = document.getElementById('wake_duration_display');
    const wakeStart = document.getElementById('wake_start_date');
    const wakeStartTime = document.getElementById('wake_start_time');
    const funeral = document.getElementById('funeral_service_at');
    const funeralTime = document.getElementById('funeral_service_time');
    const interment = document.getElementById('interment_at');
    const intermentTime = document.getElementById('interment_time');
    const intermentErr = document.getElementById('interment_at_error');
    const intermentWarning = document.getElementById('interment_schedule_warning');
    const bornErr = document.getElementById('born_error');
    const diedErr = document.getElementById('died_error');
    const wakeStartErr = document.getElementById('wake_start_error');
    const wakeErr = document.getElementById('funeral_service_at_error');
    let wakePicker = null;
    let wakeStartPicker = null;
    let interPicker = null;
    let isAutoSyncingIntermentDate = false;
    if (interment) interment.dataset.autoFilled = interment.value ? '0' : '';

    const senior = document.getElementById('senior_citizen_status');
    const seniorIdWrap = document.getElementById('senior_id_wrap');
    const seniorId = document.getElementById('senior_citizen_id_number');
    const proofWrap = document.getElementById('senior_proof_wrap');
    const proofInput = document.getElementById('senior_proof');

    const pkgList = document.getElementById('packageCardList');
    const pkgRadios = [...document.querySelectorAll('.package-radio')];
    const pkgCards = [...document.querySelectorAll('.package-card')];
    const pkgAmount = document.getElementById('package_amount');
    const packageError = document.getElementById('package_error');

    const customPkgRadio = document.getElementById('custom_package_radio');
    const prefPkg = document.getElementById('client_pref_package');
    const prefPrice = document.getElementById('client_pref_price');
    const prefIncl = document.getElementById('client_pref_inclusions');
    const prefFree = document.getElementById('client_pref_freebies');
    const customPkgFields = document.getElementById('custom_package_fields');
    const customPkgPriceDisplay = document.getElementById('custom_package_price_display');

    const inclusions = document.getElementById('selected_package_inclusions');
    const freebies = document.getElementById('selected_package_freebies');
    const packageDetailsModal = document.getElementById('package_details_modal');
    const packageDetailsTitle = document.getElementById('package_details_title');
    const packageDetailsSubtitle = document.getElementById('package_details_subtitle');
    const packageDetailTabs = [...document.querySelectorAll('.package-detail-tab')];
    const packageDetailPanels = [...document.querySelectorAll('.package-detail-panel')];
    const packageDetailServices = document.getElementById('package_detail_services');
    const packageDetailFreebies = document.getElementById('package_detail_freebies');
    const packageDetailCoverage = document.getElementById('package_detail_coverage');
    const optionalAddOnsSection = document.getElementById('optional_add_ons_section');
    const optionalAddOnsList = document.getElementById('optional_add_ons_list');
    const optionalAddOnsPanel = document.getElementById('optional_add_ons_panel');
    const optionalAddOnsSearch = document.getElementById('optional_add_ons_search');
    const chooseAddOnsBtn = document.getElementById('choose_add_ons_btn');
    const applyAddOnsBtn = document.getElementById('apply_add_ons_btn');
    const cancelAddOnsBtn = document.getElementById('cancel_add_ons_btn');
    const closeAddOnsBtn = document.getElementById('close_add_ons_btn');
    const selectedAddOnsSummary = document.getElementById('selected_add_ons_summary');
    const selectedAddOnsLines = document.getElementById('selected_add_ons_lines');
    const draftAddOnsTotal = document.getElementById('draft_add_ons_total');
    const selectedAddOnsTotal = document.getElementById('selected_add_ons_total');
    const serviceModalBackdrop = document.getElementById('intake_service_modal_backdrop');
    const oldSelectedAddOns = new Set(@json($oldSelectedAddOns));
    const globalAddOns = {!! \Illuminate\Support\Js::from(($activeAddOns ?? collect())->map(fn ($addOn) => [
        'id' => $addOn->id,
        'name' => $addOn->name,
        'category' => $addOn->category,
        'description' => $addOn->description,
        'price' => (float) $addOn->price,
        'unit' => $addOn->unit,
    ])->values()) !!};
    const serviceTypeLabels = {
        body_retrieval: 'Body Retrieval',
        embalming: 'Embalming',
        casket: 'Casket',
        home_viewing: 'Home Viewing',
        hearse: 'Hearse',
        custom: 'Other Package Inclusion',
    };
    const structuredPricingSection = document.getElementById('structured_pricing_section');
    const packageAdjustmentsPanel = document.getElementById('package_adjustments_panel');
    const openPackageAdjustmentsBtn = document.getElementById('open_package_adjustments_btn');
    const closePackageAdjustmentsBtn = document.getElementById('close_package_adjustments_btn');
    const donePackageAdjustmentsBtn = document.getElementById('done_package_adjustments_btn');
    const structuredChargesTotal = document.getElementById('structured_charges_total');
    const structuredChargesTotalModal = document.getElementById('structured_charges_total_modal');
    const replacementCasket = document.getElementById('replacement_casket_catalog_id');
    const replacementCasketWrap = document.getElementById('replacement_casket_wrap');
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
    const otherBranchWindowClosed = @json($otherBranchWindowClosed);
    const intakeAutosaveKey = [
        'funeral-system',
        'intake-draft',
        @json(auth()->id()),
        @json($isOtherEntryMode ? 'other' : 'main'),
        @json((string) $initialSelectedBranchId),
    ].join(':');
    const totalSteps = panels.length;
    const initialStep = Math.max(1, Math.min(totalSteps, Number(@json($initialStep ?? 1))));
    const serverInitialBranchId = @json((string) $initialSelectedBranchId);
    const serverOldBranchExists = @json(old('branch_id') !== null);
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
        return days > 0 ? `${days}D/${Math.max(days - 1, 0)}N` : '-';
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
        if (!radio || radio.value === 'custom') return [];
        return Array.isArray(globalAddOns) ? globalAddOns : [];
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
    const packageCountsFor = (radio = pkg()) => {
        const structuredRows = structuredInclusionsFor(radio);
        const structuredFreebieRows = structuredFreebiesFor(radio);
        return {
            inclusions: structuredRows.length || list(radio?.dataset.inclusions).length,
            freebies: structuredFreebieRows.length || list(radio?.dataset.freebies).length,
        };
    };
    const packageDetailRow = (title, details = '', icon = 'bi-check2') => `
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <div class="flex items-start gap-3">
                <i class="bi ${icon} mt-0.5 text-slate-500"></i>
                <div class="min-w-0">
                    <div class="text-sm font-bold text-slate-900">${escapeHtml(title)}</div>
                    ${details ? `<div class="mt-1 text-xs font-semibold text-slate-500">${escapeHtml(details)}</div>` : ''}
                </div>
            </div>
        </div>
    `;
    const detailRowsForPackage = (radio = pkg()) => {
        const structuredRows = structuredInclusionsFor(radio);
        const structuredFreebieRows = structuredFreebiesFor(radio);
        const legacyInclusions = list(radio?.dataset.inclusions);
        const legacyFreebies = list(radio?.dataset.freebies);

        const serviceRows = structuredRows.length
            ? structuredRows.map((item) => {
                const label = serviceTypeLabels[item.service_type] || item.name || 'Included service';
                const details = [];
                if (item.service_type === 'casket') {
                    const casket = item.casket || {};
                    details.push(`${casket.name || item.casket_type || 'Included casket'}${casket.material ? ` - ${casket.material}` : ''}`);
                    if (num(casket.reference_value) > 0) details.push(`Reference Value PHP ${fmt(casket.reference_value)}`);
                }
                if (item.included_kilometers !== null && item.included_kilometers !== undefined) details.push(`${fmt(item.included_kilometers)} included km`);
                if (num(item.price_per_excess_kilometer) > 0) details.push(`PHP ${fmt(item.price_per_excess_kilometer)} per excess km`);
                if (item.included_days !== null && item.included_days !== undefined) details.push(`${item.included_days} included day(s)`);
                if (num(item.price_per_extended_day) > 0) details.push(`PHP ${fmt(item.price_per_extended_day)} per extended day`);
                return packageDetailRow(label, details.join(' | '), item.service_type === 'casket' ? 'bi-box-seam' : 'bi-check2-circle');
            })
            : legacyInclusions.map((item) => packageDetailRow(item, '', 'bi-check2-circle'));

        const freebieRows = structuredFreebieRows.length
            ? structuredFreebieRows.map((item) => packageDetailRow(item.name || 'Freebie', `${item.quantity || 1} ${item.unit || 'item'}`, 'bi-gift'))
            : legacyFreebies.map((item) => packageDetailRow(item, '', 'bi-gift'));

        const coverageRows = structuredRows
            .filter((item) => item.included_kilometers !== null || item.price_per_excess_kilometer || item.included_days !== null || item.price_per_extended_day || item.service_type === 'casket')
            .map((item) => {
                const label = serviceTypeLabels[item.service_type] || item.name || 'Included Limit';
                const details = [];
                if (item.service_type === 'casket') {
                    const casket = item.casket || {};
                    details.push(`Included casket: ${casket.name || item.casket_type || 'Not configured'}`);
                    details.push(num(casket.reference_value) > 0 ? `Reference Value PHP ${fmt(casket.reference_value)}` : 'Reference Value not configured');
                }
                if (item.included_kilometers !== null && item.included_kilometers !== undefined) details.push(`Included ${fmt(item.included_kilometers)} km`);
                if (num(item.price_per_excess_kilometer) > 0) details.push(`Excess rate PHP ${fmt(item.price_per_excess_kilometer)} / km`);
                if (item.included_days !== null && item.included_days !== undefined) details.push(`Included ${item.included_days} day(s)`);
                if (num(item.price_per_extended_day) > 0) details.push(`Extended-day rate PHP ${fmt(item.price_per_extended_day)} / day`);
                return packageDetailRow(label, details.join(' | '), 'bi-speedometer2');
            });

        return {
            services: serviceRows.join('') || '<div class="text-sm font-semibold text-slate-500">No included services configured.</div>',
            freebies: freebieRows.join('') || '<div class="text-sm font-semibold text-slate-500">No freebies configured.</div>',
            coverage: coverageRows.join('') || '<div class="text-sm font-semibold text-slate-500">No included limits or extra charges configured.</div>',
        };
    };
    const activatePackageDetailTab = (target = 'services') => {
        packageDetailTabs.forEach((tab) => {
            const active = tab.dataset.packageDetailTab === target;
            tab.classList.toggle('bg-slate-900', active);
            tab.classList.toggle('text-white', active);
            tab.classList.toggle('text-slate-500', !active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        packageDetailPanels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.id !== `package_detail_${target}`);
        });
    };
    const openPackageDetails = (radio = pkg()) => {
        if (!radio || !packageDetailsModal) return;
        const rows = detailRowsForPackage(radio);
        const counts = packageCountsFor(radio);
        if (packageDetailsTitle) packageDetailsTitle.textContent = radio.dataset.name || 'Package Details';
        if (packageDetailsSubtitle) {
            const casket = includedCasketFor(radio);
            packageDetailsSubtitle.textContent = radio.value === 'custom'
                ? 'Price set during package setup. Manual package details are entered in the custom fields.'
                : `PHP ${fmt(radio.dataset.price)} | ${counts.inclusions} inclusion(s) | ${counts.freebies} freebie(s) | Casket: ${casket.name || 'Not configured'}`;
        }
        if (packageDetailServices) packageDetailServices.innerHTML = rows.services;
        if (packageDetailFreebies) packageDetailFreebies.innerHTML = rows.freebies;
        if (packageDetailCoverage) packageDetailCoverage.innerHTML = rows.coverage;
        activatePackageDetailTab('services');
        packageDetailsModal.classList.remove('hidden');
        packageDetailsModal.classList.add('flex');
        packageDetailsModal.querySelector('button[data-package-details-close]')?.focus();
    };
    const closePackageDetails = () => {
        packageDetailsModal?.classList.add('hidden');
        packageDetailsModal?.classList.remove('flex');
    };
    const checkedAddOns = () => [...document.querySelectorAll('.optional-add-on-checkbox:checked')]
        .map((checkbox) => ({
            id: checkbox.value,
            name: checkbox.dataset.name || '',
            description: checkbox.dataset.description || '',
            price: num(checkbox.dataset.price),
            unit: checkbox.dataset.unit || '',
        }));
    const appliedAddOns = () => parseAddOns().filter((addOn) => oldSelectedAddOns.has(String(addOn.id)));
    const addOnsTotal = () => appliedAddOns().reduce((sum, addOn) => sum + num(addOn.price), 0);
    const syncServiceBackdrop = () => {
        const hasOpenPanel = !optionalAddOnsPanel?.classList.contains('hidden') || !packageAdjustmentsPanel?.classList.contains('hidden');
        serviceModalBackdrop?.classList.toggle('hidden', !hasOpenPanel);
        document.body.style.overflow = hasOpenPanel ? 'hidden' : '';
    };
    const setAddOnsPanelOpen = (open) => {
        if (!optionalAddOnsPanel) return;
        optionalAddOnsPanel.classList.toggle('hidden', !open);
        chooseAddOnsBtn?.setAttribute('aria-expanded', open ? 'true' : 'false');
        syncServiceBackdrop();
        if (open) {
            optionalAddOnsSearch?.focus();
        }
    };
    const setPackageAdjustmentsOpen = (open) => {
        if (!packageAdjustmentsPanel) return;
        packageAdjustmentsPanel.classList.toggle('hidden', !open);
        openPackageAdjustmentsBtn?.setAttribute('aria-expanded', open ? 'true' : 'false');
        syncServiceBackdrop();
        if (open) {
            closePackageAdjustmentsBtn?.focus();
        } else {
            openPackageAdjustmentsBtn?.focus();
        }
    };
    const syncDraftAddOnsTotal = () => {
        if (draftAddOnsTotal) draftAddOnsTotal.textContent = fmt(checkedAddOns().reduce((sum, addOn) => sum + num(addOn.price), 0));
    };
    const restoreAppliedAddOns = () => {
        document.querySelectorAll('.optional-add-on-checkbox').forEach((checkbox) => {
            checkbox.checked = oldSelectedAddOns.has(String(checkbox.value));
        });
        syncDraftAddOnsTotal();
    };
    const applyDraftAddOns = () => {
        oldSelectedAddOns.clear();
        checkedAddOns().forEach((addOn) => oldSelectedAddOns.add(String(addOn.id)));
        setAddOnsPanelOpen(false);
        render();
    };
    const selectedAppliedAddOns = () => {
        return appliedAddOns();
    };
    let additionalItemIndex = 0;
    const oldAdditionalItems = @json(old('additional_service_items', []));
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
            <input type="text" name="additional_service_items[${index}][description]" value="${escapeHtml(item.description || '')}" class="form-input" placeholder="Service description" data-label="additional service description" data-additional-description>
            <div class="flex items-center rounded-lg border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-slate-900">
                <span class="pl-3 pr-2 text-slate-500 font-bold">&#8369;</span>
                <input type="number" step="0.01" min="0" name="additional_service_items[${index}][amount]" value="${escapeHtml(item.amount || '')}" class="w-full border-0 focus:outline-none focus:ring-0 font-bold p-3" placeholder="0.00" data-label="additional service amount" data-additional-amount>
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
            row.remove();
            if (!additionalItemsList.querySelector('[data-additional-service-row]')) addAdditionalServiceRow();
            syncAdditionalServiceHiddenFields();
            render();
        });
        syncAdditionalServiceHiddenFields();
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
        if (isCustomSelected()) return { amount: 0, excess: 0, included: 0, rate: 0, actual: 0, configured: false };
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
    const serviceCharges = () => {
        const retrieval = chargeForKm('body_retrieval', applyRetrievalExcess, retrievalExcessInput);
        const hearse = chargeForKm('hearse', applyHearseExcess, hearseExcessInput);
        const embalming = chargeForDays('embalming');
        const viewing = chargeForDays('home_viewing');
        const casket = casketUpgradeAmount();

        return {
            retrieval,
            hearse,
            embalming,
            viewing,
            casket,
            total: retrieval.amount + hearse.amount + embalming.amount + viewing.amount + casket,
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

    const labelFor = (field) => field?.dataset?.label || field?.getAttribute('placeholder') || 'this field';

    const fieldByName = (name) => f.querySelector(`[name="${name}"]`);

    const fieldErrorElement = (field, create = true) => {
        if (!field) return null;
        const fieldName = field.getAttribute('name') || field.id;
        if (!fieldName) return null;

        const safeName = fieldName.replace(/[^A-Za-z0-9_-]/g, '_');
        let errorEl = document.getElementById(`${safeName}_inline_error`);
        if (!errorEl && create) {
            errorEl = document.createElement('p');
            errorEl.id = `${safeName}_inline_error`;
            errorEl.className = 'intake-field-error hidden';

            const selectWrap = field.matches('select') ? field.closest('.form-select-wrap') : null;
            (selectWrap || field).insertAdjacentElement('afterend', errorEl);
        }

        return errorEl;
    };

    const showFieldError = (field, message) => {
        if (!field) return false;
        const errorEl = fieldErrorElement(field);
        field.setCustomValidity(message);
        setFieldError(field, errorEl, message);
        field.focus();
        return false;
    };

    const normalizeText = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();
    const normalizeComparable = (value) => normalizeText(value).toLocaleLowerCase();
    const hasLetter = (value) => /\p{L}/u.test(String(value ?? ''));
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

        if (!value) {
            return showFieldError(field, 'Complete address is required.');
        }

        if (!hasLetter(value)) {
            return showFieldError(field, 'Complete address must include a valid place name.');
        }

        clearFieldMessage(field);
        return true;
    };

    const validateNameGroup = (prefix, labels) => {
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

        if (!first) return showFieldError(fields.first, `${labels.first} is required.`);
        if (!isValidName(first)) return showFieldError(fields.first, `${labels.first} must contain letters only.`);
        if (middle && !isValidName(middle)) return showFieldError(fields.middle, `${labels.middle} must contain letters only.`);
        if (!last) return showFieldError(fields.last, `${labels.last} is required.`);
        if (!isValidName(last)) return showFieldError(fields.last, `${labels.last} must contain letters only.`);
        if (!isValidSuffix(suffix)) return showFieldError(fields.suffix, 'Please select a valid suffix.');

        const duplicate = validateDuplicateNameParts(first, middle, last);
        if (duplicate) return showFieldError(fields[duplicate.field], duplicate.message);

        return true;
    };

    const validateClientInformationStep = () => {
        if (!validateNameGroup('client', { first: 'First name', middle: 'Middle name', last: 'Last name' })) return false;

        const relationship = fieldByName('client_relationship');
        if (!normalizeText(relationship?.value)) {
            return showFieldError(relationship, 'Relationship is required.');
        }
        clearFieldMessage(relationship);

        const contact = fieldByName('client_contact_number');
        if (!normalizeText(contact?.value)) {
            return showFieldError(contact, 'Contact number is required.');
        }
        if (!isValidPhilippineMobile(contact.value)) {
            return showFieldError(contact, 'Please enter a valid Philippine mobile number.');
        }
        clearFieldMessage(contact);

        return validateAddressField(fieldByName('client_address'));
    };

    const validateDeceasedInformationStep = () => {
        if (!validateNameGroup('deceased', { first: 'First name', middle: 'Middle name', last: 'Last name' })) return false;
        if (!validateAddressField(fieldByName('deceased_address'))) return false;

        if (normalizeComparable(fullNameForPrefix('client')) === normalizeComparable(fullNameForPrefix('deceased'))) {
            return showFieldError(fieldByName('deceased_first_name'), 'Client and deceased names cannot be exactly the same. Please verify the entered information.');
        }

        return true;
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
            const textNode = element.querySelector('.package-toast-text');
            if (textNode) textNode.textContent = message;
            else element.textContent = message;
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

    const showBranchToast = (message) => showToast(branchToast, message, 2600, 'branch');
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
            const amount = promoType === 'PERCENT'
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
            const ref = refState(cashReferenceNumber?.value, false);
            return {
                method: 'Cash',
                bank: '',
                account: '',
                mobile: '',
                channelLabel: 'Payment Channel',
                channel: '',
                referenceLabel: 'Receipt / Reference No.',
                reference: ref.value,
                referenceInvalid: ref.invalid,
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
        const picked = picker?.selectedDates?.[0];
        if (picked instanceof Date && !Number.isNaN(picked.getTime())) return picked;

        const raw = getRawDateString(input, picker);
        if (!raw) return null;

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

    const syncIntermentDateFromFuneral = () => {
        if (!funeral || !interment) return;

        const serviceRaw = (funeral.value || '').trim();
        if (!serviceRaw) return;

        const intermentRaw = (interment.value || '').trim();
        const shouldAutoFill = interment.dataset.autoFilled === '1' || (!intermentRaw && interment.dataset.autoFilled !== '0');
        if (!shouldAutoFill) return;

        isAutoSyncingIntermentDate = true;
        interment.dataset.autoFilled = '1';
        interment.dataset.userTyped = '0';
        interment.dataset.lastTypedValue = '';
        interment.dataset.invalidTypedValue = '';

        if (interPicker) {
            interPicker.setDate(serviceRaw, false, 'Y-m-d');
        } else {
            interment.value = serviceRaw;
        }

        interment.setCustomValidity('');
        setFieldError(interment, intermentErr, '', interPicker?.altInput);
        isAutoSyncingIntermentDate = false;
    };

    const syncAge = () => {
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
        if (died) died.max = today;

        if (wakeStart) {
            wakeStart.min = requestDate?.value || '';
            if (wakeStartPicker) wakeStartPicker.set('minDate', requestDate?.value || null);
        }

        if (wakePicker) {
            wakePicker.set('minDate', wakeStart?.value || requestDate?.value || null);
        }

        if (interPicker) {
            interPicker.set('minDate', funeral?.value || wakeStart?.value || requestDate?.value || 'today');
        }

        if (paidAt && died?.value) {
            paidAt.min = `${died.value}T00:00`;
        }
    };

    const syncPreferredPackage = () => {
        if (!customPkgRadio) return;

        if (prefPkg) customPkgRadio.dataset.name = prefPkg.value || 'Client Preference';
        if (prefPrice) customPkgRadio.dataset.price = prefPrice.value || '0';
        if (prefIncl) customPkgRadio.dataset.inclusions = prefIncl.value || '';
        if (prefFree) customPkgRadio.dataset.freebies = prefFree.value || '';

        if (customPkgPriceDisplay) customPkgPriceDisplay.textContent = fmt(customPkgRadio.dataset.price || 0);
        if (pkgAmount && customPkgRadio.checked) pkgAmount.value = customPkgRadio.dataset.price || '';
    };

    const renderPkg = (shouldToast = false) => {
        const selected = pkg();

        pkgCards.forEach((card) => {
            const radio = cardRadio(card);
            const active = !!radio?.checked;
            card.setAttribute('aria-checked', active ? 'true' : 'false');
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
        if (optionalAddOnsSection) optionalAddOnsSection.classList.toggle('hidden', !selected || selected === customPkgRadio);
        setAddOnsPanelOpen(false);
        if (optionalAddOnsList) {
            if (!selected || selected === customPkgRadio) {
                optionalAddOnsList.innerHTML = '';
            } else if (addOns.length === 0) {
                optionalAddOnsList.innerHTML = '<div class="rounded-lg border border-[#C9C5BB] bg-white px-3 py-3 text-sm font-semibold text-[#5F685F]">No add-ons are currently available.</div>';
            } else {
                optionalAddOnsList.innerHTML = addOns.map((addOn) => {
                    const checked = oldSelectedAddOns.has(String(addOn.id)) ? 'checked' : '';
                    const searchable = [addOn.name, addOn.category, addOn.description, addOn.unit].filter(Boolean).join(' ').toLowerCase();
                    return `
                        <label class="optional-add-on-row flex items-start gap-3 rounded-lg border border-[#C9C5BB] bg-white px-3 py-3 text-sm text-[#333333] hover:bg-[#F3F0E8] focus-within:ring-2 focus-within:ring-[#3E4A3D]" data-add-on-search="${escapeHtml(searchable)}">
                            <input type="checkbox" name="selected_add_ons[]" value="${escapeHtml(addOn.id)}" ${checked} class="optional-add-on-checkbox mt-1 h-4 w-4 rounded border-[#C9C5BB] text-[#3E4A3D] focus:ring-[#3E4A3D]" data-name="${escapeHtml(addOn.name)}" data-description="${escapeHtml(addOn.description || '')}" data-price="${escapeHtml(addOn.price)}" data-unit="${escapeHtml(addOn.unit || '')}" data-category="${escapeHtml(addOn.category || '')}">
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-baseline justify-between gap-2">
                                    <span class="font-bold">${escapeHtml(addOn.name)}</span>
                                    <span class="font-black">&#8369; ${fmt(addOn.price)}${addOn.unit ? ` / ${escapeHtml(addOn.unit)}` : ''}</span>
                                </span>
                                <span class="mt-1 flex flex-wrap items-center gap-2">
                                    ${addOn.category ? `<span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500">${escapeHtml(addOn.category)}</span>` : ''}
                                    ${addOn.unit ? `<span class="text-[11px] font-bold text-[#5F685F]">Unit: ${escapeHtml(addOn.unit)}</span>` : ''}
                                </span>
                                ${addOn.description ? `<span class="mt-1 block text-xs font-medium text-[#5F685F]">${escapeHtml(addOn.description)}</span>` : ''}
                                <span class="mt-2 hidden text-xs font-bold text-[#3E4A3D]" data-add-on-quantity-note>Quantity: 1 ${escapeHtml(addOn.unit || 'item')} | Line total: PHP ${fmt(addOn.price)}</span>
                            </span>
                        </label>`;
                }).join('');
            }
        }
        if (optionalAddOnsSearch) optionalAddOnsSearch.value = '';
        if (chooseAddOnsBtn) {
            const unavailable = !selected || selected === customPkgRadio || addOns.length === 0;
            chooseAddOnsBtn.disabled = unavailable;
            chooseAddOnsBtn.classList.toggle('opacity-50', unavailable);
            chooseAddOnsBtn.classList.toggle('cursor-not-allowed', unavailable);
            chooseAddOnsBtn.innerHTML = addOns.length === 0
                ? '<i class="bi bi-slash-circle"></i> No Add-ons Available'
                : (oldSelectedAddOns.size ? '<i class="bi bi-pencil-square"></i> Change Add-ons' : '<i class="bi bi-plus-circle"></i> Choose Add-ons');
        }
        if (selectedAddOnsTotal) selectedAddOnsTotal.textContent = fmt(addOnsTotal());
        if (draftAddOnsTotal) draftAddOnsTotal.textContent = fmt(addOnsTotal());
        if (selectedAddOnsLines) {
            const applied = appliedAddOns();
            selectedAddOnsLines.classList.toggle('hidden', applied.length === 0);
            selectedAddOnsLines.innerHTML = applied.map((addOn) => `
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[#E5E0D5] bg-[#FBFAF7] px-3 py-2">
                    <span class="min-w-0">
                        <span class="block font-bold">${escapeHtml(addOn.name)}</span>
                        <span class="block text-xs font-medium text-[#5F685F]">Qty 1${addOn.unit ? ` ${escapeHtml(addOn.unit)}` : ''}</span>
                    </span>
                    <span class="font-black">&#8369; ${fmt(addOn.price)}</span>
                </div>
            `).join('');
        }
        const summaryTitle = selectedAddOnsSummary?.querySelector('.font-bold');
        const summaryHelp = selectedAddOnsSummary?.querySelector('.text-xs');
        if (summaryTitle) summaryTitle.textContent = oldSelectedAddOns.size ? `${oldSelectedAddOns.size} add-on(s) selected.` : (addOns.length ? 'No add-ons selected.' : 'No add-ons are currently available.');
        if (summaryHelp) summaryHelp.textContent = oldSelectedAddOns.size ? 'Applied add-ons are shown below. Use Change Add-ons to update them.' : 'Optional add-ons are not included in the base package price.';

        if (structuredPricingSection) structuredPricingSection.classList.toggle('hidden', !selected || selected === customPkgRadio);
        if (!selected || selected === customPkgRadio) setPackageAdjustmentsOpen(false);

        syncPreferredPackage();

        const isCustomSelected = selected === customPkgRadio;
        if (customPkgFields) {
            customPkgFields.classList.toggle('hidden', !isCustomSelected);
        }

        [prefPkg, prefPrice, prefIncl, prefFree].forEach((field) => {
            if (!field) return;
            field.disabled = !isCustomSelected;
            if (!isCustomSelected) field.setCustomValidity('');
        });

        if (selected && shouldToast) {
            const name = selected.dataset.name || 'this package';
            showPackageToast(`You selected the ${name} package.`);
        }
    };

    const syncControls = () => {
        enforceSeniorEligibility(false);

        if (deceasedAddr && clientAddr && deceasedAddr.dataset.manual !== '1') deceasedAddr.value = clientAddr.value;

        setHidden(seniorIdWrap, seniorId, senior?.value !== '1');
        if (seniorId) seniorId.required = false;

        setHidden(proofWrap, proofInput, senior?.value !== '1');
        if (proofInput) proofInput.required = false;

        if (payWrap) payWrap.classList.remove('hidden');

        if (amountPaid) {
            amountPaid.disabled = !payNow();
            amountPaid.readOnly = payType() === 'FULL';
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

    const renderReview = () => {
        const selected = pkg();
        const t = totals();

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
                detailRow('Funeral Service Date & Time', formatScheduleDateTime(f.elements.funeral_service_at?.value, f.elements.funeral_service_time?.value)),
                detailRow('Interment Date & Time', formatScheduleDateTime(f.elements.interment_at?.value, f.elements.interment_time?.value)),
                detailRow('Wake Duration', wakeDurationLabel(f.elements.wake_days?.value)),
                detailRow('Retrieval Excess KM', applyRetrievalExcess?.checked ? textOrDash(retrievalExcessInput?.value) : 'Within coverage'),
                detailRow('Hearse Excess KM', applyHearseExcess?.checked ? textOrDash(hearseExcessInput?.value) : 'Within coverage'),
                detailRow('Wake Location', textOrDash(f.elements.wake_location?.value)),
                detailRow('Place of Interment', textOrDash(f.elements.place_of_cemetery?.value)),
                detailRow('Case Status', textOrDash(f.elements.case_status?.value)),
            ].join('');
        }

        if (reviewPackage) {
            const selectedAddOnRows = appliedAddOns();
            reviewPackage.innerHTML = [
                detailRow('Selected Package', selected?.dataset?.name || '-'),
                detailRow('Package Price', `PHP ${fmt(t.packagePrice)}`),
                detailRow('Inclusions', list(selected?.dataset.inclusions).join(', ') || '-'),
                detailRow('Freebies / Notes', list(selected?.dataset.freebies).join(', ') || '-'),
                detailRow('Replacement Casket', selectedReplacementCasket()?.name || 'Use included casket'),
                detailRow('Optional Add-ons', selectedAddOnRows.length
                    ? selectedAddOnRows.map((addOn) => `${addOn.name} x1 - PHP ${fmt(addOn.price)}`).join(', ')
                    : 'No optional add-ons selected.'),
            ].join('');
        }

        if (reviewBilling) {
            reviewBilling.innerHTML = [
                detailRow('Package Price', `PHP ${fmt(t.packagePrice)}`),
                detailRow('Automatic Charges', `PHP ${fmt(t.structuredTotal)}`),
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

    const computeWakeDays = () => {
        if (!wakeDays) return;

        const wakeDate = getDateValue(wakeStart, wakeStartPicker);
        const serviceDate = getDateValue(interment, interPicker);

        if (!wakeDate || !serviceDate || serviceDate < wakeDate) {
            wakeDays.value = '';
            if (wakeDurationDisplay) wakeDurationDisplay.textContent = 'Auto-calculated';
            const helper = document.getElementById('wake_days_helper');
            if (helper) helper.textContent = 'Calculated from Wake Start Date through Interment Date, inclusive.';
            return;
        }

        const wakeOnly = new Date(wakeDate.getFullYear(), wakeDate.getMonth(), wakeDate.getDate());
        const serviceOnly = new Date(serviceDate.getFullYear(), serviceDate.getMonth(), serviceDate.getDate());
        const diffDays = Math.floor((serviceOnly - wakeOnly) / 86400000) + 1;

        wakeDays.value = diffDays >= 0 ? String(diffDays) : '';
        if (wakeDurationDisplay) wakeDurationDisplay.textContent = wakeDurationLabel(diffDays);

        const helper = document.getElementById('wake_days_helper');
        if (helper) helper.textContent = `Wake Duration: ${wakeDurationLabel(diffDays)}`;
    };

    const combineDateAndTime = (date, timeValue) => {
        if (!date || !timeValue) return null;
        const [hours, minutes] = String(timeValue).split(':').map(Number);
        if (!Number.isFinite(hours) || !Number.isFinite(minutes)) return null;

        const combined = new Date(date.getFullYear(), date.getMonth(), date.getDate(), hours, minutes, 0, 0);
        return Number.isNaN(combined.getTime()) ? null : combined;
    };

    const initScheduleTimePicker = (hiddenInput) => {
        if (!hiddenInput) return;

        const picker = document.querySelector(`[data-time-picker="${hiddenInput.id}"]`);
        const displayInput = document.querySelector(`[data-time-display-for="${hiddenInput.id}"]`);
        const toggleButton = document.querySelector(`[data-time-toggle-for="${hiddenInput.id}"]`);
        const popover = picker?.querySelector('[data-time-popover]');
        if (!picker || !displayInput || !popover) return;

        const state = { hour: '', minute: '', period: '' };
        const pad = (value) => String(value).padStart(2, '0');
        const allOptions = [...picker.querySelectorAll('.time-option')];

        const parseTypedTime = (value) => {
            const raw = String(value || '').trim().toUpperCase().replace(/\s+/g, '');
            if (!raw) return null;

            const amPmMatch = raw.match(/^(\d{1,2})(?::?(\d{2}))?(AM|PM)$/);
            if (amPmMatch) {
                let hour = Number(amPmMatch[1]);
                const minute = Number(amPmMatch[2] ?? '00');
                const period = amPmMatch[3];

                if (hour < 1 || hour > 12 || minute < 0 || minute > 59) return null;
                hour = hour % 12;
                if (period === 'PM') hour += 12;
                return `${pad(hour)}:${pad(minute)}`;
            }

            const twentyFourHourMatch = raw.match(/^(\d{1,2})(?::?(\d{2}))$/);
            if (twentyFourHourMatch) {
                const hour = Number(twentyFourHourMatch[1]);
                const minute = Number(twentyFourHourMatch[2]);
                if (hour < 0 || hour > 23 || minute < 0 || minute > 59) return null;
                return `${pad(hour)}:${pad(minute)}`;
            }

            const hourOnly = raw.match(/^(\d{1,2})$/);
            if (hourOnly) {
                const hour = Number(hourOnly[1]);
                if (hour < 1 || hour > 12) return null;
                return `${pad(hour)}:00`;
            }

            return null;
        };

        const formatDisplay = () => {
            if (!state.hour || !state.minute || !state.period) return '';
            return `${state.hour}:${state.minute} ${state.period}`;
        };

        const closePicker = () => {
            picker.classList.remove('is-open');
            displayInput.setAttribute('aria-expanded', 'false');
        };

        const openPicker = () => {
            document.querySelectorAll('.time-input-wrap.is-open').forEach((openWrap) => {
                if (openWrap !== picker) {
                    openWrap.classList.remove('is-open');
                    openWrap.querySelector('.schedule-time-display')?.setAttribute('aria-expanded', 'false');
                }
            });
            picker.classList.add('is-open');
            displayInput.setAttribute('aria-expanded', 'true');
            const selected = picker.querySelector('.time-option.is-selected');
            selected?.scrollIntoView({ block: 'nearest' });
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

        const syncSelectedStyles = () => {
            allOptions.forEach((option) => {
                option.classList.toggle('is-selected', state[option.dataset.timePart] === option.dataset.timeValue);
            });
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
                syncSelectedStyles();
                return;
            }

            state.hour = pad(hour24 % 12 || 12);
            state.minute = pad(minute);
            state.period = hour24 >= 12 ? 'PM' : 'AM';
            displayInput.value = formatDisplay();
            syncSelectedStyles();
        };

        const applyTypedTime = (showError = false) => {
            const typed = displayInput.value.trim();
            if (!typed) {
                state.hour = '';
                state.minute = '';
                state.period = '';
                hiddenInput.value = '';
                hiddenInput.setCustomValidity('');
                displayInput.setCustomValidity('');
                syncSelectedStyles();
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            }

            const parsed = parseTypedTime(typed);
            if (!parsed) {
                const message = 'Use a valid time like 8:30 AM.';
                hiddenInput.setCustomValidity(message);
                displayInput.setCustomValidity(showError ? message : '');
                if (showError) displayInput.reportValidity();
                return false;
            }

            hiddenInput.value = parsed;
            hiddenInput.setCustomValidity('');
            displayInput.setCustomValidity('');
            applyHiddenValueToPicker();
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            return true;
        };

        toggleButton?.addEventListener('click', () => {
            picker.classList.contains('is-open') ? closePicker() : openPicker();
        });

        displayInput.addEventListener('focus', () => {
            displayInput.select();
        });

        displayInput.addEventListener('input', () => {
            applyTypedTime(false);
        });

        displayInput.addEventListener('blur', () => {
            applyTypedTime(true);
        });

        displayInput.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                openPicker();
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                if (applyTypedTime(true)) closePicker();
            }
        });

        allOptions.forEach((option) => {
            option.addEventListener('click', () => {
                state[option.dataset.timePart] = option.dataset.timeValue;
                syncSelectedStyles();
                syncHiddenValue();
                if (state.hour && state.minute && state.period) closePicker();
            });
        });

        picker.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closePicker();
                displayInput.focus();
            }
        });

        applyHiddenValueToPicker();
        hiddenInput.addEventListener('change', () => {
            if (!picker.classList.contains('is-open')) applyHiddenValueToPicker();
        });
    };

    [wakeStartTime, funeralTime, intermentTime].forEach(initScheduleTimePicker);

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.time-input-wrap.is-open').forEach((picker) => {
            if (!picker.contains(event.target)) {
                picker.classList.remove('is-open');
                picker.querySelector('.schedule-time-display')?.setAttribute('aria-expanded', 'false');
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
            errEl.textContent = message;
            errEl.classList.remove('hidden');
        } else {
            targets.forEach(t => t && t.classList.remove('field-error'));
            targets.forEach(t => t?.closest?.('.form-select-wrap')?.classList.remove('is-error'));
            targets.forEach(t => t && t.removeAttribute('aria-invalid'));
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

    const validateWakeInterment = (mode = 'full') => {
        const wakeStartRaw = getRawDateString(wakeStart, wakeStartPicker);
        const wakeRaw = getRawDateString(funeral, wakePicker);
        const interRaw = getRawDateString(interment, interPicker);
        const wakeStartDate = getDateValue(wakeStart, wakeStartPicker);
        const wakeDate = getDateValue(funeral, wakePicker);
        const interDate = getDateValue(interment, interPicker);
        const requestDateValue = getDateValue(requestDate);
        const wakeStartDateTime = combineDateAndTime(wakeStartDate, wakeStartTime?.value);
        const funeralDateTime = combineDateAndTime(wakeDate, funeralTime?.value);
        const intermentDateTime = combineDateAndTime(interDate, intermentTime?.value);

        wakeStart?.setCustomValidity('');
        wakeStartTime?.setCustomValidity('');
        funeral?.setCustomValidity('');
        funeralTime?.setCustomValidity('');
        interment?.setCustomValidity('');
        intermentTime?.setCustomValidity('');
        intermentWarning?.classList.add('hidden');

        if (mode === 'full') {
            setFieldError(wakeStart, wakeStartErr, '', wakeStartPicker?.altInput);
            setFieldError(funeral, wakeErr, '', wakePicker?.altInput);
            setFieldError(interment, intermentErr, '', interPicker?.altInput);
        }

        if (wakeStart) {
            if (!wakeStartRaw || !wakeStartTime?.value) {
                if (mode === 'full') {
                    wakeStart.setCustomValidity('Please select a wake start date and time.');
                    wakeStartTime?.setCustomValidity('Please select a wake start date and time.');
                }
            } else if (!wakeStartDate) {
                wakeStart.setCustomValidity('Please select a wake start date and time.');
            }
        }

        if (funeral) {
            if (!wakeRaw || !funeralTime?.value) {
                if (mode === 'full') {
                    funeral.setCustomValidity('Please select a funeral service date and time.');
                    funeralTime?.setCustomValidity('Please select a funeral service date and time.');
                }
            } else if (!wakeDate) {
                funeral.setCustomValidity('Please select a funeral service date and time.');
            }
        }

        if (interment) {
            if (!interRaw || !intermentTime?.value) {
                if (mode === 'full') {
                    interment.setCustomValidity('Please select an interment date and time.');
                    intermentTime?.setCustomValidity('Please select an interment date and time.');
                }
            } else if (!interDate) {
                interment.setCustomValidity('Please select an interment date and time.');
            }
        }

        if (requestDateValue && wakeStartDate) {
            const requestOnly = new Date(requestDateValue.getFullYear(), requestDateValue.getMonth(), requestDateValue.getDate());
            const wakeOnly = new Date(wakeStartDate.getFullYear(), wakeStartDate.getMonth(), wakeStartDate.getDate());
            if (wakeOnly < requestOnly) {
                wakeStart.setCustomValidity('Wake start date cannot be before the request/recorded date.');
            }
        }

        if (wakeStartDateTime && funeralDateTime && funeralDateTime < wakeStartDateTime) {
            funeral.setCustomValidity('Funeral service date/time cannot be before the wake start date/time.');
        }

        if (wakeDate && interDate) {
            const serviceOnly = dateOnly(wakeDate);
            const interOnly = dateOnly(interDate);

            if (interOnly < serviceOnly) {
                interment.setCustomValidity('Interment date cannot be before the funeral service date.');
            } else if (interOnly > serviceOnly) {
                intermentWarning?.classList.remove('hidden');
            } else if (funeralTime?.value && intermentTime?.value && intermentDateTime && funeralDateTime && intermentDateTime < funeralDateTime) {
                interment.setCustomValidity('Interment time cannot be before the funeral service time.');
                intermentTime?.setCustomValidity('Interment time cannot be before the funeral service time.');
            }
        }

        if (wakeStartRaw || wakeStartTime?.value || mode === 'full') {
            setFieldError(wakeStart, wakeStartErr, wakeStart?.validationMessage || wakeStartTime?.validationMessage || '', wakeStartPicker?.altInput);
        }

        if (wakeRaw || mode === 'full') {
            setFieldError(funeral, wakeErr, funeral?.validationMessage || funeralTime?.validationMessage || '', wakePicker?.altInput);
        }
        if (interRaw || mode === 'full') {
            setFieldError(interment, intermentErr, interment?.validationMessage || intermentTime?.validationMessage || '', interPicker?.altInput);
        }
    };

    const render = () => {
        computeWakeDays();
        validateDobDod('light');
        validateWakeInterment('light');
        syncAge();

        const t = totals();

        if (summaryPackage) summaryPackage.textContent = fmt(t.packagePrice);
        if (summaryAddOns) summaryAddOns.textContent = fmt(t.selectedAddOns);
        if (selectedAddOnsTotal) selectedAddOnsTotal.textContent = fmt(t.selectedAddOns);
        if (structuredChargesTotal) structuredChargesTotal.textContent = fmt(t.structuredTotal);
        if (structuredChargesTotalModal) structuredChargesTotalModal.textContent = fmt(t.structuredTotal);
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

        const replacementMode = casketModeRadios.find((radio) => radio.checked)?.value === 'replacement';
        if (replacementCasketWrap) replacementCasketWrap.classList.toggle('hidden', !replacementMode);
        if (replacementCasket && !replacementMode && replacementCasket.value) {
            replacementCasket.value = '';
        }
        const includedCasket = includedCasketFor();
        if (includedCasketSummary) {
            const name = includedCasket?.name || 'No included casket configured';
            const material = includedCasket?.material ? ` - ${includedCasket.material}` : '';
            const value = num(includedCasket?.reference_value) > 0
                ? `Reference Value: PHP ${fmt(includedCasket.reference_value)}`
                : 'Reference Value not configured';
            includedCasketSummary.textContent = `${name}${material}. ${value}.`;
        }
        if (casketAdjustmentAmount) casketAdjustmentAmount.textContent = fmt(t.structured.casket);

        if (casketUpgradePreview) {
            const included = includedCasketFor();
            const selected = selectedReplacementCasket();
            if (isCustomSelected()) {
                casketUpgradePreview.textContent = 'Custom packages do not calculate casket upgrades yet.';
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
        const flatpickrDateFields = ['born', 'died', 'wake_start_date', 'funeral_service_at', 'interment_at'];

        for (const field of [...panel.querySelectorAll('input, select, textarea')].filter((element) => element.type !== 'hidden' && !element.disabled)) {
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
                field.reportValidity();
                return false;
            }

            if (isFlatpickrDate) {
                continue;
            }

            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }

        return true;
    };

    const validate = (targetStep) => {
        clearMessages();

        if (shouldLockOtherBranchIntake()) {
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Before you continue, please select a branch so we can record the case correctly.';
            showBranchToast('Before you continue, please select a branch so we can record the case correctly.');
            return false;
        }

        const panel = panels.find((element) => Number(element.dataset.step) === targetStep);
        if (!panel) return true;

        if (targetStep === 1 && !validateClientInformationStep()) return false;
        if (targetStep === 2 && !validateDeceasedInformationStep()) return false;
        if (targetStep === 2) validateDobDod('full');
        if (targetStep === 3) validateWakeInterment('full');

        if (!validatePanelFields(panel)) return false;

        if (targetStep === 4) {
            for (const row of document.querySelectorAll('[data-additional-service-row]')) {
                const description = row.querySelector('[data-additional-description]');
                const amount = row.querySelector('[data-additional-amount]');
                const hasDescription = !!description?.value?.trim();
                const hasAmount = num(amount?.value) > 0;

                if (hasAmount && !hasDescription) {
                    return showFieldError(description, 'Description is required for this additional charge.');
                }

                if (hasDescription && !hasAmount) {
                    return showFieldError(amount, 'Enter an amount greater than zero for this line.');
                }
            }
        }

        if (targetStep === 1 && (!branch.value || (isOtherEntryMode && !isBranchSelectedForOtherMode()))) {
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Before you continue, please select a branch so we can record the case correctly.';
            return false;
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
                bornPicker?.altInput?.focus();
                return false;
            }

            if (!deathRaw) {
                died.setCustomValidity('Date of death is required.');
                setFieldError(died, diedErr, 'Date of death is required.', diedPicker?.altInput);
                diedPicker?.altInput?.focus();
                return false;
            }

            if (!birthDate) {
                born.setCustomValidity('Please enter a valid date of birth.');
                setFieldError(born, bornErr, 'Please enter a valid date of birth.', bornPicker?.altInput);
                bornPicker?.altInput?.focus();
                return false;
            }

            if (!deathDate) {
                died.setCustomValidity('Please enter a valid date of death.');
                setFieldError(died, diedErr, 'Please enter a valid date of death.', diedPicker?.altInput);
                diedPicker?.altInput?.focus();
                return false;
            }

            if (birthDate > todayEnd) {
                born.setCustomValidity('Date of birth cannot be in the future.');
                setFieldError(born, bornErr, 'Date of birth cannot be in the future.', bornPicker?.altInput);
                bornPicker?.altInput?.focus();
                return false;
            }

            if (deathDate > todayEnd) {
                died.setCustomValidity('Date of death cannot be in the future.');
                setFieldError(died, diedErr, 'Date of death cannot be in the future.', diedPicker?.altInput);
                diedPicker?.altInput?.focus();
                return false;
            }

            if (deathDate < birthDate) {
                died.setCustomValidity('Date of death cannot be earlier than date of birth.');
                setFieldError(died, diedErr, 'Date of death cannot be earlier than date of birth.', diedPicker?.altInput);
                diedPicker?.altInput?.focus();
                return false;
            }

            const years = Number.parseInt(age?.value ?? '', 10);
            if (senior?.value === '1' && Number.isFinite(years) && years < 60) {
                senior.setCustomValidity('Senior Citizen can only be set to Yes when computed age is at least 60.');
                senior.reportValidity();
                return false;
            }
        }
        if (targetStep === 3 && !pkg()) {
            packageError?.classList.remove('hidden');
            return false;
        }

        if (targetStep === 3) {
            const wakeStartRaw = getRawDateString(wakeStart, wakeStartPicker);
            const wakeRaw = getRawDateString(funeral, wakePicker);
            const interRaw = getRawDateString(interment, interPicker);
            const wakeStartDate = getDateValue(wakeStart, wakeStartPicker);
            const wakeDate = getDateValue(funeral, wakePicker);
            const interDate = getDateValue(interment, interPicker);
            const requestDateValue = getDateValue(requestDate);
            const wakeStartDateTime = combineDateAndTime(wakeStartDate, wakeStartTime?.value);
            const funeralDateTime = combineDateAndTime(wakeDate, funeralTime?.value);
            const intermentDateTime = combineDateAndTime(interDate, intermentTime?.value);

            setFieldError(wakeStart, wakeStartErr, '', wakeStartPicker?.altInput);
            setFieldError(funeral, wakeErr, '', wakePicker?.altInput);
            setFieldError(interment, intermentErr, '', interPicker?.altInput);
            wakeStart?.setCustomValidity('');
            wakeStartTime?.setCustomValidity('');
            funeral?.setCustomValidity('');
            funeralTime?.setCustomValidity('');
            interment?.setCustomValidity('');
            intermentTime?.setCustomValidity('');

            if (!wakeStartRaw || !wakeStartTime?.value) {
                wakeStart.setCustomValidity('Please select a wake start date and time.');
                wakeStartTime?.setCustomValidity('Please select a wake start date and time.');
                setFieldError(wakeStart, wakeStartErr, 'Please select a wake start date and time.', wakeStartPicker?.altInput);
                wakeStartPicker?.altInput?.focus();
                return false;
            }

            if (!wakeRaw || !funeralTime?.value) {
                funeral.setCustomValidity('Please select a funeral service date and time.');
                funeralTime?.setCustomValidity('Please select a funeral service date and time.');
                setFieldError(funeral, wakeErr, 'Please select a funeral service date and time.', wakePicker?.altInput);
                wakePicker?.altInput?.focus();
                return false;
            }

            if (!interRaw || !intermentTime?.value) {
                interment.setCustomValidity('Please select an interment date and time.');
                intermentTime?.setCustomValidity('Please select an interment date and time.');
                setFieldError(interment, intermentErr, 'Please select an interment date and time.', interPicker?.altInput);
                interPicker?.altInput?.focus();
                return false;
            }

            if (!wakeStartDate) {
                wakeStart.setCustomValidity('Please select a wake start date and time.');
                setFieldError(wakeStart, wakeStartErr, 'Please select a wake start date and time.', wakeStartPicker?.altInput);
                wakeStartPicker?.altInput?.focus();
                return false;
            }

            if (!wakeDate) {
                funeral.setCustomValidity('Please select a funeral service date and time.');
                setFieldError(funeral, wakeErr, 'Please select a funeral service date and time.', wakePicker?.altInput);
                wakePicker?.altInput?.focus();
                return false;
            }

            if (!interDate) {
                interment.setCustomValidity('Please select an interment date and time.');
                setFieldError(interment, intermentErr, 'Please select an interment date and time.', interPicker?.altInput);
                interPicker?.altInput?.focus();
                return false;
            }

            const requestOnly = requestDateValue ? new Date(requestDateValue.getFullYear(), requestDateValue.getMonth(), requestDateValue.getDate()) : null;
            const wakeOnly = new Date(wakeStartDate.getFullYear(), wakeStartDate.getMonth(), wakeStartDate.getDate());
            if (requestOnly && wakeOnly < requestOnly) {
                wakeStart.setCustomValidity('Wake start date cannot be before the request/recorded date.');
                setFieldError(wakeStart, wakeStartErr, 'Wake start date cannot be before the request/recorded date.', wakeStartPicker?.altInput);
                wakeStartPicker?.altInput?.focus();
                return false;
            }

            if (funeralDateTime < wakeStartDateTime) {
                funeral.setCustomValidity('Funeral service date/time cannot be before the wake start date/time.');
                setFieldError(funeral, wakeErr, 'Funeral service date/time cannot be before the wake start date/time.', wakePicker?.altInput);
                wakePicker?.altInput?.focus();
                return false;
            }

            const serviceOnly = dateOnly(wakeDate);
            const interOnly = dateOnly(interDate);
            if (interOnly < serviceOnly) {
                interment.setCustomValidity('Interment date cannot be before the funeral service date.');
                setFieldError(interment, intermentErr, 'Interment date cannot be before the funeral service date.', interPicker?.altInput);
                interPicker?.altInput?.focus();
                return false;
            }

            if (sameDate(wakeDate, interDate) && intermentDateTime < funeralDateTime) {
                interment.setCustomValidity('Interment time cannot be before the funeral service time.');
                intermentTime?.setCustomValidity('Interment time cannot be before the funeral service time.');
                setFieldError(interment, intermentErr, 'Interment time cannot be before the funeral service time.', interPicker?.altInput);
                interPicker?.altInput?.focus();
                return false;
            }
        }

        if (targetStep === 4 && payNow()) {
            const t = totals();

            if (!payType()) {
                paymentTypeError?.classList.remove('hidden');
                return false;
            }

            if (!amountPaid?.value) {
                amountPaid.setCustomValidity('Please enter amount paid.');
                amountPaid.reportValidity();
                return false;
            }

            if (!paidAt?.value) {
                paidAt.setCustomValidity('Please select payment date.');
                paidAt.reportValidity();
                return false;
            }

            if (!validatePaymentMethodDetails()) {
                return false;
            }

            if (payType() === 'FULL' && t.paid < t.total) {
                amountPaid.setCustomValidity('Full payment must match the total amount due.');
                amountPaid.reportValidity();
                return false;
            }

            if (payType() === 'PARTIAL' && t.paid >= t.total) {
                amountPaid.setCustomValidity('Partial payment must be less than the total amount due.');
                amountPaid.reportValidity();
                return false;
            }
        }

        if (targetStep === 5) {
            const confirmBox = document.getElementById('confirm_review');
            if (confirmBox && !confirmBox.checked) {
                confirmBox.setCustomValidity('Please confirm that the information is correct.');
                confirmBox.reportValidity();
                return false;
            }
        }

        return true;
    };

    const scrollToStepTop = (smooth = false) => {
        const anchor = wizardSteps || formContent;
        if (!anchor) return;

        const scroller = anchor.closest('.page-content') || document.querySelector('.page-content');
        if (scroller) {
            const scrollerRect = scroller.getBoundingClientRect();
            const anchorRect = anchor.getBoundingClientRect();
            const scrollTop = Math.max(scroller.scrollTop + (anchorRect.top - scrollerRect.top) - 8, 0);

            scroller.scrollTo({
                top: scrollTop,
                behavior: smooth ? 'smooth' : 'auto'
            });
            return;
        }

        const topbarHeight = document.querySelector('.topbar')?.getBoundingClientRect?.().height || 0;
        const anchorTop = anchor.getBoundingClientRect().top + window.scrollY;
        const scrollTop = Math.max(anchorTop - topbarHeight - 10, 0);
        window.scrollTo({ top: scrollTop, behavior: smooth ? 'smooth' : 'auto' });
    };

    const syncProgressVisibility = () => {
        tabs.forEach((tab) => {
            const tabStep = Number(tab.dataset.step);
            tab.classList.toggle('is-locked', tabStep > maxUnlockedStep);
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
        step = Math.max(1, Math.min(totalSteps, targetStep));

        panels.forEach((panel) => panel.classList.toggle('hidden', Number(panel.dataset.step) !== step));

        tabs.forEach((tab) => {
            const tabStep = Number(tab.dataset.step);
            const active = tabStep === step;
            const completed = tabStep < step;

            tab.classList.toggle('active-step', active);
            tab.classList.toggle('completed-step', completed);
        });
        syncProgressVisibility();

        if (prev) prev.disabled = step === 1;
        next?.classList.toggle('hidden', step === totalSteps);
        save?.classList.toggle('hidden', step !== totalSteps);

        if (next) {
            next.innerHTML = step === totalSteps - 1
                ? 'Review <i class="bi bi-chevron-right text-[10px] ml-1"></i>'
                : 'Continue <i class="bi bi-chevron-right text-[10px] ml-1"></i>';
        }

        if (step === totalSteps) renderReview();
        if (scroll && step !== previousStep) scrollToStepTop(smooth);

        // Update progress rail
        const progressFill = document.getElementById('intakeProgressFill');
        if (progressFill) {
            progressFill.style.width = `${(step / totalSteps) * 100}%`;
        }
    };

    const syncBranch = (showNotice = false) => {
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
            syncBranch(true);
            render();
        });
    });

    if (deceasedAddr && clientAddr && String(deceasedAddr.value || '').trim() !== '' && deceasedAddr.value !== clientAddr.value) {
        deceasedAddr.dataset.manual = '1';
    }

    clientAddr?.addEventListener('input', syncControls);

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
        validateWakeInterment('full');
        syncAge();
        syncDateConstraints();
        render();
    });

    [wakeStart, wakeStartTime, funeral, funeralTime, interment, intermentTime, requestDate].forEach((element) => {
        element?.addEventListener('change', () => {
            if (element === funeral) syncIntermentDateFromFuneral();
            if (element === interment) markIntermentDateManual();
            computeWakeDays();
            validateWakeInterment('full');
            syncDateConstraints();
            render();
        });
    });

    funeral?.addEventListener('change', () => {
        syncIntermentDateFromFuneral();
        computeWakeDays();
        validateWakeInterment('full');
        render();
    });

    interment?.addEventListener('change', () => {
        markIntermentDateManual();
        computeWakeDays();
        validateWakeInterment('full');
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
        validateWakeInterment('full');
        render();
    });

    interment?.addEventListener('blur', () => {
        const raw = getRawDateString(interment, interPicker);
        if (!raw) return;
        computeWakeDays();
        validateWakeInterment('full');
        render();
    });

    if (typeof flatpickr !== 'undefined') {
        const ensurePickerVisible = (instance) => {
            if (!instance) return;

            const anchor = instance.altInput || instance._positionElement || instance.input;
            const cal = instance.calendarContainer;
            if (!anchor || !cal) return;
            const scroller = anchor.closest('.page-content') || document.querySelector('.page-content');

            if (instance.config.positionElement !== anchor) {
                instance.set('positionElement', anchor);
            }

            const anchorRect = anchor.getBoundingClientRect();
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
            const estimatedHeight = instance.config.enableTime ? 520 : (cal.offsetHeight || 420);
            const spaceBelow = Math.max(viewportHeight - anchorRect.bottom, 0);
            const spaceAbove = Math.max(anchorRect.top, 0);
            const shouldOpenAbove = spaceBelow < estimatedHeight && spaceAbove > spaceBelow;
            const desiredPosition = shouldOpenAbove ? 'above left' : 'below left';

            if (instance.config.position !== desiredPosition) {
                instance.set('position', desiredPosition);
            }

            if (typeof instance._positionCalendar === 'function') {
                instance._positionCalendar();
            }

            const nudgeViewport = () => {
                const rect = cal.getBoundingClientRect();
                const pad = 10;
                let delta = 0;

                if (rect.bottom > viewportHeight - pad) {
                    delta = rect.bottom - (viewportHeight - pad);
                } else if (rect.top < pad) {
                    delta = rect.top - pad;
                }

                if (!delta) return;

                if (scroller) {
                    scroller.scrollTop += delta;
                } else {
                    window.scrollBy({ top: delta, left: 0, behavior: 'auto' });
                }

                if (typeof instance._positionCalendar === 'function') {
                    instance._positionCalendar();
                }
            };

            requestAnimationFrame(nudgeViewport);
        };

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

            const maxYear = dateYear(instance.config.maxDate) ?? (instance.input?.id === 'born' || instance.input?.id === 'died' ? currentYear : currentYear + 1);
            const minYear = dateYear(instance.config.minDate) ?? (instance.input?.id === 'born' || instance.input?.id === 'died' ? currentYear - 125 : currentYear - 1);
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

        const pickerBaseOpts = {
            position: 'auto left',
            appendTo: document.body,
            animate: true,
            monthSelectorType: 'dropdown',
            prevArrow: '<span>&lsaquo;</span>',
            nextArrow: '<span>&rsaquo;</span>',
            onReady: (selectedDates, dateStr, instance) => {
                enhanceYearDropdown(instance);
                ensurePickerVisible(instance);
            },
            onOpen: (selectedDates, dateStr, instance) => {
                const cal = instance.calendarContainer;
                if (cal) {
                    cal.classList.add('shadow-2xl', 'border', 'border-slate-200');
                }
                enhanceYearDropdown(instance);
                ensurePickerVisible(instance);
            },
            onMonthChange: (selectedDates, dateStr, instance) => {
                enhanceYearDropdown(instance);
                ensurePickerVisible(instance);
            },
            onYearChange: (selectedDates, dateStr, instance) => {
                enhanceYearDropdown(instance);
                ensurePickerVisible(instance);
            }
        };

        const rememberTypedValue = (picker, input, errorEl, type) => {
            const visible = picker?.altInput;
            if (!visible) return;

            input.dataset.userTyped = input.dataset.userTyped || '0';
            input.dataset.lastTypedValue = input.dataset.lastTypedValue || '';
            input.dataset.invalidTypedValue = input.dataset.invalidTypedValue || '';

            const invalidMessage = () => {
                if (type === 'born') return 'Please enter a valid birthdate.';
                if (type === 'died') return 'Please enter a valid date of death.';
                if (type === 'wake') return 'Please enter a valid funeral service date.';
                return 'Please enter a valid interment date and time.';
            };

            visible.addEventListener('input', () => {
                const raw = visible.value.trim();
                if (type === 'interment') markIntermentDateManual();
                input.dataset.userTyped = '1';
                input.dataset.lastTypedValue = raw;

                // While typing: no error on empty, only show if obviously invalid after some input
                if (!raw) {
                    input.dataset.invalidTypedValue = '';
                    input.setCustomValidity('');
                    setFieldError(input, errorEl, '', visible);
                    return;
                }

                const parsed = Date.parse(raw);
                if (Number.isNaN(parsed)) {
                    input.dataset.invalidTypedValue = raw;
                    input.setCustomValidity(invalidMessage());
                    setFieldError(input, errorEl, invalidMessage(), visible);
                    return;
                }

                input.dataset.invalidTypedValue = '';
                input.setCustomValidity('');
                setFieldError(input, errorEl, '', visible);
            });

            visible.addEventListener('blur', () => {
                const raw = (input.dataset.lastTypedValue || visible.value || '').trim();
                const invalidTyped = (input.dataset.invalidTypedValue || '').trim();
                const hasSelectedDate = !!picker.selectedDates.length;

                // untouched empty
                if (!raw && input.dataset.userTyped !== '1') {
                    input.setCustomValidity('');
                    setFieldError(input, errorEl, '', visible);
                    return;
                }

                // empty after typing
                if (!raw) {
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
                    validateWakeInterment('full');
                    setFieldError(input, errorEl, input.validationMessage, visible);
                }
            });
        };

        wakeStartPicker = flatpickr(wakeStart, {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
            clickOpens: false,
            defaultDate: wakeStart?.value || null,
            ...pickerBaseOpts,
            onChange: (selectedDates) => {
                wakeStart.dataset.userTyped = '0';
                wakeStart.dataset.lastTypedValue = '';
                wakeStart.setCustomValidity('');
                setFieldError(wakeStart, wakeStartErr, '', wakeStartPicker?.altInput);

                if (wakePicker && selectedDates[0]) {
                    wakePicker.set('minDate', selectedDates[0]);
                }

                computeWakeDays();
                validateWakeInterment('full');
                render();
            },
            onClose: (selectedDates, dateStr, instance) => {
                const raw = (wakeStart.dataset.lastTypedValue || instance.altInput?.value || '').trim();
                const invalidTyped = (wakeStart.dataset.invalidTypedValue || '').trim();

                if (invalidTyped || (raw && !selectedDates.length && Number.isNaN(Date.parse(raw)))) {
                    wakeStart.setCustomValidity('Please select a wake start date and time.');
                    setFieldError(wakeStart, wakeStartErr, 'Please select a wake start date and time.', instance.altInput);
                    return;
                }

                validateWakeInterment('full');
                setFieldError(wakeStart, wakeStartErr, wakeStart.validationMessage || '', instance.altInput);
            },
            onValueUpdate: () => {
                computeWakeDays();
                validateWakeInterment('light');
                setFieldError(wakeStart, wakeStartErr, wakeStart.validationMessage, wakeStartPicker?.altInput);
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
            onChange: (selectedDates) => {
                funeral.dataset.userTyped = '0';
                funeral.dataset.lastTypedValue = '';
                funeral.setCustomValidity('');
                setFieldError(funeral, wakeErr, '', wakePicker?.altInput);

                if (interPicker && selectedDates[0]) {
                    interPicker.set('minDate', selectedDates[0]);
                }

                syncIntermentDateFromFuneral();
                computeWakeDays();
                validateWakeInterment('full');
                render();
            },
            onClose: (selectedDates, dateStr, instance) => {
                const raw = (funeral.dataset.lastTypedValue || instance.altInput?.value || '').trim();
                const invalidTyped = (funeral.dataset.invalidTypedValue || '').trim();

                if (invalidTyped || (raw && !selectedDates.length && Number.isNaN(Date.parse(raw)))) {
                    funeral.setCustomValidity('Please enter a valid funeral service date.');
                    setFieldError(funeral, wakeErr, 'Please enter a valid funeral service date.', instance.altInput);
                    return;
                }

                if (!raw) {
                    funeral.setCustomValidity('');
                    setFieldError(funeral, wakeErr, '', instance.altInput);
                    return;
                }

                validateWakeInterment('full');
                setFieldError(funeral, wakeErr, funeral.validationMessage || '', instance.altInput);
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
                setFieldError(funeral, wakeErr, funeral.validationMessage, wakePicker?.altInput);
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
            minDate: funeral?.value || wakeStart?.value || requestDate?.value || 'today',
            ...pickerBaseOpts,
            onChange: () => {
                markIntermentDateManual();
                interment.dataset.userTyped = '0';
                interment.dataset.lastTypedValue = '';
                interment.setCustomValidity('');
                setFieldError(interment, intermentErr, '', interPicker?.altInput);

                computeWakeDays();
                validateWakeInterment('full');
                render();
            },
            onClose: (selectedDates, dateStr, instance) => {
            const raw = (interment.dataset.lastTypedValue || instance.altInput?.value || '').trim();
            const invalidTyped = (interment.dataset.invalidTypedValue || '').trim();

            if (invalidTyped || (raw && !selectedDates.length && Number.isNaN(Date.parse(raw)))) {
                interment.setCustomValidity('Please select an interment date and time.');
                setFieldError(interment, intermentErr, 'Please select an interment date and time.', instance.altInput);
                return;
            }

            if (!raw) {
                interment.setCustomValidity('');
                setFieldError(interment, intermentErr, '', instance.altInput);
                return;
            }

            validateWakeInterment('full');
            setFieldError(interment, intermentErr, interment.validationMessage || '', instance.altInput);
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
                setFieldError(interment, intermentErr, interment.validationMessage, interPicker?.altInput);
                render();
            }
        });

        const wakeTrigger = document.getElementById('wake_picker_trigger');
        if (wakeTrigger) wakeTrigger.addEventListener('click', () => wakePicker && wakePicker.open());

        const wakeStartTrigger = document.getElementById('wake_start_picker_trigger');
        if (wakeStartTrigger) wakeStartTrigger.addEventListener('click', () => wakeStartPicker && wakeStartPicker.open());

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
                    paidAt.min = `${died.value}T00:00`;
                }

                syncAge();
                validateDobDod('full');
                validateWakeInterment('full');
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

    senior?.addEventListener('change', () => {
        if (!enforceSeniorEligibility(true)) {
            syncControls();
            render();
            return;
        }
        clearFieldMessage(senior);
        syncControls();
        render();
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
    const paymentMethodHint = document.getElementById('payment_method_hint');
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
        const hasMethod = !!selected;
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
        if (paymentMethodHint) {
            paymentMethodHint.textContent = hasMethod
                ? 'Payment details are now available below.'
                : 'Leave unselected if no payment is being recorded yet.';
        }
        if (cashReferenceWrap) cashReferenceWrap.classList.toggle('hidden', !hasMethod || isCashless);
        if (cashlessWrap) cashlessWrap.classList.toggle('hidden', !hasMethod || !isCashless);
        if (cashlessType) cashlessType.required = hasMethod && isCashless;
        if (!hasMethod && amountPaid) {
            amountPaid.value = '';
        }
        if (!hasMethod) {
            payTypeRadios.forEach((radio) => { radio.checked = false; });
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
        if (bankName) bankName.required = isCashless && type === 'bank_transfer';
        const isOtherBank = isCashless && type === 'bank_transfer' && bankName?.value === 'Other Bank';
        if (otherBankWrap) otherBankWrap.classList.toggle('hidden', !isOtherBank);
        if (otherBankName) otherBankName.required = isOtherBank;
        if (referenceNumber) referenceNumber.required = isCashless && ['bank_transfer', 'gcash', 'maya', 'other'].includes(type);
        if (isCashless && cashReferenceNumber && referenceNumber) cashReferenceNumber.value = referenceNumber.value;
        if (bankReference && referenceNumber) bankReference.value = referenceNumber.value;

        const activeAccount = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-account-name-field]`);
        const activeMobile = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-mobile-number-field]`);
        const activeChannel = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-payment-channel-field]`);
        if (accountName && activeAccount) accountName.value = activeAccount.value;
        if (mobileNumber) mobileNumber.value = activeMobile ? activeMobile.value : '';
        if (paymentChannel) paymentChannel.value = activeChannel ? activeChannel.value : '';
        if (walletProvider) walletProvider.value = type === 'gcash' ? 'GCash' : (type === 'maya' ? 'Maya' : '');
    }

    const clearPaymentMethodValidity = () => {
        [
            cashlessType,
            bankName,
            otherBankName,
            referenceNumber,
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

    const validatePaymentMethodDetails = () => {
        syncPayMethod();
        clearPaymentMethodValidity();

        const selected = payMethodRadios.find(r => r.checked)?.value || 'cash';
        if (selected !== 'cashless') return true;

        const type = cashlessType?.value || '';
        const ref = (referenceNumber?.value || '').trim();
        const approval = (document.getElementById('approval_code')?.value || '').trim();
        const activeChannel = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-payment-channel-field]`);
        const activeAccount = document.querySelector(`[data-intake-cashless-panel="${type}"] [data-intake-account-name-field]`);

        if (!type) return showFieldError(cashlessType, 'Please select a cashless type.');
        if (type === 'bank_transfer') {
            if (!(bankName?.value || '').trim()) return showFieldError(bankName, 'Please select the bank name.');
            if (bankName.value === 'Other Bank' && !(otherBankName?.value || '').trim()) {
                return showFieldError(otherBankName, 'Please enter the other bank name.');
            }
            if (!validateAccountNameValue(accountName)) return false;
            if (!validateReferenceValue(referenceNumber, true)) return false;
        }
        if ((type === 'gcash' || type === 'maya') && !ref) {
            return showFieldError(referenceNumber, 'Please enter the wallet reference number.');
        }
        if ((type === 'gcash' || type === 'maya')) {
            if (!validateAccountNameValue(activeAccount)) return false;
            if (!validateReferenceValue(referenceNumber, true)) return false;
        }
        if (type === 'card' && !approval && !ref) {
            return showFieldError(document.getElementById('approval_code'), 'Please enter the approval code or reference number.');
        }
        if (type === 'card' && ref && !validateReferenceValue(referenceNumber, false)) return false;
        if (type === 'other') {
            if (!(activeChannel?.value || '').trim()) return showFieldError(activeChannel, 'Please enter the payment channel.');
            if (!validateReferenceValue(referenceNumber, true)) return false;
        }

        return true;
    };

    const syncPaymentAndRender = () => {
        syncPayMethod();
        render();
    };

    payMethodRadios.forEach(r => r.addEventListener('change', syncPaymentAndRender));
    cashlessType?.addEventListener('change', syncPaymentAndRender);
    bankName?.addEventListener('change', syncPaymentAndRender);
    otherBankName?.addEventListener('input', syncPaymentAndRender);
    cashReferenceNumber?.addEventListener('input', render);
    referenceNumber?.addEventListener('input', () => {
        syncPayMethod();
        clearFieldMessage(referenceNumber);
        render();
    });
    referenceNumber?.addEventListener('blur', () => {
        validateReferenceValue(referenceNumber, false);
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
        render();
    });
    document.getElementById('card_type')?.addEventListener('change', render);
    document.getElementById('terminal_provider')?.addEventListener('input', render);
    document.querySelectorAll('[name="payment_notes"]').forEach(field => {
        field.addEventListener('input', render);
    });
    syncPayMethod();

    pkgRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            renderPkg(true);
            syncControls();
            render();
        });
    });

    pkgCards.forEach((card) => {
        card.addEventListener('click', (event) => {
            if (event.target.closest('.package-details-btn')) return;
            const radio = cardRadio(card);
            if (radio && !radio.checked) radio.checked = true;
            renderPkg(true);
            syncControls();
            render();
        });
        card.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            if (event.target.closest('.package-details-btn')) return;
            event.preventDefault();
            const radio = cardRadio(card);
            if (radio && !radio.checked) radio.checked = true;
            renderPkg(true);
            syncControls();
            render();
        });
    });

    if (pkgList) {
        pkgList.addEventListener('click', (event) => {
            const card = event.target.closest('.package-card');
            if (!card) return;
            if (event.target.closest('.package-details-btn')) return;
            const radio = cardRadio(card);
            if (radio && !radio.checked) radio.checked = true;
            renderPkg(true);
            syncControls();
            render();
        });
    }

    document.querySelectorAll('.package-details-btn').forEach((button) => {
        ['pointerdown', 'mousedown', 'touchstart'].forEach((eventName) => {
            button.addEventListener(eventName, (event) => {
                event.stopPropagation();
            }, { passive: true });
        });
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const radio = pkgRadios.find((item) => String(item.value) === String(button.dataset.packageValue));
            openPackageDetails(radio);
        });
    });

    packageDetailsModal?.querySelectorAll('[data-package-details-close]').forEach((button) => {
        button.addEventListener('click', closePackageDetails);
    });

    packageDetailTabs.forEach((tab) => {
        tab.addEventListener('click', () => activatePackageDetailTab(tab.dataset.packageDetailTab));
    });

    packageDetailsModal?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closePackageDetails();
    });

    optionalAddOnsList?.addEventListener('change', (event) => {
        if (!event.target.matches('.optional-add-on-checkbox')) return;
        const row = event.target.closest('.optional-add-on-row');
        row?.querySelector('[data-add-on-quantity-note]')?.classList.toggle('hidden', !event.target.checked);
        syncDraftAddOnsTotal();
    });

    chooseAddOnsBtn?.addEventListener('click', () => {
        if (chooseAddOnsBtn.disabled) return;
        restoreAppliedAddOns();
        optionalAddOnsList?.querySelectorAll('[data-add-on-quantity-note]').forEach((note) => {
            const checkbox = note.closest('.optional-add-on-row')?.querySelector('.optional-add-on-checkbox');
            note.classList.toggle('hidden', !checkbox?.checked);
        });
        if (optionalAddOnsSearch) optionalAddOnsSearch.value = '';
        optionalAddOnsList?.querySelectorAll('.optional-add-on-row').forEach((row) => row.classList.remove('hidden'));
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
        chooseAddOnsBtn?.focus();
    });

    optionalAddOnsSearch?.addEventListener('input', () => {
        const term = optionalAddOnsSearch.value.trim().toLowerCase();
        optionalAddOnsList?.querySelectorAll('.optional-add-on-row').forEach((row) => {
            row.classList.toggle('hidden', term !== '' && !String(row.dataset.addOnSearch || '').includes(term));
        });
    });

    optionalAddOnsPanel?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            restoreAppliedAddOns();
            setAddOnsPanelOpen(false);
            chooseAddOnsBtn?.focus();
        }
    });

    openPackageAdjustmentsBtn?.addEventListener('click', () => {
        setPackageAdjustmentsOpen(true);
    });

    closePackageAdjustmentsBtn?.addEventListener('click', () => {
        setPackageAdjustmentsOpen(false);
    });

    donePackageAdjustmentsBtn?.addEventListener('click', () => {
        setPackageAdjustmentsOpen(false);
    });

    packageAdjustmentsPanel?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setPackageAdjustmentsOpen(false);
        }
    });

    serviceModalBackdrop?.addEventListener('click', () => {
        if (!optionalAddOnsPanel?.classList.contains('hidden')) {
            restoreAppliedAddOns();
            setAddOnsPanelOpen(false);
        }
        if (!packageAdjustmentsPanel?.classList.contains('hidden')) {
            setPackageAdjustmentsOpen(false);
        }
    });

    addAdditionalItem?.addEventListener('click', () => {
        addAdditionalServiceRow();
        render();
    });

    [replacementCasket, retrievalExcessInput, hearseExcessInput, applyRetrievalExcess, applyHearseExcess].forEach((field) => {
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
        }
    });

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
                go(index);
                return;
            }
        }

        try {
            localStorage.removeItem(intakeAutosaveKey);
        } catch (error) {
            // Autosave is best-effort only.
        }
    });

    const getAutosaveFields = () => [...f.querySelectorAll('input, select, textarea')]
        .filter((field) => {
            if (!field.name || field.type === 'file') return false;
            if (field.name === '_token') return false;
            return true;
        });

    const saveLocalDraft = () => {
        try {
            const fields = {};
            getAutosaveFields().forEach((field) => {
                if (field.type === 'radio') {
                    if (field.checked) fields[field.name] = field.value;
                    return;
                }

                if (field.type === 'checkbox') {
                    if (!fields[field.name]) fields[field.name] = [];
                    if (field.checked) fields[field.name].push(field.value);
                    return;
                }

                fields[field.name] = field.value;
            });

            localStorage.setItem(intakeAutosaveKey, JSON.stringify({
                version: 1,
                savedAt: new Date().toISOString(),
                step,
                selectedAddOns: [...oldSelectedAddOns],
                fields,
            }));
        } catch (error) {
            // Browser storage can be disabled/full; final form submit still works.
        }
    };

    const restoreDraftPayload = (draft) => {
        if (!draft?.fields || typeof draft.fields !== 'object') return false;

        const grouped = new Map();
        getAutosaveFields().forEach((field) => {
            if (!grouped.has(field.name)) grouped.set(field.name, []);
            grouped.get(field.name).push(field);
        });

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

                field.value = value ?? '';
                restoredElements.push(field);
            });
            restoredElements.forEach((field) => field.dispatchEvent(new Event('change', { bubbles: true })));
        });

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

    const restoreLocalDraft = () => {
        let draft = null;

        try {
            draft = JSON.parse(localStorage.getItem(intakeAutosaveKey) || 'null');
        } catch (error) {
            draft = null;
        }

        return restoreDraftPayload(draft);
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
    const restoredServerDraft = restoreDraftPayload(serverDraftPayload);
    const restoredLocalDraft = restoredServerDraft ? false : restoreLocalDraft();
    syncBranch(false);
    syncRequestDate();
    syncControls();
    syncAge();
    syncDateConstraints();
    if (restoredAutosaveAdditionalRows) {
        // Autosaved manual charge rows were restored already.
    } else if (Array.isArray(oldAdditionalItems) && oldAdditionalItems.length) {
        oldAdditionalItems.forEach((item) => addAdditionalServiceRow(item || {}));
    } else if (num(addAmt?.value) > 0 || (additionalServices?.value || '').trim()) {
        addAdditionalServiceRow({ description: additionalServices?.value || '', amount: addAmt?.value || '' });
    } else {
        addAdditionalServiceRow();
    }
    syncPreferredPackage();
    renderPkg(false);
    render();
    go(initialStep, { scroll: false, smooth: false });
    showBranchToast(restoredServerDraft
        ? 'Server draft restored. Continue editing or save the official record.'
        : (restoredLocalDraft ? 'Unsaved local draft restored. Submit to save it officially.' : branchPromptMessage()));

    // ── Cancel button & dirty-state tracking ─────────────────────────────
    const cancelBtn      = document.getElementById('intakeCancelBtn');
    const cancelModal    = document.getElementById('intakeCancelModal');
    const cancelKeep     = document.getElementById('intakeCancelModalKeep');
    const cancelDiscard  = document.getElementById('intakeCancelModalDiscard');
    const cancelSaveDraft = document.getElementById('intakeCancelModalSaveDraft');
    const cancelBackdrop = document.getElementById('intakeCancelModalBackdrop');
    const saveDraftBtn   = document.getElementById('saveIntakeDraft');
    const cancelUrl      = @json($cancelUrl ?? '/');

    let formIsDirty = false;
    let autosaveTimer = null;
    const queueLocalDraftSave = () => {
        window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(saveLocalDraft, 350);
    };

    // Mark dirty on any user interaction with the form
    f.addEventListener('input',  () => { formIsDirty = true; queueLocalDraftSave(); }, { passive: true });
    f.addEventListener('change', () => { formIsDirty = true; queueLocalDraftSave(); }, { passive: true });

    const openCancelModal  = () => {
        cancelModal?.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        cancelKeep?.focus();
    };
    const closeCancelModal = () => {
        cancelModal?.classList.add('hidden');
        document.body.style.overflow = '';
    };
    const doCancel = () => { window.location.href = cancelUrl; };
    const saveDraftFromCancel = () => {
        closeCancelModal();
        if (currentStepField) currentStepField.value = String(step);

        if (saveDraftBtn && typeof f.requestSubmit === 'function') {
            f.requestSubmit(saveDraftBtn);
            return;
        }

        saveDraftBtn?.click();
    };

    cancelBtn?.addEventListener('click', () => {
        formIsDirty ? openCancelModal() : doCancel();
    });

    cancelKeep?.addEventListener('click',     closeCancelModal);
    cancelBackdrop?.addEventListener('click', closeCancelModal);
    cancelDiscard?.addEventListener('click',  doCancel);
    cancelSaveDraft?.addEventListener('click', saveDraftFromCancel);

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && cancelModal && !cancelModal.classList.contains('hidden')) {
            closeCancelModal();
        }
    });
    // ─────────────────────────────────────────────────────────────────────
})();
</script>
