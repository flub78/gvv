<!-- VIEW: application/views/membre/bs_formView.php -->
<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Formulaire de saisie d'un membre
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('membre');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo heading("membre_title", 3);

echo validation_errors();

echo form_open_multipart(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie', 'class' => 'needs-validation'));

// hidden controller url for javascript access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

if (!$has_modification_rights) echo '<fieldset disabled>';

// ========================================================================
// PERSONAL INFORMATION SECTION
// ========================================================================
echo form_fieldset($this->lang->line("membre_fieldset_perso"));
?>

<div class="row">
    <!-- Photo Upload Section (Left Column) -->
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="card-title mb-3"><?php echo $this->lang->line("gvv_membres_field_photo"); ?></h6>

                <?php if ($action != CREATION): ?>
                    <!-- Le bouton de suppression seulement si une photo existe -->
                    <?php if (isset($photo) && $photo != ''): ?>
                        <button type="button" class="btn btn-danger btn-sm w-100 mb-2" id="delete_photo" onclick="window.location.href='<?php echo controller_url('membre'); ?>/delete_photo/<?php echo $mlogin; ?>'">
                            <i class="fa fa-trash"></i> <?php echo $this->lang->line('delete'); ?>
                        </button>
                    <?php endif; ?>

                    <!-- L'input_field gère l'affichage de l'image ET le champ de téléchargement -->
                    <div class="mt-2" id="photo-upload-container">
                        <?php echo $this->gvvmetadata->input_field("membres", 'photo', $photo); ?>
                    </div>
                <?php endif; ?>

                <div id="photo-drop-zone" class="drop-zone mt-2">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-1"><?= $this->lang->line('gvv_drop_file_here') ?></p>
                    <p class="text-muted small"><?= $this->lang->line('gvv_or') ?></p>
                    <label for="fileInput" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-folder-open"></i> <?= $this->lang->line('gvv_choose_file') ?>
                    </label>
                    <?php if ($action == CREATION): ?>
                        <input type="file" id="fileInput" name="userfile" class="d-none" accept=".jpg,.jpeg,.png,.gif">
                    <?php endif; ?>
                    <p class="mt-2 small text-muted" id="photo-drop-filename"><?= $this->lang->line('gvv_no_file_selected') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Information Form (Right Column) -->
    <div class="col-md-9">
        <div class="row g-3">

            <!-- Login / Member Selection -->
            <?php if ($action == CREATION): ?>
                <div class="col-md-6">
                    <label for="mlogin" class="form-label"><?php echo $this->lang->line("gvv_membres_field_mlogin"); ?></label>
                    <?php echo input_field('mlogin', $mlogin, array('type' => 'text', 'class' => 'form-control', 'id' => 'mlogin', 'title' => 'Identifiant de connexion obligatoire. Par convention, ce sont les premières lettres du prénom suivies des lettres du nom, le tout en minuscule.')); ?>
                    <div class="form-text">Par convention, ce sont les premières lettres du prénom suivies des lettres du nom, le tout en minuscule.</div>
                </div>
            <?php else: ?>
                <?php if (isset($pilote_selector)): ?>
                    <div class="col-md-6">
                        <label for="selector" class="form-label"><?php echo $this->lang->line("gvv_membres_field_mlogin"); ?></label>
                        <div class="d-flex align-items-center">
                            <?php echo dropdown_field('mlogin', $mlogin, $pilote_selector, "id='selector' class='form-select big_select' onchange='new_selection();'"); ?>
                            <?php if (isset($has_sections) && $has_sections): ?>
                                <div class="d-flex justify-content-start ms-2">
                                    <?php foreach ($member_sections as $section): ?>
                                        <?php
                                        $badge_class = 'badge rounded-pill me-1';
                                        $badge_style = '';
                                        if (!empty($section['couleur'])) {
                                            $badge_style = ' style="background-color: ' . $section['couleur'] . '; color: black; border: 1px solid black;"';
                                        } else {
                                            $badge_class .= ' bg-primary';
                                        }
                                        ?>
                                        <span class="<?= $badge_class ?>" title="<?= $section['nom'] ?>" <?= $badge_style ?>><?= $section['acronyme'] ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php echo form_hidden('mlogin', $mlogin); ?>
                <?php endif; ?>
            <?php endif; ?>

            <!-- First Name and Last Name - Flexbox Container -->
            <div class="col-md-12">
                <div class="d-flex flex-wrap gap-3">
                    <div class="flex-fill" style="min-width: 200px;">
                        <label for="mprenom" class="form-label"><?php echo $this->lang->line("gvv_membres_field_mprenom"); ?></label>
                        <?php if ($has_admin_rights || $action == CREATION): ?>
                            <?php echo $this->gvvmetadata->input_field("membres", 'mprenom', $mprenom); ?>
                        <?php else: ?>
                            <input type="hidden" name="mprenom" value="<?php echo htmlspecialchars($mprenom); ?>">
                            <p class="form-control-plaintext bg-white border rounded px-2"><?php echo htmlspecialchars($mprenom); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex-fill" style="min-width: 200px;">
                        <label for="mnom" class="form-label"><?php echo $this->lang->line("gvv_membres_field_mnom"); ?></label>
                        <?php if ($has_admin_rights || $action == CREATION): ?>
                            <?php echo $this->gvvmetadata->input_field("membres", 'mnom', $mnom); ?>
                        <?php else: ?>
                            <input type="hidden" name="mnom" value="<?php echo htmlspecialchars($mnom); ?>">
                            <p class="form-control-plaintext bg-white border rounded px-2"><?php echo htmlspecialchars($mnom); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Trigramme -->
            <div class="col-md-6">
                <label for="trigramme" class="form-label"><?php echo $this->lang->line("gvv_membres_field_trigramme"); ?></label>
                <?php echo $this->gvvmetadata->input_field("membres", 'trigramme', $trigramme); ?>
            </div>

            <!-- Email and Parent Email - Flexbox Container -->
            <div class="col-md-12">
                <div class="d-flex flex-wrap gap-3">
                    <div class="flex-fill" style="min-width: 250px;">
                        <label for="memail" class="form-label"><?php echo $this->lang->line("gvv_membres_field_memail"); ?></label>
                        <?php echo $this->gvvmetadata->input_field("membres", 'memail', $memail); ?>
                    </div>
                    <div class="flex-fill" style="min-width: 250px;">
                        <label for="memailparent" class="form-label"><?php echo $this->lang->line("gvv_membres_field_memailparent"); ?></label>
                        <?php echo $this->gvvmetadata->input_field("membres", 'memailparent', $memailparent); ?>
                    </div>
                </div>
            </div>

            <!-- Category -->
            <?php $les_categories = $this->gvvmetadata->input_field("membres", 'categorie', $categorie); ?>
            <?php if ($les_categories): ?>
                <div class="col-md-12">
                    <label for="categorie" class="form-label"><?php echo $this->lang->line("gvv_membres_field_categorie"); ?></label>
                    <?php echo $les_categories; ?>
                </div>
            <?php endif; ?>

            <!-- Address -->
            <div class="col-md-12">
                <label for="madresse" class="form-label"><?php echo $this->lang->line("gvv_membres_field_madresse"); ?></label>
                <?php echo $this->gvvmetadata->input_field("membres", 'madresse', $madresse); ?>
            </div>

            <!-- Postal Code -->
            <div class="col-md-3">
                <label for="cp" class="form-label"><?php echo $this->lang->line("gvv_membres_field_cp"); ?></label>
                <?php echo $this->gvvmetadata->input_field("membres", 'cp', $cp); ?>
            </div>

            <!-- City -->
            <div class="col-md-5">
                <label for="ville" class="form-label"><?php echo $this->lang->line("gvv_membres_field_ville"); ?></label>
                <?php echo $this->gvvmetadata->input_field("membres", 'ville', $ville); ?>
            </div>

            <!-- Country -->
            <div class="col-md-4">
                <label for="pays" class="form-label"><?php echo $this->lang->line("gvv_membres_field_country"); ?></label>
                <?php echo $this->gvvmetadata->input_field("membres", 'pays', $pays); ?>
            </div>

            <!-- Phone Fixed -->
            <div class="col-md-6">
                <label for="mtelf" class="form-label"><?php echo $this->lang->line("gvv_membres_field_mtelf"); ?></label>
                <?php echo $this->gvvmetadata->input_field("membres", 'mtelf', $mtelf); ?>
            </div>

            <!-- Phone Mobile -->
            <div class="col-md-6">
                <label for="mtelm" class="form-label"><?php echo $this->lang->line("gvv_membres_field_mtelm"); ?></label>
                <?php echo $this->gvvmetadata->input_field("membres", 'mtelm', $mtelm); ?>
            </div>

            <!-- Profession -->
            <div class="col-md-6">
                <label for="profession" class="form-label"><?php echo $this->lang->line("gvv_membres_field_profession"); ?></label>
                <?php echo $this->gvvmetadata->input_field("membres", 'profession', $profession); ?>
            </div>

            <!-- Birth Date and Place of Birth - Flexbox Container -->
            <div class="col-md-12">
                <div class="d-flex flex-wrap gap-3">
                    <div class="flex-fill" style="min-width: 150px;">
                        <label for="mdaten" class="form-label"><?php echo $this->lang->line("gvv_membres_field_mdaten"); ?></label>
                        <?php if ($has_birthdate_rights || $action == CREATION): ?>
                            <?php echo $this->gvvmetadata->input_field("membres", 'mdaten', $mdaten); ?>
                        <?php else: ?>
                            <p class="form-control-plaintext bg-white border rounded px-2"><?php echo htmlspecialchars(date_db2ht($mdaten)); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex-fill" style="min-width: 150px;">
                        <label for="place_of_birth" class="form-label"><?php echo $this->lang->line("gvv_membres_field_place_of_birth"); ?></label>
                        <?php echo $this->gvvmetadata->input_field("membres", 'place_of_birth', $place_of_birth); ?>
                    </div>
                </div>
            </div>

            <!-- Gender -->
            <div class="col-md-3">
                <label class="form-label d-block"><?php echo $this->lang->line("gvv_membres_field_gender"); ?></label>
                <?php
                $attrs = $this->lang->line("gvv_gender");
                echo radio_field('msexe', $msexe, $attrs);
                ?>
            </div>

            <!-- Age Category -->
            <div class="col-md-3">
                <label class="form-label d-block"><?php echo $this->lang->line("gvv_membres_field_categorie"); ?></label>
                <?php
                $attrs25 = $this->lang->line("gvv_age_enum");
                echo radio_field('m25ans', $m25ans, $attrs25);
                ?>
            </div>

            <!-- Active Status & Exemption solde -->
            <div class="col-md-6">
                <?php echo form_hidden('actif', $actif); ?>
                <?php if ($has_modification_rights): ?>
                <div class="form-check mt-1">
                    <?php echo form_checkbox(array('name' => 'exemption_solde', 'class' => 'form-check-input', 'id' => 'exemption_solde', 'value' => 1, 'checked' => (!empty($exemption_solde)))); ?>
                    <label class="form-check-label" for="exemption_solde" title="Ce pilote peut réserver un appareil même si son solde est insuffisant">
                        <?php echo $this->lang->line("gvv_membres_field_exemption_solde"); ?>
                    </label>
                </div>
                <?php else: ?>
                    <?php echo form_hidden('exemption_solde', !empty($exemption_solde) ? 1 : 0); ?>
                <?php endif; ?>
            </div>

            <!-- Federation License -->
            <div class="col-md-6">
                <label for="licfed" class="form-label"><?php echo $this->lang->line("gvv_membres_field_numlicencefed"); ?></label>
                <?php echo $this->gvvmetadata->input_field("membres", 'licfed', $licfed); ?>
            </div>

            <!-- Inscription Date and Validation Date - Flexbox Container -->
            <div class="col-md-12">
                <div class="d-flex flex-wrap gap-3">
                    <div class="flex-fill" style="min-width: 150px;">
                        <label for="inscription_date" class="form-label"><?php echo $this->lang->line("gvv_membres_field_inscription_date"); ?></label>
                        <?php echo $this->gvvmetadata->input_field("membres", 'inscription_date', $inscription_date); ?>
                    </div>
                    <div class="flex-fill" style="min-width: 150px;">
                        <label for="validation_date" class="form-label"><?php echo $this->lang->line("gvv_membres_field_validation_date"); ?></label>
                        <?php echo $this->gvvmetadata->input_field("membres", 'validation_date', $validation_date); ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
// Medical Certificates Table
$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('event_type', 'date', 'comment'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'controller' => "event",
    'param' => $mlogin
);
if ($action != CREATION) {
    echo '<div class="mt-4">';
    echo heading("membre_title_medical", 4);
    echo $this->gvvmetadata->table("vue_exp_autre", $attrs, "");
    echo '</div>';
}

echo form_fieldset_close();

// ========================================================================
// BILLING SECTION
// ========================================================================
if ($has_modification_rights) {
    echo form_fieldset($this->lang->line("membre_fieldset_billing"));
    echo '<div class="row g-3">';
    echo '<div class="col-md-6">';
    echo '<label for="membre_payeur" class="form-label" title="Membre dont le compte sera débité pour les vols de ce pilote (laisser vide pour utiliser le compte du pilote)">' . $this->lang->line("gvv_membres_field_membre_payeur") . '</label>';
    echo $this->gvvmetadata->input_field("membres", 'membre_payeur', $membre_payeur);
    echo '</div>';
    echo '</div>';
    echo form_fieldset_close();
} else {
    echo form_hidden('membre_payeur', $membre_payeur);

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}
}


// ========================================================================
// AIRPLANE SECTION
// ========================================================================
echo heading("membre_title_airplane_training", 4);

?>

<div class="row g-3">
    <!-- Instructeur responsable -->
    <div class="col-md-6">
        <label for="inst_airplane" class="form-label"><?php echo $this->lang->line("gvv_membres_field_inst_airplane"); ?></label>
        <?php echo $this->gvvmetadata->input_field("membres", 'inst_airplane', $inst_airplane); ?>
    </div>
</div>

<?php
// Formation Avion section
if ($action != CREATION) {
    echo form_fieldset($this->lang->line("membre_fieldset_airplane"));

    if ($has_modification_rights) {
        echo '<div class="mb-2">';
        echo anchor(controller_url('event') . '/create/' . $mlogin . '/3', $this->lang->line('membre_add_experience'), 'class="btn btn-sm btn-secondary"');
        echo '</div>';
    }

    echo '<div class="mt-2">';
    $attrs = array(
        'controller' => "event",
        'actions' => array('edit', 'delete'),
        'fields' => array('event_type', 'date', 'glider_flight', 'plane_flight', 'comment'),
        'mode' => ($has_modification_rights) ? "rw" : "ro",
        'param' => $mlogin
    );
    echo $this->gvvmetadata->table("vue_exp_avion", $attrs, "");
    echo '</div>';
}

echo form_fieldset_close();

// ========================================================================
// GLIDER SECTION
// ========================================================================
echo heading("membre_title_glider_training", 4);

?>

<div class="row g-3">
    <!-- Instructeur responsable -->
    <div class="col-md-6">
        <label for="inst_glider" class="form-label"><?php echo $this->lang->line("gvv_membres_field_inst_glider"); ?></label>
        <?php echo $this->gvvmetadata->input_field("membres", 'inst_glider', $inst_glider); ?>
    </div>
</div>

<?php
// Formation Vol à Voile sections
if ($action != CREATION) {
    echo form_fieldset($this->lang->line("membre_fieldset_glider"));

    if ($has_modification_rights) {
        echo '<div class="mb-2">';
        echo anchor(controller_url('event') . '/create/' . $mlogin . '/1', $this->lang->line('membre_add_experience'), 'class="btn btn-sm btn-secondary"');
        echo '</div>';
    }

    echo '<div class="mt-2">';
    $attrs = array(
        'controller' => "event",
        'actions' => array('edit', 'delete'),
        'fields' => array('event_type', 'date', 'glider_flight', 'plane_flight', 'comment'),
        'mode' => ($has_modification_rights) ? "rw" : "ro",
        'param' => $mlogin
    );
    echo $this->gvvmetadata->table("vue_exp_vv", $attrs, "");
    echo '</div>';

    echo '<div class="mt-4">';
    echo heading("membre_title_FAI", 4);
    echo $this->gvvmetadata->table("vue_exp_fai", $attrs, "");
    echo '</div>';
}

echo form_fieldset_close();

// ========================================================================
// ULM SECTION
// ========================================================================
if ($action != CREATION) {
    echo form_fieldset($this->lang->line("membre_fieldset_ulm"));

    if ($has_modification_rights) {
        echo '<div class="mb-2">';
        echo anchor(controller_url('event') . '/create/' . $mlogin . '/2', $this->lang->line('membre_add_experience'), 'class="btn btn-sm btn-secondary"');
        echo '</div>';
    }

    echo '<div class="mt-2">';
    $attrs = array(
        'controller' => "event",
        'actions' => array('edit', 'delete'),
        'fields' => array('event_type', 'date', 'comment'),
        'mode' => ($has_modification_rights) ? "rw" : "ro",
        'param' => $mlogin
    );
    echo $this->gvvmetadata->table("vue_exp_ulm", $attrs, "");
    echo '</div>';

    echo form_fieldset_close();
}

// ========================================================================
// COMMENTS SECTION
// ========================================================================
echo form_fieldset($this->lang->line("membre_fieldset_information"));
?>

<div class="row g-3">
    <div class="col-md-12">
        <label for="comment" class="form-label"><?php echo $this->lang->line("gvv_membres_field_comment"); ?></label>
        <div>
            <?php echo form_textarea(array('name' => 'comment', 'id' => 'comment', 'class' => 'form-control', 'rows' => 4, 'value' => set_value('comment', $comment))); ?>
        </div>
    </div>
</div>

<?php
echo form_fieldset_close();

// ========================================================================
// ACTION BUTTONS
// ========================================================================
$bar = array();
if ($action != VISUALISATION) {
    $bar[] = array('label' => $this->lang->line("gvv_button_validate"), 'type' => "submit", 'id' => 'validate', 'name' => "button");
}
$bar[] = array('label' => $this->lang->line("gvv_button_print"), 'url' => "$controller/adhesion/$mlogin", 'role' => 'ca');
$bar[] = array('label' => $this->lang->line("membre_button_subscription"), 'url' => "$controller/adhesion/$mlogin/1", 'role' => 'ca');
echo '<div class="mt-4 mb-4">';
echo button_bar4($bar);
echo '</div>';

if (!$has_modification_rights) echo '</fieldset>';

// Reminder preferences are managed by the member themselves in "Mes réservations".
// Hidden fields ensure the values are preserved through admin form submissions.
echo form_hidden('reminder_channel', isset($reminder_channel) ? $reminder_channel : 'email');
echo form_hidden('reminder_period_hours', isset($reminder_period_hours) ? $reminder_period_hours : 24);

echo form_close();

echo '</div>';
?>

<style>
.drop-zone {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background-color 0.2s;
    background: #fafafa;
}
.drop-zone.drag-over {
    border-color: #0d6efd;
    background-color: #e8f0fe;
}
.drop-zone.has-file {
    border-color: #198754;
    background-color: #f0fff4;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var fileInput = document.getElementById('fileInput');
    if (!fileInput) return;

    var container = document.getElementById('photo-upload-container');
    if (container) {
        // Mode modification : masquer les contrôles rendus par les métadonnées
        container.querySelectorAll('label[for="fileInput"], input[name="display_userfile"]').forEach(function (el) {
            el.style.display = 'none';
        });
        fileInput.style.display = 'none';
    }

    var zone = document.getElementById('photo-drop-zone');
    var filenameLabel = document.getElementById('photo-drop-filename');

    function updatePreview(file) {
        filenameLabel.textContent = file.name;
        zone.classList.add('has-file');

        // Mise à jour de la prévisualisation si une image est déjà affichée (modification)
        var img = container ? container.querySelector('img') : null;
        if (img) {
            var reader = new FileReader();
            reader.onload = function (e) { img.src = e.target.result; };
            reader.readAsDataURL(file);
        } else {
            // Mode création : afficher une prévisualisation dans la drop zone
            var preview = zone.querySelector('img.photo-preview');
            if (!preview) {
                preview = document.createElement('img');
                preview.className = 'photo-preview img-thumbnail mb-2';
                preview.style.cssText = 'max-width:100%; max-height:150px; width:auto; height:auto;';
                zone.insertBefore(preview, zone.firstChild);
            }
            var reader = new FileReader();
            reader.onload = function (e) { preview.src = e.target.result; };
            reader.readAsDataURL(file);
        }
    }

    zone.addEventListener('click', function (e) {
        if (e.target.tagName !== 'LABEL' && e.target.tagName !== 'INPUT') {
            fileInput.click();
        }
    });

    fileInput.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            updatePreview(this.files[0]);
        }
    });

    zone.addEventListener('dragover', function (e) {
        e.preventDefault();
        zone.classList.add('drag-over');
    });

    zone.addEventListener('dragleave', function () {
        zone.classList.remove('drag-over');
    });

    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('drag-over');
        var dt = e.dataTransfer;
        if (dt.files.length > 0) {
            fileInput.files = dt.files;
            updatePreview(dt.files[0]);
        }
    });
});
</script>