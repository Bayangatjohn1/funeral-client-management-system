<style>
#intakeWizardForm :is(#intakeFormContent, .schedule-advisory-panel, .schedule-advisory-panel ul) {
    scrollbar-width:thin;
    scrollbar-color:color-mix(in srgb, var(--intake-text) 32%, transparent) transparent;
}
#intakeWizardForm :is(#intakeFormContent, .schedule-advisory-panel, .schedule-advisory-panel ul):hover {
    scrollbar-color:color-mix(in srgb, var(--intake-text) 48%, transparent) transparent;
}
html[data-theme] #intakeWizardForm .footer-action-bar :is(#intakeCancelBtn, #wizardPrev, #wizardNext) {
    border-color:transparent !important;
    outline:none !important;
    box-shadow:none !important;
    transform:none !important;
}
html[data-theme] #intakeWizardForm .footer-action-bar #intakeCancelBtn {
    background:#f8e5e4 !important;
    color:#8a3537 !important;
}
html[data-theme] #intakeWizardForm .footer-action-bar #intakeCancelBtn:hover:not(:disabled) {
    background:#efd2d0 !important;
}
html[data-theme] #intakeWizardForm .footer-action-bar #wizardPrev {
    background:var(--staff-soft, #e9efeb) !important;
    color:var(--staff-ink, #18372b) !important;
}
html[data-theme] #intakeWizardForm .footer-action-bar #wizardPrev:hover:not(:disabled) {
    background:var(--intake-hover, #dce8df) !important;
}
html[data-theme] #intakeWizardForm .footer-action-bar #wizardNext:not([data-action="draft"]) {
    background:#1b4332 !important;
    color:#fff !important;
}
html[data-theme] #intakeWizardForm .footer-action-bar #wizardNext:not([data-action="draft"]):hover:not(:disabled) {
    background:#123325 !important;
}
html[data-theme="dark"] #intakeWizardForm .footer-action-bar #intakeCancelBtn {
    background:#42282d !important;
    color:#fecaca !important;
}
html[data-theme="dark"] #intakeWizardForm .footer-action-bar #intakeCancelBtn:hover:not(:disabled) {
    background:#563138 !important;
}
html[data-theme] #intakeWizardForm .footer-action-bar :is(#intakeCancelBtn, #wizardPrev, #wizardNext):focus-visible {
    text-decoration:underline;
    text-underline-offset:3px;
}
/* The backdrop shows through the gaps between the three intake panels. */
html[data-theme] body.panel-shell-body.intake-frame .intake-root,
html[data-theme] body.panel-shell-body.intake-frame .intake-root .intake-section-shell,
html[data-theme] body.panel-shell-body.intake-frame .intake-root #intakeWizardForm {
    background:transparent !important;
    border:0 !important;
    box-shadow:none !important;
}
body.panel-shell-body.intake-frame #intakeWizardForm {
    --intake-panel-end-gap: 8px;
    --intake-panel-top-gap: 12px;
}
html[data-theme] body.panel-shell-body.intake-frame .intake-root #intakeWizardForm #wizardSteps,
html[data-theme] body.panel-shell-body.intake-frame .intake-root #intakeWizardForm #intakeFormContent,
html[data-theme] body.panel-shell-body.intake-frame .intake-root #intakeWizardForm > .footer-action-bar {
    box-sizing:border-box;
    width:auto !important;
    margin:0 var(--intake-panel-end-gap) 10px 12px !important;
    border:1px solid var(--staff-border, var(--intake-border)) !important;
    border-radius:12px !important;
    background:var(--staff-card, var(--intake-card)) !important;
}
body.panel-shell-body .intake-root.intake-reference #wizardSteps {
    gap:8px;
}
html[data-theme] body.panel-shell-body .intake-root.intake-reference #wizardSteps .wizard-tab {
    flex:1 0 auto;
    min-width:0;
    background:transparent !important;
    border-color:transparent !important;
}
body.panel-shell-body .intake-root.intake-reference #wizardSteps .wizard-tab .wz-body {
    flex:0 0 auto;
}
body.panel-shell-body .intake-root.intake-reference #wizardSteps .wizard-tab:not(:last-child)::after {
    content:'';
    display:block;
    order:2;
    flex:1 0 20px;
    height:2px;
    border-radius:2px;
    background:var(--intake-border-strong);
    pointer-events:none;
}
body.panel-shell-body .intake-root.intake-reference #wizardSteps .wizard-tab.completed-step::after {
    background:var(--staff-action, var(--intake-dark));
}
body.panel-shell-body.intake-frame .intake-steps-panel {
    position:relative;
    flex:none;
    padding-block-start:var(--intake-panel-top-gap);
}
@media(max-width:480px) {
    html[data-theme] body.panel-shell-body.intake-frame .intake-root #intakeWizardForm :is(#wizardSteps, #intakeFormContent, .footer-action-bar) {
        margin-inline-start:8px !important;
    }
}
/* Sections use dividers; fields and selectable items retain their own states. */
body.panel-shell-body .intake-root #intakeFormContent {
    --wizard-field-fill: #E0ECE4;
    --wizard-field-hover: #D3E6DA;
    --wizard-field-active-fill: #C2DFCD;
    --wizard-field-invalid: #B32636;
}
html[data-theme='dark'] body.panel-shell-body .intake-root #intakeFormContent {
    --wizard-field-fill: #253D30;
    --wizard-field-hover: #304E3D;
    --wizard-field-active-fill: #3C604A;
    --wizard-field-invalid: #FB7185;
}
body.panel-shell-body .intake-root #intakeFormContent .wizard-panel {
    border: 0 !important; border-radius: 0 !important; box-shadow: none !important;
    background: transparent !important;
    padding-top: 16px !important;
}
body.panel-shell-body .intake-root #intakeFormContent :is(.intake-section-kicker, .service-details-heading, .billing-payment-section > .billing-section-heading) { display: none !important; }
body.panel-shell-body .intake-root #intakeFormContent .wizard-panel > :first-child + :not(.hidden) { margin-top: 0; }
body.panel-shell-body .intake-root #intakeFormContent :is(.intake-field-section, .service-details-area, .schedule-stack, .service-info-stack, .package-selection-area) {
    border: 0 !important; border-radius: 0 !important; background: transparent !important;
    box-shadow: none !important;
}
body.panel-shell-body .intake-root #intakeFormContent .package-card { border: 0 !important; outline: 0 !important; box-shadow: none !important; }
body.panel-shell-body .intake-root #intakeFormContent #optional_add_ons_section { border: 0 !important; border-top: 1px solid var(--intake-border) !important; border-radius: 0; background: transparent !important; box-shadow: none !important; padding: 18px 0 0; }
body.panel-shell-body .intake-root #intakeFormContent .intake-field-section { padding: 0 !important; }
body.panel-shell-body .intake-root #intakeFormContent .intake-field-section + .intake-field-section {
    margin-top: 20px; padding-top: 20px !important; border-top: 1px solid var(--intake-border) !important;
}
body.panel-shell-body .intake-root #intakeFormContent .intake-section-kicker { margin-bottom: 14px; }
body.panel-shell-body .intake-root #intakeFormContent .section-heading-icon { background: transparent !important; border: 0 !important; box-shadow: none !important; }
body.panel-shell-body .intake-root #intakeFormContent :is(.field-label, .intake-section-kicker) { font-weight: 500; }
body.panel-shell-body .intake-root #intakeFormContent :is(.form-input, .form-textarea, .form-select, .schedule-time-display):not(.field-error, [aria-invalid='true'], #optional_add_ons_search, #amount_paid, [readonly], :disabled) {
    border: 1px solid transparent !important;
    background: var(--wizard-field-fill) !important;
    box-shadow: none !important;
}
body.panel-shell-body .intake-root #intakeFormContent :is(.form-input, .form-textarea, .form-select, .schedule-time-display):not(.field-error, [aria-invalid='true'], #optional_add_ons_search, #amount_paid, [readonly], :disabled):hover {
    background: var(--wizard-field-hover) !important;
}
body.panel-shell-body .intake-root #intakeFormContent :is(.form-input, .form-textarea, .form-select, .schedule-time-display):not(#optional_add_ons_search, #amount_paid, [readonly]):focus {
    outline: none !important;
    box-shadow: none !important;
}
body.panel-shell-body .intake-root #intakeFormContent :is(.form-input, .form-textarea, .form-select, .schedule-time-display):not(.field-error, [aria-invalid='true'], #optional_add_ons_search, #amount_paid, [readonly]):focus {
    background: var(--wizard-field-active-fill) !important;
}
body.panel-shell-body .intake-root #intakeFormContent :is(.form-input, .form-textarea, .form-select, .schedule-time-display):is(.field-error, [aria-invalid='true']):not(#amount_paid):focus {
    border-color: var(--wizard-field-invalid) !important;
}
body.panel-shell-body .intake-root #intakeFormContent .schedule-field-row { background: transparent !important; border: 0 !important; padding: 0; }
body.panel-shell-body .intake-root #intakeFormContent .wizard-divider-section {
    padding: 0 0 20px !important; margin: 0 0 20px !important; border: 0 !important; border-bottom: 1px solid var(--intake-border) !important; border-radius: 0 !important; background: transparent !important;
}
body.panel-shell-body .intake-root #billing_additional_services_section h5 { font-size: 14px; font-weight: 600; letter-spacing: normal; text-transform: none; }
body.panel-shell-body .intake-root #additional_service_items_list [data-additional-service-row] { border: 0 !important; box-shadow: none !important; }
body.panel-shell-body .intake-root #intakeFormContent .billing-workspace { margin-top: 0; gap: 24px; }
body.panel-shell-body .intake-root #intakeFormContent :is(.billing-payment-section, .billing-summary-panel) {
    border: 0 !important; border-radius: 0 !important; background: transparent !important; box-shadow: none !important; overflow: visible;
}
body.panel-shell-body .intake-root #intakeFormContent .billing-section-heading {
    padding: 0 0 12px; border-bottom: 1px solid var(--intake-border); background: transparent !important;
}
body.panel-shell-body .intake-root #intakeFormContent .billing-heading-icon { background: transparent !important; border: 0 !important; width: 20px; height: 20px; }
body.panel-shell-body .intake-root #intakeFormContent .billing-payment-stack { padding: 0; gap: 16px; }
body.panel-shell-body .intake-root #intakeFormContent .billing-draft-note { background: transparent; color: var(--intake-muted); padding: 0; border: 0; line-height: 1.6; }
body.panel-shell-body .intake-root #intakeFormContent .billing-summary { border-left: 1px solid var(--intake-border); padding-left: 24px; }
body.panel-shell-body .intake-root #intakeFormContent .billing-summary-panel > .p-5 { padding: 16px 0 0; }
body.panel-shell-body .intake-root #intakeFormContent :is(.payment-details-shell, #cashless_details_wrap) {
    border: 0 !important; border-radius: 0 !important; padding: 0 !important; background: transparent !important; box-shadow: none !important;
}
body.panel-shell-body .intake-root #intakeFormContent .payment-details-shell { border-top: 1px solid var(--intake-border) !important; padding-top: 18px !important; }
body.panel-shell-body .intake-root #intakeFormContent :is(.billing-method-choice, .bpay-tab-group) {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;
    padding: 0 !important; border: 0 !important; border-radius: 0 !important; background: transparent !important; box-shadow: none !important;
}
body.panel-shell-body .intake-root #intakeFormContent :is(.payment-method-card, .payment-type-card) {
    min-width: 0; min-height: 64px; padding: 12px !important; border: 0 !important; border-radius: 8px !important;
    background: #EAF2ED !important; color: var(--intake-text) !important; outline: 0 !important; box-shadow: none !important; transform: none !important;
    transition: background-color 150ms ease;
}
body.panel-shell-body .intake-root #intakeFormContent :is(.payment-method-card, .payment-type-card):hover { background: #DDECE2 !important; }
body.panel-shell-body .intake-root #intakeFormContent :is(.payment-method-card, .payment-type-card):has(input:checked) { background: #C4DDCE !important; }
body.panel-shell-body .intake-root #intakeFormContent :is(.payment-method-card, .payment-type-card):has(input:focus-visible) { outline: 2px solid #7FA98E !important; outline-offset: 2px; }
body.panel-shell-body .intake-root #intakeFormContent .payment-method-card:has(input[value='none']) { grid-column: 1 / -1; min-height: 42px; }
body.panel-shell-body .intake-root #intakeFormContent .payment-choice-title { font-weight: 600; }
body.panel-shell-body .intake-root #intakeFormContent .payment-type-card { min-height: 48px; padding-right: 38px !important; }
body.panel-shell-body .intake-root #intakeFormContent .payment-type-card .payment-choice-title { font-size: 14px; line-height: 1.4; }
body.panel-shell-body .intake-root #intakeFormContent .payment-choice-icon { background: transparent !important; border: 0 !important; box-shadow: none !important; }
body.panel-shell-body .intake-root #intakeFormContent :is(.payment-method-card, .payment-type-card) :is(span, i) { box-shadow: none !important; }
body.panel-shell-body .intake-root #intakeFormContent :has(> #amount_paid),
body.panel-shell-body .intake-root #additional_service_items_list [data-additional-service-row] > div:has([data-additional-amount]) { border: 1px solid transparent !important; background: var(--wizard-field-fill) !important; box-shadow: none !important; }
body.panel-shell-body .intake-root #intakeFormContent :has(> :is(#amount_paid, [data-additional-amount]):enabled:not([readonly])):hover { background: var(--wizard-field-hover) !important; }
body.panel-shell-body .intake-root #intakeFormContent :has(> #amount_paid):focus-within,
body.panel-shell-body .intake-root #additional_service_items_list [data-additional-service-row] > div:has([data-additional-amount]):focus-within { outline: none !important; background: var(--wizard-field-active-fill) !important; }
body.panel-shell-body .intake-root #intakeFormContent :has(> :is(#amount_paid, [data-additional-amount]):is(.field-error, [aria-invalid='true'])) { border-color: var(--wizard-field-invalid) !important; outline-color: var(--wizard-field-invalid) !important; }
body.panel-shell-body .intake-root #intakeFormContent :is(#amount_paid, [data-additional-amount]) { background: transparent !important; box-shadow: none !important; }
body.panel-shell-body .intake-root #intakeFormContent .billing-payment-metrics { border-top: 1px solid var(--intake-border); padding-top: 16px; gap: 0; }
body.panel-shell-body .intake-root #intakeFormContent .billing-payment-metrics > div { background: transparent !important; border: 0 !important; border-radius: 0; padding: 0 12px; color: var(--intake-text) !important; }
body.panel-shell-body .intake-root #intakeFormContent .billing-payment-metrics > div:first-child { padding-left: 0; }
body.panel-shell-body .intake-root #intakeFormContent .billing-payment-metrics > div + div { border-left: 1px solid var(--intake-border) !important; }
body.panel-shell-body .intake-root #intakeFormContent .billing-payment-metrics #payment_paid_preview { color: #286D44 !important; }
body.panel-shell-body .intake-root #intakeFormContent .billing-payment-metrics #payment_balance_preview { color: #946313 !important; }
body.panel-shell-body .intake-root #intakeFormContent .wizard-review-section { padding: 18px 0; border: 0; border-top: 1px solid var(--intake-border); background: transparent !important; box-shadow: none !important; }
body.panel-shell-body .intake-root #intakeFormContent .wizard-review-section h4 { font-size: 14px; font-weight: 600; text-transform: none; letter-spacing: normal; }
body.panel-shell-body .intake-root #intakeFormContent .wizard-review-section .review-edit { background: transparent !important; border: 0 !important; text-decoration: underline; text-underline-offset: 4px; box-shadow: none !important; }
body.panel-shell-body .intake-root #intakeFormContent .review-confirmation { border: 0 !important; background: #EAF2ED !important; box-shadow: none !important; padding: 16px; }
html[data-theme='dark'] body.panel-shell-body .intake-root #intakeFormContent :is(.payment-method-card, .payment-type-card, .review-confirmation) { background: #22392C !important; }
html[data-theme='dark'] body.panel-shell-body .intake-root #intakeFormContent :is(.payment-method-card, .payment-type-card):hover { background: #2D4938 !important; }
html[data-theme='dark'] body.panel-shell-body .intake-root #intakeFormContent :is(.payment-method-card, .payment-type-card):has(input:checked) { background: #365C43 !important; }
html[data-theme='dark'] body.panel-shell-body .intake-root #intakeFormContent #payment_paid_preview { color: #8ED0A4 !important; }
html[data-theme='dark'] body.panel-shell-body .intake-root #intakeFormContent #payment_balance_preview { color: #E3BB78 !important; }
@media (max-width: 1023px) {
    body.panel-shell-body .intake-root #intakeFormContent .billing-summary { position: static; padding: 20px 0 0; border-left: 0; border-top: 1px solid var(--intake-border); }
}
@media (max-width: 480px) {
    body.panel-shell-body .intake-root #intakeFormContent :is(.billing-method-choice, .bpay-tab-group) { grid-template-columns: minmax(0, 1fr); }
    body.panel-shell-body .intake-root #intakeFormContent .billing-payment-metrics { grid-template-columns: minmax(0, 1fr); gap: 12px; }
    body.panel-shell-body .intake-root #intakeFormContent .billing-payment-metrics > div { padding: 0 !important; border: 0 !important; }
}
@media (prefers-reduced-motion: reduce) {
    body.panel-shell-body .intake-root #intakeFormContent :is(.payment-method-card, .payment-type-card) { transition: none; }
}
/* Package selection and the selected package share the wizard's soft surfaces. */
body.panel-shell-body .intake-root #intakeFormContent #intake-step-3 {
    --package-surface: #EDF5EF;
    --package-accent-surface: #D5E8DB;
    --package-ink: #21543A;
}
html[data-theme='dark'] body.panel-shell-body .intake-root #intakeFormContent #intake-step-3 {
    --package-surface: #1B3226;
    --package-accent-surface: #294937;
    --package-ink: #C4E8D0;
}
body.panel-shell-body .intake-root #intake-step-3 .package-mode-option {
    min-height: 76px;
    padding: 12px 16px;
    border: 0;
    border-radius: 10px;
    background: var(--package-surface);
    color: var(--intake-text);
    box-shadow: none !important;
}
body.panel-shell-body .intake-root #intake-step-3 .package-mode-option:is(:hover, :focus-visible, [aria-checked='true']) {
    background: var(--package-accent-surface);
}
body.panel-shell-body .intake-root #intake-step-3 .package-mode-option__icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: var(--package-accent-surface);
    color: var(--package-ink);
}
body.panel-shell-body .intake-root #intake-step-3 .package-mode-option__arrow { color: var(--package-ink); }
body.panel-shell-body .intake-root #intake-step-3 .package-selection-area__heading { gap: 12px; }
body.panel-shell-body .intake-root #intake-step-3 .package-mode-change {
    min-height: 36px;
    padding: 6px 10px;
    border-radius: 6px;
    color: var(--package-ink);
}
body.panel-shell-body .intake-root #intake-step-3 .package-mode-change:is(:hover, :focus-visible) { background: var(--package-accent-surface); }
body.panel-shell-body .intake-root #intakeFormContent #intake-step-3 .package-card:has(.package-radio:checked) {
    border-radius: 12px !important;
    background: var(--package-surface) !important;
}
body.panel-shell-body .intake-root #intake-step-3 .inline-package-body { padding: clamp(12px, 2vw, 20px); }
body.panel-shell-body .intake-root #intake-step-3 .inline-package-header {
    align-items: center;
    padding: 14px;
    border-radius: 8px;
    background: var(--package-accent-surface);
}
body.panel-shell-body .intake-root #intake-step-3 .pkg-name { font-size: clamp(17px, 2vw, 22px); overflow-wrap: anywhere; }
body.panel-shell-body .intake-root #intake-step-3 .pkg-price {
    margin: 4px 0 0;
    color: var(--package-ink) !important;
    font-size: clamp(18px, 2.2vw, 25px);
}
body.panel-shell-body .intake-root #intake-step-3 .pkg-price sub { vertical-align: baseline; font-size: .8em; }
body.panel-shell-body .intake-root #intake-step-3 .inline-package-promotion { background: var(--package-accent-surface); }
body.panel-shell-body .intake-root #intake-step-3 .inline-package-promotion-empty {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 8px;
    background: var(--package-accent-surface);
    color: var(--package-ink);
}
body.panel-shell-body .intake-root #intake-step-3 .inline-package-promotion-empty strong { font-size: 14px; }
body.panel-shell-body .intake-root #intake-step-3 .inline-package-promotion-empty p { margin: 2px 0 0; font-size: 12px; }
body.panel-shell-body .intake-root #intake-step-3 .package-asset-slot .inline-inclusion-name { color: var(--intake-text); }
body.panel-shell-body .intake-root #intake-step-3 .package-asset-slot .inline-inclusion-status { margin-top: 4px; }
body.panel-shell-body .intake-root #intake-step-3 .package-asset-slot > .inline-package-detail { margin: 0; }
body.panel-shell-body .intake-root #intakeFormContent #custom_package_fields #custom_service_choices > .custom-package-service-card {
    background: var(--package-surface);
}
body.panel-shell-body .intake-root #intakeFormContent #custom_package_fields #custom_service_choices > .custom-package-service-card:has(.custom-service-check:checked) {
    background: var(--package-accent-surface);
}
@media (max-width: 480px) {
    body.panel-shell-body .intake-root #intake-step-3 .inline-package-header { align-items: flex-start; flex-direction: column; }
    body.panel-shell-body .intake-root #intake-step-3 .inline-package-price { white-space: normal; overflow-wrap: anywhere; }
}
body.panel-shell-body .intake-root #intakeFormContent .wizard-panel > .section-title-block {
    display: block;
    margin: 0 0 18px !important;
    padding: 0 !important;
    border: 0;
    text-align: left;
}
body.panel-shell-body .intake-root #intakeFormContent .section-title-block .section-heading-icon { display: none; }
body.panel-shell-body .intake-root #intakeFormContent .section-title-text h3 { font-size: 17px; line-height: 1.35; }
body.panel-shell-body .intake-root #intakeFormContent .section-title-text p { margin: 4px 0 0; font-size: 13px; }
html[data-theme] body.panel-shell-body .intake-root #intakeFormContent #intake-step-3 > .pkg-grid-header .pkg-section-title {
    color: var(--staff-ink, var(--intake-text)) !important;
}
body.panel-shell-body .intake-root #intakeFormContent :is(.intake-name-grid, .intake-info-grid, .intake-cashless-panel) > :not(.hidden) {
    padding-bottom: 10px;
    border-bottom: 1px solid var(--intake-border);
}
body.panel-shell-body .intake-root #intakeFormContent .schedule-stack {
    padding-bottom: 14px;
    border-bottom: 1px solid var(--intake-border) !important;
}
body.panel-shell-body .intake-root #intakeFormContent #additional_service_items_list {
    max-height: min(240px, 35dvh);
    overflow-y: auto;
    overscroll-behavior-y: contain;
    scrollbar-gutter: stable;
}
body.panel-shell-body .intake-root #intakeFormContent #additional_service_items_list:empty { margin: 0; }
body.panel-shell-body .intake-root #additional_service_items_list [data-additional-service-row] {
    border-bottom: 1px solid var(--intake-border) !important;
    border-radius: 0;
    background: transparent !important;
    padding-inline: 0;
}
body.panel-shell-body .intake-root #intakeFormContent .payment-select-wrap { position: relative; }
body.panel-shell-body .intake-root #intakeFormContent .payment-select-wrap select {
    display: block;
    width: 100%;
    appearance: none;
    background-image: none !important;
    padding-inline: 38px !important;
}
body.panel-shell-body .intake-root #intakeFormContent .payment-select-wrap :is(.payment-select-icon, .payment-select-chevron) {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    line-height: 1;
    pointer-events: none;
    z-index: 1;
}
body.panel-shell-body .intake-root #intakeFormContent .payment-select-icon { left: 12px; }
body.panel-shell-body .intake-root #intakeFormContent .payment-select-chevron { right: 12px; }
body.panel-shell-body .intake-root #optional_add_ons_panel :is(#cancel_add_ons_btn, #apply_add_ons_btn) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-width: 80px;
    min-height: 40px !important;
    padding: 8px 12px;
    border: 0 !important;
    box-shadow: none !important;
}
body.panel-shell-body .intake-root #optional_add_ons_panel #cancel_add_ons_btn {
    background: var(--intake-card) !important;
    color: var(--intake-text) !important;
}
body.panel-shell-body .intake-root #optional_add_ons_panel #cancel_add_ons_btn:hover { background: var(--intake-hover) !important; }
body.panel-shell-body .intake-root #payment_method_group:has(.payment-method-radio:checked) .payment-method-card:not(:has(.payment-method-radio:checked)) {
    display: none !important;
}
body.panel-shell-body .intake-root #payment_method_group .payment-method-card:has(.payment-method-radio:checked) {
    grid-column: 1 / -1;
}
body.panel-shell-body .intake-root #intakeFormContent .payment-method-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px 12px;
    min-height: 36px;
    margin-bottom: 6px;
}
body.panel-shell-body .intake-root #intakeFormContent .payment-method-heading .field-label { margin: 0; }
body.panel-shell-body .intake-root #change_intake_payment_method { margin: 0; text-align: right; }
@media (min-width: 481px) {
    body.panel-shell-body .intake-root #intakeFormContent #payment_method_group {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }
    body.panel-shell-body .intake-root #intakeFormContent #payment_method_group:has(input[value='cash']:checked) {
        grid-template-columns: minmax(0, 1fr) minmax(0, 0fr);
        column-gap: 0;
    }
    body.panel-shell-body .intake-root #intakeFormContent #payment_method_group:has(input[value='cashless']:checked) {
        grid-template-columns: minmax(0, 0fr) minmax(0, 1fr);
        column-gap: 0;
    }
    body.panel-shell-body .intake-root #payment_method_group .payment-method-card:has(input[value='cash']:checked) { grid-column: 1; }
    body.panel-shell-body .intake-root #payment_method_group .payment-method-card:has(input[value='cashless']:checked) { grid-column: 2; }
}
@media (prefers-reduced-motion: no-preference) {
    body.panel-shell-body .intake-root #payment_method_group {
        transition: grid-template-columns 260ms ease-in-out, column-gap 260ms ease-in-out;
    }
    body.panel-shell-body .intake-root #payment_method_group .payment-method-card,
    body.panel-shell-body .intake-root #intakeFormContent #change_intake_payment_method:not(.hidden) {
        animation: intake-payment-reveal 260ms ease-out both;
    }
    body.panel-shell-body .intake-root #intakeFormContent :is(#payment_details_section, #payment_type_section, #payment_amount_section) {
        opacity: 1;
        translate: 0 0;
        interpolate-size: allow-keywords;
        block-size: auto;
        transition: block-size 260ms ease-in-out, opacity 260ms ease-in-out, translate 260ms ease-in-out, display 260ms allow-discrete;
    }
    body.panel-shell-body .intake-root #intakeFormContent :is(#payment_details_section, #payment_type_section, #payment_amount_section).hidden {
        opacity: 0;
        translate: 0 4px;
        block-size: 0;
        overflow: clip;
        pointer-events: none;
    }
    @starting-style {
        body.panel-shell-body .intake-root #intakeFormContent :is(#payment_details_section, #payment_type_section, #payment_amount_section):not(.hidden) {
            opacity: 0;
            translate: 0 4px;
        }
    }
    body.panel-shell-body .intake-root #payment_method_group .payment-method-card:has(.payment-method-radio:checked) {
        animation: none;
    }
    @keyframes intake-payment-reveal {
        from { opacity: 0; translate: 0 4px; }
        to { opacity: 1; translate: 0 0; }
    }
}
body.panel-shell-body .intake-root #intakeFormContent .senior-eligible-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #286346;
    font-size: 14px;
    font-weight: 600;
}
html[data-theme='dark'] body.panel-shell-body .intake-root #intakeFormContent .senior-eligible-label { color: #9ED7B3; }
body.panel-shell-body .intake-root #intakeFormContent .intake-package-select {
    position: relative;
    display: block;
}
body.panel-shell-body .intake-root #intakeFormContent #regular_package_choice {
    width: 100%;
    appearance: none;
    padding-right: 40px !important;
    cursor: pointer;
}
body.panel-shell-body .intake-root #intakeFormContent .intake-package-select.is-open #regular_package_choice {
    background: var(--wizard-field-active-fill) !important;
}
body.panel-shell-body .intake-root #intakeFormContent .intake-package-select > [data-filter-select-icon] {
    position: absolute;
    right: 14px;
    top: 50%;
    translate: 0 -50%;
    color: var(--package-ink);
    pointer-events: none;
}
body.panel-shell-body .intake-root #intakeFormContent .intake-package-select.is-open > [data-filter-select-icon] { rotate: 180deg; }
body.panel-shell-body .intake-root #intakeFormContent .intake-package-select .filter-select-menu__item {
    white-space: normal;
    overflow-wrap: anywhere;
    text-align: start;
}
body.panel-shell-body .intake-root #intakeFormContent .intake-package-select .filter-select-menu__item:hover { background: var(--wizard-field-hover) !important; }
body.panel-shell-body .intake-root #intakeFormContent .intake-package-select .filter-select-menu__item[aria-selected='true'] { background: var(--wizard-field-active-fill) !important; }
@media (prefers-reduced-motion: no-preference) {
    body.panel-shell-body .intake-root #intakeFormContent .intake-package-select > [data-filter-select-icon] { transition: rotate 200ms ease-in-out; }
    body.panel-shell-body .intake-root #intakeFormContent :is(.package-mode-chooser, #regular_package_area, #custom_package_area) {
        opacity: 1;
        translate: 0 0;
        interpolate-size: allow-keywords;
        block-size: auto;
        transition: block-size 260ms ease-in-out, opacity 260ms ease-in-out, translate 260ms ease-in-out, display 260ms allow-discrete;
    }
    body.panel-shell-body .intake-root #intakeFormContent :is(.package-mode-chooser, #regular_package_area, #custom_package_area).hidden {
        opacity: 0;
        translate: 0 4px;
        block-size: 0;
        overflow: clip;
        pointer-events: none;
    }
    @starting-style {
        body.panel-shell-body .intake-root #intakeFormContent :is(.package-mode-chooser, #regular_package_area, #custom_package_area):not(.hidden) {
            opacity: 0;
            translate: 0 4px;
        }
    }
    body.panel-shell-body .intake-root #intakeFormContent #packageCardList .package-card-item:has(.package-radio:checked) {
        animation: intake-payment-reveal 260ms ease-out both;
    }
}
</style>
