/**
 * Resultat Par Sections Test
 *
 * This test verifies the tables on the /comptes/resultat_par_sections page
 * adapt their column count based on the number of sections.
 *
 * Tests:
 * - Login as admin
 * - Navigate to /comptes/resultat_par_sections page
 * - Count rows in the cloture_table to determine number of sections
 * - Verify column counts for charges, produits, and total tables
 *
 * Column count rules:
 * If nb_sections > 1:
 *   - charges/produits: (nb_sections + 1) * 2 + 2
 *   - total: (nb_sections + 1) * 2 + 1
 * If nb_sections <= 1:
 *   - charges/produits: 4
 *   - total: 3
 *
 * Usage:
 *   npx playwright test tests/resultat-par-sections.spec.js
 *   BASE_URL=https://example.com npx playwright test tests/resultat-par-sections.spec.js
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

  // Count columns in the second header row (first row has colspan, second has actual columns)
  const headerRows = table.locator('thead tr');
  const headerRowCount = await headerRows.count();
  console.log(`Found ${headerRowCount} header rows`);

  // Verify we have 2 header rows
  expect(headerRowCount).toBe(2);

  // Count columns in the second header row (the one with years)
  // This row shows the actual column structure (2026, 2025, 2026, 2025, ...)
  const secondHeaderRow = headerRows.nth(1);
  const yearCells = secondHeaderRow.locator('th');
  const yearColumnCount = await yearCells.count();
  
  // Also count the label columns from the first row (Code, Comptes or just one for Total table)
  const firstHeaderRow = headerRows.nth(0);
  const labelCells = firstHeaderRow.locator('th[rowspan="2"]');
  const labelColumnCount = await labelCells.count();
  
  // Total columns = label columns + year columns
  const headerColumnCount = labelColumnCount + yearColumnCount;
  
  console.log(`Found ${labelColumnCount} label column(s) with rowspan=2`);
  console.log(`Found ${yearColumnCount} year columns in second header row`);
  console.log(`Total columns: ${labelColumnCount} + ${yearColumnCount} = ${headerColumnCount}`);

  // Verify: number of header columns = expectedColumns
  expect(headerColumnCount).toBe(expectedColumns);
  console.log(`✓ Header column count is correct: ${headerColumnCount} (expected ${expectedColumns})`);

  // Count columns in a data row (if any exist)
  const dataRows = table.locator('tbody tr');
  const dataRowCount = await dataRows.count();

  if (dataRowCount > 0) {
    console.log(`Found ${dataRowCount} data row(s), verifying column count...`);

    // Check first data row
    const firstDataRow = dataRows.first();
    const dataCells = firstDataRow.locator('td, th');
    const dataColumnCount = await dataCells.count();

    console.log(`Found ${dataColumnCount} columns in first data row`);
    expect(dataColumnCount).toBe(expectedColumns);
    console.log(`✓ Data row column count is correct: ${dataColumnCount} (expected ${expectedColumns})`);

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
async function login_and_get_section_count(page) {
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
  console.log(`Data rows count (sections): ${dataRowCount}`);

  return dataRowCount;
}

test.describe('Resultat Par Sections - Column Count Validation', () => {

  test('should verify charges and produits tables have correct number of columns', async ({ page }) => {
    // Get the number of sections (data rows in cloture_table)
    const sectionCount = await login_and_get_section_count(page);
    console.log(`Number of sections detected: ${sectionCount}`);

    // Navigate to /comptes/resultat_par_sections
    console.log('Navigating to /comptes/resultat_par_sections...');
    await page.goto('/index.php/comptes/resultat_par_sections');
    await page.waitForLoadState('domcontentloaded');

    // Wait for tables to load
    await page.waitForTimeout(1000);

    // Take screenshot for debugging
    await page.screenshot({
      path: 'build/playwright-captures/resultat-par-sections-tables.png',
      fullPage: true
    });

    // Calculate expected number of columns based on section count
    // If sectionCount > 1: expectedColumns = (sectionCount + 1) * 2 + 2
    // If sectionCount <= 1: expectedColumns = 4
    const chargesProduitsExpectedColumns = sectionCount > 1 
      ? (sectionCount + 1) * 2 + 2 
      : 4;

    console.log(`\nCalculating expected columns for charges and produits tables:`);
    console.log(`  Section count: ${sectionCount}`);
    console.log(`  Expected columns: ${chargesProduitsExpectedColumns}`);
    if (sectionCount > 1) {
      console.log(`  Formula: (${sectionCount} + 1) * 2 + 2 = ${chargesProduitsExpectedColumns}`);
    } else {
      console.log(`  Formula: Fixed value = 4`);
    }

    // Verify charges_table
    await verifyTableColumns(page, 'resultat_par_sections_charges_table', chargesProduitsExpectedColumns, sectionCount);

    // Verify produits_table
    await verifyTableColumns(page, 'resultat_par_sections_produits_table', chargesProduitsExpectedColumns, sectionCount);

    console.log('\n✅ Charges and produits table validations completed');
  });

  test('should verify total table has correct number of columns', async ({ page }) => {
    // Get the number of sections (data rows in cloture_table)
    const sectionCount = await login_and_get_section_count(page);
    console.log(`Number of sections detected: ${sectionCount}`);

    // Navigate to /comptes/resultat_par_sections
    console.log('Navigating to /comptes/resultat_par_sections...');
    await page.goto('/index.php/comptes/resultat_par_sections');
    await page.waitForLoadState('domcontentloaded');

    // Wait for tables to load
    await page.waitForTimeout(1000);

    // Calculate expected number of columns based on section count
    // If sectionCount > 1: expectedColumns = (sectionCount + 1) * 2 + 1
    // If sectionCount <= 1: expectedColumns = 3
    const totalExpectedColumns = sectionCount > 1 
      ? (sectionCount + 1) * 2 + 1 
      : 3;

    console.log(`\nCalculating expected columns for total table:`);
    console.log(`  Section count: ${sectionCount}`);
    console.log(`  Expected columns: ${totalExpectedColumns}`);
    if (sectionCount > 1) {
      console.log(`  Formula: (${sectionCount} + 1) * 2 + 1 = ${totalExpectedColumns}`);
    } else {
      console.log(`  Formula: Fixed value = 3`);
    }

    // Verify total table
    await verifyTableColumns(page, 'resultat_par_sections_total_table', totalExpectedColumns, sectionCount);

    console.log('\n✅ Total table validation completed');
  });

});
