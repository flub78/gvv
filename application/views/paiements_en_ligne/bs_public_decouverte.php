<!-- VIEW: application/views/paiements_en_ligne/bs_public_decouverte.php -->
<?php
/**
 * Page publique de paiement d'un bon découverte (UC4).
 * Accessible sans connexion — QR code / lien email.
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<h3><?= $this->lang->line('gvv_public_decouverte_title') ?></h3>

<?php if (!empty($section)): ?>
<p class="text-muted">
    <?= $this->lang->line('gvv_public_bar_confirm_section') ?>
    <strong><?= htmlspecialchars($section['nom']) ?></strong>
</p>
<?php endif; ?>

<?php
$product     = isset($meta['product_description']) ? $meta['product_description'] : '-';
$beneficiaire = isset($meta['beneficiaire'])       ? $meta['beneficiaire']        : '-';
$de_la_part  = isset($meta['de_la_part'])          ? $meta['de_la_part']          : '';
$montant_fmt = euros((float) $tx['montant']);
?>

<div class="card mb-4" style="max-width: 560px;">
    <div class="card-body">

        <div class="mb-3">
            <span class="text-muted small"><?= $this->lang->line('gvv_decouverte_beneficiaire') ?></span><br>
            <strong class="fs-5"><?= htmlspecialchars($beneficiaire) ?></strong>
        </div>

        <?php if (!empty($de_la_part)): ?>
        <div class="mb-3">
            <span class="text-muted small"><?= $this->lang->line('gvv_decouverte_de_la_part') ?></span><br>
            <strong><?= htmlspecialchars($de_la_part) ?></strong>
        </div>
        <?php endif; ?>

        <div class="mb-3">
            <span class="text-muted small"><?= $this->lang->line('gvv_decouverte_product') ?></span><br>
            <strong><?= htmlspecialchars($product) ?></strong>
        </div>

        <div class="mb-3">
            <span class="text-muted small"><?= $this->lang->line('gvv_compta_label_montant') ?></span><br>
            <strong class="fs-5 text-warning"><?= $montant_fmt ?></strong>
        </div>

    </div>
</div>

<div class="alert alert-info small mb-4" style="max-width: 560px;">
    <i class="fas fa-info-circle"></i>
    <?= $this->lang->line('gvv_public_bar_helloasso_notice') ?>
</div>

<form method="post" action="<?= current_url() ?>" style="max-width: 560px;">
    <button type="submit" name="button" value="payer" class="btn btn-warning btn-lg w-100">
        <i class="fas fa-credit-card"></i> <?= $this->lang->line('gvv_public_decouverte_button_payer') ?>
    </button>
</form>

</div>
