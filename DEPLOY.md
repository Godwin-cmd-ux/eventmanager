# Deploying with Docker

The image is self-contained: nginx and php-fpm run in one container under
supervisord, front-end assets are built with Vite, and only production Composer
dependencies are installed. **No `.env` file is baked into the image** — every
value comes from the environment at run time.

## Requirements

- Docker 24+ (BuildKit is used for `COPY --chmod`)
- Docker Compose v2 (only needed for `docker-compose.yml`)

## Quick start

On a fresh server:

```bash
cp docker/.env.example .env          # then edit it
docker compose run --rm app php artisan key:generate --show
#   -> base64:...   put that value in APP_KEY
docker compose up -d --build
```

If the checkout already has a development `.env` (as on a laptop), keep it and
put the production values in a separate file instead:

```bash
cp docker/.env.example .env.production
# edit .env.production: APP_KEY, APP_URL, SUPABASE_*
ENV_FILE=.env.production docker compose run --rm app php artisan key:generate --show
ENV_FILE=.env.production docker compose up -d --build
```

The app is then on <http://localhost:8080>. `APP_PORT` in the env file changes
the published host port. The env file is never baked into the image — it is
read by Compose and passed to the container at run time.

## Environment

Everything in `docker/.env.example` is read at start-up. The values that must be
set for a working deployment:

| Variable | Notes |
| --- | --- |
| `APP_KEY` | `php artisan key:generate --show`; the container refuses to serve without it. |
| `APP_URL` | Public URL, e.g. `https://scan.example.com`. |
| `SUPABASE_URL` | `https://<project-ref>.supabase.co`. |
| `SUPABASE_ANON_KEY` | Public API key (stays server-side here). |
| `SUPABASE_SECRET_KEY` | Service-role key. Server-side only — never expose it. |
| `SESSION_SECURE_COOKIE` | `true` whenever the site is served over HTTPS (iOS needs it for camera access). |
| `TRUSTED_PROXIES` | `*` or a CIDR list when a proxy/CDN terminates TLS, otherwise empty. |

`APP_ENV=production`, `APP_DEBUG=false` and `LOG_CHANNEL=stderr` are pinned in
`docker-compose.yml`; logging to stderr means `docker logs` and any collector
see application logs directly.

## What happens on start-up

`docker/entrypoint.sh` recreates `storage/` and `bootstrap/cache`, gives them to
`www-data`, then runs `php artisan optimize:clear && php artisan optimize`
(config, route, event and view caches) before supervisord launches php-fpm and
nginx. Because the caches are rebuilt on every boot, changing an environment
variable only needs a restart — no rebuild.

Set `RUN_MIGRATIONS=true` to run `php artisan migrate --force` on boot. Only do
that once a Laravel database connection (`DB_CONNECTION`, ...) is configured:
the app stores its data in Supabase and its SQL schema lives in
`supabase/migrations`, so the default is `false`.

## Health, logs, lifecycle

```bash
docker compose ps                          # health: healthy
curl -fsS http://localhost:8080/up         # Laravel's health endpoint
docker compose exec app php artisan about  # versions, drivers, cached config
docker compose logs -f app                 # nginx + php-fpm + Laravel
docker compose down                        # stop (keeps the storage volume)
docker compose up -d --build               # deploy a new build
```

The image declares a `HEALTHCHECK` against `/up` (used by Compose, and by
platforms such as ECS, Fly.io and Render).

Sessions, cache, compiled views and logs live in the `app-storage` named volume,
so they survive restarts and rebuilds.

## Behind a TLS proxy

Terminate TLS at your load balancer/CDN and forward to the container's port 80,
then set `TRUSTED_PROXIES` and `SESSION_SECURE_COOKIE=true`. `TRUSTED_PROXIES`
is applied in `bootstrap/app.php`; without it Laravel generates `http://` URLs
and secure cookies are dropped.

For a single-host deployment with its own certificates, put nginx/Traefik/Caddy
in front and point it at `127.0.0.1:${APP_PORT}`.

## Scaling notes

Sessions, cache and logs are on local files, which ties a deployment to a single
container with a persistent volume. To run more than one replica, move
`SESSION_DRIVER` and `CACHE_STORE` to a shared backend (e.g. `database` on
Postgres, or `redis`) and ship logs to stdout only.

## Notes

- The image runs nginx's master process as root (to bind port 80) and drops
  its workers to `nginx` / `www-data`. Point the container at a non-privileged
  port and run it as `www-data` if your platform forbids root.
- `opcache.validate_timestamps=0`: the image is immutable, so code changes
  require a rebuild and restart — never bind-mount the source over it.
- The `.dockerignore` keeps `vendor`, `node_modules`, `.env`, tests and the
  offline `cards/` directory out of the build context.
