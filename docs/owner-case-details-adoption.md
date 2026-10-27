# Owner Case Details adoption

Adopted the actual Staff Case Full Information structure and shared visual layer on `/owner/cases/{case}`. The supplied dashboard TSX is not a full case-details implementation.

- Top Back to Records action preserves the current records URL, including search, filters and page.
- Consistent Case Full Information heading, section navigation and shared scroll behavior.
- Package/inclusions/add-ons precede schedule; photo follows the service grid.
- Reused Staff field grouping, typography, spacing, financial breakdown and responsive columns through the existing shared partial. Owner palette aliases are scoped to pages with `.case-detail-shell`.
- Photo Details uses the existing overlay focus containment/return, Escape, backdrop and scroll lock behavior. Role policy checks still determine document/photo/payment actions. No uploads, payment posting or live record changes were performed.

Verified Owner desktop light and narrow 390px dark, horizontal section navigation without document overflow, photo empty-state overlay/focus/scroll lock/Escape return, section hash preserving return query, and filtered Master Case Records return URL. Screenshot saved in `storage/app/owner-case-details-adopted.jpg`.

Build and Blade compilation passed. Owner, case snapshot and tarpaulin regression selection: 28 passed, 324 assertions. Fresh Staff/Branch Admin authenticated browser sessions were not exercised; existing selector branches are preserved while extending coverage to Owner. A live uploaded-photo fixture was unavailable, so full-image viewing was source-reviewed rather than newly exercised.

## CaseDetailDrawer.tsx Full Dossier follow-up

Reference: `C:/Users/johnk/Downloads/CaseDetailDrawer.tsx`. Owner now uses its numbered full-width section organization, pale rounded surfaces, plain inset divider headers, compact uppercase field labels, grouped birth/death dates, separate package/schedule/photo sections and payment settlement presentation. The container remains a full page as agreed.

Existing Blade nodes are regrouped rather than recreating their values. All existing snapshot inclusions, charges, discounts, tax, transaction values, documents, source information and policy-gated actions remain rendered from the original shared partial. Prototype sample documents and unsupported edit/payment actions were not imported. The settlement percentage is a display calculation from the existing paid and agreed totals, with no database writes.

Browser comparison on FC0001: 48 value blocks before and after, exact sorted multiset equality; original filtered Back URL retained. Light desktop and dark mobile schedule/photo layout, document overflow and overlay Escape/focus return checked. Final regression selection: 28 passed, 324 assertions. Existing shared Staff/Branch Admin partial behavior was not changed by this follow-up; the new dossier partial is Owner-only.

## Readability, alignment and inline contract follow-up

Added icons and selected-section feedback to every section link. Labels use 12px regular-case text; live field values use 14px with a 1.6 line height. Overview metadata shares a balanced six-column desktop grid, with three-column and single-column breakpoints. Other fields share a consistent 24px gap.

The last section now includes an inline Funeral Contract preview. A not-yet-issued case uses the existing preview snapshot service, mapper and approved contract print template, clearly labeled Draft. Issued documents use their saved snapshot/layout when available; legacy documents without snapshots retain their PDF preview. Missing required information and missing preparation permission show a concrete state instead of a blank preview. The print/PDF templates were not edited; screen-only preview wrapping is isolated in a sandboxed iframe. No generation/store request is made by opening Case Details.

Browser checks: actual unissued FC0001 contract rendered inline from case data, eight navigation icons, desktop overview alignment, 390px dark field contrast and no document overflow. Regression selection: 52 passed, 519 assertions. Added targeted draft-preview test: passed with 5 assertions, including zero issued documents and unchanged stored case amounts. Build and Blade compilation passed.


### Neutral text and balanced fields
Overview now presents six separate fields, with status and interment schedule separated. Deceased particulars use an even three-column grid; the empty photo state is compact and stacks on mobile. Text uses solid neutral ink in both themes. Original field nodes and values are retained. Verified desktop and 390px mobile, both themes, Photo Details Escape and focus return. Blade compilation, production build, and 53 regression tests (524 assertions) passed.
