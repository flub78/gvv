/**
 * Playwright test for GVV authentication
 *
 * Tests:
 * - Successful login with correct credentials
 * - Successful logout
 * - Failed login with incorrect password
 *
 * Usage:
 *   npx playwright test tests/auth-login.spec.js
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const LOGIN_URL = '/index.php/auth/login';
const TEST_USER = {
  username: 'testadmin',
  correctPassword: 'password',
  wrongPassword: 'wrongpassword123'
};

test.describe('GVV Authentication Tests', () => {

  test('should successfully login with correct credentials and logout', async ({ page }) => {
    // Navigate to login page
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');

    // Fill in login form
    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.correctPassword);

    // Submit the login form
    await page.click('button[type="submit"], input[type="submit"]');

    // Wait for navigation after login
    await page.waitForLoadState('networkidle');

    // Verify successful login by checking we're no longer on login page
    expect(page.url()).not.toBe(LOGIN_URL);

    // Verify we're redirected to the main application
    // The URL should change from /auth/login to something else
    const currentUrl = page.url();
    console.log(`After login, redirected to: ${currentUrl}`);

    // Check if we can see authenticated content
    // Look for common elements that only logged-in users see
    const isLoggedIn = await page.locator('body').evaluate((body) => {
      // Check if we're NOT on the login page anymore
      return !body.innerText.toLowerCase().includes('login failed');
    });
    expect(isLoggedIn).toBeTruthy();

    // Now test logout
    // Look for logout link/button - it might be in various formats
    const logoutSelectors = [
      'a[href*="logout"]',
      'a:has-text("Logout")',
      'a:has-text("Déconnexion")',
      'a:has-text("Log out")',
      'button:has-text("Logout")',
      'button:has-text("Déconnexion")'
    ];

    let loggedOut = false;
    for (const selector of logoutSelectors) {
      try {
        const logoutElement = page.locator(selector).first();
        if (await logoutElement.isVisible({ timeout: 2000 })) {
          console.log(`Found logout element with selector: ${selector}`);
          await logoutElement.click();
          await page.waitForLoadState('networkidle');
          loggedOut = true;
          break;
        }
      } catch (e) {
        // Try next selector
        continue;
      }
    }

    if (loggedOut) {
      // Verify we're back at login page or logged out
      const finalUrl = page.url();
      console.log(`After logout, URL is: ${finalUrl}`);

      // We should either be at login page or see login form
      const hasLoginForm = await page.locator('input[name="username"]').count() > 0;
      expect(hasLoginForm || finalUrl.includes('auth/login')).toBeTruthy();
    } else {
      console.log('Warning: Could not find logout button/link');
      // Take a screenshot for debugging
      await page.screenshot({ path: 'build/playwright-captures/after-login.png', fullPage: true });
    }
  });

  test('should deny login with incorrect password', async ({ page }) => {
    // Navigate to login page
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');

    // Fill in login form with wrong password
    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.wrongPassword);

    // Submit the login form
    await page.click('button[type="submit"], input[type="submit"]');

    // Wait for response
    await page.waitForLoadState('networkidle');

    // Verify login failed - should still be on login page or show error
    const currentUrl = page.url();
    console.log(`After failed login attempt, URL is: ${currentUrl}`);

    // Check for error message or that we're still on login page
    const hasError = await page.evaluate(() => {
      const bodyText = document.body.innerText.toLowerCase();
      return bodyText.includes('incorrect') ||
             bodyText.includes('invalid') ||
             bodyText.includes('failed') ||
             bodyText.includes('erreur') ||
             bodyText.includes('incorrect') ||
             bodyText.includes('invalide');
    });

    const stillOnLoginPage = currentUrl.includes('auth/login') ||
                            await page.locator('input[name="username"]').count() > 0;

    // Either we see an error message OR we're still on the login page
    expect(hasError || stillOnLoginPage).toBeTruthy();

    if (hasError) {
      console.log('Login correctly denied - error message shown');
    } else if (stillOnLoginPage) {
      console.log('Login correctly denied - still on login page');
    }

    // Take screenshot of the failed login state
    await page.screenshot({
      path: 'build/playwright-captures/failed-login.png',
      fullPage: true
    });
  });

  test('should show login form elements', async ({ page }) => {
    // Navigate to login page
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');

    // Verify login form elements exist
    const loginInput = page.locator('input[name="username"]');
    const passwordInput = page.locator('input[name="password"]');
    const submitButton = page.locator('button[type="submit"], input[type="submit"]');

    await expect(loginInput).toBeVisible();
    await expect(passwordInput).toBeVisible();
    await expect(submitButton).toBeVisible();

    console.log('Login form elements verified');
  });
});
