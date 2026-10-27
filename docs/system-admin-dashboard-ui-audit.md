# System Admin dashboard audit — October 13, 2026

Extended refined row feedback to Branch Coverage and Recent Activity: 8px corners, 10px text inset, inset dividers and shared subtle background blend. Eye actions remain independent. Verified branch pointer hover, both families' keyboard feedback in light/dark, and mobile overflow. Build/Blade and 6 tests/33 assertions passed. Screenshot: `storage/app/system-admin-activity-hover.jpg`.

Metric hover refinement: 8px row corners, 10px horizontal content inset, subtle theme-token background blend instead of a strong square block, and inset noninteractive divider. Actual pointer hover verified in light/dark, keyboard focus verified, mobile overflow checked. Build and Blade compilation passed. Screenshot: `storage/app/system-admin-row-hover-refined.jpg`.

Spacing/reload cleanup: removed the Updated at/page-refresh row and its JS listener at the user's request. Lower panels now use 4px top padding, 8px bottom padding and shorter rows with 44px minimum targets. Both lower panels measure about 344px at 1440px (previously 412px) with identical 4px first-row offsets. Actual pointer hover exercised through supported browser pointer drag, verifying the metric and eye surface changes and overview tooltip; keyboard focus also verified. Both themes and mobile overflow checked; no console errors observed. Build and 6 dashboard tests/33 assertions passed. Screenshot: `storage/app/system-admin-compact-spacing.jpg`.

Panel balance correction: Branch Coverage and Recent Activity now share a 560px desktop shell and 84px header. Both use fixed filter/count areas and scrolling lists, with identical list start coordinates verified in both themes at 1440px. Activity count reflects its filter. At 390px both lists return to natural page scrolling with no horizontal overflow. Lower metric panels are not affected. Build/Blade passed, 6 tests/33 assertions passed. Screenshot: `storage/app/system-admin-balanced-panels.jpg`.

Scope: UI/UX review only, before implementation. No application files edited. Reference: Staff UI Reference and cross-page conventions.

## Verified

- Authenticated `/admin`; current dataset has 3 active branches, 2 managed active accounts, 3 attention issues, 2 active packages and 1 available casket.
- Opened and closed all 20 overview dialogs. Native modal state, viewport scrim, representative focus wrap/Escape/focus return and footer destinations verified.
- Branch filters: All, Needs Admin (2), Inactive (0 plus empty state).
- Staff card reaches active staff filter (1 row); available casket card reaches available catalog filter (1 row); activity overview reaches the exact audit event (1 row). Dashboard return links work.
- Desktop 1440px and mobile 390px in light/dark; 900px intermediate layout. No horizontal document overflow. Refresh and reminder popover exercised; no console errors observed.
- Existing `SystemAdminDashboardTest`: 5 passed, 27 assertions, isolated SQLite memory database.

## Findings and recommendations

1. Needs Attention rows show an eye but no visible destination text; full-row links are available to keyboard/AT. Show one clear destination label on the row, preserve distinct overview behavior without duplicate destination controls. Use compact issue / affected scope / priority / next step presentation. Group the two branch coverage warnings with an expandable affected-branch list; retain the critical backup issue first. Backup overview currently repeats Issue/Next step without last verification date; add meaningful context from existing backup data. Do not change the existing 26-hour policy through a visual edit.
2. Branch Coverage: add a compact covered/needs-admin/inactive summary and a Covered filter. Current cards repeat coverage warnings but are useful as a complete list; retain branch name/code and admin/staff counts. Both Branch 2 and Branch 3 have 0 admins and 0 staff; do not invent a critical staffing rule. Alert branch-admin destination currently includes active and inactive accounts: label the destination accordingly or provide explicit status selection/context, rather than pretending it matches the active-count condition.
3. Recent Activity: three of five visible entries are MFA verification events; preserve audit completeness but offer a filter for configuration/account/branch changes and label any changed time/event scope. Use clearer action wording (MFA rather than Mfa), affected-record context and visible result. The metadata overview casts booleans to strings, which can produce empty→1; map boolean status values to readable terms. This defect is source-confirmed, not exercised by a matching live event.
4. Users & Access: Active System Admins shows 0 while the signed-in System Admin exists. Count intentionally excludes self and Owner and matches directory scope; rename to Other Active System Admins or place the scope alongside the metric, rather than changing counts silently. General metric overviews repeat the metric and generic text; add useful role/scope context, or use direct drilldown as primary interaction.
5. Service Catalog: available casket drilldown contains a Price needed item; Available is not the same as fully configured for pricing. Add a separate readiness notice/count with configuration context. Zero optional add-ons/freebies should remain informational, not automatic critical issues. Present active packages, caskets, paid extras and freebies as distinct configuration groups. Hearse catalog remains future work; do not display a fabricated count.
6. Header reminders: bell has 8 case/payment reminders with URLs scoped to branch 5, while Needs Attention has 3 recovery/access/assignment issues. Make both scopes explicit; recommend case reminders label plus branch context and System issues label/description. Any system-wide reminder aggregation is a separate data-scope change.
7. Layout: desktop panels force 520px or 460px heights and nested scroll regions, causing considerable empty space beside short metric lists. Reduce arbitrary fixed heights while retaining bounded long lists; mobile already uses normal page scrolling. Keep responsive density and staff tokens. Overview headers currently lack the decorative context icon required by cross-page conventions.

## Limits

No account/branch/catalog writes, backups, restore, status changes or deletion performed. No inactive branch or unassigned-staff live records; healthy/critical ordering and count scopes covered by existing isolated tests. Not every metric destination was opened (representative user/catalog/audit destinations were). All 20 overview opens were exercised at desktop; representative mobile/themed dialogs were exercised. Pointer-hover persistence and a large branch dataset remain unverified.

Baseline screenshot: `storage/app/system-admin-dashboard-audit.jpg`.

## Authorized compact attention implementation

## Authorized container redesign and redundancy cleanup

Healthy dashboards omit Needs Attention completely; verified by isolated rendered-response assertion. Repeated branch-admin warnings now form one actionable summary linked to Branch Coverage, which selects Needs Admin. Underlying controller attention data, priority and policy remain unchanged. Warning severity and backup verification context remain visible when expanded.

Users/access and catalog counts now use compact rows with right-aligned values and direct filtered destinations; nine count-only overview dialogs removed. Branch and activity overviews remain. Lower containers match natural row height (about 412px each at 1440px), without a fixed panel height. Branch/activity rows use dividers, section icons and matching filter controls. Added Covered filter. Activity filter applies only to the latest five records, explicitly described; configuration/access, security and other categories retain full Audit Logs destinations. MFA casing, visible result and readable boolean changes improved.

Browser verified warning expansion/collapse and grouped action, Covered filter, configuration/security/other activity filters and no-match state, user/catalog drilldowns and return, retained branch modal, light/dark at 1440px/390px and 900px intermediate, no document overflow. Current live backup became verified during user activity; no backup actions were performed by this audit. Healthy full dashboard is feature-tested rather than forced through production-data changes. Build, Blade compilation and 6 dashboard tests/33 assertions passed. Screenshots: `storage/app/system-admin-dashboard-redesign-top.jpg`, `storage/app/system-admin-dashboard-redesign-panels.jpg`.

Needs Attention remains above KPIs with a collapsed summary by default: highest-priority message, total count and critical/warning counts, plus Review issues. The toggle exposes the original ordered issue list and overview/destination actions; aria-expanded/controls track the state. Existing count logic and recovery policy are preserved. Renamed the self-excluding System Admin metric to Other Active System Admins. Replaced forced panel heights with maximum heights while keeping bounded scroll regions.

Verified collapsed desktop height about 104px, expanded 3 issues, overview Escape/return, collapse/reopen, and light/dark at 1440px and 390px without document overflow. Build, Blade compilation and existing 5 tests/27 assertions passed; view cache cleared. Screenshot: `storage/app/system-admin-dashboard-compact-attention.jpg`. Other audit recommendations remain proposals rather than implemented changes.
