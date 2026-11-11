# Payment drawer compact spacing

Current implementation, browser visual verification pending after previously declined execution.

Applicable: Payment drawer case context, financial summary and transaction table. Already consistent: existing modal/Back/Print/eye actions, fields and amounts. Not applicable: other drawers, payment calculations, sorting or permissions. No required functionality is missing.

Owner: Payment-scoped rules in partials/record_detail_sections.blade.php and the date cell in staff/payments/_history_modal_body.blade.php. Other detail section rules remain unchanged.

- Context always uses a single list, independent of the outer browser's width: 88px label column, 12px label/value gap, 12px row gap, 12px labels and 13px values. Branch code/name wrap together; long values remain visible.
- Payment summary is an aligned four-row financial list rather than scattered two-column metrics. Label left, amount/count right, 8px row padding, 12px labels, 14px values with tabular numerals. Existing order, figures and labels remain.
- Transactions retain all five columns with width shares 22/22/15/25/16%. Body text is 12px, headings 11px, cell padding 10px/5px, date and time are separate lines (all original date/time content retained). Actions remain two independent 32px controls with 4px gap. Monetary content can wrap for unusually long amounts. The table fits the 560px drawer's 520px content area; at narrow widths it retains a 480px minimum inside a horizontal scroller rather than hiding columns.
- Existing 20px content insets and theme-aware dividers remain. No shadows/cards/outlines added. All data/loading/transaction/print/correction behavior remains.

Verification: WorklistAdoptionTest passed seven tests/123 assertions; Payment row-selection and toolbar Node checks passed two tests. Blade compile/cache-clear and touched tracked-file whitespace passed. Asset sources unchanged; prior build applies. Desktop/narrow light/dark appearance and actual table fit remain unverified in the browser; CSS measurements are intended layout, not rendered acceptance.
