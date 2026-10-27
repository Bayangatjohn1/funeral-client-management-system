# Offline Branch Operation and Central Synchronization — SMART Plan

Status: Local implementation started at the user's request. Production deployment and branch pilot acceptance remain pending.
Prepared: 21 October 2026 (Asia/Manila).

## Objective and schedule

Within 40 working days from the agreed project start, deliver and validate offline operation at one pilot branch, with secure synchronization to an upgraded central application on Hostinger. Cover intake/drafts, clients, deceased, cases/service details, payment recording/corrections under existing permissions, attachments, contracts, local reports and printing. After pilot acceptance, allow 2 working days per additional ready branch, followed by 5 working days of shared monitoring.

This is a planning estimate for one dedicated Laravel developer with a branch representative and deployment administrator available for testing and decisions. Day numbers are relative working days, not promised calendar dates. Phase 1 validates feasibility; material scope, hosting or legacy-data findings require a revised estimate before implementation proceeds beyond the design gate. Actual branch count, hardware readiness and deployed schema are not yet verified.

## Confirmed scope and architecture

- Each branch runs a local Laravel application and database on an always-on local server; staff use its LAN address both online and offline.
- Hostinger hosts the upgraded central application and consolidated owner reporting. Existing production data is preserved through a tested migration.
- Branch servers initiate authenticated outbound HTTPS synchronization. Branch databases are not exposed publicly.
- Operational records have an owning branch. Routine operational writes happen on that branch's server. Remote operational edits on central are excluded from the first release; affected existing central workflows must be reviewed before approval.
- Only System Admin manages packages, add-on catalogs, freebies, casket catalogs, prices and catalog status. Branch Admin remains read-only, enforced by backend authorization.
- Casket availability is a status, not inventory. Stock counting, deductions, procurement and inventory transfers are excluded.
- Catalog data flows central to branches. Cases, payments and related operational records flow branch to central. Sync acknowledgments, account changes and applicable configuration flow back to branches.
- Owner reporting displays each branch's last successful sync; disconnected branches cannot supply current totals.

## Milestones and acceptance gates

| Phase | Deadline | Deliverables | Measurable acceptance |
| --- | --- | --- | --- |
| 1. Compatibility and requirements audit | Days 1–5 | Compare old deployed version/schema with this project; verify actual branches/users, data and file volume, Hostinger PHP/HTTPS/scheduler/storage/resource limits, local equipment, offline duration and shared scheduling dependencies | Document every in-scope module and its data owner, authorization, offline behavior and sync direction; produce legacy-field mapping, hosting decision and revised estimate if needed |
| 2. Sync and migration design | Days 6–10 | Define unique sync IDs without breaking legacy references, branch-safe new payment numbering, record versions, transactional outbox/inbox, deletion markers, file checksums, retry/acknowledgment protocol and account authority | Review example flows for new case, repeated payment delivery, payment correction, stale catalog, file replacement/deletion and interrupted sync; approve migration/rollback and conflict rules before feature coding |
| 3. Local operation | Days 11–16 | Package all required frontend assets locally; configure local runtime, LAN access, login, scheduler and backup; preserve existing permission rules | With internet disconnected but LAN available, complete all agreed core workflows; no external resource is required for these workflows; direct Branch Admin catalog write requests return 403 |
| 4. Synchronization | Days 17–27 | Build branch-scoped HTTPS API, credential rotation/revocation, batched push/pull, bounded retry/backoff, event deduplication, dependency ordering, file synchronization and reconciliation | Pass repeated/interrupted delivery tests with zero duplicate financial entries, zero missing committed test records and zero cross-branch data access; verify attachment checksums and deletion propagation |
| 5. Status, recovery and system verification | Days 28–32 | Add branch pending/failed/last-sync indicators and central freshness status; automate backups; implement mismatch alerts and a resolution workflow | Reconcile case counts and monetary totals exactly; restore one branch and central staging backup successfully; verify recovery does not re-create acknowledged payments |
| 6. Pilot acceptance | Days 33–40 | Deploy to one ready branch after staging acceptance; train staff; execute outage tests and monitor at least 5 consecutive working days | Complete a scripted 72-hour outage test, including restart and reconnect; meet performance and correctness targets below; no unresolved defect causing data loss, duplicate payment, access breach or blocked core workflow; branch representative signs off |

## Pilot test targets

These are acceptance targets to measure, not claims about current capability. Phase 1 confirms a representative dataset and records the exact test hardware and network conditions.

- Functional: every agreed in-scope workflow passes online, offline on LAN, and after reconnection; failed tests block acceptance.
- Correctness: seed at least 100 linked cases and 500 payment events; re-deliver selected events 10 times, interrupt a batch and restart both services. Central records and balances match the branch source exactly, with no duplicate payments or broken relationships.
- Timeliness: when connected, attempt sync at least once per minute; for a backlog of 100 ordinary record changes, excluding file uploads, central acknowledgment completes within 5 minutes on the agreed test connection. Larger backlogs and file transfer rates are benchmarked in Phase 1 and given separate targets in the approved design.
- Catalog consistency: apply catalog updates within 5 minutes under the same connected test conditions; an offline branch shows its last catalog sync and uses the last accepted catalog version. Preserve the price/version used by an existing case instead of repricing it on sync.
- Authorization: test all protected sync endpoints for invalid/revoked branch credentials and wrong-branch access; all such requests are rejected. Branch Admin cannot mutate catalogs through UI or direct requests.
- Recovery: existing hourly branch backups and daily central backups; demonstrate full isolated restoration within 2 hours on pilot hardware. Proposed off-device RPO <=1 hour requires measured backup interval + worst-case completion/copy lag + response margin <=60 minutes; hourly scheduling alone does not establish it. Configure and test the agreed cadence/off-device transfer before sign-off, or approve the measured longer RPO. Monitor snapshot/start-time age every five minutes, warn at 40, escalate at 50 and mark breach above 60 minutes; immediately alert on failed/skipped backup or copy. Secure key custody and verified copies are mandatory before pilot.
- Usability: branch staff can identify pending changes, failed sync and last successful sync; the owner can identify stale branch data without assuming reports are current.

## Offline rules that must be explicit

- Offline operation requires power, the local server and LAN. Internet loss and local server failure are different conditions.
- Local authorized users can log in without central connectivity. Centrally issued password/role/deactivation changes take effect locally after receipt; choose and document the maximum allowed disconnected-login period in Phase 1. Staff account management and recovery authority must have one defined source to avoid competing edits.
- Payment recording does not verify that an online bank/wallet transaction succeeded. Existing verification and correction permissions continue to apply.
- Shared resources and cross-branch actions cannot be treated as centrally confirmed while disconnected. Identify any shared schedules in Phase 1 and define provisional/confirmation behavior before implementation.
- Do not resolve conflicting financial data with silent last-write-wins. Quarantine invalid or conflicting events for authorized review, with audit history.
- Backups/restores and automatic case-completion jobs need installation-specific rules. Central replicas must not independently change branch-owned records; restoring an older backup must reconcile sync checkpoints before resuming writes.
- Branch installations store only their authorized operational data and required shared references. Files, audit history, revisions, soft deletions and retention holds are included in the data-mapping review.

## Migration and rollout

1. Obtain and verify a production database/file backup; compare against the current project without modifying production.
2. Rehearse upgrade, sync-ID backfill and branch extraction on staging. Preserve historical payment identifiers and case links.
3. Verify row counts, balances and sample documents before and after migration. Record rollback steps and how to preserve transactions created after cutover.
4. Establish a controlled cutover window so staff do not encode the same branch's operations in both old central and new local systems. Central operational writes remain disabled for migrated branches unless a later coordinated workflow is designed.
5. Release the pilot only after staging tests pass. Production rollout is a separate review gate, not authorization implied by this draft.
6. After pilot sign-off, budget 2 working days per additional ready branch for installation, scoped initial data, reconciliation, offline/reconnect checks and staff orientation. Monitor all deployed branches for 5 working days after the last rollout.

## Dependencies and review decisions

- Access to the old deployed code/version and a verified database/file export, plus Hostinger plan details; these cannot be established from local source alone.
- A suitable local server, LAN, backup destination and power arrangement at each branch. Final specifications follow measured workload; no purchase is assumed approved.
- A staging environment and a named branch representative for acceptance testing.
- User review of the proposed central read-only operational policy, disconnected-login limit, stale-catalog behavior and recovery targets.

SMART mapping: Specific scope and ownership above; Measurable test gates and reconciliation targets; Achievable staged implementation with an early feasibility gate; Relevant continuity of branch work and trustworthy consolidated reporting; Time-bound 40-working-day pilot plus explicitly bounded rollout per ready branch.

## Implementation checkpoint — 21 October 2026

An opt-in implementation now exists for local assets, deployment modes, bootstrap, UUID translation, encrypted persistent snapshots, secure API/file transfer, versioned acknowledgment/retry, financial reconciliation, branch permissions, freshness indicators and public/private backup coverage. See [the deployment runbook](offline-branch-deployment.md) for the actual implementation and capacity limits.

See [the validation report](offline-branch-validation.md) for passing checks, unresolved regressions and remaining pilot/release gates.

The first protocol uses complete branch snapshots, not per-record incremental event batching. The local tests and simulated outage checks do not complete the SMART pilot, hosting audit, actual migration rehearsal, recovery timing target or five-day observation requirement. The old Hostinger deployment remains unchanged. Do not mark any milestone accepted based on source implementation alone.


## Final validation follow-up - 21 October 2026

Controlled prepared/frozen/active activation separates credential preparation from ownership cutover. Frozen bootstrap confirmation plus operator reconciliation/LAN/backup review precede activation; pre-activation cancellation preserves the peer/checkpoint. Active rollback requires reconciliation. Transport compression and per-snapshot identity caching address measured larger-history transfer/SQL costs. Snapshot/file limits and financial validation remain unchanged. Branch backups use operational/sync locks and an hourly schedule.

See [final review](offline-branch-review.md) and [eight-scenario acceptance checklist](offline-branch-acceptance.md). Local MariaDB tests and an isolated encrypted backup restoration are additional evidence; they do not complete Hostinger migration, actual LAN/outage testing or the five-consecutive-working-day pilot. No previously unverified milestone is accepted by this update.
