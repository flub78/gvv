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
$filter_machine = isset($filters['machine_immat']) ? $filters['machine_immat'] : '';
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-archive"></i> <?= $this->lang->line('archived_documents_all_documents') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<div class="mb-3">
    <a href="<?= site_url('archived_documents/create') ?>" class="btn btn-sm btn-success">
        <i class="fas fa-plus"></i> <?= $this->lang->line('archived_documents_add') ?>
    </a>
    <a href="<?= site_url('document_types') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-tags"></i> <?= $this->lang->line('archived_documents_manage_types') ?>
    </a>
</div>

<style>
#doc-filter-form .select2-container { width: 100% !important; }
#doc-filter-form .select2-selection--single {
    height: 38px !important;
    padding: 6px 12px !important;
    border-color: #ced4da !important;
    border-radius: 0.375rem !important;
}
#doc-filter-form .select2-selection__rendered {
    line-height: 24px !important;
    padding: 0 !important;
}
#doc-filter-form .select2-selection__arrow {
    height: 36px !important;
}
</style>
<form method="get" class="mb-3" id="doc-filter-form">
    <div class="row g-2 align-items-end">
        <div class="col-sm-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="filter_expired" id="filter_expired" value="1" <?= $filter_expired ? 'checked' : '' ?>>
                <label class="form-check-label <?= (isset($expired_count) && $expired_count > 0) ? 'text-danger fw-bold' : '' ?>" for="filter_expired">
                    <?= $this->lang->line('archived_documents_expired') ?> (<?= isset($expired_count) ? $expired_count : 0 ?>)
                </label>
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="filter_pending" id="filter_pending" value="1" <?= $filter_pending ? 'checked' : '' ?>>
                <label class="form-check-label <?= (isset($pending_count) && $pending_count > 0) ? 'text-warning fw-bold' : '' ?>" for="filter_pending">
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
        <div class="col-sm-2">
            <label for="pilot_login" class="form-label"><?= $this->lang->line('archived_documents_pilot') ?></label>
            <?= form_dropdown('pilot_login', $pilot_selector, $filter_pilot, 'class="form-select big_select" id="pilot_login"') ?>
        </div>
        <div class="col-sm-2 ps-3">
            <label for="machine_immat" class="form-label"><?= $this->lang->line('archived_documents_machine') ?></label>
            <?php
            $count_machines = count($machine_selector) - 1;
            $select_class = ($count_machines > 10) ? 'form-select big_select' : 'form-select';
            echo form_dropdown('machine_immat', $machine_selector, $filter_machine, 'class="' . $select_class . '" id="machine_immat"');
            ?>
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
                <th><?= $this->lang->line('archived_documents_machine') ?></th>
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
                    <?php if (!empty($doc['machine_immat'])): ?>
                        <?= htmlspecialchars($doc['machine_immat']) ?>
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
                    <?php if ($doc['valid_until']):
                        $is_expired_date = strtotime($doc['valid_until']) < mktime(0,0,0);
                    ?>
                        <span <?= $is_expired_date ? 'class="text-danger fw-bold"' : '' ?>>
                            <?= date('d/m/Y', strtotime($doc['valid_until'])) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y', strtotime($doc['uploaded_at'])) ?></td>
                <td>
                    <?php
                    $is_expired_date = $doc['valid_until'] && strtotime($doc['valid_until']) < mktime(0,0,0);
                    if (!empty($doc['alarm_disabled']) && $is_expired_date):
                    ?>
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-bell-slash"></i> <?= $this->lang->line('archived_documents_alarm_disabled') ?>
                        </span>
                    <?php else:
                        $status = $doc['expiration_status'];
                        $badge_class = Archived_documents_model::status_badge_class($status);
                        $status_label = Archived_documents_model::status_label($status);
                    ?>
                        <span class="badge <?= $badge_class ?>"><?= $status_label ?></span>
                    <?php endif; ?>
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
