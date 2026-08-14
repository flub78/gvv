<!-- VIEW: application/views/acceptance/bs_historyView.php -->
<?php
/**
 * Member personal history: elements already accepted or refused
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('acceptance');
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-history"></i> <?= $this->lang->line('acceptance_history_title') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<a href="<?= site_url('acceptance') ?>" class="btn btn-link ps-0 mb-2"><i class="fas fa-arrow-left"></i> <?= $this->lang->line('acceptance_back_to_list') ?></a>

<?php if (empty($records)): ?>
    <div class="alert alert-secondary"><?= $this->lang->line('acceptance_history_empty') ?></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?= $this->lang->line('acceptance_item') ?></th>
                    <th><?= $this->lang->line('acceptance_status') ?></th>
                    <th><?= $this->lang->line('acceptance_acted_at') ?></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['item_title']) ?></td>
                        <td>
                            <?php if ($record['status'] === 'accepted'): ?>
                                <span class="badge bg-success"><?= $this->lang->line('acceptance_status_accepted') ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><?= $this->lang->line('acceptance_status_refused') ?></span>
                            <?php endif; ?>
                            <?php if (!empty($record['was_overdue'])): ?>
                                <span class="badge bg-danger"><?= $this->lang->line('acceptance_overdue') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($record['acted_at']) ? date('d/m/Y H:i', strtotime($record['acted_at'])) : '' ?></td>
                        <td>
                            <a href="<?= site_url('acceptance/read/' . $record['item_id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye"></i> <?= $this->lang->line('acceptance_history_reread') ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

</div>
