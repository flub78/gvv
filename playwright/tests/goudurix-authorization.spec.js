/**
 * Goudurix Authorization Tests - New Authorization System
 *
 * Tests that the new code-based authorization system (v2.0) correctly
 * manages access for goudurix, a user with roles:
 *   - user in section Avion (3)
 *   - user in section Général (4)
 *
 * Additional mniveaux bit flags:
 *   - BIT_TRESORIER (8) - treasurer
 *
 * Goudurix is enrolled in use_new_authorization, so require_roles() is active.
 *
 * Compared to obelix:
 *   - Has tresorier access: compta/page, comptes/page, welcome/compta
 *   - Only in sections Avion + Général (no Planeur, no ULM)
 *   - Does NOT have planchiste role: stats pages remain denied
 *   - Does NOT have CA role: achats, terrains, rapports, licences, programmes denied
 *   - Does NOT have bureau role: configuration denied
 *   - Does NOT have admin role: admin pages denied
 *
 * Usage:
 *   npx playwright test tests/goudurix-authorization.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const GOUDURIX = { username: 'goudurix', password: 'password', section: '3' };

/**
 * Helper: login as goudurix and navigate to a route
 */
async function loginAndGoto(page, route, section) {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(GOUDURIX.username, GOUDURIX.password, section || GOUDURIX.section);
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

test.describe('Goudurix Authorization - New Auth System', () => {

    // ============================================================
    // ALLOWED ROUTES - goudurix has 'user' role (sections Avion + Général)
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
    // ALLOWED ROUTES - tresorier role (via BIT_TRESORIER mniveaux flag)
    // ============================================================
    test.describe('Allowed routes (role: tresorier)', () => {

        test('compta/page - accounting entries', async ({ page }) => {
            await loginAndGoto(page, 'compta/page');
            await expectAccessGranted(page, 'compta/page');
        });

        test('comptes/page - chart of accounts', async ({ page }) => {
            await loginAndGoto(page, 'comptes/page');
            await expectAccessGranted(page, 'comptes/page');
        });

        test('welcome/compta - accounting dashboard', async ({ page }) => {
            await loginAndGoto(page, 'welcome/compta');
            await page.waitForTimeout(1000);
            await expectAccessGranted(page, 'welcome/compta');
        });

        test('dashboard shows Tresorerie section', async ({ page }) => {
            await loginAndGoto(page, 'welcome');
            const content = await page.content();
            expect(content, 'Expected "Trésorerie" section to be visible on dashboard for tresorier').toContain('Trésorerie');
        });

        test('dashboard shows Tresorerie section when logged in with section Général (4)', async ({ page }) => {
            await loginAndGoto(page, 'welcome', '4');
            const content = await page.content();
            expect(content, 'Expected "Trésorerie" visible on dashboard even from section 4').toContain('Trésorerie');
        });

        test('accounting nav menu visible when logged in with section Général (4)', async ({ page }) => {
            await loginAndGoto(page, 'welcome', '4');
            const content = await page.content();
            // The Comptabilité nav item must appear regardless of current section
            expect(content, 'Expected accounting nav menu for tresorier in section 4').toContain('Comptabilit');
        });
    });

    // ============================================================
    // SECTION SELECTOR - only authorized sections visible
    // Goudurix has roles in sections 3 (Avion) and 4 (Général) only.
    // The nav section selector must NOT show sections 1 (Planeur) or 2 (ULM).
    // ============================================================
    test.describe('Section selector - only authorized sections', () => {

        test('nav section selector shows Avion (3) for goudurix', async ({ page }) => {
            await loginAndGoto(page, 'welcome');
            const content = await page.content();
            expect(content, 'Expected Avion in section selector').toContain('Avion');
        });

        test('nav section selector shows Général (4) for goudurix', async ({ page }) => {
            await loginAndGoto(page, 'welcome');
            const content = await page.content();
            expect(content, 'Expected Général in section selector').toContain('Général');
        });

        test('nav section selector does NOT show Planeur (1) for goudurix', async ({ page }) => {
            await loginAndGoto(page, 'welcome');
            // The section selector dropdown — find the select element for section
            const sectionSelect = page.locator('select[name="section"]');
            const options = await sectionSelect.locator('option').allTextContents();
            expect(options, 'Section selector must not contain Planeur for goudurix').not.toContain('Planeur');
        });

        test('nav section selector does NOT show ULM (2) for goudurix', async ({ page }) => {
            await loginAndGoto(page, 'welcome');
            const sectionSelect = page.locator('select[name="section"]');
            const options = await sectionSelect.locator('option').allTextContents();
            expect(options, 'Section selector must not contain ULM for goudurix').not.toContain('ULM');
        });
    });

    // ============================================================
    // READ ACCESS IN ALL TRESORIER SECTIONS
    // Goudurix is tresorier in sections 3 (Avion) and 4 (Général).
    // Accounting pages must be accessible from either tresorier section.
    // ============================================================
    test.describe('Tresorier - read access in all tresorier sections', () => {

        test('compta/page accessible when logged in with section Général (4)', async ({ page }) => {
            await loginAndGoto(page, 'compta/page', '4');
            await expectAccessGranted(page, 'compta/page');
        });

        test('comptes/page accessible when logged in with section Général (4)', async ({ page }) => {
            await loginAndGoto(page, 'comptes/page', '4');
            await expectAccessGranted(page, 'comptes/page');
        });
    });

    // ============================================================
    // SECTION-BASED WRITE RESTRICTION
    // Goudurix is tresorier in section 3 (Avion) and 4 (Général).
    // Entry 29563 belongs to section 3 (Avion)  → can edit.
    // Entry 1696  belongs to section 1 (Planeur) → read-only.
    // ============================================================
    test.describe('Tresorier - section-based write restriction', () => {

        test('edit entry from own section (Avion=3, id=29563) shows modification form', async ({ page }) => {
            await loginAndGoto(page, 'compta/edit/29563', '3');
            await expectAccessGranted(page, 'compta/edit/29563');
            const content = await page.content();
            // MODIFICATION mode: no "autre section" readonly message
            expect(content).not.toContain('trésorier d\'une autre section');
            expect(content).not.toContain('lecture seule');
        });

        test('edit entry from other section (Planeur=1, id=1696) shows read-only form', async ({ page }) => {
            await loginAndGoto(page, 'compta/edit/1696', '3');
            // Access is granted (tresorier can view any section)
            await expectAccessGranted(page, 'compta/edit/1696');
            const content = await page.content();
            // VISUALISATION mode: readonly message shown, no submit button
            expect(content).toContain('autre section');
        });
    });

    // ============================================================
    // DENIED ROUTES - planchiste stats (requires planchiste role via legacy dx_auth)
    // Goudurix has no planchiste role
    // ============================================================
    test.describe('Denied routes - planchiste stats pages', () => {

        test('vols_planeur/statistic - flight statistics', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/statistic', '3');
            await expectAccessDenied(page, 'vols_planeur/statistic');
        });

        test('vols_planeur/cumuls - cumulative stats', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/cumuls', '3');
            await expectAccessDenied(page, 'vols_planeur/cumuls');
        });

        test('vols_planeur/histo - history', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/histo', '3');
            await expectAccessDenied(page, 'vols_planeur/histo');
        });

        test('vols_planeur/age - age statistics', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/age', '3');
            await expectAccessDenied(page, 'vols_planeur/age');
        });

        test('vols_planeur/jours_de_vol - flying days', async ({ page }) => {
            await loginAndGoto(page, 'vols_planeur/jours_de_vol', '3');
            await expectAccessDenied(page, 'vols_planeur/jours_de_vol');
        });
    });

    // ============================================================
    // DENIED ROUTES - CA (requires ca role)
    // Goudurix has no CA role
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
    // Goudurix has no instructor role
    // ============================================================
    test.describe('Denied routes - instructor', () => {

        test('programmes - training programs', async ({ page }) => {
            await loginAndGoto(page, 'programmes');
            await expectAccessDenied(page, 'programmes');
        });
    });

    // ============================================================
    // DENIED ROUTES - bureau (requires bureau role)
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
