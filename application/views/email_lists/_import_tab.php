<!-- VIEW: application/views/email_lists/_import_tab.php -->
<?php
/**
 * Partial view for file import tab (v1.3: upload only)
 */

// Debug: check if variables are available
if (!isset($is_edit)) {
    $is_edit = FALSE;
}

// Determine list_id from available variables
$list_id = 0;
if (isset($email_list_id) && !empty($email_list_id)) {
    $list_id = $email_list_id;
} elseif (isset($list) && !empty($list['id'])) {
    $list_id = $list['id'];
}

// Debug output (removed for production)
// error_log("DEBUG _import_tab: email_list_id=" . (isset($email_list_id) ? $email_list_id : 'not set'));
// error_log("DEBUG _import_tab: list[id]=" . (isset($list['id']) ? $list['id'] : 'not set'));
// error_log("DEBUG _import_tab: final list_id=" . $list_id);

// Get current uploaded files if in edit mode
$uploaded_files = array();
if ($list_id && isset($list['id'])) {
    $uploaded_files = $this->email_lists_model->get_uploaded_files($list['id']);
}
?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">
            <i class="bi bi-cloud-upload"></i>
            <?= $this->lang->line("email_lists_import_files") ?>
        </h5>
        <p class="text-muted">
            <?= $this->lang->line("email_lists_import_files_help") ?>
        </p>

        <!-- File upload (NOT a form - handled by JavaScript to avoid nesting) -->
        <div class="mb-4">
            <label for="file_upload" class="form-label">
                <?= $this->lang->line("email_lists_choose_file") ?>
            </label>

            <div class="input-group">
                <input type="file"
                       class="form-control"
                       id="file_upload"
                       name="uploaded_file"
                       accept=".txt,.csv"
                       <?= !$list_id ? 'disabled' : '' ?>>
                <button type="button"
                        class="btn btn-primary"
                        id="upload_button"
                        onclick="uploadEmailFile(<?= $list_id ?>)"
                        <?= !$list_id ? 'disabled' : '' ?>>
                    <i class="bi bi-cloud-upload"></i>
                    <?= $this->lang->line("email_lists_upload_button") ?>
                </button>
            </div>

            <?php if (!$list_id): ?>
                <div class="alert alert-warning mt-2">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= $this->lang->line("email_lists_save_before_upload") ?>
                </div>
            <?php endif; ?>

            <small class="text-muted">
                <i class="bi bi-info-circle"></i>
                <?= $this->lang->line("email_lists_accepted_formats") ?>: .txt, .csv
            </small>
        </div>

        <!-- Upload progress/status -->
        <div id="upload_status" class="mb-3"></div>

        <hr>

        <!-- List of uploaded files -->
        <h6 class="text-muted mb-3">
            <i class="bi bi-files"></i>
            <?= $this->lang->line("email_lists_uploaded_files") ?>
        </h6>

        <div id="uploaded_files_list">
            <?php if (empty($uploaded_files)): ?>
                <p class="text-muted fst-italic">
                    <?= $this->lang->line("email_lists_no_files_uploaded") ?>
                </p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($uploaded_files as $file): ?>
                    <div class="list-group-item" data-filename="<?= htmlspecialchars($file['filename']) ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <?= htmlspecialchars($file['filename']) ?>
                                </h6>
                                <p class="mb-1 small text-muted">
                                    <i class="bi bi-calendar"></i>
                                    <?= $this->lang->line("email_lists_uploaded_on") ?>: <?= date('d/m/Y H:i', strtotime($file['uploaded_at'])) ?>
                                    <span class="ms-3">
                                        <i class="bi bi-envelope"></i>
                                        <?= $this->lang->line("email_lists_addresses_count") ?>: <strong><?= $file['address_count'] ?></strong>
                                    </span>
                                </p>
                            </div>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    title="<?= $this->lang->line("email_lists_delete_file") ?>"
                                    onclick="deleteEmailFile(<?= $list_id ?>, '<?= htmlspecialchars($file['filename'], ENT_QUOTES) ?>')">
                                <i class="bi bi-trash"></i>
                                <?= $this->lang->line("email_lists_delete") ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
/**
 * Upload email file
 * Creates a temporary form and submits it to avoid form nesting issues
 */
function uploadEmailFile(listId) {
    var fileInput = document.getElementById('file_upload');

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert('<?= $this->lang->line("email_lists_choose_file") ?>');
        return;
    }

    // Create a temporary form element
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= controller_url($controller) ?>/upload_file/' + listId;
    form.enctype = 'multipart/form-data';
    form.style.display = 'none';

    // Move the file input to the new form (this preserves the file selection)
    form.appendChild(fileInput);

    // Add form to body and submit
    document.body.appendChild(form);
    form.submit();
}

/**
 * Delete email file
 * Creates a temporary form and submits it to avoid form nesting issues
 */
function deleteEmailFile(listId, filename) {
    if (!confirm('<?= $this->lang->line("email_lists_confirm_delete_file") ?>')) {
        return;
    }

    // Create a temporary form
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= controller_url($controller) ?>/delete_file/' + listId;

    // Add filename as hidden input
    var filenameInput = document.createElement('input');
    filenameInput.type = 'hidden';
    filenameInput.name = 'filename';
    filenameInput.value = filename;
    form.appendChild(filenameInput);

    // Add form to body and submit
    document.body.appendChild(form);
    form.submit();
}
</script>
