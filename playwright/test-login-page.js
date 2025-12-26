const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  
  // Navigate to login page
  await page.goto('http://gvv.net/auth/login');
  await page.waitForLoadState('networkidle');
  
  // Fill login form
  await page.fill('input[name="username"]', 'testadmin');
  await page.fill('input[name="password"]', 'password');
  await page.selectOption('select[name="section"]', '1');
  
  // Submit login
  await page.click('button:has-text("Connexion")');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  
  // Navigate to a page
  await page.goto('http://gvv.net/vols_planeur/page');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1000);
  
  // Get page content to see what's there
  const bodyText = await page.locator('body').textContent();
  console.log('=== Page Text Content (first 1500 chars) ===');
  console.log(bodyText.substring(0, 1500));
  
  // Look for any username-related text
  console.log('\n=== Looking for username patterns ===');
  const hasTestadmin = bodyText.includes('testadmin');
  const hasAdmin = bodyText.includes('admin');
  const hasTest = bodyText.includes('test');
  
  console.log(`Contains 'testadmin': ${hasTestadmin}`);
  console.log(`Contains 'admin': ${hasAdmin}`);
  console.log(`Contains 'test': ${hasTest}`);
  
  // Check navigation bar
  console.log('\n=== Navigation Bar Content ===');
  try {
    const navBar = await page.locator('nav').first().textContent();
    console.log(navBar.substring(0, 500));
  } catch(e) {
    console.log('No nav found');
  }
  
  await page.screenshot({ path: 'build/screenshots/after-login.png', fullPage: true });
  console.log('\nScreenshot saved to build/screenshots/after-login.png');
  
  await browser.close();
})();
