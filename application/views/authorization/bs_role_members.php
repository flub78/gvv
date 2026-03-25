<!-- VIEW: application/views/authorization/bs_role_members.php -->
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
 * Role Members Management View
 *
 * @package views
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

// Build section lookup map
$section_map = array();
foreach ($sections as $s) {
    $section_map[$s['id']] = $s['nom'];
}

$role_label = !empty($role['translation_key']) ? $this->lang->line($role['translation_key']) : $role['nom'];
if ($role_label === FALSE) $role_label = $role['nom'];
?>

<div id="body" class="body container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= site_url('authorization') ?>"><?= $this->lang->line('authorization_title') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= site_url('authorization/roles') ?>"><?= $this->lang->line('authorization_available_roles') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($role_label) ?></li>
        </ol>
    </nav>

    <h3><?= $this->lang->line('authorization_role_members') ?> : <em><?= htmlspecialchars($role_label) ?></em></h3>

    <div id="alertBox"></div>

    <!-- Current members table -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0"><?= $this->lang->line('authorization_members') ?></h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <select id="filterSection" class="form-select w-auto">
                    <option value=""><?= $this->lang->line('authorization_select_section') ?> — toutes</option>
                    <?php foreach ($sections as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" data-nom="<?= htmlspecialchars($s['nom']) ?>"><?= htmlspecialchars($s['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <table class="datatable table table-striped table-bordered" id="membersTable">
                <thead>
                    <tr>
                        <th><?= $this->lang->line('authorization_username') ?></th>
                        <th><?= $this->lang->line('authorization_name') ?></th>
                        <th><?= $this->lang->line('authorization_section') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr data-user-id="<?= (int)$member['user_id'] ?>" data-section-id="<?= (int)$member['section_id'] ?>">
                                <td><?= htmlspecialchars($member['username']) ?></td>
                                <td><?= htmlspecialchars(trim(($member['prenom'] ?? '') . ' ' . ($member['nom'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars($section_map[$member['section_id']] ?? '') ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger btn-remove-member"
                                            data-user-id="<?= (int)$member['user_id'] ?>"
                                            data-section-id="<?= (int)$member['section_id'] ?>">
                                        <i class="fas fa-times"></i> <?= $this->lang->line('authorization_remove_member') ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add member form -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0"><?= $this->lang->line('authorization_add_member') ?></h5>
        </div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label for="selectUser" class="form-label"><?= $this->lang->line('authorization_select_user') ?></label>
                    <select class="big_select" id="selectUser" style="width:300px">
                        <option value="">-- <?= $this->lang->line('authorization_select_user') ?> --</option>
                        <?php foreach ($all_users as $user): ?>
                            <?php
                                $display = htmlspecialchars($user['username']);
                                $full = trim(($user['mprenom'] ?? '') . ' ' . ($user['mnom'] ?? ''));
                                if ($full) $display .= ' (' . htmlspecialchars($full) . ')';
                            ?>
                            <option value="<?= (int)$user['id'] ?>"><?= $display ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-success w-100" id="btnAddMember">
                        <i class="fas fa-plus"></i> <?= $this->lang->line('authorization_add_member') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="<?= site_url('authorization/roles') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?= $this->lang->line('authorization_available_roles') ?>
        </a>
    </div>
</div>

<script>
(function () {
    var roleId = <?= (int)$role['id'] ?>;
    var ajaxUrl = '<?= site_url('authorization/edit_user_roles') ?>';
    var storageKey = 'role_members_section_' + roleId;

    function applySection(sectionId) {
        var sel = document.getElementById('filterSection');
        sel.value = sectionId;
        var nom = $(sel).find('option:selected').data('nom') || '';
        var oTable = $('#membersTable').dataTable({'bRetrieve': true});
        oTable.fnFilter(nom, 2, false, false);
    }

    // Restore saved section after DataTables is ready (window load fires after all document.ready callbacks)
    $(window).on('load', function () {
        var saved = sessionStorage.getItem(storageKey);
        if (saved) { applySection(saved); }
    });

    // Section filter — runs on user interaction, table already initialized by footer
    $('#filterSection').on('change', function () {
        var val = $(this).val();
        sessionStorage.setItem(storageKey, val);
        var nom = $(this).find('option:selected').data('nom') || '';
        var oTable = $('#membersTable').dataTable({'bRetrieve': true});
        oTable.fnFilter(nom, 2, false, false);
    });

    function showAlert(message, type) {
        var box = document.getElementById('alertBox');
        box.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">'
            + message
            + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            + '</div>';
    }

    function doAjax(userId, sectionId, action, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    callback(resp);
                } catch (e) {
                    callback({ success: false, message: 'Erreur de communication' });
                }
            }
        };
        xhr.send('user_id=' + userId + '&types_roles_id=' + roleId + '&section_id=' + sectionId + '&action=' + action);
    }

    // Remove member
    document.getElementById('membersTable').addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-member');
        if (!btn) return;
        var userId = btn.getAttribute('data-user-id');
        var sectionId = btn.getAttribute('data-section-id');
        doAjax(userId, sectionId, 'revoke', function (resp) {
            if (resp.success) {
                var oTable = $('#membersTable').dataTable({'bRetrieve': true});
                oTable.fnDeleteRow(btn.closest('tr'));
                showAlert(resp.message || 'Rôle retiré', 'success');
            } else {
                showAlert(resp.message || 'Erreur', 'danger');
            }
        });
    });

    // Add member
    document.getElementById('btnAddMember').addEventListener('click', function () {
        var userId = $('#selectUser').val();
        var sectionId = document.getElementById('filterSection').value;
        if (!userId || !sectionId) {
            showAlert('Veuillez sélectionner un utilisateur et une section dans le filtre', 'warning');
            return;
        }
        doAjax(userId, sectionId, 'grant', function (resp) {
            if (resp.success) {
                // Reload to refresh the list with correct section names
                window.location.reload();
            } else {
                showAlert(resp.message || 'Erreur', 'danger');
            }
        });
    });
}());
</script>
