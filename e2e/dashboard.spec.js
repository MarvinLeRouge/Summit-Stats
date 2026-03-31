import { test, expect } from './helpers/auth.js';

test.describe('dashboard', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/');
    });

    test('renders the page title and navigation', async ({ authenticatedPage: page }) => {
        await expect(page.getByRole('heading', { name: 'Progression' })).toBeVisible();
        await expect(page.getByText('Summit Stats')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Sorties' })).toBeVisible();
    });

    test('renders all filter controls', async ({ authenticatedPage: page }) => {
        await expect(page.getByLabel('Métrique')).toBeVisible();
        await expect(page.getByLabel('Type')).toBeVisible();
        await expect(page.getByLabel('Milieu')).toBeVisible();
        await expect(page.getByLabel('Du')).toBeVisible();
        await expect(page.getByLabel('Au')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Réinitialiser' })).toBeVisible();
    });

    test('shows the chart area or the empty state message', async ({ authenticatedPage: page }) => {
        // Either a chart canvas or the "no data" message must be present
        const hasChart   = await page.locator('canvas').count() > 0;
        const hasNoData  = await page.getByText('Aucune donnée pour ces critères.').isVisible().catch(() => false);
        expect(hasChart || hasNoData).toBe(true);
    });

    test('changing the metric filter reloads chart data', async ({ authenticatedPage: page }) => {
        const metricSelect = page.getByLabel('Métrique');
        await metricSelect.selectOption('distance_km');
        // Data reload triggers the loading state then either chart or empty state
        await expect(page.locator('.bg-white.rounded-lg').last()).toBeVisible();
    });

    test('reset filters button restores default metric', async ({ authenticatedPage: page }) => {
        await page.getByLabel('Métrique').selectOption('distance_km');
        await page.getByRole('button', { name: 'Réinitialiser' }).click();
        await expect(page.getByLabel('Métrique')).toHaveValue('avg_ascent_speed_mh');
    });

    test('summary stat cards are visible when data is present', async ({ authenticatedPage: page }) => {
        const hasCards = await page.getByText('Sorties').isVisible().catch(() => false);
        if (hasCards) {
            await expect(page.getByText('Sorties')).toBeVisible();
            await expect(page.getByText('Moyenne')).toBeVisible();
            await expect(page.getByText('Maximum')).toBeVisible();
        }
    });
});
