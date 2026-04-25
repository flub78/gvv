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

// Use a fixed section ID so tests are deterministic regardless of table ordering.
const TEST_SECTION_ID = 1;

async function login(page) {
    await page.goto('/index.php/auth/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', 'testadmin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Sections ordre affichage', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('should display ordre_affichage field in sections list', async ({ page }) => {
        await page.goto('/index.php/sections/page');
        const headers = await page.locator('table thead th').allTextContents();
        expect(headers).toContain('Ordre');
    });

    test('should be able to edit a section and set ordre_affichage', async ({ page }) => {
        // Navigate directly to the edit form of a known section.
        await page.goto(`/index.php/sections/edit/${TEST_SECTION_ID}`);
        await page.waitForLoadState('networkidle');

        const ordreField = page.locator('input[name="ordre_affichage"]');
        await expect(ordreField).toBeVisible();

        // Remember the original value so we can restore it afterwards.
        const originalValue = await ordreField.inputValue();

        // Change the value.
        await ordreField.fill('10');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Restore the original value.
        await page.goto(`/index.php/sections/edit/${TEST_SECTION_ID}`);
        await page.waitForLoadState('networkidle');
        await page.locator('input[name="ordre_affichage"]').fill(originalValue);
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');
    });

    test('should create a new section with ordre_affichage', async ({ page }) => {
        await page.goto('/index.php/sections/page');
        await page.click('a[href*="sections/create"]');
        await page.waitForSelector('form');

        await page.fill('input[name="nom"]', 'Section Test Ordre');
        await page.fill('input[name="description"]', 'Test description');
        await page.fill('input[name="acronyme"]', 'STO');

        const ordreField = page.locator('input[name="ordre_affichage"]');
        await expect(ordreField).toBeVisible();
        await ordreField.fill('99');

        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForURL('**/sections/page');

        const sections = await page.locator('table tbody tr td').allTextContents();
        expect(sections.some(text => text.includes('Section Test Ordre'))).toBeTruthy();

        // Clean up: delete the test section immediately after creation.
        const testRow = page.locator('table tbody tr').filter({ hasText: 'Section Test Ordre' });
        page.on('dialog', dialog => dialog.accept());
        await testRow.locator('a[href*="delete"]').click();
        await page.waitForTimeout(1000);
    });
});
