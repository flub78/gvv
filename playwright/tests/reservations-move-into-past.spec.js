/**
 * Verrou des réservations dans le passé.
 *
 * Règle : pour un pilote sans droit de gestion des réservations d'autrui
 * (ni club-admin, ni instructeur, ni gestionnaire VD) :
 *  - une réservation terminée ne peut plus être modifiée ni supprimée ;
 *  - une réservation en cours (commencée mais pas terminée) reste modifiable
 *    et supprimable, mais son début ne peut pas être déplacé dans le passé ;
 *  - une réservation future ne peut pas être déplacée ou modifiée pour
 *    commencer dans le passé.
 * Les club-admins, instructeurs et gestionnaires VD ne sont pas soumis au verrou.
 *
 * Utilisateurs de test (section Avion=3) :
 *  - goudurix      : auto_planchiste, soumis au verrou
 *  - abraracourcix : instructeur, exempté
 *  - panoramix     : club-admin, crée et nettoie les fixtures (exempté du
 *                    contrôle de solde et du verrou)
 *
 * Usage :
 *   cd playwright && npx playwright test tests/reservations-move-into-past.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const AVION_SECTION = '3';
const TIMELINE_URL = '/index.php/reservations/timeline';
const PILOT = 'goudurix';
const INSTRUCTOR = 'abraracourcix';
const ADMIN = 'panoramix';

function pad(n) { return String(n).padStart(2, '0'); }

// Local "YYYY-MM-DD HH:MM:00", rounded down to the quarter hour so that the
// server-side snapping to the timeline increment does not alter it.
function localDatetime(offsetMinutes) {
    const d = new Date(Date.now() + offsetMinutes * 60000);
    d.setMinutes(d.getMinutes() - (d.getMinutes() % 15), 0, 0);
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
}

function isoDate(offsetDays) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    return d.toISOString().slice(0, 10);
}

async function openSession(browser, username) {
    const page = await browser.newPage();
    const lp = new LoginPage(page);
    await lp.open();
    await lp.login(username, 'password', AVION_SECTION);
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

// Creates a reservation for PILOT as admin, trying each aircraft until one is
// free on the requested slot (the test database holds real reservations).
async function createAsAdmin(admin, start, end, notes) {
    for (const aircraftId of admin.aircraftIds) {
        const data = await post(admin, '/reservations/update_reservation', {
            reservation_id: '',
            aircraft_id: aircraftId,
            pilot_member_id: PILOT,
            start_datetime: start,
            end_datetime: end,
            instructor_member_id: '',
            notes,
            status: 'reservation',
        });
        if (data.success) return { id: data.reservation_id, aircraftId };
    }
    throw new Error('Impossible de créer la réservation de test sur un aéronef libre');
}

async function deleteAsAdmin(admin, id) {
    if (!id) return;
    await post(admin, '/reservations/delete', { reservation_id: String(id) });
}

test.describe('Réservations - verrou des réservations passées', () => {
    // Tests share the same pilot and the in-progress ones overlap in time:
    // run them one after the other.
    test.describe.configure({ mode: 'default' });
    let admin;

    test.beforeEach(async ({ browser }) => {
        admin = await openSession(browser, ADMIN);
    });

    test.afterEach(async () => {
        if (admin) await admin.page.close();
    });

    /**
     * @feature Réservations
     * Scénario: Un pilote ne peut pas déplacer une réservation future dans le passé
     *   Étant donné une réservation future d'un pilote sans droit de gestion des réservations
     *   Quand ce pilote la fait glisser sur une date passée
     *   Alors le déplacement est refusé avec un message sur les réservations passées
     */
    test('glisser une réservation future vers le passé doit être refusé', async ({ browser }) => {
        test.skip(!admin.aircraftIds.length, 'Aucun aéronef trouvé en section Avion');
        const futureDate = isoDate(400);
        let created = null;
        const pilot = await openSession(browser, PILOT);
        try {
            created = await createAsAdmin(admin, futureDate + ' 14:00:00', futureDate + ' 15:00:00', 'PW test drag into past');
            const pastDate = isoDate(-400);
            const data = await post(pilot, '/reservations/on_event_drop', {
                event_id: String(created.id),
                start_datetime: pastDate + ' 14:00:00',
                end_datetime: pastDate + ' 15:00:00',
                resource_id: created.aircraftId,
                action: 'move',
            });
            expect(data.success).toBe(false);
            expect(data.error).toMatch(/pass[ée]e?s?/i);
        } finally {
            await pilot.page.close();
            await deleteAsAdmin(admin, created && created.id);
        }
    });

    /**
     * @feature Réservations
     * Scénario: Un pilote ne peut pas modifier une réservation future pour la faire commencer dans le passé
     *   Étant donné une réservation future d'un pilote sans droit de gestion des réservations
     *   Quand ce pilote change ses dates pour une date passée
     *   Alors la modification est refusée avec un message sur les réservations passées
     */
    test('éditer une réservation future pour la faire démarrer dans le passé doit être refusé', async ({ browser }) => {
        test.skip(!admin.aircraftIds.length, 'Aucun aéronef trouvé en section Avion');
        const futureDate = isoDate(410);
        let created = null;
        const pilot = await openSession(browser, PILOT);
        try {
            created = await createAsAdmin(admin, futureDate + ' 14:00:00', futureDate + ' 15:00:00', 'PW test edit into past');
            const pastDate = isoDate(-410);
            const data = await post(pilot, '/reservations/update_reservation', {
                reservation_id: String(created.id),
                aircraft_id: created.aircraftId,
                pilot_member_id: PILOT,
                start_datetime: pastDate + ' 14:00:00',
                end_datetime: pastDate + ' 15:00:00',
                instructor_member_id: '',
                notes: 'PW test edit into past',
                status: 'reservation',
            });
            expect(data.success).toBe(false);
            expect(data.error).toMatch(/pass[ée]e?s?/i);
        } finally {
            await pilot.page.close();
            await deleteAsAdmin(admin, created && created.id);
        }
    });

    /**
     * @feature Réservations
     * Scénario: La prolongation d'une réservation en cours n'est pas bloquée comme réservation passée
     *   Étant donné une réservation commencée il y a une heure et pas encore terminée
     *   Quand le pilote repousse l'heure de fin
     *   Alors la modification n'est pas refusée au motif que la réservation est passée
     */
    test('une réservation en cours n\'est pas verrouillée pour la prolongation', async ({ browser }) => {
        test.skip(!admin.aircraftIds.length, 'Aucun aéronef trouvé en section Avion');
        let created = null;
        const pilot = await openSession(browser, PILOT);
        try {
            const start = localDatetime(-60);
            created = await createAsAdmin(admin, start, localDatetime(120), 'PW test in progress extend');
            const data = await post(pilot, '/reservations/on_event_drop', {
                event_id: String(created.id),
                start_datetime: start,
                end_datetime: localDatetime(180),
                resource_id: created.aircraftId,
                action: 'resize',
            });
            // Only the past lock is under test: the pilot's balance may still
            // refuse the change, which is checked after the lock.
            if (!data.success) {
                expect(data.error || '').not.toMatch(/pass[ée]e?s?/i);
            }
        } finally {
            await pilot.page.close();
            await deleteAsAdmin(admin, created && created.id);
        }
    });

    /**
     * @feature Réservations
     * Scénario: Un pilote peut annuler sa réservation en cours
     *   Étant donné une réservation commencée il y a une heure et pas encore terminée
     *   Quand le pilote la supprime
     *   Alors la suppression est acceptée
     */
    test('une réservation en cours peut être supprimée par le pilote', async ({ browser }) => {
        test.skip(!admin.aircraftIds.length, 'Aucun aéronef trouvé en section Avion');
        let created = null;
        const pilot = await openSession(browser, PILOT);
        try {
            created = await createAsAdmin(admin, localDatetime(-60), localDatetime(120), 'PW test in progress delete');
            const data = await post(pilot, '/reservations/delete', { reservation_id: String(created.id) });
            expect(data.success).toBe(true);
            created = null;
        } finally {
            await pilot.page.close();
            await deleteAsAdmin(admin, created && created.id);
        }
    });

    /**
     * @feature Réservations
     * Scénario: Une réservation terminée est verrouillée pour le pilote
     *   Étant donné une réservation terminée d'un pilote sans droit de gestion des réservations
     *   Quand ce pilote tente de la supprimer
     *   Alors la suppression est refusée avec un message sur les réservations passées
     */
    test('une réservation terminée ne peut pas être supprimée par le pilote', async ({ browser }) => {
        test.skip(!admin.aircraftIds.length, 'Aucun aéronef trouvé en section Avion');
        const pastDate = isoDate(-420);
        let created = null;
        const pilot = await openSession(browser, PILOT);
        try {
            created = await createAsAdmin(admin, pastDate + ' 14:00:00', pastDate + ' 15:00:00', 'PW test finished locked');
            const data = await post(pilot, '/reservations/delete', { reservation_id: String(created.id) });
            expect(data.success).toBe(false);
            expect(data.error).toMatch(/pass[ée]e?s?/i);
        } finally {
            await pilot.page.close();
            await deleteAsAdmin(admin, created && created.id);
        }
    });

    /**
     * @feature Réservations
     * Scénario: Un instructeur peut supprimer une réservation terminée
     *   Étant donné une réservation terminée d'un pilote
     *   Quand un instructeur la supprime
     *   Alors la suppression est acceptée
     */
    test('un instructeur peut supprimer une réservation terminée', async ({ browser }) => {
        test.skip(!admin.aircraftIds.length, 'Aucun aéronef trouvé en section Avion');
        const pastDate = isoDate(-430);
        let created = null;
        const instructor = await openSession(browser, INSTRUCTOR);
        try {
            created = await createAsAdmin(admin, pastDate + ' 14:00:00', pastDate + ' 15:00:00', 'PW test instructor finished');
            const data = await post(instructor, '/reservations/delete', { reservation_id: String(created.id) });
            expect(data.success).toBe(true);
            created = null;
        } finally {
            await instructor.page.close();
            await deleteAsAdmin(admin, created && created.id);
        }
    });
});
