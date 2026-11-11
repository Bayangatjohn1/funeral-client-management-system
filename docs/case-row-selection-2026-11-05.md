# Case/Master preview selection and status text

Implemented for Staff Case Records, Branch/System Admin Master Cases and Owner history. Browser visual verification remains pending after the earlier declined execution permission.

- Applicable: highlight the row whose View drawer is open, and render its Case/Payment Status as colored text only.
- Already consistent: row click/Enter delegates to the eye trigger; direct eye click shares the same drawer path. Tooltip remains eye-only.
- Not applicable: other modules, draft intake tables, drawer status badges or business status values.
- Existing functionality missing: none.

`resources/js/record-drawers.js` marks the previewed row with is-viewing-record and aria-current=true, and sets aria-expanded on the original eye link. Switching records clears the previous row; dialog close (X, Close, Escape or backdrop) clears the current row and restores focus. Loading/error/retry retains the selected context until the drawer closes. Modified navigation clicks do not select a row. Existing async abort/version guards remain unchanged.

`partials/record_surface_styles.blade.php` now follows Payment Monitoring: brand tint at 8% light / 13% dark with a 3px leading brand marker inset 12px from the top/bottom. The old solid selected fill is superseded. Selected cells are transparent to avoid doubling the tint, with enough specificity to remain selected on pointer hover. No shadow/outline is added. Existing 240ms background transition/reduced-motion rules remain. Keyboard focus and eye-only tooltips remain independent.

The shared worklist cells and Case/Master drawer identity status fields use case-status-text on existing status components, without background, border, radius, padding or shadow. Existing labels/semantic mappings remain. Success uses --rs-success (#166534 / #4ADE80), warning --rs-warning (#92400E / #FBBF24), informational blue #175CD3 / #84B8FF, danger red #B42318 / #FDA29B. Neutral keeps the existing neutral text tone. Other status components remain unchanged.

Payment's setHistoryRowActive now resolves the closest pm-history-row from either the row or its eye button, synchronizes expanded state on both, and uses the existing open/close lifecycle and original focus-return trigger. No modal loading, transactions or financial logic changes.

Case/Master drawer identity is plain aligned text: Case ID label, deceased heading, wrapping branch line, then labeled Case status/Payment status fields. Removed the soft card fill, inner padding and rounded corners. It uses the body's existing 20px content alignment, 12px identity gaps and a 1px theme-aware divider with 20px space before Client. No content was removed.

Latest verification: eight Node behavior checks passed, including Payment row/eye activation and cleanup plus existing Case drawer/motion/toolbar behavior. CaseSummaryDrawerTest and WorklistAdoptionTest passed nine tests / 278 assertions. Blade compile/cache clear and touched tracked-file whitespace passed. Asset source is unchanged; the prior production build remains applicable. Browser light/dark appearance, hover cascade and real hit testing remain unverified after previously declined execution.
