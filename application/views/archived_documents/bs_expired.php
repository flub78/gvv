<!-- VIEW: application/views/archived_documents/bs_expired.php -->
<?php
/**
 * View for expired documents list (admin only)
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-exclamation-triangle text-warning"></i> <?= $this->lang->line('archived_documents_expired') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<div class="mb-3">
    <a href="<?= site_url('archived_documents/my_documents') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-user"></i> <?= $this->lang->line('archived_documents_my_documents') ?>
    </a>
    <a href="<?= site_url('archived_documents/page') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-list"></i> <?= $this->lang->line('archived_documents_all_documents') ?>
    </a>
</div>

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
                        <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                        <th><?= $this->lang->line('archived_documents_days_remaining') ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiring_soon as $doc): ?>
                    <?php
                        $days_left = floor((strtotime($doc['valid_until']) - time()) / 86400);
                    ?>
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
                        <td><?= date('d/m/Y', strtotime($doc['valid_until'])) ?></td>
                        <td>
                            <span class="badge bg-warning text-dark"><?= $days_left ?> jours</span>
                        </td>
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
                        <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                        <th><?= $this->lang->line('archived_documents_days_expired') ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <?php
                        $days_expired = floor((time() - strtotime($doc['valid_until'])) / 86400);
                    ?>
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
                        <td><?= date('d/m/Y', strtotime($doc['valid_until'])) ?></td>
                        <td>
                            <span class="badge bg-danger"><?= $days_expired ?> jours</span>
                        </td>
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

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-alarm').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var row = this.closest('tr');

            fetch('<?= site_url('archived_documents/toggle_alarm') ?>/' + id, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.alarm_disabled) {
                    // Remove the row from the table when alarm is disabled
                    row.style.transition = 'opacity 0.3s';
                    row.style.opacity = '0';
                    setTimeout(function() {
                        row.remove();
                    }, 300);
                }
            });
        });
    });
});
</script>
