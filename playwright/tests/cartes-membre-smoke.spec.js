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
        expect(bodyText).not.toContain('404 Not Found');
        expect(bodyText).not.toContain('PHP Error was encountered');

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
        expect(bodyText).not.toContain('404 Not Found');
        expect(bodyText).not.toContain('Error');

        await expect(page.locator('h4')).toContainText('onfiguration');
    });

    test('should display year selector and upload forms on config page', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/config');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('select[name="year"]')).toBeVisible();
        // Two upload forms (recto + verso) + one JSON import in modal = 3 total
        const uploadInputs = page.locator('input[type="file"]');
        await expect(uploadInputs).toHaveCount(3);
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
                expect(bodyText).not.toContain('404 Not Found');
            }
        }
    });

    // -----------------------------------------------------------------------
    // Lot 2 — Layout configuration UI (merged into config page)
    // -----------------------------------------------------------------------

    test('should display recto and verso layout tabs on config page', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/config');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('#recto-tab')).toBeVisible();
        await expect(page.locator('#verso-tab')).toBeVisible();
    });

    test('should display variable fields table on config recto tab', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/config');
        await page.waitForLoadState('networkidle');

        // Recto tab is active by default — at least 6 variable fields
        const rows = page.locator('#tab-recto table tbody tr');
        expect(await rows.count()).toBeGreaterThanOrEqual(6);
    });

    test('should show export and import buttons on config page', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/config');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('a[href*="layout_export"]')).toBeVisible();
        await expect(page.locator('button[data-bs-target="#importModal"]')).toBeVisible();
    });

    test('should save layout and show confirmation', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/config');
        await page.waitForLoadState('networkidle');

        // Submit the layout save form by button text (upload buttons also have btn-primary)
        await page.click('button[type="submit"]:has-text("Enregistrer la mise en page")');
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        expect(bodyText).not.toContain('Error');
        expect(bodyText).not.toContain('404 Not Found');
        const alert = page.locator('.alert-success');
        await expect(alert).toBeVisible();
    });

    // -----------------------------------------------------------------------
    // Lot 3 — Cartes individuelles (admin access)
    // -----------------------------------------------------------------------

    test('Lot3 admin: should access individual card page and see member selector', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/carte');
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        expect(bodyText).not.toContain('404 Not Found');
        expect(bodyText).not.toContain('Error');

        // Admin sees either a member selector dropdown or the year selector form
        const memberSelect = page.locator('select[name="mlogin"]');
        const yearSelect   = page.locator('select[name="year"], select#sel_year');
        const hasMemberSel = await memberSelect.count() > 0;
        const hasYearSel   = await yearSelect.count() > 0;
        expect(hasMemberSel || hasYearSel).toBeTruthy();
    });

    test('Lot3 admin: should generate individual card PDF for a member', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/carte');
        await page.waitForLoadState('networkidle');

        // Try to pick the first member from the dropdown
        const memberSelect = page.locator('select[name="mlogin"]');
        if (await memberSelect.count() === 0) {
            test.skip();
            return;
        }

        // Select first non-empty option
        const options = await memberSelect.locator('option').all();
        let targetLogin = null;
        for (const opt of options) {
            const val = await opt.getAttribute('value');
            if (val) { targetLogin = val; break; }
        }
        if (!targetLogin) { test.skip(); return; }

        await memberSelect.selectOption(targetLogin);

        // Select a year (first option)
        const yearSelect = page.locator('select[name="year"]');
        const firstYear = await yearSelect.locator('option').first().getAttribute('value');

        if (firstYear) {
            await yearSelect.selectOption(firstYear);
        }

        // Submit the form
        const submitBtn = page.locator('button[type="submit"]');
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 15000 }).catch(() => null),
            submitBtn.click()
        ]);

        // PDF delivered inline or downloaded — verify no error page
        if (!download) {
            const bodyText = await page.locator('body').textContent().catch(() => '');
            if (bodyText) {
                expect(bodyText).not.toContain('Error');
                expect(bodyText).not.toContain('404 Not Found');
            }
        }
    });
});

// -----------------------------------------------------------------------
// Lot 3 — Cartes individuelles (member access)
// -----------------------------------------------------------------------

const MEMBER_USER = { username: 'testuser', password: 'password' };

test.describe('Cartes de membre Lot3 — accès membre', () => {

    test.beforeEach(async ({ page }) => {
        await page.goto(LOGIN_URL);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('input[name="username"]', { timeout: 5000 });
        await page.fill('input[name="username"]', MEMBER_USER.username);
        await page.fill('input[name="password"]', MEMBER_USER.password);
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');
    });

    test('Lot3 member: should access own card page', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/carte');
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        expect(bodyText).not.toContain('404 Not Found');
        expect(bodyText).not.toContain('Error');

        // Member sees either a year selector or a "no cotisation" message
        const yearSelect    = page.locator('select#sel_year');
        const noCotisAlert  = page.locator('.alert-warning');
        const hasYear  = await yearSelect.count() > 0;
        const hasAlert = await noCotisAlert.count() > 0;
        expect(hasYear || hasAlert).toBeTruthy();
    });

    test('Lot3 member: should not be able to access admin lot page', async ({ page }) => {
        await page.goto('/index.php/cartes_membre/lot');
        await page.waitForLoadState('networkidle');

        // Should be denied or redirected (not show the lot form)
        const url = page.url();
        const bodyText = await page.locator('body').textContent();
        const hasLotForm = await page.locator('button[name="generate"]').count() > 0;
        expect(hasLotForm).toBeFalsy();
    });

    test('Lot3 member: dashboard shows my member card link', async ({ page }) => {
        await page.goto('/index.php/welcome');
        await page.waitForLoadState('networkidle');

        const cardLink = page.locator('a[href*="cartes_membre/carte"]');
        await expect(cardLink).toBeVisible();
    });
});
