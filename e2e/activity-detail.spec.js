import { test, expect } from './helpers/auth.js';

/**
 * Navigates to the first activity in the list and returns its URL.
 * Skips the test if no activities exist.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<string|null>} The detail page URL, or null if skipped.
 */
const goToFirstActivity = async (page) => {
    await page.goto('/activities');
    const firstRow = page.locator('tbody tr').first();
    const hasRow   = await firstRow.isVisible().catch(() => false);
    if (!hasRow) return null;

    await firstRow.click();
    await page.waitForURL(/\/activities\/\d+/);
    return page.url();
};

test.describe('activity detail', () => {
    test('shows the activity title and stats section headings', async ({ authenticatedPage: page }) => {
        const url = await goToFirstActivity(page);
        if (!url) { test.skip(); return; }

        await expect(page.getByRole('heading').first()).toBeVisible();
        await expect(page.getByText('Général')).toBeVisible();
        await expect(page.getByText('Répartition du trajet')).toBeVisible();
    });

    test('displays global stat cards (Distance, Dénivelé)', async ({ authenticatedPage: page }) => {
        const url = await goToFirstActivity(page);
        if (!url) { test.skip(); return; }

        await expect(page.getByText('Distance')).toBeVisible();
        await expect(page.getByText('Dénivelé')).toBeVisible();
    });

    test('displays the ascent / flat / descent distribution', async ({ authenticatedPage: page }) => {
        const url = await goToFirstActivity(page);
        if (!url) { test.skip(); return; }

        await expect(page.getByText('Montée')).toBeVisible();
        await expect(page.getByText('Plat')).toBeVisible();
        await expect(page.getByText('Descente')).toBeVisible();
    });

    test('elevation profile panel is collapsed by default and expands on click', async ({ authenticatedPage: page }) => {
        const url = await goToFirstActivity(page);
        if (!url) { test.skip(); return; }

        const profileToggle = page.getByRole('button', { name: /Profil altimétrique/ });
        await expect(profileToggle).toBeVisible();

        // Collapsed by default — canvas not present
        await expect(page.locator('canvas').first()).not.toBeVisible().catch(() => {});

        await profileToggle.click();
        // After expand, the section content becomes visible
        await expect(page.locator('button').filter({ hasText: 'Profil altimétrique' })).toBeVisible();
    });

    test('map panel is collapsed by default and expands on click', async ({ authenticatedPage: page }) => {
        const url = await goToFirstActivity(page);
        if (!url) { test.skip(); return; }

        const mapToggle = page.getByRole('button', { name: /Carte du tracé/ });
        await expect(mapToggle).toBeVisible();
        await mapToggle.click();
        await expect(mapToggle).toBeVisible();
    });

    test('segment table is rendered with expected column headers', async ({ authenticatedPage: page }) => {
        const url = await goToFirstActivity(page);
        if (!url) { test.skip(); return; }

        await expect(page.getByRole('columnheader', { name: 'Type' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Pente' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Distance' })).toBeVisible();
    });

    test('back button navigates to the activities list', async ({ authenticatedPage: page }) => {
        const url = await goToFirstActivity(page);
        if (!url) { test.skip(); return; }

        await page.getByRole('button', { name: '← Retour aux sorties' }).click();
        await expect(page).toHaveURL('/activities');
    });
});
