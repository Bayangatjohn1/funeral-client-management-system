# Package Form Discard Guard — 2026-11-01

## Finding

Package Add/Edit had two close paths: the form's Cancel links and the Service Management modal's close controls. The iframe Cancel path delegated to the parent before the package form could reliably decide whether its values were dirty. If the shared JavaScript bundle did not initialize, the parent treated the form as safe to close. The package baseline was also captured before initial service controls were synchronized, which could make disabled optional fields look like user edits.

## Correction

- The package form now exposes `requestServiceFormClose` directly, so both the modal's Cancel and header close request can guard independently of shared module initialization.
- The shared service feedback module preserves this package-specific guard instead of replacing it.
- Cancel navigation is prevented until the guard completes. In an iframe, the parent then follows its existing Cancel flow; on a standalone page, navigation happens only after confirmation.
- The baseline snapshot is captured after initial service, package, promotion, casket, and freebie state is synchronized. Snapshot entries are sorted so wizard DOM rearrangement does not count as an edit.
- Discard resets the unsaved form state; Keep Editing leaves it intact. No backend or pricing behavior changed.

## Verification status

- `node --check resources/js/service-form-feedback.js` passed.
- Browser interaction testing is pending; the user will test Cancel and modal X in the running application.
