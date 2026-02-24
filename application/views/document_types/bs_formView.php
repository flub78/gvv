<!-- VIEW: application/views/document_types/bs_formView.php -->
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
 * Formulaire de saisie des types de documents
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('document_types');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading("document_types_title", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden controller url for javascript access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

$form_fields = array(
    'code' => isset($code) ? $code : '',
    'name' => isset($name) ? $name : '',
    'section_id' => isset($section_id) ? $section_id : '',
    'scope' => isset($scope) ? $scope : 'pilot',
    'required' => isset($required) ? $required : 0,
    'has_expiration' => isset($has_expiration) ? $has_expiration : 1,
    'storage_by_year' => isset($storage_by_year) ? $storage_by_year : 0,
    'alert_days_before' => isset($alert_days_before) ? $alert_days_before : 30,
    'active' => isset($active) ? $active : 1,
    'display_order' => isset($display_order) ? $display_order : 0
);

// Add id as hidden field for edit/view (not creation)
if ($action != CREATION) {
    echo form_hidden('id', $id);
}

echo ($this->gvvmetadata->form('document_types', $form_fields));

echo validation_button($action);
echo form_close();

echo '</div>';
