/**
 * Smoke test for the Maintenance module — dashboard a plat (Phase 5, Etape 5.7,
 * aplati sur le tableau de bord principal apres suppression du dashboard
 * intermediaire "maintenance_dashboard" : les 7 cartes du module pointent
 * desormais directement vers leur controleur, sur le meme modele que la
 * section Formation)
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

test.describe('Maintenance - Section a plat du tableau de bord principal (mecano)', () => {

    test('mecano sees all 7 maintenance cards directly on the main dashboard, each linking to its own controller', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        await page.goto('/index.php/welcome/section/maintenance');
        await page.waitForLoadState('networkidle');

        // Plus aucune carte "Bientot disponible" dans cette section
        await expect(page.locator('body')).not.toContainText('Bientôt disponible');
        // Plus de dashboard intermediaire : aucune carte ne pointe vers maintenance_dashboard
        await expect(page.locator('.sub-card a[href*="maintenance_dashboard"]')).toHaveCount(0);

        const links = {
            'Équipements': 'maintenance_equipements',
            "Programmes d'entretien": 'maintenance_programmes',
            "Dossiers d'entretien": 'maintenance_dossiers',
            'Opérations de maintenance': 'maintenance_operations',
            'Bulletins de service': 'maintenance_bulletins',
            'Synthèse navigabilité': 'maintenance_synthese',
            'Tableau des potentiels': 'maintenance_synthese',
        };
        for (const [label, expectedController] of Object.entries(links)) {
            const card = page.locator('.sub-card', { hasText: label });
            await expect(card).toBeVisible();
            const href = await card.locator('a').getAttribute('href');
            expect(href).toContain(expectedController);
        }
    });

    test('clicking a maintenance card lands directly on the target screen, and the back link returns to the main dashboard', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        await page.goto('/index.php/welcome/section/maintenance');
        await page.waitForLoadState('networkidle');

        await page.locator('.sub-card', { hasText: 'Équipements' }).locator('a').click();
        await page.waitForLoadState('networkidle');
        await expect(page.url()).toContain('/maintenance_equipements');
        await expect(page.url()).not.toContain('/maintenance_dashboard');

        const backLink = page.locator('#navBackLink');
        await expect(backLink).toBeVisible();
        await expect(backLink).toContainText('Maintenance et suivi de navigabilité');
        await backLink.click();
        await page.waitForLoadState('networkidle');
        await expect(page.url()).toContain('/welcome/section/maintenance');
    });

    test('non-mecano user is denied access to write screens but keeps access to the read-only synthese', async ({ page }) => {
        await login(page, { username: 'asterix', password: 'password' });

        await page.goto('/index.php/maintenance_equipements');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/r.serv.|interdit|403/i);

        await page.goto('/index.php/maintenance_synthese');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toContainText(/403/i);
    });
});
