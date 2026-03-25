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

$has_section = !empty($current_section_id);
$section_name = $has_section ? ($section_map[$current_section_id] ?? '') : '';
?>

<div id="body" class="body container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= site_url('authorization') ?>"><?= $this->lang->line('authorization_title') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= site_url('authorization/roles') ?>"><?= $this->lang->line('authorization_available_roles') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($role_label) ?></li>
        </ol>
    </nav>

    <h3>
        <?= $this->lang->line('authorization_role_members') ?> : <em><?= htmlspecialchars($role_label) ?></em>
        <?php if ($has_section): ?>
            <small class="text-muted fs-6">— <?= htmlspecialchars($section_name) ?></small>
        <?php endif; ?>
    </h3>

    <div id="alertBox"></div>

    <!-- Current members table -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0"><?= $this->lang->line('authorization_members') ?></h5>
        </div>
        <div class="card-body">
            <table class="datatable table table-striped table-bordered" id="membersTable">
                <thead>
                    <tr>
                        <th><?= $this->lang->line('authorization_username') ?></th>
                        <th><?= $this->lang->line('authorization_name') ?></th>
                        <?php if (!$has_section): ?><th><?= $this->lang->line('authorization_section') ?></th><?php endif; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr data-user-id="<?= (int)$member['user_id'] ?>" data-section-id="<?= (int)$member['section_id'] ?>">
                            <td><?= htmlspecialchars($member['username']) ?></td>
                            <td><?= htmlspecialchars(trim(($member['prenom'] ?? '') . ' ' . ($member['nom'] ?? ''))) ?></td>
                            <?php if (!$has_section): ?><td><?= htmlspecialchars($section_map[$member['section_id']] ?? '') ?></td><?php endif; ?>
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

    <!-- Add member form — only available when a section is active -->
    <?php if ($has_section): ?>
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
    <?php else: ?>
    <div class="alert alert-info mt-3">
        <i class="fas fa-info-circle"></i> <?= $this->lang->line('authorization_select_section_to_add') ?>
    </div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="<?= site_url('authorization/roles') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?= $this->lang->line('authorization_available_roles') ?>
        </a>
    </div>
</div>

<script>
(function () {
    var roleId = <?= (int)$role['id'] ?>;
    var sectionId = <?= (int)($current_section_id ?? 0) ?>;
    var ajaxUrl = '<?= site_url('authorization/edit_user_roles') ?>';
    var i18n = {
        commError:   <?= json_encode($this->lang->line('authorization_comm_error')) ?>,
        roleRemoved: <?= json_encode($this->lang->line('authorization_role_removed')) ?>,
        error:       <?= json_encode($this->lang->line('authorization_error')) ?>,
        selectUser:  <?= json_encode($this->lang->line('authorization_select_user_section')) ?>
    };

    function showAlert(message, type) {
        var box = document.getElementById('alertBox');
        box.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">'
            + message
            + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            + '</div>';
    }

    function doAjax(userId, sid, action, callback) {
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
                    callback({ success: false, message: i18n.commError });
                }
            }
        };
        xhr.send('user_id=' + userId + '&types_roles_id=' + roleId + '&section_id=' + sid + '&action=' + action);
    }

    // Remove member
    document.getElementById('membersTable').addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-member');
        if (!btn) return;
        var userId = btn.getAttribute('data-user-id');
        var sid    = btn.getAttribute('data-section-id');
        doAjax(userId, sid, 'revoke', function (resp) {
            if (resp.success) {
                var oTable = $('#membersTable').dataTable({'bRetrieve': true});
                oTable.fnDeleteRow(btn.closest('tr'));
                showAlert(resp.message || i18n.roleRemoved, 'success');
            } else {
                showAlert(resp.message || i18n.error, 'danger');
            }
        });
    });

    <?php if ($has_section): ?>
    // Add member — uses active section
    document.getElementById('btnAddMember').addEventListener('click', function () {
        var userId = $('#selectUser').val();
        if (!userId) {
            showAlert(i18n.selectUser, 'warning');
            return;
        }
        doAjax(userId, sectionId, 'grant', function (resp) {
            if (resp.success) {
                window.location.reload();
            } else {
                showAlert(resp.message || i18n.error, 'danger');
            }
        });
    });
    <?php endif; ?>
}());
</script>
