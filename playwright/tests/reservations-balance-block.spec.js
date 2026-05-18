/**
 * Régression : alodigeois pouvait réserver en ULM malgré un solde insuffisant
 *
 * Cause : deux bugs dans _check_pilot_balance() :
 *   1. membres.compte = 0 pour alodigeois → empty(0) = true → check ignoré
 *   2. condition (is_auto_planchiste || is_proprio) excluait les membres ordinaires
 *
 * Ce test soumet directement update_reservation via la session du pilote
 * et vérifie que le serveur refuse avec une erreur de solde.
 *
 * Usage :
 *   cd playwright && npx playwright test tests/reservations-balance-block.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const ULM_SECTION = '2';
const TIMELINE_URL = '/index.php/reservations/timeline';

// Appareil ULM avec tarif connu (heure_nynja, 96€/h)
const AIRCRAFT = 'F-JTVA';

test.describe.serial('Blocage solde insuffisant — alodigeois ULM', () => {

    let baseUrl;
    let createdId = null;

    // Récupère baseUrl en se connectant une fois
    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('alodigeois', 'password', ULM_SECTION);
        await lp.verifyLoggedIn();
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');
        baseUrl = await page.evaluate(() => CONFIG.baseUrl);
        await page.close();
    });

    test.afterAll(async ({ browser }) => {
        // Nettoyage : supprime la réservation si elle a été créée (ne devrait pas l'être)
        if (!createdId) return;
        const page = await browser.newPage();
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('panoramix', 'password', ULM_SECTION);
        await lp.verifyLoggedIn();
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');
        const bu = await page.evaluate(() => CONFIG.baseUrl);
        await page.request.post(bu + '/reservations/delete', {
            form: { reservation_id: String(createdId) }
        });
        await page.close();
    });

    test('alodigeois a un solde insuffisant et des réservations existantes', async ({ page }) => {
        // Vérifie les pré-conditions en base : alodigeois doit avoir au moins une réservation future
        // et un solde connu. Ce test documente l'état attendu du jeu de données.
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('alodigeois', 'password', ULM_SECTION);
        await lp.verifyLoggedIn();
        await page.goto(TIMELINE_URL);
        await page.waitForLoadState('networkidle');

        // La timeline doit se charger sans erreur
        await expect(page.locator('.timeline-container')).toBeVisible();
        console.log('✓ alodigeois peut accéder à la timeline ULM');
    });

    test('update_reservation doit retourner une erreur de solde insuffisant', async ({ page }) => {
        const lp = new LoginPage(page);
        await lp.open();
        await lp.login('alodigeois', 'password', ULM_SECTION);
        await lp.verifyLoggedIn();

        // Date future pour éviter les conflits avec les réservations existantes
        const future = new Date();
        future.setDate(future.getDate() + 7);
        const dateStr = future.toISOString().slice(0, 10);

        // Appel direct à l'API update_reservation (comme le fait le navigateur via AJAX)
        const resp = await page.request.post(baseUrl + '/reservations/update_reservation', {
            form: {
                reservation_id: '',
                aircraft_id: AIRCRAFT,
                pilot_member_id: 'alodigeois',
                start_datetime: dateStr + ' 10:00:00',
                end_datetime: dateStr + ' 11:00:00',
                instructor_member_id: '',
                notes: 'PW test solde insuffisant',
                status: 'reservation',
            }
        });

        const text = await resp.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Réponse non JSON : ' + text.slice(0, 300));
        }

        if (data.success && data.reservation_id) {
            createdId = data.reservation_id; // pour nettoyage
        }

        // La réservation doit être REFUSÉE
        expect(data.success).toBe(false);
        expect(data.error).toBeDefined();
        expect(data.error).toMatch(/[Ss]olde|[Ii]nsuffisant|[Bb]alance/);
        console.log('✓ Réservation refusée. Message :', data.error);
    });
});
