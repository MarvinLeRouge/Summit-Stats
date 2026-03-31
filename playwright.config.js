import { defineConfig, devices } from '@playwright/test';
import { config } from 'dotenv';

// Load E2E-specific env vars (TEST_TOKEN, E2E_BASE_URL) without overriding process.env
config({ path: '.env.e2e', override: false });

export default defineConfig({
    testDir: './e2e',

    /** Maximum time one test can run (includes navigation + waits). */
    timeout: 30_000,

    /** Retry once on CI to absorb transient flakiness. */
    retries: process.env.CI ? 1 : 0,

    /** Parallelism: sequential on CI to keep Docker load manageable. */
    workers: process.env.CI ? 1 : undefined,

    reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],

    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8081',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
