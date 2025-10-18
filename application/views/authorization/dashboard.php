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
 * Authorization Dashboard View
 *
 * @package views
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid">
    <h3><?= $title ?></h3>

    <!-- System Status Card -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?= $this->lang->line('authorization_system_status') ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p>
                                <strong><?= $this->lang->line('authorization_current_system') ?>:</strong>
                                <?php if ($new_system_enabled): ?>
                                    <span class="badge bg-success"><?= $this->lang->line('authorization_new_system') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><?= $this->lang->line('authorization_legacy_system') ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-3">
                            <p>
                                <strong><?= $this->lang->line('authorization_total_roles') ?>:</strong>
                                <span class="badge bg-info"><?= $total_roles ?></span>
                            </p>
                        </div>
                        <div class="col-md-3">
                            <p>
                                <strong><?= $this->lang->line('authorization_total_users') ?>:</strong>
                                <span class="badge bg-info"><?= $total_users ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                    <h5><?= $this->lang->line('authorization_manage_users') ?></h5>
                    <p class="text-muted"><?= $this->lang->line('authorization_manage_users_desc') ?></p>
                    <a href="<?= site_url('authorization/user_roles') ?>" class="btn btn-primary">
                        <?= $this->lang->line('authorization_manage') ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-shield-alt fa-3x mb-3 text-success"></i>
                    <h5><?= $this->lang->line('authorization_manage_roles') ?></h5>
                    <p class="text-muted"><?= $this->lang->line('authorization_manage_roles_desc') ?></p>
                    <a href="<?= site_url('authorization/roles') ?>" class="btn btn-success">
                        <?= $this->lang->line('authorization_view') ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-key fa-3x mb-3 text-warning"></i>
                    <h5><?= $this->lang->line('authorization_manage_permissions') ?></h5>
                    <p class="text-muted"><?= $this->lang->line('authorization_manage_permissions_desc') ?></p>
                    <a href="<?= site_url('authorization/role_permissions') ?>" class="btn btn-warning">
                        <?= $this->lang->line('authorization_manage') ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-history fa-3x mb-3 text-info"></i>
                    <h5><?= $this->lang->line('authorization_view_audit') ?></h5>
                    <p class="text-muted"><?= $this->lang->line('authorization_view_audit_desc') ?></p>
                    <a href="<?= site_url('authorization/audit_log') ?>" class="btn btn-info">
                        <?= $this->lang->line('authorization_view') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><?= $this->lang->line('authorization_recent_changes') ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_audits)): ?>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th><?= $this->lang->line('authorization_audit_date') ?></th>
                                    <th><?= $this->lang->line('authorization_audit_action') ?></th>
                                    <th><?= $this->lang->line('authorization_audit_user') ?></th>
                                    <th><?= $this->lang->line('authorization_audit_details') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_audits, 0, 10) as $audit): ?>
                                    <tr>
                                        <td><?= date('Y-m-d H:i', strtotime($audit['created_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?= $audit['action_type'] ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($audit['actor_user_id'])): ?>
                                                User #<?= $audit['actor_user_id'] ?>
                                            <?php else: ?>
                                                <em>System</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($audit['controller'])): ?>
                                                <?= htmlspecialchars($audit['controller']) ?>
                                                <?php if (!empty($audit['action'])): ?>
                                                    / <?= htmlspecialchars($audit['action']) ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?= htmlspecialchars(substr($audit['details'], 0, 50)) ?>...
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-end mt-3">
                            <a href="<?= site_url('authorization/audit_log') ?>" class="btn btn-sm btn-secondary">
                                <?= $this->lang->line('authorization_view_all') ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted"><?= $this->lang->line('authorization_no_recent_activity') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->load->view('bs_footer');
?>
