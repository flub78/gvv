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
 * Vue détail d'une inscription avec historique
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
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
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
        
        <!-- Action buttons -->
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
    </div>

    <!-- Progression -->
    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-chart-line" aria-hidden="true"></i>
                Progression
            </h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?= $progression['pourcentage'] ?>%"
                             aria-valuenow="<?= $progression['pourcentage'] ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= $progression['pourcentage'] ?>%
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <h4 class="mb-0">
                        <?= $progression['sujets_acquis'] ?> / <?= $progression['total_sujets'] ?>
                        <small class="text-muted">sujets acquis</small>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Structure du programme -->
    <div class="card mb-3">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">
                <i class="fas fa-book" aria-hidden="true"></i>
                Structure du programme
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($lecons)): ?>
                <p class="text-muted">Aucune leçon définie</p>
            <?php else: ?>
                <div class="accordion" id="leconsAccordion">
                    <?php foreach ($lecons as $index => $lecon): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $lecon['id'] ?>">
                                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" 
                                        type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?= $lecon['id'] ?>">
                                    <strong>Leçon <?= $lecon['numero'] ?>:</strong>&nbsp;
                                    <?= htmlspecialchars($lecon['titre']) ?>
                                    <span class="badge bg-secondary ms-2">
                                        <?= count($lecon['sujets']) ?> sujets
                                    </span>
                                </button>
                            </h2>
                            <div id="collapse<?= $lecon['id'] ?>" 
                                 class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                                 data-bs-parent="#leconsAccordion">
                                <div class="accordion-body">
                                    <?php if (!empty($lecon['sujets'])): ?>
                                        <ul class="list-group">
                                            <?php foreach ($lecon['sujets'] as $sujet): ?>
                                                <li class="list-group-item">
                                                    <strong>Sujet <?= $sujet['numero'] ?>:</strong> 
                                                    <?= htmlspecialchars($sujet['titre']) ?>
                                                    <?php if (!empty($sujet['description'])): ?>
                                                        <p class="mb-0 mt-1 text-muted small">
                                                            <?= nl2br(htmlspecialchars($sujet['description'])) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">Aucun sujet défini</p>
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
</div>

<?php $this->load->view('bs_footer'); ?>
