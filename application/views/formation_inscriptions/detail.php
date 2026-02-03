<!-- VIEW: application/views/formation_inscriptions/detail.php -->
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
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Vue détail d'une inscription avec progression et historique
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('formation');
$this->lang->load('gvv');

// Helper function for status badges
function get_statut_badge($statut) {
    $badges = array(
        'ouverte' => '<span class="badge bg-success">Ouverte</span>',
        'suspendue' => '<span class="badge bg-warning">Suspendue</span>',
        'cloturee' => '<span class="badge bg-primary">Clôturée</span>',
        'abandonnee' => '<span class="badge bg-danger">Abandonnée</span>'
    );
    return $badges[$statut] ?? '<span class="badge bg-secondary">' . htmlspecialchars($statut) . '</span>';
}

?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-graduation-cap" aria-hidden="true"></i>
            <?= $this->lang->line("formation_inscription_detail_title") ?>
        </h3>
        <div>
            <a href="<?= controller_url('formation_progressions') ?>/export_pdf/<?= $inscription['id'] ?>"
               class="btn btn-danger">
                <i class="fas fa-file-pdf" aria-hidden="true"></i> <?= $this->lang->line("formation_progression_export_pdf") ?>
            </a>
            <a href="<?= isset($is_student_view) && $is_student_view ? controller_url('formation_progressions/mes_formations') : controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("gvv_str_back") ?>
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
            <?= get_statut_badge($inscription['statut']) ?>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5"><?= $this->lang->line("formation_inscription_pilote") ?>:</dt>
                        <dd class="col-sm-7">
                            <strong><?= htmlspecialchars($inscription['pilote_prenom'] . ' ' . $inscription['pilote_nom']) ?></strong>
                        </dd>

                        <dt class="col-sm-5"><?= $this->lang->line("formation_inscription_programme") ?>:</dt>
                        <dd class="col-sm-7">
                            <?= htmlspecialchars($inscription['programme_titre']) ?>
                        </dd>

                        <dt class="col-sm-5"><?= $this->lang->line("formation_inscription_instructeur") ?>:</dt>
                        <dd class="col-sm-7">
                            <?php if (!empty($inscription['instructeur_nom'])): ?>
                                <?= htmlspecialchars($inscription['instructeur_prenom'] . ' ' . $inscription['instructeur_nom']) ?>
                            <?php else: ?>
                                <span class="text-muted">Aucun</span>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>

                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5"><?= $this->lang->line("formation_inscription_date_ouverture") ?>:</dt>
                        <dd class="col-sm-7"><?= date('d/m/Y', strtotime($inscription['date_ouverture'])) ?></dd>

                        <?php if ($inscription['statut'] === 'suspendue' && !empty($inscription['date_suspension'])): ?>
                            <dt class="col-sm-5"><?= $this->lang->line("formation_inscription_date_suspension") ?>:</dt>
                            <dd class="col-sm-7"><?= date('d/m/Y', strtotime($inscription['date_suspension'])) ?></dd>
                        <?php endif; ?>

                        <?php if (in_array($inscription['statut'], array('cloturee', 'abandonnee')) && !empty($inscription['date_cloture'])): ?>
                            <dt class="col-sm-5"><?= $this->lang->line("formation_inscription_date_cloture") ?>:</dt>
                            <dd class="col-sm-7"><?= date('d/m/Y', strtotime($inscription['date_cloture'])) ?></dd>
                        <?php endif; ?>

                        <dt class="col-sm-5">Version programme:</dt>
                        <dd class="col-sm-7">v<?= $inscription['version_programme'] ?? 1 ?></dd>
                    </dl>
                </div>
            </div>

            <?php if (!empty($inscription['commentaire'])): ?>
                <div class="mt-2">
                    <strong>Commentaire:</strong>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($inscription['commentaire'])) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($inscription['motif_suspension'])): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <strong>Motif de suspension:</strong>
                    <?= nl2br(htmlspecialchars($inscription['motif_suspension'])) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($inscription['motif_cloture'])): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <strong>Motif de clôture:</strong>
                    <?= nl2br(htmlspecialchars($inscription['motif_cloture'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action buttons (hidden for student view) -->
        <?php if (!isset($is_student_view) || !$is_student_view): ?>
            <?php if (in_array($inscription['statut'], array('ouverte', 'suspendue'))): ?>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <?php if ($inscription['statut'] === 'ouverte'): ?>
                            <a href="<?= controller_url('formation_seances') ?>/create?inscription_id=<?= $inscription['id'] ?>"
                               class="btn btn-success">
                                <i class="fas fa-plus" aria-hidden="true"></i> Nouvelle séance
                            </a>
                            <a href="<?= controller_url($controller) ?>/suspendre/<?= $inscription['id'] ?>"
                               class="btn btn-warning">
                                <i class="fas fa-pause" aria-hidden="true"></i> Suspendre
                            </a>
                            <a href="<?= controller_url($controller) ?>/cloturer/<?= $inscription['id'] ?>"
                               class="btn btn-primary">
                                <i class="fas fa-check" aria-hidden="true"></i> Clôturer
                            </a>
                        <?php elseif ($inscription['statut'] === 'suspendue'): ?>
                            <a href="<?= controller_url($controller) ?>/reactiver/<?= $inscription['id'] ?>"
                               class="btn btn-success">
                                <i class="fas fa-play" aria-hidden="true"></i> Réactiver
                            </a>
                            <a href="<?= controller_url($controller) ?>/cloturer/<?= $inscription['id'] ?>"
                               class="btn btn-danger">
                                <i class="fas fa-times" aria-hidden="true"></i> Abandonner
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Statistiques -->
    <?php if (!empty($stats)): ?>
    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-chart-bar" aria-hidden="true"></i>
                <?= $this->lang->line("formation_progression_statistiques") ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <h2 class="text-primary mb-0"><?= $stats['nb_seances'] ?></h2>
                        <small class="text-muted"><?= $this->lang->line("formation_progression_nb_seances") ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <h2 class="text-primary mb-0"><?= $stats['heures_totales'] ?></h2>
                        <small class="text-muted"><?= $this->lang->line("formation_progression_heures_vol") ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <h2 class="text-primary mb-0"><?= $stats['atterrissages_totaux'] ?></h2>
                        <small class="text-muted"><?= $this->lang->line("formation_progression_atterrissages") ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <h2 class="text-success mb-0"><?= $stats['pourcentage_acquis'] ?>%</h2>
                        <small class="text-muted"><?= $this->lang->line("formation_progression_pourcentage_acquis") ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Progression -->
    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-tasks" aria-hidden="true"></i>
                <?= $this->lang->line("formation_progression_titre") ?>
            </h5>
        </div>
        <div class="card-body">
            <p class="mb-2">
                <strong><?= $progression['pourcentage'] ?>%</strong>
                <?= $this->lang->line("formation_progression_sujets_acquis") ?>
                (<?= $progression['sujets_acquis'] ?>/<?= $progression['total_sujets'] ?>)
            </p>

            <div class="progress mb-3" style="height: 30px;">
                <div class="progress-bar <?= $formation_progression->get_progress_bar_class($progression['pourcentage']) ?>"
                     role="progressbar"
                     style="width: <?= $progression['pourcentage'] ?>%"
                     aria-valuenow="<?= $progression['pourcentage'] ?>" aria-valuemin="0" aria-valuemax="100">
                    <?= $progression['pourcentage'] ?>%
                </div>
            </div>

            <!-- Répartition des niveaux -->
            <?php if (!empty($stats)): ?>
            <div class="row text-center">
                <div class="col-3">
                    <div class="border rounded p-2">
                        <span class="badge bg-secondary mb-2">-</span>
                        <div><strong><?= $stats['nb_sujets_non_abordes'] ?></strong></div>
                        <small><?= $this->lang->line("formation_evaluation_niveau_non_aborde") ?></small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="border rounded p-2">
                        <span class="badge bg-info mb-2">A</span>
                        <div><strong><?= $stats['nb_sujets_abordes'] ?></strong></div>
                        <small><?= $this->lang->line("formation_evaluation_niveau_aborde") ?></small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="border rounded p-2">
                        <span class="badge bg-warning mb-2">R</span>
                        <div><strong><?= $stats['nb_sujets_a_revoir'] ?></strong></div>
                        <small><?= $this->lang->line("formation_evaluation_niveau_a_revoir") ?></small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="border rounded p-2">
                        <span class="badge bg-success mb-2">Q</span>
                        <div><strong><?= $stats['nb_sujets_acquis'] ?></strong></div>
                        <small><?= $this->lang->line("formation_evaluation_niveau_acquis") ?></small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Détail par leçon -->
    <div class="card mb-3">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">
                <i class="fas fa-book" aria-hidden="true"></i>
                <?= $this->lang->line("formation_progression_detail_lecons") ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($lecons)): ?>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <?= $this->lang->line("formation_progression_no_lecons") ?>
                </p>
            <?php else: ?>
                <div class="accordion" id="leconsAccordion">
                    <?php foreach ($lecons as $index => $lecon): ?>
                        <?php
                        $nb_sujets_lecon = count($lecon['sujets']);
                        $nb_acquis_lecon = 0;
                        foreach ($lecon['sujets'] as $s) {
                            if (isset($s['dernier_niveau']) && $s['dernier_niveau'] === 'Q') {
                                $nb_acquis_lecon++;
                            }
                        }
                        $pct_lecon = $nb_sujets_lecon > 0 ? round(($nb_acquis_lecon / $nb_sujets_lecon) * 100) : 0;
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $lecon['id'] ?>">
                                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapse<?= $lecon['id'] ?>">
                                    <span class="d-flex align-items-center flex-wrap gap-2 w-100">
                                        <span>
                                            <strong>Leçon <?= $lecon['numero'] ?> :</strong>
                                            <?= htmlspecialchars($lecon['titre']) ?>
                                        </span>
                                        <span class="badge bg-secondary">
                                            <?= $nb_sujets_lecon ?>
                                            <?= $nb_sujets_lecon > 1 ? $this->lang->line("formation_sujets") : $this->lang->line("formation_sujet") ?>
                                        </span>
                                        <span class="d-flex align-items-center gap-1 ms-auto me-2" style="min-width: 140px;">
                                            <div class="progress flex-grow-1" style="height: 12px;">
                                                <div class="progress-bar <?= $formation_progression->get_progress_bar_class($pct_lecon) ?>"
                                                     role="progressbar"
                                                     style="width: <?= $pct_lecon ?>%"
                                                     aria-valuenow="<?= $pct_lecon ?>" aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="text-nowrap"><?= $pct_lecon ?>%</small>
                                        </span>
                                    </span>
                                </button>
                            </h2>
                            <div id="collapse<?= $lecon['id'] ?>"
                                 class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                                 data-bs-parent="#leconsAccordion">
                                <div class="accordion-body">
                                    <?php if (!empty($lecon['sujets'])): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 8%;">N°</th>
                                                        <th style="width: 45%;"><?= $this->lang->line("formation_sujet") ?></th>
                                                        <th style="width: 15%;" class="text-center"><?= $this->lang->line("formation_evaluation_niveau") ?></th>
                                                        <th style="width: 12%;" class="text-center"><?= $this->lang->line("formation_progression_nb_seances_sujet") ?></th>
                                                        <th style="width: 20%;" class="text-center"><?= $this->lang->line("formation_progression_derniere_eval") ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($lecon['sujets'] as $sujet): ?>
                                                        <tr>
                                                            <td><?= $sujet['numero'] ?></td>
                                                            <td>
                                                                <strong><?= htmlspecialchars($sujet['titre']) ?></strong>
                                                                <?php if (!empty($sujet['description'])): ?>
                                                                    <br><small class="text-muted"><?= htmlspecialchars($sujet['description']) ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge <?= $formation_progression->get_niveau_badge_class($sujet['dernier_niveau']) ?>">
                                                                    <?= $formation_progression->get_niveau_label($sujet['dernier_niveau']) ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <?= $sujet['nb_seances'] ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <?= $sujet['date_derniere_eval'] ? date('d/m/Y', strtotime($sujet['date_derniere_eval'])) : '-' ?>
                                                            </td>
                                                        </tr>
                                                        <?php if (!empty($sujet['historique']) && count($sujet['historique']) > 1): ?>
                                                            <tr class="table-light">
                                                                <td colspan="5">
                                                                    <small>
                                                                        <strong><?= $this->lang->line("formation_progression_historique") ?> :</strong>
                                                                        <?php
                                                                        $hist_items = [];
                                                                        foreach ($sujet['historique'] as $h) {
                                                                            $hist_items[] = date('d/m/Y', strtotime($h['date_seance'])) . ': ' . $h['niveau'];
                                                                        }
                                                                        echo implode(' → ', $hist_items);
                                                                        ?>
                                                                    </small>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                                            <?= $this->lang->line("formation_progression_no_sujets") ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historique des séances -->
    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-history" aria-hidden="true"></i>
                Historique des séances (<?= count($seances) ?>)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($seances)): ?>
                <p class="text-muted">Aucune séance enregistrée</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Instructeur</th>
                                <th>Avion</th>
                                <th>Durée</th>
                                <th>Sujets</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seances as $seance): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($seance['date_seance'])) ?></td>
                                    <td><?= htmlspecialchars($seance['instructeur_nom'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($seance['machine_modele'] ?? '-') ?></td>
                                    <td><?= isset($seance['duree']) ? substr($seance['duree'], 0, 5) : '-' ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= $seance['nb_atterrissages'] ?? 0 ?> att.</span>
                                    </td>
                                    <td>
                                        <a href="<?= controller_url('formation_seances') ?>/detail/<?= $seance['id'] ?>"
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Autorisations de vol solo -->
    <div class="card mb-3">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                <?= $this->lang->line("formation_autorisations_solo") ?> (<?= count($autorisations_solo) ?>)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($autorisations_solo)): ?>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <?= $this->lang->line("formation_autorisations_solo_empty") ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $this->lang->line("formation_autorisation_solo_date") ?></th>
                                <th><?= $this->lang->line("formation_autorisation_solo_instructeur") ?></th>
                                <th><?= $this->lang->line("formation_autorisation_solo_machine") ?></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($autorisations_solo as $autorisation): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($autorisation['date_autorisation'])) ?></td>
                                    <td><?= htmlspecialchars($autorisation['instructeur_prenom'] . ' ' . $autorisation['instructeur_nom']) ?></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($autorisation['machine_id']) ?></span></td>
                                    <td>
                                        <a href="<?= controller_url('formation_autorisations_solo') ?>/detail/<?= $autorisation['id'] ?>"
                                           class="btn btn-sm btn-info" title="Voir les détails">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->load->view('bs_footer'); ?>
