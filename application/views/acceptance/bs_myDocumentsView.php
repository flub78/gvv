<!-- VIEW: application/views/acceptance/bs_myDocumentsView.php -->
<?php
/**
 * All documents/items eligible for the current member, each with its
 * personal status: to accept, accepted on <date>, or refused on <date>.
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('acceptance');
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-clipboard-check"></i> <?= $this->lang->line('acceptance_my_documents_title') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<a href="<?= site_url('welcome/section/user') ?>" class="btn btn-link ps-0 mb-2"><i class="fas fa-arrow-left"></i> <?= $this->lang->line('acceptance_back_to_list') ?></a>

<?php if (empty($items)): ?>
    <div class="alert alert-secondary"><?= $this->lang->line('acceptance_my_documents_empty') ?></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?= $this->lang->line('acceptance_item') ?></th>
                    <th><?= $this->lang->line('acceptance_description') ?></th>
                    <th><?= $this->lang->line('acceptance_status') ?></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($item['description'] ?? '')) ?></td>
                        <td>
                            <?php if ($item['status'] === 'accepted'): ?>
                                <span class="badge bg-success"><?= sprintf($this->lang->line('acceptance_status_accepted_on'), date('d/m/Y', strtotime($item['acted_at']))) ?></span>
                            <?php elseif ($item['status'] === 'refused'): ?>
                                <span class="badge bg-warning text-dark"><?= sprintf($this->lang->line('acceptance_status_refused_on'), date('d/m/Y', strtotime($item['acted_at']))) ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger"><?= $this->lang->line('acceptance_status_to_accept') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= site_url('acceptance/read/' . $item['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <?php if ($item['status'] === 'pending'): ?>
                                    <i class="fas fa-book-open"></i> <?= $this->lang->line('acceptance_btn_read_accept') ?>
                                <?php else: ?>
                                    <i class="fas fa-eye"></i> <?= $this->lang->line('acceptance_history_reread') ?>
                                <?php endif; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

</div>
