/**
 * Playwright smoke tests for user actions on Messages du jour (MOTD)
 *
 * Tests:
 * - Acknowledge a message ("j'ai pris connaissance") and reply to another
 * - Hide a single message
 * - Admin replies to an existing reply (nested reply, visible to all)
 * - Hide all remaining active messages
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/motd-user-actions-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

const LOGIN_URL = '/index.php/auth/login';
const DASHBOARD_URL = '/index.php/welcome';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const PILOT_USER = { username: 'asterix', password: 'password' };

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

// The outer MOTD section may render collapsed once no unread urgent/important
// message remains (it then falls back to the user's persisted preference).
// Individual actions are tested regardless of that preference, so expand it
// first if needed.
async function expandSectionIfCollapsed(page) {
  const section = page.locator('#motdSectionBody');
  const expanded = await section.evaluate(el => el.classList.contains('show'));
  if (!expanded) {
    await page.locator('.card-header', { hasText: 'Messages du jour' }).click();
    await page.waitForTimeout(300);
  }
}

test.describe.serial('MOTD User Actions Smoke Tests', () => {
  let conn;
  let msgAId, msgBId;
  const titleA = 'Playwright user-actions A ' + Date.now();
  const titleB = 'Playwright user-actions B ' + Date.now();

  test.beforeAll(async () => {
    conn = await mysql.createConnection(DB_CONFIG);
    const [resA] = await conn.query(
      `INSERT INTO motd_messages (title, content, level, start_date, end_date, target_type, origin, created_by, updated_by)
       VALUES (?, 'Contenu A', 'urgent', '2020-01-01 00:00:00', '2035-12-31 23:59:59', 'all', 'admin', 'testadmin', 'testadmin')`,
      [titleA]
    );
    msgAId = resA.insertId;
    const [resB] = await conn.query(
      `INSERT INTO motd_messages (title, content, level, start_date, end_date, target_type, origin, created_by, updated_by)
       VALUES (?, 'Contenu B', 'info', '2020-01-01 00:00:00', '2035-12-31 23:59:59', 'all', 'admin', 'testadmin', 'testadmin')`,
      [titleB]
    );
    msgBId = resB.insertId;
  });

  test.afterAll(async () => {
    await conn.query('DELETE FROM motd_replies WHERE message_id IN (?, ?)', [msgAId, msgBId]);
    await conn.query('DELETE FROM motd_user_message_state WHERE message_id IN (?, ?)', [msgAId, msgBId]);
    await conn.query('DELETE FROM motd_messages WHERE id IN (?, ?)', [msgAId, msgBId]);
    await conn.end();
  });

  test('user acknowledges a message and replies to another', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);
    await expandSectionIfCollapsed(page);

    await expect(page.locator(`text=${titleA}`)).toBeVisible();
    await expect(page.locator(`text=${titleB}`)).toBeVisible();

    const msgAItem = page.locator('.accordion-item', { hasText: titleA });
    await msgAItem.locator('.motd-ack-btn').click();
    await page.waitForTimeout(500);
    await expect(msgAItem.locator('.badge.bg-success')).toBeVisible();

    await expandIfCollapsed(page, titleB);
    const msgBItem = page.locator('.accordion-item', { hasText: titleB });
    await msgBItem.locator('.motd-reply-textarea').fill('Réponse de asterix');
    await msgBItem.locator('.motd-reply-submit-btn').click();
    await page.waitForTimeout(500);
    await expect(page.locator('text=Réponse de asterix')).toBeVisible();

    const [rows] = await conn.query(
      'SELECT acknowledged FROM motd_user_message_state WHERE message_id = ? AND user_login = ?',
      [msgAId, 'asterix']
    );
    expect(rows[0].acknowledged).toBe(1);
  });

  test('user hides a single message', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);
    await expandSectionIfCollapsed(page);

    // Baseline "Afficher tous les messages" hidden-count badge, and a marker
    // that only survives if the click below does NOT trigger a full page
    // reload (regression check for review finding #3).
    const hiddenCountBefore = await page.locator('#motdHiddenCountBadge').count()
      ? parseInt(await page.locator('#motdHiddenCountBadge').textContent(), 10)
      : 0;
    await page.evaluate(() => { window.__noReloadMarker = true; });

    await expandIfCollapsed(page, titleA);
    const msgAItem = page.locator('.accordion-item', { hasText: titleA });
    await msgAItem.locator('.motd-hide-btn').click();
    await page.waitForTimeout(600);

    await expect(page.locator(`text=${titleA}`)).not.toBeVisible();
    await expect(page.locator(`text=${titleB}`)).toBeVisible();

    // The hidden-count badge must update immediately, without a reload.
    expect(await page.evaluate(() => window.__noReloadMarker)).toBe(true);
    await expect(page.locator('#motdHiddenCountBadge')).toHaveText(String(hiddenCountBefore + 1));
  });

  test('admin replies to the existing reply (nested, visible to all)', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);
    await expandSectionIfCollapsed(page);

    await expandIfCollapsed(page, titleB);
    const msgBItem = page.locator('.accordion-item', { hasText: titleB });
    await expect(msgBItem.locator('text=Réponse de asterix')).toBeVisible();

    const replyToBtn = msgBItem.locator('.motd-reply-to-btn').first();
    await expect(replyToBtn).toBeVisible();
    await replyToBtn.click();
    await expect(msgBItem.locator('.motd-reply-replying-to')).toBeVisible();

    await msgBItem.locator('.motd-reply-textarea').fill('Réponse admin à asterix');
    await msgBItem.locator('.motd-reply-submit-btn').click();
    await page.waitForTimeout(500);
    await expect(page.locator('text=Réponse admin à asterix')).toBeVisible();

    const [rows] = await conn.query(
      'SELECT parent_reply_id FROM motd_replies WHERE author_login = ? AND message_id = ?',
      ['testadmin', msgBId]
    );
    expect(rows[0].parent_reply_id).not.toBeNull();
  });

  test('user hides all remaining messages', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);
    await expandSectionIfCollapsed(page);

    await page.locator('#motdHideAllBtn').click();
    await page.waitForLoadState('networkidle');

    await expect(page.locator(`text=${titleB}`)).not.toBeVisible();
  });
});
