/**
 * Playwright Tests – Séances de formation théoriques + Rapports annuels (Phase 2 & 3)
 *
 * Smoke tests for the theoretical training sessions and annual reports features.
 * Verifies:
 *   1. Access to formation_types_seances list
 *   2. Access to formation_seances_theoriques list
 *   3. Creation form loads with participant search widget
 *   4. Nature filter appears on formation_seances list
 *   5. Annual consolidated report is accessible
 *   6. Compliance report is accessible
 *
 * Prerequisites:
 *   - testadmin user must exist (for formation_types_seances which requires is_admin())
 *   - abraracourcix user must exist with instructor rights (for seances, seances_theoriques, rapports)
 *   - Migration 078 and 079 must be applied
 *   - Feature flag gestion_formations must be enabled
 *
 * @see doc/plans/seances_theoriques_plan.md Phase 2 & 3
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
// testadmin: legacy admin (is_admin()=true) for formation_types_seances
const ADMIN_USER = { username: 'testadmin', password: 'password' };
// abraracourcix: instructor in new auth system (BIT_FI_AVION) for other formation controllers
const INSTRUCTOR_USER = { username: 'abraracourcix', password: 'password' };

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Formation – Types de séances', () => {

    test('La liste des types de séances est accessible', async ({ page }) => {
        await login(page, ADMIN_USER); // formation_types_seances requires is_admin()
        await page.goto('/index.php/formation_types_seances');
        await expect(page).not.toHaveURL(/login/);
        // The page title contains the expected heading or content
        const body = await page.textContent('body');
        expect(body).toContain('séance');
    });

});

test.describe('Formation – Séances théoriques', () => {

    test('La liste des séances théoriques est accessible', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);
        await page.goto('/index.php/formation_seances_theoriques');
        await expect(page).not.toHaveURL(/login/);
        const body = await page.textContent('body');
        expect(body).toMatch(/[Ss]éance/);
    });

    test('Le formulaire de création charge le widget de participants', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);
        await page.goto('/index.php/formation_seances_theoriques/create');
        await expect(page).not.toHaveURL(/login/);

        // The participant select dropdown must be present
        const searchInput = page.locator('#participant-select');
        await expect(searchInput).toBeVisible();

        // The participants list container must be present
        const badges = page.locator('#participants-list');
        await expect(badges).toBeVisible();
    });

    test('La liste des séances (historique) contient le filtre Nature', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);
        await page.goto('/index.php/formation_seances');
        await expect(page).not.toHaveURL(/login/);

        const natureFilter = page.locator('select[name="nature"]');
        await expect(natureFilter).toBeVisible();

        // Options: all, vol, theorique
        const options = await natureFilter.locator('option').allTextContents();
        expect(options.some(o => o.toLowerCase().includes('vol'))).toBeTruthy();
        expect(options.some(o => o.toLowerCase().includes('cours') || o.toLowerCase().includes('sol') || o.toLowerCase().includes('théorique'))).toBeTruthy();
    });

    test('La recherche AJAX de membres retourne du JSON', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);

        const response = await page.request.get(
            '/index.php/formation_seances_theoriques/ajax_search_membres?q=a'
        );
        expect(response.status()).toBe(200);

        const contentType = response.headers()['content-type'] || '';
        expect(contentType).toContain('application/json');

        const body = await response.json();
        expect(Array.isArray(body)).toBe(true);
    });

});

test.describe('Formation – Pièces jointes aux séances théoriques', () => {

    const path = require('path');
    const TEST_FILE = path.resolve(__dirname, '../../test-data/test_cours.txt');

    // Helper: find the first existing seance theorique ID from the list page
    async function getFirstSeanceId(page) {
        await page.goto('/index.php/formation_seances_theoriques');
        const detailLink = page.locator('a[href*="/formation_seances_theoriques/detail/"]').first();
        await expect(detailLink).toBeVisible();
        const href = await detailLink.getAttribute('href');
        const match = href.match(/\/detail\/(\d+)/);
        return match ? match[1] : null;
    }

    test('Peut attacher un document à une séance théorique', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);

        const seanceId = await getFirstSeanceId(page);
        expect(seanceId).not.toBeNull();

        await page.goto(`/index.php/formation_seances_theoriques/detail/${seanceId}`);
        await expect(page).not.toHaveURL(/login/);

        // Section Documents must be present
        const section = page.locator('#formationAttachments');
        await expect(section).toBeVisible();

        // Open upload form
        await page.click('#showUploadForm');
        await expect(page.locator('#uploadFormCard')).toBeVisible();

        // Fill description
        await page.fill('#newAttachmentDescription', 'Support de cours E2E test');

        // Upload file
        const [fileChooser] = await Promise.all([
            page.waitForEvent('filechooser'),
            page.click('#newAttachmentFile'),
        ]);
        await fileChooser.setFiles(TEST_FILE);

        // Submit
        await page.click('#saveNewAttachment');

        // Row must appear in the table
        await expect(page.locator('#attachmentsTable')).toBeVisible({ timeout: 5000 });
        const tableText = await page.locator('#attachmentsTable').textContent();
        expect(tableText).toContain('Support de cours E2E test');
    });

    test('Peut supprimer un document d\'une séance théorique', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);

        const seanceId = await getFirstSeanceId(page);
        expect(seanceId).not.toBeNull();

        await page.goto(`/index.php/formation_seances_theoriques/detail/${seanceId}`);

        // Upload a document and capture the attachment ID from the AJAX response
        await page.click('#showUploadForm');
        await page.fill('#newAttachmentDescription', 'Doc à supprimer');

        const [fileChooser] = await Promise.all([
            page.waitForEvent('filechooser'),
            page.click('#newAttachmentFile'),
        ]);
        await fileChooser.setFiles(TEST_FILE);

        const [uploadResponse] = await Promise.all([
            page.waitForResponse(resp => resp.url().includes('ajax_upload_attachment'), { timeout: 8000 }),
            page.click('#saveNewAttachment'),
        ]);
        const uploadJson = await uploadResponse.json();
        expect(uploadJson.success).toBe(true);
        const attachmentId = uploadJson.attachment_id;

        // Target the specific row by its attachment ID (immune to leftover rows from previous runs)
        const targetRow = page.locator(`#attachmentsTable tbody tr[data-attachment-id="${attachmentId}"]`);
        await expect(targetRow).toBeVisible({ timeout: 5000 });

        // Delete and verify via AJAX response
        page.once('dialog', dialog => dialog.accept());
        const [deleteResponse] = await Promise.all([
            page.waitForResponse(resp => resp.url().includes('ajax_delete_attachment'), { timeout: 8000 }),
            targetRow.locator('.delete-attachment-btn').click(),
        ]);

        const json = await deleteResponse.json();
        expect(json.success).toBe(true);

        // The specific row must disappear from DOM
        await expect(targetRow).not.toBeVisible({ timeout: 3000 });
    });

    test('La création sans instructeur fonctionne', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);

        await page.goto('/index.php/formation_seances_theoriques/create');
        await expect(page).not.toHaveURL(/login/);

        // Fill required fields — leave instructor empty
        await page.fill('input[name="date_seance"]', new Date().toISOString().slice(0, 10));

        // Select a type_seance_id (first available option)
        const typeSelect = page.locator('select[name="type_seance_id"]');
        await expect(typeSelect).toBeVisible();
        const options = await typeSelect.locator('option').all();
        const firstNonEmpty = options.find(async o => (await o.getAttribute('value')) !== '');
        if (firstNonEmpty) {
            const val = await (await typeSelect.locator('option').nth(1)).getAttribute('value');
            await typeSelect.selectOption(val);
        }

        // Add at least one participant via the hidden input
        // Use the AJAX endpoint to confirm it works, then inject a participant
        await page.evaluate(() => {
            const list = document.getElementById('participants-list');
            if (!list) return;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'participants[]';
            input.value = 'abraracourcix';
            list.appendChild(input);
        });

        await page.click('button[type="submit"]');

        // Should redirect to detail page (not stay on form with error)
        await page.waitForLoadState('networkidle');
        const url = page.url();
        expect(url).toMatch(/formation_seances_theoriques\/detail\//);

        // Clean up: delete the created seance
        const match = url.match(/\/detail\/(\d+)/);
        if (match) {
            page.once('dialog', d => d.accept());
            await page.goto(`/index.php/formation_seances_theoriques/delete/${match[1]}`);
        }
    });

});

test.describe('Formation – Rapports annuels (Phase 3)', () => {

    test('Le rapport annuel consolidé est accessible', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);
        await page.goto('/index.php/formation_rapports/annuel');
        await expect(page).not.toHaveURL(/login/);

        const body = await page.textContent('body');
        // Should contain the tab headers
        expect(body).toMatch(/instructeur|programme/i);
    });

    test('Le rapport annuel affiche les deux onglets', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);
        await page.goto('/index.php/formation_rapports/annuel');
        await expect(page).not.toHaveURL(/login/);

        // Both tabs should be present
        await expect(page.locator('#tab-instructeurs-tab')).toBeVisible();
        await expect(page.locator('#tab-programmes-tab')).toBeVisible();
    });

    test("L'export CSV du rapport annuel est accessible", async ({ page }) => {
        await login(page, INSTRUCTOR_USER);

        const year = new Date().getFullYear();
        const response = await page.request.get(
            `/index.php/formation_rapports/export_annuel_csv/${year}`
        );
        expect(response.status()).toBe(200);

        const contentType = response.headers()['content-type'] || '';
        expect(contentType.toLowerCase()).toMatch(/csv|text/);
    });

    test('Le rapport de conformité est accessible', async ({ page }) => {
        await login(page, INSTRUCTOR_USER);
        await page.goto('/index.php/formation_rapports/conformite');
        await expect(page).not.toHaveURL(/login/);

        const body = await page.textContent('body');
        expect(body).toMatch(/conformit|p.riodicit/i);
    });

});
