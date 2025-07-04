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

$this->lang->load('openflyers');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_of_synchro_soldes", 3);
if (!empty($error)) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}

echo p($this->lang->line("gvv_of_explain_soldes"));
?>
<a href="https://openflyers.com/abbeville/index.php?menuAction=admin_view_favorite_generic_report&menuParameter=141">Balance des comptes utilisateurs</a>
<?php
echo p($this->lang->line("gvv_of_warning2"), 'class="error"');

echo br();
echo p($this->lang->line("gvv_of_select"));
echo form_open_multipart('openflyers/import_soldes');
echo '<input type="file" name="userfile" size="50" /><br><br>';

echo form_input(array(
	'type' => 'submit',
	'name' => 'button',
	'value' => $this->lang->line("gvv_button_validate"),
	'class' => 'btn btn-primary'
));
echo form_close('</div>');
