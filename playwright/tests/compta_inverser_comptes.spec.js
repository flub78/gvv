/**
 * Bouton "Inverser" du formulaire de création d'écriture (compta/depenses).
 *
 * Vérifie que :
 * 1. Le bouton échange les listes et les sélections débit/crédit et affiche le badge "inversée"
 * 2. Un deuxième appui revient exactement à l'état initial
 * 3. L'inversion est conservée après une erreur de validation
 * 4. L'écriture créée inversée a bien les comptes échangés en base
 * 5. Après "Créer et faire une autre saisie", le formulaire revient à l'état normal
 *
 * L'écriture créée est supprimée via l'application (restaure les soldes des comptes).
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/compta_inverser_comptes.spec.js --reporter=line
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

async function login(page) {
    await page.goto('/index.php/auth/logout');
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', 'testadmin');
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

async function formState(page) {
    return page.evaluate(() => {
        const opts = name => Array.from(document.querySelectorAll(`select[name="${name}"] option`)).map(o => o.value);
        return {
            compte1Options: opts('compte1'),
            compte2Options: opts('compte2'),
            compte1: document.querySelector('select[name="compte1"]').value,
            compte2: document.querySelector('select[name="compte2"]').value,
            inverse: document.querySelector('#inverse').value,
            badgeVisible: getComputedStyle(document.querySelector('#badge_inverse')).display !== 'none',
        };
    });
}

async function selectCompte(page, name, value) {
    await page.evaluate(([n, v]) => {
        $(`select[name="${n}"]`).val(v).trigger('change');
    }, [name, value]);
}

async function soldes(connection, ids) {
    const [rows] = await connection.query(
        'SELECT id, debit, credit FROM comptes WHERE id IN (?) ORDER BY id', [ids]);
    return rows;
}

test.describe('Compta - bouton Inverser', () => {
    test.describe.configure({ mode: 'serial' });

    test('inverse, revient à l\'état initial et crée une écriture inversée', async ({ page }) => {
        const connection = await mysql.createConnection(DB_CONFIG);
        const description = `PW INVERSER ${Date.now()}`;
        let ecritureId = null;
        let comptes = [];
        let soldesAvant = null;

        try {
            await login(page);
            await page.goto('/index.php/compta/depenses');
            await page.waitForLoadState('networkidle');

            // Libellé et position : sur la ligne juste sous le compte de crédit
            await expect(page.locator('#btn_inverse')).toHaveText('Inverser les comptes');
            const placedUnderCredit = await page.evaluate(() => {
                const row = document.querySelector('select[name="compte2"]').closest('tr');
                return !!(row && row.nextElementSibling && row.nextElementSibling.querySelector('#btn_inverse'));
            });
            expect(placedUnderCredit).toBe(true);
            await page.locator('select[name="compte1"]').locator('xpath=ancestor::table[1]')
                .screenshot({ path: 'test-results/compta-inverser-comptes-position.png' });

            const initial = await formState(page);
            expect(initial.inverse).toBe('0');
            expect(initial.badgeVisible).toBe(false);
            const charge = initial.compte1Options.find(v => v !== '');
            const banque = initial.compte2Options.find(v => v !== '');
            expect(charge, 'compte de charge disponible').toBeTruthy();
            expect(banque, 'compte de banque disponible').toBeTruthy();
            comptes = [Number(charge), Number(banque)];
            soldesAvant = await soldes(connection, comptes);

            await selectCompte(page, 'compte1', charge);
            await selectCompte(page, 'compte2', banque);
            const selected = await formState(page);

            // 1. Premier appui : listes et sélections échangées, badge visible
            await page.click('#btn_inverse');
            const inverted = await formState(page);
            expect(inverted.compte1Options).toEqual(selected.compte2Options);
            expect(inverted.compte2Options).toEqual(selected.compte1Options);
            expect(inverted.compte1).toBe(banque);
            expect(inverted.compte2).toBe(charge);
            expect(inverted.inverse).toBe('1');
            expect(inverted.badgeVisible).toBe(true);

            // 2. Deuxième appui : retour à l'état initial
            await page.click('#btn_inverse');
            expect(await formState(page)).toEqual(selected);

            // 3. Inversion conservée après une erreur de validation (montant vide)
            await page.click('#btn_inverse');
            const today = new Date();
            const dateOp = [today.getDate(), today.getMonth() + 1].map(n => String(n).padStart(2, '0')).join('/')
                + '/' + today.getFullYear();
            await page.fill('input[name="date_op"]', dateOp);
            await page.fill('textarea[name="description"]', description);
            await page.fill('input[name="montant"]', '');
            await page.click('#validate');
            await page.waitForLoadState('networkidle');
            const afterError = await formState(page);
            expect(afterError.inverse).toBe('1');
            expect(afterError.badgeVisible).toBe(true);
            expect(afterError.compte1).toBe(banque);
            expect(afterError.compte2).toBe(charge);
            expect(afterError.compte1Options).toContain(banque);
            expect(afterError.compte2Options).toContain(charge);

            // 4. Création de l'écriture inversée ("Créer et faire une autre saisie")
            await page.fill('input[name="montant"]', '1.23');
            await page.click('#validate_continue');
            await page.waitForLoadState('networkidle');

            const [rows] = await connection.query(
                'SELECT id, compte1, compte2, montant FROM ecritures WHERE description = ?', [description]);
            expect(rows.length).toBe(1);
            ecritureId = rows[0].id;
            expect(String(rows[0].compte1)).toBe(banque);
            expect(String(rows[0].compte2)).toBe(charge);
            expect(Number(rows[0].montant)).toBe(1.23);

            // 5. Retour à l'état normal après la création
            await expect(page.locator('body')).toContainText('créée avec succés');
            const afterCreate = await formState(page);
            expect(afterCreate.inverse).toBe('0');
            expect(afterCreate.badgeVisible).toBe(false);
            expect(afterCreate.compte1).toBe(charge);
            expect(afterCreate.compte2).toBe(banque);
            expect(afterCreate.compte1Options).toContain(charge);
            expect(afterCreate.compte2Options).toContain(banque);
        } finally {
            // Suppression via l'application pour restaurer les soldes des comptes
            if (ecritureId === null) {
                const [rows] = await connection.query(
                    'SELECT id FROM ecritures WHERE description = ?', [description]);
                if (rows.length) ecritureId = rows[0].id;
            }
            if (ecritureId !== null) {
                await page.goto(`/index.php/compta/delete/${ecritureId}`);
                await page.waitForLoadState('networkidle');
                const [left] = await connection.query('SELECT id FROM ecritures WHERE id = ?', [ecritureId]);
                expect(left.length).toBe(0);
            }
            if (soldesAvant) {
                expect(await soldes(connection, comptes)).toEqual(soldesAvant);
            }
            await connection.end();
        }
    });
});
