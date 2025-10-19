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
            <table id="userRolesTable" class="table table-striped table-bordered datatable">
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
                                <button class="btn btn-sm btn-success btn-grant-role"
                                        data-user-id="<?= $user['id'] ?>"
                                        data-section-id="<?= $user['section_id'] ?>"
                                        data-username="<?= htmlspecialchars($user['username']) ?>">
                                    <i class="fas fa-plus"></i> <?= $this->lang->line('authorization_grant_role') ?>
                                </button>

                                <?php if (!empty($user['roles'])): ?>
                                    <button class="btn btn-sm btn-danger btn-revoke-role"
                                            data-user-id="<?= $user['id'] ?>"
                                            data-section-id="<?= $user['section_id'] ?>"
                                            data-username="<?= htmlspecialchars($user['username']) ?>">
                                        <i class="fas fa-minus"></i> <?= $this->lang->line('authorization_revoke_role') ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Grant Role Modal -->
<div class="modal fade" id="grantRoleModal" tabindex="-1" aria-labelledby="grantRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="grantRoleModalLabel"><?= $this->lang->line('authorization_grant_role') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?= $this->lang->line('authorization_grant_role_for') ?>: <strong id="grantUsername"></strong></p>

                <div class="mb-3">
                    <label for="grantRoleSelect" class="form-label"><?= $this->lang->line('authorization_select_role') ?></label>
                    <select class="form-select" id="grantRoleSelect">
                        <option value="">-- <?= $this->lang->line('authorization_select_role') ?> --</option>
                        <?php foreach ($all_roles as $role): ?>
                            <option value="<?= $role['id'] ?>" data-scope="<?= $role['scope'] ?>">
                                <?= htmlspecialchars($role['nom']) ?>
                                <?php if ($role['scope'] === 'global'): ?>
                                    (<?= $this->lang->line('authorization_global') ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3" id="grantSectionSelect">
                    <label for="grantSection" class="form-label"><?= $this->lang->line('authorization_select_section') ?></label>
                    <select class="form-select" id="grantSection">
                        <option value="">-- <?= $this->lang->line('authorization_select_section') ?> --</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= $section['id'] ?>"><?= htmlspecialchars($section['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="grantNotes" class="form-label"><?= $this->lang->line('authorization_notes') ?></label>
                    <textarea class="form-control" id="grantNotes" rows="3"></textarea>
                </div>

                <input type="hidden" id="grantUserId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $this->lang->line('authorization_cancel') ?></button>
                <button type="button" class="btn btn-primary" id="confirmGrantRole"><?= $this->lang->line('authorization_grant') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Revoke Role Modal -->
<div class="modal fade" id="revokeRoleModal" tabindex="-1" aria-labelledby="revokeRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="revokeRoleModalLabel"><?= $this->lang->line('authorization_revoke_role') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?= $this->lang->line('authorization_revoke_role_for') ?>: <strong id="revokeUsername"></strong></p>

                <div class="mb-3">
                    <label for="revokeRoleSelect" class="form-label"><?= $this->lang->line('authorization_select_role_to_revoke') ?></label>
                    <select class="form-select" id="revokeRoleSelect">
                        <option value="">-- <?= $this->lang->line('authorization_select_role') ?> --</option>
                    </select>
                </div>

                <input type="hidden" id="revokeUserId">
                <input type="hidden" id="revokeSectionId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $this->lang->line('authorization_cancel') ?></button>
                <button type="button" class="btn btn-danger" id="confirmRevokeRole"><?= $this->lang->line('authorization_revoke') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#userRolesTable').DataTable({
        "pageLength": 25,
        "order": [[0, "asc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json"
        }
    });

    // Grant Role Button
    $('.btn-grant-role').on('click', function() {
        const userId = $(this).data('user-id');
        const sectionId = $(this).data('section-id');
        const username = $(this).data('username');

        $('#grantUserId').val(userId);
        $('#grantUsername').text(username);
        $('#grantSection').val(sectionId);
        $('#grantRoleSelect').val('');
        $('#grantNotes').val('');

        $('#grantRoleModal').modal('show');
    });

    // Handle role scope change
    $('#grantRoleSelect').on('change', function() {
        const scope = $(this).find(':selected').data('scope');
        if (scope === 'global') {
            $('#grantSectionSelect').hide();
        } else {
            $('#grantSectionSelect').show();
        }
    });

    // Confirm Grant Role
    $('#confirmGrantRole').on('click', function() {
        const userId = $('#grantUserId').val();
        const roleId = $('#grantRoleSelect').val();
        const sectionId = $('#grantSection').val();
        const notes = $('#grantNotes').val();

        if (!roleId) {
            alert('<?= $this->lang->line('authorization_please_select_role') ?>');
            return;
        }

        $.ajax({
            url: '<?= site_url('authorization/edit_user_roles') ?>',
            method: 'POST',
            data: {
                user_id: userId,
                types_roles_id: roleId,
                section_id: sectionId || null,
                action: 'grant',
                notes: notes
            },
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

    // Revoke Role Button
    $('.btn-revoke-role').on('click', function() {
        const userId = $(this).data('user-id');
        const sectionId = $(this).data('section-id');
        const username = $(this).data('username');

        $('#revokeUserId').val(userId);
        $('#revokeSectionId').val(sectionId);
        $('#revokeUsername').text(username);

        // Populate role dropdown with user's current roles
        const roles = $(this).closest('tr').find('.badge');
        const revokeSelect = $('#revokeRoleSelect');
        revokeSelect.empty();
        revokeSelect.append('<option value="">-- <?= $this->lang->line('authorization_select_role') ?> --</option>');

        roles.each(function() {
            const roleId = $(this).data('role-id');
            const roleName = $(this).text().trim();
            revokeSelect.append(`<option value="${roleId}">${roleName}</option>`);
        });

        $('#revokeRoleModal').modal('show');
    });

    // Confirm Revoke Role
    $('#confirmRevokeRole').on('click', function() {
        const userId = $('#revokeUserId').val();
        const roleId = $('#revokeRoleSelect').val();
        const sectionId = $('#revokeSectionId').val();

        if (!roleId) {
            alert('<?= $this->lang->line('authorization_please_select_role') ?>');
            return;
        }

        $.ajax({
            url: '<?= site_url('authorization/edit_user_roles') ?>',
            method: 'POST',
            data: {
                user_id: userId,
                types_roles_id: roleId,
                section_id: sectionId || null,
                action: 'revoke'
            },
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


