import { test, expect } from '@playwright/test';
import { getTestToken } from './helpers/auth.js';

test.describe('authentication', () => {
    test('login page renders the token input and submit button', async ({ page }) => {
        await page.goto('/login');
        await expect(page.getByRole('heading', { name: 'Summit Stats' })).toBeVisible();
        await expect(page.getByPlaceholder('Votre token Sanctum')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Se connecter' })).toBeVisible();
    });

    test('submit button is disabled when the input is empty', async ({ page }) => {
        await page.goto('/login');
        await expect(page.getByRole('button', { name: 'Se connecter' })).toBeDisabled();
    });

    test('entering an invalid token shows an error message', async ({ page }) => {
        await page.goto('/login');
        await page.getByPlaceholder('Votre token Sanctum').fill('invalid-token-xyz');
        await page.getByRole('button', { name: 'Se connecter' }).click();
        await expect(page.getByText('Token invalide')).toBeVisible();
    });

    test('entering a valid token redirects to the dashboard', async ({ page }) => {
        const token = getTestToken();
        await page.goto('/login');
        await page.getByPlaceholder('Votre token Sanctum').fill(token);
        await page.getByRole('button', { name: 'Se connecter' }).click();
        await expect(page).toHaveURL('/');
        await expect(page.getByText('Progression')).toBeVisible();
    });

    test('pressing Enter in the input submits the form', async ({ page }) => {
        const token = getTestToken();
        await page.goto('/login');
        await page.getByPlaceholder('Votre token Sanctum').fill(token);
        await page.getByPlaceholder('Votre token Sanctum').press('Enter');
        await expect(page).toHaveURL('/');
    });

    test('logout clears the token and redirects to /login', async ({ page }) => {
        // Inject token and navigate to the dashboard
        await page.addInitScript((t) => { localStorage.setItem('sanctum_token', t); }, getTestToken());
        await page.goto('/');
        await expect(page.getByRole('button', { name: 'Déconnexion' })).toBeVisible();

        await page.getByRole('button', { name: 'Déconnexion' }).click();
        await expect(page).toHaveURL('/login');
        const stored = await page.evaluate(() => localStorage.getItem('sanctum_token'));
        expect(stored).toBeNull();
    });

    test('accessing a protected route without a token redirects to /login', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveURL('/login');
    });
});
