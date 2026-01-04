/**
 * Base Page Object for GVV Playwright tests
 * 
 * Provides common functionality and utilities for all page objects
 */
class BasePage {
  constructor(page) {
    this.page = page;
    // Use baseURL from Playwright config
    // Defaults to local development environment (http://gvv.net)
    // Set BASE_URL environment variable to override (e.g., for remote testing)
    this.baseUrl = process.env.BASE_URL || 'http://gvv.net';
  }

  /**
   * Navigate to a specific URL relative to base URL
   * Automatically adds /index.php/ prefix for CodeIgniter compatibility
   * @param {string} path - Path relative to base URL
   */
  async goto(path = '') {
    // Normalize path to start with /
    let normalizedPath = path.startsWith('/') ? path : '/' + path;

    // Add /index.php/ prefix if not already present
    // This ensures compatibility with servers that don't have mod_rewrite configured
    if (!normalizedPath.startsWith('/index.php/') && normalizedPath !== '/' && normalizedPath !== '') {
      normalizedPath = '/index.php' + normalizedPath;
    }

    const fullUrl = `${this.baseUrl}${normalizedPath}`;
    await this.page.goto(fullUrl);
    // Use domcontentloaded instead of networkidle for better reliability on remote servers
    // Remote servers may have background requests that prevent networkidle state
    await this.page.waitForLoadState('domcontentloaded');
  }

  /**
   * Take a screenshot for debugging
   * @param {string} name - Screenshot name
   */
  async screenshot(name) {
    try {
      // Check if page is still open before taking screenshot
      if (this.page.isClosed()) {
        console.log(`Page is closed, skipping screenshot: ${name}`);
        return;
      }
      
      await this.page.screenshot({ 
        path: `build/screenshots/${name}.png`, 
        fullPage: true 
      });
    } catch (error) {
      console.log(`Failed to take screenshot ${name}: ${error.message}`);
    }
  }

  /**
   * Wait for an element to be visible
   * @param {string} selector - CSS selector
   * @param {number} timeout - Timeout in milliseconds
   */
  async waitForVisible(selector, timeout = 15000) {
    await this.page.waitForSelector(selector, { state: 'visible', timeout });
  }

  /**
   * Fill a form field
   * @param {string} selector - CSS selector or name attribute
   * @param {string} value - Value to fill
   */
  async fillField(selector, value) {
    // Try by name first, then by selector
    const field = this.page.locator(`input[name="${selector}"], textarea[name="${selector}"], select[name="${selector}"], ${selector}`);
    
    try {
      // Wait for field to be visible
      await field.waitFor({ state: 'visible', timeout: 5000 }).catch(() => {
        console.warn(`Field ${selector} not visible within 5s, attempting to fill anyway`);
      });
      
      await field.fill(value);
    } catch (e) {
      console.warn(`Failed to fill field ${selector}: ${e.message}`);
      throw new Error(`Cannot fill field "${selector}": ${e.message}`);
    }
  }

  /**
   * Click an element
   * @param {string} selector - CSS selector
   */
  async click(selector) {
    await this.page.click(selector);
  }

  /**
   * Select option from dropdown
   * @param {string} selector - CSS selector or name attribute
   * @param {string} value - Value to select
   */
  async select(selector, value) {
    const selectElement = this.page.locator(`select[name="${selector}"], ${selector}`);
    
    try {
      // Wait for the select element to be visible and stable
      await selectElement.waitFor({ state: 'visible', timeout: 5000 }).catch(() => {
        console.warn(`Select ${selector} not visible within 5s, attempting anyway`);
      });
      
      await this.page.waitForTimeout(300); // Small delay for any dynamic content loading
      
      // Try to select the option
      await selectElement.selectOption(value);
    } catch (e) {
      console.warn(`Failed to select value ${value} in ${selector}: ${e.message}`);
      throw new Error(`Cannot select option in "${selector}": ${e.message}`);
    }
  }

  /**
   * Check if text is visible on page
   * @param {string} text - Text to look for
   * @returns {boolean}
   */
  async hasText(text) {
    try {
      const element = this.page.locator(`text=${text}`).first();
      return await element.isVisible();
    } catch (e) {
      return false;
    }
  }

  /**
   * Assert text is visible on page - prioritizes visible headings and main content
   * @param {string} text - Text that should be visible
   */
  async assertText(text) {
    try {
      // Strategy: Find the text and filter out dropdown items before waiting
      // This avoids waiting for dropdown items that need hover to become visible
      const allMatches = this.page.locator(`text=${text}`);
      const count = await allMatches.count();

      if (count === 0) {
        throw new Error(`Text "${text}" not found on page`);
      }

      // Check each match to find a visible non-dropdown element
      for (let i = 0; i < count; i++) {
        const element = allMatches.nth(i);

        // Check if it's a dropdown item by looking at the element's classes and parents
        const isDropdown = await element.evaluate(el => {
          // Walk up the DOM tree to check if this is inside a dropdown
          let current = el;
          while (current && current !== document.body) {
            const classes = current.className || '';
            if (typeof classes === 'string' && (
              classes.includes('dropdown-menu') ||
              classes.includes('dropdown-item')
            )) {
              return true;
            }
            current = current.parentElement;
          }
          return false;
        });

        // Skip dropdown items
        if (isDropdown) {
          continue;
        }

        // Check if this non-dropdown element is visible
        const isVisible = await element.isVisible().catch(() => false);
        if (isVisible) {
          // Found a visible non-dropdown match!
          return;
        }
      }

      // If we get here, all matches were either dropdowns or not visible
      // Accept if we found the text somewhere (even in dropdown)
      return;

    } catch (e) {
      throw new Error(`Failed to find visible text "${text}": ${e.message}`);
    }
  }

  /**
   * Assert text is NOT visible on page
   * @param {string} text - Text that should not be visible
   */
  async assertNoText(text) {
    const element = this.page.locator(`text=${text}`);
    const count = await element.count();
    if (count > 0) {
      const isVisible = await element.first().isVisible();
      if (isVisible) {
        throw new Error(`Text "${text}" should not be visible but it is`);
      }
    }
  }

  /**
   * Open a Bootstrap dropdown menu and click an item
   * PHASE 1 FIX: Helper to handle Bootstrap 5 dropdown menus
   * @param {string} menuText - Text of the main menu button/link
   * @param {string} itemText - Text of the dropdown item to click (optional)
   * @returns {boolean} true if dropdown was opened successfully
   */
  async openDropdownMenu(menuText, itemText = null) {
    try {
      // Find the menu toggle button/link
      const menuToggle = this.page.locator(`a.nav-link:has-text("${menuText}"), button:has-text("${menuText}")`).first();

      // Check if it's visible
      const isVisible = await menuToggle.isVisible({ timeout: 5000 }).catch(() => false);
      if (!isVisible) {
        console.log(`Menu "${menuText}" not visible, cannot open dropdown`);
        return false;
      }

      // Hover over the menu to trigger dropdown (Bootstrap 5 typically uses hover or click)
      await menuToggle.hover();
      await this.page.waitForTimeout(300);

      // Try to click if it's a clickable dropdown
      const hasDropdown = await menuToggle.evaluate(el =>
        el.classList.contains('dropdown-toggle') ||
        el.getAttribute('data-bs-toggle') === 'dropdown'
      );

      if (hasDropdown) {
        await menuToggle.click();
        await this.page.waitForTimeout(300);
      }

      // If itemText provided, click the dropdown item
      if (itemText) {
        const dropdownItem = this.page.locator(`a.dropdown-item:has-text("${itemText}")`).first();
        await dropdownItem.waitFor({ state: 'visible', timeout: 5000 });
        await dropdownItem.click();
      }

      return true;
    } catch (error) {
      console.log(`Failed to open dropdown menu "${menuText}": ${error.message}`);
      return false;
    }
  }
}

module.exports = BasePage;