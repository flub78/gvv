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
 * Formulaire de saisie des terrains
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('vols_decouverte');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading("gvv_vols_decouverte_element", 3);

?>

<?php

// echo validation_errors();
$form = $this->gvvmetadata->form_flexbox('vols_decouverte', array(
	'date_vente' => $date_vente,
	'product' => $product,
	'beneficiaire' => $beneficiaire,
	'de_la_part' => $de_la_part,
	'beneficiaire_email' => $beneficiaire_email,
	'urgence' => $urgence,
	'cancelled' => $cancelled
));

// echo $form;

$index_page = $this->config->item('index_page');

$index = ($index_page) ? "$index_page/" : "";
$select_flight_url = site_url() . "vols_decouverte/action_clear";

?>
</div>


<div class="d-flex flex-wrap">

<form action="<?= $select_flight_url ?>" method="post" class="w-100">
    <div class="form-group">
		<div> Selectionnez un vol :</div> 
		<div><?php  echo dropdown_field("vd_id", "", $vd_selector, array()) ?></div>

    </div>
    <button type="submit" class="btn btn-primary mt-3">Sélectionner</button>
</form>

</div>

