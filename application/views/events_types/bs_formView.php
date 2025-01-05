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
 * Formulaire de saisie avion
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');

$this->load->view('bs_banner');

$this->lang->load('events_types');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading($this->lang->line("gvv_events_types_title"), 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

echo validation_errors();

?>
<div class="d-md-flex flex-row mb-2">
	<!-- -->
	<div class="me-3 mb-2">
		<?= $this->lang->line("gvv_events_types_field_name") . ": " ?>
		<input type="text" name="name" value="<?= $name ?>" id="name" size="64" />
	</div>

	<div class="me-3 mb-2">
		<?= $this->lang->line("gvv_events_types_field_activite") . ": " ?>
		<?= $this->eventstypesmetadata->input_field("events_types", 'activite', $activite) ?>
	</div>

</div>

<div class="d-md-flex flex-row  mb-2">

	<div class="me-3 mb-2">
		<?= $this->lang->line("gvv_events_types_field_en_vol") . ": " ?>
		<?= $this->eventstypesmetadata->input_field("events_types", 'en_vol', $en_vol) ?> </div>

	<div class="me-3 mb-2">
		<?= $this->lang->line("gvv_events_types_field_multiple") . ": " ?>
		<?= $this->eventstypesmetadata->input_field("events_types", 'multiple', $multiple) ?>
	</div>
	<div class="me-3 mb-2">
		<?= $this->lang->line("gvv_events_types_field_expirable") . ": " ?>
		<?= $this->eventstypesmetadata->input_field("events_types", 'expirable', $expirable) ?>

	</div>
	<div class="me-3 mb-2">
		<?= $this->lang->line("gvv_events_types_field_ordre") . ": " ?>
		<input type="text" name="ordre" value="<?= $ordre ?>" id="ordre" size="2" />
	</div>
	<div class="me-3 mb-2">
		<?= $this->lang->line("gvv_events_types_field_annual") . ": " ?>
		<?= $this->eventstypesmetadata->input_field("events_types", 'annual', $annual) ?>

	</div>
</div>

<?php

echo validation_button($action);
echo form_close();

echo '</div>';
?>