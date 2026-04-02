[🇫🇷 Version française](README.fr.md) | 🇬🇧 English version

---

# 🏔️ Summit Stats

> *A full-stack Laravel 12 + Vue.js 3 application built with strict TDD, layered service architecture and 100% test coverage — from geospatial GPX parsing algorithms to a dynamic progression dashboard.*

![Status](https://img.shields.io/badge/Status-V2%20Delivered-brightgreen)
[![CI](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/ci.yml/badge.svg)](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/ci.yml)
[![E2E](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/e2e.yml/badge.svg)](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/e2e.yml)
[![CD](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/build-deploy.yml/badge.svg)](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/build-deploy.yml)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)
![License](https://img.shields.io/github/license/MarvinLeRouge/Summit-Stats?cacheSeconds=3600)

[![codecov backend](https://img.shields.io/codecov/c/github/MarvinLeRouge/Summit-Stats?flag=backend&label=backend&logo=codecov)](https://codecov.io/gh/MarvinLeRouge/Summit-Stats)
[![codecov frontend](https://img.shields.io/codecov/c/github/MarvinLeRouge/Summit-Stats?flag=frontend&label=frontend&logo=codecov)](https://codecov.io/gh/MarvinLeRouge/Summit-Stats)
![Tests](https://img.shields.io/badge/Tests-212%20passing-brightgreen)

---

## Versions

**V2 (current — [v2.0.0](https://github.com/MarvinLeRouge/Summit-Stats/releases/tag/v2.0.0))** — Geographic visualization: zoomable elevation profile with map synchronization, interactive OSM map with GPX trace, automatic altitude enrichment via OpenTopoData, SSE upload progress.

**V1 ([v1.0.0](https://github.com/MarvinLeRouge/Summit-Stats/releases/tag/v1.0.0))** — Full pipeline: GPX parsing, slope segmentation, 22 metrics per activity, REST API, Vue.js 3 dashboard, 99% test coverage, GitHub Actions CI.

---

## Concept

Standard sports apps (Strava, Garmin Connect) provide global statistics but fall short for meaningful progression analysis:

- *What is my ascent speed on slopes steeper than 25%?*
- *How has my endurance evolved on long mountain outings over the last six months?*
- *Am I faster in trail running than hiking on moderate gradients?*

Summit Stats segments each GPX trace by terrain type and slope class, then lets you compose your own progression metrics through a dynamic filtering interface.

---

## Key figures

| Metric | Value |
|---|---|
| Test coverage | **100%** (backend + frontend) |
| Automated tests | **118 backend · 62 frontend unit · 32 E2E = 212 tests** |
| API endpoints | **7 REST routes** |
| Metrics per activity | **22 stats stored in database** |
| GPX pipeline | **4 services, strict TDD** |
| PHP lines of code | ~2,000 (excluding migrations and config) |

---

## Features

- **GPX import** — drag & drop upload, automatic analysis on import
- **GPX analysis pipeline** — segmentation by type (ascent / descent / flat) and slope class, Haversine formula, elevation smoothing via sliding average window
- **22 stats per activity** — total and moving speed (pauses > 30s excluded), ascent speed (average / to summit / longest non-descending segment), descent rate, % breakdown by slope class for both ascent and descent
- **On-demand recalculation** — per-activity button + `php artisan stats:recalculate` command
- **Progression dashboard** — time-series charts recalculated on the fly by metric, type, environment, period and slope range
- **Advanced filters** — combinable: activity type, environment, period, specific activity, slope range (from / to)
- **Activity history** — paginated list with filters and summary stats

---

## Architecture

```
app/
├── Http/
│   ├── Controllers/Api/     # Thin controllers — delegate all logic to services
│   ├── Requests/            # Form Requests (input validation)
│   └── Traits/              # ApiResponse — standardised JSON responses
├── Models/                  # Activity, Segment
└── Services/
    ├── ActivityService.php              # Persistence: store, update, recalculate, destroy
    └── Gpx/                             # GPX analysis pipeline (strict TDD)
        ├── GpxParserService             # XML parsing → normalised point array
        ├── ElevationCalculatorService   # Haversine distance, D+/D-, total & moving duration
        ├── SegmentationService          # Segmentation by terrain type and slope class
        ├── StatsAggregatorService       # Aggregation of 22 metrics
        └── GpxAnalysisOrchestrator      # Full pipeline orchestration

resources/js/
├── pages/         # Dashboard, Activities, ActivityDetail, Login
├── components/    # NavBar, GpxUploadForm, StatCard, PctBar, ProgressionChart
├── helpers/       # Formatting (distance, duration, speed, date)
├── stores/        # Pinia store (activities)
└── router/        # Vue Router with authentication guard
```

---

## Testing

### Backend — Pest

```bash
php artisan test                        # run all tests
php artisan test --coverage             # with coverage report (requires pcov)
php artisan test --coverage --min=80    # with minimum threshold
```

- **Strict TDD** on all 4 GPX services — tests written before implementation
- **Feature tests** on all 7 API endpoints + Artisan command
- **Unit tests** on models and the `ApiResponse` trait
- **Regression test** on a real GPX trace (11.8 km, ~470 m elevation gain)
- In-memory SQLite (`:memory:`) — full isolation, entire suite runs in < 3s

### Frontend — Vitest

```bash
npm run test:coverage    # run unit tests with coverage report
```

- 62 tests, 100% coverage on components, helpers and Pinia store
- JSDOM environment, Vue Test Utils

### E2E — Playwright

```bash
npm run test:e2e    # requires the Docker stack to be running (see Docker section)
```

- 5 spec files, 32 scenarios covering auth, upload, dashboard, activity list and detail
- Runs against the full Docker stack (Nginx + PHP-FPM + PostgreSQL)
- Chromium only

---

## CI/CD

### Pre-commit hooks

Husky + lint-staged run automatically on every `git commit`:

- **JS/Vue** staged files — `eslint --fix` then `prettier --write`
- **PHP** staged files — `vendor/bin/pint`

Style issues are auto-fixed before the commit is recorded — they never reach CI.

### CI workflow

[`CI`](.github/workflows/ci.yml) — triggers on push and pull request to `main`:

1. PHP linting (Pint) + JS linting (ESLint) — runs **before** tests for fast failure on style issues
2. PHP backend tests with coverage (Pest + pcov) — report uploaded to Codecov (`backend` flag)
3. Frontend unit tests (Vitest) — report uploaded to Codecov (`frontend` flag)

### E2E workflow

[`E2E`](.github/workflows/e2e.yml) — triggers on push to `main`:

- Builds and starts the full Docker stack
- Runs migrations and seeds test data (user + sample activity)
- Runs the Playwright suite (Chromium)
- Uploads Playwright HTML report as artifact on failure (7-day retention)
- **Gates production deployment** — the CD workflow only triggers if E2E passes

### CD workflow

[`build-deploy`](.github/workflows/build-deploy.yml) — triggered by E2E success via `workflow_run`:

- Builds and pushes two Docker images to GHCR: `app` (PHP-FPM) and `nginx` (assets baked in)
- Images tagged with short SHA (`sha-xxxxxxx`) and `latest`
- Deploys to the production server via SSH: pulls new images, `docker compose up -d`, applies pending migrations
- `workflow_dispatch` available for manual redeploys (hotfixes, rollbacks) — bypasses E2E gate

```
push main
    ├── CI ─────────── lint → tests → coverage
    └── E2E ─────────── Playwright (full Docker stack)
              │ workflow_run (success only)
              ▼
         build-deploy ── build → GHCR → SSH deploy

workflow_dispatch ──────► build-deploy  (manual, bypasses E2E)
```

---

## Production

The application runs on a VPS behind a shared Traefik reverse proxy with automatic TLS via Let's Encrypt. Images are built in CI and stored in GHCR — no source code on the server, no build step in production.

### Stack

| Service | Image | Role |
|---|---|---|
| `nginx` | custom (nginx:alpine) | Web server, static assets, OSM tile proxy cache |
| `app` | custom (PHP 8.4-FPM) | Laravel application |
| `postgres` | postgres:16-alpine | Production database |
| `redis` | redis:7-alpine | Cache, sessions, queue |
| `queue` | custom (PHP 8.4-FPM) | Laravel queue worker |

Only `nginx` is exposed to Traefik via the shared `traefik-public` network. All other services run in a private Docker network.

### OSM tile proxy cache

Nginx proxies OpenStreetMap tile requests through `/tiles/{z}/{x}/{y}.png` and caches responses on a persistent Docker volume (capped at 1 GB, TTL 30 days). Reduces load on OSM's infrastructure and speeds up repeat visits to previously explored areas.

---

## API endpoints

All routes protected by `Authorization: Bearer {token}`.

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/activities` | GPX import + automatic analysis |
| `GET` | `/api/activities` | Paginated list (filters available) |
| `GET` | `/api/activities/{id}` | Detail + segments |
| `PUT` | `/api/activities/{id}` | Update metadata |
| `DELETE` | `/api/activities/{id}` | Delete activity + GPX file |
| `POST` | `/api/activities/{id}/recalculate` | Recalculate stats from raw GPX |
| `GET` | `/api/stats` | Progression data for charts |

**Example — `/api/stats`**

```
GET /api/stats?metric=avg_ascent_speed_mh&type=trail&slope_min=15&slope_max=35&date_from=2024-01-01
```

```json
{
  "data": [
    { "date": "2024-03-15", "value": 423.5, "activity_title": "Aiguilles Rouges" },
    { "date": "2024-04-02", "value": 512.0, "activity_title": "Col de Balme" }
  ],
  "meta": { "metric": "avg_ascent_speed_mh", "unit": "m/h", "count": 2 }
}
```

---

## Installation

**Prerequisites** — PHP >= 8.3 (`pdo_sqlite`, `sqlite3`, `xml`, `mbstring`, `fileinfo`, `pcov`), Composer >= 2, Node.js >= 18

```bash
git clone https://github.com/MarvinLeRouge/Summit-Stats.git && cd Summit-Stats
composer install && npm install
cp .env.example .env
# Fill in APP_USER_NAME, APP_USER_EMAIL, APP_USER_PASSWORD
php artisan key:generate
# ⚠️ Manually create .env.testing with DB_DATABASE=:memory: (not committed)
touch database/database.sqlite && php artisan migrate
php artisan db:seed --class=UserSeeder   # token printed in console
php artisan serve                         # → http://localhost:8000
npm run dev                               # → http://localhost:5173 (HMR)
```
---

## Docker

A full development stack is provided via Docker Compose:

| Service | Description | Host port |
|---|---|---|
| `nginx` | Web server | 8081 |
| `app` | PHP-FPM (Laravel) | — |
| `postgres` | PostgreSQL 16 | 5433 |
| `redis` | Redis 7 | 6379 |
| `queue` | Laravel queue worker | — |
| `vite` | Vite dev server (HMR) | 5174 |

```bash
cp .env.example .env
# Fill in APP_USER_NAME, APP_USER_EMAIL, APP_USER_PASSWORD and DB_PASSWORD
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=UserSeeder   # token printed in console
```

The app is then available at `http://localhost:8081`.

> Note: PostgreSQL is used in Docker. SQLite (`:memory:`) is used only for unit/feature tests.

---

## About

Personal project with a dual purpose:

- **Learning** — layered service architecture (TDD, single responsibility, dependency injection), geospatial algorithms (Haversine, sliding average smoothing, GPS trace segmentation), and modern tooling (Laravel 12, Vue.js 3 Composition API, Pinia, Chart.js, Pest, GitHub Actions)
- **Portfolio** — demonstrates a structured, tested and documented approach, from scoping to CI

---

## 🗂️ Slope classes

| Class | Label | Range |
|---|---|---|
| `lt5`   | Flat     |  < 5 %      |
| `5_15`  | Moderate | 5 % – 15 %  |
| `15_25` | Steep    | 15 % – 25 % |
| `25_35` | Hard     | 25 % – 35 % |
| `gt35`  | Extreme  | > 35 %      |

---

## 🛠️ Tech stack

| Layer | Technology |
|---|---|
| Backend | ![Laravel](https://img.shields.io/badge/Laravel_12-FF2D20?logo=laravel&logoColor=white&style=flat-square) ![PHP](https://img.shields.io/badge/PHP_8.4-777BB4?logo=php&logoColor=white&style=flat-square) |
| Database | ![PostgreSQL](https://img.shields.io/badge/PostgreSQL_16-336791?logo=postgresql&logoColor=white&style=flat-square) ![SQLite](https://img.shields.io/badge/SQLite_%3Amemory%3A_(tests)-003B57?logo=sqlite&logoColor=white&style=flat-square) |
| Auth | Laravel Sanctum (single-user token) |
| Frontend | ![Vue.js](https://img.shields.io/badge/Vue.js_3-4FC08D?logo=vuedotjs&logoColor=white&style=flat-square) ![Vite](https://img.shields.io/badge/Vite-646CFF?logo=vite&logoColor=white&style=flat-square) |
| Charts | ![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?logo=chartdotjs&logoColor=white&style=flat-square) |
| Map | ![Leaflet](https://img.shields.io/badge/Leaflet-199900?logo=leaflet&logoColor=white&style=flat-square) |
| State | ![Pinia](https://img.shields.io/badge/Pinia-FFD859?logo=pinia&logoColor=black&style=flat-square) |
| Backend testing | ![Pest](https://img.shields.io/badge/Pest-8A2BE2?style=flat-square) — strict TDD, 100% coverage |
| Frontend testing | ![Vitest](https://img.shields.io/badge/Vitest-6E9F18?logo=vitest&logoColor=white&style=flat-square) + Vue Test Utils — 100% coverage |
| E2E testing | ![Playwright](https://img.shields.io/badge/Playwright-2EAD33?logo=playwright&logoColor=white&style=flat-square) (Chromium) |
| PHP linting | ![Pint](https://img.shields.io/badge/Laravel_Pint-FF2D20?logo=laravel&logoColor=white&style=flat-square) (PSR-12) |
| JS linting | ![ESLint](https://img.shields.io/badge/ESLint-4B32C3?logo=eslint&logoColor=white&style=flat-square) ![Prettier](https://img.shields.io/badge/Prettier-F7B93E?logo=prettier&logoColor=black&style=flat-square) |
| Infrastructure | ![Docker](https://img.shields.io/badge/Docker-2496ED?logo=docker&logoColor=white&style=flat-square) ![Nginx](https://img.shields.io/badge/Nginx-009639?logo=nginx&logoColor=white&style=flat-square) PHP-FPM |
| Production | ![Traefik](https://img.shields.io/badge/Traefik-24A1C1?logo=traefikproxy&logoColor=white&style=flat-square) reverse proxy + Let's Encrypt TLS |
| Pre-commit | ![Husky](https://img.shields.io/badge/Husky-000000?style=flat-square&logo=git&logoColor=white) + lint-staged (auto-fix on staged files) |
| CI | ![GitHub Actions](https://img.shields.io/badge/GitHub_Actions-2088FF?logo=githubactions&logoColor=white&style=flat-square) ![Codecov](https://img.shields.io/badge/Codecov-F01F7A?logo=codecov&logoColor=white&style=flat-square) |
| CD | ![GHCR](https://img.shields.io/badge/GHCR-181717?logo=github&logoColor=white&style=flat-square) GitHub Container Registry |

---

## 🗺️ Roadmap

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

---

## 📋 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
