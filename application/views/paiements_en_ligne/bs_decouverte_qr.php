<!-- VIEW: application/views/paiements_en_ligne/bs_decouverte_qr.php -->
<?php
/**
 * Page intermediaire QR code / lien direct - bon decouverte (UC4).
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<h3><?= $this->lang->line('gvv_decouverte_qr_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_decouverte_qr_intro') ?></p>

<?php
$product     = isset($meta['product_description']) ? htmlspecialchars($meta['product_description']) : '-';
$beneficiaire = isset($meta['beneficiaire']) ? htmlspecialchars($meta['beneficiaire']) : '-';
$offreur     = isset($meta['de_la_part']) ? htmlspecialchars($meta['de_la_part']) : '-';
$montant_fmt = euros((float) $transaction['montant']);
$show_transfer_qr = !empty($checkout_url) && empty($meta['initiated_by_user']);
?>

<div class="card mb-4" style="max-width: 700px;">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <span class="text-muted small"><?= $this->lang->line('gvv_decouverte_product') ?></span><br>
                <strong><?= $product ?></strong>
            </div>
            <div class="col-12 col-md-4">
                <span class="text-muted small"><?= $this->lang->line('gvv_decouverte_beneficiaire') ?></span><br>
                <strong><?= $beneficiaire ?></strong>
            </div>
            <div class="col-12 col-md-2">
                <span class="text-muted small"><?= $this->lang->line('gvv_decouverte_de_la_part') ?></span><br>
                <strong><?= $offreur ?></strong>
            </div>
            <div class="col-12 col-md-2">
                <span class="text-muted small"><?= $this->lang->line('gvv_compta_label_montant') ?></span><br>
                <strong><?= $montant_fmt ?></strong>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4" style="max-width: 760px;">

    <div class="col-12 col-md-8">
        <div class="card h-100 text-center">
            <div class="card-body d-flex flex-column justify-content-center">
                <h5 class="card-title"><?= $this->lang->line('gvv_decouverte_qr_direct_title') ?></h5>
                <p class="text-muted small"><?= $this->lang->line('gvv_decouverte_qr_direct_intro') ?></p>
                <?php if (!empty($checkout_url)): ?>
                <a href="<?= htmlspecialchars($checkout_url) ?>" class="btn btn-warning btn-lg mt-2">
                    <i class="fas fa-credit-card"></i> <?= $this->lang->line('gvv_decouverte_qr_direct_button') ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <?php if ($show_transfer_qr): ?>
        <div class="card h-100 text-center" data-testid="transfer-qr-card">
            <div class="card-body">
                <h5 class="card-title"><?= $this->lang->line('gvv_decouverte_qr_scan_title') ?></h5>
                <p class="text-muted small"><?= $this->lang->line('gvv_decouverte_qr_scan_intro') ?></p>
                <img src="<?= controller_url('paiements_en_ligne/decouverte_qr_image/' . htmlspecialchars($transaction_id)) ?>"
                     alt="QR code de transfert HelloAsso"
                     class="img-fluid mt-2"
                     data-testid="transfer-qr-image"
                     style="max-width: 120px;">
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info small mb-0"><?= $this->lang->line('gvv_decouverte_qr_scan_unnecessary') ?></div>
        <?php endif; ?>
    </div>

</div>

<a href="<?= site_url('vols_decouverte/create') ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_decouverte_qr_back') ?>
</a>

</div>
