# Branch menu lifecycle and softer row hover

Latest request: lighten the row highlight, make hover less abrupt, fix Branch More actions after Edit/Cancel, and remove excess space below Branch grid-card actions.

- Shared individual-filter/row hover token is now #E1EEE6 light / #2D4838 dark. Record rows and cells transition background-color over 240ms ease on entry and exit. The eye retains its separate 180ms glyph animation and original tooltip behavior. Reduced-motion preference disables the row fade. No border/shadow is added.
- Branch Edit loading retains the existing form extraction, parent-bound name/address validation, discard registration, Cancel/Close and existing request route. It no longer appends/replays every script from the fetched full page, which could register shell/menu handlers again. Shared More actions initialization is also guarded once per document.
- Branch grid aligns cards at the top and removes the old 20rem minimum height. Cards size to their content; action-footer vertical padding is 10px. Grid columns, labels, records and actions stay intact.

Owners: `resources/views/partials/record_surface_styles.blade.php`, `resources/views/admin/branches/index.blade.php`, `resources/js/app.js`.

Branch regression: six Edit/Cancel cycles across Grid/light desktop and Table/dark narrow fixtures, checking menu reopen/toggle, no fetched shell-script replay, edit-request count, and no large empty area below actions. Passed 44 assertions. Actual More/Edit menu clicks are exercised; Cancel uses its existing DOM click handler to avoid throttled headless modal animation frames. This does not claim full live form-submission coverage.

Build, JavaScript syntax, Blade compile/clear and diff checks passed. The role-rendered hover and surface suites are run separately to verify both themes/widths, hover end states and configured transition duration, stable geometry and preserved eye behavior. Final hover/surface regressions passed: 2 tests / 1,997 assertions. With the Branch lifecycle regression: 3 tests / 2,041 assertions. Screenshots confirm the lighter row fill; row-transition end states are settled before geometry checks, so this verifies configuration/end states rather than live frame cadence. This supersedes the darker immediate row highlight and stretched Branch card minimum height.
