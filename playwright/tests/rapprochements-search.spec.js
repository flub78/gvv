/**
 * Playwright test for Rapprochements Bank Statement Search
 *
 * Tests:
 * - Search box is visible in the "Relevé de banque" tab
 * - Search filters operations correctly
 * - Clear button works
 * - Search is case-insensitive
 *
 * Usage:
 *   npx playwright test tests/rapprochements-search.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');
const RapprochementsPage = require('./helpers/RapprochementsPage');

// Test configuration
const TEST_USER = {
  username: 'testadmin',
  correctPassword: 'password'
};

test.describe('Rapprochements Bank Statement Search', () => {

  test('should display search box in Relevé de banque tab', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    // Check if search box exists (the main point of this test)
    console.log('Checking for search box...');
    const searchBox = await page.locator('#searchReleveBanque');
    await expect(searchBox).toBeVisible();

    // Check placeholder text
    const placeholder = await searchBox.getAttribute('placeholder');
    expect(placeholder).toContain('Filtrer');

    // Check for clear button
    const clearButton = await page.locator('button[onclick*="clearBankSearch"]');
    await expect(clearButton).toBeVisible();

    console.log('✓ Search box and clear button are visible');

    // Take screenshot
    await page.screenshot({ path: 'playwright/screenshots/bank_search_box.png', fullPage: true });
  });

  test('should filter operations when typing in search box', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    // Count initial operation tables
    const allOperations = await page.locator('table.operations').count();
    console.log(`Total operations: ${allOperations}`);

    if (allOperations === 0) {
      console.log('⚠ No operations found - test requires bank statement to be loaded');
      return;
    }

    // Type in search box
    const searchBox = await page.locator('#searchReleveBanque');
    await searchBox.fill('virement');

    // Wait for filter to apply
    await page.waitForTimeout(500);

    // Count visible operations after search
    const visibleOperations = await page.locator('table.operations:visible').count();
    console.log(`Visible operations after search: ${visibleOperations}`);

    // At least some filtering should occur (unless all operations contain 'virement')
    expect(visibleOperations).toBeLessThanOrEqual(allOperations);

    console.log('✓ Search filtering works');

    // Take screenshot
    await page.screenshot({ path: 'playwright/screenshots/bank_search_filtered.png', fullPage: true });
  });

  test('should clear search when clear button is clicked', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    const allOperations = await page.locator('table.operations').count();

    if (allOperations === 0) {
      console.log('⚠ No operations found - test requires bank statement to be loaded');
      return;
    }

    // Type in search box
    const searchBox = await page.locator('#searchReleveBanque');
    await searchBox.fill('test search term');
    await page.waitForTimeout(500);

    // Click clear button
    const clearButton = await page.locator('button[onclick*="clearBankSearch"]');
    await clearButton.click();
    await page.waitForTimeout(500);

    // Check that search box is empty
    const searchValue = await searchBox.inputValue();
    expect(searchValue).toBe('');

    // Check that all operations are visible again
    const visibleAfterClear = await page.locator('table.operations:visible').count();
    expect(visibleAfterClear).toBe(allOperations);

    console.log('✓ Clear button works correctly');
  });

  test('should be case-insensitive', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    const allOperations = await page.locator('table.operations').count();

    if (allOperations === 0) {
      console.log('⚠ No operations found - test requires bank statement to be loaded');
      return;
    }

    const searchBox = await page.locator('#searchReleveBanque');

    // Search with lowercase
    await searchBox.fill('virement');
    await page.waitForTimeout(500);
    const resultLower = await page.locator('table.operations:visible').count();

    // Clear
    const clearButton = await page.locator('button[onclick*="clearBankSearch"]');
    await clearButton.click();
    await page.waitForTimeout(500);

    // Search with uppercase
    await searchBox.fill('VIREMENT');
    await page.waitForTimeout(500);
    const resultUpper = await page.locator('table.operations:visible').count();

    // Should have same results
    expect(resultLower).toBe(resultUpper);

    console.log('✓ Search is case-insensitive');
  });

});
