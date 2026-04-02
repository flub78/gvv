/**
 * Playwright smoke tests — UC3 : Cotisation en ligne par débit de solde
 *
 * Tests :
 *  1. Pilote sans session → redirigé vers login
 *  2. Pilote connecté → page cotisation accessible (formulaire ou message aucun produit)
 *  3. Pilote connecté → formulaire affiche le solde disponible
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-uc3-cotisation-pilote.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const COTISATION_URL = '/index.php/paiements_en_ligne/cotisation';
const BASE_URL       = 'http://gvv.net';

const PILOT = { username: 'asterix', password: 'password', section: '1' };

test.describe('UC3 — Cotisation en ligne (débit de solde)', () => {

    // ── 1. Sans session → login ───────────────────────────────────────────────

    test('Unauthenticated access redirects to login', async ({ page }) => {
        await page.goto(BASE_URL + COTISATION_URL);
        await page.waitForLoadState('domcontentloaded');
        expect(page.url()).toContain('/auth/login');
    });

    // ── 2. Pilote connecté → page cotisation accessible ───────────────────────

    test('Authenticated pilot can access cotisation page', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(BASE_URL + COTISATION_URL);
        await page.waitForLoadState('domcontentloaded');

        // Ne doit pas rediriger vers login
        expect(page.url()).not.toContain('/auth/login');

        // Soit le formulaire de sélection, soit le message "aucun produit"
        const body = await page.content();
        const hasForm    = body.includes('produit_id') || body.includes('form-check-input');
        const hasMessage = body.includes('Aucune cotisation') || body.includes('No membership');
        expect(hasForm || hasMessage).toBeTruthy();
    });

    // ── 3. Le solde disponible est affiché ────────────────────────────────────

    test('Cotisation page displays available balance', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(BASE_URL + COTISATION_URL);
        await page.waitForLoadState('domcontentloaded');

        // Ne doit pas être redirigé vers login
        expect(page.url()).not.toContain('/auth/login');

        // Le solde doit être affiché (clé lang gvv_cotisation_solde_label)
        const body = await page.content();
        const hasSolde = body.includes('Solde disponible') || body.includes('Available balance') ||
                         body.includes('Beschikbaar saldo') || body.includes('alert-info');
        expect(hasSolde).toBeTruthy();
    });

});
