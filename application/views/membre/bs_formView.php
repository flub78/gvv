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

if ($action != CREATION) {
    $cpt = ($compte) ? $compte : $compte_pilote;

    $bar = array(
        array('label' => $this->lang->line("membre_link_billing"), 'url' => controller_url("compta/journal_compte/$cpt")),
        array('label' => $this->lang->line("membre_link_certificats"), 'url' => controller_url("event/page/$mlogin")),
        array('label' => $this->lang->line("membre_link_avion"), 'url' => controller_url("vols_avion/vols_du_pilote/$mlogin")),
        array('label' => $this->lang->line("membre_link_glider"), 'url' => controller_url("vols_planeur/vols_du_pilote/$mlogin")),
    );
    if ($this->config->item('gestion_tickets')) {
        $bar[] = array('label' => $this->lang->line("membre_link_tickets"), 'url' => controller_url("tickets/page/0/$compte_ticket"));
    }
    echo br() . button_bar4($bar);
}

echo form_open_multipart(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie', 'class' => 'needs-validation'));

// hidden controller url for javascript access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// ========================================================================
// PERSONAL INFORMATION SECTION
// ========================================================================
echo form_fieldset($this->lang->line("membre_fieldset_perso"));
?>

<div class="row">
    <!-- Photo Upload Section (Left Column) -->
    <?php if ($action != CREATION): ?>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body text-center">
                                <h6 class="card-title mb-3"><?php echo $this->lang->line("gvv_membres_field_photo"); ?></h6>
                
                                <?php if (isset($photo) && $photo != ''): ?>
                    <img src="<?php echo base_url('uploads/photos/' . $photo); ?>" id="photo" alt="Photo" class="img-fluid rounded mb-3" style="max-width: 100%;">
                    <button type="button" class="btn btn-danger btn-sm w-100 mb-2" id="delete_photo" onclick="window.location.href='<?php echo controller_url('membre'); ?>/delete_photo/<?php echo $mlogin; ?>'">
                        <i class="fa fa-trash"></i> <?php echo $this->lang->line('delete'); ?>
                    </button>
                <?php else: ?>
                    <div class="text-muted mb-3">
                        <i class="fa fa-user fa-5x"></i>
                    </div>
                <?php endif; ?>
                <div class="mt-2">
                    <?php echo $this->gvvmetadata->input_field("membres", 'photo', $photo); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Personal Information Form (Right Column) -->
    <div class="<?php echo ($action != CREATION) ? 'col-md-9' : 'col-md-12'; ?>">
        <div class="row g-3">

            <!-- Login / Member Selection -->
            <?php if ($action == CREATION): ?>
            <div class="col-md-6">
                <label for="mlogin" class="form-label"><?php echo $this->lang->line("gvv_membres_field_mlogin"); ?></label>
                <?php echo input_field('mlogin', $mlogin, array('type' => 'text', 'class' => 'form-control', 'id' => 'mlogin', 'title' => 'Identifiant de connexion obligatoire, initiales + nom en minuscule')); ?>
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
                                <span class="<?= $badge_class ?>" title="<?= $section['nom'] ?>"<?= $badge_style ?>><?= $section['acronyme'] ?></span>
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
                        <?php echo $this->gvvmetadata->input_field("membres", 'mprenom', $mprenom); ?>
                    </div>
                    <div class="flex-fill" style="min-width: 200px;">
                        <label for="mnom" class="form-label"><?php echo $this->lang->line("gvv_membres_field_mnom"); ?></label>
                        <?php echo $this->gvvmetadata->input_field("membres", 'mnom', $mnom); ?>
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
                        <?php echo $this->gvvmetadata->input_field("membres", 'mdaten', $mdaten); ?>
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

            <!-- Active Status -->
            <div class="col-md-6">
                <label class="form-label d-block"><?php echo $this->lang->line("gvv_membres_field_actif"); ?></label>
                <div class="form-check">
                    <?php echo form_checkbox(array('name' => 'actif', 'class' => 'form-check-input', 'id' => 'actif', 'value' => 1, 'checked' => ($actif == 1))); ?>
                    <label class="form-check-label" for="actif" title="<?php echo $this->lang->line("membre_tooltip_active"); ?>">
                        <?php echo $this->lang->line("gvv_membres_field_actif"); ?>
                    </label>
                </div>
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
    echo '<label for="compte" class="form-label" title="Compte sur lequel sera débité les vols si ce n\'est pas celui du pilote">' . $this->lang->line("gvv_membres_field_compte") . '</label>';
    echo $this->gvvmetadata->input_field("membres", 'compte', $compte);
    echo '</div>';
    echo '</div>';
    echo form_fieldset_close();
} else {
    echo form_hidden('compte', $compte);
}

// ========================================================================
// RESPONSIBILITY SECTION
// ========================================================================
echo form_fieldset($this->lang->line("membre_fieldset_responsibility"));
$levels = $this->lang->line("membres_niveaux");
?>

<div class="row g-3">
    <!-- Row 1 -->
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', PRESIDENT, $mniveau, 'class="form-check-input" id="president"'); ?>
            <label class="form-check-label" for="president"><?php echo $levels[PRESIDENT]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', VICE_PRESIDENT, $mniveau, 'class="form-check-input" id="vice_president"'); ?>
            <label class="form-check-label" for="vice_president"><?php echo $levels[VICE_PRESIDENT]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', CA, $mniveau, 'class="form-check-input" id="ca"'); ?>
            <label class="form-check-label" for="ca"><?php echo $levels[CA]; ?></label>
        </div>
    </div>

    <!-- Row 2 -->
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', TRESORIER, $mniveau, 'class="form-check-input" id="tresorier"'); ?>
            <label class="form-check-label" for="tresorier"><?php echo $levels[TRESORIER]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', SECRETAIRE, $mniveau, 'class="form-check-input" id="secretaire"'); ?>
            <label class="form-check-label" for="secretaire"><?php echo $levels[SECRETAIRE]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', SECRETAIRE_ADJ, $mniveau, 'class="form-check-input" id="secretaire_adj"'); ?>
            <label class="form-check-label" for="secretaire_adj"><?php echo $levels[SECRETAIRE_ADJ]; ?></label>
        </div>
    </div>

    <!-- Row 3 -->
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', CHEF_PILOTE, $mniveau, 'class="form-check-input" id="chef_pilote"'); ?>
            <label class="form-check-label" for="chef_pilote"><?php echo $levels[CHEF_PILOTE]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', MECANO, $mniveau, 'class="form-check-input" id="mecano"'); ?>
            <label class="form-check-label" for="mecano"><?php echo $levels[MECANO]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', PLIEUR, $mniveau, 'class="form-check-input" id="plieur"'); ?>
            <label class="form-check-label" for="plieur"><?php echo $levels[PLIEUR]; ?></label>
        </div>
    </div>

    <!-- Row 4 -->
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', INTERNET, $mniveau, 'class="form-check-input" id="internet"'); ?>
            <label class="form-check-label" for="internet"><?php echo $levels[INTERNET]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', TREUILLARD, $mniveau, 'class="form-check-input" id="treuillard"'); ?>
            <label class="form-check-label" for="treuillard"><?php echo $levels[TREUILLARD]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', CHEF_DE_PISTE, $mniveau, 'class="form-check-input" id="chef_de_piste"'); ?>
            <label class="form-check-label" for="chef_de_piste"><?php echo $levels[CHEF_DE_PISTE]; ?></label>
        </div>
    </div>
</div>

<?php
echo form_fieldset_close();

// ========================================================================
// AIRPLANE SECTION
// ========================================================================
echo heading("membre_title_airplane_training", 4);

?>

<div class="row g-3">
    <!-- Qualifications -->
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', PILOTE_AVION, $mniveau, 'class="form-check-input" id="pilote_avion"'); ?>
            <label class="form-check-label" for="pilote_avion"><?php echo $levels[PILOTE_AVION]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', VI_AVION, $mniveau, 'class="form-check-input" id="vi_avion"'); ?>
            <label class="form-check-label" for="vi_avion"><?php echo $levels[VI_AVION]; ?></label>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', REMORQUEUR, $mniveau, 'class="form-check-input" id="remorqueur"'); ?>
            <label class="form-check-label" for="remorqueur"><?php echo $levels[REMORQUEUR]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', FI_AVION, $mniveau, 'class="form-check-input" id="fi_avion"'); ?>
            <label class="form-check-label" for="fi_avion"><?php echo $levels[FI_AVION]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', FE_AVION, $mniveau, 'class="form-check-input" id="fe_avion"'); ?>
            <label class="form-check-label" for="fe_avion"><?php echo $levels[FE_AVION]; ?></label>
        </div>
    </div>

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

    echo '<div class="mt-4">';
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
    <!-- Qualifications -->
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', PILOTE_PLANEUR, $mniveau, 'class="form-check-input" id="pilote_planeur"'); ?>
            <label class="form-check-label" for="pilote_planeur"><?php echo $levels[PILOTE_PLANEUR]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', VI_PLANEUR, $mniveau, 'class="form-check-input" id="vi_planeur"'); ?>
            <label class="form-check-label" for="vi_planeur"><?php echo $levels[VI_PLANEUR]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', ITP, $mniveau, 'class="form-check-input" id="itp"'); ?>
            <label class="form-check-label" for="itp"><?php echo $levels[ITP]; ?></label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <?php echo checkbox_array('mniveau', IVV, $mniveau, 'class="form-check-input" id="ivv"'); ?>
            <label class="form-check-label" for="ivv"><?php echo $levels[IVV]; ?></label>
        </div>
    </div>

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

    echo '<div class="mt-4">';
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
$bar = array(
    array('label' => $this->lang->line("gvv_button_validate"), 'type' => "submit", 'id' => 'validate', 'name' => "button"),
    array('label' => $this->lang->line("gvv_button_print"), 'url' => "$controller/adhesion/$mlogin", 'role' => 'ca'),
    array('label' => $this->lang->line("membre_button_subscription"), 'url' => "$controller/adhesion/$mlogin/1", 'role' => 'ca'),
);
echo '<div class="mt-4 mb-4">';
echo button_bar4($bar);
echo '</div>';

echo form_close();

echo '</div>';
?>
