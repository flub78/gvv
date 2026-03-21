/**
 * Playwright Tests - Formation Inscriptions Workflow
 *
 * Tests for the training inscriptions lifecycle (Phase 3)
 *
 * Covers the complete workflow:
 *   1. Access to inscriptions list
 *   2. Opening a new inscription
 *   3. Viewing inscription details
 *   4. Suspending an inscription
 *   5. Reactivating a suspended inscription
 *   6. Closing an inscription (success or abandon)
 *   7. Cleanup of test data
 *
 * Prerequisites:
 *   - Feature flag gestion_formations must be enabled
 *   - abraracourcix (instructor) and asterix (eleve) users must exist (see bin/create_test_users.sh)
 *   - Migration 063 must be applied
 *   - At least one active programme must exist
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/formation/inscriptions.spec.js --reporter=line
 *
 * @see doc/plans/suivi_formation_plan.md Phase 3
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const LOGIN_URL = '/index.php/auth/login';
const INSCRIPTIONS_URL = '/index.php/formation_inscriptions';
const PROGRAMMES_URL = '/index.php/programmes';
// abraracourcix has instructor rights (BIT_FI_AVION + BIT_CA)
const TEST_USER = { username: 'abraracourcix', password: 'password' };

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

/**
 * Helper to dismiss any Bootstrap alerts
 */
async function dismissAlerts(page) {
  try {
    const alerts = page.locator('.alert .btn-close');
    const count = await alerts.count();
    for (let i = 0; i < count; i++) {
      await alerts.nth(i).click({ timeout: 1000 }).catch(() => {});
    }
  } catch (e) {
    // No alerts to dismiss
  }
}

test.describe('Formation Inscriptions Workflow', () => {
  test.describe.configure({ mode: 'serial' });

  // Shared state across serial tests
  let inscriptionId = null;
  let programmeId = null;

  test('Step 1: should access inscriptions list page', async ({ page }) => {
    await login(page);

    await page.goto(INSCRIPTIONS_URL);
    await page.waitForLoadState('networkidle');

    // Check for PHP errors
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Fatal error');
    expect(bodyText).not.toContain('Parse error');
    expect(bodyText).not.toContain('Undefined');

    // Check page title (h3 used in this view)
    await expect(page.locator('h3').first()).toContainText(/Inscriptions|Formation/i);

    console.log('✓ Inscriptions list page accessible');
  });

  test('Step 2: should have at least one active programme available', async ({ page }) => {
    await login(page);

    // Go to programmes page to verify
    await page.goto(PROGRAMMES_URL);
    await page.waitForLoadState('networkidle');

    // Check if any programme exists
    const bodyText = await page.textContent('body');
    
    if (bodyText.includes('Aucun programme') || bodyText.includes('No programme')) {
      test.skip('No active programme available for testing - create one first');
    }

    // Try to get first programme ID from the list
    const firstProgrammeLink = page.locator('a[href*="/programmes/view/"]').first();
    if (await firstProgrammeLink.count() > 0) {
      const href = await firstProgrammeLink.getAttribute('href');
      const match = href.match(/\/view\/(\d+)/);
      if (match) {
        programmeId = match[1];
        console.log(`✓ Found active programme with ID: ${programmeId}`);
      }
    }
  });

  test('Step 3: should open a new inscription', async ({ page }) => {
    await login(page);

    // Cleanup: close any open inscriptions for asterix from previous test runs
    // (the list supports GET filters, so we can navigate directly to filtered results)
    for (let attempt = 0; attempt < 5; attempt++) {
      await page.goto(`${INSCRIPTIONS_URL}?pilote_id=asterix&statut=ouverte`);
      await page.waitForLoadState('networkidle');
      const firstLink = page.locator('a[href*="/detail/"]').first();
      if (await firstLink.count() === 0) break;
      const href = await firstLink.getAttribute('href');
      await page.goto(href);
      await page.waitForLoadState('networkidle');
      const cloturerBtn = page.locator('a[href*="/cloturer/"], button:has-text("Clôturer")').first();
      if (await cloturerBtn.isVisible().catch(() => false)) {
        await cloturerBtn.click();
        await page.waitForLoadState('networkidle');
        await page.check('input[type="radio"][value="abandonnee"]').catch(() => {});
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');
      } else {
        break;
      }
    }

    // Navigate to inscriptions list and open the creation form
    await page.goto(INSCRIPTIONS_URL);
    await page.waitForLoadState('networkidle');

    // Click "Ouvrir une inscription" button
    await page.click('a:has-text("Ouvrir"), button:has-text("Ouvrir"), a[href*="/ouvrir"]');
    await page.waitForLoadState('networkidle');

    // Check we're on the form page
    await expect(page.locator('h3').first()).toContainText(/Ouvrir|Nouvelle/i);

    // Fill the form
    // Select pilot (asterix - eleve in new authorization system)
    await page.selectOption('select[name="pilote_id"]', 'asterix');
    await page.waitForTimeout(500);

    // Select first available programme
    // Note: ouvrir.php has a hardcoded empty option + get_selector() also adds '' key,
    // so we must select by value (not by index) to skip both empty options.
    const programmeSelect = page.locator('select[name="programme_id"]');
    const firstProgramme = programmeSelect.locator('option:not([value=""])').first();
    const programmeValue = await firstProgramme.getAttribute('value');
    if (!programmeValue) {
      test.skip(true, 'No active programmes available');
      return;
    }
    await programmeSelect.selectOption(programmeValue);
    await page.waitForTimeout(500);

    // Select first available instructor referent if any (field is optional)
    const instructeurSelect = page.locator('select[name="instructeur_referent_id"]');
    const firstInstructeur = instructeurSelect.locator('option:not([value=""])').first();
    const instructeurValue = await firstInstructeur.getAttribute('value').catch(() => null);
    if (instructeurValue) {
      await instructeurSelect.selectOption(instructeurValue);
    }

    // Set date
    await page.fill('input[name="date_ouverture"]', new Date().toISOString().split('T')[0]);

    // Add comment
    await page.fill('textarea[name="commentaire"]', 'Test inscription Playwright ' + Date.now());

    // Submit form
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Should be redirected to detail page
    const currentUrl = page.url();
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Fatal error');
    expect(bodyText).not.toContain('Parse error');
    expect(currentUrl).toContain('/detail/');

    // Extract inscription ID from URL
    const match = currentUrl.match(/\/detail\/(\d+)/);
    if (match) {
      inscriptionId = match[1];
      console.log(`✓ Created inscription with ID: ${inscriptionId}`);
    }

    expect(inscriptionId).toBeTruthy();

    // Verify status badge shows "ouverte"
    await expect(page.locator('.badge:has-text("Ouverte")')).toBeVisible();
  });

  test('Step 4: should view inscription details', async ({ page }) => {
    test.skip(!inscriptionId, 'No inscription created in previous test');

    await login(page);

    await page.goto(`${INSCRIPTIONS_URL}/detail/${inscriptionId}`);
    await page.waitForLoadState('networkidle');

    // Check page elements (detail page title is "Fiche de progression")
    await expect(page.locator('h3').first()).toContainText(/Fiche de progression|Inscription/i);
    
    // Verify status badge
    await expect(page.locator('.badge:has-text("Ouverte")')).toBeVisible();

    // Check action buttons are present
    await expect(page.locator('a:has-text("Suspendre"), button:has-text("Suspendre")')).toBeVisible();
    await expect(page.locator('a:has-text("Clôturer"), button:has-text("Clôturer")')).toBeVisible();

    console.log('✓ Inscription details displayed correctly');
  });

  test('Step 5: should suspend the inscription', async ({ page }) => {
    test.skip(!inscriptionId, 'No inscription created in previous test');

    await login(page);

    await page.goto(`${INSCRIPTIONS_URL}/detail/${inscriptionId}`);
    await page.waitForLoadState('networkidle');

    // Click suspend button
    await page.click('a:has-text("Suspendre"), button:has-text("Suspendre"), a[href*="/suspendre/"]');
    await page.waitForLoadState('networkidle');

    // Check we're on suspension form
    await expect(page.locator('h3').first()).toContainText(/Suspendre|Suspension/i);

    // Fill suspension reason
    await page.fill('textarea[name="motif"]', 'Suspension test Playwright');

    // Submit
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Should be back on detail page
    await page.waitForURL(`**/detail/${inscriptionId}`, { timeout: 5000 });

    // Verify status changed to "suspendue"
    await expect(page.locator('.badge:has-text("Suspendue")')).toBeVisible();

    // Reactivate button should now be visible
    await expect(page.locator('a:has-text("Réactiver"), button:has-text("Réactiver")')).toBeVisible();

    console.log('✓ Inscription suspended successfully');
  });

  test('Step 6: should reactivate the inscription', async ({ page }) => {
    test.skip(!inscriptionId, 'No inscription created in previous test');

    await login(page);

    await page.goto(`${INSCRIPTIONS_URL}/detail/${inscriptionId}`);
    await page.waitForLoadState('networkidle');

    // Verify it's suspended
    await expect(page.locator('.badge:has-text("Suspendue")')).toBeVisible();

    // Click reactivate button (usually a direct link, no form)
    await page.click('a:has-text("Réactiver"), button:has-text("Réactiver"), a[href*="/reactiver/"]');
    await page.waitForLoadState('networkidle');

    // Should be back on detail page
    await page.waitForURL(`**/detail/${inscriptionId}`, { timeout: 5000 });

    // Verify status changed back to "ouverte"
    await expect(page.locator('.badge:has-text("Ouverte")')).toBeVisible();

    // Suspend and close buttons should be visible again
    await expect(page.locator('a:has-text("Suspendre"), button:has-text("Suspendre")')).toBeVisible();
    await expect(page.locator('a:has-text("Clôturer"), button:has-text("Clôturer")')).toBeVisible();

    console.log('✓ Inscription reactivated successfully');
  });

  test('Step 7: should close the inscription with success', async ({ page }) => {
    test.skip(!inscriptionId, 'No inscription created in previous test');

    await login(page);

    await page.goto(`${INSCRIPTIONS_URL}/detail/${inscriptionId}`);
    await page.waitForLoadState('networkidle');

    // Click close button
    await page.click('a:has-text("Clôturer"), button:has-text("Clôturer"), a[href*="/cloturer/"]');
    await page.waitForLoadState('networkidle');

    // Check we're on closure form
    await expect(page.locator('h3').first()).toContainText(/Clôturer|Clôture/i);

    // Select "cloturee" (success)
    await page.check('input[type="radio"][value="cloturee"]');
    await page.waitForTimeout(300);

    // Submit without motif (not required for success)
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Should be back on detail page
    await page.waitForURL(`**/detail/${inscriptionId}`, { timeout: 5000 });

    // Verify status changed to "cloturee"
    await expect(page.locator('.badge:has-text("Clôturée")')).toBeVisible();

    // Action buttons should not be visible anymore
    const suspendBtn = page.locator('a:has-text("Suspendre")');
    await expect(suspendBtn).toHaveCount(0);

    console.log('✓ Inscription closed successfully');
  });

  test('Step 8: should list the closed inscription', async ({ page }) => {
    test.skip(!inscriptionId, 'No inscription created in previous test');

    await login(page);

    // Filter by "cloturee" status using GET params (list supports GET filters)
    await page.goto(`${INSCRIPTIONS_URL}?statut=cloturee&pilote_id=asterix`);
    await page.waitForLoadState('networkidle');

    // Check the inscription appears in the list (displayed as "Le Gaulois Asterix")
    const bodyText = await page.textContent('body');
    expect(bodyText).toContain('Asterix');

    console.log('✓ Closed inscription appears in list');
  });

  test('Step 9: Complete workflow validation', async ({ page }) => {
    // This test just verifies the whole workflow completed
    test.skip(!inscriptionId, 'Workflow did not complete');

    console.log(`✓ Complete workflow validated for inscription ID: ${inscriptionId}`);
    console.log('  - Created inscription');
    console.log('  - Viewed details');
    console.log('  - Suspended');
    console.log('  - Reactivated');
    console.log('  - Closed (success)');
  });
});
