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
 * Vue table pour les terrains
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('attachments');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_attachments_title", 3);
// Provide controller to JS
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// Year selector like other views
?>
<div class='mb-3'>
    <?= year_selector($controller, $year, $year_selector) ?>
</div>
<?php

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('referenced_table', 'referenced_id', 'description', 'file', 'section'),
    'mode' => "rw",
    'class' => "datatable table table-striped"
);

// Create button above the table
echo '<div class="mb-3">'
    . '<a href="' . site_url('attachments/create') . '" class="btn btn-sm btn-success">'
    . '<i class="fas fa-plus" aria-hidden="true"></i> '
    . $this->lang->line('gvv_button_create')
    . '</a>'
    . '</div>';

echo $this->gvvmetadata->table("vue_attachments", $attrs, "");

echo '</div>';
