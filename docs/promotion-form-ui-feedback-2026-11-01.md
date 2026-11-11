# Promotion Form and Action Feedback — UI Follow-up

## Agreed interaction patterns

- Add/Edit Promotion uses the Service Management modal shell as its only modal title and description. The form must not render a second heading inside the modal.
- Keep the promotion form compact: reduce inner padding and vertical gaps, use the existing two-column layout where it fits, and stack fields on mobile.
- Do not show client-side validation errors merely because an Add form opened. Show them after a field is touched or a submit is attempted. Server validation errors remain visible after a rejected submission until the corresponding field is corrected.
- Keep existing promotion validation, status rules, discount calculation, and persistence unchanged.
- Promotion list actions use compact icon buttons with an accessible name and a hover/focus tooltip. The status action is a power icon for Turn Off/Turn On.
- A promotion status action must open exactly one confirmation dialog. Exclude promotion forms from the generic catalog status confirmation handler because Promotions already uses its own confirmation dialog.
- Use one post-action confirmation message. Do not layer an additional promotion-specific toast on top of the existing global flash feedback.

## Follow-up changes recorded

- `resources/js/promotion-form.js`: defer client errors until interaction/submission while preserving server errors.
- `resources/js/service-confirm.js`: leave promotion confirmation to its dedicated dialog.
- `resources/views/admin/promotions/form.blade.php`: omit duplicate modal heading and tighten modal spacing.
- `resources/views/admin/service-management/index.blade.php`: use tooltip-enabled icon actions for View, Edit, Turn Off, and Turn On.
- `tests/promotion-form.test.cjs`: cover initial, touched, submitted, and server-error feedback states.
- Promotions catalog uses the existing Grid/Table segmented control. Both views render the same server-filtered, searched, sorted, and paginated promotions; the selected view is preserved with the existing per-tab preference (`service-catalog-view-promotions`).
- Promotion View/Edit/Turn Off/Turn On actions reuse the catalog `row-hover-action` and shared single-tooltip behavior; no promotion-specific CSS tooltip is layered on top.

