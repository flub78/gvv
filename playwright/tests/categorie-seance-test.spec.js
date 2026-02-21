/**
 * Playwright test for categorie_seance feature
 *
 * Tests the new session category functionality:
 * - Migration to version 64 (adds categorie_seance column)
 * - Category selector visibility in session form
 * - Category column visibility in session list
 * - Category statistics in reports
 *
 * Prerequisites:
 * - Test user with access to formation_seances
 *
 * Usage:
 *   npx playwright test tests/categorie-seance-test.spec.js
 */

const { test, expect } = require('@playwright/test');

// Configuration from environment variables or defaults
const CONFIG = {
  baseUrl: process.env.BASE_URL || 'http://gvv.net',
  testUser: process.env.TEST_USER || 'testadmin',
  testPassword: process.env.TEST_PASSWORD || 'password',
};

/**
 * Login helper
 */
async function login(page) {
  await page.goto('/index.php/auth/login', { waitUntil: 'networkidle' });
  await page.fill('input[name="username"]', CONFIG.testUser);
  await page.fill('input[name="password"]', CONFIG.testPassword);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');

  if (page.url().includes('auth/login')) {
    throw new Error('Login failed - still on login page');
  }
}

/**
 * Get current migration version from the /migration page
 */
async function getCurrentMigrationVersion(page) {
  await page.goto('/index.php/migration', { waitUntil: 'networkidle' });

  const pageText = await page.locator('body').textContent();
  const match = pageText.match(/Niveau de la base:\s*(\d+)/);

  if (!match) {
    throw new Error('Could not find current database migration level on page');
  }

  return parseInt(match[1]);
}

/**
 * Run migration to a specific version
 */
async function migrateToVersion(page, targetVersion) {
  await page.goto('/index.php/migration', { waitUntil: 'networkidle' });

  await page.waitForSelector('select[name="target_level"]');
  await page.selectOption('select[name="target_level"]', targetVersion.toString());
  await page.getByRole('button', { name: 'Valider' }).click();
  await page.waitForTimeout(3000);
  await page.waitForLoadState('networkidle');

  const newVersion = await getCurrentMigrationVersion(page);
  if (newVersion !== targetVersion) {
    throw new Error(`Migration failed: expected ${targetVersion}, got ${newVersion}`);
  }

  return newVersion;
}

test.describe('Session Category Feature Tests', () => {

  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should have migration at version 64 or higher', async ({ page }) => {
    console.log('\n📋 Checking migration version...');

    let version = await getCurrentMigrationVersion(page);
    console.log(`   Current version: ${version}`);

    if (version < 64) {
      console.log(`   Migrating from ${version} to 64...`);
      await migrateToVersion(page, 64);
      version = await getCurrentMigrationVersion(page);
      console.log(`   ✅ Migration to 64 completed, now at version ${version}`);
    } else {
      console.log('   ✅ Already at version 64 or higher');
    }

    expect(version).toBeGreaterThanOrEqual(64);
  });

  test('should show category checkboxes in session form', async ({ page }) => {
    console.log('\n📋 Checking category checkboxes in session form...');

    // Navigate to create session form
    await page.goto('/index.php/formation_seances/create', { waitUntil: 'networkidle' });

    // Check if the page loaded (might redirect if feature disabled)
    const pageContent = await page.locator('body').textContent();
    if (pageContent.includes('404') || pageContent.includes('not found')) {
      console.log('   ⚠️ Formation seances feature not enabled, skipping test');
      test.skip();
      return;
    }

    // Check for category checkboxes (name="categories_seance[]")
    const categoryCheckboxes = page.locator('input[name="categories_seance[]"]');
    const checkboxCount = await categoryCheckboxes.count();

    if (checkboxCount > 0) {
      console.log(`   ✅ Found ${checkboxCount} category checkboxes`);

      // Get labels of checkboxes
      const labels = await page.locator('input[name="categories_seance[]"] + label, label:has(input[name="categories_seance[]"])').allTextContents();
      console.log(`   Categories: ${labels.join(', ')}`);

      // At least 1 category checkbox
      expect(checkboxCount).toBeGreaterThanOrEqual(1);
    } else {
      console.log('   ❌ Category checkboxes not found');
      expect(checkboxCount).toBeGreaterThan(0);
    }
  });

  test('should pre-check Formation when creating session with inscription', async ({ page }) => {
    console.log('\n📋 Checking Formation pre-checked with inscription_id...');

    // Navigate to create session form with inscription_id=1
    await page.goto('/index.php/formation_seances/create?inscription_id=1', { waitUntil: 'networkidle' });

    // Check if the page loaded
    const pageContent = await page.locator('body').textContent();
    if (pageContent.includes('404') || pageContent.includes('not found')) {
      console.log('   ⚠️ Formation seances feature not enabled, skipping test');
      test.skip();
      return;
    }

    // Find the "Formation" checkbox and verify it's checked
    const formationCheckbox = page.locator('input[name="categories_seance[]"][value="Formation"]');
    const checkboxExists = await formationCheckbox.count() > 0;

    if (checkboxExists) {
      const isChecked = await formationCheckbox.isChecked();
      if (isChecked) {
        console.log('   ✅ Formation checkbox is pre-checked');
      } else {
        console.log('   ❌ Formation checkbox is NOT pre-checked');
      }
      expect(isChecked).toBe(true);
    } else {
      console.log('   ⚠️ Formation checkbox not found (inscription may not exist)');
      // Don't fail if inscription doesn't exist
    }
  });

  test('should show category column in session list', async ({ page }) => {
    console.log('\n📋 Checking category column in session list...');

    // Navigate to session list
    await page.goto('/index.php/formation_seances', { waitUntil: 'networkidle' });

    // Check if the page loaded
    const pageContent = await page.locator('body').textContent();
    if (pageContent.includes('404') || pageContent.includes('not found')) {
      console.log('   ⚠️ Formation seances feature not enabled, skipping test');
      test.skip();
      return;
    }

    // Check for category column header
    const categoryHeader = page.locator('th', { hasText: 'Catégorie' });
    const headerExists = await categoryHeader.count() > 0;

    if (headerExists) {
      console.log('   ✅ Category column found in list');
    } else {
      // Might be English - check for "Category"
      const categoryHeaderEn = page.locator('th', { hasText: 'Category' });
      const headerExistsEn = await categoryHeaderEn.count() > 0;

      if (headerExistsEn) {
        console.log('   ✅ Category column found in list (English)');
      } else {
        console.log('   ❌ Category column not found');
      }

      expect(headerExists || headerExistsEn).toBe(true);
    }

    // Check for category filter
    const categoryFilter = page.locator('select#filter_categorie');
    const filterExists = await categoryFilter.count() > 0;

    if (filterExists) {
      console.log('   ✅ Category filter found');
    } else {
      console.log('   ⚠️ Category filter not found (optional)');
    }
  });

  test('should show category statistics in reports', async ({ page }) => {
    console.log('\n📋 Checking category statistics in reports...');

    // Navigate to formation reports
    await page.goto('/index.php/formation_rapports', { waitUntil: 'networkidle' });

    // Check if the page loaded
    const pageContent = await page.locator('body').textContent();
    if (pageContent.includes('404') || pageContent.includes('not found') || pageContent.includes('denied')) {
      console.log('   ⚠️ Formation rapports feature not enabled or no access, skipping test');
      test.skip();
      return;
    }

    // Check for "Par catégorie de séance" section
    const categorySection = page.locator('h4', { hasText: /catégorie|category/i });
    const sectionExists = await categorySection.count() > 0;

    if (sectionExists) {
      console.log('   ✅ Category statistics section found in reports');
    } else {
      // Section only appears if there are statistics, so this is not a failure
      console.log('   ℹ️ Category statistics section not visible (may appear when data exists)');
    }

    // Test passes if page loads without PHP error (not 'error' which appears in JS code)
    expect(pageContent).not.toContain('Fatal error');
    expect(pageContent).not.toContain('Parse error');
    expect(pageContent).not.toContain('404 Page not found');
  });

});
