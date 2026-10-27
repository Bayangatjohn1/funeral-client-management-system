# Owner UI review — 2026-10-13

Scope authorized: Owner pages, following Staff UI Reference and the agreed shared title/compact filters/regular table text conventions. Audit first, report confirmed issues before editing. No Owner application changes made during this initial review.

| Page | Source | Reference / status |
| --- | --- | --- |
| Owner Overview | dashboards/owner.blade.php | Staff dashboard surfaces, typography, sections and overlays; source reviewed, browser pending Owner sign-in |
| Reports & Analytics | owner/analytics.blade.php, reports/index.blade.php and shared partials | Shared reports and title surfaces; Owner role interactions and scope controls pending browser verification |
| Master Case Records | owner/cases/history.blade.php | Staff Case Records filter/table patterns; source reviewed, browser pending |
| Case Details | owner/cases/show.blade.php, partials/case_view_content.blade.php | Staff full-case details; source reviewed, browser pending |

Source-confirmed concerns:

- Owner role lacks the staff/admin body font/token foundation in layouts/panel.blade.php; dashboard inherits old body/heading font variables. The dashboard contains many 800/900 font weights and page-local palette overrides. Inspect computed results before consolidating typography/surfaces.
- Owner case history still includes a legacy case-view overlay and its script, but visible rows navigate through data-row-href to full case details. No `.open-case-modal` trigger appears in this view. This is stale source, not evidence that an active overlay is broken.
- Legacy overlay has no dialog semantics, focus entry/trap/return handling; do not redesign or activate it without first establishing whether any external code uses it.
- Case Details uses the Owner topbar header path, while history uses the inline title path. Compare their action placement and background/spacing in-browser.
- Shared case history toolbar already exists: retain its options, query state, pagination and complete columns. Do not replace it with the System Admin audit-specific controls.

Preserve Owner-only cross-branch visibility, dashboard metric definitions and chart data, case read-only permissions, exports and Back return context. Sales-per-branch routes redirect into Reports and are not independent active pages.

Browser verification currently awaits Owner sign-in. Need desktop 1440px, phone 390px, both themes, dropdown/custom-date states, rows/keyboard navigation, details and Back, dashboard drilldowns/overlays, report tabs/filters and accessible focus. Source findings do not constitute rendered completion.

## Signed-in review and implementation

Owner sign-in supplied. Rendered checks confirmed DM Sans body on Overview and conflicting light/dark backgrounds/text. Added an Owner-only body scope and `owner/partials/visual-reference.blade.php` foundation for Plus Jakarta Sans, staff theme tokens, regular row text, consistent surfaces, restrained weights and hover/focus presentation. Case history now uses page scrolling instead of a fixed viewport-height shell; all columns remain available through horizontal table scrolling. Case Details preserves its original content and permissions.

Overview Custom Range now uses a native dialog with a full-viewport scrim, labeled dates, initial focus, Escape/backdrop/close handling, focus return and mobile stacking. Opening/closing does not apply a different period; explicit Apply/Reset retain the original filter requests. Verified applying October 1–13, 2026 updates the URL and retains the same values (400,000 service value, 120,000 collected, 280,000 outstanding, 8 cases).

Verification performed: Overview desktop dark/light and phone light/dark; custom dialog at desktop and 390px, Apply, close, Escape and focus return; branch chart selection updates payment preview. History desktop dark/light, mobile light/dark, More Filters open/close, custom date Cancel, keyboard navigation to FC0008, Back to history. Detail read-only contents inspected at desktop and phone, both themes. Analytics desktop light/dark and phone light/dark, custom date overlay inspected; Reports tab navigates to the authorized Owner Branch Performance report. Phone checks have no document overflow; history table scrolls horizontally in its own wrapper. No live record writes performed. Export downloads and every report/chart drilldown have not been exhaustively exercised.

Build and Blade compilation passed. Broader existing test selection: 46 passed, 2 failed, 282 assertions. The failures are AdminOwnerInputConsistencyTest's admin desktop/mobile date-option-count expectation and admin filter-validation expectation; Owner changes do not change those admin filters/controllers. Owner-focused filter, analytics, dashboard, snapshot/read-only and report tests passed. Cache cleared after validation.

Recommendation requiring a separate decision: Overview Collected shows total collections but its link filters only fully paid cases (current data has 120,000 collected and zero fully paid cases). Destination preserved during this presentation update. Corrected history subtitle from completed-only wording to case records, reflecting the actual all-status list. No metric definition, route or report permissions changed.

## Authorized dashboard content improvements

User approved the content recommendations. Kept the original verified-case cohort and recording-date period basis for the four metrics. Clarified Collected as payments for those cases across all dates. Added validated Owner-only `collection_state` drilldown filters: WITH_PAYMENTS (total_paid > 0), WITH_BALANCE (balance_amount > 0), AFTER_INTERMENT (balance remaining and case interment date before today). History preserves this filter during other filter submissions, displays its context and supports clearing it independently.

Top Packages now includes verified cases across all payment statuses and supports All Branches. Service Value Trend is populated from case recording dates using existing total_amount values, grouped daily for short ranges and monthly above 62 days. Removed the misleading first/last-point percentage comparison. Branch Performance labels now say service value and selected-branch queries restrict its displayed branches. Consolidated the redundant Reports quick action; report access remains through Reports & Analytics.

Needs Attention now identifies cases with balance after interment within the same selected branch/date cohort, with count, balance and a matching Review cases link. No payment due-date/commitment model was established in the source audit, so this is collection follow-up, not an overdue designation. Current live verification: Collected opens 7 cases with payments; attention shows 7 cases / PHP245,000 and its review link opens 7 records; Top Packages shows 8 cases. Selecting Branch 2 gives zero values, one selected branch, empty trend/packages and no attention cases. Both 390px updated dashboard and filtered history have no document overflow.

Validation: focused Owner regression tests pass (6 tests; assertion count in storage/app/owner-content-tests.txt). New fixture covers partial-payment package inclusion, all-branch trend data, attention count/balance, collection destinations, invalid collection-state rejection and selected-branch yearly aggregation. Frontend build and Blade compilation pass. No case/payment records written in the live application.
