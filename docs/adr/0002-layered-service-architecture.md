# 0002 - Layered service architecture with thin controllers

- Status: Accepted
- Date: 2026-03-12

## Context

The core value of Summit Stats is a non-trivial GPX analysis pipeline (parsing, elevation calculation, segmentation, aggregation) built from day one with strict TDD. Putting this logic directly in controllers would make it difficult to test in isolation and to reason about each step independently.

## Decision

Keep controllers thin (HTTP concerns only: request validation, calling a service, shaping the response) and place all business logic in single-responsibility service classes under `app/Services/`. Each service is unit-tested independently of the HTTP layer, and controllers are covered by feature tests that exercise the full request/response cycle.

## Consequences

- Business logic is testable without booting the HTTP kernel, which enabled the strict TDD approach used for the GPX pipeline (see [0003](0003-gpx-pipeline-as-composable-services.md)).
- Adding or changing a pipeline step means touching one focused service class rather than a large controller method.
- Slightly more files and indirection than a "fat controller" approach, which is an acceptable trade-off given the complexity of the domain logic.
