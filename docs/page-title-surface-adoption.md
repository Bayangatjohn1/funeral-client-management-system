# Shared page title surfaces — 2026-10-13

User requested the Reports & Analytics title background, position and spacing across panel pages. Presentation-only update; routes, data, permissions, actions and print output preserved.

Shared owner: `resources/views/partials/page-title-surface.blade.php`, included by the panel layout. Explicit `panel-title-surface` markers distinguish page titles from record names, modal titles and printed headings. Screen-only styles use staff light/dark surfaces, 24px desktop / 16px phone padding, 24px / 18px corners, and Plus Jakarta Sans 700 titles. Main dashboard/report/catalog wrappers share the panel content gutter and 12px top padding. Generic headers retain their existing action slots. Reports scope controls remain outside the title card. Legacy flat-header rules exclude the new component instead of overriding its surface.

| Consumers | Role | Implementation / evidence |
| --- | --- | --- |
| Generic lists, forms, detail and profile headers | All panel roles | Shared layout marker; System Admin users, branches, payments, audit logs and backup pages inspected at desktop; User Management also inspected at 390px light and desktop dark |
| System Admin dashboard | System Admin | Header marker; light/dark rendered, 390px dark without document overflow |
| Staff / Branch Admin / Owner dashboards | Respective roles | Existing header markers added; Owner duplicate generic header suppressed; source and feature rendering checked, role browser sessions unavailable |
| Reports / Analytics | Admin and Owner | Marker on existing inner title card, preserving scope badge and right tabs; 1440px light/dark and 390px light checked |
| Service Management / Retention | Admin | Existing header markers; desktop light/dark inspected; Retention duplicate generic header suppressed |
| Intake / Saved Drafts / Edit Case / Contract Preview | Operational roles | Existing header markers; source checked, browser verification pending role access |
| Payment Corrections | Branch Admin | Existing header marker; duplicate generic header suppressed; browser verification pending role access |

Validation: frontend build passed; Blade compilation passed and cache cleared; feature selection ran 53 tests, 50 passed and 3 failed (324 assertions). ReportsModuleTest and SystemAdminDashboardTest passed. DashboardAccessTest failures concern unchanged expectations for `Managing Main Branch and all branch operations`, controller view data `selectedBranchId`, and `Staff Dashboard`. Header edits do not change these text/data values. No changes made to these expectations or controller behavior.

Some immediate post-navigation reads returned the previous page; those are not accepted as verification of the requested destination. Other role pages are implemented but visually unverified, not marked complete. Print/PDF, guest/auth and dialog headings are outside this title-card update.

Preview: `storage/app/shared-page-title-desktop.jpg`.


## Compact headers - 2026-10-20

All panel-role standard title surfaces now use 26px/600 desktop titles, 22px mobile titles, 16px vertical/20px horizontal desktop padding, 14px/16px mobile padding, and 16px/14px corners. Subtitle text is 13px with a 4px title gap. Existing scope badges and actions remain; no fixed header height is imposed. Owner executive dashboard uses equivalent compact heading typography while preserving its embedded filters. Auth, modal headings, and print/PDF are outside this change.

Owner Records desktop measured 89.6px header height, 26px title and 16px/20px padding in browser. Other-role rendering is checked by existing smoke tests; visual checks require those role sessions.
