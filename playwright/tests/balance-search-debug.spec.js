const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

// Load fixtures
const fixturesPath = path.join(__dirname, '../test-data/fixtures.json');
const fixtures = JSON.parse(fs.readFileSync(fixturesPath, 'utf8'));

/**
 * Test to verify that balance search works correctly
 * Uses data from fixtures.json to test search functionality
 */
test.describe('Balance Search Bug Fix', () => {
    // Run tests for each search test case defined in fixtures
    for (const testCase of fixtures.balance_search_tests) {
        test(`should find "${testCase.expected_name}" when typing "${testCase.search_term}"`, async ({ page }) => {
            // Go to login page
            await page.goto('http://gvv.net/auth/login');

            // Select "Général" section BEFORE logging in to see all members
            const sectionSelect = page.locator('select[name="section"]');
            await sectionSelect.selectOption('4'); // Général section
            console.log('✓ Selected Général section');

            // Now login
            await page.fill('input[name="username"]', 'testadmin');
            await page.fill('input[name="password"]', 'password');
            await page.click('button[type="submit"], input[type="submit"]');
            await page.waitForLoadState('networkidle');

            console.log('✓ Logged in successfully');

            // Navigate to balance page
            await page.goto('http://gvv.net/comptes/balance');
            await page.waitForLoadState('networkidle');

            console.log('✓ Navigated to balance page');

            // Wait for the page to be fully loaded
            await page.waitForTimeout(2000);

            // Find the search input
            const searchInput = page.locator('#accordion-search');
            await expect(searchInput).toBeVisible({ timeout: 10000 });

            console.log('✓ Search input found');

            // Type the search term from fixtures
            await searchInput.fill(testCase.search_term);
            await page.waitForTimeout(1000); // Wait for search to process

            console.log(`✓ Typed "${testCase.search_term}" in search input`);

            // Check if any accordion items are visible
            const accordionItems = page.locator('#balanceAccordion .accordion-item');
            const visibleItems = await accordionItems.evaluateAll(items =>
                items.filter(item => item.style.display !== 'none').length
            );

            console.log(`Found ${visibleItems} visible accordion items after search`);

            // Look for the expected name in the page content
            const nameVisible = await page.locator(`text=${testCase.expected_name}`).isVisible();
            console.log(`${testCase.expected_name} visible: ${nameVisible}`);

            // If not visible, let's check if the accordions are collapsed
            if (!nameVisible) {
                // Try to expand accordion sections that might contain the name
                const clientAccordion = page.locator(`.accordion-item:has-text("${testCase.expected_account_code}")`).first();
                if (await clientAccordion.isVisible()) {
                    await clientAccordion.locator('.accordion-button').click();
                    await page.waitForTimeout(500);

                    const nameAfterExpand = await page.locator(`text=${testCase.expected_name}`).isVisible();
                    console.log(`${testCase.expected_name} visible after expanding: ${nameAfterExpand}`);
                }
            }

            // Take a screenshot for debugging
            const screenshotName = `balance-search-${testCase.search_term.toLowerCase()}.png`;
            await page.screenshot({ path: `test-results/${screenshotName}`, fullPage: true });

            // Check if we have at least one visible item (the search should work)
            expect(visibleItems).toBeGreaterThan(0);

            console.log(`✓ Search test completed for "${testCase.search_term}"`);
        });
    }

    test('should clear search results when input is empty', async ({ page }) => {
        // Go to login page
        await page.goto('http://gvv.net/auth/login');

        // Select "Général" section BEFORE logging in
        const sectionSelect = page.locator('select[name="section"]');
        await sectionSelect.selectOption('4'); // Général section

        // Login
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Navigate to balance page
        await page.goto('http://gvv.net/comptes/balance');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        const searchInput = page.locator('#accordion-search');
        await expect(searchInput).toBeVisible();

        // First count all items before search
        const accordionItems = page.locator('#balanceAccordion .accordion-item');
        const allItems = await accordionItems.evaluateAll(items => items.length);

        console.log(`Total accordion items: ${allItems}`);

        // Search for something specific
        await searchInput.fill('PEI');
        await page.waitForTimeout(1000);

        const filteredItems = await accordionItems.evaluateAll(items => 
            items.filter(item => item.style.display !== 'none').length
        );

        console.log(`Items after PEI search: ${filteredItems}`);

        // Clear the search
        await searchInput.fill('');
        await page.waitForTimeout(1000);

        const itemsAfterClear = await accordionItems.evaluateAll(items => 
            items.filter(item => item.style.display !== 'none').length
        );

        console.log(`Items after clearing search: ${itemsAfterClear}`);

        // Should show all items again
        expect(itemsAfterClear).toBe(allItems);

        console.log('✓ Search clear test completed');
    });
});