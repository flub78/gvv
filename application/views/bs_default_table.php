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
 * Vue table pour les avions
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body ui-widget-content">';

echo heading($table_title, 3);

echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

$this->table->set_template(array('table_open' => '<table class="datatable" cellspacing="0" width="100%">'));
echo $this->table->generate($data_table);

/*
$bar = array(
	array('label' => "Excel", 'url' =>"$controller/ventes_csv/$year", 'role' => 'ca'),
	array('label' => "Pdf", 'url' => controller_url("rapports/ventes"), 'role' => 'ca'),
	);
echo button_bar4($bar);
*/

echo '</div>';
