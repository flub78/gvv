<!-- VIEW: application/views/email_lists/_import_tab.php -->
<?php
/**
 * Partial view for file import tab (CSV and text)
 */
?>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">
            <i class="bi bi-envelope-at"></i>
            <?= $this->lang->line("email_lists_external_addresses") ?>
        </h5>
        <p class="text-muted">
            <?= $this->lang->line("email_lists_external_addresses_help") ?>
        </p>

        <!-- Error messages container -->
        <div id="text_import_errors" class="mb-3"></div>

        <div class="mb-3">
            <label for="text_import" class="form-label">
                <?= $this->lang->line("email_lists_paste_addresses") ?>
            </label>
            <textarea class="form-control font-monospace"
                      id="text_import"
                      rows="8"
                      placeholder="email1@example.com Arthur Zorglub&#10;email2@example.com&#10;email3@example.com Association XYZ"></textarea>
        </div>

        <button type="button"
                class="btn btn-primary"
                onclick="importTextEmails()">
            <i class="bi bi-upload"></i>
            <?= $this->lang->line("email_lists_parse_import") ?>
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">
            <i class="bi bi-filetype-csv"></i>
            <?= $this->lang->line("email_lists_import_csv") ?>
        </h5>
        <p class="text-muted">
            <?= $this->lang->line("email_lists_import_csv_help") ?>
        </p>

        <!-- Error messages container -->
        <div id="csv_import_errors" class="mb-3"></div>

        <div class="mb-3">
            <label for="csv_import" class="form-label">
                <?= $this->lang->line("email_lists_paste_csv") ?>
            </label>
            <textarea class="form-control font-monospace"
                      id="csv_import"
                      rows="8"
                      placeholder="Nom,Prénom,Email&#10;Dupont,Jean,jean.dupont@example.com&#10;Martin,Marie,marie.martin@example.com"></textarea>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label for="csv_delimiter" class="form-label">
                    <?= $this->lang->line("email_lists_csv_delimiter") ?>
                </label>
                <select class="form-select" id="csv_delimiter">
                    <option value="," selected><?= $this->lang->line("email_lists_comma") ?> (,)</option>
                    <option value=";"><?= $this->lang->line("email_lists_semicolon") ?> (;)</option>
                    <option value="\t"><?= $this->lang->line("email_lists_tab") ?> (Tab)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="csv_email_col" class="form-label">
                    <?= $this->lang->line("email_lists_email_column") ?>
                </label>
                <input type="number"
                       class="form-control"
                       id="csv_email_col"
                       value="2"
                       min="0">
                <small class="text-muted"><?= $this->lang->line("email_lists_column_index_help") ?></small>
            </div>
            <div class="col-md-4">
                <label for="csv_name_col" class="form-label">
                    <?= $this->lang->line("email_lists_name_column") ?>
                </label>
                <input type="number"
                       class="form-control"
                       id="csv_name_col"
                       value="0"
                       min="-1">
                <small class="text-muted"><?= $this->lang->line("email_lists_column_optional") ?></small>
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input"
                   type="checkbox"
                   id="csv_has_header"
                   checked>
            <label class="form-check-label" for="csv_has_header">
                <?= $this->lang->line("email_lists_csv_has_header") ?>
            </label>
        </div>

        <button type="button"
                class="btn btn-primary"
                onclick="importCsvEmails()">
            <i class="bi bi-upload"></i>
            <?= $this->lang->line("email_lists_parse_import") ?>
        </button>
    </div>
</div>

<script>

// Import text emails
function importTextEmails() {
    const textarea = document.getElementById('text_import');
    const text = textarea.value.trim();
    const errorsDiv = document.getElementById('text_import_errors');

    // Clear previous errors
    errorsDiv.innerHTML = '';

    if (!text) {
        return; // No popup needed, user can see textarea is empty
    }

    // Parse emails from text (one per line, extract email addresses)
    const lines = text.split(/[\n\r]+/);
    let added = 0;
    const invalidLines = [];

    lines.forEach((line) => {
        const trimmed = line.trim();
        if (!trimmed) return;

        // Extract email using regex
        const match = trimmed.match(/([^\s@]+@[^\s@]+\.[^\s@]+)/);
        if (match) {
            const email = match[1].toLowerCase();
            // Extract potential name (text after email)
            let namePart = trimmed.replace(match[0], '').trim().replace(/[<>]/g, '');

            // Add directly to external emails list
            addExternalEmailToList(email, namePart);
            added++;
        } else {
            invalidLines.push(trimmed);
        }
    });

    // Display errors if any
    if (invalidLines.length > 0) {
        let errorHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        errorHtml += '<strong><i class="bi bi-exclamation-triangle-fill"></i> Erreur:</strong> ';
        errorHtml += 'Les adresses suivantes ne sont pas correctement formées:<ul class="mb-0 mt-2">';
        invalidLines.forEach(line => {
            errorHtml += '<li><code>' + escapeHtml(line) + '</code></li>';
        });
        errorHtml += '</ul>';
        errorHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        errorHtml += '</div>';
        errorsDiv.innerHTML = errorHtml;
    }

    // Clear textarea only if at least one email was added
    if (added > 0) {
        textarea.value = '';
    }

    // Update preview counts automatically (no popup needed, visual feedback is obvious)
    if (typeof updatePreviewCounts === 'function') {
        updatePreviewCounts();
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Import CSV emails
function importCsvEmails() {
    const textarea = document.getElementById('csv_import');
    const csv = textarea.value.trim();
    const errorsDiv = document.getElementById('csv_import_errors');

    // Clear previous errors
    errorsDiv.innerHTML = '';

    if (!csv) {
        return; // No popup needed, user can see textarea is empty
    }

    const delimiter = document.getElementById('csv_delimiter').value;
    const emailCol = parseInt(document.getElementById('csv_email_col').value);
    const nameCol = parseInt(document.getElementById('csv_name_col').value);
    const hasHeader = document.getElementById('csv_has_header').checked;

    // Parse CSV
    const lines = csv.split(/[\n\r]+/);
    let added = 0;
    const invalidLines = [];

    lines.forEach((line, idx) => {
        // Skip header
        if (hasHeader && idx === 0) return;

        const trimmed = line.trim();
        if (!trimmed) return;

        // Split by delimiter
        const columns = trimmed.split(delimiter).map(c => c.trim().replace(/^["']|["']$/g, ''));

        // Get email
        if (emailCol >= columns.length) {
            invalidLines.push({line: trimmed, reason: 'Colonne email manquante (ligne ' + (idx + 1) + ')'});
            return;
        }

        const email = columns[emailCol].toLowerCase();
        const name = nameCol >= 0 && nameCol < columns.length ? columns[nameCol] : '';

        // Validate email
        if (email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            // Add directly to external emails list
            addExternalEmailToList(email, name);
            added++;
        } else {
            invalidLines.push({line: email, reason: 'Adresse email mal formée (ligne ' + (idx + 1) + ')'});
        }
    });

    // Display errors if any
    if (invalidLines.length > 0) {
        let errorHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        errorHtml += '<strong><i class="bi bi-exclamation-triangle-fill"></i> Erreur:</strong> ';
        errorHtml += 'Problèmes détectés lors de l\'import:<ul class="mb-0 mt-2">';
        invalidLines.forEach(err => {
            errorHtml += '<li><code>' + escapeHtml(err.line) + '</code> - ' + err.reason + '</li>';
        });
        errorHtml += '</ul>';
        errorHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        errorHtml += '</div>';
        errorsDiv.innerHTML = errorHtml;
    }

    // Clear textarea only if at least one email was added
    if (added > 0) {
        textarea.value = '';
    }

    // Update preview counts automatically (no popup needed, visual feedback is obvious)
    if (typeof updatePreviewCounts === 'function') {
        updatePreviewCounts();
    }
}
</script>
