/**
 * Playwright smoke tests for the Relances feature (Phase 1).
 *
 * Tests:
 *  - Login as testtresorier and access relances/index
 *  - Page displays debtor table with expected columns
 *  - Anonymous mode checkbox is active by default (names are blurred)
 *  - Toggle anonymous mode off shows names normally
 *  - Threshold fields are editable and form submits
 *  - Menu entry exists in Compta dropdown
 *  - Dashboard card exists in Trésorerie section
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/relances.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL      = '/index.php/auth/login';
const RELANCES_URL   = '/index.php/relances/index';
const DASHBOARD_URL  = '/index.php/welcome/section/treasurer';

const TRESORIER = { username: 'testtresorier', password: 'password' };

async function loginAs(page, user, section = '1') {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');
  await page.fill('input[name="username"]', user.username);
  await page.fill('input[name="password"]', user.password);
  if (section) {
    const sectionSelect = page.locator('select[name="section"]');
    if (await sectionSelect.count() > 0) {
      await sectionSelect.selectOption(section);
    }
  }
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

test.describe('Relances - Phase 1 smoke tests', () => {

  test('should access relances/index as testtresorier', async ({ page }) => {
    await loginAs(page, TRESORIER);
    await page.goto(RELANCES_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    // Page title contains "Relances"
    const heading = page.locator('h2');
    await expect(heading).toContainText('Relances');
  });

  test('should display threshold fields', async ({ page }) => {
    await loginAs(page, TRESORIER);
    await page.goto(RELANCES_URL);
    await page.waitForLoadState('networkidle');

    await expect(page.locator('#seuil_alarme')).toBeVisible();
    await expect(page.locator('#seuil_critique')).toBeVisible();

    const alarme   = await page.inputValue('#seuil_alarme');
    const critique = await page.inputValue('#seuil_critique');
    expect(parseInt(alarme)).toBeGreaterThan(0);
    expect(parseInt(critique)).toBeGreaterThan(parseInt(alarme));
  });

  test('should show anonymous mode checkbox checked by default', async ({ page }) => {
    // Clear localStorage before test to reset to default state
    await page.goto(RELANCES_URL);
    await page.evaluate(() => localStorage.removeItem('relances_mode_anonyme'));

    await loginAs(page, TRESORIER);
    await page.goto(RELANCES_URL);
    await page.waitForLoadState('networkidle');

    const checkbox = page.locator('#mode_anonyme');
    await expect(checkbox).toBeVisible();
    await expect(checkbox).toBeChecked();

    // Body should have mode-anonyme class
    const body = page.locator('body');
    await expect(body).toHaveClass(/mode-anonyme/);
  });

  test('should toggle anonymous mode off when unchecking', async ({ page }) => {
    await loginAs(page, TRESORIER);
    await page.goto(RELANCES_URL);
    await page.waitForLoadState('networkidle');

    // Ensure mode is on first
    const checkbox = page.locator('#mode_anonyme');
    if (!(await checkbox.isChecked())) {
      await checkbox.check();
    }
    await expect(page.locator('body')).toHaveClass(/mode-anonyme/);

    // Uncheck
    await checkbox.uncheck();
    await expect(page.locator('body')).not.toHaveClass(/mode-anonyme/);
  });

  test('should display debtor table with expected columns', async ({ page }) => {
    await loginAs(page, TRESORIER);
    await page.goto(RELANCES_URL);
    await page.waitForLoadState('networkidle');

    const table = page.locator('#table-debiteurs');
    const tableExists = await table.count();

    if (tableExists === 0) {
      // Acceptable if there are no debtors
      const alert = page.locator('.alert-info');
      await expect(alert).toBeVisible();
      console.log('No debtors in test database, skipping column check.');
      return;
    }

    await expect(table).toBeVisible();

    const headers = await page.locator('#table-debiteurs thead th').allTextContents();
    expect(headers.some(h => h.includes('Total'))).toBe(true);
    expect(headers.some(h => h.includes('6 mois') || h.includes('6 months'))).toBe(true);
    expect(headers.some(h => h.includes('1 an') || h.includes('1 year'))).toBe(true);
  });

  test('should update thresholds and redirect back', async ({ page }) => {
    await loginAs(page, TRESORIER);
    await page.goto(RELANCES_URL);
    await page.waitForLoadState('networkidle');

    // Set new thresholds
    await page.fill('#seuil_alarme',   '250');
    await page.fill('#seuil_critique', '450');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    // Should be back on relances page
    expect(page.url()).toContain('relances');

    // Restore defaults
    await page.fill('#seuil_alarme',   '300');
    await page.fill('#seuil_critique', '500');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
  });

  test('should show relances menu entry in Compta dropdown', async ({ page }) => {
    await loginAs(page, TRESORIER);
    await page.goto('/index.php/welcome');
    await page.waitForLoadState('networkidle');

    // Check that a link to relances/index exists somewhere in the page source
    const html = await page.content();
    expect(html).toContain('relances/index');
  });

  test('should show relances card in Trésorerie dashboard', async ({ page }) => {
    await loginAs(page, TRESORIER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const card = page.locator('.sub-card', { hasText: /Relances/i });
    await expect(card.first()).toBeVisible();
  });

});
