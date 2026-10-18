<?php
/**
 * Mes alarmes : alarmes actives et à venir des abonnements du membre
 *
 * @see application/controllers/mes_alarmes.php
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div class="container-fluid py-3">

  <?= render_flash($this->session) ?>

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h3 class="mb-0"><i class="fas fa-bell me-2" aria-hidden="true"></i><?= $this->lang->line('alarm_my_alarms_title') ?></h3>
    <a href="<?= site_url('alarmes_config') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-cog me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_config_title') ?>
    </a>
  </div>

  <form method="get" action="<?= site_url('mes_alarmes') ?>" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
      <label for="alarme" class="form-label small mb-0"><?= $this->lang->line('alarm_col_alarm') ?></label>
      <select name="alarme" id="alarme" class="form-select form-select-sm">
        <option value=""><?= $this->lang->line('alarm_filter_all') ?></option>
        <?php foreach ($definitions as $id => $def): ?>
          <option value="<?= (int) $id ?>" <?= $filter_def === (int) $id ? 'selected' : '' ?>><?= htmlspecialchars($def['display_label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <label for="section" class="form-label small mb-0"><?= $this->lang->line('alarm_col_section') ?></label>
      <select name="section" id="section" class="form-select form-select-sm">
        <option value=""><?= $this->lang->line('alarm_filter_all') ?></option>
        <?php foreach ($sections as $id => $name): ?>
          <option value="<?= (int) $id ?>" <?= $filter_section === (int) $id ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary btn-sm"><?= $this->lang->line('alarm_filter_apply') ?></button>
    </div>
  </form>

  <?php if (empty($rows)): ?>
    <div class="alert alert-success">
      <i class="fas fa-check-circle me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_my_alarms_none') ?>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-striped table-hover" id="alarms-table">
        <thead class="table-light">
          <tr>
            <th><?= $this->lang->line('alarm_col_alarm') ?></th>
            <th><?= $this->lang->line('alarm_col_concerns') ?></th>
            <th><?= $this->lang->line('alarm_col_section') ?></th>
            <th class="text-center"><?= $this->lang->line('alarm_col_state') ?></th>
            <th class="text-center"><?= $this->lang->line('alarm_col_due') ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr class="<?= $row['state'] === 'active' ? 'table-danger' : 'table-warning' ?>">
              <td><?= htmlspecialchars($row['label']) ?>
                <div class="small text-muted"><?= htmlspecialchars($row['definition_label']) ?></div></td>
              <td><?= htmlspecialchars($row['detail']) ?></td>
              <td><?= htmlspecialchars(implode(', ', $row['section_names'])) ?></td>
              <td class="text-center">
                <span class="badge <?= $row['state'] === 'active' ? 'bg-danger' : 'bg-warning text-dark' ?>">
                  <?= $this->lang->line('alarm_state_' . $row['state']) ?>
                </span>
              </td>
              <td class="text-center"><?= $row['due_date'] ? date_db2ht($row['due_date']) : '' ?></td>
              <td><a href="<?= site_url($row['link']) ?>" class="btn btn-outline-primary btn-sm"><?= $this->lang->line('alarm_notification_open') ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
