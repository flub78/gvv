<!-- VIEW: application/views/archived_documents/bs_editView.php -->
<?php
/**
 * In-place edit form: modify label, description, dates, or replace file.
 * Does NOT create a new version.
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');
?>

<div id="body" class="body container-fluid">

<h3>
    <i class="fas fa-edit"></i> <?= $this->lang->line('archived_documents_edit_title') ?>
</h3>

<?php if (isset($message)): ?>
    <?= $message ?>
<?php endif; ?>

<?= form_open_multipart('archived_documents/edit_docValidation/' . $document['id'], array('class' => 'form-horizontal')) ?>

<div class="card">
    <div class="card-body">

        <!-- Pilote -->
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_pilot') ?>
            </label>
            <div class="col-sm-10">
                <?php if (!empty($is_admin) && !empty($pilot_selector)): ?>
                    <?= form_dropdown('pilot_login', $pilot_selector, isset($document['pilot_login']) ? $document['pilot_login'] : '', 'class="form-select big_select" id="pilot_login"') ?>
                <?php else: ?>
                    <p class="form-control-plaintext">
                        <?php if (!empty($document['pilot_prenom']) || !empty($document['pilot_nom'])): ?>
                            <?= htmlspecialchars(trim($document['pilot_prenom'] . ' ' . $document['pilot_nom'])) ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section -->
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_section') ?>
            </label>
            <div class="col-sm-10">
                <?php if (!empty($is_admin) && !empty($section_selector)): ?>
                    <?= form_dropdown('section_id', $section_selector, isset($document['section_id']) ? $document['section_id'] : '', 'class="form-select" id="section_id"') ?>
                <?php else: ?>
                    <p class="form-control-plaintext">
                        <?= !empty($document['section_name']) ? htmlspecialchars($document['section_name']) : '<span class="text-muted">-</span>' ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Description -->
        <div class="mb-3 row">
            <label for="description" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_description') ?>
            </label>
            <div class="col-sm-10">
                <?= form_input('description', set_value('description', isset($document['description']) ? $document['description'] : ''), 'class="form-control" id="description" maxlength="255"') ?>
            </div>
        </div>

        <!-- Valid from -->
        <div class="mb-3 row">
            <label for="valid_from" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_valid_from') ?>
            </label>
            <div class="col-sm-10">
                <?php
                $vf = isset($document['valid_from']) && $document['valid_from'] ? date('d/m/Y', strtotime($document['valid_from'])) : '';
                ?>
                <?= form_input('valid_from', set_value('valid_from', $vf), 'class="form-control datepicker" id="valid_from" placeholder="jj/mm/aaaa"') ?>
            </div>
        </div>

        <!-- Valid until -->
        <div class="mb-3 row">
            <label for="valid_until" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_valid_until') ?>
            </label>
            <div class="col-sm-10">
                <?php
                $vu = isset($document['valid_until']) && $document['valid_until'] ? date('d/m/Y', strtotime($document['valid_until'])) : '';
                ?>
                <?= form_input('valid_until', set_value('valid_until', $vu), 'class="form-control datepicker" id="valid_until" placeholder="jj/mm/aaaa"') ?>
            </div>
        </div>

        <!-- Machine selector -->
        <?php if (!empty($machine_selector)): ?>
        <div class="mb-3 row">
            <label for="machine_immat" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_machine') ?>
            </label>
            <div class="col-sm-10">
                <?php
                $selected_machine = isset($document['machine_immat']) ? $document['machine_immat'] : '';
                $count_machines = count($machine_selector) - 1;
                $select_class = ($count_machines > 10) ? 'form-select big_select' : 'form-select';
                echo form_dropdown('machine_immat', $machine_selector, $selected_machine, 'class="' . $select_class . '" id="machine_immat"');
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Optional file replacement -->
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_file') ?>
            </label>
            <div class="col-sm-10">
                <div class="mb-2 text-muted small">
                    <i class="fas fa-file"></i> <?= htmlspecialchars($document['original_filename']) ?>
                </div>
                <div class="drop-zone" id="drop-zone-userfile">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-1"><?= $this->lang->line('gvv_drop_file_here') ?></p>
                    <p class="text-muted small"><?= $this->lang->line('gvv_or') ?></p>
                    <label for="userfile" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-folder-open"></i> <?= $this->lang->line('gvv_choose_file') ?>
                    </label>
                    <input type="file" name="userfile" id="userfile" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.odt,.ods,.odp,.ppt,.pptx,.html,.htm">
                    <p class="mt-2 small text-muted" id="filename-userfile"><?= $this->lang->line('gvv_no_file_selected') ?></p>
                </div>
                <small class="text-muted"><?= $this->lang->line('archived_documents_file_formats') ?> — <?= $this->lang->line('archived_documents_file') ?> optionnel — laissez vide pour conserver le fichier actuel.</small>
            </div>
        </div>

    </div>
    <div class="card-footer">
        <button type="submit" name="button" value="<?= $this->lang->line('gvv_button_submitbutton') ?>" class="btn btn-primary">
            <i class="fas fa-save"></i> <?= $this->lang->line('gvv_button_submitbutton') ?>
        </button>
        <button type="submit" name="button" value="<?= $this->lang->line('gvv_button_cancel') ?>" class="btn btn-secondary">
            <i class="fas fa-times"></i> <?= $this->lang->line('gvv_button_cancel') ?>
        </button>
    </div>
</div>

<?= form_close() ?>

</div>

<style>
.drop-zone {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background-color 0.2s;
    background: #fafafa;
}
.drop-zone.drag-over {
    border-color: #0d6efd;
    background-color: #e8f0fe;
}
.drop-zone.has-file {
    border-color: #198754;
    background-color: #f0fff4;
}
</style>

<script>
function initDropZone(inputId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var zone = input.closest('.drop-zone');
    var label = document.getElementById('filename-' + inputId);

    function updateFilename(files) {
        if (files && files.length > 0) {
            label.textContent = files[0].name;
            zone.classList.add('has-file');
        }
    }

    zone.addEventListener('click', function (e) {
        if (e.target.tagName !== 'LABEL' && e.target.tagName !== 'INPUT') {
            input.click();
        }
    });

    input.addEventListener('change', function () {
        updateFilename(this.files);
    });

    zone.addEventListener('dragover', function (e) {
        e.preventDefault();
        zone.classList.add('drag-over');
    });

    zone.addEventListener('dragleave', function () {
        zone.classList.remove('drag-over');
    });

    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('drag-over');
        var dt = e.dataTransfer;
        if (dt.files.length > 0) {
            input.files = dt.files;
            updateFilename(dt.files);
        }
    });
}

initDropZone('userfile');
</script>
