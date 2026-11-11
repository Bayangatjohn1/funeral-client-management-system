# Case Records patterns

Current case-worklist decisions are owned by [funeral-system-ui-consistency's decision ledger](../../funeral-system-ui-consistency/references/current-ui-decisions.md). Read it before editing the toolbar, dropdowns, dates, reset control, tabs, table, row actions, or typography. It records the latest user corrections, exact source owners, and pending visual checks. Update it in the same task whenever the user revises a choice. This file retains adoption safeguards rather than duplicating a competing visual specification.

## Superseded guidance

Filter/Sort trigger hover/open, applied count, option selection and keyboard focus now have a dedicated [Service-derived state contract](../../funeral-system-ui-consistency/references/filter-sort-states.md). Follow it together with the ledger; copying the default surface alone is incomplete.

Earlier instructions for centered or grouped tabs, five-column search/date/sort toolbars, modal Custom Range, underline tabs, Clear All buttons, and outlined icon hover no longer describe the case pages. Latest approved intent includes left pill tabs with strong selected state, Filter/Sort parity, side-opening selections and custom date range, text-only Clear filters, soft row outline, and View color feedback and subtle eye-glyph shrink on row/direct hover with an eye-only pointer tooltip; other actions preserve their existing semantic feedback. Column headings were most recently strengthened to weight 600; row text remains regular. The ledger separates these implemented choices from visual acceptance.

## Preservation and scope

- Source consumers: `resources/views/staff/funeral_cases/index.blade.php`, `resources/views/admin/reports/master_cases.blade.php`, `resources/views/owner/cases/history.blade.php`. Shared controls/layout: `partials/case_filter_toolbar.blade.php`, `partials/case_records_layout_styles.blade.php`, `partials/case_record_filter_styles.blade.php`; actual JS/CSS owners are listed in the ledger.
- The user-approved compact table now has Case ID, Deceased, Interment Date, Case Status, Payment Status, Remaining Balance, Actions. Owner retains Branch after Case ID. Secondary client/service/package/encoding details, complete schedules and payment breakdown live in the summary drawer; they are no longer repeated in visible table cells. Preserve underlying values, date fallback order, client search metadata/query behavior, branch scope, status meaning and authorized actions. Use existing balance_amount, not total_amount, for Remaining Balance. Draft intake tables are excluded. Current ledger owns source/verification details.
- Keep pagination separate. The latest user correction connects status tabs to the page header using the header surface and rounded bottom corners. A shared thin divider is inset 16px from both header edges, with 16px spacing before the attached tabs; its full content width does not shrink to the tab labels. See the ledger for verification status. Toolbar/table start 16px below, with their top corners restored (see the current decision ledger). Do not enclose the entire worklist in a new outer card or generate mobile cards/hide columns.
- Tables scroll horizontally on narrow screens. Keep the scroll region keyboard accessible and labeled; keep pagination outside it. Verify page 2/Previous using isolated fixtures exceeding page size, including query preservation and non-overlapping results.
- Sticky toolbar uses the page scroller with a 16px inset. Table headers remain in the table wrapper's stacking context (`position:relative; z-index:0; isolation:isolate`) below the toolbar. Hit-test overlapping controls during actual scrolling, not just offsets.
- Custom dropdowns use actual `.is-open`/`aria-expanded` state, never focus as a proxy. Top-layer menus escape scroller clipping; outside-click must exempt active menu/date controls. Preserve pending selections and explicit Apply filters behavior. The case date range is now a side popover; do not restore the earlier modal merely by reusing legacy helpers.
- Composite controls own one surface/border and a single accessible keyboard focus treatment. Uniform input rules consume `--filter-control-background` and `--filter-control-border`; inner controls must not repaint the wrapper or restore nested outlines.
- View opens the concise summary drawer while preserving the actual full-details href and safe return_to; modified clicks keep native navigation. Summary content is identity/status/branch, client/contact, service/schedule, existing amounts and encoding information. Full Details retains documents, charges and transactions. Use the current ledger for drawer geometry, loading/retry/abort behavior and pending browser checks.
- Preserve actual detail/edit/print links and safe return URLs. The current table hierarchy exposes a40px View eye with an18px glyph plus a separate transparent More-actions trigger when authorized secondary actions exist; Edit and Print are labelled menu items. Shared tooltips are `View case` and `More actions`. Drafts remain staff-only; Owner is read-only; Edit remains policy-conditional. Do not copy those business rules to unrelated modules as a styling operation.

## Verification

Check source plus rendered light/dark, desktop/intermediate/390px states and long names, active filters, every menu, Custom Range, empty results, icon tooltips/focus, pagination, and print preview as applicable. A shared include or build success does not prove that all roles passed. The 2026-11-01 operational/UI review and the new skill's evidence table list checks actually completed; later visual revisions remain pending until exercised. Preserve that distinction in reports.

## Empty case results and pagination (2026-11-02)

Follow the current empty-results/footer entry in the [decision ledger](../../funeral-system-ui-consistency/references/current-ui-decisions.md). Shared results styles reserve a responsive minimum height, center the existing empty message within the visible horizontal scroller, and keep pagination in a separate normal-flow footer. Case pagination always renders; zero totals show 0 records with disabled controls. Preserve role scope, populated links and column widths. Verified in isolated cross-role render/geometry checks; complete live visual acceptance is pending. Do not restore collapsing empty tables or hide pagination solely because there is only one page.

Search component: follow the [complete current record-search contract](../../funeral-system-ui-consistency/references/record-search.md), not width alone. Case/Master/Owner now match Payment Monitoring: capped width with fixed Filter/Sort/page-size positions, integrated Search button, X, one-row Case ID/client/deceased suggestions with underlined query matches, Search/Enter selection, remote role-scoped matching and refresh reinitialization. Preserve existing backend fields and role scope.

Current button/tab revision: see [2026-11-05 decision](../../funeral-system-ui-consistency/references/current-ui-decisions.md). Service rounded 12px status tabs supersede the older fully rounded/outlined pills; preserve existing labels, routes and placements. Verification remains aspect-specific as recorded there.

View-row interaction revision (2026-11-05): reuse the existing View control for row clicks and Enter; keep other controls independent and prevent duplicate activation. Row hover changes the eye color without showing its tooltip. Current source owners and 215 browser assertions are recorded in the [current decision ledger](../../funeral-system-ui-consistency/references/current-ui-decisions.md).

Current row appearance: light View hover #1B4332, dark unchanged #5CE0A0. Use the outer 2px row outline (#719C80 light / #78AB8B dark) with zero offset, no inset cell shadows and a static 2px scroller gutter. This supersedes inner-frame styling; see the decision ledger for the 364 appearance assertions.

Latest eye motion: scale only the glyph to .9 over 180ms ease on row/direct hover, keeping the action hit target and tooltip anchor stable. Respect reduced motion. This supersedes color-only eye guidance; current sources and 439 assertions are in the decision ledger.

Outline visibility revision (2026-11-05): the user found the prior frame too subtle. Current outline is 2px, #719C80 light / #78AB8B dark, outside the row at zero offset. --record-row-outline-width owns the stroke and reserved scroller gutter; Payment's clipped records panel reserves the same space. This supersedes the previous 1px, lower-contrast frame. Eye animation, colors, tooltips and click handling are unchanged. The role-rendered hover/geometry suite passed 426 assertions at 1440/390px in both themes after this revision; build and Blade checks passed.

Case/Master surface revision: outer toolbar and header now use complementary flat green surfaces without default borders/shadows, owned by partials/record_surface_styles. Do not restore inner/outer toolbar frames. Existing tabs, eye motion, row outline and keyboard focus remain. See the current decision ledger for verification.

Current Case/Master correction: column-header backgrounds are transparent; no separate colored header band. Case Sort uses one wrapper and a full-size transparent select; no double paint or small center-only click target. Main filter and option menus use distinct shades, with no pointer-hover outline. Current decisions own the exact palette and 1,549 rendered assertions.

Latest hover decision: individual filter fields/options and table rows reuse --rs-item-hover (#C4DDCE light / #41664E dark). Row hover is a fill only; prior pointer-hover outline requirements are superseded. Keyboard focus, eye animation, eye-only tooltip and click behavior remain. Payment history rows are included even when their modal is moved outside the page container. Current decisions record the 1,949 passing assertions and source owner.

Latest hover refinement: shared individual-filter/row highlight is #E1EEE6 light / #2D4838 dark. Rows/cells fade background over 240ms ease on entry/exit; reduced motion disables that fade. This supersedes the stronger immediate highlight. Eye motion and keyboard focus remain independent. Current decisions record the 2,041 passing assertions, Branch menu lifecycle fix and compact Branch grid-card sizing.


Current preview-state revision: Case/Master row and eye activation both mark the current row while its drawer is open; selection changes with the record and clears on close. Shared compact table Case/Payment Status uses semantic text colors only, without pill background/border/padding. The drawer identity now also uses labeled text statuses and a plain aligned identity section with a divider; other modules retain their status styling. Current decision ledger owns source, palette, six Node checks/two feature tests (155 assertions) and pending browser verification.


Latest selected-row styling follows Payment Monitoring: subtle 8%/13% brand tint and 3px leading marker, replacing the solid fill. Payment eye activation now resolves and marks its parent row as well. Case/Master drawer identity is card-free, aligned to the 20px body inset with a theme-aware divider before Client. Current ledger owns the latest eight Node checks, nine feature tests/278 assertions and pending browser verification.


Current case-status text revision: Active is success green and Completed neutral gray in Case/Master compact tables and summary identity. Explicit case-status attributes avoid changing Paid/payment meanings. Exact light/dark tones and verification limits are in the current ledger.


Case/Master View now uses header X only; its footer retains Open Full Details on the left. Current ledger owns source and verification limits.

Historical Cases is disabled for all roles as of 2026-11-11. Do not render its Case Records entry or restore intake/list/detail/review/proof routes during UI adoption. Existing historical data is retained. The feature flag, route gate and restoration safeguards are owned by the [current decision ledger](../../funeral-system-ui-consistency/references/current-ui-decisions.md); this supersedes the earlier Historical worklist adoption guidance.
