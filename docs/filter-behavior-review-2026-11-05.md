# Filter behavior review — 2026-11-05

Scope: Case Records, Master Case Records, Payment Monitoring, Branch Management and User Management. Existing role scope, filter choices, query names and manual Apply behavior are preserved.

Source findings and corrections:

- `resources/js/app.js`: toolbar initialization could register the same listeners again on an existing form during UI resets. Initialization is now guarded per form. Shared custom select menus now support arrow keys, Home/End and Escape with focus returned to the select.
- `resources/js/case-record-filters.js`: the outside-click handler treated detached custom-date controls as outside the Filter panel, closing the parent workspace. Those controls are now recognized as part of the filter interaction. Escape dismisses a child dropdown/date control before the parent filter.
- `resources/js/record-results-navigation.js`: asynchronous directory updates synchronized existing fields but left absent hidden scope fields behind. Clearing Assignment/Admin scope could therefore visually clear the chip while a later Apply silently restored that scope. Removed fields are now removed and incoming hidden fields restored.
- `resources/js/worklist-controls.js`: after a results update, the applied state now comes from the server-rendered form, preserving its defaults and reconciled scope fields for later search/sort requests.

Applicability: shared dropdown interaction applies to all five pages; detached case dates apply to Case/Master Records; hidden scope reconciliation applies to the directory pages. No redesign or new filter options were introduced. Payment Monitoring retains its existing pending-filter Apply implementation.

Verification: JavaScript syntax checks, production asset build and Artisan view compilation passed; compiled views were cleared afterward. PHPUnit required execution outside the sandbox to read its bootstrap. The isolated browser regression `test_cleared_directory_scope_is_not_restored_by_later_apply` passed (6 assertions). The broader directory/audit suite stopped at a dark-theme search-background assertion (expected dark green, actual light green) before its filter behavior checks. Full browser interaction, role permutations and theme/responsive verification remain incomplete.
