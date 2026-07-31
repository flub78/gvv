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

    test('should fall back to the first page when the persisted page no longer exists after filtering', async ({ page }) => {
        // vols_planeur uses a server-side DataTable (bServerSide: true). For
        // server-side tables, DataTables 1.9.4 restores the persisted iStart from
        // the state cookie with no bounds check against the current row count
        // (unlike its client-side path, which already clamps). If a server-side
        // filter later shrinks the result set below that offset, the table is left
        // rendering a blank, out-of-range page.
        await page.goto('/index.php/vols_planeur/page');
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.dataTables_info');
        await page.waitForTimeout(1000);

        // Simulate having been left on a deep page: corrupt the DataTables state
        // cookie to a page offset far beyond the actual row count.
        const cookies = await page.context().cookies();
        const dtCookies = cookies.filter((c) => c.name.startsWith('SpryMedia_DataTables_'));
        expect(dtCookies.length).toBeGreaterThan(0);
        for (const c of dtCookies) {
            const state = JSON.parse(decodeURIComponent(c.value));
            state.iStart = 999999;
            await page.context().addCookies([{ ...c, value: encodeURIComponent(JSON.stringify(state)) }]);
        }

        await page.reload({ waitUntil: 'networkidle' });
        await page.waitForSelector('.dataTables_info');
        // The out-of-range page triggers a corrective second AJAX request; give it
        // time to complete.
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        // The table must show its first page of results, not a blank one.
        const rowCount = await page.locator('table.datatable_server tbody tr').count();
        expect(rowCount).toBeGreaterThan(0);

        const infoText = await page.locator('.dataTables_info').first().textContent();
        expect(infoText).toMatch(/l'élement 1 à|1 to/);
    });

    test('should not leak a search term from one datatable page to another', async ({ page }) => {
        // bs_footer.php's ".datatable" DataTables init (used by planeur, avion,
        // and ~22 other listing pages) used to save its state to a localStorage
        // key keyed only on the auto-generated table id (e.g. "DataTables_Table_0").
        // That id is not unique across pages, so a search typed on one listing
        // page reappeared - and silently filtered rows - on unrelated pages.
        await page.goto('/index.php/planeur/page');
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.dataTables_info');
        await page.waitForTimeout(1000);

        const searchInput = page.locator('.dataTables_filter input').first();
        await searchInput.fill('zzz_leak_test');
        await searchInput.press('Enter');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        // Navigate to a different listing page that also uses the plain
        // ".datatable" class.
        await page.goto('/index.php/avion/page');
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.dataTables_info');
        await page.waitForTimeout(1000);

        const avionSearchValue = await page.locator('.dataTables_filter input').first().inputValue();
        expect(avionSearchValue).toBe('');

        // Going back to the original page must still show the persisted term.
        await page.goto('/index.php/planeur/page');
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('.dataTables_info');
        await page.waitForTimeout(1000);

        const planeurSearchValue = await page.locator('.dataTables_filter input').first().inputValue();
        expect(planeurSearchValue).toBe('zzz_leak_test');
    });
});
