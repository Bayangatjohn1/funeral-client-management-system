# System Admin dashboard implementation and QA

Scope: System Admin dashboard and dashboard return navigation. User Management visual adoption remains a separate task. Reviewed against the current staff UI foundation. Account permissions, write workflows, and other dashboards are preserved.

## Implemented plan

| Area | Result | Evidence |
| --- | --- | --- |
| Summary accuracy | Managed active/inactive users exclude Owner and current actor, matching directory scope | Isolated feature tests and filtered user navigation |
| Issue priority | Recovery critical issue precedes access warnings; specific next actions | Feature test; actual dashboard |
| Branch coverage | Needs Admin first; All/Needs Admin/Inactive filter; initial preview of four; inactive is not Covered | Browser filters; isolated coverage assertions |
| Overview | Shared native dialog component; eye buttons and separate destination links | Every available dialog opened at 1440px and 390px |
| Tooltip | Fixed viewport-positioned tooltip, keyboard focus, Escape dismissal, hover handlers, screen-edge clamping | Keyboard tooltip visible and within mobile/desktop viewport |
| Dialog behavior | Viewport scrim, scroll lock, initial focus, Tab/Shift+Tab containment, Escape/close/backdrop dismissal, focus return | Actual browser interaction checks |
| Reduced duplication | Three summary cards; compact catalog metrics; Owner metric and duplicate audit/backup shortcut sections removed | Source and rendered page |
| Activity | Five latest events, affected record, actor/time, overview with allowlisted changes only | Browser and confidential-data exclusion feature test |
| Navigation | Destination filters retained; local Back to Dashboard link; user directory filters preserve return marker | Actual overview-to-users-to-dashboard round trip |
| Refresh | Manual reload with busy/status feedback | Actual browser reload; enabled again on completion |
| Themes/layout | Staff foundation mapped into legacy panel tokens; corrected dark heading/description contrast | 1440px desktop, 900px intermediate, 390px mobile; light/dark |

## Automated validation

- Production Vite build passes. Sandbox filesystem restrictions required an approved build outside the sandbox.
- Blade view compilation passes; generated view cache cleared afterward.
- Dedicated SystemAdminDashboardTest covers scope/count alignment, coverage status, recovery ordering/healthy state, dashboard return, and safe activity details.
- Broader DashboardAccessTest and AdminScopeAuthorizationTest run: 48 passed, four failures. Failures concern legacy operational-dashboard expectations, staff title expectation (current staff title is Dashboard), and missing intake payment-selection input. These source expectations/workflows predate this dashboard implementation; no intake or staff-page edits were made here.
- Existing unrelated trailing whitespace in reports/index.blade.php remains outside this task.

## Verification limits

- Browser QA used existing authorized System Admin records. No accounts, payments, cases, recovery, or activation operations were submitted.
- Show all branches with more than four records and inactive-branch display were not browser-exercised: the available dataset contained three active branches. Inactive/healthy coverage is covered in isolated feature tests.
- Tooltips were exercised with keyboard focus and viewport-bound checks; pointer-hover persistence was source-reviewed, not independently mouse-hover tested.
- Other roles' rendered pages were not opened under separate authenticated accounts. Shared return navigation is conditional on System Admin and an exact local dashboard URL; existing authorization tests were run.
- Browser theme transition can be aborted by resizing mid-transition. Responsive checks used clean reloads after theme/viewport changes; no new animation was introduced.
- No credentials are stored in this note.

## Section balance refinement

- Desktop uses two equal-width columns. Each paired panel is 520px tall, with a fixed header and independently scrolling body. Branch filter/status and the Show all control remain outside the scrolling branch list. Needs Attention has a 340px maximum list height, keeping its total count visible in the header.
- Catalog metrics are visible immediately, preserving all overview/destination actions and avoiding a large empty collapsed panel beside Users & Access.
- Intermediate screens stack panels and bound list height. At 700px and below, panels return to natural height and normal page scrolling.
- Scroll regions have accessible names, keyboard entry, and visible focus. PageDown scrolled the Recent Activity region in the actual browser.
- Browser checks: paired heights measured 520/520 in both themes at 1440px; 390px mobile bodies had natural height without nested scroll; no horizontal overflow at 390px or 900px. Overview modal opened from a scrolling section and fit the mobile viewport.

## Card navigation cleanup

- Cards now contain one semantic full-card destination link and a separate eye control; visible repeated action links are removed. Eye hit targets remain above the card link, and native dialogs remain in the top layer.
- Removed Add User, Add Branch, and Add Package shortcuts. Needs Attention cards show a contextual explanation without an Access review chip or repeated review link.
- Branch status filters All statuses / Needs Admin / Inactive inside the bounded list. Removed the expansion button; one section View all opens Branch Management. Each branch overview has one View Branch action, matching its card destination; no Manage Branch Users action.
- Service Catalog has compact two-column metrics and one full-width final metric. Lower paired panels are 460px tall on desktop; Branch Coverage/Recent Activity remain 520px.
- Recent Activity remains server-limited to five events. Card and overview links pass audit_log_id. Audit Logs displays the selected event context, retains it through filter changes/pagination/export, and Clear removes it. Server filtering preserves branch scope.
- Browser checks confirmed card navigation to filtered Users and available Caskets; eye opens an overview without navigating; event overview opened Audit Logs with exactly one matching row; branch overview has one action. Dark 390px mobile modal fit without page overflow.
- Dedicated tests now include five-event limit, exact event filtering, export parameter preservation, and cross-branch isolation. Staff UI reference now prohibits adding redundant controls for the same purpose.
- Skill frontmatter and the requested rule were manually validated. The bundled quick validator could not run because the local Python environment lacks PyYAML; no dependencies were installed.
- The final 460px lower-panel refinement built successfully. A final browser replay after that small height adjustment was interrupted by browser debugger synchronization failure; preceding card/navigation/modal checks completed successfully.

Hover consistency review: replaced transparent card-link overlay with the staff hover surface on all clickable card families; kept semantic warning/critical hover colors. Unified 150ms control transitions, added reduced-motion support, disabled refresh feedback, and inset card focus rings to avoid scroll clipping. Build and Blade compilation passed. Browser verified loaded styles, keyboard card feedback in dark (#1B3527), light state, and no horizontal overflow. Pointer hover replay remains unverified because the exposed browser API has no hover action.
