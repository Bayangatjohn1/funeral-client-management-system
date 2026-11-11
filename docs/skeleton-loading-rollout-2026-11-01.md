# Skeleton loading rollout — 2026-11-01

## Current component-level revision

Navigation now resolves the actual destination URL and role/view preferences via `resources/js/skeleton-profiles.js`. Separate components in `resources/js/skeleton-components.js` render page headers, KPI/metric cards, toolbar fields, table headings/rows, catalog/directory cards, payment lists, chart panels and form/detail fields. A catalog destination no longer inherits a table merely because the outgoing page contains one.

For active filter/result refreshes, measured projections follow rendered text ranges, item bounds, avatars, badges and icons. Hidden alternative views are excluded. Visible content is covered while original DOM is kept inert for recovery; record names/values/HTML are not copied into placeholder markup. New repeatable components can opt in with `data-skeleton-item`.

Destination templates describe known page families and source-confirmed structure, not exact unseen dynamic server content. Full-document navigation still reloads the destination; the sidebar remains visually unmasked while pending, rather than becoming a new SPA navigation system.

Current checks: 10 Node lifecycle/profile tests; 2 isolated browser tests / 20 assertions covering measured avatars/badges, no copied text, Catalog-versus-outgoing-table selection, dashboard components, navigation, resize and cleanup. Light desktop and dark 390px screenshots were inspected. Full live visual acceptance of every route remains pending. Earlier generic-template notes below are historical rollout evidence and are superseded where a recognized profile/component exists.

Implemented a shared loading layer in the authenticated panel, using existing navigation/async states. The system does not fake loading on already-rendered initial HTML, block POST saves with a generic mask, prefetch authenticated routes, or change database behavior.

Source owners: `resources/js/panel-skeleton.js`, `resources/js/skeleton-lifecycle.js`, `resources/css/panel-skeleton.css`, `resources/views/layouts/panel.blade.php`, and the existing loader adapters. The living skill's `references/skeleton-loading.md` records exact behavior and scope.

## Applicability

| Pattern/page family | Decision | Implementation |
|---|---|---|
| Authenticated navigation across all four roles | Applicable | Shared layout starts a delayed region loader; no per-page duplication or artificial 140ms redirect delay. |
| Ready server-rendered initial content | Not applicable | No placeholder reveal or fixed wait after content is available. |
| Case records, catalog refreshes, payments, reports, analytics | Applicable | Observe their actual busy/loading state and cover the relevant result region. |
| Existing async detail/edit dialogs | Applicable | Shared begin/finally integration for clients/deceased/cases/branch editing and audit snapshots; existing catalog iframe busy state reused. |
| Readable charts receiving optional background comparison | Already usable | Do not erase the chart just because secondary data is pending. |
| POST validation, saving buttons, confirmation, print/export/download | Existing feedback retained / skeleton not applicable | Preserve write feedback and standard file/print behavior. |
| New network requests or data features | Not authorized | No new backend endpoints or data fetches introduced. |

## Verified behaviors

Shared lifecycle has immediate navigation reveal with a compositor paint boundary and delayed AJAX reveal (160ms), idempotent/ref-counted cleanup, 30s safety recovery, disconnected-root cleanup, Back/Forward/print reset, resize/scroll tracking, light/dark surfaces and reduced-motion fallback. Actual table widths/column counts are reused. Covered content is temporarily inert and restored; new error/result content remains usable.

Six Node lifecycle tests passed, including immediate navigation. Isolated headless browser suite passed with 12 assertions across 2 tests and inspected light-desktop/dark-390px screenshots. It caught and helped correct stale sizing after resize and concurrent modal-content replacement. Initial ChromeDriver mismatch (154 vs browser 156) was resolved using Dusk's official matching driver installer. No live DB was modified by browser tests.

Reports, case-toolbar and print regression tests passed (49 tests). Role-route inline-script smoke testing uses SQLite and checks emitted script syntax, not CSS string snapshots. Initial test-harness issues (temporary extension, HTML4 parser truncation, canonical redirects) were corrected without changing production workflows.

Final role-route smoke result: 16 routes across Staff, System Admin, Branch Admin and Owner; 1 test / 145 assertions passed. Blade compilation/cache clearing and the Vite build passed. The living skill was structurally validated with 21 local links and 78 source paths.

## Remaining verification

The shared component was visually inspected in its isolated fixture. Full live-page screenshot acceptance for every role, route, modal, viewport, loading/error path and assistive technology remains pending; shared-layout coverage is not a claim that each consumer passed individual visual QA. Existing loading operations with no recognized busy state or explicit adapter require a future scoped hook rather than global fetch interception.

Sidebar follow-up: the initial navigation hook could leave the document before the 160ms timer painted. Full-document transitions now request immediate:true with variant:navigation, render above sticky content, and dispatch after a paint boundary. An idempotent 100ms fallback handles suspended animation frames; it is not a required minimum duration. The browser fixture clicks the real Blade handler and substitutes only final location assignment to verify that a skeleton exists before dispatch.

## Navigation and asynchronous tab boundaries

Current user rule: sidebar navigation masks only `.page-content`; sidebar and `header.topbar` stay rendered and usable. Use the destination profile rather than the outgoing layout. Internal asynchronous tab/filter refreshes cover only their results panel; tab headers remain clickable before and during reveal. All animated shapes use the shared 1.8s shimmer direction/speed and honor reduced motion.

`PanelSkeleton.contentTarget(source)` resolves Case Records to the table scroller, Reports/Analytics to the active analytics panel or report preview, and Schedules to the visible calendar/agenda/case/warning list. Removed legacy whole-shell opacity/pointer blocking and the Reports loading pill. Case tabs no longer impose the old 140ms wait. Reports module navigation retains the latest tab choice made while a request is pending; response/error cleanup releases its loader. Removed the artificial module enter wait/fade that hid tab headers after replacement.

Schedules uses its actual existing structure: Today is a day agenda, Upcoming defaults to a week grid, and ongoing/warnings/By Case use list placeholders. Upcoming auto view uses the existing 767px breakpoint to choose a list on narrow screens. Do not introduce a month-calendar view merely to show a skeleton. Destination templates remain structural approximations of incoming server content.

Verification for this revision: 11 Node lifecycle/profile tests passed; 3 isolated browser tests plus 1 cross-role shell rendering test passed (4 tests, 171 assertions). Browser checks clicked sidebar, topbar, Case tabs and Reports tabs during pending overlays; verified covered results are inert and navigation remains outside the mask; checked week-grid template and cleanup. The week-grid fixture screenshot was inspected. Vite build and Blade compile/cache clear passed. This verifies shared boundaries and templates; full live-page visual acceptance across every role remains pending.

## Table header and card gap correction — 2026-11-02

For asynchronous table filtering, sorting, pagination and status-tab changes, keep the existing column headings visible and stable. Project placeholders only from tbody cells, with an opaque body mask; do not render heading skeletons behind sticky titles. Direct child tables keep their header interactive and make only tbody/tfoot inert during result refresh. Full destination navigation retains its separate page-shaped header template and whole-pane mask.

Measured card grids use a transparent outer surface with one opaque mask per actual card. Match each card's bounds, background, border and radius; keep gutters transparent so the existing page background remains visible between cards. Shimmer applies only to content shapes, not card boundaries. Resize, scroll and theme changes recalculate geometry and colors. Do not restore the former continuous card-colored rectangle behind an entire grid. Unknown unmeasurable content still uses the documented fallback.

Owners: resources/js/skeleton-components.js, resources/js/panel-skeleton.js, resources/css/panel-skeleton.css. Applicable to existing measured table/card consumers; tabs, toolbars, query parameters and business behavior are preserved. No new features or permissions are introduced.

Evidence: 11 Node lifecycle/profile tests passed; 4 isolated browser tests / 36 assertions passed, including no heading placeholders, body-only bounds, sticky header hit testing after horizontal/vertical scroll, individual card mask widths, transparent gutters in both themes, dark color matching, 390px layout and cleanup. Inspected light desktop and dark narrow fixture screenshots. Vite build passed. Full live-route/all-role visual acceptance remains pending; fixture checks are not a claim that every page was visually exercised.
