<?php
/**
 * Adresse publique d'une page, avec boutons Copier et (facultatif) Aperçu.
 *
 * @var string $url      adresse publique
 * @var bool   $preview  afficher le bouton Aperçu
 */
?>
<span class="ms-auto small">
    <?= $this->lang->line('parametres_public_url') ?> :
    <a href="<?= $url ?>" target="_blank" class="public-url"><?= $url ?></a>
    <button type="button" class="btn btn-outline-secondary btn-sm ms-1 copy-url" data-url="<?= $url ?>">
        <i class="fas fa-copy"></i> <?= $this->lang->line('parametres_copy') ?>
    </button>
    <?php if (!empty($preview)): ?>
    <a href="<?= $url ?>" target="_blank" class="btn btn-outline-primary btn-sm ms-1">
        <i class="fas fa-eye"></i> <?= $this->lang->line('parametres_preview') ?>
    </a>
    <?php endif; ?>
</span>
