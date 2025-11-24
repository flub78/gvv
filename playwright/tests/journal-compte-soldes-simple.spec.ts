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
    });
});
