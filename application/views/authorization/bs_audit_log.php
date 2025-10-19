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
 * Audit Log Viewer
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
            <li class="breadcrumb-item active" aria-current="page"><?= $this->lang->line('authorization_audit_log') ?></li>
        </ol>
    </nav>

    <h3><?= $title ?></h3>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> <?= $this->lang->line('authorization_filters') ?></h5>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('authorization/audit_log') ?>">
                <div class="row">
                    <div class="col-md-3">
                        <label for="action_type" class="form-label"><?= $this->lang->line('authorization_action_type') ?></label>
                        <select class="form-select" id="action_type" name="action_type">
                            <option value="">-- <?= $this->lang->line('authorization_all') ?> --</option>
                            <option value="grant_role" <?= isset($filters['action_type']) && $filters['action_type'] === 'grant_role' ? 'selected' : '' ?>>
                                <?= $this->lang->line('authorization_grant_role') ?>
                            </option>
                            <option value="revoke_role" <?= isset($filters['action_type']) && $filters['action_type'] === 'revoke_role' ? 'selected' : '' ?>>
                                <?= $this->lang->line('authorization_revoke_role') ?>
                            </option>
                            <option value="access_denied" <?= isset($filters['action_type']) && $filters['action_type'] === 'access_denied' ? 'selected' : '' ?>>
                                <?= $this->lang->line('authorization_access_denied') ?>
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="user_id" class="form-label"><?= $this->lang->line('authorization_user_filter') ?></label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">-- <?= $this->lang->line('authorization_all_users') ?> --</option>
                            <?php
                            $users = $this->db->select('id, username')->from('users')->order_by('username')->get()->result_array();
                            foreach ($users as $user):
                            ?>
                                <option value="<?= $user['id'] ?>" <?= isset($filters['target_user_id']) && $filters['target_user_id'] == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> <?= $this->lang->line('authorization_filter') ?>
                        </button>
                        <a href="<?= site_url('authorization/audit_log') ?>" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> <?= $this->lang->line('authorization_reset') ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Log Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-history"></i> <?= $this->lang->line('authorization_audit_entries') ?>
                <span class="badge bg-secondary"><?= count($audit_log) ?> <?= $this->lang->line('authorization_entries') ?></span>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="auditTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('authorization_timestamp') ?></th>
                            <th><?= $this->lang->line('authorization_action_type') ?></th>
                            <th><?= $this->lang->line('authorization_actor') ?></th>
                            <th><?= $this->lang->line('authorization_target_user') ?></th>
                            <th><?= $this->lang->line('authorization_role') ?></th>
                            <th><?= $this->lang->line('authorization_section') ?></th>
                            <th><?= $this->lang->line('authorization_ip_address') ?></th>
                            <th><?= $this->lang->line('authorization_details') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($audit_log)): ?>
                        <?php foreach ($audit_log as $entry): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', strtotime($entry['created_at'])) ?></td>
                                <td>
                                    <?php
                                    $action_badges = array(
                                        'grant_role' => array('class' => 'bg-success', 'icon' => 'fa-plus-circle'),
                                        'revoke_role' => array('class' => 'bg-danger', 'icon' => 'fa-minus-circle'),
                                        'access_denied' => array('class' => 'bg-warning text-dark', 'icon' => 'fa-ban')
                                    );
                                    $badge_info = $action_badges[$entry['action_type']] ?? array('class' => 'bg-secondary', 'icon' => 'fa-info-circle');
                                    ?>
                                    <span class="badge <?= $badge_info['class'] ?>">
                                        <i class="fas <?= $badge_info['icon'] ?>"></i>
                                        <?= htmlspecialchars($entry['action_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($entry['actor_username'])): ?>
                                        <?= htmlspecialchars($entry['actor_username']) ?>
                                    <?php else: ?>
                                        <em class="text-muted"><?= $this->lang->line('authorization_system') ?></em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($entry['target_username'])): ?>
                                        <?= htmlspecialchars($entry['target_username']) ?>
                                    <?php else: ?>
                                        <em class="text-muted">-</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($entry['role_name'])): ?>
                                        <span class="badge bg-primary"><?= htmlspecialchars($entry['role_name']) ?></span>
                                    <?php else: ?>
                                        <em class="text-muted">-</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($entry['section_id']) && $entry['section_id'] == 0): ?>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-globe"></i> <?= $this->lang->line('authorization_global') ?>
                                        </span>
                                    <?php elseif (!empty($entry['section_id'])): ?>
                                        <?php
                                        $section = $this->db->where('id', $entry['section_id'])->get('sections')->row_array();
                                        if ($section):
                                        ?>
                                            <?= htmlspecialchars($section['nom']) ?>
                                        <?php else: ?>
                                            Section #<?= $entry['section_id'] ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <em class="text-muted">-</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($entry['ip_address'])): ?>
                                        <small><code><?= htmlspecialchars($entry['ip_address']) ?></code></small>
                                    <?php else: ?>
                                        <em class="text-muted">-</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($entry['details'])): ?>
                                        <?php
                                        // Try to decode JSON details
                                        $details = json_decode($entry['details'], true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($details)):
                                        ?>
                                            <small>
                                                <?php foreach ($details as $key => $value): ?>
                                                    <strong><?= htmlspecialchars($key) ?>:</strong> <?= htmlspecialchars($value) ?><br>
                                                <?php endforeach; ?>
                                            </small>
                                        <?php else: ?>
                                            <small><?= htmlspecialchars($entry['details']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <em class="text-muted">-</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    <em><?= $this->lang->line('authorization_no_audit_entries') ?></em>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (!empty($audit_log) && count($audit_log) >= $per_page): ?>
                <nav aria-label="Audit log pagination" class="mt-3">
                    <ul class="pagination">
                        <?php if ($page > 0): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= site_url('authorization/audit_log/' . ($page - 1)) ?>">
                                    <?= $this->lang->line('authorization_previous') ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item active">
                            <span class="page-link">
                                <?= $this->lang->line('authorization_page') ?> <?= $page + 1 ?>
                            </span>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= site_url('authorization/audit_log/' . ($page + 1)) ?>">
                                <?= $this->lang->line('authorization_next') ?>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3">
        <a href="<?= site_url('authorization') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?= $this->lang->line('authorization_back_to_dashboard') ?>
        </a>
    </div>
</div>

<script>
$(document).ready(function() {
    console.log('Initializing audit log table...');
    
    // Count table elements
    var headerCount = $('#auditTable thead th').length;
    var rowCount = $('#auditTable tbody tr').length;
    
    console.log('Headers:', headerCount);
    console.log('Rows:', rowCount);
    
    <?php if (!empty($audit_log)): ?>
    // Only initialize DataTable if we have actual audit data (not just the "no entries" row)
    if (rowCount > 0) {
        var firstRowColumns = $('#auditTable tbody tr:first td').length;
        console.log('First row columns:', firstRowColumns);
        
        // Check if first row is the "no entries" message (colspan=8)
        if ($('#auditTable tbody tr:first td[colspan]').length === 0 && firstRowColumns === headerCount) {
            // We have real data and column count matches
            try {
                $('#auditTable').DataTable({
                    "paging": false,
                    "info": false,
                    "searching": true,
                    "ordering": true,
                    "order": [[0, "desc"]],
                    "autoWidth": false,
                    "language": {
                        "search": "Rechercher:",
                        "searchPlaceholder": "Filtrer les entrées...",
                        "zeroRecords": "Aucune entrée trouvée",
                        "emptyTable": "Aucune donnée disponible"
                    },
                    "columnDefs": [
                        {
                            "targets": [7], // Details column
                            "orderable": false
                        }
                    ]
                });
                console.log('DataTable initialized successfully');
            } catch (error) {
                console.error('DataTable error:', error);
                console.log('Continuing without DataTable functionality');
            }
        } else {
            console.log('Column mismatch or no real data - headers:', headerCount, 'data:', firstRowColumns);
        }
    } else {
        console.log('No rows to process');
    }
    <?php else: ?>
    console.log('No audit log data available');
    <?php endif; ?>
});
</script>


