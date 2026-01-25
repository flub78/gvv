<!-- VIEW: application/views/email_lists/_export_section.php -->
<?php
/**
 * Partial view for export options (clipboard, files, mailto)
 * Used in view.php
 */

// Prepare email list for JavaScript
$email_addresses = array_column($emails, 'email');
$email_list_json = json_encode($email_addresses);
?>

<!-- Section 1: Envoi d'email -->
<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi bi-envelope"></i>
            <?= $this->lang->line("email_lists_send_section") ?>
        </h5>
    </div>
    <div class="card-body">
        <!-- Chunking options -->
        <div class="border rounded p-3 mb-3 bg-light">
            <h6 class="card-title">
                <i class="bi bi-scissors"></i>
                <?= $this->lang->line("email_lists_chunk_emails") ?>
            </h6>
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="chunk_size" class="form-label">
                        <?= $this->lang->line("email_lists_chunk_size") ?>:
                    </label>
                    <input type="number"
                           class="form-control form-control-sm"
                           id="chunk_size"
                           value="20"
                           min="1"
                           max="<?= count($email_addresses) ?>"
                           onchange="updateChunkDisplay()">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="chunk_part" class="form-label">
                        <?= $this->lang->line("email_lists_chunk_part") ?>:
                    </label>
                    <select class="form-select form-select-sm" id="chunk_part"></select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">&nbsp;</label>
                    <div id="chunk_info" class="text-muted small"></div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- mailto options -->
            <div class="col-md-8 mb-3">
                <div class="border rounded p-3 h-100">
                    <h6 class="card-title">
                        <i class="bi bi-envelope-open"></i>
                        <?= $this->lang->line("email_lists_mailto") ?>
                    </h6>
                    <p class="text-muted small mb-2">
                        <?= $this->lang->line("email_lists_mailto_help") ?>
                    </p>

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label for="mailto_field" class="form-label">
                                <?= $this->lang->line("email_lists_mailto_field") ?>:
                            </label>
                            <select class="form-select form-select-sm" id="mailto_field">
                                <option value="to"><?= $this->lang->line("email_lists_mailto_to") ?></option>
                                <option value="cc"><?= $this->lang->line("email_lists_mailto_cc") ?></option>
                                <option value="bcc" selected><?= $this->lang->line("email_lists_mailto_bcc") ?></option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="mailto_subject" class="form-label">
                                <?= $this->lang->line("email_lists_mailto_subject") ?>:
                            </label>
                            <input type="text"
                                   class="form-control form-control-sm"
                                   id="mailto_subject"
                                   placeholder="<?= $this->lang->line("gvv_str_optional") ?>">
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <label for="mailto_reply_to" class="form-label">
                                <?= $this->lang->line("email_lists_mailto_reply_to") ?>:
                            </label>
                            <input type="email"
                                   class="form-control form-control-sm"
                                   id="mailto_reply_to"
                                   placeholder="<?= $this->lang->line("gvv_str_optional") ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-success btn-sm"
                                onclick="openMailtoLink()">
                            <i class="bi bi-envelope-open"></i>
                            <?= $this->lang->line("email_lists_mailto") ?>
                        </button>
                        <button type="button"
                                class="btn btn-outline-secondary btn-sm"
                                onclick="saveMailtoPreferences()">
                            <i class="bi bi-save"></i>
                            <?= $this->lang->line("email_lists_mailto_save_prefs") ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Clipboard copy -->
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <h6 class="card-title">
                        <i class="bi bi-clipboard"></i>
                        <?= $this->lang->line("email_lists_export_clipboard") ?>
                    </h6>
                    <div class="mb-2">
                        <label for="separator" class="form-label">
                            <?= $this->lang->line("email_lists_separator") ?>:
                        </label>
                        <select class="form-select form-select-sm" id="separator">
                            <option value=", "><?= $this->lang->line("email_lists_separator_comma") ?> (, )</option>
                            <option value="; "><?= $this->lang->line("email_lists_separator_semicolon") ?> (; )</option>
                        </select>
                    </div>
                    <button type="button"
                            class="btn btn-primary btn-sm w-100"
                            onclick="copyEmailsToClipboard()">
                        <i class="bi bi-clipboard"></i>
                        <?= $this->lang->line("email_lists_copy") ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 2: Export fichiers -->
<div class="card mb-3">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0">
            <i class="bi bi-download"></i>
            <?= $this->lang->line("email_lists_export") ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2">
            <a href="<?= controller_url($controller) ?>/download_txt/<?= $list['id'] ?>"
               class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-text"></i>
                <?= $this->lang->line("email_lists_export_txt") ?>
            </a>
            <a href="<?= controller_url($controller) ?>/download_md/<?= $list['id'] ?>"
               class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-richtext"></i>
                <?= $this->lang->line("email_lists_export_md") ?>
            </a>
        </div>
    </div>
</div>

<!-- Toast container for notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="exportToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastBody">
        </div>
    </div>
</div>

<script>
// Initialize chunk display (called from view.php after email_list_display is loaded)
function initializeEmailChunking() {
    loadMailtoPreferences();
    updateChunkDisplay();

    // Add event listeners for chunk controls
    const chunkSizeInput = document.getElementById('chunk_size');
    const chunkPartSelect = document.getElementById('chunk_part');

    if (chunkSizeInput) {
        chunkSizeInput.addEventListener('change', function() {
            updateChunkDisplay();
            updateEmailDisplay();
        });
    }

    if (chunkPartSelect) {
        chunkPartSelect.addEventListener('change', function() {
            updateChunkInfo();
            updateEmailDisplay();
        });
    }
}

// Copy emails to clipboard
function copyEmailsToClipboard() {
    const separator = document.getElementById('separator').value;
    const emails = getCurrentEmailChunk();
    const text = emails.join(separator);

    copyToClipboard(text,
        function() {
            // Success callback
            showToast('<?= $this->lang->line("email_lists_copy_success") ?>', 'success');
        },
        function(error) {
            // Error callback
            showToast('<?= $this->lang->line("email_lists_copy_error") ?>', 'danger');
        }
    );
}

// Get current email chunk based on selection
function getCurrentEmailChunk() {
    const chunkSizeInput = document.getElementById('chunk_size');
    const chunkPartInput = document.getElementById('chunk_part');

    if (!chunkSizeInput || !chunkPartInput || !emailListFull) {
        // Fallback to full list if chunk controls not available
        return emailListFull || [];
    }

    const chunkPart = chunkPartInput.value;

    // If empty value (default option), return full list
    if (!chunkPart || chunkPart === '') {
        return emailListFull;
    }

    const chunkSize = parseInt(chunkSizeInput.value);
    const partNumber = parseInt(chunkPart);

    // Calculate start and end indices for the selected chunk part
    const startIndex = (partNumber - 1) * chunkSize;
    const endIndex = Math.min(startIndex + chunkSize, emailListFull.length);

    return emailListFull.slice(startIndex, endIndex);
}

// Update chunk display
function updateChunkDisplay() {
    if (!emailListFull || emailListFull.length === 0) {
        return;
    }

    const chunkSizeInput = document.getElementById('chunk_size');
    const partSelect = document.getElementById('chunk_part');

    if (!chunkSizeInput || !partSelect) {
        return;
    }

    const chunkSize = parseInt(chunkSizeInput.value) || 20;
    const totalEmails = emailListFull.length;

    // Update part selector
    partSelect.innerHTML = '';

    // Add default empty option (full list)
    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.text = '-- (<?= $this->lang->line("email_lists_full_list") ?>)';
    partSelect.appendChild(emptyOption);

    // Only show parts if chunk size is smaller than total emails
    if (chunkSize < totalEmails) {
        const numParts = Math.ceil(totalEmails / chunkSize);

        for (let i = 1; i <= numParts; i++) {
            const start = (i - 1) * chunkSize + 1;
            const end = Math.min(i * chunkSize, totalEmails);
            const option = document.createElement('option');
            option.value = i;
            option.text = '<?= $this->lang->line("email_lists_part") ?> ' + i + ' (' + start + '-' + end + ')';
            partSelect.appendChild(option);
        }
    }

    // Update info
    updateChunkInfo();
}

// Update chunk info display
function updateChunkInfo() {
    if (!emailListFull || emailListFull.length === 0) {
        return;
    }

    const chunkSizeInput = document.getElementById('chunk_size');
    const chunkPartInput = document.getElementById('chunk_part');
    const chunkInfoElement = document.getElementById('chunk_info');

    if (!chunkSizeInput || !chunkPartInput || !chunkInfoElement) {
        return;
    }

    const chunkPart = chunkPartInput.value;
    const totalEmails = emailListFull.length;

    // If empty value (default option), show full list info
    if (!chunkPart || chunkPart === '') {
        chunkInfoElement.innerHTML =
            '<?= $this->lang->line("email_lists_showing") ?> 1-' + totalEmails +
            ' <?= $this->lang->line("gvv_str_of") ?> ' + totalEmails;
        return;
    }

    const chunkSize = parseInt(chunkSizeInput.value) || 20;
    const partNumber = parseInt(chunkPart);

    const start = (partNumber - 1) * chunkSize + 1;
    const end = Math.min(partNumber * chunkSize, totalEmails);

    chunkInfoElement.innerHTML =
        '<?= $this->lang->line("email_lists_showing") ?> ' + start + '-' + end +
        ' <?= $this->lang->line("gvv_str_of") ?> ' + totalEmails;
}

// Update email display based on selected chunk
function updateEmailDisplay() {
    if (!emailListFull || emailListFull.length === 0) {
        return;
    }

    const emailDisplayContainer = document.getElementById('email_list_display');
    if (!emailDisplayContainer) {
        return;
    }

    // Get indices for current chunk
    const chunkPartInput = document.getElementById('chunk_part');
    const chunkSizeInput = document.getElementById('chunk_size');

    if (!chunkPartInput || !chunkSizeInput) {
        return;
    }

    const chunkPart = chunkPartInput.value;

    let startIndex = 0;
    let endIndex = emailListFull.length;

    // If a specific part is selected, calculate range
    if (chunkPart && chunkPart !== '') {
        const chunkSize = parseInt(chunkSizeInput.value);
        const partNumber = parseInt(chunkPart);
        startIndex = (partNumber - 1) * chunkSize;
        endIndex = Math.min(startIndex + chunkSize, emailListFull.length);
    }

    // Get the full email data for the chunk
    const currentEmailData = typeof emailDataFull !== 'undefined' ?
                            emailDataFull.slice(startIndex, endIndex) :
                            emailListFull.slice(startIndex, endIndex).map(function(email) {
                                return { email: email, name: '', source: '' };
                            });

    // Update recipient count badge
    const recipientBadge = document.getElementById('recipient_count_badge');
    if (recipientBadge) {
        const currentCount = endIndex - startIndex;
        const totalCount = emailListFull.length;
        if (currentCount < totalCount) {
            recipientBadge.textContent = currentCount + ' / ' + totalCount;
        } else {
            recipientBadge.textContent = totalCount;
        }
    }

    // Rebuild the email list display
    emailDisplayContainer.innerHTML = '';

    currentEmailData.forEach(function(emailData) {
        const emailDiv = document.createElement('div');
        emailDiv.className = 'mb-1';

        let html = '<i class="bi bi-envelope"></i> <code>' +
                   htmlEscape(emailData.email) + '</code>';

        if (emailData.name) {
            html += ' - <span class="text-muted">' + htmlEscape(emailData.name) + '</span>';
        }

        if (emailData.source) {
            html += ' <span class="badge bg-secondary ms-2">' +
                    htmlEscape(emailData.source) + '</span>';
        }

        emailDiv.innerHTML = html;
        emailDisplayContainer.appendChild(emailDiv);
    });
}

// Helper function to escape HTML
function htmlEscape(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Open mailto link
function openMailtoLink() {
    const field = document.getElementById('mailto_field').value;
    const subject = document.getElementById('mailto_subject').value;
    const replyTo = document.getElementById('mailto_reply_to').value;
    const emails = getCurrentEmailChunk();

    const params = {
        field: field,
        subject: subject,
        replyTo: replyTo
    };

    const mailto = generateMailto(emails, params);

    // Check URL length (browser limit ~2000 chars)
    if (mailto.length > 2000) {
        if (confirm('<?= $this->lang->line("email_lists_mailto_too_long") ?>')) {
            copyToClipboard(emails.join(', '),
                function() {
                    // Success callback
                    showToast('<?= $this->lang->line("email_lists_copy_success") ?>', 'success');
                },
                function(error) {
                    // Error callback
                    showToast('<?= $this->lang->line("email_lists_copy_error") ?>', 'danger');
                }
            );
        }
        return;
    }

    window.location.href = mailto;
}

// Save mailto preferences
function saveMailtoPreferences() {
    const prefs = {
        field: document.getElementById('mailto_field').value,
        subject: document.getElementById('mailto_subject').value,
        replyTo: document.getElementById('mailto_reply_to').value,
        chunkSize: document.getElementById('chunk_size').value,
        separator: document.getElementById('separator').value
    };

    localStorage.setItem('email_lists_mailto_prefs', JSON.stringify(prefs));
    showToast('<?= $this->lang->line("email_lists_prefs_saved") ?>', 'success');
}

// Load mailto preferences
function loadMailtoPreferences() {
    try {
        const prefs = localStorage.getItem('email_lists_mailto_prefs');
        if (prefs && prefs !== 'undefined' && prefs !== 'null') {
            const p = JSON.parse(prefs);
            if (p.field && document.getElementById('mailto_field')) {
                document.getElementById('mailto_field').value = p.field;
            }
            if (p.subject && document.getElementById('mailto_subject')) {
                document.getElementById('mailto_subject').value = p.subject;
            }
            if (p.replyTo && document.getElementById('mailto_reply_to')) {
                document.getElementById('mailto_reply_to').value = p.replyTo;
            }
            if (p.chunkSize && document.getElementById('chunk_size')) {
                document.getElementById('chunk_size').value = p.chunkSize;
            }
            if (p.separator && document.getElementById('separator')) {
                document.getElementById('separator').value = p.separator;
            }
        }
    } catch (e) {
        console.error('Error loading mailto preferences:', e);
    }
}

// Show Bootstrap toast
function showToast(message, type) {
    const toastEl = document.getElementById('exportToast');
    const toastBody = document.getElementById('toastBody');
    const toastTitle = document.getElementById('toastTitle');

    toastBody.textContent = message;
    toastTitle.textContent = type === 'success' ? 'Succès' : 'Erreur';
    toastEl.className = 'toast show bg-' + (type === 'success' ? 'success' : 'danger') + ' text-white';

    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}
</script>
