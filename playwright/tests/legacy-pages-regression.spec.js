/**
 * Non-régression de pages anciennes qui renvoyaient une erreur 500 et de
 * l'ordre de la balance :
 *  - reports : une requête SQL en échec plantait (db->error() absente de
 *    CodeIgniter 2) au lieu d'afficher l'erreur ;
 *  - comptes/resultat_categorie et membre/licences chargeaient des vues
 *    (header, sidebar, menu) qui n'existent plus ;
 *  - l'ordre des comptes de même codec et même nom (un par section) variait
 *    d'un export de balance à l'autre.
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const LoginPage = require('./helpers/LoginPage');

const ADMIN_USER = { username: 'testadmin', password: 'password' };

const DB_CONFIG = {
  host: 'localhost',
  user: 'gvv_user',
  password: 'lfoyfgbj',
  database: 'gvv2',
};

const BROKEN_REPORT = 'pw_broken_report';

test.describe('Legacy pages regression', () => {
  // Un seul worker : le rapport de test est créé/supprimé une seule fois
  test.describe.configure({ mode: 'serial' });

  let conn;

  test.beforeAll(async () => {
    conn = await mysql.createConnection(DB_CONFIG);
    await conn.execute('DELETE FROM reports WHERE nom = ?', [BROKEN_REPORT]);
    await conn.execute(
      'INSERT INTO reports (nom, titre, fields_list, align, width, landscape, `sql`) VALUES (?, ?, ?, ?, ?, ?, ?)',
      [BROKEN_REPORT, 'Rapport cassé (test)', 'a', 'left', '20', 0, 'select pw_no_such_column from membres']
    );
  });

  test.afterAll(async () => {
    if (conn) {
      await conn.execute('DELETE FROM reports WHERE nom = ?', [BROKEN_REPORT]);
      await conn.end();
    }
  });

  test.beforeEach(async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(ADMIN_USER.username, ADMIN_USER.password);
  });

  for (const action of ['execute', 'csv', 'pdf']) {
    test(`report with failing SQL: ${action} shows the error instead of a 500`, async ({ page }) => {
      const response = await page.goto(`/index.php/reports/${action}/${BROKEN_REPORT}`);
      expect(response.status()).toBe(200);
      const alert = page.locator('.alert-danger');
      await expect(alert).toContainText(BROKEN_REPORT);
      await expect(alert).toContainText('pw_no_such_column');
    });
  }

  for (const url of ['/index.php/comptes/resultat_categorie', '/index.php/membre/licences']) {
    test(`${url} renders`, async ({ page }) => {
      const response = await page.goto(url);
      expect(response.status()).toBe(200);
      await expect(page.locator('table').first()).toBeVisible();
      await expect(page.locator('body')).not.toContainText('A PHP Error');
    });
  }

  test('balance exports are identical from one call to the next (all sections)', async ({ page }) => {
    // Section "Toutes" : c'est là que coexistent les comptes de même codec et même nom
    const previous = await page.locator('select[name="section"]').inputValue().catch(() => null);
    await page.request.post('/index.php/user_roles_per_section/set_section', {
      form: { section: '0', current_url: '' },
    });
    try {
      for (const url of ['/index.php/comptes/balance_hierarchical_csv', '/index.php/comptes/balance_csv']) {
        const bodies = [];
        for (let i = 0; i < 4; i++) {
          const response = await page.request.get(url);
          expect(response.status()).toBe(200);
          bodies.push((await response.body()).toString());
        }
        for (const body of bodies) {
          expect(body).toBe(bodies[0]);
        }
      }
    } finally {
      if (previous !== null) {
        await page.request.post('/index.php/user_roles_per_section/set_section', {
          form: { section: previous, current_url: '' },
        });
      }
    }
  });
});
