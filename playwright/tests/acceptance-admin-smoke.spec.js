/**
 * Playwright smoke test for Acceptance Admin feature
 *
 * Tests:
 * - Login as admin and navigate to acceptance admin page
 * - Access the items list page
 * - Access the create form (document picker step, then the pre-filled form)
 * - Create an item and verify it appears in the list
 *
 * Since the Lot 4 amendment (doc/plans/acceptations_reconnaissances_plan.md),
 * a new acceptance item must reference an already archived document: creation
 * always goes through the picker (bs_selectDocumentView.php) before reaching
 * the pre-filled form. This suite creates one fixture archived_documents row
 * to select from.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/acceptance-admin-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

// Test configuration
const LOGIN_URL = '/index.php/auth/login';
const ACCEPTANCE_ADMIN_URL = '/index.php/acceptance_admin/page';
const ACCEPTANCE_CREATE_URL = '/index.php/acceptance_admin/create';
const TEST_USER = {
  username: 'testadmin',
  password: 'password'
};
// testadmin has no membres row (application login only), so it cannot be
// selected in the target_user_login dropdown (Membres_model::selector()
// only lists actual club members) — use testuser to target an individual.
const MEMBER_LOGIN = 'testuser';
const FIXTURE_DOC_DESCRIPTION = 'Acceptance admin smoke fixture doc';

const DB_CONFIG = {
  host: 'localhost',
  user: 'gvv_user',
  password: 'lfoyfgbj',
  database: 'gvv2',
};

/**
 * Helper: login as admin
 */
async function loginAsAdmin(page) {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');
  await page.fill('input[name="username"]', TEST_USER.username);
  await page.fill('input[name="password"]', TEST_USER.password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');
}

/**
 * Helper: from the document picker step (ACCEPTANCE_CREATE_URL with no id),
 * pick the fixture document and land on the pre-filled create form.
 */
async function goToCreateFormForFixtureDocument(page, documentId) {
  await page.goto(ACCEPTANCE_CREATE_URL);
  await page.waitForLoadState('networkidle');
  await page.selectOption('#archived_document_id_select', String(documentId));
  await page.locator('#chooseDocumentBtn').click();
  await page.waitForLoadState('networkidle');
}

/**
 * Helper: check for PHP errors
 */
async function checkNoPhpErrors(page) {
  const bodyText = await page.textContent('body');
  expect(bodyText).not.toContain('Fatal error');
  expect(bodyText).not.toContain('Parse error');
  expect(bodyText).not.toContain('A PHP Error was encountered');
  expect(bodyText).not.toContain('An uncaught Exception was encountered');
}

// The controller has no delete action for acceptance_items (only
// activate/deactivate), so items created by this suite are removed directly
// in the database rather than through the UI.
async function cleanup(conn) {
  // motd_messages.source_ref is a soft link (no FK) to acceptance_items.id,
  // so it must be cleaned up before the item row disappears, or generated
  // messages targeting real members are left orphaned on the dashboard.
  await conn.query(
    "DELETE FROM motd_messages WHERE source_type = 'acceptance_item' AND source_ref IN " +
    "(SELECT id FROM acceptance_items WHERE title LIKE 'Test Acceptance Item %' OR title LIKE 'Tracking Test %')"
  );
  await conn.query("DELETE FROM acceptance_items WHERE title LIKE 'Test Acceptance Item %' OR title LIKE 'Tracking Test %'");
  await conn.query("DELETE FROM archived_documents WHERE description = ?", [FIXTURE_DOC_DESCRIPTION]);
}

test.describe.serial('Acceptance Admin Smoke Tests', () => {
  let conn;
  let fixtureDocumentId;

  test.beforeAll(async () => {
    conn = await mysql.createConnection(DB_CONFIG);
    await cleanup(conn);

    const [types] = await conn.query('SELECT id FROM document_types LIMIT 1');
    const [result] = await conn.query(
      `INSERT INTO archived_documents (document_type_id, original_filename, description,
         file_path, is_current_version, uploaded_by, uploaded_at, updated_by)
       VALUES (?, 'fixture.pdf', ?, 'uploads/documents/test/fixture.pdf', 1, ?, NOW(), ?)`,
      [types[0].id, FIXTURE_DOC_DESCRIPTION, TEST_USER.username, TEST_USER.username]
    );
    fixtureDocumentId = result.insertId;
  });

  test.afterAll(async () => {
    await cleanup(conn);
    await conn.end();
  });

  test('should access acceptance admin page after login', async ({ page }) => {
    await loginAsAdmin(page);

    // Navigate to acceptance admin
    await page.goto(ACCEPTANCE_ADMIN_URL);
    await page.waitForLoadState('networkidle');

    console.log('Navigated to:', page.url());

    // Check no PHP errors
    await checkNoPhpErrors(page);

    // Check page title is present
    const bodyText = await page.textContent('body');
    expect(bodyText).toContain('Administration des acceptations');

    console.log('Acceptance admin page loaded successfully');
  });

  test('should access create form', async ({ page }) => {
    await loginAsAdmin(page);

    // Step 1: the picker (no document chosen yet) — only the big select is present.
    await page.goto(ACCEPTANCE_CREATE_URL);
    await page.waitForLoadState('networkidle');

    console.log('Navigated to:', page.url());

    await checkNoPhpErrors(page);
    await expect(page.locator('#archived_document_id_select')).toBeVisible();
    await expect(page.locator('input[name="title"]')).toHaveCount(0);

    // Step 2: choosing the fixture document lands on the pre-filled form.
    await page.selectOption('#archived_document_id_select', String(fixtureDocumentId));
    await page.locator('#chooseDocumentBtn').click();
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // Check form elements are present
    await expect(page.locator('input[name="title"]')).toBeVisible();
    await expect(page.locator('input[name="archived_document_id"]')).toHaveCount(1);
    await expect(page.locator('select[name="mandatory_level"]')).toBeVisible();

    // Category is no longer a free choice for new items (amendment Lot 4):
    // forced to 'document' via a hidden field, no dropdown offered.
    await expect(page.locator('select[name="category"]')).toHaveCount(0);
    // No PDF upload either: the item references the archived document instead.
    await expect(page.locator('input[name="pdf_file"]')).toHaveCount(0);

    // target_type, dual_validation and role_1/role_2 are hidden (unused for now).
    await expect(page.locator('select[name="target_type"]')).toHaveCount(0);
    await expect(page.locator('input[name="dual_validation"]')).toHaveCount(0);
    await expect(page.locator('input[name="role_1"]')).toHaveCount(0);
    await expect(page.locator('input[name="role_2"]')).toHaveCount(0);

    // Role x section grid (replaces the former free-text target_roles field).
    await expect(page.locator('input[name="roles[]"]').first()).toBeVisible();

    console.log('Create form loaded with all fields');
  });

  test('should create an acceptance item', async ({ page }) => {
    await loginAsAdmin(page);

    await goToCreateFormForFixtureDocument(page, fixtureDocumentId);

    // Fill in the form (category is already forced to 'document')
    const itemTitle = 'Test Acceptance Item ' + Date.now();
    await page.fill('input[name="title"]', itemTitle);

    // Obligation level (replaces the former boolean "mandatory" checkbox).
    await page.selectOption('select[name="mandatory_level"]', 'mandatory_hard');

    // Target one role in the role x section grid.
    await page.locator('input[name="roles[]"]').first().check();

    // Submit the form
    // Click the submit button (first submit button, which is "Valider")
    await page.locator('button[type="submit"].btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    // Check no PHP errors
    await checkNoPhpErrors(page);

    // Should be redirected to the list page with success message
    const bodyText = await page.textContent('body');
    expect(bodyText).toContain('succès') ;

    console.log('Item created successfully');

    // Navigate to the list and verify the item appears
    await page.goto(ACCEPTANCE_ADMIN_URL);
    await page.waitForLoadState('networkidle');

    const listText = await page.textContent('body');
    expect(listText).toContain(itemTitle);

    console.log('Item appears in the list');

    // The checked role must have been persisted to acceptance_item_roles.
    const [items] = await conn.query('SELECT id FROM acceptance_items WHERE title = ?', [itemTitle]);
    expect(items.length).toBe(1);
    const [roleRows] = await conn.query('SELECT * FROM acceptance_item_roles WHERE item_id = ?', [items[0].id]);
    expect(roleRows.length).toBe(1);

    console.log('Role targeting persisted to acceptance_item_roles');
  });

  test('should delete an acceptance item from the list', async ({ page }) => {
    await loginAsAdmin(page);

    // Create an item to delete.
    await goToCreateFormForFixtureDocument(page, fixtureDocumentId);
    const itemTitle = 'Test Acceptance Item ' + Date.now();
    await page.fill('input[name="title"]', itemTitle);
    // Target a member individually rather than leaving it unrestricted:
    // an unrestricted item generates a message du jour for every active
    // member (Lot 3d.4), which would spam real accounts on this shared DB.
    await page.check('#target_mode_user');
    await page.selectOption('select[name="target_user_login"]', MEMBER_LOGIN);
    await page.locator('button[type="submit"].btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    await page.goto(ACCEPTANCE_ADMIN_URL);
    await page.waitForLoadState('networkidle');
    const row = page.locator('tr', { hasText: itemTitle });
    await expect(row).toBeVisible();

    const [itemsBefore] = await conn.query('SELECT id FROM acceptance_items WHERE title = ?', [itemTitle]);
    expect(itemsBefore.length).toBe(1);
    const itemId = itemsBefore[0].id;

    page.once('dialog', dialog => dialog.accept());
    await row.locator('a[title="Supprimer"]').click();
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);
    const bodyText = await page.textContent('body');
    expect(bodyText).toContain('supprimé');
    await expect(page.locator('tr', { hasText: itemTitle })).toHaveCount(0);

    const [itemsAfter] = await conn.query('SELECT id FROM acceptance_items WHERE id = ?', [itemId]);
    expect(itemsAfter.length).toBe(0);

    console.log('Item deleted successfully');
  });

  test('should access tracking view for an item', async ({ page }) => {
    await loginAsAdmin(page);

    // First create an item so we have something to track
    await goToCreateFormForFixtureDocument(page, fixtureDocumentId);

    const itemTitle = 'Tracking Test ' + Date.now();
    await page.fill('input[name="title"]', itemTitle);
    // Target a member individually rather than leaving it unrestricted:
    // an unrestricted item generates a message du jour for every active
    // member (Lot 3d.4), which would spam real accounts on this shared DB.
    await page.check('#target_mode_user');
    await page.selectOption('select[name="target_user_login"]', MEMBER_LOGIN);
    await page.locator('button[type="submit"].btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    // Go to the list to find the tracking button
    await page.goto(ACCEPTANCE_ADMIN_URL);
    await page.waitForLoadState('networkidle');

    // Check if there is a tracking button
    const trackingLink = page.locator('a[title="Suivi des acceptations"]').first();
    const trackingExists = await trackingLink.count();

    if (trackingExists > 0) {
      await trackingLink.click();
      await page.waitForLoadState('networkidle');

      // Check no PHP errors
      await checkNoPhpErrors(page);

      const bodyText = await page.textContent('body');
      expect(bodyText).toContain('Suivi des acceptations');

      console.log('Tracking view loaded successfully');
    } else {
      console.log('No tracking button found - skipping');
    }
  });
});
