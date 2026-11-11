# Superseded: opposite reference direction

The user corrected the direction: Branch Add/Edit is the source and Service modals are the consumers. The mistaken Branch override was removed. Use [the corrected decision](service-modal-branch-reference-2026-11-05.md). The content below is historical and must not be reapplied.

Implemented; browser visual verification remains pending after earlier declined execution.

Applicable: Branch Add modal and AJAX Edit modal only. Reference: existing non-package Service catalog modal form hierarchy in admin/service-management/partials/catalog-form-ui.blade.php, outer service modal shell/reference-ui and the effective shared modal surface layer. Already consistent: Branch routes, inputs, validation, return_to, dirty-form guard, status safeguards and close/save handlers. Not applicable: standalone Branch forms, Branch View/status feedback, Service form changes, Package pages or business rules. No required functionality missing.

Final scoped owner: partials/branch_modal_form_styles.blade.php, included last from record_modal_surface_styles. It overrides old Branch utility/inline form styles only inside branchCreateModalSheet or branchModalSheet.branch-edit-mode.

- Both forms use a 576px viewport-capped centered shell, 12px corners, no decorative outer border or shadow, and viewport-bounded content. Header/footer remain fixed while only the body scrolls when needed.
- Sage header, 20px/24px padding, 20px title, 13px subtitle, 40px soft icon tile and 44px Close control; theme-aware thin divider.
- Body padding 24px; equal two-column fields with 14px gap; 13px labels, 14px values, 44px controls, 8px corners and soft backgrounds. Readonly Branch Code and all original input names/IDs/constraints preserved. Under 480px, fields stack and horizontal padding becomes 18px.
- Operating status is a divider section with existing toggle/badge/help text. Add toggle and Edit hidden status value/safeguard remain. No new status action.
- Footer uses 16px/24px padding, 10px gap and 44px controls. Cancel neutral, Save uses the existing approved sage action tokens rather than reviving a dark main action. Keyboard focus and actual invalid-field borders stay visible; no pointer-focus decorative outline. The hidden switch receives a visible wrapper focus ring.

Checks: WorklistAdoptionTest seven tests/123 assertions and the existing Branch validation/safe-return check one test/28 assertions passed. Blade compilation/cache clear and touched tracked-file whitespace passed. Asset sources unchanged. Desktop/intermediate/narrow light/dark appearance, actual overflow and click/focus behavior remain unverified in the browser. Do not treat source comparison as rendered visual parity.
