/**
 * Smoke test for the Maintenance module — Roles et acces (Phase 6, Etape 6.1)
 *
 * Verifie la matrice de droits PRD EF8 :
 *   - pilote (membre standard, sans role mecano/ca/tresorier) : lecture
 *     seule de l'etat de navigabilite (maintenance_synthese), aucun acces
 *     au reste du module (detail d'intervention).
 *   - responsable de section (role 'ca') / tresorier : lecture seule de la
 *     synthese ET de l'historique (dossiers/operations/bulletins/
 *     programmes), aucun acces en ecriture.
 *
 * Prerequisites (bin/create_test_users.sh) :
 *   - asterix : membre standard section Planeur (id=1), aucun role special -> pilote
 *   - testca : role 'ca' (legacy) dans la section par defaut (Planeur, id=1)
 *   - testtresorier : role 'tresorier' (legacy) dans la section par defaut (Planeur, id=1)
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const PILOTE_USER = { username: 'asterix', password: 'password' };
const CA_USER = { username: 'testca', password: 'password' };
const TRESORIER_USER = { username: 'testtresorier', password: 'password' };
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

test.describe('Maintenance - Roles et acces (PRD EF8)', () => {

    test('pilote: acces en lecture a la synthese, refus explicite partout ailleurs', async ({ page }) => {
        await login(page, PILOTE_USER);
        await switchToPlaneurSection(page);

        // Synthese (etat de navigabilite) : autorisee
        await page.goto('/index.php/maintenance_synthese');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toContainText(/403|interdit/i);

        // Reste du module : refus explicite (jamais silencieux)
        for (const url of [
            '/index.php/maintenance_dossiers',
            '/index.php/maintenance_operations',
            '/index.php/maintenance_bulletins',
            '/index.php/maintenance_programmes',
            '/index.php/maintenance_equipements',
        ]) {
            await page.goto(url);
            await page.waitForLoadState('networkidle');
            await expect(page.locator('body')).toContainText(/r.serv.|interdit|403/i);
        }
    });

    test('responsable de section (ca): lecture seule de la synthese et de l\'historique, aucune ecriture', async ({ page }) => {
        await login(page, CA_USER);
        await switchToPlaneurSection(page);

        // Lecture autorisee : synthese + historique (dossiers/operations/bulletins/programmes)
        for (const url of [
            '/index.php/maintenance_synthese',
            '/index.php/maintenance_dossiers',
            '/index.php/maintenance_operations',
            '/index.php/maintenance_bulletins',
            '/index.php/maintenance_programmes',
        ]) {
            await page.goto(url);
            await page.waitForLoadState('networkidle');
            await expect(page.locator('body')).not.toContainText(/403|interdit/i);
        }

        // Ecriture refusee (action d'ouverture d'un dossier)
        await page.goto('/index.php/maintenance_dossiers/ouvrir_form/aeronef');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/r.serv.|interdit|403/i);

        // Equipements reste hors perimetre lecture seule (donnees maitre, pas de l'historique)
        await page.goto('/index.php/maintenance_equipements');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/r.serv.|interdit|403/i);
    });

    test('tresorier: memes droits en lecture seule que le responsable de section', async ({ page }) => {
        await login(page, TRESORIER_USER);
        await switchToPlaneurSection(page);

        await page.goto('/index.php/maintenance_synthese');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toContainText(/403|interdit/i);

        await page.goto('/index.php/maintenance_bulletins');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toContainText(/403|interdit/i);

        // Ecriture refusee (depot d'un bulletin)
        await page.goto('/index.php/maintenance_bulletins/upload_form/F-BULL01');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/r.serv.|interdit|403/i);
    });
});
