# Minimum branch infrastructure and acceptance plan

Prepared October 25, 2026, Asia/Manila. **Planning only; no installation, purchase, transfer integration or host configuration change authorized by this document.** Current classification remains Local Validation. Architectural direction is approved: encrypted local backup, verified independent LAN copy, additional secure off-site copy when connected. Actual destinations and implementations remain subject to approval.

## 1. Minimum affordable equipment

Start with **one branch staging site**, prove it, then repeat the same setup for the other two branches. No NAS, Windows Server license, domain controller, container cluster or separate monitoring server is required for this initial plan. Equipment ownership has not been inventoried; reuse below is conditional on inspection and availability.

| Component | Concrete test baseline | Can existing equipment be used? |
|---|---|---|
| Branch server B1 | Dedicated 64-bit PC/mini-PC, 4 CPU cores, 8 GiB RAM minimum test baseline (16 GiB preferred), 512 GB SSD, wired gigabit Ethernet, supported licensed OS; no daily staff browsing/use | Yes, a healthy spare PC may qualify after storage/runtime checks. Current development PC remains unchanged and is not the unattended acceptance server. |
| Independent backup PC BK1 | Different physical PC and disk, 2 CPU cores, 4 GiB RAM for copying (8 GiB if also replacement-test host), wired Ethernet; 1 TB usable backup storage as a provisional starting size | Yes, a secured spare desktop with reliable disk can replace a NAS initially. Must remain on and protected; not a second folder/partition on B1. Capacity formula below overrides the provisional disk size. |
| Staff clients C1/C2 | Two separate existing PCs with supported browsers, individual authorized Staff accounts, LAN connectivity | Normally reusable. They access HTTPS only; no PHP, MariaDB or shared database files installed on clients. |
| LAN/power | Router with local DHCP/DNS that works without WAN; gigabit switch with at least 5 ports if router ports insufficient; Cat5e-or-better cables; UPS for B1, BK1 and router/switch | Reuse after tests. Size UPS from measured combined load for at least 10–15 minutes and graceful shutdown, not an assumed VA rating. No UPS hardware was tested. |
| Replacement test capacity | BK1 can double as temporary isolated replacement host if 4 cores/8 GiB+, sufficient **separate restore working space**, compatible runtime and safely isolated storage/network | This avoids an extra test PC. Keep its independent archives read-only to the restored app and preserve collector state. If isolation/capacity cannot be ensured, borrow a separate replacement PC. |
| Central staging | One isolated central Laravel/database instance on an approved reachable host, separate domain/schema/files/keys and test branch tokens | Existing approved staging hardware/hosting may suffice. Current Hostinger production is not staging. Actual account compatibility remains unverified. |

Four physical local PCs (B1, BK1, C1, C2) are enough for the initial two-client/offline/backup/replacement tests if BK1 meets replacement requirements. A second simulated branch on isolated staging is useful for protocol tests, but does not prove another physical branch is operating; real multi-branch acceptance requires a second separate branch installation and clients/network.

These are engineering starting specifications, not benchmarked capacity guarantees or shopping recommendations for a particular model. Check disk health, OS support, memory, free space, cooling and automatic startup before approving reuse. Do not buy a NAS or subscription merely to begin the pilot.

## 2. Software and branch access requirements

- Supported Windows installation with required Task Scheduler, SMB and PowerShell capabilities. Check edition-specific administration/encryption support on the selected host; do not assume features from Windows Server/Pro exist on Home.
- Existing Laravel 12 application release and Composer lockfile; PHP `^8.2` per composer.json. Use one explicitly tested, maintained PHP build for both web and CLI; verify extension/runtime compatibility rather than copying the development machine blindly.
- Apache HTTPS virtual host serving **only public/**, rewrite support and local trusted TLS certificate. Install/trust the local CA on C1/C2 so HTTPS works without internet. Protect CA private keys separately. Existing LAN vhost example must have its development path replaced before use.
- MariaDB with the existing migrations and SQL dump/restore clients tested against that exact version. Local validation used MariaDB 10.4.32; this does not certify every newer version or endorse deploying an unsupported release. Select a maintained version and run compatibility suites on staging before acceptance.
- PHP extensions: pdo_mysql, openssl, zlib, fileinfo, mbstring, zip, plus all locked Composer platform requirements (including Laravel/XML/DOM dependencies). Run platform checks and the existing read-only preflight on the **new** installation. Confirm CLI process creation/dump capability, permissions and web/CLI configuration separately.
- Database bound to loopback; explicit dedicated schema/user/port, least privileges needed for application and backup dump (including configured triggers/routines). Deployment migrations use separately authorized access. Required `max_allowed_packet >=64 MiB` is an existing deployment gate; do not modify the current PC to satisfy it.
- Start staging with PHP memory limit 512 MiB as the prior local baseline, then measure realistic backup peak usage. It is not sufficient by guarantee: full archive/encryption buffers consume memory. Validate dump timeout, sync request timeout, HTTPS/body limits and storage with existing limits unchanged: 20 MiB plaintext snapshot, 10 MiB/file.
- Deploy vendor dependencies and precompiled frontend assets, including public/build and public/vendor/offline. No Node/build server, CDN or Vite dev-server dependency at branch runtime. Set production-style debug/secure cookies and correct branch mode on staging only; use database-backed persistent cache/session as the runbook specifies.
- Securely provision branch accounts/catalogs and the existing prepared/frozen/active cutover through **central staging**, preserving one operational writer. Use test data. Never provision by copying all central branch records/users indiscriminately.

Deployment administrator needs approved OS administration for initial setup and secure access to staging keys/credentials. System Admin retains application Backup & Recovery/Data Retention authority; operational Staff/Branch Admin receive only existing branch permissions. Technical task accounts are OS identities, not new application roles.

## 3. LAN layout and isolation

Example dedicated test subnet, to replace if it conflicts with the actual LAN:

| Device | Example reservation/name | Access |
|---|---|---|
| Router | 192.168.50.1 | DHCP/local DNS remain available with WAN cable unplugged |
| B1 | 192.168.50.10, branch-test.example.internal | C1/C2 HTTPS 443; SMB source read restricted to BK1; database loopback only |
| BK1 | 192.168.50.20 | Pull archives from B1; protected local backup volume; outbound approved off-site HTTPS/secure transfer |
| C1/C2 | 192.168.50.31/.32 | Same local hostname online/offline; unique accounts; no SMB backup/database access |

Reserve addresses on the router; local DNS must resolve B1 without public DNS. Scope host firewalls to exact hosts/subnet and approved technical administration paths. No router port-forwarding of database, SMB or local application. Use authenticated SMB 3 with signing and encryption where supported; verify negotiation/compatibility rather than enabling guest access. Time/timezone must stay correct during outage for authentication and evidence. Test by unplugging **WAN only** on dedicated equipment, leaving switch/router/LAN powered.

## 4. Automatic startup and scheduler specification

On approved dedicated equipment, configure database and Apache services to start automatically without login and recover after failure; verify actual application readiness and service dependencies. Keep B1/BK1 awake during operating/backup periods. Approved firmware power-restoration behavior and UPS shutdown/startup must be tested. Neither task metadata nor an interactive startup shortcut proves unattended recovery.

| Task | Proposed configuration, requiring approval |
|---|---|
| B1 Laravel dispatcher | Absolute tested php.exe; `artisan schedule:run --no-interaction`; working directory dedicated staging root (for example C:\BranchStaging\funeral-system); repeat every minute indefinitely; startup trigger with readiness check; no WAN or logged-in-user requirement |
| B1 OS account | Dedicated non-admin account; Run whether user is logged on or not; Log on as a batch job; read app/runtime/protected configuration, modify required storage/cache/logs only; no destination backup deletion credentials |
| BK1 collector/health task | Dedicated local account, noninteractive execution; proposed five-minute polling; startup readiness check, one active instance; read-only source SMB credential; local archive/receipt/log write permission only as necessary |
| Common settings | Ignore new instance if already running; protected logs and task history; inspect child failures; no unbounded retries; execution/stall thresholds measured against workload and existing 15-minute application lease |

Existing Laravel **hourly branch**, **daily central** schedule and **14-day configured retention** remain unchanged. Collector polling is proposed transfer/monitoring scheduling, not a change to backup creation cadence. Do not clear application locks/checkpoints to make runs pass. OS StartWhenAvailable does not replay Laravel's missed hourly minute; stale backup detection is required.

Network-capable noninteractive authentication must be tested. Microsoft states S4U cannot access network resources/encrypted files; select a supported approved method and store credentials through protected OS facilities, never script/plaintext repository files. Credential rotation must include task/SMB retesting. [Microsoft logon types](https://learn.microsoft.com/en-us/windows/win32/taskschd/taskschedulerschema-logontype-principaltype-element), [task security context](https://learn.microsoft.com/en-us/windows/win32/taskschd/security-contexts-for-running-tasks).

## 5. Recommended copying approach — specification only

Use **destination-initiated pull** from BK1. A source SMB identity gets read access only to the encrypted backup/export directory on B1, not .env, uploads, SQL dumps, private documents or the application tree. BK1 stores copies in a local restricted directory not shared for staff access and not writable from B1. The backup PC holds its own approved off-site credentials. This reduces B1's ability to delete independent copies, but does not make an ordinary writable PC immutable or immune to compromise.

Implement after approval as a small PowerShell collector using built-in Robocopy for resumable transport and destination-side SHA-256. It needs an **explicit verified-source manifest/export**, generated read-only from backup metadata, containing branch/installation ID, backup ID, relative archive path, size, SHA-256, verification state and conservative source recovery timestamp. That safe export and collector/receipt integration **are not currently implemented**. Do not give BK1 general application DB credentials or copy every file based solely on its extension. Manifest publication must be atomic, path-restricted and authenticated by the secured source/transport; checksum equality alone does not establish source authenticity.

Collector requirements:

1. Read only eligible verified manifest entries; reject invalid paths, unknown branch, mutable/unverified entries and size/checksum inconsistencies. Use an identity keyed by installation + backup ID + checksum.
2. Copy encrypted bytes into a destination `.partial` work directory; use resumable copy and bounded transport retries (proposed `/Z /R:2 /W:5`), never `/MIR`, `/PURGE`, backup privilege overrides or source deletion. Microsoft documents that Robocopy defaults to very many retries; explicit bounds prevent a stuck job. Exit codes >=8 indicate copy failure; codes below 8 still require file/receipt verification. [Robocopy documentation](https://learn.microsoft.com/en-us/windows-server/administration/windows-commands/robocopy).
3. Calculate SHA-256 **on BK1's local destination bytes** and compare size/hash to the trusted manifest. After match, atomically publish within the same destination volume and persist a receipt. Existing matching receipts make retries idempotent; differing bytes are quarantined and reported, not overwritten as success. [Microsoft Get-FileHash](https://learn.microsoft.com/en-us/powershell/module/microsoft.powershell.utility/get-filehash?view=powershell-7.6).
4. Persist attempts and errors; retry pending work on the next five-minute poll, with bounded attempts per cycle and no busy loop. Alert after the first failed poll and on persistent failure; do not suppress old failures merely because a later task started. Clean abandoned partial files only under an approved policy after checking they are not active.
5. Create separate local/LAN/off-site receipts. Off-site uploader reads only published encrypted copies and uploads when connected, with provider-appropriate checksum verification, retry and restore tests. Provider, location, retention, credential scope and any version/deletion protection require selection/approval first. No service is being purchased or assumed available.
6. Keep APP_KEY and recovery credentials in separate authorized custody, not alongside archives as plaintext. Full decrypt/SQL restore integrity is verified in an isolated recovery test with the matching key; ordinary copy verification does not need the key on BK1.

Independent-copy expiration/deletion behavior must be approved to match existing retention and any protected recovery records/holds. Do not apply a blanket 14-day filesystem purge or introduce indefinite retention silently. Existing application retention remains untouched.

## 6. Existing functionality versus work still needed

| Already implemented/locally validated | Missing implementation or operational configuration |
|---|---|
| Encrypted local archive, checksum/decryption/archive verification, backup ledger and authorized management | Dedicated host provisioning, unattended task/service setup and actual hourly/restart/WAN evidence |
| Database/files/outbox/mappings/checkpoints in recovery bundle; isolated same-PC recovery checks | Destination PC/share ACLs, safe manifest export, pull collector, receipts, retry handling and independent storage restore |
| Backup/sync locks and failure status; hourly branch vs daily central | External health collection, missing/corrupt archive rechecks, delivery monitoring, alert routing and response roster |
| Existing System Admin permissions and retention controls | Approved off-site destination/integration and secure key custody/retrieval drill |

No new application code or UI is needed for this planning stage. Later transfer/export/monitoring work requires approval and regression tests; it must not alter operational synchronization or roles.

## 7. Accurate System Admin health without new permissions/UI

Initially use an approved **external operational status report and alert channel**, delivered only to System Admin/authorized technical personnel. BK1 can produce a restricted machine-readable receipt log and daily summary for all three branch IDs; a central approved receiver can aggregate health when reachable. This receiver/transport is a proposed small monitoring configuration/integration, not existing application reporting or a new dashboard. Do not attach patient/client details, tokens or keys to alerts.

Report branch, last contact, scheduler observation, last verified local source time, last verified LAN/off-site source time, archive ID/size/hash, errors/backlog and available space. Use explicit states: verified, pending, failed, stale, unknown. Never equate a received heartbeat or copied filename with a verified recovery point. An independent BK1 check of local archives/receipts can detect a failed B1; central missing-BK1 heartbeat detects loss of the monitor itself. Missing evidence is unknown/stale, not healthy.

Central Backup & Recovery currently reads its own database; branch backup ledger entities are not synchronized. Existing UI therefore cannot be assumed to show all branch failures. No Staff/Branch Admin management access is required for the proposed technical collection. Keep authorized manual backup/recovery and retention in the current application workflow.

During WAN outage, BK1 records and reports locally to an approved technical/System Admin access path; remote central visibility shows last contact/unknown. Internet-based alerts cannot be guaranteed. Queue events and send a consolidated recovery report when connected; store-and-forward delivery remains to be implemented. Approve who is on site/responds when no remote contact is possible.

Keep the existing one-hour RPO proposal visible: hourly creation plus backup/transfer/verification delay cannot guarantee a strict 60-minute off-device point. Measure source-point age separately per tier. The prior 40/50/60-minute warning/critical/breach proposal may alert routinely with hourly cadence; approve practical thresholds/objective after measurement. No cadence, retention or objective is silently changed here.

## 8. Capacity and three-branch rollout

Per destination: roughly `336 × measured average full encrypted archive size` for hourly/14-day planning, plus retained protected records, versions, failed/partial files, restore workspace and reserve. At 1 GiB/archive: 336 GiB per branch, about 1,008 GiB across three branches, before overhead; at 100 MiB/archive: 32.8 GiB per branch. A 1 TB nominal disk is consequently only a starting suggestion, not guaranteed capacity. Measure real history/documents and add at least 25% planning headroom after working-space needs; alert before the next backup/restore cycle cannot fit.

B1 also needs live database/uploads/outbox plus dump, ZIP, encryption and local archives. BK1 needs independent archives plus a separate complete restore working set if reused as replacement. A second disk improves isolation/capacity but is not an additional off-device tier by itself.

For three branches, plan one local server and one independent LAN backup PC at each site, using existing clients/network where qualified. A shared approved off-site destination can have three separately credentialed/prefixed areas and one System Admin alert channel. Do not add inter-branch database sharing, a WAN dependency for local backups, or a new central appliance just for this plan.

## 9. Practical setup order and pass criteria

| Step | Work after approval | Acceptance evidence |
|---|---|---|
| 1 — Inventory and assign | List available spare PCs, disk health/free space, OS support, router ports/DNS, UPS and location; name technical operator/System Admin | Approved B1/BK1/C1/C2 identities, capacity calculation, ownership and access roster; unavailable equipment explicitly listed |
| 2 — Isolated installation | Provision central staging/B1 schema, runtime, assets, TLS/LAN and branch accounts; follow prepared/frozen/active runbook on test data | Preflight/platform checks, matching release, tested packet/body limits, two authorized clients; production/development unchanged |
| 3 — Unattended host | Approve/install tasks/services and credentials on B1/BK1; export definitions | No interactive login; two successive **actual hourly** verified backups with timestamps/IDs/hashes; central daily schedule remains separate |
| 4 — LAN collector | Implement approved safe export/pull/receipts; approve ACLs and storage lifecycle | Hash confirmed on BK1; retry/lost receipt causes no duplicate logical copy; B1/C1/C2 cannot modify destination archives; source keys not copied |
| 5 — Failures/alerts | Isolated source job failure, locked/skipped job, missing/corrupt copy, unavailable share, bounded quota simulation and stalled collector | Correct failed/pending/unknown status and alert; next successful normal run/copy reconciles; no disk filling or blind lock resets |
| 6 — Real offline/restart | WAN disconnect with LAN/power retained; create cases/payments from both clients; restart B1 on dedicated equipment | Local saved records remain; scheduled local and LAN copy continue; reconnect acknowledgment/totals correct/no duplicate payments; restart recovers without login |
| 7 — Off-site | Select/approve destination, implement upload/verification/alerts | Actual independent checksum/receipt and restore retrieval; WAN failure leaves pending state and retries on return; no false freshness |
| 8 — Replacement restore | Fence original writer; hold isolated replacement on BK1/spare PC; restore complete bundle with matching key | Cases/payments/corrections/audit/documents/users/totals match; outbox/mappings/checkpoints preserved; no competing writer; reconcile centrally without resetting checkpoints |
| 9 — Second branch/pilot gate | Test another branch remains operational while first offline; then authorize five-working-day pilot | Actual branch identities/evidence, named response owner and passed gates; do not claim physical multi-branch proof from one-host simulation |

Steps 1–2 can reuse qualified spare equipment, but no inventory beyond the prior current-PC inspection establishes ownership/readiness. Steps 3–8 need explicit dedicated-equipment/configuration/integration approval. Historical migration rehearsal is still blocked pending a verified legacy DB/files export and key information; Hostinger staging compatibility still needs actual account testing. These are separate prerequisites, not reasons to modify production.

Only this document was added for this request. No application or operating-system changes and no test executions/installations were performed. All acceptance rows above are future criteria, not completed test claims.
