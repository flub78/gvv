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
    <?php if (!empty($previous_version_id)): ?>
        <i class="fas fa-code-branch"></i> <?= $this->lang->line('archived_documents_new_version_title') ?>
    <?php elseif ($action == CREATION): ?>
        <i class="fas fa-plus"></i> <?= $this->lang->line('archived_documents_add_pilot') ?>
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

<!-- Choice panel: shown via AJAX when an existing document of the same type is found -->
<div id="existing-doc-panel" class="alert alert-warning d-none">
    <p class="mb-2"><i class="fas fa-exclamation-triangle"></i> <strong><?= $this->lang->line('archived_documents_existing_found') ?></strong></p>
    <div id="existing-docs-info" class="mb-3 ps-2"></div>
    <div class="form-check">
        <input class="form-check-input" type="radio" name="action_on_existing" id="action_add_new" value="add_new" checked>
        <label class="form-check-label" for="action_add_new">
            <i class="fas fa-file-plus"></i> <?= $this->lang->line('archived_documents_action_add_new') ?>
        </label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="radio" name="action_on_existing" id="action_replace" value="replace">
        <label class="form-check-label text-danger" for="action_replace">
            <i class="fas fa-exchange-alt"></i> <?= $this->lang->line('archived_documents_action_replace') ?>
        </label>
    </div>
    <!-- Populated by JS when multiple current docs exist -->
    <div id="replace-selector" class="mt-2 ps-4 d-none"></div>
    <input type="hidden" name="existing_doc_id_for_replace" id="existing_doc_id_for_replace" value="">
</div>

<?php if (isset($message)): ?>
    <?= $message ?>
<?php endif; ?>

<?= form_open_multipart('archived_documents/formValidation/' . $action, array('class' => 'form-horizontal')) ?>

<div class="card">
    <div class="card-body">

        <!-- Document type -->
        <div class="mb-3 row">
            <label for="document_type_id" class="col-sm-2 col-form-label">
                <?= $this->lang->line('archived_documents_type') ?>
            </label>
            <div class="col-sm-10">
                <?php
                $selected_type = isset($_GET['type']) ? $_GET['type'] : (isset($document_type_id) ? $document_type_id : '');
                echo form_dropdown('document_type_id', $type_selector, $selected_type, 'class="form-select big_select" id="document_type_id"');
                ?>
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

        <?= form_hidden('pilot_login', $pilot_login) ?>
        <?= form_hidden('uploaded_by', $uploaded_by) ?>
        <?= form_hidden('source', 'pilot') ?>
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
    var checkUrl    = '<?= site_url('archived_documents/check_existing_document') ?>';
    var labelUploadedOn  = <?= json_encode($this->lang->line('archived_documents_existing_uploaded_on')) ?>;
    var labelValidUntil  = <?= json_encode($this->lang->line('archived_documents_existing_valid_until')) ?>;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function docLabel(doc) {
        var parts = [];
        if (doc.description) parts.push(doc.description);
        parts.push(doc.filename);
        if (doc.uploaded_at) parts.push(labelUploadedOn + ' ' + doc.uploaded_at);
        if (doc.valid_until)  parts.push(labelValidUntil  + ' ' + doc.valid_until);
        return parts.join(' — ');
    }

    function hidePanel() {
        var p = document.getElementById('existing-doc-panel');
        if (p) p.classList.add('d-none');
        document.getElementById('existing_doc_id_for_replace').value = '';
        var addNew = document.getElementById('action_add_new');
        if (addNew) addNew.checked = true;
    }

    function showPanel(docs) {
        var infoEl = document.getElementById('existing-docs-info');
        var selEl  = document.getElementById('replace-selector');
        var html   = '';
        docs.forEach(function (doc) {
            html += '<div><i class="fas fa-file-alt me-1"></i>' + escHtml(docLabel(doc)) + '</div>';
        });
        infoEl.innerHTML = html;

        // If multiple current docs (data inconsistency), show a selector for which to replace
        if (docs.length > 1) {
            var selHtml = '<label class="form-label small"><?= $this->lang->line('archived_documents_type') ?> :</label><select class="form-select form-select-sm" id="replace-doc-select">';
            docs.forEach(function (doc) {
                selHtml += '<option value="' + doc.id + '">' + escHtml(docLabel(doc)) + '</option>';
            });
            selHtml += '</select>';
            selEl.innerHTML = selHtml;
            selEl.classList.remove('d-none');
            document.getElementById('replace-doc-select').addEventListener('change', function () {
                document.getElementById('existing_doc_id_for_replace').value = this.value;
            });
        } else {
            selEl.innerHTML = '';
            selEl.classList.add('d-none');
        }

        // Default: first doc selected for replace
        document.getElementById('existing_doc_id_for_replace').value = docs[0].id;
        document.getElementById('existing-doc-panel').classList.remove('d-none');
        // Reset to safe default
        document.getElementById('action_add_new').checked = true;
    }

    function checkExisting(typeId) {
        if (!typeId) { hidePanel(); return; }
        fetch(checkUrl + '?type_id=' + encodeURIComponent(typeId))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.exists) { showPanel(data.docs); } else { hidePanel(); }
            })
            .catch(function () { hidePanel(); });
    }

    // Select2 triggers change via jQuery — use jQuery event delegation.
    $(document).on('change', '#document_type_id', function () { checkExisting($(this).val()); });
    // Initial check after Select2 has initialised (runs after footer's $(document).ready).
    $(document).ready(function () {
        var val = $('#document_type_id').val();
        if (val) { checkExisting(val); }
    });
}());
</script>
