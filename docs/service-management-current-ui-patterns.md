# Service Management: Current UI Patterns (Source-Verified)

**Audited:** 2026-11-01  
**Purpose:** Record what the current Laravel implementation actually does so future page work can reuse the real patterns. This describes source code, not a proposed redesign.

## Scope and verification limits

This is a read-only source review of Service Management, the catalog Add/Edit forms, and their shared JavaScript and Blade components. No application code or records were changed for this document. No browser interaction, screenshots, automated tests, or contrast measurements were performed for this audit; rendered behavior that depends on a browser is identified as source behavior rather than claimed as visually verified.

The working tree already contains many unrelated modifications. This document was added without replacing those files.

## 1. Service Management page structure

`resources/views/admin/service-management/index.blade.php` is the Service Management hub. It serves the Packages, Caskets, Hearses, Freebies, Add-ons, and Promotions tabs. It contains the search/filter/sort form, Grid/Table controls, table/card result markup, and a modal dialog that loads catalog forms and detail pages in an iframe.

The hub uses asynchronous, same-origin navigation for tab and toolbar requests. The page-specific hub script fetches the requested section, replaces the hub content, reinitializes the filter/sort behavior, and updates browser history. It aborts an earlier request when a later request supersedes it. The Grid/Table selection is stored per catalog tab in local storage (`service-catalog-view-${activeCatalog}`), so a view choice is kept separately for each tab.

On successful form submission, the iframe notifies the parent hub; the hub closes the modal, refreshes the catalog content, and displays a short save toast. This is the source-defined flow; it was not exercised in a browser during this audit.

The old generic service-management initializer in `resources/js/app.js` returns when it detects the page's server-navigation marker. For this page, the active navigation behavior is the page-specific hub script in `index.blade.php`, not that fallback initializer.

## 2. Search, Filter, Sort, and view controls

### Actual search fields and visible hints

The server-side search query is in `app/Http/Controllers/Admin/ServiceManagementController.php`. The current placeholders in the hub are:

| Tab | Visible placeholder | Fields actually searched by the controller |
| --- | --- | --- |
| Packages | `e.g., package name, description, included service, or freebie` | Package name, short description, coffin type, saved inclusion names, and package freebie names |
| Caskets | `e.g., casket name, material/type, or description` | Name, material/type, and description |
| Hearses | `e.g., hearse name or description` | Name and description |
| Freebies | `e.g., freebie name or unit` | Name and default unit |
| Add-ons | `e.g., add-on name, category, unit, or description` | Name, category, unit, and description |
| Promotions | `Search promotion or assigned package…` | Promotion name, discount type/value, and assigned package name |

The controller search is real server-side filtering, not just placeholder text. Search terms are combined with the active status/date/category filters and sort query.

### Filter and sort interaction

The initial toolbar form in `index.blade.php` keeps native selects and query names as the source of filter options and values. `resources/js/catalog-filter-sort.js` progressively converts those existing selects into compact Filter and Sort menus:

- Filter button label is `Filter`; its active-count badge is hidden at zero.
- Filter menu heading is `Filter by`; each existing select becomes a separately named fieldset of its actual options.
- Sort button is `Sort` at the select's default value; otherwise it displays the selected option's configured display label.
- Sort menu heading is `Sort by`.
- Applied filters appear below the toolbar as removable `Type: Value` chips. `Clear All` appears only when a filter is active.
- Removing one chip clears only its associated selection; Clear All resets each available filter select.
- Filter/sort navigation preserves other query parameters, drops pagination keys so results restart on the first page, and retains the selected query values.
- On the Service Management hub, changes dispatch an event to its asynchronous navigation path instead of assigning a new page URL directly.

The Grid/Table segmented toggle is in the hub toolbar but separate from the generated filter chips. Both result presentations use the same server-filtered/sorted response; toggling only switches the existing cards/table panels. View persistence is per tab.

### Current per-tab filter options and semantics

The UI options come from the Blade selects and the corresponding controller query. The current package controls include Package Status and Promotion Status; the other catalogs use their available status/availability controls. Promotions have their own status choices. The filter control is not identical across every tab because the available data and query parameters differ.

Package Status currently supports All, Active, Active with Unavailable Items, and Archived. `Active with Unavailable Items` is a derived query condition: active packages whose required linked casket, hearse, or freebie is unavailable. Optional add-ons are excluded. It does not overwrite the package's saved Active/Archived state. Promotion Status is independent. `No Promotion` is an assignment state shown for packages without a promotion; it is not a Promotion catalog row.

Catalog status behavior in `ServiceManagementController::applyCatalogStatus()` is:

- All: no status restriction.
- Available: active item with availability enabled.
- Unavailable: active item with availability disabled.
- Archived: inactive item, regardless of its former availability.

The controller retains compatibility handling for older query parameters such as the retired availability and package catalog-dependency filters. Those compatibility parameters are backend/query compatibility, not separate current toolbar categories.

### Sort options

The controller applies sorting to the actual result query. Standard catalogs support the configured name ascending/descending and newest/oldest order. Promotions also have discount amount and start/end date ordering. The UI's option values and labels are defined by the page; `catalog-filter-sort.js` changes presentation but does not invent sort keys.

## 3. Add/Edit forms and validation

Catalog forms are rendered in modal-frame context when opened from the Service Management hub, using the existing form views rather than a second copy of the catalog page. The relevant files are:

- Packages: `resources/views/admin/packages/_form.blade.php`
- Caskets: `resources/views/admin/casket-catalogs/_form.blade.php`
- Hearses: `resources/views/admin/hearse-catalogs/_form.blade.php`
- Freebies: `resources/views/admin/freebie-catalogs/_form.blade.php`
- Add-ons: `resources/views/admin/add-on-catalogs/_form.blade.php`
- Promotions: `resources/views/admin/promotions/form.blade.php`
- Modal shell: `resources/views/layouts/modal-frame.blade.php`

Server-side Laravel validation remains authoritative. The form templates render field errors, and several form classes also get client-side inline errors from `resources/js/service-form-feedback.js`. That script prevents the browser's default invalid tooltip, focuses the first visible invalid field, creates an inline field message, and links the input to it with ARIA attributes. It also associates existing server-rendered errors with their fields. Existing rules are not replaced by this script.

There is a real implementation difference: the shared client feedback initializer selects the catalog/package form classes and `#packageForm`; Promotions has its own `#promotionForm` and `resources/js/promotion-form.js`, so it does not use every shared catalog-form client behavior. Server-side promotion field errors still come from the promotion form/controller path.

### Unsaved-change confirmation

`resources/js/form-discard.js` records an initial snapshot for supported forms, including `#packageForm`. It compares current form state against that snapshot and opens a confirmation only if the form changed or arrived with validation errors. The dialog offers Keep Editing and Discard Changes. On confirmed discard it resets the form and clears supported inline error states.

Service forms additionally bind their cancel links in `service-form-feedback.js` and route modal close/cancel through the parent modal guard when embedded. The modal shell supplies the shared discard dialog styling. This describes the guards present in source; browser-level confirmation behavior was not tested in this documentation pass.

### Package conditional fields

The Package form has additional inline configuration logic for selected services and includes a form snapshot/discard integration. Some package text normalization logic is also present inline in the Blade view in addition to the shared text-assistance module; this is a duplicate implementation path to be aware of when maintaining capitalization behavior.

## 4. Text capitalization and spelling assistance

`resources/js/app.js` imports `catalog-text-assistance.js`, `service-form-feedback.js`, `form-discard.js`, `service-confirm.js`, and `catalog-filter-sort.js`.

`resources/js/catalog-text-assistance.js` uses `data-text-case="words"` or `"sentences"`. It records the original value and only normalizes fields changed by the user; normalization occurs on blur or form submission, not while each character is typed. Its Unicode-aware logic capitalizes the first character of words/sentences and tries to preserve intentional mixed case.

Proper/catalog names are generally marked with `autocapitalize="words"`, `autocorrect="off"`, and `spellcheck="false"` so the browser does not silently “correct” names. Descriptions are marked for sentence capitalization and native spellcheck/autocorrection in forms where those attributes are present. This is text capitalization plus browser-native assistance for selected natural-language fields; it is not a dictionary-based custom spell-correction feature.

### Current coverage is not completely uniform

| Form | Source-defined behavior |
| --- | --- |
| Package | Name and configured freebie names use word mode. Description uses sentence mode and native spellcheck/autocorrect. The Blade also contains an inline normalization path. |
| Promotion | Promotion name uses word mode; proper-name spellcheck/autocorrect are disabled. Other main fields are date, select, or numeric fields. |
| Casket | Name/material use word mode. Name disables correction/spellcheck; material's spellcheck is enabled. Description uses sentence mode with spellcheck/autocorrect. |
| Hearse | Name uses word mode with correction/spellcheck disabled. Description uses sentence mode with spellcheck/autocorrect. |
| Freebie | Name uses word mode with correction/spellcheck disabled. Unit is not marked for the same normalization behavior. |
| Add-on | Name uses word mode with correction/spellcheck disabled. Description uses sentence mode with spellcheck/autocorrect. Category and unit are not necessarily normalized as names. |

Therefore the accurate rule is “shared approach with field-specific opt-in,” not “every text input on every form is automatically capitalized or spell-corrected.” Saved values are not rewritten merely by opening Edit.

## 5. Confirmation, status feedback, and save feedback

### Catalog status actions

`resources/js/service-confirm.js` handles status mutation forms within the Service Management hub, except forms marked `data-promotion-confirm`. It fills a shared confirmation dialog with the action and item name. When dependencies block an archive, it shows the server-provided block message and related package links instead of allowing confirmation. `resources/views/admin/service-management/partials/reference-ui.blade.php` supplies the shared confirmation dialog markup and styling.

The View Details status-action partial (`resources/views/admin/service-management/partials/status-actions.blade.php`) also contains contextual status dialogs for the relevant item actions. The action remains subject to the existing server-side route and authorization rules.

Promotions intentionally use a separate confirmation path: the hub contains a promotion-specific dialog and script, and promotion forms are explicitly excluded from the generic status-confirm handler. Thus promotion Turn Off/Turn On confirmation is not literally the same event handler as catalog Available/Unavailable/Archive/Restore.

### Form and status feedback

`resources/views/admin/service-management/partials/status-feedback.blade.php` renders status feedback with status/alert semantics. Successful modal form saves are sent to the parent hub, which refreshes the current list and displays a short toast. Validation stays beside fields in the form; it is separate from catalog lifecycle status badges.

## 6. Source map

| Concern | Main source |
| --- | --- |
| Hub tabs, page toolbar, view panels, iframe modal, async refresh, save toast | `resources/views/admin/service-management/index.blade.php` |
| Search/filter/sort queries, compatibility parameters, pagination, availability and package dependency rules | `app/Http/Controllers/Admin/ServiceManagementController.php` |
| Filter/sort menus, active chips, clear-all and query navigation | `resources/js/catalog-filter-sort.js`, `resources/css/catalog-filter-sort.css` |
| Text normalization on edited fields | `resources/js/catalog-text-assistance.js` |
| Shared catalog form client validation and guarded cancel | `resources/js/service-form-feedback.js` |
| Form snapshot and discard confirmation | `resources/js/form-discard.js`, `resources/views/layouts/modal-frame.blade.php` |
| Catalog lifecycle confirmation | `resources/js/service-confirm.js`, `resources/views/admin/service-management/partials/reference-ui.blade.php` |
| Promotion confirmation/action presentation | `resources/views/admin/service-management/index.blade.php`, `resources/views/admin/service-management/_promotion-actions.blade.php` |
| Service Management asset imports | `resources/js/app.js` |

## 7. Reuse guidance for other system pages

For future pages, reuse only the piece that matches the page's data contract:

1. Keep the page's existing server-side filter/sort query and native select names as the behavior source; use `catalog-filter-sort.js` only where its toolbar markup contract applies.
2. Use the actual existing selects to define filter categories and choices. Do not copy catalog status options onto unrelated records.
3. Keep search fields and their placeholder examples aligned with the server-side query columns/relations.
4. Reuse the form discard guard and inline validation pattern only for forms registered by those scripts; add form selectors deliberately if a new form should participate.
5. Mark proper names and natural-language descriptions separately for capitalization/spellcheck behavior. Do not turn on native autocorrection for names, codes, emails, or numeric data.
6. Preserve the promotion-specific confirmation path unless intentionally unifying it as a separate approved change.

## Current-code caveats

- This report is grounded in the checked-in working-tree source, which currently has substantial modifications. It describes what is in this checkout, not a claim about another deployed installation.
- Search placeholder wording does not always list every searched column; notably package search also searches coffin type, and promotion search also includes discount type/value.
- Some older query parameter handling remains for compatibility even though the modern toolbar does not expose those old categories.
- Package capitalization has an inline Blade implementation as well as the imported shared helper. Future edits should avoid creating a third normalizer or assuming one path covers every field.
- No runtime, browser, accessibility contrast, or automated test verification was performed as part of creating this reference.
