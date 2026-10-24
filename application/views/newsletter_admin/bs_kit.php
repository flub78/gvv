<!-- VIEW: application/views/newsletter_admin/bs_kit.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0"><i class="fas fa-box-open text-secondary"></i> <?= $this->lang->line('nl_kit_title') ?></h3>
    <a href="<?= site_url('newsletter_admin/editions') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> <?= $this->lang->line('nl_editions_title') ?></a>
</div>

<p><?= $this->lang->line('nl_kit_intro') ?></p>

<ul class="list-group mb-3">
    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
        <div><strong><?= $this->lang->line('nl_kit_context') ?></strong>
            <div class="small text-muted"><?= $context ? sprintf($this->lang->line('nl_kit_context_version'), (int) $context['version'], htmlspecialchars(substr($context['created_at'], 0, 10)))
                                                       : sprintf($this->lang->line('nl_no_context_hint'), site_url('newsletter_admin/context')) ?></div></div>
        <?php if ($context): ?><a class="btn btn-primary btn-sm" href="<?= site_url('newsletter_admin/kit_file/consignes') ?>"><i class="fas fa-download"></i> .md</a><?php endif; ?>
    </li>
    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
        <div><strong><?= $this->lang->line('nl_kit_inventory') ?></strong>
            <div class="small text-muted"><?= $inventory ? sprintf($this->lang->line('nl_kit_inventory_from'), htmlspecialchars($inventory['run_id']))
                                                         : $this->lang->line('nl_kit_inventory_none') ?></div></div>
        <?php if ($inventory): ?><a class="btn btn-primary btn-sm" href="<?= site_url('newsletter_admin/kit_file/inventaire') ?>"><i class="fas fa-download"></i> .csv</a><?php endif; ?>
    </li>
    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
        <div><strong><?= $this->lang->line('nl_kit_postponed') ?></strong>
            <div class="small text-muted"><?= sprintf($this->lang->line('nl_kit_postponed_count'), count($postponed)) ?></div></div>
        <a class="btn btn-primary btn-sm" href="<?= site_url('newsletter_admin/kit_file/suggestions') ?>"><i class="fas fa-download"></i> .md</a>
    </li>
</ul>

<p class="small text-muted"><?= sprintf($this->lang->line('nl_kit_then_import'), site_url('newsletter_admin/import')) ?></p>

</div>
