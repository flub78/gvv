/**
 * Playwright smoke tests — UC6 : Cotisation trésorier par carte (HelloAsso)
 *
 * Tests :
 *  1. Pilote ordinaire → formulaire cotisation inaccessible
 *  2. Trésorier → formulaire cotisation accessible
 *  3. Bouton HelloAsso absent pour un trésorier non dev_user
 *  4. Accès direct cotisation_qr avec txid invalide → redirection
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-uc6-cotisation.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const COTISATION_URL = '/index.php/compta/saisie_cotisation';
const QR_URL_FAKE    = '/index.php/paiements_en_ligne/cotisation_qr/txid-inexistant-000';

const PILOT     = { username: 'asterix',       password: 'password', section: '1' };
const TRESORIER = { username: 'testtresorier', password: 'password', section: '1' };

test.describe('UC6 — Cotisation trésorier par carte', () => {

    // ── 1. Pilote ordinaire : accès refusé au formulaire cotisation ───────────

    test('Pilot cannot access saisie_cotisation', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(COTISATION_URL);
        await page.waitForLoadState('domcontentloaded');

        // Doit être redirigé (login ou deny)
        const url = page.url();
        expect(url.includes('saisie_cotisation')).toBeFalsy();
    });

    // ── 2. Trésorier : formulaire accessible ─────────────────────────────────

    test('Tresorier can access saisie_cotisation form', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(COTISATION_URL);
        await page.waitForLoadState('domcontentloaded');

        // Ne doit pas rediriger vers login/deny
        expect(page.url()).not.toContain('/auth/login');
        expect(page.url()).not.toContain('/auth/deny');
    });

    // ── 3. Bouton HelloAsso absent (testtresorier non dans dev_users) ─────────

    test('HelloAsso button absent for non-dev_user tresorier', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(COTISATION_URL);
        await page.waitForLoadState('domcontentloaded');

        if (!page.url().includes('saisie_cotisation')) {
            test.skip(true, 'Formulaire cotisation non accessible');
            return;
        }

        // Le bouton HelloAsso ne doit pas être présent
        const helloassoBtn = page.locator('button[value="helloasso"]');
        await expect(helloassoBtn).not.toBeVisible();

        // Le bouton Valider standard doit être présent
        const validateBtn = page.locator('button[value="valider"], button#btnValidate');
        await expect(validateBtn).toBeVisible();
    });

    // ── 4. cotisation_qr avec txid invalide → redirection ────────────────────

    test('cotisation_qr with invalid txid redirects', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(QR_URL_FAKE);
        await page.waitForLoadState('domcontentloaded');

        // Doit rediriger (transaction introuvable)
        expect(page.url()).not.toContain('cotisation_qr');
    });

});
