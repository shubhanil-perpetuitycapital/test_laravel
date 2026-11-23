# syntax=docker/dockerfile:1
# =============================================================
# DFC Logistics — production image
# PHP 8.2 (FPM) + nginx in one container, managed by supervisord.
# Listens on port 8080. Health check: GET /up
# =============================================================
FROM php:8.2-fpm-alpine

# --- System packages ------------------------------------------
RUN apk add --no-cache nginx supervisor su-exec tzdata

# --- PHP extensions -------------------------------------------
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql zip bcmath intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- Config files ---------------------------------------------
COPY docker/php.ini           /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf      /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/nginx.conf        /etc/nginx/nginx.conf
COPY docker/supervisord.conf  /etc/supervisord.conf
COPY docker/entrypoint.sh     /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    TZ=Asia/Kolkata

# --- PHP dependencies (cached layer: only re-runs when composer files change)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# --- Application code -----------------------------------------
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
 && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache \
 && php artisan package:discover --ansi \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

HEALTHCHECK --interval=15s --timeout=5s --start-period=60s --retries=5 \
  CMD wget -q -O /dev/null http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
