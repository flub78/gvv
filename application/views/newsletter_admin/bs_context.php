<!-- VIEW: application/views/newsletter_admin/bs_context.php -->
<?php
$this->lang->load('newsletter');
$csrf = $this->security->get_csrf_hash()
    ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'
    : '';
?>

<div id="body" class="body container-fluid">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0"><i class="fas fa-scroll text-info"></i> <?= $this->lang->line('nl_context_title') ?>
        <?php if ($active): ?><span class="badge bg-primary">v<?= (int) $active['version'] ?></span><?php endif; ?></h3>
    <a href="<?= site_url('newsletter_admin/editions') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> <?= $this->lang->line('nl_editions_title') ?></a>
</div>

<?php if (!empty($message)) echo $message; ?>

<p class="text-muted"><?= $this->lang->line('nl_context_intro') ?></p>

<div class="row g-3">
<div class="col-xl-8">
<form method="post" action="<?= site_url('newsletter_admin/context') ?>" enctype="multipart/form-data" class="card card-body">
    <?= $csrf ?>
    <div class="mb-2">
        <label class="form-label"><?= $this->lang->line('nl_context_content') ?></label>
        <textarea name="content" rows="28" class="form-control font-monospace small"><?= htmlspecialchars($active['content'] ?? '') ?></textarea>
    </div>
    <div class="mb-2">
        <label class="form-label"><?= $this->lang->line('nl_context_or_file') ?> <span class="text-muted small">(.md)</span></label>
        <input type="file" name="context_file" class="form-control form-control-sm" accept=".md,.markdown,.txt">
    </div>
    <div class="mb-2">
        <label class="form-label"><?= $this->lang->line('nl_context_comment') ?></label>
        <input type="text" name="comment" class="form-control form-control-sm" maxlength="255">
    </div>
    <div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $this->lang->line('nl_btn_save_context') ?></button>
    </div>
</form>
</div>

<div class="col-xl-4">
<div class="card">
<div class="card-header"><i class="fas fa-history"></i> <?= $this->lang->line('nl_versions') ?></div>
<?php if (!$versions): ?>
<div class="card-body text-muted"><?= $this->lang->line('nl_context_none') ?></div>
<?php else: ?>
<ul class="list-group list-group-flush small">
<?php foreach ($versions as $v): ?>
    <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
        <div>
            <a href="<?= site_url('newsletter_admin/context_version/' . $v['id']) ?>"><strong>v<?= (int) $v['version'] ?></strong></a>
            <?php if ($v['is_active']): ?><span class="badge bg-primary"><?= $this->lang->line('nl_active') ?></span><?php endif; ?>
            <div class="text-muted"><?= htmlspecialchars(substr($v['created_at'], 0, 16)) ?> · <?= htmlspecialchars((string) $v['created_by']) ?></div>
            <?php if ($v['comment']): ?><div><?= htmlspecialchars($v['comment']) ?></div><?php endif; ?>
        </div>
        <?php if (!$v['is_active']): ?>
        <form method="post" action="<?= site_url('newsletter_admin/context_restore/' . $v['id']) ?>">
            <?= $csrf ?>
            <button type="submit" class="btn btn-outline-secondary btn-sm" title="<?= $this->lang->line('nl_btn_restore') ?>"><i class="fas fa-undo"></i></button>
        </form>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
</div>
</div>

</div>
