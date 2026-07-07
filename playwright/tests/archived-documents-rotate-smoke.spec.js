/**
 * Playwright smoke test — File_rotator refactor (Lot 9, étape 2)
 *
 * Vérifie que Archived_documents::rotate() fonctionne toujours après la
 * délégation à File_rotator (aucun test n'existait sur ce bouton avant).
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/archived-documents-rotate-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const ADMIN_USER = { username: 'testadmin', password: 'password' };

// Document image existant (id=1, image/gif) — sauvegardé/restauré manuellement autour de ce test.
const DOCUMENT_VIEW_URL = '/index.php/archived_documents/view/1';

async function login(page, user) {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');
  await page.fill('input[name="username"]', user.username);
  await page.fill('input[name="password"]', user.password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');
}

function checkNoPhpErrors(body) {
  expect(body).not.toContain('Fatal error');
  expect(body).not.toContain('Parse error');
  expect(body).not.toContain('A PHP Error was encountered');
  expect(body).not.toContain('An uncaught Exception was encountered');
}

test('admin can rotate an image document via File_rotator', async ({ page }) => {
  await login(page, ADMIN_USER);

  await page.goto(DOCUMENT_VIEW_URL);
  await page.waitForLoadState('networkidle');
  checkNoPhpErrors(await page.textContent('body'));

  const rotateCw = page.locator('a[href*="/archived_documents/rotate/1/cw"]');
  await expect(rotateCw).toHaveCount(1);

  page.once('dialog', (dialog) => dialog.accept());
  await rotateCw.click();
  await page.waitForLoadState('networkidle');

  const body = await page.textContent('body');
  checkNoPhpErrors(body);
  expect(body).toContain('pivoté');

  console.log('Rotation via File_rotator succeeded with expected success message');
});
