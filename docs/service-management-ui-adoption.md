# Service Management reference adoption

Reference: the user-provided ServiceManagementView.tsx. The React/context implementation is translated into the existing Blade and JavaScript architecture. No demo role switch, catalog sample data or additional unsupported model fields are introduced.

| Area | Implementation | Verification |
| --- | --- | --- |
| System Admin / Branch Admin hub | Reference header and role badge, full-width tabs, surfaces, cards and toolbar | Seven focused service authorization/filter/pagination tests pass |
| Add/Edit Package | Six steps: Details, Services, Inclusions, Freebies, Promo, Review; sticky action footer | Structured package tests and isolated browser modal flow pass |
| Casket / Add-on / Freebie forms | Shared reference presentation, paired fields, semantic theme tokens and action footer | Catalog workflow/consistency tests pass |
| View/Add/Edit modal | One parent title, subtitle, icon and close control; child Add/Edit headers hidden in frame. Direct Edit cancel closes; Edit from View cancel returns to View. | Blade compilation and isolated browser flow pass in light/dark themes at 1440px and 390px |
| Archive/Restore/availability | Explicit confirmation dialog before existing server mutation | Existing backend permissions/status tests pass |

Preserved: stored data, field names, routes, validation, server pagination, global catalog semantics, Branch Admin read-only permissions, existing staff intake integration, historical references, and current language. System Admin cards also expose direct Edit actions through existing modal routes.

Focused verification: seven service page tests plus seventeen Package/Add-on/catalog consistency tests passed in the initial adoption; the subsequent modal adjustment passed 14 focused Package/Add-on tests (243 assertions), frontend build, Blade compilation, and an isolated Dusk browser flow (32 assertions). A broader AdminScopeAuthorizationTest run also surfaced an unrelated assigned-branch intake failure; it was not changed in this UI work. Full manual visual review of both roles and all four tabs remains pending.

Follow-up: Add Package initially opened at Step 2 because the wizard counted a hidden, static casket hint as a validation error. Error-step detection now ignores hidden hints, and browser QA asserts Step 1 on a fresh Add modal (33 assertions total). The package dialog's rendered desktop screenshot was inspected after tightening its height and aligning the step bar, panel, canvas, and footer with the supplied reference. These changes are limited to presentation and client-side wizard state; no routes, controllers, validation rules, or persistence logic were changed.

Main-page follow-up: compared the supplied screenshot with rendered System Admin desktop/mobile pages. The header icon surface, unified tabs, compact toolbar with Grid/Table control, and calmer catalog-card presentation now follow the reference while preserving live catalog content, status, filters, actions, and Branch Admin read-only access. Light desktop, 390px mobile, and dark desktop screenshots were inspected. An isolated browser run passed 35 assertions, including modal flow, initial package step, mobile overflow, and dark inactive-tab contrast. The supplied screenshot's demo role switch and sample records were intentionally not copied. Full visual review of every catalog tab and Branch Admin remains pending.
