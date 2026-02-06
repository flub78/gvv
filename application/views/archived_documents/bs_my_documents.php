<!-- VIEW: application/views/archived_documents/bs_my_documents.php -->
<?php
/**
 * View for pilot's own documents list
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');
?>

<div id="body" class="body container-fluid">

<h3><?= isset($title) ? $title : $this->lang->line('archived_documents_my_documents') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<!-- Add document button -->
<div class="mb-3">
    <a href="<?= site_url('archived_documents/create_pilot') ?>" class="btn btn-sm btn-success">
        <i class="fas fa-plus"></i> <?= $this->lang->line('archived_documents_add_pilot') ?>
    </a>
</div>

<!-- Missing required documents -->
<?php if (!empty($missing)): ?>
<div class="alert alert-warning">
    <h5><i class="fas fa-exclamation-circle"></i> <?= $this->lang->line('archived_documents_required_missing') ?></h5>
    <ul class="mb-0">
        <?php foreach ($missing as $type): ?>
        <li>
            <?= htmlspecialchars($type['name']) ?>
            <a href="<?= site_url('archived_documents/create_pilot?type=' . $type['id']) ?>" class="btn btn-sm btn-outline-primary ms-2">
                <i class="fas fa-plus"></i> Ajouter
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Documents list -->
<?php if (!empty($documents)): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?= $this->lang->line('archived_documents_type') ?></th>
                <th><?= $this->lang->line('archived_documents_file') ?></th>
                <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                <th><?= $this->lang->line('archived_documents_status') ?></th>
                <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documents as $doc): ?>
            <tr>
                <td><?= htmlspecialchars($doc['type_name']) ?></td>
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
                    <?php if (!empty($doc['rejection_reason'])): ?>
                        <span class="text-danger small d-block"><?= htmlspecialchars($doc['rejection_reason']) ?></span>
                    <?php endif; ?>
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
                    <?php if (isset($is_bureau) && $is_bureau): ?>
                    <button type="button" class="btn btn-sm btn-outline-warning toggle-alarm"
                            data-id="<?= $doc['id'] ?>"
                            data-disabled="<?= $doc['alarm_disabled'] ?>"
                            title="<?= $this->lang->line('archived_documents_toggle_alarm') ?>">
                        <i class="fas <?= $doc['alarm_disabled'] ? 'fa-bell' : 'fa-bell-slash' ?>"></i>
                    </button>
                    <?php endif; ?>
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
                    } else {
                        icon.classList.remove('fa-bell');
                        icon.classList.add('fa-bell-slash');
                    }
                    btn.dataset.disabled = data.alarm_disabled;
                }
            });
        });
    });
});
</script>
<?php endif; ?>
