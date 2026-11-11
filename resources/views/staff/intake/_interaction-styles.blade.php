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
.intake-root:has(#intakeCancelModal[open]) .intake-section-shell {
    opacity: 0;
}
html[data-theme] body.panel-shell-body .intake-root #intakeCancelModal[open] .cancel-modal-actions {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
html[data-theme] body.panel-shell-body .intake-root #intakeCancelModal[open] #intakeCancelModalSaveDraft {
    grid-column: 1 / -1;
    background: #367555 !important;
    border-color: transparent !important;
    color: #fff !important;
}
html[data-theme] body.panel-shell-body .intake-root #intakeCancelModal[open] #intakeCancelModalSaveDraft:hover {
    background: #2d6348 !important;
}
html[data-theme] body.panel-shell-body .intake-root #intakeCancelModal[open] #intakeCancelModalKeep {
    background: var(--intake-card-strong) !important;
    border-color: transparent !important;
    color: var(--intake-text) !important;
}
html[data-theme] body.panel-shell-body .intake-root #intakeCancelModal[open] #intakeCancelModalKeep:hover {
    background: var(--intake-hover) !important;
}
html[data-theme] body.panel-shell-body .intake-root #intakeCancelModal[open] #intakeCancelModalDiscard {
    --discard-bg: #f8e5e4;
    --discard-hover: #f1d3d1;
    --discard-text: #8a3537;
    background: var(--discard-bg) !important;
    border-color: transparent !important;
    color: var(--discard-text) !important;
    outline: none !important;
}
html[data-theme] body.panel-shell-body .intake-root #intakeCancelModal[open] #intakeCancelModalDiscard:focus-visible {
    text-decoration: underline;
    text-underline-offset: 3px;
}
html[data-theme='dark'] body.panel-shell-body .intake-root #intakeCancelModal[open] #intakeCancelModalDiscard {
    --discard-bg: #42282d;
    --discard-hover: #563138;
    --discard-text: #fecaca;
}
html[data-theme] body.panel-shell-body .intake-root #intakeCancelModal[open] #intakeCancelModalDiscard:hover {
    background: var(--discard-hover) !important;
}
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
body.panel-shell-body .intake-root #custom_service_choices label:has(> .custom-service-check) { min-height: 32px; }
body.panel-shell-body .intake-root #custom_service_choices label:has(> .custom-service-check):focus-within {
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
body.panel-shell-body .intake-root #custom_package_fields.custom-package-builder {
    padding: 0;
    margin-top: 16px;
    border: 0;
    background: transparent;
}
body.panel-shell-body .intake-root #custom_package_fields .custom-package-section {
    padding-top: 16px;
    border-top: 1px solid var(--intake-border);
}
body.panel-shell-body .intake-root #custom_package_fields #custom_service_choices {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 18rem), 1fr));
    gap: 10px;
    align-items: start;
}
body.panel-shell-body .intake-root #custom_package_fields #custom_service_choices > .custom-package-service-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 10px;
    padding: 12px;
    border: 0;
    border-radius: 8px;
    background: #E0ECE4;
}
html[data-theme='dark'] body.panel-shell-body .intake-root #custom_package_fields #custom_service_choices > .custom-package-service-card {
    background: #1C3428;
}
body.panel-shell-body .intake-root #custom_package_fields #custom_service_choices .custom-service-dependent {
    text-align: start;
    min-width: 0;
}
body.panel-shell-body .intake-root #custom_package_fields .custom-inclusion-inline .custom-inclusion-help {
    margin: 0 0 8px;
    color: var(--intake-muted);
    font-size: 12px;
    line-height: 1.5;
}
body.panel-shell-body .intake-root #custom_package_fields .custom-package-freebie-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr));
    gap: 8px;
    max-height: 14rem;
    overflow-y: auto;
    overscroll-behavior: contain;
}
body.panel-shell-body .intake-root #custom_package_fields .custom-package-freebie {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 8px;
    background: var(--intake-card-alt);
    color: var(--intake-text);
    font-size: 13px;
    cursor: pointer;
}
body.panel-shell-body .intake-root #custom_package_fields .custom-package-freebie:has(:checked) {
    background: var(--intake-card-strong);
}
body.panel-shell-body .intake-root #custom_package_fields .custom-package-freebie:focus-within {
    outline: 1px solid var(--record-field-focus);
    outline-offset: -1px;
}
body.panel-shell-body .intake-root #custom_package_fields .custom-package-note {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid var(--intake-border);
    color: var(--intake-muted);
    font-size: 12px;
    line-height: 1.5;
}
/* Package inclusions share a balanced card rhythm in both package modes. */
body.panel-shell-body .intake-root :is(.inline-package-items, #custom_package_fields #custom_service_choices) {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    align-items: stretch;
}
body.panel-shell-body .intake-root .inline-package-section h5 { margin-bottom: 12px; }
body.panel-shell-body .intake-root .package-service-summary {
    padding: 12px;
    border-radius: 8px;
    background: #E0ECE4;
    gap: 10px;
    min-width: 0;
}
html[data-theme='dark'] body.panel-shell-body .intake-root .package-service-summary { background: #1C3428; }
body.panel-shell-body .intake-root #custom_package_fields #custom_service_choices > .custom-package-service-card {
    gap: 12px;
    align-content: start;
}
body.panel-shell-body .intake-root .package-service-summary .inline-package-detail { margin-top: 4px; line-height: 1.6; }
body.panel-shell-body .intake-root #custom_package_fields .custom-inclusion-inline .field-label { margin-bottom: 8px; }
body.panel-shell-body .intake-root #custom_package_fields #custom_service_choices .custom-service-dependent { margin: 0; }
body.panel-shell-body .intake-root #optional_add_ons_panel:not(.hidden) {
    padding: 0 !important;
    border: 0 !important;
    max-height: min(760px, calc(100dvh - 32px)) !important;
    box-shadow: none !important;
}
body.panel-shell-body .intake-root #optional_add_ons_panel > div:first-child,
body.panel-shell-body .intake-root #optional_add_ons_panel > div:last-child { padding: 20px !important; }
body.panel-shell-body .intake-root #optional_add_ons_panel .service-tool-title { font-size: 17px; }
body.panel-shell-body .intake-root #optional_add_ons_panel .service-tool-help { margin-top: 6px; font-weight: 400; }
body.panel-shell-body .intake-root #optional_add_ons_list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-content: start;
    align-items: start;
    gap: 12px;
    padding: 20px !important;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
}
body.panel-shell-body .intake-root #optional_add_ons_list > * { margin: 0; }
body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: start;
    gap: 8px 10px;
    padding: 10px 12px;
    border: 0;
    border-radius: 8px;
    transition: background-color 160ms ease;
}
body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row:has(:checked) { background: var(--intake-card-strong) !important; }
body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row > input[hidden] { display: none !important; }
body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row > button { justify-self: end; min-height: 36px; }
body.panel-shell-body .intake-root #optional_add_ons_list .addon-card-heading { display: grid; gap: 3px; line-height: 1.4; }
body.panel-shell-body .intake-root #optional_add_ons_list .addon-card-copy { min-width: 0; overflow-wrap: anywhere; }
body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row .font-black { font-weight: 600; color: var(--intake-text); }
body.panel-shell-body .intake-root #optional_add_ons_list .add-on-quantity-area:not(.hidden) {
    display: grid;
    grid-column: 1 / -1;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 125px), 1fr));
    align-items: center;
    gap: 8px 12px;
    margin-top: 0;
    padding-top: 8px;
    border-top: 1px solid var(--intake-border);
}
body.panel-shell-body .intake-root .add-on-quantity-area label { grid-column: 1 / -1; font-size: 12px; color: var(--intake-text); }
body.panel-shell-body .intake-root .add-on-quantity-area label span { color: var(--intake-muted); }
body.panel-shell-body .intake-root .add-on-quantity-area .inline-quantity { display: inline-flex; gap: 4px; align-items: center; }
body.panel-shell-body .intake-root .add-on-quantity-area .inline-quantity :is(button,input) { min-height: 36px; height: 36px; margin: 0; }
body.panel-shell-body .intake-root .add-on-quantity-area .inline-quantity input { width: 54px; text-align: center; padding: 4px; }
body.panel-shell-body .intake-root .add-on-line-total { display: grid; gap: 4px; font-size: 11px; color: var(--intake-muted); text-align: end; }
body.panel-shell-body .intake-root .add-on-line-total strong { font-size: 13px; color: var(--intake-text); }
body.panel-shell-body .intake-root .add-ons-draft-summary { display: grid; gap: 6px; font-size: 12px; color: var(--intake-muted); }
body.panel-shell-body .intake-root .add-ons-draft-summary strong { color: var(--intake-text); font-size: 14px; }
@media (max-width: 640px) {
    body.panel-shell-body .intake-root :is(.inline-package-items, #custom_package_fields #custom_service_choices, #optional_add_ons_list) { grid-template-columns: minmax(0, 1fr); }
    body.panel-shell-body .intake-root #optional_add_ons_panel > div:last-child { gap: 12px; }
}
body.panel-shell-body .intake-root #optional_add_ons_list { max-height: 48dvh !important; flex: 1 1 auto; }
body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row { cursor: pointer; overflow-wrap: anywhere; }
body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row.hidden,
body.panel-shell-body .intake-root #optional_add_ons_list [hidden] { display: none !important; }
body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row:focus-visible { outline: 2px solid var(--intake-border-strong); outline-offset: -2px; }
body.panel-shell-body .intake-root #optional_add_ons_list [data-optional-add-on-toggle] {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    display: inline-flex; align-items: center; gap: 6px;
    background: #EAF2ED; color: #286346; border: 0;
}
body.panel-shell-body .intake-root #optional_add_ons_list [data-optional-add-on-toggle][aria-pressed='true'] { background: #A9C9B5; color: #123524; }
body.panel-shell-body .intake-root #optional_add_ons_list [data-optional-add-on-toggle]:hover { background: #92B89F; color: #123524; }
body.panel-shell-body .intake-root #draft_add_ons_header_count { font-size: 12px; font-weight: 500; color: var(--intake-muted); margin-left: 8px; white-space: nowrap; }
body.panel-shell-body .intake-root #optional_add_ons_panel #apply_add_ons_btn { background: #A9C9B5 !important; color: #123524 !important; min-width: 88px; }
body.panel-shell-body .intake-root #optional_add_ons_panel #apply_add_ons_btn:hover { background: #92B89F !important; }
body.panel-shell-body .intake-root #optional_add_ons_panel > div:first-child { position: relative; z-index: 2; }
body.panel-shell-body .intake-root .intake-addon-search {
    position: relative; min-width: 0; height: 48px; border: 1px solid transparent;
    border-radius: 14px; background: #EAF2ED; color: var(--intake-text);
}
body.panel-shell-body .intake-root .intake-addon-search:hover { background: #E4EFE8; }
body.panel-shell-body .intake-root .intake-addon-search:focus-within { background: #DCECE2; }
body.panel-shell-body .intake-root .intake-addon-search > .bi { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); }
body.panel-shell-body .intake-root .intake-addon-search #optional_add_ons_search {
    width: 100%; height: 46px; min-height: 46px !important; padding: 0 128px 0 40px !important;
    background: transparent !important; border: 0 !important; outline: 0 !important; box-shadow: none !important;
    font-size: 14px; font-weight: 400;
}
body.panel-shell-body .intake-root #optional_add_ons_search::-webkit-search-cancel-button { display: none; }
body.panel-shell-body .intake-root .intake-addon-search > button { position: absolute; top: 0; height: 46px; border: 0; }
body.panel-shell-body .intake-root #addon_search_submit { right: 0; width: 84px; background: #A9C9B5; color: #123524; border-radius: 0 13px 13px 0; font-size: 14px; font-weight: 500; }
body.panel-shell-body .intake-root #addon_search_submit:hover { background: #92B89F; }
body.panel-shell-body .intake-root #addon_search_clear { right: 92px; width: 28px; padding: 0; background: transparent; color: var(--intake-text); }
body.panel-shell-body .intake-root #addon_search_clear[hidden],
body.panel-shell-body .intake-root #addon_search_matches[hidden] { display: none !important; }
body.panel-shell-body .intake-root #addon_search_matches {
    position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 5; max-height: 240px;
    overflow-y: auto; padding: 6px; border-radius: 10px; background: var(--intake-card-strong);
}
body.panel-shell-body .intake-root #addon_search_matches button { display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px; border: 0; background: transparent; color: var(--intake-text); text-align: start; border-radius: 6px; }
body.panel-shell-body .intake-root #addon_search_matches button:is(:hover,[aria-selected='true']) { background: var(--intake-hover); }
body.panel-shell-body .intake-root #addon_search_matches button span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; }
body.panel-shell-body .intake-root #addon_search_matches button span:first-child { font-weight: 600; max-width: 65%; font-size: 14px; }
body.panel-shell-body .intake-root #addon_search_matches mark { color: inherit; background: transparent; text-decoration: underline; text-underline-offset: 3px; }
body.panel-shell-body .intake-root #addon_search_empty { grid-column: 1 / -1; color: var(--intake-muted); font-size: 13px; padding: 12px; }
html[data-theme='dark'] body.panel-shell-body .intake-root .intake-addon-search { background: #22392C; }
html[data-theme='dark'] body.panel-shell-body .intake-root .intake-addon-search:hover { background: #284434; }
html[data-theme='dark'] body.panel-shell-body .intake-root .intake-addon-search:focus-within { background: #2E4A3B; }
html[data-theme='dark'] body.panel-shell-body .intake-root #optional_add_ons_list [data-optional-add-on-toggle]:not([aria-pressed='true']) { background: #294A39; color: #BEE4CB; }
@media (prefers-reduced-motion: reduce) {
    body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row { transition: none; }
}
body.panel-shell-body .intake-root :is(#custom_casket_catalog_id, #hearse_catalog_id)[hidden] { display: none !important; }
body.panel-shell-body .intake-root .custom-asset-trigger { padding: 0; border: 0; background: transparent; color: var(--intake-text); text-align: start; text-decoration: underline; text-underline-offset: 4px; font-size: 13px; line-height: 1.6; }
body.panel-shell-body .intake-root .custom-asset-trigger:hover { color: #367555; }
body.panel-shell-body .intake-root .inline-upgrade-options:popover-open { display: flex; flex-direction: column; gap: 8px; overflow: hidden; border: 0; box-shadow: none; }
body.panel-shell-body .intake-root .intake-asset-options { overflow-y: auto; max-height: 240px; min-height: 0; overscroll-behavior: contain; }
body.panel-shell-body .intake-root .inline-upgrade-options [hidden] { display: none !important; }
body.panel-shell-body .intake-root .intake-asset-search { position: relative; min-height: 44px; flex-shrink: 0; border-radius: 14px; background: #EAF2ED; }
body.panel-shell-body .intake-root .intake-asset-search:focus-within { outline: 1px solid #7FA98E; outline-offset: -1px; }
body.panel-shell-body .intake-root .intake-asset-search input { width: 100%; height: 44px; padding: 0 124px 0 12px; border: 0; outline: 0; background: transparent; font-size: 14px; color: var(--intake-text); }
body.panel-shell-body .intake-root .intake-asset-search input::-webkit-search-cancel-button { display: none; }
body.panel-shell-body .intake-root .intake-asset-search button { position: absolute; top: 0; right: 0; height: 44px; width: 84px; background: #A9C9B5; color: #123524; border: 0; border-radius: 0 14px 14px 0; text-align: center; }
body.panel-shell-body .intake-root .intake-asset-search button:first-of-type { right: 92px; width: 24px; background: transparent; color: var(--intake-text); border-radius: 0; }
html[data-theme='dark'] body.panel-shell-body .intake-root .intake-asset-search { background: #22392C; }
body.panel-shell-body .intake-root .package-asset-slot {
    display: flex; flex-direction: column; gap: 10px; min-width: 0; padding: 12px;
    background: #E0ECE4; border-radius: 8px;
}
body.panel-shell-body .intake-root .package-asset-slot :is(.inline-inclusion-card, #regular-hearse-inclusion) {
    padding: 0; border: 0; background: transparent; width: 100%; gap: 12px;
}
body.panel-shell-body .intake-root .package-asset-slot .inline-inclusion-main { min-width: 0; }
body.panel-shell-body .intake-root .package-asset-slot .inline-inclusion-status { display: block; margin-top: 6px; }
body.panel-shell-body .intake-root .package-asset-slot .inline-inclusion-actions {
    display: grid;
    justify-items: end;
    align-content: center;
    gap: 2px;
    text-align: right;
}
body.panel-shell-body .intake-root .package-asset-slot > .inline-package-detail { font-size: 12px; line-height: 1.6; }
body.panel-shell-body .intake-root .package-asset-slot > .inline-package-detail:empty { display: none; }
body.panel-shell-body .intake-root .included-quantity-card { gap: 12px; padding: 12px; }
body.panel-shell-body .intake-root .included-quantity-card [data-extra-charge] { font-size: 12px; line-height: 1.5; color: var(--intake-muted); }
html[data-theme='dark'] body.panel-shell-body .intake-root .package-asset-slot { background: #1C3428; }
html[data-theme='dark'] body.panel-shell-body .intake-root .package-asset-slot #regular-hearse-inclusion { background: transparent; }
/* Add-ons is an anchored, non-modal picker; its own list owns scrolling. */
html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_panel[popover] {
    position: fixed !important;
    inset: auto !important;
    transform: none !important;
    margin: 0 !important;
    width: min(640px, calc(100vw - 16px)) !important;
    max-height: min(560px, calc(100dvh - 16px)) !important;
    border: 0 !important;
    border-radius: 12px !important;
    --addon-popover-surface:#eaf2ed;
    background: var(--addon-popover-surface) !important;
    box-shadow: var(--service-popover-shadow, 0 12px 34px rgb(18 55 38 / 25%)) !important;
    opacity: 0;
    translate: 0 6px;
}
html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_panel[popover]:not(:popover-open) { display: none !important; pointer-events: none; }
html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_panel[popover]:popover-open { display: flex !important; flex-direction: column; opacity: 1; translate: 0 0; }
body.panel-shell-body .intake-root #optional_add_ons_panel::backdrop { background: transparent !important; }
html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_panel > div:first-child,
html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_panel > div:last-child { padding: 12px 16px !important; background: var(--addon-popover-surface) !important; flex: 0 0 auto; }
html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_list { max-height: 280px !important; padding: 12px !important; flex: 1 1 auto; min-height: 0; background: var(--addon-popover-surface) !important; }
html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row { background: #F7FAF8 !important; }
html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row:hover { background: #EAF2ED !important; }
html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row:has(:checked) { background: #B6D2C0 !important; }
body.panel-shell-body .intake-root #optional_add_ons_panel .service-tool-title { font-size: 15px; }
body.panel-shell-body .intake-root #optional_add_ons_panel .service-tool-help { font-size: 12px; }
html[data-theme='dark'] body.panel-shell-body .intake-root #optional_add_ons_panel[popover] { --addon-popover-surface:#20392c; }
html[data-theme='dark'] body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row { background: #14261D !important; }
html[data-theme='dark'] body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row:hover { background: #1D3527 !important; }
html[data-theme='dark'] body.panel-shell-body .intake-root #optional_add_ons_list .optional-add-on-row:has(:checked) { background: #365C43 !important; }
html[data-theme='dark'] body.panel-shell-body .intake-root #optional_add_ons_panel :is(.service-tool-title, .add-ons-draft-summary strong, .intake-addon-search, #optional_add_ons_search, .add-on-quantity-area label, .add-on-line-total strong) { color: #EAF2ED !important; }
html[data-theme='dark'] body.panel-shell-body .intake-root #optional_add_ons_panel :is(.service-tool-help, #draft_add_ons_header_count, .add-ons-draft-summary, #draft_add_ons_count, .optional-add-on-row .text-xs, .add-on-line-total, .add-on-quantity-area label span) { color: #C0D5C8 !important; }
html[data-theme='dark'] body.panel-shell-body .intake-root #optional_add_ons_search::placeholder { color: #AAC5B4 !important; }
body.panel-shell-body .intake-root [data-upgrade-charge][hidden] { display: none !important; }
body.panel-shell-body .intake-root .inline-upgrade-button,
body.panel-shell-body .intake-root .custom-asset-trigger {
    min-height: 32px; padding: 4px 0; border: 0; border-radius: 0;
    background: transparent; color: var(--intake-text); font-size: 13px; font-weight: 500;
    text-decoration: underline; text-underline-offset: 4px; cursor: pointer; overflow-wrap: anywhere;
}
body.panel-shell-body .intake-root .inline-upgrade-button:hover { background: transparent; color: #367555; }
html[data-theme='dark'] body.panel-shell-body .intake-root :is(.inline-upgrade-button, .custom-asset-trigger):hover { color: #A9C9B5; }
body.panel-shell-body .intake-root .package-asset-slot { padding: 14px; gap: 8px; justify-content: center; }
body.panel-shell-body .intake-root .package-asset-slot :is([data-service-control='casket'], #regular-hearse-inclusion):not(.hidden) {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
}
body.panel-shell-body .intake-root #custom_package_fields #custom_service_choices { align-items: start; }
body.panel-shell-body .intake-root #custom_package_fields #custom_service_choices > .custom-package-service-card {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
    align-items: center; align-content: center; gap: 12px;
    min-height: 64px; padding: 12px 14px;
}
body.panel-shell-body .intake-root #custom_package_fields #custom_service_choices .custom-service-dependent { text-align: end; }
body.panel-shell-body .intake-root #custom_service_choices .custom-asset-trigger { text-align: end; }
body.panel-shell-body .intake-root .custom-defaults-notice { margin: 0 0 12px; color: #8B651F; font-size: 12px; line-height: 1.5; }
html[data-theme='dark'] body.panel-shell-body .intake-root .custom-defaults-notice { color: #D9BB7E; }
@media (max-width: 640px) {
    body.panel-shell-body .intake-root #custom_package_fields #custom_service_choices > .custom-package-service-card { grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr); }
}
@media (prefers-reduced-motion: no-preference) {
    html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_panel[popover] {
        transition: opacity 120ms ease-out, translate 120ms ease-out, display 120ms allow-discrete, overlay 120ms allow-discrete;
    }
    @starting-style {
        html[data-theme] body.panel-shell-body .intake-root #optional_add_ons_panel[popover]:popover-open { opacity: 0; translate: 0 6px; }
    }
}
body.panel-shell-body .intake-root #selected_add_ons_lines .intake-applied-add-on > span {
    flex: 1 1 12rem;
    overflow-wrap: anywhere;
}
body.panel-shell-body .intake-root #selected_add_ons_lines .intake-applied-add-on-actions {
    max-width: 100%;
    gap: 6px 10px;
}
body.panel-shell-body .intake-root #selected_add_ons_lines .intake-applied-add-on-actions label { flex-wrap: wrap; }
body.panel-shell-body .intake-root #selected_add_ons_lines [data-remove-applied-add-on] { min-height: 36px; }
</style>
