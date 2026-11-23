# syntax=docker/dockerfile:1
#
# Build context must be the repository root:
#   docker build -f docker/Dockerfile .
#
# Coolify: set "Dockerfile Location" to /docker/Dockerfile and
# "Base Directory" to / so the build context stays the repo root.

FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources

RUN npm run build


# ---------------------------------------------------------------------------
# Stage 2 — application runtime
# ---------------------------------------------------------------------------
FROM php:8.2-cli-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    PHP_CLI_SERVER_WORKERS=4

# ---------------------------------------------------------------------------
# System packages
# ---------------------------------------------------------------------------
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# PHP extensions
#
# bcmath     -> money / freight calculations
# exif, gd   -> intervention/image, simple-qrcode
# intl       -> maatwebsite/excel (phpspreadsheet), number/date formatting
# mysqli     -> legacy raw MySQL usage
# opcache    -> runtime performance
# pcntl      -> queue workers / signal handling
# pdo_mysql  -> landlord + tenant MySQL connections
# zip        -> maatwebsite/excel, composer dists
#
# mbstring, dom, xml, curl, openssl, fileinfo, tokenizer, ctype and simplexml
# are already compiled into the official php image.
# ---------------------------------------------------------------------------
COPY --from=mlocati/php-extension-installer:2 \
    /usr/bin/install-php-extensions \
    /usr/local/bin/

RUN install-php-extensions \
        bcmath \
        exif \
        gd \
        intl \
        mysqli \
        opcache \
        pcntl \
        pdo_mysql \
        zip

# ---------------------------------------------------------------------------
# Fail the build early if GD was compiled without the formats we rely on.
# ---------------------------------------------------------------------------
RUN php -r '$missing = array_filter(["imagecreatefromjpeg", "imagecreatefrompng", "imagecreatefromwebp", "imagejpeg", "imagepng", "imagettftext"], fn ($f) => ! function_exists($f)); if ($missing) { fwrite(STDERR, "GD is missing: " . implode(", ", $missing) . PHP_EOL); exit(1); }' \
    && php -m

# ---------------------------------------------------------------------------
# PHP configuration
# ---------------------------------------------------------------------------
COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini

# ---------------------------------------------------------------------------
# Entrypoint + healthcheck
# ---------------------------------------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
COPY docker/healthcheck.sh /usr/local/bin/healthcheck
RUN chmod +x /usr/local/bin/entrypoint /usr/local/bin/healthcheck

# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# Application
# ---------------------------------------------------------------------------
WORKDIR /var/www

# Copy lock files first so this layer is cached until composer.lock changes.
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --prefer-dist \
        --no-scripts \
        --no-autoloader \
        --no-progress

COPY . .

COPY --from=assets /app/public/build ./public/build

# ---------------------------------------------------------------------------
# Laravel writable directories
#
# storage/ contents are git-ignored, so every directory Laravel, dompdf and
# maatwebsite/excel write to has to be created here. Coolify volumes may
# overlay storage/app/public and storage/logs at runtime.
# ---------------------------------------------------------------------------
RUN mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
    && composer dump-autoload \
        --no-dev \
        --optimize \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
        public \
    && chmod -R ug+rwX \
        storage \
        bootstrap/cache

EXPOSE 8080

HEALTHCHECK \
    --interval=10s \
    --timeout=5s \
    --start-period=40s \
    --retries=5 \
    CMD ["healthcheck"]

ENTRYPOINT ["/usr/local/bin/entrypoint"]

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080", "--no-reload"]
