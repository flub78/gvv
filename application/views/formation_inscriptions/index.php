<!-- VIEW: application/views/formation_inscriptions/index.php -->
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
 * Vue liste des inscriptions aux formations
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
    <h3>
        <i class="fas fa-user-graduate" aria-hidden="true"></i>
        <?= $this->lang->line("formation_inscriptions_title") ?>
    </h3>

    <?php
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

    <div class="table-responsive">
        <table id="inscriptions-table" class="datatable table table-striped table-hover">
            <thead>
                <tr>
                    <th><?= $this->lang->line("formation_inscription_pilote") ?></th>
                    <th><?= $this->lang->line("formation_inscription_programme") ?></th>
                    <th><?= $this->lang->line("formation_inscription_instructeur") ?></th>
                    <th><?= $this->lang->line("formation_inscription_date_ouverture") ?></th>
                    <th><?= $this->lang->line("formation_inscription_statut") ?></th>
                    <th class="text-center"><?= $this->lang->line("gvv_str_actions") ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inscriptions as $inscription): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($inscription['pilote_prenom'] . ' ' . $inscription['pilote_nom']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($inscription['programme_titre']) ?>
                        </td>
                        <td>
                            <?php if (!empty($inscription['instructeur_nom'])): ?>
                                <?= htmlspecialchars($inscription['instructeur_prenom'] . ' ' . $inscription['instructeur_nom']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($inscription['date_ouverture'])) ?></td>
                        <td>
                            <?php
                            $badge_class = 'secondary';
                            $statut_label = $inscription['statut'];
                            switch ($inscription['statut']) {
                                case 'ouverte':
                                    $badge_class = 'success';
                                    $statut_label = 'Ouverte';
                                    break;
                                case 'suspendue':
                                    $badge_class = 'warning';
                                    $statut_label = 'Suspendue';
                                    break;
                                case 'cloturee':
                                    $badge_class = 'primary';
                                    $statut_label = 'Clôturée';
                                    break;
                                case 'abandonnee':
                                    $badge_class = 'danger';
                                    $statut_label = 'Abandonnée';
                                    break;
                            }
                            ?>
                            <span class="badge bg-<?= $badge_class ?>"><?= $statut_label ?></span>
                        </td>
                        <td class="text-center">
                            <a href="<?= controller_url($controller) ?>/detail/<?= $inscription['id'] ?>"
                               class="btn btn-sm btn-info" title="Détails">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </a>

                            <a href="<?= site_url('formation_seances/create?inscription_id=' . $inscription['id']) ?>"
                               class="btn btn-sm btn-primary" title="Ajouter une séance">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->load->view('bs_footer'); ?>
