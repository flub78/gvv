// Debug test for email list creation
const { test, expect } = require('@playwright/test');

test.describe('Email Lists Creation Debug', () => {
    test('should create a list and show any errors', async ({ page }) => {
        // Enable console logging
        page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
        page.on('pageerror', error => console.log('BROWSER ERROR:', error.message));

        // Login
        console.log('1. Logging in...');
        await page.goto('http://gvv.net/auth/login');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('   Logged in successfully');

        // Navigate to create page
        console.log('2. Navigating to create page...');
        await page.goto('http://gvv.net/email_lists/create');
        await page.waitForLoadState('networkidle');

        // Check if we're on the right page
        const title = await page.locator('h3').textContent();
        console.log('   Page title:', title);

        // Fill the form
        console.log('3. Filling form...');
        const listName = 'Test Playwright ' + Date.now();
        await page.fill('input[name="name"]', listName);
        console.log('   Name:', listName);

        await page.fill('textarea[name="description"]', 'Test description from Playwright');
        console.log('   Description filled');

        await page.selectOption('select[name="active_member"]', 'active');
        console.log('   Active member selected');

        await page.check('input[name="visible"]');
        console.log('   Visible checked');

        // Get the form action URL
        const formAction = await page.locator('form').getAttribute('action');
        console.log('   Form action:', formAction);

        // Screenshot before submit
        await page.screenshot({ path: 'test-results/before-submit.png', fullPage: true });
        console.log('   Screenshot saved: before-submit.png');

        // Submit the form
        console.log('4. Submitting form...');
        await page.click('button[type="submit"], input[type="submit"]');

        // Wait for navigation or error
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000); // Give time for any redirects

        // Get current URL
        const currentUrl = page.url();
        console.log('   Current URL after submit:', currentUrl);

        // Check for success message
        const successAlert = await page.locator('.alert-success').count();
        console.log('   Success alerts found:', successAlert);

        // Check for error message
        const errorAlert = await page.locator('.alert-danger').count();
        console.log('   Error alerts found:', errorAlert);

        if (errorAlert > 0) {
            const errorText = await page.locator('.alert-danger').textContent();
            console.log('   ERROR MESSAGE:', errorText);
        }

        if (successAlert > 0) {
            const successText = await page.locator('.alert-success').textContent();
            console.log('   SUCCESS MESSAGE:', successText);
        }

        // Screenshot after submit
        await page.screenshot({ path: 'test-results/after-submit.png', fullPage: true });
        console.log('   Screenshot saved: after-submit.png');

        // Get page content to check for PHP errors
        const bodyText = await page.locator('body').textContent();
        if (bodyText.includes('Fatal error') || bodyText.includes('Warning:') || bodyText.includes('Notice:')) {
            console.log('   PHP ERROR DETECTED IN PAGE!');
            console.log('   Body excerpt:', bodyText.substring(0, 500));
        }

        // Check if we're redirected to edit page (success scenario)
        if (currentUrl.includes('/edit/')) {
            console.log('5. SUCCESS! Redirected to edit page');
            const editTitle = await page.locator('h3').textContent();
            console.log('   Edit page title:', editTitle);

            // Extract list ID from URL
            const matches = currentUrl.match(/\/edit\/(\d+)/);
            if (matches) {
                const listId = matches[1];
                console.log('   Created list ID:', listId);

                // Verify the list name is shown
                const nameValue = await page.locator('input[name="name"]').inputValue();
                console.log('   List name in edit form:', nameValue);

                expect(nameValue).toBe(listName);
            }
        } else {
            console.log('5. PROBLEM: Not redirected to edit page');
            console.log('   Still on URL:', currentUrl);
        }
    });
});
