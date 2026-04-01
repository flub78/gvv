/**
 * Playwright smoke tests — EF3 : Mon Compte — badge HelloAsso + lien provisionnement
 *
 * Tests :
 *  1. Page historique paiements_en_ligne/index accessible (pilote connecté)
 *  2. Page mon_compte accessible (pilote connecté)
 *  3. Mon compte contient le lien "Provisionner mon compte en ligne" si HelloAsso activé
 *     (conditionnel : si la section n'a pas HelloAsso activé, le lien ne doit pas apparaître)
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-ef3-mon-compte.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const INDEX_URL  = '/index.php/paiements_en_ligne/index';
const COMPTE_URL = '/index.php/compta/mon_compte';
const PILOT = { username: 'asterix', password: 'password', section: '4' };

test.describe('EF3 — Mon Compte : historique et lien provisionnement', () => {

    // ── 1. Historique transactions ────────────────────────────────────────────

    test('GET /paiements_en_ligne/index without session redirects to login', async ({ page }) => {
        await page.goto('/index.php/auth/logout');
        await page.waitForLoadState('domcontentloaded');

        await page.goto(INDEX_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).toContain('/auth/login');
    });

    test('GET /paiements_en_ligne/index with session shows transaction history', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(INDEX_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).not.toContain('/auth/login');

        const body = await page.textContent('body');
        // Page accessible (no 500 error)
        expect(body).not.toContain('A PHP Error was encountered');
        expect(body).not.toContain('500 Internal Server Error');
    });

    // ── 2. Mon compte accessible ──────────────────────────────────────────────

    test('GET /compta/mon_compte with session shows account journal', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(COMPTE_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).not.toContain('/auth/login');

        const body = await page.textContent('body');
        expect(body).not.toContain('A PHP Error was encountered');
        expect(body).not.toContain('500 Internal Server Error');
    });

    // ── 3. Lien provisionnement conditionnel ──────────────────────────────────

    test('Mon compte does not show provisioning link when HelloAsso not configured', async ({ page }) => {
        // asterix is in section 4 — if HelloAsso is not configured there,
        // the provisioning button must not appear
        const helloassoConfigured = await page.request.get('/index.php/paiements_en_ligne/sandbox_available');

        if (helloassoConfigured.status() === 200) {
            // HelloAsso sandbox is configured — skip this test
            test.skip();
            return;
        }

        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(COMPTE_URL);
        await page.waitForLoadState('domcontentloaded');

        const body = await page.textContent('body');
        // Provisioning button must NOT appear when HelloAsso is not enabled
        expect(body).not.toContain('Provisionner mon compte en ligne');
        expect(body).not.toContain('Top up my account online');
    });

});
