# Complete record search adoption

Applicable: adopt the complete current Payment Monitoring search component in Case Records and Master Case Records: UI, Search button, Enter/X, one-row matching with underlined query text, scoped remote suggestions and refresh behavior. Preserve Filter/Sort/page-size positions, roles and backend fields. This supersedes the earlier width-only interpretation.

`resources/views/partials/case_records_layout_styles.blade.php` caps only `.case-compact-search-field` inside the unchanged full-width grid search row. At 1201px and above, use width min(560px, 100% - 24px), max-width 560px, flex 0 1 auto. The toolbar tracks and trailing controls retain their original dimensions. Below 1201px existing responsive rules remain.

Consumers: Staff Case Records; Branch/System Admin Master Case Records; Owner case history via their existing shared toolbar/layout includes. Role scope, backend search fields, filters, pagination and record actions are preserved. Search UI/interactions now match the complete component contract.

Verification: `tests/Browser/CaseSearchWidthTest.php` renders actual role-specific pages with built assets using isolated SQLite, then compares trailing-control positions before/after full-width baseline styling. It covers four roles, light/dark, 1440/1920/1100/390px. Search is capped and separated on desktop, remains contained at narrower sizes. Light/dark fixture screenshots inspected. With existing toolbar feature coverage, 4 tests / 67 assertions passed; Blade compilation/cache clear passed. Full live-role session acceptance remains pending.

Complete adoption: visible84px Search button, separate X, input128px trailing reserve/14px400 type, simple one-row Case ID · Client · Deceased matches with underlined substrings, ellipsis/full accessible names,300ms authenticated suggestion requests beyond loaded page, keyboard selection and results-only Search/Enter. Search uses applied filter snapshot; pending selections remain unapplied. Staff/Master/Owner existing async loaders handle requests, cancellation and in-place errors; component reinitializes after refreshed results. Source metadata partial avoids column-index assumptions. Current specification: .agents/skills/funeral-system-ui-consistency/references/record-search.md.



Complete-component verification:56 tests/879 assertions plus11 Node tests passed; final strengthened case browser check passed80 assertions. Build/Blade checks passed. Current skill owner is references/record-search.md, linked from SKILL.md.

2026-11-03 placeholder copy: Case/Master/Owner use Case ID, client or deceased name; Payment uses Case ID, name, payment record or reference. No leading Search in placeholder; accessible labels and Search buttons retained. Existing backend-supported query fields unchanged.4 toolbar feature tests/148 assertions passed.
