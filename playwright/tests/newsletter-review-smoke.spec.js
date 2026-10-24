/**
 * Smoke test — newsletter de veille, cycle de la version 1 (plan P2)
 *
 * Parcours d'un club-admin :
 *   1. consignes de l'agent : enregistrement d'une version ;
 *   2. kit pour l'agent de bureau ;
 *   3. import du brouillon d'une période (brouillon, rapport, inventaire) ;
 *   4. page de l'édition : version, mentions à compléter, rapport d'activité ;
 *   5. tri d'une suggestion (règle « reporter = article seulement », report, reprise) ;
 *   6. validation directe du brouillon.
 *
 * Aucun email n'est envoyé : la transmission aux relecteurs et la publication
 * (qui écrivent à des adresses réelles) ne sont pas déclenchées ici.
 * Le test crée ses propres données et fichiers, et les supprime en afterAll ;
 * la version active des consignes existante est réactivée.
 */
const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const fs = require('fs');
const os = require('os');
const path = require('path');
const { DB_CONFIG } = require('./helpers/gvv-config');

test.describe.configure({ mode: 'serial' });

const ADMIN = { username: 'panoramix', password: 'password' };
const TITLE = 'ZZTest PW veille ' + Date.now();
const MARK = 'ZZTest PW consignes';
const REVIEWER = 'zztest_pw_reviewer@example.invalid';

let db;
let activeContextBefore = null;
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
    const [nls] = await db.query('SELECT id FROM newsletters WHERE title LIKE ?', ['ZZTest PW veille%']);
    const [runs] = await db.query("SELECT run_id, package_path FROM newsletter_runs WHERE model = 'zztest-pw'");
    const [revs] = await db.query('SELECT id FROM newsletter_reviews WHERE reviewer_email = ?', [REVIEWER]);
    for (const r of runs) {
        if (r.package_path) {
            const base = path.resolve(r.package_path, '..', '..');
            for (const n of nls) rmrf(path.join(base, 'editions', String(n.id)));
            for (const v of revs) rmrf(path.join(base, 'reponses', String(v.id)));
            rmrf(r.package_path);
        }
    }
    await db.query('DELETE FROM newsletter_reviews WHERE reviewer_email = ?', [REVIEWER]);
    await db.query("DELETE FROM newsletter_runs WHERE model = 'zztest-pw'");
    for (const n of nls) await db.query('DELETE FROM newsletters WHERE id = ?', [n.id]);
    await db.query('DELETE FROM newsletter_contexts WHERE content LIKE ?', [MARK + '%']);
    await db.query('UPDATE newsletter_contexts SET is_active = 0');
    if (activeContextBefore) {
        await db.query('UPDATE newsletter_contexts SET is_active = 1 WHERE id = ?', [activeContextBefore]);
    }
}

test.beforeAll(async () => {
    db = await mysql.createConnection(DB_CONFIG);
    const [rows] = await db.query('SELECT id FROM newsletter_contexts WHERE is_active = 1');
    activeContextBefore = rows.length ? rows[0].id : null;

    tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'gvv-nl-pw-'));
    fs.writeFileSync(path.join(tmpDir, 'brouillon.md'),
        '# Veille test\n\n> **BROUILLON v1 – NON VALIDÉ.**\n\n## 1. À retenir\n\nTexte [À compléter : date]\n\n'
        + '## SECTION INTERNE – à retirer\n\nNote pour le CPS\n');
    fs.writeFileSync(path.join(tmpDir, 'rapport.md'), '# Journal\n\n| source | résultat |\n|---|---|\n| zztest-src | OK |\n');
    fs.writeFileSync(path.join(tmpDir, 'inventaire.csv'), 'source_id;doc_id\nzztest-src;DOC-1\n');
});

test.afterAll(async () => {
    await cleanup();
    await db.end();
    rmrf(tmpDir);
});

test('consignes : enregistrement d\'une nouvelle version', async ({ page }) => {
    await login(page, ADMIN);
    await page.goto('/index.php/newsletter_admin/editions');
    await expect(page.locator('h3')).toContainText('Éditions de la veille');

    await page.goto('/index.php/newsletter_admin/context');
    await page.fill('textarea[name="content"]', MARK + '\n\n## Registre des sources\n');
    await page.fill('input[name="comment"]', 'ZZTest');
    await page.click('button:has-text("Enregistrer une nouvelle version")');
    await expect(page.locator('.alert-success')).toContainText('des consignes enregistrée et active');
    await expect(page.locator('textarea[name="content"]')).toHaveValue(new RegExp('^' + MARK));

    await page.goto('/index.php/newsletter_admin/kit');
    await expect(page.locator('a[href*="kit_file/consignes"]')).toBeVisible();
});

test('import du brouillon d\'une période', async ({ page }) => {
    await login(page, ADMIN);
    await page.goto('/index.php/newsletter_admin/import');
    await page.fill('input[name="title"]', TITLE);
    await page.fill('input[name="period_start"]', '2026-09-21');
    await page.fill('input[name="period_end"]', '2026-10-21');
    await page.setInputFiles('input[name="draft_file"]', path.join(tmpDir, 'brouillon.md'));
    await page.setInputFiles('input[name="report_file"]', path.join(tmpDir, 'rapport.md'));
    await page.setInputFiles('input[name="inventory_file"]', path.join(tmpDir, 'inventaire.csv'));
    await page.fill('input[name="model"]', 'zztest-pw');
    await page.click('button:has-text("Importer")');

    await expect(page).toHaveURL(/newsletter_admin\/edition\/\d+/);
    await expect(page.locator('.alert-dismissible').first()).toContainText('importé');
    newsletterId = Number(page.url().match(/edition\/(\d+)/)[1]);

    await expect(page.locator('h3')).toContainText(TITLE);
    await expect(page.locator('h3 .badge')).toContainText('Brouillon');
    await expect(page.locator('.alert-warning.small')).toContainText('[À compléter : date]');

    await page.click('a:has-text("Rapport d\'activité")');
    await expect(page.locator('td:has-text("zztest-src")')).toBeVisible();
    for (const what of ['brouillon', 'rapport', 'inventaire']) {
        const link = page.locator(`a[href*="/run_file/"][href$="/${what}"]`);
        const resp = await page.request.get(await link.getAttribute('href'));
        expect(resp.status(), `téléchargement ${what}`).toBe(200);
    }
    const pdf = await page.request.get(`/index.php/newsletter_admin/edition_pdf/${newsletterId}`);
    expect(pdf.headers()['content-type']).toContain('application/pdf');

    await page.goto(`/index.php/newsletter_admin/version/${newsletterId}/1`);
    await expect(page.locator('h3')).toContainText('v1');

    await page.goto('/index.php/newsletter_admin/kit');
    await expect(page.locator('a[href*="kit_file/inventaire"]')).toBeVisible();
});

test('tri d\'une suggestion : report d\'un article puis reprise', async ({ page }) => {
    expect(newsletterId).not.toBeNull();
    await db.query(
        "INSERT INTO newsletter_reviews (newsletter_id, iteration, submitted_via, reviewer_email, reviewer_name, kind, content, decision, created_at, updated_at) "
        + "VALUES (?, 0, 'email', ?, 'Relecteur PW', 'remarque', 'Ajouter le REX du mois', 'en_attente', NOW(), NOW())",
        [newsletterId, REVIEWER]);

    await login(page, ADMIN);
    await page.goto(`/index.php/newsletter_admin/edition/${newsletterId}`);
    const card = page.locator('.card', { hasText: 'Ajouter le REX du mois' }).first();

    // Une remarque ne peut pas être reportée : refus explicite
    await card.locator('select[name="decision"]').selectOption('reportee');
    await card.locator('button:has-text("Enregistrer")').click();
    await expect(page.locator('.alert-danger')).toContainText('article');

    // Article reporté : il quitte l'édition et apparaît dans les suggestions reportées
    const card2 = page.locator('.card', { hasText: 'Ajouter le REX du mois' }).first();
    await card2.locator('select[name="kind"]').selectOption('article');
    await card2.locator('select[name="decision"]').selectOption('reportee');
    await card2.locator('button:has-text("Enregistrer")').click();
    await expect(page.locator('.alert-success')).toContainText('Reportée');
    await expect(page.locator('h6:has-text("reportées")')).toBeVisible();

    // Reprise dans l'édition, en attente de décision
    await page.click('button:has-text("Reprendre dans cette édition")');
    await expect(page.locator('.alert-success')).toContainText('reprise');
    const [rows] = await db.query('SELECT newsletter_id, decision FROM newsletter_reviews WHERE reviewer_email = ?', [REVIEWER]);
    expect(rows[0].newsletter_id).toBe(newsletterId);
    expect(rows[0].decision).toBe('en_attente');
});

test('validation directe du brouillon', async ({ page }) => {
    expect(newsletterId).not.toBeNull();
    await login(page, ADMIN);
    await page.goto(`/index.php/newsletter_admin/edition/${newsletterId}`);
    page.once('dialog', dialog => dialog.accept());
    await page.click('button:has-text("Valider la v1")');
    await expect(page.locator('.alert-success')).toContainText('Version 1 validée');
    await expect(page.locator('h3 .badge')).toContainText('Validée');
    await expect(page.locator('button:has-text("Publier et envoyer aux abonnés")')).toBeVisible();
});
