# System Admin staff UI adoption

Scope: System Admin only, with shared presentation selectors extended to both admin roles. Staff and Branch Admin behavior/permissions remain unchanged. No live writes for QA.

| Area | Views/reference | Preserved workflow | Status |
| --- | --- | --- | --- |
| Shell/dashboard | panel, sidebar, system-admin dashboard; staff foundations | navigation, notifications, KPIs | implemented; main responsive/theme matrix checked |
| Master Cases/details/edit | master_cases, case detail/edit; staff records/detail | all branches, columns, filters, edit actions | shared styling enabled |
| Payment Monitoring | shared staff/payments/history | filters, summary, transactions, print | shared styling enabled |
| Users/create/edit | directory/_create_form/edit; staff foundation | role/scope/branch/page-size, account actions | shared styling enabled; System toolbar keeps six controls |
| Branches/create/edit/dialogs | branches/*; staff cards/forms | metrics, Grid/Table, create/edit/status | dedicated styling added |
| Services | service hub, package/casket/add-on/freebie CRUD/detail frames | all write/read actions, filters, tabs, records | shared + form styling enabled |
| Reports/analytics | reports/index and report modules | all report types, charts, filters, exports | foundation enabled |
| Audit | audit_logs | multi-branch filters, event context, details/export | shared styling enabled |
| Backup/Recovery | backup-recovery | create/check/download/restore request | dedicated styling added |
| Retention | retention | filters, status, legal hold controls | dedicated styling added |
| Profile | profile/edit | details/password/contact forms | foundation/card styling enabled |

UI UX Pro Max was consulted. Its design-system searches returned landing-oriented patterns that do not fit this existing admin application; those outputs were not persisted or applied. Staff is the authoritative palette/font reference. Applicable general guidance: consistent 150ms feedback, visible focus, 44px controls, reduced motion, readable table scrolling, preserved semantics.

Verification (2026-10-09/10):
- Asset build and Blade compilation passed; generated view cache cleared.
- Main System Admin pages (dashboard, cases, payments, branches, users, services, analytics, audit, backup, retention, profile): loaded and no document overflow at 390px in light/dark, 900px dark, and 1440px light/dark. Wide tables retain horizontal scrolling.
- Browser exercised branch create native dialog (Tab wrap, Escape, return focus, scroll lock); user eye overview (desktop/mobile fit, focus entry/wrap, Escape); audit details; payment summary/transactions; catalog Add Package iframe and casket overview; backup create/restore dialogs; profile edit focus entry.
- Catalog iframe body/headings use Plus Jakarta Sans and inherited dark staff surfaces. Standalone create forms for users/branches/packages/caskets/add-ons/freebies and existing branch/package/casket/user/case edits were read without submission. Secondary routes checked at mobile dark and selected light widths; the full secondary-page theme matrix remains unverified because direct browser navigation intermittently aborts.
- UI UX Pro Max targeted `focus not obscured` UX guidance was verified as a relevant Web accessibility result. Added scroll padding/margins, focus containment and disabled the tall six-control directory sticky toolbar on phones.
- Dashboard tests: 5 passed/27 assertions. ReportsModuleTest: 38 passed/243 assertions. AdminScopeAuthorizationTest: 40 passed/1 existing intake failure (fixture omits required payment selection). New SystemAdminUserEditTest: 1 passed/2 assertions, covers rendering and active/current-inactive branch options.
- Fixed existing System Admin Edit User 500: replaced unsupported orWhereKey with orWhere('id', ...), preserving active branches plus the user's current branch.
- No production records/accounts/credentials/backups/holds were changed. No credential values were saved in this note.

Remaining verification limitations:
- No existing add-on/freebie records in this session for their detail/edit states, and no retained records for legal-hold control visual states; shared form/detail templates and existing server authorization were inspected.
- No separate authenticated Staff/Branch Admin browser session for live comparisons. Existing selectors retain equivalent specificity and Branch Admin applicability; isolated role tests passed except the pre-existing intake fixture failure.
- Pointer-hover replay is unavailable in the exposed browser API; hover source/cascade and keyboard equivalent states were checked.
- Print/PDF output is intentionally preserved and outside the visual adoption scope.
