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
 * Formulaire de saisie d'un vol avion
 * 
 * @package vues
 */
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
$this->lang->load('vols_avion');

echo '<div id="body" class="body ui-widget-content">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo validation_errors();

echo heading("gvv_vols_avion_title", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

echo form_hidden('vaid', $vaid);
echo form_hidden('saisie_par', $saisie_par, '');
echo form_hidden('horametres_en_min', $horametres_en_min, '');
echo form_hidden('machines', $machines, '');

// echo validation_errors();
$tabs = nbs(3);
$table = array();
$row = 0;
$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "vadate") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "vadate", $vadate);
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "vamacid") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "vamacid", $vamacid);
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "vapilid") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "vapilid", $vapilid);
$row++;

$table[$row][] = "<span class=\"DC\">" . $this->gvvmetadata->field_long_name("volsa", "vadc") . ":" . '</span>';
$table[$row][] = "<span class=\"DC\">" . $this->gvvmetadata->input_field("volsa", "vadc", $vadc) . '</span>'
    . "<span id=\"instruction\">"
    . $tabs . $this->gvvmetadata->field_long_name("volsa", "vainst") . ":"
    . $tabs . $this->gvvmetadata->input_field("volsa", "vainst", $vainst)
    . "</span>";
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "vahdeb") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "vahdeb", $vahdeb)
    . $tabs . $this->gvvmetadata->field_long_name("volsa", "vahfin") . ":"
    . $tabs . $this->gvvmetadata->input_field("volsa", "vahfin", $vahfin);
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "vacdeb") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "vacdeb", $vacdeb, 'rw', array('id' => "debut"))
    . $tabs . $this->gvvmetadata->field_long_name("volsa", "vacfin") . ":"
    . $tabs . $this->gvvmetadata->input_field("volsa", "vacfin", $vacfin, 'rw', array('id' => "fin"))
    . $tabs . $this->gvvmetadata->field_long_name("volsa", "vaduree") . ":"
    . $tabs . $this->gvvmetadata->input_field("volsa", "vaduree", $vaduree)
    . $tabs . '<span id="hora_format">' . '</span>';
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "vaobs") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "vaobs", $vaobs);
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "vacategorie") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "vacategorie", $vacategorie)
    . $tabs . $this->gvvmetadata->field_long_name("volsa", "vanumvi") . ":"
    . $tabs . $this->gvvmetadata->input_field("volsa", "vanumvi", $vanumvi);
$row++;

if ($payeur_non_pilote) {
    $table[$row][] = '<span class="payeur">' . $this->lang->line("gvv_vols_avion_label_payer") . ":" . '</span>';
    $line = $this->gvvmetadata->input_field("volsa", 'payeur', $payeur);
    if ($partage) $line .=
        nbs(2) . $this->lang->line("gvv_vols_avion_label_percent") . " : " . $this->gvvmetadata->input_field("volsa", 'pourcentage', $pourcentage);
    $table[$row][] = '<span class="payeur">' . $line . '</span>';
    $row++;
}

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "vanbpax") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "vanbpax", $vanbpax);
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "vaatt") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "vaatt", $vaatt);
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "local") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "local", $local);
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "nuit") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "nuit", $nuit);
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "valieudeco") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "valieudeco", $valieudeco)
    . $tabs . $this->gvvmetadata->field_long_name("volsa", "valieuatt") . ":"
    . $tabs . $this->gvvmetadata->input_field("volsa", "valieuatt", $valieuatt);
$row++;

$table[$row][] = $this->gvvmetadata->field_long_name("volsa", "reappro") . ":";
$table[$row][] = $this->gvvmetadata->input_field("volsa", "reappro", $reappro)
    . $tabs . $this->gvvmetadata->field_long_name("volsa", "essence") . ":"
    . $tabs . $this->gvvmetadata->input_field("volsa", "essence", $essence);
$row++;

echo form_fieldset($this->lang->line("gvv_vols_avion_fieldset_flight"));
display_form_table($table);
echo form_fieldset_close();

// Fieldset Formation
echo br();
echo form_fieldset($this->lang->line("gvv_vols_avion_fieldset_formation"), array(
    'class' => 'coolfieldset filtre',
    'title' => $this->lang->line("gvv_vols_avion_tooltip_formation")
));
echo '<div>';
$str = "";
foreach ($certificats as $certificat) {
    $id = $certificat['id'];
    // $value = isset($certificat_values[ $id]) ? $certificat_values[ $id] : null; 
    $str .= $certificat['label'] . nbs() .
        checkbox_array('certificat_values', $id, $certificat_values) . nbs(3);
}
echo $str;
echo '</div>';
echo form_fieldset_close();
echo br();


echo validation_button($action);
echo form_close();

echo br();
echo $this->lang->line("gvv_vols_avion_tip_billing");
echo '</div>';
?>
<script type="text/javascript" src="<?php echo js_url('form_vols_avion'); ?>"></script>