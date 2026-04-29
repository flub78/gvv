/**
 * Playwright smoke tests — EF4 : Liste des paiements trésorier
 *
 * Tests :
 *  1. Accès refusé pour un pilote simple (→ login ou accès refusé)
 *  2. Accès autorisé pour un trésorier (→ page liste visible)
 *  3. Tableau et filtres présents
 *  4. Lien export CSV présent
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-ef4-liste.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');
const { USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON } = require('./helpers/gvv-config');

const LISTE_URL = '/index.php/paiements_en_ligne/liste';

// Pilote ordinaire (pas de rôle tresorier)
const PILOT     = { username: 'asterix',       password: 'password', section: '1' };
// Trésorier de test — section Planeur (id=1)
const TRESORIER = { username: 'testtresorier', password: 'password', section: '1' };

test.describe('EF4 — Liste paiements trésorier', () => {

    // ── 1. Accès refusé pour un pilote simple ─────────────────────────────

    test('GET /liste redirects or denies access for a plain pilot', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(PILOT.username, PILOT.password, PILOT.section);

        await page.goto(LISTE_URL);
        await page.waitForLoadState('domcontentloaded');

        // Doit ne PAS afficher la page liste (redirection login ou accès refusé)
        const url = page.url();
        const denied = url.includes('auth/login')
            || url.includes('auth/deny')
            || !(url.includes('liste'));
        expect(denied).toBeTruthy();
    });

    // ── 2. Accès autorisé pour un trésorier ───────────────────────────────

    test('GET /liste accessible for a tresorier', async ({ page }) => {
        test.skip(USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON);
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(LISTE_URL);
        await page.waitForLoadState('domcontentloaded');

        // Ne doit pas rediriger vers login ou deny
        expect(page.url()).not.toContain('/auth/login');
        expect(page.url()).not.toContain('/auth/deny');
    });

    // ── 3. Filtres et tableau présents ────────────────────────────────────

    test('Liste page shows filter form and table (or empty message)', async ({ page }) => {
        test.skip(USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON);
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(LISTE_URL);
        await page.waitForLoadState('domcontentloaded');

        if (!page.url().includes('liste')) {
            test.skip(true, 'Page liste non accessible pour ce trésorier');
            return;
        }

        // Formulaire de filtres
        await expect(page.locator('input[name="date_from"]')).toBeVisible();
        await expect(page.locator('input[name="date_to"]')).toBeVisible();
        await expect(page.locator('select[name="statut"]')).toBeVisible();

        // Tableau ou message vide
        const hasTable = await page.locator('table').isVisible().catch(() => false);
        const hasEmpty = await page.locator('.alert-info').isVisible().catch(() => false);
        expect(hasTable || hasEmpty).toBeTruthy();
    });

    // ── 4. Lien export CSV présent ────────────────────────────────────────

    test('Liste page has CSV export link', async ({ page }) => {
        test.skip(USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON);
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(TRESORIER.username, TRESORIER.password, TRESORIER.section);

        await page.goto(LISTE_URL);
        await page.waitForLoadState('domcontentloaded');

        if (!page.url().includes('liste')) {
            test.skip(true, 'Page liste non accessible pour ce trésorier');
            return;
        }

        const csvLink = page.locator('a[href*="liste_csv"]');
        await expect(csvLink).toBeVisible();
    });

});
