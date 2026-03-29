<!-- VIEW: application/views/paiements_en_ligne/bs_index.php -->
<?php
/**
 * Liste des paiements en ligne du pilote connecté (EF6).
 *
 * Variables :
 *   $transactions — tableau de paiements (voir paiements_en_ligne_model::get_transactions)
 */

$statut_labels = array(
    'pending'   => array('label' => $this->lang->line('gvv_pel_statut_pending'),   'class' => 'warning'),
    'completed' => array('label' => $this->lang->line('gvv_pel_statut_completed'), 'class' => 'success'),
    'failed'    => array('label' => $this->lang->line('gvv_pel_statut_failed'),    'class' => 'danger'),
    'cancelled' => array('label' => $this->lang->line('gvv_pel_statut_cancelled'), 'class' => 'secondary'),
);
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3><i class="fas fa-credit-card text-primary me-2"></i><?= $this->lang->line('gvv_pel_index_title') ?></h3>
  <a href="<?= site_url('compta/mon_compte') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i><?= $this->lang->line('gvv_pel_confirm_back') ?>
  </a>
</div>

<p class="text-muted"><?= $this->lang->line('gvv_pel_index_intro') ?></p>

<?php if (empty($transactions)): ?>
  <div class="alert alert-info"><?= $this->lang->line('gvv_pel_index_empty') ?></div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover table-sm">
      <thead class="table-light">
        <tr>
          <th><?= $this->lang->line('gvv_pel_col_date') ?></th>
          <th><?= $this->lang->line('gvv_pel_col_montant') ?></th>
          <th><?= $this->lang->line('gvv_pel_col_plateforme') ?></th>
          <th><?= $this->lang->line('gvv_pel_col_statut') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $t): ?>
          <?php
          $s = isset($statut_labels[$t['statut']]) ? $statut_labels[$t['statut']] : array('label' => $t['statut'], 'class' => 'secondary');
          ?>
          <tr>
            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($t['date_demande']))) ?></td>
            <td><?= number_format((float)$t['montant'], 2, ',', ' ') ?> €</td>
            <td><?= htmlspecialchars($t['plateforme']) ?></td>
            <td><span class="badge bg-<?= $s['class'] ?>"><?= $s['label'] ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

</div>
