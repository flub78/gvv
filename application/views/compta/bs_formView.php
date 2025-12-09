<!-- VIEW: application/views/compta/bs_formView.php -->
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
 * Formulaire de passage d'écritures
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('compta');
$this->lang->load('attachments');

echo '<div id="body" class="body container-fluid">';

echo checkalert($this->session, isset($popup) ? $popup : "");

?>
<h3><?= $title ?></h3>

<?php if (isset($ran_mode_enabled) && $ran_mode_enabled): ?>
<div class="alert alert-danger border border-danger" role="alert">
    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> MODE RAN ACTIVÉ</h5>
    <p class="mb-0">
        <strong>Attention:</strong> Mode de saisie rétrospective avec compensation automatique.
        Les écritures passées en 2024 seront automatiquement compensées pour préserver les soldes 2025.
        Le contrôle de date de gel est désactivé.
    </p>
</div>
<?php endif; ?>

<div class="d-flex flex-row flex-wrap">
    <div <?php if (isset($ran_mode_enabled) && $ran_mode_enabled && $action == CREATION) echo 'style="background-color: #ffe6e6; padding: 20px; border-radius: 5px;"'; ?>>
        <?php
        if (isset($message)) {
            echo p($message) . br();
        }
        if (isset($errors)) {
            // Not a validation error but manual checks
            echo p($errors, 'class="text-danger"') . br();
        }
        echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

        // hidden contrller url for java script access
        echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
        echo form_hidden('saisie_par', $saisie_par, '');
        echo form_hidden('annee_exercise', $annee_exercise, '');


        // On affiche tous les champs dans un tableau. C'est plus simple de remplir d’abord le tableau
        // et de l'afficher ensuite, surtout pour modifier l'affichage

        echo form_hidden('id', $id);
        echo form_hidden('date_creation', $date_creation);
        echo form_hidden('title_key', $title_key);
        echo form_hidden('categorie', 0);

        // Store account selection filters to preserve them during validation errors
        if (isset($emploi_selection)) {
            echo form_hidden('emploi_selection', json_encode($emploi_selection));
        }
        if (isset($resource_selection)) {
            echo form_hidden('resource_selection', json_encode($resource_selection));
        }

        echo validation_errors();
        echo ($this->gvvmetadata->form('ecritures', array(
            'date_op' => $date_op,
            //	'annee_exercise' => $annee_exercise,
            'compte1' => $compte1,
            'compte2' => $compte2,
            'montant' => $montant,
            'description' => $description,
            'num_cheque' => $num_cheque,
            //     'categorie' => $categorie,
            'gel' => $gel
        )));

        if (!isset($errors) || !$errors) {
            if (isset($frozen_message) && $frozen_message) {
                // Show disabled button with message for frozen lines
                echo '<div class="alert alert-warning mt-3" role="alert">';
                echo '<i class="bi bi-lock-fill"></i> ' . $frozen_message;
                echo '</div>';
                echo '<button type="submit" class="btn btn-primary mt-3" disabled>';
                echo $this->lang->line("gvv_button_validate");
                echo '</button>';
            } else {
                echo validation_button($action);
            }
        }
        echo form_close();
        ?>
    </div>
    <div class="ms-4">
        <?php
        if ($action == CREATION) {
            // Inline Attachment Upload (for creation)
            echo heading("gvv_attachments_title", 3);
            echo '<small class="text-muted">(' . $this->lang->line("gvv_optional") . ')</small>';
        ?>
            <div class="form-group mt-2">
                <div class="attachment-upload-area" id="attachmentDropZone">
                    <input type="file" name="attachment_files[]" id="fileInput" multiple
                           accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.csv,.txt"
                           style="display:none;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('fileInput').click();">
                        <i class="bi bi-paperclip"></i> <?= $this->lang->line("gvv_choose_files") ?>
                    </button>
                    <small class="form-text text-muted d-block mt-1">
                        <?= $this->lang->line("gvv_supported_formats") ?>: PDF, Images, Office, CSV (Max 20MB)
                    </small>
                </div>
                <div id="fileList" class="file-list mt-2">
                    <!-- JavaScript will populate uploaded files here -->
                </div>
            </div>
        <?php
        } elseif ($action == MODIFICATION || $action == VISUALISATION) {
            // Existing attachment display for edit mode with inline editing
            echo heading("gvv_attachments_title", 3);

            // Container for attachments section
            echo '<div id="attachmentsFormSection" data-ecriture-id="' . $id . '">';
            echo '</div>';
        }
        ?>
    </div>
</div>

<style>
.file-list {
    margin-top: 10px;
}
.file-item {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 8px;
    background-color: #fff;
}
.file-item-loading {
    opacity: 0.6;
    background-color: #f8f9fa;
}
.file-item-header {
    display: flex;
    align-items: center;
    gap: 10px;
}
.file-item .filename {
    flex-grow: 1;
    font-weight: 500;
}
.file-item .filesize {
    color: #6c757d;
    font-size: 0.875rem;
}
/* PRD CA1.9: Style for description input */
.description-input {
    width: 100%;
    font-size: 0.875rem;
}
</style>

<script>
$(document).ready(function() {
    var uploadedFiles = {};

    // PRD CA1.9: Restore pending attachments from session (on validation error)
    <?php if (isset($pending_attachments) && !empty($pending_attachments)): ?>
        <?php foreach ($pending_attachments as $temp_id => $file_info): ?>
            uploadedFiles['<?= $temp_id ?>'] = <?= json_encode($file_info) ?>;
            addFileToList(
                '<?= $temp_id ?>',
                '<?= addslashes($file_info['original_name']) ?>',
                <?= $file_info['size'] ?>,
                false
            );
            // Set description if it exists
            <?php if (!empty($file_info['description'])): ?>
                $('[data-temp-id="<?= $temp_id ?>"]').val('<?= addslashes($file_info['description']) ?>');
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    // Handle file selection
    $('#fileInput').on('change', function(e) {
        var files = e.target.files;
        for (var i = 0; i < files.length; i++) {
            uploadFile(files[i]);
        }
        // Clear input so same file can be re-uploaded if needed
        $(this).val('');
    });

    // Upload file via AJAX
    function uploadFile(file) {
        var formData = new FormData();
        formData.append('file', file);

        // Add file to UI immediately with loading state
        var tempId = 'uploading_' + Date.now();
        addFileToList(tempId, file.name, 0, true);

        $.ajax({
            url: '<?= base_url() ?>index.php/compta/upload_temp_attachment',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update UI with actual temp_id
                    updateFileInList(tempId, response.file);
                    uploadedFiles[response.file.temp_id] = response.file;
                } else {
                    // Show error
                    removeFileFromList(tempId);
                    alert('Upload failed: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                removeFileFromList(tempId);
                alert('Upload failed: ' + error);
            }
        });
    }

    // Add file to list UI (PRD CA1.9: includes description input)
    function addFileToList(tempId, filename, size, isLoading) {
        var sizeStr = size > 0 ? ' (' + formatBytes(size) + ')' : '';
        var loadingClass = isLoading ? 'file-item-loading' : '';
        var removeBtn = isLoading ? '' :
            '<a href="#" onclick="removeFile(\'' + tempId + '\'); return false;"><img class="icon" src="<?= base_url() ?>themes/binary-news/images/delete.png" title="<?= $this->lang->line("gvv_confirm_remove_file") ?>" alt=""></a>';

        // PRD CA1.9: Add description input field
        var descriptionField = isLoading ? '' :
            '<input type="text" class="form-control form-control-sm description-input mt-2" ' +
            'placeholder="<?= $this->lang->line("gvv_attachment_description") ?>" ' +
            'data-temp-id="' + tempId + '" />';

        var html = '<div class="file-item ' + loadingClass + '" id="file_' + tempId + '">' +
                   '<div class="file-item-header">' +
                   '<span class="filename">' + filename + '</span>' +
                   '<span class="filesize">' + sizeStr + '</span> ' +
                   removeBtn +
                   '</div>' +
                   descriptionField +
                   '</div>';

        $('#fileList').append(html);
    }

    // Update file in list after upload completes
    function updateFileInList(oldId, fileInfo) {
        var $item = $('#file_' + oldId);
        $item.attr('id', 'file_' + fileInfo.temp_id);
        $item.removeClass('file-item-loading');
        $item.find('.filesize').text(' (' + formatBytes(fileInfo.size) + ')');

        // PRD CA1.9: Add remove button and description field
        $item.find('.file-item-header').append(
            '<a href="#" onclick="removeFile(\'' + fileInfo.temp_id + '\'); return false;"><img class="icon" src="<?= base_url() ?>themes/binary-news/images/delete.png" title="<?= $this->lang->line("gvv_confirm_remove_file") ?>" alt=""></a>'
        );

        $item.append(
            '<input type="text" class="form-control form-control-sm description-input mt-2" ' +
            'placeholder="<?= $this->lang->line("gvv_attachment_description") ?>" ' +
            'data-temp-id="' + fileInfo.temp_id + '" />'
        );
    }

    // Remove file from list
    function removeFileFromList(tempId) {
        $('#file_' + tempId).remove();
    }

    // Format bytes for display
    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // PRD CA1.9: Handle description changes
    $(document).on('blur', '.description-input', function() {
        var tempId = $(this).data('temp-id');
        var description = $(this).val();

        $.ajax({
            url: '<?= base_url() ?>index.php/compta/update_temp_attachment_description',
            type: 'POST',
            data: {
                temp_id: tempId,
                description: description
            },
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    alert('Failed to update description: ' + (response.error || 'Unknown error'));
                }
            },
            error: function() {
                // Silent failure for description updates
                console.log('Failed to update description for ' + tempId);
            }
        });
    });

    // Global function for remove button
    window.removeFile = function(tempId) {
        if (!confirm('<?= $this->lang->line("gvv_confirm_remove_file") ?>')) {
            return;
        }

        $.ajax({
            url: '<?= base_url() ?>index.php/compta/remove_temp_attachment',
            type: 'POST',
            data: { temp_id: tempId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    removeFileFromList(tempId);
                    delete uploadedFiles[tempId];
                } else {
                    alert('Failed to remove file: ' + response.error);
                }
            },
            error: function() {
                alert('Failed to remove file');
            }
        });
    };

    // Load attachments for edit/view mode
    <?php if (($action == MODIFICATION || $action == VISUALISATION) && isset($id) && $id): ?>
    var ecritureId = <?= $id ?>;
    loadAttachmentsForForm(ecritureId);
    <?php endif; ?>
});

// Load attachments section for the form
function loadAttachmentsForForm(ecritureId) {
    $('#attachmentsFormSection').html('<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></div>');

    $.ajax({
        url: '<?= site_url('compta/get_attachments_section') ?>/' + ecritureId,
        method: 'GET',
        success: function(response) {
            $('#attachmentsFormSection').html(response);
            initializeFormAttachmentHandlers(ecritureId);
        },
        error: function(xhr, status, error) {
            $('#attachmentsFormSection').html('<div class="alert alert-danger">Erreur lors du chargement des justificatifs</div>');
        }
    });
}

// Initialize attachment handlers for the form (similar to modal but for form context)
function initializeFormAttachmentHandlers(ecritureId) {
    // Edit button click
    $(document).off('click', '#attachmentsFormSection .edit-attachment-btn').on('click', '#attachmentsFormSection .edit-attachment-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.view-mode').hide();
        $row.find('.edit-mode').show();
    });

    // Cancel button click
    $(document).off('click', '#attachmentsFormSection .cancel-edit-btn').on('click', '#attachmentsFormSection .cancel-edit-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.edit-mode').hide();
        $row.find('.view-mode').show();
        $row.find('.error-message').hide().text('');
    });

    // Save button click
    $(document).off('click', '#attachmentsFormSection .save-attachment-btn').on('click', '#attachmentsFormSection .save-attachment-btn', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var attachmentId = $row.data('attachment-id');
        var description = $row.find('.description-input').val();
        var fileInput = $row.find('.file-input')[0];

        if (!attachmentId) {
            $row.find('.error-message').show().text('Erreur: ID de justificatif manquant');
            return;
        }

        $row.find('.error-message').hide().text('');

        var formData = new FormData();
        formData.append('attachment_id', attachmentId);
        formData.append('description', description);
        if (fileInput.files.length > 0) {
            formData.append('file', fileInput.files[0]);
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '<?= base_url() ?>index.php/compta/update_attachment',
            type: 'POST',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $row.find('.description-text').text(response.description);
                    if (response.file_name) {
                        $row.find('.view-mode a').attr('href', response.file_url).text(response.file_name);
                    }
                    $row.find('.edit-mode').hide();
                    $row.find('.view-mode').show();
                    $row.find('.file-input').val('');
                    showFormSuccessToast('Justificatif modifié avec succès');
                } else {
                    $row.find('.error-message').show().text(response.error || 'Erreur lors de la modification');
                }
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            },
            error: function(xhr) {
                var errorMsg = 'Erreur lors de la modification';
                try {
                    var response = JSON.parse(xhr.responseText);
                    errorMsg = response.error || errorMsg;
                } catch(e) {}
                $row.find('.error-message').show().text(errorMsg);
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            }
        });
    });

    // Delete button click
    $(document).off('click', '#attachmentsFormSection .delete-attachment-btn').on('click', '#attachmentsFormSection .delete-attachment-btn', function() {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce justificatif ?')) {
            return;
        }

        var $btn = $(this);
        var $row = $btn.closest('tr');
        var attachmentId = $row.data('attachment-id');

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '<?= base_url() ?>index.php/compta/delete_attachment',
            type: 'POST',
            method: 'POST',
            data: { attachment_id: attachmentId },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        if ($('#attachmentsFormSection #attachmentsTable tbody tr').length === 0) {
                            $('#attachmentsFormSection #attachmentsTable').replaceWith('<div class="alert alert-info">Aucun justificatif</div>');
                        }
                    });
                    showFormSuccessToast('Justificatif supprimé avec succès');
                } else {
                    alert('Erreur: ' + response.error);
                    $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Erreur lors de la suppression';
                try {
                    var response = JSON.parse(xhr.responseText);
                    errorMsg = response.error || errorMsg;
                } catch(e) {}
                alert(errorMsg);
                $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
            }
        });
    });

    // Show create form
    $(document).off('click', '#attachmentsFormSection #showCreateForm').on('click', '#attachmentsFormSection #showCreateForm', function() {
        $('#attachmentsFormSection #createAttachmentCard').slideDown();
        $(this).hide();
    });

    // Cancel create
    $(document).off('click', '#attachmentsFormSection #cancelNewAttachment').on('click', '#attachmentsFormSection #cancelNewAttachment', function() {
        $('#attachmentsFormSection #createAttachmentCard').slideUp();
        $('#attachmentsFormSection #showCreateForm').show();
        $('#attachmentsFormSection #newDescription').val('');
        $('#attachmentsFormSection #newFile').val('');
        $('#attachmentsFormSection #createErrorMessage').hide().text('');
    });

    // Save new attachment
    $(document).off('click', '#attachmentsFormSection #saveNewAttachment').on('click', '#attachmentsFormSection #saveNewAttachment', function() {
        var $btn = $(this);
        var description = $('#attachmentsFormSection #newDescription').val();
        var fileInput = $('#attachmentsFormSection #newFile')[0];

        $('#attachmentsFormSection #createErrorMessage').hide().text('');

        if (!description) {
            $('#attachmentsFormSection #createErrorMessage').show().text('La description est requise');
            return;
        }
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            $('#attachmentsFormSection #createErrorMessage').show().text('Le fichier est requis');
            return;
        }

        var formData = new FormData();
        formData.append('ecriture_id', ecritureId);
        formData.append('description', description);
        formData.append('file', fileInput.files[0]);

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: '<?= base_url() ?>index.php/compta/create_attachment',
            type: 'POST',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Add new row to table
                    var newRow = '<tr id="attachment-row-' + response.attachment_id + '" data-attachment-id="' + response.attachment_id + '">';
                    newRow += '<td class="attachment-cell">';
                    newRow += '<div class="view-mode"><span class="description-text">' + response.description + '</span></div>';
                    newRow += '<div class="edit-mode" style="display: none;">';
                    newRow += '<input type="text" class="form-control form-control-sm description-input" value="' + response.description + '">';
                    newRow += '<div class="text-danger mt-1 error-message" style="display: none;"></div>';
                    newRow += '</div></td>';
                    newRow += '<td class="attachment-cell">';
                    newRow += '<div class="view-mode"><a href="' + response.file_url + '" target="_blank">' + response.file_name + '</a></div>';
                    newRow += '<div class="edit-mode" style="display: none;">';
                    newRow += '<input type="file" class="form-control form-control-sm file-input" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">';
                    newRow += '<small class="text-muted">Laissez vide pour conserver le fichier actuel</small>';
                    newRow += '<div class="text-danger mt-1 error-message" style="display: none;"></div>';
                    newRow += '</div></td>';
                    newRow += '<td class="attachment-cell">';
                    newRow += '<div class="view-mode">';
                    newRow += '<button class="btn btn-sm btn-primary edit-attachment-btn" title="Modifier"><i class="fas fa-edit"></i></button> ';
                    newRow += '<button class="btn btn-sm btn-danger delete-attachment-btn" title="Supprimer"><i class="fas fa-trash"></i></button>';
                    newRow += '</div>';
                    newRow += '<div class="edit-mode" style="display: none;">';
                    newRow += '<button class="btn btn-sm btn-success save-attachment-btn" title="Enregistrer"><i class="fas fa-check"></i></button> ';
                    newRow += '<button class="btn btn-sm btn-secondary cancel-edit-btn" title="Annuler"><i class="fas fa-times"></i></button>';
                    newRow += '</div></td>';
                    newRow += '</tr>';

                    if ($('#attachmentsFormSection #attachmentsTable').length === 0) {
                        var tableHtml = '<table class="table table-striped table-sm" id="attachmentsTable">';
                        tableHtml += '<thead><tr><th style="width: 40%;">Description</th><th style="width: 35%;">Fichier</th><th style="width: 25%;">Actions</th></tr></thead>';
                        tableHtml += '<tbody>' + newRow + '</tbody></table>';
                        $('#attachmentsFormSection #showCreateForm').before(tableHtml);
                    } else {
                        $('#attachmentsFormSection #attachmentsTable tbody').append(newRow);
                    }

                    $('#attachmentsFormSection #newDescription').val('');
                    $('#attachmentsFormSection #newFile').val('');
                    $('#attachmentsFormSection #createAttachmentCard').slideUp();
                    $('#attachmentsFormSection #showCreateForm').show();

                    showFormSuccessToast('Justificatif créé avec succès');
                } else {
                    $('#attachmentsFormSection #createErrorMessage').show().text(response.error || 'Erreur lors de la création');
                }
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
            },
            error: function(xhr) {
                var errorMsg = 'Erreur lors de la création';
                try {
                    var response = JSON.parse(xhr.responseText);
                    errorMsg = response.error || errorMsg;
                } catch(e) {}
                $('#attachmentsFormSection #createErrorMessage').show().text(errorMsg);
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
            }
        });
    });
}

// Success toast for form
function showFormSuccessToast(message) {
    var toast = $('<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">')
        .html(message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    $('body').append(toast);
    setTimeout(function() {
        toast.fadeOut(300, function() { $(this).remove(); });
    }, 3000);
}

// Handle unfreezing an entry from the edit form (VISUALISATION mode only)
$(document).ready(function() {
    <?php if ($action == VISUALISATION && isset($id) && $id): ?>
    // Only in VISUALISATION mode: allow unfreezing by unchecking the gel checkbox
    var ecritureId = <?= $id ?>;
    var gelCheckbox = $('input[name="gel"]');

    if (gelCheckbox.length > 0) {
        // Store initial checked state (should be true in VISUALISATION mode)
        var wasInitiallyFrozen = gelCheckbox.is(':checked');

        gelCheckbox.on('change', function() {
            var isNowChecked = $(this).is(':checked');

            // Only handle unchecking a frozen entry (defrost action)
            if (wasInitiallyFrozen && !isNowChecked) {
                // Disable checkbox during AJAX request
                gelCheckbox.prop('disabled', true);

                $.ajax({
                    url: '<?= site_url("compta/toggle_gel") ?>',
                    type: 'POST',
                    data: {
                        id: ecritureId,
                        gel: 0  // Unfreeze
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Reload page to switch from VISUALISATION to MODIFICATION mode
                            location.reload();
                        } else {
                            // Error - revert checkbox state
                            gelCheckbox.prop('checked', true);
                            gelCheckbox.prop('disabled', false);
                            alert('Erreur lors du dégel: ' + (response.message || 'Erreur inconnue'));
                        }
                    },
                    error: function(xhr, status, error) {
                        // Error - revert checkbox state
                        gelCheckbox.prop('checked', true);
                        gelCheckbox.prop('disabled', false);
                        alert('Erreur lors du dégel: ' + error);
                    }
                });
            }
        });
    }
    <?php endif; ?>

    <?php if ($action == MODIFICATION && isset($id) && $id): ?>
    // In MODIFICATION mode: normal behavior, allow checking/unchecking gel checkbox
    // No special handling needed - standard form submission will handle it
    <?php endif; ?>
});
</script>

<script type="text/javascript" src="<?php echo js_url('form_ecriture'); ?>"></script>