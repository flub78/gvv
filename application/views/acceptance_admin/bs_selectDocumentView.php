<!-- VIEW: application/views/acceptance_admin/bs_selectDocumentView.php -->
<?php
/**
 * First step of acceptance item creation (Lot 4 amendment): pick the
 * archived document the new item will reference. Choosing a document
 * navigates to acceptance_admin/create/<id>, which reuses the existing
 * pre-filled creation form unchanged.
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('acceptance');
$this->lang->load('archived_documents');

$archived_document_selector = isset($archived_document_selector) ? $archived_document_selector : array();
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-plus"></i> <?= $this->lang->line('acceptance_add_item') ?></h3>

<?php if (isset($message)): ?>
    <?= $message ?>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="mb-3 row">
            <label for="archived_document_id_select" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_archived_document') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-10">
                <?= form_dropdown('archived_document_id_select', $archived_document_selector, '', 'class="form-select big_select" id="archived_document_id_select" required') ?>
                <small class="text-muted"><?= $this->lang->line('acceptance_select_document_help') ?></small>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="button" class="btn btn-primary" id="chooseDocumentBtn" disabled>
            <i class="fas fa-arrow-right"></i> <?= $this->lang->line('acceptance_choose_document') ?>
        </button>
        <a href="<?= site_url('acceptance_admin/page') ?>" class="btn btn-secondary">
            <i class="fas fa-times"></i> <?= $this->lang->line('gvv_button_cancel') ?>
        </a>
    </div>
</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('archived_document_id_select');
    var btn = document.getElementById('chooseDocumentBtn');

    function syncButton() {
        btn.disabled = !select.value;
    }

    // select2 (big_select) fires a native 'change' event on the underlying
    // <select> too, so a plain listener is enough (no jQuery dependency here).
    select.addEventListener('change', syncButton);
    syncButton();

    btn.addEventListener('click', function () {
        if (select.value) {
            window.location.href = '<?= site_url('acceptance_admin/create') ?>/' + select.value;
        }
    });
});
</script>
