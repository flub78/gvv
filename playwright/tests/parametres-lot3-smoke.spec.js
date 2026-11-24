/**
 * @feature Configuration
 *
 * Page Configuration, lot 3 : onglets Vols de découverte, Finances, Système,
 * écrans spécialisés présentés dans les onglets, anciennes adresses.
 *
 * Les tests modifient program.php (liste des développeurs, par la page) et les
 * contacts des vols de découverte : ils sont sauvegardés avant et restaurés à
 * l'identique après, ainsi que l'historique.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/parametres-lot3-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const CA_USER = { username: 'testca', password: 'password' };
const DEV_USER = { username: 'testplanchiste', password: 'password' };
const PROGRAM_FILE = path.resolve(__dirname, '../../application/config/program.php');

async function login(page, user) {
  await page.goto('/auth/logout');
  await page.goto('/auth/login');
  await page.fill('input[name="username"]', user.username);
  await page.fill('input[name="password"]', user.password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');
}

async function checkNoPhpErrors(page) {
  const body = await page.textContent('body');
  expect(body).not.toContain('A PHP Error was encountered');
  expect(body).not.toContain('Fatal error');
}

test.describe('Configuration : lot 3', () => {
  test.describe.configure({ mode: 'serial' });
  let programFile = null;
  let vdRows = [];
  let historyMaxId = 0;

  test.beforeAll(async () => {
    programFile = fs.readFileSync(PROGRAM_FILE);
    const db = await mysql.createConnection(DB_CONFIG);
    try {
      const [rows] = await db.execute("SELECT * FROM configuration WHERE cle LIKE 'vd.%'");
      vdRows = rows;
      const [[h]] = await db.execute('SELECT COALESCE(MAX(id), 0) AS max_id FROM parametres_history');
      historyMaxId = h.max_id;
    } finally {
      await db.end();
    }
  });

  // Restaure program.php à l'octet près, les clés vd.* et l'historique.
  test.afterAll(async () => {
    if (programFile !== null) {
      fs.writeFileSync(PROGRAM_FILE, programFile);
    }
    const db = await mysql.createConnection(DB_CONFIG);
    try {
      await db.execute("DELETE FROM configuration WHERE cle LIKE 'vd.%'");
      for (const row of vdRows) {
        const cols = Object.keys(row);
        await db.execute(
          `INSERT INTO configuration (${cols.map(c => '`' + c + '`').join(',')}) VALUES (${cols.map(() => '?').join(',')})`,
          cols.map(c => row[c]));
      }
      await db.execute('DELETE FROM parametres_history WHERE id > ?', [historyMaxId]);
    } finally {
      await db.end();
    }
  });

  /**
   * Scénario: Le conseil d'administration tient à jour les contacts des vols de découverte
   *   Étant donné un membre du CA connecté
   *   Quand il ouvre l'onglet Vols de découverte et change le contact planeur
   *   Alors un message confirme l'enregistrement
   *   Et l'onglet Finances lui est présenté en lecture seule
   *   Et l'onglet Système ne lui est pas présenté
   */
  test('board member edits discovery flight contacts, sees finances read-only', async ({ page }) => {
    await login(page, CA_USER);
    await page.goto('/parametres/index/vols_decouverte');
    await checkNoPhpErrors(page);
    await page.fill('#f-vd__planeur__contact_name', 'Contact Playwright');
    await page.click('#parametres-save');
    await expect(page.locator('#parametres-saved')).toBeVisible();
    await expect(page.locator('#f-vd__planeur__contact_name')).toHaveValue('Contact Playwright');

    await page.goto('/parametres/index/finances');
    await checkNoPhpErrors(page);
    await expect(page.locator('#parametres-read-only')).toBeVisible();
    await expect(page.locator('#parametres-save')).toHaveCount(0);
    await expect(page.locator('#parametres-tabs a[data-tab="systeme"]')).toHaveCount(0);
  });

  /**
   * Scénario: Un développeur qui n'est pas membre du CA accède à l'onglet Système
   *   Étant donné un développeur qui ajoute à la liste des développeurs un membre sans responsabilité au conseil d'administration
   *   Quand ce membre ouvre la configuration depuis le tableau de bord d'administration du club
   *   Alors il voit l'onglet Système
   *   Et la page indique la version de la base de données
   */
  test('developer who is not a board member reaches the System tab', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto('/parametres/index/systeme');
    const original = await page.inputValue('#f-dev_users');
    await page.fill('#f-dev_users', `${original},${DEV_USER.username}`);
    await page.click('#parametres-save');
    await expect(page.locator('#parametres-saved')).toBeVisible();

    try {
      await login(page, DEV_USER);
      await page.goto('/welcome/section/admin_club');
      await checkNoPhpErrors(page);
      await page.click('#card-parametres a');
      await expect(page.locator('#parametres-tabs a[data-tab="systeme"]')).toBeVisible();
      await page.click('#parametres-tabs a[data-tab="systeme"]');
      await checkNoPhpErrors(page);
      await expect(page.locator('[data-param="info.migration"]')).toBeVisible();
    } finally {
      // Retrait par la page, pour que le serveur relise aussitôt la liste d'origine.
      await login(page, ADMIN_USER);
      await page.goto('/parametres/index/systeme');
      await page.fill('#f-dev_users', original);
      await page.click('#parametres-save');
    }
  });

  /**
   * Scénario: Les anciennes adresses de configuration mènent au bon onglet
   *   Étant donné un administrateur du club connecté
   *   Quand il ouvre l'ancienne adresse de la configuration du club
   *   Alors il arrive sur l'onglet Club
   *   Quand il ouvre l'ancienne liste des paramètres
   *   Alors il arrive sur l'onglet Système
   *   Quand il ouvre l'ancienne configuration de la newsletter
   *   Alors il arrive sur l'onglet Newsletter
   */
  test('old configuration addresses lead to the right tab', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto('/config');
    await expect(page).toHaveURL(/parametres\/index\/club/);
    await page.goto('/configuration');
    await expect(page).toHaveURL(/parametres\/index\/systeme/);
    await page.goto('/newsletter_admin/configuration');
    await expect(page).toHaveURL(/parametres\/index\/newsletter/);
    await checkNoPhpErrors(page);
  });

  /**
   * Scénario: Les écrans spécialisés s'ouvrent depuis leur onglet
   *   Étant donné un administrateur du club connecté
   *   Quand il ouvre l'onglet Documents et cartes
   *   Alors l'écran des cartes de membre y est présenté
   *   Et il s'ouvre depuis l'onglet
   *   Et l'onglet Paiements en ligne présente l'écran des réglages HelloAsso
   */
  test('specialised screens open from their tab', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto('/parametres/index/documents');
    await checkNoPhpErrors(page);
    await page.click('[data-link="cartes_membre/config"] a');
    await expect(page).toHaveURL(/cartes_membre\/config/);
    await checkNoPhpErrors(page);

    await page.goto('/parametres/index/paiements');
    await expect(page.locator('[data-link="paiements_en_ligne/admin_config"] a')).toBeVisible();
  });
});
