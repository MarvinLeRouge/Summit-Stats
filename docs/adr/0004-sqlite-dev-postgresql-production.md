# 0004 - SQLite for dev/test, PostgreSQL for production

- Status: Accepted
- Date: 2026-03-30

## Context

Summit Stats started as a single-user local tool, where SQLite (file-based in dev, `:memory:` in tests) is simple to set up and fast for the test suite. As the project moved toward a real deployment (Docker Compose stack behind Traefik), a database better suited to a long-running server process was needed.

## Decision

Keep SQLite for local development and the automated test suite (zero setup, fast, in-memory for tests), and switch to PostgreSQL for production, running as its own container in the Docker Compose stack.

## Consequences

- Local development and CI stay fast and dependency-free (no database server to run for `php artisan test`).
- Production gets a database engine designed for concurrent access and long-running uptime.
- Any SQL that is not portable between SQLite and PostgreSQL is a risk; the codebase relies on Eloquent/query builder rather than raw dialect-specific SQL to avoid this.
- Migrations are exercised against SQLite in CI and PostgreSQL in production, so a migration that only works on one engine could, in principle, slip through - this has not been a problem in practice given the migrations used so far.
