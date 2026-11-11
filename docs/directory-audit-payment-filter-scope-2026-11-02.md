# Directory, Audit and Payment filter scope — 2026-11-02

The requested behavior remains result-only skeletons for tabs/filters, with headers, controls and summary cards visible. The user reported remaining broad loading in Payment Monitoring, Branch Management, User Management and Audit Logs.

Findings and correction:

- Branch/User directory and standalone Audit filters still used ordinary GET navigation. Their forms, pagination and reset links now have an explicit async owner in `resources/js/record-results-navigation.js`. It captures only existing GET controls inside `[data-record-results-page]`, preserves the toolbar/root/Alpine view state and replaces marked results only. It retains server form names/query values, permissions, POST behavior and same-origin boundaries. Back/Forward fetches that region, request cancellation protects newer results, and errors keep the previous content usable. No authenticated prefetch or global fetch monkey patch is used.
- Payment's target resolver used a comma-separated selector list and took the first matching node in DOM order. This selected the ancestor `.pm-panel` before `.pm-records-body`, regardless of selector order. The resolver now explicitly checks visible record bodies/correction results first. Pagination, tabs, filters and summary remain outside the mask. Legacy opacity/pointer blocking on the entire Payment panel and KPI group was removed.
- Card/Table toggles remain local Alpine switches and retain their persisted view. Only the visible results variant receives a loader. Headers, forms, summary nodes and wrappers keep their identity.
- Refreshed Branch actions use delegated listeners. Refreshed User rows receive fresh permission-checked dialogs and idempotent dialog initialization, including cancel/backdrop/close behavior. Audit detail buttons initialize after result updates. Reset links and export URLs follow the server response.

Owners: new record-results-navigation module imported by app.js; explicit result hooks in Branch/User/Audit views; user_information_modals dialog initializer; delegated Branch actions; Audit detail initializer; Payment history CSS and panel-skeleton target resolver. No controller, route, model, schema, filter options or write authorization changed.

Verification:

- Final focused suite: 11 tests / 302 assertions passed across directory/audit region navigation, Payment boundaries, async tabs, real stylesheet navigation, role-specific script rendering and empty results.
- A strengthened follow-up browser run with the actual global layout handler passed 2 tests / 27 assertions, including query submission without a whole-pane fallback, preserved heading/form/summary identities, one visible results loader, error recovery, refreshed User Information content and cancel behavior, and Payment's separate footer/KPI/control boundary.
- All 11 Node lifecycle/profile tests passed. Vite build and Blade compile/cache clear passed.
- Broader AdminScopeAuthorizationTest run had one separate failure in test_service_catalog_filters_category_and_availability_before_pagination at line 325 (server addOns category-filter expectation). It reproduced when run alone. No controller/model change was made by this UI correction; this broader suite is not recorded as fully passed.

Live all-role visual acceptance remains pending. The isolated browser tests exercise actual loader modules and relevant page scripts with controlled server-like responses; they are not a claim of manually exercising every live session.
