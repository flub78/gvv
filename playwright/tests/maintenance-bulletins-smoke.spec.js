/**
 * Smoke test for the Maintenance module — Bulletins de service (Phase 5, Etape 5.5)
 *
 * Prerequisites:
 *   - obelix user exists with mecano role in section Planeur (id=1)
 *   - asterix user exists WITHOUT mecano role (any section)
 *     See bin/create_test_users.sh
 */

const { test, expect } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

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

test.describe('Maintenance - Bulletins de service (mecano)', () => {

    test('mecano can upload a bulletin and change its status', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        await page.goto('/index.php/maintenance_bulletins');
        await page.waitForLoadState('networkidle');
        const aeronefOptions = await page.locator('#machine_immat_select option').all();
        expect(aeronefOptions.length).toBeGreaterThan(1);
        const aeronefValue = await aeronefOptions[1].getAttribute('value');

        await page.goto('/index.php/maintenance_bulletins/upload_form/' + aeronefValue);
        await page.waitForLoadState('networkidle');

        const fixturePath = path.join(__dirname, 'fixtures-tmp-bulletin.pdf');
        fs.writeFileSync(fixturePath, '%PDF-1.4 smoke test bulletin');
        await page.setInputFiles('#bulletin_file', fixturePath);
        await page.fill('#description', 'Bulletin smoke test');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        fs.unlinkSync(fixturePath);

        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('body')).toContainText('Bulletin smoke test');
        await expect(page.locator('body')).toContainText('À traiter');

        // Change status to "Traité"
        await page.selectOption('select[name="statut"]', 'traite');
        await page.click('button:has-text("Enregistrer")');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('body')).toContainText('Traité');
    });

    test('non-mecano user is denied access', async ({ page }) => {
        await login(page, { username: 'asterix', password: 'password' });
        await page.goto('/index.php/maintenance_bulletins');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/r.serv.|interdit|403/i);
    });
});
