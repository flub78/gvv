/**
 * @feature Configuration
 *
 * Page Configuration : onglets Club, Modules et Messagerie, lecture seule pour
 * le conseil d'administration, historique et retour à la valeur précédente.
 *
 * Les tests modifient application/config/club.php : le fichier est sauvegardé
 * avant les tests et restauré à l'identique après, ainsi que l'historique.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/parametres-lot2-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const CA_USER = { username: 'testca', password: 'password' };
const CLUB_FILE = path.resolve(__dirname, '../../application/config/club.php');

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

test.describe('Configuration : Club, Modules, Messagerie', () => {
  test.describe.configure({ mode: 'serial' });
  let clubFile = null;
  let historyMaxId = 0;

  test.beforeAll(async () => {
    clubFile = fs.readFileSync(CLUB_FILE);
    const db = await mysql.createConnection(DB_CONFIG);
    try {
      const [[h]] = await db.execute('SELECT COALESCE(MAX(id), 0) AS max_id FROM parametres_history');
      historyMaxId = h.max_id;
    } finally {
      await db.end();
    }
  });

  // Restaure club.php à l'octet près et retire l'historique créé par les tests.
  test.afterAll(async () => {
    if (clubFile !== null) {
      fs.writeFileSync(CLUB_FILE, clubFile);
    }
    const db = await mysql.createConnection(DB_CONFIG);
    try {
      await db.execute('DELETE FROM parametres_history WHERE id > ?', [historyMaxId]);
    } finally {
      await db.end();
    }
  });

  /**
   * Scénario: Un membre du CA consulte l'identité du club et les modules sans pouvoir les modifier
   *   Étant donné un membre du conseil d'administration connecté
   *   Quand il ouvre la configuration
   *   Alors il voit les onglets Club et Modules, signalés en lecture seule, sans champ ni bouton d'enregistrement
   *   Et il ne voit pas les onglets Messagerie et Pages publiques
   *   Et l'ouverture directe de l'onglet Messagerie lui est refusée par un message
   */
  test('board member sees Club and Modules read-only', async ({ page }) => {
    await login(page, CA_USER);
    await page.goto('/parametres');
    await checkNoPhpErrors(page);
    await expect(page.locator('#parametres-tabs a[data-tab="club"]')).toBeVisible();
    await expect(page.locator('#parametres-tabs a[data-tab="modules"]')).toBeVisible();
    await expect(page.locator('#parametres-tabs a[data-tab="messagerie"]')).toHaveCount(0);
    await expect(page.locator('#parametres-tabs a[data-tab="pages_publiques"]')).toHaveCount(0);

    for (const tab of ['club', 'modules']) {
      await page.goto('/parametres/index/' + tab);
      await expect(page.locator('#parametres-read-only')).toBeVisible();
      await expect(page.locator('#parametres-form input:not([type=hidden]), #parametres-form select, #parametres-form textarea')).toHaveCount(0);
      await expect(page.locator('#parametres-save')).toHaveCount(0);
      await expect(page.locator('#parametres-history-link')).toHaveCount(0);
    }

    await page.goto('/parametres/index/messagerie');
    await expect(page.locator('#parametres-message')).toBeVisible();
  });

  /**
   * Scénario: Une adresse invalide est refusée à côté du champ, sans perte des autres saisies
   *   Étant donné un administrateur du club sur l'onglet Club
   *   Quand il saisit une adresse e-mail invalide et modifie le contact RGPD, puis enregistre
   *   Alors l'adresse est refusée avec son motif affiché sous le champ
   *   Et le contact RGPD saisi est toujours affiché
   */
  test('invalid value is refused next to the field', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto('/parametres/index/club');
    await checkNoPhpErrors(page);
    await page.fill('#f-email_club', 'pas-une-adresse');
    await page.fill('#f-contact_rgpd', 'La secrétaire (Playwright)');
    await page.click('#parametres-save');

    await expect(page.locator('#parametres-errors')).toBeVisible();
    await expect(page.locator('#f-email_club')).toHaveClass(/is-invalid/);
    await expect(page.locator('[data-param="email_club"] .invalid-feedback')).toBeVisible();
    await expect(page.locator('#f-email_club')).toHaveValue('pas-une-adresse');
    await expect(page.locator('#f-contact_rgpd')).toHaveValue('La secrétaire (Playwright)');
  });

  /**
   * Scénario: Revenir à la valeur précédente depuis l'historique
   *   Étant donné un administrateur du club qui modifie le contact RGPD
   *   Quand il ouvre l'historique de l'onglet Club et revient sur cette modification
   *   Alors un message confirme le retour à la valeur précédente
   *   Et l'onglet Club affiche de nouveau l'ancien contact RGPD
   */
  test('restore the previous value from the history', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto('/parametres/index/club');
    const before = await page.inputValue('#f-contact_rgpd');
    const marker = 'Contact Playwright ' + Date.now();
    await page.fill('#f-contact_rgpd', marker);
    await page.click('#parametres-save');
    await expect(page.locator('#parametres-saved')).toBeVisible();

    await page.click('#parametres-history-link');
    await checkNoPhpErrors(page);
    const row = page.locator('tr', { hasText: marker }).first();
    await expect(row).toBeVisible();
    await row.locator('.restore-btn').click();
    await expect(page.locator('#parametres-restore-result.alert-success')).toBeVisible();

    await page.goto('/parametres/index/club');
    await expect(page.locator('#f-contact_rgpd')).toHaveValue(before);
  });

  /**
   * Scénario: L'identité du club ne se modifie plus dans l'ancien écran de configuration du club
   *   Étant donné un administrateur du club
   *   Quand il ouvre l'ancien écran de configuration du club
   *   Alors il n'y trouve plus le nom ni l'adresse du club
   *   Et un lien le conduit à l'onglet Club de la page Configuration
   */
  test('legacy club screen points to the Club tab', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto('/config');
    await checkNoPhpErrors(page);
    await expect(page.locator('input[name="nom_club"]')).toHaveCount(0);
    await expect(page.locator('input[name="adresse_club"]')).toHaveCount(0);
    await page.click('#config-identity-moved a');
    await expect(page).toHaveURL(/parametres\/index\/club/);
    await expect(page.locator('#f-nom_club')).toBeVisible();
  });

  /**
   * Scénario: Le mot de passe du serveur d'envoi n'est jamais réaffiché
   *   Étant donné un administrateur du club
   *   Quand il ouvre l'onglet Messagerie
   *   Alors le champ du mot de passe est vide, avec seulement l'indication qu'une valeur est définie ou non
   *   Et il peut envoyer un e-mail de test avec les paramètres enregistrés
   */
  test('messaging tab never shows the SMTP password', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto('/parametres/index/messagerie');
    await checkNoPhpErrors(page);
    await expect(page.locator('#f-smtp_pass')).toHaveValue('');
    const status = page.locator('[data-param="smtp_pass"] .secret-status');
    await expect(status).toBeVisible();

    // Mot de passe enregistré, entre guillemets simples ou doubles.
    const emailFile = path.resolve(__dirname, '../../application/config/email.php');
    const content = fs.existsSync(emailFile) ? fs.readFileSync(emailFile, 'utf8') : '';
    const match = content.match(/^\$config\['smtp_pass'\]\s*=\s*(?:'([^']*)'|"([^"]*)")\s*;/m);
    const smtpPass = match ? (match[1] !== undefined ? match[1] : match[2]) : '';
    if (smtpPass) {
      await expect(status).toHaveClass(/bg-success/);
      expect(await page.content()).not.toContain(smtpPass);
    } else {
      // Aucun mot de passe : la page l'indique, sans rien à masquer.
      await expect(status).toHaveClass(/bg-secondary/);
    }
    await expect(page.locator('#parametres-test-email-send')).toBeVisible();
  });
});
