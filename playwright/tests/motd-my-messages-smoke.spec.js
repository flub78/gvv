/**
 * Playwright smoke tests for the "Tous mes messages" dedicated MOTD page
 *
 * Tests:
 * - The dashboard always exposes a link to the page, even when the
 *   collapsible dashboard section itself is absent (e.g. every active
 *   message has been hidden).
 * - The page lists every currently active message applicable to the user,
 *   including ones already hidden from the dashboard section, with its replies.
 * - Expired / not-yet-started messages are never listed (policy from step 1).
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/motd-my-messages-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

const LOGIN_URL = '/index.php/auth/login';
const DASHBOARD_URL = '/index.php/welcome';
const MY_MESSAGES_URL = '/index.php/motd/mine';

const PILOT_USER = { username: 'obelix', password: 'password' };

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

async function expandIfCollapsed(page, title) {
  const header = page.locator('.accordion-button', { hasText: title });
  const collapsed = await header.evaluate(el => el.classList.contains('collapsed'));
  if (collapsed) {
    await header.click();
    await page.waitForTimeout(300);
  }
}

test.describe.serial('MOTD My Messages Smoke Tests', () => {
  let conn;
  let hiddenMsgId, expiredMsgId;
  const titleHidden = 'Playwright my-messages hidden ' + Date.now();
  const titleExpired = 'Playwright my-messages expired ' + Date.now();

  test.beforeAll(async () => {
    conn = await mysql.createConnection(DB_CONFIG);

    const [resHidden] = await conn.query(
      `INSERT INTO motd_messages (title, content, level, start_date, end_date, target_type, origin, created_by, updated_by)
       VALUES (?, 'Contenu masque mais toujours liste ici.', 'info', '2020-01-01 00:00:00', '2035-12-31 23:59:59', 'all', 'admin', 'testadmin', 'testadmin')`,
      [titleHidden]
    );
    hiddenMsgId = resHidden.insertId;
    await conn.query(
      `INSERT INTO motd_user_message_state (message_id, user_login, hidden, created_by, updated_by)
       VALUES (?, ?, 1, ?, ?)`,
      [hiddenMsgId, PILOT_USER.username, PILOT_USER.username, PILOT_USER.username]
    );
    await conn.query(
      `INSERT INTO motd_replies (message_id, author_login, content, created_by, updated_by)
       VALUES (?, ?, 'Reponse conservee malgre le masquage.', ?, ?)`,
      [hiddenMsgId, PILOT_USER.username, PILOT_USER.username, PILOT_USER.username]
    );

    const [resExpired] = await conn.query(
      `INSERT INTO motd_messages (title, content, level, start_date, end_date, target_type, origin, created_by, updated_by)
       VALUES (?, 'Message expire, ne doit jamais etre liste.', 'info', '2019-01-01 00:00:00', '2019-01-31 23:59:59', 'all', 'admin', 'testadmin', 'testadmin')`,
      [titleExpired]
    );
    expiredMsgId = resExpired.insertId;
  });

  test.afterAll(async () => {
    await conn.query('DELETE FROM motd_replies WHERE message_id IN (?, ?)', [hiddenMsgId, expiredMsgId]);
    await conn.query('DELETE FROM motd_user_message_state WHERE message_id IN (?, ?)', [hiddenMsgId, expiredMsgId]);
    await conn.query('DELETE FROM motd_messages WHERE id IN (?, ?)', [hiddenMsgId, expiredMsgId]);
    await conn.end();
  });

  test('dashboard always exposes the "Tous mes messages" link', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);

    // Other suites running concurrently may create their own 'all'-targeted
    // messages, visible to every user including this one; hide anything
    // currently shown (a per-user, per-suite action) so this assertion only
    // reflects our own fixture, which is already hidden in the DB.
    const hideAllBtn = page.locator('#motdHideAllBtn');
    if (await hideAllBtn.count() > 0) {
      page.once('dialog', dialog => dialog.accept());
      await hideAllBtn.click();
      await page.waitForLoadState('networkidle');
    }

    await expect(page.locator('#motdSectionBody')).toHaveCount(0);
    await expect(page.locator('#motdMineLink')).toBeVisible();
  });

  test('page lists the hidden message with its reply, and never the expired one', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(MY_MESSAGES_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);

    await expect(page.locator(`text=${titleHidden}`)).toBeVisible();
    await expandIfCollapsed(page, titleHidden);
    await expect(page.locator('text=Reponse conservee malgre le masquage.')).toBeVisible();
    await expect(page.locator(`text=${titleExpired}`)).toHaveCount(0);
  });

  test('navigating from the dashboard link reaches the page', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);

    await page.click('#motdMineLink');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/motd\/mine/);
    await expect(page.locator(`text=${titleHidden}`)).toBeVisible();
  });
});
