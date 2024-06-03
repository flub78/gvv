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
 * Vue planche (table) pour les membres
 * 
 * @package vues
 */

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

echo '<div id="body" class="body ui-widget-content">';

echo heading("Liste des licenciés", 3);

$attrs = array(
	'controller' => $controller,
    'actions' => array ('edit', 'delete'),
    'fields' => array('mnom', 'mprenom', 'madresse', 'cp', 'ville'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable",
    'numbered' => 1);

echo $this->gvvmetadata->table("membres", $attrs);

$bar = array(
	array('label' => "Excel", 'url' =>"$controller/export/csv", 'role' => 'ca'),
	array('label' => "Pdf", 'url' => "$controller/export/pdf", 'role' => 'ca'),
	);
echo button_bar4($bar);

echo '</div>';

?>
