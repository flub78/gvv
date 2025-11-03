<!-- VIEW: application/views/attachments/bs_tableView.php -->
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

// Modify actions and mode based on user permissions
if (isset($can_modify) && $can_modify) {
    // Tresorier: full read-write access
    $attrs['actions'] = array('edit', 'delete');
    $attrs['mode'] = "rw";
} else {
    // Bureau: read-only access
    $attrs['actions'] = array(); // No edit/delete actions
    $attrs['mode'] = "ro";       // Read-only mode
}

// Create button above the table - only for tresorier
if (isset($can_modify) && $can_modify) {
    echo '<div class="mb-3">'
        . '<a href="' . site_url('attachments/create') . '" class="btn btn-sm btn-success">'
        . '<i class="fas fa-plus" aria-hidden="true"></i> '
        . $this->lang->line('gvv_button_create')
        . '</a>'
        . '</div>';
} else {
    // Show info message for bureau users  
    echo '<div class="mb-3">'
        . '<div class="alert alert-info" role="alert">'
        . '<i class="fas fa-info-circle"></i> '
        . 'Mode consultation - Droits de modification requis pour créer ou modifier des justificatifs'
        . '</div>'
        . '</div>';
}

echo $this->gvvmetadata->table("vue_attachments", $attrs, "");

echo '</div>';
