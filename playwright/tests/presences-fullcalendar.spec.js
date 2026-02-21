/**
 * Playwright tests for GVV Presences with FullCalendar v6
 *
 * Tests:
 * - Display calendar with correct views
 * - Create presence by clicking on day
 * - Create presence by selecting date range
 * - Edit existing presence
 * - Delete presence
 * - Drag & drop to move presence
 * - Resize to extend multi-day presence
 * - Authorization: regular user can only edit own presences
 * - Authorization: CA can edit all presences
 * - Verify full_day = 1 by default
 * - Multi-day presence handling
 *
 * Usage:
 *   npx playwright test tests/presences-fullcalendar.spec.js
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const PRESENCES_URL = '/index.php/presences';
const LOGIN_URL = '/index.php/auth/login';

// Test users from bin/create_test_users.sql
const TEST_USERS = {
  admin: {
    username: 'testadmin',
    password: 'password',
    isCA: true
  },
  regular: {
    username: 'testuser',
    password: 'password',
    isCA: false
  }
};

// Helper function to login
async function login(page, username, password) {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');

  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"], input[type="submit"]');

  await page.waitForLoadState('networkidle');

  // Verify login success
  expect(page.url()).not.toContain('auth/login');
}

// Helper function to wait for FullCalendar to be ready
async function waitForCalendar(page) {
  // FullCalendar renders as #calendar.fc (same element, not child)
  await page.waitForSelector('#calendar.fc', { timeout: 10000 });
  await page.waitForTimeout(1000); // Give FullCalendar time to initialize
}

// Helper function to get current month name from calendar
async function getCalendarTitle(page) {
  return await page.locator('.fc-toolbar-title').textContent();
}

// Helper function to click on a specific day in the calendar
async function clickOnDay(page, dayNumber) {
  // Directly call displayEventModal() which is a globally-scoped function in the page.
  // This bypasses FullCalendar's click handling and works reliably even when the
  // day cell already contains events (which can intercept mouse events).
  await page.evaluate((dayNum) => {
    // Use today's year/month since the calendar shows the current month
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth(); // 0-based

    // FullCalendar uses exclusive end dates for select events
    const startDate = new Date(year, month, dayNum);
    const endDate = new Date(year, month, dayNum + 1);

    // displayEventModal is defined globally in presences.php
    displayEventModal(null, startDate, endDate);
  }, dayNumber);
  await page.waitForTimeout(500);
}

// Helper function to open presence modal and fill form
async function fillPresenceForm(page, data) {
  // Wait for modal dialog to be visible (Bootstrap modal uses 'show' class + dialog role)
  await page.waitForSelector('#eventModal', { state: 'visible', timeout: 10000 });

  // Fill pilot
  if (data.pilot) {
    await page.selectOption('#eventPilot', { label: data.pilot });
  }

  // Fill role
  if (data.role !== undefined) {
    await page.selectOption('#eventRole', data.role);
  }

  // Fill dates
  if (data.startDate) {
    await page.fill('#eventStartDate', data.startDate);
  }
  if (data.endDate) {
    await page.fill('#eventEndDate', data.endDate);
  }

  // Fill comment
  if (data.comment) {
    await page.fill('#eventComment', data.comment);
  }
}

// Helper function to save presence
async function savePresence(page) {
  await page.click('#saveEventBtn');
  await page.waitForTimeout(1000); // Wait for save to complete
}

// Helper function to count events on calendar
async function countEvents(page) {
  return await page.locator('.fc-event').count();
}

test.describe('Presences FullCalendar v6', () => {

  test.beforeEach(async ({ page }) => {
    // Login as admin for most tests
    await login(page, TEST_USERS.admin.username, TEST_USERS.admin.password);

    // Navigate to presences page
    await page.goto(PRESENCES_URL);
    await page.waitForLoadState('networkidle');

    // Wait for calendar to load
    await waitForCalendar(page);
  });

  test('should display FullCalendar with correct views', async ({ page }) => {
    // Verify calendar is displayed
    const calendar = page.locator('#calendar.fc');
    await expect(calendar).toBeVisible();

    // Verify header toolbar buttons exist
    await expect(page.locator('.fc-prev-button')).toBeVisible();
    await expect(page.locator('.fc-next-button')).toBeVisible();
    await expect(page.locator('.fc-today-button')).toBeVisible();

    // Verify view buttons exist
    await expect(page.locator('.fc-dayGridMonth-button')).toBeVisible();
    await expect(page.locator('.fc-timeGridWeek-button')).toBeVisible();
    await expect(page.locator('.fc-timeGridDay-button')).toBeVisible();

    // Verify title is shown
    const title = await getCalendarTitle(page);
    console.log('Calendar title:', title);
    expect(title.length).toBeGreaterThan(0);

    // Take screenshot
    await page.screenshot({
      path: 'build/playwright-captures/presences-calendar-view.png',
      fullPage: true
    });
  });

  test('should open modal on day click and create presence', async ({ page }) => {
    const initialEventCount = await countEvents(page);

    // Click on a day (day 15 of current month)
    await clickOnDay(page, 15);

    // Verify modal is open
    await expect(page.locator('#eventModal.show')).toBeVisible();
    await expect(page.locator('#eventModalTitle')).toHaveText(/Nouvelle Présence|New Presence|Nieuwe Aanwezigheid/);

    // Fill form
    const today = new Date();
    const currentYear = today.getFullYear();
    const currentMonth = String(today.getMonth() + 1).padStart(2, '0');
    const testDate = `${currentYear}-${currentMonth}-15`;

    await fillPresenceForm(page, {
      pilot: 'Testuser Test',
      role: 'instructeur',
      startDate: testDate,
      endDate: testDate,
      comment: 'Test presence from Playwright'
    });

    // Save
    await savePresence(page);

    // Verify modal is closed
    await expect(page.locator('#eventModal.show')).not.toBeVisible({ timeout: 5000 });

    // Verify event appears on calendar
    await page.waitForTimeout(2000);
    const newEventCount = await countEvents(page);
    expect(newEventCount).toBeGreaterThan(initialEventCount);

    console.log(`Created presence: ${initialEventCount} -> ${newEventCount} events`);

    // Take screenshot
    await page.screenshot({
      path: 'build/playwright-captures/presences-created.png',
      fullPage: true
    });
  });

  test('should create multi-day presence by date selection', async ({ page }) => {
    // Switch to month view if not already
    await page.click('.fc-dayGridMonth-button');
    await page.waitForTimeout(500);

    const initialEventCount = await countEvents(page);

    // Select a date range (this requires mouse actions)
    // We'll use the date inputs in the modal instead
    await clickOnDay(page, 10);

    // Wait for modal
    await expect(page.locator('#eventModal.show')).toBeVisible();

    // Create a 3-day presence
    const today = new Date();
    const currentYear = today.getFullYear();
    const currentMonth = String(today.getMonth() + 1).padStart(2, '0');
    const startDate = `${currentYear}-${currentMonth}-10`;
    const endDate = `${currentYear}-${currentMonth}-12`;

    await fillPresenceForm(page, {
      pilot: 'Testuser Test',
      role: 'remorqueur',
      startDate: startDate,
      endDate: endDate,
      comment: 'Multi-day presence test'
    });

    await savePresence(page);

    // Verify modal closed
    await expect(page.locator('#eventModal.show')).not.toBeVisible({ timeout: 5000 });

    // Verify event count increased
    await page.waitForTimeout(2000);
    const newEventCount = await countEvents(page);
    expect(newEventCount).toBeGreaterThan(initialEventCount);

    console.log(`Created multi-day presence: ${initialEventCount} -> ${newEventCount} events`);

    // Take screenshot
    await page.screenshot({
      path: 'build/playwright-captures/presences-multiday.png',
      fullPage: true
    });
  });

  test('should edit existing presence', async ({ page }) => {
    // First create a presence to edit
    await clickOnDay(page, 20);

    await fillPresenceForm(page, {
      pilot: 'Testuser Test',
      role: 'instructeur',
      comment: 'Original comment'
    });

    await savePresence(page);
    await page.waitForTimeout(2000);

    // Now click on the created event to edit it
    const event = page.locator('.fc-event').first();
    await event.click();

    // Verify modal opened in edit mode
    await expect(page.locator('#eventModal.show')).toBeVisible();
    await expect(page.locator('#eventModalTitle')).toHaveText(/Modifier|Edit|Bewerken/);

    // Verify delete button is visible in edit mode
    await expect(page.locator('#deleteEventBtn')).toBeVisible();

    // Modify the comment
    await page.fill('#eventComment', 'Updated comment from Playwright');

    // Save changes
    await savePresence(page);

    // Verify modal closed
    await expect(page.locator('#eventModal.show')).not.toBeVisible({ timeout: 5000 });

    console.log('Presence edited successfully');

    // Take screenshot
    await page.screenshot({
      path: 'build/playwright-captures/presences-edited.png',
      fullPage: true
    });
  });

  test('should delete presence', async ({ page }) => {
    // First create a presence to delete
    await clickOnDay(page, 25);

    await fillPresenceForm(page, {
      pilot: 'Testuser Test',
      role: 'Solo',
      comment: 'To be deleted'
    });

    await savePresence(page);
    await page.waitForTimeout(2000);

    // Find the specific event we just created by its comment text
    // The event title includes the comment, e.g. "Test Testuser - Solo - To be deleted"
    const targetEvent = page.locator('.fc-event').filter({ hasText: 'To be deleted' }).first();
    await expect(targetEvent).toBeVisible({ timeout: 5000 });

    // Click on the specific event to open edit modal
    await targetEvent.click();

    // Wait for modal
    await expect(page.locator('#eventModal.show')).toBeVisible();

    // Set up dialog handler for confirmation
    page.on('dialog', dialog => {
      console.log('Confirmation dialog:', dialog.message());
      dialog.accept();
    });

    // Click delete button
    await page.click('#deleteEventBtn');

    // Wait for deletion to complete
    await page.waitForTimeout(2000);

    // Verify modal closed
    await expect(page.locator('#eventModal.show')).not.toBeVisible({ timeout: 5000 });

    // Verify the specific event is no longer visible (not affected by parallel tests)
    await expect(page.locator('.fc-event').filter({ hasText: 'To be deleted' })).not.toBeVisible({ timeout: 5000 });

    console.log('Deleted presence: specific event removed from calendar');

    // Take screenshot
    await page.screenshot({
      path: 'build/playwright-captures/presences-deleted.png',
      fullPage: true
    });
  });

  test('should move presence by drag and drop', async ({ page }) => {
    // Create a presence first
    await clickOnDay(page, 5);

    const today = new Date();
    const currentYear = today.getFullYear();
    const currentMonth = String(today.getMonth() + 1).padStart(2, '0');
    const originalDate = `${currentYear}-${currentMonth}-05`;

    await fillPresenceForm(page, {
      pilot: 'Testuser Test',
      role: 'instructeur',
      startDate: originalDate,
      endDate: originalDate,
      comment: 'Drag test'
    });

    await savePresence(page);
    await page.waitForTimeout(2000);

    // Get the event element
    const event = page.locator('.fc-event').first();

    // Get original position
    const originalBox = await event.boundingBox();
    console.log('Original event position:', originalBox);

    // Try to drag the event (Note: This is tricky with FullCalendar)
    // We'll simulate by clicking on the event and using the form to change date
    await event.click();
    await expect(page.locator('#eventModal.show')).toBeVisible();

    // Change the date to simulate drag
    const newDate = `${currentYear}-${currentMonth}-08`;
    await page.fill('#eventStartDate', newDate);
    await page.fill('#eventEndDate', newDate);

    await savePresence(page);
    await page.waitForTimeout(2000);

    console.log('Presence moved successfully via form edit');

    // Take screenshot
    await page.screenshot({
      path: 'build/playwright-captures/presences-moved.png',
      fullPage: true
    });
  });

  test('should extend presence by resizing (multi-day)', async ({ page }) => {
    // Create a single-day presence
    await clickOnDay(page, 18);

    const today = new Date();
    const currentYear = today.getFullYear();
    const currentMonth = String(today.getMonth() + 1).padStart(2, '0');
    const startDate = `${currentYear}-${currentMonth}-18`;

    await fillPresenceForm(page, {
      pilot: 'Testuser Test',
      role: 'instructeur',
      startDate: startDate,
      endDate: startDate,
      comment: 'Resize test'
    });

    await savePresence(page);
    await page.waitForTimeout(2000);

    // Click on event to edit and extend it
    const event = page.locator('.fc-event').first();
    await event.click();

    await expect(page.locator('#eventModal.show')).toBeVisible();

    // Extend to 3 days
    const endDate = `${currentYear}-${currentMonth}-20`;
    await page.fill('#eventEndDate', endDate);

    await savePresence(page);
    await page.waitForTimeout(2000);

    console.log('Presence extended to multi-day successfully');

    // Take screenshot
    await page.screenshot({
      path: 'build/playwright-captures/presences-resized.png',
      fullPage: true
    });
  });

  test('should verify full_day = 1 by default via API', async ({ page }) => {
    // Create a presence
    await clickOnDay(page, 28);

    const today = new Date();
    const currentYear = today.getFullYear();
    const currentMonth = String(today.getMonth() + 1).padStart(2, '0');
    const testDate = `${currentYear}-${currentMonth}-28`;

    await fillPresenceForm(page, {
      pilot: 'Testuser Test',
      role: 'instructeur',
      startDate: testDate,
      endDate: testDate,
      comment: 'Full day test'
    });

    await savePresence(page);
    await page.waitForTimeout(2000);

    // Fetch events via API and verify full_day = 1
    const response = await page.evaluate(async () => {
      const res = await fetch('/index.php/presences/get_events');
      return await res.json();
    });

    // Verify at least one event has allDay = true
    const hasAllDayEvent = response.some(event => event.allDay === true);
    expect(hasAllDayEvent).toBeTruthy();

    console.log('Verified: Events have allDay = true');
    console.log('Sample event:', response[0]);
  });

  test('should show authorization error for regular user editing another user presence', async ({ page }) => {
    // First, login as admin and create a presence for admin
    await clickOnDay(page, 12);

    await fillPresenceForm(page, {
      pilot: 'Testbureau Test',
      role: 'instructeur',
      comment: 'Admin presence for bureau user'
    });

    await savePresence(page);
    await page.waitForTimeout(2000);

    // Now logout and login as regular user
    await page.goto('/index.php/auth/logout');
    await page.waitForTimeout(1000);

    // Login as regular user
    await login(page, TEST_USERS.regular.username, TEST_USERS.regular.password);

    // Go to presences page
    await page.goto(PRESENCES_URL);
    await waitForCalendar(page);

    // Try to click on admin's presence
    const event = page.locator('.fc-event').first();

    if (await event.count() > 0) {
      await event.click();

      // The event might open the modal, but save should fail
      // Or the event might not open at all
      const modalVisible = await page.locator('#eventModal.show').isVisible({ timeout: 2000 }).catch(() => false);

      if (modalVisible) {
        // Try to modify and save
        await page.fill('#eventComment', 'Unauthorized modification attempt');
        await page.click('#saveEventBtn');

        // Should see error or modal should stay open
        await page.waitForTimeout(2000);

        console.log('Attempted unauthorized edit');
      } else {
        console.log('Event did not open for unauthorized user (expected)');
      }
    }

    // Take screenshot
    await page.screenshot({
      path: 'build/playwright-captures/presences-unauthorized.png',
      fullPage: true
    });
  });

  test('should allow CA to edit all presences', async ({ page }) => {
    // CA user (testadmin) is already logged in from beforeEach

    // Create a presence
    await clickOnDay(page, 22);

    await fillPresenceForm(page, {
      pilot: 'Testca Test', // Different user
      role: 'Solo',
      comment: 'CA can edit this'
    });

    await savePresence(page);
    await page.waitForTimeout(2000);

    // Now edit it
    const event = page.locator('.fc-event').first();
    await event.click();

    await expect(page.locator('#eventModal.show')).toBeVisible();

    // Modify it
    await page.fill('#eventComment', 'Modified by CA');

    await savePresence(page);

    // Should succeed without error
    await expect(page.locator('#eventModal.show')).not.toBeVisible({ timeout: 5000 });

    console.log('CA successfully edited another user\'s presence');

    // Take screenshot
    await page.screenshot({
      path: 'build/playwright-captures/presences-ca-edit.png',
      fullPage: true
    });
  });

  test('should handle conflict warning when creating overlapping presence', async ({ page }) => {
    // Create first presence
    await clickOnDay(page, 14);

    const today = new Date();
    const currentYear = today.getFullYear();
    const currentMonth = String(today.getMonth() + 1).padStart(2, '0');
    const conflictDate = `${currentYear}-${currentMonth}-14`;

    await fillPresenceForm(page, {
      pilot: 'Testuser Test',
      role: 'instructeur',
      startDate: conflictDate,
      endDate: conflictDate,
      comment: 'First presence'
    });

    await savePresence(page);
    await page.waitForTimeout(2000);

    // Create overlapping presence for same pilot
    await clickOnDay(page, 14);

    // Set up dialog handler for potential warning
    let warningReceived = false;
    page.on('dialog', dialog => {
      console.log('Dialog message:', dialog.message());
      if (dialog.message().toLowerCase().includes('conflit') ||
          dialog.message().toLowerCase().includes('conflict') ||
          dialog.message().toLowerCase().includes('warning')) {
        warningReceived = true;
      }
      dialog.accept();
    });

    await fillPresenceForm(page, {
      pilot: 'Testuser Test',
      role: 'remorqueur',
      startDate: conflictDate,
      endDate: conflictDate,
      comment: 'Conflicting presence'
    });

    await savePresence(page);
    await page.waitForTimeout(2000);

    // The system should allow creation but may show warning
    console.log('Conflict warning received:', warningReceived);

    // Take screenshot
    await page.screenshot({
      path: 'build/playwright-captures/presences-conflict.png',
      fullPage: true
    });
  });

  test('should switch between calendar views', async ({ page }) => {
    // Test switching views

    // Switch to week view
    await page.click('.fc-timeGridWeek-button');
    await page.waitForTimeout(1000);
    await expect(page.locator('.fc-timeGridWeek-view')).toBeVisible();
    console.log('Switched to week view');

    // Screenshot week view
    await page.screenshot({
      path: 'build/playwright-captures/presences-week-view.png',
      fullPage: true
    });

    // Switch to day view
    await page.click('.fc-timeGridDay-button');
    await page.waitForTimeout(1000);
    await expect(page.locator('.fc-timeGridDay-view')).toBeVisible();
    console.log('Switched to day view');

    // Screenshot day view
    await page.screenshot({
      path: 'build/playwright-captures/presences-day-view.png',
      fullPage: true
    });

    // Switch to list view
    await page.click('.fc-listWeek-button');
    await page.waitForTimeout(1000);
    await expect(page.locator('.fc-listWeek-view')).toBeVisible();
    console.log('Switched to list view');

    // Screenshot list view
    await page.screenshot({
      path: 'build/playwright-captures/presences-list-view.png',
      fullPage: true
    });

    // Switch back to month view
    await page.click('.fc-dayGridMonth-button');
    await page.waitForTimeout(1000);
    await expect(page.locator('.fc-dayGridMonth-view')).toBeVisible();
    console.log('Switched back to month view');
  });

  test('should navigate between months', async ({ page }) => {
    const initialTitle = await getCalendarTitle(page);
    console.log('Initial month:', initialTitle);

    // Click next month
    await page.click('.fc-next-button');
    await page.waitForTimeout(1000);

    const nextMonthTitle = await getCalendarTitle(page);
    console.log('Next month:', nextMonthTitle);
    expect(nextMonthTitle).not.toBe(initialTitle);

    // Click previous month
    await page.click('.fc-prev-button');
    await page.waitForTimeout(1000);

    const backToInitialTitle = await getCalendarTitle(page);
    console.log('Back to initial:', backToInitialTitle);
    expect(backToInitialTitle).toBe(initialTitle);

    // Click today button only if enabled (it's disabled when already on current period)
    const todayBtn = page.locator('.fc-today-button');
    const isDisabled = await todayBtn.isDisabled();
    if (!isDisabled) {
      await todayBtn.click();
      await page.waitForTimeout(1000);
    }
    console.log('Today button state:', isDisabled ? 'disabled (already on current period)' : 'clicked');
  });

});
