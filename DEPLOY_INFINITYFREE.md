# Deploy To InfinityFree (Laravel)

## 1) Pre-build locally
InfinityFree free hosting typically does not provide SSH/Composer/NPM on server, so build everything locally first.

```powershell
cd "c:\Users\Admin\Desktop\Infinite Website\pricelist"
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 2) Create InfinityFree database
1. In InfinityFree control panel, create a MySQL database.
2. Save DB host, DB name, DB username, DB password.
3. Import your schema/data through phpMyAdmin.

If your local data is in SQLite, move/seed data into MySQL first, then export/import.

## 3) Configure `.env` for production
Set at least:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

Also make sure `APP_KEY` is set (from your existing `.env` or generated locally with `php artisan key:generate`).

## 4) Upload files via File Manager or FTP
Upload project contents to `htdocs`, including:
- `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `storage`, `vendor`, `artisan`
- root `.env`
- root `.htaccess` (included in this repo for InfinityFree rewrite)

Do not upload:
- `node_modules`
- test/dev-only local files you do not need

### If File Manager upload keeps failing
For this project, browser upload often fails because there are many files (`vendor` is large).

Use FTP (recommended):
1. Open your hosting control panel and get FTP hostname, username, password.
2. Connect using FileZilla/WinSCP.
3. Upload in this order:
   - everything except `vendor`
   - then upload `vendor` last
4. Exclude these folders from upload:
   - `node_modules`
   - `.git`
   - `tests`
   - `docker`

If you still use File Manager, upload smaller batches instead of all files at once.

## 5) Permissions
Ensure these are writable by PHP:
- `storage`
- `bootstrap/cache`

## 6) Verify
1. Open your site URL.
2. Test login, admin pages, image uploads, and PDF generation.
3. If you see 500 errors, check:
   - `.env` DB credentials
   - `APP_KEY`
   - writable `storage` and `bootstrap/cache`
   - `vendor` uploaded correctly
