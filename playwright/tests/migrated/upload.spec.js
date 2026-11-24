/**
 * Upload Image Tests - Migrated from Dusk to Playwright
 *
 * Original Dusk test: /home/frederic/git/dusk_gvv/tests/Browser/UploadTest.php
 *
 * Tests:
 * - Verify no upload field on membre/create page
 * - Upload a photo to a dedicated test member and delete it again
 * - Verify the upload interface is available on a member edit page
 *
 * IMPORTANT — database / filesystem state:
 *   These tests operate exclusively on the Gaulois test member "asterix"
 *   (created by bin/create_test_users.sh). They never touch a real club
 *   member and never delete a photo they did not upload themselves. The
 *   member-photo test uploads a fixture image, checks it, then removes it,
 *   restoring the exact initial state (asterix has no photo) — including on
 *   failure, via the finally block.
 *
 * Usage:
 *   npx playwright test tests/migrated/upload.spec.js
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const LoginPage = require('../helpers/LoginPage');
const { DB_CONFIG } = require('../helpers/gvv-config');
const path = require('path');
const fs = require('fs');

// Test configuration
const TEST_USER = 'testadmin';
const TEST_PASSWORD = 'password';

// Dedicated test member — a Gaulois fixture user, never a real club member.
const TEST_MEMBER = 'asterix';
const MEMBER_EDIT_PATH = `membre/edit/${TEST_MEMBER}`;

// Member photos are stored here by membre.php (uploads/photos/, not uploads/).
// __dirname is playwright/tests/migrated → three levels up is the repo root.
const PHOTOS_DIR = path.resolve(__dirname, '../../../uploads/photos');

// Path to test image (try legacy fixture first, then stable repo assets)
function resolveTestImagePath() {
  const candidates = [
    path.join(__dirname, '../fixtures/images/asterix.jpeg'),
    path.join(__dirname, '../../../assets/images/vd_recto.jpg'),
    path.join(__dirname, '../../../assets/images/header.jpg'),
    path.join(__dirname, '../../../assets/images/Bon-Bapteme.png')
  ];

  for (const candidate of candidates) {
    if (fs.existsSync(candidate)) {
      return candidate;
    }
  }

  throw new Error(`No test image found. Tried: ${candidates.join(', ')}`);
}

/**
 * Count image files currently stored in uploads/photos/.
 * @returns {number} file count, or -1 if the directory is unreadable
 */
function countPhotoFiles() {
  if (!fs.existsSync(PHOTOS_DIR)) {
    return 0;
  }

  try {
    return fs.readdirSync(PHOTOS_DIR).filter(f => {
      const fullPath = path.join(PHOTOS_DIR, f);
      return fs.statSync(fullPath).isFile() && !f.startsWith('.');
    }).length;
  } catch (error) {
    console.log(`⚠️  Error reading photos directory: ${error.message}`);
    return -1;
  }
}

/**
 * List the stored photo files that belong to the test member.
 * membre.php names them "<random>_<mlogin>.png".
 * @returns {string[]} file names (not full paths)
 */
function listTestMemberPhotoFiles() {
  if (!fs.existsSync(PHOTOS_DIR)) {
    return [];
  }
  try {
    return fs.readdirSync(PHOTOS_DIR).filter(f => f.endsWith(`_${TEST_MEMBER}.png`));
  } catch (error) {
    console.log(`⚠️  Error reading photos directory: ${error.message}`);
    return [];
  }
}

/**
 * Remove any test-member photo file that appeared since `before` was captured.
 * The web server (www-data) writes the file but the test runner owns the
 * directory, so it can still unlink it. Never touches other members' files.
 */
function removeStrayTestMemberPhotos(before) {
  const beforeSet = new Set(before);
  for (const f of listTestMemberPhotoFiles()) {
    if (!beforeSet.has(f)) {
      try {
        fs.unlinkSync(path.join(PHOTOS_DIR, f));
        console.log(`Cleanup: removed stray photo file ${f}`);
      } catch (error) {
        console.log(`⚠️  Could not remove ${f}: ${error.message}`);
      }
    }
  }
}

/**
 * Navigate to the test member's edit page and confirm it is usable.
 * Returns true if the page exposes the photo upload field.
 */
async function openMemberEditPage(loginPage, page) {
  await loginPage.goto(MEMBER_EDIT_PATH);
  await page.waitForLoadState('domcontentloaded');

  const hasFileInput = await page
    .locator('input[type="file"][name="userfile"]')
    .count() > 0;

  return hasFileInput;
}

/** POST to the photo deletion endpoint with the page's session (the endpoint refuses GET). */
async function postDeletePhoto(page, login) {
  const origin = new URL(page.url()).origin;
  return page.request.post(`${origin}/index.php/membre/delete_photo/${login}`);
}

/** Delete the test member's photo through the real controller endpoint. */
async function deleteMemberPhoto(loginPage, page) {
  await postDeletePhoto(page, TEST_MEMBER);
}

test.describe('GVV Upload Image Tests (Migrated from Dusk)', () => {

  test('should verify no upload field on membre create page', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Login
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD);
    console.log('✓ Logged in');

    // Navigate to membre/create page
    await loginPage.goto('membre/create');
    await page.waitForLoadState('domcontentloaded');

    // Verify page loaded
    await loginPage.assertText('Fiche de membre');
    console.log('✓ Member create page loaded');

    // Verify upload elements are NOT present on create form
    const hasPhoto = await page.locator('#photo').isVisible({ timeout: 1000 }).catch(() => false);
    const hasDeletePhoto = await page.locator('#delete_photo').isVisible({ timeout: 1000 }).catch(() => false);
    const hasPictureId = await page.locator('#picture_id').isVisible({ timeout: 1000 }).catch(() => false);

    expect(hasPhoto).toBeFalsy();
    expect(hasDeletePhoto).toBeFalsy();
    expect(hasPictureId).toBeFalsy();
    console.log('✓ No upload elements on create form (as expected)');

    await loginPage.logout();
  });

  /**
   * @feature Membres
   * Scénario: Le responsable dépose puis supprime la photo d'un membre
   *   Étant donné un membre sans photo
   *   Quand le responsable dépose une photo sur sa fiche
   *   Alors la photo est rattachée au membre
   *   Quand il supprime la photo en confirmant
   *   Alors la fiche n'a plus de photo
   *   Et le fichier de la photo n'est plus conservé
   */
  test('should upload and delete the test member photo', async ({ page }) => {
    const loginPage = new LoginPage(page);
    const testImagePath = resolveTestImagePath();
    console.log(`Using test image: ${testImagePath}`);

    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD);
    console.log('✓ Logged in');

    // The test member must exist and expose the upload field.
    const usable = await openMemberEditPage(loginPage, page);
    if (!usable) {
      test.skip(true, `Test member "${TEST_MEMBER}" is missing or has no photo upload field ` +
        `(run bin/create_test_users.sh)`);
      return;
    }
    console.log(`✓ Edit page for "${TEST_MEMBER}" loaded`);

    // Reset any leftover photo from a previous interrupted run. This only ever
    // touches the "asterix" fixture user, never real data.
    const hadStalePhoto = await page.locator('#delete_photo').count() > 0;
    if (hadStalePhoto) {
      console.log('⚠️  Stale photo found on test member, removing it before the test');
      await deleteMemberPhoto(loginPage, page);
      await openMemberEditPage(loginPage, page);
    }

    // Baseline: the exact set of the test member's photo files, and the total
    // file count. Everything the test adds must be gone by the end.
    const baselinePhotoFiles = listTestMemberPhotoFiles();
    const baselineCount = countPhotoFiles();
    console.log(`Baseline uploads/photos/ file count: ${baselineCount}`);
    expect(await page.locator('#delete_photo').count()).toBe(0);

    try {
      // ===== UPLOAD =====
      await page.locator('input[type="file"][name="userfile"]').first().setInputFiles(testImagePath);
      await page.waitForTimeout(500);
      await loginPage.screenshot('member_photo_after_file_selection');

      await page.locator('#validate').click();
      await page.waitForLoadState('domcontentloaded');

      // The upload is performed by the PHP process (www-data). If uploads/photos/
      // is not writable by the web server, CodeIgniter reports it here — treat
      // that as an environment problem, not a test failure.
      const uploadError = await page.evaluate(() => {
        const el = document.querySelector('.text-danger, .alert-danger');
        return el ? el.innerText.trim() : '';
      });
      if (uploadError) {
        test.skip(true, `Photo upload rejected server-side: "${uploadError}". ` +
          `Check that uploads/photos/ is writable by the web server.`);
        return;
      }

      // Reload the edit page to confirm the photo is now attached to the member
      // (file written AND membre.photo updated → the delete button appears).
      await openMemberEditPage(loginPage, page);
      await expect(page.locator('#delete_photo')).toHaveCount(1);
      console.log('✓ Photo uploaded and linked to the member');

      const afterUpload = countPhotoFiles();
      console.log(`uploads/photos/ file count after upload: ${afterUpload}`);
      expect(afterUpload).toBe(baselineCount + 1);

      // ===== DELETE =====
      // The delete button asks for confirmation (browser dialog): accept it.
      page.once('dialog', dialog => dialog.accept());
      await Promise.all([
        page.waitForURL(/membre\/edit\//),
        page.locator('#delete_photo').click(),
      ]);
      await page.waitForLoadState('domcontentloaded');

      await openMemberEditPage(loginPage, page);
      expect(await page.locator('#delete_photo').count()).toBe(0);
      console.log('✓ Uploaded photo deleted');

      const finalCount = countPhotoFiles();
      console.log(`Final uploads/photos/ file count: ${finalCount}`);
      expect(finalCount).toBe(baselineCount);
    } finally {
      // Safety net — restore the exact initial state whatever happened above:
      //   1. drop any photo link on the test member (idempotent),
      //   2. delete any stray photo file the upload left behind.
      await deleteMemberPhoto(loginPage, page).catch(err =>
        console.log(`⚠️  delete_photo cleanup failed: ${err.message}`));
      removeStrayTestMemberPhotos(baselinePhotoFiles);
    }

    await loginPage.logout();
    console.log('\n✓ Test completed successfully');
  });

  /**
   * @feature Membres
   * Scénario: Seul un responsable peut supprimer la photo d'un membre, et par une action explicite
   *   Étant donné un membre qui a une photo
   *   Quand un membre sans responsabilité demande la suppression de cette photo
   *   Alors la photo est conservée
   *   Quand un responsable ouvre simplement l'adresse de suppression
   *   Alors la photo est conservée
   *   Quand le responsable valide la suppression
   *   Alors le membre n'a plus de photo
   */
  test('photo deletion requires an editing role and a POST', async ({ page }) => {
    // Own temporary member, copied from the test member: other test files edit it in parallel.
    const member = `zzpwph${Date.now() % 100000}`;
    const fakePhoto = `zz_playwright_${Date.now()}.png`;   // name only, no file on disk
    const db = await mysql.createConnection(DB_CONFIG);
    const [[template]] = await db.query('SELECT * FROM membres WHERE mlogin = ?', [TEST_MEMBER]);
    if (!template) {
      await db.end();
      test.skip(true, `Test member "${TEST_MEMBER}" is missing (run bin/create_test_users.sh)`);
      return;
    }
    const photoOf = async () => (await db.query('SELECT photo FROM membres WHERE mlogin = ?', [member]))[0][0].photo;
    const loginPage = new LoginPage(page);
    try {
      const fixture = { ...template, mlogin: member, memail: null, username: null, photo: fakePhoto };
      delete fixture.mnumero;   // auto-increment, unique
      await db.query('INSERT INTO membres SET ?', [fixture]);

      // A plain member cannot delete it
      await loginPage.open();
      await loginPage.login('testuser', 'password');
      await postDeletePhoto(page, member);
      expect(await photoOf()).toBe(fakePhoto);
      await loginPage.logout();

      // An editor opening the address (GET) does not delete it
      await loginPage.open();
      await loginPage.login(TEST_USER, TEST_PASSWORD);
      await loginPage.goto(`membre/delete_photo/${member}`);
      await page.waitForLoadState('domcontentloaded');
      expect(await photoOf()).toBe(fakePhoto);

      // An editor's POST deletes it
      await postDeletePhoto(page, member);
      expect(await photoOf()).toBeNull();
      await loginPage.logout();
    } finally {
      await db.query('DELETE FROM membres WHERE mlogin = ?', [member]);
      await db.end();
    }
  });

  test('should verify the upload interface is available on a member edit page', async ({ page }) => {
    const loginPage = new LoginPage(page);

    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD);

    const usable = await openMemberEditPage(loginPage, page);
    if (!usable) {
      test.skip(true, `Test member "${TEST_MEMBER}" is missing (run bin/create_test_users.sh)`);
      return;
    }

    // The metadata-driven upload widget must be present on the edit form.
    const hasFileInput = await page.locator('input[type="file"][name="userfile"]').count() > 0;
    expect(hasFileInput).toBeTruthy();
    console.log('✓ Upload interface is available on the member edit page');

    // The photos storage directory must exist and be readable.
    expect(fs.existsSync(PHOTOS_DIR)).toBeTruthy();
    console.log(`✓ Photos directory exists: ${PHOTOS_DIR} (${countPhotoFiles()} file(s))`);

    await loginPage.logout();
  });

});
