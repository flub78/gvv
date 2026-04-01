<!-- VIEW: application/views/paiements_en_ligne/bs_public_decouverte_confirmation.php -->
<?php
/**
 * Confirmation publique de paiement bon decouverte.
 */
?>

<div id="body" class="body container-fluid">

<h3><?= $this->lang->line('gvv_decouverte_public_confirm_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_decouverte_public_confirm_intro') ?></p>

<?php if (!empty($section)): ?>
    <p>
        <strong><?= $this->lang->line('gvv_public_bar_confirm_section') ?></strong>
        <?= htmlspecialchars($section['nom']) ?>
    </p>
<?php endif; ?>

</div>
