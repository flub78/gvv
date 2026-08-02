const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

/**
 * Parcours produits -> bouton Tarifs -> CRUD tarifs (étape 11 du plan
 * refactoring_produits_tarifs_plan.md).
 *
 * Vérifie de bout en bout, dans un vrai navigateur :
 *  - la liste des produits s'affiche et permet la création d'un produit,
 *  - le bouton "Tarifs" d'une ligne produit ouvre la liste des tarifs de ce
 *    produit (sous-CRUD scopé par produit_id),
 *  - un tarif peut y être créé, édité puis supprimé,
 *  - le produit peut ensuite être supprimé (nettoyage).
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

    // 2. Creation du produit
    await page.goto('/index.php/produits/create');
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="reference"]', reference);
    await page.fill('input[name="description"]', 'Produit test Playwright');
    await page.selectOption('select[name="compte"]', { index: 1 });
    await page.click('input[type="submit"][name="button"]');
    await page.waitForLoadState('networkidle');

    // 3. Le produit doit apparaitre dans la liste, avec un bouton Tarifs
    await page.goto('/index.php/produits/page');
    await page.waitForLoadState('networkidle');
    const row = page.locator('tr', { hasText: reference });
    await expect(row).toBeVisible();
    const tarifsButton = row.locator('a[href*="/produits/tarifs/"]');
    await expect(tarifsButton).toBeVisible();

    // 4. Bouton Tarifs -> sous-CRUD scope au produit
    await tarifsButton.click();
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/\/tarifs\/page\/\d+/);
    await expect(page.locator('h3')).toContainText('Tarifs');
    await expect(page.locator('h3')).toContainText('Produit test Playwright');

    const produitId = page.url().match(/\/tarifs\/page\/(\d+)/)[1];

    // 5. Creation d'un tarif pour ce produit (produit_id pre-rempli via ?produit_id=)
    await page.goto(`/index.php/tarifs/create?produit_id=${produitId}`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('input[name="produit_id"]')).toHaveValue(produitId);
    await page.fill('input[name="prix"]', '33.50');
    await page.click('input[type="submit"][name="button"]');
    await page.waitForLoadState('networkidle');

    // 6. Le tarif doit apparaitre dans la liste scopee au produit
    await page.goto(`/index.php/tarifs/page/${produitId}`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('td[data-field="prix"]').first()).toContainText('33,50');

    // 7. Edition du tarif
    const editLink = page.locator('a[href*="/tarifs/edit/"]').first();
    await editLink.click();
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="prix"]', '44.00');
    await page.click('input[type="submit"][name="button"]');
    await page.waitForLoadState('networkidle');

    await page.goto(`/index.php/tarifs/page/${produitId}`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('td[data-field="prix"]').first()).toContainText('44,00');

    // 8. Suppression du tarif
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
});
