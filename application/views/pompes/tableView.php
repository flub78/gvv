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
 * Vue liste des utilisations des pompes
 * 
 * @package vues
 */

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

echo '<div id="body" class="body ui-widget-content">';

echo heading("Utilisation de la pompe", 3);

// -----------------------------------------------------------------------------------------
// Filtre
echo form_hidden('filter_active', $filter_active);


$tab = 3;
echo form_fieldset("Filtre", array('class' => 'coolfieldset filtre',
    'title' => 'Cliquez pour afficher/masquer les critères de selection'));
echo "<div>";
echo form_open(controller_url($controller) . "/filterValidation/" . $action, array('name' => 'saisie') );
echo form_hidden('pnum', $pnum); // 0: 100LL (défaut), 1: 98SP
echo "<table><tr><td>\n";
echo "Date: " . input_field('filter_date', $filter_date, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAAA', 'class' => 'datepicker'));
echo nbs($tab); // "</td><td>";
echo "Jusqu'a: ". input_field('date_end', $date_end, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAAA', 'class' => 'datepicker'));
echo nbs($tab); // "</td><td>";
echo "Utilisateur: ". dropdown_field('filter_pilote', $filter_pilote, $pilote_selector, "");
echo "</td></tr><tr><td>";
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => 'Filtrer'));
echo nbs();
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => 'Afficher tout'));
echo "</td></tr></table>\n";
echo form_close();
echo "</div>";
echo form_fieldset_close();

// ------------------------------------- fin filtre

$totaux = $totaux['totaux'][0];

$footer = array();
$footer[] = array( '', '', '', 'Total:', $totaux['total_qte'], $totaux['total_prix'], '', '', '');
$attrs = array(
	'controller' => $controller,
    'actions' => array ('edit', 'delete'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'footer' => $footer);

echo $this->gvvmetadata->table("vue_pompes", $attrs, "");

echo '</div>';

?>
