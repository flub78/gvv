/**
 * Playwright Tests - Formation Programmes CRUD
 *
 * Tests for the training programs management feature (Phase 2.8)
 *
 * Covers:
 *   - Access to programmes list
 *   - Manual programme creation
 *   - Programme import from Markdown file
 *   - Programme viewing (structure with lessons/subjects)
 *   - Programme editing (title, description, status)
 *   - Programme export to Markdown
 *   - Programme deletion
 *   - Cleanup of test data
 *
 * Prerequisites:
 *   - Feature flag gestion_formations must be enabled
 *   - testadmin user must exist (see bin/create_test_users.sh)
 *   - Migration 063 must be applied
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/formation/programmes.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const path = require('path');

// Test configuration
const LOGIN_URL = '/index.php/auth/login';
const PROGRAMMES_URL = '/index.php/programmes';
const TEST_USER = { username: 'testadmin', password: 'password' };

// Unique test data to avoid collisions
const TEST_PROGRAMME_TITLE = 'Test Playwright ' + Date.now();
const TEST_PROGRAMME_TITLE_MODIFIED = TEST_PROGRAMME_TITLE + ' Modifié';

/**
 * Login helper
 */
async function login(page) {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');

  await page.fill('input[name="username"]', TEST_USER.username);
  await page.fill('input[name="password"]', TEST_USER.password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');

  // Close "Message du jour" dialog if it appears
  try {
    const modDialog = page.locator('.ui-dialog');
    if (await modDialog.isVisible({ timeout: 2000 }).catch(() => false)) {
      const closeButton = page.locator('.ui-dialog-buttonpane button:has-text("OK")');
      if (await closeButton.isVisible().catch(() => false)) {
        await closeButton.click();
        await page.waitForTimeout(500);
      }
    }
  } catch (e) {
    // No dialog
  }
}

test.describe('Formation Programmes CRUD', () => {
  test.describe.configure({ mode: 'serial' });

  // Shared state across serial tests
  let programmeId = null;
  let importedProgrammeId = null;

  test('should access programmes list page', async ({ page }) => {
    await login(page);

    await page.goto(PROGRAMMES_URL);
    await page.waitForLoadState('networkidle');

    // Check for PHP errors
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Fatal error');
    expect(bodyText).not.toContain('Parse error');

    // Verify the programmes page loaded
    await expect(page.locator('h3')).toContainText('Programmes de formation');

    // Verify the "Nouveau programme" button is present
    await expect(page.locator('a:has-text("Nouveau programme")')).toBeVisible();

    // Verify the table structure
    await expect(page.locator('#programmes-table')).toBeVisible();

    console.log('Programmes list page accessible');
  });

  test('should create a programme manually', async ({ page }) => {
    await login(page);

    await page.goto(PROGRAMMES_URL + '/create');
    await page.waitForLoadState('networkidle');

    // Verify creation form loaded (manual tab active by default)
    await expect(page.locator('#manual-tab')).toBeVisible();

    // Fill the form
    await page.fill('#titre', TEST_PROGRAMME_TITLE);
    await page.fill('#description', 'Description test Playwright');

    // Submit the form
    await page.click('#programme-form button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // After creation, we should be redirected to edit page
    const currentUrl = page.url();
    console.log('After create, URL:', currentUrl);

    if (currentUrl.includes('/programmes/edit/')) {
      const match = currentUrl.match(/\/edit\/(\d+)/);
      if (match) programmeId = match[1];
    }

    // Check no error
    const hasError = await page.locator('.alert-danger').isVisible().catch(() => false);
    expect(hasError).toBeFalsy();

    // Verify programme exists in list
    await page.goto(PROGRAMMES_URL);
    await page.waitForLoadState('networkidle');
    await expect(page.locator(`text=${TEST_PROGRAMME_TITLE}`)).toBeVisible();

    // Capture the ID from the list if not already captured
    if (!programmeId) {
      const rows = page.locator('#programmes-table tbody tr');
      const count = await rows.count();
      for (let i = 0; i < count; i++) {
        const rowText = await rows.nth(i).textContent();
        if (rowText.includes(TEST_PROGRAMME_TITLE)) {
          const viewLink = rows.nth(i).locator('a[href*="/programmes/view/"]');
          const href = await viewLink.getAttribute('href');
          const match = href.match(/\/view\/(\d+)/);
          if (match) programmeId = match[1];
          break;
        }
      }
    }

    expect(programmeId).toBeTruthy();
    console.log(`Programme created with id=${programmeId}`);
  });

  test('should import a programme from Markdown', async ({ page }) => {
    await login(page);

    await page.goto(PROGRAMMES_URL + '/create');
    await page.waitForLoadState('networkidle');

    // Switch to import tab
    await page.click('#import-tab');
    await page.waitForTimeout(500);
    await expect(page.locator('#import-panel')).toBeVisible();

    // Upload Markdown file
    const markdownFilePath = path.resolve(__dirname, '../../../doc/test-data/formation_spl.md');
    const fileInput = page.locator('#markdown_file');
    await fileInput.setInputFiles(markdownFilePath);

    // Submit the import form
    await page.locator('#import-panel button[type="submit"]').click();
    await page.waitForLoadState('networkidle');

    // Check for errors
    const hasError = await page.locator('.alert-danger').isVisible().catch(() => false);
    if (hasError) {
      const errorText = await page.locator('.alert-danger').textContent();
      console.log('Import error:', errorText);
    }
    expect(hasError).toBeFalsy();

    // Should redirect to view page after successful import
    const currentUrl = page.url();
    console.log('After import, URL:', currentUrl);

    if (currentUrl.includes('/programmes/view/')) {
      const match = currentUrl.match(/\/view\/(\d+)/);
      if (match) importedProgrammeId = match[1];
    }

    expect(importedProgrammeId).toBeTruthy();

    // Verify the imported programme structure
    await expect(page.locator('h3:has-text("Formation Initiale Planeur")')).toBeVisible();

    // Verify lessons are displayed in accordion
    await expect(page.locator('.accordion-item')).toHaveCount(5, { timeout: 5000 });

    console.log(`Programme imported from Markdown (id=${importedProgrammeId})`);
  });

  test('should view programme details with lessons and subjects', async ({ page }) => {
    await login(page);
    expect(importedProgrammeId).toBeTruthy();

    await page.goto(PROGRAMMES_URL + '/view/' + importedProgrammeId);
    await page.waitForLoadState('networkidle');

    // Check for errors
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Fatal error');

    // Verify programme title
    await expect(page.locator('h3').first()).toContainText('Formation Initiale Planeur');

    // Verify version badge
    await expect(page.locator('.badge:has-text("v1")')).toBeVisible();

    // Verify status badge
    await expect(page.locator('.badge:has-text("Actif")')).toBeVisible();

    // Verify accordion with lessons
    const accordion = page.locator('#leconsAccordion');
    await expect(accordion).toBeVisible();

    const accordionItems = page.locator('.accordion-item');
    expect(await accordionItems.count()).toBe(5);

    // First lesson should be expanded by default
    await expect(page.locator('.accordion-collapse.show').first()).toBeVisible();

    // Verify subject badges are present
    const subjectBadges = page.locator('.badge.bg-info');
    expect(await subjectBadges.count()).toBeGreaterThan(0);

    // Verify action buttons
    await expect(page.locator('a:has-text("Exporter")')).toBeVisible();
    await expect(page.locator('a:has-text("Modifier")')).toBeVisible();

    // Verify a lesson title is visible in the accordion
    await expect(page.locator('.accordion-button:has-text("Découverte du planeur")')).toBeVisible();

    console.log('Programme view displays correctly');
  });

  test('should edit a programme', async ({ page }) => {
    await login(page);
    expect(programmeId).toBeTruthy();

    await page.goto(PROGRAMMES_URL + '/edit/' + programmeId);
    await page.waitForLoadState('networkidle');

    // Verify edit form loaded
    await expect(page.locator('#titre')).toBeVisible();

    // Verify current title
    const currentTitle = await page.inputValue('#titre');
    expect(currentTitle).toBe(TEST_PROGRAMME_TITLE);

    // Modify fields
    await page.fill('#titre', TEST_PROGRAMME_TITLE_MODIFIED);
    await page.fill('#description', 'Description mise à jour');

    // Submit
    await page.click('#programme-form button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Check no error
    const hasError = await page.locator('.alert-danger').isVisible().catch(() => false);
    expect(hasError).toBeFalsy();

    // Verify the modified title in the list
    await page.goto(PROGRAMMES_URL);
    await page.waitForLoadState('networkidle');
    await expect(page.locator(`text=${TEST_PROGRAMME_TITLE_MODIFIED}`)).toBeVisible();

    console.log('Programme edited successfully');
  });

  test('should export a programme to Markdown', async ({ page }) => {
    await login(page);
    expect(importedProgrammeId).toBeTruthy();

    // Trigger download via navigation - wrap in Promise.all since goto throws on download
    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.goto(PROGRAMMES_URL + '/export/' + importedProgrammeId).catch(() => {}),
    ]);

    // Verify the download has a .md extension
    const filename = download.suggestedFilename();
    expect(filename).toMatch(/\.md$/);

    console.log(`Programme exported as: ${filename}`);
  });

  test('should delete the manually created programme', async ({ page }) => {
    await login(page);
    expect(programmeId).toBeTruthy();

    await page.goto(PROGRAMMES_URL);
    await page.waitForLoadState('networkidle');

    // Handle the confirm dialog
    page.on('dialog', async dialog => {
      await dialog.accept();
    });

    // Click the delete button
    const deleteLink = page.locator(`a[href*="/programmes/delete/${programmeId}"]`);
    await expect(deleteLink).toBeVisible();
    await deleteLink.click();
    await page.waitForLoadState('networkidle');

    // Verify success message
    await expect(page.locator('.alert-success')).toBeVisible();

    // Verify programme removed from list
    await expect(page.locator(`text=${TEST_PROGRAMME_TITLE_MODIFIED}`)).not.toBeVisible();

    console.log(`Programme (id=${programmeId}) deleted`);
    programmeId = null;
  });

  test('should delete the imported programme', async ({ page }) => {
    await login(page);
    expect(importedProgrammeId).toBeTruthy();

    await page.goto(PROGRAMMES_URL);
    await page.waitForLoadState('networkidle');

    // Handle the confirm dialog
    page.on('dialog', async dialog => {
      await dialog.accept();
    });

    const deleteLink = page.locator(`a[href*="/programmes/delete/${importedProgrammeId}"]`);
    await expect(deleteLink).toBeVisible();
    await deleteLink.click();
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.alert-success')).toBeVisible();

    console.log(`Imported programme (id=${importedProgrammeId}) deleted`);
    importedProgrammeId = null;
  });
});
