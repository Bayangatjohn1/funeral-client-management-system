---
name: staff-ui-reference
description: Adopt the existing staff visual and interaction patterns across Branch Admin, System Admin, and Owner pages in this funeral-system Laravel project while preserving each page's content and functionality. Also use for cross-role UI regressions; compare actual staff sources and verify affected pages in both themes.
---

# Staff UI reference

## Scope and preservation contract

Treat the current staff pages as the visual and interaction reference, not the sample values in screenshots. Default to presentation-only adoption. A request to review or update this skill does not authorize application-page changes.

Preserve each target page's:

- Text, titles, descriptions, live data, metrics, chart type/data, table columns/order/meaning, status meaning, and amount/date formatting.
- Sections and their order, menu items and order, fields, labels, help text, validation rules, required indicators, default values, filters, sort choices, pagination options, and available actions.
- Routes, form methods/actions, field names, query parameters, selected filters, links, authorization conditions, assigned-branch scope, and business logic.
- Existing JavaScript IDs, data attributes, event hooks, Alpine state, submission behavior, success/error flows, and print/export behavior.

Adopt colors, typography, spacing, surfaces, borders, radii, shadows, icon treatment, control sizing, and corresponding hover/focus/disabled/loading appearance. Align existing controls' presentation and accessibility behavior without adding/removing business features. Keep role-specific dashboards and analytics intact. Do not copy staff permissions, metrics, filters, columns, or workflow into another role. Do not hide content or actions to make a page resemble staff.

Markup wrappers and shared components are acceptable only when they preserve content order, semantic structure, bindings, and behavior. Improve accessible names and focus handling without changing visible copy. If an interaction change alters a workflow or exceeds this contract, leave that change outside the adoption unless the user authorizes it. Explicit user scope takes precedence.

## Inspect before editing

1. Read applicable repository instructions and `git status`; preserve unrelated work. Inspect the current working-tree sources, including existing local changes.
2. Discover pages from `routes/web.php`, controllers, role checks, and navigation. Do not equate a view directory with a role: admin views can serve both Branch Admin and System Admin, and shared staff views can serve admin workflows. Inspect `app/Models/User.php` role helpers and `resources/views/partials/sidebar.blade.php` before changing role selectors.
3. For a system-wide request, maintain a compact adoption matrix in a workspace task note. Record role, route/view, staff reference, shared dependencies, preserved content/actions, status, and verification evidence. Include dashboards, lists, create/edit/detail pages, reports, catalogs, users/branches, recovery/retention, profile, and reachable dialogs as applicable. Exclude guest/auth and print/PDF redesign unless requested; preserve their output when shared styles change.
4. Capture a before baseline for each target: visible sections/columns/controls, current values, and relevant states. Use source inspection plus browser screenshots when available. Compare the same viewport, theme, and component state; do not require identical records or totals across roles.
5. Trace effective CSS and JS through imports, Blade includes, inline styles/scripts, and theme/role selectors. Inspect computed styles when source rules conflict. Do not assume that a declared token or a stylesheet filename represents the rendered result.

### Project reference map

Paths below are relative to the repository root; inspect current contents before relying on them.

| Concern | Staff reference and shared dependencies |
| --- | --- |
| Shell, sidebar, headers, theme | `resources/views/layouts/panel.blade.php`, `resources/views/partials/sidebar.blade.php`, role sidebar partials, `resources/views/partials/dashboard-header-controls.blade.php` |
| Palette, fonts, surfaces, responsive rules | `resources/css/app.css`, `resources/css/staff-reference.css`, `resources/css/staff-intake-reference.css`, `resources/css/branch-admin-reference.css` |
| Dashboard cards, summaries, tabs | `resources/views/dashboards/staff.blade.php` and its included staff partials; targets are `dashboards/admin.blade.php`, `dashboards/system-admin.blade.php`, `dashboards/owner.blade.php` |
| Case Records and filters | `resources/views/staff/funeral_cases/index.blade.php`; compare `resources/views/admin/reports/master_cases.blade.php`; inspect `partials/case_filter_toolbar.blade.php`, `partials/case_record_filter_styles.blade.php`, `partials/uniform_filter_styles.blade.php` |
| Payments and payment dialogs | Shared `resources/views/staff/payments/history.blade.php`, `staff/payments/_record_modal.blade.php`, and its included partials |
| Forms and detail pages | `resources/views/staff/intake/_form.blade.php`, `staff/funeral_cases/show.blade.php`, `staff/funeral_cases/edit.blade.php`, and relevant client/deceased pages |
| Additional visual layers | `resources/views/staff/partials/remaining-pages-visual-system.blade.php`, `staff/partials/case-details-visual-system.blade.php`, and page-local styles |
| Reusable controls | `resources/views/components/` (buttons, inputs, badges, pagination, modal), `resources/views/layouts/modal-frame.blade.php`, and `resources/js/app.js` |

`resources/css/design-system.css` also exists: verify whether it is loaded before using it. The panel currently adds `staff-ui` for staff and `branch-admin-ui` for Branch Admin; these selectors do not automatically cover System Admin or Owner. Global and role layers can define different fonts and palettes. Use the effective staff component styling as the reference, not an older global theme. If no staff counterpart exists (such as owner analytics or recovery), apply staff foundation and equivalent component styling while retaining the target's structure and workflow.

## Implement in controlled batches

Before implementing an adoption, read [references/staff-css-source-map.md](references/staff-css-source-map.md) and the full current `resources/css/staff-reference.css`. The reference records inspected tokens, selector families, later overrides, and breakpoint traps. The live source and rendered cascade take precedence over this dated map; update the map if the source changes. Do not use this map as proof of browser verification or as a universal stylesheet for forms/tables outside the dashboard.

For a full adoption, proceed through shared foundation, shell, common components, then Branch Admin, System Admin, and Owner pages. Within a role, handle lists/tables, forms/details, then dashboards/reports. For a focused request, touch only its required pages and shared dependencies. Complete each batch's checks before expanding it.

- Derive shared semantic tokens from the actual staff light/dark styles: canvas, surface, muted surface, text, secondary text, border, primary action, hover, focus, and semantic statuses. Preserve distinct success/warning/danger meaning; do not turn every status green.
- Prefer one shared rule/component for a repeated pattern. Keep exceptions scoped to components or documented role needs. Do not attach a staff role class to all users as a shortcut: audit its full selector impact first and use a shared presentation scope when appropriate.
- Establish clear CSS ownership. Consolidate conflicting rules for the touched pattern after checking their consumers. Avoid accumulating inline overrides, duplicate palettes, increasingly specific selectors, or new `!important` rules to mask an unresolved cascade. Preserve unrelated overrides.
- Reuse Blade components only when their parameters preserve target content and hooks. Do not import a staff partial that also injects staff data assumptions or actions.
- Do not add redundant controls with the same purpose in one component or flow. Prefer a single clear destination per card: the card may be a semantic link with a separate eye button for an overview, without repeating a visible destination link inside the card. Keep overview actions distinct and plainly named; avoid duplicate shortcuts when the designated management page already provides them. Preserve existing actions unless the user authorizes consolidation or removal, as in this dashboard cleanup. Audit-card drilldowns must retain the selected event/record context and respect authorization on the destination page.
- Keep existing header visibility controls (`hide_layout_topbar`, `suppress_layout_page_header`) and verify the rendered result has one intended page heading and the original actions. Do not remove headings or controls to resolve duplication without tracing ownership.
- When changing a shared stylesheet, layout, or partial, enumerate all affected role consumers. Verify those consumers, including staff, before declaring that batch complete.

### Adoption checklist

For filter-only adoption across roles, use [references/shared-filter-ui.md](references/shared-filter-ui.md). Reuse the shared presentation layer while preserving all filter behavior and date workflows.

For Branch Admin Audit Logs, read [references/audit-logs-patterns.md](references/audit-logs-patterns.md); preserve filters, export, metadata and server branch scope.

For Branch Admin Services, read [references/services-patterns.md](references/services-patterns.md); preserve read-only permissions and all catalog tabs/actions.

For User Management, read [references/user-management-patterns.md](references/user-management-patterns.md); preserve role/branch permissions and all account actions while applying the staff component foundation.

For Payment Monitoring, read [references/payment-monitoring-patterns.md](references/payment-monitoring-patterns.md) and reuse its role-scoped staff visual layer without changing payment data, permissions or workflows.

For Case Full Information, read [references/case-details-patterns.md](references/case-details-patterns.md); use the shared detail visual layer with role-scoped tokens and preserve case data, actions, return links and print output.

For Case Records and equivalent list pages, read [references/case-records-patterns.md](references/case-records-patterns.md) before implementation. It specifies the approved search/filter hierarchy, horizontal mobile table, eye tooltip and pagination behavior. These patterns are part of the adoption contract; do not substitute mobile cards or one enclosing page card.

For the recurring page-header, action, modal, feedback and verification decisions agreed during cross-role adoption, read [references/cross-page-conventions.md](references/cross-page-conventions.md). Apply each convention only to a comparable component; preserve the target page's actual data, permissions and workflow.

Apply applicable items; mark absent components as not applicable instead of adding them.

| Area | Match to staff while preserving target content |
| --- | --- |
| Canvas and headers | Background, title/subtitle hierarchy, content gutters, header action alignment, content width |
| Navigation | Icon family/treatment, active/hover/focus styles, spacing, collapsed/locked/mobile sidebar, account menu and theme toggle |
| Cards and dashboards | Surface, padding/gaps, borders/radii/shadows, metric hierarchy, tabs; preserve metrics, sections, charts and ordering |
| Buttons and icons | Primary/secondary/danger hierarchy, size, alignment, hover/focus/disabled/loading, accessible labels and hover/focus tooltips |
| Search and filters | Field heights, toolbar spacing/wrapping, popovers, active chips, reset control; retain filters, defaults and query state |
| Tables and pagination | Header/row appearance, density, numeric alignment, badges, row actions, page-size/footer layout; retain columns and formatting |
| Forms | Label/input/help/error appearance, section spacing, existing required indicators and Save/Cancel controls; retain bindings and validation |
| Modals and drawers | Header/body/footer appearance, responsive width, viewport coverage, stacking, focus/close behavior and background scroll control |
| Feedback | Existing toast/alert/confirmation/empty/loading/error styles and legibility; preserve messages and triggers |
| Responsive and accessibility | Readable contrast, visible keyboard focus, accessible icon controls, predictable wrapping and scrolling; no concealed controls |

## Verification and completion gates

For application changes, verify the staff reference and every changed target page in light and dark modes at desktop and narrow widths (for example 1440px and 390px). Check intermediate widths where toolbar/table layouts change. Record actual widths, routes, themes, and exercised states in the adoption matrix.

- Compare the before baseline: no missing/renamed/reordered content, columns, fields, options, menu entries or actions; dynamic values may differ naturally. Confirm role/branch access conditions remain intact.
- Exercise applicable controls: search, selects, custom dates, More filters, chips/reset, sorting, tabs, pagination/page size, row-action tooltip, summary toggle, existing form validation, modal open/close, account menu, theme toggle, and sidebar collapse/lock/mobile dismiss.
- Inspect opened popovers, long labels, validation errors, empty results, disabled/loading states and existing confirmation/success/error states where available. Use test fixtures or an isolated test database for workflow writes; do not create payments/cases or trigger recovery/deletion on live data for visual QA.
- Confirm the document has no unintended horizontal overflow or clipped actions. Wide tables may scroll within their intended container; do not force every column into a phone viewport or hide columns. Check scroll ownership, sticky headers/footers, and controls near viewport edges.
- Modal scrim must cover the whole viewport, including sidebar, using shared `--modal-scrim` (`rgba(15, 23, 42, 0.72)`). Check stacking contexts, Escape/close, focus entry/containment/return, and background scroll behavior for applicable dialogs. Keyboard focus and tooltips must remain visible.
- Check text contrast in both themes (normal text at least 4.5:1), distinguishable statuses beyond color alone, accessible names for icon controls, and labels/errors associated with fields. Respect reduced motion when altering animation.
- For CSS/Blade/JS application changes, run `npm.cmd run build`, `php artisan view:cache`, and relevant existing feature tests for the touched workflow. Inspect test configuration before running; use the configured test database. Clear generated view cache after validation with `php artisan view:clear`. These commands do not substitute for browser verification.
- Choose relevant existing tests from `tests/Feature` (for example `DashboardAccessTest`, `AdminScopeAuthorizationTest`, `ReportsModuleTest`, `AdminOwnerInputConsistencyTest`, or the touched workflow's tests). Inspect test contents before selecting them; passing unrelated tests is not evidence of adoption. Use existing browser tests when they cover the change and run in an isolated environment. Do not add tests that only assert CSS strings or mirror markup.

Mark a page complete only when content preservation, visual comparison, interactions, responsive behavior, both themes, and applicable automated checks pass. A shared-component change also requires checking affected role consumers. Fix the cause of failures and recheck the affected matrix entries; do not repeatedly rerun unrelated checks.

If browser access, credentials, fixtures, or a required tool are unavailable, continue independent source/build checks and list exact unverified pages/states. Mark those entries implemented but unverified; do not claim visual completion. Report existing failures separately from regressions introduced by the adoption.

Finish with the roles/pages changed, shared components updated, content-preservation result, checks actually run, and remaining unverified items. For skill-only documentation edits, validate the skill itself; application builds and browser QA are not required until application files change.
