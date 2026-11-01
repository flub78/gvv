/**
 * Playwright smoke tests for Briefing Passager feature
 *
 * Tests UC1 (paper briefing deposit) and UC3 (admin list). The former UC2 digital
 * signature (briefing_sign/briefing_tokens) was retired in Lot 6, étape 6.6.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/briefing-passager-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const fs = require('fs');
const os = require('os');
const path = require('path');
const { DB_CONFIG } = require('./helpers/gvv-config');

const LOGIN_URL        = '/index.php/auth/login';
const VLD_LIST_URL     = '/index.php/vols_decouverte';
const VLD_LIST_PAGE_URL = '/index.php/vols_decouverte/page';
const VD_CREATE_URL    = '/index.php/vols_decouverte/create';
const BRIEFING_URL     = '/index.php/briefing_passager';
const ADMIN_LIST_URL   = '/index.php/forms_admin/submissions/briefing_passager_ulm';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const BRIEFING_USER = { username: 'agecanonix',  password: 'password' };

async function login(page, user, section = null) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    if (section !== null) {
        const sectionSelect = page.locator('select[name="section"]');
        if (await sectionSelect.count() > 0) {
            await sectionSelect.selectOption(String(section));
        }
    }
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function selectFirstNonEmptyOption(page, selector) {
    const options = page.locator(`${selector} option`);
    const count = await options.count();
    for (let i = 0; i < count; i++) {
        const value = (await options.nth(i).getAttribute('value')) || '';
        if (value.trim() !== '') {
            await page.selectOption(selector, value);
            return value;
        }
    }
    throw new Error(`Aucune option disponible pour ${selector}`);
}

// --- UC1: Access standalone briefing form ---

test('UC1: pilot can access standalone briefing form', async ({ page }) => {
    await login(page, BRIEFING_USER);
    await page.goto(BRIEFING_URL);
    await page.waitForLoadState('networkidle');

    // Page should load without error or authorization denial
    await expect(page).not.toHaveURL(/error|403|404/);
    await expect(page.locator('body')).not.toContainText('Accès non autorisé');
    // Should contain the search field
    await expect(page.locator('#vld_search')).toBeVisible();
});

// --- UC1: VLD list has briefing icon ---

test('UC1: VLD list shows briefing icon column', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(VLD_LIST_URL);
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);

    // At least one clipboard-check icon should be present
    const briefingIcons = page.locator('a[href*="briefing_passager/upload"] i.fa-clipboard-check');
    const count = await briefingIcons.count();
    expect(count).toBeGreaterThan(0);
});

// --- UC1: Clicking briefing icon opens upload form ---

/**
 * @feature Briefing passager
 * Scénario: Le pilote choisit comment enregistrer le briefing d'un vol
 *   Étant donné la liste des vols de découverte
 *   Quand le pilote ouvre le briefing d'un vol qui n'en a pas encore
 *   Alors il peut faire signer le passager en ligne
 *   Et il peut déposer un briefing papier déjà signé
 */
test('UC1: briefing icon opens upload form for that VLD', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(VLD_LIST_URL);
    await page.waitForLoadState('load');
    // DataTables makes continuous AJAX requests — wait for the briefing link instead of networkidle
    await page.waitForSelector('a[href*="briefing_passager/upload"]', { timeout: 10000 });

    // Click first briefing icon
    const firstLink = page.locator('a[href*="briefing_passager/upload"]').first();
    const href = await firstLink.getAttribute('href');
    await firstLink.click();
    await page.waitForLoadState('load');

    await expect(page).not.toHaveURL(/error|403|404/);
    await expect(page.locator('#drop-zone')).toBeVisible();
    await expect(page.locator('button[name="action"][value="upload"]')).toBeVisible();
    await expect(page.locator('button[name="action"][value="link2"]')).toBeVisible();
});

// --- UC1: AJAX search returns results ---

test('UC1: AJAX VLD search returns results for known name', async ({ page }) => {
    await login(page, BRIEFING_USER);

    // Call the search endpoint directly
    const response = await page.request.get('/index.php/briefing_passager/search_vld?q=a');
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(Array.isArray(body)).toBeTruthy();
});

// --- UC3: Admin can access briefing list ---

test('UC3: admin can access briefing admin list', async ({ page }) => {
    await login(page, ADMIN_USER, 2);
    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);
    // Should show the submissions DataTable
    await expect(page.locator('#dt-submissions')).toBeVisible();
});

// --- UC3: Admin can change period filter ---

test('UC3: admin can see briefing submissions table', async ({ page }) => {
    await login(page, ADMIN_USER, 2);
    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);
    // Table should be present
    await expect(page.locator('#dt-submissions')).toBeVisible();
});

// --- UC3: PDF export link is present ---

test('UC3: PDF export link is present on admin list', async ({ page }) => {
    await login(page, ADMIN_USER, 2);
    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    const pdfLink = page.locator('a[href*="forms_admin/submission_pdf"]');
    const count = await pdfLink.count();
    if (count === 0) {
        console.log('No online submissions yet — skipping PDF link check');
        return;
    }
    await expect(pdfLink.first()).toBeVisible();
});

// --- Lot 6, étape 6.5: "link2" is the sole, permanent entry point (no testing_form flag) ---
// UC2 (digital signature via briefing_sign/briefing_tokens) was retired in étape 6.6:
// the controller, routes and views no longer exist.

test('upload form exposes a single permanent signature button (old "signer en ligne" removed)', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(VLD_LIST_PAGE_URL);
    await page.waitForLoadState('load');
    await page.waitForSelector('a[href*="briefing_passager/upload"]', { timeout: 10000 });
    const firstLink = page.locator('a[href*="briefing_passager/upload"]').first();
    await firstLink.click();
    await page.waitForLoadState('domcontentloaded');

    // Only one signature button, always visible (no testing_form flag gate)
    const signatureButtons = page.locator('button[name="action"][value="link2"]');
    await expect(signatureButtons).toHaveCount(1);
    await expect(page.locator('button[name="action"][value="link"]')).toHaveCount(0);
});

// --- Lot 6, étape 6.5 + 6.6: bouton briefing depuis upload -> formulaire pré-rempli -> soumission -> VLD mis à jour ; icon toggles green on submission, grey again on deletion ---

test('briefing_vd icon turns green after briefing-passager-ulm submission, grey again after deletion', async ({ page }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    let vdId;
    let submissionId;

    try {
        // --- Create a throwaway VLD as testadmin (club-admin: full VD + briefing rights) ---
        // Section 2 (ULM) must be selected: the briefing-passager-ulm form is scoped to
        // that club, and forms_admin denies submission_delete on a section mismatch.
        await login(page, ADMIN_USER, 2);

        const ts = Date.now();
        const benef = `PW BRIEFING BASCULE ${ts}`;

        await page.goto(VD_CREATE_URL);
        await page.waitForLoadState('domcontentloaded');
        await expect(page.locator('form[name="saisie"]')).toBeVisible({ timeout: 10000 });
        await selectFirstNonEmptyOption(page, 'select[name="product"]');
        await page.fill('input[name="beneficiaire"]', benef);
        await page.fill('input[name="de_la_part"]', 'Test bascule icone');
        await page.fill('input[name="beneficiaire_email"]', `pw-briefing-bascule-${ts}@example.test`);
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('domcontentloaded');

        await page.goto(VLD_LIST_PAGE_URL);
        await page.waitForLoadState('load');
        await page.waitForSelector('a[href*="briefing_passager/upload"]', { timeout: 10000 });
        const row = page.locator('table tr', { hasText: benef }).first();
        await expect(row).toBeVisible({ timeout: 10000 });
        const editHref = await row.locator('a[href*="/vols_decouverte/edit/"]').first().getAttribute('href');
        const m = editHref.match(/\/vols_decouverte\/edit\/(\d+)/);
        expect(m).toBeTruthy();
        vdId = m[1];

        // --- Icon is grey (btn-outline-secondary) before any briefing ---
        const briefingLink = row.locator(`a[href*="briefing_passager/upload/${vdId}"]`);
        await expect(briefingLink).toHaveClass(/btn-outline-secondary/);

        // --- Fill and submit the VLD-side upload form, then the public form (link2) ---
        await page.goto(`/index.php/briefing_passager/upload/${vdId}`);
        await page.waitForLoadState('domcontentloaded');
        await selectFirstNonEmptyOption(page, 'select[name="aerodrome"]');
        await selectFirstNonEmptyOption(page, 'select[name="airplane_immat"]');
        await selectFirstNonEmptyOption(page, 'select[name="pilote"]');
        await page.click('button[type="submit"][name="action"][value="link2"]');
        await page.waitForLoadState('domcontentloaded');

        await expect(page).toHaveURL(/forms\/briefing-passager-ulm/);
        await page.fill('input[name="prenom"]', 'Jean');
        await page.fill('input[name="nom"]', 'Testeur');
        await page.fill('input[name="date_naissance"]', '1990-01-01');
        await page.fill('input[name="poids_declare"]', '70');
        await page.fill('input[name="personne_a_prevenir"]', 'Marie Testeur');
        await page.fill('input[name="telephone"]', '0600000000');

        // Typed signature (easier to automate than canvas drawing)
        await page.click('button[data-sig-tab="text"]');
        await page.fill('.gvv-sig-text-input', 'Jean Testeur');

        await page.click('button[type="submit"].btn-success');
        await page.waitForLoadState('domcontentloaded');

        // --- Submission redirects to vols_decouverte/page with a dismissible success message
        // (étape 6.6: no more generic "thank you" page for this handler) ---
        await expect(page).toHaveURL(/vols_decouverte\/page/);
        const successAlert = page.locator('.alert-success.alert-dismissible');
        await expect(successAlert).toBeVisible();
        await expect(successAlert.locator('button.btn-close')).toBeVisible();
        const thanksBody = await page.textContent('body');
        expect(thanksBody).not.toContain('Fatal error');

        const [submissionRows] = await connection.execute(
            "SELECT id FROM form_submissions WHERE form_id = 2 AND subject_type = 'vols_decouverte' AND subject_id = ? ORDER BY id DESC LIMIT 1",
            [vdId]
        );
        expect(submissionRows.length).toBe(1);
        submissionId = submissionRows[0].id;

        // --- VLD fields updated by BriefingPassagerUlmHandler (étape 6.5) ---
        const [vldRows] = await connection.execute(
            'SELECT beneficiaire, participation, urgence, beneficiaire_tel FROM vols_decouverte WHERE id = ?',
            [vdId]
        );
        expect(vldRows.length).toBe(1);
        expect(vldRows[0].beneficiaire).toBe('Testeur Jean');
        expect(String(vldRows[0].participation)).toBe('70');
        expect(vldRows[0].urgence).toBe('Marie Testeur');
        expect(vldRows[0].beneficiaire_tel).toBe('0600000000');

        // --- Icon is now green (btn-success) ---
        // Once has_briefing is true, MetaData::table() points the icon straight at the
        // submission PDF (forms_admin/submission_pdf/{form_id}/{submission_id}) instead of
        // briefing_passager/upload/{vdId} (see MetaData.php action 'briefing_vd') — so it must
        // be located by its stable title, not by the upload href used before submission.
        await page.goto(VLD_LIST_PAGE_URL);
        await page.waitForLoadState('load');
        await page.waitForSelector('a[href*="briefing_passager/upload"]', { timeout: 10000 });
        const rowAfter = page.locator('table tr', { has: page.locator(`a[href*="/vols_decouverte/edit/${vdId}"]`) }).first();
        const briefingLinkAfter = rowAfter.locator('a[title="Briefing passager"]');
        await expect(briefingLinkAfter).toHaveClass(/btn-success/);
        await expect(briefingLinkAfter).toHaveAttribute('href', /forms_admin\/submission_pdf\/2\//);

        // --- Delete the submission via forms_admin (real endpoint, already logged in as club-admin) ---
        const del = await page.request.post(`/index.php/forms_admin/submission_delete/2/${submissionId}`);
        expect(del.ok()).toBeTruthy();
        submissionId = null; // already deleted, skip cleanup

        // --- Icon is grey again ---
        await page.goto(VLD_LIST_PAGE_URL);
        await page.waitForLoadState('load');
        await page.waitForSelector('a[href*="briefing_passager/upload"]', { timeout: 10000 });
        const rowFinal = page.locator('table tr', { has: page.locator(`a[href*="/vols_decouverte/edit/${vdId}"]`) }).first();
        const briefingLinkFinal = rowFinal.locator(`a[href*="briefing_passager/upload/${vdId}"]`);
        await expect(briefingLinkFinal).toHaveClass(/btn-outline-secondary/);
    } finally {
        // Look up any submission tied to this VD by subject_id rather than relying
        // solely on the `submissionId` captured mid-test: if the test fails before
        // that capture (e.g. the redirect assertion right after the form POST),
        // submissionId stays null and a real form_submissions row would otherwise
        // leak. Left uncleaned, it would attach its "has_briefing" state to the
        // *next* run's VD, since vols_decouverte's auto-increment recomputes to the
        // same next id once this row is deleted below and nothing else inserts in
        // between on this idle test DB — a stale submission then makes a brand new
        // VD look already-briefed (regression caught 2026-08-03).
        if (vdId) {
            const [orphanRows] = await connection.execute(
                "SELECT id FROM form_submissions WHERE form_id = 2 AND subject_type = 'vols_decouverte' AND subject_id = ?",
                [vdId]
            );
            for (const row of orphanRows) {
                await connection.execute('DELETE FROM form_submission_values WHERE submission_id = ?', [row.id]);
                await connection.execute('DELETE FROM form_submissions WHERE id = ?', [row.id]);
            }
            await connection.execute('DELETE FROM vols_decouverte WHERE id = ?', [vdId]);
        }
        await connection.end();
    }
});

// --- UC1: dépôt d'un briefing papier signé, rattaché au vol ---

// PNG valide de 2x1 pixels : assez pour exercer le dépôt, et une rotation s'y voit à ses
// dimensions, sans fichier versionné.
const TINY_PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAIAAAABCAIAAAB7QOjdAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAD0lEQVQImWNgYGBgYGAAAAAHAAHV3tbYAAAAAElFTkSuQmCC',
    'base64'
);
const pngSize = (buffer) => ({ width: buffer.readUInt32BE(16), height: buffer.readUInt32BE(20) });
const BRIEFING_FORM_ID = 2;

async function fillBriefingPage(page, vdId) {
    await page.goto(`/index.php/briefing_passager/upload/${vdId}`);
    await page.waitForLoadState('domcontentloaded');
    await page.fill('input[name="date_vol"]', '2026-06-15');
    await selectFirstNonEmptyOption(page, 'select[name="aerodrome"]');
    await selectFirstNonEmptyOption(page, 'select[name="airplane_immat"]');
    await selectFirstNonEmptyOption(page, 'select[name="pilote"]');
}

/**
 * @feature Briefing passager
 * Scénario: Un briefing signé sur papier est enregistré sur son vol
 *   Étant donné un vol de découverte sans briefing
 *   Et un briefing papier signé par le passager puis scanné
 *   Quand le gestionnaire dépose le scan depuis le briefing du vol
 *   Alors il est averti que le briefing est enregistré
 *   Et le vol apparaît comme ayant son briefing
 *   Et les informations du vol saisies avec le dépôt sont conservées
 *   Et le scan est consultable depuis la liste des vols
 *   Et un pilote de vols de découverte peut le consulter aussi
 *   Et il peut le redresser s'il est mal orienté
 */
test('UC1: a signed paper briefing is filed on its flight', async ({ page, browser }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    const scanPath = path.join(os.tmpdir(), `briefing-papier-${Date.now()}.png`);
    const uploadDir = path.resolve(__dirname, `../../uploads/reponses/${BRIEFING_FORM_ID}`);
    const uploadDirExisted = fs.existsSync(uploadDir);
    const benef = `PW BRIEFING PAPIER ${Date.now()}`;
    let vdId;

    try {
        fs.writeFileSync(scanPath, TINY_PNG);
        const [result] = await connection.execute(
            "INSERT INTO vols_decouverte (date_vente, club, product, beneficiaire, saisie_par, cancelled) VALUES (CURDATE(), 2, 'PW_BRIEFING_PAPIER', ?, 'playwright', 0)",
            [benef]
        );
        vdId = result.insertId;

        await login(page, ADMIN_USER, 2);
        await fillBriefingPage(page, vdId);
        await page.setInputFiles('input[name="userfile"]', scanPath);
        await expect(page.locator('#filename-userfile')).toHaveText(path.basename(scanPath));
        await page.click('button[name="action"][value="upload"]');
        await page.waitForLoadState('domcontentloaded');

        await expect(page).toHaveURL(/vols_decouverte\/page/);
        const successAlert = page.locator('.alert-success.alert-dismissible');
        await expect(successAlert).toBeVisible();

        const [rows] = await connection.execute(
            "SELECT id, submission_method, upload_comment FROM form_submissions WHERE form_id = ? AND subject_type = 'vols_decouverte' AND subject_id = ?",
            [BRIEFING_FORM_ID, vdId]
        );
        expect(rows.length).toBe(1);
        expect(rows[0].submission_method).toBe('upload');
        expect(rows[0].upload_comment).toContain(benef);

        const [vldRows] = await connection.execute(
            "SELECT DATE_FORMAT(date_vol, '%Y-%m-%d') AS date_vol, aerodrome FROM vols_decouverte WHERE id = ?",
            [vdId]
        );
        expect(vldRows[0].date_vol).toBe('2026-06-15');
        expect(vldRows[0].aerodrome).toBeTruthy();

        // Le vol tombe sur l'année du vol et non de la vente : on retrouve l'icône dans la liste de l'année courante.
        const row = page.locator('table tr', { has: page.locator(`a[href*="/vols_decouverte/edit/${vdId}"]`) }).first();
        await expect(row).toBeVisible({ timeout: 10000 });
        const icon = row.locator('a[title="Briefing passager"]');
        await expect(icon).toHaveClass(/btn-success/);
        const href = await icon.getAttribute('href');

        const adminFile = await page.request.get(href);
        expect(adminFile.status()).toBe(200);
        expect(adminFile.headers()['content-type']).toContain('image/png');

        const pilotContext = await browser.newContext();
        try {
            const pilotPage = await pilotContext.newPage();
            await login(pilotPage, BRIEFING_USER, 1);
            const pilotFile = await pilotPage.request.get(href);
            expect(pilotFile.status()).toBe(200);
            expect(pilotFile.headers()['content-type']).toContain('image/png');
            expect(pngSize(await pilotFile.body())).toEqual({ width: 2, height: 1 });

            const rotate = await pilotPage.request.get(
                `/index.php/forms_admin/submission_rotate/${BRIEFING_FORM_ID}/${rows[0].id}/cw`,
                { maxRedirects: 0 }
            );
            expect(rotate.status()).toBe(302);
            const rotated = await pilotPage.request.get(href);
            expect(pngSize(await rotated.body())).toEqual({ width: 1, height: 2 });
        } finally {
            await pilotContext.close();
        }

        const del = await page.request.post(`/index.php/forms_admin/submission_delete/${BRIEFING_FORM_ID}/${rows[0].id}`);
        expect(del.ok()).toBeTruthy();
    } finally {
        if (vdId) {
            const [leftovers] = await connection.execute(
                "SELECT id FROM form_submissions WHERE form_id = ? AND subject_type = 'vols_decouverte' AND subject_id = ?",
                [BRIEFING_FORM_ID, vdId]
            );
            for (const leftover of leftovers) {
                await page.request.post(`/index.php/forms_admin/submission_delete/${BRIEFING_FORM_ID}/${leftover.id}`);
            }
            await connection.execute('DELETE FROM vols_decouverte WHERE id = ?', [vdId]);
        }
        fs.rmSync(scanPath, { force: true });
        if (!uploadDirExisted && fs.existsSync(uploadDir) && fs.readdirSync(uploadDir).length === 0) {
            fs.rmdirSync(uploadDir);
        }
        await connection.end();
    }
});

/**
 * @feature Briefing passager
 * Scénario: Un dépôt incorrect est signalé et n'enregistre aucun briefing
 *   Étant donné un vol de découverte sans briefing
 *   Quand le gestionnaire demande le dépôt sans avoir choisi de fichier
 *   Alors il est averti qu'un fichier est nécessaire
 *   Quand il dépose un fichier dont le format n'est pas accepté
 *   Alors il est averti que le fichier est refusé
 *   Et le vol reste sans briefing
 */
test('UC1: an invalid paper briefing deposit is reported and files nothing', async ({ page }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    const badPath = path.join(os.tmpdir(), `briefing-papier-${Date.now()}.txt`);
    const uploadDir = path.resolve(__dirname, `../../uploads/reponses/${BRIEFING_FORM_ID}`);
    const uploadDirExisted = fs.existsSync(uploadDir);
    let vdId;

    try {
        fs.writeFileSync(badPath, 'ceci n\'est pas un scan');
        const [result] = await connection.execute(
            "INSERT INTO vols_decouverte (date_vente, club, product, beneficiaire, saisie_par, cancelled) VALUES (CURDATE(), 2, 'PW_BRIEFING_PAPIER', 'PW BRIEFING REFUS', 'playwright', 0)"
        );
        vdId = result.insertId;

        await login(page, ADMIN_USER, 2);
        await fillBriefingPage(page, vdId);
        await page.click('button[name="action"][value="upload"]');
        await page.waitForLoadState('domcontentloaded');
        await expect(page.locator('.alert-danger.alert-dismissible')).toContainText('sélectionner un fichier');

        await page.setInputFiles('input[name="userfile"]', badPath);
        await page.click('button[name="action"][value="upload"]');
        await page.waitForLoadState('domcontentloaded');
        await expect(page.locator('.alert-danger.alert-dismissible')).toContainText('Fichier refusé');

        const [rows] = await connection.execute(
            "SELECT id FROM form_submissions WHERE form_id = ? AND subject_type = 'vols_decouverte' AND subject_id = ?",
            [BRIEFING_FORM_ID, vdId]
        );
        expect(rows.length).toBe(0);
    } finally {
        if (vdId) {
            await connection.execute(
                "DELETE FROM form_submissions WHERE form_id = ? AND subject_type = 'vols_decouverte' AND subject_id = ?",
                [BRIEFING_FORM_ID, vdId]
            );
            await connection.execute('DELETE FROM vols_decouverte WHERE id = ?', [vdId]);
        }
        fs.rmSync(badPath, { force: true });
        if (!uploadDirExisted && fs.existsSync(uploadDir) && fs.readdirSync(uploadDir).length === 0) {
            fs.rmdirSync(uploadDir);
        }
        await connection.end();
    }
});

/**
 * @feature Briefing passager
 * Scénario: Un scan choisi n'est jamais perdu, quel que soit le bouton utilisé
 *   Étant donné un vol de découverte sans briefing
 *   Et un scan de briefing choisi par le gestionnaire
 *   Quand il enregistre la page au lieu de demander le dépôt
 *   Alors le scan est tout de même déposé sur le vol
 *   Et il est averti que le briefing est enregistré
 */
test('UC1: a selected scan is deposited even with the save button', async ({ page }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    const scanPath = path.join(os.tmpdir(), `briefing-papier-enregistrer-${Date.now()}.png`);
    const uploadDir = path.resolve(__dirname, `../../uploads/reponses/${BRIEFING_FORM_ID}`);
    const uploadDirExisted = fs.existsSync(uploadDir);
    let vdId;

    try {
        fs.writeFileSync(scanPath, TINY_PNG);
        const [result] = await connection.execute(
            "INSERT INTO vols_decouverte (date_vente, club, product, beneficiaire, saisie_par, cancelled) VALUES (CURDATE(), 2, 'PW_BRIEFING_PAPIER', 'PW BRIEFING ENREGISTRER', 'playwright', 0)"
        );
        vdId = result.insertId;

        await login(page, ADMIN_USER, 2);
        await fillBriefingPage(page, vdId);
        await page.setInputFiles('input[name="userfile"]', scanPath);
        await page.click('button[name="action"][value="save"]');
        await page.waitForLoadState('domcontentloaded');

        await expect(page.locator('.alert-success.alert-dismissible')).toBeVisible();
        const [rows] = await connection.execute(
            "SELECT id, submission_method FROM form_submissions WHERE form_id = ? AND subject_type = 'vols_decouverte' AND subject_id = ?",
            [BRIEFING_FORM_ID, vdId]
        );
        expect(rows.length).toBe(1);
        expect(rows[0].submission_method).toBe('upload');
    } finally {
        if (vdId) {
            const [leftovers] = await connection.execute(
                "SELECT id FROM form_submissions WHERE form_id = ? AND subject_type = 'vols_decouverte' AND subject_id = ?",
                [BRIEFING_FORM_ID, vdId]
            );
            for (const leftover of leftovers) {
                await page.request.post(`/index.php/forms_admin/submission_delete/${BRIEFING_FORM_ID}/${leftover.id}`);
            }
            await connection.execute('DELETE FROM vols_decouverte WHERE id = ?', [vdId]);
        }
        fs.rmSync(scanPath, { force: true });
        if (!uploadDirExisted && fs.existsSync(uploadDir) && fs.readdirSync(uploadDir).length === 0) {
            fs.rmdirSync(uploadDir);
        }
        await connection.end();
    }
});

/**
 * @feature Briefing passager
 * Scénario: Seuls les administrateurs peuvent supprimer un briefing
 *   Étant donné un briefing enregistré
 *   Et un pilote de vols de découverte de la section du formulaire de briefing
 *   Quand ce pilote consulte la liste des briefings
 *   Alors il voit le briefing
 *   Mais aucune suppression ne lui est proposée
 *   Et une demande de suppression de sa part est refusée
 *   Quand un administrateur consulte la même liste
 *   Alors la suppression lui est proposée
 */
test('only administrators are offered briefing deletion', async ({ page, browser }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    const roleRowIds = [];
    let submissionId;

    try {
        const [result] = await connection.execute(
            "INSERT INTO form_submissions (form_id, submission_uuid, status, submission_method, created_at, submitted_at) VALUES (?, ?, 'submitted', 'online', NOW(), NOW())",
            [BRIEFING_FORM_ID, `pw-suppression-${Date.now()}`]
        );
        submissionId = result.insertId;

        for (const roleName of ['user', 'pilote_vd']) {
            const [grant] = await connection.execute(
                `INSERT INTO user_roles_per_section (user_id, types_roles_id, section_id)
                 SELECT u.id, r.id, 2 FROM users u JOIN types_roles r ON r.nom = ? WHERE u.username = ?`,
                [roleName, BRIEFING_USER.username]
            );
            roleRowIds.push(grant.insertId);
        }

        const pilotContext = await browser.newContext();
        try {
            const pilotPage = await pilotContext.newPage();
            await login(pilotPage, BRIEFING_USER, 2);
            await pilotPage.goto(ADMIN_LIST_URL);
            await pilotPage.waitForLoadState('load');
            await expect(pilotPage.locator('#dt-submissions')).toBeVisible();
            await expect(pilotPage.locator('.btn-delete-submission')).toHaveCount(0);

            const refused = await pilotPage.request.post(
                `/index.php/forms_admin/submission_delete/${BRIEFING_FORM_ID}/${submissionId}`,
                { maxRedirects: 0 }
            );
            expect(refused.status()).toBe(403);
        } finally {
            await pilotContext.close();
        }

        const [kept] = await connection.execute('SELECT id FROM form_submissions WHERE id = ?', [submissionId]);
        expect(kept.length).toBe(1);

        await login(page, ADMIN_USER, 2);
        await page.goto(ADMIN_LIST_URL);
        await page.waitForLoadState('load');
        await expect(page.locator(`.btn-delete-submission[data-submission-id="${submissionId}"]`)).toHaveCount(1);
    } finally {
        if (submissionId) {
            await connection.execute('DELETE FROM form_submissions WHERE id = ?', [submissionId]);
        }
        for (const id of roleRowIds) {
            await connection.execute('DELETE FROM user_roles_per_section WHERE id = ?', [id]);
        }
        await connection.end();
    }
});

// --- Régression 2026-10-04 : formulaire ouvert sans vol (carte du tableau de bord) ---
// Avant correctif : le contexte d'un briefing précédent (vol, passager pré-rempli) restait
// en session, et une réponse sans vol était signalée « Erreur lors de l'enregistrement du
// briefing. ». Désormais une ouverture sans paramètre repart d'un formulaire vierge et
// la réponse est acceptée sans être rattachée à un vol.

test('briefing opened without VLD after a VLD briefing: blank form, saved, not attached to the previous VLD', async ({ page }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    const benef = `PW BRIEFING SANS VLD ${Date.now()}`;
    const [maxRows] = await connection.execute('SELECT COALESCE(MAX(id), 0) AS max_id FROM form_submissions');
    const maxSubmissionId = maxRows[0].max_id;
    const [ins] = await connection.execute(
        "INSERT INTO vols_decouverte (date_vente, club, product, saisie_par, beneficiaire) VALUES (CURDATE(), 2, 'abbeville_ulm', 'playwright', ?)",
        [benef]
    );
    const vdId = ins.insertId;

    try {
        await login(page, ADMIN_USER, 2);

        // Briefing commencé depuis le vol puis abandonné : le contexte du vol est en session.
        await page.goto(`/index.php/briefing_passager/upload/${vdId}`);
        await page.waitForLoadState('domcontentloaded');
        await selectFirstNonEmptyOption(page, 'select[name="aerodrome"]');
        await selectFirstNonEmptyOption(page, 'select[name="airplane_immat"]');
        await selectFirstNonEmptyOption(page, 'select[name="pilote"]');
        await page.click('button[type="submit"][name="action"][value="link2"]');
        await page.waitForLoadState('domcontentloaded');
        await expect(page.locator('input[name="nom"]')).toHaveValue(benef);

        // Ouverture sans paramètre, comme depuis le tableau de bord : formulaire vierge.
        await page.goto('/forms/briefing-passager-ulm');
        await page.waitForLoadState('domcontentloaded');
        await expect(page.locator('input[name="nom"]')).toHaveValue('');
        await expect(page.locator('input[name="site_decollage"]')).toHaveValue('');

        await page.fill('input[name="date_vol"]', '2026-10-04');
        await page.fill('input[name="prenom"]', 'Jean');
        await page.fill('input[name="nom"]', 'SansVol');
        await page.fill('input[name="date_naissance"]', '1990-01-01');
        await page.fill('input[name="poids_declare"]', '70');
        await page.fill('input[name="personne_a_prevenir"]', 'Marie');
        await page.fill('input[name="telephone"]', '0600000000');
        await page.click('button[data-sig-tab="text"]');
        await page.fill('.gvv-sig-text-input', 'Jean SansVol');
        await page.click('button[type="submit"].btn-success');
        await page.waitForLoadState('domcontentloaded');

        const successAlert = page.locator('.alert-success.alert-dismissible');
        await expect(successAlert).toBeVisible();
        await expect(successAlert).toContainText('aucun vol de découverte');
        await expect(page.locator('.alert-danger')).toHaveCount(0);

        const [subs] = await connection.execute(
            'SELECT id, subject_type, subject_id FROM form_submissions WHERE form_id = 2 AND id > ?',
            [maxSubmissionId]
        );
        expect(subs.length).toBe(1);
        expect(subs[0].subject_type).toBeNull();
        expect(subs[0].subject_id).toBeNull();

        const [vld] = await connection.execute('SELECT beneficiaire FROM vols_decouverte WHERE id = ?', [vdId]);
        expect(vld[0].beneficiaire).toBe(benef);
    } finally {
        const [subs] = await connection.execute(
            'SELECT id FROM form_submissions WHERE form_id = 2 AND id > ?',
            [maxSubmissionId]
        );
        for (const row of subs) {
            const [files] = await connection.execute('SELECT storage_path FROM form_submission_files WHERE submission_id = ?', [row.id]);
            for (const f of files) {
                const p = path.resolve(__dirname, '../..', String(f.storage_path).replace(/^\/+/, ''));
                if (fs.existsSync(p)) fs.unlinkSync(p);
            }
            await connection.execute('DELETE FROM form_submission_files WHERE submission_id = ?', [row.id]);
            await connection.execute('DELETE FROM form_submission_values WHERE submission_id = ?', [row.id]);
            await connection.execute('DELETE FROM form_submissions WHERE id = ?', [row.id]);
        }
        await connection.execute('DELETE FROM vols_decouverte WHERE id = ?', [vdId]);
        await connection.end();
    }
});
