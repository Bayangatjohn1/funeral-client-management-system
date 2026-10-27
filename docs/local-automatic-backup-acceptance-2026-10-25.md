# Local automatic backup and recovery acceptance review

October 25, 2026, Asia/Manila. **Result: corrected application/scheduler mechanisms pass isolated local acceptance; unattended host operation and off-device recovery objectives are NOT yet accepted.** Readiness remains Local Validation, with operational prerequisites before Hostinger staging/pilot.

No production database/application, historical business records, existing APP_KEY, synchronization credentials/checkpoints, backup interval, retention period or role permissions were changed. Branch scheduling remains hourly; central scheduling remains daily at configured 01:00. No backup-management UI or application role was added.

## Execution method and exact evidence boundary

`scripts/backup-operational-acceptance.php` hardcodes localhost databases `backup_acceptance_20261025` and `backup_replacement_20261025`. All storage/cache/output is confined to `storage/framework/backup-acceptance-runtime`. Those disposable schemas are rebuilt by the harness; it does not accept an arbitrary database target. Do not repurpose it for real data.

The test launches the real Laravel **schedule:work** command. That worker automatically starts schedule:run, which dispatches the registered scheduled backup in a separate process. No Staff/System Admin session or manual `system:backup` invocation initiates that automatic-dispatch test. Later deterministic failure/retry tests invoke the scheduler dispatcher at controlled due times, not the backup command directly.

The process clock is controlled inside the QA processes only, to exercise hourly/daily due times without altering the host clock or waiting through real hours. Outbound application HTTP is forced to connection failure; local MariaDB/dump operations remain available. This proves the scheduler engine and backup code can operate without successful internet HTTP, **not** a physical WAN outage, installed Windows task, service restart persistence or real elapsed hourly recurrence.

The documents/uploads are synthetic byte fixtures. Their preservation/restored storage links are tested; rendering or printing genuine funeral documents on replacement equipment is a separate acceptance step.

Final harness evidence: **29 acceptance checks passed, zero failed**, in `storage/framework/backup-operational-acceptance.json`. Actual scheduler event times: **October 25, 17:08:27–17:08:58 +08:00**. The JSON separately records each real UTC event timestamp, controlled due time, process/phase timings, exit status and assertion evidence. Simulated due times extending into the next day are not represented as actual elapsed hours.

## Per-check execution and findings

| Check | What actually ran and result | Evidence / defect or configuration gap |
|---|---|---|
| Host automatic scheduler configuration | Read-only Windows task inspection found no matching action referencing this app path or artisan schedule:run/work. **NOT VERIFIED for host operation.** | `storage/framework/backup-host-scheduler-observation.json`, checked 16:55:06 +08:00. The scoped search does not exclude indirect wrapper scripts/other services. No task was installed or modified. Provide the actual task/service definition and execution history. |
| Hourly branch schedule | Actual registered branch expression is tested at due hour and following non-due minute. **PASS, controlled clock.** | `schedule_definition_branch_*` results. Hourly cadence is unchanged. |
| Central schedule separation | Registered central daily expression is tested at 01:00 and 01:01; an actual central-mode dispatcher creates a verified scheduled backup. **PASS, controlled QA mode.** | `schedule_definition_central_*`, `separate_central_daily_scheduler_dispatch`. This is a separate mode/process, not a second physical central server. |
| Automatic dispatch without human backup trigger | schedule:work automatically launches schedule:run and the real registered backup command, producing a verified archive with no logged-in user. **PASS.** | `automatic_laravel_worker_dispatch_offline`, real ScheduledTaskStarting/Finished events and verified DB row. |
| Scheduler nonoverlap | Hold the real QA scheduler event mutex; dispatch at its due time; no extra backup record is created. **PASS.** | `controlled_scheduler_mutex`; skipped event/output and unchanged row count. |
| Backup service lock conflict | Hold the shared cross-process backup creation lock while scheduler dispatches. Creation is refused, no competing archive/job row is created. **PASS.** | `controlled_service_mutex`; scheduled child failure is recorded. This is not a new verified backup. |
| Synchronization/backup lock conflict | Hold branch-sync-worker lock; backup is refused until available. **PASS.** | `controlled_sync_mutex`; scheduler logs/exit evidence. Refused/skipped jobs have no new verified archive, and must be detected by operational monitoring. |
| Controlled dump failure | Scheduler uses a deliberately missing QA dump executable. Attempt fails and its failure record survives rollback. **PASS after correction.** | `controlled_dump`; baseline `backup-acceptance-baseline.txt` reproduced the original lost failure record. No real database dump executable/configuration was changed. |
| Insufficient-storage/write refusal | Real dump/archive/encryption runs; only the final test disk adapter returns false for put. Job fails with a write error and is not verified. **PASS, simulated storage rejection.** | `controlled_quota`. No disk was filled, real quota changed or working-server storage damaged. Actual OS/filesystem quota behavior remains equipment testing. |
| Interrupted creation | Kill only the owned QA worker process tree after encrypted archive write, before verification/transaction completion. It is never falsely verified. **PASS.** | `interrupt_phase_reached`, `interrupted_attempt_not_false_verified`, `interruption.json`. This is actual process termination, not power loss. |
| Subsequent scheduler retry | At the next controlled due time, locks have expired, the old attempt becomes failed/unconfirmed and a new attempt verifies successfully. **PASS after corrections.** | `automatic_next_dispatch_retries_and_classifies_interruption`. The application does not add an immediate backup retry/backoff feature: the next normal scheduled dispatch or existing authorized manual workflow is the retry path. |
| Missing/corrupted archive status | A previously verified missing file no longer remains verified. Checksum mismatch, wrong-key fixture and invalid ZIP are rejected and recorded failed. **PASS after correction.** | `AutomaticBackupAcceptanceTest`; full-suite results below. Wrong-key fixtures use a separate test encrypter; existing application keys are unchanged. |
| Archive integrity | Compare encrypted SHA-256, decrypt with matching key, open with ZIP consistency check, read expected file bytes and import actual SQL. **PASS.** | `encrypted_archive_checksum`, `decrypt_and_zip_consistency`, `required_recovery_files`. |
| Files and application storage restoration | Extract into isolated replacement directory; configure replacement disks to the extracted private/public roots and read private document, public upload and immutable pending blob through Laravel Storage. **PASS.** | `isolated_file_restore`, `replacement_application_storage_uses_restored_files`. Same machine/different directory is not off-device protection. |
| Database/relationship restoration | Actual mysql client imports SQL into the named replacement database; verify cases, payment originals/replacement, approved correction, audit row and document/correction foreign links. **PASS.** | `restored_cases_payments_correction_and_audit`, `restored_document_and_correction_relationships`. No production schema is used. |
| Financial reconciliation | Source/restored cohort: 3 cases, 16 payment rows including original VOIDED payment and replacement, one approved correction; valid posted sum and case paid sum 2,950.00, balance 50.00. **PASS.** | `restored_financial_totals`; originals/relationships retained, no duplicated payment. |
| Keys/users/authorized access | Restored password hash verifies the fixture password, encrypted MFA secret decrypts with the unchanged key, Staff role/branch/active flag and Branch Admin role remain intact. **PASS.** | `restored_password_role_and_key_encrypted_mfa`. This is credential/authorization-data verification, not a physical replacement-device login/OTP demonstration. Existing HTTP permission/security tests also pass. |
| Pending sync data | Outbox decrypts; original version/sequence/fingerprint, installation identity, mapping count and unacknowledged state match. Pending immutable bytes restored. **PASS.** | `restored_pending_checkpoint_and_mappings_unchanged`. No versions/checkpoints were reset or forced. |
| Competing writer prevention during recovery | Authorized restore procedure explicitly sets a recovery hold on the isolated clone, preserves its checkpoint, blocks outbound HTTP and verifies operational POST returns 409. Original QA scheduler processes are stopped. **PASS for this isolation procedure.** | `isolated_restored_clone_write_hold`. Automatic detection/fencing of two identical active clones is not implemented; stopping/fencing the original and applying the recovery hold remain mandatory operator steps. Do not start both writers. |
| Automatic off-device copying | Observed backup configuration is local/system-backups, 14-day retention. No independent destination, replication/copy worker, transfer acknowledgment/checksum retry automation was provided/configured. **GAP / transfer tests BLOCKED.** | `backup-config-observation.txt`; source review. A second local folder and the replacement test directory are not independent storage. |
| Operational failure alerts and central visibility | Failure status persists locally and Laravel reports scheduled command failures. No verified external alert delivery, heartbeat or off-device freshness monitor. Central Backup & Recovery reads its own database; system_backups/restore_requests are not sync payload entities. **GAP.** | `BackupRecoveryController::index`, `SyncSchema::OPERATIONAL`, scheduler events/logs. Do not claim central System Admin automatically sees every branch backup failure. Provide an approved external monitoring/escalation path without widening Branch Admin permissions. |

## Confirmed defects corrected

1. **High — branch failure history disappeared.** The running row and failed update were inside the operational transaction, so throwing rolled them back. Baseline found zero rows instead of a failed attempt. Attempt creation now precedes that snapshot transaction; failure recording occurs after rollback. Operational/file consistency locks are retained.
2. **High — invalid archive could retain verified status.** Re-verification threw but left a missing/damaged archive marked verified, allowing stale success reporting. Verification failure now marks it failed with a protected failure reason. Expired records retain their expired classification; active restore requests are not removed.
3. **Medium — scheduler/service mutex-window mismatch.** The former default scheduler mutex lasted 24 hours while the service lock lasted 15 minutes; an abruptly killed scheduler tree could suppress subsequent hourly runs long after the service lease expired. The scheduler mutex now explicitly matches **15 minutes**. This changes only overlap lease duration, not backup frequency, retention or recovery policy.
4. **Medium — interrupted attempt remained indefinitely running.** On the next backup attempt, older unfinished rows are classified failed with **completion unconfirmed / inspect worker logs**. This is not proof that a process stopped and does not kill a worker or validate an incomplete archive. External monitoring must detect the interruption before the next hourly dispatch when required by RPO.

The service also explicitly rejects a false encrypted-storage write result instead of proceeding as though storage succeeded. Failure/verification diagnostics use existing status fields. No schema migration, screen, role, policy or payment workflow was added.

### Important execution limits

Job execution must complete within its shared 15-minute lease (dump subprocess already has a 600-second timeout); benchmark real archive size, I/O and verification before staging sign-off. Lease expiry is not a process supervisor. Long-running workers beyond the lease need operator/supervisor investigation and an explicitly reviewed timeout/lease design; this small fixture does not certify large jobs or permanent network/storage stalls.

A scheduler dispatch can return successfully while an individual scheduled command fails. Monitor task failure events/logs, actual backup status and last verified recovery point, not merely the dispatcher exit code. Scheduler skips/lock refusals do not create a new verified record. No external alert destination or immediate automatic backup retry service was added.

An abruptly killed process cannot run finally cleanup. Its private backup-work SQL/ZIP or unverified encrypted archive can remain for forensic review; automatic orphan cleanup is not implemented. Inspect/secure these files under the private path and approved retention procedure. Never delete pending sync blobs/checkpoints to clean up backup artifacts. Historic backup archives are deliberately excluded from new archives to prevent recursive growth; preserve required archive history separately. Backup-catalog rows are a point-in-time database snapshot and the archive being created can appear as running in its own SQL dump; restoration uses the separately verified selected artifact/manifest, not that row as proof of completion.

## Measurements and recovery objectives

For the synthetic 3-case/16-payment/correction/document cohort, three successful scheduled command processes recorded real backup creation/dump/ZIP/encryption/write aggregates of **0.682–0.716 s**, verification **0.012–0.020 s**, total **0.695–0.736 s**. These are phase timestamps captured by the QA wrapper, not virtual-clock model timestamps. They do not establish production archive size, a complete hardware restore time or provider performance.

The existing hourly branch schedule is unchanged. Strict off-device recovery-point age includes the interval, backup/encryption time, transfer, independent verification and failure-response delay. Even this tiny positive completion duration leaves no lag budget in a 60-minute cadence:

`worst-case off-device age ≈ capture interval + completion/transfer/verification lag`.

Off-device transfer duration and retry performance are unmeasured because no approved independent destination exists. Therefore **one-hour off-device RPO is not accepted**. No alternative cadence or retention policy was automatically applied. Recommendations for approval after real-volume measurement: either choose a shorter coordinated cadence with a bounded lag/response budget (for example the earlier 30+20+10-minute planning budget), or explicitly accept a measured longer RPO. Do not install duplicate backup triggers.

A failed/skipped hourly run without prompt successful intervention can add another hour or more; repeated failure has no finite recovery-point bound. Scheduler presence alone is therefore not an RPO guarantee.

Keep the previously proposed monitoring policy as an acceptance requirement, not a claim of implementation: poll at least every five minutes, warn at recovery-point age 40 minutes, critical at 50 and breach above 60, measured from conservative snapshot/backup-start time of the latest verified OFF-DEVICE copy. Alert immediately on failures/skips and validate retry/escalation. Uploading an old archive must not reset its recovery-point age. Configure recipients/escalation for System Admin oversight and authorized deployment administration; do not grant Staff/Branch Admin backup management.

## Regression results

| Verification | Exact result | Evidence |
|---|---|---|
| Baseline defect reproduction | Two expected failures: lost branch failed-attempt row and missing archive still verified | `storage/framework/backup-acceptance-baseline.txt` |
| Full SQLite, run first | **425 tests, 3,247 assertions, zero failures/errors; two MariaDB-only skips**, 67.619 s | `backup-acceptance-sqlite.xml` / `.txt` |
| Full MariaDB, run after SQLite | **425 tests, 3,267 assertions, zero failures/errors/skips**, 122.608 s | `backup-acceptance-mariadb.xml` / `.txt` |
| Final operational harness | **29 acceptance checks passed, zero failed** | `backup-operational-acceptance.json`; detailed raw events/phase timings in the confined QA runtime |

Full regressions include existing payment/correction, authorization, System Admin backup/retention, legal-hold, offline sync and recovery tests. New `AutomaticBackupAcceptanceTest` adds five regression methods for the verified corrections and invalid-archive rejection. PHP syntax and scoped whitespace checks passed. No new UI was introduced and no fresh browser/UI certification is claimed.

## Operational configuration and evidence still required

- [ ] Install/identify the approved host scheduler/service with correct PHP binary, application working directory, least-privileged service identity, minute dispatcher, persistent cross-process cache/locks and restart persistence. Save its actual task definition, two or more **real hourly** branch execution records, and separate real daily central evidence.
- [ ] Reboot the real branch equipment and verify web/DB/scheduler startup without an interactive login. Repeat with WAN disconnected while LAN/power remain operational. Browser HTTP failure injection does not replace this.
- [ ] Configure approved independent storage and secure automated copying (or a separately verified direct remote disk destination). Test real bytes/checksum, decryption with the matching key, unavailable destination, retry, alert delivery and recovery-point freshness. Default local archive verification does not implement this pipeline.
- [ ] Configure external job/backup/transfer monitoring and System Admin escalation, including local branch health that the central backup catalog does not currently aggregate. Validate actual notification delivery; no message was sent to anyone by this review.
- [ ] Preserve matching APP_KEY, sync credentials/installation identity and release metadata in approved separate custody. No secret values belong in logs/report. Do not rotate keys or reset checkpoints merely to make a restore work.
- [ ] Size/benchmark representative full DB/uploads/documents/audits, archive working space, memory, private leftover artifacts and 14-day retention. Validate execution stays within accepted lock/runtime limits and that storage-failure detection works under the real service identity.
- [ ] Restore the entire bundle onto real isolated replacement equipment; configure its storage roots/runtime/keys and access; prove original-writer fencing, recovery hold, financial/authorization integrity, and authorized checkpoint reconciliation before releasing writes. Measure the complete proposed <=2-hour restore target, not just SQL import.
- [ ] Agree the actual RPO/cadence/lag and response budget with the business before any configuration change. Keep retention policies and legal holds unchanged.

Before Hostinger staging, carry forward [the staging audit](staging-feasibility-audit-2026-10-22.md): actual account PHP/CLI/process/dump permissions, packet/memory/body/time limits, TLS/proxy/storage/scheduler checks, verified legacy DB/files/key export and migration rehearsal, representative capacity and dependency disposition. The earlier verified local 1 MiB packet gate and missing historical export were not resolved by this backup test. No Hostinger account, independent storage or physical recovery was certified here.

## Exact files changed

1. `app/Services/SystemBackupService.php` — retain failed attempt history outside branch rollback, reject false storage write, update invalid verification status and classify expired unfinished attempts as unconfirmed.
2. `bootstrap/app.php` — match backup scheduler overlap mutex to existing 15-minute service lease; hourly/daily cadence unchanged.
3. `tests/Feature/AutomaticBackupAcceptanceTest.php` — five focused regressions; no role/permission changes.
4. `scripts/backup-operational-acceptance.php` — bounded isolated scheduler/failure/interruption/integrity/restore harness with real timestamps and explicit limitations.
5. `docs/local-automatic-backup-acceptance-2026-10-25.md` — this acceptance evidence, gaps and recommendations.

Generated logs/config observations remain under `storage/framework/backup-*` and the confined runtime directory. Existing records/keys/checkpoints and unrelated working-tree modifications were preserved. **Automatic application backup execution is locally demonstrated; unattended/off-device operational readiness remains conditional and has not been signed off.**
