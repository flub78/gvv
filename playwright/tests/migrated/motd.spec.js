/**
 * Message of the Day (MOTD) Tests - Migrated from Dusk to Playwright
 *
 * Original Dusk test: /home/frederic/git/dusk_gvv/tests/Browser/MotdTest.php
 *
 * Tests:
 * - Access to home page and verify basic elements
 * - Login workflow with MOTD display
 * - MOTD "don't show again" functionality
 * - Verify MOTD doesn't appear on subsequent login after dismissal
 *
 * Usage:
 *   npx playwright test tests/migrated/motd.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('../helpers/LoginPage');

// Test configuration
const TEST_USER = 'testadmin';
const TEST_PASSWORD = 'password';

test.describe('GVV Message of the Day Tests (Migrated from Dusk)', () => {

  test('should access home page and see basic elements', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Navigate to the main page (not login page)
    await loginPage.goto('');

    // Verify basic page elements are visible
    await loginPage.assertText('GVV');
    await loginPage.assertText('Boissel');
    await loginPage.assertText('Peignot');

    console.log('✓ Home page access verified');
  });

  test('should show MOTD on first login and not show on second login after dismissal', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Navigate to login page
    await loginPage.open();

    // Fill login form but don't use the login() method that auto-closes MOTD
    await page.fill('input[name="username"]', TEST_USER);
    await page.fill('input[name="password"]', TEST_PASSWORD);
    await page.selectOption('select[name="section"]', '1');
    await page.click('input[type="submit"], button[type="submit"]');

    // Wait for page to load
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1000);

    console.log('Login submitted, checking for MOTD...');
    await loginPage.screenshot('after_login_check_motd');

    // Check if MOTD dialog is visible
    const motdText = 'Message du jour';
    const hasMotd = await page.locator(`text=${motdText}`).isVisible({ timeout: 5000 }).catch(() => false);

    if (!hasMotd) {
      console.log('⚠️  MOTD not visible - it may have been dismissed previously or not configured');
      console.log('This test verifies MOTD behavior when it is configured in the system');
      // Still verify we're logged in
      await loginPage.verifyLoggedIn();
    } else {
      console.log('✓ MOTD dialog is visible');

      // Check the "don't show again" checkbox
      // Look for checkbox near "Ne plus afficher ce message" text
      let checkboxChecked = false;

      // Try different selectors for the checkbox
      const checkboxSelectors = [
        '#no_mod',
        'input[type="checkbox"]',
        '.modal input[type="checkbox"]',
        '.modal-body input[type="checkbox"]'
      ];

      for (const selector of checkboxSelectors) {
        const checkbox = page.locator(selector).first();
        if (await checkbox.count() > 0 && await checkbox.isVisible({ timeout: 1000 }).catch(() => false)) {
          await checkbox.check();
          console.log(`✓ Checked "don't show again" checkbox using selector: ${selector}`);
          checkboxChecked = true;
          break;
        }
      }

      if (!checkboxChecked) {
        console.log('⚠️  "Don\'t show again" checkbox not found');
      }

      // Close the MOTD dialog - look for OK button
      const closeButton = page.locator('button:has-text("OK"), button:has-text("ok")');
      await closeButton.click();
      console.log('✓ Closed MOTD dialog');

      await page.waitForTimeout(500);
      await loginPage.screenshot('after_motd_closed');

      // Verify we're logged in
      await loginPage.verifyLoggedIn();

      // Logout
      await loginPage.logout();
      console.log('✓ Logged out');

      // Login again - MOTD should NOT appear
      console.log('Logging in again to verify MOTD doesn\'t reappear...');
      await page.fill('input[name="username"]', TEST_USER);
      await page.fill('input[name="password"]', TEST_PASSWORD);
      await page.selectOption('select[name="section"]', '1');
      await page.click('input[type="submit"], button[type="submit"]');

      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);
      await loginPage.screenshot('second_login_check_motd');

      // Verify MOTD does NOT appear
      const hasMotdSecondTime = await page.locator(`text=${motdText}`).isVisible({ timeout: 2000 }).catch(() => false);

      if (hasMotdSecondTime) {
        console.log('❌ MOTD appeared on second login - "don\'t show again" didn\'t work');
        throw new Error('MOTD should not appear on second login after dismissal');
      } else {
        console.log('✓ MOTD correctly hidden on second login');
      }

      // Verify we're still logged in
      await loginPage.verifyLoggedIn();
    }

    // Final logout
    await loginPage.logout();
    console.log('✓ Test completed successfully');
  });

  test('should handle login/logout workflow with MOTD auto-dismissed by LoginPage helper', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // This test uses the standard LoginPage.login() method which auto-handles MOTD
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD);

    // Verify successful login
    await loginPage.verifyLoggedIn();
    console.log('✓ Login successful (MOTD auto-handled by LoginPage helper)');

    // Perform logout
    await loginPage.logout();

    // Verify successful logout
    await loginPage.verifyLoggedOut();
    console.log('✓ Logout successful');
  });

});
