<!-- VIEW: application/views/compta/bs_saisie_cotisation_formView.php -->
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
 * Formulaire de saisie simplifiée de cotisation
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
        echo form_open('compta/formValidation_saisie_cotisation', array('name' => 'saisie_cotisation', 'id' => 'form_saisie_cotisation'));

        echo validation_errors();
        ?>

        <!-- Section Membre et Cotisation -->
        <fieldset class="border p-3 mb-3">
            <legend class="w-auto px-2"><?= $this->lang->line('gvv_compta_label_pilote') ?> & <?= $this->lang->line('gvv_compta_label_annee_cotisation') ?></legend>

            <div class="mb-3">
                <label for="pilote" class="form-label"><?= $this->lang->line('gvv_compta_label_pilote') ?> <span class="text-danger">*</span></label>
                <?= form_dropdown('pilote', $pilote_selector, $pilote, 'class="form-select" id="pilote"') ?>
            </div>

            <div class="mb-3">
                <label for="annee_cotisation" class="form-label"><?= $this->lang->line('gvv_compta_label_annee_cotisation') ?> <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="annee_cotisation" name="annee_cotisation" value="<?= $annee_cotisation ?>" min="2000" max="2100">
            </div>
        </fieldset>

        <!-- Section Comptes -->
        <fieldset class="border p-3 mb-3">
            <legend class="w-auto px-2"><?= $this->lang->line('gvv_compta_comptes') ?></legend>

            <div class="mb-3">
                <label for="compte_banque" class="form-label"><?= $this->lang->line('gvv_compta_label_compte_banque') ?> <span class="text-danger">*</span></label>
                <?= form_dropdown('compte_banque', $compte_banque_selector, $compte_banque, 'class="form-select" id="compte_banque"') ?>
            </div>

            <div class="mb-3">
                <label for="compte_pilote" class="form-label"><?= $this->lang->line('gvv_compta_label_compte_pilote') ?> <span class="text-danger">*</span></label>
                <?= form_dropdown('compte_pilote', $compte_pilote_selector, $compte_pilote, 'class="form-select" id="compte_pilote"') ?>
            </div>

            <div class="mb-3">
                <label for="compte_recette" class="form-label"><?= $this->lang->line('gvv_compta_label_compte_recette') ?> <span class="text-danger">*</span></label>
                <?= form_dropdown('compte_recette', $compte_recette_selector, $compte_recette, 'class="form-select" id="compte_recette"') ?>
            </div>
        </fieldset>

        <!-- Section Paiement -->
        <fieldset class="border p-3 mb-3">
            <legend class="w-auto px-2">Paiement</legend>

            <div class="mb-3">
                <label for="date_op" class="form-label"><?= $this->lang->line('gvv_ecritures_field_date_op') ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control activity_date" id="date_op" name="date_op" value="<?= $date_op ?>" placeholder="dd/mm/yyyy">
            </div>

            <div class="mb-3">
                <label for="montant" class="form-label"><?= $this->lang->line('gvv_compta_label_montant') ?> <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="montant" name="montant" value="<?= $montant ?>" step="0.01" min="0.01">
            </div>

            <div class="mb-3">
                <label for="description" class="form-label"><?= $this->lang->line('gvv_ecritures_field_description') ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control description" id="description" name="description" value="<?= $description ?>">
            </div>

            <div class="mb-3">
                <label for="num_cheque" class="form-label"><?= $this->lang->line('gvv_ecritures_field_num_cheque') ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control num_cheque" id="num_cheque" name="num_cheque" value="<?= $num_cheque ?>">
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                <?= form_dropdown('type', $type_paiement_selector, $type, 'class="form-select" id="type"') ?>
            </div>
        </fieldset>

        <!-- Section Justificatifs (optionnelle) -->
        <fieldset class="border p-3 mb-3">
            <legend class="w-auto px-2"><?= $this->lang->line("gvv_attachments_title") ?> <small class="text-muted">(<?= $this->lang->line("gvv_optional") ?>)</small></legend>

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
        </fieldset>

        <!-- Boutons de validation -->
        <div class="mb-3">
            <button type="submit" id="btnValidate" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> <?= $this->lang->line("gvv_button_validate") ?>
            </button>
            <button type="button" class="btn btn-secondary" onclick="history.back()">
                <i class="bi bi-x-circle"></i> Annuler
            </button>
        </div>

        <?php
        echo form_close();
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
.description-input {
    width: 100%;
    font-size: 0.875rem;
}
</style>

<script>
$(document).ready(function() {
    var uploadedFiles = {};
    var formChanged = false;
    var submitSuccess = <?= $this->session->flashdata('success') ? 'true' : 'false' ?>;

    // Désactiver bouton si succès précédent
    if (submitSuccess) {
        $('#btnValidate').prop('disabled', true);
        formChanged = false;
    }

    // Réactiver bouton si changement de formulaire
    $('#form_saisie_cotisation input, #form_saisie_cotisation select').on('change input', function() {
        if (!formChanged) {
            $('#btnValidate').prop('disabled', false);
            formChanged = true;
        }
    });

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

    // Handle description changes
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
});
</script>

<?= '</div>' ?>
