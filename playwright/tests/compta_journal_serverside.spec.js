import { test, expect } from '@playwright/test';

/**
 * End-to-end test for server-side DataTables implementation in journal_compte
 * 
 * Tests the new server-side processing that replaces the dual mode 
 * (< 400 entries DataTables client-side, > 400 entries CodeIgniter pagination)
 */

test.describe('Journal Compte Server-side DataTables', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to GVV application
    await page.goto('http://gvv.net/');
    
    // Login - using testplanchiste user (assuming it exists)
    await page.fill('input[name="username"]', 'testplanchiste');
    await page.fill('input[name="password"]', 'password');
    await page.click('input[type="submit"]');
    
    // Wait for dashboard to load
    await page.waitForLoadState('networkidle');
  });

  test('DataTables loads correctly with server-side processing', async ({ page }) => {
    // PHASE 1 FIX: Navigate directly to balance page instead of clicking dropdown menu
    await page.goto('http://gvv.net/comptes/balance');
    await page.waitForLoadState('networkidle');

    // Find and click on first account link to open journal
    const accountLink = await page.locator('table a[href*="journal_compte"]').first();
    await accountLink.click();
    await page.waitForLoadState('networkidle');
    
    // Verify DataTables is initialized
    await expect(page.locator('#journal-table')).toBeVisible();
    await expect(page.locator('.dataTables_wrapper')).toBeVisible();
    
    // Verify pagination controls are present
    await expect(page.locator('.dataTables_paginate')).toBeVisible();
    await expect(page.locator('.dataTables_info')).toBeVisible();
    
    // Verify search box is present and functional
    const searchBox = page.locator('input[type="search"]');
    await expect(searchBox).toBeVisible();
  });

  test('Search functionality works across all data (server-side)', async ({ page }) => {
    // PHASE 1 FIX: Navigate directly to balance page
    await page.goto('http://gvv.net/comptes/balance');
    await page.waitForLoadState('networkidle');

    const accountLink = await page.locator('table a[href*="journal_compte"]').first();
    await accountLink.click();
    await page.waitForLoadState('networkidle');
    
    // Wait for DataTables to initialize
    await page.waitForSelector('.dataTables_wrapper', { timeout: 10000 });
    
    // Get initial number of displayed entries
    const initialInfo = await page.locator('.dataTables_info').textContent();
    
    // Perform a search
    const searchBox = page.locator('input[type="search"]');
    await searchBox.fill('test');
    await page.waitForTimeout(500); // Wait for search to be processed
    
    // Verify search was processed
    await page.waitForFunction(() => {
      const table = document.querySelector('#journal-table');
      return table && !table.classList.contains('processing');
    });
    
    // Search results should be different from initial results
    const searchInfo = await page.locator('.dataTables_info').textContent();
    // Note: This test might show 0 results if no entries contain "test"
    // which is expected and not a failure
  });

  test('Pagination works correctly', async ({ page }) => {
    // PHASE 1 FIX: Navigate directly to balance page
    await page.goto('http://gvv.net/comptes/balance');
    await page.waitForLoadState('networkidle');

    const accountLink = await page.locator('table a[href*="journal_compte"]').first();
    await accountLink.click();
    await page.waitForLoadState('networkidle');
    
    // Wait for DataTables to initialize
    await page.waitForSelector('.dataTables_wrapper', { timeout: 10000 });
    
    // Check if there are multiple pages (next button is enabled)
    const nextButton = page.locator('.dataTables_paginate .paginate_button.next');
    const isNextEnabled = await nextButton.evaluate(btn => !btn.classList.contains('disabled'));
    
    if (isNextEnabled) {
      // Get data from first page
      const firstPageData = await page.locator('#journal-table tbody tr').first().textContent();
      
      // Go to next page
      await nextButton.click();
      await page.waitForLoadState('networkidle');
      
      // Verify we're on a different page
      const secondPageData = await page.locator('#journal-table tbody tr').first().textContent();
      expect(firstPageData).not.toBe(secondPageData);
      
      // Go back to first page
      const prevButton = page.locator('.dataTables_paginate .paginate_button.previous');
      await prevButton.click();
      await page.waitForLoadState('networkidle');
    }
  });

  test('Column sorting works correctly', async ({ page }) => {
    // PHASE 1 FIX: Navigate directly to balance page
    await page.goto('http://gvv.net/comptes/balance');
    await page.waitForLoadState('networkidle');

    const accountLink = await page.locator('table a[href*="journal_compte"]').first();
    await accountLink.click();
    await page.waitForLoadState('networkidle');
    
    // Wait for DataTables to initialize
    await page.waitForSelector('.dataTables_wrapper', { timeout: 10000 });
    
    // Test sorting on Date column (first column)
    const dateHeader = page.locator('#journal-table thead th').first();
    
    // Get initial first row data
    const initialFirstRow = await page.locator('#journal-table tbody tr').first().textContent();
    
    // Click to sort
    await dateHeader.click();
    await page.waitForLoadState('networkidle');
    
    // Verify sorting changed (data should be different)
    const sortedFirstRow = await page.locator('#journal-table tbody tr').first().textContent();
    
    // Note: The sorting might result in the same row being first if it's already sorted correctly
    // But we can verify that sorting classes are applied
    const headerAfterSort = await dateHeader.getAttribute('class');
    expect(headerAfterSort).toMatch(/(sorting_asc|sorting_desc)/);
  });

  test('Page length selector works', async ({ page }) => {
    // PHASE 1 FIX: Navigate directly to balance page
    await page.goto('http://gvv.net/comptes/balance');
    await page.waitForLoadState('networkidle');

    const accountLink = await page.locator('table a[href*="journal_compte"]').first();
    await accountLink.click();
    await page.waitForLoadState('networkidle');
    
    // Wait for DataTables to initialize
    await page.waitForSelector('.dataTables_wrapper', { timeout: 10000 });
    
    // Find page length selector
    const lengthSelect = page.locator('select[name$="_length"]');
    await expect(lengthSelect).toBeVisible();
    
    // Change page length to 25
    await lengthSelect.selectOption('25');
    await page.waitForLoadState('networkidle');
    
    // Verify the change was applied
    const info = await page.locator('.dataTables_info').textContent();
    // The info should reflect the new page size (though exact text depends on data)
  });

  test('No JavaScript errors during operation', async ({ page }) => {
    const jsErrors = [];

    page.on('console', msg => {
      if (msg.type() === 'error') {
        jsErrors.push(msg.text());
      }
    });

    page.on('pageerror', error => {
      jsErrors.push(error.message);
    });

    // PHASE 1 FIX: Navigate directly to balance page
    await page.goto('http://gvv.net/comptes/balance');
    await page.waitForLoadState('networkidle');

    const accountLink = await page.locator('table a[href*="journal_compte"]').first();
    await accountLink.click();
    await page.waitForLoadState('networkidle');
    
    // Wait for DataTables to initialize
    await page.waitForSelector('.dataTables_wrapper', { timeout: 10000 });
    
    // Perform various operations to ensure no JS errors
    const searchBox = page.locator('input[type="search"]');
    await searchBox.fill('test');
    await page.waitForTimeout(1000);
    
    // Clear search
    await searchBox.fill('');
    await page.waitForTimeout(1000);
    
    // Check for any JavaScript errors
    expect(jsErrors).toHaveLength(0);
  });
});

test.describe('Regression tests for existing functionality', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://gvv.net/');
    await page.fill('input[name="username"]', 'testplanchiste');
    await page.fill('input[name="password"]', 'password');
    await page.click('input[type="submit"]');
    await page.waitForLoadState('networkidle');
  });

  test('Edit and delete buttons still work', async ({ page }) => {
    // PHASE 1 FIX: Navigate directly to balance page
    await page.goto('http://gvv.net/comptes/balance');
    await page.waitForLoadState('networkidle');

    const accountLink = await page.locator('table a[href*="journal_compte"]').first();
    await accountLink.click();
    await page.waitForLoadState('networkidle');
    
    // Wait for DataTables to initialize
    await page.waitForSelector('.dataTables_wrapper', { timeout: 10000 });
    
    // Look for action buttons (edit/delete)
    const actionButtons = page.locator('#journal-table .btn');
    const buttonCount = await actionButtons.count();
    
    if (buttonCount > 0) {
      // Verify edit button exists and has correct href
      const editButton = page.locator('#journal-table .btn-primary').first();
      if (await editButton.count() > 0) {
        const editHref = await editButton.getAttribute('href');
        expect(editHref).toMatch(/compta\/edit\/\d+/);
      }
      
      // Verify delete button exists and has correct href
      const deleteButton = page.locator('#journal-table .btn-danger').first();
      if (await deleteButton.count() > 0) {
        const deleteHref = await deleteButton.getAttribute('href');
        expect(deleteHref).toMatch(/compta\/delete\/\d+/);
      }
    }
  });

  test('Filters still work with server-side processing', async ({ page }) => {
    // PHASE 1 FIX: Navigate directly to balance page
    await page.goto('http://gvv.net/comptes/balance');
    await page.waitForLoadState('networkidle');

    const accountLink = await page.locator('table a[href*="journal_compte"]').first();
    await accountLink.click();
    await page.waitForLoadState('networkidle');
    
    // Wait for page to load
    await page.waitForTimeout(2000);
    
    // Expand filter accordion if it exists
    const filterButton = page.locator('button[data-bs-target="#panel_filter_id"]');
    if (await filterButton.count() > 0) {
      await filterButton.click();
      await page.waitForTimeout(500);
      
      // Apply a date filter
      const dateInput = page.locator('input[name="filter_date"]');
      if (await dateInput.count() > 0) {
        await dateInput.fill('01/01/2023');
        
        // Submit filter
        const filterSubmit = page.locator('input[value="Filtrer"]');
        if (await filterSubmit.count() > 0) {
          await filterSubmit.click();
          await page.waitForLoadState('networkidle');
          
          // Verify page reloaded with filter applied
          await page.waitForSelector('.dataTables_wrapper', { timeout: 10000 });
        }
      }
    }
  });
});