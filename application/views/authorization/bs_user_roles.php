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
                                        <span class="badge bg-primary me-1" data-role-id="<?= $role['types_roles_id'] ?>">
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
    // Store sections list globally for JavaScript access
    var allSections = <?= json_encode(array_map(function($s) { return $s['id']; }, array_filter($sections, function($s) { return $s['id'] != 0; }))) ?>;
    console.log('Available sections:', allSections);

    // Flag to prevent recursive checkbox updates
    var updatingToutesSections = false;

    // Initialize DataTable with error handling
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

    // Manage Roles Button
    $(document).on('click', '.btn-manage-roles', function(e) {
        e.preventDefault();
        const userId = $(this).data('user-id');
        const username = $(this).data('username');
        const userRoles = $(this).data('user-roles') || [];

        $('#manageUserId').val(userId);
        $('#manageUsername').text(username);

        // Update checkboxes based on user's current roles
        updateModalCheckboxes(userRoles);

        // Show modal
        new bootstrap.Modal(document.getElementById('manageRolesModal')).show();
    });

    // Handle checkbox toggle
    $(document).on('change', '.role-checkbox', function() {
        // Skip if we're programmatically updating checkboxes
        if (updatingToutesSections) {
            console.log('Skipping change event (updatingToutesSections=true)');
            return;
        }

        const $checkbox = $(this);
        const userId = $('#manageUserId').val();
        const roleId = $checkbox.data('role-id');
        const roleScope = $checkbox.data('role-scope');
        const isAllSections = $checkbox.hasClass('role-checkbox-all');
        const isSection = $checkbox.hasClass('role-checkbox-section');
        const isChecked = $checkbox.is(':checked');

        console.log('Role change - userId:', userId, 'roleId:', roleId, 'roleScope:', roleScope, 'isAllSections:', isAllSections, 'isSection:', isSection, 'action:', isChecked ? 'grant' : 'revoke');
        console.log('Checkbox classes:', $checkbox.attr('class'));
        console.log('Checkbox HTML:', $checkbox[0].outerHTML);

        // Validate required parameters
        if (!userId || !roleId) {
            console.error('Missing required parameters:', {userId, roleId});
            alert('Erreur: Paramètres manquants');
            $checkbox.prop('checked', !isChecked);
            return;
        }

        if (isAllSections) {
            // "Toutes sections" checkbox clicked
            if (roleScope === 'global') {
                // Global role: grant/revoke with section_id=0
                handleRoleChange(userId, roleId, 0, isChecked ? 'grant' : 'revoke', $checkbox);
            } else {
                // Section role: grant/revoke for ALL sections
                handleAllSectionsToggle(userId, roleId, isChecked, $checkbox);
            }
        } else {
            // Individual section checkbox clicked
            const sectionId = $checkbox.data('section-id');
            if (sectionId === undefined) {
                console.error('Missing section-id');
                alert('Erreur: section-id manquant');
                $checkbox.prop('checked', !isChecked);
                return;
            }
            handleRoleChange(userId, roleId, sectionId, isChecked ? 'grant' : 'revoke', $checkbox);
        }
    });

    // Handle role change for a single section
    function handleRoleChange(userId, roleId, sectionId, action, $checkbox) {
        $checkbox.prop('disabled', true);

        $.ajax({
            url: '<?= site_url('authorization/edit_user_roles') ?>',
            type: 'POST',
            data: {
                user_id: userId,
                types_roles_id: roleId,
                section_id: sectionId,
                action: action
            },
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    // Show success feedback
                    const $row = $checkbox.closest('tr');
                    $row.addClass('table-success');
                    setTimeout(function() {
                        $row.removeClass('table-success');
                    }, 1000);

                    // Update the main table's role badges (but skip modal update)
                    updateUserRoleBadges(userId, true); // Pass true to skip modal update

                    // Immediately update "Toutes sections" checkbox state
                    // Check if all sections are now checked for this role
                    if (!$checkbox.hasClass('role-checkbox-all')) {
                        updateToutesSectionsCheckbox(roleId);
                    }
                } else {
                    alert('Erreur: ' + response.message);
                    $checkbox.prop('checked', !$checkbox.is(':checked'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr, status, error, responseText: xhr.responseText});
                alert('Erreur lors de la modification du rôle');
                $checkbox.prop('checked', !$checkbox.is(':checked'));
            },
            complete: function() {
                $checkbox.prop('disabled', false);
            }
        });
    }

    // Update "Toutes sections" checkbox based on individual section checkboxes
    function updateToutesSectionsCheckbox(roleId) {
        const $allSectionsCheckbox = $('.role-checkbox-all[data-role-id="' + roleId + '"][data-role-scope="section"]');

        if ($allSectionsCheckbox.length === 0) {
            // This is a global role, no need to update
            console.log('updateToutesSectionsCheckbox: No checkbox found for role', roleId, '(might be global role)');
            return;
        }

        const $sectionCheckboxes = $('.role-checkbox-section[data-role-id="' + roleId + '"]');
        const totalSections = $sectionCheckboxes.length;
        const checkedSections = $sectionCheckboxes.filter(':checked').length;

        console.log('updateToutesSectionsCheckbox: role', roleId, 'has', checkedSections, 'of', totalSections, 'sections checked');

        const shouldBeChecked = (checkedSections === totalSections && totalSections > 0);
        const currentlyChecked = $allSectionsCheckbox.is(':checked');

        // Only update if state needs to change (to avoid triggering unnecessary change events)
        if (shouldBeChecked !== currentlyChecked) {
            console.log('updateToutesSectionsCheckbox: Updating checkbox from', currentlyChecked, 'to', shouldBeChecked);
            // Set flag to prevent change handler from firing
            updatingToutesSections = true;
            $allSectionsCheckbox.prop('checked', shouldBeChecked);
            // Clear flag after a short delay
            setTimeout(function() {
                updatingToutesSections = false;
            }, 100);
        }
    }

    // Handle "Toutes sections" toggle for section-specific roles
    function handleAllSectionsToggle(userId, roleId, grant, $checkbox) {
        console.log('handleAllSectionsToggle START: grant=' + grant + ', role=' + roleId + ', user=' + userId + ', sections=' + JSON.stringify(allSections));

        $checkbox.prop('disabled', true);
        const action = grant ? 'grant' : 'revoke';
        let completedCount = 0;
        let errorCount = 0;

        // Process each section sequentially
        function processNextSection(index) {
            if (index >= allSections.length) {
                // All sections processed
                console.log('handleAllSectionsToggle COMPLETE: completed=' + completedCount + ', errors=' + errorCount);
                $checkbox.prop('disabled', false);
                if (errorCount > 0) {
                    alert('Erreur: ' + errorCount + ' sections ont échoué');
                }
                // Update badges and checkboxes
                updateUserRoleBadges(userId);
                return;
            }

            const sectionId = allSections[index];
            console.log('Processing section ' + (index + 1) + '/' + allSections.length + ': sectionId=' + sectionId);

            $.ajax({
                url: '<?= site_url('authorization/edit_user_roles') ?>',
                type: 'POST',
                data: {
                    user_id: userId,
                    types_roles_id: roleId,
                    section_id: sectionId,
                    action: action
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Section ' + sectionId + ' response:', response);
                    if (response.success) {
                        completedCount++;
                    } else {
                        errorCount++;
                        console.error('Failed for section', sectionId, ':', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    errorCount++;
                    console.error('AJAX error for section', sectionId, ':', error);
                },
                complete: function() {
                    // Process next section
                    processNextSection(index + 1);
                }
            });
        }

        // Start processing
        processNextSection(0);
    }

    // Update the role badges for a user in the main table and modal
    function updateUserRoleBadges(userId, skipModalUpdate) {
        const $row = $('#userRolesTable').find('button[data-user-id="' + userId + '"]').closest('tr');
        const $badgeCell = $row.find('td').eq(4); // 5th column (roles)

        // Fetch updated roles
        $.ajax({
            url: '<?= site_url('authorization/get_user_roles') ?>',
            type: 'GET',
            data: { user_id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.roles) {
                    // Update badges in main table
                    let badgesHtml = '';
                    if (response.roles.length > 0) {
                        response.roles.forEach(function(role) {
                            badgesHtml += '<span class="badge bg-primary me-1" data-role-id="' + role.types_roles_id + '">';
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

                    // Update button data
                    $row.find('.btn-manage-roles').data('user-roles', response.roles);

                    // If modal is open for this user, update checkboxes in modal (unless skipped)
                    if (!skipModalUpdate && $('#manageUserId').val() == userId) {
                        updateModalCheckboxes(response.roles);
                    }
                }
            }
        });
    }

    // Update checkboxes in the modal based on user's current roles
    function updateModalCheckboxes(userRoles) {
        console.log('updateModalCheckboxes called with roles:', userRoles);

        // Reset all checkboxes
        $('.role-checkbox').prop('checked', false);

        // Group roles by role_id to check if ALL sections are assigned
        const rolesBySectionCount = {};

        userRoles.forEach(function(role) {
            const roleId = role.types_roles_id;

            if (role.section_id == 0 || role.section_id === '0') {
                // Global role (section_id=0)
                $('.role-checkbox-all[data-role-id="' + roleId + '"]').prop('checked', true);
            } else {
                // Section-specific role
                // Check the individual section checkbox
                $('.role-checkbox-section[data-role-id="' + roleId + '"][data-section-id="' + role.section_id + '"]')
                    .prop('checked', true);

                // Count how many sections this role has
                if (!rolesBySectionCount[roleId]) {
                    rolesBySectionCount[roleId] = 0;
                }
                rolesBySectionCount[roleId]++;
            }
        });

        // For section-specific roles, check "Toutes sections" if ALL sections are assigned
        Object.keys(rolesBySectionCount).forEach(function(roleId) {
            const count = rolesBySectionCount[roleId];
            console.log('Role', roleId, 'has', count, 'sections, total sections:', allSections.length);

            if (count === allSections.length) {
                // All sections have this role, check "Toutes sections"
                $('.role-checkbox-all[data-role-id="' + roleId + '"][data-role-scope="section"]').prop('checked', true);
                console.log('Checking "Toutes sections" for role', roleId);
            }
        });
    }
});
</script>


