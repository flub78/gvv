/**
 * Smoke test for the Maintenance module — Synthese de navigabilite (Phase 5, Etape 5.6)
 *
 * Prerequisites:
 *   - obelix user exists with mecano role in section Planeur (id=1)
 *     See bin/create_test_users.sh
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const MECANO_USER = { username: 'obelix', password: 'password' };
const PLANEUR_SECTION = '1';

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function switchToPlaneurSection(page) {
    await page.request.post('/index.php/user_roles_per_section/set_section', {
        form: { section: PLANEUR_SECTION, current_url: '/index.php/welcome' }
    });
}

test.describe('Maintenance - Synthese navigabilite (mecano)', () => {

    test('fleet view lists aircraft with a status, aircraft view shows entity detail, PDF export works', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        // Vue flotte
        await page.goto('/index.php/maintenance_synthese');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('h3')).toContainText('navigabilité');
        const rows = page.locator('table tbody tr');
        await expect(rows.first()).toBeVisible();

        // Filtrage par section (Planeur = 1)
        await page.selectOption('#section_select', '1');
        await page.click('#btn-filtrer-section');
        await page.waitForLoadState('networkidle');
        await expect(page.url()).toContain('/maintenance_synthese/index/1');

        // Detail d'un aeronef
        const firstAeronefLink = page.locator('table tbody tr td a').first();
        await firstAeronefLink.click();
        await page.waitForLoadState('networkidle');
        await expect(page.url()).toMatch(/maintenance_synthese\/aeronef\//);
        await expect(page.locator('.card-header').first()).toContainText(/./); // au moins l'aeronef lui-meme

        // Export PDF (ouvre un nouvel onglet, target="_blank") : verifie juste
        // que la reponse est bien un PDF, pas une erreur PHP (page blanche/fatal).
        const pdfUrl = await page.locator('a:has-text("Export PDF")').getAttribute('href');
        const pdfResponse = await page.request.get(pdfUrl);
        expect(pdfResponse.status()).toBe(200);
        expect(pdfResponse.headers()['content-type']).toContain('application/pdf');
    });
});
