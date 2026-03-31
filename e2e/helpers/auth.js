import { test as base } from '@playwright/test';

/**
 * Returns the Sanctum test token from the environment.
 * Throws a clear error if the variable is missing.
 *
 * @returns {string}
 */
export const getTestToken = () => {
    const token = process.env.TEST_TOKEN;
    if (!token) throw new Error('TEST_TOKEN environment variable is required to run E2E tests.');
    return token;
};

/**
 * Playwright fixture that opens any page with the Sanctum token already stored in
 * localStorage, bypassing the login UI. Mirrors bootstrap.js behaviour: the token is
 * injected via an init script so it is available before any page JS runs.
 *
 * Usage:
 * ```js
 * import { test } from '../helpers/auth.js';
 * test('my test', async ({ authenticatedPage }) => { ... });
 * ```
 */
export const test = base.extend({
    authenticatedPage: async ({ page }, use) => {
        const token = getTestToken();

        await page.addInitScript((t) => {
            localStorage.setItem('sanctum_token', t);
        }, token);

        await use(page);
    },
});

export { expect } from '@playwright/test';
