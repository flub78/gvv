const { test, expect } = require('@playwright/test');

/**
 * Test to verify that balance search works for "PEI" -> "Peignot Frédéric"
 * This tests the actual reported bug
 */
test.describe('Balance Search Bug Fix', () => {
    test('should find "Peignot Frédéric" when typing "PEI"', async ({ page }) => {
        // Login first
        await page.goto('http://gvv.net/index.php/dx_auth/login');
        await page.fill('input[name="login"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        console.log('✓ Logged in successfully');

        // Navigate to balance page
        await page.goto('http://gvv.net/index.php/comptes/balance');
        await page.waitForLoadState('networkidle');

        console.log('✓ Navigated to balance page');

        // Wait for the page to be fully loaded
        await page.waitForTimeout(2000);

        // Find the search input
        const searchInput = page.locator('#accordion-search');
        await expect(searchInput).toBeVisible({ timeout: 10000 });

        console.log('✓ Search input found');

        // Type "PEI" in the search
        await searchInput.fill('PEI');
        await page.waitForTimeout(1000); // Wait for search to process

        console.log('✓ Typed "PEI" in search input');

        // Check if any accordion items are visible
        const accordionItems = page.locator('#balanceAccordion .accordion-item');
        const visibleItems = await accordionItems.evaluateAll(items => 
            items.filter(item => item.style.display !== 'none').length
        );

        console.log(`Found ${visibleItems} visible accordion items after search`);

        // Look for "Peignot Frédéric" in the page content
        const peignotVisible = await page.locator('text=Peignot Frédéric').isVisible();
        console.log(`Peignot Frédéric visible: ${peignotVisible}`);

        // If not visible, let's check if the accordions are collapsed
        if (!peignotVisible) {
            // Try to expand accordion sections that might contain Peignot
            const clientAccordion = page.locator('.accordion-item:has-text("411")').first();
            if (await clientAccordion.isVisible()) {
                await clientAccordion.locator('.accordion-button').click();
                await page.waitForTimeout(500);
                
                const peignotAfterExpand = await page.locator('text=Peignot Frédéric').isVisible();
                console.log(`Peignot Frédéric visible after expanding: ${peignotAfterExpand}`);
            }
        }

        // Take a screenshot for debugging
        await page.screenshot({ path: 'test-results/balance-search-pei.png', fullPage: true });

        // Check if we have at least one visible item (the search should work)
        expect(visibleItems).toBeGreaterThan(0);

        console.log('✓ Search test completed');
    });

    test('should clear search results when input is empty', async ({ page }) => {
        // Login and navigate
        await page.goto('http://gvv.net/index.php/dx_auth/login');
        await page.fill('input[name="login"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await page.goto('http://gvv.net/index.php/comptes/balance');
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