# Deployment readiness — 2026-11-11

## Release artifacts

- Central: `dist/funeral-system-central-20261111-232700.zip`
  - SHA-256: `3b88cd6c3d26c52c0a9713c6746a93eb110d99fa1ef2d7cb9ebbc38396dc0786`
- Branch: `dist/funeral-system-branch-20261111-232700.zip`
  - SHA-256: `b3d2b5453cadf7be1d369b0c109dbfbe500c4ace29c6d391bd9e9721dea009c8`

Both archives contain production Composer dependencies, compiled frontend assets, the correct deployment environment template, and a release manifest. Archive paths use portable `/` separators. Verification found no `.env`, database file, SQL export, private key, PHPUnit event log, or other forbidden runtime artifact.

## Validation completed

- Production frontend build: passed (Vite 7.3.7, 105 modules)
- Blade view compilation: passed
- Deployment builder PowerShell parse: passed
- `git diff --check`: passed
- Focused deployment suite: 118 passed, 2 environment-only MySQL tests skipped, 780 assertions
- Full Laravel suite: 570 passed, 2 environment-only MySQL tests skipped, 6,377 assertions
- Node interaction suite: 63 passed
- ZIP SHA-256 sidecars: matched

The two skipped tests require the guarded localhost MySQL configuration documented by the tests. They are not application failures.

## Required operator steps

The code packages are ready, but offline operation is not activated by uploading the ZIP alone.

1. Deploy and validate the Central package first. Preserve the existing production `.env` and `APP_KEY`.
2. Rotate any production credential that has been shared outside the server and update only the server-side `.env`.
3. Install the Branch package on a dedicated local branch server with a new empty database and a newly generated branch `APP_KEY`.
4. Configure trusted LAN HTTPS/DNS, database backups, and the one-minute scheduler on both installations.
5. Issue the branch token on Central and perform the documented freeze, bootstrap, readiness verification, activation, and reconciliation sequence.

Follow `deploy/README.md` and `docs/offline-branch-deployment.md`. Do not freeze the hosted branch until the local server, backup, two-client LAN test, and rollback plan have passed.
