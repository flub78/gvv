/**
 * Section Unique Test
 *
 * This test verifies the "cloture_table" (closure dates table) on the About page.
 *
 * Tests:
 * - Login as admin
 * - Navigate to /welcome/about page
 * - Count rows in the cloture_table
 * - Verify the count is an integer between 0 and 5
 *
 * The test is domain-independent using environment variable BASE_URL
 * and deployment-independent using relative URLs.
 *
 * Usage:
 *   npx playwright test tests/section-unique.spec.js
 *   BASE_URL=https://example.com npx playwright test tests/section-unique.spec.js
 */

const { test, expect } = require('@playwright/test');

// Test configuration - domain independent
const TEST_USER = 'testadmin';
const TEST_PASSWORD = 'password';

test.describe('Section Unique - Cloture Table Validation', () => {

  test('should verify cloture_table has between 0 and 5 rows', async ({ page }) => {
    // Step 1: Login as admin
    console.log('Step 1: Logging in as admin...');
    await page.goto('/index.php/auth/login');
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', TEST_USER);
    await page.fill('input[name="password"]', TEST_PASSWORD);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Handle section selection if prompted
    const currentUrl = page.url();
    if (currentUrl.includes('select_section')) {
      const firstSection = page.locator('table tbody tr').first();
      await firstSection.locator('a').first().click();
      await page.waitForLoadState('networkidle');
    }

    console.log('✓ Successfully logged in as admin');

    // Step 2: Navigate to /welcome/about
    console.log('Step 2: Navigating to /welcome/about...');
    await page.goto('/index.php/welcome/about');
    await page.waitForLoadState('domcontentloaded');

    // Step 3: Locate and verify the cloture_table is visible (confirms page loaded)
    console.log('Step 3: Locating cloture_table...');
    const clotureTable = page.locator('table#cloture_table');
    await expect(clotureTable).toBeVisible();
    console.log('✓ Successfully navigated to about page and found cloture_table');

    // Step 4: Count rows in the table (excluding header)
    console.log('Step 4: Counting rows in cloture_table...');

    // Get all <tr> elements in the table
    const allRows = await clotureTable.locator('tr').count();

    // Subtract 1 for the header row to get data rows count
    const dataRowCount = allRows - 1;

    console.log(`Found ${allRows} total rows (including header)`);
    console.log(`Data rows count: ${dataRowCount}`);

    // Step 5: Verify count is an integer between 0 and 5
    console.log('Step 5: Validating row count...');

    // Check it's an integer (should always be true from .count())
    expect(Number.isInteger(dataRowCount)).toBeTruthy();
    console.log(`✓ Row count is an integer: ${dataRowCount}`);

    // Check range: 0 to 5
    expect(dataRowCount).toBeGreaterThanOrEqual(0);
    expect(dataRowCount).toBeLessThanOrEqual(5);
    console.log(`✓ Row count is in valid range [0-5]: ${dataRowCount}`);

    // Optional: Take a screenshot for documentation
    await page.screenshot({
      path: 'build/playwright-captures/cloture-table-validation.png',
      fullPage: true
    });

    console.log('✅ Test completed successfully');
  });

  test('should verify cloture_table structure', async ({ page }) => {
    // Login
    await page.goto('/index.php/auth/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', TEST_USER);
    await page.fill('input[name="password"]', TEST_PASSWORD);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Handle section selection if prompted
    const currentUrl = page.url();
    if (currentUrl.includes('select_section')) {
      const firstSection = page.locator('table tbody tr').first();
      await firstSection.locator('a').first().click();
      await page.waitForLoadState('networkidle');
    }

    // Navigate to about page
    await page.goto('/index.php/welcome/about');
    await page.waitForLoadState('domcontentloaded');

    // Verify table structure
    const clotureTable = page.locator('table#cloture_table');
    await expect(clotureTable).toBeVisible();

    // Verify header columns exist
    const headerCells = clotureTable.locator('tr').first().locator('th');
    const headerCount = await headerCells.count();

    expect(headerCount).toBe(2);
    console.log('✓ Table has 2 header columns');

    // Verify header text contains "Section" and "Date"
    const headerTexts = await headerCells.allTextContents();
    expect(headerTexts.some(text => text.includes('Section'))).toBeTruthy();
    expect(headerTexts.some(text => text.includes('Date') || text.includes('clôture'))).toBeTruthy();

    console.log('✓ Table headers are correct:', headerTexts);
    console.log('✅ Table structure validation completed');
  });

});
