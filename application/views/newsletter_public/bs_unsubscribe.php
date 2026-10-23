<!-- VIEW: application/views/newsletter_public/bs_unsubscribe.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container">
<div class="row justify-content-center">
<div class="col-md-6 mt-4">

<h3><?= $this->lang->line('newsletter_unsubscribe_title') ?></h3>

<?php if (isset($success)): ?>
    <div class="alert alert-<?= $success ? 'success' : 'warning' ?>">
        <i class="fas fa-<?= $success ? 'check-circle' : 'exclamation-triangle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
<?php elseif (!empty($token)): ?>
    <p><?= $this->lang->line('newsletter_unsubscribe_confirm') ?></p>
    <form method="post" action="<?= site_url('newsletter_public/unsubscribe') ?>">
        <?= $this->security->get_csrf_hash() ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">' : '' ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <button type="submit" class="btn btn-danger">
            <i class="fas fa-times-circle"></i> <?= $this->lang->line('newsletter_unsubscribe_btn') ?>
        </button>
        <a href="<?= site_url() ?>" class="btn btn-outline-secondary ms-2">Annuler</a>
    </form>
<?php else: ?>
    <div class="alert alert-warning">
        <?= $this->lang->line('newsletter_unsubscribe_invalid') ?>
    </div>
<?php endif; ?>

</div>
</div>
</div>
