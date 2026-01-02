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

/**
 * Verify table has correct number of columns
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} tableId - Table ID to verify
 * @param {number} expectedColumns - Expected number of columns
 * @param {number} sectionCount - Number of sections for logging
 */
async function verifyTableColumns(page, tableId, expectedColumns, sectionCount) {
  console.log(`\nVerifying table: ${tableId}`);

  // Locate the table
  const table = page.locator(`table#${tableId}`);
  await expect(table).toBeVisible();
  console.log(`✓ Found ${tableId}`);

  // Count columns in the header row
  const headerCells = table.locator('tr').first().locator('th, td');
  const headerColumnCount = await headerCells.count();
  console.log(`Found ${headerColumnCount} columns in header row`);

  // Verify: number of header columns = number of sections + 3
  expect(headerColumnCount).toBe(expectedColumns);
  console.log(`✓ Header column count is correct: ${headerColumnCount} = ${sectionCount} sections + 3`);

  // Count columns in a data row (if any exist)
  const dataRows = table.locator('tbody tr, tr:not(:first-child)');
  const dataRowCount = await dataRows.count();

  if (dataRowCount > 0) {
    console.log(`Found ${dataRowCount} data row(s), verifying column count...`);

    // Check first data row
    const firstDataRow = dataRows.first();
    const dataCells = firstDataRow.locator('td, th');
    const dataColumnCount = await dataCells.count();

    console.log(`Found ${dataColumnCount} columns in first data row`);
    expect(dataColumnCount).toBe(expectedColumns);
    console.log(`✓ Data row column count is correct: ${dataColumnCount} = ${sectionCount} sections + 3`);

    // Verify all data rows have the same number of columns
    for (let i = 0; i < Math.min(dataRowCount, 5); i++) { // Check first 5 rows max
      const row = dataRows.nth(i);
      const cells = row.locator('td, th');
      const cellCount = await cells.count();

      expect(cellCount).toBe(expectedColumns);
      console.log(`  ✓ Row ${i + 1}: ${cellCount} columns`);
    }

    if (dataRowCount > 5) {
      console.log(`  ... and ${dataRowCount - 5} more rows (not checked individually)`);
    }
  } else {
    console.log('⚠ No data rows found in table');
  }

  console.log(`✅ ${tableId} column validation completed`);
}

/**
 * Login, navigate to about page, and count cloture_table data rows
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<number>} Number of data rows (excluding header)
 */
async function login_and_section_number(page) {
  // Step 1: Login as admin
  console.log('Logging in as admin...');
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
  console.log('Navigating to /welcome/about...');
  await page.goto('/index.php/welcome/about');
  await page.waitForLoadState('domcontentloaded');

  // Step 3: Locate the cloture_table and count rows
  console.log('Locating cloture_table...');
  const clotureTable = page.locator('table#cloture_table');
  await expect(clotureTable).toBeVisible();
  console.log('✓ Successfully navigated to about page and found cloture_table');

  // Count all rows
  const allRows = await clotureTable.locator('tr').count();

  // Return data rows count (excluding header)
  const dataRowCount = allRows - 1;
  console.log(`Found ${allRows} total rows (including header)`);
  console.log(`Data rows count: ${dataRowCount}`);

  return dataRowCount;
}

test.describe('Section Unique - Cloture Table Validation', () => {

  test('should verify cloture_table has between 0 and 5 rows', async ({ page }) => {
    // Get the number of data rows using the helper function
    const dataRowCount = await login_and_section_number(page);

    // Verify count is an integer between 0 and 5
    console.log('Validating row count...');

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
    // Use helper function to login and navigate (we'll verify structure after)
    const dataRowCount = await login_and_section_number(page);

    // Verify table structure
    const clotureTable = page.locator('table#cloture_table');

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
    console.log(`✓ Table has ${dataRowCount} data rows`);
    console.log('✅ Table structure validation completed');
  });

  test('should verify charges_table has correct number of columns', async ({ page }) => {
    // Get the number of sections (data rows in cloture_table)
    const sectionCount = await login_and_section_number(page);
    console.log(`Number of sections detected: ${sectionCount}`);

    // Navigate to /comptes/dashboard
    console.log('Navigating to /comptes/dashboard...');
    await page.goto('/index.php/comptes/dashboard');
    await page.waitForLoadState('domcontentloaded');

    // Take screenshot before opening accordions (for debugging)
    await page.screenshot({
      path: 'build/playwright-captures/dashboard-before-accordions.png',
      fullPage: true
    });

    // Open all accordions on the page
    console.log('Opening all accordions...');

    // Try multiple strategies to find and open accordions
    // Strategy 1: Bootstrap accordion buttons
    let accordionButtons = page.locator('.accordion-button[aria-expanded="false"]');
    let accordionCount = await accordionButtons.count();

    // Strategy 2: Any button with data-bs-toggle="collapse" that's collapsed
    if (accordionCount === 0) {
      accordionButtons = page.locator('button[data-bs-toggle="collapse"][aria-expanded="false"]');
      accordionCount = await accordionButtons.count();
    }

    // Strategy 3: Any clickable element with aria-expanded="false"
    if (accordionCount === 0) {
      accordionButtons = page.locator('[aria-expanded="false"][data-bs-toggle="collapse"]');
      accordionCount = await accordionButtons.count();
    }

    if (accordionCount > 0) {
      console.log(`Found ${accordionCount} collapsed accordion(s), opening them...`);
      for (let i = 0; i < accordionCount; i++) {
        try {
          const button = accordionButtons.nth(i);
          await button.scrollIntoViewIfNeeded();
          await button.click({ timeout: 5000 });
          await page.waitForTimeout(500); // Wait for accordion animation
          console.log(`  ✓ Opened accordion ${i + 1}/${accordionCount}`);
        } catch (error) {
          console.log(`  ⚠ Could not open accordion ${i + 1}: ${error.message}`);
        }
      }
      console.log('✓ All accordions processed');
    } else {
      console.log('No collapsed accordions found');
    }

    // Alternative: Force-open all collapse elements via JavaScript
    console.log('Force-opening all Bootstrap collapse elements...');
    await page.evaluate(() => {
      // Find all collapse elements and show them
      const collapseElements = document.querySelectorAll('.collapse:not(.show)');
      collapseElements.forEach(element => {
        element.classList.add('show');
      });

      // Update aria-expanded attributes
      const expandButtons = document.querySelectorAll('[aria-expanded="false"][data-bs-toggle="collapse"]');
      expandButtons.forEach(button => {
        button.setAttribute('aria-expanded', 'true');
      });
    });

    // Wait a bit for all animations to complete
    await page.waitForTimeout(1000);

    // Take screenshot after opening accordions (for debugging)
    await page.screenshot({
      path: 'build/playwright-captures/dashboard-after-accordions.png',
      fullPage: true
    });

    // Verify both tables have correct number of columns
    const expectedColumns = sectionCount + 3;

    // Verify charges_table
    await verifyTableColumns(page, 'charges_table', expectedColumns, sectionCount);

    // Verify produits_table
    await verifyTableColumns(page, 'produits_table', expectedColumns, sectionCount);

    console.log('\n✅ All dashboard table validations completed');
  });

});
