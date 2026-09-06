[🇫🇷 Version française](architecture.fr.md) | 🇬🇧 English version

# Summit Stats - Architecture

[← Back to README](../README.md)

---

## Repository layout

```
summit-stats/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/     # ActivityController, LoginController, StatsController
│   │   ├── Requests/            # Form Requests (input validation)
│   │   └── Traits/              # ApiResponse - standardised JSON responses
│   ├── Models/                  # Activity, Segment, TrackPoint
│   └── Services/
│       ├── ActivityService.php  # Persistence: store, update, recalculate, destroy
│       ├── Geo/                 # GeoCalculatorService - shared Haversine math
│       └── Gpx/                 # GPX analysis pipeline (strict TDD)
├── resources/js/
│   ├── pages/         # Dashboard, Activities, ActivityDetail, Login
│   ├── components/    # NavBar, GpxUploadForm, StatCard, PctBar, ProgressionChart, map/elevation profile
│   ├── helpers/       # Formatting (distance, duration, speed, date)
│   ├── stores/        # Pinia store (activities)
│   └── router/        # Vue Router with authentication guard
├── routes/
│   └── api.php         # REST routes, all behind Sanctum except /login
├── config/
│   ├── slope_thresholds.php  # 5 slope classes: lt5, 5_15, 15_25, 25_35, gt35
│   └── geo.php                # Earth radius, pause threshold, OpenTopoData settings
└── database/
    ├── migrations/
    ├── factories/
    └── seeders/
```

---

## Backend - layered service architecture

Controllers are thin; all business logic lives in services.

### GPX analysis pipeline (`app/Services/Gpx/`)

Strict TDD, one service per pipeline step, orchestrated end-to-end:

1. `GpxParserService` - XML parsing to a normalized point array (lat/lon/ele/time)
2. `ElevationEnrichmentService` - optional altitude enrichment via the OpenTopoData API, with SSE progress callback
3. `ElevationCalculatorService` - Haversine distance, elevation gain/loss (D+/D-), smoothing, moving duration
4. `SegmentationService` - splits the track into typed segments (ascent/flat/descent) by slope class
5. `StatsAggregatorService` - aggregates 22 metrics from the segmented track
6. `GpxAnalysisOrchestrator` - coordinates the full pipeline

`GeoCalculatorService` (`app/Services/Geo/`) holds the Haversine math shared across the pipeline. `ActivityService` (`app/Services/`) orchestrates persistence: file storage, model creation, recalculation, cascade deletion.

---

## Frontend - Vue 3 Composition API

```
resources/js/
├── pages/         # Dashboard, Activities, ActivityDetail, Login
├── components/    # Charts (Chart.js), map (Leaflet/vue-leaflet), upload form, nav
├── helpers/       # Formatting utilities
├── stores/        # Pinia store (activities + auth state)
└── router/        # Vue Router, redirects to /login if no Bearer token
```

Axios is configured in `bootstrap.js` with a Bearer token interceptor. Upload progress is tracked via Server-Sent Events.

---

## Database

SQLite (file-based in dev, `:memory:` in tests). Three core tables:

- `activities` - metadata + all 22 aggregated stats
- `segments` - one row per ascent/flat/descent section, referencing track point indices
- `track_points` - raw GPS data (lat/lon/ele/time/distance/order), used for the map and elevation profile

---

## API

REST routes under `/api`, protected by Laravel Sanctum Bearer token (except `POST /login`). Responses use the `ApiResponse` trait for a consistent JSON structure. The activity creation route streams upload progress via SSE. See [docs/api/](api/) for the endpoint reference.
