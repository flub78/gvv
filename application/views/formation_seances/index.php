<!-- VIEW: application/views/formation_seances/index.php -->
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
 * Liste des séances de formation avec filtres et distinction inscription/libre
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
            <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
            <?= $this->lang->line("formation_seances_title") ?>
        </h3>
        <div>
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

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-filter" aria-hidden="true"></i> Filtres
        </div>
        <div class="card-body">
            <?= form_open(controller_url($controller), array('method' => 'get', 'class' => 'row g-2 align-items-end')) ?>

                <div class="col-md-2">
                    <label for="filter_pilote" class="form-label form-label-sm"><?= $this->lang->line("formation_seance_pilote") ?></label>
                    <select class="form-select form-select-sm" id="filter_pilote" name="pilote_id">
                        <option value="">Tous</option>
                        <?php foreach ($pilotes as $id => $nom): ?>
                            <?php if ($id): ?>
                                <option value="<?= $id ?>" <?= (isset($filters['pilote_id']) && $filters['pilote_id'] == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nom) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter_instructeur" class="form-label form-label-sm"><?= $this->lang->line("formation_seance_instructeur") ?></label>
                    <select class="form-select form-select-sm" id="filter_instructeur" name="instructeur_id">
                        <option value="">Tous</option>
                        <?php foreach ($instructeurs as $id => $nom): ?>
                            <?php if ($id): ?>
                                <option value="<?= $id ?>" <?= (isset($filters['instructeur_id']) && $filters['instructeur_id'] == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nom) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter_programme" class="form-label form-label-sm"><?= $this->lang->line("formation_seance_programme") ?></label>
                    <select class="form-select form-select-sm" id="filter_programme" name="programme_id">
                        <option value="">Tous</option>
                        <?php foreach ($programmes as $id => $titre): ?>
                            <?php if ($id): ?>
                                <option value="<?= $id ?>" <?= (isset($filters['programme_id']) && $filters['programme_id'] == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($titre) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter_type" class="form-label form-label-sm"><?= $this->lang->line("formation_seance_type") ?></label>
                    <select class="form-select form-select-sm" id="filter_type" name="type">
                        <option value="" <?= empty($filters['type']) ? 'selected' : '' ?>><?= $this->lang->line("formation_seance_type_toutes") ?></option>
                        <option value="formation" <?= (isset($filters['type']) && $filters['type'] == 'formation') ? 'selected' : '' ?>><?= $this->lang->line("formation_seance_type_formation") ?></option>
                        <option value="libre" <?= (isset($filters['type']) && $filters['type'] == 'libre') ? 'selected' : '' ?>><?= $this->lang->line("formation_seance_type_libre") ?></option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter_categorie" class="form-label form-label-sm"><?= $this->lang->line("formation_seance_categorie") ?></label>
                    <select class="form-select form-select-sm" id="filter_categorie" name="categorie_seance">
                        <option value=""><?= $this->lang->line("formation_seance_categorie_toutes") ?></option>
                        <?php foreach ($categories as $cat_value => $cat_label): ?>
                            <?php if ($cat_value): ?>
                                <option value="<?= htmlspecialchars($cat_value) ?>"
                                    <?= (isset($filters['categorie_seance']) && $filters['categorie_seance'] == $cat_value) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat_label) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-1">
                    <label for="filter_date_debut" class="form-label form-label-sm">Du</label>
                    <input type="date" class="form-control form-control-sm" id="filter_date_debut" name="date_debut"
                           value="<?= $filters['date_debut'] ?? '' ?>">
                </div>

                <div class="col-md-1">
                    <label for="filter_date_fin" class="form-label form-label-sm">Au</label>
                    <input type="date" class="form-control form-control-sm" id="filter_date_fin" name="date_fin"
                           value="<?= $filters['date_fin'] ?? '' ?>">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search" aria-hidden="true"></i> Filtrer
                    </button>
                    <a href="<?= controller_url($controller) ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </a>
                </div>

            <?= form_close() ?>
        </div>
    </div>

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
                                <th><?= $this->lang->line("formation_seance_type") ?></th>
                                <th><?= $this->lang->line("formation_seance_categorie") ?></th>
                                <th><?= $this->lang->line("formation_seance_programme") ?></th>
                                <th><?= $this->lang->line("formation_seance_machine") ?></th>
                                <th><?= $this->lang->line("formation_seance_duree") ?></th>
                                <th><?= $this->lang->line("formation_seance_nb_atterrissages") ?></th>
                                <th><?= $this->lang->line("formation_seance_instructeur") ?></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seances as $seance): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($seance['date_seance'])) ?></td>
                                    <td><?= htmlspecialchars(($seance['pilote_prenom'] ?? '') . ' ' . ($seance['pilote_nom'] ?? '')) ?></td>
                                    <td>
                                        <?php if ($seance['type_seance'] === 'Libre'): ?>
                                            <span class="badge bg-secondary"><?= $this->lang->line("formation_seance_type_libre") ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-primary"><?= $this->lang->line("formation_seance_type_formation") ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($seance['categorie_seance'])): ?>
                                            <?php foreach (array_map('trim', explode(',', $seance['categorie_seance'])) as $cat): ?>
                                                <span class="badge bg-info"><?= htmlspecialchars($cat) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($seance['programme_titre'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($seance['machine_modele'] ?? '') ?></td>
                                    <td><?= substr($seance['duree'], 0, 5) ?></td>
                                    <td><?= $seance['nb_atterrissages'] ?></td>
                                    <td><?= htmlspecialchars(($seance['instructeur_prenom'] ?? '') . ' ' . ($seance['instructeur_nom'] ?? '')) ?></td>
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
