/**
 * @feature Authentification et autorisations
 *
 * Renforcement des contrôles d'accès : des pages d'administration, de
 * maintenance ou de diagnostic ne doivent être accessibles qu'aux rôles
 * prévus, et les listes paginées n'acceptent que des paramètres de
 * pagination et de tri valides.
 *
 * Utilisateurs de test (bin/create_test_users.sh) :
 *   - asterix       : simple membre (sections Planeur et Général)
 *   - abraracourcix : membre du CA (section Avion)
 *   - goudurix      : trésorier (section Avion)
 *   - panoramix     : administrateur du club
 *
 * Usage:
 *   npx playwright test tests/securite-access-control.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');
const { csrfPost } = require('./helpers/csrf');

const ASTERIX = { username: 'asterix', password: 'password', section: '1' };
const ABRARACOURCIX = { username: 'abraracourcix', password: 'password', section: '3' };
const GOUDURIX = { username: 'goudurix', password: 'password', section: '3' };
const PANORAMIX = { username: 'panoramix', password: 'password', section: '1' };

async function login(page, user) {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(user.username, user.password, user.section);
    return loginPage;
}

async function gotoRoute(page, loginPage, route) {
    await loginPage.goto(route);
    await page.waitForLoadState('domcontentloaded');
}

async function expectDenied(page, route) {
    const url = page.url();
    expect(url.includes('/auth/deny') || url.includes('/auth/login'),
        `Accès attendu refusé pour ${route} (URL: ${url})`).toBeTruthy();
}

async function expectGranted(page, route) {
    const url = page.url();
    const content = await page.content();
    const denied = url.includes('/auth/deny') || url.includes('/auth/login')
        || content.includes('Accès non autorisé') || content.includes('Accès refusé');
    expect(denied, `Accès attendu autorisé pour ${route} (URL: ${url})`).toBeFalsy();
}

test.describe('Contrôles d\'accès renforcés', () => {

    /**
     * @feature Authentification et autorisations
     * Scénario: Un visiteur non connecté ne peut ni supprimer ni déposer de fichiers partagés
     *   Étant donné un visiteur qui n'est pas connecté
     *   Quand il tente de supprimer ou de déposer un fichier dans l'espace des fichiers partagés
     *   Alors la page demandée n'existe pas
     */
    test('anonymous visitor cannot reach the shared files manager', async ({ request }) => {
        const del = await request.get('/index.php/partage/delete/photos/inexistant.jpg', { maxRedirects: 0 });
        expect(del.status()).toBe(404);
        const up = await request.post('/index.php/partage/do_upload/photos', { maxRedirects: 0 });
        expect(up.status()).toBe(404);
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Un simple membre ne peut pas gérer les rôles des utilisateurs
     *   Étant donné un membre connecté sans rôle d'administration
     *   Quand il ouvre la liste, la création ou la modification des rôles par section
     *   Alors l'accès lui est refusé
     */
    test('simple member cannot manage user roles', async ({ page }) => {
        const loginPage = await login(page, ASTERIX);
        for (const route of ['user_roles_per_section/page', 'user_roles_per_section/create', 'user_roles_per_section/edit/1']) {
            await gotoRoute(page, loginPage, route);
            await expectDenied(page, route);
        }
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Un simple membre peut toujours changer de section
     *   Étant donné un membre connecté inscrit dans deux sections
     *   Quand il choisit son autre section dans le menu
     *   Alors le changement de section est accepté
     */
    test('simple member can still switch section', async ({ page }) => {
        await login(page, ASTERIX);
        const response = await csrfPost(page, '/index.php/user_roles_per_section/set_section', {
            form: { section: '4', current_url: '' },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            maxRedirects: 0,
        });
        expect(response.status()).toBe(200);
        const json = JSON.parse(await response.text());
        expect(json.error).toBeUndefined();
        expect(json.redirect).toContain('welcome');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: L'administrateur du club gère les rôles des utilisateurs
     *   Étant donné l'administrateur du club connecté
     *   Quand il ouvre la liste des rôles par section
     *   Alors la liste s'affiche
     */
    test('club administrator can manage user roles', async ({ page }) => {
        const loginPage = await login(page, PANORAMIX);
        await gotoRoute(page, loginPage, 'user_roles_per_section/page');
        await expectGranted(page, 'user_roles_per_section/page');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Les opérations ponctuelles de maintenance sont réservées à l'administrateur
     *   Étant donné un membre du CA connecté qui n'est pas administrateur
     *   Quand il ouvre la page des opérations ponctuelles de maintenance des données
     *   Alors l'accès lui est refusé
     */
    test('non-admin cannot reach one-shot maintenance operations', async ({ page }) => {
        const loginPage = await login(page, ABRARACOURCIX);
        await gotoRoute(page, loginPage, 'oneshot');
        await expectDenied(page, 'oneshot');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Un simple membre ne voit pas le plan comptable
     *   Étant donné un membre connecté sans responsabilité au club
     *   Quand il ouvre le plan comptable, sa liste dynamique ou le formulaire de création d'un compte
     *   Alors l'accès lui est refusé
     */
    test('simple member cannot reach the chart of accounts', async ({ page }) => {
        const loginPage = await login(page, ASTERIX);
        for (const route of ['plan_comptable/page', 'plan_comptable/create', 'plan_comptable/ajax_page']) {
            await gotoRoute(page, loginPage, route);
            await expectDenied(page, route);
        }
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Un membre du CA consulte le plan comptable sans pouvoir le modifier
     *   Étant donné un membre du CA connecté qui n'est pas trésorier
     *   Quand il ouvre le plan comptable
     *   Alors la liste s'affiche
     *   Mais la création d'un compte lui est refusée
     */
    test('board member can read but not create in the chart of accounts', async ({ page }) => {
        const loginPage = await login(page, ABRARACOURCIX);
        await gotoRoute(page, loginPage, 'plan_comptable/page');
        await expectGranted(page, 'plan_comptable/page');
        await gotoRoute(page, loginPage, 'plan_comptable/create');
        await expectDenied(page, 'plan_comptable/create');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Le trésorier gère le plan comptable
     *   Étant donné le trésorier connecté
     *   Quand il ouvre le plan comptable puis le formulaire de création d'un compte
     *   Alors les deux pages s'affichent
     */
    test('treasurer can read and create in the chart of accounts', async ({ page }) => {
        const loginPage = await login(page, GOUDURIX);
        for (const route of ['plan_comptable/page', 'plan_comptable/create']) {
            await gotoRoute(page, loginPage, route);
            await expectGranted(page, route);
        }
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Une pagination invalide du plan comptable est ignorée
     *   Étant donné le trésorier connecté
     *   Quand la liste dynamique du plan comptable est demandée avec une taille de page qui n'est pas un nombre
     *   Alors la liste est renvoyée normalement
     *   Et elle ne contient pas plus de lignes que la partie numérique demandée
     */
    test('chart of accounts list ignores a non numeric page size', async ({ page }) => {
        await login(page, GOUDURIX);
        const params = new URLSearchParams({
            sEcho: '1', iDisplayStart: '0', iDisplayLength: '2 PROCEDURE ANALYSE(1,1)',
            sSearch: '', bSearchable_0: 'false', bSearchable_1: 'false',
            sSearch_0: '', sSearch_1: '',
        });
        const response = await page.request.get('/index.php/plan_comptable/ajax_page?' + params.toString());
        expect(response.status()).toBe(200);
        const json = JSON.parse(await response.text());
        expect(json.aaData.length).toBeLessThanOrEqual(2);
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Un ordre de tri invalide du carnet de vols planeur est ignoré
     *   Étant donné un membre connecté
     *   Quand la liste dynamique des vols planeur est demandée avec un ordre de tri qui n'est ni croissant ni décroissant
     *   Alors la liste est renvoyée normalement
     */
    test('glider flight list ignores an invalid sort direction', async ({ page }) => {
        await login(page, ASTERIX);
        const params = new URLSearchParams({
            sEcho: '1', iDisplayStart: '0', iDisplayLength: '5',
            iSortCol_0: '0', iSortingCols: '1', bSortable_0: 'true',
            sSortDir_0: 'desc, (select 1)', sSearch: '',
        });
        const response = await page.request.get('/index.php/vols_planeur/ajax_page?' + params.toString());
        expect(response.status()).toBe(200);
        const json = JSON.parse(await response.text());
        expect(Array.isArray(json.aaData)).toBeTruthy();
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: La page de diagnostic qui affichait la configuration du serveur n'existe plus
     *   Étant donné un membre connecté
     *   Quand il demande l'ancienne page de diagnostic des paramètres reçus par le serveur
     *   Alors la page demandée n'existe pas
     */
    test('server parameters diagnostic page is gone', async ({ page }) => {
        await login(page, ASTERIX);
        const response = await page.request.get('/index.php/FFVV/echo_params', { maxRedirects: 0 });
        expect(response.status()).toBe(404);
    });
});
