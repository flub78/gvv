/**
 * Abraracourcix Authorization Tests - New Authorization System
 *
 * Tests that the new code-based authorization system (v2.0) correctly
 * manages access for abraracourcix, a user with roles:
 *   - user in section Planeur (1)
 *   - user in section Avion (3)
 *   - user in section ULM (2)
 *   - user in section Général (4)
 *
 * Additional mniveaux bit flags:
 *   - BIT_REMORQUEUR (8192) - tow pilot
 *   - BIT_FI_AVION (131072) - flight instructor (airplane)
 *   - BIT_CA (64) - member of Conseil d'Administration
 *
 * Abraracourcix is enrolled in use_new_authorization, so require_roles() is active.
 *
 * Compared to obelix:
 *   - Has CA access: achats, terrains, rapports/ffvv, welcome/ca, licences, programmes
 *   - Has instructor access: programmes (via CA bit)
 *   - Does NOT have planchiste role: stats pages remain denied (legacy dx_auth check)
 *   - Does NOT have tresorier role: accounting pages remain denied
 *   - Does NOT have bureau role: configuration remains denied
 *   - Does NOT have admin role: admin pages remain denied
 *
 * Usage:
 *   npx playwright test tests/abraracourcix-authorization.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const ABRARACOURCIX = { username: 'abraracourcix', password: 'password', section: '1' };

/**
 * Helper: login as abraracourcix and navigate to a route
 */
async function loginAndGoto(page, route, section) {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(ABRARACOURCIX.username, ABRARACOURCIX.password, section || ABRARACOURCIX.section);
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

test.describe('Abraracourcix Authorization - New Auth System', () => {

    // ============================================================
    // ALLOWED ROUTES - abraracourcix has 'user' role (all sections)
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
    // ALLOWED ROUTES - CA role (via BIT_CA mniveaux flag)
    // ============================================================
    test.describe('Allowed routes (role: CA)', () => {

        test('achats/page - purchases', async ({ page }) => {
            await loginAndGoto(page, 'achats/page');
            await expectAccessGranted(page, 'achats/page');
        });

        test('terrains/page - airfields', async ({ page }) => {
            await loginAndGoto(page, 'terrains/page');
            await expectAccessGranted(page, 'terrains/page');
        });

        test('rapports/ffvv - FFVV reports', async ({ page }) => {
            await loginAndGoto(page, 'rapports/ffvv');
            await expectAccessGranted(page, 'rapports/ffvv');
        });

        test('welcome/ca - CA dashboard', async ({ page }) => {
            await loginAndGoto(page, 'welcome/ca');
            await page.waitForTimeout(1000);
            await expectAccessGranted(page, 'welcome/ca');
        });

        test('licences/page - licences management', async ({ page }) => {
            await loginAndGoto(page, 'licences/page');
            await expectAccessGranted(page, 'licences/page');
        });

        test('programmes - training programs (CA can manage)', async ({ page }) => {
            await loginAndGoto(page, 'programmes');
            await expectAccessGranted(page, 'programmes');
        });
    });

    // ============================================================
    // DENIED ROUTES - planchiste stats (requires planchiste role via legacy dx_auth)
    // Abraracourcix has no planchiste role
    // ============================================================
    test.describe('Denied routes - planchiste stats pages', () => {

        test('vols_planeur/statistic - flight statistics', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/statistic', '1');
            await expectAccessDenied(page, 'vols_planeur/statistic');
        });

        test('vols_planeur/cumuls - cumulative stats', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/cumuls', '1');
            await expectAccessDenied(page, 'vols_planeur/cumuls');
        });

        test('vols_planeur/histo - history', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/histo', '1');
            await expectAccessDenied(page, 'vols_planeur/histo');
        });

        test('vols_planeur/age - age statistics', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/age', '1');
            await expectAccessDenied(page, 'vols_planeur/age');
        });

        test('vols_planeur/jours_de_vol - flying days', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/jours_de_vol', '1');
            await expectAccessDenied(page, 'vols_planeur/jours_de_vol');
        });
    });

    // ============================================================
    // DENIED ROUTES - accounting (requires tresorier)
    // Abraracourcix has no tresorier role
    // ============================================================
    test.describe('Denied routes - accounting (tresorier)', () => {

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
    });

    // ============================================================
    // DENIED ROUTES - bureau (requires bureau role)
    // Abraracourcix has no bureau role
    // ============================================================
    test.describe('Denied routes - bureau', () => {

        test('configuration - club config (requires bureau)', async ({ page }) => {
            await loginAndGoto(page, 'configuration/page');
            await expectAccessDenied(page, 'configuration/page');
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
