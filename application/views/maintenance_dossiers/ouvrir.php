<?php
/**
 * Vue : ouverture d'un dossier d'entretien
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$entite_label = ($entite_type === 'aeronef')
    ? $this->lang->line('maintenance_dossier_entite_aeronef')
    : $this->lang->line('maintenance_dossier_entite_equipement');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-folder-plus" aria-hidden="true"></i>
            <?= $entite_type === 'aeronef' ? $this->lang->line('maintenance_dossier_ouvrir_aeronef') : $this->lang->line('maintenance_dossier_ouvrir_equipement') ?>
        </h3>
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_retour') ?>
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?= form_open(controller_url($controller) . '/ouvrir_store', array('class' => 'card')) ?>
    <div class="card-body">

        <input type="hidden" name="entite_type" value="<?= htmlspecialchars($entite_type) ?>">

        <div class="mb-3 row">
            <label for="entite_id" class="col-sm-3 col-form-label">
                <?= $entite_label ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <?= form_dropdown('entite_id', $entite_selector, '', 'class="form-select" id="entite_id" required') ?>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="programme_id" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_dossier_programme') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <?= form_dropdown('programme_id', $programme_selector, '', 'class="form-select" id="programme_id" required') ?>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="commentaire" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_equipement_description') ?>
            </label>
            <div class="col-sm-6">
                <textarea class="form-control" id="commentaire" name="commentaire" rows="3" maxlength="255"></textarea>
            </div>
        </div>

    </div>
    <div class="card-footer text-end">
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary me-2">
            <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_annuler') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-folder-plus" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_dossier_ouvrir_btn') ?>
        </button>
    </div>
    <?= form_close() ?>

</div>
<?php $this->load->view('bs_footer'); ?>
