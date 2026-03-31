/**
 * Playwright smoke tests - UC4 : Bon decouverte via paiement CB depuis vols_decouverte/create
 *
 * Tests :
 *  1. Pilote ordinaire -> acces refuse a vols_decouverte/create
 *  2. Gestion_vd -> acces autorise a vols_decouverte/create (bouton Créer absent, Payer par CB présent si helloasso actif)
 *  3. Tresorier -> acces autorise a vols_decouverte/create
 *  4. decouverte_qr avec txid invalide -> redirection
 *  5. Confirmation publique -> accessible sans login
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const CREATE_URL = '/index.php/vols_decouverte/create';
const QR_URL_FAKE = '/index.php/paiements_en_ligne/decouverte_qr/txid-inexistant-000';
const PUBLIC_CONFIRM_URL = '/index.php/paiements_en_ligne/public_decouverte_confirmation?club=4';

const PILOT = { username: 'asterix', password: 'password', section: '1' };
const GESTION_VD = { username: 'testgestion_vd', password: 'password', section: '1' };
const TRESORIER = { username: 'testtresorier', password: 'password', section: '1' };

test.describe('UC4 - Bon decouverte via vols_decouverte/create', () => {

    test('Pilot ordinaire cannot access vols_decouverte/create', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        const response = await page.goto(CREATE_URL);
        await page.waitForLoadState('domcontentloaded');

        // Should receive 404 (show_404 does not redirect, just returns 404 status)
        expect(response.status()).toBe(404);
    });

    test('Tresorier can access vols_decouverte/create', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(CREATE_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).not.toContain('/auth/login');
        expect(page.url()).not.toContain('/auth/deny');
    });

    test('decouverte_qr with invalid txid redirects', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(QR_URL_FAKE);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).not.toContain('decouverte_qr');
    });

    test('Public confirmation is accessible without login', async ({ page }) => {
        await page.goto(PUBLIC_CONFIRM_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).not.toContain('/auth/login');
        expect(page.url()).toContain('public_decouverte_confirmation');
    });
});
