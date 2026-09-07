# 0008 - Docker Compose and Traefik as the production deployment topology

- Status: Accepted
- Date: 2026-04-02

## Context

Moving Summit Stats from local development to a real deployment required a production topology: a way to run the app (PHP-FPM), a web server, PostgreSQL (see [0004](0004-sqlite-dev-postgresql-production.md)), Redis, and a queue worker together on a VPS, with HTTPS and no manual build step on the server.

## Decision

Deploy as a Docker Compose stack (`docker-compose.prod.yml`) of five services (`nginx`, `app`, `postgres`, `redis`, `queue`), with images built in CI and pushed to GHCR rather than built on the server. Only `nginx` is attached to a shared external `traefik-public` network and carries the Traefik labels for automatic HTTPS via Let's Encrypt; all other services stay on a private internal Docker network. Deployment itself runs over SSH (`.github/workflows/build-deploy.yml`): pull the new images, `docker compose up -d`, then run migrations (see [docs/operations.md](../operations.md)).

## Consequences

- No source code or build toolchain needs to live on the production server - only the compiled images and `docker-compose.prod.yml`/`.env.prod`.
- Traefik being a shared, pre-existing reverse proxy on the VPS means Summit Stats can share TLS/routing infrastructure with other projects on the same server.
- Keeping `postgres` and `redis` off the public network limits their exposure to the internal Docker network only.
- Deployment depends on the VPS already having a running Traefik instance with the expected network name and certificate resolver configured; this is documented but is an external prerequisite, not something this stack manages.
