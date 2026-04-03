<!-- VIEW: application/views/paiements_en_ligne/bs_confirmation.php -->
<?php
/**
 * Page de confirmation après paiement HelloAsso réussi (EF6).
 *
 * Variables :
 *   $transaction — données de la transaction (optionnel)
 */
?>

<div id="body" class="body container-fluid">

<div class="row justify-content-center mt-4">
  <div class="col-md-6">
    <div class="card border-success">
      <div class="card-body text-center py-5">
        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
        <h3 class="text-success"><?= $this->lang->line('gvv_pel_confirm_title') ?></h3>
        <p class="text-muted"><?= $this->lang->line('gvv_pel_confirm_intro') ?></p>
        <?php if (!empty($transaction)): ?>
          <p class="fs-5 fw-bold mt-3">
            <?= number_format((float)$transaction['montant'], 2, ',', ' ') ?> €
          </p>
        <?php endif; ?>
        <a href="<?= isset($back_url) ? $back_url : site_url('compta/mon_compte') ?>" class="btn btn-success mt-2">
          <i class="fas fa-home me-1"></i><?= $this->lang->line('gvv_pel_confirm_back') ?>
        </a>
      </div>
    </div>
  </div>
</div>

</div>
