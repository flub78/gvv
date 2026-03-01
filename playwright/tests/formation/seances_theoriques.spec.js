/**
 * Playwright Tests – Séances de formation théoriques + Rapports annuels (Phase 2 & 3)
 *
 * Smoke tests for the theoretical training sessions and annual reports features.
 * Verifies:
 *   1. Access to formation_types_seances list
 *   2. Access to formation_seances_theoriques list
 *   3. Creation form loads with participant search widget
 *   4. Nature filter appears on formation_seances list
 *   5. Annual consolidated report is accessible
 *   6. Compliance report is accessible
 *
 * Prerequisites:
 *   - testadmin user must exist
 *   - Migration 078 and 079 must be applied
 *   - Feature flag gestion_formations must be enabled
 *
 * @see doc/plans/seances_theoriques_plan.md Phase 2 & 3
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const TEST_USER = { username: 'testadmin', password: 'password' };

async function login(page) {
    await page.goto(LOGIN_URL);
    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Formation – Types de séances', () => {

    test('La liste des types de séances est accessible', async ({ page }) => {
        await login(page);
        await page.goto('/index.php/formation_types_seances');
        await expect(page).not.toHaveURL(/login/);
        // The page title contains the expected heading or content
        const body = await page.textContent('body');
        expect(body).toContain('séance');
    });

});

test.describe('Formation – Séances théoriques', () => {

    test('La liste des séances théoriques est accessible', async ({ page }) => {
        await login(page);
        await page.goto('/index.php/formation_seances_theoriques');
        await expect(page).not.toHaveURL(/login/);
        const body = await page.textContent('body');
        expect(body).toMatch(/[Ss]éance/);
    });

    test('Le formulaire de création charge le widget de participants', async ({ page }) => {
        await login(page);
        await page.goto('/index.php/formation_seances_theoriques/create');
        await expect(page).not.toHaveURL(/login/);

        // The participant search input must be present
        const searchInput = page.locator('#participant-search');
        await expect(searchInput).toBeVisible();

        // The participant badges container must be present
        const badges = page.locator('#participants-badges');
        await expect(badges).toBeVisible();
    });

    test('La liste des séances (historique) contient le filtre Nature', async ({ page }) => {
        await login(page);
        await page.goto('/index.php/formation_seances');
        await expect(page).not.toHaveURL(/login/);

        const natureFilter = page.locator('select[name="nature"]');
        await expect(natureFilter).toBeVisible();

        // Options: all, vol, theorique
        const options = await natureFilter.locator('option').allTextContents();
        expect(options.some(o => o.toLowerCase().includes('vol'))).toBeTruthy();
        expect(options.some(o => o.toLowerCase().includes('cours') || o.toLowerCase().includes('sol') || o.toLowerCase().includes('théorique'))).toBeTruthy();
    });

    test('La recherche AJAX de membres retourne du JSON', async ({ page }) => {
        await login(page);

        const response = await page.request.get(
            '/index.php/formation_seances_theoriques/ajax_search_membres?q=a'
        );
        expect(response.status()).toBe(200);

        const contentType = response.headers()['content-type'] || '';
        expect(contentType).toContain('application/json');

        const body = await response.json();
        expect(Array.isArray(body)).toBe(true);
    });

});

test.describe('Formation – Rapports annuels (Phase 3)', () => {

    test('Le rapport annuel consolidé est accessible', async ({ page }) => {
        await login(page);
        await page.goto('/index.php/formation_rapports/annuel');
        await expect(page).not.toHaveURL(/login/);

        const body = await page.textContent('body');
        // Should contain the tab headers
        expect(body).toMatch(/instructeur|programme/i);
    });

    test('Le rapport annuel affiche les deux onglets', async ({ page }) => {
        await login(page);
        await page.goto('/index.php/formation_rapports/annuel');
        await expect(page).not.toHaveURL(/login/);

        // Both tabs should be present
        await expect(page.locator('#tab-instructeurs-tab')).toBeVisible();
        await expect(page.locator('#tab-programmes-tab')).toBeVisible();
    });

    test("L'export CSV du rapport annuel est accessible", async ({ page }) => {
        await login(page);

        const year = new Date().getFullYear();
        const response = await page.request.get(
            `/index.php/formation_rapports/export_annuel_csv/${year}`
        );
        expect(response.status()).toBe(200);

        const contentType = response.headers()['content-type'] || '';
        expect(contentType.toLowerCase()).toMatch(/csv|text/);
    });

    test('Le rapport de conformité est accessible', async ({ page }) => {
        await login(page);
        await page.goto('/index.php/formation_rapports/conformite');
        await expect(page).not.toHaveURL(/login/);

        const body = await page.textContent('body');
        expect(body).toMatch(/conformit|p.riodicit/i);
    });

});
