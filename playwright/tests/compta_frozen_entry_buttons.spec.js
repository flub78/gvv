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
    // Configure tests to run serially to avoid database race conditions
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ page }) => {
        // Login as test admin (see bin/create_test_users.sh)
        await page.goto('/');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('input[type="submit"]');

        // Wait for redirect after login
        await page.waitForLoadState('networkidle');

        // Navigate directly to account 23 which has entries to test with
        // (account 102 typically has no entries until end of year)
        await page.goto('/index.php/compta/journal_compte/23');
        await page.waitForLoadState('networkidle');
    });

    test.afterEach(async ({ page }) => {
        // Ensure all entries are unfrozen to restore clean state
        if (!page.isClosed()) {
            try {
                // Navigate back to the journal if we navigated away
                if (!page.url().includes('/compta/journal_compte/23')) {
                    await page.goto('/index.php/compta/journal_compte/23');
                    await page.waitForLoadState('networkidle');
                }

                // Uncheck all frozen entries to restore clean state
                // Get fresh count each iteration since DOM may change
                let checkedCount = await page.locator('.gel-checkbox:checked').count();
                while (checkedCount > 0) {
                    const checkbox = page.locator('.gel-checkbox:checked').first();
                    try {
                        // Wait for AJAX response
                        const responsePromise = page.waitForResponse(
                            response => response.url().includes('/compta/') && response.status() === 200,
                            { timeout: 3000 }
                        );
                        await checkbox.uncheck({ timeout: 3000 });
                        await responsePromise.catch(() => {});
                    } catch (e) {
                        // Skip this checkbox if it fails
                        break;
                    }
                    // Refresh count
                    checkedCount = await page.locator('.gel-checkbox:checked').count();
                }

                // Logout and clear session
                await page.goto('/index.php/auth/logout');
                await page.context().clearCookies();
            } catch (error) {
                // Ignore cleanup errors - just try to logout
                try {
                    await page.goto('/index.php/auth/logout');
                } catch (e) {
                    // Final cleanup failed, ignore
                }
            }
        }
    });

    test('frozen entry shows eye icon button (view mode)', async ({ page }) => {
        // Find the first entry row with a gel checkbox
        const firstGelCheckbox = page.locator('.gel-checkbox').first();
        const ecritureId = await firstGelCheckbox.getAttribute('data-ecriture-id');

        // Ensure it's not frozen initially
        const isChecked = await firstGelCheckbox.isChecked();
        if (isChecked) {
            // Wait for AJAX response when unchecking
            const responsePromise = page.waitForResponse(
                response => response.url().includes('/compta/') && response.status() === 200,
                { timeout: 5000 }
            );
            await firstGelCheckbox.uncheck();
            await responsePromise.catch(() => {});
            await page.waitForLoadState('domcontentloaded');
        }

        // Find the edit button for this entry
        const editBtn = page.locator(`.edit-entry-btn[data-ecriture-id="${ecritureId}"]`);

        // Verify initial state - should have edit icon (fa-edit) and primary color
        await expect(editBtn).toHaveClass(/btn-primary/);
        await expect(editBtn.locator('i')).toHaveClass(/fa-edit/);
        await expect(editBtn).toHaveAttribute('title', 'Modifier');

        // Check the gel checkbox to freeze the entry
        // Wait for AJAX response
        const responsePromise = page.waitForResponse(
            response => response.url().includes('/compta/') && response.status() === 200,
            { timeout: 5000 }
        );
        await firstGelCheckbox.check();
        await responsePromise.catch(() => {});
        await page.waitForLoadState('domcontentloaded');

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

        // State will be cleaned up in afterEach
    });

    test('unfreezing entry restores edit button', async ({ page }) => {
        // Find the first entry row with a gel checkbox
        const firstGelCheckbox = page.locator('.gel-checkbox').first();
        const ecritureId = await firstGelCheckbox.getAttribute('data-ecriture-id');

        // Ensure it's frozen first
        const isChecked = await firstGelCheckbox.isChecked();
        if (!isChecked) {
            // Wait for AJAX response when checking
            const responsePromise = page.waitForResponse(
                response => response.url().includes('/compta/') && response.status() === 200,
                { timeout: 5000 }
            );
            await firstGelCheckbox.check();
            await responsePromise.catch(() => {});
            await page.waitForLoadState('domcontentloaded');
        }

        // Find the edit button for this entry
        const editBtn = page.locator(`.edit-entry-btn[data-ecriture-id="${ecritureId}"]`);

        // Verify it's in view mode (same blue color, eye icon)
        await expect(editBtn).toHaveClass(/btn-primary/);
        await expect(editBtn.locator('i')).toHaveClass(/fa-eye/);

        // Uncheck the gel checkbox to unfreeze
        // Wait for AJAX response
        const responsePromise = page.waitForResponse(
            response => response.url().includes('/compta/') && response.status() === 200,
            { timeout: 5000 }
        );
        await firstGelCheckbox.uncheck();
        await responsePromise.catch(() => {});
        await page.waitForLoadState('domcontentloaded');

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

        // State will be cleaned up in afterEach
    });

    test('view button opens form in view mode with disabled submit', async ({ page }) => {
        // Find the first ALREADY frozen entry (checked gel checkbox) to avoid timing issues
        const frozenCount = await page.locator('.gel-checkbox:checked').count();
        let ecritureId;

        if (frozenCount === 0) {
            // No frozen entries, so freeze the first one
            const firstGelCheckbox = page.locator('.gel-checkbox').first();
            ecritureId = await firstGelCheckbox.getAttribute('data-ecriture-id');

            // Wait for AJAX response when checking
            const responsePromise = page.waitForResponse(
                response => response.url().includes('/compta/') && response.status() === 200,
                { timeout: 5000 }
            );
            await firstGelCheckbox.check();
            await responsePromise.catch(() => {});
            await page.waitForLoadState('domcontentloaded');

            // Verify button changed to view mode
            const viewBtn = page.locator(`.edit-entry-btn[data-ecriture-id="${ecritureId}"]`);
            await expect(viewBtn.locator('i')).toHaveClass(/fa-eye/, { timeout: 5000 });
        } else {
            // Use the already frozen entry - no timing issues!
            const frozenCheckbox = page.locator('.gel-checkbox:checked').first();
            ecritureId = await frozenCheckbox.getAttribute('data-ecriture-id');
        }

        // Find the view button for the frozen entry and verify it's in view mode
        const viewBtn = page.locator(`.edit-entry-btn[data-ecriture-id="${ecritureId}"]`);

        // Verify the button is in view mode (eye icon) before clicking
        await expect(viewBtn.locator('i')).toHaveClass(/fa-eye/, { timeout: 5000 });
        await expect(viewBtn).toHaveAttribute('data-frozen', '1');

        // Click the view button
        await viewBtn.click();

        // Wait for the edit page to load
        await page.waitForLoadState('networkidle');
        await page.waitForLoadState('domcontentloaded');

        // Verify we're on the edit page
        await expect(page).toHaveURL(/compta\/edit/);

        // Wait for form to be fully loaded and JavaScript to execute
        await page.waitForLoadState('load');

        // Verify the submit button is disabled (VISUALISATION mode)
        // Be more specific - look for submit button in the form
        const submitBtn = page.locator('form button[type="submit"], form input[type="submit"]').first();
        await expect(submitBtn).toBeDisabled({ timeout: 5000 });

        // State will be cleaned up in afterEach (includes going back to journal and unfreezing)
    });
});
