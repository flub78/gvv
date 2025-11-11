const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

/**
 * Test pour vérifier le fonctionnement des checkboxes dans l'interface licences/per_year
 *
 * Ce test vérifie que:
 * 1. L'interface affiche correctement des checkboxes
 * 2. Cocher une checkbox crée une licence (appelle licences/set)
 * 3. Décocher une checkbox supprime une licence (appelle licences/switch_it)
 * 4. Les requêtes AJAX fonctionnent correctement
 */

test.describe('Licences Checkbox Interface', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);

    // Login en tant qu'utilisateur CA (admin)
    await loginPage.open();
    await loginPage.login('ca', 'frederic', '1'); // Section Planeur
    await loginPage.verifyLoggedIn();
  });

  test.afterEach(async ({ page }) => {
    if (!page.isClosed()) {
      await loginPage.logout();
    }
  });

  test('should display licences page with checkboxes', async ({ page }) => {
    // Naviguer vers la page des licences par année
    await page.goto('/licences/per_year');
    await page.waitForLoadState('networkidle');

    // Vérifier que le titre est présent
    await expect(page.locator('h3:has-text("Licences")')).toBeVisible();

    // Vérifier qu'il y a des checkboxes avec la classe licence-checkbox
    const checkboxes = page.locator('input.licence-checkbox[type="checkbox"]');
    const count = await checkboxes.count();

    console.log(`Found ${count} licence checkboxes`);
    expect(count).toBeGreaterThan(0);

    // Vérifier que les checkboxes ont les attributs data nécessaires
    const firstCheckbox = checkboxes.first();
    await expect(firstCheckbox).toHaveAttribute('data-pilote');
    await expect(firstCheckbox).toHaveAttribute('data-year');
    await expect(firstCheckbox).toHaveAttribute('data-type');

    // Screenshot pour documentation
    await page.screenshot({ path: 'playwright/screenshots/licences-checkboxes.png', fullPage: true });
  });

  test('should create licence when checking empty checkbox', async ({ page }) => {
    // Naviguer vers la page des licences
    await page.goto('/licences/per_year');
    await page.waitForLoadState('networkidle');

    // Trouver une checkbox non cochée
    const uncheckedCheckbox = page.locator('input.licence-checkbox[type="checkbox"]:not(:checked)').first();

    // Vérifier qu'elle existe
    const isVisible = await uncheckedCheckbox.isVisible();
    if (!isVisible) {
      console.log('No unchecked checkbox found, skipping test');
      test.skip();
      return;
    }

    // Récupérer les attributs avant de cocher
    const pilote = await uncheckedCheckbox.getAttribute('data-pilote');
    const year = await uncheckedCheckbox.getAttribute('data-year');
    const type = await uncheckedCheckbox.getAttribute('data-type');

    console.log(`Testing licence creation for pilote=${pilote}, year=${year}, type=${type}`);

    // Intercepter la requête AJAX
    const responsePromise = page.waitForResponse(
      response => response.url().includes(`licences/set/${pilote}/${year}/${type}`)
    );

    // Cocher la checkbox
    await uncheckedCheckbox.check();

    // Attendre la réponse
    const response = await responsePromise;
    expect(response.status()).toBe(200);

    // Vérifier que la checkbox est maintenant cochée
    await expect(uncheckedCheckbox).toBeChecked();

    console.log('Licence created successfully');
  });

  test('should delete licence when unchecking checked checkbox', async ({ page }) => {
    // Naviguer vers la page des licences
    await page.goto('/licences/per_year');
    await page.waitForLoadState('networkidle');

    // Trouver une checkbox cochée
    const checkedCheckbox = page.locator('input.licence-checkbox[type="checkbox"]:checked').first();

    // Vérifier qu'elle existe
    const isVisible = await checkedCheckbox.isVisible();
    if (!isVisible) {
      console.log('No checked checkbox found, skipping test');
      test.skip();
      return;
    }

    // Récupérer les attributs avant de décocher
    const pilote = await checkedCheckbox.getAttribute('data-pilote');
    const year = await checkedCheckbox.getAttribute('data-year');
    const type = await checkedCheckbox.getAttribute('data-type');

    console.log(`Testing licence deletion for pilote=${pilote}, year=${year}, type=${type}`);

    // Intercepter la requête AJAX
    const responsePromise = page.waitForResponse(
      response => response.url().includes(`licences/switch_it/${pilote}/${year}/${type}`)
    );

    // Décocher la checkbox
    await checkedCheckbox.uncheck();

    // Attendre la réponse
    const response = await responsePromise;
    expect(response.status()).toBe(200);

    // Vérifier que la checkbox est maintenant décochée
    await expect(checkedCheckbox).not.toBeChecked();

    console.log('Licence deleted successfully');
  });

  test('should toggle licence multiple times', async ({ page }) => {
    // Naviguer vers la page des licences
    await page.goto('/licences/per_year');
    await page.waitForLoadState('networkidle');

    // Trouver une checkbox
    const checkbox = page.locator('input.licence-checkbox[type="checkbox"]').first();

    const pilote = await checkbox.getAttribute('data-pilote');
    const year = await checkbox.getAttribute('data-year');
    const type = await checkbox.getAttribute('data-type');

    console.log(`Testing multiple toggles for pilote=${pilote}, year=${year}, type=${type}`);

    // État initial
    const initiallyChecked = await checkbox.isChecked();
    console.log(`Initial state: ${initiallyChecked ? 'checked' : 'unchecked'}`);

    // Premier toggle
    if (initiallyChecked) {
      await checkbox.uncheck();
      await page.waitForTimeout(500);
      await expect(checkbox).not.toBeChecked();
    } else {
      await checkbox.check();
      await page.waitForTimeout(500);
      await expect(checkbox).toBeChecked();
    }

    // Deuxième toggle (retour à l'état initial)
    if (initiallyChecked) {
      await checkbox.check();
      await page.waitForTimeout(500);
      await expect(checkbox).toBeChecked();
    } else {
      await checkbox.uncheck();
      await page.waitForTimeout(500);
      await expect(checkbox).not.toBeChecked();
    }

    console.log('Multiple toggles successful');
  });
});
