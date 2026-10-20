<?php
/**
 * Administration des alarmes : catalogue, abonnements de catégories,
 * alarmes que personne ne reçoit
 *
 * @see application/controllers/alarmes_admin.php
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$csrf_name  = $this->security->get_csrf_token_name();
$csrf_value = $this->security->get_csrf_hash();
$csrf = '<input type="hidden" name="' . $csrf_name . '" value="' . $csrf_value . '">';
?>
<div class="container-fluid py-3">

  <?= render_flash($this->session) ?>

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h3 class="mb-0"><i class="fas fa-bell me-2" aria-hidden="true"></i><?= $this->lang->line('alarm_admin_title') ?></h3>
    <a href="<?= site_url('mes_alarmes') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-user-cog me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_admin_member_config') ?>
    </a>
  </div>

  <!-- Alarmes sans abonné -->
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong><?= $this->lang->line('alarm_admin_uncovered') ?></strong>
      <span class="badge <?= $uncovered ? 'bg-danger' : 'bg-success' ?>"><?= count($uncovered) ?></span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($uncovered)): ?>
        <p class="text-muted p-3 mb-0"><?= $this->lang->line('alarm_admin_uncovered_none') ?></p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0" id="uncovered-table">
            <thead class="table-light">
              <tr>
                <th><?= $this->lang->line('alarm_col_alarm') ?></th>
                <th><?= $this->lang->line('alarm_col_concerns') ?></th>
                <th class="text-center"><?= $this->lang->line('alarm_col_state') ?></th>
                <th class="text-center"><?= $this->lang->line('alarm_col_due') ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($uncovered as $item): ?>
                <tr>
                  <td <?= alarm_category_mark($item['category']) ?>>
                    <?= alarm_category_badge($item['category']) ?>
                    <div><?= htmlspecialchars($item['label']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($item['definition_label']) ?></div></td>
                  <td><?= htmlspecialchars($item['detail']) ?></td>
                  <td class="text-center"><span class="badge <?= $item['state'] === 'active' ? 'bg-danger' : 'bg-warning text-dark' ?>">
                    <?= $this->lang->line('alarm_state_' . $item['state']) ?></span></td>
                  <td class="text-center"><?= $item['due_date'] ? date_db2ht($item['due_date']) : '' ?></td>
                  <td><a href="<?= site_url($item['link']) ?>" class="btn btn-outline-primary btn-sm"><?= $this->lang->line('alarm_notification_open') ?></a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Désactivations en cours -->
  <div class="card mb-4" id="dismissals">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong><i class="fas fa-bell-slash me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_admin_dismissals') ?></strong>
      <span class="badge bg-secondary"><?= count($dismissals) ?></span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($dismissals)): ?>
        <p class="text-muted p-3 mb-0"><?= $this->lang->line('alarm_admin_dismissals_none') ?></p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0" id="dismissals-table">
            <thead class="table-light">
              <tr>
                <th><?= $this->lang->line('alarm_col_alarm') ?></th>
                <th><?= $this->lang->line('alarm_dismiss_scope') ?></th>
                <th><?= $this->lang->line('alarm_dismiss_by') ?></th>
                <th><?= $this->lang->line('alarm_dismiss_reason') ?></th>
                <th class="text-center"><?= $this->lang->line('alarm_dismiss_until') ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dismissals as $d): ?>
                <tr data-key="<?= htmlspecialchars($d['alarm_key']) ?>">
                  <td <?= $d['category'] ? alarm_category_mark($d['category']) : '' ?>>
                    <?= $d['category'] ? alarm_category_badge($d['category']) : '' ?>
                    <div><?= htmlspecialchars((string) $d['label']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($d['definition_label']) ?></div></td>
                  <td>
                    <?php if ($d['member_login'] === null): ?>
                      <span class="badge bg-dark"><?= $this->lang->line('alarm_dismiss_scope_all') ?></span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?= $this->lang->line('alarm_dismiss_scope_member') ?></span>
                      <?= htmlspecialchars(trim($d['mprenom'] . ' ' . $d['mnom']) ?: $d['member_login']) ?>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars(trim($d['author_prenom'] . ' ' . $d['author_nom']) ?: (string) $d['created_by']) ?>
                    <div class="small text-muted"><?= date_db2ht(substr($d['created_at'], 0, 10)) ?></div></td>
                  <td><?= htmlspecialchars((string) $d['reason']) ?></td>
                  <td class="text-center"><?= $d['until_date'] ? date_db2ht($d['until_date']) : $this->lang->line('alarm_dismiss_permanent') ?></td>
                  <td>
                    <form method="post" action="<?= site_url('alarmes_admin/reactivate') ?>" class="d-inline">
                      <?= $csrf ?>
                      <input type="hidden" name="dismissal_id" value="<?= (int) $d['id'] ?>">
                      <button type="submit" class="btn btn-outline-success btn-sm alarm-reactivate-btn">
                        <i class="fas fa-bell me-1" aria-hidden="true"></i><?= $this->lang->line('alarm_reactivate') ?></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Abonnements de catégories -->
  <div class="card mb-4">
    <div class="card-header"><strong><?= $this->lang->line('alarm_admin_categories') ?></strong></div>
    <div class="card-body">
      <p class="text-muted small"><?= $this->lang->line('alarm_admin_categories_help') ?></p>
      <form method="post" action="<?= site_url('alarmes_admin/add_category') ?>" class="row g-2 align-items-end mb-3" id="add-category-form">
        <?= $csrf ?>
        <div class="col-md-4">
          <label for="alarm_definition_id" class="form-label small mb-0"><?= $this->lang->line('alarm_col_alarm') ?></label>
          <select name="alarm_definition_id" id="alarm_definition_id" class="form-select form-select-sm" required>
            <?php foreach ($definitions as $def): ?>
              <?php if ($def['active']): ?>
                <option value="<?= (int) $def['id'] ?>"><?= htmlspecialchars($def['display_label']) ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="role_id" class="form-label small mb-0"><?= $this->lang->line('alarm_admin_role') ?></label>
          <select name="role_id" id="role_id" class="form-select form-select-sm" required>
            <?php foreach ($roles as $id => $label): ?>
              <option value="<?= (int) $id ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label for="section_id" class="form-label small mb-0"><?= $this->lang->line('alarm_col_section') ?></label>
          <select name="section_id" id="section_id" class="form-select form-select-sm">
            <option value=""><?= $this->lang->line('alarm_filter_all') ?></option>
            <?php foreach ($sections as $id => $name): ?>
              <option value="<?= (int) $id ?>"><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="mandatory" value="1" id="mandatory">
            <label class="form-check-label small" for="mandatory"><?= $this->lang->line('alarm_config_mandatory') ?></label>
          </div>
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-primary btn-sm"><?= $this->lang->line('alarm_admin_add') ?></button>
        </div>
      </form>

      <?php if (empty($categories)): ?>
        <p class="text-muted mb-0"><?= $this->lang->line('alarm_admin_categories_none') ?></p>
      <?php else: ?>
        <table class="table table-sm table-striped mb-0" id="categories-table">
          <thead class="table-light">
            <tr>
              <th><?= $this->lang->line('alarm_col_alarm') ?></th>
              <th><?= $this->lang->line('alarm_admin_role') ?></th>
              <th><?= $this->lang->line('alarm_col_section') ?></th>
              <th class="text-center"><?= $this->lang->line('alarm_config_mandatory') ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td><?= htmlspecialchars($cat['definition_label']) ?></td>
                <td><?= htmlspecialchars($cat['role_label']) ?></td>
                <td><?= htmlspecialchars($cat['section_name'] ?: $this->lang->line('alarm_filter_all')) ?></td>
                <td class="text-center"><?= $cat['mandatory'] ? '<i class="fas fa-check"></i>' : '' ?></td>
                <td>
                  <form method="post" action="<?= site_url('alarmes_admin/delete_category') ?>" class="d-inline">
                    <?= $csrf ?>
                    <input type="hidden" name="subscription_id" value="<?= (int) $cat['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm"><?= $this->lang->line('alarm_admin_delete') ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Catalogue -->
  <div class="card mb-4">
    <div class="card-header"><strong><?= $this->lang->line('alarm_admin_catalogue') ?></strong></div>
    <div class="card-body p-0">
      <form method="post" action="<?= site_url('alarmes_admin/save_catalogue') ?>" id="catalogue-form">
        <?= $csrf ?>
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0">
            <thead class="table-light">
              <tr>
                <th class="text-center"><?= $this->lang->line('alarm_admin_active') ?></th>
                <th><?= $this->lang->line('alarm_col_alarm') ?></th>
                <th><?= $this->lang->line('alarm_col_section') ?></th>
                <th><?= $this->lang->line('alarm_admin_lead_days') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($definitions as $def): ?>
                <tr>
                  <td class="text-center"><input class="form-check-input" type="checkbox" name="active[]" value="<?= (int) $def['id'] ?>" <?= $def['active'] ? 'checked' : '' ?>></td>
                  <td <?= alarm_category_mark($def['category']) ?>>
                    <?= alarm_category_badge($def['category']) ?> <?= htmlspecialchars($def['display_label']) ?></td>
                  <td><?= htmlspecialchars($def['section_name'] ?: $this->lang->line('alarm_filter_all')) ?></td>
                  <td style="max-width:7rem;">
                    <?php if ($def['source'] === 'document'
                              || ($def['source'] === 'computed' && Computed_alarm_provider::has_deadline($def['rule']))): ?>
                      <input type="number" min="0" class="form-control form-control-sm" name="lead_days[<?= (int) $def['id'] ?>]" value="<?= (int) $def['lead_days'] ?>">
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="p-2">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i><?= $this->lang->line('alarm_config_save') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
