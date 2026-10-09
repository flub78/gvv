/**
 * Smoke test : enregistrer un membre sans email
 *
 * Régression : le formulaire enregistrait un email vide comme '' au lieu de NULL.
 * Avec l'index unique idx_membres_memail, le deuxième membre sans email enregistré
 * échouait avec « doublon détecté (table: membres) ».
 *
 * Le test crée deux membres sans email, les enregistre sans modification l'un
 * après l'autre depuis le formulaire, vérifie que chaque enregistrement aboutit
 * et que l'email reste NULL, puis supprime ses membres, y compris en cas d'échec.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/membre-email-vide-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const ADMIN_USER = { username: 'testadmin', password: 'password' };

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

test.describe('Membre sans email', () => {
    const RUN = Date.now().toString().slice(-6);
    const LOGINS = [`pwmail${RUN}a`, `pwmail${RUN}b`];
    let db;

    test.beforeAll(async () => {
        db = await mysql.createConnection(DB_CONFIG);
        for (const login of LOGINS) {
            await db.query(
                "INSERT INTO membres (mlogin, mnom, mprenom, memail, actif) VALUES (?, 'Playwright', 'SansEmail', NULL, 1)",
                [login]);
        }
    });

    test.afterAll(async () => {
        await db.query('DELETE FROM membres WHERE mlogin IN (?)', [LOGINS]);
        await db.end();
    });

    test('deux membres sans email s\'enregistrent sans doublon', async ({ page }) => {
        await login(page, ADMIN_USER);

        for (const mlogin of LOGINS) {
            await page.goto(`/index.php/membre/edit/${mlogin}`);
            await page.waitForLoadState('networkidle');
            await page.click('input[name="button"]');
            await page.waitForLoadState('networkidle');

            // Un enregistrement réussi quitte le formulaire ; un échec le réaffiche avec l'erreur
            const errors = (await page.locator('.text-danger, .error').allTextContents())
                .map(s => s.trim()).filter(Boolean);
            expect(errors, `Enregistrement de ${mlogin}`).toEqual([]);
            expect(page.url()).not.toContain('formValidation');
            const [rows] = await db.query('SELECT memail FROM membres WHERE mlogin = ?', [mlogin]);
            expect(rows[0].memail).toBeNull();
        }
    });
});
