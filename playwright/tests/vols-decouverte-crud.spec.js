const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const LOGOUT_URL = '/index.php/auth/logout';
const VD_LIST_URL = '/index.php/vols_decouverte/page';
const VD_CREATE_URL = '/index.php/vols_decouverte/create';

async function loginAs(page, username, password) {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('domcontentloaded');
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('domcontentloaded');
}

async function checkNoPhpErrors(page) {
  const bodyText = await page.textContent('body');
  expect(bodyText).not.toContain('Fatal error');
  expect(bodyText).not.toContain('Parse error');
  expect(bodyText).not.toContain('A PHP Error was encountered');
  expect(bodyText).not.toContain('An uncaught Exception was encountered');
}

async function selectFirstNonEmptyOption(page, selector) {
  const options = page.locator(`${selector} option`);
  const count = await options.count();

  for (let i = 0; i < count; i += 1) {
    const option = options.nth(i);
    const value = (await option.getAttribute('value')) || '';
    if (value.trim() !== '') {
      await page.selectOption(selector, value);
      return value;
    }
  }

  throw new Error(`No non-empty option found for selector: ${selector}`);
}

test.describe('Vols decouverte CRUD (gestionnaire)', () => {
  test('should create, list, edit and delete a discovery flight', async ({ page }) => {
    await loginAs(page, 'testadmin', 'password');

    const timestamp = Date.now();
    const beneficiaire = `PW VD ${timestamp}`;
    const updatedBeneficiaire = `PW VD MAJ ${timestamp}`;

    // CREATE
    await page.goto(VD_CREATE_URL);
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    await expect(page.locator('form[name="saisie"]')).toBeVisible();
    await expect(page.locator('input[name="beneficiaire"]')).toBeVisible();
    await expect(page.locator('select[name="product"]')).toBeVisible();

    await selectFirstNonEmptyOption(page, 'select[name="product"]');
    await page.fill('input[name="beneficiaire"]', beneficiaire);
    await page.fill('input[name="de_la_part"]', 'Playwright');
    await page.fill('input[name="beneficiaire_email"]', `pw-vd-${timestamp}@example.test`);
    await page.fill('input[name="urgence"]', '0600000000');

    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    // READ/LIST: find created row and get ID from edit link
    await page.goto(VD_LIST_URL);
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    const createdRow = page.locator('table tr', { hasText: beneficiaire }).first();
    await expect(createdRow).toBeVisible();

    const editLink = createdRow.locator('a[href*="/vols_decouverte/edit/"]').first();
    await expect(editLink).toBeVisible();

    const editHref = await editLink.getAttribute('href');
    expect(editHref).toBeTruthy();

    const idMatch = editHref.match(/\/vols_decouverte\/edit\/(\d+)/);
    expect(idMatch).toBeTruthy();
    const vdId = idMatch[1];

    // UPDATE
    await page.goto(`/index.php/vols_decouverte/edit/${vdId}`);
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    await expect(page.locator('input[name="beneficiaire"]')).toBeVisible();
    await page.fill('input[name="beneficiaire"]', updatedBeneficiaire);

    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    await page.goto(VD_LIST_URL);
    await page.waitForLoadState('domcontentloaded');

    const updatedRow = page.locator('table tr', { hasText: updatedBeneficiaire }).first();
    await expect(updatedRow).toBeVisible();

    // DELETE
    const deleteLink = updatedRow.locator('a[href*="/vols_decouverte/delete/"]').first();
    await expect(deleteLink).toBeVisible();

    const deleteHref = await deleteLink.getAttribute('href');
    expect(deleteHref).toBeTruthy();

    await page.goto(deleteHref);
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    await page.goto(VD_LIST_URL);
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('table tr', { hasText: updatedBeneficiaire })).toHaveCount(0);
  });
});

test.describe('Vols decouverte - droits pilote_vd', () => {
  let vdActionUrl;
  let vdPreFlightUrl;
  let vdDoneUrl;

  // Create a fresh non-expired VD as admin for pilot tests
  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();
    await loginAs(page, 'testadmin', 'password');

    const timestamp = Date.now();
    const beneficiaire = `PW PILOT ${timestamp}`;

    await page.goto(VD_CREATE_URL);
    await page.waitForLoadState('domcontentloaded');
    await selectFirstNonEmptyOption(page, 'select[name="product"]');
    await page.fill('input[name="beneficiaire"]', beneficiaire);
    await page.fill('input[name="de_la_part"]', 'Test pilote');
    await page.fill('input[name="beneficiaire_email"]', `pw-pilot-${timestamp}@example.test`);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('domcontentloaded');

    // Get the obfuscated action URL from the list
    await page.goto(VD_LIST_URL);
    await page.waitForLoadState('domcontentloaded');

    const createdRow = page.locator('table tr', { hasText: beneficiaire }).first();
    const actionLink = createdRow.locator('a[href*="/vols_decouverte/action/"]').first();
    const actionHref = await actionLink.getAttribute('href');
    vdActionUrl = actionHref.replace(/^.*\/index\.php/, '/index.php');

    // Derive pre_flight and done URLs from the action URL
    const obfuscatedId = actionHref.split('/action/')[1];
    vdPreFlightUrl = `/index.php/vols_decouverte/pre_flight/${obfuscatedId}`;
    vdDoneUrl = `/index.php/vols_decouverte/done/${obfuscatedId}`;

    await page.close();
  });

  test('pilote_vd voit la liste et les liens action mais pas les boutons créer/modifier/supprimer', async ({ page }) => {
    await loginAs(page, 'agecanonix', 'password');

    await page.goto(VD_LIST_URL);
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    // La liste est visible
    await expect(page.locator('table.datatable')).toBeVisible();

    // Pas de bouton créer
    await expect(page.locator('a[href*="/vols_decouverte/create"]')).toHaveCount(0);

    // Pas de lien modifier ni supprimer
    await expect(page.locator('a[href*="/vols_decouverte/edit/"]')).toHaveCount(0);
    await expect(page.locator('a[href*="/vols_decouverte/delete/"]')).toHaveCount(0);

    // Des liens action sont présents
    await expect(page.locator('a[href*="/vols_decouverte/action/"]').first()).toBeVisible();
  });

  test('pilote_vd accède au menu action avec boutons pré-vol et post-vol', async ({ page }) => {
    await loginAs(page, 'agecanonix', 'password');

    await page.goto(vdActionUrl);
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    // Les boutons pré-vol et post-vol sont visibles
    await expect(page.locator(`a[href*="/vols_decouverte/pre_flight/"]`)).toBeVisible();
    await expect(page.locator(`a[href*="/vols_decouverte/done/"]`)).toBeVisible();
  });

  test('pilote_vd peut modifier le contact urgence (pre_flight)', async ({ page }) => {
    await loginAs(page, 'agecanonix', 'password');

    await page.goto(vdPreFlightUrl);
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    // Seul le champ urgence est éditable
    await expect(page.locator('input[name="urgence"]')).toBeVisible();
    await page.fill('input[name="urgence"]', '0611223344');

    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);
  });

  test('pilote_vd peut enregistrer les infos du vol (done)', async ({ page }) => {
    await loginAs(page, 'agecanonix', 'password');

    await page.goto(vdDoneUrl);
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    // Les champs date_vol, pilote, airplane_immat sont visibles
    await expect(page.locator('input[name="date_vol"], input[name="date_vol"]')).toBeVisible();

    // date_vol must be in the past (validation rejects future dates)
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const dateVolStr = yesterday.toISOString().split('T')[0];
    await page.fill('input[name="date_vol"]', dateVolStr);

    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);
  });

  test('pilote_vd peut accéder à vols_decouverte/create pour payer par CB', async ({ page }) => {
    await loginAs(page, 'agecanonix', 'password');

    const response = await page.goto(VD_CREATE_URL);
    await page.waitForLoadState('domcontentloaded');

    // pilote_vd peut accéder au formulaire de création (pour payer par CB)
    expect(response.status()).toBe(200);
    // Le bouton "Créer" n'est pas visible pour pilote_vd
    const createButton = page.locator('input[type="submit"][name="button"]');
    await expect(createButton).toHaveCount(0);
  });

  test('pilote_vd peut accéder au briefing passager', async ({ page }) => {
    await loginAs(page, 'agecanonix', 'password');

    // Get VD ID from the action URL
    const obfuscatedId = vdActionUrl.split('/action/')[1];
    // Navigate to briefing index (linked from list)
    await page.goto('/index.php/briefing_passager');
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    // The briefing page should be accessible (not 404)
    await expect(page.locator('body')).not.toContainText('404');
  });

  test('pilote_vd: date_vol dans le futur est rejetée avec message d\'erreur', async ({ page }) => {
    await loginAs(page, 'agecanonix', 'password');

    await page.goto(vdDoneUrl);
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const futureDateStr = tomorrow.toISOString().split('T')[0];
    await page.fill('input[name="date_vol"]', futureDateStr);

    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    // Validation error should appear
    await expect(page.locator('body')).toContainText("Ce n'est pas une date de planification");
  });
});
