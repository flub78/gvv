/**
 * Régression : cliquer sur un jour passé dans la vue mensuelle des
 * réservations pour créer une réservation était silencieusement ignoré
 * (aucun message, la fenêtre de création ne s'ouvre pas et rien n'indique
 * pourquoi). Conformément à la règle GUI du projet ("Never reject an action
 * silently"), un message d'erreur doit être affiché.
 *
 * Utilisateur de test : abraracourcix (instructeur, section Avion=3, non
 * club-admin — les club-admins peuvent créer des réservations dans le passé).
 *
 * Usage :
 *   cd playwright && npx playwright test tests/reservations-past-click-feedback.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const AVION_SECTION = '3';
const MONTH_VIEW_URL = '/index.php/reservations?view=dayGridMonth';

test.describe('Réservations - vue mensuelle - retour utilisateur sur clic passé', () => {

    test.beforeEach(async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login('abraracourcix', 'password', AVION_SECTION);
        await loginPage.verifyLoggedIn();
    });

    test('un non-admin voit un message d\'erreur en cliquant sur un jour passé', async ({ page }) => {
        await page.goto(MONTH_VIEW_URL);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.fc-daygrid-day', { timeout: 10000 });

        // Go to the previous month so every visible day cell is in the past,
        // regardless of what "today" is.
        await page.locator('.fc-prev-button').click();
        await page.waitForTimeout(300);

        let dialogMessage = null;
        page.once('dialog', async (dialog) => {
            dialogMessage = dialog.message();
            await dialog.accept();
        });

        const pastCell = page.locator('.fc-daygrid-day').first();
        await pastCell.locator('.fc-daygrid-day-top').click();

        await page.waitForTimeout(500);

        expect(dialogMessage).not.toBeNull();
        expect(dialogMessage).toMatch(/pass[ée]e?s?/i);

        // The create modal must not open
        await expect(page.locator('#eventModal.show')).not.toBeVisible();

        console.log('✓ Message affiché pour clic sur jour passé :', dialogMessage);
    });
});
