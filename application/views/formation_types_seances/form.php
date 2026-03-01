<?php
/**
 * Vue : formulaire création/édition d'un type de séance de formation
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$is_edit  = ($action === 'edit');
$form_url = $is_edit
    ? controller_url($controller) . '/update/' . $type['id']
    : controller_url($controller) . '/store';
$title    = $is_edit
    ? $this->lang->line('formation_types_seances_edit')
    : $this->lang->line('formation_types_seances_create');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-tag" aria-hidden="true"></i>
            <?= $title ?>
        </h3>
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Retour
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
                <?= $this->lang->line('formation_type_seance_nom') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="nom" name="nom"
                       value="<?= htmlspecialchars($type['nom'] ?? '') ?>"
                       maxlength="100" required>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_type_seance_nature') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <div class="form-check form-check-inline mt-2">
                    <input class="form-check-input" type="radio" name="nature" id="nature_vol"
                           value="vol" <?= (($type['nature'] ?? '') === 'vol') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="nature_vol">
                        <span class="badge bg-primary">
                            <i class="fas fa-plane" aria-hidden="true"></i>
                            <?= $this->lang->line('formation_nature_vol') ?>
                        </span>
                    </label>
                </div>
                <div class="form-check form-check-inline mt-2">
                    <input class="form-check-input" type="radio" name="nature" id="nature_theorique"
                           value="theorique" <?= (($type['nature'] ?? 'theorique') === 'theorique') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="nature_theorique">
                        <span class="badge bg-success">
                            <i class="fas fa-chalkboard" aria-hidden="true"></i>
                            <?= $this->lang->line('formation_nature_theorique') ?>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="periodicite_max_jours" class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_type_seance_periodicite') ?>
            </label>
            <div class="col-sm-3">
                <input type="number" class="form-control" id="periodicite_max_jours"
                       name="periodicite_max_jours"
                       value="<?= htmlspecialchars($type['periodicite_max_jours'] ?? '') ?>"
                       min="1" placeholder="—">
                <div class="form-text text-muted">
                    <?= $this->lang->line('formation_type_seance_periodicite_help') ?>
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="description" class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_type_seance_description') ?>
            </label>
            <div class="col-sm-6">
                <textarea class="form-control" id="description" name="description"
                          rows="3"><?= htmlspecialchars($type['description'] ?? '') ?></textarea>
            </div>
        </div>

        <?php if ($is_edit): ?>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_type_seance_actif') ?>
            </label>
            <div class="col-sm-6 mt-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="actif" name="actif"
                           value="1" <?= !empty($type['actif']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="actif">Actif</label>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <div class="card-footer text-end">
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary me-2">
            <i class="fas fa-times" aria-hidden="true"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save" aria-hidden="true"></i>
            <?= $is_edit ? 'Enregistrer' : 'Créer' ?>
        </button>
    </div>
    <?= form_close() ?>

</div>
<?php $this->load->view('bs_footer'); ?>
