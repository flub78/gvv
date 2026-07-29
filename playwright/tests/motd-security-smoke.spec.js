/**
 * Playwright smoke tests for Messages du jour (MOTD) security hardening
 * (plan step 11: XSS, access control, invalid uploads, malformed requests)
 *
 * Tests:
 * - Message/reply content is rendered as safe Markdown: raw HTML (script,
 *   event handlers, javascript: URLs) never reaches the DOM as live markup.
 * - A user who is neither the message's recipient, its author, nor an admin
 *   cannot hide it, reply to it, nor read it via the AJAX endpoints, nor
 *   fetch an image attached to it via the controlled media endpoint.
 * - The reply endpoint rejects empty content and an unknown message id.
 * - The sort-preference endpoint rejects an invalid value.
 * - The image upload endpoint rejects a file whose real content does not
 *   match its claimed image extension (MIME sniffing, not just extension).
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/motd-security-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

const LOGIN_URL = '/index.php/auth/login';
const UPLOAD_URL = '/index.php/motd/upload_image';

// Smallest possible valid 1x1 transparent PNG, used to exercise the real
// upload endpoint (uploads/motd/ is only writable by the www-data PHP
// process, not by this test runner, so the fixture media file must be
// created through the app itself rather than written directly to disk).
const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
// Neither the author, the target, nor an admin: must be denied everywhere.
const OUTSIDER_USER = { username: 'abraracourcix', password: 'password' };
const TARGET_USER = { username: 'assurancetourix', password: 'password' };

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

async function expandIfCollapsed(page, title) {
  const header = page.locator('.accordion-button', { hasText: title });
  const collapsed = await header.evaluate(el => el.classList.contains('collapsed'));
  if (collapsed) {
    await header.click();
    await page.waitForTimeout(300);
  }
}

test.describe.serial('MOTD Security Smoke Tests', () => {
  let conn;
  let xssMsgId, privateMsgId, mediaId, mediaFilename;
  const xssTitle = 'Playwright security xss ' + Date.now();
  const privateTitle = 'Playwright security private ' + Date.now();

  test.beforeAll(async ({ browser }) => {
    conn = await mysql.createConnection(DB_CONFIG);

    const [resXss] = await conn.query(
      `INSERT INTO motd_messages (title, content, level, start_date, end_date, target_type, origin, created_by, updated_by)
       VALUES (?, ?, 'info', '2020-01-01 00:00:00', '2035-12-31 23:59:59', 'all', 'admin', 'testadmin', 'testadmin')`,
      [xssTitle, "<script>window.__motdXss = true;</script>\n\n<img src=x onerror=\"window.__motdXss = true\">\n\n[link](javascript:alert(1))"]
    );
    xssMsgId = resXss.insertId;

    const [resPrivate] = await conn.query(
      `INSERT INTO motd_messages (title, content, level, start_date, end_date, target_type, target_user_login, origin, created_by, updated_by)
       VALUES (?, 'Message prive.', 'info', '2020-01-01 00:00:00', '2035-12-31 23:59:59', 'user', ?, 'admin', 'testadmin', 'testadmin')`,
      [privateTitle, TARGET_USER.username]
    );
    privateMsgId = resPrivate.insertId;

    // Real image, uploaded through the app's own endpoint (as admin) so the
    // file actually lands in uploads/motd/ with correct ownership, rather
    // than being written directly to that www-data-owned directory.
    const setupPage = await browser.newPage();
    await login(setupPage, ADMIN_USER);
    const uploadResponse = await setupPage.request.post(UPLOAD_URL, {
      multipart: {
        image_file: {
          name: 'security-test.png',
          mimeType: 'image/png',
          buffer: Buffer.from(TINY_PNG_BASE64, 'base64'),
        },
      },
    });
    const uploadBody = await uploadResponse.json();
    mediaId = parseInt(uploadBody.url.match(/media\/(\d+)/)[1], 10);
    await setupPage.close();

    await conn.query('UPDATE motd_media SET message_id = ? WHERE id = ?', [privateMsgId, mediaId]);
  });

  test.afterAll(async () => {
    const [[media]] = await conn.query('SELECT filename FROM motd_media WHERE id = ?', [mediaId]);
    await conn.query('DELETE FROM motd_media WHERE id = ?', [mediaId]);
    await conn.query('DELETE FROM motd_replies WHERE message_id IN (?, ?)', [xssMsgId, privateMsgId]);
    await conn.query('DELETE FROM motd_user_message_state WHERE message_id IN (?, ?)', [xssMsgId, privateMsgId]);
    await conn.query('DELETE FROM motd_messages WHERE id IN (?, ?)', [xssMsgId, privateMsgId]);
    await conn.end();
    if (media) {
      await require('fs').promises.unlink(
        require('path').join(__dirname, '..', '..', 'uploads', 'motd', media.filename)
      ).catch(() => {});
    }
  });

  test('message content is rendered as escaped text, not live HTML', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto('/index.php/motd/mine');
    await page.waitForLoadState('networkidle');
    await expandIfCollapsed(page, xssTitle);

    // No <script>/onerror ever executed.
    const xssFired = await page.evaluate(() => window.__motdXss === true);
    expect(xssFired).toBe(false);

    // No live <script> element was inserted for our message, and the
    // dangerous markup is visible only as literal escaped text.
    await expect(page.locator('script:has-text("__motdXss")')).toHaveCount(0);
    await expect(page.locator('text=window.__motdXss = true').first()).toBeVisible();
  });

  test('an unrelated user cannot hide, read or reply to a message not addressed to them', async ({ page }) => {
    await login(page, OUTSIDER_USER);

    const hideResponse = await page.request.post(`/index.php/motd/hide_message/${privateMsgId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    expect(hideResponse.status()).toBe(404);

    const ackResponse = await page.request.post(`/index.php/motd/acknowledge_message/${privateMsgId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    expect(ackResponse.status()).toBe(404);

    const replyResponse = await page.request.post(`/index.php/motd/reply/${privateMsgId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      form: { content: 'Tentative non autorisee' },
    });
    expect(replyResponse.status()).toBe(404);

    // The reply must not have been created despite the 404.
    const [rows] = await conn.query('SELECT COUNT(*) AS n FROM motd_replies WHERE message_id = ?', [privateMsgId]);
    expect(rows[0].n).toBe(0);

    await page.goto('/index.php/motd/mine');
    await page.waitForLoadState('networkidle');
    await expect(page.locator(`text=${privateTitle}`)).toHaveCount(0);
  });

  test('reply endpoint rejects empty content and an unknown message id', async ({ page }) => {
    await login(page, ADMIN_USER);

    const emptyResponse = await page.request.post(`/index.php/motd/reply/${xssMsgId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      form: { content: '   ' },
    });
    expect(emptyResponse.status()).toBe(422);
    const emptyBody = await emptyResponse.json();
    expect(emptyBody.success).toBe(false);

    const unknownResponse = await page.request.post('/index.php/motd/reply/999999999', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      form: { content: 'x' },
    });
    expect(unknownResponse.status()).toBe(404);
  });

  test('set_sort endpoint rejects an invalid sort value', async ({ page }) => {
    await login(page, ADMIN_USER);

    const response = await page.request.post('/index.php/motd/set_sort', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      form: { sort_by: 'not_a_valid_value' },
    });
    expect(response.status()).toBe(422);
  });

  test('an outsider cannot fetch the message image', async ({ page }) => {
    await login(page, OUTSIDER_USER);
    const deniedResponse = await page.request.get(`/index.php/motd/media/${mediaId}`);
    expect(deniedResponse.status()).toBe(404);
  });

  test('the message target can fetch the message image', async ({ page }) => {
    await login(page, TARGET_USER);
    const allowedResponse = await page.request.get(`/index.php/motd/media/${mediaId}`);
    expect(allowedResponse.status()).toBe(200);
  });

  test('image upload rejects a file whose content does not match its image extension', async ({ page }) => {
    await login(page, ADMIN_USER);

    const response = await page.request.post(UPLOAD_URL, {
      multipart: {
        image_file: {
          name: 'fake.png',
          mimeType: 'image/png',
          buffer: Buffer.from('This is a plain text file pretending to be a PNG image.'),
        },
      },
    });

    const body = await response.json().catch(() => ({}));
    expect(body.url).toBeUndefined();
    expect(body.error).toBeTruthy();
  });
});
