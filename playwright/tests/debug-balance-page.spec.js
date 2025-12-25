const { test } = require('@playwright/test');

test('Debug: Check balance page structure', async ({ page }) => {
    // Login
    await page.goto('/auth/login');
    await page.fill('input[name="username"]', 'testadmin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Navigate to balance page
    await page.goto('/comptes/balance');
    await page.waitForLoadState('networkidle');

    // Take screenshot
    await page.screenshot({ path: '/tmp/balance-page.png', fullPage: true });
    console.log('Screenshot saved to /tmp/balance-page.png');

    // Get page title
    const title = await page.title();
    console.log('Page title:', title);

    // Check if there are any tables
    const tables = await page.locator('table').count();
    console.log('Number of tables:', tables);

    // Check for links containing journal_compte
    const journalLinks = await page.locator('a[href*="journal_compte"]').count();
    console.log('Links containing journal_compte:', journalLinks);

    // Check for any links in tables
    const tableLinks = await page.locator('table a').count();
    console.log('Links in tables:', tableLinks);

    // Get first few table links
    if (tableLinks > 0) {
        const firstLinks = await page.locator('table a').evaluateAll(links =>
            links.slice(0, 5).map(l => ({ text: l.textContent, href: l.href }))
        );
        console.log('First table links:', JSON.stringify(firstLinks, null, 2));
    }

    // Check for accordion structure (balance might use accordions)
    const accordions = await page.locator('.accordion').count();
    console.log('Accordions found:', accordions);

    // Check for buttons or other navigation elements
    const buttons = await page.locator('button, .btn').count();
    console.log('Buttons found:', buttons);
});
