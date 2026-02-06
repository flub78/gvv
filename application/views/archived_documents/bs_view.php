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
    <?php if (isset($is_admin) && $is_admin): ?>
    <button type="button" class="btn btn-sm btn-warning toggle-alarm" data-id="<?= $document['id'] ?>">
        <i class="fas <?= $document['alarm_disabled'] ? 'fa-bell' : 'fa-bell-slash' ?>"></i>
        <?= $document['alarm_disabled'] ? $this->lang->line('archived_documents_enable_alarm') : $this->lang->line('archived_documents_disable_alarm') ?>
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
                $mime = $document['mime_type'];
                $file_path = $document['file_path'];
                $file_url = base_url() . ltrim($file_path, './');
                ?>
                <?php if (strpos($mime, 'image/') === 0): ?>
                    <img src="<?= $file_url ?>" alt="<?= $this->lang->line('archived_documents_preview') ?>" class="img-fluid" style="max-height: 400px;">
                <?php elseif ($mime === 'application/pdf'): ?>
                    <?php
                    // Check for thumbnail
                    $thumb_path = preg_replace('/\.pdf$/i', '_thumb.jpg', $file_path);
                    if (file_exists($thumb_path)):
                        $thumb_url = base_url() . ltrim($thumb_path, './');
                    ?>
                        <img src="<?= $thumb_url ?>" alt="<?= $this->lang->line('archived_documents_preview') ?> PDF" class="img-fluid mb-2" style="max-height: 300px;">
                        <br>
                    <?php endif; ?>
                    <a href="<?= $file_url ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt"></i> <?= $this->lang->line('archived_documents_open_pdf') ?>
                    </a>
                <?php else: ?>
                    <p class="text-muted"><?= $this->lang->line('archived_documents_preview_not_available') ?></p>
                    <a href="<?= site_url('archived_documents/download/' . $document['id']) ?>" class="btn btn-primary">
                        <i class="fas fa-download"></i> <?= $this->lang->line('archived_documents_download') ?>
                    </a>
                <?php endif; ?>
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

<?php if (isset($is_admin) && $is_admin): ?>
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
