/**
 * Smoke test for the Maintenance module — Programmes d'entretien (Phase 5, Etape 5.2)
 *
 * Prerequisites:
 *   - obelix user exists with mecano role in section Planeur (id=1)
 *     See bin/create_test_users.sh
 */

const { test, expect } = require('@playwright/test');
const path = require('path');

const LOGIN_URL = '/index.php/auth/login';
const MECANO_USER = { username: 'obelix', password: 'password' };
const PLANEUR_SECTION = '1';
const FIXTURE_MD = path.join(__dirname, '..', '..', 'doc', 'test-data', 'maintenance_visite_100h.md');

async function login(page, user) {
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

test.describe('Maintenance - Programmes entretien (mecano)', () => {

    test('mecano can create a program, upload a markdown version, see parsed structure, and re-upload', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        // Creation (metadonnees)
        await page.goto('/index.php/maintenance_programmes/create');
        await page.waitForLoadState('networkidle');
        const code = 'SMOKE' + Date.now();
        await page.fill('#code', code);
        await page.fill('#titre', 'Visite 100 heures cellule');
        await page.check('#regle_butee_heures');
        await page.fill('#seuil_heures', '100');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();

        // On the view page now — upload the fixture markdown
        await expect(page.url()).toMatch(/maintenance_programmes\/view\//);
        await page.click('a:has-text("Déposer")');
        await page.waitForLoadState('networkidle');
        await page.setInputFiles('#markdown_file', FIXTURE_MD);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();

        // Structure should now be visible: 3 sections from the fixture
        await expect(page.locator('body')).toContainText('Moteur');
        await expect(page.locator('body')).toContainText('Cellule');
        await expect(page.locator('body')).toContainText('Equipements de securite');
        await expect(page.locator('body')).toContainText('Vidange moteur');

        // Re-upload the same fixture (new version) : should sync without duplicating
        await page.click('a:has-text("Déposer")');
        await page.waitForLoadState('networkidle');
        await page.setInputFiles('#markdown_file', FIXTURE_MD);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('body')).toContainText('Vidange moteur');

        // Appears in the list
        await page.goto('/index.php/maintenance_programmes');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(code);

        // Deactivate then reactivate
        const row = page.locator('tr', { hasText: code });
        page.once('dialog', dialog => dialog.accept());
        await row.locator('a[title="Archiver"]').click();
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();
        const rowAfterDeactivate = page.locator('tr', { hasText: code });
        await expect(rowAfterDeactivate).toContainText('Inactif');

        await rowAfterDeactivate.locator('a[title="Réactiver"]').click();
        await page.waitForLoadState('networkidle');
        const rowAfterReactivate = page.locator('tr', { hasText: code });
        await expect(rowAfterReactivate).toContainText('Actif');
    });

    test('rejects an invalid markdown file without archiving it', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        await page.goto('/index.php/maintenance_programmes/create');
        await page.waitForLoadState('networkidle');
        const code = 'SMOKEBAD' + Date.now();
        await page.fill('#code', code);
        await page.fill('#titre', 'Programme invalide');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await page.click('a:has-text("Déposer")');
        await page.waitForLoadState('networkidle');

        const invalidPath = path.join(__dirname, '..', '..', 'doc', 'test-data', 'maintenance_invalid_smoke.md');
        require('fs').writeFileSync(invalidPath, '# Programme sans section\n\nDu texte, mais aucune section.');
        await page.setInputFiles('#markdown_file', invalidPath);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-danger')).toBeVisible();
        await expect(page.locator('body')).toContainText('section');
        require('fs').unlinkSync(invalidPath);
    });
});
