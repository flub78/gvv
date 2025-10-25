<!-- VIEW: application/views/authorization/bs_dashboard.php -->
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

<style>
.sub-card {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 0.75rem;
    transition: all 0.2s ease;
    height: 100%;
}

.sub-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    border-color: #007bff;
}

.sub-card i {
    font-size: 1.5rem;
}

.sub-card .card-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0.5rem 0 0.25rem 0;
}

.sub-card .card-text {
    font-size: 0.75rem;
    margin-bottom: 0.5rem;
}

.sub-card .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
}
</style>

<div id="body" class="body container-fluid py-3">
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
        <div class="row g-2">
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="sub-card text-center">
                    <i class="fas fa-users text-primary"></i>
                    <div class="card-title"><?= $this->lang->line('authorization_manage_users') ?></div>
                    <div class="card-text text-muted"><?= $this->lang->line('authorization_manage_users_desc') ?></div>
                    <a href="<?= site_url('authorization/user_roles') ?>" class="btn btn-primary btn-sm">
                        <?= $this->lang->line('authorization_manage') ?>
                    </a>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="sub-card text-center">
                    <i class="fas fa-shield-alt text-success"></i>
                    <div class="card-title"><?= $this->lang->line('authorization_manage_roles') ?></div>
                    <div class="card-text text-muted"><?= $this->lang->line('authorization_manage_roles_desc') ?></div>
                    <a href="<?= site_url('authorization/roles') ?>" class="btn btn-success btn-sm">
                        <?= $this->lang->line('authorization_view') ?>
                    </a>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="sub-card text-center">
                    <i class="fas fa-database text-warning"></i>
                    <div class="card-title"><?= $this->lang->line('authorization_data_access_rules') ?></div>
                    <div class="card-text text-muted"><?= $this->lang->line('authorization_data_access_rules_desc') ?></div>
                    <a href="<?= site_url('authorization/data_access_rules') ?>" class="btn btn-warning btn-sm">
                        <?= $this->lang->line('authorization_manage') ?>
                    </a>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="sub-card text-center">
                    <i class="fas fa-history text-info"></i>
                    <div class="card-title"><?= $this->lang->line('authorization_view_audit') ?></div>
                    <div class="card-text text-muted"><?= $this->lang->line('authorization_view_audit_desc') ?></div>
                    <a href="<?= site_url('authorization/audit_log') ?>" class="btn btn-info btn-sm">
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


