# Shared filter adoption status — 2026-10-07

Implementation: shared panel presentation layer, no filter logic changes. Scope follows GET toolbars and explicit record/report/analytics filter groups; all panel roles receive the same light/dark tokens. Print/auth and POST account/case forms excluded.

| Consumers | Status / evidence |
| --- | --- |
| Branch Admin Audit Logs | Desktop dark computed font/surface checked; existing date/filter behavior preserved |
| Branch Admin User Management | Desktop light/dark 44px sage controls checked; options/three-field toolbar intact |
| Branch Admin cases/payments/services/reports/analytics | Shared rules implemented; route-by-route visual QA still pending |
| Staff lists/reminders/payments/cases | Source consumers mapped; role/viewport visual QA pending |
| System Admin directories/catalogs/reports | Source consumers mapped; role/viewport visual QA pending |
| Owner history/sales/analytics/reports | Source consumers mapped; role/viewport visual QA pending |

Build and Blade cache/clear passed. Five isolated feature tests passed (29 assertions): directory filter scope, audit log scope, catalog pagination/search, analytics branch scope and report branch scope. These do not substitute for browser QA of other roles or mobile widths.
