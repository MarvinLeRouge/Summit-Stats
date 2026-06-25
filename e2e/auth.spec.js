import { test, expect } from '@playwright/test';
import { getTestToken, getTestPassword } from './helpers/auth.js';

test.describe('authentication', () => {
    test('login page renders the password input and submit button', async ({ page }) => {
        await page.goto('/login');
        await expect(page.getByRole('heading', { name: 'Summit Stats' })).toBeVisible();
        await expect(page.getByPlaceholder('Mot de passe')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Se connecter' })).toBeVisible();
    });

    test('submit button is disabled when the input is empty', async ({ page }) => {
        await page.goto('/login');
        await expect(page.getByRole('button', { name: 'Se connecter' })).toBeDisabled();
    });

    test('entering an invalid password shows an error message', async ({ page }) => {
        await page.goto('/login');
        await page.getByPlaceholder('Mot de passe').fill('wrong-password-xyz');
        await page.getByRole('button', { name: 'Se connecter' }).click();
        await expect(page.getByText('Mot de passe incorrect')).toBeVisible();
    });

    test('entering a valid password redirects to the dashboard', async ({ page }) => {
        const password = getTestPassword();
        await page.goto('/login');
        await page.getByPlaceholder('Mot de passe').fill(password);
        await page.getByRole('button', { name: 'Se connecter' }).click();
        await expect(page).toHaveURL('/');
        await expect(page.getByText('Progression')).toBeVisible();
    });

    test('pressing Enter in the input submits the form', async ({ page }) => {
        const password = getTestPassword();
        await page.goto('/login');
        await page.getByPlaceholder('Mot de passe').fill(password);
        await page.getByPlaceholder('Mot de passe').press('Enter');
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
