// Test workflow v1.4: Creation/modification separation
const { test, expect } = require('@playwright/test');

test.describe('Email Lists Workflow v1.4', () => {
    test.beforeEach(async ({ page }) => {
        // Login via dx_auth
        await page.goto('http://gvv.net/auth/login');
        await page.fill('input[name="username"]', 'admin');
        await page.fill('input[name="password"]', 'gvvadmin');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');
    });

    test('should show disabled address section in creation mode', async ({ page }) => {
        // Navigate to create page
        await page.goto('http://gvv.net/email_lists/create');

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

        // Check that address section header is visible but in disabled state
        await expect(page.locator('text=Ajout et suppression')).toBeVisible();

        // Check that info message is shown
        await expect(page.locator('text=Veuillez d\'abord enregistrer')).toBeVisible();

        // Check that tabs are present but in disabled preview
        const disabledSection = page.locator('#addresses-section-disabled');
        await expect(disabledSection).toBeVisible();
    });

    test('should create list and redirect to edit mode with enabled address section', async ({ page }) => {
        // Navigate to create page
        await page.goto('http://gvv.net/email_lists/create');

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
        await page.goto('http://gvv.net/email_lists/create');
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
