/**
 * DataTables State Persistence Tests
 *
 * Tests that pagination and search state are persisted across page reloads
 */

const { test, expect } = require('@playwright/test');

const BASE_URL = 'http://gvv.net';
const LOGIN_URL = `${BASE_URL}/auth/login`;
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
        await page.goto(`${BASE_URL}/compta/journal_compte/11`);
        await page.waitForSelector('#journal-table');
        await page.waitForTimeout(2000);

        // Change page length to 50
        const pageLengthSelect = page.locator('select[name="journal-table_length"]');
        await pageLengthSelect.selectOption('50');
        await page.waitForTimeout(1000);

        // Reload page
        await page.reload();
        await page.waitForSelector('#journal-table');
        await page.waitForTimeout(2000);

        // Verify page length is still 50
        const selectedValue = await pageLengthSelect.inputValue();
        expect(selectedValue).toBe('50');
    });

    test('should persist search term across reloads', async ({ page }) => {
        // Navigate to journal page
        await page.goto(`${BASE_URL}/compta/journal_compte/11`);
        await page.waitForSelector('#journal-table');
        await page.waitForTimeout(2000);

        // Enter search term
        const searchInput = page.locator('input[type="search"]');
        await searchInput.fill('2023');
        await page.waitForTimeout(1000);

        // Reload page
        await page.reload();
        await page.waitForSelector('#journal-table');
        await page.waitForTimeout(2000);

        // Verify search term is still there
        const searchValue = await searchInput.inputValue();
        expect(searchValue).toBe('2023');
    });

    test('should persist current page across reloads', async ({ page }) => {
        // Navigate to journal page
        await page.goto(`${BASE_URL}/compta/journal_compte/11`);
        await page.waitForSelector('#journal-table');
        await page.waitForTimeout(2000);

        // Clear any search
        const searchInput = page.locator('input[type="search"]');
        await searchInput.fill('');
        await page.waitForTimeout(1000);

        // Get initial page info
        const initialInfo = await page.locator('.dataTables_info').textContent();

        // Click "Next" button if it exists and is not disabled
        const nextButton = page.locator('.dataTables_paginate .ui-icon-seek-next').first();
        const isNextDisabled = await nextButton.evaluateHandle(el => {
            const parent = el.parentElement;
            return parent && parent.classList.contains('ui-state-disabled');
        });

        if (!await isNextDisabled.jsonValue()) {
            await nextButton.click();
            await page.waitForTimeout(1000);

            // Get page info after clicking next
            const afterNextInfo = await page.locator('.dataTables_info').textContent();

            // Reload page
            await page.reload();
            await page.waitForSelector('#journal-table');
            await page.waitForTimeout(2000);

            // Get page info after reload
            const afterReloadInfo = await page.locator('.dataTables_info').textContent();

            // Verify we're still on the same page (not back to page 1)
            expect(afterReloadInfo).toBe(afterNextInfo);
            expect(afterReloadInfo).not.toBe(initialInfo);
        } else {
            // If there's only one page, skip this test
            test.skip();
        }
    });
});
