/**
 * Playwright test for Rapprochements Export Buttons
 *
 * Tests:
 * - Export buttons (Excel/PDF) are visible in the "Ecritures GVV" tab
 * - Buttons link to correct export URLs
 *
 * Usage:
 *   npx playwright test tests/rapprochements-export.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');
const RapprochementsPage = require('./helpers/RapprochementsPage');

// Test configuration
const LOGIN_URL = '/auth/login';
const TEST_USER = {
  username: 'testadmin',
  correctPassword: 'password'
};

test.describe('Rapprochements Export Buttons', () => {

  test('should display export buttons in Ecritures GVV tab', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    // Click on "Ecritures GVV" tab
    console.log('Clicking on Ecritures GVV tab...');
    await rapprochementsPage.clickTab('gvv-tab');

    // Wait for tab content to be visible
    await page.waitForSelector('#gvv.show', { timeout: 5000 });

    // Check if export buttons exist
    console.log('Checking for export buttons...');

    // Check for Excel button
    const excelButton = await page.locator('a[href*="export_ecritures/csv"]').first();
    await expect(excelButton).toBeVisible();
    const excelText = await excelButton.textContent();
    console.log('Excel button text:', excelText.trim());
    expect(excelText).toContain('Excel');

    // Check for PDF button
    const pdfButton = await page.locator('a[href*="export_ecritures/pdf"]').first();
    await expect(pdfButton).toBeVisible();
    const pdfText = await pdfButton.textContent();
    console.log('PDF button text:', pdfText.trim());
    expect(pdfText).toContain('Pdf');

    // Verify button URLs
    const excelHref = await excelButton.getAttribute('href');
    const pdfHref = await pdfButton.getAttribute('href');

    console.log('Excel button href:', excelHref);
    console.log('PDF button href:', pdfHref);

    expect(excelHref).toContain('rapprochements/export_ecritures/csv');
    expect(pdfHref).toContain('rapprochements/export_ecritures/pdf');

    // Take screenshot for verification
    await page.screenshot({ path: 'playwright/screenshots/ecritures_gvv_export_buttons.png', fullPage: true });
    console.log('Screenshot saved');
  });

  test('should verify Excel button is clickable', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    // Click on "Ecritures GVV" tab
    await rapprochementsPage.clickTab('gvv-tab');
    await page.waitForSelector('#gvv.show', { timeout: 5000 });

    // Verify Excel button is clickable (we won't actually click it to avoid download)
    const excelButton = await page.locator('a[href*="export_ecritures/csv"]').first();
    await expect(excelButton).toBeVisible();
    await expect(excelButton).toBeEnabled();

    console.log('✓ Excel button is clickable');
  });

  test('should verify PDF button is clickable', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    // Click on "Ecritures GVV" tab
    await rapprochementsPage.clickTab('gvv-tab');
    await page.waitForSelector('#gvv.show', { timeout: 5000 });

    // Verify PDF button is clickable (we won't actually click it to avoid download)
    const pdfButton = await page.locator('a[href*="export_ecritures/pdf"]').first();
    await expect(pdfButton).toBeVisible();
    await expect(pdfButton).toBeEnabled();

    console.log('✓ PDF button is clickable');
  });

});
