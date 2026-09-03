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

## Updating

Deployments are automatic: once a change lands on `main` and the E2E suite passes, `build-deploy.yml` builds fresh images and redeploys the stack, including any pending migrations.

To redeploy without a new commit (e.g. a rollback to a previous image tag), trigger the workflow manually via `workflow_dispatch`.
