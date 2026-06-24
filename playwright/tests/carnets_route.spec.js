/**
 * Playwright smoke tests — Vérification des carnets de route
 *
 * Vérifie l'accès, l'affichage du formulaire de filtres, le tableau DataTable,
 * et la présence des boutons d'export CSV et PDF.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/carnets_route.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const PAGE_URL  = '/index.php/carnets_route/page';
const LOGIN_URL = '/index.php/auth/login';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const PILOT_USER = { username: 'testuser',  password: 'password' };

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

// ── Accès ────────────────────────────────────────────────────────────────────

test('admin peut accéder à la page carnets de route', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(PAGE_URL);
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/auth\/login|403|404/);
    await expect(page.locator('h3')).toContainText('carnets de route', { ignoreCase: true });
});

test('utilisateur sans rôle est redirigé', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(PAGE_URL);
    await page.waitForLoadState('networkidle');

    // Doit être redirigé vers login ou afficher un message d'accès refusé
    const url = page.url();
    const body = await page.locator('body').textContent();
    const redirectedOrDenied =
        url.includes('auth/login') ||
        url.includes('403') ||
        body.includes('autoris') ||
        body.includes('accès');
    expect(redirectedOrDenied).toBeTruthy();
});

// ── Formulaire de filtres ─────────────────────────────────────────────────────

test('le formulaire de filtres est affiché', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(PAGE_URL);
    await page.waitForLoadState('networkidle');

    await expect(page.locator('select[name="carnet_macid"]')).toBeVisible();
    await expect(page.locator('input[name="carnet_date_debut"]')).toBeVisible();
    await expect(page.locator('input[name="carnet_date_fin"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
});

test('les dates par défaut sont correctement initialisées', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(PAGE_URL);
    await page.waitForLoadState('networkidle');

    const year = new Date().getFullYear().toString();
    const today = new Date().toISOString().slice(0, 10);

    const debut = await page.locator('input[name="carnet_date_debut"]').inputValue();
    const fin   = await page.locator('input[name="carnet_date_fin"]').inputValue();

    expect(debut).toMatch(new RegExp(`^${year}-01-01`));
    expect(fin).toBe(today);
});

// ── Sélection d'un avion et affichage du tableau ──────────────────────────────

test('sélectionner un avion affiche le tableau', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(PAGE_URL);
    await page.waitForLoadState('networkidle');

    // Récupère les options du sélecteur d'avion
    const options = await page.locator('select[name="carnet_macid"] option').all();
    if (options.length <= 1) {
        test.skip('Aucun avion disponible dans la base de données');
        return;
    }

    // Sélectionne le premier avion (index 1 = premier avion réel)
    const firstValue = await options[1].getAttribute('value');
    await page.selectOption('select[name="carnet_macid"]', firstValue);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // La page ne doit pas afficher d'erreur
    await expect(page).not.toHaveURL(/error|403|404/);

    // Le tableau ou le message "aucun vol" doit être visible
    const tableVisible  = await page.locator('#carnet-table').isVisible().catch(() => false);
    const noFlightMsg   = await page.locator('.alert-info').isVisible().catch(() => false);
    expect(tableVisible || noFlightMsg).toBeTruthy();
});

// ── DataTable ─────────────────────────────────────────────────────────────────

test('le widget DataTable est initialisé (searchbox visible)', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(PAGE_URL);
    await page.waitForLoadState('networkidle');

    const options = await page.locator('select[name="carnet_macid"] option').all();
    if (options.length <= 1) {
        test.skip('Aucun avion disponible dans la base de données');
        return;
    }

    const firstValue = await options[1].getAttribute('value');
    await page.selectOption('select[name="carnet_macid"]', firstValue);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const tableVisible = await page.locator('#carnet-table').isVisible().catch(() => false);
    if (!tableVisible) {
        test.skip('Pas de vols pour cet avion — DataTable non affiché');
        return;
    }

    // DataTable injecte une input de recherche dans le DOM
    const searchbox = page.locator('.dataTables_filter input[type="search"], .dataTables_filter input[type="text"]');
    await expect(searchbox).toBeVisible();
});

// ── Boutons d'export ──────────────────────────────────────────────────────────

test('les boutons CSV et PDF sont présents après sélection d\'un avion', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(PAGE_URL);
    await page.waitForLoadState('networkidle');

    const options = await page.locator('select[name="carnet_macid"] option').all();
    if (options.length <= 1) {
        test.skip('Aucun avion disponible dans la base de données');
        return;
    }

    const firstValue = await options[1].getAttribute('value');
    await page.selectOption('select[name="carnet_macid"]', firstValue);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const tableVisible = await page.locator('#carnet-table').isVisible().catch(() => false);
    if (!tableVisible) {
        test.skip('Pas de vols pour cet avion — boutons export non affichés');
        return;
    }

    await expect(page.locator('a[href*="carnets_route/csv"]')).toBeVisible();
    await expect(page.locator('a[href*="carnets_route/pdf"]')).toBeVisible();
});

// ── Export CSV ────────────────────────────────────────────────────────────────

test('l\'export CSV retourne du contenu texte', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(PAGE_URL);
    await page.waitForLoadState('networkidle');

    const options = await page.locator('select[name="carnet_macid"] option').all();
    if (options.length <= 1) {
        test.skip('Aucun avion disponible dans la base de données');
        return;
    }

    // Applique les filtres pour remplir la session
    const firstValue = await options[1].getAttribute('value');
    await page.selectOption('select[name="carnet_macid"]', firstValue);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const tableVisible = await page.locator('#carnet-table').isVisible().catch(() => false);
    if (!tableVisible) {
        test.skip('Pas de vols pour cet avion');
        return;
    }

    // Appelle l'endpoint CSV et vérifie le Content-Type
    const response = await page.request.get('/index.php/carnets_route/csv');
    expect(response.status()).toBe(200);
    const contentType = response.headers()['content-type'] || '';
    expect(contentType).toMatch(/text\/csv|text\/plain/i);
});
