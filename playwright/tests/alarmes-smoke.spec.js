/**
 * Playwright smoke tests – Alarmes, Lot 1 (page « Mes alarmes »)
 *
 * Verifies:
 *   1. A plain member sees his own alarms without any subscription and has no
 *      subscription matrix; saving channels is confirmed
 *   2. A supervisor (CA of the Avion section only) gets a matrix limited to the
 *      Avion column; a cell can be ticked without any channel; once subscribed,
 *      the list shows only pilots active in the Avion section; with another
 *      active section, the Avion alarms are hidden behind a notice and a link
 *      to all sections, and the dashboard card gives the total in its tooltip
 *   3. A club-admin sees the member selector positioned on himself, configures
 *      another member, who then sees the cell ticked with its author
 *   4. A club-admin adds and deletes a category subscription
 *   5. A plain member cannot open the administration; the scheduler URL answers
 *      403 with a wrong secret
 *   6. The SMS reminder is refused for a member without a mobile number
 *   7. A whole row or column, or the whole matrix, is ticked or unticked in one click
 *   8. Lot 2: a supervisor subscribed to "vols sans licence" in the Avion column
 *      sees a pilot who flew in avion this year without licence
 *   9. Lot 3: the CA sees the recent-experience alarms but not "Compte débiteur",
 *      which the club-admin sees
 *
 *  10. Lot 4: a supervisor disables an alarm for himself, sees it among the
 *      disabled alarms and re-enables it; a club-admin disables it for
 *      everyone with a reason on the supervisor's page, then re-enables it
 *      from the administration
 *
 * All tests run serially in one worker: they share the test users' subscriptions.
 *
 * Prerequisites: test users asterix (member, Planeur/Général), abraracourcix
 * (CA Avion), goudurix (member Avion) and panoramix (club-admin), migration
 * 177 to 180, gestion_documentaire enabled.
 * The test creates its own document type, alarm and documents; every
 * subscription, setting and document created here is removed in afterAll.
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

// Tous les tests de ce fichier modifient les abonnements des mêmes membres de test
test.describe.configure({ mode: 'serial' });

const MEMBER = { username: 'asterix', password: 'password' };
const SUPERVISOR = { username: 'abraracourcix', password: 'password' };
const AVION_PILOT = 'goudurix';
const ADMIN = { username: 'panoramix', password: 'password' };
const TYPE_CODE = 'zz_alarm_smoke';
const FLIGHT_PILOT = 'zzpwlot2';   // pilote fictif actif en avion : vol avion de l'année sans cotisation ni licence

let avionId;
let otherSectionId;
let memberMobile;
let defId;
let missingDefId;
let licenceDefId;
let savedActive = [];   // activation des alarmes testées (réglage du catalogue), restaurée en fin de test
const TESTED_ALARMS = ['computed_vols_sans_licence', 'computed_emport_passager', 'computed_inactivite', 'computed_remorquages', 'computed_compte_debiteur'];

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
    await db.query('DELETE FROM volsa WHERE vapilid = ?', [FLIGHT_PILOT]);
    await db.query(`DELETE urps FROM user_roles_per_section urps JOIN users u ON u.id = urps.user_id
                    WHERE u.username = ?`, [FLIGHT_PILOT]);
    await db.query('DELETE FROM users WHERE username = ?', [FLIGHT_PILOT]);
    await db.query('DELETE FROM membres WHERE mlogin = ?', [FLIGHT_PILOT]);
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
            // Section active de test : une autre section où le superviseur est membre
            const [other] = await db.query(`SELECT urps.section_id FROM user_roles_per_section urps
                JOIN users u ON u.id = urps.user_id JOIN types_roles tr ON tr.id = urps.types_roles_id
                WHERE u.username = ? AND tr.nom = 'user' AND urps.revoked_at IS NULL AND urps.section_id <> ?
                ORDER BY urps.section_id LIMIT 1`, [SUPERVISOR.username, avionId]);
            otherSectionId = other[0].section_id;
            const [type] = await db.query(
                `INSERT INTO document_types (code, name, scope, required, has_expiration, alert_days_before, alarm_enabled, active)
                 VALUES (?, 'Type alarme smoke', 'pilot', 0, 1, 30, 1, 1)`, [TYPE_CODE]);
            const [def] = await db.query(
                `INSERT INTO alarm_definitions (code, label, source, document_type_id, lead_days, active)
                 VALUES (?, 'Type alarme smoke', 'document', ?, 30, 1)`, [`document_${type.insertId}`, type.insertId]);
            defId = def.insertId;
            const [missing] = await db.query("SELECT id FROM alarm_definitions WHERE code = 'missing_document'");
            missingDefId = missing[0].id;
            [savedActive] = await db.query('SELECT code, active FROM alarm_definitions WHERE code IN (?)', [TESTED_ALARMS]);
            await db.query('UPDATE alarm_definitions SET active = 1 WHERE code IN (?)', [TESTED_ALARMS]);
            const [licence] = await db.query("SELECT id FROM alarm_definitions WHERE code = 'computed_vols_sans_licence'");
            licenceDefId = licence[0].id;
            await db.query(`INSERT INTO membres (mlogin, mnom, mprenom, mnumero, ext) VALUES (?, 'Zzlot', 'Pilote', 0, 0)`, [FLIGHT_PILOT]);
            // Pilote actif en section avion : seuls les pilotes actifs de la section ont des alarmes
            const [user] = await db.query(`INSERT INTO users (username, password, email, last_ip)
                VALUES (?, 'x', 'zzpwlot2@example.invalid', '127.0.0.1')`, [FLIGHT_PILOT]);
            await db.query(`INSERT INTO user_roles_per_section (user_id, types_roles_id, section_id)
                SELECT ?, id, ? FROM types_roles WHERE nom = 'user'`, [user.insertId, avionId]);
            await db.query(
                `INSERT INTO volsa (vadate, vapilid, vamacid, vacdeb, vacfin, vaduree, vadc, vacategorie, vahdeb, vahfin, club)
                 VALUES (CURDATE(), ?, 'ZZTEST', 0, 1, 1, 0, 0, 10, 11, ?)`, [FLIGHT_PILOT, avionId]);
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
            for (const row of savedActive) {
                await db.query('UPDATE alarm_definitions SET active = ? WHERE code = ?', [row.active, row.code]);
            }
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
        await expect(row.locator('.alarm-own')).toBeVisible();
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

        // Section active autre qu'Avion : les alarmes avion sont masquées et signalées
        await page.evaluate(id => updateSection(id), String(otherSectionId));
        await page.waitForLoadState('networkidle');
        await page.goto('/index.php/mes_alarmes');
        await expect(page.locator('#section')).toHaveValue(String(otherSectionId));
        await expect(page.locator('#alarm-hidden-notice')).toBeVisible();
        await expect(page.locator('#alarms-table', { hasText: `zz-smoke-${AVION_PILOT}` })).toHaveCount(0);

        // Toutes les sections : seuls les pilotes actifs en section Avion
        await page.click('#alarm-show-all');
        await expect(page.locator('#section')).toHaveValue('');
        await expect(page.locator('#alarm-hidden-notice')).toHaveCount(0);
        await expect(page.locator('#alarms-table')).toContainText(`zz-smoke-${AVION_PILOT}`);
        await expect(page.locator('#alarms-table')).not.toContainText(`zz-smoke-${MEMBER.username}`);
        const activeRows = await page.locator('#alarms-table tbody tr.table-danger').count();

        // Carte du tableau de bord : alarmes de la section active, total dans l'infobulle
        await page.goto('/index.php/welcome/section/user');
        await expect(page.locator('#card-mes-alarmes .badge-corner'))
            .toHaveAttribute('title', new RegExp(`, ${activeRows} au total`));
    });

    test('un superviseur abonné voit les vols sans licence de sa section (Lot 2)', async ({ page }) => {
        await login(page, SUPERVISOR);
        await page.goto('/index.php/mes_alarmes');
        await expect(page.locator('#alarm-matrix')).toContainText('Vols sans licence');
        // Lot 3 : expérience récente ouverte au CA, compte débiteur réservé au bureau et aux trésoriers
        await expect(page.locator('#alarm-matrix')).toContainText('120 jours sans voler');
        await expect(page.locator('#alarm-matrix')).toContainText('Emport passager');
        await expect(page.locator('#alarm-matrix')).toContainText('Remorquage');
        await expect(page.locator('#alarm-matrix')).not.toContainText('Compte débiteur');
        await page.locator(`#cell_${licenceDefId}_${avionId}`).check();
        await page.click('#configuration button[type="submit"]');
        await expect(page.locator('.alert-success')).toBeVisible();

        await page.goto(`/index.php/mes_alarmes?section=${avionId}`);
        const row = page.locator('#alarms-table tr', { hasText: 'Zzlot' });
        await expect(row).toHaveCount(1);
        await expect(row).toContainText(String(new Date().getFullYear()));
        await expect(row.locator('.alarm-category[data-category="adhesion"]')).toBeVisible();
        await expect(row.locator('a.btn')).toHaveAttribute('href', /licences\/per_year/);
    });

    test('un administrateur configure les alarmes d\'un autre membre', async ({ page }) => {
        await login(page, ADMIN);
        await page.goto('/index.php/mes_alarmes');

        // Sélecteur positionné sur son propre profil ; toutes les sections
        await expect(page.locator('#membre')).toHaveValue(ADMIN.username);
        await expect(page.locator('#alarm-matrix thead')).toContainText('Planeur');
        await expect(page.locator('#alarm-matrix thead')).toContainText('Avion');
        await expect(page.locator('#alarm-matrix')).toContainText('Compte débiteur');
        // Catégories : une ligne d'en-tête par catégorie, dans l'ordre
        const categories = await page.locator('#alarm-matrix tr.alarm-category-row').allTextContents();
        expect(categories.map(t => t.trim())).toEqual(expect.arrayContaining(['Documents', 'Comptes', 'Expérience récente']));
        expect(categories.map(t => t.trim()).indexOf('Comptes')).toBeLessThan(categories.map(t => t.trim()).indexOf('Expérience récente'));

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

// ----------------------------------------------------------------------
// Lot 4 : désactivation des occurrences
// ----------------------------------------------------------------------

const LOT4_TYPE_CODE = 'zz_alarm_lot4';
const LOT4_DOC_LABEL = `zz-lot4-${AVION_PILOT}`;

async function cleanupLot4(db) {
    await db.query(`DELETE ad FROM archived_documents ad JOIN document_types dt ON dt.id = ad.document_type_id
                    WHERE dt.code = ?`, [LOT4_TYPE_CODE]);
    await db.query('DELETE FROM document_types WHERE code = ?', [LOT4_TYPE_CODE]);
}

/** Ouvre la boîte de dialogue de désactivation de la ligne du document de test */
async function openDismiss(page) {
    const row = page.locator('#alarms-table tr', { hasText: LOT4_DOC_LABEL });
    await expect(row).toHaveCount(1);
    await row.locator('.alarm-dismiss-btn').click();
    await expect(page.locator('#dismiss-modal')).toBeVisible();
    await expect(page.locator('#dismiss-label')).toContainText(LOT4_DOC_LABEL);
}

test.describe('Alarmes – Lot 4', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeAll(async () => {
        const db = await mysql.createConnection(DB_CONFIG);
        try {
            await cleanupLot4(db);
            const [sections] = await db.query("SELECT id FROM sections WHERE nom = 'Avion'");
            const avionId = sections[0].id;
            const [type] = await db.query(
                `INSERT INTO document_types (code, name, scope, required, has_expiration, alert_days_before, alarm_enabled, active)
                 VALUES (?, 'Type alarme lot 4', 'pilot', 0, 1, 30, 1, 1)`, [LOT4_TYPE_CODE]);
            const [def] = await db.query(
                `INSERT INTO alarm_definitions (code, label, source, document_type_id, lead_days, active)
                 VALUES (?, 'Type alarme lot 4', 'document', ?, 30, 1)`, [`document_${type.insertId}`, type.insertId]);
            await db.query(`INSERT INTO alarm_subscriptions (alarm_definition_id, subscriber_login, section_id, mandatory)
                            VALUES (?, ?, ?, 0)`, [def.insertId, SUPERVISOR.username, avionId]);
            await db.query(
                `INSERT INTO archived_documents (document_type_id, pilot_login, file_path, original_filename, description,
                     uploaded_by, uploaded_at, valid_until, alarm_disabled, is_current_version, validation_status)
                 VALUES (?, ?, 'uploads/documents/test/zz.pdf', 'zz.pdf', ?, ?, NOW(), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 0, 1, 'approved')`,
                [type.insertId, AVION_PILOT, LOT4_DOC_LABEL, AVION_PILOT]);
        } finally {
            await db.end();
        }
    });

    test.afterAll(async () => {
        const db = await mysql.createConnection(DB_CONFIG);
        try {
            await cleanupLot4(db);
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

        await expect(page.locator('.alert-success.alert-dismissible')).toContainText(LOT4_DOC_LABEL);
        await expect(page.locator('#alarms-table tr', { hasText: LOT4_DOC_LABEL })).toHaveCount(0);
        await expect(page.locator('#alarm-dismissed-notice')).toBeVisible();
        await page.click('#alarm-dismissed-toggle');
        const dismissed = page.locator('#dismissed-table tr', { hasText: LOT4_DOC_LABEL });
        await expect(dismissed).toBeVisible();
        await expect(dismissed).toContainText('Pour moi');

        await dismissed.locator('.alarm-reactivate-btn').click();
        await expect(page.locator('.alert-success.alert-dismissible')).toContainText(LOT4_DOC_LABEL);
        await expect(page.locator('#alarms-table tr', { hasText: LOT4_DOC_LABEL })).toHaveCount(1);
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
        await expect(page.locator('#alarms-table tr', { hasText: LOT4_DOC_LABEL })).toHaveCount(0);

        // Le superviseur ne la voit plus, elle est signalée comme désactivée pour tout le monde
        await page.goto('/index.php/auth/logout');
        await login(page, SUPERVISOR);
        await page.goto('/index.php/mes_alarmes?section=');
        await expect(page.locator('#alarms-table tr', { hasText: LOT4_DOC_LABEL })).toHaveCount(0);
        await page.click('#alarm-dismissed-toggle');
        const dismissed = page.locator('#dismissed-table tr', { hasText: LOT4_DOC_LABEL });
        await expect(dismissed).toContainText('Tout le monde');
        await expect(dismissed).toContainText('Licencié dans un autre club');
        await expect(dismissed.locator('.alarm-reactivate-btn')).toHaveCount(0);

        // Administration : liste et réactivation
        await page.goto('/index.php/auth/logout');
        await login(page, ADMIN);
        await page.goto('/index.php/alarmes_admin');
        const adminRow = page.locator('#dismissals-table tr', { hasText: LOT4_DOC_LABEL });
        await expect(adminRow).toContainText('Tout le monde');
        await adminRow.locator('.alarm-reactivate-btn').click();
        await expect(page.locator('.alert-success.alert-dismissible')).toContainText(LOT4_DOC_LABEL);
        await expect(page.locator('#dismissals-table tr', { hasText: LOT4_DOC_LABEL })).toHaveCount(0);
        expect(await page.textContent('body')).not.toMatch(/PHP Error|Fatal error|A Database Error/);
    });
});
