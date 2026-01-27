<!-- VIEW: application/views/formation_progressions/index.php -->
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
 * Vue liste des fiches de progression
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
            <i class="fas fa-chart-line" aria-hidden="true"></i>
            <?= $this->lang->line("formation_progressions_title") ?>
        </h3>
    </div>

    <!-- Formations list -->
    <?php if (empty($formations)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle" aria-hidden="true"></i> <?= $this->lang->line("formation_progressions_empty") ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="formations-table">
                <thead class="table-light">
                    <tr>
                        <th><?= $this->lang->line("formation_inscription_pilote") ?></th>
                        <th><?= $this->lang->line("formation_inscription_programme") ?></th>
                        <th><?= $this->lang->line("formation_inscription_date_ouverture") ?></th>
                        <th><?= $this->lang->line("formation_inscription_statut") ?></th>
                        <th class="text-end"><?= $this->lang->line("gvv_str_actions") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($formations as $formation): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($formation['pilote_prenom'] . ' ' . $formation['pilote_nom']) ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($formation['programme_code']) ?></strong> - 
                                <?= htmlspecialchars($formation['programme_titre']) ?>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($formation['date_ouverture'])) ?>
                            </td>
                            <td>
                                <?php
                                $badge_class = 'secondary';
                                switch ($formation['statut']) {
                                    case 'ouverte':
                                        $badge_class = 'success';
                                        break;
                                    case 'suspendue':
                                        $badge_class = 'warning';
                                        break;
                                    case 'cloturee':
                                        $badge_class = 'primary';
                                        break;
                                    case 'abandonnee':
                                        $badge_class = 'danger';
                                        break;
                                }
                                ?>
                                <span class="badge bg-<?= $badge_class ?>">
                                    <?= $this->lang->line('formation_inscription_statut_' . $formation['statut']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?= controller_url($controller) ?>/fiche/<?= $formation['id'] ?>" 
                                       class="btn btn-info" 
                                       title="<?= $this->lang->line('formation_progression_voir_fiche') ?>">
                                        <i class="fas fa-chart-line" aria-hidden="true"></i> 
                                        <?= $this->lang->line('formation_progression_voir') ?>
                                    </a>
                                    <a href="<?= controller_url($controller) ?>/export_pdf/<?= $formation['id'] ?>" 
                                       class="btn btn-danger"
                                       title="<?= $this->lang->line('formation_progression_export_pdf') ?>">
                                        <i class="fas fa-file-pdf" aria-hidden="true"></i> PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    <?php if (!empty($formations)): ?>
    $('#formations-table').DataTable({
        "language": {
            "url": "<?= base_url() ?>assets/datatables/french.json"
        },
        "order": [[2, "desc"]], // Tri par date d'ouverture décroissante
        "pageLength": 25
    });
    <?php endif; ?>
});
</script>

<?php
$this->load->view('bs_footer');
?>
