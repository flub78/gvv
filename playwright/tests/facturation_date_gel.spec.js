/**
 * Date de gel et facturation générée (vols, achats) — smoke test de l'interface.
 *
 * - Un vol avion ou planeur facturé dans une période clôturée s'ouvre en lecture seule
 *   avec un bandeau explicatif (aucune donnée modifiée).
 * - Un achat daté dans une période clôturée est refusé par le formulaire.
 *
 * Les tests sont en lecture seule sur les données existantes. Si le refus de l'achat
 * échouait, l'achat créé et son écriture sont supprimés en SQL (soldes rétablis), car
 * l'application refuse de supprimer une écriture clôturée.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/facturation_date_gel.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

const DB_CONFIG = {
    host: 'localhost',
    user: 'gvv_user',
    password: 'lfoyfgbj',
    database: 'gvv2',
};

const SECTION_ID = 1;
const LOCKED_TEXT = 'Facturation verrouillée';

async function login(page, username = 'testadmin') {
    await page.goto('/index.php/auth/logout');
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', 'password');
    const sectionSelect = page.locator('select[name="section"]');
    if (await sectionSelect.count() > 0) {
        await sectionSelect.selectOption(String(SECTION_ID));
    }
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
    if (page.url().includes('auth/login')) {
        throw new Error('Login failed - still on login page');
    }
}

function dbToFr(db) {
    const [y, m, d] = db.split('-');
    return `${d}/${m}/${y}`;
}

async function enabledSubmitCount(page) {
    return page.locator('#body form input[type="submit"]:not([disabled]), #body form button[type="submit"]:not([disabled])').count();
}

test.describe('Facturation et date de gel', () => {
    test.describe.configure({ mode: 'serial' });

    let connection;
    let freezeDate;

    test.beforeAll(async () => {
        connection = await mysql.createConnection(DB_CONFIG);
        const [rows] = await connection.query(
            "SELECT DATE_FORMAT(date, '%Y-%m-%d') AS d FROM clotures WHERE section = ? ORDER BY date DESC, id DESC LIMIT 1",
            [SECTION_ID]);
        freezeDate = rows.length ? rows[0].d : null;
    });

    test.afterAll(async () => {
        if (connection) await connection.end();
    });

    for (const { label, url, table, key, field } of [
        { label: 'avion', url: 'vols_avion', table: 'volsa', key: 'vaid', field: 'vol_avion' },
        { label: 'planeur', url: 'vols_planeur', table: 'volsp', key: 'vpid', field: 'vol_planeur' },
    ]) {
        test(`un vol ${label} facturé dans une période clôturée s'ouvre en lecture seule`, async ({ page }) => {
            test.skip(!freezeDate, `Pas de date de gel pour la section ${SECTION_ID}`);
            const [rows] = await connection.query(
                `SELECT v.${key} AS id FROM ${table} v JOIN achats a ON a.${field} = v.${key}
                 JOIN ecritures e ON e.achat = a.id
                 WHERE e.club = ? AND e.date_op <= ? ORDER BY e.date_op DESC LIMIT 1`,
                [SECTION_ID, freezeDate]);
            test.skip(rows.length === 0, `Aucun vol ${label} facturé dans la période clôturée`);

            await login(page);
            await page.goto(`/index.php/${url}/edit/${rows[0].id}`);
            await page.waitForLoadState('networkidle');
            await expect(page.locator('.alert-warning')).toContainText(LOCKED_TEXT);
            expect(await enabledSubmitCount(page)).toBe(0);
        });
    }

    test('un vol avion facturé dans la période ouverte reste modifiable', async ({ page }) => {
        test.skip(!freezeDate, `Pas de date de gel pour la section ${SECTION_ID}`);
        const [rows] = await connection.query(
            `SELECT v.vaid AS id FROM volsa v JOIN achats a ON a.vol_avion = v.vaid
             JOIN ecritures e ON e.achat = a.id
             WHERE e.club = ? AND e.date_op > ? AND e.gel = 0
               AND NOT EXISTS (SELECT 1 FROM achats a2 JOIN ecritures e2 ON e2.achat = a2.id
                               WHERE a2.vol_avion = v.vaid AND e2.gel != 0)
             ORDER BY e.date_op DESC LIMIT 1`,
            [SECTION_ID, freezeDate]);
        test.skip(rows.length === 0, 'Aucun vol avion facturé dans la période ouverte');

        await login(page);
        await page.goto(`/index.php/vols_avion/edit/${rows[0].id}`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toContainText(LOCKED_TEXT);
        expect(await enabledSubmitCount(page)).toBeGreaterThan(0);
    });

    test('le propriétaire non planchiste d\'un vol planeur le consulte en lecture seule', async ({ page }) => {
        // abraracourcix (CA, instructeur) n'a pas le droit de saisir les vols planeur
        const [rows] = await connection.query(
            `SELECT vpid FROM volsp WHERE vppilid = 'abraracourcix' ORDER BY vpdate DESC LIMIT 1`);
        test.skip(rows.length === 0, 'Aucun vol planeur pour abraracourcix');

        await login(page, 'abraracourcix');
        await page.goto(`/index.php/vols_planeur/edit/${rows[0].vpid}`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toContainText('Vous ne pouvez pas');
        await expect(page.locator('body')).not.toContainText('Fatal error');
        await expect(page.locator('#body form')).toHaveCount(1);
        expect(await enabledSubmitCount(page)).toBe(0);
    });

    test('un achat daté dans une période clôturée est refusé', async ({ page }) => {
        test.skip(!freezeDate, `Pas de date de gel pour la section ${SECTION_ID}`);
        const [[{ now }]] = await connection.query('SELECT NOW() AS now');
        const createdSince = ['SELECT id FROM achats WHERE date = ? AND created_at >= ?', [freezeDate, now]];

        try {
            await login(page);
            await page.goto('/index.php/achats/create');
            await page.waitForLoadState('networkidle');

            await page.fill('input[name="date"]', dbToFr(freezeDate));
            await page.evaluate(() => {
                for (const name of ['produit', 'pilote']) {
                    const select = document.querySelector(`select[name="${name}"]`);
                    const option = Array.from(select.options).find(o => o.value !== '');
                    $(select).val(option.value).trigger('change');
                }
            });
            await page.fill('input[name="quantite"]', '1');
            await page.click('#validate');
            await page.waitForLoadState('networkidle');

            await expect(page.locator('body')).toContainText(`Date antérieure ou égale au ${dbToFr(freezeDate)}`);
            const [created] = await connection.query(...createdSince);
            expect(created.length).toBe(0);
        } finally {
            // Filet de sécurité si le refus a échoué : suppression SQL avec rétablissement des soldes
            const [achats] = await connection.query(...createdSince);
            for (const { id } of achats) {
                const [ecritures] = await connection.query(
                    'SELECT id, compte1, compte2, montant FROM ecritures WHERE achat = ?', [id]);
                for (const e of ecritures) {
                    await connection.query('UPDATE comptes SET debit = debit - ? WHERE id = ?', [e.montant, e.compte1]);
                    await connection.query('UPDATE comptes SET credit = credit - ? WHERE id = ?', [e.montant, e.compte2]);
                    await connection.query('DELETE FROM ecritures WHERE id = ?', [e.id]);
                }
                await connection.query('DELETE FROM tickets WHERE achat = ?', [id]);
                await connection.query('DELETE FROM achats WHERE id = ?', [id]);
            }
        }
    });
});
