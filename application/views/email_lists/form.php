<!-- VIEW: application/views/email_lists/form.php -->
<?php
/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Vue formulaire de création/édition de liste de diffusion
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('email_lists');

$is_edit = isset($list['id']);
$list_id = $is_edit ? $list['id'] : 0;
?>
<div id="body" class="body container-fluid">
    <h3><?= $title ?></h3>

<?php
// Show validation errors
if (validation_errors()) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-exclamation-triangle-fill"></i></strong> ';
    echo validation_errors();
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Show error message
if ($this->session->flashdata('error')) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-exclamation-triangle-fill"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('error')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

    <form action="<?= controller_url($controller) ?>/<?= $action ?><?= $is_edit ? '/' . $list_id : '' ?>"
          method="post"
          accept-charset="utf-8"
          name="email_list_form"
          id="email_list_form">

        <!-- Basic information -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row mb-3">
                    <label for="name" class="col-sm-2 col-form-label">
                        <?= $this->lang->line("email_lists_name") ?> <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-10">
                        <input type="text"
                               class="form-control"
                               id="name"
                               name="name"
                               value="<?= htmlspecialchars(set_value('name', $list['name'])) ?>"
                               required>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="description" class="col-sm-2 col-form-label">
                        <?= $this->lang->line("email_lists_description") ?>
                    </label>
                    <div class="col-sm-10">
                        <textarea class="form-control"
                                  id="description"
                                  name="description"
                                  rows="3"><?= htmlspecialchars(set_value('description', $list['description'])) ?></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="active_member" class="col-sm-2 col-form-label">
                        <?= $this->lang->line("email_lists_active_member") ?>
                    </label>
                    <div class="col-sm-10">
                        <select class="form-select" id="active_member" name="active_member">
                            <option value="active" <?= set_select('active_member', 'active', $list['active_member'] == 'active') ?>>
                                <?= $this->lang->line("email_lists_active_members_only") ?>
                            </option>
                            <option value="inactive" <?= set_select('active_member', 'inactive', $list['active_member'] == 'inactive') ?>>
                                <?= $this->lang->line("email_lists_inactive_members_only") ?>
                            </option>
                            <option value="all" <?= set_select('active_member', 'all', $list['active_member'] == 'all') ?>>
                                <?= $this->lang->line("email_lists_all_members") ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-10 offset-sm-2">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="visible"
                                   name="visible"
                                   value="1"
                                   <?= set_checkbox('visible', '1', $list['visible']) ?>>
                            <label class="form-check-label" for="visible">
                                <?= $this->lang->line("email_lists_visible") ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Split-panel layout: Tabs on left, Preview on right -->
        <div class="row">
            <!-- Left panel: Tabs for list sources -->
            <div class="col-lg-8">
                <ul class="nav nav-tabs mb-3" id="listTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active"
                                id="criteria-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#criteria"
                                type="button"
                                role="tab"
                                aria-controls="criteria"
                                aria-selected="true">
                            <i class="bi bi-funnel"></i> <?= $this->lang->line("email_lists_tab_criteria") ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="manual-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#manual"
                                type="button"
                                role="tab"
                                aria-controls="manual"
                                aria-selected="false">
                            <i class="bi bi-person-plus"></i> <?= $this->lang->line("email_lists_tab_manual") ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="import-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#import"
                                type="button"
                                role="tab"
                                aria-controls="import"
                                aria-selected="false">
                            <i class="bi bi-envelope-at"></i> <?= $this->lang->line("email_lists_tab_external") ?>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="listTabsContent">
                    <!-- Criteria tab -->
                    <div class="tab-pane fade show active"
                         id="criteria"
                         role="tabpanel"
                         aria-labelledby="criteria-tab">
                        <?php $this->load->view('email_lists/_criteria_tab'); ?>
                    </div>

                    <!-- Manual selection tab -->
                    <div class="tab-pane fade"
                         id="manual"
                         role="tabpanel"
                         aria-labelledby="manual-tab">
                        <?php $this->load->view('email_lists/_manual_tab'); ?>
                    </div>

                    <!-- Import tab -->
                    <div class="tab-pane fade"
                         id="import"
                         role="tabpanel"
                         aria-labelledby="import-tab">
                        <?php $this->load->view('email_lists/_import_tab'); ?>
                    </div>
                </div>
            </div>

            <!-- Right panel: List under construction preview -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul"></i>
                            <?= $this->lang->line("email_lists_list_under_construction") ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="preview_summary" class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold"><?= $this->lang->line("email_lists_total_recipients") ?>:</span>
                                <span class="badge bg-primary" id="total_count">0</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?= $this->lang->line("email_lists_from_criteria") ?>:</span>
                                <span class="badge bg-secondary" id="criteria_count">0</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?= $this->lang->line("email_lists_manual_members") ?>:</span>
                                <span class="badge bg-secondary" id="manual_count">0</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?= $this->lang->line("email_lists_external_emails") ?>:</span>
                                <span class="badge bg-secondary" id="external_count">0</span>
                            </div>
                        </div>

                        <hr>

                        <div id="preview_list_container" style="max-height: 500px; overflow-y: auto;">
                            <h6 class="text-muted mb-2"><?= $this->lang->line("email_lists_preview") ?>:</h6>
                            <div id="preview_list" class="small">
                                <p class="text-muted fst-italic">
                                    <?= $this->lang->line("email_lists_select_criteria_to_preview") ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form actions -->
        <div class="mt-4 mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> <?= $this->lang->line("gvv_button_save") ?>
            </button>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> <?= $this->lang->line("gvv_button_cancel") ?>
            </a>
        </div>

    </form>

</div>

<script>
/**
 * Update counts in the preview panel
 */
function updatePreviewCounts() {
    // Count criteria selections
    const criteriaCount = document.querySelectorAll('input[name="roles[]"]:checked').length;
    document.getElementById('criteria_count').textContent = criteriaCount;

    // Count manual members
    const manualCount = document.querySelectorAll('input[name="manual_members[]"]').length;
    document.getElementById('manual_count').textContent = manualCount;

    // Count external emails
    const externalCount = document.querySelectorAll('input[name="external_emails[]"]').length;
    document.getElementById('external_count').textContent = externalCount;

    // Auto-refresh the full list preview from server
    refreshListPreview();
}

/**
 * Refresh the full list preview from server
 */
function refreshListPreview() {
    const previewDiv = document.getElementById('preview_list');

    // Show loading
    previewDiv.innerHTML = '<p class="text-muted fst-italic"><i class="bi bi-hourglass-split"></i> <?= $this->lang->line("gvv_str_loading") ?>...</p>';

    // Get form data
    const formData = new FormData();

    // Add roles
    document.querySelectorAll('input[name="roles[]"]:checked').forEach(function(checkbox) {
        formData.append('roles[]', checkbox.value);
    });

    // Add manual members
    document.querySelectorAll('input[name="manual_members[]"]').forEach(function(input) {
        formData.append('manual_members[]', input.value);
    });

    // Add external emails and names
    document.querySelectorAll('input[name="external_emails[]"]').forEach(function(input) {
        formData.append('external_emails[]', input.value);
    });
    document.querySelectorAll('input[name="external_names[]"]').forEach(function(input) {
        formData.append('external_names[]', input.value);
    });

    // Add active_member filter
    formData.append('active_member', document.getElementById('active_member').value);

    // AJAX request to preview endpoint
    fetch('<?= controller_url($controller) ?>/preview_list', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update total count
            document.getElementById('total_count').textContent = data.total;

            // Update criteria count (actual unique recipients from criteria)
            if (data.criteria_count !== undefined) {
                document.getElementById('criteria_count').textContent = data.criteria_count;
            }

            // Display email list as table
            if (data.emails && data.emails.length > 0) {
                let html = '<table class="table table-sm table-hover mb-0">';
                html += '<tbody>';

                data.emails.slice(0, 20).forEach(function(item) {
                    const email = typeof item === 'string' ? item : item.email;
                    const name = typeof item === 'object' && item.name ? item.name : '';
                    const isExternal = typeof item === 'object' && item.is_external;

                    html += '<tr>';
                    html += '<td class="text-break"><code class="small text-success">' + email + '</code></td>';
                    html += '<td class="text-muted small">' + (name ? name : '') + '</td>';
                    html += '<td class="text-end" style="width: 40px;">';

                    if (isExternal) {
                        html += '<button type="button" class="btn btn-sm btn-danger" onclick="deleteFromPreview(\'' + email + '\')" title="<?= $this->lang->line("gvv_button_delete") ?>">';
                        html += '<i class="fas fa-trash" aria-hidden="true"></i>';
                        html += '</button>';
                    }

                    html += '</td>';
                    html += '</tr>';
                });

                html += '</tbody>';
                html += '</table>';

                if (data.emails.length > 20) {
                    html += '<p class="text-muted fst-italic small mt-2">... <?= $this->lang->line("gvv_str_and") ?> ' + (data.emails.length - 20) + ' <?= $this->lang->line("gvv_str_others") ?></p>';
                }

                previewDiv.innerHTML = html;
            } else {
                previewDiv.innerHTML = '<p class="text-muted fst-italic"><?= $this->lang->line("email_lists_select_criteria_to_preview") ?></p>';
            }
        } else {
            previewDiv.innerHTML = '<p class="text-danger"><i class="bi bi-exclamation-triangle"></i> ' + (data.message || '<?= $this->lang->line("email_lists_preview_error") ?>') + '</p>';
        }
    })
    .catch(error => {
        previewDiv.innerHTML = '<p class="text-danger"><i class="bi bi-exclamation-triangle"></i> <?= $this->lang->line("email_lists_preview_error") ?></p>';
    });
}

// Update counts when form changes
document.addEventListener('DOMContentLoaded', function() {
    // Initial count
    updatePreviewCounts();

    // Listen for changes on criteria checkboxes
    document.querySelectorAll('input[name="roles[]"]').forEach(function(checkbox) {
        checkbox.addEventListener('change', updatePreviewCounts);
    });

    // Listen for changes on active_member filter
    document.getElementById('active_member').addEventListener('change', updatePreviewCounts);

    // Override addManualMember to update counts
    const originalAddManualMember = window.addManualMember;
    window.addManualMember = function() {
        originalAddManualMember();
        updatePreviewCounts();
    };

    // Override removeManualMember to update counts
    const originalRemoveManualMember = window.removeManualMember;
    window.removeManualMember = function(button) {
        originalRemoveManualMember(button);
        updatePreviewCounts();
    };

    // Override addExternalEmailToList to update counts
    const originalAddExternalEmailToList = window.addExternalEmailToList;
    window.addExternalEmailToList = function(email, name) {
        originalAddExternalEmailToList(email, name);
        updatePreviewCounts();
    };

    // Override removeExternalEmail to update counts
    const originalRemoveExternalEmail = window.removeExternalEmail;
    window.removeExternalEmail = function(button) {
        originalRemoveExternalEmail(button);
        updatePreviewCounts();
    };

    // Override confirmImport to update counts
    const originalConfirmImport = window.confirmImport;
    window.confirmImport = function() {
        originalConfirmImport();
        updatePreviewCounts();
    };
});

/**
 * Delete an external email from the list (called from preview panel)
 */
function deleteFromPreview(email) {
    // Find and remove the corresponding hidden inputs in external_emails_list
    const listDiv = document.getElementById('external_emails_list');
    const emailInputs = listDiv.querySelectorAll('input[name="external_emails[]"]');

    emailInputs.forEach(function(input) {
        if (input.value.toLowerCase() === email.toLowerCase()) {
            // Remove the entire parent div (the list item)
            input.closest('div.d-flex').remove();
        }
    });

    // Update preview
    if (typeof updatePreviewCounts === 'function') {
        updatePreviewCounts();
    }
}
</script>
