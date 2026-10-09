/**
 * Smoke test : cases à cocher de la fiche membre
 *  - "Pilote extérieur" (membres.ext) s'enregistre cochée puis décochée ;
 *  - un pilote extérieur n'apparaît pas dans le contrôle des vols sans cotisation,
 *    un pilote du club sans cotisation y apparaît ;
 *  - "Exempté du contrôle de solde" (membres.exemption_solde) s'enregistre cochée
 *    puis décochée (régression : le décochage n'était pas enregistré).
 *
 * La fiche du membre de test est sauvegardée puis restaurée à l'identique,
 * le vol de test (année 2099) est supprimé, y compris en cas d'échec.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/membre-checkboxes-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const MEMBER = 'asterix';
const YEAR = 2099;

async function login(page, user) {
    await page.goto('/index.php/auth/logout');
    await page.waitForLoadState('networkidle');
    await page.goto('/index.php/auth/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function saveCheckbox(page, id, checked) {
    await page.goto(`/index.php/membre/edit/${MEMBER}`);
    await page.waitForLoadState('networkidle');
    await page.locator(`#${id}`).setChecked(checked);
    await page.click('input[name="button"]');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).not.toContainText('A PHP Error was encountered');
}

test.describe('Fiche membre : cases à cocher', () => {
    let db;
    let snapshot;
    let volId;

    test.beforeAll(async () => {
        db = await mysql.createConnection(DB_CONFIG);
        const [rows] = await db.query('SELECT * FROM membres WHERE mlogin = ?', [MEMBER]);
        snapshot = rows[0];
    });

    test.afterAll(async () => {
        if (volId) {
            await db.query('DELETE FROM volsp WHERE vpid = ?', [volId]);
        }
        if (snapshot) {
            await db.query('UPDATE membres SET ? WHERE mlogin = ?', [snapshot, MEMBER]);
        }
        await db.end();
    });

    test('la case est enregistrée et exclut le pilote du contrôle', async ({ page }) => {
        test.skip(!snapshot, `Membre de test ${MEMBER} absent`);
        const [res] = await db.query(
            "INSERT INTO volsp (vpdate, vppilid, vpmacid, vpcdeb, vpcfin, vpduree, vpdc, vpcategorie, vpticcolle) " +
            "VALUES (?, ?, 'TEST', 10, 11, 60, 0, 0, 0)", [`${YEAR}-06-01`, MEMBER]);
        volId = res.insertId;

        await login(page, ADMIN_USER);
        const row = page.locator(`table tbody tr:has(td:text-is("${MEMBER}"))`);

        await saveCheckbox(page, 'ext', true);
        let [rows] = await db.query('SELECT ext FROM membres WHERE mlogin = ?', [MEMBER]);
        expect(rows[0].ext).toBe(1);
        await page.goto(`/index.php/membre/edit/${MEMBER}`);
        await expect(page.locator('#ext')).toBeChecked();
        await page.goto(`/index.php/licences/vols_sans_cotisation/${YEAR}`);
        await expect(row).toHaveCount(0);

        await saveCheckbox(page, 'ext', false);
        [rows] = await db.query('SELECT ext FROM membres WHERE mlogin = ?', [MEMBER]);
        expect(rows[0].ext).toBe(0);
        await page.goto(`/index.php/licences/vols_sans_cotisation/${YEAR}`);
        await expect(row).toHaveCount(1);
    });

    test('le décochage de l\'exemption de solde est enregistré', async ({ page }) => {
        test.skip(!snapshot, `Membre de test ${MEMBER} absent`);
        await login(page, ADMIN_USER);

        await saveCheckbox(page, 'exemption_solde', true);
        let [rows] = await db.query('SELECT exemption_solde FROM membres WHERE mlogin = ?', [MEMBER]);
        expect(rows[0].exemption_solde).toBe(1);

        await saveCheckbox(page, 'exemption_solde', false);
        [rows] = await db.query('SELECT exemption_solde FROM membres WHERE mlogin = ?', [MEMBER]);
        expect(rows[0].exemption_solde).toBe(0);
    });
});
