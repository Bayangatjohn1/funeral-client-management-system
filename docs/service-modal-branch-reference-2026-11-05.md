# Service modal forms use Branch Add/Edit as reference

User correction: Branch Add/Edit is the source. Removed the mistaken branch_modal_form_styles include/file, restoring prior Branch styling through the original index and _edit_form_styles. Earlier Branch View/grid work remains.

Applicable: Service Add/Edit modal shell and iframe forms for Casket, Add-on, Freebie, Hearse and Promotion. Already consistent: existing fields, validation, dirty/close/save and catalog refresh flows. Not applicable: Package standalone forms, View drawers, standalone catalog forms or new actions. No missing functionality required.

Final owner: partials/service_branch_form_styles.blade.php, included last from record_modal_surface_styles. Parent rules require service-modal.is-form:not(.is-package-form); iframe rules require service-catalog-form-frame and existing catalog/promotion form classes. Branch is no longer targeted.

Adopted Branch measurements: 640px viewport-capped centered shell, 16px corners, 20px/24px header, 18.4px heading, 40px icon, 24px body, 16px field gaps, 12px labels, 45.6px inputs, 10.4px field corners, 16px/24px footer and 104px minimum action widths. Catalog-specific layouts/checkboxes remain. Under 600px grids stack with 18px horizontal padding. Shared light/dark surfaces, no shadows/outer outlines and approved sage main actions remain; actual invalid fields and keyboard focus stay visible. Package pages are excluded.

Validation: ServiceCatalogConsistencyTest and WorklistAdoptionTest passed 13 tests/179 assertions; Service save/refresh and archive Node checks passed three tests. Blade compile/cache clear and touched tracked-file whitespace passed. Asset sources unchanged. Browser visual/scroll/keyboard and light/dark responsive appearance remain unverified after earlier declined execution. Source measurements are not visual acceptance.
