# Compact Case/Master worklists

User-approved implementation: seven columns in Staff Case Records and Branch/System Admin Master Case Records: Case ID, Deceased, Interment Date, Case Status, Payment Status, Remaining Balance, Actions. Owner Master history retains Branch after Case ID for eight columns. No new Branch column is introduced in admin variants that did not already show one.

Each cell has one displayed value. Case identifiers remain unchanged; interment dates retain each page's existing fallback order and show date only. Remaining Balance uses the existing balance_amount with the peso sign and two decimals, rather than the previous total_amount/paid/balance bundle. Owner branch code/name appear together in a single value. Existing status badges, eye/More Actions, row activation and authorized links remain.

Client/contact, service/package, wake location, full funeral/interment schedules, existing total/paid/balance and encoding information remain in the existing summary drawer. Full Details retains complete content. Draft intake tables are excluded. Search metadata still contains client names; existing client-name searches continue finding the corresponding deceased/case even though client name is no longer displayed in the table. No controllers, routes, policies, sorting/filter choices or financial calculations changed.

## Applicability and ownership

- Applicable: visible Case/Master worklist headers/cells, column widths, empty spans and loading placeholder structure.
- Already consistent: summary drawer content, Full Details fallback and authorized interactions.
- Not applicable: draft intake table, Payment Monitoring, other module tables, new data/actions.
- Existing functionality missing: none.
- Shared owners: partials/case_worklist_head.blade.php and partials/case_worklist_cells.blade.php, used by staff/funeral_cases/index, admin/reports/master_cases and owner/cases/history.
- Width owner: partials/case_records_layout_styles.blade.php; existing horizontal scrolling and theme tokens retained. Loading owner: resources/js/skeleton-profiles.js, seven columns or eight for Owner, in matching value order.

## Verification

- CaseSummaryDrawerTest and CaseRecordPrintTest: three tests / 162 assertions passed. Covers headers/cells/actual balance, absence of secondary display content, retained search metadata and client query matching, all-role empty spans, existing summary/full detail, access denial and print authorization.
- Drawer and skeleton Node checks: 13 tests passed. Blade compile/cache-clear, production build and touched tracked-file whitespace checks passed.
- The broader RecordsEmptyStatePresentationTest fails on an unchanged Payment Monitoring empty-footer assertion at line 32, before completing its role loop. Payment views/backend were not changed. Focused Case/Master empty spans pass for all four actor types.
- Browser execution was previously declined and not retried. Light/dark responsive visual appearance, column fit and real pointer/keyboard interaction remain unverified. Automated/source checks are not visual acceptance.

This supersedes the prior eight/nine-column table and the proposal-only status of reducing table detail. Existing UI palette, connected toolbar/table, header dividers, row hover and drawer geometry are unchanged.
