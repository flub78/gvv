const { test, expect } = require('@playwright/test');

/**
 * Test to verify that 411 (client) accounts require a pilote association
 * and that duplicates are not allowed in the same section
 */
test.describe('Compte 411 validation rules', () => {

    /**
     * Helper function to switch section via the section selector
     */
    async function switchSection(page, sectionId) {
        // The section selector is in the menu, uses POST via form or AJAX
        // We'll use evaluate to submit a form with the section change
        await page.evaluate(async (sectionId) => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/index.php/user_roles_per_section/set_section';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'section';
            input.value = sectionId;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }, sectionId);
        await page.waitForLoadState('networkidle');
    }

    /**
     * Helper function to find a pilote without a 411 account in current section
     */
    async function findPiloteWithout411(page) {
        const piloteSelect = page.locator('select[name="pilote"]');
        const options = await piloteSelect.locator('option').all();

        for (const option of options) {
            const value = await option.getAttribute('value');
            if (value && value !== '') {
                return value;
            }
        }
        return null;
    }

    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('/index.php/auth/login');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');
    });

    test('should reject creation of 411 account without pilote', async ({ page }) => {
        // Navigate to account creation page
        await page.goto('/index.php/comptes/create');
        await page.waitForLoadState('networkidle');

        // Fill in account name
        await page.fill('input[name="nom"]', 'Test Client Sans Pilote');

        // Select codec 411 (Clients)
        const codecSelect = page.locator('select[name="codec"]');
        await codecSelect.selectOption('411');

        // Wait for any JS to update the form
        await page.waitForTimeout(500);

        // Make sure pilote field is visible and empty (or select empty option)
        const piloteSelect = page.locator('select[name="pilote"]');

        // Check if the pilote select is visible
        const isVisible = await piloteSelect.isVisible();
        if (isVisible) {
            // Try to select empty option or leave empty
            try {
                await piloteSelect.selectOption('');
            } catch (e) {
                // If no empty option, just leave it as is
                console.log('Could not select empty pilote option');
            }
        }

        // Submit the form
        await page.click('input[type="submit"], button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Check we're still on the form page (validation failed)
        const isStillOnForm = await page.locator('input[name="nom"]').isVisible();

        expect(isStillOnForm, 'Expected to stay on form due to validation error').toBeTruthy();

        // Verify that an error message about pilote/membre is displayed
        const errorMessages = await page.locator('.text-danger, .alert-danger, .error, .invalid-feedback, p').allTextContents();
        const errorText = errorMessages.join(' ').toLowerCase();

        expect(errorText,
            'Expected error message mentioning pilote or membre requirement').toMatch(/pilote|membre|client|associé/i);
    });

    test('should reject duplicate 411 account for same member in same section', async ({ page }) => {
        // testuser already has a 411 account in section Planeur (id=1)
        // Make sure we're in section Planeur
        await switchSection(page, '1');

        // Navigate to account creation page
        await page.goto('/index.php/comptes/create');
        await page.waitForLoadState('networkidle');

        // Fill in account name
        await page.fill('input[name="nom"]', 'Duplicate Test Account');

        // Select codec 411 (Clients)
        const codecSelect = page.locator('select[name="codec"]');
        await codecSelect.selectOption('411');

        // Wait for pilote field to become visible
        await page.waitForTimeout(500);

        // Select testuser who already has a 411 account in section Planeur
        const piloteSelect = page.locator('select[name="pilote"]');
        await expect(piloteSelect).toBeVisible();
        await piloteSelect.selectOption('testuser');

        // Submit the form
        await page.click('input[type="submit"], button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Check we're still on the form page (validation failed)
        const isStillOnForm = await page.locator('input[name="nom"]').isVisible();

        expect(isStillOnForm, 'Expected to stay on form due to duplicate validation error').toBeTruthy();

        // Verify that an error message about duplicate is displayed
        const errorMessages = await page.locator('.text-danger, .alert-danger, .error, .invalid-feedback, p').allTextContents();
        const errorText = errorMessages.join(' ').toLowerCase();

        expect(errorText,
            'Expected error message mentioning duplicate or existing account').toMatch(/existe|déjà|duplicate|already|section/i);
    });

    test('should allow 411 account for same member in different section', async ({ page }) => {
        // testuser has a 411 account in section Planeur (id=1)
        // Creating one in section ULM (id=2) should succeed

        // First, switch to section ULM
        await switchSection(page, '2');

        // Navigate to account creation page
        await page.goto('/index.php/comptes/create');
        await page.waitForLoadState('networkidle');

        // Generate unique account name
        const uniqueName = `Test ULM Account ${Date.now()}`;

        // Fill in account name
        await page.fill('input[name="nom"]', uniqueName);

        // Select codec 411 (Clients)
        const codecSelect = page.locator('select[name="codec"]');
        await codecSelect.selectOption('411');

        // Wait for pilote field to become visible
        await page.waitForTimeout(500);

        // Select testuser - should be allowed because we're in a different section
        const piloteSelect = page.locator('select[name="pilote"]');
        await expect(piloteSelect).toBeVisible();
        await piloteSelect.selectOption('testuser');

        // Submit the form
        await page.click('input[type="submit"], button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Should be redirected to balance page (successful creation)
        const currentUrl = page.url();

        // Check if we're NOT on the form anymore (success) or no duplicate error
        const isOnFormPage = currentUrl.includes('/create') || currentUrl.includes('/formValidation');

        if (isOnFormPage) {
            // If still on form, check that there's no error about duplicate
            const errorMessages = await page.locator('.text-danger, .alert-danger, .error').allTextContents();
            const errorText = errorMessages.join(' ').toLowerCase();
            // Should not have duplicate error - other errors are OK
            expect(errorText, 'Should not have duplicate error in different section').not.toMatch(/existe.*déjà.*section|already.*exists.*section/i);
        } else {
            console.log('Account creation in different section succeeded');
        }
    });
});
