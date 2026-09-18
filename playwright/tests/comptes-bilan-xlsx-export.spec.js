/**
 * Playwright test for the bilan xlsx export (Phase 1 of the "exports feuille
 * de calcul" initiative — see doc/design_notes/exports_feuille_de_calcul_design.md).
 *
 * Tests:
 * - The "Xlsx" button is visible on the bilan page alongside CSV/Pdf
 * - Clicking it downloads a valid .xlsx (zip, PK signature)
 * - At least one monetary amount is stored as a real numeric cell (not a
 *   shared string), which is the whole point of this export: letting the
 *   operator compute on the exported data directly in the spreadsheet.
 *
 * Usage:
 *   npx playwright test tests/comptes-bilan-xlsx-export.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');
const { readZipEntry } = require('./helpers/xlsxZip');

const TEST_USER = {
  username: 'testadmin',
  password: 'password'
};

test.describe('Bilan xlsx export', () => {

  test('should display the Xlsx export button alongside CSV and Pdf', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.password);

    await page.goto('/index.php/comptes/bilan');
    await page.waitForLoadState('networkidle');

    const csvButton = page.locator('a[href*="export_bilan/csv"]');
    const xlsxButton = page.locator('a[href*="export_bilan/xlsx"]');
    const pdfButton = page.locator('a[href*="export_bilan/pdf"]');

    await expect(csvButton).toBeVisible();
    await expect(xlsxButton).toBeVisible();
    await expect(pdfButton).toBeVisible();
    await expect(xlsxButton).toHaveText('Xlsx');
  });

  test('should download a valid xlsx with typed numeric amounts', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.password);

    await page.goto('/index.php/comptes/bilan');
    await page.waitForLoadState('networkidle');

    const downloadPromise = page.waitForEvent('download');
    await page.click('a[href*="export_bilan/xlsx"]');
    const download = await downloadPromise;

    const filePath = await download.path();
    expect(filePath).toBeTruthy();

    const fs = require('fs');
    const buffer = fs.readFileSync(filePath);

    // A valid xlsx is a zip archive (PK signature) with meaningful content.
    expect(buffer.slice(0, 2).toString('ascii')).toBe('PK');
    expect(buffer.length).toBeGreaterThan(1000);
    expect(download.suggestedFilename()).toMatch(/\.xlsx$/);

    // Unzip and check the worksheet: at least one cell must be a plain
    // numeric value (<c r="..."><v>1234.56</v></c>, no t="s" attribute),
    // i.e. a real number the operator can sum/compute on — not text.
    const sheetXml = readZipEntry(filePath, 'xl/worksheets/sheet1.xml');
    const numericCellPattern = /<c r="[A-Z]\d+"(?: s="\d+")?><v>\d+(\.\d+)?<\/v><\/c>/;
    expect(sheetXml).toMatch(numericCellPattern);

    // Bold styling exists (fontId=1 with <b/> in styles.xml) for
    // section/total row labels.
    const stylesXml = readZipEntry(filePath, 'xl/styles.xml');
    expect(stylesXml).toContain('<b/>');

    // Background colors reused from the PDF (pagesBilan() in Document.php):
    // Bootstrap table-primary (CFE2FF) for column headers / grand totals,
    // table-secondary (E2E3E5) for section headers / subtotals.
    expect(stylesXml).toContain('CFE2FF');
    expect(stylesXml).toContain('E2E3E5');

    // A numeric amount cell must never carry a fill style (it would force
    // the cell to become a text string and lose its numeric type) — check
    // that at least one numeric cell has no style ("s=") attribute at all.
    const unstyledNumericCellPattern = /<c r="[A-Z]\d+"><v>\d+(\.\d+)?<\/v><\/c>/;
    expect(sheetXml).toMatch(unstyledNumericCellPattern);

    console.log('✓ Bilan xlsx export is a valid workbook with typed numeric amounts and PDF-matching background colors');
  });
});
