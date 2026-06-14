// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Smoke tests — Renommage d'identifiant membre
 *
 * Vérifie :
 * - Accès à la page de renommage (dev_user seulement)
 * - Présence du formulaire de sélection
 * - Validation des identifiants invalides (côté client)
 * - Affichage de la page de prévisualisation après soumission
 * - Refus d'accès pour un utilisateur non dev_user
 */

const LOGIN_URL       = '/index.php/auth/login';
const RENAME_URL      = '/index.php/membre/renommer';
const DEV_USER        = { username: 'testadmin',     password: 'password' };
const NON_DEV_USER    = { username: 'abraracourcix',  password: 'password' };

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
test.describe('Renommer membre — accès dev_user', () => {

    test.beforeEach(async ({ page }) => {
        await loginAs(page, DEV_USER);
    });

    test('should access membre/renommer page', async ({ page }) => {
        await page.goto(RENAME_URL);
        await page.waitForLoadState('networkidle');

        expect(await hasNoPhpError(page)).toBeTruthy();

        const url = page.url();
        expect(url).not.toContain('auth/login');
        expect(url).not.toContain('403');
    });

    test('should display member selector and new login input', async ({ page }) => {
        await page.goto(RENAME_URL);
        await page.waitForLoadState('networkidle');

        await expect(page.locator('select[name="old_mlogin"]')).toBeAttached();
        await expect(page.locator('input[name="new_mlogin"]')).toBeAttached();
    });

    test('should display submit button', async ({ page }) => {
        await page.goto(RENAME_URL);
        await page.waitForLoadState('networkidle');

        const btn = page.locator('button[type="submit"]');
        await expect(btn).toBeVisible();
    });

    test('should show preview page after selecting member and entering new login', async ({ page }) => {
        await page.goto(RENAME_URL);
        await page.waitForLoadState('networkidle');

        // Sélectionner un membre
        const options = await page.locator('select[name="old_mlogin"] option').all();
        let memberVal = null;
        for (const opt of options) {
            const v = await opt.getAttribute('value');
            if (v && v.trim()) {
                memberVal = v;
                break;
            }
        }

        if (!memberVal) {
            test.skip();
            return;
        }

        await page.selectOption('select[name="old_mlogin"]', memberVal);

        // Saisir un nouvel identifiant (générer un login unique pour éviter conflits)
        const timestamp = Date.now();
        const newLogin = `test_${timestamp}`;
        await page.fill('input[name="new_mlogin"]', newLogin);

        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        expect(await hasNoPhpError(page)).toBeTruthy();

        // Vérifier qu'on est sur la page de prévisualisation
        const body = await page.locator('body').textContent();
        expect(body).toContain('Prévisualisation');
        expect(body).toContain(memberVal);
        expect(body).toContain(newLogin);
    });

    test('should display affected tables in preview', async ({ page }) => {
        await page.goto(RENAME_URL);
        await page.waitForLoadState('networkidle');

        const options = await page.locator('select[name="old_mlogin"] option').all();
        let memberVal = null;
        for (const opt of options) {
            const v = await opt.getAttribute('value');
            if (v && v.trim()) {
                memberVal = v;
                break;
            }
        }

        if (!memberVal) {
            test.skip();
            return;
        }

        await page.selectOption('select[name="old_mlogin"]', memberVal);
        const timestamp = Date.now();
        const newLogin = `test_${timestamp}`;
        await page.fill('input[name="new_mlogin"]', newLogin);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Vérifier la présence d'éléments clés de la prévisualisation
        const body = await page.locator('body').textContent();
        expect(body).toContain('Impact sur la base de données');

        // Vérifier la présence du bouton de confirmation
        const confirmBtn = page.locator('button[type="submit"]').filter({ hasText: /Confirmer/i });
        await expect(confirmBtn).toBeVisible();
    });

    test('should show cancel button in preview', async ({ page }) => {
        await page.goto(RENAME_URL);
        await page.waitForLoadState('networkidle');

        const options = await page.locator('select[name="old_mlogin"] option').all();
        let memberVal = null;
        for (const opt of options) {
            const v = await opt.getAttribute('value');
            if (v && v.trim()) {
                memberVal = v;
                break;
            }
        }

        if (!memberVal) {
            test.skip();
            return;
        }

        await page.selectOption('select[name="old_mlogin"]', memberVal);
        const timestamp = Date.now();
        const newLogin = `test_${timestamp}`;
        await page.fill('input[name="new_mlogin"]', newLogin);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Vérifier la présence du bouton Annuler
        const cancelBtn = page.locator('a').filter({ hasText: /Annuler/i });
        await expect(cancelBtn).toBeVisible();
    });

    test('should prevent purely numeric login via client-side validation', async ({ page }) => {
        await page.goto(RENAME_URL);
        await page.waitForLoadState('networkidle');

        const options = await page.locator('select[name="old_mlogin"] option').all();
        let memberVal = null;
        for (const opt of options) {
            const v = await opt.getAttribute('value');
            if (v && v.trim()) {
                memberVal = v;
                break;
            }
        }

        if (!memberVal) {
            test.skip();
            return;
        }

        await page.selectOption('select[name="old_mlogin"]', memberVal);
        await page.fill('input[name="new_mlogin"]', '12345');

        // Intercepter la boîte de dialogue d'alerte
        let alertShown = false;
        page.on('dialog', async dialog => {
            alertShown = true;
            expect(dialog.message()).toContain('uniquement numérique');
            await dialog.accept();
        });

        await page.click('button[type="submit"]');

        // Attendre un peu pour laisser le JS s'exécuter
        await page.waitForTimeout(500);

        // Vérifier qu'une alerte a été affichée
        expect(alertShown).toBeTruthy();
    });
});

// ---------------------------------------------------------------------------
// Accès refusé pour non-dev_user
// ---------------------------------------------------------------------------
test.describe('Renommer membre — accès refusé', () => {

    test('should deny access to non-dev_user with 403', async ({ page }) => {
        await loginAs(page, NON_DEV_USER);

        await page.goto(RENAME_URL);
        await page.waitForLoadState('networkidle');

        const body = await page.locator('body').textContent();
        // Devrait afficher une erreur 403
        expect(body).toMatch(/403|Accès|interdit|réservé/i);
    });
});
