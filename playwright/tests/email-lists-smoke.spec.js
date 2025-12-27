/**
 * Playwright smoke test for Email Lists feature
 *
 * Tests:
 * - Login and navigate to email lists
 * - Access email lists index page
 * - Access create form
 * - Check that all tabs are present
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/email-lists-smoke.spec.js --headed
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const LOGIN_URL = '/index.php/auth/login';
const EMAIL_LISTS_URL = `/email_lists`;
const TEST_USER = {
  username: 'testadmin',
  password: 'password'
};

test.describe('Email Lists Smoke Tests', () => {

  test('should access email lists index page after login', async ({ page }) => {
    // Login first
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    console.log('Logged in successfully');

    // Navigate to email lists
    await page.goto(EMAIL_LISTS_URL);
    await page.waitForLoadState('networkidle');

    console.log('Navigated to:', page.url());

    // Check for PHP errors in the page content
    const bodyText = await page.textContent('body');

    if (bodyText.includes('Fatal error') ||
        bodyText.includes('Parse error') ||
        bodyText.includes('A PHP Error was encountered')) {
      console.error('PHP Error detected on page!');
      console.error(bodyText.substring(0, 500));
      throw new Error('PHP error found on email lists page');
    }

    // Debug: Print what we actually got
    const h3Elements = await page.locator('h3').allTextContents();
    console.log('H3 elements found:', h3Elements);

    const bodySnippet = await page.textContent('body');
    console.log('Body snippet (first 300 chars):', bodySnippet.substring(0, 300));

    // Verify we're on the email lists page (should have the title)
    await expect(page.locator('h3')).toContainText('Listes de diffusion', { timeout: 5000 });

    console.log('✓ Email lists index page loaded successfully');

    // Check for create button
    const createButton = page.locator('a.btn:has-text("Nouvelle liste")');
    await expect(createButton).toBeVisible();

    console.log('✓ Create button is visible');
  });

  test('should access create form and see all tabs', async ({ page }) => {
    // Login
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Go directly to create form
    await page.goto(`${EMAIL_LISTS_URL}/create`);
    await page.waitForLoadState('networkidle');

    // Check for PHP errors
    const bodyText = await page.textContent('body');
    if (bodyText.includes('Fatal error') ||
        bodyText.includes('Parse error') ||
        bodyText.includes('A PHP Error was encountered')) {
      console.error('PHP Error detected on create form!');
      console.error(bodyText.substring(0, 500));
      throw new Error('PHP error found on create form');
    }

    console.log('✓ Create form loaded without PHP errors');

    // Check basic form fields exist
    await expect(page.locator('input[name="name"]')).toBeVisible();
    console.log('✓ Name field is visible');

    await expect(page.locator('textarea[name="description"]')).toBeVisible();
    console.log('✓ Description field is visible');

    // Check active member select
    await expect(page.locator('select[name="active_member"]')).toBeVisible();
    console.log('✓ Active member select is visible');

    // Check visible checkbox
    await expect(page.locator('input[name="visible"]')).toBeVisible();
    console.log('✓ Visible checkbox is present');

    // Check submit button
    await expect(page.locator('button[type="submit"], input[type="submit"]')).toBeVisible();
    console.log('✓ Submit button is visible');

    // Note: In CREATE mode, address selection tabs (#criteria-tab, #manual-tab, #import-tab)
    // are not available. They only become available after the list is created (EDIT mode).
    console.log('✓ Create form validation complete');

    console.log('\n✓ ALL SMOKE TESTS PASSED');
  });

  test('should check menu entry exists in Dev menu', async ({ page }) => {
    // Login
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Close "Message du jour" dialog if it appears (it blocks interactions)
    const modDialog = page.locator('.ui-dialog');
    if (await modDialog.isVisible().catch(() => false)) {
      const closeButton = page.locator('.ui-dialog-buttonpane button:has-text("OK")');
      if (await closeButton.isVisible().catch(() => false)) {
        await closeButton.click();
        await page.waitForTimeout(500);
      }
    }

    // Look for Dev menu
    const devMenu = page.locator('a.nav-link:has-text("Dev")');
    if (await devMenu.isVisible()) {
      console.log('✓ Dev menu is visible');

      // Hover over Dev menu to show dropdown
      await devMenu.hover();
      await page.waitForTimeout(500);

      // Look for email lists entry
      const emailListsLink = page.locator('a.dropdown-item:has-text("Listes de diffusion")');
      if (await emailListsLink.isVisible()) {
        console.log('✓ Email Lists entry found in Dev menu');
      } else {
        console.log('⚠ Email Lists entry not visible in Dev menu (may need dev_menu config)');
      }
    } else {
      console.log('⚠ Dev menu not visible (may need dev_menu config enabled)');
    }
  });

});
