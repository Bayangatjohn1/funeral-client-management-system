# Payment Monitoring Print Preview access

The current rule is admin-only (System Admin and Branch Admin). Staff retains Record Payment, but has no monitoring Print Preview button. `payments.monitoring.print` uses the existing `admin` middleware in addition to auth, no_cache and active, so Staff and Owner direct URLs receive 403. Existing controller branch scoping and report filters are unchanged. Per-transaction payment records and per-case history printing are outside this change.

Sources: `resources/views/staff/payments/history.blade.php`, `routes/web.php`. The temporary 901–1500px Staff two-action toolbar layout was removed from `_consistency_styles.blade.php`. This supersedes the earlier change exposing monitoring Print Preview to Staff.

Verification: four isolated role/tab Blade rendering checks passed. Registered print route includes admin middleware; direct middleware checks returned Staff 403, Owner 403 and Admin 200. Blade compilation/clear, route cache clear, PHP syntax and touched-file whitespace checks passed. Feature regression assertions were added to `PaymentUiConsistencyTest`, but PHPUnit could not start because its bootstrap readability check rejected vendor/autoload.php in this environment; full HTTP feature tests remain unverified.
