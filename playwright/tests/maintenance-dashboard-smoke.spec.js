/**
 * Smoke test for the Maintenance module — Dashboard dedie (Phase 5, Etape 5.7)
 *
 * Prerequisites:
 *   - obelix user exists with mecano role in section Planeur (id=1)
 *   - asterix user exists WITHOUT mecano role (any section)
 *     See bin/create_test_users.sh
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const MECANO_USER = { username: 'obelix', password: 'password' };
const PLANEUR_SECTION = '1';

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function switchToPlaneurSection(page) {
    await page.request.post('/index.php/user_roles_per_section/set_section', {
        form: { section: PLANEUR_SECTION, current_url: '/index.php/welcome' }
    });
}

test.describe('Maintenance - Dashboard dedie (mecano)', () => {

    test('mecano sees the maintenance section on the main dashboard with active cards linking to the dedicated dashboard', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        await page.goto('/index.php/welcome/section/maintenance');
        await page.waitForLoadState('networkidle');

        // Les 2 cartes existantes ne sont plus "bientot disponible"
        await expect(page.locator('body')).not.toContainText('Bientôt disponible');
        const progCard = page.locator('.sub-card', { hasText: "Programmes d'entretien" });
        await expect(progCard.locator('a, button')).toHaveText(/Gérer/);
        const opsCard = page.locator('.sub-card', { hasText: 'Opérations de maintenance' });
        await expect(opsCard.locator('a, button')).toHaveText(/Gérer/);

        // Les deux pointent vers le dashboard maintenance dedie
        await progCard.locator('a').click();
        await page.waitForLoadState('networkidle');
        await expect(page.url()).toContain('/maintenance_dashboard');

        // Le dashboard dedie affiche toutes les cartes du module
        await expect(page.locator('body')).toContainText('Équipements');
        await expect(page.locator('body')).toContainText("Programmes d'entretien");
        await expect(page.locator('body')).toContainText("Dossiers d'entretien");
        await expect(page.locator('body')).toContainText('Opérations de maintenance');
        await expect(page.locator('body')).toContainText('Bulletins de service');
        await expect(page.locator('body')).toContainText('Synthèse navigabilité');

        // Chaque carte mene bien au bon controleur
        const links = {
            'Équipements': 'maintenance_equipements',
            "Programmes d'entretien": 'maintenance_programmes',
            "Dossiers d'entretien": 'maintenance_dossiers',
            'Opérations de maintenance': 'maintenance_operations',
            'Bulletins de service': 'maintenance_bulletins',
            'Synthèse navigabilité': 'maintenance_synthese',
        };
        for (const [label, expectedController] of Object.entries(links)) {
            const href = await page.locator('.sub-card', { hasText: label }).locator('a').getAttribute('href');
            expect(href).toContain(expectedController);
        }
    });

    test('non-mecano user is denied access to the dedicated dashboard', async ({ page }) => {
        await login(page, { username: 'asterix', password: 'password' });
        await page.goto('/index.php/maintenance_dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/r.serv.|interdit|403/i);
    });
});
