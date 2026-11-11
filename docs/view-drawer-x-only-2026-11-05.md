# View drawers use X-only dismissal

User approved removing duplicate footer Close buttons across Case/Master/Owner, Payment, Branch, User and Service View drawers. Top-right X, Escape and backdrop dismissal remain. Add/Edit forms, confirmations and their Cancel/Close controls are excluded.

Case/Master retains its left-aligned Open Full Details footer. Payment retains left-aligned history Print and detail Back/Print groups; inactive groups remain hidden. Branch/User/Service View have no useful remaining footer action, so their footer containers are omitted. No empty divider/padding region is retained. No new actions or fields.

Source owners: case_summary_drawer and user_information_modals; Branch renderBranchOverview/viewShell only renders an actions region when nonempty; Payment history markup/detail section CSS; six Service show templates exclude their entire footer for modal responses. Service standalone action footers remain. service_detail_drawer_body now accepts an absent footer so the fixed header and independently scrolling content remain functional.

Removed obsolete User Close-margin rules and Service Close-footer styles. Existing routes/data/policies/save/print/history/modal focus and close handlers remain; no backend or asset changes. This supersedes right-aligned footer Close guidance.

Validation: 16 feature tests/385 assertions (ServiceCatalogConsistencyTest, CaseSummaryDrawerTest and WorklistAdoptionTest), 11 Node behavior checks including Service content wrapping without a footer, Blade compile/cache clear and touched tracked-file whitespace passed. Actual browser light/dark/responsive rendering, pointer/focus and backdrop behavior remain unverified after previously declined execution. Source tests are not visual acceptance.
