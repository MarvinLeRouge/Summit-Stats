[🇫🇷 Version française](roadmap.fr.md) | 🇬🇧 English version

---

# Roadmap

### ✅ V1 - Delivered

- [x] Project scoping and architecture
- [x] **P1**: Setup (Laravel 12, Pest, Sanctum, Vue.js 3)
- [x] **P2**: Data model / Modèle de données (migrations, Eloquent)
- [x] **P3**: GPX algorithm (parsing, segmentation, stats), *strict TDD*
- [x] **P4**: REST API (7 endpoints + feature tests)
- [x] **P5**: Vue.js frontend (dashboard, charts, dynamic filters)
- [x] **P6**: Quality (99% coverage, PHPDoc, Pint, ESLint)
- [x] **P7**: DevOps (GitHub Actions CI, documentation)

### ✅ V2 - Delivered

- [x] **P1**: Track points storage + `/api/activities/{id}/track` endpoint
- [x] **P2**: Elevation profile (Chart.js + zoom)
- [x] **P3**: OSM map with GPX trace (Leaflet)
- [x] **P4**: Quality (tests, coverage, linting)
- [x] **P5**: DevOps (CI update, V2 documentation)

### ✅ V3 - Delivered

- [x] E2E test suite (Playwright, 32 scenarios, dedicated CI workflow)
- [x] Docker Compose stack (Nginx, PHP-FPM, PostgreSQL, Redis, queue worker)
- [x] Frontend coverage reporting on Codecov (per-flag badges)
- [x] Production deployment: Traefik, HTTPS, GHCR, SSH CD pipeline
- [x] Pre-commit hooks: Husky + lint-staged (auto-fix PHP and JS/Vue)
- [x] Optimised CI/CD pipeline: lint-first, E2E-gated deploy, workflow chaining

### 🔜 V4 - In progress

Full plan: `docs/work-in-progress/v4-action-plan.md` (local file, not tracked in git).

**Phase 1 - Security hardening (target v3.1.0)**
- [x] Password authentication - password-based login form (Sanctum via credentials)
- [x] Rate limiting on login, Sanctum token expiration policy
- [x] Explicit CORS configuration and security headers (CSP, HSTS, X-Frame-Options, ...)
- [ ] Automated database backup - scheduled `pg_dump` on the VPS, rotation, restore documentation
- [x] OWASP Top 10:2025 + ASVS audit pass
- [x] Static analysis (PHPStan/Larastan) in the pre-commit hook and CI
- [x] CI pipeline audit and optimization

**Phase 2 - Read-only sharing (target v4.0.0)**
- [ ] Read-only viewer accounts with per-activity visibility control
- [ ] Email invite and activation flow (Brevo)
- [ ] Public read-only demo account

**Phase 3 - Portfolio deployment (target v4.1.0)**
- [ ] Dedicated public portfolio subdomain (demo and viewer accounts) behind Traefik, distinct from the current private production instance, dev/prod parity re-verified
- [ ] Activity export - CSV or JSON download from the interface

**Phase 4 - Design overhaul (target v4.2.0)**
- [ ] Full visual identity refresh
- [ ] Dark mode
- [ ] Accessibility pass

## Section 5 - Maintenance

- [x] FIX-001 - Patch dependency advisories blocking the CI security gates (shell-quote, vite, undici, follow-redirects, vitest, symfony, laravel)
- [x] CI-001 - Run CI on the changelog bot PR so its auto-merge can complete
- [x] TEST-001 - Close per-file coverage gaps: remove the one-shot `MigrateFromSqlite` command (data already migrated)
- [x] DOCS-001 - Describe PostgreSQL as the development database (README, CONTRIBUTING, architecture, ADR 0004 update)
- [x] FIX-002 - Pin the pecl redis extension and cache the E2E Docker build (transient pecl.php.net outage broke the merge E2E)
- [x] FIX-003 - Set DB_PORT=5433 in .env.example to match the host port of the dev postgres container
- [x] FIX-004 - Run npm ci in the vite dev container so it stops rewriting package-lock.json
- [x] FIX-005 - Keep the health check test working with Laravel 12.73 (partial DB mock), update the Laravel minor and patch group with Symfony held on 7.4
- [x] CHORE-001 - Move to Node 26 LTS: `node:20-alpine` in the Dockerfile and the vite dev service, CI on 22, add `engines` (action majors handled with the cross-project actions task)
- [ ] DOCS-002 - Update the README CI section: it describes sequential lint-then-tests, `ci.yml` now runs path-filtered parallel jobs behind the `ci-status` aggregator
- [x] CHORE-002 - Load prod config from shared/env (app.env + secrets.env) instead of compose/.env.prod, single copy of the database password
- [x] CI-002 - Move every GitHub Action to its current major pinned by commit SHA, changelog workflow on Node 26, Dependabot docker and github-actions entries
- [x] CI-003 - Rename the deploy workflow to build-push-deploy.yml, two explicit jobs (Build & push, Deploy), no functional change
- [x] CHORE-003 - Update eslint and @eslint/js to 10
- [x] CHORE-004 - Move to PHP 8.5 (Docker image, CI, composer constraint, docs)
- [x] CHORE-005 - Upgrade to Laravel 13 with Pest 4 and PHPUnit 12 (Symfony held on 7.4)

## Section 6 - Observability

- [x] FEAT-001 - Emit production logs as JSON (one object per line on stderr), constant log messages with context
- [x] FEAT-002 - Report unhandled Laravel and frontend errors to Sentry, frontend events relayed through the backend
- [x] FEAT-003 - Health check endpoint `/health` verifying the database and Redis
