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
 * Role Permissions Management View
 *
 * @package views
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= site_url('authorization') ?>"><?= $this->lang->line('authorization_title') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= site_url('authorization/roles') ?>"><?= $this->lang->line('authorization_roles') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $this->lang->line('authorization_permissions') ?></li>
        </ol>
    </nav>

    <h3><?= $title ?></h3>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Role Information Card -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-user-shield"></i> <?= htmlspecialchars($role['nom']) ?>
                <?php if ($role['scope'] === 'global'): ?>
                    <span class="badge bg-light text-dark ms-2">
                        <i class="fas fa-globe"></i> <?= $this->lang->line('authorization_scope_global') ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-light text-dark ms-2">
                        <i class="fas fa-building"></i> <?= $this->lang->line('authorization_scope_section') ?>
                    </span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <p class="mb-0"><?= htmlspecialchars($role['description']) ?></p>
        </div>
    </div>

    <!-- Add Permission Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-plus-circle"></i> <?= $this->lang->line('authorization_add_permission') ?></h5>
        </div>
        <div class="card-body">
            <form id="addPermissionForm">
                <div class="row">
                    <div class="col-md-3">
                        <label for="controller" class="form-label"><?= $this->lang->line('authorization_controller') ?></label>
                        <select class="form-select" id="controller" required>
                            <option value="">-- <?= $this->lang->line('authorization_select') ?> --</option>
                            <?php foreach ($available_controllers as $ctrl): ?>
                                <option value="<?= htmlspecialchars($ctrl) ?>"><?= htmlspecialchars($ctrl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="action" class="form-label">
                            <?= $this->lang->line('authorization_action') ?>
                            <small class="text-muted">(<?= $this->lang->line('authorization_optional') ?>)</small>
                        </label>
                        <input type="text" class="form-control" id="action" placeholder="<?= $this->lang->line('authorization_wildcard_all_actions') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="permission_type" class="form-label"><?= $this->lang->line('authorization_permission_type') ?></label>
                        <select class="form-select" id="permission_type" required>
                            <option value="view"><?= $this->lang->line('authorization_permission_view') ?></option>
                            <option value="create"><?= $this->lang->line('authorization_permission_create') ?></option>
                            <option value="edit"><?= $this->lang->line('authorization_permission_edit') ?></option>
                            <option value="delete"><?= $this->lang->line('authorization_permission_delete') ?></option>
                            <option value="admin"><?= $this->lang->line('authorization_permission_admin') ?></option>
                        </select>
                    </div>
                    <div class="col-md-3" id="sectionSelectDiv" <?= $role['scope'] === 'global' ? 'style="display:none;"' : '' ?>>
                        <label for="section_id" class="form-label"><?= $this->lang->line('authorization_section') ?></label>
                        <select class="form-select" id="section_id">
                            <option value="">-- <?= $this->lang->line('authorization_global') ?> --</option>
                            <?php
                            $sections = $this->db->get('sections')->result_array();
                            foreach ($sections as $section): ?>
                                <option value="<?= $section['id'] ?>"><?= htmlspecialchars($section['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> <?= $this->lang->line('authorization_add') ?>
                        </button>
                    </div>
                </div>
                <input type="hidden" id="types_roles_id" value="<?= $role['id'] ?>">
            </form>
        </div>
    </div>

    <!-- Permissions List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> <?= $this->lang->line('authorization_current_permissions') ?></h5>
        </div>
        <div class="card-body">
            <?php if (empty($permissions)): ?>
                <p class="text-muted"><em><?= $this->lang->line('authorization_no_permissions') ?></em></p>
            <?php else: ?>
                <table id="permissionsTable" class="table table-striped table-bordered datatable">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('authorization_controller') ?></th>
                            <th><?= $this->lang->line('authorization_action') ?></th>
                            <th><?= $this->lang->line('authorization_permission_type') ?></th>
                            <th><?= $this->lang->line('authorization_section') ?></th>
                            <th><?= $this->lang->line('authorization_created') ?></th>
                            <th><?= $this->lang->line('authorization_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions as $perm): ?>
                            <tr>
                                <td><?= htmlspecialchars($perm['controller']) ?></td>
                                <td>
                                    <?php if ($perm['action'] === NULL || $perm['action'] === ''): ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-asterisk"></i> <?= $this->lang->line('authorization_all_actions') ?>
                                        </span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($perm['action']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $type_badges = array(
                                        'view' => 'bg-info',
                                        'create' => 'bg-success',
                                        'edit' => 'bg-primary',
                                        'delete' => 'bg-danger',
                                        'admin' => 'bg-dark'
                                    );
                                    $badge_class = $type_badges[$perm['permission_type']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badge_class ?>">
                                        <?= htmlspecialchars($perm['permission_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($perm['section_id'] == 0): ?>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-globe"></i> <?= $this->lang->line('authorization_global') ?>
                                        </span>
                                    <?php else: ?>
                                        <?php
                                        $section = $this->db->where('id', $perm['section_id'])->get('sections')->row_array();
                                        echo $section ? htmlspecialchars($section['nom']) : 'Section #' . $perm['section_id'];
                                        ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($perm['created'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger btn-remove-permission" data-permission-id="<?= $perm['id'] ?>">
                                        <i class="fas fa-trash"></i> <?= $this->lang->line('authorization_remove') ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3">
        <a href="<?= site_url('authorization/roles') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?= $this->lang->line('authorization_back_to_roles') ?>
        </a>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    <?php if (!empty($permissions)): ?>
    $('#permissionsTable').DataTable({
        "pageLength": 25,
        "order": [[0, "asc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json"
        }
    });
    <?php endif; ?>

    // Add Permission Form Submit
    $('#addPermissionForm').on('submit', function(e) {
        e.preventDefault();

        const data = {
            types_roles_id: $('#types_roles_id').val(),
            controller: $('#controller').val(),
            action: $('#action').val() || 'null',
            section_id: $('#section_id').val() || 'null',
            permission_type: $('#permission_type').val()
        };

        $.ajax({
            url: '<?= site_url('authorization/add_permission') ?>',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('<?= $this->lang->line('authorization_error_occurred') ?>');
            }
        });
    });

    // Remove Permission Button
    $('.btn-remove-permission').on('click', function() {
        if (!confirm('<?= $this->lang->line('authorization_confirm_delete') ?>')) {
            return;
        }

        const permissionId = $(this).data('permission-id');

        $.ajax({
            url: '<?= site_url('authorization/remove_permission') ?>',
            method: 'POST',
            data: { permission_id: permissionId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('<?= $this->lang->line('authorization_error_occurred') ?>');
            }
        });
    });
});
</script>

<?php
$this->load->view('bs_footer');
?>
