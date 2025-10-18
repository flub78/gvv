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
 * Select Role View (for permissions)
 *
 * @package views
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid">
    <h3><?= $title ?></h3>

    <div class="card mt-4">
        <div class="card-body">
            <p><?= $this->lang->line('authorization_select_role_desc') ?></p>

            <form method="get" action="<?= site_url('authorization/data_access_rules') ?>" id="selectRoleForm">
                <div class="mb-3">
                    <label for="roleSelect" class="form-label"><?= $this->lang->line('authorization_select_role') ?></label>
                    <select class="form-select" id="roleSelect" name="role_id" required>
                        <option value="">-- <?= $this->lang->line('authorization_select_role') ?> --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>">
                                <?= htmlspecialchars($role['nom']) ?>
                                (<?= $role['scope'] === 'global' ? $this->lang->line('authorization_global') : $this->lang->line('authorization_section') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $this->lang->line('authorization_continue') ?>
                </button>
                <a href="<?= site_url('authorization') ?>" class="btn btn-secondary">
                    <?= $this->lang->line('authorization_cancel') ?>
                </a>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#selectRoleForm').on('submit', function(e) {
        e.preventDefault();
        const roleId = $('#roleSelect').val();
        if (roleId) {
            window.location.href = '<?= site_url('authorization/data_access_rules') ?>/' + roleId;
        }
    });
});
</script>

<?php
$this->load->view('bs_footer');
?>
