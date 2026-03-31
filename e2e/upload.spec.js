import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { test, expect } from './helpers/auth.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const GPX_FILE  = path.resolve(__dirname, 'fixtures/test-upload.gpx');

test.describe('GPX upload', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/activities');
    });

    test('import button opens the upload modal', async ({ authenticatedPage: page }) => {
        await page.getByRole('button', { name: '+ Importer une sortie' }).click();
        await expect(page.getByText('Importer une sortie GPX')).toBeVisible();
    });

    test('upload form renders all required fields', async ({ authenticatedPage: page }) => {
        await page.getByRole('button', { name: '+ Importer une sortie' }).click();
        await expect(page.getByPlaceholder('Titre *')).toBeVisible();
        await expect(page.locator('select').filter({ hasText: 'Type *' })).toBeVisible();
        await expect(page.locator('select').filter({ hasText: 'Milieu *' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Importer', exact: true })).toBeDisabled();
    });

    test('cancel button closes the modal', async ({ authenticatedPage: page }) => {
        await page.getByRole('button', { name: '+ Importer une sortie' }).click();
        await expect(page.getByText('Importer une sortie GPX')).toBeVisible();

        await page.getByRole('button', { name: 'Annuler' }).click();
        await expect(page.getByText('Importer une sortie GPX')).not.toBeVisible();
    });

    test('import button is enabled only when all required fields are filled', async ({ authenticatedPage: page }) => {
        await page.getByRole('button', { name: '+ Importer une sortie' }).click();

        const submitBtn = page.getByRole('button', { name: 'Importer', exact: true });
        await expect(submitBtn).toBeDisabled();

        // Attach GPX file via setInputFiles (more reliable than filechooser on hidden inputs)
        await page.locator('input[type="file"]').setInputFiles(GPX_FILE);

        await page.getByPlaceholder('Titre *').fill('Test E2E Activity');
        await page.locator('select').filter({ hasText: 'Type *' }).selectOption('trail');
        await page.locator('select').filter({ hasText: 'Milieu *' }).selectOption('montagne');
        await page.locator('.max-w-md input[type="date"]').fill('2024-06-15');

        await expect(submitBtn).toBeEnabled();
    });

    test('uploading a valid GPX file shows success and closes the modal', async ({ authenticatedPage: page }) => {
        await page.getByRole('button', { name: '+ Importer une sortie' }).click();

        await page.locator('input[type="file"]').setInputFiles(GPX_FILE);

        await page.getByPlaceholder('Titre *').fill('Test E2E Upload');
        await page.locator('select').filter({ hasText: 'Type *' }).selectOption('trail');
        await page.locator('select').filter({ hasText: 'Milieu *' }).selectOption('montagne');
        await page.locator('.max-w-md input[type="date"]').fill('2024-06-15');

        await page.getByRole('button', { name: 'Importer', exact: true }).click();

        // Wait for the upload to complete (modal closes, toast appears)
        await expect(page.getByText('Importer une sortie GPX')).not.toBeVisible({ timeout: 20_000 });
        await expect(page.getByText('Sortie importée avec succès')).toBeVisible();
    });
});
