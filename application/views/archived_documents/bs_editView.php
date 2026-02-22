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

        <!-- Optional file replacement -->
        <div class="mb-3 row">
            <label for="userfile" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_file') ?>
            </label>
            <div class="col-sm-10">
                <div class="mb-1 text-muted small">
                    <i class="fas fa-file"></i> <?= htmlspecialchars($document['original_filename']) ?>
                </div>
                <input type="file" name="userfile" id="userfile" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.odt,.ods,.odp,.ppt,.pptx,.html,.htm">
                <small class="text-muted"><?= $this->lang->line('archived_documents_file_formats') ?> <?= $this->lang->line('archived_documents_file') ?> optionnel — laissez vide pour conserver le fichier actuel.</small>
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
