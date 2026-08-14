<!-- VIEW: application/views/acceptance/bs_dashboardView.php -->
<?php
/**
 * Member dashboard: elements the current user still has to accept/refuse
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('acceptance');
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-clipboard-check"></i> <?= $this->lang->line('acceptance_dashboard_title') ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<p>
    <?= $this->lang->line('acceptance_dashboard_intro') ?>
    <a href="<?= site_url('acceptance/history') ?>"><?= $this->lang->line('acceptance_history_title') ?></a>
</p>

<?php if (empty($items)): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $this->lang->line('acceptance_dashboard_empty') ?></div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 <?= $item['is_overdue'] ? 'border-danger' : ($item['is_near_deadline'] ? 'border-warning' : '') ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($item['title']) ?></h5>

                        <div class="mb-2">
                            <?php if ($item['mandatory_level'] === 'mandatory_hard'): ?>
                                <span class="badge bg-danger"><?= $this->lang->line('acceptance_mandatory_hard') ?></span>
                            <?php elseif ($item['mandatory_level'] === 'mandatory_soft'): ?>
                                <span class="badge bg-warning text-dark"><?= $this->lang->line('acceptance_mandatory_soft') ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= $this->lang->line('acceptance_mandatory_optional') ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($item['deadline'])): ?>
                            <p class="<?= $item['is_overdue'] ? 'text-danger fw-bold' : ($item['is_near_deadline'] ? 'text-warning fw-bold' : 'text-muted') ?> small mb-2">
                                <i class="fas fa-hourglass-half"></i>
                                <?= sprintf($this->lang->line('acceptance_dashboard_deadline'), date('d/m/Y', strtotime($item['deadline']))) ?>
                                <?php if ($item['is_overdue']): ?>
                                    &mdash; <?= $this->lang->line('acceptance_overdue') ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <div class="mt-auto d-flex gap-2">
                            <a href="<?= site_url('acceptance/read/' . $item['id']) ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-book-open"></i> <?= $this->lang->line('acceptance_btn_read_accept') ?>
                            </a>
                            <?php if ($item['can_postpone']): ?>
                                <a href="<?= site_url('acceptance') ?>" class="btn btn-outline-secondary btn-sm" title="<?= $this->lang->line('acceptance_btn_later_help') ?>">
                                    <i class="fas fa-clock"></i> <?= $this->lang->line('acceptance_btn_later') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div>
