/**
 * Régression : blocage réservation ULM quand le solde est insuffisant
 *
 * Utilisateur de test : assurancetourix (solde 76,60 € en section ULM)
 * Créé par create_test_users.sh / admin._create_test_gaulois_users()
 *
 * Bugs corrigés dans _check_pilot_balance() :
 *   1. membres.compte = 0 → empty(0) = true → check ignoré
 *   2. condition (is_auto_planchiste || is_proprio) excluait les membres ordinaires
 *   3. Seules les réservations sur l'appareil en cours étaient comptées →
 *      deux réservations courtes sur deux appareils différents passaient chacune
 *      le check individuellement alors que leur coût cumulé dépasse le solde.
 *
 * Usage :
 *   cd playwright && npx playwright test tests/reservations-balance-block.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const ULM_SECTION = '2';
const TIMELINE_URL = '/index.php/reservations/timeline';

const AIRCRAFT_A = 'F-JTVA';  // heure_nynja 108€/h
const AIRCRAFT_B = 'F-JHRV';  // heure_ctl  126€/h
// 0.5h A = 54€, 0.5h B = 63€, total = 117€ > solde 76,60€

// ─── Helpers ────────────────────────────────────────────────────────────────

async function getBaseUrl(browser) {
    const page = await browser.newPage();
    const lp = new LoginPage(page);
    await lp.open();
    await lp.login('assurancetourix', 'password', ULM_SECTION);
    await lp.verifyLoggedIn();
    await page.goto(TIMELINE_URL);
    await page.waitForLoadState('networkidle');
    const url = await page.evaluate(() => CONFIG.baseUrl);
    await page.close();
    return url;
}

async function deleteReservation(browser, baseUrl, id) {
    const page = await browser.newPage();
    const lp = new LoginPage(page);
    await lp.open();
    await lp.login('panoramix', 'password', ULM_SECTION);
    await lp.verifyLoggedIn();
    await page.goto(TIMELINE_URL);
    await page.waitForLoadState('networkidle');
    await page.request.post(baseUrl + '/reservations/delete', {
        form: { reservation_id: String(id) }
    });
    await page.close();
}

// ─── Scénario 1 : réservation 1h sur un seul appareil ───────────────────────

test.describe.serial('Blocage solde insuffisant — réservation 1h', () => {

    let baseUrl;
    let createdId = null;

    test.beforeAll(async ({ browser }) => { baseUrl = await getBaseUrl(browser); });
    test.afterAll(async ({ browser }) => {
        if (createdId) await deleteReservation(browser, baseUrl, createdId);
    });

    test('la timeline ULM est accessible', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('assurancetourix', 'password', ULM_SECTION);
        await lp.verifyLoggedIn();
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.timeline-container')).toBeVisible();
        console.log('✓ assurancetourix peut accéder à la timeline ULM');
    });

    test('1h sur F-JTVA (108€) doit être refusée — solde 76,60€', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('assurancetourix', 'password', ULM_SECTION);
        await lp.verifyLoggedIn();

        const future = new Date();
        future.setDate(future.getDate() + 14);
        const dateStr = future.toISOString().slice(0, 10);

        const resp = await page.request.post(baseUrl + '/reservations/update_reservation', {
            form: {
                reservation_id: '',
                aircraft_id: AIRCRAFT_A,
                pilot_member_id: 'assurancetourix',
                start_datetime: dateStr + ' 10:00:00',
                end_datetime: dateStr + ' 11:00:00',
                instructor_member_id: '',
                notes: 'PW test solde 1h',
                status: 'vol_local',
            }
        });

        const data = JSON.parse(await resp.text());
        if (data.success && data.reservation_id) createdId = data.reservation_id;
        expect(data.success).toBe(false);
        expect(data.error).toMatch(/[Ss]olde|[Ii]nsuffisant|[Bb]alance/);
        console.log('✓ 1h refusée. Message :', data.error);
    });
});

// ─── Scénario 2 : deux réservations 0h30 sur deux appareils différents ───────
//
// Bug corrigé : l'ancienne logique mono-appareil ne comptait que les
// réservations sur l'appareil en cours. Résultat : deux réservations
// courtes sur deux appareils différents passaient chacune le check
// (54€ < 76,60€ et 63€ < 76,60€) alors que le coût cumulé (117€) dépasse
// largement le solde. La nouvelle logique somme les coûts sur tous les
// appareils.

test.describe.serial('Blocage multi-appareils — deux réservations 0h30', () => {

    let baseUrl;
    let firstId = null;

    test.beforeAll(async ({ browser }) => { baseUrl = await getBaseUrl(browser); });
    test.afterAll(async ({ browser }) => {
        if (firstId) await deleteReservation(browser, baseUrl, firstId);
    });

    test('0h30 sur F-JTVA (54€) doit être acceptée — solde 76,60€', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('assurancetourix', 'password', ULM_SECTION);
        await lp.verifyLoggedIn();

        const future = new Date();
        future.setDate(future.getDate() + 14);
        const dateStr = future.toISOString().slice(0, 10);

        const resp = await page.request.post(baseUrl + '/reservations/update_reservation', {
            form: {
                reservation_id: '',
                aircraft_id: AIRCRAFT_A,
                pilot_member_id: 'assurancetourix',
                start_datetime: dateStr + ' 10:00:00',
                end_datetime: dateStr + ' 10:30:00',
                instructor_member_id: '',
                notes: 'PW test multi-appareil 1re',
                status: 'vol_local',
            }
        });

        const data = JSON.parse(await resp.text());
        if (data.success && data.reservation_id) firstId = data.reservation_id;
        expect(data.success).toBe(true);
        console.log('✓ 1re réservation (0h30 F-JTVA) acceptée. id =', firstId);
    });

    test('0h30 sur F-JHRV doit être refusée — coût cumulé 117€ > 76,60€', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('assurancetourix', 'password', ULM_SECTION);
        await lp.verifyLoggedIn();

        const future = new Date();
        future.setDate(future.getDate() + 14);
        const dateStr = future.toISOString().slice(0, 10);

        const resp = await page.request.post(baseUrl + '/reservations/update_reservation', {
            form: {
                reservation_id: '',
                aircraft_id: AIRCRAFT_B,
                pilot_member_id: 'assurancetourix',
                start_datetime: dateStr + ' 11:00:00',
                end_datetime: dateStr + ' 11:30:00',
                instructor_member_id: '',
                notes: 'PW test multi-appareil 2e',
                status: 'vol_local',
            }
        });

        const data = JSON.parse(await resp.text());
        if (data.success && data.reservation_id) {
            // Nettoyage si le test échoue (le check n'a pas bloqué)
            await deleteReservation(page.context().browser(), baseUrl, data.reservation_id);
        }
        expect(data.success).toBe(false);
        expect(data.error).toMatch(/[Ss]olde|[Ii]nsuffisant|[Bb]alance/);
        console.log('✓ 2e réservation (0h30 F-JHRV) refusée. Message :', data.error);
    });
});
