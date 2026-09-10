/**
 * Playwright — Ajout d'un aérodrome depuis la saisie de vol
 *
 * Fonctionnalité : à côté des sélecteurs d'aérodrome de décollage et
 * d'atterrissage des formulaires vols_avion/create et vols_planeur/create,
 * un bouton "+" ouvre une fenêtre modale de création rapide d'aérodrome.
 * Après validation, l'aérodrome est créé (AJAX terrains/ajax_create), ajouté
 * à tous les sélecteurs d'aérodrome de la page et sélectionné dans celui
 * d'où provient la demande, sans quitter le formulaire de saisie.
 *
 * Nettoyage : chaque test supprime l'aérodrome qu'il a créé (afterEach +
 * afterAll de sécurité). Les codes OACI générés sont préfixés "T" + horodatage
 * base36, ils ne peuvent pas entrer en collision avec des données réelles.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/terrain-ajout-depuis-saisie-vol.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const LOGIN_URL = '/index.php/auth/login';
const CREATE_AVION = '/index.php/vols_avion/create';
const CREATE_PLANEUR = '/index.php/vols_planeur/create';

const ADMIN = { username: 'testadmin', password: 'password' };

function dbConfig() {
    const configPath = path.resolve(__dirname, '../../application/config/database.php');
    const content = fs.readFileSync(configPath, 'utf8');
    const read = (key) => {
        const m = content.match(new RegExp(`\\$db\\['default'\\]\\['${key}'\\]\\s*=\\s*'([^']*)'`));
        if (!m) throw new Error(`database.${key} introuvable`);
        return m[1];
    };
    return { host: read('hostname'), user: read('username'), password: read('password'), database: read('database') };
}

const DB = dbConfig();
const createdOaci = new Set();

function mysql(sql) {
    const command = `mysql -N -B -h${DB.host} -u${DB.user} -p${DB.password} ${DB.database} -e "${sql.replace(/"/g, '\\"')}"`;
    const out = execSync(command, { encoding: 'utf8' }).trim();
    return out ? out.split('\n').map((l) => l.split('\t')) : [];
}

function deleteTerrain(oaci) {
    mysql(`DELETE FROM terrains WHERE oaci = '${oaci.replace(/'/g, "''")}'`);
}

function uniqueOaci() {
    const code = ('T' + Date.now().toString(36) + Math.floor(Math.random() * 36).toString(36)).toUpperCase().slice(0, 10);
    createdOaci.add(code);
    return code;
}

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

/**
 * Ouvre la modale via le bouton "+" du sélecteur `target`, saisit le
 * terrain et valide. Retourne après fermeture de la modale.
 */
async function addTerrainViaModal(page, target, { oaci, nom, freq1 }) {
    await page.click(`button.js-add-terrain[data-target="${target}"]`);
    await expect(page.locator('#terrainModal')).toBeVisible();

    await page.fill('#terrainModalOaci', oaci);
    await page.fill('#terrainModalNom', nom);
    if (freq1 !== undefined) await page.fill('#terrainModalFreq1', freq1);

    await Promise.all([
        page.waitForResponse((r) => r.url().includes('/terrains/ajax_create')),
        page.click('#terrainModalSubmit'),
    ]);
}

test.afterEach(async () => {
    for (const oaci of createdOaci) deleteTerrain(oaci);
});

test.afterAll(async () => {
    for (const oaci of createdOaci) deleteTerrain(oaci);
    createdOaci.clear();
});

test('vols_planeur/create : le bouton + crée un aérodrome et le sélectionne', async ({ page }) => {
    const oaci = uniqueOaci();
    const nom = 'Terrain Playwright Planeur';

    await login(page, ADMIN);
    await page.goto(CREATE_PLANEUR);
    await page.locator('#vplieudeco').waitFor({ state: 'attached' });

    await addTerrainViaModal(page, 'vplieudeco', { oaci, nom, freq1: '122.500' });

    await expect(page.locator('#terrainModal')).toBeHidden();

    // L'option a été ajoutée aux deux sélecteurs d'aérodrome de la page…
    await expect(page.locator(`#vplieudeco option[value="${oaci}"]`)).toHaveCount(1);
    await expect(page.locator(`#vplieuatt option[value="${oaci}"]`)).toHaveCount(1);

    // …et sélectionnée dans celui d'où provient la demande.
    await expect(page.locator('#vplieudeco')).toHaveValue(oaci);

    // Persistance en base
    const rows = mysql(`SELECT nom FROM terrains WHERE oaci = '${oaci}'`);
    expect(rows.length).toBe(1);
    expect(rows[0][0]).toBe(nom);
});

test('vols_avion/create : le bouton + crée un aérodrome et le sélectionne', async ({ page }) => {
    const oaci = uniqueOaci();
    const nom = 'Terrain Playwright Avion';

    await login(page, ADMIN);
    await page.goto(CREATE_AVION);
    await page.locator('#valieuatt').waitFor({ state: 'attached' });

    await addTerrainViaModal(page, 'valieuatt', { oaci, nom });

    await expect(page.locator('#terrainModal')).toBeHidden();
    await expect(page.locator(`#valieudeco option[value="${oaci}"]`)).toHaveCount(1);
    await expect(page.locator(`#valieuatt option[value="${oaci}"]`)).toHaveCount(1);
    await expect(page.locator('#valieuatt')).toHaveValue(oaci);
});

test('la modale refuse un code OACI déjà existant', async ({ page }) => {
    const oaci = uniqueOaci();
    const nom = 'Terrain Playwright Doublon';

    await login(page, ADMIN);
    await page.goto(CREATE_PLANEUR);
    await page.locator('#vplieudeco').waitFor({ state: 'attached' });

    // Première création : OK
    await addTerrainViaModal(page, 'vplieudeco', { oaci, nom });
    await expect(page.locator('#terrainModal')).toBeHidden();

    // Deuxième tentative avec le même code : erreur affichée, modale ouverte
    await addTerrainViaModal(page, 'vplieuatt', { oaci, nom: 'Autre nom' });
    await expect(page.locator('#terrainModal')).toBeVisible();
    await expect(page.locator('#terrainModalOaciError')).not.toBeEmpty();

    // Une seule ligne en base
    const rows = mysql(`SELECT COUNT(*) FROM terrains WHERE oaci = '${oaci}'`);
    expect(rows[0][0]).toBe('1');
});
