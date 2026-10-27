<!-- VIEW: application/views/newsletter_admin/bs_version.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container-fluid">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0"><?= htmlspecialchars($nl['title']) ?> — v<?= (int) $n ?></h3>
    <a href="<?= site_url('newsletter_admin/edition/' . $nl['id']) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> <?= $this->lang->line('nl_back_to_edition') ?></a>
</div>

<?php if ($diff !== NULL): ?>
<h5><?= sprintf($this->lang->line('nl_diff_with'), (int) $previous) ?></h5>
<?php $this->load->view('newsletter_admin/_diff', array('diff' => $diff)); ?>
<?php endif; ?>

<h5 class="mt-3"><?= $this->lang->line('nl_rendering') ?></h5>
<div class="border rounded p-3"><?= $html ?></div>

</div>
