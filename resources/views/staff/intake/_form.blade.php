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
       /* Force intake to fill the viewport like the dashboard */
       html, body { margin: 0 !important; padding: 0 !important; }
       .app-shell { width: 100% !important; }
       .main-area { padding: 0 !important; }
       .topbar { padding: 0 18px !important; margin: 0 !important; }
       @media (max-width: 1023px) {
           .topbar { padding: 0 16px !important; }
       }
       .page-content { padding: 0 !important; margin: 0 !important; width: 100% !important; background: transparent !important; }
       .page-content > * { padding: 0 !important; margin: 0 !important; width: 100% !important; }
       .intake-root { width: 100% !important; padding: 0 !important; margin: 0 !important; }

       html { scrollbar-gutter: auto; }

.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.form-input,
.form-textarea {
    width: 100%;
    border-radius: 0.9rem;
    border: 1px solid #646e82;
    background-color: #ffffff;
    padding: 0.82rem 1rem;
    font-size: 0.92rem;
    font-weight: 400;
    color: #0f172a;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, transform 0.18s ease;
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-input:focus,
.form-textarea:focus {
    border-color: #9c5a1a;
    box-shadow: 0 0 0 4px rgba(156, 90, 26, 0.10);
    outline: none;
}

.form-input::placeholder,
.form-textarea::placeholder {
    color: #9aa9bd;
    font-weight: 400 !important;
    opacity: 1;
}

.intake-root input::placeholder,
.intake-root textarea::placeholder {
    color: #9aa9bd !important;
    font-weight: 400 !important;
    opacity: 1;
}

.field-label {
    display: block;
    font-size: 0.72rem;
    font-weight: 500;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.55rem;
    padding-left: 2px;
}

.package-card-item { position: relative; }

.package-card-item .package-radio {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0.001;
    cursor: pointer;
    z-index: 5;
}

.package-card {
    transition: all 0.22s ease;
}

.package-card .check-dot {
    opacity: 0;
    transform: scale(0.6);
    border-color: #cbd5e1;
    transition: all 0.18s ease;
}

.package-card.selected {
    border-color: #0f172a;
    background-color: #f8fafc;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
}

.package-card.selected .check-dot {
    opacity: 1;
    transform: scale(1);
    border-color: #059669;
}

/* ===== Professional Flatpickr Header ===== */
.flatpickr-calendar {
    margin-top: 10px !important;
    border-radius: 22px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14) !important;
    padding: 12px 12px 10px !important;
    width: 340px !important;
    max-height: calc(100vh - 24px) !important;
    background: #ffffff !important;
    overflow: auto !important;
    z-index: 2400 !important;
}

.flatpickr-calendar.arrowTop::before,
.flatpickr-calendar.arrowTop::after,
.flatpickr-calendar.arrowBottom::before,
.flatpickr-calendar.arrowBottom::after {
    display: none !important;
}

.flatpickr-months {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 6px 34px 12px !important;
    margin-bottom: 2px !important;
    border-bottom: 1px solid #f1f5f9 !important;
}

.flatpickr-month {
    height: auto !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
}

.flatpickr-current-month {
    position: static !important;
    left: auto !important;
    width: auto !important;
    height: auto !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    line-height: 1.2 !important;
    color: #0f172a !important;
    font-weight: 800 !important;
    white-space: nowrap !important;
}

.flatpickr-current-month .flatpickr-monthDropdown-months {
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    border: 1px solid #e2e8f0 !important;
    background: #f8fafc !important;
    border-radius: 10px !important;
    padding: 4px 28px 4px 10px !important;
    margin: 0 !important;
    font-size: 15px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    line-height: 1.2 !important;
    cursor: pointer !important;
    box-shadow: none !important;
    min-width: 110px !important;
}

.flatpickr-current-month input.cur-year {
    border: 1px solid #e2e8f0 !important;
    background: #f8fafc !important;
    border-radius: 10px !important;
    box-shadow: none !important;
    padding: 4px 8px !important;
    margin: 0 !important;
    font-size: 15px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    line-height: 1.2 !important;
    width: 74px !important;
    min-width: 74px !important;
    text-align: center !important;
}

.flatpickr-current-month .flatpickr-monthDropdown-months:focus,
.flatpickr-current-month input.cur-year:focus {
    outline: none !important;
    border-color: #9c5a1a !important;
    box-shadow: 0 0 0 3px rgba(156, 90, 26, 0.10) !important;
}

.numInputWrapper {
    width: auto !important;
    min-width: 74px !important;
}

.numInputWrapper span {
    display: none !important;
}

.flatpickr-prev-month,
.flatpickr-next-month {
    top: 12px !important;
    width: 30px !important;
    height: 30px !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: #64748b !important;
    border-radius: 999px !important;
    background: #ffffff !important;
    transition: all 0.15s ease !important;
}

.flatpickr-prev-month:hover,
.flatpickr-next-month:hover {
    color: #0f172a !important;
    background: #f1f5f9 !important;
}

.flatpickr-prev-month svg,
.flatpickr-next-month svg {
    display: none !important;
}

.flatpickr-prev-month::before {
    content: "‹";
    font-size: 22px;
    font-weight: 700;
    line-height: 1;
}

.flatpickr-next-month::before {
    content: "›";
    font-size: 22px;
    font-weight: 700;
    line-height: 1;
}

.flatpickr-weekdays {
    margin: 8px 0 10px !important;
}

.flatpickr-weekday {
    font-size: 12px !important;
    font-weight: 800 !important;
    color: #64748b !important;
}

.flatpickr-day {
    border-radius: 14px !important;
    max-width: 42px !important;
    height: 42px !important;
    line-height: 42px !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
}

.flatpickr-day:hover {
    background: #f1f5f9 !important;
    border-color: #f1f5f9 !important;
}

.flatpickr-day.today {
    border: 1px solid #b45309 !important;
}

.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange {
    background: #0f172a !important;
    border-color: #0f172a !important;
    color: #ffffff !important;
}

.flatpickr-day.disabled,
.flatpickr-day.prevMonthDay,
.flatpickr-day.nextMonthDay {
    color: #cbd5e1 !important;
}

.flatpickr-time {
    border-top: 1px solid #e2e8f0 !important;
    margin-top: 8px !important;
    padding-top: 10px !important;
}

.flatpickr-time input,
.flatpickr-time .flatpickr-am-pm {
    border-radius: 10px !important;
    font-weight: 700 !important;
}

.flatpickr-calendar.open {
    margin-top: 12px !important;
}

.intake-locked {
    opacity: 0.55;
    pointer-events: none;
    user-select: none;
    filter: grayscale(0.06);
}

.lock-overlay {
    position: absolute;
    inset: 0;
    z-index: 20;
    background: rgba(255,255,255,0.72);
    backdrop-filter: blur(1px);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 3.5rem;
    border-radius: 0 0 1rem 1rem;
}

.lock-overlay.hidden {
    display: none;
}

.toast-pop {
    left: 50% !important;
    top: calc(var(--topbar-h, 72px) + 10px) !important;
    transform: translate(-50%, -8px) scale(0.96);
    min-width: 280px;
    text-align: center;
    pointer-events: none;
    filter: none;
    border: 1px solid rgba(148, 163, 184, 0.3);
    transition: opacity 0.25s ease, transform 0.25s ease;
    z-index: 40 !important;
}

.toast-pop.toast-visible {
    opacity: 1 !important;
    transform: translate(-50%, 0) scale(1);
}

.intake-toast-branch {
    top: calc(var(--topbar-h, 72px) + 10px) !important;
}

.intake-toast-package {
    top: calc(var(--topbar-h, 72px) + 58px) !important;
}

html[data-theme='dark'] .intake-toast-branch {
    background: #1a2b41 !important;
    color: #e8f0fc !important;
    border-color: #3f5b7f !important;
}

html[data-theme='dark'] .intake-toast-package {
    background: #1f5d46 !important;
    color: #e8fff4 !important;
    border-color: #2e8666 !important;
}

.intake-section-shell {
    border: none;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    overflow: visible;
}

.subsection-soft {
    border: 1px solid #e7edf4;
    border-radius: 1.1rem;
    background: linear-gradient(180deg, #fbfcfe 0%, #f8fafc 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.75);
}

.wizard-tab {
    --wizard-step-size: 34px;
    --wizard-step-offset-y: 0.35rem;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.28rem;
    min-width: 110px;
    padding: 0.35rem 0.5rem 0.55rem;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    text-transform: none;
    color: #475569;
    background: transparent;
    border: none;
}

.wizard-tab .wizard-step-number {
    width: var(--wizard-step-size);
    height: var(--wizard-step-size);
    border-radius: 50%;
    border: 1px solid #cbd5e1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 2;
    font-weight: 700;
    color: #475569;
    background: #fff;
    transition: all 0.18s ease;
}

.wizard-tab .wizard-step-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
}

.wizard-tab:hover {
    color: #9c5a1a;
}

.wizard-tab::after {
    content: "";
    position: absolute;
    left: calc(50% + (var(--wizard-step-size) / 2));
    top: calc(var(--wizard-step-offset-y) + (var(--wizard-step-size) / 2));
    width: calc(100% - (var(--wizard-step-size) / 2));
    height: 2.5px;
    border-radius: 999px;
    background: #dbe3ec;
    transform: translateY(-50%);
    z-index: 1;
}

.wizard-tab:last-child::after {
    display: none;
}

.wizard-tab.completed-step::after {
    background: #9c5a1a;
}

.wizard-tab.active-step {
    color: #9c5a1a;
}

.wizard-tab:hover .wizard-step-number {
    border-color: #9c5a1a;
    color: #9c5a1a;
}

.wizard-tab.active-step .wizard-step-number {
    background: #9c5a1a;
    color: #fff;
    border-color: #9c5a1a;
}

.wizard-tab.completed-step .wizard-step-number {
    background: #9c5a1a;
    color: #ffffff;
    border-color: #9c5a1a;
}

.wizard-tab.completed-step .wizard-step-label {
    color: #475569;
    font-weight: 600;
}

.wizard-tab.active-step .wizard-step-label {
    color: #9c5a1a;
    font-weight: 700;
}

.section-heading-icon {
    width: 2.8rem;
    height: 2.8rem;
    border-radius: 1rem;
    background: #f6f8fb;
    border: 1px solid #dbe3ec;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: none;
}

.sticky-review-card {
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16);
}

.footer-action-bar {
    backdrop-filter: none;
}

.package-card,
.payment-type-card {
    will-change: transform, box-shadow;
}

.package-card:hover,
.payment-type-card:hover {
    transform: translateY(-1px);
}

/* ===== New shared visual helpers ===== */
.intake-top-shell {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding-left: 36px;
    padding-right: 36px;
    padding-bottom: 0.4rem;
    margin-bottom: 0.9rem;
    border-bottom: 1px solid #e5eaf1;
}

@media (min-width: 1024px) {
    .intake-top-shell {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.intake-mode-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 0.85rem;
    border-radius: 0.9rem;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
}

.intake-meta-shell {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 1.25rem;
    padding: 0.2rem 0;
    background: transparent;
    border: none;
    border-radius: 0;
    box-shadow: none;
}

.intake-meta-divider {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 1.25rem;
    padding-right: 0;
    border-right: 0;
}

@media (max-width: 1023px) {
    .intake-meta-divider {
        border-right: 0;
        padding-right: 0;
    }
}

@media (max-width: 768px) {
    .wizard-steps-shell {
        display: flex !important;
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        gap: 0.35rem;
        padding: 0.45rem 16px 0.25rem;
    }

    .wizard-tab {
        --wizard-step-size: 30px;
        --wizard-step-offset-y: 0.28rem;
        flex: 0 0 auto !important;
        min-width: 94px;
        padding: 0.28rem 0.4rem 0.45rem;
        gap: 0.2rem;
    }

    .wizard-tab .wizard-step-number {
        font-size: 0.78rem;
    }

    .wizard-tab .wizard-step-label {
        font-size: 0.78rem;
        text-align: center;
    }

    .intake-meta-shell {
        display: block;
        padding: 0;
    }

    .intake-meta-divider {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 1rem;
        row-gap: 0.75rem;
        align-items: start;
    }

    .intake-meta-item {
        min-width: 0;
    }
}

.intake-meta-item {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    gap: 0.18rem;
    min-width: 92px;
}

.intake-meta-label {
    font-size: 0.63rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-weight: 800;
    color: #64748b;
}

.intake-meta-value {
    font-size: 0.78rem;
    font-weight: 500;
    color: #475569;
}

.branch-toggle {
    padding: 0.08rem 0.2rem;
    border: none;
    border-radius: 0;
    background: transparent;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: #475569;
    box-shadow: none;
    transition: color 0.15s ease, text-decoration-color 0.15s ease;
}

.wizard-steps-shell {
    position: relative;
    background: transparent;
    border-bottom: 1px solid #e6ebf2;
    gap: 1rem;
    padding-left: 18px;
    padding-right: 18px;
}

.section-title-block {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    margin-bottom: 2rem;
}

.section-title-text h3 {
    margin: 0;
    font-size: 1.12rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}

.section-title-text p {
    margin: 0.2rem 0 0;
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 500;
}

#intakeWizardForm {
    background-color: transparent;
}

#intakeFormContent {
    padding-left: 36px !important;
    padding-right: 36px !important;
}

.footer-action-bar {
    padding-left: 36px !important;
    padding-right: 36px !important;
}

html[data-theme='dark'] .panel-shell-body,
html[data-theme='dark'] .main-area,
html[data-theme='dark'] .page-content,
html[data-theme='dark'] .intake-root,
html[data-theme='dark'] .intake-section-shell {
    background: #223148 !important;
    color: #e5edf6;
}

html[data-theme='dark'] .intake-top-shell,
html[data-theme='dark'] .wizard-steps-shell {
    border-color: #3f536b;
}

html[data-theme='dark'] .section-title-text h3 {
    color: #f8fbff;
}

html[data-theme='dark'] .section-title-text p,
html[data-theme='dark'] .field-label,
html[data-theme='dark'] .intake-meta-label,
html[data-theme='dark'] .intake-meta-value {
    color: #a9bbd2;
}

html[data-theme='dark'] .lock-overlay {
    background: rgba(15, 23, 42, 0.72);
}

html[data-theme='dark'] #intake_lock_overlay .bg-white {
    background: #1b2a3d !important;
    border-color: #3f536b !important;
    color: #e5edf6 !important;
}

html[data-theme='dark'] #intake_lock_overlay .bg-slate-100 {
    background: #273a52 !important;
    border-color: #3f536b !important;
    color: #d7e6fa !important;
}

@media (max-width: 1023px) {
    .intake-top-shell,
    .wizard-steps-shell,
    #intakeFormContent,
    .footer-action-bar {
        padding-left: 16px !important;
        padding-right: 16px !important;
    }
}

@media (max-width: 640px) {
    .footer-action-bar {
        align-items: stretch;
        gap: 0.75rem;
    }

    #wizardPrev {
        min-width: 104px;
        text-align: center;
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

    <div class="intake-top-shell">
    @php
        $intakeModeLabel = $isOtherEntryMode ? 'Other Branch' : 'Main Branch';
    @endphp

    <div class="intake-meta-shell text-sm">
        <div class="intake-meta-divider place-items-start">
            <div class="intake-meta-item">
                <span class="intake-meta-label">Case ID</span>
                <span id="next_case_code" class="intake-meta-value">{{ $nextCode }}</span>
            </div>

            <div class="intake-meta-item hidden sm:flex">
                <span class="intake-meta-label">{{ $isOtherEntryMode ? 'Encoded' : 'Request' }}</span>
                <span id="service_requested_display" class="intake-meta-value">
                    {{ \Illuminate\Support\Carbon::parse(old('service_requested_at', now()->toDateString()))->format('M d, Y') }}
                </span>
            </div>

            <div class="intake-meta-item hidden lg:flex">
                <span class="intake-meta-label">Encoder</span>
                <span class="intake-meta-value">{{ auth()->user()->name }}</span>
            </div>

            <div class="intake-meta-item">
                <span class="intake-meta-label">Branch <span class="text-rose-500">*</span></span>
                <span class="intake-meta-value" id="selected_branch_code">{{ $branches->firstWhere('id', $initialSelectedBranchId)?->branch_code }}</span>
            </div>
        </div>

        <div id="branch_error" class="hidden text-xs font-bold text-rose-500 w-full"></div>
    </div>
</div>

    @if($isOtherEntryMode)
        <div class="mb-6 rounded-2xl border px-5 py-4 text-sm {{ $otherBranchWindowClosed ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-amber-200 bg-amber-50 text-amber-900' }} shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
            <div class="font-bold flex items-center gap-2">
                <i class="bi {{ $otherBranchWindowClosed ? 'bi-x-circle-fill text-rose-500' : 'bi-exclamation-triangle-fill text-amber-500' }}"></i>
                {{ $otherBranchWindowClosed ? 'Intake Window Closed' : 'External Branch Rules Apply' }}
            </div>
            <div class="mt-1 text-xs font-medium opacity-90">
                Reports must be completed, fully paid, and submitted within today only (00:00 to {{ $otherBranchCutoffAt->format('H:i') }}).
            </div>
        </div>
    @endif

    <div class="intake-section-shell overflow-hidden relative">
        <div id="intake_lock_overlay" class="lock-overlay hidden">
            <div class="max-w-md w-full mx-4 rounded-3xl border border-slate-200 bg-white shadow-[0_20px_45px_rgba(15,23,42,0.16)] px-5 py-5">
                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center shrink-0">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Select Branch to Begin</h4>
                        <p class="text-xs text-slate-600 mt-1">
                            To begin, please select which branch this case belongs to.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="wizard-steps-shell flex overflow-x-auto hide-scrollbar" id="wizardSteps">
    @php
        $steps = [
            1 => 'Client',
            2 => 'Deceased',
            3 => 'Package',
            4 => 'Service Details',
            5 => 'Billing',
            6 => $isOtherEntryMode ? 'Payment Confirmation' : 'Payment',
            7 => 'Review'
        ];
    @endphp

    @foreach($steps as $num => $label)
        <button
            type="button"
            data-step="{{ $num }}"
            class="wizard-tab flex-1 {{ $num === 1 ? 'active-step' : '' }}"
        >
            <span class="wizard-step-number">{{ $num }}</span>
            <span class="wizard-step-label">{{ $label }}</span>
        </button>
    @endforeach
</div>

        <form method="POST" action="{{ $formAction ?? route('intake.main.store') }}" enctype="multipart/form-data" id="intakeWizardForm">
            @csrf

            <input type="hidden" name="service_requested_at" id="service_requested_at" value="{{ old('service_requested_at', now()->toDateString()) }}">
            <input type="hidden" name="branch_id" id="branch_id" value="{{ $initialSelectedBranchId }}">
            <input type="hidden" id="branch_code_main_default" value="{{ optional($branches->first())->branch_code ?? 'BR001' }}">

            <div id="intakeFormContent" class="p-6 md:p-8 lg:p-10 xl:p-12">
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
                                <label class="field-label">Reported At</label>
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <div class="md:col-span-2 lg:col-span-1">
                            <label class="field-label">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="client_name" value="{{ old('client_name') }}" data-validate="letters-spaces" data-label="client name" class="form-input" placeholder="e.g., Maria Santos" required>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:col-span-2 lg:col-span-1">
                            <div>
                                <label class="field-label">Relationship <span class="text-rose-500">*</span></label>
                                <select name="client_relationship" data-label="relationship to the deceased" class="form-input" required>
                                    <option value="">Select...</option>
                                    @foreach(($clientRelationshipOptions ?? []) as $relationship)
                                        <option value="{{ $relationship }}" {{ old('client_relationship') === $relationship ? 'selected' : '' }}>
                                            {{ $relationship }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="field-label">Contact Number <span class="text-rose-500">*</span></label>
                                <input
                                    type="text"
                                    name="client_contact_number"
                                    value="{{ old('client_contact_number') }}"
                                    data-validate="digits"
                                    data-label="contact number"
                                    inputmode="numeric"
                                    maxlength="15"
                                    pattern="[0-9]{7,15}"
                                    title="7 to 15 digits only"
                                    class="form-input"
                                    placeholder="e.g. 0912..."
                                    required
                                >
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="field-label">Complete Address <span class="text-rose-500">*</span></label>
                            <input type="text" name="client_address" id="client_address" value="{{ old('client_address') }}" data-label="client address" class="form-input" placeholder="House No, Street, Barangay, City" required>
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

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-5">
                        <div class="md:col-span-8">
                            <label class="field-label">Deceased Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="deceased_name" value="{{ old('deceased_name') }}" data-validate="letters-spaces" data-label="deceased name" class="form-input" placeholder="e.g., Juan Dela Cruz" required>
                        </div>

                        <div class="md:col-span-4">
                            <label class="field-label">Date of Birth <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="born" id="born" value="{{ old('born') }}" data-label="birthdate" class="form-input pr-10 cursor-pointer" placeholder="e.g., January 2, 1990" autocomplete="off" required>
                                <span id="born_picker_trigger" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                    <i class="bi bi-calendar-event text-lg"></i>
                                </span>
                            </div>
                            @error('born')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p id="born_error" class="mt-1 text-sm text-red-600 hidden"></p>
                        </div>

                        <div class="md:col-span-8">
                            <label class="field-label">Complete Address <span class="text-rose-500">*</span></label>
                            <input type="text" name="deceased_address" id="deceased_address" value="{{ old('deceased_address', old('client_address')) }}" data-label="deceased address" class="form-input" placeholder="House No, Street, Barangay, City" required>
                        </div>

                        <div class="md:col-span-4">
                            <label class="field-label">Date of Death <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="died" id="died" value="{{ old('died') }}" data-label="date of death" class="form-input pr-10 cursor-pointer" placeholder="e.g., January 27, 2026" autocomplete="off" required>
                                <span id="died_picker_trigger" class="absolute inset-y-0 right-3 flex items-center text-slate-400 cursor-pointer hover:text-slate-600 transition-colors">
                                    <i class="bi bi-calendar-event text-lg"></i>
                                </span>
                            </div>
                            @error('died')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p id="died_error" class="mt-1 text-sm text-red-600 hidden"></p>
                        </div>

                        <div class="md:col-span-3">
                            <label class="field-label">Age</label>
                            <input type="number" name="age" id="age" value="{{ old('age') }}" class="form-input bg-slate-50 text-slate-500 cursor-not-allowed" readonly>
                        </div>

                        <div class="md:col-span-9">
                            <label class="field-label">Senior Citizen Verification</label>
                            <select name="senior_citizen_status" id="senior_citizen_status" data-label="senior citizen status" class="form-input w-full">
                                <option value="0" {{ old('senior_citizen_status', '0') === '0' ? 'selected' : '' }}>No / Standard</option>
                                <option value="1" {{ old('senior_citizen_status') === '1' ? 'selected' : '' }}>Yes / Eligible</option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1">Applies automatic discounts if enabled.</p>

                            <div id="senior_id_wrap" class="mt-3 hidden">
                                <label class="field-label">Senior Citizen ID Number <span class="text-slate-400 normal-case tracking-normal font-normal">(Optional)</span></label>
                                <input type="text" name="senior_citizen_id_number" id="senior_citizen_id_number" value="{{ old('senior_citizen_id_number') }}" data-label="Senior Citizen ID number" class="form-input w-full md:w-1/2" placeholder="Enter Valid ID">
                            </div>

                            <div id="senior_proof_wrap" class="mt-3 hidden">
                                <label class="field-label">Upload Senior ID / Certificate <span class="text-slate-400 normal-case tracking-normal font-normal">(Optional)</span></label>
                                <input type="file" name="senior_proof" id="senior_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" class="form-input">
                                <p class="text-[11px] text-slate-500 mt-1">Accepted: JPG, PNG, WEBP, PDF. Max 5MB.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="3">
                    <div class="flex items-center justify-between mb-6">
                        <div class="section-title-block" style="margin-bottom:0;">
                            <div class="section-heading-icon">
                                <i class="bi bi-box-seam-fill"></i>
                            </div>
                            <div class="section-title-text">
                                <h3>Service Package</h3>
                                <p>Select the appropriate service package for this case.</p>
                            </div>
                        </div>

                        <div id="package_error" class="hidden text-xs font-bold text-rose-500 bg-rose-50 px-3 py-1 rounded-md">
                            Selection Required
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="packageCardList">
                        @foreach($packages as $pkg)
                            @php
                                $promoNow = $pkg->promo_is_active
                                    && (!$pkg->promo_starts_at || $pkg->promo_starts_at->lte(now()))
                                    && (!$pkg->promo_ends_at || $pkg->promo_ends_at->gte(now()));
                            @endphp

                            <div class="package-card-item">
                                <label class="package-card relative flex flex-col p-5 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-slate-300 transition-all group focus-within:ring-2 focus-within:ring-slate-900">
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
                                        data-inclusions="{{ e((string) $pkg->inclusions) }}"
                                        data-freebies="{{ e((string) $pkg->freebies) }}"
                                        {{ (string) old('package_id') === (string) $pkg->id ? 'checked' : '' }}
                                        required
                                    >

                                    <div class="mb-2 flex items-start justify-between">
                                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ $pkg->coffin_type ?? 'Standard' }}</span>

                                        @if($promoNow)
                                            <span class="bg-emerald-100 text-emerald-800 text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider">
                                                {{ $pkg->promo_label }}
                                            </span>
                                        @endif
                                    </div>

                                    <h4 class="package-title text-base font-bold text-slate-900 leading-tight">{{ $pkg->name }}</h4>

                                    <div class="mt-6 flex items-baseline justify-between">
                                        <div class="text-xl font-black text-slate-900">&#8369;{{ number_format($pkg->price, 2) }}</div>
                                        <div class="check-dot w-6 h-6 rounded-full border-2 flex items-center justify-center bg-white transition-all">
                                            <i class="bi bi-check text-emerald-600 text-base"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endforeach

                        @if(empty($entryMode) || $entryMode === 'main')
                            <div class="package-card-item relative">
                                <label class="package-card relative flex flex-col p-5 rounded-xl border-2 border-dashed border-amber-200 bg-amber-50/40 cursor-pointer hover:border-amber-300 transition-all group focus-within:ring-2 focus-within:ring-amber-500/50">
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

                                    <div class="mb-2 flex items-start justify-between">
                                        <span class="text-[9px] font-black uppercase tracking-widest text-amber-700">Client Preference</span>
                                        <span class="bg-white text-amber-700 text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider border border-amber-200">Custom</span>
                                    </div>

                                    <h4 class="package-title text-base font-bold text-amber-900 leading-tight">Enter package name</h4>

                                    <div class="mt-6 flex items-baseline justify-between">
                                        <div class="text-xl font-black text-amber-900">&#8369;<span id="custom_package_price_display">0.00</span></div>
                                        <div class="check-dot w-6 h-6 rounded-full border-2 border-amber-300 flex items-center justify-center bg-white transition-all">
                                            <i class="bi bi-check text-emerald-600 text-base"></i>
                                        </div>
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
                </section>

                <section class="wizard-panel hidden" data-step="4">
                    <div class="section-title-block">
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
                </section>

                <section class="wizard-panel hidden" data-step="5">
                    <div class="section-title-block">
                        <div class="section-heading-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="section-title-text">
                            <h3>Billing Statement</h3>
                            <p>Review charges, discounts and total amount due.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Package Price</label>
                                    <div class="flex items-center rounded-lg border border-slate-300 bg-slate-50 focus-within:ring-2 focus-within:ring-slate-900">
                                        <span class="pl-3 pr-2 text-slate-500 font-bold">&#8369;</span>
                                        <input type="number" step="0.01" name="package_amount" id="package_amount" value="{{ old('package_amount') }}" class="w-full border-0 focus:outline-none focus:ring-0 bg-slate-50 font-bold p-3" readonly>
                                    </div>
                                </div>

                                <div>
                                    <label class="field-label">Additional Charges</label>
                                    <div class="flex items-center rounded-lg border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-slate-900">
                                        <span class="pl-3 pr-2 text-slate-500 font-bold">&#8369;</span>
                                        <input type="number" step="0.01" min="0" name="additional_service_amount" id="additional_service_amount" value="{{ old('additional_service_amount') }}" data-label="additional charges" class="w-full border-0 focus:outline-none focus:ring-0 font-bold p-3" placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="field-label">Description of Extras</label>
                                <textarea name="additional_services" id="additional_services" rows="2" data-label="additional services" class="form-textarea" placeholder="Detail any add-ons here...">{{ old('additional_services') }}</textarea>
                            </div>

                            <div class="p-5 rounded-xl border border-slate-200 bg-slate-50 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Discounts & Tax</h4>
                                </div>

                                <div>
                                    <label class="field-label">Discount Applied</label>
                                    <input type="text" id="auto_discount_type" value="None" class="form-input bg-white text-xs font-bold" readonly>
                                </div>

                                <div>
                                    <label class="field-label">Discount Value</label>
                                    <input type="text" id="auto_discount_amount" value="PHP 0.00" class="form-input bg-white text-xs font-bold text-emerald-600" readonly>
                                </div>

                                <div>
                                    <label class="field-label">Tax Rate (%)</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0" max="100" name="tax_rate" id="tax_rate" value="{{ old('tax_rate', '0') }}" data-label="tax rate" class="form-input pr-10">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 font-bold text-slate-400">%</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="field-label">Tax Amount</label>
                                    <input type="text" id="tax_amount_display" value="PHP 0.00" class="form-input bg-white text-xs font-bold text-rose-600" readonly>
                                </div>

                                <div id="discount_help_wrap" class="col-span-2 text-[11px] font-medium text-slate-500 flex gap-2 items-start mt-1">
                                    <i class="bi bi-info-circle text-blue-500"></i>
                                    <span id="discount_help_text_secondary">Discount is tied to Senior Citizen status.</span>
                                </div>
                            </div>
                        </div>

                        <div class="self-start sticky top-6">
                            <div class="sticky-review-card bg-slate-900 rounded-[1.35rem] p-6 text-white">
                                <h4 class=" text-white text-center uppercase tracking-widest opacity-90 mb-5">Summary Dashboard</h4>

                                <div class="space-y-4 text-sm">
                                    <div class="flex justify-between border-b border-white/10 pb-3">
                                        <span class="opacity-70">Package</span>
                                        <span class="font-bold"><span class="mr-1">&#8369;</span><span id="summary_package_price">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between border-b border-white/10 pb-3">
                                        <span class="opacity-70">Extras</span>
                                        <span class="font-bold"><span class="mr-1">&#8369;</span><span id="summary_additional">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between border-b border-white/10 pb-3">
                                        <span class="opacity-70 text-xs">Subtotal</span>
                                        <span class="font-bold text-xs"><span class="mr-1">&#8369;</span><span id="summary_subtotal">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between text-emerald-400">
                                        <span class="text-xs">Discount (<span id="summary_discount_source">None</span>)</span>
                                        <span class="font-bold">- <span class="mr-1">&#8369;</span><span id="summary_discount">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between border-b border-white/10 pb-3">
                                        <span class="opacity-70 text-xs">Tax</span>
                                        <span class="font-bold text-xs"><span class="mr-1">&#8369;</span><span id="summary_tax">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between items-end pt-2">
                                        <span class="text-base font-bold">Total Due</span>
                                        <span class="text-2xl font-black"><span class="mr-1">&#8369;</span><span id="summary_total">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between border-t border-white/10 pt-3 text-xs">
                                        <span class="opacity-70">Payment Status</span>
                                        <span id="summary_payment_status" class="font-bold">UNPAID</span>
                                    </div>

                                    <div class="flex justify-between text-xs">
                                        <span class="opacity-70">Balance</span>
                                        <span class="font-bold"><span class="mr-1">&#8369;</span><span id="summary_balance">0.00</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="6">
                    <div class="section-title-block">
                        <div class="section-heading-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="section-title-text">
                            <h3>{{ $isOtherEntryMode ? 'Payment Confirmation' : 'Process Payment' }}</h3>
                            <p>{{ $isOtherEntryMode ? 'Confirm the required payment condition for external branch intake.' : 'Record an initial payment, deposit, or full settlement.' }}</p>
                        </div>
                    </div>

                    @if($isOtherEntryMode)
                        <input type="hidden" name="mark_as_paid" id="mark_as_paid" value="1">
                        <input type="hidden" name="payment_type" id="payment_type" value="FULL">

                        <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-900 flex gap-3">
                            <i class="bi bi-shield-check text-amber-600 text-lg"></i>
                            <div>
                                <strong class="block text-xs uppercase tracking-wider mb-0.5">Verification Rule Applied</strong>
                                This external branch report will be saved as fully paid and routed automatically.
                            </div>
                        </div>
                    @endif

                    @if(!$isOtherEntryMode)
                        <div class="mb-8">
                            <label class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="checkbox" name="mark_as_paid" id="mark_as_paid" value="1" {{ old('mark_as_paid') ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                                <div>
                                    <span class="block text-sm font-bold text-slate-800">Record Initial Payment Now</span>
                                    <span class="block text-xs text-slate-500">Toggle this to log a deposit or full settlement.</span>
                                </div>
                            </label>
                        </div>
                    @endif

                    <div id="payment_form_fields" class="{{ $isOtherEntryMode ? '' : 'hidden' }} space-y-6">
                        @if(!$isOtherEntryMode)
                            <div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="payment_type_group">
                                    <label class="payment-type-card flex items-center justify-center py-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-slate-300 transition-colors">
                                        <input type="radio" name="payment_type" value="FULL" class="payment-type-radio sr-only" {{ old('payment_type') === 'FULL' ? 'checked' : '' }}>
                                        <span class="text-sm font-bold text-slate-800">Full Payment</span>
                                    </label>

                                    <label class="payment-type-card flex items-center justify-center py-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-slate-300 transition-colors">
                                        <input type="radio" name="payment_type" value="PARTIAL" class="payment-type-radio sr-only" {{ old('payment_type') === 'PARTIAL' ? 'checked' : '' }}>
                                        <span class="text-sm font-bold text-slate-800">Partial Payment</span>
                                    </label>
                                </div>

                                <div id="payment_type_error" class="hidden text-xs font-bold text-rose-500 mt-2">Please select a type.</div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="field-label">Amount Tendered</label>
                                <div class="flex items-center rounded-lg border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-slate-900">
                                    <span class="pl-3 pr-2 text-slate-500 font-bold">&#8369;</span>
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
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2" id="payment_amount_hint">
                                    {{ $isOtherEntryMode ? 'Must equal full amount' : 'Enter amount received' }}
                                </p>
                            </div>

                            <div>
                                <label class="field-label">Transaction Timestamp</label>
                                <input type="datetime-local" name="paid_at" id="paid_at" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}" data-label="payment date" class="form-input">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
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
                </section>

                <section class="wizard-panel hidden" data-step="7">
                    <div class="section-title-block pb-4 border-b border-slate-100">
                        <div class="section-heading-icon">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <div class="section-title-text">
                            <h3>Final Review</h3>
                            <p>Check each section carefully before saving the record.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        @foreach(['client' => 'Client', 'deceased' => 'Deceased', 'package' => 'Package', 'service' => 'Service', 'billing' => 'Billing', 'payment' => 'Payment'] as $id => $title)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 group relative shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
                                <button
                                    type="button"
                                    class="review-edit absolute top-4 right-4 text-[10px] font-bold uppercase tracking-wider text-slate-400 hover:text-slate-900 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                    data-jump-step="{{ $loop->iteration }}"
                                >
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>

                                <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">{{ $title }}</h4>
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
                    <button type="button" id="wizardNext" class="w-full sm:w-auto sm:min-w-[190px] px-8 py-3 rounded-xl bg-slate-900 text-white text-sm font-bold transition-all hover:bg-[#9c5a1a] hover:-translate-y-0.5">
                        Continue
                    </button>

                    <button type="submit" id="saveIntakeRecord" class="hidden w-full sm:w-auto sm:min-w-[190px] px-8 py-3 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 transition-all disabled:opacity-50">
                        Save Record
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

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
    };

    const labelFor = (field) => field?.dataset?.label || field?.getAttribute('placeholder') || 'this field';

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
            const active = card.querySelector('.payment-type-radio')?.checked;
            card.classList.toggle('border-slate-800', !!active);
            card.classList.toggle('bg-slate-50', !!active);
            card.classList.toggle('ring-2', !!active);
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
                detailRow('Client Name', textOrDash(f.elements.client_name?.value)),
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
                detailRow('Deceased Name', textOrDash(f.elements.deceased_name?.value)),
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

        if (targetStep === 2) validateDobDod('full');
        if (targetStep === 4) validateWakeInterment('full');

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

        if (targetStep === 4) {
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

        if (targetStep === 6 && payNow()) {
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

        if (targetStep === 7) {
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

    if (mark?.type === 'checkbox') {
        mark.addEventListener('change', () => {
            syncControls();
            render();
        });
    }

    payTypeRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            clearFieldMessage(amountPaid);
            syncControls();
            render();
        });
    });

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

        for (let index = 1; index <= totalSteps - 1; index += 1) {
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
