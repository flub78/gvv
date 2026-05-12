<!-- VIEW: application/views/paiements_en_ligne/bs_paiement_generique_confirmation.php -->
<?php
/**
 * Page de confirmation après retour de HelloAsso — paiement générique.
 * L'écriture comptable est créée par le webhook ; cette page informe seulement.
 */
?>

<div id="body" class="body container-fluid">

<div class="alert alert-success mt-3" style="max-width: 560px;">
    <h4 class="alert-heading"><i class="fas fa-check-circle"></i> <?= $this->lang->line('gvv_paiement_generique_confirmed') ?></h4>
    <?php if (!empty($meta['description'])): ?>
        <hr>
        <p class="mb-0"><?= htmlspecialchars($meta['description']) ?></p>
    <?php endif; ?>
</div>

<div class="d-flex gap-2 mt-3">
    <a href="<?= site_url('paiements_en_ligne/paiement_generique') ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?= $this->lang->line('gvv_paiement_generique_qr_new') ?>
    </a>
    <a href="<?= site_url('paiements_en_ligne/liste') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-list"></i> <?= $this->lang->line('gvv_liste_menu') ?>
    </a>
</div>

</div>
