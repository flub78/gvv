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


// echo validation_errors();
echo ($this->gvvmetadata->form('vols_decouverte', array(
	'date_vente' => $date_vente,
	'product' => $product,
	'beneficiaire' => $beneficiaire,
	'de_la_part' => $de_la_part,
	'occasion' => $occasion,
	'beneficiaire_email' => $beneficiaire_email,
	'urgence' => $urgence,
	// 'date_planning' => $date_planning,
	// 'time_planning' => $time_planning,
	'date_vol' => $date_vol,
	// 'time_vol' => $time_vol,
	'pilote' => $pilote,
	'airplane_immat' => $airplane_immat,
	'cancelled' => $cancelled,
	'paiement' => $paiement,
	'participation' => $participation,

)));

echo validation_button($action);
echo form_close();

echo '</div>';
