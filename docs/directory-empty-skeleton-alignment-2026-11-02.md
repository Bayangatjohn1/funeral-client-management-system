# Directory empty states and skeleton alignment — 2026-11-02

Scope: existing Grid/Table views in Branch Management and User Management. The empty Grid markup was not recognized as a complete measurable item, so only its heading could be masked while its description/icon/Add action remained visible. Empty tables and conditional pagination also reserved different space. Finally, a pending filter's loader was tied only to whichever view was visible at request start.

Changes:

- Shared `partials/directory_empty_states.blade.php` reserves `clamp(20rem,40vh,30rem)` for the data area, reduced to 18rem at 767px and below. Grid empty panels and table scrollers share that footprint. Empty table height accounts for its actual wrapper content box rather than assuming a header size. Its message stays centered within the visible horizontal scroller using an inline-size container/sticky message. Headers and all columns remain.
- Both Grid and Table keep the existing shared paginator in a stable footer for empty, single-page and multi-page results. Zero results use the already-approved `0 records`/disabled-control state. Existing Add Branch/Add User actions and permissions remain.
- Empty Grid panels have an explicit `data-skeleton-empty` hook. The measured renderer masks the whole panel and projects the real icon, text and action footprints, so no underlying empty-state description/button leaks through a partial heading mask. Both themes use existing semantic colors and radii.
- `record-results-navigation.js` tracks visible result regions for the lifetime of a pending request. Resize plus explicit `style`/`hidden` observation moves the loading lease when Grid/Table changes. It releases the hidden view, observes only relevant view attributes, and disconnects/release-cleans on success, error or cancellation. Views and headers remain usable; no minimum request duration is imposed.

Owners: Branch/User index views; the new directory_empty_states partial; skeleton-components.js; record-results-navigation.js. No route, controller, model, permission, write action or filter-option changes.

Verification: targeted directory empty-state browser/render checks passed 2 tests / 42 assertions. Final regression suite passed 11 tests / 318 assertions, covering existing loading/filter/tab behaviors, role rendering, zero-result footer structure, complete empty-state masks, equal Grid/Table footer position, pending view switches, 390px dark layout, visible mobile table messages and cleanup. All 11 Node lifecycle/profile tests, Vite build, and Blade compile/cache clear passed. Dark mobile loading/ready fixture screenshots were inspected for the shared directory empty panel. Tests are isolated and do not constitute manual live-page acceptance of every role/theme combination.
