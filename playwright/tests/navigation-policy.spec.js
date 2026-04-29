/**
 * Tests de la politique de navigation GVV
 *
 * Principe : après toute opération réussie (création, modification, suppression),
 * l'utilisateur retourne à la page d'origine, quelle qu'elle soit.
 *
 * Les tests échouent avant le correctif car :
 *   - welcome/index() ne pousse pas l'URL dans la pile return_url_stack
 *   - compta::formValidation() redirige hardcodé vers compta/journal_compte
 *     au lieu d'utiliser pop_return_url()
 *
 * Pour exécuter :
 *   cd playwright && npx playwright test tests/navigation-policy.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const { USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON } = require('./helpers/gvv-config');

// All tests in this file use legacy test users — skip when new auth is active.
test.beforeEach(async ({}) => {
    test.skip(USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON);
});

const LOGIN_URL       = '/index.php/auth/login';
const WELCOME_URL     = '/index.php/welcome';
const GRAND_JOURNAL   = '/index.php/compta/page';
const VOLS_AVION_PAGE = '/index.php/vols_avion/page';

async function login(page, username, password = 'password') {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function checkNoPhpErrors(page) {
    const body = await page.textContent('body');
    expect(body).not.toContain('Fatal error');
    expect(body).not.toContain('A PHP Error was encountered');
    expect(body).not.toContain('An uncaught Exception was encountered');
}

/**
 * Sélectionne la Nième option non-vide d'un <select> (visible ou caché).
 * Fonctionne avec les selects enrichis (Choices.js / Select2).
 * skip=0 → première option non-vide, skip=1 → deuxième, etc.
 */
async function selectNthOption(page, name, skip = 0) {
    return page.evaluate(([fieldName, skipCount]) => {
        const sel = document.querySelector(`select[name="${fieldName}"]`);
        if (!sel) return null;
        let found = 0;
        for (const opt of sel.options) {
            if (opt.value && opt.value.trim() !== '') {
                if (found === skipCount) {
                    sel.value = opt.value;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                    return opt.value;
                }
                found++;
            }
        }
        return null;
    }, [name, skip]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Scénario 1 [FAILS avant correctif] :
// Depuis le dashboard → créer un vol avion → retour au dashboard
// ─────────────────────────────────────────────────────────────────────────────
test('Vol avion créé depuis le dashboard : retour au dashboard', async ({ page }) => {
    await login(page, 'testplanchiste');

    // Visiter le dashboard — doit pousser l'URL dans la pile (après correctif)
    await page.goto(WELCOME_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);
    expect(page.url()).toContain('welcome');

    // Naviguer vers le formulaire de création (simulation du clic depuis le dashboard)
    await page.goto('/index.php/vols_avion/create');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    // Remplir les champs requis
    await page.fill('input[name="vadate"]', '19/04/2026');
    await selectNthOption(page, 'vamacid', 0);
    await selectNthOption(page, 'vapilid', 0);
    await page.fill('input[name="vahdeb"]', '10:00');
    await page.fill('input[name="vahfin"]', '11:00');

    // Fixer horamètre début < fin pour que la durée soit calculée
    await page.evaluate(() => {
        const cdeb  = document.querySelector('input[name="vacdeb"]');
        const cfin  = document.querySelector('input[name="vacfin"]');
        const duree = document.querySelector('input[name="vaduree"]');
        if (cdeb)  { cdeb.value  = '100'; cdeb.dispatchEvent(new Event('change', { bubbles: true })); }
        if (cfin)  { cfin.value  = '101'; cfin.dispatchEvent(new Event('change', { bubbles: true })); }
        if (duree) { duree.value = '1';   duree.dispatchEvent(new Event('change', { bubbles: true })); }
    });

    // Soumettre avec "Créer" (id="validate")
    await page.click('#validate');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const afterUrl = page.url();
    console.log(`URL après création vol : ${afterUrl}`);

    // ASSERTION : retour au dashboard
    expect(afterUrl,
        'Après création d\'un vol depuis le dashboard, on doit retourner au dashboard'
    ).toContain('welcome');
});

// ─────────────────────────────────────────────────────────────────────────────
// Scénario 2 [FAILS avant correctif] :
// Depuis le dashboard → créer une écriture comptable → retour au dashboard
// ─────────────────────────────────────────────────────────────────────────────
test('Écriture comptable créée depuis le dashboard : retour au dashboard', async ({ page }) => {
    await login(page, 'testtresorier');

    // Visiter le dashboard
    await page.goto(WELCOME_URL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);
    expect(page.url()).toContain('welcome');

    // Naviguer vers la saisie d'écriture générale
    await page.goto('/index.php/compta/create');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    // Remplir le formulaire (champ date = date_op, libellé = description)
    await page.fill('input[name="date_op"]', '19/04/2026');
    await selectNthOption(page, 'compte1', 0);
    await selectNthOption(page, 'compte2', 1);
    await page.fill('input[name="montant"]', '1.00');
    await page.fill('input[name="description"]', `NAV_TEST_${Date.now()}`);

    // Soumettre avec "Créer"
    await page.click('#validate');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const afterUrl = page.url();
    console.log(`URL après création écriture depuis dashboard : ${afterUrl}`);

    // ASSERTION : retour au dashboard
    expect(afterUrl,
        'Après création d\'une écriture depuis le dashboard, on doit retourner au dashboard'
    ).toContain('welcome');
});

// ─────────────────────────────────────────────────────────────────────────────
// Scénario 3 [FAILS avant correctif] :
// Depuis le grand journal → créer une écriture → retour au grand journal
// ─────────────────────────────────────────────────────────────────────────────
test('Écriture créée depuis le grand journal : retour au grand journal', async ({ page }) => {
    await login(page, 'testtresorier');

    // Visiter le grand journal (push_return_url déjà actif)
    await page.goto(GRAND_JOURNAL);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);
    expect(page.url()).toContain('compta/page');

    // Aller créer une écriture
    await page.goto('/index.php/compta/create');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    await page.fill('input[name="date_op"]', '19/04/2026');
    await selectNthOption(page, 'compte1', 0);
    await selectNthOption(page, 'compte2', 1);
    await page.fill('input[name="montant"]', '1.00');
    await page.fill('input[name="description"]', `NAV_TEST_${Date.now()}`);

    await page.click('#validate');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const afterUrl = page.url();
    console.log(`URL après création écriture depuis grand journal : ${afterUrl}`);

    // ASSERTION : retour au grand journal
    expect(afterUrl,
        'Après création d\'une écriture depuis le grand journal, on doit retourner au grand journal'
    ).toContain('compta/page');
});

// ─────────────────────────────────────────────────────────────────────────────
// Scénario 4 [doit PASSER avant et après correctif] :
// Depuis la liste de vols → modifier → retour à la même page de liste
// ─────────────────────────────────────────────────────────────────────────────
test('Modification depuis la liste de vols : retour à la même page', async ({ page }) => {
    await login(page, 'testplanchiste');

    await page.goto(VOLS_AVION_PAGE);
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);
    expect(page.url()).toContain('vols_avion/page');

    // Vérifier qu'il y a des vols à modifier
    const editLinks = page.locator('a[href*="vols_avion/edit"]');
    const count = await editLinks.count();
    if (count === 0) {
        test.skip('Aucun vol avion en base de données');
        return;
    }

    // Cliquer sur le premier lien d'édition
    await editLinks.first().click();
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);
    expect(page.url()).toContain('vols_avion/edit');

    // Valider sans modification
    await page.click('#validate');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const afterUrl = page.url();
    console.log(`URL après modification depuis liste : ${afterUrl}`);

    expect(afterUrl,
        'Après modification depuis la liste, on doit retourner à la liste'
    ).toContain('vols_avion/page');
});

// ─────────────────────────────────────────────────────────────────────────────
// Scénario 5 [FAILS avant correctif] :
// Erreur de validation puis succès → retour à la page d'origine
// ─────────────────────────────────────────────────────────────────────────────
test('Erreur de validation puis succès : retour à la page d\'origine', async ({ page }) => {
    await login(page, 'testtresorier');

    // Partir du grand journal
    await page.goto(GRAND_JOURNAL);
    await page.waitForLoadState('networkidle');

    await page.goto('/index.php/compta/create');
    await page.waitForLoadState('networkidle');

    // Sélectionner les comptes mais laisser le montant vide → erreur attendue
    await selectNthOption(page, 'compte1', 0);
    await selectNthOption(page, 'compte2', 1);
    await page.fill('input[name="description"]', `NAV_TEST_${Date.now()}`);
    // montant vide intentionnellement

    await page.click('#validate');
    await page.waitForLoadState('networkidle');

    // Doit rester sur le formulaire compta (pas de redirect)
    expect(page.url()).toContain('compta');
    const bodyAfterError = await page.textContent('body');
    expect(bodyAfterError).toMatch(/requis|required|obligatoire|champ|field/i);

    // Corriger et soumettre
    await page.fill('input[name="date_op"]', '19/04/2026');
    await page.fill('input[name="montant"]', '1.00');

    await page.click('#validate');
    await page.waitForLoadState('networkidle');
    await checkNoPhpErrors(page);

    const afterUrl = page.url();
    console.log(`URL après succès suite à erreur : ${afterUrl}`);

    // ASSERTION : retour au grand journal (page d'origine)
    expect(afterUrl,
        'Après succès suite à une erreur de validation, retour à la page d\'origine'
    ).toContain('compta/page');
});
