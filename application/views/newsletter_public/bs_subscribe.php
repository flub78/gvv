<!-- VIEW: application/views/newsletter_public/bs_subscribe.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container">
<div class="row justify-content-center">
<div class="col-md-6">

<h3><?= $this->lang->line('newsletter_subscribe_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('newsletter_subscribe_intro') ?></p>

<?php if (!empty($flash_info)): ?>
    <div class="alert alert-success"><?= $flash_info ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
    <div class="alert alert-warning"><?= $flash_error ?></div>
<?php endif; ?>
<?php if (!empty($form_errors)): ?>
    <div class="alert alert-danger"><?= $form_errors ?></div>
<?php endif; ?>

<?php if (empty($flash_info)): ?>
<form method="post" action="<?= site_url('newsletter_public/subscribe') ?>">
    <?= $this->security->get_csrf_hash() ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">' : '' ?>

    <div class="mb-3">
        <label for="first_name" class="form-label"><?= $this->lang->line('newsletter_first_name') ?> *</label>
        <input type="text" class="form-control" id="first_name" name="first_name"
               value="<?= htmlspecialchars(isset($form_data['first_name']) ? $form_data['first_name'] : '') ?>" required>
    </div>
    <div class="mb-3">
        <label for="last_name" class="form-label"><?= $this->lang->line('newsletter_last_name') ?> *</label>
        <input type="text" class="form-control" id="last_name" name="last_name"
               value="<?= htmlspecialchars(isset($form_data['last_name']) ? $form_data['last_name'] : '') ?>" required>
    </div>
    <div class="mb-3">
        <label for="email" class="form-label"><?= $this->lang->line('newsletter_email') ?> *</label>
        <input type="email" class="form-control" id="email" name="email"
               value="<?= htmlspecialchars(isset($form_data['email']) ? $form_data['email'] : '') ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-envelope"></i> <?= $this->lang->line('newsletter_subscribe_btn') ?>
    </button>
</form>
<?php endif; ?>

</div>
</div>
</div>
