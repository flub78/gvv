<!-- VIEW: application/views/email_lists/_import_tab.php -->
<?php
/**
 * Partial view for file import tab (v1.3: upload only)
 */

// Debug: check if variables are available
if (!isset($is_edit)) {
    $is_edit = FALSE;
}
if (!isset($list_id)) {
    $list_id = 0;
}

// Get current uploaded files if in edit mode
$uploaded_files = array();
if ($is_edit && isset($list['id'])) {
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

        <!-- File upload form (always visible, will show message if no list_id) -->
        <div class="mb-4">
            <label for="file_upload" class="form-label">
                <?= $this->lang->line("email_lists_choose_file") ?>
            </label>
            <div class="input-group">
                <input type="file"
                       class="form-control"
                       id="file_upload"
                       accept=".txt,.csv"
                       <?= !$list_id ? 'disabled' : '' ?>>
                <button type="button"
                        class="btn btn-primary"
                        id="upload_button"
                        onclick="uploadFile()"
                        disabled>
                    <i class="bi bi-cloud-upload"></i>
                    <?= $this->lang->line("email_lists_upload_button") ?>
                </button>
            </div>
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
                                    onclick="deleteFile('<?= htmlspecialchars($file['filename']) ?>', this)"
                                    title="<?= $this->lang->line("email_lists_delete_file") ?>">
                                <i class="bi bi-trash"></i>
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
// Enable upload button when file is selected
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('file_upload');
    const uploadButton = document.getElementById('upload_button');

    if (fileInput && uploadButton) {
        fileInput.addEventListener('change', function() {
            uploadButton.disabled = !this.files.length;
        });
    }
});

/**
 * Upload file to server (v1.3)
 */
function uploadFile() {
    const fileInput = document.getElementById('file_upload');
    const uploadButton = document.getElementById('upload_button');
    const statusDiv = document.getElementById('upload_status');
    const file = fileInput.files[0];

    if (!file) {
        return;
    }

    // Disable upload button during upload
    uploadButton.disabled = true;

    // Validate file extension
    const ext = file.name.split('.').pop().toLowerCase();
    if (ext !== 'txt' && ext !== 'csv') {
        statusDiv.innerHTML = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
            '<i class="bi bi-exclamation-triangle"></i> ' +
            '<?= $this->lang->line("email_lists_invalid_file_format") ?>' +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>';
        fileInput.value = '';
        return;
    }

    // Show loading
    statusDiv.innerHTML = '<div class="alert alert-info">' +
        '<i class="bi bi-hourglass-split"></i> ' +
        '<?= $this->lang->line("email_lists_uploading") ?>...' +
        '</div>';

    // Prepare form data
    const formData = new FormData();
    formData.append('uploaded_file', file);
    formData.append('list_id', <?= $list_id ?>);

    // Upload via AJAX
    fetch('<?= controller_url($controller) ?>/upload_file/<?= $list_id ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success message
            statusDiv.innerHTML = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                '<i class="bi bi-check-circle"></i> ' +
                '<?= $this->lang->line("email_lists_file_uploaded_success") ?> ' +
                '<strong>' + data.valid_count + '</strong> <?= $this->lang->line("email_lists_addresses_imported") ?>.' +
                (data.invalid_count > 0 ? ' <span class="text-warning">' + data.invalid_count + ' <?= $this->lang->line("email_lists_addresses_invalid") ?>.</span>' : '') +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>';

            // Add file to list
            addFileToList(data.filename, data.valid_count, new Date().toISOString());

            // Update preview
            if (typeof updatePreviewCounts === 'function') {
                updatePreviewCounts();
            }
        } else {
            // Error message
            let errorHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                '<i class="bi bi-exclamation-triangle"></i> ' +
                '<?= $this->lang->line("email_lists_upload_error") ?>:';

            if (data.errors && data.errors.length > 0) {
                errorHtml += '<ul class="mb-0 mt-2">';
                data.errors.forEach(err => {
                    errorHtml += '<li>' + escapeHtml(err) + '</li>';
                });
                errorHtml += '</ul>';
            }

            errorHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            statusDiv.innerHTML = errorHtml;

            // Re-enable upload button on error
            uploadButton.disabled = false;
        }

        // Reset file input (clears selection, disables button)
        fileInput.value = '';
        uploadButton.disabled = true;
    })
    .catch(error => {
        statusDiv.innerHTML = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
            '<i class="bi bi-exclamation-triangle"></i> ' +
            '<?= $this->lang->line("email_lists_upload_error") ?>: ' + error.message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>';
        fileInput.value = '';
        uploadButton.disabled = true;
    });
}

/**
 * Delete uploaded file and its addresses
 */
function deleteFile(filename, button) {
    if (!confirm('<?= $this->lang->line("email_lists_confirm_delete_file") ?>')) {
        return;
    }

    // Disable button
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i>';

    // AJAX delete request
    fetch('<?= controller_url($controller) ?>/delete_file/<?= $list_id ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ filename: filename })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from DOM
            const fileItem = button.closest('[data-filename]');
            fileItem.remove();

            // Check if list is now empty
            const listDiv = document.getElementById('uploaded_files_list');
            if (!listDiv.querySelector('[data-filename]')) {
                listDiv.innerHTML = '<p class="text-muted fst-italic"><?= $this->lang->line("email_lists_no_files_uploaded") ?></p>';
            }

            // Update preview
            if (typeof updatePreviewCounts === 'function') {
                updatePreviewCounts();
            }

            // Show success message
            const statusDiv = document.getElementById('upload_status');
            statusDiv.innerHTML = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                '<i class="bi bi-check-circle"></i> ' +
                '<?= $this->lang->line("email_lists_file_deleted") ?> (' + data.deleted_count + ' <?= $this->lang->line("email_lists_addresses") ?>)' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>';
        } else {
            alert('<?= $this->lang->line("email_lists_delete_error") ?>: ' + (data.errors ? data.errors.join(', ') : 'Unknown error'));
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-trash"></i>';
        }
    })
    .catch(error => {
        alert('<?= $this->lang->line("email_lists_delete_error") ?>: ' + error.message);
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-trash"></i>';
    });
}

/**
 * Add file to the uploaded files list (DOM helper)
 */
function addFileToList(filename, addressCount, uploadedAt) {
    const listDiv = document.getElementById('uploaded_files_list');

    // Remove "no files" message if present
    const noFilesMsg = listDiv.querySelector('.fst-italic');
    if (noFilesMsg) {
        noFilesMsg.remove();
    }

    // Create list group if it doesn't exist
    let listGroup = listDiv.querySelector('.list-group');
    if (!listGroup) {
        listGroup = document.createElement('div');
        listGroup.className = 'list-group';
        listDiv.appendChild(listGroup);
    }

    // Format date
    const date = new Date(uploadedAt);
    const dateStr = date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});

    // Create file item
    const fileItem = document.createElement('div');
    fileItem.className = 'list-group-item';
    fileItem.setAttribute('data-filename', filename);
    fileItem.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <h6 class="mb-1">
                    <i class="bi bi-file-earmark-text"></i>
                    ${escapeHtml(filename)}
                </h6>
                <p class="mb-1 small text-muted">
                    <i class="bi bi-calendar"></i>
                    <?= $this->lang->line("email_lists_uploaded_on") ?>: ${dateStr}
                    <span class="ms-3">
                        <i class="bi bi-envelope"></i>
                        <?= $this->lang->line("email_lists_addresses_count") ?>: <strong>${addressCount}</strong>
                    </span>
                </p>
            </div>
            <button type="button"
                    class="btn btn-sm btn-outline-danger"
                    onclick="deleteFile('${escapeHtml(filename)}', this)"
                    title="<?= $this->lang->line("email_lists_delete_file") ?>">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;

    listGroup.appendChild(fileItem);
}

/**
 * Escape HTML helper
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
