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
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
$this->lang->load('backend');

echo '<div id="body" class="body ui-widget-content">';

$controller = 'backend';

if (isset($message)) {
	echo p($message) .br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo validation_errors(); 

echo heading($this->lang->line("gvv_backend_title"), 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie') );

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// On affiche tous les champs dans un tableau. C'est plus simple de remplir d'abbord le tableau
// et de l'afficher ensuite, surtout pour modifier l'affichage

echo form_hidden('action', $action);
if (isset($id)) echo form_hidden('id', $id);

$table = array();
$row = 0;
$table [$row][] = $this->lang->line("gvv_backend_field_nom") . ": ";
$table [$row][] = input_field('username', $username, array('type'  => 'text', 'size' => '25'));

$row++;
$table [$row][] =  $this->lang->line("gvv_backend_field_password") . ": ";
$table [$row][] = form_password('password', $password, "'type'='text', 'size'='34'");

$row++;
$table [$row][] =  $this->lang->line("gvv_backend_field_passconf") . ": ";
$table [$row][] = form_password('passconf', $passconf, "'type'='text', 'size'='34'");

$row++;
$table [$row][] =  $this->lang->line("gvv_backend_field_email") . ": ";
$table [$row][] = input_field('email', $email, array('type'  => 'text', 'size' => '100'));

$row++;
$table [$row][] =  $this->lang->line("gvv_backend_field_role") . ": ";
$table [$row][] = dropdown_field('role_id', $role_id, $role_selector, "");

display_form_table($table);

// Le boutton de validation
echo "<table><tr><td>\n";
if ($action != VISUALISATION) {
	echo validation_button ($action);
}
echo form_close();
echo "</td><td>";
echo "</td></tr></table>\n";

echo '</div>';
?>
