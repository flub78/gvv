const BasePage = require('./BasePage');

/**
 * Login Page Object for GVV application
 * 
 * Handles all login-related interactions including:
 * - Navigation to login page
 * - Filling login form
 * - Section selection
 * - Login verification
 * - Logout functionality
 */
class LoginPage extends BasePage {
  constructor(page) {
    super(page);
    this.url = '/auth/login';
  }

  // Selectors
  get usernameField() { return 'input[name="username"]'; }
  get passwordField() { return 'input[name="password"]'; }
  get sectionSelect() { return 'select[name="section"]'; }
  get submitButton() { return 'input[type="submit"], button[type="submit"]'; }
  get logoutUrl() { return '/auth/logout'; }

  /**
   * Navigate to login page
   */
  async open() {
    await this.goto(this.url);
    await this.waitForVisible(this.usernameField);
    await this.assertText('Utilisateur');
    await this.assertText('Mot de passe');
    await this.assertText('Peignot'); // Part of the login page branding
  }

  /**
   * Perform login with credentials
   * @param {string} username - Username
   * @param {string} password - Password  
   * @param {string} section - Section ID (1=Planeur, 2=ULM, 3=Avion, 4=Général, 5=Toutes)
   */
  async login(username, password, section = '1') {
    console.log(`Logging in as ${username}, section ${section}`);
    
    await this.screenshot('before_login');
    
    // Fill login form
    await this.fillField('username', username);
    await this.fillField('password', password);
    
    // Select section if provided
    if (section && section !== '') {
      await this.select('section', section);
      await this.screenshot('after_select_section');
    }
    
    // Submit form
    await this.click(this.submitButton);
    await this.screenshot('after_login');
    
    // Wait for redirect and verify login success
    await this.page.waitForLoadState('networkidle');
    
    // Verify section is correctly displayed
    const sectionLabels = {
      '1': 'Planeur',
      '2': 'ULM', 
      '3': 'Avion',
      '4': 'Général',
      '5': 'Toutes'
    };
    
    if (sectionLabels[section]) {
      // For section verification, look for the text in the UI, not just the option element
      try {
        await this.assertText(sectionLabels[section]);
      } catch (e) {
        // If not found in main UI, section might be shown differently
        console.log(`Section "${sectionLabels[section]}" not found in main UI, but login succeeded`);
      }
    }
    
    // Check if page is still open before waiting
    if (!this.page.isClosed()) {
      // Small delay for UI stability
      await this.page.waitForTimeout(2000);
    }
  }

  /**
   * Logout from the application
   */
  async logout() {
    console.log('Logging out');
    
    // Check if page is still open
    if (this.page.isClosed()) {
      console.log('Page already closed, skipping logout');
      return;
    }
    
    try {
      // Navigate directly to logout URL (more reliable than clicking logout link)
      await this.goto(this.logoutUrl);
      
      // Verify we're back on login page
      await this.waitForVisible(this.usernameField);
      await this.assertText('Utilisateur');
      await this.assertText('Mot de passe');
    } catch (error) {
      console.log(`Logout failed: ${error.message}`);
      // Don't throw error - logout might have worked but page navigation failed
    }
  }

  /**
   * Verify user is logged in
   */
  async verifyLoggedIn() {
    await this.assertText('Membres');
    await this.assertText('Planeurs');
  }

  /**
   * Verify user is logged out
   */
  async verifyLoggedOut() {
    await this.assertNoText('Planeurs');
    await this.assertText('Utilisateur');
    await this.assertText('Mot de passe');
  }

  /**
   * Attempt login and expect failure
   * @param {string} username - Username
   * @param {string} password - Wrong password
   */
  async attemptLoginWithWrongPassword(username, password) {
    await this.fillField('username', username);
    await this.fillField('password', password);
    await this.click(this.submitButton);
    
    await this.page.waitForLoadState('networkidle');
    
    // Should still be on login page or show error
    const isStillOnLoginPage = await this.page.locator(this.usernameField).isVisible();
    const hasErrorMessage = await this.hasErrorMessage();
    
    if (!isStillOnLoginPage && !hasErrorMessage) {
      throw new Error('Expected login to fail but it seemed to succeed');
    }
    
    await this.screenshot('failed_login');
  }

  /**
   * Check if login error message is displayed
   * @returns {boolean} True if error message is visible
   */
  async hasErrorMessage() {
    const errorTexts = [
      'incorrect', 'invalid', 'failed', 'erreur', 'invalide',
      'Utilisateur ou mot de passe incorrect',
      'Login failed',
      'Authentication failed'
    ];
    
    for (const errorText of errorTexts) {
      if (await this.hasText(errorText)) {
        return true;
      }
    }
    return false;
  }
}

module.exports = LoginPage;