<?php
/**
 * Mes alarmes : configuration (matrice d'abonnement, réception) puis liste
 * des alarmes actives et à venir
 *
 * @see application/controllers/mes_alarmes.php
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$member_name = trim($member['mprenom'] . ' ' . $member['mnom']);
$csrf_name  = $this->security->get_csrf_token_name();
$csrf_value = $this->security->get_csrf_hash();
?>
<div class="container-fluid py-3">

  <?= render_flash($this->session) ?>

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h3 class="mb-0">
      <i class="fas fa-bell me-2" aria-hidden="true"></i><?= $this->lang->line('alarm_my_alarms_title') ?>
      <?php if (!$is_self): ?>
        <span class="badge bg-warning text-dark align-middle" style="font-size:0.5em;">
          <?= htmlspecialchars($member_name) ?>
        </span>
      <?php endif; ?>
    </h3>
    <a href="#alarm-list" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-list me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_list_title') ?>
      <span class="badge bg-secondary"><?= count($rows) ?></span>
    </a>
  </div>

  <?php if ($is_admin): ?>
    <form method="get" action="<?= site_url('mes_alarmes') ?>" class="row g-2 align-items-center mb-3" id="member-selector-form">
      <div class="col-auto">
        <label for="membre" class="col-form-label"><?= $this->lang->line('alarm_config_member') ?></label>
      </div>
      <div class="col-auto">
        <select name="membre" id="membre" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php foreach ($members as $login => $name): ?>
            <option value="<?= htmlspecialchars($login) ?>" <?= $login === $member['mlogin'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($name) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  <?php endif; ?>

  <!-- Configuration -->
  <form method="post" action="<?= site_url('mes_alarmes/save') ?>" id="configuration">
    <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_value ?>">
    <input type="hidden" name="membre" value="<?= htmlspecialchars($member['mlogin']) ?>">

    <div class="card mb-3">
      <div class="card-header"><strong><?= $this->lang->line('alarm_config_alarms') ?></strong></div>
      <div class="card-body">
        <p class="mb-2">
          <i class="fas fa-user-check text-success me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_config_own_help') ?>
        </p>
        <?php if (empty($proposed)): ?>
          <p class="text-muted small mb-0"><?= $this->lang->line('alarm_config_no_supervision') ?></p>
        <?php else: ?>
          <p class="text-muted small"><?= $this->lang->line('alarm_config_matrix_help') ?></p>
          <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover align-middle mb-0" id="alarm-matrix">
              <thead class="table-light">
                <tr>
                  <th class="align-bottom">
                    <input class="form-check-input matrix-all-toggle me-2" type="checkbox" id="all_toggle"
                           title="<?= htmlspecialchars($this->lang->line('alarm_config_toggle_all')) ?>"
                           aria-label="<?= htmlspecialchars($this->lang->line('alarm_config_toggle_all')) ?>">
                    <label for="all_toggle"><?= $this->lang->line('alarm_col_alarm') ?></label>
                  </th>
                  <?php foreach ($matrix_columns as $cell => $name): ?>
                    <th class="text-center">
                      <div><?= htmlspecialchars($name) ?></div>
                      <input class="form-check-input matrix-col-toggle" type="checkbox" data-cell="<?= (int) $cell ?>"
                             id="col_toggle_<?= (int) $cell ?>"
                             title="<?= htmlspecialchars(sprintf($this->lang->line('alarm_config_toggle_column'), $name)) ?>"
                             aria-label="<?= htmlspecialchars(sprintf($this->lang->line('alarm_config_toggle_column'), $name)) ?>">
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($proposed as $def): ?>
                  <?php $def_id = (int) $def['id']; ?>
                  <tr>
                    <td>
                      <input class="form-check-input matrix-row-toggle me-2" type="checkbox" data-alarm="<?= $def_id ?>"
                             id="row_toggle_<?= $def_id ?>"
                             title="<?= htmlspecialchars(sprintf($this->lang->line('alarm_config_toggle_row'), $def['display_label'])) ?>"
                             aria-label="<?= htmlspecialchars(sprintf($this->lang->line('alarm_config_toggle_row'), $def['display_label'])) ?>">
                      <label for="row_toggle_<?= $def_id ?>"><?= htmlspecialchars($def['display_label']) ?></label>
                    </td>
                    <?php foreach ($matrix_columns as $cell => $name): ?>
                      <td class="text-center">
                        <?php if (isset($def['cells'][$cell])): ?>
                          <?php
                          $sub = isset($effective[$def_id][$cell]) ? $effective[$def_id][$cell] : null;
                          $mandatory = $sub && $sub['mandatory'];
                          $origin = '';
                          if ($sub && $sub['origin'] === 'category') {
                              $origin = $this->lang->line('alarm_config_by_category');
                          } elseif ($sub && $sub['created_by'] && $sub['created_by'] !== $member['mlogin']) {
                              $origin = sprintf($this->lang->line('alarm_config_by_admin'),
                                  isset($creators[$sub['created_by']]) ? $creators[$sub['created_by']] : $sub['created_by']);
                          }
                          if ($mandatory) {
                              $origin = trim($this->lang->line('alarm_config_mandatory') . ' ' . $origin);
                          }
                          $label = $def['display_label'] . ' — ' . $name;
                          ?>
                          <input class="form-check-input matrix-cell" type="checkbox" name="cells[<?= $def_id ?>][]" value="<?= (int) $cell ?>"
                                 id="cell_<?= $def_id ?>_<?= (int) $cell ?>" data-alarm="<?= $def_id ?>" data-cell="<?= (int) $cell ?>"
                                 aria-label="<?= htmlspecialchars($label) ?>"
                                 <?= $sub ? 'checked' : '' ?> <?= $mandatory ? 'disabled' : '' ?>>
                          <?php if ($mandatory): ?>
                            <input type="hidden" name="cells[<?= $def_id ?>][]" value="<?= (int) $cell ?>">
                          <?php endif; ?>
                          <?php if ($origin): ?>
                            <i class="fas <?= $mandatory ? 'fa-lock text-danger' : 'fa-info-circle text-info' ?> ms-1"
                               title="<?= htmlspecialchars($origin) ?>" aria-label="<?= htmlspecialchars($origin) ?>"></i>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="text-muted" aria-hidden="true">—</span>
                        <?php endif; ?>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="small text-muted mt-2 mb-0">
            <?= $this->lang->line('alarm_config_toggle_help') ?><br>
            <i class="fas fa-lock text-danger" aria-hidden="true"></i> <?= $this->lang->line('alarm_config_mandatory') ?>
            &nbsp; <i class="fas fa-info-circle text-info" aria-hidden="true"></i> <?= $this->lang->line('alarm_config_origin_legend') ?>
          </p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><strong><?= $this->lang->line('alarm_config_delivery') ?></strong></div>
      <div class="card-body">
        <div class="mb-2"><?= $this->lang->line('alarm_config_channels') ?></div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="channel_email" value="1" id="channel_email"
                 <?= !empty($settings['channel_email']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="channel_email"><?= $this->lang->line('alarm_config_channel_email') ?></label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="channel_motd" value="1" id="channel_motd"
                 <?= !empty($settings['channel_motd']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="channel_motd"><?= $this->lang->line('alarm_config_channel_motd') ?></label>
        </div>

        <div class="mt-3 mb-1"><label for="frequency"><?= $this->lang->line('alarm_config_frequency') ?></label></div>
        <select name="frequency" id="frequency" class="form-select form-select-sm" style="max-width: 24rem;">
          <?php foreach ($frequencies as $frequency): ?>
            <option value="<?= $frequency ?>" <?= $settings['frequency'] === $frequency ? 'selected' : '' ?>>
              <?= $this->lang->line('alarm_frequency_' . $frequency) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div class="form-check mt-3">
          <input class="form-check-input" type="checkbox" name="sms" value="1" id="sms"
                 <?= !empty($settings['sms']) ? 'checked' : '' ?> <?= $has_mobile ? '' : 'disabled' ?>>
          <label class="form-check-label" for="sms"><?= $this->lang->line('alarm_config_sms') ?></label>
          <div class="form-text">
            <?= $has_mobile ? $this->lang->line('alarm_config_sms_help') : $this->lang->line('alarm_config_sms_no_mobile') ?>
          </div>
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary mb-4">
      <i class="fas fa-save me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_config_save') ?>
    </button>
  </form>

  <!-- Alarmes -->
  <h4 id="alarm-list"><i class="fas fa-exclamation-triangle text-danger me-2" aria-hidden="true"></i><?= $this->lang->line('alarm_list_title') ?></h4>

  <form method="get" action="<?= site_url('mes_alarmes') ?>#alarm-list" class="row g-2 align-items-end mb-3" id="alarm-filters">
    <?php if (!$is_self): ?>
      <input type="hidden" name="membre" value="<?= htmlspecialchars($member['mlogin']) ?>">
    <?php endif; ?>
    <div class="col-auto">
      <label for="alarme" class="form-label small mb-0"><?= $this->lang->line('alarm_col_alarm') ?></label>
      <select name="alarme" id="alarme" class="form-select form-select-sm">
        <option value=""><?= $this->lang->line('alarm_filter_all') ?></option>
        <?php foreach ($listed_defs as $id => $label): ?>
          <option value="<?= (int) $id ?>" <?= $filter_def === (int) $id ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
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
      <i class="fas fa-check-circle me-1" aria-hidden="true"></i><?= $this->lang->line(empty($effective) ? 'alarm_my_alarms_none' : 'alarm_my_alarms_none_followed') ?>
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
              <td><?= htmlspecialchars($row['detail']) ?>
                <?php if (!empty($row['own'])): ?>
                  <span class="badge bg-primary"><?= $this->lang->line('alarm_badge_own') ?></span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars(implode(', ', $row['section_names'])) ?></td>
              <td class="text-center">
                <span class="badge <?= $row['state'] === 'active' ? 'bg-danger' : 'bg-warning text-dark' ?>">
                  <?= $this->lang->line('alarm_state_' . $row['state']) ?>
                </span>
              </td>
              <td class="text-center"><?= $row['due_date'] ? date_db2ht($row['due_date']) : '' ?></td>
              <td>
                <?php if ($row['link'] !== ''): ?>
                  <a href="<?= site_url($row['link']) ?>" class="btn btn-outline-primary btn-sm"><?= $this->lang->line('alarm_notification_open') ?></a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
// Matrice : une case en tête de ligne ou de colonne coche ou décoche toutes
// les cases modifiables de la ligne ou de la colonne, celle du coin toutes
// les cases de la matrice (les cases obligatoires, désactivées, ne changent pas). Son état suit celui des cases : cochée si
// toutes le sont, indéterminée si certaines seulement.
(function () {
  var matrix = document.getElementById('alarm-matrix');
  if (!matrix) {
    return;
  }
  var TOGGLES = '.matrix-all-toggle, .matrix-row-toggle, .matrix-col-toggle';
  function cells(toggle) {
    var selector = '.matrix-cell';
    if (toggle.classList.contains('matrix-row-toggle')) {
      selector += '[data-alarm="' + toggle.dataset.alarm + '"]';
    } else if (toggle.classList.contains('matrix-col-toggle')) {
      selector += '[data-cell="' + toggle.dataset.cell + '"]';
    }
    return Array.prototype.slice.call(matrix.querySelectorAll(selector));
  }
  function refresh() {
    matrix.querySelectorAll(TOGGLES).forEach(function (toggle) {
      var editable = cells(toggle).filter(function (box) { return !box.disabled; });
      var checked = editable.filter(function (box) { return box.checked; }).length;
      toggle.disabled = editable.length === 0;
      toggle.checked = editable.length > 0 && checked === editable.length;
      toggle.indeterminate = checked > 0 && checked < editable.length;
    });
  }
  matrix.addEventListener('change', function (event) {
    var target = event.target;
    if (target.matches(TOGGLES)) {
      cells(target).forEach(function (box) {
        if (!box.disabled) {
          box.checked = target.checked;
        }
      });
    }
    refresh();
  });
  refresh();
})();
</script>
