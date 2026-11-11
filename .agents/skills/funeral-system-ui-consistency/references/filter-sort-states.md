# Filter and Sort interaction contract

Current revision: 2026-11-03. Service Management is the reference for comparable Filter/Sort presentation. Read the actual working-tree sources and effective cascade before adoption. This contract does not authorize changing another page until that page is in scope.

## Current record-worklist spacing

For the six approved record worklists, the latest Service-derived spacing and verification status are owned by [the current decision ledger](current-ui-decisions.md): .9rem panel/side padding, a single 12px heading-to-content gap, .7rem field gap, .55rem label/control and option icon/text gaps, and 4px option gaps. Directory Sort shares the generated Sort by heading; native and catalog Sort panels use a responsive 280px width cap. Existing row minimum heights, scrolling, Apply behavior and hidden-menu display ownership remain. This latest spacing revision is implemented but awaiting browser verification after the declined execution permission; earlier measured evidence below does not verify it.

## Current states

| Element/state | Required presentation and meaning |
|---|---|
| Closed trigger | 44px height, 8px corner radius, menu surface, one 1px border, readable ink. Keep each page's approved width and position. |
| Pointer hover/pressed | Apply this only to the top-level/main Filter and Sort triggers: use a soft semantic green surface (light #E5EFE8, dark #1A3023) and strengthen the border (light #7FA98E, dark #527461). Do not apply this main-trigger state to select fields inside an open Filter panel. Keep ink readable and geometry fixed. No translation, scale, glow, shadow or added outline. This supersedes the former visually unchanged pointer-hover rule. |
| Open trigger | Same neutral frame; chevron rotates 180 degrees and returns to zero when closed. Observe the real open state, never focus alone. |
| Applied filters | Existing count badge/chips represent committed filters; this is distinct from open, pressed and hovered. Do not give the whole trigger a selected fill just because a legacy `.active` class exists. Search does not increment the filter badge. |
| Unselected option | Transparent surface, reserved 1px transparent border, regular text. Existing trailing check is hidden while preserving its slot. |
| Option hover/keyboard focus | Reference `--record-choice-surface` fill, `--filter-ui-border` border. Dropdown options use a 4px adjacent-item margin so hover/selected surfaces do not touch. Do not set `display` on `.filter-select-menu`: its `[hidden]` state owns open/close visibility and a more-specific `display:* !important` causes a stuck Sort menu. Keep label/icon/check alignment and row geometry stable. |
| Selected option | Reference `--record-choice-surface` fill, 650 weight and visible trailing check; selected fill remains on hover. Use semantic selected attributes, not label matching. |
| Trigger keyboard focus | Single 2px `--filter-ui-focus` outline, 2px offset. Composite case Sort paints the ring on its wrapper while its select has focus-visible. Pointer interaction suppresses click rings through the existing modality handler. |
| Disabled/loading | Preserve existing disabled semantics and behavior; do not make a disabled choice clickable or invent a new loading state. Keep ready headers/controls usable during result-only skeletons. |

Trigger palette from the actual Service reference: light surface `#F4F8F5`, border `#AEC7B7`, text `#081D13`; dark surface `#112218`, border `#29523D`, text `#F8FAFC`. Use `--filter-ui-menu`, `--filter-ui-border`, `--filter-ui-text`; do not use Payment's locally overridden `--filter-ui-surface` for option selection. At the verified Service root, option hover/selected fill is light `#D0E2D6`, dark `#14261D`. The adoption uses `--record-choice-surface` for these measured fills and `--filter-ui-border` for the option hover border. Do not inherit target `--surface-muted` or `--brand-soft` blindly: Staff/Admin/Owner resolve those names differently. Selected check glyphs use the reference green: light `#1B4332`, dark `#B5D6C4`.

For the six approved record worklists, Payment Monitoring now owns the reference chevron pattern: main Filter/Sort and Case date arrows use independent rotate with transform:none, 0deg closed / 180deg open, 160ms ease. Actual is-open, open or Case Filter aria-expanded owns the state. Keep vertical translation separate and disable transitions under reduced motion. The scoped record-surface owner replaces the older Service transform/rotate split. Browser verification is pending; see the current ledger. Never apply both rotation mechanisms to one icon. For a custom select inside a manual-Apply Filter, contain both its trigger pointer/click activation and its detached option click. The trigger must open its child list without allowing page-level document handlers to close the main Filter; an option closes only the small selection list and keeps the main Filter open. Because these pages still contain native `<details>` and legacy bubbling/capture change owners, the shared option commit must explicitly preserve the already-open parent after dispatching `input/change`; event propagation containment alone is not sufficient. Restore only a parent that was open before the choice and is still connected, so Apply/navigation and intentional Close remain authoritative. Closing a main Filter intentionally must hide any descendant top-layer `.filter-select-menu`, clear its wrapper `.is-open`, and reset the child select's `aria-expanded`; hiding the parent alone does not remove a manual popover from the top layer. Do not add transitions to panel closing or change dimensions on open/hover.

## Source owners and adapters

- Reference markup and generated controls: `resources/views/admin/service-management/index.blade.php`, `resources/js/catalog-filter-sort.js`, `resources/css/catalog-filter-sort.css`.
- Winning Service trigger frame and case layout: `resources/views/partials/case_records_layout_styles.blade.php`. Generic palette, options and pointer focus suppression: `resources/views/partials/shared_filter_ui.blade.php`.
- Adoption CSS: `resources/views/partials/record_filter_sort_states.blade.php`, included by the case layout partial. It targets existing case toolbars, consistent Payment and Service Management controls/options. Service trigger geometry and final themed surfaces remain unchanged; shared rules remove inherited background/border transitions to keep these surfaces stable. Legacy global button/pressed rules had suppressed catalog option hover and left a light selected fill in dark mode; the scoped shared option rules restore themed feedback consistently. Search, pagination, Clear filters and business events are unchanged.
- Case markup: `resources/views/partials/case_filter_toolbar.blade.php`; Filter uses `aria-expanded`, custom Sort uses wrapper `.is-open` and select expanded state. `.case-compact-more.active` counts applied fields, not dropdown visibility. The wrapper owns Sort's border; its inner select stays transparent.
- Payment markup/styles: `resources/views/staff/payments/_toolbar.blade.php`, `resources/views/staff/payments/_consistency_styles.blade.php`. Filter uses native details `[open]`; custom Sort observes parent `.is-open`. Here the select owns the visible Sort surface—do not compare a transparent wrapper to Service's painted summary.
- Custom option rendering and selected check: `resources/js/app.js` (`filter-select-menu__item`, `aria-selected=true`); Service uses `.catalog-control-option[aria-pressed=true]`. Reuse existing input modality in `resources/js/shared-record-controls.js`.

## Behavior that visual adoption must preserve

Case/Master/Owner history and Payment retain explicit **Apply filters** for pending filter fields. Sort and page size retain existing immediate submission. Service retains its existing selection workflow; matching appearance does not authorize copying auto-apply behavior. Preserve custom date/side popovers, outside-click/escape handling, counts, query values, asynchronous results replacement, and role/branch authorization.

## Verification and next-page checklist

1. Identify the painted owner, actual open/selected attributes, inherited theme tokens and later overrides. Class names and declared styles alone are insufficient.
2. Compare closed, real pointer hover, pressed/open, selected option, unselected option hover, keyboard focus and close/reset in both themes. Assert fixed trigger bounds, one border and no pointer ring. Check long options and existing narrow layouts; do not force universal widths.
3. Cover each role that consumes the shared view: Staff Case Records; Branch/System Master Case Records; Owner case history; Staff/Branch/System Payment Monitoring. Owner Payment access remains unchanged. Test role-specific options without broadening scope.
4. Preserve each page's search/manual-Apply/sort/page-size/async behavior. Run relevant role/render and navigation regressions, compile Blade and build assets only when assets change.
5. Update this contract, [current decisions](current-ui-decisions.md), overlapping staff guidance and verification notes in the same task if the user revises a state. Replace superseded rules instead of adding competing standards.

Automated comparison owner: `tests/Browser/FilterSortStatesTest.php`, using isolated SQLite data and real role-rendered HTML with built assets. It compares browser-computed states against Service Management, uses CDP-forced hover/focus-visible pseudo states and the existing open/selected attributes, and checks fixed bounds and the visible focus-ring CSS. This CSS regression does not replace native keyboard, dropdown workflow or per-page live visual QA. This is not a blanket claim of visual acceptance for every future page, live dataset or viewport.




## Directory/Audit and Reports filter adapters (2026-11-03)

See [directory and audit adoption](worklist-adoption.md) for current component owners and scoped evidence. Branch/User Filter+Sort use the neutral reference states and manual Apply. At desktop directory widths, both triggers are220px and Sort visibly reads `Sort: {selected option}` with concise option names; do not collapse it back to an icon plus cramped label. The fixed Grid/Table slot precedes applied chips and must not shift when filter state changes. Audit reuses Filter and immediate Events per page without adding absent search/sort. Reports/Analytics receive equivalent filter frames and Branch-before-Date order only, with custom dates in a non-modal side popover. Retire legacy User toolbar paint rules from `[data-worklist-form]` rather than layering selected fills onto the new controls. Actual shared consumers and permissions remain distinct.


Current surface revision (2026-11-05): record_surface_styles supersedes default outlined control/menu surfaces on Case/Master, Payment, Branch/User and Service worklists only. Controls use #EAF2ED light / #22392C dark, hover/open #D8E7DD / #2D4938; panels use #F7FAF8 / #15251C. Transparent borders preserve geometry, no shadows, visible keyboard outlines remain. Existing applied counts, selected states, options and manual/immediate submission behavior are unchanged. See current decisions for the scoped owner and 1,260 rendered surface assertions. Other pages remain outside this revision.

Expanded flat surface scope: native Sort/Cases per page selects and their option popovers, inner filter fields, popup header/footer/Apply, date panels and pagination now consume the same updated tokens. Transparent borders and no shadows apply to these defaults; keyboard focus remains visible. See the current decision ledger for the 1,865-assertion verification and modal scope limits.

Latest panel/Sort correction: main Filter panel/body/footer #D5E4D9 light / #1C3025 dark; option/Sort/page-size menus #F0F6F2 / #304C3B. Case Sort is one painted wrapper with a full-size transparent native select and pointer-transparent icons. Hover/open uses background only; keyboard focus outline is retained. This supersedes identical popup surfaces and the narrow inner Case Sort hit area. See current decisions for 1,549 rendered assertions and exact source ownership.

Latest hover decision: individual filter fields/options and table rows reuse --rs-item-hover (#C4DDCE light / #41664E dark). Row hover is a fill only; prior pointer-hover outline requirements are superseded. Keyboard focus, eye animation, eye-only tooltip and click behavior remain. Payment history rows are included even when their modal is moved outside the page container. Current decisions record the 1,949 passing assertions and source owner.

Latest hover refinement: shared individual-filter/row highlight is #E1EEE6 light / #2D4838 dark. Rows/cells fade background over 240ms ease on entry/exit; reduced motion disables that fade. This supersedes the stronger immediate highlight. Eye motion and keyboard focus remain independent. Current decisions record the 2,041 passing assertions, Branch menu lifecycle fix and compact Branch grid-card sizing.
