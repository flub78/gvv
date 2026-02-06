<!-- VIEW: application/views/archived_documents/bs_view.php -->
<?php
/**
 * View for document details
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');

$status = $document['expiration_status'];
$badge_class = Archived_documents_model::status_badge_class($status);
$status_label = Archived_documents_model::status_label($status);
?>

<div id="body" class="body container-fluid">

<h3>
    <i class="fas fa-file"></i> <?= htmlspecialchars($type['name']) ?>
    <span class="badge <?= $badge_class ?>"><?= $status_label ?></span>
</h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<div class="mb-3">
    <a href="<?= site_url('archived_documents/my_documents') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> <?= $this->lang->line('archived_documents_back') ?>
    </a>
    <a href="<?= site_url('archived_documents/download/' . $document['id']) ?>" class="btn btn-sm btn-primary">
        <i class="fas fa-download"></i> <?= $this->lang->line('archived_documents_download') ?>
    </a>
    <a href="<?= site_url('archived_documents/delete/' . $document['id']) ?>"
       class="btn btn-sm btn-danger"
       onclick="return confirm('<?= $this->lang->line('archived_documents_confirm_delete') ?>');">
        <i class="fas fa-trash"></i> <?= $this->lang->line('archived_documents_delete') ?>
    </a>
    <?php if (isset($is_bureau) && $is_bureau): ?>
    <button type="button" class="btn btn-sm btn-warning toggle-alarm" data-id="<?= $document['id'] ?>">
        <i class="fas <?= $document['alarm_disabled'] ? 'fa-bell' : 'fa-bell-slash' ?>"></i>
        <?= $document['alarm_disabled'] ? $this->lang->line('archived_documents_enable_alarm') : $this->lang->line('archived_documents_disable_alarm') ?>
    </button>
    <?php endif; ?>
    <?php if (isset($is_admin) && $is_admin && isset($document['validation_status']) && $document['validation_status'] === 'pending'): ?>
    <a href="<?= site_url('archived_documents/approve/' . $document['id']) ?>"
       class="btn btn-sm btn-success"
       onclick="return confirm('<?= $this->lang->line('archived_documents_approve') ?> ?');">
        <i class="fas fa-check"></i> <?= $this->lang->line('archived_documents_approve') ?>
    </a>
    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
        <i class="fas fa-times"></i> <?= $this->lang->line('archived_documents_reject') ?>
    </button>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Document info -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><?= $this->lang->line('archived_documents_document_info') ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th style="width: 40%"><?= $this->lang->line('archived_documents_file') ?></th>
                        <td><?= htmlspecialchars($document['original_filename']) ?></td>
                    </tr>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_type') ?></th>
                        <td><?= htmlspecialchars($type['name']) ?></td>
                    </tr>
                    <?php if ($document['description']): ?>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_description') ?></th>
                        <td><?= htmlspecialchars($document['description']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_valid_from') ?></th>
                        <td>
                            <?php if ($document['valid_from']): ?>
                                <?= date('d/m/Y', strtotime($document['valid_from'])) ?>
                            <?php else: ?>
                                <span class="text-muted"><?= $this->lang->line('archived_documents_not_defined') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                        <td>
                            <?php if ($document['valid_until']): ?>
                                <?= date('d/m/Y', strtotime($document['valid_until'])) ?>
                            <?php else: ?>
                                <span class="text-muted"><?= $this->lang->line('archived_documents_no_expiration') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_status') ?></th>
                        <td>
                            <span class="badge <?= $badge_class ?>"><?= $status_label ?></span>
                            <?php if ($document['alarm_disabled']): ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-bell-slash"></i> <?= $this->lang->line('archived_documents_alarm_disabled') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                        <td><?= date('d/m/Y H:i', strtotime($document['uploaded_at'])) ?></td>
                    </tr>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_uploaded_by') ?></th>
                        <td><?= htmlspecialchars($document['uploaded_by']) ?></td>
                    </tr>
                    <?php if ($document['file_size']): ?>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_size') ?></th>
                        <td><?= number_format($document['file_size'] / 1024, 1) ?> Ko</td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($document['validation_status']) && $document['validation_status'] !== 'approved'): ?>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_status') ?></th>
                        <td>
                            <span class="badge <?= Archived_documents_model::status_badge_class($document['validation_status'] === 'pending' ? Archived_documents_model::STATUS_PENDING : Archived_documents_model::STATUS_REJECTED) ?>">
                                <?= Archived_documents_model::status_label($document['validation_status'] === 'pending' ? Archived_documents_model::STATUS_PENDING : Archived_documents_model::STATUS_REJECTED) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($document['validated_by'])): ?>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_validated_by') ?></th>
                        <td><?= htmlspecialchars($document['validated_by']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($document['validated_at'])): ?>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_validated_at') ?></th>
                        <td><?= date('d/m/Y H:i', strtotime($document['validated_at'])) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($document['rejection_reason'])): ?>
                    <tr>
                        <th><?= $this->lang->line('archived_documents_rejection_reason') ?></th>
                        <td><span class="text-danger"><?= htmlspecialchars($document['rejection_reason']) ?></span></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Preview -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><?= $this->lang->line('archived_documents_preview') ?></h5>
            </div>
            <div class="card-body text-center">
                <?php
                $preview_url = site_url('archived_documents/preview/' . $document['id']);
                ?>
                <style>.doc-preview-large .doc-thumbnail { width: 300px; max-height: 400px; } .doc-preview-large .fas { font-size: 5em; }</style>
                <div class="mb-3 doc-preview-large">
                    <?= attachment($document['id'], $document['file_path'], $preview_url) ?>
                </div>
                <a href="<?= $preview_url ?>" class="btn btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i> <?= $this->lang->line('archived_documents_preview') ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Version history -->
<?php if (count($versions) > 1): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history"></i> <?= $this->lang->line('archived_documents_versions') ?></h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th><?= $this->lang->line('archived_documents_version') ?></th>
                    <th><?= $this->lang->line('archived_documents_file') ?></th>
                    <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                    <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($versions as $idx => $ver): ?>
                <tr <?= $ver['is_current_version'] ? 'class="table-primary"' : '' ?>>
                    <td>
                        <?php if ($ver['is_current_version']): ?>
                            <span class="badge bg-primary"><?= $this->lang->line('archived_documents_current_version') ?></span>
                        <?php else: ?>
                            <span class="text-muted">v<?= count($versions) - $idx ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($ver['original_filename']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($ver['uploaded_at'])) ?></td>
                    <td>
                        <?php if ($ver['valid_until']): ?>
                            <?= date('d/m/Y', strtotime($ver['valid_until'])) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= site_url('archived_documents/download/' . $ver['id']) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div>

<?php if (isset($is_admin) && $is_admin && isset($document['validation_status']) && $document['validation_status'] === 'pending'): ?>
<!-- Reject modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= site_url('archived_documents/reject/' . $document['id']) ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $this->lang->line('archived_documents_reject') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
<?php endif; ?>

<?php if (isset($is_bureau) && $is_bureau): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-alarm').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var btn = this;

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
                        btn.innerHTML = '<i class="fas fa-bell"></i> <?= $this->lang->line('archived_documents_enable_alarm') ?>';
                    } else {
                        icon.classList.remove('fa-bell');
                        icon.classList.add('fa-bell-slash');
                        btn.innerHTML = '<i class="fas fa-bell-slash"></i> <?= $this->lang->line('archived_documents_disable_alarm') ?>';
                    }
                }
            });
        });
    });
});
</script>
<?php endif; ?>
