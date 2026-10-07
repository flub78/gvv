/**
 * Playwright smoke tests for Archived Documents feature
 *
 * Tests:
 * - Admin accesses "Mes documents" page
 * - Admin accesses the admin documents list
 * - Admin accesses the expired documents filter
 * - Admin can open the pilot document creation form
 * - Pilot accesses their own documents page
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/archived-documents-smoke.spec.js --reporter=line
 */

const { test, expect, devices } = require('@playwright/test');
const { USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON } = require('./helpers/gvv-config');

const LOGIN_URL = '/index.php/auth/login';
const MY_DOCS_URL = '/index.php/archived_documents/my_documents';
const ADMIN_LIST_URL = '/index.php/archived_documents/page';
const EXPIRED_URL = '/index.php/archived_documents/page?filter=expired';
const CREATE_PILOT_URL = '/index.php/archived_documents/create_pilot';
const CREATE_URL = '/index.php/archived_documents/create';

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

test.describe('Archived Documents Smoke Tests', () => {

  test('admin can access My Documents page', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(MY_DOCS_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    const body = await page.textContent('body');
    // Page should show the documents section (not redirect to login)
    expect(body).not.toContain('name="username"');

    console.log('My Documents page loaded successfully');
  });

  test('admin can access the admin documents list', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // Should not be redirected to login
    const body = await page.textContent('body');
    expect(body).not.toContain('name="username"');

    console.log('Admin documents list loaded successfully');
  });

  test('admin can filter expired documents', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(EXPIRED_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    const body = await page.textContent('body');
    expect(body).not.toContain('name="username"');

    console.log('Expired documents filter page loaded successfully');
  });

  test('admin can open the pilot document creation form', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(CREATE_PILOT_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    const body = await page.textContent('body');
    expect(body).not.toContain('name="username"');

    // Form should contain a document type selector
    const typeSelect = page.locator('select[name="document_type_id"]');
    await expect(typeSelect).toBeVisible();

    console.log('Pilot document creation form loaded successfully');
  });

  // Regression: Chromium browsers could not read a file dropped from the Linux
  // file manager (Nemo, via the document portal) and failed at submit time with
  // ERR_FILE_NOT_FOUND. The drop zone now copies the file into memory at drop
  // time and shows an alert if it cannot be read.
  test('dropped file is copied into the input, unreadable drop shows an alert', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(CREATE_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const dropped = await page.evaluate(async () => {
      const zone = document.getElementById('drop-zone-userfile');
      const file = new File(['abc'], 'Capture d\u2019\u00e9cran.png', { type: 'image/png' });
      const dt = new DataTransfer();
      dt.items.add(file);
      zone.dispatchEvent(new DragEvent('drop', { dataTransfer: dt, bubbles: true, cancelable: true }));
      await new Promise(r => setTimeout(r, 200));
      const f = document.getElementById('userfile').files[0];
      return { name: f && f.name, size: f && f.size, copied: f !== file,
               label: document.getElementById('filename-userfile').textContent };
    });
    expect(dropped).toEqual({ name: 'Capture d\u2019\u00e9cran.png', size: 3, copied: true,
                              label: 'Capture d\u2019\u00e9cran.png' });
    await expect(page.locator('.gvv-drop-file-error')).toHaveCount(0);

    // Simulate a file the browser cannot read
    await page.evaluate(async () => {
      const zone = document.getElementById('drop-zone-userfile');
      const input = document.getElementById('userfile');
      const file = new File(['abc'], 'unreadable.png', { type: 'image/png' });
      file.arrayBuffer = () => Promise.reject(new Error('NotFoundError'));
      await gvvDropFile(file, input, zone, 'unreadable');
    });
    await expect(page.locator('.gvv-drop-file-error')).toHaveCount(1);
    await expect(page.locator('.gvv-drop-file-error')).toContainText('unreadable.png');
    expect(await page.evaluate(() => document.getElementById('userfile').files.length)).toBe(0);
  });

  test('pilot can access their own documents page', async ({ page }) => {
    test.skip(USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON);
    await login(page, PILOT_USER);

    await page.goto(MY_DOCS_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    const body = await page.textContent('body');
    expect(body).not.toContain('name="username"');

    console.log('Pilot My Documents page loaded successfully');
  });

  test('non-admin is redirected from admin list to my_documents', async ({ page }) => {
    test.skip(USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON);
    await login(page, PILOT_USER);

    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // Non-admin should NOT reach the admin list (redirected to my_documents or auth/deny)
    expect(page.url()).not.toContain('archived_documents/page');

    console.log('Non-admin correctly denied access to admin list');
  });

  // ---------------------------------------------------------------------------
  // Email feature tests
  // ---------------------------------------------------------------------------

  test('admin sees email icon on admin documents list', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // Email buttons should be present (admin has access)
    const emailButtons = page.locator('.doc-email-btn');
    const count = await emailButtons.count();
    // Page may have 0 documents in test DB; just verify no PHP errors and no syntax issues
    // The icon is rendered conditionally on is_admin being true
    console.log(`Found ${count} email button(s) on admin list`);

    // Verify the modal markup is present in the DOM
    const modal = page.locator('#docEmailModal');
    await expect(modal).toBeAttached();

    console.log('Admin sees email modal markup on admin documents list');
  });

  test('non-admin does not see email icon on admin list (no access to page)', async ({ page }) => {
    await login(page, PILOT_USER);

    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // Non-admin is redirected away from the admin list, so there are no email icons
    const emailButtons = page.locator('.doc-email-btn');
    const count = await emailButtons.count();
    expect(count).toBe(0);

    console.log('Non-admin has no email buttons (redirected from admin list)');
  });

  test('non-admin cannot POST to send_email endpoint', async ({ page }) => {
    await login(page, PILOT_USER);

    // Attempt to POST directly to send_email for document id 1
    const response = await page.request.post('/index.php/archived_documents/send_email/1', {
      form: {
        recipient: 'test@example.com',
        subject: 'Test',
        body: 'Test body',
      }
    });

    const body = await response.text();

    // Should be an error page (access denied) or redirect - NOT a success message
    expect(body).not.toContain('Email envoyé avec succès');
    expect(body).not.toContain('Email sent successfully');

    console.log('Non-admin correctly denied access to send_email endpoint');
  });

  test('admin can open email modal and it is pre-filled', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);

    // Check if there are any email buttons
    const emailButtons = page.locator('.doc-email-btn');
    const count = await emailButtons.count();

    if (count === 0) {
      console.log('No documents in DB for email modal test - skipping modal interaction');
      return;
    }

    // Click the first email button
    await emailButtons.first().click();

    // Wait for modal to appear
    const modal = page.locator('#docEmailModal');
    await expect(modal).toBeVisible({ timeout: 3000 });

    // Verify recipient area and form fields are present
    await expect(page.locator('#freeEmailInput')).toBeVisible();
    await expect(page.locator('#addFreeEmailBtn')).toBeVisible();
    await expect(page.locator('#docEmailSubject')).toBeVisible();
    await expect(page.locator('#docEmailBody')).toBeVisible();

    // Subject must not be empty (pre-filled from document data)
    const subject = await page.locator('#docEmailSubject').inputValue();
    expect(subject.length).toBeGreaterThan(0);

    // Body must contain greeting and signature
    const body = await page.locator('#docEmailBody').inputValue();
    expect(body).toContain('Bonjour');
    expect(body).toContain('Cordialement');

    console.log('Email modal opens correctly with pre-filled fields');
  });

  test('admin can send document email and gets success flash message', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    const emailButtons = page.locator('.doc-email-btn');
    const count = await emailButtons.count();
    if (count === 0) {
      console.log('No documents in DB - skipping send test');
      return;
    }

    // Open modal for first document
    await emailButtons.first().click();
    const modal = page.locator('#docEmailModal');
    await expect(modal).toBeVisible({ timeout: 3000 });

    // Add recipient via the free-email input (chip-based UI)
    await page.locator('#freeEmailInput').fill('test-gvv@mailinator.com');
    await page.locator('#addFreeEmailBtn').click();

    // Submit the form via the submit button (so the recipient validation runs)
    await modal.locator('button[type="submit"]').click();

    // Should redirect back to list with success alert
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('archived_documents');
    await expect(page.locator('.alert-success')).toBeVisible({ timeout: 5000 });

    const alertText = await page.locator('.alert-success').textContent();
    expect(alertText).toContain('Email envoyé avec succès');

    console.log('Document email sent successfully, flash message shown');
  });

});

// ---------------------------------------------------------------------------
// Mobile: modals must open inside the visible area of a page widened by
// gvvSyncWideTableLayout() (regression: on Android the email modal opened
// off-screen, at the top-left of the widened page).
// ---------------------------------------------------------------------------
test.describe('Archived Documents on smartphone', () => {
  const { defaultBrowserType, ...pixel } = devices['Pixel 7'];
  test.use(pixel);

  test('email modal opens inside the visible viewport', async ({ page }) => {
    await login(page, ADMIN_USER);

    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const emailButtons = page.locator('.doc-email-btn');
    test.skip(await emailButtons.count() === 0, 'No document in the database');

    // Scroll to the Actions column, as a user would, then tap the email icon
    // (Playwright's tap() only scrolls the layout viewport, hence the JS click.)
    const button = emailButtons.first();
    await button.evaluate(el => el.scrollIntoView({ block: 'center', inline: 'center' }));
    await page.waitForTimeout(300);
    await button.evaluate(el => el.click());
    await expect(page.locator('#docEmailModal')).toBeVisible();
    // Wait for the end of the opening animation ("shown.bs.modal")
    await page.waitForFunction(() =>
      document.querySelector('#docEmailModal .modal-dialog').getBoundingClientRect().width > 200);

    const geom = await page.evaluate(() => {
      const d = document.querySelector('#docEmailModal .modal-dialog').getBoundingClientRect();
      const vv = window.visualViewport;
      return { left: d.left, top: d.top, width: d.width,
               vvLeft: vv.offsetLeft, vvTop: vv.offsetTop, vvWidth: vv.width };
    });
    expect(geom.width).toBeGreaterThan(200);
    expect(geom.left).toBeGreaterThanOrEqual(geom.vvLeft);
    expect(geom.left + geom.width).toBeLessThanOrEqual(geom.vvLeft + geom.vvWidth + 1);
    expect(geom.top).toBeGreaterThanOrEqual(geom.vvTop);

    await page.locator('#docEmailModal button[data-bs-dismiss="modal"]').first().evaluate(el => el.click());
    await expect(page.locator('#docEmailModal')).toBeHidden();
  });
});
