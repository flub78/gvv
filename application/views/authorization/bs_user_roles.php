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
// Ensure we wait for both DOM and jQuery to be ready
function initializeUserRoles() {
    console.log('Initializing user roles functionality...');
    console.log('jQuery available:', typeof $ !== 'undefined');
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    console.log('Grant role buttons found:', $('.btn-grant-role').length);
    console.log('Revoke role buttons found:', $('.btn-revoke-role').length);
    
    // Initialize DataTable
    try {
        if ($.fn.DataTable) {
            $('#userRolesTable').DataTable({
                "pageLength": 25,
                "order": [[0, "asc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json"
                }
            });
            console.log('DataTable initialized successfully');
        } else {
            console.warn('DataTable not available');
        }
    } catch (error) {
        console.error('DataTable initialization error:', error);
    }

    // Grant Role Button - using event delegation for reliability
    $(document).off('click', '.btn-grant-role').on('click', '.btn-grant-role', function(e) {
        e.preventDefault();
        console.log('Grant role button clicked (event delegation)');
        
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const sectionId = $btn.data('section-id');
        const username = $btn.data('username');

        console.log('Grant role button data:', {userId, sectionId, username});

        // Set modal form values
        $('#grantUserId').val(userId);
        $('#grantUsername').text(username);
        $('#grantSection').val(sectionId);
        $('#grantRoleSelect').val('');
        $('#grantNotes').val('');

        // Verify values were set
        console.log('Modal form values set:');
        console.log('- grantUserId set to:', $('#grantUserId').val());
        console.log('- grantUsername set to:', $('#grantUsername').text());
        console.log('- grantSection set to:', $('#grantSection').val());

        // Try both Bootstrap methods
        try {
            if (typeof bootstrap !== 'undefined') {
                const grantModal = new bootstrap.Modal(document.getElementById('grantRoleModal'));
                grantModal.show();
                console.log('Modal opened with Bootstrap 5');
            } else if ($.fn.modal) {
                $('#grantRoleModal').modal('show');
                console.log('Modal opened with jQuery/Bootstrap 4');
            } else {
                console.error('No modal method available');
                alert('Modal functionality not available. Please refresh the page.');
            }
        } catch (error) {
            console.error('Modal error:', error);
            alert('Error opening modal: ' + error.message);
        }
    });

    // Handle role scope change
    $('#grantRoleSelect').off('change').on('change', function() {
        const scope = $(this).find(':selected').data('scope');
        console.log('Role scope changed:', scope);
        if (scope === 'global') {
            $('#grantSectionSelect').hide();
        } else {
            $('#grantSectionSelect').show();
        }
    });

    // Confirm Grant Role
    $('#confirmGrantRole').off('click').on('click', function() {
        console.log('Confirm grant role clicked');
        
        // Get values and log each one
        const userId = $('#grantUserId').val();
        const roleId = $('#grantRoleSelect').val();
        const sectionId = $('#grantSection').val();
        const notes = $('#grantNotes').val();

        console.log('Individual values:');
        console.log('- userId element exists:', $('#grantUserId').length > 0);
        console.log('- userId value:', userId);
        console.log('- roleId element exists:', $('#grantRoleSelect').length > 0);
        console.log('- roleId value:', roleId);
        console.log('- sectionId element exists:', $('#grantSection').length > 0);
        console.log('- sectionId value:', sectionId);
        console.log('- notes value:', notes);

        console.log('Grant role request data:', {userId, roleId, sectionId, notes});

        // Validate required fields
        if (!userId) {
            alert('Erreur: ID utilisateur manquant');
            console.error('Missing user_id');
            return;
        }

        if (!roleId) {
            alert(<?= json_encode($this->lang->line('authorization_please_select_role')) ?>);
            console.error('Missing types_roles_id');
            return;
        }

        // Disable button during request
        const $btn = $(this);
        $btn.prop('disabled', true).text('En cours...');

        const requestData = {
            user_id: String(userId || ''),
            types_roles_id: String(roleId || ''),
            section_id: sectionId === '' ? '' : String(sectionId || ''),
            action: 'grant',
            notes: String(notes || '')
        };

        console.log('Sending AJAX request with data:', requestData);

        // Alternative approach - try serializing the data manually
        const formData = new FormData();
        formData.append('user_id', userId || '');
        formData.append('types_roles_id', roleId || '');
        formData.append('section_id', sectionId || '');
        formData.append('action', 'grant');
        formData.append('notes', notes || '');

        console.log('FormData entries:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        $.ajax({
            url: '<?= site_url('authorization/edit_user_roles') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function(xhr) {
                console.log('AJAX beforeSend with FormData');
            },
            success: function(response) {
                console.log('Grant role response:', response);
                console.log('Response type:', typeof response);
                
                // Handle both string and object responses
                let parsedResponse = response;
                if (typeof response === 'string') {
                    try {
                        parsedResponse = JSON.parse(response);
                        console.log('Parsed response:', parsedResponse);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        alert('Erreur: Réponse serveur invalide');
                        return;
                    }
                }
                
                if (parsedResponse.success) {
                    alert('Rôle attribué avec succès');
                    location.reload();
                } else {
                    alert('Erreur: ' + (parsedResponse.message || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Grant role AJAX error:', {xhr, status, error});
                console.error('Response text:', xhr.responseText);
                alert(<?= json_encode($this->lang->line('authorization_error_occurred')) ?> + ': ' + error);
            },
            complete: function() {
                // Re-enable button
                $btn.prop('disabled', false).text(<?= json_encode($this->lang->line('authorization_grant')) ?>);
            }
        });
    });

    // Revoke Role Button - using event delegation
    $(document).off('click', '.btn-revoke-role').on('click', '.btn-revoke-role', function(e) {
        e.preventDefault();
        console.log('Revoke role button clicked (event delegation)');
        
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const sectionId = $btn.data('section-id');
        const username = $btn.data('username');

        console.log('Revoke role data:', {userId, sectionId, username});

        $('#revokeUserId').val(userId);
        $('#revokeSectionId').val(sectionId);
        $('#revokeUsername').text(username);

        // Populate role dropdown with user's current roles
        const roles = $btn.closest('tr').find('.badge[data-role-id]');
        const revokeSelect = $('#revokeRoleSelect');
        revokeSelect.empty();
        revokeSelect.append(<?= json_encode('<option value="">-- ' . $this->lang->line('authorization_select_role') . ' --</option>') ?>);

        roles.each(function() {
            const roleId = $(this).data('role-id');
            const roleName = $(this).text().trim();
            if (roleId) {
                revokeSelect.append(`<option value="${roleId}">${roleName}</option>`);
            }
        });

        console.log('Available roles for revoke:', revokeSelect.find('option').length);

        // Try both Bootstrap methods
        try {
            if (typeof bootstrap !== 'undefined') {
                const revokeModal = new bootstrap.Modal(document.getElementById('revokeRoleModal'));
                revokeModal.show();
                console.log('Revoke modal opened with Bootstrap 5');
            } else if ($.fn.modal) {
                $('#revokeRoleModal').modal('show');
                console.log('Revoke modal opened with jQuery/Bootstrap 4');
            } else {
                console.error('No modal method available');
                alert('Modal functionality not available. Please refresh the page.');
            }
        } catch (error) {
            console.error('Revoke modal error:', error);
            alert('Error opening modal: ' + error.message);
        }
    });

    // Confirm Revoke Role
    $('#confirmRevokeRole').off('click').on('click', function() {
        console.log('Confirm revoke role clicked');
        
        // Get values and log each one
        const userId = $('#revokeUserId').val();
        const roleId = $('#revokeRoleSelect').val();
        const sectionId = $('#revokeSectionId').val();

        console.log('Individual revoke values:');
        console.log('- userId element exists:', $('#revokeUserId').length > 0);
        console.log('- userId value:', userId);
        console.log('- roleId element exists:', $('#revokeRoleSelect').length > 0);
        console.log('- roleId value:', roleId);
        console.log('- sectionId element exists:', $('#revokeSectionId').length > 0);
        console.log('- sectionId value:', sectionId);

        console.log('Revoke role request data:', {userId, roleId, sectionId});

        // Validate required fields
        if (!userId) {
            alert('Erreur: ID utilisateur manquant');
            console.error('Missing user_id for revoke');
            return;
        }

        if (!roleId) {
            alert(<?= json_encode($this->lang->line('authorization_please_select_role')) ?>);
            console.error('Missing types_roles_id for revoke');
            return;
        }

        // Disable button during request
        const $btn = $(this);
        $btn.prop('disabled', true).text('En cours...');

        const requestData = {
            user_id: String(userId || ''),
            types_roles_id: String(roleId || ''),
            section_id: sectionId === '' ? '' : String(sectionId || ''),
            action: 'revoke'
        };

        console.log('Sending revoke AJAX request with data:', requestData);

        // Alternative approach - try serializing the data manually
        const formData = new FormData();
        formData.append('user_id', userId || '');
        formData.append('types_roles_id', roleId || '');
        formData.append('section_id', sectionId || '');
        formData.append('action', 'revoke');

        console.log('Revoke FormData entries:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        $.ajax({
            url: '<?= site_url('authorization/edit_user_roles') ?>',
            type: 'POST', 
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function(xhr) {
                console.log('Revoke AJAX beforeSend with FormData');
            },
            success: function(response) {
                console.log('Revoke role response:', response);
                console.log('Response type:', typeof response);
                
                // Handle both string and object responses
                let parsedResponse = response;
                if (typeof response === 'string') {
                    try {
                        parsedResponse = JSON.parse(response);
                        console.log('Parsed response:', parsedResponse);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        alert('Erreur: Réponse serveur invalide');
                        return;
                    }
                }
                
                if (parsedResponse.success) {
                    alert('Rôle révoqué avec succès');
                    location.reload();
                } else {
                    alert('Erreur: ' + (parsedResponse.message || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Revoke role AJAX error:', {xhr, status, error});
                console.error('Response text:', xhr.responseText);
                alert(<?= json_encode($this->lang->line('authorization_error_occurred')) ?> + ': ' + error);
            },
            complete: function() {
                // Re-enable button
                $btn.prop('disabled', false).text(<?= json_encode($this->lang->line('authorization_revoke')) ?>);
            }
        });
    });

    console.log('User roles functionality initialized');
}

// Multiple initialization approaches for maximum compatibility
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        console.log('Document ready - initializing user roles');
        initializeUserRoles();
    });
} else {
    console.log('jQuery not available, trying window.onload');
    window.onload = function() {
        // Wait a bit for potential jQuery to load
        setTimeout(function() {
            if (typeof $ !== 'undefined') {
                console.log('jQuery loaded after delay - initializing user roles');
                initializeUserRoles();
            } else {
                console.error('jQuery still not available');
                alert('JavaScript functionality not available. Please refresh the page.');
            }
        }, 1000);
    };
}

// Add a manual trigger for debugging
window.debugUserRoles = function() {
    console.log('=== DEBUG USER ROLES ===');
    console.log('jQuery:', typeof $);
    console.log('Bootstrap:', typeof bootstrap);
    console.log('Grant buttons:', $('.btn-grant-role').length);
    console.log('Revoke buttons:', $('.btn-revoke-role').length);
    console.log('Modals:', $('#grantRoleModal').length, $('#revokeRoleModal').length);
    
    // Test click handlers
    $('.btn-grant-role').first().trigger('click');
};

// Multiple initialization approaches for maximum compatibility
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        console.log('Document ready - initializing user roles');
        initializeUserRoles();
    });
} else {
    console.log('jQuery not available, trying window.onload');
    window.onload = function() {
        // Wait a bit for potential jQuery to load
        setTimeout(function() {
            if (typeof $ !== 'undefined') {
                console.log('jQuery loaded after delay - initializing user roles');
                initializeUserRoles();
            } else {
                console.error('jQuery still not available');
                alert('JavaScript functionality not available. Please refresh the page.');
            }
        }, 1000);
    };
}

// Add a manual trigger for debugging
window.debugUserRoles = function() {
    console.log('=== DEBUG USER ROLES ===');
    console.log('jQuery:', typeof $);
    console.log('Bootstrap:', typeof bootstrap);
    console.log('Grant buttons:', $('.btn-grant-role').length);
    console.log('Revoke buttons:', $('.btn-revoke-role').length);
    console.log('Modals:', $('#grantRoleModal').length, $('#revokeRoleModal').length);
    
    // Test click handlers
    $('.btn-grant-role').first().trigger('click');
};
</script>

<!-- Simple inline test for debugging -->
<script>
console.log('=== AUTHORIZATION USER ROLES DEBUG ===');
console.log('Simple test script loaded');

// Test translation strings
console.log('Testing PHP translation output:');
console.log('please_select_role:', <?= json_encode($this->lang->line('authorization_please_select_role')) ?>);
console.log('error_occurred:', <?= json_encode($this->lang->line('authorization_error_occurred')) ?>);
console.log('grant:', <?= json_encode($this->lang->line('authorization_grant')) ?>);
console.log('revoke:', <?= json_encode($this->lang->line('authorization_revoke')) ?>);
console.log('select_role:', <?= json_encode($this->lang->line('authorization_select_role')) ?>);

// Simple fallback handlers in case jQuery/Bootstrap doesn't work
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - checking if advanced handlers are working...');
    
    // Wait a moment for jQuery handlers to attach
    setTimeout(function() {
        const testButtons = document.querySelectorAll('.btn-grant-role');
        console.log('Found grant buttons for fallback:', testButtons.length);
        
        // Only add fallback if no jQuery handlers are working
        if (typeof $ === 'undefined' || $('.btn-grant-role').length === 0) {
            console.log('Adding fallback handlers...');
            
            testButtons.forEach(function(button, index) {
                // Check if button already has handlers
                if (!button.hasAttribute('data-fallback-added')) {
                    button.setAttribute('data-fallback-added', 'true');
                    button.addEventListener('click', function(e) {
                        console.log('Fallback click handler triggered for button', index);
                        e.preventDefault();
                        
                        const userId = this.getAttribute('data-user-id');
                        const username = this.getAttribute('data-username');
                        console.log('Button data:', {userId, username});
                        
                        // Simple modal simulation
                        const roleId = prompt('Enter role ID to grant to user ' + username + ':');
                        if (roleId) {
                            console.log('Would grant role', roleId, 'to user', userId);
                            alert('Fallback mode: Would grant role ' + roleId + ' to user ' + username);
                        }
                    });
                }
            });
        } else {
            console.log('jQuery handlers found, fallback not needed');
        }
    }, 500);
});
</script>


