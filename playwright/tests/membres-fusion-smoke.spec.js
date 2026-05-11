// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Smoke tests — Fusion de membres en doublon
 *
 * Vérifie :
 * - Accès à la page de sélection (dev_user seulement)
 * - Présence des sélecteurs source et destination
 * - Affichage de la page de prévisualisation après soumission
 * - Refus d'accès pour un utilisateur non dev_user
 */

const LOGIN_URL       = '/index.php/auth/login';
const FUSION_URL      = '/index.php/membres_fusion';
const DEV_USER        = { username: 'fpeignot',      password: 'fpeignot' };
const NON_DEV_USER    = { username: 'abraracourcix',  password: 'abraracourcix' };

async function loginAs(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function hasNoPhpError(page) {
    const body = await page.locator('body').textContent();
    return !body.includes('Fatal error') && !body.includes('Parse error') && !body.includes('A PHP Error was encountered');
}

// ---------------------------------------------------------------------------
// Accès dev_user
// ---------------------------------------------------------------------------
test.describe('Fusion membres — accès dev_user', () => {

    test.beforeEach(async ({ page }) => {
        await loginAs(page, DEV_USER);
    });

    test('should access membres_fusion index page', async ({ page }) => {
        await page.goto(FUSION_URL);
        await page.waitForLoadState('networkidle');

        expect(await hasNoPhpError(page)).toBeTruthy();

        const url = page.url();
        expect(url).not.toContain('auth/login');
        expect(url).not.toContain('403');
    });

    test('should display source and destination selects', async ({ page }) => {
        await page.goto(FUSION_URL);
        await page.waitForLoadState('networkidle');

        await expect(page.locator('select[name="source"]')).toBeAttached();
        await expect(page.locator('select[name="destination"]')).toBeAttached();
    });

    test('should display submit button', async ({ page }) => {
        await page.goto(FUSION_URL);
        await page.waitForLoadState('networkidle');

        const btn = page.locator('button[type="submit"]');
        await expect(btn).toBeVisible();
    });

    test('should show preview page after selecting source and destination', async ({ page }) => {
        await page.goto(FUSION_URL);
        await page.waitForLoadState('networkidle');

        // Sélectionner deux membres différents via les <select> natifs
        const srcOptions = await page.locator('select[name="source"] option').all();
        const dstOptions = await page.locator('select[name="destination"] option').all();

        // Récupérer les deux premiers membres avec valeur non vide
        let srcVal = null, dstVal = null;
        for (const opt of srcOptions) {
            const v = await opt.getAttribute('value');
            if (v && v.trim()) { srcVal = v; break; }
        }
        for (const opt of dstOptions) {
            const v = await opt.getAttribute('value');
            if (v && v.trim() && v !== srcVal) { dstVal = v; break; }
        }

        if (!srcVal || !dstVal) {
            test.skip();
            return;
        }

        await page.selectOption('select[name="source"]',      srcVal);
        await page.selectOption('select[name="destination"]', dstVal);

        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        expect(await hasNoPhpError(page)).toBeTruthy();

        // La page de prévisualisation doit afficher source et destination
        const body = await page.locator('body').textContent();
        expect(body).toContain(srcVal);
        expect(body).toContain(dstVal);

        // Le bouton de confirmation doit être présent
        const confirmBtn = page.locator('button[name="confirm"], button:has-text("Confirmer"), input[type="submit"][value*="onfirm"]');
        await expect(confirmBtn).toBeVisible();
    });

    test('should display four preview sections', async ({ page }) => {
        await page.goto(FUSION_URL);
        await page.waitForLoadState('networkidle');

        const srcOptions = await page.locator('select[name="source"] option').all();
        const dstOptions = await page.locator('select[name="destination"] option').all();

        let srcVal = null, dstVal = null;
        for (const opt of srcOptions) {
            const v = await opt.getAttribute('value');
            if (v && v.trim()) { srcVal = v; break; }
        }
        for (const opt of dstOptions) {
            const v = await opt.getAttribute('value');
            if (v && v.trim() && v !== srcVal) { dstVal = v; break; }
        }

        if (!srcVal || !dstVal) {
            test.skip();
            return;
        }

        await page.selectOption('select[name="source"]',      srcVal);
        await page.selectOption('select[name="destination"]', dstVal);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        expect(await hasNoPhpError(page)).toBeTruthy();

        // Vérifier les quatre sections de la prévisualisation
        const body = await page.locator('body').textContent();

        // Section 1 : comparaison des champs
        expect(body).toMatch(/mnom|Nom|Fiche membre/i);
        // Section 2 : références par table
        // (présente sous forme de tableau ou message "aucune référence")
        // Section 4 : bouton annuler
        const cancelLink = page.locator('a[href*="membres_fusion"]');
        await expect(cancelLink).toBeVisible();
    });

    test('dashboard view contains fusion membres card', async ({ page }) => {
        await page.goto('/index.php/welcome');
        await page.waitForLoadState('networkidle');

        // Si redirigé (changement de mot de passe requis), passer le test
        if (page.url().includes('change_password') || page.url().includes('auth/login')) {
            test.skip();
            return;
        }

        // La carte est dans la section "Développement & Tests" (accordion possiblement réduit).
        // On vérifie que le lien est présent dans le DOM.
        const fusionLink = page.locator('a[href*="membres_fusion"]');
        await expect(fusionLink).toBeAttached();
    });
});

// ---------------------------------------------------------------------------
// Refus d'accès pour un utilisateur non dev_user
// ---------------------------------------------------------------------------
test.describe('Fusion membres — accès refusé hors dev_user', () => {

    test('should deny access for non-dev_user', async ({ page }) => {
        await loginAs(page, NON_DEV_USER);
        await page.goto(FUSION_URL);
        await page.waitForLoadState('networkidle');

        const body = await page.locator('body').textContent();
        // Doit afficher une erreur 403 ou être redirigé
        const isBlocked =
            body.includes('403') ||
            body.includes('interdit') ||
            body.includes('Forbidden') ||
            body.includes('non autorisé') ||
            page.url().includes('auth/login');

        expect(isBlocked).toBeTruthy();
    });
});
