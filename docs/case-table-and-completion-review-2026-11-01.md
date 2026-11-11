# Case table and completion review — 2026-11-01

## Table changes

Shared worklist styles cover Staff Case Records and Branch/System Admin/Owner Master Case Records. Existing columns, formatting, data, filters, and links are retained. Corners are 6px; headers have no fill, regular 13px text and .055em letter spacing; primary values use regular 15px text. Names wrap. The action column reserves 172px for View, permitted Edit, and Print, with a transparent default and outlined hover/focus. Row hover/focus draws inset edges without changing geometry. Horizontal scrolling retains all columns at narrow widths.

`case_record_actions` owns role-aware links. Owner remains read-only. Edit uses the existing policy and edit route; other-branch records remain read-only. The new print route authorizes `view`, preserves Owner's verified-only rule, and reuses the existing record print template. It opens a print preview in another tab; it does not issue a funeral contract or submit a print job without the browser dialog.

## Completion findings

Read-only local audit on November 1 found 8 ACTIVE cases, all 8 past their case-level interment datetime and locally owned. None was missing `funeral_cases.interment_at`. Mode: standalone; application timezone: Asia/Manila. No matching direct PHP/artisan Windows task or running schedule:run/work PHP process was found in the inspected scope. Indirect wrapper services were not exhaustively ruled out. No live case statuses were changed during this review.

`cases:complete-past-interments` already runs every minute in Laravel's schedule. It changes locally owned ACTIVE cases with non-null `interment_at <= now()` to COMPLETED, writes completed_at, and synchronizes service_details through model events. It leaves DRAFT, future, missing-date, deleted, and remotely owned cases alone. Payment status/balances remain independent. Date-only values represented at midnight become due at the beginning of that day; this is existing behavior covered by tests, not a claim that the physical service has actually finished. Completion timestamp is processing time, so delayed dispatch records a later completed_at.

The rule only uses the case-level datetime. A legacy case with a date only on the deceased record can display a schedule yet remain ineligible; the audit found zero such missing-case-date rows in the current local ACTIVE set. The scheduler now prevents overlapping completion runs for 15 minutes, retaining existing branch-sync ownership safeguards.

## Hosting setup (deployment requirement, not installed remotely)

Configure the hosting cron dispatcher once, every minute, using the actual PHP binary and absolute application path:

```cron
* * * * * cd /absolute/path/to/funeral-system && /absolute/path/to/php artisan schedule:run >> /absolute/path/to/funeral-system/storage/logs/scheduler.log 2>&1
```

Configure log rotation for that scheduler log. Confirm the host supports one-minute cron, the CLI PHP/extensions match the application, MySQL is reachable, and storage/cache are writable. Do not put the command into a publicly accessible URL or run it from list-page GET requests. This dispatcher runs all configured scheduled jobs, including backups/retention, so validate those deployment settings too. Once installed, no recurring manual command or open browser is needed. A configured Laravel schedule alone does not start an operating-system dispatcher. Multi-installation branch sync requires the scheduler on each owning installation.

Verify deployment with `php artisan schedule:list`, host cron execution logs, and the read-only `php scripts/audit-case-completion.php`. In isolated staging, create an ACTIVE case due shortly, wait for the normal dispatcher, verify COMPLETED/service_details/completed_at, unchanged payment totals, and no change to a future or DRAFT case. Do not use a manual completion command as evidence that cron works.

Reference: [Laravel scheduler deployment](https://laravel.com/framework/docs/12.x/scheduling#running-the-scheduler).

## Verification matrix

| Consumer | Evidence | Remaining visual verification |
|---|---|---|
| System Admin master list | Native Edge accessibility tree and light desktop screenshot: three action links visible with unclipped row layout | Final header-fill fix, hover/focus, dark and narrow/intermediate widths |
| Branch Admin master list | Shared markup/styles and access policy reviewed | Light/dark desktop and narrow layouts |
| Staff Case Records | Shared actions and existing edit permissions reviewed | Light/dark desktop and narrow layouts, including draft intake table |
| Owner master list | View/Print access and draft denial covered by isolated print test; Edit remains policy-hidden | Light/dark desktop and narrow layouts |

The initial sandbox PHPUnit bootstrap failure was caused by PHP reporting the existing autoloader as unreadable, not by a missing vendor file. Tests run outside that sandbox use SQLite :memory:. Initial fixture errors were corrected without changing production data.

Final automated checks: 15 tests / 76 assertions passed (completion, print authorization, draft access, toolbar presentation); Vite build passed; Blade cache/clear passed. Live desktop screenshot was available, but further refresh/interaction was declined by the computer-use guard because the user was actively using that window. Remaining visual matrix entries are explicitly unverified.
