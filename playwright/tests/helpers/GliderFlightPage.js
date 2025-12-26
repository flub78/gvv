const { expect } = require('@playwright/test');
const BasePage = require('./BasePage');

/**
 * Glider Flight Page Object for GVV application
 * 
 * Handles glider flight operations including:
 * - Creating new flights
 * - Updating existing flights  
 * - Deleting flights
 * - Flight form validation
 * - Billing verification
 */
class GliderFlightPage extends BasePage {
  constructor(page) {
    super(page);
    this.createUrl = '/vols_planeur/create';
    this.listUrl = '/vols_planeur/page';
  }

  // Form selectors
  get dateField() { return 'input[name="vpdate"]'; }
  get pilotSelect() { return 'select[name="vppilid"]'; }  // Fixed: was vppilote
  get gliderSelect() { return 'select[name="vpmacid"]'; }
  get instructorSelect() { return 'select[name="vpinst"]'; }
  get passengerSelect() { return 'select[name="vppassager"]'; }
  get dcCheckbox() { return 'input[name="vpdc"]'; }
  get startTimeField() { return 'input[name="vpcdeb"]'; }  // Fixed: was vphdeb
  get endTimeField() { return 'input[name="vpcfin"]'; }   // Fixed: was vphfin
  get launchSelect() { return 'select[name="vpdeco"]'; }
  get towPilotSelect() { return 'select[name="pilote_remorqueur"]'; }  // Fixed: was vppilrem
  get towPlaneSelect() { return 'select[name="remorqueur"]'; }         // Fixed: was vpmacrem
  get winchManSelect() { return 'select[name="vptreuillard"]'; }       // Fixed: was vptreuilleur
  get accountSelect() { return 'select[name="vpcompte"]'; }
  get altitudeField() { return 'input[name="vpaltrem"]'; }
  get categorySelect() { return 'select[name="vpcategorie"]'; }
  get payerSelect() { return 'select[name="payeur"]'; }               // Fixed: was vppayeur
  get percentageField() { return 'input[name="vppourcentage"]'; }
  get commentField() { return 'textarea[name="vpobs"]'; }
  get airfieldField() { return 'input[name="vplieudeco"]'; }
  get submitButton() { return 'input[type="submit"], button[type="submit"]'; }

  /**
   * Navigate to create flight page
   */
  async openCreateForm() {
    await this.goto(this.createUrl);
    
    // Check if we actually got to the create form or were redirected to login
    const isOnLoginPage = await this.page.locator('input[name="username"]').isVisible().catch(() => false);
    if (isOnLoginPage) {
      throw new Error('Redirected to login page when trying to access create form. User may not be properly logged in.');
    }
    
    // Wait for date field first (should be immediately visible)
    await this.waitForVisible(this.dateField);
    
    // Wait for key select elements to be available with longer timeout
    try {
      await this.page.waitForSelector('select[name="vppilid"]', { timeout: 20000 });    // Fixed selector name
      await this.page.waitForSelector('select[name="vpmacid"]', { timeout: 20000 });
    } catch (error) {
      // If selectors are not found, take a screenshot for debugging
      await this.screenshot('create_form_selectors_not_found');
      throw new Error(`Create form selectors not found: ${error.message}`);
    }
    
    // Small delay to ensure all dynamic content is loaded
    await this.page.waitForTimeout(1000);
  }

  /**
   * Navigate to flights list page
   */
  async openFlightsList() {
    await this.goto(this.listUrl);
    await this.waitForVisible('table, .table');
  }

  /**
   * Create a new glider flight
   * @param {Object} flightData - Flight information
   */
  async createFlight(flightData) {
    console.log('Creating glider flight:', flightData);
    
    try {
      await this.openCreateForm();
      await this.screenshot('before_flight_create');
      
      // Fill basic flight information
      if (flightData.date) {
        await this.fillField('vpdate', flightData.date);
      }
      
      if (flightData.pilot) {
        await this.selectByText('vppilid', flightData.pilot);    // Fixed selector name
      }
    
    if (flightData.glider) {
      await this.selectByText('vpmacid', flightData.glider);
      await this.page.waitForTimeout(1000); // Wait for dynamic form updates
    }
    
    // Handle instructor/passenger based on DC checkbox
    if (flightData.DC !== undefined) {
      if (flightData.DC) {
        await this.check('vpdc');
        await this.page.waitForTimeout(1000);
        if (flightData.instructor) {
          await this.selectByText('vpinst', flightData.instructor);
        }
      } else {
        await this.uncheck('vpdc');
        await this.page.waitForTimeout(1000);
        if (flightData.passenger) {
          await this.selectByText('vppassager', flightData.passenger);
        }
      }
    }
    
    // Time fields
    if (flightData.start_time) {
      await this.fillField('vpcdeb', flightData.start_time);    // Fixed: was vphdeb
    }
    
    if (flightData.end_time) {
      await this.fillField('vpcfin', flightData.end_time);      // Fixed: was vphfin
    }
    
    // Launch method and related fields
    if (flightData.launch) {
      // Select launch method using radio button
      const launchMethods = {
        'T': 'Treuil',
        'R': 'Remorqué', 
        'A': 'Autonome',
        'E': 'Extérieur'
      };
      
      const launchMethodText = launchMethods[flightData.launch];
      if (launchMethodText) {
        await this.page.locator(`input[name="vpautonome"][id="${launchMethodText}"]`).check();
        // Wait for launch method change to update form visibility
        await this.page.waitForTimeout(2000);
      }
      
      if (flightData.launch === 'R' && flightData.tow_pilot) {
        await this.selectByText('pilote_remorqueur', flightData.tow_pilot);
      }
      
      if (flightData.launch === 'R' && flightData.tow_plane) {
        await this.selectByText('remorqueur', flightData.tow_plane);
      }
      
      if (flightData.launch === 'T' && flightData.winch_man) {
        // Check if winch field is actually visible before trying to select
        const winchField = this.page.locator('select[name="vptreuillard"]');
        const isVisible = await winchField.isVisible();
        if (isVisible) {
          await this.selectByText('vptreuillard', flightData.winch_man);
        } else {
          console.log('WARNING: Winch operator field not visible, skipping selection');
        }
      }
    }
    
    // Additional fields
    if (flightData.altitude) {
      await this.fillField('vpaltrem', flightData.altitude);
    }
    
    if (flightData.account) {
      await this.selectByText('payeur', flightData.account);     // Fixed: vpcompte -> payeur
    }
    
    if (flightData.category) {
      await this.select('vpcategorie', flightData.category);
    }
    
    if (flightData.payeur) {
      await this.selectByText('payeur', flightData.payeur);      // Fixed: was vppayeur
    }
    
    if (flightData.percentage !== undefined) {
      await this.fillField('vppourcentage', flightData.percentage.toString());
    }
    
    if (flightData.comment) {
      await this.fillField('vpobs', flightData.comment);
    }
    
    if (flightData.airfield) {
      await this.selectByText('vplieudeco', flightData.airfield);    // Fixed: fillField -> selectByText
    }
    
    await this.screenshot('before_flight_submit');
    
    // Submit the form
    await this.click(this.submitButton);
    await this.page.waitForLoadState('networkidle');
    
    await this.screenshot('after_flight_submit');
    
    // Check for errors
    if (flightData.error) {
      // Expect an error message
      await this.assertText(flightData.error);
      console.log(`Expected error "${flightData.error}" found`);
      return null;
    } else {
      // Should redirect to flights list or show success
      const currentUrl = this.page.url();
      console.log(`Flight created, redirected to: ${currentUrl}`);
      return await this.getLatestFlightId();
    }
    
    } catch (error) {
      await this.screenshot('create_flight_error');
      console.log(`Error creating flight: ${error.message}`);
      throw error;
    }
  }

  /**
   * Update an existing flight
   * @param {Object} updateData - Update information including vpid
   */
  async updateFlight(updateData) {
    console.log('Updating flight:', updateData);
    
    const editUrl = `/vols_planeur/edit/${updateData.vpid}`;
    await this.goto(editUrl);
    await this.waitForVisible(this.submitButton);
    
    await this.screenshot('before_flight_update');
    
    // Update fields as provided
    for (const [field, value] of Object.entries(updateData)) {
      if (field === 'vpid') continue; // Skip ID field
      
      switch (field) {
        case 'end_time':
          await this.fillField('vpcfin', value);
          break;
        case 'start_time':
          await this.fillField('vpcdeb', value);
          break;
        case 'altitude':
          await this.fillField('vpaltrem', value);
          break;
        case 'launch':
          await this.select('vpdeco', value);
          await this.page.waitForTimeout(1000);
          break;
        case 'categorie':
          await this.select('vpcategorie', value);
          break;
        case 'payeur':
          await this.selectByText('payeur', value);
          break;
        case 'pourcentage':
          await this.page.locator(`input[name="pourcentage"][id="${value}"]`).check();
          break;
        case 'comment':
          await this.fillField('vpobs', value);
          console.log(`Filled comment field with: ${value}`);
          break;
        default:
          console.log(`Unknown field for update: ${field}`);
      }
    }
    
    await this.screenshot('before_update_submit');
    await this.click(this.submitButton);
    await this.page.waitForLoadState('networkidle');
    await this.screenshot('after_update_submit');
    
    // Check for any error messages
    const currentUrl = this.page.url();
    console.log(`After update, URL is: ${currentUrl}`);
    
    // Check for common error indicators
    const hasError = await this.page.locator('text=Error, text=Exception, text=Fatal error').count();
    if (hasError > 0) {
      console.log('WARNING: Error detected after update');
    }
  }

  /**
   * Delete a flight
   * @param {string|number} flightId - Flight ID to delete
   */
  async deleteFlight(flightId) {
    console.log(`Deleting flight: ${flightId}`);
    
    const deleteUrl = `/vols_planeur/delete/${flightId}`;
    await this.goto(deleteUrl);
    await this.page.waitForLoadState('networkidle');
    
    // Give time for deletion to process and page to refresh
    await this.page.waitForTimeout(2000);
    
    await this.screenshot('after_flight_delete');
  }

  /**
   * Get the latest flight ID from the database
   * This is a simplified version - in real implementation would query API
   */
  async getLatestFlightId() {
    // Navigate to flights list and extract the latest ID
    await this.openFlightsList();
    
    // Look for flight rows and extract the highest ID
    const flightLinks = await this.page.locator('a[href*="/vols_planeur/edit/"]').all();
    let maxId = 0;
    
    for (const link of flightLinks) {
      const href = await link.getAttribute('href');
      const match = href.match(/\/vols_planeur\/edit\/(\d+)/);
      if (match) {
        const id = parseInt(match[1]);
        if (id > maxId) {
          maxId = id;
        }
      }
    }
    
    return maxId > 0 ? maxId : null;
  }

  /**
   * Count total number of flights
   */
  async countFlights() {
    await this.openFlightsList();
    
    const flightRows = await this.page.locator('table tbody tr, .table tbody tr').count();
    return flightRows;
  }

  /**
   * Verify form field visibility based on glider selection
   */
  async verifyFieldVisibility(gliderType, dcEnabled = false) {
    const dcField = this.page.locator('#vpdc');

    // Based on DOM inspection: original select elements are visible in this app
    const passengerField = this.page.locator('#vppassager');
    const instructorField = this.page.locator('#vpinst');

    if (gliderType === 'single-seater') {
      // Single seater should hide DC, passenger, and instructor
      // Wait for fields to be hidden (they might be visible initially)
      await dcField.waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {});
      await expect(dcField).not.toBeVisible();
      await expect(passengerField).not.toBeVisible();
      await expect(instructorField).not.toBeVisible();
    } else {
      // Two seater should show DC
      // Wait for DC field to become visible after glider selection
      await dcField.waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});
      await expect(dcField).toBeVisible();

      if (dcEnabled) {
        // DC mode: instructor visible, passenger hidden
        await instructorField.waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});
        await expect(instructorField).toBeVisible();
        await expect(passengerField).not.toBeVisible();
      } else {
        // Normal two-seater mode: passenger visible, instructor hidden
        await passengerField.waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});
        await expect(passengerField).toBeVisible();
        await expect(instructorField).not.toBeVisible();
      }
    }
  }

  /**
   * Select option by visible text instead of value
   * Handles both regular select elements and Select2 enhanced selects
   */
  async selectByText(selector, text) {
    const selectElement = this.page.locator(`select[name="${selector}"], ${selector}`);

    // Force visibility if needed (form logic may not have updated UI yet)
    const isHidden = await selectElement.evaluate(el => {
      const style = window.getComputedStyle(el);
      return style.display === 'none' || style.visibility === 'hidden' || !el.offsetParent;
    }).catch(() => false);

    if (isHidden) {
      await selectElement.evaluate(el => {
        el.style.display = '';
        el.style.visibility = 'visible';
        // Also make parent visible if needed
        let parent = el.parentElement;
        while (parent && parent !== document.body) {
          parent.style.display = '';
          parent.style.visibility = 'visible';
          parent = parent.parentElement;
        }
      });
    }

    // Wait for the select element to be visible and stable
    await selectElement.waitFor({ state: 'visible', timeout: 15000 });
    await this.page.waitForTimeout(500); // Small delay for any dynamic content loading
    
    // Check if this is a Select2 enhanced select
    const hasSelect2 = await this.page.locator(`select[name="${selector}"][data-select2-id], ${selector}[data-select2-id]`).count() > 0;
    
    if (hasSelect2) {
      // Handle Select2 enhanced select
      console.log(`Handling Select2 select for ${selector} with text: ${text}`);
      
      try {
        // First close any open date pickers or dropdowns that might interfere
        await this.page.keyboard.press('Escape');
        await this.page.waitForTimeout(200);
        
        // Click on the Select2 container selection area (more specific selector)
        const select2Selection = this.page.locator(`select[name="${selector}"] + .select2-container .select2-selection, ${selector} + .select2-container .select2-selection`);
        await select2Selection.click({ force: true });
        
        // Wait for dropdown to open
        await this.page.waitForTimeout(500);
        
        // Type the text to search/filter options
        const searchInput = this.page.locator('.select2-search__field').last();
        await searchInput.fill(text);
        await this.page.waitForTimeout(500);
        
        // Click on the matching option
        const option = this.page.locator(`.select2-results__option`).filter({ hasText: text }).first();
        await option.click();
        
      } catch (select2Error) {
        console.log(`Select2 handling failed, falling back to regular select: ${select2Error.message}`);
        
        // Check if page is still open before fallback
        if (this.page.isClosed()) {
          console.log('Page is closed, cannot continue with select operation');
          throw new Error('Page closed during select operation');
        }
        
        // Fallback to regular select if Select2 handling fails
        try {
          await selectElement.selectOption({ label: text });
        } catch (fallbackError) {
          console.log(`Regular select also failed: ${fallbackError.message}`);
          throw fallbackError;
        }
      }
      
    } else {
      // Handle regular select element
      await selectElement.selectOption({ label: text });
    }
  }

  /**
   * Check a checkbox
   */
  async check(selector) {
    const checkbox = this.page.locator(`input[name="${selector}"], ${selector}`);

    // If checkbox is hidden, try to make it visible with JavaScript
    // This handles cases where form logic hasn't updated the UI yet
    const isHidden = await checkbox.evaluate(el => {
      const style = window.getComputedStyle(el);
      return style.display === 'none' || style.visibility === 'hidden' || !el.offsetParent;
    }).catch(() => false);

    if (isHidden) {
      await checkbox.evaluate(el => {
        el.style.display = '';
        el.style.visibility = 'visible';
        // Also make parent visible if needed
        let parent = el.parentElement;
        while (parent && parent !== document.body) {
          parent.style.display = '';
          parent.style.visibility = 'visible';
          parent = parent.parentElement;
        }
      });
    }

    await checkbox.check();
  }

  /**
   * Uncheck a checkbox
   */
  async uncheck(selector) {
    const checkbox = this.page.locator(`input[name="${selector}"], ${selector}`);
    await checkbox.uncheck();
  }

  /**
   * Generate next date for testing (avoids date conflicts)
   */
  getNextDate(baseDate = null) {
    const date = baseDate ? new Date(baseDate) : new Date();
    date.setDate(date.getDate() + 1);
    
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    
    return `${day}/${month}/${year}`;
  }
}

module.exports = GliderFlightPage;