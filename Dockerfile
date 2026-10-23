# syntax=docker/dockerfile:1

################################################################################
# 1. PHP dependencies (production only)
################################################################################
FROM composer:2 AS vendor

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

# Lock files first so the dependency layer is cached.
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress

COPY . .

# No scripts: `artisan` must not run during the build (there is no environment
# yet). Package discovery happens at container start, in the entrypoint.
RUN composer dump-autoload \
        --no-dev \
        --no-scripts \
        --optimize \
        --classmap-authoritative

################################################################################
# 2. Front-end assets (Vite)
################################################################################
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources

RUN npm run build

################################################################################
# 3. Runtime: php-fpm + nginx under supervisord
################################################################################
FROM php:8.3-fpm-alpine AS runtime

# nginx terminates HTTP in the container; supervisor keeps both daemons alive.
# The base image already ships most extensions this app needs, so each one is
# verified and only compiled when it is actually missing.
#
# Alpine names the Oniguruma runtime library `oniguruma` (there is no `libonig`
# package). It is installed as a world package so it survives the removal of the
# build dependencies below, in case mbstring ever has to be compiled here.
RUN set -eux; \
    apk add --no-cache nginx supervisor ca-certificates oniguruma libcurl; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS oniguruma-dev curl-dev; \
    php -m | grep -qi '^mbstring$' || docker-php-ext-install -j"$(nproc)" mbstring; \
    php -m | grep -qi '^curl$'     || docker-php-ext-install -j"$(nproc)" curl; \
    php -m | grep -qi 'opcache'    || docker-php-ext-install -j"$(nproc)" opcache; \
    apk del .build-deps; \
    rm -rf /var/cache/apk/*; \
    for ext in ctype curl fileinfo filter hash json mbstring openssl session tokenizer; do \
        php -m | grep -qix "$ext" || { echo "FATAL: PHP extension '$ext' is unavailable in the base image"; exit 1; }; \
    done

COPY docker/php.ini          /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf     /usr/local/etc/php-fpm.d/zzz-app.conf
COPY docker/nginx.conf       /etc/nginx/nginx.conf
COPY docker/fastcgi_params   /etc/nginx/fastcgi_params.d/laravel
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Dependencies and built assets come from the earlier stages; the application
# source is copied afterwards. Secrets (.env) never enter the image.
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN set -eux; \
    mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache

COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/entrypoint

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
