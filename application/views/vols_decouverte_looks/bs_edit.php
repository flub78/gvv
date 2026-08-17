<!-- VIEW: application/views/vols_decouverte_looks/bs_edit.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

function rgb_to_hex($color) {
    return sprintf('#%02x%02x%02x', $color[0], $color[1], $color[2]);
}

$var_field_labels = array(
    'numero'        => $this->lang->line('gvv_vd_looks_field_numero'),
    'date_vente'    => $this->lang->line('gvv_vd_looks_field_date_vente'),
    'date_validite' => $this->lang->line('gvv_vd_looks_field_date_validite'),
    'beneficiaire'  => $this->lang->line('gvv_vd_looks_field_beneficiaire'),
    'occasion'      => $this->lang->line('gvv_vd_looks_field_occasion'),
    'de_la_part'    => $this->lang->line('gvv_vd_looks_field_de_la_part'),
    'type_vol'      => $this->lang->line('gvv_vd_looks_field_type_vol'),
    'beneficiaire_email' => $this->lang->line('gvv_vd_looks_field_beneficiaire_email'),
);

$font_options  = array('helvetica', 'times', 'courier');
$align_options = array('L' => 'Gauche', 'C' => 'Centre', 'R' => 'Droite');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-cog text-secondary"></i> <?= $this->lang->line('gvv_vd_looks_edit_title') ?> — <?= htmlspecialchars($look['nom']) ?></h4>
            <a href="<?= controller_url('vols_decouverte_looks') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_vd_looks_back_to_list') ?>
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4 g-3">
        <div class="col-12 d-flex gap-2 flex-wrap">
            <a href="<?= controller_url('vols_decouverte_looks/test_pdf/' . $look['id']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-vial"></i> <?= $this->lang->line('gvv_vd_looks_test_pdf') ?>
            </a>
            <a href="<?= controller_url('vols_decouverte_looks/layout_export/' . $look['id']) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-download"></i> <?= $this->lang->line('gvv_vd_looks_layout_export') ?>
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-upload"></i> <?= $this->lang->line('gvv_vd_looks_layout_import') ?>
            </button>
        </div>
    </div>

    <!-- ================================================================
         Section 1 : Fonds recto / verso
    ================================================================ -->
    <div class="row g-4 mb-5">

        <!-- Fond recto -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><?= $this->lang->line('gvv_vd_looks_fond_recto') ?></strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($look['fond_recto_path'])): ?>
                        <div class="mb-2 text-center">
                            <?php
                            $recto_path = FCPATH . $look['fond_recto_path'];
                            $recto_url  = base_url($look['fond_recto_path']);
                            if (file_exists($recto_path)) {
                                $recto_url .= '?t=' . filemtime($recto_path);
                            }
                            ?>
                            <img src="<?= $recto_url ?>" class="img-thumbnail" style="max-height:120px;"
                                 alt="<?= $this->lang->line('gvv_vd_looks_fond_recto') ?>">
                        </div>
                        <p class="text-success small"><i class="fas fa-check-circle"></i> <?= $this->lang->line('gvv_vd_looks_fond_defined') ?></p>
                    <?php else: ?>
                        <p class="text-muted small"><i class="fas fa-info-circle"></i> <?= $this->lang->line('gvv_vd_looks_fond_absent') ?></p>
                    <?php endif; ?>
                    <form method="post" action="<?= controller_url('vols_decouverte_looks/upload_fond/' . $look['id']) ?>" enctype="multipart/form-data">
                        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                        <input type="hidden" name="face" value="recto">
                        <div class="mb-2">
                            <input type="file" name="fond_recto" class="form-control form-control-sm" accept=".jpg,.jpeg,.png">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-upload"></i> <?= $this->lang->line('gvv_vd_looks_upload') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fond verso -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><?= $this->lang->line('gvv_vd_looks_fond_verso') ?></strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($look['fond_verso_path'])): ?>
                        <div class="mb-2 text-center">
                            <?php
                            $verso_path = FCPATH . $look['fond_verso_path'];
                            $verso_url  = base_url($look['fond_verso_path']);
                            if (file_exists($verso_path)) {
                                $verso_url .= '?t=' . filemtime($verso_path);
                            }
                            ?>
                            <img src="<?= $verso_url ?>" class="img-thumbnail" style="max-height:120px;"
                                 alt="<?= $this->lang->line('gvv_vd_looks_fond_verso') ?>">
                        </div>
                        <p class="text-success small"><i class="fas fa-check-circle"></i> <?= $this->lang->line('gvv_vd_looks_fond_defined') ?></p>
                    <?php else: ?>
                        <p class="text-muted small"><i class="fas fa-info-circle"></i> <?= $this->lang->line('gvv_vd_looks_fond_absent') ?></p>
                    <?php endif; ?>
                    <form method="post" action="<?= controller_url('vols_decouverte_looks/upload_fond/' . $look['id']) ?>" enctype="multipart/form-data">
                        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                        <input type="hidden" name="face" value="verso">
                        <div class="mb-2">
                            <input type="file" name="fond_verso" class="form-control form-control-sm" accept=".jpg,.jpeg,.png">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-upload"></i> <?= $this->lang->line('gvv_vd_looks_upload') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div><!-- .row fonds -->

    <p class="text-muted small mb-4">
        <i class="fas fa-info-circle"></i> <?= $this->lang->line('gvv_vd_looks_fond_info') ?>
    </p>

    <!-- ================================================================
         Section 2 : Mise en page (onglets Recto / Verso)
    ================================================================ -->
    <h5 class="mb-3"><?= $this->lang->line('gvv_vd_looks_layout_title') ?></h5>

    <form method="post" action="<?= controller_url('vols_decouverte_looks/layout_save/' . $look['id']) ?>">
        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

        <div class="mb-3" style="max-width:360px;">
            <label class="form-label"><?= $this->lang->line('gvv_vd_looks_name') ?></label>
            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($look['nom']) ?>" required>
        </div>

        <ul class="nav nav-tabs mb-3" id="faceTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="recto-tab" data-bs-toggle="tab" data-bs-target="#tab-recto" type="button">
                    <?= $this->lang->line('gvv_vd_looks_layout_recto') ?>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="verso-tab" data-bs-toggle="tab" data-bs-target="#tab-verso" type="button">
                    <?= $this->lang->line('gvv_vd_looks_layout_verso') ?>
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
                    <div class="card-header"><strong><?= $this->lang->line('gvv_vd_looks_layout_variable') ?></strong></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_field') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_enabled') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_x') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_y') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_font') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_bold') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_size') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_color') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_align') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_width') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($face_layout['variable_fields'] as $idx => $field): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($var_field_labels[$field['id']] ?? $field['id']) ?>
                                        <input type="hidden" name="<?= $face ?>_var_id[]" value="<?= htmlspecialchars($field['id']) ?>">
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input" name="<?= $face ?>_var_enabled[<?= $idx ?>]"
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
                                        <input type="checkbox" class="form-check-input" name="<?= $face ?>_var_bold[<?= $idx ?>]"
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

                <!-- Champs fixes -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?= $this->lang->line('gvv_vd_looks_layout_static') ?></strong>
                        <button type="button" class="btn btn-sm btn-outline-success add-static-row" data-face="<?= $face ?>">
                            <i class="fas fa-plus"></i> <?= $this->lang->line('gvv_vd_looks_layout_add_static') ?>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 align-middle" id="static-table-<?= $face ?>">
                            <thead class="table-light">
                                <tr>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_text') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_x') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_y') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_font') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_bold') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_size') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_color') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_align') ?></th>
                                    <th><?= $this->lang->line('gvv_vd_looks_layout_width') ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($face_layout['static_fields'] as $idx => $sf): ?>
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
                                        <input type="checkbox" class="form-check-input" name="<?= $face ?>_st_bold[<?= $idx ?>]"
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

                <!-- QR code -->
                <div class="card mb-3">
                    <div class="card-header"><strong><?= $this->lang->line('gvv_vd_looks_layout_qr') ?></strong></div>
                    <div class="card-body">
                        <?php
                        $qr = $face_layout['qr_field'];
                        $qr_enabled = !empty($qr['enabled']);
                        ?>
                        <div class="row g-3 align-items-end">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="<?= $face ?>_qr_enabled"
                                           name="<?= $face ?>_qr_enabled" value="1" <?= $qr_enabled ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="<?= $face ?>_qr_enabled">
                                        <?= $this->lang->line('gvv_vd_looks_layout_enabled') ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <label class="form-label"><?= $this->lang->line('gvv_vd_looks_layout_x') ?></label>
                                <input type="number" step="0.5" class="form-control form-control-sm" style="width:80px"
                                       name="<?= $face ?>_qr_x" value="<?= $qr['x'] ?? 175 ?>">
                            </div>
                            <div class="col-auto">
                                <label class="form-label"><?= $this->lang->line('gvv_vd_looks_layout_y') ?></label>
                                <input type="number" step="0.5" class="form-control form-control-sm" style="width:80px"
                                       name="<?= $face ?>_qr_y" value="<?= $qr['y'] ?? 5 ?>">
                            </div>
                            <div class="col-auto">
                                <label class="form-label"><?= $this->lang->line('gvv_vd_looks_layout_qr_size') ?></label>
                                <input type="number" step="0.5" class="form-control form-control-sm" style="width:80px"
                                       name="<?= $face ?>_qr_size" value="<?= $qr['size'] ?? 30 ?>">
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.tab-pane -->
            <?php endforeach; ?>

        </div><!-- /.tab-content -->

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= $this->lang->line('gvv_vd_looks_layout_save') ?>
            </button>
        </div>

    </form>

</div><!-- /#body -->

<!-- Modal import JSON -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= controller_url('vols_decouverte_looks/layout_import/' . $look['id']) ?>" enctype="multipart/form-data">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?= $this->lang->line('gvv_vd_looks_layout_import') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="file" name="layout_json" class="form-control" accept=".json" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $this->lang->line('gvv_button_cancel') ?></button>
                    <button type="submit" class="btn btn-primary"><?= $this->lang->line('gvv_vd_looks_layout_import') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function makeStaticRow(face) {
    return `<tr>
        <td><input type="text" class="form-control form-control-sm" name="${face}_st_text[]" style="min-width:120px"></td>
        <td><input type="number" step="0.1" class="form-control form-control-sm" style="width:70px" name="${face}_st_x[]" value="5"></td>
        <td><input type="number" step="0.1" class="form-control form-control-sm" style="width:70px" name="${face}_st_y[]" value="5"></td>
        <td>
            <select class="form-select form-select-sm" name="${face}_st_font[]" style="width:110px">
                <option value="helvetica" selected>helvetica</option>
                <option value="times">times</option>
                <option value="courier">courier</option>
            </select>
        </td>
        <td class="text-center"><input type="checkbox" class="form-check-input" name="${face}_st_bold[]" value="1"></td>
        <td><input type="number" min="4" max="24" class="form-control form-control-sm" style="width:60px" name="${face}_st_size[]" value="10"></td>
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

document.addEventListener('focus', function(e) {
    if (e.target.classList.contains('color-hex-text')) {
        e.target.select();
    }
}, true);
</script>
