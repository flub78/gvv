/**
 * Point d'écriture unique des réservations : un déplacement au glisser-déposer
 * du planning prévient désormais les rappels, comme une modification faite
 * dans le formulaire.
 *
 * Utilisateurs de test (section ULM=2) :
 *  - obelix    : pilote auto_planchiste, exempté du contrôle de solde
 *  - panoramix : club-admin, crée et nettoie la réservation de test
 *
 * Usage :
 *   cd playwright && npx playwright test tests/reservations-drag-drop-reminder.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const LoginPage = require('./helpers/LoginPage');
const { DB_CONFIG } = require('./helpers/gvv-config');

const ULM_SECTION = '2';
const TIMELINE_URL = '/index.php/reservations/timeline';
const PILOT = 'obelix';
const ADMIN = 'panoramix';

function isoDate(offsetDays) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    return d.toISOString().slice(0, 10);
}

async function openSession(browser, username, section) {
    const page = await browser.newPage();
    const lp = new LoginPage(page);
    await lp.open();
    await lp.login(username, 'password', section);
    await lp.verifyLoggedIn();
    await page.goto(TIMELINE_URL);
    await page.waitForLoadState('networkidle');
    const ctx = await page.evaluate(() => ({
        baseUrl: CONFIG.baseUrl,
        aircraftIds: Object.keys((typeof OPTIONS !== 'undefined' && OPTIONS.aircraft) || {}),
    }));
    return { page, ...ctx };
}

async function post(session, path, form) {
    const resp = await session.page.request.post(session.baseUrl + path, { form });
    return JSON.parse(await resp.text());
}

test.describe('Réservations - rappels après un glisser-déposer', () => {

    /**
     * @feature Réservations
     * Scénario: Un pilote déplace sa réservation au glisser-déposer
     *   Étant donné une réservation future d'un pilote
     *   Et les rappels de réservation actifs dans sa section
     *   Quand le pilote la fait glisser à une autre heure dans le planning
     *   Alors le déplacement est accepté
     *   Et le mécanisme de rappel est prévenu de la modification
     */
    test('le glisser-déposer prévient les rappels', async ({ browser }) => {
        const conn = await mysql.createConnection(DB_CONFIG);
        const admin = await openSession(browser, ADMIN, ULM_SECTION);
        const pilot = await openSession(browser, PILOT, ULM_SECTION);
        let reservationId = null;
        let previousFlag = null;
        try {
            test.skip(!admin.aircraftIds.length, 'Aucun aéronef trouvé en section ULM');

            // Rappels actifs dans la section, valeur d'origine restaurée à la fin
            const [rows] = await conn.execute('SELECT reservation_reminders_enabled AS f FROM sections WHERE id = ?', [ULM_SECTION]);
            previousFlag = rows[0].f;
            await conn.execute('UPDATE sections SET reservation_reminders_enabled = 1 WHERE id = ?', [ULM_SECTION]);

            const day = isoDate(430);
            let aircraftId = null;
            for (const candidate of admin.aircraftIds) {
                const data = await post(admin, '/reservations/update_reservation', {
                    reservation_id: '',
                    aircraft_id: candidate,
                    pilot_member_id: PILOT,
                    start_datetime: day + ' 09:00:00',
                    end_datetime: day + ' 10:00:00',
                    instructor_member_id: '',
                    notes: 'PW test drag drop reminder',
                    status: 'reservation',
                });
                if (data.success) {
                    reservationId = data.reservation_id;
                    aircraftId = candidate;
                    break;
                }
            }
            expect(reservationId, 'réservation de test créée').toBeTruthy();

            const data = await post(pilot, '/reservations/on_event_drop', {
                event_id: String(reservationId),
                start_datetime: day + ' 11:00:00',
                end_datetime: day + ' 12:00:00',
                resource_id: aircraftId,
                action: 'move',
            });
            expect(data.success, data.error).toBe(true);

            const [logs] = await conn.execute(
                "SELECT COUNT(*) AS n FROM reservation_reminder_log WHERE reservation_id = ? AND trigger_source = 'event_update'",
                [reservationId]
            );
            expect(Number(logs[0].n)).toBeGreaterThan(0);
        } finally {
            if (reservationId) {
                await post(admin, '/reservations/delete', { reservation_id: String(reservationId) });
                await conn.execute('DELETE FROM reservations WHERE id = ?', [reservationId]);
                await conn.execute('DELETE FROM reservation_reminder_log WHERE reservation_id = ?', [reservationId]);
            }
            if (previousFlag !== null) {
                await conn.execute('UPDATE sections SET reservation_reminders_enabled = ? WHERE id = ?', [previousFlag, ULM_SECTION]);
            }
            await pilot.page.close();
            await admin.page.close();
            await conn.end();
        }
    });
});
