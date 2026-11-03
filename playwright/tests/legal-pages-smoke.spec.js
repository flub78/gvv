/**
 * @feature Pages publiques
 *
 * Pages publiques (règles de confidentialité, conditions d'utilisation, newsletters)
 * et leur réglage dans la page Configuration.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/legal-pages-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const MEMBER_USER = { username: 'testuser', password: 'password' };
const MARKER = 'Texte de test Playwright';

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

test.describe('Pages publiques', () => {
  // Un seul processus : la sauvegarde et la restauration des textes ne doivent pas se croiser.
  test.describe.configure({ mode: 'serial' });
  let snapshot = [];

  test.beforeAll(async () => {
    const db = await mysql.createConnection(DB_CONFIG);
    try {
      const [rows] = await db.execute("SELECT * FROM configuration WHERE cle LIKE 'legal.%'");
      snapshot = rows;
    } finally {
      await db.end();
    }
  });

  // Restaure exactement les textes présents avant les tests.
  test.afterAll(async () => {
    const db = await mysql.createConnection(DB_CONFIG);
    try {
      await db.execute("DELETE FROM configuration WHERE cle LIKE 'legal.%'");
      for (const row of snapshot) {
        const cols = Object.keys(row);
        await db.execute(
          `INSERT INTO configuration (${cols.map(c => '`' + c + '`').join(',')}) VALUES (${cols.map(() => '?').join(',')})`,
          cols.map(c => row[c]));
      }
    } finally {
      await db.end();
    }
  });

  /**
   * Scénario: Un visiteur lit les règles de confidentialité et les conditions d'utilisation sans se connecter
   *   Étant donné un visiteur non connecté sur la page de connexion
   *   Quand il suit le lien vers les règles de confidentialité
   *   Alors il lit les règles de confidentialité du club
   *   Et il peut passer aux conditions d'utilisation
   */
  test('anonymous visitor reads privacy policy and terms from the login page', async ({ page }) => {
    await page.goto('/auth/logout');
    await page.goto('/auth/login');
    await page.click('#legal-links a[href$="legal/confidentialite"]');
    await expect(page).toHaveURL(/legal\/confidentialite/);
    await checkNoPhpErrors(page);
    await expect(page.locator('article.legal-page h1')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toHaveCount(0);

    await page.click('a[href$="legal/conditions"]');
    await expect(page).toHaveURL(/legal\/conditions/);
    await expect(page.locator('article.legal-page h1')).toBeVisible();
  });

  /**
   * @feature Newsletter
   * Scénario: Un visiteur consulte les newsletters publiées sans se connecter
   *   Étant donné la newsletter de veille activée
   *   Quand un visiteur non connecté ouvre la page des newsletters
   *   Alors il voit la liste des numéros parus, sans être renvoyé vers la connexion
   */
  test('anonymous visitor sees the newsletter archive', async ({ page }) => {
    await page.goto('/auth/logout');
    const response = await page.goto('/newsletter');
    test.skip(response.status() === 404, 'Newsletter de veille désactivée sur ce serveur');
    await checkNoPhpErrors(page);
    await expect(page).toHaveURL(/newsletter/);
    await expect(page.locator('input[name="password"]')).toHaveCount(0);
    await expect(page.locator('h3')).toBeVisible();
  });

  /**
   * Scénario: L'administrateur du club publie ses propres règles de confidentialité
   *   Étant donné un administrateur du club connecté
   *   Quand il ouvre la configuration depuis le tableau de bord d'administration du club
   *   Et il saisit le texte de ses règles de confidentialité en français puis enregistre
   *   Alors un message confirme l'enregistrement
   *   Et un visiteur non connecté lit ce texte, avec le nom du club inséré
   */
  test('club admin publishes a club-specific privacy policy', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto('/welcome/section/admin_club');
    await page.click('#card-parametres a');
    await expect(page).toHaveURL(/parametres/);
    await checkNoPhpErrors(page);

    await page.fill('#f-legal__confidentialite-french', `# ${MARKER}\n\nClub : {nom_club}`);
    await page.click('#parametres-save');
    await expect(page.locator('#parametres-saved')).toBeVisible();

    await page.goto('/auth/logout');
    await page.goto('/legal/confidentialite');
    await expect(page.locator('article.legal-page h1')).toHaveText(MARKER);
    await expect(page.locator('article.legal-page')).not.toContainText('{nom_club}');
  });

  /**
   * Scénario: Un simple membre n'a pas accès à la configuration
   *   Étant donné un membre sans responsabilité au club
   *   Quand il ouvre directement l'onglet des pages publiques de la configuration
   *   Alors un message lui indique qu'il n'a pas accès à cet onglet
   */
  test('member is refused access to the configuration', async ({ page }) => {
    await login(page, MEMBER_USER);
    await page.goto('/parametres/index/pages_publiques');
    await checkNoPhpErrors(page);
    await expect(page.locator('#parametres-message')).toBeVisible();
    await expect(page.locator('#parametres-form')).toHaveCount(0);
  });
});
