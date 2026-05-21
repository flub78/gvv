/**
 * Régression : les pilote_vd doivent pouvoir réserver quelque soit leur solde
 *
 * Utilisateur de test : agecanonix (pilote_vd, section Planeur=1)
 *   - compte 411 avec solde 0 €
 *   - F-JUFA : tarif "Heure de vol Dynamic" = 100 €/h
 *   - 1h de réservation = 100 € > 0 € → bloquerait un membre ordinaire
 *   - doit être autorisé pour un pilote_vd (balance_exempt = true)
 *
 * Usage :
 *   cd playwright && npx playwright test tests/reservations-pilote-vd-balance.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const PLANEUR_SECTION = '1';
const AVION_SECTION   = '3';   // panoramix est en section Avion
const TIMELINE_URL    = '/index.php/reservations/timeline';
const AIRCRAFT_ID     = 'F-JUFA';   // Heure de vol Dynamic = 100 €/h, section Planeur

// ─── Helper : récupère baseUrl ────────────────────────────────────────────────

async function getBaseUrl(browser, username, section) {
    const page = await browser.newPage();
    const lp = new LoginPage(page);
    await lp.open();
    await lp.login(username, 'password', section);
    await lp.verifyLoggedIn();
    await page.goto(TIMELINE_URL);
    await page.waitForLoadState('networkidle');
    const url = await page.evaluate(() => CONFIG.baseUrl);
    await page.close();
    return url;
}

// ─── Helper : supprime une réservation via panoramix (admin) ──────────────────

async function deleteReservation(browser, id) {
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
            form: { reservation_id: String(id) }
        });
        console.log('Deleted test reservation id:', id);
    } finally {
        await page.close();
    }
}

// ─── Scénario : pilote_vd peut réserver malgré un solde insuffisant ───────────

test.describe.serial('pilote_vd (agecanonix) — exemption du contrôle de solde', () => {

    let baseUrl;
    let createdId = null;

    test.beforeAll(async ({ browser }) => {
        baseUrl = await getBaseUrl(browser, 'agecanonix', PLANEUR_SECTION);
    });

    test.afterAll(async ({ browser }) => {
        if (createdId) await deleteReservation(browser, createdId);
    });

    test('agecanonix peut accéder à la timeline Planeur', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('agecanonix', 'password', PLANEUR_SECTION);
        await lp.verifyLoggedIn();
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.timeline-container')).toBeVisible();
        console.log('✓ agecanonix peut accéder à la timeline Planeur');
    });

    test('1h sur F-JUFA (100€) doit être acceptée malgré un solde 0€ — pilote_vd', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('agecanonix', 'password', PLANEUR_SECTION);
        await lp.verifyLoggedIn();

        // Date dans 14 jours pour éviter les conflits
        const future = new Date();
        future.setDate(future.getDate() + 14);
        const dateStr = future.toISOString().slice(0, 10);

        const resp = await page.request.post(baseUrl + '/reservations/update_reservation', {
            form: {
                reservation_id: '',
                aircraft_id: AIRCRAFT_ID,
                pilot_member_id: 'agecanonix',
                start_datetime: dateStr + ' 09:00:00',
                end_datetime:   dateStr + ' 10:00:00',
                instructor_member_id: '',
                notes: 'PW test pilote_vd balance exempt',
                status: 'reservation',
            }
        });

        const data = JSON.parse(await resp.text());
        if (data.success && data.reservation_id) createdId = data.reservation_id;

        // Un membre ordinaire avec 0€ serait bloqué (coût 100€ > 0€).
        // Un pilote_vd doit être autorisé (balance_exempt = true).
        expect(data.success).toBe(true);
        expect(data.reservation_id).toBeGreaterThan(0);
        console.log('✓ Réservation acceptée malgré solde 0€. id =', createdId);
    });
});
