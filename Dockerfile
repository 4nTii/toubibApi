# =============================================================================
#  Toubib API — unified image (PHP 8.4 / Symfony 8)
#
#  Stages
#    base  → PHP-FPM + system deps + extensions + Composer (shared layer)
#    dev   → base + Xdebug + APCu-CLI + Symfony CLI + dev php.ini
#            source code is bind-mounted (see docker-compose.override.yml)
#    prod  → base + prod php.ini + application baked in + optimized autoloader
#
#  docker compose selects the stage through `build.target`:
#    - docker-compose.override.yml → target: dev   (default `docker compose` run)
#    - docker-compose.yml          → target: prod  (`make prod-up`)
#
#  Manual build of a single stage:
#    docker build --target dev  -t toubib-api:dev  .
#    docker build --target prod -t toubib-api:prod .
# =============================================================================

# -----------------------------------------------------------------------------
#  base — common runtime, shared by every environment
# -----------------------------------------------------------------------------
FROM php:8.4-fpm AS base

# System dependencies (Debian-based — avoids TLS issues behind corporate proxies)
RUN apt-get update && apt-get install -y --no-install-recommends \
        bash \
        curl \
        git \
        unzip \
        openssl \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        intl \
        zip \
        mbstring \
        gd \
        opcache \
        sockets \
        ftp

# APCu (used as cache adapter in every environment)
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP-FPM pool (shared)
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]


# -----------------------------------------------------------------------------
#  dev — hot-reload workflow, source mounted from the host
# -----------------------------------------------------------------------------
FROM base AS dev

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Symfony CLI (server:dump, console helpers, …)
RUN curl -sS --insecure https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

COPY docker/php/php.dev.ini /usr/local/etc/php/conf.d/custom.ini


# -----------------------------------------------------------------------------
#  prod — self-contained image with the application baked in
# -----------------------------------------------------------------------------
FROM base AS prod

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# 1. Dependencies first — cached as long as composer.{json,lock} do not change
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader \
        --prefer-dist --no-progress --no-interaction

# 2. Application source (see .dockerignore for what stays out)
COPY . .

# 3. Optimized autoloader + writable runtime dirs
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction \
    && mkdir -p var/cache var/log var/share \
    && chown -R www-data:www-data var

USER www-data
