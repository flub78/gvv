<!-- VIEW: application/views/formation_seances/libres.php -->
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
 * Liste des séances de ré-entrainement (libres, sans formation)
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-plane" aria-hidden="true"></i>
            <?= $this->lang->line("formation_seances_libres_title") ?>
        </h3>
        <div class="d-flex align-items-center gap-3">
            <?= year_selector('formation_seances', $year, $year_selector) ?>
            <a href="<?= controller_url($controller) ?>/create" class="btn btn-success">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= $this->lang->line("formation_seances_create") ?>
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

    <!-- Seances table -->
    <div class="card">
        <div class="card-header">
            <strong><?= count($seances) ?></strong> <?= $this->lang->line("formation_seance") ?>(s)
        </div>
        <div class="card-body">
            <?php if (empty($seances)): ?>
                <p class="text-muted"><?= $this->lang->line("formation_seances_empty") ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="seances-table">
                        <thead>
                            <tr>
                                <th><?= $this->lang->line("formation_seance_date") ?></th>
                                <th><?= $this->lang->line("formation_seance_pilote") ?></th>
                                <th><?= $this->lang->line("formation_seance_programme") ?></th>
                                <th><?= $this->lang->line("formation_seance_instructeur") ?></th>
                                <th><?= $this->lang->line("formation_seance_machine") ?></th>
                                <th><?= $this->lang->line("formation_seance_duree") ?></th>
                                <th><?= $this->lang->line("formation_seance_nb_atterrissages") ?></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seances as $seance): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($seance['date_seance'])) ?></td>
                                    <td><?= htmlspecialchars(($seance['pilote_prenom'] ?? '') . ' ' . ($seance['pilote_nom'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($seance['programme_titre'] ?? '') ?></td>
                                    <td><?= htmlspecialchars(($seance['instructeur_prenom'] ?? '') . ' ' . ($seance['instructeur_nom'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($seance['machine_modele'] ?? '') ?></td>
                                    <td><?= substr($seance['duree'], 0, 5) ?></td>
                                    <td><?= $seance['nb_atterrissages'] ?></td>
                                    <td>
                                        <a href="<?= controller_url($controller) ?>/detail/<?= $seance['id'] ?>"
                                           class="btn btn-sm btn-info" title="Détail">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="<?= controller_url($controller) ?>/edit/<?= $seance['id'] ?>"
                                           class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
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
