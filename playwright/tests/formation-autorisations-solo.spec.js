/**
 * Smoke test for formation autorisations solo feature
 *
 * Prerequisites:
 *   - Feature flag gestion_formations must be enabled
 *   - abraracourcix user must exist with instructor rights (BIT_FI_AVION set in mniveaux)
 *     See bin/create_test_users.sh
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
// abraracourcix is an instructor in the new authorization system (BIT_FI_AVION + BIT_CA)
const INSTRUCTOR_USER = { username: 'abraracourcix', password: 'password' };

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function logout(page) {
    await page.goto('/auth/logout');
    await page.waitForLoadState('networkidle');
}

test.describe('Formation Autorisations Solo', () => {

    test('instructor can access autorisations solo list', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);

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
        await login(page, INSTRUCTOR_USER);

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
