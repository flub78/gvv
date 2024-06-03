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
 * Vue affichant les stats des événements
 *
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body ui-widget-content container-fluid">';

echo heading("Résultats formation", 3);
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

echo "Année: " . dropdown_field('year', $year, $year_selector, "id='selector' onchange=new_selection('stats');");
echo br(2);

echo $this->gvvmetadata->table("events_year", array("class" => "datatable"), "");

echo '</div>';
?>