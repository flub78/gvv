/**
 * Playwright smoke test — Cartes dynamiques dans les dashboards (Lot 7)
 *
 * Vérifie le parcours complet :
 *  - un club-admin (testadmin) peut créer un raccourci depuis shortcuts_admin ;
 *  - le raccourci apparaît comme carte dans le dashboard cible (welcome/section/formation) ;
 *  - le désactiver le fait disparaître, le réactiver le fait réapparaître ;
 *  - un rôle requis non satisfait masque la carte pour un autre utilisateur (asterix) ;
 *  - la suppression retire le raccourci de la liste admin.
 *
 * Prerequisites: testadmin (role club-admin, bypass admin) et asterix (role 'user',
 * sans rôle 'ca') existent — voir bin/create_test_users.sh.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/dashboard-shortcuts-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const ADMIN_USER = { username: 'testadmin', password: 'password' };
const NON_ADMIN_USER = { username: 'asterix', password: 'password' };
const PLANEUR_SECTION = '1';
const SHORTCUT_TITLE = 'PW Shortcut Test ' + Date.now();

async function login(page, user) {
    await page.goto('/index.php/auth/logout');
    await page.waitForLoadState('networkidle');
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function switchToPlaneurSection(page) {
    await page.request.post('/index.php/user_roles_per_section/set_section', {
        form: { section: PLANEUR_SECTION, current_url: '/index.php/welcome' }
    });
}

test.describe('Lot 7 - Raccourcis de dashboard', () => {

    test('CRUD admin, affichage conditionnel (actif/rôle) dans le dashboard cible', async ({ page }) => {
        await login(page, ADMIN_USER);
        await switchToPlaneurSection(page);

        // --- Création ---
        await page.goto('/index.php/shortcuts_admin/create');
        await page.waitForLoadState('networkidle');

        await page.selectOption('select[name="dashboard"]', 'formation');
        await page.fill('input[name="title"]', SHORTCUT_TITLE);
        await page.fill('input[name="url"]', 'forms_admin/generate/attestation-de-formation-ulm');
        await page.fill('input[name="icon"]', 'fa-file-signature');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText(SHORTCUT_TITLE);

        // --- Apparaît dans le dashboard formation ---
        await page.goto('/index.php/welcome/section/formation');
        await page.waitForLoadState('networkidle');
        const card = page.locator('.sub-card', { hasText: SHORTCUT_TITLE });
        await expect(card).toBeVisible();
        await expect(card.locator('a')).toHaveAttribute('href', /forms_admin\/generate\/attestation-de-formation-ulm/);

        // --- Désactivation : disparaît ---
        await page.goto('/index.php/shortcuts_admin');
        await page.waitForLoadState('networkidle');
        const row = page.locator('tr', { hasText: SHORTCUT_TITLE });
        await row.locator('form button[type="submit"]').first().click(); // toggle actif -> inactif
        await page.waitForLoadState('networkidle');

        await page.goto('/index.php/welcome/section/formation');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.sub-card', { hasText: SHORTCUT_TITLE })).toHaveCount(0);

        // --- Réactivation : réapparaît ---
        await page.goto('/index.php/shortcuts_admin');
        await page.waitForLoadState('networkidle');
        await page.locator('tr', { hasText: SHORTCUT_TITLE }).locator('form button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');

        await page.goto('/index.php/welcome/section/formation');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.sub-card', { hasText: SHORTCUT_TITLE })).toBeVisible();

        // --- Rôle requis non satisfait : édition pour exiger 'ca' ---
        await page.goto('/index.php/shortcuts_admin');
        await page.waitForLoadState('networkidle');
        await page.locator('tr', { hasText: SHORTCUT_TITLE }).locator('a:has-text("Modifier")').click();
        await page.waitForLoadState('networkidle');
        await page.selectOption('select[name="role_required"]', 'ca');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // asterix n'a pas le rôle 'ca' : carte masquée
        await login(page, NON_ADMIN_USER);
        await page.goto('/index.php/welcome/section/formation');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.sub-card', { hasText: SHORTCUT_TITLE })).toHaveCount(0);

        // testadmin (bypass admin) continue de la voir
        await login(page, ADMIN_USER);
        await switchToPlaneurSection(page);
        await page.goto('/index.php/welcome/section/formation');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.sub-card', { hasText: SHORTCUT_TITLE })).toBeVisible();

        // --- Suppression ---
        await page.goto('/index.php/shortcuts_admin');
        await page.waitForLoadState('networkidle');
        page.once('dialog', d => d.accept());
        await page.locator('tr', { hasText: SHORTCUT_TITLE }).locator('button:has-text("Supprimer")').click();
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toContainText(SHORTCUT_TITLE);
    });
});
