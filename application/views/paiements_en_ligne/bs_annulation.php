<!-- VIEW: application/views/paiements_en_ligne/bs_annulation.php -->
<?php
/**
 * Page d'annulation après refus ou abandon du paiement HelloAsso (EF6).
 */
?>

<div id="body" class="body container-fluid">

<div class="row justify-content-center mt-4">
  <div class="col-md-6">
    <div class="card border-warning">
      <div class="card-body text-center py-5">
        <i class="fas fa-times-circle text-warning fa-4x mb-3"></i>
        <h3 class="text-warning"><?= $this->lang->line('gvv_pel_cancel_title') ?></h3>
        <p class="text-muted"><?= $this->lang->line('gvv_pel_cancel_intro') ?></p>
        <a href="<?= site_url('compta/mon_compte') ?>" class="btn btn-secondary mt-2">
          <i class="fas fa-home me-1"></i><?= $this->lang->line('gvv_pel_cancel_back') ?>
        </a>
      </div>
    </div>
  </div>
</div>

</div>
