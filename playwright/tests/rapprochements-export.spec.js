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

// Test configuration
const LOGIN_URL = '/auth/login';
const RAPPROCHEMENTS_URL = `/rapprochements/import_releve_from_file`;
const TEST_USER = {
  username: 'testadmin',
  correctPassword: 'password'
};

test.describe('Rapprochements Export Buttons', () => {

  test('should display export buttons in Ecritures GVV tab', async ({ page }) => {
    // Login first
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.correctPassword);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Navigate to rapprochements page
    console.log('Navigating to rapprochements page...');
    await page.goto(RAPPROCHEMENTS_URL);
    await page.waitForLoadState('networkidle');

    // Wait for the page to load
    await page.waitForTimeout(2000);

    // Click on "Ecritures GVV" tab
    console.log('Clicking on Ecritures GVV tab...');
    const gvvTab = await page.locator('#gvv-tab');
    await gvvTab.click();

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
    // Login first
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.correctPassword);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Navigate to rapprochements page
    await page.goto(RAPPROCHEMENTS_URL);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Click on "Ecritures GVV" tab
    const gvvTab = await page.locator('#gvv-tab');
    await gvvTab.click();
    await page.waitForSelector('#gvv.show', { timeout: 5000 });

    // Verify Excel button is clickable (we won't actually click it to avoid download)
    const excelButton = await page.locator('a[href*="export_ecritures/csv"]').first();
    await expect(excelButton).toBeVisible();
    await expect(excelButton).toBeEnabled();

    console.log('✓ Excel button is clickable');
  });

  test('should verify PDF button is clickable', async ({ page }) => {
    // Login first
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.correctPassword);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Navigate to rapprochements page
    await page.goto(RAPPROCHEMENTS_URL);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Click on "Ecritures GVV" tab
    const gvvTab = await page.locator('#gvv-tab');
    await gvvTab.click();
    await page.waitForSelector('#gvv.show', { timeout: 5000 });

    // Verify PDF button is clickable (we won't actually click it to avoid download)
    const pdfButton = await page.locator('a[href*="export_ecritures/pdf"]').first();
    await expect(pdfButton).toBeVisible();
    await expect(pdfButton).toBeEnabled();

    console.log('✓ PDF button is clickable');
  });

});
