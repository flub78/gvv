<?php
/**
 * Vue : depot d'un bulletin de service
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$form_url = controller_url($controller) . '/upload/' . $machine_immat;
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-bell" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_bulletins_deposer') ?>
            <small class="text-muted fs-6">— <?= htmlspecialchars($machine_immat) ?></small>
        </h3>
        <a href="<?= controller_url($controller) ?>/index/<?= htmlspecialchars($machine_immat) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_retour') ?>
        </a>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?= htmlspecialchars($this->session->flashdata('error')) ?>
        </div>
    <?php endif; ?>

    <?= form_open_multipart($form_url, array('class' => 'card')) ?>
    <div class="card-body">

        <div class="mb-3 row">
            <label for="bulletin_file" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_bulletin_fichier') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <input type="file" class="form-control" id="bulletin_file" name="bulletin_file" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx" required>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="description" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_equipement_description') ?>
            </label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="description" name="description" maxlength="255">
            </div>
        </div>

    </div>
    <div class="card-footer text-end">
        <a href="<?= controller_url($controller) ?>/index/<?= htmlspecialchars($machine_immat) ?>" class="btn btn-outline-secondary me-2">
            <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_annuler') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-upload" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_bulletins_deposer') ?>
        </button>
    </div>
    <?= form_close() ?>

</div>
<?php $this->load->view('bs_footer'); ?>
