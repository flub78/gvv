/**
 * Playwright smoke tests — UC3 : Renouvellement cotisation en ligne par le pilote
 *
 * Tests :
 *  1. Pilote sans session → redirigé vers login
 *  2. Pilote connecté → page cotisation accessible (ou message si HelloAsso non activé)
 *  3. [SKIP SI SANDBOX] Formulaire cotisation → redirection HelloAsso
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-uc3-cotisation-pilote.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const COTISATION_URL       = '/index.php/paiements_en_ligne/cotisation';
const BASE_URL             = 'http://gvv.net';

const PILOT     = { username: 'asterix',       password: 'password', section: '1' };

test.describe('UC3 — Cotisation en ligne par le pilote', () => {

    // ── 1. Sans session → login ───────────────────────────────────────────────

    test('Unauthenticated access redirects to login', async ({ page }) => {
        await page.goto(BASE_URL + COTISATION_URL);
        await page.waitForLoadState('domcontentloaded');

        const url = page.url();
        expect(url).toContain('/auth/login');
    });

    // ── 2. Pilote connecté → page accessible ─────────────────────────────────

    test('Authenticated pilot can access cotisation page', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(BASE_URL + COTISATION_URL);
        await page.waitForLoadState('domcontentloaded');

        // Ne doit pas rediriger vers login
        expect(page.url()).not.toContain('/auth/login');

        // Soit le formulaire, soit un message d'erreur (HelloAsso non activé)
        const body = await page.content();
        const hasForm    = body.includes('produit_id') || body.includes('form-check-input');
        const hasMessage = body.includes('Aucune cotisation') || body.includes('non activé') ||
                           body.includes('paiements en ligne') || body.includes('mon_compte');
        expect(hasForm || hasMessage || page.url().includes('mon_compte')).toBeTruthy();
    });

    // ── 3. [SKIP SI SANDBOX] Flow complet sandbox ─────────────────────────────

    test('[SKIP SI SANDBOX] Cotisation form submits to HelloAsso', async ({ page }) => {
        const sandboxCheck = await page.request.get(BASE_URL + '/index.php/paiements_en_ligne/sandbox_available');
        test.skip(sandboxCheck.status() !== 200, 'HelloAsso sandbox non configuré — test ignoré');

        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(BASE_URL + COTISATION_URL);
        await page.waitForLoadState('domcontentloaded');

        // Cliquer sur le premier produit disponible
        const radios = page.locator('input[name="produit_id"]');
        if (await radios.count() === 0) {
            test.skip(true, 'Aucun produit de cotisation configuré — test ignoré');
            return;
        }
        await radios.first().click();

        await Promise.all([
            page.waitForNavigation({ timeout: 15000 }),
            page.click('button[name="button"][value="payer"]'),
        ]);

        expect(page.url()).toContain('helloasso');
    });

});
