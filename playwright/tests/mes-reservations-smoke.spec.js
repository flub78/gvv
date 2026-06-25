/**
 * Playwright smoke tests — Mes réservations & scheduler de rappels
 *
 * Tests:
 * - Accès à la page "Mes futures réservations"
 * - Page chargée sans erreur PHP
 * - Bouton "Ajouter une réservation" présent
 * - Formulaire de préférences de rappel visible
 * - Sauvegarde des préférences (canal SMS, délai 12h)
 * - URL publique scheduler : secret invalide → 403
 * - URL publique scheduler : secret valide → JSON avec clé "sent"
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/mes-reservations-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const fs   = require('fs');
const path = require('path');

function readSchedulerSecret() {
  const configPath = path.resolve(__dirname, '../../application/config/program.php');
  const content = fs.readFileSync(configPath, 'utf8');
  const match = content.match(/\$config\['reservation_scheduler_secret'\]\s*=\s*'([^']+)'/);
  if (!match) throw new Error('reservation_scheduler_secret not found in program.php');
  return match[1];
}

const SCHEDULER_SECRET = readSchedulerSecret();

const LOGIN_URL        = '/index.php/auth/login';
const MES_RESA_URL     = '/index.php/mes_reservations';
const SCHED_BAD_URL    = '/index.php/reservation_scheduler/run/BAD_SECRET';
const SCHED_GOOD_URL   = `/index.php/reservation_scheduler/run/${SCHEDULER_SECRET}`;
const SAVE_PREFS_URL   = '/index.php/mes_reservations/save_preferences';

const ADMIN_USER = { username: 'testadmin', password: 'password' };

async function login(page, user) {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');
  await page.fill('input[name="username"]', user.username);
  await page.fill('input[name="password"]', user.password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');
}

async function checkNoPhpErrors(page) {
  const body = await page.textContent('body');
  expect(body).not.toContain('Fatal error');
  expect(body).not.toContain('Parse error');
  expect(body).not.toContain('A PHP Error was encountered');
  expect(body).not.toContain('An uncaught Exception was encountered');
}

test.describe('Mes réservations — smoke tests', () => {

  test('page chargée sans erreur PHP', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(MES_RESA_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const title = await page.textContent('h5');
    expect(title).toContain('réservations');
  });

  test('bouton Ajouter une réservation présent', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(MES_RESA_URL);
    await page.waitForLoadState('networkidle');

    const btn = page.locator('a:has-text("Ajouter")');
    await expect(btn).toBeVisible();
  });

  test('formulaire préférences de rappel visible', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(MES_RESA_URL);
    await page.waitForLoadState('networkidle');

    await expect(page.locator('input[name="reminder_channel"]').first()).toBeVisible();
    await expect(page.locator('input[name="reminder_period_hours"]')).toBeVisible();
    await expect(page.locator('button:has-text("Enregistrer")')).toBeVisible();
  });

  test('sauvegarde préférences canal sms et délai 12h', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(MES_RESA_URL);
    await page.waitForLoadState('networkidle');

    await page.check('input[name="reminder_channel"][value="sms"]');
    await page.fill('input[name="reminder_period_hours"]', '12');
    await page.click('button:has-text("Enregistrer")');
    await page.waitForLoadState('networkidle');

    await checkNoPhpErrors(page);
    const body = await page.textContent('body');
    expect(body).toContain('Préférences enregistrées');

    // Restore default
    await page.check('input[name="reminder_channel"][value="email"]');
    await page.fill('input[name="reminder_period_hours"]', '24');
    await page.click('button:has-text("Enregistrer")');
    await page.waitForLoadState('networkidle');
  });

});

test.describe('Scheduler de rappels — smoke tests', () => {

  test('URL publique avec secret invalide retourne 403', async ({ page }) => {
    const response = await page.goto(SCHED_BAD_URL);
    expect(response.status()).toBe(403);
    const body = await page.textContent('body');
    expect(body).toContain('Forbidden');
  });

  test('URL publique avec secret valide retourne JSON avec clé sent', async ({ page }) => {
    const response = await page.goto(SCHED_GOOD_URL);
    expect(response.status()).toBe(200);

    const text = await page.textContent('body');
    let json;
    try {
      json = JSON.parse(text);
    } catch {
      throw new Error('Réponse non JSON : ' + text.substring(0, 200));
    }
    expect(json).toHaveProperty('sent');
    expect(json).toHaveProperty('source', 'public_url');
  });

});
