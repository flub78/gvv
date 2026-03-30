<!-- VIEW: application/views/paiements_en_ligne/bs_cotisation_qr.php -->
<?php
/**
 * Page intermédiaire QR code / lien direct — cotisation trésorier par carte (UC6).
 * Affichée après la création du checkout HelloAsso.
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<h3><?= $this->lang->line('gvv_cotisation_qr_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_cotisation_qr_intro') ?></p>

<?php
$pilote      = isset($meta['pilote_login'])     ? htmlspecialchars($meta['pilote_login'])     : '—';
$annee       = isset($meta['annee_cotisation']) ? (int) $meta['annee_cotisation']             : '—';
$montant_fmt = euros((float) $transaction['montant']);
?>

<div class="card mb-4" style="max-width: 600px;">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-6">
                <span class="text-muted small"><?= $this->lang->line('gvv_compta_label_pilote') ?></span><br>
                <strong><?= $pilote ?></strong>
            </div>
            <div class="col-3">
                <span class="text-muted small"><?= $this->lang->line('gvv_compta_label_annee_cotisation') ?></span><br>
                <strong><?= $annee ?></strong>
            </div>
            <div class="col-3">
                <span class="text-muted small"><?= $this->lang->line('gvv_compta_label_montant') ?></span><br>
                <strong><?= $montant_fmt ?></strong>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4" style="max-width: 700px;">

    <!-- QR code : pilote scanne avec son téléphone -->
    <div class="col-12 col-md-6">
        <div class="card h-100 text-center">
            <div class="card-body">
                <h5 class="card-title"><?= $this->lang->line('gvv_cotisation_qr_scan_title') ?></h5>
                <p class="text-muted small"><?= $this->lang->line('gvv_cotisation_qr_scan_intro') ?></p>
                <?php if (!empty($checkout_url)): ?>
                <img src="<?= controller_url('paiements_en_ligne/cotisation_qr_image/' . htmlspecialchars($transaction_id)) ?>"
                     alt="QR code HelloAsso"
                     class="img-fluid mt-2"
                     style="max-width: 220px;">
                <?php else: ?>
                <div class="alert alert-warning small"><?= $this->lang->line('gvv_cotisation_qr_url_missing') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bouton direct : payer sur cet écran -->
    <div class="col-12 col-md-6">
        <div class="card h-100 text-center">
            <div class="card-body d-flex flex-column justify-content-center">
                <h5 class="card-title"><?= $this->lang->line('gvv_cotisation_qr_direct_title') ?></h5>
                <p class="text-muted small"><?= $this->lang->line('gvv_cotisation_qr_direct_intro') ?></p>
                <?php if (!empty($checkout_url)): ?>
                <a href="<?= htmlspecialchars($checkout_url) ?>" class="btn btn-warning btn-lg mt-2">
                    <i class="fas fa-credit-card"></i> <?= $this->lang->line('gvv_cotisation_qr_direct_button') ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<a href="<?= site_url('compta/saisie_cotisation') ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_cotisation_qr_back') ?>
</a>

</div>
