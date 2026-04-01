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
        <?php
        $payer_name = isset($tx_meta['payer_name']) ? trim((string)$tx_meta['payer_name']) : '';
        $payer_email = isset($tx_meta['payer_email']) ? trim((string)$tx_meta['payer_email']) : '';
        $description = isset($tx_meta['description']) ? trim((string)$tx_meta['description']) : '';
        ?>
        <?php if (!empty($transaction)): ?>
        <p>
            <?= sprintf(
                $this->lang->line('gvv_public_bar_confirm_detail'),
                number_format((float)$transaction['montant'], 2, ',', ' ') . ' €',
                htmlspecialchars($payer_name !== '' ? $payer_name : $this->lang->line('gvv_public_bar_confirm_unknown'))
            ) ?>
            <?php if ($payer_email !== ''): ?>
                (<?= htmlspecialchars($payer_email) ?>)
            <?php endif; ?>
        </p>
        <?php if ($description !== ''): ?>
        <p class="mb-0"><strong><?= $this->lang->line('gvv_public_bar_confirm_description') ?></strong> <?= htmlspecialchars($description) ?></p>
        <?php endif; ?>
        <?php else: ?>
        <p><?= $this->lang->line('gvv_public_bar_confirm_intro') ?></p>
        <?php endif; ?>
    </div>

    <?php if ($section): ?>
    <p class="text-muted">
        <?= $this->lang->line('gvv_public_bar_confirm_section') ?> <strong><?= htmlspecialchars($section['nom']) ?></strong>
    </p>
    <?php endif; ?>
</div>

</div>
