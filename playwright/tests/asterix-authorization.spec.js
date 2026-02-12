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

test.describe('Asterix Authorization - New Auth System', () => {

    test.describe('Allowed routes (role: user)', () => {

        test('asterix can access welcome dashboard', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('welcome');
            await page.waitForLoadState('domcontentloaded');

            // Should NOT be on the deny page
            expect(page.url()).not.toContain('/auth/deny');
            const content = await page.content();
            expect(content).not.toContain('Accès non autorisé');

            console.log('✓ asterix can access welcome');
        });

        test('asterix can access vols_planeur/page', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('vols_planeur/page');
            await page.waitForLoadState('domcontentloaded');

            expect(page.url()).not.toContain('/auth/deny');
            const content = await page.content();
            expect(content).not.toContain('Accès non autorisé');

            console.log('✓ asterix can access vols_planeur/page');
        });

        test('asterix can access membre/page', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('membre/page');
            await page.waitForLoadState('domcontentloaded');

            expect(page.url()).not.toContain('/auth/deny');
            const content = await page.content();
            expect(content).not.toContain('Accès non autorisé');

            console.log('✓ asterix can access membre/page');
        });

        test('asterix can access planeur/page', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('planeur/page');
            await page.waitForLoadState('domcontentloaded');

            expect(page.url()).not.toContain('/auth/deny');
            const content = await page.content();
            expect(content).not.toContain('Accès non autorisé');

            console.log('✓ asterix can access planeur/page');
        });
    });

    test.describe('Denied routes (missing roles)', () => {

        test('asterix cannot access compta/page (requires tresorier)', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('compta/page');
            await page.waitForLoadState('domcontentloaded');

            const url = page.url();
            const content = await page.content();
            const denied = url.includes('/auth/deny') || content.includes('Accès non autorisé');
            expect(denied).toBeTruthy();

            console.log('✓ asterix denied access to compta/page');
        });

        test('asterix cannot access comptes/page (requires tresorier)', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('comptes/page');
            await page.waitForLoadState('domcontentloaded');

            const url = page.url();
            const content = await page.content();
            const denied = url.includes('/auth/deny') || content.includes('Accès non autorisé');
            expect(denied).toBeTruthy();

            console.log('✓ asterix denied access to comptes/page');
        });

        test('asterix cannot access achats/page (requires ca)', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('achats/page');
            await page.waitForLoadState('domcontentloaded');

            const url = page.url();
            const content = await page.content();
            const denied = url.includes('/auth/deny') || content.includes('Accès non autorisé');
            expect(denied).toBeTruthy();

            console.log('✓ asterix denied access to achats/page');
        });

        test('asterix cannot access alarmes (requires ca)', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('alarmes');
            await page.waitForLoadState('domcontentloaded');

            const url = page.url();
            const content = await page.content();
            const denied = url.includes('/auth/deny') || content.includes('Accès non autorisé');
            expect(denied).toBeTruthy();

            console.log('✓ asterix denied access to alarmes');
        });

        test('asterix cannot access terrains/page (requires ca)', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('terrains/page');
            await page.waitForLoadState('domcontentloaded');

            const url = page.url();
            const content = await page.content();
            const denied = url.includes('/auth/deny') || content.includes('Accès non autorisé');
            expect(denied).toBeTruthy();

            console.log('✓ asterix denied access to terrains/page');
        });

        test('asterix cannot access rapports/ffvv (requires ca)', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('rapports/ffvv');
            await page.waitForLoadState('domcontentloaded');

            const url = page.url();
            const content = await page.content();
            const denied = url.includes('/auth/deny') || content.includes('Accès non autorisé');
            expect(denied).toBeTruthy();

            console.log('✓ asterix denied access to rapports/ffvv');
        });

        test('asterix cannot access welcome/compta (requires tresorier)', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('welcome/compta');
            await page.waitForLoadState('domcontentloaded');
            await page.waitForTimeout(2000);

            const url = page.url();
            const content = await page.content();
            console.log(`welcome/compta final URL: ${url}`);
            const denied = url.includes('/auth/deny') || url.includes('/auth/login')
                || content.includes('Accès non autorisé') || content.includes('Mot de passe');
            expect(denied).toBeTruthy();

            console.log('✓ asterix denied access to welcome/compta');
        });

        test('asterix cannot access welcome/ca (requires ca)', async ({ page }) => {
            const loginPage = new LoginPage(page);
            await loginPage.open();
            await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);

            await loginPage.goto('welcome/ca');
            await page.waitForLoadState('domcontentloaded');
            await page.waitForTimeout(2000);

            const url = page.url();
            const content = await page.content();
            console.log(`welcome/ca final URL: ${url}`);
            const denied = url.includes('/auth/deny') || url.includes('/auth/login')
                || content.includes('Accès non autorisé') || content.includes('Mot de passe');
            expect(denied).toBeTruthy();

            console.log('✓ asterix denied access to welcome/ca');
        });
    });
});
