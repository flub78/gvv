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
 * Data Access Rules Management View
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
            <li class="breadcrumb-item active" aria-current="page"><?= $this->lang->line('authorization_data_rules') ?></li>
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

    <!-- Add Data Access Rule Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-plus-circle"></i> <?= $this->lang->line('authorization_add_data_rule') ?></h5>
        </div>
        <div class="card-body">
            <form id="addRuleForm">
                <div class="row">
                    <div class="col-md-3">
                        <label for="table_name" class="form-label"><?= $this->lang->line('authorization_table_name') ?></label>
                        <select class="form-select" id="table_name" required>
                            <option value="">-- <?= $this->lang->line('authorization_select') ?> --</option>
                            <?php foreach ($available_tables as $table): ?>
                                <option value="<?= htmlspecialchars($table) ?>"><?= htmlspecialchars($table) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="access_scope" class="form-label"><?= $this->lang->line('authorization_access_scope') ?></label>
                        <select class="form-select" id="access_scope" required>
                            <option value="own"><?= $this->lang->line('authorization_scope_own') ?></option>
                            <option value="section"><?= $this->lang->line('authorization_scope_section') ?></option>
                            <option value="all"><?= $this->lang->line('authorization_scope_all') ?></option>
                        </select>
                    </div>
                    <div class="col-md-2" id="fieldNameDiv" style="display:none;">
                        <label for="field_name" class="form-label">
                            <?= $this->lang->line('authorization_field_name') ?>
                            <i class="fas fa-info-circle" title="<?= $this->lang->line('authorization_field_name_help') ?>"></i>
                        </label>
                        <input type="text" class="form-control" id="field_name" placeholder="user_id">
                    </div>
                    <div class="col-md-2" id="sectionFieldDiv" style="display:none;">
                        <label for="section_field" class="form-label">
                            <?= $this->lang->line('authorization_section_field') ?>
                            <i class="fas fa-info-circle" title="<?= $this->lang->line('authorization_section_field_help') ?>"></i>
                        </label>
                        <input type="text" class="form-control" id="section_field" placeholder="club">
                    </div>
                    <div class="col-md-3">
                        <label for="description" class="form-label"><?= $this->lang->line('authorization_description') ?></label>
                        <input type="text" class="form-control" id="description" placeholder="<?= $this->lang->line('authorization_rule_description_placeholder') ?>">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> <?= $this->lang->line('authorization_add') ?>
                        </button>
                    </div>
                </div>
                <input type="hidden" id="types_roles_id" value="<?= $role['id'] ?>">
            </form>

            <!-- Help Text -->
            <div class="alert alert-info mt-3">
                <h6><i class="fas fa-lightbulb"></i> <?= $this->lang->line('authorization_scope_help_title') ?></h6>
                <ul class="mb-0">
                    <li><strong><?= $this->lang->line('authorization_scope_own') ?>:</strong> <?= $this->lang->line('authorization_scope_own_desc') ?></li>
                    <li><strong><?= $this->lang->line('authorization_scope_section') ?>:</strong> <?= $this->lang->line('authorization_scope_section_desc') ?></li>
                    <li><strong><?= $this->lang->line('authorization_scope_all') ?>:</strong> <?= $this->lang->line('authorization_scope_all_desc') ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Data Access Rules List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> <?= $this->lang->line('authorization_current_rules') ?></h5>
        </div>
        <div class="card-body">
            <?php if (empty($rules)): ?>
                <p class="text-muted"><em><?= $this->lang->line('authorization_no_rules') ?></em></p>
            <?php else: ?>
                <table id="rulesTable" class="table table-striped table-bordered datatable">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('authorization_table_name') ?></th>
                            <th><?= $this->lang->line('authorization_access_scope') ?></th>
                            <th><?= $this->lang->line('authorization_field_name') ?></th>
                            <th><?= $this->lang->line('authorization_section_field') ?></th>
                            <th><?= $this->lang->line('authorization_description') ?></th>
                            <th><?= $this->lang->line('authorization_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $rule): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($rule['table_name']) ?></code></td>
                                <td>
                                    <?php
                                    $scope_badges = array(
                                        'own' => 'bg-info',
                                        'section' => 'bg-primary',
                                        'all' => 'bg-success'
                                    );
                                    $badge_class = $scope_badges[$rule['access_scope']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badge_class ?>">
                                        <?= htmlspecialchars($rule['access_scope']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($rule['field_name'])): ?>
                                        <code><?= htmlspecialchars($rule['field_name']) ?></code>
                                    <?php else: ?>
                                        <em class="text-muted">-</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($rule['section_field'])): ?>
                                        <code><?= htmlspecialchars($rule['section_field']) ?></code>
                                    <?php else: ?>
                                        <em class="text-muted">-</em>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($rule['description'] ?? '') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger btn-remove-rule" data-rule-id="<?= $rule['id'] ?>">
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
    <?php if (!empty($rules)): ?>
    $('#rulesTable').DataTable({
        "pageLength": 25,
        "order": [[0, "asc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json"
        }
    });
    <?php endif; ?>

    // Show/hide fields based on access scope
    $('#access_scope').on('change', function() {
        const scope = $(this).val();

        $('#fieldNameDiv').hide();
        $('#sectionFieldDiv').hide();

        if (scope === 'own') {
            $('#fieldNameDiv').show();
        } else if (scope === 'section') {
            $('#sectionFieldDiv').show();
        }
    });

    // Add Data Access Rule Form Submit
    $('#addRuleForm').on('submit', function(e) {
        e.preventDefault();

        const data = {
            types_roles_id: $('#types_roles_id').val(),
            table_name: $('#table_name').val(),
            access_scope: $('#access_scope').val(),
            field_name: $('#field_name').val() || null,
            section_field: $('#section_field').val() || null,
            description: $('#description').val() || null
        };

        $.ajax({
            url: '<?= site_url('authorization/add_data_access_rule') ?>',
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

    // Remove Rule Button
    $('.btn-remove-rule').on('click', function() {
        if (!confirm('<?= $this->lang->line('authorization_confirm_delete') ?>')) {
            return;
        }

        const ruleId = $(this).data('rule-id');

        $.ajax({
            url: '<?= site_url('authorization/remove_data_access_rule') ?>',
            method: 'POST',
            data: { rule_id: ruleId },
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
