# Reports and Analytics visual adoption

Approved reference: `ReportsAnalyticsView.tsx` supplied by the user. Copy its visual composition using Blade/CSS and the existing JavaScript. Retain the existing charts, their series meanings, calculations, records, filters, exports and authorization.

Shared composition:
- One header card with current branch scope, an assigned-scope badge only for Branch Admin, title/subtitle and Analytics/Reports switcher.
- Analytics scope toolbar with Today/This Month/This Year buttons and a Custom Range dialog, followed by active chart filters, four compact KPI cards, underline tabs and the chart/breakdown container. Related records remain in their separate card. Reports retains Quarterly and Semi-Annual presets.
- Reports configuration card contains report choices, existing filters and a Preview/Export footer. Summary and report preview retain separate cards.
- Use the reference sage surfaces and borders in light mode, corresponding dark surfaces, and the existing staff font. Keep wide tables horizontally scrollable.
- Branch Admin is limited to its assigned branch. Owner and System Admin retain their existing branch selection and report availability. Do not copy reference sample records, fake zero branches or permissive client-side scope logic.

Implementation: `reports/partials/module-tabs.blade.php`, `workspace-subtabs.blade.php`, `report-scope-controls.blade.php`, `visual-reference.blade.php`, shared `owner/analytics.blade.php` and `reports/index.blade.php`.

Verification on 2026-10-08:
- Branch Admin desktop: Analytics dark, Reports dark/light inspected. Date buttons and final mobile verification pending because the browser redirected to login.
- Frontend build and Blade compilation passed.
- Four existing report/access tests passed (37 assertions), using SQLite in-memory test configuration.
- Owner/System Admin browser comparison remains unverified; shared role scope covered by the relevant existing tests.

Do not mark this adoption fully visually verified until the remaining browser states have been exercised.

Approved refinement on 2026-10-08: KPI cards use 18px padding, 28px values and small right-aligned icons; the balance KPI is labelled Outstanding Collectibles. KPI cards remain visible across analytics tabs. Charts retain their existing types, series and calculations, with teal/blue distinguishing service amounts and case counts, green for collected and amber for outstanding. Hover tooltips keep the existing exact-value callbacks with readable rounded surfaces. Removed the obsolete hidden filter header and its custom-date handler, and consolidated KPI rules in the shared visual partial.

Latest checks: frontend build and Blade compilation passed; ReportsModuleTest passed all 38 tests (242 assertions). Final live dialog, hover/tap and mobile verification is still pending login; the current browser is at `/login`.

Floating hover tooltip: `resources/js/chart-hover-tooltip.js` supplies one shared fixed HTML card, imported by `app.js` and connected through Chart.js external tooltip callbacks. Existing chart callbacks still supply labels and formatted values. The card has a title, actual scope/period subtitle, metric color markers and label/value rows; text uses `textContent`. It does not intercept clicks. Placement is clamped to viewport edges and flips below the hovered position when there is no space above. It hides on scroll, resize, Escape, navigation reset, tab switch and expanded-chart close. Content is rebuilt only when its values change. Branch comparison, period bars, payment doughnut, collections and revenue trend all inherit the shared renderer. Build/Blade and three existing analytics tests passed (33 assertions); actual hover/tap and expanded-chart visual checks remain pending authenticated browser access.

Reference payment layout: existing Chart.js doughnut now sits beside three semantic status buttons with actual counts, percentages, existing amount/balance notes, selected state and existing filter actions. Added a status-distribution track and existing collection-rate label without changing calculations. The reference expand action uses the existing chart modal. KPI footers align note/action; full peso values remain. Analytics tabs use numbered descriptive labels and active underline. Assigned branch date controls display scope as a badge; permitted branch selectors and Custom Range remain. ReportsModuleTest passed 38 tests/242 assertions; Blade compilation passed. Live visual verification remains pending login.

KPI/tab/table correction against TSX and screenshots: replaced legacy summary-card markup with dedicated ra-kpi components, actual label/icon flex row, 20px card padding, 30px values (24px mobile), 10px uppercase labels, 11px divided footer, 16px gaps and exact currency. Removed obsolete KPI overrides from the shared visual partial. Dedicated analytics-reference-components partial follows visual-reference and owns 12px tabs with 2px active border, and compact tables (10px uppercase headers, 12px rows, 10px/14px cell padding, rounded scroll wrapper). Existing table columns, metric values and actions retained. ReportsModuleTest passed 38 tests/242 assertions; Blade compiled. Screenshot matching in authenticated browser still pending.
