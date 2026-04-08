/**
 * Tests des droits d'accès aux vols de découverte pour les membres CA et pilotes VD
 *
 * Trois bugs à corriger :
 *
 * Bug 1 — Membre CA/trésorier ne peut pas modifier un VD via /edit/
 *   goudurix (trésorier section Avion) voit le bouton Modifier dans la liste
 *   mais obtient 404 en cliquant dessus car ensure_modification_rights() ne
 *   prend pas en compte tresorier/bureau/ca.
 *
 * Bug 2 — Membre CA ne voit pas les boutons Action et Briefing passager
 *   abraracourcix (CA section Avion) devrait voir ces boutons dans la liste
 *   des VD mais has_modification_rights et has_pilot_rights sont tous les deux
 *   faux pour lui → aucun bouton d'action n'est affiché.
 *
 * Bug 3 — Accès interdit retourne 404 au lieu de 403
 *   asterix (user simple) tente d'accéder à /edit/ d'un VD et reçoit 404 ;
 *   devrait recevoir 403.
 *
 * Usage :
 *   npx playwright test tests/vols-decouverte-droits-ca-pilote.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const VD_LIST_URL  = '/index.php/vols_decouverte/page';
const VD_CREATE_URL = '/index.php/vols_decouverte/create';

// Section 3 = Avion (où goudurix et abraracourcix ont leurs droits VD)
const AVION_SECTION = '3';
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

test.describe('VD droits CA et pilote_vd — trois bugs', () => {
    let vdId;      // ID numérique du VD créé en section Avion
    let vdEditUrl; // URL d'édition directe

    // ----------------------------------------------------------------
    // Setup : goudurix (trésorier section Avion) crée un VD de test
    // ----------------------------------------------------------------
    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();
        await loginAs(page, 'goudurix', AVION_SECTION);

        const ts = Date.now();
        const beneficiaire = `PW CA PILOT ${ts}`;

        await page.goto(VD_CREATE_URL);
        await page.waitForLoadState('domcontentloaded');

        // goudurix est trésorier → le formulaire de création est accessible
        const form = page.locator('form[name="saisie"]');
        await expect(form).toBeVisible({ timeout: 10000 });

        await selectFirstNonEmptyOption(page, 'select[name="product"]');
        await page.fill('input[name="beneficiaire"]', beneficiaire);
        await page.fill('input[name="de_la_part"]',  'Test droits CA');
        await page.fill('input[name="beneficiaire_email"]', `pw-ca-${ts}@example.test`);

        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('domcontentloaded');

        // Récupère l'ID dans la liste
        await page.goto(VD_LIST_URL);
        await page.waitForLoadState('domcontentloaded');

        const row = page.locator('table tr', { hasText: beneficiaire }).first();
        await expect(row).toBeVisible({ timeout: 10000 });

        const editLink = row.locator('a[href*="/vols_decouverte/edit/"]').first();
        await expect(editLink).toBeVisible();

        const editHref = await editLink.getAttribute('href');
        expect(editHref).toBeTruthy();

        const m = editHref.match(/\/vols_decouverte\/edit\/(\d+)/);
        expect(m).toBeTruthy();
        vdId = m[1];
        vdEditUrl = `/index.php/vols_decouverte/edit/${vdId}`;

        await page.close();
    });

    // ----------------------------------------------------------------
    // Bug 1 : trésorier/CA peut modifier un VD via /edit/
    // ----------------------------------------------------------------
    test('Bug 1 — goudurix (trésorier) peut accéder à /edit/ sans 404', async ({ page }) => {
        await loginAs(page, 'goudurix', AVION_SECTION);

        const response = await page.goto(vdEditUrl);
        await page.waitForLoadState('domcontentloaded');

        // Doit retourner 200 et afficher le formulaire
        // ÉCHOUE actuellement : ensure_modification_rights() retourne 404 pour trésorier
        expect(response.status()).toBe(200);
        await expect(page.locator('form[name="saisie"]')).toBeVisible();
    });

    // ----------------------------------------------------------------
    // Bug 2 : membre CA voit les boutons Action et Briefing passager
    // ----------------------------------------------------------------
    test('Bug 2 — abraracourcix (CA) voit les boutons Action et Briefing dans la liste', async ({ page }) => {
        await loginAs(page, 'abraracourcix', AVION_SECTION);

        await page.goto(VD_LIST_URL);
        await page.waitForLoadState('domcontentloaded');

        const bodyText = await page.textContent('body');
        expect(bodyText).not.toContain('Fatal error');

        // ÉCHOUE actuellement : has_modification_rights et has_pilot_rights
        // sont tous les deux faux pour abraracourcix (CA sans gestion_vd/pilote_vd)
        await expect(page.locator('a[href*="/vols_decouverte/action/"]').first()).toBeVisible();
        await expect(page.locator('a[href*="/briefing_passager/upload/"]').first()).toBeVisible();
    });

    // ----------------------------------------------------------------
    // Bug 3 : accès interdit retourne 403, pas 404
    // ----------------------------------------------------------------
    test('Bug 3 — accès interdit retourne 403 et non 404', async ({ page }) => {
        // asterix est un simple utilisateur (section Planeur) sans droits VD
        await loginAs(page, 'asterix', PLANEUR_SECTION);

        const response = await page.goto(vdEditUrl);
        await page.waitForLoadState('domcontentloaded');

        // ÉCHOUE actuellement : show_404() utilisé au lieu de show_error(403)
        expect(response.status()).toBe(403);
    });
});
