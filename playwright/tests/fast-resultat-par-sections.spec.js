/**
 * Comparison test: /comptes/resultat_par_sections vs /comptes/fast_resultat_par_sections
 *
 * Verifies that the optimized page produces results identical to the original
 * for two different exercises (years).
 *
 * Usage:
 *   npx playwright test tests/fast-resultat-par-sections.spec.js
 */

const { test, expect } = require('@playwright/test');

const TEST_USER = 'testadmin';
const TEST_PASSWORD = 'password';

const TABLE_IDS = [
    'resultat_par_sections_charges_table',
    'resultat_par_sections_produits_table',
    'resultat_par_sections_total_table',
];

async function login(page) {
    await page.goto('/index.php/auth/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', TEST_USER);
    await page.fill('input[name="password"]', TEST_PASSWORD);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    if (page.url().includes('select_section')) {
        await page.locator('table tbody tr').first().locator('a').first().click();
        await page.waitForLoadState('networkidle');
    }
}

async function setYear(page, year) {
    await page.goto(`/index.php/comptes/new_year/${year}`);
    await page.waitForLoadState('networkidle');
}

/**
 * Extract all body row cell texts from a table identified by its ID.
 * Returns an array of rows, each row being an array of trimmed cell texts.
 */
async function extractTableRows(page, tableId) {
    const table = page.locator(`table#${tableId}`);
    const isVisible = await table.isVisible();
    if (!isVisible) {
        return [];
    }

    const rows = await table.locator('tbody tr').all();
    const result = [];
    for (const row of rows) {
        const cells = await row.locator('td, th').all();
        const rowData = [];
        for (const cell of cells) {
            rowData.push((await cell.textContent()).trim());
        }
        result.push(rowData);
    }
    return result;
}

async function getPageData(page, url) {
    await page.goto(url);
    await page.waitForLoadState('domcontentloaded');

    const result = {};
    for (const tableId of TABLE_IDS) {
        result[tableId] = await extractTableRows(page, tableId);
    }
    return result;
}

async function comparePagesForYear(page, year) {
    console.log(`\n--- Exercice ${year} ---`);

    await setYear(page, year);

    const slowData = await getPageData(page, '/index.php/comptes/resultat_par_sections');
    const fastData = await getPageData(page, '/index.php/comptes/fast_resultat_par_sections');

    for (const tableId of TABLE_IDS) {
        const slow = slowData[tableId];
        const fast = fastData[tableId];

        console.log(`  ${tableId}: ${slow.length} lignes`);

        expect(fast.length, `${tableId} - nombre de lignes pour exercice ${year}`).toBe(slow.length);

        for (let i = 0; i < slow.length; i++) {
            expect(fast[i], `${tableId} ligne ${i + 1} exercice ${year}`).toEqual(slow[i]);
        }

        console.log(`  ✓ ${tableId} identique`);
    }
}

test.describe('fast_resultat_par_sections - comparaison avec la version originale', () => {

    test('résultats identiques pour deux exercices consécutifs', async ({ page }) => {
        await login(page);

        await comparePagesForYear(page, 2025);
        await comparePagesForYear(page, 2024);

        console.log('\n✅ Les deux pages produisent des résultats identiques sur deux exercices.');
    });

});
