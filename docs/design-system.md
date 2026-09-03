# Design system

## Styling — Tailwind CSS v4

Tailwind is configured via CSS, not a `tailwind.config.js` file (`@tailwindcss/vite` plugin, v4 style).

`resources/css/app.css`:
- `@import 'tailwindcss';`
- `@source` directives extend class scanning to Blade pagination views and `resources/**/*.{blade.php,js}`
- `@theme` overrides the default font stack: `--font-sans: 'Instrument Sans', ui-sans-serif, system-ui, ...`

No other design tokens (colors, spacing, radii) are customized — components use Tailwind's default palette and scale directly.

### Conventions observed across components

- Cards: `bg-white rounded-lg shadow-sm border`
- Muted / label text: `text-gray-400` or `text-gray-500`, often `text-xs font-semibold uppercase`
- Primary numeric values: `text-gray-700` / `text-gray-900`, medium/semibold weight
- Ascent-related data: green accents (e.g. `bg-green-300`)
- Descent-related data: blue accents (e.g. `bg-blue-300`)
- Interactive/hover states: `transition-all duration-500` on animated bars (see `PctBar.vue`)

## Charts — Chart.js

All charts go through a single wrapper, `resources/js/components/BaseChart.vue`:
- Registers `Chart.register(...registerables)` once, then instantiates a `Chart` on a `<canvas>` in `onMounted`
- Destroys the instance in `onUnmounted` to avoid leaking canvases on navigation
- Reactively pushes new data into the existing instance (`chartInstance.data = ...; chartInstance.update()`) via a deep `watch` on the `data` prop, instead of recreating the chart

Feature-specific charts (`ProgressionChart.vue`, `ElevationProfile.vue`) build their `type`/`data`/`options` objects and pass them down to `BaseChart`, keeping Chart.js configuration out of the generic wrapper.

## Maps — Leaflet

`TrackMap.vue` renders GPS tracks via `@vue-leaflet/vue-leaflet`:
- Tiles are served through the app's own OSM tile proxy/cache (`/tiles/{z}/{x}/{y}.png`, see [operations.md](operations.md)) using `leaflet.offline`'s `tileLayerOffline`, not a direct call to a public OSM tile server
- The polyline and hover marker use a fixed blue palette: `#3B82F6` (track), `#2563EB` (hovered point)
- The map auto-fits its bounds to the track's lat/lon extent on load
- `ElevationProfile.vue` emits hover events with `{ lat, lon }`; `TrackMap.vue` reacts by drawing a highlighted `l-circle-marker` at that position — this is how the elevation profile and the map stay synchronized
