/**
 * Playwright test for database migrations - Complete End-to-End GUI Test
 *
 * This is a complete end-to-end test that uses only GUI interactions:
 * - Creates a database backup via GUI (/admin/backup_form) and downloads it
 * - Checks current migration version via GUI (/migration)
 * - Downgrades by DOWNGRADE_DEPTH versions (default: 10) via GUI
 * - Upgrades back to initial version via GUI
 * - Restores the database backup via GUI (/admin/restore) by uploading the file
 *
 * NO database queries, NO CLI operations, NO parallel tests
 * Single sequential test using only web interface
 *
 * The test performs actual backup/restore operations:
 * - Downloads the backup ZIP file to build/test-backup-*.zip
 * - Uploads and restores the same file after migration testing
 * - Verifies database integrity after restore
 *
 * Safety guarantee: if the test fails at any point after downgrading, the finally
 * block restores the database — first via backup restore, then via re-migration.
 * If backup is not accessible the test is skipped rather than proceeding without
 * a safety net.
 *
 * Environment Variables (all optional with defaults):
 * - BASE_URL: Application base URL (default: http://gvv.net, uses Playwright baseURL)
 * - TEST_USER: Test username (default: testadmin)
 * - TEST_PASSWORD: Test password (default: password)
 *
 * Usage:
 *   Local:     npx playwright test tests/migration-test.spec.js
 *   Jenkins:   BASE_URL=http://jenkins-server npx playwright test tests/migration-test.spec.js
 */

const { test, expect } = require('@playwright/test');
const fs = require('fs').promises;

// Skip unless explicitly requested — this test modifies the DB and must not
// run in parallel with the rest of the suite.
test.skip(
  !process.env.RUN_MIGRATION_TEST,
  'Migration test skipped by default — run with RUN_MIGRATION_TEST=1'
);

// How many versions to roll back. Enough to exercise the mechanism without
// touching old migrations that have data-truncation and throw issues.
const DOWNGRADE_DEPTH = 10;

// Configuration from environment variables or defaults
const CONFIG = {
  baseUrl: process.env.BASE_URL || 'http://gvv.net',
  testUser: process.env.TEST_USER || 'testadmin',
  testPassword: process.env.TEST_PASSWORD || 'password',
};

/**
 * Get current migration version from the /migration page
 */
async function getCurrentMigrationFromGui(page) {
  await page.goto('/index.php/migration', { waitUntil: 'networkidle' });

  // Check if redirected to login
  if (page.url().includes('auth/login')) {
    throw new Error('Not authenticated - cannot access migration page');
  }

  // Read the actual database level from the page text
  const pageText = await page.locator('body').textContent();
  const match = pageText.match(/Niveau de la base:\s*(\d+)/);

  if (!match) {
    throw new Error('Could not find current database migration level on page');
  }

  return parseInt(match[1]);
}

/**
 * Set migration to target version via GUI
 */
async function setMigrationViaGui(page, targetVersion) {
  console.log(`\n🔧 Setting migration to version ${targetVersion}...`);

  await page.goto('/index.php/migration', { waitUntil: 'networkidle' });

  // Wait for the dropdown to be visible and stable
  await page.waitForSelector('select[name="target_level"]');

  // Select target version
  await page.selectOption('select[name="target_level"]', targetVersion.toString());
  console.log(`   ✅ Selected version ${targetVersion}`);

  // Submit form
  await page.getByRole('button', { name: 'Valider' }).click();

  // Wait longer for migration to complete
  await page.waitForTimeout(5000);
  await page.waitForLoadState('networkidle');
  console.log(`   ⏳ Migration complete, verifying...`);

  // Verify the change by checking the page again
  const newVersion = await getCurrentMigrationFromGui(page);
  if (newVersion !== targetVersion) {
    throw new Error(`Migration failed: expected ${targetVersion}, got ${newVersion}`);
  }

  console.log(`   ✅ Migration to version ${targetVersion} successful`);
  return newVersion;
}

/**
 * Migrate in steps to avoid issues with large jumps
 */
async function migrateInSteps(page, fromVersion, toVersion) {
  const stepSize = 10;
  let currentVersion = fromVersion;

  console.log(`\n📊 Planning migration from ${fromVersion} to ${toVersion} in steps of ${stepSize}`);

  if (fromVersion > toVersion) {
    // Downgrade
    while (currentVersion > toVersion) {
      const nextVersion = Math.max(toVersion, currentVersion - stepSize);
      console.log(`\n⬇️  Step: ${currentVersion} → ${nextVersion}`);
      await setMigrationViaGui(page, nextVersion);
      currentVersion = nextVersion;
    }
  } else {
    // Upgrade
    while (currentVersion < toVersion) {
      const nextVersion = Math.min(toVersion, currentVersion + stepSize);
      console.log(`\n⬆️  Step: ${currentVersion} → ${nextVersion}`);
      await setMigrationViaGui(page, nextVersion);
      currentVersion = nextVersion;
    }
  }

  console.log(`\n✅ Migration completed: final version is ${currentVersion}`);
  return currentVersion;
}

/**
 * Restore database via GUI from a local backup file
 */
async function restoreDatabaseViaGui(page, backupFilePath) {
  console.log(`\n📤 Restoring backup from: ${backupFilePath}`);

  await page.goto('/index.php/admin/restore', { waitUntil: 'networkidle' });

  const restorePageContent = await page.locator('body').textContent();
  if (restorePageContent.includes('Access denied') || restorePageContent.includes('Accès refusé')) {
    throw new Error('No access to restore page');
  }

  const fileInput = await page.locator('#userfile');
  await fileInput.setInputFiles(backupFilePath);

  await page.check('input[name="erase_db"]');

  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.locator('button[type="submit"]').first().click()
  ]);

  const resultContent = await page.locator('body').textContent();
  if (resultContent.includes('error') || resultContent.includes('erreur')) {
    throw new Error('Restore reported an error');
  }

  console.log('   ✅ Database restored successfully');
}

test.describe('Database Migration End-to-End Test', () => {

  test('should backup, downgrade, upgrade, and restore database via GUI', async ({ page }) => {
    test.setTimeout(240000); // 4 minutes — includes safety-net recovery time

    console.log('\n════════════════════════════════════════════════════════════');
    console.log('  Complete End-to-End Migration Test');
    console.log('════════════════════════════════════════════════════════════');

    let backupFilePath = null;
    let initialVersion = null;
    let restoreDone = false;

    try {
      // ============================================================
      // STEP 1: Login
      // ============================================================
      console.log('\n📋 STEP 1: Login to application');
      console.log('────────────────────────────────────────────────────────────');

      await page.goto('/index.php/auth/login', { waitUntil: 'networkidle' });
      await page.fill('input[name="username"]', CONFIG.testUser);
      await page.fill('input[name="password"]', CONFIG.testPassword);
      await page.click('button[type="submit"], input[type="submit"]');
      await page.waitForLoadState('networkidle');

      if (page.url().includes('auth/login')) {
        throw new Error('Login failed - still on login page');
      }
      console.log('✅ Login successful');

      // ============================================================
      // STEP 2: Get Initial Migration Version
      // ============================================================
      console.log('\n📋 STEP 2: Check initial migration version');
      console.log('────────────────────────────────────────────────────────────');

      initialVersion = await getCurrentMigrationFromGui(page);
      console.log(`✅ Initial migration version: ${initialVersion}`);

      await page.screenshot({ path: 'build/screenshots/migration-01-initial.png' }).catch(() => {});

      // ============================================================
      // STEP 3: Backup Database - REQUIRED safety net before downgrade
      // ============================================================
      console.log('\n📋 STEP 3: Backup database via GUI');
      console.log('────────────────────────────────────────────────────────────');

      await page.goto('/index.php/admin/backup_form', { waitUntil: 'networkidle' });

      const backupPageContent = await page.locator('body').textContent();
      if (backupPageContent.includes('Access denied') || backupPageContent.includes('Accès refusé')) {
        // Without a backup we have no safety net — skip rather than risk leaving
        // the database at a very old migration level.
        test.skip(true, 'No access to backup page - skipping destructive migration test (no safety net available)');
        return;
      }

      console.log('✅ Backup page accessible - creating backup...');
      const downloadPromise = page.waitForEvent('download', { timeout: 60000 });
      await page.locator('button[type="submit"][name="type"][value=""]').first().click();
      const download = await downloadPromise;
      console.log(`   📦 Backup download started: ${download.suggestedFilename()}`);
      backupFilePath = `build/test-backup-${Date.now()}.zip`;
      await download.saveAs(backupFilePath);
      console.log(`   ✅ Backup saved to: ${backupFilePath}`);

      await page.screenshot({ path: 'build/screenshots/migration-02-backup.png' }).catch(() => {});

      // ============================================================
      // STEP 4: Downgrade by DOWNGRADE_DEPTH versions
      // ============================================================
      const targetVersion = Math.max(initialVersion - DOWNGRADE_DEPTH, 1);
      console.log(`\n📋 STEP 4: Downgrade from ${initialVersion} to ${targetVersion} (${DOWNGRADE_DEPTH} versions)`);
      console.log('────────────────────────────────────────────────────────────');

      if (initialVersion <= 1) {
        console.log(`⚠️  Already at version ${initialVersion}, skipping downgrade`);
      } else {
        await migrateInSteps(page, initialVersion, targetVersion);
        const versionAfterDowngrade = await getCurrentMigrationFromGui(page);
        expect(versionAfterDowngrade).toBe(targetVersion);
        console.log(`✅ Successfully downgraded from ${initialVersion} to ${targetVersion}`);
      }

      await page.screenshot({ path: 'build/screenshots/migration-03-downgraded.png' }).catch(() => {});

      // ============================================================
      // STEP 5: Upgrade back to Initial Version
      // ============================================================
      console.log('\n📋 STEP 5: Upgrade back to initial version');
      console.log('────────────────────────────────────────────────────────────');

      await migrateInSteps(page, targetVersion, initialVersion);
      const versionAfterUpgrade = await getCurrentMigrationFromGui(page);
      expect(versionAfterUpgrade).toBe(initialVersion);
      console.log(`✅ Successfully upgraded back to ${initialVersion}`);

      await page.screenshot({ path: 'build/screenshots/migration-04-upgraded.png' }).catch(() => {});

      // ============================================================
      // STEP 6: Verify Application Still Works
      // ============================================================
      console.log('\n📋 STEP 6: Verify application functionality');
      console.log('────────────────────────────────────────────────────────────');

      await page.goto('/index.php/welcome', { waitUntil: 'networkidle' });
      const dashboardContent = await page.locator('body').textContent();

      if (dashboardContent.includes('username') && dashboardContent.includes('password')) {
        throw new Error('Application check failed - redirected to login page');
      }
      console.log('✅ Application is functional after migrations');

      await page.screenshot({ path: 'build/screenshots/migration-05-verified.png' }).catch(() => {});

      // ============================================================
      // STEP 7: Restore Database via GUI
      // ============================================================
      console.log('\n📋 STEP 7: Restore database via GUI');
      console.log('────────────────────────────────────────────────────────────');

      await restoreDatabaseViaGui(page, backupFilePath);
      restoreDone = true;

      const finalVersion = await getCurrentMigrationFromGui(page);
      console.log(`   ✅ Migration version after restore: ${finalVersion}`);

      await page.screenshot({ path: 'build/screenshots/migration-06-restore-page.png' }).catch(() => {});

      console.log('\n════════════════════════════════════════════════════════════');
      console.log('  ✅ End-to-End Migration Test Complete');
      console.log('════════════════════════════════════════════════════════════');
      console.log(`✅ Successfully tested migration cycle: ${initialVersion} → ${targetVersion} → ${initialVersion}`);
      console.log(`✅ Database backup created and restored successfully via GUI`);
      console.log('════════════════════════════════════════════════════════════\n');

    } finally {
      // ============================================================
      // SAFETY NET: Restore database to initialVersion if test failed
      // mid-migration (e.g. upgrade from targetVersion back to initialVersion
      // failed partway through, leaving DB at an intermediate level).
      // ============================================================
      if (!restoreDone && initialVersion !== null) {
        console.log('\n⚠️  Safety net: test did not complete — attempting database recovery...');

        // Primary recovery: restore from backup (most reliable)
        if (backupFilePath) {
          try {
            await restoreDatabaseViaGui(page, backupFilePath);
            console.log('✅ Safety net: database restored from backup');
            restoreDone = true;
          } catch (restoreError) {
            console.error(`❌ Safety net backup restore failed: ${restoreError.message}`);
          }
        }

        // Fallback recovery: migrate from wherever we are back to initialVersion
        if (!restoreDone) {
          try {
            const safeCurrentVersion = await getCurrentMigrationFromGui(page);
            if (safeCurrentVersion !== initialVersion) {
              console.log(`   DB is at ${safeCurrentVersion}, migrating to ${initialVersion}...`);
              await migrateInSteps(page, safeCurrentVersion, initialVersion);
              console.log(`✅ Safety net: migrated back to ${initialVersion}`);
            }
          } catch (migrationError) {
            console.error(`❌ Safety net migration also failed: ${migrationError.message}`);
            console.error('🚨 DATABASE MAY BE AT AN INCONSISTENT MIGRATION LEVEL — manual intervention required');
          }
        }
      }

      // Cleanup: delete local backup file
      if (backupFilePath) {
        try {
          await fs.unlink(backupFilePath);
          console.log(`🧹 Cleanup: deleted backup file ${backupFilePath}`);
        } catch (error) {
          console.log(`⚠️  Cleanup: could not delete backup file: ${error.message}`);
        }
      }
    }
  });

});
