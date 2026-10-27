# Cross-page UI conventions

Use these decisions when improving a comparable panel page. Inspect the current staff source and effective styles first; these are presentation and interaction conventions, not permission to copy another page's fields, metrics or business policy.

## Page structure and hierarchy

- Keep one page title and a short, plain-English subtitle describing only that page's scope. Place page-level actions at the right of the title row; do not add a redundant topbar. When the page uses an icon-led header, show a relevant, decorative icon beside the title, with the same size and treatment as comparable pages.
- Use the staff page canvas, typography, surfaces and spacing in both themes. Organize the toolbar, tabs, content and pagination as separate surfaces where their staff counterparts are separate; do not wrap the entire page in an extra card. Preserve all live data and original actions.
- Before adding a KPI, verify its operational purpose and whether the same information already exists in analytics. Label its time basis clearly (for example, all-time cases versus cases recorded this month), and do not imply that an informational metric filters another page unless its destination and selected context actually work.

## Filters, lists and actions

- Put primary filters in task order: Search, scope (such as branch) when applicable, date, sort, More Filters, then Rows per page. Move lower-frequency filters into the existing More Filters control only when doing so preserves their options, selected state and query behavior. Keep Clear visible without creating a mostly empty extra row; show it only when filters are active.
- Each filter should have one visible outer surface and border. A nested input/select must not create an inner box. Check the actual open menu, keyboard focus, selected state, wrapping and result update—not only the static screenshot. Follow the page-specific filter reference where one exists.
- Grid and table representations of the same record must use the same icon for the same action, with consistent accessible names, tooltips, hover/focus/disabled states and meaning. Prefer distinct View (eye), Edit (pencil-square) and Disable/Enable (slash-circle/check-circle) iconography where those actions exist. Use the same status wording across card, table, detail view and confirmation. Do not add actions a role cannot perform.
- Keep table columns and live values intact. Use a restrained weight for ordinary row data; reserve stronger emphasis for identifiers or primary names. Ensure numeric values align with their headers. Wide tables scroll within their table region on narrow screens, without hiding columns or letting sticky headers cover filters.

## Modals, flow and feedback

- Reuse the existing modal/dialog shell and behavior. Modal titles have a relevant decorative icon in the header; use a context-appropriate icon for View, Add, Edit, Disable/Enable, loading and error states. Keep title, subtitle and close control aligned in both themes and at narrow widths. Do not add a second outer container around an already styled form panel.
- Use the shared viewport-wide scrim `rgba(15, 23, 42, 0.72)`, including over the sidebar. Keep footer actions grouped and separated from the close button. Preserve Escape/backdrop/close, focus return, scroll lock and existing validation behavior.
- Return Cancel/Back to the actual origin: a direct row/card action returns to the list; an action launched from a details modal returns to that details modal. Do not open a details modal after cancelling a direct action. Confirm the flow in both grid and table views where applicable.
- Show a blocking explanation only after the user selects the relevant status action or a check finds a blocker; do not show disable-policy warnings on an ordinary details view. After a successful status change, give a specific, plain-English success indication identifying the record and its new status. Ensure the resulting badge and available action reflect that status. Do not claim effects that the server has not implemented.

## Verification before reporting completion

- Compare the changed page with the actual staff reference at matching desktop, intermediate and mobile widths in light and dark modes. Exercise the changed controls, open states, tooltips, modal flows, validation and post-action feedback with isolated test data where writes are involved. A build or source inspection alone is not visual verification.
- Run the relevant build, Blade and focused workflow tests required by the main skill. Check the rendered browser state, including layout overflow, icon visibility and click targets, before declaring visual parity. Report any states that could not be tested rather than assuming they work.
