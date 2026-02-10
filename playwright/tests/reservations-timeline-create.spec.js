/**
 * Playwright smoke test for reservations timeline creation feature
 *
 * Tests:
 * - Access to timeline page
 * - Click on empty slot opens modal
 * - Modal contains form fields
 * - Can cancel without creating
 *
 * Usage:
 *   cd playwright && npx playwright test tests/reservations-timeline-create.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

test.describe('Reservations Timeline - Create Reservation', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    // Login as test admin user
    await loginPage.open();
    await loginPage.login('testadmin', 'password', '1'); // Section Planeur
    await loginPage.verifyLoggedIn();
  });

  test.afterEach(async ({ page }) => {
    if (!page.isClosed()) {
      await loginPage.logout();
    }
  });

  test('should access timeline page successfully', async ({ page }) => {
    // Navigate to reservations timeline
    await page.goto('/index.php/reservations/timeline');

    // Wait for page to load
    await page.waitForLoadState('networkidle');

    // Check that timeline container exists
    const timelineContainer = page.locator('.timeline-container');
    await expect(timelineContainer).toBeVisible();

    // Check that timeline header exists
    const timelineHeader = page.locator('.timeline-header');
    await expect(timelineHeader).toBeVisible();

    console.log('✓ Timeline page loaded successfully');
  });

  test('should open create modal when clicking empty slot', async ({ page }) => {
    // Navigate to timeline
    await page.goto('/index.php/reservations/timeline');
    await page.waitForLoadState('networkidle');

    // Wait for timeline to render
    await page.waitForSelector('.time-slot', { timeout: 5000 });

    // Click on an empty time slot
    const firstSlot = page.locator('.time-slot').first();
    await firstSlot.click();

    // Wait for modal to appear
    await page.waitForSelector('#eventModal.show', { timeout: 3000 });

    // Verify modal title for new reservation
    const modalTitle = page.locator('#eventModalTitle');
    await expect(modalTitle).toContainText('Nouvelle Réservation');

    // Verify form fields exist
    await expect(page.locator('#eventAircraft')).toBeVisible();
    await expect(page.locator('#eventPilot')).toBeVisible();
    await expect(page.locator('#eventStart')).toBeVisible();
    await expect(page.locator('#eventEnd')).toBeVisible();
    await expect(page.locator('#eventInstructor')).toBeVisible();
    await expect(page.locator('#eventNotes')).toBeVisible();
    await expect(page.locator('#eventStatus')).toBeVisible();

    // Verify buttons exist
    await expect(page.locator('#saveEventBtn')).toBeVisible();
    await expect(page.locator('button:has-text("Annuler")')).toBeVisible();

    console.log('✓ Create modal opened successfully with all fields');
  });

  test('should close modal when clicking cancel', async ({ page }) => {
    // Navigate to timeline
    await page.goto('/index.php/reservations/timeline');
    await page.waitForLoadState('networkidle');

    // Wait for timeline and click slot
    await page.waitForSelector('.time-slot', { timeout: 5000 });
    const firstSlot = page.locator('.time-slot').first();
    await firstSlot.click();

    // Wait for modal
    await page.waitForSelector('#eventModal.show', { timeout: 3000 });

    // Click cancel button
    await page.click('button:has-text("Annuler")');

    // Wait for modal to close
    await page.waitForTimeout(500);

    // Verify modal is no longer visible
    const modal = page.locator('#eventModal.show');
    await expect(modal).not.toBeVisible();

    console.log('✓ Modal closes on cancel without creating reservation');
  });

  test('should have aircraft pre-selected from clicked slot', async ({ page }) => {
    // Navigate to timeline
    await page.goto('/index.php/reservations/timeline');
    await page.waitForLoadState('networkidle');

    // Wait for timeline and click slot
    await page.waitForSelector('.time-slot', { timeout: 5000 });
    const firstSlot = page.locator('.time-slot').first();

    // Get the resource ID from the slot
    const resourceId = await firstSlot.getAttribute('data-resource-id');
    console.log('Clicked slot for aircraft ID:', resourceId);

    // Click the slot
    await firstSlot.click();

    // Wait for modal
    await page.waitForSelector('#eventModal.show', { timeout: 3000 });

    // Check that aircraft select has the correct value pre-selected
    const aircraftSelect = page.locator('#eventAircraft');
    const selectedValue = await aircraftSelect.inputValue();

    console.log('Selected aircraft ID in modal:', selectedValue);
    expect(selectedValue).toBe(resourceId);

    console.log('✓ Aircraft pre-selected correctly from clicked slot');
  });
});
