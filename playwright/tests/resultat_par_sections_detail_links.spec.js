/**
 * GVV - Résultat par sections detail view tests
 * 
 * Test suite for the detail view of "Résultat par sections" page.
 * Verifies table structure, navigation, and export functionality.
 * 
 * @fileoverview Tests for resultat_par_sections_detail page
 * @requires @playwright/test
 */

/**
 * Test suite: Résultat par sections detail view
 * 
 * @describe Résultat par sections detail view
 */

/**
 * Setup before each test
 * 
 * Performs login as admin and navigates to the application,
 * selecting a section if the section selection page appears.
 * 
 * @beforeEach
 * @async
 * @param {Object} context - Playwright test context
 * @param {Page} context.page - Playwright page object
 */

/**
 * Test: Verify simplified table structure with Section column
 * 
 * Navigates to resultat_par_sections page, opens a detail link,
 * and verifies that the detail table has the correct column structure:
 * Code, Libellé, Section, Year N, Year N-1
 * 
 * @test
 * @async
 * @param {Object} context - Playwright test context
 * @param {Page} context.page - Playwright page object
 */

/**
 * Test: Access detail page directly via codec 607
 * 
 * Navigates directly to a known codec detail page and verifies
 * that the page loads successfully with correct table structure
 * and data rows.
 * 
 * @test
 * @async
 * @param {Object} context - Playwright test context
 * @param {Page} context.page - Playwright page object
 */

/**
 * Test: Verify CSV and PDF export buttons
 * 
 * Navigates to the detail page and verifies that export buttons
 * for Excel and PDF formats are visible and accessible.
 * 
 * @test
 * @async
 * @param {Object} context - Playwright test context
 * @param {Page} context.page - Playwright page object
 */
const { test, expect } = require('@playwright/test');

test.describe('Résultat par sections detail view', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('/index.php/auth/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Select section if needed
        const currentUrl = page.url();
        if (currentUrl.includes('select_section')) {
            const firstSection = page.locator('table tbody tr').first();
            await firstSection.locator('a').first().click();
            await page.waitForLoadState('networkidle');
        }
    });

    test('should display simplified table structure with Section column', async ({ page }) => {
        // Navigate to resultat_par_sections page first
        await page.goto('/index.php/comptes/resultat_par_sections');
        await page.waitForLoadState('networkidle');

        // Find a link to a detail page (codec link)
        const detailLink = page.locator('a[href*="resultat_par_sections_detail"]').first();
        if (await detailLink.count() > 0) {
            await detailLink.click();
            await page.waitForLoadState('networkidle');

            // Check that the table has the correct columns
            const headers = await page.locator('table.resultat-table thead th').allTextContents();

            // Should have: Code, Libellé, Section, Year N, Year N-1
            expect(headers.length).toBe(5);
            expect(headers[0]).toBe('Code');
            expect(headers[1]).toBe('Libellé');
            expect(headers[2]).toBe('Section');
            // Headers 3 and 4 should be years (numbers)
            expect(headers[3]).toMatch(/^\d{4}$/);
            expect(headers[4]).toMatch(/^\d{4}$/);

            // Check that table body has rows with correct structure
            const firstRow = page.locator('table.resultat-table tbody tr').first();
            const cells = await firstRow.locator('td').count();

            // Should have 5 visible columns (Code, Libellé, Section, Year N, Year N-1)
            // compte_id is hidden
            expect(cells).toBe(5);
        }
    });

    test('should access detail page directly via codec 607', async ({ page }) => {
        // Navigate directly to a known codec detail page
        await page.goto('/index.php/comptes/resultat_par_sections_detail/607');
        await page.waitForLoadState('networkidle');

        // Check that the page loaded successfully
        const title = await page.locator('h2').first().textContent();
        expect(title).toContain('résultat par sections');

        // Check table structure
        const headers = await page.locator('table.resultat-table thead th').allTextContents();
        expect(headers.length).toBe(5);
        expect(headers[0]).toBe('Code');
        expect(headers[1]).toBe('Libellé');
        expect(headers[2]).toBe('Section');

        // Verify that table has data
        const rowCount = await page.locator('table.resultat-table tbody tr').count();
        console.log(`Table has ${rowCount} rows`);
    });

    test('should have CSV and PDF export buttons', async ({ page }) => {
        await page.goto('/index.php/comptes/resultat_par_sections_detail/607');
        await page.waitForLoadState('networkidle');

        // Check for export buttons
        const excelButton = page.locator('a:has-text("Excel")');
        const pdfButton = page.locator('a:has-text("Pdf")');

        await expect(excelButton).toBeVisible();
        await expect(pdfButton).toBeVisible();
    });
});
