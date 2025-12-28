/**
 * Sections Tests - Migrated from Dusk to Playwright
 *
 * Original Dusk test: /home/frederic/git/dusk_gvv/tests/Browser/SectionsTest.php
 *
 * Tests:
 * - User can login with a specific section
 * - User can switch between sections
 * - Resources (avions, comptes, etc.) are filtered by section
 * - Section selector updates correctly
 *
 * Note: Complex tests involving MemberHandler and AccountHandler are not migrated
 * as they require extensive business logic helpers.
 *
 * Usage:
 *   npx playwright test tests/migrated/sections.spec.js
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

test.describe('GVV Sections Tests (Migrated from Dusk)', () => {

  test('should allow user to login with section and switch between sections', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Login with Planeur section
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD, PLANEUR);

    // Verify Planeur section is active
    await loginPage.assertText('Planeur');
    console.log('✓ Logged in with Planeur section');

    // Navigate to avion/page and count planes
    await loginPage.goto('avion/page');
    await page.waitForLoadState('domcontentloaded');

    const planeCountPlaneur = await getTotalCount(page);
    console.log(`Plane count in Planeur section: ${planeCountPlaneur}`);
    expect(planeCountPlaneur).toBeGreaterThanOrEqual(0);

    // Switch to ALL section
    await switchSection(page, loginPage, ALL);

    // Navigate to avion/page again
    await loginPage.goto('avion/page');
    const planeCountAll = await getTotalCount(page);
    console.log(`Plane count in ALL section: ${planeCountAll}`);

    // ALL section should have >= planes than Planeur section
    expect(planeCountAll).toBeGreaterThanOrEqual(planeCountPlaneur);

    // Switch to GENERAL section
    await switchSection(page, loginPage, GENERAL);

    // Navigate to avion/page
    await loginPage.goto('avion/page');
    const planeCountGeneral = await getTotalCount(page);
    console.log(`Plane count in GENERAL section: ${planeCountGeneral}`);

    // General section typically has 0 or fewer planes
    expect(planeCountGeneral).toBeLessThanOrEqual(planeCountAll);

    console.log('✓ Section switching and filtering verified');

    await loginPage.logout();
  });

  test('should filter resources by section for comptes (accounts)', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Login with ULM section
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD, ULM);

    await loginPage.assertText('ULM');
    console.log('✓ Logged in with ULM section');

    // Navigate to comptes/page/411 (client accounts)
    await loginPage.goto('comptes/page/411');
    await page.waitForLoadState('domcontentloaded');

    const comptesUlm = await getTotalCount(page);
    console.log(`Client accounts in ULM section: ${comptesUlm}`);

    // Switch to Planeur section
    await switchSection(page, loginPage, PLANEUR);
    await loginPage.goto('comptes/page/411');
    const comptesPlaneur = await getTotalCount(page);
    console.log(`Client accounts in Planeur section: ${comptesPlaneur}`);

    // Switch to GENERAL section
    await switchSection(page, loginPage, GENERAL);
    await loginPage.goto('comptes/page/411');
    const comptesGeneral = await getTotalCount(page);
    console.log(`Client accounts in GENERAL section: ${comptesGeneral}`);

    // Switch to ALL section
    await switchSection(page, loginPage, ALL);
    await loginPage.goto('comptes/page/411');
    const comptesAll = await getTotalCount(page);
    console.log(`Client accounts in ALL section: ${comptesAll}`);

    // ALL section should have >= accounts than any individual section
    expect(comptesAll).toBeGreaterThanOrEqual(comptesUlm);
    expect(comptesAll).toBeGreaterThanOrEqual(comptesPlaneur);
    expect(comptesAll).toBeGreaterThanOrEqual(comptesGeneral);

    console.log('✓ Account filtering by section verified');

    await loginPage.logout();
  });

  test('should verify section selector is available on pages', async ({ page }) => {
    const loginPage = new LoginPage(page);

    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD, PLANEUR);

    // Verify section selector exists
    const sectionSelect = page.locator('select[name="section"]');
    await expect(sectionSelect).toBeVisible();
    console.log('✓ Section selector is visible');

    // Verify all sections are available in the selector
    const options = await sectionSelect.locator('option').all();
    const optionCount = options.length;

    expect(optionCount).toBeGreaterThanOrEqual(5); // Should have at least 5 sections
    console.log(`✓ Section selector has ${optionCount} options`);

    // Verify current section is selected
    const selectedValue = await sectionSelect.inputValue();
    expect(selectedValue).toBe(PLANEUR);
    console.log(`✓ Current section is ${selectedValue} (Planeur)`);

    await loginPage.logout();
  });

  test('should verify section filtering on vols_avion create page', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Login with Planeur section
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD, PLANEUR);

    // Navigate to vols_avion/create
    await loginPage.goto('vols_avion/create');
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1000);

    // Check for vamacid selector (plane selector)
    const planeSelector = page.locator('select[name="vamacid"]');

    if (await planeSelector.count() > 0) {
      await planeSelector.waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});

      // Get options in selector
      const optionsPlaneur = await planeSelector.locator('option').count();
      console.log(`Plane options in Planeur section: ${optionsPlaneur}`);

      // Switch to GENERAL section (should have fewer or no planes)
      await switchSection(page, loginPage, GENERAL);
      await loginPage.goto('vols_avion/create');
      await page.waitForTimeout(1000);

      const planeSelectorGeneral = page.locator('select[name="vamacid"]');
      if (await planeSelectorGeneral.count() > 0) {
        const optionsGeneral = await planeSelectorGeneral.locator('option').count();
        console.log(`Plane options in GENERAL section: ${optionsGeneral}`);

        // General should have <= options than Planeur
        expect(optionsGeneral).toBeLessThanOrEqual(optionsPlaneur);
      }

      console.log('✓ Plane selector filtering by section verified');
    } else {
      console.log('⚠️  Plane selector not found on vols_avion/create page');
    }

    await loginPage.logout();
  });

  test('should persist section selection across page navigation', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Login with ULM section
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD, ULM);

    // Verify ULM is selected
    await loginPage.assertText('ULM');
    console.log('✓ Logged in with ULM section');

    // Navigate to different pages and verify section persists
    const pages = ['avion/page', 'planeur/page', 'comptes/page'];

    for (const pagePath of pages) {
      await loginPage.goto(pagePath);
      await page.waitForLoadState('domcontentloaded');

      // Verify section selector still shows ULM
      const sectionSelect = page.locator('select[name="section"]');
      const selectedValue = await sectionSelect.inputValue();

      expect(selectedValue).toBe(ULM);
      console.log(`✓ Section ULM persisted on ${pagePath}`);
    }

    await loginPage.logout();
  });

});
