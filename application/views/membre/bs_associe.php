<!-- VIEW: application/views/membre/bs_associe.php -->
<?php
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
//    base restauration view

$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('admin');

echo '<div id="body" class="body ui-widget-content">';

echo heading($title, 3);
if (isset($error)) {
	echo p($error, 'class="error"');
}
echo br();
echo "Associer le licencié : " . $image . nbs();
echo "Numéro de licence : $licence_number " . br();
echo br();
echo form_open_multipart('membre/associe/' . $licence_number);
echo "Avec le membre : " . form_dropdown('mlogin', $selector, "", "");

echo nbs() . form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_button_validate"))) . br(2);

echo "Si le membre n'existe pas : "
	. anchor(controller_url('membre/heva_create/' . $licence_number), "Création", array("class" => "btn btn-primary"));

echo form_close('</div>');
