# System functional and UI/UX acceptance review

October 25, 2026, Asia/Manila. **Result: the exercised core workflows function; three confirmed UI/UX issues need targeted correction approval. No application correction or redesign has been implemented.** This is a local acceptance review, not complete staging/production certification.

## Scope, method and evidence

Reviewed current working-tree routes, controllers, authorization, pricing, financial aggregation, role navigation, Blade/CSS and status behavior. Used the existing Staff UI reference and UI/UX review skills, preserving the Botanical Operations Console theme and layouts.

The new review tooling creates a fresh SQLite file and separate storage/cache/session/view/log directories per invocation, generates a disposable QA key, seeds synthetic records, and starts temporary loopback PHP/ChromeDriver processes. Explicit isolation checks prevent use of an existing database. No development database, .env, existing APP_KEY, operating-system task/service, scheduler, production installation or Hostinger resource was modified. No email was sent: the review runtime uses the log mail driver. Runner-owned browser/server processes are stopped afterward.

| Evidence | Actual execution / interpretation |
|---|---|
| `storage/framework/functional-ux-review-20261025-105447/results.json` | 26 role/page combinations, 104 states (1440px and 390px; light and dark), completed 17:56:19 +08:00. Zero document-level horizontal-overflow flags; zero unnamed visible buttons under the script's limited accessible-name heuristic. These are checks, not a comprehensive accessibility audit. Representative screenshots were visually inspected. |
| `storage/framework/functional-ux-review-20261025-110014/results.json` | **20/20 targeted workflow checks passed**, completed 18:01:08 +08:00. Browser payment modal and correction approval, real HTTP intake/payment/admin requests, and persisted database reconciliation. |
| `storage/framework/functional-ux-review-20261025-112122/results.json` | 12 edge observations: 10 passed, two raw flags. One is the confirmed non-main branch heading issue. The other was correct rejection of a reserved `.test` email fixture, not an account-creation defect. Includes computed dark-theme colors, cross-branch denial, catalog-write denial and Owner date-basis reconciliation. |
| `storage/framework/functional-ux-review-20261025-112401/results.json` | Account-only follow-up: example.com fixture also correctly rejected (Null MX / domain accepts no mail); three other checks passed. This did not establish a defect. |
| `storage/framework/functional-ux-review-20261025-112651/results.json` | **4/4 account/filter/dialog follow-up checks passed**, 18:26:57–18:27:09 +08:00, after using a DNS-valid email-domain fixture with mail delivery disabled. Installed validator diagnostics confirmed example.com Null MX and gmail.com accepted. No validation was bypassed. |

Directory name suffixes use the PHP process's initial clock formatting; JSON timestamps above are the application Asia/Manila evidence. Earlier partial runs (`105422`, `105737`, `105838`, `110301`) exposed review-tool path/import/fixture/assertion/session setup problems and were superseded. They must not be presented as application regressions. The primary workflow rerun corrected the tool to recognize redirect-based validation and open collapsed correction details. No full regression suite, build or view-cache operation was repeated because application code did not change.

## 1. Staff workflow

| Area | Actual result |
|---|---|
| Login/navigation | Real sign-in and MFA challenge exercised. Dashboard, Record New Case, Case Records, Case Details, Payment Monitoring and reminders loaded at both widths/themes. |
| Client information | Empty Client Info stays on the first step with validation feedback. Valid split-name, relationship, Philippine mobile and address entries advance to deceased information. The initial failed selection used a nonexistent relationship option in the review tool; selecting the existing Child option passed. |
| Deceased/service/intake persistence | Real intake HTTP submission persisted the representative/deceased, selected package, schedule and initial payment exactly once. This part used a valid synthetic request payload, not manual traversal of every remaining wizard control. |
| Billing arithmetic | Fixture subtotal PHP 250,000, stored SENIOR discount PHP 50,000, net PHP 200,000. Initial paid PHP 5,000, balance PHP 195,000, PARTIAL. Existing pricing/discount resolver sources were inspected; the net is not incorrectly assumed to equal undiscounted catalog price. This is one fixture, not exhaustive pricing coverage. |
| Retry/recovery | Repeating the intake submission identity recovered the existing case rather than creating another case/payment. Existing message explicitly says changes were not reapplied. Case can be found in Case Records and reviewed in Case Details. |
| Case Details | Payment Summary and Transactions remain present; Record Payment shortcut remains absent. No replacement action or layout change proposed. |
| Additional payment | Actual Payment Monitoring modal: select case → amount PHP 500 → cash → review → confirm. Success feedback appeared; persisted paid total became PHP 5,500. |
| Overpayment/error feedback | Oversized payment returned existing redirect-with-errors behavior with “Amount cannot exceed…”; no extra payment/changed paid total. A final HTTP 200 after following the redirect is not evidence of a successful payment. |
| Correction request | Staff request created PENDING correction without changing the posted financial total. Branch Admin approval is detailed below. |
| Assigned branch restriction | Separately provisioned second-branch Staff could not access the main branch operational case (403/404). Staff branch-administration request denied. |
| Status transitions | Source confirms case status is system-managed; case edits cannot overwrite it, and locally owned ACTIVE cases complete when interment time is reached. Initial ACTIVE/PARTIAL and payment/correction state transitions were exercised. The scheduled completion event was not newly executed in this review; real scheduler acceptance remains pending. |

## 2. Branch Admin workflow

- Dashboard and upcoming-schedule implementation, current financial snapshots, this-month collections and branch scope were traced. Dashboard/case/payment/report pages rendered; no new arithmetic or navigation defect was confirmed in the exercised fixture. Exhaustive schedule boundary/click-through coverage was not performed.
- `/admin/payment-corrections` redirects to the existing Payment Monitoring Correction Requests tab. Staff request could be reviewed; actual approval confirmation dialog opened and approved the correction.
- Correction from PHP 500 to PHP 400 retained the original row as VOIDED and produced the replacement. The case paid total became **PHP 5,400**, balance **PHP 194,600**; request became APPROVED. No direct editing/removal of historical payments was introduced.
- Services visibly states read-only branch access. Write request to packages was denied; package creation, Backup & Recovery and Data Retention routes denied. Empty catalog search rendered without a server error. Grid/table tabs/search are present; not every catalog type/action/filter combination was exercised.
- Data remains assigned-branch scoped. The seed contains only one operational main-branch case; cross-branch read denial was directly demonstrated with second-branch Staff. Branch Admin scope logic was source-reviewed; do not claim a populated two-branch Branch Admin comparison from this fixture.

## 3. System Admin workflow

- Dashboard, user list/create, branch list/create, Service Management, Backup & Recovery and Record Retention rendered at desktop/mobile in both themes.
- New branch creation persisted through its actual endpoint. New assigned Staff creation passed with a DNS-valid email-domain fixture. Reserved/no-mail domains were rejected with an understandable email-validation message. User/branch filtered requests returned successfully. This checks response behavior, not every sorting/count combination.
- Backup creation confirmation opens with password input. No backup creation, recovery operation or infrastructure integration was rerun; existing accepted backup review remains authoritative for those mechanisms. The empty history's mobile presentation has a confirmed issue below.
- Legal hold placement and authorized release persisted in the isolated database without altering case/payment totals. Permanent disposal remains disabled in the existing interface/policy safeguard. No historical record was disposed and no retention policy changed.
- Account deactivation, role reassignment, password reset, privileged System Admin creation, all catalog mutations and populated backup-history actions were source-reviewed where relevant but not newly exercised end to end. Do not treat page rendering as proof of those writes.

## 4. Owner workflow and financial consistency

- Owner Overview, Reports & Analytics, Collections, Master Case Records, authorized case details and reports rendered. Analytics Payments, Service Value Trend and Collections tabs switched successfully. Custom date range with selected tab persisted.
- Owner payment-write request was denied; read-only permissions remain intact.
- Controlled date-basis fixture: older verified case with a valid PHP 250 receipt today. **Owner Overview receipt-date collections = PHP 250; Analytics collected for cases recorded today = PHP 0.** Collection Records showed the PHP 250 receipt. This is a valid difference in cohort, not missing money or a calculation regression.
- `OwnerDashboardOverview` uses valid receipts by paid_at/paid_date/created_at for selected-period cash collections; outstanding balances are a current snapshot. `BranchAnalyticsService` groups case-cohort figures by case recording date. Branch comparison collection-rate copy already explains payments for selected cases through now; Collections Breakdown explicitly identifies case recording periods.
- No financial-source rewrite is recommended. A small optional copy clarification on the top “Collected” analytics card could make its case-cohort meaning clearer alongside the receipt-date Overview. This requires copy approval only; preserve formulas and existing layouts.
- Different historical receipt/correction dates, every branch comparison mode, year-length ranges, exports/PDFs and large-history charts were not comprehensively exercised in this review. No new concurrency/synchronization certification is claimed.

## 5. Confirmed issues and proposed targeted corrections

Severity: Medium = can materially confuse a routine task or make a control difficult to read; Low = localized usability issue with a workaround. No new critical/high-severity financial or authorization defect was confirmed in the exercised paths.

| ID / severity | Reproduction and evidence | Impact | Proposed correction — NOT IMPLEMENTED |
|---|---|---|---|
| UX-01 — Medium | Non-main Staff assigned to synthetic branch 4 (Acceptance Second Branch), `/intake/main`: hidden branch ID correctly 4, heading still **Case Intake - Main Branch**. Screenshot `112122/screenshots/edge-second-branch-intake.png`. Sources: `resources/views/staff/intake/create.blade.php:3`, `_form.blade.php:2530`, branch toast at 5181. | Staff in another branch receives misleading location wording despite correct branch isolation. The toast repeats the same assumption. | Replace heading/page-title/toast wording with existing assigned branch name/code, preserving routes, branch assignment, fields and layout. Do not introduce branch selection or cross-branch encoding. Verify BR001 and a non-main branch. |
| UX-02 — Medium | Payment Monitoring, dark theme, `.pm-page-size` “Rows per page”: computed foreground rgb(24,37,31), visible toolbar background rgb(17,34,24), contrast about **1.05:1**. Evidence `112122/results.json`; screenshot `105447/screenshots/staff-payments-history-dark-390.png`. Global `resources/css/app.css:269` sets label color; page-size styling in `resources/views/staff/payments/history.blade.php:260` does not provide the needed effective dark foreground. | Label is nearly unreadable; dropdown value itself remains readable and usable. | Apply the existing dark-theme readable text token to this label, scoped to Payment Monitoring; inspect equivalent Branch/System Admin consumers. Preserve control behavior, ordering and layout. Verify both themes and widths. |
| UX-03 — Low | System Admin Backup History with no rows at 390px: table minimum width 760px, empty message centered across colspan=6. Message is largely off-screen until horizontal scroll. Screenshot `105447/screenshots/system_admin-admin-backup-recovery-light-390.png`; `resources/views/admin/backup-recovery/index.blade.php:37,65`. | System Admin cannot immediately read “No backups yet” and its next-step instruction. Actual history-table scrolling is intentional. | Make only the empty state readable within the visible panel width (for example outside the wide table when empty). Preserve populated table columns/scrolling, permissions and backup behavior. No backup UI extension/infrastructure work. |

Optional clarity proposal C-01 (Low): distinguish “Collected for cases recorded in this period” from receipt-date collections at the relevant existing analytics card/help text. Existing detailed cohort labels are correct. No financial correction is justified by the observed difference.

## 6. Decisions and preserved areas

No new business decision blocks the three targeted UX corrections. Obtain approval before applying them. Returning-client selection/case-specific relationship remains deferred; do not merge records. Existing retention copy still references formal policy approval before disposal; this review neither resolves nor changes that policy decision.

Keep unchanged:

- Payment Monitoring as the recording destination; Case Details as financial review with Payment Summary/Transactions.
- Correction approval/audit preservation, overpayment validation, replay protection and historical originals.
- Strict assigned-branch operations, legitimate central System Admin reporting, Owner read-only access and Branch Admin read-only catalogs. No Cross-Branch Encoding.
- Catalog/casket availability as status rather than inventory accounting.
- Botanical theme, existing page sections/layouts, role-specific navigation and intended in-container horizontal table scrolling.
- Automatic backup logic, hourly branch cadence, 14-day retention, System Admin-only management, keys/checkpoints and operational snapshot synchronization.
- Infrastructure acceptance pending designated equipment; no physical LAN/WAN/restart/unattended/off-device/restore claim.

## 7. Change inventory and next approval

New review tooling only:

1. `scripts/functional-ux-review.php` — isolated fresh runtime/browser runner. Default captures the 26-page matrix; `--flows` exercises core transactions; `--edges` exercises edge cases; `--account-only` narrows the DNS-valid account follow-up.
2. `scripts/functional-ux-review-flows.php` — synthetic workflow assertions, included only by the runner.
3. `scripts/functional-ux-review-edges.php` — synthetic branch, contrast, Owner cohort and management checks, included only by the runner.
4. This report. Generated evidence/screenshots/SQLite fixtures are under the separate `storage/framework/functional-ux-review-*` directories. They contain synthetic credential/session data and are local QA artifacts, not release/deployment files; do not distribute them or deploy their seeded accounts.

**No existing application source, UI, configuration or database was changed.** Unrelated working-tree changes were preserved. Review tooling is not a full regression suite; some checks record observations rather than enforcing pass/fail for usability (for example computed color capture). Use the findings table and raw evidence together.

Recommended approval scope: UX-01 assigned-branch wording, UX-02 dark label readability, UX-03 mobile empty-state visibility; optionally C-01 cohort copy. After approval, implement narrowly and verify only affected workflows/themes/widths plus required targeted tests/build checks. Do not equate the present partial workflow coverage with complete system acceptance or staging readiness.
