/**
 * Contrôle de la date de gel lors de la modification d'une écriture (compta/edit).
 *
 * Cas acceptés :
 *   - modifier la date d'une écriture postérieure à la date de gel vers une autre date postérieure
 * Cas rejetés (une écriture clôturée s'affiche en lecture seule) :
 *   - extraire une écriture clôturée en la ramenant après la date de gel
 *   - déplacer une écriture postérieure à la date de gel avant (ou à) la date de gel
 *
 * L'écriture de test est créée puis supprimée via l'application (soldes restaurés).
 * L'écriture clôturée existante n'est pas modifiée si le contrôle fonctionne ; dans le
 * cas contraire sa date d'origine est restaurée (seule la date change, les soldes sont inchangés).
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/compta_modification_date_gel.spec.js --reporter=line
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

function toDb(date) {
    return [date.getFullYear(), date.getMonth() + 1, date.getDate()]
        .map((n, i) => String(n).padStart(i ? 2 : 4, '0')).join('-');
}

function dbToFr(db) {
    const [y, m, d] = db.split('-');
    return `${d}/${m}/${y}`;
}

function addDays(db, days) {
    const [y, m, d] = db.split('-').map(Number);
    return toDb(new Date(y, m - 1, d + days));
}

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

async function submitEditDate(page, id, dbDate) {
    await page.goto(`/index.php/compta/edit/${id}`);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="date_op"]', dbToFr(dbDate));
    await page.click('#validate');
    await page.waitForLoadState('networkidle');
}

async function dateOp(connection, id) {
    const [rows] = await connection.query("SELECT DATE_FORMAT(date_op, '%Y-%m-%d') AS d FROM ecritures WHERE id = ?", [id]);
    return rows.length ? rows[0].d : null;
}

test.describe('Compta - modification et date de gel', () => {
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

    test('rejette l\'extraction d\'une écriture clôturée', async ({ page }) => {
        test.skip(!freezeDate, `Pas de date de gel pour la section ${SECTION_ID}`);

        const [rows] = await connection.query(
            `SELECT e.id, DATE_FORMAT(e.date_op, '%Y-%m-%d') AS d FROM ecritures e
             WHERE e.club = ? AND e.date_op <= ? AND e.gel = 0 AND (e.achat IS NULL OR e.achat = 0)
               AND NOT EXISTS (SELECT 1 FROM paiements_en_ligne p WHERE p.ecriture_id = e.id)
             ORDER BY e.date_op DESC LIMIT 1`,
            [SECTION_ID, freezeDate]);
        test.skip(rows.length === 0, 'Aucune écriture clôturée non gelée');
        const { id, d: original } = rows[0];

        try {
            await login(page);

            // Affichage en lecture seule avec le motif
            await page.goto(`/index.php/compta/edit/${id}`);
            await page.waitForLoadState('networkidle');
            await expect(page.locator('.alert-warning')).toContainText('appartient à une période clôturée');
            // L'icône cadenas est rendue (Font Awesome, largeur non nulle)
            const lockWidth = await page.locator('.alert-warning i.fa-lock').evaluate(el => el.getBoundingClientRect().width);
            expect(lockWidth).toBeGreaterThan(0);
            await page.locator('.alert-warning').screenshot({ path: 'test-results/compta-ecriture-cloturee-cadenas.png' });
            await expect(page.locator('form[name="saisie"] button[type="submit"]')).toBeDisabled();

            // Envoi forcé (bouton réactivé) : le serveur refuse quand même
            await page.evaluate((date) => {
                const form = document.forms['saisie'];
                form.elements['date_op'].removeAttribute('readonly');
                form.elements['date_op'].value = date;
                const button = form.querySelector('button[type="submit"]');
                button.disabled = false;
                button.name = 'button';
                button.value = 'Valider';
            }, dbToFr(addDays(freezeDate, 1)));
            await page.click('form[name="saisie"] button[type="submit"]');
            await page.waitForLoadState('networkidle');
            await expect(page.locator('body')).toContainText('appartient à une période clôturée');
            expect(await dateOp(connection, id)).toBe(original);
        } finally {
            // Si le contrôle a échoué, seule la date a changé : on la restaure
            if (await dateOp(connection, id) !== original) {
                await connection.query('UPDATE ecritures SET date_op = ? WHERE id = ?', [original, id]);
            }
        }
    });

    test('accepte un déplacement après la date de gel, rejette un déplacement avant', async ({ page }) => {
        test.skip(!freezeDate, `Pas de date de gel pour la section ${SECTION_ID}`);
        const today = toDb(new Date());
        const later = addDays(freezeDate, 2);
        test.skip(today <= later, 'La date du jour doit être postérieure à la date de gel + 2 jours');

        const description = `PW DATE GEL ${Date.now()}`;
        let id = null;
        let comptes = [];
        let soldesAvant = null;

        try {
            await login(page);

            // Création d'une écriture datée d'aujourd'hui (période ouverte)
            await page.goto('/index.php/compta/depenses');
            await page.waitForLoadState('networkidle');
            const [charge, banque] = await page.evaluate(() => ['compte1', 'compte2'].map(n =>
                Array.from(document.querySelectorAll(`select[name="${n}"] option`)).map(o => o.value).find(v => v !== '')));
            comptes = [Number(charge), Number(banque)];
            const [soldes] = await connection.query(
                'SELECT id, debit, credit FROM comptes WHERE id IN (?) ORDER BY id', [comptes]);
            soldesAvant = soldes;

            await page.evaluate(([c1, c2]) => {
                $('select[name="compte1"]').val(c1).trigger('change');
                $('select[name="compte2"]').val(c2).trigger('change');
            }, [charge, banque]);
            await page.fill('input[name="date_op"]', dbToFr(today));
            await page.fill('input[name="montant"]', '1.11');
            await page.fill('textarea[name="description"]', description);
            await page.click('#validate');
            await page.waitForLoadState('networkidle');

            const [created] = await connection.query('SELECT id FROM ecritures WHERE description = ?', [description]);
            expect(created.length).toBe(1);
            id = created[0].id;

            // Accepté : nouvelle date postérieure à la date de gel
            await submitEditDate(page, id, later);
            expect(await dateOp(connection, id)).toBe(later);

            // Rejeté : nouvelle date égale à la date de gel
            await submitEditDate(page, id, freezeDate);
            await expect(page.locator('body')).toContainText(dbToFr(freezeDate));
            expect(await dateOp(connection, id)).toBe(later);

            // Rejeté : nouvelle date antérieure à la date de gel
            await submitEditDate(page, id, addDays(freezeDate, -10));
            await expect(page.locator('body')).toContainText(dbToFr(freezeDate));
            expect(await dateOp(connection, id)).toBe(later);
        } finally {
            if (id === null) {
                const [rows] = await connection.query('SELECT id FROM ecritures WHERE description = ?', [description]);
                if (rows.length) id = rows[0].id;
            }
            if (id !== null) {
                await page.goto(`/index.php/compta/delete/${id}`);
                await page.waitForLoadState('networkidle');
                const [left] = await connection.query('SELECT id FROM ecritures WHERE id = ?', [id]);
                expect(left.length).toBe(0);
            }
            if (soldesAvant) {
                const [soldesApres] = await connection.query(
                    'SELECT id, debit, credit FROM comptes WHERE id IN (?) ORDER BY id', [comptes]);
                expect(soldesApres).toEqual(soldesAvant);
            }
        }
    });
});
