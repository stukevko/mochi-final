# syntax=docker/dockerfile:1.7
# Mochi Cards — läuft identisch auf Linux, macOS (Intel/Apple Silicon) und Windows (Docker Desktop/WSL2).
#   docker compose up -d --build   →  http://localhost:8080

# ---------- 1) PHP-Abhängigkeiten ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ---------- 2) Frontend-Build (braucht vendor/ für das Filament-Theme) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY --from=vendor /app /app
RUN npm run build

# ---------- 3) Laufzeit: PHP-FPM + nginx in einem Image ----------
FROM serversideup/php:8.4-fpm-nginx AS app
USER root
RUN install-php-extensions intl gd exif bcmath
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    AUTORUN_LARAVEL_STORAGE_LINK=true \
    PHP_OPCACHE_ENABLE=1 \
    PHP_MEMORY_LIMIT=256M \
    PHP_UPLOAD_MAX_FILE_SIZE=32M \
    PHP_POST_MAX_SIZE=32M \
    TRUSTED_PROXIES=* \
    LOG_CHANNEL=stderr
COPY --chown=www-data:www-data --from=assets /app /var/www/html
RUN rm -rf /var/www/html/node_modules /var/www/html/tests \
    && mkdir -p /var/www/html/database/data \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
USER www-data
