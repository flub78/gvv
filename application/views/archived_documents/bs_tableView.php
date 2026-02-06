<!-- VIEW: application/views/archived_documents/bs_tableView.php -->
<?php
/**
 * Admin view for documents: filter toolbar + filtered content
 * Supports filters: expired, pending, or no filter (all documents)
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');

$filter = isset($active_filter) ? $active_filter : '';
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-archive"></i> <?= $this->lang->line('archived_documents_all_documents') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<!-- Filter toolbar -->
<div class="mb-3">
    <div class="btn-group" role="group">
        <a href="<?= site_url('archived_documents/page') ?>" class="btn btn-sm <?= empty($filter) ? 'btn-secondary' : 'btn-outline-secondary' ?>">
            <i class="fas fa-list"></i> <?= $this->lang->line('archived_documents_filter_all') ?>
        </a>
        <a href="<?= site_url('archived_documents/page?filter=expired') ?>" class="btn btn-sm <?= $filter === 'expired' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">
            <i class="fas fa-exclamation-triangle"></i> <?= $this->lang->line('archived_documents_expired') ?>
            <?php if (!empty($expired_count)): ?>
                <span class="badge <?= $filter === 'expired' ? 'bg-dark' : 'bg-danger' ?>"><?= $expired_count ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= site_url('archived_documents/page?filter=pending') ?>" class="btn btn-sm <?= $filter === 'pending' ? 'btn-info text-dark' : 'btn-outline-info' ?>">
            <i class="fas fa-clock"></i> <?= $this->lang->line('archived_documents_pending_documents') ?>
            <?php if (!empty($pending_count)): ?>
                <span class="badge <?= $filter === 'pending' ? 'bg-dark' : 'bg-danger' ?>"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>
    </div>
    <a href="<?= site_url('archived_documents/create') ?>" class="btn btn-sm btn-success ms-2">
        <i class="fas fa-plus"></i> <?= $this->lang->line('archived_documents_add') ?>
    </a>
</div>

<?php if ($filter === 'expired'): ?>
<!-- ============================================ -->
<!-- FILTER: Expired documents                    -->
<!-- ============================================ -->

<!-- Expiring soon -->
<?php if (!empty($expiring_soon)): ?>
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-clock"></i> <?= $this->lang->line('archived_documents_expiring_soon') ?> (<?= count($expiring_soon) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_pilot') ?></th>
                        <th><?= $this->lang->line('archived_documents_type') ?></th>
                        <th><?= $this->lang->line('archived_documents_description') ?></th>
                        <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                        <th><?= $this->lang->line('archived_documents_days_remaining') ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiring_soon as $doc): ?>
                    <?php $days_left = floor((strtotime($doc['valid_until']) - time()) / 86400); ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('archived_documents/pilot_documents/' . $doc['pilot_login']) ?>">
                                <?= htmlspecialchars($doc['pilot_prenom'] . ' ' . $doc['pilot_nom']) ?>
                            </a>
                        </td>
                        <td>
                            <?php $type_label = !empty($doc['type_name']) ? $doc['type_name'] : $this->lang->line('archived_documents_type_other'); ?>
                            <?= htmlspecialchars($type_label) ?>
                        </td>
                        <td><?= htmlspecialchars($doc['description'] ?? '') ?></td>
                        <td><?= date('d/m/Y', strtotime($doc['valid_until'])) ?></td>
                        <td><span class="badge bg-warning text-dark"><?= $days_left ?> jours</span></td>
                        <td>
                            <a href="<?= site_url('archived_documents/view/' . $doc['id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning toggle-alarm"
                                    data-id="<?= $doc['id'] ?>"
                                    title="<?= $this->lang->line('archived_documents_toggle_alarm') ?>">
                                <i class="fas fa-bell-slash"></i> <?= $this->lang->line('archived_documents_disable_alarm') ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Expired -->
<?php if (!empty($documents)): ?>
<div class="card border-danger">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-times-circle"></i> <?= $this->lang->line('archived_documents_expired') ?> (<?= count($documents) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_pilot') ?></th>
                        <th><?= $this->lang->line('archived_documents_type') ?></th>
                        <th><?= $this->lang->line('archived_documents_description') ?></th>
                        <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                        <th><?= $this->lang->line('archived_documents_days_expired') ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <?php $days_expired = floor((time() - strtotime($doc['valid_until'])) / 86400); ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('archived_documents/pilot_documents/' . $doc['pilot_login']) ?>">
                                <?= htmlspecialchars($doc['pilot_prenom'] . ' ' . $doc['pilot_nom']) ?>
                            </a>
                        </td>
                        <td>
                            <?php $type_label = !empty($doc['type_name']) ? $doc['type_name'] : $this->lang->line('archived_documents_type_other'); ?>
                            <?= htmlspecialchars($type_label) ?>
                        </td>
                        <td><?= htmlspecialchars($doc['description'] ?? '') ?></td>
                        <td><?= date('d/m/Y', strtotime($doc['valid_until'])) ?></td>
                        <td><span class="badge bg-danger"><?= $days_expired ?> jours</span></td>
                        <td>
                            <a href="<?= site_url('archived_documents/view/' . $doc['id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning toggle-alarm"
                                    data-id="<?= $doc['id'] ?>"
                                    title="<?= $this->lang->line('archived_documents_toggle_alarm') ?>">
                                <i class="fas fa-bell-slash"></i> <?= $this->lang->line('archived_documents_disable_alarm') ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<?php if (empty($expiring_soon)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?= $this->lang->line('archived_documents_no_expired') ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php elseif ($filter === 'pending'): ?>
<!-- ============================================ -->
<!-- FILTER: Pending validation                   -->
<!-- ============================================ -->

<?php if (!empty($pending_documents)): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?= $this->lang->line('archived_documents_pilot') ?></th>
                <th><?= $this->lang->line('archived_documents_type') ?></th>
                <th><?= $this->lang->line('archived_documents_description') ?></th>
                <th><?= $this->lang->line('archived_documents_file') ?></th>
                <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                <th><?= $this->lang->line('archived_documents_uploaded_by') ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pending_documents as $doc): ?>
            <tr>
                <td>
                    <?php if (!empty($doc['pilot_nom'])): ?>
                        <?= htmlspecialchars($doc['pilot_prenom'] . ' ' . $doc['pilot_nom']) ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $type_label = !empty($doc['type_name']) ? $doc['type_name'] : $this->lang->line('archived_documents_type_other'); ?>
                    <?= htmlspecialchars($type_label) ?>
                </td>
                <td><?= htmlspecialchars($doc['description'] ?? '') ?></td>
                <td>
                    <?php $preview_url = site_url('archived_documents/preview/' . $doc['id']); ?>
                    <?= attachment($doc['id'], $doc['file_path'], $preview_url) ?>
                </td>
                <td>
                    <?php if ($doc['valid_until']): ?>
                        <?= date('d/m/Y', strtotime($doc['valid_until'])) ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y', strtotime($doc['uploaded_at'])) ?></td>
                <td><?= htmlspecialchars($doc['uploaded_by']) ?></td>
                <td>
                    <a href="<?= site_url('archived_documents/view/' . $doc['id']) ?>" class="btn btn-sm btn-outline-primary" title="<?= $this->lang->line('archived_documents_view') ?>">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?= site_url('archived_documents/approve/' . $doc['id']) ?>"
                       class="btn btn-sm btn-success"
                       onclick="return confirm('<?= $this->lang->line('archived_documents_approve') ?> ?');"
                       title="<?= $this->lang->line('archived_documents_approve') ?>">
                        <i class="fas fa-check"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-danger btn-reject"
                            data-id="<?= $doc['id'] ?>"
                            data-name="<?= htmlspecialchars($doc['original_filename']) ?>"
                            title="<?= $this->lang->line('archived_documents_reject') ?>">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Reject modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="rejectForm" action="">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $this->lang->line('archived_documents_reject') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="rejectDocName"></p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label"><?= $this->lang->line('archived_documents_rejection_reason') ?></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $this->lang->line('archived_documents_back') ?></button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> <?= $this->lang->line('archived_documents_reject') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">
    <?= $this->lang->line('archived_documents_no_documents') ?>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ============================================ -->
<!-- NO FILTER: Unassociated docs + pilot selector -->
<!-- ============================================ -->

<!-- Section 1: Unassociated documents (no pilot) -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-building"></i> <?= $this->lang->line('archived_documents_unassociated') ?></h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($unassociated_documents)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_type') ?></th>
                        <th><?= $this->lang->line('archived_documents_description') ?></th>
                        <th><?= $this->lang->line('archived_documents_section') ?></th>
                        <th><?= $this->lang->line('archived_documents_file') ?></th>
                        <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                        <th><?= $this->lang->line('archived_documents_status') ?></th>
                        <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unassociated_documents as $doc): ?>
                    <tr>
                        <td>
                            <?php $type_label = !empty($doc['type_name']) ? $doc['type_name'] : $this->lang->line('archived_documents_type_other'); ?>
                            <?= htmlspecialchars($type_label) ?>
                        </td>
                        <td><?= htmlspecialchars($doc['description'] ?? '') ?></td>
                        <td>
                            <?php if (!empty($doc['section_name'])): ?>
                                <?= htmlspecialchars($doc['section_name']) ?>
                            <?php else: ?>
                                <span class="text-muted">Club</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $preview_url = site_url('archived_documents/preview/' . $doc['id']); ?>
                            <?= attachment($doc['id'], $doc['file_path'], $preview_url) ?>
                        </td>
                        <td>
                            <?php if ($doc['valid_until']): ?>
                                <?= date('d/m/Y', strtotime($doc['valid_until'])) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status = $doc['expiration_status'];
                            $badge_class = Archived_documents_model::status_badge_class($status);
                            $status_label = Archived_documents_model::status_label($status);
                            ?>
                            <span class="badge <?= $badge_class ?>"><?= $status_label ?></span>
                            <?php if ($doc['alarm_disabled']): ?>
                                <span class="badge bg-secondary" title="<?= $this->lang->line('archived_documents_alarm_disabled') ?>">
                                    <i class="fas fa-bell-slash"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($doc['uploaded_at'])) ?></td>
                        <td>
                            <a href="<?= site_url('archived_documents/view/' . $doc['id']) ?>" class="btn btn-sm btn-outline-primary" title="<?= $this->lang->line('archived_documents_view') ?>">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= site_url('archived_documents/download/' . $doc['id']) ?>" class="btn btn-sm btn-outline-secondary" title="<?= $this->lang->line('archived_documents_download') ?>">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="<?= site_url('archived_documents/delete/' . $doc['id']) ?>"
                               class="btn btn-sm btn-outline-danger"
                               title="<?= $this->lang->line('archived_documents_delete') ?>"
                               onclick="return confirm('<?= $this->lang->line('archived_documents_confirm_delete') ?>');">
                                <i class="fas fa-trash"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning toggle-alarm"
                                    data-id="<?= $doc['id'] ?>"
                                    title="<?= $this->lang->line('archived_documents_toggle_alarm') ?>">
                                <i class="fas <?= $doc['alarm_disabled'] ? 'fa-bell' : 'fa-bell-slash' ?>"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-3 text-muted">
            <?= $this->lang->line('archived_documents_no_documents') ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Section 2: Pilot selector + pilot documents -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user"></i> <?= $this->lang->line('archived_documents_pilot_documents') ?></h5>
    </div>
    <div class="card-body">
        <form method="get" action="<?= site_url('archived_documents/page') ?>" class="row g-3 align-items-end mb-3">
            <div class="col-auto">
                <label for="pilot" class="form-label"><?= $this->lang->line('archived_documents_select_pilot') ?></label>
                <?= form_dropdown('pilot', $pilot_selector, isset($selected_pilot) ? $selected_pilot : '', 'id="pilot" class="form-select big_select"') ?>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?= $this->lang->line('archived_documents_show') ?>
                </button>
            </div>
        </form>

        <?php if (isset($selected_pilot) && $selected_pilot): ?>
            <h5><?= $this->lang->line('archived_documents_documents_of') ?> <?= htmlspecialchars($pilot_name) ?></h5>

            <!-- Missing required documents for selected pilot -->
            <?php if (!empty($pilot_missing)): ?>
            <div class="alert alert-warning">
                <h6><i class="fas fa-exclamation-circle"></i> <?= $this->lang->line('archived_documents_required_missing') ?></h6>
                <ul class="mb-0">
                    <?php foreach ($pilot_missing as $type): ?>
                    <li><?= htmlspecialchars($type['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($pilot_documents)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('archived_documents_type') ?></th>
                            <th><?= $this->lang->line('archived_documents_description') ?></th>
                            <th><?= $this->lang->line('archived_documents_file') ?></th>
                            <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                            <th><?= $this->lang->line('archived_documents_status') ?></th>
                            <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pilot_documents as $doc): ?>
                        <tr>
                            <td>
                                <?php $type_label = !empty($doc['type_name']) ? $doc['type_name'] : $this->lang->line('archived_documents_type_other'); ?>
                                <?= htmlspecialchars($type_label) ?>
                            </td>
                            <td><?= htmlspecialchars($doc['description'] ?? '') ?></td>
                            <td>
                                <?php $preview_url = site_url('archived_documents/preview/' . $doc['id']); ?>
                                <?= attachment($doc['id'], $doc['file_path'], $preview_url) ?>
                            </td>
                            <td>
                                <?php if ($doc['valid_until']): ?>
                                    <?= date('d/m/Y', strtotime($doc['valid_until'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status = $doc['expiration_status'];
                                $badge_class = Archived_documents_model::status_badge_class($status);
                                $status_label = Archived_documents_model::status_label($status);
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= $status_label ?></span>
                                <?php if ($doc['alarm_disabled']): ?>
                                    <span class="badge bg-secondary" title="<?= $this->lang->line('archived_documents_alarm_disabled') ?>">
                                        <i class="fas fa-bell-slash"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($doc['uploaded_at'])) ?></td>
                            <td>
                                <a href="<?= site_url('archived_documents/view/' . $doc['id']) ?>" class="btn btn-sm btn-outline-primary" title="<?= $this->lang->line('archived_documents_view') ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= site_url('archived_documents/download/' . $doc['id']) ?>" class="btn btn-sm btn-outline-secondary" title="<?= $this->lang->line('archived_documents_download') ?>">
                                    <i class="fas fa-download"></i>
                                </a>
                                <a href="<?= site_url('archived_documents/delete/' . $doc['id']) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   title="<?= $this->lang->line('archived_documents_delete') ?>"
                                   onclick="return confirm('<?= $this->lang->line('archived_documents_confirm_delete') ?>');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-warning toggle-alarm"
                                        data-id="<?= $doc['id'] ?>"
                                        title="<?= $this->lang->line('archived_documents_toggle_alarm') ?>">
                                    <i class="fas <?= $doc['alarm_disabled'] ? 'fa-bell' : 'fa-bell-slash' ?>"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <?= $this->lang->line('archived_documents_no_documents') ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle alarm buttons
    document.querySelectorAll('.toggle-alarm').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var row = this.closest('tr');

            fetch('<?= site_url('archived_documents/toggle_alarm/') ?>' + id, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var icon = btn.querySelector('i');
                    if (data.alarm_disabled) {
                        icon.classList.remove('fa-bell-slash');
                        icon.classList.add('fa-bell');
                        // On expired filter, remove the row when alarm disabled
                        <?php if ($filter === 'expired'): ?>
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        setTimeout(function() { row.remove(); }, 300);
                        <?php endif; ?>
                    } else {
                        icon.classList.remove('fa-bell');
                        icon.classList.add('fa-bell-slash');
                    }
                }
            });
        });
    });

    // Reject modal buttons (pending filter)
    document.querySelectorAll('.btn-reject').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var name = this.dataset.name;
            document.getElementById('rejectForm').action = '<?= site_url('archived_documents/reject/') ?>' + id;
            document.getElementById('rejectDocName').textContent = name;
            var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        });
    });
});
</script>
