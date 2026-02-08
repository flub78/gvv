<!-- VIEW: application/views/acceptance_admin/bs_trackingView.php -->
<?php
/**
 * Tracking view - show acceptance records for a specific item
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('acceptance');

$filter_status = isset($filter_status) ? $filter_status : '';
$filter_linked = isset($filter_linked) ? $filter_linked : '';
?>

<div id="body" class="body container-fluid">

<h3>
    <i class="fas fa-chart-bar"></i> <?= $this->lang->line('acceptance_tracking') ?>
    &mdash; <?= htmlspecialchars($item['title']) ?>
</h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<!-- Item summary card -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <strong><?= $this->lang->line('acceptance_category') ?>:</strong>
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
                &bull;
                <strong><?= $this->lang->line('acceptance_target_type') ?>:</strong>
                <?= $item['target_type'] === 'external' ? $this->lang->line('acceptance_target_type_external') : $this->lang->line('acceptance_target_type_internal') ?>

                <?php if ($item['mandatory']): ?>
                    &bull; <span class="badge bg-danger"><?= $this->lang->line('acceptance_mandatory') ?></span>
                <?php endif; ?>

                <?php if (!empty($item['deadline'])): ?>
                    &bull; <strong><?= $this->lang->line('acceptance_deadline') ?>:</strong>
                    <?php
                    $is_overdue = $item['deadline'] < date('Y-m-d');
                    $badge_class = $is_overdue ? 'bg-danger' : 'bg-info';
                    ?>
                    <span class="badge <?= $badge_class ?>"><?= date('d/m/Y', strtotime($item['deadline'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-end">
                <a href="<?= site_url('acceptance_admin/edit/' . $item['id']) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> <?= $this->lang->line('acceptance_edit') ?>
                </a>
                <a href="<?= site_url('acceptance_admin/page') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> <?= $this->lang->line('acceptance_back_to_list') ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats badges -->
<div class="mb-3">
    <span class="badge bg-primary"><?= $this->lang->line('acceptance_total') ?>: <?= $pending_count + $accepted_count + $refused_count ?></span>
    <span class="badge bg-warning text-dark"><?= $this->lang->line('acceptance_status_pending') ?>: <?= $pending_count ?></span>
    <span class="badge bg-success"><?= $this->lang->line('acceptance_status_accepted') ?>: <?= $accepted_count ?></span>
    <span class="badge bg-secondary"><?= $this->lang->line('acceptance_status_refused') ?>: <?= $refused_count ?></span>
    <?php if ($unlinked_count > 0): ?>
    <span class="badge bg-info"><?= $this->lang->line('acceptance_unlinked') ?>: <?= $unlinked_count ?></span>
    <?php endif; ?>
</div>

<!-- Filters -->
<form method="get" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-2">
            <label for="filter_status" class="form-label"><?= $this->lang->line('acceptance_status') ?></label>
            <?= form_dropdown('filter_status', array(
                '' => $this->lang->line('acceptance_filter_all'),
                'pending' => $this->lang->line('acceptance_status_pending'),
                'accepted' => $this->lang->line('acceptance_status_accepted'),
                'refused' => $this->lang->line('acceptance_status_refused')
            ), $filter_status, 'class="form-select" id="filter_status"') ?>
        </div>
        <div class="col-sm-2">
            <label for="filter_linked" class="form-label"><?= $this->lang->line('acceptance_link_status') ?></label>
            <?= form_dropdown('filter_linked', array(
                '' => $this->lang->line('acceptance_filter_all'),
                'linked' => $this->lang->line('acceptance_linked'),
                'unlinked' => $this->lang->line('acceptance_unlinked')
            ), $filter_linked, 'class="form-select" id="filter_linked"') ?>
        </div>
        <div class="col-sm-1">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-filter"></i>
            </button>
        </div>
    </div>
</form>

<!-- Records table -->
<div class="table-responsive">
    <table class="datatable table table-striped">
        <thead>
            <tr>
                <th><?= $this->lang->line('acceptance_user') ?></th>
                <th><?= $this->lang->line('acceptance_status') ?></th>
                <th><?= $this->lang->line('acceptance_acted_at') ?></th>
                <th><?= $this->lang->line('acceptance_created_at') ?></th>
                <th><?= $this->lang->line('acceptance_signature_mode') ?></th>
                <th><?= $this->lang->line('acceptance_linked_pilot') ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($records) && is_array($records)): ?>
            <?php foreach ($records as $record): ?>
            <tr>
                <td>
                    <?php if (!empty($record['user_login'])): ?>
                        <?php if (!empty($record['pilot_prenom']) || !empty($record['pilot_nom'])): ?>
                            <?= htmlspecialchars(trim($record['pilot_prenom'] . ' ' . $record['pilot_nom'])) ?>
                        <?php else: ?>
                            <?= htmlspecialchars($record['user_login']) ?>
                        <?php endif; ?>
                    <?php elseif (!empty($record['external_name'])): ?>
                        <i class="fas fa-external-link-alt text-warning" title="<?= $this->lang->line('acceptance_target_type_external') ?>"></i>
                        <?= htmlspecialchars($record['external_name']) ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $status_classes = array(
                        'pending' => 'bg-warning text-dark',
                        'accepted' => 'bg-success',
                        'refused' => 'bg-secondary'
                    );
                    $status_labels = array(
                        'pending' => $this->lang->line('acceptance_status_pending'),
                        'accepted' => $this->lang->line('acceptance_status_accepted'),
                        'refused' => $this->lang->line('acceptance_status_refused')
                    );
                    $badge_class = isset($status_classes[$record['status']]) ? $status_classes[$record['status']] : 'bg-secondary';
                    $status_label = isset($status_labels[$record['status']]) ? $status_labels[$record['status']] : $record['status'];
                    ?>
                    <span class="badge <?= $badge_class ?>"><?= $status_label ?></span>
                </td>
                <td>
                    <?php if (!empty($record['acted_at'])): ?>
                        <?= date('d/m/Y H:i', strtotime($record['acted_at'])) ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($record['created_at'])) ?></td>
                <td>
                    <?php if (!empty($record['signature_mode'])): ?>
                        <?php
                        $mode_labels = array(
                            'direct' => $this->lang->line('acceptance_mode_direct'),
                            'link' => $this->lang->line('acceptance_mode_link'),
                            'qrcode' => $this->lang->line('acceptance_mode_qrcode'),
                            'paper' => $this->lang->line('acceptance_mode_paper')
                        );
                        echo isset($mode_labels[$record['signature_mode']]) ? $mode_labels[$record['signature_mode']] : $record['signature_mode'];
                        ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($record['linked_pilot_login'])): ?>
                        <span class="badge bg-success"><i class="fas fa-link"></i></span>
                        <?php if (!empty($record['linked_pilot_prenom']) || !empty($record['linked_pilot_nom'])): ?>
                            <?= htmlspecialchars(trim($record['linked_pilot_prenom'] . ' ' . $record['linked_pilot_nom'])) ?>
                        <?php else: ?>
                            <?= htmlspecialchars($record['linked_pilot_login']) ?>
                        <?php endif; ?>
                    <?php elseif (empty($record['user_login'])): ?>
                        <!-- External record not linked - show link form -->
                        <span class="badge bg-info"><i class="fas fa-unlink"></i> <?= $this->lang->line('acceptance_unlinked') ?></span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (empty($record['linked_pilot_login']) && empty($record['user_login'])): ?>
                        <!-- Link to pilot form -->
                        <?= form_open('acceptance_admin/link_pilot/' . $record['id'], array('class' => 'd-inline-flex gap-1')) ?>
                            <?= form_dropdown('pilot_login', $pilot_selector, '', 'class="form-select form-select-sm" style="width:150px"') ?>
                            <button type="submit" class="btn btn-sm btn-success" title="<?= $this->lang->line('acceptance_link_to_pilot') ?>">
                                <i class="fas fa-link"></i>
                            </button>
                        <?= form_close() ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
