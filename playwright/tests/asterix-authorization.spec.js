/**
 * Asterix Authorization Tests - New Authorization System
 *
 * Tests that the new code-based authorization system (v2.0) correctly
 * restricts access for asterix, a simple user (role 'user' only) with
 * access to sections Planeur (1) and Général (4).
 *
 * Asterix is enrolled in use_new_authorization, so require_roles() is active.
 *
 * Usage:
 *   npx playwright test tests/asterix-authorization.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const ASTERIX = { username: 'asterix', password: 'password', section: '1' };

/**
 * Helper: login as asterix and navigate to a route
 */
async function loginAndGoto(page, route) {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);
    await loginPage.goto(route);
    await page.waitForLoadState('domcontentloaded');
}

/**
 * Helper: check if access is granted (not on deny page, no error)
 * Note: Do NOT check for '403' as a substring - normal pages contain account numbers like "403xxx"
 */
async function expectAccessGranted(page, route) {
    const url = page.url();
    const content = await page.content();
    const denied = url.includes('/auth/deny') || url.includes('/auth/login')
        || content.includes('Accès non autorisé') || content.includes('Accès refusé')
        || content.includes('Accès réservé aux administrateurs');

    expect(denied, `Expected access GRANTED for ${route} but was denied (URL: ${url})`).toBeFalsy();
}

/**
 * Helper: check if access is denied (redirect to deny/login page or error message)
 */
async function expectAccessDenied(page, route) {
    const url = page.url();
    const content = await page.content();
    const denied = url.includes('/auth/deny') || url.includes('/auth/login')
        || content.includes('Accès non autorisé') || content.includes('Accès refusé')
        || content.includes('Accès réservé aux administrateurs')
        || content.includes('Mot de passe');

    expect(denied, `Expected access DENIED for ${route} but was granted (URL: ${url})`).toBeTruthy();
}

test.describe('Asterix Authorization - New Auth System', () => {

    // ============================================================
    // ALLOWED ROUTES - asterix has 'user' role
    // ============================================================
    test.describe('Allowed routes (role: user)', () => {

        test('welcome dashboard', async ({ page }) => {
            await loginAndGoto(page, 'welcome');
            await expectAccessGranted(page, 'welcome');
        });

        test('vols_planeur/page - flight log viewing', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/page');
            await expectAccessGranted(page, 'vols_planeur/page');
        });

        test('membre/page - member list', async ({ page }) => {
            await loginAndGoto(page, 'membre/page');
            await expectAccessGranted(page, 'membre/page');
        });

        test('planeur/page - glider fleet', async ({ page }) => {
            await loginAndGoto(page, 'planeur/page');
            await expectAccessGranted(page, 'planeur/page');
        });

        test('avion/page - aircraft fleet', async ({ page }) => {
            await loginAndGoto(page, 'avion/page');
            await expectAccessGranted(page, 'avion/page');
        });

        test('sections/page - sections list', async ({ page }) => {
            await loginAndGoto(page, 'sections/page');
            await expectAccessGranted(page, 'sections/page');
        });

        test('tarifs/page - tariffs list', async ({ page }) => {
            await loginAndGoto(page, 'tarifs/page');
            await expectAccessGranted(page, 'tarifs/page');
        });

        test('vols_avion/page - aircraft flights', async ({ page }) => {
            await loginAndGoto(page, 'vols_avion/page');
            await expectAccessGranted(page, 'vols_avion/page');
        });

        test('tickets/page - flight tickets', async ({ page }) => {
            await loginAndGoto(page, 'tickets/page');
            await expectAccessGranted(page, 'tickets/page');
        });

        test('procedures/page - procedures list', async ({ page }) => {
            await loginAndGoto(page, 'procedures/page');
            await expectAccessGranted(page, 'procedures/page');
        });

        test('alarmes - pilot conditions', async ({ page }) => {
            await loginAndGoto(page, 'alarmes');
            await expectAccessGranted(page, 'alarmes');
        });
    });

    // ============================================================
    // DENIED ROUTES - accounting (requires tresorier or bureau)
    // ============================================================
    test.describe('Denied routes - accounting (tresorier/bureau)', () => {

        test('compta/page - accounting entries', async ({ page }) => {
            await loginAndGoto(page, 'compta/page');
            await expectAccessDenied(page, 'compta/page');
        });

        test('comptes/page - chart of accounts', async ({ page }) => {
            await loginAndGoto(page, 'comptes/page');
            await expectAccessDenied(page, 'comptes/page');
        });

        test('welcome/compta - accounting dashboard', async ({ page }) => {
            await loginAndGoto(page, 'welcome/compta');
            await page.waitForTimeout(1000);
            await expectAccessDenied(page, 'welcome/compta');
        });

        test('configuration - club config (requires bureau)', async ({ page }) => {
            await loginAndGoto(page, 'configuration/page');
            await expectAccessDenied(page, 'configuration/page');
        });
    });

    // ============================================================
    // DENIED ROUTES - CA (requires ca role)
    // ============================================================
    test.describe('Denied routes - CA management', () => {

        test('achats/page - purchases', async ({ page }) => {
            await loginAndGoto(page, 'achats/page');
            await expectAccessDenied(page, 'achats/page');
        });

        test('terrains/page - airfields', async ({ page }) => {
            await loginAndGoto(page, 'terrains/page');
            await expectAccessDenied(page, 'terrains/page');
        });

        test('rapports/ffvv - FFVV reports', async ({ page }) => {
            await loginAndGoto(page, 'rapports/ffvv');
            await expectAccessDenied(page, 'rapports/ffvv');
        });

        test('welcome/ca - CA dashboard', async ({ page }) => {
            await loginAndGoto(page, 'welcome/ca');
            await page.waitForTimeout(1000);
            await expectAccessDenied(page, 'welcome/ca');
        });

        test('licences/page - licences management', async ({ page }) => {
            await loginAndGoto(page, 'licences/page');
            await expectAccessDenied(page, 'licences/page');
        });
    });

    // ============================================================
    // DENIED ROUTES - instructor (requires instructeur role)
    // ============================================================
    test.describe('Denied routes - instructor', () => {

        test('programmes - training programs', async ({ page }) => {
            await loginAndGoto(page, 'programmes');
            await expectAccessDenied(page, 'programmes');
        });
    });

    // ============================================================
    // DENIED ROUTES - admin (requires club-admin)
    // ============================================================
    test.describe('Denied routes - admin', () => {

        test('admin/backup - system backup', async ({ page }) => {
            await loginAndGoto(page, 'admin/backup');
            await expectAccessDenied(page, 'admin/backup');
        });

        test('authorization - authorization management', async ({ page }) => {
            await loginAndGoto(page, 'authorization');
            await expectAccessDenied(page, 'authorization');
        });

        test('login_as - user impersonation', async ({ page }) => {
            await loginAndGoto(page, 'login_as');
            await expectAccessDenied(page, 'login_as');
        });
    });
});
