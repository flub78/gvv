/**
 * Playwright smoke tests — Paiements en ligne : contrôleur de base (EF6)
 *
 * Tests:
 * 1. /paiements_en_ligne retourne HTTP 200 pour un pilote connecté (liste des transactions)
 * 2. /paiements_en_ligne/confirmation accessible (page de confirmation)
 * 3. /paiements_en_ligne/annulation accessible (page d'annulation)
 * 4. /paiements_en_ligne/erreur accessible (page d'erreur)
 * 5. /paiements_en_ligne/sandbox_available répond JSON (200 ou 503)
 * 6. Accès non connecté redirige vers login
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-base.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

test.describe.configure({ mode: 'serial' });

const USER     = 'asterix';
const PASSWORD = 'password';
const SECTION  = '1';

async function login(page) {
    await page.goto('/index.php/auth/logout');
    await page.waitForLoadState('domcontentloaded');
    await page.goto('/index.php/auth/login');
    await page.waitForLoadState('domcontentloaded');
    await page.fill('input[name="username"]', USER);
    await page.fill('input[name="password"]', PASSWORD);
    const sel = page.locator('select[name="section"]');
    if (await sel.count() > 0) await sel.selectOption(SECTION);
    await page.click('input[type="submit"], button[type="submit"]');
    await page.waitForLoadState('domcontentloaded');
    try {
        const btn = page.locator('button:has-text("OK"), button:has-text("ok")');
        if (await btn.isVisible({ timeout: 2000 })) {
            await btn.click();
            await page.waitForTimeout(500);
        }
    } catch (_) {}
}

test.describe('EF6 — Base controller pages', () => {

    // ── Logged-in access ─────────────────────────────────────────────────────

    test('index returns HTTP 200 and no PHP errors for logged-in pilot', async ({ page }) => {
        await login(page);

        await page.goto('/index.php/paiements_en_ligne');
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).not.toContain('/auth/login');

        const body = await page.textContent('body');
        expect(body).not.toContain('Fatal error');
        expect(body).not.toContain('A PHP Error was encountered');
        expect(body).not.toContain('An uncaught Exception was encountered');

        // Index page should show the title
        await expect(page.locator('h3, .h3')).toBeVisible();
    });

    test('confirmation page is accessible', async ({ page }) => {
        await login(page);

        await page.goto('/index.php/paiements_en_ligne/confirmation');
        await page.waitForLoadState('domcontentloaded');

        const body = await page.textContent('body');
        expect(body).not.toContain('Fatal error');
        expect(body).not.toContain('A PHP Error was encountered');
    });

    test('annulation page is accessible', async ({ page }) => {
        await login(page);

        await page.goto('/index.php/paiements_en_ligne/annulation');
        await page.waitForLoadState('domcontentloaded');

        const body = await page.textContent('body');
        expect(body).not.toContain('Fatal error');
        expect(body).not.toContain('A PHP Error was encountered');
    });

    test('erreur page is accessible', async ({ page }) => {
        await login(page);

        await page.goto('/index.php/paiements_en_ligne/erreur');
        await page.waitForLoadState('domcontentloaded');

        const body = await page.textContent('body');
        expect(body).not.toContain('Fatal error');
        expect(body).not.toContain('A PHP Error was encountered');
    });

    test('sandbox_available returns JSON with status 200 or 503', async ({ page }) => {
        await login(page);

        const response = await page.request.get('/index.php/paiements_en_ligne/sandbox_available');
        expect([200, 503]).toContain(response.status());

        const json = await response.json();
        expect(typeof json.available).toBe('boolean');
    });

    // ── Unauthenticated access ────────────────────────────────────────────────

    test('unauthenticated access to index redirects to login', async ({ page }) => {
        await page.goto('/index.php/auth/logout');
        await page.waitForLoadState('domcontentloaded');

        await page.goto('/index.php/paiements_en_ligne');
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).toContain('/auth/login');
    });

});
