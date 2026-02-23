<!-- VIEW: application/views/archived_documents/bs_formView.php -->
<?php
/**
 * Form view for adding a document (admin view with pilot/section selectors)
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');
?>

<div id="body" class="body container-fluid">

<h3>
    <?php if (!empty($previous_version_id)): ?>
        <i class="fas fa-code-branch"></i> <?= $this->lang->line('archived_documents_new_version_title') ?>
    <?php elseif ($action == CREATION): ?>
        <i class="fas fa-plus"></i> <?= $this->lang->line('archived_documents_add') ?>
    <?php else: ?>
        <i class="fas fa-edit"></i> <?= $this->lang->line('archived_documents_view') ?>
    <?php endif; ?>
</h3>

<?php if (!empty($previous_version_id) && !empty($new_version_of)): ?>
<div class="alert alert-info">
    <i class="fas fa-code-branch"></i>
    <?= $this->lang->line('archived_documents_new_version_of') ?> :
    <strong><?= htmlspecialchars($new_version_of['original_filename']) ?></strong>
    <?php if (!empty($new_version_of['valid_until'])): ?>
        — <?= $this->lang->line('archived_documents_valid_until') ?> <?= date('d/m/Y', strtotime($new_version_of['valid_until'])) ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="mb-3">
    <a href="<?= site_url('document_types/page') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-tags"></i> <?= $this->lang->line('archived_documents_manage_types') ?>
    </a>
</div>

<?php if (isset($message)): ?>
    <?= $message ?>
<?php endif; ?>

<?= form_open_multipart('archived_documents/formValidation/' . $action, array('class' => 'form-horizontal', 'id' => 'document-upload-form')) ?>

<div class="card">
    <div class="card-body">

        <!-- Document type -->
        <div class="mb-3 row">
            <label for="document_type_id" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_type') ?>
            </label>
            <div class="col-sm-10">
                <div class="d-flex align-items-center gap-2">
                <?php
                $selected_type = isset($_GET['type']) ? $_GET['type'] : (isset($document_type_id) ? $document_type_id : '');
                echo form_dropdown('document_type_id', $type_selector, $selected_type, 'class="form-select" id="document_type_id"');
                ?>
                <button type="button" class="btn btn-outline-secondary btn-sm" title="<?= $this->lang->line('archived_documents_type_help') ?>" aria-label="<?= $this->lang->line('archived_documents_type_help') ?>" data-bs-toggle="tooltip" data-bs-placement="top">
                    <i class="fas fa-question"></i>
                </button>
                </div>
            </div>
        </div>

        <!-- Pilot selector -->
        <div class="mb-3 row">
            <label for="pilot_login" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_pilot') ?>
            </label>
            <div class="col-sm-10">
                <?php
                $selected_pilot = isset($pilot_login) ? $pilot_login : '';
                echo form_dropdown('pilot_login', $pilot_selector, $selected_pilot, 'id="pilot_login" class="form-select big_select"');
                ?>
            </div>
        </div>

        <!-- Section selector -->
        <div class="mb-3 row">
            <label for="section_id" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_section') ?>
            </label>
            <div class="col-sm-10">
                <?= form_dropdown('section_id', $section_selector, isset($section_id) ? $section_id : '', 'class="form-select" id="section_id"') ?>
            </div>
        </div>

        <!-- File upload -->
        <div class="mb-3 row">
            <label for="userfile" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_file') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-10">
                <input type="file" name="userfile" id="userfile" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.odt,.ods,.odp,.ppt,.pptx,.html,.htm">
                <small class="text-muted"><?= $this->lang->line('archived_documents_file_formats') ?></small>
            </div>
        </div>

        <!-- Description -->
        <div class="mb-3 row">
            <label for="description" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_description') ?>
            </label>
            <div class="col-sm-10">
                <?= form_input('description', set_value('description', isset($description) ? $description : ''), 'class="form-control" id="description" maxlength="255"') ?>
            </div>
        </div>

        <!-- Valid from -->
        <div class="mb-3 row">
            <label for="valid_from" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_valid_from') ?>
            </label>
            <div class="col-sm-10">
                <?= form_input('valid_from', set_value('valid_from', isset($valid_from) ? $valid_from : ''), 'class="form-control datepicker" id="valid_from" placeholder="jj/mm/aaaa"') ?>
            </div>
        </div>

        <!-- Valid until -->
        <div class="mb-3 row">
            <label for="valid_until" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_valid_until') ?>
            </label>
            <div class="col-sm-10">
                <?= form_input('valid_until', set_value('valid_until', isset($valid_until) ? $valid_until : ''), 'class="form-control datepicker" id="valid_until" placeholder="jj/mm/aaaa"') ?>
            </div>
        </div>

        <?= form_hidden('uploaded_by', $uploaded_by) ?>
        <?php if (!empty($previous_version_id)): ?>
        <?= form_hidden('previous_version_id', $previous_version_id) ?>
        <?php endif; ?>

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

<script>
(function () {
    function isValidDate(str) {
        if (!str) return true;
        var m = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (!m) return false;
        var d = parseInt(m[1], 10), mo = parseInt(m[2], 10), y = parseInt(m[3], 10);
        if (mo < 1 || mo > 12) return false;
        var dim = new Date(y, mo, 0).getDate();
        return d >= 1 && d <= dim;
    }

    document.getElementById('document-upload-form').addEventListener('submit', function (e) {
        var fields = [
            { id: 'valid_from',  label: <?= json_encode($this->lang->line('archived_documents_valid_from')) ?> },
            { id: 'valid_until', label: <?= json_encode($this->lang->line('archived_documents_valid_until')) ?> }
        ];
        var errors = [];
        fields.forEach(function (f) {
            var el = document.getElementById(f.id);
            if (el && !isValidDate(el.value.trim())) {
                errors.push(f.label + ' : ' + el.value.trim());
                el.classList.add('is-invalid');
            } else if (el) {
                el.classList.remove('is-invalid');
            }
        });
        if (errors.length) {
            e.preventDefault();
            var msg = document.getElementById('date-error-msg');
            if (!msg) {
                msg = document.createElement('div');
                msg.id = 'date-error-msg';
                document.querySelector('form').prepend(msg);
            }
            msg.className = 'alert alert-danger';
            msg.textContent = 'Date invalide : ' + errors.join(', ');
        }
    });
}());
</script>
