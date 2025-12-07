/**
 * Base Page Object for GVV Playwright tests
 * 
 * Provides common functionality and utilities for all page objects
 */
class BasePage {
  constructor(page) {
    this.page = page;
    this.baseUrl = process.env.BASE_URL || 'http://gvv.net';
  }

  /**
   * Navigate to a specific URL relative to base URL
   * @param {string} path - Path relative to base URL
   */
  async goto(path = '') {
    const fullUrl = `${this.baseUrl}${path.startsWith('/') ? path : '/' + path}`;
    await this.page.goto(fullUrl);
    await this.page.waitForLoadState('networkidle');
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
    await field.fill(value);
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
    
    // Wait for the select element to be visible and stable
    await selectElement.waitFor({ state: 'visible', timeout: 15000 });
    await this.page.waitForTimeout(500); // Small delay for any dynamic content loading
    
    // Try to select the option
    await selectElement.selectOption(value);
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
      // First try to find in visible headings (h1, h2, h3, h4, h5, h6)
      const headingSelector = `h1:has-text("${text}"), h2:has-text("${text}"), h3:has-text("${text}"), h4:has-text("${text}"), h5:has-text("${text}"), h6:has-text("${text}")`;
      const heading = this.page.locator(headingSelector).first();
      if (await heading.count() > 0) {
        await heading.waitFor({ state: 'visible' });
        return;
      }
      
      // Then try visible main content areas, excluding dropdown menus
      const contentSelector = `text=${text}`;
      const visibleElements = this.page.locator(contentSelector).locator('visible=true').and(this.page.locator(':not(.dropdown-item):not(.dropdown-menu a):not([style*="display: none"]):not([style*="visibility: hidden"])'));
      
      if (await visibleElements.count() > 0) {
        await visibleElements.first().waitFor({ state: 'visible' });
        return;
      }
      
      // Fallback to original method but only visible elements
      await this.page.waitForSelector(`text=${text}`, { state: 'visible' });
    } catch (e) {
      // Final fallback - try any visible element with the text
      const element = this.page.locator(`text=${text}`).locator('visible=true').first();
      await element.waitFor({ state: 'visible' });
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