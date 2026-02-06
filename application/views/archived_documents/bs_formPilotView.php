<!-- VIEW: application/views/archived_documents/bs_formPilotView.php -->
<?php
/**
 * Form view for adding a pilot document (simplified, no pilot/section selectors)
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');
?>

<div id="body" class="body container-fluid">

<h3>
    <?php if ($action == CREATION): ?>
        <i class="fas fa-plus"></i> <?= $this->lang->line('archived_documents_add_pilot') ?>
    <?php else: ?>
        <i class="fas fa-edit"></i> <?= $this->lang->line('archived_documents_view') ?>
    <?php endif; ?>
</h3>

<?php if (isset($message)): ?>
    <?= $message ?>
<?php endif; ?>

<?= form_open_multipart('archived_documents/formValidation/' . $action, array('class' => 'form-horizontal')) ?>

<div class="card">
    <div class="card-body">

        <!-- Document type -->
        <div class="mb-3 row">
            <label for="document_type_id" class="col-sm-3 col-form-label">
                <?= $this->lang->line('archived_documents_type') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-9">
                <?php
                $selected_type = isset($_GET['type']) ? $_GET['type'] : (isset($document_type_id) ? $document_type_id : '');
                echo form_dropdown('document_type_id', $type_selector, $selected_type, 'class="form-select" id="document_type_id" required');
                ?>
            </div>
        </div>

        <!-- File upload -->
        <div class="mb-3 row">
            <label for="userfile" class="col-sm-3 col-form-label">
                <?= $this->lang->line('archived_documents_file') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-9">
                <input type="file" name="userfile" id="userfile" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.odt,.ods,.odp,.ppt,.pptx,.html,.htm">
                <small class="text-muted"><?= $this->lang->line('archived_documents_file_formats') ?></small>
            </div>
        </div>

        <!-- Description -->
        <div class="mb-3 row">
            <label for="description" class="col-sm-3 col-form-label">
                <?= $this->lang->line('archived_documents_description') ?>
            </label>
            <div class="col-sm-9">
                <?= form_input('description', set_value('description', isset($description) ? $description : ''), 'class="form-control" id="description" maxlength="255"') ?>
            </div>
        </div>

        <!-- Valid from -->
        <div class="mb-3 row">
            <label for="valid_from" class="col-sm-3 col-form-label">
                <?= $this->lang->line('archived_documents_valid_from') ?>
            </label>
            <div class="col-sm-9">
                <?= form_input('valid_from', set_value('valid_from', isset($valid_from) ? $valid_from : ''), 'class="form-control datepicker" id="valid_from" placeholder="jj/mm/aaaa"') ?>
            </div>
        </div>

        <!-- Valid until -->
        <div class="mb-3 row">
            <label for="valid_until" class="col-sm-3 col-form-label">
                <?= $this->lang->line('archived_documents_valid_until') ?>
            </label>
            <div class="col-sm-9">
                <?= form_input('valid_until', set_value('valid_until', isset($valid_until) ? $valid_until : ''), 'class="form-control datepicker" id="valid_until" placeholder="jj/mm/aaaa"') ?>
            </div>
        </div>

        <?= form_hidden('pilot_login', $pilot_login) ?>
        <?= form_hidden('uploaded_by', $uploaded_by) ?>

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
