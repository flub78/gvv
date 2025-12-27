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
const LoginPage = require('./helpers/LoginPage');
const RapprochementsPage = require('./helpers/RapprochementsPage');

// Test configuration
const LOGIN_URL = '/index.php/auth/login';
const TEST_USER = {
  username: 'testadmin',
  correctPassword: 'password'
};

test.describe('Rapprochements Tab Persistence', () => {

  test('should save active tab to sessionStorage when clicked', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    // Check default tab is saved
    let savedTab = await rapprochementsPage.getSavedActiveTab();
    console.log('Default saved tab:', savedTab);
    expect(savedTab).toBeTruthy();

    // Click on "Ecritures GVV" tab
    console.log('Clicking on Ecritures GVV tab...');
    await rapprochementsPage.clickTab('gvv-tab');

    // Check that tab selection is saved
    savedTab = await rapprochementsPage.getSavedActiveTab();
    console.log('Saved tab after click:', savedTab);
    expect(savedTab).toBe('gvv-tab');

    console.log('✓ Tab selection saved to sessionStorage');
  });

  test('should restore active tab after page reload', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    // Click on "Ecritures GVV" tab
    console.log('Selecting Ecritures GVV tab...');
    await rapprochementsPage.clickTab('gvv-tab');

    // Verify tab is active
    expect(await rapprochementsPage.isTabActive('gvv-tab')).toBeTruthy();
    await expect(page.locator('#gvv')).toHaveClass(/show/);

    // Reload the page
    console.log('Reloading page...');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Check that "Ecritures GVV" tab is still active after reload
    expect(await rapprochementsPage.isTabActive('gvv-tab')).toBeTruthy();
    await expect(page.locator('#gvv')).toHaveClass(/show/);

    console.log('✓ Tab restored after page reload');

    // Take screenshot to verify
    await page.screenshot({ path: 'playwright/screenshots/tab_persistence_after_reload.png', fullPage: true });
  });

  test('should restore Relevé de banque tab after reload', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    // The default tab should be "Relevé de banque"
    expect(await rapprochementsPage.isTabActive('openflyers-tab')).toBeTruthy();

    // Explicitly click on it to ensure it's saved
    await rapprochementsPage.clickTab('openflyers-tab');

    // Reload the page
    console.log('Reloading page...');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Check that "Relevé de banque" tab is still active after reload
    expect(await rapprochementsPage.isTabActive('openflyers-tab')).toBeTruthy();
    await expect(page.locator('#openflyers')).toHaveClass(/show/);

    console.log('✓ Bank statement tab restored after page reload');
  });

  test('should handle tab switching and reload correctly', async ({ page }) => {
    // Login and upload bank statement
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.correctPassword);

    const rapprochementsPage = new RapprochementsPage(page);
    await rapprochementsPage.uploadAndNavigate();

    // Switch to GVV tab
    console.log('Switching to GVV tab...');
    await rapprochementsPage.clickTab('gvv-tab');
    expect(await rapprochementsPage.isTabActive('gvv-tab')).toBeTruthy();

    // Switch back to Bank tab
    console.log('Switching back to Bank tab...');
    await rapprochementsPage.clickTab('openflyers-tab');
    expect(await rapprochementsPage.isTabActive('openflyers-tab')).toBeTruthy();

    // Reload
    console.log('Reloading...');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Should be on Bank tab
    expect(await rapprochementsPage.isTabActive('openflyers-tab')).toBeTruthy();

    // Switch to GVV again
    console.log('Switching to GVV tab again...');
    await rapprochementsPage.clickTab('gvv-tab');

    // Reload again
    console.log('Reloading again...');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Should be on GVV tab now
    expect(await rapprochementsPage.isTabActive('gvv-tab')).toBeTruthy();

    console.log('✓ Tab switching and reload works correctly');
  });

});
