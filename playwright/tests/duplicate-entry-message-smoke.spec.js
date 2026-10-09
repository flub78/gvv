/**
 * Smoke test : message d'erreur d'un doublon refusé par la base (MySQL 1062)
 *
 * Le message doit indiquer le ou les champs de l'index unique et la valeur refusée,
 * au lieu du message générique « doublon détecté ».
 * Cas utilisé : produits, index unique (reference, club), non contrôlé par la
 * validation du formulaire. Deux produits de test sont créés en base, on donne au
 * premier la référence du second, puis tout est supprimé, y compris en cas d'échec.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/duplicate-entry-message-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const SECTION = 1;

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

test.describe('Message de doublon', () => {
    const RUN = Date.now().toString().slice(-6);
    const REFS = [`PW dup ${RUN} A`, `PW dup ${RUN} B`];
    let db;
    let ids = [];

    test.beforeAll(async () => {
        db = await mysql.createConnection(DB_CONFIG);
        const [comptes] = await db.query('SELECT compte FROM produits WHERE club = ? LIMIT 1', [SECTION]);
        const compte = comptes.length ? comptes[0].compte : 0;
        for (const ref of REFS) {
            const [res] = await db.query(
                "INSERT INTO produits (reference, description, compte, club, is_cotisation) VALUES (?, 'test', ?, ?, 0)",
                [ref, compte, SECTION]);
            ids.push(res.insertId);
            await db.query("INSERT INTO tarifs (produit_id, date, prix, nb_tickets) VALUES (?, '2026-01-01', 1, 0)",
                [res.insertId]);
        }
    });

    test.afterAll(async () => {
        if (ids.length) {
            await db.query('DELETE FROM tarifs WHERE produit_id IN (?)', [ids]);
            await db.query('DELETE FROM produits WHERE id IN (?)', [ids]);
        }
        await db.end();
    });

    test('le message indique les champs et la valeur en doublon', async ({ page }) => {
        await login(page, ADMIN_USER);
        await page.request.post('/index.php/user_roles_per_section/set_section', {
            form: { section: String(SECTION), current_url: '/index.php/welcome' }
        });

        await page.goto(`/index.php/produits/edit/${ids[0]}`);
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="reference"]', REFS[1]);
        await page.click('input[name="button"]');
        await page.waitForLoadState('networkidle');

        const error = page.locator('.text-danger:has-text("doublon interdit sur les champs")');
        await expect(error).toContainText(REFS[1]);
        console.log('message:', (await error.textContent()).trim());
        const [rows] = await db.query('SELECT reference FROM produits WHERE id = ?', [ids[0]]);
        expect(rows[0].reference).toBe(REFS[0]);
    });
});
