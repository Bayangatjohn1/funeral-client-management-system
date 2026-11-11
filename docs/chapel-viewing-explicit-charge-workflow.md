# Chapel Viewing selection and charges

Current branch ownership, physical identity and schedule rules are documented in [the consolidated workflow](chapel-and-payment-workflows-2026-11-08.md). This supersedes the earlier location-only continuation approach.

- Viewing Type records the viewing location and schedule. Selecting Chapel Viewing does not automatically select a paid service.
- A chapel catalog item included in the selected package supplies its included days and daily excess rate. Wake duration supplies the requested days. Only excess days are charged.
- Without a package chapel inclusion, show an explicit not-included notice and a required Chapel Viewing Rate selector with daily prices. Leave it unselected initially, even if there is only one catalog option. Staff must explicitly choose the separately billed rate to continue.
- Place the notice directly below Viewing Type, followed by the chapel selector and computation, before chapel location details. Use a soft amber background and warning icon when not included, and soft green with a check icon when included or explicitly added. Keep dark-mode colors readable and announce state changes politely.
- A staff selection adds the daily service charge for the wake duration. Clearing the selection removes the charge and blocks continuation until a valid rate is chosen. Chapel is selected in the Viewing section and excluded from the ordinary Optional Add-ons picker to avoid duplicate charges.
- The server derives the day count from the wake dates, validates the selected catalog item and price context, and prevents duplicate chapel charges through the ordinary add-on picker.
- Included or explicitly selected chapel items must be active and selectable. Catalog availability does not represent room reservations.
- When a valid chapel selection is missing, replace Continue with an enabled “Save as Draft” action using the existing draft submitter. Show amber when a rate can be selected, or soft red when the configuration is unavailable. Selecting Chapel Viewing always requires valid coverage or an explicit catalog rate before advancing or saving an official record. Restoring valid coverage returns the button to Continue.
- Home Viewing uses its own package coverage. Chapel charges do not apply when Home Viewing is selected.

Verified with IntakeDraftWorkflowTest, IntakeStructuredPricingTest, and chapel-notice.test.cjs, including required explicit rate selection, included allowance, unavailable included item, and valid zero rates.
