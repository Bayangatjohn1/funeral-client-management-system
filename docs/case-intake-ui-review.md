# Case Intake UI/UX review — 2026-10-13

## Scope and baseline

Staff Case Intake, using staff-ui-reference and ui-ux-pro-max. Intake routes are inside the staff middleware group; no access changes were made for other roles. Existing working-tree changes in the intake controller, form, create view, pricing, shared CSS/JS and catalog screens were preserved.

The existing Client → Deceased → Service Selection → Billing & Payment → Review sequence is retained. Fields, visible copy, section order, actions, validation rules, form methods, routes, branch scope, calculations and submission hooks are retained.

## Changes in this batch

- Intake CSS: reserve space for the panel navigation control at narrow/tablet widths; allow the mobile heading brand to shrink so its subtitle wraps instead of clipping.
- Shared intake form: associate 32 existing labels with controls, preserve existing IDs, and add IDs where absent.
- Wizard: expose current step, locked states and panel relationships; keep the current step visible in its horizontal rail.
- Step navigation: select the actual scrolling ancestor instead of the page-content element whose overflow is visible; respect reduced motion for step scrolling.
- Field errors: associate inline error messages with their controls using aria-describedby, preserving other descriptions.
- Review: keep Edit actions visible and give each one a section-specific accessible name.

## Verification matrix

| Role / route | Reference / shared dependency | Actual verification | Status |
| --- | --- | --- | --- |
| Staff /staff | Staff dashboard / panel shell | Source review; rendered light and dark theme controls | Reference inspected |
| Staff /intake/main | Staff intake / staff-intake-reference.css and _form.blade.php | Client step: 1440×900 light/dark; 390×844 light/dark; dark 1024×900; initial 476px light baseline. Heading/navigation clearance and subtitle wrapping inspected. No document overflow at 1440 and 390. All wizard aria-controls resolve; no duplicate IDs or orphan linked labels. Continue shows five linked errors and focuses the first input. Autosave recovery notice inspected in both themes. | Implemented; partial browser verification |
| Staff /intake/other | Shared intake form | Source inspection only; branch/reporting rules retained | Implemented but visually unverified |
| Staff /intake/drafts/{draft} | Shared intake form | Existing feature test verifies saved schedule/payment/step render | Implemented but visually unverified |
| Intake steps 2–5 and reachable dialogs | Shared intake form, service and payment controls | Source inspection; browser regression suite attempted but login redirects prevented reaching intake | Implemented but visually unverified |

## Automated checks

- npm.cmd run build: passed after sandbox escalation; repeated after the CSS correction.
- php artisan view:cache: passed; view:clear run afterward.
- IntakeDraftWorkflowTest and IntakeStructuredPricingTest: 23 passed, 254 assertions (in-memory SQLite).
- git diff --check: passed.
- Existing IntakePaymentWorkflowTest and IntakeSaveModalTest: isolated SQLite runner attempted; 8 timeouts and 1 HTTP 302 assertion failure. Failure screenshot shows the login page. EnsureAccountSecurity requires session security state that the existing Dusk loginAs setup does not supply. No application security or test authentication changes were made in this UI task. These tests do not provide successful end-to-end validation.

## Recommendations / outstanding verification

- Retain the five-step sequence; it follows the dependencies between identity, service selection, charges and final review.
- Investigate autosave recovery appearing after an otherwise empty intake is visited. Prefer offering recovery only when meaningful user entries exist. This was observed during review; its cause and recovery-state preservation need a separate focused check before altering autosave behavior.
- Complete browser QA of resumed drafts, Other Branch, service dialogs, billing, review, cancel/draft and post-save feedback using an isolated authenticated fixture compatible with current account security. Do not treat the full intake flow as visually complete yet.
