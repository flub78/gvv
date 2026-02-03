/**
 * Smoke test for formation autorisations solo feature
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';

async function login(page, username, password) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function logout(page) {
    await page.goto('/auth/logout');
    await page.waitForLoadState('networkidle');
}

test.describe('Formation Autorisations Solo', () => {

    test('instructor can access autorisations solo list', async ({ page }) => {
        // Login as CA (has instructor rights) - password is 'password'
        await login(page, 'testca', 'password');

        // Navigate to autorisations solo
        await page.goto('/formation_autorisations_solo');
        await page.waitForLoadState('networkidle');

        // Check page loaded correctly
        await expect(page.locator('h3')).toContainText('Autorisations de vol solo');

        // Check create button is visible
        await expect(page.locator('a:has-text("Nouvelle autorisation")')).toBeVisible();

        await logout(page);
    });

    test('instructor can access create form', async ({ page }) => {
        // Login as CA (has instructor rights) - password is 'password'
        await login(page, 'testca', 'password');

        // Navigate to create form
        await page.goto('/formation_autorisations_solo/create');
        await page.waitForLoadState('networkidle');

        // Check form elements are present
        await expect(page.locator('label:has-text("Formation")')).toBeVisible();
        await expect(page.locator('label:has-text("Date")')).toBeVisible();
        await expect(page.locator('label:has-text("Consignes")')).toBeVisible();

        // Check save button is present
        await expect(page.locator('button:has-text("Enregistrer")')).toBeVisible();

        await logout(page);
    });

});
