/**
 * Synchronisation des réservations avec Google Agenda — liaison du compte.
 *
 * Google est remplacé par le bouchon agenda_google_bouchon, activé le temps
 * des tests dans program.php (valeurs d'origine restaurées à la fin).
 *
 * Utilisateurs de test :
 *  - goudurix : auto_planchiste (section Avion), peut réserver
 *  - asterix  : simple membre, ne peut pas réserver
 *
 * Usage :
 *   cd playwright && npx playwright test tests/agenda-google-sync.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const LoginPage = require('./helpers/LoginPage');
const { DB_CONFIG } = require('./helpers/gvv-config');
const { applyProgramConfig, enableAgendaGoogleWithBouchon, waitForBouchon } = require('./helpers/agendaGoogleConfig');

const PILOT = 'goudurix';
const NON_BOOKER = 'asterix';
const PAGE_URL = '/index.php/mes_reservations';
const SECTIONS = { goudurix: '3', asterix: '1' };

// Les tests modifient program.php et la liaison du même membre : en série.
test.describe.configure({ mode: 'serial' });

test.describe('Agenda Google - bouchon', () => {
    /**
     * @feature Synchronisation agenda Google
     * Scénario: Le Google simulé est inaccessible sur un serveur qui ne le demande pas
     *   Étant donné un serveur dont la configuration ne demande pas le Google simulé
     *   Quand on appelle l'écran d'autorisation simulé
     *   Alors la page n'existe pas
     */
    test('le bouchon répond 404 sans le paramètre', async ({ request }) => {
        const restore = applyProgramConfig({ agenda_google_bouchon: false });
        try {
            await waitForBouchon(request, false);
            const resp = await request.get('/index.php/agenda_google_bouchon/token');
            expect(resp.status()).toBe(404);
        } finally {
            restore();
        }
    });
});

test.describe('Agenda Google - liaison du compte', () => {
    let conn;
    let restoreConfig;
    let savedLiaison;
    const account = `pw.agenda.${Date.now()}@example.org`;

    test.beforeAll(async ({ request }) => {
        conn = await mysql.createConnection(DB_CONFIG);
        // Liaison existante du membre de test, restaurée à la fin
        const [rows] = await conn.execute('SELECT * FROM agenda_google_liaisons WHERE mlogin = ?', [PILOT]);
        savedLiaison = rows[0] || null;
        await conn.execute('DELETE FROM agenda_google_liaisons WHERE mlogin = ?', [PILOT]);
        restoreConfig = enableAgendaGoogleWithBouchon();
        await waitForBouchon(request, true);
    });

    test.afterAll(async ({ request }) => {
        try {
            if (restoreConfig) restoreConfig();
            await conn.execute('DELETE FROM agenda_google_liaisons WHERE mlogin = ?', [PILOT]);
            if (savedLiaison) {
                const cols = Object.keys(savedLiaison);
                await conn.execute(
                    `INSERT INTO agenda_google_liaisons (${cols.join(',')}) VALUES (${cols.map(() => '?').join(',')})`,
                    cols.map(c => savedLiaison[c])
                );
            }
            await conn.execute('DELETE FROM agenda_google_bouchon WHERE compte = ?', [account]);
            await waitForBouchon(request, false).catch(() => {});
        } finally {
            await conn.end();
        }
    });

    async function login(page, username) {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(username, 'password', SECTIONS[username]);
        await lp.verifyLoggedIn();
        await page.goto(PAGE_URL);
    }

    /**
     * @feature Synchronisation agenda Google
     * Scénario: Un membre relie son agenda Google puis arrête la synchronisation
     *   Étant donné un membre qui peut réserver et dont la synchronisation est inactive
     *   Quand il relie son agenda et accepte l'accès sur l'écran de Google
     *   Alors la synchronisation est active avec son compte Google
     *   Quand il demande l'arrêt
     *   Alors il est prévenu que les événements resteront dans son agenda sans mise à jour
     *   Et après confirmation la synchronisation est inactive
     *   Et GVV ne conserve plus son autorisation, retirée chez Google
     */
    test('relier puis arrêter la synchronisation', async ({ page }) => {
        await login(page, PILOT);
        const block = page.locator('#agenda-google');
        await expect(block).toBeVisible();
        await expect(page.locator('#agenda-google-etat')).toContainText(/inactive/i);

        await page.click('#agenda-google-activer');
        await expect(page).toHaveURL(/agenda_google_bouchon\/autoriser/);
        await page.fill('#compte', account);
        await page.click('#bouchon-autoriser');

        await expect(page).toHaveURL(/mes_reservations/);
        await expect(page.locator('.alert-success')).toContainText(account);
        await expect(page.locator('#agenda-google-etat')).toContainText(/active/i);
        await expect(page.locator('#agenda-google-compte')).toHaveText(account);

        await page.click('#agenda-google-arreter');
        const modal = page.locator('#agenda-google-arret-modal');
        await expect(modal).toBeVisible();
        await expect(modal).toContainText(/resteront/);
        await page.click('#agenda-google-arret-confirmer');

        await expect(page.locator('.alert-success')).toContainText(/arrêtée/);
        await expect(page.locator('#agenda-google-etat')).toContainText(/inactive/i);

        const [liaisons] = await conn.execute('SELECT etat, jeton_renouvellement FROM agenda_google_liaisons WHERE mlogin = ?', [PILOT]);
        expect(liaisons[0].etat).toBe('arretee');
        expect(liaisons[0].jeton_renouvellement).toBeNull();
        const [tokens] = await conn.execute("SELECT revoque FROM agenda_google_bouchon WHERE type = 'jeton' AND compte = ?", [account]);
        expect(tokens.length).toBe(1);
        expect(Number(tokens[0].revoque)).toBe(1);
    });

    /**
     * @feature Synchronisation agenda Google
     * Scénario: Le membre refuse l'accès sur l'écran de Google
     *   Étant donné un membre qui peut réserver
     *   Quand il refuse l'accès sur l'écran de Google
     *   Alors il revient sur ses réservations avec un message expliquant l'échec et l'action à faire
     *   Et la synchronisation reste inactive
     */
    test('un refus sur l\'écran de Google est expliqué', async ({ page }) => {
        await login(page, PILOT);
        await page.click('#agenda-google-activer');
        await page.click('#bouchon-refuser');

        await expect(page).toHaveURL(/mes_reservations/);
        await expect(page.locator('.alert-danger')).toContainText(/non activée/);
        await expect(page.locator('#agenda-google-etat')).toContainText(/inactive/i);
    });

    /**
     * @feature Synchronisation agenda Google
     * Scénario: Un membre qui ne peut pas réserver ne voit pas la synchronisation
     *   Étant donné un membre sans droit de réserver
     *   Quand il consulte ses réservations
     *   Alors la synchronisation avec Google Agenda ne lui est pas proposée
     */
    test('un membre sans droit de réserver ne voit pas le bloc', async ({ page }) => {
        await login(page, NON_BOOKER);
        await expect(page.locator('body')).not.toContainText('Une erreur');
        await expect(page.locator('#agenda-google')).toHaveCount(0);
    });

    /**
     * @feature Synchronisation agenda Google
     * Scénario: La synchronisation est invisible quand le club ne l'a pas activée
     *   Étant donné un club qui n'a pas activé la synchronisation avec Google Agenda
     *   Et un membre qui peut réserver
     *   Quand il consulte ses réservations
     *   Alors la synchronisation ne lui est pas proposée
     */
    test('fonction inactive pour le club : bloc absent', async ({ page }) => {
        const restore = enableAgendaGoogleWithBouchon({ agenda_google_sync: false });
        try {
            await page.waitForTimeout(2500);   // relecture de program.php par le serveur
            await login(page, PILOT);
            await expect(page.locator('h5', { hasText: /réservations/i }).first()).toBeVisible();
            await expect(page.locator('#agenda-google')).toHaveCount(0);
        } finally {
            restore();
        }
    });
});

test.describe('Agenda Google - mise à jour des événements', () => {
    const ADMIN = 'panoramix';
    const AVION_SECTION = '3';
    const SYNC_SECRET = `pw-secret-${Date.now()}`;
    const account = `pw.agenda.evt.${Date.now()}@example.org`;
    let conn;
    let restoreConfig;
    let savedLiaison;
    let reservationId = null;
    const createdReservations = [];

    test.beforeAll(async ({ request }) => {
        conn = await mysql.createConnection(DB_CONFIG);
        const [rows] = await conn.execute('SELECT * FROM agenda_google_liaisons WHERE mlogin = ?', [PILOT]);
        savedLiaison = rows[0] || null;
        await conn.execute('DELETE FROM agenda_google_liaisons WHERE mlogin = ?', [PILOT]);
        restoreConfig = enableAgendaGoogleWithBouchon({ agenda_google_sync_secret: SYNC_SECRET });
        await waitForBouchon(request, true);
    });

    test.afterAll(async ({ request }) => {
        try {
            if (restoreConfig) restoreConfig();
            for (const id of createdReservations) {
                await conn.execute('DELETE FROM reservations WHERE id = ?', [id]);
                await conn.execute('DELETE FROM agenda_google_file WHERE reservation_id = ?', [id]);
                await conn.execute('DELETE FROM reservation_reminder_log WHERE reservation_id = ?', [id]);
            }
            await conn.execute('DELETE FROM agenda_google_liaisons WHERE mlogin = ?', [PILOT]);
            if (savedLiaison) {
                const cols = Object.keys(savedLiaison);
                await conn.execute(
                    `INSERT INTO agenda_google_liaisons (${cols.join(',')}) VALUES (${cols.map(() => '?').join(',')})`,
                    cols.map(c => savedLiaison[c])
                );
            }
            await conn.execute('DELETE FROM agenda_google_bouchon WHERE compte = ?', [account]);
            await waitForBouchon(request, false).catch(() => {});
        } finally {
            await conn.end();
        }
    });

    function isoDate(offsetDays) {
        const d = new Date();
        d.setDate(d.getDate() + offsetDays);
        return d.toISOString().slice(0, 10);
    }

    async function session(browser, username) {
        const page = await browser.newPage();
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login(username, 'password', AVION_SECTION);
        await lp.verifyLoggedIn();
        await page.goto('/index.php/reservations/timeline');
        await page.waitForLoadState('networkidle');
        const ctx = await page.evaluate(() => ({
            baseUrl: CONFIG.baseUrl,
            aircraftIds: Object.keys((typeof OPTIONS !== 'undefined' && OPTIONS.aircraft) || {}),
        }));
        return { page, ...ctx };
    }

    async function post(s, path, form) {
        const resp = await s.page.request.post(s.baseUrl + path, { form });
        return JSON.parse(await resp.text());
    }

    /** Passage du traitement de la file, comme la cron */
    async function runSync(request) {
        const resp = await request.get(`/index.php/agenda_google_traitement/run/${SYNC_SECRET}`);
        expect(resp.status()).toBe(200);
        return resp.json();
    }

    /** Événements présents dans l'agenda simulé du compte */
    async function agenda() {
        const [rows] = await conn.execute(
            "SELECT cle, contenu FROM agenda_google_bouchon WHERE type = 'evenement' AND compte = ? AND revoque = 0", [account]);
        return rows.map(r => ({ id: r.cle, ...JSON.parse(r.contenu) }));
    }

    async function linkAgenda(page) {
        await page.goto(PAGE_URL);
        await page.click('#agenda-google-activer');
        await page.fill('#compte', account);
        await page.click('#bouchon-autoriser');
        await expect(page.locator('#agenda-google-etat')).toContainText(/active/i);
    }

    /**
     * @feature Synchronisation agenda Google
     * Scénario: Une réservation suit le membre dans son agenda, quelle que soit la personne qui la modifie
     *   Étant donné un pilote qui a relié son agenda Google
     *   Quand un administrateur lui crée une réservation et que la synchronisation passe
     *   Alors la réservation apparaît dans l'agenda du pilote
     *   Quand l'administrateur la déplace dans le planning et que la synchronisation passe
     *   Alors l'événement est mis à jour avec les nouveaux horaires, sans doublon
     *   Quand le pilote la supprime et que la synchronisation passe
     *   Alors l'événement disparaît de son agenda
     */
    test('création, déplacement par un administrateur, suppression par le membre', async ({ browser, request }) => {
        const pilot = await session(browser, PILOT);
        const admin = await session(browser, ADMIN);
        try {
            test.skip(!admin.aircraftIds.length, 'Aucun aéronef trouvé en section Avion');
            await linkAgenda(pilot.page);

            const day = isoDate(450);
            let aircraftId = null;
            for (const candidate of admin.aircraftIds) {
                const data = await post(admin, '/reservations/update_reservation', {
                    reservation_id: '', aircraft_id: candidate, pilot_member_id: PILOT,
                    start_datetime: `${day} 09:00:00`, end_datetime: `${day} 10:00:00`,
                    instructor_member_id: '', notes: 'PW agenda Google', status: 'vol_local',
                });
                if (data.success) { reservationId = data.reservation_id; createdReservations.push(reservationId); aircraftId = candidate; break; }
            }
            expect(reservationId, 'réservation de test créée').toBeTruthy();

            await runSync(request);
            let events = await agenda();
            expect(events.length).toBe(1);
            expect(events[0].summary).toContain(aircraftId);
            expect(events[0].start.dateTime).toBe(`${day}T09:00:00`);
            expect(events[0].description).toContain('PW agenda Google');

            const moved = await post(admin, '/reservations/on_event_drop', {
                event_id: String(reservationId), start_datetime: `${day} 14:00:00`, end_datetime: `${day} 15:00:00`,
                resource_id: aircraftId, action: 'move',
            });
            expect(moved.success, moved.error).toBe(true);
            await runSync(request);
            events = await agenda();
            expect(events.length).toBe(1);
            expect(events[0].start.dateTime).toBe(`${day}T14:00:00`);

            const deleted = await post(pilot, '/reservations/delete', { reservation_id: String(reservationId) });
            expect(deleted.success, deleted.error).toBe(true);
            await runSync(request);
            expect((await agenda()).length).toBe(0);
        } finally {
            await pilot.page.close();
            await admin.page.close();
        }
    });

    /**
     * @feature Synchronisation agenda Google
     * Scénario: Une autorisation retirée dans Google suspend la synchronisation
     *   Étant donné un pilote dont la synchronisation est active
     *   Et qui a retiré l'accès de GVV dans son compte Google
     *   Quand une de ses réservations change et que la synchronisation passe
     *   Alors « Mes réservations » indique que la synchronisation est suspendue
     *   Et lui propose de relier à nouveau son agenda
     *   Et l'administrateur du club le voit dans la liste des synchronisations suspendues, sans son autorisation
     */
    test('une autorisation retirée suspend la synchronisation', async ({ browser, request }) => {
        const pilot = await session(browser, PILOT);
        const admin = await session(browser, ADMIN);
        try {
            test.skip(!admin.aircraftIds.length, 'Aucun aéronef trouvé en section Avion');
            await pilot.page.goto(PAGE_URL);
            if (!(await pilot.page.locator('#agenda-google-etat').textContent()).match(/Synchronisation active/)) {
                await linkAgenda(pilot.page);
            }
            // Le membre retire l'accès de GVV dans son compte Google
            await conn.execute("UPDATE agenda_google_bouchon SET revoque = 1 WHERE type = 'jeton' AND compte = ?", [account]);

            const day = isoDate(455);
            reservationId = null;
            for (const candidate of admin.aircraftIds) {
                const data = await post(admin, '/reservations/update_reservation', {
                    reservation_id: '', aircraft_id: candidate, pilot_member_id: PILOT,
                    start_datetime: `${day} 09:00:00`, end_datetime: `${day} 10:00:00`,
                    instructor_member_id: '', notes: 'PW agenda Google suspension', status: 'vol_local',
                });
                if (data.success) { reservationId = data.reservation_id; createdReservations.push(reservationId); break; }
            }
            expect(reservationId, 'réservation de test créée').toBeTruthy();

            const stats = await runSync(request);
            expect(stats.suspendues).toBeGreaterThanOrEqual(1);

            await pilot.page.goto(PAGE_URL);
            await expect(pilot.page.locator('#agenda-google-etat')).toContainText(/suspendue/i);
            await expect(pilot.page.locator('#agenda-google .alert-warning')).toContainText(/Reliez à nouveau/);
            await expect(pilot.page.locator('#agenda-google-activer')).toContainText(/Relier à nouveau/);

            // L'administrateur du club voit le membre dans la liste des suspensions
            await admin.page.goto('/index.php/agenda_google_liaisons');
            await expect(admin.page.locator('h3')).toContainText(/suspendues/);
            await expect(admin.page.locator('table')).toContainText(PILOT);
            await expect(admin.page.locator('body')).not.toContainText(/jeton|renouvellement/i);
        } finally {
            await pilot.page.close();
            await admin.page.close();
        }
    });
});
