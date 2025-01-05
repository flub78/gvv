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
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
$this->lang->load('achats');

echo '<div id="body" class="body ui-widget-content">';

echo heading("gvv_achats_title_year", 3);

echo year_selector($controller, $year, $year_selector);
echo br(2);

$attrs = array(
	'controller' => $controller,
	'fields' => array('produit', 'prix_unit', 'quantite', 'prix'),
	'mode' => "ro",
	'class' => "sql_table fixed_datatable"
);

echo $this->gvvmetadata->table("vue_achats_per_year", $attrs, "");

$bar = array(
	array('label' => "Excel", 'url' => "$controller/ventes_csv/$year", 'role' => 'ca'),
	array('label' => "Pdf", 'url' => controller_url("rapports/ventes"), 'role' => 'ca'),
);
echo button_bar4($bar);

echo '</div>';
