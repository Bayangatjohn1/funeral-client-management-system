# Flat worklist surfaces

## Current Branch KPI choice

The user chose two existing-size cards: Active Branches / total and Inactive Branches, replacing Cases This Month. Inactive count uses the already-provided total minus active counts. Active remains success green; inactive uses warning amber, including its building-slash icon. The inactive card links to the existing status=inactive list, clearing page/search/highlight scope so the full inactive list is shown. The active card retains its existing destination. Supporting copy describes intake/assignment availability rather than monthly performance. The connected summary, status filter and card dimensions remain unchanged. No controller, database, route or business logic changed.

Owners: Branch index KPI markup and the shared Branch icon tone rule. Blade compile/clear and whitespace checks passed; the existing directory scope/render regression passed 1 test / 28 assertions. Browser visual verification was not run after the previously declined browser permission.

## Current chevrons and Case date icon

Payment Monitoring is now the chevron reference for main Filter/Sort controls on the six worklists: one independent rotate property, 0deg closed / 180deg open, 160ms ease, with transform reset. Rotation follows the actual details open attribute, wrapper is-open class, or Case Filter aria-expanded state. Translation stays separate for vertically centered absolute arrows; Service no longer combines its old transform rotation with the shared rotate state. Reduced-motion disables the transition. Case date-preset chevrons use the same contract.

The existing Case/Master date icon now renders as a single funnel in the markup. It sits at z-index 2 above the transparent date select (z-index 1), with 40px left text padding and pointer-transparent icon/chevron layers. This prevents the updated select fill from covering the icon and avoids adding a second icon during shared-control initialization. Dates, options and custom-range interactions remain unchanged.

Owners: `partials/record_surface_styles`, `partials/record_filter_sort_states`, `partials/case_date_filter_control`. Blade compile/clear, PHP syntax and whitespace checks passed. Browser assertions cover closed/open/reset angles, transform ownership, duration and the date icon when the main Filter is open, but were not run because browser execution permission was previously declined. This revision remains implemented but not browser-verified; no new asset build was needed for these Blade-only changes.

## Current heading gaps and shared Sort popup

The main Filter heading row now has zero bottom padding, and its existing field body starts with a single 12px top inset. This replaces the doubled header/body padding before the first filter. Native Sort headings use a separate shared rule: no old heading padding/border, a 12px margin before the first option, and Service-derived title typography. Payment's Sort heading was previously outside the Case-only heading styles and therefore appeared cramped.

Branch/User Sort now receive the same generated “Sort by” heading as Case/Payment Sort. Their popup is right-aligned, responsive up to 280px, uses shared selected/check colors and 16px icon slots, and the transparent native select covers the full existing trigger. Options, labels, values, input/change events and navigation handlers remain. Service's existing Sort panel uses the same width cap; manual Apply behavior and hidden-menu display ownership are preserved.

Owners: `resources/js/app.js`, `resources/views/partials/record_surface_styles.blade.php`. The new heading condition is restricted to Directory worklist sorts; unrelated worklists are untouched. Build, JavaScript/PHP syntax, Blade compile/clear and whitespace checks passed. Three Node tests execute the actual menu renderer for Case, Payment and Directory wrappers, checking the heading, retained option values, selected state, input/change dispatch and request to close on selection. Browser gap assertions were updated but not run because browser execution permission was previously declined. Responsive visual verification remains pending.

## Current dropdown spacing from Service Management

The six worklists now share Service-derived dropdown spacing: .9rem panel/body/header/footer padding, .7rem field separation, .55rem label/control and option icon/text gaps, and 4px between options. Option padding matches the catalog's .4rem/.5rem values while existing row minimum heights remain. Native Sort and individual filter choice menus use the same outer inset as the Service panels.

Case/Directory fields use one gap between label and control. Payment's labels and controls are siblings, so their margins explicitly own the .55rem label-to-control gap and .7rem next-field gap; the previous flex gap plus label margin is removed. Field grids align at the start rather than distributing spare panel height. Existing scroll/max-height rules, headers, close buttons, manual Apply/Clear footers, selection/hover states and hidden-menu ownership remain. No dropdown display rule, event, field, option or filter submission behavior changed.

Owner: `resources/views/partials/record_surface_styles.blade.php`. Source and prior Service open-filter screenshot compared. Blade compile/clear, PHP syntax and touched-file whitespace checks passed. Existing browser assertions now measure the 4px option-row gap but were not executed because browser permission was previously declined. This spacing revision is implemented but not browser-verified.

## Current Filter/Sort placement after toolbar actions

Branch/User/Payment toolbars now use explicit grid areas instead of relying on implicit placement and conflicting old spans. Both Filter and Sort have matching 160px desktop tracks, 44px height and width constrained to their track. This fixes Payment's legacy 160px controls overflowing the previous 136px slots. Directory Sort reserves space for its chevron so its existing ellipsis does not paint under the icon.

Above 1280px, order is Search, Filter, Sort, view/page size, action. At 901–1280px, Search spans the first row and the other controls occupy one ordered row. At 601–900px, Filter/Sort stay paired below Search and view/page size shares the next row with the action. At 600px and below, Filter/Sort remain paired while view/page size and action receive separate rows. Internal gaps are 12px; applied chips use an implicit full-width row only when visible. No fields, menus, handlers, labels or functionality changed.

Owner: `resources/views/partials/record_surface_styles.blade.php`. Existing browser assertions were extended to check matching Filter/Sort geometry and no action overlap, but were not run because browser execution permission was previously declined. Blade compile/clear, PHP syntax and whitespace checks passed. This is implemented but not browser-verified; prior render/interaction test results remain historical evidence for the unchanged controls.

## Current primary action placement

Branch Add Branch and User Add User now sit at the end of the toolbar after the Grid/Table toggle. Admin Payment Print Preview sits after Cases per page; Staff retains its existing Record Payment action at the same toolbar position, without introducing Print Preview to that role. Branch correction requests still have no print action. Existing labels, IDs, destinations, query-preserving return links and System Admin role-dialog attributes remain. Service Add placement is unchanged.

At desktop, the toolbar has five tracks. At tablet widths, search spans the row and the action aligns at the right of the last control row. At narrow widths, the action occupies its own row. The existing sage action palette, 44px minimum height and visible keyboard focus are shared; old Payment blue rules cannot repaint the moved button. No extra primary button is added to the header.

Staff Payment toolbar replacement required delegating the existing modal trigger to document clicks and returning focus to the current trigger after close. Modal loading, preselection, automatic opening and submission remain unchanged. The actual modal script was tested through three replaced-trigger open/close cycles using a DOM stub; no browser was launched.

Owners: Branch/User index action sections, `partials/directory_toolbar`, Payment history action section, `staff/payments/_toolbar`, `staff/payments/_record_modal`, and `partials/record_surface_styles`. Existing directory/payment feature suites passed 8 tests / 244 assertions, including six role/page render checks for one toolbar action and preserved role-specific choices. The modal script regression passed 1 Node test. Blade compile/clear, PHP syntax and whitespace checks passed. Browser placement assertions were updated but not run because browser execution permission was previously declined; responsive visual acceptance is pending. Assets unchanged.

## Current Service header reference and KPI number colors

Compared Service Management's existing source and prior verified screenshot. All six worklists retain the same sage header fill (#D5E4D9 light / #1C3025 dark). Headers now share 16px padding and 20px corners; connected bottom corners remain square. Service's old 32px top padding is normalized to the other headers' 12px top gap. Its existing heading divider now consumes --rs-divider with 16px heading/nav spacing, matching the current inset divider used by the other connected headers. Existing section/action placement is preserved.

KPI values use shared semantic colors: neutral counts #123524 / #E7F0E9; active branches/users and collected amounts #166534 / #4ADE80; inactive users and outstanding balances #92400E / #FBBF24. Existing bad/error amounts retain their previous treatment. Branch/User cards receive presentation-only tone attributes; Payment reuses its existing good/warn classes. Content, figures, routes, links and permissions remain unchanged.

Owners: `resources/views/partials/record_surface_styles.blade.php`, Branch/User index tone attributes, and the updated existing browser assertions. Blade compile/clear, PHP syntax and touched-file whitespace checks passed. Calculated token contrast on the intended KPI surfaces is at least 6.75:1 light / 9.18:1 dark. Browser tests were not rerun because browser execution permission was previously declined; these changes remain implemented but not browser-verified. Prior screenshots establish the reference only.

## Current inset divider and content spacing

The header-to-tabs/KPI line is now a decorative 1px divider inset 16px from both container edges. Its width follows the full content area even when tab labels occupy only a short row. The attached tabs or KPI cards start 16px below the line. Branch Admin Payment section navigation receives that top spacing once; the following KPI grid retains its existing 16px separation from the navigation, avoiding a doubled gap. Shared header backgrounds, connected edges, KPI sizes and outer section gaps remain.

Owner: `resources/views/partials/record_surface_styles.blade.php`. The previous edge-to-edge header border is replaced by a pointer-transparent header pseudo-element using the existing `--rs-divider` token. Blade compile/clear, PHP syntax and touched-file whitespace checks passed. Browser assertions were updated for the inset line and measured content gap, but the browser recheck was not run because its permission request was declined. This revision is implemented but not browser-verified; earlier browser totals do not cover it.

## Current toolbar/table connection (2026-11-05)

The toolbar and table now touch as one continuous surface on Case Records, Master Case Records, Payment Monitoring, Branch Management, User Management and Service Management. Toolbar top corners and table bottom corners use 12px radius; the shared edge is square with no gap, stroke or shadow. Sticky toolbars and popovers keep their existing overflow/stacking behavior. Directory and Service joins also extend to Grid following the latest approval below; individual cards remain spaced apart. Service Add stays in its section intro. Pagination remains outside the horizontal table scroller.

Column-header backgrounds are transparent on Case/Master Records and, following explicit user approval, Branch/User/Service Management in both themes. Payment's existing column-header treatment is preserved.

Owner: `resources/views/partials/record_surface_styles.blade.php`. The existing role-rendered surface suite passed 1,847 assertions across 12 role/page combinations, light/dark at 1440/390px. It measures touching edges and aligned widths, checks transparent headers, and exercises opened Filter/Sort/page-size surfaces. Screenshots of Service desktop, Case desktop and Branch dark narrow were inspected. Existing worklist feature checks passed 6 tests / 85 assertions; build and Blade compile/clear passed. Full live record mutation paths were not exercised. Repository-wide diff checking reports pre-existing whitespace issues outside these edits.

User-approved scope: Case Records, Branch/System/Owner Master Case Records, Payment Monitoring, Branch Management, User Management and Service Management. Headers, existing KPIs, toolbars and their controls use complementary green surfaces without default container outlines, shadows or glow. No KPIs or controls are added. Layout, labels, routes, permissions, filters, tabs, record actions and loading behavior are preserved.

## Current palette

| Purpose | Light | Dark |
| --- | --- | --- |
| Page | #E6EEE8 | #0D1712 |
| Header | #D5E4D9 | #1C3025 |
| KPI / toolbar / filter panel | #F7FAF8 | #15251C |
| Search / Filter / Sort / view toggle | #EAF2ED | #22392C |
| Control hover / open | #D8E7DD | #2D4938 |
| Primary text | #123524 | #E7F0E9 |
| Metadata | #617568 | #A7BCAE |
| Header description | #4D6555 | #A7BCAE |

Main actions and selected tabs retain the approved medium-sage palette. Row hover uses the shared individual-filter background; keyboard focus remains outlined. View glyph scale, eye-only tooltips and Payment spacing remain governed by the existing interaction styles. Keyboard focus retains a visible 2px outline; removing default borders does not remove focus feedback. KPI semantic value/icon colors are preserved. Other record-card borders and table dividers are outside the outline-removal scope, but record-card/menu shadows are removed.

## Implementation

`resources/views/partials/record_surface_styles.blade.php` owns the scoped surface tokens and presentation. `layouts/panel.blade.php` enables `record-surface-ui` only for the existing list routes and includes the skin after legacy filter styles. It does not apply to dashboard, report, create/edit, print or guest pages. An enabled body class scopes all selectors; existing role classes are retained without assigning one role's class to another.

Explicit background-color declarations and disabled surface/border transitions resolve the legacy background shorthand/cascade. Transparent border colors preserve existing control/container dimensions. The outer Case toolbar owns the light surface; its nested controls wrapper stays transparent. No new stylesheet import or JavaScript behavior is required. The existing asset build still completes successfully.

Applicable: default border/shadow removal and purpose-based surface colors. Already consistent: existing action palette, control sizing, icon system and row interaction. Not applicable: new KPIs, new filters, changed table/card layout or backend behavior.

## Verification

`WorklistAdoptionTest::test_record_headers_kpis_and_toolbars_use_flat_surfaces` uses isolated role-rendered HTML and the built bundle. It checks page/header/KPI/toolbar/control colors, transparent borders, no shadows, open filter surfaces and keyboard focus at 1440/390px in both themes across 12 authorized role/page combinations. Final surface verification passed: 1 test / 1,260 assertions. Existing action/tab and row/eye regressions also run to check interaction preservation. Screenshot review includes light desktop Branch/User/Case pages and dark mobile Service Management. Build and Blade compilation passed; compiled views are cleared after validation. This verifies the exercised fixtures, not every live-session/data permutation.

This revision supersedes earlier outlined header/KPI/toolbar/search/filter designs on the selected pages. The no-shadow constraint remains in force.

## Panel contrast and Case Sort revision

Case Records and Master Case Records column-header elements now have transparent backgrounds; the separate colored header band is removed on those pages only.

Main Filter panels (including their scrolling body and footer) use `--rs-panel`: #D5E4D9 light / #1C3025 dark. Open option menus, Sort select menus and Cases per page popovers use `--rs-menu`: #F0F6F2 light / #304C3B dark. Fields retain the control shade. This supersedes the previous identical panel/table-surface fills: layered backgrounds now provide the distinction without default borders or shadows.

Case Sort has one painted wrapper and a transparent, absolutely positioned native select covering its full width and height. Leading/trailing icons pass pointer events through to that select. Its old flex sizing (width zero with only the center text clickable) is overridden by the scoped surface owner. No change to values, change events or submission behavior. The wrapper has no border, so it does not reduce the hit target.

Filter, Sort and page-size wrappers use the shared hover/open background. Their inner select becomes transparent in that state to avoid a second painted rectangle. Pointer hover clears inherited outline/border/shadow feedback; keyboard focus continues to use the existing visible focus outline. The row-hover outline and eye animation remain outside these rules.

Owner: `partials/record_surface_styles.blade.php`. The expanded surface browser test covers the Case header background, full hit area at left/center/right of Sort, transparent inner control, background-only hover, opened Sort menus, page-size popovers and prior surface/focus states. Final verification passed: 1 test / 1,549 assertions across 12 role/page combinations in light/dark at 1440/390px, including actual opened Sort and page-size popovers. Main Filter and Case table screenshots were inspected. This is a CSS/Blade-only revision; no asset sources or backend behavior changed. Blade compile/clear and diff checks passed.

## Shared individual-filter and row hover

Latest user request supersedes the row-hover outline: use the same background highlight as individual filters. `--rs-item-hover` is #C4DDCE light / #41664E dark and now owns individual field-wrapper hover, option-item hover and record-row hover. Top-level Filter/Sort controls keep their existing hover token. Row cells receive the same fill so legacy cell backgrounds cannot obscure the row highlight. No pointer-hover row outline or shadow remains. Keyboard-focused rows keep the visible 2px focus indicator; the eye's color, .9 glyph scale, stable hit target and eye-only tooltip remain unchanged.

Scope stays within the approved six worklists. This revision changes no handlers, routes or data. The expanded surface/filter suite passed 1,571 assertions; the row regression is rerun separately with a fresh browser per role fixture after browser-driver/tab failures. Final row regression passed 378 assertions; combined with the surface/filter suite, 1,949 assertions passed.

## Expanded cleanup after screenshot review

The user subsequently extended the same design to filter field contents and footers, native Sort and Cases per page controls, option dropdowns, date panels, table wrappers/headers, pagination footers/buttons, grid cards, and existing modals. These are now in scope; earlier exclusions for record-card borders are superseded. Semantic badges, validation states, disabled states, scrims, row outlines, focus rings and record actions remain intact.

`record_surface_styles` owns the native control fills as well as their wrappers. This fixes the old inner select painting over the updated outer Sort/page-size surface. Apply filters uses the medium-sage action tokens. Table/grid/footer surfaces share the same off-white/deep-green tokens; headers and secondary surfaces use the control shade. Pagination active states retain the sage highlight.

`partials/record_modal_surface_styles.blade.php` applies the same palette to the existing User/Branch/Payment/Case modal shells, headers, fields and secondary actions. `layouts/modal-frame.blade.php` also includes it for Service modal/iframe detail and form content, so the embedded page shares the palette. Existing modal opacity/scale transitions are preserved; old background-color transitions are excluded. No modal loaders, buttons, routes, data or permissions were changed.

Modal visual QA uses rendered User and Service detail fixtures plus Branch's native open state. The Branch fixture opens the existing dialog directly for appearance checks; it does not claim end-to-end Branch Create workflow validation. Existing Payment history opening remains covered by the row regression. Full live validation/submission of every modal is outside the visual checks.

Final preservation regressions passed: test_service_reference_search_actions_and_case_tabs and test_rendered_view_row_hover_and_payment_history_eye (2 tests / 600 assertions), covering agreed action/tab colors, eye scale, outer outline, stable row/cell geometry, eye-only tooltips and Payment spacing. Combined with the surface test: 3 tests / 1,860 assertions.


Expanded surface cleanup verified (2026-11-05): the user explicitly included native Sort/page-size controls, full filter/option/date panels and their Apply footer, table wrappers/headers, pagination footers/buttons, grid cards and existing modals. The same flat surface/control tokens now own these areas. Existing row outlines, semantic statuses, disabled states, scrims, focus, routes, handlers and layouts remain. Old exclusions for record-card borders are superseded on these six worklists. Modal iframe styling is shared through partials/record_modal_surface_styles and layouts/modal-frame; default modal fields/secondary actions are flat, while warning/destructive text semantics remain.

Final checks: test_record_headers_kpis_and_toolbars_use_flat_surfaces passed 1,400 assertions, test_record_modals_use_the_updated_flat_surfaces passed 39, and the preserved row/eye/Payment regression passed 426 (3 tests / 1,865 assertions). Surfaces run across 12 role/page combinations, light/dark at 1440/390px. Modal appearance covers User, Service iframe and Branch native open states; Branch fixture opens its existing dialog directly for styling QA rather than claiming full Create workflow coverage. Existing Payment history opening is exercised by the row regression. Open Case/Payment filter screenshots and User/Service modal screenshots were inspected. Blade compile/clear and diff checks passed; the existing asset build passed earlier in this surface task. Full live submission/error paths of every modal remain unverified.


Current individual-filter and row hover revision (2026-11-05): the user replaced pointer-hover row outlines with the same background used for individual filters. Shared --rs-item-hover is #C4DDCE light / #41664E dark. It owns individual field-wrapper hover, option hover and table-row/cell hover across the six worklists, including Payment history modals relocated outside page-content. No hover stroke or shadow remains on rows. Keyboard-focused rows retain a visible 2px focus indicator; eye color, .9 glyph shrink, tooltip behavior, click target and geometry remain unchanged. Top-level main Filter/Sort triggers keep their existing soft hover.

Owner: partials/record_surface_styles.blade.php. Verification: the expanded surface/filter suite passed 1,571 assertions, and the role-rendered row/eye/Payment regression passed 378 with fresh isolated browsers per fixture (2 tests / 1,949 assertions). Tests cover the exercised role views, both themes, desktop/mobile widths, same row fill, absent pointer outline, stable geometry, eye scale and tooltip separation. Filter-field/option hover CSS states are checked using the project's CDP method. Screenshots of Branch and Master row fills were inspected. Earlier 2px pointer-hover row frame guidance is superseded. Blade compile/clear and diff checks passed. No backend or handler changes.

Latest softness/timing revision: individual-filter/row fill is now #E1EEE6 light / #2D4838 dark, with a 240ms ease background transition on rows/cells, including return to default. Reduced motion disables the fade. Eye animation remains separate. Branch grid cards no longer use the 20rem minimum; action-footer padding is 10px. Branch Edit no longer replays fetched page scripts; global menu setup is idempotent. See branch-menu-and-soft-hover-2026-11-05.md for the 2,041 passing assertions and test limits.


## Connected Grid view (2026-11-05, superseded palette)

Grid view in Branch Management, User Management (System/Branch Admin), and Service Management (System/Branch Admin) now joins the toolbar and grid area with the existing --rs-panel sage surface: #D5E4D9 light / #1C3025 dark. This differs from the page canvas (#E6EEE8 / #0D1712) and individual cards (#F7FAF8 / #15251C). Cards retain their spacing and existing content/actions. Toolbar top and grid bottom corners are 12px; adjoining edges are square with no gap, outline or shadow. Grid-area padding is 16px. Service Add remains in its section intro. Table view retains its previous continuous surface and transparent approved headings. No handlers, routes, data, or permissions changed. Owner: resources/views/partials/record_surface_styles.blade.php.

Verified: existing role-rendered surface suite passed 2,067 assertions across 12 role/page combinations in light/dark at 1440/390px. Expanded checks exercise Grid to Table switching on five authorized directory/service role combinations, measuring touching/aligned edges and distinct page/tray/card backgrounds, including the empty Branch Admin user grid. Branch/Service desktop and User dark narrow screenshots inspected. Blade compile/clear, PHP syntax and touched-file whitespace checks passed. Assets were unchanged; the previous successful build remains applicable. Other Service catalog tabs and full live mutation paths were not separately exercised in this revision. This supersedes the earlier Table-only join; individual grid cards remain separate within the connected container.


## Grid background restored (current)

User requested the original background with the connected layout preserved. Branch/User/Service toolbar and grid area now use --rs-surface (#F7FAF8 light / #15251C dark), replacing the sage tray. Joined edges, 12px outer corners, 16px grid padding, card spacing and all existing interactions remain. Source: resources/views/partials/record_surface_styles.blade.php. Blade compile/clear and touched-file whitespace checks passed. Browser suite passed 2,047 assertions across 12 role/page combinations, both themes at 1440/390px, including Grid/Table switching and connected geometry. Restored Branch and Service desktop screenshots inspected. Assets unchanged.


## Darker grid cards (current)

Only Branch/User/Service record cards now use --rs-grid-card: #EAF2ED light / #101E16 dark, a slightly darker shade than the unchanged connected toolbar/grid surface (#F7FAF8 / #15251C). Page, toolbar, tray, geometry and interactions remain unchanged. Card inner surfaces keep their existing treatments. Shared owner: resources/views/partials/record_surface_styles.blade.php. Browser surface suite passed 2,063 assertions across 12 role/page combinations in both themes at 1440/390px, including card colors, connected edges and Grid/Table switching. Branch light/dark screenshots inspected. Blade compile/clear and whitespace checks passed. Assets unchanged; full live mutation paths and other Service catalog tabs were not separately exercised.


## Toolbar/content separator (current)

A single 1px solid line now separates the connected toolbar from Grid or Table content across the six worklists. --rs-divider is #B8CEC0 light / #355241 dark. This is an internal separator only; no outer outline, shadow, gap, new element, handler or layout wrapper is added. Existing backgrounds, darker grid cards, corner radii, card spacing and interactions remain. Owner: resources/views/partials/record_surface_styles.blade.php. Blade compile/clear and touched-file whitespace checks passed. Browser surface suite passed 2,131 assertions across 12 role/page combinations in light/dark at 1440/390px, including separator width/style/color, connected geometry and Grid/Table switching. Branch Grid/light and User Table/dark screenshots inspected. Assets unchanged; other Service tabs and live mutation paths were not separately exercised.


## Column-header separator (current)

The existing toolbar separator is also applied below the last column-header row on Case Records, Master Case Records, Branch/User/Service Management and Payment Monitoring. Native tables use each final header cell bottom border; Payment uses its existing .pm-record-head. Both use the same 1px solid --rs-divider (#B8CEC0 light / #355241 dark). Header backgrounds, connected surfaces and mobile header visibility remain unchanged. No row borders, outer outlines, shadows or new elements are introduced. Owner: resources/views/partials/record_surface_styles.blade.php. Blade compile/clear and touched-file whitespace checks passed. Role-rendered surface browser suite passed 2,179 assertions across 12 role/page combinations in light/dark at 1440/390px, including header-divider width/style/color and hidden mobile header styles, existing toolbar separator and connected geometry. Branch desktop screenshot inspected. Assets unchanged; other Service tabs and live mutation paths were not separately exercised.


## Connected summary and case tabs (current)

User approved connecting the existing header and KPI area and connecting Case/Master status tabs to the toolbar. Branch/User Management and admin Payment Monitoring now paint a continuous --rs-header summary surface with the existing lighter KPI cards inset by 16px. Header bottom corners are square; KPI area supplies the 20px bottom corners. Existing DOM/data and KPI links remain in place. Staff Payment has no KPI and receives none.

Case Records and all Master roles now place the existing tabs on --rs-surface with 12px top corners, touching the square-topped toolbar below. Existing tab labels, status destinations, sticky toolbar logic and record handlers remain. Main sections use 16px separation (including Service header/section intro and intro/toolbar); record/pagination separation stays 12px. Payment narrow gutters are 16px to align the header and KPI block. No shadows or outer outlines are added. Owner: resources/views/partials/record_surface_styles.blade.php. Browser suite passed 3,379 assertions across 12 authorized role/page combinations in light/dark at 1440/1100/390px, including summary alignment/contact, 16px main gaps, connected Case/Master tabs, existing Grid/Table switching and open controls. Existing feature checks passed 6 tests / 85 assertions; Blade compile/clear and touched-file whitespace checks passed. Branch desktop, User dark tablet, Case/Master desktop/narrow and Branch Payment dark narrow screenshots inspected. Branch Admin Payment navigation is retained between header/KPI on the same continuous background. Assets unchanged. Correction Requests content, other Service tabs and live mutation paths were not separately exercised. Existing feedback/sync notices retain their position when shown.


## Case tabs under header, KPI sizing, Payment row lines (current)

Latest correction attaches Case/Master status tabs to the page header, replacing the previous toolbar attachment. Tabs paint --rs-header and supply 20px bottom corners; toolbar starts 16px below with its 12px top corners restored. Staff/Owner narrow gutters align at 16px. Existing labels, URLs, order, sticky toolbar and interactions remain.

Branch/User/Payment KPI cards now share 140px minimum height, 20px padding and 40px square icon areas; longer content can grow. Responsive column counts and card contents remain unchanged. Payment summary and transaction list items have 1px --rs-divider separators between records, using the existing light/dark tokens. Empty states and last rows get no extra separator. Header/column-header lines remain. Source: resources/views/partials/record_surface_styles.blade.php. Blade compile/clear and whitespace checks passed. Surface suite passed 3,499 assertions across 12 role/page combinations, light/dark at 1440/1100/390px. Row/eye/Payment suite passed 438 at 1440/390px, including two-record separators, stable hover geometry and existing modal/tooltip behavior. Total: 2 tests / 3,937 assertions. Master header/tabs, Branch KPI and populated Payment screenshots inspected. Assets unchanged. Transaction-tab separator styles share the same rule but were not separately exercised; live mutation paths and other Service tabs remain outside these checks.


## Header-to-tabs/KPI divider (2026-11-05, current)

User requested a horizontal line at the join between the header and its attached status tabs or KPI area. The six-worklist skin now adds a 1px solid bottom border to the existing header only when Case/Master tabs or Branch/User/Payment KPIs are present. It reuses --rs-divider (#B8CEC0 light / #355241 dark). Branch Admin Payment keeps its existing section navigation immediately below the header; the divider marks that boundary. No background, padding, KPI size, section gap, corner radius, route, handler or DOM position changes. No shadow or outer outline is added. Owner: resources/views/partials/record_surface_styles.blade.php.

Verified: role-rendered surface browser suite passed 3,553 assertions across 12 authorized role/page combinations in light/dark at 1440/1100/390px. It checks header divider width/style/color, preserved joined geometry, KPI sizing and existing Grid/Table/open-control surfaces. Branch desktop screenshot inspected. Blade compile/clear and touched-file whitespace checks passed. Assets unchanged. Row behavior was unchanged by this divider-only revision; its prior 438-assertion regression remains recorded above. Full live mutation paths, Correction Requests content and other Service catalog tabs were not separately exercised.
