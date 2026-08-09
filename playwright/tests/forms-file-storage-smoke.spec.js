/**
 * Playwright smoke test — Formulaire = répertoire autonome, archive comme
 * unique mode d'édition (Lot 2-ter), et convention de référence relative
 * pour les images/le CSS + réécriture au rendu (Lot 2-quater)
 *
 * Vérifie le parcours qui remplace l'ancienne édition par textarea HTML/CSS
 * (page_create/page_store, page_edit/page_update, désormais supprimés) :
 *  - création d'un formulaire par dépôt d'archive ZIP (forms_admin/form_import_zip) —
 *    plus de formulaire de création à base de champs HTML/CSS en texte libre ;
 *  - le contenu déposé (pageNN.html déjà enveloppé, style.css, meta.json,
 *    images/) est installé tel quel sur le disque
 *    (Forms_file_storage::replace_all_from_dir()) et reflété dans form_pages
 *    (mirroir best-effort) ;
 *  - le fichier stocké référence son image propre par un chemin relatif
 *    (`images/{fichier}`) et une ressource partagée par `.commun/style.css` /
 *    `.commun/images/{fichier}` — jamais une route GVV en dur — et GVV les
 *    réécrit au rendu (Forms_renderer::rewrite_local_image_urls()/
 *    rewrite_shared_css_import()) ;
 *  - modification du contenu par dépôt d'une nouvelle archive sur le formulaire
 *    existant (forms_admin/form_restore) : chemin normal d'itération, pas une
 *    opération de secours — le code, le statut et le lien public ne changent pas ;
 *  - rendu public (forms/{slug}) : le contenu affiché vient du fichier, l'image
 *    propre au formulaire, l'image partagée et le CSS partagé (servis par
 *    forms_public/image, forms_public/shared_image, forms_public/shared_css)
 *    s'affichent tous correctement ;
 *  - suppression : le dossier uploads/formulaires/{code}/ est intégralement
 *    supprimé.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/forms-file-storage-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const path = require('path');
const fs = require('fs');
const os = require('os');
const { execFileSync } = require('child_process');

const LOGIN_URL = '/index.php/auth/login';
const ADMIN_USER = { username: 'testadmin', password: 'password' };

const DB_CONFIG = {
    host: 'localhost',
    user: 'gvv_user',
    password: 'lfoyfgbj',
    database: 'gvv2',
};

const UPLOADS_ROOT = path.join(__dirname, '..', '..', 'uploads', 'formulaires');
const SHARED_CSS_PATH = path.join(UPLOADS_ROOT, '.commun', 'style.css');
const SHARED_IMAGES_DIR = path.join(UPLOADS_ROOT, '.commun', 'images');

// A 1x1 transparent PNG — enough for getimagesize() to recognize a real image.
const PNG_1X1 = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
    'base64'
);

function wrappedPage(bodyContent) {
    return '<!DOCTYPE html>\n<html lang="fr">\n<head>\n<meta charset="utf-8">\n<title>page</title>\n'
        + '<link rel="stylesheet" href="style.css">\n</head>\n<body class="forms-public-root">\n'
        + bodyContent + '\n</body>\n</html>\n';
}

/** Builds a form archive (meta.json + pageNN.html + style.css + images/) as a ZIP file, same layout as the real storage directory. */
function buildArchive(zipPath, { meta, pages, css, images = {} }) {
    const srcDir = fs.mkdtempSync(path.join(os.tmpdir(), 'gvv_forms_archive_'));
    try {
        fs.writeFileSync(path.join(srcDir, 'meta.json'), JSON.stringify(meta, null, 2));
        fs.writeFileSync(path.join(srcDir, 'style.css'), css);
        for (const [number, bodyContent] of Object.entries(pages)) {
            fs.writeFileSync(path.join(srcDir, `page${String(number).padStart(2, '0')}.html`), wrappedPage(bodyContent));
        }
        if (Object.keys(images).length > 0) {
            fs.mkdirSync(path.join(srcDir, 'images'));
            for (const [filename, content] of Object.entries(images)) {
                fs.writeFileSync(path.join(srcDir, 'images', filename), content);
            }
        }
        execFileSync('zip', ['-r', zipPath, '.'], { cwd: srcDir });
    } finally {
        fs.rmSync(srcDir, { recursive: true, force: true });
    }
}

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test('create a form by archive deposit, replace its content via form_restore, and render publicly with shared CSS', async ({ page, request }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    const ts = Date.now();
    const code = 'pw_filestorage_test_' + ts;
    const title = 'Playwright file storage test ' + ts;
    let formId;

    // .commun/style.css et .commun/images/ sont des ressources partagées par tous
    // les formulaires de ce serveur — sauvegarde/restauration comme tout autre état
    // partagé du serveur de test (voir la note mémoire gvv.net).
    const hadSharedCss = fs.existsSync(SHARED_CSS_PATH);
    const originalSharedCss = hadSharedCss ? fs.readFileSync(SHARED_CSS_PATH, 'utf8') : null;
    const sharedCssMarker = '/* pw-shared-css-' + ts + ' */ .club-header { color: navy; }';
    const sharedImageName = 'pw-shared-logo-' + ts + '.png';
    const sharedImagePath = path.join(SHARED_IMAGES_DIR, sharedImageName);

    const createZipPath = path.join(os.tmpdir(), `gvv_pw_create_${ts}.zip`);
    const restoreZipPath = path.join(os.tmpdir(), `gvv_pw_restore_${ts}.zip`);

    try {
        fs.mkdirSync(path.dirname(SHARED_CSS_PATH), { recursive: true });
        fs.writeFileSync(SHARED_CSS_PATH, sharedCssMarker);
        fs.mkdirSync(SHARED_IMAGES_DIR, { recursive: true });
        fs.writeFileSync(sharedImagePath, PNG_1X1);

        await login(page, ADMIN_USER);

        // --- Création par dépôt d'archive (form_import_zip) ---
        // Le style.css et la page référencent les ressources par un chemin relatif
        // (`.commun/style.css`, `images/{fichier}`, `.commun/images/{fichier}`) —
        // jamais une route GVV en dur — réécrit par Forms_renderer au rendu.
        buildArchive(createZipPath, {
            meta: {
                title: title,
                description: 'Créé par archive (Playwright)',
                css_scope: '',
                required_params: 'none',
                pages: [{ page_number: 1, title: 'Pilote' }],
            },
            pages: {
                1: '<h1>Titre du test</h1>\n<label for="nom">Nom</label>\n<input type="text" id="nom" name="nom" required>'
                    + '\n<img src="images/logo-formulaire.png" alt="Logo formulaire">'
                    + '\n<img src=".commun/images/' + sharedImageName + '" alt="Logo club">',
            },
            css: '@import url(".commun/style.css");\n.forms-public-root h1 { color: #0d6efd; }',
            images: { 'logo-formulaire.png': PNG_1X1 },
        });

        await page.goto('/index.php/forms_admin');
        await page.waitForLoadState('networkidle');
        await page.click('button[data-bs-target="#importZipModal"]');
        await page.setInputFiles('#importZipModal input[name="import_zip"]', createZipPath);
        await page.fill('#importZipModal input[name="import_code"]', code);
        await page.click('#importZipModal button[type="submit"]');
        await page.waitForLoadState('networkidle');

        const afterCreateBody = await page.textContent('body');
        expect(afterCreateBody).not.toContain('Fatal error');

        const [formRows] = await connection.execute('SELECT id, public_slug FROM forms WHERE code = ?', [code]);
        expect(formRows.length).toBe(1);
        formId = formRows[0].id;
        const publicSlug = formRows[0].public_slug;

        const pageFile = path.join(UPLOADS_ROOT, code, 'page01.html');
        expect(fs.existsSync(pageFile)).toBe(true);
        let onDisk = fs.readFileSync(pageFile, 'utf8');
        expect(onDisk).toContain('<!DOCTYPE html>');
        expect(onDisk).toContain('name="nom"');
        expect(onDisk).toContain('Titre du test');
        // Le fichier stocké garde les chemins relatifs tels quels — la réécriture
        // n'a lieu qu'au rendu, jamais sur le fichier lui-même (aperçu file:// intact).
        expect(onDisk).toContain('src="images/logo-formulaire.png"');
        expect(onDisk).toContain('src=".commun/images/' + sharedImageName + '"');
        expect(fs.existsSync(path.join(UPLOADS_ROOT, code, 'images', 'logo-formulaire.png'))).toBe(true);

        const metaOnDisk = JSON.parse(fs.readFileSync(path.join(UPLOADS_ROOT, code, 'meta.json'), 'utf8'));
        expect(metaOnDisk.pages).toEqual([{ page_number: 1, title: 'Pilote' }]);

        const [pageRowsAfterCreate] = await connection.execute('SELECT page_number FROM form_pages WHERE form_id = ?', [formId]);
        expect(pageRowsAfterCreate.map((r) => r.page_number)).toEqual([1]);

        // --- Publication ---
        await page.goto('/index.php/forms_admin');
        await page.waitForLoadState('networkidle');
        const row = page.locator('#dt-forms tbody tr', { hasText: code });
        await expect(row).toHaveCount(1);
        await row.locator('form[action*="/publish/"] button[type="submit"]').click();
        await page.waitForLoadState('networkidle');

        // --- Rendu public : contenu du fichier, image propre, image partagée et
        // CSS partagé tous réécrits vers leur route de service et chargés ---
        await page.goto('/index.php/forms/' + publicSlug);
        await page.waitForLoadState('networkidle');

        const publicBody = await page.textContent('body');
        expect(publicBody).not.toContain('Fatal error');
        expect(publicBody).toContain('Titre du test');
        await expect(page.locator('input[name="nom"]')).toHaveCount(1);

        const publicHtml = await page.content();
        expect(publicHtml).toContain('forms_public/shared_css');
        expect(publicHtml).toContain('forms_public/image/' + code + '/logo-formulaire.png');
        expect(publicHtml).toContain('forms_public/shared_image/' + sharedImageName);
        // Jamais la route GVV en dur dans le fichier stocké lui-même.
        expect(onDisk).not.toContain('forms_public/');

        const sharedCssResponse = await request.get('/index.php/forms_public/shared_css');
        expect(sharedCssResponse.ok()).toBe(true);
        expect(await sharedCssResponse.text()).toContain(sharedCssMarker);

        const formImageResponse = await request.get('/index.php/forms_public/image/' + code + '/logo-formulaire.png');
        expect(formImageResponse.ok()).toBe(true);

        const sharedImageResponse = await request.get('/index.php/forms_public/shared_image/' + sharedImageName);
        expect(sharedImageResponse.ok()).toBe(true);

        // --- Modification du contenu par dépôt d'archive (form_restore) : chemin
        // normal, pas un secours — code/statut/lien public ne changent pas ---
        buildArchive(restoreZipPath, {
            meta: {
                title: title,
                description: 'Contenu remplacé (Playwright)',
                css_scope: '',
                required_params: 'none',
                pages: [
                    { page_number: 1, title: 'Pilote' },
                    { page_number: 2, title: 'Planeur' },
                ],
            },
            pages: {
                1: '<h1>Titre modifié</h1>\n<label for="nom">Nom</label>\n<input type="text" id="nom" name="nom" required>',
                2: '<h1>Planeur</h1>\n<label for="immat">Immatriculation</label>\n<input type="text" id="immat" name="immat">',
            },
            css: '@import url(".commun/style.css");\n.forms-public-root h1 { color: #d63384; }',
        });

        await page.goto('/index.php/forms_admin/edit/' + formId);
        await page.waitForLoadState('networkidle');
        await page.setInputFiles('input[name="restore_zip"]', restoreZipPath);
        page.once('dialog', (dialog) => dialog.accept());
        await page.click('form[action*="/form_restore/"] button[type="submit"]');
        await page.waitForLoadState('networkidle');

        const afterRestoreBody = await page.textContent('body');
        expect(afterRestoreBody).not.toContain('Fatal error');

        onDisk = fs.readFileSync(pageFile, 'utf8');
        expect(onDisk).toContain('Titre modifié');
        expect(onDisk).not.toContain('Titre du test');
        expect(fs.existsSync(path.join(UPLOADS_ROOT, code, 'page02.html'))).toBe(true);

        const [pageRowsAfterRestore] = await connection.execute('SELECT page_number FROM form_pages WHERE form_id = ? ORDER BY page_number', [formId]);
        expect(pageRowsAfterRestore.map((r) => r.page_number)).toEqual([1, 2]);

        const [formRowsAfterRestore] = await connection.execute('SELECT code, status, public_slug FROM forms WHERE id = ?', [formId]);
        expect(formRowsAfterRestore[0].code).toBe(code);
        expect(formRowsAfterRestore[0].status).toBe('published');
        expect(formRowsAfterRestore[0].public_slug).toBe(publicSlug);

        // --- Rendu public après remplacement : nouveau contenu, deuxième page présente ---
        await page.goto('/index.php/forms/' + publicSlug);
        await page.waitForLoadState('networkidle');
        expect(await page.textContent('body')).toContain('Titre modifié');

        // --- Suppression : le dossier disque doit disparaître intégralement ---
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

        try {
            if (hadSharedCss) {
                fs.writeFileSync(SHARED_CSS_PATH, originalSharedCss);
            } else {
                fs.rmSync(SHARED_CSS_PATH, { force: true });
            }
        } catch (e) { /* ignore */ }

        try {
            fs.rmSync(sharedImagePath, { force: true });
        } catch (e) { /* ignore */ }

        for (const zipPath of [createZipPath, restoreZipPath]) {
            try { fs.rmSync(zipPath, { force: true }); } catch (e) { /* ignore */ }
        }

        await connection.end();
    }
});
