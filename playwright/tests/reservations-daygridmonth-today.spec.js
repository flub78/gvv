/**
 * Régression : impossible de créer une réservation pour le jour même
 * dans la vue mensuelle (dayGridMonth) des réservations.
 *
 * Cause : les clics dans dayGridMonth remontent des dates "allDay" (minuit,
 * ou une heure par défaut fixe comme 09:00). Le code JS comparait cette
 * date/heure par défaut à `new Date()` pour décider si le jour est passé,
 * ce qui bloque systématiquement le jour courant dès que l'heure par défaut
 * est déjà dépassée (ou toujours, pour une comparaison à minuit).
 *
 * La vue timeline n'est pas affectée car elle utilise l'heure réellement
 * cliquée sur la grille, pas une heure par défaut.
 *
 * Utilisateur de test : abraracourcix (instructeur, section Avion=3, non club-admin)
 *
 * Usage :
 *   cd playwright && npx playwright test tests/reservations-daygridmonth-today.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const AVION_SECTION = '3';
const MONTH_VIEW_URL = '/index.php/reservations?view=dayGridMonth';

test.describe('Réservations - vue mensuelle - création le jour même', () => {

    test.beforeEach(async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login('abraracourcix', 'password', AVION_SECTION);
        await loginPage.verifyLoggedIn();
    });

    test('un non-admin peut ouvrir le formulaire de création sur le jour courant', async ({ page }) => {
        await page.goto(MONTH_VIEW_URL);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.fc-daygrid-day.fc-day-today', { timeout: 10000 });

        // Click on today's cell, on the day-number area to avoid hitting an
        // existing event.
        const todayCell = page.locator('.fc-daygrid-day.fc-day-today');
        await todayCell.locator('.fc-daygrid-day-top').click();

        await expect(page.locator('#eventModal.show')).toBeVisible({ timeout: 3000 });

        const modalTitle = page.locator('#eventModalTitle');
        await expect(modalTitle).toContainText('Nouvelle');

        // Save button must be present: a non-admin is allowed to book today
        await expect(page.locator('#saveEventBtn')).toBeVisible();

        console.log('✓ Modal de création ouverte pour le jour même en vue mensuelle');
    });
});
