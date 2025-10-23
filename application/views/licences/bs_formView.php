<!-- VIEW: application/views/licences/bs_formView.php -->
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
 * Formulaire de saisie licence
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

if ($action == CREATION) {
	echo heading("Nouvel licence", 3);
} elseif ($action == MODIFICATION) {
	echo heading("Modification d'une licence", 3);
} else {
	echo heading("Visualisation d'une licence", 3);
}

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie') );

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// echo form_hidden('macimmat', $macimmat);

echo validation_errors();
echo ($this->gvvmetadata->form('licences', array(
	'pilote' => $pilote,
	'year' => $year,
	'date' => $date,
	'comment' => $comment,
)));

echo validation_button ($action);
echo form_close();

echo '</div>';
?>
