/**
 * Playwright smoke tests for Archived Documents feature
 *
 * Tests:
 * - Admin accesses "Mes documents" page
 * - Admin accesses the admin documents list
 * - Admin accesses the expired documents filter
 * - Admin can open the pilot document creation form
 * - Pilot accesses their own documents page
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/archived-documents-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const MY_DOCS_URL = '/index.php/archived_documents/my_documents';
const ADMIN_LIST_URL = '/index.php/archived_documents/page';
const EXPIRED_URL = '/index.php/archived_documents/page?filter=expired';
const CREATE_PILOT_URL = '/index.php/archived_documents/create_pilot';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const PILOT_USER = { username: 'testuser', password: 'password' };

async function login(page, user) {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');
  await page.fill('input[name="username"]', user.username);
  await page.fill('input[name="password"]', user.password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');
}

async function checkNoPhpErrors(page) {
  const body = await page.textContent('body');
  expect(body).not.toContain('Fatal error');
  expect(body).not.toContain('Parse error');
  expect(body).not.toContain('A PHP Error was encountered');
  expect(body).not.toContain('An uncaught Exception was encountered');
}

test.describe('Archived Documents Smoke Tests', () => {

  test('admin can access My Documents page', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(MY_DOCS_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    const body = await page.textContent('body');
    // Page should show the documents section (not redirect to login)
    expect(body).not.toContain('name="username"');

    console.log('My Documents page loaded successfully');
  });

  test('admin can access the admin documents list', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // Should not be redirected to login
    const body = await page.textContent('body');
    expect(body).not.toContain('name="username"');

    console.log('Admin documents list loaded successfully');
  });

  test('admin can filter expired documents', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(EXPIRED_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    const body = await page.textContent('body');
    expect(body).not.toContain('name="username"');

    console.log('Expired documents filter page loaded successfully');
  });

  test('admin can open the pilot document creation form', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(CREATE_PILOT_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    const body = await page.textContent('body');
    expect(body).not.toContain('name="username"');

    // Form should contain a document type selector
    const typeSelect = page.locator('select[name="document_type_id"]');
    await expect(typeSelect).toBeVisible();

    console.log('Pilot document creation form loaded successfully');
  });

  test('pilot can access their own documents page', async ({ page }) => {
    await login(page, PILOT_USER);

    await page.goto(MY_DOCS_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    const body = await page.textContent('body');
    expect(body).not.toContain('name="username"');

    console.log('Pilot My Documents page loaded successfully');
  });

  test('non-admin is redirected from admin list to my_documents', async ({ page }) => {
    await login(page, PILOT_USER);

    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // Non-admin should NOT reach the admin list (redirected to my_documents or auth/deny)
    expect(page.url()).not.toContain('archived_documents/page');

    console.log('Non-admin correctly denied access to admin list');
  });

});
