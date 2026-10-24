<!-- VIEW: application/views/newsletter_admin/bs_editions.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container-fluid">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0"><i class="fas fa-newspaper text-info"></i> <?= $this->lang->line('nl_editions_title') ?></h3>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= site_url('newsletter_admin/import') ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-file-import"></i> <?= $this->lang->line('nl_import_title') ?></a>
        <a href="<?= site_url('newsletter_admin/kit') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-box-open"></i> <?= $this->lang->line('nl_kit_title') ?></a>
        <a href="<?= site_url('newsletter_admin/context') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-scroll"></i> <?= $this->lang->line('nl_context_title') ?></a>
        <a href="<?= site_url('newsletter_admin/runs') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-history"></i> <?= $this->lang->line('nl_runs_title') ?></a>
        <a href="<?= site_url('newsletter_admin') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-users"></i> <?= $this->lang->line('newsletter_subscribers') ?></a>
    </div>
</div>

<?php if (!empty($message)) echo $message; ?>

<?php if (empty($context)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> <?= sprintf($this->lang->line('nl_no_context_hint'), site_url('newsletter_admin/context')) ?>
</div>
<?php endif; ?>

<?php if (empty($newsletters)): ?>
<p class="text-muted"><?= $this->lang->line('nl_no_editions') ?></p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-sm align-middle">
    <thead>
        <tr>
            <th><?= $this->lang->line('newsletter_publish_newsletter_title') ?></th>
            <th><?= $this->lang->line('newsletter_period') ?></th>
            <th><?= $this->lang->line('nl_status') ?></th>
            <th><?= $this->lang->line('nl_round') ?></th>
            <th><?= $this->lang->line('nl_updated_at') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($newsletters as $n): ?>
        <tr>
            <td><a href="<?= site_url('newsletter_admin/edition/' . $n['id']) ?>"><?= htmlspecialchars($n['title']) ?></a></td>
            <td><?= date('d/m/Y', strtotime($n['period_start'])) ?> – <?= date('d/m/Y', strtotime($n['period_end'])) ?></td>
            <td><?php $this->load->view('newsletter_admin/_status_badge', array('status' => $n['status'])); ?></td>
            <td><?= (int) $n['review_iteration'] ?></td>
            <td><?= htmlspecialchars(substr((string) $n['updated_at'], 0, 16)) ?></td>
            <td class="text-end">
                <a href="<?= site_url('newsletter_admin/edition/' . $n['id']) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-folder-open"></i> <?= $this->lang->line('nl_open') ?></a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

</div>
