/**
 * Playwright smoke tests for Deadlines Calendar feature
 *
 * Tests:
 * - Admin can access the calendar page
 * - FullCalendar toolbar and view buttons are rendered
 * - JSON events endpoint returns a valid array
 * - Non-admin pilot is redirected away
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/deadlines-calendar.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL     = '/index.php/auth/login';
const CALENDAR_URL  = '/index.php/deadlines_calendar';
const EVENTS_URL    = '/index.php/deadlines_calendar/get_events';

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

test.describe('Deadlines Calendar Smoke Tests', () => {

  test('admin can access the deadlines calendar page', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(CALENDAR_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // FullCalendar adds the .fc class to the #calendar element itself
    await expect(page.locator('#calendar')).toBeVisible();
    await expect(page.locator('.fc-toolbar')).toBeVisible();
  });

  test('calendar toolbar with navigation buttons is rendered', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(CALENDAR_URL);
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.fc-toolbar')).toBeVisible();
    await expect(page.locator('.fc-prev-button')).toBeVisible();
    await expect(page.locator('.fc-next-button')).toBeVisible();
    await expect(page.locator('.fc-today-button')).toBeVisible();
  });

  test('calendar view buttons are present', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(CALENDAR_URL);
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.fc-dayGridMonth-button')).toBeVisible();
    await expect(page.locator('.fc-listMonth-button')).toBeVisible();
  });

  test('get_events endpoint returns a JSON array', async ({ page }) => {
    await login(page, ADMIN_USER);

    const response = await page.request.get(EVENTS_URL);
    expect(response.status()).toBe(200);

    const body = await response.text();
    let json;
    try {
      json = JSON.parse(body);
    } catch (e) {
      throw new Error('get_events did not return valid JSON: ' + body.substring(0, 200));
    }
    expect(Array.isArray(json)).toBeTruthy();
  });

  test('year view button is present and toggles year grid', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(CALENDAR_URL);
    await page.waitForLoadState('networkidle');

    // The custom year view button must appear in the toolbar
    await expect(page.locator('.fc-yearView-button')).toBeVisible();

    // Click it
    await page.locator('.fc-yearView-button').click();
    await page.waitForLoadState('networkidle');

    // FullCalendar calendar div should be hidden, year view visible
    await expect(page.locator('#year-view')).toBeVisible();
    await expect(page.locator('#calendar')).toBeHidden();

    // 12 month cards must be rendered
    const monthCards = page.locator('.year-month-card');
    await expect(monthCards).toHaveCount(12);

    // Year title must be a 4-digit year
    const titleText = await page.locator('#year-view-title').textContent();
    expect(titleText).toMatch(/^\d{4}$/);

    // prev/next year buttons work
    await page.locator('#year-prev-btn').click();
    await page.waitForTimeout(500);
    const prevYear = parseInt(titleText) - 1;
    await expect(page.locator('#year-view-title')).toHaveText(String(prevYear));
  });

  test('non-admin pilot is redirected away from calendar', async ({ page }) => {
    await login(page, PILOT_USER);

    await page.goto(CALENDAR_URL);
    await page.waitForLoadState('networkidle');

    // Should have been redirected — URL must not contain 'deadlines_calendar'
    expect(page.url()).not.toContain('deadlines_calendar');
  });

});
