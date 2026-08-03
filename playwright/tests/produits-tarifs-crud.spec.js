const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

/**
 * Parcours produits + tarifs.
 *
 * Le bouton "Tarifs" qui ouvrait la sous-page tarifs/page/{produit_id} depuis
 * la liste des produits a ete retire (les tarifs se gerent desormais depuis
 * le panneau integre a produits/create et produits/edit). La sous-page
 * tarifs/page/{id} et son CRUD standalone (tarifs/create, tarifs/edit,
 * tarifs/delete) restent fonctionnels et sont ici atteints par URL directe,
 * plus par un bouton.
 *
 * Verifie de bout en bout, dans un vrai navigateur :
 *  - la liste des produits affiche la colonne "tarif du jour" (restauree),
 *  - un produit peut etre cree avec un tarif initial (obligatoire) via le
 *    panneau integre, puis un second tarif ajoute via le CRUD standalone,
 *  - la suppression d'un produit est bloquee tant qu'il a des tarifs,
 *  - le panneau integre (produits/create et produits/edit) permet
 *    d'ajouter/modifier/supprimer des tarifs sans quitter la page, avec la
 *    regle "au moins un tarif" appliquee cote client et cote serveur,
 *  - nettoyage des donnees de test.
 */
test.describe('Produits -> Tarifs CRUD', () => {
  let loginPage;
  const reference = 'ZZ_PW_TEST_' + Date.now();

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login('testadmin', 'password', '1'); // Section Planeur
    await loginPage.verifyLoggedIn();
  });

  test.afterEach(async ({ page }) => {
    if (!page.isClosed()) {
      await loginPage.logout();
    }
  });

  test('creer un produit, gerer ses tarifs, puis nettoyer', async ({ page }) => {
    // 1. Liste des produits
    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h3')).toContainText('Produits');

    // 2. Creation du produit avec un tarif initial (obligatoire) via le
    //    panneau integre au formulaire
    await page.goto('/index.php/produits/create');
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="reference"]', reference);
    await page.fill('input[name="description"]', 'Produit test Playwright');
    await page.selectOption('select[name="compte"]', { index: 1 });
    await page.fill('#tarif_date', '2026-01-01');
    await page.fill('#tarif_prix', '10.00');
    await page.click('#tarif_add_btn');
    await expect(page.locator('#tarifs_tbody tr')).toHaveCount(1);
    await page.click('input[type="submit"][name="button"]');
    await page.waitForLoadState('networkidle');

    // 3. Le produit doit apparaitre dans la liste, avec son prix affiche
    //    (colonne "tarif du jour")
    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    const row = page.locator('tr', { hasText: reference });
    await expect(row).toBeVisible();
    await expect(row.locator('td[data-field="prix"]')).toContainText('10,00');

    const produitId = await row.locator('a[href*="/produits/edit/"]').getAttribute('href')
      .then(href => href.match(/\/produits\/edit\/(\d+)/)[1]);

    // 4. Sous-page tarifs/page/{id} : plus liee depuis l'UI mais toujours
    //    fonctionnelle par URL directe. Ajout d'un second tarif via le
    //    formulaire standalone tarifs/create (produit_id pre-rempli).
    await page.goto(`/index.php/tarifs/create?produit_id=${produitId}`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('input[name="produit_id"]')).toHaveValue(produitId);
    await page.fill('input[name="prix"]', '33.50');
    await page.click('input[type="submit"][name="button"]');
    await page.waitForLoadState('networkidle');

    // 5. Le tarif doit apparaitre dans la liste scopee au produit
    await page.goto(`/index.php/tarifs/page/${produitId}`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('td[data-field="prix"]').first()).toContainText('33,50');

    // 6. Edition du tarif
    const editLink = page.locator('a[href*="/tarifs/edit/"]').first();
    await editLink.click();
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="prix"]', '44.00');
    await page.click('input[type="submit"][name="button"]');
    await page.waitForLoadState('networkidle');

    await page.goto(`/index.php/tarifs/page/${produitId}`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('td[data-field="prix"]').first()).toContainText('44,00');

    // 6b. Le prix modifie se reflete aussi dans la colonne de produits/page
    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('tr', { hasText: reference }).locator('td[data-field="prix"]')).toContainText('44,00');

    // 7. Tentative de suppression du produit alors qu'il a encore des tarifs :
    //    bloquee par la contrainte fk_tarifs_produit (RESTRICT, migration 147).
    //    L'utilisateur doit voir un message explicite au lieu d'un echec
    //    silencieux (regression pour doc/reviews/pr84_produits_tarifs_refactoring.md,
    //    finding #1).
    let popupMessage = null;
    const dialogHandler = async dialog => {
      if (dialog.type() === 'alert') {
        popupMessage = dialog.message();
      }
      await dialog.accept();
    };
    page.on('dialog', dialogHandler);

    await page.locator('tr', { hasText: reference }).locator('a[href*="/produits/delete/"]').click();
    await page.waitForLoadState('networkidle');
    page.off('dialog', dialogHandler);

    expect(popupMessage).toContain('impossible');

    // Le produit doit toujours exister (suppression bloquee)
    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('tr', { hasText: reference })).toBeVisible();

    // Les tarifs (initial + ajoute a l'etape 4) doivent toujours exister aussi
    await page.goto(`/index.php/tarifs/page/${produitId}`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('td[data-field="prix"]')).toHaveCount(2);

    // 8. Suppression des deux tarifs
    page.once('dialog', dialog => dialog.accept());
    await page.locator('a[href*="/tarifs/delete/"]').first().click();
    await page.waitForLoadState('networkidle');
    page.once('dialog', dialog => dialog.accept());
    await page.locator('a[href*="/tarifs/delete/"]').first().click();
    await page.waitForLoadState('networkidle');

    await page.goto(`/index.php/tarifs/page/${produitId}`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('td[data-field="prix"]')).toHaveCount(0);

    // 9. Nettoyage : suppression du produit de test
    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    page.once('dialog', dialog => dialog.accept());
    await page.locator('tr', { hasText: reference }).locator('a[href*="/produits/delete/"]').click();
    await page.waitForLoadState('networkidle');

    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('tr', { hasText: reference })).toHaveCount(0);
  });

  test('panneau tarifs integre sur produits/create et produits/edit', async ({ page }) => {
    const reference = 'ZZ_PW_INLINE_' + Date.now();

    // 1. produits/create : le panneau tarifs est present, vide au depart
    await page.goto('/index.php/produits/create');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#tarifs_tbody tr')).toHaveCount(0);

    await page.fill('input[name="reference"]', reference);
    await page.fill('input[name="description"]', 'Produit test panneau tarifs');
    await page.selectOption('select[name="compte"]', { index: 1 });

    // 2. Tentative de soumission sans aucun tarif : bloquee cote client
    await page.click('input[type="submit"][name="button"]');
    await expect(page.locator('#tarifs_error')).toBeVisible();
    await expect(page.locator('#tarifs_error')).toContainText('tarif');
    await expect(page).toHaveURL(/produits\/create/);

    // 3. Ajout d'un tarif via le panneau (sans quitter la page)
    await page.fill('#tarif_date', '2026-01-01');
    await page.fill('#tarif_prix', '42.50');
    await page.click('#tarif_add_btn');
    await expect(page.locator('#tarifs_tbody tr')).toHaveCount(1);
    await expect(page.locator('#tarifs_tbody tr')).toContainText('42.5');

    // 4. Soumission : le produit est cree avec son tarif, visible directement
    //    dans la colonne "tarif du jour" de produits/page
    await page.click('input[type="submit"][name="button"]');
    await page.waitForLoadState('networkidle');

    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    const row = page.locator('tr', { hasText: reference });
    await expect(row).toBeVisible();
    await expect(row.locator('td[data-field="prix"]')).toContainText('42,50');

    const produitId = await row.locator('a[href*="/produits/edit/"]').getAttribute('href')
      .then(href => href.match(/\/produits\/edit\/(\d+)/)[1]);

    // 5. produits/edit : le tarif existant est pre-rempli dans le panneau
    await row.locator('a[href*="/produits/edit/"]').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#tarifs_tbody tr')).toHaveCount(1);
    await expect(page.locator('#tarifs_tbody tr')).toContainText('42.5');

    // 6. Modification du tarif existant via le panneau
    await page.locator('#tarifs_tbody tr').locator('button.btn-primary').click();
    await expect(page.locator('#tarif_prix')).toHaveValue('42.50');
    await page.fill('#tarif_prix', '55.00');
    await page.click('#tarif_add_btn'); // devenu "Mettre a jour"
    await expect(page.locator('#tarifs_tbody tr')).toHaveCount(1);
    await expect(page.locator('#tarifs_tbody tr')).toContainText('55');

    // 7. Suppression du dernier tarif restant : refusee, message explicite
    await page.locator('#tarifs_tbody tr').locator('button.btn-danger').click();
    await expect(page.locator('#tarifs_error')).toBeVisible();
    await expect(page.locator('#tarifs_tbody tr')).toHaveCount(1);

    // 8. Ajout d'un second tarif puis suppression du premier
    await page.fill('#tarif_date', '2026-02-01');
    await page.fill('#tarif_prix', '60.00');
    await page.click('#tarif_add_btn');
    await expect(page.locator('#tarifs_tbody tr')).toHaveCount(2);

    await page.locator('#tarifs_tbody tr').first().locator('button.btn-danger').click();
    await expect(page.locator('#tarifs_tbody tr')).toHaveCount(1);
    await expect(page.locator('#tarifs_tbody tr')).toContainText('60');

    // 9. Soumission de la modification : le nouveau prix est visible
    //    directement dans la liste des produits
    await page.click('input[type="submit"][name="button"]');
    await page.waitForLoadState('networkidle');

    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('tr', { hasText: reference }).locator('td[data-field="prix"]')).toContainText('60,00');

    // 10. Nettoyage : suppression du tarif restant (sous-page atteinte par
    //     URL directe, plus de bouton) puis du produit
    await page.goto(`/index.php/tarifs/page/${produitId}`);
    await page.waitForLoadState('networkidle');
    page.once('dialog', dialog => dialog.accept());
    await page.locator('a[href*="/tarifs/delete/"]').first().click();
    await page.waitForLoadState('networkidle');

    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    page.once('dialog', dialog => dialog.accept());
    await page.locator('tr', { hasText: reference }).locator('a[href*="/produits/delete/"]').click();
    await page.waitForLoadState('networkidle');

    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('tr', { hasText: reference })).toHaveCount(0);
  });
});
