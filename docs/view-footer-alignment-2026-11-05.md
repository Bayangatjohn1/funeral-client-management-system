# Superseded: View footer Close alignment

The user chose X-only dismissal for View drawers. Footer Close and empty footers were removed; useful actions remain left. Use [the current X-only decision](view-drawer-x-only-2026-11-05.md). The alignment notes below are historical.

Current implemented rule for Case Records, Master Case Records/Owner history, Payment Monitoring, Branch, User and Service View drawers: Close stays right; any existing additional footer actions stay left. Header X stays top-right. Add/Edit/confirmation buttons are excluded.

Case/Master moves Open Full Details before Close in DOM order, with space between them; focus order follows the visual order. Branch/User receive View-only end alignment; Service already has a right-aligned read-only Close.

User alignment follow-up: an older user-information-close-action rule still had margin-right:auto, which overrides the expected placement even when its flex parent uses end alignment. Replaced that original rule with margin-left:auto and margin-right:0 in user_management_visual_styles. This fixes the source conflict rather than adding another footer override. Confirmation buttons are unaffected. Blade compile/clear and touched-file whitespace passed; browser appearance remains pending.

Payment reuses its existing footer Close outside the switchable history/detail action groups. Print history stays left; Details Back/Print stay left. The same Close remains visible in either pane without adding a second control. A two-column footer keeps Close on the right while left actions wrap on narrow screens; inactive groups remain hidden. Existing close handlers, back/history switching, print URLs and focus logic remain unchanged.

Owners: partials/case_summary_drawer.blade.php, partials/record_drawer_styles.blade.php, staff/payments/history.blade.php and Payment rules in partials/record_detail_sections.blade.php. No new fields/routes/actions, shadows or outer outlines.

Verification: nine feature tests/278 assertions (CaseSummaryDrawerTest and WorklistAdoptionTest), eight Node interaction checks, Blade compile/cache clear and touched tracked-file whitespace passed. Assets unchanged. Browser actual alignment, narrow wrapping and focus/hit testing remain unverified after previously declined execution.
