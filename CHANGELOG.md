# Changelog

All notable changes to this project are documented in this file.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---
## [Unreleased]

### Added

- Harmonize Traefik configuration

- Replace token input with password-based login


### Changed

- Add V4 roadmap section to README files

- Update Docker section and CHANGELOG for Traefik harmonization

- Fix NavBar test mock to include axios.post

- Add CI compose override to fix missing traefik-public network

- Update auth tests for password-based login

- Fix logout test to avoid revoking shared TEST_TOKEN

- Export TEST_PASSWORD to GITHUB_ENV instead of step env block

- Use fixed CI password to avoid missing-secret issue

- Make curl health-check resilient to connection errors

- Update README and CHANGELOG for password-based login

- Remove paths-ignore from pull_request trigger

- Add hierarchized V4 roadmap for security, sharing, deployment and design

- Add static analysis and CI optimization to Phase 1 of the V4 roadmap

- Add Code of Conduct

- Split CONTRIBUTING.md into a bilingual file pair

- Split SECURITY.md into a bilingual file pair

- Automate CHANGELOG.md updates with git-cliff

- Extract roadmap into a dedicated bilingual document

- Extract product concept into a dedicated bilingual document

- Replace the obsolete bare-metal DEPLOY.md with an accurate operations guide

- Document Tailwind, Chart.js, and Leaflet conventions

- Extract API endpoints reference into a dedicated bilingual document

- Add architecture decision records generated from git history

- Add a pull request template

- Replace extracted sections with links, refresh stale figures

- Update CHANGELOG.md

- Bump laravel/tinker from 3.0.0 to 3.0.2

- Bump nunomaduro/collision from 8.9.1 to 8.9.4

- Bump laravel/pint from 1.29.0 to 1.29.1

- Bump laravel/sail from 1.54.0 to 1.67.0

- Bump laravel/sanctum from 4.3.1 to 4.3.3

- Bump @playwright/test from 1.58.2 to 1.62.1

- Bump dotenv from 17.3.1 to 17.4.2

- Bump vue from 3.5.30 to 3.5.42

- Upgrade vitest to 4.1.2 and jsdom to 30.0.1 together

- Update CHANGELOG.md

- Group minor and patch updates per ecosystem

- Homogenize changelog workflow


### Fixed

- Add explicit project name to avoid docker compose collision

- Add CORS support to Vite for Traefik local routing

- Remove /api/ prefix from login and logout calls

- Downgrade jsdom to 29.0.1 to keep Node 20 CI compatibility

## [3.0.0] — 2026-04-02

### Added

- Add Docker support and migrate to PostgreSQL

- Dockerize full dev stack and migrate to PostgreSQL

- Add frontend coverage upload to Codecov with flags

- Proxy and cache OSM tiles server-side via nginx


### Changed

- Update README for V2 delivery, badge and versions section

- Split README by language

- Add CHANGELOG, CONTRIBUTING, and SECURITY files

- Add self-hosting deployment guide

- Add Codecov coverage upload and Dependabot dependency updates

- Bump laravel/sail from 1.53.0 to 1.54.0

- Bump laravel/tinker from 2.11.1 to 3.0.0

- Bump @tailwindcss/vite from 4.2.1 to 4.2.2

- Bump @vitejs/plugin-vue from 6.0.4 to 6.0.5

- Trigger Codecov after app activation

- Add GitHub issue templates (bug report and feature request)

- Add codecov.yml with coverage targets and ignore rules

- Add Vitest unit test suite for JS/Vue layer

- Remove feat/** from push trigger to avoid duplicate CI runs on open PRs

- Add Playwright E2E test suite

- Extract e2e job to dedicated workflow, trigger on main push and workflow_dispatch only

- Seed a test activity in CI to unblock activity-detail tests

- Add upload API smoke test before Playwright to isolate storage issue

- Add PHP upload environment diagnostics before E2E tests

- Add error_log debug in ActivityService and dump app logs after smoke test

- Add open_basedir and fopen_tmp diagnostics to GPX store error_log

- Add direct copy() and uid/writable diagnostics to isolate Flysystem write failure

- Update README for post-V2 state — CI, Docker, testing, tech stack

- Rename laravel.yml to ci.yml and add paths-ignore for docs and markdown

- Add paths-ignore for docs and markdown in e2e workflow

- Configure flag_management to expose frontend coverage

- Replace single Codecov badge with per-flag backend and frontend badges

- Add CD workflow and fix docker-compose.prod.yml

- Verify pre-commit hook

- Add husky pre-commit hook with lint-staged

- Apply prettier and pint formatting to existing codebase

- Run linters before tests for faster feedback on style issues

- Gate deploy on E2E success via workflow_run

- Retrigger

- Trigger CI check

- Update README with CI/CD pipeline, production deployment and V3 completion


### Fixed

- Replace laravel-vite-plugin merge with standalone Vitest config

- Stabilise Playwright suite — locators, timing, coverage scope

- Create .env before docker compose up in e2e job

- Add DB_PASSWORD to e2e .env to match postgres container default

- Add dotenv as explicit dev dependency for playwright.config.js

- Generate APP_KEY before docker compose up and improve wait loop

- Replace non-existent User::activities() with Activity::exists() check

- PHP linting type error

- Initialize storage directories before running E2E tests

- Create storage/app/private in dev entrypoint

- PHP linting

- Replace Flysystem writeStream with string-based put to fix FPM upload

- Bypass Flysystem write with native copy() to fix PHP-FPM Docker issue

- Ensure gpx dir exists with 777 permissions and fix FPM pool user

- Complete www-dev.conf pool config after removing default www.conf

- Replace SCP with curl to fetch compose file over SSH

- Use ^~ prefix to prevent static assets regex from intercepting tile requests

- Disable IPv6 resolution for OSM proxy (VPS is IPv4-only)


### Revert

- Restore www.conf and minimal www-dev.conf

## [2.0.0] — 2026-03-17

### Added

- Add track_points table, model, factory, endpoint GET /activities/{id}/track

- Handle GPX without timing, return null speeds, add orchestrator tests

- Add ElevationProfile component with zoom and drag-to-zoom

- Add TrackMap component with Leaflet, OSM tiles, fitBounds on track

- Replace TileLayer with tileLayerOffline for OSM tile caching

- SSE upload with elevation enrichment progress, fix stream parsing, add toast notification

- Persist filters and page in URL query params

- 100% test coverage, add TrackPoint/ElevationEnrichment tests, codeCoverageIgnore on SSE closure

- PHPDoc on all public methods, fix ESLint warnings, 100% coverage maintained

- Add elevation profile to map synchronization on hover


### Changed

- Add V1/V2 versioning section, update roadmap and status badge

- Fixed status badge text

- Fixed status badge text

- Extract GeoCalculatorService, remove Haversine duplication across services

- Add v2 branch to CI triggers, disable elevation enrichment in CI

- Update README with V2 features and roadmap progress


### Fixed

- Replace streamedContent with direct ActivityService call in postActivityWithGpx helper, add single point GPX validation

## [1.0.0] — 2026-03-14

### Added

- Init Laravel 12 + Vue 3 + Pest + structure dossiers + slope_thresholds config

- Sanctum install + UserSeeder + HasApiTokens on User model

- Clean Laravel 12 install + Vue 3 SPA + Vue Router + Pinia + Axios

- Add Chart.js + BaseChart component

- Add activities and segments migrations

- Add Activity and Segment Eloquent models

- Add GpxParserService with TDD - parse GPX files into normalized trackpoints

- Add ElevationCalculatorService with TDD - Haversine distance, D+/D-, duration, noise threshold

- Add SegmentationService with TDD - slope classification and segment merging

- Add StatsAggregatorService with TDD - segment and activity stats aggregation

- Add GpxAnalysisOrchestrator - full GPX analysis pipeline

- Add smoothing to ElevationCalculatorService + geo config for earth radius constant

- Add regression test on real GPX track

- Add Form Requests, ApiResponse trait and global exception handler

- Add ActivityController POST endpoint + feature tests

- Add GET /api/activities endpoint + ActivityFactory + feature tests

- Add GET /api/activities/{id} endpoint + SegmentFactory + feature tests

- Add DELETE /api/activities/{id} endpoint + feature tests

- Add PUT /api/activities/{id} endpoint + feature tests

- Add GET /api/stats endpoint + StatsController + feature tests

- Setup Axios + Sanctum interceptor + activities Pinia store

- Add Activities list page with filters + format helpers

- Add GpxUploadForm modal component with drag & drop

- Add ActivityDetail page with segments table + StatCard component

- Add extended stats, moving duration, slope breakdown, recalculate command and endpoint

- Update Dashboard with new metrics, slope range filter, activity filter

- Add NavBar with navigation and logout


### Changed

- Restore and update README - P1 done, Laravel 12, security note

- Update regression test with extended stats assertions

- Reach 99% coverage - add tests for commands, models, traits and controllers

- Add PHPDoc on all public service methods

- Extract ActivityService, controllers delegate all business logic to services

- Apply Laravel Pint formatting - PSR-12 compliance

- Add ESLint + Prettier config, fix all linting issues

- Add GitHub Actions workflow with tests, coverage, pint and eslint

- Force Node.js 24 for GitHub Actions runners

- Update README - features, roadmap, prerequisites, CI badge

- Update .env.example with Summit Stats defaults and user seeder variables

- Update coverage metrics

- Bilingual README, highlight TDD architecture and 99% coverage

- Added license file / Fixed README structure

- Fixed small badge bug

- Fix Pint formatting on StatsController


### Fixed

- Get back old README

- Fix malformed APP_NAME quote in .env.example

- Read activity-level metrics directly from activities table, fix missing units


### Security

- Remove .env.testing from tracking


