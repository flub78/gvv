// End-to-end test: importing txt/csv files that carry informal info (name,
// phone number) on the same line/row as the email address must preserve
// that info instead of silently dropping it, and the recipients list must
// display entries sorted by name (when known) then by email.
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const os = require('os');
const path = require('path');

const UPLOADS_ROOT = path.join(__dirname, '..', '..', 'uploads', 'email_lists');

test.describe('Email Lists Import - Phone Numbers and Names', () => {
    let createdListIds = [];

    test.beforeEach(async ({ page }) => {
        createdListIds = [];

        await page.goto('/index.php/auth/login');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');

        const modDialog = page.locator('.ui-dialog');
        if (await modDialog.isVisible().catch(() => false)) {
            const closeButton = page.locator('.ui-dialog-buttonpane button:has-text("OK")');
            if (await closeButton.isVisible().catch(() => false)) {
                await closeButton.click();
                await page.waitForTimeout(500);
            }
        }
    });

    test.afterEach(async ({ page }) => {
        for (const id of createdListIds) {
            // Use page.request (shares the authenticated session's cookies)
            // rather than the bare `request` fixture, which is unauthenticated
            // and would silently no-op the delete.
            await page.request.get(`/index.php/email_lists/delete/${id}`).catch(() => {});
            // delete_list() only removes DB rows (cascaded via FK); the
            // uploaded file directory is left on disk and must be cleaned
            // up here so the test doesn't leak filesystem state.
            fs.rmSync(path.join(UPLOADS_ROOT, String(id)), { recursive: true, force: true });
        }
        createdListIds = [];
    });

    async function createList(page, name) {
        await page.goto('/index.php/email_lists/create');
        await page.fill('input[name="name"]', name);
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForURL(/\/email_lists\/edit\/\d+/);
        const match = page.url().match(/\/edit\/(\d+)/);
        const listId = match[1];
        createdListIds.push(listId);
        return listId;
    }

    async function uploadFile(page, listId, localFilePath) {
        await page.click('#import-tab');
        await expect(page.locator('#import')).toBeVisible();
        await page.setInputFiles('#file_upload', localFilePath);
        await page.click('#upload_button');
        await page.waitForURL(new RegExp(`/email_lists/edit/${listId}$`));
        await page.waitForLoadState('networkidle');
    }

    test('preserves name and phone number found on the same line when importing a text file', async ({ page }) => {
        const listName = 'Test Import Phone TXT ' + Date.now();
        const listId = await createList(page, listName);

        const content = [
            'Jean Dupont jean.dupont@example.com 06 12 34 56 78',
            'paul.martin@example.com 07 98 76 54 32',
            'marie@example.com Marie Curie',
            'noinfo@example.com',
        ].join('\n');

        const tmpFile = path.join(os.tmpdir(), `gvv_pw_email_import_${Date.now()}.txt`);
        fs.writeFileSync(tmpFile, content);

        try {
            await uploadFile(page, listId, tmpFile);

            // Success message reports 4 imported addresses
            await expect(page.locator('.alert-success')).toContainText('4 adresses importées');

            // Uploaded file entry shows the right address count
            const fileEntry = page.locator('#uploaded_files_list .list-group-item').first();
            await expect(fileEntry).toContainText('4');

            // Switch to the manual/external tab: each external email should
            // display the informal info captured from its line
            await page.click('#manual-tab');
            const externalList = page.locator('#external_emails_list');
            await expect(externalList).toContainText('jean.dupont@example.com');
            await expect(externalList).toContainText('06 12 34 56 78');
            await expect(externalList).toContainText('paul.martin@example.com');
            await expect(externalList).toContainText('07 98 76 54 32');
            await expect(externalList).toContainText('marie@example.com');
            await expect(externalList).toContainText('Marie Curie');
            await expect(externalList).toContainText('noinfo@example.com');
        } finally {
            fs.rmSync(tmpFile, { force: true });
        }

        // View page: recipients are sorted by name when known, else by
        // email (single unified key) - digits sort before letters, so the
        // phone-only entry (paul.martin) comes first, followed by the
        // named entries (Jean Dupont ..., Marie Curie), then the
        // nameless entry sorted by its raw email address.
        await page.goto(`/index.php/email_lists/view/${listId}`);
        const displayText = await page.locator('#email_list_display').innerText();

        const posPaul = displayText.indexOf('paul.martin@example.com');
        const posJean = displayText.indexOf('jean.dupont@example.com');
        const posMarie = displayText.indexOf('marie@example.com');
        const posNoinfo = displayText.indexOf('noinfo@example.com');

        expect(posPaul).toBeGreaterThanOrEqual(0);
        expect(posJean).toBeGreaterThan(posPaul);
        expect(posMarie).toBeGreaterThan(posJean);
        expect(posNoinfo).toBeGreaterThan(posMarie);

        // The phone number and name are shown alongside the address
        await expect(page.locator('#email_list_display')).toContainText('06 12 34 56 78');
        await expect(page.locator('#email_list_display')).toContainText('07 98 76 54 32');
        await expect(page.locator('#email_list_display')).toContainText('Marie Curie');
    });

    test('preserves phone number from an extra CSV column beyond firstname/lastname', async ({ page }) => {
        const listName = 'Test Import Phone CSV ' + Date.now();
        const listId = await createList(page, listName);

        const content = [
            'Jean,Dupont,jean.dupont@example.com,0612345678',
            'Paul,Martin,paul.martin@example.com,0798765432',
        ].join('\n');

        const tmpFile = path.join(os.tmpdir(), `gvv_pw_email_import_${Date.now()}.csv`);
        fs.writeFileSync(tmpFile, content);

        try {
            await uploadFile(page, listId, tmpFile);

            await expect(page.locator('.alert-success')).toContainText('2 adresses importées');

            await page.click('#manual-tab');
            const externalList = page.locator('#external_emails_list');
            await expect(externalList).toContainText('jean.dupont@example.com');
            await expect(externalList).toContainText('Jean Dupont 0612345678');
            await expect(externalList).toContainText('paul.martin@example.com');
            await expect(externalList).toContainText('Paul Martin 0798765432');
        } finally {
            fs.rmSync(tmpFile, { force: true });
        }
    });
});
