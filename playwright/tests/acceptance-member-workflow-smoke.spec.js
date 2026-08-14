/**
 * Playwright smoke test for the member-facing acceptance workflow (Lot 4):
 * dashboard, read/scroll-gated accept, and history.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/acceptance-member-workflow-smoke.spec.js --reporter=line
 */

const path = require('path');
const fs = require('fs');
const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

const LOGIN_URL = '/index.php/auth/login';
const ARCHIVED_DOCS_CREATE_URL = '/index.php/archived_documents/create';
const ACCEPTANCE_CREATE_URL = '/index.php/acceptance_admin/create';
const ACCEPTANCE_DASHBOARD_URL = '/index.php/acceptance';
const ACCEPTANCE_HISTORY_URL = '/index.php/acceptance/history';
const WELCOME_URL = '/index.php/welcome';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const MEMBER_USER = { username: 'testuser', password: 'password' };
const PDF_FIXTURE = path.resolve(__dirname, '../../application/tests/data/attachments/documents/small_invoice_90kb.pdf');
const GVV_ROOT = path.resolve(__dirname, '../..');

const DB_CONFIG = {
  host: 'localhost',
  user: 'gvv_user',
  password: 'lfoyfgbj',
  database: 'gvv2',
};

async function login(page, user) {
  // Visiting /auth/login while already authenticated (e.g. switching from
  // admin to member within the same test) shows GVV's "already logged in"
  // page instead of the form, so the previous session must be closed first.
  await page.goto('/index.php/auth/logout');
  await page.waitForLoadState('networkidle');

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

async function cleanup(conn) {
  const [docs] = await conn.query(
    "SELECT file_path FROM archived_documents WHERE description LIKE 'Member workflow smoke doc %'"
  );
  for (const doc of docs) {
    if (!doc.file_path) continue;
    const abs = path.resolve(GVV_ROOT, doc.file_path.replace(/^\.\//, ''));
    fs.promises.unlink(abs).catch(() => {});
    const thumb = path.join(path.dirname(abs), 'thumb_' + path.basename(abs, path.extname(abs)) + '.jpg');
    fs.promises.unlink(thumb).catch(() => {});
  }

  // motd_messages.source_ref is a soft link (no FK) to acceptance_items.id,
  // so it must be cleaned up before the item row disappears.
  await conn.query(
    "DELETE FROM motd_messages WHERE source_type = 'acceptance_item' AND source_ref IN " +
    "(SELECT id FROM acceptance_items WHERE title LIKE 'Member workflow smoke %')"
  );

  // acceptance_records/acceptance_item_roles cascade-delete with their
  // parent acceptance_items row (ON DELETE CASCADE, migrations 068/170).
  await conn.query("DELETE FROM acceptance_items WHERE title LIKE 'Member workflow smoke %'");
  await conn.query("DELETE FROM archived_documents WHERE description LIKE 'Member workflow smoke doc %'");
}

test.describe.serial('Acceptance member workflow smoke test', () => {
  let conn;

  test.beforeAll(async () => {
    conn = await mysql.createConnection(DB_CONFIG);
    await cleanup(conn);
  });

  test.afterAll(async () => {
    await cleanup(conn);
    await conn.end();
  });

  test('member reads, scrolls, accepts an item and finds it in history', async ({ page }) => {
    // 1. Admin uploads a document and creates a mandatory acceptance item
    //    targeting the member individually.
    await login(page, ADMIN_USER);

    await page.goto(ARCHIVED_DOCS_CREATE_URL);
    await page.waitForLoadState('networkidle');
    const description = 'Member workflow smoke doc ' + Date.now();
    await page.fill('input[name="description"]', description);
    await page.setInputFiles('input[name="userfile"]', PDF_FIXTURE);
    await page.locator('button[type="submit"].btn-primary').first().click();
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const [docs] = await conn.query(
      'SELECT id FROM archived_documents WHERE description = ?',
      [description]
    );
    expect(docs.length).toBe(1);
    const archivedDocumentId = docs[0].id;

    await page.goto(ACCEPTANCE_CREATE_URL + '/' + archivedDocumentId);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const itemTitle = 'Member workflow smoke ' + Date.now();
    await page.fill('input[name="title"]', itemTitle);
    await page.selectOption('select[name="mandatory_level"]', 'mandatory_soft');
    await page.check('#target_mode_user');
    await page.selectOption('select[name="target_user_login"]', MEMBER_USER.username);
    await page.locator('button[type="submit"].btn-primary').first().click();
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const [items] = await conn.query('SELECT id FROM acceptance_items WHERE title = ?', [itemTitle]);
    expect(items.length).toBe(1);
    const itemId = items[0].id;

    // 2. Member sees the item on their dashboard and the menu badge.
    await login(page, MEMBER_USER);

    await page.goto(ACCEPTANCE_DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const dashboardCard = page.locator('.card', { hasText: itemTitle });
    await expect(dashboardCard).toBeVisible();
    await expect(dashboardCard.locator('.badge', { hasText: 'Obligatoire non bloquant' })).toBeVisible();

    // 2bis. A message du jour must have been generated for this member
    // targeting the item individually (Lot 3d.4, sync_target_motd()).
    await page.goto(WELCOME_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);
    const motdSection = page.locator('#motdSectionCard');
    await expect(motdSection).toContainText(itemTitle);

    const [motdRowsBeforeAccept] = await conn.query(
      "SELECT id, dismissible FROM motd_messages WHERE source_type = 'acceptance_item' AND source_ref = ? AND target_user_login = ?",
      [String(itemId), MEMBER_USER.username]
    );
    expect(motdRowsBeforeAccept.length).toBe(1);
    expect(motdRowsBeforeAccept[0].dismissible).toBe(0);

    // 3. Read page: Accept/Refuse must be hidden until the sentinel below
    //    the PDF iframe has been scrolled into view.
    await page.goto(ACCEPTANCE_DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    await page.locator('.card', { hasText: itemTitle }).locator('a', { hasText: 'Lire et accepter' }).click();
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const actions = page.locator('#acceptanceActions');
    await expect(actions).toHaveClass(/d-none/);

    await page.locator('#acceptanceReadSentinel').scrollIntoViewIfNeeded();
    await expect(actions).not.toHaveClass(/d-none/);

    await page.locator('#acceptanceAcceptBtn').click();
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const successText = await page.textContent('body');
    expect(successText).toContain('succès');

    // 3bis. The message du jour must be gone once this member has accepted.
    await page.goto(WELCOME_URL);
    await page.waitForLoadState('networkidle');
    const dashboardBody = await page.textContent('body');
    expect(dashboardBody).not.toContain(itemTitle);

    const [motdRowsAfterAccept] = await conn.query(
      "SELECT id FROM motd_messages WHERE source_type = 'acceptance_item' AND source_ref = ? AND target_user_login = ?",
      [String(itemId), MEMBER_USER.username]
    );
    expect(motdRowsAfterAccept.length).toBe(0);

    // 4. The item must no longer be on the dashboard...
    await page.goto(ACCEPTANCE_DASHBOARD_URL);
    await page.waitForLoadState('networkidle');
    const dashboardText = await page.textContent('body');
    expect(dashboardText).not.toContain(itemTitle);

    // ...and must appear in the history as accepted, with a working reread link.
    await page.goto(ACCEPTANCE_HISTORY_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const historyRow = page.locator('tr', { hasText: itemTitle });
    await expect(historyRow).toBeVisible();
    await expect(historyRow.locator('.badge', { hasText: 'Accepté' })).toBeVisible();

    await historyRow.locator('a', { hasText: 'Relire' }).click();
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);
    const rereadText = await page.textContent('body');
    expect(rereadText).toContain('Vous avez accepté cet élément le');

    // Confirm the DB row itself reflects the acceptance and its formula text.
    const [records] = await conn.query(
      "SELECT status, formula_text FROM acceptance_records WHERE item_id = ? AND user_login = ?",
      [itemId, MEMBER_USER.username]
    );
    expect(records.length).toBe(1);
    expect(records[0].status).toBe('accepted');
    expect(records[0].formula_text).toContain(itemTitle);

    console.log('Member acceptance workflow completed successfully');
  });
});
