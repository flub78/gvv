<?php
/**
 * État d'un secret : jamais sa valeur, seulement s'il est défini (EX-SEC-01).
 *
 * @var bool $defined une valeur est enregistrée
 */
?>
<span class="badge secret-status <?= $defined ? 'bg-success' : 'bg-secondary' ?>">
    <i class="fas <?= $defined ? 'fa-key' : 'fa-minus' ?> me-1"></i><?= $this->lang->line($defined ? 'parametres_secret_defined' : 'parametres_secret_undefined') ?>
</span>
