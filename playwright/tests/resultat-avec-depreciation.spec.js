/**
 * Résultat avant et après dépréciations — Tests Playwright
 *
 * Vérifie que le résultat après dépréciations affiché sur la page
 * comptes/resultat_avec_depreciation est identique au résultat affiché
 * sur comptes/resultat (qui inclut toujours les 68x/78x).
 *
 * Cas testés :
 *   - Accessibilité de la page et présence des deux titres intermédiaires
 *   - Section Planeur : valeurs N et N-1 identiques entre les deux pages
 *   - Section Général : valeurs N et N-1 identiques entre les deux pages
 *
 * Usage :
 *   cd playwright && npx playwright test tests/resultat-avec-depreciation.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const TEST_USER     = 'testadmin';
const TEST_PASSWORD = 'password';
const PLANEUR       = '1';
const GENERAL       = '4';

/**
 * Extrait les montants de la dernière ligne Profits/Pertes du tableau résultat.
 *
 * Le tableau a 11 colonnes :
 *   [0] code  [1] label charges  [2] section  [3] N  [4] N-1  [5] sep
 *   [6] code  [7] label produits [8] section  [9] N  [10] N-1
 *
 * La ligne Profits/Pertes a "Profits" ou "Pertes" dans la colonne 1.
 * Pour comptes/resultat_avec_depreciation il y en a deux (avant + après) :
 * on prend la dernière.
 */
async function getLastResultRow(page) {
    const table = page.locator('table.sql_table');
    await expect(table).toBeVisible({ timeout: 10000 });

    const rows = table.locator('tbody tr');
    const profitRows = rows.filter({ hasText: /Profits|Pertes|Bénéfice|Déficit/ });
    const count = await profitRows.count();
    expect(count, 'Au moins une ligne Profits/Pertes attendue').toBeGreaterThan(0);

    const lastRow = profitRows.nth(count - 1);
    const cells   = lastRow.locator('td');

    return {
        chargesN:   (await cells.nth(3).textContent()).trim(),
        chargesN1:  (await cells.nth(4).textContent()).trim(),
        produitsN:  (await cells.nth(9).textContent()).trim(),
        produitsN1: (await cells.nth(10).textContent()).trim(),
    };
}

/**
 * Bascule la section active via le sélecteur dans le bandeau
 * et attend la navigation déclenchée par le changement de sélection.
 */
async function switchSection(page, sectionId) {
    const sectionSelect = page.locator('select[name="section"]');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        sectionSelect.selectOption(sectionId),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('Résultat avant et après dépréciations', () => {

    test.beforeEach(async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login(TEST_USER, TEST_PASSWORD, PLANEUR);
    });

    // ── Smoke test ────────────────────────────────────────────────────────────

    test('la page est accessible et affiche les deux titres intermédiaires', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto('comptes/resultat_avec_depreciation');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('h3')).toContainText('dépréciations');
        await expect(page.locator('table.sql_table')).toBeVisible();
        await expect(page.locator('body')).toContainText('Résultat avant dépréciations');
        await expect(page.locator('body')).toContainText('Résultat après dépréciations');
        console.log('✓ Page accessible et titres intermédiaires présents');
    });

    // ── Section Planeur ───────────────────────────────────────────────────────

    test('Planeur — résultat après dépréciations identique à comptes/resultat (N et N-1)', async ({ page }) => {
        const loginPage = new LoginPage(page);

        // Résultat de la nouvelle page (après dépréciations = dernière ligne Profits/Pertes)
        await loginPage.goto('comptes/resultat_avec_depreciation');
        await page.waitForLoadState('networkidle');
        const avecDep = await getLastResultRow(page);
        console.log('Planeur avec_dep :', avecDep);

        // Résultat de la page de référence
        await loginPage.goto('comptes/resultat');
        await page.waitForLoadState('networkidle');
        const simple = await getLastResultRow(page);
        console.log('Planeur simple   :', simple);

        expect(avecDep.chargesN,   'Planeur N  — charges side').toBe(simple.chargesN);
        expect(avecDep.chargesN1,  'Planeur N-1 — charges side').toBe(simple.chargesN1);
        expect(avecDep.produitsN,  'Planeur N  — produits side').toBe(simple.produitsN);
        expect(avecDep.produitsN1, 'Planeur N-1 — produits side').toBe(simple.produitsN1);
        console.log('✓ Planeur : résultats identiques sur N et N-1');
    });

    // ── Section Général ───────────────────────────────────────────────────────

    test('Général — résultat après dépréciations identique à comptes/resultat (N et N-1)', async ({ page }) => {
        const loginPage = new LoginPage(page);

        // Basculer vers la section Général
        await loginPage.goto('comptes/resultat');
        await page.waitForLoadState('networkidle');
        await switchSection(page, GENERAL);

        // Résultat de référence (section Général)
        await loginPage.goto('comptes/resultat');
        await page.waitForLoadState('networkidle');
        const simple = await getLastResultRow(page);
        console.log('Général simple   :', simple);

        // Résultat de la nouvelle page
        await loginPage.goto('comptes/resultat_avec_depreciation');
        await page.waitForLoadState('networkidle');
        const avecDep = await getLastResultRow(page);
        console.log('Général avec_dep :', avecDep);

        expect(avecDep.chargesN,   'Général N  — charges side').toBe(simple.chargesN);
        expect(avecDep.chargesN1,  'Général N-1 — charges side').toBe(simple.chargesN1);
        expect(avecDep.produitsN,  'Général N  — produits side').toBe(simple.produitsN);
        expect(avecDep.produitsN1, 'Général N-1 — produits side').toBe(simple.produitsN1);
        console.log('✓ Général : résultats identiques sur N et N-1');
    });
});
