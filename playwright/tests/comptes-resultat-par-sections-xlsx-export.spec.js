/**
 * Playwright test for the "résultat par sections" xlsx exports (Phase 1 of
 * the "exports feuille de calcul" initiative — see
 * doc/design_notes/exports_feuille_de_calcul_design.md).
 *
 * Tests:
 * - The "Xlsx" button is visible on the résultat par sections page and on
 *   its per-codec detail page, alongside CSV/Pdf
 * - The main export is a valid .xlsx with one sheet per table (charges,
 *   produits, total), frozen two-line headers and section names merged
 *   above their year columns
 * - Amounts are stored as real numeric cells (not shared strings)
 * - The detail export is a valid .xlsx with typed numeric amounts
 *
 * Usage:
 *   npx playwright test tests/comptes-resultat-par-sections-xlsx-export.spec.js
 */

const { test, expect } = require('@playwright/test');
const fs = require('fs');
const LoginPage = require('./helpers/LoginPage');
const { readZipEntry } = require('./helpers/xlsxZip');

const TEST_USER = {
  username: 'testadmin',
  password: 'password'
};

const unstyledNumericCellPattern = /<c r="[A-Z]+\d+"><v>-?\d+(\.\d+)?<\/v><\/c>/;

async function downloadFrom(page, selector) {
  const downloadPromise = page.waitForEvent('download');
  await page.click(selector);
  const download = await downloadPromise;
  const filePath = await download.path();
  expect(filePath).toBeTruthy();
  expect(fs.readFileSync(filePath).slice(0, 2).toString('ascii')).toBe('PK');
  expect(download.suggestedFilename()).toMatch(/\.xlsx$/);
  return filePath;
}

test.describe('Résultat par sections xlsx export', () => {

  test.beforeEach(async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.password);
  });

  test('should download a workbook with one sheet per table and typed amounts', async ({ page }) => {
    await page.goto('/index.php/comptes/resultat_par_sections');
    await page.waitForLoadState('networkidle');

    const xlsxButton = page.locator('a[href$="resultat_par_sections/xlsx"]');
    await expect(page.locator('a[href$="resultat_par_sections/csv"]')).toBeVisible();
    await expect(page.locator('a[href$="resultat_par_sections/pdf"]')).toBeVisible();
    await expect(xlsxButton).toBeVisible();
    await expect(xlsxButton).toHaveText('Xlsx');

    const filePath = await downloadFrom(page, 'a[href$="resultat_par_sections/xlsx"]');

    const workbookXml = readZipEntry(filePath, 'xl/workbook.xml');
    expect((workbookXml.match(/<sheet /g) || []).length).toBe(3);

    for (const sheet of ['sheet1', 'sheet2', 'sheet3']) {
      const sheetXml = readZipEntry(filePath, `xl/worksheets/${sheet}.xml`);
      // Two-line header (sections / years) frozen below the title rows
      expect(sheetXml).toMatch(/<pane [^>]*ySplit="5"[^>]*state="frozen"/);
      expect(sheetXml).toMatch(unstyledNumericCellPattern);
    }

    // Charges sheet: section names merged above their two year columns
    const chargesXml = readZipEntry(filePath, 'xl/worksheets/sheet1.xml');
    expect(chargesXml).toMatch(/<mergeCell ref="C4:D4"\/>/);

    console.log('✓ Résultat par sections xlsx export: 3 sheets, frozen headers, typed amounts');
  });

  test('CSV total table keeps amounts aligned under their section headers', async ({ page }) => {
    // Regression: the total table header has no Code column, but its rows
    // used to keep an empty one, shifting every amount one column right.
    const response = await page.request.get('/index.php/comptes/resultat_par_sections/csv');
    expect(response.ok()).toBeTruthy();
    const lines = (await response.text()).split(/\r?\n/);

    const totalIdx = lines.findIndex(l => /^Total;?$/.test(l.trim()));
    expect(totalIdx).toBeGreaterThan(0);
    const yearsHeader = lines[totalIdx + 2].split(';');
    const firstRow = lines[totalIdx + 3].split(';');

    expect(firstRow.length).toBe(yearsHeader.length);
    // First year header cell sits above the first amount, not above the label
    expect(yearsHeader[1]).toMatch(/^\d{4}$/);
    expect(firstRow[1]).toMatch(/^-?[\d\s ]+,\d{2}$/);
  });

  test('should download the per-codec detail as xlsx with typed amounts', async ({ page }) => {
    await page.goto('/index.php/comptes/resultat_par_sections');
    await page.waitForLoadState('networkidle');

    // Follow the first detail link rather than assuming a codec exists
    const detailLink = page.locator('a[href*="resultat_par_sections_detail/"]').first();
    test.skip(await detailLink.count() === 0, 'No account line in the résultat par sections');
    await page.goto(await detailLink.getAttribute('href'));
    await page.waitForLoadState('networkidle');

    const xlsxButton = page.locator('a[href*="resultat_par_sections_detail/"][href$="/xlsx"]');
    await expect(xlsxButton).toBeVisible();
    await expect(xlsxButton).toHaveText('Xlsx');

    const filePath = await downloadFrom(page, 'a[href*="resultat_par_sections_detail/"][href$="/xlsx"]');
    const sheetXml = readZipEntry(filePath, 'xl/worksheets/sheet1.xml');
    expect(sheetXml).toMatch(/<pane [^>]*ySplit="5"[^>]*state="frozen"/);
    expect(sheetXml).toMatch(unstyledNumericCellPattern);

    console.log('✓ Résultat par sections detail xlsx export is a valid workbook with typed amounts');
  });
});
