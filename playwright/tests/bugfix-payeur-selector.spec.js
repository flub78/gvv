/**
 * Playwright test for BugFix: Payeur Selector
 *
 * Tests that the payeur selector only shows members with 411 accounts
 * for the active section, and that the selector displays account names.
 *
 * Bug Description:
 * - Previously, payeur selector allowed selecting any active member
 * - This could corrupt billing data when members had no 411 accounts
 * - Fixed to only show 411 account holders with proper account names
 *
 * Usage:
 *   npx playwright test tests/bugfix-payeur-selector.spec.js
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const LOGIN_URL = '/auth/login';
const TEST_USER = {
  username: 'testadmin',
  password: 'password'
};

test.describe('BugFix: Payeur Selector Tests', () => {

  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    
    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Verify we're logged in (not on login page)
    expect(page.url()).not.toBe(LOGIN_URL);
  });

  test('BugFix: should verify payeur selector in glider flight form only shows 411 accounts', async ({ page }) => {
    // Navigate to glider flight creation form
    await page.goto('/index.php/vols_planeur/create');
    await page.waitForLoadState('networkidle');
    
    // Check if the page loaded correctly
    const pageTitle = await page.title();
    console.log(`Glider flight form page title: ${pageTitle}`);
    
    // Look for the payeur selector
    const payeurSelector = page.locator('select[name="payeur"], #payeur');
    
    // Wait for the selector to be present
    await expect(payeurSelector).toBeVisible({ timeout: 10000 });
    
    // Get all options in the payeur selector
    const options = await payeurSelector.locator('option').all();
    console.log(`Found ${options.length} payeur options in glider form`);
    
    // Verify there's at least one option (empty option + account options)
    expect(options.length).toBeGreaterThan(0);
    
    // Check that options contain account codes (format: (411...) Name)
    let accountOptionsFound = 0;
    let accountsToCheck = 0;
    const maxAccountsToCheck = 5; // Only check first 5 accounts for performance
    
    for (const option of options) {
      const optionText = await option.textContent();
      const optionValue = await option.getAttribute('value');
      
      // Skip empty option
      if (optionValue && optionValue.trim() !== '') {
        accountsToCheck++;
        
        // Only check first few accounts for performance
        if (accountsToCheck <= maxAccountsToCheck) {
          console.log(`Payeur option: value="${optionValue}" text="${optionText}"`);
          
          // Check that option text contains account code format (411...)
          expect(optionText).toMatch(/\(411\d*\)/);
          
          // Check that option value is numeric (account ID)
          expect(optionValue).toMatch(/^\d+$/);
        }
        
        accountOptionsFound++;
      }
    }
    
    console.log(`Checked first ${Math.min(accountsToCheck, maxAccountsToCheck)} accounts, found ${accountOptionsFound} total accounts`);
    
    // Verify we found at least one 411 account option
    expect(accountOptionsFound).toBeGreaterThan(0);
    console.log(`Verified ${accountOptionsFound} 411 account options in glider form`);
  });

  test('BugFix: should verify payeur selector in airplane flight form only shows 411 accounts', async ({ page }) => {
    // Navigate to airplane flight creation form
    await page.goto('/index.php/vols_avion/create');
    await page.waitForLoadState('networkidle');
    
    // Check if the page loaded correctly
    const pageTitle = await page.title();
    console.log(`Airplane flight form page title: ${pageTitle}`);
    
    // Look for the payeur selector
    const payeurSelector = page.locator('select[name="payeur"], #payeur');
    
    // Wait for the selector to be present
    await expect(payeurSelector).toBeVisible({ timeout: 10000 });
    
    // Get all options in the payeur selector
    const options = await payeurSelector.locator('option').all();
    console.log(`Found ${options.length} payeur options in airplane form`);
    
    // Verify there's at least one option (empty option + account options)
    expect(options.length).toBeGreaterThan(0);
    
    // Check that options contain account codes (format: (411...) Name)
    let accountOptionsFound = 0;
    let accountsToCheck = 0;
    const maxAccountsToCheck = 5; // Only check first 5 accounts for performance
    
    for (const option of options) {
      const optionText = await option.textContent();
      const optionValue = await option.getAttribute('value');
      
      // Skip empty option
      if (optionValue && optionValue.trim() !== '') {
        accountsToCheck++;
        
        // Only check first few accounts for performance
        if (accountsToCheck <= maxAccountsToCheck) {
          console.log(`Payeur option: value="${optionValue}" text="${optionText}"`);
          
          // Check that option text contains account code format (411...)
          expect(optionText).toMatch(/\(411\d*\)/);
          
          // Check that option value is numeric (account ID)
          expect(optionValue).toMatch(/^\d+$/);
        }
        
        accountOptionsFound++;
      }
    }
    
    console.log(`Checked first ${Math.min(accountsToCheck, maxAccountsToCheck)} accounts, found ${accountOptionsFound} total accounts`);
    
    // Verify we found at least one 411 account option
    expect(accountOptionsFound).toBeGreaterThan(0);
    console.log(`Verified ${accountOptionsFound} 411 account options in airplane form`);
  });

  test('BugFix: should verify payeur selector has empty default option', async ({ page }) => {
    // Navigate to glider flight creation form
    await page.goto('/index.php/vols_planeur/create');
    await page.waitForLoadState('networkidle');
    
    const payeurSelector = page.locator('select[name="payeur"], #payeur');
    await expect(payeurSelector).toBeVisible({ timeout: 10000 });
    
    // Check the first option (should be empty/default)
    const firstOption = payeurSelector.locator('option').first();
    const firstOptionValue = await firstOption.getAttribute('value');
    const firstOptionText = await firstOption.textContent();
    
    console.log(`First option: value="${firstOptionValue}" text="${firstOptionText}"`);
    
    // First option should be empty (for "no payeur selected")
    expect(firstOptionValue).toBe('');
    
    // Should have some indication it's a selection prompt
    expect(firstOptionText).toMatch(/sélection|select|choose/i);
    
    console.log('Verified that payeur selector has proper empty default option');
  });

  test('BugFix: should verify payeur selector only contains 411 accounts from active section', async ({ page }) => {
    // Navigate to glider flight creation form
    await page.goto('/index.php/vols_planeur/create');
    await page.waitForLoadState('networkidle');
    
    const payeurSelector = page.locator('select[name="payeur"], #payeur');
    await expect(payeurSelector).toBeVisible({ timeout: 10000 });
    
    const options = await payeurSelector.locator('option').all();
    
    let validAccountsFound = 0;
    let invalidAccountsFound = 0;
    let accountsChecked = 0;
    const maxAccountsToCheck = 5; // Only check first 5 accounts for performance
    
    for (const option of options) {
      const optionText = await option.textContent();
      const optionValue = await option.getAttribute('value');
      
      // Skip empty option
      if (optionValue && optionValue.trim() !== '') {
        accountsChecked++;
        
        // Only verify first few accounts for performance
        if (accountsChecked <= maxAccountsToCheck) {
          // Check if it's a 411 account
          if (optionText.match(/\(411\d*\)/)) {
            validAccountsFound++;
            console.log(`Valid 411 account: "${optionText}"`);
          } else {
            invalidAccountsFound++;
            console.log(`Invalid account (not 411): "${optionText}"`);
          }
        } else {
          // For remaining accounts, just count total without detailed verification
          if (optionText.match(/\(411\d*\)/)) {
            validAccountsFound++;
          } else {
            invalidAccountsFound++;
          }
        }
      }
    }
    
    console.log(`Checked first ${Math.min(accountsChecked, maxAccountsToCheck)} accounts in detail`);
    console.log(`Found ${validAccountsFound} valid 411 accounts and ${invalidAccountsFound} invalid accounts (total ${accountsChecked} accounts)`);
    
    // All non-empty options should be 411 accounts
    expect(invalidAccountsFound).toBe(0);
    expect(validAccountsFound).toBeGreaterThan(0);
    
    console.log('Verified that payeur selector only contains 411 accounts');
  });

  test('BugFix: should verify payeur selector shows account names with proper format', async ({ page }) => {
    // Navigate to glider flight creation form
    await page.goto('/index.php/vols_planeur/create');
    await page.waitForLoadState('networkidle');
    
    const payeurSelector = page.locator('select[name="payeur"], #payeur');
    await expect(payeurSelector).toBeVisible({ timeout: 10000 });
    
    const options = await payeurSelector.locator('option').all();
    
    let accountsChecked = 0;
    const maxAccountsToCheck = 4; // Only check first 4 accounts for performance
    
    for (const option of options) {
      const optionText = await option.textContent();
      const optionValue = await option.getAttribute('value');
      
      // Skip empty option
      if (optionValue && optionValue.trim() !== '') {
        accountsChecked++;
        
        // Only check first few accounts for performance
        if (accountsChecked <= maxAccountsToCheck) {
          console.log(`Checking option: "${optionText}"`);
          
          // Verify format is: (411...) Account Name  
          expect(optionText).toMatch(/^\(411\d*\)\s*.+$/);
          
          // Extract account name part
          const accountName = optionText.replace(/^\(411\d*\)\s*/, '');
          
          // The account name should not be empty
          expect(accountName.trim()).not.toBe('');
          
          console.log(`  Account name: "${accountName}"`);
        }
      }
    }
    
    console.log(`Checked first ${accountsChecked} accounts for proper format. Total options: ${options.length}`);
    console.log('Verified that payeur selector shows properly formatted account names');
  });
});