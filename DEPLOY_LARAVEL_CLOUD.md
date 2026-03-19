# Deploy To Laravel Cloud

This project is now set up so uploaded website and product media can use a Laravel Cloud object storage bucket instead of the local `/storage` symlink.

## What Changed In The App

- Public media URLs now resolve through a configurable disk instead of hardcoded `/storage/...` paths.
- Uploads and deletes for products, carousel slides, brands, bundles, and featured builds now use `PUBLIC_MEDIA_DISK`.
- `league/flysystem-aws-s3-v3` is included in Composer dependencies for Laravel Cloud object storage support.

## Laravel Cloud Resources

Recommended for production:

1. Attach a MySQL database.
2. Attach a public object storage bucket for uploaded images.
3. Optionally attach a worker cluster later if you start using queued jobs.

This app already uses persistent database-backed cache, sessions, and queues by default, which fits Laravel Cloud's ephemeral filesystem model.

## Environment Variables

Set these in your Laravel Cloud environment:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

PUBLIC_MEDIA_DISK=r2-public
```

Notes:

- Replace `r2-public` with the exact disk name you choose when attaching the Laravel Cloud object storage bucket.
- Laravel Cloud will automatically inject the bucket and database credentials after those resources are attached.
- If you make the bucket the default filesystem disk in Laravel Cloud, you can still keep `PUBLIC_MEDIA_DISK` set to that same disk name for clarity.

## Build Commands

Add these in `Environment -> Deployments -> Build Commands`:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Deploy Commands

Add this in `Environment -> Deployments -> Deploy Commands`:

```bash
php artisan migrate --force
```

Do not add `php artisan storage:link`. Laravel Cloud's filesystem is ephemeral, so symlinks created during deploy do not persist.

## Laravel Cloud Dashboard Checklist

1. Connect this repository and deploy the target branch.
2. Set PHP to 8.2+.
3. Set a Node version supported by Laravel Cloud for the frontend build.
4. Attach the production database.
5. Attach a public object storage bucket.
6. Set `PUBLIC_MEDIA_DISK` to the bucket disk name.
7. Save and redeploy.

## Local Testing Before Deploy

Run:

```bash
php artisan test
npm run build
```

If you want local uploads to use the bucket too, set `PUBLIC_MEDIA_DISK` to the same bucket disk name in your local `.env` and fill in the AWS variables from the bucket credentials.
