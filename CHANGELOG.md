# Changelog

All notable changes to this project are documented in this file.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- **Password-based login** — `POST /api/login` (public) validates the password against the seeded user and returns a fresh Sanctum token; the login page now shows a password field instead of a raw token input
- **Server-side logout** — `POST /api/logout` (Bearer) revokes the current token; the NavBar logout button calls it before clearing localStorage
- **`docker-compose.ci.yml`** — CI override: `traefik-public` declared as a local bridge (no external Traefik required), nginx exposed on port `8081`

### Changed
- Badges and key figures updated: 118 tests, 376 assertions, 100% coverage
- JSDoc added to all Vue components, pages, Pinia store, router, and helpers
- **Docker dev stack**: nginx no longer bound to a host port; routing now goes through a local Traefik instance at `summit-stats.marvinlerouge.local`; debug ports (postgres, redis, vite) restricted to `127.0.0.1`; explicit `traefik-public` (external) and `internal` (bridge) networks on all services
- **Docker prod stack**: implicit `default` network replaced by explicit `internal` bridge; HTTP→HTTPS redirect router removed (handled at the Traefik level)
- **Vite dev server**: `cors: true` added to allow asset loading when the app is accessed via a domain name through Traefik

---

## [2.0.0] — 2026-03-17

### Added
- **Elevation profile** — Chart.js area chart (distance vs altitude) with scroll-wheel zoom and drag-to-zoom
- **OSM map** — Leaflet map with OpenStreetMap tiles, GPX polyline, start/end markers, offline tile caching
- **Profile ↔ map synchronization** — hovering the elevation profile highlights the corresponding point on the map
- **Elevation enrichment** — automatic altitude retrieval via OpenTopoData API for GPX files missing elevation data (e.g. C:Geo exports)
- **SSE upload progress** — real-time progress streaming via Server-Sent Events during elevation enrichment
- **GPX without timing / without altitude** — conditional display of timing-dependent stats; map and profile functional in all cases
- `GET /api/activities/{id}/track` — new endpoint returning raw track points for map and profile rendering
- `TrackMap.vue`, `ElevationProfile.vue` — new frontend components

### Changed
- `GeoCalculatorService` extracted to deduplicate Haversine logic across pipeline services
- PHPDoc added to all public PHP methods
- 100% test coverage (up from 99%)

### Fixed
- Nullable `avg_speed_kmh` on segments without timing data
- Single-point GPX files now rejected with a clear validation message
- Stream parsing fixed for chunked SSE responses

---

## [1.0.0] — 2026-03-14

### Added
- **GPX pipeline** — parsing, elevation smoothing (sliding average + threshold), Haversine distances, segmentation by type (ascent / flat / descent) and slope class (5 categories), 22 aggregated metrics per activity
- **REST API** — 7 endpoints protected by Laravel Sanctum (list, show, store, update, destroy, recalculate, stats)
- **Vue.js 3 SPA** — progression dashboard with dynamic metric/type/environment/slope/date filters, activity list with pagination, activity detail with segment table
- **GPX drag & drop upload** form with metadata input
- `php artisan stats:recalculate` Artisan command
- GitHub Actions CI — tests with coverage (min 80%), Laravel Pint, ESLint
- 99% test coverage (89 tests, 271 assertions)

---

[Unreleased]: https://github.com/MarvinLeRouge/Summit-Stats/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/MarvinLeRouge/Summit-Stats/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/MarvinLeRouge/Summit-Stats/releases/tag/v1.0.0
