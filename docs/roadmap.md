[🇫🇷 Version française](roadmap.fr.md) | 🇬🇧 English version

---

# Roadmap

### ✅ V1 — Delivered

- [x] Project scoping and architecture
- [x] **P1** — Setup (Laravel 12, Pest, Sanctum, Vue.js 3)
- [x] **P2** — Data model / Modèle de données (migrations, Eloquent)
- [x] **P3** — GPX algorithm (parsing, segmentation, stats) — *strict TDD*
- [x] **P4** — REST API (7 endpoints + feature tests)
- [x] **P5** — Vue.js frontend (dashboard, charts, dynamic filters)
- [x] **P6** — Quality (99% coverage, PHPDoc, Pint, ESLint)
- [x] **P7** — DevOps (GitHub Actions CI, documentation)

### ✅ V2 — Delivered

- [x] **P1** — Track points storage + `/api/activities/{id}/track` endpoint
- [x] **P2** — Elevation profile (Chart.js + zoom)
- [x] **P3** — OSM map with GPX trace (Leaflet)
- [x] **P4** — Quality (tests, coverage, linting)
- [x] **P5** — DevOps (CI update, V2 documentation)

### ✅ V3 — Delivered

- [x] E2E test suite (Playwright — 32 scenarios, dedicated CI workflow)
- [x] Docker Compose stack (Nginx, PHP-FPM, PostgreSQL, Redis, queue worker)
- [x] Frontend coverage reporting on Codecov (per-flag badges)
- [x] Production deployment — Traefik, HTTPS, GHCR, SSH CD pipeline
- [x] Pre-commit hooks — Husky + lint-staged (auto-fix PHP and JS/Vue)
- [x] Optimised CI/CD pipeline — lint-first, E2E-gated deploy, workflow chaining

### 🔜 V4 - In progress

Full plan: `docs/work-in-progress/v4-action-plan.md` (local file, not tracked in git).

**Phase 1 - Security hardening (target v3.1.0)**
- [x] Password authentication - password-based login form (Sanctum via credentials)
- [ ] Rate limiting on login, Sanctum token expiration policy
- [ ] Explicit CORS configuration and security headers (CSP, HSTS, X-Frame-Options, ...)
- [ ] Automated database backup - scheduled `pg_dump` on the VPS, rotation, restore documentation
- [ ] OWASP Top 10:2025 + ASVS audit pass
- [ ] Static analysis (PHPStan/Larastan) in the pre-commit hook and CI
- [ ] CI pipeline audit and optimization

**Phase 2 - Read-only sharing (target v4.0.0)**
- [ ] Read-only viewer accounts with per-activity visibility control
- [ ] Email invite and activation flow (Brevo)
- [ ] Public read-only demo account

**Phase 3 - Portfolio deployment (target v4.1.0)**
- [ ] Public subdomain live behind Traefik, dev/prod parity maintained
- [ ] Activity export - CSV or JSON download from the interface

**Phase 4 - Design overhaul (target v4.2.0)**
- [ ] Full visual identity refresh
- [ ] Dark mode
- [ ] Accessibility pass
