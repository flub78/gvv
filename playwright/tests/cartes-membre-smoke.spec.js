// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Smoke tests — Impression des cartes de membre (Lot 1)
 *
 * Vérifie :
 * - Accès à la page de sélection du lot (cartes_membre/lot)
 * - Présence du formulaire de sélection membres
 * - Accès à la page de configuration des fonds (cartes_membre/config)
 * - Redirection vers login pour les non-connectés
 */

const LOGIN_URL = '/index.php/auth/login';
const TEST_USER = { username: 'testadmin', password: 'password' };

test.describe('Cartes de membre — smoke tests', () => {

    test.beforeEach(async ({ page }) => {
        await page.goto(LOGIN_URL);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('input[name="username"]', { timeout: 5000 });
        await page.fill('input[name="username"]', TEST_USER.username);
        await page.fill('input[name="password"]', TEST_USER.password);
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');
    });

    test('should access lot selection page', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/lot');
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        expect(bodyText).not.toContain('404');
        expect(bodyText).not.toContain('Error');

        // The page title (h4) should contain the card generation label
        await expect(page.locator('h4')).toContainText('cartes de membre');
    });

    test('should display year selector on lot page', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/lot');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('select[name="year"]')).toBeVisible();
    });

    test('should display generate button when members exist', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/lot');
        await page.waitForLoadState('networkidle');

        // Either the generate button or the "no members" alert should be present
        const generateBtn = page.locator('button[name="generate"]');
        const noMembersAlert = page.locator('.alert-warning');

        const hasBtn   = await generateBtn.count()      > 0;
        const hasAlert = await noMembersAlert.count() > 0;

        expect(hasBtn || hasAlert).toBeTruthy();
    });

    test('should display member table with checkboxes when members exist', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/lot');
        await page.waitForLoadState('networkidle');

        const tableExists = await page.locator('table').count() > 0;
        if (tableExists) {
            await expect(page.locator('table')).toBeVisible();
            const checkboxes = page.locator('input[type="checkbox"].membre-cb');
            const count = await checkboxes.count();
            expect(count).toBeGreaterThan(0);
        }
    });

    test('should access config page', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/config');
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        expect(bodyText).not.toContain('404');
        expect(bodyText).not.toContain('Error');

        await expect(page.locator('h4')).toContainText('onfiguration');
    });

    test('should display year selector and upload forms on config page', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/config');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('select[name="year"]')).toBeVisible();
        // Two upload forms (recto + verso)
        const uploadInputs = page.locator('input[type="file"]');
        await expect(uploadInputs).toHaveCount(2);
    });

    test('should show lot page link from config page', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/config');
        await page.waitForLoadState('networkidle');

        // Use the btn class to target only the page-body link, not the nav dropdown item
        const lotLink = page.locator('a.btn[href*="cartes_membre/lot"]');
        await expect(lotLink).toBeVisible();
    });

    test('should redirect unauthenticated users to login', async ({ page: anonPage }) => {
        // Use a fresh context with no session
        await anonPage.context().clearCookies();
        await anonPage.goto('/index.php/cartes_membre/lot');
        await anonPage.waitForLoadState('networkidle');

        const url = anonPage.url();
        expect(url).toContain('auth/login');
    });

    test('should generate PDF for batch via POST', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/lot');
        await page.waitForLoadState('networkidle');

        // Only run if there are members to generate cards for
        const generateBtn = page.locator('button[name="generate"]');
        if (await generateBtn.count() === 0) {
            test.skip();
            return;
        }

        // Listen for the PDF download (Output 'I' sends inline PDF)
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 15000 }).catch(() => null),
            generateBtn.click()
        ]);

        // If no download event, the PDF was opened inline — verify we did not land on an error page
        if (!download) {
            const bodyText = await page.locator('body').textContent().catch(() => '');
            // PDF renders natively in some browsers without a download event
            // Acceptable outcomes: empty body (PDF rendered) or no error text
            if (bodyText) {
                expect(bodyText).not.toContain('Error');
                expect(bodyText).not.toContain('404');
            }
        }
    });
});
