# Payment integrity and assigned-branch integration review

Dates: October 21–22, 2026 (Asia/Manila). Scope: the [approved instruction and clarifications](payment-integrity-branch-access-instructions.md).

## Outcome and boundaries

The existing local-branch/single-writer synchronization architecture is retained. Staff and Branch Admin continue to use their authenticated assigned branch. System Admin paid other-branch administrative reporting remains authorized; it does not grant Staff cross-branch recording or bypass activated-branch ownership. No production application, environment, credentials or database was modified. Historical permission rows, case ownership, payment originals, audit records and sync checkpoints were not removed or reset.

The latest Case Details page remains unchanged: Payment Summary and Transactions remain, and the Record Payment shortcut remains removed. Payment Monitoring's visible interface and modal are unchanged. Returning-client selection and case-specific relationships remain deferred; no automatic merging was added. The server-generated recording date remains authoritative; no backdating workflow was introduced.

## Confirmed findings and targeted changes

| Finding | Severity / evidence | Correction |
|---|---|---|
| A correction could approve an amount exceeding the case total/balance. | High. Reproduced: a replacement of 11,000 against a 10,000 case returned success instead of 422. Baseline: `storage/framework/payment-integrity-reproduction.txt`. | Approval locks the affected case, excludes voided payments, computes remaining capacity in cents and rejects an excessive/nonpositive replacement. The entire transaction rolls back, preserving the original payment, pending request and approval audit state. |
| Replacement payment history inherited the original balance-after/status snapshot, including null/stale values. | Medium. Verified from `replicate()` and the original fixture; covered by exact replacement-history assertions. | Calculate the replacement balance/status from the target case and valid posted payments using the shared `paymentTotalsFor()` derivation. |
| Pending-correction existence check and insert were not serialized. | Concurrency risk found in code: both requests could pass the pre-transaction check. The former sequential test could not establish safety. | Lock/reload the original payment and perform the pending check, creation and audit in one retriable transaction. Real overlapping correction requests now leave one pending request. Existing approval request/payment locks still permit only one replacement. |
| An OTHER_BRANCH report's payment could enter the correction path despite being locked against ordinary payment updates. | High, backend-path inconsistency verified in the correction controller. | Reject correction requests/approvals for these immutable administrative payments, including historical pending requests. Reporting creation/view routes and System Admin authority remain intact. |
| Intake's optional initial payment lacked the submission identity used by Payment Monitoring. | Medium, verified active form/controller path gap. | Add a hidden UUID to the existing intake form and persist identity plus namespaced canonical payment hash atomically with its initial payment. Legacy requests remain accepted. No visible form or new page was added. |
| Legacy temporary permission helpers could still return an “active” historical grant. | Policy hardening; no current branch-scope privilege expansion was demonstrated. No live granting routes or UI were found. | `activeTemporaryPermission()` returns null; `scopeActive()` returns no rows; model-level creation/reactivation of active grants is rejected, including default-active inserts. Historical relationships remain readable and raw historical flags are preserved. Legacy active labels identify disabled grants. |

## Payment creation and retry review

- Payment Monitoring generates a UUID, retains it on validation failure, and generates a fresh form identity after a successful redirected submission. Canonical hashes reject a reused identity with changed details. The case transaction lock and unique `(branch_id, submission_id)` remain authoritative. Legitimate equal amount/date payments with distinct identities remain allowed.
- Payment amount/details/branch authorization and current balance are validated by the existing payment controller. Real concurrency exercised its existing implementation; no relaxation or replacement of its locking/calculation logic was needed.
- Corrections preserve original transactions through VOIDED plus a separately numbered replacement. Submission identity/hash are not copied onto a correction replacement. Repeat approvals return conflict, with one replacement and one approval audit. Unauthorized/foreign reviewers remain rejected.
- Initial intake payments belong to the atomic case-creation transaction. The new UUID is attached to that payment. Whole-intake retry is **not** a successful idempotent replay API: existing duplicate-client/case validation can reject a retry before reaching payment creation. A reused identity reaching another insert is rejected by the unique index and the case transaction rolls back. Do not promise timeout recovery identical to Payment Monitoring for the entire intake workflow.
- Requests without identities retain compatibility. They cannot be recognized as the same submission solely by amount/date; two unkeyed accepted requests may be legitimate separate payments. Operators/integrations must retain and reuse the UUID for reliable retry deduplication. Legacy payment rows are not backfilled with invented request identities.

## Actual concurrency method and outcomes

Executable harness: `php scripts/payment-integrity-concurrency.php`. It hardcodes localhost and **only** `payment_integrity_validation_20261021`, creates/fresh-migrates that disposable database, and never accepts a caller-selected database. It must not be repurposed for production.

Each worker is a separate PHP process, opens a separate MariaDB connection, and sends its own request through Laravel's HTTP kernel and route middleware/controller. Authentication is seeded as a completed test session; this is not a TCP/web-server load test. Browser tests separately verify real sign-in and the visible form flow.

The coordinator holds a fixture lock while both workers enter their transactions. The harness requires distinct connection IDs and overlap evidence before releasing the lock. It checks `INNODB_LOCK_WAITS`; this MariaDB installation sometimes reports native prepared locking queries in `SHOW FULL PROCESSLIST` as `Statistics`, before listing child transactions in `INNODB_TRX`. In that case it requires **both** live simultaneous `SELECT ... FOR UPDATE` queries plus independently reported positive Laravel transaction depth and active PDO transaction state. It does not infer concurrency merely from process creation or elapsed duration. The retained JSON identifies which proof was used and records the actual queries/connections.

| Scenario | Expected and verified final result |
|---|---|
| Two Staff, distinct identities, 500 + 500 on one 1,000 case | Two payments, paid 1,000, balance 0, PAID. |
| Two Staff, identical identity and 400 payload | One payment, paid 400, balance 600, PARTIAL; retry succeeds without another insert. |
| Two Staff, distinct identities, 700 + 700 on one 1,000 case | One payment, paid 700, balance 300, PARTIAL; one request has validation errors, no extra payment/lost update. |
| The same three scenarios in local branch mode | Same financial results; branch installation/case locks and dirty-state handling participate in the actual requests. |
| Concurrent correction requests | One pending request; responses 302 and 422. |
| Concurrent approval of that correction | One replacement, original VOIDED, paid 300/balance 700; responses 302 and 409. |

The harness also attempts a direct duplicate identity insert to verify the actual MariaDB unique constraint rejects it and that transaction rollback leaves payment count unchanged. This complements the controller replay tests rather than assuming locking itself proves the constraint exists.

## Branch and synchronization review

- `User::branchScopeIds()` and `paymentWriteBranchIds()` use actual assigned IDs for Staff/Branch Admin. The legacy `can_encode_any_branch` flag and historical grants do not widen those scopes. BR001 is not a Staff fallback; legacy System Admin detection/reporting compatibility remains separate.
- Regression coverage cycles BR001/BR002/BR003 with historical grants and tampered parameters. Foreign case details, other-branch intake and payment recording remain denied; Branch Admin payment view data stays scoped to its assigned branch. Other tests preserve Staff-only intake/payment recording, read-only Branch Admin catalogs, System Admin global administration/reporting and Owner read-only reporting.
- No live temporary-grant route/UI was found to remove. Retaining the model/table for history does not retain active encoding authority. Raw legacy rows with `is_active=true` remain stored but cannot authorize operations through the supported helpers. Direct SQL can bypass Eloquent saving events, but cannot make branch scope consume these records.
- Branch middleware limits authenticated local users to the installed branch, checks activation/account freshness and holds the installation lock for operational writes. Central frozen/active branch writes remain blocked, including reporting requests aimed at an activated owner; legitimate reporting for permitted central-owned records remains available.
- Central sync validates token/installation branch ownership and graph references. Existing full-suite coverage includes independent receiver databases, lost acknowledgments, retry/rejection, corrections while local, checkpoint recovery and encrypted MariaDB backup restoration. This review does not claim a physical WAN outage or real two-computer LAN pilot.

## Database compatibility

No new migration was introduced. Fresh SQLite/MariaDB suites exercise the existing nullable payment-identity migration and MariaDB deceased-index repair. Legacy unkeyed rows remain readable; no name-based payment deduplication or destructive historical-table migration was added. Existing schema fingerprints require matching central/branch schema versions, and durable pending snapshots/checkpoints must be preserved during coordinated upgrades.

**Historical deployment migration rehearsal: Blocked.** No verified historical Hostinger database export was available. Fresh test migrations are not evidence that a representative old deployment has been rehearsed. The older migration files contain historical table/index behavior; do not rerun them manually against existing production data to disable a feature.

## Exact files changed in this review

1. `docs/payment-integrity-branch-access-instructions.md` — original instruction plus approved clarifications.
2. `app/Models/User.php` — disable active temporary-permission resolution.
3. `app/Models/TemporaryCrossBranchPermission.php` — block new/reactivated active grants; preserve historical access/labels.
4. `app/Http/Controllers/PaymentCorrectionController.php` — serialize request creation; reject report-payment mutation/overpayment; correct replacement history snapshot.
5. `app/Http/Controllers/Staff/IntakeController.php` — validate/persist optional submission identity for initial payment.
6. `resources/views/staff/intake/_form.blade.php` — hidden initial-payment identity only.
7. `tests/Feature/AdminRecoveryWorkflowTest.php` — overpayment rollback, repeat requests/approval, report immutability, replacement balance/status.
8. `tests/Feature/SystemRegressionGuardsTest.php` — legacy-grant branch matrix, disabled grants and keyed initial payment.
9. `scripts/payment-integrity-concurrency.php` — overlapping independent MariaDB request/transaction harness.
10. `tests/Feature/BranchSyncTest.php` — offline correction reconciliation and repeated apply on an independent receiver.
11. `docs/payment-integrity-branch-access-report.md` — this report and verification results.

Unrelated working-tree modifications were preserved. Logs under `storage/framework/payment-integrity-*` are verification artifacts.

## Verification results

| Check | Result | Retained evidence |
|---|---|---|
| Full SQLite Unit/Feature | 419 tests, 3,211 assertions, no failures/errors; two MariaDB-only skips; 51.153 s | `storage/framework/payment-integrity-sqlite.xml` / `.txt` |
| Full MariaDB Unit/Feature | 419 tests, 3,231 assertions, no failures/errors/skips; 137.351 s | `storage/framework/payment-integrity-mariadb.xml` / `.txt` |
| Real MariaDB concurrency | Eight overlapping scenarios passed; direct submission uniqueness rejection and rollback verified | `storage/framework/payment-integrity-concurrency.json` |
| Four-role browser workflow | One test, 49 assertions passed; 38.331 s | `storage/framework/payment-integrity-browser.txt` |
| Offline local-assets browser | One test, 79 assertions passed; 21.918 s; Staff/Branch Admin with internet HTTPS origins blocked | `storage/framework/payment-integrity-offline-browser.txt` |
| Central freshness browser | One test, 58 assertions passed; 16.235 s; Owner/System Admin, light/dark and desktop/mobile | `storage/framework/payment-integrity-central-browser.txt` |
| Focused initial correction/access run | 48 tests, 470 assertions passed before final additional history/report guards | `storage/framework/payment-integrity-targeted.xml` / `.txt` |

The final full SQLite/MariaDB suites execute the existing offline-sync regression methods. SQLite's engine-specific skips must be covered by the actual MariaDB run, not treated as verified under SQLite.

Browser total: **three tests, 186 assertions, all passed**. The payment workflow checks real sign-in, absence of the Case Details shortcut, recording through Payment Monitoring, repeated submission without another payment, final balance and reminder navigation across the four roles/themes/viewports. PHP syntax checks and scoped `git diff --check` also passed. No CSS/JavaScript bundle was modified, so an additional frontend build was not required for these PHP/Blade-only corrections.

Added integration coverage records and approves a correction locally while the mocked network is disconnected, preserves its pending outbox, then applies the exported graph twice to a separately migrated receiver with different branch primary keys. It verifies the original VOIDED row, identity, single replacement, replacement balance snapshot, case totals and APPROVED request linkage. Network acknowledgment/retry/checkpoint behavior is covered by the existing separate tests; the direct receiver apply is not presented as a physical network restoration.

## Remaining release limitations

Readiness remains **Local Validation**. Outstanding work includes verified old-export migration rehearsal, real Hostinger/PHP/scheduler/HTTPS compatibility, actual LAN outage/restart/reconnect and replacement-server recovery acceptance, off-device backups/key custody, and the five-consecutive-working-day branch pilot. Previous dependency advisory disposition remains a release requirement. No staging, physical pilot or production readiness is claimed.
