/**
 * Playwright Tests - Formation access control
 *
 * Regression test: a pilot could change the inscription id in the URL of
 * their progression sheet (formation_inscriptions/detail/<id>) and read the
 * training data of another pilot. Formation pages are now restricted to the
 * pilot themselves or to instructors / CA / RP / admins.
 *
 * Uses read-only access: no data is created or modified.
 *
 * Prerequisites:
 *   - Feature flag gestion_formations enabled
 *   - abraracourcix (instructeur) and asterix (simple user) test users
 *     (see bin/create_test_users.sh)
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/formation/access-control.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const INSTRUCTEUR = { username: 'abraracourcix', password: 'password' };
const PILOTE = { username: 'asterix', password: 'password' };

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

async function inscriptionIds(page, url) {
  await page.goto(url);
  await page.waitForLoadState('networkidle');
  const hrefs = await page.locator('a[href*="formation_inscriptions/detail/"]')
    .evaluateAll(links => links.map(a => a.getAttribute('href')));
  return [...new Set(hrefs.map(h => h.match(/detail\/(\d+)/)[1]))];
}

test.describe('Formation access control', () => {

  test('a pilot cannot read the formation of another pilot', async ({ page }) => {
    await login(page, INSTRUCTEUR);
    const allIds = await inscriptionIds(page, '/index.php/formation_inscriptions');

    await login(page, PILOTE);
    const ownIds = await inscriptionIds(page, '/index.php/formation_progressions/mes_formations');

    const otherId = allIds.find(id => !ownIds.includes(id));
    test.skip(!otherId, 'No inscription of another pilot found');

    for (const url of [
      `/index.php/formation_inscriptions/detail/${otherId}`,
      `/index.php/formation_progressions/fiche/${otherId}`,
      `/index.php/formation_progressions/export_pdf/${otherId}`,
    ]) {
      const response = await page.goto(url);
      expect(response.status(), url).toBe(403);
    }

    // The pilot still has access to their own formations
    if (ownIds.length > 0) {
      const response = await page.goto(`/index.php/formation_inscriptions/detail/${ownIds[0]}`);
      expect(response.status()).toBe(200);
    }
  });

  test('a simple pilot cannot access the formation management pages', async ({ page }) => {
    await login(page, PILOTE);

    for (const url of [
      '/index.php/formation_inscriptions',
      '/index.php/formation_inscriptions/ouvrir',
      '/index.php/formation_progressions',
      '/index.php/formation_seances',
      '/index.php/formation_seances/libres',
      '/index.php/formation_seances_theoriques',
      '/index.php/formation_rapports',
    ]) {
      const response = await page.goto(url);
      expect(response.status(), url).toBe(403);
    }

    const response = await page.goto('/index.php/formation_progressions/mes_formations');
    expect(response.status()).toBe(200);
  });

  test('an instructor can still access the formation pages', async ({ page }) => {
    await login(page, INSTRUCTEUR);
    const allIds = await inscriptionIds(page, '/index.php/formation_inscriptions');
    test.skip(allIds.length === 0, 'No inscription found');

    for (const url of [
      `/index.php/formation_inscriptions/detail/${allIds[0]}`,
      `/index.php/formation_progressions/fiche/${allIds[0]}`,
      '/index.php/formation_seances',
      '/index.php/formation_rapports',
    ]) {
      const response = await page.goto(url);
      expect(response.status(), url).toBe(200);
    }
  });
});
