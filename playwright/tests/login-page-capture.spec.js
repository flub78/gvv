/**
 * Playwright test to capture screenshot and HTML of the GVV login page
 *
 * Usage:
 *   npx playwright test tests/playwright/login-page-capture.spec.js
 */

const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

test.describe('GVV Login Page Capture', () => {
  test('should capture screenshot and HTML of login page', async ({ page }) => {
    // Create output directory if it doesn't exist
    const outputDir = path.join(__dirname, '../../build/playwright-captures');
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }

    // Navigate to the login page
    await page.goto('/auth/login');

    // Wait for the page to be fully loaded
    await page.waitForLoadState('networkidle');

    // Take a screenshot
    const screenshotPath = path.join(outputDir, 'login-page.png');
    await page.screenshot({
      path: screenshotPath,
      fullPage: true
    });
    console.log(`Screenshot saved to: ${screenshotPath}`);

    // Get the HTML content
    const htmlContent = await page.content();
    const htmlPath = path.join(outputDir, 'login-page.html');
    fs.writeFileSync(htmlPath, htmlContent);
    console.log(`HTML saved to: ${htmlPath}`);

    // Verify the page loaded correctly (basic assertions)
    expect(await page.title()).toBeTruthy();

    // Log some basic info
    console.log(`Page Title: ${await page.title()}`);
    console.log(`Page URL: ${page.url()}`);
  });
});
