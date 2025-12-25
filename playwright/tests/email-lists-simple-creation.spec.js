const { test, expect } = require('@playwright/test');

/**
 * Test simplified email list creation workflow (v1.4)
 * Tests the simple form without JavaScript complexity
 */
test.describe('Email Lists - Simple Creation Workflow', () => {
    test.beforeEach(async ({ page }) => {
        // Login as testadmin
        await page.goto('http://localhost/auth/login');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');

        // Wait for login to complete
        await page.waitForLoadState('networkidle');
    });

    test('should create new email list with simple form', async ({ page }) => {
        // Navigate to create page
        await page.goto('http://localhost/email_lists/create');

        // Verify we're on the create page
        await expect(page.locator('h3')).toContainText('Nouvelle liste');

        // Fill in the form
        const timestamp = Date.now();
        await page.fill('input[name="name"]', `Test Liste ${timestamp}`);
        await page.fill('textarea[name="description"]', 'Description de test pour la liste simplifiée');
        await page.selectOption('select[name="active_member"]', 'active');
        await page.check('input[name="visible"]');

        // Submit the form
        await page.click('button[type="submit"]');

        // Wait for redirect to edit page
        await page.waitForURL(/email_lists\/edit\/\d+/);

        // Verify we're now in edit mode
        await expect(page.locator('h3')).toContainText('Modifier la liste');

        // Verify success message
        await expect(page.locator('.alert-success')).toBeVisible();

        // Verify the list name appears
        await expect(page.locator('input[name="name"]')).toHaveValue(`Test Liste ${timestamp}`);

        console.log('✓ Email list created successfully via simple form');
    });

    test('should show validation errors for empty name', async ({ page }) => {
        // Navigate to create page
        await page.goto('http://localhost/email_lists/create');

        // Try to submit without filling name (required field)
        // Note: HTML5 validation will prevent submission, so we need to check this
        const nameInput = page.locator('input[name="name"]');

        // Verify the field has required attribute
        await expect(nameInput).toHaveAttribute('required', '');

        console.log('✓ Name field has required validation');
    });

    test('should cancel creation and return to list', async ({ page }) => {
        // Navigate to create page
        await page.goto('http://localhost/email_lists/create');

        // Click cancel button
        await page.click('a.btn-secondary');

        // Verify we're back at the index page
        await expect(page).toHaveURL(/email_lists$/);

        console.log('✓ Cancel button works correctly');
    });
});
