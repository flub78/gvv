/**
 * Tests des trois niveaux de droits sur les vols de découverte
 *
 * Modèle de droits :
 *   - CA (ca)                  → lecture seule : voit la liste, aucun bouton d'action
 *   - Gestionnaire VD          → droits complets : créer, modifier, supprimer,
 *     (gestion_vd, tresorier,     boutons action et briefing
 *      bureau, admin)
 *   - Pilote VD (pilote_vd)    → droits partiels : boutons action et briefing,
 *                                 accès pre_flight et done (date, pilote, machine,
 *                                 contact urgence), pas d'édition globale
 *
 * Utilisateurs Gaulois utilisés :
 *   - goudurix      : trésorier section Avion (3) → gestionnaire VD
 *   - abraracourcix : CA section Avion (3)        → lecture seule
 *   - agecanonix    : pilote_vd section Planeur (1) → pilote VD
 *   - asterix       : simple utilisateur           → aucun droit VD
 *
 * Bugs corrigés (tests qui échouaient avant correction) :
 *   Bug 1 : tresorier ne pouvait pas modifier un VD → ensure_modification_rights()
 *           ne prenait pas en compte tresorier/bureau
 *   Bug 2 : pilote_vd ne voyait pas les boutons Briefing passager dans la liste
 *   Bug 3 : accès interdit retournait 404 au lieu de 403
 *
 * Usage :
 *   npx playwright test tests/vols-decouverte-droits-ca-pilote.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const VD_LIST_URL   = '/index.php/vols_decouverte/page';
const VD_CREATE_URL = '/index.php/vols_decouverte/create';

const AVION_SECTION   = '3';
const PLANEUR_SECTION = '1';

async function loginAs(page, username, section) {
    const lp = new LoginPage(page);
    await lp.open();
    await lp.login(username, 'password', section);
}

async function selectFirstNonEmptyOption(page, selector) {
    const options = page.locator(`${selector} option`);
    const count = await options.count();
    for (let i = 0; i < count; i++) {
        const value = (await options.nth(i).getAttribute('value')) || '';
        if (value.trim() !== '') {
            await page.selectOption(selector, value);
            return value;
        }
    }
    throw new Error(`Aucune option disponible pour ${selector}`);
}

test.describe('VD — trois niveaux de droits', () => {

    // VD en section Avion pour les tests gestionnaire et CA
    let vdIdAvion;
    let vdEditUrlAvion;

    // VD en section Planeur pour les tests pilote_vd (agecanonix)
    // (créé par le beforeAll du describe existant vols-decouverte-crud.spec.js ;
    //  on crée ici un VD propre pour ne pas dépendre de l'autre suite)
    let vdActionUrlPlaneur;
    let vdPreFlightUrlPlaneur;
    let vdDoneUrlPlaneur;

    test.beforeAll(async ({ browser }) => {
        // --- VD section Avion créé par goudurix (trésorier) ---
        const pageAvion = await browser.newPage();
        await loginAs(pageAvion, 'goudurix', AVION_SECTION);

        const ts = Date.now();
        const benefAvion = `PW DROITS AVION ${ts}`;

        await pageAvion.goto(VD_CREATE_URL);
        await pageAvion.waitForLoadState('domcontentloaded');
        await expect(pageAvion.locator('form[name="saisie"]')).toBeVisible({ timeout: 10000 });

        await selectFirstNonEmptyOption(pageAvion, 'select[name="product"]');
        await pageAvion.fill('input[name="beneficiaire"]', benefAvion);
        await pageAvion.fill('input[name="de_la_part"]',  'Test droits VD');
        await pageAvion.fill('input[name="beneficiaire_email"]', `pw-droits-avion-${ts}@example.test`);
        await pageAvion.click('button[type="submit"], input[type="submit"]');
        await pageAvion.waitForLoadState('domcontentloaded');

        await pageAvion.goto(VD_LIST_URL);
        await pageAvion.waitForLoadState('domcontentloaded');

        const rowAvion = pageAvion.locator('table tr', { hasText: benefAvion }).first();
        await expect(rowAvion).toBeVisible({ timeout: 10000 });

        const editHref = await rowAvion.locator('a[href*="/vols_decouverte/edit/"]').first().getAttribute('href');
        const m = editHref.match(/\/vols_decouverte\/edit\/(\d+)/);
        expect(m).toBeTruthy();
        vdIdAvion     = m[1];
        vdEditUrlAvion = `/index.php/vols_decouverte/edit/${vdIdAvion}`;

        await pageAvion.close();

        // --- VD section Planeur créé par testadmin pour les tests pilote_vd ---
        const pagePlaneur = await browser.newPage();
        await loginAs(pagePlaneur, 'testadmin', PLANEUR_SECTION);

        const benefPlaneur = `PW DROITS PLANEUR ${ts}`;

        await pagePlaneur.goto(VD_CREATE_URL);
        await pagePlaneur.waitForLoadState('domcontentloaded');
        await selectFirstNonEmptyOption(pagePlaneur, 'select[name="product"]');
        await pagePlaneur.fill('input[name="beneficiaire"]', benefPlaneur);
        await pagePlaneur.fill('input[name="de_la_part"]', 'Test pilote_vd');
        await pagePlaneur.fill('input[name="beneficiaire_email"]', `pw-droits-planeur-${ts}@example.test`);
        await pagePlaneur.click('button[type="submit"], input[type="submit"]');
        await pagePlaneur.waitForLoadState('domcontentloaded');

        await pagePlaneur.goto(VD_LIST_URL);
        await pagePlaneur.waitForLoadState('domcontentloaded');

        const rowPlaneur = pagePlaneur.locator('table tr', { hasText: benefPlaneur }).first();
        await expect(rowPlaneur).toBeVisible({ timeout: 10000 });

        const actionHref = await rowPlaneur.locator('a[href*="/vols_decouverte/action/"]').first().getAttribute('href');
        const obfId = actionHref.split('/action/')[1];
        vdActionUrlPlaneur    = `/index.php/vols_decouverte/action/${obfId}`;
        vdPreFlightUrlPlaneur = `/index.php/vols_decouverte/pre_flight/${obfId}`;
        vdDoneUrlPlaneur      = `/index.php/vols_decouverte/done/${obfId}`;

        await pagePlaneur.close();
    });

    // ================================================================
    // Niveau 1 : CA — lecture seule
    // ================================================================

    test('CA — voit la liste des VD', async ({ page }) => {
        await loginAs(page, 'abraracourcix', AVION_SECTION);
        await page.goto(VD_LIST_URL);
        await page.waitForLoadState('domcontentloaded');

        const bodyText = await page.textContent('body');
        expect(bodyText).not.toContain('Fatal error');

        // La liste est accessible
        await expect(page.locator('table.datatable')).toBeVisible();
    });

    test('CA — ne voit pas les boutons créer/modifier/supprimer/action/briefing', async ({ page }) => {
        await loginAs(page, 'abraracourcix', AVION_SECTION);
        await page.goto(VD_LIST_URL);
        await page.waitForLoadState('domcontentloaded');

        // Aucun bouton de gestion ni d'action
        await expect(page.locator('a[href*="/vols_decouverte/create"]')).toHaveCount(0);
        await expect(page.locator('a[href*="/vols_decouverte/edit/"]')).toHaveCount(0);
        await expect(page.locator('a[href*="/vols_decouverte/delete/"]')).toHaveCount(0);
        await expect(page.locator('a[href*="/vols_decouverte/action/"]')).toHaveCount(0);
        await expect(page.locator('a[href*="/briefing_passager/upload/"]')).toHaveCount(0);
    });

    test('CA — accès à /edit/ refusé en 403', async ({ page }) => {
        await loginAs(page, 'abraracourcix', AVION_SECTION);
        const response = await page.goto(vdEditUrlAvion);
        await page.waitForLoadState('domcontentloaded');
        expect(response.status()).toBe(403);
    });

    // ================================================================
    // Niveau 2 : Gestionnaire VD — droits complets
    // (Bug 1 : tresorier ne pouvait pas modifier → était bloqué en 404)
    // ================================================================

    test('Gestionnaire (tresorier) — voit tous les boutons dans la liste', async ({ page }) => {
        await loginAs(page, 'goudurix', AVION_SECTION);
        await page.goto(VD_LIST_URL);
        await page.waitForLoadState('domcontentloaded');

        await expect(page.locator('a[href*="/vols_decouverte/create"]')).toBeVisible();
        await expect(page.locator('a[href*="/vols_decouverte/edit/"]').first()).toBeVisible();
        await expect(page.locator('a[href*="/vols_decouverte/action/"]').first()).toBeVisible();
        await expect(page.locator('a[href*="/briefing_passager/upload/"]').first()).toBeVisible();
    });

    test('Bug 1 — Gestionnaire (tresorier) peut modifier un VD via /edit/', async ({ page }) => {
        await loginAs(page, 'goudurix', AVION_SECTION);

        const response = await page.goto(vdEditUrlAvion);
        await page.waitForLoadState('domcontentloaded');

        // Doit retourner 200 et afficher le formulaire (était 404 avant correction)
        expect(response.status()).toBe(200);
        await expect(page.locator('form[name="saisie"]')).toBeVisible();
    });

    // ================================================================
    // Niveau 3 : Pilote VD — droits partiels
    // (Bug 2 : pilote_vd ne voyait pas le bouton Briefing passager)
    // ================================================================

    test('Bug 2 — Pilote VD voit les boutons Action et Briefing mais pas créer/modifier/supprimer', async ({ page }) => {
        await loginAs(page, 'agecanonix', PLANEUR_SECTION);
        await page.goto(VD_LIST_URL);
        await page.waitForLoadState('domcontentloaded');

        // Pas de boutons de gestion
        await expect(page.locator('a[href*="/vols_decouverte/create"]')).toHaveCount(0);
        await expect(page.locator('a[href*="/vols_decouverte/edit/"]')).toHaveCount(0);
        await expect(page.locator('a[href*="/vols_decouverte/delete/"]')).toHaveCount(0);

        // Boutons action et briefing présents
        await expect(page.locator('a[href*="/vols_decouverte/action/"]').first()).toBeVisible();
        await expect(page.locator('a[href*="/briefing_passager/upload/"]').first()).toBeVisible();
    });

    test('Pilote VD — accède à pre_flight (contact urgence)', async ({ page }) => {
        await loginAs(page, 'agecanonix', PLANEUR_SECTION);
        await page.goto(vdPreFlightUrlPlaneur);
        await page.waitForLoadState('domcontentloaded');

        const bodyText = await page.textContent('body');
        expect(bodyText).not.toContain('Fatal error');
        expect(bodyText).not.toContain('Accès interdit');

        await expect(page.locator('input[name="urgence"]')).toBeVisible();
    });

    test('Pilote VD — accède à done (date, pilote, machine)', async ({ page }) => {
        await loginAs(page, 'agecanonix', PLANEUR_SECTION);
        await page.goto(vdDoneUrlPlaneur);
        await page.waitForLoadState('domcontentloaded');

        const bodyText = await page.textContent('body');
        expect(bodyText).not.toContain('Fatal error');
        expect(bodyText).not.toContain('Accès interdit');

        await expect(page.locator('input[name="date_vol"]')).toBeVisible();
    });

    test('Pilote VD — accès à /edit/ refusé en 403', async ({ page }) => {
        // Le pilote_vd ne doit pas pouvoir faire une édition globale
        await loginAs(page, 'agecanonix', PLANEUR_SECTION);
        const response = await page.goto(vdEditUrlAvion);
        await page.waitForLoadState('domcontentloaded');
        expect(response.status()).toBe(403);
    });

    // ================================================================
    // Bug 3 : accès interdit retourne 403 et non 404
    // ================================================================

    test('Bug 3 — accès interdit retourne 403 et non 404', async ({ page }) => {
        // asterix est un simple utilisateur sans aucun droit VD
        await loginAs(page, 'asterix', PLANEUR_SECTION);
        const response = await page.goto(vdEditUrlAvion);
        await page.waitForLoadState('domcontentloaded');
        expect(response.status()).toBe(403);
    });
});
