/**
 * Test for frozen entry button behavior
 *
 * Tests that:
 * 1. When an entry is frozen, the edit button changes to a view button (eye icon)
 * 2. The view button remains active and clickable
 * 3. The delete button is disabled when frozen
 * 4. Clicking the view button opens the form in view mode
 * 5. The form's submit button is disabled in view mode
 */

const { test, expect } = require('@playwright/test');

test.describe('Compta Frozen Entry Buttons', () => {
    test.beforeEach(async ({ page }) => {
        // Login as test admin (see bin/create_test_users.sh)
        await page.goto('http://gvv.net/');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('input[type="submit"]');

        // Wait for redirect after login
        await page.waitForLoadState('networkidle');

        // Navigate to balance des comptes to find an existing account
        await page.goto('http://gvv.net/compta/balance');
        await page.waitForLoadState('networkidle');

        // Click on the first account link to go to its journal
        const firstAccountLink = page.locator('table tbody tr td a[href*="journal_compte"]').first();
        await firstAccountLink.click();
        await page.waitForLoadState('networkidle');
    });

    test('frozen entry shows eye icon button (view mode)', async ({ page }) => {
        // Find the first entry row with a gel checkbox
        const firstGelCheckbox = page.locator('.gel-checkbox').first();
        const ecritureId = await firstGelCheckbox.getAttribute('data-ecriture-id');

        // Ensure it's not frozen initially
        const isChecked = await firstGelCheckbox.isChecked();
        if (isChecked) {
            await firstGelCheckbox.uncheck();
            await page.waitForTimeout(500); // Wait for AJAX
        }

        // Find the edit button for this entry
        const editBtn = page.locator(`.edit-entry-btn[data-ecriture-id="${ecritureId}"]`);

        // Verify initial state - should have edit icon (fa-edit) and primary color
        await expect(editBtn).toHaveClass(/btn-primary/);
        await expect(editBtn.locator('i')).toHaveClass(/fa-edit/);
        await expect(editBtn).toHaveAttribute('title', 'Modifier');

        // Check the gel checkbox to freeze the entry
        await firstGelCheckbox.check();
        await page.waitForTimeout(500); // Wait for AJAX and DOM update

        // Verify the button changed to view mode (same blue color, eye icon)
        await expect(editBtn).toHaveClass(/btn-primary/);
        await expect(editBtn).toHaveClass(/view-mode/);
        await expect(editBtn.locator('i')).toHaveClass(/fa-eye/);
        await expect(editBtn).toHaveAttribute('title', 'Visualiser');
        await expect(editBtn).toHaveAttribute('data-frozen', '1');

        // Verify the button is NOT disabled (should be clickable)
        await expect(editBtn).not.toHaveAttribute('disabled');
        await expect(editBtn).not.toHaveClass(/disabled/);

        // Verify the delete button is disabled
        const deleteBtn = page.locator(`.delete-entry-btn[data-ecriture-id="${ecritureId}"]`);
        await expect(deleteBtn).toHaveClass(/disabled/);
        await expect(deleteBtn).toHaveAttribute('disabled');

        // Uncheck to restore original state
        await firstGelCheckbox.uncheck();
        await page.waitForTimeout(500);
    });

    test('unfreezing entry restores edit button', async ({ page }) => {
        // Find the first entry row with a gel checkbox
        const firstGelCheckbox = page.locator('.gel-checkbox').first();
        const ecritureId = await firstGelCheckbox.getAttribute('data-ecriture-id');

        // Ensure it's frozen first
        const isChecked = await firstGelCheckbox.isChecked();
        if (!isChecked) {
            await firstGelCheckbox.check();
            await page.waitForTimeout(500); // Wait for AJAX
        }

        // Find the edit button for this entry
        const editBtn = page.locator(`.edit-entry-btn[data-ecriture-id="${ecritureId}"]`);

        // Verify it's in view mode (same blue color, eye icon)
        await expect(editBtn).toHaveClass(/btn-primary/);
        await expect(editBtn.locator('i')).toHaveClass(/fa-eye/);

        // Uncheck the gel checkbox to unfreeze
        await firstGelCheckbox.uncheck();
        await page.waitForTimeout(500); // Wait for AJAX and DOM update

        // Verify button changed back to edit mode
        await expect(editBtn).toHaveClass(/btn-primary/);
        await expect(editBtn).not.toHaveClass(/view-mode/);
        await expect(editBtn.locator('i')).toHaveClass(/fa-edit/);
        await expect(editBtn).toHaveAttribute('title', 'Modifier');
        await expect(editBtn).toHaveAttribute('data-frozen', '0');

        // Verify delete button is enabled
        const deleteBtn = page.locator(`.delete-entry-btn[data-ecriture-id="${ecritureId}"]`);
        await expect(deleteBtn).not.toHaveClass(/disabled/);
        await expect(deleteBtn).not.toHaveAttribute('disabled');
    });

    test('view button opens form in view mode with disabled submit', async ({ page }) => {
        // Find the first entry row with a gel checkbox
        const firstGelCheckbox = page.locator('.gel-checkbox').first();
        const ecritureId = await firstGelCheckbox.getAttribute('data-ecriture-id');

        // Ensure it's frozen
        const isChecked = await firstGelCheckbox.isChecked();
        if (!isChecked) {
            await firstGelCheckbox.check();
            await page.waitForTimeout(500); // Wait for AJAX
        }

        // Click the view button (eye icon)
        const viewBtn = page.locator(`.edit-entry-btn[data-ecriture-id="${ecritureId}"]`);
        await viewBtn.click();

        // Wait for the edit page to load
        await page.waitForLoadState('networkidle');

        // Verify we're on the edit page
        await expect(page).toHaveURL(/compta\/edit/);

        // Verify the submit button is disabled (VISUALISATION mode)
        const submitBtn = page.locator('button[type="submit"], input[type="submit"]').first();
        await expect(submitBtn).toBeDisabled();

        // Go back to journal
        await page.goBack();
        await page.waitForLoadState('networkidle');

        // Uncheck to restore original state
        const gelCheckbox = page.locator(`.gel-checkbox[data-ecriture-id="${ecritureId}"]`);
        await gelCheckbox.uncheck();
        await page.waitForTimeout(500);
    });
});
