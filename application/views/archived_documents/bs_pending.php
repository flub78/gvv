<!-- VIEW: application/views/archived_documents/bs_pending.php -->
<?php
/**
 * Admin view for documents pending validation
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-clock"></i> <?= $this->lang->line('archived_documents_pending_documents') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<div class="mb-3">
    <a href="<?= site_url('archived_documents/page') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-list"></i> <?= $this->lang->line('archived_documents_all_documents') ?>
    </a>
    <a href="<?= site_url('archived_documents/my_documents') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-user"></i> <?= $this->lang->line('archived_documents_my_documents') ?>
    </a>
</div>

<?php if (!empty($pending_documents)): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?= $this->lang->line('archived_documents_pilot') ?></th>
                <th><?= $this->lang->line('archived_documents_type') ?></th>
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
<?php else: ?>
<div class="alert alert-info">
    <?= $this->lang->line('archived_documents_no_documents') ?>
</div>
<?php endif; ?>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
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
