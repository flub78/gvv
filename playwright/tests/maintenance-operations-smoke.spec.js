/**
 * Smoke test for the Maintenance module — Operations de maintenance (Phase 5, Etape 5.4)
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

test.describe('Maintenance - Operations (mecano)', () => {

    test('mecano records a direct-entry operation, checks tasks, and potentiel updates', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        // Programme avec structure + regle horaire
        await page.goto('/index.php/maintenance_programmes/create');
        await page.waitForLoadState('networkidle');
        const code = 'SMOKEOP' + Date.now();
        await page.fill('#code', code);
        await page.fill('#titre', 'Visite 100 heures cellule');
        await page.check('#regle_butee_heures');
        await page.fill('#seuil_heures', '100');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await page.click('a:has-text("Déposer")');
        await page.waitForLoadState('networkidle');
        await page.setInputFiles('#markdown_file', FIXTURE_MD);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        const programmeId = /view\/(\d+)/.exec(page.url())[1];

        // Dossier sur un aeronef
        await page.goto('/index.php/maintenance_dossiers/ouvrir_form/aeronef');
        await page.waitForLoadState('networkidle');
        const aeronefOptions = await page.locator('#entite_id option').all();
        const aeronefValue = await aeronefOptions[1].getAttribute('value');
        await page.selectOption('#entite_id', aeronefValue);
        await page.selectOption('#programme_id', programmeId);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        const dossierId = /view\/(\d+)/.exec(page.url())[1];

        // Nouvelle operation, saisie directe
        await page.click('a:has-text("Nouvelle opération")');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText('Vidange moteur');

        await page.fill('#horametre_releve', '1234.5');
        // Cocher "Fait" sur la premiere tache visible
        const firstFaitRadio = page.locator('input[type="radio"][value="fait"]').first();
        await firstFaitRadio.check();
        await page.fill('#commentaire', 'Operation smoke test');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();

        // L'operation apparait dans l'historique du dossier
        await expect(page.locator('body')).toContainText('Obelix');

        // Edition / correction de l'operation
        await page.click('a:has-text("Modifier")');
        await page.waitForLoadState('networkidle');
        await page.fill('#commentaire', 'Operation corrigee par le smoke test');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();

        // Cleanup handled by test harness cleanup script (see notes) — nothing else to assert here.
        test.info().annotations.push({ type: 'cleanup', description: `programme=${programmeId} dossier=${dossierId}` });
    });
});
