<?php
/**
 * Vue : formulaire creation/edition d'un equipement maintenable
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$is_edit  = ($action === 'edit');
$form_url = $is_edit
    ? controller_url($controller) . '/update/' . $equipement['id']
    : controller_url($controller) . '/store';
$title    = $is_edit
    ? $this->lang->line('maintenance_equipements_edit')
    : $this->lang->line('maintenance_equipements_create');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-cog" aria-hidden="true"></i>
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
            <label for="nom" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_equipement_nom') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="nom" name="nom"
                       value="<?= htmlspecialchars($equipement['nom'] ?? '') ?>"
                       maxlength="100" required>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="aeronef_id" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_equipement_aeronef') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <?php if ($is_edit): ?>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($equipement['aeronef_id']) ?>" disabled>
                    <div class="form-text text-muted">
                        <?= $this->lang->line('maintenance_equipement_aeronef_help_edit') ?>
                        <a href="<?= controller_url($controller) ?>/transfer/<?= $equipement['id'] ?>">
                            <?= $this->lang->line('maintenance_equipements_transfer') ?>
                        </a>
                    </div>
                <?php else: ?>
                    <?= form_dropdown('aeronef_id', $aeronef_selector, $equipement['aeronef_id'] ?? '', 'class="form-select" id="aeronef_id" required') ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="description" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_equipement_description') ?>
            </label>
            <div class="col-sm-6">
                <textarea class="form-control" id="description" name="description"
                          rows="3" maxlength="255"><?= htmlspecialchars($equipement['description'] ?? '') ?></textarea>
            </div>
        </div>

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
