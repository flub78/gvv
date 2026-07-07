/**
 * Régression : un non-admin peut déplacer (drag) ou modifier une réservation
 * future pour la faire commencer dans le passé. C'est interdit.
 *
 * Cause : les gardes anti-passé ne vérifiaient que la date de départ existante
 * de la réservation (avant modification), jamais la nouvelle date proposée :
 *  - reservations::on_event_drop()     ne contrôlait que $reservation['start_datetime']
 *  - reservations::update_reservation() (branche édition) ne contrôlait que
 *    $existing['start_datetime']
 * Résultat : une réservation future pouvait être déplacée/éditée pour démarrer
 * dans le passé sans être bloquée — après quoi elle devenait elle-même
 * verrouillée (car désormais "passée"), ce qui correspond au symptôme
 * rapporté ("il ne peut plus la modifier").
 *
 * Utilisateur de test : abraracourcix (instructeur, section Avion=3, non
 * club-admin, exempté du contrôle de solde) pour isoler le test du contrôle
 * de solde.
 *
 * Usage :
 *   cd playwright && npx playwright test tests/reservations-move-into-past.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const AVION_SECTION = '3';
const TIMELINE_URL = '/index.php/reservations/timeline';
const PILOT = 'abraracourcix';

function isoDate(offsetDays) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    return d.toISOString().slice(0, 10);
}

async function loginAndGetContext(page) {
    const lp = new LoginPage(page);
    await lp.open();
    await lp.login(PILOT, 'password', AVION_SECTION);
    await lp.verifyLoggedIn();
    await page.goto(TIMELINE_URL);
    await page.waitForLoadState('networkidle');
    return page.evaluate(() => ({
        baseUrl: CONFIG.baseUrl,
        aircraftId: Object.keys((typeof OPTIONS !== 'undefined' && OPTIONS.aircraft) || {})[0] || null,
    }));
}

// Deletes via a club-admin session: a non-admin cannot delete a reservation
// that ended up in the past (which is exactly what happens when the bug
// under test is not fixed yet), so cleanup must not depend on the fix.
async function deleteReservationAsAdmin(browser, baseUrl, id) {
    if (!id) return;
    const page = await browser.newPage();
    try {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('panoramix', 'password', AVION_SECTION);
        await lp.verifyLoggedIn();
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');
        await page.request.post(baseUrl + '/reservations/delete', {
            form: { reservation_id: String(id) }
        });
    } finally {
        await page.close();
    }
}

test.describe('Réservations - verrou passé sur déplacement et édition', () => {

    test('glisser une réservation future vers le passé doit être refusé', async ({ page, browser }) => {
        const { baseUrl, aircraftId } = await loginAndGetContext(page);
        test.skip(!aircraftId, 'Aucun aéronef trouvé en section Avion');

        // Use distant dates to avoid colliding with other tests' fixture data
        const futureDate = isoDate(400);
        let createdId = null;
        try {
            // Create a future reservation
            const createResp = await page.request.post(baseUrl + '/reservations/update_reservation', {
                form: {
                    reservation_id: '',
                    aircraft_id: aircraftId,
                    pilot_member_id: PILOT,
                    start_datetime: futureDate + ' 14:00:00',
                    end_datetime: futureDate + ' 15:00:00',
                    instructor_member_id: '',
                    notes: 'PW test drag into past',
                    status: 'reservation',
                }
            });
            const createData = JSON.parse(await createResp.text());
            expect(createData.success).toBe(true);
            createdId = createData.reservation_id;

            // Drag it into the past
            const pastDate = isoDate(-400);
            const dropResp = await page.request.post(baseUrl + '/reservations/on_event_drop', {
                form: {
                    event_id: String(createdId),
                    start_datetime: pastDate + ' 14:00:00',
                    end_datetime: pastDate + ' 15:00:00',
                    resource_id: aircraftId,
                    action: 'move',
                }
            });
            const dropData = JSON.parse(await dropResp.text());

            expect(dropData.success).toBe(false);
            expect(dropData.error).toMatch(/pass[ée]e?s?/i);
            console.log('✓ Déplacement vers le passé refusé :', dropData.error);
        } finally {
            await deleteReservationAsAdmin(browser, baseUrl, createdId);
        }
    });

    test('éditer une réservation future pour la faire démarrer dans le passé doit être refusé', async ({ page, browser }) => {
        const { baseUrl, aircraftId } = await loginAndGetContext(page);
        test.skip(!aircraftId, 'Aucun aéronef trouvé en section Avion');

        const futureDate = isoDate(410);
        let createdId = null;
        try {
            const createResp = await page.request.post(baseUrl + '/reservations/update_reservation', {
                form: {
                    reservation_id: '',
                    aircraft_id: aircraftId,
                    pilot_member_id: PILOT,
                    start_datetime: futureDate + ' 14:00:00',
                    end_datetime: futureDate + ' 15:00:00',
                    instructor_member_id: '',
                    notes: 'PW test edit into past',
                    status: 'reservation',
                }
            });
            const createData = JSON.parse(await createResp.text());
            expect(createData.success).toBe(true);
            createdId = createData.reservation_id;

            // Edit it to start in the past
            const pastDate = isoDate(-410);
            const editResp = await page.request.post(baseUrl + '/reservations/update_reservation', {
                form: {
                    reservation_id: String(createdId),
                    aircraft_id: aircraftId,
                    pilot_member_id: PILOT,
                    start_datetime: pastDate + ' 14:00:00',
                    end_datetime: pastDate + ' 15:00:00',
                    instructor_member_id: '',
                    notes: 'PW test edit into past',
                    status: 'reservation',
                }
            });
            const editData = JSON.parse(await editResp.text());

            expect(editData.success).toBe(false);
            expect(editData.error).toMatch(/pass[ée]e?s?/i);
            console.log('✓ Édition vers le passé refusée :', editData.error);
        } finally {
            await deleteReservationAsAdmin(browser, baseUrl, createdId);
        }
    });
});
