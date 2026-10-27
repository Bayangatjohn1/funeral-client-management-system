# Offline branch validation report

**Latest regression follow-up:** see [the per-failure correction report](system-regression-fixes-report.md) for the subsequent resolution of the 20 failures and final evidence. Earlier counts below remain historical.

**Follow-up:** this document preserves the initial checkpoint below. The four named regressions have now been corrected; MariaDB/capacity/recovery validation has been added. Use [the final review](offline-branch-review.md) for current results and readiness. The full-suite follow-up also exposed additional failures beyond the original selection; the historical passing selection is not a full-project green claim.

Date: 21 October 2026 (Asia/Manila).
Release status: implemented locally and verified with the checks below; staging compatibility review and real branch pilot are still required. The old Hostinger deployment was not modified.

## Passing checks

| Check | Observed result |
| --- | --- |
| Core HTTP/model regression selection | **85 tests, 587 assertions, no failures/errors**. Includes 32 new synchronization/deployment-mode tests, catalog consistency, backup/recovery workflows, structured intake pricing and account security. |
| Branch browser smoke | **1 test, 79 assertions, passed** using a separate SQLite Dusk database. Actual password + authenticator sign-in, staff dashboard, Branch Admin dashboard/read-only service view, local Flatpickr, no external-origin resources, light/dark themes at exact 1440px/390px viewports. |
| Central browser smoke | **1 test, 58 assertions, passed** using a different SQLite Dusk database. Owner/System Admin freshness indicators, local Chart.js on analytics, light/dark themes at 1440px/390px. |
| Status accessibility/layout | Browser checks measured at least 4.5:1 explanatory-text contrast and verified mobile navigation does not cover status text. Dashboard/banner widths stayed within the tested viewport. Screenshots were also visually inspected. |
| Production asset build | `npm.cmd run build` passed. Fonts, licenses, Flatpickr and Chart.js were generated in `public/vendor/offline`; compiled Vite assets were generated in `public/build`. |
| PHP/Blade | Added PHP files passed syntax checks; the latest snapshot/middleware also passed rechecks. Blade view compilation passed and its generated cache was cleared. |
| Whitespace | Scoped diff checks for the new sync implementation and its integration files passed. A whole-worktree check also found whitespace in unrelated, already-modified dashboard/case/test files; those edits were preserved. |

The core suite used in-memory SQLite. Browser runs used only `database/offline_sync_dusk.sqlite` and `database/offline_central_dusk.sqlite`, not the application/production database.

## Covered synchronization scenarios

- Applying the same snapshot ten times without duplicate payments.
- Importing **100 linked cases and 500 payments** into an independent database with different primary-key values, then importing again without duplication or broken links. Paid totals and balances matched.
- A simulated connection failure retains the encrypted outbox. Advancing the test clock by 72 hours, then restoring a fake connection, successfully delivers the pending data. This is a simulated outage, not a physical 72-hour branch trial.
- Lost/mismatched acknowledgments; skipped versions; conflicting retries; schema/table tampering; revoked/missing credentials; wrong-branch records/parents; spoofed branch fields on central operational requests.
- Pending local changes survive an earlier snapshot and are captured in the next version. Older acknowledged payload bodies are cleared without losing checkpoint history.
- Negative or unknown-status payments are rejected even if manipulated amounts appear to balance. A failed monetary reconciliation rolls back the entire snapshot and does not advance its checkpoint.
- Files require upload and a matching checksum, are scoped to their branch, and are mapped to controlled destinations. Uploaded file inventory permits resumable transfer.
- Central account provisioning re-encrypts MFA data for the local key. Other branch credentials are excluded; global-admin attribution stubs use unrelated password hashes. Removed accounts lose local access.
- Offline account access window, central-only password/MFA administration, recovery holds and logout behavior. Branch Admin direct catalog write requests remain forbidden.
- Central legal holds survive stale operational snapshots and are pulled back to the local branch. Central replicas do not independently auto-complete branch cases.
- Central-only owner access audit events remain central. Existing audit entries reject changes while allowing equivalent JSON metadata key ordering. Profile deletion cannot orphan provisioned branch identities.
- Operator restaging can rebuild corrected local data only after central confirms the pending version was not accepted. An already accepted checkpoint cannot be silently replaced.
- Backup archives contain public photos as well as private pending sync files.

## Four initial regression failures (subsequently corrected)

A broader run produced 104 passes and 4 failures out of 108 tests at that implementation checkpoint. A focused rerun of the dashboard/contract suites reproduced the same 4 failures out of 33 tests:

| Test | Failing expectation |
| --- | --- |
| `DashboardAccessTest::test_admin_dashboard_content_is_scoped_by_admin_type` | Expected the text `Managing Main Branch and all branch operations`. |
| `DashboardAccessTest::test_main_admin_defaults_to_all_branches_and_keeps_branch_drill_down` | The `selectedBranchId` view-data expectation fails at line 155. |
| `DashboardAccessTest::test_staff_dashboard_renders_for_staff` | Expected the literal text `Staff Dashboard`. |
| `FuneralContractSnapshotTest::test_owner_case_details_show_a_draft_without_issuing_or_changing_case_values` | Expected inline preview markup containing `srcdoc=`. |

The corresponding dashboard controllers/views and owner-preview markup already contained unrelated working-tree changes when this task started. No pre-change test execution baseline was captured, so these failures are **not certified as pre-existing**. Their tests/role content were not rewritten to make this feature appear green. Their later evidence-based resolution is recorded in the final review. The newer 85-test core selection above excludes those two suites; do not describe the full project suite as passing.

## Dependency audit

The online npm audit initially reported 20 advisories, including two critical development-tool advisories. Applying available fixes within the existing version ranges reduced the reported total to **7: 2 moderate and 5 high**, with no critical advisories remaining in that report. The remaining chain involves Tailwind 3 build dependencies (`braces`, `chokidar`, `micromatch`, `fast-glob`, `postcss-selector-parser`, `postcss-nested`, `tailwindcss`). npm proposed a breaking Tailwind 4 upgrade for the remaining fixes. That migration was not forced as part of offline synchronization. Build and browser checks passed after the compatible updates. Review/disposition the remaining advisories before release; this is not a clean security audit claim.

## UI verification matrix

| Role/page | Modes/states verified | Scope preserved |
| --- | --- | --- |
| Staff dashboard and intake | Branch mode; light/dark; desktop/mobile; status expanded; external HTTPS origins blocked; authenticator login; local date picker initialized | Existing navigation, dashboard data and intake form retained. |
| Branch Admin dashboard/services | Branch mode; light/dark; desktop/mobile; status expanded; read-only service page | Catalog modification permissions not granted; direct write requests separately rejected. |
| Owner dashboard/analytics | Central mode; light/dark; desktop/mobile; stale/unconfigured branches; local chart loader | Existing reporting and read-only scope retained. |
| System Admin dashboard | Central mode; light/dark; desktop/mobile; freshness/status expanded | Existing metrics/actions retained. Legal holds separately verified through HTTP tests. |

The shared panel gained a collapsible status block only in explicitly configured central/branch modes. Standalone does not render it. Local font links replaced the corresponding CDN links in active layouts/auth pages. Offline modes explicitly ignore the normal Vite development hot marker, so an active standalone development server cannot become a branch runtime dependency.

## Remaining acceptance work

1. Obtain a verified export/version of the old Hostinger deployment and rehearse upgrade on staging. The public URL alone does not establish schema or hosting compatibility.
2. Test the actual MySQL/MariaDB engine, hosting limits, HTTPS/proxy handling, scheduler and separate central/branch machines. Automated independent databases here were SQLite.
3. Measure actual branch data/file volume against the complete-snapshot protocol's 20 MiB data and 10 MiB/file limits. Per-record incremental batching and automated orphan-file garbage collection are not implemented.
4. Perform a real LAN-only outage/restart/reconnect exercise, actual complete restoration, and operator-led old-checkpoint recovery rehearsal. No two-hour real restore target has been demonstrated yet.
5. Review the final regression/advisory findings and disposition all remaining release blockers, then observe the pilot for at least five consecutive working days before branch sign-off and rollout.

See [the deployment runbook](offline-branch-deployment.md) and [SMART plan](offline-branch-smart-plan.md). Existing application/production environment values were not changed to activate sync, and no production database migration or deployment was executed.
