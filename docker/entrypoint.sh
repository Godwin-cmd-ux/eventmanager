#!/bin/sh
#
# Container entrypoint.
#
#  1. Recreates the writable directories (a volume mounted on storage/ starts
#     out empty) and hands them to www-data.
#  2. When starting the web server, binds Laravel's caches to the *current*
#     environment, so changing an env var and restarting is enough.
#  3. Optionally runs migrations, then execs the CMD (supervisord).
#
set -eu

APP_DIR="${APP_DIR:-/var/www/html}"
cd "$APP_DIR"

log() { printf '[entrypoint] %s\n' "$*"; }

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

# One-off commands (`docker compose run --rm app php artisan ...`) skip the
# cache rebuild, which also lets them run before APP_KEY exists.
if [ "${1:-}" = "/usr/bin/supervisord" ]; then
    if [ -z "${APP_KEY:-}" ]; then
        log "ERROR: APP_KEY is empty."
        log "Generate one with:"
        log "    docker compose run --rm app php artisan key:generate --show"
        log "then set APP_KEY in your env file and start the container again."
        exit 1
    fi

    log "rebuilding configuration, route and view caches"
    php artisan optimize:clear
    php artisan optimize

    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        log "running database migrations"
        php artisan migrate --force
    fi

    if [ "$(id -u)" = "0" ]; then
        chown -R www-data:www-data storage bootstrap/cache
    fi

    log "$(php artisan --version) ready"
fi

exec "$@"
