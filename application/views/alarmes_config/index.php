<?php
/**
 * Configuration des alarmes d'un membre
 *
 * @see application/controllers/alarmes_config.php
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
      <i class="fas fa-bell me-2" aria-hidden="true"></i><?= $this->lang->line('alarm_config_title') ?>
      <?php if (!$is_self): ?>
        <span class="badge bg-warning text-dark align-middle" style="font-size:0.5em;">
          <?= htmlspecialchars($member_name) ?>
        </span>
      <?php endif; ?>
    </h3>
    <?php if (has_alarm_subscriptions()): ?>
      <a href="<?= site_url('mes_alarmes') ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-list me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_my_alarms_title') ?>
      </a>
    <?php endif; ?>
  </div>

  <?php if ($is_admin): ?>
    <form method="get" action="<?= site_url('alarmes_config') ?>" class="row g-2 align-items-center mb-3" id="member-selector-form">
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

  <form method="post" action="<?= site_url('alarmes_config/save') ?>" id="alarm-config-form">
    <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_value ?>">
    <input type="hidden" name="membre" value="<?= htmlspecialchars($member['mlogin']) ?>">

    <div class="card mb-3">
      <div class="card-header"><strong><?= $this->lang->line('alarm_config_alarms') ?></strong></div>
      <div class="card-body">
        <p class="text-muted small"><?= $this->lang->line('alarm_config_alarms_help') ?></p>
        <?php if (empty($proposed)): ?>
          <p class="text-muted mb-0"><?= $this->lang->line('alarm_config_none_available') ?></p>
        <?php else: ?>
            <?php foreach ($proposed as $def): ?>
              <?php
              $sub = isset($effective[$def['id']]) ? $effective[$def['id']] : null;
              $mandatory = $sub && $sub['mandatory'];
              $by_other = $sub && $sub['created_by'] && $sub['created_by'] !== $member['mlogin'];
              ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="alarms[]" value="<?= (int) $def['id'] ?>"
                       id="alarm_<?= (int) $def['id'] ?>" <?= $sub ? 'checked' : '' ?> <?= $mandatory ? 'disabled' : '' ?>>
                <?php if ($mandatory): ?>
                  <input type="hidden" name="alarms[]" value="<?= (int) $def['id'] ?>">
                <?php endif; ?>
                <label class="form-check-label" for="alarm_<?= (int) $def['id'] ?>">
                  <?= htmlspecialchars($def['display_label']) ?>
                  <?php if ($def['section_name']): ?>
                    <span class="badge bg-secondary"><?= htmlspecialchars($def['section_name']) ?></span>
                  <?php endif; ?>
                  <?php if ($mandatory): ?>
                    <span class="badge bg-danger"><?= $this->lang->line('alarm_config_mandatory') ?></span>
                  <?php endif; ?>
                  <?php if ($sub && $sub['origin'] === 'category'): ?>
                    <span class="badge bg-info text-dark"><?= $this->lang->line('alarm_config_by_category') ?></span>
                  <?php elseif ($by_other): ?>
                    <span class="badge bg-light text-dark border">
                      <?= sprintf($this->lang->line('alarm_config_by_admin'),
                          htmlspecialchars(isset($creators[$sub['created_by']]) ? $creators[$sub['created_by']] : $sub['created_by'])) ?>
                    </span>
                  <?php endif; ?>
                </label>
              </div>
            <?php endforeach; ?>
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

    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_config_save') ?>
    </button>
  </form>
</div>
