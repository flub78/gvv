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
 * Vue affichant les stats des événements
 *
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->load->library('DataTable');

$this->lang->load('events');

echo '<div id="body" class="body ui-widget-content container-fluid">';

echo heading($title_key, 3);

echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// --------------------------------------------------------------------------------------------------
// Filtre
// echo form_hidden('filter_active', $filter_active);

// $tab = 3;
// echo form_fieldset("Filtre", array('class' => 'coolfieldset filtre',
// 		'title' => 'Cliquez pour afficher/masquer les critères de selection'));
// echo "<div>";
// echo form_open(controller_url($controller) . "/filterValidation/" . $action, array('name' => 'saisie') );
// echo "<table><tr><td>\n";
// echo "Menbres: " . enumerate_radio_fields(array(0 => 'Tous', 1 => 'inactifs', 2 => 'actifs'), 'filter_membre_actif', $filter_membre_actif);

// echo "</td></tr><tr><td>";
// echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => 'Filtrer'));
// echo nbs();
// echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => 'Afficher tout'));
// echo "</td></tr></table>\n";
// echo form_close();
// echo "</div>";
// echo form_fieldset_close();

$formation[0][0] = $this->lang->line("gvv_events_field_emlogin");
$table = new DataTable(array(
	'title' => "",
	'values' => $formation,
	'controller' => '',
	'class' => "datatable table",
	'create' => "",
	'first' => 0
));

$table->display();

$bar = array(
	array('label' => "Excel", 'url' => "$controller/csv/$type"),
	array('label' => "Pdf", 'url' => "$controller/pdf/$type"),
);
echo br() . button_bar4($bar);

echo '</div>';

echo '</div>';
