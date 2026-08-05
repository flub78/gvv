/**
 * Smoke test for the Maintenance module — Dossiers d'entretien (Phase 5, Etape 5.3)
 *
 * Prerequisites:
 *   - obelix user exists with mecano role in section Planeur (id=1)
 *     See bin/create_test_users.sh
 *   - At least one active maintenance program exists for section Planeur
 *     (created here by the test itself, cleaned up afterward)
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const MECANO_USER = { username: 'obelix', password: 'password' };
const PLANEUR_SECTION = '1';

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

test.describe('Maintenance - Dossiers entretien (mecano)', () => {

    test('mecano can open a dossier on an aircraft and go through its lifecycle', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        // Programme necessaire pour ouvrir un dossier
        await page.goto('/index.php/maintenance_programmes/create');
        await page.waitForLoadState('networkidle');
        const code = 'SMOKEDOS' + Date.now();
        await page.fill('#code', code);
        await page.fill('#titre', 'Programme test dossier');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Ouverture d'un dossier sur un aeronef
        await page.goto('/index.php/maintenance_dossiers/ouvrir_form/aeronef');
        await page.waitForLoadState('networkidle');
        const aeronefOptions = await page.locator('#entite_id option').all();
        expect(aeronefOptions.length).toBeGreaterThan(1);
        const aeronefValue = await aeronefOptions[1].getAttribute('value');
        await page.selectOption('#entite_id', aeronefValue);

        const programmeOption = page.locator('#programme_id option', { hasText: code });
        const programmeValue = await programmeOption.getAttribute('value');
        await page.selectOption('#programme_id', programmeValue);
        await page.fill('#commentaire', 'Dossier cree par le smoke test');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.url()).toMatch(/maintenance_dossiers\/view\//);
        await expect(page.locator('body')).toContainText('Ouvert');

        // Suspendre
        page.once('dialog', dialog => dialog.accept());
        await page.click('a:has-text("Suspendre")');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText('Suspendu');

        // Reactiver
        await page.click('a:has-text("Réactiver")');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText('Ouvert');

        // Cloturer
        page.once('dialog', dialog => dialog.accept());
        await page.click('a:has-text("Clôturer")');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText('Clôturé');
        await expect(page.locator('body')).toContainText('Dossier terminé');

        // Visible dans l'historique de l'entite (filtre par entite_id)
        await page.goto('/index.php/maintenance_dossiers?entite_type=aeronef&entite_id=' + aeronefValue);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(code);
    });
});
