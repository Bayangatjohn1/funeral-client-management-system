# Staging feasibility and deployment-readiness audit

October 22, 2026 (Asia/Manila). **Classification: Local Validation; not yet Staging-Ready, Pilot-Ready or Production-Ready.** The existing complete-snapshot architecture remains a conditional staging candidate. There is no evidence requiring a redesign, and there is not yet enough evidence to certify the actual Hostinger account or expected branch workload.

No production application, database, credentials, keys, scheduler or hosting settings were changed. SQLite and MariaDB full suites ran sequentially on isolated databases. Unrelated working-tree changes, payment/permission behavior, historical records and sync checkpoints were preserved.

## Fresh regression and recovery-message verification

| Check | Result / evidence |
|---|---|
| Complete SQLite Unit/Feature | **420 tests, 3,224 assertions, zero failures/errors; two MariaDB-only skips**, 50.109 s. `storage/framework/staging-audit-sqlite.xml` / `.txt`. |
| Complete MariaDB Unit/Feature | **420 tests, 3,244 assertions, zero failures/errors/skips**, 143.044 s. `storage/framework/staging-audit-mariadb.xml` / `.txt`. |
| Installed non-development Composer platform requirements | Passed locally, PHP 8.2.12. `storage/framework/staging-platform-requirements.txt`. This is not an account or web-SAPI check. |
| Local deployment preflight | Central-mode QA checks passed except packet capacity; actual private-storage temporary write/delete succeeded. Branch bootstrap/token checks were not executed in this mode. `storage/framework/staging-local-preflight.json`. Nonzero exit reflects the real packet blocker, not a hidden passing result. |
| Fresh synthetic capacity/replay | One MariaDB test, five assertions passed, 89.811 s. `storage/framework/staging-capacity-output.txt` and `staging-capacity-mysql-1000.json`. |

Both full suites include the recent intake timeout recovery, legacy-grant isolation, payment/correction integrity, actual independent sync receiver and MariaDB backup/restore checks. The two SQLite skips execute successfully in MariaDB. No unresolved regression failure was observed. The resolved 20 failures were not reworked; they were exercised as part of the requested full suites.

The Staff recovery message now says: **“Recovered existing case [case code]. This retry did not apply changes or create another case or payment. Review the saved details and payment history.”** Its regression asserts the case code and explicit no-edits/no-new-record wording. This is a confirmation-copy clarification only; it does not add an action or change Case Details, Payment Monitoring, financial calculations or authorization.

## Hosting requirements versus actual evidence

Local observations are CLI-only: PHP 8.2.12, MariaDB 10.4.32, memory 512M, execution time 0/unbounded, input time -1, post/upload 40M, and process execution available. None establishes the web server, reverse proxy, hosting plan or production configuration. The private-storage probe ran under the local CLI account, not the eventual web/service identity.

| Area | Application requirement / planning target | Account verification still required |
|---|---|---|
| PHP/framework | `composer.json`: PHP ^8.2, Laravel ^12; install exact lock-file release and run production platform check. Composer confirms local DOM/libxml, fileinfo, filter, hash, iconv, JSON, OpenSSL, PCRE, session, tokenizer and PHP requirements; transport/backup also need native pdo_mysql, zlib and zip. Verify mbstring and PDF/image-related extensions on the target. | Actual web and CLI versions/extensions, matching binaries, permitted functions and report/PDF/image workflows. Polyfill satisfaction of Composer alone does not prove all runtime extensions/workflows. |
| Database | MySQL/MariaDB driver with InnoDB transactions, row locks, foreign keys, required JSON/schema/index support and compatible SQL modes/collations. Local MariaDB 10.4.32 is tested; another MySQL/MariaDB version is not automatically certified. | Target engine/version, SQL mode, table engines, indexes/FKs, transaction behavior and database/user permissions. Run the isolated MariaDB/target-engine integration checks there. |
| Packet capacity | Preflight deployment gate **max_allowed_packet >=64 MiB**, allowing worst-case configured payload capacity. | Actual database server/session value and provider ability to support it. Local QA observed **1,048,576 bytes**, a confirmed failed gate. Compression on one fixture does not waive worst-case capacity. No global DB setting was changed. |
| Memory | Prior 1,000-case synthetic peak 196 MiB; start resource assessment at >=512 MiB, then measure actual web apply/export/backup peaks plus concurrent requests. This is a planning starting point, not a certified minimum for all data. | Web memory and worker/RAM ceilings, CPU/I/O throttling and concurrency. Backups load the ZIP and encrypted result into memory, so memory grows with the entire archive; tiny SQL restore evidence does not prove a large attachment backup will fit. |
| Timeouts | Sync connect timeout 10 s, configured response timeout 120 s. Actual allowed web/proxy execution must cover the measured representative operation with margin; all tasks must fit relevant lock expiry. | Web/CLI execution and input limits, reverse-proxy/request deadlines, provider kill limits and measured heavy apply/backup duration. CLI unlimited execution is not proof of web feasibility. |
| HTTPS/proxies | Central outbound synchronization uses HTTPS with normal certificate verification and no redirects. Set correct staging APP_URL and secure cookies; enforce TLS at the intended terminator. | Real certificate chain/clock, redirect behavior, secure sessions/CSRF and forwarded scheme/host/IP. No custom trusted-proxy allowlist is configured in this project; validate Laravel's effective trust on the actual topology. Do not trust arbitrary forwarded headers or add wildcard proxy trust without verifying the boundary. |
| Request/upload size | Retain **20 MiB plaintext** and **10 MiB/file**. Existing acceptance planning floor: request body >=48M and upload limit >=11M, accounting for encrypted/base64/JSON and multipart overhead. These are conservative deployment targets, not increased application limits. | Actual web post/upload limits, proxy/web server request cap, temporary disk, large request/file boundary and safe rejection. Local CLI 40M observations do not pass or fail the unmeasured web gate. |
| Scheduler/processes | `schedule:run` every minute; branch sync every minute, completion job every minute, branch backup hourly; central backup daily. No permanent sync queue worker required. Backups require permitted process execution and compatible dump/import tools. | Cron availability/cadence, correct PHP executable/workdir, nonoverlap, heartbeat, restart persistence, dump/import permissions and retention-job behavior. |
| Files/assets | Public-only document root, protected .env/private storage, writable storage/bootstrap cache under the actual service identity; compiled public/build and bundled public/vendor/offline assets. No live Vite/CDN runtime dependency in configured offline modes. | Ownership/ACLs, public storage links, private-file HTTP denial, local fonts/calendar/chart assets, PDF generation, disk/inode quotas, backup/spool paths, free space and no public diagnostics left behind. |

Official Hostinger documentation supports framework deployment, configurable PHP options and scheduled commands, but its limits depend on the plan/account. Relevant official references reviewed October 22: [framework support](https://www.hostinger.com/support/which-programming-languages-and-frameworks-are-supported-at-hostinger/), [cron setup](https://www.hostinger.com/support/1583465-how-to-set-up-a-cron-job-at-hostinger/), [plan limits](https://www.hostinger.com/support/6976044-parameters-and-limits-of-hosting-plans-in-hostinger/), [memory inspection](https://www.hostinger.com/support/1583711-what-is-php-memory-limit-at-hostinger/). These are capability references, not evidence for the user's purchased account. Do not infer compatibility from the old site's successful login page.

### Blockers, unknowns and feasible remedies

| Status | Finding | Safe next action |
|---|---|---|
| Verified local blocker | 1 MiB packet setting is below the 64 MiB deployment gate. | Configure a staging database meeting the requirement with its administrator/provider, reconnect and rerun preflight/boundary tests. This audit did not change the local or remote server setting. |
| Blocked by missing resource | No verified historical Hostinger database/files/key export. | Obtain the secure, consistent export package described below. Fresh schemas cannot substitute. |
| Verified readiness gap | Current hourly backup + completion/off-device copy delay does not strictly establish <=1-hour off-device RPO. A 90-minute alert would detect failure after the target is already missed. | Agree and stage-test cadence/transfer/monitoring using the budget below, or explicitly approve the measured longer RPO. No schedule/transfer service was changed here. |
| Unverified account prerequisites | PHP web/CLI, plan memory/time/body/packet limits, cron/process tools, private storage, TLS/proxy and account quota. | Provide an isolated staging site/database and authenticated administrative access through the approved secure channel; capture actual observations without exposing secrets. |
| Unverified workload/hardware prerequisites | Real history/files/audit growth, off-device storage/copy performance, two LAN clients and replacement server. | Obtain volume baselines and named equipment/operators; execute measured acceptance scripts. |

If a mandatory account requirement fails: first use a provider-supported configuration change within that plan; if its ceiling remains inadequate, compare a verified higher-capacity managed plan with a managed/self-administered VPS offering database and runtime control. A separate managed central database is another option only after testing secure connectivity, latency, backup consistency and access rules. Higher advertised PHP RAM does not establish packet capacity or process permission. Moving only the isolated central staging target does not authorize a production move. Increased application payload limits, disabled financial checks and silently dropped history are not remedies.

An incremental/archive synchronization redesign becomes a separate decision only if measured full history cannot fit the preserved protocol, or acceptable infrastructure cannot process it. No such redesign is justified solely by missing account information and none was introduced.

## Snapshot capacity and storage assessment

This is a full operational snapshot: cases, clients/deceased, payments, correction requests, service details/add-ons, notes/drafts, document/revision metadata, attachments and operational audits participate. Files travel separately, but their references and retained audit/history metadata still grow the plaintext graph. Compression reduces transport/outbox bytes, not the plaintext limit. File reuse/deduplication does not make metadata history unbounded.

Retained October 21 evidence: 1,000 cases/5,000 payments generated 8,725,217 plaintext bytes, 672,084 wire bytes, 672,000 encrypted-outbox bytes, approximately 14.72/5.66/42.78 s export/capture/apply, peak 196 MiB. The 2,500-case/12,500-payment SQLite fixture generated 21,675,717 bytes, exceeded 20 MiB and failed safely. These are synthetic financial-history shapes, not reliable case-count ceilings. Correction/document/audit correctness and independent receiver/replay are covered by the full suites, but no verified real-history/export dataset is available to establish expected operational volume.

Fresh October 22 1,000-case/5,000-payment financial benchmark: **8,945,217 plaintext bytes** (~42.7% of the 20 MiB limit), **671,460 wire bytes**, **671,376 encrypted-outbox bytes**; export **15.02 s**, capture **6.24 s**, in-process HTTP apply **47.18 s**, peak **188 MiB**, allocated database/index bytes **11,436,032**. Matching replay passed without duplicating payments. Schema/history shape differs from earlier measurements, demonstrating why a fixed case-count ceiling is unreliable. It is still synthetic, has no attachment blobs and does not certify mixed full-history or Hostinger performance. Do not translate its theoretical 1-Mbps byte calculation into measured WAN throughput. The previous measurement file is preserved; fresh evidence is `storage/framework/staging-capacity-mysql-1000.json`.

At/over capacity, capture or upload rejects safely, records remain local, and the rejected payload does not advance acknowledgment/checkpoints. Once a branch's full history cannot fit, automatic retry cannot make the graph smaller: central reports become stale while local records accumulate. Escalate before that point; never delete payments/audits or reset a checkpoint as a workaround. Existing file tests accept exactly 10 MiB and reject larger files; these limits remain unchanged.

Proposed operational thresholds (monitoring policy, **not new application automation**):

- Record plaintext snapshot bytes, compressed outbox/wire bytes, actual duration/peak memory, file count/bytes, oldest pending version/age and disk/inode free space. Measure byte size from controlled in-memory decoding/inspection of a real pending snapshot or staging export; never log personal payloads, tokens or keys. Do not run capture/restage just to manufacture a monitoring record.
- Warning at **70% =14 MiB**, intervention planning at **85% =17 MiB**, urgent release/operations review at **95% =19 MiB**. Review weekly during pilot, daily when above warning, and on each material history/file import. Forecast time to the limit using the measured growth trend; these are planning triggers, not hardcoded guards.
- Alert immediately on capture/file-capacity failure, packet failure, financial rejection or recovery hold. Display stale central data honestly; do not re-enter transactions centrally.
- Stage-test genuine workload proportions: baseline history + projected pilot/year growth, payment corrections/voids, full document snapshots/revisions, notes/drafts and audits, plus worst permitted attachments. Compare exact financial sums and identifiers before/after replay. Ownership/audit preservation rules exclude arbitrary history pruning.

Storage budget must separately measure database/indexes, current source uploads/private documents, retained immutable spool/incoming blobs, outboxes, full encrypted archives and backup working ZIP/SQL. Synthetic no-file spool measurements do not establish real storage growth. Automatic orphan-file garbage collection is not implemented; no file referenced by pending/accepted recovery state or retained backups may be deleted merely to gain space.

Planning formula: required local storage >= DB/indexes + source uploads + retained immutable copies + outboxes + concurrent dump/ZIP/encryption working space + full-backup retention + OS/logs + agreed free-space reserve. Measure deduplication/ZIP compression rather than assume it. At 14 days retention, hourly archives imply up to 336 full copies; a 30-minute cadence implies 672. Every 100 MiB average archive therefore consumes approximately 32.8 GiB or 65.6 GiB respectively, before working space/off-device retention. A 1 GiB archive implies 336/672 GiB. These examples are arithmetic estimates, not observed archive sizes.

## Backup/recovery configuration and RPO acceptance

Existing application behavior: encrypted full backups, branch source/sync locks, local verification, 14-day configured retention; hourly branch scheduling and daily central scheduling remain unchanged. Off-device transfer and monitoring are **not configured by the app**. ZIP content and encrypted output are held in memory; validate actual large-volume peaks, lock duration and I/O before accepting a memory/restore target.

For the proposed <=60-minute off-device RPO, require:

`maximum capture interval + worst-case backup/encryption/copy/verification lag + response margin <=60 minutes`.

Planning example: 30-minute interval +20-minute worst-case completion/copy lag +10-minute response margin. This is not an implemented schedule or a measured guarantee. The current 60-minute interval leaves no allowance for lag, so strict one-hour sign-off is pending configuration/measurement. Select one coordinated trigger owner, avoid duplicate triggers, and test interactions with backup/sync locks and refusal/retry. Approve a realistic longer RPO if the chosen configuration cannot meet one hour.

- [ ] Name backup, off-device transfer, alert response and key custodians; agree RPO/RTO and staffed/unattended coverage.
- [ ] Configure actual dump/import binaries and service execution permission; run `system:backup --type=scheduled` on **staging only** and capture a verified artifact/result without printing keys.
- [ ] Configure one accepted backup cadence and secure off-device destination independent of branch disk/server failure. Encrypt transport, verify encrypted-archive checksum at destination, record size/backup ID and conservative recovery-point time (backup start/snapshot time, not upload completion).
- [ ] Monitor at least every five minutes: warn at recovery-point age 40 minutes, critical at 50, target breach above 60. Alert immediately on failed/skipped backup, lock refusal with no prompt successful retry, transfer or verification failure. A newly uploaded old archive must not reset recovery-point age.
- [ ] Keep matching APP_KEY, branch token/installation identity, release/migration manifest and recovery instructions in approved secure custody separate from the archive; never paste values into a ticket or report. Preserve the old central key for legacy encrypted fields. Rotate/revoke credentials only through documented coordinated recovery, not to bypass a checkpoint conflict.
- [ ] Size both retention destinations and working disk for the agreed cadence; monitor free space/inodes and test expiration while a backup is pending. Do not mistake a scheduled attempt for a verified off-device recovery point.
- [ ] Restore into an isolated replacement with original writer/staff/schedulers and outbound sync disabled. Restore DB, source uploads/private files, durable outbox, immutable pending files, UUID mappings, state/checkpoints and matching key/release together.
- [ ] Verify hashes/decryption, migration level, account/MFA behavior, case/payment/correction totals and links, documents/files and central accepted version/hash. A restored clone must never write concurrently with the original server.
- [ ] Exercise matching checkpoint, lost acknowledgment and older/divergent checkpoint. The last requires a write hold/operator reconciliation; never clear the hold, reset versions or bootstrap over records.
- [ ] Demonstrate full replacement-server restoration <=2 hours **if this proposal is accepted**, including hardware/runtime/configuration/files/keys/reconciliation and LAN clients. A small SQL import time is not a physical restore demonstration.

Fresh full MariaDB tests include real dump/import and encrypted-file recovery in isolation: **3 cases /15 payments**, backup **0.673 s**, SQL import **0.770 s**, encrypted archive **29,128 bytes**, SQL **89,018 bytes**; public/private files and pending version 1 restored, then acknowledged on simulated HTTP replay. Evidence: `storage/framework/offline-restore-measurement.json`. Physical replacement equipment, configured off-device transfer and full-size restore duration remain unverified.

## Legacy migration rehearsal procedure — BLOCKED pending export

1. Obtain an authorized consistent historical database export, uploads/private documents, migration/source version, configuration inventory and securely delivered required APP_KEY information. Record checksums, capture time and completeness; do not include secret values in the audit. The public website URL is not this export.
2. Quarantine an isolated staging copy with separate database/account/domain/storage. Disable outbound mail/integrations, staff access, schedulers and sync. Do not reuse any production DB connection/cached configuration. Verify the exact selected database/host before migration; use no production credentials.
3. Preserve an untouched pre-migration copy. Inventory schema/migration history, SQL mode/engines/collations/indexes/FKs; counts and identifiers per branch; users/roles, legacy permission records, payments/VOID history/corrections/receipts, document snapshots and file checksums. Record exact cents and valid posted-payment sums without re-pricing cases. Restrict personal-data access.
4. Install a pinned candidate artifact and locked production dependencies in the isolated release. Preserve the required historical key; do not run key generation over encrypted data. Verify actual target PHP/DB behavior and staging preflight.
5. Review each pending migration against the actual schema, especially historical table/index repair and nullable payment identity changes. Apply normal pending migrations to the copy only. **Never use migrate:fresh, migration-table deletion or checkpoint resets for a legacy rehearsal.** Fresh test fixtures are not evidence of old-version compatibility.
6. Reconcile before/after row counts, identifiers, ownership and historical financial/document values, allowing only explicitly reviewed migration semantics. Test legacy payment reads, keyed intake recovery, permitted corrections, authorization, reports/contracts, catalog read-only behavior and sync bootstrap/replay on a separate clean branch installation. Retain any preexisting sync graph/checkpoints intact.
7. Verify post-upgrade backup/restoration on another isolated copy. If migration fails, preserve diagnostics and restore/replace the staging copy from the untouched bundle rather than force an unsafe rollback. No production changes follow automatically from a passing rehearsal.
8. Record exact artifact/hash, export checksum, target versions, migration output, reconciliations and named approval. Mark the rehearsal passed only after this actual historical copy succeeds.

## Physical branch preparation and acceptance order

Use [the runbook](offline-branch-deployment.md) and [existing eight-scenario acceptance checklist](offline-branch-acceptance.md); their RPO wording is updated to match this audit.

- Central staging: isolated HTTPS site/database/storage, account-specific checks above, pinned matching release and build, scheduler, backup/monitoring and named deployment administrator. Keep production untouched and external integrations disabled.
- Local branch A: Laravel/MariaDB server with reserved LAN address/name, trusted local TLS, private DB binding/firewall, service account/ACLs, full-disk protection, correct clock/time sync, storage/power plan and auto-start of web/DB/scheduler after restart. Preflight under CLI and actual service identities; do not expose the branch database publicly.
- Configure persistent sessions and a cross-process atomic-lock cache (for example a validated database/file backend), not testing array stores. Use separate installation storage/cache namespaces where a cache service is shared. Prove worker/backup nonoverlap and branch-B independence with actual service processes; merely naming a driver in preflight is not that proof.
- Two authorized Staff computers: different personal accounts, same branch hostname and same shared local records. Confirm both can see a committed case/payment. Branch Admin remains read-only on catalogs and cannot gain Staff recording permission.
- Separate branch B installation/isolated VM or server: independent database/identity/token and connectivity outside A's deliberate WAN isolation, for isolation/continued-operation tests. A second browser on A's machine is not a second branch or physical two-computer LAN proof.
- Prepare synthetic identifiers, expected cents/counts/checksums, backup bundle/key custody and evidence templates. Freeze/bootstrap/reconcile/verify-ready/activate only through controlled cutover; never introduce competing central and local writers.
- After staging feasibility and legacy rehearsal pass: run two-computer normal operation; disconnect WAN only while preserving LAN/power; create/record offline; restart actual server; reconnect; verify acknowledged versions and zero missing/duplicate payments; simulate supported failures; restore fully to isolated replacement and reconcile; confirm B continues while A is offline.
- Attach dated device names, operator/branch representatives, release/DB versions, outage times, scheduler/backup evidence, record identifiers/counts/cents and safe sync versions/hashes. Do not log personal payloads, tokens or keys. Complete five consecutive working days and the agreed real elapsed outage interval before pilot sign-off. Clock travel/browser URL blocking are automated evidence, not physical test completion.

## External resources needed and final classification

Required to proceed: an isolated Hostinger staging account/site/database and permission to inspect its actual settings; provider confirmation or a suitable staging DB for packet/memory/time/process requirements; verified historical DB/files/key package; representative volume/growth baselines; named central/branch administrators and key custodians; local server, two Staff computers, branch-B test environment, LAN/TLS/power and off-device storage/monitoring; replacement equipment and branch participation.

**Local Validation is confirmed. Staging feasibility is conditional; staging acceptance is blocked by missing historical export and unverified target account/resources, with an additional confirmed local packet-capacity failure and unresolved strict-RPO configuration.** No actual plan incompatibility is asserted without account evidence. No deployment, physical outage, real hardware restoration or production-readiness claim is made. Previously reported dependency-advisory disposition remains a release gate; this task did not perform a new package upgrade/audit.

## Exact changed files

1. `app/Http/Controllers/Staff/IntakeController.php` — confirmation-copy clarification only.
2. `tests/Feature/SystemRegressionGuardsTest.php` — assert recovered case code and explicit retry-edits wording.
3. `scripts/offline-preflight.php` — nonsecret observed-SAPI/runtime/protocol/database-version fields and payment-identity migration presence check, retaining all existing pass/fail gates.
4. `docs/offline-branch-deployment.md` — correct off-device RPO timing/alert/configuration requirements.
5. `docs/offline-branch-acceptance.md` — corresponding executable RPO acceptance gate.
6. `docs/offline-branch-smart-plan.md` — align the proposed recovery objective and alerts.
7. `docs/staging-feasibility-audit-2026-10-22.md` — this assessment, prerequisites and results.

Generated verification artifacts are under `storage/framework/staging-*`. Production environment values and server-global limits were not changed.
