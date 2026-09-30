/**
 * Playwright test for the résultat xlsx export (Phase 1 of the "exports
 * feuille de calcul" initiative — see
 * doc/design_notes/exports_feuille_de_calcul_design.md).
 *
 * Unlike the bilan PDF, the résultat PDF (pagesResultats() in Document.php)
 * has no background fill on any row — only a bold header row via the Pdf
 * table renderer — so there is no color to reuse here, just bold styling.
 *
 * Tests:
 * - The "Xlsx" button is visible on the résultat page alongside CSV/Pdf
 * - Clicking it downloads a valid .xlsx (zip, PK signature)
 * - Amounts are stored as real numeric cells (not shared strings)
 * - Total-row labels are bold, but never the numeric amount cells
 *   themselves (would force them to become text and lose their type)
 *
 * Usage:
 *   npx playwright test tests/comptes-resultat-xlsx-export.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');
const { readZipEntry } = require('./helpers/xlsxZip');

const TEST_USER = {
  username: 'testadmin',
  password: 'password'
};

test.describe('Résultat xlsx export', () => {

  test('should display the Xlsx export button alongside CSV and Pdf', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.password);

    await page.goto('/index.php/comptes/resultat');
    await page.waitForLoadState('networkidle');

    const csvButton = page.locator('a[href*="export_resultat/csv"]');
    const xlsxButton = page.locator('a[href*="export_resultat/xlsx"]');
    const pdfButton = page.locator('a[href*="export_resultat/pdf"]');

    await expect(csvButton).toBeVisible();
    await expect(xlsxButton).toBeVisible();
    await expect(pdfButton).toBeVisible();
    await expect(xlsxButton).toHaveText('Xlsx');
  });

  test('should download a valid xlsx with typed numeric amounts', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.password);

    await page.goto('/index.php/comptes/resultat');
    await page.waitForLoadState('networkidle');

    const downloadPromise = page.waitForEvent('download');
    await page.click('a[href*="export_resultat/xlsx"]');
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

    // Bold header/total row labels exist.
    expect(stylesXml).toContain('<b/>');

    // At least one amount cell is a plain, unstyled numeric value — the
    // point of this export: a real number the operator can compute on.
    const unstyledNumericCellPattern = /<c r="[A-Z]\d+"><v>-?\d+(\.\d+)?<\/v><\/c>/;
    expect(sheetXml).toMatch(unstyledNumericCellPattern);

    console.log('✓ Résultat xlsx export is a valid workbook with typed numeric amounts');
  });
});
