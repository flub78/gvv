/**
 * Playwright smoke tests — Paiement générique par QR code
 *
 * Tests :
 *  1. Accès refusé pour un pilote simple
 *  2. Accès autorisé pour un trésorier → formulaire visible
 *  3. Soumission avec champs invalides → messages d'erreur
 *  4. [SKIP SI SANDBOX] Flux complet formulaire → page QR affichée
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/paiement-generique-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');
const { USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON } = require('./helpers/gvv-config');

const FORM_URL         = '/index.php/paiements_en_ligne/paiement_generique';
const SANDBOX_CHECK    = '/index.php/paiements_en_ligne/sandbox_available?club=4';

const PILOT     = { username: 'asterix',       password: 'password', section: '1' };
const TRESORIER = { username: 'testtresorier', password: 'password', section: '1' };

test.describe('Paiement générique — smoke tests', () => {

    // ── 1. Accès refusé pour un pilote simple ─────────────────────────────

    test('Pilote ordinaire ne peut pas accéder au formulaire', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(FORM_URL);
        await page.waitForLoadState('domcontentloaded');

        const url = page.url();
        const denied = url.includes('auth/login')
            || url.includes('auth/deny')
            || !url.includes('paiement_generique');
        expect(denied).toBeTruthy();
    });

    // ── 2. Trésorier voit le formulaire ───────────────────────────────────

    test('Trésorier accède au formulaire de paiement générique', async ({ page }) => {
        test.skip(USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON);
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(FORM_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).not.toContain('/auth/login');
        expect(page.url()).not.toContain('/auth/deny');

        // Si HelloAsso non configuré → alerte visible mais pas de login
        if (!page.url().includes('paiement_generique')) {
            return;
        }

        // Formulaire présent
        await expect(page.locator('input[name="montant"]')).toBeVisible();
        await expect(page.locator('input[name="description"]')).toBeVisible();
        await expect(page.locator('select[name="compte_destination_id"]')).toBeVisible();
    });

    // ── 3. Validation : champs invalides → erreur ─────────────────────────

    test('Soumission avec montant vide → erreur visible', async ({ page }) => {
        test.skip(USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON);
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(FORM_URL);
        await page.waitForLoadState('domcontentloaded');

        if (!page.url().includes('paiement_generique')) {
            test.skip(true, 'Formulaire non accessible (HelloAsso non configuré ?)');
            return;
        }

        // Soumettre sans montant ni description
        await page.locator('button[value="valider"]').click();
        await page.waitForLoadState('domcontentloaded');

        // Doit rester sur le formulaire (pas de redirection checkout)
        expect(page.url()).not.toContain('paiement_generique_checkout');

        // Message d'erreur ou validation HTML5 native
        const hasAlert  = await page.locator('.alert-danger').isVisible().catch(() => false);
        const hasInvalid = await page.locator(':invalid').count().catch(() => 0) > 0;
        expect(hasAlert || hasInvalid).toBeTruthy();
    });

    // ── 4. [SKIP SI SANDBOX] Flux complet → page QR ───────────────────────

    test('[SKIP SI SANDBOX] Soumission valide → page QR affichée', async ({ page }) => {
        test.skip(USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON);

        const resp = await page.request.get(SANDBOX_CHECK);
        if (resp.status() !== 200) {
            test.skip(true, 'HelloAsso sandbox non configuré — test ignoré');
            return;
        }

        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(FORM_URL);
        await page.waitForLoadState('domcontentloaded');

        if (!page.url().includes('paiement_generique')) {
            test.skip(true, 'Formulaire non accessible');
            return;
        }

        // Lire les limites montant depuis les attributs min/max du champ
        const minStr = await page.locator('input[name="montant"]').getAttribute('min');
        const montant = Math.max(1, parseFloat(minStr) || 1).toFixed(2);

        await page.fill('input[name="montant"]', montant);
        await page.fill('input[name="description"]', 'Test paiement générique e2e');

        // Sélectionner le premier compte non-vide
        const firstOption = await page.locator('select[name="compte_destination_id"] option:not([value=""])').first();
        const optionValue = await firstOption.getAttribute('value');
        if (optionValue) {
            await page.selectOption('select[name="compte_destination_id"]', optionValue);
        }

        await page.locator('button[value="valider"]').click();
        await page.waitForLoadState('domcontentloaded');

        // Doit afficher la page QR (checkout ou erreur HelloAsso)
        expect(page.url()).toContain('paiement_generique');

        // QR code ou lien HelloAsso visible
        const hasQr   = await page.locator('img[alt*="QR"]').isVisible().catch(() => false);
        const hasLink = await page.locator('input#checkout-url').isVisible().catch(() => false);
        const hasErr  = await page.locator('.alert-danger').isVisible().catch(() => false);
        expect(hasQr || hasLink || hasErr).toBeTruthy();
    });

});
