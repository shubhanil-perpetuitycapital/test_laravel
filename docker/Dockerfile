# =============================================================
# DFC Chocolate Store — Laravel on nginx + PHP-FPM (port 8080)
# =============================================================

# ---- 1. PHP dependencies ------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# ---- 2. Front-end assets (skipped if there is no package.json) ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY . .
RUN if [ -f package.json ]; then \
        (npm ci || npm install) && npm run build; \
    fi; \
    mkdir -p public/build

# ---- 3. Runtime ----------------------------------------------
FROM php:8.3-fpm-alpine

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN apk add --no-cache nginx \
 && install-php-extensions pdo_mysql mysqli gd zip intl bcmath exif opcache pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
 && php artisan package:discover --ansi || true

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/app/public bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

HEALTHCHECK --interval=15s --timeout=5s --start-period=60s --retries=5 \
  CMD wget -qO- http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
