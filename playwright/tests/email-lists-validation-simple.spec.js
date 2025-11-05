const { test, expect } = require('@playwright/test');

/**
 * Simple test to verify validation errors display
 */
test.describe('Email Lists - Simple Validation Test', () => {
    test('should display validation error when name is too long', async ({ page }) => {
        // Login
        await page.goto('http://localhost/auth/login');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Go to create page
        await page.goto('http://localhost/email_lists/create');
        await page.waitForLoadState('networkidle');

        // Take a screenshot to see the page
        await page.screenshot({ path: 'test-results/before-submit.png', fullPage: true });

        // Fill with a very long name (bypass HTML5 validation)
        const longName = 'A'.repeat(300);
        await page.evaluate((name) => {
            const input = document.querySelector('input[name="name"]');
            if (input) {
                input.removeAttribute('required');
                input.removeAttribute('maxlength');
                input.value = name;
            }
        }, longName);

        await page.selectOption('select[name="active_member"]', 'active');

        // Submit
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Take screenshot after submit
        await page.screenshot({ path: 'test-results/after-submit.png', fullPage: true });

        // Check if we have validation errors
        const hasAlert = await page.locator('.alert-danger').count();
        const hasInvalidFeedback = await page.locator('.invalid-feedback').count();

        console.log(`Alert count: ${hasAlert}`);
        console.log(`Invalid feedback count: ${hasInvalidFeedback}`);

        // At least one should be present
        expect(hasAlert + hasInvalidFeedback).toBeGreaterThan(0);
    });
});
