/**
 * Playwright test for the hierarchical balance xlsx export (Phase 1 of the
 * "exports feuille de calcul" initiative — see
 * doc/design_notes/exports_feuille_de_calcul_design.md).
 *
 * Tests:
 * - The "Xlsx" button is visible on the balance page alongside CSV/Pdf
 * - Clicking it downloads a valid .xlsx (zip, PK signature) with a frozen
 *   header row
 * - Balances are stored as real numeric cells (not shared strings)
 * - General account / total labels are bold, never the amounts
 *
 * Usage:
 *   npx playwright test tests/comptes-balance-xlsx-export.spec.js
 */

const { test, expect } = require('@playwright/test');
const fs = require('fs');
const LoginPage = require('./helpers/LoginPage');
const { readZipEntry } = require('./helpers/xlsxZip');

const TEST_USER = {
  username: 'testadmin',
  password: 'password'
};

test.describe('Balance xlsx export', () => {

  test.beforeEach(async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.password);
  });

  test('should display the Xlsx export button alongside CSV and Pdf', async ({ page }) => {
    await page.goto('/index.php/comptes/balance');
    await page.waitForLoadState('networkidle');

    const xlsxButton = page.locator('a[href*="balance_hierarchical_xlsx"]');
    await expect(page.locator('a[href*="balance_hierarchical_csv"]')).toBeVisible();
    await expect(page.locator('a[href*="balance_hierarchical_pdf"]')).toBeVisible();
    await expect(xlsxButton).toBeVisible();
    await expect(xlsxButton).toHaveText('Xlsx');
  });

  test('should download a valid xlsx with typed numeric balances', async ({ page }) => {
    await page.goto('/index.php/comptes/balance');
    await page.waitForLoadState('networkidle');

    const downloadPromise = page.waitForEvent('download');
    await page.click('a[href*="balance_hierarchical_xlsx"]');
    const download = await downloadPromise;

    const filePath = await download.path();
    expect(filePath).toBeTruthy();
    expect(fs.readFileSync(filePath).slice(0, 2).toString('ascii')).toBe('PK');
    expect(download.suggestedFilename()).toMatch(/\.xlsx$/);

    const sheetXml = readZipEntry(filePath, 'xl/worksheets/sheet1.xml');
    const stylesXml = readZipEntry(filePath, 'xl/styles.xml');

    // Title + blank row + column headers frozen
    expect(sheetXml).toMatch(/<pane [^>]*ySplit="3"[^>]*state="frozen"/);
    // Bold general accounts / totals exist
    expect(stylesXml).toContain('<b/>');
    // At least one balance is a plain, unstyled number in a solde column (D/E)
    expect(sheetXml).toMatch(/<c r="[DE]\d+"><v>-?\d+(\.\d+)?<\/v><\/c>/);

    console.log('✓ Balance xlsx export is a valid workbook with typed numeric balances');
  });
});
