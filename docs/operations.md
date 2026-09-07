[🇫🇷 Version française](operations.fr.md) | 🇬🇧 English version

---

# Operations

## Production environment

The application runs on a VPS behind a shared Traefik reverse proxy with automatic TLS via Let's Encrypt. Docker images are built in CI and stored in GHCR — no source code and no build step on the server.

### Stack

| Service | Image | Role |
|---|---|---|
| `nginx` | custom (nginx:alpine) | Web server, static assets, OSM tile proxy cache |
| `app` | custom (PHP 8.4-FPM) | Laravel application |
| `postgres` | postgres:16-alpine | Production database |
| `redis` | redis:7-alpine | Cache, sessions, queue |
| `queue` | custom (PHP 8.4-FPM) | Laravel queue worker |

Only `nginx` is exposed to Traefik via the shared `traefik-public` network. All other services run in a private Docker network (`docker-compose.prod.yml`).

### OSM tile proxy cache

Nginx proxies OpenStreetMap tile requests through `/tiles/{z}/{x}/{y}.png` and caches responses on a persistent Docker volume (capped at 1 GB, TTL 30 days). Reduces load on OSM's infrastructure and speeds up repeat visits to previously explored areas.

## Continuous deployment

`.github/workflows/build-deploy.yml` handles the full build-and-deploy pipeline.

**Trigger**
- Automatically, when the `E2E` workflow succeeds on `main`
- Manually, via `workflow_dispatch` (bypasses the E2E gate — used for hotfixes, rollbacks, or redeploys)

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

Steps:
1. Create the deploy directory on the server (matches `DEPLOY_PATH` in the workflow)
2. Copy `.env.prod.example` to `.env.prod` in that directory and fill in real values (`APP_KEY`, database credentials, `DOMAIN`, etc.) — `.env.prod` is never committed
3. Trigger the workflow (push to `main` once E2E passes, or run it manually) to build the images and perform the first deployment

## Secrets and key rotation

- `.env.prod` must not be world-readable: `chmod 600 .env.prod` on the VPS after editing it.
- `APP_KEY` encrypts session data and other Laravel-internal payloads. Rotating it invalidates all existing sessions and any data encrypted with the old key (Sanctum plaintext tokens are hashed, not encrypted with `APP_KEY`, so they are unaffected). Rotate only if the key is suspected to have leaked: generate a new one with `docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan key:generate --show`, update `.env.prod`, then `docker compose -f docker-compose.prod.yml --env-file .env.prod up -d` to restart with it.
- `DB_PASSWORD` should be generated with `openssl rand -base64 32` or similar, never a memorable password, since it is never typed by a human.
- `LOG_LEVEL=warning` (not `error`): failed login attempts are logged at `warning` level so they remain visible in production logs. A stricter `error` level would silently drop this signal.
- Dependabot is enabled (`.github/dependabot.yml`, weekly npm + Composer checks). Review and merge or dismiss each alert within a week of it opening; do not let alerts accumulate unreviewed.

## Automated backups

`docker/scripts/backup-postgres.sh` dumps the database and keeps the last 14 days of backups. It needs to live on the server alongside the compose file:

```bash
curl -fsSL https://raw.githubusercontent.com/MarvinLeRouge/Summit-Stats/main/docker/scripts/backup-postgres.sh -o backup-postgres.sh
chmod +x backup-postgres.sh
```

Install it as a daily cron job (commands below are for you to run over SSH, Claude never executes these):

```bash
crontab -e
# Add this line (adjust the path to where docker-compose.prod.yml lives):
0 3 * * * cd /opt/summit-stats/compose && BACKUP_DIR=/opt/summit-stats/backups ./backup-postgres.sh >> /var/log/summit-stats-backup.log 2>&1
```

To restore from a backup:

```bash
set -a; source .env.prod; set +a
gunzip -c /opt/summit-stats/backups/summit-stats-<timestamp>.sql.gz | \
  docker compose -f docker-compose.prod.yml --env-file .env.prod exec -T postgres psql -U "$DB_USERNAME" "$DB_DATABASE"
```

## Updating

Deployments are automatic: once a change lands on `main` and the E2E suite passes, `build-deploy.yml` builds fresh images and redeploys the stack, including any pending migrations.

To redeploy without a new commit (e.g. a rollback to a previous image tag), trigger the workflow manually via `workflow_dispatch`.
