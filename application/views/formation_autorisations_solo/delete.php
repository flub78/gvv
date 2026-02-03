<!-- VIEW: application/views/formation_autorisations_solo/delete.php -->
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
 * Vue confirmation suppression autorisation de vol solo
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
            <i class="fas fa-trash text-danger" aria-hidden="true"></i>
            Supprimer l'autorisation
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("formation_autorisations_solo_back") ?>
            </a>
        </div>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        <?= $this->lang->line("formation_autorisation_solo_delete_confirm") ?>
    </div>

    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                Autorisation à supprimer
            </h5>
        </div>
        <div class="card-body">
            <table class="table table-borderless">
                <tr>
                    <th style="width: 30%"><?= $this->lang->line("formation_autorisation_solo_date") ?></th>
                    <td><?= date('d/m/Y', strtotime($autorisation['date_autorisation'])) ?></td>
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
                    <td><span class="badge bg-info"><?= htmlspecialchars($autorisation['machine_id']) ?></span></td>
                </tr>
            </table>

            <hr>

            <form method="post" action="<?= controller_url($controller) ?>/delete/<?= $autorisation['id'] ?>">
                <input type="hidden" name="confirm" value="yes">

                <div class="d-flex justify-content-between">
                    <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                        <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line("formation_form_cancel") ?>
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash" aria-hidden="true"></i> <?= $this->lang->line("formation_autorisation_solo_delete_confirm_btn") ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->load->view('bs_footer'); ?>
