<?php
// VIEW: application/views/formation_seances_theoriques/attachments_section.php
// Section documents d'une séance théorique — AJAX, pas de rechargement de page.
// Variables attendues : $attachments (array), $seance_id (int), $can_edit (bool)
?>

<div id="formationAttachments">

<?php if (empty($attachments)): ?>
    <div class="alert alert-info" id="noAttachmentsMsg">
        <i class="fas fa-info-circle"></i> <?= $this->lang->line('formation_attachment_none') ?>
    </div>
<?php else: ?>
    <table class="table table-striped table-sm" id="attachmentsTable">
        <thead>
            <tr>
                <th style="width:40%;"><?= $this->lang->line('formation_attachment_description') ?></th>
                <th style="width:35%;"><?= $this->lang->line('formation_attachment_file') ?></th>
                <th style="width:25%;"><?= $this->lang->line('formation_attachment_actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($attachments as $att): ?>
            <tr id="attachment-row-<?= $att['id'] ?>" data-attachment-id="<?= $att['id'] ?>">
                <td>
                    <div class="view-mode">
                        <span class="description-text"><?= htmlspecialchars($att['description']) ?></span>
                    </div>
                    <div class="edit-mode" style="display:none;">
                        <input type="text" class="form-control form-control-sm description-input"
                               value="<?= htmlspecialchars($att['description']) ?>">
                        <div class="text-danger mt-1 error-message" style="display:none;"></div>
                    </div>
                </td>
                <td>
                    <div class="view-mode">
                        <a href="<?= $att['file_url'] ?>" target="_blank">
                            <?= htmlspecialchars(basename($att['file'])) ?>
                        </a>
                    </div>
                    <div class="edit-mode" style="display:none;">
                        <small class="text-muted"><?= $this->lang->line('formation_attachment_current') ?>:
                            <?= htmlspecialchars(basename($att['file'])) ?>
                        </small>
                    </div>
                </td>
                <td>
                    <?php if ($can_edit): ?>
                    <div class="view-mode">
                        <button class="btn btn-sm btn-primary edit-attachment-btn" title="<?= $this->lang->line('formation_attachment_edit') ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-attachment-btn" title="<?= $this->lang->line('formation_attachment_delete') ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="edit-mode" style="display:none;">
                        <button class="btn btn-sm btn-success save-attachment-btn" title="<?= $this->lang->line('formation_attachment_save') ?>">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-secondary cancel-edit-btn" title="<?= $this->lang->line('formation_attachment_cancel') ?>">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="view-mode">
                        <a href="<?= $att['file_url'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if ($can_edit): ?>
    <button class="btn btn-sm btn-outline-primary mb-2" id="showUploadForm">
        <i class="fas fa-plus"></i> <?= $this->lang->line('formation_attachment_add') ?>
    </button>

    <div id="uploadFormCard" class="card card-body mb-3" style="display:none;">
        <div class="mb-2">
            <label for="newAttachmentDescription" class="form-label">
                <?= $this->lang->line('formation_attachment_description') ?>
            </label>
            <input type="text" class="form-control form-control-sm" id="newAttachmentDescription"
                   placeholder="<?= $this->lang->line('formation_attachment_description_placeholder') ?>">
        </div>
        <div class="mb-2">
            <label for="newAttachmentFile" class="form-label">
                <?= $this->lang->line('formation_attachment_file') ?> <span class="text-danger">*</span>
            </label>
            <input type="file" class="form-control form-control-sm" id="newAttachmentFile">
        </div>
        <div class="text-danger mb-2" id="uploadErrorMessage" style="display:none;"></div>
        <div>
            <button class="btn btn-sm btn-success" id="saveNewAttachment">
                <i class="fas fa-save"></i> <?= $this->lang->line('formation_attachment_save') ?>
            </button>
            <button class="btn btn-sm btn-secondary ms-1" id="cancelUpload">
                <i class="fas fa-times"></i> <?= $this->lang->line('formation_attachment_cancel') ?>
            </button>
        </div>
    </div>
<?php endif; ?>

</div><!-- #formationAttachments -->

<script>
(function() {
    var seanceId = <?= (int)$seance_id ?>;
    var uploadUrl  = '<?= site_url('formation_seances_theoriques/ajax_upload_attachment') ?>/' + seanceId;
    var updateBase = '<?= site_url('formation_seances_theoriques/ajax_update_attachment') ?>/';
    var deleteBase = '<?= site_url('formation_seances_theoriques/ajax_delete_attachment') ?>/';

    var _t = <?= json_encode([
        'none'             => $this->lang->line('formation_attachment_none'),
        'description'      => $this->lang->line('formation_attachment_description'),
        'file'             => $this->lang->line('formation_attachment_file'),
        'actions'          => $this->lang->line('formation_attachment_actions'),
        'save'             => $this->lang->line('formation_attachment_save'),
        'file_required'    => $this->lang->line('formation_attachment_file_required'),
        'confirm_delete'   => $this->lang->line('formation_attachment_confirm_delete'),
        'created'          => $this->lang->line('formation_attachment_created'),
        'updated'          => $this->lang->line('formation_attachment_updated'),
        'deleted'          => $this->lang->line('formation_attachment_deleted'),
        'error_upload'     => $this->lang->line('formation_attachment_error_upload'),
        'error_update'     => $this->lang->line('formation_attachment_error_update'),
        'error_delete'     => $this->lang->line('formation_attachment_error_delete'),
    ]) ?>;

    // ── Upload form toggle ───────────────────────────────────────────────────
    $(document).off('click', '#showUploadForm').on('click', '#showUploadForm', function() {
        $('#uploadFormCard').slideDown();
        $(this).hide();
    });

    $(document).off('click', '#cancelUpload').on('click', '#cancelUpload', function() {
        $('#uploadFormCard').slideUp();
        $('#showUploadForm').show();
        $('#newAttachmentDescription').val('');
        $('#newAttachmentFile').val('');
        $('#uploadErrorMessage').hide().text('');
    });

    // ── Save new attachment ──────────────────────────────────────────────────
    $(document).off('click', '#saveNewAttachment').on('click', '#saveNewAttachment', function() {
        var $btn = $(this);
        var description = $('#newAttachmentDescription').val();
        var fileInput   = $('#newAttachmentFile')[0];

        $('#uploadErrorMessage').hide().text('');

        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            $('#uploadErrorMessage').show().text(_t.file_required);
            return;
        }

        var formData = new FormData();
        formData.append('description', description);
        formData.append('file', fileInput.files[0]);

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url:         uploadUrl,
            type:        'POST',
            data:        formData,
            processData: false,
            contentType: false,
            dataType:    'json',
            success: function(response) {
                if (response.success) {
                    _addRowToTable(response);
                    $('#newAttachmentDescription').val('');
                    $('#newAttachmentFile').val('');
                    $('#uploadFormCard').slideUp();
                    $('#showUploadForm').show();
                    _showSuccessToast(_t.created);
                } else {
                    $('#uploadErrorMessage').show().text(response.error || _t.error_upload);
                }
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> ' + _t.save);
            },
            error: function(xhr) {
                var msg = _t.error_upload;
                try { msg = JSON.parse(xhr.responseText).error || msg; } catch(e) {}
                $('#uploadErrorMessage').show().text(msg);
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> ' + _t.save);
            }
        });
    });

    // ── Edit button ──────────────────────────────────────────────────────────
    $(document).off('click', '.edit-attachment-btn').on('click', '.edit-attachment-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.view-mode').hide();
        $row.find('.edit-mode').show();
    });

    // ── Cancel edit ─────────────────────────────────────────────────────────
    $(document).off('click', '.cancel-edit-btn').on('click', '.cancel-edit-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.edit-mode').hide();
        $row.find('.view-mode').show();
        $row.find('.error-message').hide().text('');
    });

    // ── Save edit ────────────────────────────────────────────────────────────
    $(document).off('click', '.save-attachment-btn').on('click', '.save-attachment-btn', function() {
        var $btn  = $(this);
        var $row  = $btn.closest('tr');
        var attId = $row.data('attachment-id');
        var description = $row.find('.description-input').val();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url:      updateBase + attId,
            type:     'POST',
            data:     { description: description },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $row.find('.description-text').text(response.description);
                    $row.find('.edit-mode').hide();
                    $row.find('.view-mode').show();
                    $row.find('.error-message').hide().text('');
                    _showSuccessToast(_t.updated);
                } else {
                    $row.find('.error-message').show().text(response.error || _t.error_update);
                }
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            },
            error: function() {
                $row.find('.error-message').show().text(_t.error_update);
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            }
        });
    });

    // ── Delete ───────────────────────────────────────────────────────────────
    $(document).off('click', '.delete-attachment-btn').on('click', '.delete-attachment-btn', function() {
        if (!confirm(_t.confirm_delete)) {
            return;
        }

        var $btn  = $(this);
        var $row  = $btn.closest('tr');
        var attId = $row.data('attachment-id');

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url:      deleteBase + attId,
            type:     'POST',
            data:     {},
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        if ($('#attachmentsTable tbody tr').length === 0) {
                            $('#attachmentsTable').replaceWith(
                                '<div class="alert alert-info" id="noAttachmentsMsg">' +
                                '<i class="fas fa-info-circle"></i> ' + _t.none +
                                '</div>'
                            );
                        }
                    });
                    _showSuccessToast(_t.deleted);
                } else {
                    $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                    alert(response.error || _t.error_delete);
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                alert(_t.error_delete);
            }
        });
    });

    // ── Helpers ──────────────────────────────────────────────────────────────
    function _addRowToTable(response) {
        var canEdit = <?= $can_edit ? 'true' : 'false' ?>;
        var row = '<tr id="attachment-row-' + response.attachment_id + '" data-attachment-id="' + response.attachment_id + '">';
        row += '<td>';
        row += '<div class="view-mode"><span class="description-text">' + _esc(response.description) + '</span></div>';
        if (canEdit) {
            row += '<div class="edit-mode" style="display:none;">';
            row += '<input type="text" class="form-control form-control-sm description-input" value="' + _esc(response.description) + '">';
            row += '<div class="text-danger mt-1 error-message" style="display:none;"></div>';
            row += '</div>';
        }
        row += '</td>';
        row += '<td>';
        row += '<div class="view-mode"><a href="' + response.file_url + '" target="_blank">' + _esc(response.file_name) + '</a></div>';
        row += '</td>';
        row += '<td>';
        if (canEdit) {
            row += '<div class="view-mode">';
            row += '<button class="btn btn-sm btn-primary edit-attachment-btn"><i class="fas fa-edit"></i></button> ';
            row += '<button class="btn btn-sm btn-danger delete-attachment-btn"><i class="fas fa-trash"></i></button>';
            row += '</div>';
            row += '<div class="edit-mode" style="display:none;">';
            row += '<button class="btn btn-sm btn-success save-attachment-btn"><i class="fas fa-check"></i></button> ';
            row += '<button class="btn btn-sm btn-secondary cancel-edit-btn"><i class="fas fa-times"></i></button>';
            row += '</div>';
        }
        row += '</td></tr>';

        if ($('#attachmentsTable').length === 0) {
            var tableHtml = '<table class="table table-striped table-sm" id="attachmentsTable">' +
                '<thead><tr>' +
                '<th style="width:40%;">' + _t.description + '</th>' +
                '<th style="width:35%;">' + _t.file + '</th>' +
                '<th style="width:25%;">' + _t.actions + '</th>' +
                '</tr></thead><tbody>' + row + '</tbody></table>';
            $('#noAttachmentsMsg').replaceWith(tableHtml);
        } else {
            $('#attachmentsTable tbody').append(row);
        }
    }

    function _esc(str) {
        return $('<span>').text(str || '').html();
    }

    function _showSuccessToast(message) {
        var $toast = $(
            '<div class="position-fixed bottom-0 end-0 p-3" style="z-index:11">' +
            '<div class="toast show" role="alert">' +
            '<div class="toast-header bg-success text-white">' +
            '<strong class="me-auto">Succès</strong>' +
            '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>' +
            '</div><div class="toast-body">' + message + '</div></div></div>'
        ).appendTo('body');
        setTimeout(function() { $toast.remove(); }, 3000);
    }
})();
</script>
