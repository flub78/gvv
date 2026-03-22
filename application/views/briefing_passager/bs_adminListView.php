<!-- VIEW: application/views/briefing_passager/bs_adminListView.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('briefing_passager');
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-list-alt"></i> <?= $this->lang->line('briefing_passager_list_title') ?></h3>

<?= $message ?>

<form method="get" action="<?= site_url('briefing_passager/admin_list') ?>" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
        <label class="form-label"><?= $this->lang->line('briefing_passager_filter_days') ?></label>
        <input type="number" name="days" value="<?= (int)$days ?>" min="1" max="3650" class="form-control" style="width:100px;">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary"><?= $this->lang->line('briefing_passager_filter_apply') ?></button>
    </div>
    <div class="col-auto ms-auto d-flex gap-2">
        <a href="<?= site_url('briefing_passager') ?>" class="btn btn-success">
            <i class="fas fa-plus"></i> <?= $this->lang->line('briefing_passager_add') ?>
        </a>
        <a href="<?= site_url('briefing_passager/export_pdf?days=' . (int)$days) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-file-pdf"></i> <?= $this->lang->line('briefing_passager_export_pdf') ?>
        </a>
    </div>
</form>

<?php if (empty($briefings)): ?>
    <div class="alert alert-info"><?= $this->lang->line('briefing_passager_no_briefings') ?></div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-sm">
    <thead class="table-dark">
        <tr>
            <th><?= $this->lang->line('briefing_passager_field_date_vol') ?></th>
            <th><?= $this->lang->line('briefing_passager_field_aerodrome') ?></th>
            <th><?= $this->lang->line('briefing_passager_field_appareil') ?></th>
            <th><?= $this->lang->line('briefing_passager_field_pilote') ?></th>
            <th><?= $this->lang->line('briefing_passager_field_nom') ?></th>
            <th><?= $this->lang->line('briefing_passager_field_mode') ?></th>
            <th><?= $this->lang->line('briefing_passager_field_date_sign') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($briefings as $b): ?>
    <tr>
        <td><?= $b['date_vol'] ? date_db2ht($b['date_vol']) : '—' ?></td>
        <td><?= htmlspecialchars($b['aerodrome'] ?? '—') ?></td>
        <td><?= htmlspecialchars($b['airplane_immat'] ?? '—') ?></td>
        <td><?= htmlspecialchars($b['pilote'] ?? '—') ?></td>
        <td><?= htmlspecialchars($b['beneficiaire'] ?? '—') ?></td>
        <td>
            <?php if ($b['type_code'] === 'briefing_passager'): ?>
                <span class="badge bg-secondary"><?= $this->lang->line('briefing_passager_mode_upload') ?></span>
            <?php else: ?>
                <span class="badge bg-primary"><?= $this->lang->line('briefing_passager_mode_digital') ?></span>
            <?php endif; ?>
        </td>
        <td><?= $b['uploaded_at'] ? date('d/m/Y H:i', strtotime($b['uploaded_at'])) : '—' ?></td>
        <td>
            <a href="<?= site_url('briefing_passager/view/' . $b['id']) ?>" class="btn btn-sm btn-outline-primary" title="<?= $this->lang->line('briefing_passager_view') ?>">
                <i class="fas fa-eye"></i>
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<div class="text-muted small"><?= count($briefings) ?> briefing(s)</div>
<?php endif; ?>

</div><!-- /body -->
