/**
 * @fileoverview Playwright tests for sections display order (ordre_affichage) functionality
 * @description Tests the display order field in the sections management interface.
 *              Validates that the ordre_affichage field is properly displayed in the sections list,
 *              can be edited for existing sections, and is available when creating new sections.
 * 
 * @module tests/sections_ordre_affichage.spec
 * @requires @playwright/test
 * 
 * @author GVV Development Team
 * @version 1.0.0
 * @since 2024
 */

/**
 * Test suite for sections display order functionality
 * @test {Sections} ordre_affichage field management
 */

/**
 * Setup hook - runs before each test
 * Authenticates as admin user and handles section selection if required
 * @async
 * @param {Object} page - Playwright page object
 * @returns {Promise<void>}
 */

/**
 * Test: Verify ordre_affichage column is visible in sections list
 * @test {Table} should display ordre_affichage field in sections list
 * @async
 * @param {Object} page - Playwright page object
 * @returns {Promise<void>}
 */

/**
 * Test: Edit a section and set the ordre_affichage value
 * @test {Form} should be able to edit a section and set ordre_affichage
 * @async
 * @param {Object} page - Playwright page object
 * @returns {Promise<void>}
 */

/**
 * Test: Create a new section with ordre_affichage field
 * @test {Form} should create a new section with ordre_affichage
 * @async
 * @param {Object} page - Playwright page object
 * @returns {Promise<void>}
 */
const { test, expect } = require('@playwright/test');

test.describe('Sections ordre affichage', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('/index.php/auth/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Select section if needed
        const currentUrl = page.url();
        if (currentUrl.includes('select_section')) {
            const firstSection = page.locator('table tbody tr').first();
            await firstSection.locator('a').first().click();
            await page.waitForLoadState('networkidle');
        }
    });

    test('should display ordre_affichage field in sections list', async ({ page }) => {
        // Navigate to sections list
        await page.goto('/index.php/sections/page');

        // Check that the table has the ordre_affichage column
        const headers = await page.locator('table thead th').allTextContents();
        expect(headers).toContain('Ordre');
    });

    test('should be able to edit a section and set ordre_affichage', async ({ page }) => {
        // Navigate to sections list
        await page.goto('/index.php/sections/page');

        // Click on first edit button
        const firstEditButton = page.locator('table tbody tr').first().locator('a[href*="edit"]');
        await firstEditButton.click();

        // Wait for form to load
        await page.waitForSelector('form');

        // Check that ordre_affichage field exists
        const ordreField = page.locator('input[name="ordre_affichage"]');
        await expect(ordreField).toBeVisible();

        // Fill in ordre_affichage
        await ordreField.fill('10');

        // Submit form
        await page.click('button[type="submit"], input[type="submit"]');

        // Wait for success message or redirect
        await page.waitForURL('**/sections/page');
    });

    test('should create a new section with ordre_affichage', async ({ page }) => {
        // Navigate to sections list
        await page.goto('/index.php/sections/page');

        // Click create button
        await page.click('a[href*="sections/create"]');

        // Wait for form to load
        await page.waitForSelector('form');

        // Fill in form
        await page.fill('input[name="nom"]', 'Section Test Ordre');
        await page.fill('input[name="description"]', 'Test description');
        await page.fill('input[name="acronyme"]', 'STO');

        // Check that ordre_affichage field exists
        const ordreField = page.locator('input[name="ordre_affichage"]');
        await expect(ordreField).toBeVisible();

        // Fill in ordre_affichage
        await ordreField.fill('99');

        // Submit form
        await page.click('button[type="submit"], input[type="submit"]');

        // Wait for redirect to list
        await page.waitForURL('**/sections/page');

        // Verify the section was created
        const sections = await page.locator('table tbody tr td').allTextContents();
        expect(sections.some(text => text.includes('Section Test Ordre'))).toBeTruthy();

        // Clean up: delete the test section
        const testRow = page.locator('table tbody tr').filter({ hasText: 'Section Test Ordre' });
        const deleteButton = testRow.locator('a[href*="delete"]');

        // Handle the confirmation dialog
        page.on('dialog', dialog => dialog.accept());
        await deleteButton.click();

        // Wait for deletion
        await page.waitForTimeout(1000);
    });
});
