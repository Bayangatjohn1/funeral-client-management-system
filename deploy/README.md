# Deployment packages

The release builder produces separate Central and Branch ZIP files without runtime secrets or local data.

## Build

From PowerShell in the project root:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-deployment-package.ps1 -Target both
```

The ZIP files and SHA-256 checksums are written to `dist/`. The builder runs the production frontend build and a production-only Composer install inside an isolated staging directory. It never modifies the project's `.env` or production database.

## Central package

1. Extract the central ZIP into the hosted application directory.
2. Keep the existing production `.env` and `APP_KEY`; merge the settings from `.env.deploy.example` instead of replacing secrets blindly.
3. Point the web root to `public/`.
4. Run `php artisan optimize:clear`, `php artisan migrate --force`, `php artisan storage:link`, and `php artisan config:cache`.
5. Run `php scripts/offline-preflight.php --probe-storage` and fix every failed gate.
6. Configure the hosting scheduler to run `php /absolute/path/artisan schedule:run` every minute.

## Branch package

1. Extract the branch ZIP onto a dedicated local branch server.
2. Copy `.env.deploy.example` to `.env`, fill every placeholder, and use a **new empty** branch database.
3. Run `php artisan key:generate`, `php artisan migrate --force`, `php artisan storage:link`, `php artisan optimize:clear`, and `php artisan config:cache`.
4. Configure trusted local HTTPS/LAN DNS and a one-minute Task Scheduler job for `php artisan schedule:run`.
5. Issue the branch token centrally, then follow the freeze → bootstrap → verify-ready → activate sequence in `docs/offline-branch-deployment.md`.

Never include `.env`, SQL exports, uploads, private storage, APP_KEY values, branch tokens, or database passwords in a release ZIP. Never freeze or activate a live branch until its local server, backup, two-client LAN test, and reconciliation checks are ready.
