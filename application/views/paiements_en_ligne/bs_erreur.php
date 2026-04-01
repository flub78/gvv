<!-- VIEW: application/views/paiements_en_ligne/bs_erreur.php -->
<?php
/**
 * Page d'erreur après échec du paiement HelloAsso (EF6).
 */
?>

<div id="body" class="body container-fluid">

<div class="row justify-content-center mt-4">
  <div class="col-md-6">
    <div class="card border-danger">
      <div class="card-body text-center py-5">
        <i class="fas fa-exclamation-triangle text-danger fa-4x mb-3"></i>
        <h3 class="text-danger"><?= $this->lang->line('gvv_pel_error_title') ?></h3>
        <p class="text-muted"><?= $this->lang->line('gvv_pel_error_intro') ?></p>
        <a href="<?= site_url('compta/mon_compte') ?>" class="btn btn-secondary mt-2">
          <i class="fas fa-home me-1"></i><?= $this->lang->line('gvv_pel_error_back') ?>
        </a>
      </div>
    </div>
  </div>
</div>

</div>
