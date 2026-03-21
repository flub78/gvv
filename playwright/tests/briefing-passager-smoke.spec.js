/**
 * Playwright smoke tests for Briefing Passager feature
 *
 * Tests UC1 (upload), UC3 (admin list) and UC2 (digital signature).
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/briefing-passager-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL        = '/index.php/auth/login';
const VLD_LIST_URL     = '/index.php/vols_decouverte';
const BRIEFING_URL     = '/index.php/briefing_passager';
const ADMIN_LIST_URL   = '/index.php/briefing_passager/admin_list';

const ADMIN_USER = { username: 'testadmin', password: 'password' };
const PILOT_USER = { username: 'testuser',  password: 'password' };

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

// --- UC1: Access standalone briefing form ---

test('UC1: pilot can access standalone briefing form', async ({ page }) => {
    await login(page, PILOT_USER);
    await page.goto(BRIEFING_URL);
    await page.waitForLoadState('networkidle');

    // Page should load without error
    await expect(page).not.toHaveURL(/error|403|404/);
    // Should contain the search field
    await expect(page.locator('#vld_search')).toBeVisible();
});

// --- UC1: VLD list has briefing icon ---

test('UC1: VLD list shows briefing icon column', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(VLD_LIST_URL);
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);

    // At least one clipboard-check icon should be present
    const briefingIcons = page.locator('a[href*="briefing_passager/upload"] i.fa-clipboard-check');
    const count = await briefingIcons.count();
    expect(count).toBeGreaterThan(0);
});

// --- UC1: Clicking briefing icon opens upload form ---

test('UC1: briefing icon opens upload form for that VLD', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(VLD_LIST_URL);
    await page.waitForLoadState('networkidle');

    // Click first briefing icon
    const firstLink = page.locator('a[href*="briefing_passager/upload"]').first();
    const href = await firstLink.getAttribute('href');
    await firstLink.click();
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);
    // Upload form should contain a file input
    await expect(page.locator('input[name="userfile"]')).toBeVisible();
});

// --- UC1: AJAX search returns results ---

test('UC1: AJAX VLD search returns results for known name', async ({ page }) => {
    await login(page, PILOT_USER);

    // Call the search endpoint directly
    const response = await page.request.get('/index.php/briefing_passager/search_vld?q=a');
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(Array.isArray(body)).toBeTruthy();
});

// --- UC3: Admin can access briefing list ---

test('UC3: admin can access briefing admin list', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);
    // Should contain the days filter
    await expect(page.locator('input[name="days"]')).toBeVisible();
});

// --- UC3: Admin can change period filter ---

test('UC3: admin can filter briefings by period', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="days"]', '365');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);
    await expect(page.locator('input[name="days"]')).toHaveValue('365');
});

// --- UC3: PDF export link is present ---

test('UC3: PDF export link is present on admin list', async ({ page }) => {
    await login(page, ADMIN_USER);
    await page.goto(ADMIN_LIST_URL);
    await page.waitForLoadState('networkidle');

    const pdfLink = page.locator('a[href*="briefing_passager/export_pdf"]');
    await expect(pdfLink).toBeVisible();
});

// --- UC2: Generate digital signature link ---

test('UC2: admin can generate a digital signature link for a VLD', async ({ page }) => {
    await login(page, ADMIN_USER);

    // Navigate to generate_link for a known VLD
    await page.goto('/index.php/briefing_passager/generate_link/16143');
    await page.waitForLoadState('networkidle');

    await expect(page).not.toHaveURL(/error|403|404/);
    // QR code image should be present
    await expect(page.locator('img[src^="data:image/png;base64,"]')).toBeVisible();
    // Sign URL input should be present and contain briefing_sign
    const signUrlInput = page.locator('#sign_url');
    await expect(signUrlInput).toBeVisible();
    const signUrl = await signUrlInput.inputValue();
    expect(signUrl).toContain('briefing_sign');
});

test('UC2: anonymous user can access sign page via token link', async ({ browser }) => {
    // First generate a token as admin
    const adminCtx = await browser.newContext();
    const adminPage = await adminCtx.newPage();
    await login(adminPage, ADMIN_USER);
    await adminPage.goto('/index.php/briefing_passager/generate_link/16143');
    await adminPage.waitForLoadState('networkidle');

    const signUrl = await adminPage.locator('#sign_url').inputValue();
    await adminCtx.close();

    expect(signUrl).toMatch(/briefing_sign\//);

    // Now access sign URL as anonymous user (new context = no cookies)
    const anonCtx = await browser.newContext();
    const anonPage = await anonCtx.newPage();
    await anonPage.goto(signUrl);
    await anonPage.waitForLoadState('networkidle');

    // Should show sign page (not login redirect)
    await expect(anonPage).not.toHaveURL(/auth\/login/);
    await expect(anonPage.locator('canvas#signature-pad')).toBeVisible();
    await anonCtx.close();
});

test('UC2: invalid token shows error page', async ({ page }) => {
    const fakeToken = 'a'.repeat(64);
    await page.goto('/index.php/briefing_sign/' + fakeToken);
    await page.waitForLoadState('networkidle');

    // Should show error page with an h3 containing error text
    await expect(page.locator('h3')).toBeVisible();
});
