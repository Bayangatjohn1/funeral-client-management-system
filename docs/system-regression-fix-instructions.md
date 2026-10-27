# CODEX — SYSTEM REGRESSION FIXES WITH DEFINED SOLUTIONS

## Main Objective

Continue improving the Sabangan Caguioa Funeral Home Management System.

Your next task is to investigate and resolve the 20 remaining regression failures reported on October 21, 2026.

**Do not guess the intended behavior, blindly rewrite tests, or introduce your own business rules.**

Follow the expected behaviors and correction guidelines below. These define the intended outcomes, not unverified root causes. Inspect the actual source code to determine the appropriate implementation changes.

Preserve the existing central-and-local-branch architecture with single-writer operational ownership, offline synchronization, role permissions, and approved workflows. Preserve unrelated working-tree changes and retain a baseline before corrective edits.

---

# PART 1 — Authorization and Branch Access

## 1. Fix `AdminScopeAuthorizationTest`

Failing test:
`test_branch_admin_main_intake_uses_assigned_branch_instead_of_br001`

### Expected behavior

Every Staff and Branch Admin account must operate within its authorized branch scope.

Branch scope does not grant encoding permission. Preserve the current Staff-only intake and payment-recording permissions unless an explicit business decision authorizes a role change. Branch Admin may view and perform only its existing authorized administrative workflows. Do not make the named intake test pass by granting Branch Admin recording access.

- Branch Admin accounts are assigned to a specific branch.
- Their access must use their actual assigned branch ID.
- BR001 must not be hardcoded as the default authorized branch.
- System Admin has system-wide authority.
- Owner remains read-only.
- Temporary cross-branch encoding requires an explicitly valid permission and must not grant unrestricted access.

### Required solution

Inspect the existing authorization policies, middleware, controller queries, model scopes, and branch assignment logic.

Ensure that authorization is based on the authenticated user's actual role and authorized branch scope, rather than assumptions about branch codes.

Enforce restrictions on backend requests, not only through UI filters.

### Verification

- BR001 Branch Admin accesses BR001 records.
- BR002 Branch Admin accesses BR002 records.
- BR003 Branch Admin accesses BR003 records.
- Unauthorized cross-branch operations are rejected.
- System Admin access remains global where authorized.
- Valid temporary cross-branch permissions continue to work according to their restrictions.

Do not introduce a new role model.

## 2. Fix `SystemRegressionGuardsTest`

Failing tests:
- `test_branch_admin_payment_monitoring_forces_assigned_branch_scope`
- `test_case_details_support_case_centered_payment_entry`

### Expected behavior

**Payment Monitoring**

Branch Admin must see only payment information from authorized branch records.

Manipulating URL parameters, query strings, or request bodies must not provide access to other branches.

**Case-centered payment recording**

The payment entry flow must be connected to the correct case.

- A payment must belong to an existing authorized case.
- The correct case balance must be displayed.
- The recorded amount must update the payment history and outstanding balance consistently.
- Payment status must be recalculated according to the existing financial rules.
- Duplicate submissions must not create unintended duplicate payments.
- Deduplicate retries or double-clicks of the same submission using the existing submission identity/idempotency mechanism where available. Two legitimate payments must not be rejected merely because their case, amount, or date match. Do not add a uniqueness constraint on that combination.
- Corrections must follow the existing audited correction/void workflow.

### Required solution

Review payment routes, controller authorization, case relationships, request validation, database transactions, and balance calculations.

Use the existing payment service or authoritative calculation logic consistently across Record Payment, Case Details, Payment Monitoring, and reports.

Do not introduce multiple independent balance calculations.

### Verification

Test payments against fully paid, partially paid, and unpaid cases; unauthorized branches; repeated submissions; and corrected payments.

---

# PART 2 — Intake and Funeral Timeline Validation

## 3. Fix `FuneralServiceTimelineTest`

Failing tests:
- `test_funeral_service_same_day_as_death_is_allowed`
- `test_funeral_service_before_date_of_death_is_rejected`
- `test_wake_end_must_be_later_than_wake_start`
- `test_wake_end_may_equal_funeral_ceremony_but_interment_must_be_later`

### Expected behavior

The intake form uses separate schedule information:

1. Wake Start Date and Time
2. Wake End Date and Time
3. Funeral Ceremony Date and Time
4. Interment Date and Time

Preserve the existing business meaning of each field.

### Required validation rules

**Date of Death**

A funeral ceremony cannot occur before the date of death.

A funeral ceremony on the same calendar date as the date of death must be allowed if it satisfies the applicable time rules.

Do not incorrectly require an additional calendar day between death and funeral ceremony.

**Wake Schedule**

Wake End must be later than Wake Start.

A zero-duration or reversed wake period must be rejected.

**Funeral Ceremony**

Wake End may equal the Funeral Ceremony Date and Time.

Do not require an unnecessary gap between the two events.

**Interment**

Interment must occur after the Funeral Ceremony according to the agreed workflow.

**Request Date**

Distinguish the system recording timestamp from a user-entered service request date. The current implementation generates the recording date on the server; preserve that audit timestamp and do not trust a posted value to overwrite it.

If a separate user-entered service request date exists in the approved workflow, establish whether it must not precede Date of Death. The earlier blanket statement is not authorization to introduce that business rule or add a new date field. If its meaning or chronology rule cannot be established, report the specific unresolved decision and continue other corrections. Do not reject or backdate a valid case merely to satisfy the old assertion.

### Required solution

Inspect date parsing, timezone handling, validation rules, controller transformations, and the relevant forms.

Use consistent date/time comparison across frontend and backend.

Do not compare formatted date strings lexically.

Do not apply a blanket prohibition against future dates to future funeral schedules. Distinguish historical dates from planned events.

Preserve optional-field behavior based on the existing approved intake requirements.

### Verification

Cover valid same-day events, invalid chronological order, equal wake-end/ceremony times, and proper date/time boundary comparisons.

---

# PART 3 — Negative Scenarios and Duplicate Client Records

## 4. Fix `SystemNegativeScenariosTest`

Failing tests:
- `test_intake_rejects_duplicate_client_in_same_branch`
- `test_intake_rejects_request_date_before_date_of_death`
- `test_intake_rejects_funeral_service_date_before_date_of_death`

### Expected behavior

Invalid dates must be rejected by server-side validation.

For duplicate clients, distinguish between:

- An accidental duplicate client master record.
- An existing client who legitimately requests another funeral service.
- A separate client who happens to share the same name.

### Required solution

Inspect the existing client identity fields, database relationships, duplicate-detection rules, and intake behavior.

Do not use a person's name alone as a guaranteed unique identifier.

Avoid creating duplicate client records when an existing matching client should be selected or reused.

However, do not prohibit legitimate new funeral cases merely because the same family representative previously requested a service.

If the current duplicate-client test contradicts these requirements, investigate and correct the test to reflect the approved business behavior rather than adding an overly restrictive database constraint.

Preserve historical cases and existing client relationships.

Do not automatically merge clients using name, phone number, address, or an unapproved combination of those fields. Reuse a client through explicit selection of an existing authorized branch record using the established workflow. Any new identity-matching or merge policy requires a separate confirmed business decision; document the ambiguity rather than inventing one.

---

# PART 4 — Dashboard Accuracy and Event Schedules

## 5. Fix `StaffDashboardReminderTest`

Failing tests:
- `test_current_wake_is_case_based_and_schedule_is_event_based`
- `test_notification_target_case_is_subtly_highlighted_on_reminders_page`
- `test_admin_payment_summary_uses_one_consistent_case_cohort`
- `test_settled_cases_leave_balance_overview_and_upcoming_is_limited_to_seven_days`
- `test_timeline_uses_saved_times_excludes_drafts_and_groups_cross_event_schedules`

### A. Currently in Wake

Count cases that are currently within their valid wake period.

A case should not be counted twice simply because it has multiple scheduled events.

### B. Scheduled Events

Count individual events, not just unique cases.

For example, a case with both a Funeral Ceremony and Interment scheduled today must contribute two scheduled events.

Do not remove valid events merely because a case has been marked Completed or Paid.

Use the actual stored schedule fields.

### C. Upcoming Events

Follow the existing seven-day upcoming window.

Ensure date boundaries are interpreted consistently in the application timezone, Asia/Manila.

Exclude Draft cases from active schedule displays unless an explicitly approved feature requires otherwise.

Group events clearly without losing individual event information.

### D. Payment Summary

Use a consistent set of eligible cases and filters when calculating dashboard totals.

Apply operational financial-summary expectations to the appropriate existing dashboard. System Admin currently has an administration dashboard; do not restore operational panels there merely because an old test expects them. Preserve existing role-specific metrics and route destinations.

Fully settled cases must no longer contribute to an outstanding-balance overview.

Do not incorrectly remove settled cases from unrelated historical revenue or scheduling reports.

### E. Reminder Navigation

When a reminder links to a specific case, navigate to the correct authorized destination.

The corresponding case may receive subtle visual highlighting to help the user identify it.

Do not reveal unauthorized case information through manipulated reminder links.

### Required solution

Inspect the dashboard query logic and event-generation services.

Separate case-based metrics from event-based metrics.

Use shared, authoritative calculations for payment summaries.

Do not patch dashboard totals using manually adjusted counts.

### Verification

Test multiple events for one case, completed/paid cases with scheduled events, drafts, date boundaries, and branch-specific filtering.

---

# PART 5 — Admin Filters and User Interface

## 6. Fix `AdminOwnerInputConsistencyTest`

Failing tests:
- `test_admin_date_filter_options_are_consistent_on_desktop_and_mobile`
- `test_admin_filter_validation_handles_valid_and_invalid_values`

### Expected behavior

Desktop and mobile users must have equivalent filtering capabilities on pages where those filters are part of the approved workflow.

Determine the actual role, route, and dashboard under test first. System Admin uses its administration dashboard; do not add operational date filters solely to satisfy an outdated test. Preserve valid branch filters and data scopes on the pages that actually support them.

- Use the same supported filter options.
- Apply the same validation.
- Preserve selected filters during normal navigation.
- Prevent invalid filter values from producing incorrect queries.
- Ensure branch restrictions still apply regardless of filter values.

### Required solution

Prefer a shared filter definition rather than maintaining different values in separate desktop and mobile implementations.

Use validated inputs and consistent query handling.

Handle invalid input using the application's established validation/error-response behavior.

Do not silently expose unrestricted records when a filter is invalid.

## 7. Fix `AdminPackageStructuredWorkflowTest`

Failing tests:
- `test_package_form_renders_in_page_casket_modal_without_new_tab_catalog_flow`
- `test_main_admin_uses_hover_actions_for_add_on_catalog`

### Expected behavior

Preserve the currently approved Service Management UI.

System Admin can manage Packages, Caskets, Add-ons, and Freebies.

Branch Admin has read-only catalog access.

For the existing package form and casket-selection workflow:

- Preserve the intended in-page modal behavior where still part of the approved UI.
- Do not unnecessarily open separate tabs.
- Maintain the existing selected item and form state.
- Preserve correct validation and save behavior.

For catalog actions:

- Maintain an accessible and discoverable action placement.
- Where hover-revealed actions are the intended desktop design, ensure keyboard focus and touch users can also access them.
- Do not restore outdated UI behavior solely because an old test expects it.

### Required solution

Compare the tests against the current approved views and actual business workflow.

If the interface intentionally changed, update outdated tests only after establishing that the new behavior is correct.

Do not redesign Service Management.

## 8. Fix `ProfileTest`

Failing test:
`test_staff_non_dashboard_pages_use_inline_header_without_notification_bell`

### Expected behavior

Maintain consistent Staff page layouts.

Preserve the approved navigation, page headers, and notification placement.

Do not introduce a global topbar, duplicated header, or unrelated notification button into pages that were deliberately designed without them.

### Required solution

Inspect the shared Blade layouts and page-specific components.

Remove unintended duplicated interface elements only when they are confirmed defects.

If the current design was intentionally updated, make the test reflect the approved design.

---

# PART 6 — Validate Existing Multi-Branch Functionality

After resolving the 20 failures, inspect the interaction of the existing features with offline deployment.

Verify:

**Temporary Cross-Branch Encoding**

The previous workflow includes temporary authorization for Staff to encode on behalf of another branch.

Because activated branches now own their operational writes locally, determine whether this workflow is supported, intentionally restricted, or incompatible with the current sync architecture.

Do not create a second central writer or route data into the wrong branch.

A temporary cross-branch permission never overrides activated branch write ownership. Preserve existing restrictions unless an approved supported workflow already exists. Do not enable cross-branch writes inside an installation whose snapshot protocol owns only one branch, and do not infer permission to provision a user on another branch server. Document any unsupported combination or required business decision.

Document the correct supported behavior and any required business decision.

**Payment Corrections**

Ensure correction requests, approvals, audit history, original transactions, and corrected payments remain consistent with the branch's ownership rules.

**Case Records and Reports**

Ensure filtering, payment status, case status, branch access, historical records and financial totals remain consistent after synchronization.

**System Admin and Owner**

Preserve System Admin global administrative permissions and Owner read-only reporting access.

Central reports must identify stale branch records instead of presenting outdated figures as current.

---

# PART 7 — Root Cause and Correction Rules

For each failing test, perform the following:

1. Reproduce the failure using the existing test.
2. Read the exact assertion and failure message.
3. Trace the route, controller, policy, validation, model, query, Blade view and relevant fixtures.
4. Compare the behavior with the requirements above and the current approved application workflows.
5. Classify the failure:
   - Confirmed application bug
   - Outdated test expectation
   - Test fixture/setup defect
   - Environment-specific issue
   - Unresolved business-rule ambiguity
6. Apply the smallest correct fix.
7. Add or update meaningful regression coverage.
8. Verify related roles and workflows.

### Important decision rule

If the intended business behavior cannot be established from the current requirements or implementation history, **do not invent one**.

Document the specific unresolved decision, explain the possible consequences, and continue resolving other confirmed issues.

Never change a business rule merely to satisfy a failing assertion.

---

# PART 8 — Mandatory Verification

Run the complete applicable SQLite suite and the relevant MariaDB suites.

Then run browser tests for affected Staff, Branch Admin, System Admin, and Owner workflows.

Verify:

- No unauthorized branch access.
- No missing or duplicated payments.
- Correct balances and historical records.
- Correct intake validation.
- Correct schedules and dashboard totals.
- No broken navigation or form workflows.
- No regressions in offline synchronization.
- No modified production data.

Retain test logs and report failures accurately.

The target is a clean applicable regression suite, but do not hide, skip, or weaken failing tests to achieve it.

---

# FINAL REPORT FORMAT

For each of the 20 original failures, report:

| Field | Required information |
|---|---|
| Test | Exact test name |
| Root cause | Verified technical cause |
| Intended behavior | Confirmed requirement |
| Solution | Actual code correction or justified test update |
| Files changed | Exact affected files |
| Verification | Tests executed and results |
| Status | Fixed / Unresolved / Blocked |

Also summarize:

- Total confirmed bugs.
- Total outdated tests.
- Total fixture/environment issues.
- Any unresolved business decisions.
- Full regression results.
- MariaDB verification results.
- Offline synchronization regression results.
- Remaining release blockers.

## Final Instructions

Prioritize authorization, intake validation, financial accuracy and schedule correctness.

Preserve the current system architecture.

Do not perform unnecessary redesigns.

Do not change unrelated application behavior.

Do not deploy or migrate the current Hostinger production installation.

**Complete all verifiable corrections in the current environment and provide evidence for each result. Do not guess, silently weaken requirements, or report unsupported success.**