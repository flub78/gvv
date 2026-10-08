/**
 * Playwright smoke test — Tampon de l'association (Lot 17 / EF19)
 *
 * Vérifie le parcours complet :
 *  - dépôt d'un tampon global depuis forms_admin/config (message + aperçu) ;
 *  - formulaire public : seul le repère est affiché (sans capter les clics), jamais l'image du tampon ;
 *  - vue admin d'une réponse : l'image du tampon remplace le repère ;
 *  - PDF imprimable : contient le tampon avec son masque de transparence (pdfimages) ;
 *  - suppression du tampon depuis l'écran de configuration.
 *
 * Le tampon global est partagé avec l'usage réel de gvv.net : il est sauvegardé
 * avant le test et restauré à l'identique après, même en cas d'échec.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/forms-stamp-smoke.spec.js --reporter=line
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

const STAMP_DIR = path.join(__dirname, '..', '..', 'uploads', 'formulaires', '.tampons');
const GLOBAL_STAMP = path.join(STAMP_DIR, 'global.png');
// 20x20 RGBA PNG, blue disc on a transparent background (colour type 6 =>
// transparency detected, no warning expected). Not fully transparent: wkhtmltopdf
// drops fully transparent images from the PDF.
const PNG_B64 = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAZklEQVQ4jWNgGOyAkZACDY0T19HFbtyw0CTZQGwGEWMwVgOJMQyXoRgGkmIYNkNRDCTHMHRDmcg1ABeAu5AS18HAjRsWmlR34Qg0cAglGxigak4hx1CCeZkUQ4kubQgZjK88pDoAAJNkLBgfBSoIAAAAAElFTkSuQmCC';

const PAGE_HTML = '<div style="position:relative"><p>Attestation de test Playwright</p>'
    + '<div data-gvv-type="stamp" style="position:absolute; right:0; top:0; width:4cm">'
    + '<img src="/assets/images/forms-widgets/stamp-placeholder.svg" alt="Tampon"> Tampon de l\'association</div></div>';

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test('association stamp: configured in admin, applied to answer view and PDF, never public', async ({ page }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    const ts = Date.now();
    const code = 'pw_stamp_test_' + ts;
    const publicSlug = 'pw-stamp-test-' + ts;
    const stampDirExisted = fs.existsSync(STAMP_DIR);
    const savedStamp = fs.existsSync(GLOBAL_STAMP) ? fs.readFileSync(GLOBAL_STAMP) : null;
    const pngPath = path.join(os.tmpdir(), 'pw_stamp_' + ts + '.png');
    const pdfPath = path.join(os.tmpdir(), 'pw_stamp_' + ts + '.pdf');
    fs.writeFileSync(pngPath, Buffer.from(PNG_B64, 'base64'));
    const dataUri = 'data:image/png;base64,' + PNG_B64;
    let formId;
    let submissionId;

    try {
        const [formResult] = await connection.execute(
            `INSERT INTO forms (code, title, status, public_slug) VALUES (?, ?, 'published', ?)`,
            [code, 'Playwright stamp test', publicSlug]
        );
        formId = formResult.insertId;
        await connection.execute(
            `INSERT INTO form_pages (form_id, page_number, title, content_html) VALUES (?, 1, 'Page 1', ?)`,
            [formId, PAGE_HTML]
        );
        const [subResult] = await connection.execute(
            `INSERT INTO form_submissions (form_id, submission_uuid, status, submitted_at)
             VALUES (?, ?, 'submitted', NOW())`,
            [formId, 'pw-stamp-' + ts]
        );
        submissionId = subResult.insertId;

        // --- Admin: upload the global stamp ---
        await login(page, ADMIN_USER);
        await page.goto('/index.php/forms_admin/config');
        await page.waitForLoadState('networkidle');

        const card = page.locator('#stamps');
        await expect(card).toBeVisible();
        const globalRow = card.locator('tbody tr').first();
        await globalRow.locator('input[name="stamp"]').setInputFiles(pngPath);
        await globalRow.locator('form[action*="stamp_upload/global"] button[type="submit"]').click();
        await page.waitForLoadState('networkidle');

        await expect(page.locator('.alert-success')).toContainText('Tampon enregistré.');
        await expect(page.locator('.alert-warning')).toHaveCount(0);
        await expect(card.locator(`img[src="${dataUri}"]`)).toHaveCount(1);

        // --- Public form: placeholder only ---
        await page.goto('/index.php/forms/' + publicSlug);
        await page.waitForLoadState('networkidle');
        const publicHtml = await page.content();
        expect(publicHtml).toContain('stamp-placeholder.svg');
        expect(publicHtml).not.toContain(dataUri);
        // The placeholder may overlap the signature widget: it must never capture clicks.
        expect(await page.locator('[data-gvv-type="stamp"]').evaluate(e => getComputedStyle(e).pointerEvents)).toBe('none');

        // --- Admin answer view: real stamp ---
        await page.goto(`/index.php/forms_admin/submission_view/${formId}/${submissionId}`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator(`[data-gvv-type="stamp"] img[src="${dataUri}"]`)).toHaveCount(1);
        expect(await page.content()).not.toContain('stamp-placeholder.svg');

        // --- PDF: the stamp (the form's only image) is embedded with its alpha mask ---
        const pdf = await page.request.get(`/index.php/forms_admin/submission_pdf/${formId}/${submissionId}`);
        expect(pdf.status()).toBe(200);
        expect(pdf.headers()['content-type']).toContain('application/pdf');
        fs.writeFileSync(pdfPath, await pdf.body());
        const images = execFileSync('pdfimages', ['-list', pdfPath]).toString();
        expect(images).toMatch(/\bimage\s+20\s+20\b/);
        expect(images).toMatch(/\bsmask\s+20\s+20\b/);

        // --- Admin: delete the stamp ---
        await page.goto('/index.php/forms_admin/config');
        await page.waitForLoadState('networkidle');
        let confirmMessage = null;
        page.once('dialog', dialog => { confirmMessage = dialog.message(); dialog.accept(); });
        await card.locator('tbody tr').first().locator('form[action*="stamp_delete/global"] button').click();
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-success')).toContainText('Tampon supprimé.');
        // The confirmation text is JSON-encoded into the handler: it must reach the dialog intact.
        expect(confirmMessage).toBe('Supprimer ce tampon ?');
        expect(fs.existsSync(GLOBAL_STAMP)).toBe(false);
    } finally {
        if (savedStamp !== null) {
            fs.writeFileSync(GLOBAL_STAMP, savedStamp);
        } else if (fs.existsSync(GLOBAL_STAMP)) {
            fs.unlinkSync(GLOBAL_STAMP);
        }
        if (!stampDirExisted && fs.existsSync(STAMP_DIR)) {
            fs.rmSync(STAMP_DIR, { recursive: true, force: true });
        }
        fs.rmSync(pngPath, { force: true });
        fs.rmSync(pdfPath, { force: true });
        if (submissionId) {
            await connection.execute('DELETE FROM form_submissions WHERE id = ?', [submissionId]);
        }
        if (formId) {
            await connection.execute('DELETE FROM form_pages WHERE form_id = ?', [formId]);
            await connection.execute('DELETE FROM forms WHERE id = ?', [formId]);
        }
        fs.rmSync(path.join(__dirname, '..', '..', 'uploads', 'formulaires', code), { recursive: true, force: true });
        await connection.end();
    }
});
