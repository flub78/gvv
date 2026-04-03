<!-- VIEW: application/views/paiements_en_ligne/bs_public_decouverte_confirmation.php -->
<?php
/**
 * Confirmation publique de paiement bon decouverte.
 */
?>

<div id="body" class="body container-fluid">

<h3><?= $this->lang->line('gvv_decouverte_public_confirm_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_decouverte_public_confirm_intro') ?></p>

<div class="card mb-4" style="max-width: 500px;">
    <div class="card-body">

        <?php if (!empty($section)): ?>
        <div class="mb-2">
            <span class="text-muted small"><?= $this->lang->line('gvv_public_bar_confirm_section') ?></span><br>
            <strong><?= htmlspecialchars($section['nom']) ?></strong>
        </div>
        <?php endif; ?>

        <?php if (!empty($beneficiaire)): ?>
        <div class="mb-2">
            <span class="text-muted small"><?= $this->lang->line('gvv_decouverte_public_confirm_beneficiaire') ?></span><br>
            <strong><?= htmlspecialchars($beneficiaire) ?></strong>
        </div>
        <?php endif; ?>

        <?php if (!empty($montant)): ?>
        <div class="mb-2">
            <span class="text-muted small"><?= $this->lang->line('gvv_decouverte_public_confirm_montant') ?></span><br>
            <strong><?= $montant ?></strong>
        </div>
        <?php endif; ?>

        <?php if (!empty($email)): ?>
        <div class="mb-2">
            <span class="text-muted small"><?= $this->lang->line('gvv_decouverte_public_confirm_email') ?></span><br>
            <strong><?= htmlspecialchars($email) ?></strong>
        </div>
        <?php endif; ?>

    </div>
</div>

</div>
