const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
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
  const select = page.locator(selector);
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

  return null;
}

test.describe('Vols decouverte - Creer et debiter', () => {
  test('gestion_vd voit le bouton "Creer et debiter" et il cree le vol + debite le compte 411', async ({ page }) => {
    await loginAs(page, 'idefix', 'password');

    await page.goto(VD_CREATE_URL);
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    const debitButton = page.locator('button[name="button"][value="create_and_debit"]');
    await expect(debitButton).toBeVisible();
    // Label includes the connected member's display name
    await expect(debitButton).toContainText('Idefix');

    const timestamp = Date.now();
    const beneficiaire = `PW VD DEBIT ${timestamp}`;

    await selectFirstNonEmptyOption(page, 'select[name="product"]');
    await page.fill('input[name="beneficiaire"]', beneficiaire);
    await page.fill('input[name="de_la_part"]', 'Playwright');
    await page.fill('input[name="beneficiaire_email"]', `pw-vd-debit-${timestamp}@example.test`);
    await page.fill('input[name="urgence"]', '0600000000');

    await debitButton.click();
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    // Success message shown on the redisplayed form
    await expect(page.locator('body')).toContainText('succès');
  });

  test('utilisateur sans compte 411 est bloqué avec un message clair', async ({ page }) => {
    await loginAs(page, 'testadmin', 'password');

    await page.goto(VD_CREATE_URL);
    await page.waitForLoadState('domcontentloaded');

    const debitButton = page.locator('button[name="button"][value="create_and_debit"]');
    await expect(debitButton).toBeVisible();

    const timestamp = Date.now();
    await selectFirstNonEmptyOption(page, 'select[name="product"]');
    await page.fill('input[name="beneficiaire"]', `PW VD NOACCOUNT ${timestamp}`);
    await page.fill('input[name="de_la_part"]', 'Playwright');
    await page.fill('input[name="beneficiaire_email"]', `pw-vd-noaccount-${timestamp}@example.test`);
    await page.fill('input[name="urgence"]', '0600000000');

    await debitButton.click();
    await page.waitForLoadState('domcontentloaded');
    await checkNoPhpErrors(page);

    await expect(page.locator('body')).toContainText('compte client (411)');
  });
});
