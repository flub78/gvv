/**
 * Playwright test for the "résultat avec dépréciation" xlsx export (Phase 1
 * of the "exports feuille de calcul" initiative — see
 * doc/design_notes/exports_feuille_de_calcul_design.md).
 *
 * Like résultat, the PDF (pagesResultatsAvecDepreciation() in Document.php)
 * has no background fill — only a bold header row — so no color to reuse.
 * Unlike résultat, this report has two separate totals/bénéfice blocks
 * (avant/après dépréciations) at data-dependent positions, so bold rows are
 * identified by content (matching known total/title labels) rather than by
 * row position — see resultat_avec_depreciation_xlsx() in comptes.php.
 *
 * Usage:
 *   npx playwright test tests/comptes-resultat-avec-depreciation-xlsx-export.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');
const { readZipEntry } = require('./helpers/xlsxZip');

const TEST_USER = {
  username: 'testadmin',
  password: 'password'
};

test.describe('Résultat avec dépréciation xlsx export', () => {

  test('should display the Xlsx export button alongside CSV and Pdf', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.password);

    await page.goto('/index.php/comptes/resultat_avec_depreciation');
    await page.waitForLoadState('networkidle');

    const csvButton = page.locator('a[href*="export_resultat_avec_depreciation/csv"]');
    const xlsxButton = page.locator('a[href*="export_resultat_avec_depreciation/xlsx"]');
    const pdfButton = page.locator('a[href*="export_resultat_avec_depreciation/pdf"]');

    await expect(csvButton).toBeVisible();
    await expect(xlsxButton).toBeVisible();
    await expect(pdfButton).toBeVisible();
    await expect(xlsxButton).toHaveText('Xlsx');
  });

  test('should download a valid xlsx with typed numeric amounts', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.password);

    await page.goto('/index.php/comptes/resultat_avec_depreciation');
    await page.waitForLoadState('networkidle');

    const downloadPromise = page.waitForEvent('download');
    await page.click('a[href*="export_resultat_avec_depreciation/xlsx"]');
    const download = await downloadPromise;

    const filePath = await download.path();
    expect(filePath).toBeTruthy();

    const fs = require('fs');
    const buffer = fs.readFileSync(filePath);

    expect(buffer.slice(0, 2).toString('ascii')).toBe('PK');
    expect(buffer.length).toBeGreaterThan(1000);
    expect(download.suggestedFilename()).toMatch(/\.xlsx$/);

    const sheetXml = readZipEntry(filePath, 'xl/worksheets/sheet1.xml');
    const stylesXml = readZipEntry(filePath, 'xl/styles.xml');

    // Bold header/total/title row labels exist.
    expect(stylesXml).toContain('<b/>');

    // At least one amount cell is a plain, unstyled numeric value.
    const unstyledNumericCellPattern = /<c r="[A-Z]\d+"><v>-?\d+(\.\d+)?<\/v><\/c>/;
    expect(sheetXml).toMatch(unstyledNumericCellPattern);

    console.log('✓ Résultat avec dépréciation xlsx export is a valid workbook with typed numeric amounts');
  });
});
