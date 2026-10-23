<!-- VIEW: application/views/newsletter_admin/bs_test_generate.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container">
<div class="row justify-content-center">
<div class="col-lg-9">

<h3><i class="fas fa-magic text-warning"></i> Test génération newsletter <small class="text-muted fs-6">via <code>claude --print</code></small></h3>

<?php if (!$api_key_configured): ?>
<div class="alert alert-warning">
  <i class="fas fa-exclamation-triangle"></i> Clé API Anthropic non configurée.
  <a href="<?= site_url('newsletter_admin/configuration') ?>" class="ms-2">Configurer →</a>
</div>

<?php else: ?>

<?php if ($this->session->flashdata('message')) echo $this->session->flashdata('message'); ?>

<?php if ($generate_error): ?>
<div class="alert alert-danger"><i class="fas fa-times-circle"></i>
  <strong>Erreur de démarrage :</strong><br><pre class="mb-0 mt-1 small"><?= htmlspecialchars($generate_error) ?></pre>
</div>
<?php endif; ?>

<!-- Fichier de contexte -->
<div class="card mb-3">
<div class="card-body py-2 d-flex align-items-center gap-3">
  <i class="fas fa-file-alt text-info fs-5"></i>
  <?php if ($context_info): ?>
    <span><strong><?= htmlspecialchars($context_info['name']) ?></strong>
    — <?= round($context_info['size'] / 1024) ?> Ko
    — modifié le <?= date('d/m/Y H:i', $context_info['mtime']) ?></span>
  <?php else: ?>
    <span class="text-danger">Fichier introuvable : <code>tmp/veille_agent_contexte.md</code></span>
  <?php endif; ?>
</div>
</div>

<?php if ($job): ?>
<!-- ── Mode polling ───────────────────────────────────────────────────── -->
<div id="status-area">
  <div id="status-running">
    <div class="alert alert-info mb-2">
      <span class="spinner-border spinner-border-sm me-2" role="status"></span>
      <span id="stream-info">Démarrage…</span>
    </div>
    <div class="card mb-2" id="stream-preview">
      <div class="card-header py-1 small text-muted">Aperçu en cours de génération</div>
      <div class="card-body p-2" style="max-height:55vh;overflow-y:auto;">
        <pre id="draft-text" style="white-space:pre-wrap;font-family:inherit;font-size:.85rem;margin:0;min-height:3rem;"></pre>
      </div>
    </div>
  </div>
  <div id="status-done" class="d-none">
    <div class="alert alert-success d-flex align-items-center gap-2 flex-wrap">
      <span><i class="fas fa-check-circle"></i> Brouillon généré</span>
      <a id="view-link" href="#" target="_blank" class="btn btn-sm btn-outline-info">
        <i class="fas fa-eye"></i> Visualiser
      </a>
      <a id="pdf-link" href="#" target="_blank" class="btn btn-sm btn-outline-danger">
        <i class="fas fa-file-pdf"></i> PDF
      </a>
      <a id="dl-link" href="#" class="btn btn-sm btn-outline-success">
        <i class="fas fa-download"></i> Télécharger
      </a>
      <form id="send-reviewers-form" method="post"
            action="<?= site_url('newsletter_admin/send_to_reviewers') ?>"
            style="display:contents"
            onsubmit="return confirm('Envoyer ce brouillon aux relecteurs ?')">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>"
               value="<?= $this->security->get_csrf_hash() ?>">
        <input type="hidden" name="draft" id="send-draft-name" value="">
        <button type="submit" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-envelope"></i> Envoyer aux relecteurs
        </button>
      </form>
    </div>
  </div>
  <div id="status-error" class="d-none">
    <div class="alert alert-danger">
      <i class="fas fa-times-circle"></i> <strong>Erreur :</strong><br>
      <pre id="error-text" class="mb-0 mt-1 small"></pre>
    </div>
  </div>
</div>

<div class="mt-3">
  <a href="<?= site_url('newsletter_admin/test_generate') ?>" class="btn btn-outline-secondary btn-sm">
    ← Nouvelle génération
  </a>
</div>

<script>
(function() {
  var url    = <?= json_encode(site_url('newsletter_admin/generate_status') . '?draft=' . rawurlencode($job)) ?>;
  var dlBase = <?= json_encode(site_url('newsletter_admin/download_draft/')) ?>;
  var elRun  = document.getElementById('status-running');
  var elDone = document.getElementById('status-done');
  var elErr  = document.getElementById('status-error');
  var elText = document.getElementById('draft-text');
  var elDl   = document.getElementById('dl-link');
  var elView = document.getElementById('view-link');
  var elPdf  = document.getElementById('pdf-link');
  var elInfo = document.getElementById('stream-info');
  var startTime = Date.now();

  function elapsed() {
    var s = Math.floor((Date.now() - startTime) / 1000);
    return s < 60 ? s + 's' : Math.floor(s/60) + 'min ' + (s%60) + 's';
  }

  function poll() {
    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.status === 'running') {
          elInfo.textContent = 'Initialisation… (' + elapsed() + ')';
          setTimeout(poll, 2000);
        } else if (data.status === 'streaming') {
          elText.textContent = data.text;
          var words = data.text.trim().split(/\s+/).length;
          elInfo.textContent = '⟳ Génération en cours — ' + words + ' mots (' + elapsed() + ')';
          // scroll to bottom
          elText.scrollTop = elText.scrollHeight;
          setTimeout(poll, 2000);
        } else if (data.status === 'done') {
          elRun.classList.add('d-none');
          elText.textContent = data.text;
          var viewBase = <?= json_encode(site_url('newsletter_admin/view_draft') . '?draft=') ?>;
          var pdfBase  = <?= json_encode(site_url('newsletter_admin/pdf_draft')  . '?draft=') ?>;
          elDl.href   = <?= json_encode(site_url('newsletter_admin/download_draft') . '?draft=') ?> + encodeURIComponent(data.name);
          elView.href = viewBase + encodeURIComponent(data.name);
          elPdf.href  = pdfBase  + encodeURIComponent(data.name);
          document.getElementById('send-draft-name').value = data.name;
          elDone.classList.remove('d-none');
        } else {
          elRun.classList.add('d-none');
          document.getElementById('error-text').textContent = data.error || 'Erreur inconnue.';
          elErr.classList.remove('d-none');
        }
      })
      .catch(function() { setTimeout(poll, 4000); });
  }

  poll();
})();
</script>

<?php else: ?>
<!-- ── Formulaire ─────────────────────────────────────────────────────── -->
<form method="post" action="<?= site_url('newsletter_admin/test_generate') ?>" id="form-generate">
  <?= $this->security->get_csrf_hash()
      ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'
      : '' ?>

  <div class="row align-items-end mb-3">
    <div class="col-md-5">
      <label class="form-label">Modèle</label>
      <select class="form-select form-select-sm" name="model">
        <option value="claude-opus-4-8"           <?= $default_model === 'claude-opus-4-8'           ? 'selected' : '' ?>>claude-opus-4-8 — Opus 4 (meilleur)</option>
        <option value="claude-sonnet-4-6"         <?= $default_model === 'claude-sonnet-4-6'         ? 'selected' : '' ?>>claude-sonnet-4-6 — Sonnet 4 (recommandé)</option>
        <option value="claude-haiku-4-5-20251001" <?= $default_model === 'claude-haiku-4-5-20251001' ? 'selected' : '' ?>>claude-haiku-4-5 — rapide</option>
      </select>
    </div>
    <div class="col-md-4">
      <button type="submit" class="btn btn-warning" id="btn-generate" <?= $context_info ? '' : 'disabled' ?>>
        <i class="fas fa-magic"></i> Générer le brouillon
      </button>
    </div>
    <div class="col-md-3 text-muted small">Durée estimée : 1–3 min.</div>
  </div>
</form>

<script>
document.getElementById('form-generate').addEventListener('submit', function() {
  var btn = document.getElementById('btn-generate');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Démarrage…';
});
</script>
<?php endif; ?>

<!-- Brouillons précédents -->
<?php if (!empty($drafts)): ?>
<div class="mt-4">
  <h6 class="text-muted"><i class="fas fa-history"></i> Brouillons précédents</h6>
  <ul class="list-group list-group-flush">
    <?php foreach (array_slice($drafts, 0, 10) as $d): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center py-1">
      <span class="small"><?= htmlspecialchars($d['name']) ?>
        <span class="text-muted">— <?= round($d['size'] / 1024) ?> Ko — <?= date('d/m/Y H:i', $d['mtime']) ?></span>
      </span>
      <div class="d-flex gap-1">
        <a href="<?= site_url('newsletter_admin/view_draft') . '?draft=' . rawurlencode($d['name']) ?>"
           target="_blank" class="btn btn-sm btn-outline-info" title="Visualiser le Markdown">
          <i class="fas fa-eye"></i>
        </a>
        <a href="<?= site_url('newsletter_admin/pdf_draft') . '?draft=' . rawurlencode($d['name']) ?>"
           target="_blank" class="btn btn-sm btn-outline-danger" title="Ouvrir en PDF">
          <i class="fas fa-file-pdf"></i>
        </a>
        <a href="<?= site_url('newsletter_admin/download_draft') . '?draft=' . rawurlencode($d['name']) ?>"
           class="btn btn-sm btn-outline-secondary" title="Télécharger le .md">
          <i class="fas fa-download"></i>
        </a>
        <form method="post" action="<?= site_url('newsletter_admin/send_to_reviewers') ?>"
              style="display:contents" onsubmit="return confirm('Envoyer ce brouillon aux relecteurs ?')">
          <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>"
                 value="<?= $this->security->get_csrf_hash() ?>">
          <input type="hidden" name="draft" value="<?= htmlspecialchars($d['name']) ?>">
          <button type="submit" class="btn btn-sm btn-outline-primary" title="Envoyer aux relecteurs">
            <i class="fas fa-envelope"></i>
          </button>
        </form>
        <form method="post" action="<?= site_url('newsletter_admin/delete_draft') ?>"
              style="display:contents" onsubmit="return confirm('Supprimer ce brouillon ?')">
          <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>"
                 value="<?= $this->security->get_csrf_hash() ?>">
          <input type="hidden" name="draft" value="<?= htmlspecialchars($d['name']) ?>">
          <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
            <i class="fas fa-trash-alt"></i>
          </button>
        </form>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php endif; ?>

</div>
</div>
</div>
