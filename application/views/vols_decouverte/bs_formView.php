<!-- VIEW: application/views/vols_decouverte/bs_formView.php -->
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

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden controller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
echo form_hidden('saisie_par', $saisie_par, '');
echo form_hidden('id', $id);

// echo validation_errors();

$url = current_url();
if (strpos($url, 'pre_flight')) {
	$modification_type = 'pre_flight';
} elseif (strpos($url, 'done')) {
	$modification_type = 'done';
} else {
	$modification_type = 'edit';
}
if ($modification_type == 'edit') {
	echo ($this->gvvmetadata->form('vols_decouverte', array(
		'date_vente' => $date_vente,
		'product' => $product,
		'beneficiaire' => $beneficiaire,
		'de_la_part' => $de_la_part,
		'occasion' => $occasion,
		'beneficiaire_email' => $beneficiaire_email,
		'urgence' => $urgence,
		'date_vol' => $date_vol,
		'pilote' => $pilote,
		'airplane_immat' => $airplane_immat,
		'cancelled' => $cancelled,
		'paiement' => $paiement,
		'participation' => $participation,

	)));
} else {


	echo form_hidden('date_vente', date_db2ht($date_vente), '');
	echo form_hidden('product', $product, '');
	echo form_hidden('beneficiaire', $beneficiaire, '');
	echo form_hidden('de_la_part', $de_la_part, '');
	echo form_hidden('occasion', $occasion, '');
	echo form_hidden('beneficiaire_email', $beneficiaire_email, '');
	echo form_hidden('cancelled', $cancelled, '');
	echo form_hidden('paiement', $paiement, '');
	echo form_hidden('participation', $participation, '');

	if ($modification_type == 'pre_flight') {
		echo form_hidden('date_vol', $date_vol, '');
		echo form_hidden('pilote', $pilote, '');
		echo form_hidden('airplane_immat', $airplane_immat, '');

?>
		<div class="d-flex flex-wrap">
			<div class="m-2 ">
				<div class="mb-2 ">Numéro: <?= $id ?> </div>
				<div class="mb-2 ">Date de vente: <?= date_db2ht($date_vente) ?> </div>
				<div class="mb-2 ">Description: <?= $description ?></div>
				<div class="mb-2 ">Bénéficiaire: <?= $beneficiaire ?></div>
			</div>
			<div class="m-2 ">
				<div class="mb-2 ">De la part de: <?= $de_la_part ?></div>
				<div class="mb-2 ">Email bénéficiaire: <?= $beneficiaire_email ?></div>
			</div>

		</div>
	<?php

		echo ($this->gvvmetadata->form('vols_decouverte', array(
			'urgence' => $urgence,
		)));
	} else {
		// done
		echo form_hidden('urgence', $urgence, '');

	?>
		<div class="d-flex flex-wrap">
			<div class="m-2 ">
				<div class="mb-2 ">Numéro: <?= $id ?> </div>
				<div class="mb-2 ">Date de vente: <?= date_db2ht($date_vente) ?> </div>
				<div class="mb-2 ">Description: <?= $description ?></div>
				<div class="mb-2 ">Bénéficiaire: <?= $beneficiaire ?></div>
			</div>
			<div class="m-2 ">
				<div class="mb-2 ">De la part de: <?= $de_la_part ?></div>
				<div class="mb-2 ">Email bénéficiaire: <?= $beneficiaire_email ?></div>
				<div class="mb-2 ">Contact en cas d'urgence: <?= $urgence ?></div>
			</div>

		</div>
<?php

		echo ($this->gvvmetadata->form('vols_decouverte', array(
			'date_vol' => $date_vol,
			'pilote' => $pilote,
			'airplane_immat' => $airplane_immat,
		)));
	}
}

echo validation_button($action);
echo form_close();

echo '</div>';
