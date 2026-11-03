<!-- VIEW: application/views/bs_configView.php -->
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
// Formulaires pour la configuration
//
// ----------------------------------------------------------------------------------------

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('config');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo heading("gvv_config_title", 3);

// L'identité du club se modifie dans l'onglet Club de la page Configuration.
echo '<div class="alert alert-info" id="config-identity-moved"><i class="fas fa-info-circle me-1"></i>'
	. sprintf($this->lang->line('gvv_config_identity_moved'),
		anchor('parametres/index/club', $this->lang->line('gvv_config_identity_moved_link')))
	. '</div>';

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

$config_fields = array(
	array($this->lang->line("gvv_config_field_gcalendar"), input_field('calendar_id', $calendar_id, array('type'  => 'text', 'size' => 50))),
	array($this->lang->line("gvv_config_field_facturation"), input_field('club', $club, array('type'  => 'text', 'size' => 32))),
	array($this->lang->line("gvv_config_field_gcalendar_url"), input_field('url_gcalendar', $url_gcalendar, array('type'  => 'text', 'size' => '100'))),
	array($this->lang->line("gvv_config_field_planche_auto"), input_field('url_planche_auto', $url_planche_auto, array('type'  => 'text', 'size' => '100'))),
	array($this->lang->line("gvv_config_maintenance_message"), form_textarea(array(
		'name' => 'maintenance_message',
		'value' => isset($maintenance_message) ? $maintenance_message : '',
		'rows' => '3',
		'cols' => '80'
	))),
	array($this->lang->line("gvv_config_ffvv_id"), input_field('ffvv_id', $ffvv_id, array('type'  => 'text', 'size' => '8'))),
	array($this->lang->line("gvv_config_ffvv_pwd"), input_field('ffvv_pwd', $ffvv_pwd, array('type'  => 'text', 'size' => '36'))),
	array($this->lang->line("gvv_config_ffvv_product"), dropdown_field('ffvv_product', $ffvv_product, $product_selector, "")),
	array($this->lang->line("gvv_config_gesasso"), checkbox_field('gesasso', $gesasso, '')),
);
foreach ($config_fields as $field) {
	echo '<div class="form-group row mb-3">';
	echo '<label class="col-sm-3 col-form-label">' . $field[0] . '</label>';
	echo '<div class="col-sm-9">' . $field[1] . '</div>';
	echo '</div>';
}

// Le boutton de validation
?>
<input type="submit" name="button" value="<?= $this->lang->line("gvv_button_validate") ?>" class="btn btn-primary" />
<?php
echo form_close();

echo '</div>';
?>