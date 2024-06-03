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
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('admin');

echo '<div id="body" class="body container-fluid">';

echo heading($this->lang->line("gvv_admin_title_restore"), 3);
echo $error . "<br>";
if (isset($backups)) {
	echo heading("Sauvegardes disponibles", 4);
	echo ul($backups);
	echo br();
}

echo p($this->lang->line("gvv_admin_db_warning"), 'class="error"' );
echo br();
echo p($this->lang->line("gvv_admin_db_select"));
echo form_open_multipart('admin/do_restore');
echo '<input type="file" name="userfile" size="50" /><br><br>';
$checked = "";
if ($erase_db) {
	$checked = ' checked="checked" ';
}
echo $this->lang->line("gvv_admin_db_overwrite") . ': '
		. "<input type=\"checkbox\" name=\"erase_db\" $checked value=\"$erase_db\" id=\"erase_db\"  />" 
		. br(2);
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_button_validate")));
echo form_close('</div>');
?>
