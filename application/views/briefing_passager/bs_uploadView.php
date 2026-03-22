<!-- VIEW: application/views/briefing_passager/bs_uploadView.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('briefing_passager');
$this->lang->load('vols_decouverte');
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-upload"></i> <?= $this->lang->line('briefing_passager_upload') ?></h3>

<?= $message ?>

<?php if ($vld): ?>

<!-- Single unified form: VLD fields + action buttons -->
<form method="post" action="<?= site_url('briefing_passager/upload_submit/' . $vld_id) ?>" enctype="multipart/form-data">

<div class="card mb-3">
    <div class="card-header"><i class="fas fa-plane"></i> <?= $this->lang->line('briefing_passager_field_vld') ?> #<?= htmlspecialchars($vld['id']) ?></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted"><?= $this->lang->line('briefing_passager_field_date_vol') ?> <span class="text-danger">*</span></label>
                <input type="date" name="date_vol" class="form-control form-control-sm" required
                       value="<?= htmlspecialchars($vld['date_vol'] ?: date('Y-m-d')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted"><?= $this->lang->line('briefing_passager_field_aerodrome') ?></label>
                <select name="aerodrome" class="form-select form-select-sm">
                    <?php foreach ($terrain_selector as $oaci => $nom): ?>
                    <option value="<?= htmlspecialchars($oaci) ?>"
                        <?= ($vld['aerodrome'] === $oaci) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nom) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted"><?= $this->lang->line('briefing_passager_field_appareil') ?></label>
                <select name="airplane_immat" class="form-select form-select-sm">
                    <?php foreach ($machine_selector as $immat => $label): ?>
                    <option value="<?= htmlspecialchars($immat) ?>"
                        <?= ($vld['airplane_immat'] === $immat) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted"><?= $this->lang->line('briefing_passager_field_nom') ?> <span class="text-danger">*</span></label>
                <input type="text" name="beneficiaire" class="form-control form-control-sm" required
                       value="<?= htmlspecialchars($vld['beneficiaire'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted"><?= $this->lang->line('briefing_passager_field_pilote') ?></label>
                <select name="pilote" class="form-select form-select-sm">
                    <?php foreach ($pilote_selector as $login => $nom): ?>
                    <option value="<?= htmlspecialchars($login) ?>"
                        <?= ($vld['pilote'] === $login) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nom) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<?php if ($briefing): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mb-3">
    <div>
        <?php echo attachment($briefing['id'], $briefing['file_path'], site_url('archived_documents/preview/' . $briefing['id'])); ?>
    </div>
    <div class="flex-grow-1">
        <i class="fas fa-exclamation-triangle"></i>
        <?= $this->lang->line('briefing_passager_already_exists') ?>
        <div class="text-muted small mt-1">
            <?= htmlspecialchars($briefing['original_filename'] ?? '') ?>
            <?= $briefing['uploaded_at'] ? '— ' . date('d/m/Y H:i', strtotime($briefing['uploaded_at'])) : '' ?>
        </div>
    </div>
    <?php if ($is_dev_user): ?>
    <div>
        <button type="submit" form="form-delete-briefing" class="btn btn-sm btn-danger"
                onclick="return confirm('<?= $this->lang->line('briefing_passager_confirm_delete') ?>')">
            <i class="fas fa-trash"></i>
        </button>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label"><?= $this->lang->line('archived_documents_file') ?></label>
            <input type="file" name="userfile" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            <div class="form-text">PDF, JPG ou PNG — 10 Mo max</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" name="action" value="upload" class="btn btn-primary">
                <i class="fas fa-upload"></i> <?= $this->lang->line('briefing_passager_upload') ?>
            </button>
            <button type="submit" name="action" value="link" class="btn btn-outline-secondary">
                <i class="fas fa-qrcode"></i> <?= $this->lang->line('briefing_passager_generate_link') ?>
            </button>
            <button type="submit" name="action" value="save" class="btn btn-outline-primary">
                <i class="fas fa-save"></i> <?= $this->lang->line('gvv_button_save') ?>
            </button>
            <a href="<?= site_url('vols_decouverte') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_button_cancel') ?>
            </a>
        </div>
    </div>
</div>

</form>

<?php if (isset($briefing) && $briefing && $is_dev_user): ?>
<form id="form-delete-briefing" method="post"
      action="<?= site_url('briefing_passager/delete/' . $briefing['id']) ?>"></form>
<?php endif; ?>

<?php else: ?>
<div class="alert alert-danger"><?= $this->lang->line('briefing_passager_not_found') ?></div>
<?php endif; ?>

</div><!-- /body -->
