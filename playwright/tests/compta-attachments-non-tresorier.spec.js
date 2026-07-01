/**
 * Tests du contrôle d'accès aux justificatifs pour les membres non trésoriers.
 *
 * Règles testées :
 *  - Un non-trésorier PEUT voir les justificatifs de ses propres écritures.
 *  - Un non-trésorier NE PEUT PAS créer de justificatifs (bouton "Créer" absent).
 *  - Un non-trésorier NE PEUT PAS accéder aux justificatifs d'un autre compte.
 *
 * Utilisateurs de test (mot de passe: "password") :
 *  - asterix  : rôle "user" uniquement (non trésorier) en sections Planeur(1) et Général(4)
 *  - panoramix: club-admin (trésorier de fait) dans toutes les sections
 *
 * Données de test :
 *  - beforeAll crée une écriture pour le compte asterix (ID 1532, section Planeur=1)
 *    via l'interface panoramix, et supprime l'écriture en afterAll.
 *  - SFRISON_ECRITURE_ID (40677) : écriture appartenant à sfrison — pas à asterix.
 *
 * Usage :
 *   cd playwright && npx playwright test tests/compta-attachments-non-tresorier.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const PLANEUR_SECTION     = '1';
const SFRISON_ECRITURE_ID = 40677; // écriture sfrison (compte 323) — PAS asterix
const ASTERIX_COMPTE_ID   = 1532;  // compte 411 d'asterix en section Planeur
const TEST_DESCRIPTION    = 'TEST-non-tresorier-justificatifs';

let asterixEcritureId = null;

test.describe.configure({ mode: 'serial' });

test.describe('Justificatifs — accès non trésorier', () => {

    // ── Setup : créer une écriture de test pour asterix ──────────────────────
    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();
        try {
            // Connexion en tant que panoramix (admin, section Planeur)
            await page.goto('/auth/login');
            await page.fill('input[name="username"]', 'panoramix');
            await page.fill('input[name="password"]', 'password');
            const sectionSelect = page.locator('select[name="section"]');
            if (await sectionSelect.count() > 0) {
                await sectionSelect.selectOption(PLANEUR_SECTION);
            }
            await page.click('input[type="submit"], button[type="submit"]');
            await page.waitForLoadState('networkidle');

            // Formulaire de création d'écriture (compta/create → formValidation/1)
            await page.goto('/index.php/compta/create');
            await page.waitForLoadState('networkidle');

            // Remplir le formulaire
            // compte1 = asterix (411), compte2 = "Remorqués" (706, ID 168)
            await page.selectOption('select[name="compte1"]', String(ASTERIX_COMPTE_ID));
            await page.selectOption('select[name="compte2"]', '168');
            await page.fill('input[name="montant"]', '1.00');
            await page.fill('input[name="date_op"]', '01/07/2026');
            const descInput = page.locator('input[name="description"], textarea[name="description"]');
            if (await descInput.count() > 0) {
                await descInput.fill(TEST_DESCRIPTION);
            }

            // Soumettre et attendre la redirection vers le journal
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle' }),
                page.click('button[type="submit"], input[type="submit"]'),
            ]);

            // Après redirection vers journal_compte/1532, récupérer l'ID via l'endpoint AJAX du journal
            // Le journal utilise une DataTable chargée en AJAX — les liens ne sont pas dans le DOM initial.
            await page.waitForLoadState('networkidle');
            console.log(`URL après soumission: ${page.url()}`);

            // Appel direct à l'endpoint datatable pour récupérer les écritures du compte asterix
            const response = await page.request.get(
                `/index.php/compta/datatable_journal_compte/${ASTERIX_COMPTE_ID}`,
                { params: { sEcho: '1', iDisplayStart: '0', iDisplayLength: '10' } }
            );

            if (response.ok()) {
                try {
                    const json = await response.json();
                    const rows = json.aaData || [];
                    // Trouver la ligne correspondant à notre description de test
                    for (const row of rows) {
                        const rowStr = row.join ? row.join('|') : String(row);
                        if (rowStr.includes(TEST_DESCRIPTION)) {
                            // Extraire l'ID depuis le lien d'édition dans la première cellule
                            const idMatch = String(row[0]).match(/\/compta\/edit\/(\d+)/);
                            if (idMatch) {
                                asterixEcritureId = idMatch[1];
                                break;
                            }
                        }
                    }
                    console.log(`JSON du journal reçu — ${rows.length} lignes, ID extrait: ${asterixEcritureId}`);
                } catch (e) {
                    console.warn('Erreur parsing JSON datatable:', e.message);
                }
            }

            if (!asterixEcritureId) {
                console.warn('Impossible de récupérer l\'ID de l\'écriture de test depuis le journal');
            }
        } finally {
            await page.close();
        }
    });

    // ── Nettoyage : supprimer l'écriture de test ─────────────────────────────
    test.afterAll(async ({ browser }) => {
        if (!asterixEcritureId) return;
        const page = await browser.newPage();
        try {
            await page.goto('/auth/login');
            await page.fill('input[name="username"]', 'panoramix');
            await page.fill('input[name="password"]', 'password');
            const sectionSelect = page.locator('select[name="section"]');
            if (await sectionSelect.count() > 0) {
                await sectionSelect.selectOption(PLANEUR_SECTION);
            }
            await page.click('input[type="submit"], button[type="submit"]');
            await page.waitForLoadState('networkidle');

            await page.goto(`/index.php/compta/delete/${asterixEcritureId}`);
            await page.waitForLoadState('networkidle');
            console.log(`Écriture de test supprimée — ID: ${asterixEcritureId}`);
        } finally {
            await page.close();
        }
    });

    // ── Test 1 : Accès refusé sur le compte d'un autre membre ────────────────
    // Ce test PASSE avant ET après la correction (refus pour la bonne raison après fix).
    test('refus accès aux justificatifs d\'un autre compte', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login('asterix', 'password', PLANEUR_SECTION);

        await page.goto(`/index.php/compta/get_attachments_section/${SFRISON_ECRITURE_ID}`);
        await page.waitForLoadState('networkidle');

        const content = await page.content();
        expect(content).toContain('Accès non autorisé');
    });

    // ── Test 2 : Accès autorisé sur son propre compte — ÉCHOUE AVANT LE FIX ─
    test('accès autorisé aux justificatifs de son propre compte', async ({ page }) => {
        if (!asterixEcritureId) {
            test.skip(true, 'Écriture de test non créée en beforeAll — vérifier les logs');
        }

        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login('asterix', 'password', PLANEUR_SECTION);

        await page.goto(`/index.php/compta/get_attachments_section/${asterixEcritureId}`);
        await page.waitForLoadState('networkidle');

        const content = await page.content();

        // NE DOIT PAS afficher "Accès non autorisé"
        expect(content).not.toContain('Accès non autorisé');
        // DOIT afficher la section justificatifs (vide puisqu'aucun n'a été créé)
        expect(content).toContain('Aucun justificatif');
    });

    // ── Test 3 : Bouton Créer absent pour un non-trésorier ───────────────────
    test('bouton Créer absent pour un non-trésorier', async ({ page }) => {
        if (!asterixEcritureId) {
            test.skip(true, 'Écriture de test non créée en beforeAll — vérifier les logs');
        }

        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login('asterix', 'password', PLANEUR_SECTION);

        await page.goto(`/index.php/compta/get_attachments_section/${asterixEcritureId}`);
        await page.waitForLoadState('networkidle');

        const content = await page.content();

        // Le bouton Créer ne doit pas apparaître pour un non-trésorier
        expect(content).not.toContain('showCreateForm');
        expect(content).not.toContain('id="showCreateForm"');
    });
});
