<!-- VIEW: application/views/cartes_membre/bs_config.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

function rgb_to_hex($color) {
    return sprintf('#%02x%02x%02x', $color[0], $color[1], $color[2]);
}

$flash_msg = $this->session->flashdata('layout_message');
$flash_err = $this->session->flashdata('layout_error');

$var_field_labels = array(
    'nom_club'      => 'Nom du club',
    'saison'        => 'Saison (année)',
    'nom_prenom'    => 'Nom et Prénom',
    'numero_membre' => 'N° de membre',
    'activites'     => 'Activités',
    'numero_carte'  => 'N° de carte',
);

$font_options  = array('helvetica', 'times', 'courier');
$align_options = array('L' => 'Gauche', 'C' => 'Centre', 'R' => 'Droite');
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
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($flash_msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($flash_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($flash_err): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($flash_err) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Sélecteur d'année + actions JSON -->
    <div class="row mb-4 align-items-end g-3">
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
        <div class="col-md-8 d-flex gap-2 flex-wrap">
            <a href="<?= controller_url('cartes_membre/layout_export') ?>?year=<?= $year ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-download"></i> <?= $this->lang->line('gvv_cartes_membre_layout_export') ?>
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-upload"></i> <?= $this->lang->line('gvv_cartes_membre_layout_import') ?>
            </button>
            <form method="post" action="<?= controller_url('cartes_membre/layout_reset') ?>" class="d-inline"
                  onsubmit="return confirm('Réinitialiser la mise en page au défaut ?')">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <input type="hidden" name="year" value="<?= $year ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-undo"></i> <?= $this->lang->line('gvv_cartes_membre_layout_reset') ?>
                </button>
            </form>
        </div>
    </div>

    <!-- ================================================================
         Section 1 : Fonds recto / verso
    ================================================================ -->
    <h5 class="mb-3"><?= $this->lang->line('gvv_cartes_membre_config') ?></h5>

    <div class="row g-4 mb-5">

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

    </div><!-- .row fonds -->

    <p class="text-muted small mb-4">
        <i class="fas fa-info-circle"></i> <?= $this->lang->line('gvv_cartes_membre_fond_info') ?>
    </p>

    <!-- ================================================================
         Section 2 : Mise en page (onglets Recto / Verso)
    ================================================================ -->
    <h5 class="mb-3"><?= $this->lang->line('gvv_cartes_membre_layout_title') ?></h5>

    <form method="post" action="<?= controller_url('cartes_membre/layout_save') ?>">
        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
        <input type="hidden" name="year" value="<?= $year ?>">

        <ul class="nav nav-tabs mb-3" id="faceTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="recto-tab" data-bs-toggle="tab" data-bs-target="#tab-recto" type="button">
                    <?= $this->lang->line('gvv_cartes_membre_layout_recto') ?>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="verso-tab" data-bs-toggle="tab" data-bs-target="#tab-verso" type="button">
                    <?= $this->lang->line('gvv_cartes_membre_layout_verso') ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="faceTabsContent">

            <?php foreach (array('recto', 'verso') as $fi => $face):
                $face_layout = $layout[$face];
                $active_class = ($fi === 0) ? 'show active' : '';
            ?>
            <div class="tab-pane fade <?= $active_class ?>" id="tab-<?= $face ?>">

                <!-- Champs variables -->
                <div class="card mb-3">
                    <div class="card-header"><strong><?= $this->lang->line('gvv_cartes_membre_layout_variable') ?></strong></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_field') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_enabled') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_x') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_y') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_font') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_bold') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_size') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_color') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_align') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_width') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($face_layout['variable_fields'] as $field): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($var_field_labels[$field['id']] ?? $field['id']) ?>
                                        <input type="hidden" name="<?= $face ?>_var_id[]" value="<?= htmlspecialchars($field['id']) ?>">
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input" name="<?= $face ?>_var_enabled[]"
                                               value="1" <?= !empty($field['enabled']) ? 'checked' : '' ?>>
                                    </td>
                                    <td><input type="number" step="0.1" class="form-control form-control-sm" style="width:70px"
                                               name="<?= $face ?>_var_x[]" value="<?= $field['x'] ?>"></td>
                                    <td><input type="number" step="0.1" class="form-control form-control-sm" style="width:70px"
                                               name="<?= $face ?>_var_y[]" value="<?= $field['y'] ?>"></td>
                                    <td>
                                        <select class="form-select form-select-sm" name="<?= $face ?>_var_font[]" style="width:110px">
                                            <?php foreach ($font_options as $fo): ?>
                                                <option value="<?= $fo ?>" <?= ($field['font'] === $fo) ? 'selected' : '' ?>><?= $fo ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input" name="<?= $face ?>_var_bold[]"
                                               value="1" <?= !empty($field['bold']) ? 'checked' : '' ?>>
                                    </td>
                                    <td><input type="number" min="4" max="24" class="form-control form-control-sm" style="width:60px"
                                               name="<?= $face ?>_var_size[]" value="<?= $field['size'] ?>"></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="color" class="form-control form-control-color flex-shrink-0"
                                                   name="<?= $face ?>_var_color[]"
                                                   value="<?= rgb_to_hex($field['color']) ?>" style="width:38px;height:32px">
                                            <input type="text" class="form-control form-control-sm color-hex-text"
                                                   value="<?= rgb_to_hex($field['color']) ?>"
                                                   maxlength="7" style="width:78px;font-family:monospace" tabindex="-1">
                                        </div>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" name="<?= $face ?>_var_align[]" style="width:90px">
                                            <?php foreach ($align_options as $ak => $av): ?>
                                                <option value="<?= $ak ?>" <?= (($field['align'] ?? 'L') === $ak) ? 'selected' : '' ?>><?= $av ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.5" class="form-control form-control-sm" style="width:70px"
                                               name="<?= $face ?>_var_width[]" value="<?= $field['width'] ?? 60 ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- Champs statiques -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?= $this->lang->line('gvv_cartes_membre_layout_static') ?></strong>
                        <button type="button" class="btn btn-sm btn-outline-success add-static-row" data-face="<?= $face ?>">
                            <i class="fas fa-plus"></i> <?= $this->lang->line('gvv_cartes_membre_layout_add_static') ?>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 align-middle" id="static-table-<?= $face ?>">
                            <thead class="table-light">
                                <tr>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_text') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_x') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_y') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_font') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_bold') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_size') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_color') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_align') ?></th>
                                    <th><?= $this->lang->line('gvv_cartes_membre_layout_width') ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($face_layout['static_fields'] as $sf): ?>
                                <tr>
                                    <td><input type="text" class="form-control form-control-sm"
                                               name="<?= $face ?>_st_text[]" value="<?= htmlspecialchars($sf['text']) ?>" style="min-width:120px"></td>
                                    <td><input type="number" step="0.1" class="form-control form-control-sm" style="width:70px"
                                               name="<?= $face ?>_st_x[]" value="<?= $sf['x'] ?>"></td>
                                    <td><input type="number" step="0.1" class="form-control form-control-sm" style="width:70px"
                                               name="<?= $face ?>_st_y[]" value="<?= $sf['y'] ?>"></td>
                                    <td>
                                        <select class="form-select form-select-sm" name="<?= $face ?>_st_font[]" style="width:110px">
                                            <?php foreach ($font_options as $fo): ?>
                                                <option value="<?= $fo ?>" <?= ($sf['font'] === $fo) ? 'selected' : '' ?>><?= $fo ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input" name="<?= $face ?>_st_bold[]"
                                               value="1" <?= !empty($sf['bold']) ? 'checked' : '' ?>>
                                    </td>
                                    <td><input type="number" min="4" max="24" class="form-control form-control-sm" style="width:60px"
                                               name="<?= $face ?>_st_size[]" value="<?= $sf['size'] ?>"></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="color" class="form-control form-control-color flex-shrink-0"
                                                   name="<?= $face ?>_st_color[]"
                                                   value="<?= rgb_to_hex($sf['color']) ?>" style="width:38px;height:32px">
                                            <input type="text" class="form-control form-control-sm color-hex-text"
                                                   value="<?= rgb_to_hex($sf['color']) ?>"
                                                   maxlength="7" style="width:78px;font-family:monospace" tabindex="-1">
                                        </div>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" name="<?= $face ?>_st_align[]" style="width:90px">
                                            <?php foreach ($align_options as $ak => $av): ?>
                                                <option value="<?= $ak ?>" <?= (($sf['align'] ?? 'L') === $ak) ? 'selected' : '' ?>><?= $av ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.5" class="form-control form-control-sm" style="width:70px"
                                               name="<?= $face ?>_st_width[]" value="<?= $sf['width'] ?? 60 ?>"></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- Photo -->
                <div class="card mb-3">
                    <div class="card-header"><strong><?= $this->lang->line('gvv_cartes_membre_layout_photo') ?></strong></div>
                    <div class="card-body">
                        <?php
                        $photo = $face_layout['photo'];
                        $photo_enabled = !empty($photo['enabled']);
                        ?>
                        <div class="row g-3 align-items-end">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="<?= $face ?>_photo_enabled"
                                           name="<?= $face ?>_photo_enabled" value="1" <?= $photo_enabled ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="<?= $face ?>_photo_enabled">
                                        <?= $this->lang->line('gvv_cartes_membre_layout_enabled') ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_layout_photo_x') ?></label>
                                <input type="number" step="0.5" class="form-control form-control-sm" style="width:80px"
                                       name="<?= $face ?>_photo_x" value="<?= $photo['x'] ?? 62 ?>">
                            </div>
                            <div class="col-auto">
                                <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_layout_photo_y') ?></label>
                                <input type="number" step="0.5" class="form-control form-control-sm" style="width:80px"
                                       name="<?= $face ?>_photo_y" value="<?= $photo['y'] ?? 14 ?>">
                            </div>
                            <div class="col-auto">
                                <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_layout_photo_w') ?></label>
                                <input type="number" step="0.5" class="form-control form-control-sm" style="width:80px"
                                       name="<?= $face ?>_photo_w" value="<?= $photo['w'] ?? 20 ?>">
                            </div>
                            <div class="col-auto">
                                <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_layout_photo_h') ?></label>
                                <input type="number" step="0.5" class="form-control form-control-sm" style="width:80px"
                                       name="<?= $face ?>_photo_h" value="<?= $photo['h'] ?? 25 ?>">
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.tab-pane -->
            <?php endforeach; ?>

        </div><!-- /.tab-content -->

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= $this->lang->line('gvv_cartes_membre_layout_save') ?>
            </button>
        </div>

    </form>

</div><!-- /#body -->

<!-- Modal import JSON -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= controller_url('cartes_membre/layout_import') ?>" enctype="multipart/form-data">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <input type="hidden" name="year" value="<?= $year ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $this->lang->line('gvv_cartes_membre_layout_import') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="file" name="layout_json" class="form-control" accept=".json" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $this->lang->line('gvv_button_cancel') ?></button>
                    <button type="submit" class="btn btn-primary"><?= $this->lang->line('gvv_cartes_membre_layout_import') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function makeStaticRow(face) {
    return `<tr>
        <td><input type="text" class="form-control form-control-sm" name="${face}_st_text[]" style="min-width:120px"></td>
        <td><input type="number" step="0.1" class="form-control form-control-sm" style="width:70px" name="${face}_st_x[]" value="3"></td>
        <td><input type="number" step="0.1" class="form-control form-control-sm" style="width:70px" name="${face}_st_y[]" value="3"></td>
        <td>
            <select class="form-select form-select-sm" name="${face}_st_font[]" style="width:110px">
                <option value="helvetica" selected>helvetica</option>
                <option value="times">times</option>
                <option value="courier">courier</option>
            </select>
        </td>
        <td class="text-center"><input type="checkbox" class="form-check-input" name="${face}_st_bold[]" value="1"></td>
        <td><input type="number" min="4" max="24" class="form-control form-control-sm" style="width:60px" name="${face}_st_size[]" value="7"></td>
        <td><div class="d-flex align-items-center gap-1"><input type="color" class="form-control form-control-color flex-shrink-0" name="${face}_st_color[]" value="#000000" style="width:38px;height:32px"><input type="text" class="form-control form-control-sm color-hex-text" value="#000000" maxlength="7" style="width:78px;font-family:monospace" tabindex="-1"></div></td>
        <td>
            <select class="form-select form-select-sm" name="${face}_st_align[]" style="width:90px">
                <option value="L" selected>Gauche</option>
                <option value="C">Centre</option>
                <option value="R">Droite</option>
            </select>
        </td>
        <td><input type="number" step="0.5" class="form-control form-control-sm" style="width:70px" name="${face}_st_width[]" value="60"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-trash"></i></button></td>
    </tr>`;
}

document.addEventListener('click', function(e) {
    if (e.target.closest('.add-static-row')) {
        const btn  = e.target.closest('.add-static-row');
        const tbody = document.querySelector(`#static-table-${btn.dataset.face} tbody`);
        const tmp = document.createElement('tbody');
        tmp.innerHTML = makeStaticRow(btn.dataset.face);
        tbody.appendChild(tmp.firstElementChild);
    }
    if (e.target.closest('.remove-row')) {
        e.target.closest('tr').remove();
    }
});

// Bidirectional sync: color picker ↔ hex text input
document.addEventListener('input', function(e) {
    if (e.target.type === 'color') {
        const text = e.target.closest('div').querySelector('.color-hex-text');
        if (text) text.value = e.target.value;
    }
});

document.addEventListener('input', function(e) {
    if (!e.target.classList.contains('color-hex-text')) return;
    const val = e.target.value.trim();
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
        const picker = e.target.closest('div').querySelector('input[type="color"]');
        if (picker) picker.value = val;
    }
});

// Allow tabbing into the hex text box (override tabindex=-1 if user focuses it)
document.addEventListener('focus', function(e) {
    if (e.target.classList.contains('color-hex-text')) {
        e.target.select();
    }
}, true);
</script>
