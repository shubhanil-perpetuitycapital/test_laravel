#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# Runtime bootstrap
#
# Coolify mounts host directories over storage/app/public and storage/logs.
# A fresh mount arrives empty and root-owned, so the directories Laravel
# expects have to be recreated and re-permissioned on every container start.
# ---------------------------------------------------------------------------
mkdir -p \
    bootstrap/cache \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true

# Make sure the log file exists and is writable before the first request, so a
# boot-time failure is written to disk instead of disappearing.
touch storage/logs/laravel.log || true
chown www-data:www-data storage/logs/laravel.log || true
chmod 664 storage/logs/laravel.log || true

# public/storage is git-ignored, so the symlink has to be recreated each start.
php artisan storage:link --force || true

# Migrations and caches are handled by the Coolify post-deployment command so
# they run once per deploy rather than once per container start.

exec "$@"
