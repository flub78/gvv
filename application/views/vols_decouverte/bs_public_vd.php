<!-- VIEW: application/views/vols_decouverte/bs_public_vd.php -->
<?php
/**
 * Page publique d'achat d'un bon de vol de découverte.
 * Accessible sans connexion.
 *
 * Variables :
 *   $section_id            — ID section sélectionnée (0 = aucune)
 *   $section_row           — données de la section sélectionnée (ou null)
 *   $sections_disponibles  — toutes les sections avec has_vd_par_cb = 1 + quota_status
 *   $sections_alternatives — sections disponibles hors section courante
 *   $section_error         — message d'erreur de section
 *   $quota_status          — statut quota de la section (ou null)
 *   $products              — tarifs VD de la section (type_ticket=1, public=1, actifs)
 *   $accueil_text          — texte d'accueil (Markdown)
 *   $errors                — erreurs de validation POST
 *   $form_data             — données saisies à réafficher
 *   $title                 — titre de la page
 */

$this->load->helper('markdown');

$fd = is_array($form_data) ? $form_data : array();
$fv = function($key, $default = '') use ($fd) {
    return htmlspecialchars(isset($fd[$key]) ? $fd[$key] : $default, ENT_QUOTES, 'UTF-8');
};

$has_validation_errors = false;
if (!empty($errors) && is_array($errors)) {
  foreach (array('beneficiaire', 'acheteur_email', 'acheteur_tel', 'urgence', 'product', 'nb_personnes') as $field_error) {
    if (!empty($errors[$field_error])) {
      $has_validation_errors = true;
      break;
    }
  }
}
?>
<style>
  .vd-hero {
    background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
    color: #fff;
    padding: 2rem 1.5rem;
    border-radius: .5rem;
    margin-bottom: 1.5rem;
  }
  .vd-hero h1 { font-size: 1.75rem; margin-bottom: .5rem; }
  .section-card { cursor: pointer; transition: box-shadow .15s; }
  .section-card:hover { box-shadow: 0 0 0 3px #0d6efd44; }
  .section-card.active { border-color: #0d6efd; box-shadow: 0 0 0 3px #0d6efd88; }
</style>
<div class="container py-4" style="max-width: 720px;">

  <!-- En-tête -->
  <div class="vd-hero">
    <h1><i class="fas fa-plane me-2"></i><?= htmlspecialchars($title) ?></h1>
    <?php if (!empty($accueil_text)): ?>
      <div class="mt-2"><?= markdown($accueil_text) ?></div>
    <?php endif; ?>
  </div>

  <?php if (!empty($section_error)): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($section_error) ?></div>
  <?php endif; ?>

  <?php if (!empty($errors['rate_limit'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['rate_limit']) ?></div>
  <?php endif; ?>

  <?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
  <?php endif; ?>

  <!-- Sélecteur de section (masqué si section forcée depuis l'URL) -->
  <?php if (empty($sections_disponibles)): ?>
  <div class="alert alert-info"><?= $this->lang->line('gvv_vd_public_no_section_available') ?></div>
  <?php elseif (empty($section_row) || count($sections_disponibles) > 1): ?>
  <div class="mb-4">
    <h5><?= $this->lang->line('gvv_vd_public_choose_section') ?></h5>
    <div class="row g-2">
      <?php foreach ($sections_disponibles as $s):
        $is_active  = ((int) $s['id'] === (int) $section_id);
        $is_complet = $s['quota_status']['atteint'];
      ?>
      <div class="col-sm-6">
        <a href="<?= site_url('vols_decouverte/public_vd?section=' . (int) $s['id']) ?>"
           class="card text-decoration-none section-card <?= $is_active ? 'active border-primary' : '' ?>">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <strong><?= htmlspecialchars($s['nom']) ?></strong>
              <?php if (!empty($s['acronyme'])): ?>
                <span class="text-muted ms-1">(<?= htmlspecialchars($s['acronyme']) ?>)</span>
              <?php endif; ?>
            </div>
            <?php if ($is_complet): ?>
              <div class="text-end">
                <span class="badge bg-secondary"><?= $this->lang->line('gvv_vd_quota_complet_badge') ?></span>
                <?php if ($s['quota_status']['jours_reset'] > 0): ?>
                  <div class="small text-muted mt-1">
                    <?= sprintf($this->lang->line('gvv_vd_quota_complet_reset'), (int) $s['quota_status']['jours_reset']) ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($section_id > 0 && $section_row): ?>

    <?php if (!empty($quota_status) && $quota_status['atteint']): ?>
      <!-- Écran quota atteint -->
      <div id="quota-alert" class="alert alert-warning">
        <h5 class="alert-heading">
          <i class="fas fa-clock me-1"></i><?= $this->lang->line('gvv_vd_quota_atteint_titre') ?>
        </h5>
        <p><?= $this->lang->line('gvv_vd_quota_atteint_msg') ?></p>
        <?php if ($quota_status['jours_reset'] > 0): ?>
          <p class="mb-0">
            <?= sprintf($this->lang->line('gvv_vd_quota_reset_dans'), (int) $quota_status['jours_reset']) ?>
          </p>
        <?php endif; ?>
      </div>

      <?php if (!empty($sections_alternatives)): ?>
        <p class="fw-semibold"><?= $this->lang->line('gvv_vd_quota_autres_sections') ?></p>
        <ul class="list-group mb-4">
          <?php foreach ($sections_alternatives as $alt): ?>
          <li class="list-group-item">
            <a href="<?= site_url('vols_decouverte/public_vd?section=' . (int) $alt['id']) ?>">
              <?= htmlspecialchars($alt['nom']) ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-muted"><?= $this->lang->line('gvv_vd_quota_aucune_autre') ?></p>
      <?php endif; ?>

    <?php else: ?>
      <!-- Formulaire de réservation -->
      <?php if (empty($products)): ?>
        <div class="alert alert-info"><?= $this->lang->line('gvv_vd_public_no_product') ?></div>
      <?php else: ?>

      <?php if ($has_validation_errors): ?>
        <div class="alert alert-danger" role="alert">
          Une erreur s'est produite. Veuillez vérifier les champs du formulaire.
        </div>
      <?php endif; ?>

      <form method="post" action="<?= site_url('vols_decouverte/public_vd') ?>" novalidate>
        <input type="hidden" name="section_id" value="<?= (int) $section_id ?>">

        <!-- Sélecteur de produit -->
        <div class="mb-3">
          <label class="form-label fw-semibold" for="product_ref">
            <?= $this->lang->line('gvv_vols_decouverte_field_product') ?> <span class="text-danger">*</span>
          </label>
          <select name="product_ref" id="product_ref" class="form-select"
                  onchange="updateNbPersonnes(this)">
            <?php foreach ($products as $p):
              $selected = ($fv('product_ref') === $p['reference']) ? 'selected' : '';
            ?>
            <option value="<?= htmlspecialchars($p['reference'], ENT_QUOTES) ?>"
                    data-nb-max="<?= (int) $p['nb_personnes_max'] ?>"
                    <?= $selected ?>>
              <?= htmlspecialchars($p['description']) ?>
              — <?= number_format((float) $p['prix'], 2, ',', ' ') ?>&nbsp;€
              <?php if ((int) $p['nb_personnes_max'] > 1): ?>
                (<?= sprintf($this->lang->line('gvv_vd_public_nb_personnes_max'), (int) $p['nb_personnes_max']) ?>)
              <?php endif; ?>
            </option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['product'])): ?>
            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['product']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Bénéficiaire -->
        <div class="mb-3">
          <label class="form-label fw-semibold" for="beneficiaire">
            <?= $this->lang->line('gvv_vd_public_beneficiaire') ?> <span class="text-danger">*</span>
          </label>
          <input type="text" name="beneficiaire" id="beneficiaire" class="form-control"
                 value="<?= $fv('beneficiaire') ?>" required>
          <?php if (!empty($errors['beneficiaire'])): ?>
            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['beneficiaire']) ?></div>
          <?php endif; ?>
        </div>

        <!-- De la part de -->
        <div class="mb-3">
          <label class="form-label" for="de_la_part">
            <?= $this->lang->line('gvv_vd_public_de_la_part') ?>
          </label>
          <input type="text" name="de_la_part" id="de_la_part" class="form-control"
                 value="<?= $fv('de_la_part') ?>">
        </div>

        <!-- Occasion -->
        <div class="mb-3">
          <label class="form-label" for="occasion">
            <?= $this->lang->line('gvv_vd_public_occasion') ?>
          </label>
          <input type="text" name="occasion" id="occasion" class="form-control"
                 value="<?= $fv('occasion') ?>"
                 placeholder="Anniversaire, Noël…">
        </div>

        <!-- Email acheteur -->
        <div class="mb-3">
          <label class="form-label fw-semibold" for="acheteur_email">
            <?= $this->lang->line('gvv_vd_public_acheteur_email') ?> <span class="text-danger">*</span>
          </label>
          <input type="email" name="acheteur_email" id="acheteur_email" class="form-control"
                 value="<?= $fv('acheteur_email') ?>" required>
          <?php if (!empty($errors['acheteur_email'])): ?>
            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['acheteur_email']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Téléphone acheteur -->
        <div class="mb-3">
          <label class="form-label fw-semibold" for="acheteur_tel">
            <?= $this->lang->line('gvv_vd_public_acheteur_tel') ?> <span class="text-danger">*</span>
          </label>
          <input type="tel" name="acheteur_tel" id="acheteur_tel" class="form-control"
                 value="<?= $fv('acheteur_tel') ?>" required>
          <?php if (!empty($errors['acheteur_tel'])): ?>
            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['acheteur_tel']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Contact urgence -->
        <div class="mb-3">
          <label class="form-label fw-semibold" for="urgence">
            <?= $this->lang->line('gvv_vd_public_urgence') ?> <span class="text-danger">*</span>
          </label>
          <input type="text" name="urgence" id="urgence" class="form-control"
                 value="<?= $fv('urgence') ?>" required>
          <?php if (!empty($errors['urgence'])): ?>
            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['urgence']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Poids cumulé -->
        <div class="mb-3">
          <label class="form-label" for="poids_passagers">
            <?= $this->lang->line('gvv_vd_public_poids') ?>
          </label>
          <div class="input-group" style="max-width:160px;">
            <input type="number" name="poids_passagers" id="poids_passagers" class="form-control"
                   value="<?= $fv('poids', '0') ?>" min="0" step="1">
            <span class="input-group-text">kg</span>
          </div>
        </div>

        <!-- Nombre de passagers (masqué si nb_personnes_max = 1) -->
        <?php
        $first_product = reset($products);
        $first_nb_max  = $first_product ? (int) $first_product['nb_personnes_max'] : 1;
        ?>
        <div id="nb-personnes-block" class="mb-3" <?= $first_nb_max <= 1 ? 'style="display:none"' : '' ?>>
          <label class="form-label fw-semibold" for="nb_personnes">
            <?= $this->lang->line('gvv_vd_public_nb_personnes') ?>
          </label>
          <input type="number" name="nb_personnes" id="nb_personnes" class="form-control"
                 style="max-width:120px;"
                 value="<?= $fv('nb_personnes', '1') ?>"
                 min="1" max="<?= $first_nb_max ?>">
          <div id="nb-personnes-hint" class="form-text text-muted">
            <?php if ($first_nb_max > 1): ?>
              <?= sprintf($this->lang->line('gvv_vd_public_nb_personnes_max'), $first_nb_max) ?>
            <?php endif; ?>
          </div>
          <?php if (!empty($errors['nb_personnes'])): ?>
            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['nb_personnes']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Bouton paiement -->
        <div class="mt-4">
          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-credit-card me-2"></i><?= $this->lang->line('gvv_vd_public_pay_btn') ?>
          </button>
        </div>

      </form>

      <?php endif; // products ?>
    <?php endif; // quota atteint ?>

  <?php endif; // section sélectionnée ?>

  <?php if (!empty($contact_email) || !empty($contact_signature)): ?>
  <hr class="mt-4">
  <p class="text-muted small text-center">
    <?= $this->lang->line('gvv_vd_public_contact_us') ?>
    <?php if (!empty($contact_email)): ?>
      <a href="mailto:<?= htmlspecialchars($contact_email, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($contact_email, ENT_QUOTES, 'UTF-8') ?>
      </a>
    <?php endif; ?>
    <?php if (!empty($contact_signature)): ?>
      — <?= htmlspecialchars($contact_signature, ENT_QUOTES, 'UTF-8') ?>
    <?php endif; ?>
  </p>
  <?php endif; ?>

</div><!-- /container -->

<script>
function updateNbPersonnes(select) {
    var opt    = select.options[select.selectedIndex];
    var nbMax  = parseInt(opt.getAttribute('data-nb-max'), 10) || 1;
    var block  = document.getElementById('nb-personnes-block');
    var input  = document.getElementById('nb_personnes');
    var hint   = document.getElementById('nb-personnes-hint');

    if (nbMax > 1) {
        block.style.display = '';
        input.max = nbMax;
        if (parseInt(input.value, 10) > nbMax) input.value = nbMax;
        hint.textContent = '<?= sprintf($this->lang->line('gvv_vd_public_nb_personnes_max'), 99) ?>'
            .replace('99', nbMax);
    } else {
        block.style.display = 'none';
        input.value = 1;
    }
}
</script>
