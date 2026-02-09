<!-- VIEW: application/views/meteo/bs_tableView.php -->
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
 * Vue table pour les cartes de préparation des vols
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('meteo');

echo '<div id="body" class="body container-fluid">';

echo heading($this->lang->line('meteo_admin_title'), 3);

echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('title', 'type', 'category', 'display_order', 'visible'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable table table-striped"
);

// Create button above the table
if ($has_modification_rights) {
    echo '<div class="mb-3">'
        . '<a href="' . site_url('meteo/create') . '" class="btn btn-sm btn-success">'
        . '<i class="fas fa-plus" aria-hidden="true"></i> '
        . $this->lang->line('gvv_button_create')
        . '</a> '
        . '<a href="' . site_url('meteo') . '" class="btn btn-sm btn-outline-secondary ms-2">'
        . '<i class="fas fa-eye" aria-hidden="true"></i> '
        . $this->lang->line('meteo_view_public')
        . '</a>'
        . '</div>';
}

echo $this->gvvmetadata->table('vue_preparation_cards', $attrs, "");

echo '</div>';

