/**
 * DataTables State Persistence Tests
 *
 * Tests that pagination and search state are persisted across page reloads
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const TEST_USER = {
    username: 'testadmin',
    password: 'password'
};

test.describe('DataTables State Persistence', () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await page.goto(LOGIN_URL);
        await page.fill('input[name="username"]', TEST_USER.username);
        await page.fill('input[name="password"]', TEST_USER.password);
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');
    });

    test('should persist page length across reloads', async ({ page }) => {
        // Navigate to journal page
        await page.goto(`/index.php/compta/journal_compte/23`);
        await page.waitForLoadState('networkidle');
        await page.selectOption('#year_selector', '2025');
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('#journal-table');
        
        // Wait for DataTables to fully initialize
        await page.waitForSelector('.dataTables_info');
        await page.waitForTimeout(2000);

        // Change page length to 50
        const pageLengthSelect = page.locator('select[name="journal-table_length"]');
        await pageLengthSelect.selectOption('50');

        // Wait for AJAX call to complete and state to save
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Reload page
        await page.reload({ waitUntil: 'networkidle' });
        await page.waitForSelector('#journal-table');
        await page.waitForSelector('.dataTables_info');
        await page.waitForTimeout(2000);

        // Verify page length is still 50
        const selectedValue = await page.locator('select[name="journal-table_length"]').inputValue();
        expect(selectedValue).toBe('50');
    });

    test('should persist search term across reloads', async ({ page }) => {
        // Navigate to journal page
        await page.goto(`/index.php/compta/journal_compte/23`);
        await page.waitForLoadState('networkidle');
        await page.selectOption('#year_selector', '2025');
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('#journal-table');
        
        // Wait for DataTables to fully initialize
        await page.waitForSelector('.dataTables_info');
        await page.waitForTimeout(2000);

        // Enter search term - need to trigger the search event properly
        const searchInput = page.locator('.dataTables_filter input');
        await searchInput.fill('2023');
        // Trigger keyup event to ensure DataTables processes the search
        await searchInput.press('Enter');

        // Wait for search to execute and state to save
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Reload page
        await page.reload({ waitUntil: 'networkidle' });
        await page.waitForSelector('#journal-table');
        
        // Wait for DataTables to initialize AND restore state - this is critical
        await page.waitForSelector('.dataTables_info');
        await page.waitForTimeout(3000);  // Give extra time for state restoration

        // Verify search term is still there
        const searchValue = await page.locator('.dataTables_filter input').inputValue();
        expect(searchValue).toBe('2023');
    });

    test('should persist current page across reloads', async ({ page }) => {
        // Navigate to journal page
        await page.goto(`/index.php/compta/journal_compte/23`);
        await page.waitForLoadState('networkidle');
        await page.selectOption('#year_selector', '2025');
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('#journal-table');
        
        // Wait for DataTables to fully initialize
        await page.waitForSelector('.dataTables_info');
        await page.waitForTimeout(2000);

        // Clear any search to ensure we have multiple pages
        const searchInput = page.locator('.dataTables_filter input');
        await searchInput.fill('');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        // Get initial page info
        const initialInfo = await page.locator('.dataTables_info').textContent();

        // Try to find and click the "Next" button - looking for the last pagination link before "Last"
        const nextButton = page.locator('.dataTables_paginate a.fg-button').nth(-2);
        const buttonCount = await nextButton.count();
        
        if (buttonCount > 0) {
            const isDisabled = await nextButton.evaluate(el => el.parentElement.classList.contains('ui-state-disabled'));
            
            if (!isDisabled) {
                await nextButton.click();

                // Wait for page change and state to save
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(2000);

                // Get page info after clicking next
                const afterNextInfo = await page.locator('.dataTables_info').textContent();

                // Reload page
                await page.reload({ waitUntil: 'networkidle' });
                await page.waitForSelector('#journal-table');
                await page.waitForSelector('.dataTables_info');
                await page.waitForTimeout(2000);

                // Get page info after reload
                const afterReloadInfo = await page.locator('.dataTables_info').textContent();

                // Verify we're still on the same page (not back to page 1)
                expect(afterReloadInfo).toBe(afterNextInfo);
                expect(afterReloadInfo).not.toBe(initialInfo);
            } else {
                test.skip();
            }
        } else {
            test.skip();
        }
    });
});
