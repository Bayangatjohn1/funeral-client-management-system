# Service drawer correction

Implemented; browser verification remains pending after the earlier declined execution permission.

## Applicable and preserved

- Applicable: Service View shell position, frame sizing, body scrolling and footer spacing.
- Already consistent: existing catalog data, View/Edit destinations, confirmation logic and drawer content-node preservation.
- Not applicable: Service create/edit forms, other pages' drawer geometry or business logic.
- Existing functionality missing: none.

## Cause and correction

`admin/service-management/partials/reference-ui.blade.php` still contained the obsolete centered detail-modal rules. Those rules overrode the original detail drawer alignment. Its generic iframe rule also capped content to viewport minus 32px, while the iframe's detail document expected the full viewport. This mismatch could hide the bottom of the fixed-height detail document.

Removed the obsolete detail geometry in reference-ui. `partials/record_drawer_styles.blade.php` now explicitly owns stretch/end alignment and the detail iframe's flexible sizing without the old 32px maximum-height deduction. The nested Service detail card is constrained to the frame viewport, with a fixed header/footer and an independently scrolling body. Increased the footer rule's specificity to override the Package view's legacy top margin and sticky layout. No shadow, outer outline, routes, calculations or action changes.

Case/Master content is unchanged in this correction. Review confirms substantial table/summary duplication: client/deceased, service/schedule, package, statuses, amounts and encoding information are already visible in the table. Suggested direction, not yet adopted: retain concise record identification and use the drawer for existing contact/wake information and other authorized details absent from the table. Another valid option is direct Full Details if no meaningful quick-detail content is needed. Do not treat this discussion as approval to remove fields or navigation.

Service card and drawer can have distinct purposes: card for comparison, drawer for configured inclusion limits/rates, detailed specifications and dependencies. Reference: [Material side sheets](https://github.com/material-components/material-components-android/blob/master/docs/components/SideSheet.md), which describes side sheets as supplementary content.

## Checks

Blade compilation/cache clear, touched tracked-file whitespace and eight existing Node interaction checks passed. ServiceCatalogConsistencyTest passed six tests / 56 assertions. Browser light/dark, narrow/desktop layout, iframe hit testing and real scrolling remain unverified. No asset source changed; this correction only changes Blade presentation styles.
