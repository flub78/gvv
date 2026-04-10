<!-- VIEW: application/views/compta/bs_import_ecrituresView.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

<h3><?= $this->lang->line('gvv_import_title') ?></h3>

<?= checkalert($this->session) ?>

<?php if (!empty($upload_error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($upload_error) ?></div>
<?php endif; ?>

<form method="post" action="<?= controller_url('compta/import_ecritures') ?>" enctype="multipart/form-data">
    <input type="hidden" name="import_source" value="file">
    <div class="mb-3">
        <label for="userfile" class="form-label">
            <?= $this->lang->line('gvv_import_file_label') ?>
        </label>
        <input type="file" class="form-control" id="userfile" name="userfile" accept=".json" required>
    </div>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-search me-1"></i><?= $this->lang->line('gvv_import_submit') ?>
    </button>
</form>

<hr class="my-4">

<form method="post" action="<?= controller_url('compta/import_ecritures') ?>">
    <input type="hidden" name="import_source" value="text">
    <div class="mb-3">
        <label for="json_text" class="form-label"><?= $this->lang->line('gvv_import_text_label') ?></label>
        <textarea class="form-control" id="json_text" name="json_text" rows="14" style="font-family: monospace;"><?= htmlspecialchars($json_text ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-outline-primary">
        <i class="fas fa-paste me-1"></i><?= $this->lang->line('gvv_import_text_submit') ?>
    </button>
</form>

</div>
