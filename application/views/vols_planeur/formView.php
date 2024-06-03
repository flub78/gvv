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
 * Formulaire de saisie d'un vol planeur
 *
 * @package vues
 */
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('vols_planeur');

echo '<div id="body" class="body ui-widget-content">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo validation_errors();

echo heading($this->lang->line("gvv_vols_planeur_title"), 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie') );

// On affiche tous les champs dans un tableau. C'est plus simple de remplir d'abbord le tableau
// et de l'afficher ensuite, surtout pour modifier l'affichage

echo form_hidden('vpid', $vpid);
echo form_hidden('saisie_par', $saisie_par, '');

$tabs = nbs(3);
$table = array();
$row = 0;
$table [$row][] = $this->lang->line("gvv_volsp_field_vpdate") . ":";
$table [$row][] = $this->gvvmetadata->input_field("volsp", 'vpdate', $vpdate);

$row++;
$table [$row][] = $this->lang->line("gvv_volsp_field_vpmacid") . ":";
$table [$row][] = $this->gvvmetadata->input_field("volsp", 'vpmacid', $vpmacid);

$row++;
$table [$row][] = $this->lang->line("gvv_volsp_field_vppilid") . ":";
$table [$row][] = $this->gvvmetadata->input_field("volsp", 'vppilid', $vppilid)
. nbs(2) . "<span id=\"DC\">" . $this->lang->line("gvv_volsp_field_vpdc") . ": " . $this->gvvmetadata->input_field("volsp", 'vpdc', $vpdc) 
. nbs(2) . "<span id=\"instruction\">" . $this->lang->line("gvv_volsp_field_instructeur") . ":" . $this->gvvmetadata->input_field("volsp", 'vpinst', $vpinst) . '</span>'
. nbs(2) . "<span id=\"passager\">". $this->lang->line("gvv_volsp_field_pas") . ":" . $this->gvvmetadata->input_field("volsp", 'vppassager', $vppassager) . '</span>' . '</span>'
		;

$row++;
$table [$row][] = $this->lang->line("gvv_volsp_field_vpcdeb") . ":";
$attrs = array('onChange' => "calculp()");
$table [$row][] = $this->gvvmetadata->input_field("volsp", 'vpcdeb', $vpcdeb, "rw", $attrs)
. $tabs . $this->lang->line("gvv_volsp_field_vpcfin") . ":"
. $this->gvvmetadata->input_field("volsp", 'vpcfin', $vpcfin, "rw", $attrs)
. $tabs . $this->lang->line("gvv_volsp_field_vpduree") . ": "
. $this->gvvmetadata->input_field("volsp", 'vpduree', $vpduree);

$row++;
$table [$row][] = $this->lang->line("gvv_volsp_field_vpobs") . ":";
$table [$row][] = $this->gvvmetadata->input_field("volsp", 'vpobs', $vpobs);

$row++;
$altitude = ($remorque_100eme) ? $this->lang->line("gvv_vols_planeur_label_centieme") : $this->lang->line("gvv_vols_planeur_label_alt");
$table [$row][] = $this->lang->line("gvv_volsp_field_vpautonome") . ":";
$txt = $this->gvvmetadata->input_field("volsp", 'vpautonome', $vpautonome)
. "<span class=\"altitude\">$altitude: " . $this->gvvmetadata->input_field("volsp", 'vpaltrem', $vpaltrem);
if (isset($vpticcolle)) {
    $txt .= $this->lang->line("gvv_vols_planeur_label_ticket") . ": ".$this->gvvmetadata->input_field("volsp", 'vpticcolle', $vpticcolle);
}
$txt .= '</span>';
 $table [$row][] = $txt;
 
$row++;
$table [$row][] = "<span class=\"altitude\">" . $this->lang->line("gvv_volsp_field_remorqueur") . ":" . '</span>';
$table [$row][] = "<span class=\"altitude\">" . $this->gvvmetadata->input_field("volsp", 'remorqueur', $remorqueur)
. $tabs . $this->lang->line("gvv_volsp_field_pilote_remorqueur") . ": " . $this->gvvmetadata->input_field("volsp", 'pilote_remorqueur', $pilote_remorqueur) . '</span>';

$row++;
$table [$row][] = "<span class=\"treuil\">" . $this->lang->line("gvv_vols_planeur_label_whincher") . ": ". '</span>';
$table [$row][] = "<span class=\"treuil\">" . $this->gvvmetadata->input_field("volsp", 'vptreuillard', $vptreuillard) . '</span>';

$row++;
$percent_selector = array ('0' => 0, '50' => 50, '100' => 100);
$table [$row][] = '<span class="VI">' . $this->lang->line("gvv_volsp_field_vpcategorie") . ":" . '</span>';
$table [$row][] = '<span class="VI">' . $this->gvvmetadata->input_field("volsp", 'vpcategorie', $vpcategorie)
. nbs(2) . $this->lang->line("gvv_volsp_field_vpnumvi") . ":"
. nbs(2) . $this->gvvmetadata->input_field("volsp", 'vpnumvi', $vpnumvi) . '</span>';

if ($payeur_non_pilote) {
    $row++;
    $table [$row][] = '<span class="payeur">' . $this->lang->line("gvv_vols_planeur_label_payer") . ":" . '</span>';
    $line = $this->gvvmetadata->input_field("volsp", 'payeur', $payeur);
    if ($partage) $line .= nbs(2) . $this->lang->line("gvv_vols_planeur_label_percent") . ": " . $this->gvvmetadata->input_field("volsp", 'pourcentage', $pourcentage);
    $table [$row][] = '<span class="payeur">' . $line . '</span>';
}
$row++;
$table [$row][] = $this->lang->line("gvv_volsp_field_vpnbkm");
$table [$row][] = $this->gvvmetadata->input_field("volsp", 'vpnbkm', $vpnbkm);

$row++;
$table [$row][] = $this->lang->line("gvv_volsp_field_vplieudeco");
$table [$row][] = $this->gvvmetadata->input_field("volsp", 'vplieudeco', $vplieudeco)
. $tabs . $this->lang->line("gvv_volsp_field_vplieuatt")
. $tabs . $this->gvvmetadata->input_field("volsp", 'vplieuatt', $vplieuatt);

$row++;
$table [$row][] = "<span class=\"autonome\">" . $this->lang->line("gvv_volsp_field_tempmoteur") . "</span>";
$table [$row][] = "<span class=\"autonome\">" . $this->gvvmetadata->input_field("volsp", 'tempmoteur', $tempmoteur). "</span>";

$row++;
$table [$row][] = "<span class=\"autonome\">" . $this->lang->line("gvv_volsp_field_avitaillement") . "</span>";
$table [$row][] = "<span class=\"autonome\">" . $this->gvvmetadata->input_field("volsp", 'reappro', $reappro)
. $tabs . $this->lang->line("gvv_volsp_field_essence")
. $tabs . $this->gvvmetadata->input_field("volsp", 'essence', $essence) . "</span>";

echo form_fieldset($this->lang->line("gvv_vols_planeur_fieldset_flight") ); 
display_form_table($table);
echo form_fieldset_close();


echo br();
// Fieldset Formation
echo form_fieldset($this->lang->line("gvv_vols_planeur_fieldset_formation"), array('class' => 'coolfieldset filtre',
    'title' => $this->lang->line("gvv_vols_planeur_tooltip_formation") ));
echo '<div>'; 
$str = "";
foreach ($certificats as $certificat) {
    $id = $certificat['id'];
    $value = isset($certificat_values[ $id]) ? $certificat_values[ $id] : null; 
    $str .= $certificat['label'] . nbs() . 
    checkbox_array('certificat_values', $id, $certificat_values) . nbs(3);
}
echo $str;
echo '</div>';
echo form_fieldset_close();

// Fieldset certificats FAI
echo br();
echo form_fieldset($this->lang->line("gvv_vols_planeur_fieldset_FAI"), array('class' => 'coolfieldset filtre',
    'title' => $this->lang->line("gvv_vols_planeur_tooltip_FAI") ));
echo '<div>'; 
$str = "";
foreach ($certificats_fai as $certificat_fai) {
    $id = $certificat_fai['id'];
    $value = isset($certificat_fai_values[ $id]) ? $certificat_fai_values[ $id] : null; 
    $str .= $certificat_fai['label'] . nbs() . 
    checkbox_array('certificat_fai_values', $id, $certificat_fai_values) . nbs(3);
}
echo $str; // ul($list);
echo '</div>';
echo form_fieldset_close();

echo br();
echo validation_button ($action);
echo form_close();

echo br();
$list = array(
  $this->lang->line("gvv_vols_planeur_tooltip_1"),
  $this->lang->line("gvv_vols_planeur_tooltip_2")
);
echo ul($list);

echo '</div>';
?>
<script type="text/javascript" src="<?php echo js_url('form_vols_planeur'); ?>"></script>


