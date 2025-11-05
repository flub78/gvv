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

<div class="row">
    <!-- Clipboard copy -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-body">
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

    <!-- File exports -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="bi bi-download"></i>
                    <?= $this->lang->line("email_lists_export") ?>
                </h6>
                <div class="d-grid gap-2">
                    <a href="<?= controller_url($controller) ?>/download_txt/<?= $list['id'] ?>"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-file-earmark-text"></i>
                        <?= $this->lang->line("email_lists_export_txt") ?>
                    </a>
                    <a href="<?= controller_url($controller) ?>/download_md/<?= $list['id'] ?>"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-file-earmark-richtext"></i>
                        <?= $this->lang->line("email_lists_export_md") ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chunking options -->
<div class="card mb-3">
    <div class="card-body">
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
</div>

<!-- mailto options -->
<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title">
            <i class="bi bi-envelope-open"></i>
            <?= $this->lang->line("email_lists_mailto") ?>
        </h6>
        <p class="text-muted small">
            <?= $this->lang->line("email_lists_mailto_help") ?>
        </p>

        <div class="row mb-2">
            <div class="col-md-3">
                <label for="mailto_field" class="form-label">
                    <?= $this->lang->line("email_lists_mailto_field") ?>:
                </label>
                <select class="form-select form-select-sm" id="mailto_field">
                    <option value="to"><?= $this->lang->line("email_lists_mailto_to") ?></option>
                    <option value="cc"><?= $this->lang->line("email_lists_mailto_cc") ?></option>
                    <option value="bcc" selected><?= $this->lang->line("email_lists_mailto_bcc") ?></option>
                </select>
            </div>
            <div class="col-md-9">
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
// Initialize email list from PHP
var emailListFull = <?= $email_list_json ?>;

// Load saved mailto preferences on page load
document.addEventListener('DOMContentLoaded', function() {
    loadMailtoPreferences();
    updateChunkDisplay();
});

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
    
    const chunkSize = parseInt(chunkSizeInput.value);
    const chunkPart = parseInt(chunkPartInput.value);

    if (!chunkSize || chunkSize >= emailListFull.length) {
        return emailListFull;
    }

    // Calculate start and end indices for the selected chunk part
    const startIndex = (chunkPart - 1) * chunkSize;
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
    const numParts = Math.ceil(totalEmails / chunkSize);

    // Update part selector
    partSelect.innerHTML = '';
    for (let i = 1; i <= numParts; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.text = i + ' / ' + numParts;
        partSelect.appendChild(option);
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
    
    const chunkSize = parseInt(chunkSizeInput.value) || 20;
    const chunkPart = parseInt(chunkPartInput.value) || 1;
    const totalEmails = emailListFull.length;

    const start = (chunkPart - 1) * chunkSize + 1;
    const end = Math.min(chunkPart * chunkSize, totalEmails);

    chunkInfoElement.innerHTML =
        '<?= $this->lang->line("email_lists_showing") ?> ' + start + '-' + end +
        ' <?= $this->lang->line("gvv_str_of") ?> ' + totalEmails;
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
    const prefs = localStorage.getItem('email_lists_mailto_prefs');
    if (prefs) {
        const p = JSON.parse(prefs);
        if (p.field) document.getElementById('mailto_field').value = p.field;
        if (p.subject) document.getElementById('mailto_subject').value = p.subject;
        if (p.replyTo) document.getElementById('mailto_reply_to').value = p.replyTo;
        if (p.chunkSize) document.getElementById('chunk_size').value = p.chunkSize;
        if (p.separator) document.getElementById('separator').value = p.separator;
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
