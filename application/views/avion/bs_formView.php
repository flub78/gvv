<!-- VIEW: application/views/avion/bs_formView.php -->
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
$this->load->view('bs_banner');
$this->load->view('bs_menu');
$this->lang->load('avion');
echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading("gvv_avion_title", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// echo form_hidden('macimmat', $macimmat);

// echo validation_errors();
echo ($this->gvvmetadata->form('machinesa', array(
    'macconstruc' => $macconstruc,
    'macmodele' => $macmodele,
    'macimmat' => $macimmat,
    'macnbhdv' => $macnbhdv,
    'macplaces' => $macplaces,
    'macrem' => $macrem,
    'maprive' => $maprive,
    'maprix' => $maprix,
    'maprixdc' => $maprixdc,
    'horametre_en_minutes' => $horametre_en_minutes,
    'actif' => $actif,
    'fabrication' => $fabrication,
    'comment' => $comment
)));

echo validation_button($action);
echo form_close();

echo '</div>';
