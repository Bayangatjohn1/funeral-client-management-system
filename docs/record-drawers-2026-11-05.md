# Record detail drawers — 2026-11-05

Implemented, with browser visual verification pending. This replaces the centered View geometry in the earlier detail-modal note; its content and semantic action rules remain applicable.

## Applicability

- Applicable: Branch overview, User information, Service catalog details, Case Records and Master Case Records summaries (including Owner history).
- Already consistent: existing View destinations, policy checks, eye/row activation, status confirmation forms and Service detail loader.
- Not applicable: edit/create forms, confirmation feedback, payment details, new business actions or data fields.
- Existing functionality missing: none required for this presentation change.

## Current design

Right drawer width 560px, capped at viewport width; full viewport height and full width on narrow screens. Header/footer remain visible; the body scrolls. Sage header, borderless soft sections, thin internal dividers, square outer edge, no shadows or decorative outer outlines. Keyboard focus remains visible. Shared 180ms horizontal motion respects reduced motion. Native modal dialogs retain Escape, focus containment and focus return. Backdrop and close controls retain their existing workflows.

Light surface/header/soft: #F7FAF8 / #D5E4D9 / #EAF2ED. Dark: #15251C / #1C3025 / #22392C. Dividers: #B8CEC0 / #355241. Primary actions retain sage #A9C9B5 → #92B89F; Service Edit keeps informational blue, feedback keeps semantic colors.

Branch retains location, operating status, case overview and existing totals. User retains access, role, identity, branch and contact fields. Service retains its existing detail iframe, catalog content, actions and Edit-return flow. Branch edit/feedback and User status confirmation remain separate centered modes.

Case/Master uses a concise read-only summary: case ID, deceased, case/payment statuses, branch, client/contact, package/service/wake location, funeral/interment schedules, existing total/paid/balance and encoded-by/date. Detailed charges, documents and transactions stay on the original Full Details page. The original eye link remains the Full Details fallback and retains return_to; modified clicks keep native navigation. The summary uses the same authorized show route with drawer_summary=1, extracts only summary markup, never executes fetched scripts, handles errors/retry, and aborts or ignores stale requests after record changes/close. No routes/controllers, policy rules, database structures or calculations were changed for this revision.

## Source owners

- `resources/views/partials/record_drawer_styles.blade.php`: final drawer geometry, scoped above older detail modal specificity; included after record_detail_modal_styles.
- `resources/js/record-dialog-motion.js`: shared centered/drawer motion and cancellation.
- `resources/js/record-drawers.js`, `partials/case_summary_drawer.blade.php`, `partials/case_record_summary.blade.php`: Case/Master shell, async summary and content.
- `partials/case_record_actions.blade.php`, `partials/case_view_content.blade.php`, `layouts/panel.blade.php`: original-link enhancement and summary presentation branch.
- `partials/user_information_modals.blade.php`: information-only drawer/body, confirmations excluded.
- `admin/branches/index.blade.php`: overview-only drawer mode, existing edit/status modes preserved.
- `partials/service_detail_drawer_body.blade.php`, `layouts/modal-frame.blade.php`: moves existing Service detail nodes into a scroll body without cloning/rebinding them. Forms excluded.
- `resources/js/app.js`: module installation.

## References

Supplementary side-sheet structure: [Material side sheets](https://m2.material.io/components/sheets-side). Modal keyboard/close/focus expectations: [W3C dialog guidance](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/). These informed interaction structure; the project retains its approved palette and typography.

## Verification

- Isolated SQLite feature checks: CaseSummaryDrawerTest, CaseRecordPrintTest, WorklistAdoptionTest and ServiceCatalogConsistencyTest: 16 tests / 269 assertions passed. Covers summary values, unchanged full details, Staff/Branch Admin/System Admin/Owner lists, cross-branch denial, Owner draft/unverified denial and existing directory/catalog workflows.
- Node behavior checks: record-drawers, row-action-menu, service-archive-confirm, payment-toolbar-action and sort-menu-heading: 14 tests passed. Covers retry/stale/abort/focus/navigation, reduced motion, reopening during close, existing Service content-node preservation and menu/confirmation regressions.
- Production asset build and Blade compile/cache-clear passed. Whole-tree whitespace check still reports unrelated existing whitespace in models, other views and tests; touched tracked drawer files pass.
- Browser execution was previously declined and was not retried. Light/dark visual appearance, responsive sizing, native focus containment, real pointer hit testing and iframe rendering remain unverified. Source/build/tests are not visual acceptance.
- Two previously reproduced unrelated package-content expectation failures remain recorded in the earlier modal note; no business/content changes were made to satisfy them.
