<!-- VIEW: application/views/programmes/view.php -->
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
 * Vue détails d'un programme de formation (lecture seule)
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('formation');

?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?= htmlspecialchars($programme['titre']) ?></h3>
        <div>
            <a href="<?= controller_url($controller) ?>/export/<?= $programme['id'] ?>" class="btn btn-success">
                <i class="fas fa-download" aria-hidden="true"></i> <?= $this->lang->line("formation_export_markdown") ?>
            </a>
            <a href="<?= controller_url($controller) ?>/edit/<?= $programme['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-edit" aria-hidden="true"></i> <?= $this->lang->line("formation_programmes_edit") ?>
            </a>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("formation_programmes_back") ?>
            </a>
        </div>
    </div>

    <?php
    // Show error message
    if ($this->session->flashdata('error')) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<strong><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></strong> ';
        echo nl2br(htmlspecialchars($this->session->flashdata('error')));
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    // Show success message
    if ($this->session->flashdata('success')) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo '<strong><i class="fas fa-check-circle" aria-hidden="true"></i></strong> ';
        echo nl2br(htmlspecialchars($this->session->flashdata('success')));
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    ?>

    <!-- Program information -->
    <div class="card mb-3">
        <div class="card-body">
            <?php if (!empty($programme['description'])): ?>
                <p class="card-text"><?= nl2br(htmlspecialchars($programme['description'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($programme['objectifs'])): ?>
                <div class="mb-3">
                    <h5><?= $this->lang->line("formation_programme_objectifs") ?></h5>
                    <p><?= nl2br(htmlspecialchars($programme['objectifs'])) ?></p>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong><?= $this->lang->line("formation_programme_type_aeronef") ?>:</strong>
                        <?php if (isset($programme['type_aeronef']) && $programme['type_aeronef'] === 'avion'): ?>
                            <span class="badge bg-info"><i class="fas fa-fighter-jet" aria-hidden="true"></i> <?= $this->lang->line("formation_programme_type_avion") ?></span>
                        <?php else: ?>
                            <span class="badge bg-primary"><i class="fas fa-plane" aria-hidden="true"></i> <?= $this->lang->line("formation_programme_type_planeur") ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="mb-1">
                        <strong><?= $this->lang->line("formation_programme_version") ?>:</strong>
                        <span class="badge bg-secondary">v<?= $programme['version'] ?></span>
                    </p>
                    <p class="mb-1">
                        <strong><?= $this->lang->line("formation_programme_actif") ?>:</strong>
                        <?php if ($programme['statut'] === 'actif'): ?>
                            <span class="badge bg-success">Actif</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Archivé</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong><?= $this->lang->line("formation_programme_date_creation") ?>:</strong>
                        <?= date('d/m/Y H:i', strtotime($programme['date_creation'])) ?>
                    </p>
                    <?php if ($programme['date_modification']): ?>
                        <p class="mb-1">
                            <strong><?= $this->lang->line("formation_programme_date_modification") ?>:</strong>
                            <?= date('d/m/Y H:i', strtotime($programme['date_modification'])) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">
                        <i class="fas fa-book text-primary" aria-hidden="true"></i>
                        <strong><?= count($lecons) ?></strong> 
                        <?= count($lecons) > 1 ? $this->lang->line("formation_lecons") : $this->lang->line("formation_lecon") ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <?php
                    $total_sujets = 0;
                    foreach ($lecons as $lecon) {
                        $total_sujets += count($lecon['sujets']);
                    }
                    ?>
                    <p class="mb-0">
                        <i class="fas fa-list text-info" aria-hidden="true"></i>
                        <strong><?= $total_sujets ?></strong> 
                        <?= $total_sujets > 1 ? $this->lang->line("formation_sujets") : $this->lang->line("formation_sujet") ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lessons details -->
    <?php if (!empty($lecons)): ?>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-book" aria-hidden="true"></i> 
                    <?= $this->lang->line("formation_lecons") ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="leconsAccordion">
                    <?php foreach ($lecons as $index => $lecon): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $lecon['id'] ?>">
                                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" 
                                        type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?= $lecon['id'] ?>" 
                                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>">
                                    <div class="d-flex align-items-center w-100">
                                        <span class="badge bg-primary me-2"><?= $lecon['numero'] ?></span>
                                        <strong class="me-2">Leçon <?= $lecon['numero'] ?>:</strong>
                                        <?= htmlspecialchars($lecon['titre']) ?>
                                        <span class="badge bg-secondary ms-auto me-2">
                                            <?= count($lecon['sujets']) ?> 
                                            <?= count($lecon['sujets']) > 1 ? 
                                                $this->lang->line("formation_sujets") : 
                                                $this->lang->line("formation_sujet") ?>
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?= $lecon['id'] ?>" 
                                 class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                                 aria-labelledby="heading<?= $lecon['id'] ?>" 
                                 data-bs-parent="#leconsAccordion">
                                <div class="accordion-body">
                                    <?php if (!empty($lecon['description'])): ?>
                                        <div class="alert alert-light mb-3">
                                            <strong><?= $this->lang->line("formation_lecon_description") ?>:</strong><br>
                                            <?= nl2br(htmlspecialchars($lecon['description'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($lecon['objectifs'])): ?>
                                        <div class="alert alert-info mb-3">
                                            <strong><?= $this->lang->line("formation_lecon_objectifs") ?>:</strong><br>
                                            <?= nl2br(htmlspecialchars($lecon['objectifs'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($lecon['sujets'])): ?>
                                        <h6 class="mb-3">
                                            <i class="fas fa-list" aria-hidden="true"></i> 
                                            <?= $this->lang->line("formation_sujets") ?>
                                        </h6>
                                        <div class="list-group">
                                            <?php foreach ($lecon['sujets'] as $sujet): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                                        <h6 class="mb-1">
                                                            <span class="badge bg-info"><?= $sujet['numero'] ?></span>
                                                            <?= htmlspecialchars($sujet['titre']) ?>
                                                        </h6>
                                                    </div>
                                                    <?php if (!empty($sujet['description'])): ?>
                                                        <p class="mb-2 mt-2">
                                                            <?= nl2br(htmlspecialchars($sujet['description'])) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($sujet['objectifs'])): ?>
                                                        <div class="mt-2">
                                                            <strong class="text-muted small">
                                                                <?= $this->lang->line("formation_sujet_objectifs") ?>:
                                                            </strong>
                                                            <div class="text-muted small">
                                                                <?= nl2br(htmlspecialchars($sujet['objectifs'])) ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-info-circle" aria-hidden="true"></i> 
                                            Aucun sujet défini pour cette leçon.
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> 
            Ce programme ne contient aucune leçon.
        </div>
    <?php endif; ?>
</div>
