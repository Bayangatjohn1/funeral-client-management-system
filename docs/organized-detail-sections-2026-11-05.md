# Organized detail sections

Implemented with automated/source verification; browser light/dark and responsive checks remain pending after previously declined execution.

## Scope

- Applicable: Payment History/Payment Details drawer, Branch overview, User information and Service catalog detail iframe. Case/Master status text colors also updated.
- Already consistent: existing fixed headers/footers, close/focus behavior, record data and authorized actions.
- Not applicable: create/edit forms, confirmations/feedback, public/standalone service pages, database/routes, business rules or new workflows.
- Existing functionality missing: none.

## Current presentation

Case/Master Active text is green (#166534 light / #4ADE80 dark); Completed is neutral gray (#667085 / #98A2B3). Explicit data-case-status on the existing status component distinguishes case status from payment status, so Paid stays success green. Applies to compact tables and Case/Master summary identity; labels and values remain unchanged.

Final section owner is partials/record_detail_sections.blade.php, included after record_detail_modal_styles and record_drawer_styles. This supersedes their nested content-card styling while preserving drawer geometry and header/footer controls. Common content inset is 20px, section padding is 20px vertically, field gaps are 16px/20px, and section dividers use #B8CEC0 light / #355241 dark. Headings are 14px semibold. No shadows, decorative outer outlines or new content cards.

- User: three groups — Access and assignment (sign-in access, role, branch); Personal and contact information (name, email, phone, address); Record information (created date). All original fields/fallbacks remain. Content uses text fields and dividers instead of an access card or individual field borders. Status confirmation markup remains separate.
- Branch: Location, Operating status and Case records overview are separate divider sections. Removed nested backgrounds/borders/corners and metric cards; metrics use a left-aligned two-column text grid. Existing case totals/outstanding amount, status note, data and actions remain.
- Service: identity, price overview, statistics and content sections share aligned insets/dividers. Inclusion/freebie lists, stats, descriptions and empty notes have no nested card surface. Original pricing, dependency content, package usage, status dialogs and Edit/return flow remain. Only the existing iframe's record-service-drawer-body is affected.
- Payment: existing full-height drawer is 560px capped to viewport. Header/footer use shared surfaces and spacing. Case/branch/client/deceased context moves to the top of the scrollable body, keeping the header compact and content reachable at short heights. Context, payment summary and transactions are separated by dividers; summary values are a two-column text grid. Detailed payment groups retain all reference/recording/balance fields and actions without card surfaces. Existing transaction table remains horizontally scrollable. Existing open/back/print/correction logic remains.

## Verification

CaseSummaryDrawerTest, WorklistAdoptionTest and ServiceCatalogConsistencyTest passed 15 tests / 334 assertions. Ten Node interaction checks passed (Payment row selection/toolbar, Case drawer, Service archive). Blade compile/cache clear and touched tracked-file whitespace passed. No JS/CSS asset source changed in this revision; prior successful production build remains applicable. Real browser spacing/overflow, theme appearance, iframe hit testing and keyboard interaction remain unverified. Do not call these changes visually accepted.
