<style>
/* Intake overlays use the viewport, not the panel's animated containing block. */
body.panel-shell-body:has(.intake-root) .page-content {
    transform: none !important;
    will-change: auto;
}
html[data-theme] body.panel-shell-body[data-intake-modal-open='true'] :is(.main-area, .page-content) {
    overflow: hidden !important;
}
html[data-theme] body.panel-shell-body[data-intake-modal-open='true'] .panel-floating-menu-btn { visibility: hidden; }
body.panel-shell-body .intake-root :is(#package_details_modal, #intakeCancelModal) {
    z-index: 1200;
    border-radius: 0;
    margin: 0;
    width: 100vw;
    height: 100dvh;
    max-width: none;
    max-height: none;
    border: 0;
    background: transparent !important;
}
body.panel-shell-body .intake-root dialog::backdrop { background: var(--modal-scrim); }
body.panel-shell-body .intake-root dialog:not([open]) { display: none !important; }
/* Native backdrops own the scrim; dialog roots handle outside clicks. */
html[data-theme] body.panel-shell-body .intake-root :is(#package_details_modal > div[data-package-details-close], #intakeCancelModalBackdrop) { display: none !important; }
html[data-theme] body.panel-shell-body .intake-root #package_details_modal > .relative > div:last-child {
    background: var(--intake-card-strong) !important;
}
body.panel-shell-body .intake-root .intake-modal-backdrop { z-index: 1200; }
body.panel-shell-body .intake-root :is(#package_adjustments_panel, #optional_add_ons_panel):not(.hidden) {
    z-index: 1201 !important;
    max-height: calc(100dvh - 32px) !important;
}
body.panel-shell-body .intake-root #package_details_modal > .relative {
    display: flex;
    flex-direction: column;
    max-height: calc(100dvh - 32px);
}
body.panel-shell-body .intake-root #package_details_modal > .relative > div { flex-shrink: 0; }
body.panel-shell-body .intake-root #package_details_modal > .relative > .overflow-y-auto {
    flex: 1 1 auto;
    min-height: 0;
}
/* The card's grid only applies when the existing control is actually visible. */
body.panel-shell-body .intake-root .service-tool-card.hidden { display: none; }
body.panel-shell-body .intake-root #choose_add_ons_btn[hidden] { display: none !important; }
body.panel-shell-body .intake-root #custom_service_choices { grid-template-columns: minmax(0, 1fr); }
body.panel-shell-body .intake-root #custom_service_choices > div {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    align-items: center;
    gap: 12px;
    border: 0;
    border-radius: 0;
    padding: 8px 0;
}
body.panel-shell-body .intake-root #custom_service_choices .custom-service-dependent {
    min-width: 0;
    margin-top: 0;
    text-align: right;
}
@media (min-width: 640px) {
    body.panel-shell-body .intake-root #custom_service_choices > div {
        grid-template-columns: minmax(160px, 1fr) minmax(0, 2fr);
    }
}
body.panel-shell-body .intake-root .custom-inclusion-chip {
    display: inline-flex;
    flex-wrap: wrap;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--intake-text);
    font-size: 13px;
}
body.panel-shell-body .intake-root .custom-inclusion-summary {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: flex-end;
    flex-wrap: wrap;
    min-height: 44px;
    list-style: none;
}
body.panel-shell-body .intake-root .custom-inclusion-edit-label { font-size: 12px; text-decoration: underline; color: var(--intake-text); background: transparent; border: 0; padding: 8px 0; min-height: 44px; }
html[data-theme] body.panel-shell-body .intake-root .custom-inclusion-dialog {
    width: min(420px, calc(100vw - 32px));
    max-height: calc(100dvh - 32px);
    margin: auto;
    padding: 20px;
    border: 1px solid var(--intake-border-strong);
    border-radius: 12px;
    background: var(--intake-card);
    color: var(--intake-text);
    text-align: left;
    overflow-y: auto;
}
body.panel-shell-body .intake-root .custom-inclusion-dialog-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
body.panel-shell-body .intake-root .custom-inclusion-dialog-heading h4 { font-size: 16px; font-weight: 700; }
body.panel-shell-body .intake-root .custom-inclusion-close { width: 36px; height: 36px; border: 0; background: transparent; color: var(--intake-text); font-size: 24px; }
html[data-theme] body.panel-shell-body .intake-root .custom-inclusion-dialog .custom-inclusion-help { color: var(--intake-muted) !important; font-size: 13px; margin: 8px 0 16px; }
@media (max-width: 639px) {
    body.panel-shell-body .intake-root #custom_service_choices > div { grid-template-columns: minmax(0, 1fr); gap: 0; }
    body.panel-shell-body .intake-root .custom-inclusion-summary { flex-wrap: nowrap; }
    body.panel-shell-body .intake-root .custom-inclusion-edit-label { flex-shrink: 0; }
}
body.panel-shell-body .intake-root .custom-inclusion-done { margin-top: 8px; min-height: 36px; padding: 6px 14px; border-radius: 8px; background: var(--intake-dark); color: white; }
html[data-theme] body.panel-shell-body .intake-root #custom_service_choices > div {
    background: transparent;
    border: 0;
}
body.panel-shell-body .intake-root :is(#custom_service_choices, #custom_package_fields, #package_adjustments_panel, #optional_add_ons_panel) input[type='checkbox'] {
    appearance: auto !important;
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    min-height: 18px !important;
    padding: 0 !important;
    border-radius: 4px !important;
    flex: 0 0 18px;
    accent-color: var(--intake-dark);
}
body.panel-shell-body .intake-root #custom_service_choices label { min-height: 32px; }
body.panel-shell-body .intake-root #custom_service_choices label:focus-within {
    outline: 2px solid var(--intake-border-strong);
    outline-offset: 3px;
}
body.panel-shell-body .intake-root .package-detail-tab {
    color: var(--intake-text) !important;
    background: var(--intake-card) !important;
    min-height: 40px;
}
body.panel-shell-body .intake-root .package-detail-tab[aria-selected='true'] {
    background: var(--intake-active) !important;
    color: var(--intake-text) !important;
    box-shadow: inset 0 -2px var(--intake-border-strong) !important;
}
body.panel-shell-body .intake-root :is(#open_package_adjustments_btn, #done_package_adjustments_btn, #apply_add_ons_btn, #package_details_modal button[data-package-details-close]:not([aria-label])) {
    background: var(--intake-dark) !important;
    color: #fff !important;
}
body.panel-shell-body .intake-root #package_adjustments_panel .package-adjustments-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 36px;
    align-items: start;
}
body.panel-shell-body .intake-root :is(#done_package_adjustments_btn, #apply_add_ons_btn) { min-height: 44px; }
body.panel-shell-body .intake-root #package_details_modal button[aria-label='Close package details'] {
    width: 36px;
    min-width: 36px;
    height: 36px;
    flex-shrink: 0;
}
body.panel-shell-body .intake-root #selected_add_ons_summary {
    background: var(--intake-card-strong) !important;
    color: var(--intake-text);
}
body.panel-shell-body .intake-root #selected_add_ons_summary .text-xs { color: var(--intake-muted) !important; }
body.panel-shell-body .intake-root [id$='picker_trigger'] {
    width: 32px;
    min-height: 32px;
    padding: 0;
    background: transparent;
    border: 0;
}
@media (max-width: 767px) {
    body.panel-shell-body .intake-root .service-tool-card:not(.hidden) { grid-template-columns: minmax(0, 1fr); }
    body.panel-shell-body .intake-root #open_package_adjustments_btn { justify-content: center; min-height: 44px; }
}
</style>
