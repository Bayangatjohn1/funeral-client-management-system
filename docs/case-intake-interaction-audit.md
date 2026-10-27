# Case Intake interaction audit — 2026-10-13

User workflow: test first, report findings before implementation. No application files were edited in this audit. Staff UI Reference remains the baseline. Screenshots supplied by the user identify the custom builder and adjustment/add-on sections; those areas were exercised in the actual browser.

## Coverage and evidence

| Area | Tested / inspected | Result |
| --- | --- | --- |
| Client | Required-field errors, valid fictional client entries, next step | Progression works; existing validation verified in earlier batch too |
| Deceased | Distinct names, typed birth/death dates, computed age, progression | Full-month date format works; ISO and slash formats were cleared with duplicate required errors |
| Service packages | Standard/custom switching; custom name, price; all five service disclosures; coverage and casket validation | Several findings below |
| Package details | Standard/custom content, services/freebies/limits tabs, empty freebies, close, Escape, Shift+Tab | Missing focus containment/return; custom limits/price presentation misleading |
| Adjustments | Open before package selection, standard/custom states, missing configuration warnings, replacement selector, Escape, close, keyboard | Viewport positioning, scrim and focus failures |
| Add-ons | No-available-add-ons state and disabled opener; source of search, manual charges and Apply/Cancel | Empty state reachable; populated dialog cannot be exercised with current catalog |
| Schedule | All four typed dates/times; wake duration; time picker hour validation, quick minutes and Apply | Valid scheduling works; date hints and keyboard/calendar access need improvement |
| Billing | Missing payment choice, Cash, Partial Payment, Cashless disclosure, No Payment, summary, Review | Can proceed with No Payment; summary has misleading currency prefix on status; several labels unassociated |
| Review | Client/deceased/package/service/billing/payment content; Edit Package return; required confirmation | Data retained; edit navigation works; no valid record submitted |
| Leave dialog | Open, Keep Editing, Shift+Tab, scroll lock | Main scroll locks; focus escapes; close loses focus; navigation button remains above scrim |
| Recovery | Existing local autosave notice remains through wizard; source restoration examined | Needs recovery notice lifecycle review |
| Other Branch | Direct route with signed-in Staff; controller/view source | HTTP 403 correctly says only System Admin can record other-branch reports; privileged flow not exercised |
| Save/draft/payment success/print | Existing feature tests and source inspected | Browser writes not performed against live database. Previous isolated Dusk tests redirected to login because loginAs lacks account security session state. Post-save visual and print flow remain unverified |

Browser checks: five-step main flow in light mode at initial 476px; custom and standard package states and overlays in dark mode at 1440×900; adjustment overlay at 390×844 light. Earlier batch also inspected initial form at 390px/1440px both themes. This is not a claim of every possible state at every breakpoint.

Fictional sample entries only; no official case, payment or server draft created. Local unsaved review data remains in the browser for inspection. Theme restored to light; temporary viewport reset.

Existing feature tests rerun: IntakeDraftWorkflowTest + IntakeStructuredPricingTest — 23 passed, 254 assertions, in-memory SQLite.

## Confirmed issues and proposed fixes

1. **High: custom mode exposes irrelevant automatic charges.** Both structured_pricing_section and optional_add_ons_section contain hidden but compute to display:grid. Users can open adjustments while the custom explanatory text says automatic adjustments do not apply. Fix the existing CSS visibility conflict; preserve existing business rules.
2. **High: manual charges cannot be reached in the empty catalog/custom flow.** billing_additional_services_section is nested inside optional_add_ons_panel. The opener is disabled when no catalog add-ons exist, and custom mode is supposed to hide this section. Give the existing Manual Charges UI an independent reachable presentation, preferably in Billing, retaining input names, calculations and actions. This is a presentation/workflow recommendation to review before moving it.
3. **High: fixed overlays are positioned relative to page content.** Adjustments extends beyond the mobile viewport; its footer is offscreen when opened. Computed ancestor page-content has transform:matrix(1,0,0,1,0,0) and will-change:opacity,transform. These create a fixed-position containing block. Scrim coordinates follow the tall page instead of the viewport. Host overlays outside that ancestor and bound dialog body scrolling; keep header/footer available.
4. **High: modal keyboard and background isolation fails.** Shift+Tab escapes package details, adjustments and leave dialogs. Package details and Keep Editing close return focus to BODY. Service dialogs set body overflow:hidden while main-area still has overflow-y:auto. Sidebar remains undimmed on desktop; floating navigation remains above scrim on mobile. Use one consistent dialog lifecycle for focus containment, return, inert background and actual scroll-container locking.
5. **High: poor dark selected-tab contrast.** Package details selected INCLUDED SERVICES computes text rgb(24,37,31) on rgb(17,34,24), making it almost invisible. Selected/hover/focus states must use Staff theme tokens with readable contrast.
6. **Medium: custom checkbox styling looks like elongated pills.** Checkbox dimensions compute to approximately 25.6×42px, despite h-4/w-4 classes. Checked and unchecked service options are hard to distinguish, particularly dark mode. Normalize checkbox styling independently of text/number input height, strengthen selected-card indication, and avoid stretching the adjacent unselected card to the selected card's expanded height.
7. **Medium: custom validation does not identify the field.** Blank included km/days fields say Please enter 0.; blank casket says Please select this field. Replace placeholder-derived names with meaningful service/unit labels, associate errors and include them in the summary. Preserve all validation rules.
8. **Medium: custom details don't present the current agreement clearly.** After entering PHP 50,000 and Embalming 3 days, details subtitle still says Price set during package setup; the limits tab says No included limits or extra charges configured. Present the entered all-in price and selected coverage; distinguish automatic charges being inapplicable from missing coverage.
9. **Medium: package tools expose zero totals before selection and overload unavailable states.** Adjustments opens before selecting a package and shows multiple Ask Admin warnings and zeroes. Gate the existing tool to an applicable selection, with a clear reason where necessary. Present no-add-ons as a concise informational empty state; its disabled button duplicates the same unavailable message. Keep operational totals clear once applicable.
10. **Medium: date input guidance and errors are confusing.** January 1, 1980 works; 01/01/1980 and 1980-01-01 were cleared. Validation showed Date of birth is required plus Please enter select birth date for the same control (and equivalent death duplicates). Show the accepted format or support agreed formats in UI parsing, avoid duplicate hidden/alt-input errors, and make the calendar trigger keyboard-accessible. Click on the field itself does not open the calendar (clickOpens:false).
11. **Medium: several billing labels still aren't associated.** Amount Received, Cashless Type, Reference No. and Case Status lack useful accessible field names. Payment Status has a peso prefix, producing ₱ UNPAID. Clarify names and remove amount styling from textual status.
12. **Medium: background recovery prompt persists through all five steps.** The earlier autosave notice stays visible after new entries and at Final Review, offering to restore over current work. Clarify whether the user is continuing/restoring and dismiss obsolete recovery presentation only with safe data-preserving behavior.

Additional focus observation: checking custom service options moves focus to open_package_adjustments_btn. Source renderPackageControls calls setPackageAdjustmentsOpen(false) for custom mode, whose close branch focuses that opener even if it was already closed. Return focus only when actually closing an open overlay.

## Remaining fixtures / access

Complete populated Add-ons search/select/Apply/Cancel, active freebies, fully configured automatic charges, server draft resume, post-save dialogs, payment summary print and System Admin Other Branch at both desktop/mobile themes with isolated authenticated fixtures. Staff access cannot exercise Other Branch, and current catalog has no active add-ons/freebies. Do not represent source inspection or passing feature tests as visual completion of those states.

Recommended implementation order after the report: visibility/Manual Charges reachability; shared overlay lifecycle; readable controls/states; custom content and validation; remaining visual consistency; then repeat the affected browser matrix with isolated write fixtures.

## Authorized fixes implemented after this report

The user authorized fixing the confirmed issues. Changes are limited to `_form.blade.php` and the new `_interaction-styles.blade.php` presentation partial. Existing backend/pricing/authorization work was preserved.

- Fixed the grid/hidden conflict. Automatic tools remain hidden for Custom and before selecting a package. The unavailable Add-ons button is hidden while the informational empty state remains.
- Moved the existing Manual Charges section to Billing, retaining all names, hidden totals, row handlers and calculations. Tested adding PHP 1,000 to a PHP 50,000 package: existing total became PHP 51,000 and Final Review retained the description and amount. Tested Add Line and Remove Line. Fields remain owned by intakeWizardForm.
- Converted package details, adjustments, add-ons and leave overlays to native dialog elements without moving their fields outside the form. Shared lifecycle handles opening, closing, focus return, keyboard containment and main-area scroll locking. Native modal isolation covers the sidebar. Native ::backdrop uses the shared rgba(15,23,42,.72) scrim; internal legacy backdrop elements no longer paint a second surface. Outside clicks preserve existing close/cancel behavior.
- Dialog sizing keeps the header and footer accessible while the body scrolls. Corrected mobile adjustment header alignment and readable footer actions. Removed the panel animation's transformed containing block for the intake page.
- Corrected selected package-detail tab colors, arrow/Home/End navigation and tab/panel relationships. Custom details now show the entered all-in price and selected service coverage instead of claiming no configured limits.
- Normalized custom and overlay checkboxes to 18px, added selected-card indication and prevented unselected service cards stretching alongside expanded fields. Closing an already-closed adjustments dialog no longer steals focus from custom fields.
- Coverage/casket errors identify the actual service and unit. Linked billing labels and manual-charge accessible names. Removed the peso prefix from the textual payment status.
- Added accepted date-format hints and semantic calendar buttons. Generated alternate date inputs are omitted from duplicate generic validation and error-summary collection. Empty birthdate now produces exactly one Date of birth is required error. Calendar opens using Enter on its button.
- Recovery notices disappear when the user begins new input or explicitly restores. Snapshots with only automatic/default fields do not show a recovery prompt; stored snapshots are not deleted implicitly.

### Verification of the fixes

Actual signed-in Staff browser, using fictional unsaved sample entries:

- 1440×900 dark: custom details, readable selected tab, services/coverage/freebies, native viewport-wide scrim including sidebar; adjustments header/body/footer.
- 390×844 dark: custom details and adjustments fit the viewport; adjustment body scrolls with footer visible. Shift+Tab remains inside and Escape restores opener focus.
- 390×844 light: custom details and corrected checkbox/coverage presentation; adjustments and leave dialog. Native leave dialog isolates background, locks main scrolling, cycles focus and Keep Editing restores Cancel focus.
- 1440×900 light: date-format guidance, keyboard calendar open, single birthdate error, valid correction, complete step progression to Final Review, manual-charge totals and Review edit return.
- Verified no browser console errors, duplicate IDs or document overflow in final inspected state. All four dialogs close cleanly and manual controls remain in the original form. Theme restored to light and temporary viewport reset.
- Asset build and Blade cache/clear passed. Focused feature regressions rerun after implementation; final test results recorded in the chat.

Remaining limitations are the fixture/access gaps listed above: populated catalog Add-ons/freebies and privileged Other Branch were not visually verified, and server save/payment/print browser writes were not performed against the live database. These limits do not justify claiming the entire Case Intake module has complete browser coverage.
# Inclusion layout follow-up — October 13, 2026

State-based actions/Hearse preparation: unset casket now shows a single Choose a casket action, switching to selected name plus Change; unset days show Set agreed days, switching to days plus Edit. Body Retrieval shows Included without implying a km limit. Hearse has Choose a hearse and a small empty-catalog overlay; no placeholder vehicle IDs or additional requirement are submitted. Future integration hook: `#custom_hearse_catalog_options` / `[data-hearse-catalog-options]` inside `[data-inclusion-kind="hearse"]`. The summary renderer supports selected catalog values and Change; integration still needs a real select control, catalog loading/events, server validation, draft persistence and snapshot handling once the System Admin catalog exists. Browser verified both unset→selected transitions and Hearse Done/Escape/focus return, desktop light/mobile dark; build, Blade compilation and 24 tests/259 assertions passed. Screenshot: `storage/app/intake-custom-inclusion-actions.jpg`.

Compact row/overlay revision: removed inclusion card outlines/backgrounds and chip borders; summaries and Edit/Change align to the right. On phones the value/action use the row's full width below the service label. New forms default all five services checked; resumed selections remain unchanged. Replaced inline details editors with 420px native dialogs inside the original form, preserving names and validation. Verified default checked DOM state before restore, casket/day open/Done, zero-days validation, Escape/focus return, modal positioning and scrim, form ownership, desktop/mobile light/dark and no horizontal overflow. Dark dialog helper contrast corrected against the existing important paragraph rule. Build and 24 intake tests (259 assertions) passed; Blade cache compiled. No vehicle/hearse-type catalog exists in the inspected models/admin service management; recommend a System Admin vehicle catalog rather than inventing options. Screenshots: `storage/app/intake-custom-compact-inclusions.jpg`, `storage/app/intake-custom-small-editor.jpg`.

Custom agreed-price follow-up (user authorized pricing support): retrieval/hearse need no km; selected transport is shown as an included chip. Available catalog casket and agreed embalming/viewing days appear as chips with Change/Edit and Done controls. These inclusions remain covered by the entered all-in price without casket or extension charges. New manual Other inclusions entry is removed; existing server-draft text is preserved as previously agreed inclusions. Optional catalog add-ons are enabled for Custom, validated as active, charged separately and snapshotted.

Verified edit/Done, positive-days validation, transport without km, keyboard access, browser totals (PHP 50,000 remains 50,000; existing PHP 1,000 manual extra produces 51,000), desktop/mobile light and dark layouts without horizontal overflow. Isolated regression test covers PHP 15,000 included casket plus five-day services without double charge, PHP 2,000 extra yielding PHP 52,000, and inactive add-on rejection. 24 tests passed, 259 assertions; build and Blade compilation passed. Live catalog has no active add-ons, so populated add-on UI remains unverified in this session. Screenshot: `storage/app/intake-custom-agreed-inclusions.jpg`.

Validation placement follow-up: summaries now anchor only after a direct step heading, otherwise at the beginning of the wizard step. This prevents Service Selection errors from appearing inside the nested Service Details section. Verified missing package-name error, summary link focus, dismissal after correction, desktop light and mobile dark presentation with no horizontal overflow. Build, Blade compilation, and 23 intake tests (254 assertions) passed. Screenshot: `storage/app/intake-validation-placement-fixed.jpg`.

Replaced the two-column service-card grid with one service per row. At widths of 640px and above, the selected service's coverage/casket field appears alongside its checkbox; narrow screens stack the field within the same row. Existing field names, values, validation and dependent-field hooks are preserved.

Verified mixed and all-selected states, check/uncheck, and keyboard navigation at 1440px and 390px in light/dark themes; checked the 768px breakpoint. No document horizontal overflow. Build and Blade compilation passed; intake draft and structured pricing tests passed (23 tests, 254 assertions). Screenshot: `storage/app/intake-inclusion-layout-fixed.jpg`.

