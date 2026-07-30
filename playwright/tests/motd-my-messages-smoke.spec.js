/**
 * Playwright smoke tests for the "Tous mes messages" dedicated MOTD page
 *
 * Tests:
 * - The dashboard always exposes a link to the page, even when the
 *   collapsible dashboard section itself is absent (e.g. every active
 *   message has been hidden).
 * - The page lists every currently active message applicable to the user.
 *   A hidden message stays hidden (DB-backed, persists across reload/session)
 *   until the user clicks "Afficher les messages masqués", which reveals
 *   every hidden message again.
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

  test('hidden message stays hidden by default, and "Afficher les messages masqués" reveals it', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(MY_MESSAGES_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);

    // Hidden by default: the state is DB-backed, so it persists across
    // reload/session rather than being a one-off client-side dismissal.
    await expect(page.locator(`text=${titleHidden}`)).toHaveCount(0);
    await expect(page.locator(`text=${titleExpired}`)).toHaveCount(0);

    const showHiddenBtn = page.locator('#motdShowHiddenBtn');
    await expect(showHiddenBtn).toBeVisible();
    page.once('dialog', dialog => dialog.accept());
    const [unhideResponse] = await Promise.all([
      page.waitForResponse(resp => resp.url().includes('/unhide_all')),
      showHiddenBtn.click(),
    ]);
    expect(unhideResponse.ok()).toBeTruthy();
    await page.waitForLoadState('networkidle');

    await expect(page.locator(`text=${titleHidden}`)).toBeVisible();
    await expandIfCollapsed(page, titleHidden);
    await expect(page.locator('text=Reponse conservee malgre le masquage.')).toBeVisible();
    await expect(page.locator(`text=${titleExpired}`)).toHaveCount(0);

    // Restore the hidden fixture state so the remaining tests in this file
    // see the same starting point.
    await conn.query(
      'UPDATE motd_user_message_state SET hidden = 1 WHERE message_id = ? AND user_login = ?',
      [hiddenMsgId, PILOT_USER.username]
    );
  });

  test('empty state explains hidden messages exist once everything is hidden', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(MY_MESSAGES_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);

    // Hide every message currently visible (this suite's own fixture plus any
    // 'all'-targeted message a concurrent suite may have created). Done via
    // the same AJAX endpoint the "Masquer" button calls, rather than
    // clicking each button in turn, to avoid racing their fadeOut animation.
    const hideButtons = page.locator('.motd-hide-btn');
    const visibleCount = await hideButtons.count();
    const messageIds = [];
    for (let i = 0; i < visibleCount; i++) {
      messageIds.push(await hideButtons.nth(i).getAttribute('data-message-id'));
    }
    for (const messageId of messageIds) {
      await page.request.post(`/index.php/motd/hide_message/${messageId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
    }

    // The empty-state text is server-rendered; reload to see it as a
    // returning user would.
    await page.reload();
    await page.waitForLoadState('networkidle');
    await closeMod(page);

    await expect(page.locator('#motdShowHiddenBtn')).toBeVisible();
    await expect(page.locator("text=Vous n'avez aucun message non masqué")).toBeVisible();
    await expect(page.locator('text=Aucun message ne vous est actuellement adressé')).toHaveCount(0);

    // Restore visibility for the remaining tests / concurrent suites. Wait
    // for the unhide_all response itself (not just networkidle, which can
    // resolve before the async $.post fires, racing ahead of the DB write)
    // before touching the DB below.
    page.once('dialog', dialog => dialog.accept());
    const [unhideResponse] = await Promise.all([
      page.waitForResponse(resp => resp.url().includes('/unhide_all')),
      page.locator('#motdShowHiddenBtn').click(),
    ]);
    expect(unhideResponse.ok()).toBeTruthy();
    await page.waitForLoadState('networkidle');

    // Re-apply this file's own hidden fixture, as in the previous test.
    await conn.query(
      'UPDATE motd_user_message_state SET hidden = 1 WHERE message_id = ? AND user_login = ?',
      [hiddenMsgId, PILOT_USER.username]
    );
  });

  test('navigating from the dashboard link reaches the page', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);

    await page.click('#motdMineLink');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/motd\/mine/);
    // The hidden fixture message stays hidden here too; its presence is
    // reflected by the "show hidden" button rather than the message itself.
    await expect(page.locator('#motdShowHiddenBtn')).toBeVisible();
  });

  test('back button from the home dashboard returns home, not a stale section', async ({ page }) => {
    // Regression: visiting a section sub-dashboard leaves 'nav_from_url' in
    // session; going back to the plain home dashboard afterwards must reset
    // it, otherwise motd/mine's "Retour" button silently points to that old
    // section instead of the page the user actually came from (home).
    await login(page, PILOT_USER);
    await page.goto('/index.php/welcome/section/user');
    await page.waitForLoadState('networkidle');
    await closeMod(page);

    await page.goto(DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await closeMod(page);

    await page.click('#motdMineLink');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/motd\/mine/);

    const backLink = page.locator('#navBackLink');
    await expect(backLink).toHaveAttribute('href', /\/welcome$/);

    await backLink.click();
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/\/welcome$/);
  });
});
