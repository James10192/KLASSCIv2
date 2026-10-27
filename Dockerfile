# syntax=docker/dockerfile:1
#
# Image KLASSCI (une image par école, la configuration vient de l'environnement).
# Nginx + PHP-FPM dans un même conteneur, supervisés par supervisord.
# Le planificateur (schedule:run) tourne dans un service séparé, avec la même image.

ARG PHP_VERSION=8.3

# ---------- base : extensions PHP requises par le composer.lock et le code ----------
FROM php:${PHP_VERSION}-fpm-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev \
        default-mysql-client nginx supervisor unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl opcache bcmath \
    && rm -rf /var/lib/apt/lists/*

# ---------- vendor : dépendances figées par composer.lock ----------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction

# ---------- image finale ----------
FROM base AS final

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stack \
    LOG_LEVEL=warning

COPY deploy/docker/php.ini /usr/local/etc/php/conf.d/klassci.ini
COPY deploy/docker/php-fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY deploy/docker/nginx.conf /etc/nginx/sites-available/default
COPY deploy/docker/supervisord.conf /etc/supervisor/conf.d/klassci.conf
COPY deploy/docker/entrypoint.sh /usr/local/bin/klassci-entrypoint
RUN chmod +x /usr/local/bin/klassci-entrypoint \
    && rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /app /var/www/html

# Dossiers qui doivent rester inscriptibles (la persistance vient de volumes montés).
RUN mkdir -p storage/app storage/framework/cache storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS http://127.0.0.1/login -o /dev/null || exit 1

ENTRYPOINT ["klassci-entrypoint"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/conf.d/klassci.conf"]
