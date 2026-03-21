/**
 * Playwright Tests - Formation Progressions Workflow
 *
 * Tests for the progression tracking/progress sheets (Phase 5)
 *
 * Covers:
 *   1. Access to progressions list page
 *   2. Viewing progression sheet (fiche)
 *   3. Verifying statistics display
 *   4. Verifying progress bar and badges
 *   5. Verifying lessons accordion
 *   6. PDF export availability
 *
 * Prerequisites:
 *   - Feature flag gestion_formations must be enabled
 *   - abraracourcix user must exist with instructor rights (see bin/create_test_users.sh)
 *   - Migration 063 must be applied
 *   - At least one open formation/inscription with recorded sessions/evaluations
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/formation/progressions.spec.js --reporter=line
 *
 * @see doc/plans/suivi_formation_plan.md Phase 5
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const LOGIN_URL = '/index.php/auth/login';
const PROGRESSIONS_URL = '/index.php/formation_progressions';
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
    if (await modDialog.isVisible({ timeout: 2000 })) {
      await page.locator('.ui-dialog button:has-text("Fermer")').click();
    }
  } catch (e) {
    // No dialog, continue
  }
}

/**
 * Navigate to progressions list page
 */
async function goToProgressions(page) {
  await page.goto(PROGRESSIONS_URL);
  await page.waitForLoadState('networkidle');
}

// =============================================================================
// TEST SUITE
// =============================================================================

test.describe('Formation Progressions - Phase 5', () => {

  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  // ---------------------------------------------------------------------------
  // Test 1: Access Progressions List
  // ---------------------------------------------------------------------------
  test('should access progressions list page', async ({ page }) => {
    await goToProgressions(page);

    // Verify page loaded
    await expect(page).toHaveURL(/formation_progressions/);

    // Verify page title (look in body div after banner)
    await expect(page.locator('#body h3, .body h3').first()).toContainText(/Fiches de progression|Progressions/i);

    // Verify table or empty message is present (table only rendered when data exists)
    const tableOrEmpty = page.locator('table#formations-table, .alert-info');
    await expect(tableOrEmpty.first()).toBeVisible({ timeout: 10000 });
  });

  // ---------------------------------------------------------------------------
  // Test 2: View Progression Sheet (Fiche)
  // ---------------------------------------------------------------------------
  test('should view progression sheet if formations exist', async ({ page }) => {
    await goToProgressions(page);

    // Wait for DataTable to load
    await page.waitForTimeout(2000);

    // Check if any formations exist
    const viewButtons = page.locator('a.btn:has-text("Voir"), button:has-text("Voir")');
    const count = await viewButtons.count();

    if (count === 0) {
      test.skip();
      return;
    }

    // Click first "Voir" button
    await viewButtons.first().click();
    await page.waitForLoadState('networkidle');

    // Verify we're on fiche page
    await expect(page).toHaveURL(/formation_progressions\/fiche/);

    // Verify page title/header (look in body div after banner)
    await expect(page.locator('#body h3, .body h3, #body h2, .body h2, #body .card-title').first()).toContainText(/Fiche de progression|Progression/i);
  });

  // ---------------------------------------------------------------------------
  // Test 3: Verify Statistics Display
  // ---------------------------------------------------------------------------
  test('should display statistics on progression sheet', async ({ page }) => {
    await goToProgressions(page);
    await page.waitForTimeout(2000);

    const viewButtons = page.locator('a.btn:has-text("Voir")');
    const count = await viewButtons.count();

    if (count === 0) {
      test.skip();
      return;
    }

    await viewButtons.first().click();
    await page.waitForLoadState('networkidle');

    // Verify statistics boxes exist
    const statsCard = page.locator('.card:has-text("Statistiques"), #stats-card');
    if (await statsCard.isVisible()) {
      // Check for statistics values (numbers)
      const statsText = await statsCard.textContent();
      expect(statsText).toMatch(/Séances|séances/i);
      expect(statsText).toMatch(/Heures|heures/i);
      expect(statsText).toMatch(/Atterrissages|atterrissages/i);
      expect(statsText).toMatch(/Acquis|acquis/i);
    }
  });

  // ---------------------------------------------------------------------------
  // Test 4: Verify Progress Bar
  // ---------------------------------------------------------------------------
  test('should display progress bar on progression sheet', async ({ page }) => {
    await goToProgressions(page);
    await page.waitForTimeout(2000);

    const viewButtons = page.locator('a.btn:has-text("Voir")');
    const count = await viewButtons.count();

    if (count === 0) {
      test.skip();
      return;
    }

    await viewButtons.first().click();
    await page.waitForLoadState('networkidle');

    // Verify progress container is visible (progress bar may have 0 width if no evaluations yet)
    const progressContainer = page.locator('.progress');
    await expect(progressContainer.first()).toBeVisible();

    // Verify the progress bar has a color class (bg-danger, bg-warning, bg-info, bg-success)
    const progressBar = page.locator('.progress-bar');
    const barCount = await progressBar.count();
    if (barCount > 0) {
      const className = await progressBar.first().getAttribute('class');
      expect(className).toMatch(/bg-(danger|warning|info|success)/);
    }
  });

  // ---------------------------------------------------------------------------
  // Test 5: Verify Lessons Accordion
  // ---------------------------------------------------------------------------
  test('should display lessons accordion on progression sheet', async ({ page }) => {
    await goToProgressions(page);
    await page.waitForTimeout(2000);

    const viewButtons = page.locator('a.btn:has-text("Voir")');
    const count = await viewButtons.count();

    if (count === 0) {
      test.skip();
      return;
    }

    await viewButtons.first().click();
    await page.waitForLoadState('networkidle');

    // Verify accordion exists and has items
    const accordion = page.locator('.accordion, #lessons-accordion');
    if (await accordion.isVisible()) {
      const items = accordion.locator('.accordion-item');
      expect(await items.count()).toBeGreaterThan(0);

      // The first lesson is expanded by default (has .show class on its collapse div)
      // Look for a subjects table inside the open section
      const openSection = accordion.locator('.accordion-collapse.show').first();
      const isOpen = await openSection.isVisible({ timeout: 2000 }).catch(() => false);
      if (isOpen) {
        const subjectsTable = openSection.locator('table').filter({ hasText: /Sujet|N°/i });
        if (await subjectsTable.count() > 0) {
          await expect(subjectsTable.first()).toBeVisible();
        }
        // No assertion if first lecon has no sujets (empty() == true in PHP)
      }
    }
  });

  // ---------------------------------------------------------------------------
  // Test 6: Verify Level Badges
  // ---------------------------------------------------------------------------
  test('should display level badges on progression sheet', async ({ page }) => {
    await goToProgressions(page);
    await page.waitForTimeout(2000);

    const viewButtons = page.locator('a.btn:has-text("Voir")');
    const count = await viewButtons.count();

    if (count === 0) {
      test.skip();
      return;
    }

    await viewButtons.first().click();
    await page.waitForLoadState('networkidle');

    // Look for badges (bg-secondary, bg-info, bg-warning, bg-success)
    const badges = page.locator('.badge, span[class*="bg-"]');
    const badgeCount = await badges.count();

    if (badgeCount > 0) {
      // Verify at least one badge has a color class
      let foundColorBadge = false;
      for (let i = 0; i < Math.min(badgeCount, 5); i++) {
        const className = await badges.nth(i).getAttribute('class');
        if (className && className.match(/bg-(secondary|info|warning|success)/)) {
          foundColorBadge = true;
          break;
        }
      }
      expect(foundColorBadge).toBeTruthy();
    }
  });

  // ---------------------------------------------------------------------------
  // Test 7: Verify PDF Export Link
  // ---------------------------------------------------------------------------
  test('should have PDF export link available', async ({ page }) => {
    await goToProgressions(page);
    await page.waitForTimeout(2000);

    const pdfButtons = page.locator('a[href*="export_pdf"], button:has-text("PDF"), a:has-text("PDF")');
    const count = await pdfButtons.count();

    if (count === 0) {
      // No formations, skip test
      test.skip();
      return;
    }

    // Verify PDF button/link is visible
    await expect(pdfButtons.first()).toBeVisible();
  });

  // ---------------------------------------------------------------------------
  // Test 8: Access via Dashboard
  // ---------------------------------------------------------------------------
  test('should access progressions from dashboard', async ({ page }) => {
    // Go to dashboard
    await page.goto('/index.php/dashboard');
    await page.waitForLoadState('networkidle');

    // Look for Formation section
    const formationSection = page.locator('.card, .panel').filter({ hasText: /Formation/i });
    
    if (await formationSection.isVisible()) {
      // Look for Progressions link
      const progressionsLink = formationSection.locator('a[href*="formation_progressions"], button:has-text("Voir")').last();
      
      if (await progressionsLink.isVisible({ timeout: 3000 })) {
        await progressionsLink.click();
        await page.waitForLoadState('networkidle');

        // Verify we're on progressions page
        await expect(page).toHaveURL(/formation_progressions/);
      } else {
        test.skip();
      }
    } else {
      test.skip();
    }
  });

});
