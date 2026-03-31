<!-- VIEW: application/views/paiements_en_ligne/bs_public_bar_confirmation.php -->
<?php
/**
 * Page de confirmation après paiement bar externe (UC2) — accès public.
 */
?>

<div id="body" class="body container-fluid">

<div class="mt-4 text-center" style="max-width: 520px; margin: 0 auto;">
    <div class="alert alert-success">
        <i class="fas fa-check-circle fa-2x mb-2"></i>
        <h4><?= $this->lang->line('gvv_public_bar_confirm_title') ?></h4>
        <p><?= $this->lang->line('gvv_public_bar_confirm_intro') ?></p>
    </div>

    <?php if ($section): ?>
    <p class="text-muted">
        <?= $this->lang->line('gvv_public_bar_confirm_section') ?> <strong><?= htmlspecialchars($section['nom']) ?></strong>
    </p>
    <?php endif; ?>
</div>

</div>
