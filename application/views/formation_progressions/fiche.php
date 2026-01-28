<!-- VIEW: application/views/formation_progressions/fiche.php -->
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
 * Vue fiche de progression détaillée
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('formation');
$this->lang->load('gvv');

$stats = $progression['stats'];
$pilote = $progression['pilote'];
$programme = $progression['programme'];
$inscription = $progression['inscription'];
$lecons = $progression['lecons'];

?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-chart-line" aria-hidden="true"></i>
            <?= $this->lang->line("formation_progression_fiche_title") ?>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>/export_pdf/<?= $inscription['id'] ?>" 
               class="btn btn-danger">
                <i class="fas fa-file-pdf" aria-hidden="true"></i> <?= $this->lang->line("formation_progression_export_pdf") ?>
            </a>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("gvv_str_back") ?>
            </a>
        </div>
    </div>

    <!-- En-tête formation -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-user-graduate" aria-hidden="true"></i> 
                <?= $this->lang->line("gvv_str_informations") ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong><?= $this->lang->line("formation_inscription_pilote") ?> :</strong> 
                       <?= htmlspecialchars($pilote['mprenom'] . ' ' . $pilote['mnom']) ?></p>
                    <p><strong><?= $this->lang->line("formation_inscription_programme") ?> :</strong> 
                       <?= htmlspecialchars($programme['titre']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong><?= $this->lang->line("formation_inscription_date_ouverture") ?> :</strong> 
                       <?= date('d/m/Y', strtotime($inscription['date_ouverture'])) ?></p>
                    <p><strong><?= $this->lang->line("formation_inscription_statut") ?> :</strong> 
                       <?php
                       $badge_class = 'secondary';
                       switch ($inscription['statut']) {
                           case 'ouverte': $badge_class = 'success'; break;
                           case 'suspendue': $badge_class = 'warning'; break;
                           case 'cloturee': $badge_class = 'primary'; break;
                           case 'abandonnee': $badge_class = 'danger'; break;
                       }
                       ?>
                       <span class="badge bg-<?= $badge_class ?>">
                           <?= $this->lang->line('formation_inscription_statut_' . $inscription['statut']) ?>
                       </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
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

    <!-- Barre de progression -->
    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-tasks" aria-hidden="true"></i> 
                <?= $this->lang->line("formation_progression_titre") ?>
            </h5>
        </div>
        <div class="card-body">
            <p class="mb-2">
                <strong><?= $stats['pourcentage_acquis'] ?>%</strong> 
                <?= $this->lang->line("formation_progression_sujets_acquis") ?>
                (<?= $stats['nb_sujets_acquis'] ?>/<?= $stats['nb_sujets_total'] ?>)
            </p>
            
            <div class="progress mb-3" style="height: 30px;">
                <div class="progress-bar <?= $formation_progression->get_progress_bar_class($stats['pourcentage_acquis']) ?>" 
                     role="progressbar" 
                     style="width: <?= $stats['pourcentage_acquis'] ?>%;"
                     aria-valuenow="<?= $stats['pourcentage_acquis'] ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                    <?= $stats['pourcentage_acquis'] ?>%
                </div>
            </div>

            <!-- Répartition des niveaux -->
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
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $lecon['id'] ?>">
                                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?= $lecon['id'] ?>">
                                    <strong>Leçon <?= $lecon['numero'] ?> :</strong>&nbsp;
                                    <?= htmlspecialchars($lecon['titre']) ?>
                                    <span class="badge bg-secondary ms-2">
                                        <?= count($lecon['sujets']) ?> 
                                        <?= count($lecon['sujets']) > 1 ? $this->lang->line("formation_sujets") : $this->lang->line("formation_sujet") ?>
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
                                                                    <?= $sujet['dernier_niveau'] ?> - <?= $formation_progression->get_niveau_label($sujet['dernier_niveau']) ?>
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
</div>

