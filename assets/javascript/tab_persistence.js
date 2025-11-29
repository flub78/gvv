/**
 * Tab Persistence for Rapprochements Page
 *
 * Saves and restores the active tab selection using sessionStorage.
 * This ensures that when the page is reloaded, the user returns to
 * the same tab they were viewing.
 *
 * Storage key: 'rapprochements_active_tab'
 */

(function() {
    'use strict';

    // Key for storing active tab in sessionStorage
    const STORAGE_KEY = 'rapprochements_active_tab';

    /**
     * Save the currently active tab to sessionStorage
     * @param {string} tabId - The ID of the tab to save (e.g., 'openflyers-tab', 'gvv-tab')
     */
    function saveActiveTab(tabId) {
        try {
            sessionStorage.setItem(STORAGE_KEY, tabId);
            console.log('Saved active tab:', tabId);
        } catch (e) {
            console.error('Error saving tab to sessionStorage:', e);
        }
    }

    /**
     * Get the saved active tab from sessionStorage
     * @returns {string|null} The saved tab ID, or null if none saved
     */
    function getSavedTab() {
        try {
            return sessionStorage.getItem(STORAGE_KEY);
        } catch (e) {
            console.error('Error reading tab from sessionStorage:', e);
            return null;
        }
    }

    /**
     * Activate the specified tab
     * @param {string} tabId - The ID of the tab button to activate
     */
    function activateTab(tabId) {
        const tabButton = document.getElementById(tabId);
        if (tabButton) {
            // Use Bootstrap's Tab API to show the tab
            const bsTab = new bootstrap.Tab(tabButton);
            bsTab.show();
            console.log('Activated tab:', tabId);
        } else {
            console.warn('Tab button not found:', tabId);
        }
    }

    /**
     * Initialize tab persistence when DOM is ready
     */
    function initTabPersistence() {
        // Get all tab buttons
        const tabButtons = document.querySelectorAll('#myTab button[data-bs-toggle="tab"]');

        if (tabButtons.length === 0) {
            console.warn('No tab buttons found');
            return;
        }

        // Add event listeners to save tab selection when changed
        tabButtons.forEach(button => {
            button.addEventListener('shown.bs.tab', function(event) {
                const tabId = event.target.id;
                saveActiveTab(tabId);
            });
        });

        // Restore saved tab on page load
        const savedTabId = getSavedTab();
        if (savedTabId) {
            console.log('Restoring saved tab:', savedTabId);
            // Wait a bit to ensure Bootstrap is fully initialized
            setTimeout(() => {
                activateTab(savedTabId);
            }, 100);
        } else {
            // No saved tab, save the default active tab
            const activeTab = document.querySelector('#myTab button.active');
            if (activeTab) {
                saveActiveTab(activeTab.id);
            }
        }

        console.log('Tab persistence initialized');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabPersistence);
    } else {
        // DOM is already ready, initialize immediately
        initTabPersistence();
    }

})();
