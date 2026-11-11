# Shared filter presentation

For Service Management-derived Filter/Sort states on Case Records, Master Case Records, Owner history and Payment Monitoring, use the [current Filter/Sort contract](../../funeral-system-ui-consistency/references/filter-sort-states.md). It supersedes generic sage-surface guidance below for those triggers and options. Match the rendered stable trigger frame, option hover/selection and keyboard focus together. Preserve explicit Apply on case/payment filters and existing immediate sort/page-size behavior; catalog workflow is separate.

For current case-worklist appearance and behavior, use the [current UI decision ledger](../../funeral-system-ui-consistency/references/current-ui-decisions.md). Its scoped rules supersede earlier case toolbar/date/reset guidance; they do not redefine catalog or payment filters. Update the ledger alongside UI revisions and preserve evidence levels.

Case Records and Master Case Records wrapped date/sort selects set `--filter-control-border:0` and `--filter-control-background:transparent`. The generic control rule consumes these variables so its higher specificity cannot restore an inner box. Keep the outer wrapper border and its accessible focus treatment. Custom date panels start hidden even when a CUSTOM range is already applied; only an explicit Custom Range selection opens the date panel. On case worklists this is now a side popover, not a modal; other consumers keep their existing workflow.

Shared custom select options include a trailing `bi-check2` icon visible only for `aria-selected=true`. Keep option text in a separate span so accessible labels stay intact. Master Case Records outside-click handling must exempt `.filter-select-menu`, otherwise choosing Custom Range opens then immediately dismisses the date panel. Clear remains the existing active-filter reset action.

`layouts/panel.blade.php` includes `partials/shared_filter_ui.blade.php` after the legacy uniform layer. All panel roles reuse the approved Audit Logs sage visual tokens: light surface #D0E2D6/text #081D13/border #AEC7B7; dark #14261D/#F8FAFC/#29523D. Plus Jakarta Sans, 14px controls, 44px border-box, 8px control radius, 12px menu radius, selected/hover/focus states.

Scope only existing GET filter forms and explicit record/report/analytics filter groups. POST create/edit forms, guest/auth and print views are excluded. Search/select wrappers own one border and inner controls fill the content height transparently. Preserve existing control padding/icons, options, values, hidden fields, events, auto/manual Apply behavior, custom-date workflow, permissions and pagination. Native select chevrons can follow :open; custom dropdowns retain their existing open-state handling. Do not simulate a native select open state using focus.

Source consumers: Staff client/deceased/case/completed/payment/reminder filters; Branch Admin dashboard-independent case/payment/user/service/audit/report filters; System Admin user/branch/catalog/recovery/report filters; Owner history/sales/analytics/report filters. Role-specific controls remain intact. Recheck light/dark and responsive states per route before claiming visual completion; shared inclusion does not prove every role has been browser-tested.

## Current directory/audit/report scope (2026-11-03)

Read [directory and audit adoption](../../funeral-system-ui-consistency/references/worklist-adoption.md) before applying these patterns to Branch/User Management, Audit or Reports/Analytics. Their current neutral menu-surface frames supersede generic sage trigger fills above. Branch/User/Audit use pending Apply; Reports keep immediate preview/soft-navigation and Branch-before-Date order. Their custom date controls now use non-modal side popovers. Preserve each scoped role and do not invent absent Audit search/sort.


Latest approved six-worklist surface skin: partials/record_surface_styles, included only for the list routes, now owns borderless soft-green Search/Filter/Sort surfaces, flat header/KPI/toolbar backgrounds and no-shadow presentation. Keep keyboard focus and row-hover outline; retain original filter events. This supersedes default border/surface colors on those pages only. Exact scope, tokens and verification are in the current decision ledger.

Expanded flat surface scope: native Sort/Cases per page selects and their option popovers, inner filter fields, popup header/footer/Apply, date panels and pagination now consume the same updated tokens. Transparent borders and no shadows apply to these defaults; keyboard focus remains visible. See the current decision ledger for the 1,865-assertion verification and modal scope limits.

Latest panel/Sort correction: main Filter panel/body/footer #D5E4D9 light / #1C3025 dark; option/Sort/page-size menus #F0F6F2 / #304C3B. Case Sort is one painted wrapper with a full-size transparent native select and pointer-transparent icons. Hover/open uses background only; keyboard focus outline is retained. This supersedes identical popup surfaces and the narrow inner Case Sort hit area. See current decisions for 1,549 rendered assertions and exact source ownership.

Latest hover decision: individual filter fields/options and table rows reuse --rs-item-hover (#C4DDCE light / #41664E dark). Row hover is a fill only; prior pointer-hover outline requirements are superseded. Keyboard focus, eye animation, eye-only tooltip and click behavior remain. Payment history rows are included even when their modal is moved outside the page container. Current decisions record the 1,949 passing assertions and source owner.

Latest hover refinement: shared individual-filter/row highlight is #E1EEE6 light / #2D4838 dark. Rows/cells fade background over 240ms ease on entry/exit; reduced motion disables that fade. This supersedes the stronger immediate highlight. Eye motion and keyboard focus remain independent. Current decisions record the 2,041 passing assertions, Branch menu lifecycle fix and compact Branch grid-card sizing.


Current six-worklist dropdown spacing is owned by the funeral-system UI decision ledger: Service-derived .9rem insets, .7rem field gaps, .55rem label/control and icon/text gaps, 4px option gaps. Payment sibling labels use explicit margins rather than adding flex gaps. Do not change Apply behavior, max-height/scroll, hidden-menu display ownership or row minimum heights to copy spacing. Implemented but browser verification remains pending after declined execution permission.


Latest heading/Sort correction: use one 12px gap after the main Filter heading row, and 12px after native Sort by headings. Directory Sort is now included in the existing app.js menu heading generator, restricted to Directory pages, with a full-size native trigger and responsive 280px popup matching the other Sort owners. Selected/check styling uses shared choice tokens. Build/Blade checks and three renderer Node tests passed; browser visual acceptance is still pending. See the current decision ledger for exact source owners and limits.


Current six-worklist chevrons follow Payment: rotate-only 0/180deg over 160ms ease, actual open-state ownership, separate translation and reduced-motion support. Case/Master date fields reuse one funnel above a transparent select with reserved text padding. See the current decision ledger for owners and pending browser verification; do not restore the older Service rotation split.
