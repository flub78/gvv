/**
 * Test for compta/journal_compte search box in pagination mode (> 400 entries)
 *
 * NOTE: These tests require valid authentication credentials.
 * They are currently SKIPPED in automated test runs.
 *
 * To run manually:
 * 1. Update credentials in test.beforeEach
 * 2. Run: npx playwright test compta_journal_search.spec.js
 *
 * Feature documentation: doc/features/journal_compte_search_box.md
 */
const { test, expect } = require('@playwright/test');

test.describe.skip('Compta Journal Compte - Search Box [MANUAL TEST]', () => {
    test.beforeEach(async ({ page }) => {
        // TODO: Update with valid credentials for your environment
        // Login
        await page.goto('/');
        await page.fill('input[name="username"]', 'YOUR_USERNAME');
        await page.fill('input[name="password"]', 'YOUR_PASSWORD');
        await page.click('input[type="submit"]');
        await page.waitForLoadState('networkidle');
    });

    test('should display search box for account with > 400 entries', async ({ page }) => {
        // Navigate to account #23 which has 1937 entries
        await page.goto('/index.php/compta/journal_compte/23');
        await page.waitForLoadState('networkidle');

        // Verify search box is visible
        const searchBox = page.locator('#tableSearch');
        await expect(searchBox).toBeVisible();

        // Verify the label contains "Recherche"
        const searchLabel = page.locator('.dataTables_filter label');
        await expect(searchLabel).toContainText('Recherche');
    });

    test('should filter table rows based on search term', async ({ page }) => {
        // Navigate to account #23 which has 1937 entries
        await page.goto('/index.php/compta/journal_compte/23');
        await page.waitForLoadState('networkidle');

        // Count initial visible rows
        const initialRowCount = await page.locator('.sql_table tbody tr:visible').count();
        console.log(`Initial row count: ${initialRowCount}`);

        // Type a search term in the search box
        const searchBox = page.locator('#tableSearch');
        await searchBox.fill('2024');

        // Wait a bit for the filtering to happen
        await page.waitForTimeout(500);

        // Count filtered rows
        const filteredRowCount = await page.locator('.sql_table tbody tr:visible').count();
        console.log(`Filtered row count: ${filteredRowCount}`);

        // Verify that some rows are hidden (filtered row count should be less than initial)
        expect(filteredRowCount).toBeLessThan(initialRowCount);

        // Verify that visible rows contain the search term
        const visibleRows = await page.locator('.sql_table tbody tr:visible').all();
        for (const row of visibleRows) {
            const rowText = await row.textContent();
            expect(rowText.toLowerCase()).toContain('2024');
        }
    });

    test('should show all rows when search is cleared', async ({ page }) => {
        // Navigate to account #23 which has 1937 entries
        await page.goto('/index.php/compta/journal_compte/23');
        await page.waitForLoadState('networkidle');

        // Count initial visible rows
        const initialRowCount = await page.locator('.sql_table tbody tr:visible').count();
        console.log(`Initial row count: ${initialRowCount}`);

        // Type a search term
        const searchBox = page.locator('#tableSearch');
        await searchBox.fill('2024');
        await page.waitForTimeout(500);

        // Count filtered rows
        const filteredRowCount = await page.locator('.sql_table tbody tr:visible').count();
        console.log(`Filtered row count: ${filteredRowCount}`);
        expect(filteredRowCount).toBeLessThan(initialRowCount);

        // Clear search
        await searchBox.fill('');
        await page.waitForTimeout(500);

        // Count rows after clearing
        const clearedRowCount = await page.locator('.sql_table tbody tr:visible').count();
        console.log(`Cleared row count: ${clearedRowCount}`);

        // Should show all rows again
        expect(clearedRowCount).toBe(initialRowCount);
    });
});
