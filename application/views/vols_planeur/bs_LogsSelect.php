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
 * Formulaire de saisie d'une planche de vol planeur
 *
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('vols_planeur');

echo '<div id="body" class="body container-fluid">';

/*
if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo validation_errors();
*/


	
/*
******************************************************************************************
*************************   Formulaire saisie DATE + Terrain   ***************************
******************************************************************************************
*/
	
	
	echo form_open(controller_url($controller) . "/plancheauto");

	echo form_fieldset($this->lang->line("gvv_vols_planeur_fieldset_flight_logs")); 

	echo form_hidden('auto', 1);

	echo $this->lang->line("gvv_date") . ":".nbs(2).$this->gvvmetadata->input_field("volsp", 'vpdate', $vpdate);
	echo nbs(3);
	echo $this->lang->line("gvv_site") . ":" . nbs(2) . form_dropdown('terrain', $terrains ); 
	echo nbs(3);
	echo $this->lang->line("gvv_vols_planeur_logs_timezone") . ":" . nbs(2);
	echo '<select name="z" id="tz" size="1">
  <option value="4">GMT+4
  <option value="3">GMT+3
  <option value="2">GMT+2
  <option value="1">GMT+1
  <option value="0">GMT
  <option value="-1">GMT-1
  </select>
  ';
		
	echo form_fieldset_close();

	echo validation_button (VALIDATION);
	
	echo form_close();	


echo '</div>';

echo '<script type="text/javascript" src="'. js_url('form_vols_planeur') . '"></script>';
?>