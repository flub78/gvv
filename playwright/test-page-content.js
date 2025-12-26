const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  
  // Login
  await page.goto('http://gvv.net/auth/login');
  await page.fill('input[name="username"]', 'testadmin');
  await page.fill('input[name="password"]', 'password');
  await page.selectOption('select[name="section"]', '1');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  
  // Navigate to vols_planeur/page  
  await page.goto('http://gvv.net/vols_planeur/page');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1000);
  
  // Get all h3 elements
  const h3Elements = await page.locator('h3').all();
  console.log(`Found ${h3Elements.length} h3 elements:`);
  for (let i = 0; i < h3Elements.length; i++) {
    const text = await h3Elements[i].textContent();
    console.log(`  h3[${i}]: "${text}"`);
  }
  
  // Search for text containing "Planche"
  const plancheElements = await page.locator('text=/Planche/i').all();
  console.log(`\nFound ${plancheElements.length} elements containing "Planche":`);
  for (let i = 0; i < Math.min(plancheElements.length, 5); i++) {
    const text = await plancheElements[i].textContent();
    const tagName = await plancheElements[i].evaluate(el => el.tagName);
    const isVisible = await plancheElements[i].isVisible();
    console.log(`  [${i}] <${tagName}> visible=${isVisible}: "${text.substring(0, 50)}"`);
  }
  
  await browser.close();
})();
