<!-- VIEW: application/views/cartes_membre/bs_config.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-cog text-secondary"></i> <?= $this->lang->line('gvv_cartes_membre_config_title') ?></h4>
            <a href="<?= controller_url('cartes_membre/lot') ?>?year=<?= $year ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-id-card"></i> <?= $this->lang->line('gvv_cartes_membre_lot_title') ?>
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Sélecteur d'année -->
    <div class="row mb-4">
        <div class="col-md-4">
            <form method="get" action="<?= controller_url('cartes_membre/config') ?>">
                <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_year') ?></label>
                <div class="input-group">
                    <select name="year" class="form-select">
                        <?php foreach ($year_selector as $y => $label): ?>
                            <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-secondary"><?= $this->lang->line('gvv_button_select') ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">

        <!-- Fond recto -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><?= $this->lang->line('gvv_cartes_membre_fond_recto') ?></strong>
                    <span class="text-muted small ms-2">(85,6 × 54 mm, JPEG ou PNG)</span>
                </div>
                <div class="card-body">
                    <?php if ($fond_recto): ?>
                        <div class="mb-2 text-center">
                            <img src="<?= base_url('uploads/configuration/' . basename($fond_recto)) ?>"
                                 class="img-thumbnail" style="max-height:120px;"
                                 alt="<?= $this->lang->line('gvv_cartes_membre_fond_recto') ?>">
                        </div>
                        <p class="text-success small"><i class="fas fa-check-circle"></i> <?= $this->lang->line('gvv_cartes_membre_fond_defined') ?></p>
                    <?php else: ?>
                        <p class="text-muted small"><i class="fas fa-info-circle"></i> <?= $this->lang->line('gvv_cartes_membre_fond_absent') ?></p>
                    <?php endif; ?>
                    <form method="post" action="<?= controller_url('cartes_membre/config') ?>?year=<?= $year ?>" enctype="multipart/form-data">
                        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                        <input type="hidden" name="year" value="<?= $year ?>">
                        <input type="hidden" name="face" value="recto">
                        <div class="mb-2">
                            <input type="file" name="fond_recto" class="form-control form-control-sm" accept=".jpg,.jpeg,.png">
                        </div>
                        <button type="submit" name="upload" value="1" class="btn btn-sm btn-primary">
                            <i class="fas fa-upload"></i> <?= $this->lang->line('gvv_cartes_membre_upload') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fond verso -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><?= $this->lang->line('gvv_cartes_membre_fond_verso') ?></strong>
                    <span class="text-muted small ms-2">(85,6 × 54 mm, JPEG ou PNG)</span>
                </div>
                <div class="card-body">
                    <?php if ($fond_verso): ?>
                        <div class="mb-2 text-center">
                            <img src="<?= base_url('uploads/configuration/' . basename($fond_verso)) ?>"
                                 class="img-thumbnail" style="max-height:120px;"
                                 alt="<?= $this->lang->line('gvv_cartes_membre_fond_verso') ?>">
                        </div>
                        <p class="text-success small"><i class="fas fa-check-circle"></i> <?= $this->lang->line('gvv_cartes_membre_fond_defined') ?></p>
                    <?php else: ?>
                        <p class="text-muted small"><i class="fas fa-info-circle"></i> <?= $this->lang->line('gvv_cartes_membre_fond_absent') ?></p>
                    <?php endif; ?>
                    <form method="post" action="<?= controller_url('cartes_membre/config') ?>?year=<?= $year ?>" enctype="multipart/form-data">
                        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                        <input type="hidden" name="year" value="<?= $year ?>">
                        <input type="hidden" name="face" value="verso">
                        <div class="mb-2">
                            <input type="file" name="fond_verso" class="form-control form-control-sm" accept=".jpg,.jpeg,.png">
                        </div>
                        <button type="submit" name="upload" value="1" class="btn btn-sm btn-primary">
                            <i class="fas fa-upload"></i> <?= $this->lang->line('gvv_cartes_membre_upload') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div><!-- .row -->

    <div class="mt-3">
        <p class="text-muted small">
            <i class="fas fa-info-circle"></i>
            <?= $this->lang->line('gvv_cartes_membre_fond_info') ?>
        </p>
    </div>

</div>
