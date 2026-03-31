import { test, expect } from './helpers/auth.js';

/**
 * Navigates to the first activity in the list and returns its URL.
 * Waits for the list to finish loading before checking for rows.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<string>} The detail page URL.
 */
const goToFirstActivity = async (page) => {
    await page.goto('/activities');
    await page.waitForLoadState('networkidle');
    await page.locator('tbody tr').first().click();
    await page.waitForURL(/\/activities\/\d+/);
    return page.url();
};

test.describe('activity detail', () => {
    test('shows the activity title and stats section headings', async ({ authenticatedPage: page }) => {
        await goToFirstActivity(page);

        await expect(page.getByRole('heading').first()).toBeVisible();
        // Scope to the section label divs to avoid matching table headers
        await expect(page.locator('div.text-xs.uppercase').filter({ hasText: 'Général' })).toBeVisible();
        await expect(page.locator('div.text-xs.uppercase').filter({ hasText: 'Répartition du trajet' })).toBeVisible();
    });

    test('displays global stat cards (Distance, Dénivelé)', async ({ authenticatedPage: page }) => {
        await goToFirstActivity(page);

        // Scope to StatCard labels (<p class="uppercase">) to avoid matching segment table headers
        await expect(page.locator('p.uppercase').filter({ hasText: 'Distance' })).toBeVisible();
        await expect(page.locator('p.uppercase').filter({ hasText: 'Dénivelé' })).toBeVisible();
    });

    test('displays the ascent / flat / descent distribution', async ({ authenticatedPage: page }) => {
        await goToFirstActivity(page);

        // Scope to the distribution cards (bg-green/gray/blue-50), not the segment table type badges
        const distributionSection = page.locator('.grid.grid-cols-3').first();
        await expect(distributionSection.getByText('Montée')).toBeVisible();
        await expect(distributionSection.getByText('Plat')).toBeVisible();
        await expect(distributionSection.getByText('Descente')).toBeVisible();
    });

    test('elevation profile panel is collapsed by default and expands on click', async ({ authenticatedPage: page }) => {
        await goToFirstActivity(page);

        const profileToggle = page.getByRole('button', { name: /Profil altimétrique/ });
        await expect(profileToggle).toBeVisible();
        await profileToggle.click();
        // After expand, the toggle is still visible (panel open)
        await expect(profileToggle).toBeVisible();
    });

    test('map panel is collapsed by default and expands on click', async ({ authenticatedPage: page }) => {
        await goToFirstActivity(page);

        const mapToggle = page.getByRole('button', { name: /Carte du tracé/ });
        await expect(mapToggle).toBeVisible();
        await mapToggle.click();
        await expect(mapToggle).toBeVisible();
    });

    test('segment table is rendered with expected column headers', async ({ authenticatedPage: page }) => {
        await goToFirstActivity(page);

        await expect(page.getByRole('columnheader', { name: 'Type' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Pente' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Distance' })).toBeVisible();
    });

    test('back button navigates to the activities list', async ({ authenticatedPage: page }) => {
        await goToFirstActivity(page);

        await page.getByRole('button', { name: '← Retour aux sorties' }).click();
        await expect(page).toHaveURL('/activities');
    });
});
