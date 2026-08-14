/**
 * Playwright smoke test for creating an acceptance item from an already
 * archived document (action button on the archived documents list), instead
 * of uploading a new PDF specific to the acceptance.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/acceptance-from-archived-document-smoke.spec.js --reporter=line
 */

const path = require('path');
const fs = require('fs');
const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

const LOGIN_URL = '/index.php/auth/login';
const ARCHIVED_DOCS_CREATE_URL = '/index.php/archived_documents/create';
const ARCHIVED_DOCS_LIST_URL = '/index.php/archived_documents/page';
const ACCEPTANCE_ADMIN_URL = '/index.php/acceptance_admin/page';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
// testadmin has no membres row (application login only), so it cannot be
// selected in the target_user_login dropdown (Membres_model::selector()
// only lists actual club members) — use testuser to target an individual.
const MEMBER_LOGIN = 'testuser';
const PDF_FIXTURE = path.resolve(__dirname, '../../application/tests/data/attachments/documents/small_invoice_90kb.pdf');
const GVV_ROOT = path.resolve(__dirname, '../..');

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

async function checkNoPhpErrors(page) {
  const body = await page.textContent('body');
  expect(body).not.toContain('Fatal error');
  expect(body).not.toContain('Parse error');
  expect(body).not.toContain('A PHP Error was encountered');
  expect(body).not.toContain('An uncaught Exception was encountered');
}

// Neither controller exposes a UI delete for these test rows in this flow
// (acceptance_admin has no delete action), so cleanup goes straight to the
// database, mirroring the pattern used by motd-*-smoke.spec.js. Also removes
// the uploaded PDF from disk so failed/repeated runs don't leak files either.
async function cleanup(conn) {
  const [docs] = await conn.query(
    "SELECT file_path FROM archived_documents WHERE description LIKE 'Acceptance smoke doc %'"
  );
  for (const doc of docs) {
    if (!doc.file_path) continue;
    const abs = path.resolve(GVV_ROOT, doc.file_path.replace(/^\.\//, ''));
    fs.promises.unlink(abs).catch(() => {});
    // PDF thumbnail generated alongside the document (see pdf_thumbnail library).
    const thumb = path.join(path.dirname(abs), 'thumb_' + path.basename(abs, path.extname(abs)) + '.jpg');
    fs.promises.unlink(thumb).catch(() => {});
  }

  // motd_messages.source_ref is a soft link (no FK) to acceptance_items.id,
  // so it must be cleaned up before the item row disappears, or generated
  // messages targeting real members are left orphaned on the dashboard.
  await conn.query(
    "DELETE FROM motd_messages WHERE source_type = 'acceptance_item' AND source_ref IN " +
    "(SELECT id FROM acceptance_items WHERE title LIKE 'Acceptance from doc %')"
  );
  await conn.query("DELETE FROM acceptance_items WHERE title LIKE 'Acceptance from doc %'");
  await conn.query("DELETE FROM archived_documents WHERE description LIKE 'Acceptance smoke doc %'");
}

test.describe.serial('Acceptance from archived document smoke test', () => {
  let conn;

  test.beforeAll(async () => {
    conn = await mysql.createConnection(DB_CONFIG);
    await cleanup(conn);
  });

  test.afterAll(async () => {
    await cleanup(conn);
    await conn.end();
  });

  test('admin can create an acceptance item referencing an archived document', async ({ page }) => {
    await login(page, ADMIN_USER);

    // 1. Upload a PDF document via the admin archived documents form.
    await page.goto(ARCHIVED_DOCS_CREATE_URL);
    await page.waitForLoadState('networkidle');

    const description = 'Acceptance smoke doc ' + Date.now();
    await page.fill('input[name="description"]', description);
    await page.setInputFiles('input[name="userfile"]', PDF_FIXTURE);
    await page.locator('button[type="submit"].btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // 2. On the admin documents list, find the row for this document and use
    // the "view acceptances" action button.
    await page.goto(ARCHIVED_DOCS_LIST_URL);
    await page.waitForLoadState('networkidle');

    // The list defaults to the admin's active section filter; the document
    // just uploaded has no section (club-wide), so clear filters to find it.
    await page.click('#clear-filters');
    await page.waitForLoadState('networkidle');

    const row = page.locator('tr', { hasText: description });
    await expect(row).toBeVisible();

    const viewAcceptancesBtn = row.locator('a[href*="acceptance_admin/page?filter_archived_document_id="]');
    await expect(viewAcceptancesBtn).toBeVisible();
    await viewAcceptancesBtn.click();
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // 2bis. That list is scoped to the document and offers a "new acceptance
    // request for this document" button leading to the pre-filled create form.
    expect(page.url()).toContain('filter_archived_document_id=');
    const listBodyText = await page.textContent('body');
    expect(listBodyText).toContain(description);

    const createAcceptanceBtn = page.locator('a[href*="acceptance_admin/create/"]');
    await expect(createAcceptanceBtn).toBeVisible();
    await createAcceptanceBtn.click();
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // 3. The create form should be pre-filled: category locked to "document",
    // no PDF upload field, and the linked document shown read-only.
    expect(page.url()).toContain('/acceptance_admin/create/');
    await expect(page.locator('input[name="pdf_file"]')).toHaveCount(0);
    await expect(page.locator('input[name="archived_document_id"]')).toHaveCount(1);
    const bodyText = await page.textContent('body');
    expect(bodyText).toContain(description);

    // Version date is locked to the archived document's deposit date (today,
    // since it was just uploaded) and not free-text editable.
    const today = new Date();
    const expectedDate = String(today.getDate()).padStart(2, '0') + '/'
      + String(today.getMonth() + 1).padStart(2, '0') + '/' + today.getFullYear();
    await expect(page.locator('input[name="version_date"][type="hidden"]')).toHaveValue(expectedDate);
    await expect(page.locator('input[name="version_date"][type="text"]')).toHaveCount(0);

    // 4. Fill remaining required field and submit.
    const itemTitle = 'Acceptance from doc ' + Date.now();
    await page.fill('input[name="title"]', itemTitle);
    // Target a member individually rather than leaving it unrestricted: an
    // unrestricted item generates a message du jour for every active member
    // (Lot 3d.4), which would spam real accounts on this shared DB.
    await page.check('#target_mode_user');
    await page.selectOption('select[name="target_user_login"]', MEMBER_LOGIN);
    await page.locator('button[type="submit"].btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);
    const successText = await page.textContent('body');
    expect(successText).toContain('succès');

    // 5. The new item appears in the acceptance list with a download action
    // that delegates to the archived document (no separate PDF was uploaded).
    await page.goto(ACCEPTANCE_ADMIN_URL);
    await page.waitForLoadState('networkidle');

    const itemRow = page.locator('tr', { hasText: itemTitle });
    await expect(itemRow).toBeVisible();
    await expect(itemRow.locator('a[title="Télécharger PDF"]')).toHaveCount(1);

    console.log('Acceptance item created from archived document successfully');
  });
});
