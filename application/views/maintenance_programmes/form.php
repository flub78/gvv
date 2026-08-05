<?php
/**
 * Vue : formulaire creation/edition des metadonnees d'un programme d'entretien
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$is_edit  = ($action === 'edit');
$form_url = $is_edit
    ? controller_url($controller) . '/update/' . $programme['id']
    : controller_url($controller) . '/store';
$title    = $is_edit
    ? $this->lang->line('maintenance_programmes_edit')
    : $this->lang->line('maintenance_programmes_create');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-clipboard-list" aria-hidden="true"></i>
            <?= $title ?>
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

    <?= form_open($form_url, array('class' => 'card')) ?>
    <div class="card-body">

        <div class="mb-3 row">
            <label for="code" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_programme_code') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-4">
                <input type="text" class="form-control" id="code" name="code"
                       value="<?= htmlspecialchars($programme['code'] ?? '') ?>"
                       maxlength="50" required>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="titre" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_programme_titre') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="titre" name="titre"
                       value="<?= htmlspecialchars($programme['titre'] ?? '') ?>"
                       maxlength="255" required>
                <div class="form-text text-muted"><?= $this->lang->line('maintenance_programme_titre_help') ?></div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="section_id" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_programme_section') ?>
            </label>
            <div class="col-sm-6">
                <?= form_dropdown('section_id', $section_selector, $programme['section_id'] ?? '', 'class="form-select" id="section_id"') ?>
            </div>
        </div>

        <hr>
        <h6 class="text-muted"><?= $this->lang->line('maintenance_programme_regle_butee') ?></h6>

        <div class="mb-3 row">
            <div class="col-sm-3"></div>
            <div class="col-sm-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="regle_butee_date" name="regle_butee_date"
                           value="1" <?= !empty($programme['regle_butee_date']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="regle_butee_date"><?= $this->lang->line('maintenance_programme_regle_date') ?></label>
                </div>
            </div>
        </div>
        <div class="mb-3 row">
            <label for="periodicite_mois" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_programme_periodicite_mois') ?>
            </label>
            <div class="col-sm-3">
                <input type="number" class="form-control" id="periodicite_mois" name="periodicite_mois"
                       value="<?= htmlspecialchars($programme['periodicite_mois'] ?? '') ?>" min="1" placeholder="—">
            </div>
        </div>

        <div class="mb-3 row">
            <div class="col-sm-3"></div>
            <div class="col-sm-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="regle_butee_heures" name="regle_butee_heures"
                           value="1" <?= !empty($programme['regle_butee_heures']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="regle_butee_heures"><?= $this->lang->line('maintenance_programme_regle_heures') ?></label>
                </div>
            </div>
        </div>
        <div class="mb-3 row">
            <label for="seuil_heures" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_programme_seuil_heures') ?>
            </label>
            <div class="col-sm-3">
                <input type="number" step="0.01" class="form-control" id="seuil_heures" name="seuil_heures"
                       value="<?= htmlspecialchars($programme['seuil_heures'] ?? '') ?>" min="0" placeholder="—">
            </div>
        </div>

        <?php if ($is_edit): ?>
        <div class="mb-3 row">
            <input type="hidden" name="id" value="<?= $programme['id'] ?>">
        </div>
        <?php endif; ?>

    </div>
    <div class="card-footer text-end">
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary me-2">
            <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_annuler') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save" aria-hidden="true"></i>
            <?= $is_edit ? $this->lang->line('maintenance_btn_enregistrer') : $this->lang->line('maintenance_btn_creer') ?>
        </button>
    </div>
    <?= form_close() ?>

</div>
<?php $this->load->view('bs_footer'); ?>
