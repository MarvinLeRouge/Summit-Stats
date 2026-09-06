[🇫🇷 Version française](frontend_developer_guide.fr.md) | 🇬🇧 English version

---

# Frontend developer guide

This guide covers day-to-day frontend development conventions. See
[docs/design-system.md](../design-system.md) for the styling, charts and map
conventions currently in place.

## Getting started

```bash
composer setup       # Full setup (deps + migrate + build) if not done yet
npm run dev          # Vite dev server (port 5173, HMR)
```

`composer dev` runs the Laravel server, queue worker, logs and Vite HMR
concurrently. See [docs/operations.md](../operations.md) for the Docker
Compose alternative.

## Structure

```
resources/js/
├── pages/         # Dashboard, Activities, ActivityDetail, Login
├── components/    # NavBar, GpxUploadForm, StatCard, PctBar, ProgressionChart...
├── helpers/       # Formatting (distance, duration, speed, date)
├── stores/        # Pinia store (activities)
└── router/        # Vue Router with authentication guard
```

Pages compose components and read/write through the Pinia store; formatting
logic (distance, duration, speed, date) belongs in `helpers/`, not duplicated
inline in components.

## Adding a page

1. Create the page component under `pages/`.
2. Add or extend a Pinia store under `stores/` if the page needs shared
   state; keep API calls in the store, not in the component.
3. Register the route in `router/`; the navigation guard already redirects to
   `/login` when there's no Bearer token, so protected pages don't need to
   re-implement that check.
4. Add a `.spec.js` test file alongside the page or component.

## Charts and maps

- All charts go through `BaseChart.vue`: it registers Chart.js once and owns
  the mount/update/destroy lifecycle of the `<canvas>`. A new chart should
  build its `type`/`data`/`options` object and pass it to `BaseChart`, not
  reimplement the Chart.js lifecycle.
- `TrackMap.vue` renders GPS tracks via Leaflet through the app's own OSM
  tile proxy (never a direct call to a public tile server, see
  [docs/operations.md](../operations.md)).
- The elevation profile and the map stay synchronized through hover events:
  `ElevationProfile.vue` emits `{ lat, lon }`, and `TrackMap.vue` reacts by
  drawing a highlighted marker at that position. Follow the same event
  pattern for any new synchronized visualization.

## API calls and auth

- Axios is configured in `bootstrap.js` with a Bearer token interceptor;
  call through that shared instance rather than configuring a new HTTP
  client.
- The GPX upload flow streams progress via Server-Sent Events; if you add
  another long-running operation with progress feedback, follow the same SSE
  pattern rather than polling.

## Styling conventions

- Tailwind CSS v4, configured via `resources/css/app.css` (no
  `tailwind.config.js`); use the default palette and scale directly, no
  custom design tokens.
- Follow the conventions already observed across components (see
  [docs/design-system.md](../design-system.md)): cards use
  `bg-white rounded-lg shadow-sm border`, ascent data uses green accents,
  descent data uses blue accents.

## Testing conventions

```bash
npm run test:coverage    # Vitest, with coverage report
```

JSDOM environment, Vue Test Utils. Mirror the test structure to the source
structure: a component's spec file sits alongside the component.

## Linting

```bash
npm run lint      # ESLint check (JS/Vue)
npm run format    # Prettier auto-format
```

Husky + lint-staged run these automatically on staged files at commit time.
