<!-- VIEW: application/views/newsletter_admin/bs_configuration.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container-fluid">
<div class="row">
<div class="col-lg-8">

<h3><?= $this->lang->line('newsletter_config_title') ?></h3>

<?php if (!empty($message)) echo $message; ?>

<form method="post" action="<?= site_url('newsletter_admin/configuration') ?>">
<?= $this->security->get_csrf_hash() ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">' : '' ?>

<!-- ── IMAP ─────────────────────────────────────────────────────────────── -->
<div class="card mb-3">
<div class="card-header"><i class="fas fa-envelope-open text-info"></i> <?= $this->lang->line('newsletter_config_imap') ?></div>
<div class="card-body">
  <div class="row">
    <div class="col-md-6 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_imap_host') ?></label>
      <input type="text" class="form-control form-control-sm" name="newsletter_imap_host"
             value="<?= htmlspecialchars($cfg['newsletter_imap_host'] ?? '') ?>">
    </div>
    <div class="col-md-2 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_imap_port') ?></label>
      <input type="number" class="form-control form-control-sm" name="newsletter_imap_port"
             value="<?= htmlspecialchars($cfg['newsletter_imap_port'] ?? '993') ?>">
    </div>
    <div class="col-md-2 mb-2 d-flex align-items-end pb-1">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="newsletter_imap_ssl" value="1"
               <?= !empty($cfg['newsletter_imap_ssl']) ? 'checked' : '' ?>>
        <label class="form-check-label"><?= $this->lang->line('newsletter_config_imap_ssl') ?></label>
      </div>
    </div>
    <div class="col-md-2 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_imap_mailbox') ?></label>
      <input type="text" class="form-control form-control-sm" name="newsletter_imap_mailbox"
             value="<?= htmlspecialchars($cfg['newsletter_imap_mailbox'] ?? 'INBOX') ?>">
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_imap_user') ?></label>
      <input type="email" class="form-control form-control-sm" name="newsletter_imap_user"
             value="<?= htmlspecialchars($cfg['newsletter_imap_user'] ?? '') ?>">
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_imap_password') ?></label>
      <input type="password" class="form-control form-control-sm" name="newsletter_imap_password"
             placeholder="<?= empty($cfg['newsletter_imap_password']) ? 'Non défini' : '••••••••' ?>">
    </div>
  </div>
</div>
</div>

<!-- ── SMTP ─────────────────────────────────────────────────────────────── -->
<div class="card mb-3">
<div class="card-header"><i class="fas fa-paper-plane text-primary"></i> <?= $this->lang->line('newsletter_config_smtp') ?></div>
<div class="card-body">
  <p class="text-muted small mb-3"><?= $this->lang->line('newsletter_config_smtp_fallback') ?></p>
  <div class="row">
    <div class="col-md-5 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_smtp_host') ?></label>
      <input type="text" class="form-control form-control-sm" name="newsletter_smtp_host"
             value="<?= htmlspecialchars($cfg['newsletter_smtp_host'] ?? '') ?>"
             placeholder="smtp.ionos.fr">
    </div>
    <div class="col-md-2 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_smtp_port') ?></label>
      <input type="number" class="form-control form-control-sm" name="newsletter_smtp_port"
             value="<?= htmlspecialchars($cfg['newsletter_smtp_port'] ?? '587') ?>">
    </div>
    <div class="col-md-2 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_smtp_crypto') ?></label>
      <select class="form-select form-select-sm" name="newsletter_smtp_crypto">
        <option value="tls"  <?= ($cfg['newsletter_smtp_crypto'] ?? 'tls') === 'tls'  ? 'selected' : '' ?>>tls (587)</option>
        <option value="ssl"  <?= ($cfg['newsletter_smtp_crypto'] ?? '')     === 'ssl'  ? 'selected' : '' ?>>ssl (465)</option>
        <option value=""     <?= ($cfg['newsletter_smtp_crypto'] ?? '')     === ''     ? 'selected' : '' ?>>aucun</option>
      </select>
    </div>
    <div class="col-md-3 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_smtp_user') ?></label>
      <input type="email" class="form-control form-control-sm" name="newsletter_smtp_user"
             value="<?= htmlspecialchars($cfg['newsletter_smtp_user'] ?? '') ?>">
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_smtp_password') ?></label>
      <input type="password" class="form-control form-control-sm" name="newsletter_smtp_password"
             placeholder="<?= empty($cfg['newsletter_smtp_password']) ? 'Non défini' : '••••••••' ?>">
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_smtp_from') ?></label>
      <input type="email" class="form-control form-control-sm" name="newsletter_smtp_from"
             value="<?= htmlspecialchars($cfg['newsletter_smtp_from'] ?? '') ?>">
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_smtp_from_name') ?></label>
      <input type="text" class="form-control form-control-sm" name="newsletter_smtp_from_name"
             value="<?= htmlspecialchars($cfg['newsletter_smtp_from_name'] ?? '') ?>">
    </div>
  </div>
</div>
</div>

<!-- ── Relecteurs / validateurs ─────────────────────────────────────────── -->
<div class="card mb-3">
<div class="card-header"><i class="fas fa-users text-warning"></i> <?= $this->lang->line('newsletter_config_people') ?></div>
<div class="card-body">
  <div class="mb-2">
    <label class="form-label"><?= $this->lang->line('newsletter_config_reviewers') ?></label>
    <input type="text" class="form-control form-control-sm" name="newsletter_reviewers"
           value="<?= htmlspecialchars($cfg['newsletter_reviewers'] ?? '') ?>"
           placeholder="alice@example.com, bob@example.com">
  </div>
  <div class="mb-2">
    <label class="form-label"><?= $this->lang->line('newsletter_config_validators') ?></label>
    <input type="text" class="form-control form-control-sm" name="newsletter_validators"
           value="<?= htmlspecialchars($cfg['newsletter_validators'] ?? '') ?>"
           placeholder="cps@example.com">
  </div>
  <hr class="my-3">
  <p class="form-text text-muted mb-2">
    Variables disponibles dans le template :
    <code>{period}</code> <code>{version}</code> <code>{date}</code>
    <code>{deadline}</code> <code>{from_name}</code> <code>{draft_name}</code>
  </p>
  <div class="mb-2">
    <label class="form-label"><?= $this->lang->line('newsletter_config_review_subject') ?></label>
    <input type="text" class="form-control form-control-sm" name="newsletter_review_subject"
           value="<?= htmlspecialchars($cfg['newsletter_review_subject'] ?? '') ?>"
           placeholder="[Veille ACS] Brouillon {period} (v{version}) — relecture souhaitée">
  </div>
  <div class="mb-2">
    <label class="form-label"><?= $this->lang->line('newsletter_config_review_body') ?></label>
    <textarea class="form-control form-control-sm" name="newsletter_review_body"
              rows="10"><?= htmlspecialchars(str_replace('\n', "\n", $cfg['newsletter_review_body'] ?? '')) ?></textarea>
    <div class="form-text">Les sauts de ligne sont conservés.</div>
  </div>
</div>
</div>

<!-- ── Agent IA ──────────────────────────────────────────────────────────── -->
<div class="card mb-3">
<div class="card-header"><i class="fas fa-robot text-secondary"></i> <?= $this->lang->line('newsletter_config_paths') ?></div>
<div class="card-body">
  <div class="row">
    <div class="col-md-8 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_anthropic_key') ?></label>
      <input type="password" class="form-control form-control-sm" name="newsletter_anthropic_api_key"
             placeholder="<?= empty($cfg['newsletter_anthropic_api_key']) ? 'Non défini' : '••••••••' ?>">
      <div class="form-text">Laisser vide pour conserver la valeur actuelle.</div>
    </div>
    <div class="col-md-4 mb-2">
      <label class="form-label"><?= $this->lang->line('newsletter_config_anthropic_model') ?></label>
      <select class="form-select form-select-sm" name="newsletter_anthropic_model">
        <?php $cur_model = $cfg['newsletter_anthropic_model'] ?? 'claude-sonnet-4-6'; ?>
        <option value="claude-opus-4-8"           <?= $cur_model === 'claude-opus-4-8'           ? 'selected' : '' ?>>claude-opus-4-8</option>
        <option value="claude-sonnet-4-6"         <?= $cur_model === 'claude-sonnet-4-6'         ? 'selected' : '' ?>>claude-sonnet-4-6</option>
        <option value="claude-haiku-4-5-20251001" <?= $cur_model === 'claude-haiku-4-5-20251001' ? 'selected' : '' ?>>claude-haiku-4-5</option>
      </select>
    </div>
  </div>
  <div class="mb-2">
    <label class="form-label"><?= $this->lang->line('newsletter_config_context_path') ?></label>
    <input type="text" class="form-control form-control-sm" name="newsletter_context_path"
           value="<?= htmlspecialchars($cfg['newsletter_context_path'] ?? '') ?>"
           placeholder="/srv/newsletter/context.md">
  </div>
  <div class="mb-2">
    <label class="form-label"><?= $this->lang->line('newsletter_config_output_path') ?></label>
    <input type="text" class="form-control form-control-sm" name="newsletter_output_path"
           value="<?= htmlspecialchars($cfg['newsletter_output_path'] ?? '') ?>"
           placeholder="/srv/newsletter/output">
  </div>
  <div class="form-check">
    <input class="form-check-input" type="checkbox" name="newsletter_alerts_enabled" value="1"
           <?= !empty($cfg['newsletter_alerts_enabled']) ? 'checked' : '' ?>>
    <label class="form-check-label"><?= $this->lang->line('newsletter_config_alerts') ?></label>
  </div>
</div>
</div>

<button type="submit" class="btn btn-primary">
  <i class="fas fa-save"></i> <?= $this->lang->line('newsletter_config_save') ?>
</button>
<a href="<?= site_url('newsletter_admin') ?>" class="btn btn-outline-secondary ms-2">Retour</a>

</form>
</div>
</div>
</div>
