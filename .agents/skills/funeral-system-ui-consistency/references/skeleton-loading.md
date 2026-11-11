# Shared skeleton loading — 2026-11-01

User request: apply skeleton loading across the system's existing pages. This authorizes a shared loading presentation, not new data fetches, business behavior, or artificial delays.

## Implementation owners

- `resources/js/skeleton-lifecycle.js`: delayed, reference-counted leases; timeout and cancellation recovery.
- `resources/js/panel-skeleton.js`: `window.PanelSkeleton.begin(target, {variant:'form'})` returns an idempotent cleanup function. Automatic adapters observe existing loading classes/aria-busy. `reset()` clears all placeholders for Back/Forward, page exit, and print.
- `resources/css/panel-skeleton.css`: Botanical light/dark muted bars, table/card layouts, responsive sizing, reduced motion and print exclusion.
- `resources/js/app.js`: imports the shared module through the existing Vite entry.
- `resources/views/layouts/panel.blade.php`: existing authenticated navigation handler starts skeletons for real same-origin navigation/GET submission. The old forced 140ms navigation delay was removed. Normal errors, redirects, client prevention, and validation remain owned by their existing flows.

## Current behavior

- AJAX regions reveal only after 160ms of actual pending work, so fast requests do not flash placeholders. Navigation uses immediate:true with variant:navigation, paints synchronously above sticky content, and crosses a compositor paint boundary before location assignment. A 100ms fallback dispatches if animation frames are suspended; normal navigation does not wait for that timer. Do not restore the former fixed 140ms transition or require a minimum skeleton duration.
- Filter/result placeholders now project measured text-line, avatar, badge, icon, card and chart geometry from the visible source component. Re-measure on resize/scroll and theme changes. Ignore hidden Grid/Table alternatives and decorative text glyphs. Do not copy record names, amounts or HTML into skeleton markup.
- Navigation uses destination-family templates with separate header, KPI, toolbar, table-row, catalog/directory item, form/detail and chart components. Recognized paths and role/view preferences select the template independently of the outgoing page. Unknown pages use an explicit detail fallback. Templates approximate incoming structure; they are not a guarantee of pixel-perfect geometry for unseen dynamic server content. Existing usable navigation remains outside the pending region.
- Preserve real content rather than replacing it with placeholder HTML. While visible, the overlay marks its region busy and makes covered child elements inert. Cleanup restores original inert/busy/position state. Error content is not hidden after the request finishes.
- Multiple requests to one region share a lease. An older completion/cancellation cannot remove the newest loader. If modal content is replaced during another pending request, reattach the surface without losing leases. Remove overlays for disconnected/replaced roots.
- A 30-second safety timeout restores usable content for stuck/cancelled navigation. It does not abort the underlying request or claim success; existing error/retry flows still own the outcome.
- Shimmer is 1.8s; reduced-motion preference uses static bars. Status text is accessible and decorative bars are aria-hidden. Do not remove existing close/escape behavior or turn a nonmodal panel into a blocking modal.
- No fetch/axios monkey-patching and no prefetch of authenticated GET routes. Keep flash/session semantics and form-discard/confirmation guards intact.

## Coverage and integration

| Consumer | Hook | Coverage state |
|---|---|---|
| Staff, Branch Admin, System Admin, Owner panel pages (dashboard, lists, detail, forms, catalogs, users, branches, reports, recovery/retention, profile) | Shared panel navigation and unmapped normal GET forms; mapped region forms have their own async owner | Installed through the shared layout; not every route was individually visually exercised. |
| Case Records and Master Case Records | `.is-updating`, `.case-records-tab-loading`, card aria-busy → table scroller | Automatic region loading for actual AJAX search/filter/tab/pagination refresh. |
| Service Management | Existing hub aria-busy → active catalog panel | Existing grid/table behavior retained. |
| Catalog detail/Add/Edit iframe | Existing iframe aria-busy → existing service-modal loading region | Parent dialog header/close controls remain outside the skeleton region. Embedded forms do not get an extra global loader. |
| Payments | `.pm-page.is-loading` → visible table/panel | Existing write/save button feedback is preserved. |
| Reports/analytics | Existing preview is-updating/aria-busy; analytics `ba-soft-loading`; report module loader delegates to shared API | Existing report request/result/error behavior retained; report drilldown pending state has aria-busy. |
| Owner summary dialog | Existing results aria-busy | Background comparison enhancement leaves already usable chart content visible rather than blanking it. |
| Client/Deceased detail/edit, Staff case detail/edit, legacy Master case modal, branch Edit, audit snapshot | Explicit begin/finally cleanup in their existing async loaders | Error/cancellation/early-return paths release leases. |

Do not add a skeleton for already-rendered initial SSR content. Login/auth pages and printable outputs are excluded. Same-page anchors, external links, new-tab/download actions and recognized print/export/download routes retain normal behavior. POST saves keep their existing feedback rather than receiving a blanket page mask. This preserves workflow feedback rather than promising a skeleton on every click or network request.

## Verification

- Six Node lifecycle tests cover immediate navigation reveal, no-flash fast completion, concurrency/idempotence, cancellation, stuck-operation timeout, and Back/Forward-style reset.
- Isolated headless Dusk fixture (no application login/live data) passed 1 test / 10 assertions: table placeholder geometry, one overlay, inert restoration, busy cleanup, concurrent modal replacement, hidden table alternatives, and 390px overflow. Light desktop and dark narrow screenshots were inspected. ChromeDriver was updated from 154 to the installed browser's 156 major using the official Dusk installer.
- Existing reports/toolbar/print regression checks passed (49 tests). A separate SQLite smoke test renders role-specific routes and validates their emitted inline JavaScript with Node; 16 Staff/System Admin/Branch Admin/Owner routes passed, 1 test / 145 assertions, in `tests/Feature/SkeletonShellIntegrationTest.php`.
- Asset build, Blade compile/cache clear and diff checks are required for changes to this integration. A fixture screenshot verifies the shared component, not every production page's final composition. Keep full all-role/per-page visual acceptance marked pending until actually checked.

## Reuse and maintenance

For a new asynchronous region, prefer an existing honest aria-busy/loading state if the shared adapter recognizes it. Otherwise call begin for that region and invoke its returned release in finally, including errors and early returns. Preserve the operation's own abort and stale-response checks. Do not cover the whole page for a small request or interfere with write submissions.

When the user changes delay, shape, colors, scope, animation or recovery behavior, update this reference and the current ledger in the same task. Keep measured implementation and evidence current; do not promote an untested consumer to verified merely because it shares the layout.

## Sidebar navigation correction

The first rollout used the AJAX 160ms threshold for navigation too; fast full-document navigation could leave before a placeholder painted. The corrected handler requests an immediate navigation variant, waits across requestAnimationFrame paint scheduling, and has an idempotent 100ms fallback for suspended frames. The navigation variant uses z-index 100 inside the content region so the old sticky toolbar cannot cover it; sidebar navigation remains outside that region. A new standalone browser test clicks the real handler extracted from the Blade layout and replaces only its final location assignment to verify placeholder presence before dispatch. Browser suite now passes 2 tests / 12 assertions. This verifies handler behavior in the isolated fixture, not every live sidebar route under real hosting latency.

## Component-level revision

Owners: `resources/js/skeleton-profiles.js` resolves destination paths and role/view preferences without prefetching; `resources/js/skeleton-components.js` renders destination components and measured source projections. `resources/js/panel-skeleton.js` owns overlay lifecycle and passes the actual destination from `resources/views/layouts/panel.blade.php`.

Measured items use DOM/Range rectangles for natural varying text widths, real avatar circles, actual badge radii and item bounds. Grid/list content is covered by individual item masks inside a transparent projection surface; original DOM is retained/inert for cancellation recovery. Known card/list selectors include service catalogs, user/branch directory cards, dashboard metrics and payment list rows; add an explicit data-skeleton-item hook for a new repeatable component rather than guessing all card-shaped divs. Unknown/unloaded detail/forms use structured fallback templates. No record text is copied into placeholders.

Navigation profiles include dashboard header/KPIs/sections, case tables (8 columns, Owner 9), user/branch directories with persisted card/table preferences, catalog card/table preference, analytics, forms and details. Each shape is decorative, with no new actions or permissions. Source measurements and incoming templates have different evidence limits: source projections can match rendered boxes; templates for a destination that has not arrived cannot claim exact per-record geometry. Keep that distinction in reports.

Verification: 10 Node lifecycle/profile tests passed; isolated browser suite passed 2 tests / 20 assertions, including real handler dispatch, circle/badge geometry, no copied fixture text, destination Catalog despite outgoing table, dashboard KPI/chart component counts, hidden-table handling, resize, and cleanup. Light-desktop and dark-390px screenshots were inspected. Full live-page/all-role visual acceptance remains pending. The former generic six-card fallback is superseded for recognized destination and measured item components.
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

## Sidebar visibility cascade correction — 2026-11-02

User reported sidebar navigation loading without a visible skeleton. The actual root cause was the page decoration selector in `resources/css/app.css`: `.page-content > :not(script):not(style)` also matched the navigation overlay, forcing computed `position:relative` and `z-index:1`. DOM presence did not imply visible coverage. The selector now excludes `.panel-skeleton`; `resources/css/panel-skeleton.css` explicitly scopes the absolute navigation overlay and z-index 100 to its direct-child location. Preserve the existing timing, guards and navigation boundaries.

Verification must include the effective application cascade. `tests/Browser/PanelSkeletonTest.php` now reads the real built application stylesheet from the Vite manifest and checks absolute position, z-index, main-pane alignment and hit testing before navigation dispatch. The expanded test failed before the fix (expected absolute, actual relative). After rebuild, 4 isolated browser tests / 40 assertions and 11 Node tests passed; full-CSS screenshot inspected. Build and Blade cache clearing passed. Native Edge inspection timed out; live all-role acceptance remains pending. Do not describe DOM-presence tests alone as proof that a user can see a skeleton. See `docs/sidebar-skeleton-visibility-fix-2026-11-02.md`.

## Internal tab loading — current behavior (2026-11-02)

Latest user request: review every existing internal tab because changing tabs must not reload the document. This supersedes the earlier regional-skeleton-plus-normal-navigation fallback. A panel-only skeleton does not by itself make navigation asynchronous.

`resources/views/layouts/panel.blade.php` excludes recognized internal tab links before scheduling any location assignment. The global window-bubble handler only owns genuine page navigation, including the sidebar. Existing Service, Staff case, Reports/Analytics and Schedules async handlers keep ownership. Dashboard tab buttons remain local switches.

Previously native Owner case, Deceased quick-filter and Payment Records/Correction Requests tabs now have explicit async owners under this user authorization. Admin/Owner share `resources/views/partials/master_case_tab_navigation.blade.php`; Deceased uses `resources/views/partials/deceased_tab_navigation.blade.php` with delegated detail links after list replacement; Payment section links use the existing fetch/reinitialize pipeline in `resources/views/staff/payments/history.blade.php`. Payment Back/Forward uses that same loader. Keep actual server queries, branch scope, permissions, CSRF approval forms, counts and modal behavior intact.

Case/Payment/Schedules async failures retain current content with an in-place error instead of forcing a hard reload. The obsolete Staff case popstate reload was removed from `resources/js/app.js`; the existing page-specific history handler owns restoration. `resources/js/panel-skeleton.js` recognizes the Payment correction results region so its tab headers remain outside the loading mask. Do not reintroduce a global tab fallback that calls location.href merely because it masks a smaller region.

Verification: the broad focused suite passed 56 tests / 544 assertions, plus 11 Node tests. Final Payment history-loader and cross-role render checks passed 3 tests / 175 assertions, including repeated switches, Back/Forward, network failure, unchanged document heading identity and valid emitted scripts. The actual case/deceased handlers were tested for successful panel updates, retained toolbar/tab usability and in-place error recovery without losing document identity. Build and Blade compilation/cache clearing passed. Full live-session visual review for every role is still pending. See `docs/tab-skeleton-scope-fix-2026-11-02.md` for the complete source audit.
## Directory/Audit/Payment filters — current scope (2026-11-02)

User intent remains result-only skeletons for internal tabs and filters, with the page header, toolbar, tabs and summary cards visible. Branch Management, User Management and standalone Audit Logs now use `resources/js/record-results-navigation.js` for their existing GET forms, pagination and reset controls. Scope is explicit: `[data-record-results-page]` roots and `[data-record-results]` card/table/list regions in their existing Blade views. Preserve native field names/options, query values, server permissions, same-origin constraints and POST submissions. This corrects loading/navigation for existing controls, not a new filter or redesign.

The module preserves form/header/summary/wrapper identities and Alpine Card/Table state, masks only a visible results variant, aborts stale requests and keeps errors in place. User dialog markup is refreshed separately and rebound idempotently; Branch actions are delegated; Audit details are initialized after result updates. Server-derived reset/export URLs and active Branch status indicators stay current. New results must not lose modal actions or copy client-only loading/bound-listener state into server response fixtures.

Payment resolves visible `.pm-records-body` or correction results BEFORE considering ancestor panels. A comma-separated selector list returns DOM order, not selector preference; never rely on it to prioritize the inner body over `.pm-panel`. The history view no longer dims/disables the entire panel or KPI group while records load. Footer, tabs, filters and summary stay outside the mask. Existing local view toggles remain local and show no artificial skeleton.

Verification: final focused suite passed 11 tests / 302 assertions; strengthened global-handler/filter and refreshed-dialog browser checks passed 2 tests / 27 assertions; 11 Node tests and Vite/Blade checks passed. A broader authorization suite had a separately reproducible Service Catalog addOns category-filter expectation failure at AdminScopeAuthorizationTest line 325; do not report that broader suite as passed. Full live all-role visual acceptance remains pending. See `docs/directory-audit-payment-filter-scope-2026-11-02.md` for exact evidence and limits.

## Directory empty-state/skeleton alignment — current (2026-11-02)

For Branch/User Management Grid and Table, reuse `resources/views/partials/directory_empty_states.blade.php`. Empty Grid panels and table data areas reserve the same responsive footprint: clamp(20rem,40vh,30rem), 18rem at 767px and below. Empty tables retain real headers/columns and center their message within the visible horizontal scroller. Footers render for zero/single/multi-page results using the existing shared paginator; empty controls stay disabled. Preserve the actual Add Branch/Add User action and permissions.

Mark a complete empty Grid component with data-skeleton-empty. `resources/js/skeleton-components.js` masks that entire component and measures icon, text and action geometry; do not mask just h3 and leave the paragraph/Add button visible. The outer results overlay remains transparent, with a complete local mask matching the empty panel's background/border/radius. Use existing theme tokens, including an explicit ink color for empty titles so legacy light-text utilities cannot reduce dark-theme readability.

`resources/js/record-results-navigation.js` keeps a per-visible-region loading lease while a request is pending. Resize plus relevant style/hidden observation follows Grid/Table switches, releases hidden-view loaders and cleans up on success/error/cancellation. Preserve usable controls and existing query/permission behavior. A one-time list of visible regions at submit time is insufficient for view switches during pending work.

Verification: 2 focused tests / 42 assertions, then 11 regression browser/render tests / 318 assertions passed; all 11 Node tests plus Vite/Blade checks passed. Checks cover both consumers, equal empty Grid/Table footer position, whole-empty masks, real action/icon footprints, zero-result disabled pagination, pending view switches, dark 390px layout and cleanup. Shared dark mobile loading/ready fixture screenshots were inspected. Full live-role visual acceptance remains pending. See `docs/directory-empty-skeleton-alignment-2026-11-02.md`.

## Promotions loading — current (2026-11-03)

Promotion cards use data-skeleton-item and their complete empty state uses data-skeleton-empty in `resources/views/admin/service-management/index.blade.php`. Missing markers previously left details/status/actions visible while only headings were covered. Keep local card background, border and radius in the measured mask, with transparent grid gaps. `resources/js/skeleton-components.js` now projects service-status pills and catalog action icons in table bodies; real table headings remain ready and visible. The shared empty projection recognizes the service empty-state icon.

`resources/js/skeleton-profiles.js` identifies Promotions separately: seven table columns (name, package, discount, start, end, status, actions), or a card template with four label/value detail groups and action footprints. Destination templates remain approximations, not copies of unseen records. Existing1.8s shimmer, delay/cancellation cleanup and responsive behavior remain shared. Do not show a skeleton for a local Grid/Table switch alone.

Verified in `tests/Browser/CatalogActionSkeletonTest.php`: actual populated/empty Branch/System role renders, card surface geometry, table header boundary, cleanup, destination card/table structure, both themes and dark390px screenshots. Final catalog plus Payment browser run passed2 tests/1576 assertions; shared PanelSkeleton regressions also passed. All12 Node tests and build/Blade checks passed. These are isolated fixtures, not all live datasets or every role workflow.

## Navigation transition polish — 2026-11-03

Ordinary refreshes and confirmed sidebar destinations use the same soft 260ms opacity-only rhythm with `ease-in-out`, starting at `.94` opacity. They must not spatially move the page heading or create a large brightness jump. The former custom ease-out curve, 140/180ms fades, 240ms/10px entrance and later 4px settle are superseded because they felt abrupt or made the title, toolbar, cards and tables appear unstable.

The outgoing document's navigation skeleton remains immediate and does not add a fixed navigation delay. Its surface uses a soft 180ms opacity-only settle from `.94`, while the generic destination header placeholder is centered so it does not resemble a real left-corner page title. It must not translate vertically. Reduced-motion users receive neither transition. Preserve the 1.8s shimmer, compositor paint boundary, 100ms suspended-frame fallback, main-pane-only mask, and usable sidebar/topbar.

Verification for this correction: Vite production build passed; all 12 Node skeleton lifecycle/profile tests passed; PHP source syntax and scoped diff checks passed. The PHP/Dusk runner could not start because this checkout currently has no `vendor/autoload.php`; do not claim browser-suite execution for this revision until dependencies are restored. Live all-role visual acceptance remains pending.

The apparent left-corner “old title” was not a second title node. `partials.page-title-surface` owned the final centered title geometry but was included at the end of `<body>`, after the header markup. A browser could therefore paint the legacy left-aligned base header before parsing the override. Load this style partial in `<head>` immediately after the Vite assets and include it exactly once. Keep topbar and inline page-header rendering mutually exclusive; do not hide a legitimate document heading merely to mask a first-paint ordering bug.

All page-reveal and navigation-settle animations must be disabled under `prefers-reduced-motion: reduce`. Internal asynchronous tabs and filters do not use the full-page reveal; their existing result-only skeleton lifecycle remains authoritative.

## Skeleton-to-content handoff — 2026-11-03

Do not instantly remove a visible regional skeleton when its request completes. Restore the underlying content's interactivity/`aria-busy` state immediately, then keep the overlay for a 220ms opacity-only exit so the ready content is revealed beneath it. The exit must not translate, resize, or delay data availability. A new request for the same target cancels/removes the old exit before painting its own overlay; global reset and reduced-motion cleanup remain immediate.

For same-origin full-document navigation, use the CSS cross-document View Transition opt-in and assign `view-transition-name: panel-content` only to `.page-content`. Fade the outgoing content/skeleton for 180ms and the incoming content for 260ms from `.94` opacity, both with `ease-in-out`. Do not transition the sidebar/topbar/root shell. Browsers without cross-document View Transition support fall back to the existing page reveal. This coordinates skeleton disappearance and content appearance without a fixed loading wait or positional motion.

Verification: Vite production build and all12 Node skeleton lifecycle/profile tests passed; PHP test-source syntax and scoped diff checks passed. Browser/Dusk execution remains unavailable because `vendor/autoload.php` is absent, so live cross-browser View Transition and all-role frame-by-frame acceptance are pending.

## Legacy navigation-motion cleanup — 2026-11-04

There is one full-page transition owner: the named `.page-content` View Transition, with `panelPageReveal` only as the unsupported-browser/direct-refresh fallback. Remove the obsolete `html.page-nav-transition` opacity/translate rules and their cleanup calls. Do not keep a second `panelPageEnter` keyframe. In View-Transition-capable browsers, suppress `panelPageReveal` on confirmed sidebar arrivals so the incoming pane is not faded twice.

Case Records asynchronous tabs must rely on their result skeleton handoff. The old `case-records-tab-loading` opacity/4px translate and `case-records-tab-enter` opacity/5px translate are removed; the loading class may only prevent interaction with the replacing result section. Other internal tab/filter handlers remain result-region owners and must not invoke the full-page transition.

Expose `window.SabanganNavigate(href)` from the shared panel layout for programmatic same-origin page navigation. Clickable rows, Case filter/reset hard-fallbacks, Service modal completion, Owner report navigation and Staff intake cancel use it, falling back to `location.href` only when the shared navigator cannot own the URL. Download/export navigation remains excluded. This ensures programmatic page changes use the same skeleton and View Transition as normal anchors.

Audit evidence: source and compiled production assets contain no `page-nav-transition`, `panelPageEnter`, or `case-records-tab-enter`. Vite build, all12 Node skeleton tests, PHP syntax for touched Blade/test sources and scoped diff checks passed. Dusk/live frame capture remains pending because `vendor/autoload.php` is absent.

## Destination-first navigation skeleton — 2026-11-04

On a genuine full-page navigation, render the skeleton for the URL being opened, never a measured projection of the outgoing page. The navigation entry stores the destination URL before paint, resolves `skeletonProfile(destination, role/view preferences)`, and marks the overlay with `is-destination`, `data-skeleton-destination`, and `data-skeleton-profile`. Source-measured skeletons remain only for asynchronous refreshes of existing result regions.

Current destination silhouettes: Payment Monitoring uses four KPI cards plus toolbar/grouped list; Branch Management uses two KPIs plus persisted card/table directory view; User Management uses three KPIs plus persisted card/table directory view; Audit Logs uses a six-column table; Record Retention uses four metrics plus six columns; Backup & Recovery uses four metrics and a six-column history table without a fake toolbar. Case/Master, Service catalogs, Reports/Analytics, Schedules, dashboards, forms and details retain their existing destination profiles.

These templates are structural placeholders only: no prefetch, record values, hidden authorization data or new behavior. Unknown destinations use the detail fallback. Verification: all12 Node lifecycle/profile tests and Vite production build passed; browser test source asserts destination path/profile metadata. Dusk/live all-role visual acceptance remains pending because `vendor/autoload.php` is absent.

## Stable post-skeleton content — 2026-11-04

Named View Transition groups animate geometry by default, even when the old/new pseudo-elements define only opacity animations. When destination pages have different height, width or scroll geometry, that default group morph makes ready content appear to move after the skeleton. Set `::view-transition-group(panel-content) { animation:none; }`, isolate the image pair, and animate only `::view-transition-old/new(panel-content)` opacity with normal blend mode. The incoming snapshot must already occupy final destination geometry.

Do not reintroduce group duration/transform interpolation. The outgoing skeleton/content fades, then the final-position incoming content fades in; title, toolbar, cards and table never resize or translate. Vite build and all12 Node tests passed; PHP browser-test source syntax and scoped diff passed. Live frame-by-frame cross-role acceptance remains pending.

## Master Case tab handoff adopted for page navigation — 2026-11-04

Latest user reference supersedes the cross-document View Transition guidance above. Full sidebar navigation shows the destination-shaped skeleton in the outgoing document; when the destination document is ready, render its final content immediately, exactly like the Master Case Records result-tab handoff. Remove the `@view-transition` opt-in, named `panel-content` pseudo-elements and old/new content fades. On `html[data-sidebar-nav-entry='true']`, disable `panelPageReveal` unconditionally.

There must be no second content animation after the skeleton: no opacity entrance, geometry morph, translate, resize or scroll interpolation. The destination page arrives at final layout in its first visible frame. Normal direct refresh may keep the subtle `panelPageReveal`; internal async tabs/filters retain their existing regional skeleton cleanup. Verification: compiled production CSS contains no View Transition rules; Vite build and all12 Node tests passed, PHP test-source syntax and scoped diff passed. Live all-role frame capture remains pending.

## Destination silhouette accuracy — 2026-11-04

Navigation templates must follow the destination's real vertical order and role-visible density, not merely its general page family. Current audited sequences are:

- Case/Master Case: header, chip tabs, toolbar, 8-column table (Owner history keeps its 9-column structure).
- Service Management: header, six catalog tabs, section intro/action, toolbar, then the persisted card or table view. Promotions retains its seven-column table/four-detail card shape.
- Payment Monitoring: Branch Admin shows section tabs, four KPIs, toolbar and a five-part case row; System Admin shows four KPIs, toolbar and the same row; Staff has no KPI strip or section tabs. Branch correction requests show tabs and a compact three-part request list with no records toolbar.
- Branch Management: header, two KPIs, toolbar, then the persisted directory card/table view.
- User Management: header, KPI strip, toolbar, then the persisted directory view. Branch Admin has three account KPIs; System Admin has four, including Role Distribution.
- Record Retention: header, four KPIs, policy notice, status toolbar and six-column register.
- Backup & Recovery: header, four KPIs and six-column history table, with no records toolbar.
- Audit Logs: header, toolbar and six-column table. Reports/Analytics keeps its configuration controls before four summary metrics and preview panels.

`resources/js/skeleton-profiles.js` owns these role/path/view distinctions. `resources/js/skeleton-components.js` honors `metricsFirst`, optional notice/section-intro blocks and destination list density. Skeleton values remain decorative and never copy live records. This improves visual continuity without adding loading delay, data fetches, permissions or controls.

Verification for this accuracy pass: the production Vite build passed and 13 focused Node lifecycle/profile tests passed. The audit compared actual Blade source order for Payment, Branch, User, Service Management, Retention, Backup, Audit, Reports and Case families. The Dusk source check exposed and corrected one obsolete assertion that expected no skeleton entrance; the approved 180ms opacity-only entrance remains, while post-skeleton content motion stays removed. Live browser execution remains pending because ChromeDriver was unavailable on port 9515; source/order verification is not a claim of pixel-perfect rendered acceptance.

## Sidebar arrival stability guard — 2026-11-04

User reported that ready content still looked unstable after destination skeleton completion. Source audit found four page-local Alpine Grid/Table entrance transitions in Branch and User Management (`opacity-0 translate-y-2`), which could run while the destination document initialized. They are removed: Grid/Table selection remains functional but changes directly without spatial entrance motion.

For every confirmed sidebar destination, `data-sidebar-nav-entry` now temporarily disables descendant `.page-content` animation/transition while the destination document initializes. The layout marks `data-sidebar-nav-settled=true` only after the window load event and two animation frames. It does not disable transforms, alter layout, delay navigation, mask interactions after readiness, or affect direct refreshes/internal result refreshes. This is a one-time boot guard, not a permanent no-motion mode; normal hover, dropdown and dialog feedback resumes after the stable first paint.

Verification: source assertions cover the guard, its release marker and removal of the four directory entrance transitions. Node lifecycle/profile tests remain required, plus Vite build and Blade compilation/cache clear. Live browser proof remains pending while ChromeDriver is unavailable.

### Nested skeleton handoff correction — 2026-11-04

Browser verification then found a remaining handoff defect: an exiting result-level skeleton could remain inside `.page-content` while a full navigation painted its new destination skeleton. The inner outgoing surface could visually sit above the destination template, or be selected first by code/tests, producing flicker and an unstable-looking ready handoff.

`PanelSkeleton.begin(..., { variant: 'navigation' })` now immediately resets and removes active/exiting descendant skeletons before painting the destination surface. It marks the page content as navigation-owned so the automatic `aria-busy` observer cannot reinsert a regional loader during the short outgoing navigation window. Cleanup removes that marker. This is limited to genuine full-page navigation; result-region loaders retain their normal 220ms exit behavior.

Verified: 13 Node lifecycle/profile tests, production Vite build, and the isolated `PanelSkeletonTest` browser fixture passed 6 tests / 76 assertions. The browser fixture includes the previously failing measured-result → destination-navigation sequence and confirms a single destination skeleton with correct metadata and no stale duplicate overlay.

Staff Outstanding Balances pagination explicitly starts the shared measured skeleton on the table scroller. The 160ms delay, inert body, sticky column labels, footer outside the overlay, and cleanup on cancellation or completion preserve the table layout. The measured table adapter includes row-header cells and plain case-status text. Build, Blade checks, 13 Node tests and 26 dashboard feature tests passed. Browser layout and animation remain unverified.

For the next role rollout, see [Content transition proposal](current-ui-decisions.md#content-transition-proposal-for-the-next-role-rollout-2026-11-06). The Staff Outstanding Balances pilot uses a scoped 150ms exit over ready rows. The scroller stays mounted so its overlay can fade out. The shared 160ms reveal threshold, 220ms default exit and stable sidebar arrival remain in place.

Staff sidebar link exception. Staff sidebar clicks use native navigation and one 200ms content entrance in the destination document. This is scoped to Staff sidebar arrivals. Regional requests, other roles and existing non-sidebar loading flows retain the shared skeleton rules.
