<?php
/**
 * Vue : formulaire d'operation de maintenance (saisie directe et/ou
 * depot d'un compte rendu, sur le meme ecran -- PRD EF4.2)
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$is_edit = ($action === 'edit');
$form_url = $is_edit
    ? controller_url($controller) . '/update/' . $operation['id']
    : controller_url($controller) . '/store/' . $dossier['id'];

// Regroupement des taches par section, dans l'ordre
$sections = array();
foreach ($taches as $tache) {
    $sections[$tache['section_titre']][] = $tache;
}
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-tools" aria-hidden="true"></i>
            <?= $is_edit ? $this->lang->line('maintenance_operation_edit') : $this->lang->line('maintenance_operation_create') ?>
        </h3>
        <a href="<?= controller_url('maintenance_dossiers') ?>/view/<?= $dossier['id'] ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_retour') ?>
        </a>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle" aria-hidden="true"></i>
        <?= htmlspecialchars($dossier['programme_code']) ?> - <?= htmlspecialchars($dossier['programme_titre']) ?>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?= form_open_multipart($form_url, array('class' => 'card mb-3')) ?>
    <div class="card-body">

        <div class="row">
            <div class="mb-3 col-md-4">
                <label for="date_operation" class="form-label"><?= $this->lang->line('maintenance_operation_date') ?> <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="date_operation" name="date_operation"
                       value="<?= htmlspecialchars($operation['date_operation'] ?? '') ?>" required>
            </div>

            <?php if (!empty($programme['regle_butee_heures'])): ?>
            <div class="mb-3 col-md-4">
                <label for="horametre_releve" class="form-label"><?= $this->lang->line('maintenance_operation_horametre') ?></label>
                <input type="number" step="0.01" class="form-control" id="horametre_releve" name="horametre_releve"
                       value="<?= htmlspecialchars($operation['horametre_releve'] ?? '') ?>" min="0">
                <div class="form-text text-muted"><?= sprintf($this->lang->line('maintenance_operation_horametre_help'), $programme['seuil_heures']) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($programme['regle_butee_date'])): ?>
            <div class="mb-3 col-md-4">
                <label for="nouvelle_echeance" class="form-label"><?= $this->lang->line('maintenance_operation_nouvelle_echeance') ?></label>
                <input type="date" class="form-control" id="nouvelle_echeance" name="nouvelle_echeance"
                       value="<?= htmlspecialchars($operation['nouvelle_echeance'] ?? '') ?>">
                <div class="form-text text-muted"><?= sprintf($this->lang->line('maintenance_operation_echeance_help'), $programme['periodicite_mois']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="commentaire" class="form-label"><?= $this->lang->line('maintenance_operation_commentaire') ?></label>
            <textarea class="form-control" id="commentaire" name="commentaire" rows="2" maxlength="500"><?= htmlspecialchars($operation['commentaire'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label for="compte_rendu" class="form-label"><?= $this->lang->line('maintenance_operation_compte_rendu') ?></label>
            <input type="file" class="form-control" id="compte_rendu" name="compte_rendu" accept=".pdf,.jpg,.jpeg,.png,.gif">
            <div class="form-text text-muted"><?= $this->lang->line('maintenance_operation_compte_rendu_help') ?></div>
            <?php if (!empty($operation['document_id'])): ?>
                <div class="mt-1">
                    <i class="fas fa-paperclip" aria-hidden="true"></i>
                    <a href="<?= site_url('archived_documents/preview/' . $operation['document_id']) ?>" target="_blank">
                        <?= $this->lang->line('maintenance_operation_document_existant') ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <hr>
        <h6 class="text-muted"><?= $this->lang->line('maintenance_operation_taches') ?></h6>

        <?php if (empty($sections)): ?>
            <p class="text-muted"><?= $this->lang->line('maintenance_operation_aucune_tache') ?></p>
        <?php else: ?>
            <?php foreach ($sections as $section_titre => $section_taches): ?>
                <h6 class="mt-3"><i class="fas fa-folder-open text-primary" aria-hidden="true"></i> <?= htmlspecialchars($section_titre) ?></h6>
                <table class="table table-sm">
                    <tbody>
                    <?php foreach ($section_taches as $tache):
                        $existing = $realisations[$tache['id']] ?? array('statut' => 'non_fait', 'commentaire' => '');
                    ?>
                        <tr>
                            <td style="width: 30%"><?= htmlspecialchars($tache['titre']) ?></td>
                            <td style="width: 30%">
                                <?php foreach (array('fait' => 'success', 'non_fait' => 'secondary', 'non_applicable' => 'warning') as $statut => $color): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio"
                                               name="realisations[<?= $tache['id'] ?>][statut]"
                                               id="tache_<?= $tache['id'] ?>_<?= $statut ?>"
                                               value="<?= $statut ?>"
                                               <?= ($existing['statut'] === $statut) ? 'checked' : '' ?>>
                                        <label class="form-check-label badge bg-<?= $color ?>" for="tache_<?= $tache['id'] ?>_<?= $statut ?>">
                                            <?= $this->lang->line('maintenance_realisation_' . $statut) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm"
                                       name="realisations[<?= $tache['id'] ?>][commentaire]"
                                       value="<?= htmlspecialchars($existing['commentaire'] ?? '') ?>"
                                       placeholder="<?= $this->lang->line('maintenance_equipement_description') ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
    <div class="card-footer text-end">
        <a href="<?= controller_url('maintenance_dossiers') ?>/view/<?= $dossier['id'] ?>" class="btn btn-outline-secondary me-2">
            <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_annuler') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save" aria-hidden="true"></i>
            <?= $is_edit ? $this->lang->line('maintenance_btn_enregistrer') : $this->lang->line('maintenance_operation_enregistrer') ?>
        </button>
    </div>
    <?= form_close() ?>

</div>
<?php $this->load->view('bs_footer'); ?>
