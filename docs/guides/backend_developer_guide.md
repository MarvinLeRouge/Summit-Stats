[🇫🇷 Version française](backend_developer_guide.fr.md) | 🇬🇧 English version

---

# Backend developer guide

This guide covers day-to-day backend development conventions. See the
[README's architecture section](../../README.md#architecture) for the module
map, and [docs/api/api_endpoints.md](../api/api_endpoints.md) for the full
route reference.

## Getting started

```bash
composer setup          # Install deps, migrate, build assets
composer dev            # Laravel server + queue worker + logs + Vite HMR
```

See [docs/operations.md](../operations.md) for the Docker Compose alternative.

## Layered architecture

Controllers are thin: all business logic lives in services.

```
app/
├── Http/
│   ├── Controllers/Api/     # Thin controllers - delegate all logic to services
│   ├── Requests/            # Form Requests (input validation)
│   └── Traits/              # ApiResponse - standardised JSON responses
├── Models/                  # Activity, Segment
└── Services/
    ├── ActivityService.php  # Persistence: store, update, recalculate, destroy
    └── Gpx/                 # GPX analysis pipeline (strict TDD)
```

Data flows one way: `route -> controller -> Form Request -> service -> model`.
A controller never contains business logic; it validates via a Form Request,
calls a service, and returns through the `ApiResponse` trait.

## The GPX analysis pipeline

`GpxAnalysisOrchestrator` (`app/Services/Gpx/`) coordinates five steps, run in
this order:

1. `GpxParserService` - XML -> normalized track points (lat/lon/ele/time)
2. `ElevationEnrichmentService` - optional altitude enrichment via the
   OpenTopoData API, with an SSE progress callback
3. `ElevationCalculatorService` - Haversine distances, elevation gain/loss,
   smoothing, moving duration
4. `SegmentationService` - splits the track into typed segments
   (ascent/flat/descent) with a slope class
5. `StatsAggregatorService` - aggregates the 22 stored metrics from segments

`GeoCalculatorService` (`app/Services/Geo/`) holds the shared Haversine math
used across the pipeline; don't duplicate distance/bearing calculations
elsewhere.

## Adding a pipeline step or a new metric

1. If it's a new stored metric, add the column via a migration on
   `activities` (or `segments` if it's per-segment), and extend
   `StatsAggregatorService` to compute it.
2. If it changes segmentation logic (e.g. a new slope class), update
   `config/slope_thresholds.php` rather than hardcoding thresholds in
   `SegmentationService`.
3. Add a Pest unit test for the service under
   `tests/Unit/Services/Gpx/`, following strict TDD: write the test before
   the implementation.
4. Run `php artisan stats:recalculate` locally to verify existing activities
   recompute correctly with the change.

## Adding an API endpoint

1. Add the route under `/api` in `routes/api.php`, protected by Sanctum
   Bearer token (except `/api/login`).
2. Add a Form Request for input validation if the endpoint takes a payload.
3. Add a thin controller method in `Http/Controllers/Api/` that validates,
   calls the relevant service, and returns via the `ApiResponse` trait for a
   consistent JSON shape.
4. Add the route to [docs/api/api_endpoints.md](../api/api_endpoints.md)
   (and its French mirror).
5. Add a Feature test under `tests/Feature/Api/` covering the endpoint.

## Testing conventions

```bash
php artisan test                            # Run all Pest tests
php artisan test --filter=GpxParserService  # Run a single test file/class
php artisan test --coverage --min=80        # With coverage (requires pcov)
```

- Feature tests in `tests/Feature/Api/` cover all API endpoints end-to-end.
- Unit tests in `tests/Unit/Services/Gpx/` cover each pipeline step in
  isolation.
- Tests run against in-memory SQLite (`:memory:`), with `Storage::fake('local')`
  and `Sanctum::actingAs($user)` for authenticated requests.
- GPX fixture files live in `tests/Fixtures/gpx/`; add new fixtures there
  rather than inlining GPX XML in test files.
- Coverage is enforced in CI at `--min=80`.

## Conventions

- PHP style follows PSR-12, enforced by Pint (`vendor/bin/pint`); run
  `vendor/bin/pint --test` before committing.
- Services are single-responsibility: one pipeline step, one class.
- `.env`, `.env.testing`, and any file containing secrets must never be
  committed.
- Commit messages follow Conventional Commits; see the repository's
  `CLAUDE.md`.
