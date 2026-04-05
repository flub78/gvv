/**
 * Playwright smoke tests — Paiements en ligne : configuration admin HelloAsso (EF5)
 *
 * Tests:
 * 1. Access denied for a non-admin user (asterix)
 * 2. Admin user (testadmin) can reach the form and select a section
 * 3. Saving the config shows a success message and values are reloaded correctly
 * 4. [SKIP] Test-connexion button (requires live sandbox credentials)
 *
 * Test users:
 *   testadmin / password — admin (role_id=2)
 *   asterix   / password — regular user (role_id=1, section 1 Planeur)
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-admin-config.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');

// ─── Serial mode ──────────────────────────────────────────────────────────────
test.describe.configure({ mode: 'serial' });

// ─── Configuration ────────────────────────────────────────────────────────────

const ADMIN_USER     = 'testadmin';
const PILOT_USER     = 'asterix';
const PASSWORD       = 'password';
const SECTION_ID     = 4;   // Général (has compte 467 required for save)
const ADMIN_CONFIG_URL = '/index.php/paiements_en_ligne/admin_config';

// DB access
const DB_USER = 'gvv_user';
const DB_PASS = 'lfoyfgbj';
const DB_NAME = 'gvv2';

// Test config values written during save test
const TEST_CLIENT_ID  = 'test_client_playwright';
const TEST_SLUG       = 'aeroclub-playwright-test';

// ─── DB helpers ───────────────────────────────────────────────────────────────

function runSql(sql) {
    return execSync(
        `mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} -se "${sql}"`,
        { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'] }
    ).trim();
}

function cleanupTestConfig() {
    runSql(
        `DELETE FROM paiements_en_ligne_config ` +
        `WHERE club=${SECTION_ID} AND plateforme='helloasso' ` +
        `AND param_key IN ('client_id','account_slug')`
    );
}

// ─── Login helpers ────────────────────────────────────────────────────────────

async function loginAs(page, username, section = null) {
    await page.goto('/index.php/auth/logout');
    await page.waitForLoadState('domcontentloaded');

    await page.goto('/index.php/auth/login');
    await page.waitForLoadState('domcontentloaded');
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', PASSWORD);

    const sectionSelect = page.locator('select[name="section"]');
    if (section && await sectionSelect.count() > 0) {
        await sectionSelect.selectOption(String(section));
    }
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

test.describe('EF5 — Admin config HelloAsso', () => {

    test.afterAll(() => {
        // Remove test-only config entries to leave DB clean
        cleanupTestConfig();
    });

    // ── Access control ───────────────────────────────────────────────────────

    test('non-admin user is denied access to admin_config', async ({ page }) => {
        await loginAs(page, PILOT_USER, SECTION_ID);

        await page.goto(ADMIN_CONFIG_URL);
        await page.waitForLoadState('domcontentloaded');

        const url     = page.url();
        const content = await page.content();

        const isDenied =
            url.includes('/auth/deny') ||
            url.includes('/auth/login') ||
            content.includes('Accès non autorisé') ||
            content.includes('Accès refusé') ||
            content.includes('Accès réservé aux administrateurs') ||
            content.includes('réservé');

        expect(isDenied,
            `Expected access DENIED for ${PILOT_USER} on admin_config (URL: ${url})`
        ).toBe(true);
    });

    // ── Admin can reach the form ──────────────────────────────────────────────

    test('admin user can open admin_config without PHP errors', async ({ page }) => {
        await loginAs(page, ADMIN_USER);

        await page.goto(ADMIN_CONFIG_URL);
        await page.waitForLoadState('domcontentloaded');

        const bodyText = await page.textContent('body');
        expect(bodyText).not.toContain('Fatal error');
        expect(bodyText).not.toContain('Parse error');
        expect(bodyText).not.toContain('A PHP Error was encountered');
        expect(bodyText).not.toContain('An uncaught Exception was encountered');

        // Section selector visible
        await expect(page.locator('select.form-select').first()).toBeVisible();

        // Page title present
        await expect(page.locator('h3')).toContainText('HelloAsso');
    });

    test('admin selects a section and config form is shown', async ({ page }) => {
        await loginAs(page, ADMIN_USER);

        await page.goto(`${ADMIN_CONFIG_URL}?section=${SECTION_ID}`);
        await page.waitForLoadState('domcontentloaded');

        const bodyText = await page.textContent('body');
        expect(bodyText).not.toContain('Fatal error');
        expect(bodyText).not.toContain('A PHP Error was encountered');

        // The three config cards should be present
        await expect(page.locator('text=Client ID')).toBeVisible();
        await expect(page.locator('text=Slug')).toBeVisible();
        await expect(page.locator('input[name="client_id"]')).toBeVisible();
        await expect(page.locator('input[name="account_slug"]')).toBeVisible();

        // Bar config card
        await expect(page.locator('input[name="has_bar"]')).toBeVisible();

        // Transaction params card
        await expect(page.locator('select[name="compte_passage"]')).toBeVisible();
        await expect(page.locator('input[name="montant_min"]')).toBeVisible();
        await expect(page.locator('input[name="montant_max"]')).toBeVisible();

        // Save button
        await expect(page.locator('button[value="save"]')).toBeVisible();
    });

    // ── Save and reload ───────────────────────────────────────────────────────

    test('saving config shows success and values are reloaded', async ({ page }) => {
        // Ensure clean state before writing
        cleanupTestConfig();

        await loginAs(page, ADMIN_USER);

        await page.goto(`${ADMIN_CONFIG_URL}?section=${SECTION_ID}`);
        await page.waitForLoadState('domcontentloaded');

        // Fill test values
        await page.fill('input[name="client_id"]', TEST_CLIENT_ID);
        await page.fill('input[name="account_slug"]', TEST_SLUG);

        // compte_passage is mandatory — select first non-empty option (codec 467 account)
        const compteSelect = page.locator('select[name="compte_passage"]');
        if (await compteSelect.count() > 0) {
            const options = await compteSelect.locator('option').all();
            for (const opt of options) {
                const val = await opt.getAttribute('value');
                if (val && val !== '0' && val !== '') {
                    await compteSelect.selectOption(val);
                    break;
                }
            }
        }

        // Submit
        await page.click('button[value="save"]');
        await page.waitForLoadState('domcontentloaded');

        const bodyAfterSave = await page.textContent('body');
        expect(bodyAfterSave).not.toContain('Fatal error');

        // Success message
        const hasSuccess =
            bodyAfterSave.includes('enregistrée') ||
            bodyAfterSave.includes('Configuration saved') ||
            bodyAfterSave.includes('Configuration opgeslagen') ||
            page.url().includes('section=' + SECTION_ID);

        expect(hasSuccess,
            'Expected a success message or a redirect back to the config page'
        ).toBe(true);

        // Reload same URL and verify values are preserved
        await page.goto(`${ADMIN_CONFIG_URL}?section=${SECTION_ID}`);
        await page.waitForLoadState('domcontentloaded');

        const clientIdValue = await page.inputValue('input[name="client_id"]');
        expect(clientIdValue).toBe(TEST_CLIENT_ID);

        const slugValue = await page.inputValue('input[name="account_slug"]');
        expect(slugValue).toBe(TEST_SLUG);
    });

    // ── [SKIP] Test connexion button ──────────────────────────────────────────

    test.skip('test-connexion button returns success in sandbox mode', async ({ page }) => {
        // Skipped: requires live HelloAsso sandbox credentials configured in DB.
        // To run manually: configure section 1 with valid sandbox client_id/secret,
        // then remove the test.skip() marker.
        await loginAs(page, ADMIN_USER);
        await page.goto(`${ADMIN_CONFIG_URL}?section=${SECTION_ID}`);
        await page.waitForLoadState('domcontentloaded');

        // Click test connection button and wait for AJAX result
        await page.click('button.btn-outline-info');
        await page.waitForTimeout(3000);

        const resultText = await page.textContent('#test-result');
        expect(resultText).toContain('succès');
    });

});
