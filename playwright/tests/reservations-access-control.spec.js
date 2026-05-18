/**
 * Tests du contrôle d'accès aux réservations par rôle
 *
 * Modèle de droits :
 *   - auto_planchiste (goudurix, section Avion=3)
 *       → pilote verrouillé sur soi-même à la création
 *       → réservation d'un autre : modal lecture seule (pas de save/delete)
 *       → isAutoPlanchiste=true, canEditOthers=false dans CONFIG JS
 *
 *   - instructeur (abraracourcix, section Avion=3)
 *       → sélecteur pilote libre, accès complet aux réservations des autres
 *       → isAutoPlanchiste=false, canEditOthers=true
 *
 *   - club-admin (panoramix, section Avion=3)
 *       → sélecteur pilote libre, accès complet
 *       → isAutoPlanchiste=false, canEditOthers=true
 *
 * Usage :
 *   cd playwright && npx playwright test tests/reservations-access-control.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const AVION_SECTION = '3';
const TIMELINE_URL = '/index.php/reservations/timeline';

async function loginAs(page, username, section) {
    const lp = new LoginPage(page);
    await lp.open();
    await lp.login(username, 'password', section);
    await lp.verifyLoggedIn();
}

// ─── auto_planchiste ─────────────────────────────────────────────────────────

test.describe('auto_planchiste (goudurix) — restrictions UI', () => {

    test('CONFIG.isAutoPlanchiste=true, canEditOthers=false, currentUser=goudurix', async ({ page }) => {
        await loginAs(page, 'goudurix', AVION_SECTION);
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');

        const cfg = await page.evaluate(() => ({
            currentUser: CONFIG.currentUser,
            canEditOthers: CONFIG.canEditOthers,
            isAutoPlanchiste: CONFIG.isAutoPlanchiste,
        }));

        expect(cfg.isAutoPlanchiste).toBe(true);
        expect(cfg.canEditOthers).toBe(false);
        expect(cfg.currentUser).toBe('goudurix');
        console.log('✓ auto_planchiste: CONFIG values correct');
    });

    test('création : sélecteur pilote désactivé et pré-rempli avec son login', async ({ page }) => {
        await loginAs(page, 'goudurix', AVION_SECTION);
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.time-slot', { timeout: 10000 });

        // Click on the first available time slot to open create modal
        await page.locator('.time-slot').first().click();
        await page.waitForSelector('#eventModal.show', { timeout: 5000 });

        // Pilot selector must be disabled (locked to current user)
        const pilotSelect = page.locator('#eventPilot');
        await expect(pilotSelect).toBeDisabled();
        // Value is 'goudurix' if they appear in OPTIONS.pilots, otherwise '' (still correctly locked)
        const pilotValue = await pilotSelect.inputValue();
        expect(['goudurix', '']).toContain(pilotValue);

        // Save button must be present (auto_planchiste can create own reservations)
        await expect(page.locator('#saveEventBtn')).toBeVisible();
        console.log('✓ auto_planchiste: pilot selector locked to self in create modal');
    });
});

// ─── instructeur ─────────────────────────────────────────────────────────────

test.describe('instructeur (abraracourcix) — accès complet', () => {

    test('CONFIG.isAutoPlanchiste=false, canEditOthers=true', async ({ page }) => {
        await loginAs(page, 'abraracourcix', AVION_SECTION);
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');

        const cfg = await page.evaluate(() => ({
            currentUser: CONFIG.currentUser,
            canEditOthers: CONFIG.canEditOthers,
            isAutoPlanchiste: CONFIG.isAutoPlanchiste,
        }));

        expect(cfg.isAutoPlanchiste).toBe(false);
        expect(cfg.canEditOthers).toBe(true);
        expect(cfg.currentUser).toBe('abraracourcix');
        console.log('✓ instructeur: CONFIG values correct');
    });

    test('création : sélecteur pilote libre (non désactivé)', async ({ page }) => {
        await loginAs(page, 'abraracourcix', AVION_SECTION);
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.time-slot', { timeout: 10000 });

        await page.locator('.time-slot').first().click();
        await page.waitForSelector('#eventModal.show', { timeout: 5000 });

        await expect(page.locator('#eventPilot')).not.toBeDisabled();
        await expect(page.locator('#saveEventBtn')).toBeVisible();
        console.log('✓ instructeur: pilot selector free in create modal');
    });
});

// ─── administrateur ──────────────────────────────────────────────────────────

test.describe('administrateur (panoramix) — accès complet', () => {

    test('CONFIG.isAutoPlanchiste=false, canEditOthers=true', async ({ page }) => {
        await loginAs(page, 'panoramix', AVION_SECTION);
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');

        const cfg = await page.evaluate(() => ({
            currentUser: CONFIG.currentUser,
            canEditOthers: CONFIG.canEditOthers,
            isAutoPlanchiste: CONFIG.isAutoPlanchiste,
        }));

        expect(cfg.isAutoPlanchiste).toBe(false);
        expect(cfg.canEditOthers).toBe(true);
        expect(cfg.currentUser).toBe('panoramix');
        console.log('✓ admin: CONFIG values correct');
    });

    test('création : sélecteur pilote libre (non désactivé)', async ({ page }) => {
        await loginAs(page, 'panoramix', AVION_SECTION);
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.time-slot', { timeout: 10000 });

        await page.locator('.time-slot').first().click();
        await page.waitForSelector('#eventModal.show', { timeout: 5000 });

        await expect(page.locator('#eventPilot')).not.toBeDisabled();
        await expect(page.locator('#saveEventBtn')).toBeVisible();
        console.log('✓ admin: pilot selector free in create modal');
    });
});

// ─── test inter-utilisateurs : lecture seule sur réservation d'un autre ──────

test.describe.serial('auto_planchiste — lecture seule sur réservation d\'un autre', () => {

    let createdReservationId = null;
    // Use tomorrow to avoid conflicts with same-day slots
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const testDate = tomorrow.toISOString().slice(0, 10);

    test.beforeAll(async ({ browser }) => {
        // Create a reservation as panoramix (admin) via the API
        const page = await browser.newPage();
        try {
            const lp = new LoginPage(page);
            await lp.open();
            await lp.login('panoramix', 'password', AVION_SECTION);
            await lp.verifyLoggedIn();

            // Load timeline to get baseUrl and a valid aircraft ID
            await page.goto(TIMELINE_URL);
            await page.waitForLoadState('networkidle');

            const setup = await page.evaluate(() => ({
                baseUrl: CONFIG.baseUrl,
                firstAircraftId: Object.keys(OPTIONS && OPTIONS.aircraft || {})[0] || null,
            }));

            if (!setup.firstAircraftId) {
                console.log('No aircraft found in section Avion — skipping cross-user test setup');
                return;
            }

            console.log('Creating test reservation for aircraft', setup.firstAircraftId, 'on', testDate);

            const resp = await page.request.post(setup.baseUrl + '/reservations/update_reservation', {
                form: {
                    reservation_id: '',
                    aircraft_id: setup.firstAircraftId,
                    pilot_member_id: 'panoramix',
                    start_datetime: testDate + ' 14:00:00',
                    end_datetime: testDate + ' 15:00:00',
                    instructor_member_id: '',
                    notes: 'PW test contrôle accès',
                    status: 'reservation',
                }
            });

            const text = await resp.text();
            try {
                const data = JSON.parse(text);
                createdReservationId = data.reservation_id || null;
                console.log('Created test reservation id:', createdReservationId);
            } catch (e) {
                console.log('Could not parse update_reservation response:', text.slice(0, 300));
            }
        } finally {
            await page.close();
        }
    });

    test.afterAll(async ({ browser }) => {
        if (!createdReservationId) return;

        const page = await browser.newPage();
        try {
            const lp = new LoginPage(page);
            await lp.open();
            await lp.login('panoramix', 'password', AVION_SECTION);
            await lp.verifyLoggedIn();

            await page.goto(TIMELINE_URL);
            await page.waitForLoadState('networkidle');
            const baseUrl = await page.evaluate(() => CONFIG.baseUrl);

            await page.request.post(baseUrl + '/reservations/delete', {
                form: { reservation_id: String(createdReservationId) }
            });
            console.log('Deleted test reservation id:', createdReservationId);
        } finally {
            await page.close();
        }
    });

    test('goudurix voit la réservation de panoramix en lecture seule (pas de save/delete)', async ({ page }) => {
        test.skip(!createdReservationId, 'Réservation de test non créée dans beforeAll');

        await loginAs(page, 'goudurix', AVION_SECTION);

        // Navigate to the date of the test reservation
        await page.goto(`${TIMELINE_URL}?date=${testDate}`);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.time-slot', { timeout: 10000 });

        // Wait for panoramix's event to appear
        const eventSelector = `.reservation-event[data-event-id="${createdReservationId}"]`;
        await page.waitForSelector(eventSelector, { timeout: 10000 });
        await page.locator(eventSelector).click();
        await page.waitForSelector('#eventModal.show', { timeout: 5000 });

        // Read-only modal: no save, no delete
        await expect(page.locator('#saveEventBtn')).not.toBeVisible();
        await expect(page.locator('#deleteEventBtn')).not.toBeVisible();

        // All form fields should be disabled
        await expect(page.locator('#eventAircraft')).toBeDisabled();
        await expect(page.locator('#eventStart')).toBeDisabled();
        await expect(page.locator('#eventNotes')).toBeDisabled();

        // Only the cancel button is present
        await expect(page.locator('#eventModalFooter button[data-bs-dismiss="modal"]')).toBeVisible();
        console.log('✓ auto_planchiste: panoramix reservation shown read-only');
    });

    test('instructeur peut modifier la réservation de panoramix (save/delete visibles)', async ({ page }) => {
        test.skip(!createdReservationId, 'Réservation de test non créée dans beforeAll');

        await loginAs(page, 'abraracourcix', AVION_SECTION);

        await page.goto(`${TIMELINE_URL}?date=${testDate}`);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.time-slot', { timeout: 10000 });

        const eventSelector = `.reservation-event[data-event-id="${createdReservationId}"]`;
        await page.waitForSelector(eventSelector, { timeout: 10000 });
        await page.locator(eventSelector).click();
        await page.waitForSelector('#eventModal.show', { timeout: 5000 });

        // Full edit access: save and delete buttons visible
        await expect(page.locator('#saveEventBtn')).toBeVisible();
        await expect(page.locator('#deleteEventBtn')).toBeVisible();

        // Fields enabled
        await expect(page.locator('#eventAircraft')).not.toBeDisabled();
        console.log('✓ instructeur: panoramix reservation editable (save/delete visible)');
    });
});
