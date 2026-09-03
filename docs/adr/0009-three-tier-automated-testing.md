# 0009 - Three-tier automated testing: Pest, Vitest, Playwright

- Status: Accepted
- Date: 2026-03-31

## Context

Summit Stats has a Laravel API backend and a Vue.js SPA frontend, plus interactions between the two that only show up when the full stack runs together (e.g. login flow, GPX upload with SSE progress, elevation profile/map synchronization). Testing only the backend, or only the frontend in isolation, would miss integration issues between them.

## Decision

Use three complementary automated test layers, each targeting a different level:

1. **Pest** (backend) - unit tests for individual GPX pipeline services, feature tests for all API endpoints, run against in-memory SQLite
2. **Vitest** (frontend) - unit tests for Vue components and stores in isolation
3. **Playwright** (E2E) - full-stack scenarios exercising the real browser against a running app, covering critical user flows end to end

Each layer runs in its own CI job/workflow, with coverage enforced for the two unit-level layers (`--coverage --min=80` for Pest) and E2E gating production deploys (see [0008](0008-docker-compose-traefik-production.md)).

## Consequences

- Bugs are caught at the cheapest layer that can catch them: unit tests for isolated logic, E2E for cross-cutting integration issues.
- Three test runners means three sets of tooling/conventions to maintain, but each is standard for its ecosystem (Pest for Laravel, Vitest for Vite-based frontends, Playwright for browser E2E).
- E2E tests are the slowest and most environment-sensitive layer; they gate deployment specifically because a passing E2E run is the strongest signal that the deployed stack actually works end to end.
