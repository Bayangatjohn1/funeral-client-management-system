# Current record search component

This is the current component contract for Payment Monitoring, Case Records, Master Case Records, Owner case history and Service Management. When asked to apply the current search bar to one of these consumers, review and implement the complete applicable UI and interaction contract. A width-only change does not satisfy that request unless the user explicitly limits scope to width.

## UI

- Soft search surface: light #EAF2ED, dark #182D22; one border, 14px outer corners, transparent inner input; 14px/400 input text. Pointer hover strengthens only the frame border (light #7FA98E, dark #527461); active/focus-within uses the existing green focus token. No fill jump, movement, glow or geometry change.
- Placeholder lists supported information types without starting with Search. Case/Master/Owner: "Case ID, client or deceased name". Payment: "Case ID, name, Payment Record No. or reference". Preserve the accessible Search label and visible Search button; placeholder is a hint, not the field label. Do not advertise unsupported query fields.
- Integrated, visible Search button, 84px wide at the trailing edge. Input reserves 128px for Search and the separate clear X; X sits 92px from the right. Current implemented palette following the request to avoid dark-green main fills (2026-11-05): Case/Master/Owner records, Payment, Service and Branch/User directories share medium-sage action tokens: default --reference-action-bg (#A9C9B5), hover/pressed --reference-action-hover (#92B89F), text --reference-action-text (#123524) in both themes, with no movement or background transition. Earlier dark-green main fills and the older divergent case palette are superseded. Shared ownership: partials/shared_filter_ui; the case adapter in partials/case_records_layout_styles consumes the same tokens. Verified by WorklistAdoptionTest::test_service_reference_search_actions_and_case_tabs (174 assertions, isolated role renders at 1440/390px, light/dark). Search behavior and field geometry are unchanged.
- At 1201px and above, cap the visible search field at min(560px, 100% - 24px) inside the existing flexible grid track. Do not shrink that track or move Filter, Sort or Cases per page. Below 1201px preserve responsive width/wrapping.
- Match popup follows the field width: 6px padding, 10px corners, at most 280px scrollable height. Hide it when empty/dismissed.
- Record matches are one row: Case ID · Client name · Deceased name, with 10px gaps. Case ID is 14px/600; names 13px/400. No branch/status, instructional headings, duplicate Search action or stacked field labels.
- Names may truncate with ellipsis; preserve full names in accessible label/title. Underline all case-insensitive query matches using a 1px underline with 3px offset, inherited color/weight and transparent background. Construct text/mark nodes safely.

## Interaction

- Typing shows immediate matching loaded records; after 300ms, request matching records through the existing authenticated, filtered GET route. Use a minimum of two characters and at most five suggestions.
- Suggestions can find records beyond the current page. Use applied filter state, not unfinished filter-panel selections. Preserve role/branch scope and the page's existing searchable fields; do not copy Payment reference-number semantics into Case queries without backend support.
- Suggestion requests use a separate abort controller/version guard. They must not replace results, update browser history, show a results skeleton or commit pending filters.
- Clicking a record match searches by its canonical case code. The catalog adapter uses the record name within its existing query-length limit. Search button and Enter submit the trimmed typed query. Reset pagination while preserving applied filters, active tab, sort and page size.
- X clears only search. Clear filters preserves search. Retain existing authorization and filter/reset conventions; do not widen data access to obtain suggestions.
- Down/Up navigate matches; Enter selects; Escape/outside click dismiss. Keep aria-controls/expanded current and visible keyboard focus.
- Reinitialize the component after async results/tabs refresh. Cancel stale suggestion requests when a form is replaced. Search errors remain in place through the existing case/payment loaders.

## Source owners

| Consumer | UI/styles | Behavior/data |
| --- | --- | --- |
| Payment Monitoring | `resources/views/staff/payments/_toolbar.blade.php`, `resources/views/staff/payments/_consistency_styles.blade.php` | `resources/views/staff/payments/history.blade.php` |
| Case/Master/Owner records | `resources/views/partials/case_filter_toolbar.blade.php`, `resources/views/partials/case_records_layout_styles.blade.php` | `resources/js/app.js` initCaseCompactFilters; `resources/views/partials/case_search_metadata.blade.php` |
| Service Management catalogs | `resources/views/admin/service-management/index.blade.php`, `resources/views/partials/services_visual_styles.blade.php` | `resources/js/catalog-record-search.js`, `resources/views/admin/service-management/partials/search-metadata.blade.php`; existing inline hub loader |
| Staff async search | Existing Case Records view | `resources/views/staff/funeral_cases/index.blade.php` loadCaseRecordSearch |
| Admin/Owner async search | Existing Master/Owner views | `resources/views/partials/master_case_tab_navigation.blade.php` loadMasterCaseSearch |

Payment, case and catalog adapters retain their own backend/query assumptions. Preserve the shared visible contract and test the affected consumers when changing shared JS or styling.

The Payment History correction badge opens the role-specific correction queue through its existing Search field (`correction_q`). Complete PAY record numbers, including branch-prefixed numbers, match exactly. Case IDs and partial payment-number searches retain the queue's existing behavior. If the payment has no PAY number, the badge searches the latest visible request number.

The Staff Request Payment Correction picker in `staff/payments/history.blade.php` searches the eligible transactions already loaded for the selected case. It searches receipt/OR, transaction reference, approval code and payment record number case-insensitively, ranking exact matches first and showing up to five typed matches. The initial list keeps eligible records reachable even without a reference. Results include the client, amount, payment date and posted status. Search/Enter moves focus to the first result. Selecting a result opens the existing correction form for that payment without a second confirmation. Use the existing soft search input, Search and clear controls, with a scrollable inline results list.

## Verification

`tests/Browser/CaseSearchWidthTest.php` uses real role-rendered markup and built assets with isolated SQLite, covering Staff, Branch Admin, System Admin and Owner, both themes and 1440/1920/1100/390px. It checks fixed trailing controls, Search/Enter/X, one-row underlined matches, applied-filter isolation, a simulated remote suggestion response and successful results replacement followed by another search. `tests/Browser/PaymentUiConsistencyTest.php` protects the Payment reference. Builds/Blade checks and relevant async/scope regressions are required; isolated fixtures do not establish full live-session acceptance.




## Service Management adapter — current (2026-11-03)

The six existing catalogs use the same soft14px search frame,14px/400 text, trailing84px Search, separate X and capped560px desktop field. Cap the inner visible field inside the unchanged flexible search track; Filter/Sort and the view switch keep their positions. Below1201px the field uses the existing responsive width. Native search cancellation decoration is hidden so only one X appears.

Placeholders describe real supported fields without Search/e.g. prefixes: Packages "Package name, description, service or freebie"; Caskets "Casket name, material or description"; Hearses "Hearse name or description"; Freebies "Freebie name or unit"; Add-ons "Add-on name, category, unit or description"; Promotions "Promotion name, package or discount". Preserve the accessible Search [catalog] label.

Catalog suggestions are one row: Name · matching detail, not case/client/deceased fields. Name is14px/600, detail13px/400; reserve detail space by capping a non-single name at65% and use ellipses with the full accessible label/title. Underline every literal case-insensitive occurrence through text/mark nodes. Metadata contains only existing server-search fields: package name/description/coffin type/inclusions/freebies; casket name/material/description; add-on name/category/unit/description; freebie name/unit; hearse name/description; promotion name/assigned package/discount type/value. Reuse the same metadata for card/table rows and deduplicate by model/id; do not search status text or advertise case/payment IDs.

Typing provides immediate loaded matches and300ms authenticated GET suggestions (minimum2 characters, max5 rows) without replacing results, changing history or showing a results skeleton. Remote matching reuses the existing catalog route and active filters/tab/sort, resets pagination, and reads only the returned active catalog. A separate abort/version guard discards stale/detached responses. Suggestions never execute fetched scripts or broaden access.

Search/Enter applies the trimmed query; X clears q only and preserves current catalog fields. Selecting a match submits its name, bounded to the existing100-character backend q limit; full names stay accessible. Empty/unnamed records remain discoverable through the existing server search but do not invent a canonical suggestion identity. Escape/outside/focus departure dismisses matches; Up/Down/Enter support keyboard selection. Explicit search submissions focus the input so the existing loader can preserve search focus through replacement when the user has not moved elsewhere. The former450ms auto-results reload and Escape-clears-query behavior are superseded. Catalog status/sort/reset workflows remain their existing immediate server submissions; do not copy the record pages' manual-Apply workflow into catalogs.

`resources/js/catalog-record-search.js` is the only search/suggestion adapter. `resources/views/admin/service-management/index.blade.php` remains the sole hub-results loader; it cancels suggestion work at navigation start and guards committed results with request version, abort state and hub connectivity. Rebind the search adapter on panel-ui:reset after replacement and abort detached bindings. Preserve read-only Branch Admin and System Admin writes, modal hooks and Grid/Table preferences.

`tests/Browser/CatalogSearchTest.php` uses isolated role-rendered Blade and the built app for all six catalogs, Branch/System roles, light/dark at1440/1920/1100/390px, stable trailing controls, safe one-row underlined matches, server-rendered off-page responses, Search/typed Enter/X, keyboard selection, hub replacement and out-of-order committed-response recovery. Screenshots inspect light desktop and dark narrow matches. This is fixture evidence, not blanket live-session acceptance.


## Branch/User directories (2026-11-03)

The complete component also applies to Branch Management and System/Branch User Management. Use [directory adoption](worklist-adoption.md) for source owners, supported placeholders and metadata. `resources/js/worklist-controls.js` is their adapter; suggestions use the current role-authenticated existing GET route and committed-filter snapshot, never pending selections. The results loader owns only result regions and a separate footer refresh. Audit Logs also uses this adapter for authorized user-name, action, and event-ID matching. See the current decision ledger for Audit behavior and verification.


Current surface revision (2026-11-05): on the six approved worklists, record_surface_styles owns the search-field fill (#EAF2ED light / #22392C dark) and transparent default border; toolbar surface is #F7FAF8 / #15251C. Main Search button retains the medium-sage palette and existing geometry/behavior. No shadow/glow. Keyboard focus remains a visible 2px outline. This supersedes default framed search surfaces there. Role-rendered surface/filter/focus verification passed 1,260 assertions; current scope and palette are in the decision ledger.

Expanded flat surface scope: native Sort/Cases per page selects and their option popovers, inner filter fields, popup header/footer/Apply, date panels and pagination now consume the same updated tokens. Transparent borders and no shadows apply to these defaults; keyboard focus remains visible. See the current decision ledger for the 1,865-assertion verification and modal scope limits.

## Staff intake Add-ons popover adapter (2026-11-07)

Scoped local adapter in resources/views/staff/intake/_form.blade.php and _interaction-styles.blade.php. Reuse the soft search frame, medium-sage 84px Search action, separate X, supported-field placeholder, safe underlined one-row suggestions (minimum 2 characters, maximum 5), Up/Down/Enter, Escape/outside/focus dismissal. Search/Enter commits local filtering; X clears search while preserving pending selections. Do not submit the intake form or navigate. The complete role-authorized catalog is already loaded, so remote suggestions, backend pagination and record query routes are not applicable. The anchored non-modal popover header contains suggestions above the independently scrolling cards; its picker is positioned against Choose/Change Add-ons and closes on ancestor scroll or resize. Fixture checks cover 30 cards, both themes and 1440/390px; full live-session acceptance remains pending. This adapter does not change existing worklist search behavior.
