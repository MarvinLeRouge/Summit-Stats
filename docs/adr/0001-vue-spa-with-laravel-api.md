# 0001 - Separate Vue 3 SPA served by a Laravel API

- Status: Accepted
- Date: 2026-03-12

## Context

Summit Stats needed a rich, interactive dashboard (filterable progression charts, synchronized elevation profile and map) alongside a backend that parses and analyzes GPX files. A traditional server-rendered Laravel app (Blade views) would make this level of client-side interactivity harder to build and maintain.

## Decision

Build the frontend as a standalone Vue.js 3 SPA (Composition API, Vue Router, Pinia) served as static assets, consuming a Laravel backend exposed purely as a REST API under `/api`. Authentication uses a Sanctum Bearer token rather than session cookies, since the frontend is a decoupled SPA rather than a Blade-rendered page.

## Consequences

- Clear separation of concerns: Laravel owns data and business logic, Vue owns presentation and interaction.
- The API can be consumed by other clients in the future (e.g. a mobile app) without changes to the backend.
- Requires explicit CORS and Bearer token handling instead of relying on Laravel's default session-based auth.
- Two separate toolchains and test suites to maintain (Pest for the API, Vitest for the frontend).
