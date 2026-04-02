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
$show_transfer_qr = !empty($checkout_url);
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

<?php
$default_subject = $this->lang->line('gvv_decouverte_qr_email_default_subject');
$default_body    = sprintf(
    $this->lang->line('gvv_decouverte_qr_email_default_body'),
    $beneficiaire,
    $checkout_url,
    $sender_name
);
?>

<div class="mb-3">
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#emailModal">
        <i class="fas fa-envelope"></i> <?= $this->lang->line('gvv_decouverte_qr_email_button') ?>
    </button>
</div>

<!-- Modal envoi email lien paiement -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">
                    <i class="fas fa-envelope"></i> <?= $this->lang->line('gvv_decouverte_qr_email_modal_title') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= site_url('paiements_en_ligne/send_payment_link_email/' . htmlspecialchars($transaction_id)) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= $this->lang->line('gvv_decouverte_qr_email_to') ?></label>
                        <input type="email" name="to" class="form-control"
                               value="<?= htmlspecialchars($beneficiaire_email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= $this->lang->line('gvv_decouverte_qr_email_subject') ?></label>
                        <input type="text" name="subject" class="form-control"
                               value="<?= htmlspecialchars($default_subject) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= $this->lang->line('gvv_decouverte_qr_email_body') ?></label>
                        <textarea name="body" class="form-control" rows="10"><?= htmlspecialchars($default_body) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= $this->lang->line('gvv_button_cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> <?= $this->lang->line('gvv_decouverte_qr_email_send') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<a href="<?= site_url('vols_decouverte/create') ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_decouverte_qr_back') ?>
</a>

</div>
