<!-- VIEW: application/views/newsletter_admin/bs_context_version.php -->
<?php
$this->lang->load('newsletter');
$csrf = $this->security->get_csrf_hash()
    ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'
    : '';
?>

<div id="body" class="body container-fluid">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <h3 class="mb-0"><?= htmlspecialchars($title) ?>
        <?php if ($v['is_active']): ?><span class="badge bg-primary"><?= $this->lang->line('nl_active') ?></span><?php endif; ?></h3>
    <div class="d-flex gap-2">
        <?php if (!$v['is_active']): ?>
        <form method="post" action="<?= site_url('newsletter_admin/context_restore/' . $v['id']) ?>">
            <?= $csrf ?>
            <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-undo"></i> <?= $this->lang->line('nl_btn_restore') ?></button>
        </form>
        <?php endif; ?>
        <a href="<?= site_url('newsletter_admin/context') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> <?= $this->lang->line('nl_context_title') ?></a>
    </div>
</div>
<p class="text-muted small"><?= htmlspecialchars(substr($v['created_at'], 0, 16)) ?> · <?= htmlspecialchars((string) $v['created_by']) ?>
    <?= $v['comment'] ? ' · ' . htmlspecialchars($v['comment']) : '' ?></p>

<?php if ($diff !== NULL): ?>
<h5><?= sprintf($this->lang->line('nl_diff_with_active'), (int) $active['version']) ?></h5>
<?php $this->load->view('newsletter_admin/_diff', array('diff' => $diff)); ?>
<?php endif; ?>

<h5 class="mt-3"><?= $this->lang->line('nl_context_content') ?></h5>
<pre class="border rounded p-2 small" style="white-space: pre-wrap;"><?= htmlspecialchars($v['content']) ?></pre>

</div>
