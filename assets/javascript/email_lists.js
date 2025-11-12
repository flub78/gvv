/**
 * GVV - Email Lists Management
 * Client-side functionality for email list operations
 *
 * Features:
 * - Clipboard copy operations
 * - Email chunking/splitting
 * - mailto: URL generation
 * - User preferences (localStorage)
 *
 * @package GVV
 * @see doc/design_notes/gestion_emails_design.md
 */

(function() {
    'use strict';

    // ========================================================================
    // Clipboard Operations
    // ========================================================================

    /**
     * Copy text to clipboard using modern Clipboard API
     * Falls back to legacy execCommand for older browsers
     *
     * @param {string} text - Text to copy
     * @param {function} successCallback - Called on success
     * @param {function} errorCallback - Called on error
     */
    window.copyToClipboard = function(text, successCallback, errorCallback) {
        if (!text) {
            if (errorCallback) errorCallback('No text to copy');
            return;
        }

        // Modern Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text)
                .then(function() {
                    if (successCallback) successCallback();
                })
                .catch(function(err) {
                    // Fallback to legacy method
                    copyToClipboardLegacy(text, successCallback, errorCallback);
                });
        } else {
            // Legacy fallback
            copyToClipboardLegacy(text, successCallback, errorCallback);
        }
    };

    /**
     * Legacy clipboard copy using execCommand
     *
     * @param {string} text - Text to copy
     * @param {function} successCallback - Called on success
     * @param {function} errorCallback - Called on error
     */
    function copyToClipboardLegacy(text, successCallback, errorCallback) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            var successful = document.execCommand('copy');
            document.body.removeChild(textarea);

            if (successful && successCallback) {
                successCallback();
            } else if (!successful && errorCallback) {
                errorCallback('Copy command failed');
            }
        } catch (err) {
            document.body.removeChild(textarea);
            if (errorCallback) errorCallback(err.toString());
        }
    }

    /**
     * Show Bootstrap toast notification
     *
     * @param {string} message - Message to display
     * @param {string} type - Toast type: 'success', 'error', 'info'
     */
    window.showToast = function(message, type) {
        type = type || 'success';

        // Create toast container if it doesn't exist
        var container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        // Create toast element
        var toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-' +
                         (type === 'error' ? 'danger' : type === 'info' ? 'info' : 'success');
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML =
            '<div class="d-flex">' +
                '<div class="toast-body">' + message + '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div>';

        container.appendChild(toast);

        // Show toast (Bootstrap 5)
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            var bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();

            // Remove from DOM after hiding
            toast.addEventListener('hidden.bs.toast', function() {
                container.removeChild(toast);
            });
        } else {
            // Fallback: just show and auto-hide
            toast.style.display = 'block';
            setTimeout(function() {
                toast.style.display = 'none';
                container.removeChild(toast);
            }, 3000);
        }
    };

    // ========================================================================
    // Email List Chunking
    // ========================================================================

    /**
     * Split email list into chunks of specified size
     *
     * @param {Array} emails - Array of email addresses
     * @param {number} size - Chunk size (default: 20)
     * @return {Array} Array of chunks
     */
    window.chunkEmails = function(emails, size) {
        size = parseInt(size) || 20;
        if (size < 1) size = 20;
        if (!emails || emails.length === 0) return [];

        var chunks = [];
        for (var i = 0; i < emails.length; i += size) {
            chunks.push(emails.slice(i, i + size));
        }
        return chunks;
    };

    /**
     * DEPRECATED - Old version of updateChunkDisplay
     * Now defined in _export_section.php
     *
     * @deprecated Use the version in _export_section.php instead
     */
    /*
    window.updateChunkDisplay = function(emails, chunkSize, partNumber) {
        var chunks = chunkEmails(emails, chunkSize);
        var totalParts = chunks.length;

        // Update part selector
        var partSelect = document.getElementById('chunk-part-select');
        if (partSelect) {
            partSelect.innerHTML = '';
            for (var i = 0; i < totalParts; i++) {
                var option = document.createElement('option');
                option.value = i + 1;
                option.textContent = 'Partie ' + (i + 1) + '/' + totalParts +
                                   ' (' + chunks[i].length + ' destinataires)';
                if (i + 1 === partNumber) option.selected = true;
                partSelect.appendChild(option);
            }
        }

        // Update email display
        var currentChunk = chunks[partNumber - 1] || [];
        var displayArea = document.getElementById('chunk-emails-display');
        if (displayArea) {
            displayArea.value = currentChunk.join(', ');
        }

        // Update summary
        var summary = document.getElementById('chunk-summary');
        if (summary) {
            var start = ((partNumber - 1) * chunkSize) + 1;
            var end = Math.min(partNumber * chunkSize, emails.length);
            summary.textContent = 'Affichage : destinataires ' + start + '-' + end +
                                ' sur ' + emails.length + ' total';
        }

        return currentChunk;
    };
    */

    // ========================================================================
    // mailto: URL Generation
    // ========================================================================

    /**
     * Generate mailto: URL with parameters
     *
     * @param {Array} emails - Array of email addresses
     * @param {Object} params - Parameters (field, subject, body, replyTo)
     * @return {string|null} mailto: URL or null if too long
     */
    window.generateMailto = function(emails, params) {
        params = params || {};
        if (!emails || emails.length === 0) return 'mailto:';

        var emailList = emails.join(', ');
        var field = params.field || 'to';
        var url = 'mailto:';
        var queryParams = [];

        // Add emails to appropriate field
        if (field === 'to') {
            url += encodeURIComponent(emailList);
        } else if (field === 'cc') {
            queryParams.push('cc=' + encodeURIComponent(emailList));
        } else if (field === 'bcc') {
            queryParams.push('bcc=' + encodeURIComponent(emailList));
        }

        // Add optional parameters
        if (params.subject) {
            queryParams.push('subject=' + encodeURIComponent(params.subject));
        }
        if (params.body) {
            queryParams.push('body=' + encodeURIComponent(params.body));
        }
        if (params.replyTo) {
            queryParams.push('reply-to=' + encodeURIComponent(params.replyTo));
        }

        if (queryParams.length > 0) {
            url += '?' + queryParams.join('&');
        }

        // Check URL length (most browsers limit to ~2000 characters)
        if (url.length > 2000) {
            return null;
        }

        return url;
    };

    /**
     * Open mailto: URL or fallback to clipboard if too long
     *
     * @param {Array} emails - Array of email addresses
     * @param {Object} params - mailto parameters
     */
    window.openMailtoOrCopy = function(emails, params) {
        var url = generateMailto(emails, params);

        if (url === null) {
            // URL too long - copy to clipboard instead
            var emailText = emails.join(', ');
            copyToClipboard(
                emailText,
                function() {
                    showToast('Liste trop longue pour mailto:. ' + emails.length +
                            ' adresses copiées dans le presse-papier.', 'info');
                },
                function(err) {
                    showToast('Erreur: ' + err, 'error');
                }
            );
        } else {
            // Open mailto: link
            window.location.href = url;
        }
    };

    // ========================================================================
    // User Preferences (localStorage)
    // ========================================================================

    /**
     * Save mailto preferences to localStorage
     *
     * @param {Object} prefs - Preferences object
     */
    window.saveMailtoPreferences = function(prefs) {
        try {
            localStorage.setItem('gvv_mailto_prefs', JSON.stringify(prefs));
            return true;
        } catch (e) {
            console.error('Failed to save preferences:', e);
            return false;
        }
    };

    /**
     * Load mailto preferences from localStorage
     *
     * @return {Object} Preferences object or defaults
     */
    window.loadMailtoPreferences = function() {
        try {
            var stored = localStorage.getItem('gvv_mailto_prefs');
            if (stored && stored !== 'undefined' && stored !== 'null') {
                return JSON.parse(stored);
            }
        } catch (e) {
            console.error('Failed to load preferences:', e);
        }

        // Return defaults
        return {
            field: 'bcc',
            subject: '',
            body: '',
            replyTo: '',
            chunkSize: 20
        };
    };

    /**
     * Apply saved preferences to form
     *
     * @param {string} formId - Form element ID
     */
    window.applyMailtoPreferences = function(formId) {
        var prefs = loadMailtoPreferences();
        var form = document.getElementById(formId);
        if (!form) return;

        // Apply each preference
        if (prefs.field && form.elements['mailto_field']) {
            form.elements['mailto_field'].value = prefs.field;
        }
        if (prefs.subject && form.elements['mailto_subject']) {
            form.elements['mailto_subject'].value = prefs.subject;
        }
        if (prefs.body && form.elements['mailto_body']) {
            form.elements['mailto_body'].value = prefs.body;
        }
        if (prefs.replyTo && form.elements['mailto_reply_to']) {
            form.elements['mailto_reply_to'].value = prefs.replyTo;
        }
        if (prefs.chunkSize && form.elements['chunk_size']) {
            form.elements['chunk_size'].value = prefs.chunkSize;
        }
    };

    /**
     * Save preferences from form
     *
     * @param {string} formId - Form element ID
     */
    window.savePreferencesFromForm = function(formId) {
        var form = document.getElementById(formId);
        if (!form) return false;

        var prefs = {
            field: form.elements['mailto_field'] ? form.elements['mailto_field'].value : 'bcc',
            subject: form.elements['mailto_subject'] ? form.elements['mailto_subject'].value : '',
            body: form.elements['mailto_body'] ? form.elements['mailto_body'].value : '',
            replyTo: form.elements['mailto_reply_to'] ? form.elements['mailto_reply_to'].value : '',
            chunkSize: form.elements['chunk_size'] ? parseInt(form.elements['chunk_size'].value) : 20
        };

        if (saveMailtoPreferences(prefs)) {
            showToast('Préférences sauvegardées', 'success');
            return true;
        } else {
            showToast('Erreur lors de la sauvegarde', 'error');
            return false;
        }
    };

    // ========================================================================
    // Utility Functions
    // ========================================================================

    /**
     * Format email list with specified separator
     *
     * @param {Array} emails - Array of emails
     * @param {string} separator - Separator (default: ', ')
     * @return {string} Formatted email list
     */
    window.formatEmailList = function(emails, separator) {
        separator = separator || ', ';
        if (!emails || emails.length === 0) return '';
        return emails.join(separator);
    };

    /**
     * Count emails in list
     *
     * @param {Array} emails - Array of emails
     * @return {number} Count
     */
    window.countEmails = function(emails) {
        return emails ? emails.length : 0;
    };

})();
