<!-- VIEW: application/views/formation_autorisations_solo/index.php -->
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
 * Vue liste des autorisations de vol solo
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('formation');
$this->lang->load('gvv');

?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
            <?= $this->lang->line("formation_autorisations_solo_title") ?>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>/create" class="btn btn-primary">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= $this->lang->line("formation_autorisations_solo_create") ?>
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
        <div class="card-body">
            <form method="get" action="<?= controller_url($controller) ?>" class="row g-3">
                <div class="col-md-4">
                    <label for="eleve_id" class="form-label"><?= $this->lang->line("formation_autorisation_solo_eleve") ?></label>
                    <select class="form-select" id="eleve_id" name="eleve_id">
                        <option value="">-- Tous --</option>
                        <?php foreach ($pilotes as $id => $nom): ?>
                            <option value="<?= $id ?>" <?= isset($filters['eleve_id']) && $filters['eleve_id'] == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nom) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="instructeur_id" class="form-label"><?= $this->lang->line("formation_autorisation_solo_instructeur") ?></label>
                    <select class="form-select" id="instructeur_id" name="instructeur_id">
                        <option value="">-- Tous --</option>
                        <?php foreach ($instructeurs as $id => $nom): ?>
                            <option value="<?= $id ?>" <?= isset($filters['instructeur_id']) && $filters['instructeur_id'] == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nom) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter" aria-hidden="true"></i> Filtrer
                    </button>
                    <a href="<?= controller_url($controller) ?>" class="btn btn-secondary ms-2">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Autorisations list -->
    <?php if (empty($autorisations)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle" aria-hidden="true"></i> <?= $this->lang->line("formation_autorisations_solo_empty") ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><?= $this->lang->line("formation_autorisation_solo_date") ?></th>
                        <th><?= $this->lang->line("formation_autorisation_solo_eleve") ?></th>
                        <th><?= $this->lang->line("formation_autorisation_solo_instructeur") ?></th>
                        <th><?= $this->lang->line("formation_autorisation_solo_machine") ?></th>
                        <th><?= $this->lang->line("formation_inscription_programme") ?></th>
                        <th class="text-center"><?= $this->lang->line("gvv_str_actions") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($autorisations as $autorisation): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($autorisation['date_autorisation'])) ?></td>
                            <td>
                                <?= htmlspecialchars($autorisation['eleve_prenom'] . ' ' . $autorisation['eleve_nom']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($autorisation['instructeur_prenom'] . ' ' . $autorisation['instructeur_nom']) ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($autorisation['machine_id']) ?></span>
                            </td>
                            <td>
                                <?= htmlspecialchars($autorisation['programme_code'] ?? '-') ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= controller_url($controller) ?>/detail/<?= $autorisation['id'] ?>"
                                   class="btn btn-sm btn-info" title="Détails">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </a>
                                <a href="<?= controller_url($controller) ?>/edit/<?= $autorisation['id'] ?>"
                                   class="btn btn-sm btn-warning" title="Modifier">
                                    <i class="fas fa-edit" aria-hidden="true"></i>
                                </a>
                                <a href="<?= controller_url($controller) ?>/delete/<?= $autorisation['id'] ?>"
                                   class="btn btn-sm btn-danger" title="Supprimer">
                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-2">
            <small class="text-muted">
                <?= count($autorisations) ?> autorisation(s)
            </small>
        </div>
    <?php endif; ?>
</div>

<?php $this->load->view('bs_footer'); ?>
