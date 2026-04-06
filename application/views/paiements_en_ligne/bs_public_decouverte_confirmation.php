<!-- VIEW: application/views/paiements_en_ligne/bs_public_decouverte_confirmation.php -->
<?php
/**
 * Confirmation publique de paiement bon decouverte.
 */
?>

<div id="body" class="body container-fluid">

<h3><?= $this->lang->line('gvv_decouverte_public_confirm_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_decouverte_public_confirm_intro') ?></p>

<div class="alert alert-success" style="max-width: 700px;">
    <p class="mb-2">
        Votre bon vol de découverte vous a été envoyé par email.
        Si vous ne l'avez pas reçu, contactez-nous à
        <?php if (!empty($club_email)): ?>
            <a href="mailto:<?= htmlspecialchars($club_email) ?>"><?= htmlspecialchars($club_email) ?></a>.
        <?php else: ?>
            l'adresse email du club.
        <?php endif; ?>
    </p>
    <p class="mb-2">Au plaisir de vous rencontrer bientôt.</p>
    <?php if (!empty($signature)): ?>
        <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($signature, ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>
</div>

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
