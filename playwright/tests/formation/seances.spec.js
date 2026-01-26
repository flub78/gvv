/**
 * Playwright Tests - Formation Seances Workflow
 *
 * Tests for the training sessions management (Phase 4)
 *
 * Covers:
 *   1. Access to seances list page
 *   2. Creating a libre (free) session
 *   3. Viewing session detail
 *   4. Editing a session
 *   5. Filtering sessions by type
 *   6. Creating an inscription-linked session (if open inscription exists)
 *   7. Deleting test sessions
 *
 * Prerequisites:
 *   - Feature flag gestion_formations must be enabled
 *   - testadmin user must exist (see bin/create_test_users.sh)
 *   - Migration 063 must be applied
 *   - At least one active programme with lessons/subjects must exist
 *   - At least one active glider (planeur) must exist
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/formation/seances.spec.js --reporter=line
 *
 * @see doc/plans/suivi_formation_plan.md Phase 4
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const LOGIN_URL = '/index.php/auth/login';
const SEANCES_URL = '/index.php/formation_seances';
const TEST_USER = { username: 'testadmin', password: 'password' };

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

test.describe('Formation Seances Workflow', () => {
  test.describe.configure({ mode: 'serial' });

  // Shared state across serial tests
  let libreSeanceId = null;
  let inscriptionSeanceId = null;

  test('Step 1: should access seances list page', async ({ page }) => {
    await login(page);

    await page.goto(SEANCES_URL);
    await page.waitForLoadState('networkidle');

    // Check for PHP errors
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Fatal error');
    expect(bodyText).not.toContain('Parse error');
    expect(bodyText).not.toContain('Severity: Notice');

    // Check page title
    await expect(page.locator('h3')).toContainText(/[Ss]éance/);

    // Verify "Nouvelle séance" button is present
    await expect(page.locator('a[href*="/formation_seances/create"]')).toBeVisible();

    // Verify filter section exists
    await expect(page.locator('#filter_type')).toBeVisible();

    // Verify table exists
    await expect(page.locator('#seances-table, .text-muted')).toBeVisible();

    console.log('Seances list page accessible');
  });

  test('Step 2: should create a libre (free) session', async ({ page }) => {
    await login(page);

    await page.goto(SEANCES_URL + '/create');
    await page.waitForLoadState('networkidle');

    // Check form loaded
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Fatal error');

    // Select libre mode
    await page.click('label[for="mode_libre"]');
    await page.waitForTimeout(300);

    // Verify libre fields are visible
    await expect(page.locator('#libre-fields')).toBeVisible();

    // Select first available pilot
    const piloteSelect = page.locator('select#pilote_id');
    await piloteSelect.selectOption({ index: 1 });
    await page.waitForTimeout(300);

    // Select first available programme
    const programmeSelect = page.locator('select#programme_id');
    await programmeSelect.selectOption({ index: 1 });
    await page.waitForTimeout(1000); // Wait for AJAX programme structure load

    // Fill date
    await page.fill('input#date_seance', new Date().toISOString().split('T')[0]);

    // Select first available instructor
    const instructeurSelect = page.locator('select#instructeur_id');
    await instructeurSelect.selectOption({ index: 1 });

    // Select first available machine (glider)
    const machineSelect = page.locator('select#machine_id');
    await machineSelect.selectOption({ index: 1 });

    // Fill duration
    await page.fill('input#duree', '0:45');

    // Fill number of landings
    await page.fill('input#nb_atterrissages', '3');

    // Check some meteo conditions
    const meteoCavok = page.locator('#meteo_cavok');
    if (await meteoCavok.isVisible()) {
      await meteoCavok.check();
    }

    // Add a comment
    await page.fill('textarea#commentaires', 'Test seance libre Playwright ' + Date.now());

    // Submit the form
    await page.click('#seance-form button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Check for errors
    const afterSubmitBody = await page.textContent('body');
    expect(afterSubmitBody).not.toContain('Fatal error');

    // We should be redirected to detail page on success
    const currentUrl = page.url();

    if (currentUrl.includes('/detail/')) {
      const match = currentUrl.match(/\/detail\/(\d+)/);
      if (match) {
        libreSeanceId = match[1];
        console.log(`Libre session created with ID: ${libreSeanceId}`);
      }
    } else {
      // If we're not on detail page, check for validation errors
      const hasValidationError = await page.locator('.alert-danger').isVisible().catch(() => false);
      if (hasValidationError) {
        const errorText = await page.locator('.alert-danger').textContent();
        console.log('Validation error:', errorText);
      }
    }

    expect(libreSeanceId).toBeTruthy();

    // Verify the detail page shows "Libre" badge
    await expect(page.locator('.badge:has-text("Libre")')).toBeVisible();
  });

  test('Step 3: should view libre session detail', async ({ page }) => {
    test.skip(!libreSeanceId, 'No libre session created in previous test');

    await login(page);

    await page.goto(`${SEANCES_URL}/detail/${libreSeanceId}`);
    await page.waitForLoadState('networkidle');

    // Check for PHP errors
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Fatal error');

    // Verify page has session info
    await expect(page.locator('h3')).toContainText(/[Ss]éance|[Dd]étail/);

    // Verify type badge shows "Libre"
    await expect(page.locator('.badge:has-text("Libre")')).toBeVisible();

    // Verify action buttons are present
    await expect(page.locator('a[href*="/edit/"]')).toBeVisible();
    await expect(page.locator('a[href*="/delete/"]')).toBeVisible();

    // Verify meteo is displayed
    const meteoSection = page.locator('text=CAVOK');
    if (await meteoSection.isVisible().catch(() => false)) {
      console.log('Meteo conditions displayed correctly');
    }

    // Verify comment is displayed
    await expect(page.locator('text=Test seance libre Playwright')).toBeVisible();

    console.log('Libre session detail displayed correctly');
  });

  test('Step 4: should edit the libre session', async ({ page }) => {
    test.skip(!libreSeanceId, 'No libre session created in previous test');

    await login(page);

    await page.goto(`${SEANCES_URL}/edit/${libreSeanceId}`);
    await page.waitForLoadState('networkidle');

    // Check form loaded
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Fatal error');

    // Verify we're on edit form
    await expect(page.locator('#seance-form')).toBeVisible();

    // Modify nb_atterrissages
    await page.fill('input#nb_atterrissages', '5');

    // Modify comment
    await page.fill('textarea#commentaires', 'Test seance libre Playwright - MODIFIE');

    // Add prochaines lecons
    await page.fill('input#prochaines_lecons', 'Prochaine: tours de piste');

    // Submit
    await page.click('#seance-form button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Should redirect to detail page
    const currentUrl = page.url();
    expect(currentUrl).toContain('/detail/' + libreSeanceId);

    // Verify modified values
    await expect(page.locator('p:has-text("MODIFIE")')).toBeVisible();
    await expect(page.locator('text=tours de piste')).toBeVisible();

    console.log('Libre session edited successfully');
  });

  test('Step 5: should filter seances by type libre', async ({ page }) => {
    test.skip(!libreSeanceId, 'No libre session created in previous test');

    await login(page);

    await page.goto(SEANCES_URL);
    await page.waitForLoadState('networkidle');

    // Filter by type "libre"
    await page.selectOption('#filter_type', 'libre');
    await page.click('button:has-text("Filtrer")');
    await page.waitForLoadState('networkidle');

    // Verify filter is applied
    const typeFilter = await page.locator('#filter_type').inputValue();
    expect(typeFilter).toBe('libre');

    // All visible badges should be "Libre" type
    const typeBadges = page.locator('#seances-table .badge');
    const badgeCount = await typeBadges.count();
    if (badgeCount > 0) {
      for (let i = 0; i < badgeCount; i++) {
        const badgeText = await typeBadges.nth(i).textContent();
        expect(badgeText.trim()).toContain('Libre');
      }
    }

    console.log('Type filter (libre) works correctly');
  });

  test('Step 6: should filter seances by type formation', async ({ page }) => {
    await login(page);

    await page.goto(SEANCES_URL);
    await page.waitForLoadState('networkidle');

    // Filter by type "formation"
    await page.selectOption('#filter_type', 'formation');
    await page.click('button:has-text("Filtrer")');
    await page.waitForLoadState('networkidle');

    // Verify filter is applied
    const typeFilter = await page.locator('#filter_type').inputValue();
    expect(typeFilter).toBe('formation');

    // All visible badges should be "Formation" type (if any)
    const typeBadges = page.locator('#seances-table .badge');
    const badgeCount = await typeBadges.count();
    for (let i = 0; i < badgeCount; i++) {
      const badgeText = await typeBadges.nth(i).textContent();
      expect(badgeText.trim()).toContain('Formation');
    }

    console.log('Type filter (formation) works correctly');
  });

  test('Step 7: should create an inscription-linked session if open inscription exists', async ({ page }) => {
    await login(page);

    // First check if there are any open inscriptions
    await page.goto(SEANCES_URL + '/create');
    await page.waitForLoadState('networkidle');

    // Ensure inscription mode is selected
    await page.click('label[for="mode_inscription"]');
    await page.waitForTimeout(300);

    // Check if we have the dynamic pilot selector (no fixed inscription)
    const inscPiloteSelect = page.locator('select#insc_pilote_id');
    const hasInscPiloteSelect = await inscPiloteSelect.isVisible().catch(() => false);

    if (!hasInscPiloteSelect) {
      // Check if there's a fixed inscription (from query param)
      const fixedInscription = page.locator('input[name="inscription_id"][type="hidden"]');
      if (await fixedInscription.count() === 0) {
        console.log('No inscription selector found - skipping test');
        test.skip(true, 'No inscription selector available');
        return;
      }
    }

    // Select first pilot
    await inscPiloteSelect.selectOption({ index: 1 });
    await page.waitForTimeout(1500); // Wait for AJAX to load inscriptions

    // Check if any inscriptions are available
    const inscSelect = page.locator('select#inscription_id');
    const inscOptions = await inscSelect.locator('option').count();

    if (inscOptions <= 1) {
      console.log('No open inscriptions found for this pilot, skipping inscription session test');
      test.skip(true, 'No open inscriptions available');
      return;
    }

    // Select first available inscription
    await inscSelect.selectOption({ index: 1 });
    await page.waitForTimeout(1500); // Wait for AJAX programme structure

    // Fill common fields
    await page.fill('input#date_seance', new Date().toISOString().split('T')[0]);

    // Select instructor
    const instructeurSelect = page.locator('select#instructeur_id');
    await instructeurSelect.selectOption({ index: 1 });

    // Select machine
    const machineSelect = page.locator('select#machine_id');
    await machineSelect.selectOption({ index: 1 });

    // Fill duration
    await page.fill('input#duree', '1:00');

    // Fill landings
    await page.fill('input#nb_atterrissages', '2');

    // Check meteo
    const meteoThermiques = page.locator('#meteo_thermiques');
    if (await meteoThermiques.isVisible()) {
      await meteoThermiques.check();
    }

    // Set an evaluation if dynamic evaluations are loaded
    const evalSelects = page.locator('.eval-niveau');
    const evalCount = await evalSelects.count();
    if (evalCount > 0) {
      // Set first subject as "Aborde"
      await evalSelects.first().selectOption('A');
      console.log(`Set evaluation for first subject (${evalCount} subjects available)`);
    }

    // Add comment
    await page.fill('textarea#commentaires', 'Test seance inscription Playwright ' + Date.now());

    // Submit
    await page.click('#seance-form button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Check for errors
    const afterSubmitBody = await page.textContent('body');
    expect(afterSubmitBody).not.toContain('Fatal error');

    const currentUrl = page.url();
    if (currentUrl.includes('/detail/')) {
      const match = currentUrl.match(/\/detail\/(\d+)/);
      if (match) {
        inscriptionSeanceId = match[1];
        console.log(`Inscription session created with ID: ${inscriptionSeanceId}`);
      }

      // Verify the detail page shows "Formation" badge
      await expect(page.locator('.badge:has-text("Formation")')).toBeVisible();

      // Verify evaluations section exists
      await expect(page.locator('text=valuations')).toBeVisible();
    } else {
      // Check for validation errors
      const hasError = await page.locator('.alert-danger').isVisible().catch(() => false);
      if (hasError) {
        const errorText = await page.locator('.alert-danger').textContent();
        console.log('Error creating inscription session:', errorText);
      }
    }

    expect(inscriptionSeanceId).toBeTruthy();
  });

  test('Step 8: should delete the inscription session', async ({ page }) => {
    test.skip(!inscriptionSeanceId, 'No inscription session created');

    await login(page);

    // Handle confirm dialog
    page.on('dialog', async dialog => {
      await dialog.accept();
    });

    await page.goto(`${SEANCES_URL}/delete/${inscriptionSeanceId}`);
    await page.waitForLoadState('networkidle');

    // Should redirect to list with success message
    await expect(page.locator('.alert-success')).toBeVisible();

    console.log(`Inscription session (id=${inscriptionSeanceId}) deleted`);
    inscriptionSeanceId = null;
  });

  test('Step 9: should delete the libre session', async ({ page }) => {
    test.skip(!libreSeanceId, 'No libre session created');

    await login(page);

    // Handle confirm dialog
    page.on('dialog', async dialog => {
      await dialog.accept();
    });

    await page.goto(`${SEANCES_URL}/delete/${libreSeanceId}`);
    await page.waitForLoadState('networkidle');

    // Should redirect to list with success message
    await expect(page.locator('.alert-success')).toBeVisible();

    console.log(`Libre session (id=${libreSeanceId}) deleted`);
    libreSeanceId = null;
  });

  test('Step 10: Complete workflow validation', async ({ page }) => {
    console.log('Complete seances workflow validated:');
    console.log('  - Accessed seances list page');
    console.log('  - Created libre (free) session');
    console.log('  - Viewed session detail');
    console.log('  - Edited session');
    console.log('  - Filtered by type (libre/formation)');
    console.log('  - Created inscription session (if available)');
    console.log('  - Deleted test sessions');
  });
});
