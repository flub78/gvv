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

<!-- Editable VLD fields -->
<div class="card mb-3">
    <div class="card-header"><i class="fas fa-plane"></i> <?= $this->lang->line('briefing_passager_field_vld') ?> #<?= htmlspecialchars($vld['id']) ?></div>
    <div class="card-body">
        <form method="post" action="<?= site_url('briefing_passager/update_vld/' . $vld_id) ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted"><?= $this->lang->line('briefing_passager_field_date_vol') ?></label>
                    <input type="date" name="date_vol" class="form-control form-control-sm"
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
                    <label class="form-label small text-muted"><?= $this->lang->line('briefing_passager_field_nom') ?></label>
                    <input type="text" name="beneficiaire" class="form-control form-control-sm"
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
            <div class="mt-2">
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-save"></i> <?= $this->lang->line('gvv_button_save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($briefing): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <?= $this->lang->line('briefing_passager_already_exists') ?>
    <a href="<?= site_url('briefing_passager/view/' . $briefing['id']) ?>" class="alert-link ms-2">
        <?= $this->lang->line('briefing_passager_view') ?>
    </a>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= site_url('briefing_passager/upload_submit/' . $vld_id) ?>" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label"><?= $this->lang->line('archived_documents_file') ?> <span class="text-danger">*</span></label>
                <input type="file" name="userfile" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                <div class="form-text">PDF, JPG ou PNG — 10 Mo max</div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $this->lang->line('briefing_passager_upload') ?>
                </button>
                <a href="<?= site_url('briefing_passager/generate_link/' . $vld_id) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-qrcode"></i> <?= $this->lang->line('briefing_passager_generate_link') ?>
                </a>
                <a href="<?= site_url('vols_decouverte') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_button_cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<div class="alert alert-danger"><?= $this->lang->line('briefing_passager_not_found') ?></div>
<?php endif; ?>

</div><!-- /body -->
