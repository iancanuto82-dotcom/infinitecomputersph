#!/bin/sh
set -e

cd /var/www/html

# Render injects PORT dynamically for each deploy.
PORT="${PORT:-10000}"

# Optional: run DB migrations on boot by setting RUN_MIGRATIONS=true.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

# Ensure the public storage symlink exists (safe to retry).
php artisan storage:link >/dev/null 2>&1 || true

exec php artisan serve --host=0.0.0.0 --port="$PORT"
