/**
 * @feature Authentification et autorisations
 *
 * Protection contre la falsification de requêtes (CSRF) : une demande de
 * modification envoyée depuis un autre site, sans le jeton propre à la
 * session, est refusée ; les formulaires et appels de GVV continuent de
 * fonctionner sans action de l'utilisateur.
 *
 * L'envoi utilisé pour les vérifications est le changement de section du
 * menu, sans effet durable sur les données.
 *
 * Usage:
 *   npx playwright test tests/csrf-protection.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const LoginPage = require('./helpers/LoginPage');
const { DB_CONFIG } = require('./helpers/gvv-config');

const ASTERIX = { username: 'asterix', password: 'password', section: '1' };
const TESTADMIN = { username: 'testadmin', password: 'password', section: '1' };
const SET_SECTION = '/index.php/user_roles_per_section/set_section';

async function login(page, user = ASTERIX) {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(user.username, user.password, user.section);
    await loginPage.goto('welcome');
    await page.waitForLoadState('domcontentloaded');
}

/** Terrain de test, créé et supprimé par le test lui-même. */
const TERRAIN = { oaci: '00CSRF', nom: 'Terrain test CSRF' };

async function createTerrain(db) {
    await db.execute('DELETE FROM terrains WHERE oaci = ?', [TERRAIN.oaci]);
    await db.execute('INSERT INTO terrains (oaci, nom, comment) VALUES (?, ?, ?)',
        [TERRAIN.oaci, TERRAIN.nom, 'Créé par csrf-protection.spec.js']);
}

/** Liste des terrains filtrée sur le terrain de test (liste paginée dans la page). */
async function openTerrainInList(page) {
    await page.goto('/index.php/terrains/page');
    const box = page.getByRole('textbox', { name: /Rechercher/ });
    const button = deleteButton(page);
    // Le filtre n'agit qu'une fois la liste initialisée : on le rejoue jusque-là
    await expect(async () => {
        await box.fill('');
        await box.fill(TERRAIN.oaci);
        await expect(button).toHaveCount(1, { timeout: 1000 });
    }).toPass({ timeout: 15000 });
    return button;
}

function deleteButton(page) {
    return page.locator(`a[href$="/terrains/delete/${TERRAIN.oaci}"]`);
}

async function terrainExists(db) {
    const [rows] = await db.execute('SELECT oaci FROM terrains WHERE oaci = ?', [TERRAIN.oaci]);
    return rows.length > 0;
}

test.describe('Protection CSRF', () => {

    /**
     * @feature Authentification et autorisations
     * Scénario: Une demande envoyée sans le jeton de la session est refusée
     *   Étant donné un membre connecté
     *   Quand une demande de modification est envoyée sans le jeton de sa session, ou avec un jeton faux
     *   Alors elle est refusée
     *   Et un message explique que la page a expiré et qu'il faut la recharger
     */
    test('request without or with a wrong token is refused', async ({ page }) => {
        await login(page);
        // Requête directe, sans le script de la page : aucun jeton
        const missing = await page.request.post(SET_SECTION, { form: { section: '1' }, maxRedirects: 0 });
        expect(missing.status()).toBe(403);
        expect(await missing.text()).toContain('La page a expiré');

        const wrong = await page.request.post(SET_SECTION, {
            form: { section: '1', gvv_csrf_token: '0123456789abcdef0123456789abcdef' }, maxRedirects: 0,
        });
        expect(wrong.status()).toBe(403);
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Un appel de script refusé reçoit un message lisible par la page
     *   Étant donné un membre connecté sur une page de GVV
     *   Et dont le jeton de session a expiré depuis le chargement de la page
     *   Quand un script de la page envoie une demande de modification
     *   Alors elle est refusée
     *   Et la page reçoit une réponse qu'elle peut interpréter, avec le message d'expiration
     */
    test('script call after the token expired gets a readable refusal', async ({ page }) => {
        await login(page);
        // Expiration du jeton : son cookie disparaît, la session reste ouverte
        const context = page.context();
        const kept = (await context.cookies()).filter((c) => !c.name.endsWith('gvv_csrf_cookie'));
        await context.clearCookies();
        await context.addCookies(kept);

        const result = await page.evaluate(async (url) => {
            // Appel fetch tel que l'écrit la page, sans X-Requested-With ni Accept JSON
            const r = await fetch(url, {
                method: 'POST',
                body: new URLSearchParams({ section: '1', current_url: '' }),
            });
            return { status: r.status, type: r.headers.get('content-type'), body: await r.text() };
        }, SET_SECTION);
        expect(result.status).toBe(403);
        expect(result.type).toContain('application/json');
        const json = JSON.parse(result.body);
        expect(json.error).toBe('csrf');
        expect(json.message).toContain('La page a expiré');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Le jeton de session n'est pas prévisible
     *   Étant donné deux visiteurs qui ouvrent GVV au même moment
     *   Quand chacun reçoit son jeton de session
     *   Alors les deux jetons sont différents
     *   Et chacun a la longueur attendue
     */
    test('session tokens are distinct and well formed', async ({ browser }) => {
        const tokens = [];
        for (let i = 0; i < 2; i++) {
            const context = await browser.newContext();
            const p = await context.newPage();
            await p.goto('/index.php/auth/login');
            const cookie = (await context.cookies()).find((c) => c.name.endsWith('gvv_csrf_cookie'));
            tokens.push(cookie ? cookie.value : '');
            await context.close();
        }
        expect(tokens[0]).toMatch(/^[0-9a-f]{32}$/);
        expect(tokens[1]).toMatch(/^[0-9a-f]{32}$/);
        expect(tokens[0]).not.toBe(tokens[1]);
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Les appels d'une page de GVV portent automatiquement le jeton
     *   Étant donné un membre connecté sur une page de GVV
     *   Quand la page envoie plusieurs demandes successives par ses scripts
     *   Alors toutes sont acceptées
     */
    test('page scripts send the token automatically, several times in a row', async ({ page }) => {
        await login(page);
        const results = await page.evaluate(async (url) => {
            const out = [];
            const body = new URLSearchParams({ section: '1', current_url: '' });
            for (let i = 0; i < 2; i++) {
                const r = await fetch(url, { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                out.push(r.status);
            }
            const jq = await new Promise((resolve) => {
                window.jQuery.post(url, { section: '1', current_url: '' })
                    .done(() => resolve(200))
                    .fail((xhr) => resolve(xhr.status));
            });
            out.push(jq);
            return out;
        }, SET_SECTION);
        expect(results).toEqual([200, 200, 200]);
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Un formulaire créé et envoyé par un script de la page est accepté
     *   Étant donné un membre connecté sur une page de GVV
     *   Quand un script de la page crée un formulaire et l'envoie
     *   Alors la demande est acceptée
     */
    test('form built and submitted by a page script is accepted', async ({ page }) => {
        await login(page);
        const responsePromise = page.waitForResponse((r) => r.url().includes('set_section') && r.request().method() === 'POST');
        await page.evaluate((url) => {
            const form = document.createElement('form');
            form.method = 'post';
            form.action = url;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'section';
            input.value = '1';
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }, SET_SECTION);
        const response = await responsePromise;
        expect(response.status()).not.toBe(403);
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Le jeton n'est jamais transmis à un autre site
     *   Étant donné un membre connecté sur une page de GVV
     *   Quand la page contient un formulaire destiné à un autre site
     *   Alors ce formulaire ne reçoit pas le jeton de la session
     *   Mais un formulaire destiné à GVV le reçoit
     */
    test('token is never added to a form sent to another site', async ({ page }) => {
        await login(page);
        const fields = await page.evaluate(() => {
            const make = (action) => {
                const form = document.createElement('form');
                form.setAttribute('method', 'post');
                form.setAttribute('action', action);
                form.addEventListener('submit', (e) => e.preventDefault());
                document.body.appendChild(form);
                form.requestSubmit();
                return form.querySelector('input[name="gvv_csrf_token"]') !== null;
            };
            return { external: make('https://example.com/collect'), local: make(window.location.href) };
        });
        expect(fields.external).toBeFalsy();
        expect(fields.local).toBeTruthy();
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Les notifications du prestataire de paiement ne sont pas bloquées
     *   Étant donné le prestataire de paiement en ligne, qui n'a pas de session GVV
     *   Quand il envoie une notification de paiement
     *   Alors elle n'est pas refusée faute de jeton
     */
    test('payment provider notifications are not blocked', async ({ request }) => {
        const response = await request.post('/index.php/paiements_en_ligne/helloasso_webhook/0', {
            data: {}, maxRedirects: 0,
        });
        expect(response.status()).not.toBe(403);
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Les cookies de GVV ne sont pas joints aux demandes venant d'un autre site
     *   Étant donné un membre qui se connecte à GVV
     *   Quand GVV lui remet ses cookies de connexion
     *   Alors le navigateur ne doit les joindre qu'aux navigations vers GVV, jamais à une image ou un formulaire d'un autre site
     */
    test('GVV cookies are restricted to same-site use', async ({ page }) => {
        await login(page);
        const cookies = await page.context().cookies();
        expect(cookies.length).toBeGreaterThan(0);
        for (const cookie of cookies) {
            expect(cookie.sameSite, `cookie ${cookie.name}`).toBe('Lax');
        }
    });
});

test.describe('Protection CSRF des suppressions', () => {
    // Les tests partagent le même terrain de test : pas d'exécution en parallèle
    test.describe.configure({ mode: 'serial' });
    let db;

    test.beforeEach(async () => {
        db = await mysql.createConnection(DB_CONFIG);
        await createTerrain(db);
    });

    test.afterEach(async () => {
        await db.execute('DELETE FROM terrains WHERE oaci = ?', [TERRAIN.oaci]);
        await db.end();
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Une suppression déclenchée par un simple lien est refusée
     *   Étant donné un administrateur connecté
     *   Et un terrain enregistré
     *   Quand une adresse de suppression du terrain est ouverte directement, par exemple depuis un lien piégé
     *   Alors la suppression est refusée avec un message expliquant qu'il faut passer par le bouton de GVV
     *   Et le terrain est toujours enregistré
     */
    test('delete reached by a plain link is refused and nothing is deleted', async ({ page }) => {
        await login(page, TESTADMIN);
        const response = await page.request.get(`/index.php/terrains/delete/${TERRAIN.oaci}`, { maxRedirects: 0 });
        expect(response.status()).toBe(405);
        expect(await response.text()).toContain('Aucune donnée n');
        expect(await terrainExists(db)).toBe(true);
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Le bouton de suppression d'une liste continue de fonctionner
     *   Étant donné un administrateur connecté
     *   Et un terrain enregistré
     *   Quand il clique sur le bouton de suppression du terrain dans la liste et confirme
     *   Alors le terrain est supprimé
     */
    test('delete button of a list still deletes after confirmation', async ({ page }) => {
        await login(page, TESTADMIN);
        const button = await openTerrainInList(page);
        page.once('dialog', (dialog) => dialog.accept());
        await Promise.all([
            page.waitForResponse((r) => r.url().includes(`/terrains/delete/${TERRAIN.oaci}`)
                && r.request().method() === 'POST'),
            button.click(),
        ]);
        await expect.poll(() => terrainExists(db)).toBe(false);
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Annuler la confirmation ne supprime rien
     *   Étant donné un administrateur connecté
     *   Et un terrain enregistré
     *   Quand il clique sur le bouton de suppression du terrain dans la liste et annule la confirmation
     *   Alors le terrain est toujours enregistré
     */
    test('cancelling the confirmation deletes nothing', async ({ page }) => {
        await login(page, TESTADMIN);
        const button = await openTerrainInList(page);
        let posted = false;
        page.on('request', (r) => {
            if (r.url().includes('/terrains/delete/') && r.method() === 'POST') posted = true;
        });
        page.once('dialog', (dialog) => dialog.dismiss());
        await button.click();
        await page.waitForTimeout(500);
        expect(posted).toBe(false);
        expect(await terrainExists(db)).toBe(true);
    });
});
