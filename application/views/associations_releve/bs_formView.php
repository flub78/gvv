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
 * Formulaire de saisie des associations relevé
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('associations_releve');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading("gvv_associations_releve_title_association", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

echo ($this->gvvmetadata->form('associations_releve', array(
	'string_releve' => isset($string_releve) ? $string_releve : '',
	'type' => isset($type) ? $type : '',
	'id_compte_gvv' => isset($id_compte_gvv) ? $id_compte_gvv : ''
)));

echo validation_button($action);
echo form_close();

echo '</div>';