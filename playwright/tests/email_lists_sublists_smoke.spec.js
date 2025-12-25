// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Email Lists - Sublists Smoke Test', () => {
    test.beforeEach(async ({ page }) => {
        // Login as testadmin
        await page.goto('/auth/login');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');

        // Wait for redirect after login
        await page.waitForLoadState('networkidle');
    });

    test('can access sublists tab in edit mode', async ({ page }) => {
        // Navigate to email lists
        await page.goto('/email_lists');

        // Click first edit button
        const editButton = page.locator('a[href*="/email_lists/edit/"]').first();
        await expect(editButton).toBeVisible();
        await editButton.click();

        // Wait for edit page to load
        await page.waitForLoadState('networkidle');

        // Verify sublists tab exists
        const sublistsTab = page.locator('#sublists-tab');
        await expect(sublistsTab).toBeVisible();

        // Click sublists tab
        await sublistsTab.click();

        // Verify tab content loaded
        const tabContent = page.locator('#sublists');
        await expect(tabContent).toBeVisible();

        // Verify either sublists content or "save first" message is shown
        const hasContent = await page.locator('.sublists-tab-container').isVisible().catch(() => false);
        const hasSaveMessage = await page.locator('text=/save first/i').isVisible().catch(() => false);
        
        expect(hasContent || hasSaveMessage).toBeTruthy();
    });

    test('sublists tab shows appropriate message in creation mode', async ({ page }) => {
        // Navigate to create new list
        await page.goto('/email_lists/create');

        // Wait for page load
        await page.waitForLoadState('networkidle');

        // Verify sublists tab exists
        const sublistsTab = page.locator('#sublists-tab');
        
        // If tab exists, click it and verify message
        if (await sublistsTab.isVisible()) {
            await sublistsTab.click();
            
            // Should show "save first" message
            const saveMessage = page.locator('text=/save.*first/i');
            await expect(saveMessage).toBeVisible();
        }
    });

    test('can see current sublists and available lists', async ({ page }) => {
        // Navigate to email lists
        await page.goto('/email_lists');

        // Click first edit button
        await page.locator('a[href*="/email_lists/edit/"]').first().click();
        await page.waitForLoadState('networkidle');

        // Click sublists tab
        await page.click('#sublists-tab');

        // Wait for tab content
        await page.waitForSelector('#sublists', { state: 'visible' });

        // Check if we see either current sublists section or "no sublists" message
        const hasCurrentSection = await page.locator('.current-sublists').isVisible().catch(() => false);
        const hasNoSublistsMsg = await page.locator('text=/no sublists/i').isVisible().catch(() => false);
        
        // One of these should be visible
        expect(hasCurrentSection || hasNoSublistsMsg).toBeTruthy();

        // Check if we see available sublists section
        const hasAvailableSection = await page.locator('.available-sublists').isVisible().catch(() => false);
        const hasNoAvailableMsg = await page.locator('text=/no.*available/i').isVisible().catch(() => false);
        
        // One of these should be visible
        expect(hasAvailableSection || hasNoAvailableMsg).toBeTruthy();
    });
});
