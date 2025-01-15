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

echo form_open_multipart(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// On affiche tous les champs dans un tableau. C'est plus simple de remplir d'abbord le tableau
// et de l'afficher ensuite, surtout pour modifier l'affichage

$tabs = nbs(10);

echo form_fieldset($this->lang->line("membre_fieldset_perso"));
?>

<?php if ($action != CREATION): ?>
    <div id="picture_id">
        <?php echo $this->gvvmetadata->input_field("membres", 'photo', $photo); ?>

        <?php if (isset($photo) && $photo != ''): ?>
            <img src="<?php echo base_url('uploads/' . $photo); ?>" id="photo" alt="Photo" class="img-responsive" style="max-width: 200px;">

            <button type="button" class="btn btn-danger btn-sm" id="delete_photo" onclick="window.location.href='<?php echo controller_url('membre'); ?>/delete_photo/<?php echo $mlogin; ?>'">
                <i class="fa fa-trash"></i> <?php echo $this->lang->line('delete'); ?>
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php

echo '<div id="info_perso">' . "\n";

$table = array();
$row = 0;
if ($action == CREATION) {
    $table[$row][] = $this->lang->line("gvv_membres_field_mlogin");
    $table[$row][] = input_field('mlogin', $mlogin, array('type'  => 'text', 'size' => '25', 'title' => 'Identifiant de connexion obligatoire, initiales + nom en minuscule'));
} else {
    if (isset($pilote_selector)) {
        $table[$row][] = $tabs;
        $table[$row][] = dropdown_field(
            'mlogin',
            $mlogin,
            $pilote_selector,
            "id='selector' class='big_select' onchange='new_selection();'"
        );
    } else {
        echo form_hidden('mlogin', $mlogin);
    }
}
$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_mprenom");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'mprenom', $mprenom);

$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_mnom");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'mnom', $mnom);

$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_trigramme");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'trigramme', $trigramme);

$les_categories = $this->gvvmetadata->input_field("membres", 'categorie', $categorie);
if ($les_categories) {
    $row++;
    $table[$row][] = $this->lang->line("gvv_membres_field_categorie");
    $table[$row][] = $les_categories;
}

$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_memail");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'memail', $memail);

$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_memailparent");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'memailparent', $memailparent);

$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_madresse");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'madresse', $madresse);

$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_cp");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'cp', $cp)
    . nbs(2) . $this->lang->line("gvv_membres_field_ville") . nbs() . $this->gvvmetadata->input_field("membres", 'ville', $ville);
$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_country");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'pays', $pays);

$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_mtelf");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'mtelf', $mtelf) . $tabs
    . $this->lang->line("gvv_membres_field_mtelm") . nbs()
    . $this->gvvmetadata->input_field("membres", 'mtelm', $mtelm);

$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_profession");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'profession', $profession);

$row++;
$table[$row][] = $this->lang->line("gvv_membres_field_mdaten");

$attrs = $this->lang->line("gvv_gender");

$attrs25 = $this->lang->line("gvv_age_enum");
$table[$row][] = $this->gvvmetadata->input_field("membres", 'mdaten', $mdaten)
    . $tabs . $this->lang->line("gvv_membres_field_gender") . ": " . radio_field('msexe', $msexe, $attrs)
    . $tabs . $this->lang->line("gvv_membres_field_categorie") . ": " . radio_field('m25ans', $m25ans, $attrs25);

$row++;
$table[$row][] = "";
$table[$row][] = $tabs .  $this->lang->line("gvv_membres_field_actif") .  ": "
    . "<span title=\"" . $this->lang->line("membre_tooltip_active") . "\">"
    . form_checkbox(array('name' => 'actif', 'value' => 1, 'checked' => ($actif == 1))) . "</span>"
    . $tabs . $this->lang->line("gvv_membres_field_numlicencefed") .  ": "
    . $this->gvvmetadata->input_field("membres", 'licfed', $licfed)
    . "";

display_form_table($table);
echo "</div>\n";


$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('event_type', 'date', 'comment'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'controller' => "event",
    'param' => $mlogin
);
if ($action != CREATION) {
    echo heading("membre_title_medical", 4);
    echo $this->gvvmetadata->table("vue_exp_autre", $attrs, "");
}


echo form_fieldset_close();

// --------------------------------------------------------------------------
if ($has_modification_rights) {
    echo form_fieldset($this->lang->line("membre_fieldset_billing"));
    $table = array();
    $row = 0;
    $table[$row][] = $this->lang->line("gvv_membres_field_compte");
    $table[$row][] = "<span title=\"Compte sur lequel sera débité les vols si ce n'est pas celui du pilote\">"
        . $this->gvvmetadata->input_field("membres", 'compte', $compte)  . "</span>";

    display_form_table($table);
    echo form_fieldset_close();
} else {
    echo form_hidden('compte', $compte);
}

// --------------------------------------------------------------------------
echo form_fieldset($this->lang->line("membre_fieldset_responsibility"));
$table = array();

$levels = $this->lang->line("membres_niveaux");

$table[$row][] = $levels[PRESIDENT];
$table[$row][] = checkbox_array('mniveau', PRESIDENT, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[VICE_PRESIDENT];
$table[$row][] = checkbox_array('mniveau', VICE_PRESIDENT, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[CA];
$table[$row][] = checkbox_array('mniveau', CA, $mniveau);

$row++;
$table[$row][] = $levels[TRESORIER];
$table[$row][] = checkbox_array('mniveau', TRESORIER, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[SECRETAIRE];
$table[$row][] = checkbox_array('mniveau', SECRETAIRE, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[SECRETAIRE_ADJ];
$table[$row][] = checkbox_array('mniveau', SECRETAIRE_ADJ, $mniveau);

$row++;
$table[$row][] = $levels[CHEF_PILOTE];
$table[$row][] = checkbox_array('mniveau', CHEF_PILOTE, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[MECANO];
$table[$row][] = checkbox_array('mniveau', MECANO, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[PLIEUR];
$table[$row][] = checkbox_array('mniveau', PLIEUR, $mniveau);

$row++;
$table[$row][] = $levels[INTERNET];
$table[$row][] = checkbox_array('mniveau', INTERNET, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[TREUILLARD];
$table[$row][] = checkbox_array('mniveau', TREUILLARD, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[CHEF_DE_PISTE];
$table[$row][] = checkbox_array('mniveau', CHEF_DE_PISTE, $mniveau);

display_form_table($table);

echo form_fieldset_close();

// --------------------------------------------------------------------------
echo form_fieldset($this->lang->line("membre_fieldset_airplaine"));
$table = array();
$table[$row][] = $levels[PILOTE_AVION];
$table[$row][] = checkbox_array('mniveau', PILOTE_AVION, $mniveau);
// $row++;
// $table [$row][] = $this->lang->line("gvv_membres_field_mbranum");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'mbranum', $mbranum);

// $table [$row][] = $tabs;
// $table [$row][] = $this->lang->line("gvv_membres_field_mbradat");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'mbradat', $mbradat);
// $table [$row][] = $tabs;
// $table [$row][] = $this->lang->line("gvv_membres_field_mbraval");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'mbraval', $mbraval);

$row++;
$table[$row][] = $levels[VI_AVION];
$table[$row][] = checkbox_array('mniveau', VI_AVION, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[REMORQUEUR];
$table[$row][] = checkbox_array('mniveau', REMORQUEUR, $mniveau);
// $table [$row][] = $tabs;
// $table [$row][] = $this->lang->line("gvv_membres_field_numinstavion");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'numinstavion', $numinstavion);

$row++;
$table[$row][] = $levels[FI_AVION];
$table[$row][] = checkbox_array('mniveau', FI_AVION, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[FE_AVION];
$table[$row][] = checkbox_array('mniveau', FE_AVION, $mniveau);
// $table [$row][] = $tabs;
// $table [$row][] = $this->lang->line("gvv_membres_field_dateinstavion");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'dateinstavion', $dateinstavion);

display_form_table($table);

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array(
        'event_type',
        'date',
        'glider_flight',
        'plane_flight',
        'comment'
    ),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'controller' => "event",
    'param' => $mlogin
);

echo heading("membre_title_airplane_training", 4);
echo $this->lang->line("gvv_membres_field_inst_airplane") . nbs() .
    $this->gvvmetadata->input_field("membres", 'inst_airplane', $inst_airplane) . br(2);
if ($action != CREATION) {
    echo $this->gvvmetadata->table("vue_exp_avion", $attrs, "");
}
echo form_fieldset_close();

// --------------------------------------------------------------------------
echo form_fieldset($this->lang->line("membre_fieldset_glider"));
$table = array();
$table[$row][] = $levels[PILOTE_PLANEUR];
$table[$row][] = checkbox_array('mniveau', PILOTE_PLANEUR, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[VI_PLANEUR];
$table[$row][] = checkbox_array('mniveau', VI_PLANEUR, $mniveau);
// $table [$row][] = $tabs;
// $table [$row][] = $this->lang->line("gvv_membres_field_mbrpval");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'mbrpval', $mbrpval);

$row++;
// $table [$row][] = $this->lang->line("gvv_membres_field_mbrpnum");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'mbrpnum', $mbrpnum);
// $table [$row][] = $tabs;
// $table [$row][] = $this->lang->line("gvv_membres_field_mbrpdat");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'mbrpdat', $mbrpdat);
// $table [$row][] = $tabs;
// $table [$row][] = $this->lang->line("gvv_membres_field_numivv");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'numivv', $numivv);

// $row++;
// $table [$row][] = $this->lang->line("gvv_membres_field_numlicencefed");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'numlicencefed', $numlicencefed);
// $table [$row][] = $this->lang->line("gvv_membres_field_vallicencefed");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'vallicencefed', $vallicencefed);

$row++;
$table[$row][] = $levels[ITP];
$table[$row][] = checkbox_array('mniveau', ITP, $mniveau);
$table[$row][] = $tabs;
$table[$row][] = $levels[IVV];
$table[$row][] = checkbox_array('mniveau', IVV, $mniveau);
// $table [$row][] = $tabs;
// $table [$row][] = $this->lang->line("gvv_membres_field_dateivv");
// $table [$row][] = $this->gvvmetadata->input_field("membres", 'dateivv', $dateivv);

$row++;
$table[$row][] = "";
$table[$row][] = "";
$table[$row][] = "";
$table[$row][] = "";

$row++;
$table[$row][] = "";
$table[$row][] = "";
$table[$row][] = "";
$table[$row][] = "";


display_form_table($table);

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array(
        'event_type',
        'date',
        'glider_flight',
        'plane_flight',
        'comment'
    ),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'controller' => "event",
    'param' => $mlogin
);

echo heading("membre_title_glider_training", 4);

echo $this->lang->line("gvv_membres_field_inst_glider") . nbs() .
    $this->gvvmetadata->input_field("membres", 'inst_glider', $inst_glider) . br(2);

if ($action != CREATION) {
    echo $this->gvvmetadata->table("vue_exp_vv", $attrs, "");

    echo heading("membre_title_FAI", 4);
    echo $this->gvvmetadata->table("vue_exp_fai", $attrs, "");
}
echo form_fieldset_close();

// Commentaires
echo form_fieldset($this->lang->line("membre_fieldset_information"));
echo $this->gvvmetadata->input_field("membres", 'comment', $comment);
echo form_fieldset_close();

$bar = array(
    array('label' => $this->lang->line("gvv_button_validate"), 'type' => "submit", 'id' => 'validate', 'name' => "button"),
    array('label' => $this->lang->line("gvv_button_print"), 'url' => "$controller/adhesion/$mlogin", 'role' => 'ca'),
    array('label' => $this->lang->line("membre_button_subscription"), 'url' => "$controller/adhesion/$mlogin/1", 'role' => 'ca'),

);
echo button_bar4($bar);

echo form_close();

echo '</div>';
?>