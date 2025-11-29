/**
 * Playwright test for Rapprochements Tab Persistence
 *
 * Tests:
 * - Tab selection is saved to sessionStorage
 * - Active tab is restored after page reload
 * - Tab persistence works for all tabs
 *
 * Usage:
 *   npx playwright test tests/rapprochements-tab-persistence.spec.js
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const BASE_URL = 'http://gvv.net';
const LOGIN_URL = `${BASE_URL}/auth/login`;
const RAPPROCHEMENTS_URL = `${BASE_URL}/rapprochements/import_releve_from_file`;
const TEST_USER = {
  username: 'testadmin',
  correctPassword: 'password'
};

test.describe('Rapprochements Tab Persistence', () => {

  test('should save active tab to sessionStorage when clicked', async ({ page }) => {
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
    await page.waitForTimeout(2000);

    // Check default tab is saved
    let savedTab = await page.evaluate(() => sessionStorage.getItem('rapprochements_active_tab'));
    console.log('Default saved tab:', savedTab);
    expect(savedTab).toBeTruthy();

    // Click on "Ecritures GVV" tab
    console.log('Clicking on Ecritures GVV tab...');
    const gvvTab = await page.locator('#gvv-tab');
    await gvvTab.click();
    await page.waitForTimeout(500);

    // Check that tab selection is saved
    savedTab = await page.evaluate(() => sessionStorage.getItem('rapprochements_active_tab'));
    console.log('Saved tab after click:', savedTab);
    expect(savedTab).toBe('gvv-tab');

    console.log('✓ Tab selection saved to sessionStorage');
  });

  test('should restore active tab after page reload', async ({ page }) => {
    // Login
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
    console.log('Selecting Ecritures GVV tab...');
    const gvvTab = await page.locator('#gvv-tab');
    await gvvTab.click();
    await page.waitForTimeout(500);

    // Verify tab is active
    await expect(gvvTab).toHaveClass(/active/);
    const gvvContent = await page.locator('#gvv');
    await expect(gvvContent).toHaveClass(/show/);

    // Reload the page
    console.log('Reloading page...');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Check that "Ecritures GVV" tab is still active after reload
    const gvvTabAfterReload = await page.locator('#gvv-tab');
    await expect(gvvTabAfterReload).toHaveClass(/active/);

    const gvvContentAfterReload = await page.locator('#gvv');
    await expect(gvvContentAfterReload).toHaveClass(/show/);

    console.log('✓ Tab restored after page reload');

    // Take screenshot to verify
    await page.screenshot({ path: 'playwright/screenshots/tab_persistence_after_reload.png', fullPage: true });
  });

  test('should restore Relevé de banque tab after reload', async ({ page }) => {
    // Login
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

    // The default tab should be "Relevé de banque"
    const bankTab = await page.locator('#openflyers-tab');
    await expect(bankTab).toHaveClass(/active/);

    // Explicitly click on it to ensure it's saved
    await bankTab.click();
    await page.waitForTimeout(500);

    // Reload the page
    console.log('Reloading page...');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Check that "Relevé de banque" tab is still active after reload
    const bankTabAfterReload = await page.locator('#openflyers-tab');
    await expect(bankTabAfterReload).toHaveClass(/active/);

    const bankContentAfterReload = await page.locator('#openflyers');
    await expect(bankContentAfterReload).toHaveClass(/show/);

    console.log('✓ Bank statement tab restored after page reload');
  });

  test('should handle tab switching and reload correctly', async ({ page }) => {
    // Login
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

    // Switch to GVV tab
    console.log('Switching to GVV tab...');
    await page.locator('#gvv-tab').click();
    await page.waitForTimeout(500);
    await expect(page.locator('#gvv-tab')).toHaveClass(/active/);

    // Switch back to Bank tab
    console.log('Switching back to Bank tab...');
    await page.locator('#openflyers-tab').click();
    await page.waitForTimeout(500);
    await expect(page.locator('#openflyers-tab')).toHaveClass(/active/);

    // Reload
    console.log('Reloading...');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Should be on Bank tab
    await expect(page.locator('#openflyers-tab')).toHaveClass(/active/);

    // Switch to GVV again
    console.log('Switching to GVV tab again...');
    await page.locator('#gvv-tab').click();
    await page.waitForTimeout(500);

    // Reload again
    console.log('Reloading again...');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Should be on GVV tab now
    await expect(page.locator('#gvv-tab')).toHaveClass(/active/);

    console.log('✓ Tab switching and reload works correctly');
  });

});
