<!-- VIEW: application/views/achats/bs_formView.php -->
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
 * Formulaire de saisie achat
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
	echo p($message) .br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo validation_errors(); 
if ($action == CREATION) {
	echo heading("Saisie d'un achat", 3);
} elseif ($action == MODIFICATION) {
	echo heading("Modification d'un achat", 3);
} else {
	echo heading("Visualisation d'un achat", 3);
}

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie') );

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
echo form_hidden('saisie_par', $saisie_par, '');
echo form_hidden('id', $id);
echo form_hidden('action', $action);

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

// echo validation_errors();
echo ($this->gvvmetadata->form('achats', array(
	'date' => $date,
	'produit' => $produit,
	'pilote' => $pilote,
	'quantite' => $quantite,
    'description' => $description,
	'num_cheque' => $num_cheque
)));

echo validation_button ($action);
echo form_close();

echo '<p class="mt-1">On ne peut pas attacher de justificatifs aux achats qui sont des écritures passées directement par GVV.</p>';
echo '</div>';
?>
