[🇫🇷 Version française](operations.fr.md) | 🇬🇧 English version

---

# Operations

## Production environment

The application runs on a VPS behind a shared Traefik reverse proxy with automatic TLS via Let's Encrypt. Docker images are built in CI and stored in GHCR: no source code and no build step on the server.

### Stack

| Service | Image | Role |
|---|---|---|
| `nginx` | custom (nginx:alpine) | Web server, static assets, OSM tile proxy cache |
| `app` | custom (PHP 8.5-FPM) | Laravel application |
| `postgres` | postgres:16-alpine | Production database |
| `redis` | redis:7-alpine | Cache, sessions, queue |
| `queue` | custom (PHP 8.5-FPM) | Laravel queue worker |

Only `nginx` is exposed to Traefik via the shared `traefik-public` network. All other services run in a private Docker network (`docker-compose.prod.yml`).

### OSM tile proxy cache

Nginx proxies OpenStreetMap tile requests through `/tiles/{z}/{x}/{y}.png` and caches responses on a persistent Docker volume (capped at 1 GB, TTL 30 days). Reduces load on OSM's infrastructure and speeds up repeat visits to previously explored areas.

## Continuous deployment

`.github/workflows/build-push-deploy.yml` handles the full build-and-deploy pipeline.

**Trigger**
- Automatically, when the `E2E` workflow succeeds on `main`
- Manually, via `workflow_dispatch` (bypasses the E2E gate; used for hotfixes, rollbacks, or redeploys)

**Build job**
- Builds the `production` and `nginx-prod` targets of the multi-stage `Dockerfile` with Docker Buildx
- Tags each image with `sha-<short-sha>` and `latest`
- Pushes both images to GHCR (`ghcr.io/marvinlerouge/summit-stats/app`, `.../nginx`)

**Deploy job**
- Connects to the production server over SSH
- Fetches `docker-compose.prod.yml` from the exact commit that was built
- Pulls the new images and redeploys the stack (`docker compose up -d --remove-orphans`)
- Runs pending migrations (`php artisan migrate --force`)
- Prints the resulting stack status

## Initial server setup

Prerequisites on the server:
- Docker and Docker Compose installed
- A Traefik instance already running, exposing a `web` (port 80) and `websecure` (port 443) entrypoint, with a `letsencrypt` certificate resolver and an external `traefik-public` Docker network
- SSH access configured, with `DEPLOY_SSH_HOST`, `DEPLOY_SSH_USER`, and `DEPLOY_SSH_PRIVATE_KEY` set as GitHub repository secrets

Server layout, under `/home/mlr/marvinlerouge.dev/summit-stats/`:

```
compose/
└── docker-compose.prod.yml   ← fetched by the deploy workflow on every deploy
shared/
├── env/
│   ├── app.env               ← non-secret settings (chmod 600, never committed)
│   └── secrets.env           ← secrets (chmod 600, never committed)
└── backups/                  ← daily database dumps
```

Containers get their configuration only through `env_file`: the compose file interpolates nothing from a file (the domain is written in the Traefik rule, `IMAGE_TAG` is set by the deploy workflow).

| File | Loaded by | Holds | Template |
|---|---|---|---|
| `shared/env/app.env` | app, queue | non-secret settings: app identity and URL, logging, session, cache and queue drivers, Redis, elevation, Sanctum | [`docker/env/app.env.example`](../docker/env/app.env.example) |
| `shared/env/secrets.env` | app, queue (last, so it wins), postgres | every topic holding a secret, kept whole: `APP_KEY`, database (`DB_CONNECTION/HOST/PORT`, `POSTGRES_*`), `APP_USER_*` | [`docker/env/secrets.env.example`](../docker/env/secrets.env.example) |

The database credentials exist once, under the postgres image's names (`POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`): `config/database.php` falls back to them when `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` are unset. Local development keeps `DB_*` in the root `.env`.

Steps:
1. Create `shared/env/` on the server and fill `app.env` and `secrets.env` from the templates, then `chmod 600 shared/env/*.env` (the deploy workflow creates the directories, never the files)
2. Trigger the workflow (push to `main` once E2E passes, or run it manually) to build the images and perform the first deployment

## Secrets and key rotation

- Both env files must not be world-readable: `chmod 600 shared/env/*.env` on the VPS after editing them.
- `APP_KEY` encrypts session data and other Laravel-internal payloads. Rotating it invalidates all existing sessions and any data encrypted with the old key (Sanctum plaintext tokens are hashed, not encrypted with `APP_KEY`, so they are unaffected). Rotate only if the key is suspected to have leaked: from `compose/`, generate a new one with `docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show`, update `shared/env/secrets.env`, then `docker compose -f docker-compose.prod.yml up -d` to restart with it.
- `POSTGRES_PASSWORD` should be generated with `openssl rand -base64 32` or similar, never a memorable password, since it is never typed by a human.
- `LOG_LEVEL=warning` (not `error`): failed login attempts are logged at `warning` level so they remain visible in production logs. A stricter `error` level would silently drop this signal.
- `LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter`: production logs are written to stderr as one JSON object per line (`message`, `context`, `level_name`, `datetime`, ...), so `docker logs` output can be filtered with `jq` or shipped to a log collector as is. Log calls keep a constant message and put variable data in the context array. Local development keeps the readable text format.
- Dependabot is enabled (`.github/dependabot.yml`, weekly npm + Composer checks). Review and merge or dismiss each alert within a week of it opening; do not let alerts accumulate unreviewed.

## Automated backups

`docker/scripts/backup.sh` runs once a week from cron on the VPS. It dumps the database and archives the uploaded GPX files (`storage/app/private/gpx` in the `app` container), then compares their fingerprint with the last stored backup:

- **Changed:** stores `shared/backups/summit-stats-<timestamp>/` (`db.sql.gz`, `gpx.tar.gz`, `fingerprint`), keeps the 8 newest backups, and mails the `scp` command to fetch it to your machine (the off-VPS copy).
- **Unchanged:** stores nothing. When no mail went out for 28 days, it sends a "still alive" mail, so a silent month never hides a broken job.
- **Failure:** mails the error and exits non-zero.

The fingerprint ignores the random `\restrict` key that `pg_dump` 16.10+ writes into every dump, so two dumps of an unchanged database match. Credentials are read inside the containers; the script itself only needs the mail settings, in `shared/env/backup.env` (template [`docker/env/backup.env.example`](../docker/env/backup.env.example)), read by the script on the host and never by the containers. Mails go through the Brevo SMTP relay with STARTTLS, from a sender verified in Brevo.

| Variable | Purpose |
|---|---|
| `BACKUP_MAIL_FROM`, `BACKUP_MAIL_FROM_NAME` | Verified Brevo sender |
| `BACKUP_MAIL_TO`, `BACKUP_MAIL_TO_NAME` | Recipient |
| `SMTP_HOST`, `SMTP_PORT` | `smtp-relay.brevo.com`, `587` |
| `SMTP_USERNAME`, `SMTP_PASSWORD` | Brevo SMTP login and SMTP key |
| `BACKUP_SSH_TARGET` | Only for the ready-to-paste `scp` command in the mail: the VPS address as your machine reaches it (IP, `user@IP` or ssh alias; default `<user>@<hostname -f>`) |
| `BACKUP_LOCAL_DIR` | Destination of that `scp` command on your machine, e.g. `~/projets/Summit-Stats/backups/` (gitignored; default `./`) |

Optional overrides on the cron line: `BACKUP_KEEP` (8), `BACKUP_ALIVE_DAYS` (28), `BACKUP_DIR` (`../shared/backups`), `BACKUP_ENV_FILE` (`../shared/env/backup.env`).

Setup on the VPS (commands for you to run over SSH, Claude never executes these):

```bash
cd /home/mlr/marvinlerouge.dev/summit-stats
mkdir -p shared/backups
curl -fsSL https://raw.githubusercontent.com/MarvinLeRouge/Summit-Stats/main/docker/env/backup.env.example -o shared/env/backup.env
chmod 600 shared/env/backup.env   # then fill it in
curl -fsSL https://raw.githubusercontent.com/MarvinLeRouge/Summit-Stats/main/docker/scripts/backup.sh -o compose/backup.sh
chmod +x compose/backup.sh
cd compose
./backup.sh --test-mail   # mail settings only: sends a test mail, no backup
./backup.sh               # first real run: stores a backup and mails it
crontab -e
# Add this line (Mondays at 03:00):
0 3 * * 1 cd /home/mlr/marvinlerouge.dev/summit-stats/compose && ./backup.sh >> ../shared/backups/backup.log 2>&1
```

To restore, from `compose/` with the backup folder in `../shared/backups/`:

```bash
B=../shared/backups/summit-stats-<timestamp>
gunzip -c "$B/db.sql.gz" | \
  docker compose -f docker-compose.prod.yml exec -T postgres sh -c 'psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" "$POSTGRES_DB"'
docker compose -f docker-compose.prod.yml exec -T app tar -C /app/storage/app/private -xzf - < "$B/gpx.tar.gz"
```

The database restore expects an empty database (fresh postgres volume): the dump recreates the tables.

## Health check

`GET /health` runs `select 1` on the database and a Redis `ping`. It answers 200 ("Application up") when both respond, 500 otherwise, with no error detail in the response (the exception is reported to Sentry). It needs no authentication, so an external uptime monitor can poll `https://summit-stats.marvinlerouge.dev/health`.

There is deliberately no Docker `healthcheck` on `app` or `nginx`: Traefik stops routing to unhealthy containers, so a database outage would turn into a Traefik 404 instead of the app's own 500.

## Error tracking (Sentry)

Unhandled backend exceptions (5xx, failed queue jobs, upload pipeline errors) and uncaught frontend errors are reported to Sentry. Expected errors are not: validation and invalid GPX files (422), authentication (401), missing resources (404), throttling (429). Without DSNs nothing is sent, so local development and CI report nothing.

Only stack traces are sent: no user, IP, cookies, headers, request bodies (GPX uploads carry home locations), query strings, SQL bindings, breadcrumbs, function arguments or source lines, and no tracing. Backend: `data_collection`, `before_send` and `before_breadcrumb` in `config/sentry.php` (`App\Services\Observability\SentryScrubber`). Frontend: `resources/js/observability.js`.

Browser events go to `POST /api/<hash of the DSN>` on the app's own origin, then `SentryTunnelController` forwards them to Sentry, so ad-blockers and browser tracking protection cannot drop them. The relay only accepts the frontend DSN (403 otherwise), caps envelopes at 1 MB and is throttled to 30 requests per minute per IP.

| Variable | Read by | Local | Production |
|---|---|---|---|
| `SENTRY_LARAVEL_DSN` | backend, runtime | `.env` | `shared/env/secrets.env` |
| `SENTRY_FRONTEND_DSN` | backend, runtime (relay allow-list) | `.env` | `shared/env/secrets.env` |
| `SENTRY_ENVIRONMENT` | backend, runtime (empty: `APP_ENV`) | `.env` | `shared/env/app.env` (`production`) |
| `VITE_SENTRY_DSN` | frontend, build time | `.env` | GitHub secret `VITE_SENTRY_DSN`, Docker build argument |
| `VITE_SENTRY_ENVIRONMENT` | frontend, build time (empty: `development`) | `.env` | `production`, set in `build-push-deploy.yml` |

`SENTRY_FRONTEND_DSN` must equal `VITE_SENTRY_DSN` character for character: the browser derives its tunnel path from the DSN baked into the bundle, so a mismatch makes every frontend event end in 404. The backend then logs `Sentry tunnel path matches no configured DSN`.

Setup, once:

1. In Sentry (EU region), one project per side: `summit-stats-backend` (Laravel) and `summit-stats-frontend` (Vue). In each, *Settings*, *Security & Privacy*: enable *Data Scrubber*, *Use Default Scrubbers* and *Prevent Storing of IP Addresses*.
2. GitHub repository secret `VITE_SENTRY_DSN`: the frontend DSN.
3. On the VPS: both DSNs in `shared/env/secrets.env`, `SENTRY_ENVIRONMENT=production` in `shared/env/app.env`, then redeploy.

Check after a deploy: `docker compose -f docker-compose.prod.yml exec app php artisan sentry:test` for the backend. For the frontend, run `setTimeout(() => { throw new Error('sentry check') }, 0)` in the browser console, first without browser extensions, then with an ad-blocker on to check the relay. The issue must appear under `environment: production`, with a `POST /api/<hash>` answering 200 and no request to `sentry.io`.

## Updating

Deployments are automatic: once a change lands on `main` and the E2E suite passes, `build-push-deploy.yml` builds fresh images and redeploys the stack, including any pending migrations.

To redeploy without a new commit (e.g. a rollback to a previous image tag), trigger the workflow manually via `workflow_dispatch`.
