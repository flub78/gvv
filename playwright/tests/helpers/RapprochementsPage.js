/**
 * Page Object Model for Rapprochements (Bank Reconciliation) page
 *
 * Provides methods for interacting with the bank reconciliation interface,
 * including uploading bank statements, switching tabs, and searching operations.
 */

const BasePage = require('./BasePage');
const path = require('path');

class RapprochementsPage extends BasePage {
  constructor(page) {
    super(page);
    this.uploadUrl = '/rapprochements/select_releve';
    this.rapprochementsUrl = '/rapprochements/import_releve_from_file';
  }

  /**
   * Upload a bank statement CSV file
   * @param {string} fileName - Name of the CSV file in test-data directory
   */
  async uploadBankStatement(fileName = 'sample_bank_statement.csv') {
    console.log(`Uploading bank statement: ${fileName}`);

    // Navigate to upload page
    await this.goto(this.uploadUrl);
    await this.page.waitForLoadState('domcontentloaded');

    // Get the file path
    const filePath = path.join(__dirname, '../../test-data', fileName);

    // Set the file input
    const fileInput = this.page.locator('input[type="file"][name="userfile"]');
    await fileInput.setInputFiles(filePath);

    // Click submit button
    await this.page.locator('input[type="submit"], button[type="submit"]').click();
    await this.page.waitForLoadState('domcontentloaded');

    // Wait for the rapprochements page to load with tabs
    await this.page.waitForTimeout(1000);

    console.log('Bank statement uploaded successfully');
  }

  /**
   * Navigate to rapprochements page (requires bank statement to be uploaded first)
   */
  async navigateToRapprochements() {
    await this.goto(this.rapprochementsUrl);
    await this.page.waitForLoadState('domcontentloaded');
    await this.page.waitForTimeout(1000);
  }

  /**
   * Upload bank statement and navigate to rapprochements page
   * Convenience method combining both steps
   */
  async uploadAndNavigate(fileName = 'sample_bank_statement.csv') {
    await this.uploadBankStatement(fileName);
    // uploadBankStatement already navigates to rapprochements page after upload
  }

  /**
   * Click on a tab
   * @param {string} tabId - Tab ID ('gvv-tab' or 'openflyers-tab')
   */
  async clickTab(tabId) {
    const tab = this.page.locator(`#${tabId}`);
    await tab.waitFor({ state: 'visible', timeout: 10000 });
    await tab.click();
    await this.page.waitForTimeout(500);
  }

  /**
   * Get the active tab ID
   * @returns {Promise<string>} The ID of the active tab
   */
  async getActiveTabId() {
    const activeTab = this.page.locator('.nav-link.active');
    return await activeTab.getAttribute('id');
  }

  /**
   * Check if a tab is active
   * @param {string} tabId - Tab ID to check
   * @returns {Promise<boolean>} True if tab is active
   */
  async isTabActive(tabId) {
    const tab = this.page.locator(`#${tabId}`);
    const classes = await tab.getAttribute('class');
    return classes.includes('active');
  }

  /**
   * Search/filter bank operations
   * @param {string} searchTerm - Text to search for
   */
  async searchBankOperations(searchTerm) {
    const searchBox = this.page.locator('#searchReleveBanque');
    await searchBox.fill(searchTerm);
    await this.page.waitForTimeout(500);
  }

  /**
   * Clear bank operations search
   */
  async clearBankSearch() {
    const clearButton = this.page.locator('button[onclick*="clearBankSearch"]');
    await clearButton.click();
    await this.page.waitForTimeout(500);
  }

  /**
   * Get the number of visible bank operations
   * @returns {Promise<number>} Count of visible operations
   */
  async getVisibleOperationsCount() {
    return await this.page.locator('table.operations:visible').count();
  }

  /**
   * Get saved active tab from sessionStorage
   * @returns {Promise<string|null>} Saved tab ID or null
   */
  async getSavedActiveTab() {
    return await this.page.evaluate(() => sessionStorage.getItem('rapprochements_active_tab'));
  }
}

module.exports = RapprochementsPage;
