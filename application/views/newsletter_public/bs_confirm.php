<!-- VIEW: application/views/newsletter_public/bs_confirm.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container">
<div class="row justify-content-center">
<div class="col-md-6 mt-4">

<h3><?= $this->lang->line('newsletter_confirm_title') ?></h3>

<div class="alert alert-<?= $level ?>">
    <i class="fas fa-<?= $level === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
    <?= htmlspecialchars($message) ?>
</div>

<?php if ($success): ?>
<p><a href="<?= site_url('newsletter_public/subscribe') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left"></i> Retour
</a></p>
<?php endif; ?>

</div>
</div>
</div>
