/**
 * Playwright smoke test – booléens modifiables en un clic dans la liste des
 * types de documents (obligatoire, expiration, alarmes, actif).
 *
 * Le test crée son propre type de document et le supprime en fin de test.
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const ADMIN = { username: 'panoramix', password: 'password' };
const MEMBER = { username: 'asterix', password: 'password' };
const TYPE_CODE = 'zz_toggle_test';

let db;
let typeId;

async function login(page, user) {
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function column(field) {
    const [rows] = await db.query(`SELECT ${field} AS v FROM document_types WHERE id = ?`, [typeId]);
    return rows[0].v;
}

test.describe('Types de documents – bascule en un clic', () => {
    // Un seul type de test partagé : exécution en série
    test.describe.configure({ mode: 'serial' });

    test.beforeAll(async () => {
        db = await mysql.createConnection(DB_CONFIG);
        await db.query('DELETE FROM document_types WHERE code = ?', [TYPE_CODE]);
        const [result] = await db.query(
            `INSERT INTO document_types (code, name, scope, required, has_expiration, alert_days_before, alarm_enabled, active)
             VALUES (?, 'Type bascule test', 'pilot', 0, 1, 30, 1, 1)`, [TYPE_CODE]);
        typeId = result.insertId;
    });

    test.afterAll(async () => {
        await db.query('DELETE FROM document_types WHERE code = ?', [TYPE_CODE]); // alarmes en cascade
        await db.end();
    });

    test('un clic sur la case Alarmes active ou désactive le booléen', async ({ page }) => {
        await login(page, ADMIN);
        await page.goto('/index.php/document_types');

        const box = page.locator(`input.gvv-toggle[data-id="${typeId}"][data-field="alarm_enabled"]`);
        await expect(box).toBeChecked();
        await expect(box).toBeEnabled();

        await box.click();
        await expect(page.locator('#gvv-toggle-message.alert-success')).toContainText('Type bascule test');
        await expect(box).not.toBeChecked();
        expect(await column('alarm_enabled')).toBe(0);
        const [defs] = await db.query("SELECT active FROM alarm_definitions WHERE code = ?", [`document_${typeId}`]);
        expect(defs.length === 0 || defs[0].active === 0).toBeTruthy();

        await box.click();
        await expect(page.locator('#gvv-toggle-message.alert-success')).toBeVisible();
        await expect(box).toBeChecked();
        await expect.poll(() => column('alarm_enabled')).toBe(1);
        const [after] = await db.query("SELECT active FROM alarm_definitions WHERE code = ?", [`document_${typeId}`]);
        expect(after[0].active).toBe(1);
    });

    test('les autres booléens de la liste sont aussi des cases cliquables', async ({ page }) => {
        await login(page, ADMIN);
        await page.goto('/index.php/document_types');

        for (const field of ['required', 'has_expiration', 'alarm_enabled', 'active']) {
            await expect(page.locator(`input.gvv-toggle[data-id="${typeId}"][data-field="${field}"]`)).toHaveCount(1);
        }
        const required = page.locator(`input.gvv-toggle[data-id="${typeId}"][data-field="required"]`);
        await required.click();
        await expect.poll(() => column('required')).toBe(1);
        await required.click();
        await expect.poll(() => column('required')).toBe(0);
    });

    test('un champ non modifiable est refusé', async ({ page }) => {
        await login(page, ADMIN);
        const response = await page.request.post('/index.php/document_types/ajax_toggle',
            { form: { id: String(typeId), field: 'name', value: '1' } });
        const result = await response.json();
        expect(result.success).toBe(false);
        expect(result.message).toBeTruthy();
    });

    test('un simple membre ne peut pas basculer un booléen', async ({ page }) => {
        await login(page, MEMBER);
        const response = await page.request.post('/index.php/document_types/ajax_toggle',
            { form: { id: String(typeId), field: 'active', value: '0' } });
        const type = response.headers()['content-type'] || '';
        if (type.includes('application/json')) {
            expect((await response.json()).success).toBe(false);
        } else {
            expect(response.status() === 403 || response.url().includes('auth/login')
                || !(await response.text()).includes('"success":true')).toBeTruthy();
        }
        expect(await column('active')).toBe(1);
    });
});
