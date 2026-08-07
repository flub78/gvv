/**
 * Playwright smoke test — Stockage fichier du contenu des formulaires (Lot 2-bis)
 *
 * Vérifie le parcours complet piloté depuis l'admin (pas d'insertion directe en
 * base pour le contenu, contrairement à forms-upload-response-smoke.spec.js) :
 *  - création d'un formulaire (forms_admin/create → store) ;
 *  - ajout d'une page (page_create → page_store) : le contenu HTML est écrit à la
 *    fois en base et sur disque (uploads/formulaires/{code}/pageNN.html), enveloppé
 *    dans un document HTML5 autonome (CSS résolu par lien relatif) — voir
 *    Forms_file_storage::write_page() et doc/design_notes/formulaires_sync_fichiers_design.md ;
 *  - édition de cette page (page_edit → page_update) : le fichier est bien réécrit,
 *    pas seulement la base ;
 *  - publication (forms_admin/publish) et rendu public (forms/{slug}) : le contenu
 *    affiché provient du fichier (Forms_public::_overlay_pages_from_file()) ;
 *  - suppression : le dossier uploads/formulaires/{code}/ est intégralement
 *    supprimé (régression : .htaccess laissé derrière, rmdir() échouant en silence).
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/forms-file-storage-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const path = require('path');
const fs = require('fs');

const LOGIN_URL = '/index.php/auth/login';
const ADMIN_USER = { username: 'testadmin', password: 'password' };

const DB_CONFIG = {
    host: 'localhost',
    user: 'gvv_user',
    password: 'lfoyfgbj',
    database: 'gvv2',
};

const UPLOADS_ROOT = path.join(__dirname, '..', '..', 'uploads', 'formulaires');

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test('create, edit and publish a form through the admin UI uses file storage end to end', async ({ page }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    const ts = Date.now();
    const code = 'pw_filestorage_test_' + ts;
    const title = 'Playwright file storage test ' + ts;
    let formId;

    try {
        await login(page, ADMIN_USER);

        // --- Création ---
        await page.goto('/index.php/forms_admin/create');
        await page.waitForLoadState('networkidle');
        await page.fill('#code', code);
        await page.fill('#title', title);
        await page.fill('#public_slug', code);
        await page.click('#gvv-form-admin-form button[type="submit"]');
        await page.waitForLoadState('networkidle');

        const afterCreateBody = await page.textContent('body');
        expect(afterCreateBody).not.toContain('Fatal error');

        // public_slug is not necessarily == code: ensure_unique_slug() (forms_model)
        // lowercases and turns underscores into dashes, so re-read the actual value.
        const [formRows] = await connection.execute('SELECT id, public_slug FROM forms WHERE code = ?', [code]);
        expect(formRows.length).toBe(1);
        formId = formRows[0].id;
        const publicSlug = formRows[0].public_slug;

        // --- Édition : ajout d'une page (page_create → page_store) ---
        await page.goto('/index.php/forms_admin/page_create/' + formId);
        await page.waitForLoadState('networkidle');
        await page.fill(
            '#content_html',
            '<h1>Titre du test</h1>\n<label for="nom">Nom</label>\n<input type="text" id="nom" name="nom" required>'
        );
        await page.click('form button[type="submit"]');
        await page.waitForLoadState('networkidle');

        const afterPageStoreBody = await page.textContent('body');
        expect(afterPageStoreBody).not.toContain('Fatal error');

        const pageFile = path.join(UPLOADS_ROOT, code, 'page01.html');
        expect(fs.existsSync(pageFile)).toBe(true);
        let onDisk = fs.readFileSync(pageFile, 'utf8');
        expect(onDisk).toContain('<!DOCTYPE html>');
        expect(onDisk).toContain('<link rel="stylesheet" href="style.css">');
        expect(onDisk).toContain('name="nom"');
        expect(onDisk).toContain('Titre du test');

        // --- Édition d'une page existante (page_edit → page_update) : le fichier
        // doit être réécrit, pas seulement la ligne en base ---
        const [pageRows] = await connection.execute('SELECT id FROM form_pages WHERE form_id = ?', [formId]);
        expect(pageRows.length).toBe(1);
        const pageId = pageRows[0].id;

        await page.goto('/index.php/forms_admin/page_edit/' + formId + '/' + pageId);
        await page.waitForLoadState('networkidle');
        await page.fill(
            '#content_html',
            '<h1>Titre modifié</h1>\n<label for="nom">Nom</label>\n<input type="text" id="nom" name="nom" required>'
        );
        await page.click('form button[type="submit"]');
        await page.waitForLoadState('networkidle');

        const afterPageUpdateBody = await page.textContent('body');
        expect(afterPageUpdateBody).not.toContain('Fatal error');

        onDisk = fs.readFileSync(pageFile, 'utf8');
        expect(onDisk).toContain('Titre modifié');
        expect(onDisk).not.toContain('Titre du test');

        // --- Publication ---
        await page.goto('/index.php/forms_admin');
        await page.waitForLoadState('networkidle');
        const row = page.locator('#dt-forms tbody tr', { hasText: code });
        await expect(row).toHaveCount(1);
        await row.locator('form[action*="/publish/"] button[type="submit"]').click();
        await page.waitForLoadState('networkidle');

        const [statusRows] = await connection.execute('SELECT status FROM forms WHERE id = ?', [formId]);
        expect(statusRows[0].status).toBe('published');

        // --- Rendu public : le contenu affiché doit venir du fichier édité ---
        await page.goto('/index.php/forms/' + publicSlug);
        await page.waitForLoadState('networkidle');

        const publicBody = await page.textContent('body');
        expect(publicBody).not.toContain('Fatal error');
        expect(publicBody).toContain('Titre modifié');
        await expect(page.locator('input[name="nom"]')).toHaveCount(1);

        // --- Suppression : le dossier disque doit disparaître intégralement,
        // pas seulement la ligne en base (régression .htaccess / rmdir()) ---
        await page.goto('/index.php/forms_admin');
        await page.waitForLoadState('networkidle');
        const rowToDelete = page.locator('#dt-forms tbody tr', { hasText: code });
        page.once('dialog', (dialog) => dialog.accept());
        await rowToDelete.locator('form[action*="/delete/"] button[type="submit"]').click();
        await page.waitForLoadState('networkidle');

        const [afterDeleteRows] = await connection.execute('SELECT id FROM forms WHERE id = ?', [formId]);
        expect(afterDeleteRows.length).toBe(0);
        expect(fs.existsSync(path.join(UPLOADS_ROOT, code))).toBe(false);
        formId = null; // deleted through the UI, nothing left for the finally block to clean up
    } finally {
        if (formId) {
            await connection.execute('DELETE FROM form_pages WHERE form_id = ?', [formId]);
            await connection.execute('DELETE FROM forms WHERE id = ?', [formId]);
        }
        // Best effort only: reached solely when the test fails before the UI
        // deletion step above. Files are owned by www-data (created through the
        // web server); this process may lack permission to remove them, same
        // caveat as forms-upload-response-smoke.spec.js. Does not affect
        // DB-level test correctness — a stray directory from a failed run may
        // need manual cleanup.
        try {
            fs.rmSync(path.join(UPLOADS_ROOT, code), { recursive: true, force: true });
        } catch (e) { /* ignore */ }
        await connection.end();
    }
});
