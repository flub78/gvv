<!-- SHARED VIEW: application/views/compta/bs_attachments_modal.php -->
<!-- Attachments Modal - used by bs_journalCompteView and bs_journalView -->
<div class="modal fade" id="attachmentsModal" tabindex="-1" aria-labelledby="attachmentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attachmentsModalLabel">
                    <i class="fas fa-paperclip"></i> Justificatifs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="attachmentsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle click on attachment paperclip icon
$(document).on('click', '.attachment-icon', function() {
    var ecritureId = $(this).data('ecriture-id');
    var date = $(this).data('date');
    var description = $(this).data('description');
    var debit = $(this).data('debit');
    var credit = $(this).data('credit');
    var montant = $(this).data('montant');

    // Format date to locale
    var date_op = new Date(date);
    var formattedDate = date_op.toLocaleDateString();

    var amount = debit || credit || montant || '';
    var modalTitle = 'Justificatifs ' + formattedDate + ' : ' + description + (amount ? ' (' + amount + ' €)' : '');
    $('#attachmentsModalLabel').html('<i class="fas fa-paperclip"></i> ' + modalTitle);

    // Open modal
    var modal = new bootstrap.Modal(document.getElementById('attachmentsModal'));
    modal.show();

    // Load attachments content
    loadAttachments(ecritureId);
});

function loadAttachments(ecritureId) {
    $('#attachmentsContent').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

    // Store ecriture_id in modal for later use
    $('#attachmentsModal').data('ecriture-id', ecritureId);

    $.ajax({
        url: '<?= site_url('compta/get_attachments_section') ?>/' + ecritureId,
        method: 'GET',
        success: function(response) {
            $('#attachmentsContent').html(response);
            initializeAttachmentHandlers();
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            var errorMsg = '<div class="alert alert-danger">';
            errorMsg += '<strong>Erreur lors du chargement des justificatifs.</strong><br>';
            errorMsg += 'Status: ' + status + '<br>';
            if (xhr.responseText) {
                errorMsg += 'Détails: ' + xhr.responseText;
            }
            errorMsg += '</div>';
            $('#attachmentsContent').html(errorMsg);
        }
    });
}

// Initialize inline editing handlers
function initializeAttachmentHandlers() {
    // Edit button click
    $(document).off('click', '.edit-attachment-btn').on('click', '.edit-attachment-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.view-mode').hide();
        $row.find('.edit-mode').show();
    });

    // Cancel button click
    $(document).off('click', '.cancel-edit-btn').on('click', '.cancel-edit-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.edit-mode').hide();
        $row.find('.view-mode').show();
        $row.find('.error-message').hide().text('');
    });

    // Save button click
    $(document).off('click', '.save-attachment-btn').on('click', '.save-attachment-btn', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr');

        var attachmentId = $row.data('attachment-id');
        if (!attachmentId) {
            attachmentId = $row.attr('data-attachment-id');
        }
        if (!attachmentId) {
            attachmentId = $row[0].getAttribute('data-attachment-id');
        }

        var description = $row.find('.description-input').val();
        var fileInput = $row.find('.file-input')[0];

        if (!attachmentId) {
            $row.find('.error-message').show().text('Erreur: ID de justificatif manquant dans la page');
            return;
        }

        var formData = new FormData();
        formData.append('attachment_id', attachmentId);
        formData.append('description', description);
        if (fileInput && fileInput.files.length > 0) {
            formData.append('file', fileInput.files[0]);
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '<?= base_url() ?>index.php/compta/update_attachment',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $row.find('.view-mode a').attr('href', response.file_url).text(response.file_name);
                    $row.find('.description-text').text(response.description);
                    $row.find('.edit-mode').hide();
                    $row.find('.view-mode').show();
                    $row.find('.error-message').hide().text('');
                    showSuccessToast('Justificatif mis à jour avec succès');
                } else {
                    $row.find('.error-message').show().text(response.error || 'Erreur lors de la mise à jour');
                }
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            },
            error: function() {
                $row.find('.error-message').show().text('Erreur lors de la mise à jour');
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            }
        });
    });
}

// Delete button click
$(document).on('click', '.delete-attachment-btn', function() {
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
        data: { attachment_id: attachmentId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                    if ($('#attachmentsTable tbody tr').length === 0) {
                        $('#attachmentsTable').replaceWith('<div class="alert alert-info">Aucun justificatif</div>');
                    }
                });
                // Update paperclip icon count
                var ecritureId = $('#attachmentsModal').data('ecriture-id');
                var $icon = $('.attachment-icon[data-ecriture-id="' + ecritureId + '"]');
                var currentCount = parseInt($icon.data('attachment-count')) || 0;
                var newCount = Math.max(0, currentCount - 1);
                $icon.data('attachment-count', newCount);
                $icon.attr('data-attachment-count', newCount);
                if (newCount === 0) {
                    $icon.attr('title', 'Aucun justificatif').removeClass('text-success fw-bold').addClass('text-muted');
                } else {
                    $icon.attr('title', newCount + ' justificatif(s)');
                }
                showSuccessToast('Justificatif supprimé avec succès');
            } else {
                $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                alert(response.error || 'Erreur lors de la suppression');
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
            alert('Erreur lors de la suppression');
        }
    });
});

// Handle create attachment inline form
$(document).on('click', '#showCreateForm', function() {
    $('#createAttachmentCard').slideDown();
    $(this).hide();
});

$(document).on('click', '#cancelCreate', function() {
    $('#createAttachmentCard').slideUp();
    $('#showCreateForm').show();
    $('#newDescription').val('');
    $('#newFile').val('');
    $('#createErrorMessage').hide().text('');
});

$(document).on('click', '#saveNewAttachment', function() {
    var $btn = $(this);
    var description = $('#newDescription').val();
    var fileInput = $('#newFile')[0];
    var ecritureId = $('#attachmentsModal').data('ecriture-id');

    $('#createErrorMessage').hide().text('');

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        $('#createErrorMessage').show().text('Veuillez sélectionner un fichier');
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
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
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

                if ($('#attachmentsTable').length === 0) {
                    var tableHtml = '<table class="table table-striped table-sm" id="attachmentsTable">';
                    tableHtml += '<thead><tr><th style="width: 40%;">Description</th><th style="width: 35%;">Fichier</th><th style="width: 25%;">Actions</th></tr></thead>';
                    tableHtml += '<tbody>' + newRow + '</tbody></table>';
                    $('#showCreateForm').before(tableHtml);
                } else {
                    $('#attachmentsTable tbody').append(newRow);
                }

                $('#newDescription').val('');
                $('#newFile').val('');
                $('#createAttachmentCard').slideUp();
                $('#showCreateForm').show();

                showSuccessToast('Justificatif créé avec succès');

                // Update paperclip icon count
                var $icon = $('.attachment-icon[data-ecriture-id="' + ecritureId + '"]');
                var currentCount = parseInt($icon.data('attachment-count')) || 0;
                var newCount = currentCount + 1;
                $icon.data('attachment-count', newCount);
                $icon.attr('data-attachment-count', newCount);
                $icon.attr('title', newCount + ' justificatif(s)');
                $icon.removeClass('text-muted').addClass('text-success fw-bold');
            } else {
                $('#createErrorMessage').show().text(response.error || 'Erreur lors de la création');
            }
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
        },
        error: function(xhr) {
            var errorMsg = 'Erreur lors de la création';
            try {
                var response = JSON.parse(xhr.responseText);
                errorMsg = response.error || errorMsg;
            } catch(e) {}
            $('#createErrorMessage').show().text(errorMsg);
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
        }
    });
});

function showSuccessToast(message) {
    var toastHtml = '<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">' +
        '<div class="toast show" role="alert">' +
        '<div class="toast-header bg-success text-white">' +
        '<strong class="me-auto">Succès</strong>' +
        '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>' +
        '</div>' +
        '<div class="toast-body">' + message + '</div>' +
        '</div></div>';
    var $toast = $(toastHtml).appendTo('body');
    setTimeout(function() { $toast.remove(); }, 3000);
}
</script>
