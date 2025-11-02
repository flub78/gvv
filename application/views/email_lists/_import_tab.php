<!-- VIEW: application/views/email_lists/_import_tab.php -->
<?php
/**
 * Partial view for file import tab (CSV and text)
 */
?>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">
            <i class="bi bi-file-earmark-text"></i>
            <?= $this->lang->line("email_lists_import_text") ?>
        </h5>
        <p class="text-muted">
            <?= $this->lang->line("email_lists_import_text_help") ?>
        </p>

        <div class="mb-3">
            <label for="text_import" class="form-label">
                <?= $this->lang->line("email_lists_paste_text") ?>
            </label>
            <textarea class="form-control font-monospace"
                      id="text_import"
                      rows="8"
                      placeholder="email1@example.com&#10;email2@example.com&#10;email3@example.com"></textarea>
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

<!-- Import results -->
<div id="import_results" class="mt-3" style="display: none;">
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="bi bi-check-circle"></i>
                <?= $this->lang->line("email_lists_import_results") ?>
            </h5>
        </div>
        <div class="card-body">
            <div id="import_summary"></div>
            <div id="import_preview" class="mt-3"></div>
            <div class="mt-3">
                <button type="button"
                        class="btn btn-success"
                        onclick="confirmImport()">
                    <i class="bi bi-check-circle"></i>
                    <?= $this->lang->line("email_lists_confirm_import") ?>
                </button>
                <button type="button"
                        class="btn btn-secondary"
                        onclick="cancelImport()">
                    <i class="bi bi-x-circle"></i>
                    <?= $this->lang->line("gvv_str_cancel") ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let pendingImports = [];

// Import text emails
function importTextEmails() {
    const textarea = document.getElementById('text_import');
    const text = textarea.value.trim();

    if (!text) {
        alert('<?= $this->lang->line("email_lists_no_text_to_import") ?>');
        return;
    }

    // Parse emails from text (one per line, extract email addresses)
    const lines = text.split(/[\n\r]+/);
    const emails = [];
    const errors = [];

    lines.forEach((line, idx) => {
        const trimmed = line.trim();
        if (!trimmed) return;

        // Extract email using regex
        const match = trimmed.match(/([^\s@]+@[^\s@]+\.[^\s@]+)/);
        if (match) {
            const email = match[1].toLowerCase();
            // Extract potential name (text before email)
            const namePart = trimmed.replace(match[0], '').trim().replace(/[<>]/g, '');
            emails.push({
                email: email,
                name: namePart || '',
                source: 'text'
            });
        } else {
            errors.push({line: idx + 1, text: trimmed});
        }
    });

    displayImportResults(emails, errors, 'text');
}

// Import CSV emails
function importCsvEmails() {
    const textarea = document.getElementById('csv_import');
    const csv = textarea.value.trim();

    if (!csv) {
        alert('<?= $this->lang->line("email_lists_no_csv_to_import") ?>');
        return;
    }

    const delimiter = document.getElementById('csv_delimiter').value;
    const emailCol = parseInt(document.getElementById('csv_email_col').value);
    const nameCol = parseInt(document.getElementById('csv_name_col').value);
    const hasHeader = document.getElementById('csv_has_header').checked;

    // Parse CSV
    const lines = csv.split(/[\n\r]+/);
    const emails = [];
    const errors = [];

    lines.forEach((line, idx) => {
        // Skip header
        if (hasHeader && idx === 0) return;

        const trimmed = line.trim();
        if (!trimmed) return;

        // Split by delimiter
        const columns = trimmed.split(delimiter).map(c => c.trim().replace(/^["']|["']$/g, ''));

        // Get email
        if (emailCol >= columns.length) {
            errors.push({line: idx + 1, text: trimmed, error: 'Column index out of range'});
            return;
        }

        const email = columns[emailCol].toLowerCase();
        const name = nameCol >= 0 && nameCol < columns.length ? columns[nameCol] : '';

        // Validate email
        if (email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            emails.push({
                email: email,
                name: name,
                source: 'csv'
            });
        } else {
            errors.push({line: idx + 1, text: trimmed, error: 'Invalid email format'});
        }
    });

    displayImportResults(emails, errors, 'csv');
}

// Display import results
function displayImportResults(emails, errors, source) {
    pendingImports = emails;

    const resultsDiv = document.getElementById('import_results');
    const summaryDiv = document.getElementById('import_summary');
    const previewDiv = document.getElementById('import_preview');

    // Summary
    let summaryHtml = '<p><strong><?= $this->lang->line("email_lists_valid_emails") ?>:</strong> ' + emails.length + '</p>';
    if (errors.length > 0) {
        summaryHtml += '<p class="text-warning"><strong><?= $this->lang->line("email_lists_errors") ?>:</strong> ' + errors.length + '</p>';
        summaryHtml += '<details class="mt-2"><summary><?= $this->lang->line("email_lists_show_errors") ?></summary><ul>';
        errors.forEach(err => {
            summaryHtml += '<li>Line ' + err.line + ': ' + err.text + (err.error ? ' (' + err.error + ')' : '') + '</li>';
        });
        summaryHtml += '</ul></details>';
    }
    summaryDiv.innerHTML = summaryHtml;

    // Preview (first 10)
    let previewHtml = '<h6><?= $this->lang->line("email_lists_preview") ?>:</h6><ul>';
    emails.slice(0, 10).forEach(item => {
        previewHtml += '<li><code>' + item.email + '</code>';
        if (item.name) {
            previewHtml += ' - ' + item.name;
        }
        previewHtml += '</li>';
    });
    if (emails.length > 10) {
        previewHtml += '<li><em>... et ' + (emails.length - 10) + ' autres</em></li>';
    }
    previewHtml += '</ul>';
    previewDiv.innerHTML = previewHtml;

    resultsDiv.style.display = 'block';
}

// Confirm import
function confirmImport() {
    if (pendingImports.length === 0) return;

    // Add to external emails list
    pendingImports.forEach(item => {
        addExternalEmailToList(item.email, item.name);
    });

    // Clear pending and hide results
    pendingImports = [];
    document.getElementById('import_results').style.display = 'none';
    document.getElementById('text_import').value = '';
    document.getElementById('csv_import').value = '';

    alert(pendingImports.length + ' <?= $this->lang->line("email_lists_emails_imported") ?>');
}

// Cancel import
function cancelImport() {
    pendingImports = [];
    document.getElementById('import_results').style.display = 'none';
}
</script>
