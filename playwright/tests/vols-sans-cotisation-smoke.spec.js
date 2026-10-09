/**
 * Smoke test : contrôle des pilotes ayant volé sans cotisation
 *  - la carte est présente dans la section "Contrôles et vérifications" du dashboard admin club ;
 *  - la page affiche la liste ou le message "aucun pilote" pour l'année courante ;
 *  - le sélecteur d'année recharge la page sur l'année choisie ;
 *  - une année sans vols affiche le message "aucun pilote".
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/vols-sans-cotisation-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const ADMIN_USER = { username: 'testadmin', password: 'password' };

async function login(page, user) {
    await page.goto('/index.php/auth/logout');
    await page.waitForLoadState('networkidle');
    await page.goto('/index.php/auth/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Contrôle vols sans cotisation', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, ADMIN_USER);
    });

    test('carte du dashboard et page du contrôle', async ({ page }) => {
        await page.goto('/index.php/welcome/section/admin_club');
        const card = page.locator('h5:has-text("Contrôles et vérifications") + div.row a[href*="licences/vols_sans_cotisation"]');
        await expect(card).toHaveCount(1);

        await card.click();
        await page.waitForLoadState('networkidle');
        const year = new Date().getFullYear();
        await expect(page.locator('h3')).toContainText(String(year));
        await expect(page.locator('body')).not.toContainText('A PHP Error was encountered');

        const empty = page.locator(`.alert-success:has-text("Il n'y a pas de pilote ayant volé sans cotisation en ${year}")`);
        const table = page.locator('table thead:has-text("Dernier vol")');
        expect(await empty.count() + await table.count()).toBe(1);
    });

    test('le sélecteur change l\'année contrôlée', async ({ page }) => {
        await page.goto('/index.php/licences/vols_sans_cotisation');
        const options = await page.locator('#vsc_year option').evaluateAll(opts => opts.map(o => o.value));
        const current = await page.locator('#vsc_year').inputValue();
        const other = options.find(v => v !== current);
        test.skip(!other, 'Une seule année disponible');

        await page.selectOption('#vsc_year', other);
        await page.waitForURL(`**/licences/vols_sans_cotisation/${other}`);
        await expect(page.locator('h3')).toContainText(other);
    });

    test('année sans vol : message explicite', async ({ page }) => {
        await page.goto('/index.php/licences/vols_sans_cotisation/1990');
        await expect(page.locator('.alert-success')).toContainText("Il n'y a pas de pilote ayant volé sans cotisation en 1990");
    });
});
