# CODEX — NEXT TASK: Payment Integrity and Branch Access Integration Review

## Objective

Continue reviewing the Sabangan Caguioa Funeral Home Management System following the successful October 21, 2026 regression corrections.

The previous report confirms 413 passing MariaDB tests and the resolution of all 20 reported regression failures.

Your next task is to review and verify two important areas:

1. **Payment Retry Protection and Financial Integrity**
2. **Assigned-Branch-Only Access and Removal of Unused Cross-Branch Encoding**

The business decision is finalized: **Staff and Branch Admin must operate strictly within their assigned branch. Cross-Branch Encoding is not required.**

Do not redesign existing functionality or introduce new business rules beyond this approved decision. Review the implementation first and make targeted corrections only where necessary.

---

## TASK 1 — Payment Retry Protection

### Current Implementation

The system already has:

- UUID-based payment submission identity.
- Canonical request hashing.
- Database uniqueness for `(branch_id, submission_id)`.
- Transaction locking.
- Duplicate-submission prevention.
- Payment correction and synchronization safeguards.

These mechanisms have passing tests.

### Required Review

Inspect all existing payment creation and correction paths, including the Record Payment modal in Payment Monitoring.

### A. Payment Submission

Verify that:

- Every active payment recording form generates a unique submission identity.
- Double-clicking Submit does not create duplicate payments.
- Retrying the same request after a timeout does not create another transaction.
- The same submission identity with modified payment details is rejected.
- A genuinely new payment receives a new identity, even when its amount and date match an existing payment.
- The server validates payment amount, remaining balance, permissions, and payment details.
- Staff can record payments only for cases belonging to their assigned branch.

### B. Payment Corrections

Verify that:

- Original transactions remain preserved in the payment and audit history.
- Approved corrections follow the existing void/replacement workflow.
- Repeated correction requests or approval submissions cannot unintentionally create multiple replacement payments.
- Corrected payments maintain accurate case relationships, totals, and balances.
- Unauthorized correction approvals are rejected.
- Branch Admin can approve authorized correction requests only from their assigned branch.

### C. Offline Synchronization

Verify payment integrity when:

- A branch records payments while offline.
- Internet connectivity returns.
- A synchronization request is repeated.
- An acknowledgment is lost.
- Two authorized Staff members record payments against the same case.
- A payment correction is completed while the branch is offline, according to existing permissions.

Ensure balances and payment histories remain consistent between branch and central records after successful synchronization.

### Required Solution

For every confirmed defect:

1. Reproduce the issue.
2. Identify the actual root cause.
3. Apply the smallest safe correction.
4. Add meaningful regression coverage.
5. Verify the correction using SQLite, MariaDB, and relevant browser tests.

Preserve backward compatibility with existing payment records and legacy requests.

Document any remaining risks involving requests without submission identities.

Do not change the existing Payment Monitoring interface unless necessary to correct a verified functional problem.

---

## TASK 2 — Assigned-Branch-Only Access

### Final Approved Business Rule

The Sabangan Caguioa Funeral Home Management System is a centralized multi-branch system.

However, centralized management does not mean that Staff or Branch Admin can operate across different branches.

**Every Staff and Branch Admin account is restricted to its assigned branch.**

### A. Required Role Permissions

**Staff**

- Can create and manage authorized funeral cases only within their assigned branch.
- Can record payments only for cases belonging to their assigned branch.
- Can access authorized case records, transactions, and payment history from their assigned branch.
- Cannot encode, modify, or access another branch's operational records.

**Branch Admin**

- Can manage and monitor authorized branch operations only within their assigned branch.
- Can review and approve permitted payment corrections within their assigned branch.
- Has read-only access to Service Management catalogs, according to existing permissions.
- Cannot access or manage another branch's operational records.
- Must not gain Staff-only intake or payment-recording permissions simply because of their administrative role.

**System Admin**

- Retains system-wide administrative authority.
- Can manage branches, users, system-wide service catalogs, and other authorized central functions.
- Global administrative access does not automatically grant routine branch intake or payment-recording permissions.
- Existing authorized system-wide reporting and administrative operations must remain functional.

**Owner**

- Retains authorized read-only access to consolidated dashboards, analytics, and reports.
- Cannot perform operational recording or unauthorized modifications.

### B. Review and Enforce Branch Restrictions

Inspect the existing:

- Role-based authorization.
- Middleware and policies.
- User branch assignments.
- Controller queries.
- Intake and payment routes.
- Payment Monitoring access.
- Case Records access.
- Offline branch database restrictions.
- Synchronization ownership validation.

Required behavior:

1. Use the authenticated user's actual assigned branch.
2. Never hardcode BR001 as the default authorized branch.
3. Do not allow request parameters or URL manipulation to bypass branch restrictions.
4. Reject unauthorized cross-branch requests at the backend.
5. Ensure branch-specific records remain correctly scoped online and offline.
6. Preserve System Admin and Owner permissions according to their approved responsibilities.

### C. Disable Unused Temporary Cross-Branch Encoding

The previous implementation included a Temporary Cross-Branch Encoding permission model.

This functionality is no longer required under the approved business workflow.

Review existing references to:

- Temporary cross-branch permissions.
- Permission-granting actions.
- Other Branch Intake.
- Cross-branch encoding routes.
- Related authorization exceptions.
- Unused UI controls and permission checks.

Apply the following rules:

- Disable or remove unused Cross-Branch Encoding UI and operational entry points.
- Remove authorization exceptions that would permit Staff to encode for another branch.
- Prevent legacy permission records from granting cross-branch operational access.
- Preserve historical audit records and existing case ownership.
- Do not delete legacy permission tables or historical data without establishing that removal is safe and necessary.
- Do not introduce destructive database migrations unnecessarily.
- Do not affect System Admin's legitimate system-wide administrative access.

If a legacy permission structure is retained for historical compatibility, it must not grant active cross-branch encoding privileges.

### D. Offline Synchronization Consistency

Each activated branch owns its operational records.

Ensure that:

- BR001 records originate from its authorized local operational database.
- BR002 records originate from its authorized local operational database.
- BR003 records originate from its authorized local operational database.
- Branches cannot submit operational records claiming another branch's identity.
- The central server validates branch ownership during synchronization.
- No competing central operational writer is introduced.
- An offline branch cannot access another branch's local operational records through the synchronization mechanism.

### E. Required Tests

Verify the following scenarios:

1. BR001 Staff cannot encode a case for BR002.
2. BR002 Staff cannot record payment for BR003.
3. Branch Admin cannot view unauthorized branch payments.
4. Tampered branch parameters cannot bypass restrictions.
5. Legacy temporary permissions do not grant cross-branch recording access.
6. System Admin retains authorized global administrative access.
7. Owner retains read-only access.
8. Local branch recording and synchronization remain functional.

### Required Result

The final system must enforce:

**Staff and Branch Admin = Assigned Branch Only**

No active Temporary Cross-Branch Encoding workflow should remain.

Preserve legitimate global administrative and reporting functionality.

---

## TASK 3 — Database and Integration Safety

Perform a focused review of recent migrations and their integration with the existing system.

Verify:

- Payment submission identity constraints.
- MariaDB index repair.
- Existing case and payment relationships.
- Historical records and transaction identifiers.
- Legacy cross-branch permission dependencies.
- Branch ownership and user assignments.
- Synchronization compatibility between central and branch application versions.
- Migration behavior on a representative database copy, when available.

Do not perform production migrations.

If a verified legacy database export is unavailable, report the migration rehearsal as blocked rather than claiming it passed.

Do not remove historical tables or records merely because their associated feature is no longer active.

---

## TASK 4 — Preserve Deferred Decisions and Existing UI

Do not implement the following as part of this task.

### Returning Clients

Keep existing-client selection and case-specific relationship improvements documented as a separate future feature.

Do not introduce automatic client merging.

### Request Date / Backdating

Inspect and document the existing server-generated recording-date behavior.

Do not introduce a backdating workflow without confirming the business requirement.

### Record Payment Button in Case Details

The previously approved button-removal change is separate from this integration review.

Preserve the latest approved behavior:

- Case Details retains Payment Summary and Transactions.
- Case Details does not contain a Record Payment shortcut.
- Payment Monitoring retains the Record Payment button and modal.

Do not restore the Case Details shortcut because of an older regression expectation.

---

## TASK 5 — Verification and Final Report

Run relevant automated and browser tests after any corrections.

Ensure the previously passing regression coverage and offline synchronization functionality remain intact.

### Required Report

Provide:

1. Verified payment retry and duplicate-prevention behavior.
2. Confirmed payment defects and corrections, if any.
3. Results of repeated-submission, concurrent-payment, correction, and offline-retry tests.
4. Verified Staff and Branch Admin branch-access restrictions.
5. Findings about unused Temporary Cross-Branch Encoding functionality.
6. Changes made to safely disable unused cross-branch access.
7. Confirmation that existing records and historical audit information were preserved.
8. Database and migration compatibility observations.
9. Exact files changed.
10. Final SQLite, MariaDB, and relevant browser test results.
11. Remaining risks and deployment blockers.

Clearly distinguish verified findings from assumptions or untested scenarios.

---

## Implementation Restrictions

- Preserve existing functionality and approved role permissions.
- Do not redesign the system.
- Do not create new payment pages or forms.
- Do not weaken financial validation.
- Do not reintroduce Cross-Branch Encoding.
- Do not remove historical case, payment, or audit records.
- Do not reset synchronization checkpoints.
- Do not modify production data or the current Hostinger deployment.
- Preserve unrelated working-tree changes.
- Apply only targeted corrections supported by verified findings.

## Expected Final Outcome

The application must maintain reliable payment processing and synchronization while enforcing strict branch ownership.

Staff and Branch Admin must operate only within their assigned branch.

System Admin retains authorized system-wide administration, and Owner retains read-only oversight.

**Complete the Payment Integrity Review and implement the approved Assigned-Branch-Only Access policy. Preserve existing functionality, verify all corrections, and report the results before proceeding to deployment preparation.**
## Approved clarifications — October 21, 2026

1. Staff and Branch Admin have assigned-branch-only operational access. Disable unused Temporary Cross-Branch Encoding grants and unauthorized entry points, preserving historical permissions/audits. Preserve System Admin's legitimate paid other-branch reporting and authorized global administration/reporting. Reporting exceptions must not bypass local operational ownership or routine Staff-only write restrictions.
2. Concurrency verification must use actual overlapping requests and independent MariaDB connections/transactions. Cover different submission identities, identical identities, combined overpayment, locks, unique constraints, rollback and final totals/status. Sequential simulations are insufficient evidence.
3. Preserve the latest Case Details UI without its Record Payment shortcut. Use isolated test databases only; do not modify Hostinger production. Legacy migration rehearsal remains blocked until a verified historical export is available.