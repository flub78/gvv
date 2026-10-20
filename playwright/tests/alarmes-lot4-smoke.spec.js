/**
 * Playwright smoke tests – Alarmes, Lot 4 (désactivation des occurrences)
 *
 * Verifies:
 *   1. A supervisor disables an alarm for himself: it leaves his list, the
 *      page counts it as disabled and shows it on demand; he re-enables it
 *   2. A club-admin, on the supervisor's page, disables it for everyone with
 *      a reason: the supervisor no longer sees it; the administration lists
 *      the disabling and re-enables it
 *
 * Prerequisites: test users abraracourcix (CA Avion), goudurix (member Avion)
 * and panoramix (club-admin), migration 180, gestion_documentaire enabled.
 * The test creates its own document type, alarm, subscription and document;
 * everything is removed in afterAll (subscriptions and disablings cascade
 * with the document type).
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const SUPERVISOR = { username: 'abraracourcix', password: 'password' };
const ADMIN = { username: 'panoramix', password: 'password' };
const AVION_PILOT = 'goudurix';
const TYPE_CODE = 'zz_alarm_lot4';
const DOC_LABEL = `zz-lot4-${AVION_PILOT}`;

async function login(page, user) {
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function cleanup(db) {
    await db.query(`DELETE ad FROM archived_documents ad JOIN document_types dt ON dt.id = ad.document_type_id
                    WHERE dt.code = ?`, [TYPE_CODE]);
    await db.query('DELETE FROM document_types WHERE code = ?', [TYPE_CODE]);
}

/** Ouvre la boîte de dialogue de désactivation de la ligne du document de test */
async function openDismiss(page) {
    const row = page.locator('#alarms-table tr', { hasText: DOC_LABEL });
    await expect(row).toHaveCount(1);
    await row.locator('.alarm-dismiss-btn').click();
    await expect(page.locator('#dismiss-modal')).toBeVisible();
    await expect(page.locator('#dismiss-label')).toContainText(DOC_LABEL);
}

test.describe('Alarmes – Lot 4', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeAll(async () => {
        const db = await mysql.createConnection(DB_CONFIG);
        try {
            await cleanup(db);
            const [sections] = await db.query("SELECT id FROM sections WHERE nom = 'Avion'");
            const avionId = sections[0].id;
            const [type] = await db.query(
                `INSERT INTO document_types (code, name, scope, required, has_expiration, alert_days_before, alarm_enabled, active)
                 VALUES (?, 'Type alarme lot 4', 'pilot', 0, 1, 30, 1, 1)`, [TYPE_CODE]);
            const [def] = await db.query(
                `INSERT INTO alarm_definitions (code, label, source, document_type_id, lead_days, active)
                 VALUES (?, 'Type alarme lot 4', 'document', ?, 30, 1)`, [`document_${type.insertId}`, type.insertId]);
            await db.query(`INSERT INTO alarm_subscriptions (alarm_definition_id, subscriber_login, section_id, mandatory)
                            VALUES (?, ?, ?, 0)`, [def.insertId, SUPERVISOR.username, avionId]);
            await db.query(
                `INSERT INTO archived_documents (document_type_id, pilot_login, file_path, original_filename, description,
                     uploaded_by, uploaded_at, valid_until, alarm_disabled, is_current_version, validation_status)
                 VALUES (?, ?, 'uploads/documents/test/zz.pdf', 'zz.pdf', ?, ?, NOW(), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 0, 1, 'approved')`,
                [type.insertId, AVION_PILOT, DOC_LABEL, AVION_PILOT]);
        } finally {
            await db.end();
        }
    });

    test.afterAll(async () => {
        const db = await mysql.createConnection(DB_CONFIG);
        try {
            await cleanup(db);
        } finally {
            await db.end();
        }
    });

    test('un superviseur désactive une alarme pour lui puis la réactive', async ({ page }) => {
        await login(page, SUPERVISOR);
        await page.goto('/index.php/mes_alarmes?section=');
        await openDismiss(page);
        // Pas de désactivation pour tout le monde sans être club-admin
        await expect(page.locator('#dismiss-scope-all')).toHaveCount(0);
        await page.click('#dismiss-submit');

        await expect(page.locator('.alert-success.alert-dismissible')).toContainText(DOC_LABEL);
        await expect(page.locator('#alarms-table tr', { hasText: DOC_LABEL })).toHaveCount(0);
        await expect(page.locator('#alarm-dismissed-notice')).toBeVisible();
        await page.click('#alarm-dismissed-toggle');
        const dismissed = page.locator('#dismissed-table tr', { hasText: DOC_LABEL });
        await expect(dismissed).toBeVisible();
        await expect(dismissed).toContainText('Pour moi');

        await dismissed.locator('.alarm-reactivate-btn').click();
        await expect(page.locator('.alert-success.alert-dismissible')).toContainText(DOC_LABEL);
        await expect(page.locator('#alarms-table tr', { hasText: DOC_LABEL })).toHaveCount(1);
        expect(await page.textContent('body')).not.toMatch(/PHP Error|Fatal error|A Database Error/);
    });

    test('un administrateur désactive une alarme pour tout le monde et la réactive depuis l\'administration', async ({ page }) => {
        await login(page, ADMIN);
        await page.goto(`/index.php/mes_alarmes?membre=${SUPERVISOR.username}&section=`);
        await openDismiss(page);
        await page.check('#dismiss-scope-all');
        await page.check('#dismiss-until');
        const until = new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10);
        await page.fill('#dismiss-until-date', until);
        await page.fill('#dismiss-reason', 'Licencié dans un autre club');
        await page.click('#dismiss-submit');
        await expect(page.locator('.alert-success.alert-dismissible')).toContainText('tout le monde');
        await expect(page).toHaveURL(new RegExp('membre=' + SUPERVISOR.username));
        await expect(page.locator('#alarms-table tr', { hasText: DOC_LABEL })).toHaveCount(0);

        // Le superviseur ne la voit plus, elle est signalée comme désactivée pour tout le monde
        await page.goto('/index.php/auth/logout');
        await login(page, SUPERVISOR);
        await page.goto('/index.php/mes_alarmes?section=');
        await expect(page.locator('#alarms-table tr', { hasText: DOC_LABEL })).toHaveCount(0);
        await page.click('#alarm-dismissed-toggle');
        const dismissed = page.locator('#dismissed-table tr', { hasText: DOC_LABEL });
        await expect(dismissed).toContainText('Tout le monde');
        await expect(dismissed).toContainText('Licencié dans un autre club');
        await expect(dismissed.locator('.alarm-reactivate-btn')).toHaveCount(0);

        // Administration : liste et réactivation
        await page.goto('/index.php/auth/logout');
        await login(page, ADMIN);
        await page.goto('/index.php/alarmes_admin');
        const adminRow = page.locator('#dismissals-table tr', { hasText: DOC_LABEL });
        await expect(adminRow).toContainText('Tout le monde');
        await adminRow.locator('.alarm-reactivate-btn').click();
        await expect(page.locator('.alert-success.alert-dismissible')).toContainText(DOC_LABEL);
        await expect(page.locator('#dismissals-table tr', { hasText: DOC_LABEL })).toHaveCount(0);
        expect(await page.textContent('body')).not.toMatch(/PHP Error|Fatal error|A Database Error/);
    });
});
