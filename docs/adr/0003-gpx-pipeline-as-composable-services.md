# 0003 - GPX analysis pipeline as five composable services

- Status: Accepted
- Date: 2026-03-12

## Context

Turning a raw GPX file into the 22 aggregated metrics shown on the dashboard involves several distinct concerns: parsing XML, optionally enriching missing elevation data, computing distances/elevation gain/moving duration, segmenting the track by terrain type and slope class, and aggregating the final statistics. Building this as one large routine would be hard to test step by step and hard to reason about when a specific metric looks wrong.

## Decision

Split the pipeline into five focused services under `app/Services/Gpx/`, each with a single responsibility and its own unit tests, coordinated by an orchestrator:

1. `GpxParserService` - XML to normalized track points (lat/lon/ele/time)
2. `ElevationEnrichmentService` - optional altitude enrichment via the OpenTopoData API
3. `ElevationCalculatorService` - Haversine distances, elevation gain/loss, smoothing, moving duration
4. `SegmentationService` - splits the track into typed segments (ascent/flat/descent) with slope class
5. `StatsAggregatorService` - aggregates the 22 stored metrics from segments

`GpxAnalysisOrchestrator` coordinates the full sequence, and `GeoCalculatorService` holds the Haversine math shared across steps.

## Consequences

- Each step can be unit-tested with fixture GPX data in isolation, which is how the pipeline was built with strict TDD from the first commit.
- A bug in one metric can usually be traced to one service rather than a monolithic calculation.
- Adding a new pipeline step (e.g. a future enrichment source) means adding one service and wiring it into the orchestrator, without touching the others.
