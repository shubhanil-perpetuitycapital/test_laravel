#!/bin/sh
set -e
cd /var/www/html

# Named volume on /storage starts empty on some hosts — recreate the tree.
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
         storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# ---- Wait for MySQL (up to ~2 minutes), printing the real error ----
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
tries=0
until php -r '
  try {
    new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"),
            getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 3]);
    exit(0);
  } catch (Throwable $e) { fwrite(STDERR, "  -> ".$e->getMessage().PHP_EOL); exit(1); }'
do
  tries=$((tries + 1))
  if [ "$tries" -ge 40 ]; then
    echo "ERROR: could not connect to MySQL at ${DB_HOST}:${DB_PORT} as ${DB_USERNAME}."
    echo "       Is the 'mysql' service running? (Coolify must use the Docker Compose build pack.)"
    exit 1
  fi
  echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT} ... (${tries}/40)"
  sleep 3
done
echo "MySQL is up."

# ---- Laravel bootstrap ----
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize:clear >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force
fi

# Seed only on a fresh database (no users yet), so redeploys don't duplicate data.
if [ "${SEED_ADMIN:-true}" = "true" ]; then
  users=$(php -r '
    try {
      $p = new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"),
                   getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
      echo (int) $p->query("SELECT COUNT(*) FROM users")->fetchColumn();
    } catch (Throwable $e) { echo 0; }')
  if [ "$users" = "0" ]; then
    echo "Fresh database — running seeders."
    php artisan db:seed --force
  fi
fi

php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

chown -R www-data:www-data storage bootstrap/cache

php-fpm -D
echo "Starting nginx on :8080"
exec nginx -g 'daemon off;'
