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
 * Vue table pour les achats
 * 
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body container-fluid">';

echo heading("Rapport DGAC", 3);

echo year_selector($controller, $year, $year_selector);
echo br(2);

$list = array(
	"Association = $association", 
	"code d'identification = $code",
);
echo ul($list);

$list = array(
	"Heures de remorquage = $total_towing", 
);
echo ul($list);

echo table_from_array ($activity, array(
	'fields' => array('', 'Heures Planeur', 'Nbre Remorqués', 'Nbre Treuillées', 'Lachés solo', 
        	'BPP théoriques', 'BPP homolgués',
        	'Nbre Lachés campagne', 'Km campagne'),
	'align' => array('left', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right'),
	'class' => 'fixed_datatable table'
));

$list = array(
	"Heures de remorquage = $total_towing", 
	"Heures total = $total_glider", 
);
echo ul($list);

echo table_from_array ($machine_activity, 
	array('fields' => array('Modèle', 'Fabrication', 'Immat', 'Heures remorquage', 'Heures planeur'),
		'align' => array('left', 'right', 'left', 'right', 'right'),
		'class' => 'fixed_datatable table'));

/*
$bar = array(
	array('label' => "Excel", 'url' =>"$controller/ventes_csv/$year", 'role' => 'ca'),
	array('label' => "Pdf", 'url' => controller_url("rapports/ventes"), 'role' => 'ca'),
	);
echo button_bar4($bar);
*/

echo '</div>';

?>
