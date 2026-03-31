/**
 * Playwright smoke tests - UC4 : Bon decouverte via lien / QR public
 *
 * Tests :
 *  1. Pilote ordinaire -> acces refuse a l'ecran gestionnaire
 *  2. Tresorier -> acces autorise a l'ecran gestionnaire
 *  3. decouverte_qr avec txid invalide -> redirection
 *  4. Confirmation publique -> accessible sans login
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const MANAGER_URL = '/index.php/paiements_en_ligne/decouverte_manager';
const QR_URL_FAKE = '/index.php/paiements_en_ligne/decouverte_qr/txid-inexistant-000';
const PUBLIC_CONFIRM_URL = '/index.php/paiements_en_ligne/public_decouverte_confirmation?club=4';

const PILOT = { username: 'asterix', password: 'password', section: '1' };
const TRESORIER = { username: 'testtresorier', password: 'password', section: '1' };

test.describe('UC4 - Bon decouverte via lien / QR public', () => {

    test('Pilot cannot access decouverte_manager', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(MANAGER_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url().includes('decouverte_manager')).toBeFalsy();
    });

    test('Tresorier can access decouverte_manager', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(MANAGER_URL);
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
