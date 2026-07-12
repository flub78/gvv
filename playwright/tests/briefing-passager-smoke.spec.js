/**
 * Playwright smoke tests for Briefing Passager feature
 *
 * Tests UC1 (upload), UC3 (admin list) and UC2 (digital signature).
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/briefing-passager-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

const LOGIN_URL        = '/index.php/auth/login';
const VLD_LIST_URL     = '/index.php/vols_decouverte';
const VLD_LIST_PAGE_URL = '/index.php/vols_decouverte/page';
const VD_CREATE_URL    = '/index.php/vols_decouverte/create';
const BRIEFING_URL     = '/index.php/briefing_passager';
const ADMIN_LIST_URL   = '/index.php/briefing_passager/admin_list';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const BRIEFING_USER = { username: 'agecanonix',  password: 'password' };

const DB_CONFIG = {
    host: 'localhost',
    user: 'gvv_user',
    password: 'lfoyfgbj',
    database: 'gvv2',
};

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
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
    // Legacy upload mechanism (drop zone, file input, "Déposer un document signé" button)
    // has been removed — only the forms-based "Formulaire en ligne" path remains.
    await expect(page.locator('#drop-zone')).toHaveCount(0);
    await expect(page.locator('input[name="userfile"]')).toHaveCount(0);
    await expect(page.locator('button[name="action"][value="upload"]')).toHaveCount(0);
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
    await login(page, ADMIN_USER);
    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);
    // Should contain the days filter
    await expect(page.locator('input[name="days"]')).toBeVisible();
});

// --- UC3: Admin can change period filter ---

test('UC3: admin can filter briefings by period', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="days"]', '365');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);
    await expect(page.locator('input[name="days"]')).toHaveValue('365');
});

// --- UC3: PDF export link is present ---

test('UC3: PDF export link is present on admin list', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    const pdfLink = page.locator('a[href*="briefing_passager/export_pdf"]');
    await expect(pdfLink).toBeVisible();
});

// --- UC2: Generate digital signature link ---

test('UC2: admin can generate a digital signature link for a VLD', async ({ page }) => {
    await login(page, ADMIN_USER);

    // Navigate to generate_link for a known VLD
    await page.goto('/index.php/briefing_passager/generate_link/16143');
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);
    // QR code image should be present
    await expect(page.locator('img[src^="data:image/png;base64,"]')).toBeVisible();
    // Sign URL input should be present and contain briefing_sign
    const signUrlInput = page.locator('#sign_url');
    await expect(signUrlInput).toBeVisible();
    const signUrl = await signUrlInput.inputValue();
    expect(signUrl).toContain('briefing_sign');
});

test('UC2: anonymous user can access sign page via token link', async ({ browser }) => {
    // First generate a token as admin
    const adminCtx = await browser.newContext();
    const adminPage = await adminCtx.newPage();
    await login(adminPage, ADMIN_USER);
    await adminPage.goto('/index.php/briefing_passager/generate_link/16143');
    await adminPage.waitForLoadState('networkidle');

    const signUrl = await adminPage.locator('#sign_url').inputValue();
    await adminCtx.close();

    expect(signUrl).toMatch(/briefing_sign\//);

    // Now access sign URL as anonymous user (new context = no cookies)
    const anonCtx = await browser.newContext();
    const anonPage = await anonCtx.newPage();
    await anonPage.goto(signUrl);
    await anonPage.waitForLoadState('networkidle');

    // Should show sign page (not login redirect)
    await expect(anonPage).not.toHaveURL(/auth\/login/);
    await expect(anonPage.locator('canvas#signature-pad')).toBeVisible();
    await anonCtx.close();
});

test('UC2: invalid token shows error page', async ({ page }) => {
    const fakeToken = 'a'.repeat(64);
    await page.goto('/index.php/briefing_sign/' + fakeToken);
    await page.waitForLoadState('networkidle');

    // Should show error page with an h3 containing error text
    await expect(page.locator('h3')).toBeVisible();
});

// --- Lot 6, étape 6.5: "link2" is the sole, permanent entry point (no testing_form flag) ---

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
        await login(page, ADMIN_USER);

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
        await page.goto(VLD_LIST_PAGE_URL);
        await page.waitForLoadState('load');
        await page.waitForSelector('a[href*="briefing_passager/upload"]', { timeout: 10000 });
        const rowAfter = page.locator('table tr', { has: page.locator(`a[href*="/vols_decouverte/edit/${vdId}"]`) }).first();
        const briefingLinkAfter = rowAfter.locator(`a[href*="briefing_passager/upload/${vdId}"]`);
        await expect(briefingLinkAfter).toHaveClass(/btn-success/);

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
        if (submissionId) {
            await connection.execute('DELETE FROM form_submission_values WHERE submission_id = ?', [submissionId]);
            await connection.execute('DELETE FROM form_submissions WHERE id = ?', [submissionId]);
        }
        if (vdId) {
            await connection.execute('DELETE FROM vols_decouverte WHERE id = ?', [vdId]);
        }
        await connection.end();
    }
});
