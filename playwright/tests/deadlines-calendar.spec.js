/**
 * Playwright smoke tests for Deadlines Calendar feature
 *
 * Tests:
 * - Landing page is the year view (default)
 * - Year view shows 12 month cards
 * - Month title click navigates to month view, back button returns to year view
 * - Year prev/next navigation works
 * - Tooltips exist on event dots
 * - JSON events endpoint returns a valid array
 * - Non-admin pilot is redirected away
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/deadlines-calendar.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL    = '/index.php/auth/login';
const CALENDAR_URL = '/index.php/deadlines_calendar';
const EVENTS_URL   = '/index.php/deadlines_calendar/get_events';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const PILOT_USER = { username: 'testuser',  password: 'password' };

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

async function openCalendar(page) {
  await page.goto(CALENDAR_URL);
  await page.waitForLoadState('networkidle');
}

test.describe('Deadlines Calendar Smoke Tests', () => {

  test('landing page is the year view by default', async ({ page }) => {
    await login(page, ADMIN_USER);
    await openCalendar(page);
    await checkNoPhpErrors(page);

    // Year view must be visible, FullCalendar hidden
    await expect(page.locator('#year-view')).toBeVisible();
    await expect(page.locator('#calendar')).toBeHidden();

    // 12 month cards
    await expect(page.locator('.year-month-card')).toHaveCount(12);

    // Year title is a 4-digit number
    const title = await page.locator('#year-view-title').textContent();
    expect(title).toMatch(/^\d{4}$/);
  });

  test('year view prev/next year navigation works', async ({ page }) => {
    await login(page, ADMIN_USER);
    await openCalendar(page);

    const titleBefore = await page.locator('#year-view-title').textContent();
    await page.locator('#year-prev-btn').click();
    await page.waitForTimeout(600);
    const titleAfter = await page.locator('#year-view-title').textContent();
    expect(parseInt(titleAfter)).toBe(parseInt(titleBefore) - 1);

    await page.locator('#year-next-btn').click();
    await page.waitForTimeout(600);
    const titleRestored = await page.locator('#year-view-title').textContent();
    expect(titleRestored).toBe(titleBefore);
  });

  test('clicking month title switches to FullCalendar month view', async ({ page }) => {
    await login(page, ADMIN_USER);
    await openCalendar(page);

    // Click the first month title
    await page.locator('.year-month-title').first().click();
    await page.waitForLoadState('networkidle');

    // Year view hidden, FullCalendar visible
    await expect(page.locator('#year-view')).toBeHidden();
    await expect(page.locator('#calendar')).toBeVisible();
    await expect(page.locator('.fc-toolbar')).toBeVisible();

    // Back-to-year button must appear
    await expect(page.locator('#back-to-year-bar')).toBeVisible();
  });

  test('back-to-year button returns to year view from month view', async ({ page }) => {
    await login(page, ADMIN_USER);
    await openCalendar(page);

    // Navigate to month view
    await page.locator('.year-month-title').first().click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#back-to-year-bar')).toBeVisible();

    // Click back button
    await page.locator('#back-to-year-bar button').click();
    await page.waitForTimeout(600);

    // Back to year view
    await expect(page.locator('#year-view')).toBeVisible();
    await expect(page.locator('#calendar')).toBeHidden();
    await expect(page.locator('#back-to-year-bar')).toBeHidden();
  });

  test('FullCalendar toolbar has translated view buttons, no year button', async ({ page }) => {
    await login(page, ADMIN_USER);
    await openCalendar(page);

    await page.evaluate(() => yearMonthClick(new Date().getFullYear(), 0));
    await page.waitForTimeout(300);

    await expect(page.locator('.fc-prev-button')).toBeVisible();
    await expect(page.locator('.fc-next-button')).toBeVisible();
    await expect(page.locator('.fc-dayGridMonth-button')).toBeVisible();
    await expect(page.locator('.fc-listMonth-button')).toBeVisible();

    // Year button must NOT be in the toolbar
    await expect(page.locator('.fc-yearView-button')).toHaveCount(0);

    // Buttons must have translated text (not English defaults)
    const monthBtn = page.locator('.fc-dayGridMonth-button');
    const btnText = await monthBtn.textContent();
    expect(btnText).not.toBe('month');
  });

  test('get_events endpoint returns a JSON array', async ({ page }) => {
    await login(page, ADMIN_USER);
    const response = await page.request.get(EVENTS_URL);
    expect(response.status()).toBe(200);
    const json = JSON.parse(await response.text());
    expect(Array.isArray(json)).toBeTruthy();
  });

  test('non-admin pilot is redirected away from calendar', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(CALENDAR_URL);
    await page.waitForLoadState('networkidle');
    expect(page.url()).not.toContain('deadlines_calendar');
  });

});
