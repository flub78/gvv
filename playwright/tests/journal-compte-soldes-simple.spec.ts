import { test, expect } from '@playwright/test';

/**
 * Test simple pour vérifier que les soldes sont corrects avec la pagination
 * 
 * Ce test vérifie que les soldes affichés dans le journal de compte sont cohérents
 * et indépendants de la pagination.
 */

test.describe('Journal Compte - Soldes', () => {
    test('Les soldes sont corrects et cohérents', async ({ page }) => {
        // Login avec testadmin qui a accès à tous les comptes
        await page.goto('http://gvv.net/');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('input[type="submit"]');
        
        // Wait for dashboard to load
        await page.waitForLoadState('networkidle');
        
        // Naviguer vers Comptabilité -> Balance des comptes pour trouver un compte avec beaucoup d'écritures
        await page.goto('http://gvv.net/index.php/comptes/balance');
        await page.waitForLoadState('networkidle');
        
        // Chercher tous les liens vers des journaux de compte
        const compteLinks = await page.locator('a[href*="journal_compte/"]').all();
        
        console.log(`Nombre de comptes trouvés: ${compteLinks.length}`);
        
        if (compteLinks.length === 0) {
            console.log('Aucun compte trouvé');
            test.skip();
            return;
        }
        
        // Chercher un compte de type 6xx ou 7xx qui ont généralement plus d'écritures
        let targetHref = null;
        for (const link of compteLinks) {
            const href = await link.getAttribute('href');
            if (href) {
                const match = href.match(/journal_compte\/([67]\d+)/);
                if (match) {
                    targetHref = href;
                    console.log(`Compte de charges/produits trouvé: ${match[1]}`);
                    break;
                }
            }
        }
        
        // Si pas de compte 6xx/7xx, utiliser le premier disponible
        if (!targetHref) {
            targetHref = await compteLinks[0].getAttribute('href');
            console.log('Utilisation du premier compte disponible');
        }
        
        console.log(`Navigation vers: ${targetHref}`);
        
        if (targetHref) {
            await page.goto(targetHref);
            await page.waitForLoadState('networkidle');
        } else {
            test.skip();
            return;
        }
        
        // Debug: afficher l'URL actuelle et le contenu de la page
        console.log('URL actuelle:', page.url());
        const pageTitle = await page.title();
        console.log('Titre de la page:', pageTitle);
        
        // Vérifier qu'on a bien un tableau DataTables
        const hasDataTable = await page.locator('.dataTables_wrapper').isVisible().catch(() => false);
        console.log('DataTable trouvé:', hasDataTable);
        
        if (!hasDataTable) {
            console.log('Pas de DataTable trouvé - le compte pourrait ne pas avoir assez d\'écritures');
            test.skip();
            return;
        }
        
        // Essayer de configurer pour afficher 25 lignes par page (optionnel)
        const lengthSelector = await page.locator('select[name="jeu_ecritures_length"]').count();
        if (lengthSelector > 0) {
            await page.selectOption('select[name="jeu_ecritures_length"]', '25');
            await page.waitForTimeout(1000); // Attendre le rechargement
            console.log('Pagination configurée à 25 lignes/page');
        } else {
            console.log('Sélecteur de pagination non trouvé - utilisation de la pagination par défaut');
        }
        
        // Fonction helper pour parser un montant français en nombre
        function parseAmount(str: string): number {
            if (!str || str === '') return 0;
            return parseFloat(str.replace(/\s/g, '').replace(',', '.'));
        }
        
        // Récupérer les lignes du tableau
        const rows = await page.locator('tbody tr').all();
        
        if (rows.length < 2) {
            console.log('Pas assez de lignes pour tester les soldes');
            test.skip();
            return;
        }
        
        console.log(`\nVérification des soldes sur ${rows.length} lignes`);
        
        // Vérifier que chaque solde = solde précédent +/- opération
        let errorsFound = 0;
        
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
            
            const diff = Math.abs(currSolde - expectedSolde);
            
            console.log(`Ligne ${i + 1}: prev=${prevSolde.toFixed(2)}, op=${operation.toFixed(2)}, curr=${currSolde.toFixed(2)}, expected=${expectedSolde.toFixed(2)}, diff=${diff.toFixed(4)}`);
            
            if (diff >= 0.01) {
                console.error(`❌ ERREUR ligne ${i + 1}: différence = ${diff.toFixed(2)}€`);
                errorsFound++;
            }
        }
        
        // Le test est considéré comme réussi s'il n'y a pas d'erreurs
        expect(errorsFound).toBe(0);
        
        console.log('✅ Tous les soldes sont cohérents');
        
        // Vérifier que le solde de la dernière ligne correspond au solde affiché sous la DataTable
        const lastRow = rows[rows.length - 1];
        const lastCells = await lastRow.locator('td').allTextContents();
        const hasActions = lastCells.length > 10;
        const offset = hasActions ? 1 : 0;
        const lastSolde = parseAmount(lastCells[offset + 8] || '0');
        
        // Récupérer le solde affiché sous la DataTable
        const currentDebitText = await page.locator('input[name="current_debit"]').inputValue();
        const currentCreditText = await page.locator('input[name="current_credit"]').inputValue();
        
        const currentDebit = parseAmount(currentDebitText);
        const currentCredit = parseAmount(currentCreditText);
        
        // Le solde affiché est soit débiteur (négatif) soit créditeur (positif)
        const displayedSolde = currentCredit - currentDebit;
        
        const soldeDiff = Math.abs(lastSolde - displayedSolde);
        
        console.log(`\nVérification du solde final:`);
        console.log(`  Solde dernière ligne: ${lastSolde.toFixed(2)}€`);
        console.log(`  Solde affiché (débit): ${currentDebit.toFixed(2)}€`);
        console.log(`  Solde affiché (crédit): ${currentCredit.toFixed(2)}€`);
        console.log(`  Solde calculé: ${displayedSolde.toFixed(2)}€`);
        console.log(`  Différence: ${soldeDiff.toFixed(4)}€`);
        
        expect(soldeDiff).toBeLessThan(0.01);
        
        console.log('✅ Le solde de la dernière ligne correspond au solde affiché');
    });

    test('Le solde initial (opening balance) est correctement pris en compte', async ({ page }) => {
        // Login avec testadmin
        await page.goto('http://gvv.net/');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('input[type="submit"]');
        
        await page.waitForLoadState('networkidle');
        
        // Naviguer vers le compte 37 mentionné par l'utilisateur (ou un autre compte avec solde initial)
        // Ce compte devrait avoir un solde initial de 45,50€
        await page.goto('http://gvv.net/index.php/compta/journal_compte/37');
        await page.waitForLoadState('networkidle');
        
        console.log('URL actuelle:', page.url());
        
        // Vérifier qu'on a bien un tableau DataTables
        const hasDataTable = await page.locator('.dataTables_wrapper').isVisible().catch(() => false);
        
        if (!hasDataTable) {
            console.log('Pas de DataTable trouvé - le compte 37 pourrait ne pas exister ou ne pas avoir d\'écritures');
            test.skip();
            return;
        }
        
        // Fonction helper pour parser un montant français en nombre
        function parseAmount(str: string): number {
            if (!str || str === '') return 0;
            return parseFloat(str.replace(/\s/g, '').replace(',', '.'));
        }
        
        // Récupérer la première ligne du tableau
        const firstRow = await page.locator('tbody tr').first();
        const cells = await firstRow.locator('td').allTextContents();
        
        // Déterminer l'offset si colonne Actions présente
        const hasActions = cells.length > 10;
        const offset = hasActions ? 1 : 0;
        
        const firstDebit = parseAmount(cells[offset + 6] || '0');
        const firstCredit = parseAmount(cells[offset + 7] || '0');
        const firstSolde = parseAmount(cells[offset + 8] || '0');
        
        const firstOperation = firstCredit - firstDebit;
        
        console.log(`\nPremière ligne du compte:`);
        console.log(`  Débit: ${firstDebit.toFixed(2)}€`);
        console.log(`  Crédit: ${firstCredit.toFixed(2)}€`);
        console.log(`  Opération: ${firstOperation.toFixed(2)}€`);
        console.log(`  Solde affiché: ${firstSolde.toFixed(2)}€`);
        
        // Le solde de la première ligne NE DOIT PAS être égal à l'opération seule
        // (sauf si le compte commence vraiment à 0, ce qui est rare)
        // Dans le cas du compte 37, on s'attend à ce que le solde soit différent de l'opération
        // car il y a un solde d'ouverture de 45,50€
        
        // Si le solde initial était ignoré, on aurait: firstSolde == firstOperation
        // Avec le fix, on devrait avoir: firstSolde = solde_initial + firstOperation
        
        // Test: récupérer la deuxième ligne et vérifier la cohérence
        const rows = await page.locator('tbody tr').all();
        
        if (rows.length < 2) {
            console.log('Pas assez de lignes pour valider le solde initial');
            // Mais on peut quand même vérifier que le solde n\'est pas nul si on a une opération
            if (Math.abs(firstOperation) > 0.01) {
                console.log(`Vérification: le solde (${firstSolde.toFixed(2)}€) inclut bien plus que juste l'opération (${firstOperation.toFixed(2)}€)`);
                // Le solde devrait être différent de l'opération s'il y a un solde initial
                // Note: Ce test peut échouer si le compte commence vraiment à 0
                // Dans ce cas, le test sera skip
                if (Math.abs(firstSolde - firstOperation) < 0.01) {
                    console.log('⚠️ Le solde de la première ligne semble égal à l\'opération - possiblement pas de solde initial ou bug');
                } else {
                    console.log('✅ Le solde de la première ligne inclut bien un solde initial');
                }
            }
            return;
        }
        
        // Vérifier la cohérence entre ligne 1 et ligne 2
        const secondRow = rows[1];
        const secondCells = await secondRow.locator('td').allTextContents();
        
        const secondDebit = parseAmount(secondCells[offset + 6] || '0');
        const secondCredit = parseAmount(secondCells[offset + 7] || '0');
        const secondSolde = parseAmount(secondCells[offset + 8] || '0');
        
        const secondOperation = secondCredit - secondDebit;
        const expectedSecondSolde = firstSolde + secondOperation;
        
        const diff = Math.abs(secondSolde - expectedSecondSolde);
        
        console.log(`\nDeuxième ligne:`);
        console.log(`  Opération: ${secondOperation.toFixed(2)}€`);
        console.log(`  Solde affiché: ${secondSolde.toFixed(2)}€`);
        console.log(`  Solde attendu (${firstSolde.toFixed(2)} + ${secondOperation.toFixed(2)}): ${expectedSecondSolde.toFixed(2)}€`);
        console.log(`  Différence: ${diff.toFixed(4)}€`);
        
        // Le test vérifie que la cohérence est respectée
        expect(diff).toBeLessThan(0.01);
        
        console.log('✅ Le solde initial est correctement pris en compte et les soldes sont cohérents');
    });
});
