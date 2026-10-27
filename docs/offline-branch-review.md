# Offline branch final validation and release review

**Regression follow-up:** the 20 failures listed below are the retained earlier checkpoint. See [the per-failure correction report](system-regression-fixes-report.md) for subsequent investigation, corrections and current SQLite/MariaDB/browser results. Physical staging and pilot requirements still apply.

21 October 2026. **Readiness: Local Validation.** Controlled staging materials are prepared, but this release is not Staging-Ready, Pilot-Ready or Production-Ready. The old Hostinger application/database/credentials/configuration were not modified. No real application .env was activated, no production migration or deployment occurred, and no sync checkpoint was deleted or reset.

## Architecture verified from source

| Question | Actual implementation/evidence | Practical boundary |
| --- | --- | --- |
| One application/shared database per branch | Branch mode uses one Laravel database connection, shared operational tables, database sessions/cache, source-row locking and a persistent outbox. | Independent browser computers access the same server through a configured LAN hostname. Two actual LAN computers have not been demonstrated here. |
| Same Staff/Branch Admin application online/offline | Existing web routes/controllers operate on the branch database. Outbound HTTP is isolated to BranchSyncService; local operational controllers do not call Hostinger. | Requires local Apache/PHP, MariaDB, LAN, power and valid local accounts. It is not a per-device PWA. |
| Offline authentication/assets/services | Provisioned password/MFA, bounded offline account window, local fonts/Flatpickr/Chart.js and compiled assets. Browser tests block external HTTPS origins. Minute scheduler drives synchronization; no permanent sync queue worker required. | Physical scheduler/service auto-start, trusted LAN TLS, server clock and actual WAN outage remain acceptance work. |
| Central data consistency | UUID mapping, transactional apply, version/hash acknowledgment, immutable audits, branch ownership and exact-cent financial reconciliation; independent receiver DB/bootstrap tests. | Central operational copies are last accepted branch data; central catalogs/accounts/legal holds remain centrally authoritative. Actual two-machine HTTPS transport is not yet certified. |
| Existing workflows/roles | Staff intake/payment permissions, read-only Branch Admin catalogs, owner read access, historical prices/documents, catalog status rather than inventory are retained. | The full project regression suite has additional unresolved failures below; preserving source does not certify all workflows. |

## Issues found, severity and corrections

| Severity | Verified issue | Correction/disposition |
| --- | --- | --- |
| High | Issuing a token immediately stopped central operations before a branch was usable. | New peers start prepared; central remains writable. Explicit freeze serializes with central writes/jobs; local remains read-only until bootstrap readiness, operator checklist and explicit activation are received. Safe cancellation exists only before activation. Existing peer ownership/checkpoints are preserved by migration. |
| High | Database dump and file archive could race local transactions or a sync worker; hot SQLite main-file copy could miss WAL data. | Branch backup holds worker/source locks across dump/files; MySQL/MariaDB is the deployed branch target. Live SQLite branch backup is explicitly refused; standalone SQLite uses VACUUM INTO. Actual encrypted SQL/public/private restore was tested in isolation. |
| High | Real MariaDB max_allowed_packet was only 1 MiB; uncompressed encrypted outbox insertion failed during the second accumulated snapshot, a SQLite test did not expose it. | New outboxes compress before APP_KEY encryption, legacy JSON outboxes remain readable, and an actual packet-capacity guard rejects safely before SQL. Local outage/second-version tests now pass. Deployment preflight requires >=64 MiB to support worst-case configured payloads; server-global settings were not changed. |
| Medium | 1,000-case uncompressed encrypted request was about 15.4 MB: theoretical upload at 1 Mbps exceeded the old 60-second timeout. Larger real SQL apply also approached/exceeded 60 seconds. | Bounded gzip-before-authenticated-encryption transport, backward reading support, per-snapshot identity caching and bounded 120-second default HTTP timeout. Plaintext/file limits, hashes and financial checks stay intact. Staging must verify hosting/runtime limits and matching reader versions. |
| Medium | A complete snapshot with >5,000 distinct files exceeded the inventory endpoint's per-request validation limit. | Worker splits inventory into bounded 5,000-hash requests. Worker test exercises 5,001 hashes; actual file contents/application are covered separately. |
| Medium | Status did not distinguish worker activity, local saved changes and central reachability observations; scheduled case changes could temporarily appear fully synced. | Persisted worker/contact/failure/ack/dirty state drives Preparing, Saved locally, Pending sync, Syncing, Synced and failure/attention messages. Scheduled changes mark dirty; frozen/recovery jobs cannot mutate operational records. Connection labels explicitly refer to the last central attempt/check. Page-load refresh preserves idle-session behavior. |
| Medium | Owner read-only draft preview incorrectly required document issuance permission. | Preview uses owner case-view authorization. Issuance policy/endpoints remain unchanged; preview does not create a document or change case amounts. |
| Low | Three dashboard tests expected superseded system/branch/staff dashboard behavior. | Verified current route/controller/views and existing role dashboard tests; updated checks to assert current view/data/branch isolation/actions rather than restore obsolete interfaces. |
| Low | Two SQLite fixtures used branch codes longer than the actual MySQL VARCHAR(10). | Shortened test-only identifiers in FuneralContractSnapshotTest/ReportsModuleTest; production column/validation unchanged. |
| Release blocker | Remaining full-suite failures and seven build dependency package findings. | Listed below, not hidden or reported green. They require resolution/disposition before staging acceptance. |

## Affected files

- Cutover: additive `database/migrations/2026_10_21_130000_add_branch_cutover_lifecycle.php`, `app/Console/Commands/BranchSync.php`, `app/Http/Controllers/BranchSyncController.php`, `app/Http/Middleware/BranchDeploymentMode.php`, `routes/branch-sync.php`, `app/Models/FuneralCase.php`, completion/retention commands.
- Capacity/reliability: `app/Services/BranchSync/{BranchSyncService,SyncSchema,SyncSnapshot,SyncTransport,OutboxPayload}.php`, `config/branch_sync.php`.
- Backup/schedule: `app/Services/SystemBackupService.php`, `bootstrap/app.php`.
- Existing UI: `resources/views/partials/branch-sync-status.blade.php`, owner `partials/case-dossier.blade.php`. Existing staff visual patterns retained; no dashboard redesign.
- Verification/preparation: `tests/Feature/BranchSyncTest.php`, dashboard/document/report fixtures, guarded `tests/TestCase.php`, `phpunit.offline-mysql.xml`, `scripts/offline-preflight.php`, runbook/SMART/acceptance documents and example LAN Apache configuration.

All other pre-existing working-tree changes were preserved. No commit/PR or production rollout was performed.

## Regression evidence

The baseline broad run found 398 tests with 24 failures, including the four requested ones. After the corrective work, the full SQLite run has **409 tests: 387 passed, 20 failed, 2 skipped; 2,972 assertions**. The two skips require actual MariaDB (backup/restore and small-packet guard). The four requested failures are resolved; the other 20 were also observed in this turn's baseline and remain release blockers. This is not a clean full-project regression result.

The applicable MariaDB selection passed **129 tests, 969 assertions**, including sync, actual independent MariaDB receiver/bootstrap, security, catalogs, structured intake, dashboard/document behavior, recovery workflows and payment corrections. Additional final capacity/scheduled-hold checks are recorded separately below. Tests use only exact named local databases `offline_validation_20261021`, `offline_receiver_20261021`, `offline_restore_20261021`. The test guard requires explicit MySQL opt-in, localhost and the exact test database name. It does not permit arbitrary application/production databases.

Browser verification passed: Branch Staff/Branch Admin **1 test /79 assertions**; Central Owner/System Admin **1 test /58 assertions**. Real password/authenticator login, local assets, read-only catalogs, themes at exact 1440px/390px, contrast/layout and central Chart.js were checked. Screenshots were inspected. Owner draft authorization was verified by feature tests; no physical branch-browser preview pilot is claimed. Build passed (7.02 seconds), Blade compilation/cache cleanup passed, and changed PHP syntax/scoped whitespace checks passed.

Relevant artifacts in `storage/framework/`: `offline-final-results.xml`, `offline-final-output.txt`, `offline-final-mysql-results.xml`, `offline-final-mysql-output.txt`, `offline-final-branch-browser.txt`, `offline-final-central-browser.txt`, `offline-final-build.txt`, `offline-mysql-followup.txt`, `offline-final-capacity-output.txt`. Initial failing runs are retained as investigation evidence.

## Capacity measurements

Measured on this Windows/XAMPP PHP 8.2.12 and MariaDB 10.4.32 environment. Data is synthetic: five payments/case, simple case fields, no actual attachment history, and no years of audit/document data. Times are local in-process HTTP/SQL, not measured WAN transfer. Peak memory includes PHPUnit/application/test-process overhead. SQL allocated bytes are approximate DATA_LENGTH+INDEX_LENGTH, not full filesystem/redo-log usage.

| Engine / cases / payments | Plain bytes | Wire bytes | Outbox bytes | Export / capture / apply seconds | Peak process MiB | Allocated DB bytes |
| --- | ---: | ---: | ---: | --- | ---: | ---: |
| sqlite / 100 / 500 | 865,606 | 71,480 | 71,396 | 0.21 / 0.13 / 0.98 | 100.0 | 970,752 |
| mysql / 100 / 500 | 871,806 | 72,248 | 72,136 | 2.13 / 0.84 / 4.80 | 86.0 | 3,112,960 |
| mysql / 1000 / 5000 | 8,725,217 | 672,084 | 672,000 | 14.72 / 5.66 / 42.78 | 196.0 | 10,305,536 |

Final additional MariaDB benchmark/scheduled-hold run: **2 tests, 14 assertions, passed**. At 1,000 cases the theoretical transfer at 1 Mbps is 5.38 seconds before protocol/latency/file overhead; this was not a measured network link.

Before compression, the SQLite 1,000-case/5,000-payment fixture produced 8,663,217 plaintext bytes and 15,401,616 wire bytes (theoretical 123.21 seconds at 1 Mbps). Per-snapshot UUID caching subsequently reduced repeated identity queries. Compressed results are reported above; they do not establish Hostinger performance.

At 2,500 cases/12,500 payments, the SQLite snapshot was **21,675,717 bytes**, above 20 MiB. Export took **8.58 seconds**, peak test-process memory **339,738,624 bytes**; capture rejected safely, no outbox/network/checkpoint advance, all local payments retained. The exact 10 MiB PDF upload boundary passed and >10 MiB was rejected. Compressed expansion limits and legacy readers are tested.

Conclusion: complete snapshots remain suitable for a bounded pilot only after representative full-history/file measurement. This evidence does not establish actual expected branch workload. Start staging resource review at >=512 MiB PHP memory, then measure actual web-SAPI peaks/runtime with representative data and concurrency. Do not silently raise the 20 MiB limit. If actual history approaches it, a separately reviewed incremental/archive protocol will be needed. No such redesign was introduced here.

Spool/files require a separate budget: synthetic benchmarks have zero immutable file spool bytes, so they do not measure real upload-storage growth. Blobs/grants are retained and automatic GC is not implemented. Monitor uploads, pending/latest payloads, immutable copies and hourly full backup retention; define audited safe cleanup before capacity is exhausted.

## Backup/recovery evidence

Fixture: **3 cases / 15 payments**. Encrypted backup creation: **0.68 s**; isolated SQL import: **1.04 s**; encrypted archive: **28,872 bytes**; SQL: **88,478 bytes**. Public/private files were extracted and verified; pending version 1 was restored and acknowledged on replay.

This test performed real mysqldump, encrypted archive/checksum verification, isolated MariaDB SQL import and extracted public/private files. It restored installation identity, UUID mappings, pending encrypted outbox/version and 15 payment records with an exact total of 3,000.00. A simulated central lost acknowledgment then resumed the pending version without duplication. The original APP_KEY was used; it must be preserved separately for real recovery. Old-checkpoint recovery holds and prohibited restaging/sequence replacement are tested separately.

This is not a two-hour full replacement-server recovery demonstration: the fixture is tiny, HTTP acknowledgment is simulated, and hardware/OS/service/TLS/key retrieval time was not measured. Branch backups are now hourly with a proposed <=1-hour off-device RPO; a real off-device copy task, independent key custody and acceptance are still required. Same-disk archives do not protect against disk loss.

## Security and dependencies

Passing sync/security coverage includes credential revocation, branch/file isolation, spoofed branch references, offline expiry, deactivated accounts after pull, password/MFA handling and replay counters, negative/invalid payments, exact totals, immutable audit entries, central legal holds surviving stale branch snapshots, installation/checkpoint holds and lost/wrong acknowledgments. A simulated three-day outage accumulates 100 additional cases/500 payments and delivers both versions safely; it is not an elapsed physical outage.

Offline access cannot receive immediate revocation/legal-hold updates. Require local server ACLs/private DB bindings/firewalls, full-disk encryption, trusted local TLS, timekeeping, and controlled key/token custody. Operational DB rows and immutable private blobs are not individually encrypted by this feature. Existing public photo storage is retained and requires business policy review. No OS/network security configuration was silently enabled.

Online npm audit still reports **7 affected build dependency packages: 5 high, 2 moderate, 0 critical**. They are Tailwind 3's braces/chokidar/micromatch/fast-glob/postcss-nested/postcss-selector-parser/tailwindcss chain. The two underlying advisory families include [braces recursion exhaustion](https://github.com/advisories/GHSA-vfj7-8cjw-p6xm) and [selector parsing CPU exhaustion](https://github.com/advisories/GHSA-rj75-hqrm-r3gf). npm proposes a breaking Tailwind 4 migration; incompatible overrides/forced upgrades were not applied. Build only from trusted repository/templates in a constrained CI environment; resolve or explicitly accept the build-tool risk before release. The separate online `npm audit --omit=dev` result reports **0 findings**. This does not make the full dependency audit clean. JSON evidence: `offline-final-npm-audit.json`, `offline-final-npm-production-audit.json`.

## Staging and external acceptance gates

The read-only preflight was run against the isolated local validation DB; its optional temporary storage write/delete probe checks actual access where Windows ACL hints are unreliable. The actual packet-size check fails the >=64 MiB deployment requirement on this local server (observed 1 MiB); no server-global setting was changed. `offline-preflight-local.json` records the observations. An example TLS/LAN Apache virtual host is prepared but not enabled.

Required next evidence:

1. Resolve/disposition the remaining 20 regression failures and seven build dependency findings; rerun full applicable checks.
2. Obtain verified legacy code/database/files and matching central APP_KEY; rehearse migrations/reconciliation on an isolated clone. Fresh current-schema MySQL migration is verified; old-deployment compatibility is not.
3. Verify actual Hostinger staging PHP/web/cron/HTTPS/proxy/request/memory/execution/packet limits and two independent machine synchronization, including uploads and interruption. Provider documentation alone does not certify this account.
4. Size actual branch history, audit/documents, uploads, immutable spool and hourly backup retention; measure real network throughput, lock duration and resource peaks.
5. Configure real LAN/TLS/services/power and off-device backups/key custody; execute all eight scenarios in [the acceptance checklist](offline-branch-acceptance.md), including two actual staff computers, WAN-only outage/restart/reconnect, multi-branch operation and complete isolated replacement-server restore.
6. Complete/sign at least five consecutive working days with a real >=72-hour outage interval and agreed correctness/sync/RPO/restore targets. No physical pilot has been claimed.

The updated [deployment runbook](offline-branch-deployment.md) defines preparation/freeze/activation, pre-activation cancellation, active rollback and checkpoint reconciliation. The [SMART plan](offline-branch-smart-plan.md) retains its measurable pilot gates and time allowance.

## Remaining full-suite failures

These failures are release blockers for the combined working tree. Some visibly reference older UI/role/fixture expectations, but their business intent has not all been independently settled. Do not weaken validation/authorization or change assertions just to get green. The final JUnit/output logs contain exact diagnostics.

| Suite | Failing test |
| --- | --- |
| `AdminOwnerInputConsistencyTest` | `test_admin_date_filter_options_are_consistent_on_desktop_and_mobile` |
| `AdminOwnerInputConsistencyTest` | `test_admin_filter_validation_handles_valid_and_invalid_values` |
| `AdminPackageStructuredWorkflowTest` | `test_package_form_renders_in_page_casket_modal_without_new_tab_catalog_flow` |
| `AdminPackageStructuredWorkflowTest` | `test_main_admin_uses_hover_actions_for_add_on_catalog` |
| `AdminScopeAuthorizationTest` | `test_branch_admin_main_intake_uses_assigned_branch_instead_of_br001` |
| `FuneralServiceTimelineTest` | `test_funeral_service_same_day_as_death_is_allowed` |
| `FuneralServiceTimelineTest` | `test_funeral_service_before_date_of_death_is_rejected` |
| `FuneralServiceTimelineTest` | `test_wake_end_must_be_later_than_wake_start` |
| `FuneralServiceTimelineTest` | `test_wake_end_may_equal_funeral_ceremony_but_interment_must_be_later` |
| `ProfileTest` | `test_staff_non_dashboard_pages_use_inline_header_without_notification_bell` |
| `StaffDashboardReminderTest` | `test_current_wake_is_case_based_and_schedule_is_event_based` |
| `StaffDashboardReminderTest` | `test_notification_target_case_is_subtly_highlighted_on_reminders_page` |
| `StaffDashboardReminderTest` | `test_admin_payment_summary_uses_one_consistent_case_cohort` |
| `StaffDashboardReminderTest` | `test_settled_cases_leave_balance_overview_and_upcoming_is_limited_to_seven_days` |
| `StaffDashboardReminderTest` | `test_timeline_uses_saved_times_excludes_drafts_and_groups_cross_event_schedules` |
| `SystemNegativeScenariosTest` | `test_intake_rejects_duplicate_client_in_same_branch` |
| `SystemNegativeScenariosTest` | `test_intake_rejects_request_date_before_date_of_death` |
| `SystemNegativeScenariosTest` | `test_intake_rejects_funeral_service_date_before_date_of_death` |
| `SystemRegressionGuardsTest` | `test_branch_admin_payment_monitoring_forces_assigned_branch_scope` |
| `SystemRegressionGuardsTest` | `test_case_details_support_case_centered_payment_entry` |
