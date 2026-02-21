<!-- VIEW: application/views/authorization/bs_roles.php -->
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
 * Roles List View
 *
 * @package views
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid">
    <h3><?= $title ?></h3>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>



    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><?= $this->lang->line('authorization_available_roles') ?></h5>
            <p><?= $this->lang->line('authorization_roles_defined_by_migration') ?></p>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered datatable">
                <thead>
                    <tr>
                        <th><?= $this->lang->line('authorization_role_name') ?></th>
                        <th><?= $this->lang->line('authorization_role_description') ?></th>
                        <th><?= $this->lang->line('authorization_role_scope') ?></th>
                        <th><?= $this->lang->line('authorization_data_rules') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars(!empty($role['translation_key']) ? $this->lang->line($role['translation_key']) : $role['nom']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($role['nom']) ?></small>
                            </td>
                            <?php
                                $desc_key = ($role['translation_key'] ?? '') . '_desc';
                                $desc = $this->lang->line($desc_key);
                            ?>
                            <td><?= htmlspecialchars($desc !== FALSE ? $desc : $role['description']) ?></td>
                            <td>
                                <?php if ($role['scope'] === 'global'): ?>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-globe"></i> <?= $this->lang->line('authorization_global') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info">
                                        <i class="fas fa-building"></i> <?= $this->lang->line('authorization_section') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= site_url('authorization/data_access_rules/' . $role['id']) ?>"
                                   class="btn btn-sm btn-info"
                                   title="<?= $this->lang->line('authorization_data_rules') ?>">
                                    <i class="fas fa-database"></i> <?= $this->lang->line('authorization_data_rules') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="<?= site_url('authorization') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?= $this->lang->line('authorization_back_to_dashboard') ?>
        </a>
    </div>
</div>

