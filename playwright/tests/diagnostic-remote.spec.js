/**
 * Diagnostic test to identify 404 issues on remote server
 *
 * This test checks:
 * 1. Basic connectivity
 * 2. URL routing (with and without index.php)
 * 3. Authentication
 * 4. Test data availability
 * 5. Assets loading
 *
 * Run with: BASE_URL=https://gvvg.flub78.net npx playwright test tests/diagnostic-remote.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

test.describe('Remote Server Diagnostics', () => {
    test('Check basic connectivity and home page', async ({ page }) => {
        console.log(`Testing against: ${process.env.BASE_URL || 'http://gvv.net'}`);

        const response = await page.goto('/');
        console.log(`Home page status: ${response.status()}`);
        expect(response.status()).toBe(200);

        const title = await page.title();
        console.log(`Page title: ${title}`);
    });

    test('Check URL routing with and without index.php', async ({ page }) => {
        // Test without index.php (requires mod_rewrite)
        console.log('\n=== Testing without index.php ===');
        const response1 = await page.goto('/index.php/auth/login');
        console.log(`/auth/login status: ${response1.status()}`);

        // Test with index.php (should always work)
        console.log('\n=== Testing with index.php ===');
        const response2 = await page.goto('/index.php/auth/login');
        console.log(`/index.php/auth/login status: ${response2.status()}`);

        // At least one should work
        const oneWorks = response1.status() === 200 || response2.status() === 200;
        expect(oneWorks).toBeTruthy();

        if (response1.status() !== 200) {
            console.log('⚠️  URLs without index.php return 404 - mod_rewrite might not be configured');
        }
        if (response2.status() !== 200) {
            console.log('⚠️  URLs with index.php return 404 - routing issue');
        }
    });

    test('Check authentication with testadmin user', async ({ page }) => {
        console.log('\n=== Testing authentication ===');

        await page.goto('/index.php/auth/login');

        // Check if login form exists
        const hasUsername = await page.locator('input[name="username"]').count() > 0;
        const hasPassword = await page.locator('input[name="password"]').count() > 0;

        console.log(`Login form found: ${hasUsername && hasPassword}`);
        expect(hasUsername && hasPassword).toBeTruthy();

        // Try to login
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('input[type="submit"], button[type="submit"]');
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        const stillOnLogin = currentUrl.includes('/auth/login');

        if (stillOnLogin) {
            console.log('⚠️  Login failed - testadmin user might not exist or wrong password');
            const errorMsg = await page.locator('body').textContent();
            console.log(`Page content sample: ${errorMsg.substring(0, 200)}`);
        } else {
            console.log('✓ Login successful');
        }
    });

    test('Check test data availability', async ({ page }) => {
        console.log('\n=== Testing data availability ===');

        // Login first
        await page.goto('/index.php/auth/login');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('input[type="submit"], button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Check if compte 23 exists
        const response = await page.goto('/index.php/compta/journal_compte/23');
        console.log(`Account 23 status: ${response.status()}`);

        if (response.status() === 404) {
            console.log('⚠️  Account 23 not found - test data might be missing');
        } else if (response.status() === 200) {
            const hasTable = await page.locator('table').count() > 0;
            const hasEntries = await page.locator('.gel-checkbox').count() > 0;
            console.log(`✓ Account 23 found, has table: ${hasTable}, has entries: ${hasEntries}`);
        }

        // Check balance page
        const balanceResponse = await page.goto('/index.php/comptes/balance');
        console.log(`Balance page status: ${balanceResponse.status()}`);

        if (balanceResponse.status() === 404) {
            console.log('⚠️  Balance page not found');
        }
    });

    test('Check network requests and assets', async ({ page }) => {
        console.log('\n=== Testing assets and resources ===');

        const failedRequests = [];
        const assetRequests = [];

        page.on('response', response => {
            const url = response.url();
            const status = response.status();

            // Track CSS, JS, images
            if (url.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico)$/)) {
                assetRequests.push({ url, status });
                if (status >= 400) {
                    failedRequests.push({ url, status });
                }
            }
        });

        await page.goto('/');
        await page.waitForLoadState('networkidle');

        console.log(`Total assets loaded: ${assetRequests.length}`);
        console.log(`Failed assets: ${failedRequests.length}`);

        if (failedRequests.length > 0) {
            console.log('\n⚠️  Failed asset requests:');
            failedRequests.forEach(req => {
                console.log(`  - ${req.status}: ${req.url}`);
            });
        }
    });

    test('Summary and recommendations', async ({ page }) => {
        console.log('\n=== DIAGNOSTIC SUMMARY ===');
        console.log('If you see 404 errors above, check:');
        console.log('1. mod_rewrite enabled and .htaccess deployed on remote server');
        console.log('2. testadmin user exists in remote database');
        console.log('3. Test data (account 23, etc.) exists in remote database');
        console.log('4. All application assets are deployed to remote server');
        console.log('5. Database connection configured correctly on remote');
    });
});
