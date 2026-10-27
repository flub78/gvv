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

<p class="text-muted"><?= $this->lang->line('nl_import_intro') ?></p>

<?php if (!empty($result) && !$result['ok']): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><?= $this->lang->line('nl_import_failed') ?></strong>
    <ul class="mb-0"><?php foreach ($result['errors'] as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<form method="post" action="<?= site_url('newsletter_admin/import') ?>" enctype="multipart/form-data" class="card card-body">
    <?= $csrf ?>
    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('nl_import_file') ?> * <span class="text-muted small">(.md, .zip)</span></label>
        <input type="file" name="draft_file" class="form-control" accept=".md,.markdown,.zip" required>
    </div>
    <div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> <?= $this->lang->line('nl_btn_import') ?></button>
    </div>
</form>

</div>
