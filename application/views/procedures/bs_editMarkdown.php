<!-- VIEW: application/views/procedures/bs_editMarkdown.php -->
<?php
/**
 * Vue édition du contenu markdown d'une procédure
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('procedures');
?>

<div id="body" class="body ui-widget-content">
    <div class="container-fluid">

        <!-- Header -->
        <div class="row mb-3">
            <div class="col-md-8">
                <h2>
                    <i class="fab fa-markdown"></i>
                    Éditer le contenu - <?= htmlspecialchars($procedure['title']) ?>
                </h2>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= site_url("procedures/view/{$procedure['id']}") ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la procédure
                </a>
            </div>
        </div>

        <!-- Messages flash -->
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $this->session->flashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $this->session->flashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div id="save-feedback"></div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-edit"></i> Contenu markdown
                </h5>
                <button type="button" id="save-btn" class="btn btn-primary btn-sm">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
            <div class="card-body">
                <textarea id="markdown-content" class="form-control" rows="25"
                          style="font-family: monospace;"><?= htmlspecialchars($markdown_content) ?></textarea>
            </div>
        </div>

    </div>
</div>

<script>
var saveUrl = '<?= site_url("procedures/save_markdown") ?>';
var procedureId = <?= (int) $procedure['id'] ?>;

$(document).ready(function() {
    $('#save-btn').on('click', function() {
        var $btn = $(this);
        var $feedback = $('#save-feedback');

        $btn.prop('disabled', true);

        $.ajax({
            url: saveUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                id: procedureId,
                content: $('#markdown-content').val()
            },
            success: function(resp) {
                var alertClass = resp.success ? 'alert-success' : 'alert-danger';
                $feedback.html(
                    '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                    resp.message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>'
                );
            },
            error: function() {
                $feedback.html(
                    '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    'Erreur lors de la sauvegarde' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>'
                );
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
