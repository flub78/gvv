// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Tests smoke pour le rapport adhérents
 */

// Test configuration
const LOGIN_URL = '/index.php/auth/login';
const TEST_USER = {
    username: 'testadmin',
    password: 'password'
};

test.describe('Adherents Report', () => {

    test.beforeEach(async ({ page }) => {
        // Se connecter en tant qu'utilisateur admin (a le role CA)
        await page.goto(LOGIN_URL);
        await page.waitForLoadState('networkidle');

        // Attendre la page de login
        await page.waitForSelector('input[name="username"]', { timeout: 5000 });

        // Utiliser les identifiants de test
        await page.fill('input[name="username"]', TEST_USER.username);
        await page.fill('input[name="password"]', TEST_USER.password);
        await page.click('button[type="submit"], input[type="submit"]');

        // Attendre la navigation
        await page.waitForLoadState('networkidle');
    });

    test('should access adherents report page', async ({ page }) => {
        // Naviguer vers la page du rapport adhérents
        await page.goto('/index.php/adherents_report');
        await page.waitForLoadState('networkidle');

        // Vérifier que la page s'affiche correctement
        await expect(page.locator('h3')).toContainText('Rapport Adhérents');

        // Vérifier la présence du sélecteur d'année
        await expect(page.locator('#year_selector')).toBeVisible();

        // Vérifier la présence du tableau
        await expect(page.locator('table.table-bordered')).toBeVisible();

        // Vérifier les en-têtes de lignes
        await expect(page.locator('tbody')).toContainText('Moins de 25 ans');
        await expect(page.locator('tbody')).toContainText('25-59 ans');
        await expect(page.locator('tbody')).toContainText('60 ans et plus');

        // Vérifier la ligne de total
        await expect(page.locator('tfoot')).toContainText('Total');

        // Vérifier la note d'information
        await expect(page.locator('.alert-info')).toBeVisible();
    });

    test('should change year via selector', async ({ page }) => {
        // Naviguer vers la page du rapport adhérents
        await page.goto('/index.php/adherents_report');
        await page.waitForLoadState('networkidle');

        // Attendre que la page soit chargée
        await expect(page.locator('#year_selector')).toBeVisible();

        // Récupérer les options disponibles
        const options = await page.locator('#year_selector option').all();

        if (options.length > 1) {
            // Sélectionner une autre année si disponible
            const currentYear = await page.locator('#year_selector').inputValue();
            const allValues = await Promise.all(options.map(o => o.getAttribute('value')));
            const otherYear = allValues.find(v => v !== currentYear);

            if (otherYear) {
                // Changer l'année
                await page.selectOption('#year_selector', otherYear);

                // Attendre le rechargement de la page
                await page.waitForLoadState('networkidle');

                // Vérifier que le titre contient la nouvelle année
                await expect(page.locator('h3')).toContainText(otherYear);
            }
        }
    });

    test('should display correct table structure', async ({ page }) => {
        // Naviguer vers la page du rapport adhérents
        await page.goto('/index.php/adherents_report');
        await page.waitForLoadState('networkidle');

        // Vérifier la structure du tableau
        const headerCells = await page.locator('thead th').all();

        // Il devrait y avoir au moins 2 colonnes (une vide + Total Club)
        expect(headerCells.length).toBeGreaterThanOrEqual(2);

        // Vérifier que la dernière colonne est "Total Club"
        const lastHeader = await headerCells[headerCells.length - 1].textContent();
        expect(lastHeader).toContain('Total Club');

        // Vérifier le nombre de lignes de données (3 groupes d'âge)
        const dataRows = await page.locator('tbody tr').all();
        expect(dataRows.length).toBe(3);

        // Vérifier la ligne de pied de tableau (Total)
        const footerRows = await page.locator('tfoot tr').all();
        expect(footerRows.length).toBe(1);
    });
});
