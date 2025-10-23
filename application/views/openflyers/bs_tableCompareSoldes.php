<!-- VIEW: application/views/openflyers/bs_tableCompareSoldes.php -->
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
 * Vue table pour les terrains
 * 
 * @package vues
 * @file bs_tableSoldes.php
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('openflyers');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_of_title_soldes_compare", 3);

if ($error) {
    echo '<p class="text-danger">' . $error . "</p>\n";
}

?>
	<p>Seuls les comptes au solde non null sont affichés.</p>
    <p>Date de comparaison: <?=date_db2ht($compare_date)?></p>
<?php


// Utilisé pour les comptes clients
echo table_from_array ($soldes, array(
    'fields' => array('Compte OF', 'Nom', 'Profil',  'Compte GVV', 'Solde OF', 'Solde GVV'),
    'align' => array('right', 'left', 'left', 'center', 'right', 'right'),
    'class' => 'datatable table'
));

echo '</div>';

?>

<?php