<!-- VIEW: application/views/newsletter_admin/bs_run.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container-fluid">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <h3 class="mb-0"><i class="fas fa-clipboard-list text-secondary"></i> <?= htmlspecialchars($run['run_id']) ?></h3>
    <a href="<?= site_url('newsletter_admin/runs') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> <?= $this->lang->line('nl_runs_title') ?></a>
</div>

<dl class="row small mb-2">
    <dt class="col-sm-2"><?= $this->lang->line('nl_origin') ?></dt><dd class="col-sm-4"><?= htmlspecialchars($this->lang->line('nl_origin_' . $run['origin']) ?: $run['origin']) ?></dd>
    <dt class="col-sm-2"><?= $this->lang->line('nl_status') ?></dt><dd class="col-sm-4"><?= htmlspecialchars($this->lang->line('nl_run_' . $run['status']) ?: $run['status']) ?></dd>
    <dt class="col-sm-2"><?= $this->lang->line('nl_context_title') ?></dt>
    <dd class="col-sm-4"><?php if ($context): ?><a href="<?= site_url('newsletter_admin/context_version/' . $context['id']) ?>">v<?= (int) $context['version'] ?></a><?php else: ?>—<?php endif; ?></dd>
    <dt class="col-sm-2"><?= $this->lang->line('nl_edition') ?></dt>
    <dd class="col-sm-4"><?php if ($newsletter): ?><a href="<?= site_url('newsletter_admin/edition/' . $newsletter['id']) ?>"><?= htmlspecialchars($newsletter['title']) ?></a><?php else: ?>—<?php endif; ?></dd>
    <dt class="col-sm-2"><?= $this->lang->line('nl_model') ?></dt><dd class="col-sm-4"><?= htmlspecialchars((string) $run['model']) ?></dd>
    <dt class="col-sm-2"><?= $this->lang->line('nl_cost') ?></dt><dd class="col-sm-4"><?= $run['cost_usd'] !== NULL ? htmlspecialchars($run['cost_usd']) . ' $' : '—' ?></dd>
    <dt class="col-sm-2"><?= $this->lang->line('nl_imported_at') ?></dt><dd class="col-sm-4"><?= htmlspecialchars(substr((string) $run['created_at'], 0, 16)) ?> · <?= htmlspecialchars((string) $run['created_by']) ?></dd>
</dl>
<?php if ($run['error']): ?><div class="alert alert-danger small"><?= nl2br(htmlspecialchars($run['error'])) ?></div><?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('newsletter_admin/run_file/' . $run['id'] . '/brouillon') ?>"><i class="fas fa-download"></i> <?= $this->lang->line('nl_import_draft') ?></a>
    <?php if ($report_html !== NULL): ?>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('newsletter_admin/run_file/' . $run['id'] . '/rapport') ?>"><i class="fas fa-download"></i> <?= $this->lang->line('nl_import_report') ?></a>
    <?php endif; ?>
    <?php if ($has_inventory): ?>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('newsletter_admin/run_file/' . $run['id'] . '/inventaire') ?>"><i class="fas fa-download"></i> <?= $this->lang->line('nl_import_inventory') ?></a>
    <?php endif; ?>
</div>

<h5><?= $this->lang->line('nl_activity_report') ?></h5>
<?php if ($report_html === NULL): ?>
<div class="alert alert-warning"><?= $this->lang->line('nl_no_report') ?></div>
<?php else: ?>
<div class="border rounded p-3 small" style="overflow-x: auto;"><?= $report_html ?></div>
<?php endif; ?>

</div>
