<!-- VIEW: application/views/newsletter_admin/bs_runs.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container-fluid">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0"><i class="fas fa-history text-secondary"></i> <?= $this->lang->line('nl_runs_title') ?></h3>
    <a href="<?= site_url('newsletter_admin/editions') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> <?= $this->lang->line('nl_editions_title') ?></a>
</div>

<?php if (!$runs): ?>
<p class="text-muted"><?= $this->lang->line('nl_runs_none') ?></p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-sm align-middle">
    <thead>
        <tr>
            <th><?= $this->lang->line('nl_run') ?></th>
            <th><?= $this->lang->line('nl_origin') ?></th>
            <th><?= $this->lang->line('nl_status') ?></th>
            <th><?= $this->lang->line('nl_context_title') ?></th>
            <th><?= $this->lang->line('nl_model') ?></th>
            <th class="text-end"><?= $this->lang->line('nl_cost') ?></th>
            <th><?= $this->lang->line('nl_edition') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($runs as $r): ?>
        <tr>
            <td><a href="<?= site_url('newsletter_admin/run/' . $r['id']) ?>"><?= htmlspecialchars($r['run_id']) ?></a></td>
            <td><?= htmlspecialchars($this->lang->line('nl_origin_' . $r['origin']) ?: $r['origin']) ?></td>
            <td><span class="badge <?= $r['status'] === 'succeeded' ? 'bg-success' : ($r['status'] === 'failed' ? 'bg-danger' : 'bg-secondary') ?>">
                <?= htmlspecialchars($this->lang->line('nl_run_' . $r['status']) ?: $r['status']) ?></span></td>
            <td><?= $r['context_version'] ? 'v' . (int) $r['context_version'] : '—' ?></td>
            <td><?= htmlspecialchars((string) $r['model']) ?></td>
            <td class="text-end"><?= $r['cost_usd'] !== NULL ? htmlspecialchars($r['cost_usd']) . ' $' : '' ?></td>
            <td><?php if ($r['newsletter_id']): ?><a href="<?= site_url('newsletter_admin/edition/' . $r['newsletter_id']) ?>"><?= htmlspecialchars((string) $r['newsletter_title']) ?></a><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

</div>
