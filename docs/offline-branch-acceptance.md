# Controlled staging and branch acceptance

Prepared 21 October 2026. This is an executable operator checklist, not a record of a physical pilot. Attach dated evidence and operator/branch representative names to each result. Never run destructive test suites or outage simulations on the old Hostinger production installation.

Updated 22 October 2026 for the [staging feasibility audit](staging-feasibility-audit-2026-10-22.md), including the strict off-device RPO prerequisites. Existing hourly scheduling is not a certified one-hour RPO.

## Preconditions and evidence

- [ ] Staging central, branch A and branch B run the exact same release/migrations and compiled assets. Use independent MariaDB databases and branch APP_KEY values. Restrict access to named testers; disable production email/integrations and real payments.
- [ ] Preserve the existing central APP_KEY. Store branch keys and tokens separately in an approved encrypted vault/off-device recovery package. Never paste them into tickets, screenshots or this checklist.
- [ ] Run `php scripts/offline-preflight.php` on each configured staging installation; save its JSON. It is read-only. CLI PHP settings do not establish web-SAPI PHP limits.
- [ ] Verify trusted HTTPS, reverse-proxy handling, request limit >=48M, upload limit >=11M, memory and execution time against the measured representative dataset. Remove temporary authenticated PHP diagnostics afterward.
- [ ] Set reserved LAN server addresses, local DNS, Apache public-only document roots, private database bindings, host firewalls and OS/service account permissions. Use full-disk encryption and locked server access. Establish time synchronization before outage tests; MFA requires correct local time.
- [ ] From TWO actual authorized computers, open the same local hostname and sign in as separate Staff accounts. A second browser on one computer is not physical LAN evidence.
- [ ] Establish scheduler auto-start, Apache/MariaDB auto-start, sufficient disk space, a power plan and off-device backup destination. Validate service restart without an interactive desktop.
- [ ] Complete the cutover procedure in the deployment runbook, including freeze, bootstrap, readiness, reconciliation and explicit activation. Keep both operational writers disabled during frozen verification.
- [ ] Agree pilot RPO <=1 hour and target restore <=2 hours. Existing hourly backups plus completion/copy delay do not strictly guarantee that RPO. Configure/test a coordinated cadence with interval + worst-case completion/copy lag + response margin <=60 minutes, or obtain approval for a measured longer RPO. Verify off-device checksums; monitor recovery-point age from backup start/snapshot time at least every five minutes, warn at 40, escalate at 50 and mark breach above 60 minutes. Alert immediately on failed/skipped backup/copy. A local-only archive does not satisfy off-device RPO. See the deployment runbook for configuration/retention prerequisites.

## Automated local commands

Run sequentially from the repository with dependencies installed. Keep the normal application `.env` unchanged.

```powershell
php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml --log-junit storage/framework/offline-final-results.xml
# LOCAL DISPOSABLE DATABASES ONLY. This configuration runs destructive migrations in:
# offline_validation_20261021, offline_receiver_20261021, offline_restore_20261021
# Create those empty databases on localhost first; never point this at production.
php vendor/phpunit/phpunit/phpunit --configuration phpunit.offline-mysql.xml --filter 'BranchSyncTest|DashboardAccessTest|FuneralContractSnapshotTest|AccountSecurity|ServiceCatalogConsistencyTest|PaymentCorrection'
$env:OFFLINE_CAPACITY_CASES='1000'
php vendor/phpunit/phpunit/phpunit --configuration phpunit.offline-mysql.xml --filter test_capacity_measurement_and_replay_with_synthetic_history
Remove-Item Env:OFFLINE_CAPACITY_CASES
npm.cmd run build
```

The MySQL test guard accepts only explicit opt-in, localhost and the exact validation database name. Bootstrap/remapping uses the named receiver database; recovery uses the named restore database. The backup test commits synthetic fixtures so a real external mysqldump process can read them, restores SQL and files in isolation, and rebuilds only the disposable validation schema afterward. HTTP failures/acknowledgments in feature tests are simulated; actual network transport remains a staging acceptance gate.

## Scenario scripts

For each scenario save start/end time, branch code, device names, software release, record identifiers, local/central counts and totals, sync versions/hashes (no payloads or secrets), backup evidence and pass/fail reason.

1. **Normal operation.** Staff A on computer A creates a synthetic case and records a permitted payment. Staff B on computer B opens that case through the same branch hostname, confirms the exact payment and records a second permitted payment. Verify distinct payment/receipt identifiers and exact balances. Run `php artisan branch:sync run` or wait for the minute scheduler; check `php artisan branch:sync status` on both installations. Compare central records by branch code plus case/payment identifiers, never by database numeric IDs. Confirm Branch Admin cannot submit catalog/package writes and Owner remains read-only.
2. **Internet disconnected, LAN available.** Disconnect ONLY the branch WAN/uplink, preserving switch/router LAN, server and power. Record start time. Reauthenticate on both computers with centrally enrolled authenticator accounts; create cases, save/resume drafts, record permitted payments, open reports/contracts and print. Verify all required resources load locally. Confirm records remain visible on the other computer. Run sync and confirm failure retains source records/outbox. Reject recording after the configured offline account window expires in an isolated clock simulation, not by changing the pilot server clock.
3. **Internet restored.** Reconnect WAN. Observe scheduler attempts and acknowledgments without manual duplicate encoding. For the agreed backlog of 100 ordinary changes, target acknowledgment within five minutes on the measured pilot connection. Compare exact cents, payment counts, balances, document identifiers and file checksums. Confirm Pending becomes Synced only after matching acknowledgment; central freshness updates. Re-deliver an already accepted snapshot in the automated test harness; payment count must remain unchanged.
4. **Server restart while disconnected.** With WAN still disconnected, commit records and note identifiers/pending version. Restart the actual branch server, then reconnect both computers through its unchanged hostname. Confirm committed data, outbox, mappings, files and checkpoint survive, and the scheduler starts automatically. Do not judge unsaved browser form text as committed data. Record recovery time and any service intervention.
5. **Recovery.** Copy a verified encrypted backup plus separately preserved APP_KEY into an isolated replacement installation. Disable outbound sync and staff access while restoring database/public/private files together. Verify archive checksum/decryption, migration level, branch/installation identity, UUID mappings, pending payloads/blobs and financial totals. Compare central checkpoint before releasing writes. Test (a) matching checkpoint, (b) lost acknowledgment of pending version, and (c) an older checkpoint: (c) must hold writes for operator reconciliation, never reset versions. A recovered clone must not run alongside the original branch writer. Record full restoration duration including configuration/files, not SQL import alone.
6. **Failures.** On staging only, test invalid/revoked token, rejected financial snapshot, interrupted file upload, lost acknowledgment, wrong acknowledgment, version conflict, request/file limit exceeded and worker restart. Ensure central rejects atomically, source transactions remain intact and retries are bounded. Use `restage` only for corrected data after central confirms that pending version was never accepted. Ordinary staff see a safe failure/attention message; detailed diagnostics stay with administrators. Never manipulate live money data to manufacture a failure.
7. **Multiple branches.** Leave A disconnected for at least an hour while B remains connected. Record and synchronize B transactions throughout. Assert B scheduler/acknowledgments continue and A data is explicitly stale centrally. Confirm A token cannot read B files or submit B records. A's recovery hold must not become a global application hold.
8. **User experience.** In light/dark themes at 1440px and 390px, ask Staff and Branch Admin to identify connection observation, Saved locally, Pending sync, Syncing, Synced and Sync failed/Requires attention. Connection wording refers to the last central check, not guaranteed present internet. Observe Syncing while a real worker is active and reload the page; the banner deliberately does not poll or prolong idle sessions. Confirm local changes during an in-flight snapshot remain pending until the next acknowledged version. Have each tester explain the status in their own words; record misunderstandings.

## Five consecutive working days

| Day | Required activity | Evidence |
| --- | --- | --- |
| 1 | Controlled staging acceptance/cutover, two-computer normal operation, reconciliation, first verified off-device backup | Signed preflight/cutover sheet, counts/totals, timestamped backup |
| 2 | Begin a real scripted 72-hour WAN outage at an agreed hour; accumulate synthetic/approved pilot transactions | WAN isolation evidence; local access and record identifiers |
| 3 | Continue outage; restart actual local server; verify hourly off-device backups and branch B operation | Restart timeline, data/checkpoint comparison, branch B acknowledgments |
| 4 | Continue outage and all agreed local workflows; inspect disk growth and account-window remaining time | Workflow matrix, disk/backup/time/security observations |
| 5 | Complete >=72 real hours, reconnect, reconcile, perform isolated replacement-server restore and failure drills | Sync timing, zero discrepancies/duplicates, full restore duration, branch sign-off |

Choose working days that actually permit the full 72-hour interval. Extend monitoring if holidays, outages or failures prevent a valid five-day record. Simulation/clock travel never replaces elapsed outage evidence.

During staffed hours check at opening, every 15 minutes and closing: last central contact/ack, oldest pending age, last safe failure category, recovery hold, scheduler service health, free space, clock, and last verified off-device backup. Do not log tokens, payloads, names, MFA values or keys in monitoring records. Escalate immediately for inconsistent totals, duplicate payments, isolation breaches or recovery holds; pause affected recording, preserve all copies/logs and involve the designated deployment administrator. Connectivity failure alone permits local work within account validity and backup capacity; never re-enter the same transactions centrally.

Acceptance requires zero missing committed records, duplicate payments, broken links, incorrect balances, unauthorized access or unresolved core-workflow defects; all eight scripts pass; catalogs do not reprice historical cases; the agreed sync/RPO/restore targets are measured; five consecutive working days are signed by the branch representative and deployment administrator. Hosting rehearsal and dependency/regression disposition must be recorded before Pilot-Ready. Production approval is a subsequent gate.


Preflight is read-only by default. To verify actual private storage access, run `php scripts/offline-preflight.php --probe-storage`; this writes and removes one temporary file on that installation. Run it under the intended service identity too. The local probe passed; the local 1 MiB database packet setting remains a failed deployment-capacity gate.
