<!-- VIEW: application/views/formation_seances/detail.php -->
<?php
/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 * Détail d'une séance de formation avec évaluations
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

// Niveau labels and colors
$niveau_labels = array(
    '-' => array('label' => $this->lang->line("formation_evaluation_niveau_non_aborde"), 'class' => 'bg-light text-dark'),
    'A' => array('label' => $this->lang->line("formation_evaluation_niveau_aborde"), 'class' => 'bg-info text-white'),
    'R' => array('label' => $this->lang->line("formation_evaluation_niveau_a_revoir"), 'class' => 'bg-warning'),
    'Q' => array('label' => $this->lang->line("formation_evaluation_niveau_acquis"), 'class' => 'bg-success text-white')
);
?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
            <?= $this->lang->line("formation_seances_detail") ?>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("formation_seances_back") ?>
            </a>
        </div>
    </div>

    <?php
    // Display flash messages
    if ($this->session->flashdata('success')) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-check-circle" aria-hidden="true"></i> ' . $this->session->flashdata('success');
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    if ($this->session->flashdata('error')) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i> ' . $this->session->flashdata('error');
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    ?>

    <!-- Informations générales -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <?= $this->lang->line("gvv_str_informations") ?>
            </h5>
            <?php if ($is_libre): ?>
                <span class="badge bg-secondary fs-6"><?= $this->lang->line("formation_seance_type_libre") ?></span>
            <?php else: ?>
                <span class="badge bg-primary fs-6 border border-white"><?= $this->lang->line("formation_seance_type_formation") ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5"><?= $this->lang->line("formation_seance_date") ?>:</dt>
                        <dd class="col-sm-7"><?= date('d/m/Y', strtotime($seance['date_seance'])) ?></dd>

                        <dt class="col-sm-5"><?= $this->lang->line("formation_seance_pilote") ?>:</dt>
                        <dd class="col-sm-7">
                            <strong><?= htmlspecialchars(($seance['pilote_prenom'] ?? '') . ' ' . ($seance['pilote_nom'] ?? '')) ?></strong>
                        </dd>

                        <dt class="col-sm-5"><?= $this->lang->line("formation_seance_instructeur") ?>:</dt>
                        <dd class="col-sm-7">
                            <?= htmlspecialchars(($seance['instructeur_prenom'] ?? '') . ' ' . ($seance['instructeur_nom'] ?? '')) ?>
                        </dd>

                        <dt class="col-sm-5"><?= $this->lang->line("formation_seance_programme") ?>:</dt>
                        <dd class="col-sm-7">
                            <?= htmlspecialchars($seance['programme_titre'] ?? '') ?>
                        </dd>
                    </dl>
                </div>

                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5"><?= $this->lang->line("formation_seance_machine") ?>:</dt>
                        <dd class="col-sm-7">
                            <?= htmlspecialchars(($seance['machine_modele'] ?? '') . ' - ' . ($seance['machine_immat'] ?? '')) ?>
                        </dd>

                        <dt class="col-sm-5"><?= $this->lang->line("formation_seance_duree") ?>:</dt>
                        <dd class="col-sm-7"><?= substr($seance['duree'], 0, 5) ?></dd>

                        <dt class="col-sm-5"><?= $this->lang->line("formation_seance_nb_atterrissages") ?>:</dt>
                        <dd class="col-sm-7"><?= $seance['nb_atterrissages'] ?></dd>

                        <?php if (!$is_libre && !empty($seance['inscription_id'])): ?>
                            <dt class="col-sm-5"><?= $this->lang->line("formation_seance_inscription") ?>:</dt>
                            <dd class="col-sm-7">
                                <a href="<?= controller_url('formation_inscriptions') ?>/detail/<?= $seance['inscription_id'] ?>">
                                    Inscription #<?= $seance['inscription_id'] ?>
                                </a>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Météo -->
            <?php if (!empty($meteo)): ?>
                <div class="mt-2">
                    <strong><?= $this->lang->line("formation_seance_meteo") ?>:</strong>
                    <?php foreach ($meteo as $condition): ?>
                        <?php $label = $this->lang->line("formation_seance_meteo_" . $condition); ?>
                        <?php if ($label): ?>
                            <span class="badge bg-info"><?= $label ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Commentaires -->
            <?php if (!empty($seance['commentaires'])): ?>
                <div class="mt-3">
                    <strong><?= $this->lang->line("formation_seance_commentaire") ?>:</strong>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($seance['commentaires'])) ?></p>
                </div>
            <?php endif; ?>

            <!-- Prochaines leçons -->
            <?php if (!empty($seance['prochaines_lecons'])): ?>
                <div class="mt-2">
                    <strong><?= $this->lang->line("formation_seance_prochaines_lecons") ?>:</strong>
                    <?= htmlspecialchars($seance['prochaines_lecons']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action buttons -->
        <div class="card-footer">
            <div class="d-flex gap-2">
                <a href="<?= controller_url($controller) ?>/edit/<?= $seance['id'] ?>"
                   class="btn btn-warning">
                    <i class="fas fa-edit" aria-hidden="true"></i> Modifier
                </a>
                <a href="<?= controller_url($controller) ?>/delete/<?= $seance['id'] ?>"
                   class="btn btn-danger"
                   onclick="return confirm('<?= $this->lang->line("formation_seance_delete_confirm") ?>');">
                    <i class="fas fa-trash" aria-hidden="true"></i> Supprimer
                </a>
            </div>
        </div>
    </div>

    <!-- Évaluations -->
    <div class="card mb-3">
        <div class="card-header bg-warning">
            <h5 class="mb-0">
                <i class="fas fa-star" aria-hidden="true"></i>
                <?= $this->lang->line("formation_evaluations") ?>
                (<?= count($evaluations) ?>)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($evaluations)): ?>
                <p class="text-muted"><?= $this->lang->line("formation_evaluation_aucune") ?></p>
            <?php else: ?>
                <?php
                // Group evaluations by lesson
                $evals_by_lecon = array();
                foreach ($evaluations as $eval) {
                    $lecon_key = ($eval['lecon_numero'] ?? '?') . ' - ' . ($eval['lecon_titre'] ?? '');
                    if (!isset($evals_by_lecon[$lecon_key])) {
                        $evals_by_lecon[$lecon_key] = array();
                    }
                    $evals_by_lecon[$lecon_key][] = $eval;
                }
                ?>
                <?php foreach ($evals_by_lecon as $lecon_name => $lecon_evals): ?>
                    <h6 class="text-primary mt-3">
                        <i class="fas fa-book" aria-hidden="true"></i>
                        <?= $this->lang->line("formation_lecon") ?> <?= htmlspecialchars($lecon_name) ?>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">#</th>
                                    <th><?= $this->lang->line("formation_evaluation_sujet") ?></th>
                                    <th style="width:150px"><?= $this->lang->line("formation_evaluation_niveau") ?></th>
                                    <th><?= $this->lang->line("formation_evaluation_commentaire") ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lecon_evals as $eval): ?>
                                    <?php
                                    $niv = $eval['niveau'] ?? '-';
                                    $niv_info = $niveau_labels[$niv] ?? $niveau_labels['-'];
                                    ?>
                                    <tr>
                                        <td class="text-muted"><?= htmlspecialchars($eval['sujet_numero'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($eval['sujet_titre'] ?? '') ?></td>
                                        <td>
                                            <span class="badge <?= $niv_info['class'] ?>"><?= $niv_info['label'] ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($eval['commentaire'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->load->view('bs_footer'); ?>
