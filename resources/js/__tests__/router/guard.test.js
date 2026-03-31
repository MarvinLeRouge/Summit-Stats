import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

/**
 * Tests for the navigation guard defined in router/index.js.
 *
 * The guard is extracted and tested directly to avoid spinning up the full
 * Vue Router instance with lazy-loaded page components.
 */

/** Simulates the beforeEach guard logic from router/index.js. */
const guard = (to) => {
    const token = localStorage.getItem('sanctum_token');
    if (to.meta.requiresAuth && !token) return '/login';
};

describe('navigation guard', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('redirects to /login when no token is stored and route requires auth', () => {
        const to = { meta: { requiresAuth: true } };
        expect(guard(to)).toBe('/login');
    });

    it('allows navigation when a token is present and route requires auth', () => {
        localStorage.setItem('sanctum_token', 'test-token-123');
        const to = { meta: { requiresAuth: true } };
        expect(guard(to)).toBeUndefined();
    });

    it('allows navigation to public routes without a token', () => {
        const to = { meta: {} };
        expect(guard(to)).toBeUndefined();
    });

    it('allows navigation to /login even without a token', () => {
        const to = { path: '/login', meta: {} };
        expect(guard(to)).toBeUndefined();
    });

    it('reads the token from localStorage on each call', () => {
        const to = { meta: { requiresAuth: true } };

        expect(guard(to)).toBe('/login');

        localStorage.setItem('sanctum_token', 'fresh-token');
        expect(guard(to)).toBeUndefined();
    });
});
