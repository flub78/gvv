/**
 * Smoke test — Actif par section (migration membres.actif → user_roles_per_section)
 *
 * Vérifie que le sélecteur de pilote du formulaire de vol planeur affiche uniquement
 * les membres ayant le rôle 'user' dans la section active, et pas les membres sans ce rôle.
 *
 * Test user: testplanchiste (rôles planchiste + user, section Planeur id=1)
 *
 * Assertions clés :
 * - jjmonvoisin : membres.actif=0 MAIS rôle 'user' dans Planeur → DOIT apparaître
 *   (preuve que le filtre utilise les rôles, pas membres.actif)
 * - 9992 (LACOFFRETTE) : aucun rôle 'user' dans Planeur → NE DOIT PAS apparaître
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/actif-par-section-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const PLANCHISTE = { username: 'testplanchiste', password: 'password', section: '1' };

// Membre avec membres.actif=0 mais rôle 'user' dans Planeur — doit apparaître
const ACTIVE_BY_ROLE_ONLY = 'jjmonvoisin';

// Membre sans rôle 'user' dans Planeur — ne doit pas apparaître
const INACTIVE_IN_SECTION = '9992';

test.describe('Actif par section — smoke tests sélecteur pilote', () => {

    test.beforeEach(async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login(PLANCHISTE.username, PLANCHISTE.password, PLANCHISTE.section);
    });

    test('le formulaire de vol planeur est accessible sans erreur', async ({ page }) => {
        await page.goto('/index.php/vols_planeur/create');
        await page.waitForLoadState('networkidle');

        const body = await page.locator('body').textContent();
        expect(body).not.toContain('A PHP Error was encountered');
        expect(body).not.toContain('404 Not Found');
    });

    test('le sélecteur de pilote est présent et contient au moins un pilote', async ({ page }) => {
        await page.goto('/index.php/vols_planeur/create');
        await page.waitForLoadState('networkidle');

        const pilotSelect = page.locator('select[name="vppilid"]');
        await expect(pilotSelect).toBeVisible({ timeout: 5000 });

        const options = await pilotSelect.locator('option').all();
        expect(options.length).toBeGreaterThan(1);
    });

    test('un membre avec membres.actif=0 mais rôle user apparaît dans le sélecteur', async ({ page }) => {
        await page.goto('/index.php/vols_planeur/create');
        await page.waitForLoadState('networkidle');

        const pilotSelect = page.locator('select[name="vppilid"]');
        await expect(pilotSelect).toBeVisible({ timeout: 5000 });

        // jjmonvoisin a membres.actif=0 mais le rôle 'user' dans Planeur — doit apparaître
        const option = pilotSelect.locator(`option[value="${ACTIVE_BY_ROLE_ONLY}"]`);
        await expect(option).toBeAttached({ timeout: 3000 });
    });

    test('un membre sans rôle user dans la section n\'apparaît pas dans le sélecteur', async ({ page }) => {
        await page.goto('/index.php/vols_planeur/create');
        await page.waitForLoadState('networkidle');

        const pilotSelect = page.locator('select[name="vppilid"]');
        await expect(pilotSelect).toBeVisible({ timeout: 5000 });

        // 9992 (LACOFFRETTE) n'a pas le rôle 'user' dans Planeur — ne doit pas apparaître
        const option = pilotSelect.locator(`option[value="${INACTIVE_IN_SECTION}"]`);
        await expect(option).not.toBeAttached();
    });

    test('en mode toutes sections le sélecteur fonctionne sans erreur', async ({ page }) => {
        // Passer en mode toutes sections
        await page.goto('/index.php/auth/set_section/0');
        await page.waitForLoadState('networkidle');

        await page.goto('/index.php/vols_planeur/create');
        await page.waitForLoadState('networkidle');

        const body = await page.locator('body').textContent();
        expect(body).not.toContain('A PHP Error was encountered');

        const pilotSelect = page.locator('select[name="vppilid"]');
        await expect(pilotSelect).toBeVisible({ timeout: 5000 });

        const options = await pilotSelect.locator('option').all();
        expect(options.length).toBeGreaterThan(1);
    });
});
