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

<div class="d-flex flex-row flex-wrap">
    <div>
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

        if (!$errors) echo validation_button($action);
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
            // Existing attachment display for edit mode
            echo heading("gvv_attachments_title", 3);

            $attrs = array(
                'controller' => "attachments",
                'actions' => array('edit', 'delete'),
                'fields' => array('description', 'file'),
                'mode' => "rw",
                'class' => "fixed_datatable table table-striped",
                'param' => "?table=ecritures&id=" . $id
            );

            echo $this->gvvmetadata->table("vue_attachments", $attrs, "");
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
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.file-item-loading {
    opacity: 0.6;
    background-color: #f8f9fa;
}
.file-item .filename {
    flex-grow: 1;
    font-weight: 500;
}
.file-item .filesize {
    color: #6c757d;
    font-size: 0.875rem;
}
</style>

<script>
$(document).ready(function() {
    var uploadedFiles = {};

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

    // Add file to list UI
    function addFileToList(tempId, filename, size, isLoading) {
        var sizeStr = size > 0 ? ' (' + formatBytes(size) + ')' : '';
        var loadingClass = isLoading ? 'file-item-loading' : '';
        var removeBtn = isLoading ? '' :
            '<a href="#" onclick="removeFile(\'' + tempId + '\'); return false;"><img class="icon" src="<?= base_url() ?>themes/binary-news/images/delete.png" title="<?= $this->lang->line("gvv_confirm_remove_file") ?>" alt=""></a>';

        var html = '<div class="file-item ' + loadingClass + '" id="file_' + tempId + '">' +
                   '<span class="filename">' + filename + '</span>' +
                   '<span class="filesize">' + sizeStr + '</span> ' +
                   removeBtn +
                   '</div>';

        $('#fileList').append(html);
    }

    // Update file in list after upload completes
    function updateFileInList(oldId, fileInfo) {
        var $item = $('#file_' + oldId);
        $item.attr('id', 'file_' + fileInfo.temp_id);
        $item.removeClass('file-item-loading');
        $item.find('.filesize').text(' (' + formatBytes(fileInfo.size) + ')');
        $item.append(
            '<a href="#" onclick="removeFile(\'' + fileInfo.temp_id + '\'); return false;"><img class="icon" src="<?= base_url() ?>themes/binary-news/images/delete.png" title="<?= $this->lang->line("gvv_confirm_remove_file") ?>" alt=""></a>'
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
});
</script>

<script type="text/javascript" src="<?php echo js_url('form_ecriture'); ?>"></script>