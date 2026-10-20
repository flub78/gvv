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
    <a href="#configuration" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-cog me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_config_title') ?>
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

  <!-- Alarmes -->
  <h4 id="alarm-list"><i class="fas fa-exclamation-triangle text-danger me-2" aria-hidden="true"></i><?= $this->lang->line('alarm_list_title') ?>
    <span class="badge bg-secondary align-middle" style="font-size:0.6em;"><?= count($rows) ?></span></h4>

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

  <?php if ($hidden_count > 0): ?>
    <?php $all_sections_url = site_url('mes_alarmes') . '?section='
        . ($filter_def ? '&alarme=' . (int) $filter_def : '')
        . (!$is_self ? '&membre=' . rawurlencode($member['mlogin']) : '') . '#alarm-list'; ?>
    <div class="alert alert-info py-2" id="alarm-hidden-notice">
      <i class="fas fa-filter me-1" aria-hidden="true"></i>
      <?= sprintf($this->lang->line('alarm_filter_hidden'), $hidden_count, htmlspecialchars($sections[$filter_section])) ?>
      <a href="<?= $all_sections_url ?>" class="alert-link ms-1" id="alarm-show-all"><?= $this->lang->line('alarm_filter_show_all') ?></a>
    </div>
  <?php endif; ?>

  <?php if (empty($rows)): ?>
    <?php if ($hidden_count == 0): ?>
    <div class="alert alert-success">
      <i class="fas fa-check-circle me-1" aria-hidden="true"></i><?= $this->lang->line(empty($effective) ? 'alarm_my_alarms_none' : 'alarm_my_alarms_none_followed') ?>
    </div>
    <?php endif; ?>
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
            <?php $style = alarm_category_style($row['category']); ?>
            <tr class="<?= $row['state'] === 'active' ? 'table-danger' : 'table-warning' ?>" data-category="<?= htmlspecialchars($row['category']) ?>">
              <td <?= alarm_category_mark($row['category']) ?>>
                <?= alarm_category_badge($row['category']) ?>
                <div><?= htmlspecialchars($row['label']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($row['definition_label']) ?></div></td>
              <td><?= htmlspecialchars($row['detail']) ?>
                <?php if (!empty($row['own'])): ?>
                  <span class="badge bg-dark alarm-own"><i class="fas fa-user me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_badge_own') ?></span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars(implode(', ', $row['section_names'])) ?></td>
              <td class="text-center">
                <span class="badge <?= $row['state'] === 'active' ? 'bg-danger' : 'bg-warning text-dark' ?>">
                  <?= $this->lang->line('alarm_state_' . $row['state']) ?>
                </span>
              </td>
              <td class="text-center"><?= $row['due_date'] ? date_db2ht($row['due_date']) : '' ?></td>
              <td class="text-nowrap">
                <?php if ($row['link'] !== ''): ?>
                  <a href="<?= site_url($row['link']) ?>" class="btn btn-outline-primary btn-sm"><?= $this->lang->line('alarm_notification_open') ?></a>
                <?php endif; ?>
                <?php if (empty($row['own']) || $is_admin): ?>
                  <button type="button" class="btn btn-outline-secondary btn-sm alarm-dismiss-btn" data-bs-toggle="modal" data-bs-target="#dismiss-modal"
                          data-key="<?= htmlspecialchars($row['key']) ?>" data-label="<?= htmlspecialchars($row['label'] . ' — ' . $row['detail']) ?>"
                          data-own="<?= empty($row['own']) ? '0' : '1' ?>" title="<?= $this->lang->line('alarm_dismiss_title') ?>">
                    <i class="fas fa-bell-slash" aria-hidden="true"></i><span class="visually-hidden"><?= $this->lang->line('alarm_dismiss_title') ?></span>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($dismissed_rows): ?>
    <!-- Alarmes désactivées -->
    <div class="alert alert-secondary py-2 d-flex flex-wrap align-items-center gap-2" id="alarm-dismissed-notice">
      <span><i class="fas fa-bell-slash me-1" aria-hidden="true"></i><?= sprintf($this->lang->line('alarm_dismissed_count'), count($dismissed_rows)) ?></span>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#alarm-dismissed" id="alarm-dismissed-toggle">
        <?= $this->lang->line('alarm_dismissed_show') ?>
      </button>
    </div>
    <div class="collapse" id="alarm-dismissed">
      <div class="table-responsive">
        <table class="table table-sm table-striped text-muted" id="dismissed-table">
          <thead class="table-light">
            <tr>
              <th><?= $this->lang->line('alarm_col_alarm') ?></th>
              <th><?= $this->lang->line('alarm_col_concerns') ?></th>
              <th><?= $this->lang->line('alarm_dismiss_scope') ?></th>
              <th><?= $this->lang->line('alarm_dismiss_by') ?></th>
              <th><?= $this->lang->line('alarm_dismiss_reason') ?></th>
              <th class="text-center"><?= $this->lang->line('alarm_dismiss_until') ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dismissed_rows as $row): ?>
              <?php $d = $row['dismissal']; $for_all = ($d['member_login'] === null); ?>
              <tr data-key="<?= htmlspecialchars($row['key']) ?>">
                <td <?= alarm_category_mark($row['category']) ?>>
                  <?= alarm_category_badge($row['category']) ?>
                  <div><?= htmlspecialchars($row['label']) ?></div>
                  <div class="small"><?= htmlspecialchars($row['definition_label']) ?></div></td>
                <td><?= htmlspecialchars($row['detail']) ?></td>
                <td><span class="badge <?= $for_all ? 'bg-dark' : 'bg-secondary' ?>"><?= $this->lang->line($for_all ? 'alarm_dismiss_scope_all' : 'alarm_dismiss_scope_self') ?></span></td>
                <td><?= htmlspecialchars(isset($authors[$d['created_by']]) ? $authors[$d['created_by']] : (string) $d['created_by']) ?>
                  <div class="small"><?= date_db2ht(substr($d['created_at'], 0, 10)) ?></div></td>
                <td><?= htmlspecialchars((string) $d['reason']) ?></td>
                <td class="text-center"><?= $d['until_date'] ? date_db2ht($d['until_date']) : $this->lang->line('alarm_dismiss_permanent') ?></td>
                <td>
                  <?php if (!$for_all || $is_admin): ?>
                    <form method="post" action="<?= site_url('mes_alarmes/reactivate') ?>" class="d-inline">
                      <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_value ?>">
                      <input type="hidden" name="membre" value="<?= htmlspecialchars($member['mlogin']) ?>">
                      <input type="hidden" name="filter_alarme" value="<?= $filter_def ?: '' ?>">
                      <input type="hidden" name="filter_section" value="<?= $filter_section ?: '' ?>">
                      <input type="hidden" name="dismissal_id" value="<?= (int) $d['id'] ?>">
                      <button type="submit" class="btn btn-outline-success btn-sm alarm-reactivate-btn">
                        <i class="fas fa-bell me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_reactivate') ?></button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- Désactivation d'une alarme -->
  <div class="modal fade" id="dismiss-modal" tabindex="-1" aria-labelledby="dismiss-modal-title" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" action="<?= site_url('mes_alarmes/dismiss') ?>" class="modal-content" id="dismiss-form">
        <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_value ?>">
        <input type="hidden" name="membre" value="<?= htmlspecialchars($member['mlogin']) ?>">
        <input type="hidden" name="filter_alarme" value="<?= $filter_def ?: '' ?>">
        <input type="hidden" name="filter_section" value="<?= $filter_section ?: '' ?>">
        <input type="hidden" name="key" id="dismiss-key" value="">
        <div class="modal-header">
          <h5 class="modal-title" id="dismiss-modal-title"><i class="fas fa-bell-slash me-2" aria-hidden="true"></i><?= $this->lang->line('alarm_dismiss_title') ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $this->lang->line('alarm_dismiss_cancel') ?>"></button>
        </div>
        <div class="modal-body">
          <p class="fw-bold" id="dismiss-label"></p>
          <div class="mb-3">
            <div class="form-label"><?= $this->lang->line('alarm_dismiss_scope') ?></div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="dismiss-scope-self" value="self" checked>
              <label class="form-check-label" for="dismiss-scope-self"><?= $this->lang->line($is_self ? 'alarm_dismiss_for_me' : 'alarm_dismiss_for_member') ?></label>
            </div>
            <?php if ($is_admin): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="scope" id="dismiss-scope-all" value="all">
                <label class="form-check-label" for="dismiss-scope-all"><?= $this->lang->line('alarm_dismiss_for_all') ?></label>
              </div>
            <?php endif; ?>
            <div class="form-text" id="dismiss-own-help" style="display:none;"><?= $this->lang->line('alarm_dismiss_own_help') ?></div>
          </div>
          <div class="mb-3">
            <div class="form-label"><?= $this->lang->line('alarm_dismiss_duration') ?></div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="duration" id="dismiss-permanent" value="permanent" checked>
              <label class="form-check-label" for="dismiss-permanent"><?= $this->lang->line('alarm_dismiss_permanent') ?></label>
            </div>
            <div class="form-check d-flex align-items-center gap-2">
              <input class="form-check-input" type="radio" name="duration" id="dismiss-until" value="until">
              <label class="form-check-label" for="dismiss-until"><?= $this->lang->line('alarm_dismiss_until_label') ?></label>
              <input type="date" class="form-control form-control-sm w-auto" name="until_date" id="dismiss-until-date" min="<?= $today ?>">
            </div>
            <div class="form-text"><?= $this->lang->line('alarm_dismiss_auto_help') ?></div>
          </div>
          <div class="mb-2">
            <label for="dismiss-reason" class="form-label"><?= $this->lang->line('alarm_dismiss_reason') ?>
              <span class="text-muted small" id="dismiss-reason-hint"><?= $this->lang->line('alarm_dismiss_reason_optional') ?></span></label>
            <input type="text" class="form-control form-control-sm" name="reason" id="dismiss-reason" maxlength="255">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"><?= $this->lang->line('alarm_dismiss_cancel') ?></button>
          <button type="submit" class="btn btn-primary btn-sm" id="dismiss-submit"><i class="fas fa-bell-slash me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_dismiss_submit') ?></button>
        </div>
      </form>
    </div>
  </div>

  <!-- Configuration -->
  <h4 class="mt-4" id="configuration-title"><i class="fas fa-cog text-secondary me-2" aria-hidden="true"></i><?= $this->lang->line('alarm_config_title') ?></h4>
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
                <?php $current_category = null; ?>
                <?php foreach ($proposed as $def): ?>
                  <?php
                  $def_id = (int) $def['id'];
                  $style = alarm_category_style($def['category']);
                  ?>
                  <?php if ($def['category'] !== $current_category): ?>
                    <?php $current_category = $def['category']; ?>
                    <tr class="alarm-category-row">
                      <th colspan="<?= count($matrix_columns) + 1 ?>" class="text-<?= $style['color'] === 'warning' ? 'dark' : $style['color'] ?>" <?= alarm_category_mark($def['category']) ?>>
                        <i class="fas <?= $style['icon'] ?> me-2" aria-hidden="true"></i><?= $this->lang->line('alarm_category_' . $def['category']) ?>
                      </th>
                    </tr>
                  <?php endif; ?>
                  <tr data-category="<?= htmlspecialchars($def['category']) ?>">
                    <td <?= alarm_category_mark($def['category']) ?>>
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

// Désactivation : la boîte de dialogue reçoit l'alarme du bouton. Une alarme propre ne se désactive que pour tout le monde
// (administrateur club) ; le motif est alors obligatoire, la date l'est quand « jusqu'au » est choisi.
(function () {
  var modal = document.getElementById('dismiss-modal');
  if (!modal) {
    return;
  }
  var self = document.getElementById('dismiss-scope-self');
  var all = document.getElementById('dismiss-scope-all');
  var reason = document.getElementById('dismiss-reason');
  var until = document.getElementById('dismiss-until');
  var untilDate = document.getElementById('dismiss-until-date');
  function refresh() {
    var forAll = all && all.checked;
    reason.required = !!forAll;
    document.getElementById('dismiss-reason-hint').style.display = forAll ? 'none' : '';
    untilDate.required = until.checked;
  }
  modal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var own = button.dataset.own === '1';
    document.getElementById('dismiss-key').value = button.dataset.key;
    document.getElementById('dismiss-label').textContent = button.dataset.label;
    self.disabled = own;
    self.checked = !own;
    if (all) {
      all.checked = own;
    }
    document.getElementById('dismiss-own-help').style.display = own ? '' : 'none';
    refresh();
  });
  modal.addEventListener('change', refresh);
  untilDate.addEventListener('input', function () {
    until.checked = true;
    refresh();
  });
})();
</script>
