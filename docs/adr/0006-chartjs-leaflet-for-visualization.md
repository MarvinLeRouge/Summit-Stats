# 0006 - Chart.js and Leaflet for data visualization and mapping

- Status: Accepted
- Date: 2026-03-16

## Context

Summit Stats needs two distinct kinds of visualization: progression charts and elevation profiles (time series data), and a geographic map showing the GPX trace with a marker synchronized to the elevation profile's hover position. These are different rendering problems (canvas-based charting vs. tile-based mapping) with different mature libraries in the Vue ecosystem.

## Decision

Use Chart.js for all chart rendering (progression charts, elevation profile), wrapped in a single generic `BaseChart.vue` component that owns the Chart.js instance lifecycle (see [design-system.md](../design-system.md)). Use Leaflet, via `@vue-leaflet/vue-leaflet`, for the OSM map (`TrackMap.vue`), rendering the GPX polyline and a circle marker driven by hover events from the elevation profile.

## Consequences

- One reusable chart wrapper keeps Chart.js setup/teardown logic in one place instead of duplicated per chart.
- Leaflet's marker/polyline API made it straightforward to synchronize the map with the elevation profile's hover state through simple prop/event passing between the two components.
- Two separate visualization libraries means two sets of conventions to know, but each is used for what it is best at (canvas charts vs. tile maps) rather than forcing one library to do both.
