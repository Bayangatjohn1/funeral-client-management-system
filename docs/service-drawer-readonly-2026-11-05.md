# Read-only Service View drawers

User approved read-only View drawers and subsequently X-only dismissal. Implemented for Package, Casket, Add-on, Freebie, Hearse and Promotion details. Modal footers are now omitted entirely, with the outer X, Escape and backdrop closing retained. See [the current X-only decision](view-drawer-x-only-2026-11-05.md). Browser visual verification remains pending after earlier declined execution.

Applicable: drawer record action presentation and its obsolete footer grouping. Already consistent: grid/table More Actions already exposes Edit, availability, Archive/Restore and status-appropriate promotion actions; no new commands needed. Not applicable: standalone detail pages, Add/Edit forms, controller/routes/policies/calculations or other modules. No required functionality missing.

The six show views now render management footer controls only when the existing permission condition holds AND modal is false. Modal responses contain only the existing Close control; the outer drawer retains X. This removes actions at render time rather than merely hiding controls. Content and original permissions remain. Standalone page actions and list menu forms keep the same destinations and confirmation safeguards.

Removed the previous two-row footer node grouping, action-group CSS/tone metadata and unused iframe Edit color rules. service_detail_drawer_body only arranges the fixed header/body/footer. record_drawer_styles keeps a compact right-aligned Close, 44px minimum height, without shadows or decorative outlines.

Verification: ServiceCatalogConsistencyTest passed seven tests/119 assertions, including all six read-only drawer footers, Close presence, standalone Edit and retained grid/table Edit/status/availability forms. Nine Node checks passed for drawer/loading/motion and existing Service archive/save-refresh behavior. Blade compile/cache clear and touched tracked-file whitespace passed. Asset sources unchanged; no build required for these Blade changes. Actual browser light/dark layout and pointer/focus behavior remain unverified.
