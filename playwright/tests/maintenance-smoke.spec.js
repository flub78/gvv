/**
 * Smoke test for the Maintenance module — parcours mecano de bout en bout (Phase 10, Etape 10.1)
 *
 * Ce fichier complete un scenario transverse non couvert par les smoke tests
 * par controleur (maintenance-equipements/-programmes/-dossiers/-operations
 * -smoke.spec.js, conserves tels quels pour la regression par ecran) :
 *   - creation d'un equipement, ouverture d'un dossier dessus, operation en
 *     saisie directe -> potentiel recalcule et visible dans la synthese de
 *     navigabilite (PRD EF6/EF7)
 *   - operation avec depot d'un compte rendu -> document consultable depuis
 *     l'historique du dossier (PRD EF4.2)
 *   - transfert de l'equipement vers un autre aeronef -> historique du
 *     dossier toujours accessible via l'entite (l'id equipement ne change
 *     pas lors d'un transfert, seul son aeronef_id change)
 *
 * Etape 10.2 (parcours pilote lecture seule) est deja couverte par
 * playwright/tests/maintenance-roles-smoke.spec.js (test "pilote: acces en
 * lecture a la synthese, refus explicite partout ailleurs", issu de la
 * Phase 6) -- non dupliquee ici.
 *
 * Prerequisites (bin/create_test_users.sh) :
 *   - obelix : role mecano, section Planeur (id=1)
 */

const { test, expect } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

const LOGIN_URL = '/index.php/auth/login';
const MECANO_USER = { username: 'obelix', password: 'password' };
const PLANEUR_SECTION = '1';
const FIXTURE_MD = path.join(__dirname, '..', '..', 'doc', 'test-data', 'maintenance_visite_100h.md');

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

test.describe('Maintenance - Parcours mecano de bout en bout', () => {

    test('equipement -> dossier -> operation directe (potentiel visible) -> operation compte rendu (document consultable) -> transfert (historique preserve)', async ({ page }) => {
        await login(page, MECANO_USER);
        await switchToPlaneurSection(page);

        // Dashboard maintenance accessible
        await page.goto('/index.php/maintenance_dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toContainText(/403|interdit/i);

        // Equipement rattache a un premier aeronef actif
        await page.goto('/index.php/maintenance_equipements/create');
        await page.waitForLoadState('networkidle');
        const nom = 'E2E smoke ' + Date.now();
        await page.fill('#nom', nom);
        const aeronefOptions = await page.locator('#aeronef_id option').all();
        const initialAeronefValue = await aeronefOptions[1].getAttribute('value');
        await page.selectOption('#aeronef_id', initialAeronefValue);
        await page.fill('#description', 'Equipement du parcours e2e');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();

        // Programme a butee horaire (seuil 100h) avec structure de taches
        await page.goto('/index.php/maintenance_programmes/create');
        await page.waitForLoadState('networkidle');
        const code = 'SMOKEE2E' + Date.now();
        await page.fill('#code', code);
        await page.fill('#titre', 'Visite 100 heures - equipement e2e');
        await page.check('#regle_butee_heures');
        await page.fill('#seuil_heures', '100');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await page.click('a:has-text("Déposer")');
        await page.waitForLoadState('networkidle');
        await page.setInputFiles('#markdown_file', FIXTURE_MD);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        const programmeId = /view\/(\d+)/.exec(page.url())[1];

        // Ouverture d'un dossier sur l'equipement (pas l'aeronef)
        await page.goto('/index.php/maintenance_dossiers/ouvrir_form/equipement');
        await page.waitForLoadState('networkidle');
        const equipementOption = page.locator('#entite_id option', { hasText: nom });
        const equipementId = await equipementOption.getAttribute('value');
        await page.selectOption('#entite_id', equipementId);
        await page.selectOption('#programme_id', programmeId);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText('Ouvert');
        const dossierId = /view\/(\d+)/.exec(page.url())[1];

        // Operation en saisie directe : releve horametre -> potentiel recalcule (Maintenance_potentiel::appliquer_operation)
        await page.click('a:has-text("Nouvelle opération")');
        await page.waitForLoadState('networkidle');
        await page.fill('#date_operation', new Date().toISOString().slice(0, 10));
        await page.fill('#horametre_releve', '42.5');
        await page.locator('input[type="radio"][value="fait"]').first().check();
        await page.fill('#commentaire', 'Operation directe e2e');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();

        // Potentiel visible depuis la synthese de navigabilite de l'aeronef porteur
        await page.goto('/index.php/maintenance_synthese/aeronef/' + initialAeronefValue);
        await page.waitForLoadState('networkidle');
        const equipementCard = page.locator('.card', { hasText: nom });
        await expect(equipementCard).toContainText('100');

        // Deuxieme operation sur le meme dossier, avec depot d'un compte rendu -> document consultable
        await page.goto('/index.php/maintenance_operations/create/' + dossierId);
        await page.waitForLoadState('networkidle');
        await page.fill('#date_operation', new Date().toISOString().slice(0, 10));
        const fixturePath = path.join(__dirname, 'fixtures-tmp-compte-rendu.pdf');
        fs.writeFileSync(fixturePath, '%PDF-1.4 smoke test compte rendu');
        await page.setInputFiles('#compte_rendu', fixturePath);
        await page.fill('#commentaire', 'Operation avec compte rendu e2e');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        fs.unlinkSync(fixturePath);
        await expect(page.locator('.alert-success')).toBeVisible();

        // Badge "compte rendu" sur la nouvelle operation, lien vers le document depuis son edition
        const compteRenduItem = page.locator('li', { hasText: 'Compte rendu papier' });
        await expect(compteRenduItem).toBeVisible();
        await compteRenduItem.locator('a:has-text("Modifier")').click();
        await page.waitForLoadState('networkidle');
        const docUrl = await page.locator('a:has-text("Compte rendu déjà déposé")').getAttribute('href');
        expect(docUrl).toBeTruthy();
        const docResponse = await page.request.get(docUrl);
        expect(docResponse.status()).toBe(200);

        // Transfert de l'equipement vers un autre aeronef
        await page.goto('/index.php/maintenance_equipements/transfer/' + equipementId);
        await page.waitForLoadState('networkidle');
        const targetOptions = await page.locator('#nouvel_aeronef_id option').all();
        let targetValue = null;
        for (const opt of targetOptions) {
            const v = await opt.getAttribute('value');
            if (v && v !== initialAeronefValue) {
                targetValue = v;
                break;
            }
        }
        expect(targetValue).not.toBeNull();
        await page.selectOption('#nouvel_aeronef_id', targetValue);
        await page.check('#confirmation');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toBeVisible();

        // Historique du dossier toujours accessible via l'entite equipement apres transfert
        await page.goto('/index.php/maintenance_dossiers?entite_type=equipement&entite_id=' + equipementId);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(code);
    });
});
