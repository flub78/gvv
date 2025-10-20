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
 * User Roles Management View
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
            <li class="breadcrumb-item active" aria-current="page"><?= $this->lang->line('authorization_users') ?></li>
        </ol>
    </nav>

    <h3><?= $title ?></h3>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><?= $this->lang->line('authorization_user_roles_list') ?></h5>
        </div>
        <div class="card-body">
            <table id="userRolesTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th><?= $this->lang->line('authorization_username') ?></th>
                        <th><?= $this->lang->line('authorization_email') ?></th>
                        <th><?= $this->lang->line('authorization_name') ?></th>
                        <th><?= $this->lang->line('authorization_section') ?></th>
                        <th><?= $this->lang->line('authorization_current_roles') ?></th>
                        <th><?= $this->lang->line('authorization_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if (!empty($user['mprenom']) || !empty($user['mnom'])): ?>
                                    <?= htmlspecialchars($user['mprenom']) ?> <?= htmlspecialchars($user['mnom']) ?>
                                <?php else: ?>
                                    <em>-</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($user['section_name'])): ?>
                                    <?= htmlspecialchars($user['section_name']) ?>
                                <?php else: ?>
                                    <em><?= $this->lang->line('authorization_no_section') ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($user['roles'])): ?>
                                    <?php foreach ($user['roles'] as $role): ?>
                                    <?php
                                        $color = '#0d6efd'; // default blue
                                        if ($role['scope'] === 'global') {
                                            $color = '#a5d8ff'; // lighter blue
                                        } else if ($role['section_color']) {
                                            $color = $role['section_color'];
                                        }
                                    ?>
                                    <span class="badge me-1" style="background-color: <?= htmlspecialchars($color) ?>; color: black;" data-role-id="<?= $role['types_roles_id'] ?>">
                                            <?= htmlspecialchars($role['role_name']) ?>
                                            <?php if ($role['scope'] === 'global'): ?>
                                                <i class="fas fa-globe" title="Global"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <em><?= $this->lang->line('authorization_no_roles') ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-manage-roles"
                                        data-user-id="<?= $user['id'] ?>"
                                        data-username="<?= htmlspecialchars($user['username']) ?>"
                                        data-user-roles='<?= json_encode($user['roles']) ?>'>
                                    <i class="fas fa-cog"></i> Gérer les rôles
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manage Roles Modal -->
<div class="modal fade" id="manageRolesModal" tabindex="-1" aria-labelledby="manageRolesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageRolesModalLabel">Gérer les rôles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Utilisateur: <strong id="manageUsername"></strong></p>
                <input type="hidden" id="manageUserId">

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Rôle</th>
                                <th>Toutes sections</th>
                                <?php foreach ($sections as $section): ?>
                                    <?php if ($section['id'] != 0): ?>
                                        <th><?= htmlspecialchars($section['nom']) ?></th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Group roles: show global roles first, then section roles
                            $global_roles = array_filter($all_roles, function($r) { return $r['scope'] === 'global'; });
                            $section_roles = array_filter($all_roles, function($r) { return $r['scope'] === 'section'; });
                            $ordered_roles = array_merge($global_roles, $section_roles);
                            ?>

                            <?php foreach ($ordered_roles as $role): ?>
                            <tr data-role-id="<?= $role['id'] ?>" data-role-scope="<?= $role['scope'] ?>">
                                <td><?php if ($role['scope'] === 'global'): ?><strong><?php endif; ?><?= htmlspecialchars($role['nom']) ?><?php if ($role['scope'] === 'global'): ?></strong><?php endif; ?></td>
                                <td class="text-center">
                                    <!-- "Toutes sections" checkbox for all roles -->
                                    <input type="checkbox" class="form-check-input role-checkbox role-checkbox-all"
                                           data-role-id="<?= $role['id'] ?>"
                                           data-role-scope="<?= $role['scope'] ?>">
                                </td>
                                <?php foreach ($sections as $section): ?>
                                    <?php if ($section['id'] != 0): ?>
                                        <td class="text-center">
                                            <?php if ($role['scope'] === 'section'): ?>
                                                <!-- Individual section checkboxes for section roles only -->
                                                <input type="checkbox" class="form-check-input role-checkbox role-checkbox-section"
                                                       data-role-id="<?= $role['id'] ?>"
                                                       data-section-id="<?= $section['id'] ?>"
                                                       data-role-scope="section">
                                            <?php else: ?>
                                                <!-- No section checkboxes for global roles -->
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function() {
    var allSections = <?= json_encode(array_map(function($s) { return $s['id']; }, array_filter($sections, function($s) { return $s['id'] != 0; }))) ?>;

    try {
        if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#userRolesTable')) {
            $('#userRolesTable').DataTable({
                "pageLength": 25,
                "order": [[0, "asc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json"
                }
            });
        }
    } catch (error) {
        console.error('DataTable initialization error:', error);
    }

    $(document).on('click', '.btn-manage-roles', function(e) {
        e.preventDefault();
        const userId = $(this).data('user-id');
        const username = $(this).data('username');
        const userRoles = $(this).data('user-roles') || [];

        $('#manageUserId').val(userId);
        $('#manageUsername').text(username);

        updateModalCheckboxes(userRoles);

        new bootstrap.Modal(document.getElementById('manageRolesModal')).show();
    });

    $(document).on('change', '.role-checkbox', function() {
        const $checkbox = $(this);
        const userId = $('#manageUserId').val();
        const roleId = $checkbox.data('role-id');
        const isChecked = $checkbox.is(':checked');
        const action = isChecked ? 'grant' : 'revoke';

        let sectionId;
        if ($checkbox.hasClass('role-checkbox-all')) {
            sectionId = -1; // All sections
        } else {
            sectionId = $checkbox.data('section-id');
        }

        handleRoleChange(userId, roleId, sectionId, action, $checkbox);
    });

    function handleRoleChange(userId, roleId, sectionId, action, $checkbox) {
        console.log("--- handleRoleChange ---");
        console.log("userId:", userId, "roleId:", roleId, "sectionId:", sectionId, "action:", action);
        $checkbox.prop('disabled', true);

        $.ajax({
            url: '<?= site_url('authorization/edit_user_roles') ?>',
            type: 'POST',
            data: {
                user_id: userId,
                types_roles_id: roleId,
                section_id: sectionId,
                action: action,
                '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
            },
            dataType: 'json',
            success: function(response) {
                console.log("AJAX success. Response:", response);
                if (response.success) {
                    console.log("Response success is true. Calling updateUserRoleBadges and updateModalCheckboxes.");
                    updateUserRoleBadges(userId, response.roles);
                    updateModalCheckboxes(response.roles);
                } else {
                    console.log("Response success is false. Message:", response.message);
                    alert('Erreur: ' + response.message);
                    $checkbox.prop('checked', !$checkbox.is(':checked'));
                }
            },
            error: function(xhr, status, error) {
                console.log("AJAX error. Status:", status, "Error:", error);
                console.log("Response text:", xhr.responseText);
                alert('Erreur lors de la modification du rôle');
                $checkbox.prop('checked', !$checkbox.is(':checked'));
            },
            complete: function() {
                console.log("AJAX complete.");
                $checkbox.prop('disabled', false);
            }
        });
    }

    function updateUserRoleBadges(userId, roles) {
        const $row = $('#userRolesTable').find('button[data-user-id="' + userId + '"]').closest('tr');
        const $badgeCell = $row.find('td').eq(4);

        let badgesHtml = '';
        if (roles.length > 0) {
            roles.forEach(function(role) {
                let color = '#0d6efd'; // default blue
                if (role.scope === 'global') {
                    color = '#a5d8ff'; // lighter blue
                } else if (role.section_color) {
                    color = role.section_color;
                }
                badgesHtml += '<span class="badge me-1" style="background-color: ' + color + '; color: black;" data-role-id="' + role.types_roles_id + '">';
                badgesHtml += role.role_name;
                if (role.scope === 'global') {
                    badgesHtml += ' <i class="fas fa-globe" title="Global"></i>';
                }
                badgesHtml += '</span>';
            });
        } else {
            badgesHtml = '<em><?= $this->lang->line('authorization_no_roles') ?></em>';
        }
        $badgeCell.html(badgesHtml);

        $row.find('.btn-manage-roles').data('user-roles', roles);
    }

    function updateModalCheckboxes(userRoles) {
        console.log("--- updateModalCheckboxes ---");
        console.log("Received userRoles:", userRoles);

        // First, reset all checkboxes
        $('.role-checkbox').prop('checked', false);
        console.log("All checkboxes reset.");

        // Create a map of user's roles for easier lookup
        const userRolesMap = {};
        userRoles.forEach(function(role) {
            if (!userRolesMap[role.types_roles_id]) {
                userRolesMap[role.types_roles_id] = new Set();
            }
            userRolesMap[role.types_roles_id].add(role.section_id.toString());
        });
        console.log("userRolesMap created:", userRolesMap);

        // Iterate over each role in the modal
        $('.role-checkbox-all').each(function() {
            const $allCheckbox = $(this);
            const roleId = $allCheckbox.data('role-id').toString();
            const roleScope = $allCheckbox.data('role-scope');
            console.log("Processing roleId:", roleId, "with scope:", roleScope);

            if (roleScope === 'global') {
                if (userRolesMap[roleId] && userRolesMap[roleId].has('0')) {
                    $allCheckbox.prop('checked', true);
                    console.log("Checked global role", roleId);
                }
            } else {
                const $sectionCheckboxes = $('.role-checkbox-section[data-role-id="' + roleId + '"]');
                let checkedCount = 0;

                $sectionCheckboxes.each(function() {
                    const $sectionCheckbox = $(this);
                    const sectionId = $sectionCheckbox.data('section-id').toString();
                    if (userRolesMap[roleId] && userRolesMap[roleId].has(sectionId)) {
                        $sectionCheckbox.prop('checked', true);
                        checkedCount++;
                        console.log("Checked section", sectionId, "for role", roleId);
                    }
                });

                console.log("Role", roleId, "has", checkedCount, "of", $sectionCheckboxes.length, "sections checked.");
                if (checkedCount === $sectionCheckboxes.length && $sectionCheckboxes.length > 0) {
                    $allCheckbox.prop('checked', true);
                    console.log("Checked 'Toutes sections' for role", roleId);
                }
            }
        });
        console.log("--- updateModalCheckboxes finished ---");
    }
});
</script>


