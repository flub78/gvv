<!-- VIEW: application/views/membre/mes_autorisations.php -->
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
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Page Mes Autorisations - liste des rôles de l'utilisateur connecté par section
 *
 * @package views
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h2>
                <i class="fas fa-shield-alt text-secondary"></i>
                <?= htmlspecialchars($title) ?>
            </h2>
        </div>
    </div>

    <?php if (empty($roles_by_section)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <?= $this->lang->line('authorization_no_roles_assigned') ?>
    </div>
    <?php else: ?>

    <?php foreach ($roles_by_section as $section_name => $roles): ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-layer-group text-primary me-2"></i>
                <?= htmlspecialchars($section_name) ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= $this->lang->line('authorization_role_name') ?></th>
                        <th><?= $this->lang->line('authorization_role_description') ?></th>
                        <th><?= $this->lang->line('authorization_granted_at') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                    <?php
                        $tk = $role['translation_key'] ?? '';
                        $role_label = $tk ? $this->lang->line($tk) : $role['role_name'];
                        $desc_key = $tk . '_desc';
                        $desc = $tk ? $this->lang->line($desc_key) : FALSE;
                        if ($desc === FALSE) $desc = $role['description'] ?? '';
                    ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($role_label !== FALSE ? $role_label : $role['role_name']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($desc) ?></td>
                        <td><?= htmlspecialchars($role['granted_at'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>

    <div class="mt-3">
        <a href="<?= controller_url('welcome') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            <?= $this->lang->line('gvv_button_back') ?>
        </a>
    </div>

</div>
