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
const LoginPage = require('./helpers/LoginPage');

const ASTERIX = { username: 'asterix', password: 'password', section: '1' };
const SET_SECTION = '/index.php/user_roles_per_section/set_section';

async function login(page) {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(ASTERIX.username, ASTERIX.password, ASTERIX.section);
    await loginPage.goto('welcome');
    await page.waitForLoadState('domcontentloaded');
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
});
