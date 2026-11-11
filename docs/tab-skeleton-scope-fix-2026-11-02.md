# Internal tab reload review — 2026-11-02

The earlier fix scoped the skeleton to a results panel but still scheduled a normal location assignment for unhandled tab links. That did not satisfy the user's requirement to keep the current document while changing tabs. The user requested a review of all existing internal tabs; the normal-navigation fallback is now superseded.

The shared layout excludes recognized tab links before scheduling navigation. Page-specific handlers own fetching/local switching; the global handler still owns actual sidebar/page navigation. Existing modified/new-tab clicks retain normal browser behavior.

| Existing tabs | Source audit and current handling |
|---|---|
| Dashboard activity/schedule | Existing local button switches; no document navigation introduced. |
| Staff Case Records | Existing capture-phase fetch/list replacement retained. Request failure stays in place; obsolete competing popstate reload removed from app.js. |
| Admin and Owner Master Case Records | Shared master_case_tab_navigation partial; Admin's existing async loader reused for Owner. Abort/stale-request handling, server URLs, branch scope and selected state retained. Errors restore prior tab state and show an inline message. |
| Service Management | Existing loadSection fetch/reinitialize pipeline retained; global listener never assigns location for these tabs. |
| Deceased quick filters | Async list/tab/footer replacement; toolbar and modal retained. Delegated detail links still work after refreshed rows arrive. |
| Payment Records / Correction Requests | Existing fetchFilters pipeline owns section links and Back/Forward. Reinitializes correction approval and payment interactions after response. Missing content/network failure stays in place with an error. |
| Schedules sections/views | Existing fetch/abort/replace flow retained. Missing response content uses the inline error flow instead of document navigation. |
| Reports / Analytics | Existing module fetch owner retained; report-type and analytics-view buttons remain with their Alpine/local handlers. |

Sources changed: shared layout; master_case_tab_navigation partial shared by Admin/Owner; deceased_tab_navigation partial; Staff deceased/case/payment views; reminder-navigation partial; app.js legacy tab behavior; panel-skeleton.js correction-results target. No controller, route, database, permission or approval-submit behavior changed. New async ownership is limited to existing previously-native tab controls covered by the user's no-reload request.

Verification: broad focused suite passed 56 tests / 544 assertions (browser handlers, loading boundaries, cross-role script renders and existing case/payment/report regressions); all 11 Node tests passed. Final focused Payment history-loader and cross-role checks passed 3 tests / 175 assertions. Browser tests execute actual case/deceased/payment handlers with controlled response promises and errors; assert unchanged heading DOM identity, repeated panel updates, stable location/history, usable controls and real Payment Back/Forward. Blade compilation/cache clearing and Vite build passed.

Evidence limits: source audit covers all discovered tab families, with isolated behavioral and cross-role render checks. Every live role session was not manually exercised. Existing async Schedules placeholder-shape and Payment Monitoring alias-profile gaps remain separate from document reload behavior.
