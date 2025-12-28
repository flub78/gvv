/**
 * Accounting (Compta) Tests - Migrated from Dusk to Playwright
 *
 * Original Dusk test: /home/frederic/git/dusk_gvv/tests/Browser/ComptaTest.php
 *
 * Tests:
 * - Verify account and codec counts across different sections
 * - Ensure codec count >= account count for each section
 * - Verify ALL section has counts >= individual sections
 *
 * Usage:
 *   npx playwright test tests/migrated/compta.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('../helpers/LoginPage');

// Test configuration
const TEST_USER = process.env.TEST_USER || 'testadmin';
const TEST_PASSWORD = process.env.TEST_PASSWORD || 'password';

// Section constants (matching Dusk test)
const PLANEUR = '1';
const ULM = '2';
const AVION = '3';
const GENERAL = '4';
const ALL = '5';

/**
 * Helper function to get total count from DataTables or visible rows
 */
async function getTotalCount(page) {
  await page.waitForSelector('table', { timeout: 10000 });
  await page.waitForTimeout(500);

  // Look for DataTables info text
  const infoText = await page.locator('.dataTables_info, div[id$="_info"]').textContent().catch(() => null);

  if (infoText) {
    const match = infoText.match(/sur\s+(\d+)/i);
    if (match) {
      return parseInt(match[1], 10);
    }
  }

  // Fallback: count visible rows
  const rows = await page.locator('table tbody tr').count();
  return rows;
}

/**
 * Helper function to switch section
 */
async function switchSection(page, loginPage, section) {
  const sectionSelect = page.locator('select[name="section"]');
  await sectionSelect.selectOption(section);
  await page.waitForTimeout(1000);
  await loginPage.screenshot(`switched_to_section_${section}`);
  console.log(`Switched to section ${section}`);
}

test.describe('GVV Accounting (Compta) Tests (Migrated from Dusk)', () => {

  test('should verify account and codec counts across sections', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Login with Planeur section
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD, PLANEUR);
    console.log('✓ Logged in with Planeur section');

    // ===== PLANEUR SECTION =====
    console.log('\n--- PLANEUR SECTION ---');

    // Navigate to comptes/general (codecs)
    await loginPage.goto('comptes/general');
    await page.waitForLoadState('domcontentloaded');
    const planeur_codec_count = await getTotalCount(page);
    console.log(`Planeur codec count: ${planeur_codec_count}`);
    expect(planeur_codec_count).toBeGreaterThanOrEqual(16);

    // Navigate to comptes/page (accounts)
    await loginPage.goto('comptes/page');
    await page.waitForLoadState('domcontentloaded');
    const planeur_account_count = await getTotalCount(page);
    console.log(`Planeur account count: ${planeur_account_count}`);

    // Verify codec_count >= account_count
    expect(planeur_codec_count).toBeGreaterThanOrEqual(planeur_account_count);
    console.log('✓ Planeur: codec_count >= account_count');

    // ===== ULM SECTION =====
    console.log('\n--- ULM SECTION ---');
    await switchSection(page, loginPage, ULM);

    // Navigate to comptes/general (codecs)
    await loginPage.goto('comptes/general');
    const ulm_codec_count = await getTotalCount(page);
    console.log(`ULM codec count: ${ulm_codec_count}`);
    expect(ulm_codec_count).toBeGreaterThanOrEqual(25);

    // Navigate to comptes/page (accounts)
    await loginPage.goto('comptes/page');
    const ulm_account_count = await getTotalCount(page);
    console.log(`ULM account count: ${ulm_account_count}`);

    // Verify codec_count >= account_count
    expect(ulm_codec_count).toBeGreaterThanOrEqual(ulm_account_count);
    console.log('✓ ULM: codec_count >= account_count');

    // ===== AVION SECTION =====
    console.log('\n--- AVION SECTION ---');
    await switchSection(page, loginPage, AVION);

    // Navigate to comptes/general (codecs)
    await loginPage.goto('comptes/general');
    const avion_codec_count = await getTotalCount(page);
    console.log(`Avion codec count: ${avion_codec_count}`);
    expect(avion_codec_count).toBeGreaterThanOrEqual(16);

    // Navigate to comptes/page (accounts)
    await loginPage.goto('comptes/page');
    const avion_account_count = await getTotalCount(page);
    console.log(`Avion account count: ${avion_account_count}`);

    // Verify codec_count >= account_count
    expect(avion_codec_count).toBeGreaterThanOrEqual(avion_account_count);
    console.log('✓ Avion: codec_count >= account_count');

    // ===== GENERAL SECTION =====
    console.log('\n--- GENERAL SECTION ---');
    await switchSection(page, loginPage, GENERAL);

    // Navigate to comptes/general (codecs)
    await loginPage.goto('comptes/general');
    const general_codec_count = await getTotalCount(page);
    console.log(`General codec count: ${general_codec_count}`);
    expect(general_codec_count).toBeGreaterThanOrEqual(16);

    // Navigate to comptes/page (accounts)
    await loginPage.goto('comptes/page');
    const general_account_count = await getTotalCount(page);
    console.log(`General account count: ${general_account_count}`);

    // Verify codec_count >= account_count
    expect(general_codec_count).toBeGreaterThanOrEqual(general_account_count);
    console.log('✓ General: codec_count >= account_count');

    // ===== ALL SECTION =====
    console.log('\n--- ALL SECTION ---');
    await switchSection(page, loginPage, ALL);

    // Navigate to comptes/general (codecs)
    await loginPage.goto('comptes/general');
    const all_codec_count = await getTotalCount(page);
    console.log(`ALL codec count: ${all_codec_count}`);
    expect(all_codec_count).toBeGreaterThanOrEqual(16);

    // Navigate to comptes/page (accounts)
    await loginPage.goto('comptes/page');
    const all_account_count = await getTotalCount(page);
    console.log(`ALL account count: ${all_account_count}`);

    // Verify codec_count >= account_count
    expect(all_codec_count).toBeGreaterThanOrEqual(all_account_count);
    console.log('✓ ALL: codec_count >= account_count');

    // ===== VERIFY ALL >= INDIVIDUAL SECTIONS =====
    console.log('\n--- VERIFICATION: ALL >= INDIVIDUAL SECTIONS ---');
    expect(all_codec_count).toBeGreaterThanOrEqual(planeur_codec_count);
    expect(all_codec_count).toBeGreaterThanOrEqual(ulm_codec_count);
    expect(all_codec_count).toBeGreaterThanOrEqual(avion_codec_count);
    expect(all_codec_count).toBeGreaterThanOrEqual(general_codec_count);
    console.log('✓ ALL codec count >= all individual sections');

    expect(all_account_count).toBeGreaterThanOrEqual(planeur_account_count);
    expect(all_account_count).toBeGreaterThanOrEqual(ulm_account_count);
    expect(all_account_count).toBeGreaterThanOrEqual(avion_account_count);
    expect(all_account_count).toBeGreaterThanOrEqual(general_account_count);
    console.log('✓ ALL account count >= all individual sections');

    // Summary
    console.log('\n=== SUMMARY ===');
    console.log(`Planeur: ${planeur_codec_count} codecs, ${planeur_account_count} accounts`);
    console.log(`ULM: ${ulm_codec_count} codecs, ${ulm_account_count} accounts`);
    console.log(`Avion: ${avion_codec_count} codecs, ${avion_account_count} accounts`);
    console.log(`General: ${general_codec_count} codecs, ${general_account_count} accounts`);
    console.log(`ALL: ${all_codec_count} codecs, ${all_account_count} accounts`);

    await loginPage.logout();
    console.log('\n✓ Test completed successfully');
  });

  test('should verify codec page is accessible', async ({ page }) => {
    const loginPage = new LoginPage(page);

    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD, PLANEUR);

    // Navigate to comptes/general
    await loginPage.goto('comptes/general');

    // Verify page elements
    await loginPage.assertText('Balance générale des comptes');

    // Verify table exists
    const tableExists = await page.locator('table').count() > 0;
    expect(tableExists).toBeTruthy();
    console.log('✓ Codec page is accessible and contains table');

    await loginPage.logout();
  });

  test('should verify accounts page is accessible', async ({ page }) => {
    const loginPage = new LoginPage(page);

    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD, PLANEUR);

    // Navigate to comptes/page
    await loginPage.goto('comptes/page');

    // Verify page elements
    await loginPage.assertText('Comptes');

    // Verify table exists
    const tableExists = await page.locator('table').count() > 0;
    expect(tableExists).toBeTruthy();
    console.log('✓ Accounts page is accessible and contains table');

    await loginPage.logout();
  });

});
