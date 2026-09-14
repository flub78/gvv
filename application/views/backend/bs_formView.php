<!-- VIEW: application/views/backend/bs_formView.php -->
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
// Formulaire de saisie utilisateur
// ----------------------------------------------------------------------------------------
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('backend');

echo '<div id="body" class="body container-fluid">';

$controller = 'backend';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo validation_errors();

echo heading("gvv_backend_title", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// On affiche tous les champs dans un tableau. C'est plus simple de remplir d'abbord le tableau
// et de l'afficher ensuite, surtout pour modifier l'affichage

echo form_hidden('action', $action);
if (isset($id)) echo form_hidden('id', $id);

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

$backend_fields = array(
	array($this->lang->line("gvv_backend_field_nom"), input_field('username', $username, array('type'  => 'text', 'size' => '25'))),
	array($this->lang->line("gvv_backend_field_password"), form_password('password', $password, "'type'='text', 'size'='34'")),
	array($this->lang->line("gvv_backend_field_passconf"), form_password('passconf', $passconf, "'type'='text', 'size'='34'")),
	array($this->lang->line("gvv_backend_field_email"), input_field('email', $email, array('type'  => 'text', 'size' => '100'))),
	array($this->lang->line("gvv_backend_field_role"), dropdown_field('role_id', $role_id, $role_selector, "")),
);
foreach ($backend_fields as $field) {
	echo '<div class="form-group row mb-3">';
	echo '<label class="col-sm-3 col-form-label">' . $field[0] . ': </label>';
	echo '<div class="col-sm-9">' . $field[1] . '</div>';
	echo '</div>';
}

// Le boutton de validation
if ($action != VISUALISATION) {
	echo validation_button($action);
}
echo form_close();

echo '</div>';
