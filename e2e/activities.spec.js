import { test, expect } from './helpers/auth.js';

test.describe('activities list', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/activities');
    });

    test('renders the page title and import button', async ({ authenticatedPage: page }) => {
        await expect(page.getByRole('heading', { name: 'Mes sorties' })).toBeVisible();
        await expect(page.getByRole('button', { name: '+ Importer une sortie' })).toBeVisible();
    });

    test('renders the filter controls', async ({ authenticatedPage: page }) => {
        await expect(page.getByRole('button', { name: 'Réinitialiser' })).toBeVisible();
        // Type and environment selects
        await expect(page.locator('select').first()).toBeVisible();
    });

    test('shows the activity table or the empty state message', async ({ authenticatedPage: page }) => {
        const hasTable   = await page.locator('table').isVisible().catch(() => false);
        const hasEmpty   = await page.getByText('Aucune sortie trouvée').isVisible().catch(() => false);
        expect(hasTable || hasEmpty).toBe(true);
    });

    test('clicking on an activity row navigates to the detail page', async ({ authenticatedPage: page }) => {
        const firstRow = page.locator('tbody tr').first();
        const hasRow = await firstRow.isVisible().catch(() => false);
        if (!hasRow) {
            test.skip();
            return;
        }
        await firstRow.click();
        await expect(page).toHaveURL(/\/activities\/\d+/);
    });

    test('filtering by type updates the URL query string', async ({ authenticatedPage: page }) => {
        const typeSelect = page.locator('select').first();
        await typeSelect.selectOption('trail');
        await expect(page).toHaveURL(/type=trail/);
    });

    test('reset filters clears the URL query string', async ({ authenticatedPage: page }) => {
        const typeSelect = page.locator('select').first();
        await typeSelect.selectOption('trail');
        await expect(page).toHaveURL(/type=trail/);

        await page.getByRole('button', { name: 'Réinitialiser' }).click();
        await expect(page).toHaveURL('/activities');
    });

    test('upload modal opens when clicking the import button', async ({ authenticatedPage: page }) => {
        await page.getByRole('button', { name: '+ Importer une sortie' }).click();
        await expect(page.getByText('Importer une sortie')).toBeVisible();
    });
});
