/**
 * Playwright smoke tests – Alarmes, Lot 1 (page « Mes alarmes »)
 *
 * Verifies:
 *   1. A plain member sees his own alarms without any subscription and has no
 *      subscription matrix; saving channels is confirmed
 *   2. A supervisor (CA of the Avion section only) gets a matrix limited to the
 *      Avion column; a cell can be ticked without any channel; once subscribed,
 *      the list shows only pilots active in the Avion section and the dashboard
 *      card shows the number of active alarms
 *   3. A club-admin sees the member selector positioned on himself, configures
 *      another member, who then sees the cell ticked with its author
 *   4. A club-admin adds and deletes a category subscription
 *   5. A plain member cannot open the administration; the scheduler URL answers
 *      403 with a wrong secret
 *   6. The SMS reminder is refused for a member without a mobile number
 *   7. A whole row or column, or the whole matrix, is ticked or unticked in one click
 *
 * Prerequisites: test users asterix (member, Planeur/Général), abraracourcix
 * (CA Avion), goudurix (member Avion) and panoramix (club-admin), migration
 * 177, gestion_documentaire enabled.
 * The test creates its own document type, alarm and documents; every
 * subscription, setting and document created here is removed in afterAll.
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const MEMBER = { username: 'asterix', password: 'password' };
const SUPERVISOR = { username: 'abraracourcix', password: 'password' };
const AVION_PILOT = 'goudurix';
const ADMIN = { username: 'panoramix', password: 'password' };
const TYPE_CODE = 'zz_alarm_smoke';

let avionId;
let memberMobile;
let defId;
let missingDefId;

async function login(page, user) {
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function cleanup(db) {
    const logins = [MEMBER.username, SUPERVISOR.username, ADMIN.username];
    await db.query(`DELETE ad FROM archived_documents ad JOIN document_types dt ON dt.id = ad.document_type_id
                    WHERE dt.code = ?`, [TYPE_CODE]);
    await db.query('DELETE FROM document_types WHERE code = ?', [TYPE_CODE]); // alarme et abonnements en cascade
    await db.query('DELETE FROM alarm_subscriptions WHERE subscriber_login IN (?)', [logins]);
    await db.query('DELETE FROM alarm_subscriptions WHERE subscriber_login IS NULL AND created_by IN (?)', [logins]);
    await db.query('DELETE FROM alarm_member_settings WHERE member_login IN (?)', [logins]);
}

async function addExpiredDocument(db, typeId, pilot) {
    await db.query(
        `INSERT INTO archived_documents (document_type_id, pilot_login, file_path, original_filename, description,
             uploaded_by, uploaded_at, valid_until, alarm_disabled, is_current_version, validation_status)
         VALUES (?, ?, 'uploads/documents/test/zz.pdf', 'zz.pdf', ?, ?, NOW(), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 0, 1, 'approved')`,
        [typeId, pilot, `zz-smoke-${pilot}`, pilot]);
}

test.describe('Alarmes – Lot 1', () => {
    // Les tests modifient la configuration des mêmes membres : exécution en série
    test.describe.configure({ mode: 'serial' });

    test.beforeAll(async () => {
        const db = await mysql.createConnection(DB_CONFIG);
        try {
            await cleanup(db);
            const [sections] = await db.query("SELECT id FROM sections WHERE nom = 'Avion'");
            avionId = sections[0].id;
            const [type] = await db.query(
                `INSERT INTO document_types (code, name, scope, required, has_expiration, alert_days_before, alarm_enabled, active)
                 VALUES (?, 'Type alarme smoke', 'pilot', 0, 1, 30, 1, 1)`, [TYPE_CODE]);
            const [def] = await db.query(
                `INSERT INTO alarm_definitions (code, label, source, document_type_id, lead_days, active)
                 VALUES (?, 'Type alarme smoke', 'document', ?, 30, 1)`, [`document_${type.insertId}`, type.insertId]);
            defId = def.insertId;
            const [missing] = await db.query("SELECT id FROM alarm_definitions WHERE code = 'missing_document'");
            missingDefId = missing[0].id;
            const [member] = await db.query('SELECT mtelm FROM membres WHERE mlogin = ?', [MEMBER.username]);
            memberMobile = member[0].mtelm;
            await addExpiredDocument(db, type.insertId, MEMBER.username);  // actif en Planeur et Général
            await addExpiredDocument(db, type.insertId, AVION_PILOT);      // actif en Avion
        } finally {
            await db.end();
        }
    });

    test.afterAll(async () => {
        const db = await mysql.createConnection(DB_CONFIG);
        try {
            await cleanup(db);
            await db.query('UPDATE membres SET mtelm = ? WHERE mlogin = ?', [memberMobile, MEMBER.username]);
        } finally {
            await db.end();
        }
    });

    test('le rappel SMS est refusé sans numéro de mobile', async ({ page }) => {
        const db = await mysql.createConnection(DB_CONFIG);
        try {
            await db.query("UPDATE membres SET mtelm = '' WHERE mlogin = ?", [MEMBER.username]);
            await login(page, MEMBER);
            await page.goto('/index.php/mes_alarmes');
            await expect(page.locator('#sms')).toBeDisabled();

            // Envoi forcé de l'option : refus explicite, rien n'est enregistré
            const response = await page.request.post('/index.php/mes_alarmes/save',
                { form: { sms: '1', channel_email: '1', frequency: 'weekly' } });
            const html = await response.text(); // page après redirection, avec le message flash
            expect(html).toContain('alert-danger');
            expect(html).toMatch(/mobile/);
            const [rows] = await db.query('SELECT sms FROM alarm_member_settings WHERE member_login = ?', [MEMBER.username]);
            expect(rows.length === 0 || rows[0].sms === 0).toBeTruthy();
        } finally {
            await db.query('UPDATE membres SET mtelm = ? WHERE mlogin = ?', [memberMobile, MEMBER.username]);
            await db.end();
        }
    });

    test('un simple membre voit ses propres alarmes sans abonnement', async ({ page }) => {
        await login(page, MEMBER);
        await page.goto('/index.php/mes_alarmes');

        await expect(page.locator('h3')).toBeVisible();
        await expect(page.locator('#alarm-matrix')).toHaveCount(0);
        const row = page.locator('#alarms-table tr', { hasText: `zz-smoke-${MEMBER.username}` });
        await expect(row).toHaveCount(1);
        await expect(row.locator('.badge.bg-primary')).toBeVisible();
        await expect(page.locator('#alarms-table')).not.toContainText(`zz-smoke-${AVION_PILOT}`);

        // Réglages sans abonnement : pas de canal exigé, confirmation
        await page.check('#channel_email');
        await page.click('#configuration button[type="submit"]');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('#channel_email')).toBeChecked();
        expect(await page.textContent('body')).not.toMatch(/PHP Error|Fatal error|A Database Error/);
    });

    test('un superviseur s\'abonne par section et ne voit que les pilotes de cette section', async ({ page }) => {
        await login(page, SUPERVISOR);
        await page.goto('/index.php/mes_alarmes');

        const headers = page.locator('#alarm-matrix thead th');
        await expect(headers).toContainText(['Avion']);
        await expect(page.locator('#alarm-matrix thead')).not.toContainText('Planeur');
        await expect(page.locator('#alarms-table')).toHaveCount(0);

        // Case cochée sans canal : enregistrée
        await page.locator(`#cell_${defId}_${avionId}`).check();
        await page.click('#configuration button[type="submit"]');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator(`#cell_${defId}_${avionId}`)).toBeChecked();
        await expect(page.locator('#channel_email')).not.toBeChecked();

        // Seuls les pilotes actifs en section Avion
        await expect(page.locator('#alarms-table')).toContainText(`zz-smoke-${AVION_PILOT}`);
        await expect(page.locator('#alarms-table')).not.toContainText(`zz-smoke-${MEMBER.username}`);
        const activeRows = await page.locator('#alarms-table tbody tr.table-danger').count();

        // Carte du tableau de bord : nombre d'alarmes actives
        await page.goto('/index.php/welcome/section/user');
        await expect(page.locator('#card-mes-alarmes .badge-corner')).toHaveText(String(activeRows));
    });

    test('un administrateur configure les alarmes d\'un autre membre', async ({ page }) => {
        await login(page, ADMIN);
        await page.goto('/index.php/mes_alarmes');

        // Sélecteur positionné sur son propre profil ; toutes les sections
        await expect(page.locator('#membre')).toHaveValue(ADMIN.username);
        await expect(page.locator('#alarm-matrix thead')).toContainText('Planeur');
        await expect(page.locator('#alarm-matrix thead')).toContainText('Avion');

        // Configuration d'un autre membre : sa matrice, ses alarmes
        await page.selectOption('#membre', SUPERVISOR.username);
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(new RegExp('membre=' + SUPERVISOR.username));
        await expect(page.locator('#alarm-matrix thead')).not.toContainText('Planeur');
        await expect(page.locator(`#cell_${defId}_${avionId}`)).toBeChecked();
        await page.locator(`#cell_${missingDefId}_${avionId}`).check();
        await page.click('#configuration button[type="submit"]');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page).toHaveURL(new RegExp('membre=' + SUPERVISOR.username));
        await expect(page.locator(`#cell_${missingDefId}_${avionId}`)).toBeChecked();

        // Le membre voit la case cochée et son auteur
        await page.goto('/index.php/auth/logout');
        await login(page, SUPERVISOR);
        await page.goto('/index.php/mes_alarmes');
        await expect(page.locator(`#cell_${missingDefId}_${avionId}`)).toBeChecked();
        const origin = page.locator(`#cell_${missingDefId}_${avionId} ~ i.fa-info-circle`);
        await expect(origin).toHaveAttribute('title', /anoramix/i);
    });

    test('une ligne ou une colonne entière se coche ou se décoche en un clic', async ({ page }) => {
        await login(page, ADMIN);
        await page.goto('/index.php/mes_alarmes');

        const column = page.locator(`.matrix-cell[data-cell="${avionId}"]:not([disabled])`);
        const columnToggle = page.locator(`#col_toggle_${avionId}`);
        expect(await column.count()).toBeGreaterThan(1);
        await columnToggle.check();
        for (const box of await column.all()) {
            await expect(box).toBeChecked();
        }
        await columnToggle.uncheck();
        for (const box of await column.all()) {
            await expect(box).not.toBeChecked();
        }

        const row = page.locator(`.matrix-cell[data-alarm="${defId}"]:not([disabled])`);
        const rowToggle = page.locator(`#row_toggle_${defId}`);
        expect(await row.count()).toBeGreaterThan(1);
        await rowToggle.check();
        for (const box of await row.all()) {
            await expect(box).toBeChecked();
        }
        // Une case décochée : la tête de ligne devient indéterminée ; un clic recoche tout
        await row.first().uncheck();
        expect(await rowToggle.evaluate(el => el.indeterminate)).toBe(true);
        await rowToggle.click();
        for (const box of await row.all()) {
            await expect(box).toBeChecked();
        }
        await rowToggle.uncheck();
        for (const box of await row.all()) {
            await expect(box).not.toBeChecked();
        }

        // Case du coin : toute la matrice ; les têtes de ligne et de colonne suivent
        const all = page.locator('.matrix-cell:not([disabled])');
        await page.locator('#all_toggle').check();
        for (const box of await all.all()) {
            await expect(box).toBeChecked();
        }
        await expect(rowToggle).toBeChecked();
        await expect(columnToggle).toBeChecked();
        await page.locator('#all_toggle').uncheck();
        for (const box of await all.all()) {
            await expect(box).not.toBeChecked();
        }
        await expect(columnToggle).not.toBeChecked();
    });

    test('un administrateur gère les abonnements de catégories', async ({ page }) => {
        await login(page, ADMIN);
        await page.goto('/index.php/alarmes_admin');
        await expect(page.locator('h3')).toBeVisible();

        await page.selectOption('#role_id', { label: 'Instructeur' });
        await page.selectOption('#section_id', String(avionId));
        await page.click('#add-category-form button[type="submit"]');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('#categories-table')).toContainText('Instructeur');

        await page.locator('#categories-table tr', { hasText: 'Instructeur' }).filter({ hasText: 'Avion' })
            .locator('button[type="submit"]').first().click();
        await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('un simple membre n\'accède pas à l\'administration', async ({ page }) => {
        await login(page, MEMBER);
        const response = await page.goto('/index.php/alarmes_admin');
        expect(response.status()).toBe(403);
    });

    test('le déclencheur refuse un mauvais secret', async ({ request }) => {
        const response = await request.get('/index.php/alarm_scheduler/run/mauvais-secret');
        expect(response.status()).toBe(403);
    });
});
