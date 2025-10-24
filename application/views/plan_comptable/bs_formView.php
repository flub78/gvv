<!-- VIEW: application/views/plan_comptable/bs_formView.php -->
<?php
// ----------------------------------------------------------------------------------------
//    GVV Gestion vol à voile
//    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// Formulaire de saisie planeur
// ----------------------------------------------------------------------------------------
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');
$this->lang->load('planc');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo validation_errors();
if ($action == CREATION) {
	echo heading("new_codec", 3);
} elseif ($action == MODIFICATION) {
	echo heading("update_codec", 3);
} else {
	echo heading("view_codec", 3);
}

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden controller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

// On affiche tous les champs dans un tableau. C'est plus simple de remplir d'abord le tableau
// et de l'afficher ensuite, surtout pour modifier l'affichage

$table = array();
$row = 0;
$table[$row][] = $this->lang->line("codec") . ": ";
if ($action == CREATION) {
	$table[$row][] = input_field('pcode', $pcode, array('type'  => 'text', 'size' => '10'));
} else {
	$table[$row][] = dropdown_field(
		'pcode',
		$pcode,
		$code_selector,
		"id='selector' onchange='mlogin_changed();'"
	);
}

$row++;
$table[$row][] = $this->lang->line("codec_desc") . ": ";
$table[$row][] = input_field('pdesc', $pdesc, array('type'  => 'text', 'size' => '50'));

display_form_table($table);

echo validation_button($action);
echo form_close();

echo '</div>';
