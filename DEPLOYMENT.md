# Deployment Guide

This project is a Laravel application. In production, the domain document root must point to the `public` directory.

## Server Requirements

- PHP 8.2 or newer
- Composer
- MySQL or MariaDB
- Apache with `mod_rewrite` or an equivalent Nginx configuration

## Build Before Upload

Run these commands locally before uploading the project:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

If PowerShell blocks `npm`, use:

```powershell
npm.cmd run build
```

## Production Environment

Copy `.env.production.example` to `.env` on the server and update these values:

```env
APP_URL=https://your-domain.com
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Generate the application key if `APP_KEY` is empty:

```bash
php artisan key:generate
```

## Database Setup

After setting the production `.env`, run:

```bash
php artisan migrate --force
```

If the production install needs starter data, run the appropriate seeder:

```bash
php artisan db:seed --force
```

## Storage And Cache

Run:

```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ensure these directories are writable by the web server:

```text
storage/
bootstrap/cache/
```

## Shared Hosting Notes

If the hosting panel allows changing the document root, point the domain to:

```text
public
```

If it does not allow that, the included root `.htaccess` will route browser requests to `public/` and block common Laravel source/config paths. This is useful for shared hosting, but the safer setup is still to keep the Laravel project outside `public_html`, then copy only the contents of Laravel's `public` directory into `public_html`. Update `public_html/index.php` paths so they point back to the Laravel project:

```php
require __DIR__.'/../your-laravel-folder/vendor/autoload.php';
$app = require_once __DIR__.'/../your-laravel-folder/bootstrap/app.php';
```

Never expose the project root or `.env` file directly to the browser.
