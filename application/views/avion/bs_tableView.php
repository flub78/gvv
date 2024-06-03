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
 * Vue table pour les avions
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('avion');

echo '<div id="body" class="body container-fluid">';

echo heading($this->lang->line("gvv_avion_title_list"), 3);

echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// --------------------------------------------------------------------------------------------------
// Filtre
echo form_hidden('filter_active', $filter_active);

$tab = 3;
echo form_fieldset($this->lang->line("gvv_str_filter"), array('class' => 'coolfieldset filtre mb-3 mt-3',
    'title' => $this->lang->line("gvv_str_filter_tooltip")));
echo "<div>";
echo form_open(controller_url($controller) . "/filterValidation/" . $action, array('name' => 'saisie') );
echo "<table><tr><td>\n";
echo $this->lang->line("avion_filter_active") . ": " 
		. enumerate_radio_fields($this->lang->line("avion_filter_active_select"), 'filter_machine_actif', $filter_machine_actif);

echo "</td></tr><tr><td>";
	
echo $this->lang->line("avion_filter_owner") . ": " .  enumerate_radio_fields($this->lang->line("avion_filter_owner_select"), 'filter_proprio', $filter_proprio);

echo "</td></tr><tr><td>";
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_select")));
echo nbs();
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_display")));
echo "</td></tr></table>\n";
echo form_close();
echo "</div>";
echo form_fieldset_close();

// --------------------------------------------------------------------------------------------------
// Data
$attrs = array(
	'controller' => $controller,
    'actions' => array ('edit', 'delete'),
    'fields' => array('macmodele', 'macconstruc', 'macimmat', 'macplaces', 'macrem', 
   	'maprive', 'actif', 'vols', 'fabrication'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable table table-striped");

echo $this->gvvmetadata->table("vue_avions", $attrs, "");

/*
$bar = array(
	array('label' => "Excel", 'url' =>"$controller/ventes_csv/$year", 'role' => 'ca'),
	array('label' => "Pdf", 'url' => controller_url("rapports/ventes"), 'role' => 'ca'),
	);
echo button_bar4($bar);
*/

echo '</div>';

?>
