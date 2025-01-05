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
 * Formulaire de saisie d'un événement
 * ----------------------------------------------------------------------------------------
 */
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('events');

echo '<div id="body" class="body ui-widget-content">';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo validation_errors();

echo heading("gvv_events_title", 3);

// ------------------------------------------------------------------------
echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));
// hidden controller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
echo form_hidden('emlogin', $emlogin, '');
echo form_hidden('id', $id, '');
$table = array();
$row = 0;
echo heading($this->lang->line("gvv_events_field_emlogin") . " " . $mimage, 4);
$row++;
$table[$row][] = $this->lang->line("gvv_events_title_event") . " ";
$table[$row][] = dropdown_field('etype', $etype, $event_type_selector, "");
$table[$row][] = $this->lang->line("gvv_events_short_field_date") . " ";
$table[$row][] =  $this->gvvmetadata->input_field('events', 'edate', $edate);

$table[$row][] = $this->lang->line("gvv_events_field_event_type") . " ";
if (($evaid != 0) && ($evaid != null)) {
	$event_type_flight = "avion";
	$display_plane = "block";
	$display_glider = "none";
} else if (($evpid != 0) && ($evpid != null)) {
	$event_type_flight = "planeur";
	$display_plane = "none";
	$display_glider = "block";
} else {
	$event_type_flight = "aucun";
	$display_plane = "none";
	$display_glider = "none";
}
$table[$row][] = dropdown_field(
	'event_type_flight',
	$event_type_flight,
	$this->lang->line("gvv_events_type_selector"),
	"onchange=get_plane_selector(this);"
);

$table[$row][] = dropdown_field('evpid', $evpid, $planeurs_selector, "id='dropdown_planeurs' style='display:" . $display_glider . "'") .
	dropdown_field('evaid', $evaid, $avions_selector, "id='dropdown_avions' style='display:" . $display_plane . "'");
display_form_table($table);

echo $this->lang->line("gvv_events_field_date_expiration") . nbs()
	.  $this->gvvmetadata->input_field('events', 'date_expiration', $date_expiration);

$table = array();
$row = 0;
$table[$row][] = $this->lang->line("gvv_events_short_field_comment") . " ";
display_form_table($table);
$table = array();
$row = 0;
$data = array(
	'name' => 'ecomment',
	'cols' => 64,
	'rows' => 2,
	'value' => $ecomment,
	'maxlength' => 128
);
// $table [$row][] = form_textarea("ecomment", $ecomment, "maxlength=128");
$table[$row][] = form_textarea($data);
display_form_table($table);

echo validation_button($action);
echo form_close();

echo '</div>';
