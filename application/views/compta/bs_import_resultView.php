<!-- VIEW: application/views/compta/bs_import_resultView.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

<h3><?= $this->lang->line('gvv_import_result_title') ?></h3>

<?php if (!empty($insert_errors)): ?>
    <div class="alert alert-danger">
        <strong><?= $this->lang->line('gvv_import_errors_title') ?> :</strong>
        <ul class="mb-1 mt-1">
            <?php foreach ($insert_errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
        <?= $this->lang->line('gvv_import_rollback') ?>
    </div>
<?php elseif ($count > 0): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-1"></i>
        <?= $count ?> <?= $this->lang->line('gvv_import_success') ?>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        <?= $this->lang->line('gvv_import_nothing_selected') ?>
    </div>
<?php endif; ?>

<a href="<?= controller_url('compta/import_ecritures') ?>" class="btn btn-secondary">
    <i class="fas fa-arrow-left me-1"></i><?= $this->lang->line('gvv_import_title') ?>
</a>

</div>
