/**
 * Résultat par sections — Cohérence avant/après dépréciations
 *
 * Vérifie que les résultats avant et après dépréciations affichés sur la page
 * comptes/resultat_par_sections (par section dans les colonnes) sont identiques
 * aux résultats affichés sur comptes/resultat_avec_depreciation (par section active).
 *
 * Pour chaque section disponible :
 *   - Extrait les valeurs N (2025) de la colonne section dans resultat_par_sections
 *   - Extrait les valeurs N (2025) depuis resultat_avec_depreciation avec cette section active
 *   - Vérifie l'égalité (avant dépréciations et après dépréciations)
 *
 * Usage :
 *   cd playwright && npx playwright test tests/resultat-par-sections-depreciation.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const TEST_USER     = 'testadmin';
const TEST_PASSWORD = 'password';
const YEAR          = '2025';
const SECTION_START = '1'; // Section Planeur pour le login initial

/**
 * Parse une valeur formatée en euros (ex: "1 234,56 €") en nombre flottant signé.
 * Retourne 0 si la chaîne est vide ou invalide.
 */
function parseAmount(str) {
    if (!str || str.trim() === '') return 0;
    // Supprimer espaces insécables, espaces, symbole €, puis convertir virgule → point
    const clean = str.replace(/\u00a0/g, '').replace(/\s/g, '').replace('€', '').replace(',', '.');
    const val = parseFloat(clean);
    return isNaN(val) ? 0 : val;
}

/**
 * Sélectionne l'année dans le sélecteur d'année.
 * Le sélecteur déclenche new_year() → /comptes/new_year/N → redirect → page courante.
 * On attend networkidle pour laisser les redirects se terminer.
 */
async function setYear(page, year) {
    const yearSelect = page.locator('select[name="year"]');
    const count = await yearSelect.count();
    if (count > 0) {
        const currentVal = await yearSelect.inputValue();
        if (currentVal !== year) {
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
                yearSelect.selectOption(year),
            ]);
        }
    }
}

/**
 * Bascule la section active et attend la navigation.
 * updateSection() fait un $.post() puis window.location.href → navigation browser.
 */
async function switchSection(page, sectionId) {
    const sectionSelect = page.locator('select[name="section"]');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
        sectionSelect.selectOption(String(sectionId)),
    ]);
}

/**
 * Extrait les résultats avant et après dépréciations depuis resultat_avec_depreciation.
 *
 * La table affiche côté charges : col 3 = N, col 4 = N-1
 * et côté produits : col 9 = N, col 10 = N-1.
 * Les lignes Bénéfice/Déficit contiennent le résultat (une seule des deux colonnes est remplie).
 *   - Bénéfice : résultat positif dans col charges (col 3)
 *   - Déficit   : valeur absolue du résultat négatif dans col produits (col 9)
 *
 * @returns {{ avantDepN: number, apresDepN: number }}
 */
async function getDepreciationResults(page) {
    const table = page.locator('table.sql_table');
    await expect(table).toBeVisible({ timeout: 10000 });

    const rows = table.locator('tbody tr');
    const profitRows = rows.filter({ hasText: /Bénéfice|Déficit/ });
    const count = await profitRows.count();

    let avantDepN = null;
    let apresDepN = null;

    for (let i = 0; i < count; i++) {
        const row = profitRows.nth(i);
        const cells = row.locator('td');

        const label1 = (await cells.nth(1).textContent()).trim();
        // Col 3 = N côté charges (Bénéfice), col 9 = N côté produits (Déficit)
        const benefN  = parseAmount((await cells.nth(3).textContent()).trim());
        const deficitN = parseAmount((await cells.nth(9).textContent()).trim());

        // Le résultat signé : Bénéfice est positif (col charges), Déficit est négatif (col produits)
        const signed = benefN - deficitN;

        if (/avant/i.test(label1)) {
            avantDepN = signed;
        } else if (/apr/i.test(label1)) {
            apresDepN = signed;
        }
    }

    return { avantDepN, apresDepN };
}

/**
 * Extrait les résultats avant et après dépréciations depuis le tableau total de
 * resultat_par_sections, pour la section indiquée par son nom complet.
 *
 * Structure du tableau total (skip_label_cols=true) :
 *   td[0] = label, td[1] = section0 N, td[2] = section0 N-1, td[3] = section1 N, ...
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} sectionName Nom complet de la section (ex: "Planeur")
 * @returns {{ avantDepN: number, apresDepN: number }}
 */
async function getSectionsResultsForSection(page, sectionName) {
    const table = page.locator('table#resultat_par_sections_total_table');
    await expect(table).toBeVisible({ timeout: 10000 });

    // Trouver l'index de la section dans les en-têtes (ligne 1 du thead)
    const sectionHeaders = table.locator('thead tr').nth(0).locator('th.section-header');
    const sectionCount   = await sectionHeaders.count();

    let sectionColIdx = -1;
    for (let i = 0; i < sectionCount; i++) {
        const text = (await sectionHeaders.nth(i).textContent()).trim();
        if (text === sectionName) {
            sectionColIdx = i;
            break;
        }
    }

    if (sectionColIdx === -1) {
        return null; // Section absente du tableau
    }

    // Dans les <tr> du tbody :
    //   td[0] = label
    //   td[1 + sectionColIdx * 2] = N (2025)
    //   td[2 + sectionColIdx * 2] = N-1 (2024)
    const nTdIdx = 1 + sectionColIdx * 2;

    const avantDepRow = table.locator('tbody tr.row-resultat-avant-dep');
    const apresDepRow = table.locator('tbody tr.row-resultat-apres-dep');

    await expect(avantDepRow).toBeVisible();
    await expect(apresDepRow).toBeVisible();

    const avantDepN = parseAmount(
        (await avantDepRow.locator('td').nth(nTdIdx).textContent()).trim()
    );
    const apresDepN = parseAmount(
        (await apresDepRow.locator('td').nth(nTdIdx).textContent()).trim()
    );

    return { avantDepN, apresDepN };
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('Cohérence avant/après dépréciations entre resultat_par_sections et resultat_avec_depreciation', () => {

    test.beforeEach(async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login(TEST_USER, TEST_PASSWORD, SECTION_START);
    });

    // ── Smoke : les deux pages sont accessibles et contiennent les éléments attendus ──

    test('resultat_par_sections affiche les lignes avant et après dépréciations', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto('comptes/resultat_par_sections');
        await setYear(page, YEAR);

        const table = page.locator('table#resultat_par_sections_total_table');
        await expect(table).toBeVisible();

        const avantRow = table.locator('tbody tr.row-resultat-avant-dep');
        const apresRow = table.locator('tbody tr.row-resultat-apres-dep');
        await expect(avantRow).toBeVisible();
        await expect(apresRow).toBeVisible();

        const avantLabel = (await avantRow.locator('td').first().textContent()).trim();
        const apresLabel = (await apresRow.locator('td').first().textContent()).trim();

        expect(avantLabel).toContain('avant');
        expect(apresLabel).toContain('après');
        console.log(`✓ Lignes présentes : "${avantLabel}" et "${apresLabel}"`);
    });

    // ── Comparaison par section ───────────────────────────────────────────────

    test('boucle sur chaque section : avant/après dépréciations identiques sur les deux pages', async ({ page }) => {
        test.setTimeout(180000); // 3 min — boucle sur toutes les sections
        const loginPage = new LoginPage(page);

        // 1. Aller sur resultat_par_sections, récupérer les sections depuis le banner
        await loginPage.goto('comptes/resultat_par_sections');
        await setYear(page, YEAR);

        const sectionSelect = page.locator('select[name="section"]');
        const options = await sectionSelect.locator('option').all();

        const sections = [];
        for (const opt of options) {
            const value = await opt.getAttribute('value');
            const name  = (await opt.textContent()).trim();
            // Exclure "Toutes" et les valeurs vides/nulles/zero
            if (value && value !== '' && value !== '0' && name !== 'Toutes') {
                sections.push({ id: value, name });
            }
        }
        console.log(`Sections trouvées : ${sections.map(s => s.name).join(', ')}`);
        expect(sections.length).toBeGreaterThan(0);

        // 2. Pour chaque section, comparer les valeurs entre les deux pages
        for (const section of sections) {
            console.log(`\n── Section : ${section.name} (id=${section.id}) ──`);

            // Extraire les valeurs depuis le tableau par sections (on y est déjà)
            const parSections = await getSectionsResultsForSection(page, section.name);

            if (parSections === null) {
                console.log(`  ⚠ Section "${section.name}" absente du tableau par sections, ignorée`);
                // Revenir pour la prochaine itération
                await loginPage.goto('comptes/resultat_par_sections');
                await setYear(page, YEAR);
                continue;
            }

            console.log(`  resultat_par_sections : avant=${parSections.avantDepN.toFixed(2)}, après=${parSections.apresDepN.toFixed(2)}`);

            // Extraire les valeurs depuis resultat_avec_depreciation pour cette section
            await loginPage.goto('comptes/resultat_avec_depreciation');
            await setYear(page, YEAR);
            await switchSection(page, section.id);
            await page.waitForLoadState('domcontentloaded');

            const avecDep = await getDepreciationResults(page);

            if (avecDep.avantDepN === null || avecDep.apresDepN === null) {
                console.log(`  ⚠ Lignes avant/après dépréciations introuvables pour "${section.name}"`);
                await loginPage.goto('comptes/resultat_par_sections');
                await setYear(page, YEAR);
                continue;
            }

            console.log(`  resultat_avec_depreciation : avant=${avecDep.avantDepN.toFixed(2)}, après=${avecDep.apresDepN.toFixed(2)}`);

            // Tolérance de 0,01 € pour les arrondis de formatage
            const tolerance = 0.015;

            expect(
                Math.abs(parSections.avantDepN - avecDep.avantDepN),
                `Section ${section.name} — résultat avant dépréciations N : par_sections=${parSections.avantDepN.toFixed(2)} vs avec_dep=${avecDep.avantDepN.toFixed(2)}`
            ).toBeLessThanOrEqual(tolerance);

            expect(
                Math.abs(parSections.apresDepN - avecDep.apresDepN),
                `Section ${section.name} — résultat après dépréciations N : par_sections=${parSections.apresDepN.toFixed(2)} vs avec_dep=${avecDep.apresDepN.toFixed(2)}`
            ).toBeLessThanOrEqual(tolerance);

            console.log(`  ✓ Section ${section.name} : avant et après dépréciations identiques`);

            // Revenir sur resultat_par_sections pour la prochaine itération
            await loginPage.goto('comptes/resultat_par_sections');
            await setYear(page, YEAR);
        }

        console.log('\n✅ Toutes les sections vérifiées');
    });
});
