# Payment Monitoring UI adoption — 2026-11-02

Scope: adopt relevant current Master Case Records presentation in Payment Monitoring, including all existing authorized consumers. Shared source: `resources/views/staff/payments/history.blade.php`, `resources/views/staff/payments/_toolbar.blade.php`, `resources/views/staff/payments/_consistency_styles.blade.php`. Shared select enhancement: `resources/js/app.js`.

| Pattern | Applicability and outcome |
| --- | --- |
| Toolbar | Applicable: flexible soft search, balanced Filter/Sort controls, matching control surfaces, Cases per page, removable applied chips and regular red Clear filters with subtle hover outline. |
| Filter selections | Applicable: existing options consolidated into compact labelled Filter panel; child menus escape its scroller via native top-layer popovers, prefer right and flip/clamp when necessary. Custom payment dates use a nonmodal side popover; no backdrop or body lock. |
| Sorting | Existing backend asc/desc support exposed as latest/earliest payment first. No new ordering semantics. |
| Rows/actions | Applicable: modest corners, regular primary/secondary text, clearer spaced field labels, soft inset row outline and semantic icon color-only hover (no glow). Existing View Transactions, summary and print destinations retained with accessible labels; labelled Record Payment retained. |
| Loading/empty/footer | Already consistent: existing results-only async loader and stable result/footer footprint preserved. Tabs, summary and filters stay outside pending mask. |
| Real table conversion/new Edit | Not applicable: retain grouped cases/transactions, financial fields and existing correction workflow. No new action permissions. |
| Manual filter Apply | Latest user-approved revision: explicit Apply filters commits selections; Use date range sets pending dates. Sort/page size remain automatic using applied filter snapshot. |
| Page size | Existing control retained with 10/20/50/100; transaction groups now honor the selected existing page size rather than fixed 20. |

| Consumer | Preserved scope/actions |
| --- | --- |
| Staff main/other branch | `/payments/history`; own branch, existing Record Payment workflow. |
| Branch Admin main/other branch | `/admin/payments` and existing alias; assigned branch only; existing correction approvals. |
| System Admin | Same shared admin view; existing branch selector retained. |
| Owner | Existing redirect to Owner Analytics preserved; no new Payment Monitoring access. |

Verification uses in-memory SQLite and actual role-rendered Blade plus the built application assets in isolated Chrome fixtures. Populated long-name cases exercise real styles rather than CSS-only mocks; screenshots cover light desktop and dark narrow for Staff/Branch/System. Feature coverage includes main/other branch actors and Owner redirects. Browser checks cover computed control surfaces/widths, regular row text, transparent icon hover with semantic glyph color, nonmodal date placement and no custom-date auto-submit. These are isolated render checks, not full live-session acceptance or financial write/print-output validation. The older live-data typography test has updated expectations but is intentionally not executed against real records.

Final checks: 53 tests / 650 assertions and 11 Node tests passed; production build, Blade compilation/cache clearing passed.

Screenshot corrections: Sort menu reserves 280px for unbroken labels; custom overlapping action tooltips are suppressed in favor of native title/accessibility labels. Payment Monitoring excludes obsolete amber clear-button rules. Its redundant page-navigation footer is hidden so pagination remains the final footer.

Latest correction: pagination is a separate sibling footer 12px below each result panel; search retains 14px outer/inner corners without clipping suggestions. Applied filters use the shared catalog-filter-chip markup, preserving individual remove handlers.

Action correction: match Master Case Records' two glyph-only drop shadows at 50%/30% opacity (2px/5px); native title tooltips on transaction/summary icons, summary title follows expanded state. Modal print actions retain visible Print Payment Summary/History labels, rather than icon-only output.

Search radius follow-up: outer search wrapper and direct input both enforce the Master Case Records 14px radius at the scoped Payment form boundary; browser checks assert computed radius for Staff, Branch and System Admin.

Current action colors: neutral ink at rest; only the icon changes to semantic green (transactions) or blue (summary/print) on hover/focus, matching Master Case Records. Print text remains readable neutral ink.

Tooltip correction supersedes native-title-only behavior: show existing custom labels immediately on hover/focus-visible, 6px below and right-aligned to action buttons, with no glow on tooltip text. Remove duplicate native titles from these two buttons; retain aria-label and dynamically updated summary tooltip.

Latest user correction: tooltips centered above icons with visible overflow, text-only semantic status colors, no action glow, and modal print icon plus visible label both use hover/focus blue.

Tooltip clipping fix: action labels use manual native popovers in the browser top layer, fixed above the trigger with viewport clamping. Ancestor overflow/corners cannot clip them. Hide on leave/blur/click, Escape, resize, scroll and async listener teardown.

Approved summary order in inline summary, history modal and transaction section: Remaining Balance, Total Paid, Total Service Amount, Payment Count. Existing amounts and payments_count expressions preserved. Shared-role feature check passed (66 assertions); Blade compile/cache clear passed.

Approved modal layout: Payment History heading with existing client/deceased/branch context; compact four-value summary, horizontally scrollable payment table (date, record number, method, right-aligned amount, View details). Details replace the history body in the same modal, with Back restoring trigger focus and existing metadata, print/correction permissions. Header/footer stay outside scrolling body. Reopening starts at history. Source: resources/views/staff/payments/_history_modal_body.blade.php. Existing values/calculations retained. Focused role/report suite passed 47 tests/413 assertions; modal navigation browser check passed 63 assertions across Staff/Branch/System. Full live acceptance and actual financial writes/print output are not claimed.

Modal refinement: body padding24px; summary16px padding/gaps, detail grid24px row/32px column gaps, label/value8px separation and wrapping values. History row actions are eye/print icons with accessible names and title tooltips. Details footer replaces history footer with Back to payment history and selected-payment Print payment summary. Existing correction action remains in details body; header close remains available. Table print and details print reuse the existing authorized route.

Latest details layout: three ordered groups (Payment information, Balance information, References and recording), aligned Label : Value rows with subtle bottom dividers. Label column220px/gap20px desktop;42%/12px narrow, wrapping values. Supersedes stacked labels; existing values and modal actions preserved.

Reduced visual lines: no per-field borders; subtle separators only between the three information groups, no trailing separator. Label/value alignment preserved; row vertical padding8px.

Status alignment correction: detail status is left-aligned at the value column start, intrinsic width, no badge centering/min-width/margins; semantic colors preserved.

Dynamic modal header: history uses Payment History — case code; selected details uses Payment Details — payment record number (Payment Details alone when unavailable). Details subtitle retains case code plus original client/deceased/branch context. Back/reopen restores history title and hides duplicate case subtitle.

Header context refinement: labelled definition-list fields for Case ID, Client, Deceased and Branch (branch code plus name), visible in history and details. Two-column desktop, single-column narrow, wrapping names,14px values/12px muted labels, distinct branch code. Existing case code and branch code used rather than internal database IDs.

Naming/typography refinement: Payment Summary replaces Current Case Summary. Row tooltips use View payment history and Show/Hide payment summary; modal eye/print use View payment details and Print payment summary, with top-layer tooltips shared by modal icons. Payment detail labels14px, values15px/1.5 line height, group headings15px; compact8px row padding retained.

Readability revision supersedes14/15px details: labels/values16px, headings17px, modal title22px, context15px; existing compact spacing retained. Rendered browser checks assert computed detail sizes across roles.

History header simplified to case/branch/client/deceased context only. Visible redundant title removed; hidden title retains accessible dialog naming. Detail title remains visible only in details mode; Back restores context-only header. Existing typography sizes unchanged.

Header context now uses aligned Label : Value,96px label columns (88px narrow) and14px gaps so Deceased does not collide with its value. Modal has stable viewport-bounded760px height across history/details; body scrolls inside fixed header/footer. Back to payment history is a neutral arrow/text button with subtle hover/focus border. Text sizes unchanged.

Single-payment print renamed to Print payment record; document heading/browser title and post-save messages use Payment Record. Overall case Payment Summary and Print payment history remain. Existing print routes/data/permissions unchanged.5 focused tests/123 assertions and Blade checks passed.

Latest approved title revision: visible Payment History / Payment Details, no IDs in title; context fields remain below. Remove duplicate table/details body heading, focus visible title on details entry. Supersedes context-only history header. Existing sizes/height unchanged.

Tooltip lifecycle correction: dismiss all action tooltips on action pointerdown/click; suppress tooltip reopening during programmatic focus restoration after Back/Close. New pointer entry or keyboard Tab re-enables tooltips, preserving keyboard focus access. Modal eye uses Master Case green #087443/light, #5CE0A0/dark on hover/focus; print stays blue; defaults neutral, no glow.

Page-size spacing correction:104px control with14px left/36px right select padding,12px label gap; right-aligned160px menu with40px minimum options and separated number/check. Plain numeric options omit decorative icons. Existing10/20/50/100 choices and async pagination behavior retained.

View payment history (2026-11-03) uses one decorative Bootstrap bi-eye icon. The document/clock badge is superseded and its styles removed. Keep the existing44px target, View payment history accessible label/top-layer tooltip and neutral default with green hover/focus; no glow. Staff, Branch Admin and System Admin share this implementation.

Search review/fixes: suggestion commits canonical case code rather than concatenated case/client/deceased display text, which backend cannot match as one field. Transaction view now provides the same local suggestion metadata. Suggestions remain current-page only; Enter searches all accessible records by existing backend fields. Latest request aborts pending request and guards stale success/error/finally, so later search/clear is not dropped. Preserve filters/page size/sort and reset both page keys. Isolated backend checks cover case/client/deceased/reference and missing term in both views across Staff/Branch/System; browser checks exercise suggestion click, trimmed Enter, clear and rapid request cancellation. No real payment writes or live user sessions used.

Filter badge count excludes search q. Search chip/reset stay available; only additional filters increment the badge. Cross-role feature check verifies search-only has no badge and search plus two filters shows2;81 assertions passed.

Clear behavior revised: Clear filters removes additional filters only, preserving current search, active tab, sort, page size and existing case scope. It is hidden for search-only state. Search X removes q only through existing form serialization and retains filters. Clear filters server href and async click share semantics, with async reading the latest typed query. Default role branch scope remains server-enforced.

Sort review: existing asc/desc sorts case groups by MAX(paid_at) for nonvoid payments, tie-breaks case id in same direction. Summary includes cases without payments (NULL first ascending, last descending); transaction view requires matching payments. Summary withMax is all nonvoid payments; transaction withMax follows active payment/date filters. Nested payment list retains its own chronological order, independent of group sort. New isolated tests cover multiple payments/case, equal dates, void exclusion and unpaid cases. Suggested future choices (not implemented): balance highest/lowest and newest case. Suggested clearer labels Last payment: Newest first / Oldest first; proposed no-payment-last policy would require deliberate behavior change. Preserve existing sort until separately authorized.

Final sort verification:47 feature tests/441 assertions passed, including all three Payment role consumers and existing scope/filter/pagination regressions. No sort options/business behavior changed by this review.

Approved sort rollout: Latest Payment(desc), Oldest Payment(asc), Highest Balance(balance_high), Lowest Balance(balance_low). Shared sort closure; both date directions explicitly place no-payment cases last. Balance ordering uses current balance_amount and stable case id. Existing valid-payment rules/scopes/date filters preserved.47 feature tests/465 assertions and Blade checks passed. Supersedes earlier review-only recommendations for these4 choices.

Row/table spacing refinement: desktop rows20px/24px padding,24px column gap/14px row gap; columns140px identity, flexible names,180–240px last payment and intrinsic actions. Tablet uses aligned two-column layout; narrow uses single column with18px/16px padding and12px gaps. Action gaps12px; history table cells14px/16px with middle alignment. Dark text-only statuses explicitly use Paid #86EFAC, Partial #FCD34D, Unpaid #FCA5A5 in records and portal modal, overriding legacy dark/role cascade. Browser checks measure actual foreground/background contrast≥4.5 for all statuses across roles.

Desktop row correction: rebalance name/date space with140px case, min240px/1.1fr names, min230px/.9fr date, intrinsic actions from1301px. Use two-column layout through1300px to prevent crowding. Last payment is a small label above an unbroken full date/time, replacing accidental wrap between date and time. Preserve actual date format/value and roles.

Approved grouped-record headers: Case, Client / Deceased, Last Payment, Status, Actions in summary and transaction views. Shared _record_headers partial. Wide desktop uses matching five-column header/row templates; status is a separate cell from action group. Remove repeated visible Last payment label on desktop but retain visually hidden accessible text; stacked layouts retain visible label below1301px and hide the desktop header. Header uses neutral card surface; no new columns/queries/actions. Existing client/deceased and branch grouping preserved.

Case-row action addition authorized: Print payment history printer icon sits between View payment history and summary chevron. Uses existing payments.history.print route, native top-layer tooltip and blue hover/focus only. Single-payment Print payment record remains inside modal. Header/row Actions column widened to156px for three44px targets and12px gaps; existing role authorization retained.

Search UX revision: explicit Search for typed query / Press Enter action, Suggestions from this page heading and separate case/client/deceased/branch/status metadata with matched text highlighted. Selecting a case filters by its canonical case code; Enter searches typed query. Arrow keys navigate popup; Escape closes. Latest manual-filter approval now implemented; this supersedes auto-filter guidance. Tests separate pending filter selection from Apply and automatic Sort requests.


Final revision verification:52 tests/734 assertions passed; Blade compilation/cache clear and skill validation passed. Search popup screenshot inspected. Full live-session acceptance remains pending.

Latest simplification supersedes suggestion-popup UX: no autocomplete or current-page suggestions. Explicit Search button and Enter submit the typed query; typing makes no request; X clears q only. Removed popup handlers, row suggestion metadata and obsolete CSS. Input preserves rounded container with84px Search segment and separate clear target. Manual filters and applied-filter snapshots unchanged.

Latest search clarification: preserve Search button/Enter plus simple max5 case matches (case ID, Client, Deceased only). Cached matches appear immediately;300ms cancellable request to existing role-scoped filtered history route retrieves matches beyond current page. Results remain unchanged during suggestions; selected case commits canonical code. No branch/status/help headings in popup. Keys Up/Down/Enter/Escape supported.6 focused tests/399 assertions and Blade checks passed; remote suggestion transport verified with simulated HTML plus real backend role matching. Supersedes no-popup version.

Suggestion row refinement: single line Case ID · Client name · Deceased name,10px gaps, full search-control width. Ellipsis for long names, full names via accessible label/title. Supersedes stacked Client/Deceased layout.

Search match emphasis: underline every case-insensitive occurrence of the typed query in case ID/client/deceased text. Neutral foreground, transparent mark background,1px underline/3px offset; no row layout changes. Render via text nodes/mark elements, not interpolated HTML.

Search width revision: at1201px+ cap visible search control at560px and leave24px unused inside its existing flexible grid cell. Toolbar column tracks remain unchanged, keeping Filter/Sort/Cases per page positions fixed. Narrow layouts retain responsive width. Browser compares control positions against former full-width search geometry.

2026-11-03 filter correction:320px border-box main panel; header and Apply footer are separate fixed flex children, fields alone scroll inside a clipped body. Removes old negative-margin sticky footer leak. Side menu widths240px/placement remain. Placeholder uses Payment Record No. explicitly. Regression geometry checks force300px panel height and verify scrolled fields cannot hit the footer; side menu remains top-layer/outside panel.

Final filter correction verification:6 tests414 assertions passed; Blade/skill validation passed. Scrolled filter fixture screenshot inspected.

