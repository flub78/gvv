<!-- VIEW: application/views/programmes/index.php -->
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
 * Vue liste des programmes de formation
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('formation');

?>
<div id="body" class="body container-fluid">
    <h3><?= $this->lang->line("formation_programmes_title") ?></h3>

    <?php
    // Show success message
    if ($this->session->flashdata('success')) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo '<strong><i class="fas fa-check-circle" aria-hidden="true"></i></strong> ';
        echo nl2br(htmlspecialchars($this->session->flashdata('success')));
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }

    // Show error message
    if ($this->session->flashdata('error')) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<strong><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></strong> ';
        echo nl2br(htmlspecialchars($this->session->flashdata('error')));
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    ?>

    <!-- Action buttons -->
    <div class="mb-3">
        <a href="<?= controller_url($controller) ?>/create" class="btn btn-primary">
            <i class="fas fa-plus" aria-hidden="true"></i> <?= $this->lang->line("formation_programmes_create") ?>
        </a>
    </div>

    <!-- Programs table -->
    <?php if (empty($programmes)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle" aria-hidden="true"></i> <?= $this->lang->line("formation_programmes_no_programmes") ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable" id="programmes-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 140px;"><?= $this->lang->line("gvv_str_actions") ?></th>
                        <th><?= $this->lang->line("formation_programme_titre") ?></th>
                        <th><?= $this->lang->line("formation_programme_description") ?></th>
                        <th class="text-center"><?= $this->lang->line("formation_programme_nb_lecons") ?></th>
                        <th class="text-center"><?= $this->lang->line("formation_programme_nb_sujets") ?></th>
                        <th class="text-center"><?= $this->lang->line("formation_programme_version") ?></th>
                        <th class="text-center"><?= $this->lang->line("formation_programme_actif") ?></th>
                        <th><?= $this->lang->line("formation_programme_date_modification") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($programmes as $programme): ?>
                        <tr class="<?= $programme['actif'] ? '' : 'table-secondary' ?>">
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?= controller_url($controller) ?>/view/<?= $programme['id'] ?>" 
                                       class="btn btn-primary"
                                       title="<?= $this->lang->line("formation_programmes_view") ?>">
                                        <i class="fas fa-eye text-white" aria-hidden="true"></i>
                                    </a>
                                    <a href="<?= controller_url($controller) ?>/edit/<?= $programme['id'] ?>"
                                       class="btn btn-secondary" 
                                       title="<?= $this->lang->line("formation_programmes_edit") ?>">
                                        <i class="fas fa-edit text-white" aria-hidden="true"></i>
                                    </a>
                                    <a href="<?= controller_url($controller) ?>/export/<?= $programme['id'] ?>"
                                       class="btn btn-info" 
                                       title="<?= $this->lang->line("formation_programmes_export") ?>">
                                        <i class="fas fa-download text-white" aria-hidden="true"></i>
                                    </a>
                                    <?php 
                                    $confirm_msg = str_replace('{name}', $programme['titre'], 
                                                              $this->lang->line("formation_programmes_delete_confirm")); 
                                    ?>
                                    <a href="<?= controller_url($controller) ?>/delete/<?= $programme['id'] ?>"
                                       class="btn btn-danger" 
                                       title="<?= $this->lang->line("formation_programmes_delete") ?>"
                                       onclick="return confirm(<?= htmlspecialchars(json_encode($confirm_msg), ENT_QUOTES) ?>)">
                                        <i class="fas fa-trash text-white" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($programme['titre']) ?></strong>
                            </td>
                            <td>
                                <?php 
                                $desc = htmlspecialchars($programme['description'] ?? '');
                                echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                                ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary"><?= $programme['nb_lecons'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info"><?= $programme['nb_sujets'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">v<?= $programme['version'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($programme['actif']): ?>
                                    <i class="fas fa-check-circle text-success" aria-hidden="true"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-muted" aria-hidden="true"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $programme['date_modification'] ? 
                                    date('d/m/Y H:i', strtotime($programme['date_modification'])) : 
                                    date('d/m/Y H:i', strtotime($programme['date_creation'])) 
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$this->load->view('bs_footer');
?>
