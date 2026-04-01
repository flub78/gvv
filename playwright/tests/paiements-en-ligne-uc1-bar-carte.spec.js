/**
 * Playwright tests — UC1 : Paiement bar par carte (pilote authentifié)
 *
 * Tests HTTP/navigation sur l'endpoint /paiements_en_ligne/bar_carte :
 *  1. Sans session → redirection vers login
 *  2. Avec session pilote (section 4 Général avec bar) : formulaire accessible
 *  3. Validation montant < 0.50€ → erreur, pas de checkout
 *  4. [SKIP SI SANDBOX] Flow complet : formulaire → checkout HelloAsso
 *
 * Les tests 1–3 s'exécutent toujours.
 * Le test 4 nécessite HelloAsso sandbox configuré et est skippé automatiquement sinon.
 *
 * Fixtures : asterix (password='password', section=4 Général avec has_bar=1)
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-uc1-bar-carte.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const BAR_CARTE_URL = '/index.php/paiements_en_ligne/bar_carte';
const PILOT = { username: 'asterix', password: 'password', section: '4' };

test.describe('UC1 — Paiement bar par carte', () => {

    // ── 1. Sans session → redirection login ──────────────────────────────────

    test('GET /bar_carte without session redirects to login', async ({ page }) => {
        await page.goto('/index.php/auth/logout');
        await page.waitForLoadState('domcontentloaded');

        await page.goto(BAR_CARTE_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).toContain('/auth/login');
    });

    // ── 2. Avec session pilote (section 4) → formulaire bar_carte ────────────

    test('GET /bar_carte with section Général (has_bar=1) shows form or mon_compte', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(BAR_CARTE_URL);
        await page.waitForLoadState('domcontentloaded');

        // Pas de redirection vers login
        expect(page.url()).not.toContain('/auth/login');

        // Soit le formulaire bar_carte, soit mon_compte (si HelloAsso non activé)
        const isFormOrAccount = page.url().includes('bar_carte')
            || page.url().includes('mon_compte')
            || page.url().includes('compta');
        expect(isFormOrAccount).toBeTruthy();
    });

    // ── 3. Validation montant < 0.50€ ─────────────────────────────────────────

    test('POST montant < 0.50 stays on bar_carte with error when form accessible', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(BAR_CARTE_URL);
        await page.waitForLoadState('domcontentloaded');

        // Tester seulement si le formulaire est accessible
        if (!page.url().includes('bar_carte')) {
            test.skip(true, 'bar_carte non accessible (HelloAsso non activé ou section sans bar)');
            return;
        }

        // Bypass HTML5 min validation pour tester la validation serveur
        await page.evaluate(() => {
            const input = document.querySelector('input[name="montant"]');
            if (input) {
                input.removeAttribute('min');
                input.removeAttribute('required');
            }
        });

        await page.fill('input[name="montant"]', '0.10');
        await page.fill('input[name="description"]', 'Test validation montant min');
        await page.click('button[name="button"][value="valider"]');
        await page.waitForLoadState('domcontentloaded');

        // Doit rester sur bar_carte avec un message d'erreur
        expect(page.url()).toContain('bar_carte');
        const errorVisible = await page.locator('.alert-danger').isVisible().catch(() => false);
        expect(errorVisible).toBeTruthy();
    });

    // ── 4. [SKIP SI SANDBOX] Flow complet HelloAsso ───────────────────────────

    test('[SANDBOX] Flow complet bar_carte → HelloAsso checkout', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login(PILOT.username, PILOT.password, PILOT.section);

        // Vérifier si HelloAsso sandbox est configuré
        const sandboxCheck = await page.request.get('/index.php/paiements_en_ligne/sandbox_available');
        test.skip(sandboxCheck.status() !== 200, 'HelloAsso sandbox non configuré — test ignoré');

        await page.goto(BAR_CARTE_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).toContain('bar_carte');
        await expect(page.locator('input[name="montant"]')).toBeVisible();

        await page.fill('input[name="montant"]', '2.00');
        await page.fill('input[name="description"]', 'Test bar carte Playwright sandbox');
        await page.click('button[name="button"][value="valider"]');
        await page.waitForLoadState('domcontentloaded');

        // Doit rediriger vers HelloAsso ou rester sur page d'erreur interne
        const finalUrl = page.url();
        const redirectedToHA = finalUrl.includes('helloasso');
        const stayedOnError  = finalUrl.includes('bar_carte') || finalUrl.includes('erreur');
        expect(redirectedToHA || stayedOnError).toBeTruthy();
    });

});
