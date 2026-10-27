# Owner dashboard component audit — 2026-10-14

Scope: `/owner`, its shared branch/period selectors, trends, comparison, exposure, detail dialog and custom-date dialog. Review only; application files were not changed during this audit. Uses the existing Staff UI Reference contract.

## Evidence status

Initial audit covered the current source while awaiting Owner sign-in. The user subsequently signed in and authorized improvements. Fresh browser checks and implementation results are recorded below. Existing isolated feature tests cover valid receipts, all-date balances, scoped detail endpoints, 20-row pagination, empty search and Staff denial.

## Confirmed source findings

| Priority | Component | Finding and recommendation |
| --- | --- | --- |
| P1 | Trend bars | No labeled X/Y axes, numerical Y ticks or gridlines. Bar height has a minimum 14% for every positive value and a visible 4% zero bar, so heights exaggerate small values. Use a true proportional scale, a zero baseline, date/month X label and collections/case-count Y label. Keep zero placeholders separate from quantitative bars. |
| P1 | Branch comparison | Still a table, despite the requested visual presentation. Replace primary comparison with descending horizontal bars, explicit metric selector, displayed value and tie-aware rank. Keep branch select/clear and period scope. |
| P1 | Detail navigation | A bar narrows the detail request to CUSTOM dates; View Case builds return_to from that narrowed request. Returning can replace the original dashboard period with the bar period. Carry original dashboard scope independently from detail scope. |
| P2 | Amount hiding | Visible amounts and bar accessible names are masked, but KPI button data-title/drilldown scope remains independent. Monetary change percentages and bar heights remain visible. Clarify this is amount masking; decide whether comparison percentages/heights should be hidden too. |
| P2 | Detail summary | Dialog has matching count and scope but no selected KPI total, filtered total, record interval or Full Records shortcut. Add concise summary and distinguish overall versus search-filtered totals. |
| P2 | Pagination | Server fetch is capped at 20 records, but pagination lives inside the scrolling results. Replacing results removes the focused page link without moving focus or resetting dialog scroll. Keep pagination in a stable footer, announce page changes and move focus to result summary. |
| P2 | Search | One generic placeholder mentions payment records in case-only dialogs; no dedicated Clear Search action. Use context-specific hints and explicit reset. Empty search guidance should distinguish zero records for the scope from no search matches. |
| P2 | Loading/error | Loading replaces the entire results block and errors offer only text instructing Search to retry. Provide a Retry button and distinguish expired session from request failure. Retain scope and announce state. |
| P2 | Chart keyboard/touch | Zero bars are disabled and unavailable to keyboard exploration; hover-only tooltip cannot serve touch users. Exact-value disclosure helps, but should say View exact values. Ensure all periods have an accessible value, with only nonzero periods offering drilldown. |
| P2 | Custom dates | Native required/min validation exists, with draft reset on cancellation. No inline explanation of an invalid range. Add clear field-associated feedback; verify Escape, backdrop and current-CUSTOM reopening in the browser. |
| P3 | Branch clear | Remove Branch Filter and Show All Branches are duplicate actions in two sections. Keep one obvious reset plus appropriate selected-branch context. |
| P3 | Density | Date basis is repeated in KPI footers, comparison caption, chart description and chart comparison summary. Consolidate explanation while retaining the difference between reporting period and all-date current exposure. |
| P3 | Scalability | Detail endpoints paginate, but dashboard controller still calculates legacy metrics no longer displayed before computing the executive overview. Clean up unused queries after checking consumers/tests; measure before claiming performance improvement. |

## Source behavior that is appropriate

- Branch IDs and dynamic date bounds; selected reporting scope persists in links.
- Valid receipt-date collections versus case recording-date measures; all-date current balances are explicit.
- Zero previous baseline avoids fabricated growth percentages.
- Native buttons and dialogs; initial focus, Escape, backdrop close, trigger-focus return and background scroll lock are implemented.
- AbortController and request identity prevent stale search responses replacing newer results.
- Read-only detail endpoints, server pagination, escaped record output and role protection.

## Pending browser matrix

Fresh check needed for all four KPI dialogs and risk dialog; bar-specific receipt/case drilldowns; search hit/empty/reset; Prev/Next on a fixture with more than 20 records; scroll/focus after page replacement; View Case/back scope; custom valid/invalid/cancel/Escape/backdrop/reopen; amount masking; branch select/clear; trend toggle and exact values; shared dropdown keyboard/outside-close; hover/focus; mobile and desktop light/dark. No live records should be created for review.

## Implemented after user authorization

- Added labeled date/month X axis, currency/case-count Y axis, numerical ticks and gridlines. Bars use actual proportions without a minimum positive-value exaggeration.
- Replaced comparison table with ranked horizontal branch bars; Collections, Service Value and Cases selectors reorder values with equal ranks for ties. One branch-reset action remains in the toolbar.
- Preserved original dashboard branch/period independently of clicked bar scope when opening case details.
- Added scope/matching totals, record interval, context-specific search hints, Clear Search and distinct empty-scope/search messages.
- Moved pagination into the stable dialog footer; page loads reset results scroll and focus the summary. Added read-only Case Records/Collections Analytics shortcuts and explicit broader balance-record destination for risk details.
- Added Retry and expired-session handling, plus field-associated custom-date feedback.
- Retained native modal focus/closing, background scroll lock, request cancellation and escaped output. No live records were written.

Fresh checks: all four KPI summaries and destinations; risk search hit and empty state; receipt bar date restriction; case-volume bar restriction; corrected original-month return link; branch select/remove and empty-scope modal; three comparison metrics; custom reversed dates and Cancel; Escape/focus return; desktop 1440px light/dark and mobile 390px dark modal containment/scrolling, no document overflow. Build and Blade compilation passed; 20 Owner tests passed, 152 assertions, including a new original-dashboard return-context check.

Limits: more-than-20 records pagination was exercised in isolated server tests, not with live browser records. Retry/expired-session branches were source-reviewed without forced network failures. No claim of browser performance at 5000 records; cleanup of legacy unused dashboard aggregates remains a separate performance follow-up. Full Analytics exports were unchanged and not retested.

## Final approved flow: summary first

The user subsequently selected `Dashboard → View Summary → View Records → Case Details`. This supersedes the searchable dashboard modal described above.

- KPI cards are static overview articles with explicit View Summary buttons. Graph bars open a scoped summary.
- The 540px summary dialog has a metric icon/title, total/count, branch/date basis, explanatory note and one View Records destination. No search, records table or pagination is present in the dialog. It shrinks to content and stays within the mobile viewport.
- Cases/service metrics open date-filtered Master Case Records; outstanding uses all-date WITH_BALANCE; past-interment uses AFTER_INTERMENT with the same case/deceased fallback as the dashboard.
- Collections open a new Owner-only read-only `/owner/collections` worklist using valid receipt dates. This avoids mixing receipt-date collections with case-recording-date analytics. The page reuses the panel, filter tokens, table and pagination foundation.
- Master Case Records and collection records retain original dashboard context through filtering. Case Details returns to the full current records URL, preserving search, dates and pagination.

Final verification: light desktop summary sizing (540px wide, about 451px tall), dark mobile summary (358px wide, about 430px tall), no modal search/table, Escape/focus return, actual summary→case records→search→case details→Back→dashboard flow, and actual receipt bar summary→2 receipts/45000→search FC0001→case details→Back with search preserved→original month. Mobile dark collection worklist contrast and document overflow checked after scoped fixes. Build, JS syntax and Blade compilation passed. Final Owner regression selection: 20 passed, 165 assertions, including summary-only output, aligned past-interment fallback, Owner receipt eligibility and page 2 pagination, and Staff denial. No live records were changed.
