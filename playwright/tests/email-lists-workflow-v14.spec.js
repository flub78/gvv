// Test workflow v1.4: Creation/modification separation
const { test, expect } = require('@playwright/test');

test.describe('Email Lists Workflow v1.4', () => {
    test.beforeEach(async ({ page }) => {
        // Login via dx_auth (use testadmin which has proper test permissions)
        await page.goto('/auth/login');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Close "Message du jour" dialog if it appears (it blocks interactions)
        const modDialog = page.locator('.ui-dialog');
        if (await modDialog.isVisible().catch(() => false)) {
            const closeButton = page.locator('.ui-dialog-buttonpane button:has-text("OK")');
            if (await closeButton.isVisible().catch(() => false)) {
                await closeButton.click();
                await page.waitForTimeout(500);
            }
        }
    });

    test('should show disabled address section in creation mode', async ({ page }) => {
        // Navigate to create page
        await page.goto('/email_lists/create');

        // Check title
        await expect(page.locator('h3')).toContainText('Nouvelle liste');

        // Check that metadata section is visible and editable
        await expect(page.locator('input[name="name"]')).toBeVisible();
        await expect(page.locator('input[name="name"]')).toBeEditable();
        await expect(page.locator('textarea[name="description"]')).toBeVisible();
        await expect(page.locator('select[name="active_member"]')).toBeVisible();
        await expect(page.locator('input[name="visible"]')).toBeVisible();

        // Check that submit buttons are present in metadata section
        await expect(page.locator('button[type="submit"]').first()).toBeVisible();

        // Check that info note about adding addresses after creation is shown
        await expect(page.locator('text=Après avoir créé la liste')).toBeVisible();
        await expect(page.locator('text=Par critères')).toBeVisible();
        await expect(page.locator('text=Sélection manuelle de membres')).toBeVisible();
        await expect(page.locator('text=Import de fichiers')).toBeVisible();
    });

    test('should create list and redirect to edit mode with enabled address section', async ({ page }) => {
        // Navigate to create page
        await page.goto('/email_lists/create');

        // Fill in metadata
        const listName = 'Test List v1.4 ' + Date.now();
        await page.fill('input[name="name"]', listName);
        await page.fill('textarea[name="description"]', 'Test description for workflow v1.4');
        await page.selectOption('select[name="active_member"]', 'active');
        await page.check('input[name="visible"]');

        // Submit form
        await page.click('button[type="submit"], input[type="submit"]');

        // Wait for redirect to edit page
        await page.waitForURL(/\/email_lists\/edit\/\d+/);

        // Check that we're now in edit mode
        await expect(page.locator('h3')).toContainText('Modifier la liste');

        // Check that metadata section still shows the list name
        await expect(page.locator('input[name="name"]')).toHaveValue(listName);

        // Check that address section is now enabled
        const enabledSection = page.locator('#addresses-section');
        await expect(enabledSection).toBeVisible();

        // Check that tabs are now functional
        await expect(page.locator('#criteria-tab')).toBeVisible();
        await expect(page.locator('#manual-tab')).toBeVisible();
        await expect(page.locator('#import-tab')).toBeVisible();

        // Check that preview panel is visible
        await expect(page.locator('text=Liste en construction')).toBeVisible();
        await expect(page.locator('#total_count')).toBeVisible();

        // Success message should be shown
        await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('should allow modifying metadata and addresses in edit mode', async ({ page }) => {
        // First create a list
        await page.goto('/email_lists/create');
        const listName = 'Test Edit v1.4 ' + Date.now();
        await page.fill('input[name="name"]', listName);
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForURL(/\/email_lists\/edit\/\d+/);

        // Now we're in edit mode
        // Modify metadata
        await page.fill('input[name="name"]', listName + ' MODIFIED');
        await page.fill('textarea[name="description"]', 'Modified description');

        // Check that we can interact with address tabs
        await page.click('#criteria-tab');
        await expect(page.locator('#criteria')).toBeVisible();

        await page.click('#manual-tab');
        await expect(page.locator('#manual')).toBeVisible();

        await page.click('#import-tab');
        await expect(page.locator('#import')).toBeVisible();

        // Preview should update (even if empty)
        await expect(page.locator('#total_count')).toHaveText('0');
    });
});
