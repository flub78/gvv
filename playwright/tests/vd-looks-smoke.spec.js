// @ts-check
const { test, expect } = require('@playwright/test');
const path = require('path');

/**
 * Smoke tests — Configuration des bons de vol de découverte (Lot 3)
 *
 * Vérifie :
 * - Accès admin à la liste des looks, création, édition, upload de fond
 * - Déplacement d'un champ + sauvegarde de la mise en page
 * - Export puis import du JSON produisant un résultat identique
 * - Association d'un look à une section
 * - Refus d'accès pour un utilisateur sans droit d'administration vd
 *
 * @see doc/plans/configuration_bons_vols_decouverte_plan.md
 */

const LOGIN_URL = '/index.php/auth/login';
const ADMIN_USER = { username: 'testadmin', password: 'password' };
const MEMBER_USER = { username: 'testuser', password: 'password' };
// Small fixture (well under upload_max_filesize) — Bon-Bapteme.png is too large for the test php.ini limit.
const FOND_FIXTURE = path.resolve(__dirname, '../../assets/images/gvv_icon_64.png');

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('input[name="username"]', { timeout: 5000 });
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Configuration des bons de vol de découverte — accès admin', () => {

    test.beforeEach(async ({ page }) => {
        await login(page, ADMIN_USER);
    });

    test('should access the looks list page', async ({ page }) => {
        await page.goto('/index.php/vols_decouverte_looks');
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        expect(bodyText).not.toContain('404 Not Found');
        expect(bodyText).not.toContain('PHP Error was encountered');
        await expect(page.locator('h4')).toContainText('vol de découverte');
    });

    test('should create a new look and open its editor', async ({ page }) => {
        await page.goto('/index.php/vols_decouverte_looks');
        await page.waitForLoadState('networkidle');

        const lookName = 'Look Playwright ' + Date.now();
        await page.fill('input[name="nom"]', lookName);
        await page.click('button:has-text("Créer")');
        await page.waitForLoadState('networkidle');

        expect(page.url()).toContain('vols_decouverte_looks/edit/');
        const bodyText = await page.locator('body').textContent();
        expect(bodyText).not.toContain('PHP Error was encountered');
        await expect(page.locator('input[name="nom"]')).toHaveValue(lookName);
    });

    test('should upload a recto background image', async ({ page }) => {
        await page.goto('/index.php/vols_decouverte_looks');
        await page.waitForLoadState('networkidle');

        const lookName = 'Look Fond ' + Date.now();
        await page.fill('input[name="nom"]', lookName);
        await page.click('button:has-text("Créer")');
        await page.waitForLoadState('networkidle');

        await page.setInputFiles('input[name="fond_recto"]', FOND_FIXTURE);
        await page.click('form[action*="upload_fond"] button[type="submit"]');
        await page.waitForLoadState('networkidle');

        const alert = page.locator('.alert-success');
        await expect(alert).toBeVisible();
        await expect(page.locator('img[alt="Fond recto"]')).toBeVisible();
    });

    test('should move a field and save the layout', async ({ page }) => {
        await page.goto('/index.php/vols_decouverte_looks');
        await page.waitForLoadState('networkidle');

        const lookName = 'Look Layout ' + Date.now();
        await page.fill('input[name="nom"]', lookName);
        await page.click('button:has-text("Créer")');
        await page.waitForLoadState('networkidle');

        // The default layout only carries variable fields on the verso (recto only has the QR code).
        await page.click('#verso-tab');
        const xInput = page.locator('#tab-verso input[name="verso_var_x[]"]').first();
        await xInput.fill('42');

        await page.click('button[type="submit"]:has-text("Enregistrer la mise en page")');
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        expect(bodyText).not.toContain('PHP Error was encountered');
        await expect(page.locator('.alert-success')).toBeVisible();
        await page.click('#verso-tab');
        await expect(page.locator('#tab-verso input[name="verso_var_x[]"]').first()).toHaveValue('42');
    });

    test('should export and re-import the layout with an identical result', async ({ page }) => {
        await page.goto('/index.php/vols_decouverte_looks');
        await page.waitForLoadState('networkidle');

        const lookName = 'Look Export ' + Date.now();
        await page.fill('input[name="nom"]', lookName);
        await page.click('button:has-text("Créer")');
        await page.waitForLoadState('networkidle');

        const [download] = await Promise.all([
            page.waitForEvent('download'),
            page.click('a[href*="layout_export"]'),
        ]);
        const exportPath = await download.path();
        expect(exportPath).toBeTruthy();

        await page.click('button[data-bs-target="#importModal"]');
        await page.setInputFiles('input[name="layout_json"]', exportPath);
        await page.click('#importModal button[type="submit"]:has-text("Importer JSON")');
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        expect(bodyText).not.toContain('PHP Error was encountered');
        await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('should associate a look to a section', async ({ page }) => {
        await page.goto('/index.php/vols_decouverte_looks');
        await page.waitForLoadState('networkidle');

        const lookName = 'Look Section ' + Date.now();
        await page.fill('input[name="nom"]', lookName);
        await page.click('button:has-text("Créer")');
        await page.waitForLoadState('networkidle');

        await page.goto('/index.php/vols_decouverte_looks/sections');
        await page.waitForLoadState('networkidle');

        const select = page.locator('select[name^="look_"]').first();
        await expect(select).toBeVisible();
        await select.selectOption({ label: lookName });

        await page.click('button[name="save_sections"]');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('select[name^="look_"]').first()).toHaveValue(await select.inputValue());
    });

    test('should return 404 for an unknown look id', async ({ page }) => {
        await page.goto('/index.php/vols_decouverte_looks/edit/999999');
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        expect(bodyText).toContain('introuvable');
    });
});

test.describe('Configuration des bons de vol de découverte — accès refusé', () => {

    test('should deny access to a member without vd admin rights', async ({ page }) => {
        await login(page, MEMBER_USER);
        await page.goto('/index.php/vols_decouverte_looks');
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        // show_error(..., 403) renders CodeIgniter's generic error page, not the looks list.
        const hasLooksList = await page.locator('h4:has-text("vol de découverte")').count() > 0;
        expect(hasLooksList).toBeFalsy();
        expect(bodyText).not.toContain('gvv_vd_looks_new');
    });

    test('should redirect unauthenticated users to login', async ({ page }) => {
        await page.context().clearCookies();
        await page.goto('/index.php/vols_decouverte_looks');
        await page.waitForLoadState('networkidle');

        expect(page.url()).toContain('auth/login');
    });
});
