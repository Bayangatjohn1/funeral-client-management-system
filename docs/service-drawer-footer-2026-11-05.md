# Superseded: Service drawer footer organization

The user chose read-only Service drawers. The grouping below was removed; use [the current read-only decision](service-drawer-readonly-2026-11-05.md). Do not restore record actions in the drawer.

Implemented; browser visual verification remains pending after earlier declined execution.

Applicable: existing View drawer footers across Service catalog/Package/Promotion details. Already consistent: original Edit/Close/status actions and confirmations. Not applicable: Add/Edit forms, standalone pages, new buttons/actions or business logic. No required functionality missing.

The existing service_detail_drawer_body partial now groups original footer nodes (without cloning): availability/archive/restore/promotion forms in a top status row, Close/Edit in a bottom main row. Native confirmation dialogs/scripts remain outside action groups. Close stays left and Edit right when present; absent actions are not invented. The top row wraps at 150px flexible button basis, has 8px gaps and a divider with 12px spacing before the main row. Main row has 12px gaps; controls have 44px minimum height, 13px text, 8px corners and consistent padding. Long labels wrap rather than collide.

Final CSS owner: partials/record_drawer_styles.blade.php, scoped to the iframe detail card/action groups; control sizing outranks older Service footer rules. record_detail_modal_styles Edit blue selector now supports the grouped descendant. Existing status buttons carry presentation-only data-status-tone (success/warning), preserving semantic colors after grouping. No shadows/cards/outer outlines added.

Original form nodes and submit controls remain together; existing confirmation listeners, server routes, permission checks, Edit-return, Close and save/refresh behavior remain. Detail drawer only; Service Add/Edit forms and Package standalone forms excluded.

Checks: ten Node checks passed, including actual group/node/form preservation, repeated initialization, existing archive confirmation and save/refresh. ServiceCatalogConsistencyTest passed six tests/56 assertions. Blade compile/cache clear and touched tracked-file whitespace passed. Asset sources unchanged. Browser desktop/narrow light/dark wrapping, actual focus order and pointer hit testing remain unverified.
