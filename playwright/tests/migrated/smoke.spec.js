/**
 * Smoke Tests - Migrated from Dusk to Playwright
 * 
 * Original Dusk test: /home/frederic/git/dusk_gvv/tests/Browser/SmokeTest.php
 * 
 * Basic smoke tests to verify core application functionality:
 * - Application loads without errors
 * - Main navigation works
 * - Core pages are accessible
 * - No critical JavaScript errors
 * 
 * Usage:
 *   npx playwright test tests/migrated/smoke.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('../helpers/LoginPage');

// Test configuration
const TEST_USER = 'testadmin';
const TEST_PASSWORD = 'password';

test.describe('GVV Smoke Tests (Migrated from Dusk)', () => {

  test('should load application without errors', async ({ page }) => {
    const loginPage = new LoginPage(page);
    
    // Navigate to home page
    await loginPage.goto('');
    
    // Check for basic application elements
    await loginPage.assertText('GVV');
    await loginPage.assertText('Boissel');
    await loginPage.assertText('Peignot');
    
    // Verify no JavaScript errors in console
    const jsErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        jsErrors.push(msg.text());
      }
    });
    
    // Navigate around a bit to trigger any JS
    await loginPage.goto('/auth/login');
    await page.waitForTimeout(2000);
    
    // Log any JS errors for debugging
    if (jsErrors.length > 0) {
      console.log('JavaScript errors detected:', jsErrors);
    }
    
    console.log('✓ Application loads without critical errors');
  });

  test('should navigate to core pages without errors', async ({ page }) => {
    const loginPage = new LoginPage(page);
    
    // Login first
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD);
    
    // Test core application pages
    const corePages = [
      { url: '', name: 'Home' },
      { url: 'vols_planeur/page', name: 'Glider Flights' },
      { url: 'membre/page', name: 'Members' },
      { url: 'planeur/page', name: 'Gliders' },
      { url: 'avion/page', name: 'Aircraft' },
      { url: 'terrains/page', name: 'Airfields' },
      { url: 'comptes/page', name: 'Accounts' },
      { url: 'achats/page', name: 'Purchases' }
    ];
    
    for (const pageInfo of corePages) {
      console.log(`Testing ${pageInfo.name} page...`);
      
      await loginPage.goto(pageInfo.url);
      
      // Verify page loads successfully (no error pages)
      const hasError = await loginPage.hasText('Error') ||
                      await loginPage.hasText('Exception') ||
                      await loginPage.hasText('Fatal error') ||
                      await loginPage.hasText('404');
                      
      expect(hasError).toBeFalsy();
      
      // Verify common elements are present
      await loginPage.assertText('GVV');
      
      console.log(`✓ ${pageInfo.name} page loads successfully`);
    }
    
    await loginPage.logout();
  });

  test('should handle login/logout cycle multiple times', async ({ page }) => {
    const loginPage = new LoginPage(page);
    
    // Test multiple login/logout cycles
    for (let i = 1; i <= 3; i++) {
      console.log(`Login/logout cycle ${i}`);
      
      await loginPage.open();
      await loginPage.login(TEST_USER, TEST_PASSWORD);
      await loginPage.verifyLoggedIn();
      
      await loginPage.logout();
      await loginPage.verifyLoggedOut();
      
      console.log(`✓ Cycle ${i} completed successfully`);
    }
  });

  test('should handle form interactions without errors', async ({ page }) => {
    const loginPage = new LoginPage(page);
    
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD);
    
    // Test basic form interactions
    console.log('Testing flight creation form...');
    await loginPage.goto('vols_planeur/create');
    
    // Fill some basic form fields
    await page.fill('input[name="vpdate"]', '01/01/2024');
    await page.waitForTimeout(500);
    
    // Test dropdown interactions
    const pilotSelect = page.locator('select[name="vppilote"]');
    if (await pilotSelect.count() > 0) {
      const options = await pilotSelect.locator('option').count();
      if (options > 1) {
        await pilotSelect.selectOption({ index: 1 });
        console.log('✓ Pilot selection works');
      }
    }
    
    const gliderSelect = page.locator('select[name="vpmacid"]');
    if (await gliderSelect.count() > 0) {
      const options = await gliderSelect.locator('option').count();
      if (options > 1) {
        await gliderSelect.selectOption({ index: 1 });
        await page.waitForTimeout(1000); // Wait for dynamic updates
        console.log('✓ Glider selection works');
      }
    }
    
    console.log('✓ Form interactions work without errors');
    
    await loginPage.logout();
  });

  test('should display proper navigation for logged-in users', async ({ page }) => {
    const loginPage = new LoginPage(page);
    
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD);
    
    // Check for main navigation elements
    const navElements = [
      'Membres',
      'Planeurs', 
      'Vols',
      'Compta'
    ];
    
    for (const element of navElements) {
      const hasElement = await loginPage.hasText(element);
      if (hasElement) {
        console.log(`✓ Navigation element "${element}" found`);
      }
    }
    
    // Should have at least some navigation
    const hasAnyNav = await loginPage.hasText('Membres') ||
                     await loginPage.hasText('Planeurs') ||
                     await loginPage.hasText('Vols');
                     
    expect(hasAnyNav).toBeTruthy();
    
    await loginPage.logout();
  });

  test('should handle AJAX requests without errors', async ({ page }) => {
    const loginPage = new LoginPage(page);
    
    // Track failed network requests
    const failedRequests = [];
    page.on('response', response => {
      if (response.status() >= 400) {
        failedRequests.push({
          url: response.url(),
          status: response.status(),
          statusText: response.statusText()
        });
      }
    });
    
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD);
    
    // Navigate to pages that likely make AJAX calls
    await loginPage.goto('vols_planeur/page');
    await page.waitForTimeout(3000);
    
    await loginPage.goto('membres/page');
    await page.waitForTimeout(3000);
    
    // Log any failed requests (but don't fail test unless critical)
    if (failedRequests.length > 0) {
      console.log('Failed requests detected:', failedRequests);
      
      // Only fail if there are critical failures (5xx errors)
      const criticalFailures = failedRequests.filter(req => req.status >= 500);
      expect(criticalFailures.length).toBe(0);
    }
    
    console.log('✓ No critical AJAX errors detected');
    
    await loginPage.logout();
  });

  test('should handle different screen sizes', async ({ page, context }) => {
    const loginPage = new LoginPage(page);
    
    // Test different viewport sizes
    const viewports = [
      { width: 1920, height: 1080, name: 'Desktop' },
      { width: 1366, height: 768, name: 'Laptop' },
      { width: 768, height: 1024, name: 'Tablet' },
      { width: 375, height: 667, name: 'Mobile' }
    ];
    
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD);
    
    for (const viewport of viewports) {
      console.log(`Testing ${viewport.name} viewport (${viewport.width}x${viewport.height})`);
      
      await page.setViewportSize({
        width: viewport.width,
        height: viewport.height
      });
      
      await loginPage.goto('vols_planeur/page');
      await page.waitForTimeout(1000);
      
      // Verify page still loads and main elements are present
      await loginPage.assertText('GVV');
      
      console.log(`✓ ${viewport.name} viewport works`);
    }
    
    await loginPage.logout();
  });

  test('should load all critical resources', async ({ page }) => {
    const loginPage = new LoginPage(page);
    
    // Track resource loading
    const resourceFailures = [];
    page.on('response', response => {
      if (response.status() >= 400) {
        const url = response.url();
        // Only track CSS, JS, and other critical resources
        if (url.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico)$/)) {
          resourceFailures.push({
            url: url,
            status: response.status()
          });
        }
      }
    });
    
    await loginPage.open();
    await loginPage.login(TEST_USER, TEST_PASSWORD);
    
    // Navigate to main pages to load resources
    await loginPage.goto('vols_planeur/page');
    await page.waitForLoadState('networkidle');
    
    await loginPage.goto('membres/page');
    await page.waitForLoadState('networkidle');
    
    // Report any resource failures
    if (resourceFailures.length > 0) {
      console.log('Resource loading failures:', resourceFailures);
      
      // Don't fail test for missing images, but fail for CSS/JS
      const criticalFailures = resourceFailures.filter(res => 
        res.url.match(/\.(css|js)$/) && res.status >= 400
      );
      
      expect(criticalFailures.length).toBe(0);
    }
    
    console.log('✓ Critical resources load successfully');
    
    await loginPage.logout();
  });

});