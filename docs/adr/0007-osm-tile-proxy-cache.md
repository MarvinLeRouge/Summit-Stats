# 0007 - Proxy and cache for OSM map tiles

- Status: Accepted
- Date: 2026-04-02

## Context

`TrackMap.vue` (see [0006](0006-chartjs-leaflet-for-visualization.md)) initially fetched OpenStreetMap tiles directly from OSM's public tile servers. Calling a third-party tile server directly from every client on every map view is unnecessary load on OSM's shared infrastructure and adds latency for repeat visits to the same area.

## Decision

Proxy OSM tile requests server-side through nginx, at `/tiles/{z}/{x}/{y}.png`, and cache responses on a persistent Docker volume (capped at 1 GB, 30-day TTL). The frontend requests tiles from this proxy path instead of an external OSM host, using `leaflet.offline`'s `tileLayerOffline` (see [design-system.md](../design-system.md)).

## Consequences

- Reduces repeated load on OSM's public tile infrastructure and speeds up revisits to previously explored areas.
- Nginx configuration needed a specific location match precedence (`^~` prefix) so the tile route isn't intercepted by the generic static-assets regex.
- The cache is capped and time-limited, so it does not grow unbounded on the production volume.
