<!-- VIEW: application/views/newsletter_admin/bs_test_email.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container">
<div class="row justify-content-center">
<div class="col-lg-7">

<h3><i class="fas fa-paper-plane text-primary"></i> <?= $this->lang->line('newsletter_test_email_title') ?></h3>

<p class="text-muted">
  Expéditeur : <strong><?= htmlspecialchars($cfg['newsletter_smtp_from'] ?? '(non configuré)') ?></strong>
  <?php if (!empty($cfg['newsletter_smtp_host'])): ?>
    via <strong><?= htmlspecialchars($cfg['newsletter_smtp_host']) ?></strong>
  <?php else: ?>
    via <strong>config globale GVV (Brevo)</strong>
  <?php endif; ?>
</p>

<?php if (isset($result)): ?>
<div class="alert alert-<?= $result['ok'] ? 'success' : 'danger' ?> mb-3">
  <i class="fas fa-<?= $result['ok'] ? 'check-circle' : 'times-circle' ?>"></i>
  <?= nl2br(htmlspecialchars($result['msg'])) ?>
</div>
<?php endif; ?>

<?php if (empty($cfg['newsletter_smtp_from'])): ?>
<div class="alert alert-warning">
  Adresse d'expédition non configurée.
  <a href="<?= site_url('newsletter_admin/configuration') ?>">Configurer →</a>
</div>
<?php else: ?>

<form method="post" action="<?= site_url('newsletter_admin/test_email') ?>"
      enctype="multipart/form-data">
  <?= $this->security->get_csrf_hash()
      ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'
      : '' ?>

  <div class="mb-3">
    <label class="form-label"><?= $this->lang->line('newsletter_test_to') ?> *</label>
    <input type="text" class="form-control" name="to_email"
           value="<?= htmlspecialchars(isset($form['to_email']) ? $form['to_email'] : $user_email) ?>"
           placeholder="email1@exemple.com, email2@exemple.com"
           required>
    <div class="form-text">Plusieurs destinataires séparés par des virgules.</div>
  </div>

  <div class="mb-3">
    <label class="form-label">Sujet *</label>
    <input type="text" class="form-control" name="subject"
           value="<?= htmlspecialchars(isset($form['subject']) ? $form['subject'] : '[GVV Veille] Test d\'envoi') ?>"
           required>
  </div>

  <div class="mb-3">
    <label class="form-label">Corps du message *</label>
    <textarea class="form-control" name="body" rows="6" required><?= htmlspecialchars(isset($form['body']) ? $form['body'] : "Ceci est un email de test depuis GVV.\n\nExpéditeur : " . ($cfg['newsletter_smtp_from'] ?? '') . "\nDate : " . date('d/m/Y H:i')) ?></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Pièce jointe <span class="text-muted">(optionnel, max 10 Mo)</span></label>
    <input type="file" class="form-control" name="attachment">
  </div>

  <button type="submit" class="btn btn-primary">
    <i class="fas fa-paper-plane"></i> <?= $this->lang->line('newsletter_test_send') ?>
  </button>
  <a href="<?= site_url('newsletter_admin/configuration') ?>" class="btn btn-outline-secondary ms-2">Configuration</a>
</form>

<?php endif; ?>

</div>
</div>
</div>
