# Chapel and payment correction workflows

## Implemented scope

This rollout extends the existing Laravel workflows and keeps their shared visual language. It adds branch-owned physical chapel configuration, intake integration and schedule advisories, and staff correction/void requests with Branch Admin review and persistent in-app notices.

| Area | Implementation owner | Behavior |
| --- | --- | --- |
| Chapel identity | AddOnCatalog, AddOnCatalogController, add-on form | Category-specific branch/address, normalized branch/name uniqueness, read-only Day display, legacy Needs Setup context |
| Package allowance | PackageController, package form | One configured chapel inclusion per branch, included-day quantity, linked catalog rate |
| Intake | IntakeController, staff/intake/_form | Branch-appropriate chapel, authoritative name/address, package allowance or explicit paid selection, server-calculated days, no duplicate optional-add-on charge |
| Schedule advisory | ChapelSchedule, ChapelScheduleController | Physical chapel ID and full half-open interval, saved Active/Completed cases, signed acknowledgment, final transactional recheck |
| Correction requests | PaymentCorrectionController, correction-create | Own-payment requests, selected changed fields, payment details validation, evidence source and private optional file, financial preview |
| Review | correction-detail, PaymentCorrectionController | Frozen before/after values, current balance impact, approval note, rejection reason, stale-payment protection, original/replacement history |
| Tracking | PaymentController, _correction_requests | Staff-owned requests or branch queue, search/status/sort/page size, connected table/footer, row and eye action |
| Notifications | WorkflowNotice, topbar-notifications, payment-workflow.js | Durable per-user records, unread and pending counts, 30-second visible-page refresh, authorized direct request link |
| Motion | payment-workflow.js, record-dialog-motion | Right drawer, centered confirmation with parent hidden, preserved inputs/scroll, reduced motion, bounded close wait |

## Chapel rules

- A Chapel Viewing catalog row identifies one physical chapel/room and its daily rate. Branch/name uniqueness also applies to archived records; restore/edit rather than duplicate.
- Existing chapel rows are not assigned to a branch by guessing. Complete branch/address configuration in Service Management. Their UI indicates Needs Setup.
- Staff see their branch's chapels. The server also enforces the case branch and active/selectable status.
- Package chapel allowances apply only to the configured branch. A package's unassigned chapel inclusion needs setup before use for Chapel Viewing.
- Home Viewing and Chapel Viewing use their own coverage. Chapel is selected in the Viewing section, not again in Optional Add-ons.
- Included chapel: charge only excess days. Separately selected chapel: charge all viewing days. Explicit zero rates are accepted; missing configuration is not treated as free.
- Incomplete/invalid chapel selection changes the primary action to a functioning Save as Draft. Valid configuration restores Continue.
- Start/end equality is not overlap. Draft/deleted records are not reservations. Conflict acknowledgment is an operational decision, not a billing override.
- Changing chapel or schedule clears the advisory signature and acknowledgment. Final saving rechecks under the chapel lock so a newly recorded conflict cannot reuse an old acknowledgment.
- Autosave/server drafts restore inputs with current catalog configuration and a review notice. Old schedule acknowledgments are never restored.
- Historic cases lacking a chapel catalog ID cannot be reliably matched to a physical chapel. Do not infer identity from free text; branch staff must check their historical schedule while legacy records are mapped through an authorized data-maintenance process.

## Payment correction rules

- Staff requests concern their own posted payments in the same branch. No direct edit, cross-branch reassignment, or request against a voided payment.
- Correct Payment produces a linked replacement after approval. Void Payment Record has no replacement. Neither is a refund or permanent deletion.
- Amount/date/case/details are selected explicitly. Method-specific fields are prefilled from the original payment and validated with PaymentDetails; switching to Cash clears cashless metadata.
- Reason category, explanation and supporting source/reference are required. JPG/PNG/PDF evidence is optional, limited to 5 MB, stored privately and served through requester/reviewer authorization with download disposition.
- A pending request does not change posted balances. Staff may withdraw it; submitted proposals cannot be edited.
- Only a same-branch Branch Admin can approve/reject; self-approval is prohibited. Approval requires a review note. Rejection requires a reason.
- Approval checks the frozen original against the current payment and validates the current target balance. Stale proposals are visibly blocked and must be rejected/re-requested.
- Original void, replacement, balances, audit entry, request decision and durable notice are committed transactionally. Repeated approval cannot create a second replacement.
- Original receipt references are retained; no official receipt renumbering or automatic refund is introduced.
- Reading a notice marks it read; the pending count remains until resolution. Notification polling failures do not undo committed decisions. The queue remains accessible independently of notifications.

## UI and verification

- Shared soft-green surfaces, divider sections, background-based field states, semantic action colors and text status labels; no ordinary-container shadows.
- The request/review drawer uses a right-side close control and a scroll body with persistent footer. Confirmation replaces the visible parent form and restores its DOM, inputs and scroll on Keep Editing.
- Request and review have full-page fallback routes. Browser fixtures verify actual Blade output and the built app bundle using isolated in-memory SQLite; no live financial records are modified.
- Tests: ChapelCorrectionWorkflowTest, IntakeDraftWorkflowTest, IntakeStructuredPricingTest, AdminRecoveryWorkflowTest, CatalogAmountValidationTest, PackageFormPresentationTest; BranchSyncTest covers correction reconciliation. Node tests cover notice/button states, form discard, hearse pricing and shared drawer motion. ChapelCorrectionPresentationTest covers request field states, confirmation restoration, admin rejection UI and mobile light/dark layout. IntakePackageCardsTest covers inclusion placement and footer reachability.
- Build and Blade compilation are required. Isolated browser checks do not establish acceptance of every production role/data combination.

## Boundaries

No new refund, accounting-period closure, overpayment-credit, complimentary-day, automatic chapel allocation, or privileged temporary-delegation subsystem is introduced. Existing role administration and backup/recovery remain authoritative. The current application has no reporting-period closure state to enforce; do not simulate closure based on an old payment date. Any future closing/adjustment module must enforce its locks before normal correction approval.
