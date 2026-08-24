#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --force
fi

echo "Waiting for the database..."
until php artisan db:show > /dev/null 2>&1; do
    sleep 2
done

php artisan migrate --force
php artisan storage:link || true

# --no-reload: without it, `artisan serve` only forwards a small whitelist
# of env vars (APP_ENV, PATH, ...) to the process that actually handles
# requests once a .env file exists — silently dropping the DB_* etc. vars
# this compose file sets, and falling back to whatever's in .env instead.
exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
