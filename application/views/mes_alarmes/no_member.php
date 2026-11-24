<?php
/**
 * Mes alarmes, pour un compte sans fiche membre : explication et, pour un
 * administrateur du club, choix du membre dont consulter les alarmes.
 *
 * @see application/controllers/mes_alarmes.php
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div class="container-fluid py-3">

  <?= render_flash($this->session) ?>

  <h3 class="mb-3">
    <i class="fas fa-bell me-2" aria-hidden="true"></i><?= $this->lang->line('alarm_my_alarms_title') ?>
  </h3>

  <div class="alert alert-info alert-dismissible fade show" role="alert" id="alarm-no-member">
    <i class="fas fa-info-circle me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_no_member_record') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>

  <?php if ($is_admin && $members): ?>
    <form method="get" action="<?= site_url('mes_alarmes') ?>" class="row g-2 align-items-center mb-3" id="member-selector-form">
      <div class="col-auto">
        <label for="membre" class="col-form-label"><?= $this->lang->line('alarm_config_member') ?></label>
      </div>
      <div class="col-auto">
        <select name="membre" id="membre" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value=""></option>
          <?php foreach ($members as $login => $name): ?>
            <option value="<?= htmlspecialchars($login) ?>"><?= htmlspecialchars($name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  <?php endif; ?>
</div>
