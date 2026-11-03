<!-- VIEW: application/views/parametres/bs_historique.php -->
<?php
/**
 * Historique des modifications d'un onglet de la page Configuration.
 *
 * @var string $tab     onglet
 * @var array  $info    icône et libellé de l'onglet
 * @var array  $rows    lignes préparées pour la vue vue_parametres_history (valeurs échappées)
 * @var array  $result  compte rendu du dernier retour arrière : type, text
 */
?>
<div id="body" class="body container-fluid">

<h3>
    <i class="fas fa-history text-primary me-2"></i><?= $this->lang->line('parametres_history_title') ?> :
    <i class="<?= $info['icon'] ?> ms-1 me-1"></i><?= $this->lang->line($info['label']) ?>
</h3>

<?php if (!empty($result)): ?>
<div class="alert alert-<?= $result['type'] ?> alert-dismissible fade show" role="alert" id="parametres-restore-result">
    <?= htmlspecialchars($result['text']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<p class="mb-3">
    <a href="<?= site_url('parametres/index/' . $tab) ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-arrow-left me-1"></i><?= $this->lang->line('parametres_back_to_tab') ?>
    </a>
</p>

<?php if (empty($rows)): ?>
<div class="alert alert-info" id="parametres-history-empty"><?= $this->lang->line('parametres_history_empty') ?></div>
<?php else: ?>
<p class="form-text"><?= $this->lang->line('parametres_history_help') ?></p>
<?= $this->gvvmetadata->table('vue_parametres_history', array(
    'fields' => array('date', 'auteur', 'parametre', 'action', 'ancienne', 'nouvelle', 'retour'),
    'mode'   => 'ro',
    'class'  => 'table table-striped table-sm',
), $rows) ?>
<?php endif; ?>
</div>
