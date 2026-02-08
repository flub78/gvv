<!-- VIEW: application/views/acceptance_admin/bs_itemsListView.php -->
<?php
/**
 * Admin list of acceptance items with filters
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('acceptance');

$filter_category = isset($filter_category) ? $filter_category : '';
$filter_active = isset($filter_active) ? $filter_active : '';
$filter_overdue = isset($filter_overdue) ? $filter_overdue : '';
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-clipboard-check"></i> <?= $this->lang->line('acceptance_admin_title') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<?php if (isset($message) && $message): ?>
    <?= $message ?>
<?php endif; ?>

<!-- Filters -->
<form method="get" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-2">
            <label for="filter_category" class="form-label"><?= $this->lang->line('acceptance_category') ?></label>
            <?= form_dropdown('filter_category', array(
                '' => $this->lang->line('acceptance_filter_all'),
                'document' => $this->lang->line('acceptance_category_document'),
                'formation' => $this->lang->line('acceptance_category_formation'),
                'controle' => $this->lang->line('acceptance_category_controle'),
                'briefing' => $this->lang->line('acceptance_category_briefing'),
                'autorisation' => $this->lang->line('acceptance_category_autorisation')
            ), $filter_category, 'class="form-select" id="filter_category"') ?>
        </div>
        <div class="col-sm-2">
            <label for="filter_active" class="form-label"><?= $this->lang->line('acceptance_active') ?></label>
            <?= form_dropdown('filter_active', array(
                'all' => $this->lang->line('acceptance_filter_all'),
                '1' => $this->lang->line('acceptance_yes'),
                '0' => $this->lang->line('acceptance_no')
            ), $filter_active, 'class="form-select" id="filter_active"') ?>
        </div>
        <div class="col-sm-2">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="filter_overdue" id="filter_overdue" value="1" <?= $filter_overdue ? 'checked' : '' ?>>
                <label class="form-check-label <?= (isset($overdue_count) && $overdue_count > 0) ? 'text-danger fw-bold' : '' ?>" for="filter_overdue">
                    <?= $this->lang->line('acceptance_overdue') ?> (<?= isset($overdue_count) ? $overdue_count : 0 ?>)
                </label>
            </div>
        </div>
        <div class="col-sm-1">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-filter"></i>
            </button>
        </div>
        <div class="col-sm-3 text-end">
            <a href="<?= site_url('acceptance_admin/create') ?>" class="btn btn-success">
                <i class="fas fa-plus"></i> <?= $this->lang->line('acceptance_add_item') ?>
            </a>
        </div>
    </div>
</form>

<!-- Items table -->
<div class="table-responsive">
    <table class="datatable table table-striped">
        <thead>
            <tr>
                <th><?= $this->lang->line('acceptance_title') ?></th>
                <th><?= $this->lang->line('acceptance_category') ?></th>
                <th><?= $this->lang->line('acceptance_target_type') ?></th>
                <th><?= $this->lang->line('acceptance_mandatory') ?></th>
                <th><?= $this->lang->line('acceptance_deadline') ?></th>
                <th><?= $this->lang->line('acceptance_active') ?></th>
                <th><?= $this->lang->line('acceptance_created_by') ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($select_result) && is_array($select_result)): ?>
            <?php foreach ($select_result as $item): ?>
            <?php
                $is_overdue = !empty($item['deadline']) && $item['deadline'] < date('Y-m-d');
                $is_near_deadline = !empty($item['deadline']) && !$is_overdue
                    && $item['deadline'] <= date('Y-m-d', strtotime('+7 days'));
            ?>
            <tr>
                <td>
                    <?= htmlspecialchars($item['title']) ?>
                    <?php if (!empty($item['pdf_path'])): ?>
                        <i class="fas fa-file-pdf text-danger" title="PDF"></i>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $cat_labels = array(
                        'document' => $this->lang->line('acceptance_category_document'),
                        'formation' => $this->lang->line('acceptance_category_formation'),
                        'controle' => $this->lang->line('acceptance_category_controle'),
                        'briefing' => $this->lang->line('acceptance_category_briefing'),
                        'autorisation' => $this->lang->line('acceptance_category_autorisation')
                    );
                    echo isset($cat_labels[$item['category']]) ? $cat_labels[$item['category']] : $item['category'];
                    ?>
                </td>
                <td>
                    <?php if ($item['target_type'] === 'external'): ?>
                        <span class="badge bg-warning text-dark"><?= $this->lang->line('acceptance_target_type_external') ?></span>
                    <?php else: ?>
                        <?= $this->lang->line('acceptance_target_type_internal') ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($item['mandatory']): ?>
                        <span class="badge bg-danger"><?= $this->lang->line('acceptance_yes') ?></span>
                    <?php else: ?>
                        <span class="text-muted"><?= $this->lang->line('acceptance_no') ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($item['deadline'])): ?>
                        <?php if ($is_overdue): ?>
                            <span class="badge bg-danger"><?= date('d/m/Y', strtotime($item['deadline'])) ?></span>
                        <?php elseif ($is_near_deadline): ?>
                            <span class="badge bg-warning text-dark"><?= date('d/m/Y', strtotime($item['deadline'])) ?></span>
                        <?php else: ?>
                            <?= date('d/m/Y', strtotime($item['deadline'])) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($item['active']): ?>
                        <span class="badge bg-success"><?= $this->lang->line('acceptance_yes') ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><?= $this->lang->line('acceptance_no') ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars(isset($item['created_by_name']) ? $item['created_by_name'] : '') ?></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="<?= site_url('acceptance_admin/edit/' . $item['id']) ?>" class="btn btn-outline-primary" title="<?= $this->lang->line('acceptance_edit') ?>">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="<?= site_url('acceptance_admin/tracking/' . $item['id']) ?>" class="btn btn-outline-info" title="<?= $this->lang->line('acceptance_tracking') ?>">
                            <i class="fas fa-chart-bar"></i>
                        </a>
                        <?php if (!empty($item['pdf_path'])): ?>
                        <a href="<?= site_url('acceptance_admin/download/' . $item['id']) ?>" class="btn btn-outline-secondary" title="<?= $this->lang->line('acceptance_download_pdf') ?>">
                            <i class="fas fa-download"></i>
                        </a>
                        <?php endif; ?>
                        <a href="<?= site_url('acceptance_admin/toggle_active/' . $item['id']) ?>"
                           class="btn btn-outline-<?= $item['active'] ? 'warning' : 'success' ?>"
                           title="<?= $item['active'] ? $this->lang->line('acceptance_deactivate') : $this->lang->line('acceptance_activate') ?>"
                           onclick="return confirm('<?= $item['active'] ? $this->lang->line('acceptance_confirm_deactivate') : $this->lang->line('acceptance_confirm_activate') ?>');">
                            <i class="fas fa-<?= $item['active'] ? 'ban' : 'check' ?>"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
