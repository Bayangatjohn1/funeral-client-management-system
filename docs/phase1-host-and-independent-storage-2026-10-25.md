# Phase 1 — Host scheduler and independent storage preparation

October 25, 2026, Asia/Manila. **Classification: Local Validation; physical host acceptance pending.**

This phase uses the current development PC for safe inspection only, as authorized. No scheduled task/service was installed, no server was restarted, no WAN connection was interrupted, and no existing database, application configuration, key, checkpoint, retention setting or UI was changed. No new application defect was confirmed. Preparation is complete locally; unattended operation and independent storage protection are not accepted yet.

## Actual execution and evidence

Read-only Windows task, service, process, memory and volume inspection was performed at **2026-10-25 17:34:10 +08:00**. Evidence: `storage/framework/phase1-host-readonly-observation.json`.

| Check | Actual result | Meaning and remaining acceptance |
|---|---|---|
| Matching scheduler task | No direct action/working-directory match for this app or artisan schedule:run/work | Configuration gap in the inspected scope. Indirect wrappers and unrelated service definitions were not exhaustively excluded. No claim that every possible dispatcher is absent. |
| Web/database services | Apache2.4, mysql and MySQL80 running, configured Auto | Existing service metadata only. Multiple database services require explicit staging port/schema identification. Startup recovery, application health and noninteractive scheduler execution remain untested. |
| Current host | Windows 11 Home Single Language; non-domain host; inspection identity not elevated | Development equipment observation, not dedicated-server certification. |
| Memory/storage | 16,469,532,672 bytes physical RAM; C: 160,144,580,608 bytes free of 510,745,636,864 | Approximately 15.34 GiB RAM and 149.15 GiB free. A point-in-time observation, not a workload capacity test or storage reservation. |
| Registered app schedule/locks | Source review: branch hourly, central daily configured time; backup scheduler overlap 15 minutes; service and branch-sync locks 900 seconds | Existing accepted behavior preserved. No new actual hourly execution or lock-failure exercise this phase. |
| Two unattended hourly successes | **PENDING** | Requires approved host task, no interactive login, real clock and two successive due hours. |
| Restart, WAN outage, two LAN clients | **PENDING PHYSICAL ACCEPTANCE** | Explicitly excluded on the current PC. No simulation substitutes for these tests. |
| Independent encrypted transfer | **BLOCKED: destination/infrastructure not approved** | No copy or remote connection attempted. Same-server archives are not off-device protection. |
| Replacement hardware restoration | **PENDING PHYSICAL ACCEPTANCE** | Prior same-PC isolated restore is useful application evidence, not hardware acceptance. |

The accepted earlier review is `docs/local-automatic-backup-acceptance-2026-10-25.md`: 29 controlled local acceptance checks passed; SQLite 425 tests/3,247 assertions, two MariaDB-only skips; MariaDB 425 tests/3,267 assertions, no failures/errors. These are **historical results, not fresh executions in this phase**. Its controlled-clock scheduling evidence does not demonstrate real elapsed hourly recurrence. The destructive QA harness was not rerun, preserving its existing databases. No application code changed, so the full application suites were not rerun for this documentation-only preparation.

## Proposed dedicated test-server scheduler — not installed

Use a separate approved staging checkout, database, storage/cache roots and credentials, never the current development or production installation. Suggested staging root: `C:\BranchStaging\funeral-system`; PHP runtime path must be selected and verified first. An approved deployment administrator configures the task after reviewing the following definition:

| Task property | Proposed value / acceptance condition |
|---|---|
| Name | Sabangan Branch Staging Laravel Scheduler |
| Action | Approved absolute PHP executable; arguments `artisan schedule:run --no-interaction`; working directory approved staging root |
| Trigger | Every minute indefinitely; startup trigger after database/web readiness; retain hourly Laravel backup cadence |
| Principal | Dedicated least-privilege approved Windows account; run whether logged on or not, using an approved noninteractive logon method |
| Rights | Batch logon as required; read application/runtime and protected configuration; modify only necessary storage/cache/log directories; approved database/dump access |
| Conditions | No interactive-login or WAN-availability prerequisite; power/sleep policy reviewed for an always-on test server |
| Overlap | Ignore new OS task instances while one dispatcher runs; retain existing application locks; alert on a long-running/stalled dispatcher |
| Output | Protected dated dispatcher logs, Task Scheduler Operational history and application backup ledger; confirm child command failures as well as dispatcher result |
| Runtime limits | Review against dump timeout, full scheduler workload and 15-minute lease. Do not adopt a timeout or extend leases without measurement. An expired lease alone does not prove its previous process stopped. |

This is a reviewable configuration specification, not an importable task or installed service. Account, runtime paths, power policy, startup delay and timeouts need equipment-specific verification. Enter credentials through the authorized administrator's secure OS workflow; never commit passwords or send them in chat. Use UNC destinations and approved SMB access; interactive mapped drives do not establish unattended access. Do not assume LocalSystem or broad administrator privileges are necessary.

Microsoft documents that S4U logon has no network/encrypted-file access, so it must not be assumed to support NAS transfers. Password logon requires credentials at task registration. See [Microsoft task registration/logon types](https://learn.microsoft.com/en-us/windows/win32/taskschd/taskfolder-registertaskdefinition).

`StartWhenAvailable` can delay a missed OS trigger; it does not backfill Laravel's missed hourly due minute. A dispatcher restarting at 10:05 does not imply the 10:00 backup ran. Detect stale backups and use the existing authorized recovery/manual workflow as needed. See [Microsoft StartWhenAvailable](https://learn.microsoft.com/en-us/windows/win32/taskschd/tasksettings-startwhenavailable).

### Actual host acceptance record to complete

1. Export the approved task definition; record host, timezone, PHP version, staging schema/port, account/logon type, permissions, power policy and log locations. Keep secrets out of evidence.
2. Sign out all interactive application/server users. Collect **two successive actual hourly** start/end timestamps, task events, child command results, backup IDs, source recovery timestamps, sizes, encrypted SHA-256 and verification status. No manual dispatcher or controlled clock qualifies.
3. On dedicated equipment, restart between observations. Record database/web/task startup and application health without login; confirm the next real due backup. Do not infer this from Auto metadata.
4. Disconnect only WAN while maintaining server power, LAN and both clients. Repeat real due-hour verification and local operational access. Document exact network isolation and reconnection times.
5. In disposable staging only, hold the scheduled mutex and service/sync locks separately; document skip/refusal, no conflicting verified archive, lease age and subsequent normal successful execution. Inspect process state before any authorized stale-lock intervention. Never clear locks/checkpoints blindly.
6. Repeat controlled dump/storage/interruption failures in disposable equipment, observing actual OS task logs and next successful run. Never fill a live disk. Attach source and restored financial reconciliation.

## Independent storage proposal — approval required before integration

Recommended topology: existing encrypted local archive → separate secured LAN NAS/backup computer → additional secured off-site destination. The LAN copy remains reachable during WAN failure; off-site copies protect against branch-wide loss after verified delivery. A LAN NAS alone does not provide that disaster protection.

| Destination | Benefits | Requirements / limitations |
|---|---|---|
| Separate LAN NAS | Always-on storage, access controls, potentially protected versions | Approved hardware/SMB ACLs/account, capacity, UPS, disk health, secured physical access and independent verification. RAID alone is not backup. |
| Separate secured LAN PC | Can reuse approved equipment | Must remain powered and maintained; secure share/firewall, storage health and independent credentials. A sleeping staff workstation is unreliable. |
| Secure off-site storage | Survives loss of branch equipment | Approved provider/location, credentials, secure transport, versioning/deletion protection, capacity/cost, restore access and jurisdiction/policy review. WAN outage delays delivery. |

Existing application support: encrypted archive creation, local metadata/checksum and verification, existing authorized manual/status/recovery management, scheduled local retention. A configurable Laravel backup disk is not proof of a replicated independent copy. **No verified automatic copy workflow, remote receipt, transfer retry policy or external monitoring delivery exists in the reviewed setup.**

Before implementing a transporter, approve destination(s), ownership, access model, retention compatibility and alert recipient/channel. Required acceptance behavior:

- Copy only complete verified encrypted bytes. Capture backup identity, conservative source recovery time, byte length and SHA-256. Transfer to a temporary name and publish as usable only after destination-side verification.
- Use idempotent transfer identity (installation/branch + backup ID + checksum), durable pending state and bounded retry/backoff. Test disconnect, partial upload, checksum mismatch and lost acknowledgment. An object ETag alone is not necessarily a SHA-256 confirmation.
- Separate copy access from broad deletion/administration where supported. Protect keys separately, use secure transport and appropriate storage encryption/access controls. Never automatically mirror source deletion without approved retention behavior.
- Acknowledge LAN and off-site copies separately. Retry backlog after WAN recovery without making older source points appear fresh. Demonstrate an actual restore from the independent destination.

No transfer integration, destination selection, credentials or retention changes were made.

## Monitoring and the one-hour objective

Keep System Admin's exclusive application Backup & Recovery/Data Retention management. Staff/Branch Admin receive no system-wide permission. Authorized technical personnel manage host/storage monitoring under deployment administration.

Monitor dispatcher heartbeat, scheduled due events, backup attempt failures/skips, last verified source recovery point, missing/corrupt files, free space/forecast, LAN/off-site receipts, pending-transfer age and delivery failures. A zero dispatcher exit code alone is insufficient: inspect scheduled child failures and the backup ledger. Lock skips may not create a new backup record. Integrity verification at creation does not constitute continuous archive scrubbing; periodic revalidation needs approved operational configuration.

Central Backup & Recovery reads its own database; branch backup/restore ledger entities are not operational synchronization payloads. Central System Admin does not automatically receive all local backup failures. An approved external monitor/alert route and ownership/escalation roster remain prerequisites; no dashboard or messaging integration was added.

Proposed monitoring acceptance, consistent with the existing runbook: check at least every five minutes; warn at recovery-point age 40 minutes, critical at 50, record breach above 60. With an hourly schedule these thresholds can alert routinely: this is a strict-target feasibility issue, not a reason to suppress alerts or relabel old points. Deduplicate notifications while preserving breach evidence. Evaluate thresholds and schedule together before operational approval.

For each destination measure `now - source recovery timestamp`, including backup creation, encryption, transfer, verification and detection/response delays. **Hourly creation plus positive delivery delay cannot guarantee a strict one-hour off-device RPO**, even with no failures. During total WAN loss off-site freshness necessarily increases; a healthy LAN copy provides separate protection. Preserve current cadence/retention until a measured recommendation is approved.

During outage, approved LAN/local monitoring must alert locally and retain events. Central monitoring can flag missing heartbeat but cannot identify the exact branch failure without connectivity. Store-and-forward alerts/alternate connectivity are not configured or verified. Do not assume email/cloud alerts will arrive during WAN loss.

## Replacement-server recovery procedure

1. Authorize recovery; fence or power down the original writer and its scheduler/sync transport. If it is unavailable, enforce network/token fencing under the existing procedure before replacement activation. Record the authoritative central checkpoint without changing it.
2. Prepare isolated replacement application/runtime and compatible database, with outbound synchronization blocked and recovery hold established before allowing operational access. Do not start two copies with the same installation identity.
3. Retrieve a verified independent archive and its trusted checksum/metadata. Obtain matching APP_KEY and required sync/database credentials from separate secure custody. Never regenerate a key to solve a restore failure.
4. Verify encrypted bytes, decrypt and validate archive; restore database, private/public uploads, pending immutable sync files/outbox, mappings and checkpoint state as one recovery set. Apply correct disk roots, ACLs and configuration. Retain original identifiers/history.
5. Reconcile users/roles, case counts, posted/voided payments, corrections, audit history, per-case paid/balance/status, documents and pending sequences/fingerprints with source records and central acknowledgments. Account for records acknowledged centrally after the backup; use existing operator reconciliation rather than resetting sequence/version/checkpoints or inventing payments.
6. Confirm held operational writes, blocked outbound transport and original-writer fencing. Test authorized login and document reads in isolation. Investigate any checkpoint divergence; do not force synchronization.
7. After reconciliation and authorized release, permit one replacement writer, perform supported sync/retry reconciliation, verify central acknowledgment/no duplicates, then restore scheduler/monitoring. Preserve recovery evidence and review data-loss window. Do not reactivate the old server as another writer.

Prerequisites: spare server/isolated network, compatible runtime/dump clients, actual independent archive and keys, central staging endpoint/operator, expected totals/checkpoints, custody record and approved downtime. Same-PC synthetic restoration from the previous review remains the only completed restoration evidence; no hardware restoration occurred here.

## Resource/storage planning

Planning estimates, not measured production sizing: an initial dedicated test server with 4 CPU cores, 8–16 GiB RAM, SSD, wired LAN, UPS and independently sized backup storage is a starting point; validate full history, documents, two users and concurrent scheduler workload before procurement/acceptance. Protect disk/database access with OS ACLs, host firewall and restricted credentials. Current PC specifications do not certify these requirements.

With hourly full archives and unchanged 14-day retention, budget about **336 × average encrypted archive size per destination**, plus protected restore artifacts, failed/orphaned files, versions, temporary work and reserve. At 100 MiB/archive this is 32.8 GiB; at 1 GiB/archive it is 336 GiB. Do not assume expiration caps all retained bytes. Measure database/uploads/private files/outbox growth and cleanup behavior separately.

Local working disk must accommodate live data, SQL dump, ZIP, encrypted archive and verification/restoration workspace. The implementation materializes archive/encryption data in memory; a PHP 512 MiB limit is not a guarantee for large real attachments. Prior small synthetic backup timings are unsuitable for production sizing. Measure peak memory and all phases with representative data before setting host limits.

Theoretical transfer of 1 GiB: about 86 seconds at 100 Mbit/s or 859 seconds at 10 Mbit/s, before protocol/encryption/storage overhead. These are calculations, not observed rates. Size off-site bandwidth and local backlog for outage duration and subsequent full archives, then measure actual delivery/restore time. Proposed storage headroom/alerts must cover the next dump/archive cycle, not just a fixed percentage.

## Decisions and staging gates

- Approve dedicated host, isolated schema/ports/path, task account and installation authorization; supply authorized restart/WAN/two-client test access.
- Approve independent LAN destination and off-site destination, access method, custody, capacity, transfer ownership, retention compatibility and alert route. Do not share secrets in chat.
- Obtain representative operational size/growth and acceptable outage/loss objectives; approve any schedule/monitoring changes only after measurement.
- Complete real noninteractive two-hour, restart, WAN, lock/failure and independent restore evidence above.
- Hostinger account PHP/CLI/extensions, HTTPS/proxy/body limits, cron/storage/packet capacity remain unverified. Previously observed local packet limitation is not Hostinger evidence. Historical migration rehearsal remains **BLOCKED** until verified legacy DB/files export and matching application-key information are available.

Exact new files this phase: this report and `storage/framework/phase1-host-readonly-observation.json`. No application source or existing configuration file was modified.
