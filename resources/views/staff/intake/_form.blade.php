@php
    $isOtherEntryMode = !empty($entryMode) && $entryMode === 'other';
    $otherBranchCutoffHour = max(min((int) config('funeral.other_branch_report_cutoff_hour', 18), 23), 0);
    $todayStart = now()->copy()->startOfDay();
    $otherBranchCutoffAt = $todayStart->copy()->addHours($otherBranchCutoffHour);
    $otherBranchWindowClosed = $isOtherEntryMode && now()->gt($otherBranchCutoffAt);
    $otherBranchReportedDefault = old('reported_at', $isOtherEntryMode
        ? now()->copy()->min($otherBranchCutoffAt)->format('Y-m-d\TH:i')
        : null);

    $initialSelectedBranchId = old('branch_id', $defaultBranchId ?? auth()->user()->branch_id);
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
        width: 220px; flex-shrink: 0;
        display: flex; flex-direction: column; gap: 0;
        padding: 18px 0 12px;
        background: #ffffff;
        border-right: 1px solid #e4e8ef;
        overflow-y: auto; overflow-x: hidden;
    }
    .wizard-steps-group-label {
        font-size: 9px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.13em; color: #9baec8;
        padding: 0 18px 10px; border-bottom: 1px solid #f0f2f6;
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
    /* track column: circle + vertical line */
    .wizard-tab .wz-track {
        display: flex; flex-direction: column; align-items: center;
        padding: 12px 0 0 18px; flex-shrink: 0; width: 52px;
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
        flex: 1; padding: 10px 14px 10px 4px;
        border-radius: 8px; margin: 4px 8px 4px 0;
        display: flex; flex-direction: column; gap: 3px;
        transition: background .15s ease;
        min-height: 48px;
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
    .form-input.field-error, .form-textarea.field-error {
        border-color: #e11d48 !important;
        background: #fff7f8 !important;
        box-shadow: 0 0 0 4px rgba(225,29,72,.08) !important;
    }
    .form-input::placeholder, .form-textarea::placeholder { color: #b0beca; font-weight: 400; }
    .intake-root input::placeholder, .intake-root textarea::placeholder { color: #b0beca !important; font-weight: 400 !important; opacity: 1; }
    .form-input[readonly], .form-input.cursor-not-allowed { background: #f4f6fb; color: #7a8fa8; cursor: not-allowed; }


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
        color: #64748b;
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
    .form-select-wrap::after {
        content: "";
        position: absolute;
        right: 14px;
        top: 50%;
        width: 8px;
        height: 8px;
        border-right: 2px solid #94a3b8;
        border-bottom: 2px solid #94a3b8;
        transform: translateY(-65%) rotate(45deg);
        pointer-events: none;
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
        background: linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
    }

    /* ── Package cards ── */
    .package-card-item { position: relative; }
    .package-card-item .package-radio {
        position: absolute; inset: 0; width: 100%; height: 100%;
        opacity: .001; cursor: pointer; z-index: 5;
    }
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
    .pkg-feature-item { display: flex; align-items: flex-start; gap: 8px; font-size: 12px; font-weight: 500; color: #334155; line-height: 1.45; }
    .pkg-feature-check { color: #059669; font-weight: 900; flex-shrink: 0; margin-top: 1px; }
    .pkg-feature-plus { color: #d97706; font-weight: 900; flex-shrink: 0; font-size: 13px; line-height: 1; }

    .pkg-card-footer { padding: 0 22px 20px; }
    .pkg-select-btn {
        width: 100%; padding: 10px 16px; border-radius: 10px;
        font-size: 13px; font-weight: 700; text-align: center;
        border: 1.5px solid #d0d9e8; background: #fff; color: #334155;
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
    .bpay-tab-group .payment-method-card:hover { transform: none !important; box-shadow: none !important; background: #f8fafc; border: 0 !important; }
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
    .intake-toast-branch { top: calc(var(--topbar-h,72px) + 10px) !important; }
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
        background: linear-gradient(135deg,#047857 0%,#059669 100%) !important;
        font-size: 13px !important; font-weight: 700 !important; color: #ffffff !important;
        border: none !important;
        box-shadow: 0 4px 14px rgba(5,150,105,.3) !important;
        transition: all .18s ease !important; min-width: 160px !important;
    }
    #saveIntakeRecord:hover:not(:disabled) { background: linear-gradient(135deg,#036348 0%,#047857 100%) !important; box-shadow: 0 6px 20px rgba(5,150,105,.42) !important; transform: translateY(-1px); }
    #saveIntakeRecord:disabled { opacity: .5; transform: none !important; }

    /* ── Flatpickr ── */
    .flatpickr-calendar { margin-top: 10px !important; border-radius: 18px !important; border: 1px solid #e4e8ef !important; box-shadow: 0 16px 40px rgba(15,23,42,.15) !important; padding: 12px 12px 10px !important; width: 340px !important; max-height: calc(100vh - 24px) !important; background: #ffffff !important; overflow: auto !important; z-index: 2400 !important; }
    .flatpickr-calendar.arrowTop::before,.flatpickr-calendar.arrowTop::after,.flatpickr-calendar.arrowBottom::before,.flatpickr-calendar.arrowBottom::after { display: none !important; }
    .flatpickr-months { position: relative !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 6px 34px 12px !important; margin-bottom: 2px !important; border-bottom: 1px solid #f0f4f8 !important; }
    .flatpickr-month { height: auto !important; display: flex !important; justify-content: center !important; align-items: center !important; }
    .flatpickr-current-month { position: static !important; left: auto !important; width: auto !important; height: auto !important; padding: 0 !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; line-height: 1.2 !important; color: #0d1f38 !important; font-weight: 800 !important; white-space: nowrap !important; }
    .flatpickr-current-month .flatpickr-monthDropdown-months { appearance: none !important; -webkit-appearance: none !important; border: 1px solid #e4e8ef !important; background: #f4f7fb !important; border-radius: 10px !important; padding: 4px 28px 4px 10px !important; margin: 0 !important; font-size: 15px !important; font-weight: 800 !important; color: #0d1f38 !important; line-height: 1.2 !important; cursor: pointer !important; box-shadow: none !important; min-width: 110px !important; }
    .flatpickr-current-month input.cur-year { border: 1px solid #e4e8ef !important; background: #f4f7fb !important; border-radius: 10px !important; box-shadow: none !important; padding: 4px 8px !important; margin: 0 !important; font-size: 15px !important; font-weight: 800 !important; color: #0d1f38 !important; width: 74px !important; min-width: 74px !important; text-align: center !important; }
    .flatpickr-current-month .flatpickr-monthDropdown-months:focus,.flatpickr-current-month input.cur-year:focus { outline: none !important; border-color: #1b3358 !important; box-shadow: 0 0 0 3px rgba(27,51,88,.1) !important; }
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
            width: 100% !important;
            flex-direction: row !important;
            border-right: none; border-bottom: 1px solid #e4e8ef;
            overflow-x: auto; overflow-y: hidden;
            padding: 0; height: auto;
        }
        .wizard-steps-group-label { display: none; }
        .wizard-tab { flex-direction: column; width: auto; flex: 0 0 auto; min-width: 70px; align-items: center; }
        .wizard-tab .wz-track { flex-direction: row; padding: 10px 0 0 0; width: auto; }
        .wizard-tab .wz-line { display: none; }
        .wizard-tab .wz-body { padding: 4px 6px 8px; margin: 0 4px; min-height: auto; align-items: center; }
        .wizard-tab .wz-sub { display: none; }
        .wizard-tab .wizard-step-label { font-size: 0.67rem; text-align: center; }
        #intakeFormContent { padding: 14px 16px !important; }
        .footer-action-bar { padding: 12px 16px !important; }
        .wizard-panel { padding: 18px 16px; border-radius: 14px; }
    }
    @media (max-width: 640px) {
        .footer-action-bar { flex-direction: column; align-items: stretch; gap: 8px; }
        #wizardPrev, #wizardNext, #saveIntakeRecord { text-align: center; width: 100%; }
    }
    </style>

    <div id="branch_toast" class="hidden fixed left-1/2 -translate-x-1/2 px-5 py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold transition-all duration-300 opacity-0 translate-y-[-8px] toast-pop intake-toast-branch">
        You're now recording for this branch.
    </div>

    <div id="package_toast" class="hidden fixed left-1/2 -translate-x-1/2 px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-bold transition-all duration-200 opacity-0 translate-y-[-8px] flex items-center gap-2 toast-pop intake-toast-package">
        <i class="bi bi-check2-circle text-lg"></i>
        <span class="package-toast-text">You selected a package.</span>
    </div>
    {{-- Right: draft label + exit --}}
    
</div>

{{-- Progress rail (updated by JS) --}}
<div class="intake-progress-rail"><div class="intake-progress-fill" id="intakeProgressFill"></div></div>

    @if($isOtherEntryMode)
        <div class="mb-6 rounded-2xl border px-5 py-4 text-sm {{ $otherBranchWindowClosed ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-amber-200 bg-amber-50 text-amber-900' }} shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
            <div class="font-bold flex items-center gap-2">
                <i class="bi {{ $otherBranchWindowClosed ? 'bi-x-circle-fill text-rose-500' : 'bi-exclamation-triangle-fill text-amber-500' }}"></i>
                {{ $otherBranchWindowClosed ? 'Intake Window Closed' : 'External Branch Report' }}
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

            <input type="hidden" name="service_requested_at" id="service_requested_at" value="{{ old('service_requested_at', now()->toDateString()) }}">
            <input type="hidden" name="branch_id" id="branch_id" value="{{ $initialSelectedBranchId }}">
            <input type="hidden" id="branch_code_main_default" value="{{ optional($branches->first())->branch_code ?? 'BR001' }}">

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
                                    @error('client_first_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Middle Name</label>
                                    <input type="text" name="client_middle_name" value="{{ old('client_middle_name') }}" data-label="client middle name" class="form-input" placeholder="Middle name">
                                    @error('client_middle_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Last Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="client_last_name" value="{{ old('client_last_name') }}" data-label="client last name" class="form-input" placeholder="Last name" required>
                                    @error('client_last_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                                    @error('client_suffix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                                    @error('deceased_first_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Middle Name</label>
                                    <input type="text" name="deceased_middle_name" value="{{ old('deceased_middle_name') }}" data-label="deceased middle name" class="form-input" placeholder="Middle name">
                                    @error('deceased_middle_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Last Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="deceased_last_name" value="{{ old('deceased_last_name') }}" data-label="deceased last name" class="form-input" placeholder="Last name" required>
                                    @error('deceased_last_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                                    @error('deceased_suffix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p id="born_error" class="mt-1 text-sm text-red-600 hidden"></p>
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
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p id="died_error" class="mt-1 text-sm text-red-600 hidden"></p>
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
                    @php
                        $maxPkgPrice = $packages->max('price');
                    @endphp

                    <div class="pkg-grid-header">
                        <div>
                            <p class="pkg-section-title">Service Packages</p>
                            <p class="pkg-section-sub">Select the appropriate service package for this case.</p>
                        </div>
                        <div id="package_error" class="hidden text-xs font-bold text-rose-500 bg-rose-50 border border-rose-200 px-3 py-1.5 rounded-lg flex items-center gap-1">
                            <i class="bi bi-exclamation-circle-fill"></i> Selection required
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" id="packageCardList">
                        @foreach($packages as $pkg)
                            @php
                                $promoNow = $pkg->promo_is_active
                                    && (!$pkg->promo_starts_at || $pkg->promo_starts_at->lte(now()))
                                    && (!$pkg->promo_ends_at || $pkg->promo_ends_at->gte(now()));

                                $isFeatured = $pkg->price == $maxPkgPrice;

                                $inclusionItems = $pkg->inclusionNames();
                                $freebieItems = $pkg->freebieNames();
                            @endphp

                            <div class="package-card-item {{ $isFeatured ? 'pkg-featured-item' : '' }}">
                                @if($isFeatured)
                                    <div class="pkg-badge-recommended">Recommended</div>
                                @endif
                                <label class="package-card pkg-premium-card {{ $isFeatured ? 'pkg-card-featured' : '' }} w-full cursor-pointer">
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
                                        data-inclusions="{{ e(implode("\n", $inclusionItems)) }}"
                                        data-freebies="{{ e(implode("\n", $freebieItems)) }}"
                                        {{ (string) old('package_id') === (string) $pkg->id ? 'checked' : '' }}
                                        required
                                    >

                                    <div class="pkg-card-body">
                                        <div class="pkg-tier-label">{{ $pkg->coffin_type ?? 'Standard' }}</div>
                                        <div class="pkg-name">{{ $pkg->name }}</div>
                                        <div class="pkg-price"><sub>&#8369;</sub>{{ number_format($pkg->price, 0) }}</div>
                                        @if($promoNow)
                                            <div class="pkg-promo-badge">{{ $pkg->promo_label }}</div>
                                        @else
                                            <p class="pkg-price-note">Fixed package rate</p>
                                        @endif

                                        @if(count($inclusionItems) > 0)
                                            <ul class="pkg-features-list">
                                                @foreach(array_slice($inclusionItems, 0, 6) as $item)
                                                    <li class="pkg-feature-item">
                                                        <span class="pkg-feature-check">&#10003;</span>
                                                        {{ $item }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>

                                    <div class="pkg-card-footer">
                                        <div class="pkg-select-btn">
                                            <span class="pkg-btn-unselected">Select Package</span>
                                            <span class="pkg-btn-selected"><i class="bi bi-check-circle-fill"></i> Selected</span>
                                        </div>
                                        <div class="check-dot hidden"></div>{{-- JS compat --}}
                                    </div>
                                </label>
                            </div>
                        @endforeach

                        @if(empty($entryMode) || $entryMode === 'main')
                            <div class="package-card-item">
                                <label class="package-card pkg-premium-card pkg-card-custom w-full cursor-pointer">
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
                                    >

                                    <div class="pkg-card-body">
                                        <div class="pkg-tier-label" style="color:#b45309;">Client Preference</div>
                                        <div class="pkg-name" style="color:#78350f;">Custom</div>
                                        <div class="pkg-price" style="color:#92400e;">
                                            <sub>&#8369;</sub><span id="custom_package_price_display">0</span>
                                        </div>
                                        <p class="pkg-price-note">Tailored to client's needs</p>

                                        <ul class="pkg-features-list">
                                            <li class="pkg-feature-item">
                                                <span class="pkg-feature-plus">+</span>
                                                Choose Casket Tier
                                            </li>
                                            <li class="pkg-feature-item">
                                                <span class="pkg-feature-plus">+</span>
                                                Flexible Wake Duration
                                            </li>
                                            <li class="pkg-feature-item">
                                                <span class="pkg-feature-plus">+</span>
                                                Add-on Services (Music, Video)
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="pkg-card-footer">
                                        <div class="pkg-select-btn">
                                            <span class="pkg-btn-unselected">Build Package</span>
                                            <span class="pkg-btn-selected"><i class="bi bi-check-circle-fill"></i> Selected</span>
                                        </div>
                                        <div class="check-dot hidden"></div>{{-- JS compat --}}
                                    </div>
                                </label>
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

                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">
                                <i class="bi bi-list-check mr-1"></i> Inclusions
                            </h5>
                            <ul id="selected_package_inclusions" class="space-y-2 text-xs font-medium text-slate-700">
                                <li class="text-slate-400 italic">Select a package to view.</li>
                            </ul>
                        </div>

                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">
                                <i class="bi bi-gift mr-1"></i> Freebies & Notes
                            </h5>
                            <ul id="selected_package_freebies" class="space-y-2 text-xs font-medium text-slate-700">
                                <li class="text-slate-400 italic">Package freebies will appear here.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-8 pt-8 border-t border-slate-200">
                        <div class="section-title-block mb-6">
                            <div class="section-heading-icon">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div class="section-title-text">
                                <h3>Service Details</h3>
                                <p>Set the wake, interment, and case progress details.</p>
                            </div>
                        </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <div>
                            <label class="field-label">Wake Start Date <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="funeral_service_at" id="funeral_service_at" value="{{ old('funeral_service_at') }}" data-label="wake start date" class="form-input pr-10 cursor-pointer" placeholder="e.g., January 2, 2026" autocomplete="off" required>
                                <span id="wake_picker_trigger" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                    <i class="bi bi-calendar-event text-lg"></i>
                                </span>
                            </div>
                            @error('funeral_service_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p id="funeral_service_at_error" class="mt-1 text-sm text-red-600 hidden"></p>
                            <p class="text-xs text-slate-500 mt-1">Click to pick the first day of the wake.</p>
                        </div>

                        <div>
                            <label class="field-label">Service Type</label>
                            <input type="hidden" name="service_type" id="service_type" value="Burial">
                            <div class="form-input bg-slate-50 border-slate-200 text-slate-700 font-semibold flex items-center gap-2 pointer-events-none">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                Burial (fixed)
                            </div>
                        </div>

                        <div class="md:col-span-1">
                            <label class="field-label">Wake Location <span class="text-rose-500">*</span></label>
                            <input type="text" name="wake_location" id="wake_location" value="{{ old('wake_location') }}" data-label="wake location" class="form-input" placeholder="Chapel or House Address" required>
                        </div>

                        <div>
                            <label class="field-label">Wake Days</label>
                            <input type="number" name="wake_days" id="wake_days" value="{{ old('wake_days') }}" data-label="wake days" class="form-input bg-slate-50" placeholder="Auto-calculated" readonly>
                            <p id="wake_days_helper" class="text-xs text-slate-500 mt-1">Auto-calculated once dates are selected.</p>
                        </div>

                        <div>
                            <label class="field-label">Interment / Burial Schedule <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="interment_at" id="interment_at" value="{{ old('interment_at') }}" data-label="interment or burial date" class="form-input pr-10 cursor-pointer" placeholder="e.g., January 5, 2026 9:00 AM" autocomplete="off" required>
                                <span id="inter_picker_trigger" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                    <i class="bi bi-calendar-event text-lg"></i>
                                </span>
                            </div>
                            @error('interment_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <div id="interment_at_error" class="hidden text-xs font-bold text-rose-500 mt-1">Interment date cannot be earlier than the wake start date.</div>
                            <p class="text-xs text-slate-500 mt-1">Select burial date and time. Calendar opens on click.</p>
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
                                    <option value="DRAFT" {{ old('case_status', 'ACTIVE') === 'DRAFT' ? 'selected' : '' }}>Pending</option>
                                    <option value="ACTIVE" {{ old('case_status', 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>Ongoing</option>
                                    <option value="COMPLETED" {{ old('case_status') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                                </select>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5">
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
                            <div class="p-5 space-y-5">

                                {{-- Additional charges --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="field-label">Additional Charges</label>
                                        <div class="flex items-center rounded-lg border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-slate-900">
                                            <span class="pl-3 pr-2 text-slate-500 font-bold">&#8369;</span>
                                            <input type="number" step="0.01" min="0" name="additional_service_amount" id="additional_service_amount" value="{{ old('additional_service_amount') }}" data-label="additional charges" class="w-full border-0 focus:outline-none focus:ring-0 font-bold p-3" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="field-label">Description of Extras</label>
                                        <textarea name="additional_services" id="additional_services" rows="2" data-label="additional services" class="form-textarea" placeholder="Detail any add-ons here...">{{ old('additional_services') }}</textarea>
                                    </div>
                                </div>

                                {{-- Hidden billing inputs still used by JS --}}
                                <input type="number" step="0.01" name="package_amount" id="package_amount" value="{{ old('package_amount') }}" class="hidden" readonly>
                                <input type="hidden" name="tax_rate" id="tax_rate" value="0">
                                <input type="text" id="auto_discount_type" value="None" class="hidden" readonly>
                                <input type="text" id="auto_discount_amount" value="PHP 0.00" class="hidden" readonly>

                                {{-- Discount info note --}}
                                <div id="discount_help_wrap" class="text-[11px] font-medium text-slate-500 flex gap-2 items-start">
                                    <i class="bi bi-info-circle text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span id="discount_help_text_secondary">Discount is tied to Senior Citizen status.</span>
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
                                    <label id="mark_as_paid_label" class="group flex items-center justify-between gap-4 p-4 rounded-xl border-2 {{ old('mark_as_paid') ? 'border-slate-800 bg-slate-50' : 'border-slate-200 bg-white' }} cursor-pointer transition-all duration-200 hover:border-slate-400">
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

                                <div id="payment_form_fields" class="{{ $isOtherEntryMode ? '' : 'hidden' }} space-y-5">
                                    {{-- Payment Method --}}
                                    <div>
                                        <label class="field-label text-xs">Payment Method</label>
                                        <div class="bpay-tab-group">
                                            <label class="payment-method-card cursor-pointer">
                                                <input type="radio" name="payment_method" value="CASH" class="payment-method-radio sr-only" {{ old('payment_method', 'CASH') === 'CASH' ? 'checked' : '' }}>
                                                <span class="flex items-center gap-2 text-sm font-bold text-slate-700"><i class="bi bi-cash-coin"></i> Cash</span>
                                            </label>
                                            <label class="payment-method-card cursor-pointer">
                                                <input type="radio" name="payment_method" value="BANK_TRANSFER" class="payment-method-radio sr-only" {{ old('payment_method') === 'BANK_TRANSFER' ? 'checked' : '' }}>
                                                <span class="flex items-center gap-2 text-sm font-bold text-slate-700"><i class="bi bi-bank"></i> Bank Transfer</span>
                                            </label>
                                        </div>
                                    </div>

                                    {{-- Bank reference (visible only when Bank Transfer is selected) --}}
                                    <div id="bank_reference_wrap" class="hidden">
                                        <label class="field-label">Transaction / Reference No.</label>
                                        <input type="text" name="bank_reference" id="bank_reference" value="{{ old('bank_reference') }}" data-label="bank reference" class="form-input" placeholder="e.g. TXN-20240101-001">
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
                                            <div id="payment_type_error" class="hidden text-xs font-bold text-rose-500 mt-2">Please select a type.</div>
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
                                            <span>Current Payment</span>
                                            <span class="font-bold text-slate-700 whitespace-nowrap">&#8369;&nbsp;<span id="summary_payment_status_dummy" class="hidden"></span><span id="summary_payment_status" class="font-bold">UNPAID</span></span>
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
                <button type="button" id="wizardPrev" class="w-auto shrink-0 px-6 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all disabled:opacity-30 disabled:hover:bg-white">
                    Back
                </button>

                <div class="flex-1 sm:flex-none sm:ml-auto">
                    <button type="button" id="wizardNext" class="w-full sm:w-auto sm:min-w-[190px] px-8 py-3 rounded-xl bg-slate-900 text-white text-sm font-bold transition-all hover:bg-[#2563eb] hover:-translate-y-0.5">
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
    const funeral = document.getElementById('funeral_service_at');
    const interment = document.getElementById('interment_at');
    const intermentErr = document.getElementById('interment_at_error');
    const bornErr = document.getElementById('born_error');
    const diedErr = document.getElementById('died_error');
    const wakeErr = document.getElementById('funeral_service_at_error');
    let wakePicker = null;
    let interPicker = null;

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

    const addAmt = document.getElementById('additional_service_amount');
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
    const summarySubtotal = document.getElementById('summary_subtotal');
    const summaryDiscountSource = document.getElementById('summary_discount_source');
    const summaryDiscount = document.getElementById('summary_discount');
    const summaryTax = document.getElementById('summary_tax');
    const summaryTotal = document.getElementById('summary_total');
    const summaryStatus = document.getElementById('summary_payment_status');
    const summaryBalance = document.getElementById('summary_balance');

    const paymentStatusPreview = document.getElementById('payment_status_preview');
    const paymentPaidPreview = document.getElementById('payment_paid_preview');
    const paymentBalancePreview = document.getElementById('payment_balance_preview');

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
    const totalSteps = panels.length;
    const initialStep = Math.max(1, Math.min(totalSteps, Number(@json($initialStep ?? 1))));
    const serverInitialBranchId = @json((string) $initialSelectedBranchId);
    const serverOldBranchExists = @json(old('branch_id') !== null);

    let step = 1;
    let branchToastTimer = null;
    let packageToastTimer = null;
    const today = new Date().toLocaleDateString('en-CA');

    const num = (value) => Number.parseFloat(value || 0) || 0;
    const fmt = (value) => num(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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
            errorEl.className = 'mt-1 text-sm text-red-600 hidden';
            field.insertAdjacentElement('afterend', errorEl);
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

    const realignVisibleToasts = () => {
        if (branchToast && !branchToast.classList.contains('hidden')) alignToastToMainCenter(branchToast);
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
        alignToastToMainCenter(element);
        scheduleToastRealign();

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

        return {
            type: 'None',
            source: 'None',
            amount: 0,
            message: 'No automatic discount applies when Senior Citizen is marked No.',
        };
    };

    const discount = () => {
        const meta = autoDiscountMeta();
        return { amount: meta.amount, source: meta.source, type: meta.type, message: meta.message };
    };

    const totals = () => {
        const packagePrice = num(pkgAmount?.value);
        const additional = num(addAmt?.value);
        const subtotal = packagePrice + additional;
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

        return { packagePrice, additional, subtotal, disc, tax, total, paid, balance, status, rate };
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

        if (funeral) {
            funeral.min = died?.value || '';
            if (wakePicker) wakePicker.set('minDate', died?.value || null);
        }

        if (interPicker) {
            interPicker.set('minDate', funeral?.value || died?.value || 'today');
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
            card.classList.toggle('border-slate-800', active);
            card.classList.toggle('bg-slate-50', active);
            card.classList.toggle('ring-2', active);
            card.classList.toggle('selected', active);
        });

        if (pkgAmount) pkgAmount.value = selected?.dataset.price || '';
        if (packageError) packageError.classList.add('hidden');

        if (inclusions) {
            inclusions.innerHTML = list(selected?.dataset.inclusions)
                .map((item) => `<li class="flex items-start gap-2"><i class="bi bi-check2 text-emerald-500 mt-0.5"></i> <span>${escapeHtml(item)}</span></li>`)
                .join('') || '<li class="text-slate-400 italic">Select a package to view inclusions.</li>';
        }

        if (freebies) {
            freebies.innerHTML = list(selected?.dataset.freebies)
                .map((item) => `<li class="flex items-start gap-2"><i class="bi bi-gift text-amber-500 mt-0.5"></i> <span>${escapeHtml(item)}</span></li>`)
                .join('') || '<li class="text-slate-400 italic">Package freebies and notes will appear here.</li>';
        }

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

        if (payWrap && mark?.type === 'checkbox') payWrap.classList.toggle('hidden', !payNow());

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
                detailRow('Service Type', 'Burial (fixed)'),
                detailRow('Wake Location', textOrDash(f.elements.wake_location?.value)),
                detailRow('Wake Start Date', formatDateOnly(f.elements.funeral_service_at?.value)),
                detailRow('Interment Date', formatDateTime(f.elements.interment_at?.value)),
                detailRow('Place of Interment', textOrDash(f.elements.place_of_cemetery?.value)),
                detailRow('Case Status', textOrDash(f.elements.case_status?.value)),
                detailRow('Wake Days', textOrDash(f.elements.wake_days?.value)),
            ].join('');
        }

        if (reviewPackage) {
            reviewPackage.innerHTML = [
                detailRow('Selected Package', selected?.dataset?.name || '-'),
                detailRow('Package Price', `PHP ${fmt(t.packagePrice)}`),
                detailRow('Inclusions', list(selected?.dataset.inclusions).join(', ') || '-'),
                detailRow('Freebies / Notes', list(selected?.dataset.freebies).join(', ') || '-'),
            ].join('');
        }

        if (reviewBilling) {
            reviewBilling.innerHTML = [
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
                detailRow('Amount Paid', `PHP ${fmt(t.paid)}`),
                detailRow('Remaining Balance', `PHP ${fmt(t.balance)}`),
                detailRow('Payment Date', formatDateTime(paidAt?.value)),
                detailRow('Payment Status', t.status),
            ].join('');
        }
    };

    const computeWakeDays = () => {
        if (!wakeDays) return;

        const wakeDate = getDateValue(funeral, wakePicker);
        const interDate = getDateValue(interment, interPicker);

        if (!wakeDate || !interDate || interDate < wakeDate) {
            wakeDays.value = '';
            const helper = document.getElementById('wake_days_helper');
            if (helper) helper.textContent = 'Auto-calculated once dates are selected.';
            return;
        }

        const wakeOnly = new Date(wakeDate.getFullYear(), wakeDate.getMonth(), wakeDate.getDate());
        const interOnly = new Date(interDate.getFullYear(), interDate.getMonth(), interDate.getDate());
        const diffDays = Math.floor((interOnly - wakeOnly) / 86400000) + 1;

        wakeDays.value = diffDays > 0 ? String(diffDays) : '';

        const helper = document.getElementById('wake_days_helper');
        if (helper) helper.textContent = diffDays === 1 ? '1 day' : `${diffDays} days`;
    };

    const setFieldError = (el, errEl, message, altEl = null) => {
        if (!el || !errEl) return;

        const targets = [el];
        if (altEl) targets.push(altEl);

        if (message) {
            targets.forEach(t => t && t.classList.add('field-error'));
            targets.forEach(t => t && t.setAttribute('aria-invalid', 'true'));
            errEl.textContent = message;
            errEl.classList.remove('hidden');
        } else {
            targets.forEach(t => t && t.classList.remove('field-error'));
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
        const wakeRaw = getRawDateString(funeral, wakePicker);
        const interRaw = getRawDateString(interment, interPicker);
        const wakeDate = getDateValue(funeral, wakePicker);
        const interDate = getDateValue(interment, interPicker);
        const deathDate = getDateValue(died, diedPicker);

        funeral?.setCustomValidity('');
        interment?.setCustomValidity('');

        if (mode === 'full') {
            setFieldError(funeral, wakeErr, '', wakePicker?.altInput);
            setFieldError(interment, intermentErr, '', interPicker?.altInput);
        }

        if (funeral) {
            if (!wakeRaw) {
                if (mode === 'full') funeral.setCustomValidity('Wake start date is required.');
            } else if (!wakeDate) {
                funeral.setCustomValidity('Please enter a valid wake start date.');
            }
        }

        if (interment) {
            if (!interRaw) {
                if (mode === 'full') interment.setCustomValidity('Interment date is required.');
            } else if (!interDate) {
                interment.setCustomValidity('Please enter a valid interment date and time.');
            }
        }

        if (deathDate && wakeDate) {
            const deathOnly = new Date(deathDate.getFullYear(), deathDate.getMonth(), deathDate.getDate());
            const wakeOnly = new Date(wakeDate.getFullYear(), wakeDate.getMonth(), wakeDate.getDate());
            if (wakeOnly < deathOnly) {
                funeral.setCustomValidity('Wake start date must be on or after the date of death.');
            }
        }

        if (wakeDate && interDate && interDate < wakeDate) {
            interment.setCustomValidity('Interment date cannot be earlier than the wake start date.');
        }

        if (wakeRaw || mode === 'full') {
            setFieldError(funeral, wakeErr, funeral?.validationMessage || '', wakePicker?.altInput);
        }
        if (interRaw || mode === 'full') {
            setFieldError(interment, intermentErr, interment?.validationMessage || '', interPicker?.altInput);
        }
    };

    const render = () => {
        computeWakeDays();
        validateDobDod('light');
        validateWakeInterment('light');
        syncAge();

        const t = totals();

        if (summaryPackage) summaryPackage.textContent = fmt(t.packagePrice);
        if (summaryAdd) summaryAdd.textContent = fmt(t.additional);
        if (summarySubtotal) summarySubtotal.textContent = fmt(t.subtotal);
        if (summaryDiscountSource) summaryDiscountSource.textContent = t.disc.source;
        if (summaryDiscount) summaryDiscount.textContent = fmt(t.disc.amount);
        if (summaryTax) summaryTax.textContent = fmt(t.tax);
        if (summaryTotal) summaryTotal.textContent = fmt(t.total);
        if (summaryStatus) summaryStatus.textContent = t.status;
        if (summaryBalance) summaryBalance.textContent = fmt(t.balance);

        if (taxAmountDisplay) taxAmountDisplay.value = `PHP ${fmt(t.tax)}`;
        if (taxRate) taxRate.value = t.rate.toFixed(2);
        if (autoDiscountType) autoDiscountType.value = t.disc.type;
        if (autoDiscountAmount) autoDiscountAmount.value = `PHP ${fmt(t.disc.amount)}`;
        if (discountHelpSecondary) discountHelpSecondary.textContent = t.disc.message;

        if (paymentStatusPreview) paymentStatusPreview.textContent = t.status;
        if (paymentPaidPreview) paymentPaidPreview.textContent = fmt(t.paid);
        if (paymentBalancePreview) paymentBalancePreview.textContent = fmt(t.balance);

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
        const flatpickrDateFields = ['born', 'died', 'funeral_service_at', 'interment_at'];

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
            const wakeRaw = getRawDateString(funeral, wakePicker);
            const interRaw = getRawDateString(interment, interPicker);
            const wakeDate = getDateValue(funeral, wakePicker);
            const interDate = getDateValue(interment, interPicker);
            const deathDate = getDateValue(died, diedPicker);

            setFieldError(funeral, wakeErr, '', wakePicker?.altInput);
            setFieldError(interment, intermentErr, '', interPicker?.altInput);
            funeral?.setCustomValidity('');
            interment?.setCustomValidity('');

            if (!wakeRaw) {
                funeral.setCustomValidity('Wake start date is required.');
                setFieldError(funeral, wakeErr, 'Wake start date is required.', wakePicker?.altInput);
                wakePicker?.altInput?.focus();
                return false;
            }

            if (!interRaw) {
                interment.setCustomValidity('Interment date is required.');
                setFieldError(interment, intermentErr, 'Interment date is required.', interPicker?.altInput);
                interPicker?.altInput?.focus();
                return false;
            }

            if (!wakeDate) {
                funeral.setCustomValidity('Please enter a valid wake start date.');
                setFieldError(funeral, wakeErr, 'Please enter a valid wake start date.', wakePicker?.altInput);
                wakePicker?.altInput?.focus();
                return false;
            }

            if (!interDate) {
                interment.setCustomValidity('Please enter a valid interment date and time.');
                setFieldError(interment, intermentErr, 'Please enter a valid interment date and time.', interPicker?.altInput);
                interPicker?.altInput?.focus();
                return false;
            }

            if (deathDate) {
                const deathOnly = new Date(deathDate.getFullYear(), deathDate.getMonth(), deathDate.getDate());
                const wakeOnly = new Date(wakeDate.getFullYear(), wakeDate.getMonth(), wakeDate.getDate());

                if (wakeOnly < deathOnly) {
                    funeral.setCustomValidity('Wake start date must be on or after the date of death.');
                    setFieldError(funeral, wakeErr, 'Wake start date must be on or after the date of death.', wakePicker?.altInput);
                    wakePicker?.altInput?.focus();
                    return false;
                }
            }

            if (interDate < wakeDate) {
                interment.setCustomValidity('Interment date cannot be earlier than the wake start date.');
                setFieldError(interment, intermentErr, 'Interment date cannot be earlier than the wake start date.', interPicker?.altInput);
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

    const go = (targetStep, options = {}) => {
        const { scroll = true, smooth = true } = options;
        const previousStep = step;

        if (shouldLockOtherBranchIntake() && targetStep > 1) {
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Please select a branch first before encoding.';
            showBranchToast('Select Branch 2 or Branch 3 before entering case details.');
            targetStep = 1;
        }

        step = Math.max(1, Math.min(totalSteps, targetStep));

        panels.forEach((panel) => panel.classList.toggle('hidden', Number(panel.dataset.step) !== step));

        tabs.forEach((tab) => {
            const tabStep = Number(tab.dataset.step);
            const active = tabStep === step;
            const completed = tabStep < step;

            tab.classList.toggle('active-step', active);
            tab.classList.toggle('completed-step', completed);
        });

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

            if (targetStep > step) {
                for (let index = step; index < targetStep; index += 1) {
                    if (!validate(index)) return;
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

    funeral?.addEventListener('change', () => {
        computeWakeDays();
        validateWakeInterment('full');
        render();
    });

    interment?.addEventListener('change', () => {
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
    });

    interment?.addEventListener('input', () => {
        const raw = getRawDateString(interment, interPicker);
        if (!raw) {
            interment.setCustomValidity('');
            setFieldError(interment, intermentErr, '', interPicker?.altInput);
            return;
        }
        computeWakeDays();
        validateWakeInterment('light');
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
    });

    interment?.addEventListener('blur', () => {
        const raw = getRawDateString(interment, interPicker);
        if (!raw) return;
        computeWakeDays();
        validateWakeInterment('full');
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

        const pickerBaseOpts = {
            position: 'auto left',
            appendTo: document.body,
            animate: true,
            monthSelectorType: 'dropdown',
            prevArrow: '<span>&lsaquo;</span>',
            nextArrow: '<span>&rsaquo;</span>',
            onReady: (selectedDates, dateStr, instance) => {
                ensurePickerVisible(instance);
            },
            onOpen: (selectedDates, dateStr, instance) => {
                const cal = instance.calendarContainer;
                if (cal) {
                    cal.classList.add('shadow-2xl', 'border', 'border-slate-200');
                }
                ensurePickerVisible(instance);
            },
            onMonthChange: (selectedDates, dateStr, instance) => {
                ensurePickerVisible(instance);
            },
            onYearChange: (selectedDates, dateStr, instance) => {
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
                if (type === 'wake') return 'Please enter a valid wake start date.';
                return 'Please enter a valid interment date and time.';
            };

            visible.addEventListener('input', () => {
                const raw = visible.value.trim();
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

                computeWakeDays();
                validateWakeInterment('full');
                render();
            },
            onClose: (selectedDates, dateStr, instance) => {
                const raw = (funeral.dataset.lastTypedValue || instance.altInput?.value || '').trim();
                const invalidTyped = (funeral.dataset.invalidTypedValue || '').trim();

                if (invalidTyped || (raw && !selectedDates.length && Number.isNaN(Date.parse(raw)))) {
                    funeral.setCustomValidity('Please enter a valid wake start date.');
                    setFieldError(funeral, wakeErr, 'Please enter a valid wake start date.', instance.altInput);
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
                    return;
                }

                validateWakeInterment('light');
                setFieldError(funeral, wakeErr, funeral.validationMessage, wakePicker?.altInput);
            }
        });

        interPicker = flatpickr(interment, {
            altInput: true,
            altFormat: 'F j, Y h:i K',
            dateFormat: 'Y-m-d H:i',
            allowInput: true,
            clickOpens: false,
            enableTime: true,
            defaultDate: interment?.value || null,
            minDate: funeral?.value || died?.value || 'today',
            ...pickerBaseOpts,
            onChange: () => {
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
                interment.setCustomValidity('Please enter a valid interment date and time.');
                setFieldError(interment, intermentErr, 'Please enter a valid interment date and time.', instance.altInput);
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
                    return;
                }

                validateWakeInterment('light');
                setFieldError(interment, intermentErr, interment.validationMessage, interPicker?.altInput);
            }
        });

        const wakeTrigger = document.getElementById('wake_picker_trigger');
        if (wakeTrigger) wakeTrigger.addEventListener('click', () => wakePicker && wakePicker.open());

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

                if (funeral && died.value) {
                    funeral.min = died.value;
                    if (wakePicker) wakePicker.set('minDate', died.value);

                    const currentWake = getDateValue(funeral, wakePicker);
                    const deathDate = getDateValue(died, diedPicker);
                    if (currentWake && deathDate && currentWake < deathDate) {
                        funeral.value = died.value;
                        wakePicker?.setDate(died.value, true);
                    }
                }

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
        rememberTypedValue(wakePicker, funeral, wakeErr, 'wake');
        rememberTypedValue(interPicker, interment, intermentErr, 'interment');
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
    const bankRefWrap     = document.getElementById('bank_reference_wrap');

    function syncPayMethod() {
        const selected = payMethodRadios.find(r => r.checked)?.value;
        payMethodCards.forEach(card => {
            card.classList.toggle('active-tab', !!card.querySelector('.payment-method-radio')?.checked);
        });
        if (bankRefWrap) bankRefWrap.classList.toggle('hidden', selected !== 'BANK_TRANSFER');
    }

    payMethodRadios.forEach(r => r.addEventListener('change', syncPayMethod));
    syncPayMethod();

    pkgRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            renderPkg(true);
            syncControls();
            render();
        });
    });

    pkgCards.forEach((card) => {
        card.addEventListener('click', () => {
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
            const radio = cardRadio(card);
            if (radio && !radio.checked) radio.checked = true;
            renderPkg(true);
            syncControls();
            render();
        });
    }

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
    });

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
    syncBranch(false);
    syncRequestDate();
    syncControls();
    syncAge();
    syncDateConstraints();
    syncPreferredPackage();
    renderPkg(false);
    render();
    go(initialStep, { scroll: false, smooth: false });
    showBranchToast(branchPromptMessage());
})();
</script>
