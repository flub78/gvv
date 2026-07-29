/**
 * Playwright smoke tests for the Messages du jour (MOTD) dashboard section
 *
 * Tests:
 * - No active message => the section is not rendered
 * - An urgent unread message => the section is expanded by default, the
 *   message and its replies are visible
 * - Sort criterion selection is persisted via AJAX
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/motd-dashboard-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

const LOGIN_URL = '/index.php/auth/login';
const DASHBOARD_URL = '/index.php/welcome';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const PILOT_USER = { username: 'testuser', password: 'password' };

const DB_CONFIG = {
  host: 'localhost',
  user: 'gvv_user',
  password: 'lfoyfgbj',
  database: 'gvv2',
};

async function login(page, user) {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');
  await page.fill('input[name="username"]', user.username);
  await page.fill('input[name="password"]', user.password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');
}

async function closeMod(page) {
  const ok = page.locator('.ui-dialog button:has-text("OK")');
  if (await ok.count() > 0) {
    await ok.click();
  }
}

async function checkNoPhpErrors(page) {
  const body = await page.textContent('body');
  expect(body).not.toContain('Fatal error');
  expect(body).not.toContain('Parse error');
  expect(body).not.toContain('A PHP Error was encountered');
  expect(body).not.toContain('An uncaught Exception was encountered');
}

async function cleanup(conn) {
  await conn.query("DELETE FROM motd_replies WHERE message_id IN (SELECT id FROM motd_messages WHERE title LIKE 'Playwright dashboard smoke%')");
  await conn.query("DELETE FROM motd_user_message_state WHERE message_id IN (SELECT id FROM motd_messages WHERE title LIKE 'Playwright dashboard smoke%')");
  await conn.query("DELETE FROM motd_messages WHERE title LIKE 'Playwright dashboard smoke%'");
  await conn.query("DELETE FROM motd_user_prefs WHERE user_login = 'testuser'");
}

test.describe.serial('MOTD Dashboard Smoke Tests', () => {
  let conn;
  let messageId;

  test.beforeAll(async () => {
    conn = await mysql.createConnection(DB_CONFIG);
    await cleanup(conn);
  });

  test.afterAll(async () => {
    await cleanup(conn);
    await conn.end();
  });

  test('no active message => no MOTD section on the dashboard', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);
    await checkNoPhpErrors(page);

    // Other suites running concurrently may have their own active messages
    // visible to testuser; hide anything currently shown so this test only
    // asserts its own scenario (no *unhidden* message left => no section).
    const hideAllBtn = page.locator('#motdHideAllBtn');
    if (await hideAllBtn.count() > 0) {
      page.once('dialog', dialog => dialog.accept());
      await hideAllBtn.click();
      await page.waitForLoadState('networkidle');
    }

    await expect(page.locator('#motdSectionBody')).toHaveCount(0);
  });

  test('urgent unread message is expanded by default with its reply', async ({ page }) => {
    const [result] = await conn.query(
      `INSERT INTO motd_messages (title, content, level, start_date, end_date, target_type, origin, created_by, updated_by)
       VALUES (?, ?, 'urgent', '2020-01-01 00:00:00', '2035-12-31 23:59:59', 'all', 'admin', 'testadmin', 'testadmin')`,
      ['Playwright dashboard smoke ' + Date.now(), 'Contenu urgent de test.']
    );
    messageId = result.insertId;
    await conn.query(
      `INSERT INTO motd_replies (message_id, author_login, content, created_by, updated_by)
       VALUES (?, 'testuser', 'Réponse de test.', 'testuser', 'testuser')`,
      [messageId]
    );

    await login(page, PILOT_USER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);
    await checkNoPhpErrors(page);

    await expect(page.locator('#motdSectionBody')).toHaveClass(/show/);
    await expect(page.locator('text=Contenu urgent de test')).toBeVisible();
    await expect(page.locator('text=Réponse de test')).toBeVisible();
  });

  test('sort selection is persisted', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);

    await Promise.all([
      page.waitForNavigation(),
      page.selectOption('#motdSortSelect', 'date'),
    ]);
    await page.waitForLoadState('networkidle');

    const [rows] = await conn.query("SELECT sort_by FROM motd_user_prefs WHERE user_login = 'testuser'");
    expect(rows[0].sort_by).toBe('date');
  });
});
