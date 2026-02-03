<!-- VIEW: application/views/formation_autorisations_solo/detail.php -->
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
 * Vue détail autorisation de vol solo
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
            <?= $this->lang->line("formation_autorisations_solo_detail") ?>
        </h3>
        <div>
            <?php if ($is_instructeur): ?>
                <a href="<?= controller_url($controller) ?>/edit/<?= $autorisation['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit" aria-hidden="true"></i> Modifier
                </a>
                <a href="<?= controller_url($controller) ?>/delete/<?= $autorisation['id'] ?>" class="btn btn-danger">
                    <i class="fas fa-trash" aria-hidden="true"></i> Supprimer
                </a>
            <?php endif; ?>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("formation_autorisations_solo_back") ?>
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

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-plane" aria-hidden="true"></i>
                Autorisation de vol solo - <?= htmlspecialchars($autorisation['machine_id']) ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 40%"><?= $this->lang->line("formation_autorisation_solo_date") ?></th>
                            <td>
                                <strong><?= date('d/m/Y', strtotime($autorisation['date_autorisation'])) ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->lang->line("formation_autorisation_solo_eleve") ?></th>
                            <td><?= htmlspecialchars($autorisation['eleve_prenom'] . ' ' . $autorisation['eleve_nom']) ?></td>
                        </tr>
                        <tr>
                            <th><?= $this->lang->line("formation_autorisation_solo_instructeur") ?></th>
                            <td><?= htmlspecialchars($autorisation['instructeur_prenom'] . ' ' . $autorisation['instructeur_nom']) ?></td>
                        </tr>
                        <tr>
                            <th><?= $this->lang->line("formation_autorisation_solo_machine") ?></th>
                            <td><span class="badge bg-info fs-6"><?= htmlspecialchars($autorisation['machine_id']) ?></span></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 40%"><?= $this->lang->line("formation_inscription_programme") ?></th>
                            <td>
                                <?php if (!empty($autorisation['programme_titre'])): ?>
                                    <?= htmlspecialchars($autorisation['programme_code'] . ' - ' . $autorisation['programme_titre']) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->lang->line("formation_autorisation_solo_section") ?></th>
                            <td>
                                <?php if (!empty($autorisation['section_nom'])): ?>
                                    <?= htmlspecialchars($autorisation['section_nom']) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->lang->line("formation_autorisation_solo_date_creation") ?></th>
                            <td>
                                <?php if (!empty($autorisation['date_creation'])): ?>
                                    <?= date('d/m/Y H:i', strtotime($autorisation['date_creation'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->lang->line("formation_autorisation_solo_date_modification") ?></th>
                            <td>
                                <?php if (!empty($autorisation['date_modification'])): ?>
                                    <?= date('d/m/Y H:i', strtotime($autorisation['date_modification'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr>

            <h5><i class="fas fa-list" aria-hidden="true"></i> <?= $this->lang->line("formation_autorisation_solo_consignes") ?></h5>
            <div class="card bg-light">
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($autorisation['consignes']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($autorisation['inscription_id'])): ?>
    <div class="mt-3">
        <a href="<?= controller_url('formation_inscriptions') ?>/detail/<?= $autorisation['inscription_id'] ?>" class="btn btn-outline-primary">
            <i class="fas fa-graduation-cap" aria-hidden="true"></i> Voir la fiche de progression
        </a>
    </div>
    <?php endif; ?>
</div>

<?php $this->load->view('bs_footer'); ?>
