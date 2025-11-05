const { test, expect } = require('@playwright/test');

/**
 * Test validation errors for email list creation
 * Verifies that CodeIgniter validation errors are displayed properly
 */
test.describe('Email Lists - Validation Errors', () => {
    test.beforeEach(async ({ page }) => {
        // Login as testadmin
        await page.goto('http://localhost/auth/login');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');

        // Wait for login to complete
        await page.waitForLoadState('networkidle');

        // Navigate to create page
        await page.goto('http://localhost/email_lists/create');
    });

    test('should show field-specific validation error for name too long', async ({ page }) => {
        // Fill name with more than 255 characters
        const longName = 'A'.repeat(300);

        // Remove HTML5 required attribute to test server-side validation
        await page.evaluate(() => {
            document.querySelector('input[name="name"]').removeAttribute('required');
            document.querySelector('input[name="name"]').removeAttribute('maxlength');
            document.querySelector('select[name="active_member"]').removeAttribute('required');
        });

        await page.fill('input[name="name"]', longName);
        await page.selectOption('select[name="active_member"]', 'active');

        // Submit the form
        await page.click('button[type="submit"]');

        // Wait for page to reload with validation errors
        await page.waitForLoadState('networkidle');

        // Check that we're still on the create page (validation failed)
        await expect(page).toHaveURL(/email_lists\/create$/);

        // Check for validation error near the name field
        const nameFieldContainer = page.locator('.row:has(input[name="name"])');
        await expect(nameFieldContainer.locator('.invalid-feedback')).toBeVisible();

        console.log('✓ Field-specific validation error displayed for name field');
    });

    test('should show validation error for description too long', async ({ page }) => {
        // Fill description with more than 1000 characters
        const longDescription = 'B'.repeat(1100);

        // Remove HTML5 maxlength to test server-side validation
        await page.evaluate(() => {
            document.querySelector('textarea[name="description"]').removeAttribute('maxlength');
        });

        await page.fill('input[name="name"]', 'Test List');
        await page.fill('textarea[name="description"]', longDescription);
        await page.selectOption('select[name="active_member"]', 'active');

        // Submit the form
        await page.click('button[type="submit"]');

        // Wait for page to reload with validation errors
        await page.waitForLoadState('networkidle');

        // Check that we're still on the create page
        await expect(page).toHaveURL(/email_lists\/create$/);

        // Check for validation error near the description field
        const descFieldContainer = page.locator('.row:has(textarea[name="description"])');
        await expect(descFieldContainer.locator('.invalid-feedback')).toBeVisible();

        console.log('✓ Field-specific validation error displayed for description field');
    });

    test('should show validation error for invalid active_member value', async ({ page }) => {
        await page.fill('input[name="name"]', 'Test List');

        // Try to submit with invalid active_member value via JavaScript
        await page.evaluate(() => {
            // Add an invalid option
            const select = document.querySelector('select[name="active_member"]');
            const invalidOption = document.createElement('option');
            invalidOption.value = 'invalid_value';
            invalidOption.text = 'Invalid';
            invalidOption.selected = true;
            select.appendChild(invalidOption);
        });

        // Submit the form
        await page.click('button[type="submit"]');

        // Wait for page to reload with validation errors
        await page.waitForLoadState('networkidle');

        // Check that we're still on the create page
        await expect(page).toHaveURL(/email_lists\/create$/);

        // Check for validation error
        const activeMemFieldContainer = page.locator('.row:has(select[name="active_member"])');
        await expect(activeMemFieldContainer.locator('.invalid-feedback')).toBeVisible();

        console.log('✓ Field-specific validation error displayed for active_member field');
    });

    test('should preserve form values after validation error', async ({ page }) => {
        const testName = 'Test List ' + Date.now();
        const testDesc = 'Test description';

        // Remove required/maxlength to trigger server validation
        await page.evaluate(() => {
            document.querySelector('input[name="name"]').removeAttribute('maxlength');
        });

        // Fill with invalid data (name too long)
        await page.fill('input[name="name"]', testName.repeat(20));
        await page.fill('textarea[name="description"]', testDesc);
        await page.selectOption('select[name="active_member"]', 'inactive');

        // Submit the form
        await page.click('button[type="submit"]');

        // Wait for page to reload
        await page.waitForLoadState('networkidle');

        // Verify that description and active_member are preserved
        await expect(page.locator('textarea[name="description"]')).toHaveValue(testDesc);
        await expect(page.locator('select[name="active_member"]')).toHaveValue('inactive');

        console.log('✓ Form values preserved after validation error');
    });
});
