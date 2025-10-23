<!-- VIEW: application/views/events_types/bs_tableView.php -->
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
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('events_types');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_events_types_title_list", 3);
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('name', 'activite', 'en_vol', 'multiple', 'expirable', 'ordre', 'annual'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable table table-striped"
);

// Create button above the table
echo '<div class="mb-3">'
    . '<a href="' . site_url('events_types/create') . '" class="btn btn-sm btn-success">'
    . '<i class="fas fa-plus" aria-hidden="true"></i> '
    . $this->lang->line('gvv_button_create')
    . '</a>'
    . '</div>';

echo $this->eventstypesmetadata->table("vue_events_types", $attrs);

/*
$bar = array(
	array('label' => "Excel", 'url' =>"$controller/ventes_csv/$year", 'role' => 'ca'),
	array('label' => "Pdf", 'url' => controller_url("rapports/ventes"), 'role' => 'ca'),
	);
echo button_bar4($bar);
*/

echo '</div>';
