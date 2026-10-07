/**
 * Playwright test for the xlsx export of the simple list pages (Phase 2 of
 * the "exports feuille de calcul" initiative — see
 * doc/design_notes/exports_feuille_de_calcul_design.md).
 *
 * For each list page that already had a working CSV export:
 * - the "Xlsx" button is displayed next to the CSV one
 * - clicking it downloads a valid .xlsx (zip, PK signature) with a frozen
 *   header
 *
 * Usage:
 *   npx playwright test tests/list-xlsx-exports.spec.js
 */

const { test, expect } = require('@playwright/test');
const fs = require('fs');
const LoginPage = require('./helpers/LoginPage');
const { readZipEntry } = require('./helpers/xlsxZip');

const TEST_USER = {
  username: 'testadmin',
  password: 'password'
};

// page: list page URL; link: fragment of the Xlsx button href
const LIST_PAGES = [
  { name: 'avions', page: '/index.php/avion/page', link: 'avion/export/xlsx' },
  { name: 'planeurs', page: '/index.php/planeur/page', link: 'planeur/export/xlsx' },
  { name: 'sections', page: '/index.php/sections/page', link: 'sections/export/xlsx' },
  { name: 'terrains', page: '/index.php/terrains/page', link: 'terrains/export/xlsx' },
  { name: 'plan comptable', page: '/index.php/plan_comptable/page', link: 'plan_comptable/export/xlsx' },
  { name: 'vols de découverte', page: '/index.php/vols_decouverte/page', link: 'vols_decouverte/export/xlsx' },
  { name: 'membres', page: '/index.php/membre/page', link: 'membre/export/xlsx' },
  { name: 'tickets', page: '/index.php/tickets/page', link: 'tickets/export/xlsx' },
  { name: 'soldes tickets', page: '/index.php/tickets/solde', link: 'tickets/solde/xlsx' },
  { name: 'vols planeur', page: '/index.php/vols_planeur/page', link: 'vols_planeur/xlsx/' },
  { name: 'vols avion', page: '/index.php/vols_avion/page', link: 'vols_avion/xlsx/' },
  { name: 'comptes', page: '/index.php/comptes/page', link: 'comptes/balance_xlsx' },
  { name: 'relances', page: '/index.php/relances', link: 'relances/export_xlsx' },
  { name: 'licences par année', page: '/index.php/licences/per_year', link: 'licences/per_year_detail_xlsx' },
];

async function checkXlsxDownload(page, link) {
  const csvLink = link.replace('xlsx', 'csv');
  await expect(page.locator(`a[href*="${csvLink}"]`).first()).toBeVisible();
  const xlsxButton = page.locator(`a[href*="${link}"]`).first();
  await expect(xlsxButton).toBeVisible();

  const downloadPromise = page.waitForEvent('download');
  await xlsxButton.click();
  const download = await downloadPromise;

  const filePath = await download.path();
  expect(filePath).toBeTruthy();
  expect(fs.readFileSync(filePath).slice(0, 2).toString('ascii')).toBe('PK');
  expect(download.suggestedFilename()).toMatch(/\.xlsx$/);

  const sheetXml = readZipEntry(filePath, 'xl/worksheets/sheet1.xml');
  expect(sheetXml).toMatch(/<pane [^>]*state="frozen"/);
  return sheetXml;
}

test.describe('List pages xlsx exports', () => {

  test.beforeEach(async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.password);
  });

  for (const list of LIST_PAGES) {
    test(`${list.name}: Xlsx button downloads a valid workbook`, async ({ page }) => {
      await page.goto(list.page);
      await page.waitForLoadState('networkidle');
      await checkXlsxDownload(page, list.link);
    });
  }

  test('carnets de route: Xlsx export of the continuity check', async ({ page }) => {
    await page.goto('/index.php/carnets_route/page');
    await page.waitForLoadState('networkidle');

    // Select the first aircraft in the filter, as a user would
    const machine = page.locator('select[name="carnet_macid"]');
    const options = await machine.locator('option').evaluateAll(opts => opts.map(o => o.value).filter(v => v));
    test.skip(options.length === 0, 'No aircraft available');
    await machine.selectOption(options[0]);
    await page.locator('form[action*="carnets_route/filter"] [type="submit"]').first().click();
    await page.waitForLoadState('networkidle');

    const xlsxButton = page.locator('a[href*="carnets_route/xlsx"]');
    await expect(xlsxButton).toBeVisible();
    const downloadPromise = page.waitForEvent('download');
    await xlsxButton.click();
    const download = await downloadPromise;
    const filePath = await download.path();
    expect(fs.readFileSync(filePath).slice(0, 2).toString('ascii')).toBe('PK');
  });
});
