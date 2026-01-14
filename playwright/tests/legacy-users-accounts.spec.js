/**
 * Playwright test for Legacy Authorization System (DX_Auth/permissions)
 *
 * Tests basic access control for different user roles using the test users
 * created by bin/create_test_users.sh. Each test user has password "password".
 * 
 * This test verifies the LEGACY authorization system (DX_Auth/permissions)
 * before migration to the new Gvv_Authorization system.
 * 
 * Test Users (from bin/create_test_users.sh):
 * - testuser (role: user/1) - basic user access
 * - testplanchiste (role: planchiste/5) - windsurfer/basic operations
 * - testca (role: ca/6) - board member (Conseil d'Administration)
 * - testbureau (role: bureau/7) - bureau member
 * - testtresorier (role: tresorier/8) - treasurer
 * - testadmin (role: club-admin/10) - administrator
 * 
 * Usage:
 *   npx playwright test tests/legacy-users-accounts.spec.js
 *   npx playwright test tests/legacy-users-accounts.spec.js -g "testadmin"
 *   npx playwright test tests/legacy-users-accounts.spec.js --ui
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const BASE_URL = '/index.php';
const LOGIN_URL = '/index.php/auth/login';

// Test users from bin/create_test_users.sh
const TEST_USERS = {
    testuser: {
        username: 'testuser',
        password: 'password',
        role: 'user',
        role_id: 1,
        description: 'Basic user - limited access'
    },
    testplanchiste: {
        username: 'testplanchiste',
        password: 'password',
        role: 'planchiste',
        role_id: 5,
        description: 'Windsurfer - operational access'
    },
    testca: {
        username: 'testca',
        password: 'password',
        role: 'ca',
        role_id: 6,
        description: 'Board member - CA access'
    },
    testbureau: {
        username: 'testbureau',
        password: 'password',
        role: 'bureau',
        role_id: 7,
        description: 'Bureau member - elevated access'
    },
    testtresorier: {
        username: 'testtresorier',
        password: 'password',
        role: 'tresorier',
        role_id: 8,
        description: 'Treasurer - financial access'
    },
    testadmin: {
        username: 'testadmin',
        password: 'password',
        role: 'club-admin',
        role_id: 10,
        description: 'Administrator - full access'
    }
};

// Pages to test with expected access per role
const TEST_PAGES = {
    welcome: {
        url: '/index.php/welcome/index',
        description: 'Welcome dashboard',
        allowedRoles: ['user', 'planchiste', 'ca', 'bureau', 'tresorier', 'club-admin']
    },
    membre_page: {
        url: '/index.php/membre/page',
        description: 'Member listing',
        allowedRoles: ['user', 'planchiste', 'ca', 'bureau', 'tresorier', 'club-admin']
    },
    membre_create: {
        url: '/index.php/membre/create',
        description: 'Create new member',
        allowedRoles: ['club-admin', 'bureau']
    },
    vols_planeur: {
        url: '/index.php/vols_planeur/page',
        description: 'Glider flights listing',
        allowedRoles: ['user', 'planchiste', 'ca', 'bureau', 'tresorier', 'club-admin']
    },
    vols_planeur_create: {
        url: '/index.php/vols_planeur/create',
        description: 'Create glider flight',
        allowedRoles: ['planchiste', 'ca', 'bureau', 'tresorier', 'club-admin']
    },
    vols_avion: {
        url: '/index.php/vols_avion/page',
        description: 'Airplane flights listing',
        allowedRoles: ['user', 'planchiste', 'ca', 'bureau', 'tresorier', 'club-admin']
    },
    compta_mon_compte: {
        url: '/index.php/compta/mon_compte/1',
        description: 'View own account',
        allowedRoles: ['user', 'planchiste', 'ca', 'bureau', 'tresorier', 'club-admin']
    },
    compta_ecritures: {
        url: '/index.php/compta/ecritures',
        description: 'Accounting entries',
        allowedRoles: ['tresorier', 'club-admin', 'bureau']
    },
    compta_comptes: {
        url: '/index.php/comptes/page',
        description: 'Chart of accounts',
        allowedRoles: ['tresorier', 'club-admin', 'bureau']
    },
    compta_balance: {
        url: '/index.php/comptes/balance',
        description: 'Account balance',
        allowedRoles: ['tresorier', 'club-admin', 'bureau']
    },
    terrains_page: {
        url: '/index.php/terrains/page',
        description: 'Airfield listing',
        allowedRoles: ['ca', 'bureau', 'tresorier', 'club-admin']
    },
    backend_users: {
        url: '/index.php/backend/users',
        description: 'User administration',
        allowedRoles: ['club-admin']
    },
    admin_index: {
        url: '/index.php/admin/index',
        description: 'Administration panel',
        allowedRoles: ['club-admin']
    }
};

/**
 * Helper: Login with specified user
 */
async function loginUser(page, username, password) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"], input[type="submit"]');
    
    await page.waitForLoadState('networkidle');
    
    // Check for login error message
    const bodyText = await page.locator('body').innerText();
    if (bodyText.includes('mot de passe est incorrect') || 
        bodyText.includes('password is incorrect') ||
        bodyText.includes('Login failed')) {
        throw new Error(`Login failed for ${username}: incorrect password or user not found`);
    }
    
    // Verify we're not on login page (use pathname to be domain-agnostic)
    const pathname = new URL(page.url()).pathname;
    if (pathname.includes('/auth/login')) {
        throw new Error(`Login failed for ${username}: still on login page`);
    }
    
    // Close "Message du jour" dialog if it appears
    try {
        const modDialog = page.locator('.ui-dialog');
        if (await modDialog.isVisible({ timeout: 2000 })) {
            const closeButton = page.locator('.ui-dialog-buttonpane button:has-text("OK")');
            if (await closeButton.isVisible({ timeout: 1000 })) {
                await closeButton.click();
                await page.waitForTimeout(500);
            }
        }
    } catch (e) {
        // Dialog not present, continue
    }
    
    console.log(`✓ Logged in as ${username}`);
}

/**
 * Helper: Logout current user
 */
async function logoutUser(page) {
    const logoutSelectors = [
        'a[href*="logout"]',
        'a:has-text("Logout")',
        'a:has-text("Déconnexion")',
        'a:has-text("Log out")'
    ];
    
    for (const selector of logoutSelectors) {
        try {
            const logoutElement = page.locator(selector).first();
            if (await logoutElement.isVisible({ timeout: 2000 })) {
                await logoutElement.click();
                await page.waitForLoadState('networkidle');
                console.log(`✓ Logged out`);
                return true;
            }
        } catch (e) {
            continue;
        }
    }
    
    // Fallback: direct logout URL
    await page.goto('/index.php/auth/logout');
    await page.waitForLoadState('networkidle');
    console.log(`✓ Logged out (via direct URL)`);
    return true;
}

/**
 * Helper: Check if page access is allowed or denied
 */
async function checkPageAccess(page, url) {
    await page.goto(url);
    await page.waitForLoadState('networkidle');
    
    const pathname = new URL(page.url()).pathname;
    
    // Check if redirected to login (access denied)
    if (pathname.includes('/auth/login')) {
        return { allowed: false, reason: 'Redirected to login page' };
    }
    
    // Check for error messages
    const bodyText = await page.locator('body').innerText();
    const lowerBody = bodyText.toLowerCase();
    
    if (lowerBody.includes('access denied') || 
        lowerBody.includes('accès refusé') ||
        lowerBody.includes('unauthorized') ||
        lowerBody.includes('non autorisé') ||
        lowerBody.includes('permission denied')) {
        return { allowed: false, reason: 'Access denied message shown' };
    }
    
    // Check for 403/404 status indicators
    if (lowerBody.includes('403 forbidden') || lowerBody.includes('404 not found')) {
        return { allowed: false, reason: 'HTTP error status' };
    }
    
    // If we got here, access appears to be allowed
    return { allowed: true, reason: 'Page loaded successfully' };
}

/**
 * Test: Login capability for each user
 */
for (const [key, user] of Object.entries(TEST_USERS)) {
    test(`${user.username} should be able to login`, async ({ page }) => {
        console.log(`\n[TEST] Login test for ${user.username} (${user.description})`);
        
        await loginUser(page, user.username, user.password);
        
        // Verify we're not on login page (domain-agnostic)
        const pathname = new URL(page.url()).pathname;
        expect(pathname).not.toContain('/auth/login');
        
        console.log(`✓ ${user.username} logged in successfully`);
        
        await logoutUser(page);
    });
}

/**
 * Test: Page access for each user
 */
for (const [userKey, user] of Object.entries(TEST_USERS)) {
    test.describe(`${user.username} page access tests`, () => {
        test.beforeEach(async ({ page }) => {
            await loginUser(page, user.username, user.password);
        });
        
        test.afterEach(async ({ page }) => {
            await logoutUser(page);
        });
        
        for (const [pageKey, pageInfo] of Object.entries(TEST_PAGES)) {
            const shouldHaveAccess = pageInfo.allowedRoles.includes(user.role);
            
            test(`should ${shouldHaveAccess ? 'allow' : 'deny'} access to ${pageInfo.description}`, async ({ page }) => {
                console.log(`\n[TEST] ${user.username} accessing ${pageInfo.url}`);
                console.log(`  Expected: ${shouldHaveAccess ? 'ALLOW' : 'DENY'}`);
                
                const result = await checkPageAccess(page, pageInfo.url);
                
                console.log(`  Result: ${result.allowed ? 'ALLOWED' : 'DENIED'} (${result.reason})`);
                
                if (shouldHaveAccess) {
                    expect(result.allowed).toBeTruthy();
                } else {
                    expect(result.allowed).toBeFalsy();
                }
            });
        }
    });
}

/**
 * Test: Sequential login verification (all users can login/logout)
 */
test('all users should be able to login and logout sequentially', async ({ page }) => {
    console.log('\n[TEST] Sequential login/logout for all users');
    
    for (const [key, user] of Object.entries(TEST_USERS)) {
        console.log(`\nTesting ${user.username}...`);
        
        await loginUser(page, user.username, user.password);
        
        // Verify logged in (domain-agnostic)
        const pathname = new URL(page.url()).pathname;
        expect(pathname).not.toContain('/auth/login');
        
        await logoutUser(page);
        
        // Verify logged out
        await page.goto(LOGIN_URL);
        const loginForm = await page.locator('input[name="username"]').isVisible();
        expect(loginForm).toBeTruthy();
        
        console.log(`✓ ${user.username} login/logout cycle complete`);
    }
    
    console.log('\n✓ All users successfully completed login/logout cycle');
});

/**
 * Test: Admin-specific access verification
 */
test('testadmin should have access to all admin pages', async ({ page }) => {
    console.log('\n[TEST] Admin full access verification');
    
    await loginUser(page, 'testadmin', 'password');
    
    const adminPages = [
        '/index.php/admin/index',
        '/index.php/backend/users',
        '/index.php/authorization/user_roles',
        '/index.php/comptes/balance'
    ];
    
    for (const url of adminPages) {
        const result = await checkPageAccess(page, url);
        console.log(`  ${url}: ${result.allowed ? '✓ ALLOWED' : '✗ DENIED'}`);
        expect(result.allowed).toBeTruthy();
    }
    
    await logoutUser(page);
    
    console.log('✓ Admin access verification complete');
});

/**
 * Test: Basic user restricted access
 */
test('testuser should NOT have access to admin/treasurer pages', async ({ page }) => {
    console.log('\n[TEST] Basic user restriction verification');
    
    await loginUser(page, 'testuser', 'password');
    
    const restrictedPages = [
        '/index.php/admin/index',
        '/index.php/backend/users',
        '/index.php/compta/ecritures',
        '/index.php/comptes/balance'
    ];
    
    for (const url of restrictedPages) {
        const result = await checkPageAccess(page, url);
        console.log(`  ${url}: ${result.allowed ? '✗ WRONGLY ALLOWED' : '✓ CORRECTLY DENIED'}`);
        expect(result.allowed).toBeFalsy();
    }
    
    await logoutUser(page);
    
    console.log('✓ Basic user restriction verification complete');
});

/**
 * Test: Treasurer financial access
 */
test('testtresorier should have access to financial pages', async ({ page }) => {
    console.log('\n[TEST] Treasurer financial access verification');
    
    await loginUser(page, 'testtresorier', 'password');
    
    const financialPages = [
        '/index.php/compta/ecritures',
        '/index.php/comptes/page',
        '/index.php/comptes/balance',
        '/index.php/compta/mon_compte/1'
    ];
    
    for (const url of financialPages) {
        const result = await checkPageAccess(page, url);
        console.log(`  ${url}: ${result.allowed ? '✓ ALLOWED' : '✗ DENIED'}`);
        expect(result.allowed).toBeTruthy();
    }
    
    // But should NOT have admin access
    const adminResult = await checkPageAccess(page, '/index.php/admin/index');
    console.log(`  /index.php/admin/index: ${adminResult.allowed ? '✗ WRONGLY ALLOWED' : '✓ CORRECTLY DENIED'}`);
    expect(adminResult.allowed).toBeFalsy();
    
    await logoutUser(page);
    
    console.log('✓ Treasurer access verification complete');
});
