const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const VD_LIST_URL = '/index.php/vols_decouverte/page';
const VD_CREATE_URL = '/index.php/vols_decouverte/create';

const TEST_USER = {
  username: 'testadmin',
  password: 'password',
};

async function login(page) {
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');

  await page.fill('input[name="username"]', TEST_USER.username);
  await page.fill('input[name="password"]', TEST_USER.password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');
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

test.describe('Vols decouverte CRUD', () => {
  test('should create, list, edit and delete a discovery flight', async ({ page }) => {
    await login(page);

    const timestamp = Date.now();
    const beneficiaire = `PW VD ${timestamp}`;
    const updatedBeneficiaire = `PW VD MAJ ${timestamp}`;

    // CREATE
    await page.goto(VD_CREATE_URL);
    await page.waitForLoadState('networkidle');
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
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    // READ/LIST: find created row and get ID from edit link
    await page.goto(VD_LIST_URL);
    await page.waitForLoadState('networkidle');
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
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    await expect(page.locator('input[name="beneficiaire"]')).toBeVisible();
    await page.fill('input[name="beneficiaire"]', updatedBeneficiaire);

    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    await page.goto(VD_LIST_URL);
    await page.waitForLoadState('networkidle');

    const updatedRow = page.locator('table tr', { hasText: updatedBeneficiaire }).first();
    await expect(updatedRow).toBeVisible();

    // DELETE
    const deleteLink = updatedRow.locator('a[href*="/vols_decouverte/delete/"]').first();
    await expect(deleteLink).toBeVisible();

    const deleteHref = await deleteLink.getAttribute('href');
    expect(deleteHref).toBeTruthy();

    await page.goto(deleteHref);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    await page.goto(VD_LIST_URL);
    await page.waitForLoadState('networkidle');

    await expect(page.locator('table tr', { hasText: updatedBeneficiaire })).toHaveCount(0);
  });
});
