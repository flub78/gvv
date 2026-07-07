/**
 * Playwright smoke test — Soumission de formulaire par téléchargement (Lot 9, étapes 3 et 4)
 *
 * Vérifie le parcours complet :
 *  - la page publique d'un formulaire avec allow_upload_response=1 affiche le bouton
 *    "Télécharger un formulaire prérempli" et la modale associée ;
 *  - un envoi (fichier + commentaire) crée une soumission visible par l'admin, avec
 *    le commentaire utilisé comme identifiant ;
 *  - pour ce type de réponse, la liste admin masque "Ouvrir", affiche une miniature
 *    cliquable à la place de "Générer PDF", et propose la rotation ;
 *  - la suppression de la réponse supprime aussi le fichier sur disque.
 *
 * Le formulaire de test est créé directement en base (mysql2), comme le fait déjà
 * global-setup.js pour ce projet : aucun test Playwright existant ne couvre le module
 * formulaires, donc pas de parcours admin de création de formulaire à réutiliser ici.
 *
 * NOTE: tout se passe dans un seul test — fullyParallel étant activé
 * (playwright.config.js), des tests séparés dans ce fichier peuvent s'exécuter dans
 * des workers différents, chacun avec son propre beforeAll/formulaire, ce qui
 * casserait le partage d'état attendu (formulaire, soumission, fichier).
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/forms-upload-response-smoke.spec.js --reporter=line
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

const PDF_FIXTURE = path.join(__dirname, '..', '..', 'application', 'tests', 'data', 'attachments', 'documents', 'small_invoice_90kb.pdf');
const COMMENT = 'Réponse scannée — test Playwright';

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test('public upload response is manageable (thumbnail, rotate, delete) in admin', async ({ page }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    const ts = Date.now();
    const publicSlug = 'pw-upload-test-' + ts;
    let formId;
    let submissionId;

    try {
        const [formResult] = await connection.execute(
            `INSERT INTO forms (code, title, status, public_slug, allow_upload_response)
             VALUES (?, ?, 'published', ?, 1)`,
            ['pw_upload_test_' + ts, 'Playwright upload test', publicSlug]
        );
        formId = formResult.insertId;

        await connection.execute(
            `INSERT INTO form_pages (form_id, page_number, title, content_html)
             VALUES (?, 1, 'Page 1', ?)`,
            [formId, '<p>Formulaire de test (Playwright, Lot 9 étape 3).</p>']
        );

        // --- Public side: open the modal and upload a scanned response ---
        await page.goto('/index.php/forms/' + publicSlug);
        await page.waitForLoadState('networkidle');

        const uploadButton = page.locator('button:has-text("Télécharger un formulaire prérempli")');
        await expect(uploadButton).toHaveCount(1);
        await uploadButton.click();

        const modal = page.locator('#uploadResponseModal');
        await expect(modal).toBeVisible();

        await modal.locator('#upload_response_file').setInputFiles(PDF_FIXTURE);
        await modal.locator('#upload_comment').fill(COMMENT);
        await modal.locator('button[type="submit"]').click();
        await page.waitForLoadState('networkidle');

        const publicBody = await page.textContent('body');
        expect(publicBody).not.toContain('Fatal error');
        expect(publicBody).not.toContain('A PHP Error was encountered');

        const [submissionRows] = await connection.execute(
            "SELECT id FROM form_submissions WHERE form_id = ? AND submission_method = 'upload'",
            [formId]
        );
        expect(submissionRows.length).toBe(1);
        submissionId = submissionRows[0].id;
        const filePath = path.join(__dirname, '..', '..', 'uploads', 'reponses', String(formId), 'reponse_' + submissionId + '.pdf');
        expect(fs.existsSync(filePath)).toBe(true);

        // --- Admin side: submission visible, with the comment as identifier ---
        await login(page, ADMIN_USER);
        await page.goto('/index.php/forms_admin/submissions/' + formId);
        await page.waitForLoadState('networkidle');

        const adminBody = await page.textContent('body');
        expect(adminBody).not.toContain('Fatal error');
        expect(adminBody).toContain(COMMENT);

        const row = page.locator('#dt-submissions tbody tr', { hasText: COMMENT });
        await expect(row).toHaveCount(1);

        // No "Ouvrir" button for an upload-type response
        await expect(row.locator('a:has-text("Ouvrir")')).toHaveCount(0);

        // Thumbnail (attachment() helper) replaces "Générer PDF", clickable to open full size
        const thumbnailLink = row.locator('a[target="_blank"]');
        await expect(thumbnailLink).toHaveCount(1);
        await expect(thumbnailLink.locator('img, i.fa-file-pdf')).toHaveCount(1);

        // Rotation buttons present and functional
        const rotateCw = row.locator('a[href*="/submission_rotate/"][href$="/cw"]');
        await expect(rotateCw).toHaveCount(1);
        page.once('dialog', (dialog) => dialog.accept());
        await rotateCw.click();
        await page.waitForLoadState('networkidle');

        const afterRotateBody = await page.textContent('body');
        expect(afterRotateBody).not.toContain('Fatal error');
        // PDF rotation needs qpdf; this dev environment may not have it installed
        // (see FileRotatorTest's own qpdf-adaptive test) — either outcome proves
        // the button/endpoint is correctly wired, so accept both explicit messages.
        const rotated = afterRotateBody.includes('pivoté');
        const toolMissing = afterRotateBody.includes('manquant sur le serveur');
        expect(rotated || toolMissing).toBe(true);

        // --- Deletion removes both the DB row and the file on disk ---
        await page.locator('.btn-delete-submission').first().click();
        await page.locator('#deleteSubmissionModal button[type="submit"]').click();
        await page.waitForLoadState('networkidle');

        const afterDeleteBody = await page.textContent('body');
        expect(afterDeleteBody).not.toContain(COMMENT);
        expect(fs.existsSync(filePath)).toBe(false);
    } finally {
        if (formId) {
            await connection.execute('DELETE FROM form_submission_files WHERE submission_id IN (SELECT id FROM form_submissions WHERE form_id = ?)', [formId]);
            await connection.execute('DELETE FROM form_submissions WHERE form_id = ?', [formId]);
            await connection.execute('DELETE FROM form_pages WHERE form_id = ?', [formId]);
            await connection.execute('DELETE FROM forms WHERE id = ?', [formId]);

            // Best effort filesystem cleanup — files are owned by www-data (uploaded
            // through the web server); may be a no-op depending on permissions, which
            // does not affect DB-level test correctness. Normally already deleted by
            // the delete-submission step above.
            const dir = path.join(__dirname, '..', '..', 'uploads', 'reponses', String(formId));
            try {
                for (const f of fs.readdirSync(dir)) {
                    fs.unlinkSync(path.join(dir, f));
                }
                fs.rmdirSync(dir);
            } catch (e) { /* ignore */ }
        }
        await connection.end();
    }
});
