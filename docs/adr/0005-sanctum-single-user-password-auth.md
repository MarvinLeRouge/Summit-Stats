# 0005 - Sanctum token auth with single-user password login

- Status: Accepted
- Date: 2026-06-25

## Context

Summit Stats is a single-user personal tool with no registration flow. Early on, Sanctum was installed with a seeded user and a Bearer token issued at setup time, entered manually into the frontend. This was workable for solo local use but not a real login experience, and left the token as a long-lived shared secret typed in by hand.

## Decision

Use Laravel Sanctum for API authentication (Bearer tokens, no sessions, consistent with the SPA/API split in [0001](0001-vue-spa-with-laravel-api.md)), but replace the manual token entry with a proper `POST /api/login` endpoint that authenticates the single seeded user by password and returns a fresh Sanctum token. There is intentionally no registration endpoint - the one user is created via `UserSeeder` from environment variables.

## Consequences

- The frontend gets a real login form/flow instead of asking the user to paste a token.
- The attack surface stays intentionally minimal: one user, no public registration, no password reset flow (single operator, credentials managed via `.env`).
- Password input required bounding (see V4 Phase 1 security hardening) since Laravel does not cap string length by default.
- If multi-user support is ever needed (e.g. read-only sharing, see the V4 roadmap), this decision will need to be revisited.
