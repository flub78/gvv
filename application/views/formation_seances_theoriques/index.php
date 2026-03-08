<?php
/**
 * Vue : liste des séances théoriques
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-chalkboard" aria-hidden="true"></i>
            <?= $this->lang->line('formation_seances_theoriques_title') ?>
        </h3>
        <a href="<?= controller_url($controller) ?>/create" class="btn btn-success">
            <i class="fas fa-plus" aria-hidden="true"></i>
            <?= $this->lang->line('formation_seance_theorique_create') ?>
        </a>
    </div>

    <?php
    if ($this->session->flashdata('success')) {
        echo '<div class="alert alert-success alert-dismissible fade show">'
           . '<i class="fas fa-check-circle" aria-hidden="true"></i> '
           . $this->session->flashdata('success')
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    if ($this->session->flashdata('error')) {
        echo '<div class="alert alert-danger alert-dismissible fade show">'
           . '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i> '
           . $this->session->flashdata('error')
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    ?>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-filter" aria-hidden="true"></i> Filtres
        </div>
        <div class="card-body">
            <?= form_open(controller_url($controller), array('method' => 'get', 'class' => 'row g-2 align-items-end')) ?>

                <div class="col-md-2">
                    <label for="filter_instructeur" class="form-label form-label-sm">
                        <?= $this->lang->line('formation_seance_instructeur') ?>
                    </label>
                    <select class="form-select form-select-sm" id="filter_instructeur" name="instructeur_id">
                        <option value="">Tous</option>
                        <?php foreach ($instructeurs as $id => $nom): ?>
                            <?php if ($id): ?>
                                <option value="<?= $id ?>"
                                    <?= (!empty($filters['instructeur_id']) && $filters['instructeur_id'] == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nom) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter_participant" class="form-label form-label-sm">
                        <?= $this->lang->line('formation_seance_participants') ?>
                    </label>
                    <select class="form-select form-select-sm" id="filter_participant" name="participant_id">
                        <option value="">Tous</option>
                        <?php foreach ($membres as $id => $nom): ?>
                            <?php if ($id): ?>
                                <option value="<?= htmlspecialchars($id) ?>"
                                    <?= (!empty($filters['participant_id']) && $filters['participant_id'] == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nom) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter_programme" class="form-label form-label-sm">
                        <?= $this->lang->line('formation_seance_programme') ?>
                    </label>
                    <select class="form-select form-select-sm" id="filter_programme" name="programme_id">
                        <option value="">Tous</option>
                        <?php foreach ($programmes as $id => $titre): ?>
                            <?php if ($id): ?>
                                <option value="<?= $id ?>"
                                    <?= (!empty($filters['programme_id']) && $filters['programme_id'] == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($titre) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter_date_debut" class="form-label form-label-sm">Du</label>
                    <input type="date" class="form-control form-control-sm" id="filter_date_debut"
                           name="date_debut" value="<?= $filters['date_debut'] ?? '' ?>">
                </div>

                <div class="col-md-2">
                    <label for="filter_date_fin" class="form-label form-label-sm">Au</label>
                    <input type="date" class="form-control form-control-sm" id="filter_date_fin"
                           name="date_fin" value="<?= $filters['date_fin'] ?? '' ?>">
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

    <!-- Tableau -->
    <div class="card">
        <div class="card-header">
            <strong><?= count($seances) ?></strong> séance(s)
        </div>
        <div class="card-body">
            <?php if (empty($seances)): ?>
                <p class="text-muted"><?= $this->lang->line('formation_seances_theoriques_empty') ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $this->lang->line('formation_seance_date') ?></th>
                                <th><?= $this->lang->line('formation_type_seance_nom') ?></th>
                                <th><?= $this->lang->line('formation_seance_instructeur') ?></th>
                                <th><?= $this->lang->line('formation_seance_programme') ?></th>
                                <th><?= $this->lang->line('formation_seance_lieu') ?></th>
                                <th><?= $this->lang->line('formation_seance_duree_cours') ?></th>
                                <th><?= $this->lang->line('formation_seance_nb_participants') ?></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seances as $s): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($s['date_seance'])) ?></td>
                                    <td><?= htmlspecialchars($s['type_seance'] ?? '') ?></td>
                                    <td><?= htmlspecialchars(
                                        trim(($s['instructeur_prenom'] ?? '') . ' ' . ($s['instructeur_nom'] ?? ''))
                                    ) ?></td>
                                    <td><?= htmlspecialchars($s['programme_titre'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($s['lieu'] ?? '') ?></td>
                                    <td><?= !empty($s['duree']) ? substr($s['duree'], 0, 5) : '—' ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= (int)$s['nb_participants'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= controller_url($controller) ?>/detail/<?= $s['id'] ?>"
                                           class="btn btn-sm btn-info" title="Détail">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="<?= controller_url($controller) ?>/edit/<?= $s['id'] ?>"
                                           class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </a>
                                        <a href="<?= controller_url($controller) ?>/delete/<?= $s['id'] ?>"
                                           class="btn btn-sm btn-danger" title="Supprimer"
                                           onclick="return confirm('Supprimer cette séance ?')">
                                            <i class="fas fa-trash" aria-hidden="true"></i>
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
