const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    try {
        // Login
        console.log('Logging in...');
        await page.goto('http://gvv.net/auth/login');
        await page.fill('input[name="username"]', 'testadmin');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Select section if needed
        if (page.url().includes('select_section')) {
            console.log('Selecting section...');
            const firstSection = page.locator('table tbody tr').first();
            await firstSection.locator('a').first().click();
            await page.waitForLoadState('networkidle');
        }

        console.log('Current URL:', page.url());

        // Navigate to resultat_par_sections_detail
        console.log('Navigating to resultat_par_sections_detail/607...');
        await page.goto('http://gvv.net/comptes/resultat_par_sections_detail/607');
        await page.waitForTimeout(2000);

        console.log('Final URL:', page.url());

        // Check for errors
        const bodyText = await page.textContent('body');
        console.log('Page body (first 500 chars):', bodyText.substring(0, 500));

        // Take screenshot
        await page.screenshot({ path: '/tmp/resultat_detail_test.png', fullPage: true });
        console.log('Screenshot saved to /tmp/resultat_detail_test.png');

        // Check table
        const tableExists = await page.locator('table.resultat-table').count();
        console.log('Table count:', tableExists);

        if (tableExists > 0) {
            const headers = await page.locator('table.resultat-table thead th').allTextContents();
            console.log('Table headers:', headers);
        }

    } catch (error) {
        console.error('Error:', error.message);
        await page.screenshot({ path: '/tmp/resultat_detail_error.png', fullPage: true });
    } finally {
        await page.waitForTimeout(5000); // Wait to see the page
        await browser.close();
    }
})();
