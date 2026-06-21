<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$status_labels = array(
    'success' => array('class' => 'bg-success', 'label' => 'Succès'),
    'failure' => array('class' => 'bg-danger',  'label' => 'Échec'),
    'skipped' => array('class' => 'bg-secondary','label' => 'Ignoré'),
);
?>
<div class="container-fluid py-3">

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">
        <i class="fas fa-bell me-2"></i>
        Logs des rappels de réservation
      </h5>
      <div class="d-flex gap-2 flex-wrap">
        <a href="<?= site_url('reservation_reminder_log') ?>"
           class="btn btn-sm <?= $status_filter === null ? 'btn-dark' : 'btn-outline-dark' ?>">
          Tous
        </a>
        <a href="<?= site_url('reservation_reminder_log?status=success') ?>"
           class="btn btn-sm <?= $status_filter === 'success' ? 'btn-success' : 'btn-outline-success' ?>">
          Succès
        </a>
        <a href="<?= site_url('reservation_reminder_log?status=failure') ?>"
           class="btn btn-sm <?= $status_filter === 'failure' ? 'btn-danger' : 'btn-outline-danger' ?>">
          Échecs
        </a>
        <a href="<?= site_url('reservation_reminder_log?status=skipped') ?>"
           class="btn btn-sm <?= $status_filter === 'skipped' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
          Ignorés
        </a>
      </div>
    </div>

    <div class="card-body p-0">
      <?php if (empty($logs)): ?>
        <p class="text-muted p-3 mb-0">Aucun log pour ce filtre.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Réservation</th>
                <th>Pilote</th>
                <th>Type</th>
                <th>Source</th>
                <th>Canal</th>
                <th>Statut</th>
                <th>Erreur</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <?php
                  $st = isset($status_labels[$log['status']]) ? $status_labels[$log['status']] : array('class'=>'bg-secondary','label'=>$log['status']);
                  $pilot_name = !empty($log['pilot_nom'])
                      ? htmlspecialchars(trim($log['pilot_prenom'] . ' ' . $log['pilot_nom']), ENT_QUOTES, 'UTF-8')
                      : htmlspecialchars($log['pilot_member_id'] ?? '-', ENT_QUOTES, 'UTF-8');
                  $date_str = $log['sent_at'] ? date('d/m/Y H:i', strtotime($log['sent_at'])) : '-';
                ?>
                <tr>
                  <td class="text-nowrap small"><?= $date_str ?></td>
                  <td class="text-nowrap">
                    #<?= (int) $log['reservation_id'] ?>
                    <?php if (!empty($log['start_datetime'])): ?>
                      <span class="text-muted small ms-1"><?= date('d/m H:i', strtotime($log['start_datetime'])) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="small"><?= $pilot_name ?></td>
                  <td class="small"><?= htmlspecialchars($log['notification_type'] ?? $log['action_type'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="small"><?= htmlspecialchars($log['trigger_source'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-nowrap small"><?= htmlspecialchars($log['channel'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
                  </td>
                  <td class="small text-danger">
                    <?= htmlspecialchars($log['error_message'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="p-2">
          <small class="text-muted"><?= count($logs) ?> entrée(s)</small>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>
