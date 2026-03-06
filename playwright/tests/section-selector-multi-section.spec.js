/**
 * Section Selector - Multi-Section User Test
 *
 * Verifies that a user enrolled in the new authorization system who belongs
 * to multiple sections can switch sections using the navbar section selector,
 * and that ONLY the sections where the user has a role appear in the selector.
 *
 * Test user: asterix (new auth system)
 *   - Roles in: Planeur (id=1), Général (id=4)
 *   - NOT in:   ULM (id=2), Avion (id=3)
 *
 * Expected behaviour:
 *   - The section <select> in the navbar shows exactly 2 options
 *   - Options are "Planeur" and "Général"
 *   - "ULM" and "Avion" are absent
 *   - Changing the select switches the active section
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/section-selector-multi-section.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const ASTERIX = { username: 'asterix', password: 'password' };

// Sections where asterix has a role
const ALLOWED_SECTIONS = [
    { id: '1', nom: 'Planeur' },
    { id: '4', nom: 'Général' },
];

// Sections where asterix has NO role (must NOT appear in selector)
const FORBIDDEN_SECTIONS = ['ULM', 'Avion'];

test.describe('Section selector - utilisateur multi-sections (nouveau système)', () => {

    test('le sélecteur de section ne contient que les sections de l\'utilisateur', async ({ page }) => {
        console.log('=== Test: contenu du sélecteur de section ===');

        // Login as asterix on first allowed section
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login(ASTERIX.username, ASTERIX.password, ALLOWED_SECTIONS[0].id);

        console.log(`Connecté en tant que ${ASTERIX.username}, section ${ALLOWED_SECTIONS[0].nom}`);

        // Locate the section selector in the navbar
        const sectionSelect = page.locator('select[name="section"]');
        await expect(sectionSelect).toBeVisible({ timeout: 5000 });
        console.log('Sélecteur de section trouvé dans la navbar');

        // Collect all option texts
        const options = sectionSelect.locator('option');
        const optionCount = await options.count();
        console.log(`Nombre d'options dans le sélecteur : ${optionCount}`);

        const optionTexts = await options.allTextContents();
        console.log('Options disponibles :', optionTexts);

        // Must have exactly the allowed sections
        expect(optionCount).toBe(ALLOWED_SECTIONS.length);
        console.log(`✓ Exactement ${ALLOWED_SECTIONS.length} options (attendu : ${ALLOWED_SECTIONS.length})`);

        // Each allowed section must appear
        for (const section of ALLOWED_SECTIONS) {
            const found = optionTexts.some(text => text.trim() === section.nom);
            expect(found, `La section "${section.nom}" doit apparaître dans le sélecteur`).toBeTruthy();
            console.log(`✓ Section autorisée présente : ${section.nom}`);
        }

        // Forbidden sections must NOT appear
        for (const nom of FORBIDDEN_SECTIONS) {
            const found = optionTexts.some(text => text.trim() === nom);
            expect(found, `La section "${nom}" ne doit PAS apparaître dans le sélecteur`).toBeFalsy();
            console.log(`✓ Section interdite absente : ${nom}`);
        }

        console.log('✅ Contenu du sélecteur correct');
    });

    test('le changement de section via le sélecteur est effectif', async ({ page }) => {
        console.log('=== Test: changement de section ===');

        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login(ASTERIX.username, ASTERIX.password, ALLOWED_SECTIONS[0].id);

        console.log(`Connecté en section initiale : ${ALLOWED_SECTIONS[0].nom} (id=${ALLOWED_SECTIONS[0].id})`);

        // Verify initial section is selected
        const sectionSelect = page.locator('select[name="section"]');
        await expect(sectionSelect).toBeVisible({ timeout: 5000 });

        const initialValue = await sectionSelect.inputValue();
        expect(initialValue).toBe(ALLOWED_SECTIONS[0].id);
        console.log(`✓ Section initiale sélectionnée : ${initialValue}`);

        // Switch to the second allowed section
        const targetSection = ALLOWED_SECTIONS[1];
        console.log(`Changement vers : ${targetSection.nom} (id=${targetSection.id})`);

        await sectionSelect.selectOption(targetSection.id);

        // Wait for the page to reload / section to update
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(1000);

        console.log('Page rechargée après changement de section');
        await page.screenshot({ path: 'build/playwright-captures/section-selector-after-change.png' });

        // Verify the new section is now active in the selector
        const updatedSelect = page.locator('select[name="section"]');
        await expect(updatedSelect).toBeVisible({ timeout: 5000 });

        const newValue = await updatedSelect.inputValue();
        expect(newValue).toBe(targetSection.id);
        console.log(`✓ Nouvelle section active dans le sélecteur : ${newValue} (${targetSection.nom})`);

        // Verify the section name appears in the UI
        await expect(page.locator('body')).toContainText(targetSection.nom);
        console.log(`✓ Nom de la nouvelle section visible dans la page : ${targetSection.nom}`);

        console.log('✅ Changement de section effectif');
    });

    test('retour à la section initiale fonctionne', async ({ page }) => {
        console.log('=== Test: aller-retour de section ===');

        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login(ASTERIX.username, ASTERIX.password, ALLOWED_SECTIONS[0].id);

        const sectionSelect = page.locator('select[name="section"]');
        await expect(sectionSelect).toBeVisible({ timeout: 5000 });

        // Switch to second section
        await sectionSelect.selectOption(ALLOWED_SECTIONS[1].id);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        const afterFirst = await page.locator('select[name="section"]').inputValue();
        expect(afterFirst).toBe(ALLOWED_SECTIONS[1].id);
        console.log(`✓ Section après premier changement : ${afterFirst}`);

        // Switch back to first section
        await page.locator('select[name="section"]').selectOption(ALLOWED_SECTIONS[0].id);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        const afterSecond = await page.locator('select[name="section"]').inputValue();
        expect(afterSecond).toBe(ALLOWED_SECTIONS[0].id);
        console.log(`✓ Section après retour : ${afterSecond}`);

        console.log('✅ Aller-retour de section fonctionnel');
    });

});
