#!/bin/sh
# =============================================================
# Runs on every container start, before nginx/php-fpm.
# Toggle steps with env vars (all default to "true"):
#   RUN_MIGRATIONS   php artisan migrate --force
#   SEED_ADMIN       create/update the admin user from ADMIN_* vars
#   CACHE_CONFIG     config/route/view cache for speed
# =============================================================
set -e
cd /var/www/html

artisan() { su-exec www-data php artisan "$@"; }

if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
  exit 1
fi

# Storage may be an empty mounted volume on first start — recreate its folders.
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Wait for MySQL (up to ~60s).
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
  echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306} ..."
  i=0
  until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Exception $e) { exit(1); }' 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -ge 30 ]; then
      echo "ERROR: could not connect to MySQL. Check DB_HOST / DB_USERNAME / DB_PASSWORD." >&2
      exit 1
    fi
    sleep 2
  done
  echo "MySQL is ready."
fi

# Clear any stale caches from a previous start before running commands.
artisan optimize:clear > /dev/null

artisan storage:link --force > /dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  artisan migrate --force
fi

if [ "${SEED_ADMIN:-true}" = "true" ]; then
  artisan db:seed --class=AdminUserSeeder --force
fi

if [ "${CACHE_CONFIG:-true}" = "true" ]; then
  artisan config:cache
  artisan route:cache
  artisan view:cache
fi

exec "$@"
