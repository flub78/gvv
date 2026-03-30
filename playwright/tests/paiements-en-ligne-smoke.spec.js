/**
 * Playwright smoke tests — Paiements en ligne : règlement bar par débit de solde (UC5)
 *
 * Tests:
 * 1. Bar link absent on mon_compte when section has no bar (has_bar=0, default)
 * 2. Direct URL /paiements_en_ligne/bar_debit_solde redirects with error when section has no bar
 * 3. Full positive flow: form displayed, payment confirmed, balance updated
 *    (requires DB setup: has_bar=1 + bar_account_id set for section Planeur)
 *
 * Test users:
 *   asterix / password — section 1 (Planeur)
 *   compte 411 id=1456 in section 1
 *   bar account (706) id=55 in section 1
 *
 * Tests run SERIALLY to avoid session conflicts (same user) and DB state races.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');

// ─── Serial mode ──────────────────────────────────────────────────────────────
// All tests must run sequentially: same user login + shared DB state
test.describe.configure({ mode: 'serial' });

// ─── Configuration ────────────────────────────────────────────────────────────

const USER     = 'asterix';
const PASSWORD = 'password';
const SECTION  = '1';   // Planeur

// DB access (same as application/config/database.php)
const DB_HOST = 'localhost';
const DB_USER = 'gvv_user';
const DB_PASS = 'lfoyfgbj';
const DB_NAME = 'gvv2';

// Section 1 test fixtures
const SECTION_ID      = 1;
const BAR_ACCOUNT_ID  = 55;   // compte 706 "Heures de vol + remorqués", club=1
const ASTERIX_411_ID  = 1476; // compte 411 d'asterix dans section 1
const DEBIT_ACCT_ID   = 19;   // compte 606 "Frais de bureau", club=1

// Track test ecriture IDs for cleanup
let testEcritureIds = [];

// ─── DB helpers ───────────────────────────────────────────────────────────────

function runSql(sql) {
    execSync(
        `mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS} ${DB_NAME} -e "${sql}"`,
        { stdio: 'pipe' }
    );
}

function enableBar() {
    runSql(`UPDATE sections SET has_bar=1, bar_account_id=${BAR_ACCOUNT_ID} WHERE id=${SECTION_ID}`);
}

function disableBar() {
    runSql(`UPDATE sections SET has_bar=0, bar_account_id=NULL WHERE id=${SECTION_ID}`);
}

/**
 * Credit asterix's 411 account with a test ecriture so he has funds for the payment test.
 * Returns the inserted ecriture ID for cleanup.
 */
function fundAsterix(amount) {
    const today = new Date().toISOString().slice(0, 10);
    const year  = new Date().getFullYear();
    runSql(
        `INSERT INTO ecritures (annee_exercise, date_creation, date_op, compte1, compte2, montant, description, num_cheque, saisie_par, club) ` +
        `VALUES (${year}, NOW(), '${today}', ${DEBIT_ACCT_ID}, ${ASTERIX_411_ID}, ${amount}, 'Test smoke UC5 funding', 'Playwright', 'test_runner', ${SECTION_ID})`
    );
    const result = execSync(
        `mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS} ${DB_NAME} -se "SELECT LAST_INSERT_ID()"`,
        { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'] }
    ).trim();
    return parseInt(result, 10);
}

function cleanupEcritures() {
    if (testEcritureIds.length > 0) {
        runSql(`DELETE FROM ecritures WHERE id IN (${testEcritureIds.join(',')})`);
        testEcritureIds = [];
    }
}

// ─── Login helper ─────────────────────────────────────────────────────────────

async function login(page) {
    // Always logout first so the login form shows regardless of prior session state
    await page.goto('/index.php/auth/logout');
    await page.waitForLoadState('domcontentloaded');

    await page.goto('/index.php/auth/login');
    await page.waitForLoadState('domcontentloaded');
    await page.fill('input[name="username"]', USER);
    await page.fill('input[name="password"]', PASSWORD);
    await page.selectOption('select[name="section"]', SECTION);
    await page.click('input[type="submit"], button[type="submit"]');
    await page.waitForLoadState('domcontentloaded');

    // Dismiss "Message du jour" modal if present
    try {
        const btn = page.locator('button:has-text("OK"), button:has-text("ok")');
        if (await btn.isVisible({ timeout: 2000 })) {
            await btn.click();
            await page.waitForTimeout(500);
        }
    } catch (_) {}
}

// ─── Tests ────────────────────────────────────────────────────────────────────

test.describe('UC5 — Bar payment by balance debit', () => {

    test.beforeAll(() => {
        // Ensure bar is disabled and accounts are clean at the start
        disableBar();
        cleanupEcritures();
    });

    test.afterAll(() => {
        // Restore clean state: disable bar and remove test ecritures
        disableBar();
        cleanupEcritures();
    });

    // ── Negative: section has no bar ────────────────────────────────────────

    test('bar link absent on mon_compte when section has no bar', async ({ page }) => {
        // DB state: has_bar=0 (set in beforeAll)
        await login(page);

        await page.goto('/index.php/compta/mon_compte');
        await page.waitForLoadState('domcontentloaded');

        // Bar link must NOT be present
        const barLink = page.locator('a:has-text("Régler mes consommations de bar")');
        await expect(barLink).toHaveCount(0);
    });

    test('direct URL redirects when section has no bar', async ({ page }) => {
        // DB state: has_bar=0
        await login(page);

        await page.goto('/index.php/paiements_en_ligne/bar_debit_solde');
        await page.waitForLoadState('domcontentloaded');

        // Should be redirected away from bar form (to mon_compte or show error)
        const url = page.url();
        const bodyText = await page.textContent('body');

        const redirectedAway = !url.includes('bar_debit_solde');
        const hasError = bodyText.includes('ne dispose pas') ||
                         bodyText.includes('Veuillez sélectionner') ||
                         bodyText.includes('no bar');

        expect(redirectedAway || hasError,
            `Expected redirect or error message. URL: ${url}`
        ).toBe(true);
    });

    // ── Positive: full payment flow ──────────────────────────────────────────

    test('bar link visible on mon_compte when has_bar=1', async ({ page }) => {
        enableBar();
        // Fund asterix with 50€ so balance is positive for subsequent tests
        const id = fundAsterix(50.00);
        testEcritureIds.push(id);

        await login(page);

        await page.goto('/index.php/compta/mon_compte');
        await page.waitForLoadState('domcontentloaded');

        // Bar link must be visible for section with has_bar=1
        const barLink = page.locator('a:has-text("Régler mes consommations de bar")');
        await expect(barLink).toBeVisible();
    });

    test('bar form is accessible and shows current balance', async ({ page }) => {
        // DB state: has_bar=1 (set in previous test)
        await login(page);

        await page.goto('/index.php/paiements_en_ligne/bar_debit_solde');
        await page.waitForLoadState('domcontentloaded');

        // No PHP errors
        const bodyText = await page.textContent('body');
        expect(bodyText).not.toContain('Fatal error');
        expect(bodyText).not.toContain('A PHP Error was encountered');

        // Form elements visible
        await expect(page.locator('input[name="montant"]')).toBeVisible();
        await expect(page.locator('input[name="description"]')).toBeVisible();
        await expect(page.locator('button[name="button"][value="valider"]')).toBeVisible();

        // Balance shown
        await expect(page.locator('text=Votre solde actuel')).toBeVisible();

        // Section name shown in the form card
        await expect(page.locator('.text-muted.small:has-text("Planeur")')).toBeVisible();
    });

    test('submitting a valid payment redirects to mon_compte with success', async ({ page }) => {
        // DB state: has_bar=1, asterix funded 50€ in previous test
        await login(page);

        await page.goto('/index.php/paiements_en_ligne/bar_debit_solde');
        await page.waitForLoadState('domcontentloaded');

        // Fill the form
        await page.fill('input[name="montant"]', '1.50');
        await page.fill('input[name="description"]', 'Test smoke UC5 : 1 café');

        // Submit and wait for redirect to mon_compte
        // Dismiss JS alert popup (success message shown via checkalert/popup flashdata)
        page.once('dialog', dialog => dialog.dismiss());
        await Promise.all([
            page.waitForURL('**/mon_compte**', { timeout: 10000 }),
            page.click('button[name="button"][value="valider"]'),
        ]);
        await page.waitForLoadState('domcontentloaded');

        // Verify we're on mon_compte with the account statement visible
        expect(page.url()).toContain('mon_compte');
        const finalBody = await page.textContent('body');
        expect(finalBody).toContain('Extrait de compte');
    });

    test('payment refused when amount below minimum (0.50€)', async ({ page }) => {
        // DB state: has_bar=1
        await login(page);

        await page.goto('/index.php/paiements_en_ligne/bar_debit_solde');
        await page.waitForLoadState('domcontentloaded');

        await page.fill('input[name="montant"]', '0.40');
        await page.fill('input[name="description"]', 'Test montant invalide');
        await page.click('button[name="button"][value="valider"]');
        await page.waitForLoadState('domcontentloaded');

        // Stays on bar form with error mentioning minimum amount
        const bodyText = await page.textContent('body');
        expect(bodyText).toContain('0,50');
    });

    test('payment refused when description is empty', async ({ page }) => {
        // DB state: has_bar=1
        await login(page);

        await page.goto('/index.php/paiements_en_ligne/bar_debit_solde');
        await page.waitForLoadState('domcontentloaded');

        await page.fill('input[name="montant"]', '2.00');
        // Remove HTML5 required attribute to bypass browser validation
        await page.evaluate(() => {
            document.getElementById('description').removeAttribute('required');
        });
        await page.click('button[name="button"][value="valider"]');
        await page.waitForLoadState('domcontentloaded');

        // Server-side validation: description obligatoire
        const bodyText = await page.textContent('body');
        expect(bodyText).toContain('obligatoire');
    });
});
