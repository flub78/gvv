/**
 * Smoke test for the Maintenance module — Equipements (Phase 5, Etape 5.1)
 *
 * Prerequisites:
 *   - obelix user exists with mecano role in section Planeur (id=1)
 *     See bin/create_test_users.sh
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

test.describe('Maintenance - Equipements (mecano)', () => {

    test('mecano can create, edit, transfer and deactivate an equipement', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        // Liste (vide ou non)
        await page.goto('/index.php/maintenance_equipements');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('h3')).toContainText('quipements');

        // Creation
        await page.goto('/index.php/maintenance_equipements/create');
        await page.waitForLoadState('networkidle');
        const nom = 'Parachute smoke ' + Date.now();
        await page.fill('#nom', nom);
        const aeronefOptions = await page.locator('#aeronef_id option').all();
        expect(aeronefOptions.length).toBeGreaterThan(1);
        const firstAeronefValue = await aeronefOptions[1].getAttribute('value');
        await page.selectOption('#aeronef_id', firstAeronefValue);
        await page.fill('#description', 'Equipement cree par le smoke test');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Doit apparaitre dans la liste
        await expect(page.locator('body')).toContainText(nom);
        await expect(page.locator('.alert-success')).toBeVisible();

        // Edition
        const row = page.locator('tr', { hasText: nom });
        await row.locator('a[title="Modifier"]').click();
        await page.waitForLoadState('networkidle');
        await page.fill('#description', 'Description modifiee par le smoke test');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();

        // Transfert
        const rowAfterEdit = page.locator('tr', { hasText: nom });
        await rowAfterEdit.locator('a[title="Transférer"]').click();
        await page.waitForLoadState('networkidle');
        await expect(page.locator('h3')).toContainText('Transf');

        const targetOptions = await page.locator('#nouvel_aeronef_id option').all();
        let targetValue = null;
        for (const opt of targetOptions) {
            const v = await opt.getAttribute('value');
            if (v && v !== firstAeronefValue) {
                targetValue = v;
                break;
            }
        }
        expect(targetValue).not.toBeNull();
        await page.selectOption('#nouvel_aeronef_id', targetValue);
        await page.check('#confirmation');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('body')).toContainText(targetValue);

        // Desactivation
        const rowAfterTransfer = page.locator('tr', { hasText: nom });
        page.once('dialog', dialog => dialog.accept());
        await rowAfterTransfer.locator('a[title="Désactiver"]').click();
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();

        const rowAfterDeactivate = page.locator('tr', { hasText: nom });
        await expect(rowAfterDeactivate).toContainText('Inactif');
    });

    test('non-mecano user is denied access', async ({ page }) => {
        // asterix has no mecano role in any section
        await login(page, { username: 'asterix', password: 'password' });
        await page.goto('/index.php/maintenance_equipements');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/r.serv.|interdit|403/i);
    });
});
