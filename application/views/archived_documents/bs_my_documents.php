<!-- VIEW: application/views/archived_documents/bs_my_documents.php -->
<?php
/**
 * View for pilot's own documents list
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');

$is_bureau       = !empty($is_bureau);
$is_strict_admin = !empty($is_strict_admin);
$current_user    = isset($current_user) ? $current_user : (isset($pilot_login) ? $pilot_login : '');
?>

<div id="body" class="body container-fluid">

<h3><?= isset($title) ? $title : $this->lang->line('archived_documents_my_documents') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<!-- Add document button -->
<div class="mb-3">
<?php
$current_user = $this->dx_auth->get_username();
if (isset($is_admin) && $is_admin && isset($pilot_login) && $pilot_login !== $current_user):
    // Admin viewing another pilot's documents: link to admin form pre-filled with this pilot
?>
    <a href="<?= site_url('archived_documents/create?pilot=' . urlencode($pilot_login)) ?>" class="btn btn-sm btn-success">
        <i class="fas fa-plus"></i> <?= $this->lang->line('archived_documents_add') ?>
    </a>
<?php else: ?>
    <a href="<?= site_url('archived_documents/create_pilot') ?>" class="btn btn-sm btn-success">
        <i class="fas fa-plus"></i> <?= $this->lang->line('archived_documents_add_pilot') ?>
    </a>
<?php endif; ?>
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

<!-- Pilot documents -->
<h5 class="mt-4"><i class="fas fa-user"></i> <?= $this->lang->line('archived_documents_pilot_documents_section') ?></h5>
<?php if (!empty($documents)): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?= $this->lang->line('archived_documents_type') ?></th>
                <th><?= $this->lang->line('archived_documents_machine') ?></th>
                <th><?= $this->lang->line('archived_documents_description') ?></th>
                <th><?= $this->lang->line('archived_documents_file') ?></th>
                <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                <th><?= $this->lang->line('archived_documents_status') ?></th>
                <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documents as $doc): ?>
            <?php
            $pilot_doc_private = !empty($doc['is_private']);
            $pilot_can_see_file = !$pilot_doc_private || $is_bureau || $is_strict_admin
                || (!empty($current_user) && $doc['pilot_login'] === $current_user);
            ?>
            <tr>
                <td>
                    <?php $type_label = !empty($doc['type_name']) ? $doc['type_name'] : $this->lang->line('archived_documents_type_other'); ?>
                    <?= htmlspecialchars($type_label) ?>
                    <?php if ($pilot_doc_private): ?><span class="badge bg-secondary ms-1"><i class="fas fa-lock"></i></span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars(!empty($doc['machine_immat']) ? $doc['machine_immat'] : '') ?></td>
                <td><?= htmlspecialchars(!empty($doc['description']) ? $doc['description'] : '') ?></td>
                <td>
                    <?php if ($pilot_can_see_file): ?>
                    <?php $preview_url = site_url('archived_documents/preview/' . $doc['id']); ?>
                    <?= attachment($doc['id'], $doc['file_path'], $preview_url) ?>
                    <?php else: ?>
                    <span class="text-muted" title="<?= $this->lang->line('archived_documents_no_file_access') ?>"><i class="fas fa-lock"></i></span>
                    <?php endif; ?>
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
                    <a href="<?= site_url('archived_documents/edit_doc/' . $doc['id']) ?>" class="btn btn-sm btn-outline-secondary" title="<?= $this->lang->line('archived_documents_edit') ?>">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="<?= site_url('archived_documents/new_version/' . $doc['id']) ?>" class="btn btn-sm btn-outline-success" title="<?= $this->lang->line('archived_documents_new_version') ?>">
                        <i class="fas fa-code-branch"></i>
                    </a>
                    <?php if ($pilot_can_see_file): ?>
                    <a href="<?= site_url('archived_documents/download/' . $doc['id']) ?>" class="btn btn-sm btn-outline-secondary" title="<?= $this->lang->line('archived_documents_download') ?>">
                        <i class="fas fa-download"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (empty($doc['validation_status']) || $doc['validation_status'] !== 'approved'): ?>
                    <a href="<?= site_url('archived_documents/delete/' . $doc['id']) ?>"
                       class="btn btn-sm btn-outline-danger"
                       title="<?= $this->lang->line('archived_documents_delete') ?>"
                       onclick="return confirm('<?= $this->lang->line('archived_documents_confirm_delete') ?>');">
                        <i class="fas fa-trash"></i>
                    </a>
                    <?php endif; ?>
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

<!-- Section documents -->
<h5 class="mt-4"><i class="fas fa-layer-group"></i> <?= $this->lang->line('archived_documents_section_documents_section') ?></h5>
<?php if (!empty($section_documents)): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?= $this->lang->line('archived_documents_type') ?></th>
                <th><?= $this->lang->line('archived_documents_machine') ?></th>
                <th><?= $this->lang->line('archived_documents_description') ?></th>
                <th><?= $this->lang->line('archived_documents_file') ?></th>
                <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($section_documents as $doc): ?>
            <?php
            $sec_doc_private = !empty($doc['is_private']);
            $sec_can_see_file = !$sec_doc_private || $is_bureau || $is_strict_admin;
            ?>
            <tr>
                <td>
                    <?php $type_label = !empty($doc['type_name']) ? $doc['type_name'] : $this->lang->line('archived_documents_type_other'); ?>
                    <?= htmlspecialchars($type_label) ?>
                    <?php if ($sec_doc_private): ?><span class="badge bg-secondary ms-1"><i class="fas fa-lock"></i></span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars(!empty($doc['machine_immat']) ? $doc['machine_immat'] : '') ?></td>
                <td><?= htmlspecialchars(!empty($doc['description']) ? $doc['description'] : '') ?></td>
                <td>
                    <?php if ($sec_can_see_file): ?>
                    <?php $preview_url = site_url('archived_documents/preview/' . $doc['id']); ?>
                    <?= attachment($doc['id'], $doc['file_path'], $preview_url) ?>
                    <?php else: ?>
                    <span class="text-muted" title="<?= $this->lang->line('archived_documents_no_file_access') ?>"><i class="fas fa-lock"></i></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($doc['valid_until']): ?>
                        <?= date('d/m/Y', strtotime($doc['valid_until'])) ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y', strtotime($doc['uploaded_at'])) ?></td>
                <td>
                    <a href="<?= site_url('archived_documents/view/' . $doc['id']) ?>" class="btn btn-sm btn-outline-primary" title="<?= $this->lang->line('archived_documents_view') ?>">
                        <i class="fas fa-eye"></i>
                    </a>
                    <?php if ($sec_can_see_file): ?>
                    <a href="<?= site_url('archived_documents/download/' . $doc['id']) ?>" class="btn btn-sm btn-outline-secondary" title="<?= $this->lang->line('archived_documents_download') ?>">
                        <i class="fas fa-download"></i>
                    </a>
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

<!-- Club documents -->
<h5 class="mt-4"><i class="fas fa-building"></i> <?= $this->lang->line('archived_documents_club_documents_section') ?></h5>
<?php if (!empty($club_documents)): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?= $this->lang->line('archived_documents_type') ?></th>
                <th><?= $this->lang->line('archived_documents_machine') ?></th>
                <th><?= $this->lang->line('archived_documents_description') ?></th>
                <th><?= $this->lang->line('archived_documents_file') ?></th>
                <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($club_documents as $doc): ?>
            <?php
            $club_doc_private = !empty($doc['is_private']);
            $club_can_see_file = !$club_doc_private || $is_bureau || $is_strict_admin;
            ?>
            <tr>
                <td>
                    <?php $type_label = !empty($doc['type_name']) ? $doc['type_name'] : $this->lang->line('archived_documents_type_other'); ?>
                    <?= htmlspecialchars($type_label) ?>
                    <?php if ($club_doc_private): ?><span class="badge bg-secondary ms-1"><i class="fas fa-lock"></i></span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars(!empty($doc['machine_immat']) ? $doc['machine_immat'] : '') ?></td>
                <td><?= htmlspecialchars(!empty($doc['description']) ? $doc['description'] : '') ?></td>
                <td>
                    <?php if ($club_can_see_file): ?>
                    <?php $preview_url = site_url('archived_documents/preview/' . $doc['id']); ?>
                    <?= attachment($doc['id'], $doc['file_path'], $preview_url) ?>
                    <?php else: ?>
                    <span class="text-muted" title="<?= $this->lang->line('archived_documents_no_file_access') ?>"><i class="fas fa-lock"></i></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($doc['valid_until']): ?>
                        <?= date('d/m/Y', strtotime($doc['valid_until'])) ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y', strtotime($doc['uploaded_at'])) ?></td>
                <td>
                    <a href="<?= site_url('archived_documents/view/' . $doc['id']) ?>" class="btn btn-sm btn-outline-primary" title="<?= $this->lang->line('archived_documents_view') ?>">
                        <i class="fas fa-eye"></i>
                    </a>
                    <?php if ($club_can_see_file): ?>
                    <a href="<?= site_url('archived_documents/download/' . $doc['id']) ?>" class="btn btn-sm btn-outline-secondary" title="<?= $this->lang->line('archived_documents_download') ?>">
                        <i class="fas fa-download"></i>
                    </a>
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

            fetch('<?= site_url('archived_documents/toggle_alarm') ?>/' + id, {
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
