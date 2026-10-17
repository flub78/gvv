/**
 * Playwright Tests – Niveau d'entraînement
 *
 * Smoke tests for the training level report (solo hours over the last
 * 12 months, ascending, warning under 10 hours).
 * Verifies:
 *   1. The card is present in the Formation dashboard section and leads to the page
 *   2. Pilots are sorted by ascending total hours
 *   3. Rows under the threshold carry the warning style
 *
 * Prerequisites:
 *   - abraracourcix user must exist with instructor rights
 *   - Feature flag gestion_formations must be enabled
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const INSTRUCTOR_USER = { username: 'abraracourcix', password: 'password' };
const PAGE_URL = '/index.php/formation_rapports/niveau_entrainement';

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

/** Convertit "h:mm" en minutes */
function toMinutes(text) {
    const [h, m] = text.trim().split(':').map(Number);
    return h * 60 + m;
}

test.describe("Formation – Niveau d'entraînement", () => {

    test('La carte du tableau de bord mène à la page', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);
        await page.goto('/index.php/welcome/section/formation');

        const link = page.locator('a[href*="formation_rapports/niveau_entrainement"]');
        await expect(link).toHaveCount(1);
        await link.click();
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/formation_rapports\/niveau_entrainement/);
        await expect(page.locator('h3')).toContainText(/entra.nement/i);
        expect(await page.textContent('body')).not.toMatch(/PHP Error|Fatal error|A Database Error/);
        await expect(page.locator('body')).toContainText(/autres clubs/);
        await expect(page.locator('table thead')).toContainText('Heures en DC');
    });

    test('Les pilotes sont triés par heures croissantes avec alerte sous 10 h', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);
        await page.goto(PAGE_URL);

        const rows = page.locator('table tbody tr');
        const count = await rows.count();
        test.skip(count === 0, 'Aucun pilote solo dans la base');

        let previous = -1;
        for (let i = 0; i < count; i++) {
            const row = rows.nth(i);
            const total = toMinutes(await row.locator('td').nth(1).innerText());
            expect(total).toBeGreaterThanOrEqual(previous);
            previous = total;

            const warned = (await row.getAttribute('class') || '').includes('table-warning');
            expect(warned).toBe(total < 10 * 60);
        }
    });

});
