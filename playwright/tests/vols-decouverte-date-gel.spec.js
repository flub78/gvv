/**
 * Vols de découverte et date de gel : la date de vente (date comptable du bon et
 * du débit éventuel) ne peut ni être dans une période clôturée à la création, ni
 * y entrer, ni en sortir. Les autres modifications d'un bon vendu avant la date
 * de gel restent permises (le vol d'un bon vendu l'an dernier doit pouvoir être saisi).
 *
 * Nettoyage : les bons créés sont supprimés avec leur débit éventuel (helpers/vdCleanup) ;
 * le bon clôturé existant utilisé pour le cas accepté est restauré à l'identique.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/vols-decouverte-date-gel.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { deleteTestVds } = require('./helpers/vdCleanup');

const DB_CONFIG = {
    host: 'localhost',
    user: 'gvv_user',
    password: 'lfoyfgbj',
    database: 'gvv2',
};

const SECTION_ID = 1;
const CLOSED_TEXT = 'antérieure ou égale à la date de gel';
const FROM_CLOSED_TEXT = 'vendu dans une période clôturée';

function dbToFr(db) {
    const [y, m, d] = db.split('-');
    return `${d}/${m}/${y}`;
}

function today() {
    const d = new Date();
    return [d.getFullYear(), d.getMonth() + 1, d.getDate()]
        .map((n, i) => String(n).padStart(i ? 2 : 4, '0')).join('-');
}

async function login(page, username) {
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
        throw new Error(`Login failed for ${username}`);
    }
}

async function fillCreateForm(page, beneficiaire, dateVente) {
    await page.goto('/index.php/vols_decouverte/create');
    await page.waitForLoadState('networkidle');
    const product = await page.evaluate(() => {
        const option = Array.from(document.querySelectorAll('select[name="product"] option')).find(o => o.value !== '');
        return option ? option.value : null;
    });
    expect(product, 'produit vol de découverte disponible').toBeTruthy();
    await page.selectOption('select[name="product"]', product);
    await page.fill('input[name="date_vente"]', dbToFr(dateVente));
    await page.fill('input[name="beneficiaire"]', beneficiaire);
    await page.fill('input[name="de_la_part"]', 'Playwright');
    await page.fill('input[name="beneficiaire_email"]', `pw-vd-gel-${Date.now()}@example.test`);
    await page.fill('input[name="urgence"]', '0600000000');
}

async function submitEdit(page, id, fields) {
    await page.goto(`/index.php/vols_decouverte/edit/${id}`);
    await page.waitForLoadState('networkidle');
    for (const [name, value] of Object.entries(fields)) {
        await page.fill(`input[name="${name}"]`, value);
    }
    await page.click('#validate');
    await page.waitForLoadState('networkidle');
}

test.describe('Vols de découverte et date de gel', () => {
    test.describe.configure({ mode: 'serial' });

    let connection;
    let freezeDate;
    const createdBeneficiaires = [];

    async function vdRows(beneficiaire) {
        const [rows] = await connection.query(
            "SELECT id, DATE_FORMAT(date_vente, '%Y-%m-%d') AS date_vente FROM vols_decouverte WHERE beneficiaire = ?",
            [beneficiaire]);
        return rows;
    }

    test.beforeAll(async () => {
        connection = await mysql.createConnection(DB_CONFIG);
        const [rows] = await connection.query(
            "SELECT DATE_FORMAT(date, '%Y-%m-%d') AS d FROM clotures WHERE section = ? ORDER BY date DESC, id DESC LIMIT 1",
            [SECTION_ID]);
        freezeDate = rows.length ? rows[0].d : null;
    });

    test.afterAll(async () => {
        await deleteTestVds(createdBeneficiaires);
        if (connection) await connection.end();
    });

    test('création refusée avec une date de vente dans la période clôturée', async ({ page }) => {
        test.skip(!freezeDate, `Pas de date de gel pour la section ${SECTION_ID}`);
        const beneficiaire = `PW VD GEL CREER ${Date.now()}`;
        createdBeneficiaires.push(beneficiaire);

        await login(page, 'testadmin');
        await fillCreateForm(page, beneficiaire, freezeDate);
        await page.click('#validate');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText(CLOSED_TEXT);
        expect(await vdRows(beneficiaire)).toHaveLength(0);
    });

    test('création et débit refusés avec une date de vente dans la période clôturée', async ({ page }) => {
        test.skip(!freezeDate, `Pas de date de gel pour la section ${SECTION_ID}`);
        const beneficiaire = `PW VD GEL DEBIT ${Date.now()}`;
        createdBeneficiaires.push(beneficiaire);

        await login(page, 'agecanonix');
        await fillCreateForm(page, beneficiaire, freezeDate);
        await page.click('button[name="button"][value="create_and_debit"]');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText(CLOSED_TEXT);
        expect(await vdRows(beneficiaire)).toHaveLength(0);
    });

    test('bon de la période ouverte : modifiable, mais sa date ne peut pas entrer dans la période clôturée', async ({ page }) => {
        test.skip(!freezeDate, `Pas de date de gel pour la section ${SECTION_ID}`);
        test.skip(today() <= freezeDate, 'La date du jour doit être postérieure à la date de gel');
        const beneficiaire = `PW VD GEL OUVERT ${Date.now()}`;
        createdBeneficiaires.push(beneficiaire);

        await login(page, 'testadmin');
        await fillCreateForm(page, beneficiaire, today());
        await page.click('#validate');
        await page.waitForLoadState('networkidle');
        const created = await vdRows(beneficiaire);
        expect(created).toHaveLength(1);
        const id = created[0].id;

        // Rejeté : date de vente déplacée dans la période clôturée
        await submitEdit(page, id, { date_vente: dbToFr(freezeDate) });
        await expect(page.locator('body')).toContainText(CLOSED_TEXT);
        expect((await vdRows(beneficiaire))[0].date_vente).toBe(today());

        // Accepté : autre champ modifié, date inchangée
        const renamed = `${beneficiaire} MAJ`;
        createdBeneficiaires.push(renamed);
        await submitEdit(page, id, { beneficiaire: renamed });
        await expect(page.locator('body')).not.toContainText(CLOSED_TEXT);
        expect(await vdRows(renamed)).toHaveLength(1);
    });

    test('bon vendu dans la période clôturée : sa date ne peut pas en sortir, le reste reste modifiable', async ({ page }) => {
        test.skip(!freezeDate, `Pas de date de gel pour la section ${SECTION_ID}`);
        test.skip(today() <= freezeDate, 'La date du jour doit être postérieure à la date de gel');
        const [rows] = await connection.query(
            `SELECT * FROM vols_decouverte WHERE club = ? AND date_vente <= ? AND (cancelled = 0 OR cancelled IS NULL)
             ORDER BY date_vente DESC LIMIT 1`, [SECTION_ID, freezeDate]);
        test.skip(rows.length === 0, 'Aucun bon vendu dans la période clôturée');
        const original = rows[0];
        const id = original.id;

        try {
            await login(page, 'testadmin');

            // Rejeté : date de vente sortie de la période clôturée
            await submitEdit(page, id, { date_vente: dbToFr(today()) });
            await expect(page.locator('body')).toContainText(FROM_CLOSED_TEXT);
            const [[after]] = await connection.query('SELECT date_vente FROM vols_decouverte WHERE id = ?', [id]);
            expect(after.date_vente).toEqual(original.date_vente);

            // Accepté : enregistrement sans changer la date de vente
            await submitEdit(page, id, {});
            await expect(page.locator('body')).not.toContainText(FROM_CLOSED_TEXT);
            await expect(page.locator('body')).not.toContainText(CLOSED_TEXT);
        } finally {
            // Restauration à l'identique du bon existant
            const columns = Object.keys(original).filter(c => c !== 'id');
            await connection.query(
                `UPDATE vols_decouverte SET ${columns.map(c => `\`${c}\` = ?`).join(', ')} WHERE id = ?`,
                [...columns.map(c => original[c]), id]);
        }
    });
});
