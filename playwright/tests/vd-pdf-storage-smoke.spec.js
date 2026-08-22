// @ts-check
const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

/**
 * Smoke tests — Génération et stockage du PDF à la vente/modification (Lot 4)
 *
 * Vérifie :
 * - La création d'un vol de découverte génère et stocke un PDF (pdf_path + fichier).
 * - La modification d'un champ régénère le PDF stocké.
 * - Le changement de look d'une section après la vente ne modifie pas l'apparence
 *   d'un bon déjà émis (le PDF stocké est servi tel quel).
 * - Bout en bout : vente → impression → contenu conforme (PDF valide).
 *
 * @see doc/plans/configuration_bons_vols_decouverte_plan.md
 */

const LOGIN_URL = '/index.php/auth/login';
const ADMIN_USER = { username: 'testadmin', password: 'password' };
const DB_CONFIG = { host: 'localhost', user: 'gvv_user', password: 'lfoyfgbj', database: 'gvv2' };
const GVV_ROOT = path.resolve(__dirname, '../..');
const FLAG_KEY = 'vd.new_pdf_engine.enabled';

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('input[name="username"]', { timeout: 5000 });
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function setFlag(value) {
    const connection = await mysql.createConnection(DB_CONFIG);
    const [rows] = await connection.execute('SELECT id FROM configuration WHERE cle = ?', [FLAG_KEY]);
    if (rows.length > 0) {
        await connection.execute('UPDATE configuration SET valeur = ? WHERE cle = ?', [value, FLAG_KEY]);
    } else {
        await connection.execute('INSERT INTO configuration (cle, valeur) VALUES (?, ?)', [FLAG_KEY, value]);
    }
    await connection.end();
}

async function query(sql, params) {
    const connection = await mysql.createConnection(DB_CONFIG);
    const [rows] = await connection.execute(sql, params);
    await connection.end();
    return rows;
}

async function createVd(beneficiaire, sectionId) {
    const rows = await query(
        `INSERT INTO vols_decouverte (date_vente, club, product, saisie_par, beneficiaire, de_la_part, occasion, beneficiaire_email, cancelled)
         VALUES ('2026-08-03', ?, 'planeur', 'playwright', ?, 'PW Donateur', 'PW Occasion', 'pw-test@example.com', 0)`,
        [sectionId, beneficiaire]
    );
    return rows.insertId;
}

// .serial: this suite toggles the shared 'vd.new_pdf_engine.enabled' config
// flag in beforeAll/afterAll. With fullyParallel:true, an unserialized
// describe block can have its tests scheduled across different workers, each
// running its own beforeAll/afterAll — one worker's afterAll (flag -> '0')
// can then race another worker's still-running test that needs flag === '1',
// causing _generate_and_store_vd_pdf() to silently skip regeneration.
test.describe.serial('Stockage du PDF des bons de vol de découverte — moteur activé', () => {
    /** @type {number[]} */
    let createdIds = [];
    /** @type {number[]} */
    let createdLookIds = [];

    test.beforeAll(async () => {
        await setFlag('1');
    });

    test.afterAll(async () => {
        await setFlag('0');
    });

    test.afterEach(async () => {
        for (const id of createdIds) {
            const [vd] = await query('SELECT pdf_path FROM vols_decouverte WHERE id = ?', [id]);
            if (vd && vd.pdf_path) {
                const filePath = path.join(GVV_ROOT, vd.pdf_path);
                try {
                    if (fs.existsSync(filePath)) fs.unlinkSync(filePath);
                } catch (e) {
                    // Files written by the web server user may not be deletable by the
                    // test runner user; not fatal, only a leftover under gitignored uploads/.
                    console.warn('[cleanup] could not remove ' + filePath + ': ' + e.message);
                }
            }
            await query('DELETE FROM vols_decouverte WHERE id = ?', [id]);
        }
        createdIds = [];
        for (const id of createdLookIds) {
            await query('DELETE FROM vols_decouverte_look_sections WHERE look_id = ?', [id]);
            await query('DELETE FROM vols_decouverte_looks WHERE id = ?', [id]);
        }
        createdLookIds = [];
    });

    test('creating a discovery flight through the form generates and stores a valid PDF', async ({ page }) => {
        await login(page, ADMIN_USER);
        await page.goto('/index.php/vols_decouverte/create');
        await page.waitForLoadState('networkidle');

        const beneficiaire = 'PW Creation ' + Date.now();
        await page.fill('input[name="date_vente"]', '03/08/2026');
        await page.fill('input[name="date_validite"]', '03/08/2027');
        await page.selectOption('select[name="product"]', 'planeur');
        await page.fill('input[name="beneficiaire"]', beneficiaire);
        await page.fill('input[name="de_la_part"]', 'PW Donateur');
        await page.fill('input[name="occasion"]', 'PW Occasion');
        await page.fill('input[name="beneficiaire_email"]', 'pw-test@example.com');
        await page.click('#validate');
        await page.waitForLoadState('networkidle');

        const rows = await query('SELECT id, pdf_path FROM vols_decouverte WHERE beneficiaire = ?', [beneficiaire]);
        expect(rows.length).toBe(1);
        createdIds.push(rows[0].id);

        expect(rows[0].pdf_path).toBeTruthy();
        const filePath = path.join(GVV_ROOT, rows[0].pdf_path);
        expect(fs.existsSync(filePath)).toBeTruthy();
        const content = fs.readFileSync(filePath);
        expect(content.slice(0, 4).toString()).toBe('%PDF');
    });

    test('editing a field regenerates the stored PDF', async ({ page }) => {
        await login(page, ADMIN_USER);

        const id = await createVd('PW Edit Before ' + Date.now(), 1);
        createdIds.push(id);

        // First generation, via the manual regeneration action (simulates post_create).
        await page.goto('/index.php/vols_decouverte/regenerate/' + (await transformIdViaUi(page, id)));
        await page.waitForLoadState('networkidle');

        const before = await query('SELECT pdf_path FROM vols_decouverte WHERE id = ?', [id]);
        expect(before[0].pdf_path).toBeTruthy();
        const beforePath = path.join(GVV_ROOT, before[0].pdf_path);
        const beforeContent = fs.readFileSync(beforePath);

        const newBeneficiaire = 'PW Edit After ' + Date.now();
        await page.goto('/index.php/vols_decouverte/edit/' + id);
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="beneficiaire"]', newBeneficiaire);
        await page.click('#validate');
        await page.waitForLoadState('networkidle');

        const after = await query('SELECT pdf_path, beneficiaire FROM vols_decouverte WHERE id = ?', [id]);
        expect(after[0].beneficiaire).toBe(newBeneficiaire);
        expect(after[0].pdf_path).toBeTruthy();
        const afterContent = fs.readFileSync(path.join(GVV_ROOT, after[0].pdf_path));

        expect(afterContent.slice(0, 4).toString()).toBe('%PDF');
        expect(Buffer.compare(beforeContent, afterContent)).not.toBe(0);
    });

    test('changing a section look after the sale does not alter an already-issued voucher', async ({ page }) => {
        await login(page, ADMIN_USER);

        const id = await createVd('PW Stability ' + Date.now(), 1);
        createdIds.push(id);

        const obfuscated = await transformIdViaUi(page, id);
        await page.goto('/index.php/vols_decouverte/regenerate/' + obfuscated);
        await page.waitForLoadState('networkidle');

        const stored = await query('SELECT pdf_path FROM vols_decouverte WHERE id = ?', [id]);
        expect(stored[0].pdf_path).toBeTruthy();
        const originalBytes = fs.readFileSync(path.join(GVV_ROOT, stored[0].pdf_path));

        // Configure a visually different look and associate it to section 1.
        const layout = JSON.stringify({
            version: 1,
            recto: { variable_fields: [], static_fields: [], qr_field: { enabled: true, x: 10, y: 10, size: 15 } },
            verso: {
                variable_fields: [
                    { id: 'beneficiaire', enabled: true, x: 100, y: 100, font: 'times', bold: true, size: 20, color: [255, 0, 0], align: 'C', width: 100 },
                ],
                static_fields: [], qr_field: null,
            },
        });
        const lookResult = await query(
            "INSERT INTO vols_decouverte_looks (nom, layout_json) VALUES (?, ?)",
            ['PW Stability Look', layout]
        );
        createdLookIds.push(lookResult.insertId);
        await query(
            'INSERT INTO vols_decouverte_look_sections (section_id, look_id) VALUES (1, ?)',
            [lookResult.insertId]
        );

        // Re-print the same, already-issued voucher: must be served unchanged.
        const printResponse = await page.request.get('/index.php/vols_decouverte/print_vd/' + obfuscated);
        const reprintedBytes = Buffer.from(await printResponse.body());

        expect(Buffer.compare(originalBytes, reprintedBytes)).toBe(0);
    });
});

/**
 * Retrieves the obfuscated id for a raw vols_decouverte id by locating its
 * row (via `action` link) on the admin list page — avoids re-implementing
 * the crypto_helper transform algorithm in JS.
 */
async function transformIdViaUi(page, id) {
    await page.goto('/index.php/vols_decouverte/select_by_id');
    await page.waitForLoadState('networkidle');
    await page.selectOption('select[name="vd_id"]', String(id));
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    const url = page.url();
    const match = url.match(/action\/([A-Za-z0-9]+)/);
    if (!match) {
        throw new Error('Could not resolve obfuscated id for vols_decouverte id=' + id + ' (landed on ' + url + ')');
    }
    return match[1];
}
