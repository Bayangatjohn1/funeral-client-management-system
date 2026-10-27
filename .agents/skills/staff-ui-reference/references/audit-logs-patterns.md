# Branch Admin Audit Logs

View log details opens a centered native dialog, rather than an inline table popover. Include event date, user, branch, action and target context before existing remarks/result/changes/snapshot/security sections. Preserve authorized deferred metadata loading. Use shared filter theme tokens and Plus Jakarta Sans, a sticky title/44px close button, Escape/backdrop dismissal and return focus to the eye action. On mobile use one-column context, wrapping long values and viewport-limited modal scrolling; retain all authorized information. The panel layout does not render a scripts stack: initialize this page's handlers from its rendered content section.

Date-menu chevron follows the native details open state: rotate 180 degrees when open and reset when closed, with reduced-motion support.

Custom Range now opens a native date dialog using the shared modal visual pattern; do not put date inputs inside the preset popover. Draft dates are separate from submitted hidden date_from/date_to values. Valid complete drafts auto-apply after a short debounce; Cancel/Escape/backdrop discards drafts and returns focus. Date trigger matches the other sage controls' 44px height, border/radius and typography; retain the preset menu/checkmarks.

Filters now auto-apply: user/action/page-size select changes and date presets submit immediately. Custom dates submit after both dates are complete and ordered, with a short debounce; incomplete/reversed ranges show inline guidance and do not submit. Date menu has an Event date heading, selected-state checkmark, labeled From/To fields and status text. Manual Apply is a noscript fallback only. This supersedes the earlier Apply-only behavior.

Reuse `partials/case_records_pagination` with the logs paginator, which renders the shared table pagination component and record-count state. Preserve filter query parameters and transparent detached footer.

Audit detail text and strong labels use `--ink`, headings use `--ink-muted`, and dividers use `--border` in both themes; legacy dark text must not survive inside the popover. Remove fixed/max heights from the list and every table wrapper so vertical scrolling belongs to `.page-content`; horizontal overflow remains confined to the table wrapper.

Rows per page is now an inline label/select in the primary toolbar; do not wrap this single setting in More Filters. Assigned-branch restrictions stay enforced on the server.

Compact Branch Admin toolbar: All Users, Any Action, one Date Range menu, More Filters, then Clear/PDF/Apply. Date menu presets are All Dates/Today/This Week/This Month/This Year/Custom Range, setting original date_from/date_to fields; only custom reveals labeled From/To. More Filters holds page size and assigned-branch information. Preserve backend scope and Apply submission; preset selection alone does not execute a search. Menus close outside/on Escape, remain above the table, and wrap on mobile.

Use `partials/audit_logs_visual_styles.blade.php` with staff foundation and shared sticky emphasis. Preserve user/action/date/page-size/Clear/PDF/Apply controls, assigned-branch chip, full table columns, timezone formatting, action/status semantics and authorized details loading. No search field exists currently; do not add one as part of visual adoption.

Use transparent page and outer list, independent sticky card at 16px inset, complete 44px sage controls, three/two/one-column responsive filters, horizontal table scroll and transparent detached pagination. The 44px eye summary opens the existing details and shows View log details tooltip on hover/focus. Preserve remarks/result/change/snapshot content and server branch/target-branch access checks. Keep System Admin presentation scoped separately.
