<!-- VIEW: application/views/newsletter_admin/bs_test_ai.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container">
<div class="row justify-content-center">
<div class="col-lg-8">

<h3><i class="fas fa-robot text-secondary"></i> <?= $this->lang->line('newsletter_test_ai_title') ?></h3>

<?php if (!$api_key_configured): ?>
<div class="alert alert-warning">
  <i class="fas fa-exclamation-triangle"></i>
  <?= $this->lang->line('newsletter_test_ai_no_key') ?>
  <a href="<?= site_url('newsletter_admin/configuration') ?>" class="ms-2">Configurer →</a>
</div>
<?php else: ?>

<?php if (isset($result)): ?>
<div class="alert alert-<?= $result['ok'] ? 'success' : 'danger' ?> mb-3">
  <i class="fas fa-<?= $result['ok'] ? 'check-circle' : 'times-circle' ?>"></i>
  <?= htmlspecialchars($result['msg']) ?>
</div>
<?php endif; ?>

<form method="post" action="<?= site_url('newsletter_admin/test_ai') ?>">
  <?= $this->security->get_csrf_hash()
      ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'
      : '' ?>

  <div class="row mb-3">
    <div class="col-md-6">
      <label class="form-label"><?= $this->lang->line('newsletter_test_ai_model') ?></label>
      <select class="form-select form-select-sm" name="model">
        <?php $sel_model = $form['model'] ?? $default_model; ?>
        <option value="claude-opus-4-8"          <?= $sel_model === 'claude-opus-4-8'          ? 'selected' : '' ?>>claude-opus-4-8 (Opus 4)</option>
        <option value="claude-sonnet-4-6"        <?= $sel_model === 'claude-sonnet-4-6'        ? 'selected' : '' ?>>claude-sonnet-4-6 (Sonnet 4)</option>
        <option value="claude-haiku-4-5-20251001" <?= $sel_model === 'claude-haiku-4-5-20251001' ? 'selected' : '' ?>>claude-haiku-4-5 (Haiku 4 — rapide)</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label"><?= $this->lang->line('newsletter_test_ai_max_tokens') ?></label>
      <input type="number" class="form-control form-control-sm" name="max_tokens"
             value="<?= (int) ($form['max_tokens'] ?? 1024) ?>" min="64" max="4096" step="64">
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label"><?= $this->lang->line('newsletter_test_ai_prompt') ?> *</label>
    <textarea class="form-control" name="prompt" rows="8" required
              placeholder="Bonjour Claude ! Présente-toi en quelques mots."><?= htmlspecialchars($form['prompt'] ?? '') ?></textarea>
  </div>

  <button type="submit" class="btn btn-primary">
    <i class="fas fa-paper-plane"></i> <?= $this->lang->line('newsletter_test_ai_send') ?>
  </button>
  <a href="<?= site_url('newsletter_admin/configuration') ?>" class="btn btn-outline-secondary ms-2">Configuration</a>
</form>

<?php if (isset($ai_response)): ?>
<hr class="mt-4">
<h5><i class="fas fa-comment-dots text-secondary"></i> <?= $this->lang->line('newsletter_test_ai_result') ?></h5>

<?php if ($ai_response['ok']): ?>
  <div class="card">
    <div class="card-body">
      <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;"><?= htmlspecialchars($ai_response['text']) ?></pre>
    </div>
  </div>
  <div class="text-muted small mt-2">
    Modèle : <strong><?= htmlspecialchars($ai_response['model']) ?></strong>
    &nbsp;|&nbsp; Tokens : <?= (int) $ai_response['input_tokens'] ?> in / <?= (int) $ai_response['output_tokens'] ?> out
    &nbsp;|&nbsp; Stop : <code><?= htmlspecialchars($ai_response['stop_reason']) ?></code>
  </div>
<?php else: ?>
  <div class="alert alert-danger">
    <strong>Erreur API :</strong><br>
    <pre class="mb-0"><?= htmlspecialchars($ai_response['error']) ?></pre>
  </div>
<?php endif; ?>
<?php endif; ?>

<?php endif; ?>

</div>
</div>
</div>
