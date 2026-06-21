<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$csrf_name  = $this->security->get_csrf_token_name();
$csrf_value = $this->security->get_csrf_hash();
$confirm_msg = addslashes($this->lang->line('mes_reservations_confirm_delete'));
?>
<div class="container-fluid py-3">

  <?php if ($msg = $this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($msg = $this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- =====================================================================
       LISTE DES RÉSERVATIONS
       ===================================================================== -->
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="fas fa-calendar-alt me-2"></i>
        <?= $this->lang->line('mes_reservations_title') ?>
      </h5>
      <a href="<?= site_url('reservations') ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>
        <?= $this->lang->line('mes_reservations_btn_add') ?>
      </a>
    </div>
    <div class="card-body p-0">
      <?php if (empty($reservations)): ?>
        <p class="text-muted p-3 mb-0">
          <?= $this->lang->line('mes_reservations_no_resa') ?>
        </p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:70px;"></th>
                <th><?= $this->lang->line('mes_reservations_col_date') ?></th>
                <th><?= $this->lang->line('mes_reservations_col_aircraft') ?></th>
                <th><?= $this->lang->line('mes_reservations_col_role') ?></th>
                <th><?= $this->lang->line('mes_reservations_col_status') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reservations as $r): ?>
                <?php
                  $id    = (int) $r['id'];
                  $start = new DateTime($r['start_datetime']);
                  $end   = new DateTime($r['end_datetime']);
                  $role  = ($r['pilot_member_id'] === $username)
                         ? $this->lang->line('mes_reservations_role_pilot')
                         : $this->lang->line('mes_reservations_role_instructor');
                  $aircraft_label = $r['macimmat']
                      ? htmlspecialchars($r['macimmat'] . ' (' . $r['macmodele'] . ')', ENT_QUOTES, 'UTF-8')
                      : htmlspecialchars($r['aircraft_id'], ENT_QUOTES, 'UTF-8');
                  $edit_url = site_url('reservations/timeline?date=' . $start->format('Y-m-d'));
                ?>
                <tr>
                  <td class="text-nowrap">
                    <a href="<?= $edit_url ?>"
                       class="btn btn-sm btn-primary"
                       title="<?= $this->lang->line('mes_reservations_btn_edit') ?>">
                      <i class="fas fa-edit" aria-hidden="true"></i>
                    </a>
                    <a href="#"
                       class="btn btn-sm btn-danger"
                       title="<?= $this->lang->line('mes_reservations_btn_delete') ?>"
                       onclick="if(confirm('<?= $confirm_msg ?>')) document.getElementById('del-<?= $id ?>').submit(); return false;">
                      <i class="fas fa-trash" aria-hidden="true"></i>
                    </a>
                    <form id="del-<?= $id ?>"
                          method="post"
                          action="<?= site_url('mes_reservations/delete') ?>"
                          style="display:none">
                      <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_value ?>">
                      <input type="hidden" name="reservation_id" value="<?= $id ?>">
                    </form>
                  </td>
                  <td>
                    <strong><?= $start->format('d/m/Y') ?></strong>
                    <span class="text-muted small ms-1">
                      <?= $start->format('H:i') ?>–<?= $end->format('H:i') ?>
                    </span>
                  </td>
                  <td><?= $aircraft_label ?></td>
                  <td><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- =====================================================================
       PRÉFÉRENCES DE RAPPEL
       ===================================================================== -->
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-bell me-2"></i>
        <?= $this->lang->line('mes_reservations_prefs_title') ?>
      </h5>
    </div>
    <div class="card-body">
      <form method="post" action="<?= site_url('mes_reservations/save_preferences') ?>">
        <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_value ?>">

        <div class="mb-3">
          <label class="form-label fw-bold">
            <?= $this->lang->line('mes_reservations_prefs_channel') ?>
          </label>
          <div class="d-flex gap-3 flex-wrap">
            <?php
              $channels = array(
                'email'     => $this->lang->line('mes_reservations_channel_email'),
                'sms'       => $this->lang->line('mes_reservations_channel_sms'),
                'email+sms' => $this->lang->line('mes_reservations_channel_both'),
              );
              foreach ($channels as $val => $label):
                $checked = ($prefs['reminder_channel'] === $val) ? 'checked' : '';
            ?>
              <div class="form-check">
                <input class="form-check-input" type="radio"
                       name="reminder_channel" id="channel_<?= $val ?>"
                       value="<?= $val ?>" <?= $checked ?>>
                <label class="form-check-label" for="channel_<?= $val ?>">
                  <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mb-3" style="max-width:240px;">
          <label for="reminder_period_hours" class="form-label fw-bold">
            <?= $this->lang->line('mes_reservations_prefs_period') ?>
          </label>
          <input type="number" class="form-control" id="reminder_period_hours"
                 name="reminder_period_hours"
                 value="<?= (int) $prefs['reminder_period_hours'] ?>"
                 min="1" max="168" step="1">
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i>
          <?= $this->lang->line('mes_reservations_prefs_save') ?>
        </button>
      </form>
    </div>
  </div>

</div>
