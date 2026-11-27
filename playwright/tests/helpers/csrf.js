/**
 * Protection CSRF : les requêtes POST envoyées directement par un test
 * (page.request.post) doivent porter le jeton de la session, comme le font
 * les formulaires et scripts de GVV (assets/javascript/csrf.js).
 *
 * Le jeton n'est volontairement PAS ajouté à toutes les requêtes du navigateur
 * de test : un formulaire de l'application qui l'oublierait doit faire échouer
 * les tests qui passent par l'interface.
 */

const COOKIE_SUFFIX = 'gvv_csrf_cookie';

/**
 * En-tête portant le jeton CSRF de la session de la page.
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<Object>}
 */
async function csrfHeaders(page) {
    const cookies = await page.context().cookies();
    const cookie = cookies.find((c) => c.name.endsWith(COOKIE_SUFFIX));
    return cookie ? { 'X-CSRF-Token': cookie.value } : {};
}

/**
 * page.request.post() avec le jeton CSRF de la session.
 * @param {import('@playwright/test').Page} page
 * @param {string} url
 * @param {Object} [options]
 */
async function csrfPost(page, url, options = {}) {
    const headers = Object.assign({}, await csrfHeaders(page), options.headers || {});
    return page.request.post(url, Object.assign({}, options, { headers }));
}

/**
 * Supprime un enregistrement par l'application, comme le bouton de suppression
 * d'une liste (POST avec le jeton CSRF) : les effets de la suppression
 * (facturation annulée, soldes rétablis, fichiers effacés) sont appliqués,
 * contrairement à un DELETE SQL direct. Sert surtout au nettoyage des tests.
 * @param {import('@playwright/test').Page} page  page connectée avec les droits nécessaires
 * @param {string} controller  contrôleur GVV (ex. 'vols_avion')
 * @param {string|number} id  identifiant de l'enregistrement
 * @param {Object} [options]  options de page.request.post (ex. maxRedirects)
 */
async function deleteViaApp(page, controller, id, options = {}) {
    return csrfPost(page, `/index.php/${controller}/delete/${encodeURIComponent(id)}`, options);
}

module.exports = { csrfHeaders, csrfPost, deleteViaApp };
