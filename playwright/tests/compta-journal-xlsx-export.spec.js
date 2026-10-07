/**
 * Playwright test for the journal and account extract xlsx exports (Phase 1
 * of the "exports feuille de calcul" initiative — see
 * doc/design_notes/exports_feuille_de_calcul_design.md).
 *
 * Tests:
 * - The "Xlsx" button is present next to CSV/Pdf on the journal
 *   (compta/page) and on an account extract (compta/journal_compte/<id>)
 * - Each downloads a valid .xlsx with a frozen header row
 * - Amounts are stored as real numeric cells (not shared strings)
 *
 * Usage:
 *   npx playwright test tests/compta-journal-xlsx-export.spec.js
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

async function downloadXlsx(page) {
  const xlsxButton = page.locator('input[type="submit"][value="Xlsx"]');
  await expect(page.locator('input[type="submit"][value="CSV"]')).toBeVisible();
  await expect(page.locator('input[type="submit"][value="Pdf"]')).toBeVisible();
  await expect(xlsxButton).toBeVisible();

  const downloadPromise = page.waitForEvent('download');
  await xlsxButton.click();
  const download = await downloadPromise;

  const filePath = await download.path();
  expect(filePath).toBeTruthy();
  expect(fs.readFileSync(filePath).slice(0, 2).toString('ascii')).toBe('PK');
  expect(download.suggestedFilename()).toMatch(/\.xlsx$/);
  return filePath;
}

test.describe('Journal xlsx exports', () => {

  test.beforeEach(async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_USER.username, TEST_USER.password);
  });

  test('journal: downloads a valid xlsx with typed amounts', async ({ page }) => {
    await page.goto('/index.php/compta/page');
    await page.waitForLoadState('networkidle');

    const filePath = await downloadXlsx(page);
    const sheetXml = readZipEntry(filePath, 'xl/worksheets/sheet1.xml');

    expect(sheetXml).toMatch(/<pane [^>]*ySplit="1"[^>]*state="frozen"/);
    expect(sheetXml).toMatch(unstyledNumericCellPattern);

    console.log('✓ Journal xlsx export is a valid workbook with typed amounts');
  });

  test('account extract: downloads a valid xlsx with typed amounts', async ({ page }) => {
    await page.goto('/index.php/compta/page');
    await page.waitForLoadState('networkidle');

    // Use an account listed in the journal rather than assuming an id exists
    const accountLink = page.locator('a[href*="compta/journal_compte/"]').first();
    test.skip(await accountLink.count() === 0, 'No entry in the journal');
    await page.goto(await accountLink.getAttribute('href'));
    await page.waitForLoadState('networkidle');

    const filePath = await downloadXlsx(page);
    const sheetXml = readZipEntry(filePath, 'xl/worksheets/sheet1.xml');

    // Header block (title, account/pilot info, opening balance, column
    // headers) is frozen above the entries
    expect(sheetXml).toMatch(/<pane [^>]*ySplit="\d+"[^>]*state="frozen"/);
    expect(sheetXml).toMatch(unstyledNumericCellPattern);

    console.log('✓ Account extract xlsx export is a valid workbook with typed amounts');
  });
});
