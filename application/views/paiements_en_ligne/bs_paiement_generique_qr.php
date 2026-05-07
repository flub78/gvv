<!-- VIEW: application/views/paiements_en_ligne/bs_paiement_generique_qr.php -->
<?php
/**
 * Page QR code / lien HelloAsso — paiement générique (trésorier).
 */
$montant_fmt = number_format((float) $transaction['montant'], 2, ',', ' ') . ' €';
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<h3><?= $this->lang->line('gvv_paiement_generique_qr_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_paiement_generique_qr_intro') ?></p>

<!-- Récapitulatif -->
<div class="card mb-4" style="max-width: 640px;">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-12 col-md-6">
                <span class="text-muted small"><?= $this->lang->line('gvv_paiement_generique_qr_label_description') ?></span><br>
                <strong><?= htmlspecialchars($meta['description'] ?? '') ?></strong>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-muted small"><?= $this->lang->line('gvv_paiement_generique_qr_label_montant') ?></span><br>
                <strong><?= $montant_fmt ?></strong>
            </div>
            <div class="col-12 col-md-4">
                <span class="text-muted small"><?= $this->lang->line('gvv_paiement_generique_qr_label_compte') ?></span><br>
                <strong><?= htmlspecialchars($meta['compte_destination_nom'] ?? '') ?></strong>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($checkout_url)): ?>

<div class="row g-4 mb-4" style="max-width: 640px;">

    <!-- QR code -->
    <div class="col-12 col-md-5 text-center">
        <div class="card h-100">
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                <img src="<?= site_url('paiements_en_ligne/paiement_generique_qr_image/' . htmlspecialchars($transaction_id)) ?>"
                     alt="QR code paiement"
                     class="img-fluid mb-2"
                     style="max-width: 180px;">
                <div class="text-muted small">Scannez pour payer</div>
            </div>
        </div>
    </div>

    <!-- Lien et actions -->
    <div class="col-12 col-md-7">
        <div class="card h-100">
            <div class="card-body d-flex flex-column justify-content-center gap-3">

                <div>
                    <div class="text-muted small mb-1"><?= $this->lang->line('gvv_paiement_generique_qr_link_label') ?></div>
                    <div class="input-group">
                        <input type="text" id="checkout-url" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($checkout_url) ?>" readonly>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-copy"
                                onclick="copyLink()">
                            <i class="fas fa-copy"></i> <?= $this->lang->line('gvv_paiement_generique_qr_copy') ?>
                        </button>
                    </div>
                    <div id="copy-feedback" class="text-success small mt-1" style="display:none;">
                        <?= $this->lang->line('gvv_paiement_generique_qr_copied') ?>
                    </div>
                </div>

                <a href="<?= htmlspecialchars($checkout_url) ?>" target="_blank" rel="noopener"
                   class="btn btn-warning">
                    <i class="fas fa-credit-card"></i> <?= $this->lang->line('gvv_paiement_generique_qr_open') ?>
                </a>

            </div>
        </div>
    </div>

</div>

<?php else: ?>
    <div class="alert alert-danger"><?= $this->lang->line('gvv_paiement_generique_error_checkout') ?></div>
<?php endif; ?>

<div class="d-flex gap-2 mt-2">
    <a href="<?= site_url('paiements_en_ligne/paiement_generique') ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?= $this->lang->line('gvv_paiement_generique_qr_new') ?>
    </a>
    <a href="<?= site_url('paiements_en_ligne/liste') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-list"></i> <?= $this->lang->line('gvv_liste_menu') ?>
    </a>
</div>

</div>

<script>
function copyLink() {
    var input = document.getElementById('checkout-url');
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(function() {
            showCopied();
        });
    } else {
        input.select();
        document.execCommand('copy');
        showCopied();
    }
}
function showCopied() {
    var fb = document.getElementById('copy-feedback');
    fb.style.display = 'block';
    setTimeout(function() { fb.style.display = 'none'; }, 2000);
}
</script>
