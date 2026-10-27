# Targeted payment edge cases and next staging-readiness review

October 22, 2026, Asia/Manila. This follows the accepted [local integration review](payment-integrity-branch-access-report.md); it does not reopen the resolved 20 regression failures. No production installation, Hostinger configuration, database or synchronization checkpoint was modified. Case Details and Payment Monitoring UI files are unchanged in this follow-up.

## 1. Concurrent payment and correction approval

Extended `scripts/payment-integrity-concurrency.php` with eight mixed-operation scenarios: standalone/branch modes, correction decrease/increase, and reversed worker launch order. Workers use independent PHP HTTP-kernel requests and MariaDB connections/transactions. The coordinator requires proof of overlapping active locking queries and active PDO transactions before releasing its fixture lock. This is actual database concurrency, not sequential request simulation; it is not an external TCP load test.

Starting case total: 1,000; original posted payment: 400. A normal payment of 500 races with approval:

| Correction | Required serially consistent outcome |
|---|---|
| Decrease original 400 to 300 | Both compatible operations commit: original VOIDED, exactly one 300 replacement and one new 500 payment, paid 800, balance 200, PARTIAL. |
| Increase original 400 to 600 | Exactly one operation safely rejects when both cannot fit: either new payment commits and correction remains PENDING (paid 900/balance 100), or correction commits and new payment is rejected (paid 600/balance 400). |

All eight mixed scenarios passed, together with the existing eight scenarios. Assertions compare valid posted-payment sums against persisted case totals/status, count normal/replacement payments and check correction status. No overpayment, duplicated payment, lost committed update or inconsistent status was observed. No production payment/correction locking change was necessary. Reversed launch order is not a guarantee of scheduler/commit order; the JSON records the observed results.

Evidence: `storage/framework/payment-edge-concurrency-baseline.txt` (initial successful run) and `storage/framework/payment-edge-concurrency.json` (final 16-scenario run). Actual MariaDB: 10.4.32. Unique-index rejection and transaction rollback checks remain included.

## 2. Initial intake response-loss recovery

**Confirmed gap:** the first POST committed one case and its initial payment. After discarding the response and clearing success-session state, the same POST returned generic duplicate-client validation instead of identifying the saved case. It did not create another payment in the reproduced scenario, but the Staff recovery path was unclear. Evidence: `storage/framework/intake-timeout-baseline.txt`.

**Small correction:** before duplicate-person validation, an initial-payment retry looks up its UUID strictly within the authenticated operational branch. It checks the stored intake payment hash, client/deceased names and entry source, authorizes viewing the case, then redirects to the existing Case Details page with its case code and an already-saved message. It creates no new row and applies none of the retry's edits. Changed payment details under the same identity are rejected. Hash generation is shared with initial-payment persistence and remains compatible with identities created by the previous release.

Feature coverage commits and discards the original response, removes success-session state, repeats the request, verifies the original case redirect and one case/payment, checks the existing Transactions section, and rejects a changed amount under the same key. The browser test submits a real request while intentionally ignoring its success navigation, retries it, and opens the recovered case URL. This models an unacknowledged UI result, not a physical dropped TCP packet. No intake wizard redesign, new page or payment-entry shortcut was added.

**Limits:** recovery needs the retained submission UUID and an authorized, readable case. It does not merge people or reconstruct missing identifiers. Legacy unkeyed requests still use existing duplicate validation and Staff should search their assigned branch's Case Records by client/deceased before retrying with new details. A catalog or validation-rule change before a later retry can still fail validation before recovery; use Case Records and review the saved case rather than altering the payload to force a new intake. A deleted/unavailable record or changed payment identity is not silently recreated. An intake without an initial payment has no persisted payment identity and retains its current duplicate-check/Case Records recovery path.

## 3. Legacy temporary permission authorization audit

Reviewed `User` helpers, the legacy permission model, branch-scope middleware/global model scope, case/payment policies, payment/correction controllers, intake/draft/schedule-advisory paths, case lists, sidebar references, branch deployment middleware and synchronization ownership checks.

- No operational authorization path consumes a historical temporary grant. `activeTemporaryPermission()` returns null, `scopeActive()` cannot return records, and active grant creation/reactivation remains blocked. Raw historical flags remain intact.
- `canEncodeAnyBranch()` resolves only existing System Admin authority; it does not consult temporary grants or the legacy user flag. Its current references support global administrative/reporting behavior. Staff/Branch Admin scopes use their actual assigned branch.
- Other-branch intake/report entry points enforce System Admin checks. Draft branch resolution does not allow Staff to choose another branch. Staff-only recording middleware is retained; administrative reporting does not open ordinary payment posting.
- Branch Admin correction approval checks assigned branch; immutable report-payment guards and central frozen/active ownership controls remain intact. Sync validates token/installation identity and cross-branch graph references.
- Existing BR001/BR002/BR003 legacy-grant/tampered-parameter regression coverage is rerun. It verifies no foreign operational access and preservation of the historical grant rows. Legitimate System Admin paid-report tests remain in the selected suite. No further permission fix, table deletion or destructive migration was necessary.

## Changed files in this follow-up

1. `scripts/payment-integrity-concurrency.php` — mixed payment/approval race scenarios and financial invariants.
2. `app/Http/Controllers/Staff/IntakeController.php` — keyed recovery to the saved case and shared initial-payment hash helper.
3. `tests/Feature/SystemRegressionGuardsTest.php` — committed-response-loss recovery regression.
4. `tests/Browser/IntakeTimeoutRecoveryTest.php` — ignored-success-navigation/retry browser coverage.
5. `docs/payment-edge-cases-and-staging-readiness.md` — findings, results and readiness gates.

## Verification results

| Check | Result | Evidence |
|---|---|---|
| Relevant SQLite suites | 94 tests, 728 assertions, no failures/errors; two MariaDB-only skips | `storage/framework/payment-edge-sqlite.xml` / `.txt` |
| Relevant MariaDB suites | 94 tests, 748 assertions, no failures/errors/skips | `storage/framework/payment-edge-mariadb.xml` / `.txt` |
| Independent MariaDB concurrency | 16 scenarios passed, including eight new mixed payment/approval scenarios | `storage/framework/payment-edge-concurrency.json` |
| Browser recovery and four-role workflows | Two tests, 60 assertions, all passed; 46.422 s | `storage/framework/payment-edge-browser.txt` |

The selected suites are `SystemRegressionGuardsTest`, `BranchSyncTest` and `AdminRecoveryWorkflowTest`, covering keyed retries, legacy grants, System Admin reporting, correction integrity, independent receiver reconciliation, lost acknowledgments and actual MariaDB backup restoration. These are targeted results; the earlier accepted full-suite 419-test evidence remains historical and is not presented as a fresh full-suite run for this follow-up.

Observed increasing-correction outcomes in the final concurrency run: standalone committed the 600 replacement and rejected the new payment; branch mode committed the 500 payment and rejected approval. Both fit the serial outcomes above. Every decreasing-correction scenario committed both compatible operations with paid 800/balance 200.

PHP/Blade source whitespace checks passed for the touched tracked files. No frontend bundle, Case Details template or Payment Monitoring template was changed. The browser tests exercise the unchanged approved payment entry point, successful recording and replay, all four roles, and the new intake recovery behavior. No physical WAN outage, real branch pilot or Hostinger deployment is claimed.

## Next staging-readiness gates

**Classification: Local Validation; staging acceptance remains pending.** Prepared materials do not establish hosting compatibility or legacy upgrade success.

| Area | Available evidence | Remaining prerequisite / acceptance evidence |
|---|---|---|
| Hostinger runtime | Project requires PHP ^8.2 and Laravel ^12; local PHP 8.2.12/MariaDB 10.4.32 testing. Read-only preflight script and deployment runbook exist. | Isolated staging site/database and actual account/plan details. Record web-SAPI AND CLI versions/extensions, Composer platform checks, document root to public/, storage/cache access, disabled functions, private files, HTTPS/trusted proxy/session behavior, and matching deployed assets/schema. No account-specific Hostinger compatibility was verified here. |
| Scheduling/transport | Existing minute scheduler, bounded sync requests, durable outbox and retry tests. | Verify the actual permitted cron cadence, PHP executable and nonoverlap behavior. Exercise central/branch HTTPS authentication, proxy/body/time limits and automatic restart using staging endpoints only. |
| Verified legacy migration | Fresh isolated SQLite/MariaDB migrations pass in local validation. No new migration introduced by this follow-up. | **Blocked:** verified historical database export and matching application/upload/key metadata are unavailable. Obtain checksum, schema/version and row/count/financial baselines; restore an isolated copy; rehearse migrations and rollback/recovery; compare constraints, original IDs, case/payment history, totals, documents and checkpoints. Fresh migrations are not a legacy rehearsal. |
| Snapshot capacity | Historical synthetic MariaDB fixture: 1,000 cases/5,000 payments, 8,725,217 plaintext bytes, 672,084 wire bytes; export 14.72 s, capture 5.66 s, apply 42.78 s, peak 196 MiB. 2,500-case SQLite fixture exceeded 20 MiB and failed safely. | Measure representative full branch history and files on staging, including accumulated offline payments/corrections and audit/documents. Retain the 20 MiB plaintext and 10 MiB/file limits; require packet capacity >=64 MiB. Local preflight previously observed only 1 MiB, so that deployment gate is not met. Validate web memory/runtime and request/proxy limits with headroom; 512 MiB is a starting resource-review target, not a proven plan requirement. Do not infer real WAN transfer time from compressed bytes. |
| Storage retention | Immutable blobs/outboxes and full-backup retention are documented; synthetic benchmark had no attachment spool. | Budget actual upload/private/spool growth plus 14 days of hourly branch archives. Define monitored free-space thresholds and approved cleanup; automated orphan-blob GC is not implemented. |
| Backup/recovery | Earlier actual encrypted MariaDB backup/import fixture restored 3 cases/15 payments, public/private files, mappings and pending outbox, then replayed a lost acknowledgment. Selected regression suite reruns backup/recovery tests. | Provision database dump/import tools and approved execution permissions, secure APP_KEY custody, off-device checksummed copies, monitoring and a complete replacement-server restore with real volumes. Restore database, uploads, pending payloads, mappings, checkpoints and matching keys together; never reset a checkpoint to force sync. |
| Recovery targets and pilot | Existing runbook proposes RPO <=1 hour, alert at >90-minute verified off-device backup age, replacement-server restore <=2 hours. These are proposals, not measured business sign-off. | Agree the data-loss window and restore target; demonstrate them on real equipment. Then complete LAN outage/restart/reconnect, multiple-branch operation and the five-consecutive-working-day pilot. Same-disk backups do not satisfy off-device protection. |

Capacity/preflight/recovery numbers above are retained **October 21 synthetic/local measurements**, not newly measured Hostinger results: `storage/framework/offline-capacity-mysql-1000.json`, `offline-preflight-local.json`, `offline-restore-measurement.json` and [the earlier review](offline-branch-review.md). Actual workload sizing remains outstanding.

Hostinger permits PHP settings through hPanel within the hosting plan's ceilings; the account's PHP Info/plan must be inspected. Official references reviewed October 22: [PHP parameter configuration](https://www.hostinger.com/support/4622479-how-to-change-values-of-php-parameters-in-hostinger/), [PHP memory inspection](https://www.hostinger.com/support/1583711-what-is-php-memory-limit-at-hostinger/), [plan limits](https://www.hostinger.com/support/6976044-parameters-and-limits-of-hosting-plans-in-hostinger/). These documents do not certify the user's account or authorize production changes.

Use [the deployment/recovery runbook](offline-branch-deployment.md) and [physical acceptance checklist](offline-branch-acceptance.md) for the controlled staging/pilot steps. Existing dependency-advisory disposition remains a release prerequisite. Production deployment is outside this task.
