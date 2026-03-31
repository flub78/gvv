/**
 * Playwright smoke tests — UC7 : Règlement compte pilote par carte (HelloAsso)
 *
 * Tests :
 *  1. Pilote ordinaire → formulaire règlement inaccessible
 *  2. Trésorier → formulaire règlement accessible
 *  3. Bouton HelloAsso absent pour un trésorier non dev_user
 *  4. Accès direct credit_qr avec txid invalide → redirection
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-uc7-credit.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const CREDIT_URL   = '/index.php/compta/reglement_pilote';
const QR_URL_FAKE  = '/index.php/paiements_en_ligne/credit_qr/txid-inexistant-000';

const PILOT     = { username: 'asterix',       password: 'password', section: '1' };
const TRESORIER = { username: 'testtresorier', password: 'password', section: '1' };

test.describe('UC7 — Règlement compte pilote par carte', () => {

    // ── 1. Pilote ordinaire : accès refusé ───────────────────────────────────

    test('Pilot cannot access reglement_pilote', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(CREDIT_URL);
        await page.waitForLoadState('domcontentloaded');

        // Doit être redirigé (login ou deny)
        const url = page.url();
        expect(url.includes('reglement_pilote')).toBeFalsy();
    });

    // ── 2. Trésorier : formulaire accessible ─────────────────────────────────

    test('Tresorier can access reglement_pilote form', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(CREDIT_URL);
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

        await page.goto(CREDIT_URL);
        await page.waitForLoadState('domcontentloaded');

        if (!page.url().includes('reglement_pilote') &&
            !page.url().includes('compta') &&
            !page.url().includes('formView')) {
            test.skip(true, 'Formulaire règlement non accessible');
            return;
        }

        // Le bouton HelloAsso ne doit pas être présent
        const helloassoBtn = page.locator('button[value="helloasso"]');
        await expect(helloassoBtn).not.toBeVisible();
    });

    // ── 4. credit_qr avec txid invalide → redirection ────────────────────────

    test('credit_qr with invalid txid redirects', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(QR_URL_FAKE);
        await page.waitForLoadState('domcontentloaded');

        // Doit rediriger (transaction introuvable)
        expect(page.url()).not.toContain('credit_qr');
    });

});
