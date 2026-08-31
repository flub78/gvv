/**
 * Playwright smoke tests for Messages du jour (MOTD) admin CRUD
 *
 * Tests:
 * - Admin can access the MOTD admin list
 * - Admin can create, edit and delete a message
 * - Cross-field validation (dates, target) rejects bad input
 * - Non-admin is denied access to the MOTD admin controller
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/motd-admin-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const LOGIN_URL = '/index.php/auth/login';
const LIST_URL = '/index.php/motd/page';
const CREATE_URL = '/index.php/motd/create';
const UPLOAD_URL = '/index.php/motd/upload_image';

// Smallest possible valid 1x1 transparent PNG.
const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

const DB_CONFIG = {
  host: 'localhost',
  user: 'gvv_user',
  password: 'lfoyfgbj',
  database: 'gvv2',
};

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

test.describe('MOTD Admin Smoke Tests', () => {

  test('admin can access the MOTD admin list', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(LIST_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);
    const body = await page.textContent('body');
    expect(body).not.toContain('name="username"');

    console.log('MOTD admin list loaded successfully');
  });

  test('admin can create, edit and delete a message', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(CREATE_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const title = 'Playwright smoke ' + Date.now();
    await page.fill('input[name="title"]', title);
    await page.fill('textarea[name="content"]', 'Contenu de test Playwright.');
    await page.fill('input[name="start_date"]', '01/01/2026');
    await page.fill('input[name="end_date"]', '31/12/2026');

    await page.click('#validate');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    await page.goto(LIST_URL);
    await page.waitForLoadState('networkidle');
    await expect(page.locator(`text=${title}`)).toBeVisible();

    // Edit
    const row = page.locator('tr', { hasText: title });
    await row.locator('a[href*="/motd/edit/"]').click();
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const editedTitle = title + ' (modifié)';
    await page.fill('input[name="title"]', editedTitle);
    // No confirm() dialog on this button (validation_button() only wires
    // the confirm onclick to the optional #delete button, not #validate).
    await page.click('#validate');
    await page.waitForLoadState('networkidle');

    await page.goto(LIST_URL);
    await page.waitForLoadState('networkidle');
    await expect(page.locator(`text=${editedTitle}`)).toBeVisible();

    // Delete
    const editedRow = page.locator('tr', { hasText: editedTitle });
    page.once('dialog', dialog => dialog.accept());
    await editedRow.locator('a[href*="/motd/delete/"]').click();
    await page.waitForLoadState('networkidle');

    await page.goto(LIST_URL);
    await page.waitForLoadState('networkidle');
    await expect(page.locator(`text=${editedTitle}`)).not.toBeVisible();

    console.log('MOTD create/edit/delete flow completed successfully');
  });

  test('rejects a message with end_date before start_date', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(CREATE_URL);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="title"]', 'Dates incoherentes');
    await page.fill('textarea[name="content"]', 'Contenu');
    await page.fill('input[name="start_date"]', '31/12/2026');
    await page.fill('input[name="end_date"]', '01/01/2026');
    await page.click('input[name="button"][value="Créer"]');
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');
    expect(body).toContain('postérieure ou égale');

    console.log('Incoherent dates correctly rejected');
  });

  test('non-admin is denied access to the MOTD admin list', async ({ page }) => {
    await login(page, PILOT_USER);

    const response = await page.goto(LIST_URL);
    await page.waitForLoadState('networkidle');

    expect(response.status()).toBe(404);

    console.log('Non-admin correctly denied access to MOTD admin list');
  });

  test('deleting a message removes its linked image file from disk', async ({ page }) => {
    await login(page, ADMIN_USER);

    const uploadResponse = await page.request.post(UPLOAD_URL, {
      multipart: {
        image_file: {
          name: 'delete-cascade-test.png',
          mimeType: 'image/png',
          buffer: Buffer.from(TINY_PNG_BASE64, 'base64'),
        },
      },
    });
    const uploadBody = await uploadResponse.json();
    const mediaId = parseInt(uploadBody.url.match(/media\/(\d+)/)[1], 10);

    const conn = await mysql.createConnection(DB_CONFIG);
    let messageId = null;

    try {
      const [[media]] = await conn.query('SELECT filename FROM motd_media WHERE id = ?', [mediaId]);
      const filePath = path.join(__dirname, '..', '..', 'uploads', 'motd', media.filename);
      expect(fs.existsSync(filePath)).toBe(true);

      // Create the message referencing the uploaded image in its content, so
      // the real post_create() -> link_uploaded_media() flow links it (not a
      // direct SQL shortcut).
      await page.goto(CREATE_URL);
      await page.waitForLoadState('networkidle');
      const title = 'Playwright delete-cascade ' + Date.now();
      await page.fill('input[name="title"]', title);
      await page.fill('textarea[name="content"]', `![img](${uploadBody.url})`);
      await page.fill('input[name="start_date"]', '01/01/2026');
      await page.fill('input[name="end_date"]', '31/12/2026');
      await page.click('#validate');
      await page.waitForLoadState('networkidle');

      const [[linkedMedia]] = await conn.query('SELECT message_id FROM motd_media WHERE id = ?', [mediaId]);
      expect(linkedMedia.message_id).not.toBeNull();
      messageId = linkedMedia.message_id;

      await page.goto(LIST_URL);
      await page.waitForLoadState('networkidle');
      const row = page.locator('tr', { hasText: title });
      page.once('dialog', dialog => dialog.accept());
      await row.locator('a[href*="/motd/delete/"]').click();
      await page.waitForLoadState('networkidle');

      expect(fs.existsSync(filePath)).toBe(false);

      const [mediaRows] = await conn.query('SELECT * FROM motd_media WHERE id = ?', [mediaId]);
      expect(mediaRows.length).toBe(0);
      messageId = null; // deleted through the UI, nothing left to clean up

      console.log('Message deletion removed its linked image file from disk');
    } finally {
      // Safety net: if an assertion above failed, the message/media/file may
      // still be around — remove them so the shared DB and uploads/motd/ are
      // left exactly as they were found.
      const [leftover] = await conn.query('SELECT filename FROM motd_media WHERE id = ?', [mediaId]);
      if (leftover.length > 0) {
        const strayPath = path.join(__dirname, '..', '..', 'uploads', 'motd', leftover[0].filename);
        try { if (fs.existsSync(strayPath)) fs.unlinkSync(strayPath); } catch (e) { /* www-data-owned: ignore */ }
        await conn.query('DELETE FROM motd_media WHERE id = ?', [mediaId]);
      }
      if (messageId) {
        await conn.query('DELETE FROM motd_media WHERE message_id = ?', [messageId]);
        await conn.query('DELETE FROM motd_messages WHERE id = ?', [messageId]);
      }
      await conn.end();
    }
  });

  test('non-admin cannot POST to motd formValidation endpoint', async ({ page }) => {
    await login(page, PILOT_USER);

    const response = await page.request.post('/index.php/motd/formValidation/1', {
      form: {
        title: 'Injection attempt',
        content: 'x',
        start_date: '01/01/2026',
        end_date: '31/12/2026',
        target_type: 'all',
        button: 'Créer',
      }
    });

    const body = await response.text();
    expect(body).not.toContain('Injection attempt');

    console.log('Non-admin correctly denied access to formValidation endpoint');
  });

});
