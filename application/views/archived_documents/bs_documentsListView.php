<!-- VIEW: application/views/archived_documents/bs_documentsListView.php -->
<?php
/**
 * Admin documents list view (datatable + filters)
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('archived_documents');

$filters = isset($filters) ? $filters : array();
$filter_expired = !empty($filters['expired']);
$filter_pending = !empty($filters['pending']);
$filter_type = isset($filters['document_type_id']) ? $filters['document_type_id'] : '';
$filter_section = isset($filters['section_id']) ? $filters['section_id'] : '';
$filter_pilot = isset($filters['pilot_login']) ? $filters['pilot_login'] : '';
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-archive"></i> <?= $this->lang->line('archived_documents_all_documents') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<form method="get" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="filter_expired" id="filter_expired" value="1" <?= $filter_expired ? 'checked' : '' ?>>
                <label class="form-check-label" for="filter_expired">
                    <?= $this->lang->line('archived_documents_expired') ?> (<?= isset($expired_count) ? $expired_count : 0 ?>)
                </label>
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="filter_pending" id="filter_pending" value="1" <?= $filter_pending ? 'checked' : '' ?>>
                <label class="form-check-label" for="filter_pending">
                    <?= $this->lang->line('archived_documents_pending_documents') ?> (<?= isset($pending_count) ? $pending_count : 0 ?>)
                </label>
            </div>
        </div>
        <div class="col-sm-2">
            <label for="document_type_id" class="form-label"><?= $this->lang->line('archived_documents_type') ?></label>
            <?= form_dropdown('document_type_id', $type_selector, $filter_type, 'class="form-select" id="document_type_id"') ?>
        </div>
        <div class="col-sm-2">
            <label for="section_id" class="form-label"><?= $this->lang->line('archived_documents_section') ?></label>
            <?= form_dropdown('section_id', $section_selector, $filter_section, 'class="form-select" id="section_id"') ?>
        </div>
        <div class="col-sm-3">
            <label for="pilot_login" class="form-label"><?= $this->lang->line('archived_documents_pilot') ?></label>
            <?= form_dropdown('pilot_login', $pilot_selector, $filter_pilot, 'class="form-select big_select" id="pilot_login"') ?>
        </div>
        <div class="col-sm-1">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-filter"></i>
            </button>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="datatable table table-striped">
        <thead>
            <tr>
                <th><?= $this->lang->line('archived_documents_type') ?></th>
                <th><?= $this->lang->line('archived_documents_pilot') ?></th>
                <th><?= $this->lang->line('archived_documents_section') ?></th>
                <th><?= $this->lang->line('archived_documents_file') ?></th>
                <th><?= $this->lang->line('archived_documents_description') ?></th>
                <th><?= $this->lang->line('archived_documents_valid_until') ?></th>
                <th><?= $this->lang->line('archived_documents_uploaded_at') ?></th>
                <th><?= $this->lang->line('archived_documents_status') ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documents as $doc): ?>
            <tr>
                <td>
                    <?php $type_label = !empty($doc['type_name']) ? $doc['type_name'] : $this->lang->line('archived_documents_type_other'); ?>
                    <?= htmlspecialchars($type_label) ?>
                </td>
                <td>
                    <?php if (!empty($doc['pilot_nom'])): ?>
                        <?= htmlspecialchars($doc['pilot_prenom'] . ' ' . $doc['pilot_nom']) ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($doc['section_name'])): ?>
                        <?= htmlspecialchars($doc['section_name']) ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $preview_url = site_url('archived_documents/preview/' . $doc['id']); ?>
                    <?= attachment($doc['id'], $doc['file_path'], $preview_url) ?>
                </td>
                <td><?= htmlspecialchars($doc['description'] ?? '') ?></td>
                <td>
                    <?php if ($doc['valid_until']): ?>
                        <?= date('d/m/Y', strtotime($doc['valid_until'])) ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y', strtotime($doc['uploaded_at'])) ?></td>
                <td>
                    <?php
                    $status = $doc['expiration_status'];
                    $badge_class = Archived_documents_model::status_badge_class($status);
                    $status_label = Archived_documents_model::status_label($status);
                    ?>
                    <span class="badge <?= $badge_class ?>"><?= $status_label ?></span>
                </td>
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
                    <?php if (!empty($is_admin) && !empty($doc['pilot_login']) && !empty($doc['validation_status']) && $doc['validation_status'] === 'pending'): ?>
                          <a href="<?= site_url('archived_documents/approve/' . $doc['id']) ?>"
                              class="btn btn-sm btn-success"
                              title="<?= $this->lang->line('archived_documents_approve') ?>">
                        <i class="fas fa-check"></i>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div>
