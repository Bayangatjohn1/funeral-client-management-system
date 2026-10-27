# Offline branch deployment and recovery runbook

Updated 22 October 2026. The feature is opt-in. This document is not evidence of deployment or a completed branch pilot. The old Hostinger system is unchanged. See [the staging feasibility audit](staging-feasibility-audit-2026-10-22.md) for current verified blockers, target-account unknowns and prerequisites.

Regression follow-up: see [the per-failure correction report](system-regression-fixes-report.md). Stage migrations `2026_10_21_140000_add_payment_submission_identity` and `2026_10_21_150000_finish_deceased_client_index_repair` before coordinated central/branch upgrade. Sync schema fingerprints include the new payment fields, so mismatched versions must be upgraded together; retain pending envelopes and checkpoints. The second migration preserves foreign keys and records and intentionally does not recreate the obsolete unique client/branch index on rollback. After keyed payments have been accepted, retain submission identity/hash columns during application rollback so retries remain identifiable. Rehearse backup restoration and schema compatibility before activation; do not run this preparation against Hostinger production.

## Ownership and architecture

- `standalone` preserves the existing installation. `central` enables the protected synchronization API and consolidated reporting. `branch` uses a local Laravel server and shared local MySQL/MariaDB database; Staff and Branch Admin use its same LAN hostname online and offline. Branches initiate outbound HTTPS; never expose their database ports publicly.
- System Admin centrally owns packages, add-on/freebie/casket catalogs, prices, catalog status and account administration. Branch Admin remains read-only on catalogs. Casket availability is status only; stock deductions/transfers/inventory are excluded.
- Activated branches own clients, deceased, cases, service details, payments/corrections, drafts, notes, attachments, contracts/revisions and operational audit entries. UUID mappings translate independent numeric primary keys. New branch payment/receipt/contract numbers have branch prefixes; historical identifiers/pricing/document snapshots remain intact.
- Central legal-hold placement/release remains centrally authorized. Controls are pulled to branches; stale operational snapshots cannot overwrite central holds. A disconnected branch cannot receive a newly placed hold until reconnection. No irreversible disposal should be approved on a disconnected branch based on stale controls.
- Central operational requests and completion/retention jobs skip frozen/active branches. Other branches continue their current workflow. Owner reports indicate last received/contact/pending/stale data; disconnected branches cannot provide live totals.
- Branch password/MFA administration/enrollment/recovery remain central. Local authenticator login works within the default 168-hour account synchronization window. Revocation/password/role changes take effect after receipt; immediate offline revocation is impossible. Local TOTP replay counters never move backward. Other branch credentials and global admin secrets are excluded; global administrators use disabled attribution stubs locally.

## Protocol, capacity and retained storage

This is a complete-snapshot protocol, not per-record incremental batching. A committed snapshot is staged in an encrypted durable outbox before network access. Only one unacknowledged version is staged; later local transactions stay in the database for the next version. Version/fingerprint matching provides idempotent retry and rejects skipped/conflicting versions. Central applies the graph transactionally and checks ownership, references, completeness, files, audit immutability and financial totals in exact cents. Failure does not advance its checkpoint.

Plain snapshot limit: **20 MiB**; file limit: **10 MiB**. Do not increase them without measuring representative data and hosting resources. New envelopes gzip before authenticated encryption (`BRS2` marker); upgraded readers also accept legacy uncompressed envelopes. Decompression has an expansion bound. Deploy matching central/branch releases before sending compressed envelopes; an old reader cannot read the new envelope. Existing durable outboxes remain readable and must not be discarded during upgrade.

New persistent outbox payloads also compress before APP_KEY encryption; legacy encrypted JSON outboxes remain readable. Fingerprints still hash the original uncompressed JSON, so version/hash acknowledgment semantics do not change. A measured MariaDB `max_allowed_packet=1 MiB` caused an earlier uncompressed outbox insert to exceed the database packet limit. The encoder now checks actual packet capacity with safety overhead before submitting an insert/update. An oversized payload fails with a safe capacity reason and leaves source transactions/checkpoints intact. Require `max_allowed_packet >=64 MiB` for the worst-case configured snapshot size on deployment; compression success with a synthetic dataset does not waive that requirement. No server-global packet configuration was changed by this task.

Measured larger SQL batches require bounded HTTP timeouts: default response timeout is now 120 seconds; connection timeout is 10 seconds. Validate actual hosting execution/request limits and memory, not only CLI settings. File uploads yield after about four minutes and resume from hash inventory. Retry backoff is 15, 30, then at most 60 seconds, with a minute scheduler. Worker/backup locks expire after 15 minutes; measured tasks must finish within that bound.

Files are frozen by checksum in private immutable storage and transferred separately. Older acknowledged payload bodies are cleared while version/hash history remains. Latest acknowledged/pending payloads and immutable blobs/grants are retained. Automated orphan-blob garbage collection is NOT implemented. Monitor spool, upload and archive disk usage; never delete files needed by pending snapshots or recovery. Full snapshots must be measured against actual years of history, audit data and document snapshots; synthetic case/payment benchmarks are not workload forecasts.

## Known environment and deployment prerequisites

The identified old deployment is `https://sabangan-caguioa.com/login`; the updated application is not deployed. Its public page could not be inspected through the available fetch tool. No hosting credentials, deployed code, verified legacy export or hosting staging was supplied. Local sample branches do not establish actual branch/user counts.

Before hosting staging:

1. Obtain a verified database/file export and exact old release. Preserve the existing central `APP_KEY`; never generate a replacement key on an existing installation. Rehearse additive migrations on an isolated clone and compare case/payment counts, exact balances, user access, historical identifiers and sample contracts/files before/after.
2. Confirm a PHP version compatible with composer.lock, extensions, MySQL/MariaDB, writable storage, trusted HTTPS/proxy settings, cron and external HTTPS access. The project minimum is PHP 8.2, but verify the actual supported Hostinger version selection. Test web and cron PHP independently.
3. Require sufficient POST capacity for the encrypted request, for example `post_max_size=48M`, and file uploads >=11M. Confirm web-server/proxy limits and actual PHP execution/memory limits using representative data. CLI preflight alone cannot verify these.
4. Prepare each local server, reserved address, DNS/TLS, router/LAN, power, backups, free disk and timekeeping. Serve ONLY Laravel `public/`. Protect .env/keys, application files, private storage and database with host ACLs/firewall/private bindings. Offline operation still requires the server, LAN and power.
   A reviewable Apache TLS/LAN example is provided in `docs/offline-branch-lan-vhost.example.conf`; it has not been enabled. Replace its sample subnet/hostname/certificates and test two actual staff computers.
5. Review shared schedules/cross-branch resources: disconnected information cannot establish a centrally confirmed reservation. This release does not add a new cross-branch reservation protocol.

Provider references reviewed: [PHP parameters](https://www.hostinger.com/support/4622479-how-to-change-values-of-php-parameters-in-hostinger/), [cron troubleshooting](https://www.hostinger.com/support/1583514-troubleshooting-cron-jobs-at-hostinger/), [PHP version selection](https://www.hostinger.com/support/1575755-how-to-change-the-php-version-of-your-hostinger-hosting-plan/). These are provider documentation, not verification of this account.

## Release build and isolated central setup

```text
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
```

Run migrations only on the authorized staging installation at this stage. Never run tests, seeders, `migrate:fresh`, `migrate:refresh` or `db:wipe` on production. Upload BOTH `public/build` and generated `public/vendor/offline` (fonts/licenses, Flatpickr, Chart.js). Central/branch modes deliberately ignore `public/hot`; standalone development retains its existing Vite setup.

Central staging configuration:

```dotenv
BRANCH_SYNC_MODE=central
APP_URL=https://your-staging-domain.example
APP_DEBUG=false
```

Rebuild configuration cache after changes. `php scripts/offline-preflight.php` produces a read-only, secret-free checklist from the configured installation. It does not certify web-SAPI limits, scheduler auto-start or LAN accessibility. Existing unrelated working-tree changes must also pass their release gates.

## Controlled preparation, freeze and activation

Lifecycle: **prepared -> frozen -> active**. New token issuance starts prepared and keeps central recording available. Rotation preserves the existing lifecycle/installation/checkpoint. The additive lifecycle migration marks existing peers active, preserving previously transferred ownership rather than silently returning it to central.

First prepare the branch runtime, empty database, compiled assets, TLS/LAN, scheduler, backup destination and centrally enrolled user accounts. Then on central staging:

```text
php artisan branch:sync issue-token --branch=BR001
```

Store the token securely only on the owning branch; it is displayed once and central stores its hash. A prepared token can fetch references but cannot bootstrap or push operational data. Do not print tokens in logs or monitoring evidence.

Create a NEW EMPTY branch database using the same updated migrations. Do not copy a full central database to every branch or run seeders. Configure:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-trusted-local-hostname.example
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_new_branch_database
DB_USERNAME=your_branch_database_user
DB_PASSWORD=your_branch_database_password
CACHE_STORE=database
SESSION_DRIVER=database
BRANCH_SYNC_MODE=branch
BRANCH_SYNC_CODE=BR001
BRANCH_SYNC_CENTRAL_URL=https://your-staging-domain.example
BRANCH_SYNC_TOKEN=the_separately_delivered_token
BRANCH_SYNC_MAX_OFFLINE_HOURS=168
BRANCH_SYNC_TIMEOUT=120
```

Only this NEW branch receives a fresh independent application key:

```text
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
```

At an agreed cutover window, notify staff and stop central encoding for the branch, then run centrally:

```text
php artisan branch:sync freeze --branch=BR001
```

Freeze serializes with central operational writes and scheduled completion/retention. Both operational writers are now paused. On the branch:

```text
php artisan branch:sync bootstrap
php artisan branch:sync status
```

Bootstrap requires an uninitialized database without branch/user/operational data. Historical demo catalogs are replaced transactionally only on that otherwise-empty installation. It imports this branch's historical operations/files, shared references and required users; MFA secrets are translated to the new branch APP_KEY. Other branch staff credentials are not distributed.

Frozen local login/read checks are allowed, but local operational writes and scheduled case changes remain blocked. Compare counts, exact cents/balances, UUID links, sample files, issued contracts and catalog/status values against frozen central. Check two actual LAN computers, login/MFA, local assets, service/scheduler auto-start and the backup/key/off-device plan. Create and verify a complete backup. Then:

```text
# Branch: confirms installation and received frozen snapshot hash
php artisan branch:sync verify-ready
# Central: only after the operator completes all checks above
php artisan branch:sync activate --branch=BR001 --reviewed
# Branch: receive active ownership before recording
php artisan branch:sync run
php artisan branch:sync status
```

Readiness confirms the bootstrap identity/hash, not physical LAN usability or human reconciliation. The operator's `--reviewed` attests those checks. Until active state is received, local recording remains blocked. After activation staff always record on the same local application, online and offline; central remains its synchronized replica.

### Provisioning fails before activation

Run centrally `php artisan branch:sync cancel --branch=BR001`. This only allows prepared/frozen peers with accepted version zero. It disables access, returns central ownership and preserves peer identity/checkpoints. Confirm central recording is available. Quarantine the unactivated local copy. If central records subsequently change, preserve that copy for diagnosis and prepare a new empty local database/bootstrap after a fresh freeze; do not resume its stale copy. Never delete peers or reset checkpoints.

Cancellation is forbidden after activation, even before the first push. Revoking an active token also does NOT restore central writes. Other branches retain their own ownership/workflows.

## Scheduler, backups and local security

Run `php artisan schedule:run` every minute. On Windows Task Scheduler, executable `C:\xampp\php\php.exe`, arguments `artisan schedule:run`, Start in the application directory. Run without an interactive desktop, auto-start after restart and prevent overlapping processes. On Hostinger verify the supported cron setup and actual execution. No permanent queue worker is required for synchronization.

Branch encrypted backups run hourly; central/standalone retain the configured daily schedule. Branch backup acquires the sync-worker lock and source row lock until committed database dump and public/private file archive are complete. Operational requests briefly wait. A running sync worker causes safe backup refusal; monitor/retry instead of treating the scheduled attempt as success. Backup archives exclude backup-work and previous system-backup files.

Copy verified archives OFF the branch server; preserve the matching APP_KEY separately in approved secure custody. Pilot proposal: RPO <=1 hour and full replacement-server restore <=2 hours; agree these with the business. Measure recovery-point age from the conservative backup start/snapshot time of the latest successfully verified OFF-DEVICE archive, never its upload-completion time. Monitor at least every five minutes, warn at 40 minutes, escalate critically at 50 minutes, and record a target breach above 60 minutes. Alert immediately on failed/skipped backup or copy verification and retry/escalate; a 90-minute alert is too late for this target.

The application still creates hourly branch backups. Hourly cadence PLUS dump/encryption/copy time cannot strictly guarantee a <=60-minute off-device recovery point. Before signing off this RPO, configure and measure an approved cadence with interval + worst-case completion/copy lag + response margin <=60 minutes (planning example: 30 + 20 + 10). Choose one coordinated trigger owner, avoid duplicate triggers, and validate lock waits/sync-worker refusals and retries under load. Any cadence change must be explicitly configured/tested on staging; this runbook has not changed the application schedule or configured a transfer service. Otherwise record the measured longer RPO for business approval rather than claim one hour. Host-level secure off-device copy/checksum verification is required and not configured by the application. Same-disk archives do not meet the RPO. Size 14 days of the selected full-backup cadence (336 archives at hourly, 672 at every 30 minutes), working space and off-device retention. The full-size hardware recovery target is not established by a tiny SQL-import timing.

Deploy MySQL/MariaDB branches. Live SQLite branch backup is refused because copying a hot main file cannot guarantee a consistent database/file bundle. Standalone SQLite uses `VACUUM INTO`, including committed WAL pages. For SQLite diagnostic recovery, stop web/scheduler/all writers and cold-copy database/WAL/SHM, public/private files and original key together.

Keep password hashing compatible and server time correct. Deactivate migrated users centrally rather than deleting historical actors; profile deletion is blocked for frozen/active branch accounts. Received account changes invalidate sessions through existing security fingerprints. Local account access expires after the configured disconnected window; logout remains available during recovery holds.

Snapshots/backups are encrypted. Operational database rows and immutable private blobs are not separately encrypted by this feature: require full-disk encryption, ACLs and restricted host/LAN access. Public photos retain the existing public-storage policy; do not claim all uploads are private. Review this policy with the business before pilot.

## Failures, restoration and rollback

- Network failures retain local records and durable outbox/files. Matching version AND fingerprint are required before acknowledgment. Lost acknowledgment replays safely; never re-enter the payment centrally.
- Schema/ownership/file/financial failures roll back the entire central apply. Ordinary staff receive a safe error; protected logs and `branch:sync status` provide operator reason categories. Correct source inconsistencies through authorized audited workflows.
- `php artisan branch:sync restage` can rebuild a corrected pending snapshot only after central confirms its version was never accepted. Then run sync. Restage cannot overwrite accepted checkpoints or repair an old restored database.
- Restore into an isolated replacement first with outbound sync/staff access disabled. Verify checksum/decryption and restore database, public/private files, immutable pending blobs, mappings, outbox/checkpoints and matching APP_KEY together. Confirm installation identity and compare central accepted version/hash before releasing writes.
- Matching checkpoints can resume normal delivery. A lost acknowledgment can replay the exact pending version. Older/different checkpoints or changed installation identity trigger a recovery write hold. Do not delete tables, decrement sequence, rerun bootstrap over data, or clear the hold to bypass reconciliation.
- Active rollback has no automatic return-to-central command. Stop branch staff/schedulers, preserve the latest complete copies/key, reconcile all committed/unsynchronized transactions against central in isolation and verify identifiers, cents, files/documents. If local data is lost, retain the central write block until authorized reconciliation. Never operate recovered and original branch writers together. Rolling code back to an old reader while new branches still send compressed envelopes is unsupported; coordinate compatible release/data restoration.
- Financial/case/client/deceased records cannot disappear silently. Child set removal propagates; central audits are retained. Historical pricing/documents are not repriced by catalog pulls. Legal holds flow from central and remain protected from stale branch operational updates.
- Immutable file GC remains operator-reviewed future work. Do not delete files referenced by pending payloads, accepted recovery state or retained backup procedures merely to reduce disk usage.

## Required release gates

Use [the executable acceptance checklist](offline-branch-acceptance.md) for eight scenarios and the five-consecutive-working-day pilot. Before pilot: verify old-export migration, actual hosting central/branch HTTPS, representative capacity/memory/files, all applicable regression/security findings, real two-computer LAN/outage/restart/reconnect, complete replacement-server restore and branch representative sign-off. Automated SQLite/MariaDB tests and clock travel do not satisfy physical outage/pilot gates.

See [final review](offline-branch-review.md), [validation evidence](offline-branch-validation.md) and [SMART plan](offline-branch-smart-plan.md). After pilot acceptance retain the estimate of two working days per additional ready branch plus five working days of shared monitoring. Production deployment remains a separate controlled approval gate.


Preflight is read-only by default. To verify actual private storage access, run `php scripts/offline-preflight.php --probe-storage`; this writes and removes one temporary file on that installation. Run it under the intended service identity too. The local probe passed; the local 1 MiB database packet setting remains a failed deployment-capacity gate.
