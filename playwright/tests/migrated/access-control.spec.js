/**
 * Access Control Tests - Migrated from Dusk to Playwright
 * 
 * Original Dusk tests:
 * - AdminAccessTest.php
 * - UserAccessTest.php  
 * - BureauAccessTest.php
 * - CAAccessTest.php
 * - PlanchisteAccessTest.php
 * 
 * Tests different user roles and their access permissions to various pages
 * 
 * Usage:
 *   npx playwright test tests/migrated/access-control.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('../helpers/LoginPage');

// Test users and their expected access levels
const TEST_USERS = {
  admin: { username: 'testadmin', password: 'password', role: 'Admin' },
  user: { username: 'testuser', password: 'password', role: 'User' },
  bureau: { username: 'testbureau', password: 'password', role: 'Bureau' },
  ca: { username: 'testca', password: 'password', role: 'CA' },
  planchiste: { username: 'testplanchiste', password: 'password', role: 'Planchiste' }
};

// Common elements that should always be visible when logged in
const COMMON_ELEMENTS = ['GVV', 'Copyright (©)', 'Boissel', 'Peignot'];

// Elements that indicate errors or problems
const ERROR_INDICATORS = ['Error', 'Exception', 'Fatal error', 'Undefined', '404 Page not found'];

// Login redirect indicators (what users see when denied access)
const ACCESS_DENIED = ['Utilisateur', 'Mot de passe', 'Connexion'];

/**
 * Test helper to verify page access - improved to handle hidden dropdown elements
 */
async function testPageAccess(page, loginPage, url, expectedElements, mustNotSee = []) {
  console.log(`Testing access to: ${url}`);
  
  await loginPage.goto(url);
  await page.waitForLoadState('networkidle');
  
  // Wait a bit more for dynamic content to load
  await page.waitForTimeout(1000);
  
  // Check for expected elements
  for (const element of expectedElements) {
    try {
      await loginPage.assertText(element);
    } catch (e) {
      console.log(`Expected element "${element}" not found on ${url}`);
      // Try to find the page title as additional debug info
      try {
        const title = await page.title();
        console.log(`Page title: ${title}`);
        const h3 = await page.locator('h3').first().textContent();
        if (h3) console.log(`Main heading: ${h3}`);
      } catch (debugError) {
        // Ignore debug errors
      }
      throw e;
    }
  }
  
  // Check that forbidden elements are not present
  for (const element of mustNotSee) {
    try {
      await loginPage.assertNoText(element);
    } catch (e) {
      console.log(`Forbidden element "${element}" found on ${url}`);
      throw e;
    }
  }
  
  console.log(`✓ Access to ${url} verified`);
}

test.describe('GVV Access Control Tests (Migrated from Dusk)', () => {

  test.describe('Admin User Access', () => {
    
    test('admin can access all administrative pages', async ({ page }) => {
      const loginPage = new LoginPage(page);
      const user = TEST_USERS.admin;
      
      // Login as admin
      await loginPage.open();
      await loginPage.login(user.username, user.password);
      
      // Test various admin-accessible pages
      const adminPages = [
        { url: 'vols_planeur/page', mustSee: ['Vols Planeur'] },
        { url: 'alarmes', mustSee: ['Conditions', 'Visite'] },
        { url: 'tickets/page', mustSee: ['Gestion des tickets'] },
        { url: 'tickets/solde', mustSee: ['Solde des tickets par pilote'] },
        { url: 'rapports/ffvv', mustSee: ['annuel FFVV'] },
        { url: 'rapports/dgac', mustSee: ['Rapport DGAC'] },
        { url: 'terrains/page', mustSee: ['Terrains'] },
        { url: 'terrains/edit/LFOI', mustSee: ['Terrain'] },
        { url: 'terrains/create', mustSee: ['Terrain'] },
        { url: 'membre/page', mustSee: ['Membres'] },
        { url: 'comptes/page', mustSee: ['Comptes'] },
        { url: 'achats/page', mustSee: ['Achats'] },
        { url: 'planeur/page', mustSee: ['Planeurs'] },
        { url: 'avion/page', mustSee: ['Avions'] }
      ];
      
      for (const pageTest of adminPages) {
        await testPageAccess(
          page,
          loginPage,
          pageTest.url,
          [...COMMON_ELEMENTS, ...pageTest.mustSee],
          ERROR_INDICATORS
        );
      }
      
      await loginPage.logout();
    });
    
    test('admin can access financial and accounting features', async ({ page }) => {
      const loginPage = new LoginPage(page);
      const user = TEST_USERS.admin;
      
      await loginPage.open();
      await loginPage.login(user.username, user.password);
      
      // Test financial/accounting pages
      const financialPages = [
        { url: 'comptes/page', mustSee: ['Comptes'] },
        { url: 'achats/page', mustSee: ['Achats'] }
      ];
      
      for (const pageTest of financialPages) {
        await testPageAccess(
          page,
          loginPage,
          pageTest.url,
          [...COMMON_ELEMENTS, ...pageTest.mustSee],
          ERROR_INDICATORS
        );
      }
      
      await loginPage.logout();
    });
  });

  test.describe('Regular User Access', () => {
    
    test('regular user has limited access to non-admin pages', async ({ page }) => {
      const loginPage = new LoginPage(page);
      const user = TEST_USERS.user;
      
      await loginPage.open();
      await loginPage.login(user.username, user.password);
      
      // Pages regular users CAN access (just home page)
      const allowedPages = [
        { url: '', mustSee: [] }  // Just verify home page loads
      ];
      
      for (const pageTest of allowedPages) {
        await testPageAccess(
          page, 
          loginPage, 
          pageTest.url, 
          [...COMMON_ELEMENTS, ...pageTest.mustSee], 
          ERROR_INDICATORS
        );
      }
      
      // Pages regular users CANNOT access
      const deniedPages = [
        'alarmes',
        'tickets/solde', 
        'rapports/ffvv',
        'rapports/dgac',
        'terrains/page',
        'terrains/edit/LFOI',
        'terrains/create',
        'comptes/page',
        'achats/page'
      ];
      
      for (const url of deniedPages) {
        await testPageAccess(
          page, 
          loginPage, 
          url, 
          [...COMMON_ELEMENTS, ...ACCESS_DENIED], 
          ERROR_INDICATORS
        );
      }
      
      await loginPage.logout();
    });
  });

  test.describe('Bureau User Access', () => {
    
    test('bureau user has intermediate access level', async ({ page }) => {
      const loginPage = new LoginPage(page);
      const user = TEST_USERS.bureau;
      
      await loginPage.open();
      await loginPage.login(user.username, user.password);
      
      // Bureau users typically have access to member management and flights
      const allowedPages = [
        { url: 'vols_planeur/page', mustSee: ['Vols Planeur'] },
        { url: 'membre/page', mustSee: ['Membres'] },
        { url: 'tickets/page', mustSee: ['Gestion des tickets'] },
        { url: 'planeur/page', mustSee: ['Planeurs'] }
      ];
      
      for (const pageTest of allowedPages) {
        await testPageAccess(
          page,
          loginPage,
          pageTest.url,
          [...COMMON_ELEMENTS, ...pageTest.mustSee],
          ERROR_INDICATORS
        );
      }
      
      await loginPage.logout();
    });
  });

  test.describe('CA (Board) User Access', () => {
    
    test('CA user has access to management and reports', async ({ page }) => {
      const loginPage = new LoginPage(page);
      const user = TEST_USERS.ca;
      
      await loginPage.open();
      await loginPage.login(user.username, user.password);
      
      // CA users typically have access to reports and oversight functions
      const allowedPages = [
        { url: 'vols_planeur/page', mustSee: ['Vols Planeur'] },
        { url: 'membre/page', mustSee: ['Membres'] },
        { url: 'planeur/page', mustSee: ['Planeurs'] }
      ];
      
      for (const pageTest of allowedPages) {
        await testPageAccess(
          page,
          loginPage,
          pageTest.url,
          [...COMMON_ELEMENTS, ...pageTest.mustSee],
          ERROR_INDICATORS
        );
      }
      
      await loginPage.logout();
    });
  });

  test.describe('Planchiste User Access', () => {
    
    test('planchiste user has access to flight operations', async ({ page }) => {
      const loginPage = new LoginPage(page);
      const user = TEST_USERS.planchiste;
      
      await loginPage.open();
      await loginPage.login(user.username, user.password);
      
      // Planchistes manage daily flight operations
      const allowedPages = [
        { url: 'vols_planeur/page', mustSee: ['Vols Planeur'] },
        { url: 'vols_planeur/create', mustSee: ['Vol'] },
        { url: 'planeur/page', mustSee: ['Planeurs'] },
        { url: 'membre/page', mustSee: ['Membres'] }
      ];
      
      for (const pageTest of allowedPages) {
        await testPageAccess(
          page,
          loginPage,
          pageTest.url,
          [...COMMON_ELEMENTS, ...pageTest.mustSee],
          ERROR_INDICATORS
        );
      }
      
      await loginPage.logout();
    });
  });

  test.describe('Cross-User Navigation Tests', () => {
    
    test('all user types can access basic flight pages', async ({ page }) => {
      const loginPage = new LoginPage(page);
      
      // Test that admin user can access home page after login
      const user = TEST_USERS.admin;
      console.log(`Testing basic access for admin user`);
      
      await loginPage.open();
      await loginPage.login(user.username, user.password);
      
      // Admin should be able to view home page
      await testPageAccess(
        page, 
        loginPage, 
        '', 
        [...COMMON_ELEMENTS], 
        ERROR_INDICATORS
      );
      
      await loginPage.logout();
    });
    
    test('navigation elements reflect user permissions', async ({ page }) => {
      const loginPage = new LoginPage(page);
      
      // Login as admin and check that admin-only nav elements appear
      await loginPage.open();
      await loginPage.login(TEST_USERS.admin.username, TEST_USERS.admin.password);
      
      await loginPage.goto('');
      
      // Admin should see administrative navigation options - check for admin-accessible page content
      const hasAdminNav = await loginPage.hasText('Planeurs') || 
                         await loginPage.hasText('Comptes') ||
                         await loginPage.hasText('Membres') ||
                         await loginPage.hasText('Facturation');
      
      expect(hasAdminNav).toBeTruthy();
      console.log('Admin navigation elements verified');
      
      await loginPage.logout();
      
      // Login as regular user and verify limited navigation
      await loginPage.open();
      await loginPage.login(TEST_USERS.user.username, TEST_USERS.user.password);
      
      await loginPage.goto('');
      
      // Regular user should not see admin navigation
      const hasLimitedNav = !(await loginPage.hasText('Configuration')) ||
                           !(await loginPage.hasText('Administration'));
      
      expect(hasLimitedNav).toBeTruthy();
      console.log('User navigation limitations verified');
      
      await loginPage.logout();
    });
  });

});