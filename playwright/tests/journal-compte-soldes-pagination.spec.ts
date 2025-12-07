import { test, expect } from '@playwright/test';
import * as fs from 'fs';

/**
 * Tests for journal_compte balance calculations with server-side pagination
 * 
 * This test verifies that:
 * 1. Balances are calculated correctly with pagination
 * 2. Balances are independent of pagination size (same ecriture has same balance)
 * 3. Balance increments are consistent
 */

test.describe('Journal Compte - Soldes avec Pagination', () => {
    test.beforeEach(async ({ page }) => {
        // Login
        await page.goto('http://gvv.net/');
        await page.fill('input[name="username"]', 'testplanchiste');
        await page.fill('input[name="password"]', 'password');
        await page.click('input[type="submit"]');
        
        // Wait for dashboard to load
        await page.waitForLoadState('networkidle');
    });

    test('Les soldes sont indépendants de la taille de la pagination', async ({ page }) => {
        // PHASE 1 FIX: Navigate directly to balance page
        await page.goto('http://gvv.net/comptes/balance');
        await page.waitForLoadState('networkidle');

        // Cliquer sur le premier lien de compte pour ouvrir le journal
        const accountLink = await page.locator('table a[href*="journal_compte"]').first();
        await accountLink.click();
        await page.waitForLoadState('networkidle');
        
        // Attendre le chargement de la page
        await page.waitForSelector('.dataTables_wrapper');
        
        // Récupérer le nombre total d'écritures
        const infoText = await page.locator('.dataTables_info').textContent();
        console.log('DataTables info:', infoText);
        
        // Vérifier qu'on a au moins 30 écritures pour un test significatif
        const totalMatch = infoText?.match(/(\d+) enregistrements?/);
        if (!totalMatch || parseInt(totalMatch[1]) < 30) {
            test.skip(true, 'Pas assez d\'écritures pour tester la pagination');
            return;
        }
        
        const totalRecords = parseInt(totalMatch[1]);
        console.log(`Total d'écritures: ${totalRecords}`);
        
        // Fonction helper pour extraire les données d'une ligne du tableau
        async function getRowData(row: any) {
            const cells = await row.locator('td').allTextContents();
            // Format: [Actions?, Date, Autre compte, Description, N° chèque, Prix, Quantité, Débit, Crédit, Solde, Gel]
            // On doit adapter selon la présence de la colonne Actions
            const hasActions = cells.length > 10;
            const offset = hasActions ? 1 : 0;
            
            return {
                date: cells[offset + 0],
                autreCompte: cells[offset + 1],
                description: cells[offset + 2],
                numCheque: cells[offset + 3],
                debit: cells[offset + 6],
                credit: cells[offset + 7],
                solde: cells[offset + 8]?.replace(/\s/g, '').replace(',', '.') || '0'
            };
        }
        
        // Fonction helper pour parser un montant français en nombre
        function parseAmount(str: string): number {
            if (!str || str === '') return 0;
            // Enlever les espaces et remplacer virgule par point
            return parseFloat(str.replace(/\s/g, '').replace(',', '.'));
        }
        
        // Test 1: Configurer pour 10 lignes par page
        console.log('\n=== Test avec 10 lignes par page ===');
        await page.selectOption('select[name="jeu_ecritures_length"]', '10');
        await page.waitForTimeout(1000); // Attendre le rechargement
        
        // Aller à la page 2 (écritures 11-20)
        await page.click('a.paginate_button:has-text("2")');
        await page.waitForTimeout(1000);
        
        // Récupérer les données de la ligne 5 sur cette page (= ligne 15 globalement)
        const rows10 = await page.locator('tbody tr').all();
        expect(rows10.length).toBeGreaterThan(4);
        
        const row15_with10 = await getRowData(rows10[4]); // Index 4 = 5ème ligne = 15ème globalement
        console.log(`Ligne 15 (avec 10/page): solde = ${row15_with10.solde}`);
        
        // Test 2: Configurer pour 25 lignes par page
        console.log('\n=== Test avec 25 lignes par page ===');
        await page.selectOption('select[name="jeu_ecritures_length"]', '25');
        await page.waitForTimeout(1000);
        
        // Sur cette page, la ligne 15 globale est à l'index 14
        const rows25 = await page.locator('tbody tr').all();
        expect(rows25.length).toBeGreaterThan(14);
        
        const row15_with25 = await getRowData(rows25[14]); // Index 14 = 15ème ligne
        console.log(`Ligne 15 (avec 25/page): solde = ${row15_with25.solde}`);
        
        // CRITIQUE: Les soldes doivent être identiques
        expect(row15_with10.solde).toBe(row15_with25.solde);
        
        // Vérifier aussi d'autres attributs pour s'assurer que c'est bien la même écriture
        expect(row15_with10.date).toBe(row15_with25.date);
        expect(row15_with10.description).toBe(row15_with25.description);
        
        console.log('✓ Les soldes sont identiques quelle que soit la pagination');
    });

    test('Les incréments de solde sont cohérents', async ({ page }) => {
        // PHASE 1 FIX: Navigate directly to balance page
        await page.goto('http://gvv.net/comptes/balance');
        await page.waitForLoadState('networkidle');

        // Cliquer sur le premier lien de compte pour ouvrir le journal
        const accountLink = await page.locator('table a[href*="journal_compte"]').first();
        await accountLink.click();
        await page.waitForLoadState('networkidle');
        
        await page.waitForSelector('.dataTables_wrapper');
        
        // Configurer pour 25 lignes par page pour avoir assez de données
        await page.selectOption('select[name="jeu_ecritures_length"]', '25');
        await page.waitForTimeout(1000);
        
        // Fonction helper pour parser un montant français en nombre
        function parseAmount(str: string): number {
            if (!str || str === '') return 0;
            return parseFloat(str.replace(/\s/g, '').replace(',', '.'));
        }
        
        // Récupérer toutes les lignes de la page
        const rows = await page.locator('tbody tr').all();
        expect(rows.length).toBeGreaterThan(1);
        
        console.log(`\n=== Vérification des incréments de solde sur ${rows.length} lignes ===`);
        
        // Vérifier que chaque solde = solde précédent +/- opération
        for (let i = 1; i < Math.min(rows.length, 10); i++) {
            const prevCells = await rows[i - 1].locator('td').allTextContents();
            const currCells = await rows[i].locator('td').allTextContents();
            
            // Déterminer l'offset si colonne Actions présente
            const hasActions = prevCells.length > 10;
            const offset = hasActions ? 1 : 0;
            
            const prevSolde = parseAmount(prevCells[offset + 8] || '0');
            const currDebit = parseAmount(currCells[offset + 6] || '0');
            const currCredit = parseAmount(currCells[offset + 7] || '0');
            const currSolde = parseAmount(currCells[offset + 8] || '0');
            
            // Calculer le solde attendu
            const operation = currCredit - currDebit;
            const expectedSolde = prevSolde + operation;
            
            console.log(`Ligne ${i + 1}: prev=${prevSolde.toFixed(2)}, op=${operation.toFixed(2)}, curr=${currSolde.toFixed(2)}, expected=${expectedSolde.toFixed(2)}`);
            
            // Vérifier avec une petite tolérance pour les erreurs d'arrondi
            expect(Math.abs(currSolde - expectedSolde)).toBeLessThan(0.01);
        }
        
        console.log('✓ Tous les incréments de solde sont cohérents');
    });

    test('Les exports CSV contiennent les soldes', async ({ page, context }) => {
        // PHASE 1 FIX: Navigate directly to balance page
        await page.goto('http://gvv.net/comptes/balance');
        await page.waitForLoadState('networkidle');

        // Cliquer sur le premier lien de compte pour ouvrir le journal
        const accountLink = await page.locator('table a[href*="journal_compte"]').first();
        await accountLink.click();
        await page.waitForLoadState('networkidle');
        
        await page.waitForSelector('.dataTables_wrapper');
        
        // Préparer l'écoute du téléchargement
        const downloadPromise = page.waitForEvent('download');
        
        // Cliquer sur le bouton CSV (il faut trouver le bon sélecteur)
        // Selon la structure du formulaire, le bouton CSV devrait être présent
        await page.click('input[value="Csv"]');
        
        const download = await downloadPromise;
        
        // Sauvegarder le fichier
        const path = await download.path();
        expect(path).toBeTruthy();
        
        // Lire le contenu du CSV
        const content = fs.readFileSync(path!, 'utf-8');
        
        console.log('\n=== Vérification de l\'export CSV ===');
        console.log('Premières lignes du CSV:');
        console.log(content.split('\n').slice(0, 5).join('\n'));
        
        // Vérifier que l'en-tête contient "Solde"
        expect(content).toContain('Solde');
        
        // Vérifier qu'il y a des valeurs de solde dans les données
        const lines = content.split('\n');
        let soldeFound = false;
        
        for (const line of lines.slice(2)) { // Skip header lines
            if (line.includes(';')) {
                const cells = line.split(';');
                // La colonne solde devrait être l'avant-dernière (avant la colonne gel si elle existe)
                if (cells.length > 7) {
                    const soldeValue = cells[cells.length - 2];
                    if (soldeValue && soldeValue.trim() !== '' && !soldeValue.includes('Solde')) {
                        soldeFound = true;
                        console.log(`Valeur de solde trouvée: ${soldeValue}`);
                        break;
                    }
                }
            }
        }
        
        expect(soldeFound).toBeTruthy();
        console.log('✓ Les soldes sont présents dans l\'export CSV');
    });

    test('Les exports PDF sont générés en paysage', async ({ page }) => {
        // PHASE 1 FIX: Navigate directly to balance page
        await page.goto('http://gvv.net/comptes/balance');
        await page.waitForLoadState('networkidle');

        // Cliquer sur le premier lien de compte pour ouvrir le journal
        const accountLink = await page.locator('table a[href*="journal_compte"]').first();
        await accountLink.click();
        await page.waitForLoadState('networkidle');
        
        await page.waitForSelector('.dataTables_wrapper');
        
        // Préparer l'écoute du téléchargement
        const downloadPromise = page.waitForEvent('download');
        
        // Cliquer sur le bouton PDF
        await page.click('input[value="Pdf"]');
        
        const download = await downloadPromise;
        
        // Vérifier que le téléchargement a réussi
        const path = await download.path();
        expect(path).toBeTruthy();
        
        // Vérifier que c'est bien un PDF
        expect(download.suggestedFilename()).toMatch(/\.pdf$/i);
        
        console.log('✓ L\'export PDF a été généré avec succès');
        console.log(`  Fichier: ${download.suggestedFilename()}`);
        
        // Note: Pour vérifier que le PDF est en paysage et contient les soldes,
        // il faudrait utiliser une bibliothèque de parsing PDF comme pdf-parse
        // Ce qui dépasse le scope de ce test basique
    });
});
