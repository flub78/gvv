<!-- VIEW: application/views/newsletter_admin/bs_import.php -->
<?php
$this->lang->load('newsletter');
$csrf = $this->security->get_csrf_hash()
    ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'
    : '';
?>

<div id="body" class="body container">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0"><i class="fas fa-file-import text-primary"></i> <?= $this->lang->line('nl_import_title') ?></h3>
    <a href="<?= site_url('newsletter_admin/editions') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> <?= $this->lang->line('nl_editions_title') ?></a>
</div>

<p class="text-muted"><?= sprintf($this->lang->line('nl_import_intro'), site_url('newsletter_admin/kit')) ?></p>

<?php if (!empty($result) && !$result['ok']): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><?= $this->lang->line('nl_import_failed') ?></strong>
    <ul class="mb-0"><?php foreach ($result['errors'] as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (empty($context)): ?>
<div class="alert alert-warning small"><?= sprintf($this->lang->line('nl_no_context_hint'), site_url('newsletter_admin/context')) ?></div>
<?php else: ?>
<p class="small"><?= sprintf($this->lang->line('nl_import_context'), (int) $context['version']) ?></p>
<?php endif; ?>

<form method="post" action="<?= site_url('newsletter_admin/import') ?>" enctype="multipart/form-data" class="card card-body">
    <?= $csrf ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= $this->lang->line('newsletter_publish_newsletter_title') ?> *</label>
            <input type="text" name="title" class="form-control" required maxlength="255"
                   value="<?= htmlspecialchars($form['title'] ?? '') ?>" placeholder="Veille sécurité et réglementation – Octobre 2026">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label"><?= $this->lang->line('newsletter_publish_period_start') ?> *</label>
            <input type="date" name="period_start" class="form-control" required value="<?= htmlspecialchars($form['period_start'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label"><?= $this->lang->line('newsletter_publish_period_end') ?> *</label>
            <input type="date" name="period_end" class="form-control" required value="<?= htmlspecialchars($form['period_end'] ?? '') ?>">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('nl_import_draft') ?> * <span class="text-muted small">(.md)</span></label>
        <input type="file" name="draft_file" class="form-control" accept=".md,.markdown,.txt" required>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= $this->lang->line('nl_import_report') ?> <span class="text-muted small">(.md)</span></label>
            <input type="file" name="report_file" class="form-control" accept=".md,.markdown,.txt">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= $this->lang->line('nl_import_inventory') ?> <span class="text-muted small">(.csv)</span></label>
            <input type="file" name="inventory_file" class="form-control" accept=".csv,.txt">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= $this->lang->line('nl_model') ?></label>
            <input type="text" name="model" class="form-control" maxlength="100" value="<?= htmlspecialchars($form['model'] ?? '') ?>" placeholder="Claude Desktop">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label"><?= $this->lang->line('nl_cost') ?></label>
            <input type="text" name="cost_usd" class="form-control" inputmode="decimal" value="<?= htmlspecialchars($form['cost_usd'] ?? '') ?>">
        </div>
    </div>
    <div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> <?= $this->lang->line('nl_btn_import') ?></button>
    </div>
</form>

</div>
