<!-- VIEW: application/views/archived_documents/bs_tableView.php -->
<?php
/**
 * Table view for all documents (admin only)
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-archive"></i> <?= $this->lang->line('archived_documents_all_documents') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<div class="mb-3">
    <a href="<?= site_url('archived_documents/my_documents') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-user"></i> <?= $this->lang->line('archived_documents_my_documents') ?>
    </a>
    <a href="<?= site_url('archived_documents/expired') ?>" class="btn btn-sm btn-outline-warning">
        <i class="fas fa-exclamation-triangle"></i> <?= $this->lang->line('archived_documents_expired') ?>
    </a>
    <a href="<?= site_url('archived_documents/create') ?>" class="btn btn-sm btn-success">
        <i class="fas fa-plus"></i> <?= $this->lang->line('archived_documents_add') ?>
    </a>
</div>

<?php if (!empty($select_result)): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover datatable">
        <thead>
            <tr>
                <th><?= $this->lang->line('archived_documents_pilot') ?></th>
                <th><?= $this->lang->line('archived_documents_type') ?></th>
                <th><?= $this->lang->line('archived_documents_file') ?></th>
                <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                <th><?= $this->lang->line('archived_documents_status') ?></th>
                <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($select_result as $doc): ?>
            <tr>
                <td>
                    <?php if ($doc['pilot_login']): ?>
                    <a href="<?= site_url('archived_documents/pilot_documents/' . $doc['pilot_login']) ?>">
                        <?= htmlspecialchars($doc['pilot_prenom'] . ' ' . $doc['pilot_nom']) ?>
                    </a>
                    <?php elseif ($doc['section_name']): ?>
                        <span class="text-muted"><?= htmlspecialchars($doc['section_name']) ?></span>
                    <?php else: ?>
                        <span class="text-muted">Club</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($doc['type_name']) ?></td>
                <td>
                    <a href="<?= site_url('archived_documents/download/' . $doc['id']) ?>">
                        <?= htmlspecialchars($doc['original_filename']) ?>
                    </a>
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

</div>

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
                }
            });
        });
    });
});
</script>
