/**
 * Playwright smoke test for Acceptance Admin feature
 *
 * Tests:
 * - Login as admin and navigate to acceptance admin page
 * - Access the items list page
 * - Access the create form
 * - Create an item and verify it appears in the list
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/acceptance-admin-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const LOGIN_URL = '/index.php/auth/login';
const ACCEPTANCE_ADMIN_URL = '/index.php/acceptance_admin/page';
const ACCEPTANCE_CREATE_URL = '/index.php/acceptance_admin/create';
const TEST_USER = {
  username: 'testadmin',
  password: 'password'
};

/**
 * Helper: login as admin
 */
async function loginAsAdmin(page) {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');
  await page.fill('input[name="username"]', TEST_USER.username);
  await page.fill('input[name="password"]', TEST_USER.password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');
}

/**
 * Helper: check for PHP errors
 */
async function checkNoPhpErrors(page) {
  const bodyText = await page.textContent('body');
  expect(bodyText).not.toContain('Fatal error');
  expect(bodyText).not.toContain('Parse error');
  expect(bodyText).not.toContain('A PHP Error was encountered');
  expect(bodyText).not.toContain('An uncaught Exception was encountered');
}

test.describe('Acceptance Admin Smoke Tests', () => {

  test('should access acceptance admin page after login', async ({ page }) => {
    await loginAsAdmin(page);

    // Navigate to acceptance admin
    await page.goto(ACCEPTANCE_ADMIN_URL);
    await page.waitForLoadState('networkidle');

    console.log('Navigated to:', page.url());

    // Check no PHP errors
    await checkNoPhpErrors(page);

    // Check page title is present
    const bodyText = await page.textContent('body');
    expect(bodyText).toContain('Administration des acceptations');

    console.log('Acceptance admin page loaded successfully');
  });

  test('should access create form', async ({ page }) => {
    await loginAsAdmin(page);

    // Navigate to create form
    await page.goto(ACCEPTANCE_CREATE_URL);
    await page.waitForLoadState('networkidle');

    console.log('Navigated to:', page.url());

    // Check no PHP errors
    await checkNoPhpErrors(page);

    // Check form elements are present
    await expect(page.locator('input[name="title"]')).toBeVisible();
    await expect(page.locator('select[name="category"]')).toBeVisible();
    await expect(page.locator('select[name="target_type"]')).toBeVisible();

    console.log('Create form loaded with all fields');
  });

  test('should create an acceptance item', async ({ page }) => {
    await loginAsAdmin(page);

    // Navigate to create form
    await page.goto(ACCEPTANCE_CREATE_URL);
    await page.waitForLoadState('networkidle');

    // Fill in the form
    const itemTitle = 'Test Acceptance Item ' + Date.now();
    await page.fill('input[name="title"]', itemTitle);
    await page.selectOption('select[name="category"]', 'document');
    await page.selectOption('select[name="target_type"]', 'internal');

    // Check mandatory checkbox
    await page.check('input[name="mandatory"]');

    // Submit the form
    // Click the submit button (first submit button, which is "Valider")
    await page.locator('button[type="submit"].btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    // Check no PHP errors
    await checkNoPhpErrors(page);

    // Should be redirected to the list page with success message
    const bodyText = await page.textContent('body');
    expect(bodyText).toContain('succès') ;

    console.log('Item created successfully');

    // Navigate to the list and verify the item appears
    await page.goto(ACCEPTANCE_ADMIN_URL);
    await page.waitForLoadState('networkidle');

    const listText = await page.textContent('body');
    expect(listText).toContain(itemTitle);

    console.log('Item appears in the list');
  });

  test('should access tracking view for an item', async ({ page }) => {
    await loginAsAdmin(page);

    // First create an item so we have something to track
    await page.goto(ACCEPTANCE_CREATE_URL);
    await page.waitForLoadState('networkidle');

    const itemTitle = 'Tracking Test ' + Date.now();
    await page.fill('input[name="title"]', itemTitle);
    await page.selectOption('select[name="category"]', 'briefing');
    await page.locator('button[type="submit"].btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    // Go to the list to find the tracking button
    await page.goto(ACCEPTANCE_ADMIN_URL);
    await page.waitForLoadState('networkidle');

    // Check if there is a tracking button
    const trackingLink = page.locator('a[title="Suivi des acceptations"]').first();
    const trackingExists = await trackingLink.count();

    if (trackingExists > 0) {
      await trackingLink.click();
      await page.waitForLoadState('networkidle');

      // Check no PHP errors
      await checkNoPhpErrors(page);

      const bodyText = await page.textContent('body');
      expect(bodyText).toContain('Suivi des acceptations');

      console.log('Tracking view loaded successfully');
    } else {
      console.log('No tracking button found - skipping');
    }
  });
});
