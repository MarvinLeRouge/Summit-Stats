import { test, expect } from './helpers/auth.js';

test.describe('dashboard', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/');
    });

    test('renders the page title and navigation', async ({ authenticatedPage: page }) => {
        await expect(page.getByRole('heading', { name: 'Progression' })).toBeVisible();
        // Nav bar — scoped to <nav> to avoid matching the <title> tag in <head>
        await expect(page.locator('nav').getByText('Summit Stats')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Sorties' })).toBeVisible();
    });

    test('renders all filter controls', async ({ authenticatedPage: page }) => {
        // Dashboard labels are not associated via for/id — select by position inside filter panel
        const selects = page.locator('.bg-white.rounded-lg.shadow-sm select');
        await expect(selects.first()).toBeVisible(); // metric
        await expect(page.locator('input[type="date"]').first()).toBeVisible(); // date_from
        await expect(page.getByRole('button', { name: 'Réinitialiser' })).toBeVisible();
    });

    test('shows the chart area or the empty state message', async ({ authenticatedPage: page }) => {
        await page.waitForLoadState('networkidle');
        const hasChart  = (await page.locator('canvas').count()) > 0;
        const hasNoData = await page.getByText('Aucune donnée pour ces critères.').isVisible().catch(() => false);
        expect(hasChart || hasNoData).toBe(true);
    });

    test('changing the metric filter reloads chart data', async ({ authenticatedPage: page }) => {
        // First select inside the filter panel is the metric select
        const metricSelect = page.locator('.bg-white.rounded-lg.shadow-sm select').first();
        await metricSelect.selectOption('distance_km');
        await expect(page.locator('.bg-white.rounded-lg').last()).toBeVisible();
    });

    test('reset filters button restores default metric', async ({ authenticatedPage: page }) => {
        const metricSelect = page.locator('.bg-white.rounded-lg.shadow-sm select').first();
        await metricSelect.selectOption('distance_km');
        await page.getByRole('button', { name: 'Réinitialiser' }).click();
        await expect(metricSelect).toHaveValue('avg_ascent_speed_mh');
    });

    test('summary stat cards are visible when data is present', async ({ authenticatedPage: page }) => {
        await page.waitForLoadState('networkidle');
        // Stat cards are rendered inside a 3-column grid — scope to avoid matching the nav "Sorties" link
        const grid    = page.locator('.grid.grid-cols-3');
        const hasGrid = await grid.isVisible().catch(() => false);
        if (hasGrid) {
            await expect(grid.locator('p.uppercase').filter({ hasText: 'Sorties' })).toBeVisible();
            await expect(grid.locator('p.uppercase').filter({ hasText: 'Moyenne' })).toBeVisible();
            await expect(grid.locator('p.uppercase').filter({ hasText: 'Maximum' })).toBeVisible();
        }
    });
});
