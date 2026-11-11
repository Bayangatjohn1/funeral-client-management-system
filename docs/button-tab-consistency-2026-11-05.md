# Service reference: buttons and case tabs

UI-only scope: Case/Master search, Branch/User search and Add actions, Payment search and Print Preview, Service search and its existing section Add action; Staff Case and Branch/System/Owner Master tabs. Routes, handlers, labels, permissions and component positions are preserved. Row actions and modal/destructive actions retain their existing semantic styling.

Latest user revision: avoid dark green as the main fill. Main actions, Search and selected tabs use medium sage `--reference-action-bg` (#A9C9B5), button hover/pressed `--reference-action-hover` (#92B89F), and dark text `--reference-action-text` (#123524) in both themes. Selected tabs retain their default fill on hover. Existing 84px trailing search buttons and right-hand 13px corners remain. Shared tokens are scoped to the specified page containers. Global submit rules exclude these search buttons so component styles own their appearance.

Existing page actions use `.reference-page-action`: #A9C9B5/#123524 default, #92B89F/#123524 hover/pressed/focus; no shadow or movement. Service Add remains in `.service-section-intro`, separate from the page header.

Case tabs adopt Service's 12px corners, 44px minimum height, 10px/14px padding, 8px icon gap and 14px/600 typography. Default is transparent/muted; hover uses the existing muted surface; selected remains #A9C9B5/#123524 during hover. Existing status tabs gain matching 16px Bootstrap status icons; no new tabs or navigation are introduced. Service retains its original icons and counts. Existing surrounding wrappers and responsive layout stay in place.

Sources: `partials/shared_filter_ui`, `partials/services_visual_styles`, `partials/worklist_consistency_styles`, `partials/case_records_layout_styles`, affected index/list views, and the generic submit exclusions in `resources/css/app.css`.

Applicable: shared search/action colors and tab appearance. Already consistent: existing search widths/right-hand corner geometry. Not applicable: backend/filter changes, relocating actions, changing row-action semantics, adding tab counts to case pages.

Build and Blade compilation passed. Focused browser verification uses isolated SQLite role-rendered fixtures in `WorklistAdoptionTest::test_service_reference_search_actions_and_case_tabs`, both themes and 1440/390 widths. Focused browser comparison passed: 1 test / 174 assertions across the checked role renders, both themes at exact 1440/390px viewports, including label clipping checks. Screenshot review confirmed readable status labels and icons after removing the legacy fixed tab widths. Earlier outlined, fully rounded case tabs are superseded by this user request. The medium-sage revision passed the same 174 browser assertions. Branch Management's legacy header action override now consumes the shared tokens. Full live-session acceptance is separate from isolated fixture verification.
