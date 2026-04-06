<!-- VIEW: application/views/paiements_en_ligne/bs_admin_config.php -->
<?php
/**
 * Configuration admin HelloAsso par section (EF5).
 *
 * Variables :
 *   $sections_selector    — sélecteur de sections
 *   $bar_account_selector — sélecteur de comptes 7xx
 *   $club_id              — section courante
 *   $cfg                  — array de config HelloAsso (clés/valeurs)
 *   $section_row          — ligne de la table sections (has_bar, bar_account_id)
 *   $webhook_url          — URL de webhook générée
 *   $success / $error     — messages flash
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?= htmlspecialchars($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3><i class="fas fa-credit-card text-primary me-2"></i><?= $this->lang->line('gvv_admin_config_title') ?></h3>
</div>

<!-- Sélecteur de section -->
<div class="card mb-3" style="max-width:500px;">
  <div class="card-body">
    <label class="form-label fw-bold"><?= $this->lang->line('gvv_admin_config_section') ?></label>
    <select class="form-select" onchange="location.href='<?= site_url('paiements_en_ligne/admin_config') ?>?section='+this.value">
      <option value="0"><?= $this->lang->line('gvv_admin_config_select_section') ?></option>
      <?php foreach ($sections_selector as $id => $nom): ?>
        <option value="<?= (int)$id ?>" <?= $id == $club_id ? 'selected' : '' ?>>
          <?= htmlspecialchars($nom) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<?php if ($club_id): ?>

<?= form_open('paiements_en_ligne/admin_config?section=' . $club_id, array('id' => 'config-form')) ?>

<!-- HelloAsso API -->
<div class="card mb-3">
  <div class="card-header"><i class="fas fa-key me-2 text-warning"></i><?= $this->lang->line('gvv_admin_config_helloasso_title') ?></div>
  <div class="card-body">

    <div class="row g-3">

      <div class="col-md-6">
        <label class="form-label"><?= $this->lang->line('gvv_admin_config_client_id') ?></label>
        <input type="text" name="client_id" class="form-control font-monospace"
               value="<?= htmlspecialchars($cfg['client_id']) ?>"
               autocomplete="off" placeholder="client_id HelloAsso" />
      </div>

      <div class="col-md-6">
        <label class="form-label"><?= $this->lang->line('gvv_admin_config_client_secret') ?></label>
        <div class="input-group">
          <input type="password" name="client_secret" id="client_secret" class="form-control font-monospace"
                 value="" autocomplete="new-password"
                 placeholder="<?= $cfg['client_secret'] !== '' ? $this->lang->line('gvv_admin_config_secret_set') : $this->lang->line('gvv_admin_config_secret_empty') ?>" />
          <button type="button" class="btn btn-outline-secondary" onclick="toggleSecret()">
            <i class="fas fa-eye" id="secret-eye"></i>
          </button>
        </div>
        <div class="form-text"><?= $this->lang->line('gvv_admin_config_secret_help') ?></div>
      </div>

      <div class="col-md-6">
        <label class="form-label"><?= $this->lang->line('gvv_admin_config_account_slug') ?></label>
        <input type="text" name="account_slug" class="form-control font-monospace"
               value="<?= htmlspecialchars($cfg['account_slug']) ?>"
               placeholder="mon-aeroclub" />
        <div class="form-text"><?= $this->lang->line('gvv_admin_config_slug_help') ?></div>
      </div>

      <div class="col-md-6">
        <label class="form-label"><?= $this->lang->line('gvv_admin_config_environment') ?></label>
        <select name="environment" class="form-select">
          <option value="sandbox"    <?= $cfg['environment'] === 'sandbox'    ? 'selected' : '' ?>>Sandbox (test)</option>
          <option value="production" <?= $cfg['environment'] === 'production' ? 'selected' : '' ?>>Production</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label"><?= $this->lang->line('gvv_admin_config_webhook_secret') ?></label>
        <input type="password" name="webhook_secret" class="form-control font-monospace"
               value="" autocomplete="new-password"
               placeholder="<?= (!empty($cfg['webhook_secret'])) ? $this->lang->line('gvv_admin_config_secret_set') : $this->lang->line('gvv_admin_config_secret_empty') ?>" />
      </div>

      <div class="col-md-6">
        <label class="form-label"><?= $this->lang->line('gvv_admin_config_webhook_url') ?></label>
        <div class="input-group">
          <input type="text" id="webhook-url" class="form-control font-monospace"
                 value="<?= htmlspecialchars($webhook_url) ?>" readonly />
          <button type="button" class="btn btn-outline-secondary" onclick="copyWebhookUrl()" title="Copier">
            <i class="fas fa-copy"></i>
          </button>
        </div>
        <div class="form-text"><?= $this->lang->line('gvv_admin_config_webhook_url_help') ?></div>
      </div>

    </div><!-- /row -->

    <!-- Bouton test connexion -->
    <div class="mt-3">
      <button type="button" class="btn btn-outline-info" onclick="testConnexion()">
        <i class="fas fa-plug me-1"></i><?= $this->lang->line('gvv_admin_config_test_btn') ?>
      </button>
      <span id="test-result" class="ms-3"></span>
    </div>

  </div>
</div>

<!-- Configuration bar -->
<div class="card mb-3">
  <div class="card-header"><i class="fas fa-beer me-2 text-warning"></i><?= $this->lang->line('gvv_admin_config_bar_title') ?></div>
  <div class="card-body">

    <div class="form-check form-switch mb-3">
      <input class="form-check-input" type="checkbox" name="has_bar" id="has_bar" value="1"
             <?= (!empty($section_row['has_bar'])) ? 'checked' : '' ?>
             onchange="toggleBarAccount(this.checked)" />
      <label class="form-check-label" for="has_bar"><?= $this->lang->line('gvv_admin_config_has_bar') ?></label>
    </div>

    <div id="bar-account-block" <?= empty($section_row['has_bar']) ? 'style="display:none"' : '' ?>>
      <label class="form-label"><?= $this->lang->line('gvv_admin_config_bar_account') ?></label>
      <?= dropdown_field('bar_account_id', isset($section_row['bar_account_id']) ? $section_row['bar_account_id'] : '',
          $bar_account_selector, 'class="form-select" style="max-width:400px;"') ?>
      <div class="form-text"><?= $this->lang->line('gvv_admin_config_bar_account_help') ?></div>
    </div>

  </div>
</div>

<!-- Fonctionnalités CB -->
<div class="card mb-3">
  <div class="card-header"><i class="fas fa-credit-card me-2 text-success"></i><?= $this->lang->line('gvv_admin_config_cb_features_title') ?></div>
  <div class="card-body">

    <div class="form-check form-switch mb-2">
      <input class="form-check-input" type="checkbox" name="has_vd_par_cb" id="has_vd_par_cb" value="1"
             <?= (!empty($section_row['has_vd_par_cb'])) ? 'checked' : '' ?> />
      <label class="form-check-label" for="has_vd_par_cb"><?= $this->lang->line('gvv_admin_config_has_vd_par_cb') ?></label>
    </div>

    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="has_approvisio_par_cb" id="has_approvisio_par_cb" value="1"
             <?= (!empty($section_row['has_approvisio_par_cb'])) ? 'checked' : '' ?> />
      <label class="form-check-label" for="has_approvisio_par_cb"><?= $this->lang->line('gvv_admin_config_has_approvisio_par_cb') ?></label>
    </div>

  </div>
</div>

<!-- Paramètres de transaction -->
<div class="card mb-3">
  <div class="card-header"><i class="fas fa-sliders-h me-2 text-primary"></i><?= $this->lang->line('gvv_admin_config_transaction_title') ?></div>
  <div class="card-body">
    <div class="row g-3">

      <div class="col-md-4">
        <label class="form-label"><?= $this->lang->line('gvv_admin_config_compte_passage') ?></label>
        <?= dropdown_field('compte_passage', $cfg['compte_passage'],
            $compte_passage_selector, 'class="form-select" style="max-width:400px;"') ?>
        <div class="form-text"><?= $this->lang->line('gvv_admin_config_compte_passage_help') ?></div>
      </div>

      <div class="col-md-4">
        <label class="form-label"><?= $this->lang->line('gvv_admin_config_montant_min') ?></label>
        <div class="input-group" style="max-width:180px;">
          <input type="number" name="montant_min" class="form-control"
                 value="<?= htmlspecialchars($cfg['montant_min']) ?>" min="0.50" step="0.50" />
          <span class="input-group-text">€</span>
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label"><?= $this->lang->line('gvv_admin_config_montant_max') ?></label>
        <div class="input-group" style="max-width:180px;">
          <input type="number" name="montant_max" class="form-control"
                 value="<?= htmlspecialchars($cfg['montant_max']) ?>" min="1" step="1" />
          <span class="input-group-text">€</span>
        </div>
      </div>

      <div class="col-12">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="enabled" id="enabled" value="1"
                 <?= ($cfg['enabled'] === '1') ? 'checked' : '' ?> />
          <label class="form-check-label" for="enabled"><?= $this->lang->line('gvv_admin_config_enabled') ?></label>
        </div>
        <div class="form-text"><?= $this->lang->line('gvv_admin_config_enabled_help') ?></div>
      </div>

    </div>
  </div>
</div>

<!-- Page publique VD -->
<div class="card mb-3">
  <div class="card-header"><i class="fas fa-globe me-2 text-info"></i><?= $this->lang->line('gvv_vd_public_title') ?></div>
  <div class="card-body">

    <div class="mb-3">
      <label class="form-label fw-semibold"><?= $this->lang->line('gvv_vd_accueil_text_label') ?></label>
      <textarea name="vd_accueil_text" class="form-control font-monospace" rows="4"
                placeholder="<?= htmlspecialchars($this->lang->line('gvv_vd_public_default_accueil')) ?>"
      ><?= htmlspecialchars($cfg['vd_accueil_text']) ?></textarea>
    </div>

    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label fw-semibold"><?= $this->lang->line('gvv_vd_quota_mensuel_label') ?></label>
        <input type="number" name="vd_quota_mensuel" class="form-control" style="max-width:140px;"
               value="<?= (int) $cfg['vd_quota_mensuel'] ?>" min="0" step="1" />
      </div>
      <div class="col-md-8 text-muted small">
        <?= (int) $vd_vendu_30j ?> <?= $this->lang->line('gvv_vd_quota_atteint_msg') ?>
      </div>
    </div>

  </div>
</div>

<!-- Boutons -->
<div class="d-flex gap-2 mb-4">
  <button type="submit" name="button" value="save" class="btn btn-success">
    <i class="fas fa-save me-1"></i><?= $this->lang->line('gvv_button_save') ?>
  </button>
  <a href="<?= site_url('paiements_en_ligne/admin_config') ?>" class="btn btn-secondary">
    <?= $this->lang->line('gvv_button_cancel') ?>
  </a>
</div>

<?= form_close() ?>

<?php endif; // club_id ?>

</div><!-- /body -->

<script>
function toggleBarAccount(checked) {
    document.getElementById('bar-account-block').style.display = checked ? '' : 'none';
}

function toggleSecret() {
    const f = document.getElementById('client_secret');
    const eye = document.getElementById('secret-eye');
    if (f.type === 'password') { f.type = 'text'; eye.className = 'fas fa-eye-slash'; }
    else                       { f.type = 'password'; eye.className = 'fas fa-eye'; }
}

function copyWebhookUrl() {
    const el = document.getElementById('webhook-url');
    el.select();
    document.execCommand('copy');
    const btn = el.nextElementSibling;
    btn.innerHTML = '<i class="fas fa-check text-success"></i>';
    setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 2000);
}

function testConnexion() {
    const result = document.getElementById('test-result');
    result.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i><?= $this->lang->line('gvv_admin_config_test_pending') ?></span>';

    fetch('<?= site_url('paiements_en_ligne/test_connexion') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            club_id:     '<?= $club_id ?>',
            client_id:   document.querySelector('input[name="client_id"]').value,
            client_secret: document.getElementById('client_secret').value,
            environment: document.querySelector('select[name="environment"]').value,
            '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
        }).toString()
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            result.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>' + data.message + '</span>';
        } else {
            result.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>' + data.message + '</span>';
        }
    })
    .catch(() => {
        result.innerHTML = '<span class="text-danger"><?= $this->lang->line('gvv_admin_config_test_error') ?></span>';
    });
}
</script>
