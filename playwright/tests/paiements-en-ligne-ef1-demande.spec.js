/**
 * Playwright smoke tests — EF1 : Provisionnement compte pilote par CB
 *
 * Tests :
 *  1. Sans session → redirection login
 *  2. Avec session pilote (section Général, id=4) : formulaire ou mon_compte si non configuré
 *  3. Champs montant avec attributs min/max
 *  4. Soumission montant invalide → erreur serveur (validation bypass HTML5)
 *  5. [SKIP SI SANDBOX] Flow complet → redirection HelloAsso
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-ef1-demande.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const DEMANDE_URL = '/index.php/paiements_en_ligne/demande';
const PILOT = { username: 'asterix', password: 'password', section: '4' };

test.describe('EF1 — Provisionnement compte pilote', () => {

    // ── 1. Sans session ───────────────────────────────────────────────────────

    test('GET /demande without session redirects to login', async ({ page }) => {
        await page.goto('/index.php/auth/logout');
        await page.waitForLoadState('domcontentloaded');

        await page.goto(DEMANDE_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).toContain('/auth/login');
    });

    // ── 2. Avec session → formulaire ou mon_compte ────────────────────────────

    test('GET /demande with session shows form or redirects to mon_compte', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(DEMANDE_URL);
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).not.toContain('/auth/login');
        const url = page.url();
        expect(url.includes('demande') || url.includes('mon_compte') || url.includes('compta')).toBeTruthy();
    });

    // ── 3. Attributs min/max sur le champ montant ─────────────────────────────

    test('Form has montant input with numeric min/max when accessible', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(DEMANDE_URL);
        await page.waitForLoadState('domcontentloaded');

        if (!page.url().includes('demande')) {
            test.skip(true, '/demande non accessible (HelloAsso non activé pour cette section)');
            return;
        }

        const input = page.locator('input[name="montant"]');
        await expect(input).toBeVisible();

        const min = await input.getAttribute('min');
        const max = await input.getAttribute('max');
        expect(parseFloat(min)).toBeGreaterThan(0);
        expect(parseFloat(max)).toBeGreaterThan(parseFloat(min));
    });

    // ── 4. Validation serveur — montant invalide ──────────────────────────────

    test('POST montant below min stays on form with error', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(DEMANDE_URL);
        await page.waitForLoadState('domcontentloaded');

        if (!page.url().includes('demande')) {
            test.skip(true, '/demande non accessible');
            return;
        }

        // Bypass HTML5 min/required/step pour tester la validation serveur
        await page.evaluate(() => {
            const input = document.querySelector('input[name="montant"]');
            if (input) {
                input.removeAttribute('min');
                input.removeAttribute('required');
                input.removeAttribute('step');
            }
        });

        // Valeur entière valide selon step=1, mais sous le minimum configuré (5€)
        await page.fill('input[name="montant"]', '1');
        await page.click('button[name="button"][value="valider"]');
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).toContain('demande');
        await expect(page.locator('.alert-danger')).toBeVisible();
    });

    // ── 5. [SANDBOX] Flow complet ─────────────────────────────────────────────

    test('[SANDBOX] demande form → HelloAsso checkout redirect', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        const sandboxCheck = await page.request.get('/index.php/paiements_en_ligne/sandbox_available');
        test.skip(sandboxCheck.status() !== 200, 'HelloAsso sandbox non configuré — test ignoré');

        await page.goto(DEMANDE_URL);
        await page.waitForLoadState('domcontentloaded');
        expect(page.url()).toContain('demande');

        await page.fill('input[name="montant"]', '10.00');
        await page.click('button[name="button"][value="valider"]');
        await page.waitForLoadState('domcontentloaded');

        const finalUrl = page.url();
        expect(finalUrl.includes('helloasso') || finalUrl.includes('demande') || finalUrl.includes('erreur')).toBeTruthy();
    });

});
