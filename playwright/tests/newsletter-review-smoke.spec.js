/**
 * Smoke test — newsletter de veille, cycle de relecture dans GVV (plan P2)
 *
 * Parcours d'un club-admin :
 *   1. liste des éditions et lien vers la page publique d'abonnement ;
 *   2. import d'un brouillon en archive zip (Markdown + image) : titre, période
 *      et version lus dans le brouillon, image affichée, aperçu PDF ;
 *   3. commentaire du DTO inséré sous un paragraphe, consignes de modification ;
 *   4. tri d'une remarque : report (statut REPORTER dans les consignes de
 *      modification), puis modification (statut APPLIQUER) ;
 *   5. « Valider et publier » : confirmation demandée, puis état validé ;
 *   6. archive des membres : dates au format local, suppression par un club-admin,
 *      bouton de suppression absent pour un simple membre ;
 *   7. suppression d'une édition depuis la liste des éditions.
 *
 * Aucun email n'est envoyé : la transmission aux relecteurs et la publication
 * (qui écrivent à des adresses réelles) ne sont pas déclenchées ici ; l'état
 * validé est positionné directement en base.
 * Le test crée ses propres données (période en 1990) et fichiers, et les
 * supprime en afterAll.
 */
const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const fs = require('fs');
const os = require('os');
const path = require('path');
const { execFileSync } = require('child_process');
const { DB_CONFIG } = require('./helpers/gvv-config');

test.describe.configure({ mode: 'serial' });

const ADMIN = { username: 'panoramix', password: 'password' };
const MEMBER = { username: 'asterix', password: 'password' };
const ARCHIVED = 'ZZTest PW veille archivée';
const TITLE = 'ZZTest PW veille – mars 1990';
const REVIEWER = 'zztest_pw_reviewer@example.invalid';
const COMMENT = 'ZZTest : le plafond à 400 ft de ces zones fait qu\'on n\'a pas de raison d\'y pénétrer';

let db;
let tmpDir;
let newsletterId = null;

async function login(page, user) {
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

function rmrf(p) {
    if (p && fs.existsSync(p)) fs.rmSync(p, { recursive: true, force: true });
}

async function cleanup() {
    const [nls] = await db.query('SELECT id, draft_path FROM newsletters WHERE title LIKE ?', ['ZZTest PW veille%']);
    await db.query('DELETE FROM newsletters WHERE title = ?', [ARCHIVED]);
    const [revs] = await db.query('SELECT id FROM newsletter_reviews WHERE reviewer_email = ?', [REVIEWER]);
    for (const n of nls) {
        if (n.draft_path) {
            // …/editions/<id>/v<N>/brouillon.md → racine du stockage
            const root = path.resolve(n.draft_path, '..', '..', '..', '..');
            rmrf(path.join(root, 'editions', String(n.id)));
            for (const r of revs) rmrf(path.join(root, 'reponses', String(r.id)));
        }
    }
    await db.query('DELETE FROM newsletter_reviews WHERE reviewer_email = ?', [REVIEWER]);
    for (const n of nls) await db.query('DELETE FROM newsletters WHERE id = ?', [n.id]);
}

test.beforeAll(async () => {
    db = await mysql.createConnection(DB_CONFIG);
    await cleanup();

    tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'gvv-nl-pw-'));
    fs.mkdirSync(path.join(tmpDir, 'lettre', 'img'), { recursive: true });
    fs.writeFileSync(path.join(tmpDir, 'lettre', 'brouillon.md'),
        '# ' + TITLE + '\n\nPériode : du 1er mars au 31 mars 1990.\n\n'
        + '<!-- BROUILLON -->\n> **BROUILLON v1 – NON VALIDÉ.**\n<!-- /BROUILLON -->\n\n'
        + '## 1. À retenir\n\nZones drones autour du terrain [À compléter : date]\n\n'
        + '![Planeur](img/planeur.svg) REX planeur\n\n'
        + '## SECTION INTERNE – à retirer\n\nNote pour le CPS\n');
    fs.writeFileSync(path.join(tmpDir, 'lettre', 'img', 'planeur.svg'),
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 30" width="32" height="15"><rect width="64" height="30" fill="#1F3A5F"/></svg>');
    execFileSync('zip', ['-qr', path.join(tmpDir, 'brouillon.zip'), 'lettre'], { cwd: tmpDir });
});

test.afterAll(async () => {
    await cleanup();
    await db.end();
    rmrf(tmpDir);
});

test('éditions et lien vers la page publique d\'abonnement', async ({ page }) => {
    await login(page, ADMIN);
    await page.goto('/index.php/newsletter_admin/editions');
    await expect(page.locator('h3')).toContainText('Veille réglementaire');

    await page.goto('/index.php/newsletter_admin');
    const publicPage = page.locator('a[href$="newsletter_public/subscribe"]');
    await expect(publicPage).toBeVisible();
    expect((await page.request.get(await publicPage.getAttribute('href'))).status()).toBe(200);
});

test('import d\'un brouillon en archive zip', async ({ page }) => {
    await login(page, ADMIN);
    await page.goto('/index.php/newsletter_admin/import');
    await page.setInputFiles('input[name="draft_file"]', path.join(tmpDir, 'brouillon.zip'));
    await page.click('button:has-text("Importer")');

    await expect(page).toHaveURL(/newsletter_admin\/edition\/\d+/);
    await expect(page.locator('.alert-dismissible').first()).toContainText('Version 1 importée');
    newsletterId = Number(page.url().match(/edition\/(\d+)/)[1]);

    await expect(page.locator('h3')).toContainText(TITLE);
    await expect(page.locator('h3 .badge')).toContainText('Brouillon');
    await expect(page.locator('p.text-muted').first()).toContainText('01/03/1990 – 31/03/1990');
    await expect(page.locator('.alert-warning.small')).toContainText('[À compléter : date]');

    const img = page.locator('.nl-draft img[alt="Planeur"]');
    await expect(img).toHaveAttribute('src', new RegExp(`newsletter_admin/image/${newsletterId}/1/planeur.svg`));
    expect((await page.request.get(await img.getAttribute('src'))).status()).toBe(200);

    const pdf = await page.request.get(`/index.php/newsletter_admin/edition_pdf/${newsletterId}`);
    expect(pdf.headers()['content-type']).toContain('application/pdf');
    expect(pdf.headers()['content-disposition']).toMatch(/brouillon_\d{8}-\d{8}_v1\.pdf/);
});

test('commentaire du DTO et consignes de modification', async ({ page }) => {
    expect(newsletterId).not.toBeNull();
    await login(page, ADMIN);
    await page.goto(`/index.php/newsletter_admin/edition/${newsletterId}`);

    const block = page.locator('.nl-block', { hasText: 'Zones drones autour du terrain' });
    await block.locator('.nl-comment-btn').click();
    const form = block.locator('form[action*="/annotate/"]');
    await expect(form.locator('input[name="label"]')).toHaveValue('Commentaire du DTO');
    await form.locator('textarea[name="content"]').fill(COMMENT);
    await form.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-success')).toContainText('Commentaire inséré');
    await expect(page.locator('.nl-annotation')).toContainText(COMMENT);

    const link = page.locator('a[href*="/directives/"]');
    await expect(link).toContainText('1 commentaire');
    const resp = await page.request.get(await link.getAttribute('href'));
    expect(resp.status()).toBe(200);
    expect(resp.headers()['content-disposition']).toMatch(/consignes_modification_\d{8}-\d{8}_v1\.md/);
    const directives = await resp.text();
    expect(directives).toContain('Zones drones autour du terrain');
    expect(directives).toContain('> **Commentaire du DTO :** ' + COMMENT);
});

test('tri d\'une remarque : statuts REPORTER puis APPLIQUER', async ({ page }) => {
    expect(newsletterId).not.toBeNull();
    await db.query(
        "INSERT INTO newsletter_reviews (newsletter_id, iteration, submitted_via, reviewer_email, reviewer_name, content, decision, created_at, updated_at) "
        + "VALUES (?, 0, 'email', ?, 'Relecteur PW', 'Ajouter le REX du mois', 'en_attente', NOW(), NOW())",
        [newsletterId, REVIEWER]);

    await login(page, ADMIN);
    await page.goto(`/index.php/newsletter_admin/edition/${newsletterId}`);
    const card = () => page.locator('#remarques ~ .card', { hasText: 'Ajouter le REX du mois' }).first();

    const directives = async () => {
        const link = page.locator('a[href*="/directives/"]');
        return (await page.request.get(await link.getAttribute('href'))).text();
    };

    // Report : la remarque reste dans l'édition et part dans les consignes avec le statut REPORTER
    await card().locator('select[name="decision"]').selectOption('reportee');
    await card().locator('button:has-text("Enregistrer")').click();
    await expect(page.locator('.alert-success')).toContainText('Reportée');
    await expect(page.locator('a[href*="/directives/"]')).toContainText('1 remarque');
    expect(await directives()).toMatch(/Statut : \*\*REPORTER\*\*\n[\s\S]*Ajouter le REX du mois/);

    // Une modification sans texte est refusée explicitement, puis acceptée avec un texte
    await card().locator('select[name="decision"]').selectOption('modifiee');
    await card().locator('button:has-text("Enregistrer")').click();
    await expect(page.locator('.alert-danger')).toContainText('texte retenu');
    await card().locator('select[name="decision"]').selectOption('modifiee');
    await card().locator('textarea[name="admin_text"]').fill('Ajouter le REX planeur du mois');
    await card().locator('button:has-text("Enregistrer")').click();
    await expect(page.locator('.alert-success')).toContainText('Modifiée');
    await expect(page.locator('a[href*="/directives/"]')).toContainText('1 remarque');
    const text = await directives();
    expect(text).toContain('Statut : **APPLIQUER**');
    expect(text).toContain('Ajouter le REX planeur du mois');
    expect(text).not.toContain('**REPORTER**\n');

    const [rows] = await db.query('SELECT newsletter_id, decision, admin_text FROM newsletter_reviews WHERE reviewer_email = ?', [REVIEWER]);
    expect(rows[0].newsletter_id).toBe(newsletterId);
    expect(rows[0].decision).toBe('modifiee');
});

test('valider et publier : confirmation, puis état validé', async ({ page }) => {
    expect(newsletterId).not.toBeNull();
    await login(page, ADMIN);
    await page.goto(`/index.php/newsletter_admin/edition/${newsletterId}`);

    // « Valider et publier » enverrait la newsletter aux abonnés réels :
    // la confirmation est refusée et rien ne change
    let question = '';
    page.once('dialog', dialog => { question = dialog.message(); dialog.dismiss(); });
    await page.click('button:has-text("Valider et publier la v1")');
    expect(question).toContain('envoyée par email à tous les abonnés');
    await expect(page.locator('h3 .badge')).toContainText('Brouillon');

    // État validé (publication non aboutie) positionné en base : nom du valideur,
    // nouvel essai de publication proposé, aperçu PDF du texte publié
    await db.query("UPDATE newsletters SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?",
        [ADMIN.username, newsletterId]);
    await page.goto(`/index.php/newsletter_admin/edition/${newsletterId}`);
    await expect(page.locator('h3 .badge')).toContainText('Validée');
    await expect(page.locator('button:has-text("Relancer la publication")')).toBeVisible();
    const [[member]] = await db.query('SELECT mprenom, mnom FROM membres WHERE mlogin = ?', [ADMIN.username]);
    await expect(page.locator('.card-body p').first()).toContainText(`Validée par ${member.mprenom} ${member.mnom}`);
    const pdf = await page.request.get(`/index.php/newsletter_admin/edition_pdf/${newsletterId}`);
    expect(pdf.headers()['content-type']).toContain('application/pdf');
});

test('archive : dates locales et suppression par un club-admin', async ({ page }) => {
    // Newsletter publiée créée en base ; son PDF est hors de l'archive (non supprimé par l'application)
    const pdfPath = path.join(tmpDir, 'archivee.pdf');
    fs.writeFileSync(pdfPath, '%PDF-1.4 zztest');
    const [res] = await db.query(
        "INSERT INTO newsletters (title, period_start, period_end, status, review_iteration, pdf_path, published_at, created_at, updated_at) "
        + "VALUES (?, '1990-04-01', '1990-04-30', 'published', 0, ?, '1990-05-02 10:00:00', NOW(), NOW())", [ARCHIVED, pdfPath]);
    const id = res.insertId;

    await login(page, MEMBER);
    await page.goto('/index.php/newsletter');
    const memberRow = page.locator('tr', { hasText: ARCHIVED });
    await expect(memberRow).toContainText('01/04/1990 – 30/04/1990');
    await expect(memberRow).toContainText('02/05/1990');
    await expect(memberRow.locator('button:has-text("Supprimer")')).toHaveCount(0);
    await page.goto('/index.php/auth/logout');

    await login(page, ADMIN);
    await page.goto('/index.php/newsletter');
    const row = page.locator('tr', { hasText: ARCHIVED });
    page.once('dialog', dialog => dialog.accept());
    await row.locator('button:has-text("Supprimer")').click();
    await expect(page).toHaveURL(/\/newsletter$/);
    await expect(page.locator('.alert-success')).toContainText(ARCHIVED);
    await expect(page.locator('tr', { hasText: ARCHIVED })).toHaveCount(0);
    const [rows] = await db.query('SELECT id FROM newsletters WHERE id = ?', [id]);
    expect(rows.length).toBe(0);
    expect(fs.existsSync(pdfPath)).toBe(true);
});

test('suppression d\'une édition depuis la liste', async ({ page }) => {
    expect(newsletterId).not.toBeNull();
    await login(page, ADMIN);
    await page.goto('/index.php/newsletter_admin/editions');
    const row = page.locator('tr', { hasText: TITLE });
    let question = '';
    page.once('dialog', dialog => { question = dialog.message(); dialog.accept(); });
    await row.locator('button:has-text("Supprimer")').click();
    expect(question).toContain(TITLE);
    await expect(page).toHaveURL(/newsletter_admin\/editions$/);
    await expect(page.locator('.alert-success')).toContainText(TITLE);
    await expect(page.locator('tr', { hasText: TITLE })).toHaveCount(0);
    const [rows] = await db.query('SELECT id FROM newsletters WHERE id = ?', [newsletterId]);
    expect(rows.length).toBe(0);
});
